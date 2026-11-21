/**
 * WordPress dependencies
 */
import {
	BaseControl,
	Button,
	PanelBody,
	SelectControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import SettingsPanels from '../../../components/settings-panels';
import { CHART_TYPE_OPTIONS } from '../chart-types';
import { baseSettingsFor } from '../../../components/editor-data';

const PIE_TYPES = [ 'pie', 'donut', 'polar-area' ];

/**
 * Sidebar for a chart's type, data and settings.
 *
 * Settings panels show each setting's effective value (defaults, global settings, then the
 * chart's own). Changing one stores it as the chart's override; "Reset" removes the override so
 * the setting follows the global settings again.
 *
 * @param {Object}                  props            Component props.
 * @param {Object}                  props.chart      Chart document.
 * @param {(chart: Object) => void} props.onChange   Called with the updated chart.
 * @param {() => void}              props.onEditData Opens the data editor.
 * @return {Element} Inspector panels.
 */
export default function ChartInspector( { chart, onChange, onEditData } ) {
	return (
		<>
			<PanelBody title={ __( 'Chart', 'easy-charts' ) }>
				<SelectControl
					__next40pxDefaultSize
					__nextHasNoMarginBottom
					label={ __( 'Chart type', 'easy-charts' ) }
					value={ chart.type }
					options={ CHART_TYPE_OPTIONS.map( ( option ) => ( {
						value: option.value,
						label: option.label,
					} ) ) }
					onChange={ ( type ) => onChange( { ...chart, type } ) }
				/>
				<BaseControl
					__nextHasNoMarginBottom
					id="easy-charts-data"
					label={ __( 'Data', 'easy-charts' ) }
					help={
						PIE_TYPES.includes( chart.type )
							? __(
									'Pie, donut and polar-area charts use the first series: one slice per row.',
									'easy-charts'
								)
							: undefined
					}
				>
					<div>
						<Button variant="secondary" onClick={ onEditData }>
							{ __( 'Edit data', 'easy-charts' ) }
						</Button>
					</div>
				</BaseControl>
			</PanelBody>
			<SettingsPanels
				base={ baseSettingsFor( chart ) }
				overrides={ chart.settings }
				type={ chart.type }
				onChange={ ( settings ) => onChange( { ...chart, settings } ) }
			/>
		</>
	);
}
