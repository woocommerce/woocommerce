/**
 * Internal dependencies
 */
import { getAddedProducts } from '../get-added-products';

describe( 'getAddedProducts', () => {
	it( 'compares quantities per product, not per cart line', () => {
		// The server replaced product 5's line key while product 9 was added.
		const previousItems = [ { key: 'old-key', id: 5, quantity: 3 } ];
		const items = [
			{ key: 'new-key', id: 5, quantity: 3 },
			{ key: 'line-9', id: 9, quantity: 1 },
		];

		expect( getAddedProducts( previousItems, items ) ).toEqual( [
			{ id: 9, quantity: 1 },
		] );
	} );
} );
