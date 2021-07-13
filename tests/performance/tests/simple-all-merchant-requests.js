import { wpLogin } from '../requests/merchant/wp-login.js';
import { products } from '../requests/merchant/products.js';
import { addProduct } from '../requests/merchant/add-product.js';

export let options = {
    scenarios: {
        addProductSmoke: {
            executor: 'per-vu-iterations',
            vus: 1,
            iterations: 1,
            maxDuration: '360s',
            exec: 'addProductFlow',
        },
    },
};

export function addProductFlow() {
    wpLogin();
    products();
    addProduct();
}
