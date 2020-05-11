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
			} );
		} );

		const product = await factories.product.create( {
			Name: 'Test Product',
		} );

		expect( product.ID ).toEqual( 101 );
		expect( product.Name ).toMatch( 'Test Product' );
	} );
} );
