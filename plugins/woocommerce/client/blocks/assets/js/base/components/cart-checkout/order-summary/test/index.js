/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import { previewCart } from '@woocommerce/resource-previews';

/**
 * Internal dependencies
 */
import OrderSummary from '../index';

let mockCartIsLoading = false;

jest.mock( '@woocommerce/base-context', () => ( {
	...jest.requireActual( '@woocommerce/base-context' ),
	useStoreCart: () => ( {
		cartIsLoading: mockCartIsLoading,
		// other cart properties you need
	} ),
	useContainerWidthContext: () => ( {
		isLarge: true,
		hasContainerWidth: true,
	} ),
} ) );

// Helper function to update the mock value
const setCartIsLoading = ( value ) => {
	mockCartIsLoading = value;
};

describe( 'Order Summary', () => {
	it( 'renders correct cart line subtotal when currency has 0 decimals', async () => {
		render(
			<OrderSummary
				cartItems={ [
					{
						...previewCart.items[ 0 ],
						totals: {
							...previewCart.items[ 0 ].totals,
							// Change price format so there are no decimals.
							currency_minor_unit: 0,
							currency_prefix: '',
							currency_suffix: '€',
							line_subtotal: '16',
							line_total: '18',
						},
					},
				] }
			/>
		);

		expect( screen.getByText( '16€' ) ).toBeTruthy();
	} );

	it( 'should show loading state', () => {
		setCartIsLoading( true );
		// your test code
	} );

	it( 'should show loaded state', () => {
		setCartIsLoading( false );
		// your test code
	} );
} );
