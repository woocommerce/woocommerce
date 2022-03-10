(window["__wcAdmin_webpackJsonp"] = window["__wcAdmin_webpackJsonp"] || []).push([[27],{

/***/ 44:
/***/ (function(module, exports) {

function _defineProperty(obj, key, value) {
  if (key in obj) {
    Object.defineProperty(obj, key, {
      value: value,
      enumerable: true,
      configurable: true,
      writable: true
    });
  } else {
    obj[key] = value;
  }

  return obj;
}

module.exports = _defineProperty, module.exports.__esModule = true, module.exports["default"] = module.exports;

/***/ }),

/***/ 537:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(2);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(1);
/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(prop_types__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(22);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_components__WEBPACK_IMPORTED_MODULE_3__);


/**
 * External dependencies
 */



/**
 * Component to render when there is an error in a report component due to data
 * not being loaded or being invalid.
 *
 * @param {Object} props React props.
 * @param {string} [props.className] Additional class name to style the component.
 */

function ReportError(_ref) {
  let {
    className
  } = _ref;

  const title = Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('There was an error getting your stats. Please try again.', 'woocommerce-admin');

  const actionLabel = Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Reload', 'woocommerce-admin');

  const actionCallback = () => {
    // @todo Add tracking for how often an error is displayed, and the reload action is clicked.
    window.location.reload();
  };

  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_3__["EmptyContent"], {
    className: className,
    title: title,
    actionLabel: actionLabel,
    actionCallback: actionCallback
  });
}

ReportError.propTypes = {
  /**
   * Additional class name to style the component.
   */
  className: prop_types__WEBPACK_IMPORTED_MODULE_2___default.a.string
};
/* harmony default export */ __webpack_exports__["a"] = (ReportError);

/***/ }),

/***/ 538:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "e", function() { return getRequestByIdString; });
/* unused harmony export getAttributeLabels */
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "a", function() { return getCategoryLabels; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "b", function() { return getCouponLabels; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "c", function() { return getCustomerLabels; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "d", function() { return getProductLabels; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "f", function() { return getTaxRateLabels; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "h", function() { return getVariationName; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "g", function() { return getVariationLabels; });
/* harmony import */ var _wordpress_url__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(16);
/* harmony import */ var _wordpress_url__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_url__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(21);
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(5);
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _woocommerce_navigation__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(13);
/* harmony import */ var _woocommerce_navigation__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _woocommerce_data__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(12);
/* harmony import */ var _woocommerce_data__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_data__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _analytics_report_taxes_utils__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(539);
/* harmony import */ var _utils_admin_settings__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(23);
/**
 * External dependencies
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
  let handleData = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : lodash__WEBPACK_IMPORTED_MODULE_2__["identity"];
  return function () {
    let queryString = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : '';
    let query = arguments.length > 1 ? arguments[1] : undefined;
    const pathString = typeof path === 'function' ? path(query) : path;
    const idList = Object(_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_3__["getIdsFromQuery"])(queryString);

    if (idList.length < 1) {
      return Promise.resolve([]);
    }

    const payload = {
      include: idList.join(','),
      per_page: idList.length
    };
    return _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_1___default()({
      path: Object(_wordpress_url__WEBPACK_IMPORTED_MODULE_0__["addQueryArgs"])(pathString, payload)
    }).then(data => data.map(handleData));
  };
}
const getAttributeLabels = getRequestByIdString(_woocommerce_data__WEBPACK_IMPORTED_MODULE_4__["NAMESPACE"] + '/products/attributes', attribute => ({
  key: attribute.id,
  label: attribute.name
}));
const getCategoryLabels = getRequestByIdString(_woocommerce_data__WEBPACK_IMPORTED_MODULE_4__["NAMESPACE"] + '/products/categories', category => ({
  key: category.id,
  label: category.name
}));
const getCouponLabels = getRequestByIdString(_woocommerce_data__WEBPACK_IMPORTED_MODULE_4__["NAMESPACE"] + '/coupons', coupon => ({
  key: coupon.id,
  label: coupon.code
}));
const getCustomerLabels = getRequestByIdString(_woocommerce_data__WEBPACK_IMPORTED_MODULE_4__["NAMESPACE"] + '/customers', customer => ({
  key: customer.id,
  label: customer.name
}));
const getProductLabels = getRequestByIdString(_woocommerce_data__WEBPACK_IMPORTED_MODULE_4__["NAMESPACE"] + '/products', product => ({
  key: product.id,
  label: product.name
}));
const getTaxRateLabels = getRequestByIdString(_woocommerce_data__WEBPACK_IMPORTED_MODULE_4__["NAMESPACE"] + '/taxes', taxRate => ({
  key: taxRate.id,
  label: Object(_analytics_report_taxes_utils__WEBPACK_IMPORTED_MODULE_5__[/* getTaxCode */ "a"])(taxRate)
}));
/**
 * Create a variation name by concatenating each of the variation's
 * attribute option strings.
 *
 * @param {Object} variation - variation returned by the api
 * @param {Array} variation.attributes - attribute objects, with option property.
 * @param {string} variation.name - name of variation.
 * @return {string} - formatted variation name
 */

function getVariationName(_ref) {
  let {
    attributes,
    name
  } = _ref;
  const separator = Object(_utils_admin_settings__WEBPACK_IMPORTED_MODULE_6__[/* getAdminSetting */ "d"])('variationTitleAttributesSeparator', ' - ');

  if (name && name.indexOf(separator) > -1) {
    return name;
  }

  const attributeList = (attributes || []).map(_ref2 => {
    let {
      option
    } = _ref2;
    return option;
  }).join(', ');
  return attributeList ? name + separator + attributeList : name;
}
const getVariationLabels = getRequestByIdString(_ref3 => {
  let {
    products
  } = _ref3;

  // If a product was specified, get just its variations.
  if (products) {
    return _woocommerce_data__WEBPACK_IMPORTED_MODULE_4__["NAMESPACE"] + `/products/${products}/variations`;
  }

  return _woocommerce_data__WEBPACK_IMPORTED_MODULE_4__["NAMESPACE"] + '/variations';
}, variation => {
  return {
    key: variation.id,
    label: getVariationName(variation)
  };
});

/***/ }),

/***/ 539:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "a", function() { return getTaxCode; });
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(2);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__);
/**
 * External dependencies
 */

function getTaxCode(tax) {
  return [tax.country, tax.state, tax.name || Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('TAX', 'woocommerce-admin'), tax.priority].map(item => item.toString().toUpperCase().trim()).filter(Boolean).join('-');
}

/***/ }),

/***/ 540:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";

// UNUSED EXPORTS: ReportChart

// EXTERNAL MODULE: external ["wp","element"]
var external_wp_element_ = __webpack_require__(0);

// EXTERNAL MODULE: external ["wp","i18n"]
var external_wp_i18n_ = __webpack_require__(2);

// EXTERNAL MODULE: external ["wp","compose"]
var external_wp_compose_ = __webpack_require__(14);

// EXTERNAL MODULE: external ["wp","date"]
var external_wp_date_ = __webpack_require__(68);

// EXTERNAL MODULE: external ["wp","data"]
var external_wp_data_ = __webpack_require__(8);

// EXTERNAL MODULE: external "lodash"
var external_lodash_ = __webpack_require__(5);

// EXTERNAL MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/prop-types@15.8.1/node_modules/prop-types/index.js
var prop_types = __webpack_require__(1);
var prop_types_default = /*#__PURE__*/__webpack_require__.n(prop_types);

// EXTERNAL MODULE: external ["wc","components"]
var external_wc_components_ = __webpack_require__(22);

// EXTERNAL MODULE: external ["wc","data"]
var external_wc_data_ = __webpack_require__(12);

// EXTERNAL MODULE: external ["wc","date"]
var external_wc_date_ = __webpack_require__(20);

// EXTERNAL MODULE: ./client/lib/currency-context.js
var currency_context = __webpack_require__(536);

// EXTERNAL MODULE: ./client/analytics/components/report-error/index.js
var report_error = __webpack_require__(537);

// EXTERNAL MODULE: external ["wc","navigation"]
var external_wc_navigation_ = __webpack_require__(13);

// CONCATENATED MODULE: ./client/analytics/components/report-chart/utils.js
/**
 * External dependencies
 */



const DEFAULT_FILTER = 'all';
function getSelectedFilter(filters, query) {
  let selectedFilterArgs = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : {};

  if (!filters || filters.length === 0) {
    return null;
  }

  const clonedFilters = filters.slice(0);
  const filterConfig = clonedFilters.pop();

  if (filterConfig.showFilters(query, selectedFilterArgs)) {
    const allFilters = Object(external_wc_navigation_["flattenFilters"])(filterConfig.filters);
    const value = query[filterConfig.param] || filterConfig.defaultValue || DEFAULT_FILTER;
    return Object(external_lodash_["find"])(allFilters, {
      value
    });
  }

  return getSelectedFilter(clonedFilters, query, selectedFilterArgs);
}
function getChartMode(selectedFilter, query) {
  if (selectedFilter && query) {
    const selectedFilterParam = Object(external_lodash_["get"])(selectedFilter, ['settings', 'param']);

    if (!selectedFilterParam || Object.keys(query).includes(selectedFilterParam)) {
      return Object(external_lodash_["get"])(selectedFilter, ['chartMode']);
    }
  }

  return null;
}
function createDateFormatter(format) {
  return date => Object(external_wp_date_["format"])(format, date);
}
// CONCATENATED MODULE: ./client/analytics/components/report-chart/index.js


/**
 * External dependencies
 */










/**
 * Internal dependencies
 */




/**
 * Component that renders the chart in reports.
 */

class report_chart_ReportChart extends external_wp_element_["Component"] {
  shouldComponentUpdate(nextProps) {
    if (nextProps.isRequesting !== this.props.isRequesting || nextProps.primaryData.isRequesting !== this.props.primaryData.isRequesting || nextProps.secondaryData.isRequesting !== this.props.secondaryData.isRequesting || !Object(external_lodash_["isEqual"])(nextProps.query, this.props.query)) {
      return true;
    }

    return false;
  }

