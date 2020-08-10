(window["webpackJsonp"] = window["webpackJsonp"] || []).push([[9],{

/***/ 737:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
// ESM COMPAT FLAG
__webpack_require__.r(__webpack_exports__);

// EXPORTS
__webpack_require__.d(__webpack_exports__, "default", function() { return /* binding */ categories_CategoriesReport; });

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

// EXTERNAL MODULE: ./node_modules/prop-types/index.js
var prop_types = __webpack_require__(1);
var prop_types_default = /*#__PURE__*/__webpack_require__.n(prop_types);

// EXTERNAL MODULE: external {"this":["wp","i18n"]}
var external_this_wp_i18n_ = __webpack_require__(3);

// EXTERNAL MODULE: external {"this":["wp","hooks"]}
var external_this_wp_hooks_ = __webpack_require__(48);

// EXTERNAL MODULE: ./client/lib/async-requests/index.js
var async_requests = __webpack_require__(751);

// CONCATENATED MODULE: ./client/analytics/report/categories/config.js
/**
 * External dependencies
 */


/**
 * Internal dependencies
 */


var CATEGORY_REPORT_CHARTS_FILTER = 'woocommerce_admin_categories_report_charts';
var CATEGORY_REPORT_FILTERS_FILTER = 'woocommerce_admin_categories_report_filters';
var CATEGORY_REPORT_ADVANCED_FILTERS_FILTER = 'woocommerce_admin_category_report_advanced_filters';
var charts = Object(external_this_wp_hooks_["applyFilters"])(CATEGORY_REPORT_CHARTS_FILTER, [{
  key: 'items_sold',
  label: Object(external_this_wp_i18n_["__"])('Items Sold', 'woocommerce'),
  order: 'desc',
  orderby: 'items_sold',
  type: 'number'
}, {
  key: 'net_revenue',
  label: Object(external_this_wp_i18n_["__"])('Net Sales', 'woocommerce'),
  order: 'desc',
  orderby: 'net_revenue',
  type: 'currency'
}, {
  key: 'orders_count',
  label: Object(external_this_wp_i18n_["__"])('Orders', 'woocommerce'),
  order: 'desc',
  orderby: 'orders_count',
  type: 'number'
}]);
var config_filters = Object(external_this_wp_hooks_["applyFilters"])(CATEGORY_REPORT_FILTERS_FILTER, [{
  label: Object(external_this_wp_i18n_["__"])('Show', 'woocommerce'),
  staticParams: ['chartType', 'paged', 'per_page'],
  param: 'filter',
  showFilters: function showFilters() {
    return true;
  },
  filters: [{
    label: Object(external_this_wp_i18n_["__"])('All Categories', 'woocommerce'),
    value: 'all'
  }, {
    label: Object(external_this_wp_i18n_["__"])('Single Category', 'woocommerce'),
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
          placeholder: Object(external_this_wp_i18n_["__"])('Type to search for a category', 'woocommerce'),
          button: Object(external_this_wp_i18n_["__"])('Single Category', 'woocommerce')
        }
      }
    }]
  }, {
    label: Object(external_this_wp_i18n_["__"])('Comparison', 'woocommerce'),
    value: 'compare-categories',
    chartMode: 'item-comparison',
    settings: {
      type: 'categories',
      param: 'categories',
      getLabels: async_requests["a" /* getCategoryLabels */],
      labels: {
        helpText: Object(external_this_wp_i18n_["__"])('Check at least two categories below to compare', 'woocommerce'),
        placeholder: Object(external_this_wp_i18n_["__"])('Search for categories to compare', 'woocommerce'),
        title: Object(external_this_wp_i18n_["__"])('Compare Categories', 'woocommerce'),
        update: Object(external_this_wp_i18n_["__"])('Compare', 'woocommerce')
      }
    }
  }]
}]);
var config_advancedFilters = Object(external_this_wp_hooks_["applyFilters"])(CATEGORY_REPORT_ADVANCED_FILTERS_FILTER, {});
// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/assertThisInitialized.js
var assertThisInitialized = __webpack_require__(62);
var assertThisInitialized_default = /*#__PURE__*/__webpack_require__.n(assertThisInitialized);

