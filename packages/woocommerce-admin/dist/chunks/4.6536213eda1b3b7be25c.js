(window["webpackJsonp"] = window["webpackJsonp"] || []).push([[4],{

/***/ 265:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "a", function() { return Spinner; });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);

function Spinner() {
  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("span", {
    className: "components-spinner"
  });
}
//# sourceMappingURL=index.js.map

/***/ }),

/***/ 751:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "e", function() { return getRequestByIdString; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "a", function() { return getCategoryLabels; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "b", function() { return getCouponLabels; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "c", function() { return getCustomerLabels; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "d", function() { return getProductLabels; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "f", function() { return getTaxRateLabels; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "g", function() { return getVariationLabels; });
/* harmony import */ var _wordpress_url__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(27);
/* harmony import */ var _wordpress_url__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_url__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(21);
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(2);
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _woocommerce_navigation__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(23);
/* harmony import */ var _woocommerce_navigation__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var analytics_report_taxes_utils__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(752);
/* harmony import */ var wc_api_constants__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(33);
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

/***/ 752:
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

/***/ 754:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";

// UNUSED EXPORTS: ReportChart

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/defineProperty.js
var defineProperty = __webpack_require__(17);
var defineProperty_default = /*#__PURE__*/__webpack_require__.n(defineProperty);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/classCallCheck.js
var classCallCheck = __webpack_require__(38);
var classCallCheck_default = /*#__PURE__*/__webpack_require__.n(classCallCheck);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/createClass.js
var createClass = __webpack_require__(37);
var createClass_default = /*#__PURE__*/__webpack_require__.n(createClass);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/inherits.js
var inherits = __webpack_require__(39);
var inherits_default = /*#__PURE__*/__webpack_require__.n(inherits);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/possibleConstructorReturn.js
var possibleConstructorReturn = __webpack_require__(42);
var possibleConstructorReturn_default = /*#__PURE__*/__webpack_require__.n(possibleConstructorReturn);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/getPrototypeOf.js
var getPrototypeOf = __webpack_require__(26);
var getPrototypeOf_default = /*#__PURE__*/__webpack_require__.n(getPrototypeOf);

// EXTERNAL MODULE: external {"this":["wp","element"]}
var external_this_wp_element_ = __webpack_require__(0);

// EXTERNAL MODULE: external {"this":["wp","i18n"]}
var external_this_wp_i18n_ = __webpack_require__(3);

// EXTERNAL MODULE: ./node_modules/@wordpress/compose/build-module/higher-order/compose.js
var compose = __webpack_require__(169);

// EXTERNAL MODULE: ./node_modules/@wordpress/date/build-module/index.js
var build_module = __webpack_require__(173);

// EXTERNAL MODULE: external "lodash"
var external_lodash_ = __webpack_require__(2);

// EXTERNAL MODULE: ./node_modules/prop-types/index.js
var prop_types = __webpack_require__(1);
var prop_types_default = /*#__PURE__*/__webpack_require__.n(prop_types);

// EXTERNAL MODULE: ./client/lib/date.js
var date = __webpack_require__(108);

// EXTERNAL MODULE: external {"this":["wc","components"]}
var external_this_wc_components_ = __webpack_require__(53);

// EXTERNAL MODULE: external {"this":["wc","data"]}
var external_this_wc_data_ = __webpack_require__(43);

// EXTERNAL MODULE: ./client/lib/currency-context.js
var currency_context = __webpack_require__(200);

// EXTERNAL MODULE: ./client/wc-api/reports/utils.js
var utils = __webpack_require__(750);

// EXTERNAL MODULE: ./client/analytics/components/report-error/index.js
var report_error = __webpack_require__(261);

// EXTERNAL MODULE: ./client/wc-api/with-select.js
var with_select = __webpack_require__(101);

// EXTERNAL MODULE: external {"this":["wc","navigation"]}
var external_this_wc_navigation_ = __webpack_require__(23);

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

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = getPrototypeOf_default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = getPrototypeOf_default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return possibleConstructorReturn_default()(this, result); }; }

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
    return _objectSpread(_objectSpread({}, newProps), {}, {
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
    return _objectSpread(_objectSpread({}, newProps), {}, {
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
  return _objectSpread(_objectSpread({}, newProps), {}, {
    primaryData: primaryData,
    secondaryData: secondaryData
  });
}))(report_chart_ReportChart));

/***/ }),

/***/ 755:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "a", function() { return getSelectedChart; });
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(2);
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_0__);
/**
 * External dependencies
 */

