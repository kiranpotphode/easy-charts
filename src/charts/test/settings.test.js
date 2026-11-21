/**
 * External dependencies
 */
import { describe, expect, it } from 'vitest';

/**
 * Internal dependencies
 */
import {
	hasOverride,
	mergeSettings,
	removeOverrides,
	setOverride,
	toSettings,
} from '../settings';

const base = {
	legend: { show: true, position: 'bottom' },
	palette: { colors: [ '#111111', '#222222' ] },
	labels: { prefix: '' },
};

describe( 'mergeSettings', () => {
	it( 'merges groups key by key', () => {
		expect(
			mergeSettings( base, { legend: { position: 'top' } } ).legend
		).toEqual( { show: true, position: 'top' } );
	} );

	it( 'replaces lists whole', () => {
		expect(
			mergeSettings( base, { palette: { colors: [ '#333333' ] } } )
				.palette.colors
		).toEqual( [ '#333333' ] );
	} );

	it( 'ignores empty group overrides, as PHP does', () => {
		expect( mergeSettings( base, { legend: [] } ).legend ).toEqual(
			base.legend
		);
		expect( mergeSettings( base, { legend: {} } ).legend ).toEqual(
			base.legend
		);
	} );

	it( 'adds new groups and keys', () => {
		const merged = mergeSettings( base, {
			title: { text: 'Sales' },
			labels: { suffix: '%' },
		} );
		expect( merged.title ).toEqual( { text: 'Sales' } );
		expect( merged.labels ).toEqual( { prefix: '', suffix: '%' } );
	} );

	it( 'leaves its inputs untouched', () => {
		const copy = structuredClone( base );
		mergeSettings( base, { legend: { show: false } } );
		expect( base ).toEqual( copy );
	} );

	it( 'treats settings sent as a PHP empty array as none', () => {
		expect( toSettings( [] ) ).toEqual( {} );
		expect( toSettings( null ) ).toEqual( {} );
		expect( mergeSettings( base, [] ) ).toEqual( base );
	} );
} );

describe( 'overrides', () => {
	it( 'sets one value without touching others', () => {
		const settings = { legend: { show: false } };
		const next = setOverride( settings, 'legend', 'position', 'top' );
		expect( next ).toEqual( { legend: { show: false, position: 'top' } } );
		expect( settings ).toEqual( { legend: { show: false } } );
		expect( setOverride( [], 'title', 'text', 'A' ) ).toEqual( {
			title: { text: 'A' },
		} );
	} );

	it( 'knows which values are overridden', () => {
		const settings = { legend: { show: false } };
		expect( hasOverride( settings, 'legend', 'show' ) ).toBe( true );
		expect( hasOverride( settings, 'legend', 'position' ) ).toBe( false );
		expect( hasOverride( [], 'legend', 'show' ) ).toBe( false );
	} );

	it( 'removes overrides and empty groups', () => {
		const settings = {
			legend: { show: false, position: 'top' },
			labels: { suffix: '%' },
		};
		expect(
			removeOverrides( settings, [
				[ 'legend', 'show' ],
				[ 'labels', 'suffix' ],
				[ 'title', 'text' ],
			] )
		).toEqual( { legend: { position: 'top' } } );
		expect( settings.labels ).toEqual( { suffix: '%' } );
	} );
} );