  getItemChartData() {
    const {
      primaryData,
      selectedChart
    } = this.props;
    const chartData = primaryData.data.intervals.map(function (interval) {
      const intervalData = {};
      interval.subtotals.segments.forEach(function (segment) {
        if (segment.segment_label) {
          const label = intervalData[segment.segment_label] ? segment.segment_label + ' (#' + segment.segment_id + ')' : segment.segment_label;
          intervalData[segment.segment_id] = {
            label,
            value: segment.subtotals[selectedChart.key] || 0
          };
        }
      });
      return {
        date: Object(external_wp_date_["format"])('Y-m-d\\TH:i:s', interval.date_start),
        ...intervalData
      };
    });
    return chartData;
  }

  getTimeChartData() {
    const {
      query,
      primaryData,
      secondaryData,
      selectedChart,
      defaultDateRange
    } = this.props;
    const currentInterval = Object(external_wc_date_["getIntervalForQuery"])(query, defaultDateRange);
    const {
      primary,
      secondary
    } = Object(external_wc_date_["getCurrentDates"])(query, defaultDateRange);
    const chartData = primaryData.data.intervals.map(function (interval, index) {
      const secondaryDate = Object(external_wc_date_["getPreviousDate"])(interval.date_start, primary.after, secondary.after, query.compare, currentInterval);
      const secondaryInterval = secondaryData.data.intervals[index];
      return {
        date: Object(external_wp_date_["format"])('Y-m-d\\TH:i:s', interval.date_start),
        primary: {
          label: `${primary.label} (${primary.range})`,
          labelDate: interval.date_start,
          value: interval.subtotals[selectedChart.key] || 0
        },
        secondary: {
          label: `${secondary.label} (${secondary.range})`,
          labelDate: secondaryDate.format('YYYY-MM-DD HH:mm:ss'),
          value: secondaryInterval && secondaryInterval.subtotals[selectedChart.key] || 0
        }
      };
    });
    return chartData;
  }

  getTimeChartTotals() {
    const {
      primaryData,
      secondaryData,
      selectedChart
    } = this.props;
    return {
      primary: Object(external_lodash_["get"])(primaryData, ['data', 'totals', selectedChart.key], null),
      secondary: Object(external_lodash_["get"])(secondaryData, ['data', 'totals', selectedChart.key], null)
    };
  }

  renderChart(mode, isRequesting, chartData, legendTotals) {
    const {
      emptySearchResults,
      filterParam,
      interactiveLegend,
      itemsLabel,
      legendPosition,
      path,
      query,
      selectedChart,
      showHeaderControls,
      primaryData,
      defaultDateRange
    } = this.props;
    const currentInterval = Object(external_wc_date_["getIntervalForQuery"])(query, defaultDateRange);
    const allowedIntervals = Object(external_wc_date_["getAllowedIntervalsForQuery"])(query, defaultDateRange);
    const formats = Object(external_wc_date_["getDateFormatsForInterval"])(currentInterval, primaryData.data.intervals.length, {
      type: 'php'
    });
    const emptyMessage = emptySearchResults ? Object(external_wp_i18n_["__"])('No data for the current search', 'woocommerce-admin') : Object(external_wp_i18n_["__"])('No data for the selected date range', 'woocommerce-admin');
    const {
      formatAmount,
      getCurrencyConfig
    } = this.context;
    return Object(external_wp_element_["createElement"])(external_wc_components_["Chart"], {
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
      screenReaderFormat: createDateFormatter(formats.screenReaderFormat),
      showHeaderControls: showHeaderControls,
      title: selectedChart.label,
      tooltipLabelFormat: createDateFormatter(formats.tooltipLabelFormat),
      tooltipTitle: mode === 'time-comparison' && selectedChart.label || null,
      tooltipValueFormat: Object(external_wc_data_["getTooltipValueFormat"])(selectedChart.type, formatAmount),
      chartType: Object(external_wc_date_["getChartTypeForQuery"])(query),
      valueType: selectedChart.type,
      xFormat: createDateFormatter(formats.xFormat),
      x2Format: createDateFormatter(formats.x2Format),
      currency: getCurrencyConfig()
    });
  }

  renderItemComparison() {
    const {
      isRequesting,
      primaryData
    } = this.props;

    if (primaryData.isError) {
      return Object(external_wp_element_["createElement"])(report_error["a" /* default */], null);
    }

    const isChartRequesting = isRequesting || primaryData.isRequesting;
    const chartData = this.getItemChartData();
    return this.renderChart('item-comparison', isChartRequesting, chartData);
  }

  renderTimeComparison() {
    const {
      isRequesting,
      primaryData,
      secondaryData
    } = this.props;

    if (!primaryData || primaryData.isError || secondaryData.isError) {
      return Object(external_wp_element_["createElement"])(report_error["a" /* default */], null);
    }

    const isChartRequesting = isRequesting || primaryData.isRequesting || secondaryData.isRequesting;
    const chartData = this.getTimeChartData();
    const legendTotals = this.getTimeChartTotals();
    return this.renderChart('time-comparison', isChartRequesting, chartData, legendTotals);
  }

  render() {
    const {
      mode
    } = this.props;

    if (mode === 'item-comparison') {
      return this.renderItemComparison();
    }

    return this.renderTimeComparison();
  }

}
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
/* harmony default export */ var report_chart = __webpack_exports__["a"] = (Object(external_wp_compose_["compose"])(Object(external_wp_data_["withSelect"])((select, props) => {
  const {
    charts,
    endpoint,
    filters,
    isRequesting,
    limitProperties,
    query,
    advancedFilters
  } = props;
  const limitBy = limitProperties || [endpoint];
  const selectedFilter = getSelectedFilter(filters, query);
  const filterParam = Object(external_lodash_["get"])(selectedFilter, ['settings', 'param']);
  const chartMode = props.mode || getChartMode(selectedFilter, query) || 'time-comparison';
  const {
    woocommerce_default_date_range: defaultDateRange
  } = select(external_wc_data_["SETTINGS_STORE_NAME"]).getSetting('wc_admin', 'wcAdminSettings');
  /* eslint @wordpress/no-unused-vars-before-return: "off" */

  const reportStoreSelector = select(external_wc_data_["REPORTS_STORE_NAME"]);
  const newProps = {
    mode: chartMode,
    filterParam,
    defaultDateRange
  };

  if (isRequesting) {
    return newProps;
  }

  const hasLimitByParam = limitBy.some(item => query[item] && query[item].length);

  if (query.search && !hasLimitByParam) {
    return { ...newProps,
      emptySearchResults: true
    };
  }

  const fields = charts && charts.map(chart => chart.key);
  const primaryData = Object(external_wc_data_["getReportChartData"])({
    endpoint,
    dataType: 'primary',
    query,
    selector: reportStoreSelector,
    limitBy,
    filters,
    advancedFilters,
    defaultDateRange,
    fields
  });

  if (chartMode === 'item-comparison') {
    return { ...newProps,
      primaryData
    };
  }

  const secondaryData = Object(external_wc_data_["getReportChartData"])({
    endpoint,
    dataType: 'secondary',
    query,
    selector: reportStoreSelector,
    limitBy,
    filters,
    advancedFilters,
    defaultDateRange,
    fields
  });
  return { ...newProps,
    primaryData,
    secondaryData
  };
}))(report_chart_ReportChart));

/***/ }),

/***/ 562:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "b", function() { return charts; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "a", function() { return advancedFilters; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "c", function() { return filters; });
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(2);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_hooks__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(27);
/* harmony import */ var _wordpress_hooks__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_hooks__WEBPACK_IMPORTED_MODULE_1__);
/**
 * External dependencies
 */


const REVENUE_REPORT_CHARTS_FILTER = 'woocommerce_admin_revenue_report_charts';
const REVENUE_REPORT_FILTERS_FILTER = 'woocommerce_admin_revenue_report_filters';
const REVENUE_REPORT_ADVANCED_FILTERS_FILTER = 'woocommerce_admin_revenue_report_advanced_filters';
/**
 * @typedef {import('../index.js').chart} chart
 */

/**
 * Revenue Report charts filter.
 *
 * @filter woocommerce_admin_revenue_report_charts
 * @param {Array.<chart>} charts Report charts.
 */

const charts = Object(_wordpress_hooks__WEBPACK_IMPORTED_MODULE_1__["applyFilters"])(REVENUE_REPORT_CHARTS_FILTER, [{
  key: 'gross_sales',
  label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Gross sales', 'woocommerce-admin'),
  order: 'desc',
  orderby: 'gross_sales',
  type: 'currency',
  isReverseTrend: false
}, {
  key: 'refunds',
  label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Returns', 'woocommerce-admin'),
  order: 'desc',
  orderby: 'refunds',
  type: 'currency',
  isReverseTrend: true
}, {
  key: 'coupons',
  label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Coupons', 'woocommerce-admin'),
  order: 'desc',
  orderby: 'coupons',
  type: 'currency',
  isReverseTrend: false
}, {
  key: 'net_revenue',
  label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Net sales', 'woocommerce-admin'),
  orderby: 'net_revenue',
  type: 'currency',
  isReverseTrend: false,
  labelTooltipText: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Full refunds are not deducted from tax or net sales totals', 'woocommerce-admin')
}, {
  key: 'taxes',
  label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Taxes', 'woocommerce-admin'),
  order: 'desc',
  orderby: 'taxes',
  type: 'currency',
  isReverseTrend: false,
  labelTooltipText: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Full refunds are not deducted from tax or net sales totals', 'woocommerce-admin')
}, {
  key: 'shipping',
  label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Shipping', 'woocommerce-admin'),
  orderby: 'shipping',
  type: 'currency',
  isReverseTrend: false
}, {
  key: 'total_sales',
  label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Total sales', 'woocommerce-admin'),
  order: 'desc',
  orderby: 'total_sales',
  type: 'currency',
  isReverseTrend: false
}]);
/**
 * Revenue Report Advanced Filters.
 *
 * @filter woocommerce_admin_revenue_report_advanced_filters
 * @param {Object} advancedFilters Report Advanced Filters.
 * @param {string} advancedFilters.title Interpolated component string for Advanced Filters title.
 * @param {Object} advancedFilters.filters An object specifying a report's Advanced Filters.
 */

