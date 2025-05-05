/**
 * External dependencies
 */
import { FrontendUtils } from '@woocommerce/e2e-utils';
import { Page } from '@playwright/test';

export class MiniCartUtils {
	private page: Page;
	private frontendUtils: FrontendUtils;

	constructor( page: Page, frontendUtils: FrontendUtils ) {
		this.page = page;
		this.frontendUtils = frontendUtils;
	}

	async openMiniCart() {
		const miniCartButton = this.page.locator(
			'.wc-block-mini-cart__button'
		);
		// The mini cart button scripts are loaded when the button is either
		// hovered or focused. The click event alone does not trigger neither of
		// those actions so we need to perform one explicitly.
		await miniCartButton.hover();
		await miniCartButton.click();
	}
}
