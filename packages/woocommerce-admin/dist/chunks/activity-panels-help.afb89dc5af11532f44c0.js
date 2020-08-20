(window["__wcAdmin_webpackJsonp"] = window["__wcAdmin_webpackJsonp"] || []).push([["activity-panels-help"],{

/***/ "./client/header/activity-panel/activity-header/index.js":
/*!***************************************************************!*\
  !*** ./client/header/activity-panel/activity-header/index.js ***!
  \***************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/classCallCheck */ "./node_modules/@babel/runtime/helpers/classCallCheck.js");
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/createClass */ "./node_modules/@babel/runtime/helpers/createClass.js");
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @babel/runtime/helpers/inherits */ "./node_modules/@babel/runtime/helpers/inherits.js");
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @babel/runtime/helpers/possibleConstructorReturn */ "./node_modules/@babel/runtime/helpers/possibleConstructorReturn.js");
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @babel/runtime/helpers/getPrototypeOf */ "./node_modules/@babel/runtime/helpers/getPrototypeOf.js");
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! classnames */ "./node_modules/classnames/index.js");
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(classnames__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! prop-types */ "./node_modules/prop-types/index.js");
/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(prop_types__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! @wordpress/components */ "./node_modules/@wordpress/components/build-module/index.js");
/* harmony import */ var _style_scss__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ./style.scss */ "./client/header/activity-panel/activity-header/style.scss");
/* harmony import */ var _style_scss__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(_style_scss__WEBPACK_IMPORTED_MODULE_9__);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! @woocommerce/components */ "@woocommerce/components");
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_10___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_components__WEBPACK_IMPORTED_MODULE_10__);







function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_4___default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_4___default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_3___default()(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/**
 * External dependencies
 */




/**
 * Internal dependencies
 */




var ActivityHeader = /*#__PURE__*/function (_Component) {
  _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_2___default()(ActivityHeader, _Component);

  var _super = _createSuper(ActivityHeader);

  function ActivityHeader() {
    _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0___default()(this, ActivityHeader);

    return _super.apply(this, arguments);
  }

  _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1___default()(ActivityHeader, [{
    key: "render",
    value: function render() {
      var _this$props = this.props,
          className = _this$props.className,
          menu = _this$props.menu,
          subtitle = _this$props.subtitle,
          title = _this$props.title,
          unreadMessages = _this$props.unreadMessages;
      var cardClassName = classnames__WEBPACK_IMPORTED_MODULE_6___default()({
        'woocommerce-layout__inbox-panel-header': subtitle,
        'woocommerce-layout__activity-panel-header': !subtitle
      }, className);
      var countUnread = unreadMessages ? unreadMessages : 0;
      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])("div", {
        className: cardClassName
      }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])("div", {
        className: "woocommerce-layout__inbox-title"
      }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_8__["__experimentalText"], {
        variant: "title.small"
      }, title), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_8__["__experimentalText"], {
        variant: "button"
      }, countUnread > 0 && Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])("span", {
        className: "woocommerce-layout__inbox-badge"
      }, unreadMessages))), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])("div", {
        className: "woocommerce-layout__inbox-subtitle"
      }, subtitle && Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_8__["__experimentalText"], {
        variant: "body.small"
      }, subtitle)), menu && Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])("div", {
        className: "woocommerce-layout__activity-panel-header-menu"
      }, menu));
    }
  }]);

  return ActivityHeader;
}(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["Component"]);

ActivityHeader.propTypes = {
  className: prop_types__WEBPACK_IMPORTED_MODULE_7___default.a.string,
  unreadMessages: prop_types__WEBPACK_IMPORTED_MODULE_7___default.a.number,
  title: prop_types__WEBPACK_IMPORTED_MODULE_7___default.a.string.isRequired,
  subtitle: prop_types__WEBPACK_IMPORTED_MODULE_7___default.a.string,
  menu: prop_types__WEBPACK_IMPORTED_MODULE_7___default.a.shape({
    type: prop_types__WEBPACK_IMPORTED_MODULE_7___default.a.oneOf([_woocommerce_components__WEBPACK_IMPORTED_MODULE_10__["EllipsisMenu"]])
  })
};
/* harmony default export */ __webpack_exports__["default"] = (ActivityHeader);

/***/ }),

/***/ "./client/header/activity-panel/activity-header/style.scss":
/*!*****************************************************************!*\
  !*** ./client/header/activity-panel/activity-header/style.scss ***!
  \*****************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),

/***/ "./client/header/activity-panel/panels/help.js":
/*!*****************************************************!*\
  !*** ./client/header/activity-panel/panels/help.js ***!
  \*****************************************************/
