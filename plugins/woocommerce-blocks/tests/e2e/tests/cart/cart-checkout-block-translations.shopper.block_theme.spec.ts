/**
 * External dependencies
 */
import { expect, test as base } from '@woocommerce/e2e-playwright-utils';
import { cli } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import { REGULAR_PRICED_PRODUCT_NAME } from '../checkout/constants';
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
			`npm run wp-env run tests-cli -- wp language core activate nl_NL`
		);
	} );

	test.afterAll( async () => {
		await cli(
			`npm run wp-env run tests-cli -- wp language core activate en_US`
		);
	} );

	test( 'User can view translated Cart block', async ( {
		frontendUtils,
		page,
	} ) => {
		await frontendUtils.goToShop();
		await frontendUtils.addToCart( REGULAR_PRICED_PRODUCT_NAME );
		await frontendUtils.goToCart();

		const totalsHeader = page
			.getByRole( 'cell', { name: 'Totaal' } )
			.locator( 'span' );
		await expect( totalsHeader ).toBeVisible();

		const removeLink = page.getByLabel( 'Verwijder Polo uit winkelwagen' );
		await expect( removeLink ).toBeVisible();
		await expect( removeLink ).toHaveText( 'Verwijder item' );

		const sidebarHeader = page.getByText( 'Totaal winkelwagen' );
		await expect( sidebarHeader ).toBeVisible();

		const couponLink = page.getByLabel( 'Een waardebon toevoegen' );
		await expect( couponLink ).toBeVisible();

		const submitButton = page.getByRole( 'link', {
			name: 'Ga naar afrekenen',
		} );
		await expect( submitButton ).toBeVisible();
	} );

	test( 'User can view translated Checkout block', async ( {
		frontendUtils,
		page,
	} ) => {
		await frontendUtils.goToShop();
		await frontendUtils.addToCart( REGULAR_PRICED_PRODUCT_NAME );
		await frontendUtils.goToCart();
		await frontendUtils.goToCheckout();

		const contactHeading = page
			.getByRole( 'group', { name: 'Contactgegevens' } )
			.locator( 'h2' );
		await expect( contactHeading ).toBeVisible();

		const shippingHeading = page
			.getByRole( 'group', { name: 'Verzendadres' } )
			.locator( 'h2' );
		await expect( shippingHeading ).toBeVisible();

		const shippingOptionsHeading = page
			.getByRole( 'group', { name: 'Verzendopties' } )
			.locator( 'h2' );
		await expect( shippingOptionsHeading ).toBeVisible();

		const paymentMethodHeading = page
			.getByRole( 'group', { name: 'Betaalopties' } )
			.locator( 'h2' );
		await expect( paymentMethodHeading ).toBeVisible();

		const returnToCart = page.getByRole( 'link', {
			name: 'Ga terug naar winkelwagen',
		} );
		await expect( returnToCart ).toBeVisible();

		const submitButton = page.getByRole( 'button', {
			name: 'Plaats bestelling',
		} );
		await expect( submitButton ).toBeVisible();

		const orderSummaryHeader = page.getByRole( 'button', {
			name: 'Besteloverzicht',
		} );
		await expect( orderSummaryHeader ).toBeVisible();

		const subtotalLabel = page.getByText( 'Subtotaal' );
		await expect( subtotalLabel ).toBeVisible();

		const shippingLabel = page.getByText( 'Verzending' );
		await expect( shippingLabel ).toBeVisible();

		const totalLabel = page.getByText( 'Totaal', { exact: true } );
		await expect( totalLabel ).toBeVisible();
	} );
} );
