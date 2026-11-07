/**
 * External dependencies
 */
import { render, within } from '@testing-library/react';
import { previewCart } from '@woocommerce/resource-previews';

/**
 * Internal dependencies
 */
import OrderSummary from '../index';
import { textContentMatcher } from '../../../../../../../tests/utils/find-by-text';

// The line total rolls its digits, which are drawn by CSS, so its price is read from the element's text.
const lineTotal = () =>
	within(
		/** @type {HTMLElement} */ (
			document.querySelector(
				'.wc-block-cart-item__total-price-and-sale-badge-wrapper'
			)
		)
	);

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

		expect(
			lineTotal().getByText( textContentMatcher( '16€' ) )
		).toBeInTheDocument();
	} );

	it( 'renders correct cart line subtotal when product price is 0', async () => {
		render(
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

		expect(
			lineTotal().getByText( textContentMatcher( '$0.00' ) )
		).toBeInTheDocument();
	} );
} );
