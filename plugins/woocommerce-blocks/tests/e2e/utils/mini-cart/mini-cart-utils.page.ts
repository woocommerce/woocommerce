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
		const block = await this.frontendUtils.getBlockByName(
			'woocommerce/mini-cart'
		);
		await block.click();
	}
}
