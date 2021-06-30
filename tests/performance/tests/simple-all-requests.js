import { HomePage } from '../requests/shopper/home.js';
import { ShopPage } from '../requests/shopper/shop-page.js';
import { SingleProduct } from '../requests/shopper/single-product.js';
import { Cart } from '../requests/shopper/cart.js';
import { CheckoutGuest } from '../requests/shopper/checkout-guest.js';
import { CheckoutCustomerLogin } from '../requests/shopper/checkout-customer-login.js';

export let options = {
    scenarios: {
      homePage: {
        executor: 'per-vu-iterations',
        vus: 1,
        iterations: 1,
        maxDuration: '10s',
        exec: 'home_page',
      },
      shopPage: {
        executor: 'per-vu-iterations',
        vus: 1,
        iterations: 1,
        maxDuration: '10s',
        startTime: '4s',
        exec: 'shop_page',
      },
      singleProduct: {
        executor: 'per-vu-iterations',
        vus: 1,
        iterations: 1,
        maxDuration: '10s',
        startTime: '8s',
        exec: 'single_product',
      },
      checkoutFlow: {
        executor: 'per-vu-iterations',
        vus: 1,
        iterations: 1,
        maxDuration: '50s',
        startTime: '16s',
        exec: 'checkout',
      },
    },
  };
  
export function home_page() {
	HomePage();
}
export function shop_page() {
	ShopPage();
}
export function single_product() {
	SingleProduct();
}
export function checkout() {
    Cart();
	CheckoutGuest();
}
