(window["webpackJsonp"] = window["webpackJsonp"] || []).push([[25],{

/***/ 709:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var _babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(11);
/* harmony import */ var _babel_runtime_helpers_esm_toConsumableArray__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(17);
/* harmony import */ var _babel_runtime_helpers_esm_objectWithoutProperties__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(16);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(2);
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(173);
/* harmony import */ var _base_control__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(171);





/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */


/**
 * Internal dependencies
 */



function SelectControl(_ref) {
  var help = _ref.help,
      instanceId = _ref.instanceId,
      label = _ref.label,
      _ref$multiple = _ref.multiple,
      multiple = _ref$multiple === void 0 ? false : _ref$multiple,
      onChange = _ref.onChange,
      _ref$options = _ref.options,
      options = _ref$options === void 0 ? [] : _ref$options,
      className = _ref.className,
      hideLabelFromVision = _ref.hideLabelFromVision,
      props = Object(_babel_runtime_helpers_esm_objectWithoutProperties__WEBPACK_IMPORTED_MODULE_2__[/* default */ "a"])(_ref, ["help", "instanceId", "label", "multiple", "onChange", "options", "className", "hideLabelFromVision"]);

  var id = "inspector-select-control-".concat(instanceId);

  var onChangeValue = function onChangeValue(event) {
    if (multiple) {
      var selectedOptions = Object(_babel_runtime_helpers_esm_toConsumableArray__WEBPACK_IMPORTED_MODULE_1__[/* default */ "a"])(event.target.options).filter(function (_ref2) {
        var selected = _ref2.selected;
        return selected;
      });

      var newValues = selectedOptions.map(function (_ref3) {
        var value = _ref3.value;
        return value;
      });
      onChange(newValues);
      return;
    }

    onChange(event.target.value);
  }; // Disable reason: A select with an onchange throws a warning

  /* eslint-disable jsx-a11y/no-onchange */


  return !Object(lodash__WEBPACK_IMPORTED_MODULE_4__["isEmpty"])(options) && Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_3__["createElement"])(_base_control__WEBPACK_IMPORTED_MODULE_6__[/* default */ "a"], {
    label: label,
    hideLabelFromVision: hideLabelFromVision,
    id: id,
    help: help,
    className: className
  }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_3__["createElement"])("select", Object(_babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_0__[/* default */ "a"])({
    id: id,
    className: "components-select-control__input",
    onChange: onChangeValue,
    "aria-describedby": !!help ? "".concat(id, "__help") : undefined,
    multiple: multiple
  }, props), options.map(function (option, index) {
    return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_3__["createElement"])("option", {
      key: "".concat(option.label, "-").concat(option.value, "-").concat(index),
      value: option.value,
      disabled: option.disabled
    }, option.label);
  })));
  /* eslint-enable jsx-a11y/no-onchange */
}

/* harmony default export */ __webpack_exports__["a"] = (Object(_wordpress_compose__WEBPACK_IMPORTED_MODULE_5__[/* default */ "a"])(SelectControl));
//# sourceMappingURL=index.js.map

/***/ }),

