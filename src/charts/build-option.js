/**
 * Turns an Easy Charts document into an ECharts option.
 *
 * Pure function with no DOM or WordPress dependencies, shared by the front end and the block
 * editor. The data grid keeps the 1.x orientation: column 0 holds the category (row) labels and
 * every later column is one series.
 *
 * Security: labels, prefixes and suffixes come from chart data, so tooltips only ever use
 * `valueFormatter` (ECharts HTML-escapes its output) and never a custom HTML `formatter`.
 * Colours end up in style attributes, so only real colour values are passed through.
 */

const AXIS_TYPES = [
	'bar',
	'stacked-bar',
	'percent-bar',
	'step-up-bar',
	'waterfall',
	'line',
	'area',
	'stacked-area',
	'percent-area',
];

const PIE_TYPES = [ 'pie', 'donut', 'polar-area' ];

export const CHART_TYPES = [ ...AXIS_TYPES, ...PIE_TYPES ];

const DEFAULT_STRINGS = {
	increase: 'Increase',
	decrease: 'Decrease',
};

const COLOR_PATTERN =
	/^(#[0-9a-f]{3,8}|(rgb|rgba|hsl|hsla)\(\s*[\d.%\s,/-]+\)|transparent)$/i;

/**
 * Read a cell as a number.
 *
 * @param {string|number|null|undefined} cell Cell value.
 * @return {number|null} The number, or null for blank and non-numeric cells.
 */
export function toNumber( cell ) {
	if ( typeof cell === 'number' ) {
		return Number.isFinite( cell ) ? cell : null;
	}
	if ( typeof cell === 'string' && cell.trim() !== '' ) {
		const value = Number( cell.trim() );
		return Number.isFinite( value ) ? value : null;
	}
	return null;
}

/**
 * Whether a string is a plain colour value.
 *
 * @param {unknown} value Candidate colour.
 * @return {boolean} True for hex, rgb(a), hsl(a) or transparent.
 */
export function isColor( value ) {
	return typeof value === 'string' && COLOR_PATTERN.test( value.trim() );
}

/**
 * Format a number for labels and tooltips.
 *
 * @param {number|null} value  Value.
 * @param {Object}      labels Label settings: precision, prefix, suffix.
 * @return {string} Formatted value, or an empty string for missing values.
 */
export function formatValue( value, labels = {} ) {
	if ( value === null || value === undefined || Number.isNaN( value ) ) {
		return '';
	}
	const precision = Math.min(
		Math.max( parseInt( labels.precision ?? 2, 10 ) || 0, 0 ),
		10
	);
	const rounded = Number( Number( value ).toFixed( precision ) );
	return `${ labels.prefix ?? '' }${ rounded }${ labels.suffix ?? '' }`;
}

/**
 * Scale each category (row) so its series add up to 100.
 *
 * @param {Array<Array<number|null>>} series Values per series.
 * @return {Array<Array<number|null>>} Percentages per series.
 */
export function toPercentages( series ) {
	const length = series.length ? series[ 0 ].length : 0;
	const totals = Array.from( { length }, ( _, row ) =>
		series.reduce(
			( sum, values ) => sum + Math.abs( values[ row ] ?? 0 ),
			0
		)
	);
	return series.map( ( values ) =>
		values.map( ( value, row ) =>
			value === null || totals[ row ] === 0
				? null
				: ( Math.abs( value ) / totals[ row ] ) * 100
		)
	);
}

/**
 * Floating bars for step-up and waterfall charts: each bar starts where the previous one ended.
 *
 * @param {Array<number|null>} values Values in drawing order.
 * @return {{base: Array<number|null>, size: Array<number|null>, rising: Array<boolean>}} Invisible
 *         base heights, visible bar heights, and whether each step went up.
 */
export function toSteps( values ) {
	let total = 0;
	const base = [];
	const size = [];
	const rising = [];
	values.forEach( ( value ) => {
		if ( value === null ) {
			base.push( null );
			size.push( null );
			rising.push( true );
			return;
		}
		const start = total;
		total += value;
		base.push( Math.min( start, total ) );
		size.push( Math.abs( value ) );
		rising.push( value >= 0 );
	} );
	return { base, size, rising };
}

/**
 * Title component. A custom `title.text` setting replaces the chart's own title.
 *
 * @param {string} title    Chart title.
 * @param {Object} settings Resolved settings.
 * @return {Object} ECharts title option.
 */
function buildTitle( title, settings ) {
	const text = settings.title?.text || title || '';
	const subtext = settings.title?.subtext ?? '';
	const show = settings.title?.show !== false && Boolean( text || subtext );
	return { show, text, subtext, left: 'center' };
}

/**
 * Legend component and the space it takes on each side.
 *
 * @param {Object}  settings Resolved settings.
 * @param {boolean} hasTitle Whether a title is shown.
 * @return {{legend: Object, space: Object}} Legend option and reserved space.
 */
function buildLegend( settings, hasTitle ) {
	const show = settings.legend?.show !== false;
	const position = settings.legend?.position ?? 'bottom';
	let titleSpace = 0;
	if ( hasTitle ) {
		titleSpace = settings.title?.subtext ? 64 : 40;
	}
	const space = { top: titleSpace, bottom: 0, left: 0, right: 0 };
	const legend = { show, type: 'scroll' };

	if ( ! show ) {
		return { legend, space };
	}
	if ( position === 'left' || position === 'right' ) {
		Object.assign( legend, {
			orient: 'vertical',
			[ position ]: 8,
			top: 'middle',
		} );
		space[ position ] = 120;
	} else if ( position === 'top' ) {
		Object.assign( legend, { top: titleSpace + 4, left: 'center' } );
		space.top = titleSpace + 32;
	} else {
		Object.assign( legend, { bottom: 4, left: 'center' } );
		space.bottom = 32;
	}
	return { legend, space };
}

/**
 * Parsed chart input shared by the chart builders.
 *
 * @typedef {Object} ChartInput
 * @property {string}                    type       Chart type slug.
 * @property {string[]}                  categories Row labels (column 0).
 * @property {string[]}                  names      Series names (header of columns 1..n).
 * @property {Array<Array<number|null>>} series     Values per series.
 * @property {Object}                    settings   Resolved settings.
 * @property {Object}                    space      Space reserved around the plot by title and legend.
 * @property {Object}                    strings    Translated UI strings.
 */

/**
 * Chart for types drawn on category/value axes.
 *
 * @param {ChartInput} input Parsed chart input.
 * @return {Object} Axis, grid and series options.
 */
function buildAxisChart( input ) {
	const { type, categories, names, series, settings, space, strings } = input;
	const labels = settings.labels ?? {};
	const showLabels = labels.show !== false;
	const horizontal = settings.layout?.orientation === 'horizontal';
	const percent = type === 'percent-bar' || type === 'percent-area';
	const stacked =
		[ 'stacked-bar', 'stacked-area' ].includes( type ) || percent;
	const values = percent ? toPercentages( series ) : series;
	const format = percent
		? ( value ) =>
				formatValue( value, {
					precision: labels.precision,
					suffix: '%',
				} )
		: ( value ) => formatValue( value, labels );

	const label = {
		show: showLabels,
		position: stacked ? 'inside' : 'top',
		formatter: ( params ) => format( params.value ),
	};
	if ( horizontal && ! stacked ) {
		label.position = 'right';
	}

	let chartSeries;
	let legendNames = names;

	if ( type === 'waterfall' ) {
		// Rises and falls each get their own two-series stack (invisible base + bar), drawn in
		// the same slot. A single three-series stack would break: with stackStrategy "all" each
		// value stacks on the series just before it, which is empty for half of the steps.
		const steps = toSteps( values[ 0 ] ?? [] );
		const pick = ( list, up ) =>
			list.map( ( value, i ) =>
				steps.rising[ i ] === up ? value : null
			);
		legendNames = [ strings.increase, strings.decrease ];
		chartSeries = [
			[ 'rise', strings.increase, true ],
			[ 'fall', strings.decrease, false ],
		].flatMap( ( [ stack, name, up ] ) => {
			// Falling bars are drawn with their size; labels and tooltips show the signed change.
			const signed = ( value ) =>
				format( up || value === null ? value : -value );
			return [
				invisibleBase( stack, pick( steps.base, up ) ),
				{
					type: 'bar',
					name,
					stack,
					stackStrategy: 'all',
					barGap: '-100%',
					label: {
						...label,
						formatter: ( params ) => signed( params.value ),
					},
					tooltip: { valueFormatter: signed },
					data: pick( steps.size, up ),
				},
			];
		} );
	} else if ( type === 'step-up-bar' ) {
		// As in 1.x (uvCharts): within each category, every series starts where the previous
		// series ended, and the steps stand side by side.
		const steps = categories.map( ( _, row ) =>
			toSteps( values.map( ( data ) => data[ row ] ) )
		);
		chartSeries = values.flatMap( ( _, i ) => {
			const stack = `step-${ i }`;
			return [
				invisibleBase(
					stack,
					steps.map( ( step ) => step.base[ i ] )
				),
				{
					type: 'bar',
					name: names[ i ],
					stack,
					stackStrategy: 'all',
					label,
					data: steps.map( ( step ) => step.size[ i ] ),
				},
			];
		} );
	} else {
		const isBar = type.endsWith( 'bar' );
		chartSeries = values.map( ( data, i ) => ( {
			type: isBar ? 'bar' : 'line',
			name: names[ i ],
			data,
			label,
			...( stacked ? { stack: 'total' } : {} ),
			...( isBar ? {} : { connectNulls: false } ),
			...( type.endsWith( 'area' )
				? { areaStyle: { opacity: 0.4 } }
				: {} ),
		} ) );
	}

	// xLabel names the category axis and yLabel the value axis, whichever way the chart runs.
	// Horizontal charts list categories top to bottom, as 1.x did.
	const categoryAxis = {
		type: 'category',
		data: categories,
		name: settings.axis?.xLabel || '',
		nameLocation: 'middle',
		nameGap: horizontal ? 48 : 32,
		...( horizontal ? { inverse: true } : {} ),
	};
	const valueAxis = {
		type: 'value',
		name: settings.axis?.yLabel || '',
		nameLocation: 'middle',
		nameGap: horizontal ? 32 : 48,
		...( percent
			? { max: 100, axisLabel: { formatter: '{value}%' } }
			: {
					axisLabel: {
						formatter: ( value ) => formatValue( value, labels ),
					},
				} ),
	};

	return {
		grid: {
			left: 16 + space.left,
			right: 16 + space.right,
			top: 24 + space.top,
			bottom: 16 + space.bottom,
			outerBoundsMode: 'same',
			outerBoundsContain: 'all',
		},
		xAxis: horizontal ? valueAxis : categoryAxis,
		yAxis: horizontal ? categoryAxis : valueAxis,
		series: chartSeries,
		legendNames,
		tooltip: {
			trigger: 'axis',
			axisPointer: {
				type:
					type.endsWith( 'bar' ) || type === 'waterfall'
						? 'shadow'
						: 'line',
			},
			valueFormatter: format,
		},
	};
}

/**
 * Invisible series that lifts floating bars to their starting height.
 *
 * @param {string}             stack Stack id shared with the visible bars.
 * @param {Array<number|null>} data  Base heights.
 * @return {Object} ECharts series.
 */
function invisibleBase( stack, data ) {
	return {
		type: 'bar',
		stack,
		stackStrategy: 'all',
		data,
		silent: true,
		itemStyle: { color: 'transparent', borderColor: 'transparent' },
		emphasis: { disabled: true },
		tooltip: { show: false },
		label: { show: false },
	};
}

/**
 * Chart for pie, donut and polar-area types. Slices are rows; values come from the first series.
 *
 * @param {ChartInput} input Parsed chart input.
 * @return {Object} Series and tooltip options.
 */
function buildPieChart( input ) {
	const { type, categories, names, series, settings, space } = input;
	const labels = settings.labels ?? {};
	const format = ( value ) => formatValue( value, labels );
	const values = series[ 0 ] ?? [];
	const data = categories
		.map( ( name, i ) => ( { name, value: values[ i ] } ) )
		.filter( ( slice ) => slice.value !== null && slice.value > 0 );

	const radius = {
		pie: '60%',
		donut: [ '40%', '65%' ],
		'polar-area': [ '15%', '65%' ],
	}[ type ];

	const centerY = 50 + ( space.top - space.bottom ) / 8;

	return {
		series: [
			{
				type: 'pie',
				name: names[ 0 ] ?? '',
				radius,
				center: [ '50%', `${ centerY }%` ],
				...( type === 'polar-area' ? { roseType: 'area' } : {} ),
				data,
				label: {
					show: labels.show !== false,
					formatter: ( params ) =>
						`${ params.name }: ${ format( params.value ) }`,
				},
			},
		],
		legendNames: data.map( ( slice ) => slice.name ),
		tooltip: { trigger: 'item', valueFormatter: format },
	};
}

/**
 * Build the ECharts option for a chart.
 *
 * @param {Object} input           Chart input.
 * @param {string} input.type      Chart type slug.
 * @param {Object} input.data      Data grid: { columns: string[], rows: Array<Array> }.
 * @param {Object} input.settings  Resolved settings (defaults, global, template and overrides merged).
 * @param {string} [input.title]   Chart title.
 * @param {Object} [input.strings] Translated UI strings (increase, decrease).
 * @return {Object} ECharts option.
 */
export function buildOption( {
	type,
	data,
	settings = {},
	title = '',
	strings = {},
} ) {
	const chartType = CHART_TYPES.includes( type ) ? type : 'bar';
	const columns = data?.columns ?? [];
	const rows = data?.rows ?? [];
	const categories = rows.map( ( row ) => String( row[ 0 ] ?? '' ) );
	const names = columns.slice( 1 ).map( String );
	const series = names.map( ( _, i ) =>
		rows.map( ( row ) => toNumber( row[ i + 1 ] ) )
	);

	const titleOption = buildTitle( title, settings );
	const { legend, space } = buildLegend( settings, titleOption.show );

	const input = {
		type: chartType,
		categories,
		names,
		series,
		settings,
		space,
		strings: { ...DEFAULT_STRINGS, ...strings },
	};
	const chart = PIE_TYPES.includes( chartType )
		? buildPieChart( input )
		: buildAxisChart( input );

	const colors = ( settings.palette?.colors ?? [] ).filter( isColor );
	const background = settings.frame?.background;

	return {
		aria: { enabled: true },
		...( isColor( background ) ? { backgroundColor: background } : {} ),
		...( colors.length ? { color: colors } : {} ),
		title: titleOption,
		legend: { ...legend, data: chart.legendNames },
		tooltip: { show: settings.tooltip?.show !== false, ...chart.tooltip },
		...( chart.grid ? { grid: chart.grid } : {} ),
		...( chart.xAxis ? { xAxis: chart.xAxis, yAxis: chart.yAxis } : {} ),
		series: chart.series,
	};
}
