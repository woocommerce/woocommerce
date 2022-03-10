this["wc"] = this["wc"] || {}; this["wc"]["printShippingLabelBanner"] =
/******/ (function(modules) { // webpackBootstrap
/******/ 	// The module cache
/******/ 	var installedModules = {};
/******/
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/
/******/ 		// Check if module is in cache
/******/ 		if(installedModules[moduleId]) {
/******/ 			return installedModules[moduleId].exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = installedModules[moduleId] = {
/******/ 			i: moduleId,
/******/ 			l: false,
/******/ 			exports: {}
/******/ 		};
/******/
/******/ 		// Execute the module function
/******/ 		modules[moduleId].call(module.exports, module, module.exports, __webpack_require__);
/******/
/******/ 		// Flag the module as loaded
/******/ 		module.l = true;
/******/
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/
/******/
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = modules;
/******/
/******/ 	// expose the module cache
/******/ 	__webpack_require__.c = installedModules;
/******/
/******/ 	// define getter function for harmony exports
/******/ 	__webpack_require__.d = function(exports, name, getter) {
/******/ 		if(!__webpack_require__.o(exports, name)) {
/******/ 			Object.defineProperty(exports, name, { enumerable: true, get: getter });
/******/ 		}
/******/ 	};
/******/
/******/ 	// define __esModule on exports
/******/ 	__webpack_require__.r = function(exports) {
/******/ 		if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 			Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 		}
/******/ 		Object.defineProperty(exports, '__esModule', { value: true });
/******/ 	};
/******/
/******/ 	// create a fake namespace object
/******/ 	// mode & 1: value is a module id, require it
/******/ 	// mode & 2: merge all properties of value into the ns
/******/ 	// mode & 4: return value when already ns object
/******/ 	// mode & 8|1: behave like require
/******/ 	__webpack_require__.t = function(value, mode) {
/******/ 		if(mode & 1) value = __webpack_require__(value);
/******/ 		if(mode & 8) return value;
/******/ 		if((mode & 4) && typeof value === 'object' && value && value.__esModule) return value;
/******/ 		var ns = Object.create(null);
/******/ 		__webpack_require__.r(ns);
/******/ 		Object.defineProperty(ns, 'default', { enumerable: true, value: value });
/******/ 		if(mode & 2 && typeof value != 'string') for(var key in value) __webpack_require__.d(ns, key, function(key) { return value[key]; }.bind(null, key));
/******/ 		return ns;
/******/ 	};
/******/
/******/ 	// getDefaultExport function for compatibility with non-harmony modules
/******/ 	__webpack_require__.n = function(module) {
/******/ 		var getter = module && module.__esModule ?
/******/ 			function getDefault() { return module['default']; } :
/******/ 			function getModuleExports() { return module; };
/******/ 		__webpack_require__.d(getter, 'a', getter);
/******/ 		return getter;
/******/ 	};
/******/
/******/ 	// Object.prototype.hasOwnProperty.call
/******/ 	__webpack_require__.o = function(object, property) { return Object.prototype.hasOwnProperty.call(object, property); };
/******/
/******/ 	// __webpack_public_path__
/******/ 	__webpack_require__.p = "";
/******/
/******/
/******/ 	// Load entry module and return exports
/******/ 	return __webpack_require__(__webpack_require__.s = 495);
/******/ })
/************************************************************************/
/******/ ({

/***/ 0:
/***/ (function(module, exports) {

(function() { module.exports = window["wp"]["element"]; }());

/***/ }),

/***/ 1:
/***/ (function(module, exports, __webpack_require__) {

/**
 * Copyright (c) 2013-present, Facebook, Inc.
 *
 * This source code is licensed under the MIT license found in the
 * LICENSE file in the root directory of this source tree.
 */

if (false) { var throwOnDirectAccess, ReactIs; } else {
  // By explicitly using `prop-types` you are opting into new production behavior.
  // http://fb.me/prop-types-in-prod
  module.exports = __webpack_require__(73)();
}


/***/ }),

/***/ 116:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/**
 * WordPress dependencies
 */

/** @typedef {{icon: JSX.Element, size?: number} & import('@wordpress/primitives').SVGProps} IconProps */

/**
 * Return an SVG icon.
 *
 * @param {IconProps} props icon is the SVG component to render
 *                          size is a number specifiying the icon size in pixels
 *                          Other props will be passed to wrapped SVG component
 *
 * @return {JSX.Element}  Icon component
 */

function Icon(_ref) {
  let {
    icon,
    size = 24,
    ...props
  } = _ref;
  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["cloneElement"])(icon, {
    width: size,
    height: size,
    ...props
  });
}

/* harmony default export */ __webpack_exports__["a"] = (Icon);
//# sourceMappingURL=index.js.map

/***/ }),

/***/ 12:
/***/ (function(module, exports) {

(function() { module.exports = window["wc"]["data"]; }());

/***/ }),

/***/ 14:
/***/ (function(module, exports) {

(function() { module.exports = window["wp"]["compose"]; }());

/***/ }),

/***/ 15:
/***/ (function(module, exports) {

(function() { module.exports = window["wc"]["wcSettings"]; }());

/***/ }),

