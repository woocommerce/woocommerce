(window["__wcAdmin_webpackJsonp"] = window["__wcAdmin_webpackJsonp"] || []).push([["homescreen"],{

/***/ "./client/dashboard/store-performance/utils.js":
/*!*****************************************************!*\
  !*** ./client/dashboard/store-performance/utils.js ***!
  \*****************************************************/
/*! exports provided: getIndicatorValues, getIndicatorData */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "getIndicatorValues", function() { return getIndicatorValues; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "getIndicatorData", function() { return getIndicatorData; });
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/defineProperty */ "./node_modules/@babel/runtime/helpers/defineProperty.js");
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var moment__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! moment */ "moment");
/* harmony import */ var moment__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(moment__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! lodash */ "lodash");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _woocommerce_date__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @woocommerce/date */ "@woocommerce/date");
/* harmony import */ var _woocommerce_date__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_date__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _woocommerce_data__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @woocommerce/data */ "@woocommerce/data");
/* harmony import */ var _woocommerce_data__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_data__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _woocommerce_navigation__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @woocommerce/navigation */ "@woocommerce/navigation");
/* harmony import */ var _woocommerce_navigation__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _woocommerce_number__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @woocommerce/number */ "@woocommerce/number");
/* harmony import */ var _woocommerce_number__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_number__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var _woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @woocommerce/wc-admin-settings */ "./client/settings/index.js");
/* harmony import */ var wc_api_reports_utils__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! wc-api/reports/utils */ "./client/wc-api/reports/utils.js");


function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0___default()(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

/**
 * External dependencies
 */


/**
 * WooCommerce dependencies
 */








function getReportUrl(href, persistedQuery, primaryItem) {
  if (!href) {
    return '';
  }

  if (href === '/jetpack') {
    return Object(_woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_7__["getAdminLink"])('admin.php?page=jetpack#/dashboard');
  }

  return Object(_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_5__["getNewPath"])(persistedQuery, href, {
    chart: primaryItem.chart
  });
}

var getIndicatorValues = function getIndicatorValues(_ref) {
  var indicator = _ref.indicator,
      primaryData = _ref.primaryData,
      secondaryData = _ref.secondaryData,
      currency = _ref.currency,
      formatAmount = _ref.formatAmount,
      persistedQuery = _ref.persistedQuery;
  var primaryItem = Object(lodash__WEBPACK_IMPORTED_MODULE_2__["find"])(primaryData.data, function (data) {
    return data.stat === indicator.stat;
  });
  var secondaryItem = Object(lodash__WEBPACK_IMPORTED_MODULE_2__["find"])(secondaryData.data, function (data) {
    return data.stat === indicator.stat;
  });

  if (!primaryItem || !secondaryItem) {
    return {};
  }

  var href = primaryItem._links && primaryItem._links.report[0] && primaryItem._links.report[0].href || '';
  var reportUrl = getReportUrl(href, persistedQuery, primaryItem);
  var reportUrlType = href === '/jetpack' ? 'wp-admin' : 'wc-admin';
  var isCurrency = primaryItem.format === 'currency';
  var delta = Object(_woocommerce_number__WEBPACK_IMPORTED_MODULE_6__["calculateDelta"])(primaryItem.value, secondaryItem.value);
  var primaryValue = isCurrency ? formatAmount(primaryItem.value) : Object(_woocommerce_number__WEBPACK_IMPORTED_MODULE_6__["formatValue"])(currency, primaryItem.format, primaryItem.value);
  var secondaryValue = isCurrency ? formatAmount(secondaryItem.value) : Object(_woocommerce_number__WEBPACK_IMPORTED_MODULE_6__["formatValue"])(currency, secondaryItem.format, secondaryItem.value);
  return {
    primaryValue: primaryValue,
    secondaryValue: secondaryValue,
    delta: delta,
    reportUrl: reportUrl,
    reportUrlType: reportUrlType
  };
};
var getIndicatorData = function getIndicatorData(select, indicators, query, filters) {
  var _select = select('wc-api'),
      getReportItems = _select.getReportItems,
      getReportItemsError = _select.getReportItemsError,
      isReportItemsRequesting = _select.isReportItemsRequesting;

  var _select$getSetting = select(_woocommerce_data__WEBPACK_IMPORTED_MODULE_4__["SETTINGS_STORE_NAME"]).getSetting('wc_admin', 'wcAdminSettings'),
      defaultDateRange = _select$getSetting.woocommerce_default_date_range;

  var datesFromQuery = Object(_woocommerce_date__WEBPACK_IMPORTED_MODULE_3__["getCurrentDates"])(query, defaultDateRange);
  var endPrimary = datesFromQuery.primary.before;
  var endSecondary = datesFromQuery.secondary.before;
  var statKeys = indicators.map(function (indicator) {
    return indicator.stat;
  }).join(',');
  var filterQuery = Object(wc_api_reports_utils__WEBPACK_IMPORTED_MODULE_8__["getFilterQuery"])({
    filters: filters,
    query: query
  });

  var primaryQuery = _objectSpread(_objectSpread({}, filterQuery), {}, {
    after: Object(_woocommerce_date__WEBPACK_IMPORTED_MODULE_3__["appendTimestamp"])(datesFromQuery.primary.after, 'start'),
    before: Object(_woocommerce_date__WEBPACK_IMPORTED_MODULE_3__["appendTimestamp"])(endPrimary, endPrimary.isSame(moment__WEBPACK_IMPORTED_MODULE_1___default()(), 'day') ? 'now' : 'end'),
    stats: statKeys
  });

  var secondaryQuery = _objectSpread(_objectSpread({}, filterQuery), {}, {
    after: Object(_woocommerce_date__WEBPACK_IMPORTED_MODULE_3__["appendTimestamp"])(datesFromQuery.secondary.after, 'start'),
    before: Object(_woocommerce_date__WEBPACK_IMPORTED_MODULE_3__["appendTimestamp"])(endSecondary, endSecondary.isSame(moment__WEBPACK_IMPORTED_MODULE_1___default()(), 'day') ? 'now' : 'end'),
    stats: statKeys
  });

  var primaryData = getReportItems('performance-indicators', primaryQuery);
  var primaryError = getReportItemsError('performance-indicators', primaryQuery) || null;
  var primaryRequesting = isReportItemsRequesting('performance-indicators', primaryQuery);
  var secondaryData = getReportItems('performance-indicators', secondaryQuery);
  var secondaryError = getReportItemsError('performance-indicators', secondaryQuery) || null;
  var secondaryRequesting = isReportItemsRequesting('performance-indicators', secondaryQuery);
  return {
    primaryData: primaryData,
    primaryError: primaryError,
    primaryRequesting: primaryRequesting,
    secondaryData: secondaryData,
    secondaryError: secondaryError,
    secondaryRequesting: secondaryRequesting,
    defaultDateRange: defaultDateRange
  };
};

/***/ }),

/***/ "./client/homescreen/index.js":
/*!************************************!*\
  !*** ./client/homescreen/index.js ***!
  \************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/compose */ "./node_modules/@wordpress/compose/build-module/index.js");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! lodash */ "lodash");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @woocommerce/wc-admin-settings */ "./client/settings/index.js");
/* harmony import */ var _woocommerce_data__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @woocommerce/data */ "@woocommerce/data");
/* harmony import */ var _woocommerce_data__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_data__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _woocommerce_navigation__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @woocommerce/navigation */ "@woocommerce/navigation");
/* harmony import */ var _woocommerce_navigation__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var dashboard_utils__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! dashboard/utils */ "./client/dashboard/utils.js");
/* harmony import */ var _layout__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ./layout */ "./client/homescreen/layout.js");


/**
 * External dependencies
 */



/**
 * WooCommerce dependencies
 */




/**
 * Internal dependencies
 */




var Homescreen = function Homescreen(_ref) {
  var profileItems = _ref.profileItems,
      query = _ref.query;

  var _ref2 = profileItems || {},
      profilerCompleted = _ref2.completed,
      profilerSkipped = _ref2.skipped;

  if (Object(dashboard_utils__WEBPACK_IMPORTED_MODULE_7__["isOnboardingEnabled"])() && !profilerCompleted && !profilerSkipped) {
    Object(_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_6__["getHistory"])().push(Object(_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_6__["getNewPath"])({}, "/profiler", {}));
  }

  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_layout__WEBPACK_IMPORTED_MODULE_8__["default"], {
    query: query
  });
};

/* harmony default export */ __webpack_exports__["default"] = (Object(_wordpress_compose__WEBPACK_IMPORTED_MODULE_1__["compose"])(Object(_woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_4__["getSetting"])('onboarding', {}).profile ? Object(_woocommerce_data__WEBPACK_IMPORTED_MODULE_5__["withOnboardingHydration"])(Object(_woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_4__["getSetting"])('onboarding', {}).profile) : lodash__WEBPACK_IMPORTED_MODULE_3__["identity"], Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_2__["withSelect"])(function (select) {
  if (!Object(dashboard_utils__WEBPACK_IMPORTED_MODULE_7__["isOnboardingEnabled"])()) {
    return;
  }

  var _select = select(_woocommerce_data__WEBPACK_IMPORTED_MODULE_5__["ONBOARDING_STORE_NAME"]),
      getProfileItems = _select.getProfileItems;

  var profileItems = getProfileItems();
  return {
    profileItems: profileItems
  };
}))(Homescreen));

/***/ }),

/***/ "./client/homescreen/layout.js":
/*!*************************************!*\
  !*** ./client/homescreen/layout.js ***!
  \*************************************/
/*! exports provided: Layout, default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "Layout", function() { return Layout; });
/* harmony import */ var _babel_runtime_helpers_slicedToArray__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/slicedToArray */ "./node_modules/@babel/runtime/helpers/slicedToArray.js");
/* harmony import */ var _babel_runtime_helpers_slicedToArray__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_slicedToArray__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/compose */ "./node_modules/@wordpress/compose/build-module/index.js");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! classnames */ "./node_modules/classnames/index.js");
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(classnames__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! prop-types */ "./node_modules/prop-types/index.js");
/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(prop_types__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _woocommerce_data__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @woocommerce/data */ "@woocommerce/data");
/* harmony import */ var _woocommerce_data__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_data__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var _quick_links__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ../quick-links */ "./client/quick-links/index.js");
/* harmony import */ var _stats_overview__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ./stats-overview */ "./client/homescreen/stats-overview/index.js");
/* harmony import */ var _style_scss__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ./style.scss */ "./client/homescreen/style.scss");
/* harmony import */ var _style_scss__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(_style_scss__WEBPACK_IMPORTED_MODULE_9__);
/* harmony import */ var _dashboard_style_scss__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! ../dashboard/style.scss */ "./client/dashboard/style.scss");
/* harmony import */ var _dashboard_style_scss__WEBPACK_IMPORTED_MODULE_10___default = /*#__PURE__*/__webpack_require__.n(_dashboard_style_scss__WEBPACK_IMPORTED_MODULE_10__);
/* harmony import */ var dashboard_utils__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! dashboard/utils */ "./client/dashboard/utils.js");
/* harmony import */ var _task_list_placeholder__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! ../task-list/placeholder */ "./client/task-list/placeholder.js");
/* harmony import */ var _header_activity_panel_panels_inbox__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! ../header/activity-panel/panels/inbox */ "./client/header/activity-panel/panels/inbox/index.js");
/* harmony import */ var wc_api_with_select__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! wc-api/with-select */ "./client/wc-api/with-select.js");
/* harmony import */ var _welcome_modal__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(/*! ./welcome-modal */ "./client/homescreen/welcome-modal/index.js");



/**
 * External dependencies
 */





/**
 * WooCommerce dependencies
 */


/**
 * Internal dependencies
 */










