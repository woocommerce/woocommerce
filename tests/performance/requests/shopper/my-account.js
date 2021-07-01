import { sleep, check, group } from "k6";
import http from "k6/http";
import { Trend } from 'k6/metrics';
import { randomIntBetween } from "https://jslib.k6.io/k6-utils/1.1.0/index.js";
import {
    base_url,
    customer_username,
    customer_password,
    think_time_min,
    think_time_max
} from '../../config.js';
import {
    htmlRequestHeader,
    allRequestHeader,
    commonRequestHeaders,
    commonGetRequestHeaders,
    commonPostRequestHeaders,
    commonNonStandardHeaders
} from '../../headers.js';

/* add custom metrics for each step to the standard output */
let myAccountTrend = new Trend('wc_get_myaccount');
let myAccountLoginTrend = new Trend('wc_post_myaccount');
let myAccountLoginTrend2 = new Trend('wc_post_wc-ajax_get_refreshed_fragments');

export function MyAccount() {

    let response;
    let woocommerce_login_nonce;

    group("My Account", function () {
        var requestheaders = Object.assign(htmlRequestHeader, commonRequestHeaders, commonGetRequestHeaders, commonNonStandardHeaders)

        response = http.get(`${base_url}/my-account`,
            {
                headers: requestheaders,
            });
        myAccountTrend.add(response.timings.duration);
        check(response, {
            'is status 200': (r) => r.status === 200,
            "body conatins My account title": response =>
                response.body.includes('<h1 class="entry-title">My account</h1>'),
        });

        woocommerce_login_nonce = response.html().find("input[name=woocommerce-login-nonce]").first().attr("value");
    });

    sleep(randomIntBetween(`${think_time_min}`, `${think_time_max}`));


    group("My Account Login", function () {
        var requestheaders = Object.assign(htmlRequestHeader, commonRequestHeaders, commonGetRequestHeaders, commonNonStandardHeaders)
    
        response = http.post(`${base_url}/my-account`,
          {
            username: `${customer_username}`,
            password: `${customer_password}`,
            "woocommerce-login-nonce": `${woocommerce_login_nonce}`,
            _wp_http_referer: "/my-account",
            login: "Log%20in",
          },
          {
            headers: {
              headers: requestheaders,
            },
          }
        );
        myAccountLoginTrend.add(response.timings.duration);
        check(response, {
          'is status 200': (r) => r.status === 200,
          "body conatins My account welcome": response =>
              response.body.includes('From your account dashboard you can view'),
        });

        var requestheaders = Object.assign(allRequestHeader, commonRequestHeaders, commonPostRequestHeaders, commonNonStandardHeaders)

        response = http.post(
        `${base_url}/?wc-ajax=get_refreshed_fragments`,
        {
            headers: requestheaders,
        }
        );
        myAccountLoginTrend2.add(response.timings.duration);
        check(response, {
        'is status 200': (r) => r.status === 200,
        });
    });
}

export default function () {
    MyAccount();
}
