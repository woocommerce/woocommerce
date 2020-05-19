(window["webpackJsonp"] = window["webpackJsonp"] || []).push([["analytics-report-categories~analytics-report-coupons~analytics-report-customers~analytics-report-dow~99eefb40"],{

/***/ "./client/analytics/components/report-filters/index.js":
/*!*************************************************************!*\
  !*** ./client/analytics/components/report-filters/index.js ***!
  \*************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/defineProperty */ "./node_modules/@babel/runtime/helpers/defineProperty.js");
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/classCallCheck */ "./node_modules/@babel/runtime/helpers/classCallCheck.js");
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @babel/runtime/helpers/createClass */ "./node_modules/@babel/runtime/helpers/createClass.js");
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @babel/runtime/helpers/assertThisInitialized */ "./node_modules/@babel/runtime/helpers/assertThisInitialized.js");
/* harmony import */ var _babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @babel/runtime/helpers/possibleConstructorReturn */ "./node_modules/@babel/runtime/helpers/possibleConstructorReturn.js");
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @babel/runtime/helpers/getPrototypeOf */ "./node_modules/@babel/runtime/helpers/getPrototypeOf.js");
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @babel/runtime/helpers/inherits */ "./node_modules/@babel/runtime/helpers/inherits.js");
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! prop-types */ "./node_modules/prop-types/index.js");
/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(prop_types__WEBPACK_IMPORTED_MODULE_8__);
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! lodash */ "lodash");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_9__);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_10___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_10__);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! @woocommerce/components */ "@woocommerce/components");
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_11___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_components__WEBPACK_IMPORTED_MODULE_11__);
/* harmony import */ var _woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! @woocommerce/wc-admin-settings */ "./client/settings/index.js");
/* harmony import */ var _woocommerce_data__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! @woocommerce/data */ "@woocommerce/data");
/* harmony import */ var _woocommerce_data__WEBPACK_IMPORTED_MODULE_13___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_data__WEBPACK_IMPORTED_MODULE_13__);
/* harmony import */ var lib_tracks__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! lib/tracks */ "./client/lib/tracks.js");
/* harmony import */ var lib_date__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(/*! lib/date */ "./client/lib/date.js");
/* harmony import */ var lib_currency_context__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__(/*! lib/currency-context */ "./client/lib/currency-context.js");









function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0___default()(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function () { var Super = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_5___default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_5___default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_4___default()(this, result); }; }

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
  _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_6___default()(ReportFilters, _Component);

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
      Object(lib_tracks__WEBPACK_IMPORTED_MODULE_14__["recordEvent"])('datepicker_update', _objectSpread({
        report: report
      }, Object(lodash__WEBPACK_IMPORTED_MODULE_9__["omitBy"])(data, lodash__WEBPACK_IMPORTED_MODULE_9__["isUndefined"])));
    }
  }, {
    key: "trackFilterSelect",
    value: function trackFilterSelect(data) {
      var report = this.props.report;
      Object(lib_tracks__WEBPACK_IMPORTED_MODULE_14__["recordEvent"])('analytics_filter', {
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
          Object(lib_tracks__WEBPACK_IMPORTED_MODULE_14__["recordEvent"])('analytics_filters_add', {
            report: report,
            filter: data.key
          });
          break;

        case 'remove':
          Object(lib_tracks__WEBPACK_IMPORTED_MODULE_14__["recordEvent"])('analytics_filters_remove', {
            report: report,
            filter: data.key
          });
          break;

        case 'filter':
          var snakeCaseData = Object.keys(data).reduce(function (result, property) {
            result[Object(lodash__WEBPACK_IMPORTED_MODULE_9__["snakeCase"])(property)] = data[property];
            return result;
          }, {});
          Object(lib_tracks__WEBPACK_IMPORTED_MODULE_14__["recordEvent"])('analytics_filters_filter', {
            report: report,
            snakeCaseData: snakeCaseData
          });
          break;

        case 'clear_all':
          Object(lib_tracks__WEBPACK_IMPORTED_MODULE_14__["recordEvent"])('analytics_filters_clear_all', {
            report: report
          });
          break;

        case 'match':
          Object(lib_tracks__WEBPACK_IMPORTED_MODULE_14__["recordEvent"])('analytics_filters_all_any', {
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

      var _getDateParamsFromQue = Object(lib_date__WEBPACK_IMPORTED_MODULE_15__["getDateParamsFromQuery"])(query, defaultDateRange),
          period = _getDateParamsFromQue.period,
          compare = _getDateParamsFromQue.compare,
          before = _getDateParamsFromQue.before,
          after = _getDateParamsFromQue.after;

      var _getCurrentDates = Object(lib_date__WEBPACK_IMPORTED_MODULE_15__["getCurrentDates"])(query, defaultDateRange),
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
        siteLocale: _woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_12__["LOCALE"].siteLocale,
        currency: Currency,
        path: path,
        filters: filters,
        advancedFilters: advancedFilters,
        showDatePicker: showDatePicker,
        onDateSelect: this.trackDateSelect,
        onFilterSelect: this.trackFilterSelect,
        onAdvancedFilterAction: this.trackAdvancedFilterAction,
        dateQuery: dateQuery,
        isoDateFormat: lib_date__WEBPACK_IMPORTED_MODULE_15__["isoDateFormat"]
      });
    }
  }]);

  return ReportFilters;
}(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["Component"]);

