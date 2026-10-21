/**
 * External dependencies
 */
import { act, renderHook } from '@testing-library/react';
import { useDispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { useStoreAddToCart } from '../use-store-add-to-cart';

jest.mock( '@wordpress/data', () => ( {
	...jest.requireActual( '@wordpress/data' ),
	useDispatch: jest.fn(),
} ) );

jest.mock( '../cart/use-store-cart', () => ( {
	useStoreCart: () => ( { cartItems: [], cartIsLoading: false } ),
} ) );

describe( 'useStoreAddToCart', () => {
	const createErrorNotice = jest.fn();

	beforeEach( () => {
		createErrorNotice.mockClear();
		( useDispatch as jest.Mock ).mockReturnValue( {
			addItemToCart: jest
				.fn()
				.mockRejectedValue( new Error( 'Out of stock' ) ),
			createErrorNotice,
			removeNotice: jest.fn(),
		} );
	} );

	it( 'shows add to cart errors in the All Products context by default', async () => {
		const { result } = renderHook( () => useStoreAddToCart( 1 ) );

		await act( async () => {
			await result.current.addToCart();
		} );

		expect( createErrorNotice ).toHaveBeenCalledWith(
			'Out of stock',
			expect.objectContaining( { context: 'wc/all-products' } )
		);
	} );

	it( 'shows add to cart errors in the given notice context', async () => {
		const { result } = renderHook( () =>
			useStoreAddToCart( 1, 'wc/cart' )
		);

		await act( async () => {
			await result.current.addToCart();
		} );

		expect( createErrorNotice ).toHaveBeenCalledWith(
			'Out of stock',
			expect.objectContaining( { context: 'wc/cart' } )
		);
	} );
} );