/***/ 17:
/***/ (function(module, exports) {

(function() { module.exports = window["wc"]["tracks"]; }());

/***/ }),

/***/ 2:
/***/ (function(module, exports) {

(function() { module.exports = window["wp"]["i18n"]; }());

/***/ }),

/***/ 21:
/***/ (function(module, exports) {

(function() { module.exports = window["wp"]["apiFetch"]; }());

/***/ }),

/***/ 23:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "d", function() { return getAdminSetting; });
/* unused harmony export ADMIN_URL */
/* unused harmony export COUNTRIES */
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "a", function() { return CURRENCY; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "b", function() { return LOCALE; });
/* unused harmony export SITE_TITLE */
/* unused harmony export WC_ASSET_URL */
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "c", function() { return ORDER_STATUSES; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "e", function() { return setAdminSetting; });
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(2);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _woocommerce_settings__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(15);
/* harmony import */ var _woocommerce_settings__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_settings__WEBPACK_IMPORTED_MODULE_1__);
/**
 * External dependencies
 */

 // Remove mutable data from settings object to prevent access. Data stores should be used instead.

const mutableSources = ['wcAdminSettings', 'preloadSettings'];
const adminSettings = Object(_woocommerce_settings__WEBPACK_IMPORTED_MODULE_1__["getSetting"])('admin', {});
const ADMIN_SETTINGS_SOURCE = Object.keys(adminSettings).reduce((source, key) => {
  if (!mutableSources.includes(key)) {
    source[key] = adminSettings[key];
  }

  return source;
}, {});
/**
 * Retrieves a setting value from the setting state.
 *
 * @param {string}   name                         The identifier for the setting.
 * @param {*}    [fallback=false]             The value to use as a fallback
 *                                                if the setting is not in the
 *                                                state.
 * @param {Function} [filter=( val ) => val]  	  A callback for filtering the
 *                                                value before it's returned.
 *                                                Receives both the found value
 *                                                (if it exists for the key) and
 *                                                the provided fallback arg.
 *
 * @return {*}  The value present in the settings state for the given
 *                   name.
 */

function getAdminSetting(name) {
  let fallback = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : false;
  let filter = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : val => val;

  if (mutableSources.includes(name)) {
    throw new Error(Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Mutable settings should be accessed via data store.'));
  }

  const value = ADMIN_SETTINGS_SOURCE.hasOwnProperty(name) ? ADMIN_SETTINGS_SOURCE[name] : fallback;
  return filter(value, fallback);
}
const ADMIN_URL = Object(_woocommerce_settings__WEBPACK_IMPORTED_MODULE_1__["getSetting"])('adminUrl');
const COUNTRIES = Object(_woocommerce_settings__WEBPACK_IMPORTED_MODULE_1__["getSetting"])('countries');
const CURRENCY = Object(_woocommerce_settings__WEBPACK_IMPORTED_MODULE_1__["getSetting"])('currency');
const LOCALE = Object(_woocommerce_settings__WEBPACK_IMPORTED_MODULE_1__["getSetting"])('locale');
const SITE_TITLE = Object(_woocommerce_settings__WEBPACK_IMPORTED_MODULE_1__["getSetting"])('siteTitle');
const WC_ASSET_URL = Object(_woocommerce_settings__WEBPACK_IMPORTED_MODULE_1__["getSetting"])('wcAssetUrl');
const ORDER_STATUSES = getAdminSetting('orderStatuses');
/**
 * Sets a value to a property on the settings state.
 *
 * NOTE: This feature is to be removed in favour of data stores when a full migration
 * is complete.
 *
 * @deprecated
 *
 * @param {string}   name                        The setting property key for the
 *                                               setting being mutated.
 * @param {*}    value                       The value to set.
 * @param {Function} [filter=( val ) => val]     Allows for providing a callback
 *                                               to sanitize the setting (eg.
 *                                               ensure it's a number)
 */

function setAdminSetting(name, value) {
  let filter = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : val => val;

  if (mutableSources.includes(name)) {
    throw new Error(Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Mutable settings should be mutated via data store.'));
  }

  ADMIN_SETTINGS_SOURCE[name] = filter(value);
}

/***/ }),

/***/ 273:
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),

/***/ 4:
/***/ (function(module, exports) {

(function() { module.exports = window["wp"]["components"]; }());

/***/ }),

/***/ 44:
/***/ (function(module, exports) {

function _defineProperty(obj, key, value) {
  if (key in obj) {
    Object.defineProperty(obj, key, {
      value: value,
      enumerable: true,
      configurable: true,
      writable: true
    });
  } else {
    obj[key] = value;
  }

  return obj;
}

module.exports = _defineProperty, module.exports.__esModule = true, module.exports["default"] = module.exports;

/***/ }),

/***/ 495:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
// ESM COMPAT FLAG
__webpack_require__.r(__webpack_exports__);

// EXTERNAL MODULE: external ["wp","element"]
var external_wp_element_ = __webpack_require__(0);

// EXTERNAL MODULE: external ["wc","data"]
var external_wc_data_ = __webpack_require__(12);

// EXTERNAL MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/@babel+runtime@7.17.2/node_modules/@babel/runtime/helpers/defineProperty.js
var defineProperty = __webpack_require__(44);
var defineProperty_default = /*#__PURE__*/__webpack_require__.n(defineProperty);

