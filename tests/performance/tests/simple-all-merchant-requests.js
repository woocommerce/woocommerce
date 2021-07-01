import { WPLogin } from '../requests/merchant/wp-login.js';

export let options = {
    scenarios: {
        homePage: {
            executor: 'per-vu-iterations',
            vus: 1,
            iterations: 1,
            maxDuration: '60s',
            exec: 'wp_login',
        },
    },
};

export function wp_login() {
    WPLogin();
}