/***/ 738:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* unused harmony export getFilterQuery */
/* unused harmony export timeStampFilterDates */
/* unused harmony export getQueryFromConfig */
/* unused harmony export isReportDataEmpty */
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "d", function() { return getSummaryNumbers; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "a", function() { return getReportChartData; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "e", function() { return getTooltipValueFormat; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "c", function() { return getReportTableQuery; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "b", function() { return getReportTableData; });
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(15);
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _babel_runtime_helpers_slicedToArray__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(749);
/* harmony import */ var _babel_runtime_helpers_slicedToArray__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_slicedToArray__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(2);
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var moment__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(12);
/* harmony import */ var moment__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(moment__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var lib_date__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(104);
/* harmony import */ var _woocommerce_navigation__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(22);
/* harmony import */ var _woocommerce_navigation__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var wc_api_constants__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(24);
/* harmony import */ var _utils__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(738);



function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0___default()(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

/**
 * External dependencies
 */


/**
 * WooCommerce dependencies
 */



/**
 * Internal dependencies
 */



/**
 * Add filters and advanced filters values to a query object.
 *
 * @param  {Object} options                   arguments
 * @param  {string} options.endpoint          Report API Endpoint
 * @param  {Object} options.query             Query parameters in the url
 * @param  {Array}  options.limitBy           Properties used to limit the results. It will be used in the API call to send the IDs.
 * @param  {Array}  [options.filters]         config filters
 * @param  {Object} [options.advancedFilters] config advanced filters
 * @return {Object} A query object with the values from filters and advanced fitlters applied.
 */

function getFilterQuery(options) {
  var endpoint = options.endpoint,
      query = options.query,
      limitBy = options.limitBy,
      _options$filters = options.filters,
      filters = _options$filters === void 0 ? [] : _options$filters,
      _options$advancedFilt = options.advancedFilters,
      advancedFilters = _options$advancedFilt === void 0 ? {} : _options$advancedFilt;

  if (query.search) {
    var limitProperties = limitBy || [endpoint];
    return limitProperties.reduce(function (result, limitProperty) {
      result[limitProperty] = query[limitProperty];
      return result;
    }, {});
  }

  return filters.map(function (filter) {
    return getQueryFromConfig(filter, advancedFilters, query);
  }).reduce(function (result, configQuery) {
    return Object.assign(result, configQuery);
  }, {});
} // Some stats endpoints don't have interval data, so they can ignore after/before params and omit that part of the response.

var noIntervalEndpoints = ['stock', 'customers'];
/**
 * Add timestamp to advanced filter parameters involving date. The api
 * expects a timestamp for these values similar to `before` and `after`.
 *
 * @param {Object} config - advancedFilters config object.
 * @param {Object} activeFilter - an active filter.
 * @return {Object} - an active filter with timestamp added to date values.
 */

function timeStampFilterDates(config, activeFilter) {
  var advancedFilterConfig = config.filters[activeFilter.key];

  if (Object(lodash__WEBPACK_IMPORTED_MODULE_2__["get"])(advancedFilterConfig, ['input', 'component']) !== 'Date') {
    return activeFilter;
  }

  var rule = activeFilter.rule,
      value = activeFilter.value;
  var timeOfDayMap = {
    after: 'start',
    before: 'end'
  }; // If the value is an array, it signifies "between" values which must have a timestamp
  // appended to each value.

  if (Array.isArray(value)) {
    var _value = _babel_runtime_helpers_slicedToArray__WEBPACK_IMPORTED_MODULE_1___default()(value, 2),
        after = _value[0],
        before = _value[1];

    return Object.assign({}, activeFilter, {
      value: [Object(lib_date__WEBPACK_IMPORTED_MODULE_4__[/* appendTimestamp */ "a"])(moment__WEBPACK_IMPORTED_MODULE_3___default()(after), timeOfDayMap.after), Object(lib_date__WEBPACK_IMPORTED_MODULE_4__[/* appendTimestamp */ "a"])(moment__WEBPACK_IMPORTED_MODULE_3___default()(before), timeOfDayMap.before)]
    });
  }

  return Object.assign({}, activeFilter, {
    value: Object(lib_date__WEBPACK_IMPORTED_MODULE_4__[/* appendTimestamp */ "a"])(moment__WEBPACK_IMPORTED_MODULE_3___default()(value), timeOfDayMap[rule])
  });
}
function getQueryFromConfig(config, advancedFilters, query) {
  var queryValue = query[config.param];

  if (!queryValue) {
    return {};
  }

  if (queryValue === 'advanced') {
    var activeFilters = Object(_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_5__["getActiveFiltersFromQuery"])(query, advancedFilters.filters);

    if (activeFilters.length === 0) {
      return {};
    }

    return activeFilters.map(function (filter) {
      return timeStampFilterDates(advancedFilters, filter);
    }).reduce(function (result, activeFilter) {
      var key = activeFilter.key,
          rule = activeFilter.rule,
          value = activeFilter.value;
      result[Object(_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_5__["getUrlKey"])(key, rule)] = value;
      return result;
    }, {
      match: query.match || 'all'
    });
  }

  var filter = Object(lodash__WEBPACK_IMPORTED_MODULE_2__["find"])(Object(_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_5__["flattenFilters"])(config.filters), {
    value: queryValue
  });

  if (!filter) {
    return {};
  }

  if (filter.settings && filter.settings.param) {
    var param = filter.settings.param;

    if (query[param]) {
      return _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0___default()({}, param, query[param]);
    }

    return {};
  }

  return _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0___default()({}, config.param, queryValue);
}
/**
 * Returns true if a report object is empty.
 *
 * @param  {Object}  report   Report to check
 * @param  {string}  endpoint Endpoint slug
 * @return {boolean}        True if report is data is empty.
 */

function isReportDataEmpty(report, endpoint) {
  if (!report) {
    return true;
  }

  if (!report.data) {
    return true;
  }

  if (!report.data.totals || Object(lodash__WEBPACK_IMPORTED_MODULE_2__["isNull"])(report.data.totals)) {
    return true;
  }

  var checkIntervals = !Object(lodash__WEBPACK_IMPORTED_MODULE_2__["includes"])(noIntervalEndpoints, endpoint);

  if (checkIntervals && (!report.data.intervals || report.data.intervals.length === 0)) {
    return true;
  }

  return false;
}
/**
 * Constructs and returns a query associated with a Report data request.
 *
 * @param  {Object} options           arguments
 * @param  {string} options.endpoint  Report API Endpoint
 * @param  {string} options.dataType  'primary' or 'secondary'.
 * @param  {Object} options.query     Query parameters in the url.
 * @param  {Array}  options.limitBy   Properties used to limit the results. It will be used in the API call to send the IDs.
 * @param  {string}  options.defaultDateRange   User specified default date range.
 * @return {Object} data request query parameters.
 */

function getRequestQuery(options) {
  var endpoint = options.endpoint,
      dataType = options.dataType,
      query = options.query,
      fields = options.fields;
  var datesFromQuery = Object(lib_date__WEBPACK_IMPORTED_MODULE_4__[/* getCurrentDates */ "f"])(query, options.defaultDateRange);
  var interval = Object(lib_date__WEBPACK_IMPORTED_MODULE_4__[/* getIntervalForQuery */ "i"])(query);
  var filterQuery = getFilterQuery(options);
  var end = datesFromQuery[dataType].before;
  var noIntervals = Object(lodash__WEBPACK_IMPORTED_MODULE_2__["includes"])(noIntervalEndpoints, endpoint);
  return noIntervals ? _objectSpread({}, filterQuery, {
    fields: fields
  }) : _objectSpread({
    order: 'asc',
    interval: interval,
    per_page: wc_api_constants__WEBPACK_IMPORTED_MODULE_6__[/* MAX_PER_PAGE */ "b"],
    after: Object(lib_date__WEBPACK_IMPORTED_MODULE_4__[/* appendTimestamp */ "a"])(datesFromQuery[dataType].after, 'start'),
    before: Object(lib_date__WEBPACK_IMPORTED_MODULE_4__[/* appendTimestamp */ "a"])(end, 'end'),
    segmentby: query.segmentby,
    fields: fields
  }, filterQuery);
}
/**
 * Returns summary number totals needed to render a report page.
 *
 * @param  {Object} options           arguments
 * @param  {string} options.endpoint  Report API Endpoint
 * @param  {Object} options.query     Query parameters in the url
 * @param  {Object} options.select    Instance of @wordpress/select
 * @param  {Array}  options.limitBy   Properties used to limit the results. It will be used in the API call to send the IDs.
 * @param  {string}  options.defaultDateRange   User specified default date range.
 * @return {Object} Object containing summary number responses.
 */


function getSummaryNumbers(options) {
  var endpoint = options.endpoint,
      select = options.select;

  var _select = select('wc-api'),
      getReportStats = _select.getReportStats,
      getReportStatsError = _select.getReportStatsError,
      isReportStatsRequesting = _select.isReportStatsRequesting;

  var response = {
    isRequesting: false,
    isError: false,
    totals: {
      primary: null,
      secondary: null
    }
  };
  var primaryQuery = getRequestQuery(_objectSpread({}, options, {
    dataType: 'primary'
  })); // Disable eslint rule requiring `getReportStats` to be defined below because the next two statements
  // depend on `getReportStats` to have been called.
  // eslint-disable-next-line @wordpress/no-unused-vars-before-return

  var primary = getReportStats(endpoint, primaryQuery);

  if (isReportStatsRequesting(endpoint, primaryQuery)) {
    return _objectSpread({}, response, {
      isRequesting: true
    });
  } else if (getReportStatsError(endpoint, primaryQuery)) {
    return _objectSpread({}, response, {
      isError: true
    });
  }

  var primaryTotals = primary && primary.data && primary.data.totals || null;
  var secondaryQuery = getRequestQuery(_objectSpread({}, options, {
    dataType: 'secondary'
  })); // Disable eslint rule requiring `getReportStats` to be defined below because the next two statements
  // depend on `getReportStats` to have been called.
  // eslint-disable-next-line @wordpress/no-unused-vars-before-return

  var secondary = getReportStats(endpoint, secondaryQuery);

  if (isReportStatsRequesting(endpoint, secondaryQuery)) {
    return _objectSpread({}, response, {
      isRequesting: true
    });
  } else if (getReportStatsError(endpoint, secondaryQuery)) {
    return _objectSpread({}, response, {
      isError: true
    });
  }

  var secondaryTotals = secondary && secondary.data && secondary.data.totals || null;
  return _objectSpread({}, response, {
    totals: {
      primary: primaryTotals,
      secondary: secondaryTotals
    }
  });
}
/**
 * Returns all of the data needed to render a chart with summary numbers on a report page.
 *
 * @param  {Object} options           arguments
 * @param  {string} options.endpoint  Report API Endpoint
 * @param  {string} options.dataType  'primary' or 'secondary'
 * @param  {Object} options.query     Query parameters in the url
 * @param  {Object} options.select    Instance of @wordpress/select
 * @param  {Array}  options.limitBy   Properties used to limit the results. It will be used in the API call to send the IDs.
 * @param  {string}  options.defaultDateRange   User specified default date range.
 * @return {Object}  Object containing API request information (response, fetching, and error details)
 */

function getReportChartData(options) {
  var endpoint = options.endpoint,
      select = options.select;

  var _select2 = select('wc-api'),
      getReportStats = _select2.getReportStats,
      getReportStatsError = _select2.getReportStatsError,
      isReportStatsRequesting = _select2.isReportStatsRequesting;

  var response = {
    isEmpty: false,
    isError: false,
    isRequesting: false,
    data: {
      totals: {},
      intervals: []
    }
  };
  var requestQuery = getRequestQuery(options); // Disable eslint rule requiring `stats` to be defined below because the next two if statements
  // depend on `getReportStats` to have been called.
  // eslint-disable-next-line @wordpress/no-unused-vars-before-return

  var stats = getReportStats(endpoint, requestQuery);

  if (isReportStatsRequesting(endpoint, requestQuery)) {
    return _objectSpread({}, response, {
      isRequesting: true
    });
  }

  if (getReportStatsError(endpoint, requestQuery)) {
    return _objectSpread({}, response, {
      isError: true
    });
  }

  if (isReportDataEmpty(stats, endpoint)) {
    return _objectSpread({}, response, {
      isEmpty: true
    });
  }

  var totals = stats && stats.data && stats.data.totals || null;
  var intervals = stats && stats.data && stats.data.intervals || []; // If we have more than 100 results for this time period,
  // we need to make additional requests to complete the response.

  if (stats.totalResults > wc_api_constants__WEBPACK_IMPORTED_MODULE_6__[/* MAX_PER_PAGE */ "b"]) {
    var isFetching = true;
    var isError = false;
    var pagedData = [];
    var totalPages = Math.ceil(stats.totalResults / wc_api_constants__WEBPACK_IMPORTED_MODULE_6__[/* MAX_PER_PAGE */ "b"]);
    var pagesFetched = 1;

    for (var i = 2; i <= totalPages; i++) {
      var nextQuery = _objectSpread({}, requestQuery, {
        page: i
      });

      var _data = getReportStats(endpoint, nextQuery);

      if (isReportStatsRequesting(endpoint, nextQuery)) {
        continue;
      }

      if (getReportStatsError(endpoint, nextQuery)) {
        isError = true;
        isFetching = false;
        break;
      }

      pagedData.push(_data);
      pagesFetched++;

      if (pagesFetched === totalPages) {
        isFetching = false;
        break;
      }
    }

    if (isFetching) {
      return _objectSpread({}, response, {
        isRequesting: true
      });
    } else if (isError) {
      return _objectSpread({}, response, {
        isError: true
      });
    }

    Object(lodash__WEBPACK_IMPORTED_MODULE_2__["forEach"])(pagedData, function (_data) {
      intervals = intervals.concat(_data.data.intervals);
    });
  }

  return _objectSpread({}, response, {
    data: {
      totals: totals,
      intervals: intervals
    }
  });
}
/**
 * Returns a formatting function or string to be used by d3-format
 *
 * @param  {string} type Type of number, 'currency', 'number', 'percent', 'average'
 * @param  {Function} formatCurrency format currency function
 * @return {string|Function}  returns a number format based on the type or an overriding formatting function
 */

function getTooltipValueFormat(type, formatCurrency) {
  switch (type) {
    case 'currency':
      return formatCurrency;

    case 'percent':
      return '.0%';

    case 'number':
      return ',';

    case 'average':
      return ',.2r';

    default:
      return ',';
  }
}
/**
 * Returns query needed for a request to populate a table.
 *
 * @param  {Object} options              arguments
 * @param  {Object} options.query        Query parameters in the url
 * @param  {Object} options.tableQuery   Query parameters specific for that endpoint
 * @param  {string} options.defaultDateRange   User specified default date range.
 * @return {Object} Object    Table data response
 */

function getReportTableQuery(options) {
  var query = options.query,
      _options$tableQuery = options.tableQuery,
      tableQuery = _options$tableQuery === void 0 ? {} : _options$tableQuery;
  var filterQuery = getFilterQuery(options);
  var datesFromQuery = Object(lib_date__WEBPACK_IMPORTED_MODULE_4__[/* getCurrentDates */ "f"])(query, options.defaultDateRange);
  var noIntervals = Object(lodash__WEBPACK_IMPORTED_MODULE_2__["includes"])(noIntervalEndpoints, options.endpoint);
  return _objectSpread({
    orderby: query.orderby || 'date',
    order: query.order || 'desc',
    after: noIntervals ? undefined : Object(lib_date__WEBPACK_IMPORTED_MODULE_4__[/* appendTimestamp */ "a"])(datesFromQuery.primary.after, 'start'),
    before: noIntervals ? undefined : Object(lib_date__WEBPACK_IMPORTED_MODULE_4__[/* appendTimestamp */ "a"])(datesFromQuery.primary.before, 'end'),
    page: query.paged || 1,
    per_page: query.per_page || wc_api_constants__WEBPACK_IMPORTED_MODULE_6__[/* QUERY_DEFAULTS */ "d"].pageSize
  }, filterQuery, {}, tableQuery);
}
/**
 * Returns table data needed to render a report page.
 *
 * @param  {Object} options                arguments
 * @param  {string} options.endpoint       Report API Endpoint
 * @param  {Object} options.query          Query parameters in the url
 * @param  {Object} options.select         Instance of @wordpress/select
 * @param  {Object} options.tableQuery     Query parameters specific for that endpoint
 * @param  {string}  options.defaultDateRange   User specified default date range.
 * @return {Object} Object    Table data response
 */

function getReportTableData(options) {
  var endpoint = options.endpoint,
      select = options.select;

  var _select3 = select('wc-api'),
      getReportItems = _select3.getReportItems,
      getReportItemsError = _select3.getReportItemsError,
      isReportItemsRequesting = _select3.isReportItemsRequesting;

  var tableQuery = _utils__WEBPACK_IMPORTED_MODULE_7__[/* getReportTableQuery */ "c"](options);
  var response = {
    query: tableQuery,
    isRequesting: false,
    isError: false,
    items: {
      data: [],
      totalResults: 0
    }
  }; // Disable eslint rule requiring `items` to be defined below because the next two if statements
  // depend on `getReportItems` to have been called.
  // eslint-disable-next-line @wordpress/no-unused-vars-before-return

  var items = getReportItems(endpoint, tableQuery);

  if (isReportItemsRequesting(endpoint, tableQuery)) {
    return _objectSpread({}, response, {
      isRequesting: true
    });
  } else if (getReportItemsError(endpoint, tableQuery)) {
    return _objectSpread({}, response, {
      isError: true
    });
  }

  return _objectSpread({}, response, {
    items: items
  });
}

/***/ }),

/***/ 739:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "e", function() { return getRequestByIdString; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "a", function() { return getCategoryLabels; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "b", function() { return getCouponLabels; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "c", function() { return getCustomerLabels; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "d", function() { return getProductLabels; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "f", function() { return getTaxRateLabels; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "g", function() { return getVariationLabels; });
/* harmony import */ var _wordpress_url__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(30);
/* harmony import */ var _wordpress_url__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_url__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(20);
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(2);
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _woocommerce_navigation__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(22);
/* harmony import */ var _woocommerce_navigation__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var analytics_report_taxes_utils__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(740);
/* harmony import */ var wc_api_constants__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(24);
/**
 * External dependencies
 */



/**
 * WooCommerce dependencies
 */


/**
 * Internal dependencies
 */



/**
 * Get a function that accepts ids as they are found in url parameter and
 * returns a promise with an optional method applied to results
 *
 * @param {string|Function} path - api path string or a function of the query returning api path string
 * @param {Function} [handleData] - function applied to each iteration of data
 * @return {Function} - a function of ids returning a promise
 */

function getRequestByIdString(path) {
  var handleData = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : lodash__WEBPACK_IMPORTED_MODULE_2__["identity"];
  return function () {
    var queryString = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : '';
    var query = arguments.length > 1 ? arguments[1] : undefined;
    var pathString = typeof path === 'function' ? path(query) : path;
    var idList = Object(_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_3__["getIdsFromQuery"])(queryString);

    if (idList.length < 1) {
      return Promise.resolve([]);
    }

    var payload = {
      include: idList.join(','),
      per_page: idList.length
    };
    return _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_1___default()({
      path: Object(_wordpress_url__WEBPACK_IMPORTED_MODULE_0__["addQueryArgs"])(pathString, payload)
    }).then(function (data) {
      return data.map(handleData);
    });
  };
}
var getCategoryLabels = getRequestByIdString(wc_api_constants__WEBPACK_IMPORTED_MODULE_5__[/* NAMESPACE */ "c"] + '/products/categories', function (category) {
  return {
    key: category.id,
    label: category.name
  };
});
var getCouponLabels = getRequestByIdString(wc_api_constants__WEBPACK_IMPORTED_MODULE_5__[/* NAMESPACE */ "c"] + '/coupons', function (coupon) {
  return {
    key: coupon.id,
    label: coupon.code
  };
});
var getCustomerLabels = getRequestByIdString(wc_api_constants__WEBPACK_IMPORTED_MODULE_5__[/* NAMESPACE */ "c"] + '/customers', function (customer) {
  return {
    key: customer.id,
    label: customer.name
  };
});
var getProductLabels = getRequestByIdString(wc_api_constants__WEBPACK_IMPORTED_MODULE_5__[/* NAMESPACE */ "c"] + '/products', function (product) {
  return {
    key: product.id,
    label: product.name
  };
});
var getTaxRateLabels = getRequestByIdString(wc_api_constants__WEBPACK_IMPORTED_MODULE_5__[/* NAMESPACE */ "c"] + '/taxes', function (taxRate) {
  return {
    key: taxRate.id,
    label: Object(analytics_report_taxes_utils__WEBPACK_IMPORTED_MODULE_4__[/* getTaxCode */ "a"])(taxRate)
  };
});
var getVariationLabels = getRequestByIdString(function (query) {
  return wc_api_constants__WEBPACK_IMPORTED_MODULE_5__[/* NAMESPACE */ "c"] + "/products/".concat(query.products, "/variations");
}, function (variation) {
  return {
    key: variation.id,
    label: variation.attributes.reduce(function (desc, attribute, index, arr) {
      return desc + "".concat(attribute.option).concat(arr.length === index + 1 ? '' : ', ');
    }, '')
  };
});

/***/ }),

/***/ 740:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "a", function() { return getTaxCode; });
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(3);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__);
/**
 * External dependencies
 */

function getTaxCode(tax) {
  return [tax.country, tax.state, tax.name || Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('TAX', 'woocommerce'), tax.priority].map(function (item) {
    return item.toString().toUpperCase().trim();
  }).filter(Boolean).join('-');
}

/***/ }),

/***/ 741:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";

// UNUSED EXPORTS: ReportChart

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/defineProperty.js
var defineProperty = __webpack_require__(15);
var defineProperty_default = /*#__PURE__*/__webpack_require__.n(defineProperty);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/classCallCheck.js
var classCallCheck = __webpack_require__(41);
var classCallCheck_default = /*#__PURE__*/__webpack_require__.n(classCallCheck);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/createClass.js
var createClass = __webpack_require__(40);
var createClass_default = /*#__PURE__*/__webpack_require__.n(createClass);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/possibleConstructorReturn.js
var possibleConstructorReturn = __webpack_require__(44);
var possibleConstructorReturn_default = /*#__PURE__*/__webpack_require__.n(possibleConstructorReturn);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/getPrototypeOf.js
var getPrototypeOf = __webpack_require__(29);
var getPrototypeOf_default = /*#__PURE__*/__webpack_require__.n(getPrototypeOf);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/inherits.js
var inherits = __webpack_require__(42);
var inherits_default = /*#__PURE__*/__webpack_require__.n(inherits);

// EXTERNAL MODULE: external {"this":["wp","element"]}
var external_this_wp_element_ = __webpack_require__(0);

// EXTERNAL MODULE: external {"this":["wp","i18n"]}
var external_this_wp_i18n_ = __webpack_require__(3);

// EXTERNAL MODULE: ./node_modules/@wordpress/compose/build-module/higher-order/compose.js
var compose = __webpack_require__(256);

// EXTERNAL MODULE: ./node_modules/@wordpress/date/build-module/index.js
var build_module = __webpack_require__(172);

// EXTERNAL MODULE: external "lodash"
var external_lodash_ = __webpack_require__(2);

// EXTERNAL MODULE: ./node_modules/prop-types/index.js
var prop_types = __webpack_require__(1);
var prop_types_default = /*#__PURE__*/__webpack_require__.n(prop_types);

// EXTERNAL MODULE: ./client/lib/date.js
var date = __webpack_require__(104);

// EXTERNAL MODULE: external {"this":["wc","components"]}
var external_this_wc_components_ = __webpack_require__(63);

// EXTERNAL MODULE: external {"this":["wc","data"]}
var external_this_wc_data_ = __webpack_require__(51);

// EXTERNAL MODULE: ./client/lib/currency-context.js
var currency_context = __webpack_require__(203);

// EXTERNAL MODULE: ./client/wc-api/reports/utils.js
var utils = __webpack_require__(738);

// EXTERNAL MODULE: ./client/analytics/components/report-error/index.js
var report_error = __webpack_require__(261);

// EXTERNAL MODULE: ./client/wc-api/with-select.js
var with_select = __webpack_require__(101);

// EXTERNAL MODULE: external {"this":["wc","navigation"]}
var external_this_wc_navigation_ = __webpack_require__(22);

// CONCATENATED MODULE: ./client/analytics/components/report-chart/utils.js
/**
 * External dependencies
 */

/**
 * WooCommerce dependencies
 */


var DEFAULT_FILTER = 'all';
function getSelectedFilter(filters, query) {
  var selectedFilterArgs = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : {};

  if (!filters || filters.length === 0) {
    return null;
  }

  var clonedFilters = filters.slice(0);
  var filterConfig = clonedFilters.pop();

  if (filterConfig.showFilters(query, selectedFilterArgs)) {
    var allFilters = Object(external_this_wc_navigation_["flattenFilters"])(filterConfig.filters);
    var value = query[filterConfig.param] || filterConfig.defaultValue || DEFAULT_FILTER;
    return Object(external_lodash_["find"])(allFilters, {
      value: value
    });
  }

  return getSelectedFilter(clonedFilters, query, selectedFilterArgs);
}
function getChartMode(selectedFilter, query) {
  if (selectedFilter && query) {
    var selectedFilterParam = Object(external_lodash_["get"])(selectedFilter, ['settings', 'param']);

    if (!selectedFilterParam || Object.keys(query).includes(selectedFilterParam)) {
      return Object(external_lodash_["get"])(selectedFilter, ['chartMode']);
    }
  }

  return null;
}
// CONCATENATED MODULE: ./client/analytics/components/report-chart/index.js








function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { defineProperty_default()(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function () { var Super = getPrototypeOf_default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = getPrototypeOf_default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return possibleConstructorReturn_default()(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/**
 * External dependencies
 */






/**
 * WooCommerce dependencies
 */





/**
 * Internal dependencies
 */





/**
 * Component that renders the chart in reports.
 */

var report_chart_ReportChart = /*#__PURE__*/function (_Component) {
  inherits_default()(ReportChart, _Component);

  var _super = _createSuper(ReportChart);

  function ReportChart() {
    classCallCheck_default()(this, ReportChart);

    return _super.apply(this, arguments);
  }

  createClass_default()(ReportChart, [{
    key: "shouldComponentUpdate",
    value: function shouldComponentUpdate(nextProps) {
      if (nextProps.isRequesting !== this.props.isRequesting || nextProps.primaryData.isRequesting !== this.props.primaryData.isRequesting || nextProps.secondaryData.isRequesting !== this.props.secondaryData.isRequesting || !Object(external_lodash_["isEqual"])(nextProps.query, this.props.query)) {
        return true;
      }

      return false;
    }
  }, {
    key: "getItemChartData",
    value: function getItemChartData() {
      var _this$props = this.props,
          primaryData = _this$props.primaryData,
          selectedChart = _this$props.selectedChart;
      var chartData = primaryData.data.intervals.map(function (interval) {
        var intervalData = {};
        interval.subtotals.segments.forEach(function (segment) {
          if (segment.segment_label) {
            var label = intervalData[segment.segment_label] ? segment.segment_label + ' (#' + segment.segment_id + ')' : segment.segment_label;
            intervalData[segment.segment_id] = {
              label: label,
              value: segment.subtotals[selectedChart.key] || 0
            };
          }
        });
        return _objectSpread({
          date: Object(build_module["a" /* format */])('Y-m-d\\TH:i:s', interval.date_start)
        }, intervalData);
      });
      return chartData;
    }
  }, {
    key: "getTimeChartData",
    value: function getTimeChartData() {
      var _this$props2 = this.props,
          query = _this$props2.query,
          primaryData = _this$props2.primaryData,
          secondaryData = _this$props2.secondaryData,
          selectedChart = _this$props2.selectedChart,
          defaultDateRange = _this$props2.defaultDateRange;
      var currentInterval = Object(date["i" /* getIntervalForQuery */])(query);

      var _getCurrentDates = Object(date["f" /* getCurrentDates */])(query, defaultDateRange),
          primary = _getCurrentDates.primary,
          secondary = _getCurrentDates.secondary;

      var chartData = primaryData.data.intervals.map(function (interval, index) {
        var secondaryDate = Object(date["j" /* getPreviousDate */])(interval.date_start, primary.after, secondary.after, query.compare, currentInterval);
        var secondaryInterval = secondaryData.data.intervals[index];
        return {
          date: Object(build_module["a" /* format */])('Y-m-d\\TH:i:s', interval.date_start),
          primary: {
            label: "".concat(primary.label, " (").concat(primary.range, ")"),
            labelDate: interval.date_start,
            value: interval.subtotals[selectedChart.key] || 0
          },
          secondary: {
            label: "".concat(secondary.label, " (").concat(secondary.range, ")"),
            labelDate: secondaryDate.format('YYYY-MM-DD HH:mm:ss'),
            value: secondaryInterval && secondaryInterval.subtotals[selectedChart.key] || 0
          }
        };
      });
      return chartData;
    }
  }, {
    key: "getTimeChartTotals",
    value: function getTimeChartTotals() {
      var _this$props3 = this.props,
          primaryData = _this$props3.primaryData,
          secondaryData = _this$props3.secondaryData,
          selectedChart = _this$props3.selectedChart;
      return {
        primary: Object(external_lodash_["get"])(primaryData, ['data', 'totals', selectedChart.key], null),
        secondary: Object(external_lodash_["get"])(secondaryData, ['data', 'totals', selectedChart.key], null)
      };
    }
  }, {
    key: "renderChart",
    value: function renderChart(mode, isRequesting, chartData, legendTotals) {
      var _this$props4 = this.props,
          emptySearchResults = _this$props4.emptySearchResults,
          filterParam = _this$props4.filterParam,
          interactiveLegend = _this$props4.interactiveLegend,
          itemsLabel = _this$props4.itemsLabel,
          legendPosition = _this$props4.legendPosition,
          path = _this$props4.path,
          query = _this$props4.query,
          selectedChart = _this$props4.selectedChart,
          showHeaderControls = _this$props4.showHeaderControls,
          primaryData = _this$props4.primaryData;
      var currentInterval = Object(date["i" /* getIntervalForQuery */])(query);
      var allowedIntervals = Object(date["d" /* getAllowedIntervalsForQuery */])(query);
      var formats = Object(date["g" /* getDateFormatsForInterval */])(currentInterval, primaryData.data.intervals.length);
      var emptyMessage = emptySearchResults ? Object(external_this_wp_i18n_["__"])('No data for the current search', 'woocommerce') : Object(external_this_wp_i18n_["__"])('No data for the selected date range', 'woocommerce');
      var _this$context = this.context,
          formatCurrency = _this$context.formatCurrency,
          getCurrency = _this$context.getCurrency;
      return Object(external_this_wp_element_["createElement"])(external_this_wc_components_["Chart"], {
        allowedIntervals: allowedIntervals,
        data: chartData,
        dateParser: '%Y-%m-%dT%H:%M:%S',
        emptyMessage: emptyMessage,
        filterParam: filterParam,
        interactiveLegend: interactiveLegend,
        interval: currentInterval,
        isRequesting: isRequesting,
        itemsLabel: itemsLabel,
        legendPosition: legendPosition,
        legendTotals: legendTotals,
        mode: mode,
        path: path,
        query: query,
        screenReaderFormat: formats.screenReaderFormat,
        showHeaderControls: showHeaderControls,
        title: selectedChart.label,
        tooltipLabelFormat: formats.tooltipLabelFormat,
        tooltipTitle: mode === 'time-comparison' && selectedChart.label || null,
        tooltipValueFormat: Object(utils["e" /* getTooltipValueFormat */])(selectedChart.type, formatCurrency),
        chartType: Object(date["e" /* getChartTypeForQuery */])(query),
        valueType: selectedChart.type,
        xFormat: formats.xFormat,
        x2Format: formats.x2Format,
        currency: getCurrency()
      });
    }
  }, {
    key: "renderItemComparison",
    value: function renderItemComparison() {
      var _this$props5 = this.props,
          isRequesting = _this$props5.isRequesting,
          primaryData = _this$props5.primaryData;

      if (primaryData.isError) {
        return Object(external_this_wp_element_["createElement"])(report_error["a" /* default */], {
          isError: true
        });
      }

      var isChartRequesting = isRequesting || primaryData.isRequesting;
      var chartData = this.getItemChartData();
      return this.renderChart('item-comparison', isChartRequesting, chartData);
    }
  }, {
    key: "renderTimeComparison",
    value: function renderTimeComparison() {
      var _this$props6 = this.props,
          isRequesting = _this$props6.isRequesting,
          primaryData = _this$props6.primaryData,
          secondaryData = _this$props6.secondaryData;

      if (!primaryData || primaryData.isError || secondaryData.isError) {
        return Object(external_this_wp_element_["createElement"])(report_error["a" /* default */], {
          isError: true
        });
      }

      var isChartRequesting = isRequesting || primaryData.isRequesting || secondaryData.isRequesting;
      var chartData = this.getTimeChartData();
      var legendTotals = this.getTimeChartTotals();
      return this.renderChart('time-comparison', isChartRequesting, chartData, legendTotals);
    }
  }, {
    key: "render",
    value: function render() {
      var mode = this.props.mode;

      if (mode === 'item-comparison') {
        return this.renderItemComparison();
      }

      return this.renderTimeComparison();
    }
  }]);

  return ReportChart;
}(external_this_wp_element_["Component"]);
report_chart_ReportChart.contextType = currency_context["a" /* CurrencyContext */];
report_chart_ReportChart.propTypes = {
  /**
   * Filters available for that report.
   */
  filters: prop_types_default.a.array,

  /**
   * Whether there is an API call running.
   */
  isRequesting: prop_types_default.a.bool,

  /**
   * Label describing the legend items.
   */
  itemsLabel: prop_types_default.a.string,

  /**
   * Allows specifying properties different from the `endpoint` that will be used
   * to limit the items when there is an active search.
   */
  limitProperties: prop_types_default.a.array,

  /**
   * `items-comparison` (default) or `time-comparison`, this is used to generate correct
   * ARIA properties.
   */
  mode: prop_types_default.a.string,

  /**
   * Current path
   */
  path: prop_types_default.a.string.isRequired,

  /**
   * Primary data to display in the chart.
   */
  primaryData: prop_types_default.a.object,

  /**
   * The query string represented in object form.
   */
  query: prop_types_default.a.object.isRequired,

  /**
   * Secondary data to display in the chart.
   */
  secondaryData: prop_types_default.a.object,

  /**
   * Properties of the selected chart.
   */
  selectedChart: prop_types_default.a.shape({
    /**
     * Key of the selected chart.
     */
    key: prop_types_default.a.string.isRequired,

    /**
     * Chart label.
     */
    label: prop_types_default.a.string.isRequired,

    /**
     * Order query argument.
     */
    order: prop_types_default.a.oneOf(['asc', 'desc']),

    /**
     * Order by query argument.
     */
    orderby: prop_types_default.a.string,

    /**
     * Number type for formatting.
     */
    type: prop_types_default.a.oneOf(['average', 'number', 'currency']).isRequired
  }).isRequired
};
report_chart_ReportChart.defaultProps = {
  isRequesting: false,
  primaryData: {
    data: {
      intervals: []
    },
    isError: false,
    isRequesting: false
  },
  secondaryData: {
    data: {
      intervals: []
    },
    isError: false,
    isRequesting: false
  }
};
/* harmony default export */ var report_chart = __webpack_exports__["a"] = (Object(compose["a" /* default */])(Object(with_select["a" /* default */])(function (select, props) {
  var charts = props.charts,
      endpoint = props.endpoint,
      filters = props.filters,
      isRequesting = props.isRequesting,
      limitProperties = props.limitProperties,
      query = props.query,
      advancedFilters = props.advancedFilters;
  var limitBy = limitProperties || [endpoint];
  var selectedFilter = getSelectedFilter(filters, query);
  var filterParam = Object(external_lodash_["get"])(selectedFilter, ['settings', 'param']);
  var chartMode = props.mode || getChartMode(selectedFilter, query) || 'time-comparison';

  var _select$getSetting = select(external_this_wc_data_["SETTINGS_STORE_NAME"]).getSetting('wc_admin', 'wcAdminSettings'),
      defaultDateRange = _select$getSetting.woocommerce_default_date_range;

  var newProps = {
    mode: chartMode,
    filterParam: filterParam,
    defaultDateRange: defaultDateRange
  };

  if (isRequesting) {
    return newProps;
  }

  var hasLimitByParam = limitBy.some(function (item) {
    return query[item] && query[item].length;
  });

  if (query.search && !hasLimitByParam) {
    return _objectSpread({}, newProps, {
      emptySearchResults: true
    });
  }

  var fields = charts && charts.map(function (chart) {
    return chart.key;
  });
  var primaryData = Object(utils["a" /* getReportChartData */])({
    endpoint: endpoint,
    dataType: 'primary',
    query: query,
    select: select,
    limitBy: limitBy,
    filters: filters,
    advancedFilters: advancedFilters,
    defaultDateRange: defaultDateRange,
    fields: fields
  });

  if (chartMode === 'item-comparison') {
    return _objectSpread({}, newProps, {
      primaryData: primaryData
    });
  }

  var secondaryData = Object(utils["a" /* getReportChartData */])({
    endpoint: endpoint,
    dataType: 'secondary',
    query: query,
    select: select,
    limitBy: limitBy,
    filters: filters,
    advancedFilters: advancedFilters,
    defaultDateRange: defaultDateRange,
    fields: fields
  });
  return _objectSpread({}, newProps, {
    primaryData: primaryData,
    secondaryData: secondaryData
  });
}))(report_chart_ReportChart));

/***/ }),

/***/ 763:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "b", function() { return charts; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "c", function() { return filters; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "a", function() { return advancedFilters; });
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(3);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_hooks__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(48);
/* harmony import */ var _wordpress_hooks__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_hooks__WEBPACK_IMPORTED_MODULE_1__);
/**
 * External dependencies
 */


var REVENUE_REPORT_CHARTS_FILTER = 'woocommerce_admin_revenue_report_charts';
var REVENUE_REPORT_FILTERS_FILTER = 'woocommerce_admin_revenue_report_filters';
var REVENUE_REPORT_ADVANCED_FILTERS_FILTER = 'woocommerce_admin_revenue_report_advanced_filters';
var charts = Object(_wordpress_hooks__WEBPACK_IMPORTED_MODULE_1__["applyFilters"])(REVENUE_REPORT_CHARTS_FILTER, [{
  key: 'gross_sales',
  label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Gross Sales', 'woocommerce'),
  order: 'desc',
  orderby: 'gross_sales',
  type: 'currency'
}, {
  key: 'refunds',
  label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Returns', 'woocommerce'),
  order: 'desc',
  orderby: 'refunds',
  type: 'currency'
}, {
  key: 'coupons',
  label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Coupons', 'woocommerce'),
  order: 'desc',
  orderby: 'coupons',
  type: 'currency'
}, {
  key: 'net_revenue',
  label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Net Sales', 'woocommerce'),
  orderby: 'net_revenue',
  type: 'currency'
}, {
  key: 'taxes',
  label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Taxes', 'woocommerce'),
  order: 'desc',
  orderby: 'taxes',
  type: 'currency'
}, {
  key: 'shipping',
  label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Shipping', 'woocommerce'),
  orderby: 'shipping',
  type: 'currency'
}, {
  key: 'total_sales',
  label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Total Sales', 'woocommerce'),
  order: 'desc',
  orderby: 'total_sales',
  type: 'currency'
}]);
var filters = Object(_wordpress_hooks__WEBPACK_IMPORTED_MODULE_1__["applyFilters"])(REVENUE_REPORT_FILTERS_FILTER, []);
var advancedFilters = Object(_wordpress_hooks__WEBPACK_IMPORTED_MODULE_1__["applyFilters"])(REVENUE_REPORT_ADVANCED_FILTERS_FILTER, {});

/***/ }),

/***/ 764:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "b", function() { return charts; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "c", function() { return filters; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "a", function() { return advancedFilters; });
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(3);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_hooks__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(48);
/* harmony import */ var _wordpress_hooks__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_hooks__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var lib_async_requests__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(739);
/**
 * External dependencies
 */


/**
 * Internal dependencies
 */


var PRODUCTS_REPORT_CHARTS_FILTER = 'woocommerce_admin_products_report_charts';
var PRODUCTS_REPORT_FILTERS_FILTER = 'woocommerce_admin_products_report_filters';
var PRODUCTS_REPORT_ADVANCED_FILTERS_FILTER = 'woocommerce_admin_products_report_advanced_filters';
var charts = Object(_wordpress_hooks__WEBPACK_IMPORTED_MODULE_1__["applyFilters"])(PRODUCTS_REPORT_CHARTS_FILTER, [{
  key: 'items_sold',
  label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Items Sold', 'woocommerce'),
  order: 'desc',
  orderby: 'items_sold',
  type: 'number'
}, {
  key: 'net_revenue',
  label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Net Sales', 'woocommerce'),
  order: 'desc',
  orderby: 'net_revenue',
  type: 'currency'
}, {
  key: 'orders_count',
  label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Orders', 'woocommerce'),
  order: 'desc',
  orderby: 'orders_count',
  type: 'number'
}]);
var filterConfig = {
  label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Show', 'woocommerce'),
  staticParams: [],
  param: 'filter',
  showFilters: function showFilters() {
    return true;
  },
  filters: [{
    label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('All Products', 'woocommerce'),
    value: 'all'
  }, {
    label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Single Product', 'woocommerce'),
    value: 'select_product',
    chartMode: 'item-comparison',
    subFilters: [{
      component: 'Search',
      value: 'single_product',
      chartMode: 'item-comparison',
      path: ['select_product'],
      settings: {
        type: 'products',
        param: 'products',
        getLabels: lib_async_requests__WEBPACK_IMPORTED_MODULE_2__[/* getProductLabels */ "d"],
        labels: {
          placeholder: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Type to search for a product', 'woocommerce'),
          button: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Single Product', 'woocommerce')
        }
      }
    }]
  }, {
    label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Comparison', 'woocommerce'),
    value: 'compare-products',
    chartMode: 'item-comparison',
    settings: {
      type: 'products',
      param: 'products',
      getLabels: lib_async_requests__WEBPACK_IMPORTED_MODULE_2__[/* getProductLabels */ "d"],
      labels: {
        helpText: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Check at least two products below to compare', 'woocommerce'),
        placeholder: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Search for products to compare', 'woocommerce'),
        title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Compare Products', 'woocommerce'),
        update: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Compare', 'woocommerce')
      }
    }
  }]
};
var variationsConfig = {
  showFilters: function showFilters(query) {
    return query.filter === 'single_product' && !!query.products && query['is-variable'];
  },
  staticParams: ['filter', 'products'],
  param: 'filter-variations',
  filters: [{
    label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('All Variations', 'woocommerce'),
    chartMode: 'item-comparison',
    value: 'all'
  }, {
    label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Comparison', 'woocommerce'),
    chartMode: 'item-comparison',
    value: 'compare-variations',
    settings: {
      type: 'variations',
      param: 'variations',
      getLabels: lib_async_requests__WEBPACK_IMPORTED_MODULE_2__[/* getVariationLabels */ "g"],
      labels: {
        helpText: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Check at least two variations below to compare', 'woocommerce'),
        placeholder: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Search for variations to compare', 'woocommerce'),
        title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Compare Variations', 'woocommerce'),
        update: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Compare', 'woocommerce')
      }
    }
  }]
};
var filters = Object(_wordpress_hooks__WEBPACK_IMPORTED_MODULE_1__["applyFilters"])(PRODUCTS_REPORT_FILTERS_FILTER, [filterConfig, variationsConfig]);
var advancedFilters = Object(_wordpress_hooks__WEBPACK_IMPORTED_MODULE_1__["applyFilters"])(PRODUCTS_REPORT_ADVANCED_FILTERS_FILTER, {});

/***/ }),

/***/ 765:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "b", function() { return charts; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "c", function() { return filters; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "a", function() { return advancedFilters; });
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(3);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_hooks__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(48);
/* harmony import */ var _wordpress_hooks__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_hooks__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(26);
/* harmony import */ var lib_async_requests__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(739);
/**
 * External dependencies
 */



/**
 * Internal dependencies
 */


var ORDERS_REPORT_CHARTS_FILTER = 'woocommerce_admin_orders_report_charts';
var ORDERS_REPORT_FILTERS_FILTER = 'woocommerce_admin_orders_report_filters';
var ORDERS_REPORT_ADVANCED_FILTERS_FILTER = 'woocommerce_admin_orders_report_advanced_filters';
var charts = Object(_wordpress_hooks__WEBPACK_IMPORTED_MODULE_1__["applyFilters"])(ORDERS_REPORT_CHARTS_FILTER, [{
  key: 'orders_count',
  label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Orders', 'woocommerce'),
  type: 'number'
}, {
  key: 'net_revenue',
  label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Net Sales', 'woocommerce'),
  order: 'desc',
  orderby: 'net_total',
  type: 'currency'
}, {
  key: 'avg_order_value',
  label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Average Order Value', 'woocommerce'),
  type: 'currency'
}, {
  key: 'avg_items_per_order',
  label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Average Items Per Order', 'woocommerce'),
  order: 'desc',
  orderby: 'num_items_sold',
  type: 'average'
}]);
var filters = Object(_wordpress_hooks__WEBPACK_IMPORTED_MODULE_1__["applyFilters"])(ORDERS_REPORT_FILTERS_FILTER, [{
  label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Show', 'woocommerce'),
  staticParams: ['chart'],
  param: 'filter',
  showFilters: function showFilters() {
    return true;
  },
  filters: [{
    label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('All Orders', 'woocommerce'),
    value: 'all'
  }, {
    label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Advanced Filters', 'woocommerce'),
    value: 'advanced'
  }]
}]);
/*eslint-disable max-len*/

var advancedFilters = Object(_wordpress_hooks__WEBPACK_IMPORTED_MODULE_1__["applyFilters"])(ORDERS_REPORT_ADVANCED_FILTERS_FILTER, {
  title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["_x"])('Orders Match {{select /}} Filters', 'A sentence describing filters for Orders. See screen shot for context: https://cloudup.com/cSsUY9VeCVJ', 'woocommerce'),
  filters: {
    status: {
      labels: {
        add: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Order Status', 'woocommerce'),
        remove: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Remove order status filter', 'woocommerce'),
        rule: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Select an order status filter match', 'woocommerce'),

        /* translators: A sentence describing an Order Status filter. See screen shot for context: https://cloudup.com/cSsUY9VeCVJ */
        title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('{{title}}Order Status{{/title}} {{rule /}} {{filter /}}', 'woocommerce'),
        filter: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Select an order status', 'woocommerce')
      },
      rules: [{
        value: 'is',

        /* translators: Sentence fragment, logical, "Is" refers to searching for orders matching a chosen order status. Screenshot for context: https://cloudup.com/cSsUY9VeCVJ */
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["_x"])('Is', 'order status', 'woocommerce')
      }, {
        value: 'is_not',

        /* translators: Sentence fragment, logical, "Is Not" refers to searching for orders that don\'t match a chosen order status. Screenshot for context: https://cloudup.com/cSsUY9VeCVJ */
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["_x"])('Is Not', 'order status', 'woocommerce')
      }],
      input: {
        component: 'SelectControl',
        options: Object.keys(_woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_2__[/* ORDER_STATUSES */ "d"]).map(function (key) {
          return {
            value: key,
            label: _woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_2__[/* ORDER_STATUSES */ "d"][key]
          };
        })
      }
    },
    product: {
      labels: {
        add: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Products', 'woocommerce'),
        placeholder: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Search products', 'woocommerce'),
        remove: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Remove products filter', 'woocommerce'),
        rule: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Select a product filter match', 'woocommerce'),

        /* translators: A sentence describing a Product filter. See screen shot for context: https://cloudup.com/cSsUY9VeCVJ */
        title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('{{title}}Product{{/title}} {{rule /}} {{filter /}}', 'woocommerce'),
        filter: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Select products', 'woocommerce')
      },
      rules: [{
        value: 'includes',

        /* translators: Sentence fragment, logical, "Includes" refers to orders including a given product(s). Screenshot for context: https://cloudup.com/cSsUY9VeCVJ */
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["_x"])('Includes', 'products', 'woocommerce')
      }, {
        value: 'excludes',

        /* translators: Sentence fragment, logical, "Excludes" refers to orders excluding a given product(s). Screenshot for context: https://cloudup.com/cSsUY9VeCVJ */
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["_x"])('Excludes', 'products', 'woocommerce')
      }],
      input: {
        component: 'Search',
        type: 'products',
        getLabels: lib_async_requests__WEBPACK_IMPORTED_MODULE_3__[/* getProductLabels */ "d"]
      }
    },
    coupon: {
      labels: {
        add: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Coupon Codes', 'woocommerce'),
        placeholder: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Search coupons', 'woocommerce'),
        remove: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Remove coupon filter', 'woocommerce'),
        rule: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Select a coupon filter match', 'woocommerce'),

        /* translators: A sentence describing a Coupon filter. See screen shot for context: https://cloudup.com/cSsUY9VeCVJ */
        title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('{{title}}Coupon Code{{/title}} {{rule /}} {{filter /}}', 'woocommerce'),
        filter: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Select coupon codes', 'woocommerce')
      },
      rules: [{
        value: 'includes',

        /* translators: Sentence fragment, logical, "Includes" refers to orders including a given coupon code(s). Screenshot for context: https://cloudup.com/cSsUY9VeCVJ */
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["_x"])('Includes', 'coupon code', 'woocommerce')
      }, {
        value: 'excludes',

        /* translators: Sentence fragment, logical, "Excludes" refers to orders excluding a given coupon code(s). Screenshot for context: https://cloudup.com/cSsUY9VeCVJ */
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["_x"])('Excludes', 'coupon code', 'woocommerce')
      }],
      input: {
        component: 'Search',
        type: 'coupons',
        getLabels: lib_async_requests__WEBPACK_IMPORTED_MODULE_3__[/* getCouponLabels */ "b"]
      }
    },
    customer_type: {
      labels: {
        add: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Customer Type', 'woocommerce'),
        remove: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Remove customer filter', 'woocommerce'),
        rule: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Select a customer filter match', 'woocommerce'),

        /* translators: A sentence describing a Customer filter. See screen shot for context: https://cloudup.com/cSsUY9VeCVJ */
        title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('{{title}}Customer is{{/title}} {{filter /}}', 'woocommerce'),
        filter: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Select a customer type', 'woocommerce')
      },
      input: {
        component: 'SelectControl',
        options: [{
          value: 'new',
          label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('New', 'woocommerce')
        }, {
          value: 'returning',
          label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Returning', 'woocommerce')
        }],
        defaultOption: 'new'
      }
    },
    refunds: {
      labels: {
        add: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Refunds', 'woocommerce'),
        remove: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Remove refunds filter', 'woocommerce'),
        rule: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Select a refund filter match', 'woocommerce'),
        title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('{{title}}Refunds{{/title}} {{filter /}}', 'woocommerce'),
        filter: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Select a refund type', 'woocommerce')
      },
      input: {
        component: 'SelectControl',
        options: [{
          value: 'all',
          label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('All', 'woocommerce')
        }, {
          value: 'partial',
          label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Partially refunded', 'woocommerce')
        }, {
          value: 'full',
          label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Fully refunded', 'woocommerce')
        }, {
          value: 'none',
          label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('None', 'woocommerce')
        }],
        defaultOption: 'all'
      }
    },
    tax_rate: {
      labels: {
        add: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Tax Rates', 'woocommerce'),
        placeholder: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Search tax rates', 'woocommerce'),
        remove: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Remove tax rate filter', 'woocommerce'),
        rule: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Select a tax rate filter match', 'woocommerce'),

        /* translators: A sentence describing a tax rate filter. See screen shot for context: https://cloudup.com/cSsUY9VeCVJ */
        title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('{{title}}Tax Rate{{/title}} {{rule /}} {{filter /}}', 'woocommerce'),
        filter: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Select tax rates', 'woocommerce')
      },
      rules: [{
        value: 'includes',

        /* translators: Sentence fragment, logical, "Includes" refers to orders including a given tax rate(s). Screenshot for context: https://cloudup.com/cSsUY9VeCVJ */
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["_x"])('Includes', 'tax rate', 'woocommerce')
      }, {
        value: 'excludes',

        /* translators: Sentence fragment, logical, "Excludes" refers to orders excluding a given tax rate(s). Screenshot for context: https://cloudup.com/cSsUY9VeCVJ */
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["_x"])('Excludes', 'tax rate', 'woocommerce')
      }],
      input: {
        component: 'Search',
        type: 'taxes',
        getLabels: lib_async_requests__WEBPACK_IMPORTED_MODULE_3__[/* getTaxRateLabels */ "f"]
      }
    }
  }
});
/*eslint-enable max-len*/

