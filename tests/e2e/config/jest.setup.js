const {
	visitAdminPage,
	switchUserToTest,
	clearLocalStorage,
	setBrowserViewport
} = require( "@wordpress/e2e-test-utils" );

const { merchant } = require( '@woocommerce/e2e-utils' );

/**
 * Navigates to the post listing screen and bulk-trashes any posts which exist.
 *
 * @return {Promise} Promise resolving once posts have been trashed.
 */
async function trashExistingPosts() {
	await merchant.login();
	// Visit `/wp-admin/edit.php` so we can see a list of posts and delete them.
	await visitAdminPage( 'edit.php' );

	// If this selector doesn't exist there are no posts for us to delete.
	const bulkSelector = await page.$( '#bulk-action-selector-top' );
	if ( ! bulkSelector ) {
		return;
	}

	// Select all posts.
	await page.waitForSelector( '#cb-select-all-1' );
	await page.click( '#cb-select-all-1' );
	// Select the "bulk actions" > "trash" option.
	await page.select( '#bulk-action-selector-top', 'trash' );
	// Submit the form to send all draft/scheduled/published posts to the trash.
	await page.click( '#doaction' );
	await page.waitForXPath(
		'//*[contains(@class, "updated notice")]/p[contains(text(), "moved to the Trash.")]'
	);
	await switchUserToTest();
}

/**
 * Navigates to the product listing screen and bulk-trashes any product which exist.
 *
 * @return {Promise} Promise resolving once products have been trashed.
 */
async function trashExistingProducts() {
	await merchant.login();
	// Visit `/wp-admin/edit.php?post_type=product` so we can see a list of products and delete them.
	await visitAdminPage( 'edit.php', 'post_type=product' );

	// If this selector doesn't exist there are no products for us to delete.
	const bulkSelector = await page.$( '#bulk-action-selector-top' );
	if ( ! bulkSelector ) {
		return;
	}

	// Select all products.
	await page.waitForSelector( '#cb-select-all-1' );
	await page.click( '#cb-select-all-1' );
	// Select the "bulk actions" > "trash" option.
	await page.select( '#bulk-action-selector-top', 'trash' );
	// Submit the form to send all draft/scheduled/published posts to the trash.
	await page.click( '#doaction' );
	await page.waitForXPath(
		'//*[contains(@class, "updated notice")]/p[contains(text(), "moved to the Trash.")]'
	);
	await switchUserToTest();
}

// Before every test suite run, delete all content created by the test. This ensures
// other posts/comments/etc. aren't dirtying tests and tests don't depend on
// each other's side-effects.
beforeAll( async () => {
	await trashExistingPosts();
	await trashExistingProducts();
	await switchUserToTest();
	await clearLocalStorage();
	await setBrowserViewport( 'large' );
} );
