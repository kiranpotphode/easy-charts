<?php
/**
 * Tests for converting 1.x charts to v2 documents.
 *
 * @package EasyCharts
 */

namespace EasyCharts\Tests\Integration;

use EasyCharts\Chart\Schema;
use EasyCharts\Migration\V1Converter;
use WP_UnitTestCase;

/**
 * V1Converter: data grid, types and settings mapping.
 */
class V1ConverterTest extends WP_UnitTestCase {

	/**
	 * The 1.x demo grid, as jspreadsheet saves it (every cell a string).
	 */
	const GRID = '[["","Kia","Nissan"],["2008","10","11"],["2009","20","11.5"],["2010","","15"]]';

	/**
	 * Convert without configuration groups (1.x defaults apply).
	 *
	 * @param string $type   1.x type slug.
	 * @param array  $groups 1.x configuration groups.
	 * @param string $grid   JSON grid.
	 * @return array|null
	 */
	private function convert( $type = 'ec_bar_chart', array $groups = array(), $grid = self::GRID ) {
		return V1Converter::from_meta( $grid, $type, $groups, 'Car sales' );
	}

	public function test_converts_the_grid_with_the_same_orientation() {
		$doc = $this->convert();

		$this->assertTrue( Schema::validate( $doc ) );
		$this->assertSame( 'bar', $doc['type'] );
		$this->assertSame( array( '', 'Kia', 'Nissan' ), $doc['data']['columns'] );
		$this->assertSame(
			array(
				array( '2008', 10, 11 ),
				array( '2009', 20, 11.5 ),
				array( '2010', null, 15 ),
			),
			$doc['data']['rows']
		);
	}

	public function test_maps_every_1x_type() {
		foreach ( V1Converter::TYPES as $slug => $type ) {
			$this->assertSame( $type, $this->convert( $slug )['type'], $slug );
		}
		$this->assertSame( 'bar', $this->convert( '' )['type'], 'missing type' );
		$this->assertSame( 'bar', $this->convert( 'ec_unknown_chart' )['type'], 'unknown type' );
	}

	public function test_transposes_pie_donut_polar_and_step_up_like_1x() {
		foreach ( array( 'ec_pie_chart', 'ec_donut_chart', 'ec_polar_area_chart', 'ec_step_up_bar_chart' ) as $slug ) {
			$doc = $this->convert( $slug );

			$this->assertSame( array( '', '2008', '2009', '2010' ), $doc['data']['columns'], $slug );
			// uvCharts drew pies from the first data row: slices Kia and Nissan with 2008 values.
			$this->assertSame(
				array(
					array( 'Kia', 10, 20, null ),
					array( 'Nissan', 11, 11.5, 15 ),
				),
				$doc['data']['rows'],
				$slug
			);
		}
	}

	public function test_keeps_numbers_blank_cells_and_text() {
		$doc = $this->convert( 'ec_bar_chart', array(), '[["",2020,"B"],[2008,5," 7 "],["x","n/a",null],["y",true,1.5e1]]' );

		$this->assertSame( array( '', '2020', 'B' ), $doc['data']['columns'] );
		$this->assertSame(
			array(
				array( '2008', 5, 7 ),
				array( 'x', 'n/a', null ),
				array( 'y', 1, 15 ),
			),
			$doc['data']['rows']
		);
	}

	public function test_drops_blank_padding_and_pads_ragged_rows() {
		$doc = $this->convert( 'ec_bar_chart', array(), '[["","Kia","",""],["2008","10"],["","",""],["2009","20","",""]]' );

		$this->assertSame( array( '', 'Kia' ), $doc['data']['columns'] );
		$this->assertSame( array( array( '2008', 10 ), array( '2009', 20 ) ), $doc['data']['rows'] );
	}

