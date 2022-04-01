import { homePage } from '../requests/shopper/home.js';
import { shopPage } from '../requests/shopper/shop-page.js';
import { searchProduct } from '../requests/shopper/search-product.js';
import { singleProduct } from '../requests/shopper/single-product.js';
import { cart } from '../requests/shopper/cart.js';
import { cartRemoveItem } from '../requests/shopper/cart-remove-item.js';
import { cartApplyCoupon } from '../requests/shopper/cart-apply-coupon.js';
import { checkoutGuest } from '../requests/shopper/checkout-guest.js';
import { checkoutCustomerLogin } from '../requests/shopper/checkout-customer-login.js';
import { myAccount } from '../requests/shopper/my-account.js';
import { wpLogin } from '../requests/merchant/wp-login.js';
import { products } from '../requests/merchant/products.js';
import { addProduct } from '../requests/merchant/add-product.js';
import { orders } from '../requests/merchant/orders.js';
import { ordersHeartbeat } from '../requests/merchant/orders-heartbeat.js';
import { homeWCAdmin } from '../requests/merchant/home-wc-admin.js';
import { myAccountMerchantLogin } from '../requests/merchant/my-account-merchant.js';

export let options = {
    scenarios: {
        merchantBackgroundActivity: {
            executor: 'ramping-vus',
			startVUs: 1,
			stages: [
			  { target: 1, duration: '5s' },
			  { target: 2, duration: '600s' },
			  { target: 2, duration: '120s' },
			  { target: 4, duration: '600s' },
			  { target: 4, duration: '120s' },
			  { target: 0, duration: '120s' },
			],
			gracefulRampDown: '0',
            exec: 'merchantAllFlows',
        },
        merchantIdleBackgroundActivity: {
            executor: 'ramping-vus',
			startVUs: 1,
			stages: [
			  { target: 1, duration: '5s' },
			  { target: 2, duration: '600s' },
			  { target: 2, duration: '120s' },
			  { target: 4, duration: '600s' },
			  { target: 4, duration: '120s' },
			  { target: 0, duration: '120s' },
			],
			gracefulRampDown: '0',
            exec: 'merchantHeartbeatFlow',
        },
        shopperBackgroundActivity: {
            executor: 'ramping-vus',
			startVUs: 1,
			stages: [
			  { target: 1, duration: '5s' },
			  { target: 2, duration: '600s' },
			  { target: 2, duration: '120s' },
			  { target: 4, duration: '600s' },
			  { target: 4, duration: '120s' },
			  { target: 0, duration: '120s' },
			],
			gracefulRampDown: '0',
            exec: 'shopperBrowsingFlows',
        },
        shopperGuestCheckouts: {
            executor: 'ramping-vus',
			startVUs: 1,
			stages: [
			  { target: 1, duration: '5s' },
			  { target: 4, duration: '600s' },
			  { target: 4, duration: '120s' },
			  { target: 8, duration: '600s' },
			  { target: 8, duration: '120s' },
			  { target: 0, duration: '120s' },
			],
			gracefulRampDown: '0',
            exec: 'checkoutGuestFlow',
        },
        shopperCustomerCheckouts: {
            executor: 'ramping-vus',
			startVUs: 1,
			stages: [
			  { target: 1, duration: '5s' },
			  { target: 2, duration: '600s' },
			  { target: 2, duration: '120s' },
			  { target: 4, duration: '600s' },
			  { target: 4, duration: '120s' },
			  { target: 0, duration: '120s' },
			],
			gracefulRampDown: '0',
            exec: 'checkoutCustomerLoginFlow',
        },
    },
};

// Use myAccountMerchantLogin() instead of wpLogin() if having issues with login.
export function merchantAllFlows() {
    myAccountMerchantLogin();
    homeWCAdmin();
    orders();
    products();
    addProduct();
}
// Use myAccountMerchantLogin() instead of wpLogin() if having issues with login.
export function merchantHeartbeatFlow() {
	// Login only on first iteration
	if (__ITER == 0) {
    	myAccountMerchantLogin();}
    ordersHeartbeat();
}
export function shopperBrowsingFlows() {
    homePage();
    shopPage();
    searchProduct();
    singleProduct();
    cartRemoveItem();
	cartApplyCoupon();
    myAccount();
}
export function checkoutGuestFlow() {
    cart();
    checkoutGuest();
}
export function checkoutCustomerLoginFlow() {
    cart();
    checkoutCustomerLogin();
}
