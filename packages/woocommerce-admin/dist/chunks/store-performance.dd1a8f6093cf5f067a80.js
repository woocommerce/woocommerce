(window["webpackJsonp"] = window["webpackJsonp"] || []).push([["store-performance"],{

/***/ "./client/dashboard/store-performance/index.js":
/*!*****************************************************!*\
  !*** ./client/dashboard/store-performance/index.js ***!
  \*****************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/classCallCheck */ "./node_modules/@babel/runtime/helpers/classCallCheck.js");
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/createClass */ "./node_modules/@babel/runtime/helpers/createClass.js");
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @babel/runtime/helpers/possibleConstructorReturn */ "./node_modules/@babel/runtime/helpers/possibleConstructorReturn.js");
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @babel/runtime/helpers/getPrototypeOf */ "./node_modules/@babel/runtime/helpers/getPrototypeOf.js");
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @babel/runtime/helpers/inherits */ "./node_modules/@babel/runtime/helpers/inherits.js");
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @wordpress/compose */ "./node_modules/@wordpress/compose/build-module/index.js");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_8__);
/* harmony import */ var moment__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! moment */ "moment");
/* harmony import */ var moment__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(moment__WEBPACK_IMPORTED_MODULE_9__);
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! lodash */ "lodash");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_10___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_10__);
/* harmony import */ var lib_date__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! lib/date */ "./client/lib/date.js");
/* harmony import */ var _woocommerce_navigation__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! @woocommerce/navigation */ "@woocommerce/navigation");
/* harmony import */ var _woocommerce_navigation__WEBPACK_IMPORTED_MODULE_12___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_12__);
/* harmony import */ var _woocommerce_number__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! @woocommerce/number */ "@woocommerce/number");
/* harmony import */ var _woocommerce_number__WEBPACK_IMPORTED_MODULE_13___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_number__WEBPACK_IMPORTED_MODULE_13__);
/* harmony import */ var _woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! @woocommerce/wc-admin-settings */ "./client/settings/index.js");
/* harmony import */ var _woocommerce_data__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(/*! @woocommerce/data */ "@woocommerce/data");
/* harmony import */ var _woocommerce_data__WEBPACK_IMPORTED_MODULE_15___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_data__WEBPACK_IMPORTED_MODULE_15__);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__(/*! @woocommerce/components */ "@woocommerce/components");
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_16___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_components__WEBPACK_IMPORTED_MODULE_16__);
/* harmony import */ var wc_api_with_select__WEBPACK_IMPORTED_MODULE_17__ = __webpack_require__(/*! wc-api/with-select */ "./client/wc-api/with-select.js");
/* harmony import */ var _style_scss__WEBPACK_IMPORTED_MODULE_18__ = __webpack_require__(/*! ./style.scss */ "./client/dashboard/store-performance/style.scss");
/* harmony import */ var _style_scss__WEBPACK_IMPORTED_MODULE_18___default = /*#__PURE__*/__webpack_require__.n(_style_scss__WEBPACK_IMPORTED_MODULE_18__);
/* harmony import */ var lib_tracks__WEBPACK_IMPORTED_MODULE_19__ = __webpack_require__(/*! lib/tracks */ "./client/lib/tracks.js");
/* harmony import */ var lib_currency_context__WEBPACK_IMPORTED_MODULE_20__ = __webpack_require__(/*! lib/currency-context */ "./client/lib/currency-context.js");







function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function () { var Super = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_3___default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_3___default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_2___default()(this, result); }; }

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







var _getSetting = Object(_woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_14__["getSetting"])('dataEndpoints', {
  performanceIndicators: []
}),
    indicators = _getSetting.performanceIndicators;

