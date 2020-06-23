(window["webpackJsonp"] = window["webpackJsonp"] || []).push([[0],{

/***/ 112:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "a", function() { return useInstanceId; });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/**
 * WordPress dependencies
 */

var instanceMap = new WeakMap();
/**
 * Creates a new id for a given object.
 *
 * @param {Object} object Object reference to create an id for.
 */

function createId(object) {
  var instances = instanceMap.get(object) || 0;
  instanceMap.set(object, instances + 1);
  return instances;
}
/**
 * Provides a unique instance ID.
 *
 * @param {Object} object Object reference to create an id for.
 */


function useInstanceId(object) {
  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["useMemo"])(function () {
    return createId(object);
  }, [object]);
}
//# sourceMappingURL=index.js.map

/***/ }),

/***/ 128:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/esm/defineProperty.js
var defineProperty = __webpack_require__(6);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/esm/objectWithoutProperties.js
var objectWithoutProperties = __webpack_require__(14);

// EXTERNAL MODULE: ./node_modules/classnames/index.js
var classnames = __webpack_require__(8);
var classnames_default = /*#__PURE__*/__webpack_require__.n(classnames);

// EXTERNAL MODULE: external {"this":["wp","element"]}
var external_this_wp_element_ = __webpack_require__(0);

// CONCATENATED MODULE: ./node_modules/@wordpress/components/build-module/visually-hidden/utils.js



/**
 * Utility Functions
 */

/**
 * renderAsRenderProps is used to wrap a component and convert
 * the passed property "as" either a string or component, to the
 * rendered tag if a string, or component.
 *
 * See VisuallyHidden hidden for example.
 *
 * @param {string|WPComponent} as A tag or component to render.
 * @return {WPComponent} The rendered component.
 */
function renderAsRenderProps(_ref) {
  var _ref$as = _ref.as,
      Component = _ref$as === void 0 ? 'div' : _ref$as,
      props = Object(objectWithoutProperties["a" /* default */])(_ref, ["as"]);

  if (typeof props.children === 'function') {
    return props.children(props);
  }

  return Object(external_this_wp_element_["createElement"])(Component, props);
}


//# sourceMappingURL=utils.js.map
// CONCATENATED MODULE: ./node_modules/@wordpress/components/build-module/visually-hidden/index.js



function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { Object(defineProperty["a" /* default */])(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

/**
 * External dependencies
 */

/**
 * Internal dependencies
 */


/**
 * VisuallyHidden component to render text out non-visually
 * for use in devices such as a screen reader.
 */

function VisuallyHidden(_ref) {
  var _ref$as = _ref.as,
      as = _ref$as === void 0 ? 'div' : _ref$as,
      className = _ref.className,
      props = Object(objectWithoutProperties["a" /* default */])(_ref, ["as", "className"]);

  return renderAsRenderProps(_objectSpread({
    as: as,
    className: classnames_default()('components-visually-hidden', className)
  }, props));
}

/* harmony default export */ var visually_hidden = __webpack_exports__["a"] = (VisuallyHidden);
//# sourceMappingURL=index.js.map

/***/ }),

/***/ 171:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(8);
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(classnames__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _visually_hidden__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(128);


/**
 * External dependencies
 */

/**
 * Internal dependencies
 */



function BaseControl(_ref) {
  var id = _ref.id,
      label = _ref.label,
      hideLabelFromVision = _ref.hideLabelFromVision,
      help = _ref.help,
      className = _ref.className,
      children = _ref.children;
  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("div", {
    className: classnames__WEBPACK_IMPORTED_MODULE_1___default()('components-base-control', className)
  }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("div", {
    className: "components-base-control__field"
  }, label && id && (hideLabelFromVision ? Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_visually_hidden__WEBPACK_IMPORTED_MODULE_2__[/* default */ "a"], {
    as: "label",
    htmlFor: id
  }, label) : Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("label", {
    className: "components-base-control__label",
    htmlFor: id
  }, label)), label && !id && (hideLabelFromVision ? Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_visually_hidden__WEBPACK_IMPORTED_MODULE_2__[/* default */ "a"], {
    as: "label"
  }, label) : Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(BaseControl.VisualLabel, null, label)), children), !!help && Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("p", {
    id: id + '__help',
    className: "components-base-control__help"
  }, help));
}

BaseControl.VisualLabel = function (_ref2) {
  var className = _ref2.className,
      children = _ref2.children;
  className = classnames__WEBPACK_IMPORTED_MODULE_1___default()('components-base-control__label', className);
  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("span", {
    className: className
  }, children);
};

/* harmony default export */ __webpack_exports__["a"] = (BaseControl);
//# sourceMappingURL=index.js.map

/***/ }),

/***/ 422:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var _babel_runtime_helpers_esm_defineProperty__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(6);
/* harmony import */ var _babel_runtime_helpers_esm_objectWithoutProperties__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(14);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__);



function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { Object(_babel_runtime_helpers_esm_defineProperty__WEBPACK_IMPORTED_MODULE_0__[/* default */ "a"])(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

/**
 * WordPress dependencies
 */
 // Disable reason: JSDoc linter doesn't seem to parse the union (`&`) correctly.

/* eslint-disable jsdoc/valid-types */

/** @typedef {{icon: JSX.Element, size?: number} & import('react').ComponentPropsWithoutRef<'SVG'>} IconProps */

/* eslint-enable jsdoc/valid-types */

/**
 * Return an SVG icon.
 *
 * @param {IconProps} props icon is the SVG component to render
 *                          size is a number specifiying the icon size in pixels
 *                          Other props will be passed to wrapped SVG component
 *
 * @return {JSX.Element}  Icon component
 */

function Icon(_ref) {
  var icon = _ref.icon,
      _ref$size = _ref.size,
      size = _ref$size === void 0 ? 24 : _ref$size,
      props = Object(_babel_runtime_helpers_esm_objectWithoutProperties__WEBPACK_IMPORTED_MODULE_1__[/* default */ "a"])(_ref, ["icon", "size"]);

  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__["cloneElement"])(icon, _objectSpread({
    width: size,
    height: size
  }, props));
}

/* harmony default export */ __webpack_exports__["a"] = (Icon);
//# sourceMappingURL=index.js.map

/***/ }),

