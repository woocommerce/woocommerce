const {
	visitAdminPage,
	clearLocalStorage,
	setBrowserViewport,
	withRestApi
} from '@woocommerce/e2e-utils';

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
	if ( bulkSelector ) {
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
	}
	
	await merchant.logout();
}

/**
 * Use api package to delete products.
 *
 * @return {Promise} Promise resolving once products have been trashed.
 */
async function trashExistingProducts() {
	await merchant.login();
	// Visit `/wp-admin/edit.php?post_type=product` so we can see a list of products and delete them.
	await visitAdminPage( 'edit.php', 'post_type=product' );

	products = await repository.list();
	while ( products.length > 0 ) {
		for( let p = 0; p < products.length; p++ ) {
			await repository.delete( products[ p ].id );
		}
		products = await repository.list();
	}
}

/**
 * Use api package to delete coupons.
 *
 * @return {Promise} Promise resolving once coupons have been trashed.
 */
async function deleteAllCoupons() {
	const repository = Coupon.restRepository( factories.api.withDefaultPermalinks );
	let coupons;

	coupons = await repository.list();

	while ( coupons.length > 0 ) {
		for (let c = 0; c < coupons.length; c++ ) {
			await repository.delete( coupons[ c ].id );
		}
		coupons = await repository.list();
	}
}

// Before every test suite run, delete all content created by the test. This ensures
// other posts/comments/etc. aren't dirtying tests and tests don't depend on
// each other's side-effects.
beforeAll( async () => {
	await trashExistingPosts();
	await withRestApi.deleteAllProducts();
	await withRestApi.deleteAllCoupons();
	await clearLocalStorage();
	await setBrowserViewport( 'large' );
} );
