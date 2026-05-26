/**
 * External dependencies
 */
import type { Page } from '@playwright/test';
import { getCanvas } from '@woocommerce/e2e-utils-playwright';

const fillPageTitle = async ( page: Page, title: string ) => {
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

	const canvas = await getCanvas( page );
	// Match by role/name to stay compatible across Gutenberg versions:
	// older releases expose the title as an `aria-label` ("Add title"
	// or "Block: Title"), while recent trunk only exposes the accessible
	// name through the textbox role.
	const block_title = canvas.getByRole( 'textbox', {
		name: /^(Add title|Block: Title)$/,
	} );
	await block_title.click();
	await block_title.fill( title );
};

export { fillPageTitle };
