/**
 * External dependencies
 */
import {
	act,
	render,
	screen,
	queryByText,
	waitFor,
	waitForElementToBeRemoved,
} from '@testing-library/react';
import { previewCart } from '@woocommerce/resource-previews';
import { dispatch } from '@wordpress/data';
import { CART_STORE_KEY as storeKey } from '@woocommerce/block-data';
import { SlotFillProvider } from '@woocommerce/blocks-checkout';
import { server, http, HttpResponse } from '@woocommerce/test-utils/msw';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import Block from '../block';
import { defaultCartState } from '../../../data/cart/default-state';

const MiniCartBlock = ( props ) => (
	<SlotFillProvider>
		<Block
			contents='<div data-block-name="woocommerce/mini-cart-contents" class="wp-block-woocommerce-mini-cart-contents"><div data-block-name="woocommerce/filled-mini-cart-contents-block" class="wp-block-woocommerce-filled-mini-cart-contents-block"><div data-block-name="woocommerce/mini-cart-title-block" class="wp-block-woocommerce-mini-cart-title-block"><div data-block-name="woocommerce/mini-cart-title-label-block" class="wp-block-woocommerce-mini-cart-title-label-block"></div>
			<div data-block-name="woocommerce/mini-cart-title-items-counter-block" class="wp-block-woocommerce-mini-cart-title-items-counter-block"></div></div>
			<div data-block-name="woocommerce/mini-cart-items-block" class="wp-block-woocommerce-mini-cart-items-block"><div data-block-name="woocommerce/mini-cart-products-table-block" class="wp-block-woocommerce-mini-cart-products-table-block"></div></div>
			<div data-block-name="woocommerce/mini-cart-footer-block" class="wp-block-woocommerce-mini-cart-footer-block"><div data-block-name="woocommerce/mini-cart-cart-button-block" class="wp-block-woocommerce-mini-cart-cart-button-block"></div>
			<div data-block-name="woocommerce/mini-cart-checkout-button-block" class="wp-block-woocommerce-mini-cart-checkout-button-block"></div></div></div>
			<div data-block-name="woocommerce/empty-mini-cart-contents-block" class="wp-block-woocommerce-empty-mini-cart-contents-block">
			<p class="has-text-align-center"><strong>Your cart is currently empty!</strong></p>
			<div data-block-name="woocommerce/mini-cart-shopping-button-block" class="wp-block-woocommerce-mini-cart-shopping-button-block"></div></div></div>'
			{ ...props }
		/>
	</SlotFillProvider>
);

const mockEmptyCart = () => {
	server.use(
		http.get( '*/wc/store/v1/cart*', () => {
			return HttpResponse.json( defaultCartState.cartData );
		} )
	);
};

const mockFullCart = () => {
	server.use(
		http.get( '*/wc/store/v1/cart*', () => {
			return HttpResponse.json( previewCart );
		} )
	);
};