/**
 * Takes a chart name returns the configuration for that chart from and array
 * of charts. If the chart is not found it will return the first chart.
 *
 * @param {string} chartName - the name of the chart to get configuration for
 * @param {Array} charts - list of charts for a particular report
 * @return {Object} - chart configuration object
 */

function getSelectedChart(chartName) {
  var charts = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : [];
  var chart = Object(lodash__WEBPACK_IMPORTED_MODULE_0__["find"])(charts, {
    key: chartName
  });

  if (chart) {
    return chart;
  }

  return charts[0];
}

/***/ }),

/***/ 756:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* unused harmony export ReportSummary */
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(38);
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(37);
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(39);
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(42);
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(26);
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(3);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(169);
/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(1);
/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(prop_types__WEBPACK_IMPORTED_MODULE_8__);
/* harmony import */ var lib_date__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(108);
/* harmony import */ var _woocommerce_navigation__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(23);
/* harmony import */ var _woocommerce_navigation__WEBPACK_IMPORTED_MODULE_10___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_10__);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(53);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_11___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_components__WEBPACK_IMPORTED_MODULE_11__);
/* harmony import */ var _woocommerce_number__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(201);
/* harmony import */ var _woocommerce_number__WEBPACK_IMPORTED_MODULE_12___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_number__WEBPACK_IMPORTED_MODULE_12__);
/* harmony import */ var _woocommerce_data__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(43);
/* harmony import */ var _woocommerce_data__WEBPACK_IMPORTED_MODULE_13___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_data__WEBPACK_IMPORTED_MODULE_13__);
/* harmony import */ var wc_api_reports_utils__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(750);
/* harmony import */ var analytics_components_report_error__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(261);
/* harmony import */ var wc_api_with_select__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__(101);
/* harmony import */ var lib_tracks__WEBPACK_IMPORTED_MODULE_17__ = __webpack_require__(63);
/* harmony import */ var lib_currency_context__WEBPACK_IMPORTED_MODULE_18__ = __webpack_require__(200);







function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_4___default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_4___default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_3___default()(this, result); }; }

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
 * Component to render summary numbers in reports.
 */

