<?php
/**
 * Chart document post meta.
 *
 * @package EasyCharts
 */

namespace EasyCharts\Chart;

use EasyCharts\PostType\ChartPostType;
use EasyCharts\Service;

/**
 * Registers the post meta that holds a chart's v2 document and exposes it in REST.
 *
 * 1.x meta keys (_easy_charts_chart_data, _ec_chart_*) are left unregistered and untouched.
 */
final class ChartMeta implements Service {

	const KEY = '_easy_charts_chart';

	/**
	 * Attach hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'init', array( $this, 'register_meta' ) );
	}

	/**
	 * Register the meta key.
	 *
	 * @return void
	 */
	public function register_meta() {
		register_post_meta(
			ChartPostType::POST_TYPE,
			self::KEY,
			array(
				'type'              => 'object',
				'single'            => true,
				'description'       => __( 'Chart document (data, type and settings).', 'easy-charts' ),
				'sanitize_callback' => array( $this, 'sanitize' ),
				'auth_callback'     => array( $this, 'can_edit' ),
				'show_in_rest'      => array(
					// Only when editing: the document is the chart's whole data set.
					'schema' => array_merge( Schema::get(), array( 'context' => array( 'edit' ) ) ),
				),
			)
		);
	}

	/**
	 * Sanitize callback: normalizes valid documents.
	 *
	 * REST requests and ChartRepository::save() validate before saving, so an invalid value only arrives
	 * here from a direct update_post_meta() call. It is passed through unchanged rather than blanked, so
	 * such a call can never wipe a chart; readers (ChartRepository::find()) validate and ignore it.
	 *
	 * @param mixed $value Meta value being saved.
	 * @return mixed
	 */
	public function sanitize( $value ) {
		$doc = Schema::sanitize( $value );

		return is_wp_error( $doc ) ? $value : $doc;
	}

	/**
	 * Only users who can edit the chart may change its document.
	 *
	 * @param bool   $allowed  Whether the user can edit the meta (ignored).
	 * @param string $meta_key Meta key.
	 * @param int    $post_id  Chart post ID.
	 * @return bool
	 */
	public function can_edit( $allowed, $meta_key, $post_id ) {
		return current_user_can( 'edit_post', $post_id );
	}
}
