(window["webpackJsonp"] = window["webpackJsonp"] || []).push([["customizable-dashboard"],{

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
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @babel/runtime/helpers/inherits */ "./node_modules/@babel/runtime/helpers/inherits.js");
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @babel/runtime/helpers/possibleConstructorReturn */ "./node_modules/@babel/runtime/helpers/possibleConstructorReturn.js");
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @babel/runtime/helpers/getPrototypeOf */ "./node_modules/@babel/runtime/helpers/getPrototypeOf.js");
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_6__);
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

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_6___default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_6___default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_5___default()(this, result); }; }

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
  _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_4___default()(ReportFilters, _Component);

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
        currency: Currency.getCurrency(),
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

/***/ "./client/dashboard/customizable.js":
/*!******************************************!*\
  !*** ./client/dashboard/customizable.js ***!
  \******************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _babel_runtime_helpers_slicedToArray__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/slicedToArray */ "./node_modules/@babel/runtime/helpers/slicedToArray.js");
/* harmony import */ var _babel_runtime_helpers_slicedToArray__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_slicedToArray__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _babel_runtime_helpers_objectWithoutProperties__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/objectWithoutProperties */ "./node_modules/@babel/runtime/helpers/objectWithoutProperties.js");
/* harmony import */ var _babel_runtime_helpers_objectWithoutProperties__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_objectWithoutProperties__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @babel/runtime/helpers/defineProperty */ "./node_modules/@babel/runtime/helpers/defineProperty.js");
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _babel_runtime_helpers_toConsumableArray__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @babel/runtime/helpers/toConsumableArray */ "./node_modules/@babel/runtime/helpers/toConsumableArray.js");
/* harmony import */ var _babel_runtime_helpers_toConsumableArray__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_toConsumableArray__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @wordpress/compose */ "./node_modules/@wordpress/compose/build-module/index.js");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! lodash */ "lodash");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! @wordpress/components */ "./node_modules/@wordpress/components/build-module/index.js");
/* harmony import */ var _wordpress_hooks__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! @wordpress/hooks */ "@wordpress/hooks");
/* harmony import */ var _wordpress_hooks__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(_wordpress_hooks__WEBPACK_IMPORTED_MODULE_9__);
/* harmony import */ var _wordpress_icons__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! @wordpress/icons */ "./node_modules/@wordpress/icons/build-module/index.js");
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! @woocommerce/components */ "@woocommerce/components");
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_11___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_components__WEBPACK_IMPORTED_MODULE_11__);
/* harmony import */ var _woocommerce_data__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! @woocommerce/data */ "@woocommerce/data");
/* harmony import */ var _woocommerce_data__WEBPACK_IMPORTED_MODULE_12___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_data__WEBPACK_IMPORTED_MODULE_12__);
/* harmony import */ var _style_scss__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! ./style.scss */ "./client/dashboard/style.scss");
/* harmony import */ var _style_scss__WEBPACK_IMPORTED_MODULE_13___default = /*#__PURE__*/__webpack_require__.n(_style_scss__WEBPACK_IMPORTED_MODULE_13__);
/* harmony import */ var _default_sections__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! ./default-sections */ "./client/dashboard/default-sections.js");
/* harmony import */ var _section__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(/*! ./section */ "./client/dashboard/section.js");
/* harmony import */ var wc_api_with_select__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__(/*! wc-api/with-select */ "./client/wc-api/with-select.js");
/* harmony import */ var lib_tracks__WEBPACK_IMPORTED_MODULE_17__ = __webpack_require__(/*! lib/tracks */ "./client/lib/tracks.js");
/* harmony import */ var dashboard_utils__WEBPACK_IMPORTED_MODULE_18__ = __webpack_require__(/*! dashboard/utils */ "./client/dashboard/utils.js");
/* harmony import */ var lib_date__WEBPACK_IMPORTED_MODULE_19__ = __webpack_require__(/*! lib/date */ "./client/lib/date.js");
/* harmony import */ var analytics_components_report_filters__WEBPACK_IMPORTED_MODULE_20__ = __webpack_require__(/*! analytics/components/report-filters */ "./client/analytics/components/report-filters/index.js");






