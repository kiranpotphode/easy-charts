<?php
/**
 * Tests for converting 1.x charts to v2 documents.
 *
 * @package EasyCharts
 */

namespace EasyCharts\Tests\Integration;

use EasyCharts\Chart\ChartRepository;
use EasyCharts\Chart\Schema;
use EasyCharts\Migration\V1Converter;
use EasyCharts\Settings\SettingsResolver;
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

	public function test_fonts_curves_opacity_and_donut_hole_are_kept_only_when_changed() {
		$defaults = array(
			'caption' => array( 'fontfamily' => 'Arial' ),
			'label'   => array( 'fontsize' => '11' ),
			'line'    => array( 'interpolation' => 'linear' ),
			'area'    => array(
				'interpolation' => 'linear',
				'opacity'       => '0.2',
			),
			'donut'   => array( 'factor' => '0.4' ),
		);
		foreach ( array( 'ec_line_chart', 'ec_area_chart', 'ec_donut_chart' ) as $type ) {
			$settings = $this->convert( $type, $defaults )['settings'];
			foreach ( array( 'font', 'line', 'area', 'donut' ) as $group ) {
				$this->assertArrayNotHasKey( $group, $settings, "$type: 1.x defaults follow the 2.0 settings ($group)" );
			}
		}

		$changed = array(
			'caption' => array( 'fontfamily' => 'Georgia' ),
			'label'   => array( 'fontsize' => '14' ),
			'line'    => array( 'interpolation' => 'monotone' ),
			'area'    => array(
				'interpolation' => 'basis',
				'opacity'       => '0.65',
			),
			'donut'   => array( 'factor' => '0.25' ),
		);
		$this->assertSame(
			array(
				'family' => 'serif',
				'size'   => 14,
			),
			$this->convert( 'ec_bar_chart', $changed )['settings']['font']
		);
		$this->assertSame( array( 'smooth' => true ), $this->convert( 'ec_line_chart', $changed )['settings']['line'] );

		$area = $this->convert( 'ec_stacked_area_chart', $changed )['settings'];
		$this->assertSame( array( 'smooth' => true ), $area['line'] );
		$this->assertSame( array( 'opacity' => 0.65 ), $area['area'] );

		$this->assertSame( array( 'hole' => 0.25 ), $this->convert( 'ec_donut_chart', $changed )['settings']['donut'] );
		$bar = $this->convert( 'ec_bar_chart', $changed )['settings'];
		foreach ( array( 'line', 'area', 'donut' ) as $group ) {
			$this->assertArrayNotHasKey( $group, $bar, "bar charts don't use $group settings" );
		}

		$this->assertSame( 'monospace', $this->convert( 'ec_bar_chart', array( 'caption' => array( 'fontfamily' => 'Courier New' ) ) )['settings']['font']['family'] );
		$this->assertArrayNotHasKey( 'font', $this->convert( 'ec_bar_chart', array( 'caption' => array( 'fontfamily' => 'Comic Sans' ) ) )['settings'], 'unknown fonts follow the site font' );
		$this->assertSame( array( 'hole' => 0.9 ), $this->convert( 'ec_donut_chart', array( 'donut' => array( 'factor' => '5' ) ) )['settings']['donut'], 'clamped' );
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

	public function test_default_palette_keeps_the_uvcharts_default_colours() {
		foreach ( array( 'Default', 'Brink', 'Unknown' ) as $palette ) {
			$settings = $this->convert( 'ec_bar_chart', array( 'graph' => array( 'palette' => $palette ) ) )['settings'];
			// Not an override: the 1.x look supplies the uvCharts Default colours until migration.
			$this->assertArrayNotHasKey( 'palette', $settings, $palette );
		}
		$this->assertSame( V1Converter::DEFAULT_COLORS, V1Converter::look_settings()['palette']['colors'] );
	}

	public function test_converted_charts_keep_their_1x_look() {
		$doc = $this->convert();
		$this->assertSame(
			array(
				'templateId' => null,
				'v1Look'     => true,
			),
			$doc['source']
		);

		// Site-wide settings don't apply until the chart is migrated.
		update_option(
			'easy_charts_settings',
			array(
				'palette' => array( 'colors' => array( '#111111' ) ),
				'legend'  => array( 'position' => 'top' ),
			)
		);
		$resolver = new SettingsResolver( new ChartRepository() );
		$settings = $resolver->resolve( $doc );
		$this->assertSame( V1Converter::DEFAULT_COLORS, $settings['palette']['colors'] );
		$this->assertSame( 'bottom', $settings['legend']['position'] );
		$this->assertSame( 'sans-serif', $settings['font']['family'] );
		$this->assertSame( 11, $settings['font']['size'] );

		// Migrated: what the chart didn't change in 1.x follows the site.
		unset( $doc['source']['v1Look'] );
		$settings = $resolver->resolve( $doc );
		$this->assertSame( array( '#111111' ), $settings['palette']['colors'] );
		$this->assertSame( 'top', $settings['legend']['position'] );
		$this->assertSame( 'site', $settings['font']['family'] );
		$this->assertSame( 'horizontal', $settings['layout']['orientation'], 'the 1.x orientation stays' );
	}

	public function test_explicit_1x_choices_stay_after_migration_even_when_they_equal_2x_defaults() {
		$settings = $this->convert(
			'ec_area_chart',
			array(
				'area'    => array( 'opacity' => '0.4' ),
				'caption' => array( 'fontfamily' => 'Verdana' ),
				'label'   => array( 'fontsize' => '12' ),
			)
		)['settings'];

		$this->assertSame( array( 'opacity' => 0.4 ), $settings['area'] );
		$this->assertSame(
			array(
				'size'   => 12,
				'family' => 'sans-serif',
			),
			$settings['font']
		);
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