var TaskList = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["lazy"])(function () {
  return Promise.all(/*! import() | task-list */[__webpack_require__.e("vendors~activity-panels-inbox~homescreen~leaderboards~store-alerts~task-list"), __webpack_require__.e("activity-panels-help~task-list"), __webpack_require__.e("homescreen~task-list"), __webpack_require__.e("task-list")]).then(__webpack_require__.bind(null, /*! ../task-list */ "./client/task-list/index.js"));
});
var Layout = function Layout(_ref) {
  var isUndoRequesting = _ref.isUndoRequesting,
      query = _ref.query,
      requestingTaskList = _ref.requestingTaskList,
      taskListComplete = _ref.taskListComplete,
      taskListHidden = _ref.taskListHidden,
      shouldShowWelcomeModal = _ref.shouldShowWelcomeModal,
      updateOptions = _ref.updateOptions;

  var _useState = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["useState"])(true),
      _useState2 = _babel_runtime_helpers_slicedToArray__WEBPACK_IMPORTED_MODULE_0___default()(_useState, 2),
      showInbox = _useState2[0],
      setShowInbox = _useState2[1];

  var _useState3 = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["useState"])(false),
      _useState4 = _babel_runtime_helpers_slicedToArray__WEBPACK_IMPORTED_MODULE_0___default()(_useState3, 2),
      isContentSticky = _useState4[0],
      setIsContentSticky = _useState4[1];

  var content = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["useRef"])(null);

  var maybeStickContent = function maybeStickContent() {
    if (!content.current) {
      return;
    }

    var _content$current$getB = content.current.getBoundingClientRect(),
        bottom = _content$current$getB.bottom;

    var shouldBeSticky = showInbox && bottom < window.innerHeight;
    setIsContentSticky(shouldBeSticky);
  };

  Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["useEffect"])(function () {
    maybeStickContent();
    window.addEventListener('resize', maybeStickContent);
    return function () {
      window.removeEventListener('resize', maybeStickContent);
    };
  }, []);
  var isTaskListEnabled = taskListHidden === false && !taskListComplete;
  var isDashboardShown = !isTaskListEnabled || !query.task;

  var isInboxPanelEmpty = function isInboxPanelEmpty(isEmpty) {
    setShowInbox(!isEmpty);
  };

  if (isUndoRequesting && !showInbox) {
    setShowInbox(true);
  }

  var renderColumns = function renderColumns() {
    return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["Fragment"], null, showInbox && Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])("div", {
      className: "woocommerce-homescreen-column is-inbox"
    }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])(_header_activity_panel_panels_inbox__WEBPACK_IMPORTED_MODULE_13__["default"], {
      isPanelEmpty: isInboxPanelEmpty
    })), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])("div", {
      className: "woocommerce-homescreen-column",
      ref: content,
      style: {
        position: isContentSticky ? 'sticky' : 'static'
      }
    }, isTaskListEnabled && renderTaskList(), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])(_stats_overview__WEBPACK_IMPORTED_MODULE_8__["default"], null), !isTaskListEnabled && Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])(_quick_links__WEBPACK_IMPORTED_MODULE_7__["default"], null)));
  };

  var renderTaskList = function renderTaskList() {
    if (requestingTaskList) {
      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])(_task_list_placeholder__WEBPACK_IMPORTED_MODULE_12__["default"], null);
    }

    return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["Suspense"], {
      fallback: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])(_task_list_placeholder__WEBPACK_IMPORTED_MODULE_12__["default"], null)
    }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])(TaskList, {
      query: query
    }));
  };

  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])("div", {
    className: classnames__WEBPACK_IMPORTED_MODULE_4___default()('woocommerce-homescreen', {
      hasInbox: showInbox
    })
  }, isDashboardShown ? renderColumns() : isTaskListEnabled && renderTaskList(), shouldShowWelcomeModal && Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])(_welcome_modal__WEBPACK_IMPORTED_MODULE_15__["WelcomeModal"], {
    onClose: function onClose() {
      updateOptions({
        woocommerce_task_list_welcome_modal_dismissed: true
      });
    }
  }));
};
Layout.propTypes = {
  /**
   * If the task list option is being fetched.
   */
  requestingTaskList: prop_types__WEBPACK_IMPORTED_MODULE_5___default.a.bool.isRequired,

  /**
   * If the task list has been completed.
   */
  taskListComplete: prop_types__WEBPACK_IMPORTED_MODULE_5___default.a.bool,

  /**
   * If the task list is hidden.
   */
  taskListHidden: prop_types__WEBPACK_IMPORTED_MODULE_5___default.a.bool,

  /**
   * Page query, used to determine the current task if any.
   */
  query: prop_types__WEBPACK_IMPORTED_MODULE_5___default.a.object.isRequired,

  /**
   * If the welcome modal should display
   */
  shouldShowWelcomeModal: prop_types__WEBPACK_IMPORTED_MODULE_5___default.a.bool,

  /**
   * Dispatch an action to update an option
   */
  updateOptions: prop_types__WEBPACK_IMPORTED_MODULE_5___default.a.func.isRequired
};
/* harmony default export */ __webpack_exports__["default"] = (Object(_wordpress_compose__WEBPACK_IMPORTED_MODULE_2__["compose"])(Object(wc_api_with_select__WEBPACK_IMPORTED_MODULE_14__["default"])(function (select) {
  var _select = select('wc-api'),
      getUndoDismissRequesting = _select.getUndoDismissRequesting;

  var _getUndoDismissReques = getUndoDismissRequesting(),
      isUndoRequesting = _getUndoDismissReques.isUndoRequesting;

  var _select2 = select(_woocommerce_data__WEBPACK_IMPORTED_MODULE_6__["OPTIONS_STORE_NAME"]),
      getOption = _select2.getOption,
      isResolving = _select2.isResolving;

  var welcomeModalDismissed = getOption('woocommerce_task_list_welcome_modal_dismissed') === '1';
  var welcomeModalDismissedIsResolving = isResolving('getOption', ['woocommerce_task_list_welcome_modal_dismissed']);
  var shouldShowWelcomeModal = !welcomeModalDismissedIsResolving && !welcomeModalDismissed;

  if (Object(dashboard_utils__WEBPACK_IMPORTED_MODULE_11__["isOnboardingEnabled"])()) {
    return {
      isUndoRequesting: isUndoRequesting,
      shouldShowWelcomeModal: shouldShowWelcomeModal,
      taskListComplete: getOption('woocommerce_task_list_complete') === 'yes',
      taskListHidden: getOption('woocommerce_task_list_hidden') === 'yes',
      requestingTaskList: isResolving('getOption', ['woocommerce_task_list_complete']) || isResolving('getOption', ['woocommerce_task_list_hidden'])
    };
  }

  return {
    shouldShowWelcomeModal: shouldShowWelcomeModal,
    requestingTaskList: false
  };
}), Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_3__["withDispatch"])(function (dispatch) {
  return {
    updateOptions: dispatch(_woocommerce_data__WEBPACK_IMPORTED_MODULE_6__["OPTIONS_STORE_NAME"]).updateOptions
  };
}))(Layout));

/***/ }),

/***/ "./client/homescreen/stats-overview/defaults.js":
/*!******************************************************!*\
  !*** ./client/homescreen/stats-overview/defaults.js ***!
  \******************************************************/
/*! exports provided: DEFAULT_STATS, DEFAULT_HIDDEN_STATS */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "DEFAULT_STATS", function() { return DEFAULT_STATS; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "DEFAULT_HIDDEN_STATS", function() { return DEFAULT_HIDDEN_STATS; });
/* harmony import */ var _wordpress_hooks__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/hooks */ "@wordpress/hooks");
/* harmony import */ var _wordpress_hooks__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_hooks__WEBPACK_IMPORTED_MODULE_0__);
/**
 * External dependencies
 */

var DEFAULT_STATS = Object(_wordpress_hooks__WEBPACK_IMPORTED_MODULE_0__["applyFilters"])('woocommerce_admin_homepage_default_stats', ['revenue/total_sales', 'revenue/net_revenue', 'orders/orders_count', 'products/items_sold', 'jetpack/stats/visitors', 'jetpack/stats/views']);
var DEFAULT_HIDDEN_STATS = ['revenue/net_revenue', 'products/items_sold'];

/***/ }),

/***/ "./client/homescreen/stats-overview/index.js":
/*!***************************************************!*\
  !*** ./client/homescreen/stats-overview/index.js ***!
  \***************************************************/
/*! exports provided: StatsOverview, default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "StatsOverview", function() { return StatsOverview; });
/* harmony import */ var _babel_runtime_helpers_objectWithoutProperties__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/objectWithoutProperties */ "./node_modules/@babel/runtime/helpers/objectWithoutProperties.js");
/* harmony import */ var _babel_runtime_helpers_objectWithoutProperties__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_objectWithoutProperties__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/components */ "./node_modules/@wordpress/components/build-module/index.js");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! lodash */ "lodash");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @woocommerce/components */ "@woocommerce/components");
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_components__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _woocommerce_data__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @woocommerce/data */ "@woocommerce/data");
/* harmony import */ var _woocommerce_data__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_data__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var _woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @woocommerce/wc-admin-settings */ "./client/settings/index.js");
/* harmony import */ var _woocommerce_navigation__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! @woocommerce/navigation */ "@woocommerce/navigation");
/* harmony import */ var _woocommerce_navigation__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_8__);
/* harmony import */ var _style_scss__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ./style.scss */ "./client/homescreen/stats-overview/style.scss");
/* harmony import */ var _style_scss__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(_style_scss__WEBPACK_IMPORTED_MODULE_9__);
/* harmony import */ var _defaults__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! ./defaults */ "./client/homescreen/stats-overview/defaults.js");
/* harmony import */ var _stats_list__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! ./stats-list */ "./client/homescreen/stats-overview/stats-list.js");
/* harmony import */ var lib_tracks__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! lib/tracks */ "./client/lib/tracks.js");
/* harmony import */ var _install_jetpack_cta__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! ./install-jetpack-cta */ "./client/homescreen/stats-overview/install-jetpack-cta.js");



/**
 * External dependencies
 */




/**
 * WooCommerce dependencies
 */





/**
 * Internal dependencies
 */







var _getSetting = Object(_woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_7__["getSetting"])('dataEndpoints', {
  performanceIndicators: []
}),
    performanceIndicators = _getSetting.performanceIndicators;

var stats = performanceIndicators.filter(function (indicator) {
  return _defaults__WEBPACK_IMPORTED_MODULE_10__["DEFAULT_STATS"].includes(indicator.stat);
});
var StatsOverview = function StatsOverview() {
  var _useUserPreferences = Object(_woocommerce_data__WEBPACK_IMPORTED_MODULE_6__["useUserPreferences"])(),
      updateUserPreferences = _useUserPreferences.updateUserPreferences,
      userPrefs = _babel_runtime_helpers_objectWithoutProperties__WEBPACK_IMPORTED_MODULE_0___default()(_useUserPreferences, ["updateUserPreferences"]);

  var hiddenStats = Object(lodash__WEBPACK_IMPORTED_MODULE_4__["get"])(userPrefs, ['homepage_stats', 'hiddenStats'], _defaults__WEBPACK_IMPORTED_MODULE_10__["DEFAULT_HIDDEN_STATS"]);

  var toggleStat = function toggleStat(stat) {
    var nextHiddenStats = Object(lodash__WEBPACK_IMPORTED_MODULE_4__["xor"])(hiddenStats, [stat]);
    updateUserPreferences({
      homepage_stats: {
        hiddenStats: nextHiddenStats
      }
    });
    Object(lib_tracks__WEBPACK_IMPORTED_MODULE_12__["recordEvent"])('statsoverview_indicators_toggle', {
      indicator_name: stat,
      status: nextHiddenStats.includes(stat) ? 'off' : 'on'
    });
  };

  var activeStats = stats.filter(function (item) {
    return !hiddenStats.includes(item.stat);
  });
  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__["Card"], {
    size: "large",
    className: "woocommerce-stats-overview woocommerce-homescreen-card"
  }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__["CardHeader"], {
    size: "medium"
  }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__["__experimentalText"], {
    variant: "title.small"
  }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__["__"])('Stats overview', 'woocommerce')), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_5__["EllipsisMenu"], {
    label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__["__"])('Choose which values to display', 'woocommerce'),
    renderContent: function renderContent() {
      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["Fragment"], null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_5__["MenuTitle"], null, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__["__"])('Display stats:', 'woocommerce')), stats.map(function (item) {
        var checked = !hiddenStats.includes(item.stat);
        return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_5__["MenuItem"], {
          checked: checked,
          isCheckbox: true,
          isClickable: true,
          key: item.stat,
          onInvoke: function onInvoke() {
            return toggleStat(item.stat);
          }
        }, item.label);
      }));
    }
  })), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__["CardBody"], null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__["TabPanel"], {
    className: "woocommerce-stats-overview__tabs",
    onSelect: function onSelect(period) {
      Object(lib_tracks__WEBPACK_IMPORTED_MODULE_12__["recordEvent"])('statsoverview_date_picker_update', {
        period: period
      });
    },
    tabs: [{
      title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__["__"])('Today', 'woocommerce'),
      name: 'today'
    }, {
      title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__["__"])('Week to date', 'woocommerce'),
      name: 'week'
    }, {
      title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__["__"])('Month to date', 'woocommerce'),
      name: 'month'
    }]
  }, function (tab) {
    return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["Fragment"], null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])(_install_jetpack_cta__WEBPACK_IMPORTED_MODULE_13__["default"], null), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])(_stats_list__WEBPACK_IMPORTED_MODULE_11__["default"], {
      query: {
        period: tab.name,
        compare: 'previous_period'
      },
      stats: activeStats
    }));
  })), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__["CardFooter"], null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_5__["Link"], {
    className: "woocommerce-stats-overview__more-btn",
    href: Object(_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_8__["getNewPath"])({}, '/analytics/overview'),
    type: "wc-admin",
    onClick: function onClick() {
      Object(lib_tracks__WEBPACK_IMPORTED_MODULE_12__["recordEvent"])('statsoverview_indicators_click', {
        key: 'view_detailed_stats'
      });
    }
  }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__["__"])('View detailed stats'))));
};
/* harmony default export */ __webpack_exports__["default"] = (StatsOverview);

