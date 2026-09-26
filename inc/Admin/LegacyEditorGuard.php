<?php
/**
 * Keeps the 1.x chart editor away from charts stored in the v2 format.
 *
 * @package EasyCharts
 */

namespace EasyCharts\Admin;

use EasyCharts\Chart\ChartRepository;
use EasyCharts\Frontend\ChartRenderer;
use EasyCharts\PostType\ChartPostType;
use EasyCharts\Service;
use WP_Post;

/**
 * Temporary bridge until the block-based chart editor replaces the 1.x metaboxes.
 *
 * The 1.x editor only reads and writes 1.x meta. For a chart that has a stored v2 document it
 * would show its built-in demo data and save edits that the front end ignores. So for those
 * charts this guard:
 *
 * - replaces the 1.x Data, Preview and Configuration metaboxes with a notice and a preview drawn
 *   by the new renderer,
 * - dequeues the 1.x editor scripts on that screen,
 * - refuses the 1.x AJAX data save.
 *
 * Charts without a stored v2 document keep the full 1.x editor.
 */
final class LegacyEditorGuard implements Service {

	const PREVIEW_METABOX = 'easy_charts_v2_preview';

	const LEGACY_METABOXES = array(
		'easy_charts_data_metabox',
		'easy_charts_preview_metabox',
		'easy_charts_configuration_metabox',
	);

	const LEGACY_SCRIPTS = array(
		'easy-charts-admin-js',
		'jsuites-js',
		'jspreadsheet-js',
		'd3-js',
		'filesaver-js',
		'canvg-js',
		'canvas-toblob-js',
		'uvhcharts-js',
		'responsive-tabs-js',
		'bootstrap-touchspin-js',
	);

	/**
	 * Chart storage.
	 *
	 * @var ChartRepository
	 */
	private $charts;

	/**
	 * Chart output.
	 *
	 * @var ChartRenderer
	 */
	private $renderer;

	/**
	 * Constructor.
	 *
	 * @param ChartRepository $charts   Chart storage.
	 * @param ChartRenderer   $renderer Chart output.
	 */
	public function __construct( ChartRepository $charts, ChartRenderer $renderer ) {
		$this->charts   = $charts;
		$this->renderer = $renderer;
	}

	/**
	 * Attach hooks.
	 *
	 * @return void
	 */
	public function register() {
		// Runs after the generic add_meta_boxes action the 1.x admin uses.
		add_action( 'add_meta_boxes_' . ChartPostType::POST_TYPE, array( $this, 'replace_metaboxes' ), 20 );
		add_action( 'admin_enqueue_scripts', array( $this, 'dequeue_legacy_scripts' ), 20 );
		// Before the 1.x handler (default priority).
		add_action( 'wp_ajax_easy_charts_save_chart_data', array( $this, 'refuse_legacy_save' ), 1 );
	}

	/**
	 * Whether a chart has a stored v2 document. Charts only converted from 1.x on the fly don't.
	 *
	 * @param int $chart_id Chart post ID.
	 * @return bool
	 */
	public function is_v2_chart( $chart_id ) {
		return null !== $this->charts->find_stored( (int) $chart_id );
	}

	/**
	 * Swap the 1.x editing metaboxes for a notice and a v2 preview.
	 *
	 * @param WP_Post $post Chart being edited.
	 * @return void
	 */
	public function replace_metaboxes( $post ) {
		if ( ! $post instanceof WP_Post || ! $this->is_v2_chart( $post->ID ) ) {
			return;
		}

		foreach ( self::LEGACY_METABOXES as $id ) {
			remove_meta_box( $id, ChartPostType::POST_TYPE, 'advanced' );
		}

		add_meta_box(
			self::PREVIEW_METABOX,
			__( 'Chart', 'easy-charts' ),
			array( $this, 'render_preview' ),
			ChartPostType::POST_TYPE,
			'normal',
			'high'
		);
	}

	/**
	 * Metabox: notice plus the chart drawn by the new renderer.
	 *
	 * @param WP_Post $post Chart being edited.
	 * @return void
	 */
	public function render_preview( $post ) {
		printf(
			'<div class="notice notice-info inline"><p>%s</p></div>',
			esc_html__( 'This chart uses the Easy Charts 2.0 format. Its data and settings cannot be edited on this screen; the new chart editor will replace it. The preview below is what visitors see.', 'easy-charts' )
		);

		echo $this->renderer->render( $post->ID ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ChartRenderer escapes its output.
	}

	/**
	 * Drop the 1.x editor scripts when editing a v2 chart; they expect the removed metaboxes.
	 *
	 * @return void
	 */
	public function dequeue_legacy_scripts() {
		$post = get_post();
		if ( ! $post || ChartPostType::POST_TYPE !== $post->post_type || ! $this->is_v2_chart( $post->ID ) ) {
			return;
		}

		foreach ( self::LEGACY_SCRIPTS as $handle ) {
			wp_dequeue_script( $handle );
		}
	}

	/**
	 * Refuse the 1.x AJAX data save for v2 charts, so their 1.x meta cannot drift from what is shown.
	 *
	 * @return void
	 */
	public function refuse_legacy_save() {
		$chart_id = isset( $_POST['chart_id'] ) ? absint( wp_unslash( $_POST['chart_id'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Read-only check; the 1.x handler verifies the nonce.

		if ( $chart_id && $this->is_v2_chart( $chart_id ) ) {
			wp_send_json_error(
				array( 'message' => __( 'This chart uses the Easy Charts 2.0 format and cannot be edited with the 1.x editor.', 'easy-charts' ) ),
				409
			);
		}
	}
}
