<?php
/**
 * Converts 1.x charts to v2 chart documents.
 *
 * @package EasyCharts
 */

namespace EasyCharts\Migration;

use EasyCharts\Chart\Schema;
use EasyCharts\Settings\Defaults;
use EasyCharts\Settings\SettingsResolver;

/**
 * Builds a v2 document from a chart's 1.x post meta, in memory.
 *
 * Read-only: 1.x meta is never modified or deleted, and the result is not stored. A chart only
 * gets a stored v2 document when someone saves it in the 2.0 editor.
 *
 * Settings follow what 1.x drew (uvCharts), and a 1.x value only becomes a chart override when it
 * differs from the v2 default, so untouched settings follow the site's global settings. Fonts,
 * margins, axis ticks, opacity, scale types, line and area interpolation, the donut hole size and
 * the tooltip format have no v2 equivalent and are dropped.
 */
final class V1Converter {

	const DATA_KEY = '_easy_charts_chart_data';

	const TYPE_KEY = '_ec_chart_type';

	/**
	 * 1.x type slugs and their v2 types. Anything else was drawn as a bar chart in 1.x.
	 */
	const TYPES = array(
		'ec_bar_chart'          => 'bar',
		'ec_area_chart'         => 'area',
		'ec_stacked_bar_chart'  => 'stacked-bar',
		'ec_stacked_area_chart' => 'stacked-area',
		'ec_percent_bar_chart'  => 'percent-bar',
		'ec_percent_area_chart' => 'percent-area',
		'ec_pie_chart'          => 'pie',
		'ec_donut_chart'        => 'donut',
		'ec_step_up_bar_chart'  => 'step-up-bar',
		'ec_waterfall_chart'    => 'waterfall',
		'ec_line_chart'         => 'line',
		'ec_polar_area_chart'   => 'polar-area',
	);

	/**
	 * Types whose 1.x data grid was transposed before drawing.
	 */
	const TRANSPOSED = array( 'pie', 'donut', 'polar-area', 'step-up-bar' );

	const PIE_TYPES = array( 'pie', 'donut', 'polar-area' );

	/**
	 * The 1.x font choices by the generic family that 2.0 draws them with (only generic families
	 * are sure to exist on every visitor's device; 1.x never loaded its fonts). Arial, the 1.x
	 * default, is left out: charts that kept it follow the site-wide font setting.
	 */
	const FONT_FAMILIES = array(
		'Arial Black'         => 'sans-serif',
		'Arial Narrow'        => 'sans-serif',
		'Century Gothic'      => 'sans-serif',
		'Gill Sans'           => 'sans-serif',
		'Impact'              => 'sans-serif',
		'Lucida Sans Unicode' => 'sans-serif',
		'Tahoma'              => 'sans-serif',
		'Trebuchet MS'        => 'sans-serif',
		'Verdana'             => 'sans-serif',
		'Georgia'             => 'serif',
		'Palatino Linotype'   => 'serif',
		'Times New Roman'     => 'serif',
		'Courier New'         => 'monospace',
		'Lucida Console'      => 'monospace',
	);

	/**
	 * The 1.x configuration groups the converter reads, with the 1.x fallback values used when a
	 * group was never saved (1.x `Easy_Charts::ec_get_chart_configuration()`).
	 */
	const GROUP_DEFAULTS = array(
		'graph'     => array(
			'palette'     => 'Brink',
			'responsive'  => 1,
			'bgcolor'     => '#ffffff',
			'orientation' => 'Horizontal',
		),
		'meta'      => array(
			'caption'        => '',
			'subcaption'     => '',
			'hlabel'         => '',
			'vlabel'         => '',
			'isDownloadable' => 0,
			'downloadLabel'  => 'Download',
		),
		'dimension' => array(
			'width'  => 400,
			'height' => 400,
		),
		'label'     => array(
			'fontsize'  => 11,
			'showlabel' => 1,
			'precision' => 2,
			'prefix'    => '',
			'suffix'    => '',
		),
		'caption'   => array(
			'fontfamily' => 'Arial',
		),
		'line'      => array(
			'interpolation' => 'linear',
		),
		'area'      => array(
			'interpolation' => 'linear',
			'opacity'       => 0.2,
		),
		'donut'     => array(
			'factor' => 0.4,
		),
		'legend'    => array(
			'position'    => 'bottom',
			'showlegends' => true,
		),
		'tooltip'   => array(
			'show' => 1,
		),
	);

