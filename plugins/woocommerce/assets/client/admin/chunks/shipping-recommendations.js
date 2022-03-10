(window["__wcAdmin_webpackJsonp"] = window["__wcAdmin_webpackJsonp"] || []).push([[49],{

/***/ 541:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "a", function() { return createNoticesFromResponse; });
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(8);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_0__);
/**
 * External dependencies
 */

function createNoticesFromResponse(response) {
  const {
    createNotice
  } = Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_0__["dispatch"])('core/notices');

  if (response.error_data && response.errors && Object.keys(response.errors).length) {
    // Loop over multi-error responses.
    Object.keys(response.errors).forEach(errorKey => {
      createNotice('error', response.errors[errorKey].join(' '));
    });
  } else if (response.message) {
    // Handle generic messages.
    createNotice(response.code ? 'error' : 'success', response.message);
  }
}

/***/ }),

/***/ 569:
/***/ (function(module, exports, __webpack_require__) {

"use strict";
Object.defineProperty(exports,"__esModule",{value:!0}),exports["default"]=_default;var _react=_interopRequireDefault(__webpack_require__(6)),_excluded=["size","onClick","icon","className"];function _interopRequireDefault(a){return a&&a.__esModule?a:{default:a}}function _extends(){return _extends=Object.assign||function(a){for(var b,c=1;c<arguments.length;c++)for(var d in b=arguments[c],b)Object.prototype.hasOwnProperty.call(b,d)&&(a[d]=b[d]);return a},_extends.apply(this,arguments)}function _objectWithoutProperties(a,b){if(null==a)return{};var c,d,e=_objectWithoutPropertiesLoose(a,b);if(Object.getOwnPropertySymbols){var f=Object.getOwnPropertySymbols(a);for(d=0;d<f.length;d++)c=f[d],0<=b.indexOf(c)||Object.prototype.propertyIsEnumerable.call(a,c)&&(e[c]=a[c])}return e}function _objectWithoutPropertiesLoose(a,b){if(null==a)return{};var c,d,e={},f=Object.keys(a);for(d=0;d<f.length;d++)c=f[d],0<=b.indexOf(c)||(e[c]=a[c]);return e}function _default(a){var b=a.size,c=void 0===b?24:b,d=a.onClick,e=a.icon,f=a.className,g=_objectWithoutProperties(a,_excluded),h=["gridicon","gridicons-external",f,!!function isModulo18(a){return 0==a%18}(c)&&"needs-offset",!1,!1].filter(Boolean).join(" ");return _react["default"].createElement("svg",_extends({className:h,height:c,width:c,onClick:d},g,{xmlns:"http://www.w3.org/2000/svg",viewBox:"0 0 24 24"}),_react["default"].createElement("g",null,_react["default"].createElement("path",{d:"M19 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h6v2H5v12h12v-6h2zM13 3v2h4.586l-7.793 7.793 1.414 1.414L19 6.414V11h2V3h-8z"})))}


/***/ }),

/***/ 636:
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),

/***/ 637:
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),

