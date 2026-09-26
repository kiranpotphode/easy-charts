<?php
/**
 * [easy_chart] shortcode.
 *
 * @package EasyCharts
 */

namespace EasyCharts\Shortcode;

use EasyCharts\Chart\ChartRepository;
use EasyCharts\Frontend\ChartRenderer;
use EasyCharts\Service;

/**
 * Handles `[easy_chart chart_id="N"]`.
 *
 * Every chart renders with ECharts: from its stored v2 document, or from one converted from its
 * 1.x meta. Only a chart whose 1.x data cannot be converted (missing or broken grid) still goes
 * through the unchanged 1.x uvCharts path, until that path is removed before the 2.0 beta.
 */
final class ChartShortcode implements Service {

	const TAG = 'easy_chart';

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
	 * Attach hooks. Priority 20 replaces the 1.x registration made at the default priority.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'init', array( $this, 'register_shortcode' ), 20 );
	}

	/**
	 * Register the shortcode.
	 *
	 * @return void
	 */
	public function register_shortcode() {
		add_shortcode( self::TAG, array( $this, 'render' ) );
	}

	/**
	 * Shortcode callback.
	 *
	 * @param array|string $atts Shortcode attributes.
	 * @return string
	 */
	public function render( $atts ) {
		$parsed   = shortcode_atts( array( 'chart_id' => null ), $atts, self::TAG );
		$chart_id = intval( $parsed['chart_id'] );
		if ( $chart_id <= 0 ) {
			return '';
		}

		// Only the presence of a document decides the path, so a chart the renderer declines
		// (e.g. private) never falls back to the 1.x output.
		if ( null !== $this->charts->find( $chart_id ) ) {
			return $this->renderer->render( $chart_id );
		}

		if ( class_exists( 'Easy_Charts_Public' ) ) {
			return \Easy_Charts_Public::easy_chart_shortcode_callback( $atts );
		}

		return '';
	}
}
