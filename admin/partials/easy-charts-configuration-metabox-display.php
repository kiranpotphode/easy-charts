<?php
/**
 * Markup file for metabox
 *
 * HTML markup for configuration matabox.
 *
 * @link       http://kiranpotphode.com
 * @since      1.0.0
 *
 * @package    Easy_Charts
 * @subpackage Easy_Charts/admin/partials
 */

global $post;
$plugin = new Easy_Charts();

$font_family = $plugin->get_font_family_array();
?>

<div id="easy-charts-configuration-metabox-wrap">
	<div id="easy-chart-configuration-box">
		<div id="ec-tabs">
			<div class="resp-tabs-container">
				<div id="ec-tabs-graph" class="ec-tab" data-pws-tab-name="<?php esc_attr_e( 'General', 'easy-charts' ); ?>" data-pws-tab="ec-tabs-graph" data-pws-tab-icon="fa-university">
					<?php
					$ec_chart_type = get_post_meta( $post->ID, '_ec_chart_type', true );
					$plugin->ec_render_field( 'dropdown', 'ec_chart_type', 'Chart Type', $ec_chart_type, array(
						'ec_bar_chart'          => __( 'Bar Chart', 'easy-charts' ),
						'ec_area_chart'         => __( 'Area Chart', 'easy-charts' ),
						'ec_stacked_bar_chart'  => __( 'Stacked Bar Chart', 'easy-charts' ),
						'ec_stacked_area_chart' => __( 'Stacked Area Chart', 'easy-charts' ),
						'ec_percent_bar_chart'  => __( 'Percent Bar Chart', 'easy-charts' ),
						'ec_percent_area_chart' => __( 'Percent Area Chart', 'easy-charts' ),
						'ec_pie_chart'          => __( 'Pie Chart', 'easy-charts' ),
						'ec_donut_chart'        => __( 'Donut Chart', 'easy-charts' ),
						'ec_step_up_bar_chart'  => __( 'Step Up Bar Chart', 'easy-charts' ),
						'ec_waterfall_chart'    => __( 'Waterfall Chart', 'easy-charts' ),
						'ec_line_chart'         => __( 'Line Chart', 'easy-charts' ),
						'ec_polar_area_chart'   => __( 'Polar Area Chart', 'easy-charts' ),
					) );

					$ec_chart_graph = $plugin->ec_get_chart_configuration( $post->ID, 'graph' );

					$plugin->ec_render_field( 'radio', 'ec_chart_graph_responsive', 'Responsive', $ec_chart_graph['responsive'], array(
						'Yes' => true,
						'No'  => false,
					) );
					$plugin->ec_render_field( 'dropdown', 'ec_chart_graph_palette', 'Palette', $ec_chart_graph['palette'], array(
						'Default'    => 'Default',
						'OldDefault' => 'OldDefault',
						'Plain'      => 'Plain',
						'Android'    => 'Android',
						'Simple'     => 'Simple',
						'Soft'       => 'Soft',
						'Egypt'      => 'Egypt',
						'Olive'      => 'Olive',
						'Candid'     => 'Candid',
						'Sulphide'   => 'Sulphide',
						'Lint'       => 'Lint',
					) );
					$plugin->ec_render_field( 'color-picker', 'ec_chart_graph_bgcolor', 'Background Color', $ec_chart_graph['bgcolor'] );
					$plugin->ec_render_field( 'radio', 'ec_chart_graph_orientation', 'Orientation', $ec_chart_graph['orientation'], array(
						'Horizontal' => 'Horizontal',
						'Vertical'   => 'Vertical',
					) );
					$plugin->ec_render_field( 'slider', 'ec_chart_graph_opacity', 'Opacity', $ec_chart_graph['opacity'] );
					?>
				</div>

				<div id="ec-tabs-meta" class="ec-tab" data-pws-tab-name="<?php esc_attr_e( 'Meta', 'easy-charts' ); ?>" data-pws-tab="ec-tabs-meta" data-pws-tab-icon="fa-table">
					<?php $ec_chart_meta = $plugin->ec_get_chart_configuration( $post->ID, 'meta' ); ?>

					<input type="hidden" value=".uv-div-<?php echo esc_attr( $post->ID ); ?>" name="ec_chart_meta_position"/>

					<?php
					$plugin->ec_render_field( 'text', 'ec_chart_meta_caption', 'Caption', $ec_chart_meta['caption'] );
					$plugin->ec_render_field( 'text', 'ec_chart_meta_subcaption', 'Sub Caption', $ec_chart_meta['subcaption'] );
					$plugin->ec_render_field( 'text', 'ec_chart_meta_hlabel', 'Horizontal Label', $ec_chart_meta['hlabel'] );
					$plugin->ec_render_field( 'text', 'ec_chart_meta_hsublabel', 'Horizontal Sub Label', $ec_chart_meta['hsublabel'] );
					$plugin->ec_render_field( 'text', 'ec_chart_meta_vlabel', 'Vertical Label', $ec_chart_meta['vlabel'] );
					$plugin->ec_render_field( 'text', 'ec_chart_meta_vsublabel', 'Vertical Sub Label', $ec_chart_meta['vsublabel'] );
					$plugin->ec_render_field( 'radio', 'ec_chart_meta_isDownloadable', 'Is Downloadable', $ec_chart_meta['isDownloadable'], array(
						'Yes' => '1',
						'No'  => '0',
					) );
					$plugin->ec_render_field( 'text', 'ec_chart_meta_downloadLabel', 'Download Label', $ec_chart_meta['downloadLabel'] );
					?>
				</div>

				<div id="ec-tabs-dimension" class="ec-tab" data-pws-tab-name="<?php esc_attr_e( 'Dimension', 'easy-charts' ); ?>" data-pws-tab="ec-tabs-dimension" data-pws-tab-icon="fa-text-height">
					<?php
					$ec_chart_dimension = $plugin->ec_get_chart_configuration( $post->ID, 'dimension' );

					$plugin->ec_render_field( 'number', 'ec_chart_dimension_width', 'Width', $ec_chart_dimension['width'] );
					$plugin->ec_render_field( 'number', 'ec_chart_dimension_height', 'Height', $ec_chart_dimension['height'] );
					?>
				</div>

				<div id="ec-tabs-margin" class="ec-tab" data-pws-tab-name="<?php esc_attr_e( 'Margin', 'easy-charts' ); ?>" data-pws-tab="ec-tabs-margin" data-pws-tab-icon="fa-arrows">
					<?php
					$ec_chart_margin = $plugin->ec_get_chart_configuration( $post->ID, 'margin' );

					$plugin->ec_render_field( 'number', 'ec_chart_margin_top', 'Top', $ec_chart_margin['top'], array( 'min' => 1 ) );
					$plugin->ec_render_field( 'number', 'ec_chart_margin_bottom', 'Bottom', $ec_chart_margin['bottom'], array( 'min' => 1 ) );
					$plugin->ec_render_field( 'number', 'ec_chart_margin_left', 'Left', $ec_chart_margin['left'], array( 'min' => 1 ) );
					$plugin->ec_render_field( 'number', 'ec_chart_margin_right', 'Right', $ec_chart_margin['right'], array( 'min' => 1 ) );
					?>
				</div>

				<div id="ec-tabs-frame" class="ec-tab" data-pws-tab-name="<?php esc_attr_e( 'Frame', 'easy-charts' ); ?>" data-pws-tab="ec-tabs-frame" data-pws-tab-icon="fa-television">
					<?php
					$ec_chart_frame = $plugin->ec_get_chart_configuration( $post->ID, 'frame' );

					$plugin->ec_render_field( 'color-picker', 'ec_chart_frame_bgcolor', 'Background Color', $ec_chart_frame['bgcolor'] );
					?>
				</div>

				<div id="ec-tabs-axis" class="ec-tab" data-pws-tab-name="<?php esc_attr_e( 'Axis', 'easy-charts' ); ?>" data-pws-tab="ec-tabs-axis" data-pws-tab-icon="fa-long-arrow-right">
					<?php
					$ec_chart_axis = $plugin->ec_get_chart_configuration( $post->ID, 'axis' );

					$plugin->ec_render_field( 'slider', 'ec_chart_axis_opacity', 'Opacity', $ec_chart_axis['opacity'] );
					$plugin->ec_render_field( 'number', 'ec_chart_axis_ticks', 'Ticks', $ec_chart_axis['ticks'], array(
						'min' => 0,
						'max' => '',
					) );
					$plugin->ec_render_field( 'number', 'ec_chart_axis_subticks', 'Subticks', $ec_chart_axis['subticks'], array( 'min' => 0 ) );
					$plugin->ec_render_field( 'number', 'ec_chart_axis_padding', 'Padding', $ec_chart_axis['padding'], array( 'min' => 0 ) );
					$plugin->ec_render_field( 'color-picker', 'ec_chart_axis_strokecolor', 'Stroke Color', $ec_chart_axis['strokecolor'] );
					$plugin->ec_render_field( 'number', 'ec_chart_axis_minor', 'Minor', $ec_chart_axis['minor'], array( 'max' => 0 ) );
					$plugin->ec_render_field( 'dropdown', 'ec_chart_axis_fontfamily', 'Font Family', $ec_chart_axis['fontfamily'], $font_family, 'Arial' );
					$plugin->ec_render_field( 'number', 'ec_chart_axis_fontsize', 'Font Size', $ec_chart_axis['fontsize'], array( 'min' => 0 ) );
					$plugin->ec_render_field( 'dropdown', 'ec_chart_axis_fontweight', 'Font Weight', $ec_chart_axis['fontweight'], array(
						'100' => 100,
						'200' => 200,
						'300' => 300,
						'400' => 400,
						'500' => 500,
						'600' => 600,
						'700' => 700,
					) );
					$plugin->ec_render_field( 'radio', 'ec_chart_axis_showticks', 'Show Ticks', $ec_chart_axis['showticks'], array(
						'Yes' => 1,
						'No'  => 0,
					) );
					$plugin->ec_render_field( 'radio', 'ec_chart_axis_showsubticks', 'Show Sub Ticks', $ec_chart_axis['showsubticks'], array(
						'Yes' => 1,
						'No'  => 0,
					) );
					$plugin->ec_render_field( 'radio', 'ec_chart_axis_showtext', 'Show Text', $ec_chart_axis['showtext'], array(
						'Yes' => 1,
						'No'  => 0,
					) );
					?>
				</div>

				<div id="ec-tabs-label" class="ec-tab" data-pws-tab-name="<?php esc_attr_e( 'Label', 'easy-charts' ); ?>" data-pws-tab="ec-tabs-label" data-pws-tab-icon="fa-tag">
					<?php
					$ec_chart_label = $plugin->ec_get_chart_configuration( $post->ID, 'label' );

					$plugin->ec_render_field( 'dropdown', 'ec_chart_label_fontfamily', 'Font Family', $ec_chart_label['fontfamily'], $font_family, 'Arial' );
					$plugin->ec_render_field( 'number', 'ec_chart_label_fontsize', 'Font Size', $ec_chart_label['fontsize'], array( 'min' => 0 ) );
					$plugin->ec_render_field( 'dropdown', 'ec_chart_label_fontweight', 'Font Weight', $ec_chart_label['fontweight'], array(
						'100' => 100,
						'200' => 200,
						'300' => 300,
						'400' => 400,
						'500' => 500,
						'600' => 600,
						'700' => 700,
						'800' => 800,
						'900' => 900,
					) );
					$plugin->ec_render_field( 'color-picker', 'ec_chart_label_strokecolor', 'Stroke Color', $ec_chart_label['strokecolor'] );
					$plugin->ec_render_field( 'radio', 'ec_chart_label_showlabel', 'Show Label', $ec_chart_label['showlabel'], array(
						'Yes' => 1,
						'No'  => 0,
					) );
					$plugin->ec_render_field( 'number', 'ec_chart_label_precision', 'Precision', $ec_chart_label['precision'], array( 'min' => 0 ) );
					$plugin->ec_render_field( 'text', 'ec_chart_label_prefix', 'Prefix', $ec_chart_label['prefix'] );
					$plugin->ec_render_field( 'text', 'ec_chart_label_suffix', 'Suffix', $ec_chart_label['suffix'] );
					?>
				</div>

				<div id="ec-tabs-legend" class="ec-tab" data-pws-tab-name="<?php esc_attr_e( 'Legend', 'easy-charts' ); ?>" data-pws-tab="ec-tabs-legend" data-pws-tab-icon="fa-info">
					<?php
					$ec_chart_legend = $plugin->ec_get_chart_configuration( $post->ID, 'legend' );
					$plugin->ec_render_field( 'dropdown', 'ec_chart_legend_position', 'Position', $ec_chart_legend['position'], array(
						'bottom' => 'Bottom',
						'right'  => 'Right',
					) );
					$plugin->ec_render_field( 'dropdown', 'ec_chart_legend_type', 'Type', $ec_chart_legend['legendtype'], array(
						'categories' => 'Categories',
						'labels'     => 'Labels',
					) );
					$plugin->ec_render_field( 'dropdown', 'ec_chart_legend_fontfamily', 'Font Family', $ec_chart_legend['fontfamily'], $font_family, 'Arial' );
					$plugin->ec_render_field( 'number', 'ec_chart_legend_fontsize', 'Font Size', $ec_chart_legend['fontsize'], array( 'min' => 0 ) );
					$plugin->ec_render_field( 'dropdown', 'ec_chart_legend_fontweight', 'Font Weight', $ec_chart_legend['fontweight'], array(
						'100' => 100,
						'200' => 200,
						'300' => 300,
						'400' => 400,
						'500' => 500,
						'600' => 600,
						'700' => 700,
						'800' => 800,
						'900' => 900,
					) );
					$plugin->ec_render_field( 'color-picker', 'ec_chart_legend_color', 'Stroke Color', $ec_chart_legend['color'] );
					$plugin->ec_render_field( 'number', 'ec_chart_legend_strokewidth', 'Stroke Width', $ec_chart_legend['strokewidth'], array(
						'min'  => 0,
						'step' => .05,
					) );
					$plugin->ec_render_field( 'number', 'ec_chart_legend_textmargin', 'Text Margin', $ec_chart_legend['textmargin'], array( 'min' => 0 ) );
					$plugin->ec_render_field( 'number', 'ec_chart_legend_symbolsize', 'Symbol Size', $ec_chart_legend['symbolsize'], array( 'min' => 0 ) );
					$plugin->ec_render_field( 'color-picker', 'ec_chart_legend_inactivecolor', 'Inactive Color', $ec_chart_legend['inactivecolor'] );
					$plugin->ec_render_field( 'number', 'ec_chart_legend_legendstart', 'Start', $ec_chart_legend['legendstart'], array( 'min' => 0 ) );
					$plugin->ec_render_field( 'radio', 'ec_chart_legend_showlegends', 'Show Legend', $ec_chart_legend['showlegends'], array(
						'Yes' => 1,
						'No'  => 0,
					) );
					?>
				</div>

				<div id="ec-tabs-scale" class="ec-tab" data-pws-tab-name="<?php esc_attr_e( 'Scale', 'easy-charts' ); ?>" data-pws-tab="ec-tabs-scale" data-pws-tab-icon="fa-balance-scale">
					<?php
					$ec_chart_scale = $plugin->ec_get_chart_configuration( $post->ID, 'scale' );

					$plugin->ec_render_field( 'radio', 'ec_chart_scale_type', 'Type', $ec_chart_scale['type'], array(
						'Linear' => 'linear',
						'Log'    => 'log',
						'Pow'    => 'pow',
						'SQRT'   => 'sqrt',
					) );
					$plugin->ec_render_field( 'slider', 'ec_chart_scale_ordinality', 'Ordinality', $ec_chart_scale['ordinality'] );
					?>
				</div>

				<div id="ec-tabs-tooltip" class="ec-tab" data-pws-tab-name="<?php esc_attr_e( 'Tooltip', 'easy-charts' ); ?>" data-pws-tab="ec-tabs-tooltip" data-pws-tab-icon="fa-comment">
					<?php
					$ec_chart_tooltip = $plugin->ec_get_chart_configuration( $post->ID, 'tooltip' );

					$plugin->ec_render_field( 'radio', 'ec_chart_tooltip_show', 'Show', $ec_chart_tooltip['show'], array(
						'Yes' => 1,
						'No'  => 0,
					) );
					$plugin->ec_render_field( 'text', 'ec_chart_tooltip_format', 'Format', $ec_chart_tooltip['format'] );
					?>
				</div>

				<div id="ec-tabs-caption" class="ec-tab" data-pws-tab-name="<?php esc_attr_e( 'Caption', 'easy-charts' ); ?>" data-pws-tab="ec-tabs-caption" data-pws-tab-icon="fa-header">
					<?php
					$ec_chart_caption = $plugin->ec_get_chart_configuration( $post->ID, 'caption' );

					$plugin->ec_render_field( 'dropdown', 'ec_chart_caption_fontfamily', 'Font Family', $ec_chart_caption['fontfamily'], $font_family, 'Arial' );
					$plugin->ec_render_field( 'number', 'ec_chart_caption_fontsize', 'Font Size', $ec_chart_caption['fontsize'], array( 'min' => 0 ) );
					$plugin->ec_render_field( 'dropdown', 'ec_chart_caption_fontweight', 'Font Weight', $ec_chart_caption['fontweight'], array(
						'100' => 100,
						'200' => 200,
						'300' => 300,
						'400' => 400,
						'500' => 500,
						'600' => 600,
						'700' => 700,
						'800' => 800,
						'900' => 900,
					) );
					$plugin->ec_render_field( 'dropdown', 'ec_chart_caption_textdecoration', 'Textdecoration', $ec_chart_caption['textdecoration'], array(
						'none'         => 'none',
						'blink'        => 'blink',
						'line-through' => 'line-through',
						'overline'     => 'overline',
						'underline'    => 'underline',
					) );
					$plugin->ec_render_field( 'color-picker', 'ec_chart_caption_strokecolor', 'Stroke Color', $ec_chart_caption['strokecolor'] );
					$plugin->ec_render_field( 'dropdown', 'ec_chart_caption_cursor', 'Cursor', $ec_chart_caption['cursor'], array(
						'pointer' => 'pointer',
						'move'    => 'move',
						'auto'    => 'auto',
						'text'    => 'text',
						'none'    => 'none',
					) );
					?>
				</div>

				<div id="ec-tabs-subcaption" class="ec-tab" data-pws-tab-name="<?php esc_attr_e( 'Subcaption', 'easy-charts' ); ?>" data-pws-tab="ec-tabs-subcaption" data-pws-tab-icon="fa-font">
					<?php
					$ec_chart_subcaption = $plugin->ec_get_chart_configuration( $post->ID, 'subcaption' );

					$plugin->ec_render_field( 'dropdown', 'ec_chart_subcaption_fontfamily', 'Font Family', $ec_chart_subcaption['fontfamily'], $font_family, 'Arial' );
					$plugin->ec_render_field( 'number', 'ec_chart_subcaption_fontsize', 'Font Size', $ec_chart_subcaption['fontsize'], array( 'min' => 0 ) );
					$plugin->ec_render_field( 'dropdown', 'ec_chart_subcaption_fontweight', 'Font Weight', $ec_chart_subcaption['fontweight'], array(
						'100' => 100,
						'200' => 200,
						'300' => 300,
						'400' => 400,
						'500' => 500,
						'600' => 600,
						'700' => 700,
						'800' => 800,
						'900' => 900,
					) );
					$plugin->ec_render_field( 'dropdown', 'ec_chart_subcaption_textdecoration', 'Textdecoration', $ec_chart_subcaption['textdecoration'], array(
						'none'         => 'none',
						'blink'        => 'blink',
						'line-through' => 'line-through',
						'overline'     => 'overline',
						'underline'    => 'underline',
					) );
					$plugin->ec_render_field( 'color-picker', 'ec_chart_subcaption_strokecolor', 'Stroke Color', $ec_chart_subcaption['strokecolor'] );
					$plugin->ec_render_field( 'dropdown', 'ec_chart_subcaption_cursor', 'Cursor', $ec_chart_subcaption['cursor'], array(
						'pointer' => 'pointer',
						'move'    => 'move',
						'auto'    => 'auto',
						'text'    => 'text',
						'none'    => 'none',
					) );
					?>
				</div>

				<div id="ec-tabs-bar" class="ec-tab" data-pws-tab-name="<?php esc_attr_e( 'Bar', 'easy-charts' ); ?>" data-pws-tab="ec-tabs-bar" data-pws-tab-icon="fa-bar-chart">
					<?php
					$ec_chart_bar = $plugin->ec_get_chart_configuration( $post->ID, 'bar' );

					$plugin->ec_render_field( 'dropdown', 'ec_chart_bar_fontfamily', 'Font Family', $ec_chart_bar['fontfamily'], $font_family, 'Arial' );
					$plugin->ec_render_field( 'number', 'ec_chart_bar_fontsize', 'Font Size', $ec_chart_bar['fontsize'], array( 'min' => 0 ) );
					$plugin->ec_render_field( 'dropdown', 'ec_chart_bar_fontweight', 'Font Weight', $ec_chart_bar['fontweight'], array(
						'100' => 100,
						'200' => 200,
						'300' => 300,
						'400' => 400,
						'500' => 500,
						'600' => 600,
						'700' => 700,
						'800' => 800,
						'900' => 900,
					) );
					$plugin->ec_render_field( 'color-picker', 'ec_chart_bar_strokecolor', 'Stroke Color', $ec_chart_bar['strokecolor'] );
					$plugin->ec_render_field( 'color-picker', 'ec_chart_bar_textcolor', 'TextColor', $ec_chart_bar['textcolor'] );
					?>
				</div>

				<div id="ec-tabs-line" class="ec-tab" data-pws-tab-name="<?php esc_attr_e( 'Line', 'easy-charts' ); ?>" data-pws-tab="ec-tabs-line" data-pws-tab-icon="fa-line-chart">
					<?php
					$ec_chart_line = $plugin->ec_get_chart_configuration( $post->ID, 'line' );

					$plugin->ec_render_field( 'dropdown', 'ec_chart_line_interpolation', 'Interpolation', $ec_chart_line['interpolation'], array(
						'linear'   => 'linear',
						'basis'    => 'basis',
						'cardinal' => 'cardinal',
						'monotone' => 'monotone',
					) );
					?>
				</div>

				<div id="ec-tabs-area" class="ec-tab" data-pws-tab-name="<?php esc_attr_e( 'Area', 'easy-charts' ); ?>" data-pws-tab="ec-tabs-area" data-pws-tab-icon="fa-area-chart">
					<?php
					$ec_chart_area = $plugin->ec_get_chart_configuration( $post->ID, 'area' );

					$plugin->ec_render_field( 'dropdown', 'ec_chart_area_interpolation', 'Interpolation', $ec_chart_area['interpolation'], array(
						'linear'   => 'linear',
						'basis'    => 'basis',
						'cardinal' => 'cardinal',
						'monotone' => 'monotone',
					) );
					$plugin->ec_render_field( 'slider', 'ec_chart_area_opacity', 'Opacity', $ec_chart_area['opacity'] );
					$plugin->ec_render_field( 'dropdown', 'ec_chart_area_offset', 'Offset', $ec_chart_area['offset'], array(
						'zero'      => 'zero',
						'wiggle'    => 'wiggle',
						'silhoutte' => 'silhoutte',
						'expand'    => 'expand',
					) );
					?>
				</div>

				<div id="ec-tabs-pie" class="ec-tab" data-pws-tab-name="<?php esc_attr_e( 'Pie', 'easy-charts' ); ?>" data-pws-tab="ec-tabs-pie" data-pws-tab-icon="fa-pie-chart">
					<?php
					$ec_chart_pie = $plugin->ec_get_chart_configuration( $post->ID, 'pie' );

					$plugin->ec_render_field( 'dropdown', 'ec_chart_pie_fontfamily', 'Font Family', $ec_chart_pie['fontfamily'], $font_family, 'Arial' );
					$plugin->ec_render_field( 'number', 'ec_chart_pie_fontsize', 'Font Size', $ec_chart_pie['fontsize'], array( 'min' => 0 ) );
					$plugin->ec_render_field( 'dropdown', 'ec_chart_pie_fontweight', 'Font Weight', $ec_chart_pie['fontweight'], array(
						'100' => 100,
						'200' => 200,
						'300' => 300,
						'400' => 400,
						'500' => 500,
						'600' => 600,
						'700' => 700,
						'800' => 800,
						'900' => 900,
					) );
					$plugin->ec_render_field( 'dropdown', 'ec_chart_pie_fontvariant', 'Font Varient', $ec_chart_pie['fontvariant'], array(
						'normal'     => 'normal',
						'small-caps' => 'small-caps',
					) );
					$plugin->ec_render_field( 'color-picker', 'ec_chart_pie_fontfill', 'Font Fill', $ec_chart_pie['fontfill'] );
					$plugin->ec_render_field( 'color-picker', 'ec_chart_pie_strokecolor', 'Stroke Color', $ec_chart_pie['strokecolor'] );
					$plugin->ec_render_field( 'number', 'ec_chart_pie_strokewidth', 'Stroke Width', $ec_chart_pie['strokewidth'], array( 'min' => 0 ) );
					?>
				</div>

				<div id="ec-tabs-donut" class="ec-tab" data-pws-tab-name="<?php esc_attr_e( 'Donut', 'easy-charts' ); ?>" data-pws-tab="ec-tabs-donut" data-pws-tab-icon="fa-pie-chart">
					<?php
					$ec_chart_donut = $plugin->ec_get_chart_configuration( $post->ID, 'donut' );

					$plugin->ec_render_field( 'dropdown', 'ec_chart_donut_fontfamily', 'Font Family', $ec_chart_donut['fontfamily'], $font_family, 'Arial' );
					$plugin->ec_render_field( 'number', 'ec_chart_donut_fontsize', 'Font Size', $ec_chart_pie['fontsize'], array( 'min' => 0 ) );
					$plugin->ec_render_field( 'dropdown', 'ec_chart_donut_fontweight', 'Font Weight', $ec_chart_donut['fontweight'], array(
						'100' => 100,
						'200' => 200,
						'300' => 300,
						'400' => 400,
						'500' => 500,
						'600' => 600,
						'700' => 700,
						'800' => 800,
						'900' => 900,
					) );
					$plugin->ec_render_field( 'dropdown', 'ec_chart_donut_fontvariant', 'Font Varient', $ec_chart_donut['fontvariant'], array(
						'normal'     => 'normal',
						'small-caps' => 'small-caps',
					) );
					$plugin->ec_render_field( 'color-picker', 'ec_chart_donut_fontfill', 'Font Fill', $ec_chart_donut['fontfill'] );
					$plugin->ec_render_field( 'color-picker', 'ec_chart_donut_strokecolor', 'Stroke Color', $ec_chart_donut['strokecolor'] );
					$plugin->ec_render_field( 'number', 'ec_chart_donut_strokewidth', 'Stroke Width', $ec_chart_donut['strokewidth'], array( 'min' => 0 ) );
					$plugin->ec_render_field( 'number', 'ec_chart_donut_strokewidth', 'Stroke Width', $ec_chart_donut['strokewidth'], array( 'min' => 0 ) );
					$plugin->ec_render_field( 'slider', 'ec_chart_donut_factor', 'Factor', $ec_chart_donut['factor'] );
					?>
				</div>
			</div>

		</div>

	</div>
</div>