/***/ 638:
/***/ (function(module, exports) {

module.exports = "data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAxMjAgMTIwIj48cGF0aCBmaWxsPSIjN2Q1N2E0IiBkPSJNMCAwaDEyMHYxMjBIMHoiLz48cGF0aCBmaWxsPSIjZmZmIiBkPSJNNjcuNDggNTMuNTVjLTEuMTktLjI2LTIuMzMuNDItMy40MyAyLjAzLS44NyAxLjI2LTEuNDUgMi41Ni0xLjc0IDMuOTEtLjE2Ljc3LS4yNCAxLjU4LS4yNCAyLjQxIDAgLjk3LjE5IDEuOTYuNTggMi45OS40OCAxLjI2IDEuMTMgMS45NiAxLjkzIDIuMTIuOC4xNiAxLjY5LS4xOSAyLjY2LTEuMDYgMS4yMi0xLjA5IDIuMDYtMi43MiAyLjUxLTQuODguMTYtLjc3LjI0LTEuNTguMjQtMi40MSAwLS45Ny0uMTktMS45Ni0uNTgtMi45OS0uNDgtMS4yNS0xLjEyLTEuOTYtMS45My0yLjEyem0yMC42MiAwYy0xLjE5LS4yNi0yLjMzLjQyLTMuNDMgMi4wMy0uODcgMS4yNi0xLjQ1IDIuNTYtMS43NCAzLjkxLS4xNi43Ny0uMjQgMS41OC0uMjQgMi40MSAwIC45Ny4xOSAxLjk2LjU4IDIuOTkuNDggMS4yNiAxLjEzIDEuOTYgMS45MyAyLjEyLjguMTYgMS42OS0uMTkgMi42Ni0xLjA2IDEuMjItMS4wOSAyLjA2LTIuNzIgMi41MS00Ljg4LjE2LS43Ny4yNC0xLjU4LjI0LTIuNDEgMC0uOTctLjE5LTEuOTYtLjU4LTIuOTktLjQ4LTEuMjUtMS4xMi0xLjk2LTEuOTMtMi4xMnoiLz48cGF0aCBmaWxsPSIjZmZmIiBkPSJNOTIuNzYgNDBIMjcuMjRjLTQuMTQgMC03LjUgMy4zNi03LjUgNy41djI0Ljk4YzAgNC4xNCAzLjM2IDcuNSA3LjUgNy41aDMxLjA0bDE0LjE5IDcuOS0zLjIyLTcuOWgyMy41YzQuMTQgMCA3LjUtMy4zNiA3LjUtNy41VjQ3LjVjLjAxLTQuMTQtMy4zNS03LjUtNy40OS03LjV6TTUyLjc0IDcyLjkxYy4wNi44NC0uMDcgMS41NS0uMzggMi4xNi0uNC43NC0uOTggMS4xMy0xLjc1IDEuMTktLjg3LjA2LTEuNzMtLjM1LTIuNi0xLjIyLTMuMDYtMy4xNC01LjQ5LTcuODEtNy4yOC0xNC0yLjEyIDQuMjEtMy43MSA3LjM3LTQuNzUgOS40OC0xLjkzIDMuNzItMy41OSA1LjYyLTQuOTcgNS43Mi0uOS4wNi0xLjY2LS42OS0yLjI5LTIuMjYtMS42OS00LjMtMy41LTEyLjYzLTUuNDQtMjQuOTctLjEzLS44Ni4wNS0xLjYuNTItMi4yMS40Ny0uNjEgMS4xNi0uOTUgMi4wNi0xLjAyIDEuNjctLjEyIDIuNjMuNjcgMi44OCAyLjM2IDEuMDMgNi44NiAyLjE0IDEyLjY5IDMuMzEgMTcuNDhsNy4yMS0xMy43MmMuNjYtMS4yNCAxLjQ4LTEuOSAyLjQ3LTEuOTcgMS40NC0uMSAyLjM1LjgyIDIuNzEgMi43Ni44MiA0LjM2IDEuODYgOC4xMSAzLjEyIDExLjI1Ljg2LTguMzUgMi4zMS0xNC4zOSA0LjM0LTE4LjExLjQ4LS45IDEuMjEtMS4zOSAyLjE3LTEuNDYuNzctLjA1IDEuNDYuMTYgMi4wOC42NS42Mi40OS45NSAxLjEyIDEgMS44OS4wNC41OC0uMDcgMS4xLS4zMiAxLjU3LTEuMjggMi4zOC0yLjM0IDYuMzQtMy4xOCAxMS44OS0uODIgNS4zNC0xLjEzIDkuNTMtLjkxIDEyLjU0em0yMC4yLTUuMTZjLTEuOTYgMy4yOC00LjU0IDQuOTItNy43MiA0LjkyLS41OCAwLTEuMTgtLjA3LTEuNzktLjE5LTIuMzItLjQ4LTQuMDctMS43NS01LjI2LTMuODEtMS4wNi0xLjgtMS41OS0zLjk3LTEuNTktNi41MiAwLTMuMzguODUtNi40NyAyLjU2LTkuMjcgMi0zLjI4IDQuNTctNC45MiA3LjcyLTQuOTIuNTggMCAxLjE3LjA3IDEuNzkuMTkgMi4zMi40OCA0LjA3IDEuNzUgNS4yNiAzLjgxIDEuMDYgMS43NyAxLjU5IDMuOTMgMS41OSA2LjQ3LS4wMSAzLjM4LS44NiA2LjQ4LTIuNTYgOS4zMnptMjAuNjIgMGMtMS45NiAzLjI4LTQuNTQgNC45Mi03LjcyIDQuOTItLjU4IDAtMS4xNy0uMDctMS43OC0uMTktMi4zMi0uNDgtNC4wNy0xLjc1LTUuMjYtMy44MS0xLjA2LTEuOC0xLjU5LTMuOTctMS41OS02LjUyIDAtMy4zOC44NS02LjQ3IDIuNTYtOS4yNyAyLTMuMjggNC41Ny00LjkyIDcuNzItNC45Mi41OCAwIDEuMTcuMDcgMS43OC4xOSAyLjMyLjQ4IDQuMDcgMS43NSA1LjI2IDMuODEgMS4wNiAxLjc3IDEuNTkgMy45MyAxLjU5IDYuNDcgMCAzLjM4LS44NiA2LjQ4LTIuNTYgOS4zMnoiLz48L3N2Zz4K"

/***/ }),

