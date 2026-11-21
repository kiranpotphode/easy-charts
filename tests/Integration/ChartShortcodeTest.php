<?php
/**
 * Tests for the [easy_chart] shortcode.
 *
 * @package EasyCharts
 */

namespace EasyCharts\Tests\Integration;

use EasyCharts\Chart\ChartRepository;
use WP_UnitTestCase;

/**
 * ChartShortcode output.
 */
class ChartShortcodeTest extends WP_UnitTestCase {

	/**
	 * Create a chart.
	 *
	 * @param bool   $with_v2 Whether to give it a v2 document.
	 * @param string $status  Post status.
	 * @return int
	 */
	private function chart( $with_v2, $status = 'publish' ) {
		$id = self::factory()->post->create(
			array(
				'post_type'   => 'easy_charts',
				'post_status' => $status,
			)
		);
		update_post_meta( $id, '_easy_charts_chart_data', '[["","Kia"],["2008",10]]' );

		if ( $with_v2 ) {
			( new ChartRepository() )->save(
				$id,
				array(
					'schema' => 2,
					'type'   => 'line',
					'data'   => array(
						'columns' => array( '', 'Kia' ),
						'rows'    => array( array( '2008', 10 ) ),
					),
				)
			);
		}

		return $id;
	}

	public function test_v2_chart_renders_with_echarts() {
		$html = do_shortcode( '[easy_chart chart_id="' . $this->chart( true ) . '"]' );

		$this->assertStringContainsString( 'easy-charts-chart--line', $html );
		$this->assertStringNotContainsString( 'uv-div-', $html );
	}

	public function test_1x_chart_renders_with_echarts_after_conversion() {
		$id   = $this->chart( false );
		$html = do_shortcode( "[easy_chart chart_id='" . $id . "']" );

		// No 1.x type saved: 1.x drew a bar chart.
		$this->assertStringContainsString( 'easy-charts-chart--bar', $html );
		$this->assertStringNotContainsString( 'uv-div-', $html );
	}

	public function test_chart_without_usable_data_shows_a_notice_to_its_editors_only() {
		$id = $this->chart( false );
		update_post_meta( $id, '_easy_charts_chart_data', 'not a grid' );
		$shortcode = "[easy_chart chart_id='" . $id . "']";

		wp_set_current_user( 0 );
		$this->assertSame( '', do_shortcode( $shortcode ), 'visitor' );

		wp_set_current_user( self::factory()->user->create( array( 'role' => 'contributor' ) ) );
		$this->assertSame( '', do_shortcode( $shortcode ), 'user who cannot edit the chart' );

		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );
		$html = do_shortcode( $shortcode );
		$this->assertStringStartsWith( '<p class="easy-charts-notice">', $html );
		$this->assertStringContainsString( 'post.php?post=' . $id . '&#038;action=edit', $html );
		$this->assertStringNotContainsString( 'easy-charts-chart', $html );
		$this->assertStringNotContainsString( 'uv-div-', $html );
	}

	public function test_no_notice_for_posts_that_are_not_charts() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$post = self::factory()->post->create();

		$this->assertSame( '', do_shortcode( '[easy_chart chart_id="' . $post . '"]' ) );
	}

	public function test_invalid_ids_render_nothing() {
		foreach ( array( '[easy_chart]', '[easy_chart chart_id="0"]', '[easy_chart chart_id="abc"]' ) as $shortcode ) {
			$this->assertSame( '', do_shortcode( $shortcode ), $shortcode );
		}
	}

	public function test_private_chart_is_hidden_from_visitors() {
		wp_set_current_user( 0 );
		$id = $this->chart( true, 'private' );

		$this->assertSame( '', do_shortcode( '[easy_chart chart_id="' . $id . '"]' ) );
	}
}
