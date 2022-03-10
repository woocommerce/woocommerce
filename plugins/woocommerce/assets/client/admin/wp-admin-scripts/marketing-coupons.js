this["wc"] = this["wc"] || {}; this["wc"]["marketingCoupons"] =
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
/******/ 	return __webpack_require__(__webpack_require__.s = 498);
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

/***/ 10:
/***/ (function(module, exports) {

(function() { module.exports = window["wp"]["dataControls"]; }());

/***/ }),

/***/ 100:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(6);
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);

/* harmony default export */ __webpack_exports__["a"] = (react__WEBPACK_IMPORTED_MODULE_0___default.a.createContext(null));

/***/ }),

/***/ 114:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";

// EXPORTS
__webpack_require__.d(__webpack_exports__, "a", function() { return /* reexport */ components_button; });
__webpack_require__.d(__webpack_exports__, "b", function() { return /* reexport */ product_icon; });
__webpack_require__.d(__webpack_exports__, "c", function() { return /* reexport */ slider; });

// UNUSED EXPORTS: Card

// NAMESPACE OBJECT: ./client/marketing/components/product-icon/icons/index.js
var icons_namespaceObject = {};
__webpack_require__.r(icons_namespaceObject);
__webpack_require__.d(icons_namespaceObject, "blank", function() { return library_blank; });
__webpack_require__.d(icons_namespaceObject, "amazonEbayIntegration", function() { return amazon_ebay; });
__webpack_require__.d(icons_namespaceObject, "woocommerceAmazonEbayIntegration", function() { return amazon_ebay; });
__webpack_require__.d(icons_namespaceObject, "automatewoo", function() { return library_automatewoo; });
__webpack_require__.d(icons_namespaceObject, "automatewooAlt", function() { return automatewoo_alt; });
__webpack_require__.d(icons_namespaceObject, "facebook", function() { return library_facebook; });
__webpack_require__.d(icons_namespaceObject, "facebookForWoocommerce", function() { return library_facebook; });
__webpack_require__.d(icons_namespaceObject, "pinterest", function() { return library_pinterest; });
__webpack_require__.d(icons_namespaceObject, "pinterestForWoocommerce", function() { return library_pinterest; });
__webpack_require__.d(icons_namespaceObject, "googleAds", function() { return library_google; });
__webpack_require__.d(icons_namespaceObject, "googleListingsAndAds", function() { return library_google; });
__webpack_require__.d(icons_namespaceObject, "hubspotForWoocommerce", function() { return library_hubspot; });
__webpack_require__.d(icons_namespaceObject, "mailchimpForWoocommerce", function() { return library_mailchimp; });
__webpack_require__.d(icons_namespaceObject, "mailpoet", function() { return library_mailpoet; });
__webpack_require__.d(icons_namespaceObject, "woocommerceStoreCredit", function() { return currency_dollar; });
__webpack_require__.d(icons_namespaceObject, "woocommerceFreeGiftCoupons", function() { return library_gift; });
__webpack_require__.d(icons_namespaceObject, "woocommerceUrlCoupons", function() { return library_link; });
__webpack_require__.d(icons_namespaceObject, "woocommerceGroupCoupons", function() { return library_people; });
__webpack_require__.d(icons_namespaceObject, "woocommerceSmartCoupons", function() { return library_tip; });

// EXTERNAL MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/@babel+runtime@7.17.2/node_modules/@babel/runtime/helpers/extends.js
var helpers_extends = __webpack_require__(40);
var extends_default = /*#__PURE__*/__webpack_require__.n(helpers_extends);

// EXTERNAL MODULE: external ["wp","element"]
var external_wp_element_ = __webpack_require__(0);

// EXTERNAL MODULE: external ["wp","components"]
var external_wp_components_ = __webpack_require__(4);

// EXTERNAL MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/classnames@2.3.1/node_modules/classnames/index.js
var classnames = __webpack_require__(7);
var classnames_default = /*#__PURE__*/__webpack_require__.n(classnames);

// EXTERNAL MODULE: ./client/marketing/components/button/style.scss
var button_style = __webpack_require__(301);

// CONCATENATED MODULE: ./client/marketing/components/button/index.js



/**
 * External dependencies
 */


/**
 * Internal dependencies
 */


/* harmony default export */ var components_button = (props => {
  return Object(external_wp_element_["createElement"])(external_wp_components_["Button"], extends_default()({}, props, {
    className: classnames_default()(props.className, 'woocommerce-admin-marketing-button')
  }));
});
// EXTERNAL MODULE: ./client/marketing/components/card/index.js
var card = __webpack_require__(91);

// EXTERNAL MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/prop-types@15.8.1/node_modules/prop-types/index.js
var prop_types = __webpack_require__(1);
var prop_types_default = /*#__PURE__*/__webpack_require__.n(prop_types);

// EXTERNAL MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/@wordpress+icons@6.3.0/node_modules/@wordpress/icons/build-module/icon/index.js
var icon = __webpack_require__(116);

// EXTERNAL MODULE: external "lodash"
var external_lodash_ = __webpack_require__(5);

// EXTERNAL MODULE: ./client/marketing/components/product-icon/style.scss
var product_icon_style = __webpack_require__(303);

// EXTERNAL MODULE: external ["wp","primitives"]
var external_wp_primitives_ = __webpack_require__(9);

// CONCATENATED MODULE: ./client/marketing/components/product-icon/icons/library/blank.js


/**
 * External dependencies
 */

const blank = Object(external_wp_element_["createElement"])(external_wp_primitives_["SVG"], {
  width: "36",
  height: "36",
  fill: "none",
  xmlns: "http://www.w3.org/2000/svg"
});
/* harmony default export */ var library_blank = (blank);
// CONCATENATED MODULE: ./client/marketing/components/product-icon/icons/library/amazon-ebay.js


/**
 * External dependencies
 */

const amazonEbay = Object(external_wp_element_["createElement"])(external_wp_primitives_["SVG"], {
  xmlns: "http://www.w3.org/2000/svg",
  width: "100",
  height: "100",
  viewBox: "0 0 100 100"
}, Object(external_wp_element_["createElement"])("defs", null, Object(external_wp_element_["createElement"])("clipPath", {
  id: "b"
}, Object(external_wp_element_["createElement"])("rect", {
  width: "100",
  height: "100"
}))), Object(external_wp_element_["createElement"])("g", {
  id: "a",
  clipPath: "url(#b)"
}, Object(external_wp_element_["createElement"])("rect", {
  width: "100",
  height: "100",
  fill: "#fff"
}), Object(external_wp_element_["createElement"])("rect", {
  width: "100",
  height: "100",
  fill: "#eee"
}), Object(external_wp_element_["createElement"])("g", {
  transform: "translate(9 25.655)"
}, Object(external_wp_element_["createElement"])(external_wp_primitives_["Path"], {
  d: "M179.753,195.8c-4.732,3.488-11.591,5.349-17.5,5.349a31.66,31.66,0,0,1-21.374-8.156c-.443-.4-.046-.946.486-.634a43.018,43.018,0,0,0,21.384,5.671,42.523,42.523,0,0,0,16.312-3.335c.8-.34,1.471.525.688,1.106",
  transform: "translate(-129.235 -176.611)",
  fill: "#f90",
  fillRule: "evenodd"
}), Object(external_wp_element_["createElement"])(external_wp_primitives_["Path"], {
  d: "M577.807,183.949c-.6-.773-4-.365-5.522-.184-.464.057-.535-.347-.117-.638,2.7-1.9,7.142-1.354,7.66-.716s-.135,5.09-2.676,7.213c-.39.326-.762.152-.588-.28.571-1.425,1.85-4.619,1.244-5.395",
  transform: "translate(-525.323 -167.01)",
  fill: "#f90",
  fillRule: "evenodd"
}), Object(external_wp_element_["createElement"])(external_wp_primitives_["Path"], {
  d: "M572.708,6.758V4.908a.457.457,0,0,1,.468-.468h8.284a.461.461,0,0,1,.479.468V6.493a2.605,2.605,0,0,1-.624,1.163l-4.292,6.129a9.146,9.146,0,0,1,4.725,1.014.843.843,0,0,1,.44.72v1.974c0,.269-.3.585-.61.422a9.542,9.542,0,0,0-8.752.014c-.287.156-.588-.156-.588-.425V15.627a2.238,2.238,0,0,1,.3-1.272l4.973-7.132h-4.328a.458.458,0,0,1-.479-.464",
  transform: "translate(-525.64 -4.078)",
  fillRule: "evenodd"
}), Object(external_wp_element_["createElement"])(external_wp_primitives_["Path"], {
  d: "M173.431,15.624h-2.52a.476.476,0,0,1-.45-.429V2.261a.473.473,0,0,1,.486-.464h2.35a.475.475,0,0,1,.457.432V3.92h.046a3.463,3.463,0,0,1,6.589,0,3.722,3.722,0,0,1,6.4-.982c.8,1.088.634,2.669.634,4.055l0,8.163a.476.476,0,0,1-.486.468h-2.517a.479.479,0,0,1-.454-.468V8.3a16.192,16.192,0,0,0-.071-2.424,1.312,1.312,0,0,0-1.482-1.113,1.674,1.674,0,0,0-1.506,1.06,7.831,7.831,0,0,0-.234,2.478v6.855a.476.476,0,0,1-.486.468h-2.517a.476.476,0,0,1-.454-.468l0-6.855c0-1.443.238-3.566-1.553-3.566-1.811,0-1.74,2.07-1.74,3.566v6.855a.476.476,0,0,1-.486.468",
  transform: "translate(-156.58 -1.399)",
  fillRule: "evenodd"
}), Object(external_wp_element_["createElement"])(external_wp_primitives_["Path"], {
  d: "M714.982,1.524c3.739,0,5.763,3.211,5.763,7.295,0,3.945-2.237,7.075-5.763,7.075-3.672,0-5.671-3.211-5.671-7.213,0-4.027,2.024-7.156,5.671-7.156M715,4.164c-1.857,0-1.974,2.531-1.974,4.108s-.025,4.955,1.953,4.955c1.953,0,2.045-2.722,2.045-4.381a11.959,11.959,0,0,0-.376-3.431A1.577,1.577,0,0,0,715,4.164",
  transform: "translate(-651.552 -1.399)",
  fillRule: "evenodd"
}), Object(external_wp_element_["createElement"])(external_wp_primitives_["Path"], {
  d: "M875.817,15.624h-2.51a.479.479,0,0,1-.454-.468l0-12.938a.477.477,0,0,1,.486-.422h2.336a.482.482,0,0,1,.45.362V4.136h.046c.705-1.769,1.694-2.612,3.435-2.612a3.307,3.307,0,0,1,2.942,1.524c.659,1.035.659,2.775.659,4.027v8.142a.484.484,0,0,1-.486.408h-2.527a.477.477,0,0,1-.447-.408V8.191c0-1.414.163-3.484-1.577-3.484a1.647,1.647,0,0,0-1.457,1.035,5.724,5.724,0,0,0-.4,2.449v6.965a.485.485,0,0,1-.493.468",
  transform: "translate(-801.775 -1.399)",
  fillRule: "evenodd"
}), Object(external_wp_element_["createElement"])(external_wp_primitives_["Path"], {
  d: "M413.163,8.046a4.93,4.93,0,0,1-.471,2.673,2.048,2.048,0,0,1-1.744,1.145c-.968,0-1.535-.737-1.535-1.825,0-2.148,1.925-2.538,3.75-2.538v.546m2.541,6.143a.526.526,0,0,1-.6.06,6.143,6.143,0,0,1-1.446-1.68,4.991,4.991,0,0,1-4.154,1.833,3.575,3.575,0,0,1-3.771-3.927,4.277,4.277,0,0,1,2.687-4.119,17.463,17.463,0,0,1,4.739-.876V5.154a3.214,3.214,0,0,0-.308-1.825,1.677,1.677,0,0,0-1.414-.656,1.917,1.917,0,0,0-2.024,1.514.527.527,0,0,1-.439.461l-2.442-.262a.444.444,0,0,1-.376-.528C406.719.893,409.4,0,411.795,0a5.714,5.714,0,0,1,3.8,1.255C416.818,2.4,416.7,3.928,416.7,5.59V9.517a3.447,3.447,0,0,0,.95,2.336.477.477,0,0,1-.011.67c-.514.429-1.428,1.226-1.932,1.673l0-.007",
  transform: "translate(-372.698 0)",
  fillRule: "evenodd"
}), Object(external_wp_element_["createElement"])(external_wp_primitives_["Path"], {
  d: "M7.426,8.046a4.93,4.93,0,0,1-.471,2.673,2.043,2.043,0,0,1-1.744,1.145c-.968,0-1.531-.737-1.531-1.825C3.679,7.89,5.6,7.5,7.426,7.5v.546m2.541,6.143a.526.526,0,0,1-.6.06,6.2,6.2,0,0,1-1.446-1.68A4.986,4.986,0,0,1,3.771,14.4,3.576,3.576,0,0,1,0,10.474,4.282,4.282,0,0,1,2.687,6.356,17.462,17.462,0,0,1,7.426,5.48V5.154a3.243,3.243,0,0,0-.3-1.825,1.686,1.686,0,0,0-1.414-.656A1.921,1.921,0,0,0,3.679,4.186a.527.527,0,0,1-.436.461L.8,4.385a.446.446,0,0,1-.376-.528C.985.893,3.662,0,6.058,0a5.714,5.714,0,0,1,3.8,1.255C11.08,2.4,10.963,3.928,10.963,5.59V9.517a3.447,3.447,0,0,0,.95,2.336.473.473,0,0,1-.007.67c-.514.429-1.428,1.226-1.932,1.673l-.007-.007",
  transform: "translate(0 0)",
  fillRule: "evenodd"
})), Object(external_wp_element_["createElement"])("g", {
  transform: "translate(18.9 54.637)"
}, Object(external_wp_element_["createElement"])(external_wp_primitives_["Path"], {
  d: "M8.055,26.308C3.716,26.308.1,28.149.1,33.7c0,4.4,2.431,7.171,8.067,7.171,6.633,0,7.059-4.37,7.059-4.37H12.011s-.689,2.353-4.04,2.353a4.4,4.4,0,0,1-4.693-4.428H15.562V32.807c0-2.557-1.623-6.5-7.507-6.5Zm-.112,2.073c2.6,0,4.37,1.592,4.37,3.977H3.349C3.349,29.826,5.661,28.381,7.943,28.381Z",
  transform: "translate(0 -20.83)",
  fill: "#e53238"
}), Object(external_wp_element_["createElement"])(external_wp_primitives_["Path"], {
  d: "M75.169.1V17.254c0,.974-.069,2.341-.069,2.341h3.066s.11-.982.11-1.879c0,0,1.515,2.37,5.633,2.37a6.961,6.961,0,0,0,7.283-7.325A6.922,6.922,0,0,0,83.915,5.52c-4.279,0-5.609,2.311-5.609,2.311V.1Zm7.955,7.542c2.945,0,4.818,2.186,4.818,5.119a4.857,4.857,0,0,1-4.8,5.2c-3.143,0-4.839-2.454-4.839-5.175C78.306,10.254,79.827,7.642,83.123,7.642Z",
  transform: "translate(-59.609)",
  fill: "#0064d2"
}), Object(external_wp_element_["createElement"])(external_wp_primitives_["Path"], {
  d: "M159.834,26.308c-6.528,0-6.947,3.574-6.947,4.146h3.249s.17-2.087,3.473-2.087c2.146,0,3.809.982,3.809,2.871v.672h-3.809c-5.057,0-7.731,1.479-7.731,4.482,0,2.955,2.47,4.562,5.809,4.562,4.55,0,6.015-2.514,6.015-2.514,0,1,.077,1.985.077,1.985h2.888s-.112-1.221-.112-2V31.669c0-4.428-3.572-5.36-6.722-5.36Zm3.585,7.619v.9c0,1.169-.721,4.075-4.968,4.075-2.326,0-3.323-1.161-3.323-2.507C155.128,33.943,158.486,33.927,163.419,33.927Z",
  transform: "translate(-120.634 -20.83)",
  fill: "#f5af02"
}), Object(external_wp_element_["createElement"])(external_wp_primitives_["Path"], {
  d: "M214.879,29.041h3.655l5.246,10.51,5.234-10.51h3.311l-9.533,18.711h-3.473l2.751-5.216Z",
  transform: "translate(-170.706 -23.002)",
  fill: "#86b817"
}))));
/* harmony default export */ var amazon_ebay = (amazonEbay);
// CONCATENATED MODULE: ./client/marketing/components/product-icon/icons/library/automatewoo.js


