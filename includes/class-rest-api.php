<?php
namespace Oportunidades\Includes;

use Exception;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Rest_API {
    /**
     * Importer service.
     *
     * @var Importer
     */
    protected $importer;

    public function __construct( Importer $importer ) {
        $this->importer = $importer;
        add_action( 'rest_api_init', [ $this, 'register_routes' ] );
    }

    /**
     * Register REST API routes.
     */
    public function register_routes() {
        register_rest_route(
            'oportunidades/v1',
            '/import',
            [
                'methods'             => 'POST',
                'callback'            => [ $this, 'handle_import' ],
                'permission_callback' => [ $this, 'check_permission' ],
                'args'                => [
                    'payload' => [
                        'required' => true,
                    ],
                ],
            ]
        );
    }

    /**
     * Validate shared token.
     *
     * @return bool|WP_Error
     */
    public function check_permission() {
        $settings = get_option( OPORTUNIDADES_OPTION_NAME, [] );
        $token    = $settings['api_token'] ?? '';

        if ( empty( $token ) ) {
            return new WP_Error( 'oportunidades_no_token', __( 'Token API não configurado.', 'oportunidades' ), [ 'status' => 403 ] );
        }

        $provided = $this->get_request_token();

        if ( hash_equals( $token, $provided ) ) {
            return true;
        }

        return new WP_Error( 'oportunidades_invalid_token', __( 'Token inválido.', 'oportunidades' ), [ 'status' => 403 ] );
    }

    /**
     * Extract token from request.
     *
     * @return string
     */
    protected function get_request_token() {
        $headers = function_exists( 'getallheaders' ) ? getallheaders() : [];

        $provided = '';

        if ( isset( $headers['Authorization'] ) && 0 === strpos( $headers['Authorization'], 'Bearer ' ) ) {
            $provided = substr( $headers['Authorization'], 7 );
        } elseif ( isset( $_SERVER['HTTP_X_OPORTUNIDADES_TOKEN'] ) ) {
            $provided = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_OPORTUNIDADES_TOKEN'] ) );
        } elseif ( isset( $_REQUEST['token'] ) ) {
            $provided = sanitize_text_field( wp_unslash( $_REQUEST['token'] ) );
        }

        return (string) $provided;
    }

    /**
     * Handle import request.
     *
     * @param WP_REST_Request $request Request instance.
     * @return WP_REST_Response|WP_Error
     */
    public function handle_import( WP_REST_Request $request ) {
        $payload = $request->get_json_params();

        if ( empty( $payload ) ) {
            $payload = json_decode( $request->get_body(), true );
        }

        if ( empty( $payload ) ) {
            return new WP_Error( 'oportunidades_empty_payload', __( 'Payload vazio.', 'oportunidades' ), [ 'status' => 400 ] );
        }

        try {
            $summary = $this->importer->import( $payload, 'rest' );

            return new WP_REST_Response( [
                'success' => true,
                'summary' => $summary,
            ] );
        } catch ( Exception $e ) {
            return new WP_Error( 'oportunidades_import_error', $e->getMessage(), [ 'status' => 400 ] );
        }
    }
}
