import { WPLogin } from '../requests/merchant/wp-login.js';
import { Products } from '../requests/merchant/products.js';
import { AddProduct } from '../requests/merchant/add-product.js';

export let options = {
    scenarios: {
        homePage: {
            executor: 'per-vu-iterations',
            vus: 1,
            iterations: 1,
            maxDuration: '60s',
            exec: 'products',
        },
    },
};

export function products() {
    WPLogin();
    Products();
    AddProduct();
}
