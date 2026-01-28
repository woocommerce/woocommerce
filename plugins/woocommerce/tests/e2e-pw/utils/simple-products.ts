/**
 * External dependencies
 */
import { expect } from '@playwright/test';
import type { Page } from '@playwright/test';

/**
 * This function simulates the clicking of the "Add New" link under the "product" section in the menu.
 *
 * @param page - The Playwright page object.
 */
export async function clickAddNewMenuItem( page: Page ): Promise< void > {
	await page
		.locator( '#menu-posts-product' )
		.getByRole( 'link', { name: 'Add New' } )
		.click();
}

/**
 * This function checks if the old product editor is visible.
 *
 * @param page - The Playwright page object.
 */
export async function expectOldProductEditor( page: Page ): Promise< void > {
	await expect(
		page.getByRole( 'heading', { name: 'Product data' } )
	).toBeVisible();
}

/**
 * This function checks if the block product editor is visible.
 *
 * @param page - The Playwright page object.
 */
export async function expectBlockProductEditor( page: Page ): Promise< void > {
	await expect(
		page.locator( '.woocommerce-product-header__inner h1' )
	).toContainText( 'Add new product' );
}

/**
 * Click on a block editor tab.
 *
 * @param tabName - The name of the tab to click.
 * @param page    - The Playwright page object.
 */
export async function clickOnTab(
	tabName: string,
	page: Page
): Promise< void > {
	await page
		.locator( '.woocommerce-product-tabs' )
		.getByRole( 'tab', { name: tabName } )
		.click();
}