const advancedFilters = Object(_wordpress_hooks__WEBPACK_IMPORTED_MODULE_1__["applyFilters"])(REVENUE_REPORT_ADVANCED_FILTERS_FILTER, {
  filters: {},
  title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["_x"])('Revenue Matches {{select /}} Filters', 'A sentence describing filters for Revenue. See screen shot for context: https://cloudup.com/cSsUY9VeCVJ', 'woocommerce-admin')
});
const filterValues = [];

if (Object.keys(advancedFilters.filters).length) {
  filterValues.push({
    label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('All Revenue', 'woocommerce-admin'),
    value: 'all'
  });
  filterValues.push({
    label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Advanced Filters', 'woocommerce-admin'),
    value: 'advanced'
  });
}
/**
 * @typedef {import('../index.js').filter} filter
 */

/**
 * Revenue Report Filters.
 *
 * @filter woocommerce_admin_revenue_report_filters
 * @param {Array.<filter>} filters Report filters.
 */


const filters = Object(_wordpress_hooks__WEBPACK_IMPORTED_MODULE_1__["applyFilters"])(REVENUE_REPORT_FILTERS_FILTER, [{
  label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Show', 'woocommerce-admin'),
  staticParams: ['chartType', 'paged', 'per_page'],
  param: 'filter',
  showFilters: () => filterValues.length > 0,
  filters: filterValues
}]);

/***/ }),

/***/ 563:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "b", function() { return charts; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "a", function() { return advancedFilters; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "c", function() { return filters; });
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(2);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_hooks__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(27);
/* harmony import */ var _wordpress_hooks__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_hooks__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(8);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _lib_async_requests__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(538);
/* harmony import */ var _customer_effort_score_tracks_data_constants__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(69);
/**
 * External dependencies
 */



/**
 * Internal dependencies
 */



const PRODUCTS_REPORT_CHARTS_FILTER = 'woocommerce_admin_products_report_charts';
const PRODUCTS_REPORT_FILTERS_FILTER = 'woocommerce_admin_products_report_filters';
const PRODUCTS_REPORT_ADVANCED_FILTERS_FILTER = 'woocommerce_admin_products_report_advanced_filters';
const {
  addCesSurveyForAnalytics
} = Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_2__["dispatch"])(_customer_effort_score_tracks_data_constants__WEBPACK_IMPORTED_MODULE_4__[/* STORE_KEY */ "c"]);
/**
 * @typedef {import('../index.js').chart} chart
 */

/**
 * Products Report charts filter.
 *
 * @filter woocommerce_admin_products_report_charts
 * @param {Array.<chart>} charts Report charts.
 */

const charts = Object(_wordpress_hooks__WEBPACK_IMPORTED_MODULE_1__["applyFilters"])(PRODUCTS_REPORT_CHARTS_FILTER, [{
  key: 'items_sold',
  label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Items sold', 'woocommerce-admin'),
  order: 'desc',
  orderby: 'items_sold',
  type: 'number'
}, {
  key: 'net_revenue',
  label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Net sales', 'woocommerce-admin'),
  order: 'desc',
  orderby: 'net_revenue',
  type: 'currency'
}, {
  key: 'orders_count',
  label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Orders', 'woocommerce-admin'),
  order: 'desc',
  orderby: 'orders_count',
  type: 'number'
}]);
const filterConfig = {
  label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Show', 'woocommerce-admin'),
  staticParams: ['chartType', 'paged', 'per_page'],
  param: 'filter',
  showFilters: () => true,
  filters: [{
    label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('All products', 'woocommerce-admin'),
    value: 'all'
  }, {
    label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Single product', 'woocommerce-admin'),
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
        getLabels: _lib_async_requests__WEBPACK_IMPORTED_MODULE_3__[/* getProductLabels */ "d"],
        labels: {
          placeholder: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Type to search for a product', 'woocommerce-admin'),
          button: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Single product', 'woocommerce-admin')
        }
      }
    }]
  }, {
    label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Comparison', 'woocommerce-admin'),
    value: 'compare-products',
    chartMode: 'item-comparison',
    settings: {
      type: 'products',
      param: 'products',
      getLabels: _lib_async_requests__WEBPACK_IMPORTED_MODULE_3__[/* getProductLabels */ "d"],
      labels: {
        helpText: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Check at least two products below to compare', 'woocommerce-admin'),
        placeholder: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Search for products to compare', 'woocommerce-admin'),
        title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Compare Products', 'woocommerce-admin'),
        update: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Compare', 'woocommerce-admin')
      },
      onClick: addCesSurveyForAnalytics
    }
  }]
};
const variationsConfig = {
  showFilters: query => query.filter === 'single_product' && !!query.products && query['is-variable'],
  staticParams: ['filter', 'products', 'chartType', 'paged', 'per_page'],
  param: 'filter-variations',
  filters: [{
    label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('All variations', 'woocommerce-admin'),
    chartMode: 'item-comparison',
    value: 'all'
  }, {
    label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Single variation', 'woocommerce-admin'),
    value: 'select_variation',
    subFilters: [{
      component: 'Search',
      value: 'single_variation',
      path: ['select_variation'],
      settings: {
        type: 'variations',
        param: 'variations',
        getLabels: _lib_async_requests__WEBPACK_IMPORTED_MODULE_3__[/* getVariationLabels */ "g"],
        labels: {
          placeholder: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Type to search for a variation', 'woocommerce-admin'),
          button: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Single variation', 'woocommerce-admin')
        }
      }
    }]
  }, {
    label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Comparison', 'woocommerce-admin'),
    chartMode: 'item-comparison',
    value: 'compare-variations',
    settings: {
      type: 'variations',
      param: 'variations',
      getLabels: _lib_async_requests__WEBPACK_IMPORTED_MODULE_3__[/* getVariationLabels */ "g"],
      labels: {
        helpText: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Check at least two variations below to compare', 'woocommerce-admin'),
        placeholder: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Search for variations to compare', 'woocommerce-admin'),
        title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Compare Variations', 'woocommerce-admin'),
        update: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Compare', 'woocommerce-admin')
      }
    }
  }]
};
/**
 * Produts Report Advanced Filters.
 *
 * @filter woocommerce_admin_products_report_advanced_filters
 * @param {Object} advancedFilters Report Advanced Filters.
 * @param {string} advancedFilters.title Interpolated component string for Advanced Filters title.
 * @param {Object} advancedFilters.filters An object specifying a report's Advanced Filters.
 */

const advancedFilters = Object(_wordpress_hooks__WEBPACK_IMPORTED_MODULE_1__["applyFilters"])(PRODUCTS_REPORT_ADVANCED_FILTERS_FILTER, {
  filters: {},
  title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["_x"])('Products Match {{select /}} Filters', 'A sentence describing filters for Products. See screen shot for context: https://cloudup.com/cSsUY9VeCVJ', 'woocommerce-admin')
});

if (Object.keys(advancedFilters.filters).length) {
  filterConfig.filters.push({
    label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Advanced Filters', 'woocommerce-admin'),
    value: 'advanced'
  });
  variationsConfig.filters.push({
    label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Advanced Filters', 'woocommerce-admin'),
    value: 'advanced'
  });
}
/**
 * @typedef {import('../index.js').filter} filter
 */

/**
 * Products Report Filters.
 *
 * @filter woocommerce_admin_products_report_filters
 * @param {Array.<filter>} filters Report filters.
 */


const filters = Object(_wordpress_hooks__WEBPACK_IMPORTED_MODULE_1__["applyFilters"])(PRODUCTS_REPORT_FILTERS_FILTER, [filterConfig, variationsConfig]);

/***/ }),

