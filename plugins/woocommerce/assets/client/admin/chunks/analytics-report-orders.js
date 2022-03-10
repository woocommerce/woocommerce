(window["__wcAdmin_webpackJsonp"] = window["__wcAdmin_webpackJsonp"] || []).push([[12],{

/***/ 516:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
// ESM COMPAT FLAG
__webpack_require__.r(__webpack_exports__);

// EXPORTS
__webpack_require__.d(__webpack_exports__, "default", function() { return /* binding */ orders_OrdersReport; });

// EXTERNAL MODULE: external ["wp","element"]
var external_wp_element_ = __webpack_require__(0);

// EXTERNAL MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/prop-types@15.8.1/node_modules/prop-types/index.js
var prop_types = __webpack_require__(1);
var prop_types_default = /*#__PURE__*/__webpack_require__.n(prop_types);

// EXTERNAL MODULE: ./client/analytics/report/orders/config.js
var config = __webpack_require__(565);

// EXTERNAL MODULE: ./client/lib/get-selected-chart/index.js
var get_selected_chart = __webpack_require__(542);

// EXTERNAL MODULE: external ["wp","i18n"]
var external_wp_i18n_ = __webpack_require__(2);

// EXTERNAL MODULE: external "lodash"
var external_lodash_ = __webpack_require__(5);

// EXTERNAL MODULE: external ["wc","components"]
var external_wc_components_ = __webpack_require__(22);

// EXTERNAL MODULE: external ["wc","number"]
var external_wc_number_ = __webpack_require__(136);

// EXTERNAL MODULE: external ["wc","navigation"]
var external_wc_navigation_ = __webpack_require__(13);

// EXTERNAL MODULE: external ["wc","date"]
var external_wc_date_ = __webpack_require__(20);

// EXTERNAL MODULE: ./client/analytics/components/report-table/index.js + 2 modules
var report_table = __webpack_require__(545);

// EXTERNAL MODULE: ./client/utils/admin-settings.js
var admin_settings = __webpack_require__(23);

// EXTERNAL MODULE: ./client/lib/currency-context.js
var currency_context = __webpack_require__(536);

// CONCATENATED MODULE: ./client/analytics/report/orders/table.js


/**
 * External dependencies
 */







/**
 * Internal dependencies
 */





const capitalizeFirstLetter = expr => expr.charAt(0).toUpperCase() + expr.slice(1);

class table_OrdersReportTable extends external_wp_element_["Component"] {
  constructor() {
    super();
    this.getHeadersContent = this.getHeadersContent.bind(this);
    this.getRowsContent = this.getRowsContent.bind(this);
    this.getSummary = this.getSummary.bind(this);
  }

  getHeadersContent() {
    return [{
      label: Object(external_wp_i18n_["__"])('Date', 'woocommerce-admin'),
      key: 'date',
      required: true,
      defaultSort: true,
      isLeftAligned: true,
      isSortable: true
    }, {
      label: Object(external_wp_i18n_["__"])('Order #', 'woocommerce-admin'),
      screenReaderLabel: Object(external_wp_i18n_["__"])('Order Number', 'woocommerce-admin'),
      key: 'order_number',
      required: true
    }, {
      label: Object(external_wp_i18n_["__"])('Status', 'woocommerce-admin'),
      key: 'status',
      required: false,
      isSortable: false
    }, {
      label: Object(external_wp_i18n_["__"])('Customer', 'woocommerce-admin'),
      key: 'customer_id',
      required: false,
      isSortable: false
    }, {
      label: Object(external_wp_i18n_["__"])('Customer type', 'woocommerce-admin'),
      key: 'customer_type',
      required: false,
      isSortable: false
    }, {
      label: Object(external_wp_i18n_["__"])('Product(s)', 'woocommerce-admin'),
      screenReaderLabel: Object(external_wp_i18n_["__"])('Products', 'woocommerce-admin'),
      key: 'products',
      required: false,
      isSortable: false
    }, {
      label: Object(external_wp_i18n_["__"])('Items sold', 'woocommerce-admin'),
      key: 'num_items_sold',
      required: false,
      isSortable: true,
      isNumeric: true
    }, {
      label: Object(external_wp_i18n_["__"])('Coupon(s)', 'woocommerce-admin'),
      screenReaderLabel: Object(external_wp_i18n_["__"])('Coupons', 'woocommerce-admin'),
      key: 'coupons',
      required: false,
      isSortable: false
    }, {
      label: Object(external_wp_i18n_["__"])('Net sales', 'woocommerce-admin'),
      screenReaderLabel: Object(external_wp_i18n_["__"])('Net sales', 'woocommerce-admin'),
      key: 'net_total',
      required: true,
      isSortable: true,
      isNumeric: true
    }];
  }

  getCustomerName(customer) {
    const {
      first_name: firstName,
      last_name: lastName
    } = customer || {};

    if (!firstName && !lastName) {
      return '';
    }

    return [firstName, lastName].join(' ');
  }

  getRowsContent(tableData) {
    const {
      query
    } = this.props;
    const persistedQuery = Object(external_wc_navigation_["getPersistedQuery"])(query);
    const dateFormat = Object(admin_settings["d" /* getAdminSetting */])('dateFormat', external_wc_date_["defaultTableDateFormat"]);
    const {
      render: renderCurrency,
      getCurrencyConfig
    } = this.context;
    return Object(external_lodash_["map"])(tableData, row => {
      const {
        currency,
        date_created: dateCreated,
        net_total: netTotal,
        num_items_sold: numItemsSold,
        order_id: orderId,
        order_number: orderNumber,
        parent_id: parentId,
        status,
        customer_type: customerType
      } = row;
      const extendedInfo = row.extended_info || {};
      const {
        coupons,
        customer,
        products
      } = extendedInfo;
      const formattedProducts = products.sort((itemA, itemB) => itemB.quantity - itemA.quantity).map(item => ({
        label: item.name,
        quantity: item.quantity,
        href: Object(external_wc_navigation_["getNewPath"])(persistedQuery, '/analytics/products', {
          filter: 'single_product',
          products: item.id
        })
      }));
      const formattedCoupons = coupons.map(coupon => ({
        label: coupon.code,
        href: Object(external_wc_navigation_["getNewPath"])(persistedQuery, '/analytics/coupons', {
          filter: 'single_coupon',
          coupons: coupon.id
        })
      }));
      return [{
        display: Object(external_wp_element_["createElement"])(external_wc_components_["Date"], {
          date: dateCreated,
          visibleFormat: dateFormat
        }),
        value: dateCreated
      }, {
        display: Object(external_wp_element_["createElement"])(external_wc_components_["Link"], {
          href: 'post.php?post=' + (parentId ? parentId : orderId) + '&action=edit' + (parentId ? '#order_refunds' : ''),
          type: "wp-admin"
        }, orderNumber),
        value: orderNumber
      }, {
        display: Object(external_wp_element_["createElement"])(external_wc_components_["OrderStatus"], {
          className: "woocommerce-orders-table__status",
          order: {
            status
          },
          labelPositionToLeft: true,
          orderStatusMap: Object(admin_settings["d" /* getAdminSetting */])('orderStatuses', {})
        }),
        value: status
      }, {
        display: this.getCustomerName(customer),
        value: this.getCustomerName(customer)
      }, {
        display: capitalizeFirstLetter(customerType),
        value: customerType
      }, {
        display: this.renderList(formattedProducts.length ? [formattedProducts[0]] : [], formattedProducts.map(product => ({
          label: Object(external_wp_i18n_["sprintf"])(Object(external_wp_i18n_["__"])('%s× %s', 'woocommerce-admin'), product.quantity, product.label),
          href: product.href
        }))),
        value: formattedProducts.map(_ref => {
          let {
            quantity,
            label
          } = _ref;
          return Object(external_wp_i18n_["sprintf"])(Object(external_wp_i18n_["__"])('%s× %s', 'woocommerce-admin'), quantity, label);
        }).join(', ')
      }, {
        display: Object(external_wc_number_["formatValue"])(getCurrencyConfig(), 'number', numItemsSold),
        value: numItemsSold
      }, {
        display: this.renderList(formattedCoupons.length ? [formattedCoupons[0]] : [], formattedCoupons),
        value: formattedCoupons.map(coupon => coupon.label).join(', ')
      }, {
        display: renderCurrency(netTotal, currency),
        value: netTotal
      }];
    });
  }

  getSummary(totals) {
    const {
      orders_count: ordersCount = 0,
      total_customers: totalCustomers = 0,
      products = 0,
      num_items_sold: numItemsSold = 0,
      coupons_count: couponsCount = 0,
      net_revenue: netRevenue = 0
    } = totals;
    const {
      formatAmount,
      getCurrencyConfig
    } = this.context;
    const currency = getCurrencyConfig();
    return [{
      label: Object(external_wp_i18n_["_n"])('Order', 'Orders', ordersCount, 'woocommerce-admin'),
      value: Object(external_wc_number_["formatValue"])(currency, 'number', ordersCount)
    }, {
      label: Object(external_wp_i18n_["_n"])(' Customer', ' Customers', totalCustomers, 'woocommerce-admin'),
      value: Object(external_wc_number_["formatValue"])(currency, 'number', totalCustomers)
    }, {
      label: Object(external_wp_i18n_["_n"])('Product', 'Products', products, 'woocommerce-admin'),
      value: Object(external_wc_number_["formatValue"])(currency, 'number', products)
    }, {
      label: Object(external_wp_i18n_["_n"])('Item sold', 'Items sold', numItemsSold, 'woocommerce-admin'),
      value: Object(external_wc_number_["formatValue"])(currency, 'number', numItemsSold)
    }, {
      label: Object(external_wp_i18n_["_n"])('Coupon', 'Coupons', couponsCount, 'woocommerce-admin'),
      value: Object(external_wc_number_["formatValue"])(currency, 'number', couponsCount)
    }, {
      label: Object(external_wp_i18n_["__"])('net sales', 'woocommerce-admin'),
      value: formatAmount(netRevenue)
    }];
  }

  renderLinks() {
    let items = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : [];
    return items.map((item, i) => Object(external_wp_element_["createElement"])(external_wc_components_["Link"], {
      href: item.href,
      key: i,
      type: "wc-admin"
    }, item.label));
  }

  renderList(visibleItems, popoverItems) {
    return Object(external_wp_element_["createElement"])(external_wp_element_["Fragment"], null, this.renderLinks(visibleItems), popoverItems.length > 1 && Object(external_wp_element_["createElement"])(external_wc_components_["ViewMoreList"], {
      items: this.renderLinks(popoverItems)
    }));
  }

  render() {
    const {
      query,
      filters,
      advancedFilters
    } = this.props;
    return Object(external_wp_element_["createElement"])(report_table["a" /* default */], {
      endpoint: "orders",
      getHeadersContent: this.getHeadersContent,
      getRowsContent: this.getRowsContent,
      getSummary: this.getSummary,
      summaryFields: ['orders_count', 'total_customers', 'products', 'num_items_sold', 'coupons_count', 'net_revenue'],
      query: query,
      tableQuery: {
        extended_info: true
      },
      title: Object(external_wp_i18n_["__"])('Orders', 'woocommerce-admin'),
      columnPrefsKey: "orders_report_columns",
      filters: filters,
      advancedFilters: advancedFilters
    });
  }

}

table_OrdersReportTable.contextType = currency_context["a" /* CurrencyContext */];
/* harmony default export */ var table = (table_OrdersReportTable);
// EXTERNAL MODULE: ./client/analytics/components/report-chart/index.js + 1 modules
var report_chart = __webpack_require__(540);

// EXTERNAL MODULE: ./client/analytics/components/report-summary/index.js
var report_summary = __webpack_require__(543);

// EXTERNAL MODULE: ./client/analytics/components/report-filters/index.js
var report_filters = __webpack_require__(544);

// CONCATENATED MODULE: ./client/analytics/report/orders/index.js


/**
 * External dependencies
 */


/**
 * Internal dependencies
 */







class orders_OrdersReport extends external_wp_element_["Component"] {
  render() {
    const {
      path,
      query
    } = this.props;
    return Object(external_wp_element_["createElement"])(external_wp_element_["Fragment"], null, Object(external_wp_element_["createElement"])(report_filters["a" /* default */], {
      query: query,
      path: path,
      filters: config["c" /* filters */],
      advancedFilters: config["a" /* advancedFilters */],
      report: "orders"
    }), Object(external_wp_element_["createElement"])(report_summary["a" /* default */], {
      charts: config["b" /* charts */],
      endpoint: "orders",
      query: query,
      selectedChart: Object(get_selected_chart["a" /* default */])(query.chart, config["b" /* charts */]),
      filters: config["c" /* filters */],
      advancedFilters: config["a" /* advancedFilters */]
    }), Object(external_wp_element_["createElement"])(report_chart["a" /* default */], {
      charts: config["b" /* charts */],
      endpoint: "orders",
      path: path,
      query: query,
      selectedChart: Object(get_selected_chart["a" /* default */])(query.chart, config["b" /* charts */]),
      filters: config["c" /* filters */],
      advancedFilters: config["a" /* advancedFilters */]
    }), Object(external_wp_element_["createElement"])(table, {
      query: query,
      filters: config["c" /* filters */],
      advancedFilters: config["a" /* advancedFilters */]
    }));
  }

}
orders_OrdersReport.propTypes = {
  path: prop_types_default.a.string.isRequired,
  query: prop_types_default.a.object.isRequired
};

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

/***/ 542:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "a", function() { return getSelectedChart; });
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(5);
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
  let charts = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : [];
  const chart = Object(lodash__WEBPACK_IMPORTED_MODULE_0__["find"])(charts, {
    key: chartName
  });

  if (chart) {
    return chart;
  }

  return charts[0];
}

/***/ }),

