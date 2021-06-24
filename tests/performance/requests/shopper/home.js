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
let homePageTrend = new Trend('_step_01_home_page_duration');

export function HomePage() {

  let response;

  group("Home Page", function () {
    var requestheaders = Object.assign(htmlRequestHeader, commonRequestHeaders, commonGetRequestHeaders, commonNonStandardHeaders)

    response = http.get(`${base_url}/`,
      {
        headers: requestheaders,
      });
    homePageTrend.add(response.timings.duration);
    check(response, {
      'is status 200': (r) => r.status === 200,
    });
  });

  sleep(randomIntBetween(`${think_time_min}`, `${think_time_max}`));
}

export default function () {
  HomePage();
}
