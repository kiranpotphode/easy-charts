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
	describeChart,
	resolveFont,
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

describe( 'resolveFont', () => {
	it( 'uses the site font by default, and generic stacks otherwise', () => {
		expect( resolveFont( undefined, '"Inter", sans-serif' ) ).toEqual( {
			family: '"Inter", sans-serif',
			size: 12,
		} );
		expect( resolveFont( { family: 'serif', size: 18 }, 'Inter' ) ).toEqual(
			{
				family: "Georgia, 'Times New Roman', Times, serif",
				size: 18,
			}
		);
		expect( resolveFont( { family: 'monospace' } ).family ).toMatch(
			/monospace$/
		);
		// No site font (e.g. server-side): a system sans-serif.
		expect( resolveFont( { family: 'site' } ).family ).toMatch(
			/sans-serif$/
		);
	} );

	it( 'ignores unknown families and clamps the size', () => {
		for ( const family of [
			'Comic Sans',
			'constructor',
			'__proto__',
			7,
		] ) {
			expect( resolveFont( { family }, 'Inter' ).family ).toBe( 'Inter' );
		}
		expect( resolveFont( { size: 200 } ).size ).toBe( 32 );
		expect( resolveFont( { size: 2 } ).size ).toBe( 8 );
		expect( resolveFont( { size: '15' } ).size ).toBe( 15 );
		expect( resolveFont( { size: 'big' } ).size ).toBe( 12 );
	} );
} );

describe( 'font, curves, area opacity and donut hole', () => {
	const option = ( type, extra ) =>
		buildOption( {
			type,
			data,
			settings: { ...settings, ...extra },
			title: 'Sales',
			siteFont: 'Inter',
		} );

	it( 'applies the font to the title, legend, axes, labels and tooltip', () => {
		const result = option( 'bar', { font: { family: 'site', size: 14 } } );
		expect( result.textStyle ).toEqual( {
			fontFamily: 'Inter',
			fontSize: 14,
		} );
		expect( result.title.textStyle.fontSize ).toBe( 21 );
		expect( result.title.subtextStyle.fontSize ).toBe( 14 );
		expect( result.legend.textStyle.fontSize ).toBe( 14 );
		expect( result.xAxis.axisLabel.fontSize ).toBe( 14 );
		expect( result.yAxis.axisLabel.fontFamily ).toBe( 'Inter' );
		expect( result.series[ 0 ].label.fontSize ).toBe( 14 );
		expect( result.tooltip.textStyle.fontSize ).toBe( 14 );
	} );

	it( 'curves lines and areas only when asked', () => {
		expect( option( 'line' ).series[ 0 ].smooth ).toBe( false );
		expect(
			option( 'stacked-area', { line: { smooth: true } } ).series[ 0 ]
				.smooth
		).toBe( true );
		expect(
			option( 'line', { line: { smooth: 'yes' } } ).series[ 0 ].smooth
		).toBe( false );
		expect(
			option( 'bar', { line: { smooth: true } } ).series[ 0 ]
		).not.toHaveProperty( 'smooth' );
	} );

	it( 'sets the area opacity within 0 to 1', () => {
		const opacity = ( value ) =>
			option( 'area', { area: { opacity: value } } ).series[ 0 ].areaStyle
				.opacity;
		expect( opacity( undefined ) ).toBe( 0.4 );
		expect( opacity( 0.75 ) ).toBe( 0.75 );
		expect( opacity( 3 ) ).toBe( 1 );
		expect( opacity( 'x' ) ).toBe( 0.4 );
	} );

	it( 'sizes the donut hole as a fraction of the outer radius', () => {
		const radius = ( value ) =>
			option( 'donut', { donut: { hole: value } } ).series[ 0 ].radius;
		expect( radius( undefined ) ).toEqual( [ '39%', '65%' ] );
		expect( radius( 0.4 ) ).toEqual( [ '26%', '65%' ] );
		expect( radius( 0 ) ).toEqual( [ '7%', '65%' ] );
		expect( radius( 1 ) ).toEqual( [ '59%', '65%' ] );
	} );
} );

