/**
 * External dependencies
 */
import { useCartEventsContext } from '@woocommerce/base-context';
import { useEffect } from '@wordpress/element';
import { render, screen, waitFor } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { CartEventsProvider } from '../index';
import Block from '../../../../../../blocks/cart/inner-blocks/proceed-to-checkout-block/block';

describe( 'CartEventsProvider', () => {
	it( 'allows observers to unsubscribe', async () => {
		const mockObserver = jest.fn().mockReturnValue( { type: 'error' } );
		const MockObserverComponent = () => {
			const { onProceedToCheckout } = useCartEventsContext();
			useEffect( () => {
				const unsubscribe = onProceedToCheckout( () => {
					mockObserver();
					unsubscribe();
				} );
			}, [ onProceedToCheckout ] );
			return <div>Mock observer</div>;
		};

		render(
			<CartEventsProvider>
				<div>
					<MockObserverComponent />
					<Block checkoutPageId={ 0 } className="test-block" />
				</div>
			</CartEventsProvider>
		);
		expect( screen.getByText( 'Mock observer' ) ).toBeInTheDocument();
		const button = screen.getByText( 'Proceed to Checkout' );

		// Forcibly set the button URL to # to prevent JSDOM error: `["Error: Not implemented: navigation (except hash changes)`
		button.parentElement?.removeAttribute( 'href' );

		// Click twice. The observer should unsubscribe after the first click.
		button.click();
		button.click();
		await waitFor( () => {
			expect( mockObserver ).toHaveBeenCalledTimes( 1 );
		} );
	} );
} );
