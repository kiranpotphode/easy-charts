<?php
/**
 * Migrating 1.x charts to 2.0 styling.
 *
 * @package EasyCharts
 */

namespace EasyCharts\Migration;

use EasyCharts\Chart\ChartRepository;
use EasyCharts\PostType\ChartPostType;
use WP_Error;
use WP_Query;

/**
 * A converted 1.x chart keeps its 1.x look (`source.v1Look`) until it is migrated. Migrating stores
 * the chart in the 2.0 format without that flag, so the settings it didn't change in 1.x follow the
 * 2.0 and site-wide settings. Only `_easy_charts_chart` is written: the 1.x meta stays, so going
 * back to 1.x still shows the original chart.
 */
final class Migrator {

	/**
	 * Charts processed per batch.
	 */
	const BATCH = 20;

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
	 * Whether a chart still keeps its 1.x look.
	 *
	 * @param int $chart_id Chart post ID.
	 * @return bool
	 */
	public function keeps_1x_look( $chart_id ) {
		$doc = $this->charts->find( (int) $chart_id );

		return null !== $doc && ! empty( $doc['source']['v1Look'] );
	}

	/**
	 * Migrate one chart.
	 *
	 * @param int $chart_id Chart post ID.
	 * @return bool|WP_Error True when migrated, false when there was nothing to migrate.
	 */
	public function migrate( $chart_id ) {
		$doc = $this->charts->find( (int) $chart_id );
		if ( null === $doc || empty( $doc['source']['v1Look'] ) ) {
			return false;
		}

		unset( $doc['source']['v1Look'] );
		$saved = $this->charts->save( (int) $chart_id, $doc );

		return is_wp_error( $saved ) ? $saved : true;
	}

	/**
	 * Migrate one batch of the charts made with 1.x.
	 *
	 * Batches page through every chart with 1.x data (a set that migrating doesn't change), so
	 * calling pages 1 to `pages` migrates them all.
	 *
	 * @param int $page Batch number, from 1.
	 * @return array{migrated: int, failed: int, pages: int}
	 */
	public function migrate_batch( $page ) {
		$query  = $this->query( max( 1, (int) $page ), self::BATCH );
		$result = array(
			'migrated' => 0,
			'failed'   => 0,
			'pages'    => (int) $query->max_num_pages,
		);

		foreach ( $query->posts as $chart_id ) {
			$migrated = $this->migrate( $chart_id );
			if ( is_wp_error( $migrated ) ) {
				++$result['failed'];
			} elseif ( $migrated ) {
				++$result['migrated'];
			}
		}

		return $result;
	}

	/**
	 * How many charts still keep their 1.x look.
	 *
	 * @return int
	 */
	public function count_pending() {
		return count( array_filter( $this->query( 1, -1 )->posts, array( $this, 'keeps_1x_look' ) ) );
	}

	/**
	 * Charts that have 1.x data, oldest first.
	 *
	 * @param int $page     Page number.
	 * @param int $per_page Charts per page, or -1 for all.
	 * @return WP_Query Query whose posts are IDs.
	 */
	private function query( $page, $per_page ) {
		return new WP_Query(
			array(
				'post_type'              => ChartPostType::POST_TYPE,
				'post_status'            => 'any',
				'fields'                 => 'ids',
				'orderby'                => 'ID',
				'order'                  => 'ASC',
				'posts_per_page'         => $per_page,
				'paged'                  => $page,
				'meta_key'               => V1Converter::DATA_KEY, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Admin-only, on demand.
				'update_post_term_cache' => false,
			)
		);
	}
}
