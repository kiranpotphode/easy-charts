/**
 * WordPress dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';
import { Placeholder } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import ChartPreview from './components/chart-preview';

/**
 * Block editor for a chart.
 *
 * @param {Object} props            Block props.
 * @param {Object} props.attributes Block attributes.
 * @return {Element} Editor view.
 */
export default function Edit( { attributes } ) {
	const blockProps = useBlockProps();

	if ( attributes.chart ) {
		return (
			<div { ...blockProps }>
				<ChartPreview doc={ attributes.chart } />
			</div>
		);
	}

	return (
		<div { ...blockProps }>
			<Placeholder
				icon="chart-bar"
				label={ __( 'Chart', 'easy-charts' ) }
				instructions={ __(
					'Chart editing is being built.',
					'easy-charts'
				) }
			/>
		</div>
	);
}