function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_2___default()(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

/**
 * External dependencies
 */







/**
 * WooCommerce dependencies
 */



/**
 * Internal dependencies
 */









var TaskList = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_4__["lazy"])(function () {
  return Promise.all(/*! import() | task-list */[__webpack_require__.e("vendors~activity-panels-inbox~homescreen~leaderboards~store-alerts~task-list"), __webpack_require__.e("homescreen~task-list"), __webpack_require__.e("task-list")]).then(__webpack_require__.bind(null, /*! ../task-list */ "./client/task-list/index.js"));
});
var DASHBOARD_FILTERS_FILTER = 'woocommerce_admin_dashboard_filters';
var filters = Object(_wordpress_hooks__WEBPACK_IMPORTED_MODULE_9__["applyFilters"])(DASHBOARD_FILTERS_FILTER, []);

var mergeSectionsWithDefaults = function mergeSectionsWithDefaults(prefSections) {
  if (!prefSections || prefSections.length === 0) {
    return _default_sections__WEBPACK_IMPORTED_MODULE_14__["default"];
  }

  var defaultKeys = _default_sections__WEBPACK_IMPORTED_MODULE_14__["default"].map(function (section) {
    return section.key;
  });
  var prefKeys = prefSections.map(function (section) {
    return section.key;
  });
  var keys = new Set([].concat(_babel_runtime_helpers_toConsumableArray__WEBPACK_IMPORTED_MODULE_3___default()(prefKeys), _babel_runtime_helpers_toConsumableArray__WEBPACK_IMPORTED_MODULE_3___default()(defaultKeys)));
  var sections = [];
  keys.forEach(function (key) {
    var defaultSection = _default_sections__WEBPACK_IMPORTED_MODULE_14__["default"].find(function (section) {
      return section.key === key;
    });

    if (!defaultSection) {
      return;
    }

    var prefSection = prefSections.find(function (section) {
      return section.key === key;
    });
    sections.push(_objectSpread(_objectSpread({}, defaultSection), prefSection));
  });
  return sections;
};