/***/ }),

/***/ "./client/homescreen/stats-overview/install-jetpack-cta.js":
/*!*****************************************************************!*\
  !*** ./client/homescreen/stats-overview/install-jetpack-cta.js ***!
  \*****************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/regenerator */ "./node_modules/@babel/runtime/regenerator/index.js");
/* harmony import */ var _babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _babel_runtime_helpers_asyncToGenerator__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/asyncToGenerator */ "./node_modules/@babel/runtime/helpers/asyncToGenerator.js");
/* harmony import */ var _babel_runtime_helpers_asyncToGenerator__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_asyncToGenerator__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _babel_runtime_helpers_slicedToArray__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @babel/runtime/helpers/slicedToArray */ "./node_modules/@babel/runtime/helpers/slicedToArray.js");
/* harmony import */ var _babel_runtime_helpers_slicedToArray__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_slicedToArray__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _babel_runtime_helpers_objectWithoutProperties__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @babel/runtime/helpers/objectWithoutProperties */ "./node_modules/@babel/runtime/helpers/objectWithoutProperties.js");
/* harmony import */ var _babel_runtime_helpers_objectWithoutProperties__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_objectWithoutProperties__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @wordpress/compose */ "./node_modules/@wordpress/compose/build-module/index.js");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @wordpress/components */ "./node_modules/@wordpress/components/build-module/index.js");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_8__);
/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! prop-types */ "./node_modules/prop-types/index.js");
/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(prop_types__WEBPACK_IMPORTED_MODULE_9__);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_10___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_10__);
/* harmony import */ var _woocommerce_data__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! @woocommerce/data */ "@woocommerce/data");
/* harmony import */ var _woocommerce_data__WEBPACK_IMPORTED_MODULE_11___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_data__WEBPACK_IMPORTED_MODULE_11__);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! @woocommerce/components */ "@woocommerce/components");
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_12___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_components__WEBPACK_IMPORTED_MODULE_12__);
/* harmony import */ var _woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! @woocommerce/wc-admin-settings */ "./client/settings/index.js");
/* harmony import */ var lib_tracks__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! lib/tracks */ "./client/lib/tracks.js");
/* harmony import */ var lib_notices__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(/*! lib/notices */ "./client/lib/notices/index.js");






/**
 * External dependencies
 */






/**
 * WooCommerce dependencies
 */




/**
 * Internal dependencies
 */




function InstallJetpackCta(_ref) {
  var getJetpackConnectUrl = _ref.getJetpackConnectUrl,
      getPluginsError = _ref.getPluginsError,
      isJetpackInstalled = _ref.isJetpackInstalled,
      isJetpackActivated = _ref.isJetpackActivated,
      isJetpackConnected = _ref.isJetpackConnected,
      installAndActivatePlugins = _ref.installAndActivatePlugins,
      isConnecting = _ref.isConnecting,
      isInstalling = _ref.isInstalling;

  var _useUserPreferences = Object(_woocommerce_data__WEBPACK_IMPORTED_MODULE_11__["useUserPreferences"])(),
      updateUserPreferences = _useUserPreferences.updateUserPreferences,
      userPrefs = _babel_runtime_helpers_objectWithoutProperties__WEBPACK_IMPORTED_MODULE_3___default()(_useUserPreferences, ["updateUserPreferences"]);

  var _useState = Object(react__WEBPACK_IMPORTED_MODULE_8__["useState"])((userPrefs.homepage_stats || {}).installJetpackDismissed),
      _useState2 = _babel_runtime_helpers_slicedToArray__WEBPACK_IMPORTED_MODULE_2___default()(_useState, 2),
      isDismissed = _useState2[0],
      setIsDismissed = _useState2[1];

  function install() {
    return _install.apply(this, arguments);
  }

  function _install() {
    _install = _babel_runtime_helpers_asyncToGenerator__WEBPACK_IMPORTED_MODULE_1___default()( /*#__PURE__*/_babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0___default.a.mark(function _callee() {
      return _babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0___default.a.wrap(function _callee$(_context) {
        while (1) {
          switch (_context.prev = _context.next) {
            case 0:
              Object(lib_tracks__WEBPACK_IMPORTED_MODULE_14__["recordEvent"])('statsoverview_install_jetpack');
              installAndActivatePlugins(['jetpack']).then(connect).catch(function (error) {
                Object(lib_notices__WEBPACK_IMPORTED_MODULE_15__["createNoticesFromResponse"])(error);
              });

            case 2:
            case "end":
              return _context.stop();
          }
        }
      }, _callee);
    }));
    return _install.apply(this, arguments);
  }

  function connect() {
    if (isJetpackConnected) {
      return;
    }

    getJetpackConnectUrl({
      redirect_url: Object(_woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_13__["getAdminLink"])('admin.php?page=wc-admin')
    }).then(function (url) {
      var error = getPluginsError('getJetpackConnectUrl');

      if (error) {
        Object(lib_notices__WEBPACK_IMPORTED_MODULE_15__["createNoticesFromResponse"])(error);
        return;
      }

      window.location = url;
    });
  }

  function dismiss() {
    if (isInstalling || isConnecting) {
      return;
    }

    var homepageStats = userPrefs.homepage_stats || {};
    homepageStats.installJetpackDismissed = true;
    updateUserPreferences({
      homepage_stats: homepageStats
    });
    setIsDismissed(true);
    Object(lib_tracks__WEBPACK_IMPORTED_MODULE_14__["recordEvent"])('statsoverview_dismiss_install_jetpack');
  }

  var doNotShow = isDismissed || isJetpackInstalled && isJetpackActivated && isJetpackConnected;

  if (doNotShow) {
    return null;
  }

  function getInstallJetpackText() {
    if (!isJetpackInstalled) {
      return Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_5__["__"])('Get Jetpack', 'woocommerce');
    }

    if (!isJetpackActivated) {
      return Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_5__["__"])('Activate Jetpack', 'woocommerce');
    }

    if (!isJetpackConnected) {
      return Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_5__["__"])('Connect Jetpack', 'woocommerce');
    }

    return '';
  }

  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_4__["createElement"])("article", {
    className: "woocommerce-stats-overview__install-jetpack-promo"
  }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_4__["createElement"])("div", {
    className: "woocommerce-stats-overview__install-jetpack-promo__content"
  }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_4__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_12__["H"], null, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_5__["__"])('Get traffic stats with Jetpack', 'woocommerce')), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_4__["createElement"])("p", null, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_5__["__"])('Keep an eye on your views and visitors metrics with ' + 'Jetpack. Requires Jetpack plugin and a WordPress.com ' + 'account.', 'woocommerce'))), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_4__["createElement"])("footer", null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_4__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_7__["Button"], {
    isSecondary: true,
    onClick: install,
    isBusy: isInstalling
  }, getInstallJetpackText()), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_4__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_7__["Button"], {
    isTertiary: true,
    onClick: dismiss,
    isBusy: isInstalling
  }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_5__["__"])('No thanks', 'woocommerce'))));
}

InstallJetpackCta.propTypes = {
  /**
   * Is the Jetpack plugin connected.
   */
  isJetpackConnected: prop_types__WEBPACK_IMPORTED_MODULE_9___default.a.bool.isRequired
};
/* harmony default export */ __webpack_exports__["default"] = (Object(_wordpress_compose__WEBPACK_IMPORTED_MODULE_6__["compose"])(Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_10__["withSelect"])(function (select) {
  var _select = select(_woocommerce_data__WEBPACK_IMPORTED_MODULE_11__["PLUGINS_STORE_NAME"]),
      isJetpackConnected = _select.isJetpackConnected,
      isPluginsRequesting = _select.isPluginsRequesting,
      getActivePlugins = _select.getActivePlugins,
      getInstalledPlugins = _select.getInstalledPlugins,
      getPluginsError = _select.getPluginsError;

  return {
    getJetpackConnectUrl: Object(_woocommerce_data__WEBPACK_IMPORTED_MODULE_11__["__experimentalResolveSelect"])(_woocommerce_data__WEBPACK_IMPORTED_MODULE_11__["PLUGINS_STORE_NAME"]).getJetpackConnectUrl,
    getPluginsError: getPluginsError,
    isConnecting: isPluginsRequesting('getJetpackConnectUrl'),
    isInstalling: isPluginsRequesting('installPlugins') || isPluginsRequesting('activatePlugins'),
    isJetpackInstalled: getInstalledPlugins().includes('jetpack'),
    isJetpackActivated: getActivePlugins().includes('jetpack'),
    isJetpackConnected: isJetpackConnected()
  };
}), Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_10__["withDispatch"])(function (dispatch) {
  var _dispatch = dispatch(_woocommerce_data__WEBPACK_IMPORTED_MODULE_11__["PLUGINS_STORE_NAME"]),
      installAndActivatePlugins = _dispatch.installAndActivatePlugins;

  return {
    installAndActivatePlugins: installAndActivatePlugins
  };
}))(InstallJetpackCta));

/***/ }),

/***/ "./client/homescreen/stats-overview/stats-list.js":
/*!********************************************************!*\
  !*** ./client/homescreen/stats-overview/stats-list.js ***!
  \********************************************************/
/*! exports provided: StatsList, default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "StatsList", function() { return StatsList; });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! classnames */ "./node_modules/classnames/index.js");
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(classnames__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @woocommerce/components */ "@woocommerce/components");
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_components__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _woocommerce_navigation__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @woocommerce/navigation */ "@woocommerce/navigation");
/* harmony import */ var _woocommerce_navigation__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var wc_api_with_select__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! wc-api/with-select */ "./client/wc-api/with-select.js");
/* harmony import */ var lib_tracks__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! lib/tracks */ "./client/lib/tracks.js");
/* harmony import */ var lib_currency_context__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! lib/currency-context */ "./client/lib/currency-context.js");
/* harmony import */ var dashboard_store_performance_utils__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! dashboard/store-performance/utils */ "./client/dashboard/store-performance/utils.js");


/**
 * External dependencies
 */



/**
 * WooCommerce dependencies
 */



/**
 * Internal dependencies
 */