/***/ 565:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "b", function() { return charts; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "c", function() { return filters; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "a", function() { return advancedFilters; });
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(2);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_hooks__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(27);
/* harmony import */ var _wordpress_hooks__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_hooks__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _lib_async_requests__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(538);
/* harmony import */ var _utils_admin_settings__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(23);
/**
 * External dependencies
 */


/**
 * Internal dependencies
 */



const ORDERS_REPORT_CHARTS_FILTER = 'woocommerce_admin_orders_report_charts';
const ORDERS_REPORT_FILTERS_FILTER = 'woocommerce_admin_orders_report_filters';
const ORDERS_REPORT_ADVANCED_FILTERS_FILTER = 'woocommerce_admin_orders_report_advanced_filters';
/**
 * @typedef {import('../index.js').chart} chart
 */

/**
 * Orders Report charts filter.
 *
 * @filter woocommerce_admin_orders_report_charts
 * @param {Array.<chart>} charts Report charts.
 */

const charts = Object(_wordpress_hooks__WEBPACK_IMPORTED_MODULE_1__["applyFilters"])(ORDERS_REPORT_CHARTS_FILTER, [{
  key: 'orders_count',
  label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Orders', 'woocommerce-admin'),
  type: 'number'
}, {
  key: 'net_revenue',
  label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Net sales', 'woocommerce-admin'),
  order: 'desc',
  orderby: 'net_total',
  type: 'currency'
}, {
  key: 'avg_order_value',
  label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Average order value', 'woocommerce-admin'),
  type: 'currency'
}, {
  key: 'avg_items_per_order',
  label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Average items per order', 'woocommerce-admin'),
  order: 'desc',
  orderby: 'num_items_sold',
  type: 'average'
}]);
/**
 * @typedef {import('../index.js').filter} filter
 */

/**
 * Orders Report Filters.
 *
 * @filter woocommerce_admin_orders_report_filters
 * @param {Array.<filter>} filters Report filters.
 */

const filters = Object(_wordpress_hooks__WEBPACK_IMPORTED_MODULE_1__["applyFilters"])(ORDERS_REPORT_FILTERS_FILTER, [{
  label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Show', 'woocommerce-admin'),
  staticParams: ['chartType', 'paged', 'per_page'],
  param: 'filter',
  showFilters: () => true,
  filters: [{
    label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('All orders', 'woocommerce-admin'),
    value: 'all'
  }, {
    label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Advanced filters', 'woocommerce-admin'),
    value: 'advanced'
  }]
}]);
/*eslint-disable max-len*/

/**
 * Orders Report Advanced Filters.
 *
 * @filter woocommerce_admin_orders_report_advanced_filters
 * @param {Object} advancedFilters Report Advanced Filters.
 * @param {string} advancedFilters.title Interpolated component string for Advanced Filters title.
 * @param {Object} advancedFilters.filters An object specifying a report's Advanced Filters.
 */

const advancedFilters = Object(_wordpress_hooks__WEBPACK_IMPORTED_MODULE_1__["applyFilters"])(ORDERS_REPORT_ADVANCED_FILTERS_FILTER, {
  title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["_x"])('Orders match {{select /}} filters', 'A sentence describing filters for Orders. See screen shot for context: https://cloudup.com/cSsUY9VeCVJ', 'woocommerce-admin'),
  filters: {
    status: {
      labels: {
        add: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Order Status', 'woocommerce-admin'),
        remove: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Remove order status filter', 'woocommerce-admin'),
        rule: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Select an order status filter match', 'woocommerce-admin'),

        /* translators: A sentence describing an Order Status filter. See screen shot for context: https://cloudup.com/cSsUY9VeCVJ */
        title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('{{title}}Order Status{{/title}} {{rule /}} {{filter /}}', 'woocommerce-admin'),
        filter: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Select an order status', 'woocommerce-admin')
      },
      rules: [{
        value: 'is',

        /* translators: Sentence fragment, logical, "Is" refers to searching for orders matching a chosen order status. Screenshot for context: https://cloudup.com/cSsUY9VeCVJ */
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["_x"])('Is', 'order status', 'woocommerce-admin')
      }, {
        value: 'is_not',

        /* translators: Sentence fragment, logical, "Is Not" refers to searching for orders that don\'t match a chosen order status. Screenshot for context: https://cloudup.com/cSsUY9VeCVJ */
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["_x"])('Is Not', 'order status', 'woocommerce-admin')
      }],
      input: {
        component: 'SelectControl',
        options: Object.keys(_utils_admin_settings__WEBPACK_IMPORTED_MODULE_3__[/* ORDER_STATUSES */ "c"]).map(key => ({
          value: key,
          label: _utils_admin_settings__WEBPACK_IMPORTED_MODULE_3__[/* ORDER_STATUSES */ "c"][key]
        }))
      }
    },
    product: {
      labels: {
        add: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Products', 'woocommerce-admin'),
        placeholder: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Search products', 'woocommerce-admin'),
        remove: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Remove products filter', 'woocommerce-admin'),
        rule: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Select a product filter match', 'woocommerce-admin'),

        /* translators: A sentence describing a Product filter. See screen shot for context: https://cloudup.com/cSsUY9VeCVJ */
        title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('{{title}}Product{{/title}} {{rule /}} {{filter /}}', 'woocommerce-admin'),
        filter: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Select products', 'woocommerce-admin')
      },
      rules: [{
        value: 'includes',

        /* translators: Sentence fragment, logical, "Includes" refers to orders including a given product(s). Screenshot for context: https://cloudup.com/cSsUY9VeCVJ */
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["_x"])('Includes', 'products', 'woocommerce-admin')
      }, {
        value: 'excludes',

        /* translators: Sentence fragment, logical, "Excludes" refers to orders excluding a given product(s). Screenshot for context: https://cloudup.com/cSsUY9VeCVJ */
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["_x"])('Excludes', 'products', 'woocommerce-admin')
      }],
      input: {
        component: 'Search',
        type: 'products',
        getLabels: _lib_async_requests__WEBPACK_IMPORTED_MODULE_2__[/* getProductLabels */ "d"]
      }
    },
    variation: {
      labels: {
        add: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Variations', 'woocommerce-admin'),
        placeholder: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Search variations', 'woocommerce-admin'),
        remove: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Remove variations filter', 'woocommerce-admin'),
        rule: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Select a variation filter match', 'woocommerce-admin'),

        /* translators: A sentence describing a Variation filter. See screen shot for context: https://cloudup.com/cSsUY9VeCVJ */
        title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('{{title}}Variation{{/title}} {{rule /}} {{filter /}}', 'woocommerce-admin'),
        filter: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Select variation', 'woocommerce-admin')
      },
      rules: [{
        value: 'includes',

        /* translators: Sentence fragment, logical, "Includes" refers to orders including a given variation(s). Screenshot for context: https://cloudup.com/cSsUY9VeCVJ */
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["_x"])('Includes', 'variations', 'woocommerce-admin')
      }, {
        value: 'excludes',

        /* translators: Sentence fragment, logical, "Excludes" refers to orders excluding a given variation(s). Screenshot for context: https://cloudup.com/cSsUY9VeCVJ */
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["_x"])('Excludes', 'variations', 'woocommerce-admin')
      }],
      input: {
        component: 'Search',
        type: 'variations',
        getLabels: _lib_async_requests__WEBPACK_IMPORTED_MODULE_2__[/* getVariationLabels */ "g"]
      }
    },
    coupon: {
      labels: {
        add: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Coupon Codes', 'woocommerce-admin'),
        placeholder: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Search coupons', 'woocommerce-admin'),
        remove: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Remove coupon filter', 'woocommerce-admin'),
        rule: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Select a coupon filter match', 'woocommerce-admin'),

        /* translators: A sentence describing a Coupon filter. See screen shot for context: https://cloudup.com/cSsUY9VeCVJ */
        title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('{{title}}Coupon code{{/title}} {{rule /}} {{filter /}}', 'woocommerce-admin'),
        filter: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Select coupon codes', 'woocommerce-admin')
      },
      rules: [{
        value: 'includes',

        /* translators: Sentence fragment, logical, "Includes" refers to orders including a given coupon code(s). Screenshot for context: https://cloudup.com/cSsUY9VeCVJ */
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["_x"])('Includes', 'coupon code', 'woocommerce-admin')
      }, {
        value: 'excludes',

        /* translators: Sentence fragment, logical, "Excludes" refers to orders excluding a given coupon code(s). Screenshot for context: https://cloudup.com/cSsUY9VeCVJ */
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["_x"])('Excludes', 'coupon code', 'woocommerce-admin')
      }],
      input: {
        component: 'Search',
        type: 'coupons',
        getLabels: _lib_async_requests__WEBPACK_IMPORTED_MODULE_2__[/* getCouponLabels */ "b"]
      }
    },
    customer_type: {
      labels: {
        add: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Customer type', 'woocommerce-admin'),
        remove: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Remove customer filter', 'woocommerce-admin'),
        rule: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Select a customer filter match', 'woocommerce-admin'),

        /* translators: A sentence describing a Customer filter. See screen shot for context: https://cloudup.com/cSsUY9VeCVJ */
        title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('{{title}}Customer is{{/title}} {{filter /}}', 'woocommerce-admin'),
        filter: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Select a customer type', 'woocommerce-admin')
      },
      input: {
        component: 'SelectControl',
        options: [{
          value: 'new',
          label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('New', 'woocommerce-admin')
        }, {
          value: 'returning',
          label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Returning', 'woocommerce-admin')
        }],
        defaultOption: 'new'
      }
    },
    refunds: {
      labels: {
        add: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Refunds', 'woocommerce-admin'),
        remove: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Remove refunds filter', 'woocommerce-admin'),
        rule: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Select a refund filter match', 'woocommerce-admin'),
        title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('{{title}}Refunds{{/title}} {{filter /}}', 'woocommerce-admin'),
        filter: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Select a refund type', 'woocommerce-admin')
      },
      input: {
        component: 'SelectControl',
        options: [{
          value: 'all',
          label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('All', 'woocommerce-admin')
        }, {
          value: 'partial',
          label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Partially refunded', 'woocommerce-admin')
        }, {
          value: 'full',
          label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Fully refunded', 'woocommerce-admin')
        }, {
          value: 'none',
          label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('None', 'woocommerce-admin')
        }],
        defaultOption: 'all'
      }
    },
    tax_rate: {
      labels: {
        add: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Tax Rates', 'woocommerce-admin'),
        placeholder: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Search tax rates', 'woocommerce-admin'),
        remove: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Remove tax rate filter', 'woocommerce-admin'),
        rule: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Select a tax rate filter match', 'woocommerce-admin'),

        /* translators: A sentence describing a tax rate filter. See screen shot for context: https://cloudup.com/cSsUY9VeCVJ */
        title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('{{title}}Tax Rate{{/title}} {{rule /}} {{filter /}}', 'woocommerce-admin'),
        filter: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Select tax rates', 'woocommerce-admin')
      },
      rules: [{
        value: 'includes',

        /* translators: Sentence fragment, logical, "Includes" refers to orders including a given tax rate(s). Screenshot for context: https://cloudup.com/cSsUY9VeCVJ */
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["_x"])('Includes', 'tax rate', 'woocommerce-admin')
      }, {
        value: 'excludes',

        /* translators: Sentence fragment, logical, "Excludes" refers to orders excluding a given tax rate(s). Screenshot for context: https://cloudup.com/cSsUY9VeCVJ */
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["_x"])('Excludes', 'tax rate', 'woocommerce-admin')
      }],
      input: {
        component: 'Search',
        type: 'taxes',
        getLabels: _lib_async_requests__WEBPACK_IMPORTED_MODULE_2__[/* getTaxRateLabels */ "f"]
      }
    },
    attribute: {
      allowMultiple: true,
      labels: {
        add: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Attribute', 'woocommerce-admin'),
        placeholder: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Search attributes', 'woocommerce-admin'),
        remove: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Remove attribute filter', 'woocommerce-admin'),
        rule: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Select a product attribute filter match', 'woocommerce-admin'),

        /* translators: A sentence describing a Product filter. See screen shot for context: https://cloudup.com/cSsUY9VeCVJ */
        title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('{{title}}Attribute{{/title}} {{rule /}} {{filter /}}', 'woocommerce-admin'),
        filter: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Select attributes', 'woocommerce-admin')
      },
      rules: [{
        value: 'is',

        /* translators: Sentence fragment, logical, "Is" refers to searching for products matching a chosen attribute. Screenshot for context: https://cloudup.com/cSsUY9VeCVJ */
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["_x"])('Is', 'product attribute', 'woocommerce-admin')
      }, {
        value: 'is_not',

        /* translators: Sentence fragment, logical, "Is Not" refers to searching for products that don\'t match a chosen attribute. Screenshot for context: https://cloudup.com/cSsUY9VeCVJ */
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["_x"])('Is Not', 'product attribute', 'woocommerce-admin')
      }],
      input: {
        component: 'ProductAttribute'
      }
    }
  }
});
/*eslint-enable max-len*/