var CustomizableDashboard = function CustomizableDashboard(_ref) {
  var defaultDateRange = _ref.defaultDateRange,
      path = _ref.path,
      query = _ref.query,
      taskListComplete = _ref.taskListComplete,
      taskListHidden = _ref.taskListHidden;

  var _useUserPreferences = Object(_woocommerce_data__WEBPACK_IMPORTED_MODULE_12__["useUserPreferences"])(),
      isRequesting = _useUserPreferences.isRequesting,
      updateUserPreferences = _useUserPreferences.updateUserPreferences,
      userPrefs = _babel_runtime_helpers_objectWithoutProperties__WEBPACK_IMPORTED_MODULE_1___default()(_useUserPreferences, ["isRequesting", "updateUserPreferences"]);

  var _useState = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_4__["useState"])(isRequesting ? false : mergeSectionsWithDefaults(userPrefs.dashboard_sections)),
      _useState2 = _babel_runtime_helpers_slicedToArray__WEBPACK_IMPORTED_MODULE_0___default()(_useState, 2),
      dashSections = _useState2[0],
      setSections = _useState2[1]; // Update sections when the request is finished (and they weren't hydrated).


  if (!isRequesting && dashSections === false) {
    setSections(mergeSectionsWithDefaults(userPrefs.dashboard_sections));
  }

  var sections = dashSections || _default_sections__WEBPACK_IMPORTED_MODULE_14__["default"];
  var isTaskListEnabled = Object(dashboard_utils__WEBPACK_IMPORTED_MODULE_18__["isOnboardingEnabled"])() && !taskListHidden;
  var isDashboardShown = !isTaskListEnabled || !query.task && taskListComplete;

  var updateSections = function updateSections(newSections) {
    setSections(newSections);
    updateUserPreferences({
      dashboard_sections: newSections
    });
  };

  var updateSection = function updateSection(updatedKey, newSettings) {
    var newSections = sections.map(function (section) {
      if (section.key === updatedKey) {
        return _objectSpread(_objectSpread({}, section), newSettings);
      }

      return section;
    });
    updateSections(newSections);
  };

  var onChangeHiddenBlocks = function onChangeHiddenBlocks(updatedKey) {
    return function (updatedHiddenBlocks) {
      updateSection(updatedKey, {
        hiddenBlocks: updatedHiddenBlocks
      });
    };
  };

  var onSectionTitleUpdate = function onSectionTitleUpdate(updatedKey) {
    return function (updatedTitle) {
      Object(lib_tracks__WEBPACK_IMPORTED_MODULE_17__["recordEvent"])('dash_section_rename', {
        key: updatedKey
      });
      updateSection(updatedKey, {
        title: updatedTitle
      });
    };
  };

  var toggleVisibility = function toggleVisibility(key, onToggle) {
    return function () {
      if (onToggle) {
        // Close the dropdown before setting state so an action is not performed on an unmounted component.
        onToggle();
      } // When toggling visibility, place section at the end of the array.


      var index = sections.findIndex(function (s) {
        return key === s.key;
      });
      var toggledSection = sections.splice(index, 1).shift();
      toggledSection.isVisible = !toggledSection.isVisible;
      sections.push(toggledSection);

      if (toggledSection.isVisible) {
        Object(lib_tracks__WEBPACK_IMPORTED_MODULE_17__["recordEvent"])('dash_section_add', {
          key: toggledSection.key
        });
      } else {
        Object(lib_tracks__WEBPACK_IMPORTED_MODULE_17__["recordEvent"])('dash_section_remove', {
          key: toggledSection.key
        });
      }

      updateSections(sections);
    };
  };

  var onMove = function onMove(index, change) {
    var movedSection = sections.splice(index, 1).shift();
    var newIndex = index + change; // Figure out the index of the skipped section.

    var nextJumpedSectionIndex = change < 0 ? newIndex : newIndex - 1;

    if (sections[nextJumpedSectionIndex].isVisible || // Is the skipped section visible?
    index === 0 || // Will this be the first element?
    index === sections.length - 1 // Will this be the last element?
    ) {
        // Yes, lets insert.
        sections.splice(newIndex, 0, movedSection);
        updateSections(sections);
        var eventProps = {
          key: movedSection.key,
          direction: change > 0 ? 'down' : 'up'
        };
        Object(lib_tracks__WEBPACK_IMPORTED_MODULE_17__["recordEvent"])('dash_section_order_change', eventProps);
      } else {
      // No, lets try the next one.
      onMove(index, change + change);
    }
  };

  var renderAddMore = function renderAddMore() {
    var hiddenSections = sections.filter(function (section) {
      return section.isVisible === false;
    });

    if (hiddenSections.length === 0) {
      return null;
    }

    return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_4__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_8__["Dropdown"], {
      position: "top center",
      className: "woocommerce-dashboard-section__add-more",
      renderToggle: function renderToggle(_ref2) {
        var onToggle = _ref2.onToggle,
            isOpen = _ref2.isOpen;
        return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_4__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_8__["Button"], {
          onClick: onToggle,
          title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_5__["__"])('Add more sections', 'woocommerce'),
          "aria-expanded": isOpen
        }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_4__["createElement"])(_wordpress_icons__WEBPACK_IMPORTED_MODULE_10__["Icon"], {
          icon: _wordpress_icons__WEBPACK_IMPORTED_MODULE_10__["plusCircleFilled"]
        }));
      },
      renderContent: function renderContent(_ref3) {
        var onToggle = _ref3.onToggle;
        return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_4__["createElement"])(_wordpress_element__WEBPACK_IMPORTED_MODULE_4__["Fragment"], null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_4__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_11__["H"], null, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_5__["__"])('Dashboard Sections', 'woocommerce')), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_4__["createElement"])("div", {
          className: "woocommerce-dashboard-section__add-more-choices"
        }, hiddenSections.map(function (section) {
          return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_4__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_8__["Button"], {
            key: section.key,
            onClick: toggleVisibility(section.key, onToggle),
            className: "woocommerce-dashboard-section__add-more-btn",
            title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_5__["sprintf"])(Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_5__["__"])('Add %s section', 'woocommerce'), section.title)
          }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_4__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_8__["Icon"], {
            icon: section.icon,
            size: 30
          }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_4__["createElement"])("span", {
            className: "woocommerce-dashboard-section__add-more-btn-title"
          }, section.title));
        })));
      }
    });
  };

  var renderDashboardReports = function renderDashboardReports() {
    var _getDateParamsFromQue = Object(lib_date__WEBPACK_IMPORTED_MODULE_19__["getDateParamsFromQuery"])(query, defaultDateRange),
        period = _getDateParamsFromQue.period,
        compare = _getDateParamsFromQue.compare,
        before = _getDateParamsFromQue.before,
        after = _getDateParamsFromQue.after;

    var _getCurrentDates = Object(lib_date__WEBPACK_IMPORTED_MODULE_19__["getCurrentDates"])(query, defaultDateRange),
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
    var visibleSectionKeys = sections.filter(function (section) {
      return section.isVisible;
    }).map(function (section) {
      return section.key;
    });
    return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_4__["createElement"])(_wordpress_element__WEBPACK_IMPORTED_MODULE_4__["Fragment"], null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_4__["createElement"])(analytics_components_report_filters__WEBPACK_IMPORTED_MODULE_20__["default"], {
      report: "dashboard",
      query: query,
      path: path,
      dateQuery: dateQuery,
      isoDateFormat: lib_date__WEBPACK_IMPORTED_MODULE_19__["isoDateFormat"],
      filters: filters
    }), sections.map(function (section, index) {
      if (section.isVisible) {
        return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_4__["createElement"])(_section__WEBPACK_IMPORTED_MODULE_15__["default"], {
          component: section.component,
          hiddenBlocks: section.hiddenBlocks,
          key: section.key,
          onChangeHiddenBlocks: onChangeHiddenBlocks(section.key),
          onTitleUpdate: onSectionTitleUpdate(section.key),
          path: path,
          query: query,
          title: section.title,
          onMove: Object(lodash__WEBPACK_IMPORTED_MODULE_7__["partial"])(onMove, index),
          onRemove: toggleVisibility(section.key),
          isFirst: section.key === visibleSectionKeys[0],
          isLast: section.key === visibleSectionKeys[visibleSectionKeys.length - 1]
        });
      }

      return null;
    }), renderAddMore());
  };

  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_4__["createElement"])(_wordpress_element__WEBPACK_IMPORTED_MODULE_4__["Fragment"], null, isTaskListEnabled && Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_4__["createElement"])(_wordpress_element__WEBPACK_IMPORTED_MODULE_4__["Suspense"], {
    fallback: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_4__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_11__["Spinner"], null)
  }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_4__["createElement"])(TaskList, {
    query: query,
    inline: isDashboardShown
  })), isDashboardShown && renderDashboardReports());
};