ReportFilters.contextType = lib_currency_context__WEBPACK_IMPORTED_MODULE_16__["CurrencyContext"];
/* harmony default export */ __webpack_exports__["default"] = (Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_10__["withSelect"])(function (select) {
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

/***/ "./client/analytics/components/report-table/download-icon.js":
/*!*******************************************************************!*\
  !*** ./client/analytics/components/report-table/download-icon.js ***!
  \*******************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);

/* harmony default export */ __webpack_exports__["default"] = (function () {
  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("svg", {
    role: "img",
    "aria-hidden": "true",
    focusable: "false",
    version: "1.1",
    xmlns: "http://www.w3.org/2000/svg",
    x: "0px",
    y: "0px",
    viewBox: "0 0 24 24"
  }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("path", {
    d: "M18,9c-0.009,0-0.017,0.002-0.025,0.003C17.72,5.646,14.922,3,11.5,3C7.91,3,5,5.91,5,9.5c0,0.524,0.069,1.031,0.186,1.519 C5.123,11.016,5.064,11,5,11c-2.209,0-4,1.791-4,4c0,1.202,0.541,2.267,1.38,3h18.593C22.196,17.089,23,15.643,23,14 C23,11.239,20.761,9,18,9z M12,16l-4-5h3V8h2v3h3L12,16z"
  }));
});

/***/ }),

/***/ "./client/analytics/components/report-table/index.js":
/*!***********************************************************!*\
  !*** ./client/analytics/components/report-table/index.js ***!
  \***********************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/extends */ "./node_modules/@babel/runtime/helpers/extends.js");
