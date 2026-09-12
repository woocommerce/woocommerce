/**
 * External dependencies
 */
import { test as base, expect } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import { CartPage } from './cart.page';
import {
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
	test( 'User can add, update, remove, and proceed from the cart', async ( {
		frontendUtils,
		page,
		pageObject,
	} ) => {
		await frontendUtils.goToCart();

		await expect(
			page.getByRole( 'heading', {
				name: 'Your cart is currently empty!',
			} )
		).toBeVisible();

		await frontendUtils.goToShop();
		await frontendUtils.addToCart( SIMPLE_PHYSICAL_PRODUCT_NAME );
		await frontendUtils.goToShop();
		await frontendUtils.addToCart( SIMPLE_VIRTUAL_PRODUCT_NAME );
		await frontendUtils.goToCart();

		const physicalRow = await pageObject.findProductRow(
			SIMPLE_PHYSICAL_PRODUCT_NAME
		);
		const virtualRow = await pageObject.findProductRow(
			SIMPLE_VIRTUAL_PRODUCT_NAME
		);
		await expect( physicalRow ).toHaveCount( 1 );
		await expect( physicalRow ).toBeVisible();
		await expect( virtualRow ).toHaveCount( 1 );
		await expect( virtualRow ).toBeVisible();

		const quantityInput = page.getByLabel(
			`Quantity of ${ SIMPLE_VIRTUAL_PRODUCT_NAME } in your cart.`
		);
		const proceedToCheckout = page.getByRole( 'link', {
			name: 'Proceed to Checkout',
		} );

		await quantityInput.fill( '4' );

		await expect( proceedToCheckout ).toBeDisabled();
		await expect( proceedToCheckout ).toBeEnabled();
		await expect( quantityInput ).toHaveValue( '4' );

		await page
			.getByLabel(
				`Increase quantity of ${ SIMPLE_VIRTUAL_PRODUCT_NAME }`
			)
			.click();
		await expect( proceedToCheckout ).toBeDisabled();
		await expect( proceedToCheckout ).toBeEnabled();
		await expect( quantityInput ).toHaveValue( '5' );

		await page
			.getByLabel( `Reduce quantity of ${ SIMPLE_VIRTUAL_PRODUCT_NAME }` )
			.click();
		await expect( proceedToCheckout ).toBeDisabled();
		await expect( proceedToCheckout ).toBeEnabled();
		await expect( quantityInput ).toHaveValue( '4' );

		await physicalRow
			.getByLabel( `Remove ${ SIMPLE_PHYSICAL_PRODUCT_NAME } from cart` )
			.click();
		await expect( physicalRow ).toBeHidden();
		await expect( virtualRow ).toBeVisible();
		await expect( quantityInput ).toHaveValue( '4' );

		await proceedToCheckout.click();

		await expect(
			page.getByRole( 'button', { name: 'Place Order' } )
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
			page.getByRole( 'link', { name: 'Proceed to Checkout' } )
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
			page.getByRole( 'link', { name: 'Proceed to Checkout' } )
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
			page.getByRole( 'link', { name: 'Proceed to Checkout' } )
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

	test( 'User can see Cross-Sells products block', async ( {
		frontendUtils,
		page,
	} ) => {
		await frontendUtils.goToShop();
		await frontendUtils.addToCart( SIMPLE_PHYSICAL_PRODUCT_NAME );
		await frontendUtils.goToCart();
		await page
			.locator( '.wp-block-woocommerce-product-collection' )
			.waitFor();
		// Cap is the cross sells product that will be added to the cart
		await page
			.getByRole( 'button', { name: 'Add to cart: “Cap”' } )
			.click();
		await expect(
			page.getByLabel( `Quantity of Cap in your cart.` )
		).toHaveValue( '1' );
	} );
} );
