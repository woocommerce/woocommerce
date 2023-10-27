/**
 * External dependencies
 */
import { test as base, expect } from '@woocommerce/e2e-playwright-utils';
import {
	installPluginFromPHPFile,
	uninstallPluginFromPHPFile,
} from '@woocommerce/e2e-mocks/custom-plugins';

/**
 * Internal dependencies
 */
import { CartPage } from './cart.page';
import {
	DISCOUNTED_PRODUCT_NAME,
	REGULAR_PRICED_PRODUCT_NAME,
} from '../checkout/constants';

const test = base.extend< { pageObject: CartPage } >( {
	pageObject: async ( { page }, use ) => {
		const pageObject = new CartPage( {
			page,
		} );
		await use( pageObject );
	},
} );

test.describe( 'Shopper → Cart block', () => {
	test( 'The discount label is only visible next to the discounted product', async ( {
		pageObject,
		frontendUtils,
	} ) => {
		await frontendUtils.goToShop();
		await frontendUtils.emptyCart();
		await frontendUtils.addToCart( DISCOUNTED_PRODUCT_NAME );
		await frontendUtils.addToCart( REGULAR_PRICED_PRODUCT_NAME );
		await frontendUtils.goToCart();
		// Check for discount on the discounted product
		const discountedProductRow = await pageObject.findProductRow(
			DISCOUNTED_PRODUCT_NAME
		);
		await expect( discountedProductRow ).toBeVisible();
		const capRegularPriceElement = discountedProductRow.locator(
			'.wc-block-components-product-price__regular'
		);
		const capDiscountedPriceElement = discountedProductRow.locator(
			'.wc-block-components-product-price__value.is-discounted'
		);
		await expect( capRegularPriceElement ).toBeVisible();
		await expect( capDiscountedPriceElement ).toBeVisible();

		// Check for absence of discount on the regular priced product
		const regularPricedProductRow = await pageObject.findProductRow(
			REGULAR_PRICED_PRODUCT_NAME
		);
		await expect( regularPricedProductRow ).toBeVisible();
		const hoodieRegularPriceElement = regularPricedProductRow.locator(
			'.wc-block-components-product-price__regular'
		);
		const hoodieDiscountedPriceElement = regularPricedProductRow.locator(
			'.wc-block-components-product-price__value.is-discounted'
		);
		await expect( hoodieRegularPriceElement ).toBeHidden();
		await expect( hoodieDiscountedPriceElement ).toBeHidden();
	} );

	test( 'Products with updated prices should not display a discount label', async ( {
		pageObject,
		frontendUtils,
	} ) => {
		await installPluginFromPHPFile(
			`${ __dirname }/update-price-plugin.php`
		);
		await frontendUtils.goToShop();
		await frontendUtils.emptyCart();
		await frontendUtils.addToCart( DISCOUNTED_PRODUCT_NAME );
		await frontendUtils.addToCart( REGULAR_PRICED_PRODUCT_NAME );
		await frontendUtils.goToCart();

		// Ensure no discount label is present on the products in the cart
		const capRow = await pageObject.findProductRow(
			DISCOUNTED_PRODUCT_NAME
		);
		await expect( capRow ).toBeVisible();
		const capRegularPriceElement = capRow.locator(
			'.wc-block-components-product-price__regular'
		);
		const capDiscountedPriceElement = capRow.locator(
			'.wc-block-components-product-price__value.is-discounted'
		);
		await expect( capRegularPriceElement ).toBeHidden();
		await expect( capDiscountedPriceElement ).toBeHidden();

		// Locate the price element within the Cap product row
		const capPriceElement = capRow
			.locator( '.wc-block-components-product-price__value' )
			.first();
		await expect( capPriceElement ).toBeVisible();

		// Get the text content of the price element and check the price
		const capPriceText = await capPriceElement.textContent();
		expect( capPriceText ).toBe( '$50.00' );

		const hoodieRow = await pageObject.findProductRow(
			REGULAR_PRICED_PRODUCT_NAME
		);
		await expect( hoodieRow ).toBeVisible();
		const hoodieRegularPriceElement = hoodieRow.locator(
			'.wc-block-components-product-price__regular'
		);
		const hoodieDiscountedPriceElement = hoodieRow.locator(
			'.wc-block-components-product-price__value.is-discounted'
		);
		await expect( hoodieRegularPriceElement ).toBeHidden();
		await expect( hoodieDiscountedPriceElement ).toBeHidden();

		// Locate the price element within the Hoodie with Logo product row
		const hoodiePriceElement = hoodieRow
			.locator( '.wc-block-components-product-price__value' )
			.first();
		await expect( hoodiePriceElement ).toBeVisible();

		// Get the text content of the price element and check the price
		const hoodiePriceText = await hoodiePriceElement.textContent();
		expect( hoodiePriceText ).toBe( '$50.00' );

		await uninstallPluginFromPHPFile(
			`${ __dirname }/update-price-plugin.php`
		);
	} );
} );
