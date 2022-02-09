const {
	visitAdminPage,
	clearLocalStorage,
	setBrowserViewport,
	withRestApi,
	WP_ADMIN_LOGIN
} from '@woocommerce/e2e-utils';

const config = require( 'config' );
const { HTTPClientFactory } = require( '@woocommerce/api' );
const { addConsoleSuppression, updateReadyPageStatus, setupJestRetries } = require( '@woocommerce/e2e-environment' );
const { DEFAULT_TIMEOUT_OVERRIDE } = process.env;

// @todo: remove this once https://github.com/woocommerce/woocommerce-admin/issues/6992 has been addressed
addConsoleSuppression('woocommerce_shared_settings', false);
// @todo: remove this once https://github.com/woocommerce/woocommerce/issues/31867 has been addressed
addConsoleSuppression('wp.compose.withState', false);

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

/**
 * Uses the WordPress API to update the Ready page's status.
 *
 * @param {string} status | Status to update the page to. One of: publish, future, draft, pending, private
 */
async function updateReadyPageStatus( status ) {
	const apiUrl = config.get('url');
	const wpPagesEndpoint = '/wp/v2/pages';
	const adminUsername = config.get('users.admin.username');
	const adminPassword = config.get('users.admin.password');
	const client = HTTPClientFactory.build(apiUrl)
		.withBasicAuth(adminUsername, adminPassword)
		.create();

	// As the default status filter in the API is `publish`, we need to
	// filter based on the supplied status otherwise no results are returned.
	let statusFilter = 'publish';
	if ( 'publish' === status ) {
		// The page will be in a draft state, so we need to filter on that status
		statusFilter = 'draft';
	}
	const getPostsResponse = await client.get(`${wpPagesEndpoint}?search=ready&status=${statusFilter}`);
	const pageId = getPostsResponse.data[0].id;

	// Update the page to the new status
	await client.post(`${wpPagesEndpoint}/${pageId}`, { 'status': status });
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
beforeAll(async () => {

	setupJestRetries( 2 );

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
	await setBrowserViewport( {
		width: 1280,
		height: 800,
	});
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