// EXTERNAL MODULE: external ["wp","i18n"]
var external_wp_i18n_ = __webpack_require__(2);

// EXTERNAL MODULE: external ["wp","components"]
var external_wp_components_ = __webpack_require__(4);

// EXTERNAL MODULE: external ["wp","compose"]
var external_wp_compose_ = __webpack_require__(14);

// EXTERNAL MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/@automattic+interpolate-components@1.2.0_react@17.0.2/node_modules/@automattic/interpolate-components/dist/esm/index.js + 1 modules
var esm = __webpack_require__(79);

// EXTERNAL MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/prop-types@15.8.1/node_modules/prop-types/index.js
var prop_types = __webpack_require__(1);
var prop_types_default = /*#__PURE__*/__webpack_require__.n(prop_types);

// EXTERNAL MODULE: external "lodash"
var external_lodash_ = __webpack_require__(5);

// EXTERNAL MODULE: external ["wp","data"]
var external_wp_data_ = __webpack_require__(8);

// EXTERNAL MODULE: external ["wc","tracks"]
var external_wc_tracks_ = __webpack_require__(17);

// EXTERNAL MODULE: ./client/wp-admin-scripts/print-shipping-label-banner/style.scss
var style = __webpack_require__(273);

// CONCATENATED MODULE: ./client/wp-admin-scripts/print-shipping-label-banner/dismiss-modal/index.js



/**
 * External dependencies
 */






/**
 * Internal dependencies
 */


class dismiss_modal_DismissModal extends external_wp_element_["Component"] {
  constructor() {
    super(...arguments);

    defineProperty_default()(this, "setDismissed", timestamp => {
      this.props.updateOptions({
        woocommerce_shipping_dismissed_timestamp: timestamp
      });
    });

    defineProperty_default()(this, "hideBanner", () => {
      document.getElementById('woocommerce-admin-print-label').style.display = 'none';
    });

    defineProperty_default()(this, "remindMeLaterClicked", () => {
      const {
        onCloseAll,
        trackElementClicked
      } = this.props;
      this.setDismissed(Date.now());
      onCloseAll();
      this.hideBanner();
      trackElementClicked('shipping_banner_dismiss_modal_remind_me_later');
    });

    defineProperty_default()(this, "closeForeverClicked", () => {
      const {
        onCloseAll,
        trackElementClicked
      } = this.props;
      this.setDismissed(-1);
      onCloseAll();
      this.hideBanner();
      trackElementClicked('shipping_banner_dismiss_modal_close_forever');
    });
  }

  render() {
    const {
      onClose,
      visible
    } = this.props;

    if (!visible) {
      return null;
    }

    return Object(external_wp_element_["createElement"])(external_wp_components_["Modal"], {
      title: Object(external_wp_i18n_["__"])('Are you sure?', 'woocommerce-admin'),
      onRequestClose: onClose,
      className: "wc-admin-shipping-banner__dismiss-modal"
    }, Object(external_wp_element_["createElement"])("p", {
      className: "wc-admin-shipping-banner__dismiss-modal-help-text"
    }, Object(external_wp_i18n_["__"])('With WooCommerce Shipping you can Print shipping labels from your WooCommerce dashboard at the lowest USPS rates.', 'woocommerce-admin')), Object(external_wp_element_["createElement"])("div", {
      className: "wc-admin-shipping-banner__dismiss-modal-actions"
    }, Object(external_wp_element_["createElement"])(external_wp_components_["Button"], {
      isSecondary: true,
      onClick: this.remindMeLaterClicked
    }, Object(external_wp_i18n_["__"])('Remind me later', 'woocommerce-admin')), Object(external_wp_element_["createElement"])(external_wp_components_["Button"], {
      isPrimary: true,
      onClick: this.closeForeverClicked
    }, Object(external_wp_i18n_["__"])("I don't need this", 'woocommerce-admin'))));
  }

}
/* harmony default export */ var dismiss_modal = (Object(external_wp_compose_["compose"])(Object(external_wp_data_["withDispatch"])(dispatch => {
  const {
    updateOptions
  } = dispatch(external_wc_data_["OPTIONS_STORE_NAME"]);
  return {
    updateOptions
  };
}))(dismiss_modal_DismissModal));
// EXTERNAL MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/@wordpress+icons@6.3.0/node_modules/@wordpress/icons/build-module/icon/index.js
var icon = __webpack_require__(116);

// EXTERNAL MODULE: external ["wp","primitives"]
var external_wp_primitives_ = __webpack_require__(9);

// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/@wordpress+icons@6.3.0/node_modules/@wordpress/icons/build-module/library/warning.js


/**
 * WordPress dependencies
 */

