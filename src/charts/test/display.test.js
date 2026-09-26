/**
 * External dependencies
 */
import { describe, expect, it } from 'vitest';

/**
 * Internal dependencies
 */
import { withMotionPreference } from '../display';

const view = ( matches ) => ( { matchMedia: () => ( { matches } ) } );

describe( 'withMotionPreference', () => {
	it( 'turns animation off when reduced motion is preferred', () => {
		expect( withMotionPreference( { a: 1 }, view( true ) ) ).toEqual( {
			a: 1,
			animation: false,
		} );
	} );

	it( 'leaves the option alone otherwise', () => {
		const option = { a: 1 };
		expect( withMotionPreference( option, view( false ) ) ).toBe( option );
		expect( withMotionPreference( option, {} ) ).toBe( option );
	} );
} );
