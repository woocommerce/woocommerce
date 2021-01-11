/**
 * External dependencies
 */
import { render, screen, waitFor } from '@testing-library/react';
import { previewCart } from '@woocommerce/resource-previews';
import { dispatch } from '@wordpress/data';
import { CART_STORE_KEY as storeKey } from '@woocommerce/block-data';
/**
 * Internal dependencies
 */
import CartBlock from '../block';
import { defaultCartState } from '../../../../data/default-states';

describe( 'Testing cart', () => {
	beforeEach( async () => {
		fetchMock.mockResponse( ( req ) => {
			if ( req.url.match( /wc\/store\/cart/ ) ) {
				return Promise.resolve( JSON.stringify( previewCart ) );
			}
		} );
		// need to clear the store resolution state between tests.
		await dispatch( storeKey ).invalidateResolutionForStore();
		await dispatch( storeKey ).receiveCart( defaultCartState );
	} );
	afterEach( () => {
		fetchMock.resetMocks();
	} );
	it( 'renders cart if there are items in the cart', async () => {
		render(
			<CartBlock
				emptyCart={ null }
				attributes={ {
					isShippingCalculatorEnabled: false,
				} }
			/>
		);
		await waitFor( () => expect( fetchMock ).toHaveBeenCalled() );
		expect(
			screen.getByText( /Proceed to Checkout/i )
		).toBeInTheDocument();

		expect( fetchMock ).toHaveBeenCalledTimes( 1 );
	} );
	it( 'renders empty cart if there are no items in the cart', async () => {
		fetchMock.mockResponse( ( req ) => {
			if ( req.url.match( /wc\/store\/cart/ ) ) {
				return Promise.resolve( JSON.stringify( defaultCartState ) );
			}
		} );
		render(
			<CartBlock
				emptyCart={ '<div>Empty Cart</div>' }
				attributes={ {
					isShippingCalculatorEnabled: false,
				} }
			/>
		);

		await waitFor( () => expect( fetchMock ).toHaveBeenCalled() );
		expect( screen.getByText( /Empty Cart/i ) ).toBeInTheDocument();
		expect( fetchMock ).toHaveBeenCalledTimes( 1 );
	} );
} );
