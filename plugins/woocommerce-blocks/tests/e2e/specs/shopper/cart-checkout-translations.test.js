/**
 * Internal dependencies
 */
import { cli } from '../../../utils/run-cli-from-test';
import { merchant } from '../../../utils/merchant';
import { shopper } from '../../../utils/shopper';

if ( process.env.WOOCOMMERCE_BLOCKS_PHASE < 2 ) {
	// eslint-disable-next-line jest/no-focused-tests
	test.only( 'Skipping Checkout tests', () => {} );
}

describe( 'Shopper → Cart → Can view translated cart & checkout blocks', () => {
	// We need to install the language files for the blocks plugin.
	// We also need to install the plugin from w.org via the cli. This is because
	// on w.org, the slug is `woo-gutenberg-products-block` where as here it's
	// `woocommerce-gutenberg-products-block`. If we try to install the language files
	// directly, it won't find them because of the slug mismatch.
	beforeAll( async () => {
		const resultInstallBlocks = await cli(
			'npm run wp-env run tests-cli "wp plugin install woo-gutenberg-products-block"'
		);
		const resultInstallLanguages = await cli(
			'npm run wp-env run tests-cli "wp language plugin install woo-gutenberg-products-block fr_FR"'
		);
		expect( resultInstallBlocks.code ).toEqual( 0 );
		expect( resultInstallLanguages.code ).toEqual( 0 );
		await merchant.changeLanguage( 'fr_FR' );
	} );

	// We need to clean up here by changing the language back to English
	// and uninstalling the w.org version of Woo Blocks plugin and the language files
	afterAll( async () => {
		await merchant.changeLanguage( 'en_EN' );
		const resultUninstallBlocks = await cli(
			'npm run wp-env run tests-cli "wp plugin uninstall woo-gutenberg-products-block"'
		);
		const resultUninstallLanguages = await cli(
			'npm run wp-env run tests-cli "wp language plugin uninstall woo-gutenberg-products-block fr_FR"'
		);
		expect( resultUninstallBlocks.code ).toEqual( 0 );
		expect( resultUninstallLanguages.code ).toEqual( 0 );
	} );

	it( 'should be able to view translated Cart block ', async () => {
		await shopper.goToShop();
		await shopper.addToCartFromShopPage( '128GB USB Stick' );
		await shopper.block.goToCart();
		const productHeader = await page.$(
			'.wc-block-cart-items .wc-block-cart-items__header span'
		);
		await expect( productHeader ).toMatch( 'Produit', { timeout: 2000 } );

		const removeLink = await page.$( '.wc-block-cart-item__remove-link' );
		await expect( removeLink ).toMatch( 'Retirer l’élément' );

		const submitButton = await page.$( '.wc-block-cart__submit-button' );
		await expect( submitButton ).toMatch( 'Procéder au paiement' );

		const orderSummary = await page.$(
			'.wp-block-woocommerce-cart-order-summary-block'
		);
		await expect( orderSummary ).toMatch( 'Total panier' );
		await expect( orderSummary ).toMatch( 'Sous-total' );
		await expect( orderSummary ).toMatch( 'Coupon code' );
		await expect( orderSummary ).toMatch( 'Appliquer un code promo' );
	} );

	it( 'should be able to view translated Checkout block', async () => {
		await shopper.block.goToCheckout();

		const contactHeading = await page.$(
			'#contact-fields .wc-block-components-checkout-step__title'
		);
		await expect( contactHeading ).toMatch( 'Coordonnées', {
			timeout: 2000,
		} );

		const shippingHeading = await page.$(
			'#shipping-fields .wc-block-components-checkout-step__title'
		);
		await expect( shippingHeading ).toMatch( 'Adresse de livraison' );

		const shippingOptionsHeading = await page.$(
			'#shipping-option .wc-block-components-checkout-step__title'
		);
		await expect( shippingOptionsHeading ).toMatch(
			'Options de livraison'
		);

		const paymentMethodHeading = await page.$(
			'#payment-method .wc-block-components-checkout-step__title'
		);
		await expect( paymentMethodHeading ).toMatch( 'Options de paiement' );

		const returnToCart = await page.$(
			'.wc-block-components-checkout-return-to-cart-button'
		);
		await expect( returnToCart ).toMatch( 'Retour au panier' );

		const submitButton = await page.$(
			'.wc-block-components-checkout-place-order-button'
		);
		await expect( submitButton ).toMatch( 'Passer la commande' );

		const orderSummary = await page.$(
			'.wp-block-woocommerce-checkout-order-summary-block'
		);
		await expect( orderSummary ).toMatch( 'Récapitulatif de commande' );
		await expect( orderSummary ).toMatch( 'Sous-total' );
		await expect( orderSummary ).toMatch( 'Coupon code' );
		await expect( orderSummary ).toMatch( 'Livraison' );
	} );
} );
