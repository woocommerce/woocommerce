import apiFetch from '@wordpress/api-fetch';
import { factories } from '../src/factories';

describe( 'product-factory', () => {
	it( 'should store ID after creation', async () => {
		// We want to mock an API response for the test.
		apiFetch.setFetchHandler( ( options ) => {
			expect( options.data ).toHaveProperty( 'name' );
			expect( ( options.data as any ).name ).toMatch( 'Test Product' );
			return Promise.resolve( {
				id: 101,
				name: 'Test Product',
				regular_price: '9.99',
			} );
		} );

		const product = await factories.product.create( {
			Name: 'Test Product',
			RegularPrice: '9.99',
		} );

		expect( product.ID ).toEqual( 101 );
		expect( product.Name ).toMatch( 'Test Product' );
		expect( product.RegularPrice ).toMatch( '9.99' );
	} );
} );
