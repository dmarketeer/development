<?php
namespace Oportunidades\Includes;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Cron_Manager {
    /**
     * Importer service.
     *
     * @var Importer
     */
    protected $importer;

    const CRON_HOOK = 'oportunidades_process_local_file';

    public function __construct( Importer $importer ) {
        $this->importer = $importer;

        add_action( self::CRON_HOOK, [ $this, 'process_local_file' ] );
    }

    /**
     * Schedule cron events.
     */
    public static function schedule_events() {
        if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
            wp_schedule_event( time(), 'hourly', self::CRON_HOOK );
        }

        if ( ! wp_next_scheduled( 'oportunidades_send_digest' ) ) {
            $now      = current_time( 'timestamp', true );
            $next_run = strtotime( 'today 09:00 UTC' );
            if ( $next_run <= $now ) {
                $next_run = strtotime( 'tomorrow 09:00 UTC' );
            }
            wp_schedule_event( $next_run, 'daily', 'oportunidades_send_digest' );
        }
    }

    /**
     * Clear cron events.
     */
    public static function clear_events() {
        wp_clear_scheduled_hook( self::CRON_HOOK );
        wp_clear_scheduled_hook( 'oportunidades_send_digest' );
    }

    /**
     * Process local file import respecting sync interval.
     */
    public function process_local_file() {
        $settings = get_option( OPORTUNIDADES_OPTION_NAME, [] );
        $path     = $settings['local_file'] ?? '';

        if ( empty( $path ) || ! file_exists( $path ) ) {
            return;
        }

        $interval = max( 15, (int) ( $settings['sync_interval'] ?? 1440 ) );
        $last_run = (int) get_option( 'oportunidades_last_local_sync', 0 );

        if ( $last_run && ( time() - $last_run ) < ( $interval * MINUTE_IN_SECONDS ) ) {
            return;
        }

        $content = file_get_contents( $path );
        $payload = json_decode( $content, true );

        if ( empty( $payload ) ) {
            return;
        }

        $this->importer->import( $payload, 'cron' );
        update_option( 'oportunidades_last_local_sync', time() );
    }
}