/**
 * External dependencies
 */

const automatewoo = Object(external_wp_element_["createElement"])(external_wp_primitives_["SVG"], {
  xmlns: "http://www.w3.org/2000/svg",
  width: "100",
  height: "100",
  viewBox: "0 0 100 100"
}, Object(external_wp_element_["createElement"])("defs", null, Object(external_wp_element_["createElement"])("clipPath", {
  id: "b"
}, Object(external_wp_element_["createElement"])("rect", {
  width: "100",
  height: "100"
}))), Object(external_wp_element_["createElement"])("g", {
  id: "a",
  clipPath: "url(#b)"
}, Object(external_wp_element_["createElement"])("rect", {
  width: "100",
  height: "100",
  fill: "#fff"
}), Object(external_wp_element_["createElement"])("rect", {
  width: "100",
  height: "100",
  fill: "#7532e4"
}), Object(external_wp_element_["createElement"])("g", {
  transform: "translate(-43.503 -133.512)"
}, Object(external_wp_element_["createElement"])(external_wp_primitives_["Path"], {
  d: "M78.217,193.13H64.405l-2.823,7.764H54.6L67.648,166.9h7.669l12.934,33.995H81.059Zm-11.6-6.047h9.4L71.33,174.245Z",
  transform: "translate(0 0)",
  fill: "#1ff2e6"
}), Object(external_wp_element_["createElement"])(external_wp_primitives_["Path"], {
  d: "M246.639,166.9h6.753l-9.4,33.995h-6.81l-7.764-24.208-7.764,24.208h-6.906L205.3,166.9h7l6.238,23.388,7.535-23.388h6.849l7.592,23.483Z",
  transform: "translate(-121.952)",
  fill: "#1ff2e6"
}))));
/* harmony default export */ var library_automatewoo = (automatewoo);
// CONCATENATED MODULE: ./client/marketing/components/product-icon/icons/library/automatewoo-alt.js


/**
 * External dependencies
 */

const automatewooAlt = Object(external_wp_element_["createElement"])(external_wp_primitives_["SVG"], {
  xmlns: "http://www.w3.org/2000/svg"
}, Object(external_wp_element_["createElement"])(external_wp_primitives_["Path"], {
  fillRule: "evenodd",
  clipRule: "evenodd",
  d: "M4.67708 14.1615h3.77084l.77604 2.1198h1.96354L7.65625 7H5.5625L2 16.2813h1.90625l.77083-2.1198zm3.17188-1.6511H5.28125l1.28646-3.50519 1.28125 3.50519zM22.9791 7h-1.8437l-1.6719 6.4115L17.3906 7h-1.8698l-2.0573 6.3854L11.7604 7H9.8489l2.5781 9.2813h1.8854l2.1198-6.60942 2.1198 6.60942h1.8594L22.9791 7z"
}));
/* harmony default export */ var automatewoo_alt = (automatewooAlt);
// CONCATENATED MODULE: ./client/marketing/components/product-icon/icons/library/facebook.js


/**
 * External dependencies
 */

const facebook = Object(external_wp_element_["createElement"])(external_wp_primitives_["SVG"], {
  width: "36",
  height: "36",
  viewBox: "0 0 36 36",
  fill: "none",
  xmlns: "http://www.w3.org/2000/svg"
}, Object(external_wp_element_["createElement"])(external_wp_primitives_["Path"], {
  fillRule: "evenodd",
  clipRule: "evenodd",
  d: "M34 0H2C0.8 0 0 0.8 0 2V34C0 35 0.8 36 2 36H19.2V22H14.6V16.6H19.2V12.6C19.2 8 22 5.4 26.2 5.4C28.2 5.4 29.8 5.6 30.4 5.6V10.4H27.6C25.4 10.4 25 11.4 25 13V16.4H30.4L29.6 22H25V36H34C35 36 36 35.2 36 34V2C36 0.8 35.2 0 34 0Z",
  fill: "#3B5997"
}));
/* harmony default export */ var library_facebook = (facebook);
// CONCATENATED MODULE: ./client/marketing/components/product-icon/icons/library/pinterest.js


/**
 * External dependencies
 */

const pinterest = Object(external_wp_element_["createElement"])(external_wp_primitives_["SVG"], {
  width: "303",
  height: "303",
  viewBox: "-30 -30 303 303",
  fill: "none",
  xmlns: "http://www.w3.org/2000/SVG"
}, Object(external_wp_element_["createElement"])(external_wp_primitives_["Path"], {
  fill: "#E60023",
  d: "M121.5,0C54.4,0,0,54.4,0,121.5C0,173,32,217,77.2,234.7c-1.1-9.6-2-24.4,0.4-34.9 c2.2-9.5,14.2-60.4,14.2-60.4s-3.6-7.3-3.6-18c0-16.9,9.8-29.5,22-29.5c10.4,0,15.4,7.8,15.4,17.1c0,10.4-6.6,26-10.1,40.5 c-2.9,12.1,6.1,22,18,22c21.6,0,38.2-22.8,38.2-55.6c0-29.1-20.9-49.4-50.8-49.4C86.3,66.5,66,92.4,66,119.2c0,10.4,4,21.6,9,27.7 c1,1.2,1.1,2.3,0.8,3.5c-0.9,3.8-3,12.1-3.4,13.8c-0.5,2.2-1.8,2.7-4.1,1.6c-15.2-7.1-24.7-29.2-24.7-47.1 c0-38.3,27.8-73.5,80.3-73.5c42.1,0,74.9,30,74.9,70.2c0,41.9-26.4,75.6-63,75.6c-12.3,0-23.9-6.4-27.8-14c0,0-6.1,23.2-7.6,28.9 c-2.7,10.6-10.1,23.8-15.1,31.9c11.4,3.5,23.4,5.4,36,5.4c67.1,0,121.5-54.4,121.5-121.5C243,54.4,188.6,0,121.5,0z"
}));
/* harmony default export */ var library_pinterest = (pinterest);
// CONCATENATED MODULE: ./client/marketing/components/product-icon/icons/library/google.js


/**
 * External dependencies
 */

const google = Object(external_wp_element_["createElement"])(external_wp_primitives_["SVG"], {
  xmlns: "http://www.w3.org/2000/svg",
  width: "128",
  height: "128",
  viewBox: "0 0 128 128"
}, Object(external_wp_element_["createElement"])("rect", {
  width: "128",
  height: "128",
  fill: "#eee"
}), Object(external_wp_element_["createElement"])(external_wp_primitives_["Path"], {
  d: "M92.4539 42.3419c3.7531 0 6.7961-3.0427 6.7961-6.7959 0-3.7531-3.043-6.7958-6.7961-6.7958s-6.7958 3.0427-6.7958 6.7958c0 3.7532 3.0427 6.7959 6.7958 6.7959zm-48.1438-2.0744l20.848-20.8481c1.5904-1.5903 3.7989-2.562 6.2285-2.562h30.9214c1.161-.0041 2.312.2217 3.386.6642 1.073.4426 2.049 1.0932 2.87 1.9143.821.8212 1.472 1.7967 1.914 2.8704.443 1.0737.669 2.2244.665 3.3857v30.9213c0 2.4297-.972 4.6384-2.607 6.2285L87.7202 83.678 44.3101 40.2675z",
  fill: "#4285F4"
}), Object(external_wp_element_["createElement"])(external_wp_primitives_["Path"], {
  d: "M87.7202 83.678l-25.6915 25.716c-1.6346 1.59-3.8431 2.606-6.2726 2.606-2.4294 0-4.6383-1.016-6.2285-2.606L18.6061 78.4725C16.9717 76.8821 16 74.6736 16 72.244c0-2.4737 1.0159-4.6824 2.6061-6.2726L44.31 40.2675 87.7202 83.678z",
  fill: "#34A853"
}), Object(external_wp_element_["createElement"])(external_wp_primitives_["Path"], {
  d: "M33.6115 93.4777L18.6061 78.4723C16.9717 76.8825 16 74.6736 16 72.2442c0-2.4737 1.0159-4.6824 2.6061-6.2726L44.31 40.2677l21.2557 21.256-31.9542 31.954z",
  fill: "#FBBC05"
}), Object(external_wp_element_["createElement"])(external_wp_primitives_["Path"], {
  d: "M108.092 18.9973c-1.607-1.3873-3.661-2.1473-5.784-2.1399H71.3866c-2.4296 0-4.6381.9717-6.2285 2.562l-20.848 20.8481 21.2556 21.256 21.649-21.649c-1.0082-1.2168-1.5589-2.7482-1.5565-4.3285 0-3.7531 3.0426-6.7958 6.7957-6.7958 1.5804-.0025 3.1118.5482 4.3287 1.5565l11.3094-11.3094z",
  fill: "#EA4335"
}), Object(external_wp_element_["createElement"])(external_wp_primitives_["Path"], {
  d: "M65.5535 77.7372c7.6238 0 13.8041-6.1803 13.8041-13.8041S73.1773 50.129 65.5535 50.129s-13.8041 6.1803-13.8041 13.8041 6.1803 13.8041 13.8041 13.8041z",
  fill: "#4285F4"
}), Object(external_wp_element_["createElement"])(external_wp_primitives_["Path"], {
  d: "M84.3608 59.8724H66.0013v7.877h10.568c-.9853 5.0043-5.1048 7.8771-10.568 7.8771-6.4483 0-11.6427-5.3749-11.6427-12.0473s5.1944-12.0473 11.6427-12.0473c2.7764 0 5.284 1.0194 7.2543 2.6875l5.7318-5.9311c-3.4928-3.1508-7.9708-5.0969-12.9861-5.0969-10.9261 0-19.7029 9.0819-19.7029 20.3878S55.0752 83.967 66.0013 83.967c9.8514 0 18.8073-7.4138 18.8073-20.3878 0-1.2047-.1791-2.5022-.4478-3.7068z",
  fill: "#fff"
}));
/* harmony default export */ var library_google = (google);
// CONCATENATED MODULE: ./client/marketing/components/product-icon/icons/library/hubspot.js


/**
 * External dependencies
 */

const hubspot = Object(external_wp_element_["createElement"])(external_wp_primitives_["SVG"], {
  xmlns: "http://www.w3.org/2000/svg",
  width: "100",
  height: "100",
  viewBox: "0 0 100 100"
}, Object(external_wp_element_["createElement"])("defs", null, Object(external_wp_element_["createElement"])("clipPath", {
  id: "b"
}, Object(external_wp_element_["createElement"])("rect", {
  width: "100",
  height: "100"
}))), Object(external_wp_element_["createElement"])("g", {
  id: "a",
  clipPath: "url(#b)"
}, Object(external_wp_element_["createElement"])("rect", {
  width: "100",
  height: "100",
  fill: "#fff"
}), Object(external_wp_element_["createElement"])(external_wp_primitives_["Path"], {
  d: "M100,100H0V0H100V100ZM40.665,75.539a6.446,6.446,0,1,0,6.447,6.447,6.376,6.376,0,0,0-.3-1.843L54.158,72.8A19.808,19.808,0,1,0,69.206,37.48h.015V28.455a6.959,6.959,0,0,0,4.013-6.273v-.211a6.971,6.971,0,0,0-6.952-6.953H66.07a6.97,6.97,0,0,0-6.952,6.953v.211a6.957,6.957,0,0,0,4.013,6.273V37.5a19.745,19.745,0,0,0-9.376,4.126L28.935,22.295a7.919,7.919,0,0,0-4.148-9.145,7.845,7.845,0,0,0-3.5-.817,7.919,7.919,0,1,0,3.938,14.786l24.4,19a19.775,19.775,0,0,0,.3,22.3l-7.426,7.427A6.362,6.362,0,0,0,40.665,75.539Zm25.522-8.321h0l-.023,0a10.164,10.164,0,0,1,.023-20.328H66.2a10.166,10.166,0,0,1-.012,20.333Z",
  fill: "#ff7a59"
})));
/* harmony default export */ var library_hubspot = (hubspot);
// CONCATENATED MODULE: ./client/marketing/components/product-icon/icons/library/mailchimp.js


/**
 * External dependencies
 */

