(window["__wcAdmin_webpackJsonp"] = window["__wcAdmin_webpackJsonp"] || []).push([[13],{

/***/ 509:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(2);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(14);
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_compose__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(1);
/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(prop_types__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _woocommerce_data__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(12);
/* harmony import */ var _woocommerce_data__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_data__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(8);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _config__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(563);
/* harmony import */ var _lib_get_selected_chart__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(542);
/* harmony import */ var _table__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(570);
/* harmony import */ var _components_report_chart__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(540);
/* harmony import */ var _components_report_error__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(537);
/* harmony import */ var _components_report_summary__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(543);
/* harmony import */ var _variations_table__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(564);
/* harmony import */ var _components_report_filters__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(544);


/**
 * External dependencies
 */






/**
 * Internal dependencies
 */










class ProductsReport extends _wordpress_element__WEBPACK_IMPORTED_MODULE_0__["Component"] {
  getChartMeta() {
    const {
      query,
      isSingleProductView,
      isSingleProductVariable
    } = this.props;
    const isCompareView = query.filter === 'compare-products' && query.products && query.products.split(',').length > 1;
    const mode = isCompareView || isSingleProductView && isSingleProductVariable ? 'item-comparison' : 'time-comparison';
    const compareObject = isSingleProductView && isSingleProductVariable ? 'variations' : 'products';
    const label = isSingleProductView && isSingleProductVariable ? Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('%d variations', 'woocommerce-admin') : Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('%d products', 'woocommerce-admin');
    return {
      compareObject,
      itemsLabel: label,
      mode
    };
  }

  render() {
    const {
      compareObject,
      itemsLabel,
      mode
    } = this.getChartMeta();
    const {
      path,
      query,
      isError,
      isRequesting,
      isSingleProductVariable
    } = this.props;

    if (isError) {
      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_components_report_error__WEBPACK_IMPORTED_MODULE_10__[/* default */ "a"], null);
    }

    const chartQuery = { ...query
    };

    if (mode === 'item-comparison') {
      chartQuery.segmentby = compareObject === 'products' ? 'product' : 'variation';
    }

    return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["Fragment"], null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_components_report_filters__WEBPACK_IMPORTED_MODULE_13__[/* default */ "a"], {
      query: query,
      path: path,
      filters: _config__WEBPACK_IMPORTED_MODULE_6__[/* filters */ "c"],
      advancedFilters: _config__WEBPACK_IMPORTED_MODULE_6__[/* advancedFilters */ "a"],
      report: "products"
    }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_components_report_summary__WEBPACK_IMPORTED_MODULE_11__[/* default */ "a"], {
      mode: mode,
      charts: _config__WEBPACK_IMPORTED_MODULE_6__[/* charts */ "b"],
      endpoint: "products",
      isRequesting: isRequesting,
      query: chartQuery,
      selectedChart: Object(_lib_get_selected_chart__WEBPACK_IMPORTED_MODULE_7__[/* default */ "a"])(query.chart, _config__WEBPACK_IMPORTED_MODULE_6__[/* charts */ "b"]),
      filters: _config__WEBPACK_IMPORTED_MODULE_6__[/* filters */ "c"],
      advancedFilters: _config__WEBPACK_IMPORTED_MODULE_6__[/* advancedFilters */ "a"]
    }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_components_report_chart__WEBPACK_IMPORTED_MODULE_9__[/* default */ "a"], {
      charts: _config__WEBPACK_IMPORTED_MODULE_6__[/* charts */ "b"],
      mode: mode,
      filters: _config__WEBPACK_IMPORTED_MODULE_6__[/* filters */ "c"],
      advancedFilters: _config__WEBPACK_IMPORTED_MODULE_6__[/* advancedFilters */ "a"],
      endpoint: "products",
      isRequesting: isRequesting,
      itemsLabel: itemsLabel,
      path: path,
      query: chartQuery,
      selectedChart: Object(_lib_get_selected_chart__WEBPACK_IMPORTED_MODULE_7__[/* default */ "a"])(chartQuery.chart, _config__WEBPACK_IMPORTED_MODULE_6__[/* charts */ "b"])
    }), isSingleProductVariable ? Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_variations_table__WEBPACK_IMPORTED_MODULE_12__[/* default */ "a"], {
      baseSearchQuery: {
        filter: 'single_product'
      },
      isRequesting: isRequesting,
      query: query,
      filters: _config__WEBPACK_IMPORTED_MODULE_6__[/* filters */ "c"],
      advancedFilters: _config__WEBPACK_IMPORTED_MODULE_6__[/* advancedFilters */ "a"]
    }) : Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_table__WEBPACK_IMPORTED_MODULE_8__[/* default */ "a"], {
      isRequesting: isRequesting,
      query: query,
      filters: _config__WEBPACK_IMPORTED_MODULE_6__[/* filters */ "c"],
      advancedFilters: _config__WEBPACK_IMPORTED_MODULE_6__[/* advancedFilters */ "a"]
    }));
  }

}

