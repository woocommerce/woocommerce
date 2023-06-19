/**
 * External dependencies
 */
import {
	CartEventsProvider,
	useCartEventsContext,
} from '@woocommerce/base-context';
import { useEffect } from '@wordpress/element';
import { render, screen, waitFor } from '@testing-library/react';

/**
 * Internal dependencies
 */
import Block from '../block';

describe( 'Mini Cart Checkout Button Block', () => {
	it( 'dispatches the onProceedToCheckout event when the button is clicked', async () => {
		const mockObserver = jest.fn().mockReturnValue( { type: 'error' } );
		const MockObserverComponent = () => {
			const { onProceedToCheckout } = useCartEventsContext();
			useEffect( () => {
				return onProceedToCheckout( mockObserver );
			}, [ onProceedToCheckout ] );
			return <div>Mock observer</div>;
		};

		render(
			<CartEventsProvider>
				<div>
					<MockObserverComponent />
					<Block
						checkoutButtonLabel={ 'Proceed to Checkout' }
						className="test-block"
					/>
				</div>
			</CartEventsProvider>
		);
		expect( screen.getByText( 'Mock observer' ) ).toBeInTheDocument();
		const button = screen.getByText( 'Proceed to Checkout' );

		// Forcibly set the button URL to # to prevent JSDOM error: `["Error: Not implemented: navigation (except hash changes)`
		button.parentElement?.removeAttribute( 'href' );
		button.click();
		await waitFor( () => {
			expect( mockObserver ).toHaveBeenCalled();
		} );
	} );
} );