/***/ }),

/***/ 766:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "b", function() { return charts; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "c", function() { return filters; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "a", function() { return advancedFilters; });
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(3);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_hooks__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(48);
/* harmony import */ var _wordpress_hooks__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_hooks__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var lib_async_requests__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(739);
/**
 * External dependencies
 */


/**
 * Internal dependencies
 */


var COUPON_REPORT_CHARTS_FILTER = 'woocommerce_admin_coupons_report_charts';
var COUPON_REPORT_FILTERS_FILTER = 'woocommerce_admin_coupons_report_filters';
var COUPON_REPORT_ADVANCED_FILTERS_FILTER = 'woocommerce_admin_coupon_report_advanced_filters';
var charts = Object(_wordpress_hooks__WEBPACK_IMPORTED_MODULE_1__["applyFilters"])(COUPON_REPORT_CHARTS_FILTER, [{
  key: 'orders_count',
  label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Discounted Orders', 'woocommerce'),
  order: 'desc',
  orderby: 'orders_count',
  type: 'number'
}, {
  key: 'amount',
  label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Amount', 'woocommerce'),
  order: 'desc',
  orderby: 'amount',
  type: 'currency'
}]);
var filters = Object(_wordpress_hooks__WEBPACK_IMPORTED_MODULE_1__["applyFilters"])(COUPON_REPORT_FILTERS_FILTER, [{
  label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Show', 'woocommerce'),
  staticParams: [],
  param: 'filter',
  showFilters: function showFilters() {
    return true;
  },
  filters: [{
    label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('All Coupons', 'woocommerce'),
    value: 'all'
  }, {
    label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Single Coupon', 'woocommerce'),
    value: 'select_coupon',
    chartMode: 'item-comparison',
    subFilters: [{
      component: 'Search',
      value: 'single_coupon',
      chartMode: 'item-comparison',
      path: ['select_coupon'],
      settings: {
        type: 'coupons',
        param: 'coupons',
        getLabels: lib_async_requests__WEBPACK_IMPORTED_MODULE_2__[/* getCouponLabels */ "b"],
        labels: {
          placeholder: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Type to search for a coupon', 'woocommerce'),
          button: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Single Coupon', 'woocommerce')
        }
      }
    }]
  }, {
    label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Comparison', 'woocommerce'),
    value: 'compare-coupons',
    settings: {
      type: 'coupons',
      param: 'coupons',
      getLabels: lib_async_requests__WEBPACK_IMPORTED_MODULE_2__[/* getCouponLabels */ "b"],
      labels: {
        title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Compare Coupon Codes', 'woocommerce'),
        update: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Compare', 'woocommerce'),
        helpText: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Check at least two coupon codes below to compare', 'woocommerce')
      }
    }
  }]
}]);
var advancedFilters = Object(_wordpress_hooks__WEBPACK_IMPORTED_MODULE_1__["applyFilters"])(COUPON_REPORT_ADVANCED_FILTERS_FILTER, {});

/***/ }),