const warning = Object(external_wp_element_["createElement"])(external_wp_primitives_["SVG"], {
  xmlns: "http://www.w3.org/2000/svg",
  viewBox: "-2 -2 24 24"
}, Object(external_wp_element_["createElement"])(external_wp_primitives_["Path"], {
  d: "M10 2c4.42 0 8 3.58 8 8s-3.58 8-8 8-8-3.58-8-8 3.58-8 8-8zm1.13 9.38l.35-6.46H8.52l.35 6.46h2.26zm-.09 3.36c.24-.23.37-.55.37-.96 0-.42-.12-.74-.36-.97s-.59-.35-1.06-.35-.82.12-1.07.35-.37.55-.37.97c0 .41.13.73.38.96.26.23.61.34 1.06.34s.8-.11 1.05-.34z"
}));
/* harmony default export */ var library_warning = (warning);
//# sourceMappingURL=warning.js.map
// CONCATENATED MODULE: ./client/wp-admin-scripts/print-shipping-label-banner/setup-notice/index.js


/**
 * External dependencies
 */


const setupErrorTypes = {
  DOWNLOAD: 'download',
  INSTALL: 'install',
  ACTIVATE: 'activate',
  SETUP: 'setup',
  START: 'start'
};
const setupErrorDescriptions = {
  [setupErrorTypes.DOWNLOAD]: Object(external_wp_i18n_["__"])('download', 'woocommerce-admin'),
  [setupErrorTypes.INSTALL]: Object(external_wp_i18n_["__"])('install', 'woocommerce-admin'),
  [setupErrorTypes.ACTIVATE]: Object(external_wp_i18n_["__"])('activate', 'woocommerce-admin'),
  [setupErrorTypes.SETUP]: Object(external_wp_i18n_["__"])('set up', 'woocommerce-admin'),
  [setupErrorTypes.START]: Object(external_wp_i18n_["__"])('start', 'woocommerce-admin')
};
function SetupNotice(_ref) {
  let {
    isSetupError,
    errorReason
  } = _ref;

  const getErrorMessage = errorType => {
    // Default to 'set up' description if the error type somehow doesn't exist.
    const description = errorType in setupErrorDescriptions ? setupErrorDescriptions[errorType] : setupErrorDescriptions[setupErrorTypes.SETUP];
    return Object(external_wp_i18n_["sprintf"])(Object(external_wp_i18n_["__"])('Unable to %s the plugin. Refresh the page and try again.', 'woocommerce-admin'), description);
  };

  if (!isSetupError) {
    return null;
  }

  return Object(external_wp_element_["createElement"])("div", {
    className: "wc-admin-shipping-banner-install-error"
  }, Object(external_wp_element_["createElement"])(icon["a" /* default */], {
    icon: library_warning,
    className: "warning-icon"
  }), getErrorMessage(errorReason));
}
// EXTERNAL MODULE: external ["wp","apiFetch"]
var external_wp_apiFetch_ = __webpack_require__(21);
var external_wp_apiFetch_default = /*#__PURE__*/__webpack_require__.n(external_wp_apiFetch_);

// CONCATENATED MODULE: ./client/wp-admin-scripts/print-shipping-label-banner/wcs-api.js
/**
 * External dependencies
 */

function acceptWcsTos() {
  const path = '/wc/v1/connect/tos';
  return external_wp_apiFetch_default()({
    path,
    method: 'POST',
    data: {
      accepted: true
    }
  });
}
function getWcsAssets() {
  const path = '/wc/v1/connect/assets';
  return external_wp_apiFetch_default()({
    path,
    method: 'GET'
  });
}
// EXTERNAL MODULE: ./client/utils/admin-settings.js
var admin_settings = __webpack_require__(23);

// CONCATENATED MODULE: ./client/wp-admin-scripts/print-shipping-label-banner/shipping-banner/index.js



/**
 * External dependencies
 */










/**
 * Internal dependencies
 */






const wcAdminAssetUrl = Object(admin_settings["d" /* getAdminSetting */])('wcAdminAssetUrl', '');
const wcsPluginSlug = 'woocommerce-services';
class shipping_banner_ShippingBanner extends external_wp_element_["Component"] {
  constructor(props) {
    var _this;

    super(props);
    _this = this;

    defineProperty_default()(this, "isSetupError", () => this.state.wcsSetupError);

    defineProperty_default()(this, "closeDismissModal", () => {
      this.setState({
        isDismissModalOpen: false
      });
      this.trackElementClicked('shipping_banner_dismiss_modal_close_button');
    });

    defineProperty_default()(this, "openDismissModal", () => {
      this.setState({
        isDismissModalOpen: true
      });
      this.trackElementClicked('shipping_banner_dimiss');
    });

    defineProperty_default()(this, "hideBanner", () => {
      this.setState({
        showShippingBanner: false
      });
    });

    defineProperty_default()(this, "createShippingLabelClicked", () => {
      const {
        activePlugins
      } = this.props;
      this.setState({
        isShippingLabelButtonBusy: true
      });
      this.trackElementClicked('shipping_banner_create_label');

      if (!activePlugins.includes(wcsPluginSlug)) {
        this.installAndActivatePlugins(wcsPluginSlug);
      } else {
        this.acceptTosAndGetWCSAssets();
      }
    });

    defineProperty_default()(this, "woocommerceServiceLinkClicked", () => {
      this.trackElementClicked('shipping_banner_woocommerce_service_link');
    });

    defineProperty_default()(this, "trackBannerEvent", function (eventName) {
      let customProps = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};
      const {
        activePlugins,
        isJetpackConnected
      } = _this.props;
      Object(external_wc_tracks_["recordEvent"])(eventName, {
        banner_name: 'wcadmin_install_wcs_prompt',
        jetpack_installed: activePlugins.includes('jetpack'),
        jetpack_connected: isJetpackConnected,
        wcs_installed: activePlugins.includes(wcsPluginSlug),
        ...customProps
      });
    });