var StatsList = function StatsList(_ref) {
  var stats = _ref.stats,
      primaryData = _ref.primaryData,
      secondaryData = _ref.secondaryData,
      primaryRequesting = _ref.primaryRequesting,
      secondaryRequesting = _ref.secondaryRequesting,
      primaryError = _ref.primaryError,
      secondaryError = _ref.secondaryError,
      query = _ref.query;

  var _useContext = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["useContext"])(lib_currency_context__WEBPACK_IMPORTED_MODULE_7__["CurrencyContext"]),
      formatAmount = _useContext.formatAmount,
      getCurrencyConfig = _useContext.getCurrencyConfig;

  if (primaryError || secondaryError) {
    return null;
  }

  var persistedQuery = Object(_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_4__["getPersistedQuery"])(query);
  var currency = getCurrencyConfig();
  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("ul", {
    className: classnames__WEBPACK_IMPORTED_MODULE_2___default()('woocommerce-stats-overview__stats', {
      'is-even': stats.length % 2 === 0
    })
  }, stats.map(function (item) {
    if (primaryRequesting || secondaryRequesting) {
      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_3__["SummaryNumberPlaceholder"], {
        className: "is-homescreen",
        key: item.stat
      });
    }

    var _getIndicatorValues = Object(dashboard_store_performance_utils__WEBPACK_IMPORTED_MODULE_8__["getIndicatorValues"])({
      indicator: item,
      primaryData: primaryData,
      secondaryData: secondaryData,
      currency: currency,
      formatAmount: formatAmount,
      persistedQuery: persistedQuery
    }),
        primaryValue = _getIndicatorValues.primaryValue,
        secondaryValue = _getIndicatorValues.secondaryValue,
        delta = _getIndicatorValues.delta,
        reportUrl = _getIndicatorValues.reportUrl,
        reportUrlType = _getIndicatorValues.reportUrlType;

    return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_3__["SummaryNumber"], {
      isHomescreen: true,
      key: item.stat,
      href: reportUrl,
      hrefType: reportUrlType,
      label: item.label,
      value: primaryValue,
      prevLabel: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Previous Period:', 'woocommerce'),
      prevValue: secondaryValue,
      delta: delta,
      onLinkClickCallback: function onLinkClickCallback() {
        Object(lib_tracks__WEBPACK_IMPORTED_MODULE_6__["recordEvent"])('statsoverview_indicators_click', {
          key: item.stat
        });
      }
    });
  }));
};
/* harmony default export */ __webpack_exports__["default"] = (Object(wc_api_with_select__WEBPACK_IMPORTED_MODULE_5__["default"])(function (select, _ref2) {
  var stats = _ref2.stats,
      query = _ref2.query;

  if (stats.length === 0) {
    return;
  }

  return Object(dashboard_store_performance_utils__WEBPACK_IMPORTED_MODULE_8__["getIndicatorData"])(select, stats, query);
})(StatsList));

/***/ }),

/***/ "./client/homescreen/stats-overview/style.scss":
/*!*****************************************************!*\
  !*** ./client/homescreen/stats-overview/style.scss ***!
  \*****************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),

/***/ "./client/homescreen/style.scss":
/*!**************************************!*\
  !*** ./client/homescreen/style.scss ***!
  \**************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),

/***/ "./client/homescreen/welcome-modal/illustrations/inbox.js":
/*!****************************************************************!*\
  !*** ./client/homescreen/welcome-modal/illustrations/inbox.js ***!
  \****************************************************************/
/*! exports provided: InboxIllustration */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "InboxIllustration", function() { return InboxIllustration; });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);


/**
 * External dependencies
 */

