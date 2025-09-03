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

define( 'EASY_CHARTS_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) );
define( 'EASY_CHARTS_URL', untrailingslashit( plugin_dir_url( __FILE__ ) ) );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require EASY_CHARTS_PATH . '/includes/class-easy-charts.php';

/**
 * Begins execution of the plugin.
 *
 * @since    1.0.0
 */
function run_easy_charts() {

	$easy_charts_plugin = new Easy_Charts();
	$easy_charts_plugin->run();
}
run_easy_charts();
