<?php
namespace Oportunidades\Includes;

use Exception;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Google_Sheets_Fetcher {
	/**
	 * Fetch data from Google Sheets.
	 *
	 * @param string $spreadsheet_id Google Spreadsheet ID
	 * @param string $range Range to fetch (e.g., 'Sheet1!A1:Z1000')
	 * @param string $api_key Google API Key
	 * @return array|WP_Error The fetched data or error
	 */
	public function fetch_from_sheets( $spreadsheet_id, $range = 'Sheet1', $api_key = '' ) {
		if ( empty( $spreadsheet_id ) ) {
			return new \WP_Error(
				'sheets_missing_id',
				__( 'ID da planilha não fornecido.', 'oportunidades' )
			);
		}

		if ( empty( $api_key ) ) {
			return new \WP_Error(
				'sheets_missing_key',
				__( 'API Key do Google não configurada.', 'oportunidades' )
			);
		}

		// Construct Google Sheets API URL
		$api_url = sprintf(
			'https://sheets.googleapis.com/v4/spreadsheets/%s/values/%s?key=%s',
			$spreadsheet_id,
			urlencode( $range ),
			$api_key
		);

		// Fetch the data
		$response = wp_remote_get(
			$api_url,
			[
				'timeout'     => 30,
				'user-agent'  => 'WordPress/Oportunidades-Plugin',
				'sslverify'   => true,
				'headers'     => [
					'Accept' => 'application/json',
				],
			]
		);

		// Check for errors
		if ( is_wp_error( $response ) ) {
			return new \WP_Error(
				'sheets_fetch_error',
				sprintf(
					__( 'Erro ao conectar ao Google Sheets: %s', 'oportunidades' ),
					$response->get_error_message()
				)
			);
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		if ( $status_code !== 200 ) {
			$error_message = sprintf(
				__( 'Erro ao buscar dados do Google Sheets. Código HTTP: %d', 'oportunidades' ),
				$status_code
			);

			// Add specific help for common errors
			if ( $status_code === 403 ) {
				$error_message .= "\n\n" . __( 'Verifique se: 1) A API Key está correta, 2) A Google Sheets API está habilitada no seu projeto, 3) A planilha tem permissões de visualização pública.', 'oportunidades' );
			} elseif ( $status_code === 404 ) {
				$error_message .= "\n\n" . sprintf(
					__( 'A planilha não foi encontrada. Verifique se o ID "%s" está correto.', 'oportunidades' ),
					$spreadsheet_id
				);
			}

			return new \WP_Error(
				'sheets_fetch_error',
				$error_message
			);
		}

		// Get and parse content
		$body = wp_remote_retrieve_body( $response );
		if ( empty( $body ) ) {
			return new \WP_Error(
				'sheets_empty_response',
				__( 'Resposta vazia do Google Sheets.', 'oportunidades' )
			);
		}

		// Try to decode JSON
		$data = json_decode( $body, true );
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			return new \WP_Error(
				'sheets_json_error',
				sprintf(
					__( 'Erro ao decodificar JSON: %s', 'oportunidades' ),
					json_last_error_msg()
				)
			);
		}

		// Convert Google Sheets format to our expected format
		return $this->convert_sheets_data( $data );
	}

	/**
	 * Convert Google Sheets API response to our format.
	 *
	 * @param array $sheets_data Raw data from Google Sheets API
	 * @return array Converted data
	 */
	protected function convert_sheets_data( $sheets_data ) {
		if ( ! isset( $sheets_data['values'] ) || empty( $sheets_data['values'] ) ) {
			return new \WP_Error(
				'sheets_no_data',
				__( 'Nenhum dado encontrado na planilha.', 'oportunidades' )
			);
		}

		$rows = $sheets_data['values'];

		// First row should be the header
                $header = array_shift( $rows );

                // Normalizar valores do cabeçalho para evitar espaços em branco extras
                $header = $this->normalize_header_row( $header );

		if ( empty( $header ) ) {
			return new \WP_Error(
				'sheets_no_header',
				__( 'Cabeçalho não encontrado na planilha.', 'oportunidades' )
			);
		}

		$oportunidades = [];
                foreach ( $rows as $row ) {
                        $row = $this->normalize_data_row( $row, count( $header ) );

                        if ( $this->row_is_effectively_empty( $row ) ) {
                                continue;
                        }

                        // Combine header with row values
                        $item = array_combine( $header, $row );

			if ( $item !== false ) {
				$oportunidades[] = $item;
			}
		}

                return [
                        'schema_version' => '1.0',
                        'oportunidades'  => $oportunidades,
                ];
        }

        /**
         * Normalize header row values.
         *
         * @param array $header Raw header row from Google Sheets
         * @return array Normalized header values
         */
        protected function normalize_header_row( array $header ) {
                return array_map(
                        function ( $value ) {
                                return is_string( $value ) ? trim( $value ) : $value;
                        },
                        $header
                );
        }

        /**
         * Normalize a data row to match the header size.
         *
         * @param array $row Data row values
         * @param int   $target_length Header length to match
         * @return array Normalized row
         */
        protected function normalize_data_row( array $row, $target_length ) {
                $row = array_map(
                        function ( $value ) {
                                return is_string( $value ) ? trim( $value ) : $value;
                        },
                        $row
                );

                if ( count( $row ) < $target_length ) {
                        $row = array_pad( $row, $target_length, '' );
                } elseif ( count( $row ) > $target_length ) {
                        $row = array_slice( $row, 0, $target_length );
                }

                return $row;
        }

        /**
         * Check if the row is effectively empty (only blank strings or nulls).
         *
         * @param array $row Normalized row values
         * @return bool True when row has no meaningful values
         */
        protected function row_is_effectively_empty( array $row ) {
                foreach ( $row as $value ) {
                        if ( is_string( $value ) ) {
                                if ( $value !== '' ) {
                                        return false;
                                }
                        } elseif ( null !== $value ) {
                                return false;
                        }
                }

                return true;
        }

	/**
	 * Validate Google Sheets configuration.
	 *
	 * @param string $spreadsheet_id Google Spreadsheet ID
	 * @param string $range Range to fetch
	 * @param string $api_key Google API Key
	 * @return true|WP_Error True if valid, WP_Error otherwise
	 */
	public function validate_config( $spreadsheet_id, $range = 'Sheet1', $api_key = '' ) {
		if ( empty( $spreadsheet_id ) ) {
			return new \WP_Error(
				'sheets_missing_id',
				__( 'ID da planilha é obrigatório.', 'oportunidades' )
			);
		}

		if ( empty( $api_key ) ) {
			return new \WP_Error(
				'sheets_missing_key',
				__( 'API Key do Google é obrigatória.', 'oportunidades' )
			);
		}

		// Try to fetch a small portion of data to validate
		$api_url = sprintf(
			'https://sheets.googleapis.com/v4/spreadsheets/%s/values/%s?key=%s',
			$spreadsheet_id,
			urlencode( $range ),
			$api_key
		);

		$response = wp_remote_get(
			$api_url,
			[
				'timeout'     => 15,
				'user-agent'  => 'WordPress/Oportunidades-Plugin',
				'headers'     => [
					'Accept' => 'application/json',
				],
			]
		);

		if ( is_wp_error( $response ) ) {
			return new \WP_Error(
				'sheets_validation_error',
				sprintf(
					__( 'Erro ao validar planilha: %s', 'oportunidades' ),
					$response->get_error_message()
				)
			);
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		if ( $status_code === 404 ) {
			return new \WP_Error(
				'sheets_not_found',
				sprintf(
					__( 'Planilha não encontrada: %s', 'oportunidades' ),
					$spreadsheet_id
				)
			);
		} elseif ( $status_code === 403 ) {
			return new \WP_Error(
				'sheets_access_denied',
				__( 'Acesso negado. Verifique se a API Key está correta e se a planilha tem permissões adequadas.', 'oportunidades' )
			);
		} elseif ( $status_code !== 200 ) {
			return new \WP_Error(
				'sheets_validation_error',
				sprintf(
					__( 'Erro ao validar planilha. Código HTTP: %d', 'oportunidades' ),
					$status_code
				)
			);
		}

		return true;
	}

	/**
	 * Fetch and import data from Google Sheets in one step.
	 *
	 * @param Importer $importer Importer instance
	 * @param array $settings Plugin settings
	 * @return array Import summary
	 */
	public function fetch_and_import( Importer $importer, array $settings ) {
		$spreadsheet_id = $settings['sheets_spreadsheet_id'] ?? '';
		$range          = $settings['sheets_range'] ?? 'Sheet1';
		$api_key        = $settings['sheets_api_key'] ?? '';

		if ( empty( $spreadsheet_id ) ) {
			throw new Exception( __( 'ID da planilha Google Sheets não configurado.', 'oportunidades' ) );
		}

		if ( empty( $api_key ) ) {
			throw new Exception( __( 'API Key do Google não configurada.', 'oportunidades' ) );
		}

		// Fetch data from Google Sheets
		$data = $this->fetch_from_sheets( $spreadsheet_id, $range, $api_key );
		if ( is_wp_error( $data ) ) {
			throw new Exception( $data->get_error_message() );
		}

		// Import the fetched data
		$summary = $importer->import( $data, 'google_sheets' );

		// Log the last fetch time
		update_option( 'oportunidades_last_sheets_fetch', current_time( 'mysql', true ) );

		return $summary;
	}
}