	/**
	 * The uvCharts "Default" palette, used by 1.x charts that never chose another one.
	 */
	const DEFAULT_COLORS = array( '#00BBC9', '#EC63AB', '#AA8AE4', '#83CE44', '#ff8f25', '#009EAA', '#CA4F7F', '#9C70C0', '#6BAF3B' );

	/**
	 * How 1.x drew charts where 2.0's defaults differ, for charts that keep their 1.x look.
	 *
	 * A converted chart's own settings hold only what its author changed in 1.x. Until it is
	 * migrated it is drawn on top of these values (not the site-wide settings); once migrated, the
	 * values it didn't change follow the 2.0 and site-wide settings.
	 */
	const LOOK = array(
		'palette' => array( 'colors' => self::DEFAULT_COLORS ),
		// 1.x drew in Arial; the generic sans-serif stack includes it.
		'font'    => array(
			'family' => 'sans-serif',
			'size'   => 11,
		),
		'area'    => array( 'opacity' => 0.2 ),
		'donut'   => array( 'hole' => 0.4 ),
	);

	/**
	 * Palettes from uvCharts, by their 1.x name ("Default" is DEFAULT_COLORS).
	 */
	const PALETTES = array(
		'OldDefault' => array( '#7E6DA1', '#C2CF30', '#FF8900', '#FE2600', '#E3003F', '#8E1E5F', '#FE2AC2', '#CCF030', '#9900EC', '#3A1AA8', '#3932FE', '#3276FF', '#35B9F6', '#42BC6A', '#91E0CB' ),
		'Plain'      => array( '#B1EB68', '#B1B9B5', '#FFA16C', '#9B64E7', '#CEE113', '#2F9CFA', '#CA6877', '#EC3D8C', '#9CC66D', '#C73640', '#7D9532', '#B064DC' ),
		'Android'    => array( '#33B5E5', '#AA66CC', '#99CC00', '#FFBB33', '#FF4444', '#0099CC', '#9933CC', '#669900', '#FF8800', '#CC0000' ),
		'Soft'       => array( '#9ED8D2', '#FFD478', '#F16D9A', '#A8D59D', '#FDC180', '#F05133', '#EDED8A', '#F6A0A5', '#9F218B' ),
		'Simple'     => array( '#FF8181', '#FFB081', '#FFE081', '#EFFF81', '#BFFF81', '#90FF81', '#81FFA2', '#81FFD1', '#9681FF', '#C281FF', '#FF81DD' ),
		'Egypt'      => array( '#3A3E04', '#784818', '#FCFCA8', '#C03C0C', '#F0A830', '#A8783C', '#FCFCFC', '#FCE460', '#540C00', '#C0C084', '#3C303C', '#1EA34A', '#606C54', '#F06048' ),
		'Olive'      => array( '#18240C', '#3C6C18', '#60A824', '#90D824', '#A8CC60', '#789C60', '#CCF030', '#B4CCA8', '#D8F078', '#40190D', '#E4F0CC' ),
		'Candid'     => array( '#AF5E14', '#81400C', '#E5785D', '#FEBFBF', '#A66363', '#C7B752', '#EFF1A7', '#83ADB7', '#528F98', '#BCEDF5', '#446B3D', '#8BD96F', '#E4FFB9' ),
		'Sulphide'   => array( '#594440', '#0392A7', '#FFC343', '#E2492F', '#007257', '#B0BC4A', '#2E5493', '#7C2738', '#FF538B', '#A593A1', '#EBBA86', '#E2D9CA' ),
		'Lint'       => array( '#A8A878', '#F0D89C', '#60909C', '#242418', '#E49C30', '#54483C', '#306090', '#C06C00', '#C0C0C0', '#847854', '#6C3C00', '#9C3C3C', '#183C60', '#FCCC00', '#840000', '#FCFCFC' ),
	);

