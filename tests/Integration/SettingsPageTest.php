<?php
/**
 * Tests for the global chart settings page.
 *
 * @package EasyCharts
 */

namespace EasyCharts\Tests\Integration;

use EasyCharts\Admin\SettingsPage;
use EasyCharts\Plugin;
use WP_REST_Request;
use WP_UnitTestCase;

/**
 * SettingsPage menu, assets and saving through the core settings route.
 */
class SettingsPageTest extends WP_UnitTestCase {

	public function set_up() {
		parent::set_up();
		// The test library unregisters settings between tests; register ours again.
		Plugin::instance()->get( \EasyCharts\Settings\GlobalSettings::class )->register_setting();
	}

	public function tear_down() {
		$GLOBALS['submenu'] = array(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Reset the admin menu.
		parent::tear_down();
	}

	/**
	 * Log in as a new user with a role.
	 *
	 * @param string $role Role.
	 * @return void
	 */
	private function login( $role ) {
		wp_set_current_user( self::factory()->user->create( array( 'role' => $role ) ) );
	}

	public function test_page_replaces_the_1x_page_for_admins_only() {
		$page = Plugin::instance()->get( SettingsPage::class );

		$this->login( 'administrator' );
		$page->add_menu();
		$items = wp_list_pluck( $GLOBALS['submenu']['edit.php?post_type=easy_charts'], 2 );
		$this->assertSame( array( SettingsPage::SLUG ), $items );

		$GLOBALS['submenu'] = array(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Reset the admin menu.
		$this->login( 'editor' );
		$page->add_menu();
		$this->assertArrayNotHasKey( 'edit.php?post_type=easy_charts', $GLOBALS['submenu'] );
	}

	public function test_app_loads_on_its_page_only_with_defaults_and_saved_settings() {
		$page = Plugin::instance()->get( SettingsPage::class );
		$this->login( 'administrator' );
		update_option( 'easy_charts_settings', array( 'legend' => array( 'position' => 'left' ) ) );
		$page->add_menu();
		$hook = get_plugin_page_hookname( SettingsPage::SLUG, 'edit.php?post_type=easy_charts' );

		$page->enqueue( 'index.php' );
		$this->assertFalse( wp_script_is( SettingsPage::SCRIPT, 'enqueued' ) );

		$page->enqueue( $hook );
		$this->assertTrue( wp_script_is( SettingsPage::SCRIPT, 'enqueued' ) );
		$inline = implode( '', wp_scripts()->get_data( SettingsPage::SCRIPT, 'before' ) );
		$data   = json_decode( substr( $inline, strpos( $inline, '{' ), -1 ), true );
		$this->assertSame( 'bottom', $data['baseSettings']['legend']['position'], 'built-in defaults' );
		$this->assertSame( 'left', $data['settings']['legend']['position'], 'saved global settings' );
	}

	public function test_admins_save_through_the_settings_route() {
		$this->login( 'administrator' );
		$request = new WP_REST_Request( 'POST', '/wp/v2/settings' );
		$request->set_body_params(
			array(
				'easy_charts_settings' => array(
					'palette' => array( 'colors' => array( '#112233', '#445566' ) ),
					'labels'  => array( 'suffix' => ' kg' ),
				),
			)
		);

		$response = rest_do_request( $request );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( array( '#112233', '#445566' ), get_option( 'easy_charts_settings' )['palette']['colors'] );
		$this->assertSame( ' kg', $response->get_data()['easy_charts_settings']['labels']['suffix'] );
	}

	public function test_editors_cannot_change_global_settings() {
		$this->login( 'editor' );
		$request = new WP_REST_Request( 'POST', '/wp/v2/settings' );
		$request->set_body_params( array( 'easy_charts_settings' => array( 'labels' => array( 'suffix' => 'x' ) ) ) );

		$this->assertSame( 403, rest_do_request( $request )->get_status() );
		$this->assertSame( array(), get_option( 'easy_charts_settings', array() ) );
	}
}
