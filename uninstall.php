<?php
/**
 * Runs when the plugin is deleted.
 *
 * Deliberately deletes nothing: charts (posts of the easy_charts type, their 2.0 and 1.x post meta)
 * and the easy_charts_settings option are kept, so reinstalling, or going back to 1.x, brings every
 * chart back. Charts can be deleted under Easy Charts like any other post.
 *
 * @link    https://kiranpotphode.com/
 * @package EasyCharts
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}
