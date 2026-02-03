/**
 * External dependencies
 */
import type { ProductsStoreState } from '@woocommerce/stores/woocommerce/products';

/**
 * Internal dependencies
 */
import type { NormalizedProductData } from '../types';

let mockProductsState: {
	products: Record<
		number,
		Partial< ProductsStoreState[ 'products' ][ number ] >
	>;
	productVariations: Record<
		number,
		Partial< ProductsStoreState[ 'productVariations' ][ number ] >
	>;
};

jest.mock(
	'@wordpress/interactivity',
	() => ( {
		store: jest.fn( ( name ) => {
			if ( name === 'woocommerce/products' ) {
				return { state: mockProductsState };
			}
			return { state: {} };
		} ),
	} ),
	{ virtual: true }
);

jest.mock( '@woocommerce/stores/woocommerce/product-data', () => ( {} ), {
	virtual: true,
} );

jest.mock( '@woocommerce/stores/woocommerce/products', () => ( {} ), {
	virtual: true,
} );

describe( 'getProductData', () => {
	let getProductData: (
		id: number,
		selectedAttributes: Array< { attribute: string; value: string } >
	) => NormalizedProductData | null;

	beforeEach( () => {
		jest.resetModules();
		mockProductsState = {
			products: {},
			productVariations: {},
		};

		jest.isolateModules( () => {
			//eslint-disable-next-line @typescript-eslint/no-var-requires
			const frontend = require( '../frontend' );
			getProductData = frontend.getProductData;
		} );
	} );

	it( 'returns null when product is not in store', () => {
		mockProductsState.products = {};

		const result = getProductData( 999, [] );
		expect( result ).toBeNull();
	} );

	it( 'returns product data with default quantity constraints', () => {
		mockProductsState.products = {
			1: {
				id: 1,
				type: 'simple',
				is_purchasable: true,
				is_in_stock: true,
				sold_individually: false,
			},
		};

		const result = getProductData( 1, [] );

		expect( result ).toEqual( {
			id: 1,
			type: 'simple',
			is_in_stock: true,
			sold_individually: false,
			min: 1,
			max: Number.MAX_SAFE_INTEGER,
			step: 1,
		} );
	} );

	it( 'returns product data with custom quantity constraints', () => {
		mockProductsState.products = {
			1: {
				id: 1,
				type: 'simple',
				is_purchasable: true,
				is_in_stock: true,
				sold_individually: false,
				add_to_cart: {
					text: 'Add to cart',
					description: 'Add to cart',
					url: '',
					minimum: 2,
					maximum: 10,
					multiple_of: 2,
					single_text: 'Add to cart',
				},
			},
		};

		const result = getProductData( 1, [] );

		expect( result ).toEqual( {
			id: 1,
			type: 'simple',
			is_in_stock: true,
			sold_individually: false,
			min: 2,
			max: 10,
			step: 2,
		} );
	} );

	it( 'returns max as MAX_SAFE_INTEGER when maximum is 0', () => {
		mockProductsState.products = {
			1: {
				id: 1,
				type: 'simple',
				is_purchasable: true,
				is_in_stock: true,
				sold_individually: false,
				add_to_cart: {
					text: 'Add to cart',
					description: 'Add to cart',
					url: '',
					minimum: 1,
					maximum: 0,
					multiple_of: 1,
					single_text: 'Add to cart',
				},
			},
		};

		const result = getProductData( 1, [] );

		expect( result?.max ).toBe( Number.MAX_SAFE_INTEGER );
	} );

	describe( 'variable products', () => {
		it( 'returns variation data when attributes match a variation', () => {
			mockProductsState.products = {
				1: {
					id: 1,
					type: 'variable',
					is_purchasable: true,
					is_in_stock: true,
					sold_individually: false,
					add_to_cart: {
						text: 'Add to cart',
						description: 'Add to cart',
						url: '',
						minimum: 1,
						maximum: 100,
						multiple_of: 1,
						single_text: 'Add to cart',
					},
					variations: [
						{
							id: 10,
							attributes: [ { name: 'Color', value: 'red' } ],
						},
					],
				},
			};
			mockProductsState.productVariations = {
				10: {
					id: 10,
					type: 'variation',
					is_purchasable: true,
					is_in_stock: true,
					sold_individually: false,
					add_to_cart: {
						text: 'Add to cart',
						description: 'Add to cart',
						url: '',
						minimum: 5,
						maximum: 50,
						multiple_of: 5,
						single_text: 'Add to cart',
					},
				},
			};

			const result = getProductData( 1, [
				{ attribute: 'Color', value: 'red' },
			] );

			expect( result ).toEqual( {
				id: 10,
				type: 'variation',
				is_in_stock: true,
				sold_individually: false,
				min: 5,
				max: 50,
				step: 5,
			} );
		} );

		it( 'returns null when variation is matched but not in store', () => {
			mockProductsState.products = {
				1: {
					id: 1,
					type: 'variable',
					is_purchasable: true,
					is_in_stock: true,
					sold_individually: false,
					variations: [
						{
							id: 10,
							attributes: [ { name: 'Color', value: 'red' } ],
						},
					],
				},
			};
			mockProductsState.productVariations = {};

			const result = getProductData( 1, [
				{ attribute: 'Color', value: 'red' },
			] );

			expect( result ).toBeNull();
		} );

		it( 'returns parent product data when no attributes are selected', () => {
			mockProductsState.products = {
				1: {
					id: 1,
					type: 'variable',
					is_purchasable: true,
					is_in_stock: true,
					sold_individually: false,
					add_to_cart: {
						text: 'Add to cart',
						description: 'Add to cart',
						url: '',
						minimum: 1,
						maximum: 100,
						multiple_of: 1,
						single_text: 'Add to cart',
					},
					variations: [
						{
							id: 10,
							attributes: [ { name: 'Color', value: 'red' } ],
						},
					],
				},
			};

			const result = getProductData( 1, [] );

			expect( result ).toEqual( {
				id: 1,
				type: 'variable',
				is_in_stock: true,
				sold_individually: false,
				min: 1,
				max: 100,
				step: 1,
			} );
		} );
	} );
} );
