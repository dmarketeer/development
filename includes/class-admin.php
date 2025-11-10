<?php
namespace Oportunidades\Includes;

use Exception;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Admin {
    /**
     * Importer service.
     *
     * @var Importer
     */
    protected $importer;

    /**
     * Database layer.
     *
     * @var Database
     */
    protected $database;

    public function __construct( Importer $importer, Database $database ) {
        $this->importer = $importer;
        $this->database = $database;

        add_action( 'admin_menu', [ $this, 'register_menu' ] );
        add_action( 'admin_init', [ $this, 'register_settings' ] );
        add_action( 'admin_post_oportunidades_import', [ $this, 'handle_manual_import' ] );
        add_action( 'admin_post_oportunidades_reset', [ $this, 'handle_reset' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
    }

    /**
     * Register admin menu.
     */
    public function register_menu() {
        add_menu_page(
            __( 'Oportunidades', 'oportunidades' ),
            __( 'Oportunidades', 'oportunidades' ),
            'manage_options',
            'oportunidades',
            [ $this, 'render_settings_page' ],
            'dashicons-analytics',
            65
        );
    }

    /**
     * Register settings and fields.
     */
    public function register_settings() {
        register_setting( 'oportunidades_settings', OPORTUNIDADES_OPTION_NAME, [ $this, 'sanitize_settings' ] );

        add_settings_section( 'oportunidades_general', __( 'Integração', 'oportunidades' ), '__return_false', 'oportunidades' );

        add_settings_field(
            'api_token',
            __( 'Token da API', 'oportunidades' ),
            [ $this, 'render_token_field' ],
            'oportunidades',
            'oportunidades_general'
        );

        add_settings_field(
            'sync_interval',
            __( 'Intervalo de sincronização (minutos)', 'oportunidades' ),
            [ $this, 'render_sync_field' ],
            'oportunidades',
            'oportunidades_general'
        );

        add_settings_field(
            'local_file',
            __( 'Ficheiro local para reprocessamento', 'oportunidades' ),
            [ $this, 'render_local_file_field' ],
            'oportunidades',
            'oportunidades_general'
        );

        add_settings_field(
            'notification_emails',
            __( 'Emails para notificações', 'oportunidades' ),
            [ $this, 'render_emails_field' ],
            'oportunidades',
            'oportunidades_general'
        );

        add_settings_field(
            'default_filters',
            __( 'Filtros predefinidos', 'oportunidades' ),
            [ $this, 'render_filters_field' ],
            'oportunidades',
            'oportunidades_general'
        );

        add_settings_field(
            'custom_field_map',
            __( 'Mapeamento de campos adicionais', 'oportunidades' ),
            [ $this, 'render_custom_field_map' ],
            'oportunidades',
            'oportunidades_general'
        );
    }

    /**
     * Sanitize settings input.
     */
    public function sanitize_settings( $settings ) {
        $settings['api_token']           = sanitize_text_field( $settings['api_token'] ?? '' );
        $settings['sync_interval']       = isset( $settings['sync_interval'] ) ? absint( $settings['sync_interval'] ) : 1440;
        $settings['local_file']          = sanitize_text_field( $settings['local_file'] ?? '' );
        $settings['notification_emails'] = implode( ',', array_filter( array_map( 'sanitize_email', explode( ',', $settings['notification_emails'] ?? '' ) ) ) );
        $settings['default_filters']     = array_filter( array_map( 'sanitize_text_field', explode( ',', $settings['default_filters'] ?? '' ) ) );
        $settings['custom_field_map']    = sanitize_textarea_field( $settings['custom_field_map'] ?? '' );

        return $settings;
    }

    /**
     * Render token field.
     */
    public function render_token_field() {
        $settings = get_option( OPORTUNIDADES_OPTION_NAME, [] );
        $token    = $settings['api_token'] ?? wp_generate_password( 32, false );
        ?>
        <input type="text" name="<?php echo esc_attr( OPORTUNIDADES_OPTION_NAME . '[api_token]' ); ?>" value="<?php echo esc_attr( $token ); ?>" class="regular-text" />
        <p class="description"><?php esc_html_e( 'Token partilhado com o pipeline externo.', 'oportunidades' ); ?></p>
        <?php
    }

    /**
     * Render sync interval field.
     */
    public function render_sync_field() {
        $settings = get_option( OPORTUNIDADES_OPTION_NAME, [] );
        $value    = $settings['sync_interval'] ?? 1440;
        ?>
        <input type="number" min="15" step="15" name="<?php echo esc_attr( OPORTUNIDADES_OPTION_NAME . '[sync_interval]' ); ?>" value="<?php echo esc_attr( $value ); ?>" />
        <p class="description"><?php esc_html_e( 'Utilizado para reprocessar ficheiros locais via WP-Cron.', 'oportunidades' ); ?></p>
        <?php
    }

    /**
     * Render local file field.
     */
    public function render_local_file_field() {
        $settings = get_option( OPORTUNIDADES_OPTION_NAME, [] );
        $value    = $settings['local_file'] ?? '';
        ?>
        <input type="text" name="<?php echo esc_attr( OPORTUNIDADES_OPTION_NAME . '[local_file]' ); ?>" value="<?php echo esc_attr( $value ); ?>" class="regular-text" />
        <p class="description"><?php esc_html_e( 'Caminho absoluto para ficheiro JSON/CSV a reprocessar automaticamente.', 'oportunidades' ); ?></p>
        <?php
    }

    /**
     * Render notification emails field.
     */
    public function render_emails_field() {
        $settings = get_option( OPORTUNIDADES_OPTION_NAME, [] );
        $value    = $settings['notification_emails'] ?? '';
        ?>
        <input type="text" name="<?php echo esc_attr( OPORTUNIDADES_OPTION_NAME . '[notification_emails]' ); ?>" value="<?php echo esc_attr( $value ); ?>" class="regular-text" />
        <p class="description"><?php esc_html_e( 'Separar múltiplos emails por vírgulas.', 'oportunidades' ); ?></p>
        <?php
    }

    /**
     * Render default filters field.
     */
    public function render_filters_field() {
        $settings = get_option( OPORTUNIDADES_OPTION_NAME, [] );
        $value    = implode( ',', $settings['default_filters'] ?? [] );
        ?>
        <input type="text" name="<?php echo esc_attr( OPORTUNIDADES_OPTION_NAME . '[default_filters]' ); ?>" value="<?php echo esc_attr( $value ); ?>" class="regular-text" />
        <p class="description"><?php esc_html_e( 'Lista de filtros separados por vírgulas (ex.: LSF,Fachadas,Reabilitação).', 'oportunidades' ); ?></p>
        <?php
    }

    /**
     * Render custom field mapping textarea.
     */
    public function render_custom_field_map() {
        $settings = get_option( OPORTUNIDADES_OPTION_NAME, [] );
        $value    = $settings['custom_field_map'] ?? '';
        ?>
        <textarea name="<?php echo esc_attr( OPORTUNIDADES_OPTION_NAME . '[custom_field_map]' ); ?>" rows="5" class="large-text"><?php echo esc_textarea( $value ); ?></textarea>
        <p class="description"><?php esc_html_e( 'Opcional: JSON com mapeamento de colunas personalizadas enviadas pelo pipeline.', 'oportunidades' ); ?></p>
        <?php
    }

    /**
     * Render settings page.
     */
    public function render_settings_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $last_ingestion = get_option( 'oportunidades_last_ingestion' );
        $errors         = get_transient( 'oportunidades_last_errors' );
        $summary        = get_transient( 'oportunidades_last_summary' );
        $reset_notice   = get_transient( 'oportunidades_reset_notice' );

        delete_transient( 'oportunidades_last_errors' );
        delete_transient( 'oportunidades_last_summary' );
        delete_transient( 'oportunidades_reset_notice' );

        ?>
        <div class="wrap oportunidades-wrap">
            <h1><?php esc_html_e( 'Oportunidades', 'oportunidades' ); ?></h1>
            <form method="post" action="options.php" class="oportunidades-settings-form">
                <?php
                settings_fields( 'oportunidades_settings' );
                do_settings_sections( 'oportunidades' );
                submit_button();
                ?>
            </form>

            <hr />
            <h2><?php esc_html_e( 'Importação Manual', 'oportunidades' ); ?></h2>
            <?php if ( ! empty( $reset_notice ) ) : ?>
                <div class="notice notice-<?php echo esc_attr( $reset_notice['type'] ); ?>"><p><?php echo esc_html( $reset_notice['message'] ); ?></p></div>
            <?php endif; ?>
            <?php if ( ! empty( $errors ) ) : ?>
                <div class="notice notice-error"><p><?php echo esc_html( implode( ' ', (array) $errors ) ); ?></p></div>
            <?php endif; ?>
            <?php if ( ! empty( $summary ) ) : ?>
                <div class="notice notice-success"><p><?php echo esc_html( sprintf( __( 'Processados %1$d registos. Inseridos: %2$d. Actualizados: %3$d.', 'oportunidades' ), $summary['processed'], $summary['inserted'], $summary['updated'] ) ); ?></p></div>
            <?php endif; ?>
            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
                <?php wp_nonce_field( 'oportunidades_import' ); ?>
                <input type="hidden" name="action" value="oportunidades_import" />
                <p>
                    <label for="oportunidades_file"><?php esc_html_e( 'Ficheiro JSON ou CSV', 'oportunidades' ); ?></label>
                    <input type="file" id="oportunidades_file" name="oportunidades_file" accept=".json,.csv" required />
                </p>
                <?php submit_button( __( 'Importar agora', 'oportunidades' ) ); ?>
            </form>

            <hr />
            <h2><?php esc_html_e( 'Reposição de dados', 'oportunidades' ); ?></h2>
            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <?php wp_nonce_field( 'oportunidades_reset' ); ?>
                <input type="hidden" name="action" value="oportunidades_reset" />
                <?php
                submit_button(
                    __( 'Repor registos', 'oportunidades' ),
                    'delete',
                    'submit',
                    false,
                    [
                        'onclick' => "return confirm('" . esc_js( __( 'Esta acção remove todos os registos armazenados. Deseja continuar?', 'oportunidades' ) ) . "');",
                    ]
                );
                ?>
                <p class="description"><?php esc_html_e( 'Remove todos os registos armazenados na base de dados do plugin.', 'oportunidades' ); ?></p>
            </form>

            <hr />
            <h2><?php esc_html_e( 'Resumo', 'oportunidades' ); ?></h2>
            <p><?php esc_html_e( 'Última ingestão:', 'oportunidades' ); ?> <?php echo esc_html( $last_ingestion ? get_date_from_gmt( $last_ingestion, 'd/m/Y H:i' ) : __( 'Nunca', 'oportunidades' ) ); ?></p>

            <div id="oportunidades-admin-table"></div>
        </div>
        <?php
    }

    /**
     * Handle manual uploads.
     */
    public function handle_manual_import() {
        if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'oportunidades_import' ) ) {
            wp_die( esc_html__( 'Sem permissões.', 'oportunidades' ) );
        }

        if ( empty( $_FILES['oportunidades_file']['tmp_name'] ) ) {
            wp_safe_redirect( add_query_arg( 'oportunidades_error', 'missing_file', wp_get_referer() ) );
            exit;
        }

        $file    = $_FILES['oportunidades_file'];
        $content = file_get_contents( $file['tmp_name'] );
        $extension = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );

        try {
            if ( 'json' === $extension ) {
                $payload = json_decode( $content, true );
            } else {
                $payload = $this->parse_csv( $content );
            }

            $summary = $this->importer->import( $payload, 'upload' );
            set_transient( 'oportunidades_last_summary', $summary, MINUTE_IN_SECONDS * 30 );
        } catch ( Exception $e ) {
            set_transient( 'oportunidades_last_errors', [ $e->getMessage() ], MINUTE_IN_SECONDS * 30 );
        }

        wp_safe_redirect( wp_get_referer() );
        exit;
    }

    /**
     * Handle reset requests.
     */
    public function handle_reset() {
        if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'oportunidades_reset' ) ) {
            wp_die( esc_html__( 'Sem permissões.', 'oportunidades' ) );
        }

        $redirect = wp_get_referer() ? wp_get_referer() : admin_url( 'admin.php?page=oportunidades' );

        try {
            $this->database->reset();
            delete_option( 'oportunidades_last_ingestion' );
            delete_option( 'oportunidades_cache_version' );
            delete_transient( 'oportunidades_last_summary' );
            delete_transient( 'oportunidades_last_errors' );

            set_transient(
                'oportunidades_reset_notice',
                [
                    'type'    => 'success',
                    'message' => __( 'Todos os registos foram removidos.', 'oportunidades' ),
                ],
                MINUTE_IN_SECONDS * 5
            );
        } catch ( Exception $e ) {
            set_transient(
                'oportunidades_reset_notice',
                [
                    'type'    => 'error',
                    'message' => $e->getMessage(),
                ],
                MINUTE_IN_SECONDS * 5
            );
        }

        wp_safe_redirect( $redirect );
        exit;
    }

    /**
     * Parse CSV to dataset structure.
     */
    protected function parse_csv( $content ) {
        $rows = array_map( 'str_getcsv', preg_split( '/\r\n|\n|\r/', trim( $content ) ) );
        $header = array_shift( $rows );

        $items = [];
        foreach ( $rows as $row ) {
            if ( count( $row ) !== count( $header ) ) {
                continue;
            }
            $items[] = array_combine( $header, $row );
        }

        return [
            'schema_version' => '1.0',
            'oportunidades'  => $items,
        ];
    }

    /**
     * Enqueue admin scripts.
     */
    public function enqueue_assets( $hook ) {
        if ( 'toplevel_page_oportunidades' !== $hook ) {
            return;
        }

        wp_enqueue_style( 'oportunidades-admin', OPORTUNIDADES_PLUGIN_URL . 'admin/css/admin.css', [], OPORTUNIDADES_VERSION );
        wp_enqueue_script( 'oportunidades-admin', OPORTUNIDADES_PLUGIN_URL . 'admin/js/admin.js', [ 'wp-element' ], OPORTUNIDADES_VERSION, true );

        wp_localize_script(
            'oportunidades-admin',
            'OportunidadesAdmin',
            [
                'restUrl'   => rest_url( 'oportunidades/v1/import' ),
                'nonce'     => wp_create_nonce( 'wp_rest' ),
                'token'     => get_option( OPORTUNIDADES_OPTION_NAME, [] )['api_token'] ?? '',
                'summary'   => get_transient( 'oportunidades_last_summary' ),
                'tableData' => $this->database->query_records( [ 'per_page' => 50 ] ),
            ]
        );
    }
}
