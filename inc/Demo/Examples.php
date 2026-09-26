<?php
/**
 * Example charts for development and the Playground demo.
 *
 * @package EasyCharts
 */

namespace EasyCharts\Demo;

use EasyCharts\Chart\ChartMeta;
use EasyCharts\Chart\ChartRepository;
use EasyCharts\Migration\V1Converter;
use EasyCharts\PostType\ChartPostType;
use RuntimeException;
use WP_Error;

/**
 * Creates one published example chart per chart type and a page showing them all, used by the
 * local-only `wp easy-charts seed-examples` command and the Playground blueprint.
 *
 * With `$v1`, the charts are stored in the 1.x format instead (meta exactly as the 1.x editor
 * saves it), to check the conversion of 1.x charts.
 *
 * Re-running updates the existing examples instead of duplicating them (tracked by the
 * `_easy_charts_example` meta key).
 */
final class Examples {

	const META = '_easy_charts_example';

	/**
	 * Chart storage.
	 *
	 * @var ChartRepository
	 */
	private $charts;

	/**
	 * Progress messages of the last run.
	 *
	 * @var string[]
	 */
	private $log = array();

	/**
	 * Constructor.
	 *
	 * @param ChartRepository|null $charts Chart storage.
	 */
	public function __construct( ?ChartRepository $charts = null ) {
		$this->charts = $charts ? $charts : new ChartRepository();
	}

	/**
	 * Create or update the example charts and the page that shows them.
	 *
	 * @param bool $v1 Store the charts in the 1.x format (converted when drawn).
	 * @return string[]|WP_Error Progress messages, or the error that stopped the run.
	 */
	public function create( $v1 = false ) {
		$this->log = array();
		try {
			$result = $v1 ? $this->create_v1() : $this->create_v2();
		} catch ( RuntimeException $e ) {
			$result = new WP_Error( 'easy_charts_example', $e->getMessage() );
		}

		return is_wp_error( $result ) ? $result : $this->log;
	}

	/**
	 * Create or update the 2.0 examples and their page.
	 *
	 * @return true|WP_Error
	 */
	private function create_v2() {
		$sections = '';

		foreach ( self::examples() as $type => $example ) {
			$chart_id = $this->upsert(
				$type,
				array(
					'post_type'   => ChartPostType::POST_TYPE,
					'post_status' => 'publish',
					'post_title'  => $example['title'],
				)
			);

			$saved = $this->charts->save(
				$chart_id,
				array(
					'schema'   => 2,
					'type'     => $type,
					'data'     => $example['data'],
					'settings' => isset( $example['settings'] ) ? $example['settings'] : array(),
				)
			);
			if ( is_wp_error( $saved ) ) {
				return new WP_Error( $saved->get_error_code(), sprintf( '%s: %s', $type, $saved->get_error_message() ) );
			}
			$this->clear_legacy_meta( $chart_id );

			$sections   .= self::section( $example['title'], $type, $chart_id );
			$this->log[] = sprintf( 'Chart %d: %s', $chart_id, $type );
		}

		$this->publish_page( 'page', 'Easy Charts examples', $sections );

		return true;
	}

	/**
	 * Create or update the 1.x-format example charts and their page.
	 *
	 * @return true
	 */
	private function create_v1() {
		$sections = '';

		foreach ( self::v1_examples() as $slug => $example ) {
			$chart_id = $this->upsert(
				'v1-' . $slug,
				array(
					'post_type'   => ChartPostType::POST_TYPE,
					'post_status' => 'publish',
					'post_title'  => $example['title'],
				)
			);

			// A 1.x chart has no stored v2 document; this one is ours, so a stale one can go.
			delete_post_meta( $chart_id, ChartMeta::KEY );
			update_post_meta( $chart_id, V1Converter::DATA_KEY, wp_slash( wp_json_encode( $example['data'] ) ) );
			update_post_meta( $chart_id, V1Converter::TYPE_KEY, $slug );
			foreach ( self::v1_groups( $chart_id, $example['config'] ) as $group => $values ) {
				update_post_meta( $chart_id, '_ec_chart_' . $group, $values );
			}

			$sections   .= self::section( $example['title'], $slug, $chart_id );
			$this->log[] = sprintf( 'Chart %d: %s (1.x format) %s', $chart_id, $slug, admin_url( 'post.php?post=' . $chart_id . '&action=edit' ) );
		}

		$this->publish_page( 'v1-page', 'Easy Charts 1.x examples', $sections );

		return true;
	}