const mailchimp = Object(external_wp_element_["createElement"])(external_wp_primitives_["SVG"], {
  width: "36",
  height: "36",
  viewBox: "0 0 36 36",
  fill: "none",
  xmlns: "http://www.w3.org/2000/svg"
}, Object(external_wp_element_["createElement"])("rect", {
  width: "36",
  height: "36",
  rx: "3",
  fill: "#FFE071"
}), Object(external_wp_element_["createElement"])(external_wp_primitives_["Path"], {
  d: "M24.0534 17.2863C24.2392 17.2638 24.4176 17.2625 24.5813 17.2863C24.6764 17.0647 24.6923 16.6823 24.6071 16.2661C24.4808 15.6471 24.3091 15.2728 23.9546 15.331C23.6002 15.3892 23.5873 15.8374 23.7143 16.4564C23.7848 16.8043 23.9117 17.1023 24.0534 17.2863Z",
  fill: "black"
}), Object(external_wp_element_["createElement"])(external_wp_primitives_["Path"], {
  d: "M21.0119 17.7757C21.2652 17.8889 21.4209 17.9647 21.4823 17.899C21.5215 17.8576 21.5099 17.7794 21.4491 17.6786C21.3241 17.4702 21.0665 17.2587 20.7937 17.1404C20.2357 16.895 19.5697 16.9764 19.0559 17.3532C18.886 17.4802 18.7254 17.6555 18.7487 17.7625C18.756 17.7969 18.7812 17.8232 18.8413 17.8314C18.9811 17.8476 19.4698 17.5954 20.0321 17.5603C20.4294 17.5353 20.7587 17.6624 21.0119 17.7757Z",
  fill: "black"
}), Object(external_wp_element_["createElement"])(external_wp_primitives_["Path"], {
  d: "M20.5024 18.073C20.1725 18.1262 19.9904 18.237 19.8733 18.3409C19.7733 18.4298 19.712 18.5281 19.7126 18.5975C19.7126 18.6307 19.7267 18.6495 19.7378 18.6589C19.7531 18.6726 19.7709 18.6802 19.7923 18.6802C19.8671 18.6802 20.0339 18.6119 20.0339 18.6119C20.4932 18.4442 20.7961 18.4642 21.0966 18.4993C21.2627 18.518 21.3406 18.5287 21.3774 18.4705C21.3884 18.4536 21.4013 18.4179 21.3682 18.3628C21.2903 18.2339 20.9568 18.0179 20.5024 18.073Z",
  fill: "black"
}), Object(external_wp_element_["createElement"])(external_wp_primitives_["Path"], {
  d: "M23.0263 19.1626C23.2501 19.2753 23.4972 19.2309 23.5775 19.0644C23.6578 18.8973 23.5413 18.6713 23.3169 18.5587C23.0925 18.446 22.846 18.4904 22.7657 18.6569C22.6859 18.824 22.8025 19.0506 23.0263 19.1626Z",
  fill: "black"
}), Object(external_wp_element_["createElement"])(external_wp_primitives_["Path"], {
  d: "M24.4673 17.8777C24.2851 17.8746 24.1343 18.0786 24.13 18.3334C24.1257 18.5881 24.2698 18.7971 24.4519 18.8003C24.634 18.8034 24.7849 18.5994 24.7892 18.3446C24.7935 18.0899 24.6494 17.8809 24.4673 17.8777Z",
  fill: "black"
}), Object(external_wp_element_["createElement"])(external_wp_primitives_["Path"], {
  d: "M12.2373 22.4735C12.1919 22.4153 12.1177 22.4335 12.0454 22.4504C11.9951 22.4623 11.9381 22.476 11.8755 22.4748C11.7419 22.4723 11.6284 22.4134 11.5646 22.3139C11.4819 22.1837 11.4868 21.9903 11.5781 21.7682C11.5904 21.7381 11.6051 21.7049 11.6211 21.6686C11.767 21.3344 12.0117 20.7743 11.7369 20.241C11.5303 19.8398 11.1937 19.5895 10.7884 19.5369C10.3996 19.4868 9.99919 19.6339 9.7441 19.9212C9.34124 20.3749 9.27808 20.9921 9.35595 21.2099C9.38477 21.29 9.42892 21.3119 9.46142 21.3163C9.5301 21.3257 9.63127 21.275 9.69505 21.1003C9.69934 21.0878 9.70547 21.0684 9.71344 21.0434C9.74165 20.9514 9.79438 20.7799 9.88084 20.6422C9.98508 20.4763 10.147 20.3618 10.3371 20.3205C10.5308 20.2779 10.7289 20.3161 10.8944 20.4269C11.1765 20.6153 11.285 20.9683 11.1648 21.305C11.1023 21.479 11.0011 21.812 11.0238 22.0855C11.0692 22.6394 11.4028 22.8616 11.7026 22.8854C11.9939 22.8966 12.1981 22.7295 12.2496 22.6075C12.279 22.5361 12.2539 22.4923 12.2373 22.4735Z",
  fill: "black"
}), Object(external_wp_element_["createElement"])(external_wp_primitives_["Path"], {
  d: "M29.0624 21.4609C29.0513 21.4209 28.979 21.1511 28.8796 20.8263C28.7803 20.5015 28.6773 20.2724 28.6773 20.2724C29.0759 19.6634 29.0826 19.1189 29.0299 18.8109C28.9735 18.4285 28.8177 18.1031 28.5031 17.7663C28.1892 17.4296 27.5466 17.0847 26.6434 16.8262C26.5403 16.7968 26.1994 16.7011 26.1694 16.6917C26.1669 16.6717 26.1442 15.5513 26.124 15.0706C26.1093 14.7233 26.0798 14.18 25.9149 13.6455C25.7181 12.922 25.3759 12.2886 24.9479 11.8836C26.1283 10.635 26.8647 9.25926 26.8629 8.07947C26.8592 5.81 24.1293 5.1234 20.7642 6.54542C20.7605 6.54667 20.0565 6.85147 20.051 6.8546C20.048 6.85147 18.7621 5.56402 18.7431 5.5465C14.907 2.13103 2.91255 15.7391 6.7474 19.0444L7.58562 19.7692C7.36794 20.3437 7.28271 21.0028 7.35261 21.7107C7.44213 22.6201 7.90202 23.4926 8.64704 24.166C9.35404 24.8057 10.2842 25.2106 11.1868 25.21C12.6793 28.72 16.0886 30.8737 20.0872 30.9951C24.3758 31.1253 27.9758 29.0711 29.4842 25.3815C29.583 25.1224 30.0018 23.9557 30.0018 22.9255C30.0005 21.8903 29.4272 21.4609 29.0624 21.4609ZM11.5161 24.2236C11.3861 24.2461 11.2531 24.2555 11.1188 24.2518C9.82374 24.2161 8.42445 23.0263 8.28526 21.6143C8.13135 20.054 8.91255 18.8535 10.2953 18.5687C10.4608 18.5349 10.6601 18.5149 10.876 18.5268C11.651 18.57 12.7928 19.1777 13.0534 20.9002C13.2845 22.4261 12.9172 23.9801 11.5161 24.2236ZM10.0696 17.6361C9.20872 17.807 8.45021 18.3052 7.98603 18.9931C7.70887 18.7571 7.19195 18.3002 7.10059 18.1218C6.35986 16.686 7.90877 13.8946 8.99104 12.318C11.6657 8.42245 15.8544 5.4739 17.7939 6.00903C18.1091 6.10041 19.1533 7.33591 19.1533 7.33591C19.1533 7.33591 17.2151 8.43372 15.4172 9.96402C12.9951 11.8667 11.1654 14.6338 10.0696 17.6361ZM23.6657 23.6403C23.694 23.6284 23.7136 23.5952 23.7099 23.5627C23.7056 23.5226 23.6706 23.4932 23.6314 23.4976C23.6314 23.4976 21.6024 23.8043 19.6856 23.0876C19.8941 22.3948 20.4496 22.6451 21.2884 22.714C22.8012 22.806 24.1563 22.5807 25.1582 22.2871C26.0265 22.033 27.1664 21.5317 28.0525 20.8182C28.3511 21.4879 28.4565 22.2252 28.4565 22.2252C28.4565 22.2252 28.6877 22.1832 28.8809 22.304C29.0636 22.4186 29.1973 22.657 29.1059 23.2735C28.9195 24.4252 28.44 25.3596 27.6343 26.2196C27.1437 26.7585 26.5477 27.2273 25.8665 27.5684C25.5047 27.7624 25.119 27.9301 24.7118 28.0659C21.6735 29.0786 18.5628 27.9652 17.5603 25.5737C17.4799 25.394 17.4125 25.2056 17.3592 25.0091C16.9318 23.4331 17.2948 21.5423 18.4285 20.3525V20.3519C18.4984 20.2761 18.5696 20.1866 18.5696 20.0746C18.5696 19.9807 18.5113 19.8818 18.4604 19.8111C18.0637 19.224 16.6896 18.2232 16.9655 16.2861C17.1635 14.8948 18.3556 13.9146 19.4673 13.9728C19.5611 13.9778 19.6549 13.9835 19.7487 13.9891C20.2307 14.0179 20.6507 14.0811 21.0468 14.098C21.7103 14.1274 22.3069 14.0285 23.0139 13.4277C23.2525 13.2249 23.4438 13.049 23.7669 12.9933C23.8006 12.9877 23.8853 12.9564 24.0545 12.9645C24.2268 12.9739 24.3911 13.0221 24.5389 13.1222C25.1055 13.5072 25.1858 14.4391 25.2153 15.1213C25.2318 15.5106 25.2778 16.4526 25.2937 16.7224C25.3299 17.3407 25.4887 17.4277 25.8113 17.536C25.9922 17.5967 26.1608 17.6424 26.4085 17.7131C27.1584 17.9278 27.603 18.1462 27.8838 18.426C28.0512 18.6013 28.1285 18.7872 28.153 18.9643C28.2413 19.6227 27.6521 20.4364 26.0921 21.1755C24.3868 21.9836 22.3174 22.1882 20.888 22.0255C20.7783 22.013 20.3883 21.9679 20.3871 21.9679C19.2435 21.8108 18.591 23.3192 19.2778 24.3525C19.7199 25.0185 20.9248 25.4522 22.1303 25.4522C24.8939 25.4529 27.0186 24.248 27.8084 23.2078C27.8323 23.1765 27.8342 23.1734 27.8716 23.1158C27.9102 23.0557 27.8783 23.0232 27.8299 23.057C27.1842 23.5076 24.3169 25.2976 21.2492 24.7594C21.2492 24.7594 20.8764 24.6968 20.5361 24.5616C20.2656 24.4546 19.6997 24.1886 19.631 23.5958C22.107 24.3788 23.6657 23.6403 23.6657 23.6403ZM19.7444 23.1677C19.7444 23.1684 19.7444 23.1684 19.7444 23.1677C19.745 23.169 19.745 23.169 19.745 23.1696C19.745 23.169 19.7444 23.1684 19.7444 23.1677ZM15.0088 12.3023C15.9599 11.1807 17.1304 10.2056 18.1784 9.65858C18.2145 9.6398 18.2532 9.67986 18.2336 9.71616C18.1502 9.87013 17.9901 10.1993 17.9392 10.4497C17.9313 10.4885 17.9729 10.5179 18.0048 10.4954C18.6573 10.0416 19.7916 9.55531 20.7875 9.49272C20.8304 9.49022 20.8506 9.54592 20.8169 9.57283C20.6654 9.69113 20.4999 9.85511 20.3791 10.021C20.3582 10.0491 20.3779 10.0898 20.4122 10.0898C21.1112 10.0948 22.0966 10.3446 22.7386 10.712C22.7821 10.737 22.7509 10.8227 22.7024 10.8115C21.7305 10.5843 20.1406 10.4115 18.488 10.8227C17.0133 11.1901 15.8875 11.7572 15.0665 12.3668C15.0254 12.3981 14.9757 12.3418 15.0088 12.3023Z",
  fill: "black"
}));
/* harmony default export */ var library_mailchimp = (mailchimp);
// CONCATENATED MODULE: ./client/marketing/components/product-icon/icons/library/mailpoet.js


/**
 * External dependencies
 */

const mailpoet = Object(external_wp_element_["createElement"])(external_wp_primitives_["SVG"], {
  xmlns: "http://www.w3.org/2000/svg",
  viewBox: "-30 -30 212.02 216.4"
}, Object(external_wp_element_["createElement"])(external_wp_primitives_["Path"], {
  fill: "#fe5301",
  d: "M37.71,89.1c3.5,0,5.9-.8,7.2-2.3a8,8,0,0,0,2-5.4V35.7l17,45.1a12.68,12.68,0,0,0,3.7,5.4c1.6,1.3,4,2,7.2,2a12.54,12.54,0,0,0,5.9-1.4,8.41,8.41,0,0,0,3.9-5l18.1-50V81a8.53,8.53,0,0,0,2.1,6.1c1.4,1.4,3.7,2.2,6.9,2.2,3.5,0,5.9-.8,7.2-2.3a8,8,0,0,0,2-5.4V8.7a7.48,7.48,0,0,0-3.3-6.6c-2.1-1.4-5-2.1-8.6-2.1a19.3,19.3,0,0,0-9.4,2,11.63,11.63,0,0,0-5.1,6.8L74.91,67.1,54.41,8.4a12.4,12.4,0,0,0-4.5-6.2c-2.1-1.5-5-2.2-8.8-2.2a16.51,16.51,0,0,0-8.9,2.1c-2.3,1.5-3.5,3.9-3.5,7.2V80.8c0,2.8.7,4.8,2,6.2C32.21,88.4,34.41,89.1,37.71,89.1Z"
}), Object(external_wp_element_["createElement"])(external_wp_primitives_["Path"], {
  fill: "#fe5301",
  d: "M149,116.6l-2.4-1.9a7.4,7.4,0,0,0-9.4.3,19.65,19.65,0,0,1-12.5,4.6h-21.4A37.08,37.08,0,0,0,77,130.5l-1.1,1.2-1.1-1.1a37.25,37.25,0,0,0-26.3-10.9H27a19.59,19.59,0,0,1-12.4-4.6,7.28,7.28,0,0,0-9.4-.3l-2.4,1.9A7.43,7.43,0,0,0,0,122.2a7.14,7.14,0,0,0,2.4,5.7A37.28,37.28,0,0,0,27,137.4h21.6a19.59,19.59,0,0,1,18.9,14.4v.2c.1.7,1.2,4.4,8.5,4.4s8.4-3.7,8.5-4.4v-.2a19.59,19.59,0,0,1,18.9-14.4H125a37.28,37.28,0,0,0,24.6-9.5,7.42,7.42,0,0,0,2.4-5.7A7.86,7.86,0,0,0,149,116.6Z"
}));
/* harmony default export */ var library_mailpoet = (mailpoet);
// CONCATENATED MODULE: ./client/marketing/components/product-icon/icons/library/currency-dollar.js


/**
 * External dependencies
 */

const currencyDollar = Object(external_wp_element_["createElement"])(external_wp_primitives_["SVG"], {
  xmlns: "http://www.w3.org/2000/svg",
  viewBox: "0 0 24 24"
}, Object(external_wp_element_["createElement"])(external_wp_primitives_["Path"], {
  d: "M3.25 12a8.75 8.75 0 1117.5 0 8.75 8.75 0 01-17.5 0zM12 4.75a7.25 7.25 0 100 14.5 7.25 7.25 0 000-14.5zm-1.338 4.877c-.314.22-.412.452-.412.623 0 .171.098.403.412.623.312.218.783.377 1.338.377.825 0 1.605.233 2.198.648.59.414 1.052 1.057 1.052 1.852 0 .795-.461 1.438-1.052 1.852-.41.286-.907.486-1.448.582v.316a.75.75 0 01-1.5 0v-.316a3.64 3.64 0 01-1.448-.582c-.59-.414-1.052-1.057-1.052-1.852a.75.75 0 011.5 0c0 .171.098.403.412.623.312.218.783.377 1.338.377s1.026-.159 1.338-.377c.314-.22.412-.452.412-.623 0-.171-.098-.403-.412-.623-.312-.218-.783-.377-1.338-.377-.825 0-1.605-.233-2.198-.648-.59-.414-1.052-1.057-1.052-1.852 0-.795.461-1.438 1.052-1.852a3.64 3.64 0 011.448-.582V7.5a.75.75 0 011.5 0v.316c.54.096 1.039.296 1.448.582.59.414 1.052 1.057 1.052 1.852a.75.75 0 01-1.5 0c0-.171-.098-.403-.412-.623-.312-.218-.783-.377-1.338-.377s-1.026.159-1.338.377z"
}));
/* harmony default export */ var currency_dollar = (currencyDollar);
// CONCATENATED MODULE: ./client/marketing/components/product-icon/icons/library/gift.js


/**
 * External dependencies
 */

const gift = Object(external_wp_element_["createElement"])(external_wp_primitives_["SVG"], {
  xmlns: "http://www.w3.org/2000/svg"
}, Object(external_wp_element_["createElement"])(external_wp_primitives_["Path"], {
  fillRule: "evenodd",
  clipRule: "evenodd",
  d: "M14.75 9C16.1307 9 17.25 7.88071 17.25 6.5C17.25 5.11929 16.1307 4 14.75 4C13.3693 4 12.25 5.11929 12.25 6.5C12.25 5.11929 11.1307 4 9.75 4C8.36929 4 7.25 5.11929 7.25 6.5C7.25 7.88071 8.36929 9 9.75 9H4V20L20 20V9L14.75 9ZM14.75 7.5C15.3023 7.5 15.75 7.05228 15.75 6.5C15.75 5.94772 15.3023 5.5 14.75 5.5C14.1977 5.5 13.75 5.94772 13.75 6.5V7.5H14.75ZM18.5 18.5V10.5H13V18.5H18.5ZM11.5 18.5H5.5L5.5 10.5H11.5L11.5 18.5ZM8.75 6.5C8.75 7.05228 9.19772 7.5 9.75 7.5H10.75V6.5C10.75 5.94772 10.3023 5.5 9.75 5.5C9.19772 5.5 8.75 5.94772 8.75 6.5Z"
}));
/* harmony default export */ var library_gift = (gift);
// CONCATENATED MODULE: ./client/marketing/components/product-icon/icons/library/link.js


/**
 * External dependencies
 */

const link_link = Object(external_wp_element_["createElement"])(external_wp_primitives_["SVG"], {
  xmlns: "http://www.w3.org/2000/svg",
  viewBox: "0 0 24 24"
}, Object(external_wp_element_["createElement"])(external_wp_primitives_["Path"], {
  d: "M15.6 7.2H14v1.5h1.6c2 0 3.7 1.7 3.7 3.7s-1.7 3.7-3.7 3.7H14v1.5h1.6c2.8 0 5.2-2.3 5.2-5.2 0-2.9-2.3-5.2-5.2-5.2zM4.7 12.4c0-2 1.7-3.7 3.7-3.7H10V7.2H8.4c-2.9 0-5.2 2.3-5.2 5.2 0 2.9 2.3 5.2 5.2 5.2H10v-1.5H8.4c-2 0-3.7-1.7-3.7-3.7zm4.6.9h5.3v-1.5H9.3v1.5z"
}));
/* harmony default export */ var library_link = (link_link);
// CONCATENATED MODULE: ./client/marketing/components/product-icon/icons/library/people.js


/**
 * External dependencies
 */

const people = Object(external_wp_element_["createElement"])(external_wp_primitives_["SVG"], {
  xmlns: "http://www.w3.org/2000/svg",
  viewBox: "0 0 24 24"
}, Object(external_wp_element_["createElement"])(external_wp_primitives_["Path"], {
  d: "M17.5 9a2 2 0 11-4 0 2 2 0 014 0zm-4.25 8v-2a2.75 2.75 0 00-2.75-2.75h-4A2.75 2.75 0 003.75 15v2h1.5v-2c0-.69.56-1.25 1.25-1.25h4c.69 0 1.25.56 1.25 1.25v2h1.5zm7-2v2h-1.5v-2c0-.69-.56-1.25-1.25-1.25H15v-1.5h2.5A2.75 2.75 0 0120.25 15zM8.5 11a2 2 0 100-4 2 2 0 000 4z"
}));
/* harmony default export */ var library_people = (people);
// CONCATENATED MODULE: ./client/marketing/components/product-icon/icons/library/tip.js


