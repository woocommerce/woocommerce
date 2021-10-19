import { wpLogin } from '../requests/merchant/wp-login.js';
import { products } from '../requests/merchant/products.js';
import { addProduct } from '../requests/merchant/add-product.js';
import { orders } from '../requests/merchant/orders.js';
import { homeWCAdmin } from '../requests/merchant/home-wc-admin.js';
import { myAccountMerchantLogin } from '../requests/merchant/my-account-merchant.js';

export let options = {
    scenarios: {
        allMerchantSmoke: {
            executor: 'per-vu-iterations',
            vus: 1,
            iterations: 1,
            maxDuration: '360s',
            exec: 'allMerchantFlow',
        },
    },
};

// Use myAccountMerchantLogin() instead of wpLogin() if having issues with login.
export function allMerchantFlow() {
    wpLogin();
    homeWCAdmin();
    orders();
    products();
    addProduct();
}
