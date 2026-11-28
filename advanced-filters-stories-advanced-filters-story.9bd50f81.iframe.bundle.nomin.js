"use strict";
(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[3388],{

/***/ "../../packages/js/components/src/advanced-filters/stories/advanced-filters.story.tsx"
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var ___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../packages/js/components/src/advanced-filters/index.tsx");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
/**
 * External dependencies
 */

/**
 * Internal dependencies
 */


const ORDER_STATUSES = {
  cancelled: 'Cancelled',
  completed: 'Completed',
  failed: 'Failed',
  'on-hold': 'On hold',
  pending: 'Pending payment',
  processing: 'Processing',
  refunded: 'Refunded'
};
const siteLocale = 'en_US';
const currency = {
  code: 'USD',
  decimalSeparator: '.',
  precision: 2,
  priceFormat: '%1$s%2$s',
  symbol: '$',
  symbolPosition: 'left',
  thousandSeparator: ','
};
const path = new URL(document.location.href).searchParams.get('path') ?? '';
const query = {
  component: 'advanced-filters'
};
const advancedFilters = {
  title: 'Orders Match <select/> Filters',
  filters: {
    status: {
      labels: {
        add: 'Order Status',
        remove: 'Remove order status filter',
        rule: 'Select an order status filter match',
        title: '<title>Order Status</title> <rule/> <filter/>',
        filter: 'Select an order status'
      },
      rules: [{
        value: 'is',
        label: 'Is'
      }, {
        value: 'is_not',
        label: 'Is Not'
      }],
      input: {
        component: 'SelectControl',
        options: Object.keys(ORDER_STATUSES).map(key => ({
          value: key,
          label: ORDER_STATUSES[key]
        }))
      }
    },
    product: {
      labels: {
        add: 'Products',
        placeholder: 'Search products',
        remove: 'Remove products filter',
        rule: 'Select a product filter match',
        title: '<title>Product</title> <rule/> <filter/>',
        filter: 'Select products'
      },
      rules: [{
        value: 'includes',
        label: 'Includes'
      }, {
        value: 'excludes',
        label: 'Excludes'
      }],
      input: {
        component: 'Search',
        type: 'products',
        getLabels: () => Promise.resolve([])
      }
    },
    customer: {
      labels: {
        add: 'Customer type',
        remove: 'Remove customer filter',
        rule: 'Select a customer filter match',
        title: '<title>Customer is</title> <filter/>',
        filter: 'Select a customer type'
      },
      input: {
        component: 'SelectControl',
        options: [{
          value: 'new',
          label: 'New'
        }, {
          value: 'returning',
          label: 'Returning'
        }],
        defaultOption: 'new'
      }
    },
    quantity: {
      labels: {
        add: 'Item Quantity',
        remove: 'Remove item quantity filter',
        rule: 'Select an item quantity filter match',
        title: '<title>Item Quantity is</title> <rule/> <filter/>'
      },
      rules: [{
        value: 'lessthan',
        label: 'Less Than'
      }, {
        value: 'morethan',
        label: 'More Than'
      }, {
        value: 'between',
        label: 'Between'
      }],
      input: {
        component: 'Number'
      }
    },
    subtotal: {
      labels: {
        add: 'Subtotal',
        remove: 'Remove subtotal filter',
        rule: 'Select a subtotal filter match',
        title: '<title>Subtotal is</title> <rule/> <filter/>'
      },
      rules: [{
        value: 'lessthan',
        label: 'Less Than'
      }, {
        value: 'morethan',
        label: 'More Than'
      }, {
        value: 'between',
        label: 'Between'
      }],
      input: {
        component: 'Number',
        type: 'currency'
      }
    },
    date: {
      labels: {
        add: 'Date',
        remove: 'Remove date filter',
        rule: 'Select a date filter match',
        title: '<title>Date</title> <rule/> <filter/>',
        filter: 'Select a transaction date'
      },
      rules: [{
        value: 'before',
        label: 'Before'
      }, {
        value: 'after',
        label: 'After'
      }, {
        value: 'between',
        label: 'Between'
      }],
      input: {
        component: 'Date'
      }
    }
  }
};
const Basic = () => /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(___WEBPACK_IMPORTED_MODULE_0__/* ["default"] */ .A, {
  siteLocale: siteLocale,
  path: path,
  query: query,
  config: advancedFilters,
  currency: currency
});
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  title: 'Components/AdvancedFilters',
  component: ___WEBPACK_IMPORTED_MODULE_0__/* ["default"] */ .A
});
Basic.parameters = {
  ...Basic.parameters,
  docs: {
    ...Basic.parameters?.docs,
    source: {
      originalSource: "() => <AdvancedFilters siteLocale={siteLocale} path={path} query={query} config={advancedFilters} currency={currency} />",
      ...Basic.parameters?.docs?.source
    }
  }
};
try {
    // @ts-ignore
    AdvancedFilters.displayName = "AdvancedFilters";
    // @ts-ignore
    AdvancedFilters.__docgenInfo = { "description": "Displays a configurable set of filters which can modify query parameters.", "displayName": "AdvancedFilters", "props": { "config": { "defaultValue": null, "description": "The configuration object required to render filters.", "name": "config", "required": true, "type": { "name": "AdvancedFilterConfig" } }, "path": { "defaultValue": null, "description": "Name of this filter, used in translations.", "name": "path", "required": true, "type": { "name": "string" } }, "currency": { "defaultValue": null, "description": "The currency formatting instance for the site.", "name": "currency", "required": true, "type": { "name": "Currency" } }, "query": { "defaultValue": { value: "{}" }, "description": "The query string represented in object form.", "name": "query", "required": false, "type": { "name": "Query" } }, "onAdvancedFilterAction": { "defaultValue": { value: "() => {}" }, "description": "Function to be called after an advanced filter action has been taken.", "name": "onAdvancedFilterAction", "required": false, "type": { "name": "(action: AdvancedFilterAction, data?: Record<string, unknown> | ActiveFilter) => void" } }, "siteLocale": { "defaultValue": { value: "en_US" }, "description": "The locale for the site.", "name": "siteLocale", "required": false, "type": { "name": "string" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/advanced-filters/stories/advanced-filters.story.tsx#AdvancedFilters"] = { docgenInfo: AdvancedFilters.__docgenInfo, name: "AdvancedFilters", path: "../../packages/js/components/src/advanced-filters/stories/advanced-filters.story.tsx#AdvancedFilters" };
}
catch (__react_docgen_typescript_loader_error) { }
/* harmony export */ __webpack_require__.d(__webpack_exports__, [
/* harmony export */   "Basic", 0, /* binding */ Basic,
/* harmony export */   "default", 0, __WEBPACK_DEFAULT_EXPORT__
/* harmony export */ ]);


/***/ },

/***/ "../../node_modules/.pnpm/@wordpress+deprecated@4.33.1/node_modules/@wordpress/deprecated/build-module/index.js"
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (/* binding */ deprecated)
/* harmony export */ });
/* unused harmony export logged */
/* harmony import */ var _wordpress_hooks__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+hooks@4.57.0/node_modules/@wordpress/hooks/build-module/index.mjs");

const logged = /* @__PURE__ */ Object.create(null);
function deprecated(feature, options = {}) {
  const { since, version, alternative, plugin, link, hint } = options;
  const pluginMessage = plugin ? ` from ${plugin}` : "";
  const sinceMessage = since ? ` since version ${since}` : "";
  const versionMessage = version ? ` and will be removed${pluginMessage} in version ${version}` : "";
  const useInsteadMessage = alternative ? ` Please use ${alternative} instead.` : "";
  const linkMessage = link ? ` See: ${link}` : "";
  const hintMessage = hint ? ` Note: ${hint}` : "";
  const message = `${feature} is deprecated${sinceMessage}${versionMessage}.${useInsteadMessage}${linkMessage}${hintMessage}`;
  if (message in logged) {
    return;
  }
  ;(0,_wordpress_hooks__WEBPACK_IMPORTED_MODULE_0__/* .doAction */ .Eo)("deprecated", feature, options, message);
  console.warn(message);
  logged[message] = true;
}

//# sourceMappingURL=index.js.map


/***/ },

