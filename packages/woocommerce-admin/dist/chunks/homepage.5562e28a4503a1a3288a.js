(window["webpackJsonp"] = window["webpackJsonp"] || []).push([["homepage"],{

/***/ "./client/dashboard/utils.js":
/*!***********************************!*\
  !*** ./client/dashboard/utils.js ***!
  \***********************************/
/*! exports provided: getCountryCode, getCurrencyRegion, getProductIdsForCart, getPriceValue, isOnboardingEnabled */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "getCountryCode", function() { return getCountryCode; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "getCurrencyRegion", function() { return getCurrencyRegion; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "getProductIdsForCart", function() { return getProductIdsForCart; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "getPriceValue", function() { return getPriceValue; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "isOnboardingEnabled", function() { return isOnboardingEnabled; });
/* harmony import */ var _wordpress_html_entities__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/html-entities */ "@wordpress/html-entities");
/* harmony import */ var _wordpress_html_entities__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_html_entities__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! lodash */ "lodash");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @woocommerce/wc-admin-settings */ "./client/settings/index.js");
/**
 * External dependencies
 */


/**
 * Internal dependencies
 */


/**
 * Gets the country code from a country:state value string.
 *
 * @param {string} countryState Country state string, e.g. US:GA.
 * @return {string} Country string.
 */

function getCountryCode(countryState) {
  if (!countryState) {
    return null;
  }

  return countryState.split(':')[0];
}
function getCurrencyRegion(countryState) {
  var region = getCountryCode(countryState);
  var euCountries = Object(lodash__WEBPACK_IMPORTED_MODULE_1__["without"])(Object(_woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_2__["getSetting"])('onboarding', {
    euCountries: []
  }).euCountries, 'GB');

  if (euCountries.includes(region)) {
    region = 'EU';
  }

  return region;
}
/**
 * Gets the product IDs for items based on the product types and theme selected in the onboarding profiler.
 *
 * @param {Object} profileItems Onboarding profile.
 * @param {boolean} includeInstalledItems Include installed items in returned product IDs.
 * @return {Array} Product Ids.
 */

function getProductIdsForCart(profileItems) {
  var includeInstalledItems = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : false;
  var onboarding = Object(_woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_2__["getSetting"])('onboarding', {}); // The population of onboarding.productTypes only happens if the task list should be shown
  // so bail early if it isn't present.

  if (!onboarding.productTypes) {
    return productIds;
  }

  var productIds = [];
  var plugins = Object(_woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_2__["getSetting"])('plugins', {});
  var productTypes = profileItems.product_types || [];
  productTypes.forEach(function (productType) {
    if (onboarding.productTypes[productType] && onboarding.productTypes[productType].product && (includeInstalledItems || !plugins.installedPlugins.includes(onboarding.productTypes[productType].slug))) {
      productIds.push(onboarding.productTypes[productType].product);
    }
  });
  var theme = onboarding.themes.find(function (themeData) {
    return themeData.slug === profileItems.theme;
  });

  if (theme && theme.id && getPriceValue(theme.price) > 0 && (includeInstalledItems || !theme.is_installed)) {
    productIds.push(theme.id);
  }

  return productIds;
}
/**
 * Get the value of a price from a string, removing any non-numeric characters.
 *
 * @param {string} string Price string.
 * @return {number} Number value.
 */

function getPriceValue(string) {
  return Number(Object(_wordpress_html_entities__WEBPACK_IMPORTED_MODULE_0__["decodeEntities"])(string).replace(/[^0-9.-]+/g, ''));
}
/**
 * Returns if the onboarding feature of WooCommerce Admin should be enabled.
 *
 * While we preform an a/b test of onboarding, the feature will be enabled within the plugin build,
 * but only if the user recieved the test/opted in.
 *
 * @return {boolean} True if the onboarding is enabled.
 */

function isOnboardingEnabled() {
  if (false) {}

  return Object(_woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_2__["getSetting"])('onboardingEnabled', false);
}

/***/ }),

/***/ "./client/homepage/index.js":
/*!**********************************!*\
  !*** ./client/homepage/index.js ***!
  \**********************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/compose */ "./node_modules/@wordpress/compose/build-module/index.js");
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @woocommerce/components */ "@woocommerce/components");
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_components__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var wc_api_with_select__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! wc-api/with-select */ "./client/wc-api/with-select.js");
/* harmony import */ var dashboard_utils__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! dashboard/utils */ "./client/dashboard/utils.js");
/* harmony import */ var _stats_overview__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./stats-overview */ "./client/homepage/stats-overview/index.js");


/**
 * External dependencies
 */


/**
 * WooCommerce dependencies
 */


/**
 * Internal dependencies
 */




var ProfileWizard = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["lazy"])(function () {
  return Promise.all(/*! import() | profile-wizard */[__webpack_require__.e("vendors~activity-panels-inbox~activity-panels-orders~activity-panels-stock~dashboard-charts~devdocs~~f6270017"), __webpack_require__.e("profile-wizard~task-list"), __webpack_require__.e("profile-wizard")]).then(__webpack_require__.bind(null, /*! ../profile-wizard */ "./client/profile-wizard/index.js"));
});

var Homepage = function Homepage(_ref) {
  var profileItems = _ref.profileItems,
      query = _ref.query;

  if (Object(dashboard_utils__WEBPACK_IMPORTED_MODULE_4__["isOnboardingEnabled"])() && !profileItems.completed) {
    return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["Suspense"], {
      fallback: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_2__["Spinner"], null)
    }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(ProfileWizard, {
      query: query
    }));
  }

  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_stats_overview__WEBPACK_IMPORTED_MODULE_5__["default"], null);
};