/**
 * External dependencies
 */

const tip = Object(external_wp_element_["createElement"])(external_wp_primitives_["SVG"], {
  xmlns: "http://www.w3.org/2000/svg"
}, Object(external_wp_element_["createElement"])(external_wp_primitives_["Path"], {
  fillRule: "evenodd",
  clipRule: "evenodd",
  d: "M15 16.5H9V15h6v1.5zM15.0052 5.99481c-1.6597-1.65973-4.3507-1.65973-6.0104 0-1.65973 1.65973-1.65973 4.35069 0 6.01039.29289.2929.29289.7678 0 1.0607-.2929.2929-.76777.2929-1.06066 0-2.24552-2.2455-2.24552-5.88624 0-8.13175 2.24556-2.24551 5.88616-2.24551 8.13176 0 2.2455 2.24551 2.2455 5.88625 0 8.13175-.2929.2929-.7678.2929-1.0607 0-.2929-.2929-.2929-.7678 0-1.0607 1.6597-1.6597 1.6597-4.35066 0-6.01039zM14 19.5h-4V18h4v1.5z"
}));
/* harmony default export */ var library_tip = (tip);
// CONCATENATED MODULE: ./client/marketing/components/product-icon/icons/index.js
 // Amazon & Ebay Integration


 // AutomateWoo


 // Facebook


 // Pinterest


 // Google Ads


 // Hubspot

 // Mailchimp

 // MailPoet

 // Coupons






// CONCATENATED MODULE: ./client/marketing/components/product-icon/index.js


/**
 * External dependencies
 */





/**
 * Internal dependencies
 */




class product_icon_ProductIcon extends external_wp_element_["Component"] {
  render() {
    const product = Object(external_lodash_["camelCase"])(this.props.product);
    let iconComponent = library_blank;

    if (product in icons_namespaceObject) {
      iconComponent = icons_namespaceObject[product];
    }

    return Object(external_wp_element_["createElement"])("div", {
      className: classnames_default()(this.props.className, 'woocommerce-admin-marketing-product-icon')
    }, Object(external_wp_element_["createElement"])(icon["a" /* default */], {
      icon: iconComponent
    }));
  }

}

product_icon_ProductIcon.propTypes = {
  /**
   * Product to retrieve icon for.
   */
  product: prop_types_default.a.string.isRequired,

  /**
   * Additional classNames.
   */
  className: prop_types_default.a.string
};
/* harmony default export */ var product_icon = (product_icon_ProductIcon);
// EXTERNAL MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/react-transition-group@4.4.2_react-dom@17.0.2+react@17.0.2/node_modules/react-transition-group/esm/TransitionGroup.js + 1 modules
var TransitionGroup = __webpack_require__(520);

// EXTERNAL MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/react-transition-group@4.4.2_react-dom@17.0.2+react@17.0.2/node_modules/react-transition-group/esm/CSSTransition.js + 2 modules
var CSSTransition = __webpack_require__(513);

// EXTERNAL MODULE: ./client/marketing/components/slider/style.scss
var slider_style = __webpack_require__(304);

// CONCATENATED MODULE: ./client/marketing/components/slider/index.js


/**
 * External dependencies
 */





/**
 * Internal dependencies
 */



const Slider = _ref => {
  let {
    children,
    animationKey,
    animate
  } = _ref;
  const [height, updateHeight] = Object(external_wp_element_["useState"])(null);
  const container = Object(external_wp_element_["useRef"])();
  const containerClasses = classnames_default()('woocommerce-marketing-slider', animate && `animate-${animate}`);
  const style = {};

  if (height) {
    style.height = height;
  } // timeout should be slightly longer than the CSS animation


  const timeout = 320;

  const updateSliderHeight = () => {
    const slide = container.current.querySelector('.woocommerce-marketing-slider__slide');
    updateHeight(slide.clientHeight);
  };

  const debouncedUpdateSliderHeight = Object(external_lodash_["debounce"])(updateSliderHeight, 50);
  Object(external_wp_element_["useEffect"])(() => {
    // Update the slider height on Resize
    window.addEventListener('resize', debouncedUpdateSliderHeight);
    return () => {
      window.removeEventListener('resize', debouncedUpdateSliderHeight);
    };
  }, []);
  /**
   * Fix slider height before a slide enters because slides are absolutely position
   */

  const onEnter = () => {
    const newSlide = container.current.querySelector('.slide-enter');
    updateHeight(newSlide.clientHeight);
  };

  return Object(external_wp_element_["createElement"])("div", {
    className: containerClasses,
    ref: container,
    style: style
  }, Object(external_wp_element_["createElement"])(TransitionGroup["a" /* default */], null, Object(external_wp_element_["createElement"])(CSSTransition["a" /* default */], {
    timeout: timeout,
    classNames: "slide",
    key: animationKey,
    onEnter: onEnter
  }, Object(external_wp_element_["createElement"])("div", {
    className: "woocommerce-marketing-slider__slide"
  }, children))));
};

Slider.propTypes = {
  /**
   * A unique identifier for each slideable page.
   */
  animationKey: prop_types_default.a.any.isRequired,

  /**
   * null, 'left', 'right', to designate which direction to slide on a change.
   */
  animate: prop_types_default.a.oneOf([null, 'left', 'right'])
};
/* harmony default export */ var slider = (Slider);
// CONCATENATED MODULE: ./client/marketing/components/index.js





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

/***/ 138:
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),

/***/ 14:
/***/ (function(module, exports) {

(function() { module.exports = window["wp"]["compose"]; }());

/***/ }),

/***/ 15:
/***/ (function(module, exports) {

(function() { module.exports = window["wc"]["wcSettings"]; }());

/***/ }),

/***/ 16:
/***/ (function(module, exports) {

(function() { module.exports = window["wp"]["url"]; }());

/***/ }),

/***/ 17:
/***/ (function(module, exports) {

(function() { module.exports = window["wc"]["tracks"]; }());

/***/ }),

/***/ 178:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "a", function() { return getInAppPurchaseUrl; });
/* harmony import */ var _wordpress_url__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(16);
/* harmony import */ var _wordpress_url__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_url__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _woocommerce_settings__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(15);
/* harmony import */ var _woocommerce_settings__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_settings__WEBPACK_IMPORTED_MODULE_1__);
/**
 * External dependencies
 */


/**
 * Returns an in-app-purchase URL.
 *
 * @param {string} url
 * @param {Object} queryArgs
 * @return {string} url with in-app-purchase query parameters
 */

const getInAppPurchaseUrl = function (url) {
  let queryArgs = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};
  const {
    pathname,
    search
  } = window.location;
  const connectNonce = Object(_woocommerce_settings__WEBPACK_IMPORTED_MODULE_1__["getSetting"])('connectNonce', '');
  queryArgs = {
    'wccom-site': Object(_woocommerce_settings__WEBPACK_IMPORTED_MODULE_1__["getSetting"])('siteUrl'),
    // If the site is installed in a directory the directory must be included in the back param path.
    'wccom-back': pathname + search,
    'wccom-woo-version': Object(_woocommerce_settings__WEBPACK_IMPORTED_MODULE_1__["getSetting"])('wcVersion'),
    'wccom-connect-nonce': connectNonce,
    ...queryArgs
  };
  return Object(_wordpress_url__WEBPACK_IMPORTED_MODULE_0__["addQueryArgs"])(url, queryArgs);
};

/***/ }),

/***/ 179:
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),

/***/ 181:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";

// UNUSED EXPORTS: RecommendedExtensions, RecommendedExtensionsPlaceholder

// EXTERNAL MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/@babel+runtime@7.17.2/node_modules/@babel/runtime/helpers/extends.js
var helpers_extends = __webpack_require__(40);
var extends_default = /*#__PURE__*/__webpack_require__.n(helpers_extends);

// EXTERNAL MODULE: external ["wp","element"]
var external_wp_element_ = __webpack_require__(0);

// EXTERNAL MODULE: external ["wp","i18n"]
var external_wp_i18n_ = __webpack_require__(2);

// EXTERNAL MODULE: external ["wp","compose"]
var external_wp_compose_ = __webpack_require__(14);

// EXTERNAL MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/classnames@2.3.1/node_modules/classnames/index.js
var classnames = __webpack_require__(7);
var classnames_default = /*#__PURE__*/__webpack_require__.n(classnames);

// EXTERNAL MODULE: external ["wp","data"]
var external_wp_data_ = __webpack_require__(8);

// EXTERNAL MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/prop-types@15.8.1/node_modules/prop-types/index.js
var prop_types = __webpack_require__(1);
var prop_types_default = /*#__PURE__*/__webpack_require__.n(prop_types);

// EXTERNAL MODULE: ./client/marketing/components/recommended-extensions/style.scss
var style = __webpack_require__(138);

// EXTERNAL MODULE: external ["wc","tracks"]
var external_wc_tracks_ = __webpack_require__(17);

// EXTERNAL MODULE: ./client/marketing/components/index.js + 19 modules
var components = __webpack_require__(114);

// EXTERNAL MODULE: ./client/lib/in-app-purchase.js
var in_app_purchase = __webpack_require__(178);

// CONCATENATED MODULE: ./client/marketing/components/recommended-extensions/item.js


/**
 * External dependencies
 */


/**
 * Internal dependencies
 */





const RecommendedExtensionsItem = _ref => {
  let {
    title,
    description,
    url,
    product,
    category
  } = _ref;

  const onProductClick = () => {
    Object(external_wc_tracks_["recordEvent"])('marketing_recommended_extension', {
      name: title
    });
  };

  const classNameBase = 'woocommerce-marketing-recommended-extensions-item';
  const connectURL = Object(in_app_purchase["a" /* getInAppPurchaseUrl */])(url); // Temporary fix to account for different styles between marketing & coupons

  if (category === 'coupons' && product === 'automatewoo') {
    product = `automatewoo-alt`;
  }

  return Object(external_wp_element_["createElement"])("a", {
    href: connectURL,
    className: classNameBase,
    onClick: onProductClick
  }, Object(external_wp_element_["createElement"])(components["b" /* ProductIcon */], {
    product: product
  }), Object(external_wp_element_["createElement"])("div", {
    className: `${classNameBase}__text`
  }, Object(external_wp_element_["createElement"])("h4", null, title), Object(external_wp_element_["createElement"])("p", null, description)));
};

RecommendedExtensionsItem.propTypes = {
  title: prop_types_default.a.string.isRequired,
  description: prop_types_default.a.string.isRequired,
  url: prop_types_default.a.string.isRequired,
  product: prop_types_default.a.string.isRequired,
  category: prop_types_default.a.string.isRequired
};
/* harmony default export */ var item = (RecommendedExtensionsItem);
// CONCATENATED MODULE: ./client/marketing/components/recommended-extensions/placeholder.js


/**
 * Internal dependencies
 */


const RecommendedExtensionsPlaceholder = () => {
  const classNameBase = 'is-loading woocommerce-marketing-recommended-extensions-item';
  return Object(external_wp_element_["createElement"])("div", {
    className: classNameBase,
    "aria-hidden": "true"
  }, Object(external_wp_element_["createElement"])("div", {
    className: "woocommerce-admin-marketing-product-icon is-placeholder"
  }), Object(external_wp_element_["createElement"])("div", {
    className: `${classNameBase}__text`
  }, Object(external_wp_element_["createElement"])("h4", {
    className: "is-placeholder",
    "aria-hidden": "true"
  }), Object(external_wp_element_["createElement"])("p", null, Object(external_wp_element_["createElement"])("span", {
    className: "is-placeholder"
  }), Object(external_wp_element_["createElement"])("span", {
    className: "is-placeholder"
  }), Object(external_wp_element_["createElement"])("span", {
    className: "is-placeholder"
  }))));
};

/* harmony default export */ var placeholder = (RecommendedExtensionsPlaceholder);
// EXTERNAL MODULE: ./client/marketing/data/constants.js
var constants = __webpack_require__(58);

// EXTERNAL MODULE: ./client/marketing/components/card/index.js
var card = __webpack_require__(91);

// CONCATENATED MODULE: ./client/marketing/components/recommended-extensions/index.js



/**
 * External dependencies
 */





/**
 * Internal dependencies
 */







const RecommendedExtensions = _ref => {
  let {
    extensions,
    isLoading,
    title,
    description,
    category
  } = _ref;

  if (extensions.length === 0 && !isLoading) {
    return null;
  }

  const categoryClass = category ? `woocommerce-marketing-recommended-extensions-card__category-${category}` : '';
  const placholdersCount = 5;
  return Object(external_wp_element_["createElement"])(card["a" /* default */], {
    title: title,
    description: description,
    className: classnames_default()('woocommerce-marketing-recommended-extensions-card', categoryClass)
  }, isLoading ? Object(external_wp_element_["createElement"])("div", {
    className: classnames_default()('woocommerce-marketing-recommended-extensions-card__items', `woocommerce-marketing-recommended-extensions-card__items--count-${placholdersCount}`)
  }, [...Array(placholdersCount).keys()].map(key => Object(external_wp_element_["createElement"])(placeholder, {
    key: key
  }))) : Object(external_wp_element_["createElement"])("div", {
    className: classnames_default()('woocommerce-marketing-recommended-extensions-card__items', `woocommerce-marketing-recommended-extensions-card__items--count-${extensions.length}`)
  }, extensions.map(extension => Object(external_wp_element_["createElement"])(item, extends_default()({
    key: extension.product,
    category: category
  }, extension)))));
};

RecommendedExtensions.propTypes = {
  /**
   * Array of recommended extensions.
   */
  extensions: prop_types_default.a.arrayOf(prop_types_default.a.object).isRequired,

  /**
   * Whether the card is loading.
   */
  isLoading: prop_types_default.a.bool.isRequired,

  /**
   * Cart title.
   */
  title: prop_types_default.a.string,

  /**
   * Card description.
   */
  description: prop_types_default.a.string,

  /**
   * Category of extensions to display.
   */
  category: prop_types_default.a.string
};
RecommendedExtensions.defaultProps = {
  title: Object(external_wp_i18n_["__"])('Recommended extensions', 'woocommerce-admin'),
  description: Object(external_wp_i18n_["__"])('Great marketing requires the right tools. Take your marketing to the next level with our recommended marketing extensions.', 'woocommerce-admin')
};


/* harmony default export */ var recommended_extensions = __webpack_exports__["a"] = (Object(external_wp_compose_["compose"])(Object(external_wp_data_["withSelect"])((select, props) => {
  const {
    getRecommendedPlugins,
    isResolving
  } = select(constants["b" /* STORE_KEY */]);
  return {
    extensions: getRecommendedPlugins(props.category),
    isLoading: isResolving('getRecommendedPlugins', [props.category])
  };
}), Object(external_wp_data_["withDispatch"])(dispatch => {
  const {
    createNotice
  } = dispatch('core/notices');
  return {
    createNotice
  };
}))(RecommendedExtensions));

/***/ }),

/***/ 182:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";

// UNUSED EXPORTS: KnowledgeBase

// EXTERNAL MODULE: external ["wp","element"]
var external_wp_element_ = __webpack_require__(0);

// EXTERNAL MODULE: external ["wp","compose"]
var external_wp_compose_ = __webpack_require__(14);

// EXTERNAL MODULE: external ["wp","i18n"]
var external_wp_i18n_ = __webpack_require__(2);

// EXTERNAL MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/classnames@2.3.1/node_modules/classnames/index.js
var classnames = __webpack_require__(7);
var classnames_default = /*#__PURE__*/__webpack_require__.n(classnames);

// EXTERNAL MODULE: external ["wp","data"]
var external_wp_data_ = __webpack_require__(8);

// EXTERNAL MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/prop-types@15.8.1/node_modules/prop-types/index.js
var prop_types = __webpack_require__(1);
var prop_types_default = /*#__PURE__*/__webpack_require__.n(prop_types);

