/**
 * WordPress dependencies
 */
import { BlockControls, InspectorControls } from '@wordpress/block-editor';
import { ToolbarButton, ToolbarGroup } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import ChartPreview from '../../../components/chart-preview';
import DataEditorModal from '../data-editor/data-editor-modal';
import ChartInspector from '../inspector/chart-inspector';
import Wizard from './wizard';

/**
 * Editing UI for one chart document, wherever it is stored (block attributes or chart post meta):
 * the wizard until there is a chart, then preview, toolbar, sidebar and data modal.
 *
 * @param {Object}                    props           Component props.
 * @param {Object|null}               props.chart     Chart document, or null for a new chart.
 * @param {(chart: Object) => void}   props.onChange  Called with the updated document.
 * @param {(chartId: number) => void} [props.onEmbed] Embeds an existing chart; omitted where
 *                                                    embedding makes no sense (a chart post).
 * @param {string}                    [props.title]   Title when the settings have none.
 * @param {Element}                   [props.notice]  Shown at the top of the sidebar.
 * @return {Element} Chart editor.
 */
export default function ChartEditor( {
	chart,
	onChange,
	onEmbed,
	title = '',
	notice,
} ) {
	const [ isEditingData, setEditingData ] = useState( false );

	if ( ! chart ) {
		return (
			<Wizard
				onCreate={ ( created, { editData } ) => {
					onChange( created );
					setEditingData( editData );
				} }
				onEmbed={ onEmbed }
			/>
		);
	}

	return (
		<>
			<BlockControls>
				<ToolbarGroup>
					<ToolbarButton
						icon="editor-table"
						label={ __( 'Edit data', 'easy-charts' ) }
						onClick={ () => setEditingData( true ) }
					/>
				</ToolbarGroup>
			</BlockControls>
			<InspectorControls>
				{ notice }
				<ChartInspector
					chart={ chart }
					onChange={ onChange }
					onEditData={ () => setEditingData( true ) }
				/>
			</InspectorControls>
			<ChartPreview doc={ chart } title={ title } />
			{ isEditingData && (
				<DataEditorModal
					data={ chart.data }
					onClose={ () => setEditingData( false ) }
					onApply={ ( data ) => {
						onChange( { ...chart, data } );
						setEditingData( false );
					} }
				/>
			) }
		</>
	);
}