/***/ 543:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* unused harmony export ReportSummary */
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(2);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(14);
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_compose__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(8);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(1);
/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(prop_types__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _woocommerce_navigation__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(13);
/* harmony import */ var _woocommerce_navigation__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(22);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_components__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var _woocommerce_number__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(136);
/* harmony import */ var _woocommerce_number__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_number__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var _woocommerce_data__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(12);
/* harmony import */ var _woocommerce_data__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_data__WEBPACK_IMPORTED_MODULE_8__);
/* harmony import */ var _woocommerce_date__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(20);
/* harmony import */ var _woocommerce_date__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_date__WEBPACK_IMPORTED_MODULE_9__);
/* harmony import */ var _woocommerce_tracks__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(17);
/* harmony import */ var _woocommerce_tracks__WEBPACK_IMPORTED_MODULE_10___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_tracks__WEBPACK_IMPORTED_MODULE_10__);
/* harmony import */ var _report_error__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(537);
/* harmony import */ var _lib_currency_context__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(536);


/**
 * External dependencies
 */











/**
 * Internal dependencies
 */



/**
 * Component to render summary numbers in reports.
 */

class ReportSummary extends _wordpress_element__WEBPACK_IMPORTED_MODULE_0__["Component"] {
  formatVal(val, type) {
    const {
      formatAmount,
      getCurrencyConfig
    } = this.context;
    return type === 'currency' ? formatAmount(val) : Object(_woocommerce_number__WEBPACK_IMPORTED_MODULE_7__["formatValue"])(getCurrencyConfig(), type, val);
  }