/*! exports provided: SETUP_TASK_HELP_ITEMS_FILTER, HelpPanel, default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "SETUP_TASK_HELP_ITEMS_FILTER", function() { return SETUP_TASK_HELP_ITEMS_FILTER; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "HelpPanel", function() { return HelpPanel; });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/components */ "./node_modules/@wordpress/components/build-module/index.js");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _wordpress_hooks__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @wordpress/hooks */ "@wordpress/hooks");
/* harmony import */ var _wordpress_hooks__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_wordpress_hooks__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _wordpress_icons__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @wordpress/icons */ "./node_modules/@wordpress/icons/build-module/index.js");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! lodash */ "lodash");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var _woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @woocommerce/wc-admin-settings */ "./client/settings/index.js");
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! @woocommerce/components */ "@woocommerce/components");
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_components__WEBPACK_IMPORTED_MODULE_8__);
/* harmony import */ var _woocommerce_data__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! @woocommerce/data */ "@woocommerce/data");
/* harmony import */ var _woocommerce_data__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_data__WEBPACK_IMPORTED_MODULE_9__);
/* harmony import */ var _activity_header__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! ../activity-header */ "./client/header/activity-panel/activity-header/index.js");
/* harmony import */ var dashboard_utils__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! dashboard/utils */ "./client/dashboard/utils.js");
/* harmony import */ var lib_tracks__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! lib/tracks */ "./client/lib/tracks.js");
/* harmony import */ var task_list_tasks_payments_methods__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! task-list/tasks/payments/methods */ "./client/task-list/tasks/payments/methods.js");
/* harmony import */ var redux__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! redux */ "./node_modules/redux/es/redux.js");


/**
 * External dependencies
 */







/**
 * WooCommerce dependencies
 */




/**
 * Internal dependencies
 */






var SETUP_TASK_HELP_ITEMS_FILTER = 'woocommerce_admin_setup_task_help_items';

function getAppearanceItems() {
  return [{
    title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Showcase your products and tailor your shopping experience using Blocks', 'woocommerce'),
    link: 'https://docs.woocommerce.com/document/woocommerce-blocks/?utm_source=help_panel'
  }, {
    title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Manage Store Notice, Catalog View and Product Images', 'woocommerce'),
    link: 'https://docs.woocommerce.com/document/woocommerce-customizer/?utm_source=help_panel'
  }, {
    title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('How to choose and change a theme', 'woocommerce'),
    link: 'https://docs.woocommerce.com/document/choose-change-theme/?utm_source=help_panel'
  }];
}

function getPaymentsItems(props) {
  var countryCode = props.countryCode,
      profileItems = props.profileItems;
  var paymentMethods = props.getPaymentMethods({
    activePlugins: [],
    countryCode: countryCode,
    options: {},
    profileItems: profileItems
  });

  var methodIsVisible = function methodIsVisible(methodKey) {
    return Boolean(paymentMethods.find(function (method) {
      return method.key === methodKey;
    }));
  };

  var showWCPay = methodIsVisible('wcpay');
  var showStripe = methodIsVisible('stripe');
  var showKlarnaCheckout = methodIsVisible('klarna_checkout');
  var showKlarnaPayments = methodIsVisible('klarna_payments');
  var showPayPal = methodIsVisible('paypal');
  var showSquare = methodIsVisible('square');
  var showPayFast = methodIsVisible('payfast');
  var showEway = methodIsVisible('eway');
  return [{
    title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Which Payment Option is Right for Me?', 'woocommerce'),
    link: 'https://docs.woocommerce.com/document/premium-payment-gateway-extensions/?utm_source=help_panel'
  }, showWCPay && {
    title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('WooCommerce Payments Start Up Guide', 'woocommerce'),
    link: 'https://docs.woocommerce.com/document/payments//?utm_source=help_panel'
  }, showWCPay && {
    title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('WooCommerce Payments FAQs', 'woocommerce'),
    link: 'https://docs.woocommerce.com/documentation/woocommerce-payments/woocommerce-payments-faqs/?utm_source=help_panel'
  }, showStripe && {
    title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Stripe Setup and Configuration', 'woocommerce'),
    link: 'https://docs.woocommerce.com/document/stripe/?utm_source=help_panel'
  }, showPayPal && {
    title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('PayPal Checkout Setup and Configuration', 'woocommerce'),
    link: 'https://docs.woocommerce.com/document/paypal-express-checkout/?utm_source=help_panel'
  }, showSquare && {
    title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Square - Get started', 'woocommerce'),
    link: 'https://docs.woocommerce.com/document/woocommerce-square/?utm_source=help_panel'
  }, showKlarnaCheckout && {
    title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Klarna - Introduction', 'woocommerce'),
    link: 'https://docs.woocommerce.com/document/klarna-checkout/?utm_source=help_panel'
  }, showKlarnaPayments && {
    title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Klarna - Introduction', 'woocommerce'),
    link: 'https://docs.woocommerce.com/document/klarna-payments/?utm_source=help_panel'
  }, showPayFast && {
    title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('PayFast Setup and Configuration', 'woocommerce'),
    link: 'https://docs.woocommerce.com/document/payfast-payment-gateway/?utm_source=help_panel'
  }, showEway && {
    title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('eWAY Setup and Configuration', 'woocommerce'),
    link: 'https://docs.woocommerce.com/document/eway/?utm_source=help_panel'
  }, {
    title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Direct Bank Transfer (BACS)', 'woocommerce'),
    link: 'https://docs.woocommerce.com/document/bacs/?utm_source=help_panel'
  }, {
    title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Cash on Delivery', 'woocommerce'),
    link: 'https://docs.woocommerce.com/document/cash-on-delivery/?utm_source=help_panel'
  }].filter(Boolean);
}