    defineProperty_default()(this, "trackImpression", () => {
      this.trackBannerEvent('banner_impression');
    });

    defineProperty_default()(this, "trackElementClicked", element => {
      this.trackBannerEvent('banner_element_clicked', {
        element
      });
    });

    defineProperty_default()(this, "getInstallText", () => {
      const {
        activePlugins
      } = this.props;

      if (activePlugins.includes(wcsPluginSlug)) {
        // If WCS is active, then the only remaining step is to agree to the ToS.
        return Object(external_wp_i18n_["__"])('You\'ve already installed WooCommerce Shipping. By clicking "Create shipping label", you agree to its {{tosLink}}Terms of Service{{/tosLink}}.', 'woocommerce-admin');
      }

      return Object(external_wp_i18n_["__"])('By clicking "Create shipping label", {{wcsLink}}WooCommerce Shipping{{/wcsLink}} will be installed and you agree to its {{tosLink}}Terms of Service{{/tosLink}}.', 'woocommerce-admin');
    });

    const orderId = new URL(window.location.href).searchParams.get('post');
    this.state = {
      showShippingBanner: true,
      isDismissModalOpen: false,
      setupErrorReason: setupErrorTypes.SETUP,
      orderId: parseInt(orderId, 10),
      wcsAssetsLoaded: false,
      wcsAssetsLoading: false,
      wcsSetupError: false,
      isShippingLabelButtonBusy: false,
      installText: this.getInstallText(),
      isWcsModalOpen: false
    };
  }

  componentDidMount() {
    const {
      showShippingBanner
    } = this.state;

    if (showShippingBanner) {
      this.trackImpression();
    }
  }

  async installAndActivatePlugins(pluginSlug) {
    // Avoid double activating.
    const {
      installPlugins,
      activatePlugins,
      isRequesting
    } = this.props;

    if (isRequesting) {
      return false;
    }

    const install = await installPlugins([pluginSlug]);

    if (install.success !== true) {
      this.setState({
        setupErrorReason: setupErrorTypes.INSTALL,
        wcsSetupError: true
      });
      return;
    }

    const activation = await activatePlugins([pluginSlug]);

    if (activation.success !== true) {
      this.setState({
        setupErrorReason: setupErrorTypes.ACTIVATE,
        wcsSetupError: true
      });
      return;
    }

    this.acceptTosAndGetWCSAssets();
  }

  acceptTosAndGetWCSAssets() {
    return acceptWcsTos().then(() => getWcsAssets()).then(wcsAssets => this.loadWcsAssets(wcsAssets)).catch(() => this.setState({
      wcsSetupError: true
    }));
  }

  generateMetaBoxHtml(nodeId, title, args) {
    const argsJsonString = JSON.stringify(args).replace(/"/g, '&quot;'); // JS has no native html_entities so we just replace.

    const togglePanelText = Object(external_wp_i18n_["__"])('Toggle panel:', 'woocommerce-admin');

    return `
<div id="${nodeId}" class="postbox">
	<div class="postbox-header">
		<h2 class="hndle"><span>${title}</span></h2>
		<div class="handle-actions">
			<button type="button" class="handlediv" aria-expanded="true">
				<span class="screen-reader-text">${togglePanelText} ${title}</span>
				<span class="toggle-indicator" aria-hidden="true"></span>
			</button>
		</div>
	</div>
	<div class="inside">
		<div class="wcc-root woocommerce wc-connect-create-shipping-label" data-args="${argsJsonString}">
		</div>
	</div>
</div>
`;
  }

  loadWcsAssets(_ref) {
    let {
      assets
    } = _ref;

    if (this.state.wcsAssetsLoaded || this.state.wcsAssetsLoading) {
      this.openWcsModal();
      return;
    }

    this.setState({
      wcsAssetsLoading: true
    });
    const jsPath = assets.wc_connect_admin_script;
    const stylePath = assets.wc_connect_admin_style;

    if (undefined === window.wcsPluginData) {
      const assetPath = jsPath.substring(0, jsPath.lastIndexOf('/') + 1);
      window.wcsPluginData = {
        assetPath
      };
    }

    const {
      orderId
    } = this.state;
    const {
      itemsCount
    } = this.props;
    const shippingLabelContainerHtml = this.generateMetaBoxHtml('woocommerce-order-label', Object(external_wp_i18n_["__"])('Shipping Label', 'woocommerce-admin'), {
      order: {
        id: orderId
      },
      context: 'shipping_label',
      items: itemsCount
    }); // Insert shipping label metabox just above main order details box.

    document.getElementById('woocommerce-order-data').insertAdjacentHTML('beforebegin', shippingLabelContainerHtml);
    const shipmentTrackingHtml = this.generateMetaBoxHtml('woocommerce-order-shipment-tracking', Object(external_wp_i18n_["__"])('Shipment Tracking', 'woocommerce-admin'), {
      order: {
        id: orderId
      },
      context: 'shipment_tracking',
      items: itemsCount
    }); // Insert tracking metabox in the side after the order actions.

    document.getElementById('woocommerce-order-actions').insertAdjacentHTML('afterend', shipmentTrackingHtml);

    if (window.jQuery) {
      // Need to refresh so the new metaboxes are sortable.
      window.jQuery('#normal-sortables').sortable('refresh');
      window.jQuery('#side-sortables').sortable('refresh');
      window.jQuery('#woocommerce-order-label').hide();
    }

    Promise.all([new Promise((resolve, reject) => {
      const script = document.createElement('script');
      script.src = jsPath;
      script.async = true;
      script.onload = resolve;
      script.onerror = reject;
      document.body.appendChild(script);
    }), new Promise((resolve, reject) => {
      if (stylePath !== '') {
        const head = document.getElementsByTagName('head')[0];
        const link = document.createElement('link');
        link.rel = 'stylesheet';
        link.type = 'text/css';
        link.href = stylePath;
        link.media = 'all';
        link.onload = resolve;
        link.onerror = reject;
        head.appendChild(link);
      } else {
        resolve();
      }
    })]).then(() => {
      this.setState({
        wcsAssetsLoaded: true,
        wcsAssetsLoading: false,
        isShippingLabelButtonBusy: false
      });
      this.openWcsModal();
    });
  }

  openWcsModal() {
    if (window.wcsGetAppStoreAsync) {
      window.wcsGetAppStoreAsync('wc-connect-create-shipping-label').then(wcsStore => {
        const state = wcsStore.getState();
        const {
          orderId
        } = this.state;
        const siteId = state.ui.selectedSiteId;
        const wcsStoreUnsubscribe = wcsStore.subscribe(() => {
          const latestState = wcsStore.getState();
          const shippingLabelState = Object(external_lodash_["get"])(latestState, ['extensions', 'woocommerce', 'woocommerceServices', siteId, 'shippingLabel', orderId], null);
          const labelSettingsState = Object(external_lodash_["get"])(latestState, ['extensions', 'woocommerce', 'woocommerceServices', siteId, 'labelSettings'], null);
          const packageState = Object(external_lodash_["get"])(latestState, ['extensions', 'woocommerce', 'woocommerceServices', siteId, 'packages'], null);
          const locationsState = Object(external_lodash_["get"])(latestState, ['extensions', 'woocommerce', 'sites', siteId, 'data', 'locations']);

          if (shippingLabelState && labelSettingsState && labelSettingsState.meta && packageState && locationsState) {
            if (shippingLabelState.loaded && labelSettingsState.meta.isLoaded && packageState.isLoaded && Object(external_lodash_["isArray"])(locationsState) && !this.state.isWcsModalOpen) {
              if (window.jQuery) {
                this.setState({
                  isWcsModalOpen: true
                });
                window.jQuery('.shipping-label__new-label-button').click();
              }

              wcsStore.dispatch({
                type: 'NOTICE_CREATE',
                notice: {
                  duration: 10000,
                  status: 'is-success',
                  text: Object(external_wp_i18n_["__"])('Plugin installed and activated', 'woocommerce-admin')
                }
              });
            } else if (shippingLabelState.showPurchaseDialog) {
              wcsStoreUnsubscribe();

              if (window.jQuery) {
                window.jQuery('#woocommerce-order-label').show();
              }
            }
          }
        });
        document.getElementById('woocommerce-admin-print-label').style.display = 'none';
      });
    }
  }

  render() {
    const {
      isDismissModalOpen,
      showShippingBanner,
      isShippingLabelButtonBusy
    } = this.state;

    if (!showShippingBanner) {
      return null;
    }

    return Object(external_wp_element_["createElement"])("div", null, Object(external_wp_element_["createElement"])("div", {
      className: "wc-admin-shipping-banner-container"
    }, Object(external_wp_element_["createElement"])("img", {
      className: "wc-admin-shipping-banner-illustration",
      src: wcAdminAssetUrl + 'shippingillustration.svg',
      alt: Object(external_wp_i18n_["__"])('Shipping ', 'woocommerce-admin')
    }), Object(external_wp_element_["createElement"])("div", {
      className: "wc-admin-shipping-banner-blob"
    }, Object(external_wp_element_["createElement"])("h3", null, Object(external_wp_i18n_["__"])('Print discounted shipping labels with a click.', 'woocommerce-admin')), Object(external_wp_element_["createElement"])("p", null, Object(esm["a" /* default */])({
      mixedString: this.state.installText,
      components: {
        tosLink: Object(external_wp_element_["createElement"])(external_wp_components_["ExternalLink"], {
          href: "https://wordpress.com/tos",
          target: "_blank",
          type: "external"
        }),
        wcsLink: Object(external_wp_element_["createElement"])(external_wp_components_["ExternalLink"], {
          href: "https://woocommerce.com/products/shipping/?utm_medium=product",
          target: "_blank",
          type: "external",
          onClick: this.woocommerceServiceLinkClicked
        })
      }
    })), Object(external_wp_element_["createElement"])(SetupNotice, {
      isSetupError: this.isSetupError(),
      errorReason: this.state.setupErrorReason
    })), Object(external_wp_element_["createElement"])(external_wp_components_["Button"], {
      disabled: isShippingLabelButtonBusy,
      isPrimary: true,
      isBusy: isShippingLabelButtonBusy,
      onClick: this.createShippingLabelClicked
    }, Object(external_wp_i18n_["__"])('Create shipping label', 'woocommerce-admin')), Object(external_wp_element_["createElement"])("button", {
      onClick: this.openDismissModal,
      type: "button",
      className: "notice-dismiss",
      disabled: this.state.isShippingLabelButtonBusy
    }, Object(external_wp_element_["createElement"])("span", {
      className: "screen-reader-text"
    }, Object(external_wp_i18n_["__"])('Close Print Label Banner.', 'woocommerce-admin')))), Object(external_wp_element_["createElement"])(dismiss_modal, {
      visible: isDismissModalOpen,
      onClose: this.closeDismissModal,
      onCloseAll: this.hideBanner,
      trackElementClicked: this.trackElementClicked
    }));
  }

}
shipping_banner_ShippingBanner.propTypes = {
  itemsCount: prop_types_default.a.number.isRequired,
  isJetpackConnected: prop_types_default.a.bool.isRequired,
  activePlugins: prop_types_default.a.array.isRequired,
  activatePlugins: prop_types_default.a.func.isRequired,
  installPlugins: prop_types_default.a.func.isRequired,
  isRequesting: prop_types_default.a.bool.isRequired
};
/* harmony default export */ var shipping_banner = (Object(external_wp_compose_["compose"])(Object(external_wp_data_["withSelect"])(select => {
  const {
    isPluginsRequesting,
    isJetpackConnected,
    getActivePlugins
  } = select(external_wc_data_["PLUGINS_STORE_NAME"]);
  const isRequesting = isPluginsRequesting('activatePlugins') || isPluginsRequesting('installPlugins');
  return {
    isRequesting,
    isJetpackConnected: isJetpackConnected(),
    activePlugins: getActivePlugins()
  };
}), Object(external_wp_data_["withDispatch"])(dispatch => {
  const {
    activatePlugins,
    installPlugins
  } = dispatch(external_wc_data_["PLUGINS_STORE_NAME"]);
  return {
    activatePlugins,
    installPlugins
  };
}))(shipping_banner_ShippingBanner));
// CONCATENATED MODULE: ./client/wp-admin-scripts/print-shipping-label-banner/index.js


