<?php
/**
 * Chart document storage.
 *
 * @package EasyCharts
 */

namespace EasyCharts\Chart;

use EasyCharts\Migration\V1Converter;
use EasyCharts\PostType\ChartPostType;
use WP_Error;

/**
 * Reads and writes v2 chart documents on `easy_charts` posts.
 *
 * Only the `_easy_charts_chart` meta key is ever written. 1.x meta keys are never modified or
 * deleted, so a site can roll back to 1.x with its original charts intact. Charts without a stored
 * v2 document are converted from their 1.x meta each time they are read, and only get a stored
 * document when saved in the 2.0 editor.
 */
final class ChartRepository {

	/**
	 * 1.x chart converter.
	 *
	 * @var V1Converter
	 */
	private $converter;

	/**
	 * Constructor.
	 *
	 * @param V1Converter|null $converter 1.x chart converter.
	 */
	public function __construct( ?V1Converter $converter = null ) {
		$this->converter = $converter ? $converter : new V1Converter();
	}

	/**
	 * Get a chart's v2 document: the stored one, or else one converted from 1.x meta (not stored).
	 *
	 * @param int $chart_id Chart post ID.
	 * @return array|null The document, or null if the post is not a chart or has no usable data.
	 */
	public function find( $chart_id ) {
		$doc = $this->find_stored( $chart_id );
		if ( null === $doc && $this->is_chart( $chart_id ) ) {
			$doc = $this->converter->convert( (int) $chart_id );
		}

		return $doc;
	}

	/**
	 * Get a chart's stored v2 document only, ignoring 1.x meta.
	 *
	 * @param int $chart_id Chart post ID.
	 * @return array|null The document, or null if the post is not a chart or has no valid stored document.
	 */
	public function find_stored( $chart_id ) {
		if ( ! $this->is_chart( $chart_id ) ) {
			return null;
		}

		$stored = get_post_meta( $chart_id, ChartMeta::KEY, true );
		if ( ! is_array( $stored ) ) {
			return null;
		}

		$doc = Schema::sanitize( $stored );

		return is_wp_error( $doc ) ? null : $doc;
	}

	/**
	 * Validate and store a chart's v2 document.
	 *
	 * @param int   $chart_id Chart post ID.
	 * @param array $doc      Chart document.
	 * @return true|WP_Error
	 */
	public function save( $chart_id, array $doc ) {
		if ( ! $this->is_chart( $chart_id ) ) {
			return new WP_Error( 'easy_charts_not_a_chart', __( 'The post is not a chart.', 'easy-charts' ) );
		}

		$doc = Schema::sanitize( $doc );
		if ( is_wp_error( $doc ) ) {
			return $doc;
		}

		// update_post_meta() unslashes its input, so slash it to keep backslashes in labels.
		$updated = update_post_meta( $chart_id, ChartMeta::KEY, wp_slash( $doc ) );

		// update_post_meta() also returns false when the stored value is already identical.
		if ( false === $updated && get_post_meta( $chart_id, ChartMeta::KEY, true ) !== $doc ) {
			return new WP_Error( 'easy_charts_save_failed', __( 'The chart could not be saved.', 'easy-charts' ) );
		}

		return true;
	}

	/**
	 * Whether the ID belongs to an `easy_charts` post.
	 *
	 * @param int $chart_id Post ID.
	 * @return bool
	 */
	public function is_chart( $chart_id ) {
		return ChartPostType::POST_TYPE === get_post_type( (int) $chart_id );
	}
}
