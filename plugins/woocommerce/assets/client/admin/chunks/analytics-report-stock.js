(window["__wcAdmin_webpackJsonp"] = window["__wcAdmin_webpackJsonp"] || []).push([[15],{

/***/ 510:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
// ESM COMPAT FLAG
__webpack_require__.r(__webpack_exports__);

// EXPORTS
__webpack_require__.d(__webpack_exports__, "default", function() { return /* binding */ stock_StockReport; });

// EXTERNAL MODULE: external ["wp","element"]
var external_wp_element_ = __webpack_require__(0);

// EXTERNAL MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/prop-types@15.8.1/node_modules/prop-types/index.js
var prop_types = __webpack_require__(1);
var prop_types_default = /*#__PURE__*/__webpack_require__.n(prop_types);

// EXTERNAL MODULE: external ["wp","i18n"]
var external_wp_i18n_ = __webpack_require__(2);

// EXTERNAL MODULE: external ["wp","hooks"]
var external_wp_hooks_ = __webpack_require__(27);

// CONCATENATED MODULE: ./client/analytics/report/stock/config.js
/**
 * External dependencies
 */


const STOCK_REPORT_FILTERS_FILTER = 'woocommerce_admin_stock_report_filters';
const STOCK_REPORT_ADVANCED_FILTERS_FILTER = 'woocommerce_admin_stock_report_advanced_filters';
const showDatePicker = false;
/**
 * Stock Report Advanced Filters.
 *
 * @filter woocommerce_admin_stock_report_advanced_filters
 * @param {Object} advancedFilters Report Advanced Filters.
 * @param {string} advancedFilters.title Interpolated component string for Advanced Filters title.
 * @param {Object} advancedFilters.filters An object specifying a report's Advanced Filters.
 */

const config_advancedFilters = Object(external_wp_hooks_["applyFilters"])(STOCK_REPORT_ADVANCED_FILTERS_FILTER, {
  filters: {},
  title: Object(external_wp_i18n_["_x"])('Products Match {{select /}} Filters', 'A sentence describing filters for Products. See screen shot for context: https://cloudup.com/cSsUY9VeCVJ', 'woocommerce-admin')
});
/**
 * @typedef {import('../index.js').filter} filter
 */

/**
 * Stock Report Filters.
 *
 * @filter woocommerce_admin_stock_report_filters
 * @param {Array.<filter>} filters Report filters.
 */

const config_filters = Object(external_wp_hooks_["applyFilters"])(STOCK_REPORT_FILTERS_FILTER, [{
  label: Object(external_wp_i18n_["__"])('Show', 'woocommerce-admin'),
  staticParams: ['paged', 'per_page'],
  param: 'type',
  showFilters: () => true,
  filters: [{
    label: Object(external_wp_i18n_["__"])('All products', 'woocommerce-admin'),
    value: 'all'
  }, {
    label: Object(external_wp_i18n_["__"])('Out of stock', 'woocommerce-admin'),
    value: 'outofstock'
  }, {
    label: Object(external_wp_i18n_["__"])('Low stock', 'woocommerce-admin'),
    value: 'lowstock'
  }, {
    label: Object(external_wp_i18n_["__"])('In stock', 'woocommerce-admin'),
    value: 'instock'
  }, {
    label: Object(external_wp_i18n_["__"])('On backorder', 'woocommerce-admin'),
    value: 'onbackorder'
  }]
}, {
  label: Object(external_wp_i18n_["__"])('Filter by', 'woocommerce-admin'),
  staticParams: ['paged', 'per_page'],
  param: 'filter',
  showFilters: () => Object.keys(config_advancedFilters.filters).length,
  filters: [{
    label: Object(external_wp_i18n_["__"])('All Products', 'woocommerce-admin'),
    value: 'all'
  }, {
    label: Object(external_wp_i18n_["__"])('Advanced Filters', 'woocommerce-admin'),
    value: 'advanced'
  }]
}]);
// EXTERNAL MODULE: external ["wp","htmlEntities"]
var external_wp_htmlEntities_ = __webpack_require__(34);

// EXTERNAL MODULE: external ["wc","components"]
var external_wc_components_ = __webpack_require__(22);

// EXTERNAL MODULE: external ["wc","navigation"]
var external_wc_navigation_ = __webpack_require__(13);

// EXTERNAL MODULE: external ["wc","number"]
var external_wc_number_ = __webpack_require__(136);

// EXTERNAL MODULE: external ["wc","wcSettings"]
var external_wc_wcSettings_ = __webpack_require__(15);

// EXTERNAL MODULE: ./client/analytics/components/report-table/index.js + 2 modules
var report_table = __webpack_require__(545);

// CONCATENATED MODULE: ./client/analytics/report/stock/utils.js
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
// EXTERNAL MODULE: ./client/lib/currency-context.js
var currency_context = __webpack_require__(536);

// EXTERNAL MODULE: ./client/utils/admin-settings.js
var admin_settings = __webpack_require__(23);

// CONCATENATED MODULE: ./client/analytics/report/stock/table.js


/**
 * External dependencies
 */







/**
 * Internal dependencies
 */





const stockStatuses = Object(admin_settings["d" /* getAdminSetting */])('stockStatuses', {});

class table_StockReportTable extends external_wp_element_["Component"] {
  constructor() {
    super();
    this.getHeadersContent = this.getHeadersContent.bind(this);
    this.getRowsContent = this.getRowsContent.bind(this);
    this.getSummary = this.getSummary.bind(this);
  }

  getHeadersContent() {
    return [{
      label: Object(external_wp_i18n_["__"])('Product / Variation', 'woocommerce-admin'),
      key: 'title',
      required: true,
      isLeftAligned: true,
      isSortable: true
    }, {
      label: Object(external_wp_i18n_["__"])('SKU', 'woocommerce-admin'),
      key: 'sku',
      isSortable: true
    }, {
      label: Object(external_wp_i18n_["__"])('Status', 'woocommerce-admin'),
      key: 'stock_status',
      isSortable: true,
      defaultSort: true
    }, {
      label: Object(external_wp_i18n_["__"])('Stock', 'woocommerce-admin'),
      key: 'stock_quantity',
      isSortable: true
    }];
  }

  getRowsContent() {
    let products = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : [];
    const {
      query
    } = this.props;
    const persistedQuery = Object(external_wc_navigation_["getPersistedQuery"])(query);
    return products.map(product => {
      const {
        id,
        manage_stock: manageStock,
        parent_id: parentId,
        sku,
        stock_quantity: stockQuantity,
        stock_status: stockStatus,
        low_stock_amount: lowStockAmount
      } = product;
      const name = Object(external_wp_htmlEntities_["decodeEntities"])(product.name);
      const productDetailLink = Object(external_wc_navigation_["getNewPath"])(persistedQuery, '/analytics/products', {
        filter: 'single_product',
        products: parentId || id
      });
      const nameLink = Object(external_wp_element_["createElement"])(external_wc_components_["Link"], {
        href: productDetailLink,
        type: "wc-admin"
      }, name);
      const editProductLink = Object(external_wc_wcSettings_["getAdminLink"])('post.php?action=edit&post=' + (parentId || id));
      const stockStatusLink = isLowStock(stockStatus, stockQuantity, lowStockAmount) ? Object(external_wp_element_["createElement"])(external_wc_components_["Link"], {
        href: editProductLink,
        type: "wp-admin"
      }, Object(external_wp_i18n_["_x"])('Low', 'Indication of a low quantity', 'woocommerce-admin')) : Object(external_wp_element_["createElement"])(external_wc_components_["Link"], {
        href: editProductLink,
        type: "wp-admin"
      }, stockStatuses[stockStatus]);
      return [{
        display: nameLink,
        value: name
      }, {
        display: sku,
        value: sku
      }, {
        display: stockStatusLink,
        value: stockStatuses[stockStatus]
      }, {
        display: manageStock ? Object(external_wc_number_["formatValue"])(this.context.getCurrencyConfig(), 'number', stockQuantity) : Object(external_wp_i18n_["__"])('N/A', 'woocommerce-admin'),
        value: stockQuantity
      }];
    });
  }

  getSummary(totals) {
    const {
      products = 0,
      outofstock = 0,
      lowstock = 0,
      instock = 0,
      onbackorder = 0
    } = totals;
    const currency = this.context.getCurrencyConfig();
    return [{
      label: Object(external_wp_i18n_["_n"])('Product', 'Products', products, 'woocommerce-admin'),
      value: Object(external_wc_number_["formatValue"])(currency, 'number', products)
    }, {
      label: Object(external_wp_i18n_["__"])('Out of stock', 'woocommerce-admin'),
      value: Object(external_wc_number_["formatValue"])(currency, 'number', outofstock)
    }, {
      label: Object(external_wp_i18n_["__"])('Low stock', 'woocommerce-admin'),
      value: Object(external_wc_number_["formatValue"])(currency, 'number', lowstock)
    }, {
      label: Object(external_wp_i18n_["__"])('On backorder', 'woocommerce-admin'),
      value: Object(external_wc_number_["formatValue"])(currency, 'number', onbackorder)
    }, {
      label: Object(external_wp_i18n_["__"])('In stock', 'woocommerce-admin'),
      value: Object(external_wc_number_["formatValue"])(currency, 'number', instock)
    }];
  }

  render() {
    const {
      advancedFilters,
      filters,
      query
    } = this.props;
    return Object(external_wp_element_["createElement"])(report_table["a" /* default */], {
      endpoint: "stock",
      getHeadersContent: this.getHeadersContent,
      getRowsContent: this.getRowsContent,
      getSummary: this.getSummary,
      summaryFields: ['products', 'outofstock', 'lowstock', 'instock', 'onbackorder'],
      query: query,
      tableQuery: {
        orderby: query.orderby || 'stock_status',
        order: query.order || 'asc',
        type: query.type || 'all'
      },
      title: Object(external_wp_i18n_["__"])('Stock', 'woocommerce-admin'),
      filters: filters,
      advancedFilters: advancedFilters
    });
  }

}

table_StockReportTable.contextType = currency_context["a" /* CurrencyContext */];
/* harmony default export */ var table = (table_StockReportTable);
// EXTERNAL MODULE: ./client/analytics/components/report-filters/index.js
var report_filters = __webpack_require__(544);

// CONCATENATED MODULE: ./client/analytics/report/stock/index.js


/**
 * External dependencies
 */


/**
 * Internal dependencies
 */




class stock_StockReport extends external_wp_element_["Component"] {
  render() {
    const {
      query,
      path
    } = this.props;
    return Object(external_wp_element_["createElement"])(external_wp_element_["Fragment"], null, Object(external_wp_element_["createElement"])(report_filters["a" /* default */], {
      query: query,
      path: path,
      showDatePicker: showDatePicker,
      filters: config_filters,
      advancedFilters: config_advancedFilters,
      report: "stock"
    }), Object(external_wp_element_["createElement"])(table, {
      query: query,
      filters: config_filters,
      advancedFilters: config_advancedFilters
    }));
  }

}
stock_StockReport.propTypes = {
  query: prop_types_default.a.object.isRequired
};

/***/ })

}]);