ProductsReport.propTypes = {
  path: prop_types__WEBPACK_IMPORTED_MODULE_3___default.a.string.isRequired,
  query: prop_types__WEBPACK_IMPORTED_MODULE_3___default.a.object.isRequired
};
/* harmony default export */ __webpack_exports__["default"] = (Object(_wordpress_compose__WEBPACK_IMPORTED_MODULE_2__["compose"])(Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_5__["withSelect"])((select, props) => {
  const {
    query,
    isRequesting
  } = props;
  const isSingleProductView = !query.search && query.products && query.products.split(',').length === 1;
  const {
    getItems,
    isResolving,
    getItemsError
  } = select(_woocommerce_data__WEBPACK_IMPORTED_MODULE_4__["ITEMS_STORE_NAME"]);

  if (isRequesting) {
    return {
      query: { ...query
      },
      isSingleProductView,
      isRequesting
    };
  }

  if (isSingleProductView) {
    const productId = parseInt(query.products, 10);
    const includeArgs = {
      include: productId
    }; // TODO Look at similar usage to populate tags in the Search component.

    const products = getItems('products', includeArgs);
    const isVariable = products && products.get(productId) && products.get(productId).type === 'variable';
    const isProductsRequesting = isResolving('getItems', ['products', includeArgs]);
    const isProductsError = Boolean(getItemsError('products', includeArgs));
    return {
      query: { ...query,
        'is-variable': isVariable
      },
      isSingleProductView,
      isRequesting: isProductsRequesting,
      isSingleProductVariable: isVariable,
      isError: isProductsError
    };
  }

  return {
    query,
    isSingleProductView
  };
}))(ProductsReport));

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

/***/ 564:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(2);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(5);
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(22);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_components__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _woocommerce_navigation__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(13);
/* harmony import */ var _woocommerce_navigation__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _woocommerce_number__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(136);
/* harmony import */ var _woocommerce_number__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_number__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _woocommerce_settings__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(15);
/* harmony import */ var _woocommerce_settings__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_settings__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var _components_report_table__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(545);
/* harmony import */ var _products_utils__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(554);
/* harmony import */ var _lib_currency_context__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(536);
/* harmony import */ var _lib_async_requests__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(538);
/* harmony import */ var _utils_admin_settings__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(23);


/**
 * External dependencies
 */







/**
 * Internal dependencies
 */






const manageStock = Object(_utils_admin_settings__WEBPACK_IMPORTED_MODULE_11__[/* getAdminSetting */ "d"])('manageStock', 'no');
const stockStatuses = Object(_utils_admin_settings__WEBPACK_IMPORTED_MODULE_11__[/* getAdminSetting */ "d"])('stockStatuses', {});

const getFullVariationName = rowData => Object(_lib_async_requests__WEBPACK_IMPORTED_MODULE_10__[/* getVariationName */ "h"])(rowData.extended_info || {});

class VariationsReportTable extends _wordpress_element__WEBPACK_IMPORTED_MODULE_0__["Component"] {
  constructor() {
    super();
    this.getHeadersContent = this.getHeadersContent.bind(this);
    this.getRowsContent = this.getRowsContent.bind(this);
    this.getSummary = this.getSummary.bind(this);
  }

  getHeadersContent() {
    return [{
      label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Product / Variation title', 'woocommerce-admin'),
      key: 'name',
      required: true,
      isLeftAligned: true
    }, {
      label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('SKU', 'woocommerce-admin'),
      key: 'sku',
      hiddenByDefault: true,
      isSortable: true
    }, {
      label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Items sold', 'woocommerce-admin'),
      key: 'items_sold',
      required: true,
      defaultSort: true,
      isSortable: true,
      isNumeric: true
    }, {
      label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Net sales', 'woocommerce-admin'),
      screenReaderLabel: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Net sales', 'woocommerce-admin'),
      key: 'net_revenue',
      required: true,
      isSortable: true,
      isNumeric: true
    }, {
      label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Orders', 'woocommerce-admin'),
      key: 'orders_count',
      isSortable: true,
      isNumeric: true
    }, manageStock === 'yes' ? {
      label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Status', 'woocommerce-admin'),
      key: 'stock_status'
    } : null, manageStock === 'yes' ? {
      label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Stock', 'woocommerce-admin'),
      key: 'stock',
      isNumeric: true
    } : null].filter(Boolean);
  }

