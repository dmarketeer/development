<?php
namespace Oportunidades\Includes;

use DateTimeImmutable;
use Exception;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Importer {
    /**
     * Database handler.
     *
     * @var Database
     */
    protected $database;

    public function __construct( Database $database ) {
        $this->database = $database;
    }

    /**
     * Import opportunities from array payload.
     */
    public function import( array $payload, $origin = 'rest' ) {
        $summary = [
            'processed' => 0,
            'inserted'  => 0,
            'updated'   => 0,
            'errors'    => [],
        ];

        if ( empty( $payload['oportunidades'] ) || ! is_array( $payload['oportunidades'] ) ) {
            throw new Exception( __( 'Payload inválido: campo "oportunidades" em falta.', 'oportunidades' ) );
        }

        $schema_version = $payload['schema_version'] ?? '1.0';
        $settings       = get_option( OPORTUNIDADES_OPTION_NAME, [] );

        foreach ( $payload['oportunidades'] as $item ) {
            $summary['processed']++;

            try {
                $record      = $this->normalize_record( $item, $schema_version, $origin, $settings );
                $existing_id = $this->maybe_get_existing_id_by_hash( $record['hash'] );
                $id          = $this->database->upsert_record( $record );

                if ( $existing_id && $existing_id === $id ) {
                    $summary['updated']++;
                } elseif ( $id ) {
                    $summary['inserted']++;
                }
            } catch ( Exception $e ) {
                $summary['errors'][] = $e->getMessage();
            }
        }

        update_option( 'oportunidades_last_ingestion', current_time( 'mysql', true ) );
        update_option( 'oportunidades_cache_version', time() );

        return $summary;
    }

    /**
     * Normalizes a single record.
     */
    protected function normalize_record( array $item, $schema_version, $origin, array $settings ) {
        // Mapeamento de campos alternativos do Google Sheets
        $title = sanitize_text_field(
            $item['titulo'] ??
            $item['title'] ??
            $item['Contrato'] ??  // Campo "Contrato" do Google Sheets
            ''
        );
        if ( empty( $title ) ) {
            throw new Exception( __( 'Registo sem título.', 'oportunidades' ) );
        }

        $deadline = $item['prazo'] ??
                    $item['deadline'] ??
                    $item['Prazo das propostas'] ??  // Campo do Google Sheets
                    null;
        if ( $deadline ) {
            $deadline = $this->parse_date( $deadline );
        }

        $value = $item['valor_normalizado'] ??
                 $item['valor'] ??
                 $item['Preço base s/IVA (€)'] ??  // Campo do Google Sheets
                 null;
        if ( null !== $value ) {
            $value = floatval( preg_replace( '/[^0-9.,-]/', '', (string) $value ) );
        }

        $categories = $item['categorias'] ?? $item['categories'] ?? [];
        $filters    = $item['filtros'] ?? $item['filters'] ?? [];
        if ( empty( $filters ) && ! empty( $settings['default_filters'] ) ) {
            $filters = $settings['default_filters'];
        }

        $custom_fields = $item['campos_personalizados'] ?? $item['custom_fields'] ?? [];
        if ( ! is_array( $custom_fields ) ) {
            $custom_fields = [];
        }

        // Adicionar campos do Google Sheets aos custom_fields
        if ( isset( $item['Distrito'] ) ) {
            $custom_fields['distrito'] = sanitize_text_field( $item['Distrito'] );
        }
        if ( isset( $item['Prazo'] ) ) {
            $custom_fields['prazo_execucao'] = sanitize_text_field( $item['Prazo'] );
        }
        if ( isset( $item['Data do Anúncio'] ) ) {
            $custom_fields['data_anuncio'] = sanitize_text_field( $item['Data do Anúncio'] );
        }

        if ( ! empty( $settings['custom_field_map'] ) ) {
            $map = json_decode( $settings['custom_field_map'], true );
            if ( is_array( $map ) ) {
                foreach ( $map as $source => $label ) {
                    if ( isset( $item[ $source ] ) ) {
                        $custom_fields[ $label ] = sanitize_text_field( $item[ $source ] );
                    }
                }
            }
        }

        $hash = $this->calculate_hash( [
            $item['identificador'] ?? $item['id'] ?? $item['Anúncio'] ?? $title,
            $deadline,
            $value,
        ] );

        return [
            'external_id'      => sanitize_text_field( $item['identificador'] ?? $item['id'] ?? $item['Anúncio'] ?? '' ),
            'title'            => $title,
            'summary'          => wp_kses_post( $item['resumo'] ?? $item['descricao'] ?? $item['Descrição'] ?? $item['summary'] ?? '' ),
            'awarding_entity'  => sanitize_text_field( $item['entidade_adjudicante'] ?? $item['Adjudicante'] ?? $item['entity'] ?? '' ),
            'value_normalized' => $value,
            'deadline_date'    => $deadline,
            'url'              => esc_url_raw( $item['url'] ?? $item['link'] ?? $item['Link PDF'] ?? '' ),
            'categories'       => $categories,
            'filters'          => $filters,
            'custom_fields'    => $custom_fields,
            'origin'           => $origin,
            'schema_version'   => $schema_version,
            'hash'             => $hash,
        ];
    }

    /**
     * Parse date string into MySQL format.
     */
    protected function parse_date( $date ) {
        try {
            $dt = new DateTimeImmutable( $date );
            return $dt->format( 'Y-m-d H:i:s' );
        } catch ( Exception $e ) {
            return null;
        }
    }

    /**
     * Calculate hash from meaningful fields.
     */
    protected function calculate_hash( array $parts ) {
        return hash( 'sha256', implode( '|', array_map( 'wp_json_encode', $parts ) ) );
    }

    /**
     * Retrieve existing record ID by hash.
     */
    protected function maybe_get_existing_id_by_hash( $hash ) {
        global $wpdb;
        $table = OPORTUNIDADES_TABLE_NAME;

        $id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE hash = %s", $hash ) );

        return $id ? (int) $id : null;
    }

    /**
     * Ensure the public page exists.
     */
    public function maybe_schedule_page_creation() {
        if ( get_option( 'oportunidades_public_page_id' ) ) {
            return;
        }

        $page_id = wp_insert_post(
            [
                'post_title'   => __( 'Oportunidades', 'oportunidades' ),
                'post_content' => '[oportunidades]',
                'post_status'  => 'publish',
                'post_type'    => 'page',
            ]
        );

        if ( $page_id && ! is_wp_error( $page_id ) ) {
            update_option( 'oportunidades_public_page_id', $page_id );
        }
    }
}