/***/ 767:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "b", function() { return charts; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "c", function() { return filters; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "a", function() { return advancedFilters; });
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(3);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_hooks__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(48);
/* harmony import */ var _wordpress_hooks__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_hooks__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var lib_async_requests__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(739);
/* harmony import */ var _utils__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(740);
/* harmony import */ var wc_api_constants__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(24);
/**
 * External dependencies
 */


/**
 * Internal dependencies
 */




var TAXES_REPORT_CHARTS_FILTER = 'woocommerce_admin_taxes_report_charts';
var TAXES_REPORT_FILTERS_FILTER = 'woocommerce_admin_taxes_report_filters';
var TAXES_REPORT_ADVANCED_FILTERS_FILTER = 'woocommerce_admin_taxes_report_advanced_filters';
var charts = Object(_wordpress_hooks__WEBPACK_IMPORTED_MODULE_1__["applyFilters"])(TAXES_REPORT_CHARTS_FILTER, [{
  key: 'total_tax',
  label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Total Tax', 'woocommerce'),
  order: 'desc',
  orderby: 'total_tax',
  type: 'currency'
}, {
  key: 'order_tax',
  label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Order Tax', 'woocommerce'),
  order: 'desc',
  orderby: 'order_tax',
  type: 'currency'
}, {
  key: 'shipping_tax',
  label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Shipping Tax', 'woocommerce'),
  order: 'desc',
  orderby: 'shipping_tax',
  type: 'currency'
}, {
  key: 'orders_count',
  label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Orders', 'woocommerce'),
  order: 'desc',
  orderby: 'orders_count',
  type: 'number'
}]);
var filters = Object(_wordpress_hooks__WEBPACK_IMPORTED_MODULE_1__["applyFilters"])(TAXES_REPORT_FILTERS_FILTER, [{
  label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Show', 'woocommerce'),
  staticParams: ['chart'],
  param: 'filter',
  showFilters: function showFilters() {
    return true;
  },
  filters: [{
    label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('All Taxes', 'woocommerce'),
    value: 'all'
  }, {
    label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Comparison', 'woocommerce'),
    value: 'compare-taxes',
    chartMode: 'item-comparison',
    settings: {
      type: 'taxes',
      param: 'taxes',
      getLabels: Object(lib_async_requests__WEBPACK_IMPORTED_MODULE_2__[/* getRequestByIdString */ "e"])(wc_api_constants__WEBPACK_IMPORTED_MODULE_4__[/* NAMESPACE */ "c"] + '/taxes', function (tax) {
        return {
          id: tax.id,
          label: Object(_utils__WEBPACK_IMPORTED_MODULE_3__[/* getTaxCode */ "a"])(tax)
        };
      }),
      labels: {
        helpText: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Check at least two tax codes below to compare', 'woocommerce'),
        placeholder: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Search for tax codes to compare', 'woocommerce'),
        title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Compare Tax Codes', 'woocommerce'),
        update: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Compare', 'woocommerce')
      }
    }
  }]
}]);
var advancedFilters = Object(_wordpress_hooks__WEBPACK_IMPORTED_MODULE_1__["applyFilters"])(TAXES_REPORT_ADVANCED_FILTERS_FILTER, {});

/***/ }),

