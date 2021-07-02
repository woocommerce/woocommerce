/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import { previewCart } from '@woocommerce/resource-previews';

/**
 * Internal dependencies
 */
import { allSettings } from '../../../../../settings/shared/settings-init';
import CheckoutSidebar from '../index';

describe( 'Testing checkout sidebar', () => {
	it( 'Shows rate percentages after tax lines if the block is set to do so', async () => {
		allSettings.displayCartPricesIncludingTax = false;
		allSettings.displayItemizedTaxes = true;
		const { totals: cartTotals, items: cartItems } = previewCart;
		const { container } = render(
			<CheckoutSidebar
				cartItems={ cartItems }
				cartTotals={ cartTotals }
				showRateAfterTaxName={ true }
			/>
		);
		expect( container ).toMatchSnapshot();
	} );
} );
