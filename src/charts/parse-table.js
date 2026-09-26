/**
 * Turns pasted spreadsheet data into a chart data grid, and back into text.
 *
 * Excel, Numbers and Google Sheets copy cells as tab-separated text; CSV files use commas or, in
 * many European locales, semicolons. Quoted fields may contain delimiters, quotes ("") and line
 * breaks. The first row holds the series names and the first column the row labels, as in the
 * chart data grid.
 */

const DELIMITERS = [ '\t', ';', ',' ];

/**
 * Split text into rows of raw cells.
 *
 * @param {string} text      Text to parse.
 * @param {string} delimiter Cell delimiter.
 * @return {string[][]} Rows of cells.
 */
function split( text, delimiter ) {
	const rows = [];
	let row = [];
	let cell = '';
	let quoted = false;

	for ( let i = 0; i < text.length; i++ ) {
		const char = text[ i ];
		if ( quoted ) {
			if ( char === '"' && text[ i + 1 ] === '"' ) {
				cell += '"';
				i++;
			} else if ( char === '"' ) {
				quoted = false;
			} else {
				cell += char;
			}
		} else if ( char === '"' && cell.trim() === '' ) {
			quoted = true;
			cell = '';
		} else if ( char === delimiter ) {
			row.push( cell );
			cell = '';
		} else if ( char === '\n' || char === '\r' ) {
			if ( char === '\r' && text[ i + 1 ] === '\n' ) {
				i++;
			}
			row.push( cell );
			rows.push( row );
			row = [];
			cell = '';
		} else {
			cell += char;
		}
	}
	row.push( cell );
	rows.push( row );

	return rows;
}

/**
 * Pick the delimiter: tabs win (spreadsheet copy), otherwise the one that splits the first line
 * into the most cells.
 *
 * @param {string} text Text to parse.
 * @return {string} Delimiter.
 */
export function detectDelimiter( text ) {
	if ( text.includes( '\t' ) ) {
		return '\t';
	}
	const firstLine = split( text, '\n' )[ 0 ][ 0 ] ?? '';
	let best = ',';
	let bestCount = 1;
	DELIMITERS.slice( 1 ).forEach( ( delimiter ) => {
		const count = split( firstLine, delimiter )[ 0 ].length;
		if ( count > bestCount ) {
			best = delimiter;
			bestCount = count;
		}
	} );
	return best;
}

/**
 * Read a pasted cell as a number where it clearly is one: "1234", "-1.5", "1,234.5" (thousands
 * separators), "1.234,5" or "1,5" (decimal comma), with an optional currency sign or % sign.
 *
 * @param {string} text Trimmed cell text.
 * @return {number|null} The number, or null if the cell is not numeric.
 */
export function parseNumber( text ) {
	const bare = text
		.replace( /^[-+]?\s*[$€£¥₹]\s*/, ( sign ) =>
			sign.replace( /[^-+]/g, '' )
		)
		.replace( /\s*%$/, '' )
		.replace( /\s/g, '' );
	if ( bare === '' ) {
		return null;
	}
	let normalized = null;
	if ( /^[-+]?\d+(\.\d+)?$/.test( bare ) ) {
		normalized = bare;
	} else if ( /^[-+]?\d{1,3}(,\d{3})+(\.\d+)?$/.test( bare ) ) {
		normalized = bare.replace( /,/g, '' );
	} else if ( /^[-+]?\d{1,3}(\.\d{3})+(,\d+)?$/.test( bare ) ) {
		normalized = bare.replace( /\./g, '' ).replace( ',', '.' );
	} else if ( /^[-+]?\d+,\d+$/.test( bare ) ) {
		normalized = bare.replace( ',', '.' );
	}
	return normalized === null ? null : Number( normalized );
}

/**
 * Parse pasted text into a chart data grid.
 *
 * @param {string} text Pasted text.
 * @return {{columns: string[], rows: Array<Array<string|number|null>>}|null} Data grid, or null
 *         when there is no header row with at least one series.
 */
export function parseTable( text ) {
	if ( typeof text !== 'string' || text.trim() === '' ) {
		return null;
	}
	const lines = split( text, detectDelimiter( text ) ).filter( ( row ) =>
		row.some( ( cell ) => cell.trim() !== '' )
	);
	if ( lines.length === 0 ) {
		return null;
	}

	const width = Math.max( ...lines.map( ( row ) => row.length ) );
	if ( width < 2 ) {
		return null;
	}
	const [ header, ...body ] = lines.map( ( row ) =>
		Array.from( { length: width }, ( _, i ) => ( row[ i ] ?? '' ).trim() )
	);

	return {
		columns: header,
		rows: body.map( ( [ label, ...cells ] ) => [
			label,
			...cells.map( ( cell ) => {
				if ( cell === '' ) {
					return null;
				}
				return parseNumber( cell ) ?? cell;
			} ),
		] ),
	};
}

/**
 * Write a data grid as tab-separated text, quoting cells that need it.
 *
 * @param {{columns: string[], rows: Array<Array>}} data Data grid.
 * @return {string} Text that parseTable() reads back.
 */
export function toText( data ) {
	const quote = ( cell ) => {
		const text = cell === null || cell === undefined ? '' : String( cell );
		return /[\t\n\r"]/.test( text )
			? `"${ text.replace( /"/g, '""' ) }"`
			: text;
	};
	return [ data?.columns ?? [], ...( data?.rows ?? [] ) ]
		.map( ( row ) => row.map( quote ).join( '\t' ) )
		.join( '\n' );
}