/***/ 750:
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
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(17);
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _babel_runtime_helpers_slicedToArray__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(146);
/* harmony import */ var _babel_runtime_helpers_slicedToArray__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_slicedToArray__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(2);
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var moment__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(16);
/* harmony import */ var moment__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(moment__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var lib_date__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(108);
/* harmony import */ var _woocommerce_navigation__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(23);
/* harmony import */ var _woocommerce_navigation__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var wc_api_constants__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(33);
/* harmony import */ var _utils__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(750);



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
  return noIntervals ? _objectSpread(_objectSpread({}, filterQuery), {}, {
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
  var primaryQuery = getRequestQuery(_objectSpread(_objectSpread({}, options), {}, {
    dataType: 'primary'
  })); // Disable eslint rule requiring `getReportStats` to be defined below because the next two statements
  // depend on `getReportStats` to have been called.
  // eslint-disable-next-line @wordpress/no-unused-vars-before-return

  var primary = getReportStats(endpoint, primaryQuery);

  if (isReportStatsRequesting(endpoint, primaryQuery)) {
    return _objectSpread(_objectSpread({}, response), {}, {
      isRequesting: true
    });
  } else if (getReportStatsError(endpoint, primaryQuery)) {
    return _objectSpread(_objectSpread({}, response), {}, {
      isError: true
    });
  }

  var primaryTotals = primary && primary.data && primary.data.totals || null;
  var secondaryQuery = getRequestQuery(_objectSpread(_objectSpread({}, options), {}, {
    dataType: 'secondary'
  })); // Disable eslint rule requiring `getReportStats` to be defined below because the next two statements
  // depend on `getReportStats` to have been called.
  // eslint-disable-next-line @wordpress/no-unused-vars-before-return

  var secondary = getReportStats(endpoint, secondaryQuery);

  if (isReportStatsRequesting(endpoint, secondaryQuery)) {
    return _objectSpread(_objectSpread({}, response), {}, {
      isRequesting: true
    });
  } else if (getReportStatsError(endpoint, secondaryQuery)) {
    return _objectSpread(_objectSpread({}, response), {}, {
      isError: true
    });
  }

  var secondaryTotals = secondary && secondary.data && secondary.data.totals || null;
  return _objectSpread(_objectSpread({}, response), {}, {
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
    return _objectSpread(_objectSpread({}, response), {}, {
      isRequesting: true
    });
  }

  if (getReportStatsError(endpoint, requestQuery)) {
    return _objectSpread(_objectSpread({}, response), {}, {
      isError: true
    });
  }

  if (isReportDataEmpty(stats, endpoint)) {
    return _objectSpread(_objectSpread({}, response), {}, {
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
      var nextQuery = _objectSpread(_objectSpread({}, requestQuery), {}, {
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
      return _objectSpread(_objectSpread({}, response), {}, {
        isRequesting: true
      });
    } else if (isError) {
      return _objectSpread(_objectSpread({}, response), {}, {
        isError: true
      });
    }

    Object(lodash__WEBPACK_IMPORTED_MODULE_2__["forEach"])(pagedData, function (_data) {
      intervals = intervals.concat(_data.data.intervals);
    });
  }

  return _objectSpread(_objectSpread({}, response), {}, {
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
  return _objectSpread(_objectSpread({
    orderby: query.orderby || 'date',
    order: query.order || 'desc',
    after: noIntervals ? undefined : Object(lib_date__WEBPACK_IMPORTED_MODULE_4__[/* appendTimestamp */ "a"])(datesFromQuery.primary.after, 'start'),
    before: noIntervals ? undefined : Object(lib_date__WEBPACK_IMPORTED_MODULE_4__[/* appendTimestamp */ "a"])(datesFromQuery.primary.before, 'end'),
    page: query.paged || 1,
    per_page: query.per_page || wc_api_constants__WEBPACK_IMPORTED_MODULE_6__[/* QUERY_DEFAULTS */ "d"].pageSize
  }, filterQuery), tableQuery);
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
    return _objectSpread(_objectSpread({}, response), {}, {
      isRequesting: true
    });
  } else if (getReportItemsError(endpoint, tableQuery)) {
    return _objectSpread(_objectSpread({}, response), {}, {
      isError: true
    });
  }

  return _objectSpread(_objectSpread({}, response), {}, {
    items: items
  });
}

/***/ }),

/***/ 757:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(17);
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(38);
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(37);
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(62);
/* harmony import */ var _babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(39);
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(42);
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(26);
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(1);
/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(prop_types__WEBPACK_IMPORTED_MODULE_8__);
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(2);
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_9__);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(18);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_10___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_10__);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(53);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_11___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_components__WEBPACK_IMPORTED_MODULE_11__);
/* harmony import */ var _woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(22);
/* harmony import */ var _woocommerce_data__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(43);
/* harmony import */ var _woocommerce_data__WEBPACK_IMPORTED_MODULE_13___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_data__WEBPACK_IMPORTED_MODULE_13__);
/* harmony import */ var lib_tracks__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(63);
/* harmony import */ var lib_date__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(108);
/* harmony import */ var lib_currency_context__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__(200);









function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0___default()(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_6___default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_6___default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_5___default()(this, result); }; }

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





