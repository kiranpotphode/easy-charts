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
 * Charts without a stored v2 document keep the full 1.x editor, plus a preview of how 2.0 draws
 * them (converted from their 1.x meta) right after the 1.x preview, for comparison.
 */
final class LegacyEditorGuard implements Service {

	const PREVIEW_METABOX = 'easy_charts_v2_preview';

	const COMPARISON_METABOX = 'easy_charts_v2_comparison';

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
		add_filter( 'get_user_option_meta-box-order_' . ChartPostType::POST_TYPE, array( $this, 'place_comparison_in_saved_order' ) );
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
		if ( ! $post instanceof WP_Post ) {
			return;
		}
		if ( ! $this->is_v2_chart( $post->ID ) ) {
			$this->add_comparison( $post );
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
	 * For a 1.x chart that converts, add the 2.0 preview right after the 1.x preview.
	 *
	 * @param WP_Post $post Chart being edited.
	 * @return void
	 */
	private function add_comparison( WP_Post $post ) {
		if ( null === $this->charts->find( $post->ID ) ) {
			return;
		}

		add_meta_box(
			self::COMPARISON_METABOX,
			__( 'Easy Charts 2.0 preview', 'easy-charts' ),
			array( $this, 'render_comparison' ),
			ChartPostType::POST_TYPE,
			'advanced'
		);

		// Move it from the end of the list to just after the 1.x preview.
		global $wp_meta_boxes;
		$boxes = &$wp_meta_boxes[ ChartPostType::POST_TYPE ]['advanced']['default'];
		if ( ! isset( $boxes['easy_charts_preview_metabox'] ) ) {
			return;
		}
		$comparison = array( self::COMPARISON_METABOX => $boxes[ self::COMPARISON_METABOX ] );
		unset( $boxes[ self::COMPARISON_METABOX ] );
		$at    = array_search( 'easy_charts_preview_metabox', array_keys( $boxes ), true ) + 1;
		$boxes = array_slice( $boxes, 0, $at, true ) + $comparison + array_slice( $boxes, $at, null, true );
	}

	/**
	 * Keep the 2.0 preview after the 1.x preview when the user has a saved metabox order, which
	 * would otherwise put the new box last. Once the user moves the box, their order wins.
	 *
	 * @param mixed $order Saved order: comma-separated box IDs by context.
	 * @return mixed
	 */
	public function place_comparison_in_saved_order( $order ) {
		if ( ! is_array( $order ) || false !== strpos( implode( ',', $order ), self::COMPARISON_METABOX ) ) {
			return $order;
		}

		foreach ( $order as $context => $ids ) {
			$list = explode( ',', (string) $ids );
			$at   = array_search( 'easy_charts_preview_metabox', $list, true );
			if ( false !== $at ) {
				array_splice( $list, $at + 1, 0, self::COMPARISON_METABOX );
				$order[ $context ] = implode( ',', $list );
				break;
			}
		}

		return $order;
	}

	/**
	 * Metabox: the 1.x chart as 2.0 will draw it.
	 *
	 * @param WP_Post $post Chart being edited.
	 * @return void
	 */
	public function render_comparison( $post ) {
		printf(
			'<p class="description">%s</p>',
			esc_html__( 'How Easy Charts 2.0 will draw this chart, converted from its 1.x data and settings. Nothing is saved in the 2.0 format. Reload the page after saving to update this preview.', 'easy-charts' )
		);

		echo $this->renderer->render( $post->ID ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ChartRenderer escapes its output.
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