/* harmony default export */ __webpack_exports__["default"] = (Object(_wordpress_compose__WEBPACK_IMPORTED_MODULE_6__["compose"])(Object(wc_api_with_select__WEBPACK_IMPORTED_MODULE_16__["default"])(function (select) {
  var _select = select(_woocommerce_data__WEBPACK_IMPORTED_MODULE_12__["OPTIONS_STORE_NAME"]),
      getOption = _select.getOption;

  var _select$getSetting = select(_woocommerce_data__WEBPACK_IMPORTED_MODULE_12__["SETTINGS_STORE_NAME"]).getSetting('wc_admin', 'wcAdminSettings'),
      defaultDateRange = _select$getSetting.woocommerce_default_date_range;

  var withSelectData = {
    defaultDateRange: defaultDateRange
  };

  if (Object(dashboard_utils__WEBPACK_IMPORTED_MODULE_18__["isOnboardingEnabled"])()) {
    withSelectData.homepageEnabled = window.wcAdminFeatures.homescreen && getOption('woocommerce_homescreen_enabled') === 'yes';
    withSelectData.taskListHidden = getOption('woocommerce_task_list_hidden') === 'yes';
    withSelectData.taskListComplete = getOption('woocommerce_task_list_complete') === 'yes';
  }

  return withSelectData;
}))(CustomizableDashboard));