  getValues(key, type) {
    const {
      emptySearchResults,
      summaryData
    } = this.props;
    const {
      totals
    } = summaryData;
    const primaryTotal = totals.primary ? totals.primary[key] : 0;
    const secondaryTotal = totals.secondary ? totals.secondary[key] : 0;
    const primaryValue = emptySearchResults ? 0 : primaryTotal;
    const secondaryValue = emptySearchResults ? 0 : secondaryTotal;
    return {
      delta: Object(_woocommerce_number__WEBPACK_IMPORTED_MODULE_7__["calculateDelta"])(primaryValue, secondaryValue),
      prevValue: this.formatVal(secondaryValue, type),
      value: this.formatVal(primaryValue, type)
    };
  }

  render() {
    const {
      charts,
      query,
      selectedChart,
      summaryData,
      endpoint,
      report,
      defaultDateRange
    } = this.props;
    const {
      isError,
      isRequesting
    } = summaryData;

    if (isError) {
      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_report_error__WEBPACK_IMPORTED_MODULE_11__[/* default */ "a"], null);
    }

    if (isRequesting) {
      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_6__["SummaryListPlaceholder"], {
        numberOfItems: charts.length
      });
    }

    const {
      compare
    } = Object(_woocommerce_date__WEBPACK_IMPORTED_MODULE_9__["getDateParamsFromQuery"])(query, defaultDateRange);

    const renderSummaryNumbers = _ref => {
      let {
        onToggle
      } = _ref;
      return charts.map(chart => {
        const {
          key,
          order,
          orderby,
          label,
          type,
          isReverseTrend,
          labelTooltipText
        } = chart;
        const newPath = {
          chart: key
        };

        if (orderby) {
          newPath.orderby = orderby;
        }

        if (order) {
          newPath.order = order;
        }

        const href = Object(_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_5__["getNewPath"])(newPath);
        const isSelected = selectedChart.key === key;
        const {
          delta,
          prevValue,
          value
        } = this.getValues(key, type);
        return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_6__["SummaryNumber"], {
          key: key,
          delta: delta,
          href: href,
          label: label,
          reverseTrend: isReverseTrend,
          prevLabel: compare === 'previous_period' ? Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Previous period:', 'woocommerce-admin') : Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Previous year:', 'woocommerce-admin'),
          prevValue: prevValue,
          selected: isSelected,
          value: value,
          labelTooltipText: labelTooltipText,
          onLinkClickCallback: () => {
            // Wider than a certain breakpoint, there is no dropdown so avoid calling onToggle.
            if (onToggle) {
              onToggle();
            }

            Object(_woocommerce_tracks__WEBPACK_IMPORTED_MODULE_10__["recordEvent"])('analytics_chart_tab_click', {
              report: report || endpoint,
              key
            });
          }
        });
      });
    };

    return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_6__["SummaryList"], null, renderSummaryNumbers);
  }

}
ReportSummary.propTypes = {
  /**
   * Properties of all the charts available for that report.
   */
  charts: prop_types__WEBPACK_IMPORTED_MODULE_4___default.a.array.isRequired,

  /**
   * The endpoint to use in API calls to populate the Summary Numbers.
   * For example, if `taxes` is provided, data will be fetched from the report
   * `taxes` endpoint (ie: `/wc-analytics/reports/taxes/stats`). If the provided endpoint
   * doesn't exist, an error will be shown to the user with `ReportError`.
   */
  endpoint: prop_types__WEBPACK_IMPORTED_MODULE_4___default.a.string.isRequired,

  /**
   * Allows specifying properties different from the `endpoint` that will be used
   * to limit the items when there is an active search.
   */
  limitProperties: prop_types__WEBPACK_IMPORTED_MODULE_4___default.a.array,

  /**
   * The query string represented in object form.
   */
  query: prop_types__WEBPACK_IMPORTED_MODULE_4___default.a.object.isRequired,

  /**
   * Properties of the selected chart.
   */
  selectedChart: prop_types__WEBPACK_IMPORTED_MODULE_4___default.a.shape({
    /**
     * Key of the selected chart.
     */
    key: prop_types__WEBPACK_IMPORTED_MODULE_4___default.a.string.isRequired,

    /**
     * Chart label.
     */
    label: prop_types__WEBPACK_IMPORTED_MODULE_4___default.a.string.isRequired,

    /**
     * Order query argument.
     */
    order: prop_types__WEBPACK_IMPORTED_MODULE_4___default.a.oneOf(['asc', 'desc']),

    /**
     * Order by query argument.
     */
    orderby: prop_types__WEBPACK_IMPORTED_MODULE_4___default.a.string,

    /**
     * Number type for formatting.
     */
    type: prop_types__WEBPACK_IMPORTED_MODULE_4___default.a.oneOf(['average', 'number', 'currency']).isRequired
  }).isRequired,

  /**
   * Data to display in the SummaryNumbers.
   */
  summaryData: prop_types__WEBPACK_IMPORTED_MODULE_4___default.a.object,

  /**
   * Report name, if different than the endpoint.
   */
  report: prop_types__WEBPACK_IMPORTED_MODULE_4___default.a.string
};
ReportSummary.defaultProps = {
  summaryData: {
    totals: {
      primary: {},
      secondary: {}
    },
    isError: false
  }
};
ReportSummary.contextType = _lib_currency_context__WEBPACK_IMPORTED_MODULE_12__[/* CurrencyContext */ "a"];
/* harmony default export */ __webpack_exports__["a"] = (Object(_wordpress_compose__WEBPACK_IMPORTED_MODULE_2__["compose"])(Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_3__["withSelect"])((select, props) => {
  const {
    charts,
    endpoint,
    limitProperties,
    query,
    filters,
    advancedFilters
  } = props;
  const limitBy = limitProperties || [endpoint];
  const hasLimitByParam = limitBy.some(item => query[item] && query[item].length);

  if (query.search && !hasLimitByParam) {
    return {
      emptySearchResults: true
    };
  }

  const fields = charts && charts.map(chart => chart.key);
  const {
    woocommerce_default_date_range: defaultDateRange
  } = select(_woocommerce_data__WEBPACK_IMPORTED_MODULE_8__["SETTINGS_STORE_NAME"]).getSetting('wc_admin', 'wcAdminSettings');
  const summaryData = Object(_woocommerce_data__WEBPACK_IMPORTED_MODULE_8__["getSummaryNumbers"])({
    endpoint,
    query,
    select,
    limitBy,
    filters,
    advancedFilters,
    defaultDateRange,
    fields
  });
  return {
    summaryData,
    defaultDateRange
  };
}))(ReportSummary));

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

/***/ })

}]);