/***/ "../../node_modules/.pnpm/date-fns-tz@3.2.0_date-fns@4.4.0/node_modules/date-fns-tz/dist/esm/getTimezoneOffset/index.js"
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  Z: () => (/* binding */ getTimezoneOffset)
});

;// ../../node_modules/.pnpm/date-fns-tz@3.2.0_date-fns@4.4.0/node_modules/date-fns-tz/dist/esm/_lib/tzTokenizeDate/index.js
/**
 * Returns the [year, month, day, hour, minute, seconds] tokens of the provided
 * `date` as it will be rendered in the `timeZone`.
 */
function tzTokenizeDate(date, timeZone) {
    const dtf = getDateTimeFormat(timeZone);
    return 'formatToParts' in dtf ? partsOffset(dtf, date) : hackyOffset(dtf, date);
}
const typeToPos = {
    year: 0,
    month: 1,
    day: 2,
    hour: 3,
    minute: 4,
    second: 5,
};
function partsOffset(dtf, date) {
    try {
        const formatted = dtf.formatToParts(date);
        const filled = [];
        for (let i = 0; i < formatted.length; i++) {
            const pos = typeToPos[formatted[i].type];
            if (pos !== undefined) {
                filled[pos] = parseInt(formatted[i].value, 10);
            }
        }
        return filled;
    }
    catch (error) {
        if (error instanceof RangeError) {
            return [NaN];
        }
        throw error;
    }
}
function hackyOffset(dtf, date) {
    const formatted = dtf.format(date);
    // eslint-disable-next-line @typescript-eslint/no-non-null-assertion
    const parsed = /(\d+)\/(\d+)\/(\d+),? (\d+):(\d+):(\d+)/.exec(formatted);
    // const [, fMonth, fDay, fYear, fHour, fMinute, fSecond] = parsed
    // return [fYear, fMonth, fDay, fHour, fMinute, fSecond]
    return [
        parseInt(parsed[3], 10),
        parseInt(parsed[1], 10),
        parseInt(parsed[2], 10),
        parseInt(parsed[4], 10),
        parseInt(parsed[5], 10),
        parseInt(parsed[6], 10),
    ];
}
// Get a cached Intl.DateTimeFormat instance for the IANA `timeZone`. This can be used
// to get deterministic local date/time output according to the `en-US` locale which
// can be used to extract local time parts as necessary.
const dtfCache = {};
// New browsers use `hourCycle`, IE and Chrome <73 does not support it and uses `hour12`
const testDateFormatted = new Intl.DateTimeFormat('en-US', {
    hourCycle: 'h23',
    timeZone: 'America/New_York',
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
}).format(new Date('2014-06-25T04:00:00.123Z'));
const hourCycleSupported = testDateFormatted === '06/25/2014, 00:00:00' ||
    testDateFormatted === '‎06‎/‎25‎/‎2014‎ ‎00‎:‎00‎:‎00';
