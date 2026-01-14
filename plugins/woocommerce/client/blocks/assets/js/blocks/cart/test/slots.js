/* eslint-disable jest/no-commented-out-tests */
/**
 * External dependencies
 */
import { render, screen, waitFor, act } from '@testing-library/react';
import { previewCart } from '@woocommerce/resource-previews';
import { dispatch } from '@wordpress/data';
import { CART_STORE_KEY as storeKey } from '@woocommerce/block-data';
import { server, http, HttpResponse } from '@woocommerce/test-utils/msw';
import { ExperimentalOrderMeta } from '@woocommerce/blocks-checkout';
import { registerPlugin } from '@wordpress/plugins';
/**
 * Internal dependencies
 */
import { defaultCartState } from '../../../data/cart/default-state';

import Cart from '../block';
import OrderSummaryBlock from '../inner-blocks/cart-order-summary-block/frontend';

const SlotFillConsumer = ( { cart } ) => {
	const { billingData } = cart;

	return <p>My address: { billingData.address_1 }</p>;
};

const CartBlock = () => {
	return (
		<Cart>
			<OrderSummaryBlock />
		</Cart>
	);
};

describe( 'Testing Slotfills', () => {
	beforeAll( () => {
		registerPlugin( 'slot-fills-test', {
			render: () => (
				<ExperimentalOrderMeta>
					<SlotFillConsumer />
				</ExperimentalOrderMeta>
			),
			scope: 'woocommerce-checkout',
		} );
	} );
	beforeEach( () => {
		server.use(
			http.get( '/wc/store/v1/cart/', () => {
				return HttpResponse.json( previewCart );
			} )
		);

		act( () => {
			// need to clear the store resolution state between tests.
			dispatch( storeKey ).invalidateResolutionForStore();
			dispatch( storeKey ).receiveCart( defaultCartState.cartData );
		} );
	} );

	afterEach( () => {
		server.resetHandlers();
	} );

	it( 'still expects billingData', async () => {
		server.use(
			http.get( '/wc/store/v1/cart', () => {
				const cart = {
					...previewCart,
					billing_address: {
						...previewCart.billing_address,
						address_1: 'Street address',
					},
				};

				return HttpResponse.json( cart );
			} )
		);
		render( <CartBlock /> );

		await waitFor( () => {
			expect(
				screen.getByText( /My address: Street address/i )
			).toBeVisible();
		} );
	} );
} );