/***/ }),

/***/ 566:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "b", function() { return charts; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "a", function() { return advancedFilters; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "c", function() { return filters; });
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(2);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_hooks__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(27);
/* harmony import */ var _wordpress_hooks__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_hooks__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(8);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _lib_async_requests__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(538);
/* harmony import */ var _customer_effort_score_tracks_data_constants__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(69);
/**
 * External dependencies
 */



/**
 * Internal dependencies
 */



const COUPON_REPORT_CHARTS_FILTER = 'woocommerce_admin_coupons_report_charts';
const COUPON_REPORT_FILTERS_FILTER = 'woocommerce_admin_coupons_report_filters';
const COUPON_REPORT_ADVANCED_FILTERS_FILTER = 'woocommerce_admin_coupon_report_advanced_filters';
const {
  addCesSurveyForAnalytics
} = Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_2__["dispatch"])(_customer_effort_score_tracks_data_constants__WEBPACK_IMPORTED_MODULE_4__[/* STORE_KEY */ "c"]);
/**
 * @typedef {import('../index.js').chart} chart
 */

/**
 * Coupons Report charts filter.
 *
 * @filter woocommerce_admin_coupons_report_charts
 * @param {Array.<chart>} charts Report charts.
 */

const charts = Object(_wordpress_hooks__WEBPACK_IMPORTED_MODULE_1__["applyFilters"])(COUPON_REPORT_CHARTS_FILTER, [{
  key: 'orders_count',
  label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Discounted orders', 'woocommerce-admin'),
  order: 'desc',
  orderby: 'orders_count',
  type: 'number'
}, {
  key: 'amount',
  label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Amount', 'woocommerce-admin'),
  order: 'desc',
  orderby: 'amount',
  type: 'currency'
}]);
/**
 * Coupons Report Advanced Filters.
 *
 * @filter woocommerce_admin_coupon_report_advanced_filters
 * @param {Object} advancedFilters Report Advanced Filters.
 * @param {string} advancedFilters.title Interpolated component string for Advanced Filters title.
 * @param {Object} advancedFilters.filters An object specifying a report's Advanced Filters.
 */

const advancedFilters = Object(_wordpress_hooks__WEBPACK_IMPORTED_MODULE_1__["applyFilters"])(COUPON_REPORT_ADVANCED_FILTERS_FILTER, {
  filters: {},
  title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["_x"])('Coupons match {{select /}} filters', 'A sentence describing filters for Coupons. See screen shot for context: https://cloudup.com/cSsUY9VeCVJ', 'woocommerce-admin')
});
const filterValues = [{
  label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('All coupons', 'woocommerce-admin'),
  value: 'all'
}, {
  label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Single coupon', 'woocommerce-admin'),
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
      getLabels: _lib_async_requests__WEBPACK_IMPORTED_MODULE_3__[/* getCouponLabels */ "b"],
      labels: {
        placeholder: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Type to search for a coupon', 'woocommerce-admin'),
        button: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Single Coupon', 'woocommerce-admin')
      }
    }
  }]
}, {
  label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Comparison', 'woocommerce-admin'),
  value: 'compare-coupons',
  settings: {
    type: 'coupons',
    param: 'coupons',
    getLabels: _lib_async_requests__WEBPACK_IMPORTED_MODULE_3__[/* getCouponLabels */ "b"],
    labels: {
      title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Compare Coupon Codes', 'woocommerce-admin'),
      update: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Compare', 'woocommerce-admin'),
      helpText: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Check at least two coupon codes below to compare', 'woocommerce-admin')
    },
    onClick: addCesSurveyForAnalytics
  }
}];

if (Object.keys(advancedFilters.filters).length) {
  filterValues.push({
    label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Advanced filters', 'woocommerce-admin'),
    value: 'advanced'
  });
}
/**
 * @typedef {import('../index.js').filter} filter
 */

/**
 * Coupons Report Filters.
 *
 * @filter woocommerce_admin_coupons_report_filters
 * @param {Array.<filter>} filters Report filters.
 */


const filters = Object(_wordpress_hooks__WEBPACK_IMPORTED_MODULE_1__["applyFilters"])(COUPON_REPORT_FILTERS_FILTER, [{
  label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Show', 'woocommerce-admin'),
  staticParams: ['chartType', 'paged', 'per_page'],
  param: 'filter',
  showFilters: () => true,
  filters: filterValues
}]);

/***/ }),

/***/ 567:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "b", function() { return charts; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "a", function() { return advancedFilters; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "c", function() { return filters; });
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(2);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_hooks__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(27);
/* harmony import */ var _wordpress_hooks__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_hooks__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _woocommerce_data__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(12);
/* harmony import */ var _woocommerce_data__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_data__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(8);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _lib_async_requests__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(538);
/* harmony import */ var _utils__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(539);
/* harmony import */ var _customer_effort_score_tracks_data_constants__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(69);
/**
 * External dependencies
 */




/**
 * Internal dependencies
 */




const TAXES_REPORT_CHARTS_FILTER = 'woocommerce_admin_taxes_report_charts';
const TAXES_REPORT_FILTERS_FILTER = 'woocommerce_admin_taxes_report_filters';
const TAXES_REPORT_ADVANCED_FILTERS_FILTER = 'woocommerce_admin_taxes_report_advanced_filters';
const {
  addCesSurveyForAnalytics
} = Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_3__["dispatch"])(_customer_effort_score_tracks_data_constants__WEBPACK_IMPORTED_MODULE_6__[/* STORE_KEY */ "c"]);
/**
 * @typedef {import('../index.js').chart} chart
 */

/**
 * Taxes Report charts filter.
 *
 * @filter woocommerce_admin_taxes_report_charts
 * @param {Array.<chart>} charts Report charts.
 */

const charts = Object(_wordpress_hooks__WEBPACK_IMPORTED_MODULE_1__["applyFilters"])(TAXES_REPORT_CHARTS_FILTER, [{
  key: 'total_tax',
  label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Total tax', 'woocommerce-admin'),
  order: 'desc',
  orderby: 'total_tax',
  type: 'currency'
}, {
  key: 'order_tax',
  label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Order tax', 'woocommerce-admin'),
  order: 'desc',
  orderby: 'order_tax',
  type: 'currency'
}, {
  key: 'shipping_tax',
  label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Shipping tax', 'woocommerce-admin'),
  order: 'desc',
  orderby: 'shipping_tax',
  type: 'currency'
}, {
  key: 'orders_count',
  label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Orders', 'woocommerce-admin'),
  order: 'desc',
  orderby: 'orders_count',
  type: 'number'
}]);
/**
 * Taxes Report Advanced Filters.
 *
 * @filter woocommerce_admin_taxes_report_advanced_filters
 * @param {Object} advancedFilters Report Advanced Filters.
 * @param {string} advancedFilters.title Interpolated component string for Advanced Filters title.
 * @param {Object} advancedFilters.filters An object specifying a report's Advanced Filters.
 */

const advancedFilters = Object(_wordpress_hooks__WEBPACK_IMPORTED_MODULE_1__["applyFilters"])(TAXES_REPORT_ADVANCED_FILTERS_FILTER, {
  filters: {},
  title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["_x"])('Taxes match {{select /}} filters', 'A sentence describing filters for Taxes. See screen shot for context: https://cloudup.com/cSsUY9VeCVJ', 'woocommerce-admin')
});
const filterValues = [{
  label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('All taxes', 'woocommerce-admin'),
  value: 'all'
}, {
  label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Comparison', 'woocommerce-admin'),
  value: 'compare-taxes',
  chartMode: 'item-comparison',
  settings: {
    type: 'taxes',
    param: 'taxes',
    getLabels: Object(_lib_async_requests__WEBPACK_IMPORTED_MODULE_4__[/* getRequestByIdString */ "e"])(_woocommerce_data__WEBPACK_IMPORTED_MODULE_2__["NAMESPACE"] + '/taxes', tax => ({
      id: tax.id,
      key: tax.id,
      label: Object(_utils__WEBPACK_IMPORTED_MODULE_5__[/* getTaxCode */ "a"])(tax)
    })),
    labels: {
      helpText: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Check at least two tax codes below to compare', 'woocommerce-admin'),
      placeholder: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Search for tax codes to compare', 'woocommerce-admin'),
      title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Compare Tax Codes', 'woocommerce-admin'),
      update: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Compare', 'woocommerce-admin')
    },
    onClick: addCesSurveyForAnalytics
  }
}];

if (Object.keys(advancedFilters.filters).length) {
  filterValues.push({
    label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Advanced filters', 'woocommerce-admin'),
    value: 'advanced'
  });
}
/**
 * @typedef {import('../index.js').filter} filter
 */

/**
 * Coupons Report Filters.
 *
 * @filter woocommerce_admin_taxes_report_filters
 * @param {Array.<filter>} filters Report filters.
 */


const filters = Object(_wordpress_hooks__WEBPACK_IMPORTED_MODULE_1__["applyFilters"])(TAXES_REPORT_FILTERS_FILTER, [{
  label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Show', 'woocommerce-admin'),
  staticParams: ['chartType', 'paged', 'per_page'],
  param: 'filter',
  showFilters: () => true,
  filters: filterValues
}]);

