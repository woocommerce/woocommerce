/**
 * External dependencies
 */
import type { Page } from '@playwright/test';
import { expect } from '@playwright/test';

/**
 * This function simulates the clicking of the "Add New" link under the "product" section in the menu.
 *
 * @param {import('@playwright/test').Page} page
 */
async function clickAddNewMenuItem( page: Page ) {
	await page
		.locator( '#menu-posts-product' )
		.getByRole( 'link', { name: 'Add New' } )
		.click();
}

/**
 * This function checks if the old product editor is visible.
 *
 * @param {import('@playwright/test').Page} page
 */
async function expectOldProductEditor( page: Page ) {
	await expect(
		page.getByRole( 'heading', { name: 'Product data' } )
	).toBeVisible();
}

/**
 * This function checks if the block product editor is visible.
 *
 * @param {import('@playwright/test').Page} page
 */
async function expectBlockProductEditor( page: Page ) {
	await expect(
		page.locator( '.woocommerce-product-header__inner h1' )
	).toContainText( 'Add new product' );
}

/**
 * Click on a block editor tab.
 *
 * @param {string}                          tabName
 * @param {import('@playwright/test').Page} page
 */
async function clickOnTab( tabName: string, page: Page ) {
	await page
		.locator( '.woocommerce-product-tabs' )
		.getByRole( 'tab', { name: tabName } )
		.click();
}

export {
	expectBlockProductEditor,
	expectOldProductEditor,
	clickAddNewMenuItem,
	clickOnTab,
};
