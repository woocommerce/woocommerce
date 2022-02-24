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
		await expect( page ).toMatchElement( 'h1', { text: 'Checkout' } );
	},

	productIsInCheckoutBlock: async ( productTitle, quantity, total ) => {
		// Make sure Order summary is expanded
		const [ button ] = await page.$x(
			`//button[contains(@aria-expanded, 'false')]//span[contains(text(), 'Order summary')]`
		);
		if ( button ) {
			await button.click();
		}
		await expect( page ).toMatchElement( 'span', {
			text: productTitle,
		} );
		await expect( page ).toMatchElement(
			'div.wc-block-components-order-summary-item__quantity',
			{
				text: quantity,
			}
		);
		await expect( page ).toMatchElement(
			'span.wc-block-components-product-price__value',
			{
				text: total,
			}
		);
	},

	goToBlockPage: async ( title ) => {
		await page.goto( await getBlockPagePermalink( title ), {
			waitUntil: 'networkidle0',
		} );

		await expect( page ).toMatchElement( 'h1', { text: title } );
	},
};