	/**
	 * Whether a chart has 1.x data to convert.
	 *
	 * @param int $chart_id Chart post ID.
	 * @return bool
	 */
	public function has_v1_data( $chart_id ) {
		return '' !== get_post_meta( (int) $chart_id, self::DATA_KEY, true );
	}

	/**
	 * Convert a chart's 1.x meta.
	 *
	 * @param int $chart_id Chart post ID.
	 * @return array|null v2 document, or null when the chart has no usable 1.x data.
	 */
	public function convert( $chart_id ) {
		$chart_id = (int) $chart_id;
		if ( ! $this->has_v1_data( $chart_id ) ) {
			return null;
		}

		if ( has_filter( 'easy_charts_get_chart_data' ) ) {
			_deprecated_hook( 'easy_charts_get_chart_data', '2.0.0', 'easy_charts_convert_v1_chart', esc_html__( 'Charts are drawn with Apache ECharts in 2.0, so the uvCharts payload this filter changed is no longer used.', 'easy-charts' ) );
		}

		$groups = array();
		foreach ( array_keys( self::GROUP_DEFAULTS ) as $group ) {
			$groups[ $group ] = $this->read_group( $chart_id, $group );
		}

		$doc = self::from_meta(
			get_post_meta( $chart_id, self::DATA_KEY, true ),
			get_post_meta( $chart_id, self::TYPE_KEY, true ),
			$groups,
			(string) get_post_field( 'post_title', $chart_id, 'raw' )
		);
		if ( null === $doc ) {
			return null;
		}

		/**
		 * Filters a chart converted from 1.x before it is drawn or opened.
		 *
		 * The result is validated against the v2 schema; an invalid document is discarded.
		 *
		 * @since 2.0.0
		 *
		 * @param array $doc      v2 chart document.
		 * @param int   $chart_id Chart post ID.
		 */
		$doc = Schema::sanitize( apply_filters( 'easy_charts_convert_v1_chart', $doc, $chart_id ) );

		return is_wp_error( $doc ) ? null : $doc;
	}

	/**
	 * Convert 1.x meta values. Pure: reads nothing from the database.
	 *
	 * @param mixed  $data       `_easy_charts_chart_data`: JSON grid, row 0 = series names.
	 * @param mixed  $type       `_ec_chart_type` slug.
	 * @param array  $groups     1.x configuration groups by name (graph, meta, ...). Missing groups
	 *                           and keys fall back to the 1.x defaults.
	 * @param string $post_title Chart post title.
	 * @return array|null v2 document, or null when the data is not a usable grid.
	 */
	public static function from_meta( $data, $type, array $groups, $post_title = '' ) {
		$type = is_string( $type ) && isset( self::TYPES[ $type ] ) ? self::TYPES[ $type ] : 'bar';
		$grid = self::parse_grid( $data );
		if ( null === $grid ) {
			return null;
		}
		if ( in_array( $type, self::TRANSPOSED, true ) ) {
			$grid = self::transpose( $grid );
		}

		$header  = array_shift( $grid );
		$columns = array_map( array( __CLASS__, 'label' ), $header );
		$rows    = array();
		foreach ( $grid as $row ) {
			$cells = array( self::label( array_shift( $row ) ) );
			foreach ( $row as $cell ) {
				$cells[] = self::cell( $cell );
			}
			$rows[] = $cells;
		}

		foreach ( self::GROUP_DEFAULTS as $group => $defaults ) {
			$saved            = isset( $groups[ $group ] ) && is_array( $groups[ $group ] ) ? $groups[ $group ] : array();
			$groups[ $group ] = array_merge( $defaults, $saved );
		}

		return Schema::normalize(
			array(
				'schema'   => Schema::VERSION,
				'type'     => $type,
				'data'     => array(
					'columns' => $columns,
					'rows'    => $rows,
				),
				'settings' => self::own_settings( $type, $groups, (string) $post_title ),
				'source'   => array(
					'templateId' => null,
					'v1Look'     => true,
				),
			)
		);
	}

