/**
 * Internal dependencies
 */
import { DEFAULT_ATTRIBUTES } from '../constants';

describe( 'variation selector fallback attributes', () => {
	it( 'uses non-persisted term IDs for editor preview data', () => {
		const termIds = DEFAULT_ATTRIBUTES.flatMap( ( attribute ) =>
			attribute.terms.map( ( term ) => term.id )
		);

		expect( termIds ).not.toHaveLength( 0 );
		expect( termIds.every( ( termId ) => termId < 0 ) ).toBe( true );
	} );

	it( 'keeps preview term IDs unique across attributes', () => {
		const termIds = DEFAULT_ATTRIBUTES.flatMap( ( attribute ) =>
			attribute.terms.map( ( term ) => term.id )
		);

		expect( new Set( termIds ).size ).toBe( termIds.length );
	} );
} );
