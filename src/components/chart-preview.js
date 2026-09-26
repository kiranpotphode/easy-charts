/**
 * WordPress dependencies
 */
import { useEffect, useMemo, useRef, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { buildOption } from '../charts/build-option';
import { hideDrawing, withMotionPreference } from '../charts/display';
import { getEditorData, resolveSettings } from './editor-data';
import './components.css';

/**
 * Chart drawn with ECharts, the same way the front end draws it.
 *
 * @param {Object} props            Component props.
 * @param {Object} props.doc        Chart document.
 * @param {Object} [props.settings] Effective settings from the server (embedded charts, whose
 *                                  template the editor can't resolve); otherwise resolved here.
 * @param {string} props.title      Title when the settings have none (e.g. the chart post title).
 * @return {Element} Preview.
 */
export default function ChartPreview( {
	doc,
	settings: resolved,
	title = '',
} ) {
	const boxRef = useRef();
	const chartRef = useRef();
	// The font of the text around the preview, for the "site font" setting.
	const [ siteFont, setSiteFont ] = useState( '' );
	const settings = useMemo(
		() => resolved ?? resolveSettings( doc ),
		[ doc, resolved ]
	);
	const hasData = doc?.data?.rows?.length > 0;

	const option = useMemo(
		() =>
			buildOption( {
				type: doc?.type,
				data: doc?.data,
				settings,
				title: settings.title?.text || title,
				strings: getEditorData().strings,
				siteFont,
			} ),
		[ doc, settings, title, siteFont ]
	);

	useEffect( () => {
		const echarts = window.easyChartsECharts;
		const box = boxRef.current;
		if ( ! hasData || ! echarts || ! box ) {
			return;
		}
		const chart = echarts.init( box, null, { renderer: 'svg' } );
		hideDrawing( box );
		chartRef.current = chart;

		// The editor canvas is an iframe: read the style with that window.
		const view = box.ownerDocument.defaultView;
		setSiteFont( view?.getComputedStyle( box ).fontFamily ?? '' );

		// Observe with the iframe window's ResizeObserver too.
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
		const chart = chartRef.current;
		chart?.setOption(
			withMotionPreference(
				option,
				boxRef.current?.ownerDocument.defaultView
			),
			true
		);
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
