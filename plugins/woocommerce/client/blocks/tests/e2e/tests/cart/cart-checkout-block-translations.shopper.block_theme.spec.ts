/**
 * External dependencies
 */
import { expect, test as base, wpCLI } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import { SIMPLE_PHYSICAL_PRODUCT_NAME } from '../checkout/constants';
import { CheckoutPage } from '../checkout/checkout.page';
import { translations } from '../../test-data/data/data';
import { getTestTranslation } from '../../utils/get-test-translation';

const test = base.extend< { checkoutPageObject: CheckoutPage } >( {
	checkoutPageObject: async ( { page }, use ) => {
		const pageObject = new CheckoutPage( {
			page,
		} );
		await use( pageObject );
	},
} );

test.describe( 'Shopper → Translations', () => {
	test.beforeEach( async () => {
		await wpCLI( `site switch-language ${ translations.locale }` );
	} );

	test( 'User can view translated Cart block', async ( {
		frontendUtils,
		page,
	} ) => {
		await frontendUtils.emptyCart();
		await frontendUtils.goToShop();
		await frontendUtils.addToCart( SIMPLE_PHYSICAL_PRODUCT_NAME );
		await frontendUtils.goToCart();

		const totalsHeader = page
			.getByRole( 'columnheader', {
				name: getTestTranslation( 'Total' ),
			} )
			.locator( 'span' );
		await expect( totalsHeader ).toBeVisible();

		await expect(
			page.getByText( getTestTranslation( 'Remove item' ) )
		).toBeVisible();

		await expect(
			page.getByText( getTestTranslation( 'Cart totals' ) )
		).toBeVisible();

		await expect(
			page.getByRole( 'button', {
				name: getTestTranslation( 'Add coupons' ),
			} )
		).toBeVisible();

		await expect(
			page.getByRole( 'link', {
				name: getTestTranslation( 'Proceed to Checkout' ),
			} )
		).toBeVisible();
	} );

	test( 'User can view translated Checkout block', async ( {
		frontendUtils,
		page,
	} ) => {
		await frontendUtils.emptyCart();
		await frontendUtils.goToShop();
		await frontendUtils.addToCart( SIMPLE_PHYSICAL_PRODUCT_NAME );
		await frontendUtils.goToCheckout();

		await expect(
			page
				.getByRole( 'group', {
					name: getTestTranslation( 'Contact information' ),
				} )
				.locator( 'h2' )
		).toBeVisible();

		await expect(
			page
				.getByRole( 'group', {
					name: getTestTranslation( 'Shipping address' ),
				} )
				.locator( 'h2' )
		).toBeVisible();

		await expect(
			page
				.getByRole( 'group', {
					name: getTestTranslation( 'Shipping options' ),
				} )
				.locator( 'h2' )
		).toBeVisible();

		await expect(
			page
				.getByRole( 'group', {
					name: getTestTranslation( 'Payment options' ),
				} )
				.locator( 'h2' )
		).toBeVisible();

		await expect(
			page.getByRole( 'button', {
				name: getTestTranslation( 'Place Order' ),
			} )
		).toBeVisible();

		await expect(
			page.getByRole( 'heading', {
				name: getTestTranslation( 'Order summary' ),
			} )
		).toBeVisible();

		await expect(
			page.getByText( getTestTranslation( 'Subtotal' ) )
		).toBeVisible();

		await expect(
			page.getByText( getTestTranslation( 'Total' ), { exact: true } )
		).toBeVisible();
	} );
} );