// EXTERNAL MODULE: external ["wc","components"]
var external_wc_components_ = __webpack_require__(22);

// EXTERNAL MODULE: external ["wc","tracks"]
var external_wc_tracks_ = __webpack_require__(17);

// EXTERNAL MODULE: ./client/marketing/components/knowledge-base/style.scss
var style = __webpack_require__(179);

// EXTERNAL MODULE: ./client/marketing/components/index.js + 19 modules
var components = __webpack_require__(114);

// EXTERNAL MODULE: ./client/marketing/data/constants.js
var constants = __webpack_require__(58);

// EXTERNAL MODULE: ./client/marketing/components/card/index.js
var card = __webpack_require__(91);

// EXTERNAL MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/@automattic+interpolate-components@1.2.0_react@17.0.2/node_modules/@automattic/interpolate-components/dist/esm/index.js + 1 modules
var esm = __webpack_require__(79);

// CONCATENATED MODULE: ./client/marketing/components/knowledge-base/ReadBlogMessage.js


/**
 * External dependencies
 */




const ReadBlogMessage = () => {
  return Object(esm["a" /* default */])({
    mixedString: Object(external_wp_i18n_["__"])('Read {{link}}the WooCommerce blog{{/link}} for more tips on marketing your store', 'woocommerce-admin'),
    components: {
      link: Object(external_wp_element_["createElement"])(external_wc_components_["Link"], {
        type: "external",
        href: "https://woocommerce.com/blog/marketing/coupons/?utm_medium=product",
        target: "_blank"
      })
    }
  });
};

/* harmony default export */ var knowledge_base_ReadBlogMessage = (ReadBlogMessage);
// CONCATENATED MODULE: ./client/marketing/components/knowledge-base/placeholder.js


/**
 * Internal dependencies
 */


const KnowledgebaseCardPostPlaceholder = index => {
  const classNameBase = 'woocommerce-marketing-knowledgebase-card__post';
  return Object(external_wp_element_["createElement"])("div", {
    className: `is-loading ${classNameBase}`,
    key: index,
    "aria-hidden": "true"
  }, Object(external_wp_element_["createElement"])("div", {
    className: `${classNameBase}-img is-placeholder`
  }), Object(external_wp_element_["createElement"])("div", {
    className: `${classNameBase}-text`
  }, Object(external_wp_element_["createElement"])("h3", {
    className: "is-placeholder",
    "aria-hidden": "true"
  }), Object(external_wp_element_["createElement"])("p", {
    className: `${classNameBase}-meta is-placeholder`
  })));
};

/* harmony default export */ var placeholder = (KnowledgebaseCardPostPlaceholder);
// CONCATENATED MODULE: ./client/marketing/components/knowledge-base/index.js


/**
 * External dependencies
 */








/**
 * Internal dependencies
 */








const KnowledgeBase = _ref => {
  let {
    posts,
    isLoading,
    error,
    title,
    description,
    category
  } = _ref;
  const [page, updatePage] = Object(external_wp_element_["useState"])(1);
  const [animate, updateAnimate] = Object(external_wp_element_["useState"])(null);

  const onPaginationPageChange = newPage => {
    let newAnimate;

    if (newPage > page) {
      newAnimate = 'left';
      Object(external_wc_tracks_["recordEvent"])('marketing_knowledge_carousel', {
        direction: 'forward',
        page: newPage
      });
    } else {
      newAnimate = 'right';
      Object(external_wc_tracks_["recordEvent"])('marketing_knowledge_carousel', {
        direction: 'back',
        page: newPage
      });
    }

    updatePage(newPage);
    updateAnimate(newAnimate);
  };

  const onPostClick = post => {
    Object(external_wc_tracks_["recordEvent"])('marketing_knowledge_article', {
      title: post.title
    });
  };
  /**
   * Get the 2 posts we need for the current page
   */


  const getCurrentSlide = () => {
    const currentPosts = posts.slice((page - 1) * 2, (page - 1) * 2 + 2);
    const pageClass = classnames_default()('woocommerce-marketing-knowledgebase-card__page', {
      'page-with-single-post': currentPosts.length === 1
    });
    const displayPosts = currentPosts.map((post, index) => {
      return Object(external_wp_element_["createElement"])("a", {
        className: "woocommerce-marketing-knowledgebase-card__post",
        href: post.link,
        key: index,
        onClick: () => {
          onPostClick(post);
        },
        target: "_blank",
        rel: "noopener noreferrer"
      }, post.image && Object(external_wp_element_["createElement"])("div", {
        className: "woocommerce-marketing-knowledgebase-card__post-img"
      }, Object(external_wp_element_["createElement"])("img", {
        src: post.image,
        alt: ""
      })), Object(external_wp_element_["createElement"])("div", {
        className: "woocommerce-marketing-knowledgebase-card__post-text"
      }, Object(external_wp_element_["createElement"])("h3", null, post.title), Object(external_wp_element_["createElement"])("p", {
        className: "woocommerce-marketing-knowledgebase-card__post-meta"
      }, Object(external_wp_i18n_["__"])('By', 'woocommerce-admin') + ' ', post.author_name, post.author_avatar && Object(external_wp_element_["createElement"])("img", {
        src: post.author_avatar.replace('s=96', 's=32'),
        className: "woocommerce-gravatar",
        alt: "",
        width: "16",
        height: "16"
      }))));
    });
    return Object(external_wp_element_["createElement"])("div", {
      className: pageClass
    }, displayPosts);
  };

  const renderEmpty = () => {
    const emptyTitle = Object(external_wp_i18n_["__"])('No posts yet', 'woocommerce-admin');

    return Object(external_wp_element_["createElement"])(external_wc_components_["EmptyContent"], {
      title: emptyTitle,
      message: Object(external_wp_element_["createElement"])(knowledge_base_ReadBlogMessage, null),
      illustration: "",
      actionLabel: ""
    });
  };

  const renderError = () => {
    const errorTitle = Object(external_wp_i18n_["__"])("Oops, our posts aren't loading right now", 'woocommerce-admin');

    return Object(external_wp_element_["createElement"])(external_wc_components_["EmptyContent"], {
      title: errorTitle,
      message: Object(external_wp_element_["createElement"])(knowledge_base_ReadBlogMessage, null),
      illustration: "",
      actionLabel: ""
    });
  };

  const renderPosts = () => {
    return Object(external_wp_element_["createElement"])("div", {
      className: "woocommerce-marketing-knowledgebase-card__posts"
    }, Object(external_wp_element_["createElement"])(components["c" /* Slider */], {
      animationKey: page,
      animate: animate
    }, getCurrentSlide()), Object(external_wp_element_["createElement"])(external_wc_components_["Pagination"], {
      page: page,
      perPage: 2,
      total: posts.length,
      onPageChange: onPaginationPageChange,
      showPagePicker: false,
      showPerPagePicker: false,
      showPageArrowsLabel: false
    }));
  };
  /**
   * Renders two `KnowledgebaseCardPostPlaceholder`s wrapped to mimic {@link renderPosts()} output.
   */


  const renderPlaceholder = () => {
    return Object(external_wp_element_["createElement"])("div", {
      className: "woocommerce-marketing-knowledgebase-card__posts"
    }, Object(external_wp_element_["createElement"])("div", {
      className: "woocommerce-marketing-knowledgebase-card__page"
    }, Object(external_wp_element_["createElement"])(placeholder, null), Object(external_wp_element_["createElement"])(placeholder, null)));
  };

  const renderCardBody = () => {
    if (isLoading) {
      return renderPlaceholder();
    }

    if (error) {
      return renderError();
    }

    return posts.length === 0 ? renderEmpty() : renderPosts();
  };

  const categoryClass = category ? `woocommerce-marketing-knowledgebase-card__category-${category}` : '';
  return Object(external_wp_element_["createElement"])(card["a" /* default */], {
    title: title,
    description: description,
    className: classnames_default()('woocommerce-marketing-knowledgebase-card', categoryClass)
  }, renderCardBody());
};

KnowledgeBase.propTypes = {
  /**
   * Array of posts.
   */
  posts: prop_types_default.a.arrayOf(prop_types_default.a.object).isRequired,

  /**
   * Whether the card is loading.
   */
  isLoading: prop_types_default.a.bool.isRequired,

  /**
   * Cart title.
   */
  title: prop_types_default.a.string,

  /**
   * Card description.
   */
  description: prop_types_default.a.string,

  /**
   * Category of extensions to display.
   */
  category: prop_types_default.a.string
};
KnowledgeBase.defaultProps = {
  title: Object(external_wp_i18n_["__"])('WooCommerce knowledge base', 'woocommerce-admin'),
  description: Object(external_wp_i18n_["__"])('Learn the ins and outs of successful marketing from the experts at WooCommerce.', 'woocommerce-admin')
};

/* harmony default export */ var knowledge_base = __webpack_exports__["a"] = (Object(external_wp_compose_["compose"])(Object(external_wp_data_["withSelect"])((select, props) => {
  const {
    getBlogPosts,
    getBlogPostsError,
    isResolving
  } = select(constants["b" /* STORE_KEY */]);
  return {
    posts: getBlogPosts(props.category),
    isLoading: isResolving('getBlogPosts', [props.category]),
    error: getBlogPostsError(props.category)
  };
}), Object(external_wp_data_["withDispatch"])(dispatch => {
  const {
    createNotice
  } = dispatch('core/notices');
  return {
    createNotice
  };
}))(KnowledgeBase));

/***/ }),

/***/ 19:
/***/ (function(module, exports) {

(function() { module.exports = window["wc"]["experimental"]; }());

/***/ }),

/***/ 2:
/***/ (function(module, exports) {

(function() { module.exports = window["wp"]["i18n"]; }());

/***/ }),

/***/ 22:
/***/ (function(module, exports) {

(function() { module.exports = window["wc"]["components"]; }());

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

/***/ 24:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "a", function() { return _extends; });
function _extends() {
  _extends = Object.assign || function (target) {
    for (var i = 1; i < arguments.length; i++) {
      var source = arguments[i];

      for (var key in source) {
        if (Object.prototype.hasOwnProperty.call(source, key)) {
          target[key] = source[key];
        }
      }
    }

    return target;
  };

  return _extends.apply(this, arguments);
}

/***/ }),

/***/ 26:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";

// EXPORTS
__webpack_require__.d(__webpack_exports__, "a", function() { return /* binding */ _inheritsLoose; });

// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/@babel+runtime@7.17.2/node_modules/@babel/runtime/helpers/esm/setPrototypeOf.js
function _setPrototypeOf(o, p) {
  _setPrototypeOf = Object.setPrototypeOf || function _setPrototypeOf(o, p) {
    o.__proto__ = p;
    return o;
  };

  return _setPrototypeOf(o, p);
}
// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/@babel+runtime@7.17.2/node_modules/@babel/runtime/helpers/esm/inheritsLoose.js

function _inheritsLoose(subClass, superClass) {
  subClass.prototype = Object.create(superClass.prototype);
  subClass.prototype.constructor = subClass;
  _setPrototypeOf(subClass, superClass);
}

/***/ }),

/***/ 29:
/***/ (function(module, exports) {

(function() { module.exports = window["ReactDOM"]; }());

/***/ }),

/***/ 30:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "a", function() { return _objectWithoutPropertiesLoose; });
function _objectWithoutPropertiesLoose(source, excluded) {
  if (source == null) return {};
  var target = {};
  var sourceKeys = Object.keys(source);
  var key, i;

  for (i = 0; i < sourceKeys.length; i++) {
    key = sourceKeys[i];
    if (excluded.indexOf(key) >= 0) continue;
    target[key] = source[key];
  }

  return target;
}

/***/ }),

/***/ 301:
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),

/***/ 302:
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),

/***/ 303:
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),

/***/ 304:
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),

/***/ 305:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";

// NAMESPACE OBJECT: ./client/marketing/data/actions.js
var actions_namespaceObject = {};
__webpack_require__.r(actions_namespaceObject);
__webpack_require__.d(actions_namespaceObject, "receiveInstalledPlugins", function() { return receiveInstalledPlugins; });
__webpack_require__.d(actions_namespaceObject, "receiveActivatingPlugin", function() { return receiveActivatingPlugin; });
__webpack_require__.d(actions_namespaceObject, "removeActivatingPlugin", function() { return removeActivatingPlugin; });
__webpack_require__.d(actions_namespaceObject, "receiveRecommendedPlugins", function() { return receiveRecommendedPlugins; });
__webpack_require__.d(actions_namespaceObject, "receiveBlogPosts", function() { return receiveBlogPosts; });
__webpack_require__.d(actions_namespaceObject, "handleFetchError", function() { return handleFetchError; });
__webpack_require__.d(actions_namespaceObject, "setError", function() { return setError; });
__webpack_require__.d(actions_namespaceObject, "activateInstalledPlugin", function() { return activateInstalledPlugin; });
__webpack_require__.d(actions_namespaceObject, "loadInstalledPluginsAfterActivation", function() { return loadInstalledPluginsAfterActivation; });

// NAMESPACE OBJECT: ./client/marketing/data/selectors.js
var selectors_namespaceObject = {};
__webpack_require__.r(selectors_namespaceObject);
__webpack_require__.d(selectors_namespaceObject, "getInstalledPlugins", function() { return getInstalledPlugins; });
__webpack_require__.d(selectors_namespaceObject, "getActivatingPlugins", function() { return getActivatingPlugins; });
__webpack_require__.d(selectors_namespaceObject, "getRecommendedPlugins", function() { return getRecommendedPlugins; });
__webpack_require__.d(selectors_namespaceObject, "getBlogPosts", function() { return getBlogPosts; });
__webpack_require__.d(selectors_namespaceObject, "getBlogPostsError", function() { return getBlogPostsError; });

// NAMESPACE OBJECT: ./client/marketing/data/resolvers.js
var resolvers_namespaceObject = {};
__webpack_require__.r(resolvers_namespaceObject);
__webpack_require__.d(resolvers_namespaceObject, "getRecommendedPlugins", function() { return resolvers_getRecommendedPlugins; });
__webpack_require__.d(resolvers_namespaceObject, "getBlogPosts", function() { return resolvers_getBlogPosts; });

// EXTERNAL MODULE: external ["wp","dataControls"]
var external_wp_dataControls_ = __webpack_require__(10);

// EXTERNAL MODULE: external ["wp","data"]
var external_wp_data_ = __webpack_require__(8);

// EXTERNAL MODULE: ./client/marketing/data/constants.js
var constants = __webpack_require__(58);

// EXTERNAL MODULE: external ["wp","i18n"]
var external_wp_i18n_ = __webpack_require__(2);

// CONCATENATED MODULE: ./client/marketing/data/action-types.js
const TYPES = {
  SET_INSTALLED_PLUGINS: 'SET_INSTALLED_PLUGINS',
  SET_ACTIVATING_PLUGIN: 'SET_ACTIVATING_PLUGIN',
  REMOVE_ACTIVATING_PLUGIN: 'REMOVE_ACTIVATING_PLUGIN',
  SET_RECOMMENDED_PLUGINS: 'SET_RECOMMENDED_PLUGINS',
  SET_BLOG_POSTS: 'SET_BLOG_POSTS',
  SET_ERROR: 'SET_ERROR'
};
/* harmony default export */ var action_types = (TYPES);
// CONCATENATED MODULE: ./client/marketing/data/actions.js
/**
 * External dependencies
 */



/**
 * Internal dependencies
 */



