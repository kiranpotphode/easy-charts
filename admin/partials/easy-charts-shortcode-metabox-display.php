<?php
/**
 * Markup file for metabox
 *
 * HTML markup for shortcode metabox.
 *
 * @link       http://kiranpotphode.com
 * @since      1.0.0
 *
 * @package    Easy_Charts
 * @subpackage Easy_Charts/admin/partials
 */

global $post;
?>

<div id="easy-charts-shortcode-metabox-wrap">
	<div id="easy-chart-shortcode-box">
		<input type="text" name="ec-shortcode" value="[easy_chart chart_id='<?php echo esc_attr( $post->ID ); ?>']" readonly>
	</div>
</div>
