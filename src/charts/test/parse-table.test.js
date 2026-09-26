/**
 * External dependencies
 */
import { describe, expect, it } from 'vitest';

/**
 * Internal dependencies
 */
import {
	detectDelimiter,
	fromGrid,
	parseNumber,
	parseTable,
	toGrid,
	toText,
} from '../parse-table';

describe( 'parseTable', () => {
	it( 'reads cells copied from a spreadsheet (tabs)', () => {
		expect(
			parseTable( '\tKia\tNissan\n2008\t10\t11\n2009\t20\t\n' )
		).toEqual( {
			columns: [ '', 'Kia', 'Nissan' ],
			rows: [
				[ '2008', 10, 11 ],
				[ '2009', 20, null ],
			],
		} );
	} );

	it( 'reads CSV with quotes, escaped quotes and line breaks', () => {
		const data = parseTable(
			'Year,"Sales, EU","Say ""hi"""\r\n2008,"1,200.5",x\r\n"Line\nbreak",3,4'
		);
		expect( data.columns ).toEqual( [ 'Year', 'Sales, EU', 'Say "hi"' ] );
		expect( data.rows ).toEqual( [
			[ '2008', 1200.5, 'x' ],
			[ 'Line\nbreak', 3, 4 ],
		] );
	} );

	it( 'reads semicolon CSV with decimal commas', () => {
		expect( parseTable( ';A;B\nQ1;1,5;1.234,5\n' ).rows ).toEqual( [
			[ 'Q1', 1.5, 1234.5 ],
		] );
	} );

	it( 'pads short rows and skips blank lines', () => {
		expect( parseTable( 'x\ta\tb\n\n1\t2\n  \t \n' ) ).toEqual( {
			columns: [ 'x', 'a', 'b' ],
			rows: [ [ '1', 2, null ] ],
		} );
	} );

	it( 'rejects text without a series', () => {
		expect( parseTable( '' ) ).toBeNull();
		expect( parseTable( '   \n ' ) ).toBeNull();
		expect( parseTable( 'just one column\nof text' ) ).toBeNull();
		expect( parseTable( null ) ).toBeNull();
	} );
} );

describe( 'detectDelimiter', () => {
	it( 'prefers tabs, then the delimiter that splits most', () => {
		expect( detectDelimiter( 'a;b\tc' ) ).toBe( '\t' );
		expect( detectDelimiter( 'a;b;c\n1,5;2' ) ).toBe( ';' );
		expect( detectDelimiter( '"a;b",c,d' ) ).toBe( ',' );
		expect( detectDelimiter( 'single' ) ).toBe( ',' );
	} );
} );

describe( 'parseNumber', () => {
	it.each( [
		[ '42', 42 ],
		[ '-1.5', -1.5 ],
		[ '+3', 3 ],
		[ '1,234', 1234 ],
		[ '1,234,567.89', 1234567.89 ],
		[ '1.234.567,89', 1234567.89 ],
		[ '2,5', 2.5 ],
		[ '$1,200', 1200 ],
		[ '-€ 3.5', -3.5 ],
		[ '45 %', 45 ],
		[ '1 000', 1000 ],
	] )( 'reads %s', ( text, expected ) => {
		expect( parseNumber( text ) ).toBe( expected );
	} );

	it.each( [ '', 'abc', '12abc', '1.2.3', '0x1A', '1e5x', '--1' ] )(
		'rejects %s',
		( text ) => {
			expect( parseNumber( text ) ).toBeNull();
		}
	);
} );

describe( 'toText', () => {
	it( 'round-trips through parseTable', () => {
		const data = {
			columns: [ '', 'A\tB', 'Say "hi"' ],
			rows: [
				[ 'Line\nbreak', 1.5, null ],
				[ 'x', -2, 'text' ],
			],
		};
		expect( parseTable( toText( data ) ) ).toEqual( data );
	} );
} );

describe( 'fromGrid and toGrid', () => {
	it( 'drops spreadsheet padding and reads numbers', () => {
		expect(
			fromGrid( [
				[ '', 'Kia', '', '' ],
				[ '2008', '10', '', '' ],
				[ '', '', '', '' ],
				[ '2009', ' 1,5 ', '', '' ],
				[ '', '', '', '' ],
			] )
		).toEqual( {
			columns: [ '', 'Kia' ],
			rows: [
				[ '2008', 10 ],
				[ '2009', 1.5 ],
			],
		} );
	} );

	it( 'keeps a named series without values', () => {
		expect(
			fromGrid( [
				[ '', 'A', 'B' ],
				[ 'x', 1, '' ],
			] ).columns
		).toEqual( [ '', 'A', 'B' ] );
	} );

	it( 'rejects grids without a series', () => {
		expect( fromGrid( [ [ 'only labels' ], [ 'x' ] ] ) ).toBeNull();
		expect( fromGrid( [ [ '', '' ] ] ) ).toBeNull();
		expect( fromGrid( 'nope' ) ).toBeNull();
	} );

	it( 'round-trips', () => {
		const data = {
			columns: [ '', 'A' ],
			rows: [
				[ 'x', 1.5 ],
				[ 'y', null ],
				[ 'z', 'n/a' ],
			],
		};
		expect( toGrid( data ) ).toEqual( [
			[ '', 'A' ],
			[ 'x', '1.5' ],
			[ 'y', '' ],
			[ 'z', 'n/a' ],
		] );
		expect( fromGrid( toGrid( data ) ) ).toEqual( data );
	} );
} );
