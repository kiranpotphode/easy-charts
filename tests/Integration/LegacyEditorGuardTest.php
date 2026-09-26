<?php
/**
 * Tests for keeping the 1.x editor away from v2 charts.
 *
 * @package EasyCharts
 */

namespace EasyCharts\Tests\Integration;

use EasyCharts\Admin\LegacyEditorGuard;
use EasyCharts\Chart\ChartRepository;
use EasyCharts\Plugin;
use WP_Ajax_UnitTestCase;
use WPAjaxDieContinueException;
use WPAjaxDieStopException;

/**
 * LegacyEditorGuard: v2 charts get a notice and v2 preview; 1.x charts keep the 1.x editor.
 */
class LegacyEditorGuardTest extends WP_Ajax_UnitTestCase {

	public static function set_up_before_class() {
		parent::set_up_before_class();
		require_once ABSPATH . 'wp-admin/includes/template.php';
	}

	public function set_up() {
		parent::set_up();
		$GLOBALS['wp_meta_boxes'] = array(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Reset between tests.
	}

	/**
	 * Create a chart, optionally with a v2 document.
	 *
	 * @param bool $with_v2 Whether to store a v2 document.
	 * @return int
	 */
	private function chart( $with_v2 ) {
		$id = self::factory()->post->create( array( 'post_type' => 'easy_charts' ) );
		if ( $with_v2 ) {
			( new ChartRepository() )->save(
				$id,
				array(
					'schema' => 2,
					'type'   => 'pie',
					'data'   => array(
						'columns' => array( '', 'Share' ),
						'rows'    => array( array( 'A', 1 ) ),
					),
				)
			);
		}

		return $id;
	}

	/**
	 * Run the metabox actions for a chart, as the edit screen does.
	 *
	 * @param int $id Chart ID.
	 * @return array Registered metaboxes for easy_charts, by context.
	 */
	private function metaboxes( $id ) {
		$post = get_post( $id );
		do_action( 'add_meta_boxes', 'easy_charts', $post );
		do_action( 'add_meta_boxes_easy_charts', $post ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Core's add_meta_boxes_{post_type} hook.

		return $GLOBALS['wp_meta_boxes']['easy_charts'];
	}

	public function test_v2_chart_gets_notice_and_preview_instead_of_1x_editor() {
		$boxes = $this->metaboxes( $this->chart( true ) );

		foreach ( LegacyEditorGuard::LEGACY_METABOXES as $legacy ) {
			$this->assertFalse( $boxes['advanced']['default'][ $legacy ], $legacy . ' removed' );
		}
		$this->assertArrayHasKey( LegacyEditorGuard::PREVIEW_METABOX, $boxes['normal']['high'] );
		$this->assertArrayHasKey( 'easy_charts_shortcode_metabox', $boxes['side']['default'], 'shortcode box stays' );
	}

	public function test_1x_chart_keeps_the_1x_editor() {
		$boxes = $this->metaboxes( $this->chart( false ) );

		foreach ( LegacyEditorGuard::LEGACY_METABOXES as $legacy ) {
			$this->assertIsArray( $boxes['advanced']['default'][ $legacy ], $legacy . ' kept' );
		}
		$this->assertArrayNotHasKey( 'normal', $boxes );
	}

	public function test_preview_shows_notice_and_v2_chart() {
		$guard = Plugin::instance()->get( LegacyEditorGuard::class );
		$id    = $this->chart( true );

		ob_start();
		$guard->render_preview( get_post( $id ) );
		$html = ob_get_clean();

		$this->assertStringContainsString( 'notice-info', $html );
		$this->assertStringContainsString( 'easy-charts-chart--pie', $html );
	}

	public function test_1x_editor_scripts_are_dequeued_for_v2_charts_only() {
		$guard = Plugin::instance()->get( LegacyEditorGuard::class );
		wp_register_script( 'easy-charts-admin-js', 'https://example.test/admin.js', array(), '1', true );

		foreach ( array( true, false ) as $with_v2 ) {
			$GLOBALS['post'] = get_post( $this->chart( $with_v2 ) ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Simulate the edit screen.
			wp_enqueue_script( 'easy-charts-admin-js' );

			$guard->dequeue_legacy_scripts();

			$this->assertSame( ! $with_v2, wp_script_is( 'easy-charts-admin-js', 'enqueued' ), $with_v2 ? 'v2' : '1.x' );
			wp_dequeue_script( 'easy-charts-admin-js' );
		}
	}

	public function test_1x_ajax_save_is_refused_for_v2_charts() {
		$id = $this->chart( true );
		$this->_setRole( 'administrator' );
		$_POST = array(
			'action'       => 'easy_charts_save_chart_data',
			'chart_id'     => $id,
			'_nonce_check' => wp_create_nonce( 'ec-ajax-nonce' ),
			'chart_data'   => '[["","Kia"],["2008",10]]',
		);

		try {
			$this->_handleAjax( 'easy_charts_save_chart_data' );
		} catch ( WPAjaxDieContinueException $e ) {
			unset( $e );
		} catch ( WPAjaxDieStopException $e ) {
			unset( $e );
		}

		$this->assertFalse( json_decode( $this->_last_response, true )['success'] );
		$this->assertSame( '', get_post_meta( $id, '_easy_charts_chart_data', true ) );
	}
}
