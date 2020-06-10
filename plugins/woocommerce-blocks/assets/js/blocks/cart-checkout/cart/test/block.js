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
		fetchMock.mockResponseOnce( ( req ) => {
			if ( req.url.match( /wc\/store\/cart/ ) ) {
				return Promise.resolve( JSON.stringify( previewCart ) );
			}
		} );
		// need to clear the store resolution state between tests.
		await dispatch( storeKey ).invalidateResolutionForStore();
		await dispatch( storeKey ).receiveCart( defaultCartState );
	} );
	it( 'renders cart if there are items in the cart', async () => {
		render(
			<CartBlock
				emptyCart={ null }
				attributes={ {
					isShippingCalculatorEnabled: false,
					isShippingCostHidden: true,
				} }
			/>
		);
		await waitFor( () => {
			expect(
				screen.getByText( /Proceed to Checkout/ )
			).toBeInTheDocument();
		} );
	} );
	it( 'renders empty cart if there are no items in the cart', async () => {
		fetchMock.mockResponseOnce( ( req ) => {
			if ( req.url.match( /wc\/store\/cart/ ) ) {
				return Promise.resolve( JSON.stringify( defaultCartState ) );
			}
		} );
		render(
			<CartBlock
				emptyCart={ '<div>Empty Cart</div>' }
				attributes={ {
					isShippingCalculatorEnabled: false,
					isShippingCostHidden: true,
				} }
			/>
		);
		expect( await screen.findByText( /Empty Cart/ ) ).toBeInTheDocument();
	} );
} );
