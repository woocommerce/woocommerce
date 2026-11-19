/**
 * External dependencies
 */
import { act, fireEvent, render, screen } from '@testing-library/react';
import { dispatch } from '@wordpress/data';
import { cartStore } from '@woocommerce/block-data';
import { useStoreCart } from '@woocommerce/base-context/hooks';

/**
 * Internal dependencies
 */
import EmptyCart from '../frontend';

jest.mock( '@woocommerce/base-context/hooks', () => ( {
	useStoreCart: jest.fn(),
} ) );

// Keep the entrance attribute in place. jsdom has no animations, so it would be removed at once.
jest.mock( '@woocommerce/base-utils', () => ( {
	...jest.requireActual( '@woocommerce/base-utils' ),
	whenAnimationsEnd: () => () => undefined,
} ) );

const mockCart = ( {
	cartItems,
	hasPendingItemsOperations = false,
}: {
	cartItems: Array< { key: string } >;
	hasPendingItemsOperations?: boolean;
} ) => {
	( useStoreCart as jest.Mock ).mockReturnValue( {
		cartItems,
		cartIsLoading: false,
		hasPendingItemsOperations,
	} );
};

// A new element each time, so a rerender renders the block again.
const emptyCartBlock = () => (
	<EmptyCart className="wp-block-woocommerce-empty-cart-block">
		<p>Empty Cart</p>
	</EmptyCart>
);

// Remove marks the item as pending delete, which the cart reports as a pending item operation.
const startRemoval = ( rerender: ( ui: JSX.Element ) => void ) => {
	act( () => {
		dispatch( cartStore ).itemIsPendingDelete( 'item' );
	} );
	mockCart( {
		cartItems: [ { key: 'item' } ],
		hasPendingItemsOperations: true,
	} );
	rerender( emptyCartBlock() );
};

const emptyCart = ( rerender: ( ui: JSX.Element ) => void ) => {
	mockCart( { cartItems: [] } );
	rerender( emptyCartBlock() );
	act( () => {
		dispatch( cartStore ).itemIsPendingDelete( 'item', false );
	} );
};

const getEmptyCart = () =>
	screen.getByText( 'Empty Cart' ).parentElement as HTMLElement;

describe( 'Empty cart entrance', () => {
	let removeButton: HTMLButtonElement;

	beforeEach( () => {
		removeButton = document.createElement( 'button' );
		document.body.appendChild( removeButton );
	} );

	afterEach( () => {
		removeButton.remove();
	} );

	it( 'rises in when a click removes the last item', () => {
		mockCart( { cartItems: [ { key: 'item' } ] } );
		const { rerender } = render( emptyCartBlock() );

		fireEvent.pointerDown( removeButton );
		fireEvent.click( removeButton, { detail: 1 } );
		startRemoval( rerender );
		emptyCart( rerender );

		expect( getEmptyCart() ).toHaveAttribute( 'data-wc-entering' );
	} );

	it( 'shows at once when a key press removes the last item, even after a click during the removal', () => {
		mockCart( { cartItems: [ { key: 'item' } ] } );
		const { rerender } = render( emptyCartBlock() );

		fireEvent.keyDown( removeButton, { key: 'Enter' } );
		fireEvent.click( removeButton, { detail: 0 } );
		startRemoval( rerender );
		fireEvent.pointerDown( document.body );
		fireEvent.click( document.body, { detail: 1 } );
		emptyCart( rerender );

		expect( getEmptyCart() ).not.toHaveAttribute( 'data-wc-entering' );
	} );

	it( 'shows at once when a key press removes the last item during a pointer quantity update', () => {
		mockCart( { cartItems: [ { key: 'item' } ] } );
		const { rerender } = render( emptyCartBlock() );

		// The shopper changes the quantity with the pointer. The update is still pending when they press Enter on Remove.
		fireEvent.pointerDown( document.body );
		fireEvent.click( document.body, { detail: 1 } );
		mockCart( {
			cartItems: [ { key: 'item' } ],
			hasPendingItemsOperations: true,
		} );
		rerender( emptyCartBlock() );

		fireEvent.keyDown( removeButton, { key: 'Enter' } );
		fireEvent.click( removeButton, { detail: 0 } );
		startRemoval( rerender );
		emptyCart( rerender );

		expect( getEmptyCart() ).not.toHaveAttribute( 'data-wc-entering' );
	} );
} );
