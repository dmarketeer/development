<?php
namespace Oportunidades;

use Oportunidades\Includes\Admin;
use Oportunidades\Includes\Cron_Manager;
use Oportunidades\Includes\Database;
use Oportunidades\Includes\Emailer;
use Oportunidades\Includes\Frontend;
use Oportunidades\Includes\Github_Fetcher;
use Oportunidades\Includes\Importer;
use Oportunidades\Includes\Rest_API;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once __DIR__ . '/trait-singleton.php';
require_once __DIR__ . '/class-database.php';
require_once __DIR__ . '/class-importer.php';
require_once __DIR__ . '/class-rest-api.php';
require_once __DIR__ . '/class-admin.php';
require_once __DIR__ . '/class-frontend.php';
require_once __DIR__ . '/class-emailer.php';
require_once __DIR__ . '/class-github-fetcher.php';
require_once __DIR__ . '/class-cron-manager.php';

class Plugin {
    use Trait_Singleton;

    /**
     * Database layer instance.
     *
     * @var Database
     */
    public $database;

    /**
     * Import service instance.
     *
     * @var Importer
     */
    public $importer;

    /**
     * GitHub fetcher instance.
     *
     * @var Github_Fetcher
     */
    protected $github_fetcher;

    /**
     * Plugin bootstrap.
     */
    protected function __construct() {
        $this->define_constants();
        $this->database       = new Database();
        $this->importer       = new Importer( $this->database );
        $this->github_fetcher = new Github_Fetcher();

        register_activation_hook( OPORTUNIDADES_PLUGIN_FILE, [ $this, 'activate' ] );
        register_deactivation_hook( OPORTUNIDADES_PLUGIN_FILE, [ $this, 'deactivate' ] );
        register_uninstall_hook( OPORTUNIDADES_PLUGIN_FILE, [ __CLASS__, 'uninstall' ] );

        add_action( 'plugins_loaded', [ $this, 'load_textdomain' ] );
        add_action( 'init', [ $this, 'init_components' ] );
    }

    /**
     * Define constants used throughout the plugin.
     */
    protected function define_constants() {
        if ( ! defined( 'OPORTUNIDADES_TABLE_NAME' ) ) {
            global $wpdb;
            define( 'OPORTUNIDADES_TABLE_NAME', $wpdb->prefix . 'oportunidades' );
        }

        if ( ! defined( 'OPORTUNIDADES_OPTION_NAME' ) ) {
            define( 'OPORTUNIDADES_OPTION_NAME', 'oportunidades_settings' );
        }
    }

    /**
     * Bootstrap components.
     */
    public function init_components() {
        new Rest_API( $this->importer );
        new Admin( $this->importer, $this->database, $this->github_fetcher );
        new Frontend( $this->database );
        new Emailer( $this->database );
        new Cron_Manager( $this->importer, $this->github_fetcher );
    }

    /**
     * Load plugin text domain.
     */
    public function load_textdomain() {
        load_plugin_textdomain( 'oportunidades', false, dirname( plugin_basename( OPORTUNIDADES_PLUGIN_FILE ) ) . '/languages' );
    }

    /**
     * Plugin activation hook.
     */
    public function activate() {
        $this->database->create_table();
        $this->importer->maybe_schedule_page_creation();
        Cron_Manager::schedule_events();
    }

    /**
     * Plugin deactivation hook.
     */
    public function deactivate() {
        Cron_Manager::clear_events();
    }

    /**
     * Plugin uninstall hook.
     */
    public static function uninstall() {
        Cron_Manager::clear_events();
    }
}
