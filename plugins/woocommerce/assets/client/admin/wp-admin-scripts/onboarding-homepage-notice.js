this["wc"] = this["wc"] || {}; this["wc"]["onboardingHomepageNotice"] =
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
/******/ 	return __webpack_require__(__webpack_require__.s = 484);
/******/ })
/************************************************************************/
/******/ ({

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

/***/ 484:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(8);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(2);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_dom_ready__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(63);
/* harmony import */ var _wordpress_dom_ready__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_dom_ready__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _woocommerce_settings__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(15);
/* harmony import */ var _woocommerce_settings__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_settings__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _woocommerce_tracks__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(17);
/* harmony import */ var _woocommerce_tracks__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_tracks__WEBPACK_IMPORTED_MODULE_4__);
/**
 * External dependencies
 */





/**
 * Internal dependencies
 */

/**
 * Returns a promise and resolves when the post begins to publish.
 *
 * @return {Promise} Promise for overlay existence.
 */

const saveStarted = () => {
  if (!document.querySelector('.editor-post-publish-button').classList.contains('is-busy')) {
    const promise = new Promise(resolve => {
      window.requestAnimationFrame(resolve);
    });
    return promise.then(() => saveStarted());
  }

  return Promise.resolve(true);
};
/**
 * Returns a promise and resolves when the post has been saved and notices have shown.
 *
 * @return {Promise} Promise for overlay existence.
 */


const saveCompleted = () => {
  if (document.querySelector('.editor-post-publish-button').classList.contains('is-busy')) {
    const promise = new Promise(resolve => {
      window.requestAnimationFrame(resolve);
    });
    return promise.then(() => saveCompleted());
  }

  return Promise.resolve(true);
};
/**
 * Displays a notice on page save and updates the hompage options.
 */


const onboardingHomepageNotice = () => {
  const saveButton = document.querySelector('.editor-post-publish-button');

  if (saveButton.classList.contains('is-clicked')) {
    return;
  }

  saveButton.classList.add('is-clicked');
  saveCompleted().then(() => {
    const notificationType = document.querySelector('.components-snackbar__content') !== null ? 'snackbar' : 'default';
    Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_0__["dispatch"])('core/notices').removeNotice('SAVE_POST_NOTICE_ID');
    Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_0__["dispatch"])('core/notices').createSuccessNotice(Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])("🏠 Nice work creating your store's homepage!", 'woocommerce-admin'), {
      id: 'WOOCOMMERCE_ONBOARDING_HOME_PAGE_NOTICE',
      type: notificationType,
      actions: [{
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Continue setup.', 'woocommerce-admin'),
        onClick: () => {
          Object(_woocommerce_tracks__WEBPACK_IMPORTED_MODULE_4__["queueRecordEvent"])('tasklist_appearance_continue_setup', {});
          window.location = Object(_woocommerce_settings__WEBPACK_IMPORTED_MODULE_3__["getAdminLink"])('admin.php?page=wc-admin&task=appearance');
        }
      }]
    });
  });
};

_wordpress_dom_ready__WEBPACK_IMPORTED_MODULE_2___default()(() => {
  const publishButton = document.querySelector('.editor-post-publish-button');

  if (publishButton) {
    publishButton.addEventListener('click', saveStarted().then(() => onboardingHomepageNotice()));
  }
});

/***/ }),

/***/ 63:
/***/ (function(module, exports) {

(function() { module.exports = window["wp"]["domReady"]; }());

/***/ }),

/***/ 8:
/***/ (function(module, exports) {

(function() { module.exports = window["wp"]["data"]; }());

/***/ })

/******/ });