function getDateTimeFormat(timeZone) {
    if (!dtfCache[timeZone]) {
        dtfCache[timeZone] = hourCycleSupported
            ? new Intl.DateTimeFormat('en-US', {
                hourCycle: 'h23',
                timeZone: timeZone,
                year: 'numeric',
                month: 'numeric',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
            })
            : new Intl.DateTimeFormat('en-US', {
                hour12: false,
                timeZone: timeZone,
                year: 'numeric',
                month: 'numeric',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
            });
    }
    return dtfCache[timeZone];
}

;// ../../node_modules/.pnpm/date-fns-tz@3.2.0_date-fns@4.4.0/node_modules/date-fns-tz/dist/esm/_lib/newDateUTC/index.js
/**
 * Use instead of `new Date(Date.UTC(...))` to support years below 100 which doesn't work
 * otherwise due to the nature of the
 * [`Date` constructor](https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Date#interpretation_of_two-digit_years.
 *
 * For `Date.UTC(...)`, use `newDateUTC(...).getTime()`.
 */
function newDateUTC(fullYear, month, day, hour, minute, second, millisecond) {
    const utcDate = new Date(0);
    utcDate.setUTCFullYear(fullYear, month, day);
    utcDate.setUTCHours(hour, minute, second, millisecond);
    return utcDate;
}

;// ../../node_modules/.pnpm/date-fns-tz@3.2.0_date-fns@4.4.0/node_modules/date-fns-tz/dist/esm/_lib/tzParseTimezone/index.js


