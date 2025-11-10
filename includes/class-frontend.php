<?php
namespace Oportunidades\Includes;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Frontend {
    /**
     * Database layer.
     *
     * @var Database
     */
    protected $database;

    public function __construct( Database $database ) {
        $this->database = $database;

        add_shortcode( 'oportunidades', [ $this, 'render_shortcode' ] );
        add_action( 'init', [ $this, 'register_block' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );
        add_action( 'rest_api_init', [ $this, 'register_public_endpoint' ] );
    }

    /**
     * Register Gutenberg block.
     */
    public function register_block() {
        register_block_type(
            OPORTUNIDADES_PLUGIN_DIR . 'blocks/oportunidades-grid',
            [
                'render_callback' => [ $this, 'render_block' ],
            ]
        );
    }

    /**
     * Enqueue public assets.
     */
    public function enqueue_assets() {
        wp_enqueue_style( 'oportunidades-public', OPORTUNIDADES_PLUGIN_URL . 'public/css/public.css', [], OPORTUNIDADES_VERSION );
        wp_enqueue_script( 'oportunidades-public', OPORTUNIDADES_PLUGIN_URL . 'public/js/public.js', [ 'wp-element' ], OPORTUNIDADES_VERSION, true );
    }

    /**
     * Shortcode render.
     */
    public function render_shortcode( $atts = [] ) {
        $atts = shortcode_atts(
            [
                'categoria' => '',
                'distrito'  => '',
                'limite'    => 20,
            ],
            $atts,
            'oportunidades'
        );

        $records = $this->database->query_records(
            [
                'category' => $atts['categoria'],
                'district' => $atts['distrito'],
                'per_page' => (int) $atts['limite'],
            ]
        );

        ob_start();
        include OPORTUNIDADES_PLUGIN_DIR . 'templates/list.php';
        return ob_get_clean();
    }

    /**
     * Render block output.
     */
    public function render_block( $attributes, $content ) {
        return $this->render_shortcode(
            [
                'categoria' => $attributes['categoria'] ?? '',
                'distrito'  => $attributes['distrito'] ?? '',
                'limite'    => $attributes['limite'] ?? 20,
            ]
        );
    }

    /**
     * Register public endpoint that reads from DB.
     */
    public function register_public_endpoint() {
        $routes = [
            [ 'namespace' => 'oportunidades/v1', 'route' => '/list' ],
            [ 'namespace' => 'dmarketeer/v1', 'route' => '/opportunities' ],
        ];

        foreach ( $routes as $route ) {
            register_rest_route(
                $route['namespace'],
                $route['route'],
                [
                    'methods'             => 'GET',
                    'callback'            => [ $this, 'handle_list' ],
                    'permission_callback' => '__return_true',
                ]
            );
        }
    }

    /**
     * Handle list endpoint with caching.
     */
    public function handle_list( $request ) {
        $args = [
            'per_page'  => min( 100, absint( $request['per_page'] ?? 20 ) ),
            'paged'     => absint( $request['page'] ?? 1 ),
            'category'  => sanitize_text_field( $request['categoria'] ?? '' ),
            'district'  => sanitize_text_field( $request['distrito'] ?? '' ),
            'min_value' => $request['min'] ?? null,
            'max_value' => $request['max'] ?? null,
        ];

        $version   = get_option( 'oportunidades_cache_version', 1 );
        $cache_key = 'oportunidades_rest_cache_' . md5( wp_json_encode( $args ) . '|' . $version );
        $data      = get_transient( $cache_key );

        if ( false === $data ) {
            $data = [
                'records' => $this->database->query_records( $args ),
                'total'   => $this->database->count_records( $args ),
            ];

            set_transient( $cache_key, $data, MINUTE_IN_SECONDS * 10 );
        }

        return rest_ensure_response( $data );
    }
}