/***/ }),

/***/ 568:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "b", function() { return charts; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "c", function() { return filters; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "a", function() { return advancedFilters; });
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(2);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_hooks__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(27);
/* harmony import */ var _wordpress_hooks__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_hooks__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _lib_async_requests__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(538);
/**
 * External dependencies
 */


/**
 * Internal dependencies
 */


const DOWLOADS_REPORT_CHARTS_FILTER = 'woocommerce_admin_downloads_report_charts';
const DOWLOADS_REPORT_FILTERS_FILTER = 'woocommerce_admin_downloads_report_filters';
const DOWLOADS_REPORT_ADVANCED_FILTERS_FILTER = 'woocommerce_admin_downloads_report_advanced_filters';
/**
 * @typedef {import('../index.js').chart} chart
 */

/**
 * Downloads Report charts filter.
 *
 * @filter woocommerce_admin_downloads_report_charts
 * @param {Array.<chart>} charts Report charts.
 */

const charts = Object(_wordpress_hooks__WEBPACK_IMPORTED_MODULE_1__["applyFilters"])(DOWLOADS_REPORT_CHARTS_FILTER, [{
  key: 'download_count',
  label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Downloads', 'woocommerce-admin'),
  type: 'number'
}]);
/**
 * @typedef {import('../index.js').filter} filter
 */

/**
 * Downloads Report Filters.
 *
 * @filter woocommerce_admin_downloads_report_filters
 * @param {Array.<filter>} filters Report filters.
 */

const filters = Object(_wordpress_hooks__WEBPACK_IMPORTED_MODULE_1__["applyFilters"])(DOWLOADS_REPORT_FILTERS_FILTER, [{
  label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Show', 'woocommerce-admin'),
  staticParams: ['chartType', 'paged', 'per_page'],
  param: 'filter',
  showFilters: () => true,
  filters: [{
    label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('All downloads', 'woocommerce-admin'),
    value: 'all'
  }, {
    label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Advanced filters', 'woocommerce-admin'),
    value: 'advanced'
  }]
}]);
/*eslint-disable max-len*/

/**
 * Downloads Report Advanced Filters.
 *
 * @filter woocommerce_admin_downloads_report_advanced_filters
 * @param {Object} advancedFilters Report Advanced Filters.
 * @param {string} advancedFilters.title Interpolated component string for Advanced Filters title.
 * @param {Object} advancedFilters.filters An object specifying a report's Advanced Filters.
 */

const advancedFilters = Object(_wordpress_hooks__WEBPACK_IMPORTED_MODULE_1__["applyFilters"])(DOWLOADS_REPORT_ADVANCED_FILTERS_FILTER, {
  title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["_x"])('Downloads match {{select /}} filters', 'A sentence describing filters for Downloads. See screen shot for context: https://cloudup.com/ccxhyH2mEDg', 'woocommerce-admin'),
  filters: {
    product: {
      labels: {
        add: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Product', 'woocommerce-admin'),
        placeholder: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Search', 'woocommerce-admin'),
        remove: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Remove product filter', 'woocommerce-admin'),
        rule: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Select a product filter match', 'woocommerce-admin'),

        /* translators: A sentence describing a Product filter. See screen shot for context: https://cloudup.com/ccxhyH2mEDg */
        title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('{{title}}Product{{/title}} {{rule /}} {{filter /}}', 'woocommerce-admin'),
        filter: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Select product', 'woocommerce-admin')
      },
      rules: [{
        value: 'includes',

        /* translators: Sentence fragment, logical, "Includes" refers to products including a given product(s). Screenshot for context: https://cloudup.com/ccxhyH2mEDg */
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["_x"])('Includes', 'products', 'woocommerce-admin')
      }, {
        value: 'excludes',

        /* translators: Sentence fragment, logical, "Excludes" refers to products excluding a products(s). Screenshot for context: https://cloudup.com/ccxhyH2mEDg */
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["_x"])('Excludes', 'products', 'woocommerce-admin')
      }],
      input: {
        component: 'Search',
        type: 'products',
        getLabels: _lib_async_requests__WEBPACK_IMPORTED_MODULE_2__[/* getProductLabels */ "d"]
      }
    },
    customer: {
      labels: {
        add: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Username', 'woocommerce-admin'),
        placeholder: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Search customer username', 'woocommerce-admin'),
        remove: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Remove customer username filter', 'woocommerce-admin'),
        rule: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Select a customer username filter match', 'woocommerce-admin'),

        /* translators: A sentence describing a customer username filter. See screen shot for context: https://cloudup.com/ccxhyH2mEDg */
        title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('{{title}}Username{{/title}} {{rule /}} {{filter /}}', 'woocommerce-admin'),
        filter: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Select customer username', 'woocommerce-admin')
      },
      rules: [{
        value: 'includes',

        /* translators: Sentence fragment, logical, "Includes" refers to customer usernames including a given username(s). Screenshot for context: https://cloudup.com/ccxhyH2mEDg */
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["_x"])('Includes', 'customer usernames', 'woocommerce-admin')
      }, {
        value: 'excludes',

        /* translators: Sentence fragment, logical, "Excludes" refers to customer usernames excluding a given username(s). Screenshot for context: https://cloudup.com/ccxhyH2mEDg */
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["_x"])('Excludes', 'customer usernames', 'woocommerce-admin')
      }],
      input: {
        component: 'Search',
        type: 'usernames',
        getLabels: _lib_async_requests__WEBPACK_IMPORTED_MODULE_2__[/* getCustomerLabels */ "c"]
      }
    },
    order: {
      labels: {
        add: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Order #', 'woocommerce-admin'),
        placeholder: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Search order number', 'woocommerce-admin'),
        remove: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Remove order number filter', 'woocommerce-admin'),
        rule: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Select a order number filter match', 'woocommerce-admin'),

        /* translators: A sentence describing a order number filter. See screen shot for context: https://cloudup.com/ccxhyH2mEDg */
        title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('{{title}}Order #{{/title}} {{rule /}} {{filter /}}', 'woocommerce-admin'),
        filter: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Select order number', 'woocommerce-admin')
      },
      rules: [{
        value: 'includes',

        /* translators: Sentence fragment, logical, "Includes" refers to order numbers including a given order(s). Screenshot for context: https://cloudup.com/ccxhyH2mEDg */
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["_x"])('Includes', 'order numbers', 'woocommerce-admin')
      }, {
        value: 'excludes',

        /* translators: Sentence fragment, logical, "Excludes" refers to order numbers excluding a given order(s). Screenshot for context: https://cloudup.com/ccxhyH2mEDg */
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["_x"])('Excludes', 'order numbers', 'woocommerce-admin')
      }],
      input: {
        component: 'Search',
        type: 'orders',
        getLabels: async value => {
          const orderIds = value.split(',');
          return await orderIds.map(orderId => ({
            id: orderId,
            label: '#' + orderId
          }));
        }
      }
    },
    ip_address: {
      labels: {
        add: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('IP Address', 'woocommerce-admin'),
        placeholder: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Search IP address', 'woocommerce-admin'),
        remove: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Remove IP address filter', 'woocommerce-admin'),
        rule: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Select an IP address filter match', 'woocommerce-admin'),

        /* translators: A sentence describing a order number filter. See screen shot for context: https://cloudup.com/ccxhyH2mEDg */
        title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('{{title}}IP Address{{/title}} {{rule /}} {{filter /}}', 'woocommerce-admin'),
        filter: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Select IP address', 'woocommerce-admin')
      },
      rules: [{
        value: 'includes',

        /* translators: Sentence fragment, logical, "Includes" refers to IP addresses including a given address(s). Screenshot for context: https://cloudup.com/ccxhyH2mEDg */
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["_x"])('Includes', 'IP addresses', 'woocommerce-admin')
      }, {
        value: 'excludes',

        /* translators: Sentence fragment, logical, "Excludes" refers to IP addresses excluding a given address(s). Screenshot for context: https://cloudup.com/ccxhyH2mEDg */
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["_x"])('Excludes', 'IP addresses', 'woocommerce-admin')
      }],
      input: {
        component: 'Search',
        type: 'downloadIps',
        getLabels: async value => {
          const ips = value.split(',');
          return await ips.map(ip => {
            return {
              id: ip,
              label: ip
            };
          });
        }
      }
    }
  }
});
/*eslint-enable max-len*/

/***/ }),

/***/ 644:
/***/ (function(module, exports, __webpack_require__) {

"use strict";
Object.defineProperty(exports,"__esModule",{value:!0}),exports["default"]=_default;var _react=_interopRequireDefault(__webpack_require__(6)),_excluded=["size","onClick","icon","className"];function _interopRequireDefault(a){return a&&a.__esModule?a:{default:a}}function _extends(){return _extends=Object.assign||function(a){for(var b,c=1;c<arguments.length;c++)for(var d in b=arguments[c],b)Object.prototype.hasOwnProperty.call(b,d)&&(a[d]=b[d]);return a},_extends.apply(this,arguments)}function _objectWithoutProperties(a,b){if(null==a)return{};var c,d,e=_objectWithoutPropertiesLoose(a,b);if(Object.getOwnPropertySymbols){var f=Object.getOwnPropertySymbols(a);for(d=0;d<f.length;d++)c=f[d],0<=b.indexOf(c)||Object.prototype.propertyIsEnumerable.call(a,c)&&(e[c]=a[c])}return e}function _objectWithoutPropertiesLoose(a,b){if(null==a)return{};var c,d,e={},f=Object.keys(a);for(d=0;d<f.length;d++)c=f[d],0<=b.indexOf(c)||(e[c]=a[c]);return e}function _default(a){var b=a.size,c=void 0===b?24:b,d=a.onClick,e=a.icon,f=a.className,g=_objectWithoutProperties(a,_excluded),h=["gridicon","gridicons-line-graph",f,!1,!1,!1].filter(Boolean).join(" ");return _react["default"].createElement("svg",_extends({className:h,height:c,width:c,onClick:d},g,{xmlns:"http://www.w3.org/2000/svg",viewBox:"0 0 24 24"}),_react["default"].createElement("g",null,_react["default"].createElement("path",{d:"M3 19h18v2H3zm3-3c1.1 0 2-.9 2-2 0-.5-.2-1-.5-1.3L8.8 10H9c.5 0 1-.2 1.3-.5l2.7 1.4v.1c0 1.1.9 2 2 2s2-.9 2-2c0-.5-.2-.9-.5-1.3L17.8 7h.2c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2c0 .5.2 1 .5 1.3L15.2 9H15c-.5 0-1 .2-1.3.5L11 8.2V8c0-1.1-.9-2-2-2s-2 .9-2 2c0 .5.2 1 .5 1.3L6.2 12H6c-1.1 0-2 .9-2 2s.9 2 2 2z"})))}


/***/ }),

