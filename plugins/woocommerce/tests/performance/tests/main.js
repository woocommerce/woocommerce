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
import { addCustomerOrder } from '../setup/add-customer-order.js';

const defaultIterations = 3;

export const options = {
	scenarios: {
		shopperBrowseSmoke: {
			executor: 'per-vu-iterations',
			vus: 1,
			iterations: defaultIterations,
			maxDuration: '180s',
			exec: 'shopperBrowseFlow',
		},
		myAccountSmoke: {
			executor: 'per-vu-iterations',
			vus: 1,
			iterations: defaultIterations,
			maxDuration: '60s',
			startTime: '20s',
			exec: 'myAccountFlow',
		},
		cartSmoke: {
			executor: 'per-vu-iterations',
			vus: 1,
			iterations: defaultIterations,
			maxDuration: '60s',
			startTime: '25s',
			exec: 'cartFlow',
		},
		checkoutGuestSmoke: {
			executor: 'per-vu-iterations',
			vus: 1,
			iterations: defaultIterations,
			maxDuration: '120s',
			startTime: '30s',
			exec: 'checkoutGuestFlow',
		},
		checkoutCustomerLoginSmoke: {
			executor: 'per-vu-iterations',
			vus: 1,
			iterations: defaultIterations,
			maxDuration: '120s',
			startTime: '40s',
			exec: 'checkoutCustomerLoginFlow',
		},
		allMerchantSmoke: {
			executor: 'per-vu-iterations',
			vus: 1,
			iterations: defaultIterations,
			maxDuration: '360s',
			exec: 'allMerchantFlow',
		},
		allAPISmoke: {
			executor: 'per-vu-iterations',
			vus: 1,
			iterations: defaultIterations,
			maxDuration: '120s',
			exec: 'allAPIFlow',
		},
	},
	thresholds: {
		// All checks (assertions) must pass
		checks: [ 'rate==1' ],
		// Response time thresholds for all requests
		http_req_duration: [ 'p(90)<1000', 'p(95)<1500', 'p(99.9)<3000' ],
	},
};

export function setup() {
	addCustomerOrder();
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
	homeWCAdmin( {
		other: false,
		orders: false,
		reviews: false,
		products: false,
	} );
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
