/**
 * Smoke test: every option the builder produces renders in real ECharts (server-side SVG).
 *
 * Catches options ECharts rejects or crashes on, which the option-shape tests cannot see.
 */

/**
 * External dependencies
 */
import * as echarts from 'echarts';
import { afterEach, describe, expect, it } from 'vitest';

/**
 * Internal dependencies
 */
import { CHART_TYPES, buildOption } from '../build-option';

const settings = {
	title: { show: true, subtext: 'Subtitle' },
	legend: { show: true, position: 'bottom' },
	tooltip: { show: true },
	labels: { show: true, precision: 1, prefix: '$', suffix: '' },
	layout: { orientation: 'vertical' },
	palette: { colors: [ '#1a73e8', '#34a853' ] },
	frame: { background: '#ffffff' },
	axis: { xLabel: 'Quarter', yLabel: 'Revenue' },
};

const data = {
	columns: [ '', 'North', 'South' ],
	rows: [
		[ 'Q1', 120, 95 ],
		[ 'Q2', -35, 102 ],
		[ 'Q3', 128, null ],
		[ 'Q4', 150, 121 ],
	],
};

let chart;

/**
 * Render an option to an SVG string.
 *
 * @param {Object} option ECharts option.
 * @return {string} SVG markup.
 */
function renderSvg( option ) {
	chart = echarts.init( null, null, {
		renderer: 'svg',
		ssr: true,
		width: 600,
		height: 400,
	} );
	chart.setOption( { ...option, animation: false } );
	return chart.renderToSVGString();
}

afterEach( () => {
	chart?.dispose();
	chart = null;
} );

describe( 'rendering with ECharts', () => {
	it.each( CHART_TYPES )( 'renders a %s chart', ( type ) => {
		const svg = renderSvg(
			buildOption( { type, data, settings, title: 'Revenue' } )
		);

		expect( svg ).toMatch( /^<svg/ );
		expect( svg ).toContain( 'Revenue' );
		expect( svg.length ).toBeGreaterThan( 2000 );
	} );

	it.each( [ 'stacked-bar', 'line', 'step-up-bar', 'waterfall' ] )(
		'renders a horizontal %s chart',
		( type ) => {
			const svg = renderSvg(
				buildOption( {
					type,
					data,
					settings: {
						...settings,
						layout: { orientation: 'horizontal' },
					},
				} )
			);
			expect( svg ).toMatch( /^<svg/ );
		}
	);

	it.each( [ 'waterfall', 'step-up-bar' ] )(
		'lays out every floating %s bar, rising or falling',
		( type ) => {
			renderSvg(
				buildOption( {
					type,
					data: {
						columns: [ '', 'Change', 'Other' ],
						rows: [
							[ 'Start', 100, 5 ],
							[ 'Costs', -130, -10 ],
							[ 'Income', 40, 20 ],
						],
					},
					settings,
				} )
			);
			const visible = chart
				.getModel()
				.getSeries()
				.filter( ( series ) => ! series.get( 'silent' ) );
			const heights = visible.flatMap( ( series ) => {
				const seriesData = series.getData();
				return seriesData
					.mapArray( ( i ) => ( {
						value: seriesData.get(
							series.getData().mapDimension( 'y' ),
							i
						),
						layout: seriesData.getItemLayout( i ),
					} ) )
					.filter( ( item ) => ! Number.isNaN( item.value ) )
					.map( ( item ) => Math.abs( item.layout?.height ?? 0 ) );
			} );
			// Waterfall: 3 steps; step-up: 3 categories x 2 series.
			expect( heights ).toHaveLength( type === 'waterfall' ? 3 : 6 );
			heights.forEach( ( height ) =>
				expect( height ).toBeGreaterThan( 0 )
			);
		}
	);

	it( 'renders a chart with no rows', () => {
		const svg = renderSvg(
			buildOption( {
				type: 'bar',
				data: { columns: [ '' ], rows: [] },
				settings,
				title: 'Empty',
			} )
		);
		expect( svg ).toContain( 'Empty' );
	} );
} );
