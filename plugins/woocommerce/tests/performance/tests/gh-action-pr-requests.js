/**
 * k6 dependencies
 */
import encoding from 'k6/encoding';
import http from 'k6/http';
import { check } from 'k6';

/**
 * Internal dependencies
 */
import { homePage } from '../requests/shopper/home.js';
import { shopPage } from '../requests/shopper/shop-page.js';
import { searchProduct } from '../requests/shopper/search-product.js';
import { singleProduct } from '../requests/shopper/single-product.js';
import { cart } from '../requests/shopper/cart.js';
import { cartRemoveItem } from '../requests/shopper/cart-remove-item.js';
import { checkoutGuest } from '../requests/shopper/checkout-guest.js';
import { checkoutCustomerLogin } from '../requests/shopper/checkout-customer-login.js';
import { myAccount } from '../requests/shopper/my-account.js';
import { myAccountOrders } from '../requests/shopper/my-account-orders.js';
import { categoryPage } from '../requests/shopper/category-page.js';
import { wpLogin } from '../requests/merchant/wp-login.js';
import { products } from '../requests/merchant/products.js';
import { addProduct } from '../requests/merchant/add-product.js';
import { coupons } from '../requests/merchant/coupons.js';
import { orders } from '../requests/merchant/orders.js';
import { ordersSearch } from '../requests/merchant/orders-search.js';
import { ordersFilter } from '../requests/merchant/orders-filter.js';
import { addOrder } from '../requests/merchant/add-order.js';
import { ordersAPI } from '../requests/api/orders.js';
import { homeWCAdmin } from '../requests/merchant/home-wc-admin.js';
import { base_url, admin_username, admin_password } from '../config.js';

const shopper_request_threshold = 'p(95)<10000';
const merchant_request_threshold = 'p(95)<10000';
const api_request_threshold = 'p(95)<10000';