// EXTERNAL MODULE: ./node_modules/@wordpress/compose/build-module/higher-order/compose.js
var compose = __webpack_require__(169);

// EXTERNAL MODULE: external "lodash"
var external_lodash_ = __webpack_require__(2);

// EXTERNAL MODULE: external {"this":["wc","navigation"]}
var external_this_wc_navigation_ = __webpack_require__(23);

// EXTERNAL MODULE: external {"this":["wc","components"]}
var external_this_wc_components_ = __webpack_require__(53);

// EXTERNAL MODULE: external {"this":["wc","number"]}
var external_this_wc_number_ = __webpack_require__(201);

// EXTERNAL MODULE: ./client/analytics/report/categories/breadcrumbs.js
var breadcrumbs = __webpack_require__(790);

// EXTERNAL MODULE: ./client/analytics/components/report-table/index.js + 2 modules
var report_table = __webpack_require__(759);

// EXTERNAL MODULE: ./client/wc-api/with-select.js
var with_select = __webpack_require__(101);

// EXTERNAL MODULE: ./client/lib/currency-context.js
var currency_context = __webpack_require__(200);

// CONCATENATED MODULE: ./client/analytics/report/categories/table.js








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






var table_CategoriesReportTable = /*#__PURE__*/function (_Component) {
  inherits_default()(CategoriesReportTable, _Component);

  var _super = _createSuper(CategoriesReportTable);

  function CategoriesReportTable(props) {
    var _this;

    classCallCheck_default()(this, CategoriesReportTable);

    _this = _super.call(this, props);
    _this.getRowsContent = _this.getRowsContent.bind(assertThisInitialized_default()(_this));
    _this.getSummary = _this.getSummary.bind(assertThisInitialized_default()(_this));
    return _this;
  }

  createClass_default()(CategoriesReportTable, [{
    key: "getHeadersContent",
    value: function getHeadersContent() {
      return [{
        label: Object(external_this_wp_i18n_["__"])('Category', 'woocommerce'),
        key: 'category',
        required: true,
        isSortable: true,
        isLeftAligned: true
      }, {
        label: Object(external_this_wp_i18n_["__"])('Items Sold', 'woocommerce'),
        key: 'items_sold',
        required: true,
        defaultSort: true,
        isSortable: true,
        isNumeric: true
      }, {
        label: Object(external_this_wp_i18n_["__"])('Net Sales', 'woocommerce'),
        key: 'net_revenue',
        isSortable: true,
        isNumeric: true
      }, {
        label: Object(external_this_wp_i18n_["__"])('Products', 'woocommerce'),
        key: 'products_count',
        isSortable: true,
        isNumeric: true
      }, {
        label: Object(external_this_wp_i18n_["__"])('Orders', 'woocommerce'),
        key: 'orders_count',
        isSortable: true,
        isNumeric: true
      }];
    }
  }, {
    key: "getRowsContent",
    value: function getRowsContent(categoryStats) {
      var _this2 = this;

      var _this$context = this.context,
          renderCurrency = _this$context.render,
          getCurrencyFormatDecimal = _this$context.formatDecimal,
          getCurrency = _this$context.getCurrency;
      var currency = getCurrency();
      return Object(external_lodash_["map"])(categoryStats, function (categoryStat) {
        var categoryId = categoryStat.category_id,
            itemsSold = categoryStat.items_sold,
            netRevenue = categoryStat.net_revenue,
            productsCount = categoryStat.products_count,
            ordersCount = categoryStat.orders_count;
        var _this2$props = _this2.props,
            categories = _this2$props.categories,
            query = _this2$props.query;
        var category = categories.get(categoryId);
        var persistedQuery = Object(external_this_wc_navigation_["getPersistedQuery"])(query);
        return [{
          display: Object(external_this_wp_element_["createElement"])(breadcrumbs["a" /* default */], {
            query: query,
            category: category,
            categories: categories
          }),
          value: category && category.name
        }, {
          display: Object(external_this_wc_number_["formatValue"])(currency, 'number', itemsSold),
          value: itemsSold
        }, {
          display: renderCurrency(netRevenue),
          value: getCurrencyFormatDecimal(netRevenue)
        }, {
          display: category && Object(external_this_wp_element_["createElement"])(external_this_wc_components_["Link"], {
            href: Object(external_this_wc_navigation_["getNewPath"])(persistedQuery, '/analytics/categories', {
              filter: 'single_category',
              categories: category.id
            }),
            type: "wc-admin"
          }, Object(external_this_wc_number_["formatValue"])(currency, 'number', productsCount)),
          value: productsCount
        }, {
          display: Object(external_this_wc_number_["formatValue"])(currency, 'number', ordersCount),
          value: ordersCount
        }];
      });
    }
  }, {
    key: "getSummary",
    value: function getSummary(totals) {
      var totalResults = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : 0;
      var _totals$items_sold = totals.items_sold,
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
        label: Object(external_this_wp_i18n_["_n"])('category', 'categories', totalResults, 'woocommerce'),
        value: Object(external_this_wc_number_["formatValue"])(currency, 'number', totalResults)
      }, {
        label: Object(external_this_wp_i18n_["_n"])('item sold', 'items sold', itemsSold, 'woocommerce'),
        value: Object(external_this_wc_number_["formatValue"])(currency, 'number', itemsSold)
      }, {
        label: Object(external_this_wp_i18n_["__"])('net sales', 'woocommerce'),
        value: formatCurrency(netRevenue)
      }, {
        label: Object(external_this_wp_i18n_["_n"])('order', 'orders', ordersCount, 'woocommerce'),
        value: Object(external_this_wc_number_["formatValue"])(currency, 'number', ordersCount)
      }];
    }
  }, {
    key: "render",
    value: function render() {
      var _this$props = this.props,
          advancedFilters = _this$props.advancedFilters,
          filters = _this$props.filters,
          isRequesting = _this$props.isRequesting,
          query = _this$props.query;
      var labels = {
        helpText: Object(external_this_wp_i18n_["__"])('Check at least two categories below to compare', 'woocommerce'),
        placeholder: Object(external_this_wp_i18n_["__"])('Search by category name', 'woocommerce')
      };
      return Object(external_this_wp_element_["createElement"])(report_table["a" /* default */], {
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
        title: Object(external_this_wp_i18n_["__"])('Categories', 'woocommerce'),
        columnPrefsKey: "categories_report_columns",
        filters: filters,
        advancedFilters: advancedFilters
      });
    }
  }]);

  return CategoriesReportTable;
}(external_this_wp_element_["Component"]);

