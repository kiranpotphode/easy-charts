/**
 * WordPress dependencies
 */
import { useEffect, useRef } from '@wordpress/element';

/**
 * External dependencies
 */
import jspreadsheet from 'jspreadsheet-ce';
import 'jsuites/dist/jsuites.css';
import 'jspreadsheet-ce/dist/jspreadsheet.css';

/**
 * Spreadsheet grid (jspreadsheet CE) for the chart data.
 *
 * Created once on mount from `grid`, then owns its cells; every change is reported through
 * `onChange`. Destroyed with its document event listeners on unmount. Each mount draws into a
 * fresh inner element, so a remount (React StrictMode mounts twice in development) can never
 * share DOM with the grid it replaces.
 *
 * @param {Object}                     props          Component props.
 * @param {string[][]}                 props.grid     Initial rows of cells, header first.
 * @param {(grid: string[][]) => void} props.onChange Called with all cells after each change.
 * @param {{current: Object|null}}     props.apiRef   Receives the worksheet (insertRow, ...).
 * @param {string}                     props.label    Accessible name of the grid.
 * @return {Element} Grid container.
 */
export default function DataGrid( { grid, onChange, apiRef, label } ) {
	const elementRef = useRef();
	const onChangeRef = useRef( onChange );
	onChangeRef.current = onChange;

	useEffect( () => {
		const element = elementRef.current.ownerDocument.createElement( 'div' );
		elementRef.current.appendChild( element );
		const columns = Math.max( ...grid.map( ( row ) => row.length ), 2 );
		const report = ( worksheet ) =>
			onChangeRef.current( worksheet.getData() );
		const events = [
			'onafterchanges',
			'oninsertrow',
			'ondeleterow',
			'oninsertcolumn',
			'ondeletecolumn',
			'onmoverow',
			'onmovecolumn',
			'onundo',
			'onredo',
		];

		const [ worksheet ] = jspreadsheet( element, {
			worksheets: [
				{
					data: grid,
					minDimensions: [ columns + 1, grid.length + 3 ],
					defaultColWidth: 110,
					allowComments: false,
				},
			],
			tabs: false,
			toolbar: false,
			parseFormulas: false,
			onbeforeformula: () => false,
			...Object.fromEntries(
				events.map( ( event ) => [ event, ( ws ) => report( ws ) ] )
			),
		} );
		if ( apiRef ) {
			apiRef.current = worksheet;
		}

		return () => {
			if ( apiRef ) {
				apiRef.current = null;
			}
			jspreadsheet.destroy( element, true );
			element.remove();
		};
		// Created once: later cell changes live in the grid itself.
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [] );

	return (
		<div
			ref={ elementRef }
			className="easy-charts-data-grid"
			role="group"
			aria-label={ label }
		/>
	);
}