var ReportFilters = /*#__PURE__*/function (_Component) {
  _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_4___default()(ReportFilters, _Component);

  var _super = _createSuper(ReportFilters);

  function ReportFilters() {
    var _this;

    _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_1___default()(this, ReportFilters);

    _this = _super.call(this);
    _this.trackDateSelect = _this.trackDateSelect.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_3___default()(_this));
    _this.trackFilterSelect = _this.trackFilterSelect.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_3___default()(_this));
    _this.trackAdvancedFilterAction = _this.trackAdvancedFilterAction.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_3___default()(_this));
    return _this;
  }

  _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_2___default()(ReportFilters, [{
    key: "trackDateSelect",
    value: function trackDateSelect(data) {
      var report = this.props.report;
      Object(lib_tracks__WEBPACK_IMPORTED_MODULE_14__[/* recordEvent */ "b"])('datepicker_update', _objectSpread({
        report: report
      }, Object(lodash__WEBPACK_IMPORTED_MODULE_9__["omitBy"])(data, lodash__WEBPACK_IMPORTED_MODULE_9__["isUndefined"])));
    }
  }, {
    key: "trackFilterSelect",
    value: function trackFilterSelect(data) {
      var report = this.props.report;
      Object(lib_tracks__WEBPACK_IMPORTED_MODULE_14__[/* recordEvent */ "b"])('analytics_filter', {
        report: report,
        filter: data.filter || 'all'
      });
    }
  }, {
    key: "trackAdvancedFilterAction",
    value: function trackAdvancedFilterAction(action, data) {
      var report = this.props.report;

      switch (action) {
        case 'add':
          Object(lib_tracks__WEBPACK_IMPORTED_MODULE_14__[/* recordEvent */ "b"])('analytics_filters_add', {
            report: report,
            filter: data.key
          });
          break;

        case 'remove':
          Object(lib_tracks__WEBPACK_IMPORTED_MODULE_14__[/* recordEvent */ "b"])('analytics_filters_remove', {
            report: report,
            filter: data.key
          });
          break;

        case 'filter':
          var snakeCaseData = Object.keys(data).reduce(function (result, property) {
            result[Object(lodash__WEBPACK_IMPORTED_MODULE_9__["snakeCase"])(property)] = data[property];
            return result;
          }, {});
          Object(lib_tracks__WEBPACK_IMPORTED_MODULE_14__[/* recordEvent */ "b"])('analytics_filters_filter', {
            report: report,
            snakeCaseData: snakeCaseData
          });
          break;

        case 'clear_all':
          Object(lib_tracks__WEBPACK_IMPORTED_MODULE_14__[/* recordEvent */ "b"])('analytics_filters_clear_all', {
            report: report
          });
          break;

        case 'match':
          Object(lib_tracks__WEBPACK_IMPORTED_MODULE_14__[/* recordEvent */ "b"])('analytics_filters_all_any', {
            report: report,
            value: data.match
          });
          break;
      }
    }
  }, {
    key: "render",
    value: function render() {
      var _this$props = this.props,
          advancedFilters = _this$props.advancedFilters,
          filters = _this$props.filters,
          path = _this$props.path,
          query = _this$props.query,
          showDatePicker = _this$props.showDatePicker,
          defaultDateRange = _this$props.defaultDateRange;

      var _getDateParamsFromQue = Object(lib_date__WEBPACK_IMPORTED_MODULE_15__[/* getDateParamsFromQuery */ "h"])(query, defaultDateRange),
          period = _getDateParamsFromQue.period,
          compare = _getDateParamsFromQue.compare,
          before = _getDateParamsFromQue.before,
          after = _getDateParamsFromQue.after;

      var _getCurrentDates = Object(lib_date__WEBPACK_IMPORTED_MODULE_15__[/* getCurrentDates */ "f"])(query, defaultDateRange),
          primaryDate = _getCurrentDates.primary,
          secondaryDate = _getCurrentDates.secondary;

      var dateQuery = {
        period: period,
        compare: compare,
        before: before,
        after: after,
        primaryDate: primaryDate,
        secondaryDate: secondaryDate
      };
      var Currency = this.context;
      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_11__["ReportFilters"], {
        query: query,
        siteLocale: _woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_12__[/* LOCALE */ "c"].siteLocale,
        currency: Currency.getCurrency(),
        path: path,
        filters: filters,
        advancedFilters: advancedFilters,
        showDatePicker: showDatePicker,
        onDateSelect: this.trackDateSelect,
        onFilterSelect: this.trackFilterSelect,
        onAdvancedFilterAction: this.trackAdvancedFilterAction,
        dateQuery: dateQuery,
        isoDateFormat: lib_date__WEBPACK_IMPORTED_MODULE_15__[/* isoDateFormat */ "k"]
      });
    }
  }]);

  return ReportFilters;
}(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["Component"]);

ReportFilters.contextType = lib_currency_context__WEBPACK_IMPORTED_MODULE_16__[/* CurrencyContext */ "a"];
/* harmony default export */ __webpack_exports__["a"] = (Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_10__["withSelect"])(function (select) {
  var _select$getSetting = select(_woocommerce_data__WEBPACK_IMPORTED_MODULE_13__["SETTINGS_STORE_NAME"]).getSetting('wc_admin', 'wcAdminSettings'),
      defaultDateRange = _select$getSetting.woocommerce_default_date_range;

  return {
    defaultDateRange: defaultDateRange
  };
})(ReportFilters));
ReportFilters.propTypes = {
  /**
   * Config option passed through to `AdvancedFilters`
   */
  advancedFilters: prop_types__WEBPACK_IMPORTED_MODULE_8___default.a.object,

  /**
   * Config option passed through to `FilterPicker` - if not used, `FilterPicker` is not displayed.
   */
  filters: prop_types__WEBPACK_IMPORTED_MODULE_8___default.a.array,

  /**
   * The `path` parameter supplied by React-Router
   */
  path: prop_types__WEBPACK_IMPORTED_MODULE_8___default.a.string.isRequired,

  /**
   * The query string represented in object form
   */
  query: prop_types__WEBPACK_IMPORTED_MODULE_8___default.a.object,

  /**
   * Whether the date picker must be shown..
   */
  showDatePicker: prop_types__WEBPACK_IMPORTED_MODULE_8___default.a.bool,

  /**
   * The report where filter are placed.
   */
  report: prop_types__WEBPACK_IMPORTED_MODULE_8___default.a.string.isRequired
};

