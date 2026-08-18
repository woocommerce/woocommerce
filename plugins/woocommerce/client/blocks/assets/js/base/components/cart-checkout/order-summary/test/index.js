/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import { previewCart } from '@woocommerce/resource-previews';

/**
 * Internal dependencies
 */
import OrderSummary from '../index';

// The currency symbol sits in its own element, so the whole price is read at once.
/**
 * @param {HTMLElement} container
 */
const getLineSubtotal = ( container ) =>
	container.querySelector(
		'.wc-block-components-order-summary-item__total-price bdi'
	)?.textContent;

jest.mock( '@woocommerce/base-context', () => ( {
	...jest.requireActual( '@woocommerce/base-context' ),
	useStoreCart: () => ( {
		cartIsLoading: false,
	} ),
	useContainerWidthContext: () => ( {
		isLarge: true,
		hasContainerWidth: true,
	} ),
} ) );

describe( 'Order Summary', () => {
	it( 'renders correct cart line subtotal when currency has 0 decimals', async () => {
		const { container } = render(
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

		expect( getLineSubtotal( container ) ).toBe( '16€' );
	} );

	it( 'renders correct cart line subtotal when product price is 0', async () => {
		const { container } = render(
			<OrderSummary
				cartItems={ [
					{
						...previewCart.items[ 0 ],
						prices: {
							...previewCart.items[ 0 ].prices,
							price: '0',
							regular_price: '0',
							sale_price: '0',
						},
						totals: {
							...previewCart.items[ 0 ].totals,
							line_subtotal: '0',
							line_subtotal_tax: '0',
							line_total: '0',
							line_total_tax: '0',
						},
					},
				] }
			/>
		);

		expect( getLineSubtotal( container ) ).toBe( '$0.00' );
	} );
} );
