/**
 * External dependencies
 */
import { describe, expect, it } from 'vitest';

/**
 * Internal dependencies
 */
import {
	CHART_TYPES,
	buildOption,
	formatValue,
	isColor,
	toNumber,
	toPercentages,
	toSteps,
} from '../build-option';

const data = {
	columns: [ '', 'Kia', 'Nissan' ],
	rows: [
		[ '2008', 10, 30 ],
		[ '2009', 20, '5' ],
		[ '2010', null, 15 ],
	],
};

const settings = {
	title: { show: true, subtext: '' },
	legend: { show: true, position: 'bottom' },
	tooltip: { show: true },
	labels: { show: true, precision: 2, prefix: '', suffix: '' },
	layout: { orientation: 'vertical' },
	palette: { colors: [] },
	frame: { background: '#ffffff' },
	axis: { xLabel: '', yLabel: '' },
};

const visible = ( option ) =>
	option.series.filter( ( series ) => ! series.silent );

describe( 'helpers', () => {
	it( 'reads numbers from cells', () => {
		expect( toNumber( 5 ) ).toBe( 5 );
		expect( toNumber( ' 2.5 ' ) ).toBe( 2.5 );
		expect( toNumber( '' ) ).toBeNull();
		expect( toNumber( 'abc' ) ).toBeNull();
		expect( toNumber( null ) ).toBeNull();
		expect( toNumber( Infinity ) ).toBeNull();
	} );

	it( 'formats values with precision, prefix and suffix', () => {
		expect( formatValue( 10, { precision: 2 } ) ).toBe( '10' );
		expect( formatValue( 1.23456, { precision: 2 } ) ).toBe( '1.23' );
		expect( formatValue( 5, { prefix: '$', suffix: ' M' } ) ).toBe(
			'$5 M'
		);
		expect( formatValue( null ) ).toBe( '' );
	} );

	it( 'accepts only plain colour values', () => {
		expect( isColor( '#abc' ) ).toBe( true );
		expect( isColor( 'rgba(0, 0, 0, 0.5)' ) ).toBe( true );
		expect( isColor( 'red;background:url(x)' ) ).toBe( false );
		expect( isColor( '"><img src=x>' ) ).toBe( false );
	} );

	it( 'normalizes each category to 100 percent', () => {
		const percentages = toPercentages( [
			[ 10, 20 ],
			[ 30, null ],
		] );
		expect( percentages[ 0 ] ).toEqual( [ 25, 100 ] );
		expect( percentages[ 1 ] ).toEqual( [ 75, null ] );
	} );

	it( 'builds floating steps that start where the previous ended', () => {
		expect( toSteps( [ 10, 5, -3, null ] ) ).toEqual( {
			base: [ 0, 10, 12, null ],
			size: [ 10, 5, 3, null ],
			rising: [ true, true, false, true ],
		} );
	} );
} );

