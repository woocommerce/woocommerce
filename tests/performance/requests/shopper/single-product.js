import { sleep, check, group } from "k6";
import http from "k6/http";
import { Trend } from 'k6/metrics';
import { randomIntBetween } from "https://jslib.k6.io/k6-utils/1.1.0/index.js";
import {
  base_url,
  product_url,
  product_id,
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
let productPageTrend = new Trend('wc_get_product_page');

export function SingleProduct() {

  let response;

  group("Product Page", function () {
    var requestheaders = Object.assign(htmlRequestHeader, commonRequestHeaders, commonGetRequestHeaders, commonNonStandardHeaders)

    response = http.get(`${base_url}/product/${product_url}`, {
      headers: requestheaders,
    });
    productPageTrend.add(response.timings.duration);
    check(response, {
        'is status 200': (r) => r.status === 200,
        "body conatins product id": response =>
            response.body.includes(`id="product-${product_id}`),
    });
    
  });

  sleep(randomIntBetween(`${think_time_min}`, `${think_time_max}`));
}

export default function () {
  SingleProduct();
}
