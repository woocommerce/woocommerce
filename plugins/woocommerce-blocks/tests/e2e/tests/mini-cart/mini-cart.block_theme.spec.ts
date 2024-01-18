/**
 * External dependencies
 */
import { test, expect } from '@woocommerce/e2e-playwright-utils';

/**
 * Internal dependencies
 */
import { blockData, openMiniCart } from './utils';
import { REGULAR_PRICED_PRODUCT_NAME } from '../checkout/constants';

test.describe( `${ blockData.name } Block`, () => {
	/**
	 * This is a workaround to run tests in isolation.
	 * Ideally, the test should be run in isolation by default. But we're
	 * overriding the storageState in config which make all tests run with admin
	 * user.
	 */
	test.use( {
		storageState: {
			origins: [],
			cookies: [],
		},
	} );

	test( 'should the Mini Cart block be present near the navigation block', async ( {
		page,
		frontendUtils,
	} ) => {
		await frontendUtils.goToShop();
		const block = await frontendUtils.getBlockByName( blockData.slug );

		const navigationBlock = page.locator(
			`//div[@data-block-name='${ blockData.slug }']/preceding-sibling::nav[contains(@class, 'wp-block-navigation')]`
		);

		await expect( navigationBlock ).toBeVisible();
		await expect( block ).toBeVisible();
	} );

	test( 'should open the empty cart drawer', async ( {
		page,
		frontendUtils,
	} ) => {
		await frontendUtils.goToShop();
		await openMiniCart( frontendUtils );

		await expect( page.getByRole( 'dialog' ) ).toContainText(
			'Your cart is currently empty!'
		);
	} );

	test( 'should close the drawer when clicking on the close button', async ( {
		page,
		frontendUtils,
	} ) => {
		await frontendUtils.goToShop();
		await openMiniCart( frontendUtils );

		await expect( page.getByRole( 'dialog' ) ).toContainText(
			'Your cart is currently empty!'
		);

		await page.getByRole( 'button', { name: 'Close' } ).click();
		await expect( page.getByRole( 'dialog' ) ).toHaveCount( 0 );
	} );

	test( 'should close the drawer when clicking outside the drawer', async ( {
		page,
		frontendUtils,
	} ) => {
		await frontendUtils.goToShop();
		await openMiniCart( frontendUtils );

		await expect( page.getByRole( 'dialog' ) ).toContainText(
			'Your cart is currently empty!'
		);

		await page.mouse.click( 0, 0 );
		await expect( page.getByRole( 'dialog' ) ).toHaveCount( 0 );
	} );

	test( 'should open the filled cart drawer', async ( {
		page,
		frontendUtils,
	} ) => {
		await frontendUtils.goToShop();
		await page.click( 'text=Add to cart' );
		await openMiniCart( frontendUtils );

		await expect( page.getByRole( 'dialog' ) ).toContainText(
			'Your cart (1 item)'
		);
	} );

	test( 'should show the correct cart items count', async ( {
		page,
		frontendUtils,
	} ) => {
		await frontendUtils.emptyCart();
		await frontendUtils.goToShop();
		await frontendUtils.addToCart( REGULAR_PRICED_PRODUCT_NAME );
		await openMiniCart( frontendUtils );

		await expect(
			page.getByRole( 'heading', { name: 'Your cart (1 item)' } )
		).toBeVisible();

		await page.getByRole( 'button', { name: 'Close' } ).click();
		await frontendUtils.addToCart( REGULAR_PRICED_PRODUCT_NAME );
		await openMiniCart( frontendUtils );

		await expect(
			page.getByRole( 'heading', { name: 'Your cart (2 items)' } )
		).toBeVisible();
	} );

	test( 'should show the correct cart item name', async ( {
		page,
		frontendUtils,
	} ) => {
		await frontendUtils.emptyCart();
		await frontendUtils.goToShop();
		await frontendUtils.addToCart( REGULAR_PRICED_PRODUCT_NAME );
		await openMiniCart( frontendUtils );

		await expect(
			page.getByRole( 'link', { name: REGULAR_PRICED_PRODUCT_NAME } )
		).toBeVisible();
	} );

	test( 'should show subtotal, view cart button and checkout button', async ( {
		page,
		frontendUtils,
	} ) => {
		await frontendUtils.emptyCart();
		await frontendUtils.goToShop();
		await frontendUtils.addToCart( REGULAR_PRICED_PRODUCT_NAME );
		await openMiniCart( frontendUtils );

		await expect( page.getByText( 'Subtotal' ) ).toBeVisible();

		await expect(
			page.getByRole( 'link', { name: 'View my cart' } )
		).toBeVisible();

		await expect(
			page.getByRole( 'link', { name: 'Go to checkout' } )
		).toBeVisible();
	} );

	test( 'should allow to update the product quantity', async ( {
		page,
		frontendUtils,
	} ) => {
		await frontendUtils.emptyCart();
		await frontendUtils.goToShop();
		await frontendUtils.addToCart( REGULAR_PRICED_PRODUCT_NAME );
		await openMiniCart( frontendUtils );

		await expect(
			page.getByLabel( 'Quantity of Polo in your cart.' )
		).toHaveValue( '1' );

		await page
			.getByRole( 'button', { name: 'Increase quantity of Polo' } )
			.click();

		await expect(
			page.getByLabel( 'Quantity of Polo in your cart.' )
		).toHaveValue( '2' );

		await page
			.getByRole( 'button', { name: 'Reduce quantity of Polo' } )
			.click();

		await expect(
			page.getByLabel( 'Quantity of Polo in your cart.' )
		).toHaveValue( '1' );

		await expect(
			page.getByRole( 'button', { name: 'Reduce quantity of Polo' } )
		).toBeDisabled();
	} );

	test( 'should allow to remove a product from the cart', async ( {
		page,
		frontendUtils,
	} ) => {
		await frontendUtils.emptyCart();
		await frontendUtils.goToShop();
		await frontendUtils.addToCart( REGULAR_PRICED_PRODUCT_NAME );
		await openMiniCart( frontendUtils );

		await expect(
			page.getByRole( 'link', { name: REGULAR_PRICED_PRODUCT_NAME } )
		).toBeVisible();

		await page
			.getByRole( 'button', { name: 'Remove Polo from cart' } )
			.click();

		await expect(
			page.getByText( 'Your cart is currently empty!' )
		).toBeVisible();
	} );

	test( 'should allow to proceed to the cart page', async ( {
		page,
		frontendUtils,
	} ) => {
		await frontendUtils.emptyCart();
		await frontendUtils.goToShop();
		await frontendUtils.addToCart( REGULAR_PRICED_PRODUCT_NAME );
		await openMiniCart( frontendUtils );
		await page.getByRole( 'link', { name: 'View my cart' } ).click();
		await expect( page ).toHaveURL( /\/cart\/?$/ );
	} );

	test( 'should allow to proceed to the checkout page', async ( {
		page,
		frontendUtils,
	} ) => {
		await frontendUtils.emptyCart();
		await frontendUtils.goToShop();
		await frontendUtils.addToCart( REGULAR_PRICED_PRODUCT_NAME );
		await openMiniCart( frontendUtils );
		await page.getByRole( 'link', { name: 'Go to checkout' } ).click();
		await expect( page ).toHaveURL( /\/checkout\/?$/ );
	} );
} );