/***/ 639:
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),

/***/ 667:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
// ESM COMPAT FLAG
__webpack_require__.r(__webpack_exports__);

// EXTERNAL MODULE: external ["wp","element"]
var external_wp_element_ = __webpack_require__(0);

// EXTERNAL MODULE: external ["wp","i18n"]
var external_wp_i18n_ = __webpack_require__(2);

// EXTERNAL MODULE: external ["wp","data"]
var external_wp_data_ = __webpack_require__(8);

// EXTERNAL MODULE: external ["wc","experimental"]
var external_wc_experimental_ = __webpack_require__(19);

// EXTERNAL MODULE: external ["wc","data"]
var external_wc_data_ = __webpack_require__(12);

// EXTERNAL MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/gridicons@3.4.0_react@17.0.2/node_modules/gridicons/dist/external.js
var external = __webpack_require__(569);
var external_default = /*#__PURE__*/__webpack_require__.n(external);

// EXTERNAL MODULE: external ["wp","components"]
var external_wp_components_ = __webpack_require__(4);

// EXTERNAL MODULE: ./client/lib/notices/index.js
var notices = __webpack_require__(541);

// EXTERNAL MODULE: external ["wc","components"]
var external_wc_components_ = __webpack_require__(22);

// EXTERNAL MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/classnames@2.3.1/node_modules/classnames/index.js
var classnames = __webpack_require__(7);
var classnames_default = /*#__PURE__*/__webpack_require__.n(classnames);

// EXTERNAL MODULE: ./client/settings-recommendations/dismissable-list.scss
var dismissable_list = __webpack_require__(636);

// CONCATENATED MODULE: ./client/settings-recommendations/dismissable-list.tsx


/**
 * External dependencies
 */







/**
 * Internal dependencies
 */

 // using a context provider for the option name so that the option name prop doesn't need to be passed to the `DismissableListHeading` too

const OptionNameContext = Object(external_wp_element_["createContext"])('');
const DismissableListHeading = _ref => {
  let {
    children,
    onDismiss = () => null
  } = _ref;
  const {
    updateOptions
  } = Object(external_wp_data_["useDispatch"])(external_wc_data_["OPTIONS_STORE_NAME"]);
  const dismissOptionName = Object(external_wp_element_["useContext"])(OptionNameContext);

  const handleDismissClick = () => {
    onDismiss();
    updateOptions({
      [dismissOptionName]: 'yes'
    });
  };

  return Object(external_wp_element_["createElement"])(external_wp_components_["CardHeader"], null, Object(external_wp_element_["createElement"])("div", {
    className: "woocommerce-dismissable-list__header"
  }, children), Object(external_wp_element_["createElement"])("div", null, Object(external_wp_element_["createElement"])(external_wc_components_["EllipsisMenu"], {
    label: Object(external_wp_i18n_["__"])('Task List Options', 'woocommerce-admin'),
    renderContent: () => Object(external_wp_element_["createElement"])("div", {
      className: "woocommerce-dismissable-list__controls"
    }, Object(external_wp_element_["createElement"])(external_wp_components_["Button"], {
      onClick: handleDismissClick
    }, Object(external_wp_i18n_["__"])('Hide this', 'woocommerce-admin')))
  })));
};
const DismissableList = _ref2 => {
  let {
    children,
    className,
    dismissOptionName
  } = _ref2;
  const isVisible = Object(external_wp_data_["useSelect"])(select => {
    const {
      getOption,
      hasFinishedResolution
    } = select(external_wc_data_["OPTIONS_STORE_NAME"]);
    const hasFinishedResolving = hasFinishedResolution('getOption', [dismissOptionName]);
    const isDismissed = getOption(dismissOptionName) === 'yes';
    return hasFinishedResolving && !isDismissed;
  });

  if (!isVisible) {
    return null;
  }

  return Object(external_wp_element_["createElement"])(external_wp_components_["Card"], {
    size: "medium",
    className: classnames_default()('woocommerce-dismissable-list', className)
  }, Object(external_wp_element_["createElement"])(OptionNameContext.Provider, {
    value: dismissOptionName
  }, children));
};
// EXTERNAL MODULE: external ["wc","wcSettings"]
var external_wc_wcSettings_ = __webpack_require__(15);

