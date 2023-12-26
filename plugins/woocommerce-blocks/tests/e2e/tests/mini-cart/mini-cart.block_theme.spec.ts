/**
 * External dependencies
 */
import { test, expect } from '@woocommerce/e2e-playwright-utils';
import { FrontendUtils } from '@woocommerce/e2e-utils';

const blockName = 'woocommerce/mini-cart';

const openMiniCart = async ( frontendUtils: FrontendUtils ) => {
	const block = await frontendUtils.getBlockByName( blockName );
	await block.click();
};

test.describe( `Mini Cart Block`, () => {
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

	test.beforeEach( async ( { page } ) => {
		await page.goto( `/shop`, { waitUntil: 'commit' } );
	} );

	test( 'should the Mini Cart block be present near the navigation block', async ( {
		page,
		frontendUtils,
	} ) => {
		const block = await frontendUtils.getBlockByName( blockName );

		// The Mini Cart block should be present near the navigation block.
		const navigationBlock = page.locator(
			`//div[@data-block-name='${ blockName }']/preceding-sibling::nav[contains(@class, 'wp-block-navigation')]`
		);

		await expect( navigationBlock ).toBeVisible();
		await expect( block ).toBeVisible();
	} );

	test( 'should open the empty cart drawer', async ( {
		page,
		frontendUtils,
	} ) => {
		await openMiniCart( frontendUtils );

		await expect( page.getByRole( 'dialog' ) ).toContainText(
			'Your cart is currently empty!'
		);
	} );

	test( 'should close the drawer when clicking on the close button', async ( {
		page,
		frontendUtils,
	} ) => {
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
		await page.click( 'text=Add to cart' );

		await openMiniCart( frontendUtils );

		await expect( page.getByRole( 'dialog' ) ).toContainText(
			'Your cart (1 item)'
		);
	} );
} );
