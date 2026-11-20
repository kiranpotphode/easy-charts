<?php
/**
 * Tests for the chart list columns.
 *
 * @package EasyCharts
 */

namespace EasyCharts\Tests\Integration;

use EasyCharts\Admin\ChartList;
use EasyCharts\Chart\ChartRepository;
use EasyCharts\Chart\Schema;
use EasyCharts\Plugin;
use WP_UnitTestCase;

/**
 * ChartList columns.
 */
class ChartListTest extends WP_UnitTestCase {

	/**
	 * Print one cell.
	 *
	 * @param string $column  Column key.
	 * @param int    $post_id Chart ID.
	 * @return string
	 */
	private function cell( $column, $post_id ) {
		ob_start();
		Plugin::instance()->get( ChartList::class )->column( $column, $post_id );

		return ob_get_clean();
	}

	public function test_columns_follow_the_title() {
		$columns = Plugin::instance()->get( ChartList::class )->columns(
			array(
				'cb'    => '',
				'title' => 'Title',
				'date'  => 'Date',
			)
		);

		$this->assertSame( array( 'cb', 'title', 'easy_charts_type', 'easy_charts_shortcode', 'date' ), array_keys( $columns ) );
	}

	public function test_cells_show_type_and_shortcode() {
		$v2 = self::factory()->post->create( array( 'post_type' => 'easy_charts' ) );
		( new ChartRepository() )->save(
			$v2,
			array(
				'schema' => 2,
				'type'   => 'polar-area',
				'data'   => array(
					'columns' => array( '', 'A' ),
					'rows'    => array( array( 'x', 1 ) ),
				),
			)
		);
		$v1 = self::factory()->post->create( array( 'post_type' => 'easy_charts' ) );
		update_post_meta( $v1, '_easy_charts_chart_data', '[["","Kia"],["2008","10"]]' );
		update_post_meta( $v1, '_ec_chart_type', 'ec_line_chart' );
		$empty = self::factory()->post->create( array( 'post_type' => 'easy_charts' ) );

		$this->assertSame( 'Polar area', $this->cell( 'easy_charts_type', $v2 ) );
		$this->assertSame( 'Line <em>(1.x look)</em>', $this->cell( 'easy_charts_type', $v1 ) );
		$this->assertStringContainsString( 'No data', $this->cell( 'easy_charts_type', $empty ) );
		$this->assertSame( '<code>[easy_chart chart_id="' . $v2 . '"]</code>', $this->cell( 'easy_charts_shortcode', $v2 ) );
	}

	public function test_every_chart_type_has_a_label() {
		$this->assertEqualSets( Schema::TYPES, array_keys( Schema::type_labels() ) );
	}
}
