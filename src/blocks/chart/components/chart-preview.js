/**
 * WordPress dependencies
 */
import { useEffect, useMemo, useRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { buildOption } from '../../../charts/build-option';
import { getEditorData, resolveSettings } from '../editor-data';

/**
 * Chart drawn with ECharts, the same way the front end draws it.
 *
 * @param {Object} props       Component props.
 * @param {Object} props.doc   Chart document.
 * @param {string} props.title Title when the settings have none (e.g. the chart post title).
 * @return {Element} Preview.
 */
export default function ChartPreview( { doc, title = '' } ) {
	const boxRef = useRef();
	const chartRef = useRef();
	const settings = useMemo( () => resolveSettings( doc ), [ doc ] );
	const hasData = doc?.data?.rows?.length > 0;

	const option = useMemo(
		() =>
			buildOption( {
				type: doc?.type,
				data: doc?.data,
				settings,
				title: settings.title?.text || title,
				strings: getEditorData().strings,
			} ),
		[ doc, settings, title ]
	);

	useEffect( () => {
		const echarts = window.easyChartsECharts;
		const box = boxRef.current;
		if ( ! hasData || ! echarts || ! box ) {
			return;
		}
		const chart = echarts.init( box, null, { renderer: 'svg' } );
		chartRef.current = chart;

		// The editor canvas is an iframe: observe with that window's ResizeObserver.
		const view = box.ownerDocument.defaultView;
		const observer = view?.ResizeObserver
			? new view.ResizeObserver( () => chart.resize() )
			: null;
		observer?.observe( box );

		return () => {
			observer?.disconnect();
			chart.dispose();
			chartRef.current = null;
		};
	}, [ hasData ] );

	useEffect( () => {
		// Replace the whole option so removed series and settings disappear.
		chartRef.current?.setOption( option, true );
	}, [ option, hasData ] );

	if ( ! hasData ) {
		return (
			<p className="easy-charts-preview__empty">
				{ __( 'Add data to see the chart.', 'easy-charts' ) }
			</p>
		);
	}

	const height = Math.max(
		100,
		parseInt( settings.size?.height, 10 ) || 400
	);
	const width =
		settings.size?.responsive === false
			? Math.max( 100, parseInt( settings.size?.width, 10 ) || 400 )
			: undefined;

	return (
		<div
			ref={ boxRef }
			className="easy-charts-preview"
			style={ { height, width, maxWidth: '100%' } }
		/>
	);
}
