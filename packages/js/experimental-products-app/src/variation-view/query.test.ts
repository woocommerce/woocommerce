/**
 * Internal dependencies
 */
import { buildVariationViewQuery } from './query';

describe( 'buildVariationViewQuery', () => {
	it( 'uses the product list query to fetch the parent product with embedded variations', () => {
		const query = buildVariationViewQuery( 99 );

		expect( query ).toEqual(
			expect.objectContaining( {
				_embed: 1,
				include: [ 99 ],
				page: 1,
				per_page: 1,
			} )
		);
	} );
} );