function receiveInstalledPlugins(plugins) {
  return {
    type: action_types.SET_INSTALLED_PLUGINS,
    plugins
  };
}
function receiveActivatingPlugin(pluginSlug) {
  return {
    type: action_types.SET_ACTIVATING_PLUGIN,
    pluginSlug
  };
}
function removeActivatingPlugin(pluginSlug) {
  return {
    type: action_types.REMOVE_ACTIVATING_PLUGIN,
    pluginSlug
  };
}
function receiveRecommendedPlugins(plugins, category) {
  return {
    type: action_types.SET_RECOMMENDED_PLUGINS,
    data: {
      plugins,
      category
    }
  };
}
function receiveBlogPosts(posts, category) {
  return {
    type: action_types.SET_BLOG_POSTS,
    data: {
      posts,
      category
    }
  };
}
function handleFetchError(error, message) {
  const {
    createNotice
  } = Object(external_wp_data_["dispatch"])('core/notices');
  createNotice('error', message); // eslint-disable-next-line no-console

  console.log(error);
}
function setError(category, error) {
  return {
    type: action_types.SET_ERROR,
    category,
    error
  };
}
function* activateInstalledPlugin(pluginSlug) {
  const {
    createNotice
  } = Object(external_wp_data_["dispatch"])('core/notices');
  yield receiveActivatingPlugin(pluginSlug);

  try {
    const response = yield Object(external_wp_dataControls_["apiFetch"])({
      path: constants["a" /* API_NAMESPACE */] + '/overview/activate-plugin',
      method: 'POST',
      data: {
        plugin: pluginSlug
      }
    });

    if (response) {
      yield createNotice('success', Object(external_wp_i18n_["__"])('The extension has been successfully activated.', 'woocommerce-admin')); // Deliberately load the new plugin data in a new request.

      yield loadInstalledPluginsAfterActivation(pluginSlug);
    } else {
      throw new Error();
    }
  } catch (error) {
    yield handleFetchError(error, Object(external_wp_i18n_["__"])('There was an error trying to activate the extension.', 'woocommerce-admin'));
    yield removeActivatingPlugin(pluginSlug);
  }

  return true;
}
function* loadInstalledPluginsAfterActivation(activatedPluginSlug) {
  try {
    const response = yield Object(external_wp_dataControls_["apiFetch"])({
      path: `${constants["a" /* API_NAMESPACE */]}/overview/installed-plugins`
    });

    if (response) {
      yield receiveInstalledPlugins(response);
      yield removeActivatingPlugin(activatedPluginSlug);
    } else {
      throw new Error();
    }
  } catch (error) {
    yield handleFetchError(error, Object(external_wp_i18n_["__"])('There was an error loading installed extensions.', 'woocommerce-admin'));
  }
}
// CONCATENATED MODULE: ./client/marketing/data/selectors.js
function getInstalledPlugins(state) {
  return state.installedPlugins;
}
function getActivatingPlugins(state) {
  return state.activatingPlugins;
}
function getRecommendedPlugins(state, category) {
  return state.recommendedPlugins[category] || [];
}
function getBlogPosts(state, category) {
  return state.blogPosts[category] || [];
}
function getBlogPostsError(state, category) {
  return state.errors.blogPosts && state.errors.blogPosts[category];
}
// CONCATENATED MODULE: ./client/marketing/data/resolvers.js
/**
 * External dependencies
 */


/**
 * Internal dependencies
 */



function* resolvers_getRecommendedPlugins(category) {
  try {
    const categoryParam = yield category ? `&category=${category}` : '';
    const response = yield Object(external_wp_dataControls_["apiFetch"])({
      path: `${constants["a" /* API_NAMESPACE */]}/recommended?per_page=9${categoryParam}`
    });

    if (response) {
      yield receiveRecommendedPlugins(response, category);
    } else {
      throw new Error();
    }
  } catch (error) {
    yield handleFetchError(error, Object(external_wp_i18n_["__"])('There was an error loading recommended extensions.', 'woocommerce-admin'));
  }
}
function* resolvers_getBlogPosts(category) {
  try {
    const categoryParam = yield category ? `?category=${category}` : '';
    const response = yield Object(external_wp_dataControls_["apiFetch"])({
      path: `${constants["a" /* API_NAMESPACE */]}/knowledge-base${categoryParam}`,
      method: 'GET'
    });

    if (response) {
      yield receiveBlogPosts(response, category);
    } else {
      throw new Error();
    }
  } catch (error) {
    yield setError(category, error);
  }
}
// EXTERNAL MODULE: external "lodash"
var external_lodash_ = __webpack_require__(5);

// EXTERNAL MODULE: ./client/utils/admin-settings.js
var admin_settings = __webpack_require__(23);

// CONCATENATED MODULE: ./client/marketing/data/reducer.js
/**
 * External dependencies
 */

/**
 * Internal dependencies
 */



const {
  installedExtensions
} = Object(admin_settings["d" /* getAdminSetting */])('marketing', {});
const DEFAULT_STATE = {
  installedPlugins: installedExtensions,
  activatingPlugins: [],
  recommendedPlugins: {},
  blogPosts: {},
  errors: {}
};

const reducer = function () {
  let state = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : DEFAULT_STATE;
  let action = arguments.length > 1 ? arguments[1] : undefined;

  switch (action.type) {
    case action_types.SET_INSTALLED_PLUGINS:
      return { ...state,
        installedPlugins: action.plugins
      };

    case action_types.SET_ACTIVATING_PLUGIN:
      return { ...state,
        activatingPlugins: [...state.activatingPlugins, action.pluginSlug]
      };

    case action_types.REMOVE_ACTIVATING_PLUGIN:
      return { ...state,
        activatingPlugins: Object(external_lodash_["without"])(state.activatingPlugins, action.pluginSlug)
      };

    case action_types.SET_RECOMMENDED_PLUGINS:
      return { ...state,
        recommendedPlugins: { ...state.recommendedPlugins,
          [action.data.category]: action.data.plugins
        }
      };

    case action_types.SET_BLOG_POSTS:
      return { ...state,
        blogPosts: { ...state.blogPosts,
          [action.data.category]: action.data.posts
        }
      };

    case action_types.SET_ERROR:
      return { ...state,
        errors: { ...state.errors,
          blogPosts: { ...state.errors.blogPosts,
            [action.category]: action.error
          }
        }
      };

    default:
      return state;
  }
};

/* harmony default export */ var data_reducer = (reducer);
// CONCATENATED MODULE: ./client/marketing/data/index.js
/**
 * External dependencies
 */


/**
 * Internal dependencies
 */






Object(external_wp_data_["registerStore"])(constants["b" /* STORE_KEY */], {
  actions: actions_namespaceObject,
  selectors: selectors_namespaceObject,
  resolvers: resolvers_namespaceObject,
  controls: external_wp_dataControls_["controls"],
  reducer: data_reducer
});

/***/ }),

/***/ 4:
/***/ (function(module, exports) {

(function() { module.exports = window["wp"]["components"]; }());

/***/ }),

/***/ 40:
/***/ (function(module, exports) {

function _extends() {
  module.exports = _extends = Object.assign || function (target) {
    for (var i = 1; i < arguments.length; i++) {
      var source = arguments[i];

      for (var key in source) {
        if (Object.prototype.hasOwnProperty.call(source, key)) {
          target[key] = source[key];
        }
      }
    }

    return target;
  }, module.exports.__esModule = true, module.exports["default"] = module.exports;
  return _extends.apply(this, arguments);
}

module.exports = _extends, module.exports.__esModule = true, module.exports["default"] = module.exports;

/***/ }),

/***/ 482:
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),

/***/ 498:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
// ESM COMPAT FLAG
__webpack_require__.r(__webpack_exports__);

// EXTERNAL MODULE: external ["wp","element"]
var external_wp_element_ = __webpack_require__(0);

// EXTERNAL MODULE: external ["wp","i18n"]
var external_wp_i18n_ = __webpack_require__(2);

// EXTERNAL MODULE: external ["wc","data"]
var external_wc_data_ = __webpack_require__(12);

// EXTERNAL MODULE: ./client/marketing/coupons/style.scss
var style = __webpack_require__(482);

// EXTERNAL MODULE: ./client/marketing/components/recommended-extensions/index.js + 2 modules
var recommended_extensions = __webpack_require__(181);

// EXTERNAL MODULE: ./client/marketing/components/knowledge-base/index.js + 2 modules
var knowledge_base = __webpack_require__(182);

// EXTERNAL MODULE: ./client/utils/admin-settings.js
var admin_settings = __webpack_require__(23);

// EXTERNAL MODULE: ./client/marketing/data/index.js + 5 modules
var data = __webpack_require__(305);

// CONCATENATED MODULE: ./client/marketing/coupons/index.js


/**
 * External dependencies
 */


/**
 * Internal dependencies
 */







const CouponsOverview = () => {
  const {
    currentUserCan
  } = Object(external_wc_data_["useUser"])();
  const shouldShowExtensions = Object(admin_settings["d" /* getAdminSetting */])('allowMarketplaceSuggestions', false) && currentUserCan('install_plugins');
  return Object(external_wp_element_["createElement"])("div", {
    className: "woocommerce-marketing-coupons"
  }, shouldShowExtensions && Object(external_wp_element_["createElement"])(recommended_extensions["a" /* default */], {
    title: Object(external_wp_i18n_["__"])('Recommended coupon extensions', 'woocommerce-admin'),
    description: Object(external_wp_i18n_["__"])('Take your coupon marketing to the next level with our recommended coupon extensions.', 'woocommerce-admin'),
    category: "coupons"
  }), Object(external_wp_element_["createElement"])(knowledge_base["a" /* default */], {
    category: "coupons",
    description: Object(external_wp_i18n_["__"])('Learn the ins and outs of successful coupon marketing from the experts at WooCommerce.', 'woocommerce-admin')
  }));
};

/* harmony default export */ var coupons = (CouponsOverview);
// CONCATENATED MODULE: ./client/wp-admin-scripts/marketing-coupons/index.js


/**
 * External dependencies
 */

/**
 * Internal dependencies
 */


const postForm = document.getElementById('posts-filter');

if (postForm) {
  const couponRoot = document.createElement('div');
  couponRoot.setAttribute('id', 'coupon-root');
  Object(external_wp_element_["render"])(Object(external_wp_element_["createElement"])(coupons, null), postForm.parentNode.appendChild(couponRoot));
}

/***/ }),

/***/ 5:
/***/ (function(module, exports) {

(function() { module.exports = window["lodash"]; }());

/***/ }),

/***/ 513:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";

// EXTERNAL MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/@babel+runtime@7.17.2/node_modules/@babel/runtime/helpers/esm/extends.js
var esm_extends = __webpack_require__(24);

// EXTERNAL MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/@babel+runtime@7.17.2/node_modules/@babel/runtime/helpers/esm/objectWithoutPropertiesLoose.js
var objectWithoutPropertiesLoose = __webpack_require__(30);

// EXTERNAL MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/@babel+runtime@7.17.2/node_modules/@babel/runtime/helpers/esm/inheritsLoose.js + 1 modules
var inheritsLoose = __webpack_require__(26);

// EXTERNAL MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/dom-helpers@5.2.1/node_modules/dom-helpers/esm/addClass.js + 1 modules
var esm_addClass = __webpack_require__(87);

// EXTERNAL MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/dom-helpers@5.2.1/node_modules/dom-helpers/esm/removeClass.js
var esm_removeClass = __webpack_require__(85);

// EXTERNAL MODULE: external "React"
var external_React_ = __webpack_require__(6);
var external_React_default = /*#__PURE__*/__webpack_require__.n(external_React_);

// EXTERNAL MODULE: external "ReactDOM"
var external_ReactDOM_ = __webpack_require__(29);
var external_ReactDOM_default = /*#__PURE__*/__webpack_require__.n(external_ReactDOM_);

// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/react-transition-group@4.4.2_react-dom@17.0.2+react@17.0.2/node_modules/react-transition-group/esm/config.js
/* harmony default export */ var config = ({
  disabled: false
});
// EXTERNAL MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/react-transition-group@4.4.2_react-dom@17.0.2+react@17.0.2/node_modules/react-transition-group/esm/TransitionGroupContext.js
var TransitionGroupContext = __webpack_require__(100);

// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/react-transition-group@4.4.2_react-dom@17.0.2+react@17.0.2/node_modules/react-transition-group/esm/Transition.js








var UNMOUNTED = 'unmounted';
var EXITED = 'exited';
var ENTERING = 'entering';
var ENTERED = 'entered';
var EXITING = 'exiting';
/**
 * The Transition component lets you describe a transition from one component
 * state to another _over time_ with a simple declarative API. Most commonly
 * it's used to animate the mounting and unmounting of a component, but can also
 * be used to describe in-place transition states as well.
 *
 * ---
 *
 * **Note**: `Transition` is a platform-agnostic base component. If you're using
 * transitions in CSS, you'll probably want to use
 * [`CSSTransition`](https://reactcommunity.org/react-transition-group/css-transition)
 * instead. It inherits all the features of `Transition`, but contains
 * additional features necessary to play nice with CSS transitions (hence the
 * name of the component).
 *
 * ---
 *
 * By default the `Transition` component does not alter the behavior of the
 * component it renders, it only tracks "enter" and "exit" states for the
 * components. It's up to you to give meaning and effect to those states. For
 * example we can add styles to a component when it enters or exits:
 *
 * ```jsx
 * import { Transition } from 'react-transition-group';
 *
 * const duration = 300;
 *
 * const defaultStyle = {
 *   transition: `opacity ${duration}ms ease-in-out`,
 *   opacity: 0,
 * }
 *
 * const transitionStyles = {
 *   entering: { opacity: 1 },
 *   entered:  { opacity: 1 },
 *   exiting:  { opacity: 0 },
 *   exited:  { opacity: 0 },
 * };
 *
 * const Fade = ({ in: inProp }) => (
 *   <Transition in={inProp} timeout={duration}>
 *     {state => (
 *       <div style={{
 *         ...defaultStyle,
 *         ...transitionStyles[state]
 *       }}>
 *         I'm a fade Transition!
 *       </div>
 *     )}
 *   </Transition>
 * );
 * ```
 *
 * There are 4 main states a Transition can be in:
 *  - `'entering'`
 *  - `'entered'`
 *  - `'exiting'`
 *  - `'exited'`
 *
 * Transition state is toggled via the `in` prop. When `true` the component
 * begins the "Enter" stage. During this stage, the component will shift from
 * its current transition state, to `'entering'` for the duration of the
 * transition and then to the `'entered'` stage once it's complete. Let's take
 * the following example (we'll use the
 * [useState](https://reactjs.org/docs/hooks-reference.html#usestate) hook):
 *
 * ```jsx
 * function App() {
 *   const [inProp, setInProp] = useState(false);
 *   return (
 *     <div>
 *       <Transition in={inProp} timeout={500}>
 *         {state => (
 *           // ...
 *         )}
 *       </Transition>
 *       <button onClick={() => setInProp(true)}>
 *         Click to Enter
 *       </button>
 *     </div>
 *   );
 * }
 * ```
 *
 * When the button is clicked the component will shift to the `'entering'` state
 * and stay there for 500ms (the value of `timeout`) before it finally switches
 * to `'entered'`.
 *
 * When `in` is `false` the same thing happens except the state moves from
 * `'exiting'` to `'exited'`.
 */