var InboxIllustration = function InboxIllustration() {
  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("svg", {
    xmlns: "http://www.w3.org/2000/svg",
    width: "517",
    height: "160",
    fill: "none"
  }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("defs", null), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("g", {
    clipPath: "url(#clip0)"
  }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("path", {
    className: "fill-theme-color",
    d: "M0 0h517v160H0z"
  }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("path", {
    fill: "#fff",
    fillOpacity: ".65",
    d: "M33.576 185.926c-6.271-.911-14.742-.279-17.182 7.085-1.239 3.736-.178 7.645.98 11.08 4.89 14.682 11.49 28.444 19.643 40.954 3.897 5.965 8.253 11.884 9.592 19.504 1.34 7.619-.56 16.084-2.934 23.945-5.595 18.62-13.762 36.371-24.188 52.572 16.006 9.711 34.165 19.634 52.684 12.57 11.09-4.232 21.041-14.268 32.365-15.961 7.562-1.132 14.735 1.648 21.594 4.467a998.376 998.376 0 0195.343 45.227c13.023 7.042 26.207 14.481 40.901 16.153 14.694 1.672 31.486-3.518 41.947-17.66 1.611-2.179 3.241-4.669 5.483-5.546 2.02-.776 4.069-.045 5.952.688l113.896 44.033c6.241 2.411 12.718 4.853 19.534 3.832 6.606-.985 12.833-5.095 18.858-9.148 13.771-9.237 29.242-21.105 32.239-39.005 2.407-14.347-4.339-27.253-11.974-37.283-7.636-10.03-16.705-19.204-20.353-32.315-5.549-19.955 2.798-42.949 9.281-64.164a405.4 405.4 0 0013.244-58.574c2.588-17.377 4.004-35.179.91-51.659-3.095-16.481-11.265-31.624-24.089-38.27-16.746-8.681-38.828-2.057-54.255-13.347-13.04-9.513-17.58-29.035-25.856-44.316-14.698-27.146-41.453-40.923-67.958-50.405-28.1-10.066-58.213-16.679-88.607-10-6.962 1.527-14.047 3.833-20.152 8.649-9.36 7.388-15.196 19.616-22.986 29.33C156.104 57.468 100.341 49.156 68.22 87.48c-11.398 13.594-17.581 31.878-18.797 49.831-1.31 19.318 8.69 33.652 8.706 50.888-7.135 2.277-17.21-1.211-24.553-2.273z"
  }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("path", {
    fill: "#F6F7F7",
    d: "M113 33h267v185H113z"
  }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("path", {
    className: "fill-theme-color",
    d: "M248.466 73.79h-114.69V47.88h114.69V73.79zm-114.015-.673h113.341V48.554H134.451v24.563z"
  }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("path", {
    fill: "#CCC",
    d: "M155.702 56.63h-12.818v12.786h12.818V56.63z"
  }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("path", {
    className: "fill-theme-color",
    d: "M154.016 67.733h-13.493V54.274h13.493v13.46zm-12.819-.673h12.144V54.947h-12.144V67.06z"
  }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("path", {
    className: "fill-theme-color",
    d: "M225.267 56.966h-50v.673h50v-.673z",
    opacity: ".7"
  }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("path", {
    className: "fill-theme-color",
    d: "M235.311 61.677h-60.044v.673h60.044v-.673z",
    opacity: ".5"
  }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("path", {
    className: "fill-theme-color",
    d: "M225.267 66.387h-50v.673h50v-.673z",
    opacity: ".2"
  }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("path", {
    className: "fill-theme-color",
    d: "M248.466 147.142h-114.69v-25.909h114.69v25.909zm-114.015-.673h113.341v-24.563H134.451v24.563z"
  }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("path", {
    fill: "#CCC",
    d: "M155.702 129.981h-12.818v12.786h12.818v-12.786z"
  }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("path", {
    className: "fill-theme-color",
    d: "M154.016 141.085h-13.493v-13.459h13.493v13.459zm-12.819-.673h12.144v-12.113h-12.144v12.113z"
  }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("path", {
    className: "fill-theme-color",
    d: "M235.311 130.318h-60.044v.673h60.044v-.673z",
    opacity: ".7"
  }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("path", {
    className: "fill-theme-color",
    d: "M225.267 135.028h-50v.673h50v-.673z",
    opacity: ".5"
  }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("path", {
    className: "fill-theme-color",
    d: "M215.267 139.739h-40v.673h40v-.673z",
    opacity: ".2"
  }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("path", {
    className: "fill-theme-color",
    d: "M289.62 110.465H174.93V84.557h114.69v25.908zm-114.016-.672h113.341V85.23H175.604v24.563z"
  }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("path", {
    fill: "#CCC",
    d: "M267.694 106.092h12.818V93.305h-12.818v12.787z"
  }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("path", {
    className: "fill-theme-color",
    d: "M282.873 104.409H269.38V90.95h13.493v13.459zm-12.818-.673h12.144V91.623h-12.144v12.113z"
  }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("path", {
    className: "fill-theme-color",
    d: "M248.129 93.642h-60.044v.673h60.044v-.673z",
    opacity: ".7"
  }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("path", {
    className: "fill-theme-color",
    d: "M238.085 98.353h-50v.672h50v-.672z",
    opacity: ".5"
  }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("path", {
    className: "fill-theme-color",
    d: "M243.085 103.063h-55v.673h55v-.673z",
    opacity: ".2"
  }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("path", {
    className: "fill-theme-color",
    d: "M266.035 66.154a5.363 5.363 0 005.369-5.356 5.363 5.363 0 00-5.369-5.356c-2.966 0-5.37 2.398-5.37 5.356 0 2.958 2.404 5.356 5.37 5.356zM273.793 140.515c2.966 0 5.37-2.398 5.37-5.356 0-2.958-2.404-5.356-5.37-5.356a5.363 5.363 0 00-5.369 5.356 5.363 5.363 0 005.369 5.356zM153.706 102.83a5.363 5.363 0 005.37-5.356c0-2.959-2.404-5.357-5.37-5.357s-5.37 2.398-5.37 5.357a5.363 5.363 0 005.37 5.356z",
    opacity: ".5"
  }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("path", {
    className: "fill-theme-color",
    d: "M401.276 172h-70.552a8.79 8.79 0 01-6.169-2.517 8.532 8.532 0 01-2.555-6.078V131.56a3.368 3.368 0 011.078-2.471l37.386-34.915A8.113 8.113 0 01366 92c2.06 0 4.041.778 5.536 2.174l35.645 33.289a8.882 8.882 0 012.084 2.944 8.78 8.78 0 01.735 3.515v29.483c0 2.28-.919 4.466-2.555 6.078a8.79 8.79 0 01-6.169 2.517z"
  }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("path", {
    fill: "#F0F0F0",
    d: "M393.267 106h-54.534c-2.614 0-4.733 2.053-4.733 4.585v52.83c0 2.532 2.119 4.585 4.733 4.585h54.534c2.614 0 4.733-2.053 4.733-4.585v-52.83c0-2.532-2.119-4.585-4.733-4.585z"
  }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("path", {
    className: "fill-theme-color",
    d: "M366 150.493l-41.579-20.323a1.667 1.667 0 00-1.631.091 1.695 1.695 0 00-.579.619 1.725 1.725 0 00-.211.826v34.967a5.345 5.345 0 001.543 3.767 5.261 5.261 0 003.725 1.56h77.464a5.261 5.261 0 003.725-1.56 5.345 5.345 0 001.543-3.767v-34.368c0-.352-.088-.699-.257-1.008a2.069 2.069 0 00-1.688-1.071 2.035 2.035 0 00-1.009.205L366 150.493zM390 118h-48v2h48v-2zM390 124h-48v2h48v-2z"
  }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("path", {
    className: "fill-theme-color",
    d: "M384 130h-42v2h42v-2z"
  }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("path", {
    fill: "#fff",
    d: "M335 112a7 7 0 100-14 7 7 0 000 14z"
  }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("path", {
    className: "fill-theme-color",
    d: "M336 98a8.003 8.003 0 00-7.391 4.939 7.992 7.992 0 00-.455 4.622 7.993 7.993 0 006.285 6.285A8 8 0 00344 106a8.022 8.022 0 00-8-8zm-1.642 12.265l-4.1-4.1 1.15-1.15 2.954 2.954 6.234-6.234 1.15 1.15-7.388 7.38z"
  })), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("defs", null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("clipPath", {
    id: "clip0"
  }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("path", {
    fill: "#fff",
    d: "M0 0h517v160H0z"
  }))));
};

/***/ }),

/***/ "./client/homescreen/welcome-modal/illustrations/line-chart.js":
/*!*********************************************************************!*\
  !*** ./client/homescreen/welcome-modal/illustrations/line-chart.js ***!
  \*********************************************************************/
/*! exports provided: LineChartIllustration */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "LineChartIllustration", function() { return LineChartIllustration; });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);


/**
 * External dependencies
 */

var LineChartIllustration = function LineChartIllustration() {
  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("svg", {
    xmlns: "http://www.w3.org/2000/svg",
    width: "517",
    height: "160",
    fill: "none"
  }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("defs", null), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("g", {
    clipPath: "url(#clip0)"
  }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("path", {
    className: "fill-theme-color",
    d: "M0 0h517v160H0z"
  }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("path", {
    fill: "#fff",
    fillOpacity: ".65",
    d: "M125.85 217.924l-1.055-.321c-34.868-10.598-101.138-36.619-95.91-101.998 7.156-89.462 89.192-28.933 194.231-87.715 161.485-90.37 242.851-42.249 253.957 78.717 10.842 118.101-82.942 115.494-138.938 123.306-118.486 16.529-165.516 2.231-212.285-11.989z"
  }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("path", {
    fill: "#F6F7F7",
    d: "M125 33h267v185H125z"
  }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("path", {
    fill: "#CCC",
    d: "M327.367 93.974a6.417 6.417 0 00-6.285 7.671 6.418 6.418 0 005.035 5.044 6.405 6.405 0 006.579-2.73 6.427 6.427 0 00-.797-8.105 6.404 6.404 0 00-4.532-1.88zm0 11.615a5.18 5.18 0 01-3.668-1.522 5.2 5.2 0 01-1.23-5.38 5.196 5.196 0 014.168-3.447 5.18 5.18 0 014.967 2.137 5.201 5.201 0 01-1.546 7.453 5.186 5.186 0 01-2.706.75l.015.009z"
  }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("path", {
    fill: "#CCC",
    d: "M329.332 103.181l.806-.811a.354.354 0 00.078-.391.365.365 0 00-.078-.116l-1.456-1.461 1.456-1.458a.363.363 0 00.105-.254.36.36 0 00-.105-.254l-.806-.81a.354.354 0 00-.254-.106.356.356 0 00-.255.106l-1.456 1.458-1.456-1.458a.35.35 0 00-.253-.105.355.355 0 00-.253.105l-.809.826a.362.362 0 00-.078.39.363.363 0 00.078.117l1.456 1.458-1.456 1.461a.369.369 0 00-.105.254.356.356 0 00.105.254l.809.81a.354.354 0 00.39.078.354.354 0 00.116-.078l1.456-1.461 1.456 1.461a.366.366 0 00.509-.015zM314.559 145.63a6.413 6.413 0 00-2.722-4.13 6.429 6.429 0 00-4.883-.957l-.192.046c-.346.08-.684.191-1.01.33a6.437 6.437 0 00-3.892 5.926 6.433 6.433 0 003.907 5.916l.183.074a6.402 6.402 0 007.999-2.997 6.423 6.423 0 00.735-3.001 6.196 6.196 0 00-.125-1.207zm-1.184 1.978a.028.028 0 010 .018v.058a5.213 5.213 0 01-.913 2.181 5.191 5.191 0 01-4.068 2.146 5.162 5.162 0 01-3.445-1.2 5.2 5.2 0 01.693-8.443 4.936 4.936 0 011.026-.464l.192-.058a5.176 5.176 0 014.527.859 5.201 5.201 0 012.058 4.129 4.906 4.906 0 01-.07.774z"
  }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("path", {
    fill: "#CCC",
    d: "M310.223 149.613l.808-.81a.349.349 0 00.078-.116.348.348 0 000-.275.353.353 0 00-.078-.117l-1.455-1.458 1.455-1.458a.36.36 0 00.079-.393.36.36 0 00-.079-.117l-.808-.807a.362.362 0 00-.391-.078.349.349 0 00-.116.078l-1.455 1.464-1.465-1.464a.366.366 0 00-.254-.106.36.36 0 00-.253.106l-.809.807a.358.358 0 00-.078.393.358.358 0 00.078.117l1.459 1.458-1.459 1.458a.356.356 0 00-.078.392.382.382 0 00.078.116l.809.81a.365.365 0 00.253.106.366.366 0 00.254-.106l1.458-1.458 1.456 1.458a.353.353 0 00.513 0zM295.605 51.781l-.583-.587a.297.297 0 00-.219-.093.31.31 0 00-.22.093l-3.662 3.635-1.547-1.562a.308.308 0 00-.437 0l-.589.584a.313.313 0 00-.093.22.307.307 0 00.093.22l2.35 2.372a.31.31 0 00.339.07.297.297 0 00.1-.07l4.465-4.439a.316.316 0 00.097-.22.313.313 0 00-.094-.223zm0 0l-.583-.587a.297.297 0 00-.219-.093.31.31 0 00-.22.093l-3.662 3.635-1.547-1.562a.308.308 0 00-.437 0l-.589.584a.313.313 0 00-.093.22.307.307 0 00.093.22l2.35 2.372a.31.31 0 00.339.07.297.297 0 00.1-.07l4.465-4.439a.316.316 0 00.097-.22.313.313 0 00-.094-.223zm0 0l-.583-.587a.297.297 0 00-.219-.093.31.31 0 00-.22.093l-3.662 3.635-1.547-1.562a.308.308 0 00-.437 0l-.589.584a.313.313 0 00-.093.22.307.307 0 00.093.22l2.35 2.372a.31.31 0 00.339.07.297.297 0 00.1-.07l4.465-4.439a.316.316 0 00.097-.22.313.313 0 00-.094-.223zm0 0l-.583-.587a.297.297 0 00-.219-.093.31.31 0 00-.22.093l-3.662 3.635-1.547-1.562a.308.308 0 00-.437 0l-.589.584a.313.313 0 00-.093.22.307.307 0 00.093.22l2.35 2.372a.31.31 0 00.339.07.297.297 0 00.1-.07l4.465-4.439a.316.316 0 00.097-.22.313.313 0 00-.094-.223zm-3.628-4.619a6.402 6.402 0 00-5.921 3.963 6.432 6.432 0 001.389 6.996 6.404 6.404 0 009.86-.973 6.428 6.428 0 00-.797-8.106 6.403 6.403 0 00-4.531-1.88zm0 11.616a5.186 5.186 0 01-4.793-3.208 5.2 5.2 0 011.124-5.663 5.186 5.186 0 015.654-1.126 5.204 5.204 0 011.685 8.476 5.17 5.17 0 01-3.67 1.515v.006zm3.628-6.99l-.583-.588a.298.298 0 00-.219-.093.31.31 0 00-.22.093l-3.662 3.635-1.547-1.562a.308.308 0 00-.437 0l-.589.584a.313.313 0 00-.093.22.307.307 0 00.093.22l2.35 2.372c.029.03.063.053.1.07a.31.31 0 00.239 0 .297.297 0 00.1-.07l4.465-4.438a.304.304 0 00.075-.347.31.31 0 00-.072-.103v.006zm0 0l-.583-.588a.298.298 0 00-.219-.093.31.31 0 00-.22.093l-3.662 3.635-1.547-1.562a.308.308 0 00-.437 0l-.589.584a.313.313 0 00-.093.22.307.307 0 00.093.22l2.35 2.372c.029.03.063.053.1.07a.31.31 0 00.239 0 .297.297 0 00.1-.07l4.465-4.438a.304.304 0 00.075-.347.31.31 0 00-.072-.103v.006zm0 0l-.583-.588a.298.298 0 00-.219-.093.31.31 0 00-.22.093l-3.662 3.635-1.547-1.562a.308.308 0 00-.437 0l-.589.584a.313.313 0 00-.093.22.307.307 0 00.093.22l2.35 2.372c.029.03.063.053.1.07a.31.31 0 00.239 0 .297.297 0 00.1-.07l4.465-4.438a.304.304 0 00.075-.347.31.31 0 00-.072-.103v.006zm0 0l-.583-.588a.298.298 0 00-.219-.093.31.31 0 00-.22.093l-3.662 3.635-1.547-1.562a.308.308 0 00-.437 0l-.589.584a.313.313 0 00-.093.22.307.307 0 00.093.22l2.35 2.372c.029.03.063.053.1.07a.31.31 0 00.239 0 .297.297 0 00.1-.07l4.465-4.438a.304.304 0 00.075-.347.31.31 0 00-.072-.103v.006zm0 0l-.583-.588a.298.298 0 00-.219-.093.31.31 0 00-.22.093l-3.662 3.635-1.547-1.562a.308.308 0 00-.437 0l-.589.584a.313.313 0 00-.093.22.307.307 0 00.093.22l2.35 2.372c.029.03.063.053.1.07a.31.31 0 00.239 0 .297.297 0 00.1-.07l4.465-4.438a.304.304 0 00.075-.347.31.31 0 00-.072-.103v.006zM306.96 98.595l-.582-.59a.311.311 0 00-.22-.093.308.308 0 00-.22.093l-3.662 3.635-1.547-1.562a.31.31 0 00-.22-.094.303.303 0 00-.22.094l-.589.584a.31.31 0 000 .44l2.347 2.372c.029.03.063.053.101.069a.302.302 0 00.339-.069l4.467-4.438a.312.312 0 00.097-.22.308.308 0 00-.091-.22zm0 0l-.582-.59a.311.311 0 00-.22-.093.308.308 0 00-.22.093l-3.662 3.635-1.547-1.562a.31.31 0 00-.22-.094.303.303 0 00-.22.094l-.589.584a.31.31 0 000 .44l2.347 2.372c.029.03.063.053.101.069a.302.302 0 00.339-.069l4.467-4.438a.312.312 0 00.097-.22.308.308 0 00-.091-.22zm0 0l-.582-.59a.311.311 0 00-.22-.093.308.308 0 00-.22.093l-3.662 3.635-1.547-1.562a.31.31 0 00-.22-.094.303.303 0 00-.22.094l-.589.584a.31.31 0 000 .44l2.347 2.372c.029.03.063.053.101.069a.302.302 0 00.339-.069l4.467-4.438a.312.312 0 00.097-.22.308.308 0 00-.091-.22zm0 0l-.582-.59a.311.311 0 00-.22-.093.308.308 0 00-.22.093l-3.662 3.635-1.547-1.562a.31.31 0 00-.22-.094.303.303 0 00-.22.094l-.589.584a.31.31 0 000 .44l2.347 2.372c.029.03.063.053.101.069a.302.302 0 00.339-.069l4.467-4.438a.312.312 0 00.097-.22.308.308 0 00-.091-.22zm-3.628-4.621a6.417 6.417 0 00-6.285 7.671 6.412 6.412 0 005.035 5.044 6.401 6.401 0 006.578-2.73 6.42 6.42 0 00-.797-8.105 6.4 6.4 0 00-4.531-1.88zm0 11.615a5.18 5.18 0 01-4.793-3.208 5.201 5.201 0 013.781-7.085 5.179 5.179 0 015.326 2.21c.57.854.874 1.86.874 2.887a5.202 5.202 0 01-1.516 3.677 5.178 5.178 0 01-3.672 1.516v.003zm3.628-6.99l-.582-.59a.31.31 0 00-.22-.094.308.308 0 00-.22.093l-3.662 3.635-1.547-1.562a.31.31 0 00-.22-.094.302.302 0 00-.22.094l-.589.584a.31.31 0 000 .44l2.347 2.372c.029.03.063.053.101.069a.302.302 0 00.339-.069l4.467-4.438a.3.3 0 00.098-.22.304.304 0 00-.092-.223v.002zm0 0l-.582-.59a.31.31 0 00-.22-.094.308.308 0 00-.22.093l-3.662 3.635-1.547-1.562a.31.31 0 00-.22-.094.302.302 0 00-.22.094l-.589.584a.31.31 0 000 .44l2.347 2.372c.029.03.063.053.101.069a.302.302 0 00.339-.069l4.467-4.438a.3.3 0 00.098-.22.304.304 0 00-.092-.223v.002zm0 0l-.582-.59a.31.31 0 00-.22-.094.308.308 0 00-.22.093l-3.662 3.635-1.547-1.562a.31.31 0 00-.22-.094.302.302 0 00-.22.094l-.589.584a.31.31 0 000 .44l2.347 2.372c.029.03.063.053.101.069a.302.302 0 00.339-.069l4.467-4.438a.3.3 0 00.098-.22.304.304 0 00-.092-.223v.002zm0 0l-.582-.59a.31.31 0 00-.22-.094.308.308 0 00-.22.093l-3.662 3.635-1.547-1.562a.31.31 0 00-.22-.094.302.302 0 00-.22.094l-.589.584a.31.31 0 000 .44l2.347 2.372c.029.03.063.053.101.069a.302.302 0 00.339-.069l4.467-4.438a.3.3 0 00.098-.22.304.304 0 00-.092-.223v.002zm0 0l-.582-.59a.31.31 0 00-.22-.094.308.308 0 00-.22.093l-3.662 3.635-1.547-1.562a.31.31 0 00-.22-.094.302.302 0 00-.22.094l-.589.584a.31.31 0 000 .44l2.347 2.372c.029.03.063.053.101.069a.302.302 0 00.339-.069l4.467-4.438a.3.3 0 00.098-.22.304.304 0 00-.092-.223v.002zM287.774 145.407l-.582-.59a.303.303 0 00-.101-.069.302.302 0 00-.339.069l-3.662 3.634-1.547-1.562a.31.31 0 00-.439 0l-.589.584a.301.301 0 00-.07.34c.017.038.04.072.07.1l2.346 2.372a.301.301 0 00.339.07.321.321 0 00.101-.07l4.467-4.438a.309.309 0 00.097-.219.309.309 0 00-.091-.221zm0 0l-.582-.59a.303.303 0 00-.101-.069.302.302 0 00-.339.069l-3.662 3.634-1.547-1.562a.31.31 0 00-.439 0l-.589.584a.301.301 0 00-.07.34c.017.038.04.072.07.1l2.346 2.372a.301.301 0 00.339.07.321.321 0 00.101-.07l4.467-4.438a.309.309 0 00.097-.219.309.309 0 00-.091-.221zm0 0l-.582-.59a.303.303 0 00-.101-.069.302.302 0 00-.339.069l-3.662 3.634-1.547-1.562a.31.31 0 00-.439 0l-.589.584a.301.301 0 00-.07.34c.017.038.04.072.07.1l2.346 2.372a.301.301 0 00.339.07.321.321 0 00.101-.07l4.467-4.438a.309.309 0 00.097-.219.309.309 0 00-.091-.221zm0 0l-.582-.59a.303.303 0 00-.101-.069.302.302 0 00-.339.069l-3.662 3.634-1.547-1.562a.31.31 0 00-.439 0l-.589.584a.301.301 0 00-.07.34c.017.038.04.072.07.1l2.346 2.372a.301.301 0 00.339.07.321.321 0 00.101-.07l4.467-4.438a.309.309 0 00.097-.219.309.309 0 00-.091-.221zm-3.628-4.622a6.416 6.416 0 00-6.285 7.671 6.414 6.414 0 005.035 5.044 6.393 6.393 0 003.702-.365 6.418 6.418 0 003.957-5.931 6.43 6.43 0 00-1.877-4.539 6.403 6.403 0 00-4.532-1.88zm0 11.616a5.181 5.181 0 01-2.882-.876 5.2 5.2 0 011.87-9.418 5.186 5.186 0 015.326 2.21c.57.855.874 1.859.874 2.887a5.191 5.191 0 01-1.515 3.678 5.163 5.163 0 01-3.673 1.516v.003zm3.628-6.991l-.582-.59a.303.303 0 00-.101-.069.302.302 0 00-.339.069l-3.662 3.634-1.547-1.562a.31.31 0 00-.439 0l-.589.584a.301.301 0 00-.07.34c.017.038.04.072.07.1l2.346 2.372a.301.301 0 00.339.07.321.321 0 00.101-.07l4.467-4.438a.297.297 0 00.098-.22.293.293 0 00-.023-.121.284.284 0 00-.069-.102v.003zm0 0l-.582-.59a.303.303 0 00-.101-.069.302.302 0 00-.339.069l-3.662 3.634-1.547-1.562a.31.31 0 00-.439 0l-.589.584a.301.301 0 00-.07.34c.017.038.04.072.07.1l2.346 2.372a.301.301 0 00.339.07.321.321 0 00.101-.07l4.467-4.438a.297.297 0 00.098-.22.293.293 0 00-.023-.121.284.284 0 00-.069-.102v.003zm0 0l-.582-.59a.303.303 0 00-.101-.069.302.302 0 00-.339.069l-3.662 3.634-1.547-1.562a.31.31 0 00-.439 0l-.589.584a.301.301 0 00-.07.34c.017.038.04.072.07.1l2.346 2.372a.301.301 0 00.339.07.321.321 0 00.101-.07l4.467-4.438a.297.297 0 00.098-.22.293.293 0 00-.023-.121.284.284 0 00-.069-.102v.003zm0 0l-.582-.59a.303.303 0 00-.101-.069.302.302 0 00-.339.069l-3.662 3.634-1.547-1.562a.31.31 0 00-.439 0l-.589.584a.301.301 0 00-.07.34c.017.038.04.072.07.1l2.346 2.372a.301.301 0 00.339.07.321.321 0 00.101-.07l4.467-4.438a.297.297 0 00.098-.22.293.293 0 00-.023-.121.284.284 0 00-.069-.102v.003zm0 0l-.582-.59a.303.303 0 00-.101-.069.302.302 0 00-.339.069l-3.662 3.634-1.547-1.562a.31.31 0 00-.439 0l-.589.584a.301.301 0 00-.07.34c.017.038.04.072.07.1l2.346 2.372a.301.301 0 00.339.07.321.321 0 00.101-.07l4.467-4.438a.297.297 0 00.098-.22.293.293 0 00-.023-.121.284.284 0 00-.069-.102v.003zM349.568 75.187l-.583-.578a.298.298 0 00-.219-.093.306.306 0 00-.22.093l-1.904 1.895-.687.682-.058.055-.357.358-.638.632-1.547-1.562a.308.308 0 00-.44 0l-.589.584a.312.312 0 00-.093.22.307.307 0 00.093.22l2.216 2.241.131.132a.304.304 0 00.44.003l1.849-1.835.61-.61 2.002-1.99a.306.306 0 00-.006-.447zm0 0l-.583-.578a.298.298 0 00-.219-.093.306.306 0 00-.22.093l-1.904 1.895-.687.682-.058.055-.357.358-.638.632-1.547-1.562a.308.308 0 00-.44 0l-.589.584a.312.312 0 00-.093.22.307.307 0 00.093.22l2.216 2.241.131.132a.304.304 0 00.44.003l1.849-1.835.61-.61 2.002-1.99a.306.306 0 00-.006-.447zm0 0l-.583-.578a.298.298 0 00-.219-.093.306.306 0 00-.22.093l-1.904 1.895-.687.682-.058.055-.357.358-.638.632-1.547-1.562a.308.308 0 00-.44 0l-.589.584a.312.312 0 00-.093.22.307.307 0 00.093.22l2.216 2.241.131.132a.304.304 0 00.44.003l1.849-1.835.61-.61 2.002-1.99a.306.306 0 00-.006-.447zm0 0l-.583-.578a.298.298 0 00-.219-.093.306.306 0 00-.22.093l-1.904 1.895-.687.682-.058.055-.357.358-.638.632-1.547-1.562a.308.308 0 00-.44 0l-.589.584a.312.312 0 00-.093.22.307.307 0 00.093.22l2.216 2.241.131.132a.304.304 0 00.44.003l1.849-1.835.61-.61 2.002-1.99a.306.306 0 00-.006-.447zm-3.628-4.619a6.402 6.402 0 00-4.17 1.5 6.422 6.422 0 00-1.386 8.21 6.415 6.415 0 003.447 2.79 6.4 6.4 0 004.477-.092c.317-.126.624-.278.915-.456a6.418 6.418 0 002.93-7.236 6.422 6.422 0 00-2.309-3.413 6.4 6.4 0 00-3.904-1.303zm2.273 11.087a5.056 5.056 0 01-.665.272 5.213 5.213 0 01-3.406-.067 5.197 5.197 0 01-1.681-8.731 5.182 5.182 0 018.501 2.56 5.195 5.195 0 01-2.749 5.966zm1.355-6.468l-.583-.578a.298.298 0 00-.219-.093.306.306 0 00-.22.093l-1.904 1.895-.687.682-.058.055-.357.358-.638.632-1.547-1.562a.308.308 0 00-.44 0l-.589.584a.312.312 0 00-.093.22.307.307 0 00.093.22l2.216 2.241.131.132a.304.304 0 00.44.003l1.849-1.835.61-.61 2.002-1.99a.306.306 0 00-.006-.447zm0 0l-.583-.578a.298.298 0 00-.219-.093.306.306 0 00-.22.093l-1.904 1.895-.687.682-.058.055-.357.358-.638.632-1.547-1.562a.308.308 0 00-.44 0l-.589.584a.312.312 0 00-.093.22.307.307 0 00.093.22l2.216 2.241.131.132a.304.304 0 00.44.003l1.849-1.835.61-.61 2.002-1.99a.306.306 0 00-.006-.447zm0 0l-.583-.578a.298.298 0 00-.219-.093.306.306 0 00-.22.093l-1.904 1.895-.687.682-.058.055-.357.358-.638.632-1.547-1.562a.308.308 0 00-.44 0l-.589.584a.312.312 0 00-.093.22.307.307 0 00.093.22l2.216 2.241.131.132a.304.304 0 00.44.003l1.849-1.835.61-.61 2.002-1.99a.306.306 0 00-.006-.447zm0 0l-.583-.578a.298.298 0 00-.219-.093.306.306 0 00-.22.093l-1.904 1.895-.687.682-.058.055-.357.358-.638.632-1.547-1.562a.308.308 0 00-.44 0l-.589.584a.312.312 0 00-.093.22.307.307 0 00.093.22l2.216 2.241.131.132a.304.304 0 00.44.003l1.849-1.835.61-.61 2.002-1.99a.306.306 0 00-.006-.447zm0 0l-.583-.578a.298.298 0 00-.219-.093.306.306 0 00-.22.093l-1.904 1.895-.687.682-.058.055-.357.358-.638.632-1.547-1.562a.308.308 0 00-.44 0l-.589.584a.312.312 0 00-.093.22.307.307 0 00.093.22l2.216 2.241.131.132a.304.304 0 00.44.003l1.849-1.835.61-.61 2.002-1.99a.306.306 0 00-.006-.447z"
  }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("path", {
    className: "fill-theme-color",
    d: "M268.92 47H150.08c-3.358 0-6.08 2.91-6.08 6.5s2.722 6.5 6.08 6.5h118.84c3.358 0 6.08-2.91 6.08-6.5s-2.722-6.5-6.08-6.5z",
    opacity: ".6"
  }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("path", {
    className: "fill-theme-color",
    d: "M321.919 71H150.081c-3.359 0-6.081 2.686-6.081 6s2.722 6 6.081 6h171.838c3.359 0 6.081-2.686 6.081-6s-2.722-6-6.081-6z",
    opacity: ".3"
  }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("path", {
    className: "fill-theme-color",
    d: "M279.927 94H150.073c-3.354 0-6.073 2.91-6.073 6.5s2.719 6.5 6.073 6.5h129.854c3.354 0 6.073-2.91 6.073-6.5s-2.719-6.5-6.073-6.5z",
    opacity: ".6"
  }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("path", {
    className: "fill-theme-color",
    d: "M321.919 118H150.081c-3.359 0-6.081 2.686-6.081 6s2.722 6 6.081 6h171.838c3.359 0 6.081-2.686 6.081-6s-2.722-6-6.081-6z",
    opacity: ".3"
  }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("path", {
    className: "fill-theme-color",
    d: "M261.916 141H150.084c-3.36 0-6.084 2.686-6.084 6s2.724 6 6.084 6h111.832c3.36 0 6.084-2.686 6.084-6s-2.724-6-6.084-6z",
    opacity: ".1"
  }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("path", {
    fill: "#CCC",
    d: "M316.161 47.162a6.4 6.4 0 00-5.92 3.963 6.432 6.432 0 001.389 6.996 6.404 6.404 0 009.86-.973 6.428 6.428 0 00-.797-8.106 6.404 6.404 0 00-4.532-1.88zm0 11.616a5.18 5.18 0 01-2.882-.876 5.198 5.198 0 011.87-9.417 5.181 5.181 0 015.326 2.21c.57.854.874 1.859.874 2.887a5.195 5.195 0 01-3.201 4.8c-.63.26-1.305.392-1.987.39v.006z"
  }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("path", {
    fill: "#CCC",
    d: "M318.127 56.366l.808-.807a.35.35 0 00.078-.117.346.346 0 000-.276.35.35 0 00-.078-.117l-1.458-1.458 1.455-1.458a.35.35 0 00.078-.117.346.346 0 000-.277.35.35 0 00-.078-.117l-.808-.807a.364.364 0 00-.254-.105.358.358 0 00-.253.105l-1.456 1.458-1.455-1.458a.361.361 0 00-.51 0l-.806.807a.365.365 0 00-.107.255.365.365 0 00.107.256l1.456 1.458-1.453 1.455a.365.365 0 00-.079.394.381.381 0 00.079.116l.806.807a.353.353 0 00.255.106.363.363 0 00.255-.106l1.455-1.458 1.456 1.458a.352.352 0 00.253.107.356.356 0 00.254-.104zM369.966 70.568a6.402 6.402 0 00-5.921 3.963 6.432 6.432 0 001.389 6.995 6.404 6.404 0 0010.94-4.539 6.403 6.403 0 00-3.953-5.935 6.383 6.383 0 00-2.455-.484zm0 11.616a5.179 5.179 0 01-3.17-1.076 5.203 5.203 0 01-1.621-6.136 5.187 5.187 0 015.512-3.13 5.186 5.186 0 012.985 1.519 5.2 5.2 0 01-1.158 8.146 5.18 5.18 0 01-2.548.674v.003z"
  }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("path", {
    fill: "#CCC",
    d: "M371.925 79.772l.808-.807a.363.363 0 000-.51l-1.458-1.459 1.458-1.458a.348.348 0 00.078-.116.343.343 0 000-.275.346.346 0 00-.078-.116l-.808-.81a.358.358 0 00-.507 0l-1.452 1.458-1.456-1.458a.358.358 0 00-.507 0l-.808.81a.36.36 0 00-.078.391.348.348 0 00.078.116l1.455 1.458-1.455 1.458a.364.364 0 000 .51l.808.808a.35.35 0 00.507 0l1.456-1.458 1.458 1.458a.358.358 0 00.501 0z"
  }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("path", {
    className: "fill-theme-color",
    d: "M344 94h90v80h-90z"
  }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("path", {
    fill: "#fff",
    fillOpacity: ".65",
    d: "M364.607 150.419H357v25.307h7.607v-25.307zM379.317 132h-7.607v43.455h7.607V132zM394.026 136h-7.607v61.603h7.607V136zM408.736 123h-7.607v55.726h7.607V123zM423.445 132.197h-7.607v38.342h7.607v-38.342z",
    opacity: ".2"
  }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("path", {
    fill: "#fff",
    d: "M356.331 134l-.331-.495 15.486-21.052 13.65 14.005 11.039-17.456 4.84 5.868 13.168-11.268 14.625 14.021L451.763 99l.237.594-23.213 18.833-14.619-14.015-13.201 11.297-4.748-5.756-11.014 17.418-13.677-14.031L356.331 134z"
  })), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("defs", null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("clipPath", {
    id: "clip0"
  }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("path", {
    fill: "#fff",
    d: "M0 0h517v160H0z"
  }))));
};

/***/ }),

/***/ "./client/homescreen/welcome-modal/illustrations/pie-chart.js":
/*!********************************************************************!*\
  !*** ./client/homescreen/welcome-modal/illustrations/pie-chart.js ***!
  \********************************************************************/
/*! exports provided: PieChartIllustration */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "PieChartIllustration", function() { return PieChartIllustration; });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);


/**
 * External dependencies
 */

var PieChartIllustration = function PieChartIllustration() {
  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("svg", {
    xmlns: "http://www.w3.org/2000/svg",
    width: "517",
    height: "160",
    fill: "none"
  }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("defs", null), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("g", {
    clipPath: "url(#clip0)"
  }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("path", {
    className: "fill-theme-color",
    d: "M0 0h517v160H0z"
  }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("path", {
    fill: "#fff",
    fillOpacity: ".65",
    d: "M30.501 63.74c7.21-10.372 19.533-17.051 31.735-22.399l2.057-.888c12.774-5.469 25.944-10.008 39.27-14.127 7.129-2.21 14.285-4.313 21.448-6.389l5.615-1.62c7.29-2.106 14.596-4.21 21.916-6.315a6165.97 6165.97 0 0121.511-6.139 3346.684 3346.684 0 0127.597-7.677 2189.847 2189.847 0 0121.603-5.782c9.237-2.42 18.491-4.764 27.761-7.035 7.246-1.77 14.502-3.483 21.767-5.14a1152.381 1152.381 0 0128.025-6 940.985 940.985 0 0119.106-3.654l2.908-.52c27.416-4.852 55.724-8.222 82.193-2.775l.715.151c.355.074.71.148 1.067.23a87.181 87.181 0 0114.309 4.404c8.282 3.398 15.644 8.247 20.596 14.967 7.763 10.54 8.624 24.398 6.126 37.281-2.498 12.884-8.007 25.346-12.299 37.974-1.257 3.7-2.378 7.49-3.34 11.33-5.997 24.068-5.398 49.993 11.766 67.323a93.715 93.715 0 007.029 6.227c3.928 3.218 7.905 6.424 11.03 10.3 7.28 9.017 9.211 20.756 10.296 32.099 1.425 15.086 1.236 31.775-9.516 44.175-11.153 12.875-30.519 17.317-48.211 18.232-27.498 1.457-54.442-3.316-81.339-6.956-26.898-3.641-54.739-6.141-81.787-.263a121.18 121.18 0 00-17.082 5.062 108.9 108.9 0 00-21.21 10.677c-9.622 6.318-17.826 14.22-23.006 23.613-11.123 20.092-39.488 28.645-62.664 24.15-22.115-4.288-39.921-20.774-44.019-40.738-4.538-22.229 6.615-44.308 16.332-66.515a358.83 358.83 0 003.437-8.081 238.988 238.988 0 001.795-4.513 165.185 165.185 0 002.828-7.947c4.39-13.591 6.016-28.984-2.295-40.321-4.658-6.347-11.477-10.355-19.238-13.393-17.388-6.801-39.481-8.722-52.38-21.167C22.84 94.854 21.359 76.92 30.502 63.74z"
  }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("path", {
    fill: "#F6F7F7",
    d: "M124 33h267v185H124z"
  }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("path", {
    className: "fill-theme-color",
    d: "M169 152.005V229a734.947 734.947 0 01-15.628-.991l-2.372-.181v-75.823c0-.395.072-.785.212-1.15.14-.365.345-.696.604-.975.258-.279.565-.5.903-.651a2.61 2.61 0 011.066-.229h12.43c.366 0 .728.078 1.066.229.338.151.645.372.903.651.259.279.464.61.604.975.14.365.212.755.212 1.15z",
    opacity: ".7"
  }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("path", {
    className: "fill-theme-color",
    d: "M186 229.733V127.377c0-.63.31-1.235.861-1.681.551-.446 1.299-.696 2.079-.696h13.12c.386 0 .768.061 1.125.181.357.119.681.294.954.515.273.221.489.483.637.771.148.289.224.598.224.91V230l-19-.267z",
    opacity: ".5"
  }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("path", {
    className: "fill-theme-color",
    d: "M225 230.002v-97.406a2.843 2.843 0 012.843-2.845h12.689a2.844 2.844 0 012.844 2.845v97.196l-18.376.21z",
    opacity: ".7"
  }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("path", {
    className: "fill-theme-color",
    d: "M282 88.368v140.224c-6 .145-12 .281-18 .408V88.368c0-.628.293-1.23.816-1.674.522-.445 1.231-.694 1.969-.694h12.43c.738 0 1.447.25 1.969.694.523.444.816 1.046.816 1.674z",
    opacity: ".5"
  }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("path", {
    className: "fill-theme-color",
    d: "M319 112.954v115.709c-6 .12-12 .232-18 .337V112.954c0-.518.293-1.015.816-1.382.522-.366 1.231-.572 1.969-.572h12.43c.738 0 1.447.206 1.969.572.523.367.816.864.816 1.382z",
    opacity: ".7"
  }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("path", {
    stroke: "#CCC",
    strokeLinecap: "round",
    strokeMiterlimit: "10",
    strokeWidth: "2",
    d: "M160.125 133.501l41.91-46.767 41.91 23.545 41.91-72.248 41.909 34.511"
  }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("path", {
    className: "fill-theme-color",
    d: "M160 139.005c2.761 0 5-2.24 5-5.003a5.002 5.002 0 00-5-5.002c-2.761 0-5 2.24-5 5.002a5.002 5.002 0 005 5.003zM201.5 93.007c4.142 0 7.5-3.36 7.5-7.504A7.502 7.502 0 00201.5 78c-4.142 0-7.5 3.36-7.5 7.504a7.502 7.502 0 007.5 7.503zM243.784 119.31c4.985 0 9.026-4.043 9.026-9.031s-4.041-9.031-9.026-9.031c-4.986 0-9.027 4.043-9.027 9.031s4.041 9.031 9.027 9.031zM286.027 46.062c4.985 0 9.027-4.043 9.027-9.031S291.012 28 286.027 28c-4.986 0-9.027 4.043-9.027 9.031s4.041 9.031 9.027 9.031zM327.5 80.007c4.142 0 7.5-3.36 7.5-7.504A7.502 7.502 0 00327.5 65c-4.142 0-7.5 3.36-7.5 7.504a7.502 7.502 0 007.5 7.503zM408 137l-36 2-18-30.926c5.588-3.326 12.033-5.083 18.606-5.074C392.154 103 408 118.222 408 137zM351.107 110l-.143.088c-7.887 4.836-13.573 12.518-15.859 21.429a35.211 35.211 0 003.603 26.338l.084.145L370 140.317 351.107 110zm-12.19 47.543a34.886 34.886 0 01-3.485-25.944c2.25-8.77 7.826-16.342 15.566-21.138l18.531 29.738-30.612 17.344zM408.664 138.651l-35.891 2.797 10.3 32.297.162-.046c7.808-2.265 14.585-6.957 19.211-13.301 4.626-6.344 6.824-13.96 6.23-21.588l-.012-.159zm-35.447 3.081l35.134-2.738c1.116 15.348-9.387 29.753-25.051 34.355l-10.083-31.617zM370.719 142.639l-30.714 17.335.088.131c3.977 5.942 9.926 10.554 16.982 13.165 7.056 2.61 14.849 3.083 22.245 1.349l.164-.038-8.765-31.942zm-30.249 17.435l30.034-16.951 8.57 31.234c-7.278 1.673-14.935 1.192-21.871-1.374-6.936-2.566-12.794-7.086-16.733-12.909z"
  }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("path", {
    fill: "#fff",
    d: "M423 97h-17v-1h17v1zM423 101h-17v-3h17v3zM416 104h-17.979l-.05.068L384 122.821l.28.179 13.92-18.685H416V104z"
  })), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("defs", null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("clipPath", {
    id: "clip0"
  }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("path", {
    fill: "#fff",
    d: "M0 0h517v160H0z"
  }))));
};

/***/ }),

/***/ "./client/homescreen/welcome-modal/index.js":
/*!**************************************************!*\
  !*** ./client/homescreen/welcome-modal/index.js ***!
  \**************************************************/
/*! exports provided: WelcomeModal */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "WelcomeModal", function() { return WelcomeModal; });
/* harmony import */ var _babel_runtime_helpers_slicedToArray__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/slicedToArray */ "./node_modules/@babel/runtime/helpers/slicedToArray.js");
/* harmony import */ var _babel_runtime_helpers_slicedToArray__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_slicedToArray__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/components */ "./node_modules/@wordpress/components/build-module/index.js");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var lib_tracks__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! lib/tracks */ "./client/lib/tracks.js");
/* harmony import */ var _illustrations_line_chart__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./illustrations/line-chart */ "./client/homescreen/welcome-modal/illustrations/line-chart.js");
/* harmony import */ var _illustrations_inbox__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./illustrations/inbox */ "./client/homescreen/welcome-modal/illustrations/inbox.js");
/* harmony import */ var _illustrations_pie_chart__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ./illustrations/pie-chart */ "./client/homescreen/welcome-modal/illustrations/pie-chart.js");
/* harmony import */ var _page_content__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ./page-content */ "./client/homescreen/welcome-modal/page-content/index.js");
/* harmony import */ var _style_scss__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ./style.scss */ "./client/homescreen/welcome-modal/style.scss");
/* harmony import */ var _style_scss__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(_style_scss__WEBPACK_IMPORTED_MODULE_9__);



/**
 * External dependencies
 */



/**
 * Internal dependencies
 */







var pages = [{
  image: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])(_illustrations_line_chart__WEBPACK_IMPORTED_MODULE_5__["LineChartIllustration"], null),
  content: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])(_page_content__WEBPACK_IMPORTED_MODULE_8__["PageContent"], {
    title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__["__"])('Welcome to your WooCommerce store’s online HQ!', 'woocommerce'),
    body: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__["__"])("Here's where you’ll find setup suggestions, tips and tools, and key data on your store’s performance and earnings — all the basics for store management and growth.", 'woocommerce')
  })
}, {
  image: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])(_illustrations_inbox__WEBPACK_IMPORTED_MODULE_6__["InboxIllustration"], null),
  content: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])(_page_content__WEBPACK_IMPORTED_MODULE_8__["PageContent"], {
    title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__["__"])('A personalized inbox full of relevant advice.', 'woocommerce'),
    body: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__["__"])('Check your inbox for helpful growth tips tailored to your store and notifications about key traffic and sales milestones. We look forward to celebrating them with you!', 'woocommerce')
  })
}, {
  image: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])(_illustrations_pie_chart__WEBPACK_IMPORTED_MODULE_7__["PieChartIllustration"], null),
  content: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])(_page_content__WEBPACK_IMPORTED_MODULE_8__["PageContent"], {
    title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__["__"])('Good data leads to smart business decisions.', 'woocommerce'),
    body: 'Monitor your stats to improve performance, increase sales, and track your progress toward revenue goals. The more you know, the better you can serve your customers and grow your store.'
  })
}];
var WelcomeModal = function WelcomeModal(_ref) {
  var onClose = _ref.onClose;

  var _useState = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["useState"])(true),
      _useState2 = _babel_runtime_helpers_slicedToArray__WEBPACK_IMPORTED_MODULE_0___default()(_useState, 2),
      guideIsOpen = _useState2[0],
      setGuideIsOpen = _useState2[1];

  Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["useEffect"])(function () {
    Object(lib_tracks__WEBPACK_IMPORTED_MODULE_4__["recordEvent"])('task_list_welcome_modal_open');
  }, []);
  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["Fragment"], null, guideIsOpen && Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__["Guide"], {
    onFinish: function onFinish() {
      setGuideIsOpen(false);
      onClose();
      Object(lib_tracks__WEBPACK_IMPORTED_MODULE_4__["recordEvent"])('task_list_welcome_modal_close');
    },
    className: 'woocommerce__welcome-modal',
    finishButtonText: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__["__"])("Let's go", 'woocommerce'),
    pages: pages
  }));
};

/***/ }),

/***/ "./client/homescreen/welcome-modal/page-content/index.js":
/*!***************************************************************!*\
  !*** ./client/homescreen/welcome-modal/page-content/index.js ***!
  \***************************************************************/
/*! exports provided: PageContent */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "PageContent", function() { return PageContent; });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);


/**
 * External dependencies
 */

var PageContent = function PageContent(_ref) {
  var title = _ref.title,
      body = _ref.body;
  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("div", {
    className: "woocommerce__welcome-modal__page-content"
  }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("h2", {
    className: "woocommerce__welcome-modal__page-content__header"
  }, title), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("p", {
    className: "woocommerce__welcome-modal__page-content__body"
  }, body));
};

/***/ }),

/***/ "./client/homescreen/welcome-modal/style.scss":
/*!****************************************************!*\
  !*** ./client/homescreen/welcome-modal/style.scss ***!
  \****************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),

/***/ "./client/lib/notices/index.js":
/*!*************************************!*\
  !*** ./client/lib/notices/index.js ***!
  \*************************************/
/*! exports provided: createNoticesFromResponse */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "createNoticesFromResponse", function() { return createNoticesFromResponse; });
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_0__);
/**
 * External dependencies
 */

function createNoticesFromResponse(response) {
  var _dispatch = Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_0__["dispatch"])('core/notices'),
      createNotice = _dispatch.createNotice;

  if (response.error_data && response.errors && Object.keys(response.errors).length) {
    // Loop over multi-error responses.
    Object.keys(response.errors).forEach(function (errorKey) {
      createNotice('error', response.errors[errorKey].join(' '));
    });
  } else if (response.message) {
    // Handle generic messages.
    createNotice(response.code ? 'error' : 'success', response.message);
  }
}

/***/ }),

/***/ "./client/quick-links/index.js":
/*!*************************************!*\
  !*** ./client/quick-links/index.js ***!
  \*************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/defineProperty */ "./node_modules/@babel/runtime/helpers/defineProperty.js");
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/components */ "./node_modules/@wordpress/components/build-module/index.js");
/* harmony import */ var _wordpress_icons__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @wordpress/icons */ "./node_modules/@wordpress/icons/build-module/index.js");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! lodash */ "lodash");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @woocommerce/wc-admin-settings */ "./client/settings/index.js");
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @woocommerce/components */ "@woocommerce/components");
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_components__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var lib_tracks__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! lib/tracks */ "./client/lib/tracks.js");
/* harmony import */ var _style_scss__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ./style.scss */ "./client/quick-links/style.scss");
/* harmony import */ var _style_scss__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(_style_scss__WEBPACK_IMPORTED_MODULE_9__);



