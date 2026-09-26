<?php
/**
 * Built-in chart settings.
 *
 * @package EasyCharts
 */

namespace EasyCharts\Settings;

/**
 * The bottom settings layer: values every chart starts from.
 *
 * This is the starting set of groups; it grows with the ECharts option builder.
 */
final class Defaults {

	/**
	 * Get the default settings.
	 *
	 * @return array
	 */
	public static function get() {
		$defaults = array(
			'title'    => array(
				'show'    => true,
				// Empty: use the chart's post title.
				'text'    => '',
				'subtext' => '',
			),
			'size'     => array(
				'width'      => 400,
				'height'     => 400,
				'responsive' => true,
			),
			'palette'  => array(
				// Empty list: use the chart library's default colours.
				'colors' => array(),
			),
			'layout'   => array(
				// Bar charts: 'vertical' columns or 'horizontal' bars.
				'orientation' => 'vertical',
			),
			'frame'    => array(
				'background' => '#ffffff',
			),
			'font'     => array(
				// 'site' (the font of the text around the chart), 'sans-serif', 'serif' or 'monospace'.
				'family' => 'site',
				// Base size in px; the title is drawn larger.
				'size'   => 12,
			),
			'line'     => array(
				// Curved lines, for line and area charts.
				'smooth' => false,
			),
			'area'     => array(
				// Fill opacity of area charts, 0 to 1.
				'opacity' => 0.4,
			),
			'donut'    => array(
				// Hole size as a fraction of the outer radius, 0.1 to 0.9.
				'hole' => 0.6,
			),
			'axis'     => array(
				'xLabel' => '',
				'yLabel' => '',
			),
			'legend'   => array(
				'show'     => true,
				'position' => 'bottom',
			),
			'tooltip'  => array(
				'show' => true,
			),
			'labels'   => array(
				'show'      => true,
				'precision' => 2,
				'prefix'    => '',
				'suffix'    => '',
			),
			'download' => array(
				'enabled' => false,
				'label'   => __( 'Download', 'easy-charts' ),
			),
		);

		/**
		 * Filters the built-in chart settings, the lowest settings layer.
		 *
		 * @param array $defaults Default settings, grouped.
		 */
		return apply_filters( 'easy_charts_default_settings', $defaults );
	}
}