/***/ 768:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "b", function() { return charts; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "c", function() { return filters; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "a", function() { return advancedFilters; });
/* harmony import */ var _babel_runtime_helpers_asyncToGenerator__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(46);
/* harmony import */ var _babel_runtime_helpers_asyncToGenerator__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_asyncToGenerator__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(3);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_hooks__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(48);
/* harmony import */ var _wordpress_hooks__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_hooks__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var lib_async_requests__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(739);


/**
 * External dependencies
 */


/**
 * Internal dependencies
 */


var DOWLOADS_REPORT_CHARTS_FILTER = 'woocommerce_admin_downloads_report_charts';
var DOWLOADS_REPORT_FILTERS_FILTER = 'woocommerce_admin_downloads_report_filters';
var DOWLOADS_REPORT_ADVANCED_FILTERS_FILTER = 'woocommerce_admin_downloads_report_advanced_filters';
var charts = Object(_wordpress_hooks__WEBPACK_IMPORTED_MODULE_2__["applyFilters"])(DOWLOADS_REPORT_CHARTS_FILTER, [{
  key: 'download_count',
  label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Downloads', 'woocommerce'),
  type: 'number'
}]);
var filters = Object(_wordpress_hooks__WEBPACK_IMPORTED_MODULE_2__["applyFilters"])(DOWLOADS_REPORT_FILTERS_FILTER, [{
  label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Show', 'woocommerce'),
  staticParams: [],
  param: 'filter',
  showFilters: function showFilters() {
    return true;
  },
  filters: [{
    label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('All Downloads', 'woocommerce'),
    value: 'all'
  }, {
    label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Advanced Filters', 'woocommerce'),
    value: 'advanced'
  }]
}]);
/*eslint-disable max-len*/

var advancedFilters = Object(_wordpress_hooks__WEBPACK_IMPORTED_MODULE_2__["applyFilters"])(DOWLOADS_REPORT_ADVANCED_FILTERS_FILTER, {
  title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["_x"])('Downloads Match {{select /}} Filters', 'A sentence describing filters for Downloads. See screen shot for context: https://cloudup.com/ccxhyH2mEDg', 'woocommerce'),
  filters: {
    product: {
      labels: {
        add: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Product', 'woocommerce'),
        placeholder: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Search', 'woocommerce'),
        remove: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Remove product filter', 'woocommerce'),
        rule: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Select a product filter match', 'woocommerce'),

        /* translators: A sentence describing a Product filter. See screen shot for context: https://cloudup.com/ccxhyH2mEDg */
        title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('{{title}}Product{{/title}} {{rule /}} {{filter /}}', 'woocommerce'),
        filter: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Select product', 'woocommerce')
      },
      rules: [{
        value: 'includes',

        /* translators: Sentence fragment, logical, "Includes" refers to products including a given product(s). Screenshot for context: https://cloudup.com/ccxhyH2mEDg */
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["_x"])('Includes', 'products', 'woocommerce')
      }, {
        value: 'excludes',

        /* translators: Sentence fragment, logical, "Excludes" refers to products excluding a products(s). Screenshot for context: https://cloudup.com/ccxhyH2mEDg */
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["_x"])('Excludes', 'products', 'woocommerce')
      }],
      input: {
        component: 'Search',
        type: 'products',
        getLabels: lib_async_requests__WEBPACK_IMPORTED_MODULE_3__[/* getProductLabels */ "d"]
      }
    },
    customer: {
      labels: {
        add: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Username', 'woocommerce'),
        placeholder: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Search customer username', 'woocommerce'),
        remove: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Remove customer username filter', 'woocommerce'),
        rule: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Select a customer username filter match', 'woocommerce'),

        /* translators: A sentence describing a customer username filter. See screen shot for context: https://cloudup.com/ccxhyH2mEDg */
        title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('{{title}}Username{{/title}} {{rule /}} {{filter /}}', 'woocommerce'),
        filter: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Select customer username', 'woocommerce')
      },
      rules: [{
        value: 'includes',

        /* translators: Sentence fragment, logical, "Includes" refers to customer usernames including a given username(s). Screenshot for context: https://cloudup.com/ccxhyH2mEDg */
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["_x"])('Includes', 'customer usernames', 'woocommerce')
      }, {
        value: 'excludes',

        /* translators: Sentence fragment, logical, "Excludes" refers to customer usernames excluding a given username(s). Screenshot for context: https://cloudup.com/ccxhyH2mEDg */
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["_x"])('Excludes', 'customer usernames', 'woocommerce')
      }],
      input: {
        component: 'Search',
        type: 'usernames',
        getLabels: lib_async_requests__WEBPACK_IMPORTED_MODULE_3__[/* getCustomerLabels */ "c"]
      }
    },
    order: {
      labels: {
        add: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Order #', 'woocommerce'),
        placeholder: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Search order number', 'woocommerce'),
        remove: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Remove order number filter', 'woocommerce'),
        rule: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Select a order number filter match', 'woocommerce'),

        /* translators: A sentence describing a order number filter. See screen shot for context: https://cloudup.com/ccxhyH2mEDg */
        title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('{{title}}Order #{{/title}} {{rule /}} {{filter /}}', 'woocommerce'),
        filter: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Select order number', 'woocommerce')
      },
      rules: [{
        value: 'includes',

        /* translators: Sentence fragment, logical, "Includes" refers to order numbers including a given order(s). Screenshot for context: https://cloudup.com/ccxhyH2mEDg */
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["_x"])('Includes', 'order numbers', 'woocommerce')
      }, {
        value: 'excludes',

        /* translators: Sentence fragment, logical, "Excludes" refers to order numbers excluding a given order(s). Screenshot for context: https://cloudup.com/ccxhyH2mEDg */
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["_x"])('Excludes', 'order numbers', 'woocommerce')
      }],
      input: {
        component: 'Search',
        type: 'orders',
        getLabels: function () {
          var _getLabels = _babel_runtime_helpers_asyncToGenerator__WEBPACK_IMPORTED_MODULE_0___default()( /*#__PURE__*/regeneratorRuntime.mark(function _callee(value) {
            var orderIds;
            return regeneratorRuntime.wrap(function _callee$(_context) {
              while (1) {
                switch (_context.prev = _context.next) {
                  case 0:
                    orderIds = value.split(',');
                    _context.next = 3;
                    return orderIds.map(function (orderId) {
                      return {
                        id: orderId,
                        label: '#' + orderId
                      };
                    });

                  case 3:
                    return _context.abrupt("return", _context.sent);

                  case 4:
                  case "end":
                    return _context.stop();
                }
              }
            }, _callee);
          }));

          function getLabels(_x2) {
            return _getLabels.apply(this, arguments);
          }

          return getLabels;
        }()
      }
    },
    ip_address: {
      labels: {
        add: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('IP Address', 'woocommerce'),
        placeholder: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Search IP address', 'woocommerce'),
        remove: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Remove IP address filter', 'woocommerce'),
        rule: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Select an IP address filter match', 'woocommerce'),

        /* translators: A sentence describing a order number filter. See screen shot for context: https://cloudup.com/ccxhyH2mEDg */
        title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('{{title}}IP Address{{/title}} {{rule /}} {{filter /}}', 'woocommerce'),
        filter: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Select IP address', 'woocommerce')
      },
      rules: [{
        value: 'includes',

        /* translators: Sentence fragment, logical, "Includes" refers to IP addresses including a given address(s). Screenshot for context: https://cloudup.com/ccxhyH2mEDg */
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["_x"])('Includes', 'IP addresses', 'woocommerce')
      }, {
        value: 'excludes',

        /* translators: Sentence fragment, logical, "Excludes" refers to IP addresses excluding a given address(s). Screenshot for context: https://cloudup.com/ccxhyH2mEDg */
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["_x"])('Excludes', 'IP addresses', 'woocommerce')
      }],
      input: {
        component: 'Search',
        type: 'downloadIps',
        getLabels: function () {
          var _getLabels2 = _babel_runtime_helpers_asyncToGenerator__WEBPACK_IMPORTED_MODULE_0___default()( /*#__PURE__*/regeneratorRuntime.mark(function _callee2(value) {
            var ips;
            return regeneratorRuntime.wrap(function _callee2$(_context2) {
              while (1) {
                switch (_context2.prev = _context2.next) {
                  case 0:
                    ips = value.split(',');
                    _context2.next = 3;
                    return ips.map(function (ip) {
                      return {
                        id: ip,
                        label: ip
                      };
                    });

                  case 3:
                    return _context2.abrupt("return", _context2.sent);

                  case 4:
                  case "end":
                    return _context2.stop();
                }
              }
            }, _callee2);
          }));

          function getLabels(_x3) {
            return _getLabels2.apply(this, arguments);
          }

          return getLabels;
        }()
      }
    }
  }
});
/*eslint-enable max-len*/

