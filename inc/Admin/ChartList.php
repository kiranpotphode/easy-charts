<?php
/**
 * Columns on the chart list screen.
 *
 * @package EasyCharts
 */

namespace EasyCharts\Admin;

use EasyCharts\Chart\ChartRepository;
use EasyCharts\PostType\ChartPostType;
use EasyCharts\Service;

/**
 * Adds "Type" and "Shortcode" columns to Easy Charts → All Charts.
 */
final class ChartList implements Service {

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
	 * Attach hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_filter( 'manage_' . ChartPostType::POST_TYPE . '_posts_columns', array( $this, 'columns' ) );
		add_action( 'manage_' . ChartPostType::POST_TYPE . '_posts_custom_column', array( $this, 'column' ), 10, 2 );
	}

	/**
	 * Chart type names.
	 *
	 * @return array Labels keyed by type slug.
	 */
	public static function type_labels() {
		return array(
			'bar'          => __( 'Bar', 'easy-charts' ),
			'stacked-bar'  => __( 'Stacked bar', 'easy-charts' ),
			'percent-bar'  => __( 'Percent bar', 'easy-charts' ),
			'step-up-bar'  => __( 'Step-up bar', 'easy-charts' ),
			'waterfall'    => __( 'Waterfall', 'easy-charts' ),
			'line'         => __( 'Line', 'easy-charts' ),
			'area'         => __( 'Area', 'easy-charts' ),
			'stacked-area' => __( 'Stacked area', 'easy-charts' ),
			'percent-area' => __( 'Percent area', 'easy-charts' ),
			'pie'          => __( 'Pie', 'easy-charts' ),
			'donut'        => __( 'Donut', 'easy-charts' ),
			'polar-area'   => __( 'Polar area', 'easy-charts' ),
		);
	}

	/**
	 * Add the columns after the title.
	 *
	 * @param array $columns Column labels by key.
	 * @return array
	 */
	public function columns( $columns ) {
		$result = array();
		foreach ( $columns as $key => $label ) {
			$result[ $key ] = $label;
			if ( 'title' === $key ) {
				$result['easy_charts_type']      = __( 'Type', 'easy-charts' );
				$result['easy_charts_shortcode'] = __( 'Shortcode', 'easy-charts' );
			}
		}

		return $result;
	}

	/**
	 * Print a column cell.
	 *
	 * @param string $column  Column key.
	 * @param int    $post_id Chart ID.
	 * @return void
	 */
	public function column( $column, $post_id ) {
		if ( 'easy_charts_shortcode' === $column ) {
			printf( '<code>[easy_chart chart_id="%d"]</code>', (int) $post_id );
			return;
		}
		if ( 'easy_charts_type' !== $column ) {
			return;
		}

		$doc    = $this->charts->find( (int) $post_id );
		$labels = self::type_labels();
		if ( null === $doc ) {
			echo '<span aria-hidden="true">&#8212;</span><span class="screen-reader-text">' . esc_html__( 'No data', 'easy-charts' ) . '</span>';
			return;
		}

		echo esc_html( isset( $labels[ $doc['type'] ] ) ? $labels[ $doc['type'] ] : $doc['type'] );
		if ( null === $this->charts->find_stored( (int) $post_id ) ) {
			echo ' <em>' . esc_html__( '(1.x)', 'easy-charts' ) . '</em>';
		}
	}
}