var ReportSummary = /*#__PURE__*/function (_Component) {
  _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_2___default()(ReportSummary, _Component);

  var _super = _createSuper(ReportSummary);

  function ReportSummary() {
    _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0___default()(this, ReportSummary);

    return _super.apply(this, arguments);
  }

  _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1___default()(ReportSummary, [{
    key: "formatVal",
    value: function formatVal(val, type) {
      var _this$context = this.context,
          formatCurrency = _this$context.formatCurrency,
          getCurrency = _this$context.getCurrency;
      return type === 'currency' ? formatCurrency(val) : Object(_woocommerce_number__WEBPACK_IMPORTED_MODULE_12__["formatValue"])(getCurrency(), type, val);
    }
  }, {
    key: "getValues",
    value: function getValues(key, type) {
      var _this$props = this.props,
          emptySearchResults = _this$props.emptySearchResults,
          summaryData = _this$props.summaryData;
      var totals = summaryData.totals;
      var primaryValue = emptySearchResults ? 0 : totals.primary[key];
      var secondaryValue = emptySearchResults ? 0 : totals.secondary[key];
      return {
        delta: Object(_woocommerce_number__WEBPACK_IMPORTED_MODULE_12__["calculateDelta"])(primaryValue, secondaryValue),
        prevValue: this.formatVal(secondaryValue, type),
        value: this.formatVal(primaryValue, type)
      };
    }
  }, {
    key: "render",
    value: function render() {
      var _this = this;

      var _this$props2 = this.props,
          charts = _this$props2.charts,
          isRequesting = _this$props2.isRequesting,
          query = _this$props2.query,
          selectedChart = _this$props2.selectedChart,
          summaryData = _this$props2.summaryData,
          endpoint = _this$props2.endpoint,
          report = _this$props2.report,
          defaultDateRange = _this$props2.defaultDateRange;
      var isError = summaryData.isError,
          isSummaryDataRequesting = summaryData.isRequesting;

      if (isError) {
        return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(analytics_components_report_error__WEBPACK_IMPORTED_MODULE_15__[/* default */ "a"], {
          isError: true
        });
      }

      if (isRequesting || isSummaryDataRequesting) {
        return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_11__["SummaryListPlaceholder"], {
          numberOfItems: charts.length
        });
      }

      var _getDateParamsFromQue = Object(lib_date__WEBPACK_IMPORTED_MODULE_9__[/* getDateParamsFromQuery */ "h"])(query, defaultDateRange),
          compare = _getDateParamsFromQue.compare;

      var renderSummaryNumbers = function renderSummaryNumbers(_ref) {
        var onToggle = _ref.onToggle;
        return charts.map(function (chart) {
          var key = chart.key,
              order = chart.order,
              orderby = chart.orderby,
              label = chart.label,
              type = chart.type;
          var newPath = {
            chart: key
          };

          if (orderby) {
            newPath.orderby = orderby;
          }

          if (order) {
            newPath.order = order;
          }

          var href = Object(_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_10__["getNewPath"])(newPath);
          var isSelected = selectedChart.key === key;

          var _this$getValues = _this.getValues(key, type),
              delta = _this$getValues.delta,
              prevValue = _this$getValues.prevValue,
              value = _this$getValues.value;

          return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_11__["SummaryNumber"], {
            key: key,
            delta: delta,
            href: href,
            label: label,
            prevLabel: compare === 'previous_period' ? Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__["__"])('Previous Period:', 'woocommerce') : Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__["__"])('Previous Year:', 'woocommerce'),
            prevValue: prevValue,
            selected: isSelected,
            value: value,
            onLinkClickCallback: function onLinkClickCallback() {
              // Wider than a certain breakpoint, there is no dropdown so avoid calling onToggle.
              if (onToggle) {
                onToggle();
              }

              Object(lib_tracks__WEBPACK_IMPORTED_MODULE_17__[/* recordEvent */ "b"])('analytics_chart_tab_click', {
                report: report || endpoint,
                key: key
              });
            }
          });
        });
      };

      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_11__["SummaryList"], null, renderSummaryNumbers);
    }
  }]);

  return ReportSummary;
}(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["Component"]);
ReportSummary.propTypes = {
  /**
   * Properties of all the charts available for that report.
   */
  charts: prop_types__WEBPACK_IMPORTED_MODULE_8___default.a.array.isRequired,

  /**
   * The endpoint to use in API calls to populate the Summary Numbers.
   * For example, if `taxes` is provided, data will be fetched from the report
   * `taxes` endpoint (ie: `/wc-analytics/reports/taxes/stats`). If the provided endpoint
   * doesn't exist, an error will be shown to the user with `ReportError`.
   */
  endpoint: prop_types__WEBPACK_IMPORTED_MODULE_8___default.a.string.isRequired,

  /**
   * Allows specifying properties different from the `endpoint` that will be used
   * to limit the items when there is an active search.
   */
  limitProperties: prop_types__WEBPACK_IMPORTED_MODULE_8___default.a.array,

  /**
   * The query string represented in object form.
   */
  query: prop_types__WEBPACK_IMPORTED_MODULE_8___default.a.object.isRequired,

  /**
   * Whether there is an API call running.
   */
  isRequesting: prop_types__WEBPACK_IMPORTED_MODULE_8___default.a.bool,

  /**
   * Properties of the selected chart.
   */
  selectedChart: prop_types__WEBPACK_IMPORTED_MODULE_8___default.a.shape({
    /**
     * Key of the selected chart.
     */
    key: prop_types__WEBPACK_IMPORTED_MODULE_8___default.a.string.isRequired,

    /**
     * Chart label.
     */
    label: prop_types__WEBPACK_IMPORTED_MODULE_8___default.a.string.isRequired,

    /**
     * Order query argument.
     */
    order: prop_types__WEBPACK_IMPORTED_MODULE_8___default.a.oneOf(['asc', 'desc']),

    /**
     * Order by query argument.
     */
    orderby: prop_types__WEBPACK_IMPORTED_MODULE_8___default.a.string,

    /**
     * Number type for formatting.
     */
    type: prop_types__WEBPACK_IMPORTED_MODULE_8___default.a.oneOf(['average', 'number', 'currency']).isRequired
  }).isRequired,

  /**
   * Data to display in the SummaryNumbers.
   */
  summaryData: prop_types__WEBPACK_IMPORTED_MODULE_8___default.a.object,

  /**
   * Report name, if different than the endpoint.
   */
  report: prop_types__WEBPACK_IMPORTED_MODULE_8___default.a.string
};
ReportSummary.defaultProps = {
  summaryData: {
    totals: {
      primary: {},
      secondary: {}
    },
    isError: false,
    isRequesting: false
  }
};
ReportSummary.contextType = lib_currency_context__WEBPACK_IMPORTED_MODULE_18__[/* CurrencyContext */ "a"];
/* harmony default export */ __webpack_exports__["a"] = (Object(_wordpress_compose__WEBPACK_IMPORTED_MODULE_7__[/* default */ "a"])(Object(wc_api_with_select__WEBPACK_IMPORTED_MODULE_16__[/* default */ "a"])(function (select, props) {
  var charts = props.charts,
      endpoint = props.endpoint,
      isRequesting = props.isRequesting,
      limitProperties = props.limitProperties,
      query = props.query,
      filters = props.filters,
      advancedFilters = props.advancedFilters;
  var limitBy = limitProperties || [endpoint];

  if (isRequesting) {
    return {};
  }

  var hasLimitByParam = limitBy.some(function (item) {
    return query[item] && query[item].length;
  });

  if (query.search && !hasLimitByParam) {
    return {
      emptySearchResults: true
    };
  }

  var fields = charts && charts.map(function (chart) {
    return chart.key;
  });

  var _select$getSetting = select(_woocommerce_data__WEBPACK_IMPORTED_MODULE_13__["SETTINGS_STORE_NAME"]).getSetting('wc_admin', 'wcAdminSettings'),
      defaultDateRange = _select$getSetting.woocommerce_default_date_range;

  var summaryData = Object(wc_api_reports_utils__WEBPACK_IMPORTED_MODULE_14__[/* getSummaryNumbers */ "d"])({
    endpoint: endpoint,
    query: query,
    select: select,
    limitBy: limitBy,
    filters: filters,
    advancedFilters: advancedFilters,
    defaultDateRange: defaultDateRange,
    fields: fields
  });
  return {
    summaryData: summaryData,
    defaultDateRange: defaultDateRange
  };
}))(ReportSummary));

