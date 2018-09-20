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
 * Version:           1.2.1
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

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-easy-charts-activator.php
 */
function activate_easy_charts() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-easy-charts-activator.php';
	Easy_Charts_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-easy-charts-deactivator.php
 */
function deactivate_easy_charts() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-easy-charts-deactivator.php';
	Easy_Charts_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_easy_charts' );
register_deactivation_hook( __FILE__, 'deactivate_easy_charts' );

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
function run_easy_charts() {

	$plugin = new Easy_Charts();
	$plugin->run();

}
run_easy_charts();
