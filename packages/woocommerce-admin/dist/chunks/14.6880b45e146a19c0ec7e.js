(window["webpackJsonp"] = window["webpackJsonp"] || []).push([[14],{

/***/ 739:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
// ESM COMPAT FLAG
__webpack_require__.r(__webpack_exports__);

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

// EXTERNAL MODULE: ./node_modules/prop-types/index.js
var prop_types = __webpack_require__(1);
var prop_types_default = /*#__PURE__*/__webpack_require__.n(prop_types);

// EXTERNAL MODULE: ./client/analytics/report/products/config.js
var config = __webpack_require__(778);

// EXTERNAL MODULE: ./client/lib/get-selected-chart/index.js
var get_selected_chart = __webpack_require__(755);

// EXTERNAL MODULE: ./client/analytics/report/products/table.js
var table = __webpack_require__(789);

// EXTERNAL MODULE: ./client/analytics/components/report-chart/index.js + 1 modules
var report_chart = __webpack_require__(754);

// EXTERNAL MODULE: ./client/analytics/components/report-error/index.js
var report_error = __webpack_require__(261);

// EXTERNAL MODULE: ./client/analytics/components/report-summary/index.js
var report_summary = __webpack_require__(756);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/assertThisInitialized.js
var assertThisInitialized = __webpack_require__(62);
var assertThisInitialized_default = /*#__PURE__*/__webpack_require__.n(assertThisInitialized);

// EXTERNAL MODULE: external "lodash"
var external_lodash_ = __webpack_require__(2);

// EXTERNAL MODULE: external {"this":["wc","components"]}
var external_this_wc_components_ = __webpack_require__(53);

// EXTERNAL MODULE: external {"this":["wc","navigation"]}
var external_this_wc_navigation_ = __webpack_require__(23);

// EXTERNAL MODULE: external {"this":["wc","number"]}
var external_this_wc_number_ = __webpack_require__(201);

// EXTERNAL MODULE: ./client/settings/index.js
var settings = __webpack_require__(22);

// EXTERNAL MODULE: ./client/analytics/components/report-table/index.js + 2 modules
var report_table = __webpack_require__(759);

// EXTERNAL MODULE: ./client/analytics/report/products/utils.js
var utils = __webpack_require__(791);

// EXTERNAL MODULE: ./client/lib/currency-context.js
var currency_context = __webpack_require__(200);

// CONCATENATED MODULE: ./client/analytics/report/products/table-variations.js








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




var manageStock = Object(settings["g" /* getSetting */])('manageStock', 'no');
var stockStatuses = Object(settings["g" /* getSetting */])('stockStatuses', {});

var table_variations_VariationsReportTable = /*#__PURE__*/function (_Component) {
  inherits_default()(VariationsReportTable, _Component);

  var _super = _createSuper(VariationsReportTable);

  function VariationsReportTable() {
    var _this;

    classCallCheck_default()(this, VariationsReportTable);

    _this = _super.call(this);
    _this.getHeadersContent = _this.getHeadersContent.bind(assertThisInitialized_default()(_this));
    _this.getRowsContent = _this.getRowsContent.bind(assertThisInitialized_default()(_this));
    _this.getSummary = _this.getSummary.bind(assertThisInitialized_default()(_this));
    return _this;
  }

  createClass_default()(VariationsReportTable, [{
    key: "getHeadersContent",
    value: function getHeadersContent() {
      return [{
        label: Object(external_this_wp_i18n_["__"])('Product / Variation Title', 'woocommerce'),
        key: 'name',
        required: true,
        isLeftAligned: true
      }, {
        label: Object(external_this_wp_i18n_["__"])('SKU', 'woocommerce'),
        key: 'sku',
        hiddenByDefault: true,
        isSortable: true
      }, {
        label: Object(external_this_wp_i18n_["__"])('Items Sold', 'woocommerce'),
        key: 'items_sold',
        required: true,
        defaultSort: true,
        isSortable: true,
        isNumeric: true
      }, {
        label: Object(external_this_wp_i18n_["__"])('Net Sales', 'woocommerce'),
        screenReaderLabel: Object(external_this_wp_i18n_["__"])('Net Sales', 'woocommerce'),
        key: 'net_revenue',
        required: true,
        isSortable: true,
        isNumeric: true
      }, {
        label: Object(external_this_wp_i18n_["__"])('Orders', 'woocommerce'),
        key: 'orders_count',
        isSortable: true,
        isNumeric: true
      }, manageStock === 'yes' ? {
        label: Object(external_this_wp_i18n_["__"])('Status', 'woocommerce'),
        key: 'stock_status'
      } : null, manageStock === 'yes' ? {
        label: Object(external_this_wp_i18n_["__"])('Stock', 'woocommerce'),
        key: 'stock',
        isNumeric: true
      } : null].filter(Boolean);
    }
  }, {
    key: "getRowsContent",
    value: function getRowsContent() {
      var data = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : [];
      var query = this.props.query;
      var persistedQuery = Object(external_this_wc_navigation_["getPersistedQuery"])(query);
      var _this$context = this.context,
          formatCurrency = _this$context.formatCurrency,
          getCurrencyFormatDecimal = _this$context.formatDecimal,
          getCurrency = _this$context.getCurrency;
      return Object(external_lodash_["map"])(data, function (row) {
        var itemsSold = row.items_sold,
            netRevenue = row.net_revenue,
            ordersCount = row.orders_count,
            productId = row.product_id;
        var extendedInfo = row.extended_info || {};
        var stockStatus = extendedInfo.stock_status,
            stockQuantity = extendedInfo.stock_quantity,
            lowStockAmount = extendedInfo.low_stock_amount,
            sku = extendedInfo.sku;
        var name = Object(external_lodash_["get"])(row, ['extended_info', 'name'], '');
        var ordersLink = Object(external_this_wc_navigation_["getNewPath"])(persistedQuery, '/analytics/orders', {
          filter: 'advanced',
          product_includes: query.products
        });
        var editPostLink = Object(settings["f" /* getAdminLink */])("post.php?post=".concat(productId, "&action=edit"));
        return [{
          display: Object(external_this_wp_element_["createElement"])(external_this_wc_components_["Link"], {
            href: editPostLink,
            type: "wp-admin"
          }, name),
          value: name
        }, {
          display: sku,
          value: sku
        }, {
          display: Object(external_this_wc_number_["formatValue"])(getCurrency(), 'number', itemsSold),
          value: itemsSold
        }, {
          display: formatCurrency(netRevenue),
          value: getCurrencyFormatDecimal(netRevenue)
        }, {
          display: Object(external_this_wp_element_["createElement"])(external_this_wc_components_["Link"], {
            href: ordersLink,
            type: "wc-admin"
          }, ordersCount),
          value: ordersCount
        }, manageStock === 'yes' ? {
          display: Object(utils["a" /* isLowStock */])(stockStatus, stockQuantity, lowStockAmount) ? Object(external_this_wp_element_["createElement"])(external_this_wc_components_["Link"], {
            href: editPostLink,
            type: "wp-admin"
          }, Object(external_this_wp_i18n_["_x"])('Low', 'Indication of a low quantity', 'woocommerce')) : stockStatuses[stockStatus],
          value: stockStatuses[stockStatus]
        } : null, manageStock === 'yes' ? {
          display: stockQuantity,
          value: stockQuantity
        } : null].filter(Boolean);
      });
    }
  }, {
    key: "getSummary",
    value: function getSummary(totals) {
      var _totals$variations_co = totals.variations_count,
          variationsCount = _totals$variations_co === void 0 ? 0 : _totals$variations_co,
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
        label: Object(external_this_wp_i18n_["_n"])('variation sold', 'variations sold', variationsCount, 'woocommerce'),
        value: Object(external_this_wc_number_["formatValue"])(currency, 'number', variationsCount)
      }, {
        label: Object(external_this_wp_i18n_["_n"])('item sold', 'items sold', itemsSold, 'woocommerce'),
        value: Object(external_this_wc_number_["formatValue"])(currency, 'number', itemsSold)
      }, {
        label: Object(external_this_wp_i18n_["__"])('net sales', 'woocommerce'),
        value: formatCurrency(netRevenue)
      }, {
        label: Object(external_this_wp_i18n_["_n"])('orders', 'orders', ordersCount, 'woocommerce'),
        value: Object(external_this_wc_number_["formatValue"])(currency, 'number', ordersCount)
      }];
    }
  }, {
    key: "render",
    value: function render() {
      var _this$props = this.props,
          advancedFilters = _this$props.advancedFilters,
          baseSearchQuery = _this$props.baseSearchQuery,
          filters = _this$props.filters,
          isRequesting = _this$props.isRequesting,
          query = _this$props.query;
      var labels = {
        helpText: Object(external_this_wp_i18n_["__"])('Check at least two variations below to compare', 'woocommerce'),
        placeholder: Object(external_this_wp_i18n_["__"])('Search by variation name or SKU', 'woocommerce')
      };
      return Object(external_this_wp_element_["createElement"])(report_table["a" /* default */], {
        baseSearchQuery: baseSearchQuery,
        compareBy: 'variations',
        compareParam: 'filter-variations',
        endpoint: "variations",
        getHeadersContent: this.getHeadersContent,
        getRowsContent: this.getRowsContent,
        isRequesting: isRequesting,
        itemIdField: "variation_id",
        labels: labels,
        query: query,
        getSummary: this.getSummary,
        summaryFields: ['variations_count', 'items_sold', 'net_revenue', 'orders_count'],
        searchBy: "variations",
        tableQuery: {
          orderby: query.orderby || 'items_sold',
          order: query.order || 'desc',
          extended_info: true,
          products: query.products,
          variations: query.variations
        },
        title: Object(external_this_wp_i18n_["__"])('Variations', 'woocommerce'),
        columnPrefsKey: "variations_report_columns",
        filters: filters,
        advancedFilters: advancedFilters
      });
    }
  }]);

  return VariationsReportTable;
}(external_this_wp_element_["Component"]);

table_variations_VariationsReportTable.contextType = currency_context["a" /* CurrencyContext */];
/* harmony default export */ var table_variations = (table_variations_VariationsReportTable);
// EXTERNAL MODULE: ./client/wc-api/with-select.js
var with_select = __webpack_require__(101);

// EXTERNAL MODULE: ./client/analytics/components/report-filters/index.js
var report_filters = __webpack_require__(757);

// CONCATENATED MODULE: ./client/analytics/report/products/index.js








function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { defineProperty_default()(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function products_createSuper(Derived) { var hasNativeReflectConstruct = products_isNativeReflectConstruct(); return function _createSuperInternal() { var Super = getPrototypeOf_default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = getPrototypeOf_default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return possibleConstructorReturn_default()(this, result); }; }

function products_isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/**
 * External dependencies
 */




/**
 * Internal dependencies
 */











var products_ProductsReport = /*#__PURE__*/function (_Component) {
  inherits_default()(ProductsReport, _Component);

  var _super = products_createSuper(ProductsReport);

  function ProductsReport() {
    classCallCheck_default()(this, ProductsReport);

    return _super.apply(this, arguments);
  }

  createClass_default()(ProductsReport, [{
    key: "getChartMeta",
    value: function getChartMeta() {
      var _this$props = this.props,
          query = _this$props.query,
          isSingleProductView = _this$props.isSingleProductView,
          isSingleProductVariable = _this$props.isSingleProductVariable;
      var isCompareView = query.filter === 'compare-products' && query.products && query.products.split(',').length > 1;
      var mode = isCompareView || isSingleProductView && isSingleProductVariable ? 'item-comparison' : 'time-comparison';
      var compareObject = isSingleProductView && isSingleProductVariable ? 'variations' : 'products';
      var label = isSingleProductView && isSingleProductVariable ? Object(external_this_wp_i18n_["__"])('%d variations', 'woocommerce') : Object(external_this_wp_i18n_["__"])('%d products', 'woocommerce');
      return {
        compareObject: compareObject,
        itemsLabel: label,
        mode: mode
      };
    }
  }, {
    key: "render",
    value: function render() {
      var _this$getChartMeta = this.getChartMeta(),
          compareObject = _this$getChartMeta.compareObject,
          itemsLabel = _this$getChartMeta.itemsLabel,
          mode = _this$getChartMeta.mode;

      var _this$props2 = this.props,
          path = _this$props2.path,
          query = _this$props2.query,
          isError = _this$props2.isError,
          isRequesting = _this$props2.isRequesting,
          isSingleProductVariable = _this$props2.isSingleProductVariable;

      if (isError) {
        return Object(external_this_wp_element_["createElement"])(report_error["a" /* default */], {
          isError: true
        });
      }

      var chartQuery = _objectSpread({}, query);

      if (mode === 'item-comparison') {
        chartQuery.segmentby = compareObject === 'products' ? 'product' : 'variation';
      }

      return Object(external_this_wp_element_["createElement"])(external_this_wp_element_["Fragment"], null, Object(external_this_wp_element_["createElement"])(report_filters["a" /* default */], {
        query: query,
        path: path,
        filters: config["c" /* filters */],
        advancedFilters: config["a" /* advancedFilters */],
        report: "products"
      }), Object(external_this_wp_element_["createElement"])(report_summary["a" /* default */], {
        mode: mode,
        charts: config["b" /* charts */],
        endpoint: "products",
        isRequesting: isRequesting,
        query: chartQuery,
        selectedChart: Object(get_selected_chart["a" /* default */])(query.chart, config["b" /* charts */]),
        filters: config["c" /* filters */],
        advancedFilters: config["a" /* advancedFilters */]
      }), Object(external_this_wp_element_["createElement"])(report_chart["a" /* default */], {
        charts: config["b" /* charts */],
        mode: mode,
        filters: config["c" /* filters */],
        advancedFilters: config["a" /* advancedFilters */],
        endpoint: "products",
        isRequesting: isRequesting,
        itemsLabel: itemsLabel,
        path: path,
        query: chartQuery,
        selectedChart: Object(get_selected_chart["a" /* default */])(chartQuery.chart, config["b" /* charts */])
      }), isSingleProductVariable ? Object(external_this_wp_element_["createElement"])(table_variations, {
        baseSearchQuery: {
          filter: 'single_product'
        },
        isRequesting: isRequesting,
        query: query,
        filters: config["c" /* filters */],
        advancedFilters: config["a" /* advancedFilters */]
      }) : Object(external_this_wp_element_["createElement"])(table["a" /* default */], {
        isRequesting: isRequesting,
        query: query,
        filters: config["c" /* filters */],
        advancedFilters: config["a" /* advancedFilters */]
      }));
    }
  }]);

  return ProductsReport;
}(external_this_wp_element_["Component"]);

products_ProductsReport.propTypes = {
  path: prop_types_default.a.string.isRequired,
  query: prop_types_default.a.object.isRequired
};
/* harmony default export */ var products = __webpack_exports__["default"] = (Object(compose["a" /* default */])(Object(with_select["a" /* default */])(function (select, props) {
  var query = props.query,
      isRequesting = props.isRequesting;
  var isSingleProductView = !query.search && query.products && query.products.split(',').length === 1;

  if (isRequesting) {
    return {
      query: _objectSpread({}, query),
      isSingleProductView: isSingleProductView,
      isRequesting: isRequesting
    };
  }

  var _select = select('wc-api'),
      getItems = _select.getItems,
      isGetItemsRequesting = _select.isGetItemsRequesting,
      getItemsError = _select.getItemsError;

  if (isSingleProductView) {
    var productId = parseInt(query.products, 10);
    var includeArgs = {
      include: productId
    }; // TODO Look at similar usage to populate tags in the Search component.

    var products = getItems('products', includeArgs);
    var isVariable = products && products.get(productId) && products.get(productId).type === 'variable';
    var isProductsRequesting = isGetItemsRequesting('products', includeArgs);
    var isProductsError = Boolean(getItemsError('products', includeArgs));
    return {
      query: _objectSpread(_objectSpread({}, query), {}, {
        'is-variable': isVariable
      }),
      isSingleProductView: isSingleProductView,
      isSingleProductVariable: isVariable,
      isRequesting: isProductsRequesting,
      isError: isProductsError
    };
  }

  return {
    query: query,
    isSingleProductView: isSingleProductView
  };
}))(products_ProductsReport));

/***/ }),

/***/ 778:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "b", function() { return charts; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "c", function() { return filters; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "a", function() { return advancedFilters; });
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(3);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_hooks__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(48);
/* harmony import */ var _wordpress_hooks__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_hooks__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var lib_async_requests__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(751);
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
  staticParams: ['chartType', 'paged', 'per_page'],
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
  staticParams: ['filter', 'products', 'chartType', 'paged', 'per_page'],
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

/***/ })

}]);