/***/ }),

/***/ 789:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(38);
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(37);
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(62);
/* harmony import */ var _babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(39);
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(42);
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(26);
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(3);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(169);
/* harmony import */ var _wordpress_html_entities__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(72);
/* harmony import */ var _wordpress_html_entities__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(_wordpress_html_entities__WEBPACK_IMPORTED_MODULE_9__);
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(2);
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_10___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_10__);
/* harmony import */ var _woocommerce_navigation__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(23);
/* harmony import */ var _woocommerce_navigation__WEBPACK_IMPORTED_MODULE_11___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_11__);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(53);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_12___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_components__WEBPACK_IMPORTED_MODULE_12__);
/* harmony import */ var _woocommerce_number__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(201);
/* harmony import */ var _woocommerce_number__WEBPACK_IMPORTED_MODULE_13___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_number__WEBPACK_IMPORTED_MODULE_13__);
/* harmony import */ var _woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(22);
/* harmony import */ var _categories_breadcrumbs__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(790);
/* harmony import */ var _utils__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__(791);
/* harmony import */ var analytics_components_report_table__WEBPACK_IMPORTED_MODULE_17__ = __webpack_require__(759);
/* harmony import */ var wc_api_with_select__WEBPACK_IMPORTED_MODULE_18__ = __webpack_require__(101);
/* harmony import */ var lib_currency_context__WEBPACK_IMPORTED_MODULE_19__ = __webpack_require__(200);
/* harmony import */ var _style_scss__WEBPACK_IMPORTED_MODULE_20__ = __webpack_require__(898);
/* harmony import */ var _style_scss__WEBPACK_IMPORTED_MODULE_20___default = /*#__PURE__*/__webpack_require__.n(_style_scss__WEBPACK_IMPORTED_MODULE_20__);








