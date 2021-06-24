import { sleep, check, group } from "k6";
import http from "k6/http";
import { Trend } from 'k6/metrics';
import { randomIntBetween } from "https://jslib.k6.io/k6-utils/1.1.0/index.js";
import {
    base_url,
    think_time_min,
    think_time_max
} from '../../config.js';
import {
    htmlRequestHeader,
    commonRequestHeaders,
    commonGetRequestHeaders,
    commonNonStandardHeaders
} from '../../headers.js';

/* add custom metrics for each step to the standard output */
let shopPageTrend = new Trend('_step_02_shop_page_duration');

export function ShopPage() {

    let response;

    group("Shop Page", function () {
        var requestheaders = Object.assign(htmlRequestHeader, commonRequestHeaders, commonGetRequestHeaders, commonNonStandardHeaders)

        response = http.get(`${base_url}/shop`,
            {
                headers: requestheaders,
            });
        shopPageTrend.add(response.timings.duration);
        check(response, {
            'is status 200': (r) => r.status === 200,
            "body conatins woocommerce-products-header": response =>
                response.body.includes('<header class="woocommerce-products-header">'),
        });
    });

    sleep(randomIntBetween(`${think_time_min}`, `${think_time_max}`));
}

export default function () {
    ShopPage();
}