/***/ 645:
/***/ (function(module, exports, __webpack_require__) {

"use strict";
Object.defineProperty(exports,"__esModule",{value:!0}),exports["default"]=_default;var _react=_interopRequireDefault(__webpack_require__(6)),_excluded=["size","onClick","icon","className"];function _interopRequireDefault(a){return a&&a.__esModule?a:{default:a}}function _extends(){return _extends=Object.assign||function(a){for(var b,c=1;c<arguments.length;c++)for(var d in b=arguments[c],b)Object.prototype.hasOwnProperty.call(b,d)&&(a[d]=b[d]);return a},_extends.apply(this,arguments)}function _objectWithoutProperties(a,b){if(null==a)return{};var c,d,e=_objectWithoutPropertiesLoose(a,b);if(Object.getOwnPropertySymbols){var f=Object.getOwnPropertySymbols(a);for(d=0;d<f.length;d++)c=f[d],0<=b.indexOf(c)||Object.prototype.propertyIsEnumerable.call(a,c)&&(e[c]=a[c])}return e}function _objectWithoutPropertiesLoose(a,b){if(null==a)return{};var c,d,e={},f=Object.keys(a);for(d=0;d<f.length;d++)c=f[d],0<=b.indexOf(c)||(e[c]=a[c]);return e}function _default(a){var b=a.size,c=void 0===b?24:b,d=a.onClick,e=a.icon,f=a.className,g=_objectWithoutProperties(a,_excluded),h=["gridicon","gridicons-stats-alt",f,!1,!1,!!function isModulo18(a){return 0==a%18}(c)&&"needs-offset-y"].filter(Boolean).join(" ");return _react["default"].createElement("svg",_extends({className:h,height:c,width:c,onClick:d},g,{xmlns:"http://www.w3.org/2000/svg",viewBox:"0 0 24 24"}),_react["default"].createElement("g",null,_react["default"].createElement("path",{d:"M21 21H3v-2h18v2zM8 10H4v7h4v-7zm6-7h-4v14h4V3zm6 3h-4v11h4V6z"})))}


/***/ }),

/***/ 646:
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),

/***/ 647:
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),

/***/ 668:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
// ESM COMPAT FLAG
__webpack_require__.r(__webpack_exports__);

// EXTERNAL MODULE: external ["wp","element"]
var external_wp_element_ = __webpack_require__(0);

// EXTERNAL MODULE: external ["wp","i18n"]
var external_wp_i18n_ = __webpack_require__(2);

// EXTERNAL MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/classnames@2.3.1/node_modules/classnames/index.js
var classnames = __webpack_require__(7);
var classnames_default = /*#__PURE__*/__webpack_require__.n(classnames);

// EXTERNAL MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/gridicons@3.4.0_react@17.0.2/node_modules/gridicons/dist/line-graph.js
var line_graph = __webpack_require__(644);
var line_graph_default = /*#__PURE__*/__webpack_require__.n(line_graph);

// EXTERNAL MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/gridicons@3.4.0_react@17.0.2/node_modules/gridicons/dist/stats-alt.js
var stats_alt = __webpack_require__(645);
var stats_alt_default = /*#__PURE__*/__webpack_require__.n(stats_alt);

// EXTERNAL MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/prop-types@15.8.1/node_modules/prop-types/index.js
var prop_types = __webpack_require__(1);
var prop_types_default = /*#__PURE__*/__webpack_require__.n(prop_types);

// EXTERNAL MODULE: external ["wp","components"]
var external_wp_components_ = __webpack_require__(4);

// EXTERNAL MODULE: external ["wc","components"]
var external_wc_components_ = __webpack_require__(22);

// EXTERNAL MODULE: external ["wc","data"]
var external_wc_data_ = __webpack_require__(12);

// EXTERNAL MODULE: external ["wc","date"]
var external_wc_date_ = __webpack_require__(20);

// EXTERNAL MODULE: external ["wc","tracks"]
var external_wc_tracks_ = __webpack_require__(17);

// EXTERNAL MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/@babel+runtime@7.17.2/node_modules/@babel/runtime/helpers/defineProperty.js
var defineProperty = __webpack_require__(44);
var defineProperty_default = /*#__PURE__*/__webpack_require__.n(defineProperty);

// EXTERNAL MODULE: external ["wc","navigation"]
var external_wc_navigation_ = __webpack_require__(13);

// EXTERNAL MODULE: external ["wc","wcSettings"]
var external_wc_wcSettings_ = __webpack_require__(15);

// EXTERNAL MODULE: external ["wc","experimental"]
var external_wc_experimental_ = __webpack_require__(19);

// EXTERNAL MODULE: ./client/analytics/components/report-chart/index.js + 1 modules
var report_chart = __webpack_require__(540);

// EXTERNAL MODULE: ./client/dashboard/dashboard-charts/block.scss
var block = __webpack_require__(646);

// CONCATENATED MODULE: ./client/dashboard/dashboard-charts/block.js



/**
 * External dependencies
 */







/**
 * Internal dependencies
 */




class block_ChartBlock extends external_wp_element_["Component"] {
  constructor() {
    super(...arguments);

    defineProperty_default()(this, "handleChartClick", () => {
      const {
        selectedChart
      } = this.props;
      Object(external_wc_navigation_["getHistory"])().push(this.getChartPath(selectedChart));
    });
  }

  getChartPath(chart) {
    return Object(external_wc_navigation_["getNewPath"])({
      chart: chart.key
    }, '/analytics/' + chart.endpoint, Object(external_wc_navigation_["getPersistedQuery"])());
  }

  render() {
    const {
      charts,
      endpoint,
      path,
      query,
      selectedChart,
      filters
    } = this.props;

    if (!selectedChart) {
      return null;
    }

    return Object(external_wp_element_["createElement"])("div", {
      role: "presentation",
      className: "woocommerce-dashboard__chart-block-wrapper",
      onClick: this.handleChartClick
    }, Object(external_wp_element_["createElement"])(external_wp_components_["Card"], {
      className: "woocommerce-dashboard__chart-block"
    }, Object(external_wp_element_["createElement"])(external_wp_components_["CardHeader"], null, Object(external_wp_element_["createElement"])(external_wc_experimental_["Text"], {
      as: "h3",
      size: 16,
      weight: 600,
      color: "#23282d"
    }, selectedChart.label)), Object(external_wp_element_["createElement"])(external_wp_components_["CardBody"], {
      size: null
    }, Object(external_wp_element_["createElement"])("a", {
      className: "screen-reader-text",
      href: Object(external_wc_wcSettings_["getAdminLink"])(this.getChartPath(selectedChart))
    },
    /* translators: %s is the chart type */
    Object(external_wp_i18n_["sprintf"])(Object(external_wp_i18n_["__"])('%s Report', 'woocommerce-admin'), selectedChart.label)), Object(external_wp_element_["createElement"])(report_chart["a" /* default */], {
      charts: charts,
      endpoint: endpoint,
      query: query,
      interactiveLegend: false,
      legendPosition: "bottom",
      path: path,
      selectedChart: selectedChart,
      showHeaderControls: false,
      filters: filters
    }))));
  }

}

block_ChartBlock.propTypes = {
  charts: prop_types_default.a.array,
  endpoint: prop_types_default.a.string.isRequired,
  path: prop_types_default.a.string.isRequired,
  query: prop_types_default.a.object.isRequired,
  selectedChart: prop_types_default.a.object.isRequired
};
/* harmony default export */ var dashboard_charts_block = (block_ChartBlock);
// EXTERNAL MODULE: external ["wp","hooks"]
var external_wp_hooks_ = __webpack_require__(27);

// EXTERNAL MODULE: ./client/analytics/report/orders/config.js
var config = __webpack_require__(565);

// EXTERNAL MODULE: ./client/analytics/report/products/config.js
var products_config = __webpack_require__(563);

// EXTERNAL MODULE: ./client/analytics/report/revenue/config.js
var revenue_config = __webpack_require__(562);

// EXTERNAL MODULE: ./client/analytics/report/coupons/config.js
var coupons_config = __webpack_require__(566);

// EXTERNAL MODULE: ./client/analytics/report/taxes/config.js
var taxes_config = __webpack_require__(567);

// EXTERNAL MODULE: ./client/analytics/report/downloads/config.js
var downloads_config = __webpack_require__(568);

// CONCATENATED MODULE: ./client/dashboard/dashboard-charts/config.js
/**
 * External dependencies
 */


/**
 * Internal dependencies
 */







