/**
 * External dependencies
 */
import { expect, test } from '@woocommerce/e2e-playwright-utils';
import { cli } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import { openMiniCart } from './utils';
import { REGULAR_PRICED_PRODUCT_NAME } from '../checkout/constants';

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

	test( 'User can see translation in empty Mini-Cart', async ( {
		page,
		frontendUtils,
	} ) => {
		await frontendUtils.emptyCart();
		await frontendUtils.goToShop();
		await openMiniCart( frontendUtils );

		await expect(
			page.getByText( 'Je winkelwagen is momenteel leeg!' )
		).toBeVisible();

		await expect(
			page.getByRole( 'link', { name: 'Begin met winkelen' } )
		).toBeVisible();
	} );

	test( 'User can see translation in filled Mini-Cart', async ( {
		page,
		frontendUtils,
	} ) => {
		await frontendUtils.goToShop();
		await page.getByLabel( 'Toevoegen aan winkelwagen: “Beanie“' ).click();
		await openMiniCart( frontendUtils );

		await expect(
			page.getByRole( 'heading', { name: 'Je winkelwagen (1 artikel)' } )
		).toBeVisible();

		await expect(
			page.getByRole( 'link', { name: 'Mijn winkelmand bekijken' } )
		).toBeVisible();

		await expect(
			page.getByRole( 'link', { name: 'Naar afrekenen' } )
		).toBeVisible();
	} );
} );

test.describe( 'Shopper → Tax', () => {
	test.beforeAll( async () => {
		await cli(
			`npm run wp-env run tests-cli -- wp option set woocommerce_prices_include_tax no`
		);
		await cli(
			`npm run wp-env run tests-cli -- wp option set woocommerce_tax_display_cart incl`
		);
	} );

	test.afterAll( async () => {
		await cli(
			`npm run wp-env run tests-cli -- wp option set woocommerce_prices_include_tax yes`
		);
		await cli(
			`npm run wp-env run tests-cli -- wp option set woocommerce_tax_display_cart excl`
		);
	} );

	test( 'User can see tax label and price including tax', async ( {
		frontendUtils,
		page,
	} ) => {
		await frontendUtils.emptyCart();
		await frontendUtils.goToShop();
		await frontendUtils.addToCart( REGULAR_PRICED_PRODUCT_NAME );
		await frontendUtils.goToMiniCart();

		await expect(
			page.getByTestId( 'mini-cart' ).getByLabel( '1 item in cart' )
		).toContainText( '(incl. tax)' );

		// Hovering over the mini cart should not change the label,
		// see https://github.com/woocommerce/woocommerce/issues/43691
		page.getByTestId( 'mini-cart' ).getByLabel( '1 item in cart' ).hover();
		await expect(
			page.getByTestId( 'mini-cart' ).getByLabel( '1 item in cart' )
		).toContainText( '(incl. tax)' );

		await cli(
			`npm run wp-env run tests-cli -- wp option set woocommerce_prices_include_tax yes`
		);
		await cli(
			`npm run wp-env run tests-cli -- wp option set woocommerce_tax_display_cart excl`
		);
		await page.reload();

		await expect(
			page.getByTestId( 'mini-cart' ).getByLabel( '1 item in cart' )
		).toContainText( '(ex. tax)' );
	} );
} );