/***/ }),

/***/ 759:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/extends.js
var helpers_extends = __webpack_require__(80);
var extends_default = /*#__PURE__*/__webpack_require__.n(helpers_extends);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/toConsumableArray.js
var toConsumableArray = __webpack_require__(41);
var toConsumableArray_default = /*#__PURE__*/__webpack_require__.n(toConsumableArray);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/defineProperty.js
var defineProperty = __webpack_require__(17);
var defineProperty_default = /*#__PURE__*/__webpack_require__.n(defineProperty);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/slicedToArray.js
var slicedToArray = __webpack_require__(146);
var slicedToArray_default = /*#__PURE__*/__webpack_require__.n(slicedToArray);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/objectWithoutProperties.js
var objectWithoutProperties = __webpack_require__(129);
var objectWithoutProperties_default = /*#__PURE__*/__webpack_require__.n(objectWithoutProperties);

// EXTERNAL MODULE: external {"this":["wp","element"]}
var external_this_wp_element_ = __webpack_require__(0);

// EXTERNAL MODULE: ./node_modules/@wordpress/components/build-module/checkbox-control/index.js
var checkbox_control = __webpack_require__(771);

// EXTERNAL MODULE: ./node_modules/@wordpress/components/build-module/button/index.js
var build_module_button = __webpack_require__(67);

// EXTERNAL MODULE: external {"this":["wp","hooks"]}
var external_this_wp_hooks_ = __webpack_require__(48);

// EXTERNAL MODULE: ./node_modules/@wordpress/compose/build-module/higher-order/compose.js
var compose = __webpack_require__(169);

// EXTERNAL MODULE: ./node_modules/@wordpress/dom/build-module/index.js + 2 modules
var build_module = __webpack_require__(45);

// EXTERNAL MODULE: external {"this":["wp","data"]}
var external_this_wp_data_ = __webpack_require__(18);

// EXTERNAL MODULE: external "lodash"
var external_lodash_ = __webpack_require__(2);

// EXTERNAL MODULE: external {"this":["wp","i18n"]}
var external_this_wp_i18n_ = __webpack_require__(3);

// EXTERNAL MODULE: ./node_modules/classnames/index.js
var classnames = __webpack_require__(8);
var classnames_default = /*#__PURE__*/__webpack_require__.n(classnames);

// EXTERNAL MODULE: ./node_modules/prop-types/index.js
var prop_types = __webpack_require__(1);
var prop_types_default = /*#__PURE__*/__webpack_require__.n(prop_types);

// EXTERNAL MODULE: external {"this":["wc","components"]}
var external_this_wc_components_ = __webpack_require__(53);

// CONCATENATED MODULE: ./client/analytics/components/report-table/download-icon.js

/* harmony default export */ var download_icon = (function () {
  return Object(external_this_wp_element_["createElement"])("svg", {
    role: "img",
    "aria-hidden": "true",
    focusable: "false",
    version: "1.1",
    xmlns: "http://www.w3.org/2000/svg",
    x: "0px",
    y: "0px",
    viewBox: "0 0 24 24"
  }, Object(external_this_wp_element_["createElement"])("path", {
    d: "M18,9c-0.009,0-0.017,0.002-0.025,0.003C17.72,5.646,14.922,3,11.5,3C7.91,3,5,5.91,5,9.5c0,0.524,0.069,1.031,0.186,1.519 C5.123,11.016,5.064,11,5,11c-2.209,0-4,1.791-4,4c0,1.202,0.541,2.267,1.38,3h18.593C22.196,17.089,23,15.643,23,14 C23,11.239,20.761,9,18,9z M12,16l-4-5h3V8h2v3h3L12,16z"
  }));
});
// EXTERNAL MODULE: external {"this":["wc","navigation"]}
var external_this_wc_navigation_ = __webpack_require__(23);

// EXTERNAL MODULE: external {"this":["wc","csvExport"]}
var external_this_wc_csvExport_ = __webpack_require__(733);

// EXTERNAL MODULE: external {"this":["wc","data"]}
var external_this_wc_data_ = __webpack_require__(43);

// EXTERNAL MODULE: ./client/analytics/components/report-error/index.js
var report_error = __webpack_require__(261);

// EXTERNAL MODULE: ./client/wc-api/reports/utils.js
var utils = __webpack_require__(750);

// EXTERNAL MODULE: ./client/wc-api/constants.js
var constants = __webpack_require__(33);

// EXTERNAL MODULE: ./client/wc-api/with-select.js
var with_select = __webpack_require__(101);

// CONCATENATED MODULE: ./client/analytics/components/report-table/utils.js