function getProductsItems() {
  return [{
    title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Adding and Managing Products', 'woocommerce'),
    link: 'https://docs.woocommerce.com/document/managing-products/?utm_source=help_panel'
  }, {
    title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Import products using the CSV Importer and Exporter', 'woocommerce'),
    link: 'https://docs.woocommerce.com/document/product-csv-importer-exporter/?utm_source=help_panel'
  }, {
    title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Migrate products using Cart2Cart', 'woocommerce'),
    link: 'https://woocommerce.com/products/cart2cart/?utm_source=help_panel'
  }, {
    title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Learn more about setting up products', 'woocommerce'),
    link: 'https://docs.woocommerce.com/documentation/plugins/woocommerce/getting-started/setup-products/?utm_source=help_panel'
  }];
}

function getShippingItems(_ref) {
  var activePlugins = _ref.activePlugins,
      countryCode = _ref.countryCode;
  var showWCS = countryCode === 'US' && !activePlugins.includes('woocommerce-services');
  return [{
    title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Setting up Shipping Zones', 'woocommerce'),
    link: 'https://docs.woocommerce.com/document/setting-up-shipping-zones/?utm_source=help_panel'
  }, {
    title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Core Shipping Options', 'woocommerce'),
    link: 'https://docs.woocommerce.com/documentation/plugins/woocommerce/getting-started/shipping/core-shipping-options/?utm_source=help_panel'
  }, {
    title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Product Shipping Classes', 'woocommerce'),
    link: 'https://docs.woocommerce.com/document/product-shipping-classes/?utm_source=help_panel'
  }, showWCS && {
    title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('WooCommerce Shipping setup and configuration', 'woocommerce'),
    link: 'https://docs.woocommerce.com/document/woocommerce-services/#section-3/?utm_source=help_panel'
  }, {
    title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Learn more about configuring your shipping settings', 'woocommerce'),
    link: 'https://docs.woocommerce.com/documentation/plugins/woocommerce/getting-started/shipping/?utm_source=help_panel'
  }].filter(Boolean);
}

function getTaxItems(props) {
  var countryCode = props.countryCode;

  var _props$getSetting = props.getSetting('onboarding', {}),
      _props$getSetting$aut = _props$getSetting.automatedTaxSupportedCountries,
      automatedTaxSupportedCountries = _props$getSetting$aut === void 0 ? [] : _props$getSetting$aut,
      taxJarActivated = _props$getSetting.taxJarActivated;

  var showWCS = !taxJarActivated && // WCS integration doesn't work with the official TaxJar plugin.
  automatedTaxSupportedCountries.includes(countryCode);
  return [{
    title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Setting up Taxes in WooCommerce', 'woocommerce'),
    link: 'https://docs.woocommerce.com/document/setting-up-taxes-in-woocommerce/?utm_source=help_panel'
  }, showWCS && {
    title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Automated Tax calculation using WooCommerce Services', 'woocommerce'),
    link: 'https://docs.woocommerce.com/document/woocommerce-services/?utm_source=help_panel#section-10'
  }].filter(Boolean);
}