// EXTERNAL MODULE: ./client/shipping/woocommerce-services-item.scss
var woocommerce_services_item = __webpack_require__(637);

// EXTERNAL MODULE: ./client/shipping/woo-icon.svg
var woo_icon = __webpack_require__(638);
var woo_icon_default = /*#__PURE__*/__webpack_require__.n(woo_icon);

// EXTERNAL MODULE: ./client/utils/admin-settings.js
var admin_settings = __webpack_require__(23);

// CONCATENATED MODULE: ./client/shipping/woocommerce-services-item.tsx


/**
 * External dependencies
 */






/**
 * Internal dependencies
 */





const WooCommerceServicesItem = _ref => {
  let {
    onSetupClick,
    pluginsBeingSetup
  } = _ref;
  const wcAdminAssetUrl = Object(admin_settings["d" /* getAdminSetting */])('wcAdminAssetUrl', '');
  const {
    createSuccessNotice
  } = Object(external_wp_data_["useDispatch"])('core/notices');
  const isSiteConnectedToJetpack = Object(external_wp_data_["useSelect"])(select => select(external_wc_data_["PLUGINS_STORE_NAME"]).isJetpackConnected());

  const handleSetupClick = () => {
    onSetupClick(['woocommerce-services']).then(() => {
      const actions = [];

      if (!isSiteConnectedToJetpack) {
        actions.push({
          url: Object(external_wc_wcSettings_["getAdminLink"])('plugins.php'),
          label: Object(external_wp_i18n_["__"])('Finish the setup by connecting your store to Jetpack.', 'woocommerce-admin')
        });
      }

      createSuccessNotice(Object(external_wp_i18n_["__"])('🎉 WooCommerce Shipping is installed!', 'woocommerce-admin'), {
        actions
      });
    });
  };

  return Object(external_wp_element_["createElement"])("div", {
    className: "woocommerce-list__item-inner woocommerce-services-item"
  }, Object(external_wp_element_["createElement"])("div", {
    className: "woocommerce-list__item-before"
  }, Object(external_wp_element_["createElement"])("img", {
    className: "woocommerce-services-item__logo",
    src: woo_icon_default.a,
    alt: ""
  })), Object(external_wp_element_["createElement"])("div", {
    className: "woocommerce-list__item-text"
  }, Object(external_wp_element_["createElement"])("span", {
    className: "woocommerce-list__item-title"
  }, Object(external_wp_i18n_["__"])('Woocommerce Shipping', 'woocommerce-admin'), Object(external_wp_element_["createElement"])(external_wc_components_["Pill"], null, Object(external_wp_i18n_["__"])('Recommended', 'woocommerce-admin'))), Object(external_wp_element_["createElement"])("span", {
    className: "woocommerce-list__item-content"
  }, Object(external_wp_i18n_["__"])('Print USPS and DHL Express labels straight from your WooCommerce dashboard and save on shipping.', 'woocommerce-admin'), Object(external_wp_element_["createElement"])("br", null), Object(external_wp_element_["createElement"])(external_wp_components_["ExternalLink"], {
    href: "https://woocommerce.com/woocommerce-shipping/"
  }, Object(external_wp_i18n_["__"])('Learn more', 'woocommerce-admin')))), Object(external_wp_element_["createElement"])("div", {
    className: "woocommerce-list__item-after"
  }, Object(external_wp_element_["createElement"])(external_wp_components_["Button"], {
    isSecondary: true,
    onClick: handleSetupClick,
    isBusy: pluginsBeingSetup.includes('woocommerce-services'),
    disabled: pluginsBeingSetup.length > 0
  }, Object(external_wp_i18n_["__"])('Get started', 'woocommerce-admin'))));
};