/**
 * External dependencies
 */


/**
 * Internal dependencies
 */



const metaBox = document.getElementById('wc-admin-shipping-banner-root');
const print_shipping_label_banner_args = metaBox.dataset.args && JSON.parse(metaBox.dataset.args) || {}; // Render the header.

const HydratedShippingBanner = Object(external_wc_data_["withPluginsHydration"])({ ...Object(admin_settings["d" /* getAdminSetting */])('plugins'),
  jetpackStatus: Object(admin_settings["d" /* getAdminSetting */])('dataEndpoints', {}).jetpackStatus
})(shipping_banner);
Object(external_wp_element_["render"])(Object(external_wp_element_["createElement"])(HydratedShippingBanner, {
  itemsCount: print_shipping_label_banner_args.items
}), metaBox);

/***/ }),

/***/ 5:
/***/ (function(module, exports) {

(function() { module.exports = window["lodash"]; }());

/***/ }),

/***/ 6:
/***/ (function(module, exports) {

(function() { module.exports = window["React"]; }());

/***/ }),

/***/ 73:
/***/ (function(module, exports, __webpack_require__) {

"use strict";
/**
 * Copyright (c) 2013-present, Facebook, Inc.
 *
 * This source code is licensed under the MIT license found in the
 * LICENSE file in the root directory of this source tree.
 */



var ReactPropTypesSecret = __webpack_require__(74);

function emptyFunction() {}
function emptyFunctionWithReset() {}
emptyFunctionWithReset.resetWarningCache = emptyFunction;

module.exports = function() {
  function shim(props, propName, componentName, location, propFullName, secret) {
    if (secret === ReactPropTypesSecret) {
      // It is still safe when called from React.
      return;
    }
    var err = new Error(
      'Calling PropTypes validators directly is not supported by the `prop-types` package. ' +
      'Use PropTypes.checkPropTypes() to call them. ' +
      'Read more at http://fb.me/use-check-prop-types'
    );
    err.name = 'Invariant Violation';
    throw err;
  };
  shim.isRequired = shim;
  function getShim() {
    return shim;
  };
  // Important!
  // Keep this list in sync with production version in `./factoryWithTypeCheckers.js`.
  var ReactPropTypes = {
    array: shim,
    bigint: shim,
    bool: shim,
    func: shim,
    number: shim,
    object: shim,
    string: shim,
    symbol: shim,

    any: shim,
    arrayOf: getShim,
    element: shim,
    elementType: shim,
    instanceOf: getShim,
    node: shim,
    objectOf: getShim,
    oneOf: getShim,
    oneOfType: getShim,
    shape: getShim,
    exact: getShim,

    checkPropTypes: emptyFunctionWithReset,
    resetWarningCache: emptyFunction
  };

  ReactPropTypes.PropTypes = ReactPropTypes;

  return ReactPropTypes;
};


/***/ }),