	/**
	 * Read one configuration group as 1.x did, including its fallback and filter.
	 *
	 * @param int    $chart_id Chart post ID.
	 * @param string $group    Group name.
	 * @return array
	 */
	private function read_group( $chart_id, $group ) {
		$option = get_post_meta( $chart_id, '_ec_chart_' . $group, true );
		if ( '' === $option ) {
			$option = self::GROUP_DEFAULTS[ $group ];
		}

		/**
		 * Filters a 1.x configuration group before the chart is converted.
		 *
		 * @since 1.0.3
		 * @since 2.0.0 Only read when converting a chart that has no 2.0 document yet.
		 *
		 * @param array  $option   Configuration group (uvCharts keys).
		 * @param int    $chart_id Chart post ID.
		 * @param string $group    Group name, e.g. `meta` or `legend`.
		 */
		$option = apply_filters( 'easy_charts_get_chart_configurations', $option, $chart_id, $group );

		return is_array( $option ) ? $option : array();
	}

	/**
	 * A 1.x slider value (0 to 1), or null when it is missing, not a number or the 1.x default.
	 *
	 * @param mixed $value      Saved value (a string from the 1.x form).
	 * @param float $v1_default The 1.x default.
	 * @return float|null
	 */
	private static function fraction( $value, $v1_default ) {
		$value = self::scalar( $value );
		if ( ! is_numeric( $value ) || abs( (float) $value - $v1_default ) < 0.001 ) {
			return null;
		}

		return round( (float) $value, 2 );
	}

	/**
	 * Decode the 1.x grid into rectangular rows, dropping blank padding rows and columns.
	 *
	 * @param mixed $data JSON grid.
	 * @return array|null Rows of cells, header first; null when there is no header row.
	 */
	private static function parse_grid( $data ) {
		$grid = is_string( $data ) ? json_decode( $data, true ) : null;
		if ( ! is_array( $grid ) || ! $grid ) {
			return null;
		}

		$rows = array();
		foreach ( $grid as $row ) {
			if ( ! is_array( $row ) ) {
				return null;
			}
			$rows[] = array_values( $row );
		}

		$width = max( array_map( 'count', $rows ) );
		if ( 0 === $width ) {
			return null;
		}
		foreach ( $rows as $i => $row ) {
			$rows[ $i ] = array_pad( $row, $width, '' );
		}

		// Blank rows after the header and blank columns after the first are spreadsheet padding.
		$header = array_shift( $rows );
		$rows   = array_values( array_filter( $rows, array( __CLASS__, 'has_content' ) ) );
		array_unshift( $rows, $header );

		$keep = array( 0 );
		for ( $col = 1; $col < $width; $col++ ) {
			if ( self::has_content( array_column( $rows, $col ) ) ) {
				$keep[] = $col;
			}
		}

		return array_map(
			function ( $row ) use ( $keep ) {
				return array_map(
					function ( $col ) use ( $row ) {
						return $row[ $col ];
					},
					$keep
				);
			},
			$rows
		);
	}

	/**
	 * Swap rows and columns.
	 *
	 * @param array $grid Rectangular grid.
	 * @return array
	 */
	private static function transpose( array $grid ) {
		$result = array();
		foreach ( $grid as $row ) {
			foreach ( $row as $col => $cell ) {
				$result[ $col ][] = $cell;
			}
		}

		return $result;
	}

