/**
 * External dependencies
 */

import { render, screen, waitFor } from '@testing-library/react';
import { registerCheckoutFilters } from '@woocommerce/blocks-checkout';
import { useCartEventsContext } from '@woocommerce/base-context';
import { useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import Block from '../block';
import { CartEventsProvider } from '../../../../../base/context/providers';

describe( 'Proceed to checkout block', () => {
	it( 'allows the text to be filtered', () => {
		registerCheckoutFilters( 'test-extension', {
			proceedToCheckoutButtonLabel: () => {
				return 'Proceed to step two';
			},
		} );
		render(
			<Block checkoutPageId={ 0 } buttonLabel={ '' } className={ '' } />
		);

		// TODO: Fix a recent deprecation of showSpinner prop of Button called in this component.
		expect( console ).toHaveWarned();

		expect( screen.getByText( 'Proceed to step two' ) ).toBeInTheDocument();
	} );
	it( 'allows the link to be filtered', () => {
		registerCheckoutFilters( 'test-extension', {
			proceedToCheckoutButtonLink: () => {
				return 'https://woocommerce.com';
			},
		} );
		render(
			<Block checkoutPageId={ 0 } buttonLabel={ '' } className={ '' } />
		);
		const button = screen.getByText( 'Proceed to Checkout' );
		const link = button.closest( 'a' );
		expect( link?.href ).toBe( 'https://woocommerce.com/' );
	} );
	it( 'does not allow incorrect types to be applied to either button label or button link', () => {
		registerCheckoutFilters( 'test-extension', {
			proceedToCheckoutButtonLabel: () => {
				return 123;
			},
			proceedToCheckoutButtonLink: () => {
				return 123;
			},
		} );
		render(
			<Block checkoutPageId={ 0 } buttonLabel={ '' } className={ '' } />
		);
		//@todo When https://github.com/WordPress/gutenberg/issues/22850 is complete use that new matcher here for more specific error message assertion.
		expect( console ).toHaveErrored();
	} );
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
						buttonLabel={ 'Proceed to Checkout' }
						checkoutPageId={ 0 }
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
