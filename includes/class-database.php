<?php
namespace Oportunidades\Includes;

use Exception;
use wpdb;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Database {
    /**
     * WordPress database instance.
     *
     * @var wpdb
     */
    protected $wpdb;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
    }

    /**
     * Create the oportunidades table.
     */
    public function create_table() {
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset_collate = $this->wpdb->get_charset_collate();
        $table_name      = OPORTUNIDADES_TABLE_NAME;

        $sql = "CREATE TABLE {$table_name} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            external_id VARCHAR(255) NULL,
            title TEXT NOT NULL,
            summary LONGTEXT NULL,
            awarding_entity VARCHAR(255) NULL,
            value_normalized DECIMAL(20,2) NULL,
            deadline_date DATETIME NULL,
            url TEXT NULL,
            categories LONGTEXT NULL,
            filters LONGTEXT NULL,
            custom_fields LONGTEXT NULL,
            origin VARCHAR(100) NULL,
            schema_version VARCHAR(50) NULL,
            hash CHAR(64) NOT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            KEY hash (hash),
            KEY deadline_date (deadline_date),
            KEY awarding_entity (awarding_entity)
        ) {$charset_collate};";

        dbDelta( $sql );
    }

    /**
     * Insert or update a record using hash deduplication.
     */
    public function upsert_record( array $record ) {
        $table = OPORTUNIDADES_TABLE_NAME;
        $hash  = $record['hash'];

        $existing_id = $this->wpdb->get_var( $this->wpdb->prepare( "SELECT id FROM {$table} WHERE hash = %s", $hash ) );

        $data = [
            'external_id'      => $record['external_id'] ?? null,
            'title'            => $record['title'],
            'summary'          => $record['summary'] ?? null,
            'awarding_entity'  => $record['awarding_entity'] ?? null,
            'value_normalized' => $record['value_normalized'] ?? null,
            'deadline_date'    => $record['deadline_date'] ?? null,
            'url'              => $record['url'] ?? null,
            'categories'       => isset( $record['categories'] ) ? wp_json_encode( (array) $record['categories'], JSON_UNESCAPED_UNICODE ) : null,
            'filters'          => isset( $record['filters'] ) ? wp_json_encode( (array) $record['filters'], JSON_UNESCAPED_UNICODE ) : null,
            'custom_fields'    => isset( $record['custom_fields'] ) ? wp_json_encode( (array) $record['custom_fields'], JSON_UNESCAPED_UNICODE ) : null,
            'origin'           => $record['origin'] ?? null,
            'schema_version'   => $record['schema_version'] ?? null,
            'hash'             => $hash,
            'updated_at'       => current_time( 'mysql', true ),
        ];

        if ( $existing_id ) {
            $this->wpdb->update( $table, $data, [ 'id' => $existing_id ] );
            return (int) $existing_id;
        }

        $data['created_at'] = current_time( 'mysql', true );
        $this->wpdb->insert( $table, $data );

        return (int) $this->wpdb->insert_id;
    }

    /**
     * Retrieve records applying filters.
     */
    public function query_records( array $args = [] ) {
        $args = $this->parse_args( $args );
        $table = OPORTUNIDADES_TABLE_NAME;

        [ $where_sql, $bindings ] = $this->build_where_clause( $args );

        $orderby   = in_array( strtolower( $args['orderby'] ), [ 'deadline_date', 'created_at', 'value_normalized' ], true ) ? $args['orderby'] : 'deadline_date';
        $order     = 'DESC' === strtoupper( $args['order'] ) ? 'DESC' : 'ASC';
        $limit     = (int) $args['per_page'];
        $offset    = ( (int) $args['paged'] - 1 ) * $limit;

        $sql = "SELECT * FROM {$table} {$where_sql} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";
        $bindings[] = $limit;
        $bindings[] = $offset;

        $prepared = $this->wpdb->prepare( $sql, $bindings );
        $rows     = $this->wpdb->get_results( $prepared, ARRAY_A );

        return array_map( [ $this, 'hydrate_record' ], $rows );
    }

    /**
     * Count records for pagination.
     */
    public function count_records( array $args = [] ) {
        $args = $this->parse_args( $args );
        $table = OPORTUNIDADES_TABLE_NAME;

        [ $where_sql, $bindings ] = $this->build_where_clause( $args );

        $sql = "SELECT COUNT(*) FROM {$table} {$where_sql}";
        $prepared = $this->wpdb->prepare( $sql, $bindings );

        return (int) $this->wpdb->get_var( $prepared );
    }

    /**
     * Parse and merge default args.
     */
    protected function parse_args( array $args ) {
        $defaults = [
            'search'      => '',
            'district'    => '',
            'category'    => '',
            'min_value'   => null,
            'max_value'   => null,
            'keyword'     => '',
            'orderby'     => 'deadline_date',
            'order'       => 'ASC',
            'paged'       => 1,
            'per_page'    => 20,
        ];

        $args = wp_parse_args( $args, $defaults );
        $args['per_page'] = max( 1, (int) $args['per_page'] );
        $args['paged']    = max( 1, (int) $args['paged'] );

        return $args;
    }

    /**
     * Build where clause based on args.
     */
    protected function build_where_clause( array $args ) {
        $where    = [ '1=1' ];
        $bindings = [];
        $table    = OPORTUNIDADES_TABLE_NAME;

        if ( ! empty( $args['search'] ) ) {
            $where[]   = 'title LIKE %s';
            $bindings[] = '%' . $this->wpdb->esc_like( $args['search'] ) . '%';
        }

        if ( ! empty( $args['keyword'] ) ) {
            $where[]   = 'summary LIKE %s';
            $bindings[] = '%' . $this->wpdb->esc_like( $args['keyword'] ) . '%';
        }

        if ( ! empty( $args['district'] ) ) {
            $where[]   = 'custom_fields LIKE %s';
            $bindings[] = '%"distrito":%"' . $this->wpdb->esc_like( $args['district'] ) . '%';
        }

        if ( ! empty( $args['category'] ) ) {
            $where[]   = 'categories LIKE %s';
            $bindings[] = '%"' . $this->wpdb->esc_like( $args['category'] ) . '"%';
        }

        if ( null !== $args['min_value'] ) {
            $where[]   = 'value_normalized >= %f';
            $bindings[] = (float) $args['min_value'];
        }

        if ( null !== $args['max_value'] ) {
            $where[]   = 'value_normalized <= %f';
            $bindings[] = (float) $args['max_value'];
        }

        return [ 'WHERE ' . implode( ' AND ', $where ), $bindings ];
    }

    /**
     * Convert JSON fields back to arrays.
     */
    protected function hydrate_record( array $row ) {
        foreach ( [ 'categories', 'filters', 'custom_fields' ] as $key ) {
            if ( isset( $row[ $key ] ) && ! empty( $row[ $key ] ) ) {
                $decoded     = json_decode( $row[ $key ], true );
                $row[ $key ] = null === $decoded ? [] : $decoded;
            } else {
                $row[ $key ] = [];
            }
        }

        return $row;
    }

    /**
     * Remove all stored records.
     *
     * @throws Exception When the operation fails.
     */
    public function reset() {
        $table = OPORTUNIDADES_TABLE_NAME;

        $result = $this->wpdb->query( "TRUNCATE TABLE {$table}" );

        if ( false === $result ) {
            throw new Exception( __( 'Não foi possível limpar os registos.', 'oportunidades' ) );
        }
    }
}