var Transition_Transition = /*#__PURE__*/function (_React$Component) {
  Object(inheritsLoose["a" /* default */])(Transition, _React$Component);

  function Transition(props, context) {
    var _this;

    _this = _React$Component.call(this, props, context) || this;
    var parentGroup = context; // In the context of a TransitionGroup all enters are really appears

    var appear = parentGroup && !parentGroup.isMounting ? props.enter : props.appear;
    var initialStatus;
    _this.appearStatus = null;

    if (props.in) {
      if (appear) {
        initialStatus = EXITED;
        _this.appearStatus = ENTERING;
      } else {
        initialStatus = ENTERED;
      }
    } else {
      if (props.unmountOnExit || props.mountOnEnter) {
        initialStatus = UNMOUNTED;
      } else {
        initialStatus = EXITED;
      }
    }

    _this.state = {
      status: initialStatus
    };
    _this.nextCallback = null;
    return _this;
  }

  Transition.getDerivedStateFromProps = function getDerivedStateFromProps(_ref, prevState) {
    var nextIn = _ref.in;

    if (nextIn && prevState.status === UNMOUNTED) {
      return {
        status: EXITED
      };
    }

    return null;
  } // getSnapshotBeforeUpdate(prevProps) {
  //   let nextStatus = null
  //   if (prevProps !== this.props) {
  //     const { status } = this.state
  //     if (this.props.in) {
  //       if (status !== ENTERING && status !== ENTERED) {
  //         nextStatus = ENTERING
  //       }
  //     } else {
  //       if (status === ENTERING || status === ENTERED) {
  //         nextStatus = EXITING
  //       }
  //     }
  //   }
  //   return { nextStatus }
  // }
  ;

  var _proto = Transition.prototype;

  _proto.componentDidMount = function componentDidMount() {
    this.updateStatus(true, this.appearStatus);
  };

  _proto.componentDidUpdate = function componentDidUpdate(prevProps) {
    var nextStatus = null;

    if (prevProps !== this.props) {
      var status = this.state.status;

      if (this.props.in) {
        if (status !== ENTERING && status !== ENTERED) {
          nextStatus = ENTERING;
        }
      } else {
        if (status === ENTERING || status === ENTERED) {
          nextStatus = EXITING;
        }
      }
    }

    this.updateStatus(false, nextStatus);
  };

  _proto.componentWillUnmount = function componentWillUnmount() {
    this.cancelNextCallback();
  };

  _proto.getTimeouts = function getTimeouts() {
    var timeout = this.props.timeout;
    var exit, enter, appear;
    exit = enter = appear = timeout;

    if (timeout != null && typeof timeout !== 'number') {
      exit = timeout.exit;
      enter = timeout.enter; // TODO: remove fallback for next major

      appear = timeout.appear !== undefined ? timeout.appear : enter;
    }

    return {
      exit: exit,
      enter: enter,
      appear: appear
    };
  };

  _proto.updateStatus = function updateStatus(mounting, nextStatus) {
    if (mounting === void 0) {
      mounting = false;
    }

    if (nextStatus !== null) {
      // nextStatus will always be ENTERING or EXITING.
      this.cancelNextCallback();

      if (nextStatus === ENTERING) {
        this.performEnter(mounting);
      } else {
        this.performExit();
      }
    } else if (this.props.unmountOnExit && this.state.status === EXITED) {
      this.setState({
        status: UNMOUNTED
      });
    }
  };

  _proto.performEnter = function performEnter(mounting) {
    var _this2 = this;

    var enter = this.props.enter;
    var appearing = this.context ? this.context.isMounting : mounting;

    var _ref2 = this.props.nodeRef ? [appearing] : [external_ReactDOM_default.a.findDOMNode(this), appearing],
        maybeNode = _ref2[0],
        maybeAppearing = _ref2[1];

    var timeouts = this.getTimeouts();
    var enterTimeout = appearing ? timeouts.appear : timeouts.enter; // no enter animation skip right to ENTERED
    // if we are mounting and running this it means appear _must_ be set

    if (!mounting && !enter || config.disabled) {
      this.safeSetState({
        status: ENTERED
      }, function () {
        _this2.props.onEntered(maybeNode);
      });
      return;
    }

    this.props.onEnter(maybeNode, maybeAppearing);
    this.safeSetState({
      status: ENTERING
    }, function () {
      _this2.props.onEntering(maybeNode, maybeAppearing);

      _this2.onTransitionEnd(enterTimeout, function () {
        _this2.safeSetState({
          status: ENTERED
        }, function () {
          _this2.props.onEntered(maybeNode, maybeAppearing);
        });
      });
    });
  };

  _proto.performExit = function performExit() {
    var _this3 = this;

    var exit = this.props.exit;
    var timeouts = this.getTimeouts();
    var maybeNode = this.props.nodeRef ? undefined : external_ReactDOM_default.a.findDOMNode(this); // no exit animation skip right to EXITED

    if (!exit || config.disabled) {
      this.safeSetState({
        status: EXITED
      }, function () {
        _this3.props.onExited(maybeNode);
      });
      return;
    }

    this.props.onExit(maybeNode);
    this.safeSetState({
      status: EXITING
    }, function () {
      _this3.props.onExiting(maybeNode);

      _this3.onTransitionEnd(timeouts.exit, function () {
        _this3.safeSetState({
          status: EXITED
        }, function () {
          _this3.props.onExited(maybeNode);
        });
      });
    });
  };

  _proto.cancelNextCallback = function cancelNextCallback() {
    if (this.nextCallback !== null) {
      this.nextCallback.cancel();
      this.nextCallback = null;
    }
  };

  _proto.safeSetState = function safeSetState(nextState, callback) {
    // This shouldn't be necessary, but there are weird race conditions with
    // setState callbacks and unmounting in testing, so always make sure that
    // we can cancel any pending setState callbacks after we unmount.
    callback = this.setNextCallback(callback);
    this.setState(nextState, callback);
  };

  _proto.setNextCallback = function setNextCallback(callback) {
    var _this4 = this;

    var active = true;

    this.nextCallback = function (event) {
      if (active) {
        active = false;
        _this4.nextCallback = null;
        callback(event);
      }
    };

    this.nextCallback.cancel = function () {
      active = false;
    };

    return this.nextCallback;
  };

  _proto.onTransitionEnd = function onTransitionEnd(timeout, handler) {
    this.setNextCallback(handler);
    var node = this.props.nodeRef ? this.props.nodeRef.current : external_ReactDOM_default.a.findDOMNode(this);
    var doesNotHaveTimeoutOrListener = timeout == null && !this.props.addEndListener;

    if (!node || doesNotHaveTimeoutOrListener) {
      setTimeout(this.nextCallback, 0);
      return;
    }

    if (this.props.addEndListener) {
      var _ref3 = this.props.nodeRef ? [this.nextCallback] : [node, this.nextCallback],
          maybeNode = _ref3[0],
          maybeNextCallback = _ref3[1];

      this.props.addEndListener(maybeNode, maybeNextCallback);
    }

    if (timeout != null) {
      setTimeout(this.nextCallback, timeout);
    }
  };

  _proto.render = function render() {
    var status = this.state.status;

    if (status === UNMOUNTED) {
      return null;
    }

    var _this$props = this.props,
        children = _this$props.children,
        _in = _this$props.in,
        _mountOnEnter = _this$props.mountOnEnter,
        _unmountOnExit = _this$props.unmountOnExit,
        _appear = _this$props.appear,
        _enter = _this$props.enter,
        _exit = _this$props.exit,
        _timeout = _this$props.timeout,
        _addEndListener = _this$props.addEndListener,
        _onEnter = _this$props.onEnter,
        _onEntering = _this$props.onEntering,
        _onEntered = _this$props.onEntered,
        _onExit = _this$props.onExit,
        _onExiting = _this$props.onExiting,
        _onExited = _this$props.onExited,
        _nodeRef = _this$props.nodeRef,
        childProps = Object(objectWithoutPropertiesLoose["a" /* default */])(_this$props, ["children", "in", "mountOnEnter", "unmountOnExit", "appear", "enter", "exit", "timeout", "addEndListener", "onEnter", "onEntering", "onEntered", "onExit", "onExiting", "onExited", "nodeRef"]);

    return (
      /*#__PURE__*/
      // allows for nested Transitions
      external_React_default.a.createElement(TransitionGroupContext["a" /* default */].Provider, {
        value: null
      }, typeof children === 'function' ? children(status, childProps) : external_React_default.a.cloneElement(external_React_default.a.Children.only(children), childProps))
    );
  };

  return Transition;
}(external_React_default.a.Component);

Transition_Transition.contextType = TransitionGroupContext["a" /* default */];
Transition_Transition.propTypes =  false ? undefined : {}; // Name the function so it is clearer in the documentation

function noop() {}

Transition_Transition.defaultProps = {
  in: false,
  mountOnEnter: false,
  unmountOnExit: false,
  appear: false,
  enter: true,
  exit: true,
  onEnter: noop,
  onEntering: noop,
  onEntered: noop,
  onExit: noop,
  onExiting: noop,
  onExited: noop
};
Transition_Transition.UNMOUNTED = UNMOUNTED;
Transition_Transition.EXITED = EXITED;
Transition_Transition.ENTERING = ENTERING;
Transition_Transition.ENTERED = ENTERED;
Transition_Transition.EXITING = EXITING;
/* harmony default export */ var esm_Transition = (Transition_Transition);
// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/react-transition-group@4.4.2_react-dom@17.0.2+react@17.0.2/node_modules/react-transition-group/esm/CSSTransition.js










var _addClass = function addClass(node, classes) {
  return node && classes && classes.split(' ').forEach(function (c) {
    return Object(esm_addClass["a" /* default */])(node, c);
  });
};

var CSSTransition_removeClass = function removeClass(node, classes) {
  return node && classes && classes.split(' ').forEach(function (c) {
    return Object(esm_removeClass["a" /* default */])(node, c);
  });
};
/**
 * A transition component inspired by the excellent
 * [ng-animate](https://docs.angularjs.org/api/ngAnimate) library, you should
 * use it if you're using CSS transitions or animations. It's built upon the
 * [`Transition`](https://reactcommunity.org/react-transition-group/transition)
 * component, so it inherits all of its props.
 *
 * `CSSTransition` applies a pair of class names during the `appear`, `enter`,
 * and `exit` states of the transition. The first class is applied and then a
 * second `*-active` class in order to activate the CSS transition. After the
 * transition, matching `*-done` class names are applied to persist the
 * transition state.
 *
 * ```jsx
 * function App() {
 *   const [inProp, setInProp] = useState(false);
 *   return (
 *     <div>
 *       <CSSTransition in={inProp} timeout={200} classNames="my-node">
 *         <div>
 *           {"I'll receive my-node-* classes"}
 *         </div>
 *       </CSSTransition>
 *       <button type="button" onClick={() => setInProp(true)}>
 *         Click to Enter
 *       </button>
 *     </div>
 *   );
 * }
 * ```
 *
 * When the `in` prop is set to `true`, the child component will first receive
 * the class `example-enter`, then the `example-enter-active` will be added in
 * the next tick. `CSSTransition` [forces a
 * reflow](https://github.com/reactjs/react-transition-group/blob/5007303e729a74be66a21c3e2205e4916821524b/src/CSSTransition.js#L208-L215)
 * between before adding the `example-enter-active`. This is an important trick
 * because it allows us to transition between `example-enter` and
 * `example-enter-active` even though they were added immediately one after
 * another. Most notably, this is what makes it possible for us to animate
 * _appearance_.
 *
 * ```css
 * .my-node-enter {
 *   opacity: 0;
 * }
 * .my-node-enter-active {
 *   opacity: 1;
 *   transition: opacity 200ms;
 * }
 * .my-node-exit {
 *   opacity: 1;
 * }
 * .my-node-exit-active {
 *   opacity: 0;
 *   transition: opacity 200ms;
 * }
 * ```
 *
 * `*-active` classes represent which styles you want to animate **to**, so it's
 * important to add `transition` declaration only to them, otherwise transitions
 * might not behave as intended! This might not be obvious when the transitions
 * are symmetrical, i.e. when `*-enter-active` is the same as `*-exit`, like in
 * the example above (minus `transition`), but it becomes apparent in more
 * complex transitions.
 *
 * **Note**: If you're using the
 * [`appear`](http://reactcommunity.org/react-transition-group/transition#Transition-prop-appear)
 * prop, make sure to define styles for `.appear-*` classes as well.
 */


var CSSTransition_CSSTransition = /*#__PURE__*/function (_React$Component) {
  Object(inheritsLoose["a" /* default */])(CSSTransition, _React$Component);

  function CSSTransition() {
    var _this;

    for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
      args[_key] = arguments[_key];
    }

    _this = _React$Component.call.apply(_React$Component, [this].concat(args)) || this;
    _this.appliedClasses = {
      appear: {},
      enter: {},
      exit: {}
    };

    _this.onEnter = function (maybeNode, maybeAppearing) {
      var _this$resolveArgument = _this.resolveArguments(maybeNode, maybeAppearing),
          node = _this$resolveArgument[0],
          appearing = _this$resolveArgument[1];

      _this.removeClasses(node, 'exit');

      _this.addClass(node, appearing ? 'appear' : 'enter', 'base');

      if (_this.props.onEnter) {
        _this.props.onEnter(maybeNode, maybeAppearing);
      }
    };

    _this.onEntering = function (maybeNode, maybeAppearing) {
      var _this$resolveArgument2 = _this.resolveArguments(maybeNode, maybeAppearing),
          node = _this$resolveArgument2[0],
          appearing = _this$resolveArgument2[1];

      var type = appearing ? 'appear' : 'enter';

      _this.addClass(node, type, 'active');

      if (_this.props.onEntering) {
        _this.props.onEntering(maybeNode, maybeAppearing);
      }
    };

    _this.onEntered = function (maybeNode, maybeAppearing) {
      var _this$resolveArgument3 = _this.resolveArguments(maybeNode, maybeAppearing),
          node = _this$resolveArgument3[0],
          appearing = _this$resolveArgument3[1];

      var type = appearing ? 'appear' : 'enter';

      _this.removeClasses(node, type);

      _this.addClass(node, type, 'done');

      if (_this.props.onEntered) {
        _this.props.onEntered(maybeNode, maybeAppearing);
      }
    };

    _this.onExit = function (maybeNode) {
      var _this$resolveArgument4 = _this.resolveArguments(maybeNode),
          node = _this$resolveArgument4[0];

      _this.removeClasses(node, 'appear');

      _this.removeClasses(node, 'enter');

      _this.addClass(node, 'exit', 'base');

      if (_this.props.onExit) {
        _this.props.onExit(maybeNode);
      }
    };

    _this.onExiting = function (maybeNode) {
      var _this$resolveArgument5 = _this.resolveArguments(maybeNode),
          node = _this$resolveArgument5[0];

      _this.addClass(node, 'exit', 'active');

      if (_this.props.onExiting) {
        _this.props.onExiting(maybeNode);
      }
    };

    _this.onExited = function (maybeNode) {
      var _this$resolveArgument6 = _this.resolveArguments(maybeNode),
          node = _this$resolveArgument6[0];

      _this.removeClasses(node, 'exit');

      _this.addClass(node, 'exit', 'done');

      if (_this.props.onExited) {
        _this.props.onExited(maybeNode);
      }
    };

    _this.resolveArguments = function (maybeNode, maybeAppearing) {
      return _this.props.nodeRef ? [_this.props.nodeRef.current, maybeNode] // here `maybeNode` is actually `appearing`
      : [maybeNode, maybeAppearing];
    };

    _this.getClassNames = function (type) {
      var classNames = _this.props.classNames;
      var isStringClassNames = typeof classNames === 'string';
      var prefix = isStringClassNames && classNames ? classNames + "-" : '';
      var baseClassName = isStringClassNames ? "" + prefix + type : classNames[type];
      var activeClassName = isStringClassNames ? baseClassName + "-active" : classNames[type + "Active"];
      var doneClassName = isStringClassNames ? baseClassName + "-done" : classNames[type + "Done"];
      return {
        baseClassName: baseClassName,
        activeClassName: activeClassName,
        doneClassName: doneClassName
      };
    };

    return _this;
  }

  var _proto = CSSTransition.prototype;

  _proto.addClass = function addClass(node, type, phase) {
    var className = this.getClassNames(type)[phase + "ClassName"];

    var _this$getClassNames = this.getClassNames('enter'),
        doneClassName = _this$getClassNames.doneClassName;

    if (type === 'appear' && phase === 'done' && doneClassName) {
      className += " " + doneClassName;
    } // This is to force a repaint,
    // which is necessary in order to transition styles when adding a class name.


    if (phase === 'active') {
      /* eslint-disable no-unused-expressions */
      node && node.scrollTop;
    }

    if (className) {
      this.appliedClasses[type][phase] = className;

      _addClass(node, className);
    }
  };

  _proto.removeClasses = function removeClasses(node, type) {
    var _this$appliedClasses$ = this.appliedClasses[type],
        baseClassName = _this$appliedClasses$.base,
        activeClassName = _this$appliedClasses$.active,
        doneClassName = _this$appliedClasses$.done;
    this.appliedClasses[type] = {};

    if (baseClassName) {
      CSSTransition_removeClass(node, baseClassName);
    }

    if (activeClassName) {
      CSSTransition_removeClass(node, activeClassName);
    }

    if (doneClassName) {
      CSSTransition_removeClass(node, doneClassName);
    }
  };

  _proto.render = function render() {
    var _this$props = this.props,
        _ = _this$props.classNames,
        props = Object(objectWithoutPropertiesLoose["a" /* default */])(_this$props, ["classNames"]);

    return /*#__PURE__*/external_React_default.a.createElement(esm_Transition, Object(esm_extends["a" /* default */])({}, props, {
      onEnter: this.onEnter,
      onEntered: this.onEntered,
      onEntering: this.onEntering,
      onExit: this.onExit,
      onExiting: this.onExiting,
      onExited: this.onExited
    }));
  };

  return CSSTransition;
}(external_React_default.a.Component);

