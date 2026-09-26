/**
 * WordPress dependencies
 */
import { BlockControls, useBlockProps } from '@wordpress/block-editor';
import { ToolbarButton, ToolbarGroup } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import ChartPreview from './components/chart-preview';
import Wizard from './components/wizard';
import DataEditorModal from './data-editor/data-editor-modal';

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
	const { chart } = attributes;
	const [ isEditingData, setEditingData ] = useState( false );

	if ( ! chart ) {
		return (
			<div { ...blockProps }>
				<Wizard
					onCreate={ ( created ) => {
						setAttributes( { chart: created } );
						setEditingData( true );
					} }
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
