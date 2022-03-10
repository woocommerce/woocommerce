(window["__wcAdmin_webpackJsonp"] = window["__wcAdmin_webpackJsonp"] || []).push([[8],{

/***/ 511:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
// ESM COMPAT FLAG
__webpack_require__.r(__webpack_exports__);

// EXTERNAL MODULE: external ["wp","element"]
var external_wp_element_ = __webpack_require__(0);

// EXTERNAL MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/prop-types@15.8.1/node_modules/prop-types/index.js
var prop_types = __webpack_require__(1);
var prop_types_default = /*#__PURE__*/__webpack_require__.n(prop_types);

// EXTERNAL MODULE: external ["wp","i18n"]
var external_wp_i18n_ = __webpack_require__(2);

// EXTERNAL MODULE: external ["wp","hooks"]
var external_wp_hooks_ = __webpack_require__(27);

// EXTERNAL MODULE: external ["wp","data"]
var external_wp_data_ = __webpack_require__(8);

// EXTERNAL MODULE: ./client/lib/async-requests/index.js
var async_requests = __webpack_require__(538);

// EXTERNAL MODULE: ./client/customer-effort-score-tracks/data/constants.js
var constants = __webpack_require__(69);

// CONCATENATED MODULE: ./client/analytics/report/categories/config.js
/**
 * External dependencies
 */



/**
 * Internal dependencies
 */



const CATEGORY_REPORT_CHARTS_FILTER = 'woocommerce_admin_categories_report_charts';
const CATEGORY_REPORT_FILTERS_FILTER = 'woocommerce_admin_categories_report_filters';
const CATEGORY_REPORT_ADVANCED_FILTERS_FILTER = 'woocommerce_admin_category_report_advanced_filters';
const {
  addCesSurveyForAnalytics
} = Object(external_wp_data_["dispatch"])(constants["c" /* STORE_KEY */]);
/**
 * @typedef {import('../index.js').chart} chart
 */

/**
 * Category Report charts filter.
 *
 * @filter woocommerce_admin_categories_report_charts
 * @param {Array.<chart>} charts Category Report charts.
 */

const charts = Object(external_wp_hooks_["applyFilters"])(CATEGORY_REPORT_CHARTS_FILTER, [{
  key: 'items_sold',
  label: Object(external_wp_i18n_["__"])('Items sold', 'woocommerce-admin'),
  order: 'desc',
  orderby: 'items_sold',
  type: 'number'
}, {
  key: 'net_revenue',
  label: Object(external_wp_i18n_["__"])('Net sales', 'woocommerce-admin'),
  order: 'desc',
  orderby: 'net_revenue',
  type: 'currency'
}, {
  key: 'orders_count',
  label: Object(external_wp_i18n_["__"])('Orders', 'woocommerce-admin'),
  order: 'desc',
  orderby: 'orders_count',
  type: 'number'
}]);
/**
 * Category Report Advanced Filters.
 *
 * @filter woocommerce_admin_category_report_advanced_filters
 * @param {Object} advancedFilters Report Advanced Filters.
 * @param {string} advancedFilters.title Interpolated component string for Advanced Filters title.
 * @param {Object} advancedFilters.filters An object specifying a report's Advanced Filters.
 */

const config_advancedFilters = Object(external_wp_hooks_["applyFilters"])(CATEGORY_REPORT_ADVANCED_FILTERS_FILTER, {
  filters: {},
  title: Object(external_wp_i18n_["_x"])('Categories match {{select /}} filters', 'A sentence describing filters for Categories. See screen shot for context: https://cloudup.com/cSsUY9VeCVJ', 'woocommerce-admin')
});
const filterValues = [{
  label: Object(external_wp_i18n_["__"])('All categories', 'woocommerce-admin'),
  value: 'all'
}, {
  label: Object(external_wp_i18n_["__"])('Single category', 'woocommerce-admin'),
  value: 'select_category',
  chartMode: 'item-comparison',
  subFilters: [{
    component: 'Search',
    value: 'single_category',
    chartMode: 'item-comparison',
    path: ['select_category'],
    settings: {
      type: 'categories',
      param: 'categories',
      getLabels: async_requests["a" /* getCategoryLabels */],
      labels: {
        placeholder: Object(external_wp_i18n_["__"])('Type to search for a category', 'woocommerce-admin'),
        button: Object(external_wp_i18n_["__"])('Single Category', 'woocommerce-admin')
      }
    }
  }]
}, {
  label: Object(external_wp_i18n_["__"])('Comparison', 'woocommerce-admin'),
  value: 'compare-categories',
  chartMode: 'item-comparison',
  settings: {
    type: 'categories',
    param: 'categories',
    getLabels: async_requests["a" /* getCategoryLabels */],
    labels: {
      helpText: Object(external_wp_i18n_["__"])('Check at least two categories below to compare', 'woocommerce-admin'),
      placeholder: Object(external_wp_i18n_["__"])('Search for categories to compare', 'woocommerce-admin'),
      title: Object(external_wp_i18n_["__"])('Compare Categories', 'woocommerce-admin'),
      update: Object(external_wp_i18n_["__"])('Compare', 'woocommerce-admin')
    },
    onClick: addCesSurveyForAnalytics
  }
}];

if (Object.keys(config_advancedFilters.filters).length) {
  filterValues.push({
    label: Object(external_wp_i18n_["__"])('Advanced filters', 'woocommerce-admin'),
    value: 'advanced'
  });
}
/**
 * @typedef {import('../index.js').filter} filter
 */

/**
 * Category Report Filters.
 *
 * @filter woocommerce_admin_categories_report_filters
 * @param {Array.<filter>} filters Report filters.
 */


const config_filters = Object(external_wp_hooks_["applyFilters"])(CATEGORY_REPORT_FILTERS_FILTER, [{
  label: Object(external_wp_i18n_["__"])('Show', 'woocommerce-admin'),
  staticParams: ['chartType', 'paged', 'per_page'],
  param: 'filter',
  showFilters: () => true,
  filters: filterValues
}]);
// EXTERNAL MODULE: external ["wp","compose"]
var external_wp_compose_ = __webpack_require__(14);

// EXTERNAL MODULE: external "lodash"
var external_lodash_ = __webpack_require__(5);

// EXTERNAL MODULE: external ["wc","navigation"]
var external_wc_navigation_ = __webpack_require__(13);

// EXTERNAL MODULE: external ["wc","components"]
var external_wc_components_ = __webpack_require__(22);

// EXTERNAL MODULE: external ["wc","number"]
var external_wc_number_ = __webpack_require__(136);

// EXTERNAL MODULE: external ["wc","data"]
var external_wc_data_ = __webpack_require__(12);

// EXTERNAL MODULE: ./client/analytics/report/categories/breadcrumbs.js
var breadcrumbs = __webpack_require__(571);

// EXTERNAL MODULE: ./client/analytics/components/report-table/index.js + 2 modules
var report_table = __webpack_require__(545);

// EXTERNAL MODULE: ./client/lib/currency-context.js
var currency_context = __webpack_require__(536);

// CONCATENATED MODULE: ./client/analytics/report/categories/table.js


/**
 * External dependencies
 */









/**
 * Internal dependencies
 */





class table_CategoriesReportTable extends external_wp_element_["Component"] {
  constructor(props) {
    super(props);
    this.getRowsContent = this.getRowsContent.bind(this);
    this.getSummary = this.getSummary.bind(this);
  }

  getHeadersContent() {
    return [{
      label: Object(external_wp_i18n_["__"])('Category', 'woocommerce-admin'),
      key: 'category',
      required: true,
      isSortable: true,
      isLeftAligned: true
    }, {
      label: Object(external_wp_i18n_["__"])('Items sold', 'woocommerce-admin'),
      key: 'items_sold',
      required: true,
      defaultSort: true,
      isSortable: true,
      isNumeric: true
    }, {
      label: Object(external_wp_i18n_["__"])('Net sales', 'woocommerce-admin'),
      key: 'net_revenue',
      isSortable: true,
      isNumeric: true
    }, {
      label: Object(external_wp_i18n_["__"])('Products', 'woocommerce-admin'),
      key: 'products_count',
      isSortable: true,
      isNumeric: true
    }, {
      label: Object(external_wp_i18n_["__"])('Orders', 'woocommerce-admin'),
      key: 'orders_count',
      isSortable: true,
      isNumeric: true
    }];
  }

  getRowsContent(categoryStats) {
    const {
      render: renderCurrency,
      formatDecimal: getCurrencyFormatDecimal,
      getCurrencyConfig
    } = this.context;
    const {
      categories,
      query
    } = this.props;

    if (!categories) {
      return [];
    }

    const currency = getCurrencyConfig();
    return Object(external_lodash_["map"])(categoryStats, categoryStat => {
      const {
        category_id: categoryId,
        items_sold: itemsSold,
        net_revenue: netRevenue,
        products_count: productsCount,
        orders_count: ordersCount
      } = categoryStat;
      const category = categories.get(categoryId);
      const persistedQuery = Object(external_wc_navigation_["getPersistedQuery"])(query);
      return [{
        display: Object(external_wp_element_["createElement"])(breadcrumbs["a" /* default */], {
          query: query,
          category: category,
          categories: categories
        }),
        value: category && category.name
      }, {
        display: Object(external_wc_number_["formatValue"])(currency, 'number', itemsSold),
        value: itemsSold
      }, {
        display: renderCurrency(netRevenue),
        value: getCurrencyFormatDecimal(netRevenue)
      }, {
        display: category && Object(external_wp_element_["createElement"])(external_wc_components_["Link"], {
          href: Object(external_wc_navigation_["getNewPath"])(persistedQuery, '/analytics/categories', {
            filter: 'single_category',
            categories: category.id
          }),
          type: "wc-admin"
        }, Object(external_wc_number_["formatValue"])(currency, 'number', productsCount)),
        value: productsCount
      }, {
        display: Object(external_wc_number_["formatValue"])(currency, 'number', ordersCount),
        value: ordersCount
      }];
    });
  }

  getSummary(totals) {
    let totalResults = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : 0;
    const {
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
      label: Object(external_wp_i18n_["_n"])('Category', 'Categories', totalResults, 'woocommerce-admin'),
      value: Object(external_wc_number_["formatValue"])(currency, 'number', totalResults)
    }, {
      label: Object(external_wp_i18n_["_n"])('Item sold', 'Items sold', itemsSold, 'woocommerce-admin'),
      value: Object(external_wc_number_["formatValue"])(currency, 'number', itemsSold)
    }, {
      label: Object(external_wp_i18n_["__"])('Net sales', 'woocommerce-admin'),
      value: formatAmount(netRevenue)
    }, {
      label: Object(external_wp_i18n_["_n"])('Order', 'Orders', ordersCount, 'woocommerce-admin'),
      value: Object(external_wc_number_["formatValue"])(currency, 'number', ordersCount)
    }];
  }

  render() {
    const {
      advancedFilters,
      filters,
      isRequesting,
      query
    } = this.props;
    const labels = {
      helpText: Object(external_wp_i18n_["__"])('Check at least two categories below to compare', 'woocommerce-admin'),
      placeholder: Object(external_wp_i18n_["__"])('Search by category name', 'woocommerce-admin')
    };
    return Object(external_wp_element_["createElement"])(report_table["a" /* default */], {
      compareBy: "categories",
      endpoint: "categories",
      getHeadersContent: this.getHeadersContent,
      getRowsContent: this.getRowsContent,
      getSummary: this.getSummary,
      summaryFields: ['items_sold', 'net_revenue', 'orders_count'],
      isRequesting: isRequesting,
      itemIdField: "category_id",
      query: query,
      searchBy: "categories",
      labels: labels,
      tableQuery: {
        orderby: query.orderby || 'items_sold',
        order: query.order || 'desc',
        extended_info: true
      },
      title: Object(external_wp_i18n_["__"])('Categories', 'woocommerce-admin'),
      columnPrefsKey: "categories_report_columns",
      filters: filters,
      advancedFilters: advancedFilters
    });
  }

}

table_CategoriesReportTable.contextType = currency_context["a" /* CurrencyContext */];
/* harmony default export */ var table = (Object(external_wp_compose_["compose"])(Object(external_wp_data_["withSelect"])((select, props) => {
  const {
    isRequesting,
    query
  } = props;

  if (isRequesting || query.search && !(query.categories && query.categories.length)) {
    return {};
  }

  const {
    getItems,
    getItemsError,
    isResolving
  } = select(external_wc_data_["ITEMS_STORE_NAME"]);
  const tableQuery = {
    per_page: -1
  };
  const categories = getItems('categories', tableQuery);
  const isCategoriesError = Boolean(getItemsError('categories', tableQuery));
  const isCategoriesRequesting = isResolving('getItems', ['categories', tableQuery]);
  return {
    categories,
    isError: isCategoriesError,
    isRequesting: isCategoriesRequesting
  };
}))(table_CategoriesReportTable));
// EXTERNAL MODULE: ./client/lib/get-selected-chart/index.js
var get_selected_chart = __webpack_require__(542);

// EXTERNAL MODULE: ./client/analytics/components/report-chart/index.js + 1 modules
var report_chart = __webpack_require__(540);

// EXTERNAL MODULE: ./client/analytics/components/report-summary/index.js
var report_summary = __webpack_require__(543);

// EXTERNAL MODULE: ./client/analytics/report/products/table.js
var products_table = __webpack_require__(570);

// EXTERNAL MODULE: ./client/analytics/components/report-filters/index.js
var report_filters = __webpack_require__(544);

// CONCATENATED MODULE: ./client/analytics/report/categories/index.js


/**
 * External dependencies
 */



/**
 * Internal dependencies
 */









class categories_CategoriesReport extends external_wp_element_["Component"] {
  getChartMeta() {
    const {
      query
    } = this.props;
    const isCompareView = query.filter === 'compare-categories' && query.categories && query.categories.split(',').length > 1;
    const isSingleCategoryView = query.filter === 'single_category' && !!query.categories;
    const mode = isCompareView || isSingleCategoryView ? 'item-comparison' : 'time-comparison';
    const itemsLabel = isSingleCategoryView ? Object(external_wp_i18n_["__"])('%d products', 'woocommerce-admin') : Object(external_wp_i18n_["__"])('%d categories', 'woocommerce-admin');
    return {
      isSingleCategoryView,
      itemsLabel,
      mode
    };
  }

  render() {
    const {
      isRequesting,
      query,
      path
    } = this.props;
    const {
      mode,
      itemsLabel,
      isSingleCategoryView
    } = this.getChartMeta();
    const chartQuery = { ...query
    };

    if (mode === 'item-comparison') {
      chartQuery.segmentby = isSingleCategoryView ? 'product' : 'category';
    }

    return Object(external_wp_element_["createElement"])(external_wp_element_["Fragment"], null, Object(external_wp_element_["createElement"])(report_filters["a" /* default */], {
      query: query,
      path: path,
      filters: config_filters,
      advancedFilters: config_advancedFilters,
      report: "categories"
    }), Object(external_wp_element_["createElement"])(report_summary["a" /* default */], {
      charts: charts,
      endpoint: "products",
      isRequesting: isRequesting,
      limitProperties: isSingleCategoryView ? ['products', 'categories'] : ['categories'],
      query: chartQuery,
      selectedChart: Object(get_selected_chart["a" /* default */])(query.chart, charts),
      filters: config_filters,
      advancedFilters: config_advancedFilters,
      report: "categories"
    }), Object(external_wp_element_["createElement"])(report_chart["a" /* default */], {
      charts: charts,
      filters: config_filters,
      advancedFilters: config_advancedFilters,
      mode: mode,
      endpoint: "products",
      limitProperties: isSingleCategoryView ? ['products', 'categories'] : ['categories'],
      path: path,
      query: chartQuery,
      isRequesting: isRequesting,
      itemsLabel: itemsLabel,
      selectedChart: Object(get_selected_chart["a" /* default */])(query.chart, charts)
    }), isSingleCategoryView ? Object(external_wp_element_["createElement"])(products_table["a" /* default */], {
      isRequesting: isRequesting,
      query: chartQuery,
      baseSearchQuery: {
        filter: 'single_category'
      },
      hideCompare: isSingleCategoryView,
      filters: config_filters,
      advancedFilters: config_advancedFilters
    }) : Object(external_wp_element_["createElement"])(table, {
      isRequesting: isRequesting,
      query: query,
      filters: config_filters,
      advancedFilters: config_advancedFilters
    }));
  }

}

categories_CategoriesReport.propTypes = {
  query: prop_types_default.a.object.isRequired,
  path: prop_types_default.a.string.isRequired
};
/* harmony default export */ var report_categories = __webpack_exports__["default"] = (categories_CategoriesReport);

/***/ })

}]);