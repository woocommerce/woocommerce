import { HomePage } from '../requests/shopper/home.js';
import { ShopPage } from '../requests/shopper/shop-page.js';
import { SingleProduct } from '../requests/shopper/single-product.js';
import { CheckoutFlow } from '../requests/shopper/checkout.js';

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
	CheckoutFlow();
}
