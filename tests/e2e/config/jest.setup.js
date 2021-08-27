import {
	clearLocalStorage,
	setBrowserViewport,
	withRestApi,
	WP_ADMIN_LOGIN
} from '@woocommerce/e2e-utils';

const config = require( 'config' );
const { HTTPClientFactory } = require( '@woocommerce/api' );
const { addConsoleSuppression, updateReadyPageStatus } = require( '@woocommerce/e2e-environment' );
const { DEFAULT_TIMEOUT_OVERRIDE } = process.env;

// @todo: remove this once https://github.com/woocommerce/woocommerce-admin/issues/6992 has been addressed
addConsoleSuppression( 'woocommerce_shared_settings', false );

/**
 * Uses the WordPress API to delete all existing posts
 */
async function trashExistingPosts() {
	const apiUrl = config.get('url');
	const wpPostsEndpoint = '/wp/v2/posts';
	const adminUsername = config.get('users.admin.username');
	const adminPassword = config.get('users.admin.password');
	const client = HTTPClientFactory.build(apiUrl)
		.withBasicAuth(adminUsername, adminPassword)
		.create();

	// List all existing posts
	const response = await client.get(wpPostsEndpoint);
	const posts = response.data;

	// Delete each post
	for (const post of posts) {
		await client.delete(`${wpPostsEndpoint}/${post.id}`);
	}
}

// Before every test suite run, delete all content created by the test. This ensures
// other posts/comments/etc. aren't dirtying tests and tests don't depend on
// each other's side-effects.
beforeAll(async () => {

	if ( DEFAULT_TIMEOUT_OVERRIDE ) {
		page.setDefaultNavigationTimeout( DEFAULT_TIMEOUT_OVERRIDE );
		page.setDefaultTimeout( DEFAULT_TIMEOUT_OVERRIDE );
	}

	try {
		// Update the ready page to prevent concurrent test runs
		await updateReadyPageStatus('draft');
		await trashExistingPosts();
		await withRestApi.deleteAllProducts();
		await withRestApi.deleteAllCoupons();
		await withRestApi.deleteAllOrders();
	} catch ( error ) {
		// Prevent an error here causing tests to fail.
	}

	await page.goto(WP_ADMIN_LOGIN);
	await clearLocalStorage();
	await setBrowserViewport('large');
});

// Clear browser cookies and cache using DevTools.
// This is to ensure that each test ends with no user logged in.
afterAll(async () => {
	// Reset the ready page to published to allow future test runs
	try {
		await updateReadyPageStatus('publish');
	} catch ( error ) {
		// Prevent an error here causing tests to fail.
	}

	const client = await page.target().createCDPSession();
	await client.send('Network.clearBrowserCookies');
	await client.send('Network.clearBrowserCache');
});