/* harmony import */ var _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _babel_runtime_helpers_objectWithoutProperties__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/objectWithoutProperties */ "./node_modules/@babel/runtime/helpers/objectWithoutProperties.js");
/* harmony import */ var _babel_runtime_helpers_objectWithoutProperties__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_objectWithoutProperties__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _babel_runtime_helpers_toConsumableArray__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @babel/runtime/helpers/toConsumableArray */ "./node_modules/@babel/runtime/helpers/toConsumableArray.js");
/* harmony import */ var _babel_runtime_helpers_toConsumableArray__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_toConsumableArray__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @babel/runtime/helpers/defineProperty */ "./node_modules/@babel/runtime/helpers/defineProperty.js");
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @babel/runtime/helpers/classCallCheck */ "./node_modules/@babel/runtime/helpers/classCallCheck.js");
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @babel/runtime/helpers/createClass */ "./node_modules/@babel/runtime/helpers/createClass.js");
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @babel/runtime/helpers/assertThisInitialized */ "./node_modules/@babel/runtime/helpers/assertThisInitialized.js");
/* harmony import */ var _babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @babel/runtime/helpers/possibleConstructorReturn */ "./node_modules/@babel/runtime/helpers/possibleConstructorReturn.js");
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! @babel/runtime/helpers/getPrototypeOf */ "./node_modules/@babel/runtime/helpers/getPrototypeOf.js");
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_8__);
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! @babel/runtime/helpers/inherits */ "./node_modules/@babel/runtime/helpers/inherits.js");
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_9__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_10___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! @wordpress/components */ "./node_modules/@wordpress/components/build-module/index.js");
/* harmony import */ var _wordpress_hooks__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! @wordpress/hooks */ "@wordpress/hooks");
/* harmony import */ var _wordpress_hooks__WEBPACK_IMPORTED_MODULE_12___default = /*#__PURE__*/__webpack_require__.n(_wordpress_hooks__WEBPACK_IMPORTED_MODULE_12__);
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! @wordpress/compose */ "./node_modules/@wordpress/compose/build-module/index.js");
/* harmony import */ var _wordpress_dom__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! @wordpress/dom */ "./node_modules/@wordpress/dom/build-module/index.js");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_15___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_15__);
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__(/*! lodash */ "lodash");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_16___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_16__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_17__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_17___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_17__);
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_18__ = __webpack_require__(/*! classnames */ "./node_modules/classnames/index.js");
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_18___default = /*#__PURE__*/__webpack_require__.n(classnames__WEBPACK_IMPORTED_MODULE_18__);
/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_19__ = __webpack_require__(/*! prop-types */ "./node_modules/prop-types/index.js");
/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_19___default = /*#__PURE__*/__webpack_require__.n(prop_types__WEBPACK_IMPORTED_MODULE_19__);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_20__ = __webpack_require__(/*! @woocommerce/components */ "@woocommerce/components");
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_20___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_components__WEBPACK_IMPORTED_MODULE_20__);
/* harmony import */ var _download_icon__WEBPACK_IMPORTED_MODULE_21__ = __webpack_require__(/*! ./download-icon */ "./client/analytics/components/report-table/download-icon.js");
/* harmony import */ var _woocommerce_navigation__WEBPACK_IMPORTED_MODULE_22__ = __webpack_require__(/*! @woocommerce/navigation */ "@woocommerce/navigation");
/* harmony import */ var _woocommerce_navigation__WEBPACK_IMPORTED_MODULE_22___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_22__);
/* harmony import */ var _woocommerce_csv_export__WEBPACK_IMPORTED_MODULE_23__ = __webpack_require__(/*! @woocommerce/csv-export */ "@woocommerce/csv-export");
/* harmony import */ var _woocommerce_csv_export__WEBPACK_IMPORTED_MODULE_23___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_csv_export__WEBPACK_IMPORTED_MODULE_23__);
/* harmony import */ var _woocommerce_data__WEBPACK_IMPORTED_MODULE_24__ = __webpack_require__(/*! @woocommerce/data */ "@woocommerce/data");
/* harmony import */ var _woocommerce_data__WEBPACK_IMPORTED_MODULE_24___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_data__WEBPACK_IMPORTED_MODULE_24__);
/* harmony import */ var analytics_components_report_error__WEBPACK_IMPORTED_MODULE_25__ = __webpack_require__(/*! analytics/components/report-error */ "./client/analytics/components/report-error/index.js");
/* harmony import */ var wc_api_reports_utils__WEBPACK_IMPORTED_MODULE_26__ = __webpack_require__(/*! wc-api/reports/utils */ "./client/wc-api/reports/utils.js");
/* harmony import */ var wc_api_constants__WEBPACK_IMPORTED_MODULE_27__ = __webpack_require__(/*! wc-api/constants */ "./client/wc-api/constants.js");
/* harmony import */ var wc_api_with_select__WEBPACK_IMPORTED_MODULE_28__ = __webpack_require__(/*! wc-api/with-select */ "./client/wc-api/with-select.js");
/* harmony import */ var _utils__WEBPACK_IMPORTED_MODULE_29__ = __webpack_require__(/*! ./utils */ "./client/analytics/components/report-table/utils.js");
/* harmony import */ var lib_tracks__WEBPACK_IMPORTED_MODULE_30__ = __webpack_require__(/*! lib/tracks */ "./client/lib/tracks.js");
/* harmony import */ var _style_scss__WEBPACK_IMPORTED_MODULE_31__ = __webpack_require__(/*! ./style.scss */ "./client/analytics/components/report-table/style.scss");
/* harmony import */ var _style_scss__WEBPACK_IMPORTED_MODULE_31___default = /*#__PURE__*/__webpack_require__.n(_style_scss__WEBPACK_IMPORTED_MODULE_31__);












function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_3___default()(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function () { var Super = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_8___default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_8___default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_7___default()(this, result); }; }

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








var TABLE_FILTER = 'woocommerce_admin_report_table';
/**
 * Component that extends `TableCard` to facilitate its usage in reports.
 */

