<?php
namespace Oportunidades\Includes;

use Exception;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Github_Fetcher {
    /**
     * Fetch the latest dataset JSON from a GitHub repository path.
     *
     * @param string $repository Owner/repository string.
     * @param string $branch     Branch or ref to inspect.
     * @param string $path       Optional path within the repository.
     *
     * @return array Parsed dataset payload.
     *
     * @throws Exception If the repository or file cannot be retrieved.
     */
    public function fetch_latest_dataset( $repository, $branch = 'main', $path = '' ) {
        $repository = trim( (string) $repository );
        if ( empty( $repository ) ) {
            throw new Exception( __( 'Repositório do GitHub em falta.', 'oportunidades' ) );
        }

        $path      = trim( (string) $path, '/' );
        $segments  = array_map( 'rawurlencode', explode( '/', $repository ) );
        $base_path = implode( '/', $segments );
        $url       = sprintf( 'https://api.github.com/repos/%s/contents', $base_path );

        if ( ! empty( $path ) ) {
            $url .= '/' . implode( '/', array_map( 'rawurlencode', explode( '/', $path ) ) );
        }

        if ( ! empty( $branch ) ) {
            $url = add_query_arg( 'ref', $branch, $url );
        }

        $response = wp_remote_get(
            $url,
            [
                'headers' => [
                    'Accept'     => 'application/vnd.github+json',
                    'User-Agent' => 'oportunidades-plugin',
                ],
                'timeout' => 20,
            ]
        );

        if ( is_wp_error( $response ) ) {
            throw new Exception( sprintf( __( 'Falha ao contactar o GitHub: %s', 'oportunidades' ), $response->get_error_message() ) );
        }

        $code = (int) wp_remote_retrieve_response_code( $response );
        if ( 200 !== $code ) {
            throw new Exception( __( 'Resposta inesperada do GitHub.', 'oportunidades' ) );
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( ! is_array( $body ) ) {
            throw new Exception( __( 'Conteúdo inesperado devolvido pelo GitHub.', 'oportunidades' ) );
        }

        // When a single file is requested the API returns an associative array instead of a list.
        if ( isset( $body['download_url'] ) ) {
            return $this->retrieve_json_payload( $body['download_url'] );
        }

        $files = array_filter(
            $body,
            static function ( $item ) {
                return isset( $item['type'], $item['name'] ) && 'file' === $item['type'] && preg_match( '/\.json$/', $item['name'] );
            }
        );

        if ( empty( $files ) ) {
            throw new Exception( __( 'Nenhum ficheiro JSON encontrado no repositório.', 'oportunidades' ) );
        }

        usort(
            $files,
            static function ( $a, $b ) {
                return strcmp( $b['name'], $a['name'] );
            }
        );

        $download_url = $files[0]['download_url'] ?? '';

        if ( empty( $download_url ) ) {
            throw new Exception( __( 'Não foi possível obter o URL de download do ficheiro no GitHub.', 'oportunidades' ) );
        }

        return $this->retrieve_json_payload( $download_url );
    }

    /**
     * Retrieve and decode a JSON file from a remote URL.
     *
     * @param string $url Remote JSON URL.
     *
     * @return array Parsed payload.
     *
     * @throws Exception When the JSON cannot be retrieved or decoded.
     */
    protected function retrieve_json_payload( $url ) {
        $response = wp_remote_get(
            $url,
            [
                'headers' => [
                    'Accept'     => 'application/json',
                    'User-Agent' => 'oportunidades-plugin',
                ],
                'timeout' => 20,
            ]
        );

        if ( is_wp_error( $response ) ) {
            throw new Exception( sprintf( __( 'Falha ao descarregar o dataset: %s', 'oportunidades' ), $response->get_error_message() ) );
        }

        $code = (int) wp_remote_retrieve_response_code( $response );
        if ( 200 !== $code ) {
            throw new Exception( __( 'Falha ao descarregar o ficheiro JSON do GitHub.', 'oportunidades' ) );
        }

        $payload = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( empty( $payload ) || ! is_array( $payload ) ) {
            throw new Exception( __( 'Conteúdo JSON inválido devolvido pelo GitHub.', 'oportunidades' ) );
        }

        return $payload;
    }
}