/***/ }),

/***/ "./client/dashboard/default-sections.js":
/*!**********************************************!*\
  !*** ./client/dashboard/default-sections.js ***!
  \**********************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_hooks__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/hooks */ "@wordpress/hooks");
/* harmony import */ var _wordpress_hooks__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_hooks__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @woocommerce/components */ "@woocommerce/components");
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_components__WEBPACK_IMPORTED_MODULE_3__);


/**
 * External dependencies
 */



/**
 * Internal dependencies
 */


var LazyDashboardCharts = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["lazy"])(function () {
  return Promise.all(/*! import() | dashboard-charts */[__webpack_require__.e("vendors~analytics-report-categories~analytics-report-coupons~analytics-report-downloads~analytics-re~2579715d"), __webpack_require__.e("vendors~activity-panels-inbox~activity-panels-orders~activity-panels-stock~dashboard-charts~devdocs~~54068357"), __webpack_require__.e("analytics-report-categories~analytics-report-coupons~analytics-report-customers~analytics-report-dow~141c9af9"), __webpack_require__.e("analytics-report-categories~analytics-report-coupons~analytics-report-downloads~analytics-report-ord~21222c04"), __webpack_require__.e("analytics-report-downloads~dashboard-charts"), __webpack_require__.e("dashboard-charts")]).then(__webpack_require__.bind(null, /*! ./dashboard-charts */ "./client/dashboard/dashboard-charts/index.js"));
});
var LazyLeaderboards = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["lazy"])(function () {
  return Promise.all(/*! import() | leaderboards */[__webpack_require__.e("vendors~activity-panels-inbox~homescreen~leaderboards~store-alerts~task-list"), __webpack_require__.e("leaderboards")]).then(__webpack_require__.bind(null, /*! ./leaderboards */ "./client/dashboard/leaderboards/index.js"));
});
var LazyStorePerformance = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["lazy"])(function () {
  return __webpack_require__.e(/*! import() | store-performance */ "store-performance").then(__webpack_require__.bind(null, /*! ./store-performance */ "./client/dashboard/store-performance/index.js"));
});

var DashboardCharts = function DashboardCharts(props) {
  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["Suspense"], {
    fallback: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_3__["Spinner"], null)
  }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(LazyDashboardCharts, props));
};

var Leaderboards = function Leaderboards(props) {
  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["Suspense"], {
    fallback: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_3__["Spinner"], null)
  }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(LazyLeaderboards, props));
};

var StorePerformance = function StorePerformance(props) {
  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["Suspense"], {
    fallback: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_3__["Spinner"], null)
  }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(LazyStorePerformance, props));
};

