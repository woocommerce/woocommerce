this["wc"] = this["wc"] || {}; this["wc"]["betaFeaturesTrackingModal"] =
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
/******/ 	return __webpack_require__(__webpack_require__.s = 500);
/******/ })
/************************************************************************/
/******/ ({

/***/ 0:
/***/ (function(module, exports) {

(function() { module.exports = window["wp"]["element"]; }());

/***/ }),

/***/ 12:
/***/ (function(module, exports) {

(function() { module.exports = window["wc"]["data"]; }());

/***/ }),

/***/ 137:
/***/ (function(module, exports) {

(function() { module.exports = window["wc"]["explat"]; }());

/***/ }),

/***/ 14:
/***/ (function(module, exports) {

(function() { module.exports = window["wp"]["compose"]; }());

/***/ }),

/***/ 17:
/***/ (function(module, exports) {

(function() { module.exports = window["wc"]["tracks"]; }());

/***/ }),

/***/ 2:
/***/ (function(module, exports) {

(function() { module.exports = window["wp"]["i18n"]; }());

/***/ }),

/***/ 4:
/***/ (function(module, exports) {

(function() { module.exports = window["wp"]["components"]; }());

/***/ }),

/***/ 488:
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),

/***/ 500:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
// ESM COMPAT FLAG
__webpack_require__.r(__webpack_exports__);

// EXTERNAL MODULE: external ["wp","element"]
var external_wp_element_ = __webpack_require__(0);

// EXTERNAL MODULE: external ["wp","i18n"]
var external_wp_i18n_ = __webpack_require__(2);

// EXTERNAL MODULE: external ["wp","components"]
var external_wp_components_ = __webpack_require__(4);

// EXTERNAL MODULE: external ["wp","data"]
var external_wp_data_ = __webpack_require__(8);

// EXTERNAL MODULE: external ["wp","compose"]
var external_wp_compose_ = __webpack_require__(14);

// EXTERNAL MODULE: external ["wc","data"]
var external_wc_data_ = __webpack_require__(12);

// EXTERNAL MODULE: external ["wc","tracks"]
var external_wc_tracks_ = __webpack_require__(17);

// EXTERNAL MODULE: external ["wc","explat"]
var external_wc_explat_ = __webpack_require__(137);

// CONCATENATED MODULE: ./client/wp-admin-scripts/beta-features-tracking-modal/container.js


/**
 * External dependencies
 */









const BetaFeaturesTrackingModal = _ref => {
  let {
    updateOptions
  } = _ref;
  const [isModalOpen, setIsModalOpen] = Object(external_wp_element_["useState"])(false);
  const [isChecked, setIsChecked] = Object(external_wp_element_["useState"])(false);
  const enableNavigationCheckbox = Object(external_wp_element_["useRef"])(document.querySelector('#woocommerce_navigation_enabled'));

  const setTracking = async allow => {
    if (typeof window.wcTracks.enable === 'function') {
      if (allow) {
        window.wcTracks.enable(() => {
          Object(external_wc_explat_["initializeExPlat"])();
        });
      } else {
        window.wcTracks.isEnabled = false;
      }
    }

    if (allow) {
      Object(external_wc_tracks_["recordEvent"])('settings_features_tracking_enabled');
    }

    return updateOptions({
      woocommerce_allow_tracking: allow ? 'yes' : 'no'
    });
  };

  Object(external_wp_element_["useEffect"])(() => {
    if (!enableNavigationCheckbox.current) {
      return;
    }

    const listener = e => {
      if (e.target.checked) {
        e.target.checked = false;
        setIsModalOpen(true);
      }
    };

    const checkbox = enableNavigationCheckbox.current;
    checkbox.addEventListener('change', listener, false);
    return () => checkbox.removeEventListener('change', listener);
  }, []);

  if (!enableNavigationCheckbox.current) {
    return null;
  }

  if (!isModalOpen) {
    return null;
  }

  return Object(external_wp_element_["createElement"])(external_wp_components_["Modal"], {
    title: Object(external_wp_i18n_["__"])('Build a Better WooCommerce', 'woocommerce-admin'),
    onRequestClose: () => setIsModalOpen(false),
    className: "woocommerce-beta-features-tracking-modal"
  }, Object(external_wp_element_["createElement"])("p", null, Object(external_wp_i18n_["__"])('Testing new features requires sharing non-sensitive data via ', 'woocommerce-admin'), Object(external_wp_element_["createElement"])("a", {
    href: "https://woocommerce.com/usage-tracking?utm_medium=product"
  }, Object(external_wp_i18n_["__"])('usage tracking', 'woocommerce-admin')), Object(external_wp_i18n_["__"])('. Gathering usage data allows us to make WooCommerce better — your store will be considered as we evaluate new features, judge the quality of an update, or determine if an improvement makes sense. No personal data is tracked or stored and you can opt-out at any time.', 'woocommerce-admin')), Object(external_wp_element_["createElement"])("div", {
    className: "woocommerce-beta-features-tracking-modal__checkbox"
  }, Object(external_wp_element_["createElement"])(external_wp_components_["CheckboxControl"], {
    label: "Enable usage tracking",
    onChange: setIsChecked,
    checked: isChecked
  })), Object(external_wp_element_["createElement"])("div", {
    className: "woocommerce-beta-features-tracking-modal__actions"
  }, Object(external_wp_element_["createElement"])(external_wp_components_["Button"], {
    isPrimary: true,
    onClick: async () => {
      if (isChecked) {
        await setTracking(true);
        enableNavigationCheckbox.current.checked = true;
      } else {
        await setTracking(false);
      }

      setIsModalOpen(false);
    }
  }, Object(external_wp_i18n_["__"])('Save', 'woocommerce-admin'))));
};

const BetaFeaturesTrackingContainer = Object(external_wp_compose_["compose"])(Object(external_wp_data_["withDispatch"])(dispatch => {
  const {
    updateOptions
  } = dispatch(external_wc_data_["OPTIONS_STORE_NAME"]);
  return {
    updateOptions
  };
}))(BetaFeaturesTrackingModal);
// EXTERNAL MODULE: ./client/wp-admin-scripts/beta-features-tracking-modal/style.scss
var style = __webpack_require__(488);

// CONCATENATED MODULE: ./client/wp-admin-scripts/beta-features-tracking-modal/index.js


/**
 * External dependencies
 */

/**
 * Internal dependencies
 */



const betaFeaturesRoot = document.createElement('div');
betaFeaturesRoot.setAttribute('id', 'beta-features-tracking');
Object(external_wp_element_["render"])(Object(external_wp_element_["createElement"])(BetaFeaturesTrackingContainer, null), document.body.appendChild(betaFeaturesRoot));

/***/ }),

/***/ 8:
/***/ (function(module, exports) {

(function() { module.exports = window["wp"]["data"]; }());

/***/ })

/******/ });