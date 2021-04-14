/**
 * External dependencies
 */
import { shopper as wcShopper } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import { getBlockPagePermalink } from './get-block-page-permalink';

export const shopper = {
	...wcShopper,

	goToCheckoutBlock: async () => {
		const checkoutBlockPermalink = await getBlockPagePermalink(
			`Checkout Block`
		);

		await page.goto( checkoutBlockPermalink, {
			waitUntil: 'networkidle0',
		} );
	},

	productIsInCheckoutBlock: async ( productTitle, quantity, total ) => {
		await expect( page ).toClick( '.wc-block-components-panel__button' );
		await expect( page ).toMatchElement(
			'.wc-block-components-product-name',
			{
				text: productTitle,
			}
		);
		await expect( page ).toMatchElement(
			'.wc-block-components-order-summary-item__quantity',
			{
				text: quantity,
			}
		);
		await expect( page ).toMatchElement(
			'.wc-block-components-product-price__value',
			{
				text: total,
			}
		);
	},
};