CSSTransition_CSSTransition.defaultProps = {
  classNames: ''
};
CSSTransition_CSSTransition.propTypes =  false ? undefined : {};
/* harmony default export */ var esm_CSSTransition = __webpack_exports__["a"] = (CSSTransition_CSSTransition);

/***/ }),

/***/ 520:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";

// EXTERNAL MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/@babel+runtime@7.17.2/node_modules/@babel/runtime/helpers/esm/objectWithoutPropertiesLoose.js
var objectWithoutPropertiesLoose = __webpack_require__(30);

// EXTERNAL MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/@babel+runtime@7.17.2/node_modules/@babel/runtime/helpers/esm/extends.js
var esm_extends = __webpack_require__(24);

// EXTERNAL MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/@babel+runtime@7.17.2/node_modules/@babel/runtime/helpers/esm/assertThisInitialized.js
var assertThisInitialized = __webpack_require__(84);

// EXTERNAL MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/@babel+runtime@7.17.2/node_modules/@babel/runtime/helpers/esm/inheritsLoose.js + 1 modules
var inheritsLoose = __webpack_require__(26);

// EXTERNAL MODULE: external "React"
var external_React_ = __webpack_require__(6);
var external_React_default = /*#__PURE__*/__webpack_require__.n(external_React_);

// EXTERNAL MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/react-transition-group@4.4.2_react-dom@17.0.2+react@17.0.2/node_modules/react-transition-group/esm/TransitionGroupContext.js
var TransitionGroupContext = __webpack_require__(100);

// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/react-transition-group@4.4.2_react-dom@17.0.2+react@17.0.2/node_modules/react-transition-group/esm/utils/ChildMapping.js

/**
 * Given `this.props.children`, return an object mapping key to child.
 *
 * @param {*} children `this.props.children`
 * @return {object} Mapping of key to child
 */

function getChildMapping(children, mapFn) {
  var mapper = function mapper(child) {
    return mapFn && Object(external_React_["isValidElement"])(child) ? mapFn(child) : child;
  };

  var result = Object.create(null);
  if (children) external_React_["Children"].map(children, function (c) {
    return c;
  }).forEach(function (child) {
    // run the map function here instead so that the key is the computed one
    result[child.key] = mapper(child);
  });
  return result;
}
/**
 * When you're adding or removing children some may be added or removed in the
 * same render pass. We want to show *both* since we want to simultaneously
 * animate elements in and out. This function takes a previous set of keys
 * and a new set of keys and merges them with its best guess of the correct
 * ordering. In the future we may expose some of the utilities in
 * ReactMultiChild to make this easy, but for now React itself does not
 * directly have this concept of the union of prevChildren and nextChildren
 * so we implement it here.
 *
 * @param {object} prev prev children as returned from
 * `ReactTransitionChildMapping.getChildMapping()`.
 * @param {object} next next children as returned from
 * `ReactTransitionChildMapping.getChildMapping()`.
 * @return {object} a key set that contains all keys in `prev` and all keys
 * in `next` in a reasonable order.
 */

function mergeChildMappings(prev, next) {
  prev = prev || {};
  next = next || {};

  function getValueForKey(key) {
    return key in next ? next[key] : prev[key];
  } // For each key of `next`, the list of keys to insert before that key in
  // the combined list


  var nextKeysPending = Object.create(null);
  var pendingKeys = [];

  for (var prevKey in prev) {
    if (prevKey in next) {
      if (pendingKeys.length) {
        nextKeysPending[prevKey] = pendingKeys;
        pendingKeys = [];
      }
    } else {
      pendingKeys.push(prevKey);
    }
  }

  var i;
  var childMapping = {};

  for (var nextKey in next) {
    if (nextKeysPending[nextKey]) {
      for (i = 0; i < nextKeysPending[nextKey].length; i++) {
        var pendingNextKey = nextKeysPending[nextKey][i];
        childMapping[nextKeysPending[nextKey][i]] = getValueForKey(pendingNextKey);
      }
    }

    childMapping[nextKey] = getValueForKey(nextKey);
  } // Finally, add the keys which didn't appear before any key in `next`


  for (i = 0; i < pendingKeys.length; i++) {
    childMapping[pendingKeys[i]] = getValueForKey(pendingKeys[i]);
  }

  return childMapping;
}

function getProp(child, prop, props) {
  return props[prop] != null ? props[prop] : child.props[prop];
}

function getInitialChildMapping(props, onExited) {
  return getChildMapping(props.children, function (child) {
    return Object(external_React_["cloneElement"])(child, {
      onExited: onExited.bind(null, child),
      in: true,
      appear: getProp(child, 'appear', props),
      enter: getProp(child, 'enter', props),
      exit: getProp(child, 'exit', props)
    });
  });
}
function getNextChildMapping(nextProps, prevChildMapping, onExited) {
  var nextChildMapping = getChildMapping(nextProps.children);
  var children = mergeChildMappings(prevChildMapping, nextChildMapping);
  Object.keys(children).forEach(function (key) {
    var child = children[key];
    if (!Object(external_React_["isValidElement"])(child)) return;
    var hasPrev = (key in prevChildMapping);
    var hasNext = (key in nextChildMapping);
    var prevChild = prevChildMapping[key];
    var isLeaving = Object(external_React_["isValidElement"])(prevChild) && !prevChild.props.in; // item is new (entering)

    if (hasNext && (!hasPrev || isLeaving)) {
      // console.log('entering', key)
      children[key] = Object(external_React_["cloneElement"])(child, {
        onExited: onExited.bind(null, child),
        in: true,
        exit: getProp(child, 'exit', nextProps),
        enter: getProp(child, 'enter', nextProps)
      });
    } else if (!hasNext && hasPrev && !isLeaving) {
      // item is old (exiting)
      // console.log('leaving', key)
      children[key] = Object(external_React_["cloneElement"])(child, {
        in: false
      });
    } else if (hasNext && hasPrev && Object(external_React_["isValidElement"])(prevChild)) {
      // item hasn't changed transition states
      // copy over the last transition props;
      // console.log('unchanged', key)
      children[key] = Object(external_React_["cloneElement"])(child, {
        onExited: onExited.bind(null, child),
        in: prevChild.props.in,
        exit: getProp(child, 'exit', nextProps),
        enter: getProp(child, 'enter', nextProps)
      });
    }
  });
  return children;
}
// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/react-transition-group@4.4.2_react-dom@17.0.2+react@17.0.2/node_modules/react-transition-group/esm/TransitionGroup.js









var values = Object.values || function (obj) {
  return Object.keys(obj).map(function (k) {
    return obj[k];
  });
};

var defaultProps = {
  component: 'div',
  childFactory: function childFactory(child) {
    return child;
  }
};
/**
 * The `<TransitionGroup>` component manages a set of transition components
 * (`<Transition>` and `<CSSTransition>`) in a list. Like with the transition
 * components, `<TransitionGroup>` is a state machine for managing the mounting
 * and unmounting of components over time.
 *
 * Consider the example below. As items are removed or added to the TodoList the
 * `in` prop is toggled automatically by the `<TransitionGroup>`.
 *
 * Note that `<TransitionGroup>`  does not define any animation behavior!
 * Exactly _how_ a list item animates is up to the individual transition
 * component. This means you can mix and match animations across different list
 * items.
 */

var TransitionGroup_TransitionGroup = /*#__PURE__*/function (_React$Component) {
  Object(inheritsLoose["a" /* default */])(TransitionGroup, _React$Component);

  function TransitionGroup(props, context) {
    var _this;

    _this = _React$Component.call(this, props, context) || this;

    var handleExited = _this.handleExited.bind(Object(assertThisInitialized["a" /* default */])(_this)); // Initial children should all be entering, dependent on appear


    _this.state = {
      contextValue: {
        isMounting: true
      },
      handleExited: handleExited,
      firstRender: true
    };
    return _this;
  }

  var _proto = TransitionGroup.prototype;

  _proto.componentDidMount = function componentDidMount() {
    this.mounted = true;
    this.setState({
      contextValue: {
        isMounting: false
      }
    });
  };

  _proto.componentWillUnmount = function componentWillUnmount() {
    this.mounted = false;
  };

  TransitionGroup.getDerivedStateFromProps = function getDerivedStateFromProps(nextProps, _ref) {
    var prevChildMapping = _ref.children,
        handleExited = _ref.handleExited,
        firstRender = _ref.firstRender;
    return {
      children: firstRender ? getInitialChildMapping(nextProps, handleExited) : getNextChildMapping(nextProps, prevChildMapping, handleExited),
      firstRender: false
    };
  } // node is `undefined` when user provided `nodeRef` prop
  ;

  _proto.handleExited = function handleExited(child, node) {
    var currentChildMapping = getChildMapping(this.props.children);
    if (child.key in currentChildMapping) return;

    if (child.props.onExited) {
      child.props.onExited(node);
    }

    if (this.mounted) {
      this.setState(function (state) {
        var children = Object(esm_extends["a" /* default */])({}, state.children);

        delete children[child.key];
        return {
          children: children
        };
      });
    }
  };

  _proto.render = function render() {
    var _this$props = this.props,
        Component = _this$props.component,
        childFactory = _this$props.childFactory,
        props = Object(objectWithoutPropertiesLoose["a" /* default */])(_this$props, ["component", "childFactory"]);

    var contextValue = this.state.contextValue;
    var children = values(this.state.children).map(childFactory);
    delete props.appear;
    delete props.enter;
    delete props.exit;

    if (Component === null) {
      return /*#__PURE__*/external_React_default.a.createElement(TransitionGroupContext["a" /* default */].Provider, {
        value: contextValue
      }, children);
    }

    return /*#__PURE__*/external_React_default.a.createElement(TransitionGroupContext["a" /* default */].Provider, {
      value: contextValue
    }, /*#__PURE__*/external_React_default.a.createElement(Component, props, children));
  };

  return TransitionGroup;
}(external_React_default.a.Component);

TransitionGroup_TransitionGroup.propTypes =  false ? undefined : {};
TransitionGroup_TransitionGroup.defaultProps = defaultProps;
/* harmony default export */ var esm_TransitionGroup = __webpack_exports__["a"] = (TransitionGroup_TransitionGroup);

/***/ }),

/***/ 58:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "b", function() { return STORE_KEY; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "a", function() { return API_NAMESPACE; });
const STORE_KEY = 'wc/marketing';
const API_NAMESPACE = '/wc-admin/marketing';

/***/ }),

/***/ 6:
/***/ (function(module, exports) {

(function() { module.exports = window["React"]; }());

/***/ }),

/***/ 7:
/***/ (function(module, exports, __webpack_require__) {

/*!
  Copyright (c) 2018 Jed Watson.
  Licensed under the MIT License (MIT), see
  http://jedwatson.github.io/classnames
*/
/* global define */

(function () {
	'use strict';

	var hasOwn = {}.hasOwnProperty;

	function classNames() {
		var classes = [];

		for (var i = 0; i < arguments.length; i++) {
			var arg = arguments[i];
			if (!arg) continue;

			var argType = typeof arg;

			if (argType === 'string' || argType === 'number') {
				classes.push(arg);
			} else if (Array.isArray(arg)) {
				if (arg.length) {
					var inner = classNames.apply(null, arg);
					if (inner) {
						classes.push(inner);
					}
				}
			} else if (argType === 'object') {
				if (arg.toString === Object.prototype.toString) {
					for (var key in arg) {
						if (hasOwn.call(arg, key) && arg[key]) {
							classes.push(key);
						}
					}
				} else {
					classes.push(arg.toString());
				}
			}
		}

		return classes.join(' ');
	}

	if ( true && module.exports) {
		classNames.default = classNames;
		module.exports = classNames;
	} else if (typeof define === 'function' && typeof define.amd === 'object' && define.amd) {
		// register as 'classnames', consistent with npm package name
		define('classnames', [], function () {
			return classNames;
		});
	} else {
		window.classNames = classNames;
	}
}());


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

/***/ 84:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "a", function() { return _assertThisInitialized; });
function _assertThisInitialized(self) {
  if (self === void 0) {
    throw new ReferenceError("this hasn't been initialised - super() hasn't been called");
  }

  return self;
}

/***/ }),

/***/ 85:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "a", function() { return removeClass; });
function replaceClassName(origClass, classToRemove) {
  return origClass.replace(new RegExp("(^|\\s)" + classToRemove + "(?:\\s|$)", 'g'), '$1').replace(/\s+/g, ' ').replace(/^\s*|\s*$/g, '');
}
/**
 * Removes a CSS class from a given element.
 * 
 * @param element the element
 * @param className the CSS class name
 */


function removeClass(element, className) {
  if (element.classList) {
    element.classList.remove(className);
  } else if (typeof element.className === 'string') {
    element.className = replaceClassName(element.className, className);
  } else {
    element.setAttribute('class', replaceClassName(element.className && element.className.baseVal || '', className));
  }
}

/***/ }),

/***/ 87:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";

// EXPORTS
__webpack_require__.d(__webpack_exports__, "a", function() { return /* binding */ addClass; });

// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/dom-helpers@5.2.1/node_modules/dom-helpers/esm/hasClass.js
/**
 * Checks if a given element has a CSS class.
 * 
 * @param element the element
 * @param className the CSS class name
 */
function hasClass(element, className) {
  if (element.classList) return !!className && element.classList.contains(className);
  return (" " + (element.className.baseVal || element.className) + " ").indexOf(" " + className + " ") !== -1;
}
// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/dom-helpers@5.2.1/node_modules/dom-helpers/esm/addClass.js

/**
 * Adds a CSS class to a given element.
 * 
 * @param element the element
 * @param className the CSS class name
 */

function addClass(element, className) {
  if (element.classList) element.classList.add(className);else if (!hasClass(element, className)) if (typeof element.className === 'string') element.className = element.className + " " + className;else element.setAttribute('class', (element.className && element.className.baseVal || '') + " " + className);
}

/***/ }),

/***/ 9:
/***/ (function(module, exports) {

(function() { module.exports = window["wp"]["primitives"]; }());

/***/ }),

/***/ 91:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(4);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(1);
/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(prop_types__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(7);
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(classnames__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _woocommerce_experimental__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(19);
/* harmony import */ var _woocommerce_experimental__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_experimental__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _style_scss__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(302);
/* harmony import */ var _style_scss__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_style_scss__WEBPACK_IMPORTED_MODULE_5__);


/**
 * External dependencies
 */




/**
 * Internal dependencies
 */



const Card = props => {
  const {
    title,
    description,
    children,
    className
  } = props;
  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__["Card"], {
    className: classnames__WEBPACK_IMPORTED_MODULE_3___default()(className, 'woocommerce-admin-marketing-card')
  }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__["CardHeader"], null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("div", null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_woocommerce_experimental__WEBPACK_IMPORTED_MODULE_4__["Text"], {
    variant: "title.small",
    as: "p",
    size: "20",
    lineHeight: "28px"
  }, title), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_woocommerce_experimental__WEBPACK_IMPORTED_MODULE_4__["Text"], {
    variant: "subtitle.small",
    as: "p",
    className: "woocommerce-admin-marketing-card-subtitle",
    size: "14",
    lineHeight: "20px"
  }, description))), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__["CardBody"], null, children));
};

Card.propTypes = {
  /**
   * Card title.
   */
  title: prop_types__WEBPACK_IMPORTED_MODULE_2___default.a.string,

  /**
   * Card description.
   */
  description: prop_types__WEBPACK_IMPORTED_MODULE_2___default.a.string,

  /**
   * Additional class name to style the component.
   */
  className: prop_types__WEBPACK_IMPORTED_MODULE_2___default.a.string,

  /**
   * A renderable component (or string) which will be displayed as the content of this item. Generally a `ToggleControl`.
   */
  children: prop_types__WEBPACK_IMPORTED_MODULE_2___default.a.node
};
/* harmony default export */ __webpack_exports__["a"] = (Card);

/***/ })

/******/ });