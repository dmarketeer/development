<?php
namespace Oportunidades\Includes;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Emailer {
    /**
     * Database layer.
     *
     * @var Database
     */
    protected $database;

    public function __construct( Database $database ) {
        $this->database = $database;

        add_action( 'oportunidades_send_digest', [ $this, 'send_digest' ] );
    }

    /**
     * Send daily digest via wp_mail.
     */
    public function send_digest() {
        $settings = get_option( OPORTUNIDADES_OPTION_NAME, [] );
        $recipients = array_filter( array_map( 'sanitize_email', explode( ',', $settings['notification_emails'] ?? '' ) ) );

        if ( empty( $recipients ) ) {
            return;
        }

        $records = $this->database->query_records( [ 'per_page' => 20 ] );

        ob_start();
        include OPORTUNIDADES_PLUGIN_DIR . 'templates/email.php';
        $message = ob_get_clean();

        $sent = wp_mail( $recipients, __( 'Oportunidades diárias', 'oportunidades' ), $message, [ 'Content-Type: text/html; charset=UTF-8' ] );

        $log = get_option( 'oportunidades_email_log', [] );
        $log[] = [
            'timestamp' => current_time( 'mysql', true ),
            'recipients' => $recipients,
            'sent' => (bool) $sent,
        ];
        update_option( 'oportunidades_email_log', array_slice( $log, -50 ) );
        update_option( 'oportunidades_last_email', current_time( 'mysql', true ) );
    }
}
