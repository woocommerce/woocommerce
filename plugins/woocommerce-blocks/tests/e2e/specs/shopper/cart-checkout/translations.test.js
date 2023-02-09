/* eslint-disable jest/expect-expect */
/**
 * Internal dependencies
 */
import { merchant, shopper } from '../../../../utils';

if ( process.env.WOOCOMMERCE_BLOCKS_PHASE < 2 ) {
	// Skips all the tests if it's a WooCommerce Core process environment.
	// eslint-disable-next-line jest/no-focused-tests
	test.only( 'Skipping Cart & Checkout tests', () => {} );
}

describe( 'Shopper → Cart & Checkout → Translations', () => {
	beforeAll( async () => {
		await merchant.changeLanguage( 'nl_NL' );
	} );

	afterAll( async () => {
		// default value empty in the select menu for English (United States)
		await merchant.changeLanguage( 'en_EN' );
	} );

	it( 'User can view translated Cart block', async () => {
		await shopper.goToShop();
		await shopper.addToCartFromShopPage( '128GB USB Stick' );
		await shopper.block.goToCart();

		await page.waitForSelector( '.wp-block-woocommerce-filled-cart-block' );

		const productHeader = await page.waitForSelector(
			'.wc-block-cart-items .wc-block-cart-items__header span'
		);
		await expect( productHeader ).toMatch( 'Product' );

		const removeLink = await page.waitForSelector(
			'.wc-block-cart-item__remove-link'
		);
		await expect( removeLink ).toMatch( 'Verwijder item' );

		const submitButton = await page.waitForSelector(
			'.wc-block-cart__submit-button'
		);

		await expect( submitButton ).toMatch( 'Ga naar afrekenen' );

		const orderSummary = await page.$(
			'.wp-block-woocommerce-cart-order-summary-block'
		);

		await expect( orderSummary ).toMatch( 'Subtotaal' );
		// Skipping translation for now, as it's not available in WooCommerce Core.
		// await expect( orderSummary ).toMatch( 'Een waardebon toevoegen' );
		await expect( orderSummary ).toMatch( 'Totaal' );
	} );

	it( 'User can view translated Checkout block', async () => {
		await shopper.block.goToCheckout();

		const contactHeading = await page.$(
			'#contact-fields .wc-block-components-checkout-step__title'
		);
		await expect( contactHeading ).toMatch( 'Contactgegevens' );

		const shippingHeading = await page.$(
			'#shipping-fields .wc-block-components-checkout-step__title'
		);
		await expect( shippingHeading ).toMatch( 'Verzendadres' );

		const shippingOptionsHeading = await page.$(
			'#shipping-option .wc-block-components-checkout-step__title'
		);
		await expect( shippingOptionsHeading ).toMatch( 'Verzendopties' );

		const paymentMethodHeading = await page.$(
			'#payment-method .wc-block-components-checkout-step__title'
		);
		await expect( paymentMethodHeading ).toMatch( 'Betaalopties' );

		const returnToCart = await page.$(
			'.wc-block-components-checkout-return-to-cart-button'
		);
		await expect( returnToCart ).toMatch( 'Ga terug naar winkelwagen' );

		const submitButton = await page.$(
			'.wc-block-components-checkout-place-order-button'
		);
		await expect( submitButton ).toMatch( 'Plaats bestelling' );

		const orderSummary = await page.$(
			'.wp-block-woocommerce-checkout-order-summary-block'
		);
		await expect( orderSummary ).toMatch( 'Besteloverzicht' );
		await expect( orderSummary ).toMatch( 'Subtotaal' );
		// Skipping translation for now, as it's not available in WooCommerce Core.
		// await expect( orderSummary ).toMatch( 'Een waardebon toevoegen' );
		await expect( orderSummary ).toMatch( 'Verzending' );
		await expect( orderSummary ).toMatch( 'Totaal' );
	} );
} );
