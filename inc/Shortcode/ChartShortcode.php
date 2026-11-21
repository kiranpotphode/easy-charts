<?php
/**
 * [easy_chart] shortcode.
 *
 * @package EasyCharts
 */

namespace EasyCharts\Shortcode;

use EasyCharts\Frontend\ChartRenderer;
use EasyCharts\Service;

/**
 * Handles `[easy_chart chart_id="N"]`.
 *
 * Every chart renders with ECharts: from its stored v2 document, or from one converted from its
 * 1.x meta. The shortcode keeps its 1.x tag and attribute, so existing posts keep working.
 */
final class ChartShortcode implements Service {

	const TAG = 'easy_chart';

	/**
	 * Chart output.
	 *
	 * @var ChartRenderer
	 */
	private $renderer;

	/**
	 * Constructor.
	 *
	 * @param ChartRenderer $renderer Chart output.
	 */
	public function __construct( ChartRenderer $renderer ) {
		$this->renderer = $renderer;
	}

	/**
	 * Attach hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'init', array( $this, 'register_shortcode' ) );
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

		return $this->renderer->render( $chart_id );
	}
}