var StorePerformance = /*#__PURE__*/function (_Component) {
  _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_4___default()(StorePerformance, _Component);

  var _super = _createSuper(StorePerformance);

  function StorePerformance() {
    _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0___default()(this, StorePerformance);

    return _super.apply(this, arguments);
  }

  _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1___default()(StorePerformance, [{
    key: "renderMenu",
    value: function renderMenu() {
      var _this$props = this.props,
          hiddenBlocks = _this$props.hiddenBlocks,
          isFirst = _this$props.isFirst,
          isLast = _this$props.isLast,
          onMove = _this$props.onMove,
          onRemove = _this$props.onRemove,
          onTitleBlur = _this$props.onTitleBlur,
          onTitleChange = _this$props.onTitleChange,
          onToggleHiddenBlock = _this$props.onToggleHiddenBlock,
          titleInput = _this$props.titleInput,
          Controls = _this$props.controls;
      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_16__["EllipsisMenu"], {
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__["__"])('Choose which analytics to display and the section name', 'woocommerce'),
        renderContent: function renderContent(_ref) {
          var onToggle = _ref.onToggle;
          return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["Fragment"], null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_16__["MenuTitle"], null, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__["__"])('Display Stats:', 'woocommerce')), indicators.map(function (indicator, i) {
            var checked = !hiddenBlocks.includes(indicator.stat);
            return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_16__["MenuItem"], {
              checked: checked,
              isCheckbox: true,
              isClickable: true,
              key: i,
              onInvoke: function onInvoke() {
                onToggleHiddenBlock(indicator.stat)();
                Object(lib_tracks__WEBPACK_IMPORTED_MODULE_19__["recordEvent"])('dash_indicators_toggle', {
                  status: checked ? 'off' : 'on',
                  key: indicator.stat
                });
              }
            }, indicator.label);
          }),  true && Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(Controls, {
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
    }
  }, {
    key: "renderList",
    value: function renderList() {
      var _this$props2 = this.props,
          query = _this$props2.query,
          primaryRequesting = _this$props2.primaryRequesting,
          secondaryRequesting = _this$props2.secondaryRequesting,
          primaryError = _this$props2.primaryError,
          secondaryError = _this$props2.secondaryError,
          primaryData = _this$props2.primaryData,
          secondaryData = _this$props2.secondaryData,
          userIndicators = _this$props2.userIndicators,
          defaultDateRange = _this$props2.defaultDateRange;

      if (primaryRequesting || secondaryRequesting) {
        return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_16__["SummaryListPlaceholder"], {
          numberOfItems: userIndicators.length
        });
      }

      if (primaryError || secondaryError) {
        return null;
      }

      var persistedQuery = Object(_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_12__["getPersistedQuery"])(query);

      var _getDateParamsFromQue = Object(lib_date__WEBPACK_IMPORTED_MODULE_11__["getDateParamsFromQuery"])(query, defaultDateRange),
          compare = _getDateParamsFromQue.compare;

      var prevLabel = compare === 'previous_period' ? Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__["__"])('Previous Period:', 'woocommerce') : Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__["__"])('Previous Year:', 'woocommerce');
      var _this$context = this.context,
          formatCurrency = _this$context.formatCurrency,
          getCurrency = _this$context.getCurrency;
      var currency = getCurrency();
      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_16__["SummaryList"], null, function () {
        return userIndicators.map(function (indicator, i) {
          var primaryItem = Object(lodash__WEBPACK_IMPORTED_MODULE_10__["find"])(primaryData.data, function (data) {
            return data.stat === indicator.stat;
          });
          var secondaryItem = Object(lodash__WEBPACK_IMPORTED_MODULE_10__["find"])(secondaryData.data, function (data) {
            return data.stat === indicator.stat;
          });

          if (!primaryItem || !secondaryItem) {
            return null;
          }

          var href = primaryItem._links && primaryItem._links.report[0] && primaryItem._links.report[0].href || '';
          var reportUrl = href && Object(_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_12__["getNewPath"])(persistedQuery, href, {
            chart: primaryItem.chart
          }) || '';
          var isCurrency = primaryItem.format === 'currency';
          var delta = Object(_woocommerce_number__WEBPACK_IMPORTED_MODULE_13__["calculateDelta"])(primaryItem.value, secondaryItem.value);
          var primaryValue = isCurrency ? formatCurrency(primaryItem.value) : Object(_woocommerce_number__WEBPACK_IMPORTED_MODULE_13__["formatValue"])(currency, primaryItem.format, primaryItem.value);
          var secondaryValue = isCurrency ? formatCurrency(secondaryItem.value) : Object(_woocommerce_number__WEBPACK_IMPORTED_MODULE_13__["formatValue"])(currency, secondaryItem.format, secondaryItem.value);
          return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_16__["SummaryNumber"], {
            key: i,
            href: reportUrl,
            label: indicator.label,
            value: primaryValue,
            prevLabel: prevLabel,
            prevValue: secondaryValue,
            delta: delta,
            onLinkClickCallback: function onLinkClickCallback() {
              Object(lib_tracks__WEBPACK_IMPORTED_MODULE_19__["recordEvent"])('dash_indicators_click', {
                key: indicator.stat
              });
            }
          });
        });
      });
    }
  }, {
    key: "render",
    value: function render() {
      var _this$props3 = this.props,
          userIndicators = _this$props3.userIndicators,
          title = _this$props3.title;
      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["Fragment"], null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_16__["SectionHeader"], {
        title: title || Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__["__"])('Store Performance', 'woocommerce'),
        menu: this.renderMenu()
      }), userIndicators.length > 0 && Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])("div", {
        className: "woocommerce-dashboard__store-performance"
      }, this.renderList()));
    }
  }]);

  return StorePerformance;
}(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["Component"]);