describe( 'buildOption', () => {
	it.each( CHART_TYPES )( 'builds a %s chart', ( type ) => {
		const option = buildOption( { type, data, settings, title: 'Sales' } );
		expect( option.aria.enabled ).toBe( true );
		expect( option.title.text ).toBe( 'Sales' );
		expect( option.series.length ).toBeGreaterThan( 0 );
	} );

	it( 'uses one series per data column for bar charts', () => {
		const option = buildOption( { type: 'bar', data, settings } );
		expect( option.series.map( ( s ) => s.name ) ).toEqual( [
			'Kia',
			'Nissan',
		] );
		expect( option.series[ 1 ].data ).toEqual( [ 30, 5, 15 ] );
		expect( option.xAxis.data ).toEqual( [ '2008', '2009', '2010' ] );
		expect( option.series[ 0 ].stack ).toBeUndefined();
	} );

	it( 'swaps axes for horizontal bars', () => {
		const option = buildOption( {
			type: 'bar',
			data,
			settings: { ...settings, layout: { orientation: 'horizontal' } },
		} );
		expect( option.yAxis.type ).toBe( 'category' );
		expect( option.xAxis.type ).toBe( 'value' );
	} );

	it( 'stacks stacked charts', () => {
		for ( const type of [ 'stacked-bar', 'stacked-area' ] ) {
			const option = buildOption( { type, data, settings } );
			expect( option.series.every( ( s ) => s.stack === 'total' ) ).toBe(
				true
			);
		}
	} );

	it( 'normalizes percent charts and caps the axis at 100', () => {
		const option = buildOption( { type: 'percent-bar', data, settings } );
		expect( option.yAxis.max ).toBe( 100 );
		expect( option.series[ 0 ].data[ 0 ] ).toBe( 25 );
		expect( option.series[ 1 ].data[ 0 ] ).toBe( 75 );
	} );

	it( 'draws areas for area charts', () => {
		const option = buildOption( { type: 'area', data, settings } );
		expect( option.series[ 0 ].type ).toBe( 'line' );
		expect( option.series[ 0 ].areaStyle ).toBeDefined();
	} );

	it( 'draws step-up bars on an invisible base per series', () => {
		const option = buildOption( { type: 'step-up-bar', data, settings } );
		expect( option.series ).toHaveLength( 4 );
		expect( option.series[ 0 ].silent ).toBe( true );
		expect( option.series[ 0 ].data ).toEqual( [ 0, 10, null ] );
		expect( option.series[ 1 ].data ).toEqual( [ 10, 20, null ] );
		expect( option.series[ 2 ].stack ).toBe( 'step-1' );
	} );

	it( 'splits waterfall steps into increase and decrease', () => {
		const option = buildOption( {
			type: 'waterfall',
			data: {
				columns: [ '', 'Change' ],
				rows: [
					[ 'Start', 100 ],
					[ 'Costs', -30 ],
					[ 'Sales', 20 ],
				],
			},
			settings,
			strings: { increase: 'Up', decrease: 'Down' },
		} );
		const [ base, up, down ] = option.series;
		expect( base.data ).toEqual( [ 0, 70, 70 ] );
		expect( up.data ).toEqual( [ 100, null, 20 ] );
		expect( down.data ).toEqual( [ null, 30, null ] );
		expect( option.legend.data ).toEqual( [ 'Up', 'Down' ] );
	} );

	it( 'uses rows as slices and skips empty ones for pie charts', () => {
		const option = buildOption( { type: 'pie', data, settings } );
		expect( option.series[ 0 ].data ).toEqual( [
			{ name: '2008', value: 10 },
			{ name: '2009', value: 20 },
		] );
		expect( option.xAxis ).toBeUndefined();
	} );

	it( 'gives donut and polar-area charts their shapes', () => {
		expect(
			buildOption( { type: 'donut', data, settings } ).series[ 0 ].radius
		).toEqual( [ '40%', '65%' ] );
		expect(
			buildOption( { type: 'polar-area', data, settings } ).series[ 0 ]
				.roseType
		).toBe( 'area' );
	} );

	it( 'applies palette, background and label settings safely', () => {
		const option = buildOption( {
			type: 'bar',
			data,
			settings: {
				...settings,
				palette: { colors: [ '#111111', 'bad;color', '#222' ] },
				frame: { background: 'url(javascript:x)' },
				labels: { show: false, precision: 1, prefix: '$', suffix: '' },
			},
		} );
		expect( option.color ).toEqual( [ '#111111', '#222' ] );
		expect( option.backgroundColor ).toBeUndefined();
		expect( visible( option )[ 0 ].label.show ).toBe( false );
		expect( option.tooltip.valueFormatter( 1.25 ) ).toBe( '$1.3' );
	} );

	it( 'never uses an HTML tooltip formatter', () => {
		for ( const type of CHART_TYPES ) {
			const option = buildOption( { type, data, settings } );
			expect( option.tooltip.formatter ).toBeUndefined();
		}
	} );

	it( 'hides the title when there is nothing to show', () => {
		expect(
			buildOption( { type: 'bar', data, settings } ).title.show
		).toBe( false );
	} );

	it( 'positions the legend', () => {
		const option = buildOption( {
			type: 'bar',
			data,
			settings: {
				...settings,
				legend: { show: true, position: 'right' },
			},
		} );
		expect( option.legend.orient ).toBe( 'vertical' );
		expect( option.grid.right ).toBeGreaterThan( 100 );
	} );

	it( 'falls back to a bar chart for an unknown type and copes with empty data', () => {
		const option = buildOption( {
			type: 'radar',
			data: { columns: [ '' ], rows: [] },
			settings,
		} );
		expect( option.series ).toEqual( [] );
		expect( option.xAxis.data ).toEqual( [] );
	} );
} );
