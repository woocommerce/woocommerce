/**
 * External dependencies
 */
import { expect, test as base } from '@woocommerce/e2e-playwright-utils';
import { cli } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import { CheckoutPage } from '../checkout/checkout.page';

const test = base.extend< { checkoutPageObject: CheckoutPage } >( {
	checkoutPageObject: async ( { page }, use ) => {
		const pageObject = new CheckoutPage( {
			page,
		} );
		await use( pageObject );
	},
} );

test.describe( 'Shopper → Translations', () => {
	test.beforeAll( async () => {
		await cli(
			`npm run wp-env run tests-cli -- wp language core install nl_NL`
		);
		await cli(
			`npm run wp-env run tests-cli -- wp site switch-language nl_NL`
		);
		await cli(
			`npm run wp-env run tests-cli -- wp language plugin install woocommerce nl_NL`
		);
	} );

	test.afterAll( async () => {
		await cli(
			`npm run wp-env run tests-cli -- wp site switch-language en_US`
		);
	} );

	test( 'User can view translated Cart block', async ( {
		frontendUtils,
		page,
	} ) => {
		await frontendUtils.goToShop();

		const beanieAddToCartButton = page.getByLabel(
			'Toevoegen aan winkelwagen: “Beanie“'
		);
		await beanieAddToCartButton.click();

		// Add to cart initiates a request that could be interrupted by navigation, wait till it's done.
		await expect( beanieAddToCartButton ).toHaveText( /in winkelwagen/ );

		await frontendUtils.goToCart();

		const totalsHeader = page
			.getByRole( 'cell', { name: 'Totaal' } )
			.locator( 'span' );
		await expect( totalsHeader ).toBeVisible();

		await expect(
			page.getByLabel( 'Verwijder Beanie uit winkelwagen' )
		).toBeVisible();

		await expect( page.getByText( 'Totalen winkelwagen' ) ).toBeVisible();

		await expect(
			page.getByRole( 'button', { name: 'Een waardebon toevoegen' } )
		).toBeVisible();

		await expect(
			page.getByRole( 'link', { name: 'Doorgaan naar afrekenen' } )
		).toBeVisible();
	} );

	test( 'User can view translated Checkout block', async ( {
		frontendUtils,
		page,
	} ) => {
		await frontendUtils.goToShop();
		const beanieAddToCartButton = page.getByLabel(
			'Toevoegen aan winkelwagen: “Beanie“'
		);

		await beanieAddToCartButton.click();
		await page.getByLabel( 'Toevoegen aan winkelwagen: “Beanie“' ).click();

		// Add to cart initiates a request that could be interrupted by navigation, wait till it's done.
		await expect( beanieAddToCartButton ).toHaveText( /in winkelwagen/ );

		await frontendUtils.goToCheckout();

		await expect(
			page
				.getByRole( 'group', { name: 'Contactgegevens' } )
				.locator( 'h2' )
		).toBeVisible();

		await expect(
			page.getByRole( 'group', { name: 'Verzendadres' } ).locator( 'h2' )
		).toBeVisible();

		await expect(
			page.getByRole( 'group', { name: 'Verzendopties' } ).locator( 'h2' )
		).toBeVisible();

		await expect(
			page
				.getByRole( 'group', { name: 'Betalingsopties' } )
				.locator( 'h2' )
		).toBeVisible();

		await expect(
			page.getByRole( 'link', { name: 'Terug naar winkelwagen' } )
		).toBeVisible();

		await expect(
			page.getByRole( 'button', { name: 'Bestel en betaal' } )
		).toBeVisible();

		await expect(
			page.getByRole( 'button', {
				name: 'Besteloverzicht',
			} )
		).toBeVisible();

		await expect( page.getByText( 'Subtotaal' ) ).toBeVisible();

		await expect( page.getByText( 'Verzending' ) ).toBeVisible();

		await expect(
			page.getByText( 'Totaal', { exact: true } )
		).toBeVisible();
	} );
} );