function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0___default()(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

/**
 * External dependencies
 */




/**
 * WooCommerce dependencies
 */



/**
 * Internal dependencies
 */




function getItems(props) {
  return [{
    title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__["__"])('Market my store', 'woocommerce'),
    type: 'wc-admin',
    path: 'marketing',
    icon: _wordpress_icons__WEBPACK_IMPORTED_MODULE_4__["megaphone"],
    listItemTag: 'marketing'
  }, {
    title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__["__"])('Add products', 'woocommerce'),
    type: 'wp-admin',
    path: 'post-new.php?post_type=product',
    icon: _wordpress_icons__WEBPACK_IMPORTED_MODULE_4__["box"],
    listItemTag: 'add-products'
  }, {
    title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__["__"])('Personalize my store', 'woocommerce'),
    type: 'wp-admin',
    path: 'customize.php',
    icon: _wordpress_icons__WEBPACK_IMPORTED_MODULE_4__["brush"],
    listItemTag: 'personalize-store'
  }, {
    title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__["__"])('Shipping settings', 'woocommerce'),
    type: 'wc-settings',
    tab: 'shipping',
    icon: _wordpress_icons__WEBPACK_IMPORTED_MODULE_4__["shipping"],
    listItemTag: 'shipping-settings'
  }, {
    title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__["__"])('Tax settings', 'woocommerce'),
    type: 'wc-settings',
    tab: 'tax',
    icon: _wordpress_icons__WEBPACK_IMPORTED_MODULE_4__["percent"],
    listItemTag: 'tax-settings'
  }, {
    title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__["__"])('Payment settings', 'woocommerce'),
    type: 'wc-settings',
    tab: 'checkout',
    icon: _wordpress_icons__WEBPACK_IMPORTED_MODULE_4__["payment"],
    listItemTag: 'payment-settings'
  }, {
    title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__["__"])('Edit store details', 'woocommerce'),
    type: 'wc-settings',
    tab: 'general',
    icon: _wordpress_icons__WEBPACK_IMPORTED_MODULE_4__["pencil"],
    listItemTag: 'edit-store-details'
  }, {
    title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__["__"])('Get support', 'woocommerce'),
    type: 'external',
    href: 'https://woocommerce.com/my-account/create-a-ticket/',
    icon: _wordpress_icons__WEBPACK_IMPORTED_MODULE_4__["lifesaver"],
    after: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])(_wordpress_icons__WEBPACK_IMPORTED_MODULE_4__["Icon"], {
      icon: _wordpress_icons__WEBPACK_IMPORTED_MODULE_4__["external"]
    }),
    listItemTag: 'support'
  }, {
    title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__["__"])('View my store', 'woocommerce'),
    type: 'external',
    href: props.getSetting('siteUrl'),
    icon: _wordpress_icons__WEBPACK_IMPORTED_MODULE_4__["home"],
    after: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])(_wordpress_icons__WEBPACK_IMPORTED_MODULE_4__["Icon"], {
      icon: _wordpress_icons__WEBPACK_IMPORTED_MODULE_4__["external"]
    }),
    listItemTag: 'view-store'
  }];
}

