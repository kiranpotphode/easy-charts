/**
 * WordPress dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import ChartEditor from './components/chart-editor';
import ChartPostChart, { CHART_POST_TYPE } from './components/chart-post-chart';
import EmbeddedChart from './components/embedded-chart';

/**
 * Block editor for a chart.
 *
 * Three cases: on a chart post's own screen the block edits that post's chart (meta); with a
 * `chartId` it embeds a chart post read-only; otherwise it holds its own chart in attributes.
 *
 * @param {Object}                       props               Block props.
 * @param {Object}                       props.attributes    Block attributes.
 * @param {(attributes: Object) => void} props.setAttributes Update attributes.
 * @param {Object}                       props.context       Block context (postId, postType).
 * @return {Element} Editor view.
 */
export default function Edit( { attributes, setAttributes, context } ) {
	const blockProps = useBlockProps();
	const { chart, chartId } = attributes;

	let content;
	if ( context.postType === CHART_POST_TYPE && context.postId ) {
		content = <ChartPostChart postId={ context.postId } />;
	} else if ( chartId ) {
		content = (
			<EmbeddedChart
				chartId={ chartId }
				onDetach={ ( copy ) =>
					setAttributes( { chartId: undefined, chart: copy } )
				}
				onReplace={ () => setAttributes( { chartId: undefined } ) }
			/>
		);
	} else {
		content = (
			<ChartEditor
				chart={ chart ?? null }
				onChange={ ( next ) => setAttributes( { chart: next } ) }
				onEmbed={ ( id ) => setAttributes( { chartId: id } ) }
			/>
		);
	}

	return <div { ...blockProps }>{ content }</div>;
}
