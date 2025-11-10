<?php
/**
 * Plugin Name:       Oportunidades
 * Plugin URI:        https://example.com/oportunidades
 * Description:       Recebe dados de oportunidades de um pipeline externo, armazena-os na base de dados WordPress e disponibiliza ferramentas de consulta e notificação.
 * Version:           1.0.0
 * Author:            Equipa Oportunidades
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       oportunidades
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
exit;
}

define( 'OPORTUNIDADES_VERSION', '1.0.0' );
define( 'OPORTUNIDADES_PLUGIN_FILE', __FILE__ );
define( 'OPORTUNIDADES_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'OPORTUNIDADES_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once OPORTUNIDADES_PLUGIN_DIR . 'includes/class-oportunidades-plugin.php';

function oportunidades() {
return \Oportunidades\Plugin::instance();
}

oportunidades();