const MILLISECONDS_IN_HOUR = 3600000;
const MILLISECONDS_IN_MINUTE = 60000;
const patterns = {
    timezone: /([Z+-].*)$/,
    timezoneZ: /^(Z)$/,
    timezoneHH: /^([+-]\d{2})$/,
    timezoneHHMM: /^([+-])(\d{2}):?(\d{2})$/,
};
// Parse constious time zone offset formats to an offset in milliseconds
function tzParseTimezone(timezoneString, date, isUtcDate) {
    // Empty string
    if (!timezoneString) {
        return 0;
    }
    // Z
    let token = patterns.timezoneZ.exec(timezoneString);
    if (token) {
        return 0;
    }
    let hours;
    let absoluteOffset;
    // ±hh
    token = patterns.timezoneHH.exec(timezoneString);
    if (token) {
        hours = parseInt(token[1], 10);
        if (!validateTimezone(hours)) {
            return NaN;
        }
        return -(hours * MILLISECONDS_IN_HOUR);
    }
    // ±hh:mm or ±hhmm
    token = patterns.timezoneHHMM.exec(timezoneString);
    if (token) {
        hours = parseInt(token[2], 10);
        const minutes = parseInt(token[3], 10);
        if (!validateTimezone(hours, minutes)) {
            return NaN;
        }
        absoluteOffset = Math.abs(hours) * MILLISECONDS_IN_HOUR + minutes * MILLISECONDS_IN_MINUTE;
        return token[1] === '+' ? -absoluteOffset : absoluteOffset;
    }
    // IANA time zone
    if (isValidTimezoneIANAString(timezoneString)) {
        date = new Date(date || Date.now());
        const utcDate = isUtcDate ? date : toUtcDate(date);
        const offset = calcOffset(utcDate, timezoneString);
        const fixedOffset = isUtcDate ? offset : fixOffset(date, offset, timezoneString);
        return -fixedOffset;
    }
    return NaN;
}
function toUtcDate(date) {
    return newDateUTC(date.getFullYear(), date.getMonth(), date.getDate(), date.getHours(), date.getMinutes(), date.getSeconds(), date.getMilliseconds());
}
function calcOffset(date, timezoneString) {
    const tokens = tzTokenizeDate(date, timezoneString);
    // ms dropped because it's not provided by tzTokenizeDate
    const asUTC = newDateUTC(tokens[0], tokens[1] - 1, tokens[2], tokens[3] % 24, tokens[4], tokens[5], 0).getTime();
    let asTS = date.getTime();
    const over = asTS % 1000;
    asTS -= over >= 0 ? over : 1000 + over;
    return asUTC - asTS;
}
function fixOffset(date, offset, timezoneString) {
    const localTS = date.getTime();
    // Our UTC time is just a guess because our offset is just a guess
    let utcGuess = localTS - offset;
    // Test whether the zone matches the offset for this ts
    const o2 = calcOffset(new Date(utcGuess), timezoneString);
    // If so, offset didn't change, and we're done
    if (offset === o2) {
        return offset;
    }
    // If not, change the ts by the difference in the offset
    utcGuess -= o2 - offset;
    // If that gives us the local time we want, we're done
    const o3 = calcOffset(new Date(utcGuess), timezoneString);
    if (o2 === o3) {
        return o2;
    }
    // If it's different, we're in a hole time. The offset has changed, but we don't adjust the time
    return Math.max(o2, o3);
}
function validateTimezone(hours, minutes) {
    return -23 <= hours && hours <= 23 && (minutes == null || (0 <= minutes && minutes <= 59));
}
const validIANATimezoneCache = {};
function isValidTimezoneIANAString(timeZoneString) {
    if (validIANATimezoneCache[timeZoneString])
        return true;
    try {
        new Intl.DateTimeFormat(undefined, { timeZone: timeZoneString });
        validIANATimezoneCache[timeZoneString] = true;
        return true;
    }
    catch (error) {
        return false;
    }
}

;// ../../node_modules/.pnpm/date-fns-tz@3.2.0_date-fns@4.4.0/node_modules/date-fns-tz/dist/esm/getTimezoneOffset/index.js

/**
 * @name getTimezoneOffset
 * @category Time Zone Helpers
 * @summary Gets the offset in milliseconds between the time zone and Universal Coordinated Time (UTC)
 *
 * @description
 * Returns the time zone offset from UTC time in milliseconds for IANA time zones as well
 * as other time zone offset string formats.
 *
 * For time zones where daylight savings time is applicable a `Date` should be passed on
 * the second parameter to ensure the offset correctly accounts for DST at that time of
 * year. When omitted, the current date is used.
 *
 * @param timeZone the time zone of this local time, can be an offset or IANA time zone
 * @param date the date with values representing the local time
 *
 * @example
 * const result = getTimezoneOffset('-07:00')
 *   //=> -18000000 (-7 * 60 * 60 * 1000)
 * const result = getTimezoneOffset('Africa/Johannesburg')
 *   //=> 7200000 (2 * 60 * 60 * 1000)
 * const result = getTimezoneOffset('America/New_York', new Date(2016, 0, 1))
 *   //=> -18000000 (-5 * 60 * 60 * 1000)
 * const result = getTimezoneOffset('America/New_York', new Date(2016, 6, 1))
 *   //=> -14400000 (-4 * 60 * 60 * 1000)
 */
function getTimezoneOffset(timeZone, date) {
    return -tzParseTimezone(timeZone, date);
}


/***/ }

}]);