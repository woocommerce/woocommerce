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
import { coupons } from '../requests/merchant/coupons.js';
import { myAccount } from '../requests/shopper/my-account.js';
import { categoryPage } from '../requests/shopper/category-page.js';
import { myAccountMerchantLogin } from '../requests/merchant/my-account-merchant.js';
import { products } from '../requests/merchant/products.js';
import { addProduct } from '../requests/merchant/add-product.js';
import { orders } from '../requests/merchant/orders.js';
import { ordersSearch } from '../requests/merchant/orders-search.js';
import { homeWCAdmin } from '../requests/merchant/home-wc-admin.js';

export const options = {
	scenarios: {
		homePageSmoke: {
			executor: 'per-vu-iterations',
			vus: 1,
			iterations: 3,
			maxDuration: '60s',
			exec: 'homePageFlow',
		},
		shopPageSmoke: {
			executor: 'per-vu-iterations',
			vus: 1,
			iterations: 3,
			maxDuration: '60s',
			startTime: '5s',
			exec: 'shopPageFlow',
		},
		searchProductSmoke: {
			executor: 'per-vu-iterations',
			vus: 1,
			iterations: 3,
			maxDuration: '60s',
			startTime: '10s',
			exec: 'searchProductFlow',
		},
		singleProductSmoke: {
			executor: 'per-vu-iterations',
			vus: 1,
			iterations: 3,
			maxDuration: '60s',
			startTime: '15s',
			exec: 'singleProductFlow',
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
	},
	thresholds: {
		checks: [ 'rate==1' ],
	},
};

export function homePageFlow() {
	homePage();
}
export function shopPageFlow() {
	shopPage();
}
export function searchProductFlow() {
	searchProduct();
}
export function singleProductFlow() {
	singleProduct();
	categoryPage();
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
}
export function cartFlow() {
	cartRemoveItem();
}
export function allMerchantFlow() {
	myAccountMerchantLogin();
	homeWCAdmin();
	orders();
	ordersSearch();
	products();
	addProduct();
	coupons();
}