/***/ }),

/***/ 893:
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),

/***/ 894:
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),

/***/ 910:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
// ESM COMPAT FLAG
__webpack_require__.r(__webpack_exports__);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/defineProperty.js
var defineProperty = __webpack_require__(15);
var defineProperty_default = /*#__PURE__*/__webpack_require__.n(defineProperty);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/classCallCheck.js
var classCallCheck = __webpack_require__(41);
var classCallCheck_default = /*#__PURE__*/__webpack_require__.n(classCallCheck);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/createClass.js
var createClass = __webpack_require__(40);
var createClass_default = /*#__PURE__*/__webpack_require__.n(createClass);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/possibleConstructorReturn.js
var possibleConstructorReturn = __webpack_require__(44);
var possibleConstructorReturn_default = /*#__PURE__*/__webpack_require__.n(possibleConstructorReturn);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/getPrototypeOf.js
var getPrototypeOf = __webpack_require__(29);
var getPrototypeOf_default = /*#__PURE__*/__webpack_require__.n(getPrototypeOf);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/inherits.js
var inherits = __webpack_require__(42);
var inherits_default = /*#__PURE__*/__webpack_require__.n(inherits);

// EXTERNAL MODULE: external {"this":["wp","element"]}
var external_this_wp_element_ = __webpack_require__(0);

