/**
 * External dependencies
 */
import { FrontendUtils, expect } from '@woocommerce/e2e-utils';
import { Page } from '@playwright/test';

export class MiniCartUtils {
	private page: Page;
	private frontendUtils: FrontendUtils;

	constructor( page: Page, frontendUtils: FrontendUtils ) {
		this.page = page;
		this.frontendUtils = frontendUtils;
	}

	async openMiniCart() {
		const miniCartButton = await this.frontendUtils.getBlockByName(
			'woocommerce/mini-cart'
		);
		const miniCartContents = await this.frontendUtils.getBlockByName(
			'woocommerce/mini-cart-contents'
		);

		// When clicking the cart button right after the page loads, the drawer
		// script might not have been executed yet, so we need to retry the
		// click until the drawer is visible.
		await expect( async () => {
			await miniCartButton.click();
			await expect( miniCartContents ).toBeVisible();
		} ).toPass();
	}
}