var DEFAULT_SECTIONS_FILTER = 'woocommerce_dashboard_default_sections';
/* harmony default export */ __webpack_exports__["default"] = (Object(_wordpress_hooks__WEBPACK_IMPORTED_MODULE_2__["applyFilters"])(DEFAULT_SECTIONS_FILTER, [{
  key: 'store-performance',
  component: StorePerformance,
  title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Performance', 'woocommerce'),
  isVisible: true,
  icon: 'arrow-right-alt',
  hiddenBlocks: ['coupons/amount', 'coupons/orders_count', 'downloads/download_count', 'taxes/order_tax', 'taxes/total_tax', 'taxes/shipping_tax', 'revenue/shipping', 'orders/avg_order_value', 'revenue/refunds', 'revenue/gross_sales']
}, {
  key: 'charts',
  component: DashboardCharts,
  title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Charts', 'woocommerce'),
  isVisible: true,
  icon: 'chart-bar',
  hiddenBlocks: ['orders_avg_order_value', 'avg_items_per_order', 'products_items_sold', 'revenue_total_sales', 'revenue_refunds', 'coupons_amount', 'coupons_orders_count', 'revenue_shipping', 'taxes_total_tax', 'taxes_order_tax', 'taxes_shipping_tax', 'downloads_download_count']
}, {
  key: 'leaderboards',
  component: Leaderboards,
  title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Leaderboards', 'woocommerce'),
  isVisible: true,
  icon: 'editor-ol',
  hiddenBlocks: ['coupons', 'customers']
}]));

/***/ }),

/***/ "./client/dashboard/section-controls.js":
/*!**********************************************!*\
  !*** ./client/dashboard/section-controls.js ***!
  \**********************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/classCallCheck */ "./node_modules/@babel/runtime/helpers/classCallCheck.js");
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/createClass */ "./node_modules/@babel/runtime/helpers/createClass.js");
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @babel/runtime/helpers/assertThisInitialized */ "./node_modules/@babel/runtime/helpers/assertThisInitialized.js");
/* harmony import */ var _babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @babel/runtime/helpers/inherits */ "./node_modules/@babel/runtime/helpers/inherits.js");
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @babel/runtime/helpers/possibleConstructorReturn */ "./node_modules/@babel/runtime/helpers/possibleConstructorReturn.js");
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @babel/runtime/helpers/getPrototypeOf */ "./node_modules/@babel/runtime/helpers/getPrototypeOf.js");
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! @wordpress/components */ "./node_modules/@wordpress/components/build-module/index.js");
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! @woocommerce/components */ "@woocommerce/components");
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_components__WEBPACK_IMPORTED_MODULE_9__);








function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_5___default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_5___default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_4___default()(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/**
 * External dependencies
 */



/**
 * Internal dependencies
 */



var SectionControls = /*#__PURE__*/function (_Component) {
  _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_3___default()(SectionControls, _Component);

  var _super = _createSuper(SectionControls);

  function SectionControls(props) {
    var _this;

    _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0___default()(this, SectionControls);

    _this = _super.call(this, props);
    _this.onMoveUp = _this.onMoveUp.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_2___default()(_this));
    _this.onMoveDown = _this.onMoveDown.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_2___default()(_this));
    return _this;
  }

  _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1___default()(SectionControls, [{
    key: "onMoveUp",
    value: function onMoveUp() {
      var _this$props = this.props,
          onMove = _this$props.onMove,
          onToggle = _this$props.onToggle;
      onMove(-1); // Close the dropdown

      onToggle();
    }
  }, {
    key: "onMoveDown",
    value: function onMoveDown() {
      var _this$props2 = this.props,
          onMove = _this$props2.onMove,
          onToggle = _this$props2.onToggle;
      onMove(1); // Close the dropdown

      onToggle();
    }
  }, {
    key: "render",
    value: function render() {
      var _this$props3 = this.props,
          onRemove = _this$props3.onRemove,
          isFirst = _this$props3.isFirst,
          isLast = _this$props3.isLast,
          onTitleBlur = _this$props3.onTitleBlur,
          onTitleChange = _this$props3.onTitleChange,
          titleInput = _this$props3.titleInput;
      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["Fragment"], null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("div", {
        className: "woocommerce-ellipsis-menu__item"
      }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_8__["TextControl"], {
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__["__"])('Section Title', 'woocommerce'),
        onBlur: onTitleBlur,
        onChange: onTitleChange,
        required: true,
        value: titleInput
      })), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("div", {
        className: "woocommerce-dashboard-section-controls"
      }, !isFirst && Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_9__["MenuItem"], {
        isClickable: true,
        onInvoke: this.onMoveUp
      }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_8__["Icon"], {
        icon: 'arrow-up-alt2',
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__["__"])('Move up')
      }), Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__["__"])('Move up', 'woocommerce')), !isLast && Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_9__["MenuItem"], {
        isClickable: true,
        onInvoke: this.onMoveDown
      }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_8__["Icon"], {
        icon: 'arrow-down-alt2',
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__["__"])('Move Down')
      }), Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__["__"])('Move Down', 'woocommerce')), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_9__["MenuItem"], {
        isClickable: true,
        onInvoke: onRemove
      }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_8__["Icon"], {
        icon: 'trash',
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__["__"])('Remove block')
      }), Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__["__"])('Remove section', 'woocommerce'))));
    }
  }]);

  return SectionControls;
}(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["Component"]);