// EXTERNAL MODULE: external {"this":["wp","i18n"]}
var external_this_wp_i18n_ = __webpack_require__(3);

// EXTERNAL MODULE: ./node_modules/classnames/index.js
var classnames = __webpack_require__(10);
var classnames_default = /*#__PURE__*/__webpack_require__.n(classnames);

// EXTERNAL MODULE: ./node_modules/@wordpress/compose/build-module/higher-order/compose.js
var compose = __webpack_require__(256);

// EXTERNAL MODULE: ./node_modules/gridicons/dist/index.js
var dist = __webpack_require__(66);
var dist_default = /*#__PURE__*/__webpack_require__.n(dist);

// EXTERNAL MODULE: ./node_modules/prop-types/index.js
var prop_types = __webpack_require__(1);
var prop_types_default = /*#__PURE__*/__webpack_require__.n(prop_types);

// EXTERNAL MODULE: ./node_modules/@wordpress/components/build-module/select-control/index.js
var select_control = __webpack_require__(709);

// EXTERNAL MODULE: ./node_modules/@wordpress/components/build-module/navigable-container/menu.js + 1 modules
var menu = __webpack_require__(424);

// EXTERNAL MODULE: ./node_modules/@wordpress/components/build-module/icon-button/index.js
var icon_button = __webpack_require__(85);

// EXTERNAL MODULE: external {"this":["wp","data"]}
var external_this_wp_data_ = __webpack_require__(19);

// EXTERNAL MODULE: external {"this":["wc","components"]}
var external_this_wc_components_ = __webpack_require__(63);

// EXTERNAL MODULE: ./client/lib/date.js
var date = __webpack_require__(104);

// EXTERNAL MODULE: external {"this":["wc","navigation"]}
var external_this_wc_navigation_ = __webpack_require__(22);

// EXTERNAL MODULE: ./client/settings/index.js
var settings = __webpack_require__(26);

// EXTERNAL MODULE: ./client/analytics/components/report-chart/index.js + 1 modules
var report_chart = __webpack_require__(741);

// EXTERNAL MODULE: ./client/dashboard/dashboard-charts/block.scss
var block = __webpack_require__(893);

// CONCATENATED MODULE: ./client/dashboard/dashboard-charts/block.js