export const options = {
	scenarios: {
		shopperBrowseSmoke: {
			executor: 'per-vu-iterations',
			vus: 1,
			iterations: 3,
			maxDuration: '180s',
			exec: 'shopperBrowseFlow',
		},
		myAccountSmoke: {
			executor: 'per-vu-iterations',
			vus: 1,
			iterations: 3,
			maxDuration: '60s',
			startTime: '20s',
			exec: 'myAccountFlow',
		},
		cartSmoke: {
			executor: 'per-vu-iterations',
			vus: 1,
			iterations: 3,
			maxDuration: '60s',
			startTime: '25s',
			exec: 'cartFlow',
		},
		checkoutGuestSmoke: {
			executor: 'per-vu-iterations',
			vus: 1,
			iterations: 3,
			maxDuration: '120s',
			startTime: '30s',
			exec: 'checkoutGuestFlow',
		},
		checkoutCustomerLoginSmoke: {
			executor: 'per-vu-iterations',
			vus: 1,
			iterations: 3,
			maxDuration: '120s',
			startTime: '40s',
			exec: 'checkoutCustomerLoginFlow',
		},
		allMerchantSmoke: {
			executor: 'per-vu-iterations',
			vus: 1,
			iterations: 3,
			maxDuration: '360s',
			exec: 'allMerchantFlow',
		},
		allAPISmoke: {
			executor: 'per-vu-iterations',
			vus: 1,
			iterations: 3,
			maxDuration: '120s',
			exec: 'allAPIFlow',
		},
	},
	thresholds: {
		checks: [ 'rate==1' ],
		// Listing individual metrics due to https://github.com/grafana/k6/issues/1321
		'http_req_duration{name:Shopper - Site Root}': [
			`${ shopper_request_threshold }`,
		],
		'http_req_duration{name:Shopper - Shop Page}': [
			`${ shopper_request_threshold }`,
		],
		'http_req_duration{name:Shopper - Search Products}': [
			`${ shopper_request_threshold }`,
		],
		'http_req_duration{name:Shopper - Category Page}': [
			`${ shopper_request_threshold }`,
		],
		'http_req_duration{name:Shopper - Product Page}': [
			`${ shopper_request_threshold }`,
		],
		'http_req_duration{name:Shopper - wc-ajax=add_to_cart}': [
			`${ shopper_request_threshold }`,
		],
		'http_req_duration{name:Shopper - View Cart}': [
			`${ shopper_request_threshold }`,
		],
		'http_req_duration{name:Shopper - Remove Item From Cart}': [
			`${ shopper_request_threshold }`,
		],
		'http_req_duration{name:Shopper - wc-ajax=apply_coupon}': [
			`${ shopper_request_threshold }`,
		],
		'http_req_duration{name:Shopper - Update Cart}': [
			`${ shopper_request_threshold }`,
		],
		'http_req_duration{name:Shopper - View Checkout}': [
			`${ shopper_request_threshold }`,
		],
		'http_req_duration{name:Shopper - wc-ajax=update_order_review}': [
			`${ shopper_request_threshold }`,
		],
		'http_req_duration{name:Shopper - wc-ajax=checkout}': [
			`${ shopper_request_threshold }`,
		],
		'http_req_duration{name:Shopper - Order Received}': [
			`${ shopper_request_threshold }`,
		],
		'http_req_duration{name:Shopper - wc-ajax=get_refreshed_fragments}': [
			`${ shopper_request_threshold }`,
		],
		'http_req_duration{name:Shopper - Login to Checkout}': [
			`${ shopper_request_threshold }`,
		],
		'http_req_duration{name:Shopper - My Account Login Page}': [
			`${ shopper_request_threshold }`,
		],
		'http_req_duration{name:Shopper - Login to My Account}': [
			`${ shopper_request_threshold }`,
		],
		'http_req_duration{name:Shopper - My Account}': [
			`${ shopper_request_threshold }`,
		],
		'http_req_duration{name:Shopper - My Account Orders}': [
			`${ shopper_request_threshold }`,
		],
		'http_req_duration{name:Shopper - My Account Open Order}': [
			`${ shopper_request_threshold }`,
		],
		'http_req_duration{name:Merchant - WP Login Page}': [
			`${ merchant_request_threshold }`,
		],
		'http_req_duration{name:Merchant - Login to WP Admin}': [
			`${ merchant_request_threshold }`,
		],
		'http_req_duration{name:Merchant - WC-Admin}': [
			`${ merchant_request_threshold }`,
		],
		'http_req_duration{name:Merchant - wc-analytics/orders?}': [
			`${ merchant_request_threshold }`,
		],
		'http_req_duration{name:Merchant - wc-analytics/products/reviews?}': [
			`${ merchant_request_threshold }`,
		],
		'http_req_duration{name:Merchant - wc-analytics/products/low-in-stock?}':
			[ `${ merchant_request_threshold }` ],
		'http_req_duration{name:Merchant - All Orders}': [
			`${ merchant_request_threshold }`,
		],
		'http_req_duration{name:Merchant - Completed Orders}': [
			`${ merchant_request_threshold }`,
		],
		'http_req_duration{name:Merchant - New Order Page}': [
			`${ merchant_request_threshold }`,
		],
		'http_req_duration{name:Merchant - Create New Order}': [
			`${ merchant_request_threshold }`,
		],
		'http_req_duration{name:Merchant - Open Order}': [
			`${ merchant_request_threshold }`,
		],
		'http_req_duration{name:Merchant - Update Existing Order Status}': [
			`${ merchant_request_threshold }`,
		],
		'http_req_duration{name:Merchant - Search Orders By Product}': [
			`${ merchant_request_threshold }`,
		],
		'http_req_duration{name:Merchant - Search Orders By Customer Email}': [
			`${ merchant_request_threshold }`,
		],
		'http_req_duration{name:Merchant - Search Orders By Customer Address}':
			[ `${ merchant_request_threshold }` ],
		'http_req_duration{name:Merchant - Filter Orders By Month}': [
			`${ merchant_request_threshold }`,
		],
		'http_req_duration{name:Merchant - Filter Orders By Customer}': [
			`${ merchant_request_threshold }`,
		],
		'http_req_duration{name:Merchant - All Products}': [
			`${ merchant_request_threshold }`,
		],
		'http_req_duration{name:Merchant - Add New Product}': [
			`${ merchant_request_threshold }`,
		],
		'http_req_duration{name:Merchant - action=sample-permalink}': [
			`${ merchant_request_threshold }`,
		],
		'http_req_duration{name:Merchant - action=heartbeat autosave}': [
			`${ merchant_request_threshold }`,
		],
		'http_req_duration{name:Merchant - Update New Product}': [
			`${ merchant_request_threshold }`,
		],
		'http_req_duration{name:Merchant - Coupons}': [
			`${ merchant_request_threshold }`,
		],
		'http_req_duration{name:Merchant - wc-admin/onboarding/tasks?}': [
			`${ merchant_request_threshold }`,
		],
		'http_req_duration{name:Merchant - wc-analytics/admin/notes?}': [
			`${ merchant_request_threshold }`,
		],
		'http_req_duration{name:Merchant - wc-admin/options?options=woocommerce_ces_tracks_queue}':
			[ `${ merchant_request_threshold }` ],
		'http_req_duration{name:Merchant - action=heartbeat}': [
			`${ merchant_request_threshold }`,
		],
		'http_req_duration{name:API - Create Order}': [
			`${ api_request_threshold }`,
		],
		'http_req_duration{name:API - Retrieve Order}': [
			`${ api_request_threshold }`,
		],
		'http_req_duration{name:API - Update Order (Status)}': [
			`${ api_request_threshold }`,
		],
		'http_req_duration{name:API - Delete Order}': [
			`${ api_request_threshold }`,
		],
		'http_req_duration{name:API - Batch Create Orders}': [
			`${ api_request_threshold }`,
		],
		'http_req_duration{name:API - Batch Update (Status) Orders}': [
			`${ api_request_threshold }`,
		],
	},
};

