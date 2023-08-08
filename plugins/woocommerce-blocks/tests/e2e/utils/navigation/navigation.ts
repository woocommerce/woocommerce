/**
 * External dependencies
 */
import { Page, PlaywrightTestArgs } from '@playwright/test';
import { BlockData } from '@woocommerce/e2e-types';

/**
 * Closes any modals in the editor if they are open.
 */
export const closeModalIfExists = async ( page: Page ) => {
	// The modal close button can have different aria-labels, depending on the version of Gutenberg/WP.
	// Newer versions (WP >=6.2) use `Close`, while older versions (WP <6.1) use `Close dialog`.
	const closeButton = page.getByLabel( /^Close$|^Close dialog$/ );
	if ( ( await closeButton.count() ) > 0 ) {
		await closeButton.click();
	}
};

/**
 * Goes to the edit page of a specified block.
 */
export const editBlockPage = async (
	page: PlaywrightTestArgs[ 'page' ],
	{ name, selectors }: BlockData
) => {
	const {
		editor: { block: blockSelector },
	} = selectors;
	await page.goto(
		`/wp-admin/edit.php?post_type=page&s=${ encodeURIComponent( name ) }`
	);

	// This is the link to the edit page of the block, this is the page's title.
	await page
		.getByRole( 'link', { name: `“${ name } block” (Edit)` } )
		.click();

	await page.waitForSelector( blockSelector );
	await closeModalIfExists( page );
};
