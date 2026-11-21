<?php
/**
 * Columns on the chart list screen.
 *
 * @package EasyCharts
 */

namespace EasyCharts\Admin;

use EasyCharts\Chart\ChartRepository;
use EasyCharts\Chart\Schema;
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
		$labels = Schema::type_labels();
		if ( null === $doc ) {
			echo '<span aria-hidden="true">&#8212;</span><span class="screen-reader-text">' . esc_html__( 'No data', 'easy-charts' ) . '</span>';
			return;
		}

		echo esc_html( isset( $labels[ $doc['type'] ] ) ? $labels[ $doc['type'] ] : $doc['type'] );
		if ( ! empty( $doc['source']['v1Look'] ) ) {
			echo ' <em>' . esc_html__( '(1.x look)', 'easy-charts' ) . '</em>';
		}
	}
}