	public function test_unusable_data_gives_null() {
		foreach ( array( '', 'not json', '{"a":1}', '[]', '[[]]', '["a","b"]', null ) as $grid ) {
			$this->assertNull( $this->convert( 'ec_bar_chart', array(), $grid ), var_export( $grid, true ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_export -- Assertion message.
		}
	}

	public function test_1x_defaults_become_only_the_overrides_that_change_the_chart() {
		// No groups saved: 1.x drew horizontal bars, no title, legend at the bottom.
		$this->assertSame(
			array(
				'title'  => array( 'show' => false ),
				'layout' => array( 'orientation' => 'horizontal' ),
			),
			$this->convert()['settings']
		);
	}

	public function test_maps_settings_saved_by_the_1x_form() {
		// Values as the 1.x save handler stores them: strings, HTML-encoded.
		$settings = $this->convert(
			'ec_line_chart',
			array(
				'graph'     => array(
					'palette'     => 'Soft',
					'responsive'  => '',
					'bgcolor'     => '#f0f0f0',
					'orientation' => 'Vertical',
					'opacity'     => '1',
				),
				'meta'      => array(
					'caption'        => 'Sales &#38; profit',
					'subcaption'     => 'By &#34;year&#34;',
					'hlabel'         => 'Year',
					'hsublabel'      => 'ignored',
					'vlabel'         => 'Units',
					'isDownloadable' => '1',
					'downloadLabel'  => 'Save &#60;PNG&#62;',
				),
				'dimension' => array(
					'width'  => '600',
					'height' => '300',
				),
				'label'     => array(
					'showlabel' => '0',
					'precision' => '1',
					'prefix'    => '&#36;',
					'suffix'    => ' k',
				),
				'legend'    => array(
					'position'    => 'right',
					'showlegends' => '1',
				),
				'tooltip'   => array( 'show' => '0' ),
			)
		)['settings'];

		$this->assertSame(
			array(
				'title'    => array(
					'text'    => 'Sales & profit',
					'subtext' => 'By "year"',
				),
				'size'     => array(
					'width'      => 600,
					'height'     => 300,
					'responsive' => false,
				),
				'palette'  => array( 'colors' => V1Converter::PALETTES['Soft'] ),
				'legend'   => array( 'position' => 'right' ),
				'tooltip'  => array( 'show' => false ),
				'labels'   => array(
					'show'      => false,
					'precision' => 1,
					'prefix'    => '$',
					'suffix'    => ' k',
				),
				'download' => array(
					'enabled' => true,
					'label'   => 'Save <PNG>',
				),
				'frame'    => array( 'background' => '#f0f0f0' ),
				'axis'     => array(
					'xLabel' => 'Year',
					'yLabel' => 'Units',
				),
			),
			$settings
		);
	}

	public function test_horizontal_charts_swap_the_axis_labels() {
		$settings = $this->convert(
			'ec_bar_chart',
			array(
				'meta' => array(
					'hlabel' => 'Units',
					'vlabel' => 'Year',
				),
			)
		)['settings'];

		$this->assertSame(
			array(
				'xLabel' => 'Year',
				'yLabel' => 'Units',
			),
			$settings['axis']
		);
	}

	public function test_title_follows_the_1x_caption() {
		$title = function ( $caption, $subcaption = '' ) {
			$settings = $this->convert(
				'ec_bar_chart',
				array(
					'meta' => array(
						'caption'    => $caption,
						'subcaption' => $subcaption,
					),
				)
			)['settings'];

			return isset( $settings['title'] ) ? $settings['title'] : array();
		};

		$this->assertSame( array(), $title( 'Car sales' ), 'caption equal to the post title' );
		$this->assertSame( array( 'text' => 'Other' ), $title( 'Other' ) );
		$this->assertSame( array( 'text' => 'Only sub' ), $title( '', 'Only sub' ) );
		$this->assertSame( array( 'show' => false ), $title( '' ) );
	}

	public function test_default_palette_follows_the_global_palette() {
		foreach ( array( 'Default', 'Brink', 'Unknown' ) as $palette ) {
			$settings = $this->convert( 'ec_bar_chart', array( 'graph' => array( 'palette' => $palette ) ) )['settings'];
			$this->assertArrayNotHasKey( 'palette', $settings, $palette );
		}
	}

	public function test_pie_charts_get_no_axis_settings_and_waterfall_no_legend() {
		$pie = $this->convert( 'ec_pie_chart', array( 'meta' => array( 'hlabel' => 'x' ) ) )['settings'];
		$this->assertArrayNotHasKey( 'layout', $pie );
		$this->assertArrayNotHasKey( 'axis', $pie );

		$waterfall = $this->convert( 'ec_waterfall_chart' )['settings'];
		$this->assertSame( array( 'show' => false ), $waterfall['legend'] );
	}

	public function test_convert_reads_meta_and_applies_the_1x_configuration_filter() {
		$id = self::factory()->post->create(
			array(
				'post_type'  => 'easy_charts',
				'post_title' => 'Car sales',
			)
		);
		update_post_meta( $id, V1Converter::DATA_KEY, wp_slash( self::GRID ) );
		update_post_meta( $id, V1Converter::TYPE_KEY, 'ec_donut_chart' );
		update_post_meta( $id, '_ec_chart_label', array( 'suffix' => ' units' ) );

		$filter = function ( $option, $chart_id, $group ) use ( $id ) {
			if ( $id === $chart_id && 'legend' === $group ) {
				$option['position'] = 'right';
			}
			return $option;
		};
		add_filter( 'easy_charts_get_chart_configurations', $filter, 10, 3 );
		$doc = ( new V1Converter() )->convert( $id );
		remove_filter( 'easy_charts_get_chart_configurations', $filter, 10 );

		$this->assertSame( 'donut', $doc['type'] );
		$this->assertSame( ' units', $doc['settings']['labels']['suffix'] );
		$this->assertSame( 'right', $doc['settings']['legend']['position'] );
	}

	public function test_convert_filter_can_adjust_and_invalid_results_are_discarded() {
		$id = self::factory()->post->create( array( 'post_type' => 'easy_charts' ) );
		update_post_meta( $id, V1Converter::DATA_KEY, wp_slash( self::GRID ) );
		$converter = new V1Converter();

		$retype = function ( $doc ) {
			$doc['type'] = 'line';
			return $doc;
		};
		add_filter( 'easy_charts_convert_v1_chart', $retype );
		$this->assertSame( 'line', $converter->convert( $id )['type'] );
		remove_filter( 'easy_charts_convert_v1_chart', $retype );

		add_filter( 'easy_charts_convert_v1_chart', '__return_empty_array' );
		$this->assertNull( $converter->convert( $id ) );
		remove_filter( 'easy_charts_convert_v1_chart', '__return_empty_array' );
	}

	public function test_chart_without_1x_data_is_not_converted() {
		$id = self::factory()->post->create( array( 'post_type' => 'easy_charts' ) );

		$this->assertFalse( ( new V1Converter() )->has_v1_data( $id ) );
		$this->assertNull( ( new V1Converter() )->convert( $id ) );
	}

	public function test_1x_data_filter_is_deprecated() {
		$id = self::factory()->post->create( array( 'post_type' => 'easy_charts' ) );
		update_post_meta( $id, V1Converter::DATA_KEY, wp_slash( self::GRID ) );
		add_filter( 'easy_charts_get_chart_data', '__return_null' );
		$this->setExpectedDeprecated( 'easy_charts_get_chart_data' );

		( new V1Converter() )->convert( $id );

		remove_filter( 'easy_charts_get_chart_data', '__return_null' );
	}
}