function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { defineProperty_default()(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

/**
 * External dependencies
 */

function extendTableData(select, props, queriedTableData) {
  var extendItemsMethodNames = props.extendItemsMethodNames,
      itemIdField = props.itemIdField;
  var itemsData = queriedTableData.items.data;

  if (!Array.isArray(itemsData) || !itemsData.length || !extendItemsMethodNames || !itemIdField) {
    return queriedTableData;
  }

  var _select = select('wc-api'),
      getErrorMethod = _select[extendItemsMethodNames.getError],
      isRequestingMethod = _select[extendItemsMethodNames.isRequesting],
      loadMethod = _select[extendItemsMethodNames.load];

  var extendQuery = {
    include: itemsData.map(function (item) {
      return item[itemIdField];
    }).join(','),
    per_page: itemsData.length
  };
  var extendedItems = loadMethod(extendQuery);
  var isExtendedItemsRequesting = isRequestingMethod ? isRequestingMethod(extendQuery) : false;
  var isExtendedItemsError = getErrorMethod ? getErrorMethod(extendQuery) : false;
  var extendedItemsData = itemsData.map(function (item) {
    var extendedItemData = Object(external_lodash_["first"])(extendedItems.filter(function (extendedItem) {
      return item.id === extendedItem.id;
    }));
    return _objectSpread(_objectSpread({}, item), extendedItemData);
  });
  var isRequesting = queriedTableData.isRequesting || isExtendedItemsRequesting;
  var isError = queriedTableData.isError || isExtendedItemsError;
  return _objectSpread(_objectSpread({}, queriedTableData), {}, {
    isRequesting: isRequesting,
    isError: isError,
    items: _objectSpread(_objectSpread({}, queriedTableData.items), {}, {
      data: extendedItemsData
    })
  });
}
// EXTERNAL MODULE: ./client/lib/tracks.js
var tracks = __webpack_require__(63);

// EXTERNAL MODULE: ./client/analytics/components/report-table/style.scss
var style = __webpack_require__(897);

// CONCATENATED MODULE: ./client/analytics/components/report-table/index.js







function report_table_ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function report_table_objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { report_table_ownKeys(Object(source), true).forEach(function (key) { defineProperty_default()(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { report_table_ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

/**
 * External dependencies
 */










/**
 * WooCommerce dependencies
 */






/**
 * Internal dependencies
 */








var TABLE_FILTER = 'woocommerce_admin_report_table';

var report_table_ReportTable = function ReportTable(props) {
  var getHeadersContent = props.getHeadersContent,
      getRowsContent = props.getRowsContent,
      getSummary = props.getSummary,
      isRequesting = props.isRequesting,
      primaryData = props.primaryData,
      tableData = props.tableData,
      endpoint = props.endpoint,
      itemIdField = props.itemIdField,
      tableQuery = props.tableQuery,
      compareBy = props.compareBy,
      searchBy = props.searchBy,
      _props$labels = props.labels,
      labels = _props$labels === void 0 ? {} : _props$labels,
      tableProps = objectWithoutProperties_default()(props, ["getHeadersContent", "getRowsContent", "getSummary", "isRequesting", "primaryData", "tableData", "endpoint", "itemIdField", "tableQuery", "compareBy", "searchBy", "labels"]); // Pull these props out separately because they need to be included in tableProps.


  var query = props.query,
      columnPrefsKey = props.columnPrefsKey;
  var items = tableData.items,
      reportQuery = tableData.query;
  var initialSelectedRows = query.filter ? Object(external_this_wc_navigation_["getIdsFromQuery"])(query[compareBy]) : [];

  var _useState = Object(external_this_wp_element_["useState"])(initialSelectedRows),
      _useState2 = slicedToArray_default()(_useState, 2),
      selectedRows = _useState2[0],
      setSelectedRows = _useState2[1];

  var scrollPointRef = Object(external_this_wp_element_["useRef"])(null);

  var _useUserPreferences = Object(external_this_wc_data_["useUserPreferences"])(),
      updateUserPreferences = _useUserPreferences.updateUserPreferences,
      userData = objectWithoutProperties_default()(_useUserPreferences, ["updateUserPreferences"]); // Bail early if we've encountered an error.


  var isError = tableData.isError || primaryData.isError;

  if (isError) {
    return Object(external_this_wp_element_["createElement"])(report_error["a" /* default */], {
      isError: true
    });
  }

  var userPrefColumns = [];

  if (columnPrefsKey) {
    userPrefColumns = userData && userData[columnPrefsKey] ? userData[columnPrefsKey] : userPrefColumns;
  }

  var onColumnsChange = function onColumnsChange(shownColumns, toggledColumn) {
    var columns = getHeadersContent().map(function (header) {
      return header.key;
    });
    var hiddenColumns = columns.filter(function (column) {
      return !shownColumns.includes(column);
    });

    if (columnPrefsKey) {
      var userDataFields = defineProperty_default()({}, columnPrefsKey, hiddenColumns);

      updateUserPreferences(userDataFields);
    }

    if (toggledColumn) {
      var eventProps = {
        report: endpoint,
        column: toggledColumn,
        status: shownColumns.includes(toggledColumn) ? 'on' : 'off'
      };
      Object(tracks["b" /* recordEvent */])('analytics_table_header_toggle', eventProps);
    }
  };

  var onPageChange = function onPageChange(newPage, source) {
    scrollPointRef.current.scrollIntoView();
    var tableElement = scrollPointRef.current.nextSibling.querySelector('.woocommerce-table__table');
    var focusableElements = build_module["a" /* focus */].focusable.find(tableElement);

    if (focusableElements.length) {
      focusableElements[0].focus();
    }

    if (source) {
      if (source === 'goto') {
        Object(tracks["b" /* recordEvent */])('analytics_table_go_to_page', {
          report: endpoint,
          page: newPage
        });
      } else {
        Object(tracks["b" /* recordEvent */])('analytics_table_page_click', {
          report: endpoint,
          direction: source
        });
      }
    }
  };

  var trackTableSearch = function trackTableSearch() {
    // @todo: decide if this should only fire for new tokens (not any/all changes).
    Object(tracks["b" /* recordEvent */])('analytics_table_filter', {
      report: endpoint
    });
  };

  var onSort = function onSort(key, direction) {
    Object(external_this_wc_navigation_["onQueryChange"])('sort')(key, direction);
    var eventProps = {
      report: endpoint,
      column: key,
      direction: direction
    };
    Object(tracks["b" /* recordEvent */])('analytics_table_sort', eventProps);
  };

  var filterShownHeaders = function filterShownHeaders(headers, hiddenKeys) {
    // If no user preferences, set visibilty based on column default.
    if (!hiddenKeys) {
      return headers.map(function (header) {
        return report_table_objectSpread(report_table_objectSpread({}, header), {}, {
          visible: header.required || !header.hiddenByDefault
        });
      });
    } // Set visibilty based on user preferences.


    return headers.map(function (header) {
      return report_table_objectSpread(report_table_objectSpread({}, header), {}, {
        visible: header.required || !hiddenKeys.includes(header.key)
      });
    });
  };

  var onClickDownload = function onClickDownload() {
    var initiateReportExport = props.initiateReportExport,
        title = props.title;
    var params = Object.assign({}, query);
    var data = items.data,
        totalResults = items.totalResults;
    var downloadType = 'browser'; // Delete unnecessary items from filename.

    delete params.extended_info;

    if (params.search) {
      delete params[searchBy];
    }

    if (data && data.length === totalResults) {
      Object(external_this_wc_csvExport_["downloadCSVFile"])(Object(external_this_wc_csvExport_["generateCSVFileName"])(title, params), Object(external_this_wc_csvExport_["generateCSVDataFromTable"])(getHeadersContent(), getRowsContent(data)));
    } else {
      downloadType = 'email';
      initiateReportExport(endpoint, title, reportQuery);
    }

    Object(tracks["b" /* recordEvent */])('analytics_table_download', {
      report: endpoint,
      rows: totalResults,
      downloadType: downloadType
    });
  };

  var onCompare = function onCompare() {
    var compareParam = props.compareParam;

    if (compareBy) {
      Object(external_this_wc_navigation_["onQueryChange"])('compare')(compareBy, compareParam, selectedRows.join(','));
    }
  };

  var onSearchChange = function onSearchChange(values) {
    var baseSearchQuery = props.baseSearchQuery,
        compareParam = props.compareParam; // A comma is used as a separator between search terms, so we want to escape
    // any comma they contain.

    var searchTerms = values.map(function (v) {
      return v.label.replace(',', '%2C');
    });

    if (searchTerms.length) {
      var _objectSpread2;

      Object(external_this_wc_navigation_["updateQueryString"])(report_table_objectSpread(report_table_objectSpread((_objectSpread2 = {
        filter: undefined
      }, defineProperty_default()(_objectSpread2, compareParam, undefined), defineProperty_default()(_objectSpread2, searchBy, undefined), _objectSpread2), baseSearchQuery), {}, {
        search: Object(external_lodash_["uniq"])(searchTerms).join(',')
      }));
    } else {
      Object(external_this_wc_navigation_["updateQueryString"])({
        search: undefined
      });
    }

    trackTableSearch();
  };

  var selectAllRows = function selectAllRows(checked) {
    var ids = props.ids;
    setSelectedRows(checked ? ids : []);
  };

  var selectRow = function selectRow(i, checked) {
    var ids = props.ids;

    if (checked) {
      setSelectedRows(Object(external_lodash_["uniq"])([ids[i]].concat(toConsumableArray_default()(selectedRows))));
    } else {
      var index = selectedRows.indexOf(ids[i]);
      setSelectedRows([].concat(toConsumableArray_default()(selectedRows.slice(0, index)), toConsumableArray_default()(selectedRows.slice(index + 1))));
    }
  };

  var getCheckbox = function getCheckbox(i) {
    var _props$ids = props.ids,
        ids = _props$ids === void 0 ? [] : _props$ids;
    var isChecked = selectedRows.indexOf(ids[i]) !== -1;
    return {
      display: Object(external_this_wp_element_["createElement"])(checkbox_control["a" /* default */], {
        onChange: Object(external_lodash_["partial"])(selectRow, i),
        checked: isChecked
      }),
      value: false
    };
  };

  var getAllCheckbox = function getAllCheckbox() {
    var _props$ids2 = props.ids,
        ids = _props$ids2 === void 0 ? [] : _props$ids2;
    var hasData = ids.length > 0;
    var isAllChecked = hasData && ids.length === selectedRows.length;
    return {
      cellClassName: 'is-checkbox-column',
      key: 'compare',
      label: Object(external_this_wp_element_["createElement"])(checkbox_control["a" /* default */], {
        onChange: selectAllRows,
        "aria-label": Object(external_this_wp_i18n_["__"])('Select All'),
        checked: isAllChecked,
        disabled: !hasData
      }),
      required: true
    };
  };

  var isLoading = isRequesting || tableData.isRequesting || primaryData.isRequesting;
  var totals = Object(external_lodash_["get"])(primaryData, ['data', 'totals'], {});
  var totalResults = items.totalResults;
  var downloadable = totalResults > 0; // Search words are in the query string, not the table query.

  var searchWords = Object(external_this_wc_navigation_["getSearchWords"])(query);
  var searchedLabels = searchWords.map(function (v) {
    return {
      key: v,
      label: v
    };
  });
  /**
   * Filter report table.
   *
   * Enables manipulation of data used to create a report table.
   *
   * @param {Object} reportTableData - data used to create the table.
   * @param {string} reportTableData.endpoint - table api endpoint.
   * @param {Array} reportTableData.headers - table headers data.
   * @param {Array} reportTableData.rows - table rows data.
   * @param {Object} reportTableData.totals - total aggregates for request.
   * @param {Array} reportTableData.summary - summary numbers data.
   * @param {Object} reportTableData.items - response from api requerst.
   */

  var filteredTableProps = Object(external_this_wp_hooks_["applyFilters"])(TABLE_FILTER, {
    endpoint: endpoint,
    headers: getHeadersContent(),
    rows: getRowsContent(items.data),
    totals: totals,
    summary: getSummary ? getSummary(totals, totalResults) : null,
    items: items
  });
  var headers = filteredTableProps.headers,
      rows = filteredTableProps.rows;
  var summary = filteredTableProps.summary; // Add in selection for comparisons.

  if (compareBy) {
    rows = rows.map(function (row, i) {
      return [getCheckbox(i)].concat(toConsumableArray_default()(row));
    });
    headers = [getAllCheckbox()].concat(toConsumableArray_default()(headers));
  } // Hide any headers based on user prefs, if loaded.


  var filteredHeaders = filterShownHeaders(headers, userPrefColumns);
  var className = classnames_default()('woocommerce-report-table', {
    'has-compare': !!compareBy,
    'has-search': !!searchBy
  });
  return Object(external_this_wp_element_["createElement"])(external_this_wp_element_["Fragment"], null, Object(external_this_wp_element_["createElement"])("div", {
    className: "woocommerce-report-table__scroll-point",
    ref: scrollPointRef,
    "aria-hidden": true
  }), Object(external_this_wp_element_["createElement"])(external_this_wc_components_["TableCard"], extends_default()({
    className: className,
    actions: [compareBy && Object(external_this_wp_element_["createElement"])(external_this_wc_components_["CompareButton"], {
      key: "compare",
      className: "woocommerce-table__compare",
      count: selectedRows.length,
      helpText: labels.helpText || Object(external_this_wp_i18n_["__"])('Check at least two items below to compare', 'woocommerce'),
      onClick: onCompare,
      disabled: !downloadable
    }, labels.compareButton || Object(external_this_wp_i18n_["__"])('Compare', 'woocommerce')), searchBy && Object(external_this_wp_element_["createElement"])(external_this_wc_components_["Search"], {
      allowFreeTextSearch: true,
      inlineTags: true,
      key: "search",
      onChange: onSearchChange,
      placeholder: labels.placeholder || Object(external_this_wp_i18n_["__"])('Search by item name', 'woocommerce'),
      selected: searchedLabels,
      showClearButton: true,
      type: searchBy,
      disabled: !downloadable
    }), downloadable && Object(external_this_wp_element_["createElement"])(build_module_button["a" /* default */], {
      key: "download",
      className: "woocommerce-table__download-button",
      disabled: isLoading,
      onClick: onClickDownload
    }, Object(external_this_wp_element_["createElement"])(download_icon, null), Object(external_this_wp_element_["createElement"])("span", {
      className: "woocommerce-table__download-button__label"
    }, labels.downloadButton || Object(external_this_wp_i18n_["__"])('Download', 'woocommerce')))],
    headers: filteredHeaders,
    isLoading: isLoading,
    onQueryChange: external_this_wc_navigation_["onQueryChange"],
    onColumnsChange: onColumnsChange,
    onSort: onSort,
    onPageChange: onPageChange,
    rows: rows,
    rowsPerPage: parseInt(reportQuery.per_page, 10) || constants["d" /* QUERY_DEFAULTS */].pageSize,
    summary: summary,
    totalRows: totalResults
  }, tableProps)));
};

report_table_ReportTable.propTypes = {
  /**
   * Pass in query parameters to be included in the path when onSearch creates a new url.
   */
  baseSearchQuery: prop_types_default.a.object,

  /**
   * The string to use as a query parameter when comparing row items.
   */
  compareBy: prop_types_default.a.string,

  /**
   * Url query parameter compare function operates on
   */
  compareParam: prop_types_default.a.string,

  /**
   * The key for user preferences settings for column visibility.
   */
  columnPrefsKey: prop_types_default.a.string,

  /**
   * The endpoint to use in API calls to populate the table rows and summary.
   * For example, if `taxes` is provided, data will be fetched from the report
   * `taxes` endpoint (ie: `/wc-analytics/reports/taxes` and `/wc/v4/reports/taxes/stats`).
   * If the provided endpoint doesn't exist, an error will be shown to the user
   * with `ReportError`.
   */
  endpoint: prop_types_default.a.string,

  /**
   * Name of the methods available via `select( 'wc-api' )` that will be used to
   * load more data for table items. If omitted, no call will be made and only
   * the data returned by the reports endpoint will be used.
   */
  extendItemsMethodNames: prop_types_default.a.shape({
    getError: prop_types_default.a.string,
    isRequesting: prop_types_default.a.string,
    load: prop_types_default.a.string
  }),

  /**
   * A function that returns the headers object to build the table.
   */
  getHeadersContent: prop_types_default.a.func.isRequired,

  /**
   * A function that returns the rows array to build the table.
   */
  getRowsContent: prop_types_default.a.func.isRequired,

  /**
   * A function that returns the summary object to build the table.
   */
  getSummary: prop_types_default.a.func,

  /**
   * The name of the property in the item object which contains the id.
   */
  itemIdField: prop_types_default.a.string,

  /**
   * Custom labels for table header actions.
   */
  labels: prop_types_default.a.shape({
    compareButton: prop_types_default.a.string,
    downloadButton: prop_types_default.a.string,
    helpText: prop_types_default.a.string,
    placeholder: prop_types_default.a.string
  }),

  /**
   * Primary data of that report. If it's not provided, it will be automatically
   * loaded via the provided `endpoint`.
   */
  primaryData: prop_types_default.a.object,

  /**
   * The string to use as a query parameter when searching row items.
   */
  searchBy: prop_types_default.a.string,

  /**
   * List of fields used for summary numbers. (Reduces queries)
   */
  summaryFields: prop_types_default.a.arrayOf(prop_types_default.a.string),

  /**
   * Table data of that report. If it's not provided, it will be automatically
   * loaded via the provided `endpoint`.
   */
  tableData: prop_types_default.a.object.isRequired,

  /**
   * Properties to be added to the query sent to the report table endpoint.
   */
  tableQuery: prop_types_default.a.object,

  /**
   * String to display as the title of the table.
   */
  title: prop_types_default.a.string.isRequired
};
report_table_ReportTable.defaultProps = {
  primaryData: {},
  tableData: {
    items: {
      data: [],
      totalResults: 0
    },
    query: {}
  },
  tableQuery: {},
  compareParam: 'filter',
  downloadable: false,
  onSearch: external_lodash_["noop"],
  baseSearchQuery: {}
};
/* harmony default export */ var report_table = __webpack_exports__["a"] = (Object(compose["a" /* default */])(Object(with_select["a" /* default */])(function (select, props) {
  var endpoint = props.endpoint,
      getSummary = props.getSummary,
      isRequesting = props.isRequesting,
      itemIdField = props.itemIdField,
      query = props.query,
      tableData = props.tableData,
      tableQuery = props.tableQuery,
      filters = props.filters,
      advancedFilters = props.advancedFilters,
      summaryFields = props.summaryFields;

  if (isRequesting || query.search && !(query[endpoint] && query[endpoint].length)) {
    return {};
  }

  var _select$getSetting = select(external_this_wc_data_["SETTINGS_STORE_NAME"]).getSetting('wc_admin', 'wcAdminSettings'),
      defaultDateRange = _select$getSetting.woocommerce_default_date_range; // Variations and Category charts are powered by the /reports/products/stats endpoint.


  var chartEndpoint = ['variations', 'categories'].includes(endpoint) ? 'products' : endpoint;
  var primaryData = getSummary ? Object(utils["a" /* getReportChartData */])({
    endpoint: chartEndpoint,
    dataType: 'primary',
    query: query,
    select: select,
    filters: filters,
    advancedFilters: advancedFilters,
    tableQuery: tableQuery,
    defaultDateRange: defaultDateRange,
    fields: summaryFields
  }) : {};
  var queriedTableData = tableData || Object(utils["b" /* getReportTableData */])({
    endpoint: endpoint,
    query: query,
    select: select,
    tableQuery: tableQuery,
    filters: filters,
    advancedFilters: advancedFilters,
    defaultDateRange: defaultDateRange
  });
  var extendedTableData = extendTableData(select, props, queriedTableData);
  return {
    primaryData: primaryData,
    ids: itemIdField ? extendedTableData.items.data.map(function (item) {
      return item[itemIdField];
    }) : [],
    tableData: extendedTableData,
    query: report_table_objectSpread(report_table_objectSpread({}, tableQuery), query)
  };
}), Object(external_this_wp_data_["withDispatch"])(function (dispatch) {
  var _dispatch = dispatch('wc-api'),
      initiateReportExport = _dispatch.initiateReportExport;

  return {
    initiateReportExport: initiateReportExport
  };
}))(report_table_ReportTable));

/***/ }),

/***/ 768:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(32);


/**
 * WordPress dependencies
 */

var check = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__[/* SVG */ "b"], {
  xmlns: "http://www.w3.org/2000/svg",
  viewBox: "0 0 24 24"
}, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__[/* Path */ "a"], {
  d: "M9 18.6L3.5 13l1-1L9 16.4l9.5-9.9 1 1z"
}));
/* harmony default export */ __webpack_exports__["a"] = (check);
//# sourceMappingURL=check.js.map

/***/ }),

/***/ 771:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "a", function() { return CheckboxControl; });
/* harmony import */ var _babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(12);
/* harmony import */ var _babel_runtime_helpers_esm_objectWithoutProperties__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(14);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(112);
/* harmony import */ var _wordpress_icons__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(422);
/* harmony import */ var _wordpress_icons__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(768);
/* harmony import */ var _base_control__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(171);




