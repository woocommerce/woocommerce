/**
 * @format
 */

const baseUrl = process.env.WP_BASE_URL;

const WP_ADMIN_NEW_PRODUCT = baseUrl + '/wp-admin/post-new.php?post_type=product';

const StoreOwnerFlow = {
	openNewProduct: async () => {
		await page.goto( WP_ADMIN_NEW_PRODUCT, {
			waitUntil: 'networkidle0',
		} );
	},
};

export { StoreOwnerFlow };