/* harmony default export */ __webpack_exports__["default"] = (SectionControls);

/***/ }),

/***/ "./client/dashboard/section.js":
/*!*************************************!*\
  !*** ./client/dashboard/section.js ***!
  \*************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return Section; });
/* harmony import */ var _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/extends */ "./node_modules/@babel/runtime/helpers/extends.js");
/* harmony import */ var _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _babel_runtime_helpers_objectWithoutProperties__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/objectWithoutProperties */ "./node_modules/@babel/runtime/helpers/objectWithoutProperties.js");
/* harmony import */ var _babel_runtime_helpers_objectWithoutProperties__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_objectWithoutProperties__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @babel/runtime/helpers/classCallCheck */ "./node_modules/@babel/runtime/helpers/classCallCheck.js");
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @babel/runtime/helpers/createClass */ "./node_modules/@babel/runtime/helpers/createClass.js");
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @babel/runtime/helpers/assertThisInitialized */ "./node_modules/@babel/runtime/helpers/assertThisInitialized.js");
/* harmony import */ var _babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @babel/runtime/helpers/inherits */ "./node_modules/@babel/runtime/helpers/inherits.js");
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @babel/runtime/helpers/possibleConstructorReturn */ "./node_modules/@babel/runtime/helpers/possibleConstructorReturn.js");
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @babel/runtime/helpers/getPrototypeOf */ "./node_modules/@babel/runtime/helpers/getPrototypeOf.js");
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_8__);
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! lodash */ "lodash");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_9__);
/* harmony import */ var _section_controls__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! ./section-controls */ "./client/dashboard/section-controls.js");










