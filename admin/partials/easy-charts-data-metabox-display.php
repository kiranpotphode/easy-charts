<?php
/**
 * Markup file for metabox
 *
 * HTML markup for data metabox.
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
<div id="easy-charts-data-metabox-wrap">

	<div id="easy-data-chart-box">

		<div class="ec-data-container">
			<div class="handsontable-container" style="width: auto; height: auto; overflow: hidden;">
				<div id="handsontable" class="hot htRemoveRow handsontable htRowHeaders htColumnHeaders"></div>
			</div>
			<div class="handsontable-controls">
				<div class="button-group">
					<button class="button button-primary" id="ec-button-add-col"><span
								class="dashicons dashicons-plus"></span><?php esc_html_e( 'Add Column', 'easy-charts' ); ?>
					</button>
					<button class="button " id="ec-button-remove-col"><span class="dashicons dashicons-minus"></span><?php esc_html_e( 'Remove Column', 'easy-charts' ); ?>
					</button>
				</div>
				<div class="button-group">
					<button class="button button-primary" id="ec-button-add-row"><span
								class="dashicons dashicons-plus"></span><?php esc_html_e( 'Add Row', 'easy-charts' ); ?>
					</button>
					<button class="button " id="ec-button-remove-row"><span
								class="dashicons dashicons-minus"></span><?php esc_html_e( 'Remove Row', 'easy-charts' ); ?>
					</button>
				</div>
				<div class="button-group">
					<button class="button button-primary" id="ec-button-save-data"><span
								class="dashicons dashicons-update"></span><?php esc_html_e( 'Update Chart Data', 'easy-charts' ); ?>
					</button>
				</div>

				<div id="dialog-confirm" title="Empty Row/Column found!" style="display: none;">
					<p><span class="ui-icon ui-icon-alert" style="float:left; margin:0 7px 20px 0; display: none;"></span>
						<?php esc_html_e( "There are some empty row and/or columns in chart's datatable.", 'easy-charts' ); ?>
						<br/>
						<?php esc_html_e( 'Please delete these empty rows/columns or fill in data !', 'easy-charts' ); ?></p>
				</div>
			</div>
			<div id="datatable-note">
				<?php esc_html_e( 'Note: Add/Remove, Move Column/Row using mouse is available in datatable.', 'easy-charts' ); ?>
				<br/>
				<?php esc_html_e( 'Try right clicking on datatable headers.', 'easy-charts' ); ?>
			</div>

		</div>
	</div>

</div>