function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_5___default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_5___default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_4___default()(this, result); }; }

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







var manageStock = Object(_woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_14__[/* getSetting */ "g"])('manageStock', 'no');
var stockStatuses = Object(_woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_14__[/* getSetting */ "g"])('stockStatuses', {});

var ProductsReportTable = /*#__PURE__*/function (_Component) {
  _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_3___default()(ProductsReportTable, _Component);

  var _super = _createSuper(ProductsReportTable);

  function ProductsReportTable() {
    var _this;

    _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0___default()(this, ProductsReportTable);

    _this = _super.call(this);
    _this.getHeadersContent = _this.getHeadersContent.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_2___default()(_this));
    _this.getRowsContent = _this.getRowsContent.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_2___default()(_this));
    _this.getSummary = _this.getSummary.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_2___default()(_this));
    return _this;
  }

  _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1___default()(ProductsReportTable, [{
    key: "getHeadersContent",
    value: function getHeadersContent() {
      return [{
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__["__"])('Product Title', 'woocommerce'),
        key: 'product_name',
        required: true,
        isLeftAligned: true,
        isSortable: true
      }, {
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__["__"])('SKU', 'woocommerce'),
        key: 'sku',
        hiddenByDefault: true,
        isSortable: true
      }, {
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__["__"])('Items Sold', 'woocommerce'),
        key: 'items_sold',
        required: true,
        defaultSort: true,
        isSortable: true,
        isNumeric: true
      }, {
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__["__"])('Net Sales', 'woocommerce'),
        screenReaderLabel: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__["__"])('Net Sales', 'woocommerce'),
        key: 'net_revenue',
        required: true,
        isSortable: true,
        isNumeric: true
      }, {
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__["__"])('Orders', 'woocommerce'),
        key: 'orders_count',
        isSortable: true,
        isNumeric: true
      }, {
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__["__"])('Category', 'woocommerce'),
        key: 'product_cat'
      }, {
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__["__"])('Variations', 'woocommerce'),
        key: 'variations',
        isSortable: true
      }, manageStock === 'yes' ? {
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__["__"])('Status', 'woocommerce'),
        key: 'stock_status'
      } : null, manageStock === 'yes' ? {
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__["__"])('Stock', 'woocommerce'),
        key: 'stock',
        isNumeric: true
      } : null].filter(Boolean);
    }
  }, {
    key: "getRowsContent",
    value: function getRowsContent() {
      var _this2 = this;

      var data = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : [];
      var query = this.props.query;
      var persistedQuery = Object(_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_11__["getPersistedQuery"])(query);
      var _this$context = this.context,
          renderCurrency = _this$context.render,
          getCurrencyFormatDecimal = _this$context.formatDecimal,
          getCurrency = _this$context.getCurrency;
      var currency = getCurrency();
      return Object(lodash__WEBPACK_IMPORTED_MODULE_10__["map"])(data, function (row) {
        var productId = row.product_id,
            itemsSold = row.items_sold,
            netRevenue = row.net_revenue,
            ordersCount = row.orders_count;
        var extendedInfo = row.extended_info || {};
        var categoryIds = extendedInfo.category_ids,
            lowStockAmount = extendedInfo.low_stock_amount,
            extendedInfoManageStock = extendedInfo.manage_stock,
            sku = extendedInfo.sku,
            extendedInfoStockStatus = extendedInfo.stock_status,
            stockQuantity = extendedInfo.stock_quantity,
            _extendedInfo$variati = extendedInfo.variations,
            variations = _extendedInfo$variati === void 0 ? [] : _extendedInfo$variati;
        var name = Object(_wordpress_html_entities__WEBPACK_IMPORTED_MODULE_9__["decodeEntities"])(extendedInfo.name);
        var ordersLink = Object(_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_11__["getNewPath"])(persistedQuery, '/analytics/orders', {
          filter: 'advanced',
          product_includes: productId
        });
        var productDetailLink = Object(_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_11__["getNewPath"])(persistedQuery, '/analytics/products', {
          filter: 'single_product',
          products: productId
        });
        var categories = _this2.props.categories;
        var productCategories = categoryIds && categoryIds.map(function (categoryId) {
          return categories.get(categoryId);
        }).filter(Boolean) || [];
        var stockStatus = Object(_utils__WEBPACK_IMPORTED_MODULE_16__[/* isLowStock */ "a"])(extendedInfoStockStatus, stockQuantity, lowStockAmount) ? Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_12__["Link"], {
          href: Object(_woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_14__[/* getAdminLink */ "f"])('post.php?action=edit&post=' + productId),
          type: "wp-admin"
        }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__["_x"])('Low', 'Indication of a low quantity', 'woocommerce')) : stockStatuses[extendedInfoStockStatus];
        return [{
          display: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_12__["Link"], {
            href: productDetailLink,
            type: "wc-admin"
          }, name),
          value: name
        }, {
          display: sku,
          value: sku
        }, {
          display: Object(_woocommerce_number__WEBPACK_IMPORTED_MODULE_13__["formatValue"])(currency, 'number', itemsSold),
          value: itemsSold
        }, {
          display: renderCurrency(netRevenue),
          value: getCurrencyFormatDecimal(netRevenue)
        }, {
          display: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_12__["Link"], {
            href: ordersLink,
            type: "wc-admin"
          }, ordersCount),
          value: ordersCount
        }, {
          display: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("div", {
            className: "woocommerce-table__product-categories"
          }, productCategories[0] && Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])(_categories_breadcrumbs__WEBPACK_IMPORTED_MODULE_15__[/* default */ "a"], {
            category: productCategories[0],
            categories: categories
          }), productCategories.length > 1 && Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_12__["Tag"], {
            label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__["sprintf"])(Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__["_x"])('+%d more', 'categories', 'woocommerce'), productCategories.length - 1),
            popoverContents: productCategories.map(function (category) {
              return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])(_categories_breadcrumbs__WEBPACK_IMPORTED_MODULE_15__[/* default */ "a"], {
                category: category,
                categories: categories,
                key: category.id,
                query: query
              });
            })
          })),
          value: productCategories.map(function (category) {
            return category.name;
          }).join(', ')
        }, {
          display: Object(_woocommerce_number__WEBPACK_IMPORTED_MODULE_13__["formatValue"])(currency, 'number', variations.length),
          value: variations.length
        }, manageStock === 'yes' ? {
          display: extendedInfoManageStock ? stockStatus : Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__["__"])('N/A', 'woocommerce'),
          value: extendedInfoManageStock ? stockStatuses[extendedInfoStockStatus] : null
        } : null, manageStock === 'yes' ? {
          display: extendedInfoManageStock ? Object(_woocommerce_number__WEBPACK_IMPORTED_MODULE_13__["formatValue"])(currency, 'number', stockQuantity) : Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__["__"])('N/A', 'woocommerce'),
          value: stockQuantity
        } : null].filter(Boolean);
      });
    }
  }, {
    key: "getSummary",
    value: function getSummary(totals) {
      var _totals$products_coun = totals.products_count,
          productsCount = _totals$products_coun === void 0 ? 0 : _totals$products_coun,
          _totals$items_sold = totals.items_sold,
          itemsSold = _totals$items_sold === void 0 ? 0 : _totals$items_sold,
          _totals$net_revenue = totals.net_revenue,
          netRevenue = _totals$net_revenue === void 0 ? 0 : _totals$net_revenue,
          _totals$orders_count = totals.orders_count,
          ordersCount = _totals$orders_count === void 0 ? 0 : _totals$orders_count;
      var _this$context2 = this.context,
          formatCurrency = _this$context2.formatCurrency,
          getCurrency = _this$context2.getCurrency;
      var currency = getCurrency();
      return [{
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__["_n"])('product', 'products', productsCount, 'woocommerce'),
        value: Object(_woocommerce_number__WEBPACK_IMPORTED_MODULE_13__["formatValue"])(currency, 'number', productsCount)
      }, {
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__["_n"])('item sold', 'items sold', itemsSold, 'woocommerce'),
        value: Object(_woocommerce_number__WEBPACK_IMPORTED_MODULE_13__["formatValue"])(currency, 'number', itemsSold)
      }, {
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__["__"])('net sales', 'woocommerce'),
        value: formatCurrency(netRevenue)
      }, {
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__["_n"])('orders', 'orders', ordersCount, 'woocommerce'),
        value: Object(_woocommerce_number__WEBPACK_IMPORTED_MODULE_13__["formatValue"])(currency, 'number', ordersCount)
      }];
    }
  }, {
    key: "render",
    value: function render() {
      var _this$props = this.props,
          advancedFilters = _this$props.advancedFilters,
          baseSearchQuery = _this$props.baseSearchQuery,
          filters = _this$props.filters,
          hideCompare = _this$props.hideCompare,
          isRequesting = _this$props.isRequesting,
          query = _this$props.query;
      var labels = {
        helpText: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__["__"])('Check at least two products below to compare', 'woocommerce'),
        placeholder: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__["__"])('Search by product name or SKU', 'woocommerce')
      };
      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])(analytics_components_report_table__WEBPACK_IMPORTED_MODULE_17__[/* default */ "a"], {
        compareBy: hideCompare ? undefined : 'products',
        endpoint: "products",
        getHeadersContent: this.getHeadersContent,
        getRowsContent: this.getRowsContent,
        getSummary: this.getSummary,
        summaryFields: ['products_count', 'items_sold', 'net_revenue', 'orders_count'],
        itemIdField: "product_id",
        isRequesting: isRequesting,
        labels: labels,
        query: query,
        searchBy: "products",
        baseSearchQuery: baseSearchQuery,
        tableQuery: {
          orderby: query.orderby || 'items_sold',
          order: query.order || 'desc',
          extended_info: true,
          segmentby: query.segmentby
        },
        title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__["__"])('Products', 'woocommerce'),
        columnPrefsKey: "products_report_columns",
        filters: filters,
        advancedFilters: advancedFilters
      });
    }
  }]);

  return ProductsReportTable;
}(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["Component"]);

