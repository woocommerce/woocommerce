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
	SIMPLE_PHYSICAL_PRODUCT_NAME,
	SIMPLE_VIRTUAL_PRODUCT_NAME,
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

	test( 'User can view empty cart message', async ( {
		frontendUtils,
		page,
	} ) => {
		await frontendUtils.emptyCart();
		await frontendUtils.goToCart();

		// Verify cart is empty
		await expect(
			page.getByRole( 'heading', {
				name: 'Your cart is currently empty!',
			} )
		).toBeVisible();
	} );

	test( 'User can remove a product from cart', async ( {
		frontendUtils,
		page,
	} ) => {
		await frontendUtils.goToShop();
		await frontendUtils.addToCart( SIMPLE_PHYSICAL_PRODUCT_NAME );
		await frontendUtils.goToCart();
		await page
			.getByLabel( `Remove ${ SIMPLE_PHYSICAL_PRODUCT_NAME } from cart` )
			.click();

		// Verify product is removed from the cart'
		await expect(
			page.getByRole( 'heading', {
				name: 'Your cart is currently empty!',
			} )
		).toBeVisible();
	} );

	test( 'User can update product quantity', async ( {
		frontendUtils,
		page,
	} ) => {
		await frontendUtils.goToShop();
		await frontendUtils.addToCart( SIMPLE_VIRTUAL_PRODUCT_NAME );
		await frontendUtils.goToCart();

		// Via the input field
		await page
			.getByLabel(
				`Quantity of ${ SIMPLE_VIRTUAL_PRODUCT_NAME } in your cart.`
			)
			.fill( '4' );

		// Verify the "Proceed to Checkout" button is disabled during network request
		await expect(
			page.getByRole( 'button', { name: 'Proceed to Checkout' } )
		).toBeDisabled();

		// Verify the "Proceed to Checkout" button is enabled after network request
		await expect(
			page.getByRole( 'link', { name: 'Proceed to Checkout' } )
		).toBeEnabled();

		await expect(
			page.getByLabel(
				`Quantity of ${ SIMPLE_VIRTUAL_PRODUCT_NAME } in your cart.`
			)
		).toHaveValue( '4' );

		// Via the plus button
		await page
			.getByLabel(
				`Increase quantity of ${ SIMPLE_VIRTUAL_PRODUCT_NAME }`
			)
			.click();
		// Verify the "Proceed to Checkout" button is disabled during network request
		await expect(
			page.getByRole( 'button', { name: 'Proceed to Checkout' } )
		).toBeDisabled();

		// Verify the "Proceed to Checkout" button is enabled after network request
		await expect(
			page.getByRole( 'link', { name: 'Proceed to Checkout' } )
		).toBeEnabled();

		await expect(
			page.getByLabel(
				`Quantity of ${ SIMPLE_VIRTUAL_PRODUCT_NAME } in your cart.`
			)
		).toHaveValue( '5' );

		// Via the minus button
		await page
			.getByLabel( `Reduce quantity of ${ SIMPLE_VIRTUAL_PRODUCT_NAME }` )
			.click();
		// Verify the "Proceed to Checkout" button is disabled during network request
		await expect(
			page.getByRole( 'button', { name: 'Proceed to Checkout' } )
		).toBeDisabled();

		// Verify the "Proceed to Checkout" button is enabled after network request
		await expect(
			page.getByRole( 'link', { name: 'Proceed to Checkout' } )
		).toBeEnabled();

		await expect(
			page.getByLabel(
				`Quantity of ${ SIMPLE_VIRTUAL_PRODUCT_NAME } in your cart.`
			)
		).toHaveValue( '4' );
	} );

	test( 'User can proceed to checkout', async ( { frontendUtils, page } ) => {
		await frontendUtils.goToShop();
		await frontendUtils.addToCart( SIMPLE_VIRTUAL_PRODUCT_NAME );
		await frontendUtils.goToCart();

		// Click on "Proceed to Checkout" button
		await page.getByRole( 'link', { name: 'Proceed to Checkout' } ).click();

		// Verify that you see the Checkout Block page
		await expect(
			page.getByRole( 'heading', { name: 'Checkout' } )
		).toBeVisible();
	} );

	test( 'User can see Cross-Sells products block', async ( {
		frontendUtils,
		page,
	} ) => {
		await frontendUtils.emptyCart();
		await frontendUtils.goToShop();
		await frontendUtils.addToCart( SIMPLE_PHYSICAL_PRODUCT_NAME );
		await frontendUtils.goToCart();
		await page.waitForSelector(
			'.wp-block-woocommerce-cart-cross-sells-block'
		);
		// Cap is the cross sells product that will be added to the cart
		await page
			.getByRole( 'button', { name: 'Add to cart: “Cap”' } )
			.click();
		await expect(
			page.getByLabel( `Quantity of Cap in your cart.` )
		).toHaveValue( '1' );
	} );
} );
