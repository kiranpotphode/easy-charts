<?php
/**
 * PHPUnit bootstrap: loads the WordPress test library and this plugin.
 *
 * Run inside the wp-env test site: npm run test:php
 *
 * @package EasyCharts
 */

$easy_charts_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $easy_charts_tests_dir ) {
	$easy_charts_tests_dir = '/wordpress-phpunit';
}

if ( ! file_exists( $easy_charts_tests_dir . '/includes/functions.php' ) ) {
	fwrite( STDERR, "WordPress test library not found in {$easy_charts_tests_dir}. Run the tests with: npm run test:php\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite
	exit( 1 );
}

require_once dirname( __DIR__ ) . '/vendor/yoast/phpunit-polyfills/phpunitpolyfills-autoload.php';
require_once $easy_charts_tests_dir . '/includes/functions.php';

tests_add_filter(
	'muplugins_loaded',
	static function () {
		require dirname( __DIR__ ) . '/easy-charts.php';
	}
);

require $easy_charts_tests_dir . '/includes/bootstrap.php';
