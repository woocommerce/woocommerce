/**
 * External dependencies
 */
import { expect, test, wpCLI } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import { REGULAR_PRICED_PRODUCT_NAME } from '../checkout/constants';
import { getTestTranslation } from '../../utils/get-test-translations';
import { translations } from '../../test-data/data/data';

test.describe( 'Shopper → Translations', () => {
	test.beforeEach( async () => {
		await wpCLI( `site switch-language ${ translations.locale }` );
	} );

	test( 'User can see translation in empty Mini-Cart', async ( {
		page,
		frontendUtils,
		miniCartUtils,
	} ) => {
		await frontendUtils.goToShop();
		await miniCartUtils.openMiniCart();

		await expect(
			page.getByText(
				getTestTranslation( 'Your cart is currently empty!' )
			)
		).toBeVisible();

		await expect(
			page.getByRole( 'link', {
				name: getTestTranslation( 'Start shopping' ),
			} )
		).toBeVisible();
	} );

	test( 'User can see translation in filled Mini-Cart', async ( {
		page,
		frontendUtils,
		miniCartUtils,
	} ) => {
		await frontendUtils.goToShop();
		await page
			.getByLabel( getTestTranslation( 'Add to cart: “Beanie“' ) )
			.click();
		await miniCartUtils.openMiniCart();

		await expect(
			page.getByRole( 'heading', {
				name: getTestTranslation( 'Your cart (1 item)' ),
			} )
		).toBeVisible();

		await expect(
			page.getByRole( 'link', {
				name: getTestTranslation( 'View my cart' ),
			} )
		).toBeVisible();

		await expect(
			page.getByRole( 'link', {
				name: getTestTranslation( 'Go to checkout' ),
			} )
		).toBeVisible();
	} );
} );

test.describe( 'Shopper → Tax', () => {
	test.beforeEach( async () => {
		await wpCLI( 'option set woocommerce_prices_include_tax no' );
		await wpCLI( 'option set woocommerce_tax_display_cart incl' );
	} );

	test( 'User can see tax label and price including tax', async ( {
		frontendUtils,
		page,
	} ) => {
		await frontendUtils.goToShop();
		await frontendUtils.addToCart( REGULAR_PRICED_PRODUCT_NAME );
		await frontendUtils.goToMiniCart();

		await expect(
			page.getByTestId( 'mini-cart' ).getByLabel( '1 item in cart' )
		).toContainText( '(incl. tax)' );

		// Hovering over the mini cart should not change the label,
		// see https://github.com/woocommerce/woocommerce/issues/43691
		await page
			.getByTestId( 'mini-cart' )
			.getByLabel( '1 item in cart' )
			.dispatchEvent( 'mouseover' );

		await expect(
			page.getByTestId( 'mini-cart' ).getByLabel( '1 item in cart' )
		).toContainText( '(incl. tax)' );

		await wpCLI( 'option set woocommerce_prices_include_tax yes' );
		await wpCLI( 'option set woocommerce_tax_display_cart excl' );
		await page.reload();

		await expect(
			page.getByTestId( 'mini-cart' ).getByLabel( '1 item in cart' )
		).toContainText( '(ex. tax)' );
	} );
} );
