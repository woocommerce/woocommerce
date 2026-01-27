/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';
import { blocksConfig } from '@woocommerce/block-settings';

/**
 * Internal dependencies
 */
import { getProducts } from '../';

jest.mock( '@wordpress/api-fetch' );
jest.mock( '@woocommerce/block-settings', () => ( {
	blocksConfig: {
		productCount: 0,
	},
} ) );

describe( 'getProducts', () => {
	beforeEach( () => {
		jest.clearAllMocks();
		// Reset productCount before each test
		blocksConfig.productCount = 0;
	} );

	test( 'small catalog: should not load selected products separately', async () => {
		blocksConfig.productCount = 50; // small catalog

		apiFetch.mockResolvedValue( [
			{ id: 1, name: 'shirt' },
			{ id: 2, name: 'pants' },
		] );

		await getProducts( { search: 'shirt', selected: [ 10, 20 ] } );

		expect( apiFetch ).toHaveBeenCalledTimes( 1 );
		expect( apiFetch ).toHaveBeenCalledWith(
			expect.objectContaining( {
				path: expect.stringContaining( 'search=shirt' ),
			} )
		);
		expect( apiFetch ).toHaveBeenCalledWith(
			expect.objectContaining( {
				path: expect.not.stringContaining( 'exclude=10' ),
			} )
		);
	} );

	test( 'large catalog: should load selected products separately', async () => {
		blocksConfig.productCount = 500; // large catalog

		apiFetch
			.mockResolvedValueOnce( [
				{ id: 1, name: 'shirt' },
				{ id: 2, name: 'pants' },
			] )
			.mockResolvedValueOnce( [
				{ id: 10, name: 'Special product' },
				{ id: 20, name: 'Other product' },
			] );

		await getProducts( { search: 'shirt', selected: [ 10, 20 ] } );

		// Two requests will have been made, one for the main search and one for the selected products.
		expect( apiFetch ).toHaveBeenCalledTimes( 2 );
		expect( apiFetch ).toHaveBeenCalledWith(
			expect.objectContaining( {
				path: expect.stringContaining( 'exclude%5B0%5D=10' ),
			} )
		);
		expect( apiFetch ).toHaveBeenCalledWith(
			expect.objectContaining( {
				path: expect.stringContaining( 'include%5B0%5D=10' ),
			} )
		);
	} );

	test( 'large catalog: should paginate selected products when necessary', async () => {
		blocksConfig.productCount = 500; // large catalog

		apiFetch
			.mockResolvedValueOnce( [
				{ id: 1, name: 'shirt' },
				{ id: 2, name: 'pants' },
			] )
			.mockResolvedValueOnce( [
				{ id: 10, name: 'Special product' },
				{ id: 11, name: 'Other product' },
			] );

		await getProducts( {
			search: 'shirt',
			selected: Array.from( { length: 101 }, ( _, i ) => i + 10 ),
		} );

		// Three requests will have been made, one for the main search and two for each selected product page.
		expect( apiFetch ).toHaveBeenCalledTimes( 3 );
		expect( apiFetch ).toHaveBeenCalledWith(
			expect.objectContaining( {
				path: expect.stringContaining( 'exclude%5B0%5D=10' ),
			} )
		);
		expect( apiFetch ).toHaveBeenCalledWith(
			expect.objectContaining( {
				path: expect.stringContaining( 'include%5B0%5D=10' ),
			} )
		);
		expect( apiFetch ).toHaveBeenCalledWith(
			expect.objectContaining( {
				path: expect.stringContaining( 'page=1' ),
			} )
		);
		expect( apiFetch ).toHaveBeenCalledWith(
			expect.objectContaining( {
				path: expect.stringContaining( 'page=2' ),
			} )
		);
	} );
} );
