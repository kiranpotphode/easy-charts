/**
 * Chart types as offered in the editor: label, icon and starter data.
 */

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Small monochrome icon.
 *
 * @param {Object}  props          Icon props.
 * @param {Element} props.children SVG shapes.
 * @return {Element} SVG icon.
 */
const Icon = ( { children } ) => (
	<svg
		viewBox="0 0 24 24"
		width="36"
		height="36"
		aria-hidden="true"
		focusable="false"
		fill="currentColor"
	>
		{ children }
	</svg>
);

const faded = { opacity: 0.45 };

export const CHART_TYPE_OPTIONS = [
	{
		value: 'bar',
		label: __( 'Bar', 'easy-charts' ),
		icon: (
			<Icon>
				<rect x="3" y="10" width="4" height="11" />
				<rect x="10" y="5" width="4" height="16" />
				<rect x="17" y="13" width="4" height="8" />
			</Icon>
		),
	},
	{
		value: 'stacked-bar',
		label: __( 'Stacked bar', 'easy-charts' ),
		icon: (
			<Icon>
				<rect x="3" y="12" width="4" height="9" />
				<rect x="3" y="7" width="4" height="5" style={ faded } />
				<rect x="10" y="10" width="4" height="11" />
				<rect x="10" y="3" width="4" height="7" style={ faded } />
				<rect x="17" y="15" width="4" height="6" />
				<rect x="17" y="10" width="4" height="5" style={ faded } />
			</Icon>
		),
	},
	{
		value: 'percent-bar',
		label: __( 'Percent bar', 'easy-charts' ),
		icon: (
			<Icon>
				<rect x="3" y="10" width="4" height="11" />
				<rect x="3" y="3" width="4" height="7" style={ faded } />
				<rect x="10" y="7" width="4" height="14" />
				<rect x="10" y="3" width="4" height="4" style={ faded } />
				<rect x="17" y="13" width="4" height="8" />
				<rect x="17" y="3" width="4" height="10" style={ faded } />
			</Icon>
		),
	},
	{
		value: 'step-up-bar',
		label: __( 'Step-up bar', 'easy-charts' ),
		icon: (
			<Icon>
				<rect x="3" y="15" width="5" height="6" />
				<rect x="9" y="9" width="5" height="6" style={ faded } />
				<rect x="15" y="3" width="5" height="6" />
			</Icon>
		),
	},
	{
		value: 'waterfall',
		label: __( 'Waterfall', 'easy-charts' ),
		icon: (
			<Icon>
				<rect x="2" y="8" width="4" height="13" />
				<rect x="7" y="4" width="4" height="4" />
				<rect x="12" y="4" width="4" height="7" style={ faded } />
				<rect x="17" y="11" width="4" height="10" />
			</Icon>
		),
	},
	{
		value: 'line',
		label: __( 'Line', 'easy-charts' ),
		icon: (
			<Icon>
				<path
					d="M3 18 9 11l4 4 8-10"
					fill="none"
					stroke="currentColor"
					strokeWidth="2"
				/>
			</Icon>
		),
	},
	{
		value: 'area',
		label: __( 'Area', 'easy-charts' ),
		icon: (
			<Icon>
				<path d="M3 21V16l6-7 4 4 8-9v17z" />
			</Icon>
		),
	},
	{
		value: 'stacked-area',
		label: __( 'Stacked area', 'easy-charts' ),
		icon: (
			<Icon>
				<path d="M3 21v-5l6-3 5 2 7-4v10z" />
				<path d="M3 16v-5l6-5 5 4 7-6v7l-7 4-5-2z" style={ faded } />
			</Icon>
		),
	},
	{
		value: 'percent-area',
		label: __( 'Percent area', 'easy-charts' ),
		icon: (
			<Icon>
				<path d="M3 21v-9l6 3 5-4 7 2v8z" />
				<path d="M3 3h18v10l-7-2-5 4-6-3z" style={ faded } />
			</Icon>
		),
	},
	{
		value: 'pie',
		label: __( 'Pie', 'easy-charts' ),
		icon: (
			<Icon>
				<path d="M12 3a9 9 0 1 0 9 9h-9z" />
				<path d="M14 1a9 9 0 0 1 9 9h-9z" style={ faded } />
			</Icon>
		),
	},
	{
		value: 'donut',
		label: __( 'Donut', 'easy-charts' ),
		icon: (
			<Icon>
				<path d="M12 3a9 9 0 1 0 9 9h-4.5A4.5 4.5 0 1 1 12 7.5z" />
				<path
					d="M13 2a9 9 0 0 1 9 9h-4.5A4.5 4.5 0 0 0 13 6.5z"
					style={ faded }
				/>
			</Icon>
		),
	},
	{
		value: 'polar-area',
		label: __( 'Polar area', 'easy-charts' ),
		icon: (
			<Icon>
				<path d="M12 12V2a10 10 0 0 1 10 10z" />
				<path d="M12 12h6a6 6 0 0 1-6 6z" style={ faded } />
				<path d="M12 12v8a8 8 0 0 1-8-8z" />
				<path d="M12 12H8a4 4 0 0 1 4-4z" style={ faded } />
			</Icon>
		),
	},
];

const PIE_TYPES = [ 'pie', 'donut', 'polar-area' ];

/**
 * Starter data so a new chart shows something to edit.
 *
 * @param {string} type Chart type.
 * @return {{columns: string[], rows: Array<Array>}} Data grid.
 */
export function sampleData( type ) {
	if ( PIE_TYPES.includes( type ) ) {
		return {
			columns: [ '', __( 'Share', 'easy-charts' ) ],
			rows: [
				[ __( 'Category A', 'easy-charts' ), 45 ],
				[ __( 'Category B', 'easy-charts' ), 30 ],
				[ __( 'Category C', 'easy-charts' ), 25 ],
			],
		};
	}
	if ( type === 'waterfall' ) {
		return {
			columns: [ '', __( 'Change', 'easy-charts' ) ],
			rows: [
				[ __( 'Start', 'easy-charts' ), 100 ],
				[ __( 'Income', 'easy-charts' ), 40 ],
				[ __( 'Costs', 'easy-charts' ), -25 ],
				[ __( 'Other', 'easy-charts' ), 10 ],
			],
		};
	}
	return {
		columns: [
			'',
			__( 'Series 1', 'easy-charts' ),
			__( 'Series 2', 'easy-charts' ),
		],
		rows: [
			[ 'Q1', 12, 8 ],
			[ 'Q2', 15, 11 ],
			[ 'Q3', 11, 14 ],
			[ 'Q4', 18, 13 ],
		],
	};
}

/**
 * A new chart document.
 *
 * @param {string} type Chart type.
 * @param {Object} data Data grid.
 * @return {Object} Chart document.
 */
export function newChart( type, data ) {
	return {
		schema: 2,
		type,
		data,
		settings: {},
		source: { templateId: null },
	};
}