  getRowsContent() {
    let data = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : [];
    const {
      query
    } = this.props;
    const persistedQuery = Object(_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_4__["getPersistedQuery"])(query);
    const {
      formatAmount,
      formatDecimal: getCurrencyFormatDecimal,
      getCurrencyConfig
    } = this.context;
    return Object(lodash__WEBPACK_IMPORTED_MODULE_2__["map"])(data, row => {
      const {
        items_sold: itemsSold,
        net_revenue: netRevenue,
        orders_count: ordersCount,
        product_id: productId,
        variation_id: variationId
      } = row;
      const extendedInfo = row.extended_info || {};
      const {
        stock_status: stockStatus,
        stock_quantity: stockQuantity,
        low_stock_amount: lowStockAmount,
        deleted,
        sku
      } = extendedInfo;
      const name = getFullVariationName(row);
      const ordersLink = Object(_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_4__["getNewPath"])(persistedQuery, '/analytics/orders', {
        filter: 'advanced',
        variation_includes: variationId
      });
      const editPostLink = Object(_woocommerce_settings__WEBPACK_IMPORTED_MODULE_6__["getAdminLink"])(`post.php?post=${productId}&action=edit`);
      return [{
        display: deleted ? name + ' ' + Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('(Deleted)', ' woocommerce-admin') : Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_3__["Link"], {
          href: editPostLink,
          type: "wp-admin"
        }, name),
        value: name
      }, {
        display: sku,
        value: sku
      }, {
        display: Object(_woocommerce_number__WEBPACK_IMPORTED_MODULE_5__["formatValue"])(getCurrencyConfig(), 'number', itemsSold),
        value: itemsSold
      }, {
        display: formatAmount(netRevenue),
        value: getCurrencyFormatDecimal(netRevenue)
      }, {
        display: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_3__["Link"], {
          href: ordersLink,
          type: "wc-admin"
        }, ordersCount),
        value: ordersCount
      }, manageStock === 'yes' ? {
        display: Object(_products_utils__WEBPACK_IMPORTED_MODULE_8__[/* isLowStock */ "a"])(stockStatus, stockQuantity, lowStockAmount) ? Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_3__["Link"], {
          href: editPostLink,
          type: "wp-admin"
        }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["_x"])('Low', 'Indication of a low quantity', 'woocommerce-admin')) : stockStatuses[stockStatus],
        value: stockStatuses[stockStatus]
      } : null, manageStock === 'yes' ? {
        display: stockQuantity,
        value: stockQuantity
      } : null].filter(Boolean);
    });
  }

  getSummary(totals) {
    const {
      variations_count: variationsCount = 0,
      items_sold: itemsSold = 0,
      net_revenue: netRevenue = 0,
      orders_count: ordersCount = 0
    } = totals;
    const {
      formatAmount,
      getCurrencyConfig
    } = this.context;
    const currency = getCurrencyConfig();
    return [{
      label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["_n"])('variation sold', 'variations sold', variationsCount, 'woocommerce-admin'),
      value: Object(_woocommerce_number__WEBPACK_IMPORTED_MODULE_5__["formatValue"])(currency, 'number', variationsCount)
    }, {
      label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["_n"])('item sold', 'items sold', itemsSold, 'woocommerce-admin'),
      value: Object(_woocommerce_number__WEBPACK_IMPORTED_MODULE_5__["formatValue"])(currency, 'number', itemsSold)
    }, {
      label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('net sales', 'woocommerce-admin'),
      value: formatAmount(netRevenue)
    }, {
      label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["_n"])('orders', 'orders', ordersCount, 'woocommerce-admin'),
      value: Object(_woocommerce_number__WEBPACK_IMPORTED_MODULE_5__["formatValue"])(currency, 'number', ordersCount)
    }];
  }

  render() {
    const {
      advancedFilters,
      baseSearchQuery,
      filters,
      isRequesting,
      query
    } = this.props;
    const labels = {
      helpText: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Check at least two variations below to compare', 'woocommerce-admin'),
      placeholder: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Search by variation name or SKU', 'woocommerce-admin')
    };
    return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_components_report_table__WEBPACK_IMPORTED_MODULE_7__[/* default */ "a"], {
      baseSearchQuery: baseSearchQuery,
      compareBy: "variations",
      compareParam: "filter-variations",
      endpoint: "variations",
      getHeadersContent: this.getHeadersContent,
      getRowsContent: this.getRowsContent,
      isRequesting: isRequesting,
      itemIdField: "variation_id",
      labels: labels,
      query: query,
      getSummary: this.getSummary,
      summaryFields: ['variations_count', 'items_sold', 'net_revenue', 'orders_count'],
      tableQuery: {
        orderby: query.orderby || 'items_sold',
        order: query.order || 'desc',
        extended_info: true,
        product_includes: query.product_includes,
        variations: query.variations
      },
      title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Variations', 'woocommerce-admin'),
      columnPrefsKey: "variations_report_columns",
      filters: filters,
      advancedFilters: advancedFilters
    });
  }

}

VariationsReportTable.contextType = _lib_currency_context__WEBPACK_IMPORTED_MODULE_9__[/* CurrencyContext */ "a"];
/* harmony default export */ __webpack_exports__["a"] = (VariationsReportTable);

/***/ })

}]);