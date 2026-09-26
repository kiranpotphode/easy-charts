/**
 * WordPress dependencies
 */
import {
	BlockControls,
	InspectorControls,
	useBlockProps,
} from '@wordpress/block-editor';
import { ToolbarButton, ToolbarGroup } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import ChartPreview from './components/chart-preview';
import EmbeddedChart from './components/embedded-chart';
import Wizard from './components/wizard';
import DataEditorModal from './data-editor/data-editor-modal';
import ChartInspector from './inspector/chart-inspector';

/**
 * Block editor for a chart.
 *
 * @param {Object}                       props               Block props.
 * @param {Object}                       props.attributes    Block attributes.
 * @param {(attributes: Object) => void} props.setAttributes Update attributes.
 * @return {Element} Editor view.
 */
export default function Edit( { attributes, setAttributes } ) {
	const blockProps = useBlockProps();
	const { chart, chartId } = attributes;
	const [ isEditingData, setEditingData ] = useState( false );

	if ( chartId ) {
		return (
			<div { ...blockProps }>
				<EmbeddedChart
					chartId={ chartId }
					onDetach={ ( copy ) =>
						setAttributes( { chartId: undefined, chart: copy } )
					}
					onReplace={ () => setAttributes( { chartId: undefined } ) }
				/>
			</div>
		);
	}

	if ( ! chart ) {
		return (
			<div { ...blockProps }>
				<Wizard
					onCreate={ ( created, { editData } ) => {
						setAttributes( { chart: created } );
						setEditingData( editData );
					} }
					onEmbed={ ( id ) => setAttributes( { chartId: id } ) }
				/>
			</div>
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
				<ChartInspector
					chart={ chart }
					onChange={ ( next ) => setAttributes( { chart: next } ) }
					onEditData={ () => setEditingData( true ) }
				/>
			</InspectorControls>
			<div { ...blockProps }>
				<ChartPreview doc={ chart } />
			</div>
			{ isEditingData && (
				<DataEditorModal
					data={ chart.data }
					onClose={ () => setEditingData( false ) }
					onApply={ ( data ) => {
						setAttributes( { chart: { ...chart, data } } );
						setEditingData( false );
					} }
				/>
			) }
		</>
	);
}
