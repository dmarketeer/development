<?php
namespace Oportunidades\Includes;

use Exception;

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

    /**
     * GitHub fetcher helper.
     *
     * @var Github_Fetcher
     */
    protected $github_fetcher;

    const CRON_HOOK = 'oportunidades_process_local_file';

    public function __construct( Importer $importer, Github_Fetcher $github_fetcher ) {
        $this->importer       = $importer;
        $this->github_fetcher = $github_fetcher;

        add_action( self::CRON_HOOK, [ $this, 'run_scheduled_tasks' ] );
    }

    /**
     * Execute scheduled tasks: local file reprocess and GitHub sync.
     */
    public function run_scheduled_tasks() {
        $this->process_local_file();
        $this->process_github_repository();
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

    /**
     * Fetch the latest dataset from GitHub when configured.
     *
     * @param bool $force Whether to force the download bypassing interval checks.
     */
    public function process_github_repository( $force = false ) {
        $settings = get_option( OPORTUNIDADES_OPTION_NAME, [] );
        $repo     = $settings['github_repo'] ?? '';

        if ( empty( $repo ) ) {
            return;
        }

        $interval = max( 15, (int) ( $settings['sync_interval'] ?? 1440 ) );
        $last_run = (int) get_option( 'oportunidades_last_github_sync', 0 );

        if ( ! $force && $last_run && ( time() - $last_run ) < ( $interval * MINUTE_IN_SECONDS ) ) {
            return;
        }

        try {
            $payload = $this->github_fetcher->fetch_latest_dataset(
                $repo,
                $settings['github_branch'] ?? 'main',
                $settings['github_path'] ?? ''
            );

            $summary = $this->importer->import( $payload, 'github' );

            update_option( 'oportunidades_last_github_sync', time() );
            set_transient( 'oportunidades_last_summary', $summary, MINUTE_IN_SECONDS * 30 );
        } catch ( Exception $e ) {
            set_transient( 'oportunidades_last_errors', [ $e->getMessage() ], MINUTE_IN_SECONDS * 30 );
        }
    }
}