function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_7___default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_7___default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_6___default()(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/**
 * External dependencies
 */


/**
 * Internal dependencies
 */



var Section = /*#__PURE__*/function (_Component) {
  _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_5___default()(Section, _Component);

  var _super = _createSuper(Section);

  function Section(props) {
    var _this;

    _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_2___default()(this, Section);

    _this = _super.call(this, props);
    var title = props.title;
    _this.state = {
      titleInput: title
    };
    _this.onToggleHiddenBlock = _this.onToggleHiddenBlock.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_4___default()(_this));
    _this.onTitleChange = _this.onTitleChange.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_4___default()(_this));
    _this.onTitleBlur = _this.onTitleBlur.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_4___default()(_this));
    return _this;
  }

  _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_3___default()(Section, [{
    key: "onTitleChange",
    value: function onTitleChange(updatedTitle) {
      this.setState({
        titleInput: updatedTitle
      });
    }
  }, {
    key: "onTitleBlur",
    value: function onTitleBlur() {
      var _this$props = this.props,
          onTitleUpdate = _this$props.onTitleUpdate,
          title = _this$props.title;
      var titleInput = this.state.titleInput;

      if (titleInput === '') {
        this.setState({
          titleInput: title
        });
      } else if (onTitleUpdate) {
        onTitleUpdate(titleInput);
      }
    }
  }, {
    key: "onToggleHiddenBlock",
    value: function onToggleHiddenBlock(key) {
      var _this2 = this;

      return function () {
        var hiddenBlocks = Object(lodash__WEBPACK_IMPORTED_MODULE_9__["xor"])(_this2.props.hiddenBlocks, [key]);

        _this2.props.onChangeHiddenBlocks(hiddenBlocks);
      };
    }
  }, {
    key: "render",
    value: function render() {
      var _this$props2 = this.props,
          SectionComponent = _this$props2.component,
          props = _babel_runtime_helpers_objectWithoutProperties__WEBPACK_IMPORTED_MODULE_1___default()(_this$props2, ["component"]);

      var titleInput = this.state.titleInput;
      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_8__["createElement"])("div", {
        className: "woocommerce-dashboard-section"
      }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_8__["createElement"])(SectionComponent, _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0___default()({
        onTitleChange: this.onTitleChange,
        onTitleBlur: this.onTitleBlur,
        onToggleHiddenBlock: this.onToggleHiddenBlock,
        titleInput: titleInput,
        controls: _section_controls__WEBPACK_IMPORTED_MODULE_10__["default"]
      }, props)));
    }
  }]);

  return Section;
}(_wordpress_element__WEBPACK_IMPORTED_MODULE_8__["Component"]);



/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/arrayWithHoles.js":
/*!***************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/arrayWithHoles.js ***!
  \***************************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

function _arrayWithHoles(arr) {
  if (Array.isArray(arr)) return arr;
}

module.exports = _arrayWithHoles;

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/iterableToArrayLimit.js":
/*!*********************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/iterableToArrayLimit.js ***!
  \*********************************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

function _iterableToArrayLimit(arr, i) {
  if (typeof Symbol === "undefined" || !(Symbol.iterator in Object(arr))) return;
  var _arr = [];
  var _n = true;
  var _d = false;
  var _e = undefined;

  try {
    for (var _i = arr[Symbol.iterator](), _s; !(_n = (_s = _i.next()).done); _n = true) {
      _arr.push(_s.value);

      if (i && _arr.length === i) break;
    }
  } catch (err) {
    _d = true;
    _e = err;
  } finally {
    try {
      if (!_n && _i["return"] != null) _i["return"]();
    } finally {
      if (_d) throw _e;
    }
  }

  return _arr;
}

module.exports = _iterableToArrayLimit;

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/nonIterableRest.js":
/*!****************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/nonIterableRest.js ***!
  \****************************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

function _nonIterableRest() {
  throw new TypeError("Invalid attempt to destructure non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method.");
}

module.exports = _nonIterableRest;

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/slicedToArray.js":
/*!**************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/slicedToArray.js ***!
  \**************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

var arrayWithHoles = __webpack_require__(/*! ./arrayWithHoles */ "./node_modules/@babel/runtime/helpers/arrayWithHoles.js");

var iterableToArrayLimit = __webpack_require__(/*! ./iterableToArrayLimit */ "./node_modules/@babel/runtime/helpers/iterableToArrayLimit.js");

var unsupportedIterableToArray = __webpack_require__(/*! ./unsupportedIterableToArray */ "./node_modules/@babel/runtime/helpers/unsupportedIterableToArray.js");

var nonIterableRest = __webpack_require__(/*! ./nonIterableRest */ "./node_modules/@babel/runtime/helpers/nonIterableRest.js");

function _slicedToArray(arr, i) {
  return arrayWithHoles(arr) || iterableToArrayLimit(arr, i) || unsupportedIterableToArray(arr, i) || nonIterableRest();
}

module.exports = _slicedToArray;

/***/ })

}]);
//# sourceMappingURL=customizable-dashboard.62b0384493d573318ce9.min.js.map
