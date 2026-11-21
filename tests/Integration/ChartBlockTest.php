<?php
/**
 * Tests for the chart block.
 *
 * @package EasyCharts
 */

namespace EasyCharts\Tests\Integration;

use EasyCharts\Block\ChartBlock;
use EasyCharts\Chart\ChartRepository;
use EasyCharts\Plugin;
use WP_Block_Type_Registry;
use WP_UnitTestCase;

/**
 * ChartBlock registration and server rendering.
 */
class ChartBlockTest extends WP_UnitTestCase {

	/**
	 * A chart document for block attributes.
	 *
	 * @param array $overrides Document overrides.
	 * @return array
	 */
	private function doc( array $overrides = array() ) {
		return array_merge(
			array(
				'schema' => 2,
				'type'   => 'pie',
				'data'   => array(
					'columns' => array( '', 'Share' ),
					'rows'    => array( array( 'A', 1 ), array( 'B', 2 ) ),
				),
			),
			$overrides
		);
	}

	/**
	 * Render block markup as it appears in post content.
	 *
	 * @param array $attributes Block attributes.
	 * @return string
	 */
	private function render( array $attributes ) {
		return do_blocks( '<!-- wp:easy-charts/chart ' . wp_json_encode( (object) $attributes ) . ' /-->' );
	}

	/**
	 * Decode the JSON config embedded in rendered HTML.
	 *
	 * @param string $html Rendered chart.
	 * @return array
	 */
	private function config( $html ) {
		$this->assertSame( 1, preg_match( '#<script type="application/json" class="easy-charts-chart__config">(.*?)</script>#s', $html, $match ) );

		return json_decode( $match[1], true );
	}

	public function test_block_is_registered_from_the_build() {
		$block = WP_Block_Type_Registry::get_instance()->get_registered( ChartBlock::NAME );

		$this->assertNotNull( $block );
		$this->assertSame( 3, $block->api_version );
		$this->assertContains( 'easy-charts-echarts', $block->editor_script_handles );
	}

	public function test_embedded_chart_renders_like_the_shortcode() {
		$id = self::factory()->post->create(
			array(
				'post_type'   => 'easy_charts',
				'post_status' => 'publish',
			)
		);
		( new ChartRepository() )->save( $id, $this->doc( array( 'type' => 'line' ) ) );

		$html = $this->render( array( 'chartId' => $id ) );

		$this->assertStringStartsWith( '<div class="wp-block-easy-charts-chart">', $html );
		$this->assertStringContainsString( 'easy-charts-chart--line', $html );
		$this->assertStringContainsString( 'data-chart-id="' . $id . '"', $html );
	}

	public function test_block_chart_renders_from_its_attributes() {
		$html = $this->render(
			array(
				'chart' => $this->doc( array( 'settings' => array( 'title' => array( 'text' => 'Budget' ) ) ) ),
				'align' => 'wide',
			)
		);

		$this->assertStringContainsString( 'alignwide', $html );
		$this->assertStringContainsString( 'easy-charts-chart--pie', $html );
		$this->assertStringNotContainsString( 'data-chart-id', $html );
		$this->assertStringContainsString( '<caption>Budget</caption>', $html );
		$this->assertSame( 'Budget', $this->config( $html )['title'] );
	}

	public function test_block_chart_is_validated_and_escaped() {
		$this->assertSame( '', $this->render( array( 'chart' => $this->doc( array( 'type' => 'radar' ) ) ) ), 'unknown type' );
		$this->assertSame( '', $this->render( array( 'chart' => array( 'type' => 'bar' ) ) ), 'no data' );

		$html = $this->render(
			array(
				'chart' => $this->doc(
					array(
						'data' => array(
							'columns' => array( '', '</script><script>alert(1)</script>' ),
							'rows'    => array( array( '<img src=x onerror=alert(1)>', 1 ) ),
						),
					)
				),
			)
		);
		$this->assertStringNotContainsString( '<script>alert', $html );
		$this->assertStringNotContainsString( '<img', $html );
		$this->assertSame( '</script><script>alert(1)</script>', $this->config( $html )['data']['columns'][1] );
	}

	public function test_empty_or_missing_charts_render_nothing() {
		$this->assertSame( '', $this->render( array() ) );
		$this->assertSame( '', $this->render( array( 'chartId' => 999999 ) ) );
	}

	public function test_editor_gets_the_base_settings() {
		$block = Plugin::instance()->get( ChartBlock::class );
		update_option( 'easy_charts_settings', array( 'legend' => array( 'position' => 'top' ) ) );

		$block->editor_data();
		$inline = implode( '', wp_scripts()->get_data( generate_block_asset_handle( ChartBlock::NAME, 'editorScript' ), 'before' ) );

		$this->assertStringContainsString( 'window.easyChartsEditor = ', $inline );
		$data = json_decode( substr( $inline, strpos( $inline, '{' ), -1 ), true );
		$this->assertSame( 'top', $data['baseSettings']['legend']['position'] );
		$this->assertSame( 'Increase', $data['strings']['increase'] );
	}
}