	/**
	 * Whether any cell holds a value.
	 *
	 * @param array $cells Cells.
	 * @return bool
	 */
	private static function has_content( array $cells ) {
		foreach ( $cells as $cell ) {
			if ( null !== $cell && '' !== trim( (string) self::scalar( $cell ) ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * A header or row label as text.
	 *
	 * @param mixed $cell Cell.
	 * @return string
	 */
	private static function label( $cell ) {
		return trim( (string) self::scalar( $cell ) );
	}

	/**
	 * A data cell: a number when it reads as one, null when blank, otherwise the text as typed.
	 *
	 * @param mixed $cell Cell.
	 * @return int|float|string|null
	 */
	private static function cell( $cell ) {
		$text = trim( (string) self::scalar( $cell ) );
		if ( '' === $text ) {
			return null;
		}

		return is_numeric( $text ) ? 0 + $text : $text;
	}

	/**
	 * Reduce a decoded JSON value to a scalar.
	 *
	 * @param mixed $cell Cell.
	 * @return string|int|float
	 */
	private static function scalar( $cell ) {
		if ( is_bool( $cell ) ) {
			return $cell ? '1' : '';
		}

		return is_scalar( $cell ) ? $cell : '';
	}

	/**
	 * Map the 1.x configuration to v2 settings (all groups, before dropping defaults).
	 *
	 * @param string $type       v2 type.
	 * @param array  $groups     1.x groups, merged with their defaults.
	 * @param string $post_title Chart post title.
	 * @return array
	 */
	private static function settings( $type, array $groups, $post_title ) {
		$graph = $groups['graph'];
		$meta  = $groups['meta'];
		$pie   = in_array( $type, self::PIE_TYPES, true );

		$settings = array(
			'title'    => self::title( self::text( $meta['caption'] ), self::text( $meta['subcaption'] ), $post_title ),
			'size'     => array(
				'width'      => self::dimension( $groups['dimension']['width'] ),
				'height'     => self::dimension( $groups['dimension']['height'] ),
				'responsive' => self::flag( $graph['responsive'] ),
			),
			'palette'  => array(
				// "Default", and the never-saved fallback "Brink" (not a uvCharts palette), draw in the
				// uvCharts Default colours.
				'colors' => is_string( $graph['palette'] ) && isset( self::PALETTES[ $graph['palette'] ] ) ? self::PALETTES[ $graph['palette'] ] : self::DEFAULT_COLORS,
			),
			'legend'   => array(
				// uvCharts never drew a legend for waterfall charts.
				'show'     => 'waterfall' !== $type && self::flag( $groups['legend']['showlegends'] ),
				'position' => 'right' === $groups['legend']['position'] ? 'right' : 'bottom',
			),
			'tooltip'  => array(
				'show' => self::flag( $groups['tooltip']['show'] ),
			),
			'labels'   => array(
				'show'      => self::flag( $groups['label']['showlabel'] ),
				'precision' => min( max( (int) $groups['label']['precision'], 0 ), 10 ),
				// Spaces are part of the unit, e.g. " kg".
				'prefix'    => self::text( $groups['label']['prefix'], false ),
				'suffix'    => self::text( $groups['label']['suffix'], false ),
			),
			'download' => array(
				'enabled' => self::flag( $meta['isDownloadable'] ),
				'label'   => self::text( $meta['downloadLabel'] ),
			),
		);

		// Style values are recorded only when the chart's author changed them from the 1.x
		// default (see LOOK).
		$family = trim( (string) self::scalar( $groups['caption']['fontfamily'] ) );
		if ( isset( self::FONT_FAMILIES[ $family ] ) ) {
			$settings['font']['family'] = self::FONT_FAMILIES[ $family ];
		}
		$size = (int) self::scalar( $groups['label']['fontsize'] );
		if ( $size > 0 && 11 !== $size ) {
			$settings['font']['size'] = min( max( $size, 8 ), 32 );
		}

		$area = in_array( $type, array( 'area', 'stacked-area', 'percent-area' ), true );
		if ( 'line' === $type || $area ) {
			$curve = strtolower( trim( (string) self::scalar( $groups[ $area ? 'area' : 'line' ]['interpolation'] ) ) );
			if ( '' !== $curve && 'linear' !== $curve ) {
				$settings['line']['smooth'] = true;
			}
		}
		if ( $area ) {
			$opacity = self::fraction( $groups['area']['opacity'], 0.2 );
			if ( null !== $opacity ) {
				$settings['area']['opacity'] = min( max( $opacity, 0 ), 1 );
			}
		}
		if ( 'donut' === $type ) {
			$hole = self::fraction( $groups['donut']['factor'], 0.4 );
			if ( null !== $hole ) {
				$settings['donut']['hole'] = min( max( $hole, 0.1 ), 0.9 );
			}
		}

		$background = is_string( $graph['bgcolor'] ) ? sanitize_hex_color( trim( $graph['bgcolor'] ) ) : null;
		if ( $background ) {
			$settings['frame'] = array( 'background' => $background );
		}

		if ( ! $pie ) {
			$horizontal = 'horizontal' === strtolower( trim( (string) self::scalar( $graph['orientation'] ) ) );
			$hlabel     = self::text( $meta['hlabel'] );
			$vlabel     = self::text( $meta['vlabel'] );

			$settings['layout'] = array( 'orientation' => $horizontal ? 'horizontal' : 'vertical' );
			// 1.x labelled the axes by position; v2 names the category (x) and value (y) axes.
			$settings['axis'] = array(
				'xLabel' => $horizontal ? $vlabel : $hlabel,
				'yLabel' => $horizontal ? $hlabel : $vlabel,
			);
		}

		return $settings;
	}

	/**
	 * Title settings from the 1.x caption, which 1.x drew instead of the post title.
	 *
	 * @param string $caption    Caption.
	 * @param string $subcaption Sub caption.
	 * @param string $post_title Chart post title.
	 * @return array
	 */
	private static function title( $caption, $subcaption, $post_title ) {
		if ( '' === $caption ) {
			// No caption: 1.x drew no title, or only the sub caption.
			return '' === $subcaption
				? array( 'show' => false )
				: array( 'text' => $subcaption );
		}

		return array(
			'text'    => $caption === $post_title ? '' : $caption,
			'subtext' => $subcaption,
		);
	}

	/**
	 * The settings 1.x charts are drawn on until they are migrated: the 2.0 defaults with the 1.x
	 * look (LOOK) on top. Site-wide settings don't apply.
	 *
	 * @return array
	 */
	public static function look_settings() {
		return SettingsResolver::merge( Defaults::get(), self::LOOK );
	}

	/**
	 * A converted chart's own settings: what differs from the 1.x look.
	 *
	 * @param string $type       v2 type.
	 * @param array  $groups     1.x groups, merged with their defaults.
	 * @param string $post_title Chart post title.
	 * @return array
	 */
	private static function own_settings( $type, array $groups, $post_title ) {
		$settings = self::settings( $type, $groups, $post_title );
		$own      = self::overrides( $settings, self::look_settings() );

		// A font picked in 1.x stays the chart's own choice even where it maps to the 1.x default
		// family, so it doesn't change to the site font after migration.
		if ( isset( $settings['font']['family'] ) ) {
			$own['font']['family'] = $settings['font']['family'];
		}

		return $own;
	}

	/**
	 * Values that differ from a base, by group.
	 *
	 * @param array $settings Settings by group.
	 * @param array $defaults Base settings by group.
	 * @return array
	 */
	private static function overrides( array $settings, array $defaults ) {
		$result = array();
		foreach ( $settings as $group => $values ) {
			foreach ( $values as $key => $value ) {
				$default = isset( $defaults[ $group ][ $key ] ) ? $defaults[ $group ][ $key ] : null;
				if ( $value !== $default ) {
					$result[ $group ][ $key ] = $value;
				}
			}
		}

		return $result;
	}

	/**
	 * Text saved by the 1.x settings form, which stored it HTML-encoded.
	 *
	 * @param mixed $value Saved value.
	 * @param bool  $trim  Whether to trim surrounding whitespace.
	 * @return string Plain text.
	 */
	private static function text( $value, $trim = true ) {
		$text = html_entity_decode( (string) self::scalar( $value ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );

		return $trim ? trim( $text ) : $text;
	}

	/**
	 * A 1.x yes/no value ('1', 1, true, 'true').
	 *
	 * @param mixed $value Saved value.
	 * @return bool
	 */
	private static function flag( $value ) {
		return in_array( strtolower( trim( (string) self::scalar( $value ) ) ), array( '1', 'true', 'yes', 'on' ), true );
	}

	/**
	 * A width or height in pixels; 1.x fell back to 400.
	 *
	 * @param mixed $value Saved value.
	 * @return int
	 */
	private static function dimension( $value ) {
		$pixels = absint( self::scalar( $value ) );

		return $pixels > 0 ? $pixels : 400;
	}
}