/***/ 74:
/***/ (function(module, exports, __webpack_require__) {

"use strict";
/**
 * Copyright (c) 2013-present, Facebook, Inc.
 *
 * This source code is licensed under the MIT license found in the
 * LICENSE file in the root directory of this source tree.
 */



var ReactPropTypesSecret = 'SECRET_DO_NOT_PASS_THIS_OR_YOU_WILL_BE_FIRED';

module.exports = ReactPropTypesSecret;


/***/ }),

/***/ 79:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";

// EXPORTS
__webpack_require__.d(__webpack_exports__, "a", function() { return /* binding */ interpolate; });

// EXTERNAL MODULE: external "React"
var external_React_ = __webpack_require__(6);

// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/@automattic+interpolate-components@1.2.0_react@17.0.2/node_modules/@automattic/interpolate-components/dist/esm/tokenize.js
function identifyToken(item) {
  // {{/example}}
  if (item.startsWith('{{/')) {
    return {
      type: 'componentClose',
      value: item.replace(/\W/g, '')
    };
  } // {{example /}}


  if (item.endsWith('/}}')) {
    return {
      type: 'componentSelfClosing',
      value: item.replace(/\W/g, '')
    };
  } // {{example}}


  if (item.startsWith('{{')) {
    return {
      type: 'componentOpen',
      value: item.replace(/\W/g, '')
    };
  }

  return {
    type: 'string',
    value: item
  };
}