describe( 'Testing Mini-Cart', () => {
	beforeEach( () => {
		act( () => {
			mockFullCart();
			// need to clear the store resolution state between tests.
			dispatch( storeKey ).invalidateResolutionForStore();
			dispatch( storeKey ).receiveCart( defaultCartState.cartData );
		} );
	} );

	afterEach( () => {
		server.resetHandlers();
	} );

	it( 'shows Mini-Cart count badge when there are items in the cart', async () => {
		render( <MiniCartBlock /> );

		await waitFor( () =>
			expect( screen.getByText( '3' ) ).toBeInTheDocument()
		);
	} );

	it( "doesn't show Mini-Cart count badge when cart is empty", async () => {
		mockEmptyCart();
		render( <MiniCartBlock /> );
		await waitFor( () =>
			expect( screen.getByLabelText( /0 items in cart/i ) ).toBeVisible()
		);
		const badgeWith0Count = screen.queryByText( '0' );

		expect( badgeWith0Count ).toBeNull();
	} );

	it( 'opens Mini-Cart drawer when clicking on button', async () => {
		const user = userEvent.setup();
		render( <MiniCartBlock /> );

		await waitFor( () =>
			expect( screen.getByLabelText( /3 items in cart/i ) ).toBeVisible()
		);
		await act( async () => {
			await user.click( screen.getByLabelText( /items/i ) );
		} );

		await waitFor( () =>
			expect( screen.getByText( /your cart/i ) ).toBeInTheDocument()
		);
	} );

	it( 'closes the drawer when clicking on the close button', async () => {
		const user = userEvent.setup();
		render( <MiniCartBlock /> );
		await waitFor( () =>
			expect( screen.getByLabelText( /3 items in cart/i ) ).toBeVisible()
		);

		// Open drawer.
		await act( async () => {
			await user.click( screen.getByLabelText( /items/i ) );
		} );

		// Close drawer.
		let closeButton = null;

		await waitFor( () => {
			closeButton = screen.getByLabelText( /close/i );
		} );

		if ( closeButton ) {
			await act( async () => {
				await user.click( closeButton );
			} );
		}

		await waitFor( () => {
			expect(
				screen.queryByText( /your cart/i )
			).not.toBeInTheDocument();
		} );
	} );

	it( 'renders empty cart if there are no items in the cart', async () => {
		const user = userEvent.setup();
		mockEmptyCart();
		render( <MiniCartBlock /> );

		await waitFor( () =>
			expect( screen.getByLabelText( /0 items in cart/i ) ).toBeVisible()
		);

		await act( async () => {
			await user.click( screen.getByLabelText( /items/i ) );
		} );

		// Should show empty cart message
		await waitFor( () =>
			expect(
				screen.getByText( /Your cart is currently empty!/i )
			).toBeVisible()
		);
	} );

	it( 'updates contents when removed from cart event is triggered', async () => {
		render( <MiniCartBlock /> );
		await waitFor( () =>
			expect( screen.getByLabelText( /3 items in cart/i ) ).toBeVisible()
		);

		mockEmptyCart();
		// eslint-disable-next-line no-undef
		const removedFromCartEvent = new Event( 'wc-blocks_removed_from_cart' );
		act( () => {
			document.body.dispatchEvent( removedFromCartEvent );
		} );

		await waitForElementToBeRemoved( () =>
			screen.queryByLabelText( /3 items in cart/i )
		);
		await waitFor( () =>
			expect( screen.getByLabelText( /0 items in cart/i ) ).toBeVisible()
		);
	} );

	it( 'updates contents when added to cart event is triggered', async () => {
		mockEmptyCart();
		render( <MiniCartBlock /> );
		await waitFor( () =>
			expect( screen.getByLabelText( /0 items in cart/i ) ).toBeVisible()
		);

		mockFullCart();
		// eslint-disable-next-line no-undef
		const addedToCartEvent = new Event( 'wc-blocks_added_to_cart' );
		act( () => {
			document.body.dispatchEvent( addedToCartEvent );
		} );

		await waitForElementToBeRemoved( () =>
			screen.queryByLabelText( /0 items in cart/i )
		);
		await waitFor( () =>
			expect(
				screen.getByLabelText( /3 items in cart/i )
			).toBeInTheDocument()
		);
	} );

	it( 'renders cart price if "Hide Cart Price" setting is not enabled', async () => {
		mockEmptyCart();
		render( <MiniCartBlock hasHiddenPrice={ false } /> );
		await waitFor( () =>
			expect(
				screen.getByLabelText( /0 items in cart/i )
			).toBeInTheDocument()
		);

		await waitFor( () =>
			expect( screen.getByText( '$0.00' ) ).toBeInTheDocument()
		);
	} );

	it( 'does not render cart price if "Hide Cart Price" setting is enabled', async () => {
		mockEmptyCart();
		const { container } = render( <MiniCartBlock /> );
		await waitFor( () =>
			expect(
				screen.getByLabelText( /0 items in cart/i )
			).toBeInTheDocument()
		);

		await waitFor( () =>
			expect( queryByText( container, '$0.00' ) ).not.toBeInTheDocument()
		);
	} );
} );