/* harmony default export */ var shipping_woocommerce_services_item = (WooCommerceServicesItem);
// EXTERNAL MODULE: ./client/shipping/shipping-recommendations.scss
var shipping_recommendations = __webpack_require__(639);

// CONCATENATED MODULE: ./client/shipping/shipping-recommendations.tsx


/**
 * External dependencies
 */





 // eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore VisuallyHidden is present, it's just not typed
// eslint-disable-next-line @woocommerce/dependency-group


/**
 * Internal dependencies
 */






const useInstallPlugin = () => {
  const [pluginsBeingSetup, setPluginsBeingSetup] = Object(external_wp_element_["useState"])([]);
  const {
    installAndActivatePlugins
  } = Object(external_wp_data_["useDispatch"])(external_wc_data_["PLUGINS_STORE_NAME"]);

  const handleSetup = slugs => {
    if (pluginsBeingSetup.length > 0) {
      return Promise.resolve();
    }

    setPluginsBeingSetup(slugs);
    return installAndActivatePlugins(slugs).then(() => {
      setPluginsBeingSetup([]);
    }).catch(response => {
      Object(notices["a" /* createNoticesFromResponse */])(response);
      setPluginsBeingSetup([]);
      return Promise.reject();
    });
  };

  return [pluginsBeingSetup, handleSetup];
};

const ShippingRecommendationsList = _ref => {
  let {
    children
  } = _ref;
  return Object(external_wp_element_["createElement"])(DismissableList, {
    className: "woocommerce-recommended-shipping-extensions",
    dismissOptionName: "woocommerce_settings_shipping_recommendations_hidden"
  }, Object(external_wp_element_["createElement"])(DismissableListHeading, null, Object(external_wp_element_["createElement"])(external_wc_experimental_["Text"], {
    variant: "title.small",
    as: "p",
    size: "20",
    lineHeight: "28px"
  }, Object(external_wp_i18n_["__"])('Recommended shipping solutions', 'woocommerce-admin')), Object(external_wp_element_["createElement"])(external_wc_experimental_["Text"], {
    className: "woocommerce-recommended-shipping__header-heading",
    variant: "caption",
    as: "p",
    size: "12",
    lineHeight: "16px"
  }, Object(external_wp_i18n_["__"])('We recommend adding one of the following shipping extensions to your store. The extension will be installed and activated for you when you click "Get started".', 'woocommerce-admin'))), Object(external_wp_element_["createElement"])("ul", {
    className: "woocommerce-list"
  }, external_wp_element_["Children"].map(children, item => Object(external_wp_element_["createElement"])("li", {
    className: "woocommerce-list__item"
  }, item))), Object(external_wp_element_["createElement"])(external_wp_components_["CardFooter"], null, Object(external_wp_element_["createElement"])(external_wp_components_["Button"], {
    className: "woocommerce-recommended-shipping-extensions__more_options_cta",
    href: "https://woocommerce.com/product-category/woocommerce-extensions/shipping-methods/?utm_source=shipping_recommendations",
    target: "_blank",
    isTertiary: true
  }, Object(external_wp_i18n_["__"])('See more options', 'woocommerce-admin'), Object(external_wp_element_["createElement"])(external_wp_components_["VisuallyHidden"], null, Object(external_wp_i18n_["__"])('(opens in a new tab)', 'woocommerce-admin')), Object(external_wp_element_["createElement"])(external_default.a, {
    size: 18
  }))));
};

const ShippingRecommendations = () => {
  const [pluginsBeingSetup, setupPlugin] = useInstallPlugin();
  const activePlugins = Object(external_wp_data_["useSelect"])(select => select(external_wc_data_["PLUGINS_STORE_NAME"]).getActivePlugins());

  if (activePlugins.includes('woocommerce-services')) {
    return null;
  }

  return Object(external_wp_element_["createElement"])(ShippingRecommendationsList, null, Object(external_wp_element_["createElement"])(shipping_woocommerce_services_item, {
    pluginsBeingSetup: pluginsBeingSetup,
    onSetupClick: setupPlugin
  }));
};

/* harmony default export */ var shipping_shipping_recommendations = __webpack_exports__["default"] = (ShippingRecommendations);

/***/ })

}]);