/**
 * WordPress dependencies
 */


/**
 * Internal dependencies
 */


function CheckboxControl(_ref) {
  var label = _ref.label,
      className = _ref.className,
      heading = _ref.heading,
      checked = _ref.checked,
      help = _ref.help,
      onChange = _ref.onChange,
      props = Object(_babel_runtime_helpers_esm_objectWithoutProperties__WEBPACK_IMPORTED_MODULE_1__[/* default */ "a"])(_ref, ["label", "className", "heading", "checked", "help", "onChange"]);

  var instanceId = Object(_wordpress_compose__WEBPACK_IMPORTED_MODULE_3__[/* default */ "a"])(CheckboxControl);
  var id = "inspector-checkbox-control-".concat(instanceId);

  var onChangeValue = function onChangeValue(event) {
    return onChange(event.target.checked);
  };

  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__["createElement"])(_base_control__WEBPACK_IMPORTED_MODULE_6__[/* default */ "a"], {
    label: heading,
    id: id,
    help: help,
    className: className
  }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__["createElement"])("span", {
    className: "components-checkbox-control__input-container"
  }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__["createElement"])("input", Object(_babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_0__[/* default */ "a"])({
    id: id,
    className: "components-checkbox-control__input",
    type: "checkbox",
    value: "1",
    onChange: onChangeValue,
    checked: checked,
    "aria-describedby": !!help ? id + '__help' : undefined
  }, props)), checked ? Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__["createElement"])(_wordpress_icons__WEBPACK_IMPORTED_MODULE_4__[/* default */ "a"], {
    icon: _wordpress_icons__WEBPACK_IMPORTED_MODULE_5__[/* default */ "a"],
    className: "components-checkbox-control__checked",
    role: "presentation"
  }) : null), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__["createElement"])("label", {
    className: "components-checkbox-control__label",
    htmlFor: id
  }, label));
}
//# sourceMappingURL=index.js.map

/***/ }),

/***/ 897:
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ })

}]);
