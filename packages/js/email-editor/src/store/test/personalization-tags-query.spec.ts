/**
 * Internal dependencies
 */
import { getPersonalizationTagsQuery } from '../personalization-tags-query';

describe( 'getPersonalizationTagsQuery', () => {
	it( 'includes post_id when there is a post id', () => {
		expect( getPersonalizationTagsQuery( 23 ) ).toStrictEqual( {
			context: 'view',
			per_page: -1,
			post_id: 23,
		} );
	} );

	it( 'omits post_id when there is no post id', () => {
		expect( getPersonalizationTagsQuery( undefined ) ).toStrictEqual( {
			context: 'view',
			per_page: -1,
		} );
	} );

	// The selector runs once per block on every store change, and core-data
	// caches the query by argument reference.
	it( 'returns the same object for the same post id', () => {
		expect( getPersonalizationTagsQuery( 23 ) ).toBe(
			getPersonalizationTagsQuery( 23 )
		);
	} );

	// A single-slot cache would make alternating post ids miss every time,
	// which is the problem this builder exists to avoid.
	it( 'caches each post id independently', () => {
		const first = getPersonalizationTagsQuery( 23 );
		const second = getPersonalizationTagsQuery( 24 );

		expect( second ).not.toBe( first );
		expect( getPersonalizationTagsQuery( 23 ) ).toBe( first );
	} );
} );
