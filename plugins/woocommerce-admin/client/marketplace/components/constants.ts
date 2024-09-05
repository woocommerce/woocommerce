/**
 * Internal dependencies
 */
import { ADMIN_URL } from '../../utils/admin-settings';

export const DEFAULT_TAB_KEY = 'discover';
export const MARKETPLACE_HOST = 'https://woocommerce.com';
export const MARKETPLACE_PATH = '/extensions';
export const MARKETPLACE_SEARCH_API_PATH =
	'/wp-json/wccom-extensions/1.0/search';
export const MARKETPLACE_CATEGORY_API_PATH =
	'/wp-json/wccom-extensions/1.0/categories';
export const MARKETPLACE_ITEMS_PER_PAGE = 60;
export const MARKETPLACE_SEARCH_RESULTS_PER_PAGE = 8;
export const MARKETPLACE_CART_PATH = MARKETPLACE_HOST + '/cart/';
export const MARKETPLACE_RENEW_SUBSCRIPTON_PATH =
	MARKETPLACE_HOST + '/my-account/my-subscriptions/';
export const MARKETPLACE_COLLABORATION_PATH =
	MARKETPLACE_HOST +
	'/document/managing-woocommerce-com-subscriptions/#transfer-a-woocommerce-com-subscription';
export const MARKETPLACE_SHARING_PATH =
	MARKETPLACE_HOST +
	'/document/managing-woocommerce-com-subscriptions/#share-a-subscription';
export const WP_ADMIN_PLUGIN_LIST_URL = ADMIN_URL + 'plugins.php';
export const WOO_CONNECT_PLUGIN_DOWNLOAD_URL =
	MARKETPLACE_HOST + '/product-download/woo-update-manager';