ProductsReportTable.contextType = lib_currency_context__WEBPACK_IMPORTED_MODULE_19__[/* CurrencyContext */ "a"];
/* harmony default export */ __webpack_exports__["a"] = (Object(_wordpress_compose__WEBPACK_IMPORTED_MODULE_8__[/* default */ "a"])(Object(wc_api_with_select__WEBPACK_IMPORTED_MODULE_18__[/* default */ "a"])(function (select, props) {
  var query = props.query,
      isRequesting = props.isRequesting;

  if (isRequesting || query.search && !(query.products && query.products.length)) {
    return {};
  }

  var _select = select('wc-api'),
      getItems = _select.getItems,
      getItemsError = _select.getItemsError,
      isGetItemsRequesting = _select.isGetItemsRequesting;

  var tableQuery = {
    per_page: -1
  };
  var categories = getItems('categories', tableQuery);
  var isError = Boolean(getItemsError('categories', tableQuery));
  var isLoading = isGetItemsRequesting('categories', tableQuery);
  return {
    categories: categories,
    isError: isError,
    isRequesting: isLoading
  };
}))(ProductsReportTable));

/***/ }),

/***/ 790:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "a", function() { return CategoryBreadcrumbs; });
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(38);
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(37);
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(39);
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(42);
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(26);
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(2);
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(265);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(53);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_components__WEBPACK_IMPORTED_MODULE_8__);
/* harmony import */ var _woocommerce_navigation__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(23);
/* harmony import */ var _woocommerce_navigation__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_9__);







