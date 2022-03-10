this["wc"] = this["wc"] || {}; this["wc"]["navigationOptOut"] =
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
/******/ 	return __webpack_require__(__webpack_require__.s = 499);
/******/ })
/************************************************************************/
/******/ ({

/***/ 0:
/***/ (function(module, exports) {

(function() { module.exports = window["wp"]["element"]; }());

/***/ }),

/***/ 2:
/***/ (function(module, exports) {

(function() { module.exports = window["wp"]["i18n"]; }());

/***/ }),

/***/ 4:
/***/ (function(module, exports) {

(function() { module.exports = window["wp"]["components"]; }());

/***/ }),

/***/ 483:
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),

/***/ 499:
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

// CONCATENATED MODULE: ./client/wp-admin-scripts/navigation-opt-out/container.js


/**
 * External dependencies
 */



class container_NavigationOptOutContainer extends external_wp_element_["Component"] {
  constructor(props) {
    super(props);
    this.state = {
      isModalOpen: true
    };
  }

  render() {
    const {
      isModalOpen
    } = this.state;

    if (!isModalOpen) {
      return null;
    }

    if (!window.surveyData || !window.surveyData.url) {
      return null;
    }

    return Object(external_wp_element_["createElement"])(external_wp_components_["Modal"], {
      title: Object(external_wp_i18n_["__"])('Help us improve', 'woocommerce-admin'),
      onRequestClose: () => this.setState({
        isModalOpen: false
      }),
      className: "woocommerce-navigation-opt-out-modal"
    }, Object(external_wp_element_["createElement"])("p", null, Object(external_wp_i18n_["__"])("Take this 2-minute survey to share why you're opting out of the new navigation", 'woocommerce-admin')), Object(external_wp_element_["createElement"])("div", {
      className: "woocommerce-navigation-opt-out-modal__actions"
    }, Object(external_wp_element_["createElement"])(external_wp_components_["Button"], {
      isDefault: true,
      onClick: () => this.setState({
        isModalOpen: false
      })
    }, Object(external_wp_i18n_["__"])('No thanks', 'woocommerce-admin')), Object(external_wp_element_["createElement"])(external_wp_components_["Button"], {
      isPrimary: true,
      target: "_blank",
      href: window.surveyData.url,
      onClick: () => this.setState({
        isModalOpen: false
      })
    }, Object(external_wp_i18n_["__"])('Share feedback', 'woocommerce-admin'))));
  }

}
// EXTERNAL MODULE: ./client/wp-admin-scripts/navigation-opt-out/style.scss
var style = __webpack_require__(483);

// CONCATENATED MODULE: ./client/wp-admin-scripts/navigation-opt-out/index.js


/**
 * External dependencies
 */

/**
 * Internal dependencies
 */



const navigationOptOutRoot = document.createElement('div');
navigationOptOutRoot.setAttribute('id', 'navigation-opt-out-root');
Object(external_wp_element_["render"])(Object(external_wp_element_["createElement"])(container_NavigationOptOutContainer, null), document.body.appendChild(navigationOptOutRoot));

/***/ })

/******/ });