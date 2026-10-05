import { describe, expect, it, vi } from 'vitest';

/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import { previewCart } from '@woocommerce/resource-previews';

/**
 * Internal dependencies
 */
import OrderSummary from '../index';
import { textContentMatcher } from '../../../../../../../tests/utils/find-by-text';

// The screen reader label repeats the price, so only the visible one is queried.
const visibleOnly = {
	ignore: 'script, style, .screen-reader-text',
};
vi.mock( '@woocommerce/base-context', async () => {
	const mock = {
		...( await vi.importActual( '@woocommerce/base-context' ) ),
		useStoreCart: () => ( {
			cartIsLoading: false,
		} ),
		useContainerWidthContext: () => ( {
			isLarge: true,
			hasContainerWidth: true,
		} ),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
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
			screen.getByText( textContentMatcher( '16€' ), visibleOnly )
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
			screen.getByText( textContentMatcher( '$0.00' ), visibleOnly )
		).toBeInTheDocument();
	} );
} );
