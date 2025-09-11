/**
 * External dependencies
 */
import { test, expect, BlockData } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import { REGULAR_PRICED_PRODUCT_NAME } from '../checkout/constants';

const blockData: BlockData = {
	name: 'Mini-Cart',
	slug: 'woocommerce/mini-cart',
	mainClass: '.wc-block-minicart',
	selectors: {
		frontend: {},
		editor: {},
	},
};

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
		miniCartUtils,
	} ) => {
		await frontendUtils.goToShop();
		await miniCartUtils.openMiniCart();

		await expect( page.getByRole( 'dialog' ) ).toContainText(
			'Your cart is currently empty!'
		);
	} );

	test( 'should close the drawer when clicking on the close button', async ( {
		page,
		frontendUtils,
		miniCartUtils,
	} ) => {
		await frontendUtils.goToShop();
		await miniCartUtils.openMiniCart();

		await expect( page.getByRole( 'dialog' ) ).toContainText(
			'Your cart is currently empty!'
		);

		await page.getByRole( 'button', { name: 'Close' } ).click();
		await expect( page.getByRole( 'dialog' ) ).toHaveCount( 0 );
	} );

	test( 'should close the drawer when clicking outside the drawer', async ( {
		page,
		frontendUtils,
		miniCartUtils,
	} ) => {
		await frontendUtils.goToShop();
		await miniCartUtils.openMiniCart();

		await expect( page.getByRole( 'dialog' ) ).toContainText(
			'Your cart is currently empty!'
		);

		await page.mouse.click( 0, 0 );
		await expect( page.getByRole( 'dialog' ) ).toHaveCount( 0 );
	} );

	test( 'should open the filled cart drawer', async ( {
		page,
		frontendUtils,
		miniCartUtils,
	} ) => {
		await frontendUtils.goToShop();
		await page.click( 'text=Add to cart' );
		await miniCartUtils.openMiniCart();
		const miniCartTitleBlock = page.locator(
			'[data-block-name="woocommerce/mini-cart-title-block"]'
		);
		const titleText = await miniCartTitleBlock.innerText();
		await expect( miniCartTitleBlock ).toBeVisible();
		expect(
			titleText?.includes( '(1 item)' ) ||
				titleText?.includes( '(items: 1)' )
		).toBeTruthy();
	} );

	test( 'should show the correct cart items count', async ( {
		page,
		frontendUtils,
		miniCartUtils,
	} ) => {
		await frontendUtils.goToShop();
		await frontendUtils.addToCart( REGULAR_PRICED_PRODUCT_NAME );
		await miniCartUtils.openMiniCart();
		const miniCartTitleBlock = page.locator(
			'[data-block-name="woocommerce/mini-cart-title-block"]'
		);
		let titleText = await miniCartTitleBlock.innerText();

		await expect( miniCartTitleBlock ).toBeVisible();
		expect(
			titleText?.includes( '(1 item)' ) ||
				titleText?.includes( '(items: 1)' )
		).toBeTruthy();

		await page.getByRole( 'button', { name: 'Close' } ).click();

		// Mini cart gets out of sync if triggered to open and close very quickly. PW interacts too quickly
		// and this isn't something that you'll see often in real use. This waits for the mini cart to close.
		await expect( page.getByRole( 'dialog' ) ).toBeHidden();

		await frontendUtils.addToCart( REGULAR_PRICED_PRODUCT_NAME );
		await miniCartUtils.openMiniCart();

		await expect( miniCartTitleBlock ).toBeVisible();
		titleText = await miniCartTitleBlock.innerText();
		expect(
			titleText?.includes( '(2 items)' ) ||
				titleText?.includes( '(items: 2)' )
		).toBeTruthy();
	} );

	test( 'should show the correct cart item name', async ( {
		page,
		frontendUtils,
		miniCartUtils,
	} ) => {
		await frontendUtils.goToShop();
		await frontendUtils.addToCart( REGULAR_PRICED_PRODUCT_NAME );
		await miniCartUtils.openMiniCart();

		await expect(
			page
				.getByRole( 'link', { name: REGULAR_PRICED_PRODUCT_NAME } )
				.filter( { has: page.locator( ':visible' ) } )
		).toBeVisible();
	} );

	test( 'should show subtotal, view cart button and checkout button', async ( {
		page,
		frontendUtils,
		miniCartUtils,
	} ) => {
		await frontendUtils.goToShop();
		await frontendUtils.addToCart( REGULAR_PRICED_PRODUCT_NAME );
		await miniCartUtils.openMiniCart();

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
		miniCartUtils,
	} ) => {
		await frontendUtils.goToShop();
		await frontendUtils.addToCart( REGULAR_PRICED_PRODUCT_NAME );
		await miniCartUtils.openMiniCart();

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
		miniCartUtils,
	} ) => {
		await frontendUtils.goToShop();
		await frontendUtils.addToCart( REGULAR_PRICED_PRODUCT_NAME );
		await miniCartUtils.openMiniCart();

		await expect(
			page
				.getByRole( 'link', { name: REGULAR_PRICED_PRODUCT_NAME } )
				.filter( { has: page.locator( ':visible' ) } )
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
		miniCartUtils,
	} ) => {
		await frontendUtils.goToShop();
		await frontendUtils.addToCart( REGULAR_PRICED_PRODUCT_NAME );
		await miniCartUtils.openMiniCart();
		await page.getByRole( 'link', { name: 'View my cart' } ).click();
		await expect( page ).toHaveURL( /\/cart\/?$/ );
	} );

	test( 'should allow to proceed to the checkout page', async ( {
		page,
		frontendUtils,
		miniCartUtils,
	} ) => {
		await frontendUtils.goToShop();
		await frontendUtils.addToCart( REGULAR_PRICED_PRODUCT_NAME );
		await miniCartUtils.openMiniCart();
		await page.getByRole( 'link', { name: 'Go to checkout' } ).click();
		await expect( page ).toHaveURL( /\/checkout\/?$/ );
	} );
} );
