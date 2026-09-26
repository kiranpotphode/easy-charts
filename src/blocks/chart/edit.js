/**
 * WordPress dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';
import { Placeholder } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Block editor for a chart.
 *
 * @return {Element} Editor view.
 */
export default function Edit() {
	return (
		<div { ...useBlockProps() }>
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