function handleOnItemClick(props, event) {
  var a = event.currentTarget;
  var listItemTag = a.dataset.listItemTag;

  if (!listItemTag) {
    return;
  }

  props.recordEvent('home_quick_links_click', {
    task_name: listItemTag
  });

  if (typeof props.onItemClick !== 'function') {
    return;
  }

  if (!props.onItemClick(listItemTag)) {
    event.preventDefault();
    return false;
  }
}

function getLinkTypeAndHref(item) {
  var linkType;
  var href;

  switch (item.type) {
    case 'wc-admin':
      linkType = 'wc-admin';
      href = "admin.php?page=wc-admin&path=%2F".concat(item.path);
      break;

    case 'wp-admin':
      linkType = 'wp-admin';
      href = item.path;
      break;

    case 'wc-settings':
      linkType = 'wp-admin';
      href = "admin.php?page=wc-settings&tab=".concat(item.tab);
      break;

    default:
      linkType = 'external';
      href = item.href;
      break;
  }

  return {
    linkType: linkType,
    href: href
  };
}

function getListItems(props) {
  return getItems(props).map(function (item) {
    return _objectSpread(_objectSpread({
      title: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__["__experimentalText"], {
        as: "div",
        variant: "button"
      }, item.title),
      before: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])(_wordpress_icons__WEBPACK_IMPORTED_MODULE_4__["Icon"], {
        icon: item.icon
      }),
      after: item.after
    }, getLinkTypeAndHref(item)), {}, {
      listItemTag: item.listItemTag,
      onClick: Object(lodash__WEBPACK_IMPORTED_MODULE_5__["partial"])(handleOnItemClick, props)
    });
  });
}