var ReportTable = /*#__PURE__*/function (_Component) {
  _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_9___default()(ReportTable, _Component);

  var _super = _createSuper(ReportTable);

  function ReportTable(props) {
    var _this;

    _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_4___default()(this, ReportTable);

    _this = _super.call(this, props);
    var _this$props = _this.props,
        query = _this$props.query,
        compareBy = _this$props.compareBy;
    var selectedRows = query.filter ? Object(_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_22__["getIdsFromQuery"])(query[compareBy]) : [];
    _this.state = {
      selectedRows: selectedRows
    };
    _this.onColumnsChange = _this.onColumnsChange.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_6___default()(_this));
    _this.onPageChange = _this.onPageChange.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_6___default()(_this));
    _this.onSort = _this.onSort.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_6___default()(_this));
    _this.scrollPointRef = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__["createRef"])();
    _this.trackTableSearch = _this.trackTableSearch.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_6___default()(_this));
    _this.onClickDownload = _this.onClickDownload.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_6___default()(_this));
    _this.onCompare = _this.onCompare.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_6___default()(_this));
    _this.onSearchChange = _this.onSearchChange.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_6___default()(_this));
    _this.selectRow = _this.selectRow.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_6___default()(_this));
    _this.selectAllRows = _this.selectAllRows.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_6___default()(_this));
    return _this;
  }

  _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_5___default()(ReportTable, [{
    key: "componentDidUpdate",
    value: function componentDidUpdate(_ref) {
      var prevQuery = _ref.query;
      var _this$props2 = this.props,
          compareBy = _this$props2.compareBy,
          query = _this$props2.query;

      if (query.filter || prevQuery.filter) {
        var prevIds = prevQuery.filter ? Object(_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_22__["getIdsFromQuery"])(prevQuery[compareBy]) : [];
        var currentIds = query.filter ? Object(_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_22__["getIdsFromQuery"])(query[compareBy]) : [];

        if (!Object(lodash__WEBPACK_IMPORTED_MODULE_16__["isEqual"])(prevIds.sort(), currentIds.sort())) {
          /* eslint-disable react/no-did-update-set-state */
          this.setState({
            selectedRows: currentIds
          });
          /* eslint-enable react/no-did-update-set-state */
        }
      }
    }
  }, {
    key: "onColumnsChange",
    value: function onColumnsChange(shownColumns, toggledColumn) {
      var _this$props3 = this.props,
          columnPrefsKey = _this$props3.columnPrefsKey,
          endpoint = _this$props3.endpoint,
          getHeadersContent = _this$props3.getHeadersContent,
          updateCurrentUserData = _this$props3.updateCurrentUserData;
      var columns = getHeadersContent().map(function (header) {
        return header.key;
      });
      var hiddenColumns = columns.filter(function (column) {
        return !shownColumns.includes(column);
      });

      if (columnPrefsKey) {
        var userDataFields = _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_3___default()({}, columnPrefsKey, hiddenColumns);

        updateCurrentUserData(userDataFields);
      }

      if (toggledColumn) {
        var eventProps = {
          report: endpoint,
          column: toggledColumn,
          status: shownColumns.includes(toggledColumn) ? 'on' : 'off'
        };
        Object(lib_tracks__WEBPACK_IMPORTED_MODULE_30__["recordEvent"])('analytics_table_header_toggle', eventProps);
      }
    }
  }, {
    key: "onPageChange",
    value: function onPageChange(newPage, source) {
      var endpoint = this.props.endpoint;
      this.scrollPointRef.current.scrollIntoView();
      var tableElement = this.scrollPointRef.current.nextSibling.querySelector('.woocommerce-table__table');
      var focusableElements = _wordpress_dom__WEBPACK_IMPORTED_MODULE_14__["focus"].focusable.find(tableElement);

      if (focusableElements.length) {
        focusableElements[0].focus();
      }

      if (source) {
        if (source === 'goto') {
          Object(lib_tracks__WEBPACK_IMPORTED_MODULE_30__["recordEvent"])('analytics_table_go_to_page', {
            report: endpoint,
            page: newPage
          });
        } else {
          Object(lib_tracks__WEBPACK_IMPORTED_MODULE_30__["recordEvent"])('analytics_table_page_click', {
            report: endpoint,
            direction: source
          });
        }
      }
    }
  }, {
    key: "trackTableSearch",
    value: function trackTableSearch() {
      var endpoint = this.props.endpoint; // @todo: decide if this should only fire for new tokens (not any/all changes).

      Object(lib_tracks__WEBPACK_IMPORTED_MODULE_30__["recordEvent"])('analytics_table_filter', {
        report: endpoint
      });
    }
  }, {
    key: "onSort",
    value: function onSort(key, direction) {
      Object(_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_22__["onQueryChange"])('sort')(key, direction);
      var endpoint = this.props.endpoint;
      var eventProps = {
        report: endpoint,
        column: key,
        direction: direction
      };
      Object(lib_tracks__WEBPACK_IMPORTED_MODULE_30__["recordEvent"])('analytics_table_sort', eventProps);
    }
  }, {
    key: "filterShownHeaders",
    value: function filterShownHeaders(headers, hiddenKeys) {
      // If no user preferences, set visibilty based on column default.
      if (!hiddenKeys) {
        return headers.map(function (header) {
          return _objectSpread({}, header, {
            visible: header.required || !header.hiddenByDefault
          });
        });
      } // Set visibilty based on user preferences.


      return headers.map(function (header) {
        return _objectSpread({}, header, {
          visible: header.required || !hiddenKeys.includes(header.key)
        });
      });
    }
  }, {
    key: "onClickDownload",
    value: function onClickDownload() {
      var _this$props4 = this.props,
          endpoint = _this$props4.endpoint,
          getHeadersContent = _this$props4.getHeadersContent,
          getRowsContent = _this$props4.getRowsContent,
          initiateReportExport = _this$props4.initiateReportExport,
          query = _this$props4.query,
          searchBy = _this$props4.searchBy,
          tableData = _this$props4.tableData,
          title = _this$props4.title;
      var params = Object.assign({}, query);
      var items = tableData.items,
          reportQuery = tableData.query;
      var data = items.data,
          totalResults = items.totalResults;
      var downloadType = 'browser'; // Delete unnecessary items from filename.

      delete params.extended_info;

      if (params.search) {
        delete params[searchBy];
      }

      if (data && data.length === totalResults) {
        Object(_woocommerce_csv_export__WEBPACK_IMPORTED_MODULE_23__["downloadCSVFile"])(Object(_woocommerce_csv_export__WEBPACK_IMPORTED_MODULE_23__["generateCSVFileName"])(title, params), Object(_woocommerce_csv_export__WEBPACK_IMPORTED_MODULE_23__["generateCSVDataFromTable"])(getHeadersContent(), getRowsContent(data)));
      } else {
        downloadType = 'email';
        initiateReportExport(endpoint, title, reportQuery);
      }

      Object(lib_tracks__WEBPACK_IMPORTED_MODULE_30__["recordEvent"])('analytics_table_download', {
        report: endpoint,
        rows: totalResults,
        downloadType: downloadType
      });
    }
  }, {
    key: "onCompare",
    value: function onCompare() {
      var _this$props5 = this.props,
          compareBy = _this$props5.compareBy,
          compareParam = _this$props5.compareParam;
      var selectedRows = this.state.selectedRows;

      if (compareBy) {
        Object(_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_22__["onQueryChange"])('compare')(compareBy, compareParam, selectedRows.join(','));
      }
    }
  }, {
    key: "onSearchChange",
    value: function onSearchChange(values) {
      var _this$props6 = this.props,
          baseSearchQuery = _this$props6.baseSearchQuery,
          compareParam = _this$props6.compareParam,
          searchBy = _this$props6.searchBy; // A comma is used as a separator between search terms, so we want to escape
      // any comma they contain.

      var labels = values.map(function (v) {
        return v.label.replace(',', '%2C');
      });

      if (labels.length) {
        var _objectSpread2;

        Object(_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_22__["updateQueryString"])(_objectSpread((_objectSpread2 = {
          filter: undefined
        }, _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_3___default()(_objectSpread2, compareParam, undefined), _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_3___default()(_objectSpread2, searchBy, undefined), _objectSpread2), baseSearchQuery, {
          search: Object(lodash__WEBPACK_IMPORTED_MODULE_16__["uniq"])(labels).join(',')
        }));
      } else {
        Object(_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_22__["updateQueryString"])({
          search: undefined
        });
      }

      this.trackTableSearch();
    }
  }, {
    key: "selectAllRows",
    value: function selectAllRows(checked) {
      var ids = this.props.ids;
      this.setState({
        selectedRows: checked ? ids : []
      });
    }
  }, {
    key: "selectRow",
    value: function selectRow(i, checked) {
      var ids = this.props.ids;

      if (checked) {
        this.setState(function (_ref2) {
          var selectedRows = _ref2.selectedRows;
          return {
            selectedRows: Object(lodash__WEBPACK_IMPORTED_MODULE_16__["uniq"])([ids[i]].concat(_babel_runtime_helpers_toConsumableArray__WEBPACK_IMPORTED_MODULE_2___default()(selectedRows)))
          };
        });
      } else {
        this.setState(function (_ref3) {
          var selectedRows = _ref3.selectedRows;
          var index = selectedRows.indexOf(ids[i]);
          return {
            selectedRows: [].concat(_babel_runtime_helpers_toConsumableArray__WEBPACK_IMPORTED_MODULE_2___default()(selectedRows.slice(0, index)), _babel_runtime_helpers_toConsumableArray__WEBPACK_IMPORTED_MODULE_2___default()(selectedRows.slice(index + 1)))
          };
        });
      }
    }
  }, {
    key: "getCheckbox",
    value: function getCheckbox(i) {
      var _this$props$ids = this.props.ids,
          ids = _this$props$ids === void 0 ? [] : _this$props$ids;
      var selectedRows = this.state.selectedRows;
      var isChecked = selectedRows.indexOf(ids[i]) !== -1;
      return {
        display: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_11__["CheckboxControl"], {
          onChange: Object(lodash__WEBPACK_IMPORTED_MODULE_16__["partial"])(this.selectRow, i),
          checked: isChecked
        }),
        value: false
      };
    }
  }, {
    key: "getAllCheckbox",
    value: function getAllCheckbox() {
      var _this$props$ids2 = this.props.ids,
          ids = _this$props$ids2 === void 0 ? [] : _this$props$ids2;
      var selectedRows = this.state.selectedRows;
      var hasData = ids.length > 0;
      var isAllChecked = hasData && ids.length === selectedRows.length;
      return {
        cellClassName: 'is-checkbox-column',
        key: 'compare',
        label: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_11__["CheckboxControl"], {
          onChange: this.selectAllRows,
          "aria-label": Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_17__["__"])('Select All'),
          checked: isAllChecked,
          disabled: !hasData
        }),
        required: true
      };
    }
  }, {
    key: "render",
    value: function render() {
      var _this2 = this;

      var selectedRows = this.state.selectedRows;

      var _this$props7 = this.props,
          getHeadersContent = _this$props7.getHeadersContent,
          getRowsContent = _this$props7.getRowsContent,
          getSummary = _this$props7.getSummary,
          isRequesting = _this$props7.isRequesting,
          primaryData = _this$props7.primaryData,
          tableData = _this$props7.tableData,
          endpoint = _this$props7.endpoint,
          itemIdField = _this$props7.itemIdField,
          tableQuery = _this$props7.tableQuery,
          userPrefColumns = _this$props7.userPrefColumns,
          compareBy = _this$props7.compareBy,
          searchBy = _this$props7.searchBy,
          _this$props7$labels = _this$props7.labels,
          labels = _this$props7$labels === void 0 ? {} : _this$props7$labels,
          tableProps = _babel_runtime_helpers_objectWithoutProperties__WEBPACK_IMPORTED_MODULE_1___default()(_this$props7, ["getHeadersContent", "getRowsContent", "getSummary", "isRequesting", "primaryData", "tableData", "endpoint", "itemIdField", "tableQuery", "userPrefColumns", "compareBy", "searchBy", "labels"]);

      var items = tableData.items,
          query = tableData.query;
      var isError = tableData.isError || primaryData.isError;

      if (isError) {
        return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__["createElement"])(analytics_components_report_error__WEBPACK_IMPORTED_MODULE_25__["default"], {
          isError: true
        });
      }

      var isLoading = isRequesting || tableData.isRequesting || primaryData.isRequesting;
      var totals = Object(lodash__WEBPACK_IMPORTED_MODULE_16__["get"])(primaryData, ['data', 'totals'], {});
      var totalResults = items.totalResults;
      var downloadable = totalResults > 0; // Search words are in the query string, not the table query.

      var searchWords = Object(_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_22__["getSearchWords"])(this.props.query);
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

      var filteredTableProps = Object(_wordpress_hooks__WEBPACK_IMPORTED_MODULE_12__["applyFilters"])(TABLE_FILTER, {
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
          return [_this2.getCheckbox(i)].concat(_babel_runtime_helpers_toConsumableArray__WEBPACK_IMPORTED_MODULE_2___default()(row));
        });
        headers = [this.getAllCheckbox()].concat(_babel_runtime_helpers_toConsumableArray__WEBPACK_IMPORTED_MODULE_2___default()(headers));
      } // Hide any headers based on user prefs, if loaded.


      var filteredHeaders = this.filterShownHeaders(headers, userPrefColumns);
      var className = classnames__WEBPACK_IMPORTED_MODULE_18___default()('woocommerce-report-table', {
        'has-compare': !!compareBy,
        'has-search': !!searchBy
      });
      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__["createElement"])(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__["Fragment"], null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__["createElement"])("div", {
        className: "woocommerce-report-table__scroll-point",
        ref: this.scrollPointRef,
        "aria-hidden": true
      }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_20__["TableCard"], _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0___default()({
        className: className,
        actions: [compareBy && Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_20__["CompareButton"], {
          key: "compare",
          className: "woocommerce-table__compare",
          count: selectedRows.length,
          helpText: labels.helpText || Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_17__["__"])('Check at least two items below to compare', 'woocommerce'),
          onClick: this.onCompare,
          disabled: !downloadable
        }, labels.compareButton || Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_17__["__"])('Compare', 'woocommerce')), searchBy && Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_20__["Search"], {
          allowFreeTextSearch: true,
          inlineTags: true,
          key: "search",
          onChange: this.onSearchChange,
          placeholder: labels.placeholder || Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_17__["__"])('Search by item name', 'woocommerce'),
          selected: searchedLabels,
          showClearButton: true,
          type: searchBy,
          disabled: !downloadable
        }), downloadable && Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_11__["IconButton"], {
          key: "download",
          className: "woocommerce-table__download-button",
          disabled: isLoading,
          onClick: this.onClickDownload,
          isLink: true
        }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__["createElement"])(_download_icon__WEBPACK_IMPORTED_MODULE_21__["default"], null), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__["createElement"])("span", {
          className: "woocommerce-table__download-button__label"
        }, labels.downloadButton || Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_17__["__"])('Download', 'woocommerce')))],
        headers: filteredHeaders,
        isLoading: isLoading,
        onQueryChange: _woocommerce_navigation__WEBPACK_IMPORTED_MODULE_22__["onQueryChange"],
        onColumnsChange: this.onColumnsChange,
        onSort: this.onSort,
        onPageChange: this.onPageChange,
        rows: rows,
        rowsPerPage: parseInt(query.per_page, 10) || wc_api_constants__WEBPACK_IMPORTED_MODULE_27__["QUERY_DEFAULTS"].pageSize,
        summary: summary,
        totalRows: totalResults
      }, tableProps)));
    }
  }]);

  return ReportTable;
}(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__["Component"]);