function tokenize(mixedString) {
  const tokenStrings = mixedString.split(/(\{\{\/?\s*\w+\s*\/?\}\})/g); // split to components and strings

  return tokenStrings.map(identifyToken);
}
// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/@automattic+interpolate-components@1.2.0_react@17.0.2/node_modules/@automattic/interpolate-components/dist/esm/index.js



function getCloseIndex(openIndex, tokens) {
  const openToken = tokens[openIndex];
  let nestLevel = 0;

  for (let i = openIndex + 1; i < tokens.length; i++) {
    const token = tokens[i];

    if (token.value === openToken.value) {
      if (token.type === 'componentOpen') {
        nestLevel++;
        continue;
      }

      if (token.type === 'componentClose') {
        if (nestLevel === 0) {
          return i;
        }

        nestLevel--;
      }
    }
  } // if we get this far, there was no matching close token


  throw new Error('Missing closing component token `' + openToken.value + '`');
}

function buildChildren(tokens, components) {
  let children = [];
  let openComponent;
  let openIndex;

  for (let i = 0; i < tokens.length; i++) {
    const token = tokens[i];

    if (token.type === 'string') {
      children.push(token.value);
      continue;
    } // component node should at least be set


    if (components[token.value] === undefined) {
      throw new Error(`Invalid interpolation, missing component node: \`${token.value}\``);
    } // should be either ReactElement or null (both type "object"), all other types deprecated


    if (typeof components[token.value] !== 'object') {
      throw new Error(`Invalid interpolation, component node must be a ReactElement or null: \`${token.value}\``);
    } // we should never see a componentClose token in this loop


    if (token.type === 'componentClose') {
      throw new Error(`Missing opening component token: \`${token.value}\``);
    }

    if (token.type === 'componentOpen') {
      openComponent = components[token.value];
      openIndex = i;
      break;
    } // componentSelfClosing token


    children.push(components[token.value]);
    continue;
  }

  if (openComponent) {
    const closeIndex = getCloseIndex(openIndex, tokens);
    const grandChildTokens = tokens.slice(openIndex + 1, closeIndex);
    const grandChildren = buildChildren(grandChildTokens, components);
    const clonedOpenComponent = /*#__PURE__*/Object(external_React_["cloneElement"])(openComponent, {}, grandChildren);
    children.push(clonedOpenComponent);

    if (closeIndex < tokens.length - 1) {
      const siblingTokens = tokens.slice(closeIndex + 1);
      const siblings = buildChildren(siblingTokens, components);
      children = children.concat(siblings);
    }
  }

  children = children.filter(Boolean);

  if (children.length === 0) {
    return null;
  }

  if (children.length === 1) {
    return children[0];
  }

  return /*#__PURE__*/Object(external_React_["createElement"])(external_React_["Fragment"], null, ...children);
}

function interpolate(options) {
  const {
    mixedString,
    components,
    throwErrors
  } = options;

  if (!components) {
    return mixedString;
  }

  if (typeof components !== 'object') {
    if (throwErrors) {
      throw new Error(`Interpolation Error: unable to process \`${mixedString}\` because components is not an object`);
    }

    return mixedString;
  }

  const tokens = tokenize(mixedString);

  try {
    return buildChildren(tokens, components);
  } catch (error) {
    if (throwErrors) {
      throw new Error(`Interpolation Error: unable to process \`${mixedString}\` because of error \`${error.message}\``);
    }

    return mixedString;
  }
}

/***/ }),

/***/ 8:
/***/ (function(module, exports) {

(function() { module.exports = window["wp"]["data"]; }());

/***/ }),

/***/ 9:
/***/ (function(module, exports) {

(function() { module.exports = window["wp"]["primitives"]; }());

/***/ })

/******/ });