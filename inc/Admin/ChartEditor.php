<?php
/**
 * Chart post edit screen extras.
 *
 * @package EasyCharts
 */

namespace EasyCharts\Admin;

use EasyCharts\Assets;
use EasyCharts\PostType\ChartPostType;
use EasyCharts\Service;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Chart post edit screen: makes sure every chart post opens with its chart block, and loads the
 * "Use this chart" panel (shortcode).
 *
 * The chart itself is edited by the chart block, which binds to the post's `_easy_charts_chart`
 * meta. Chart post content holds nothing but that block; the chart lives in meta.
 */
final class ChartEditor implements Service {

	const SCRIPT = 'easy-charts-chart-post';

	/**
	 * Attach hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue' ) );
		add_filter( 'rest_prepare_' . ChartPostType::POST_TYPE, array( $this, 'add_chart_block' ), 10, 3 );
	}

	/**
	 * Give the editor a chart block for chart posts whose content has none.
	 *
	 * The block editor applies a post type's template to new posts only. Charts made with 1.x (and
	 * any made before this editor) have empty content, so they would open with nothing to edit.
	 * Serving the block as their content lets them open like new ones, without marking them as
	 * changed.
	 *
	 * @param WP_REST_Response $response Post response.
	 * @param \WP_Post         $post     Chart post.
	 * @param WP_REST_Request  $request  Request.
	 * @return WP_REST_Response
	 */
	public function add_chart_block( $response, $post, $request ) {
		$data = $response->get_data();
		if ( 'edit' !== $request['context'] || ! isset( $data['content']['raw'] ) || has_block( 'easy-charts/chart', $data['content']['raw'] ) ) {
			return $response;
		}

		$data['content']['raw'] = '<!-- wp:easy-charts/chart /-->';
		$response->set_data( $data );

		return $response;
	}

	/**
	 * Load the script on chart posts only.
	 *
	 * @return void
	 */
	public function enqueue() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		$asset  = Assets::get( 'chart-post' );
		if ( ! $screen || ChartPostType::POST_TYPE !== $screen->post_type || null === $asset ) {
			return;
		}

		wp_enqueue_script( self::SCRIPT, Assets::url( 'chart-post.js' ), $asset['dependencies'], $asset['version'], true );
		wp_set_script_translations( self::SCRIPT, 'easy-charts', EASY_CHARTS_DIR . 'languages' );
	}
}