const DASHBOARD_CHARTS_FILTER = 'woocommerce_admin_dashboard_charts_filter';
const config_charts = {
  revenue: revenue_config["b" /* charts */],
  orders: config["b" /* charts */],
  products: products_config["b" /* charts */],
  coupons: coupons_config["b" /* charts */],
  taxes: taxes_config["b" /* charts */],
  downloads: downloads_config["b" /* charts */]
};
const defaultCharts = [{
  label: Object(external_wp_i18n_["__"])('Total sales', 'woocommerce-admin'),
  report: 'revenue',
  key: 'total_sales'
}, {
  label: Object(external_wp_i18n_["__"])('Net sales', 'woocommerce-admin'),
  report: 'revenue',
  key: 'net_revenue'
}, {
  label: Object(external_wp_i18n_["__"])('Orders', 'woocommerce-admin'),
  report: 'orders',
  key: 'orders_count'
}, {
  label: Object(external_wp_i18n_["__"])('Average order value', 'woocommerce-admin'),
  report: 'orders',
  key: 'avg_order_value'
}, {
  label: Object(external_wp_i18n_["__"])('Items sold', 'woocommerce-admin'),
  report: 'products',
  key: 'items_sold'
}, {
  label: Object(external_wp_i18n_["__"])('Returns', 'woocommerce-admin'),
  report: 'revenue',
  key: 'refunds'
}, {
  label: Object(external_wp_i18n_["__"])('Discounted orders', 'woocommerce-admin'),
  report: 'coupons',
  key: 'orders_count'
}, {
  label: Object(external_wp_i18n_["__"])('Gross discounted', 'woocommerce-admin'),
  report: 'coupons',
  key: 'amount'
}, {
  label: Object(external_wp_i18n_["__"])('Total tax', 'woocommerce-admin'),
  report: 'taxes',
  key: 'total_tax'
}, {
  label: Object(external_wp_i18n_["__"])('Order tax', 'woocommerce-admin'),
  report: 'taxes',
  key: 'order_tax'
}, {
  label: Object(external_wp_i18n_["__"])('Shipping tax', 'woocommerce-admin'),
  report: 'taxes',
  key: 'shipping_tax'
}, {
  label: Object(external_wp_i18n_["__"])('Shipping', 'woocommerce-admin'),
  report: 'revenue',
  key: 'shipping'
}, {
  label: Object(external_wp_i18n_["__"])('Downloads', 'woocommerce-admin'),
  report: 'downloads',
  key: 'download_count'
}];
/**
 * Dashboard Charts section charts.
 *
 * @filter woocommerce_admin_dashboard_charts_filter
 * @param {Array.<Object>} charts Array of visible charts.
 */

const uniqCharts = Object(external_wp_hooks_["applyFilters"])(DASHBOARD_CHARTS_FILTER, defaultCharts.map(chartDef => ({ ...config_charts[chartDef.report].find(chart => chart.key === chartDef.key),
  label: chartDef.label,
  endpoint: chartDef.report
})));
// EXTERNAL MODULE: ./client/dashboard/dashboard-charts/style.scss
var style = __webpack_require__(647);

// CONCATENATED MODULE: ./client/dashboard/dashboard-charts/index.js


/**
 * External dependencies
 */











/**
 * Internal dependencies
 */





const renderChartToggles = _ref => {
  let {
    hiddenBlocks,
    onToggleHiddenBlock
  } = _ref;
  return uniqCharts.map(chart => {
    const key = chart.endpoint + '_' + chart.key;
    const checked = !hiddenBlocks.includes(key);
    return Object(external_wp_element_["createElement"])(external_wc_components_["MenuItem"], {
      checked: checked,
      isCheckbox: true,
      isClickable: true,
      key: chart.endpoint + '_' + chart.key,
      onInvoke: () => {
        onToggleHiddenBlock(key)();
        Object(external_wc_tracks_["recordEvent"])('dash_charts_chart_toggle', {
          status: checked ? 'off' : 'on',
          key
        });
      }
    }, chart.label);
  });
};

const renderIntervalSelector = _ref2 => {
  let {
    chartInterval,
    setInterval,
    query,
    defaultDateRange
  } = _ref2;
  const allowedIntervals = Object(external_wc_date_["getAllowedIntervalsForQuery"])(query, defaultDateRange);

  if (!allowedIntervals || allowedIntervals.length < 1) {
    return null;
  }

  const intervalLabels = {
    hour: Object(external_wp_i18n_["__"])('By hour', 'woocommerce-admin'),
    day: Object(external_wp_i18n_["__"])('By day', 'woocommerce-admin'),
    week: Object(external_wp_i18n_["__"])('By week', 'woocommerce-admin'),
    month: Object(external_wp_i18n_["__"])('By month', 'woocommerce-admin'),
    quarter: Object(external_wp_i18n_["__"])('By quarter', 'woocommerce-admin'),
    year: Object(external_wp_i18n_["__"])('By year', 'woocommerce-admin')
  };
  return Object(external_wp_element_["createElement"])(external_wp_components_["SelectControl"], {
    className: "woocommerce-chart__interval-select",
    value: chartInterval,
    options: allowedIntervals.map(allowedInterval => ({
      value: allowedInterval,
      label: intervalLabels[allowedInterval]
    })),
    "aria-label": "Chart period",
    onChange: setInterval
  });
};

const renderChartBlocks = _ref3 => {
  let {
    hiddenBlocks,
    path,
    query,
    filters
  } = _ref3;
  // Reduce the API response to only the necessary stat fields
  // by supplying all charts common to each endpoint.
  const chartsByEndpoint = uniqCharts.reduce((byEndpoint, chart) => {
    if (typeof byEndpoint[chart.endpoint] === 'undefined') {
      byEndpoint[chart.endpoint] = [];
    }

    byEndpoint[chart.endpoint].push(chart);
    return byEndpoint;
  }, {});
  return Object(external_wp_element_["createElement"])("div", {
    className: "woocommerce-dashboard__columns"
  }, uniqCharts.map(chart => {
    return hiddenBlocks.includes(chart.endpoint + '_' + chart.key) ? null : Object(external_wp_element_["createElement"])(dashboard_charts_block, {
      charts: chartsByEndpoint[chart.endpoint],
      endpoint: chart.endpoint,
      key: chart.endpoint + '_' + chart.key,
      path: path,
      query: query,
      selectedChart: chart,
      filters: filters
    });
  }));
};

const DashboardCharts = props => {
  const {
    controls: Controls,
    hiddenBlocks,
    isFirst,
    isLast,
    onMove,
    onRemove,
    onTitleBlur,
    onTitleChange,
    onToggleHiddenBlock,
    path,
    title,
    titleInput,
    filters,
    defaultDateRange
  } = props;
  const {
    updateUserPreferences,
    ...userPrefs
  } = Object(external_wc_data_["useUserPreferences"])();
  const [chartType, setChartType] = Object(external_wp_element_["useState"])(userPrefs.dashboard_chart_type || 'line');
  const [chartInterval, setChartInterval] = Object(external_wp_element_["useState"])(userPrefs.dashboard_chart_interval || 'day');
  const query = { ...props.query,
    chartType,
    interval: chartInterval
  };

  const handleTypeToggle = type => {
    return () => {
      setChartType(type);
      const userDataFields = {
        dashboard_chart_type: type
      };
      updateUserPreferences(userDataFields);
      Object(external_wc_tracks_["recordEvent"])('dash_charts_type_toggle', {
        chart_type: type
      });
    };
  };

  const renderMenu = () => Object(external_wp_element_["createElement"])(external_wc_components_["EllipsisMenu"], {
    label: Object(external_wp_i18n_["__"])('Choose which charts to display', 'woocommerce-admin'),
    renderContent: _ref4 => {
      let {
        onToggle
      } = _ref4;
      return Object(external_wp_element_["createElement"])(external_wp_element_["Fragment"], null, Object(external_wp_element_["createElement"])(external_wc_components_["MenuTitle"], null, Object(external_wp_i18n_["__"])('Charts', 'woocommerce-admin')), renderChartToggles({
        hiddenBlocks,
        onToggleHiddenBlock
      }), Object(external_wp_element_["createElement"])(Controls, {
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

  const setInterval = interval => {
    setChartInterval(interval);
    const userDataFields = {
      dashboard_chart_interval: interval
    };
    updateUserPreferences(userDataFields);
    Object(external_wc_tracks_["recordEvent"])('dash_charts_interval', {
      interval
    });
  };

  return Object(external_wp_element_["createElement"])("div", {
    className: "woocommerce-dashboard__dashboard-charts"
  }, Object(external_wp_element_["createElement"])(external_wc_components_["SectionHeader"], {
    title: title || Object(external_wp_i18n_["__"])('Charts', 'woocommerce-admin'),
    menu: renderMenu(),
    className: 'has-interval-select'
  }, renderIntervalSelector({
    chartInterval,
    setInterval,
    query,
    defaultDateRange
  }), Object(external_wp_element_["createElement"])(external_wp_components_["NavigableMenu"], {
    className: "woocommerce-chart__types",
    orientation: "horizontal",
    role: "menubar"
  }, Object(external_wp_element_["createElement"])(external_wp_components_["Button"], {
    className: classnames_default()('woocommerce-chart__type-button', {
      'woocommerce-chart__type-button-selected': !query.chartType || query.chartType === 'line'
    }),
    title: Object(external_wp_i18n_["__"])('Line chart', 'woocommerce-admin'),
    "aria-checked": query.chartType === 'line',
    role: "menuitemradio",
    tabIndex: query.chartType === 'line' ? 0 : -1,
    onClick: handleTypeToggle('line')
  }, Object(external_wp_element_["createElement"])(line_graph_default.a, null)), Object(external_wp_element_["createElement"])(external_wp_components_["Button"], {
    className: classnames_default()('woocommerce-chart__type-button', {
      'woocommerce-chart__type-button-selected': query.chartType === 'bar'
    }),
    title: Object(external_wp_i18n_["__"])('Bar chart', 'woocommerce-admin'),
    "aria-checked": query.chartType === 'bar',
    role: "menuitemradio",
    tabIndex: query.chartType === 'bar' ? 0 : -1,
    onClick: handleTypeToggle('bar')
  }, Object(external_wp_element_["createElement"])(stats_alt_default.a, null)))), renderChartBlocks({
    hiddenBlocks,
    path,
    query,
    filters
  }));
};

DashboardCharts.propTypes = {
  path: prop_types_default.a.string.isRequired,
  query: prop_types_default.a.object.isRequired,
  defaultDateRange: prop_types_default.a.string.isRequired
};
/* harmony default export */ var dashboard_charts = __webpack_exports__["default"] = (DashboardCharts);

/***/ })

}]);