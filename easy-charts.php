<?php
/** Plugin headers
 *
 * @link              https://kiranpotphode.com/
 * @since             1.0.0
 * @package           Easy_Charts
 *
 * @wordpress-plugin
 * Plugin Name:       Easy Charts
 * Plugin URI:        http://kiranpotphode.github.io/easy-charts/
 * Description:       Build simple, reusable, customisable charts on any page or post with ease.
 * Version:           1.2.5
 * Author:            Kiran Potphode
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       easy-charts
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'EASY_CHARTS_VERSION', '1.2.5' );
define( 'EASY_CHARTS_FILE', __FILE__ );
define( 'EASY_CHARTS_DIR', plugin_dir_path( __FILE__ ) );

/*
 * 2.0 core (namespaced classes in inc/, loaded by Composer). It runs alongside
 * the 1.x classes below until each 1.x piece is replaced.
 */
if ( is_readable( EASY_CHARTS_DIR . 'vendor/autoload.php' ) ) {
	require_once EASY_CHARTS_DIR . 'vendor/autoload.php';
	EasyCharts\Plugin::instance()->boot();
} else {
	add_action(
		'admin_notices',
		static function () {
			if ( ! current_user_can( 'activate_plugins' ) ) {
				return;
			}
			printf(
				'<div class="notice notice-error"><p>%s</p></div>',
				esc_html__( 'Easy Charts: the vendor/autoload.php file is missing. Run "composer install" in the plugin folder.', 'easy-charts' )
			);
		}
	);
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-easy-charts-activator.php
 */
function easy_charts_activate() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-easy-charts-activator.php';
	Easy_Charts_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-easy-charts-deactivator.php
 */
function easy_charts_deactivate() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-easy-charts-deactivator.php';
	Easy_Charts_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'easy_charts_activate' );
register_deactivation_hook( __FILE__, 'easy_charts_deactivate' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-easy-charts.php';

/**
 * Begins execution of the plugin.
 *
 * @since    1.0.0
 */
function easy_charts_run() {

	$plugin = new Easy_Charts();
	$plugin->run();
}
easy_charts_run();