function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function () { var Super = getPrototypeOf_default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = getPrototypeOf_default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return possibleConstructorReturn_default()(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/**
 * External dependencies
 */



/**
 * WooCommerce dependencies
 */




/**
 * Internal dependencies
 */




var block_ChartBlock = /*#__PURE__*/function (_Component) {
  inherits_default()(ChartBlock, _Component);

  var _super = _createSuper(ChartBlock);

  function ChartBlock() {
    var _temp, _this;

    classCallCheck_default()(this, ChartBlock);

    for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
      args[_key] = arguments[_key];
    }

    return possibleConstructorReturn_default()(_this, (_temp = _this = _super.call.apply(_super, [this].concat(args)), _this.handleChartClick = function () {
      var selectedChart = _this.props.selectedChart;
      Object(external_this_wc_navigation_["getHistory"])().push(_this.getChartPath(selectedChart));
    }, _temp));
  }

  createClass_default()(ChartBlock, [{
    key: "getChartPath",
    value: function getChartPath(chart) {
      return Object(external_this_wc_navigation_["getNewPath"])({
        chart: chart.key
      }, '/analytics/' + chart.endpoint, Object(external_this_wc_navigation_["getPersistedQuery"])());
    }
  }, {
    key: "render",
    value: function render() {
      var _this$props = this.props,
          charts = _this$props.charts,
          endpoint = _this$props.endpoint,
          path = _this$props.path,
          query = _this$props.query,
          selectedChart = _this$props.selectedChart;

      if (!selectedChart) {
        return null;
      }

      return Object(external_this_wp_element_["createElement"])("div", {
        role: "presentation",
        className: "woocommerce-dashboard__chart-block-wrapper",
        onClick: this.handleChartClick
      }, Object(external_this_wp_element_["createElement"])(external_this_wc_components_["Card"], {
        className: "woocommerce-dashboard__chart-block woocommerce-analytics__card",
        title: selectedChart.label
      }, Object(external_this_wp_element_["createElement"])("a", {
        className: "screen-reader-text",
        href: Object(settings["f" /* getAdminLink */])(this.getChartPath(selectedChart))
      },
      /* translators: %s is the chart type */
      Object(external_this_wp_i18n_["sprintf"])(Object(external_this_wp_i18n_["__"])('%s Report', 'woocommerce'), selectedChart.label)), Object(external_this_wp_element_["createElement"])(report_chart["a" /* default */], {
        charts: charts,
        endpoint: endpoint,
        query: query,
        interactiveLegend: false,
        legendPosition: "bottom",
        path: path,
        selectedChart: selectedChart,
        showHeaderControls: false
      })));
    }
  }]);

  return ChartBlock;
}(external_this_wp_element_["Component"]);

block_ChartBlock.propTypes = {
  charts: prop_types_default.a.array,
  endpoint: prop_types_default.a.string.isRequired,
  path: prop_types_default.a.string.isRequired,
  query: prop_types_default.a.object.isRequired,
  selectedChart: prop_types_default.a.object.isRequired
};
/* harmony default export */ var dashboard_charts_block = (block_ChartBlock);
// EXTERNAL MODULE: external {"this":["wp","hooks"]}
var external_this_wp_hooks_ = __webpack_require__(48);

// EXTERNAL MODULE: ./client/analytics/report/orders/config.js
var config = __webpack_require__(765);

// EXTERNAL MODULE: ./client/analytics/report/products/config.js
var products_config = __webpack_require__(764);

// EXTERNAL MODULE: ./client/analytics/report/revenue/config.js
var revenue_config = __webpack_require__(763);

// EXTERNAL MODULE: ./client/analytics/report/coupons/config.js
var coupons_config = __webpack_require__(766);

// EXTERNAL MODULE: ./client/analytics/report/taxes/config.js
var taxes_config = __webpack_require__(767);

// EXTERNAL MODULE: ./client/analytics/report/downloads/config.js
var downloads_config = __webpack_require__(768);

// CONCATENATED MODULE: ./client/dashboard/dashboard-charts/config.js


function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { defineProperty_default()(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

/**
 * External dependencies
 */


/**
 * Internal dependencies
 */







var DASHBOARD_CHARTS_FILTER = 'woocommerce_admin_dashboard_charts_filter';
var config_charts = {
  revenue: revenue_config["b" /* charts */],
  orders: config["b" /* charts */],
  products: products_config["b" /* charts */],
  coupons: coupons_config["b" /* charts */],
  taxes: taxes_config["b" /* charts */],
  downloads: downloads_config["b" /* charts */]
};
var defaultCharts = [{
  label: Object(external_this_wp_i18n_["__"])('Total Sales', 'woocommerce'),
  report: 'revenue',
  key: 'total_sales'
}, {
  label: Object(external_this_wp_i18n_["__"])('Net Sales', 'woocommerce'),
  report: 'revenue',
  key: 'net_revenue'
}, {
  label: Object(external_this_wp_i18n_["__"])('Orders', 'woocommerce'),
  report: 'orders',
  key: 'orders_count'
}, {
  label: Object(external_this_wp_i18n_["__"])('Average Order Value', 'woocommerce'),
  report: 'orders',
  key: 'avg_order_value'
}, {
  label: Object(external_this_wp_i18n_["__"])('Items Sold', 'woocommerce'),
  report: 'products',
  key: 'items_sold'
}, {
  label: Object(external_this_wp_i18n_["__"])('Returns', 'woocommerce'),
  report: 'revenue',
  key: 'refunds'
}, {
  label: Object(external_this_wp_i18n_["__"])('Discounted Orders', 'woocommerce'),
  report: 'coupons',
  key: 'orders_count'
}, {
  label: Object(external_this_wp_i18n_["__"])('Gross discounted', 'woocommerce'),
  report: 'coupons',
  key: 'amount'
}, {
  label: Object(external_this_wp_i18n_["__"])('Total Tax', 'woocommerce'),
  report: 'taxes',
  key: 'total_tax'
}, {
  label: Object(external_this_wp_i18n_["__"])('Order Tax', 'woocommerce'),
  report: 'taxes',
  key: 'order_tax'
}, {
  label: Object(external_this_wp_i18n_["__"])('Shipping Tax', 'woocommerce'),
  report: 'taxes',
  key: 'shipping_tax'
}, {
  label: Object(external_this_wp_i18n_["__"])('Shipping', 'woocommerce'),
  report: 'revenue',
  key: 'shipping'
}, {
  label: Object(external_this_wp_i18n_["__"])('Downloads', 'woocommerce'),
  report: 'downloads',
  key: 'download_count'
}];
var uniqCharts = Object(external_this_wp_hooks_["applyFilters"])(DASHBOARD_CHARTS_FILTER, defaultCharts.map(function (chartDef) {
  return _objectSpread({}, config_charts[chartDef.report].find(function (chart) {
    return chart.key === chartDef.key;
  }), {
    label: chartDef.label,
    endpoint: chartDef.report
  });
}));
// EXTERNAL MODULE: ./client/wc-api/with-select.js
var with_select = __webpack_require__(101);

// EXTERNAL MODULE: ./client/lib/tracks.js
var tracks = __webpack_require__(79);

// EXTERNAL MODULE: ./client/dashboard/dashboard-charts/style.scss
var style = __webpack_require__(894);

// CONCATENATED MODULE: ./client/dashboard/dashboard-charts/index.js








function dashboard_charts_ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function dashboard_charts_objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { dashboard_charts_ownKeys(Object(source), true).forEach(function (key) { defineProperty_default()(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { dashboard_charts_ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function dashboard_charts_createSuper(Derived) { var hasNativeReflectConstruct = dashboard_charts_isNativeReflectConstruct(); return function () { var Super = getPrototypeOf_default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = getPrototypeOf_default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return possibleConstructorReturn_default()(this, result); }; }

function dashboard_charts_isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/**
 * External dependencies
 */








/**
 * WooCommerce dependencies
 */



/**
 * Internal dependencies
 */







var dashboard_charts_DashboardCharts = /*#__PURE__*/function (_Component) {
  inherits_default()(DashboardCharts, _Component);

  var _super = dashboard_charts_createSuper(DashboardCharts);

  function DashboardCharts(props) {
    var _this;

    classCallCheck_default()(this, DashboardCharts);

    _this = _super.apply(this, arguments);

    _this.setInterval = function (interval) {
      _this.setState({
        interval: interval
      }, function () {
        var userDataFields = {
          dashboard_chart_interval: _this.state.interval
        };

        _this.props.updateCurrentUserData(userDataFields);

        Object(tracks["b" /* recordEvent */])('dash_charts_interval', {
          interval: interval
        });
      });
    };

    _this.state = {
      chartType: props.userPrefChartType || 'line',
      interval: props.userPrefChartInterval || 'day'
    };
    return _this;
  }

  createClass_default()(DashboardCharts, [{
    key: "handleTypeToggle",
    value: function handleTypeToggle(chartType) {
      var _this2 = this;

      return function () {
        _this2.setState({
          chartType: chartType
        });

        var userDataFields = {
          dashboard_chart_type: chartType
        };

        _this2.props.updateCurrentUserData(userDataFields);

        Object(tracks["b" /* recordEvent */])('dash_charts_type_toggle', {
          chart_type: chartType
        });
      };
    }
  }, {
    key: "renderMenu",
    value: function renderMenu() {
      var _this$props = this.props,
          hiddenBlocks = _this$props.hiddenBlocks,
          isFirst = _this$props.isFirst,
          isLast = _this$props.isLast,
          onMove = _this$props.onMove,
          onRemove = _this$props.onRemove,
          onTitleBlur = _this$props.onTitleBlur,
          onTitleChange = _this$props.onTitleChange,
          onToggleHiddenBlock = _this$props.onToggleHiddenBlock,
          titleInput = _this$props.titleInput,
          Controls = _this$props.controls;
      return Object(external_this_wp_element_["createElement"])(external_this_wc_components_["EllipsisMenu"], {
        label: Object(external_this_wp_i18n_["__"])('Choose which charts to display', 'woocommerce'),
        renderContent: function renderContent(_ref) {
          var onToggle = _ref.onToggle;
          return Object(external_this_wp_element_["createElement"])(external_this_wp_element_["Fragment"], null, Object(external_this_wp_element_["createElement"])(external_this_wc_components_["MenuTitle"], null, Object(external_this_wp_i18n_["__"])('Charts', 'woocommerce')), uniqCharts.map(function (chart) {
            var key = chart.endpoint + '_' + chart.key;
            var checked = !hiddenBlocks.includes(key);
            return Object(external_this_wp_element_["createElement"])(external_this_wc_components_["MenuItem"], {
              checked: checked,
              isCheckbox: true,
              isClickable: true,
              key: chart.endpoint + '_' + chart.key,
              onInvoke: function onInvoke() {
                onToggleHiddenBlock(key)();
                Object(tracks["b" /* recordEvent */])('dash_charts_chart_toggle', {
                  status: checked ? 'off' : 'on',
                  key: key
                });
              }
            }, chart.label);
          }),  true && Object(external_this_wp_element_["createElement"])(Controls, {
            onToggle: onToggle,
            onMove: onMove,
            onRemove: onRemove,
            isFirst: isFirst,
            isLast: isLast,
            onTitleBlur: onTitleBlur,
            onTitleChange: onTitleChange,
            titleInput: titleInput
          }));
        }
      });
    }
  }, {
    key: "renderIntervalSelector",
    value: function renderIntervalSelector() {
      var allowedIntervals = Object(date["d" /* getAllowedIntervalsForQuery */])(this.props.query);

      if (!allowedIntervals || allowedIntervals.length < 1) {
        return null;
      }

      var intervalLabels = {
        hour: Object(external_this_wp_i18n_["__"])('By hour', 'woocommerce'),
        day: Object(external_this_wp_i18n_["__"])('By day', 'woocommerce'),
        week: Object(external_this_wp_i18n_["__"])('By week', 'woocommerce'),
        month: Object(external_this_wp_i18n_["__"])('By month', 'woocommerce'),
        quarter: Object(external_this_wp_i18n_["__"])('By quarter', 'woocommerce'),
        year: Object(external_this_wp_i18n_["__"])('By year', 'woocommerce')
      };
      return Object(external_this_wp_element_["createElement"])(select_control["a" /* default */], {
        className: "woocommerce-chart__interval-select",
        value: this.state.interval,
        options: allowedIntervals.map(function (allowedInterval) {
          return {
            value: allowedInterval,
            label: intervalLabels[allowedInterval]
          };
        }),
        onChange: this.setInterval
      });
    }
  }, {
    key: "renderChartBlocks",
    value: function renderChartBlocks(query) {
      var _this$props2 = this.props,
          hiddenBlocks = _this$props2.hiddenBlocks,
          path = _this$props2.path; // Reduce the API response to only the necessary stat fields
      // by supplying all charts common to each endpoint.

      var chartsByEndpoint = uniqCharts.reduce(function (byEndpoint, chart) {
        if (typeof byEndpoint[chart.endpoint] === 'undefined') {
          byEndpoint[chart.endpoint] = [];
        }

        byEndpoint[chart.endpoint].push(chart);
        return byEndpoint;
      }, {});
      return Object(external_this_wp_element_["createElement"])("div", {
        className: "woocommerce-dashboard__columns"
      }, uniqCharts.map(function (chart) {
        return hiddenBlocks.includes(chart.endpoint + '_' + chart.key) ? null : Object(external_this_wp_element_["createElement"])(dashboard_charts_block, {
          charts: chartsByEndpoint[chart.endpoint],
          endpoint: chart.endpoint,
          key: chart.endpoint + '_' + chart.key,
          path: path,
          query: query,
          selectedChart: chart
        });
      }));
    }
  }, {
    key: "render",
    value: function render() {
      var title = this.props.title;
      var _this$state = this.state,
          chartType = _this$state.chartType,
          interval = _this$state.interval;

      var query = dashboard_charts_objectSpread({}, this.props.query, {
        chartType: chartType,
        interval: interval
      });

      return Object(external_this_wp_element_["createElement"])(external_this_wp_element_["Fragment"], null, Object(external_this_wp_element_["createElement"])("div", {
        className: "woocommerce-dashboard__dashboard-charts"
      }, Object(external_this_wp_element_["createElement"])(external_this_wc_components_["SectionHeader"], {
        title: title || Object(external_this_wp_i18n_["__"])('Charts', 'woocommerce'),
        menu: this.renderMenu(),
        className: 'has-interval-select'
      }, this.renderIntervalSelector(), Object(external_this_wp_element_["createElement"])(menu["a" /* default */], {
        className: "woocommerce-chart__types",
        orientation: "horizontal",
        role: "menubar"
      }, Object(external_this_wp_element_["createElement"])(icon_button["a" /* default */], {
        className: classnames_default()('woocommerce-chart__type-button', {
          'woocommerce-chart__type-button-selected': !query.chartType || query.chartType === 'line'
        }),
        icon: Object(external_this_wp_element_["createElement"])(dist_default.a, {
          icon: "line-graph"
        }),
        title: Object(external_this_wp_i18n_["__"])('Line chart', 'woocommerce'),
        "aria-checked": query.chartType === 'line',
        role: "menuitemradio",
        tabIndex: query.chartType === 'line' ? 0 : -1,
        onClick: this.handleTypeToggle('line')
      }), Object(external_this_wp_element_["createElement"])(icon_button["a" /* default */], {
        className: classnames_default()('woocommerce-chart__type-button', {
          'woocommerce-chart__type-button-selected': query.chartType === 'bar'
        }),
        icon: Object(external_this_wp_element_["createElement"])(dist_default.a, {
          icon: "stats-alt"
        }),
        title: Object(external_this_wp_i18n_["__"])('Bar chart', 'woocommerce'),
        "aria-checked": query.chartType === 'bar',
        role: "menuitemradio",
        tabIndex: query.chartType === 'bar' ? 0 : -1,
        onClick: this.handleTypeToggle('bar')
      }))), this.renderChartBlocks(query)));
    }
  }]);

  return DashboardCharts;
}(external_this_wp_element_["Component"]);

dashboard_charts_DashboardCharts.propTypes = {
  path: prop_types_default.a.string.isRequired,
  query: prop_types_default.a.object.isRequired
};
/* harmony default export */ var dashboard_charts = __webpack_exports__["default"] = (Object(compose["a" /* default */])(Object(with_select["a" /* default */])(function (select) {
  var _select = select('wc-api'),
      getCurrentUserData = _select.getCurrentUserData;

  var userData = getCurrentUserData();
  return {
    userPrefChartType: userData.dashboard_chart_type,
    userPrefChartInterval: userData.dashboard_chart_interval
  };
}), Object(external_this_wp_data_["withDispatch"])(function (dispatch) {
  var _dispatch = dispatch('wc-api'),
      updateCurrentUserData = _dispatch.updateCurrentUserData;

  return {
    updateCurrentUserData: updateCurrentUserData
  };
}))(dashboard_charts_DashboardCharts));

/***/ })

}]);
