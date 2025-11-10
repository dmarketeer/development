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
    const GITHUB_SYNC_HOOK = 'oportunidades_github_sync';

    public function __construct( Importer $importer ) {
        $this->importer = $importer;

        add_action( self::CRON_HOOK, [ $this, 'process_local_file' ] );
        add_action( self::GITHUB_SYNC_HOOK, [ $this, 'process_github_sync' ] );
    }

    /**
     * Schedule cron events.
     */
    public static function schedule_events() {
        if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
            wp_schedule_event( time(), 'hourly', self::CRON_HOOK );
        }

        if ( ! wp_next_scheduled( self::GITHUB_SYNC_HOOK ) ) {
            wp_schedule_event( time(), 'hourly', self::GITHUB_SYNC_HOOK );
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
        wp_clear_scheduled_hook( self::GITHUB_SYNC_HOOK );
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

    /**
     * Process GitHub sync respecting sync interval.
     */
    public function process_github_sync() {
        $settings = get_option( OPORTUNIDADES_OPTION_NAME, [] );
        $repo_url = $settings['github_repo_url'] ?? '';

        if ( empty( $repo_url ) ) {
            return;
        }

        $interval = max( 15, (int) ( $settings['sync_interval'] ?? 1440 ) );
        $last_run = (int) get_option( 'oportunidades_last_github_sync', 0 );

        if ( $last_run && ( time() - $last_run ) < ( $interval * MINUTE_IN_SECONDS ) ) {
            return;
        }

        try {
            $github_fetcher = new GitHub_Fetcher();
            $github_fetcher->fetch_and_import( $this->importer, $settings );
            update_option( 'oportunidades_last_github_sync', time() );
        } catch ( \Exception $e ) {
            error_log( 'Oportunidades GitHub Sync Error: ' . $e->getMessage() );
        }
    }
}