	/**
	 * Page content for one chart: a heading and its shortcode.
	 *
	 * @param string $title    Chart title.
	 * @param string $type     Chart type.
	 * @param int    $chart_id Chart ID.
	 * @return string Block markup.
	 */
	private static function section( $title, $type, $chart_id ) {
		return sprintf(
			"<!-- wp:heading -->\n<h2 class=\"wp-block-heading\">%s (%s)</h2>\n<!-- /wp:heading -->\n\n<!-- wp:shortcode -->\n[easy_chart chart_id=\"%d\"]\n<!-- /wp:shortcode -->\n\n",
			esc_html( $title ),
			esc_html( $type ),
			$chart_id
		);
	}

	/**
	 * Create or update an example page and log its URL.
	 *
	 * @param string $key     Example key.
	 * @param string $title   Page title.
	 * @param string $content Page content.
	 * @return void
	 */
	private function publish_page( $key, $title, $content ) {
		$page_id = $this->upsert(
			$key,
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => $title,
				'post_content' => $content,
			)
		);

		$this->log[] = sprintf( 'Example page: %s', get_permalink( $page_id ) );
	}

	/**
	 * All 17 1.x configuration groups as the 1.x editor saves them: its form defaults with the
	 * given changes, text HTML-encoded by FILTER_SANITIZE_SPECIAL_CHARS.
	 *
	 * @param int   $chart_id Chart ID.
	 * @param array $changes  Changed values by group and key (plain text).
	 * @return array
	 */
	private static function v1_groups( $chart_id, array $changes ) {
		$font   = array(
			'fontfamily' => 'Arial',
			'fontsize'   => '11',
			'fontweight' => '700',
		);
		$groups = array(
			'meta'       => array(
				'position'       => '.uv-div-' . $chart_id,
				'caption'        => '',
				'subcaption'     => '',
				'hlabel'         => '',
				'hsublabel'      => '',
				'vlabel'         => '',
				'vsublabel'      => '',
				'isDownloadable' => 0,
				'downloadLabel'  => 'Download',
			),
			'graph'      => array(
				'responsive'  => true,
				// The form has no "Brink" option, so the browser submits the first one.
				'palette'     => 'Default',
				'bgcolor'     => '#ffffff',
				'orientation' => 'Horizontal',
				'opacity'     => 1.0,
			),
			'dimension'  => array(
				'width'  => 400,
				'height' => 400,
			),
			'margin'     => array(
				'top'    => 50,
				'bottom' => 150,
				'left'   => 100,
				'right'  => 100,
			),
			'frame'      => array( 'bgcolor' => '#ffffff' ),
			'axis'       => array(
				'opacity'      => 0.1,
				'ticks'        => 8,
				'subticks'     => 2,
				'padding'      => 5,
				'strokecolor'  => '#000000',
				'minor'        => -10,
				'showticks'    => 1,
				'showsubticks' => 1,
				'showtext'     => 1,
			) + $font,
			'label'      => array(
				'strokecolor' => '#000000',
				'showlabel'   => 1,
				'precision'   => 2,
				'prefix'      => '',
				'suffix'      => '',
			) + $font,
			'legend'     => array(
				'position'      => 'bottom',
				'color'         => '#000000',
				'strokewidth'   => 0.15,
				'textmargin'    => 15,
				'symbolsize'    => 10,
				'inactivecolor' => '#DDD',
				'legendstart'   => 0,
				'legendtype'    => 'categories',
				'showlegends'   => 1,
			) + $font,
			'scale'      => array(
				'type'       => 'linear',
				'ordinality' => 0.2,
			),
			'tooltip'    => array(
				'show'   => 1,
				'format' => '%c [%l] : %v',
			),
			'caption'    => array(
				'textdecoration' => 'none',
				'strokecolor'    => '#000000',
				'cursor'         => 'pointer',
			) + $font,
			'subcaption' => array(
				'textdecoration' => 'none',
				'strokecolor'    => '#000000',
				'cursor'         => 'pointer',
			) + $font,
			'bar'        => array(
				'strokecolor' => 'none',
				'textcolor'   => '#000000',
			) + $font,
			'line'       => array( 'interpolation' => 'linear' ),
			'area'       => array(
				'interpolation' => 'linear',
				'opacity'       => 0.2,
				'offset'        => 'zero',
			),
			'pie'        => array(
				'fontvariant' => 'small-caps',
				'fontfill'    => '#000000',
				'strokecolor' => '#ffffff',
				'strokewidth' => '1',
			) + $font,
			'donut'      => array(
				'fontvariant' => 'small-caps',
				'fontfill'    => '#000000',
				'strokecolor' => '#ffffff',
				'strokewidth' => '1',
				'factor'      => '0.4',
			) + $font,
		);

		foreach ( $changes as $group => $values ) {
			$groups[ $group ] = array_merge( $groups[ $group ], $values );
		}

		foreach ( $groups as $group => $values ) {
			foreach ( $values as $key => $value ) {
				if ( is_string( $value ) ) {
					$groups[ $group ][ $key ] = filter_var( $value, FILTER_SANITIZE_SPECIAL_CHARS );
				}
			}
		}

		return $groups;
	}

	/**
	 * 1.x example charts: data as the 1.x spreadsheet saves it (strings), and the settings changed
	 * from the 1.x form defaults.
	 *
	 * @return array Examples keyed by 1.x type slug.
	 */
	private static function v1_examples() {
		$cars     = array(
			array( '', 'Kia', 'Nissan', 'Toyota', 'Honda' ),
			array( '2008', '10', '11', '12', '13' ),
			array( '2009', '20', '11', '14', '13' ),
			array( '2010', '30', '15', '12', '13' ),
		);
		$vertical = array( 'orientation' => 'Vertical' );

		return array(
			'ec_bar_chart'          => array(
				'title'  => '1.x bar: default horizontal, caption & axis labels',
				'data'   => $cars,
				'config' => array(
					'meta' => array(
						'caption'    => 'Car sales & service',
						'subcaption' => 'Units "sold"',
						'hlabel'     => 'Units',
						'vlabel'     => 'Year',
					),
				),
			),
			'ec_stacked_bar_chart'  => array(
				'title'  => '1.x stacked bar: vertical, Android palette, legend right',
				'data'   => $cars,
				'config' => array(
					'graph'  => array( 'palette' => 'Android' ) + $vertical,
					'legend' => array( 'position' => 'right' ),
					'meta'   => array(
						'hlabel' => 'Year',
						'vlabel' => 'Units',
					),
				),
			),
			'ec_percent_bar_chart'  => array(
				'title'  => '1.x percent bar: vertical, Georgia font',
				'data'   => $cars,
				'config' => array(
					'graph'   => $vertical,
					'label'   => array( 'precision' => 0 ),
					'caption' => array( 'fontfamily' => 'Georgia' ),
				),
			),
			'ec_step_up_bar_chart'  => array(
				'title'  => '1.x step-up bar: vertical',
				'data'   => $cars,
				'config' => array( 'graph' => $vertical ),
			),
			'ec_waterfall_chart'    => array(
				'title'  => '1.x waterfall: vertical, $ prefix, download',
				'data'   => array(
					array( '', 'Change' ),
					array( 'Opening', '120' ),
					array( 'Sales', '45' ),
					array( 'Costs', '-30' ),
					array( 'Tax', '-15' ),
					array( 'Other', '8' ),
				),
				'config' => array(
					'graph' => $vertical,
					'label' => array( 'prefix' => '$' ),
					'meta'  => array(
						'isDownloadable' => 1,
						'downloadLabel'  => 'Save chart',
					),
				),
			),
			'ec_line_chart'         => array(
				'title'  => '1.x line: default horizontal (drawn sideways)',
				'data'   => $cars,
				'config' => array( 'label' => array( 'suffix' => ' units' ) ),
			),
			'ec_area_chart'         => array(
				'title'  => '1.x area: vertical, Soft palette, no labels',
				'data'   => $cars,
				'config' => array(
					'graph' => array( 'palette' => 'Soft' ) + $vertical,
					'label' => array( 'showlabel' => 0 ),
				),
			),
			'ec_stacked_area_chart' => array(
				'title'  => '1.x stacked area: vertical, fixed 500x300, grey background, curved, opacity 0.6',
				'data'   => $cars,
				'config' => array(
					'area'      => array(
						'interpolation' => 'cardinal',
						'opacity'       => '0.6',
					),
					'graph'     => array(
						'responsive' => false,
						'bgcolor'    => '#f4f4f4',
					) + $vertical,
					'dimension' => array(
						'width'  => 500,
						'height' => 300,
					),
				),
			),
			'ec_percent_area_chart' => array(
				'title'  => '1.x percent area: vertical, no legend',
				'data'   => $cars,
				'config' => array(
					'graph'  => $vertical,
					'legend' => array( 'showlegends' => 0 ),
				),
			),
			'ec_pie_chart'          => array(
				'title'  => '1.x pie: Candid palette (first data row)',
				'data'   => $cars,
				'config' => array(
					'graph' => array( 'palette' => 'Candid' ),
					'meta'  => array( 'caption' => 'Sales in 2008' ),
				),
			),
			'ec_donut_chart'        => array(
				'title'  => '1.x donut: no tooltip, % suffix, large hole',
				'data'   => $cars,
				'config' => array(
					'donut'   => array( 'factor' => '0.7' ),
					'tooltip' => array( 'show' => 0 ),
					'label'   => array( 'suffix' => '%' ),
				),
			),
			'ec_polar_area_chart'   => array(
				'title'  => '1.x polar area: legend right',
				'data'   => $cars,
				'config' => array( 'legend' => array( 'position' => 'right' ) ),
			),
		);
	}

	/**
	 * Create a post, or update the one previously created for this example key.
	 *
	 * @param string $key  Example key.
	 * @param array  $post Post fields.
	 * @return int Post ID.
	 * @throws RuntimeException When the post can't be saved.
	 */
	private function upsert( $key, array $post ) {
		$existing = get_posts(
			array(
				'post_type'      => $post['post_type'],
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_key'       => self::META, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Examples only.
				'meta_value'     => $key, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- Examples only.
			)
		);

		if ( $existing ) {
			$post['ID'] = $existing[0];
		}

		$post_id = wp_insert_post( wp_slash( $post ), true );
		if ( is_wp_error( $post_id ) ) {
			throw new RuntimeException( esc_html( $post_id->get_error_message() ) );
		}
		update_post_meta( $post_id, self::META, $key );

		return $post_id;
	}

	/**
	 * Remove 1.x meta from an example chart, e.g. demo data saved by opening it in the 1.x editor.
	 *
	 * Only ever called for charts created here (they carry the example marker), never for
	 * real 1.x charts, whose meta must stay untouched.
	 *
	 * @param int $chart_id Example chart ID.
	 * @return void
	 */
	private function clear_legacy_meta( $chart_id ) {
		if ( '' === get_post_meta( $chart_id, self::META, true ) ) {
			return;
		}

		$groups = array( 'type', 'meta', 'graph', 'dimension', 'margin', 'frame', 'axis', 'label', 'legend', 'scale', 'tooltip', 'caption', 'subcaption', 'bar', 'line', 'area', 'pie', 'donut' );

		delete_post_meta( $chart_id, '_easy_charts_chart_data' );
		foreach ( $groups as $group ) {
			delete_post_meta( $chart_id, '_ec_chart_' . $group );
		}
	}

	/**
	 * Example data for every chart type.
	 *
	 * @return array
	 */
	private static function examples() {
		$months = array( 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun' );

		return array(
			'bar'          => array(
				'title'    => 'Quarterly revenue by region',
				'data'     => array(
					'columns' => array( '', 'North', 'South', 'West' ),
					'rows'    => array(
						array( 'Q1', 120, 95, 70 ),
						array( 'Q2', 135, 102, 88 ),
						array( 'Q3', 128, 110, 94 ),
						array( 'Q4', 150, 121, 105 ),
					),
				),
				'settings' => array(
					'title'    => array( 'subtext' => 'Thousands of dollars' ),
					'labels'   => array( 'prefix' => '$' ),
					'download' => array( 'enabled' => true ),
				),
			),
			'stacked-bar'  => array(
				'title'    => 'Energy mix by year',
				'data'     => array(
					'columns' => array( '', 'Solar', 'Wind', 'Hydro' ),
					'rows'    => array(
						array( '2022', 12, 18, 30 ),
						array( '2023', 17, 21, 29 ),
						array( '2024', 24, 25, 28 ),
					),
				),
				'settings' => array(
					'layout' => array( 'orientation' => 'horizontal' ),
					'labels' => array( 'suffix' => ' TWh' ),
				),
			),
			'percent-bar'  => array(
				'title' => 'Survey responses',
				'data'  => array(
					'columns' => array( '', 'Agree', 'Neutral', 'Disagree' ),
					'rows'    => array(
						array( 'Easy to use', 64, 22, 14 ),
						array( 'Good value', 48, 30, 22 ),
						array( 'Would recommend', 71, 18, 11 ),
					),
				),
			),
			'step-up-bar'  => array(
				'title' => 'Sign-ups by channel, stepping up to each quarter\'s total',
				'data'  => array(
					'columns' => array( '', 'Organic', 'Paid', 'Referral' ),
					'rows'    => array(
						array( 'Q1', 120, 60, 25 ),
						array( 'Q2', 135, 80, 30 ),
						array( 'Q3', 150, 72, 41 ),
					),
				),
			),
			'line'         => array(
				'title'    => 'Average temperature',
				'data'     => array(
					'columns' => array( '', 'London', 'Delhi' ),
					'rows'    => array_map(
						null,
						$months,
						array( 5.2, 5.8, 7.9, 10.3, 13.6, 16.6 ),
						array( 14.3, 17.4, 22.9, 29.1, 32.7, 32.6 )
					),
				),
				'settings' => array(
					'labels' => array(
						'show'      => false,
						'precision' => 1,
						'suffix'    => ' °C',
					),
					'legend' => array( 'position' => 'top' ),
					'axis'   => array( 'yLabel' => 'Temperature' ),
				),
			),
			'area'         => array(
				'title'    => 'Website visitors',
				'data'     => array(
					'columns' => array( '', 'Desktop', 'Mobile' ),
					'rows'    => array_map(
						null,
						$months,
						array( 3200, 3400, 3100, 3600, 3900, 4100 ),
						array( 4100, 4600, 5200, 5600, 6300, 7100 )
					),
				),
				'settings' => array( 'labels' => array( 'show' => false ) ),
			),
			'stacked-area' => array(
				'title'    => 'Support tickets by channel',
				'data'     => array(
					'columns' => array( '', 'Email', 'Chat', 'Phone' ),
					'rows'    => array_map(
						null,
						$months,
						array( 120, 110, 130, 125, 140, 150 ),
						array( 80, 95, 105, 120, 130, 145 ),
						array( 60, 55, 50, 48, 45, 40 )
					),
				),
				'settings' => array( 'labels' => array( 'show' => false ) ),
			),
			'percent-area' => array(
				'title'    => 'Browser share',
				'data'     => array(
					'columns' => array( '', 'Chrome', 'Safari', 'Firefox', 'Other' ),
					'rows'    => array(
						array( '2021', 63, 19, 4, 14 ),
						array( '2022', 65, 19, 3, 13 ),
						array( '2023', 64, 20, 3, 13 ),
						array( '2024', 66, 18, 3, 13 ),
					),
				),
				'settings' => array(
					'labels'  => array( 'show' => false ),
					'palette' => array( 'colors' => array( '#1a73e8', '#34a853', '#fbbc04', '#9aa0a6' ) ),
				),
			),
			'pie'          => array(
				'title'    => 'Budget allocation',
				'data'     => array(
					'columns' => array( '', 'Share' ),
					'rows'    => array(
						array( 'Staff', 45 ),
						array( 'Marketing', 20 ),
						array( 'Operations', 25 ),
						array( 'Other', 10 ),
					),
				),
				'settings' => array( 'labels' => array( 'suffix' => '%' ) ),
			),
			'donut'        => array(
				'title'    => 'Device usage',
				'data'     => array(
					'columns' => array( '', 'Users' ),
					'rows'    => array(
						array( 'Mobile', 5800 ),
						array( 'Desktop', 3400 ),
						array( 'Tablet', 800 ),
					),
				),
				'settings' => array( 'legend' => array( 'position' => 'right' ) ),
			),
			'polar-area'   => array(
				'title' => 'Orders by weekday',
				'data'  => array(
					'columns' => array( '', 'Orders' ),
					'rows'    => array(
						array( 'Mon', 42 ),
						array( 'Tue', 38 ),
						array( 'Wed', 45 ),
						array( 'Thu', 51 ),
						array( 'Fri', 67 ),
						array( 'Sat', 88 ),
						array( 'Sun', 73 ),
					),
				),
			),
			'waterfall'    => array(
				'title'    => 'Profit bridge',
				'data'     => array(
					'columns' => array( '', 'Change' ),
					'rows'    => array(
						array( 'Opening', 120 ),
						array( 'Sales', 45 ),
						array( 'Costs', -30 ),
						array( 'Tax', -15 ),
						array( 'Other', 8 ),
					),
				),
				'settings' => array(
					'labels'   => array( 'prefix' => '$' ),
					'download' => array( 'enabled' => true ),
				),
			),
		);
	}
}
