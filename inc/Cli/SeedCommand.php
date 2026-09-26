<?php
/**
 * Development command that seeds example charts.
 *
 * @package EasyCharts
 */

namespace EasyCharts\Cli;

use EasyCharts\Chart\ChartRepository;
use EasyCharts\PostType\ChartPostType;
use EasyCharts\Service;
use WP_CLI;

/**
 * `wp easy-charts seed-examples`: creates one published example chart per chart type and a page
 * showing them all. Only registered on local and development sites.
 *
 * Re-running updates the existing examples instead of duplicating them (tracked by the
 * `_easy_charts_example` meta key).
 */
final class SeedCommand implements Service {

	const META = '_easy_charts_example';

	/**
	 * Chart storage.
	 *
	 * @var ChartRepository
	 */
	private $charts;

	/**
	 * Constructor.
	 *
	 * @param ChartRepository $charts Chart storage.
	 */
	public function __construct( ChartRepository $charts ) {
		$this->charts = $charts;
	}

	/**
	 * Whether the command should be available in this environment.
	 *
	 * @return bool
	 */
	public static function is_available() {
		return defined( 'WP_CLI' ) && WP_CLI && in_array( wp_get_environment_type(), array( 'local', 'development' ), true );
	}

	/**
	 * Register the WP-CLI command.
	 *
	 * @return void
	 */
	public function register() {
		WP_CLI::add_command( 'easy-charts seed-examples', array( $this, 'seed' ) );
	}

	/**
	 * Create or update the example charts and the page that shows them.
	 *
	 * ## EXAMPLES
	 *
	 *     wp easy-charts seed-examples
	 *
	 * @return void
	 */
	public function seed() {
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
				WP_CLI::error( sprintf( '%s: %s', $type, $saved->get_error_message() ) );
			}
			$this->clear_legacy_meta( $chart_id );

			$sections .= sprintf(
				"<!-- wp:heading -->\n<h2 class=\"wp-block-heading\">%s (%s)</h2>\n<!-- /wp:heading -->\n\n<!-- wp:shortcode -->\n[easy_chart chart_id=\"%d\"]\n<!-- /wp:shortcode -->\n\n",
				esc_html( $example['title'] ),
				esc_html( $type ),
				$chart_id
			);
			WP_CLI::log( sprintf( 'Chart %d: %s', $chart_id, $type ) );
		}

		$page_id = $this->upsert(
			'page',
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => 'Easy Charts examples',
				'post_content' => $sections,
			)
		);

		WP_CLI::success( sprintf( 'Example page: %s', get_permalink( $page_id ) ) );
	}

	/**
	 * Create a post, or update the one previously created for this example key.
	 *
	 * @param string $key  Example key.
	 * @param array  $post Post fields.
	 * @return int Post ID.
	 */
	private function upsert( $key, array $post ) {
		$existing = get_posts(
			array(
				'post_type'      => $post['post_type'],
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_key'       => self::META, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Dev-only command.
				'meta_value'     => $key, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- Dev-only command.
			)
		);

		if ( $existing ) {
			$post['ID'] = $existing[0];
		}

		$post_id = wp_insert_post( wp_slash( $post ), true );
		if ( is_wp_error( $post_id ) ) {
			WP_CLI::error( $post_id->get_error_message() );
		}
		update_post_meta( $post_id, self::META, $key );

		return $post_id;
	}

	/**
	 * Remove 1.x meta from an example chart, e.g. demo data saved by opening it in the 1.x editor.
	 *
	 * Only ever called for charts this command created (they carry the example marker), never for
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