export function setup() {
	/**
	 * Convert Cart & Checkout pages to shortcode
	 */
	function useCartCheckoutShortcodes() {
		/**
		 * A WordPress page.
		 * @typedef {Object} WPPage
		 * @property {number} id
		 * @property {string} slug
		 */

		let defaultHeaders;

		/**
		 * @type {WPPage[]}
		 */
		let wp_pages;

		/**
		 * @type {WPPage}
		 */
		let cart;

		/**
		 * @type {WPPage}
		 */
		let checkout;

		function setDefaultHeaders() {
			const credentials = `${ admin_username }:${ admin_password }`;
			const encodedCredentials = encoding.b64encode( credentials );
			defaultHeaders = {
				Authorization: `Basic ${ encodedCredentials }`,
				'Content-Type': 'application/json',
			};
		}

		function listWPPages() {
			const url = `${ base_url }/wp-json/wp/v2/pages`;
			const body = {
				_fields: [ 'id', 'slug' ],
				context: 'edit',
			};
			const params = {
				headers: defaultHeaders,
			};
			const response = http.get( url, body, params );
			check( response, {
				'WP pages list obtained': ( r ) => r.status === 200,
			} );
			wp_pages = response.json();
		}

		function findCartCheckoutPage() {
			cart = wp_pages.find( ( page ) => page.slug === 'cart' );
			checkout = wp_pages.find( ( page ) => page.slug === 'checkout' );
		}

		function convertToCartCheckoutShortcode() {
			const url_cart = `${ base_url }/wp-json/wp/v2/pages/${ cart.id }`;
			const url_checkout = `${ base_url }/wp-json/wp/v2/pages/${ checkout.id }`;

			const body_cart = {
				content: {
					raw: '<!-- wp:shortcode -->[woocommerce_cart]<!-- /wp:shortcode -->',
				},
			};
			const body_checkout = {
				content: {
					raw: '<!-- wp:shortcode -->[woocommerce_checkout]<!-- /wp:shortcode -->',
				},
			};

			const body_cart_str = JSON.stringify( body_cart );
			const body_checkout_str = JSON.stringify( body_checkout );

			const params = {
				headers: defaultHeaders,
			};

			const response_cart = http.post( url_cart, body_cart_str, params );
			const response_checkout = http.post(
				url_checkout,
				body_checkout_str,
				params
			);

			check( response_cart, {
				'cart shortcode set': ( r ) =>
					r.status >= 200 && r.status < 300,
			} );
			check( response_checkout, {
				'checkout shortcode set': ( r ) =>
					r.status >= 200 && r.status < 300,
			} );
		}

		setDefaultHeaders();

		listWPPages();

		findCartCheckoutPage();

		convertToCartCheckoutShortcode();
	}

	useCartCheckoutShortcodes();

	// Other setup steps in the future
}

export function shopperBrowseFlow() {
	homePage();
	shopPage();
	categoryPage();
	searchProduct();
	singleProduct();
}
export function checkoutGuestFlow() {
	cart();
	checkoutGuest();
}
export function checkoutCustomerLoginFlow() {
	cart();
	checkoutCustomerLogin();
}
export function myAccountFlow() {
	myAccount();
	myAccountOrders();
}
export function cartFlow() {
	cartRemoveItem();
}
export function allMerchantFlow() {
	wpLogin();
	homeWCAdmin( { other: false, orders: false, reviews: false, products: false} );
	addOrder();
	orders();
	ordersSearch();
	ordersFilter();
	addProduct();
	products();
	coupons();
}

export function allAPIFlow() {
	ordersAPI();
}