var QuickLinks = function QuickLinks(props) {
  var listItems = getListItems(props);
  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__["Card"], {
    size: "large",
    className: "woocommerce-quick-links"
  }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__["CardHeader"], {
    size: "medium"
  }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__["__experimentalText"], {
    variant: "title.small"
  }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__["__"])('Store management', 'woocommerce'))), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__["CardBody"], null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_7__["List"], {
    items: listItems,
    className: "woocommerce-quick-links__list"
  })));
};

QuickLinks.defaultProps = {
  getSetting: _woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_6__["getSetting"],
  recordEvent: lib_tracks__WEBPACK_IMPORTED_MODULE_8__["recordEvent"]
};
/* harmony default export */ __webpack_exports__["default"] = (QuickLinks);

/***/ }),

/***/ "./client/quick-links/style.scss":
/*!***************************************!*\
  !*** ./client/quick-links/style.scss ***!
  \***************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),

/***/ "./client/task-list/placeholder.js":
/*!*****************************************!*\
  !*** ./client/task-list/placeholder.js ***!
  \*****************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _style_scss__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./style.scss */ "./client/task-list/style.scss");
/* harmony import */ var _style_scss__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_style_scss__WEBPACK_IMPORTED_MODULE_1__);


/**
 * Internal dependencies
 */


var TaskListPlaceholder = function TaskListPlaceholder(props) {
  var _props$numTasks = props.numTasks,
      numTasks = _props$numTasks === void 0 ? 5 : _props$numTasks;
  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("div", {
    className: "woocommerce-task-dashboard__container"
  }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("div", {
    className: "woocommerce-card woocommerce-task-card is-loading",
    "aria-hidden": true
  }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("div", {
    className: "woocommerce-card__header"
  }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("div", {
    className: "woocommerce-card__title-wrapper"
  }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("div", {
    className: "woocommerce-card__title woocommerce-card__header-item"
  }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("span", {
    className: "is-placeholder"
  })))), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("div", {
    className: "woocommerce-card__body"
  }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("div", {
    className: "woocommerce-list"
  }, Array.from(new Array(numTasks)).map(function (v, i) {
    return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("div", {
      key: i,
      className: "woocommerce-list__item has-action"
    }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("div", {
      className: "woocommerce-list__item-inner"
    }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("div", {
      className: "woocommerce-list__item-before"
    }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("span", {
      className: "is-placeholder"
    })), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("div", {
      className: "woocommerce-list__item-text"
    }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("div", {
      className: "woocommerce-list__item-title"
    }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("span", {
      className: "is-placeholder"
    }))), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("div", {
      className: "woocommerce-list__item-after"
    }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("span", {
      className: "is-placeholder"
    }))));
  })))));
};

/* harmony default export */ __webpack_exports__["default"] = (TaskListPlaceholder);

/***/ })

}]);
//# sourceMappingURL=homescreen.1a6248ca0590fb59239b.min.js.map
