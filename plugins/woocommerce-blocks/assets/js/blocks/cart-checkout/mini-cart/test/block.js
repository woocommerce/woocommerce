/**
 * External dependencies
 */
import {
	act,
	render,
	screen,
	fireEvent,
	waitFor,
	waitForElementToBeRemoved,
} from '@testing-library/react';
import { previewCart } from '@woocommerce/resource-previews';
import { dispatch } from '@wordpress/data';
import { CART_STORE_KEY as storeKey } from '@woocommerce/block-data';
import { SlotFillProvider } from '@woocommerce/blocks-checkout';
import { default as fetchMock } from 'jest-fetch-mock';

/**
 * Internal dependencies
 */
import Block from '../block';
import { defaultCartState } from '../../../../data/default-states';

const MiniCartBlock = ( props ) => (
	<SlotFillProvider>
		<Block { ...props } />
	</SlotFillProvider>
);

const mockEmptyCart = () => {
	fetchMock.mockResponse( ( req ) => {
		if ( req.url.match( /wc\/store\/cart/ ) ) {
			return Promise.resolve(
				JSON.stringify( defaultCartState.cartData )
			);
		}
		return Promise.resolve( '' );
	} );
};

const mockFullCart = () => {
	fetchMock.mockResponse( ( req ) => {
		if ( req.url.match( /wc\/store\/cart/ ) ) {
			return Promise.resolve( JSON.stringify( previewCart ) );
		}
		return Promise.resolve( '' );
	} );
};

describe( 'Testing cart', () => {
	beforeEach( async () => {
		mockFullCart();
		// need to clear the store resolution state between tests.
		await dispatch( storeKey ).invalidateResolutionForStore();
		await dispatch( storeKey ).receiveCart( defaultCartState.cartData );
	} );

	afterEach( () => {
		fetchMock.resetMocks();
	} );

	it( 'opens Mini Cart drawer when clicking on button', async () => {
		render( <MiniCartBlock /> );
		await waitFor( () => expect( fetchMock ).toHaveBeenCalled() );
		fireEvent.click( screen.getByLabelText( /items/i ) );

		expect( screen.getByText( /Your cart/i ) ).toBeInTheDocument();
		expect( fetchMock ).toHaveBeenCalledTimes( 1 );
		// ["`select` control in `@wordpress/data-controls` is deprecated. Please use built-in `resolveSelect` control in `@wordpress/data` instead."]
		expect( console ).toHaveWarned();
	} );

	it( 'renders empty cart if there are no items in the cart', async () => {
		mockEmptyCart();
		render( <MiniCartBlock /> );

		await waitFor( () => expect( fetchMock ).toHaveBeenCalled() );
		fireEvent.click( screen.getByLabelText( /items/i ) );

		expect( screen.getByText( /Cart is empty/i ) ).toBeInTheDocument();
		expect( fetchMock ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'updates contents when removed from cart event is triggered', async () => {
		render( <MiniCartBlock /> );
		await waitFor( () => expect( fetchMock ).toHaveBeenCalled() );

		mockEmptyCart();
		// eslint-disable-next-line no-undef
		const removedFromCartEvent = new Event( 'wc-blocks_removed_from_cart' );
		act( () => {
			document.body.dispatchEvent( removedFromCartEvent );
		} );

		await waitForElementToBeRemoved( () =>
			screen.queryByLabelText( /3 items/i )
		);
		await waitFor( () =>
			expect( screen.getByLabelText( /0 items/i ) ).toBeInTheDocument()
		);
	} );

	it( 'updates contents when added to cart event is triggered', async () => {
		mockEmptyCart();
		render( <MiniCartBlock /> );
		await waitFor( () => expect( fetchMock ).toHaveBeenCalled() );

		mockFullCart();
		// eslint-disable-next-line no-undef
		const removedFromCartEvent = new Event( 'wc-blocks_added_to_cart' );
		act( () => {
			document.body.dispatchEvent( removedFromCartEvent );
		} );

		await waitForElementToBeRemoved( () =>
			screen.queryByLabelText( /0 items/i )
		);
		await waitFor( () =>
			expect( screen.getAllByLabelText( /3 items/i ).length > 0 )
		);
	} );
} );