ReportTable.propTypes = {
  /**
   * Pass in query parameters to be included in the path when onSearch creates a new url.
   */
  baseSearchQuery: prop_types__WEBPACK_IMPORTED_MODULE_19___default.a.object,

  /**
   * The string to use as a query parameter when comparing row items.
   */
  compareBy: prop_types__WEBPACK_IMPORTED_MODULE_19___default.a.string,

  /**
   * Url query parameter compare function operates on
   */
  compareParam: prop_types__WEBPACK_IMPORTED_MODULE_19___default.a.string,

  /**
   * The key for user preferences settings for column visibility.
   */
  columnPrefsKey: prop_types__WEBPACK_IMPORTED_MODULE_19___default.a.string,

  /**
   * The endpoint to use in API calls to populate the table rows and summary.
   * For example, if `taxes` is provided, data will be fetched from the report
   * `taxes` endpoint (ie: `/wc-analytics/reports/taxes` and `/wc/v4/reports/taxes/stats`).
   * If the provided endpoint doesn't exist, an error will be shown to the user
   * with `ReportError`.
   */
  endpoint: prop_types__WEBPACK_IMPORTED_MODULE_19___default.a.string,

  /**
   * Name of the methods available via `select( 'wc-api' )` that will be used to
   * load more data for table items. If omitted, no call will be made and only
   * the data returned by the reports endpoint will be used.
   */
  extendItemsMethodNames: prop_types__WEBPACK_IMPORTED_MODULE_19___default.a.shape({
    getError: prop_types__WEBPACK_IMPORTED_MODULE_19___default.a.string,
    isRequesting: prop_types__WEBPACK_IMPORTED_MODULE_19___default.a.string,
    load: prop_types__WEBPACK_IMPORTED_MODULE_19___default.a.string
  }),

  /**
   * A function that returns the headers object to build the table.
   */
  getHeadersContent: prop_types__WEBPACK_IMPORTED_MODULE_19___default.a.func.isRequired,

  /**
   * A function that returns the rows array to build the table.
   */
  getRowsContent: prop_types__WEBPACK_IMPORTED_MODULE_19___default.a.func.isRequired,

  /**
   * A function that returns the summary object to build the table.
   */
  getSummary: prop_types__WEBPACK_IMPORTED_MODULE_19___default.a.func,

  /**
   * The name of the property in the item object which contains the id.
   */
  itemIdField: prop_types__WEBPACK_IMPORTED_MODULE_19___default.a.string,

  /**
   * Custom labels for table header actions.
   */
  labels: prop_types__WEBPACK_IMPORTED_MODULE_19___default.a.shape({
    compareButton: prop_types__WEBPACK_IMPORTED_MODULE_19___default.a.string,
    downloadButton: prop_types__WEBPACK_IMPORTED_MODULE_19___default.a.string,
    helpText: prop_types__WEBPACK_IMPORTED_MODULE_19___default.a.string,
    placeholder: prop_types__WEBPACK_IMPORTED_MODULE_19___default.a.string
  }),

  /**
   * Primary data of that report. If it's not provided, it will be automatically
   * loaded via the provided `endpoint`.
   */
  primaryData: prop_types__WEBPACK_IMPORTED_MODULE_19___default.a.object,

  /**
   * The string to use as a query parameter when searching row items.
   */
  searchBy: prop_types__WEBPACK_IMPORTED_MODULE_19___default.a.string,

  /**
   * List of fields used for summary numbers. (Reduces queries)
   */
  summaryFields: prop_types__WEBPACK_IMPORTED_MODULE_19___default.a.arrayOf(prop_types__WEBPACK_IMPORTED_MODULE_19___default.a.string),

  /**
   * Table data of that report. If it's not provided, it will be automatically
   * loaded via the provided `endpoint`.
   */
  tableData: prop_types__WEBPACK_IMPORTED_MODULE_19___default.a.object.isRequired,

  /**
   * Properties to be added to the query sent to the report table endpoint.
   */
  tableQuery: prop_types__WEBPACK_IMPORTED_MODULE_19___default.a.object,

  /**
   * String to display as the title of the table.
   */
  title: prop_types__WEBPACK_IMPORTED_MODULE_19___default.a.string.isRequired
};
ReportTable.defaultProps = {
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
  onSearch: lodash__WEBPACK_IMPORTED_MODULE_16__["noop"],
  baseSearchQuery: {}
};
/* harmony default export */ __webpack_exports__["default"] = (Object(_wordpress_compose__WEBPACK_IMPORTED_MODULE_13__["compose"])(Object(wc_api_with_select__WEBPACK_IMPORTED_MODULE_28__["default"])(function (select, props) {
  var endpoint = props.endpoint,
      getSummary = props.getSummary,
      isRequesting = props.isRequesting,
      itemIdField = props.itemIdField,
      query = props.query,
      tableData = props.tableData,
      tableQuery = props.tableQuery,
      columnPrefsKey = props.columnPrefsKey,
      filters = props.filters,
      advancedFilters = props.advancedFilters,
      summaryFields = props.summaryFields;
  var userPrefColumns = [];

  if (columnPrefsKey) {
    var _select = select('wc-api'),
        getCurrentUserData = _select.getCurrentUserData;

    var userData = getCurrentUserData();
    userPrefColumns = userData && userData[columnPrefsKey] ? userData[columnPrefsKey] : userPrefColumns;
  }

  if (isRequesting || query.search && !(query[endpoint] && query[endpoint].length)) {
    return {
      userPrefColumns: userPrefColumns
    };
  }

  var _select$getSetting = select(_woocommerce_data__WEBPACK_IMPORTED_MODULE_24__["SETTINGS_STORE_NAME"]).getSetting('wc_admin', 'wcAdminSettings'),
      defaultDateRange = _select$getSetting.woocommerce_default_date_range; // Variations and Category charts are powered by the /reports/products/stats endpoint.


  var chartEndpoint = ['variations', 'categories'].includes(endpoint) ? 'products' : endpoint;
  var primaryData = getSummary ? Object(wc_api_reports_utils__WEBPACK_IMPORTED_MODULE_26__["getReportChartData"])({
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
  var queriedTableData = tableData || Object(wc_api_reports_utils__WEBPACK_IMPORTED_MODULE_26__["getReportTableData"])({
    endpoint: endpoint,
    query: query,
    select: select,
    tableQuery: tableQuery,
    filters: filters,
    advancedFilters: advancedFilters,
    defaultDateRange: defaultDateRange
  });
  var extendedTableData = Object(_utils__WEBPACK_IMPORTED_MODULE_29__["extendTableData"])(select, props, queriedTableData);
  return {
    primaryData: primaryData,
    ids: itemIdField ? extendedTableData.items.data.map(function (item) {
      return item[itemIdField];
    }) : [],
    tableData: extendedTableData,
    query: _objectSpread({}, tableQuery, {}, query),
    userPrefColumns: userPrefColumns
  };
}), Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_15__["withDispatch"])(function (dispatch) {
  var _dispatch = dispatch('wc-api'),
      initiateReportExport = _dispatch.initiateReportExport,
      updateCurrentUserData = _dispatch.updateCurrentUserData;

  return {
    initiateReportExport: initiateReportExport,
    updateCurrentUserData: updateCurrentUserData
  };
}))(ReportTable));

/***/ }),

