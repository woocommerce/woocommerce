import { sleep, check, group } from "k6";
import http from "k6/http";
import { Trend } from 'k6/metrics';
import { randomIntBetween } from "https://jslib.k6.io/k6-utils/1.1.0/index.js";
import {
  base_url,
  product_search_term,
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
let searchProductTrend = new Trend('wc_get_search_product');

export function SearchProduct() {

  let response;

  group("Search Product", function () {
    var requestheaders = Object.assign(htmlRequestHeader, commonRequestHeaders, commonGetRequestHeaders, commonNonStandardHeaders)

    response = http.get(`${base_url}/?s=${product_search_term}&post_type=product`, {
      headers: requestheaders,
    });
    searchProductTrend.add(response.timings.duration);
    check(response, {
        'is status 200': (r) => r.status === 200,
        "body conatins search results title": response =>
           response.body.includes("Search results:"), 
    });
  });

  sleep(randomIntBetween(`${think_time_min}`, `${think_time_max}`));
}

export default function () {
  SearchProduct();
}