function getItems(props) {
  var taskName = props.taskName;

  switch (taskName) {
    case 'products':
      return getProductsItems();

    case 'appearance':
      return getAppearanceItems();

    case 'shipping':
      return getShippingItems(props);

    case 'tax':
      return getTaxItems(props);

    case 'payments':
      return getPaymentsItems(props);

    default:
      return [];
  }
}

function handleOnItemClick(props, event) {
  var taskName = props.taskName; // event isn't initially set when triggering link with the keyboard.

  if (!event) {
    return;
  }

  props.recordEvent('help_panel_click', {
    task_name: taskName,
    link: event.currentTarget.href
  });
}

function getListItems(props) {
  var itemsByType = getItems(props);
  var genericDocsLink = {
    title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('WooCommerce Docs', 'woocommerce'),
    link: 'https://docs.woocommerce.com/?utm_source=help_panel'
  };
  itemsByType.push(genericDocsLink);
  var filteredItems = Object(_wordpress_hooks__WEBPACK_IMPORTED_MODULE_4__["applyFilters"])(SETUP_TASK_HELP_ITEMS_FILTER, itemsByType, props.taskName, props); // Filter out items that aren't objects without `title` and `link` properties.

  var validatedItems = Array.isArray(filteredItems) ? filteredItems.filter(function (item) {
    return item instanceof Object && item.title && item.link;
  }) : []; // Default empty array to the generic docs link.

  if (!validatedItems.length) {
    validatedItems = [genericDocsLink];
  }

  var onClick = Object(lodash__WEBPACK_IMPORTED_MODULE_6__["partial"])(handleOnItemClick, props);
  return validatedItems.map(function (item) {
    return {
      title: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__["__experimentalText"], {
        as: "div",
        variant: "button"
      }, item.title),
      before: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_wordpress_icons__WEBPACK_IMPORTED_MODULE_5__["Icon"], {
        icon: _wordpress_icons__WEBPACK_IMPORTED_MODULE_5__["page"]
      }),
      after: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_wordpress_icons__WEBPACK_IMPORTED_MODULE_5__["Icon"], {
        icon: _wordpress_icons__WEBPACK_IMPORTED_MODULE_5__["chevronRight"]
      }),
      linkType: 'external',
      target: '_blank',
      href: item.link,
      onClick: onClick
    };
  });
}

var HelpPanel = function HelpPanel(props) {
  var taskName = props.taskName;
  Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["useEffect"])(function () {
    props.recordEvent('help_panel_open', {
      task_name: taskName
    });
  }, [taskName]);
  var listItems = getListItems(props);
  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["Fragment"], null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_activity_header__WEBPACK_IMPORTED_MODULE_10__["default"], {
    title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Documentation', 'woocommerce')
  }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_8__["Section"], null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_8__["List"], {
    items: listItems,
    className: "woocommerce-quick-links__list"
  })));
};
HelpPanel.defaultProps = {
  getPaymentMethods: task_list_tasks_payments_methods__WEBPACK_IMPORTED_MODULE_13__["getPaymentMethods"],
  getSetting: _woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_7__["getSetting"],
  recordEvent: lib_tracks__WEBPACK_IMPORTED_MODULE_12__["recordEvent"]
};
/* harmony default export */ __webpack_exports__["default"] = (Object(redux__WEBPACK_IMPORTED_MODULE_14__["compose"])(Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_3__["withSelect"])(function (select) {
  var _select = select(_woocommerce_data__WEBPACK_IMPORTED_MODULE_9__["ONBOARDING_STORE_NAME"]),
      getProfileItems = _select.getProfileItems;

  var _select2 = select(_woocommerce_data__WEBPACK_IMPORTED_MODULE_9__["SETTINGS_STORE_NAME"]),
      getSettings = _select2.getSettings;

  var _select3 = select(_woocommerce_data__WEBPACK_IMPORTED_MODULE_9__["PLUGINS_STORE_NAME"]),
      getActivePlugins = _select3.getActivePlugins;

  var _getSettings = getSettings('general'),
      _getSettings$general = _getSettings.general,
      generalSettings = _getSettings$general === void 0 ? {} : _getSettings$general;

  var activePlugins = getActivePlugins();
  var profileItems = getProfileItems();
  var countryCode = Object(dashboard_utils__WEBPACK_IMPORTED_MODULE_11__["getCountryCode"])(generalSettings.woocommerce_default_country);
  return {
    activePlugins: activePlugins,
    countryCode: countryCode,
    profileItems: profileItems
  };
}))(HelpPanel));

/***/ })

}]);
//# sourceMappingURL=activity-panels-help.afb89dc5af11532f44c0.min.js.map
