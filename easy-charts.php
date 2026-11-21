<?php
/**
 * Plugin headers
 *
 * @link              https://kiranpotphode.com/
 * @package           EasyCharts
 *
 * @wordpress-plugin
 * Plugin Name:       Easy Charts
 * Plugin URI:        http://kiranpotphode.github.io/easy-charts/
 * Description:       Build simple, reusable, customisable charts on any page or post with ease.
 * Version:           2.0.0
 * Requires at least: 6.6
 * Requires PHP:      7.4
 * Author:            Kiran Potphode
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       easy-charts
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'EASY_CHARTS_VERSION', '2.0.0' );
define( 'EASY_CHARTS_FILE', __FILE__ );
define( 'EASY_CHARTS_DIR', plugin_dir_path( __FILE__ ) );

// Namespaced classes in inc/, loaded by Composer's autoloader (shipped in the release zip).
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
