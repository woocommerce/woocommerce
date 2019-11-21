/**
 * @format
 */

const baseUrl = process.env.WP_BASE_URL;

const WP_ADMIN_NEW_PRODUCT = baseUrl + '/wp-admin/post-new.php?post_type=product';

const MY_ACCOUNT_ORDERS = baseUrl + '/my-account/orders/';
const MY_ACCOUNT_DOWNLOADS = baseUrl + '/my-account/downloads/';
const MY_ACCOUNT_ADDRESSES = baseUrl + '/my-account/edit-address/';
const MY_ACCOUNT_ACCOUNT_DETAILS = baseUrl + '/my-account/edit-account/';

const CustomerFlow = {
	goToOrders: async () => {
		await page.goto( MY_ACCOUNT_ORDERS, {
			waitUntil: 'networkidle0',
		} );
	},

	goToDownloads: async () => {
		await page.goto( MY_ACCOUNT_DOWNLOADS, {
			waitUntil: 'networkidle0',
		} );
	},

	goToAddresses: async () => {
		await page.goto( MY_ACCOUNT_ADDRESSES, {
			waitUntil: 'networkidle0',
		} );
	},

	goToAccountDetails: async () => {
		await page.goto( MY_ACCOUNT_ACCOUNT_DETAILS, {
			waitUntil: 'networkidle0',
		} );
	},
};

const StoreOwnerFlow = {
	openNewProduct: async () => {
		await page.goto( WP_ADMIN_NEW_PRODUCT, {
			waitUntil: 'networkidle0',
		} );
	},
};

export { CustomerFlow, StoreOwnerFlow };