describe( 'describeChart', () => {
	const strings = {
		types: { 'stacked-bar': 'Stacked bar' },
		chartLabel: '%1$s (%2$s chart).',
		untitledChart: '%s chart.',
		tableNote: 'The values are in the table that follows.',
	};

	it( 'names the title and type, and points to the table', () => {
		expect( describeChart( 'stacked-bar', 'Sales', strings ) ).toBe(
			'Sales (Stacked bar chart). The values are in the table that follows.'
		);
	} );

	it( 'handles untitled charts, no table and reordered placeholders', () => {
		expect(
			describeChart( 'stacked-bar', '', { ...strings, tableNote: '' } )
		).toBe( 'Stacked bar chart.' );
		expect(
			describeChart( 'pie', 'Share', {
				...strings,
				chartLabel: '%2$s: %1$s',
				tableNote: '',
			} )
		).toBe( 'pie: Share' );
	} );

	it( 'replaces the ECharts description, using the custom title', () => {
		const option = buildOption( {
			type: 'line',
			data,
			settings: { ...settings, title: { text: 'Custom' } },
			title: 'Post title',
			strings: { types: { line: 'Line' } },
		} );
		expect( option.aria.label.description ).toBe( 'Custom (Line chart).' );
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

	it.each( [ 'bar', 'line', 'area', 'step-up-bar', 'waterfall' ] )(
		'swaps axes for a horizontal %s chart',
		( type ) => {
			const option = buildOption( {
				type,
				data,
				settings: {
					...settings,
					layout: { orientation: 'horizontal' },
					axis: { xLabel: 'Year', yLabel: 'Units' },
				},
			} );
			expect( option.yAxis.type ).toBe( 'category' );
			expect( option.yAxis.inverse ).toBe( true );
			expect( option.yAxis.name ).toBe( 'Year' );
			expect( option.xAxis.type ).toBe( 'value' );
			expect( option.xAxis.name ).toBe( 'Units' );
		}
	);

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

	it( 'steps up across the series within each category', () => {
		const option = buildOption( { type: 'step-up-bar', data, settings } );
		const [ kiaBase, kia, nissanBase, nissan ] = option.series;
		expect( option.series ).toHaveLength( 4 );
		expect( kiaBase.silent ).toBe( true );
		expect( kiaBase.data ).toEqual( [ 0, 0, null ] );
		expect( kia.data ).toEqual( [ 10, 20, null ] );
		// Nissan starts where Kia ended in the same year; 2010 has no Kia value.
		expect( nissanBase.data ).toEqual( [ 10, 20, 0 ] );
		expect( nissan.data ).toEqual( [ 30, 5, 15 ] );
		expect( nissan.stack ).toBe( 'step-1' );
	} );

	it( 'stacks floating bars on bases below zero', () => {
		for ( const type of [ 'step-up-bar', 'waterfall' ] ) {
			const option = buildOption( { type, data, settings } );
			expect(
				option.series.every( ( s ) => s.stackStrategy === 'all' )
			).toBe( true );
		}
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
		const [ riseBase, up, fallBase, down ] = option.series;
		expect( riseBase.data ).toEqual( [ 0, null, 70 ] );
		expect( up.data ).toEqual( [ 100, null, 20 ] );
		expect( fallBase.data ).toEqual( [ null, 70, null ] );
		expect( down.data ).toEqual( [ null, 30, null ] );
		// Each visible series stacks only on its own base, in the same slot.
		expect( [ up.stack, down.stack ] ).toEqual( [ 'rise', 'fall' ] );
		expect( down.barGap ).toBe( '-100%' );
		// Falling steps are labelled with their signed change.
		expect( down.label.formatter( { value: 30 } ) ).toBe( '-30' );
		expect( down.tooltip.valueFormatter( 30 ) ).toBe( '-30' );
		expect( up.label.formatter( { value: 20 } ) ).toBe( '20' );
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
		).toEqual( [ '39%', '65%' ] );
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
			option.series.forEach( ( series ) =>
				expect( series.tooltip?.formatter ).toBeUndefined()
			);
		}
	} );

	it( 'prefers a custom title text over the chart title', () => {
		const option = buildOption( {
			type: 'bar',
			data,
			settings: { ...settings, title: { text: 'Custom' } },
			title: 'Post title',
		} );
		expect( option.title.text ).toBe( 'Custom' );
		expect( option.title.show ).toBe( true );
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

describe( 'security', () => {
	// ECharts inserts a tooltip `formatter`'s output as HTML; `valueFormatter` output is escaped.
	// Chart data is untrusted, so tooltips must only ever use `valueFormatter`.
	const layouts = [ 'vertical', 'horizontal' ];

	it.each(
		CHART_TYPES.flatMap( ( type ) => layouts.map( ( l ) => [ type, l ] ) )
	)(
		'%s (%s) tooltips never use an HTML formatter',
		( type, orientation ) => {
			const option = buildOption( {
				type,
				data,
				settings: { ...settings, layout: { orientation } },
				title: '<img src=x onerror=alert(1)>',
			} );

			expect( option.tooltip.formatter ).toBeUndefined();
			expect( typeof option.tooltip.valueFormatter ).toBe( 'function' );
			for ( const series of option.series ) {
				expect( series.tooltip?.formatter ).toBeUndefined();
			}
		}
	);
} );
