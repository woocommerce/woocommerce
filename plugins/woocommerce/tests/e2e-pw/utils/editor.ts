/**
 * External dependencies
 */
import type { Page, FrameLocator, Locator } from '@playwright/test';
import e2eUtils from '@woocommerce/e2e-utils-playwright';

const { getCanvas } = e2eUtils;

/**
 * Fill the page title in the block editor.
 *
 * @param page  - Playwright Page object
 * @param title - Title to fill in
 */
export const fillPageTitle = async (
	page: Page,
	title: string
): Promise< void > => {
	// Close the Block Inserter if it's open.
	// Since Gutenberg 19.9 it is expanded by default.
	if (
		await page
			.getByRole( 'button', {
				name: /Toggle block inserter|Block Inserter/,
				expanded: true,
			} )
			.isVisible()
	) {
		await page.getByLabel( 'Close Block Inserter' ).click();
	}

	const canvas: FrameLocator | Page = await getCanvas( page );
	// Gutenberg (since 19.9) uses the "Block: Title" label.
	const block_title: Locator = canvas.getByLabel( /Add title|Block: Title/ );
	await block_title.click();
	await block_title.fill( title );
};
