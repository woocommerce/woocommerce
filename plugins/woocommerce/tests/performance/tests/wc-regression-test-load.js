/* eslint-disable eqeqeq */
/* eslint-disable no-undef */
/**
 * Internal dependencies
 */
import { homePage } from '../requests/shopper/home.js';
import { shopPage } from '../requests/shopper/shop-page.js';
import { searchProduct } from '../requests/shopper/search-product.js';
import { singleProduct } from '../requests/shopper/single-product.js';
import { cart } from '../requests/shopper/cart.js';
import { cartRemoveItem } from '../requests/shopper/cart-remove-item.js';
import { cartApplyCoupon } from '../requests/shopper/cart-apply-coupon.js';
import { checkoutGuest } from '../requests/shopper/checkout-guest.js';
import { checkoutCustomerLogin } from '../requests/shopper/checkout-customer-login.js';
import { myAccountOrders } from '../requests/shopper/my-account-orders.js';
import { categoryPage } from '../requests/shopper/category-page.js';
import { products } from '../requests/merchant/products.js';
import { addProduct } from '../requests/merchant/add-product.js';
import { coupons } from '../requests/merchant/coupons.js';
import { orders } from '../requests/merchant/orders.js';
import { ordersSearch } from '../requests/merchant/orders-search.js';
import { ordersFilter } from '../requests/merchant/orders-filter.js';
import { addOrder } from '../requests/merchant/add-order.js';
import { homeWCAdmin } from '../requests/merchant/home-wc-admin.js';
import { myAccountMerchantLogin } from '../requests/merchant/my-account-merchant.js';
import { wpLogin } from '../requests/merchant/wp-login.js';
import { ordersAPI } from '../requests/api/orders.js';
import { admin_acc_login } from '../config.js';
import { addCustomerOrder } from '../setup/add-customer-order.js';

const shopper_request_threshold = 'p(95)<100000';
const merchant_request_threshold = 'p(95)<100000';
const api_request_threshold = 'p(95)<100000';

export const options = {
	scenarios: {
		merchantOrders: {
			executor: 'ramping-arrival-rate',
			startRate: 2, // starting iterations per timeUnit
			timeUnit: '20s',
			preAllocatedVUs: 5,
			maxVUs: 9,
			stages: [
				// target value is iterations per timeUnit
				{ target: 1, duration: '60s' },
				{ target: 2, duration: '500s' },
				{ target: 1, duration: '60' },
			],
			exec: 'merchantOrderFlows',
		},
		merchantOther: {
			executor: 'ramping-arrival-rate',
			startRate: 2, // starting iterations per timeUnit
			timeUnit: '20s',
			preAllocatedVUs: 5,
			maxVUs: 9,
			stages: [
				// target value is iterations per timeUnit
				{ target: 1, duration: '60s' },
				{ target: 2, duration: '500s' },
				{ target: 1, duration: '60' },
			],
			exec: 'merchantOtherFlows',
		},
		shopperBrowsing: {
			executor: 'ramping-arrival-rate',
			startRate: 2, // starting iterations per timeUnit
			timeUnit: '10s',
			preAllocatedVUs: 5,
			maxVUs: 9,
			stages: [
				// target value is iterations per timeUnit
				{ target: 2, duration: '60s' },
				{ target: 10, duration: '500s' },
				{ target: 1, duration: '60' },
			],
			exec: 'shopperBrowsingFlows',
		},
		shopperGuestCheckouts: {
			executor: 'ramping-arrival-rate',
			startRate: 2, // starting iterations per timeUnit
			timeUnit: '20s',
			preAllocatedVUs: 5,
			maxVUs: 9,
			stages: [
				// target value is iterations per timeUnit
				{ target: 1, duration: '60s' },
				{ target: 2, duration: '500s' },
				{ target: 1, duration: '60' },
			],
			exec: 'checkoutGuestFlow',
		},
		shopperCustomerCheckouts: {
			executor: 'ramping-arrival-rate',
			startRate: 2, // starting iterations per timeUnit
			timeUnit: '20s',
			preAllocatedVUs: 5,
			maxVUs: 9,
			stages: [
				// target value is iterations per timeUnit
				{ target: 1, duration: '60s' },
				{ target: 2, duration: '500s' },
				{ target: 1, duration: '60' },
			],
			exec: 'checkoutCustomerLoginFlow',
		},
		apiBackground: {
			executor: 'ramping-arrival-rate',
			startRate: 1, // starting iterations per timeUnit
			timeUnit: '30s',
			preAllocatedVUs: 5,
			maxVUs: 5,
			stages: [
				// target value is iterations per timeUnit
				{ target: 1, duration: '60s' },
				{ target: 2, duration: '500s' },
				{ target: 1, duration: '60' },
			],
			exec: 'allAPIFlow',
		},
	},
	thresholds: {
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
	addCustomerOrder();
}

// Use myAccountMerchantLogin() instead of wpLogin() if having issues with login.
export function merchantOrderFlows() {
	if ( admin_acc_login === true ) {
		myAccountMerchantLogin();
	} else {
		wpLogin();
	}
	addOrder();
	orders();
	ordersSearch();
	ordersFilter();
}

// Use myAccountMerchantLogin() instead of wpLogin() if having issues with login.
export function merchantOtherFlows() {
	if ( admin_acc_login === true ) {
		myAccountMerchantLogin();
	} else {
		wpLogin();
	}
	homeWCAdmin();
	addProduct();
	products();
	coupons();
}
export function shopperBrowsingFlows() {
	homePage();
	shopPage();
	searchProduct();
	singleProduct();
	cartRemoveItem();
	cartApplyCoupon();
	categoryPage();
}
export function checkoutGuestFlow() {
	cart();
	checkoutGuest();
}
export function checkoutCustomerLoginFlow() {
	cart();
	checkoutCustomerLogin();
	myAccountOrders();
}
export function allAPIFlow() {
	ordersAPI();
}
