/**
 * WordPress dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import ChartPreview from './components/chart-preview';
import Wizard from './components/wizard';

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

	return (
		<div { ...blockProps }>
			{ attributes.chart ? (
				<ChartPreview doc={ attributes.chart } />
			) : (
				<Wizard onCreate={ ( chart ) => setAttributes( { chart } ) } />
			) }
		</div>
	);
}
