<?php
/**
 * Tests for the chart post edit screen extras.
 *
 * @package EasyCharts
 */

namespace EasyCharts\Tests\Integration;

use EasyCharts\Admin\ChartEditor;
use EasyCharts\Plugin;
use WP_UnitTestCase;

/**
 * ChartEditor loads its script on chart posts only.
 */
class ChartEditorTest extends WP_UnitTestCase {

	public function tear_down() {
		wp_dequeue_script( ChartEditor::SCRIPT );
		set_current_screen( 'front' );
		parent::tear_down();
	}

	public function test_script_loads_on_chart_posts_only() {
		$editor = Plugin::instance()->get( ChartEditor::class );

		set_current_screen( 'post' );
		$editor->enqueue();
		$this->assertFalse( wp_script_is( ChartEditor::SCRIPT, 'enqueued' ), 'posts' );

		set_current_screen( 'easy_charts' );
		$editor->enqueue();
		$this->assertTrue( wp_script_is( ChartEditor::SCRIPT, 'enqueued' ), 'charts' );
	}

	public function test_chart_posts_without_the_block_open_with_it_in_the_editor() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );
		$id = self::factory()->post->create(
			array(
				'post_type'    => 'easy_charts',
				'post_content' => '',
			)
		);

		$request = new \WP_REST_Request( 'GET', '/wp/v2/easy-charts/' . $id );
		$request->set_query_params( array( 'context' => 'edit' ) );
		$data = rest_do_request( $request )->get_data();

		$this->assertSame( '<!-- wp:easy-charts/chart /-->', $data['content']['raw'] );
		$this->assertSame( '', get_post_field( 'post_content', $id ), 'nothing is saved' );

		// Content that already has the block is served as it is.
		wp_update_post(
			array(
				'ID'           => $id,
				'post_content' => '<!-- wp:easy-charts/chart {"align":"wide"} /-->',
			)
		);
		$this->assertSame( '<!-- wp:easy-charts/chart {"align":"wide"} /-->', rest_do_request( $request )->get_data()['content']['raw'] );
	}

	public function test_1x_admin_is_gone() {
		$this->assertFalse( class_exists( 'Easy_Charts_Admin', false ) );
		$this->assertFalse( has_action( 'wp_ajax_easy_charts_save_chart_data' ) );
		$this->assertFalse( has_action( 'wp_ajax_easy_charts_get_published_charts' ) );
	}
}