function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_4___default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_4___default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_3___default()(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/**
 * External dependencies
 */



/**
 * WooCommerce dependencies
 */




var CategoryBreadcrumbs = /*#__PURE__*/function (_Component) {
  _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_2___default()(CategoryBreadcrumbs, _Component);

  var _super = _createSuper(CategoryBreadcrumbs);

  function CategoryBreadcrumbs() {
    _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0___default()(this, CategoryBreadcrumbs);

    return _super.apply(this, arguments);
  }

  _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1___default()(CategoryBreadcrumbs, [{
    key: "getCategoryAncestorIds",
    value: function getCategoryAncestorIds(category, categories) {
      var ancestors = [];
      var parent = category.parent;

      while (parent) {
        ancestors.unshift(parent);
        parent = categories.get(parent).parent;
      }

      return ancestors;
    }
  }, {
    key: "getCategoryAncestors",
    value: function getCategoryAncestors(category, categories) {
      var ancestorIds = this.getCategoryAncestorIds(category, categories);

      if (!ancestorIds.length) {
        return;
      }

      if (ancestorIds.length === 1) {
        return categories.get(Object(lodash__WEBPACK_IMPORTED_MODULE_6__["first"])(ancestorIds)).name + ' › ';
      }

      if (ancestorIds.length === 2) {
        return categories.get(Object(lodash__WEBPACK_IMPORTED_MODULE_6__["first"])(ancestorIds)).name + ' › ' + categories.get(Object(lodash__WEBPACK_IMPORTED_MODULE_6__["last"])(ancestorIds)).name + ' › ';
      }

      return categories.get(Object(lodash__WEBPACK_IMPORTED_MODULE_6__["first"])(ancestorIds)).name + ' … ' + categories.get(Object(lodash__WEBPACK_IMPORTED_MODULE_6__["last"])(ancestorIds)).name + ' › ';
    }
  }, {
    key: "render",
    value: function render() {
      var _this$props = this.props,
          categories = _this$props.categories,
          category = _this$props.category,
          query = _this$props.query;
      var persistedQuery = Object(_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_9__["getPersistedQuery"])(query);
      return category ? Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])("div", {
        className: "woocommerce-table__breadcrumbs"
      }, this.getCategoryAncestors(category, categories), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_8__["Link"], {
        href: Object(_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_9__["getNewPath"])(persistedQuery, '/analytics/categories', {
          filter: 'single_category',
          categories: category.id
        }),
        type: "wc-admin"
      }, category.name)) : Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_7__[/* default */ "a"], null);
    }
  }]);

  return CategoryBreadcrumbs;
}(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["Component"]);



/***/ }),

/***/ 791:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "a", function() { return isLowStock; });
/**
 * Determine if a product or variation is in low stock.
 *
 * @param {number} threshold - The number at which stock is determined to be low.
 * @return {boolean} - Whether or not the stock is low.
 */
function isLowStock(status, quantity, threshold) {
  if (!quantity) {
    // Sites that don't do inventory tracking will always return false.
    return false;
  }

  return status && quantity <= threshold === 'instock';
}

/***/ }),

/***/ 898:
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ })

}]);