table_CategoriesReportTable.contextType = currency_context["a" /* CurrencyContext */];
/* harmony default export */ var table = (Object(compose["a" /* default */])(Object(with_select["a" /* default */])(function (select, props) {
  var isRequesting = props.isRequesting,
      query = props.query;

  if (isRequesting || query.search && !(query.categories && query.categories.length)) {
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
  var isCategoriesError = Boolean(getItemsError('categories', tableQuery));
  var isCategoriesRequesting = isGetItemsRequesting('categories', tableQuery);
  return {
    categories: categories,
    isError: isCategoriesError,
    isRequesting: isCategoriesRequesting
  };
}))(table_CategoriesReportTable));
// EXTERNAL MODULE: ./client/lib/get-selected-chart/index.js
var get_selected_chart = __webpack_require__(755);

// EXTERNAL MODULE: ./client/analytics/components/report-chart/index.js + 1 modules
var report_chart = __webpack_require__(754);

// EXTERNAL MODULE: ./client/analytics/components/report-summary/index.js
var report_summary = __webpack_require__(756);

// EXTERNAL MODULE: ./client/analytics/report/products/table.js
var products_table = __webpack_require__(789);

// EXTERNAL MODULE: ./client/analytics/components/report-filters/index.js
var report_filters = __webpack_require__(757);

// CONCATENATED MODULE: ./client/analytics/report/categories/index.js








function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { defineProperty_default()(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function categories_createSuper(Derived) { var hasNativeReflectConstruct = categories_isNativeReflectConstruct(); return function _createSuperInternal() { var Super = getPrototypeOf_default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = getPrototypeOf_default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return possibleConstructorReturn_default()(this, result); }; }

function categories_isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/**
 * External dependencies
 */



/**
 * Internal dependencies
 */









var categories_CategoriesReport = /*#__PURE__*/function (_Component) {
  inherits_default()(CategoriesReport, _Component);

  var _super = categories_createSuper(CategoriesReport);

  function CategoriesReport() {
    classCallCheck_default()(this, CategoriesReport);

    return _super.apply(this, arguments);
  }

  createClass_default()(CategoriesReport, [{
    key: "getChartMeta",
    value: function getChartMeta() {
      var query = this.props.query;
      var isCompareView = query.filter === 'compare-categories' && query.categories && query.categories.split(',').length > 1;
      var isSingleCategoryView = query.filter === 'single_category' && !!query.categories;
      var mode = isCompareView || isSingleCategoryView ? 'item-comparison' : 'time-comparison';
      var itemsLabel = isSingleCategoryView ? Object(external_this_wp_i18n_["__"])('%d products', 'woocommerce') : Object(external_this_wp_i18n_["__"])('%d categories', 'woocommerce');
      return {
        isSingleCategoryView: isSingleCategoryView,
        itemsLabel: itemsLabel,
        mode: mode
      };
    }
  }, {
    key: "render",
    value: function render() {
      var _this$props = this.props,
          isRequesting = _this$props.isRequesting,
          query = _this$props.query,
          path = _this$props.path;

      var _this$getChartMeta = this.getChartMeta(),
          mode = _this$getChartMeta.mode,
          itemsLabel = _this$getChartMeta.itemsLabel,
          isSingleCategoryView = _this$getChartMeta.isSingleCategoryView;

      var chartQuery = _objectSpread({}, query);

      if (mode === 'item-comparison') {
        chartQuery.segmentby = isSingleCategoryView ? 'product' : 'category';
      }

      return Object(external_this_wp_element_["createElement"])(external_this_wp_element_["Fragment"], null, Object(external_this_wp_element_["createElement"])(report_filters["a" /* default */], {
        query: query,
        path: path,
        filters: config_filters,
        advancedFilters: config_advancedFilters,
        report: "categories"
      }), Object(external_this_wp_element_["createElement"])(report_summary["a" /* default */], {
        charts: charts,
        endpoint: "products",
        isRequesting: isRequesting,
        limitProperties: isSingleCategoryView ? ['products', 'categories'] : ['categories'],
        query: chartQuery,
        selectedChart: Object(get_selected_chart["a" /* default */])(query.chart, charts),
        filters: config_filters,
        advancedFilters: config_advancedFilters,
        report: "categories"
      }), Object(external_this_wp_element_["createElement"])(report_chart["a" /* default */], {
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
      }), isSingleCategoryView ? Object(external_this_wp_element_["createElement"])(products_table["a" /* default */], {
        isRequesting: isRequesting,
        query: chartQuery,
        baseSearchQuery: {
          filter: 'single_category'
        },
        hideCompare: isSingleCategoryView,
        filters: config_filters,
        advancedFilters: config_advancedFilters
      }) : Object(external_this_wp_element_["createElement"])(table, {
        isRequesting: isRequesting,
        query: query,
        filters: config_filters,
        advancedFilters: config_advancedFilters
      }));
    }
  }]);

  return CategoriesReport;
}(external_this_wp_element_["Component"]);


categories_CategoriesReport.propTypes = {
  query: prop_types_default.a.object.isRequired,
  path: prop_types_default.a.string.isRequired
};

/***/ })

}]);