StorePerformance.contextType = lib_currency_context__WEBPACK_IMPORTED_MODULE_20__["CurrencyContext"];
/* harmony default export */ __webpack_exports__["default"] = (Object(_wordpress_compose__WEBPACK_IMPORTED_MODULE_7__["compose"])(Object(wc_api_with_select__WEBPACK_IMPORTED_MODULE_17__["default"])(function (select, props) {
  var hiddenBlocks = props.hiddenBlocks,
      query = props.query;

  var _select = select('wc-api'),
      getReportItems = _select.getReportItems,
      getReportItemsError = _select.getReportItemsError,
      isReportItemsRequesting = _select.isReportItemsRequesting;

  var _select$getSetting = select(_woocommerce_data__WEBPACK_IMPORTED_MODULE_15__["SETTINGS_STORE_NAME"]).getSetting('wc_admin', 'wcAdminSettings'),
      defaultDateRange = _select$getSetting.woocommerce_default_date_range;

  var datesFromQuery = Object(lib_date__WEBPACK_IMPORTED_MODULE_11__["getCurrentDates"])(query, defaultDateRange);
  var endPrimary = datesFromQuery.primary.before;
  var endSecondary = datesFromQuery.secondary.before;
  var userIndicators = indicators.filter(function (indicator) {
    return !hiddenBlocks.includes(indicator.stat);
  });
  var statKeys = userIndicators.map(function (indicator) {
    return indicator.stat;
  }).join(',');

  if (statKeys.length === 0) {
    return {
      hiddenBlocks: hiddenBlocks,
      userIndicators: userIndicators,
      indicators: indicators,
      defaultDateRange: defaultDateRange
    };
  }

  var primaryQuery = {
    after: Object(lib_date__WEBPACK_IMPORTED_MODULE_11__["appendTimestamp"])(datesFromQuery.primary.after, 'start'),
    before: Object(lib_date__WEBPACK_IMPORTED_MODULE_11__["appendTimestamp"])(endPrimary, endPrimary.isSame(moment__WEBPACK_IMPORTED_MODULE_9___default()(), 'day') ? 'now' : 'end'),
    stats: statKeys
  };
  var secondaryQuery = {
    after: Object(lib_date__WEBPACK_IMPORTED_MODULE_11__["appendTimestamp"])(datesFromQuery.secondary.after, 'start'),
    before: Object(lib_date__WEBPACK_IMPORTED_MODULE_11__["appendTimestamp"])(endSecondary, endSecondary.isSame(moment__WEBPACK_IMPORTED_MODULE_9___default()(), 'day') ? 'now' : 'end'),
    stats: statKeys
  };
  var primaryData = getReportItems('performance-indicators', primaryQuery);
  var primaryError = getReportItemsError('performance-indicators', primaryQuery) || null;
  var primaryRequesting = isReportItemsRequesting('performance-indicators', primaryQuery);
  var secondaryData = getReportItems('performance-indicators', secondaryQuery);
  var secondaryError = getReportItemsError('performance-indicators', secondaryQuery) || null;
  var secondaryRequesting = isReportItemsRequesting('performance-indicators', secondaryQuery);
  return {
    hiddenBlocks: hiddenBlocks,
    userIndicators: userIndicators,
    indicators: indicators,
    primaryData: primaryData,
    primaryError: primaryError,
    primaryRequesting: primaryRequesting,
    secondaryData: secondaryData,
    secondaryError: secondaryError,
    secondaryRequesting: secondaryRequesting,
    defaultDateRange: defaultDateRange
  };
}), Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_8__["withDispatch"])(function (dispatch) {
  var _dispatch = dispatch('wc-api'),
      updateCurrentUserData = _dispatch.updateCurrentUserData;

  return {
    updateCurrentUserData: updateCurrentUserData
  };
}))(StorePerformance));

/***/ }),

/***/ "./client/dashboard/store-performance/style.scss":
/*!*******************************************************!*\
  !*** ./client/dashboard/store-performance/style.scss ***!
  \*******************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ })

}]);
//# sourceMappingURL=store-performance.dd1a8f6093cf5f067a80.min.js.map
