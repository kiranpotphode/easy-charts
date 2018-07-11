<?php
/**
 * Markup file for metabox
 *
 * HTML markup for preview metabox.
 *
 * @link       http://kiranpotphode.com
 * @since      1.0.0
 *
 * @package    Easy_Charts
 * @subpackage Easy_Charts/admin/partials
 */

	global $post;
	$plugin = new Easy_Charts();
?>

<div id="easy-charts-preview-metabox-wrap">
	<div id="easy-chart-preview-box">
		<?php
		$chart_data = get_post_meta( $post->ID, '_easy_charts_chart_data', true );
		$chart_data = json_decode( $chart_data );

		if ( null === $chart_data ) {
				esc_html_e( 'Please click "Update chart data" and save chart for preview.', 'easy-charts' );
		}

		$translation_array = array(
			'chart_data'    => $chart_data,
			'chart_id'      => $post->ID,
			'ec_ajax_nonce' => wp_create_nonce( 'ec-ajax-nonce' ),
		);

		wp_localize_script( 'easy-charts-admin-js', 'ec_chart', $translation_array );
		wp_enqueue_script( 'easy-charts-admin-js' );

		echo $plugin->ec_render_chart( $post->ID );
		?>
	</div>
</div>