/* harmony default export */ __webpack_exports__["default"] = (Object(_wordpress_compose__WEBPACK_IMPORTED_MODULE_1__["compose"])(Object(wc_api_with_select__WEBPACK_IMPORTED_MODULE_3__["default"])(function (select) {
  if (!Object(dashboard_utils__WEBPACK_IMPORTED_MODULE_4__["isOnboardingEnabled"])()) {
    return;
  }

  var _select = select('wc-api'),
      getProfileItems = _select.getProfileItems;

  var profileItems = getProfileItems();
  return {
    profileItems: profileItems
  };
}))(Homepage));

/***/ }),

/***/ "./client/homepage/stats-overview/defaults.js":
/*!****************************************************!*\
  !*** ./client/homepage/stats-overview/defaults.js ***!
  \****************************************************/
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

var DEFAULT_STATS = Object(_wordpress_hooks__WEBPACK_IMPORTED_MODULE_0__["applyFilters"])('woocommerce_admin_homepage_default_stats', ['revenue/total_sales', 'revenue/net_revenue', 'orders/orders_count', 'products/items_sold']);
var DEFAULT_HIDDEN_STATS = ['revenue/net_revenue', 'products/items_sold'];

/***/ }),

/***/ "./client/homepage/stats-overview/index.js":
/*!*************************************************!*\
  !*** ./client/homepage/stats-overview/index.js ***!
  \*************************************************/
/*! exports provided: StatsOverview, default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "StatsOverview", function() { return StatsOverview; });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/compose */ "./node_modules/@wordpress/compose/build-module/index.js");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! lodash */ "lodash");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! prop-types */ "./node_modules/prop-types/index.js");
/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(prop_types__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var lib_tracks__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! lib/tracks */ "./client/lib/tracks.js");
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @woocommerce/components */ "@woocommerce/components");
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_components__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var _woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! @woocommerce/wc-admin-settings */ "./client/settings/index.js");
/* harmony import */ var wc_api_with_select__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! wc-api/with-select */ "./client/wc-api/with-select.js");
/* harmony import */ var _defaults__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! ./defaults */ "./client/homepage/stats-overview/defaults.js");


/**
 * External dependencies
 */







/**
 * WooCommerce dependencies
 */



/**
 * Internal dependencies
 */




var _getSetting = Object(_woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_8__["getSetting"])('dataEndpoints', {
  performanceIndicators: []
}),
    performanceIndicators = _getSetting.performanceIndicators;

var stats = performanceIndicators.filter(function (indicator) {
  return _defaults__WEBPACK_IMPORTED_MODULE_10__["DEFAULT_STATS"].includes(indicator.stat);
});
var StatsOverview = function StatsOverview(_ref) {
  var userPrefs = _ref.userPrefs,
      updateCurrentUserData = _ref.updateCurrentUserData;
  var userHiddenStats = userPrefs.hiddenStats;
  var hiddenStats = userHiddenStats ? userHiddenStats : _defaults__WEBPACK_IMPORTED_MODULE_10__["DEFAULT_HIDDEN_STATS"];

  var toggleStat = function toggleStat(stat) {
    var nextHiddenStats = Object(lodash__WEBPACK_IMPORTED_MODULE_3__["xor"])(hiddenStats, [stat]);
    updateCurrentUserData({
      homepage_stats: {
        hiddenStats: nextHiddenStats
      }
    });
    Object(lib_tracks__WEBPACK_IMPORTED_MODULE_6__["recordEvent"])('statsoverview_indicators_toggle', {
      indicator_name: stat,
      status: nextHiddenStats.includes(stat) ? 'off' : 'on'
    });
  };

  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_7__["Card"], {
    className: "woocommerce-analytics__card",
    title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Stats overview', 'woocommerce'),
    menu: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_7__["EllipsisMenu"], {
      label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Choose which values to display', 'woocommerce'),
      renderContent: function renderContent() {
        return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["Fragment"], null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_7__["MenuTitle"], null, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Display stats:', 'woocommerce')), stats.map(function (item) {
          var checked = !hiddenStats.includes(item.stat);
          return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_7__["MenuItem"], {
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
    })
  }, "Content Here");
};
StatsOverview.propTypes = {
  /**
   * Homepage user preferences.
   */
  userPrefs: prop_types__WEBPACK_IMPORTED_MODULE_5___default.a.object.isRequired,

  /**
   * A method to update user meta.
   */
  updateCurrentUserData: prop_types__WEBPACK_IMPORTED_MODULE_5___default.a.func.isRequired
};
/* harmony default export */ __webpack_exports__["default"] = (Object(_wordpress_compose__WEBPACK_IMPORTED_MODULE_2__["compose"])(Object(wc_api_with_select__WEBPACK_IMPORTED_MODULE_9__["default"])(function (select) {
  var _select = select('wc-api'),
      getCurrentUserData = _select.getCurrentUserData;

  return {
    userPrefs: getCurrentUserData().homepage_stats || {}
  };
}), Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_4__["withDispatch"])(function (dispatch) {
  var _dispatch = dispatch('wc-api'),
      updateCurrentUserData = _dispatch.updateCurrentUserData;

  return {
    updateCurrentUserData: updateCurrentUserData
  };
}))(StatsOverview));

/***/ })

}]);
//# sourceMappingURL=homepage.5562e28a4503a1a3288a.min.js.map