/***/ "./client/analytics/components/report-table/style.scss":
/*!*************************************************************!*\
  !*** ./client/analytics/components/report-table/style.scss ***!
  \*************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),

/***/ "./client/analytics/components/report-table/utils.js":
/*!***********************************************************!*\
  !*** ./client/analytics/components/report-table/utils.js ***!
  \***********************************************************/
/*! exports provided: extendTableData */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "extendTableData", function() { return extendTableData; });
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/defineProperty */ "./node_modules/@babel/runtime/helpers/defineProperty.js");
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! lodash */ "lodash");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_1__);


function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0___default()(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

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
    var extendedItemData = Object(lodash__WEBPACK_IMPORTED_MODULE_1__["first"])(extendedItems.filter(function (extendedItem) {
      return item.id === extendedItem.id;
    }));
    return _objectSpread({}, item, {}, extendedItemData);
  });
  var isRequesting = queriedTableData.isRequesting || isExtendedItemsRequesting;
  var isError = queriedTableData.isError || isExtendedItemsError;
  return _objectSpread({}, queriedTableData, {
    isRequesting: isRequesting,
    isError: isError,
    items: _objectSpread({}, queriedTableData.items, {
      data: extendedItemsData
    })
  });
}

/***/ })

}]);
//# sourceMappingURL=analytics-report-categories~analytics-report-coupons~analytics-report-customers~analytics-report-dow~99eefb40.fe06418122b476ca2c68.min.js.map
