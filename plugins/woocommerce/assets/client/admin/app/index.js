this["wc"] = this["wc"] || {}; this["wc"]["app"] =
/******/ (function(modules) { // webpackBootstrap
/******/ 	// install a JSONP callback for chunk loading
/******/ 	function webpackJsonpCallback(data) {
/******/ 		var chunkIds = data[0];
/******/ 		var moreModules = data[1];
/******/
/******/
/******/ 		// add "moreModules" to the modules object,
/******/ 		// then flag all "chunkIds" as loaded and fire callback
/******/ 		var moduleId, chunkId, i = 0, resolves = [];
/******/ 		for(;i < chunkIds.length; i++) {
/******/ 			chunkId = chunkIds[i];
/******/ 			if(Object.prototype.hasOwnProperty.call(installedChunks, chunkId) && installedChunks[chunkId]) {
/******/ 				resolves.push(installedChunks[chunkId][0]);
/******/ 			}
/******/ 			installedChunks[chunkId] = 0;
/******/ 		}
/******/ 		for(moduleId in moreModules) {
/******/ 			if(Object.prototype.hasOwnProperty.call(moreModules, moduleId)) {
/******/ 				modules[moduleId] = moreModules[moduleId];
/******/ 			}
/******/ 		}
/******/ 		if(parentJsonpFunction) parentJsonpFunction(data);
/******/
/******/ 		while(resolves.length) {
/******/ 			resolves.shift()();
/******/ 		}
/******/
/******/ 	};
/******/
/******/
/******/ 	// The module cache
/******/ 	var installedModules = {};
/******/
/******/ 	// object to store loaded CSS chunks
/******/ 	var installedCssChunks = {
/******/ 		19: 0
/******/ 	}
/******/ 	var isCssRtlEnabled = function() {
/******/ 		return document.dir === 'rtl';
/******/ 	}
/******/
/******/ 	// object to store loaded and loading chunks
/******/ 	// undefined = chunk not loaded, null = chunk preloaded/prefetched
/******/ 	// Promise = chunk loading, 0 = chunk loaded
/******/ 	var installedChunks = {
/******/ 		19: 0
/******/ 	};
/******/
/******/
/******/
/******/ 	// script path function
/******/ 	function webpackJsonpScriptSrc(chunkId) {
/******/ 		return __webpack_require__.p + "chunks/" + ({"5":"activity-panels-help","6":"activity-panels-inbox","7":"analytics-report","8":"analytics-report-categories","9":"analytics-report-coupons","10":"analytics-report-customers","11":"analytics-report-downloads","12":"analytics-report-orders","13":"analytics-report-products","14":"analytics-report-revenue","15":"analytics-report-stock","16":"analytics-report-taxes","17":"analytics-report-variations","18":"analytics-settings","25":"customizable-dashboard","26":"dashboard","27":"dashboard-charts","32":"homescreen","33":"leaderboards","35":"marketing-overview","46":"payment-recommendations","48":"profile-wizard","49":"shipping-recommendations","50":"store-alerts","51":"store-performance","53":"two-column-tasks","54":"two-column-tasks-extended","55":"wcpay-payment-welcome-page","56":"wcpay-usage-modal"}[chunkId]||chunkId) + ".js"
/******/ 	}
/******/
/******/ 	function jsonpScriptSrc(chunkId) {
/******/ 		var src = webpackJsonpScriptSrc(chunkId);
/******/ 		if ( window.wcAdminAssets && window.wcAdminAssets.version ) {
/******/ 			src += '?ver=' + window.wcAdminAssets.version;
/******/ 		}
/******/ 		return src;
/******/ 	}
/******/
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
/******/ 	// This file contains only the entry chunk.
/******/ 	// The chunk loading function for additional chunks
/******/ 	__webpack_require__.e = function requireEnsure(chunkId) {
/******/ 		var promises = [];
/******/
/******/
/******/ 		// mini-css-extract-plugin CSS loading
/******/ 		var cssChunks = {"0":1,"3":1,"4":1,"5":1,"6":1,"7":1,"18":1,"26":1,"27":1,"32":1,"33":1,"35":1,"46":1,"48":1,"49":1,"50":1,"51":1,"53":1,"55":1};
/******/ 		if(installedCssChunks[chunkId]) promises.push(installedCssChunks[chunkId]);
/******/ 		else if(installedCssChunks[chunkId] !== 0 && cssChunks[chunkId]) {
/******/ 			promises.push(installedCssChunks[chunkId] = new Promise(function(resolve, reject) {
/******/ 				var href = false && isCssRtlEnabled() ? undefined : "chunks/" + chunkId + ".style.css";
/******/ 				var fullhref = __webpack_require__.p + href;
/******/ 				var existingLinkTags = document.getElementsByTagName("link");
/******/ 				for(var i = 0; i < existingLinkTags.length; i++) {
/******/ 					var tag = existingLinkTags[i];
/******/ 					var dataHref = tag.getAttribute("data-href") || tag.getAttribute("href");
/******/ 					if(tag.rel === "stylesheet" && (dataHref === href || dataHref === fullhref)) return resolve();
/******/ 				}
/******/ 				var existingStyleTags = document.getElementsByTagName("style");
/******/ 				for(var i = 0; i < existingStyleTags.length; i++) {
/******/ 					var tag = existingStyleTags[i];
/******/ 					var dataHref = tag.getAttribute("data-href");
/******/ 					if(dataHref === href || dataHref === fullhref) return resolve();
/******/ 				}
/******/ 				var linkTag = document.createElement("link");
/******/ 				linkTag.rel = "stylesheet";
/******/ 				linkTag.type = "text/css";
/******/ 				linkTag.setAttribute("data-webpack", true);
/******/ 				linkTag.onload = resolve;
/******/ 				linkTag.onerror = function(event) {
/******/ 					var request = event && event.target && event.target.src || fullhref;
/******/ 					var err = new Error("Loading CSS chunk " + chunkId + " failed.\n(" + request + ")");
/******/ 					err.code = "CSS_CHUNK_LOAD_FAILED";
/******/ 					err.request = request;
/******/ 					delete installedCssChunks[chunkId]
/******/ 					linkTag.parentNode.removeChild(linkTag)
/******/ 					reject(err);
/******/ 				};
/******/ 				linkTag.href = fullhref;
/******/ 		if ( window.wcAdminAssets && window.wcAdminAssets.version ) {
/******/ 			linkTag.href += '?ver=' + window.wcAdminAssets.version;
/******/ 		}
/******/
/******/ 				var head = document.getElementsByTagName("head")[0];
/******/ 				head.appendChild(linkTag);
/******/ 			}).then(function() {
/******/ 				installedCssChunks[chunkId] = 0;
/******/ 			}));
/******/ 		}
/******/
/******/ 		// JSONP chunk loading for javascript
/******/
/******/ 		var installedChunkData = installedChunks[chunkId];
/******/ 		if(installedChunkData !== 0) { // 0 means "already installed".
/******/
/******/ 			// a Promise means "currently loading".
/******/ 			if(installedChunkData) {
/******/ 				promises.push(installedChunkData[2]);
/******/ 			} else {
/******/ 				// setup Promise in chunk cache
/******/ 				var promise = new Promise(function(resolve, reject) {
/******/ 					installedChunkData = installedChunks[chunkId] = [resolve, reject];
/******/ 				});
/******/ 				promises.push(installedChunkData[2] = promise);
/******/
/******/ 				// start chunk loading
/******/ 				var script = document.createElement('script');
/******/ 				var onScriptComplete;
/******/
/******/ 				script.charset = 'utf-8';
/******/ 				script.timeout = 120;
/******/ 				if (__webpack_require__.nc) {
/******/ 					script.setAttribute("nonce", __webpack_require__.nc);
/******/ 				}
/******/ 				script.src = jsonpScriptSrc(chunkId);
/******/
/******/ 				// create error before stack unwound to get useful stacktrace later
/******/ 				var error = new Error();
/******/ 				onScriptComplete = function (event) {
/******/ 					// avoid mem leaks in IE.
/******/ 					script.onerror = script.onload = null;
/******/ 					clearTimeout(timeout);
/******/ 					var chunk = installedChunks[chunkId];
/******/ 					if(chunk !== 0) {
/******/ 						if(chunk) {
/******/ 							var errorType = event && (event.type === 'load' ? 'missing' : event.type);
/******/ 							var realSrc = event && event.target && event.target.src;
/******/ 							error.message = 'Loading chunk ' + chunkId + ' failed.\n(' + errorType + ': ' + realSrc + ')';
/******/ 							error.name = 'ChunkLoadError';
/******/ 							error.type = errorType;
/******/ 							error.request = realSrc;
/******/ 							chunk[1](error);
/******/ 						}
/******/ 						installedChunks[chunkId] = undefined;
/******/ 					}
/******/ 				};
/******/ 				var timeout = setTimeout(function(){
/******/ 					onScriptComplete({ type: 'timeout', target: script });
/******/ 				}, 120000);
/******/ 				script.onerror = script.onload = onScriptComplete;
/******/ 				document.head.appendChild(script);
/******/ 			}
/******/ 		}
/******/ 		return Promise.all(promises);
/******/ 	};
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
/******/ 	// on error function for async loading
/******/ 	__webpack_require__.oe = function(err) { console.error(err); throw err; };
/******/
/******/ 	var jsonpArray = window["__wcAdmin_webpackJsonp"] = window["__wcAdmin_webpackJsonp"] || [];
/******/ 	var oldJsonpFunction = jsonpArray.push.bind(jsonpArray);
/******/ 	jsonpArray.push = webpackJsonpCallback;
/******/ 	jsonpArray = jsonpArray.slice();
/******/ 	for(var i = 0; i < jsonpArray.length; i++) webpackJsonpCallback(jsonpArray[i]);
/******/ 	var parentJsonpFunction = oldJsonpFunction;
/******/
/******/
/******/ 	// Load entry module and return exports
/******/ 	return __webpack_require__(__webpack_require__.s = 308);
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

/***/ 101:
/***/ (function(module, exports) {

(function() { module.exports = window["wc"]["currency"]; }());

/***/ }),

/***/ 11:
/***/ (function(module, exports) {

(function() { module.exports = window["moment"]; }());

/***/ }),

/***/ 112:
/***/ (function(module, exports) {

(function() { module.exports = window["wp"]["dom"]; }());

/***/ }),

/***/ 115:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "a", function() { return REVIEW_PAGE_LIMIT; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "c", function() { return unapprovedReviewsQuery; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "b", function() { return getUnapprovedReviews; });
/* harmony import */ var _woocommerce_data__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(12);
/* harmony import */ var _woocommerce_data__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_data__WEBPACK_IMPORTED_MODULE_0__);
/**
 * External dependencies
 */

const REVIEW_PAGE_LIMIT = 5;
const unapprovedReviewsQuery = {
  page: 1,
  per_page: 1,
  status: 'hold',
  _embed: 1,
  _fields: ['id']
};
function getUnapprovedReviews(select) {
  const {
    getReviewsTotalCount,
    getReviewsError,
    isResolving
  } = select(_woocommerce_data__WEBPACK_IMPORTED_MODULE_0__["REVIEWS_STORE_NAME"]); // eslint-disable-next-line @wordpress/no-unused-vars-before-return

  const totalReviews = getReviewsTotalCount(unapprovedReviewsQuery);
  const isError = Boolean(getReviewsError(unapprovedReviewsQuery));
  const isRequesting = isResolving('getReviewsTotalCount', [unapprovedReviewsQuery]);

  if (isError || isRequesting && totalReviews === undefined) {
    return null;
  }

  return totalReviews;
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

/***/ 117:
/***/ (function(module, exports, __webpack_require__) {

"use strict";


if (true) {
  module.exports = __webpack_require__(146);
} else {}


/***/ }),

/***/ 12:
/***/ (function(module, exports) {

(function() { module.exports = window["wc"]["data"]; }());

/***/ }),

/***/ 13:
/***/ (function(module, exports) {

(function() { module.exports = window["wc"]["navigation"]; }());

/***/ }),

/***/ 132:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(2);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_hooks__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(27);
/* harmony import */ var _wordpress_hooks__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_hooks__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _utils_admin_settings__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(23);
/**
 * External dependencies
 */



/**
 * Internal dependencies
 */


const RevenueReport = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__["lazy"])(() => Promise.all(/* import() | analytics-report-revenue */[__webpack_require__.e(0), __webpack_require__.e(14)]).then(__webpack_require__.bind(null, 514)));
const ProductsReport = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__["lazy"])(() => Promise.all(/* import() | analytics-report-products */[__webpack_require__.e(0), __webpack_require__.e(3), __webpack_require__.e(13)]).then(__webpack_require__.bind(null, 509)));
const VariationsReport = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__["lazy"])(() => Promise.all(/* import() | analytics-report-variations */[__webpack_require__.e(0), __webpack_require__.e(17)]).then(__webpack_require__.bind(null, 515)));
const OrdersReport = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__["lazy"])(() => Promise.all(/* import() | analytics-report-orders */[__webpack_require__.e(0), __webpack_require__.e(12)]).then(__webpack_require__.bind(null, 516)));
const CategoriesReport = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__["lazy"])(() => Promise.all(/* import() | analytics-report-categories */[__webpack_require__.e(0), __webpack_require__.e(3), __webpack_require__.e(8)]).then(__webpack_require__.bind(null, 511)));
const CouponsReport = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__["lazy"])(() => Promise.all(/* import() | analytics-report-coupons */[__webpack_require__.e(0), __webpack_require__.e(9)]).then(__webpack_require__.bind(null, 517)));
const TaxesReport = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__["lazy"])(() => Promise.all(/* import() | analytics-report-taxes */[__webpack_require__.e(0), __webpack_require__.e(16)]).then(__webpack_require__.bind(null, 518)));
const DownloadsReport = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__["lazy"])(() => Promise.all(/* import() | analytics-report-downloads */[__webpack_require__.e(0), __webpack_require__.e(11)]).then(__webpack_require__.bind(null, 519)));
const StockReport = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__["lazy"])(() => Promise.all(/* import() | analytics-report-stock */[__webpack_require__.e(0), __webpack_require__.e(15)]).then(__webpack_require__.bind(null, 510)));
const CustomersReport = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__["lazy"])(() => Promise.all(/* import() | analytics-report-customers */[__webpack_require__.e(0), __webpack_require__.e(10)]).then(__webpack_require__.bind(null, 512)));
const manageStock = Object(_utils_admin_settings__WEBPACK_IMPORTED_MODULE_3__[/* getAdminSetting */ "d"])('manageStock', 'no');
const REPORTS_FILTER = 'woocommerce_admin_reports_list';
/* harmony default export */ __webpack_exports__["a"] = (() => {
  const reports = [{
    report: 'revenue',
    title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Revenue', 'woocommerce-admin'),
    component: RevenueReport,
    navArgs: {
      id: 'woocommerce-analytics-revenue'
    }
  }, {
    report: 'products',
    title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Products', 'woocommerce-admin'),
    component: ProductsReport,
    navArgs: {
      id: 'woocommerce-analytics-products'
    }
  }, {
    report: 'variations',
    title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Variations', 'woocommerce-admin'),
    component: VariationsReport,
    navArgs: {
      id: 'woocommerce-analytics-variations'
    }
  }, {
    report: 'orders',
    title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Orders', 'woocommerce-admin'),
    component: OrdersReport,
    navArgs: {
      id: 'woocommerce-analytics-orders'
    }
  }, {
    report: 'categories',
    title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Categories', 'woocommerce-admin'),
    component: CategoriesReport,
    navArgs: {
      id: 'woocommerce-analytics-categories'
    }
  }, {
    report: 'coupons',
    title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Coupons', 'woocommerce-admin'),
    component: CouponsReport,
    navArgs: {
      id: 'woocommerce-analytics-coupons'
    }
  }, {
    report: 'taxes',
    title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Taxes', 'woocommerce-admin'),
    component: TaxesReport,
    navArgs: {
      id: 'woocommerce-analytics-taxes'
    }
  }, manageStock === 'yes' ? {
    report: 'stock',
    title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Stock', 'woocommerce-admin'),
    component: StockReport,
    navArgs: {
      id: 'woocommerce-analytics-stock'
    }
  } : null, {
    report: 'customers',
    title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Customers', 'woocommerce-admin'),
    component: CustomersReport
  }, {
    report: 'downloads',
    title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Downloads', 'woocommerce-admin'),
    component: DownloadsReport,
    navArgs: {
      id: 'woocommerce-analytics-downloads'
    }
  }].filter(Boolean);
  /**
   * An object defining a report page.
   *
   * @typedef {Object} report
   * @property {string} report Report slug.
   * @property {string} title Report title.
   * @property {Node} component React Component to render.
   * @property {Object} navArgs Arguments supplied to WooCommerce Navigation.
   */

  /**
   * Filter Report pages list.
   *
   * @filter woocommerce_admin_reports_list
   * @param {Array.<report>} reports Report pages list.
   */

  return Object(_wordpress_hooks__WEBPACK_IMPORTED_MODULE_1__["applyFilters"])(REPORTS_FILTER, reports);
});

/***/ }),

/***/ 133:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* unused harmony export BailSignal */
/* unused harmony export Controller */
/* unused harmony export FrameValue */
/* unused harmony export Interpolation */
/* unused harmony export Spring */
/* unused harmony export SpringContext */
/* unused harmony export SpringRef */
/* unused harmony export SpringValue */
/* unused harmony export Trail */
/* unused harmony export Transition */
/* unused harmony export config */
/* unused harmony export easings */
/* unused harmony export inferTo */
/* unused harmony export interpolate */
/* unused harmony export to */
/* unused harmony export update */
/* unused harmony export useChain */
/* unused harmony export useSpring */
/* unused harmony export useSpringRef */
/* unused harmony export useSprings */
/* unused harmony export useTrail */
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "useTransition", function() { return useTransition; });
/* harmony import */ var _react_spring_shared__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(3);
/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "Globals", function() { return _react_spring_shared__WEBPACK_IMPORTED_MODULE_0__["b"]; });

/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(6);
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _react_spring_animated__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(32);
/* harmony import */ var _react_spring_types_animated__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(185);
/* harmony import */ var _react_spring_types_animated__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_react_spring_types_animated__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _react_spring_types_interpolation__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(186);
/* harmony import */ var _react_spring_types_interpolation__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_react_spring_types_interpolation__WEBPACK_IMPORTED_MODULE_4__);








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

function callProp(value, ...args) {
  return _react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* is */ "s"].fun(value) ? value(...args) : value;
}
const matchProp = (value, key) => value === true || !!(key && value && (_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* is */ "s"].fun(value) ? value(key) : Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* toArray */ "y"])(value).includes(key)));
const resolveProp = (prop, key) => _react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* is */ "s"].obj(prop) ? key && prop[key] : prop;
const getDefaultProp = (props, key) => props.default === true ? props[key] : props.default ? props.default[key] : undefined;

const noopTransform = value => value;

const getDefaultProps = (props, transform = noopTransform) => {
  let keys = DEFAULT_PROPS;

  if (props.default && props.default !== true) {
    props = props.default;
    keys = Object.keys(props);
  }

  const defaults = {};

  for (const key of keys) {
    const value = transform(props[key], key);

    if (!_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* is */ "s"].und(value)) {
      defaults[key] = value;
    }
  }

  return defaults;
};
const DEFAULT_PROPS = ['config', 'onProps', 'onStart', 'onChange', 'onPause', 'onResume', 'onRest'];
const RESERVED_PROPS = {
  config: 1,
  from: 1,
  to: 1,
  ref: 1,
  loop: 1,
  reset: 1,
  pause: 1,
  cancel: 1,
  reverse: 1,
  immediate: 1,
  default: 1,
  delay: 1,
  onProps: 1,
  onStart: 1,
  onChange: 1,
  onPause: 1,
  onResume: 1,
  onRest: 1,
  onResolve: 1,
  items: 1,
  trail: 1,
  sort: 1,
  expires: 1,
  initial: 1,
  enter: 1,
  update: 1,
  leave: 1,
  children: 1,
  onDestroyed: 1,
  keys: 1,
  callId: 1,
  parentId: 1
};

function getForwardProps(props) {
  const forward = {};
  let count = 0;
  Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* eachProp */ "l"])(props, (value, prop) => {
    if (!RESERVED_PROPS[prop]) {
      forward[prop] = value;
      count++;
    }
  });

  if (count) {
    return forward;
  }
}

function inferTo(props) {
  const to = getForwardProps(props);

  if (to) {
    const out = {
      to
    };
    Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* eachProp */ "l"])(props, (val, key) => key in to || (out[key] = val));
    return out;
  }

  return _extends({}, props);
}
function computeGoal(value) {
  value = Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* getFluidValue */ "q"])(value);
  return _react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* is */ "s"].arr(value) ? value.map(computeGoal) : Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* isAnimatedString */ "t"])(value) ? _react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* Globals */ "b"].createStringInterpolator({
    range: [0, 1],
    output: [value, value]
  })(1) : value;
}
function hasProps(props) {
  for (const _ in props) return true;

  return false;
}
function isAsyncTo(to) {
  return _react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* is */ "s"].fun(to) || _react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* is */ "s"].arr(to) && _react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* is */ "s"].obj(to[0]);
}
function detachRefs(ctrl, ref) {
  var _ctrl$ref;

  (_ctrl$ref = ctrl.ref) == null ? void 0 : _ctrl$ref.delete(ctrl);
  ref == null ? void 0 : ref.delete(ctrl);
}
function replaceRef(ctrl, ref) {
  if (ref && ctrl.ref !== ref) {
    var _ctrl$ref2;

    (_ctrl$ref2 = ctrl.ref) == null ? void 0 : _ctrl$ref2.delete(ctrl);
    ref.add(ctrl);
    ctrl.ref = ref;
  }
}

function useChain(refs, timeSteps, timeFrame = 1000) {
  Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* useLayoutEffect */ "A"])(() => {
    if (timeSteps) {
      let prevDelay = 0;
      Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* each */ "k"])(refs, (ref, i) => {
        const controllers = ref.current;

        if (controllers.length) {
          let delay = timeFrame * timeSteps[i];
          if (isNaN(delay)) delay = prevDelay;else prevDelay = delay;
          Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* each */ "k"])(controllers, ctrl => {
            Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* each */ "k"])(ctrl.queue, props => {
              const memoizedDelayProp = props.delay;

              props.delay = key => delay + callProp(memoizedDelayProp || 0, key);
            });
          });
          ref.start();
        }
      });
    } else {
      let p = Promise.resolve();
      Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* each */ "k"])(refs, ref => {
        const controllers = ref.current;

        if (controllers.length) {
          const queues = controllers.map(ctrl => {
            const q = ctrl.queue;
            ctrl.queue = [];
            return q;
          });
          p = p.then(() => {
            Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* each */ "k"])(controllers, (ctrl, i) => Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* each */ "k"])(queues[i] || [], update => ctrl.queue.push(update)));
            return Promise.all(ref.start());
          });
        }
      });
    }
  });
}

const config = {
  default: {
    tension: 170,
    friction: 26
  },
  gentle: {
    tension: 120,
    friction: 14
  },
  wobbly: {
    tension: 180,
    friction: 12
  },
  stiff: {
    tension: 210,
    friction: 20
  },
  slow: {
    tension: 280,
    friction: 60
  },
  molasses: {
    tension: 280,
    friction: 120
  }
};
const c1 = 1.70158;
const c2 = c1 * 1.525;
const c3 = c1 + 1;
const c4 = 2 * Math.PI / 3;
const c5 = 2 * Math.PI / 4.5;

const bounceOut = x => {
  const n1 = 7.5625;
  const d1 = 2.75;

  if (x < 1 / d1) {
    return n1 * x * x;
  } else if (x < 2 / d1) {
    return n1 * (x -= 1.5 / d1) * x + 0.75;
  } else if (x < 2.5 / d1) {
    return n1 * (x -= 2.25 / d1) * x + 0.9375;
  } else {
    return n1 * (x -= 2.625 / d1) * x + 0.984375;
  }
};

const easings = {
  linear: x => x,
  easeInQuad: x => x * x,
  easeOutQuad: x => 1 - (1 - x) * (1 - x),
  easeInOutQuad: x => x < 0.5 ? 2 * x * x : 1 - Math.pow(-2 * x + 2, 2) / 2,
  easeInCubic: x => x * x * x,
  easeOutCubic: x => 1 - Math.pow(1 - x, 3),
  easeInOutCubic: x => x < 0.5 ? 4 * x * x * x : 1 - Math.pow(-2 * x + 2, 3) / 2,
  easeInQuart: x => x * x * x * x,
  easeOutQuart: x => 1 - Math.pow(1 - x, 4),
  easeInOutQuart: x => x < 0.5 ? 8 * x * x * x * x : 1 - Math.pow(-2 * x + 2, 4) / 2,
  easeInQuint: x => x * x * x * x * x,
  easeOutQuint: x => 1 - Math.pow(1 - x, 5),
  easeInOutQuint: x => x < 0.5 ? 16 * x * x * x * x * x : 1 - Math.pow(-2 * x + 2, 5) / 2,
  easeInSine: x => 1 - Math.cos(x * Math.PI / 2),
  easeOutSine: x => Math.sin(x * Math.PI / 2),
  easeInOutSine: x => -(Math.cos(Math.PI * x) - 1) / 2,
  easeInExpo: x => x === 0 ? 0 : Math.pow(2, 10 * x - 10),
  easeOutExpo: x => x === 1 ? 1 : 1 - Math.pow(2, -10 * x),
  easeInOutExpo: x => x === 0 ? 0 : x === 1 ? 1 : x < 0.5 ? Math.pow(2, 20 * x - 10) / 2 : (2 - Math.pow(2, -20 * x + 10)) / 2,
  easeInCirc: x => 1 - Math.sqrt(1 - Math.pow(x, 2)),
  easeOutCirc: x => Math.sqrt(1 - Math.pow(x - 1, 2)),
  easeInOutCirc: x => x < 0.5 ? (1 - Math.sqrt(1 - Math.pow(2 * x, 2))) / 2 : (Math.sqrt(1 - Math.pow(-2 * x + 2, 2)) + 1) / 2,
  easeInBack: x => c3 * x * x * x - c1 * x * x,
  easeOutBack: x => 1 + c3 * Math.pow(x - 1, 3) + c1 * Math.pow(x - 1, 2),
  easeInOutBack: x => x < 0.5 ? Math.pow(2 * x, 2) * ((c2 + 1) * 2 * x - c2) / 2 : (Math.pow(2 * x - 2, 2) * ((c2 + 1) * (x * 2 - 2) + c2) + 2) / 2,
  easeInElastic: x => x === 0 ? 0 : x === 1 ? 1 : -Math.pow(2, 10 * x - 10) * Math.sin((x * 10 - 10.75) * c4),
  easeOutElastic: x => x === 0 ? 0 : x === 1 ? 1 : Math.pow(2, -10 * x) * Math.sin((x * 10 - 0.75) * c4) + 1,
  easeInOutElastic: x => x === 0 ? 0 : x === 1 ? 1 : x < 0.5 ? -(Math.pow(2, 20 * x - 10) * Math.sin((20 * x - 11.125) * c5)) / 2 : Math.pow(2, -20 * x + 10) * Math.sin((20 * x - 11.125) * c5) / 2 + 1,
  easeInBounce: x => 1 - bounceOut(1 - x),
  easeOutBounce: bounceOut,
  easeInOutBounce: x => x < 0.5 ? (1 - bounceOut(1 - 2 * x)) / 2 : (1 + bounceOut(2 * x - 1)) / 2
};

const defaults = _extends({}, config.default, {
  mass: 1,
  damping: 1,
  easing: easings.linear,
  clamp: false
});

class AnimationConfig {
  constructor() {
    this.tension = void 0;
    this.friction = void 0;
    this.frequency = void 0;
    this.damping = void 0;
    this.mass = void 0;
    this.velocity = 0;
    this.restVelocity = void 0;
    this.precision = void 0;
    this.progress = void 0;
    this.duration = void 0;
    this.easing = void 0;
    this.clamp = void 0;
    this.bounce = void 0;
    this.decay = void 0;
    this.round = void 0;
    Object.assign(this, defaults);
  }

}
function mergeConfig(config, newConfig, defaultConfig) {
  if (defaultConfig) {
    defaultConfig = _extends({}, defaultConfig);
    sanitizeConfig(defaultConfig, newConfig);
    newConfig = _extends({}, defaultConfig, newConfig);
  }

  sanitizeConfig(config, newConfig);
  Object.assign(config, newConfig);

  for (const key in defaults) {
    if (config[key] == null) {
      config[key] = defaults[key];
    }
  }

  let {
    mass,
    frequency,
    damping
  } = config;

  if (!_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* is */ "s"].und(frequency)) {
    if (frequency < 0.01) frequency = 0.01;
    if (damping < 0) damping = 0;
    config.tension = Math.pow(2 * Math.PI / frequency, 2) * mass;
    config.friction = 4 * Math.PI * damping * mass / frequency;
  }

  return config;
}

function sanitizeConfig(config, props) {
  if (!_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* is */ "s"].und(props.decay)) {
    config.duration = undefined;
  } else {
    const isTensionConfig = !_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* is */ "s"].und(props.tension) || !_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* is */ "s"].und(props.friction);

    if (isTensionConfig || !_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* is */ "s"].und(props.frequency) || !_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* is */ "s"].und(props.damping) || !_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* is */ "s"].und(props.mass)) {
      config.duration = undefined;
      config.decay = undefined;
    }

    if (isTensionConfig) {
      config.frequency = undefined;
    }
  }
}

const emptyArray = [];
class Animation {
  constructor() {
    this.changed = false;
    this.values = emptyArray;
    this.toValues = null;
    this.fromValues = emptyArray;
    this.to = void 0;
    this.from = void 0;
    this.config = new AnimationConfig();
    this.immediate = false;
  }

}

function scheduleProps(callId, {
  key,
  props,
  defaultProps,
  state,
  actions
}) {
  return new Promise((resolve, reject) => {
    var _props$cancel;

    let delay;
    let timeout;
    let cancel = matchProp((_props$cancel = props.cancel) != null ? _props$cancel : defaultProps == null ? void 0 : defaultProps.cancel, key);

    if (cancel) {
      onStart();
    } else {
      if (!_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* is */ "s"].und(props.pause)) {
        state.paused = matchProp(props.pause, key);
      }

      let pause = defaultProps == null ? void 0 : defaultProps.pause;

      if (pause !== true) {
        pause = state.paused || matchProp(pause, key);
      }

      delay = callProp(props.delay || 0, key);

      if (pause) {
        state.resumeQueue.add(onResume);
        actions.pause();
      } else {
        actions.resume();
        onResume();
      }
    }

    function onPause() {
      state.resumeQueue.add(onResume);
      state.timeouts.delete(timeout);
      timeout.cancel();
      delay = timeout.time - _react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* raf */ "w"].now();
    }

    function onResume() {
      if (delay > 0 && !_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* Globals */ "b"].skipAnimation) {
        state.delayed = true;
        timeout = _react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* raf */ "w"].setTimeout(onStart, delay);
        state.pauseQueue.add(onPause);
        state.timeouts.add(timeout);
      } else {
        onStart();
      }
    }

    function onStart() {
      if (state.delayed) {
        state.delayed = false;
      }

      state.pauseQueue.delete(onPause);
      state.timeouts.delete(timeout);

      if (callId <= (state.cancelId || 0)) {
        cancel = true;
      }

      try {
        actions.start(_extends({}, props, {
          callId,
          cancel
        }), resolve);
      } catch (err) {
        reject(err);
      }
    }
  });
}

const getCombinedResult = (target, results) => results.length == 1 ? results[0] : results.some(result => result.cancelled) ? getCancelledResult(target.get()) : results.every(result => result.noop) ? getNoopResult(target.get()) : getFinishedResult(target.get(), results.every(result => result.finished));
const getNoopResult = value => ({
  value,
  noop: true,
  finished: true,
  cancelled: false
});
const getFinishedResult = (value, finished, cancelled = false) => ({
  value,
  finished,
  cancelled
});
const getCancelledResult = value => ({
  value,
  cancelled: true,
  finished: false
});

function runAsync(to, props, state, target) {
  const {
    callId,
    parentId,
    onRest
  } = props;
  const {
    asyncTo: prevTo,
    promise: prevPromise
  } = state;

  if (!parentId && to === prevTo && !props.reset) {
    return prevPromise;
  }

  return state.promise = (async () => {
    state.asyncId = callId;
    state.asyncTo = to;
    const defaultProps = getDefaultProps(props, (value, key) => key === 'onRest' ? undefined : value);
    let preventBail;
    let bail;
    const bailPromise = new Promise((resolve, reject) => (preventBail = resolve, bail = reject));

    const bailIfEnded = bailSignal => {
      const bailResult = callId <= (state.cancelId || 0) && getCancelledResult(target) || callId !== state.asyncId && getFinishedResult(target, false);

      if (bailResult) {
        bailSignal.result = bailResult;
        bail(bailSignal);
        throw bailSignal;
      }
    };

    const animate = (arg1, arg2) => {
      const bailSignal = new BailSignal();
      const skipAnimationSignal = new SkipAniamtionSignal();
      return (async () => {
        if (_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* Globals */ "b"].skipAnimation) {
          stopAsync(state);
          skipAnimationSignal.result = getFinishedResult(target, false);
          bail(skipAnimationSignal);
          throw skipAnimationSignal;
        }

        bailIfEnded(bailSignal);
        const props = _react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* is */ "s"].obj(arg1) ? _extends({}, arg1) : _extends({}, arg2, {
          to: arg1
        });
        props.parentId = callId;
        Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* eachProp */ "l"])(defaultProps, (value, key) => {
          if (_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* is */ "s"].und(props[key])) {
            props[key] = value;
          }
        });
        const result = await target.start(props);
        bailIfEnded(bailSignal);

        if (state.paused) {
          await new Promise(resume => {
            state.resumeQueue.add(resume);
          });
        }

        return result;
      })();
    };

    let result;

    if (_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* Globals */ "b"].skipAnimation) {
      stopAsync(state);
      return getFinishedResult(target, false);
    }

    try {
      let animating;

      if (_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* is */ "s"].arr(to)) {
        animating = (async queue => {
          for (const props of queue) {
            await animate(props);
          }
        })(to);
      } else {
          animating = Promise.resolve(to(animate, target.stop.bind(target)));
        }

      await Promise.all([animating.then(preventBail), bailPromise]);
      result = getFinishedResult(target.get(), true, false);
    } catch (err) {
      if (err instanceof BailSignal) {
        result = err.result;
      } else if (err instanceof SkipAniamtionSignal) {
        result = err.result;
      } else {
        throw err;
      }
    } finally {
      if (callId == state.asyncId) {
        state.asyncId = parentId;
        state.asyncTo = parentId ? prevTo : undefined;
        state.promise = parentId ? prevPromise : undefined;
      }
    }

    if (_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* is */ "s"].fun(onRest)) {
      _react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* raf */ "w"].batchedUpdates(() => {
        onRest(result, target, target.item);
      });
    }

    return result;
  })();
}
function stopAsync(state, cancelId) {
  Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* flush */ "m"])(state.timeouts, t => t.cancel());
  state.pauseQueue.clear();
  state.resumeQueue.clear();
  state.asyncId = state.asyncTo = state.promise = undefined;
  if (cancelId) state.cancelId = cancelId;
}
class BailSignal extends Error {
  constructor() {
    super('An async animation has been interrupted. You see this error because you ' + 'forgot to use `await` or `.catch(...)` on its returned promise.');
    this.result = void 0;
  }

}
class SkipAniamtionSignal extends Error {
  constructor() {
    super('SkipAnimationSignal');
    this.result = void 0;
  }

}

const isFrameValue = value => value instanceof FrameValue;
let nextId$1 = 1;
class FrameValue extends _react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* FluidValue */ "a"] {
  constructor(...args) {
    super(...args);
    this.id = nextId$1++;
    this.key = void 0;
    this._priority = 0;
  }

  get priority() {
    return this._priority;
  }

  set priority(priority) {
    if (this._priority != priority) {
      this._priority = priority;

      this._onPriorityChange(priority);
    }
  }

  get() {
    const node = Object(_react_spring_animated__WEBPACK_IMPORTED_MODULE_2__[/* getAnimated */ "e"])(this);
    return node && node.getValue();
  }

  to(...args) {
    return _react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* Globals */ "b"].to(this, args);
  }

  interpolate(...args) {
    Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* deprecateInterpolate */ "j"])();
    return _react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* Globals */ "b"].to(this, args);
  }

  toJSON() {
    return this.get();
  }

  observerAdded(count) {
    if (count == 1) this._attach();
  }

  observerRemoved(count) {
    if (count == 0) this._detach();
  }

  _attach() {}

  _detach() {}

  _onChange(value, idle = false) {
    Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* callFluidObservers */ "d"])(this, {
      type: 'change',
      parent: this,
      value,
      idle
    });
  }

  _onPriorityChange(priority) {
    if (!this.idle) {
      _react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* frameLoop */ "o"].sort(this);
    }

    Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* callFluidObservers */ "d"])(this, {
      type: 'priority',
      parent: this,
      priority
    });
  }

}

const $P = Symbol.for('SpringPhase');
const HAS_ANIMATED = 1;
const IS_ANIMATING = 2;
const IS_PAUSED = 4;
const hasAnimated = target => (target[$P] & HAS_ANIMATED) > 0;
const isAnimating = target => (target[$P] & IS_ANIMATING) > 0;
const isPaused = target => (target[$P] & IS_PAUSED) > 0;
const setActiveBit = (target, active) => active ? target[$P] |= IS_ANIMATING | HAS_ANIMATED : target[$P] &= ~IS_ANIMATING;
const setPausedBit = (target, paused) => paused ? target[$P] |= IS_PAUSED : target[$P] &= ~IS_PAUSED;

class SpringValue extends FrameValue {
  constructor(arg1, arg2) {
    super();
    this.key = void 0;
    this.animation = new Animation();
    this.queue = void 0;
    this.defaultProps = {};
    this._state = {
      paused: false,
      delayed: false,
      pauseQueue: new Set(),
      resumeQueue: new Set(),
      timeouts: new Set()
    };
    this._pendingCalls = new Set();
    this._lastCallId = 0;
    this._lastToId = 0;
    this._memoizedDuration = 0;

    if (!_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* is */ "s"].und(arg1) || !_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* is */ "s"].und(arg2)) {
      const props = _react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* is */ "s"].obj(arg1) ? _extends({}, arg1) : _extends({}, arg2, {
        from: arg1
      });

      if (_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* is */ "s"].und(props.default)) {
        props.default = true;
      }

      this.start(props);
    }
  }

  get idle() {
    return !(isAnimating(this) || this._state.asyncTo) || isPaused(this);
  }

  get goal() {
    return Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* getFluidValue */ "q"])(this.animation.to);
  }

  get velocity() {
    const node = Object(_react_spring_animated__WEBPACK_IMPORTED_MODULE_2__[/* getAnimated */ "e"])(this);
    return node instanceof _react_spring_animated__WEBPACK_IMPORTED_MODULE_2__[/* AnimatedValue */ "c"] ? node.lastVelocity || 0 : node.getPayload().map(node => node.lastVelocity || 0);
  }

  get hasAnimated() {
    return hasAnimated(this);
  }

  get isAnimating() {
    return isAnimating(this);
  }

  get isPaused() {
    return isPaused(this);
  }

  get isDelayed() {
    return this._state.delayed;
  }

  advance(dt) {
    let idle = true;
    let changed = false;
    const anim = this.animation;
    let {
      config,
      toValues
    } = anim;
    const payload = Object(_react_spring_animated__WEBPACK_IMPORTED_MODULE_2__[/* getPayload */ "g"])(anim.to);

    if (!payload && Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* hasFluidValue */ "r"])(anim.to)) {
      toValues = Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* toArray */ "y"])(Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* getFluidValue */ "q"])(anim.to));
    }

    anim.values.forEach((node, i) => {
      if (node.done) return;
      const to = node.constructor == _react_spring_animated__WEBPACK_IMPORTED_MODULE_2__[/* AnimatedString */ "b"] ? 1 : payload ? payload[i].lastPosition : toValues[i];
      let finished = anim.immediate;
      let position = to;

      if (!finished) {
        position = node.lastPosition;

        if (config.tension <= 0) {
          node.done = true;
          return;
        }

        let elapsed = node.elapsedTime += dt;
        const from = anim.fromValues[i];
        const v0 = node.v0 != null ? node.v0 : node.v0 = _react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* is */ "s"].arr(config.velocity) ? config.velocity[i] : config.velocity;
        let velocity;

        if (!_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* is */ "s"].und(config.duration)) {
          let p = 1;

          if (config.duration > 0) {
            if (this._memoizedDuration !== config.duration) {
              this._memoizedDuration = config.duration;

              if (node.durationProgress > 0) {
                node.elapsedTime = config.duration * node.durationProgress;
                elapsed = node.elapsedTime += dt;
              }
            }

            p = (config.progress || 0) + elapsed / this._memoizedDuration;
            p = p > 1 ? 1 : p < 0 ? 0 : p;
            node.durationProgress = p;
          }

          position = from + config.easing(p) * (to - from);
          velocity = (position - node.lastPosition) / dt;
          finished = p == 1;
        } else if (config.decay) {
            const decay = config.decay === true ? 0.998 : config.decay;
            const e = Math.exp(-(1 - decay) * elapsed);
            position = from + v0 / (1 - decay) * (1 - e);
            finished = Math.abs(node.lastPosition - position) < 0.1;
            velocity = v0 * e;
          } else {
              velocity = node.lastVelocity == null ? v0 : node.lastVelocity;
              const precision = config.precision || (from == to ? 0.005 : Math.min(1, Math.abs(to - from) * 0.001));
              const restVelocity = config.restVelocity || precision / 10;
              const bounceFactor = config.clamp ? 0 : config.bounce;
              const canBounce = !_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* is */ "s"].und(bounceFactor);
              const isGrowing = from == to ? node.v0 > 0 : from < to;
              let isMoving;
              let isBouncing = false;
              const step = 1;
              const numSteps = Math.ceil(dt / step);

              for (let n = 0; n < numSteps; ++n) {
                isMoving = Math.abs(velocity) > restVelocity;

                if (!isMoving) {
                  finished = Math.abs(to - position) <= precision;

                  if (finished) {
                    break;
                  }
                }

                if (canBounce) {
                  isBouncing = position == to || position > to == isGrowing;

                  if (isBouncing) {
                    velocity = -velocity * bounceFactor;
                    position = to;
                  }
                }

                const springForce = -config.tension * 0.000001 * (position - to);
                const dampingForce = -config.friction * 0.001 * velocity;
                const acceleration = (springForce + dampingForce) / config.mass;
                velocity = velocity + acceleration * step;
                position = position + velocity * step;
              }
            }

        node.lastVelocity = velocity;

        if (Number.isNaN(position)) {
          console.warn(`Got NaN while animating:`, this);
          finished = true;
        }
      }

      if (payload && !payload[i].done) {
        finished = false;
      }

      if (finished) {
        node.done = true;
      } else {
        idle = false;
      }

      if (node.setValue(position, config.round)) {
        changed = true;
      }
    });
    const node = Object(_react_spring_animated__WEBPACK_IMPORTED_MODULE_2__[/* getAnimated */ "e"])(this);
    const currVal = node.getValue();

    if (idle) {
      const finalVal = Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* getFluidValue */ "q"])(anim.to);

      if ((currVal !== finalVal || changed) && !config.decay) {
        node.setValue(finalVal);

        this._onChange(finalVal);
      } else if (changed && config.decay) {
        this._onChange(currVal);
      }

      this._stop();
    } else if (changed) {
      this._onChange(currVal);
    }
  }

  set(value) {
    _react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* raf */ "w"].batchedUpdates(() => {
      this._stop();

      this._focus(value);

      this._set(value);
    });
    return this;
  }

  pause() {
    this._update({
      pause: true
    });
  }

  resume() {
    this._update({
      pause: false
    });
  }

  finish() {
    if (isAnimating(this)) {
      const {
        to,
        config
      } = this.animation;
      _react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* raf */ "w"].batchedUpdates(() => {
        this._onStart();

        if (!config.decay) {
          this._set(to, false);
        }

        this._stop();
      });
    }

    return this;
  }

  update(props) {
    const queue = this.queue || (this.queue = []);
    queue.push(props);
    return this;
  }

  start(to, arg2) {
    let queue;

    if (!_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* is */ "s"].und(to)) {
      queue = [_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* is */ "s"].obj(to) ? to : _extends({}, arg2, {
        to
      })];
    } else {
      queue = this.queue || [];
      this.queue = [];
    }

    return Promise.all(queue.map(props => {
      const up = this._update(props);

      return up;
    })).then(results => getCombinedResult(this, results));
  }

  stop(cancel) {
    const {
      to
    } = this.animation;

    this._focus(this.get());

    stopAsync(this._state, cancel && this._lastCallId);
    _react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* raf */ "w"].batchedUpdates(() => this._stop(to, cancel));
    return this;
  }

  reset() {
    this._update({
      reset: true
    });
  }

  eventObserved(event) {
    if (event.type == 'change') {
      this._start();
    } else if (event.type == 'priority') {
      this.priority = event.priority + 1;
    }
  }

  _prepareNode(props) {
    const key = this.key || '';
    let {
      to,
      from
    } = props;
    to = _react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* is */ "s"].obj(to) ? to[key] : to;

    if (to == null || isAsyncTo(to)) {
      to = undefined;
    }

    from = _react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* is */ "s"].obj(from) ? from[key] : from;

    if (from == null) {
      from = undefined;
    }

    const range = {
      to,
      from
    };

    if (!hasAnimated(this)) {
      if (props.reverse) [to, from] = [from, to];
      from = Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* getFluidValue */ "q"])(from);

      if (!_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* is */ "s"].und(from)) {
        this._set(from);
      } else if (!Object(_react_spring_animated__WEBPACK_IMPORTED_MODULE_2__[/* getAnimated */ "e"])(this)) {
          this._set(to);
        }
    }

    return range;
  }

  _update(_ref, isLoop) {
    let props = _extends({}, _ref);

    const {
      key,
      defaultProps
    } = this;
    if (props.default) Object.assign(defaultProps, getDefaultProps(props, (value, prop) => /^on/.test(prop) ? resolveProp(value, key) : value));
    mergeActiveFn(this, props, 'onProps');
    sendEvent(this, 'onProps', props, this);

    const range = this._prepareNode(props);

    if (Object.isFrozen(this)) {
      throw Error('Cannot animate a `SpringValue` object that is frozen. ' + 'Did you forget to pass your component to `animated(...)` before animating its props?');
    }

    const state = this._state;
    return scheduleProps(++this._lastCallId, {
      key,
      props,
      defaultProps,
      state,
      actions: {
        pause: () => {
          if (!isPaused(this)) {
            setPausedBit(this, true);
            Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* flushCalls */ "n"])(state.pauseQueue);
            sendEvent(this, 'onPause', getFinishedResult(this, checkFinished(this, this.animation.to)), this);
          }
        },
        resume: () => {
          if (isPaused(this)) {
            setPausedBit(this, false);

            if (isAnimating(this)) {
              this._resume();
            }

            Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* flushCalls */ "n"])(state.resumeQueue);
            sendEvent(this, 'onResume', getFinishedResult(this, checkFinished(this, this.animation.to)), this);
          }
        },
        start: this._merge.bind(this, range)
      }
    }).then(result => {
      if (props.loop && result.finished && !(isLoop && result.noop)) {
        const nextProps = createLoopUpdate(props);

        if (nextProps) {
          return this._update(nextProps, true);
        }
      }

      return result;
    });
  }

  _merge(range, props, resolve) {
    if (props.cancel) {
      this.stop(true);
      return resolve(getCancelledResult(this));
    }

    const hasToProp = !_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* is */ "s"].und(range.to);
    const hasFromProp = !_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* is */ "s"].und(range.from);

    if (hasToProp || hasFromProp) {
      if (props.callId > this._lastToId) {
        this._lastToId = props.callId;
      } else {
        return resolve(getCancelledResult(this));
      }
    }

    const {
      key,
      defaultProps,
      animation: anim
    } = this;
    const {
      to: prevTo,
      from: prevFrom
    } = anim;
    let {
      to = prevTo,
      from = prevFrom
    } = range;

    if (hasFromProp && !hasToProp && (!props.default || _react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* is */ "s"].und(to))) {
      to = from;
    }

    if (props.reverse) [to, from] = [from, to];
    const hasFromChanged = !Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* isEqual */ "u"])(from, prevFrom);

    if (hasFromChanged) {
      anim.from = from;
    }

    from = Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* getFluidValue */ "q"])(from);
    const hasToChanged = !Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* isEqual */ "u"])(to, prevTo);

    if (hasToChanged) {
      this._focus(to);
    }

    const hasAsyncTo = isAsyncTo(props.to);
    const {
      config
    } = anim;
    const {
      decay,
      velocity
    } = config;

    if (hasToProp || hasFromProp) {
      config.velocity = 0;
    }

    if (props.config && !hasAsyncTo) {
      mergeConfig(config, callProp(props.config, key), props.config !== defaultProps.config ? callProp(defaultProps.config, key) : void 0);
    }

    let node = Object(_react_spring_animated__WEBPACK_IMPORTED_MODULE_2__[/* getAnimated */ "e"])(this);

    if (!node || _react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* is */ "s"].und(to)) {
      return resolve(getFinishedResult(this, true));
    }

    const reset = _react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* is */ "s"].und(props.reset) ? hasFromProp && !props.default : !_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* is */ "s"].und(from) && matchProp(props.reset, key);
    const value = reset ? from : this.get();
    const goal = computeGoal(to);
    const isAnimatable = _react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* is */ "s"].num(goal) || _react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* is */ "s"].arr(goal) || Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* isAnimatedString */ "t"])(goal);
    const immediate = !hasAsyncTo && (!isAnimatable || matchProp(defaultProps.immediate || props.immediate, key));

    if (hasToChanged) {
      const nodeType = Object(_react_spring_animated__WEBPACK_IMPORTED_MODULE_2__[/* getAnimatedType */ "f"])(to);

      if (nodeType !== node.constructor) {
        if (immediate) {
          node = this._set(goal);
        } else throw Error(`Cannot animate between ${node.constructor.name} and ${nodeType.name}, as the "to" prop suggests`);
      }
    }

    const goalType = node.constructor;
    let started = Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* hasFluidValue */ "r"])(to);
    let finished = false;

    if (!started) {
      const hasValueChanged = reset || !hasAnimated(this) && hasFromChanged;

      if (hasToChanged || hasValueChanged) {
        finished = Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* isEqual */ "u"])(computeGoal(value), goal);
        started = !finished;
      }

      if (!Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* isEqual */ "u"])(anim.immediate, immediate) && !immediate || !Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* isEqual */ "u"])(config.decay, decay) || !Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* isEqual */ "u"])(config.velocity, velocity)) {
        started = true;
      }
    }

    if (finished && isAnimating(this)) {
      if (anim.changed && !reset) {
        started = true;
      } else if (!started) {
          this._stop(prevTo);
        }
    }

    if (!hasAsyncTo) {
      if (started || Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* hasFluidValue */ "r"])(prevTo)) {
        anim.values = node.getPayload();
        anim.toValues = Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* hasFluidValue */ "r"])(to) ? null : goalType == _react_spring_animated__WEBPACK_IMPORTED_MODULE_2__[/* AnimatedString */ "b"] ? [1] : Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* toArray */ "y"])(goal);
      }

      if (anim.immediate != immediate) {
        anim.immediate = immediate;

        if (!immediate && !reset) {
          this._set(prevTo);
        }
      }

      if (started) {
        const {
          onRest
        } = anim;
        Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* each */ "k"])(ACTIVE_EVENTS, type => mergeActiveFn(this, props, type));
        const result = getFinishedResult(this, checkFinished(this, prevTo));
        Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* flushCalls */ "n"])(this._pendingCalls, result);

        this._pendingCalls.add(resolve);

        if (anim.changed) _react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* raf */ "w"].batchedUpdates(() => {
          anim.changed = !reset;
          onRest == null ? void 0 : onRest(result, this);

          if (reset) {
            callProp(defaultProps.onRest, result);
          } else {
              anim.onStart == null ? void 0 : anim.onStart(result, this);
            }
        });
      }
    }

    if (reset) {
      this._set(value);
    }

    if (hasAsyncTo) {
      resolve(runAsync(props.to, props, this._state, this));
    } else if (started) {
        this._start();
      } else if (isAnimating(this) && !hasToChanged) {
          this._pendingCalls.add(resolve);
        } else {
            resolve(getNoopResult(value));
          }
  }

  _focus(value) {
    const anim = this.animation;

    if (value !== anim.to) {
      if (Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* getFluidObservers */ "p"])(this)) {
        this._detach();
      }

      anim.to = value;

      if (Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* getFluidObservers */ "p"])(this)) {
        this._attach();
      }
    }
  }

  _attach() {
    let priority = 0;
    const {
      to
    } = this.animation;

    if (Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* hasFluidValue */ "r"])(to)) {
      Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* addFluidObserver */ "c"])(to, this);

      if (isFrameValue(to)) {
        priority = to.priority + 1;
      }
    }

    this.priority = priority;
  }

  _detach() {
    const {
      to
    } = this.animation;

    if (Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* hasFluidValue */ "r"])(to)) {
      Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* removeFluidObserver */ "x"])(to, this);
    }
  }

  _set(arg, idle = true) {
    const value = Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* getFluidValue */ "q"])(arg);

    if (!_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* is */ "s"].und(value)) {
      const oldNode = Object(_react_spring_animated__WEBPACK_IMPORTED_MODULE_2__[/* getAnimated */ "e"])(this);

      if (!oldNode || !Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* isEqual */ "u"])(value, oldNode.getValue())) {
        const nodeType = Object(_react_spring_animated__WEBPACK_IMPORTED_MODULE_2__[/* getAnimatedType */ "f"])(value);

        if (!oldNode || oldNode.constructor != nodeType) {
          Object(_react_spring_animated__WEBPACK_IMPORTED_MODULE_2__[/* setAnimated */ "h"])(this, nodeType.create(value));
        } else {
          oldNode.setValue(value);
        }

        if (oldNode) {
          _react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* raf */ "w"].batchedUpdates(() => {
            this._onChange(value, idle);
          });
        }
      }
    }

    return Object(_react_spring_animated__WEBPACK_IMPORTED_MODULE_2__[/* getAnimated */ "e"])(this);
  }

  _onStart() {
    const anim = this.animation;

    if (!anim.changed) {
      anim.changed = true;
      sendEvent(this, 'onStart', getFinishedResult(this, checkFinished(this, anim.to)), this);
    }
  }

  _onChange(value, idle) {
    if (!idle) {
      this._onStart();

      callProp(this.animation.onChange, value, this);
    }

    callProp(this.defaultProps.onChange, value, this);

    super._onChange(value, idle);
  }

  _start() {
    const anim = this.animation;
    Object(_react_spring_animated__WEBPACK_IMPORTED_MODULE_2__[/* getAnimated */ "e"])(this).reset(Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* getFluidValue */ "q"])(anim.to));

    if (!anim.immediate) {
      anim.fromValues = anim.values.map(node => node.lastPosition);
    }

    if (!isAnimating(this)) {
      setActiveBit(this, true);

      if (!isPaused(this)) {
        this._resume();
      }
    }
  }

  _resume() {
    if (_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* Globals */ "b"].skipAnimation) {
      this.finish();
    } else {
      _react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* frameLoop */ "o"].start(this);
    }
  }

  _stop(goal, cancel) {
    if (isAnimating(this)) {
      setActiveBit(this, false);
      const anim = this.animation;
      Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* each */ "k"])(anim.values, node => {
        node.done = true;
      });

      if (anim.toValues) {
        anim.onChange = anim.onPause = anim.onResume = undefined;
      }

      Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* callFluidObservers */ "d"])(this, {
        type: 'idle',
        parent: this
      });
      const result = cancel ? getCancelledResult(this.get()) : getFinishedResult(this.get(), checkFinished(this, goal != null ? goal : anim.to));
      Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* flushCalls */ "n"])(this._pendingCalls, result);

      if (anim.changed) {
        anim.changed = false;
        sendEvent(this, 'onRest', result, this);
      }
    }
  }

}

function checkFinished(target, to) {
  const goal = computeGoal(to);
  const value = computeGoal(target.get());
  return Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* isEqual */ "u"])(value, goal);
}

function createLoopUpdate(props, loop = props.loop, to = props.to) {
  let loopRet = callProp(loop);

  if (loopRet) {
    const overrides = loopRet !== true && inferTo(loopRet);
    const reverse = (overrides || props).reverse;
    const reset = !overrides || overrides.reset;
    return createUpdate(_extends({}, props, {
      loop,
      default: false,
      pause: undefined,
      to: !reverse || isAsyncTo(to) ? to : undefined,
      from: reset ? props.from : undefined,
      reset
    }, overrides));
  }
}
function createUpdate(props) {
  const {
    to,
    from
  } = props = inferTo(props);
  const keys = new Set();
  if (_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* is */ "s"].obj(to)) findDefined(to, keys);
  if (_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* is */ "s"].obj(from)) findDefined(from, keys);
  props.keys = keys.size ? Array.from(keys) : null;
  return props;
}
function declareUpdate(props) {
  const update = createUpdate(props);

  if (_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* is */ "s"].und(update.default)) {
    update.default = getDefaultProps(update);
  }

  return update;
}

function findDefined(values, keys) {
  Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* eachProp */ "l"])(values, (value, key) => value != null && keys.add(key));
}

const ACTIVE_EVENTS = ['onStart', 'onRest', 'onChange', 'onPause', 'onResume'];

function mergeActiveFn(target, props, type) {
  target.animation[type] = props[type] !== getDefaultProp(props, type) ? resolveProp(props[type], target.key) : undefined;
}

function sendEvent(target, type, ...args) {
  var _target$animation$typ, _target$animation, _target$defaultProps$, _target$defaultProps;

  (_target$animation$typ = (_target$animation = target.animation)[type]) == null ? void 0 : _target$animation$typ.call(_target$animation, ...args);
  (_target$defaultProps$ = (_target$defaultProps = target.defaultProps)[type]) == null ? void 0 : _target$defaultProps$.call(_target$defaultProps, ...args);
}

const BATCHED_EVENTS = ['onStart', 'onChange', 'onRest'];
let nextId = 1;
class Controller {
  constructor(props, flush) {
    this.id = nextId++;
    this.springs = {};
    this.queue = [];
    this.ref = void 0;
    this._flush = void 0;
    this._initialProps = void 0;
    this._lastAsyncId = 0;
    this._active = new Set();
    this._changed = new Set();
    this._started = false;
    this._item = void 0;
    this._state = {
      paused: false,
      pauseQueue: new Set(),
      resumeQueue: new Set(),
      timeouts: new Set()
    };
    this._events = {
      onStart: new Map(),
      onChange: new Map(),
      onRest: new Map()
    };
    this._onFrame = this._onFrame.bind(this);

    if (flush) {
      this._flush = flush;
    }

    if (props) {
      this.start(_extends({
        default: true
      }, props));
    }
  }

  get idle() {
    return !this._state.asyncTo && Object.values(this.springs).every(spring => {
      return spring.idle && !spring.isDelayed && !spring.isPaused;
    });
  }

  get item() {
    return this._item;
  }

  set item(item) {
    this._item = item;
  }

  get() {
    const values = {};
    this.each((spring, key) => values[key] = spring.get());
    return values;
  }

  set(values) {
    for (const key in values) {
      const value = values[key];

      if (!_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* is */ "s"].und(value)) {
        this.springs[key].set(value);
      }
    }
  }

  update(props) {
    if (props) {
      this.queue.push(createUpdate(props));
    }

    return this;
  }

  start(props) {
    let {
      queue
    } = this;

    if (props) {
      queue = Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* toArray */ "y"])(props).map(createUpdate);
    } else {
      this.queue = [];
    }

    if (this._flush) {
      return this._flush(this, queue);
    }

    prepareKeys(this, queue);
    return flushUpdateQueue(this, queue);
  }

  stop(arg, keys) {
    if (arg !== !!arg) {
      keys = arg;
    }

    if (keys) {
      const springs = this.springs;
      Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* each */ "k"])(Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* toArray */ "y"])(keys), key => springs[key].stop(!!arg));
    } else {
      stopAsync(this._state, this._lastAsyncId);
      this.each(spring => spring.stop(!!arg));
    }

    return this;
  }

  pause(keys) {
    if (_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* is */ "s"].und(keys)) {
      this.start({
        pause: true
      });
    } else {
      const springs = this.springs;
      Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* each */ "k"])(Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* toArray */ "y"])(keys), key => springs[key].pause());
    }

    return this;
  }

  resume(keys) {
    if (_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* is */ "s"].und(keys)) {
      this.start({
        pause: false
      });
    } else {
      const springs = this.springs;
      Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* each */ "k"])(Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* toArray */ "y"])(keys), key => springs[key].resume());
    }

    return this;
  }

  each(iterator) {
    Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* eachProp */ "l"])(this.springs, iterator);
  }

  _onFrame() {
    const {
      onStart,
      onChange,
      onRest
    } = this._events;
    const active = this._active.size > 0;
    const changed = this._changed.size > 0;

    if (active && !this._started || changed && !this._started) {
      this._started = true;
      Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* flush */ "m"])(onStart, ([onStart, result]) => {
        result.value = this.get();
        onStart(result, this, this._item);
      });
    }

    const idle = !active && this._started;
    const values = changed || idle && onRest.size ? this.get() : null;

    if (changed && onChange.size) {
      Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* flush */ "m"])(onChange, ([onChange, result]) => {
        result.value = values;
        onChange(result, this, this._item);
      });
    }

    if (idle) {
      this._started = false;
      Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* flush */ "m"])(onRest, ([onRest, result]) => {
        result.value = values;
        onRest(result, this, this._item);
      });
    }
  }

  eventObserved(event) {
    if (event.type == 'change') {
      this._changed.add(event.parent);

      if (!event.idle) {
        this._active.add(event.parent);
      }
    } else if (event.type == 'idle') {
      this._active.delete(event.parent);
    } else return;

    _react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* raf */ "w"].onFrame(this._onFrame);
  }

}
function flushUpdateQueue(ctrl, queue) {
  return Promise.all(queue.map(props => flushUpdate(ctrl, props))).then(results => getCombinedResult(ctrl, results));
}
async function flushUpdate(ctrl, props, isLoop) {
  const {
    keys,
    to,
    from,
    loop,
    onRest,
    onResolve
  } = props;
  const defaults = _react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* is */ "s"].obj(props.default) && props.default;

  if (loop) {
    props.loop = false;
  }

  if (to === false) props.to = null;
  if (from === false) props.from = null;
  const asyncTo = _react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* is */ "s"].arr(to) || _react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* is */ "s"].fun(to) ? to : undefined;

  if (asyncTo) {
    props.to = undefined;
    props.onRest = undefined;

    if (defaults) {
      defaults.onRest = undefined;
    }
  } else {
      Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* each */ "k"])(BATCHED_EVENTS, key => {
        const handler = props[key];

        if (_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* is */ "s"].fun(handler)) {
          const queue = ctrl['_events'][key];

          props[key] = ({
            finished,
            cancelled
          }) => {
            const result = queue.get(handler);

            if (result) {
              if (!finished) result.finished = false;
              if (cancelled) result.cancelled = true;
            } else {
              queue.set(handler, {
                value: null,
                finished: finished || false,
                cancelled: cancelled || false
              });
            }
          };

          if (defaults) {
            defaults[key] = props[key];
          }
        }
      });
    }

  const state = ctrl['_state'];

  if (props.pause === !state.paused) {
    state.paused = props.pause;
    Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* flushCalls */ "n"])(props.pause ? state.pauseQueue : state.resumeQueue);
  } else if (state.paused) {
      props.pause = true;
    }

  const promises = (keys || Object.keys(ctrl.springs)).map(key => ctrl.springs[key].start(props));
  const cancel = props.cancel === true || getDefaultProp(props, 'cancel') === true;

  if (asyncTo || cancel && state.asyncId) {
    promises.push(scheduleProps(++ctrl['_lastAsyncId'], {
      props,
      state,
      actions: {
        pause: _react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* noop */ "v"],
        resume: _react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* noop */ "v"],

        start(props, resolve) {
          if (cancel) {
            stopAsync(state, ctrl['_lastAsyncId']);
            resolve(getCancelledResult(ctrl));
          } else {
            props.onRest = onRest;
            resolve(runAsync(asyncTo, props, state, ctrl));
          }
        }

      }
    }));
  }

  if (state.paused) {
    await new Promise(resume => {
      state.resumeQueue.add(resume);
    });
  }

  const result = getCombinedResult(ctrl, await Promise.all(promises));

  if (loop && result.finished && !(isLoop && result.noop)) {
    const nextProps = createLoopUpdate(props, loop, to);

    if (nextProps) {
      prepareKeys(ctrl, [nextProps]);
      return flushUpdate(ctrl, nextProps, true);
    }
  }

  if (onResolve) {
    _react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* raf */ "w"].batchedUpdates(() => onResolve(result, ctrl, ctrl.item));
  }

  return result;
}
function getSprings(ctrl, props) {
  const springs = _extends({}, ctrl.springs);

  if (props) {
    Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* each */ "k"])(Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* toArray */ "y"])(props), props => {
      if (_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* is */ "s"].und(props.keys)) {
        props = createUpdate(props);
      }

      if (!_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* is */ "s"].obj(props.to)) {
        props = _extends({}, props, {
          to: undefined
        });
      }

      prepareSprings(springs, props, key => {
        return createSpring(key);
      });
    });
  }

  setSprings(ctrl, springs);
  return springs;
}
function setSprings(ctrl, springs) {
  Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* eachProp */ "l"])(springs, (spring, key) => {
    if (!ctrl.springs[key]) {
      ctrl.springs[key] = spring;
      Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* addFluidObserver */ "c"])(spring, ctrl);
    }
  });
}

function createSpring(key, observer) {
  const spring = new SpringValue();
  spring.key = key;

  if (observer) {
    Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* addFluidObserver */ "c"])(spring, observer);
  }

  return spring;
}

function prepareSprings(springs, props, create) {
  if (props.keys) {
    Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* each */ "k"])(props.keys, key => {
      const spring = springs[key] || (springs[key] = create(key));
      spring['_prepareNode'](props);
    });
  }
}

function prepareKeys(ctrl, queue) {
  Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* each */ "k"])(queue, props => {
    prepareSprings(ctrl.springs, props, key => {
      return createSpring(key, ctrl);
    });
  });
}

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

const _excluded$3 = ["children"];
const SpringContext = _ref => {
  let {
    children
  } = _ref,
      props = _objectWithoutPropertiesLoose(_ref, _excluded$3);

  const inherited = Object(react__WEBPACK_IMPORTED_MODULE_1__["useContext"])(ctx);
  const pause = props.pause || !!inherited.pause,
        immediate = props.immediate || !!inherited.immediate;
  props = Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* useMemoOne */ "B"])(() => ({
    pause,
    immediate
  }), [pause, immediate]);
  const {
    Provider
  } = ctx;
  return react__WEBPACK_IMPORTED_MODULE_1__["createElement"](Provider, {
    value: props
  }, children);
};
const ctx = makeContext(SpringContext, {});
SpringContext.Provider = ctx.Provider;
SpringContext.Consumer = ctx.Consumer;

function makeContext(target, init) {
  Object.assign(target, react__WEBPACK_IMPORTED_MODULE_1__["createContext"](init));
  target.Provider._context = target;
  target.Consumer._context = target;
  return target;
}

const SpringRef = () => {
  const current = [];

  const SpringRef = function SpringRef(props) {
    Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* deprecateDirectCall */ "i"])();
    const results = [];
    Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* each */ "k"])(current, (ctrl, i) => {
      if (_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* is */ "s"].und(props)) {
        results.push(ctrl.start());
      } else {
        const update = _getProps(props, ctrl, i);

        if (update) {
          results.push(ctrl.start(update));
        }
      }
    });
    return results;
  };

  SpringRef.current = current;

  SpringRef.add = function (ctrl) {
    if (!current.includes(ctrl)) {
      current.push(ctrl);
    }
  };

  SpringRef.delete = function (ctrl) {
    const i = current.indexOf(ctrl);
    if (~i) current.splice(i, 1);
  };

  SpringRef.pause = function () {
    Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* each */ "k"])(current, ctrl => ctrl.pause(...arguments));
    return this;
  };

  SpringRef.resume = function () {
    Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* each */ "k"])(current, ctrl => ctrl.resume(...arguments));
    return this;
  };

  SpringRef.set = function (values) {
    Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* each */ "k"])(current, ctrl => ctrl.set(values));
  };

  SpringRef.start = function (props) {
    const results = [];
    Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* each */ "k"])(current, (ctrl, i) => {
      if (_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* is */ "s"].und(props)) {
        results.push(ctrl.start());
      } else {
        const update = this._getProps(props, ctrl, i);

        if (update) {
          results.push(ctrl.start(update));
        }
      }
    });
    return results;
  };

  SpringRef.stop = function () {
    Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* each */ "k"])(current, ctrl => ctrl.stop(...arguments));
    return this;
  };

  SpringRef.update = function (props) {
    Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* each */ "k"])(current, (ctrl, i) => ctrl.update(this._getProps(props, ctrl, i)));
    return this;
  };

  const _getProps = function _getProps(arg, ctrl, index) {
    return _react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* is */ "s"].fun(arg) ? arg(index, ctrl) : arg;
  };

  SpringRef._getProps = _getProps;
  return SpringRef;
};

function useSprings(length, props, deps) {
  const propsFn = _react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* is */ "s"].fun(props) && props;
  if (propsFn && !deps) deps = [];
  const ref = Object(react__WEBPACK_IMPORTED_MODULE_1__["useMemo"])(() => propsFn || arguments.length == 3 ? SpringRef() : void 0, []);
  const layoutId = Object(react__WEBPACK_IMPORTED_MODULE_1__["useRef"])(0);
  const forceUpdate = Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* useForceUpdate */ "z"])();
  const state = Object(react__WEBPACK_IMPORTED_MODULE_1__["useMemo"])(() => ({
    ctrls: [],
    queue: [],

    flush(ctrl, updates) {
      const springs = getSprings(ctrl, updates);
      const canFlushSync = layoutId.current > 0 && !state.queue.length && !Object.keys(springs).some(key => !ctrl.springs[key]);
      return canFlushSync ? flushUpdateQueue(ctrl, updates) : new Promise(resolve => {
        setSprings(ctrl, springs);
        state.queue.push(() => {
          resolve(flushUpdateQueue(ctrl, updates));
        });
        forceUpdate();
      });
    }

  }), []);
  const ctrls = Object(react__WEBPACK_IMPORTED_MODULE_1__["useRef"])([...state.ctrls]);
  const updates = [];
  const prevLength = Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* usePrev */ "D"])(length) || 0;
  Object(react__WEBPACK_IMPORTED_MODULE_1__["useMemo"])(() => {
    Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* each */ "k"])(ctrls.current.slice(length, prevLength), ctrl => {
      detachRefs(ctrl, ref);
      ctrl.stop(true);
    });
    ctrls.current.length = length;
    declareUpdates(prevLength, length);
  }, [length]);
  Object(react__WEBPACK_IMPORTED_MODULE_1__["useMemo"])(() => {
    declareUpdates(0, Math.min(prevLength, length));
  }, deps);

  function declareUpdates(startIndex, endIndex) {
    for (let i = startIndex; i < endIndex; i++) {
      const ctrl = ctrls.current[i] || (ctrls.current[i] = new Controller(null, state.flush));
      const update = propsFn ? propsFn(i, ctrl) : props[i];

      if (update) {
        updates[i] = declareUpdate(update);
      }
    }
  }

  const springs = ctrls.current.map((ctrl, i) => getSprings(ctrl, updates[i]));
  const context = Object(react__WEBPACK_IMPORTED_MODULE_1__["useContext"])(SpringContext);
  const prevContext = Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* usePrev */ "D"])(context);
  const hasContext = context !== prevContext && hasProps(context);
  Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* useLayoutEffect */ "A"])(() => {
    layoutId.current++;
    state.ctrls = ctrls.current;
    const {
      queue
    } = state;

    if (queue.length) {
      state.queue = [];
      Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* each */ "k"])(queue, cb => cb());
    }

    Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* each */ "k"])(ctrls.current, (ctrl, i) => {
      ref == null ? void 0 : ref.add(ctrl);

      if (hasContext) {
        ctrl.start({
          default: context
        });
      }

      const update = updates[i];

      if (update) {
        replaceRef(ctrl, update.ref);

        if (ctrl.ref) {
          ctrl.queue.push(update);
        } else {
          ctrl.start(update);
        }
      }
    });
  });
  Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* useOnce */ "C"])(() => () => {
    Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* each */ "k"])(state.ctrls, ctrl => ctrl.stop(true));
  });
  const values = springs.map(x => _extends({}, x));
  return ref ? [values, ref] : values;
}

function useSpring(props, deps) {
  const isFn = _react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* is */ "s"].fun(props);
  const [[values], ref] = useSprings(1, isFn ? props : [props], isFn ? deps || [] : deps);
  return isFn || arguments.length == 2 ? [values, ref] : values;
}

const initSpringRef = () => SpringRef();

const useSpringRef = () => Object(react__WEBPACK_IMPORTED_MODULE_1__["useState"])(initSpringRef)[0];

function useTrail(length, propsArg, deps) {
  var _passedRef;

  const propsFn = _react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* is */ "s"].fun(propsArg) && propsArg;
  if (propsFn && !deps) deps = [];
  let reverse = true;
  let passedRef = undefined;
  const result = useSprings(length, (i, ctrl) => {
    const props = propsFn ? propsFn(i, ctrl) : propsArg;
    passedRef = props.ref;
    reverse = reverse && props.reverse;
    return props;
  }, deps || [{}]);
  const ref = (_passedRef = passedRef) != null ? _passedRef : result[1];
  Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* useLayoutEffect */ "A"])(() => {
    Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* each */ "k"])(ref.current, (ctrl, i) => {
      const parent = ref.current[i + (reverse ? 1 : -1)];

      if (parent) {
        ctrl.start({
          to: parent.springs
        });
      } else {
        ctrl.start();
      }
    });
  }, deps);

  if (propsFn || arguments.length == 3) {
    ref['_getProps'] = (propsArg, ctrl, i) => {
      const props = _react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* is */ "s"].fun(propsArg) ? propsArg(i, ctrl) : propsArg;

      if (props) {
        const parent = ref.current[i + (props.reverse ? 1 : -1)];
        if (parent) props.to = parent.springs;
        return props;
      }
    };

    return result;
  }

  ref['start'] = propsArg => {
    const results = [];
    Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* each */ "k"])(ref.current, (ctrl, i) => {
      const props = _react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* is */ "s"].fun(propsArg) ? propsArg(i, ctrl) : propsArg;
      const parent = ref.current[i + (reverse ? 1 : -1)];

      if (parent) {
        results.push(ctrl.start(_extends({}, props, {
          to: parent.springs
        })));
      } else {
        results.push(ctrl.start(_extends({}, props)));
      }
    });
    return results;
  };

  return result[0];
}

let TransitionPhase;

(function (TransitionPhase) {
  TransitionPhase["MOUNT"] = "mount";
  TransitionPhase["ENTER"] = "enter";
  TransitionPhase["UPDATE"] = "update";
  TransitionPhase["LEAVE"] = "leave";
})(TransitionPhase || (TransitionPhase = {}));

function useTransition(data, props, deps) {
  const propsFn = _react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* is */ "s"].fun(props) && props;
  const {
    reset,
    sort,
    trail = 0,
    expires = true,
    exitBeforeEnter = false,
    onDestroyed,
    ref: propsRef,
    config: propsConfig
  } = propsFn ? propsFn() : props;
  const ref = Object(react__WEBPACK_IMPORTED_MODULE_1__["useMemo"])(() => propsFn || arguments.length == 3 ? SpringRef() : void 0, []);
  const items = Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* toArray */ "y"])(data);
  const transitions = [];
  const usedTransitions = Object(react__WEBPACK_IMPORTED_MODULE_1__["useRef"])(null);
  const prevTransitions = reset ? null : usedTransitions.current;
  Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* useLayoutEffect */ "A"])(() => {
    usedTransitions.current = transitions;
  });
  Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* useOnce */ "C"])(() => () => {
    Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* each */ "k"])(usedTransitions.current, t => {
      if (t.expired) {
        clearTimeout(t.expirationId);
      }

      detachRefs(t.ctrl, ref);
      t.ctrl.stop(true);
    });
  });
  const keys = getKeys(items, propsFn ? propsFn() : props, prevTransitions);
  const expired = reset && usedTransitions.current || [];
  Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* useLayoutEffect */ "A"])(() => Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* each */ "k"])(expired, ({
    ctrl,
    item,
    key
  }) => {
    detachRefs(ctrl, ref);
    callProp(onDestroyed, item, key);
  }));
  const reused = [];
  if (prevTransitions) Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* each */ "k"])(prevTransitions, (t, i) => {
    if (t.expired) {
      clearTimeout(t.expirationId);
      expired.push(t);
    } else {
      i = reused[i] = keys.indexOf(t.key);
      if (~i) transitions[i] = t;
    }
  });
  Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* each */ "k"])(items, (item, i) => {
    if (!transitions[i]) {
      transitions[i] = {
        key: keys[i],
        item,
        phase: TransitionPhase.MOUNT,
        ctrl: new Controller()
      };
      transitions[i].ctrl.item = item;
    }
  });

  if (reused.length) {
    let i = -1;
    const {
      leave
    } = propsFn ? propsFn() : props;
    Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* each */ "k"])(reused, (keyIndex, prevIndex) => {
      const t = prevTransitions[prevIndex];

      if (~keyIndex) {
        i = transitions.indexOf(t);
        transitions[i] = _extends({}, t, {
          item: items[keyIndex]
        });
      } else if (leave) {
        transitions.splice(++i, 0, t);
      }
    });
  }

  if (_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* is */ "s"].fun(sort)) {
    transitions.sort((a, b) => sort(a.item, b.item));
  }

  let delay = -trail;
  const forceUpdate = Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* useForceUpdate */ "z"])();
  const defaultProps = getDefaultProps(props);
  const changes = new Map();
  const exitingTransitions = Object(react__WEBPACK_IMPORTED_MODULE_1__["useRef"])(new Map());
  const forceChange = Object(react__WEBPACK_IMPORTED_MODULE_1__["useRef"])(false);
  Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* each */ "k"])(transitions, (t, i) => {
    const key = t.key;
    const prevPhase = t.phase;
    const p = propsFn ? propsFn() : props;
    let to;
    let phase;
    let propsDelay = callProp(p.delay || 0, key);

    if (prevPhase == TransitionPhase.MOUNT) {
      to = p.enter;
      phase = TransitionPhase.ENTER;
    } else {
      const isLeave = keys.indexOf(key) < 0;

      if (prevPhase != TransitionPhase.LEAVE) {
        if (isLeave) {
          to = p.leave;
          phase = TransitionPhase.LEAVE;
        } else if (to = p.update) {
          phase = TransitionPhase.UPDATE;
        } else return;
      } else if (!isLeave) {
        to = p.enter;
        phase = TransitionPhase.ENTER;
      } else return;
    }

    to = callProp(to, t.item, i);
    to = _react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* is */ "s"].obj(to) ? inferTo(to) : {
      to
    };

    if (!to.config) {
      const config = propsConfig || defaultProps.config;
      to.config = callProp(config, t.item, i, phase);
    }

    delay += trail;

    const payload = _extends({}, defaultProps, {
      delay: propsDelay + delay,
      ref: propsRef,
      immediate: p.immediate,
      reset: false
    }, to);

    if (phase == TransitionPhase.ENTER && _react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* is */ "s"].und(payload.from)) {
      const _p = propsFn ? propsFn() : props;

      const from = _react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* is */ "s"].und(_p.initial) || prevTransitions ? _p.from : _p.initial;
      payload.from = callProp(from, t.item, i);
    }

    const {
      onResolve
    } = payload;

    payload.onResolve = result => {
      callProp(onResolve, result);
      const transitions = usedTransitions.current;
      const t = transitions.find(t => t.key === key);
      if (!t) return;

      if (result.cancelled && t.phase != TransitionPhase.UPDATE) {
        return;
      }

      if (t.ctrl.idle) {
        const idle = transitions.every(t => t.ctrl.idle);

        if (t.phase == TransitionPhase.LEAVE) {
          const expiry = callProp(expires, t.item);

          if (expiry !== false) {
            const expiryMs = expiry === true ? 0 : expiry;
            t.expired = true;

            if (!idle && expiryMs > 0) {
              if (expiryMs <= 0x7fffffff) t.expirationId = setTimeout(forceUpdate, expiryMs);
              return;
            }
          }
        }

        if (idle && transitions.some(t => t.expired)) {
          exitingTransitions.current.delete(t);

          if (exitBeforeEnter) {
            forceChange.current = true;
          }

          forceUpdate();
        }
      }
    };

    const springs = getSprings(t.ctrl, payload);

    if (phase === TransitionPhase.LEAVE && exitBeforeEnter) {
      exitingTransitions.current.set(t, {
        phase,
        springs,
        payload
      });
    } else {
      changes.set(t, {
        phase,
        springs,
        payload
      });
    }
  });
  const context = Object(react__WEBPACK_IMPORTED_MODULE_1__["useContext"])(SpringContext);
  const prevContext = Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* usePrev */ "D"])(context);
  const hasContext = context !== prevContext && hasProps(context);
  Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* useLayoutEffect */ "A"])(() => {
    if (hasContext) {
      Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* each */ "k"])(transitions, t => {
        t.ctrl.start({
          default: context
        });
      });
    }
  }, [context]);
  Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* each */ "k"])(changes, (_, t) => {
    if (exitingTransitions.current.size) {
      const ind = transitions.findIndex(state => state.key === t.key);
      transitions.splice(ind, 1);
    }
  });
  Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* useLayoutEffect */ "A"])(() => {
    Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* each */ "k"])(exitingTransitions.current.size ? exitingTransitions.current : changes, ({
      phase,
      payload
    }, t) => {
      const {
        ctrl
      } = t;
      t.phase = phase;
      ref == null ? void 0 : ref.add(ctrl);

      if (hasContext && phase == TransitionPhase.ENTER) {
        ctrl.start({
          default: context
        });
      }

      if (payload) {
        replaceRef(ctrl, payload.ref);

        if (ctrl.ref && !forceChange.current) {
          ctrl.update(payload);
        } else {
          ctrl.start(payload);

          if (forceChange.current) {
            forceChange.current = false;
          }
        }
      }
    });
  }, reset ? void 0 : deps);

  const renderTransitions = render => react__WEBPACK_IMPORTED_MODULE_1__["createElement"](react__WEBPACK_IMPORTED_MODULE_1__["Fragment"], null, transitions.map((t, i) => {
    const {
      springs
    } = changes.get(t) || t.ctrl;
    const elem = render(_extends({}, springs), t.item, t, i);
    return elem && elem.type ? react__WEBPACK_IMPORTED_MODULE_1__["createElement"](elem.type, _extends({}, elem.props, {
      key: _react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* is */ "s"].str(t.key) || _react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* is */ "s"].num(t.key) ? t.key : t.ctrl.id,
      ref: elem.ref
    })) : elem;
  }));

  return ref ? [renderTransitions, ref] : renderTransitions;
}
let nextKey = 1;

function getKeys(items, {
  key,
  keys = key
}, prevTransitions) {
  if (keys === null) {
    const reused = new Set();
    return items.map(item => {
      const t = prevTransitions && prevTransitions.find(t => t.item === item && t.phase !== TransitionPhase.LEAVE && !reused.has(t));

      if (t) {
        reused.add(t);
        return t.key;
      }

      return nextKey++;
    });
  }

  return _react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* is */ "s"].und(keys) ? items : _react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* is */ "s"].fun(keys) ? items.map(keys) : Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* toArray */ "y"])(keys);
}

const _excluded$2 = ["children"];
function Spring(_ref) {
  let {
    children
  } = _ref,
      props = _objectWithoutPropertiesLoose(_ref, _excluded$2);

  return children(useSpring(props));
}

const _excluded$1 = ["items", "children"];
function Trail(_ref) {
  let {
    items,
    children
  } = _ref,
      props = _objectWithoutPropertiesLoose(_ref, _excluded$1);

  const trails = useTrail(items.length, props);
  return items.map((item, index) => {
    const result = children(item, index);
    return _react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* is */ "s"].fun(result) ? result(trails[index]) : result;
  });
}

const _excluded = ["items", "children"];
function Transition(_ref) {
  let {
    items,
    children
  } = _ref,
      props = _objectWithoutPropertiesLoose(_ref, _excluded);

  return useTransition(items, props)(children);
}

class Interpolation extends FrameValue {
  constructor(source, args) {
    super();
    this.key = void 0;
    this.idle = true;
    this.calc = void 0;
    this._active = new Set();
    this.source = source;
    this.calc = Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* createInterpolator */ "f"])(...args);

    const value = this._get();

    const nodeType = Object(_react_spring_animated__WEBPACK_IMPORTED_MODULE_2__[/* getAnimatedType */ "f"])(value);
    Object(_react_spring_animated__WEBPACK_IMPORTED_MODULE_2__[/* setAnimated */ "h"])(this, nodeType.create(value));
  }

  advance(_dt) {
    const value = this._get();

    const oldValue = this.get();

    if (!Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* isEqual */ "u"])(value, oldValue)) {
      Object(_react_spring_animated__WEBPACK_IMPORTED_MODULE_2__[/* getAnimated */ "e"])(this).setValue(value);

      this._onChange(value, this.idle);
    }

    if (!this.idle && checkIdle(this._active)) {
      becomeIdle(this);
    }
  }

  _get() {
    const inputs = _react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* is */ "s"].arr(this.source) ? this.source.map(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* getFluidValue */ "q"]) : Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* toArray */ "y"])(Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* getFluidValue */ "q"])(this.source));
    return this.calc(...inputs);
  }

  _start() {
    if (this.idle && !checkIdle(this._active)) {
      this.idle = false;
      Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* each */ "k"])(Object(_react_spring_animated__WEBPACK_IMPORTED_MODULE_2__[/* getPayload */ "g"])(this), node => {
        node.done = false;
      });

      if (_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* Globals */ "b"].skipAnimation) {
        _react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* raf */ "w"].batchedUpdates(() => this.advance());
        becomeIdle(this);
      } else {
        _react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* frameLoop */ "o"].start(this);
      }
    }
  }

  _attach() {
    let priority = 1;
    Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* each */ "k"])(Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* toArray */ "y"])(this.source), source => {
      if (Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* hasFluidValue */ "r"])(source)) {
        Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* addFluidObserver */ "c"])(source, this);
      }

      if (isFrameValue(source)) {
        if (!source.idle) {
          this._active.add(source);
        }

        priority = Math.max(priority, source.priority + 1);
      }
    });
    this.priority = priority;

    this._start();
  }

  _detach() {
    Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* each */ "k"])(Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* toArray */ "y"])(this.source), source => {
      if (Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* hasFluidValue */ "r"])(source)) {
        Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* removeFluidObserver */ "x"])(source, this);
      }
    });

    this._active.clear();

    becomeIdle(this);
  }

  eventObserved(event) {
    if (event.type == 'change') {
      if (event.idle) {
        this.advance();
      } else {
        this._active.add(event.parent);

        this._start();
      }
    } else if (event.type == 'idle') {
        this._active.delete(event.parent);
      } else if (event.type == 'priority') {
          this.priority = Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* toArray */ "y"])(this.source).reduce((highest, parent) => Math.max(highest, (isFrameValue(parent) ? parent.priority : 0) + 1), 0);
        }
  }

}

function isIdle(source) {
  return source.idle !== false;
}

function checkIdle(active) {
  return !active.size || Array.from(active).every(isIdle);
}

function becomeIdle(self) {
  if (!self.idle) {
    self.idle = true;
    Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* each */ "k"])(Object(_react_spring_animated__WEBPACK_IMPORTED_MODULE_2__[/* getPayload */ "g"])(self), node => {
      node.done = true;
    });
    Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* callFluidObservers */ "d"])(self, {
      type: 'idle',
      parent: self
    });
  }
}

const to = (source, ...args) => new Interpolation(source, args);
const interpolate = (source, ...args) => (Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* deprecateInterpolate */ "j"])(), new Interpolation(source, args));

_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* Globals */ "b"].assign({
  createStringInterpolator: _react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* createStringInterpolator */ "g"],
  to: (source, args) => new Interpolation(source, args)
});
const update = _react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* frameLoop */ "o"].advance;




/***/ }),

/***/ 134:
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var reactIs = __webpack_require__(117);

/**
 * Copyright 2015, Yahoo! Inc.
 * Copyrights licensed under the New BSD License. See the accompanying LICENSE file for terms.
 */
var REACT_STATICS = {
  childContextTypes: true,
  contextType: true,
  contextTypes: true,
  defaultProps: true,
  displayName: true,
  getDefaultProps: true,
  getDerivedStateFromError: true,
  getDerivedStateFromProps: true,
  mixins: true,
  propTypes: true,
  type: true
};
var KNOWN_STATICS = {
  name: true,
  length: true,
  prototype: true,
  caller: true,
  callee: true,
  arguments: true,
  arity: true
};
var FORWARD_REF_STATICS = {
  '$$typeof': true,
  render: true,
  defaultProps: true,
  displayName: true,
  propTypes: true
};
var MEMO_STATICS = {
  '$$typeof': true,
  compare: true,
  defaultProps: true,
  displayName: true,
  propTypes: true,
  type: true
};
var TYPE_STATICS = {};
TYPE_STATICS[reactIs.ForwardRef] = FORWARD_REF_STATICS;
TYPE_STATICS[reactIs.Memo] = MEMO_STATICS;

function getStatics(component) {
  // React v16.11 and below
  if (reactIs.isMemo(component)) {
    return MEMO_STATICS;
  } // React v16.12 and above


  return TYPE_STATICS[component['$$typeof']] || REACT_STATICS;
}

var defineProperty = Object.defineProperty;
var getOwnPropertyNames = Object.getOwnPropertyNames;
var getOwnPropertySymbols = Object.getOwnPropertySymbols;
var getOwnPropertyDescriptor = Object.getOwnPropertyDescriptor;
var getPrototypeOf = Object.getPrototypeOf;
var objectPrototype = Object.prototype;
function hoistNonReactStatics(targetComponent, sourceComponent, blacklist) {
  if (typeof sourceComponent !== 'string') {
    // don't hoist over string (html) components
    if (objectPrototype) {
      var inheritedComponent = getPrototypeOf(sourceComponent);

      if (inheritedComponent && inheritedComponent !== objectPrototype) {
        hoistNonReactStatics(targetComponent, inheritedComponent, blacklist);
      }
    }

    var keys = getOwnPropertyNames(sourceComponent);

    if (getOwnPropertySymbols) {
      keys = keys.concat(getOwnPropertySymbols(sourceComponent));
    }

    var targetStatics = getStatics(targetComponent);
    var sourceStatics = getStatics(sourceComponent);

    for (var i = 0; i < keys.length; ++i) {
      var key = keys[i];

      if (!KNOWN_STATICS[key] && !(blacklist && blacklist[key]) && !(sourceStatics && sourceStatics[key]) && !(targetStatics && targetStatics[key])) {
        var descriptor = getOwnPropertyDescriptor(sourceComponent, key);

        try {
          // Avoid failures from read-only properties
          defineProperty(targetComponent, key, descriptor);
        } catch (e) {}
      }
    }
  }

  return targetComponent;
}

module.exports = hoistNonReactStatics;


/***/ }),

/***/ 135:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";

// EXPORTS
__webpack_require__.d(__webpack_exports__, "c", function() { return /* binding */ layout_PrimaryLayout; });
__webpack_require__.d(__webpack_exports__, "b", function() { return /* binding */ PageLayout; });
__webpack_require__.d(__webpack_exports__, "a", function() { return /* binding */ EmbedLayout; });

// EXTERNAL MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/@babel+runtime@7.17.2/node_modules/@babel/runtime/helpers/extends.js
var helpers_extends = __webpack_require__(40);
var extends_default = /*#__PURE__*/__webpack_require__.n(helpers_extends);

// EXTERNAL MODULE: external ["wp","element"]
var external_wp_element_ = __webpack_require__(0);

// EXTERNAL MODULE: external ["wp","components"]
var external_wp_components_ = __webpack_require__(4);

// EXTERNAL MODULE: external ["wp","compose"]
var external_wp_compose_ = __webpack_require__(14);

// EXTERNAL MODULE: external ["wp","data"]
var external_wp_data_ = __webpack_require__(8);

// EXTERNAL MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/@babel+runtime@7.17.2/node_modules/@babel/runtime/helpers/esm/inheritsLoose.js + 1 modules
var inheritsLoose = __webpack_require__(26);

// EXTERNAL MODULE: external "React"
var external_React_ = __webpack_require__(6);
var external_React_default = /*#__PURE__*/__webpack_require__.n(external_React_);

// EXTERNAL MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/history@4.10.1/node_modules/history/esm/history.js + 2 modules
var esm_history = __webpack_require__(65);

// EXTERNAL MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/mini-create-react-context@0.4.1_prop-types@15.8.1+react@17.0.2/node_modules/mini-create-react-context/dist/esm/index.js
var esm = __webpack_require__(281);

// EXTERNAL MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/tiny-invariant@1.2.0/node_modules/tiny-invariant/dist/tiny-invariant.esm.js
var tiny_invariant_esm = __webpack_require__(51);

// EXTERNAL MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/@babel+runtime@7.17.2/node_modules/@babel/runtime/helpers/esm/extends.js
var esm_extends = __webpack_require__(24);

// EXTERNAL MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/path-to-regexp@1.8.0/node_modules/path-to-regexp/index.js
var path_to_regexp = __webpack_require__(177);
var path_to_regexp_default = /*#__PURE__*/__webpack_require__.n(path_to_regexp);

// EXTERNAL MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/react-is@16.13.1/node_modules/react-is/index.js
var react_is = __webpack_require__(117);

// EXTERNAL MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/@babel+runtime@7.17.2/node_modules/@babel/runtime/helpers/esm/objectWithoutPropertiesLoose.js
var objectWithoutPropertiesLoose = __webpack_require__(30);

// EXTERNAL MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/hoist-non-react-statics@3.3.2/node_modules/hoist-non-react-statics/dist/hoist-non-react-statics.cjs.js
var hoist_non_react_statics_cjs = __webpack_require__(134);
var hoist_non_react_statics_cjs_default = /*#__PURE__*/__webpack_require__.n(hoist_non_react_statics_cjs);

// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/react-router@5.2.1_react@17.0.2/node_modules/react-router/esm/react-router.js













// TODO: Replace with React.createContext once we can assume React 16+

var react_router_createNamedContext = function createNamedContext(name) {
  var context = Object(esm["a" /* default */])();
  context.displayName = name;
  return context;
};

var historyContext = /*#__PURE__*/react_router_createNamedContext("Router-History");

var react_router_context = /*#__PURE__*/react_router_createNamedContext("Router");

/**
 * The public API for putting history on context.
 */

var react_router_Router = /*#__PURE__*/function (_React$Component) {
  Object(inheritsLoose["a" /* default */])(Router, _React$Component);

  Router.computeRootMatch = function computeRootMatch(pathname) {
    return {
      path: "/",
      url: "/",
      params: {},
      isExact: pathname === "/"
    };
  };

  function Router(props) {
    var _this;

    _this = _React$Component.call(this, props) || this;
    _this.state = {
      location: props.history.location
    }; // This is a bit of a hack. We have to start listening for location
    // changes here in the constructor in case there are any <Redirect>s
    // on the initial render. If there are, they will replace/push when
    // they mount and since cDM fires in children before parents, we may
    // get a new location before the <Router> is mounted.

    _this._isMounted = false;
    _this._pendingLocation = null;

    if (!props.staticContext) {
      _this.unlisten = props.history.listen(function (location) {
        if (_this._isMounted) {
          _this.setState({
            location: location
          });
        } else {
          _this._pendingLocation = location;
        }
      });
    }

    return _this;
  }

  var _proto = Router.prototype;

  _proto.componentDidMount = function componentDidMount() {
    this._isMounted = true;

    if (this._pendingLocation) {
      this.setState({
        location: this._pendingLocation
      });
    }
  };

  _proto.componentWillUnmount = function componentWillUnmount() {
    if (this.unlisten) {
      this.unlisten();
      this._isMounted = false;
      this._pendingLocation = null;
    }
  };

  _proto.render = function render() {
    return /*#__PURE__*/external_React_default.a.createElement(react_router_context.Provider, {
      value: {
        history: this.props.history,
        location: this.state.location,
        match: Router.computeRootMatch(this.state.location.pathname),
        staticContext: this.props.staticContext
      }
    }, /*#__PURE__*/external_React_default.a.createElement(historyContext.Provider, {
      children: this.props.children || null,
      value: this.props.history
    }));
  };

  return Router;
}(external_React_default.a.Component);

if (false) {}

/**
 * The public API for a <Router> that stores location in memory.
 */

var react_router_MemoryRouter = /*#__PURE__*/function (_React$Component) {
  Object(inheritsLoose["a" /* default */])(MemoryRouter, _React$Component);

  function MemoryRouter() {
    var _this;

    for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
      args[_key] = arguments[_key];
    }

    _this = _React$Component.call.apply(_React$Component, [this].concat(args)) || this;
    _this.history = Object(esm_history["c" /* createMemoryHistory */])(_this.props);
    return _this;
  }

  var _proto = MemoryRouter.prototype;

  _proto.render = function render() {
    return /*#__PURE__*/external_React_default.a.createElement(react_router_Router, {
      history: this.history,
      children: this.props.children
    });
  };

  return MemoryRouter;
}(external_React_default.a.Component);

if (false) {}

var react_router_Lifecycle = /*#__PURE__*/function (_React$Component) {
  Object(inheritsLoose["a" /* default */])(Lifecycle, _React$Component);

  function Lifecycle() {
    return _React$Component.apply(this, arguments) || this;
  }

  var _proto = Lifecycle.prototype;

  _proto.componentDidMount = function componentDidMount() {
    if (this.props.onMount) this.props.onMount.call(this, this);
  };

  _proto.componentDidUpdate = function componentDidUpdate(prevProps) {
    if (this.props.onUpdate) this.props.onUpdate.call(this, this, prevProps);
  };

  _proto.componentWillUnmount = function componentWillUnmount() {
    if (this.props.onUnmount) this.props.onUnmount.call(this, this);
  };

  _proto.render = function render() {
    return null;
  };

  return Lifecycle;
}(external_React_default.a.Component);

/**
 * The public API for prompting the user before navigating away from a screen.
 */

function Prompt(_ref) {
  var message = _ref.message,
      _ref$when = _ref.when,
      when = _ref$when === void 0 ? true : _ref$when;
  return /*#__PURE__*/external_React_default.a.createElement(react_router_context.Consumer, null, function (context) {
    !context ?  false ? undefined : Object(tiny_invariant_esm["a" /* default */])(false) : void 0;
    if (!when || context.staticContext) return null;
    var method = context.history.block;
    return /*#__PURE__*/external_React_default.a.createElement(react_router_Lifecycle, {
      onMount: function onMount(self) {
        self.release = method(message);
      },
      onUpdate: function onUpdate(self, prevProps) {
        if (prevProps.message !== message) {
          self.release();
          self.release = method(message);
        }
      },
      onUnmount: function onUnmount(self) {
        self.release();
      },
      message: message
    });
  });
}

if (false) { var messageType; }

var cache = {};
var cacheLimit = 10000;
var cacheCount = 0;

function compilePath(path) {
  if (cache[path]) return cache[path];
  var generator = path_to_regexp_default.a.compile(path);

  if (cacheCount < cacheLimit) {
    cache[path] = generator;
    cacheCount++;
  }

  return generator;
}
/**
 * Public API for generating a URL pathname from a path and parameters.
 */


function generatePath(path, params) {
  if (path === void 0) {
    path = "/";
  }

  if (params === void 0) {
    params = {};
  }

  return path === "/" ? path : compilePath(path)(params, {
    pretty: true
  });
}

/**
 * The public API for navigating programmatically with a component.
 */

function Redirect(_ref) {
  var computedMatch = _ref.computedMatch,
      to = _ref.to,
      _ref$push = _ref.push,
      push = _ref$push === void 0 ? false : _ref$push;
  return /*#__PURE__*/external_React_default.a.createElement(react_router_context.Consumer, null, function (context) {
    !context ?  false ? undefined : Object(tiny_invariant_esm["a" /* default */])(false) : void 0;
    var history = context.history,
        staticContext = context.staticContext;
    var method = push ? history.push : history.replace;
    var location = Object(esm_history["b" /* createLocation */])(computedMatch ? typeof to === "string" ? generatePath(to, computedMatch.params) : Object(esm_extends["a" /* default */])({}, to, {
      pathname: generatePath(to.pathname, computedMatch.params)
    }) : to); // When rendering in a static context,
    // set the new location immediately.

    if (staticContext) {
      method(location);
      return null;
    }

    return /*#__PURE__*/external_React_default.a.createElement(react_router_Lifecycle, {
      onMount: function onMount() {
        method(location);
      },
      onUpdate: function onUpdate(self, prevProps) {
        var prevLocation = Object(esm_history["b" /* createLocation */])(prevProps.to);

        if (!Object(esm_history["e" /* locationsAreEqual */])(prevLocation, Object(esm_extends["a" /* default */])({}, location, {
          key: prevLocation.key
        }))) {
          method(location);
        }
      },
      to: to
    });
  });
}

if (false) {}

var cache$1 = {};
var cacheLimit$1 = 10000;
var cacheCount$1 = 0;

function compilePath$1(path, options) {
  var cacheKey = "" + options.end + options.strict + options.sensitive;
  var pathCache = cache$1[cacheKey] || (cache$1[cacheKey] = {});
  if (pathCache[path]) return pathCache[path];
  var keys = [];
  var regexp = path_to_regexp_default()(path, keys, options);
  var result = {
    regexp: regexp,
    keys: keys
  };

  if (cacheCount$1 < cacheLimit$1) {
    pathCache[path] = result;
    cacheCount$1++;
  }

  return result;
}
/**
 * Public API for matching a URL pathname to a path.
 */


function matchPath(pathname, options) {
  if (options === void 0) {
    options = {};
  }

  if (typeof options === "string" || Array.isArray(options)) {
    options = {
      path: options
    };
  }

  var _options = options,
      path = _options.path,
      _options$exact = _options.exact,
      exact = _options$exact === void 0 ? false : _options$exact,
      _options$strict = _options.strict,
      strict = _options$strict === void 0 ? false : _options$strict,
      _options$sensitive = _options.sensitive,
      sensitive = _options$sensitive === void 0 ? false : _options$sensitive;
  var paths = [].concat(path);
  return paths.reduce(function (matched, path) {
    if (!path && path !== "") return null;
    if (matched) return matched;

    var _compilePath = compilePath$1(path, {
      end: exact,
      strict: strict,
      sensitive: sensitive
    }),
        regexp = _compilePath.regexp,
        keys = _compilePath.keys;

    var match = regexp.exec(pathname);
    if (!match) return null;
    var url = match[0],
        values = match.slice(1);
    var isExact = pathname === url;
    if (exact && !isExact) return null;
    return {
      path: path,
      // the path used to match
      url: path === "/" && url === "" ? "/" : url,
      // the matched portion of the URL
      isExact: isExact,
      // whether or not we matched exactly
      params: keys.reduce(function (memo, key, index) {
        memo[key.name] = values[index];
        return memo;
      }, {})
    };
  }, null);
}

function isEmptyChildren(children) {
  return external_React_default.a.Children.count(children) === 0;
}

function evalChildrenDev(children, props, path) {
  var value = children(props);
   false ? undefined : void 0;
  return value || null;
}
/**
 * The public API for matching a single path and rendering.
 */


var react_router_Route = /*#__PURE__*/function (_React$Component) {
  Object(inheritsLoose["a" /* default */])(Route, _React$Component);

  function Route() {
    return _React$Component.apply(this, arguments) || this;
  }

  var _proto = Route.prototype;

  _proto.render = function render() {
    var _this = this;

    return /*#__PURE__*/external_React_default.a.createElement(react_router_context.Consumer, null, function (context$1) {
      !context$1 ?  false ? undefined : Object(tiny_invariant_esm["a" /* default */])(false) : void 0;
      var location = _this.props.location || context$1.location;
      var match = _this.props.computedMatch ? _this.props.computedMatch // <Switch> already computed the match for us
      : _this.props.path ? matchPath(location.pathname, _this.props) : context$1.match;

      var props = Object(esm_extends["a" /* default */])({}, context$1, {
        location: location,
        match: match
      });

      var _this$props = _this.props,
          children = _this$props.children,
          component = _this$props.component,
          render = _this$props.render; // Preact uses an empty array as children by
      // default, so use null if that's the case.

      if (Array.isArray(children) && isEmptyChildren(children)) {
        children = null;
      }

      return /*#__PURE__*/external_React_default.a.createElement(react_router_context.Provider, {
        value: props
      }, props.match ? children ? typeof children === "function" ?  false ? undefined : children(props) : children : component ? /*#__PURE__*/external_React_default.a.createElement(component, props) : render ? render(props) : null : typeof children === "function" ?  false ? undefined : children(props) : null);
    });
  };

  return Route;
}(external_React_default.a.Component);

if (false) {}

function addLeadingSlash(path) {
  return path.charAt(0) === "/" ? path : "/" + path;
}

function addBasename(basename, location) {
  if (!basename) return location;
  return Object(esm_extends["a" /* default */])({}, location, {
    pathname: addLeadingSlash(basename) + location.pathname
  });
}

function stripBasename(basename, location) {
  if (!basename) return location;
  var base = addLeadingSlash(basename);
  if (location.pathname.indexOf(base) !== 0) return location;
  return Object(esm_extends["a" /* default */])({}, location, {
    pathname: location.pathname.substr(base.length)
  });
}

function createURL(location) {
  return typeof location === "string" ? location : Object(esm_history["d" /* createPath */])(location);
}

function staticHandler(methodName) {
  return function () {
      false ? undefined : Object(tiny_invariant_esm["a" /* default */])(false) ;
  };
}

function noop() {}
/**
 * The public top-level API for a "static" <Router>, so-called because it
 * can't actually change the current location. Instead, it just records
 * location changes in a context object. Useful mainly in testing and
 * server-rendering scenarios.
 */


var react_router_StaticRouter = /*#__PURE__*/function (_React$Component) {
  Object(inheritsLoose["a" /* default */])(StaticRouter, _React$Component);

  function StaticRouter() {
    var _this;

    for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
      args[_key] = arguments[_key];
    }

    _this = _React$Component.call.apply(_React$Component, [this].concat(args)) || this;

    _this.handlePush = function (location) {
      return _this.navigateTo(location, "PUSH");
    };

    _this.handleReplace = function (location) {
      return _this.navigateTo(location, "REPLACE");
    };

    _this.handleListen = function () {
      return noop;
    };

    _this.handleBlock = function () {
      return noop;
    };

    return _this;
  }

  var _proto = StaticRouter.prototype;

  _proto.navigateTo = function navigateTo(location, action) {
    var _this$props = this.props,
        _this$props$basename = _this$props.basename,
        basename = _this$props$basename === void 0 ? "" : _this$props$basename,
        _this$props$context = _this$props.context,
        context = _this$props$context === void 0 ? {} : _this$props$context;
    context.action = action;
    context.location = addBasename(basename, Object(esm_history["b" /* createLocation */])(location));
    context.url = createURL(context.location);
  };

  _proto.render = function render() {
    var _this$props2 = this.props,
        _this$props2$basename = _this$props2.basename,
        basename = _this$props2$basename === void 0 ? "" : _this$props2$basename,
        _this$props2$context = _this$props2.context,
        context = _this$props2$context === void 0 ? {} : _this$props2$context,
        _this$props2$location = _this$props2.location,
        location = _this$props2$location === void 0 ? "/" : _this$props2$location,
        rest = Object(objectWithoutPropertiesLoose["a" /* default */])(_this$props2, ["basename", "context", "location"]);

    var history = {
      createHref: function createHref(path) {
        return addLeadingSlash(basename + createURL(path));
      },
      action: "POP",
      location: stripBasename(basename, Object(esm_history["b" /* createLocation */])(location)),
      push: this.handlePush,
      replace: this.handleReplace,
      go: staticHandler("go"),
      goBack: staticHandler("goBack"),
      goForward: staticHandler("goForward"),
      listen: this.handleListen,
      block: this.handleBlock
    };
    return /*#__PURE__*/external_React_default.a.createElement(react_router_Router, Object(esm_extends["a" /* default */])({}, rest, {
      history: history,
      staticContext: context
    }));
  };

  return StaticRouter;
}(external_React_default.a.Component);

if (false) {}

/**
 * The public API for rendering the first <Route> that matches.
 */

var react_router_Switch = /*#__PURE__*/function (_React$Component) {
  Object(inheritsLoose["a" /* default */])(Switch, _React$Component);

  function Switch() {
    return _React$Component.apply(this, arguments) || this;
  }

  var _proto = Switch.prototype;

  _proto.render = function render() {
    var _this = this;

    return /*#__PURE__*/external_React_default.a.createElement(react_router_context.Consumer, null, function (context) {
      !context ?  false ? undefined : Object(tiny_invariant_esm["a" /* default */])(false) : void 0;
      var location = _this.props.location || context.location;
      var element, match; // We use React.Children.forEach instead of React.Children.toArray().find()
      // here because toArray adds keys to all child elements and we do not want
      // to trigger an unmount/remount for two <Route>s that render the same
      // component at different URLs.

      external_React_default.a.Children.forEach(_this.props.children, function (child) {
        if (match == null && /*#__PURE__*/external_React_default.a.isValidElement(child)) {
          element = child;
          var path = child.props.path || child.props.from;
          match = path ? matchPath(location.pathname, Object(esm_extends["a" /* default */])({}, child.props, {
            path: path
          })) : context.match;
        }
      });
      return match ? /*#__PURE__*/external_React_default.a.cloneElement(element, {
        location: location,
        computedMatch: match
      }) : null;
    });
  };

  return Switch;
}(external_React_default.a.Component);

if (false) {}

/**
 * A public higher-order component to access the imperative API
 */

function withRouter(Component) {
  var displayName = "withRouter(" + (Component.displayName || Component.name) + ")";

  var C = function C(props) {
    var wrappedComponentRef = props.wrappedComponentRef,
        remainingProps = Object(objectWithoutPropertiesLoose["a" /* default */])(props, ["wrappedComponentRef"]);

    return /*#__PURE__*/external_React_default.a.createElement(react_router_context.Consumer, null, function (context) {
      !context ?  false ? undefined : Object(tiny_invariant_esm["a" /* default */])(false) : void 0;
      return /*#__PURE__*/external_React_default.a.createElement(Component, Object(esm_extends["a" /* default */])({}, remainingProps, context, {
        ref: wrappedComponentRef
      }));
    });
  };

  C.displayName = displayName;
  C.WrappedComponent = Component;

  if (false) {}

  return hoist_non_react_statics_cjs_default()(C, Component);
}

var useContext = external_React_default.a.useContext;
function useHistory() {
  if (false) {}

  return useContext(historyContext);
}
function useLocation() {
  if (false) {}

  return useContext(react_router_context).location;
}
function useParams() {
  if (false) {}

  var match = useContext(react_router_context).match;
  return match ? match.params : {};
}
function useRouteMatch(path) {
  if (false) {}

  var location = useLocation();
  var match = useContext(react_router_context).match;
  return path ? matchPath(location.pathname, path) : match;
}

if (false) { var secondaryBuildName, initialBuildName, buildNames, key, global; }


//# sourceMappingURL=react-router.js.map

// EXTERNAL MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/prop-types@15.8.1/node_modules/prop-types/index.js
var prop_types = __webpack_require__(1);
var prop_types_default = /*#__PURE__*/__webpack_require__.n(prop_types);

// EXTERNAL MODULE: external "lodash"
var external_lodash_ = __webpack_require__(5);

// EXTERNAL MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/qs@6.10.3/node_modules/qs/lib/index.js
var lib = __webpack_require__(31);

// EXTERNAL MODULE: external ["wc","navigation"]
var external_wc_navigation_ = __webpack_require__(13);

// EXTERNAL MODULE: external ["wc","data"]
var external_wc_data_ = __webpack_require__(12);

// EXTERNAL MODULE: external ["wc","tracks"]
var external_wc_tracks_ = __webpack_require__(17);

// EXTERNAL MODULE: external ["wc","notices"]
var external_wc_notices_ = __webpack_require__(311);

// EXTERNAL MODULE: external ["wp","plugins"]
var external_wp_plugins_ = __webpack_require__(66);

// EXTERNAL MODULE: ./client/layout/style.scss
var layout_style = __webpack_require__(312);

// EXTERNAL MODULE: external ["wp","hooks"]
var external_wp_hooks_ = __webpack_require__(27);

// EXTERNAL MODULE: external ["wp","i18n"]
var external_wp_i18n_ = __webpack_require__(2);

// EXTERNAL MODULE: external ["wc","components"]
var external_wc_components_ = __webpack_require__(22);

// EXTERNAL MODULE: ./client/analytics/report/get-reports.js
var get_reports = __webpack_require__(132);

// EXTERNAL MODULE: ./client/dashboard/utils.js
var utils = __webpack_require__(80);

// EXTERNAL MODULE: ./client/utils/admin-settings.js
var admin_settings = __webpack_require__(23);

// EXTERNAL MODULE: ./client/layout/NoMatch.tsx
var NoMatch = __webpack_require__(184);

// CONCATENATED MODULE: ./client/layout/controller.js


/**
 * External dependencies
 */







/**
 * Internal dependencies
 */





const AnalyticsReport = Object(external_wp_element_["lazy"])(() => __webpack_require__.e(/* import() | analytics-report */ 7).then(__webpack_require__.bind(null, 653)));
const AnalyticsSettings = Object(external_wp_element_["lazy"])(() => __webpack_require__.e(/* import() | analytics-settings */ 18).then(__webpack_require__.bind(null, 664)));
const Dashboard = Object(external_wp_element_["lazy"])(() => __webpack_require__.e(/* import() | dashboard */ 26).then(__webpack_require__.bind(null, 654)));
const Homescreen = Object(external_wp_element_["lazy"])(() => Promise.all(/* import() | homescreen */[__webpack_require__.e(1), __webpack_require__.e(2), __webpack_require__.e(4), __webpack_require__.e(32)]).then(__webpack_require__.bind(null, 660)));
const MarketingOverview = Object(external_wp_element_["lazy"])(() => Promise.all(/* import() | marketing-overview */[__webpack_require__.e(2), __webpack_require__.e(35)]).then(__webpack_require__.bind(null, 665)));
const ProfileWizard = Object(external_wp_element_["lazy"])(() => Promise.all(/* import() | profile-wizard */[__webpack_require__.e(1), __webpack_require__.e(48)]).then(__webpack_require__.bind(null, 661)));
const SettingsGroup = Object(external_wp_element_["lazy"])(() => Promise.all(/* import() | profile-wizard */[__webpack_require__.e(1), __webpack_require__.e(48)]).then(__webpack_require__.bind(null, 655)));
const WCPaymentsWelcomePage = Object(external_wp_element_["lazy"])(() => __webpack_require__.e(/* import() | wcpay-payment-welcome-page */ 55).then(__webpack_require__.bind(null, 663)));
const PAGES_FILTER = 'woocommerce_admin_pages_list';
const getPages = () => {
  const pages = [];
  const initialBreadcrumbs = [['', Object(admin_settings["d" /* getAdminSetting */])('woocommerceTranslation')]];
  pages.push({
    container: Homescreen,
    path: '/',
    breadcrumbs: [...initialBreadcrumbs, Object(external_wp_i18n_["__"])('Home', 'woocommerce-admin')],
    wpOpenMenu: 'toplevel_page_woocommerce',
    navArgs: {
      id: 'woocommerce-home'
    },
    capability: 'manage_woocommerce'
  });

  if (window.wcAdminFeatures.analytics) {
    pages.push({
      container: Dashboard,
      path: '/analytics/overview',
      breadcrumbs: [...initialBreadcrumbs, ['/analytics/overview', Object(external_wp_i18n_["__"])('Analytics', 'woocommerce-admin')], Object(external_wp_i18n_["__"])('Overview', 'woocommerce-admin')],
      wpOpenMenu: 'toplevel_page_wc-admin-path--analytics-overview',
      navArgs: {
        id: 'woocommerce-analytics-overview'
      },
      capability: 'view_woocommerce_reports'
    });
    pages.push({
      container: AnalyticsSettings,
      path: '/analytics/settings',
      breadcrumbs: [...initialBreadcrumbs, ['/analytics/revenue', Object(external_wp_i18n_["__"])('Analytics', 'woocommerce-admin')], Object(external_wp_i18n_["__"])('Settings', 'woocommerce-admin')],
      wpOpenMenu: 'toplevel_page_wc-admin-path--analytics-overview',
      navArgs: {
        id: 'woocommerce-analytics-settings'
      },
      capability: 'view_woocommerce_reports'
    });
    pages.push({
      container: AnalyticsReport,
      path: '/customers',
      breadcrumbs: [...initialBreadcrumbs, Object(external_wp_i18n_["__"])('Customers', 'woocommerce-admin')],
      wpOpenMenu: 'toplevel_page_woocommerce',
      navArgs: {
        id: 'woocommerce-analytics-customers'
      },
      capability: 'view_woocommerce_reports'
    });
    pages.push({
      container: AnalyticsReport,
      path: '/analytics/:report',
      breadcrumbs: _ref => {
        let {
          match
        } = _ref;
        const report = Object(external_lodash_["find"])(Object(get_reports["a" /* default */])(), {
          report: match.params.report
        });

        if (!report) {
          return [];
        }

        return [...initialBreadcrumbs, ['/analytics/revenue', Object(external_wp_i18n_["__"])('Analytics', 'woocommerce-admin')], report.title];
      },
      wpOpenMenu: 'toplevel_page_wc-admin-path--analytics-overview',
      capability: 'view_woocommerce_reports'
    });
  }

  if (window.wcAdminFeatures.marketing) {
    pages.push({
      container: MarketingOverview,
      path: '/marketing',
      breadcrumbs: [...initialBreadcrumbs, ['/marketing', Object(external_wp_i18n_["__"])('Marketing', 'woocommerce-admin')], Object(external_wp_i18n_["__"])('Overview', 'woocommerce-admin')],
      wpOpenMenu: 'toplevel_page_woocommerce-marketing',
      navArgs: {
        id: 'woocommerce-marketing-overview'
      },
      capability: 'view_woocommerce_reports'
    });
  }

  if (window.wcAdminFeatures.onboarding) {
    pages.push({
      container: ProfileWizard,
      path: '/setup-wizard',
      breadcrumbs: [...initialBreadcrumbs, Object(external_wp_i18n_["__"])('Setup Wizard', 'woocommerce-admin')],
      capability: 'manage_woocommerce'
    });
  }

  if (window.wcAdminFeatures.settings) {
    pages.push({
      container: SettingsGroup,
      path: '/settings/:page',
      breadcrumbs: _ref2 => {
        let {
          match
        } = _ref2;
        // @todo This might need to be refactored to retreive groups via data store.
        const settingsPages = Object(admin_settings["d" /* getAdminSetting */])('settingsPages');
        const page = settingsPages[match.params.page];

        if (!page) {
          return [];
        }

        return [...initialBreadcrumbs, [settingsPages.general ? '/settings/general' : `/settings/${Object.keys(settingsPages)[0]}`, Object(external_wp_i18n_["__"])('Settings', 'woocommerce-admin')], page];
      },
      wpOpenMenu: 'toplevel_page_woocommerce',
      capability: 'manage_woocommerce'
    });
  }

  if (window.wcAdminFeatures['wc-pay-welcome-page']) {
    pages.push({
      container: WCPaymentsWelcomePage,
      path: '/wc-pay-welcome-page',
      breadcrumbs: [['/wc-pay-welcome-page', Object(external_wp_i18n_["__"])('WooCommerce Payments', 'woocommerce-admin')], Object(external_wp_i18n_["__"])('WooCommerce Payments', 'woocommerce-admin')],
      navArgs: {
        id: 'woocommerce-wc-pay-welcome-page'
      },
      wpOpenMenu: 'toplevel_page_woocommerce-wc-pay-welcome-page',
      capability: 'manage_woocommerce'
    });
  }
  /**
   * List of WooCommerce Admin pages.
   *
   * @filter woocommerce_admin_pages_list
   * @param {Array.<Object>} pages Array page objects.
   */


  const filteredPages = Object(external_wp_hooks_["applyFilters"])(PAGES_FILTER, pages);
  filteredPages.push({
    container: NoMatch["a" /* NoMatch */],
    path: '*',
    breadcrumbs: [...initialBreadcrumbs, Object(external_wp_i18n_["__"])('Not allowed', 'woocommerce-admin')],
    wpOpenMenu: 'toplevel_page_woocommerce'
  });
  return filteredPages;
};
class controller_Controller extends external_wp_element_["Component"] {
  componentDidMount() {
    window.document.documentElement.scrollTop = 0;
    window.document.body.classList.remove('woocommerce-admin-is-loading');
  }

  componentDidUpdate(prevProps) {
    const prevBaseQuery = Object(external_lodash_["omit"])(prevProps.query, 'chartType', 'filter', 'paged');
    const baseQuery = Object(external_lodash_["omit"])(this.props.query, 'chartType', 'filter', 'paged');

    if (prevProps.query.paged > 1 && !Object(external_lodash_["isEqual"])(prevBaseQuery, baseQuery)) {
      Object(external_wc_navigation_["getHistory"])().replace(Object(external_wc_navigation_["getNewPath"])({
        paged: 1
      }));
    }

    if (prevProps.match.url !== this.props.match.url) {
      window.document.documentElement.scrollTop = 0;
    }
  }

  render() {
    const {
      page,
      match,
      query
    } = this.props;
    const {
      url,
      params
    } = match;
    window.wpNavMenuUrlUpdate(query);
    window.wpNavMenuClassChange(page, url);
    return Object(external_wp_element_["createElement"])(external_wp_element_["Suspense"], {
      fallback: Object(external_wp_element_["createElement"])(external_wc_components_["Spinner"], null)
    }, Object(external_wp_element_["createElement"])(page.container, {
      params: params,
      path: url,
      pathMatch: page.path,
      query: query
    }));
  }

}
/**
 * Update an anchor's link in sidebar to include persisted queries. Leave excluded screens
 * as is.
 *
 * @param {HTMLElement} item - Sidebar anchor link.
 * @param {Object} nextQuery - A query object to be added to updated hrefs.
 * @param {Array} excludedScreens - wc-admin screens to avoid updating.
 */

function updateLinkHref(item, nextQuery, excludedScreens) {
  if (Object(utils["f" /* isWCAdmin */])(item.href)) {
    const search = Object(external_lodash_["last"])(item.href.split('?'));
    const query = Object(lib["parse"])(search);
    const path = query.path || 'homescreen';
    const screen = Object(external_wc_navigation_["getScreenFromPath"])(path);
    const isExcludedScreen = excludedScreens.includes(screen);
    const href = 'admin.php?' + Object(lib["stringify"])(Object.assign(query, isExcludedScreen ? {} : nextQuery)); // Replace the href so you can see the url on hover.

    item.href = href;

    item.onclick = e => {
      e.preventDefault();
      Object(external_wc_navigation_["getHistory"])().push(href);
    };
  }
} // Update's wc-admin links in wp-admin menu

window.wpNavMenuUrlUpdate = function (query) {
  const nextQuery = Object(external_wc_navigation_["getPersistedQuery"])(query);
  const excludedScreens = Object(external_wc_navigation_["getQueryExcludedScreens"])();
  Array.from(document.querySelectorAll('#adminmenu a')).forEach(item => updateLinkHref(item, nextQuery, excludedScreens));
}; // When the route changes, we need to update wp-admin's menu with the correct section & current link


window.wpNavMenuClassChange = function (page, url) {
  const wpNavMenu = document.querySelector('#adminmenu');
  Array.from(wpNavMenu.getElementsByClassName('current')).forEach(function (item) {
    item.classList.remove('current');
  });
  const submenu = Array.from(wpNavMenu.querySelectorAll('.wp-has-current-submenu'));
  submenu.forEach(function (element) {
    element.classList.remove('wp-has-current-submenu');
    element.classList.remove('wp-menu-open');
    element.classList.remove('selected');
    element.classList.add('wp-not-current-submenu');
    element.classList.add('menu-top');
  });
  const pageUrl = url === '/' ? 'admin.php?page=wc-admin' : 'admin.php?page=wc-admin&path=' + encodeURIComponent(url);
  const currentItemsSelector = url === '/' ? `li > a[href$="${pageUrl}"], li > a[href*="${pageUrl}?"]` : `li > a[href*="${pageUrl}"]`;
  const currentItems = wpNavMenu.querySelectorAll(currentItemsSelector);
  Array.from(currentItems).forEach(function (item) {
    item.parentElement.classList.add('current');
  });

  if (page.wpOpenMenu) {
    const currentMenu = wpNavMenu.querySelector('#' + page.wpOpenMenu);

    if (currentMenu) {
      currentMenu.classList.remove('wp-not-current-submenu');
      currentMenu.classList.add('wp-has-current-submenu');
      currentMenu.classList.add('wp-menu-open');
      currentMenu.classList.add('current');
    }
  }

  const wpWrap = document.querySelector('#wpwrap');
  wpWrap.classList.remove('wp-responsive-open');
};
// EXTERNAL MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/classnames@2.3.1/node_modules/classnames/index.js
var classnames = __webpack_require__(7);
var classnames_default = /*#__PURE__*/__webpack_require__.n(classnames);

// EXTERNAL MODULE: external ["wp","htmlEntities"]
var external_wp_htmlEntities_ = __webpack_require__(34);

// EXTERNAL MODULE: external ["wc","wcSettings"]
var external_wc_wcSettings_ = __webpack_require__(15);

// EXTERNAL MODULE: external ["wc","experimental"]
var external_wc_experimental_ = __webpack_require__(19);

// EXTERNAL MODULE: ./client/header/style.scss
var header_style = __webpack_require__(313);

// CONCATENATED MODULE: ./client/hooks/useIsScrolled.js
/**
 * External dependencies
 */

function useIsScrolled() {
  const [isScrolled, setIsScrolled] = Object(external_wp_element_["useState"])(false);
  const rafHandle = Object(external_wp_element_["useRef"])(null);
  Object(external_wp_element_["useEffect"])(() => {
    const updateIsScrolled = () => {
      setIsScrolled(window.pageYOffset > 20);
    };

    const scrollListener = () => {
      rafHandle.current = window.requestAnimationFrame(updateIsScrolled);
    };

    window.addEventListener('scroll', scrollListener);
    return () => {
      window.removeEventListener('scroll', scrollListener);
      window.cancelAnimationFrame(rafHandle.current);
    };
  }, []);
  return isScrolled;
}
// EXTERNAL MODULE: ./client/header/utils.js
var header_utils = __webpack_require__(64);

// CONCATENATED MODULE: ./client/header/index.js


/**
 * External dependencies
 */






/**
 * Internal dependencies
 */




const PAGE_TITLE_FILTER = 'woocommerce_admin_header_page_title';
const Header = _ref => {
  var _pageTitleSlot$fills;

  let {
    sections,
    isEmbedded = false,
    query
  } = _ref;
  const headerElement = Object(external_wp_element_["useRef"])(null);
  const siteTitle = Object(external_wc_wcSettings_["getSetting"])('siteTitle', '');
  const pageTitle = sections.slice(-1)[0];
  const isScrolled = useIsScrolled();
  let debounceTimer = null;
  const className = classnames_default()('woocommerce-layout__header', {
    'is-scrolled': isScrolled
  });
  const pageTitleSlot = Object(external_wc_experimental_["useSlot"])('woocommerce_header_page_title');
  const hasPageTitleFills = Boolean(pageTitleSlot === null || pageTitleSlot === void 0 ? void 0 : (_pageTitleSlot$fills = pageTitleSlot.fills) === null || _pageTitleSlot$fills === void 0 ? void 0 : _pageTitleSlot$fills.length);
  const headerItemSlot = Object(external_wc_experimental_["useSlot"])('woocommerce_header_item');
  const headerItemSlotFills = headerItemSlot === null || headerItemSlot === void 0 ? void 0 : headerItemSlot.fills;
  Object(external_wp_element_["useLayoutEffect"])(() => {
    updateBodyMargin();
    window.addEventListener('resize', updateBodyMargin);
    return () => {
      window.removeEventListener('resize', updateBodyMargin);
      const wpBody = document.querySelector('#wpbody');

      if (!wpBody) {
        return;
      }

      wpBody.style.marginTop = null;
    };
  }, [headerItemSlotFills]);

  const updateBodyMargin = () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(function () {
      const wpBody = document.querySelector('#wpbody');

      if (!wpBody || !headerElement.current) {
        return;
      }

      wpBody.style.marginTop = `${headerElement.current.offsetHeight}px`;
    }, 200);
  };

  Object(external_wp_element_["useEffect"])(() => {
    if (!isEmbedded) {
      const documentTitle = sections.map(section => {
        return Array.isArray(section) ? section[1] : section;
      }).reverse().join(' &lsaquo; ');
      const decodedTitle = Object(external_wp_htmlEntities_["decodeEntities"])(Object(external_wp_i18n_["sprintf"])(
      /* translators: 1: document title. 2: page title */
      Object(external_wp_i18n_["__"])('%1$s &lsaquo; %2$s &#8212; WooCommerce', 'woocommerce-admin'), documentTitle, siteTitle));

      if (document.title !== decodedTitle) {
        document.title = decodedTitle;
      }
    }
  }, [isEmbedded, sections, siteTitle]);
  return Object(external_wp_element_["createElement"])("div", {
    className: className,
    ref: headerElement
  }, Object(external_wp_element_["createElement"])("div", {
    className: "woocommerce-layout__header-wrapper"
  }, Object(external_wp_element_["createElement"])(header_utils["b" /* WooHeaderNavigationItem */].Slot, {
    fillProps: {
      isEmbedded,
      query
    }
  }), Object(external_wp_element_["createElement"])(external_wc_experimental_["Text"], {
    className: `woocommerce-layout__header-heading`,
    as: "h1"
  }, Object(external_wp_htmlEntities_["decodeEntities"])(hasPageTitleFills ? Object(external_wp_element_["createElement"])(header_utils["c" /* WooHeaderPageTitle */].Slot, {
    fillProps: {
      isEmbedded,
      query
    }
  }) : pageTitle)), Object(external_wp_element_["createElement"])(header_utils["a" /* WooHeaderItem */].Slot, {
    fillProps: {
      isEmbedded,
      query
    }
  })));
};
// CONCATENATED MODULE: ./client/layout/notices.js


/**
 * External dependencies
 */


class notices_Notices extends external_wp_element_["Component"] {
  render() {
    return Object(external_wp_element_["createElement"])("div", {
      id: "woocommerce-layout__notice-list",
      className: "woocommerce-layout__notice-list"
    });
  }

}

/* harmony default export */ var layout_notices = (notices_Notices);
// EXTERNAL MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/@react-spring+web@9.4.3_react-dom@17.0.2+react@17.0.2/node_modules/@react-spring/web/dist/react-spring-web.esm.js
var react_spring_web_esm = __webpack_require__(175);

// EXTERNAL MODULE: ./client/layout/transient-notices/snackbar/index.js
var snackbar = __webpack_require__(277);

// CONCATENATED MODULE: ./client/layout/transient-notices/snackbar/list.js



/**
 * External dependencies
 */





/**
 * Internal dependencies
 */


/**
 * Renders a list of notices.
 *
 * @param  {Object}   $0           Props passed to the component.
 * @param  {Array}    $0.notices   Array of notices to render.
 * @param  {Function} $0.onRemove  Function called when a notice should be removed / dismissed.
 * @param  {Function} $0.onRemove2 Function called when a notice should be removed / dismissed.
 * @param  {Object}   $0.className Name of the class used by the component.
 * @param  {Object}   $0.children  Array of children to be rendered inside the notice list.
 * @return {Object}                The rendered notices list.
 */

function SnackbarList(_ref) {
  let {
    notices,
    className,
    children,
    onRemove = external_lodash_["noop"],
    onRemove2 = external_lodash_["noop"]
  } = _ref;
  const isReducedMotion = Object(external_wp_compose_["useReducedMotion"])();
  const [refMap] = Object(external_wp_element_["useState"])(() => new WeakMap());
  const transitions = Object(react_spring_web_esm["useTransition"])(notices, {
    keys: notice => notice.id,
    from: {
      opacity: 0,
      height: 0
    },
    enter: item => async next => await next({
      opacity: 1,
      height: refMap.get(item).offsetHeight
    }),
    leave: () => async next => {
      await next({
        opacity: 0
      });
      await next({
        height: 0
      });
    },
    immediate: isReducedMotion
  });
  className = classnames_default()('components-snackbar-list', className);

  const removeNotice = notice => () => {
    onRemove(notice.id); // To be removed when we're no longer using core/notices2.

    onRemove2(notice.id);
  };

  return Object(external_wp_element_["createElement"])("div", {
    className: className
  }, children, transitions((style, notice) => Object(external_wp_element_["createElement"])(react_spring_web_esm["animated"].div, {
    style: style
  }, Object(external_wp_element_["createElement"])("div", {
    className: "components-snackbar-list__notice-container",
    ref: ref => ref && refMap.set(notice, ref)
  }, Object(external_wp_element_["createElement"])(snackbar["a" /* default */], extends_default()({}, Object(external_lodash_["omit"])(notice, ['content']), {
    onRemove: removeNotice(notice)
  }), notice.content)))));
}

/* harmony default export */ var list = (SnackbarList);
// EXTERNAL MODULE: ./client/layout/transient-notices/style.scss
var transient_notices_style = __webpack_require__(314);

// CONCATENATED MODULE: ./client/layout/transient-notices/index.js


/**
 * External dependencies
 */






/**
 * Internal dependencies
 */



const QUEUE_OPTION = 'woocommerce_admin_transient_notices_queue';
const QUEUED_NOTICE_FILTER = 'woocommerce_admin_queued_notice_filter';

function TransientNotices(props) {
  const {
    removeNotice: onRemove
  } = Object(external_wp_data_["useDispatch"])('core/notices');
  const {
    createNotice: createNotice2,
    removeNotice: onRemove2
  } = Object(external_wp_data_["useDispatch"])('core/notices2');
  const {
    updateOptions
  } = Object(external_wp_data_["useDispatch"])(external_wc_data_["OPTIONS_STORE_NAME"]);
  const {
    currentUser = {},
    notices = [],
    notices2 = [],
    noticesQueue = {}
  } = Object(external_wp_data_["useSelect"])(select => {
    // NOTE: This uses core/notices2, if this file is copied back upstream
    // to Gutenberg this needs to be changed back to just core/notices.
    return {
      currentUser: select(external_wc_data_["USER_STORE_NAME"]).getCurrentUser(),
      notices: select('core/notices').getNotices(),
      notices2: select('core/notices2').getNotices(),
      noticesQueue: select(external_wc_data_["OPTIONS_STORE_NAME"]).getOption(QUEUE_OPTION)
    };
  });

  const getCurrentUserNotices = () => {
    return Object.values(noticesQueue).filter(notice => notice.user_id === currentUser.id || !notice.user_id);
  };

  const dequeueNotice = id => {
    const remainingNotices = { ...noticesQueue
    };
    delete remainingNotices[id];
    updateOptions({
      [QUEUE_OPTION]: remainingNotices
    });
  };

  Object(external_wp_element_["useEffect"])(() => {
    getCurrentUserNotices().forEach(queuedNotice => {
      /**
       * Filter each transient notice.
       *
       * @filter woocommerce_admin_queued_notice_filter
       * @param {Object} notice A transient notice.
       */
      const notice = Object(external_wp_hooks_["applyFilters"])(QUEUED_NOTICE_FILTER, queuedNotice);
      createNotice2(notice.status, notice.content, {
        onDismiss: () => {
          dequeueNotice(notice.id);
        },
        ...(notice.options || {})
      });
    });
  }, []);
  /**
   * Combines the two notices in the component vs in the useSelect, as we don't want to
   * create new object references on each useSelect call.
   */

  const getNotices = () => {
    return notices.concat(notices2);
  };

  const {
    className
  } = props;
  const classes = classnames_default()('woocommerce-transient-notices', 'components-notices__snackbar', className);
  const combinedNotices = getNotices();
  return Object(external_wp_element_["createElement"])(list, {
    notices: combinedNotices,
    className: classes,
    onRemove: onRemove,
    onRemove2: onRemove2
  });
}

TransientNotices.propTypes = {
  /**
   * Additional class name to style the component.
   */
  className: prop_types_default.a.string,

  /**
   * Array of notices to be displayed.
   */
  notices: prop_types_default.a.array
};
/* harmony default export */ var transient_notices = (TransientNotices);
// EXTERNAL MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/@wordpress+icons@6.3.0/node_modules/@wordpress/icons/build-module/icon/index.js
var build_module_icon = __webpack_require__(116);

// EXTERNAL MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/@wordpress+icons@6.3.0/node_modules/@wordpress/icons/build-module/library/help.js
var library_help = __webpack_require__(534);

// EXTERNAL MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/@wordpress+icons@6.3.0/node_modules/@wordpress/icons/build-module/library/external.js
var external = __webpack_require__(535);

// EXTERNAL MODULE: ./client/activity-panel/style.scss
var activity_panel_style = __webpack_require__(315);

// CONCATENATED MODULE: ./client/activity-panel/icon-flag.js

const IconFlag = () => Object(external_wp_element_["createElement"])("svg", {
  width: "24",
  height: "24",
  viewBox: "0 0 24 24",
  fill: "none",
  xmlns: "http://www.w3.org/2000/svg"
}, Object(external_wp_element_["createElement"])("mask", {
  id: "mask0_2915:6733",
  maskUnits: "userSpaceOnUse",
  x: "4",
  y: "3",
  width: "16",
  height: "18"
}, Object(external_wp_element_["createElement"])("path", {
  fillRule: "evenodd",
  clipRule: "evenodd",
  d: "M4.5 3.5H13.5L13.9 5.5H19.5V15.5H12.5L12.1 13.5H6.5V20.5H4.5V3.5ZM12.26 7.5L11.86 5.5H6.5V11.5H13.74L14.14 13.5H17.5V7.5H12.26Z",
  fill: "white"
})), Object(external_wp_element_["createElement"])("g", {
  mask: "url(#mask0_2915:6733)"
}, Object(external_wp_element_["createElement"])("rect", {
  width: "24",
  height: "24",
  fill: "#50575E"
})));
// EXTERNAL MODULE: ./client/inbox-panel/utils.js
var inbox_panel_utils = __webpack_require__(187);

// CONCATENATED MODULE: ./client/activity-panel/unread-indicators.js
/**
 * External dependencies
 */

/**
 * Internal dependencies
 */



const UNREAD_NOTES_QUERY = {
  page: 1,
  per_page: external_wc_data_["QUERY_DEFAULTS"].pageSize,
  status: 'unactioned',
  type: external_wc_data_["QUERY_DEFAULTS"].noteTypes,
  orderby: 'date',
  order: 'desc'
};
function isNotesPanelVisible(select) {
  const {
    getNotes,
    getNotesError,
    isResolving
  } = select(external_wc_data_["NOTES_STORE_NAME"]);
  const {
    getCurrentUser
  } = select(external_wc_data_["USER_STORE_NAME"]);
  const userData = getCurrentUser();
  const lastRead = parseInt(userData && userData.woocommerce_meta && userData.woocommerce_meta.activity_panel_inbox_last_read, 10);

  if (!lastRead) {
    return null;
  }

  getNotes(UNREAD_NOTES_QUERY);
  const isError = Boolean(getNotesError('getNotes', [UNREAD_NOTES_QUERY]));
  const isRequesting = isResolving('getNotes', [UNREAD_NOTES_QUERY]);

  if (isError || isRequesting) {
    return null;
  }

  const latestNotes = getNotes(UNREAD_NOTES_QUERY);
  const unreadNotesCount = Object(inbox_panel_utils["a" /* getUnreadNotesCount */])(latestNotes, lastRead);
  return unreadNotesCount > 0;
}
function getLowStockCount() {
  return Object(admin_settings["d" /* getAdminSetting */])('lowStockCount', 0);
}
// CONCATENATED MODULE: ./client/activity-panel/tab/index.js


/**
 * External dependencies
 */



const Tab = _ref => {
  let {
    icon,
    title,
    name,
    unread,
    selected,
    isPanelOpen,
    onTabClick
  } = _ref;
  const className = classnames_default()('woocommerce-layout__activity-panel-tab', {
    'is-active': isPanelOpen && selected,
    'has-unread': unread
  });
  const tabKey = `activity-panel-tab-${name}`;
  return Object(external_wp_element_["createElement"])(external_wp_components_["Button"], {
    role: "tab",
    className: className,
    "aria-selected": selected,
    "aria-controls": `activity-panel-${name}`,
    key: tabKey,
    id: tabKey,
    onClick: () => {
      onTabClick(name);
    }
  }, icon, title, ' ', unread && Object(external_wp_element_["createElement"])("span", {
    className: "screen-reader-text"
  }, Object(external_wp_i18n_["__"])('unread activity', 'woocommerce-admin')));
};
// CONCATENATED MODULE: ./client/activity-panel/tabs/index.js



/**
 * External dependencies
 */



/**
 * Internal dependencies
 */


const Tabs = _ref => {
  let {
    tabs,
    onTabClick,
    selectedTab: selectedTabName,
    tabOpen = false
  } = _ref;
  const [{
    tabOpen: tabIsOpenState,
    currentTab
  }, setTabState] = Object(external_wp_element_["useState"])({
    tabOpen,
    currentTab: selectedTabName
  }); // Keep state synced with props

  Object(external_wp_element_["useEffect"])(() => {
    setTabState({
      tabOpen,
      currentTab: selectedTabName
    });
  }, [tabOpen, selectedTabName]);
  return Object(external_wp_element_["createElement"])(external_wp_components_["NavigableMenu"], {
    role: "tablist",
    orientation: "horizontal",
    className: "woocommerce-layout__activity-panel-tabs"
  }, tabs && tabs.map((tab, i) => {
    if (tab.component) {
      const {
        component: Comp,
        options
      } = tab;
      return Object(external_wp_element_["createElement"])(Comp, extends_default()({
        key: i
      }, options));
    }

    return Object(external_wp_element_["createElement"])(Tab, extends_default()({
      key: i,
      index: i,
      isPanelOpen: tabIsOpenState,
      selected: currentTab === tab.name
    }, tab, {
      onTabClick: () => {
        const isTabOpen = currentTab === tab.name || currentTab === '' ? !tabIsOpenState : true; // If a panel is being opened, or if an existing panel is already open and a different one is being opened, record a track.

        if (!isTabOpen || currentTab !== tab.name) {
          Object(external_wc_tracks_["recordEvent"])('activity_panel_open', {
            tab: tab.name
          });
        }

        setTabState({
          tabOpen: isTabOpen,
          currentTab: tab.name
        });
        onTabClick(tab, isTabOpen);
      }
    }));
  }));
};
// CONCATENATED MODULE: ./client/activity-panel/setup-progress.js

const SetupProgress = () => Object(external_wp_element_["createElement"])("svg", {
  className: "woocommerce-layout__activity-panel-tab-icon setup-progress",
  width: "18",
  height: "18",
  viewBox: "0 0 24 24",
  fill: "none",
  xmlns: "http://www.w3.org/2000/svg"
}, Object(external_wp_element_["createElement"])("path", {
  d: "M12 20C16.4183 20 20 16.4183 20 12C20 7.58172 16.4183 4 12 4C7.58172 4 4 7.58172 4 12C4 16.4183 7.58172 20 12 20Z",
  stroke: "#DCDCDE",
  strokeWidth: "2"
}), Object(external_wp_element_["createElement"])("path", {
  d: "M4 12V12C4 16.4183 7.58172 20 12 20V20C16.4183 20 20 16.4183 20 12V12C20 7.58172 16.4183 4 12 4V4",
  strokeWidth: "2",
  strokeLinecap: "round"
}));
// EXTERNAL MODULE: ./client/activity-panel/display-options/index.js + 3 modules
var display_options = __webpack_require__(180);

// EXTERNAL MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/@wordpress+icons@6.3.0/node_modules/@wordpress/icons/build-module/library/close.js
var library_close = __webpack_require__(531);

// EXTERNAL MODULE: ./client/activity-panel/highlight-tooltip/style.scss
var highlight_tooltip_style = __webpack_require__(316);

// CONCATENATED MODULE: ./client/activity-panel/highlight-tooltip/index.js


/**
 * External dependencies
 */






/**
 * Internal dependencies
 */


const SHOW_CLASS = 'highlight-tooltip__show';

function HighlightTooltip(_ref) {
  let {
    title,
    closeButtonText,
    content,
    show = true,
    id,
    onClose,
    delay,
    onShow = external_lodash_["noop"],
    useAnchor = false
  } = _ref;
  const [showHighlight, setShowHighlight] = Object(external_wp_element_["useState"])(delay > 0 ? null : show);
  const [node, setNode] = Object(external_wp_element_["useState"])(null);
  const [anchorRect, setAnchorRect] = Object(external_wp_element_["useState"])(null);
  Object(external_wp_element_["useEffect"])(() => {
    const element = document.getElementById(id);
    let container, parent;

    if (element && !node) {
      // Add tooltip container
      if (!useAnchor) {
        parent = element.parentElement;
      } else {
        parent = document.createElement('div');
        document.body.appendChild(parent);
      }

      container = document.createElement('div');
      container.classList.add('highlight-tooltip__container');
      parent.appendChild(container);
      setNode(container);
    }

    const timeoutId = triggerShowTooltip(container);
    return () => {
      if (container) {
        const parentElement = container.parentElement;
        parentElement.removeChild(container);

        if (useAnchor) {
          parentElement.remove();
        }
      }

      if (timeoutId) {
        clearTimeout(timeoutId);
      }
    };
  }, []);
  Object(external_wp_element_["useEffect"])(() => {
    if (!showHighlight && node) {
      node.classList.remove(SHOW_CLASS);
    }
  }, [showHighlight]);
  Object(external_wp_element_["useEffect"])(() => {
    if (show !== showHighlight && showHighlight !== null && node) {
      setShowHighlight(show);

      if (!show) {
        node.classList.remove(SHOW_CLASS);
      } else if (node) {
        triggerShowTooltip(node);
      }
    }
  }, [show]);
  Object(external_wp_element_["useLayoutEffect"])(() => {
    window.addEventListener('resize', updateSize);
    return () => window.removeEventListener('resize', updateSize);
  }, []);

  function updateSize() {
    if (useAnchor) {
      const element = document.getElementById(id);
      setAnchorRect(element.getBoundingClientRect());
    }
  }

  const triggerShowTooltip = container => {
    let timeoutId = null;

    if (delay > 0) {
      timeoutId = setTimeout(() => {
        timeoutId = null;
        showTooltip(container);
      }, delay);
    } else if (!showHighlight) {
      showTooltip(container);
    }

    return timeoutId;
  };

  const showTooltip = container => {
    const element = document.getElementById(id);

    if (element && useAnchor) {
      setAnchorRect(element.getBoundingClientRect());
    }

    if (container) {
      container.classList.add(SHOW_CLASS);
    }

    setShowHighlight(true);
    onShow();
  };

  const triggerClose = () => {
    setShowHighlight(false);

    if (onClose) {
      onClose();
    }
  };

  if (!node) {
    return null;
  }

  return Object(external_wp_element_["createPortal"])(Object(external_wp_element_["createElement"])("div", {
    className: "highlight-tooltip__portal"
  }, showHighlight ? Object(external_wp_element_["createElement"])(external_wp_element_["Fragment"], null, Object(external_wp_element_["createElement"])(external_wp_components_["IsolatedEventContainer"], {
    className: "highlight-tooltip__overlay"
  }), Object(external_wp_element_["createElement"])(external_wp_components_["Popover"], {
    className: "highlight-tooltip__popover",
    noArrow: false,
    anchorRect: anchorRect,
    focusOnMount: "container"
  }, Object(external_wp_element_["createElement"])(external_wp_components_["Card"], {
    size: "medium"
  }, Object(external_wp_element_["createElement"])(external_wp_components_["CardHeader"], null, title, Object(external_wp_element_["createElement"])(external_wp_components_["Button"], {
    isSmall: true,
    onClick: triggerClose,
    icon: library_close["a" /* default */]
  })), Object(external_wp_element_["createElement"])(external_wp_components_["CardBody"], null, content || null), Object(external_wp_element_["createElement"])(external_wp_components_["CardFooter"], {
    isBorderless: true
  }, Object(external_wp_element_["createElement"])(external_wp_components_["Button"], {
    size: "small",
    isPrimary: true,
    onClick: triggerClose
  }, closeButtonText || Object(external_wp_i18n_["__"])('Close', 'woocommerce-admin')))))) : null), node);
}

HighlightTooltip.propTypes = {
  /**
   * The id of the element it should highlight, should be unique per HighlightTooltip.
   */
  id: prop_types_default.a.string.isRequired,

  /**
   * Title of the popup
   */
  title: prop_types_default.a.string.isRequired,

  /**
   * Text of the close button.
   */
  closeButtonText: prop_types_default.a.string.isRequired,

  /**
   * Content of the popup, can be either text or react element.
   */
  content: prop_types_default.a.oneOfType([prop_types_default.a.string, prop_types_default.a.node]),

  /**
   * If to show the popup, defaults to true.
   */
  show: prop_types_default.a.bool,

  /**
   * Callback for when the user closes the popup.
   */
  onClose: prop_types_default.a.func,

  /**
   * This will delay the popup from appearing by the number of ms.
   */
  delay: prop_types_default.a.number,

  /**
   * A callback for when the tooltip is shown.
   */
  onShow: prop_types_default.a.func,

  /**
   * useAnchor, will append the tooltip to the body tag, and make use of the anchorRect to display the tooltip.
   * Defaults to false.
   */
  useAnchor: prop_types_default.a.bool
};

// EXTERNAL MODULE: external ["wp","dom"]
var external_wp_dom_ = __webpack_require__(112);

// CONCATENATED MODULE: ./client/hooks/useFocusOnMount.js
/**
 * This hook was directly copied from https://github.com/WordPress/gutenberg/blob/master/packages/compose/src/hooks/use-focus-on-mount/index.js
 * to avoid its absence in older versions of WordPress.
 *
 * This can be removed once the minimum supported version of WordPress includes this hook.
 */

/**
 * External dependencies
 */


/**
 * Hook used to focus the first tabbable element on mount.
 *
 * @param {boolean|string} focusOnMount Focus on mount mode.
 * @return {Function} Ref callback.
 *
 * @example
 * ```js
 * import { useFocusOnMount } from '@wordpress/compose';
 *
 * const WithFocusOnMount = () => {
 *     const ref = useFocusOnMount()
 *     return (
 *         <div ref={ ref }>
 *             <Button />
 *             <Button />
 *         </div>
 *     );
 * }
 * ```
 */

function useFocusOnMount() {
  let focusOnMount = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : 'firstElement';
  const focusOnMountRef = Object(external_wp_element_["useRef"])(focusOnMount);
  Object(external_wp_element_["useEffect"])(() => {
    focusOnMountRef.current = focusOnMount;
  }, [focusOnMount]);
  return Object(external_wp_element_["useCallback"])(node => {
    if (!node || focusOnMountRef.current === false) {
      return;
    }

    if (node.contains(node.ownerDocument.activeElement)) {
      return;
    }

    let target = node;

    if (focusOnMountRef.current === 'firstElement') {
      const firstTabbable = external_wp_dom_["focus"].tabbable.find(node)[0];

      if (firstTabbable) {
        target = firstTabbable;
      }
    }

    target.focus();
  }, []);
}
// CONCATENATED MODULE: ./client/hooks/useFocusOutside.js
/**
 * External dependencies
 */


/**
 * Input types which are classified as button types, for use in considering
 * whether element is a (focus-normalized) button.
 *
 * @type {string[]}
 */

const INPUT_BUTTON_TYPES = ['button', 'submit'];
/**
 * @typedef {HTMLButtonElement | HTMLLinkElement | HTMLInputElement} FocusNormalizedButton
 */
// Disable reason: Rule doesn't support predicate return types

/* eslint-disable jsdoc/valid-types */

/**
 * Returns true if the given element is a button element subject to focus
 * normalization, or false otherwise.
 *
 * @see https://developer.mozilla.org/en-US/docs/Web/HTML/Element/button#Clicking_and_focus
 *
 * @param {EventTarget} eventTarget The target from a mouse or touch event.
 *
 * @return {eventTarget is FocusNormalizedButton} Whether element is a button.
 */

function isFocusNormalizedButton(eventTarget) {
  if (!(eventTarget instanceof window.HTMLElement)) {
    return false;
  }

  switch (eventTarget.nodeName) {
    case 'A':
    case 'BUTTON':
      return true;

    case 'INPUT':
      return Object(external_lodash_["includes"])(INPUT_BUTTON_TYPES,
      /** @type {HTMLInputElement} */
      eventTarget.type);
  }

  return false;
}
/* eslint-enable jsdoc/valid-types */

/**
 * @typedef {import('react').SyntheticEvent} SyntheticEvent
 */

/**
 * @callback EventCallback
 * @param {SyntheticEvent} event input related event.
 */

/**
 * @typedef FocusOutsideReactElement
 * @property {EventCallback} handleFocusOutside callback for a focus outside event.
 */

/**
 * @typedef {import('react').MutableRefObject<FocusOutsideReactElement | undefined>} FocusOutsideRef
 */

/**
 * @typedef {Object} FocusOutsideReturnValue
 * @property {EventCallback} onFocus      An event handler for focus events.
 * @property {EventCallback} onBlur       An event handler for blur events.
 * @property {EventCallback} onMouseDown  An event handler for mouse down events.
 * @property {EventCallback} onMouseUp    An event handler for mouse up events.
 * @property {EventCallback} onTouchStart An event handler for touch start events.
 * @property {EventCallback} onTouchEnd   An event handler for touch end events.
 */

/**
 * A react hook that can be used to check whether focus has moved outside the
 * element the event handlers are bound to.
 *
 * @param {EventCallback} onFocusOutside        A callback triggered when focus moves outside
 *                                              the element the event handlers are bound to.
 *
 * @return {FocusOutsideReturnValue} An object containing event handlers. Bind the event handlers
 *                                   to a wrapping element element to capture when focus moves
 *                                   outside that element.
 */


function useFocusOutside(onFocusOutside) {
  const currentOnFocusOutside = Object(external_wp_element_["useRef"])(onFocusOutside);
  Object(external_wp_element_["useEffect"])(() => {
    currentOnFocusOutside.current = onFocusOutside;
  }, [onFocusOutside]);
  const preventBlurCheck = Object(external_wp_element_["useRef"])(false);
  /**
   * @type {import('react').MutableRefObject<number | undefined>}
   */

  const blurCheckTimeoutId = Object(external_wp_element_["useRef"])();
  /**
   * Cancel a blur check timeout.
   */

  const cancelBlurCheck = Object(external_wp_element_["useCallback"])(() => {
    clearTimeout(blurCheckTimeoutId.current);
  }, []); // Cancel blur checks on unmount.

  Object(external_wp_element_["useEffect"])(() => {
    return () => cancelBlurCheck();
  }, []); // Cancel a blur check if the callback or ref is no longer provided.

  Object(external_wp_element_["useEffect"])(() => {
    if (!onFocusOutside) {
      cancelBlurCheck();
    }
  }, [onFocusOutside, cancelBlurCheck]);
  /**
   * Handles a mousedown or mouseup event to respectively assign and
   * unassign a flag for preventing blur check on button elements. Some
   * browsers, namely Firefox and Safari, do not emit a focus event on
   * button elements when clicked, while others do. The logic here
   * intends to normalize this as treating click on buttons as focus.
   *
   * @see https://developer.mozilla.org/en-US/docs/Web/HTML/Element/button#Clicking_and_focus
   *
   * @param {SyntheticEvent} event Event for mousedown or mouseup.
   */

  const normalizeButtonFocus = Object(external_wp_element_["useCallback"])(event => {
    const {
      type,
      target
    } = event;
    const isInteractionEnd = Object(external_lodash_["includes"])(['mouseup', 'touchend'], type);

    if (isInteractionEnd) {
      preventBlurCheck.current = false;
    } else if (isFocusNormalizedButton(target)) {
      preventBlurCheck.current = true;
    }
  }, []);
  /**
   * A callback triggered when a blur event occurs on the element the handler
   * is bound to.
   *
   * Calls the `onFocusOutside` callback in an immediate timeout if focus has
   * move outside the bound element and is still within the document.
   *
   * @param {SyntheticEvent} event Blur event.
   */

  const queueBlurCheck = Object(external_wp_element_["useCallback"])(event => {
    // React does not allow using an event reference asynchronously
    // due to recycling behavior, except when explicitly persisted.
    event.persist(); // Skip blur check if clicking button. See `normalizeButtonFocus`.

    if (preventBlurCheck.current) {
      return;
    }

    blurCheckTimeoutId.current = setTimeout(() => {
      // If document is not focused then focus should remain
      // inside the wrapped component and therefore we cancel
      // this blur event thereby leaving focus in place.
      // https://developer.mozilla.org/en-US/docs/Web/API/Document/hasFocus.
      if (!document.hasFocus()) {
        event.preventDefault();
        return;
      }

      if (typeof currentOnFocusOutside.current === 'function') {
        currentOnFocusOutside.current(event);
      }
    }, 0);
  }, []);
  return {
    onFocus: cancelBlurCheck,
    onMouseDown: normalizeButtonFocus,
    onMouseUp: normalizeButtonFocus,
    onTouchStart: normalizeButtonFocus,
    onTouchEnd: normalizeButtonFocus,
    onBlur: queueBlurCheck
  };
}
// CONCATENATED MODULE: ./client/activity-panel/panel.js



/**
 * External dependencies
 */



/**
 * Internal dependencies
 */



const Panel = _ref => {
  let {
    content,
    isPanelOpen,
    isPanelSwitching,
    currentTab,
    tab,
    closePanel,
    clearPanel
  } = _ref;
  const panelClass = 'woocommerce-layout__activity-panel-wrapper';

  const handleFocusOutside = event => {
    const isClickOnModalOrSnackbar = event.relatedTarget && (event.relatedTarget.closest('.woocommerce-inbox-dismiss-confirmation_modal') || event.relatedTarget.closest('.components-snackbar__action'));

    if (isPanelOpen && !isClickOnModalOrSnackbar) {
      closePanel();
    }
  };

  const possibleFocusPanel = () => {
    if (!containerRef.current || !isPanelOpen || !tab) {
      return;
    }

    focusOnMountRef(containerRef.current);
  };

  const finishTransition = e => {
    if (e && e.propertyName === 'transform') {
      clearPanel();
      possibleFocusPanel();
    }
  };

  const focusOnMountRef = useFocusOnMount();
  const useFocusOutsideProps = useFocusOutside(handleFocusOutside);
  const containerRef = Object(external_wp_element_["useRef"])(null);
  const mergedContainerRef = Object(external_wp_element_["useCallback"])(node => {
    containerRef.current = node;
    focusOnMountRef(node);
  }, []);

  if (!tab) {
    return Object(external_wp_element_["createElement"])("div", {
      className: panelClass
    });
  }

  if (!content) {
    return null;
  }

  const classNames = classnames_default()(panelClass, {
    'is-open': isPanelOpen,
    'is-switching': isPanelSwitching
  });
  return Object(external_wp_element_["createElement"])("div", extends_default()({
    className: classNames,
    tabIndex: 0,
    role: "tabpanel",
    "aria-label": tab.title,
    onTransitionEnd: finishTransition
  }, useFocusOutsideProps, {
    ref: mergedContainerRef
  }), Object(external_wp_element_["createElement"])("div", {
    className: "woocommerce-layout__activity-panel-content",
    key: 'activity-panel-' + currentTab,
    id: 'activity-panel-' + currentTab
  }, Object(external_wp_element_["createElement"])(external_wp_element_["Suspense"], {
    fallback: Object(external_wp_element_["createElement"])(external_wc_components_["Spinner"], null)
  }, content)));
};
/* harmony default export */ var panel = (Panel);
// EXTERNAL MODULE: ./client/homescreen/activity-panel/orders/utils.js
var orders_utils = __webpack_require__(88);

// EXTERNAL MODULE: ./client/homescreen/activity-panel/reviews/utils.js
var reviews_utils = __webpack_require__(115);

// EXTERNAL MODULE: ./client/activity-panel/panels/inbox/abbreviated-notifications-panel.js + 2 modules
var abbreviated_notifications_panel = __webpack_require__(275);

// CONCATENATED MODULE: ./client/activity-panel/activity-panel.js


/**
 * External dependencies
 */











/**
 * Internal dependencies
 */













const HelpPanel = Object(external_wp_element_["lazy"])(() => __webpack_require__.e(/* import() | activity-panels-help */ 5).then(__webpack_require__.bind(null, 666)));
const InboxPanel = Object(external_wp_element_["lazy"])(() => Promise.all(/* import() | activity-panels-inbox */[__webpack_require__.e(2), __webpack_require__.e(4), __webpack_require__.e(6)]).then(__webpack_require__.bind(null, 656)));
const ActivityPanel = _ref => {
  let {
    isEmbedded,
    query
  } = _ref;
  const [currentTab, setCurrentTab] = Object(external_wp_element_["useState"])('');
  const [isPanelClosing, setIsPanelClosing] = Object(external_wp_element_["useState"])(false);
  const [isPanelOpen, setIsPanelOpen] = Object(external_wp_element_["useState"])(false);
  const [isPanelSwitching, setIsPanelSwitching] = Object(external_wp_element_["useState"])(false);
  const {
    fills
  } = Object(external_wc_experimental_["useSlot"])(abbreviated_notifications_panel["a" /* ABBREVIATED_NOTIFICATION_SLOT_NAME */]);
  const hasExtendedNotifications = Boolean(fills === null || fills === void 0 ? void 0 : fills.length);
  const {
    updateUserPreferences,
    ...userData
  } = Object(external_wc_data_["useUserPreferences"])();

  const getPreviewSiteBtnTrackData = (select, getOption) => {
    let trackData = {};

    if (query.page === 'wc-admin' && query.task === 'appearance') {
      var _task$additionalData, _task$additionalData2, _task$additionalData3;

      const {
        getTaskLists
      } = select(external_wc_data_["ONBOARDING_STORE_NAME"]);
      const taskLists = getTaskLists();
      const tasks = taskLists.reduce((acc, taskList) => [...acc, ...taskList.tasks], []);
      const task = tasks.find(t => t.id === 'appearance');
      const demoNotice = getOption('woocommerce_demo_store_notice');
      trackData = {
        set_notice: demoNotice ? 'Y' : 'N',
        create_homepage: (task === null || task === void 0 ? void 0 : (_task$additionalData = task.additionalData) === null || _task$additionalData === void 0 ? void 0 : _task$additionalData.hasHomepage) === true ? 'Y' : 'N',
        upload_logo: task !== null && task !== void 0 && (_task$additionalData2 = task.additionalData) !== null && _task$additionalData2 !== void 0 && (_task$additionalData3 = _task$additionalData2.themeMods) !== null && _task$additionalData3 !== void 0 && _task$additionalData3.custom_logo ? 'Y' : 'N'
      };
    }

    return trackData;
  };

  function getThingsToDoNextCount(extendedTaskList) {
    if (!extendedTaskList || !extendedTaskList.tasks.length || extendedTaskList.isHidden) {
      return 0;
    }

    return extendedTaskList.tasks.filter(task => task.canView && !task.isComplete && !task.isDismissed).length;
  }

  function isAbbreviatedPanelVisible(select, setupTaskListHidden, thingsToDoNextCount) {
    const orderStatuses = Object(orders_utils["c" /* getOrderStatuses */])(select);
    const isOrdersCardVisible = setupTaskListHidden && isPanelOpen ? Object(orders_utils["d" /* getUnreadOrders */])(select, orderStatuses) > 0 : false;
    const isReviewsCardVisible = setupTaskListHidden && isPanelOpen ? Object(reviews_utils["b" /* getUnapprovedReviews */])(select) : false;
    const isLowStockCardVisible = setupTaskListHidden && isPanelOpen ? Object(orders_utils["a" /* getLowStockCount */])(select) : false;
    return thingsToDoNextCount > 0 || isOrdersCardVisible || isReviewsCardVisible || isLowStockCardVisible || hasExtendedNotifications;
  }

  const {
    hasUnreadNotes,
    hasAbbreviatedNotifications,
    isCompletedTask,
    thingsToDoNextCount,
    requestingTaskListOptions,
    setupTaskListComplete,
    setupTaskListHidden,
    previewSiteBtnTrackData
  } = Object(external_wp_data_["useSelect"])(select => {
    var _getTaskList, _getTaskList2, _getTask;

    const {
      getOption
    } = select(external_wc_data_["OPTIONS_STORE_NAME"]);
    const {
      getTask,
      getTaskList,
      hasFinishedResolution
    } = select(external_wc_data_["ONBOARDING_STORE_NAME"]);
    const isSetupTaskListHidden = (_getTaskList = getTaskList('setup')) === null || _getTaskList === void 0 ? void 0 : _getTaskList.isHidden;
    const extendedTaskList = getTaskList('extended');
    const thingsToDoCount = getThingsToDoNextCount(extendedTaskList);
    return {
      hasUnreadNotes: isNotesPanelVisible(select),
      hasAbbreviatedNotifications: isAbbreviatedPanelVisible(select, isSetupTaskListHidden, thingsToDoCount),
      thingsToDoNextCount: thingsToDoCount,
      requestingTaskListOptions: !hasFinishedResolution('getTaskLists'),
      setupTaskListComplete: (_getTaskList2 = getTaskList('setup')) === null || _getTaskList2 === void 0 ? void 0 : _getTaskList2.isComplete,
      setupTaskListHidden: isSetupTaskListHidden,
      isCompletedTask: Boolean(query.task && ((_getTask = getTask(query.task)) === null || _getTask === void 0 ? void 0 : _getTask.isComplete)),
      previewSiteBtnTrackData: getPreviewSiteBtnTrackData(select, getOption)
    };
  });
  const {
    unhideTaskList
  } = Object(external_wp_data_["useDispatch"])(external_wc_data_["ONBOARDING_STORE_NAME"]);
  const {
    currentUserCan
  } = Object(external_wc_data_["useUser"])();

  const togglePanel = (_ref2, isTabOpen) => {
    let {
      name: tabName
    } = _ref2;
    const panelSwitching = tabName !== currentTab && currentTab !== '' && isTabOpen && isPanelOpen;

    if (isPanelClosing) {
      return;
    }

    setCurrentTab(tabName);
    setIsPanelOpen(isTabOpen);
    setIsPanelSwitching(panelSwitching);
  };

  const closePanel = () => {
    setIsPanelClosing(true);
    setIsPanelOpen(false);
  };

  const clearPanel = () => {
    if (!isPanelOpen) {
      setIsPanelClosing(false);
      setIsPanelSwitching(false);
      setCurrentTab('');
    }
  };

  const isHomescreen = () => {
    return query.page === 'wc-admin' && !query.path;
  };

  const isPerformingSetupTask = () => {
    return query.task && !query.path && (requestingTaskListOptions === true || setupTaskListHidden === false && setupTaskListComplete === false);
  };

  const redirectToHomeScreen = () => {
    if (Object(utils["f" /* isWCAdmin */])(window.location.href)) {
      Object(external_wc_navigation_["getHistory"])().push(Object(external_wc_navigation_["getNewPath"])({}, '/', {}));
    } else {
      window.location.href = Object(external_wc_wcSettings_["getAdminLink"])('admin.php?page=wc-admin');
    }
  }; // @todo Pull in dynamic unread status/count


  const getTabs = () => {
    const activity = {
      name: 'activity',
      title: Object(external_wp_i18n_["__"])('Activity', 'woocommerce-admin'),
      icon: Object(external_wp_element_["createElement"])(IconFlag, null),
      unread: hasUnreadNotes || hasAbbreviatedNotifications,
      visible: (isEmbedded || !isHomescreen()) && !isPerformingSetupTask()
    };
    const setup = {
      name: 'setup',
      title: Object(external_wp_i18n_["__"])('Finish setup', 'woocommerce-admin'),
      icon: Object(external_wp_element_["createElement"])(SetupProgress, null),
      onClick: () => {
        const currentLocation = window.location.href;
        const homescreenLocation = Object(external_wc_wcSettings_["getAdminLink"])('admin.php?page=wc-admin'); // Don't navigate if we're already on the homescreen, this will cause an infinite loop

        if (currentLocation !== homescreenLocation) {
          // Ensure that if the user is trying to get to the task list they can see it even if
          // it was dismissed.
          if (setupTaskListHidden === 'no') {
            redirectToHomeScreen();
          } else {
            unhideTaskList('setup').then(redirectToHomeScreen);
          }
        }

        return null;
      },
      visible: currentUserCan('manage_woocommerce') && !requestingTaskListOptions && !setupTaskListComplete && !setupTaskListHidden && !isPerformingSetupTask() && (!isHomescreen() || isEmbedded)
    };
    const help = {
      name: 'help',
      title: Object(external_wp_i18n_["__"])('Help', 'woocommerce-admin'),
      icon: Object(external_wp_element_["createElement"])(build_module_icon["a" /* default */], {
        icon: library_help["a" /* default */]
      }),
      visible: currentUserCan('manage_woocommerce') && (isHomescreen() && !isEmbedded || isPerformingSetupTask())
    };
    const displayOptions = {
      component: display_options["b" /* DisplayOptions */],
      visible: currentUserCan('manage_woocommerce') && !isEmbedded && isHomescreen() && !isPerformingSetupTask()
    };
    const previewSite = {
      name: 'previewSite',
      title: Object(external_wp_i18n_["__"])('Preview site', 'woocommerce-admin'),
      icon: Object(external_wp_element_["createElement"])(build_module_icon["a" /* default */], {
        icon: external["a" /* default */]
      }),
      visible: query.page === 'wc-admin' && query.task === 'appearance',
      onClick: () => {
        window.open(Object(external_wc_wcSettings_["getSetting"])('siteUrl'));
        Object(external_wc_tracks_["recordEvent"])('wcadmin_tasklist_previewsite', previewSiteBtnTrackData);
        return null;
      }
    };
    return [activity, setup, previewSite, displayOptions, help].filter(tab => tab.visible);
  };

  const getPanelContent = tab => {
    const {
      task
    } = query;

    switch (tab) {
      case 'activity':
        return Object(external_wp_element_["createElement"])(InboxPanel, {
          hasAbbreviatedNotifications: hasAbbreviatedNotifications,
          thingsToDoNextCount: thingsToDoNextCount
        });

      case 'help':
        return Object(external_wp_element_["createElement"])(HelpPanel, {
          taskName: task
        });

      default:
        return null;
    }
  };

  const closedHelpPanelHighlight = () => {
    Object(external_wc_tracks_["recordEvent"])('help_tooltip_click');

    if (userData && updateUserPreferences) {
      updateUserPreferences({
        help_panel_highlight_shown: 'yes'
      });
    }
  };

  const shouldShowHelpTooltip = () => {
    const {
      task
    } = query;
    const startedTasks = userData && userData.task_list_tracked_started_tasks;
    const highlightShown = userData && userData.help_panel_highlight_shown;

    if (task && highlightShown !== 'yes' && (startedTasks || {})[task] > 1 && !isCompletedTask) {
      return true;
    }

    return false;
  };

  const tabs = getTabs();
  const headerId = Object(external_lodash_["uniqueId"])('activity-panel-header_');
  const showHelpHighlightTooltip = shouldShowHelpTooltip();
  return Object(external_wp_element_["createElement"])("div", null, Object(external_wp_element_["createElement"])(external_wc_components_["H"], {
    id: headerId,
    className: "screen-reader-text"
  }, Object(external_wp_i18n_["__"])('Store Activity', 'woocommerce-admin')), Object(external_wp_element_["createElement"])(external_wc_components_["Section"], {
    component: "aside",
    id: "woocommerce-activity-panel",
    className: "woocommerce-layout__activity-panel",
    "aria-labelledby": headerId
  }, Object(external_wp_element_["createElement"])(Tabs, {
    tabs: tabs,
    tabOpen: isPanelOpen,
    selectedTab: currentTab,
    onTabClick: (tab, tabOpen) => {
      if (tab.onClick) {
        tab.onClick();
        return;
      }

      togglePanel(tab, tabOpen);
    }
  }), Object(external_wp_element_["createElement"])(Panel, {
    currentTab: true,
    isPanelOpen: isPanelOpen,
    isPanelSwitching: isPanelSwitching,
    tab: Object(external_lodash_["find"])(getTabs(), {
      name: currentTab
    }),
    content: getPanelContent(currentTab),
    closePanel: () => closePanel(),
    clearPanel: () => clearPanel()
  })), showHelpHighlightTooltip ? Object(external_wp_element_["createElement"])(HighlightTooltip, {
    delay: 1000,
    useAnchor: true,
    title: Object(external_wp_i18n_["__"])("We're here for help", 'woocommerce-admin'),
    content: Object(external_wp_i18n_["__"])('If you have any questions, feel free to explore the WooCommerce docs listed here.', 'woocommerce-admin'),
    closeButtonText: Object(external_wp_i18n_["__"])('Got it', 'woocommerce-admin'),
    id: "activity-panel-tab-help",
    onClose: () => closedHelpPanelHighlight(),
    onShow: () => Object(external_wc_tracks_["recordEvent"])('help_tooltip_view')
  }) : null);
};
ActivityPanel.defaultProps = {
  getHistory: external_wc_navigation_["getHistory"]
};
/* harmony default export */ var activity_panel = (ActivityPanel);
// CONCATENATED MODULE: ./client/activity-panel/index.js


/**
 * External dependencies
 */

/**
 * Internal dependencies
 */




const ActivityPanelHeaderItem = () => Object(external_wp_element_["createElement"])(header_utils["a" /* WooHeaderItem */], {
  order: 20
}, _ref => {
  let {
    isEmbedded,
    query
  } = _ref;

  if (!window.wcAdminFeatures['activity-panels']) {
    return null;
  }

  return Object(external_wp_element_["createElement"])(activity_panel, {
    isEmbedded: isEmbedded,
    query: query
  });
});

Object(external_wp_plugins_["registerPlugin"])('activity-panel-header-item', {
  render: ActivityPanelHeaderItem,
  scope: 'woocommerce-admin'
});
// EXTERNAL MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/gridicons@3.4.0_react@17.0.2/node_modules/gridicons/dist/cross-small.js
var cross_small = __webpack_require__(280);
var cross_small_default = /*#__PURE__*/__webpack_require__.n(cross_small);

// CONCATENATED MODULE: ./client/lib/platform/index.js
const ANDROID_PLATFORM = 'android';
const IOS_PLATFORM = 'ios';
const UNKNOWN_PLATFORM = 'unknown';
/**
 * Provide basic detection of platform based on user agent. This is not
 * a robust check for browser features or the like. You should only use
 * this for non-critical display logic.
 */

const platform = () => {
  if (/iPhone|iPad|iPod/i.test(window.navigator.userAgent)) {
    return IOS_PLATFORM;
  } else if (/Android/i.test(window.navigator.userAgent)) {
    return ANDROID_PLATFORM;
  }

  return UNKNOWN_PLATFORM;
};
// CONCATENATED MODULE: ./client/mobile-banner/app-icon.js

const AppIcon = () => {
  return Object(external_wp_element_["createElement"])("svg", {
    width: "37",
    height: "37",
    viewBox: "0 0 92 92",
    fill: "none",
    xmlns: "http://www.w3.org/2000/svg"
  }, Object(external_wp_element_["createElement"])("rect", {
    width: "92",
    height: "92",
    rx: "21.3953",
    fill: "#7F54B3"
  }), Object(external_wp_element_["createElement"])("path", {
    fillRule: "evenodd",
    clipRule: "evenodd",
    d: "M72.5937 28.043H19.8094C16.4781 28.0459 13.7783 30.7705 13.7754 34.1324V54.4501C13.7783 57.812 16.4781 60.5366 19.8094 60.5395H44.8229L56.2573 66.9607L53.6672 60.5395H72.599C74.2009 60.5402 75.7374 59.8983 76.8702 58.7552C78.0029 57.612 78.639 56.0614 78.6383 54.4447V34.1324C78.6376 32.5157 78.0002 30.9657 76.8664 29.8235C75.7327 28.6814 74.1956 28.0408 72.5937 28.043ZM19.1057 32.4208C18.4658 32.4324 17.8646 32.7359 17.467 33.2482C17.0888 33.7635 16.9404 34.4175 17.058 35.0502C18.5962 45.0986 20.0338 51.8757 21.371 55.3816C21.8779 56.658 22.4896 57.2703 23.2063 57.2185C24.3075 57.1489 25.6263 55.5968 27.1627 52.5621C27.9964 50.8412 29.2602 48.2662 30.9539 44.837C32.3785 49.88 34.309 53.6787 36.7456 56.2331C37.4291 56.9436 38.1204 57.2748 38.8195 57.2266C39.4185 57.1931 39.953 56.8315 40.217 56.2813C40.4753 55.7358 40.5806 55.1278 40.5211 54.5248C40.3516 52.0703 40.5919 48.667 41.2421 44.3149C41.9081 39.8057 42.7523 36.5818 43.7749 34.6432C43.9822 34.2526 44.0733 33.8087 44.037 33.366C44.0039 32.7587 43.7116 32.1969 43.2374 31.829C42.7745 31.4367 42.1799 31.2446 41.5803 31.2935C40.8334 31.3325 40.1682 31.7885 39.8499 32.4797C38.2331 35.5019 37.0812 40.4109 36.3943 47.2068C35.2823 44.2394 34.4509 41.1703 33.9114 38.0412C33.623 36.4613 32.9037 35.7125 31.7536 35.7946C30.9592 35.8589 30.3063 36.3944 29.7819 37.4012L24.0348 48.5643C23.0997 44.6692 22.2205 39.9289 21.3972 34.3433C21.1997 32.9652 20.4358 32.3244 19.1057 32.4208ZM69.9089 34.6877C71.6969 35.0381 73.2407 36.2 74.1186 37.8559C74.9693 39.3247 75.3946 41.1161 75.3946 43.23C75.4148 45.9567 74.7062 48.6357 73.3477 50.9687C71.7778 53.7023 69.7195 55.0691 67.1727 55.0691C66.6933 55.0668 66.2153 55.0128 65.7467 54.9078C63.9584 54.5581 62.4143 53.396 61.5371 51.7396C60.6864 50.2452 60.261 48.4411 60.261 46.3272C60.2357 43.6127 60.945 40.9454 62.3079 38.6295C63.9023 35.8959 65.9607 34.5291 68.4829 34.5291C68.9623 34.5304 69.4402 34.5836 69.9089 34.6877ZM68.7937 49.4848C69.7707 48.5773 70.4399 47.2269 70.8012 45.4337V45.4419C70.9315 44.7826 70.9959 44.1112 70.9933 43.4382C70.986 42.5849 70.8291 41.74 70.5302 40.9452C70.1443 39.901 69.6304 39.3124 68.9884 39.1793C68.0378 38.9643 67.1239 39.5256 66.2469 40.8632C65.5812 41.8393 65.109 42.9432 64.8577 44.1106C64.7276 44.7708 64.6632 45.4432 64.6657 46.1171C64.6739 46.9677 64.8308 47.8096 65.1287 48.6019C65.5146 49.6388 66.0294 50.2274 66.6731 50.3678C67.3169 50.5081 68.0237 50.2138 68.7937 49.4848ZM57.9079 37.8559C57.0291 36.2008 55.4854 35.0392 53.6976 34.6877C53.2279 34.5837 52.749 34.5306 52.2687 34.5291C49.7443 34.5291 47.6856 35.8959 46.0927 38.6295C44.7295 40.9454 44.0201 43.6127 44.0454 46.3272C44.0454 48.4411 44.4699 50.2452 45.319 51.7396C46.1976 53.3949 47.7414 54.5566 49.5294 54.9078C49.999 55.0126 50.4779 55.0667 50.9582 55.0691C53.5055 55.0691 55.5642 53.7023 57.1343 50.9687C58.4922 48.6355 59.2001 45.9565 59.1789 43.23C59.1789 41.1161 58.7544 39.3247 57.9053 37.8559H57.9079ZM54.5903 45.4337C54.2307 47.2269 53.5614 48.5773 52.5825 49.4848C51.8115 50.2065 51.101 50.5017 50.4589 50.3678C49.8169 50.2338 49.3011 49.6461 48.9169 48.6019C48.6181 47.8097 48.4603 46.9678 48.4511 46.1171C48.4495 45.4431 48.5148 44.7707 48.6459 44.1106C48.8971 42.9432 49.3694 41.8393 50.0353 40.8632C50.9124 39.5256 51.8264 38.9643 52.7773 39.1793C53.4193 39.3124 53.9333 39.901 54.3193 40.9452C54.617 41.7404 54.7739 42.585 54.7824 43.4382C54.785 44.1112 54.7207 44.7826 54.5903 45.4419V45.4337Z",
    fill: "white"
  }));
};
// CONCATENATED MODULE: ./client/mobile-banner/constants.js
// The Play Store link is defined as an exported constant mainly for the sake of testing the Mobile App Banner.
// It is nearly impossible to fake navigation in JSDOM 16, so exposing this link for mocking allows us to
// avoid triggering a navigation.
const PLAY_STORE_LINK = 'https://play.google.com/store/apps/details?id=com.woocommerce.android';
const TRACKING_EVENT_NAME = 'wcadmin_mobile_android_banner_click';
// EXTERNAL MODULE: ./client/mobile-banner/banner.scss
var banner = __webpack_require__(317);

// CONCATENATED MODULE: ./client/mobile-banner/banner.js


/**
 * External dependencies
 */






/**
 * Internal dependencies
 */





const SHOW_APP_BANNER_MODIFIER_CLASS = 'woocommerce-layout__show-app-banner';
const Banner = _ref => {
  let {
    onInstall,
    onDismiss
  } = _ref;
  const [isActioned, setIsActioned] = Object(external_wp_element_["useState"])(false);
  const isVisible = platform() === ANDROID_PLATFORM && !isActioned;
  Object(external_wp_element_["useEffect"])(() => {
    const layout = document.getElementsByClassName('woocommerce-layout')[0];

    if (isVisible && layout) {
      // This is a hack to allow the mobile banner to work in the context of the header which is
      // position fixed. This can be refactored away when we move away from the activity panel
      // in future.
      layout.classList.add(SHOW_APP_BANNER_MODIFIER_CLASS);
    }

    return () => {
      if (layout) {
        layout.classList.remove(SHOW_APP_BANNER_MODIFIER_CLASS);
      }
    };
  }, [isVisible]);

  if (!isVisible) {
    return null;
  }

  return Object(external_wp_element_["createElement"])("div", {
    className: "woocommerce-mobile-app-banner"
  }, Object(external_wp_element_["createElement"])(build_module_icon["a" /* default */], {
    icon: Object(external_wp_element_["createElement"])(cross_small_default.a, {
      "data-testid": "dismiss-btn"
    }),
    onClick: () => {
      onDismiss();
      setIsActioned(true);
      Object(external_wc_tracks_["recordEvent"])(TRACKING_EVENT_NAME, {
        action: 'dismiss'
      });
    }
  }), Object(external_wp_element_["createElement"])(AppIcon, null), Object(external_wp_element_["createElement"])("div", {
    className: "woocommerce-mobile-app-banner__description"
  }, Object(external_wp_element_["createElement"])("p", {
    className: "woocommerce-mobile-app-banner__description__text"
  }, Object(external_wp_i18n_["__"])('Run your store from anywhere', 'woocommerce-admin')), Object(external_wp_element_["createElement"])("p", {
    className: "woocommerce-mobile-app-banner__description__text"
  }, Object(external_wp_i18n_["__"])('Download the WooCommerce app', 'woocommerce-admin'))), Object(external_wp_element_["createElement"])(external_wp_components_["Button"], {
    href: PLAY_STORE_LINK,
    isSecondary: true,
    onClick: () => {
      onInstall();
      setIsActioned(true);
      Object(external_wc_tracks_["recordEvent"])(TRACKING_EVENT_NAME, {
        action: 'install'
      });
    }
  }, Object(external_wp_i18n_["__"])('Install', 'woocommerce-admin')));
};
// CONCATENATED MODULE: ./client/mobile-banner/index.js


/**
 * External dependencies
 */


/**
 * Internal dependencies
 */



const MobileAppBanner = () => {
  const {
    updateUserPreferences,
    ...userData
  } = Object(external_wc_data_["useUserPreferences"])();
  const isDismissed = userData.android_app_banner_dismissed === 'yes';

  const onClick = () => {
    updateUserPreferences({
      android_app_banner_dismissed: 'yes'
    });
  };

  if (isDismissed) {
    return null;
  }

  return Object(external_wp_element_["createElement"])(header_utils["a" /* WooHeaderItem */], null, Object(external_wp_element_["createElement"])(Banner, {
    onDismiss: onClick,
    onInstall: onClick
  }));
};
Object(external_wp_plugins_["registerPlugin"])('mobile-banner-header-item', {
  render: MobileAppBanner,
  scope: 'woocommerce-admin'
});
// EXTERNAL MODULE: ./client/navigation/style.scss
var navigation_style = __webpack_require__(318);

// CONCATENATED MODULE: ./client/navigation/utils.ts
/**
 * External dependencies
 */


/**
 * Get the full URL if a relative path is passed.
 */
const getFullUrl = url => {
  if (url.indexOf('http') === 0) {
    return url;
  }

  return Object(external_wc_wcSettings_["getAdminLink"])(url);
};
/**
 * Get a default regex expression to match the path and provided params.
 */

const getDefaultMatchExpression = url => {
  const escapedUrl = url.replace(/[-\/\\^$*+?.()|[\]{}]/gi, '\\$&');
  const [path, args, hash] = escapedUrl.split(/\\\?|#/);
  const hashExpression = hash ? `(.*#${hash}$)` : '';
  const argsExpression = args ? args.split('&').reduce((acc, param) => {
    return `${acc}(?=.*[?|&]${param}(&|$|#))`;
  }, '') : '';
  return '^' + path + argsExpression + hashExpression;
};
/**
 * Get a match score for a menu item given a location.
 */

const getMatchScore = function (location, itemUrl) {
  let itemExpression = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : null;

  if (!itemUrl) {
    return 0;
  }

  const fullUrl = getFullUrl(itemUrl);
  const {
    href
  } = location; // Return highest possible score for exact match.

  if (fullUrl === href) {
    return Number.MAX_SAFE_INTEGER;
  }

  const defaultExpression = getDefaultMatchExpression(fullUrl);
  const regexp = new RegExp(itemExpression || defaultExpression, 'i');
  return (decodeURIComponent(href).match(regexp) || []).length;
};

/**
 * Get the closest matching item.
 *
 * @param {Array} items An array of items to match against.
 */
const getMatchingItem = items => {
  let matchedItem = null;
  let highestMatchScore = 0;
  items.forEach(item => {
    const score = getMatchScore(window.location, item.url, item.matchExpression);

    if (score > 0 && score >= highestMatchScore) {
      highestMatchScore = score;
      matchedItem = item;
    }
  });
  return matchedItem || null;
};
/**
 * Available menu IDs.
 */

const menuIds = ['primary', 'favorites', 'plugins', 'secondary'];

/**
 * Default categories for the menu.
 */
const defaultCategories = {
  woocommerce: {
    id: 'woocommerce',
    isCategory: true,
    menuId: 'primary',
    migrate: true,
    order: 10,
    parent: '',
    title: 'WooCommerce'
  }
};
/**
 * Sort an array of menu items by their order property.
 *
 * @param {Array} menuItems Array of menu items.
 * @return {Array} Sorted menu items.
 */

const sortMenuItems = menuItems => {
  return menuItems.sort((a, b) => {
    if (a.order === b.order) {
      return a.title.localeCompare(b.title);
    }

    return a.order - b.order;
  });
};
/**
 * Get a flat tree structure of all Categories and thier children grouped by menuId
 *
 * @param {Array} menuItems Array of menu items.
 * @param {Function} currentUserCan Callback method passed the capability to determine if a menu item is visible.
 * @return {Object} Mapped menu items and categories.
 */

const getMappedItemsCategories = (menuItems, currentUserCan) => {
  const categories = { ...defaultCategories
  };
  const items = sortMenuItems(menuItems).reduce((acc, item) => {
    // Set up the category if it doesn't yet exist.
    if (!acc[item.parent]) {
      acc[item.parent] = {};
      menuIds.forEach(menuId => {
        acc[item.parent][menuId] = [];
      });
    } // Incorrect menu ID.


    if (!acc[item.parent][item.menuId]) {
      return acc;
    } // User does not have permission to view this item.


    if (currentUserCan && item.capability && !currentUserCan(item.capability)) {
      return acc;
    } // Add categories.


    if (item.isCategory) {
      categories[item.id] = item;
    }

    const menuIdArray = acc[item.parent][item.menuId];

    if (menuIdArray) {
      menuIdArray.push(item);
    }

    return acc;
  }, {});
  return {
    items,
    categories
  };
};
// EXTERNAL MODULE: external ["wp","primitives"]
var external_wp_primitives_ = __webpack_require__(9);

// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/@wordpress+icons@6.3.0/node_modules/@wordpress/icons/build-module/library/wordpress.js


/**
 * WordPress dependencies
 */

const wordpress = Object(external_wp_element_["createElement"])(external_wp_primitives_["SVG"], {
  xmlns: "http://www.w3.org/2000/svg",
  viewBox: "-2 -2 24 24"
}, Object(external_wp_element_["createElement"])(external_wp_primitives_["Path"], {
  d: "M20 10c0-5.51-4.49-10-10-10C4.48 0 0 4.49 0 10c0 5.52 4.48 10 10 10 5.51 0 10-4.48 10-10zM7.78 15.37L4.37 6.22c.55-.02 1.17-.08 1.17-.08.5-.06.44-1.13-.06-1.11 0 0-1.45.11-2.37.11-.18 0-.37 0-.58-.01C4.12 2.69 6.87 1.11 10 1.11c2.33 0 4.45.87 6.05 2.34-.68-.11-1.65.39-1.65 1.58 0 .74.45 1.36.9 2.1.35.61.55 1.36.55 2.46 0 1.49-1.4 5-1.4 5l-3.03-8.37c.54-.02.82-.17.82-.17.5-.05.44-1.25-.06-1.22 0 0-1.44.12-2.38.12-.87 0-2.33-.12-2.33-.12-.5-.03-.56 1.2-.06 1.22l.92.08 1.26 3.41zM17.41 10c.24-.64.74-1.87.43-4.25.7 1.29 1.05 2.71 1.05 4.25 0 3.29-1.73 6.24-4.4 7.78.97-2.59 1.94-5.2 2.92-7.78zM6.1 18.09C3.12 16.65 1.11 13.53 1.11 10c0-1.3.23-2.48.72-3.59C3.25 10.3 4.67 14.2 6.1 18.09zm4.03-6.63l2.58 6.98c-.86.29-1.76.45-2.71.45-.79 0-1.57-.11-2.29-.33.81-2.38 1.62-4.74 2.42-7.1z"
}));
/* harmony default export */ var library_wordpress = (wordpress);
//# sourceMappingURL=wordpress.js.map
// CONCATENATED MODULE: ./client/navigation/components/header/index.js


/**
 * External dependencies
 */










/**
 * Internal dependencies
 */



const header_Header = () => {
  const siteTitle = Object(external_wc_wcSettings_["getSetting"])('siteTitle', '');
  const homeUrl = Object(external_wc_wcSettings_["getSetting"])('homeUrl', '');
  const isScrolled = useIsScrolled();
  const [isFolded, setIsFolded] = Object(external_wp_element_["useState"])(document.body.classList.contains(false));
  const navClasses = {
    folded: 'is-wc-nav-folded',
    expanded: 'is-wc-nav-expanded'
  };

  const foldNav = () => {
    document.body.classList.add(navClasses.folded);
    document.body.classList.remove(navClasses.expanded);
    setIsFolded(true);
  };

  const expandNav = () => {
    document.body.classList.remove(navClasses.folded);
    document.body.classList.add(navClasses.expanded);
    setIsFolded(false);
  };

  const toggleFolded = () => {
    if (document.body.classList.contains(navClasses.folded)) {
      expandNav();
    } else {
      foldNav();
    }
  };

  const foldOnMobile = function () {
    let screenWidth = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : document.body.clientWidth;

    if (screenWidth <= 960) {
      foldNav();
    } else {
      expandNav();
    }
  };

  Object(external_wp_element_["useEffect"])(() => {
    foldOnMobile();
    const foldEvents = [{
      eventName: 'orientationchange',
      handler: e => foldOnMobile(e.target.screen.availWidth)
    }, {
      eventName: 'resize',
      handler: Object(external_lodash_["debounce"])(() => foldOnMobile(), 200)
    }];

    for (const {
      eventName,
      handler
    } of foldEvents) {
      window.addEventListener(eventName, handler, false);
    }

    Object(external_wc_navigation_["addHistoryListener"])(() => foldOnMobile());
  }, []);
  let buttonIcon = Object(external_wp_element_["createElement"])(build_module_icon["a" /* default */], {
    size: "36px",
    icon: library_wordpress
  });
  const {
    isRequestingSiteIcon,
    siteIconUrl
  } = Object(external_wp_data_["useSelect"])(select => {
    const {
      isResolving
    } = select('core/data');
    const {
      getEntityRecord
    } = select('core');
    const siteData = getEntityRecord('root', '__unstableBase', undefined) || {};
    return {
      isRequestingSiteIcon: isResolving('core', 'getEntityRecord', ['root', '__unstableBase', undefined]),
      siteIconUrl: siteData.siteIconUrl
    };
  });

  if (siteIconUrl) {
    buttonIcon = Object(external_wp_element_["createElement"])("img", {
      alt: Object(external_wp_i18n_["__"])('Site Icon'),
      src: siteIconUrl
    });
  } else if (isRequestingSiteIcon) {
    buttonIcon = null;
  }

  const className = classnames_default()('woocommerce-navigation-header', {
    'is-scrolled': isScrolled
  });
  return Object(external_wp_element_["createElement"])("div", {
    className: className
  }, Object(external_wp_element_["createElement"])(external_wp_components_["Button"], {
    onClick: () => toggleFolded(),
    className: "woocommerce-navigation-header__site-icon",
    "aria-label": "Fold navigation",
    role: "switch",
    "aria-checked": isFolded ? 'true' : 'false'
  }, buttonIcon), Object(external_wp_element_["createElement"])(external_wp_components_["Button"], {
    title: siteTitle,
    href: homeUrl,
    className: "woocommerce-navigation-header__site-title",
    as: "span"
  }, Object(external_wp_htmlEntities_["decodeEntities"])(siteTitle)));
};

/* harmony default export */ var header = (header_Header);
// EXTERNAL MODULE: ./client/navigation/components/category-title/style.scss
var category_title_style = __webpack_require__(319);

// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/@wordpress+icons@6.3.0/node_modules/@wordpress/icons/build-module/library/star-filled.js


/**
 * WordPress dependencies
 */

const starFilled = Object(external_wp_element_["createElement"])(external_wp_primitives_["SVG"], {
  xmlns: "http://www.w3.org/2000/svg",
  viewBox: "0 0 24 24"
}, Object(external_wp_element_["createElement"])(external_wp_primitives_["Path"], {
  d: "M11.776 4.454a.25.25 0 01.448 0l2.069 4.192a.25.25 0 00.188.137l4.626.672a.25.25 0 01.139.426l-3.348 3.263a.25.25 0 00-.072.222l.79 4.607a.25.25 0 01-.362.263l-4.138-2.175a.25.25 0 00-.232 0l-4.138 2.175a.25.25 0 01-.363-.263l.79-4.607a.25.25 0 00-.071-.222L4.754 9.881a.25.25 0 01.139-.426l4.626-.672a.25.25 0 00.188-.137l2.069-4.192z"
}));
/* harmony default export */ var star_filled = (starFilled);
//# sourceMappingURL=star-filled.js.map
// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/@wordpress+icons@6.3.0/node_modules/@wordpress/icons/build-module/library/star-empty.js


/**
 * WordPress dependencies
 */

const starEmpty = Object(external_wp_element_["createElement"])(external_wp_primitives_["SVG"], {
  xmlns: "http://www.w3.org/2000/svg",
  viewBox: "0 0 24 24"
}, Object(external_wp_element_["createElement"])(external_wp_primitives_["Path"], {
  fillRule: "evenodd",
  d: "M9.706 8.646a.25.25 0 01-.188.137l-4.626.672a.25.25 0 00-.139.427l3.348 3.262a.25.25 0 01.072.222l-.79 4.607a.25.25 0 00.362.264l4.138-2.176a.25.25 0 01.233 0l4.137 2.175a.25.25 0 00.363-.263l-.79-4.607a.25.25 0 01.072-.222l3.347-3.262a.25.25 0 00-.139-.427l-4.626-.672a.25.25 0 01-.188-.137l-2.069-4.192a.25.25 0 00-.448 0L9.706 8.646zM12 7.39l-.948 1.921a1.75 1.75 0 01-1.317.957l-2.12.308 1.534 1.495c.412.402.6.982.503 1.55l-.362 2.11 1.896-.997a1.75 1.75 0 011.629 0l1.895.997-.362-2.11a1.75 1.75 0 01.504-1.55l1.533-1.495-2.12-.308a1.75 1.75 0 01-1.317-.957L12 7.39z",
  clipRule: "evenodd"
}));
/* harmony default export */ var star_empty = (starEmpty);
//# sourceMappingURL=star-empty.js.map
// EXTERNAL MODULE: ./client/navigation/components/favorite-button/style.scss
var favorite_button_style = __webpack_require__(320);

// CONCATENATED MODULE: ./client/navigation/components/favorite-button/index.js


/**
 * External dependencies
 */






/**
 * Internal dependencies
 */


const FavoriteButton = _ref => {
  let {
    id
  } = _ref;
  const {
    favorites,
    isResolving
  } = Object(external_wp_data_["useSelect"])(select => {
    return {
      favorites: select(external_wc_data_["NAVIGATION_STORE_NAME"]).getFavorites(),
      isResolving: select(external_wc_data_["NAVIGATION_STORE_NAME"]).isResolving('getFavorites')
    };
  });
  const {
    addFavorite,
    removeFavorite
  } = Object(external_wp_data_["useDispatch"])(external_wc_data_["NAVIGATION_STORE_NAME"]);
  const isFavorited = favorites.includes(id);

  const toggleFavorite = () => {
    const toggle = isFavorited ? removeFavorite : addFavorite;
    toggle(id);
    Object(external_wc_tracks_["recordEvent"])('navigation_favorite', {
      id,
      action: isFavorited ? 'unfavorite' : 'favorite'
    });
  };

  if (isResolving) {
    return null;
  }

  return Object(external_wp_element_["createElement"])(external_wp_components_["Button"], {
    id: "woocommerce-navigation-favorite-button",
    className: "woocommerce-navigation-favorite-button",
    isTertiary: true,
    onClick: toggleFavorite,
    "aria-label": isFavorited ? Object(external_wp_i18n_["__"])('Add this item to your favorites.', 'woocommerce-admin') : Object(external_wp_i18n_["__"])('Remove this item from your favorites.', 'woocommerce-admin')
  }, Object(external_wp_element_["createElement"])(build_module_icon["a" /* default */], {
    icon: isFavorited ? star_filled : star_empty,
    className: `star-${isFavorited ? 'filled' : 'empty'}-icon`
  }));
};
/* harmony default export */ var favorite_button = (FavoriteButton);
// CONCATENATED MODULE: ./client/navigation/components/favorites-tooltip/index.js


/**
 * External dependencies
 */



/**
 * Internal dependencies
 */


const tooltipHiddenOption = 'woocommerce_navigation_favorites_tooltip_hidden';
const FavoritesTooltip = () => {
  const {
    isFavoritesResolving,
    isOptionResolving,
    isTooltipHidden
  } = Object(external_wp_data_["useSelect"])(select => {
    const {
      getOption,
      isResolving
    } = select(external_wc_data_["OPTIONS_STORE_NAME"]);
    return {
      isFavoritesResolving: select(external_wc_data_["NAVIGATION_STORE_NAME"]).isResolving('getFavorites'),
      isOptionResolving: isResolving('getOption', [tooltipHiddenOption]),
      isTooltipHidden: getOption(tooltipHiddenOption) === 'yes'
    };
  });
  const {
    updateOptions
  } = Object(external_wp_data_["useDispatch"])(external_wc_data_["OPTIONS_STORE_NAME"]);

  if (isFavoritesResolving || isTooltipHidden || isOptionResolving) {
    return null;
  }

  if (document.body.classList.contains('is-wc-nav-folded')) {
    return null;
  }

  return Object(external_wp_element_["createElement"])(HighlightTooltip, {
    delay: 1000,
    title: Object(external_wp_i18n_["__"])('Introducing favorites', 'woocommerce-admin'),
    content: Object(external_wp_i18n_["__"])('You can now favorite your extensions to pin them in the top level of the navigation.', 'woocommerce-admin'),
    closeButtonText: Object(external_wp_i18n_["__"])('Got it', 'woocommerce-admin'),
    id: "woocommerce-navigation-favorite-button",
    onClose: () => updateOptions({
      [tooltipHiddenOption]: 'yes'
    }),
    useAnchor: true
  });
};
/* harmony default export */ var favorites_tooltip = (FavoritesTooltip);
// CONCATENATED MODULE: ./client/navigation/components/category-title/index.js


/**
 * Internal dependencies
 */



const CategoryTitle = _ref => {
  let {
    category
  } = _ref;
  const {
    id,
    menuId,
    title
  } = category;
  const className = 'woocommerce-navigation-category-title';

  if (['plugins', 'favorites'].includes(menuId)) {
    return Object(external_wp_element_["createElement"])("span", {
      className: className
    }, Object(external_wp_element_["createElement"])("span", {
      className: `${className}__text`
    }, title), Object(external_wp_element_["createElement"])(FavoriteButton, {
      id: id
    }), Object(external_wp_element_["createElement"])(FavoritesTooltip, null));
  }

  return Object(external_wp_element_["createElement"])("span", {
    className: className
  }, title);
};
/* harmony default export */ var category_title = (CategoryTitle);
// CONCATENATED MODULE: ./client/navigation/components/Item/index.js


/**
 * External dependencies
 */




const Item = _ref => {
  var _slot$fills;

  let {
    item
  } = _ref;
  const slot = Object(external_wc_experimental_["useSlot"])('woocommerce_navigation_' + item.id);
  const hasFills = Boolean(slot === null || slot === void 0 ? void 0 : (_slot$fills = slot.fills) === null || _slot$fills === void 0 ? void 0 : _slot$fills.length);

  const trackClick = id => {
    Object(external_wc_tracks_["recordEvent"])('navigation_click', {
      menu_item: id
    });
  }; // Disable reason: The div wrapping the slot item is used for tracking purposes
  // and should not be a tabbable element.

  /* eslint-disable jsx-a11y/no-static-element-interactions, jsx-a11y/click-events-have-key-events */
  // Only render a slot if a coresponding Fill exists and the item is not a category


  if (hasFills && !item.isCategory) {
    return Object(external_wp_element_["createElement"])(external_wc_experimental_["NavigationItem"], {
      key: item.id,
      item: item.id
    }, Object(external_wp_element_["createElement"])("div", {
      onClick: () => trackClick(item.id)
    }, Object(external_wp_element_["createElement"])(external_wc_navigation_["WooNavigationItem"].Slot, {
      name: item.id
    })));
  }

  return Object(external_wp_element_["createElement"])(external_wc_experimental_["NavigationItem"], {
    key: item.id,
    item: item.id,
    title: item.title,
    badge: item.badge ? item.badge : null,
    href: item.url,
    navigateToMenu: !item.url && item.id,
    onClick: () => trackClick(item.id),
    hideIfTargetMenuEmpty: true
  });
  /* eslint-enable jsx-a11y/no-static-element-interactions, jsx-a11y/click-events-have-key-events */
};

/* harmony default export */ var components_Item = (Item);
// CONCATENATED MODULE: ./client/navigation/components/container/primary-menu.js


/**
 * External dependencies
 */



/**
 * Internal dependencies
 */



const PrimaryMenu = _ref => {
  let {
    category,
    onBackClick,
    pluginItems,
    primaryItems
  } = _ref;

  if (!primaryItems.length && !pluginItems.length) {
    return null;
  }
  /**
   * Navigation's exit button WooCommerce label.
   *
   * @filter woocommerce_navigation_root_back_label
   * @param {string} label Back button label.
   */


  const rootBackLabel = Object(external_wp_hooks_["applyFilters"])('woocommerce_navigation_root_back_label', Object(external_wp_i18n_["__"])('WordPress Dashboard', 'woocommerce-admin'));
  /**
   * Navigation's exit button url.
   *
   * @filter woocommerce_navigation_root_back_url
   * @param {string} url Back button url.
   */

  const rootBackUrl = Object(external_wp_hooks_["applyFilters"])('woocommerce_navigation_root_back_url', window.wcNavigation.rootBackUrl);
  const isRootBackVisible = category.id === 'woocommerce' && rootBackUrl;
  return Object(external_wp_element_["createElement"])(external_wc_experimental_["NavigationMenu"], {
    title: Object(external_wp_element_["createElement"])(category_title, {
      category: category
    }),
    menu: category.id,
    parentMenu: category.parent,
    backButtonLabel: isRootBackVisible ? rootBackLabel : category.backButtonLabel || null,
    onBackButtonClick: isRootBackVisible ? () => {
      onBackClick('woocommerce');
      window.location = rootBackUrl;
    } : () => onBackClick(category.id)
  }, !!primaryItems.length && Object(external_wp_element_["createElement"])(external_wc_experimental_["NavigationGroup"], null, primaryItems.map(item => Object(external_wp_element_["createElement"])(components_Item, {
    key: item.id,
    item: item
  }))), !!pluginItems.length && Object(external_wp_element_["createElement"])(external_wc_experimental_["NavigationGroup"], {
    title: category.id === 'woocommerce' ? Object(external_wp_i18n_["__"])('Extensions', 'woocommerce-admin') : null
  }, pluginItems.map(item => Object(external_wp_element_["createElement"])(components_Item, {
    key: item.id,
    item: item
  }))));
};
// CONCATENATED MODULE: ./client/navigation/components/container/secondary-menu.js


/**
 * External dependencies
 */

/**
 * Internal dependencies
 */



const SecondaryMenu = _ref => {
  let {
    category,
    items,
    onBackClick
  } = _ref;

  if (!items.length) {
    return null;
  }

  const isRoot = category.id === 'woocommerce';
  return Object(external_wp_element_["createElement"])(external_wc_experimental_["NavigationMenu"], {
    className: "components-navigation__menu-secondary",
    title: !isRoot && Object(external_wp_element_["createElement"])(category_title, {
      category: category
    }),
    menu: category.id,
    parentMenu: category.parent,
    backButtonLabel: category.backButtonLabel || null,
    onBackButtonClick: isRoot ? null : () => onBackClick(category.id)
  }, Object(external_wp_element_["createElement"])(external_wc_experimental_["NavigationGroup"], {
    onBackButtonClick: () => onBackClick(category.id)
  }, items.map(item => Object(external_wp_element_["createElement"])(components_Item, {
    key: item.id,
    item: item
  }))));
};
// CONCATENATED MODULE: ./client/navigation/components/container/index.js


/**
 * External dependencies
 */







/**
 * Internal dependencies
 */






const Container = () => {
  const {
    menuItems
  } = Object(external_wp_data_["useSelect"])(select => {
    return {
      menuItems: select(external_wc_data_["NAVIGATION_STORE_NAME"]).getMenuItems()
    };
  });
  Object(external_wp_element_["useEffect"])(() => {
    // Collapse the original WP Menu.
    document.documentElement.classList.remove('wp-toolbar');
    document.body.classList.add('has-woocommerce-navigation');
    const adminMenu = document.getElementById('adminmenumain');

    if (!adminMenu) {
      return;
    }

    adminMenu.classList.add('folded');
  }, []);
  const [activeItem, setActiveItem] = Object(external_wp_element_["useState"])('woocommerce-home');
  const [activeLevel, setActiveLevel] = Object(external_wp_element_["useState"])('woocommerce');
  Object(external_wp_element_["useEffect"])(() => {
    const initialMatchedItem = getMatchingItem(menuItems);

    if (initialMatchedItem && activeItem !== initialMatchedItem) {
      setActiveItem(initialMatchedItem);
      setActiveLevel(initialMatchedItem.parent);
    }

    const removeListener = Object(external_wc_navigation_["addHistoryListener"])(() => {
      setTimeout(() => {
        const matchedItem = getMatchingItem(menuItems);

        if (matchedItem) {
          setActiveItem(matchedItem);
          setActiveLevel(matchedItem.parent);
        }
      }, 0);
    });
    return removeListener;
  }, [menuItems]);
  const {
    currentUserCan
  } = Object(external_wc_data_["useUser"])();
  const {
    categories,
    items
  } = Object(external_wp_element_["useMemo"])(() => getMappedItemsCategories(menuItems, currentUserCan), [menuItems, currentUserCan]);
  const navDomRef = Object(external_wp_element_["useRef"])(null);

  const onBackClick = id => {
    Object(external_wc_tracks_["recordEvent"])('navigation_back_click', {
      category: id
    });
  };

  const isRoot = activeLevel === 'woocommerce';
  const classes = classnames_default()('woocommerce-navigation', {
    'is-root': isRoot
  });
  return Object(external_wp_element_["createElement"])("div", {
    className: classes
  }, Object(external_wp_element_["createElement"])(header, null), Object(external_wp_element_["createElement"])("div", {
    className: "woocommerce-navigation__wrapper",
    ref: navDomRef
  }, Object(external_wp_element_["createElement"])(external_wc_experimental_["Navigation"], {
    activeItem: activeItem ? activeItem.id : null,
    activeMenu: activeLevel,
    onActivateMenu: function () {
      if (navDomRef && navDomRef.current) {
        navDomRef.current.scrollTop = 0;
      }

      setActiveLevel(...arguments);
    }
  }, Object.values(categories).map(category => {
    const categoryItems = items[category.id];
    return !!categoryItems && [Object(external_wp_element_["createElement"])(PrimaryMenu, {
      key: category.id,
      category: category,
      onBackClick: onBackClick,
      primaryItems: [...categoryItems.primary, ...categoryItems.favorites],
      pluginItems: categoryItems.plugins
    }), Object(external_wp_element_["createElement"])(SecondaryMenu, {
      key: `secondary/${category.id}`,
      category: category,
      onBackClick: onBackClick,
      items: categoryItems.secondary
    })];
  }))));
};

/* harmony default export */ var container = (Container);
// CONCATENATED MODULE: ./client/navigation/index.js
/**
 * External dependencies
 */

/**
 * Internal dependencies
 */



const HydratedNavigation = Object(external_wc_data_["withNavigationHydration"])(window.wcNavigation)(container);
/* harmony default export */ var navigation = (HydratedNavigation);
// CONCATENATED MODULE: ./client/layout/navigation.js


/**
 * External dependencies
 */






/**
 * Internal dependencies
 */







const NavigationPlugin = () => {
  const {
    persistedQuery
  } = Object(external_wp_data_["useSelect"])(select => {
    return {
      persistedQuery: select(external_wc_data_["NAVIGATION_STORE_NAME"]).getPersistedQuery()
    };
  });

  if (!window.wcAdminFeatures.navigation) {
    return null;
  }
  /**
   * If the current page is embedded, stay with the default urls
   * provided by Navigation because the router isn't present to
   * respond to <Link /> component's manipulation of the url.
   */


  if (!Object(utils["f" /* isWCAdmin */])(window.location.href)) {
    return Object(external_wp_element_["createElement"])(header_utils["b" /* WooHeaderNavigationItem */], {
      order: -100
    }, Object(external_wp_element_["createElement"])(navigation, null));
  }

  const reports = Object(get_reports["a" /* default */])().filter(item => item.navArgs);
  const pages = getPages().filter(page => page.navArgs).map(page => {
    if (page.path === '/analytics/settings') {
      return { ...page,
        breadcrumbs: [Object(external_wp_i18n_["__"])('Analytics', 'woocommerce-admin')]
      };
    }

    return page;
  });
  return Object(external_wp_element_["createElement"])(external_wp_element_["Fragment"], null, Object(external_wp_element_["createElement"])(header_utils["b" /* WooHeaderNavigationItem */], {
    order: -100
  }, Object(external_wp_element_["createElement"])(navigation, null)), pages.map(page => Object(external_wp_element_["createElement"])(external_wc_navigation_["WooNavigationItem"], {
    item: page.navArgs.id,
    key: page.navArgs.id
  }, Object(external_wp_element_["createElement"])(external_wc_components_["Link"], {
    className: "components-button",
    href: Object(external_wc_navigation_["getNewPath"])(Object(external_wc_navigation_["pathIsExcluded"])(page.path) ? {} : persistedQuery, page.path, {}),
    type: "wc-admin"
  }, page.breadcrumbs[page.breadcrumbs.length - 1]))), reports.map(item => Object(external_wp_element_["createElement"])(external_wc_navigation_["WooNavigationItem"], {
    item: item.navArgs.id,
    key: item.navArgs.id
  }, Object(external_wp_element_["createElement"])(external_wc_components_["Link"], {
    className: "components-button",
    href: Object(external_wc_navigation_["getNewPath"])(Object(external_wc_navigation_["pathIsExcluded"])(item.report) ? {} : persistedQuery, `/analytics/${item.report}`, {}),
    type: "wc-admin"
  }, item.title))));
};

Object(external_wp_plugins_["registerPlugin"])('wc-admin-navigation', {
  render: NavigationPlugin,
  scope: 'woocommerce-navigation'
});
// CONCATENATED MODULE: ./client/layout/index.js



/**
 * External dependencies
 */













/**
 * Internal dependencies
 */










const StoreAlerts = Object(external_wp_element_["lazy"])(() => Promise.all(/* import() | store-alerts */[__webpack_require__.e(1), __webpack_require__.e(50)]).then(__webpack_require__.bind(null, 669)));
const WCPayUsageModal = Object(external_wp_element_["lazy"])(() => __webpack_require__.e(/* import() | wcpay-usage-modal */ 56).then(__webpack_require__.bind(null, 555)));
class layout_PrimaryLayout extends external_wp_element_["Component"] {
  render() {
    const {
      children
    } = this.props;
    return Object(external_wp_element_["createElement"])("div", {
      className: "woocommerce-layout__primary",
      id: "woocommerce-layout__primary"
    }, window.wcAdminFeatures['store-alerts'] && Object(external_wp_element_["createElement"])(external_wp_element_["Suspense"], {
      fallback: null
    }, Object(external_wp_element_["createElement"])(StoreAlerts, null)), Object(external_wp_element_["createElement"])(layout_notices, null), children);
  }

}

class layout_Layout extends external_wp_element_["Component"] {
  componentDidMount() {
    this.recordPageViewTrack();
  }

  componentDidUpdate(prevProps) {
    const previousPath = Object(external_lodash_["get"])(prevProps, 'location.pathname');
    const currentPath = Object(external_lodash_["get"])(this.props, 'location.pathname');

    if (!previousPath || !currentPath) {
      return;
    }

    if (previousPath !== currentPath) {
      this.recordPageViewTrack();
    }
  }

  recordPageViewTrack() {
    const {
      activePlugins,
      installedPlugins,
      isEmbedded,
      isJetpackConnected
    } = this.props;
    const navigationFlag = {
      has_navigation: !!window.wcNavigation
    };

    if (isEmbedded) {
      const path = document.location.pathname + document.location.search;
      Object(external_wc_tracks_["recordPageView"])(path, {
        is_embedded: true,
        ...navigationFlag
      });
      return;
    }

    const pathname = Object(external_lodash_["get"])(this.props, 'location.pathname');

    if (!pathname) {
      return;
    } // Remove leading slash, and camel case remaining pathname


    let path = pathname.substring(1).replace(/\//g, '_'); // When pathname is `/` we are on the home screen.

    if (path.length === 0) {
      path = 'home_screen';
    }

    Object(external_wc_tracks_["recordPageView"])(path, {
      jetpack_installed: installedPlugins.includes('jetpack'),
      jetpack_active: activePlugins.includes('jetpack'),
      jetpack_connected: isJetpackConnected,
      ...navigationFlag
    });
  }

  getQuery(searchString) {
    if (!searchString) {
      return {};
    }

    const search = searchString.substring(1);
    return Object(lib["parse"])(search);
  }

  isWCPaySettingsPage() {
    const {
      page,
      section,
      tab
    } = Object(external_wc_navigation_["getQuery"])();
    return page === 'wc-settings' && tab === 'checkout' && section === 'woocommerce_payments';
  }

  render() {
    const {
      isEmbedded,
      ...restProps
    } = this.props;
    const {
      location,
      page
    } = this.props;
    const {
      breadcrumbs
    } = page;
    const query = this.getQuery(location && location.search);
    return Object(external_wp_element_["createElement"])(external_wp_components_["SlotFillProvider"], null, Object(external_wp_element_["createElement"])("div", {
      className: "woocommerce-layout"
    }, Object(external_wp_element_["createElement"])(Header, {
      sections: Object(external_lodash_["isFunction"])(breadcrumbs) ? breadcrumbs(this.props) : breadcrumbs,
      isEmbedded: isEmbedded,
      query: query
    }), Object(external_wp_element_["createElement"])(transient_notices, null), !isEmbedded && Object(external_wp_element_["createElement"])(layout_PrimaryLayout, null, Object(external_wp_element_["createElement"])("div", {
      className: "woocommerce-layout__main"
    }, Object(external_wp_element_["createElement"])(controller_Controller, extends_default()({}, restProps, {
      query: query
    })))), isEmbedded && this.isWCPaySettingsPage() && Object(external_wp_element_["createElement"])(external_wp_element_["Suspense"], {
      fallback: null
    }, Object(external_wp_element_["createElement"])(WCPayUsageModal, null))), Object(external_wp_element_["createElement"])(external_wp_plugins_["PluginArea"], {
      scope: "woocommerce-admin"
    }), window.wcAdminFeatures.navigation && Object(external_wp_element_["createElement"])(external_wp_plugins_["PluginArea"], {
      scope: "woocommerce-navigation"
    }), Object(external_wp_element_["createElement"])(external_wp_plugins_["PluginArea"], {
      scope: "woocommerce-tasks"
    }));
  }

}

layout_Layout.propTypes = {
  isEmbedded: prop_types_default.a.bool,
  page: prop_types_default.a.shape({
    container: prop_types_default.a.oneOfType([prop_types_default.a.func, prop_types_default.a.object // Support React.lazy
    ]),
    path: prop_types_default.a.string,
    breadcrumbs: prop_types_default.a.oneOfType([prop_types_default.a.func, prop_types_default.a.arrayOf(prop_types_default.a.oneOfType([prop_types_default.a.arrayOf(prop_types_default.a.string), prop_types_default.a.string]))]).isRequired,
    wpOpenMenu: prop_types_default.a.string
  }).isRequired
};
const dataEndpoints = Object(admin_settings["d" /* getAdminSetting */])('dataEndpoints');
const Layout = Object(external_wp_compose_["compose"])(Object(external_wc_data_["withPluginsHydration"])({ ...Object(admin_settings["d" /* getAdminSetting */])('plugins', {}),
  jetpackStatus: dataEndpoints && dataEndpoints.jetpackStatus || false
}), Object(external_wp_data_["withSelect"])((select, _ref) => {
  let {
    isEmbedded
  } = _ref;

  // Embedded pages don't send plugin info to Tracks.
  if (isEmbedded) {
    return;
  }

  const {
    getActivePlugins,
    getInstalledPlugins,
    isJetpackConnected
  } = select(external_wc_data_["PLUGINS_STORE_NAME"]);
  return {
    activePlugins: getActivePlugins(),
    isJetpackConnected: isJetpackConnected(),
    installedPlugins: getInstalledPlugins()
  };
}))(layout_Layout);

const _PageLayout = () => {
  const {
    currentUserCan
  } = Object(external_wc_data_["useUser"])();
  return Object(external_wp_element_["createElement"])(react_router_Router, {
    history: Object(external_wc_navigation_["getHistory"])()
  }, Object(external_wp_element_["createElement"])(react_router_Switch, null, getPages().filter(page => !page.capability || currentUserCan(page.capability)).map(page => {
    return Object(external_wp_element_["createElement"])(react_router_Route, {
      key: page.path,
      path: page.path,
      exact: true,
      render: props => Object(external_wp_element_["createElement"])(Layout, extends_default()({
        page: page
      }, props))
    });
  })));
};

const PageLayout = Object(external_wp_compose_["compose"])(window.wcSettings.admin ? Object(external_wc_data_["withOptionsHydration"])({ ...Object(admin_settings["d" /* getAdminSetting */])('preloadOptions', {})
}) : external_lodash_["identity"])(_PageLayout);

const _EmbedLayout = () => Object(external_wp_element_["createElement"])(Layout, {
  page: {
    breadcrumbs: Object(admin_settings["d" /* getAdminSetting */])('embedBreadcrumbs', [])
  },
  isEmbedded: true
});

const EmbedLayout = Object(external_wp_compose_["compose"])(Object(admin_settings["d" /* getAdminSetting */])('preloadOptions') ? Object(external_wc_data_["withOptionsHydration"])({ ...Object(admin_settings["d" /* getAdminSetting */])('preloadOptions')
}) : external_lodash_["identity"])(_EmbedLayout);

/***/ }),

/***/ 136:
/***/ (function(module, exports) {

(function() { module.exports = window["wc"]["number"]; }());

/***/ }),

/***/ 137:
/***/ (function(module, exports) {

(function() { module.exports = window["wc"]["explat"]; }());

/***/ }),

/***/ 14:
/***/ (function(module, exports) {

(function() { module.exports = window["wp"]["compose"]; }());

/***/ }),

/***/ 144:
/***/ (function(module, exports) {

(function() { module.exports = window["wp"]["notices"]; }());

/***/ }),

/***/ 145:
/***/ (function(module, exports) {

// shim for using process in browser
var process = module.exports = {};

// cached from whatever global is present so that test runners that stub it
// don't break things.  But we need to wrap it in a try catch in case it is
// wrapped in strict mode code which doesn't define any globals.  It's inside a
// function because try/catches deoptimize in certain engines.

var cachedSetTimeout;
var cachedClearTimeout;

function defaultSetTimout() {
    throw new Error('setTimeout has not been defined');
}
function defaultClearTimeout () {
    throw new Error('clearTimeout has not been defined');
}
(function () {
    try {
        if (typeof setTimeout === 'function') {
            cachedSetTimeout = setTimeout;
        } else {
            cachedSetTimeout = defaultSetTimout;
        }
    } catch (e) {
        cachedSetTimeout = defaultSetTimout;
    }
    try {
        if (typeof clearTimeout === 'function') {
            cachedClearTimeout = clearTimeout;
        } else {
            cachedClearTimeout = defaultClearTimeout;
        }
    } catch (e) {
        cachedClearTimeout = defaultClearTimeout;
    }
} ())
function runTimeout(fun) {
    if (cachedSetTimeout === setTimeout) {
        //normal enviroments in sane situations
        return setTimeout(fun, 0);
    }
    // if setTimeout wasn't available but was latter defined
    if ((cachedSetTimeout === defaultSetTimout || !cachedSetTimeout) && setTimeout) {
        cachedSetTimeout = setTimeout;
        return setTimeout(fun, 0);
    }
    try {
        // when when somebody has screwed with setTimeout but no I.E. maddness
        return cachedSetTimeout(fun, 0);
    } catch(e){
        try {
            // When we are in I.E. but the script has been evaled so I.E. doesn't trust the global object when called normally
            return cachedSetTimeout.call(null, fun, 0);
        } catch(e){
            // same as above but when it's a version of I.E. that must have the global object for 'this', hopfully our context correct otherwise it will throw a global error
            return cachedSetTimeout.call(this, fun, 0);
        }
    }


}
function runClearTimeout(marker) {
    if (cachedClearTimeout === clearTimeout) {
        //normal enviroments in sane situations
        return clearTimeout(marker);
    }
    // if clearTimeout wasn't available but was latter defined
    if ((cachedClearTimeout === defaultClearTimeout || !cachedClearTimeout) && clearTimeout) {
        cachedClearTimeout = clearTimeout;
        return clearTimeout(marker);
    }
    try {
        // when when somebody has screwed with setTimeout but no I.E. maddness
        return cachedClearTimeout(marker);
    } catch (e){
        try {
            // When we are in I.E. but the script has been evaled so I.E. doesn't  trust the global object when called normally
            return cachedClearTimeout.call(null, marker);
        } catch (e){
            // same as above but when it's a version of I.E. that must have the global object for 'this', hopfully our context correct otherwise it will throw a global error.
            // Some versions of I.E. have different rules for clearTimeout vs setTimeout
            return cachedClearTimeout.call(this, marker);
        }
    }



}
var queue = [];
var draining = false;
var currentQueue;
var queueIndex = -1;

function cleanUpNextTick() {
    if (!draining || !currentQueue) {
        return;
    }
    draining = false;
    if (currentQueue.length) {
        queue = currentQueue.concat(queue);
    } else {
        queueIndex = -1;
    }
    if (queue.length) {
        drainQueue();
    }
}

function drainQueue() {
    if (draining) {
        return;
    }
    var timeout = runTimeout(cleanUpNextTick);
    draining = true;

    var len = queue.length;
    while(len) {
        currentQueue = queue;
        queue = [];
        while (++queueIndex < len) {
            if (currentQueue) {
                currentQueue[queueIndex].run();
            }
        }
        queueIndex = -1;
        len = queue.length;
    }
    currentQueue = null;
    draining = false;
    runClearTimeout(timeout);
}

process.nextTick = function (fun) {
    var args = new Array(arguments.length - 1);
    if (arguments.length > 1) {
        for (var i = 1; i < arguments.length; i++) {
            args[i - 1] = arguments[i];
        }
    }
    queue.push(new Item(fun, args));
    if (queue.length === 1 && !draining) {
        runTimeout(drainQueue);
    }
};

// v8 likes predictible objects
function Item(fun, array) {
    this.fun = fun;
    this.array = array;
}
Item.prototype.run = function () {
    this.fun.apply(null, this.array);
};
process.title = 'browser';
process.browser = true;
process.env = {};
process.argv = [];
process.version = ''; // empty string to avoid regexp issues
process.versions = {};

function noop() {}

process.on = noop;
process.addListener = noop;
process.once = noop;
process.off = noop;
process.removeListener = noop;
process.removeAllListeners = noop;
process.emit = noop;
process.prependListener = noop;
process.prependOnceListener = noop;

process.listeners = function (name) { return [] }

process.binding = function (name) {
    throw new Error('process.binding is not supported');
};

process.cwd = function () { return '/' };
process.chdir = function (dir) {
    throw new Error('process.chdir is not supported');
};
process.umask = function() { return 0; };


/***/ }),

/***/ 146:
/***/ (function(module, exports, __webpack_require__) {

"use strict";
/** @license React v16.13.1
 * react-is.production.min.js
 *
 * Copyright (c) Facebook, Inc. and its affiliates.
 *
 * This source code is licensed under the MIT license found in the
 * LICENSE file in the root directory of this source tree.
 */

var b="function"===typeof Symbol&&Symbol.for,c=b?Symbol.for("react.element"):60103,d=b?Symbol.for("react.portal"):60106,e=b?Symbol.for("react.fragment"):60107,f=b?Symbol.for("react.strict_mode"):60108,g=b?Symbol.for("react.profiler"):60114,h=b?Symbol.for("react.provider"):60109,k=b?Symbol.for("react.context"):60110,l=b?Symbol.for("react.async_mode"):60111,m=b?Symbol.for("react.concurrent_mode"):60111,n=b?Symbol.for("react.forward_ref"):60112,p=b?Symbol.for("react.suspense"):60113,q=b?
Symbol.for("react.suspense_list"):60120,r=b?Symbol.for("react.memo"):60115,t=b?Symbol.for("react.lazy"):60116,v=b?Symbol.for("react.block"):60121,w=b?Symbol.for("react.fundamental"):60117,x=b?Symbol.for("react.responder"):60118,y=b?Symbol.for("react.scope"):60119;
function z(a){if("object"===typeof a&&null!==a){var u=a.$$typeof;switch(u){case c:switch(a=a.type,a){case l:case m:case e:case g:case f:case p:return a;default:switch(a=a&&a.$$typeof,a){case k:case n:case t:case r:case h:return a;default:return u}}case d:return u}}}function A(a){return z(a)===m}exports.AsyncMode=l;exports.ConcurrentMode=m;exports.ContextConsumer=k;exports.ContextProvider=h;exports.Element=c;exports.ForwardRef=n;exports.Fragment=e;exports.Lazy=t;exports.Memo=r;exports.Portal=d;
exports.Profiler=g;exports.StrictMode=f;exports.Suspense=p;exports.isAsyncMode=function(a){return A(a)||z(a)===l};exports.isConcurrentMode=A;exports.isContextConsumer=function(a){return z(a)===k};exports.isContextProvider=function(a){return z(a)===h};exports.isElement=function(a){return"object"===typeof a&&null!==a&&a.$$typeof===c};exports.isForwardRef=function(a){return z(a)===n};exports.isFragment=function(a){return z(a)===e};exports.isLazy=function(a){return z(a)===t};
exports.isMemo=function(a){return z(a)===r};exports.isPortal=function(a){return z(a)===d};exports.isProfiler=function(a){return z(a)===g};exports.isStrictMode=function(a){return z(a)===f};exports.isSuspense=function(a){return z(a)===p};
exports.isValidElementType=function(a){return"string"===typeof a||"function"===typeof a||a===e||a===m||a===g||a===f||a===p||a===q||"object"===typeof a&&null!==a&&(a.$$typeof===t||a.$$typeof===r||a.$$typeof===h||a.$$typeof===k||a.$$typeof===n||a.$$typeof===w||a.$$typeof===x||a.$$typeof===y||a.$$typeof===v)};exports.typeOf=z;


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

/***/ 175:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* unused harmony export a */
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "animated", function() { return animated; });
/* harmony import */ var _react_spring_core__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(133);
/* harmony reexport (checked) */ if(__webpack_require__.o(_react_spring_core__WEBPACK_IMPORTED_MODULE_0__, "useTransition")) __webpack_require__.d(__webpack_exports__, "useTransition", function() { return _react_spring_core__WEBPACK_IMPORTED_MODULE_0__["useTransition"]; });

/* harmony import */ var react_dom__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(29);
/* harmony import */ var react_dom__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(react_dom__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _react_spring_shared__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(3);
/* harmony import */ var _react_spring_animated__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(32);






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

const _excluded$2 = ["style", "children", "scrollTop", "scrollLeft"];
const isCustomPropRE = /^--/;

function dangerousStyleValue(name, value) {
  if (value == null || typeof value === 'boolean' || value === '') return '';
  if (typeof value === 'number' && value !== 0 && !isCustomPropRE.test(name) && !(isUnitlessNumber.hasOwnProperty(name) && isUnitlessNumber[name])) return value + 'px';
  return ('' + value).trim();
}

const attributeCache = {};
function applyAnimatedValues(instance, props) {
  if (!instance.nodeType || !instance.setAttribute) {
    return false;
  }

  const isFilterElement = instance.nodeName === 'filter' || instance.parentNode && instance.parentNode.nodeName === 'filter';

  const _ref = props,
        {
    style,
    children,
    scrollTop,
    scrollLeft
  } = _ref,
        attributes = _objectWithoutPropertiesLoose(_ref, _excluded$2);

  const values = Object.values(attributes);
  const names = Object.keys(attributes).map(name => isFilterElement || instance.hasAttribute(name) ? name : attributeCache[name] || (attributeCache[name] = name.replace(/([A-Z])/g, n => '-' + n.toLowerCase())));

  if (children !== void 0) {
    instance.textContent = children;
  }

  for (let name in style) {
    if (style.hasOwnProperty(name)) {
      const value = dangerousStyleValue(name, style[name]);

      if (isCustomPropRE.test(name)) {
        instance.style.setProperty(name, value);
      } else {
        instance.style[name] = value;
      }
    }
  }

  names.forEach((name, i) => {
    instance.setAttribute(name, values[i]);
  });

  if (scrollTop !== void 0) {
    instance.scrollTop = scrollTop;
  }

  if (scrollLeft !== void 0) {
    instance.scrollLeft = scrollLeft;
  }
}
let isUnitlessNumber = {
  animationIterationCount: true,
  borderImageOutset: true,
  borderImageSlice: true,
  borderImageWidth: true,
  boxFlex: true,
  boxFlexGroup: true,
  boxOrdinalGroup: true,
  columnCount: true,
  columns: true,
  flex: true,
  flexGrow: true,
  flexPositive: true,
  flexShrink: true,
  flexNegative: true,
  flexOrder: true,
  gridRow: true,
  gridRowEnd: true,
  gridRowSpan: true,
  gridRowStart: true,
  gridColumn: true,
  gridColumnEnd: true,
  gridColumnSpan: true,
  gridColumnStart: true,
  fontWeight: true,
  lineClamp: true,
  lineHeight: true,
  opacity: true,
  order: true,
  orphans: true,
  tabSize: true,
  widows: true,
  zIndex: true,
  zoom: true,
  fillOpacity: true,
  floodOpacity: true,
  stopOpacity: true,
  strokeDasharray: true,
  strokeDashoffset: true,
  strokeMiterlimit: true,
  strokeOpacity: true,
  strokeWidth: true
};

const prefixKey = (prefix, key) => prefix + key.charAt(0).toUpperCase() + key.substring(1);

const prefixes = ['Webkit', 'Ms', 'Moz', 'O'];
isUnitlessNumber = Object.keys(isUnitlessNumber).reduce((acc, prop) => {
  prefixes.forEach(prefix => acc[prefixKey(prefix, prop)] = acc[prop]);
  return acc;
}, isUnitlessNumber);

const _excluded$1 = ["x", "y", "z"];
const domTransforms = /^(matrix|translate|scale|rotate|skew)/;
const pxTransforms = /^(translate)/;
const degTransforms = /^(rotate|skew)/;

const addUnit = (value, unit) => _react_spring_shared__WEBPACK_IMPORTED_MODULE_2__[/* is */ "s"].num(value) && value !== 0 ? value + unit : value;

const isValueIdentity = (value, id) => _react_spring_shared__WEBPACK_IMPORTED_MODULE_2__[/* is */ "s"].arr(value) ? value.every(v => isValueIdentity(v, id)) : _react_spring_shared__WEBPACK_IMPORTED_MODULE_2__[/* is */ "s"].num(value) ? value === id : parseFloat(value) === id;

class AnimatedStyle extends _react_spring_animated__WEBPACK_IMPORTED_MODULE_3__[/* AnimatedObject */ "a"] {
  constructor(_ref) {
    let {
      x,
      y,
      z
    } = _ref,
        style = _objectWithoutPropertiesLoose(_ref, _excluded$1);

    const inputs = [];
    const transforms = [];

    if (x || y || z) {
      inputs.push([x || 0, y || 0, z || 0]);
      transforms.push(xyz => [`translate3d(${xyz.map(v => addUnit(v, 'px')).join(',')})`, isValueIdentity(xyz, 0)]);
    }

    Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_2__[/* eachProp */ "l"])(style, (value, key) => {
      if (key === 'transform') {
        inputs.push([value || '']);
        transforms.push(transform => [transform, transform === '']);
      } else if (domTransforms.test(key)) {
        delete style[key];
        if (_react_spring_shared__WEBPACK_IMPORTED_MODULE_2__[/* is */ "s"].und(value)) return;
        const unit = pxTransforms.test(key) ? 'px' : degTransforms.test(key) ? 'deg' : '';
        inputs.push(Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_2__[/* toArray */ "y"])(value));
        transforms.push(key === 'rotate3d' ? ([x, y, z, deg]) => [`rotate3d(${x},${y},${z},${addUnit(deg, unit)})`, isValueIdentity(deg, 0)] : input => [`${key}(${input.map(v => addUnit(v, unit)).join(',')})`, isValueIdentity(input, key.startsWith('scale') ? 1 : 0)]);
      }
    });

    if (inputs.length) {
      style.transform = new FluidTransform(inputs, transforms);
    }

    super(style);
  }

}

class FluidTransform extends _react_spring_shared__WEBPACK_IMPORTED_MODULE_2__[/* FluidValue */ "a"] {
  constructor(inputs, transforms) {
    super();
    this._value = null;
    this.inputs = inputs;
    this.transforms = transforms;
  }

  get() {
    return this._value || (this._value = this._get());
  }

  _get() {
    let transform = '';
    let identity = true;
    Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_2__[/* each */ "k"])(this.inputs, (input, i) => {
      const arg1 = Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_2__[/* getFluidValue */ "q"])(input[0]);
      const [t, id] = this.transforms[i](_react_spring_shared__WEBPACK_IMPORTED_MODULE_2__[/* is */ "s"].arr(arg1) ? arg1 : input.map(_react_spring_shared__WEBPACK_IMPORTED_MODULE_2__[/* getFluidValue */ "q"]));
      transform += ' ' + t;
      identity = identity && id;
    });
    return identity ? 'none' : transform;
  }

  observerAdded(count) {
    if (count == 1) Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_2__[/* each */ "k"])(this.inputs, input => Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_2__[/* each */ "k"])(input, value => Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_2__[/* hasFluidValue */ "r"])(value) && Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_2__[/* addFluidObserver */ "c"])(value, this)));
  }

  observerRemoved(count) {
    if (count == 0) Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_2__[/* each */ "k"])(this.inputs, input => Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_2__[/* each */ "k"])(input, value => Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_2__[/* hasFluidValue */ "r"])(value) && Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_2__[/* removeFluidObserver */ "x"])(value, this)));
  }

  eventObserved(event) {
    if (event.type == 'change') {
      this._value = null;
    }

    Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_2__[/* callFluidObservers */ "d"])(this, event);
  }

}

const primitives = ['a', 'abbr', 'address', 'area', 'article', 'aside', 'audio', 'b', 'base', 'bdi', 'bdo', 'big', 'blockquote', 'body', 'br', 'button', 'canvas', 'caption', 'cite', 'code', 'col', 'colgroup', 'data', 'datalist', 'dd', 'del', 'details', 'dfn', 'dialog', 'div', 'dl', 'dt', 'em', 'embed', 'fieldset', 'figcaption', 'figure', 'footer', 'form', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'head', 'header', 'hgroup', 'hr', 'html', 'i', 'iframe', 'img', 'input', 'ins', 'kbd', 'keygen', 'label', 'legend', 'li', 'link', 'main', 'map', 'mark', 'menu', 'menuitem', 'meta', 'meter', 'nav', 'noscript', 'object', 'ol', 'optgroup', 'option', 'output', 'p', 'param', 'picture', 'pre', 'progress', 'q', 'rp', 'rt', 'ruby', 's', 'samp', 'script', 'section', 'select', 'small', 'source', 'span', 'strong', 'style', 'sub', 'summary', 'sup', 'table', 'tbody', 'td', 'textarea', 'tfoot', 'th', 'thead', 'time', 'title', 'tr', 'track', 'u', 'ul', 'var', 'video', 'wbr', 'circle', 'clipPath', 'defs', 'ellipse', 'foreignObject', 'g', 'image', 'line', 'linearGradient', 'mask', 'path', 'pattern', 'polygon', 'polyline', 'radialGradient', 'rect', 'stop', 'svg', 'text', 'tspan'];

const _excluded = ["scrollTop", "scrollLeft"];
_react_spring_core__WEBPACK_IMPORTED_MODULE_0__["Globals"].assign({
  batchedUpdates: react_dom__WEBPACK_IMPORTED_MODULE_1__["unstable_batchedUpdates"],
  createStringInterpolator: _react_spring_shared__WEBPACK_IMPORTED_MODULE_2__[/* createStringInterpolator */ "g"],
  colors: _react_spring_shared__WEBPACK_IMPORTED_MODULE_2__[/* colors */ "e"]
});
const host = Object(_react_spring_animated__WEBPACK_IMPORTED_MODULE_3__[/* createHost */ "d"])(primitives, {
  applyAnimatedValues,
  createAnimatedStyle: style => new AnimatedStyle(style),
  getComponentProps: _ref => {
    let props = _objectWithoutPropertiesLoose(_ref, _excluded);

    return props;
  }
});
const animated = host.animated;




/***/ }),

/***/ 176:
/***/ (function(module, exports, __webpack_require__) {

/*
Breaks a Javascript string into individual user-perceived "characters" 
called extended grapheme clusters by implementing the Unicode UAX-29 standard, version 10.0.0

Usage:
var splitter = new GraphemeSplitter();
//returns an array of strings, one string for each grapheme cluster
var graphemes = splitter.splitGraphemes(string); 

*/
function GraphemeSplitter(){
	var CR = 0,
		LF = 1,
		Control = 2,
		Extend = 3,
		Regional_Indicator = 4,
		SpacingMark = 5,
		L = 6,
		V = 7,
		T = 8,
		LV = 9,
		LVT = 10,
		Other = 11,
		Prepend = 12,
		E_Base = 13,
		E_Modifier = 14,
		ZWJ = 15,
		Glue_After_Zwj = 16,
		E_Base_GAZ = 17;
		
	// BreakTypes
	var NotBreak = 0,
		BreakStart = 1,
		Break = 2,
		BreakLastRegional = 3,
		BreakPenultimateRegional = 4;
		
	function isSurrogate(str, pos) {
		return  0xd800 <= str.charCodeAt(pos) && str.charCodeAt(pos) <= 0xdbff && 
				0xdc00 <= str.charCodeAt(pos + 1) && str.charCodeAt(pos + 1) <= 0xdfff;
	}
		
	// Private function, gets a Unicode code point from a JavaScript UTF-16 string
	// handling surrogate pairs appropriately
	function codePointAt(str, idx){
		if(idx === undefined){
			idx = 0;
		}
		var code = str.charCodeAt(idx);

		// if a high surrogate
		if (0xD800 <= code && code <= 0xDBFF && 
			idx < str.length - 1){
			var hi = code;
			var low = str.charCodeAt(idx + 1);
			if (0xDC00 <= low && low <= 0xDFFF){
				return ((hi - 0xD800) * 0x400) + (low - 0xDC00) + 0x10000;
			}
			return hi;
		}
		
		// if a low surrogate
		if (0xDC00 <= code && code <= 0xDFFF &&
			idx >= 1){
			var hi = str.charCodeAt(idx - 1);
			var low = code;
			if (0xD800 <= hi && hi <= 0xDBFF){
				return ((hi - 0xD800) * 0x400) + (low - 0xDC00) + 0x10000;
			}
			return low;
		}
		
		//just return the char if an unmatched surrogate half or a 
		//single-char codepoint
		return code;
	}
	
	// Private function, returns whether a break is allowed between the 
	// two given grapheme breaking classes
	function shouldBreak(start, mid, end){
		var all = [start].concat(mid).concat([end]);
		var previous = all[all.length - 2]
		var next = end
		
		// Lookahead termintor for:
		// GB10. (E_Base | EBG) Extend* ?	E_Modifier
		var eModifierIndex = all.lastIndexOf(E_Modifier)
		if(eModifierIndex > 1 &&
			all.slice(1, eModifierIndex).every(function(c){return c == Extend}) &&
			[Extend, E_Base, E_Base_GAZ].indexOf(start) == -1){
			return Break
		}

		// Lookahead termintor for:
		// GB12. ^ (RI RI)* RI	?	RI
		// GB13. [^RI] (RI RI)* RI	?	RI
		var rIIndex = all.lastIndexOf(Regional_Indicator)
		if(rIIndex > 0 &&
			all.slice(1, rIIndex).every(function(c){return c == Regional_Indicator}) &&
			[Prepend, Regional_Indicator].indexOf(previous) == -1) { 
			if(all.filter(function(c){return c == Regional_Indicator}).length % 2 == 1) {
				return BreakLastRegional
			}
			else {
				return BreakPenultimateRegional
			}
		}
		
		// GB3. CR X LF
		if(previous == CR && next == LF){
			return NotBreak;
		}
		// GB4. (Control|CR|LF) ÷
		else if(previous == Control || previous == CR || previous == LF){
			if(next == E_Modifier && mid.every(function(c){return c == Extend})){
				return Break
			}
			else {
				return BreakStart
			}
		}
		// GB5. ÷ (Control|CR|LF)
		else if(next == Control || next == CR || next == LF){
			return BreakStart;
		}
		// GB6. L X (L|V|LV|LVT)
		else if(previous == L && 
			(next == L || next == V || next == LV || next == LVT)){
			return NotBreak;
		}
		// GB7. (LV|V) X (V|T)
		else if((previous == LV || previous == V) && 
			(next == V || next == T)){
			return NotBreak;
		}
		// GB8. (LVT|T) X (T)
		else if((previous == LVT || previous == T) && 
			next == T){
			return NotBreak;
		}
		// GB9. X (Extend|ZWJ)
		else if (next == Extend || next == ZWJ){
			return NotBreak;
		}
		// GB9a. X SpacingMark
		else if(next == SpacingMark){
			return NotBreak;
		}
		// GB9b. Prepend X
		else if (previous == Prepend){
			return NotBreak;
		}
		
		// GB10. (E_Base | EBG) Extend* ?	E_Modifier
		var previousNonExtendIndex = all.indexOf(Extend) != -1 ? all.lastIndexOf(Extend) - 1 : all.length - 2;
		if([E_Base, E_Base_GAZ].indexOf(all[previousNonExtendIndex]) != -1 &&
			all.slice(previousNonExtendIndex + 1, -1).every(function(c){return c == Extend}) &&
			next == E_Modifier){
			return NotBreak;
		}
		
		// GB11. ZWJ ? (Glue_After_Zwj | EBG)
		if(previous == ZWJ && [Glue_After_Zwj, E_Base_GAZ].indexOf(next) != -1) {
			return NotBreak;
		}

		// GB12. ^ (RI RI)* RI ? RI
		// GB13. [^RI] (RI RI)* RI ? RI
		if(mid.indexOf(Regional_Indicator) != -1) { 
			return Break;
		}
		if(previous == Regional_Indicator && next == Regional_Indicator) {
			return NotBreak;
		}

		// GB999. Any ? Any
		return BreakStart;
	}
	
	// Returns the next grapheme break in the string after the given index
	this.nextBreak = function(string, index){
		if(index === undefined){
			index = 0;
		}
		if(index < 0){
			return 0;
		}
		if(index >= string.length - 1){
			return string.length;
		}
		var prev = getGraphemeBreakProperty(codePointAt(string, index));
		var mid = []
		for (var i = index + 1; i < string.length; i++) {
			// check for already processed low surrogates
			if(isSurrogate(string, i - 1)){
				continue;
			}
		
			var next = getGraphemeBreakProperty(codePointAt(string, i));
			if(shouldBreak(prev, mid, next)){
				return i;
			}
			
			mid.push(next);
		}
		return string.length;
	};
	
	// Breaks the given string into an array of grapheme cluster strings
	this.splitGraphemes = function(str){
		var res = [];
		var index = 0;
		var brk;
		while((brk = this.nextBreak(str, index)) < str.length){
			res.push(str.slice(index, brk));
			index = brk;
		}
		if(index < str.length){
			res.push(str.slice(index));
		}
		return res;
	};

	// Returns the iterator of grapheme clusters there are in the given string
	this.iterateGraphemes = function(str) {
		var index = 0;
		var res = {
			next: (function() {
				var value;
				var brk;
				if ((brk = this.nextBreak(str, index)) < str.length) {
					value = str.slice(index, brk);
					index = brk;
					return { value: value, done: false };
				}
				if (index < str.length) {
					value = str.slice(index);
					index = str.length;
					return { value: value, done: false };
				}
				return { value: undefined, done: true };
			}).bind(this)
		};
		// ES2015 @@iterator method (iterable) for spread syntax and for...of statement
		if (typeof Symbol !== 'undefined' && Symbol.iterator) {
			res[Symbol.iterator] = function() {return res};
		}
		return res;
	};

	// Returns the number of grapheme clusters there are in the given string
	this.countGraphemes = function(str){
		var count = 0;
		var index = 0;
		var brk;
		while((brk = this.nextBreak(str, index)) < str.length){
			index = brk;
			count++;
		}
		if(index < str.length){
			count++;
		}
		return count;
	};
	
	//given a Unicode code point, determines this symbol's grapheme break property
	function getGraphemeBreakProperty(code){
		
		//grapheme break property for Unicode 10.0.0, 
		//taken from http://www.unicode.org/Public/10.0.0/ucd/auxiliary/GraphemeBreakProperty.txt
		//and adapted to JavaScript rules
		
		if(		
		(0x0600 <= code && code <= 0x0605) || // Cf   [6] ARABIC NUMBER SIGN..ARABIC NUMBER MARK ABOVE
		0x06DD == code || // Cf       ARABIC END OF AYAH
		0x070F == code || // Cf       SYRIAC ABBREVIATION MARK
		0x08E2 == code || // Cf       ARABIC DISPUTED END OF AYAH
		0x0D4E == code || // Lo       MALAYALAM LETTER DOT REPH
		0x110BD == code || // Cf       KAITHI NUMBER SIGN
		(0x111C2 <= code && code <= 0x111C3) || // Lo   [2] SHARADA SIGN JIHVAMULIYA..SHARADA SIGN UPADHMANIYA
		0x11A3A == code || // Lo       ZANABAZAR SQUARE CLUSTER-INITIAL LETTER RA
		(0x11A86 <= code && code <= 0x11A89) || // Lo   [4] SOYOMBO CLUSTER-INITIAL LETTER RA..SOYOMBO CLUSTER-INITIAL LETTER SA
		0x11D46 == code // Lo       MASARAM GONDI REPHA
		){
			return Prepend;
		}
		if(
		0x000D == code // Cc       <control-000D>
		){
			return CR;
		}
		
		if(
		0x000A == code // Cc       <control-000A>
		){
			return LF;
		}
		
		
		if(
		(0x0000 <= code && code <= 0x0009) || // Cc  [10] <control-0000>..<control-0009>
		(0x000B <= code && code <= 0x000C) || // Cc   [2] <control-000B>..<control-000C>
		(0x000E <= code && code <= 0x001F) || // Cc  [18] <control-000E>..<control-001F>
		(0x007F <= code && code <= 0x009F) || // Cc  [33] <control-007F>..<control-009F>
		0x00AD == code || // Cf       SOFT HYPHEN
		0x061C == code || // Cf       ARABIC LETTER MARK
	
		0x180E == code || // Cf       MONGOLIAN VOWEL SEPARATOR
		0x200B == code || // Cf       ZERO WIDTH SPACE
		(0x200E <= code && code <= 0x200F) || // Cf   [2] LEFT-TO-RIGHT MARK..RIGHT-TO-LEFT MARK
		0x2028 == code || // Zl       LINE SEPARATOR
		0x2029 == code || // Zp       PARAGRAPH SEPARATOR
		(0x202A <= code && code <= 0x202E) || // Cf   [5] LEFT-TO-RIGHT EMBEDDING..RIGHT-TO-LEFT OVERRIDE
		(0x2060 <= code && code <= 0x2064) || // Cf   [5] WORD JOINER..INVISIBLE PLUS
		0x2065 == code || // Cn       <reserved-2065>
		(0x2066 <= code && code <= 0x206F) || // Cf  [10] LEFT-TO-RIGHT ISOLATE..NOMINAL DIGIT SHAPES
		(0xD800 <= code && code <= 0xDFFF) || // Cs [2048] <surrogate-D800>..<surrogate-DFFF>
		0xFEFF == code || // Cf       ZERO WIDTH NO-BREAK SPACE
		(0xFFF0 <= code && code <= 0xFFF8) || // Cn   [9] <reserved-FFF0>..<reserved-FFF8>
		(0xFFF9 <= code && code <= 0xFFFB) || // Cf   [3] INTERLINEAR ANNOTATION ANCHOR..INTERLINEAR ANNOTATION TERMINATOR
		(0x1BCA0 <= code && code <= 0x1BCA3) || // Cf   [4] SHORTHAND FORMAT LETTER OVERLAP..SHORTHAND FORMAT UP STEP
		(0x1D173 <= code && code <= 0x1D17A) || // Cf   [8] MUSICAL SYMBOL BEGIN BEAM..MUSICAL SYMBOL END PHRASE
		0xE0000 == code || // Cn       <reserved-E0000>
		0xE0001 == code || // Cf       LANGUAGE TAG
		(0xE0002 <= code && code <= 0xE001F) || // Cn  [30] <reserved-E0002>..<reserved-E001F>
		(0xE0080 <= code && code <= 0xE00FF) || // Cn [128] <reserved-E0080>..<reserved-E00FF>
		(0xE01F0 <= code && code <= 0xE0FFF) // Cn [3600] <reserved-E01F0>..<reserved-E0FFF>
		){
			return Control;
		}
		
		
		if(
		(0x0300 <= code && code <= 0x036F) || // Mn [112] COMBINING GRAVE ACCENT..COMBINING LATIN SMALL LETTER X
		(0x0483 <= code && code <= 0x0487) || // Mn   [5] COMBINING CYRILLIC TITLO..COMBINING CYRILLIC POKRYTIE
		(0x0488 <= code && code <= 0x0489) || // Me   [2] COMBINING CYRILLIC HUNDRED THOUSANDS SIGN..COMBINING CYRILLIC MILLIONS SIGN
		(0x0591 <= code && code <= 0x05BD) || // Mn  [45] HEBREW ACCENT ETNAHTA..HEBREW POINT METEG
		0x05BF == code || // Mn       HEBREW POINT RAFE
		(0x05C1 <= code && code <= 0x05C2) || // Mn   [2] HEBREW POINT SHIN DOT..HEBREW POINT SIN DOT
		(0x05C4 <= code && code <= 0x05C5) || // Mn   [2] HEBREW MARK UPPER DOT..HEBREW MARK LOWER DOT
		0x05C7 == code || // Mn       HEBREW POINT QAMATS QATAN
		(0x0610 <= code && code <= 0x061A) || // Mn  [11] ARABIC SIGN SALLALLAHOU ALAYHE WASSALLAM..ARABIC SMALL KASRA
		(0x064B <= code && code <= 0x065F) || // Mn  [21] ARABIC FATHATAN..ARABIC WAVY HAMZA BELOW
		0x0670 == code || // Mn       ARABIC LETTER SUPERSCRIPT ALEF
		(0x06D6 <= code && code <= 0x06DC) || // Mn   [7] ARABIC SMALL HIGH LIGATURE SAD WITH LAM WITH ALEF MAKSURA..ARABIC SMALL HIGH SEEN
		(0x06DF <= code && code <= 0x06E4) || // Mn   [6] ARABIC SMALL HIGH ROUNDED ZERO..ARABIC SMALL HIGH MADDA
		(0x06E7 <= code && code <= 0x06E8) || // Mn   [2] ARABIC SMALL HIGH YEH..ARABIC SMALL HIGH NOON
		(0x06EA <= code && code <= 0x06ED) || // Mn   [4] ARABIC EMPTY CENTRE LOW STOP..ARABIC SMALL LOW MEEM
		0x0711 == code || // Mn       SYRIAC LETTER SUPERSCRIPT ALAPH
		(0x0730 <= code && code <= 0x074A) || // Mn  [27] SYRIAC PTHAHA ABOVE..SYRIAC BARREKH
		(0x07A6 <= code && code <= 0x07B0) || // Mn  [11] THAANA ABAFILI..THAANA SUKUN
		(0x07EB <= code && code <= 0x07F3) || // Mn   [9] NKO COMBINING SHORT HIGH TONE..NKO COMBINING DOUBLE DOT ABOVE
		(0x0816 <= code && code <= 0x0819) || // Mn   [4] SAMARITAN MARK IN..SAMARITAN MARK DAGESH
		(0x081B <= code && code <= 0x0823) || // Mn   [9] SAMARITAN MARK EPENTHETIC YUT..SAMARITAN VOWEL SIGN A
		(0x0825 <= code && code <= 0x0827) || // Mn   [3] SAMARITAN VOWEL SIGN SHORT A..SAMARITAN VOWEL SIGN U
		(0x0829 <= code && code <= 0x082D) || // Mn   [5] SAMARITAN VOWEL SIGN LONG I..SAMARITAN MARK NEQUDAA
		(0x0859 <= code && code <= 0x085B) || // Mn   [3] MANDAIC AFFRICATION MARK..MANDAIC GEMINATION MARK
		(0x08D4 <= code && code <= 0x08E1) || // Mn  [14] ARABIC SMALL HIGH WORD AR-RUB..ARABIC SMALL HIGH SIGN SAFHA
		(0x08E3 <= code && code <= 0x0902) || // Mn  [32] ARABIC TURNED DAMMA BELOW..DEVANAGARI SIGN ANUSVARA
		0x093A == code || // Mn       DEVANAGARI VOWEL SIGN OE
		0x093C == code || // Mn       DEVANAGARI SIGN NUKTA
		(0x0941 <= code && code <= 0x0948) || // Mn   [8] DEVANAGARI VOWEL SIGN U..DEVANAGARI VOWEL SIGN AI
		0x094D == code || // Mn       DEVANAGARI SIGN VIRAMA
		(0x0951 <= code && code <= 0x0957) || // Mn   [7] DEVANAGARI STRESS SIGN UDATTA..DEVANAGARI VOWEL SIGN UUE
		(0x0962 <= code && code <= 0x0963) || // Mn   [2] DEVANAGARI VOWEL SIGN VOCALIC L..DEVANAGARI VOWEL SIGN VOCALIC LL
		0x0981 == code || // Mn       BENGALI SIGN CANDRABINDU
		0x09BC == code || // Mn       BENGALI SIGN NUKTA
		0x09BE == code || // Mc       BENGALI VOWEL SIGN AA
		(0x09C1 <= code && code <= 0x09C4) || // Mn   [4] BENGALI VOWEL SIGN U..BENGALI VOWEL SIGN VOCALIC RR
		0x09CD == code || // Mn       BENGALI SIGN VIRAMA
		0x09D7 == code || // Mc       BENGALI AU LENGTH MARK
		(0x09E2 <= code && code <= 0x09E3) || // Mn   [2] BENGALI VOWEL SIGN VOCALIC L..BENGALI VOWEL SIGN VOCALIC LL
		(0x0A01 <= code && code <= 0x0A02) || // Mn   [2] GURMUKHI SIGN ADAK BINDI..GURMUKHI SIGN BINDI
		0x0A3C == code || // Mn       GURMUKHI SIGN NUKTA
		(0x0A41 <= code && code <= 0x0A42) || // Mn   [2] GURMUKHI VOWEL SIGN U..GURMUKHI VOWEL SIGN UU
		(0x0A47 <= code && code <= 0x0A48) || // Mn   [2] GURMUKHI VOWEL SIGN EE..GURMUKHI VOWEL SIGN AI
		(0x0A4B <= code && code <= 0x0A4D) || // Mn   [3] GURMUKHI VOWEL SIGN OO..GURMUKHI SIGN VIRAMA
		0x0A51 == code || // Mn       GURMUKHI SIGN UDAAT
		(0x0A70 <= code && code <= 0x0A71) || // Mn   [2] GURMUKHI TIPPI..GURMUKHI ADDAK
		0x0A75 == code || // Mn       GURMUKHI SIGN YAKASH
		(0x0A81 <= code && code <= 0x0A82) || // Mn   [2] GUJARATI SIGN CANDRABINDU..GUJARATI SIGN ANUSVARA
		0x0ABC == code || // Mn       GUJARATI SIGN NUKTA
		(0x0AC1 <= code && code <= 0x0AC5) || // Mn   [5] GUJARATI VOWEL SIGN U..GUJARATI VOWEL SIGN CANDRA E
		(0x0AC7 <= code && code <= 0x0AC8) || // Mn   [2] GUJARATI VOWEL SIGN E..GUJARATI VOWEL SIGN AI
		0x0ACD == code || // Mn       GUJARATI SIGN VIRAMA
		(0x0AE2 <= code && code <= 0x0AE3) || // Mn   [2] GUJARATI VOWEL SIGN VOCALIC L..GUJARATI VOWEL SIGN VOCALIC LL
		(0x0AFA <= code && code <= 0x0AFF) || // Mn   [6] GUJARATI SIGN SUKUN..GUJARATI SIGN TWO-CIRCLE NUKTA ABOVE
		0x0B01 == code || // Mn       ORIYA SIGN CANDRABINDU
		0x0B3C == code || // Mn       ORIYA SIGN NUKTA
		0x0B3E == code || // Mc       ORIYA VOWEL SIGN AA
		0x0B3F == code || // Mn       ORIYA VOWEL SIGN I
		(0x0B41 <= code && code <= 0x0B44) || // Mn   [4] ORIYA VOWEL SIGN U..ORIYA VOWEL SIGN VOCALIC RR
		0x0B4D == code || // Mn       ORIYA SIGN VIRAMA
		0x0B56 == code || // Mn       ORIYA AI LENGTH MARK
		0x0B57 == code || // Mc       ORIYA AU LENGTH MARK
		(0x0B62 <= code && code <= 0x0B63) || // Mn   [2] ORIYA VOWEL SIGN VOCALIC L..ORIYA VOWEL SIGN VOCALIC LL
		0x0B82 == code || // Mn       TAMIL SIGN ANUSVARA
		0x0BBE == code || // Mc       TAMIL VOWEL SIGN AA
		0x0BC0 == code || // Mn       TAMIL VOWEL SIGN II
		0x0BCD == code || // Mn       TAMIL SIGN VIRAMA
		0x0BD7 == code || // Mc       TAMIL AU LENGTH MARK
		0x0C00 == code || // Mn       TELUGU SIGN COMBINING CANDRABINDU ABOVE
		(0x0C3E <= code && code <= 0x0C40) || // Mn   [3] TELUGU VOWEL SIGN AA..TELUGU VOWEL SIGN II
		(0x0C46 <= code && code <= 0x0C48) || // Mn   [3] TELUGU VOWEL SIGN E..TELUGU VOWEL SIGN AI
		(0x0C4A <= code && code <= 0x0C4D) || // Mn   [4] TELUGU VOWEL SIGN O..TELUGU SIGN VIRAMA
		(0x0C55 <= code && code <= 0x0C56) || // Mn   [2] TELUGU LENGTH MARK..TELUGU AI LENGTH MARK
		(0x0C62 <= code && code <= 0x0C63) || // Mn   [2] TELUGU VOWEL SIGN VOCALIC L..TELUGU VOWEL SIGN VOCALIC LL
		0x0C81 == code || // Mn       KANNADA SIGN CANDRABINDU
		0x0CBC == code || // Mn       KANNADA SIGN NUKTA
		0x0CBF == code || // Mn       KANNADA VOWEL SIGN I
		0x0CC2 == code || // Mc       KANNADA VOWEL SIGN UU
		0x0CC6 == code || // Mn       KANNADA VOWEL SIGN E
		(0x0CCC <= code && code <= 0x0CCD) || // Mn   [2] KANNADA VOWEL SIGN AU..KANNADA SIGN VIRAMA
		(0x0CD5 <= code && code <= 0x0CD6) || // Mc   [2] KANNADA LENGTH MARK..KANNADA AI LENGTH MARK
		(0x0CE2 <= code && code <= 0x0CE3) || // Mn   [2] KANNADA VOWEL SIGN VOCALIC L..KANNADA VOWEL SIGN VOCALIC LL
		(0x0D00 <= code && code <= 0x0D01) || // Mn   [2] MALAYALAM SIGN COMBINING ANUSVARA ABOVE..MALAYALAM SIGN CANDRABINDU
		(0x0D3B <= code && code <= 0x0D3C) || // Mn   [2] MALAYALAM SIGN VERTICAL BAR VIRAMA..MALAYALAM SIGN CIRCULAR VIRAMA
		0x0D3E == code || // Mc       MALAYALAM VOWEL SIGN AA
		(0x0D41 <= code && code <= 0x0D44) || // Mn   [4] MALAYALAM VOWEL SIGN U..MALAYALAM VOWEL SIGN VOCALIC RR
		0x0D4D == code || // Mn       MALAYALAM SIGN VIRAMA
		0x0D57 == code || // Mc       MALAYALAM AU LENGTH MARK
		(0x0D62 <= code && code <= 0x0D63) || // Mn   [2] MALAYALAM VOWEL SIGN VOCALIC L..MALAYALAM VOWEL SIGN VOCALIC LL
		0x0DCA == code || // Mn       SINHALA SIGN AL-LAKUNA
		0x0DCF == code || // Mc       SINHALA VOWEL SIGN AELA-PILLA
		(0x0DD2 <= code && code <= 0x0DD4) || // Mn   [3] SINHALA VOWEL SIGN KETTI IS-PILLA..SINHALA VOWEL SIGN KETTI PAA-PILLA
		0x0DD6 == code || // Mn       SINHALA VOWEL SIGN DIGA PAA-PILLA
		0x0DDF == code || // Mc       SINHALA VOWEL SIGN GAYANUKITTA
		0x0E31 == code || // Mn       THAI CHARACTER MAI HAN-AKAT
		(0x0E34 <= code && code <= 0x0E3A) || // Mn   [7] THAI CHARACTER SARA I..THAI CHARACTER PHINTHU
		(0x0E47 <= code && code <= 0x0E4E) || // Mn   [8] THAI CHARACTER MAITAIKHU..THAI CHARACTER YAMAKKAN
		0x0EB1 == code || // Mn       LAO VOWEL SIGN MAI KAN
		(0x0EB4 <= code && code <= 0x0EB9) || // Mn   [6] LAO VOWEL SIGN I..LAO VOWEL SIGN UU
		(0x0EBB <= code && code <= 0x0EBC) || // Mn   [2] LAO VOWEL SIGN MAI KON..LAO SEMIVOWEL SIGN LO
		(0x0EC8 <= code && code <= 0x0ECD) || // Mn   [6] LAO TONE MAI EK..LAO NIGGAHITA
		(0x0F18 <= code && code <= 0x0F19) || // Mn   [2] TIBETAN ASTROLOGICAL SIGN -KHYUD PA..TIBETAN ASTROLOGICAL SIGN SDONG TSHUGS
		0x0F35 == code || // Mn       TIBETAN MARK NGAS BZUNG NYI ZLA
		0x0F37 == code || // Mn       TIBETAN MARK NGAS BZUNG SGOR RTAGS
		0x0F39 == code || // Mn       TIBETAN MARK TSA -PHRU
		(0x0F71 <= code && code <= 0x0F7E) || // Mn  [14] TIBETAN VOWEL SIGN AA..TIBETAN SIGN RJES SU NGA RO
		(0x0F80 <= code && code <= 0x0F84) || // Mn   [5] TIBETAN VOWEL SIGN REVERSED I..TIBETAN MARK HALANTA
		(0x0F86 <= code && code <= 0x0F87) || // Mn   [2] TIBETAN SIGN LCI RTAGS..TIBETAN SIGN YANG RTAGS
		(0x0F8D <= code && code <= 0x0F97) || // Mn  [11] TIBETAN SUBJOINED SIGN LCE TSA CAN..TIBETAN SUBJOINED LETTER JA
		(0x0F99 <= code && code <= 0x0FBC) || // Mn  [36] TIBETAN SUBJOINED LETTER NYA..TIBETAN SUBJOINED LETTER FIXED-FORM RA
		0x0FC6 == code || // Mn       TIBETAN SYMBOL PADMA GDAN
		(0x102D <= code && code <= 0x1030) || // Mn   [4] MYANMAR VOWEL SIGN I..MYANMAR VOWEL SIGN UU
		(0x1032 <= code && code <= 0x1037) || // Mn   [6] MYANMAR VOWEL SIGN AI..MYANMAR SIGN DOT BELOW
		(0x1039 <= code && code <= 0x103A) || // Mn   [2] MYANMAR SIGN VIRAMA..MYANMAR SIGN ASAT
		(0x103D <= code && code <= 0x103E) || // Mn   [2] MYANMAR CONSONANT SIGN MEDIAL WA..MYANMAR CONSONANT SIGN MEDIAL HA
		(0x1058 <= code && code <= 0x1059) || // Mn   [2] MYANMAR VOWEL SIGN VOCALIC L..MYANMAR VOWEL SIGN VOCALIC LL
		(0x105E <= code && code <= 0x1060) || // Mn   [3] MYANMAR CONSONANT SIGN MON MEDIAL NA..MYANMAR CONSONANT SIGN MON MEDIAL LA
		(0x1071 <= code && code <= 0x1074) || // Mn   [4] MYANMAR VOWEL SIGN GEBA KAREN I..MYANMAR VOWEL SIGN KAYAH EE
		0x1082 == code || // Mn       MYANMAR CONSONANT SIGN SHAN MEDIAL WA
		(0x1085 <= code && code <= 0x1086) || // Mn   [2] MYANMAR VOWEL SIGN SHAN E ABOVE..MYANMAR VOWEL SIGN SHAN FINAL Y
		0x108D == code || // Mn       MYANMAR SIGN SHAN COUNCIL EMPHATIC TONE
		0x109D == code || // Mn       MYANMAR VOWEL SIGN AITON AI
		(0x135D <= code && code <= 0x135F) || // Mn   [3] ETHIOPIC COMBINING GEMINATION AND VOWEL LENGTH MARK..ETHIOPIC COMBINING GEMINATION MARK
		(0x1712 <= code && code <= 0x1714) || // Mn   [3] TAGALOG VOWEL SIGN I..TAGALOG SIGN VIRAMA
		(0x1732 <= code && code <= 0x1734) || // Mn   [3] HANUNOO VOWEL SIGN I..HANUNOO SIGN PAMUDPOD
		(0x1752 <= code && code <= 0x1753) || // Mn   [2] BUHID VOWEL SIGN I..BUHID VOWEL SIGN U
		(0x1772 <= code && code <= 0x1773) || // Mn   [2] TAGBANWA VOWEL SIGN I..TAGBANWA VOWEL SIGN U
		(0x17B4 <= code && code <= 0x17B5) || // Mn   [2] KHMER VOWEL INHERENT AQ..KHMER VOWEL INHERENT AA
		(0x17B7 <= code && code <= 0x17BD) || // Mn   [7] KHMER VOWEL SIGN I..KHMER VOWEL SIGN UA
		0x17C6 == code || // Mn       KHMER SIGN NIKAHIT
		(0x17C9 <= code && code <= 0x17D3) || // Mn  [11] KHMER SIGN MUUSIKATOAN..KHMER SIGN BATHAMASAT
		0x17DD == code || // Mn       KHMER SIGN ATTHACAN
		(0x180B <= code && code <= 0x180D) || // Mn   [3] MONGOLIAN FREE VARIATION SELECTOR ONE..MONGOLIAN FREE VARIATION SELECTOR THREE
		(0x1885 <= code && code <= 0x1886) || // Mn   [2] MONGOLIAN LETTER ALI GALI BALUDA..MONGOLIAN LETTER ALI GALI THREE BALUDA
		0x18A9 == code || // Mn       MONGOLIAN LETTER ALI GALI DAGALGA
		(0x1920 <= code && code <= 0x1922) || // Mn   [3] LIMBU VOWEL SIGN A..LIMBU VOWEL SIGN U
		(0x1927 <= code && code <= 0x1928) || // Mn   [2] LIMBU VOWEL SIGN E..LIMBU VOWEL SIGN O
		0x1932 == code || // Mn       LIMBU SMALL LETTER ANUSVARA
		(0x1939 <= code && code <= 0x193B) || // Mn   [3] LIMBU SIGN MUKPHRENG..LIMBU SIGN SA-I
		(0x1A17 <= code && code <= 0x1A18) || // Mn   [2] BUGINESE VOWEL SIGN I..BUGINESE VOWEL SIGN U
		0x1A1B == code || // Mn       BUGINESE VOWEL SIGN AE
		0x1A56 == code || // Mn       TAI THAM CONSONANT SIGN MEDIAL LA
		(0x1A58 <= code && code <= 0x1A5E) || // Mn   [7] TAI THAM SIGN MAI KANG LAI..TAI THAM CONSONANT SIGN SA
		0x1A60 == code || // Mn       TAI THAM SIGN SAKOT
		0x1A62 == code || // Mn       TAI THAM VOWEL SIGN MAI SAT
		(0x1A65 <= code && code <= 0x1A6C) || // Mn   [8] TAI THAM VOWEL SIGN I..TAI THAM VOWEL SIGN OA BELOW
		(0x1A73 <= code && code <= 0x1A7C) || // Mn  [10] TAI THAM VOWEL SIGN OA ABOVE..TAI THAM SIGN KHUEN-LUE KARAN
		0x1A7F == code || // Mn       TAI THAM COMBINING CRYPTOGRAMMIC DOT
		(0x1AB0 <= code && code <= 0x1ABD) || // Mn  [14] COMBINING DOUBLED CIRCUMFLEX ACCENT..COMBINING PARENTHESES BELOW
		0x1ABE == code || // Me       COMBINING PARENTHESES OVERLAY
		(0x1B00 <= code && code <= 0x1B03) || // Mn   [4] BALINESE SIGN ULU RICEM..BALINESE SIGN SURANG
		0x1B34 == code || // Mn       BALINESE SIGN REREKAN
		(0x1B36 <= code && code <= 0x1B3A) || // Mn   [5] BALINESE VOWEL SIGN ULU..BALINESE VOWEL SIGN RA REPA
		0x1B3C == code || // Mn       BALINESE VOWEL SIGN LA LENGA
		0x1B42 == code || // Mn       BALINESE VOWEL SIGN PEPET
		(0x1B6B <= code && code <= 0x1B73) || // Mn   [9] BALINESE MUSICAL SYMBOL COMBINING TEGEH..BALINESE MUSICAL SYMBOL COMBINING GONG
		(0x1B80 <= code && code <= 0x1B81) || // Mn   [2] SUNDANESE SIGN PANYECEK..SUNDANESE SIGN PANGLAYAR
		(0x1BA2 <= code && code <= 0x1BA5) || // Mn   [4] SUNDANESE CONSONANT SIGN PANYAKRA..SUNDANESE VOWEL SIGN PANYUKU
		(0x1BA8 <= code && code <= 0x1BA9) || // Mn   [2] SUNDANESE VOWEL SIGN PAMEPET..SUNDANESE VOWEL SIGN PANEULEUNG
		(0x1BAB <= code && code <= 0x1BAD) || // Mn   [3] SUNDANESE SIGN VIRAMA..SUNDANESE CONSONANT SIGN PASANGAN WA
		0x1BE6 == code || // Mn       BATAK SIGN TOMPI
		(0x1BE8 <= code && code <= 0x1BE9) || // Mn   [2] BATAK VOWEL SIGN PAKPAK E..BATAK VOWEL SIGN EE
		0x1BED == code || // Mn       BATAK VOWEL SIGN KARO O
		(0x1BEF <= code && code <= 0x1BF1) || // Mn   [3] BATAK VOWEL SIGN U FOR SIMALUNGUN SA..BATAK CONSONANT SIGN H
		(0x1C2C <= code && code <= 0x1C33) || // Mn   [8] LEPCHA VOWEL SIGN E..LEPCHA CONSONANT SIGN T
		(0x1C36 <= code && code <= 0x1C37) || // Mn   [2] LEPCHA SIGN RAN..LEPCHA SIGN NUKTA
		(0x1CD0 <= code && code <= 0x1CD2) || // Mn   [3] VEDIC TONE KARSHANA..VEDIC TONE PRENKHA
		(0x1CD4 <= code && code <= 0x1CE0) || // Mn  [13] VEDIC SIGN YAJURVEDIC MIDLINE SVARITA..VEDIC TONE RIGVEDIC KASHMIRI INDEPENDENT SVARITA
		(0x1CE2 <= code && code <= 0x1CE8) || // Mn   [7] VEDIC SIGN VISARGA SVARITA..VEDIC SIGN VISARGA ANUDATTA WITH TAIL
		0x1CED == code || // Mn       VEDIC SIGN TIRYAK
		0x1CF4 == code || // Mn       VEDIC TONE CANDRA ABOVE
		(0x1CF8 <= code && code <= 0x1CF9) || // Mn   [2] VEDIC TONE RING ABOVE..VEDIC TONE DOUBLE RING ABOVE
		(0x1DC0 <= code && code <= 0x1DF9) || // Mn  [58] COMBINING DOTTED GRAVE ACCENT..COMBINING WIDE INVERTED BRIDGE BELOW
		(0x1DFB <= code && code <= 0x1DFF) || // Mn   [5] COMBINING DELETION MARK..COMBINING RIGHT ARROWHEAD AND DOWN ARROWHEAD BELOW
		0x200C == code || // Cf       ZERO WIDTH NON-JOINER
		(0x20D0 <= code && code <= 0x20DC) || // Mn  [13] COMBINING LEFT HARPOON ABOVE..COMBINING FOUR DOTS ABOVE
		(0x20DD <= code && code <= 0x20E0) || // Me   [4] COMBINING ENCLOSING CIRCLE..COMBINING ENCLOSING CIRCLE BACKSLASH
		0x20E1 == code || // Mn       COMBINING LEFT RIGHT ARROW ABOVE
		(0x20E2 <= code && code <= 0x20E4) || // Me   [3] COMBINING ENCLOSING SCREEN..COMBINING ENCLOSING UPWARD POINTING TRIANGLE
		(0x20E5 <= code && code <= 0x20F0) || // Mn  [12] COMBINING REVERSE SOLIDUS OVERLAY..COMBINING ASTERISK ABOVE
		(0x2CEF <= code && code <= 0x2CF1) || // Mn   [3] COPTIC COMBINING NI ABOVE..COPTIC COMBINING SPIRITUS LENIS
		0x2D7F == code || // Mn       TIFINAGH CONSONANT JOINER
		(0x2DE0 <= code && code <= 0x2DFF) || // Mn  [32] COMBINING CYRILLIC LETTER BE..COMBINING CYRILLIC LETTER IOTIFIED BIG YUS
		(0x302A <= code && code <= 0x302D) || // Mn   [4] IDEOGRAPHIC LEVEL TONE MARK..IDEOGRAPHIC ENTERING TONE MARK
		(0x302E <= code && code <= 0x302F) || // Mc   [2] HANGUL SINGLE DOT TONE MARK..HANGUL DOUBLE DOT TONE MARK
		(0x3099 <= code && code <= 0x309A) || // Mn   [2] COMBINING KATAKANA-HIRAGANA VOICED SOUND MARK..COMBINING KATAKANA-HIRAGANA SEMI-VOICED SOUND MARK
		0xA66F == code || // Mn       COMBINING CYRILLIC VZMET
		(0xA670 <= code && code <= 0xA672) || // Me   [3] COMBINING CYRILLIC TEN MILLIONS SIGN..COMBINING CYRILLIC THOUSAND MILLIONS SIGN
		(0xA674 <= code && code <= 0xA67D) || // Mn  [10] COMBINING CYRILLIC LETTER UKRAINIAN IE..COMBINING CYRILLIC PAYEROK
		(0xA69E <= code && code <= 0xA69F) || // Mn   [2] COMBINING CYRILLIC LETTER EF..COMBINING CYRILLIC LETTER IOTIFIED E
		(0xA6F0 <= code && code <= 0xA6F1) || // Mn   [2] BAMUM COMBINING MARK KOQNDON..BAMUM COMBINING MARK TUKWENTIS
		0xA802 == code || // Mn       SYLOTI NAGRI SIGN DVISVARA
		0xA806 == code || // Mn       SYLOTI NAGRI SIGN HASANTA
		0xA80B == code || // Mn       SYLOTI NAGRI SIGN ANUSVARA
		(0xA825 <= code && code <= 0xA826) || // Mn   [2] SYLOTI NAGRI VOWEL SIGN U..SYLOTI NAGRI VOWEL SIGN E
		(0xA8C4 <= code && code <= 0xA8C5) || // Mn   [2] SAURASHTRA SIGN VIRAMA..SAURASHTRA SIGN CANDRABINDU
		(0xA8E0 <= code && code <= 0xA8F1) || // Mn  [18] COMBINING DEVANAGARI DIGIT ZERO..COMBINING DEVANAGARI SIGN AVAGRAHA
		(0xA926 <= code && code <= 0xA92D) || // Mn   [8] KAYAH LI VOWEL UE..KAYAH LI TONE CALYA PLOPHU
		(0xA947 <= code && code <= 0xA951) || // Mn  [11] REJANG VOWEL SIGN I..REJANG CONSONANT SIGN R
		(0xA980 <= code && code <= 0xA982) || // Mn   [3] JAVANESE SIGN PANYANGGA..JAVANESE SIGN LAYAR
		0xA9B3 == code || // Mn       JAVANESE SIGN CECAK TELU
		(0xA9B6 <= code && code <= 0xA9B9) || // Mn   [4] JAVANESE VOWEL SIGN WULU..JAVANESE VOWEL SIGN SUKU MENDUT
		0xA9BC == code || // Mn       JAVANESE VOWEL SIGN PEPET
		0xA9E5 == code || // Mn       MYANMAR SIGN SHAN SAW
		(0xAA29 <= code && code <= 0xAA2E) || // Mn   [6] CHAM VOWEL SIGN AA..CHAM VOWEL SIGN OE
		(0xAA31 <= code && code <= 0xAA32) || // Mn   [2] CHAM VOWEL SIGN AU..CHAM VOWEL SIGN UE
		(0xAA35 <= code && code <= 0xAA36) || // Mn   [2] CHAM CONSONANT SIGN LA..CHAM CONSONANT SIGN WA
		0xAA43 == code || // Mn       CHAM CONSONANT SIGN FINAL NG
		0xAA4C == code || // Mn       CHAM CONSONANT SIGN FINAL M
		0xAA7C == code || // Mn       MYANMAR SIGN TAI LAING TONE-2
		0xAAB0 == code || // Mn       TAI VIET MAI KANG
		(0xAAB2 <= code && code <= 0xAAB4) || // Mn   [3] TAI VIET VOWEL I..TAI VIET VOWEL U
		(0xAAB7 <= code && code <= 0xAAB8) || // Mn   [2] TAI VIET MAI KHIT..TAI VIET VOWEL IA
		(0xAABE <= code && code <= 0xAABF) || // Mn   [2] TAI VIET VOWEL AM..TAI VIET TONE MAI EK
		0xAAC1 == code || // Mn       TAI VIET TONE MAI THO
		(0xAAEC <= code && code <= 0xAAED) || // Mn   [2] MEETEI MAYEK VOWEL SIGN UU..MEETEI MAYEK VOWEL SIGN AAI
		0xAAF6 == code || // Mn       MEETEI MAYEK VIRAMA
		0xABE5 == code || // Mn       MEETEI MAYEK VOWEL SIGN ANAP
		0xABE8 == code || // Mn       MEETEI MAYEK VOWEL SIGN UNAP
		0xABED == code || // Mn       MEETEI MAYEK APUN IYEK
		0xFB1E == code || // Mn       HEBREW POINT JUDEO-SPANISH VARIKA
		(0xFE00 <= code && code <= 0xFE0F) || // Mn  [16] VARIATION SELECTOR-1..VARIATION SELECTOR-16
		(0xFE20 <= code && code <= 0xFE2F) || // Mn  [16] COMBINING LIGATURE LEFT HALF..COMBINING CYRILLIC TITLO RIGHT HALF
		(0xFF9E <= code && code <= 0xFF9F) || // Lm   [2] HALFWIDTH KATAKANA VOICED SOUND MARK..HALFWIDTH KATAKANA SEMI-VOICED SOUND MARK
		0x101FD == code || // Mn       PHAISTOS DISC SIGN COMBINING OBLIQUE STROKE
		0x102E0 == code || // Mn       COPTIC EPACT THOUSANDS MARK
		(0x10376 <= code && code <= 0x1037A) || // Mn   [5] COMBINING OLD PERMIC LETTER AN..COMBINING OLD PERMIC LETTER SII
		(0x10A01 <= code && code <= 0x10A03) || // Mn   [3] KHAROSHTHI VOWEL SIGN I..KHAROSHTHI VOWEL SIGN VOCALIC R
		(0x10A05 <= code && code <= 0x10A06) || // Mn   [2] KHAROSHTHI VOWEL SIGN E..KHAROSHTHI VOWEL SIGN O
		(0x10A0C <= code && code <= 0x10A0F) || // Mn   [4] KHAROSHTHI VOWEL LENGTH MARK..KHAROSHTHI SIGN VISARGA
		(0x10A38 <= code && code <= 0x10A3A) || // Mn   [3] KHAROSHTHI SIGN BAR ABOVE..KHAROSHTHI SIGN DOT BELOW
		0x10A3F == code || // Mn       KHAROSHTHI VIRAMA
		(0x10AE5 <= code && code <= 0x10AE6) || // Mn   [2] MANICHAEAN ABBREVIATION MARK ABOVE..MANICHAEAN ABBREVIATION MARK BELOW
		0x11001 == code || // Mn       BRAHMI SIGN ANUSVARA
		(0x11038 <= code && code <= 0x11046) || // Mn  [15] BRAHMI VOWEL SIGN AA..BRAHMI VIRAMA
		(0x1107F <= code && code <= 0x11081) || // Mn   [3] BRAHMI NUMBER JOINER..KAITHI SIGN ANUSVARA
		(0x110B3 <= code && code <= 0x110B6) || // Mn   [4] KAITHI VOWEL SIGN U..KAITHI VOWEL SIGN AI
		(0x110B9 <= code && code <= 0x110BA) || // Mn   [2] KAITHI SIGN VIRAMA..KAITHI SIGN NUKTA
		(0x11100 <= code && code <= 0x11102) || // Mn   [3] CHAKMA SIGN CANDRABINDU..CHAKMA SIGN VISARGA
		(0x11127 <= code && code <= 0x1112B) || // Mn   [5] CHAKMA VOWEL SIGN A..CHAKMA VOWEL SIGN UU
		(0x1112D <= code && code <= 0x11134) || // Mn   [8] CHAKMA VOWEL SIGN AI..CHAKMA MAAYYAA
		0x11173 == code || // Mn       MAHAJANI SIGN NUKTA
		(0x11180 <= code && code <= 0x11181) || // Mn   [2] SHARADA SIGN CANDRABINDU..SHARADA SIGN ANUSVARA
		(0x111B6 <= code && code <= 0x111BE) || // Mn   [9] SHARADA VOWEL SIGN U..SHARADA VOWEL SIGN O
		(0x111CA <= code && code <= 0x111CC) || // Mn   [3] SHARADA SIGN NUKTA..SHARADA EXTRA SHORT VOWEL MARK
		(0x1122F <= code && code <= 0x11231) || // Mn   [3] KHOJKI VOWEL SIGN U..KHOJKI VOWEL SIGN AI
		0x11234 == code || // Mn       KHOJKI SIGN ANUSVARA
		(0x11236 <= code && code <= 0x11237) || // Mn   [2] KHOJKI SIGN NUKTA..KHOJKI SIGN SHADDA
		0x1123E == code || // Mn       KHOJKI SIGN SUKUN
		0x112DF == code || // Mn       KHUDAWADI SIGN ANUSVARA
		(0x112E3 <= code && code <= 0x112EA) || // Mn   [8] KHUDAWADI VOWEL SIGN U..KHUDAWADI SIGN VIRAMA
		(0x11300 <= code && code <= 0x11301) || // Mn   [2] GRANTHA SIGN COMBINING ANUSVARA ABOVE..GRANTHA SIGN CANDRABINDU
		0x1133C == code || // Mn       GRANTHA SIGN NUKTA
		0x1133E == code || // Mc       GRANTHA VOWEL SIGN AA
		0x11340 == code || // Mn       GRANTHA VOWEL SIGN II
		0x11357 == code || // Mc       GRANTHA AU LENGTH MARK
		(0x11366 <= code && code <= 0x1136C) || // Mn   [7] COMBINING GRANTHA DIGIT ZERO..COMBINING GRANTHA DIGIT SIX
		(0x11370 <= code && code <= 0x11374) || // Mn   [5] COMBINING GRANTHA LETTER A..COMBINING GRANTHA LETTER PA
		(0x11438 <= code && code <= 0x1143F) || // Mn   [8] NEWA VOWEL SIGN U..NEWA VOWEL SIGN AI
		(0x11442 <= code && code <= 0x11444) || // Mn   [3] NEWA SIGN VIRAMA..NEWA SIGN ANUSVARA
		0x11446 == code || // Mn       NEWA SIGN NUKTA
		0x114B0 == code || // Mc       TIRHUTA VOWEL SIGN AA
		(0x114B3 <= code && code <= 0x114B8) || // Mn   [6] TIRHUTA VOWEL SIGN U..TIRHUTA VOWEL SIGN VOCALIC LL
		0x114BA == code || // Mn       TIRHUTA VOWEL SIGN SHORT E
		0x114BD == code || // Mc       TIRHUTA VOWEL SIGN SHORT O
		(0x114BF <= code && code <= 0x114C0) || // Mn   [2] TIRHUTA SIGN CANDRABINDU..TIRHUTA SIGN ANUSVARA
		(0x114C2 <= code && code <= 0x114C3) || // Mn   [2] TIRHUTA SIGN VIRAMA..TIRHUTA SIGN NUKTA
		0x115AF == code || // Mc       SIDDHAM VOWEL SIGN AA
		(0x115B2 <= code && code <= 0x115B5) || // Mn   [4] SIDDHAM VOWEL SIGN U..SIDDHAM VOWEL SIGN VOCALIC RR
		(0x115BC <= code && code <= 0x115BD) || // Mn   [2] SIDDHAM SIGN CANDRABINDU..SIDDHAM SIGN ANUSVARA
		(0x115BF <= code && code <= 0x115C0) || // Mn   [2] SIDDHAM SIGN VIRAMA..SIDDHAM SIGN NUKTA
		(0x115DC <= code && code <= 0x115DD) || // Mn   [2] SIDDHAM VOWEL SIGN ALTERNATE U..SIDDHAM VOWEL SIGN ALTERNATE UU
		(0x11633 <= code && code <= 0x1163A) || // Mn   [8] MODI VOWEL SIGN U..MODI VOWEL SIGN AI
		0x1163D == code || // Mn       MODI SIGN ANUSVARA
		(0x1163F <= code && code <= 0x11640) || // Mn   [2] MODI SIGN VIRAMA..MODI SIGN ARDHACANDRA
		0x116AB == code || // Mn       TAKRI SIGN ANUSVARA
		0x116AD == code || // Mn       TAKRI VOWEL SIGN AA
		(0x116B0 <= code && code <= 0x116B5) || // Mn   [6] TAKRI VOWEL SIGN U..TAKRI VOWEL SIGN AU
		0x116B7 == code || // Mn       TAKRI SIGN NUKTA
		(0x1171D <= code && code <= 0x1171F) || // Mn   [3] AHOM CONSONANT SIGN MEDIAL LA..AHOM CONSONANT SIGN MEDIAL LIGATING RA
		(0x11722 <= code && code <= 0x11725) || // Mn   [4] AHOM VOWEL SIGN I..AHOM VOWEL SIGN UU
		(0x11727 <= code && code <= 0x1172B) || // Mn   [5] AHOM VOWEL SIGN AW..AHOM SIGN KILLER
		(0x11A01 <= code && code <= 0x11A06) || // Mn   [6] ZANABAZAR SQUARE VOWEL SIGN I..ZANABAZAR SQUARE VOWEL SIGN O
		(0x11A09 <= code && code <= 0x11A0A) || // Mn   [2] ZANABAZAR SQUARE VOWEL SIGN REVERSED I..ZANABAZAR SQUARE VOWEL LENGTH MARK
		(0x11A33 <= code && code <= 0x11A38) || // Mn   [6] ZANABAZAR SQUARE FINAL CONSONANT MARK..ZANABAZAR SQUARE SIGN ANUSVARA
		(0x11A3B <= code && code <= 0x11A3E) || // Mn   [4] ZANABAZAR SQUARE CLUSTER-FINAL LETTER YA..ZANABAZAR SQUARE CLUSTER-FINAL LETTER VA
		0x11A47 == code || // Mn       ZANABAZAR SQUARE SUBJOINER
		(0x11A51 <= code && code <= 0x11A56) || // Mn   [6] SOYOMBO VOWEL SIGN I..SOYOMBO VOWEL SIGN OE
		(0x11A59 <= code && code <= 0x11A5B) || // Mn   [3] SOYOMBO VOWEL SIGN VOCALIC R..SOYOMBO VOWEL LENGTH MARK
		(0x11A8A <= code && code <= 0x11A96) || // Mn  [13] SOYOMBO FINAL CONSONANT SIGN G..SOYOMBO SIGN ANUSVARA
		(0x11A98 <= code && code <= 0x11A99) || // Mn   [2] SOYOMBO GEMINATION MARK..SOYOMBO SUBJOINER
		(0x11C30 <= code && code <= 0x11C36) || // Mn   [7] BHAIKSUKI VOWEL SIGN I..BHAIKSUKI VOWEL SIGN VOCALIC L
		(0x11C38 <= code && code <= 0x11C3D) || // Mn   [6] BHAIKSUKI VOWEL SIGN E..BHAIKSUKI SIGN ANUSVARA
		0x11C3F == code || // Mn       BHAIKSUKI SIGN VIRAMA
		(0x11C92 <= code && code <= 0x11CA7) || // Mn  [22] MARCHEN SUBJOINED LETTER KA..MARCHEN SUBJOINED LETTER ZA
		(0x11CAA <= code && code <= 0x11CB0) || // Mn   [7] MARCHEN SUBJOINED LETTER RA..MARCHEN VOWEL SIGN AA
		(0x11CB2 <= code && code <= 0x11CB3) || // Mn   [2] MARCHEN VOWEL SIGN U..MARCHEN VOWEL SIGN E
		(0x11CB5 <= code && code <= 0x11CB6) || // Mn   [2] MARCHEN SIGN ANUSVARA..MARCHEN SIGN CANDRABINDU
		(0x11D31 <= code && code <= 0x11D36) || // Mn   [6] MASARAM GONDI VOWEL SIGN AA..MASARAM GONDI VOWEL SIGN VOCALIC R
		0x11D3A == code || // Mn       MASARAM GONDI VOWEL SIGN E
		(0x11D3C <= code && code <= 0x11D3D) || // Mn   [2] MASARAM GONDI VOWEL SIGN AI..MASARAM GONDI VOWEL SIGN O
		(0x11D3F <= code && code <= 0x11D45) || // Mn   [7] MASARAM GONDI VOWEL SIGN AU..MASARAM GONDI VIRAMA
		0x11D47 == code || // Mn       MASARAM GONDI RA-KARA
		(0x16AF0 <= code && code <= 0x16AF4) || // Mn   [5] BASSA VAH COMBINING HIGH TONE..BASSA VAH COMBINING HIGH-LOW TONE
		(0x16B30 <= code && code <= 0x16B36) || // Mn   [7] PAHAWH HMONG MARK CIM TUB..PAHAWH HMONG MARK CIM TAUM
		(0x16F8F <= code && code <= 0x16F92) || // Mn   [4] MIAO TONE RIGHT..MIAO TONE BELOW
		(0x1BC9D <= code && code <= 0x1BC9E) || // Mn   [2] DUPLOYAN THICK LETTER SELECTOR..DUPLOYAN DOUBLE MARK
		0x1D165 == code || // Mc       MUSICAL SYMBOL COMBINING STEM
		(0x1D167 <= code && code <= 0x1D169) || // Mn   [3] MUSICAL SYMBOL COMBINING TREMOLO-1..MUSICAL SYMBOL COMBINING TREMOLO-3
		(0x1D16E <= code && code <= 0x1D172) || // Mc   [5] MUSICAL SYMBOL COMBINING FLAG-1..MUSICAL SYMBOL COMBINING FLAG-5
		(0x1D17B <= code && code <= 0x1D182) || // Mn   [8] MUSICAL SYMBOL COMBINING ACCENT..MUSICAL SYMBOL COMBINING LOURE
		(0x1D185 <= code && code <= 0x1D18B) || // Mn   [7] MUSICAL SYMBOL COMBINING DOIT..MUSICAL SYMBOL COMBINING TRIPLE TONGUE
		(0x1D1AA <= code && code <= 0x1D1AD) || // Mn   [4] MUSICAL SYMBOL COMBINING DOWN BOW..MUSICAL SYMBOL COMBINING SNAP PIZZICATO
		(0x1D242 <= code && code <= 0x1D244) || // Mn   [3] COMBINING GREEK MUSICAL TRISEME..COMBINING GREEK MUSICAL PENTASEME
		(0x1DA00 <= code && code <= 0x1DA36) || // Mn  [55] SIGNWRITING HEAD RIM..SIGNWRITING AIR SUCKING IN
		(0x1DA3B <= code && code <= 0x1DA6C) || // Mn  [50] SIGNWRITING MOUTH CLOSED NEUTRAL..SIGNWRITING EXCITEMENT
		0x1DA75 == code || // Mn       SIGNWRITING UPPER BODY TILTING FROM HIP JOINTS
		0x1DA84 == code || // Mn       SIGNWRITING LOCATION HEAD NECK
		(0x1DA9B <= code && code <= 0x1DA9F) || // Mn   [5] SIGNWRITING FILL MODIFIER-2..SIGNWRITING FILL MODIFIER-6
		(0x1DAA1 <= code && code <= 0x1DAAF) || // Mn  [15] SIGNWRITING ROTATION MODIFIER-2..SIGNWRITING ROTATION MODIFIER-16
		(0x1E000 <= code && code <= 0x1E006) || // Mn   [7] COMBINING GLAGOLITIC LETTER AZU..COMBINING GLAGOLITIC LETTER ZHIVETE
		(0x1E008 <= code && code <= 0x1E018) || // Mn  [17] COMBINING GLAGOLITIC LETTER ZEMLJA..COMBINING GLAGOLITIC LETTER HERU
		(0x1E01B <= code && code <= 0x1E021) || // Mn   [7] COMBINING GLAGOLITIC LETTER SHTA..COMBINING GLAGOLITIC LETTER YATI
		(0x1E023 <= code && code <= 0x1E024) || // Mn   [2] COMBINING GLAGOLITIC LETTER YU..COMBINING GLAGOLITIC LETTER SMALL YUS
		(0x1E026 <= code && code <= 0x1E02A) || // Mn   [5] COMBINING GLAGOLITIC LETTER YO..COMBINING GLAGOLITIC LETTER FITA
		(0x1E8D0 <= code && code <= 0x1E8D6) || // Mn   [7] MENDE KIKAKUI COMBINING NUMBER TEENS..MENDE KIKAKUI COMBINING NUMBER MILLIONS
		(0x1E944 <= code && code <= 0x1E94A) || // Mn   [7] ADLAM ALIF LENGTHENER..ADLAM NUKTA
		(0xE0020 <= code && code <= 0xE007F) || // Cf  [96] TAG SPACE..CANCEL TAG
		(0xE0100 <= code && code <= 0xE01EF) // Mn [240] VARIATION SELECTOR-17..VARIATION SELECTOR-256
		){
			return Extend;
		}
		
		
		if(
		(0x1F1E6 <= code && code <= 0x1F1FF) // So  [26] REGIONAL INDICATOR SYMBOL LETTER A..REGIONAL INDICATOR SYMBOL LETTER Z
		){
			return Regional_Indicator;
		}
		
		if(
		0x0903 == code || // Mc       DEVANAGARI SIGN VISARGA
		0x093B == code || // Mc       DEVANAGARI VOWEL SIGN OOE
		(0x093E <= code && code <= 0x0940) || // Mc   [3] DEVANAGARI VOWEL SIGN AA..DEVANAGARI VOWEL SIGN II
		(0x0949 <= code && code <= 0x094C) || // Mc   [4] DEVANAGARI VOWEL SIGN CANDRA O..DEVANAGARI VOWEL SIGN AU
		(0x094E <= code && code <= 0x094F) || // Mc   [2] DEVANAGARI VOWEL SIGN PRISHTHAMATRA E..DEVANAGARI VOWEL SIGN AW
		(0x0982 <= code && code <= 0x0983) || // Mc   [2] BENGALI SIGN ANUSVARA..BENGALI SIGN VISARGA
		(0x09BF <= code && code <= 0x09C0) || // Mc   [2] BENGALI VOWEL SIGN I..BENGALI VOWEL SIGN II
		(0x09C7 <= code && code <= 0x09C8) || // Mc   [2] BENGALI VOWEL SIGN E..BENGALI VOWEL SIGN AI
		(0x09CB <= code && code <= 0x09CC) || // Mc   [2] BENGALI VOWEL SIGN O..BENGALI VOWEL SIGN AU
		0x0A03 == code || // Mc       GURMUKHI SIGN VISARGA
		(0x0A3E <= code && code <= 0x0A40) || // Mc   [3] GURMUKHI VOWEL SIGN AA..GURMUKHI VOWEL SIGN II
		0x0A83 == code || // Mc       GUJARATI SIGN VISARGA
		(0x0ABE <= code && code <= 0x0AC0) || // Mc   [3] GUJARATI VOWEL SIGN AA..GUJARATI VOWEL SIGN II
		0x0AC9 == code || // Mc       GUJARATI VOWEL SIGN CANDRA O
		(0x0ACB <= code && code <= 0x0ACC) || // Mc   [2] GUJARATI VOWEL SIGN O..GUJARATI VOWEL SIGN AU
		(0x0B02 <= code && code <= 0x0B03) || // Mc   [2] ORIYA SIGN ANUSVARA..ORIYA SIGN VISARGA
		0x0B40 == code || // Mc       ORIYA VOWEL SIGN II
		(0x0B47 <= code && code <= 0x0B48) || // Mc   [2] ORIYA VOWEL SIGN E..ORIYA VOWEL SIGN AI
		(0x0B4B <= code && code <= 0x0B4C) || // Mc   [2] ORIYA VOWEL SIGN O..ORIYA VOWEL SIGN AU
		0x0BBF == code || // Mc       TAMIL VOWEL SIGN I
		(0x0BC1 <= code && code <= 0x0BC2) || // Mc   [2] TAMIL VOWEL SIGN U..TAMIL VOWEL SIGN UU
		(0x0BC6 <= code && code <= 0x0BC8) || // Mc   [3] TAMIL VOWEL SIGN E..TAMIL VOWEL SIGN AI
		(0x0BCA <= code && code <= 0x0BCC) || // Mc   [3] TAMIL VOWEL SIGN O..TAMIL VOWEL SIGN AU
		(0x0C01 <= code && code <= 0x0C03) || // Mc   [3] TELUGU SIGN CANDRABINDU..TELUGU SIGN VISARGA
		(0x0C41 <= code && code <= 0x0C44) || // Mc   [4] TELUGU VOWEL SIGN U..TELUGU VOWEL SIGN VOCALIC RR
		(0x0C82 <= code && code <= 0x0C83) || // Mc   [2] KANNADA SIGN ANUSVARA..KANNADA SIGN VISARGA
		0x0CBE == code || // Mc       KANNADA VOWEL SIGN AA
		(0x0CC0 <= code && code <= 0x0CC1) || // Mc   [2] KANNADA VOWEL SIGN II..KANNADA VOWEL SIGN U
		(0x0CC3 <= code && code <= 0x0CC4) || // Mc   [2] KANNADA VOWEL SIGN VOCALIC R..KANNADA VOWEL SIGN VOCALIC RR
		(0x0CC7 <= code && code <= 0x0CC8) || // Mc   [2] KANNADA VOWEL SIGN EE..KANNADA VOWEL SIGN AI
		(0x0CCA <= code && code <= 0x0CCB) || // Mc   [2] KANNADA VOWEL SIGN O..KANNADA VOWEL SIGN OO
		(0x0D02 <= code && code <= 0x0D03) || // Mc   [2] MALAYALAM SIGN ANUSVARA..MALAYALAM SIGN VISARGA
		(0x0D3F <= code && code <= 0x0D40) || // Mc   [2] MALAYALAM VOWEL SIGN I..MALAYALAM VOWEL SIGN II
		(0x0D46 <= code && code <= 0x0D48) || // Mc   [3] MALAYALAM VOWEL SIGN E..MALAYALAM VOWEL SIGN AI
		(0x0D4A <= code && code <= 0x0D4C) || // Mc   [3] MALAYALAM VOWEL SIGN O..MALAYALAM VOWEL SIGN AU
		(0x0D82 <= code && code <= 0x0D83) || // Mc   [2] SINHALA SIGN ANUSVARAYA..SINHALA SIGN VISARGAYA
		(0x0DD0 <= code && code <= 0x0DD1) || // Mc   [2] SINHALA VOWEL SIGN KETTI AEDA-PILLA..SINHALA VOWEL SIGN DIGA AEDA-PILLA
		(0x0DD8 <= code && code <= 0x0DDE) || // Mc   [7] SINHALA VOWEL SIGN GAETTA-PILLA..SINHALA VOWEL SIGN KOMBUVA HAA GAYANUKITTA
		(0x0DF2 <= code && code <= 0x0DF3) || // Mc   [2] SINHALA VOWEL SIGN DIGA GAETTA-PILLA..SINHALA VOWEL SIGN DIGA GAYANUKITTA
		0x0E33 == code || // Lo       THAI CHARACTER SARA AM
		0x0EB3 == code || // Lo       LAO VOWEL SIGN AM
		(0x0F3E <= code && code <= 0x0F3F) || // Mc   [2] TIBETAN SIGN YAR TSHES..TIBETAN SIGN MAR TSHES
		0x0F7F == code || // Mc       TIBETAN SIGN RNAM BCAD
		0x1031 == code || // Mc       MYANMAR VOWEL SIGN E
		(0x103B <= code && code <= 0x103C) || // Mc   [2] MYANMAR CONSONANT SIGN MEDIAL YA..MYANMAR CONSONANT SIGN MEDIAL RA
		(0x1056 <= code && code <= 0x1057) || // Mc   [2] MYANMAR VOWEL SIGN VOCALIC R..MYANMAR VOWEL SIGN VOCALIC RR
		0x1084 == code || // Mc       MYANMAR VOWEL SIGN SHAN E
		0x17B6 == code || // Mc       KHMER VOWEL SIGN AA
		(0x17BE <= code && code <= 0x17C5) || // Mc   [8] KHMER VOWEL SIGN OE..KHMER VOWEL SIGN AU
		(0x17C7 <= code && code <= 0x17C8) || // Mc   [2] KHMER SIGN REAHMUK..KHMER SIGN YUUKALEAPINTU
		(0x1923 <= code && code <= 0x1926) || // Mc   [4] LIMBU VOWEL SIGN EE..LIMBU VOWEL SIGN AU
		(0x1929 <= code && code <= 0x192B) || // Mc   [3] LIMBU SUBJOINED LETTER YA..LIMBU SUBJOINED LETTER WA
		(0x1930 <= code && code <= 0x1931) || // Mc   [2] LIMBU SMALL LETTER KA..LIMBU SMALL LETTER NGA
		(0x1933 <= code && code <= 0x1938) || // Mc   [6] LIMBU SMALL LETTER TA..LIMBU SMALL LETTER LA
		(0x1A19 <= code && code <= 0x1A1A) || // Mc   [2] BUGINESE VOWEL SIGN E..BUGINESE VOWEL SIGN O
		0x1A55 == code || // Mc       TAI THAM CONSONANT SIGN MEDIAL RA
		0x1A57 == code || // Mc       TAI THAM CONSONANT SIGN LA TANG LAI
		(0x1A6D <= code && code <= 0x1A72) || // Mc   [6] TAI THAM VOWEL SIGN OY..TAI THAM VOWEL SIGN THAM AI
		0x1B04 == code || // Mc       BALINESE SIGN BISAH
		0x1B35 == code || // Mc       BALINESE VOWEL SIGN TEDUNG
		0x1B3B == code || // Mc       BALINESE VOWEL SIGN RA REPA TEDUNG
		(0x1B3D <= code && code <= 0x1B41) || // Mc   [5] BALINESE VOWEL SIGN LA LENGA TEDUNG..BALINESE VOWEL SIGN TALING REPA TEDUNG
		(0x1B43 <= code && code <= 0x1B44) || // Mc   [2] BALINESE VOWEL SIGN PEPET TEDUNG..BALINESE ADEG ADEG
		0x1B82 == code || // Mc       SUNDANESE SIGN PANGWISAD
		0x1BA1 == code || // Mc       SUNDANESE CONSONANT SIGN PAMINGKAL
		(0x1BA6 <= code && code <= 0x1BA7) || // Mc   [2] SUNDANESE VOWEL SIGN PANAELAENG..SUNDANESE VOWEL SIGN PANOLONG
		0x1BAA == code || // Mc       SUNDANESE SIGN PAMAAEH
		0x1BE7 == code || // Mc       BATAK VOWEL SIGN E
		(0x1BEA <= code && code <= 0x1BEC) || // Mc   [3] BATAK VOWEL SIGN I..BATAK VOWEL SIGN O
		0x1BEE == code || // Mc       BATAK VOWEL SIGN U
		(0x1BF2 <= code && code <= 0x1BF3) || // Mc   [2] BATAK PANGOLAT..BATAK PANONGONAN
		(0x1C24 <= code && code <= 0x1C2B) || // Mc   [8] LEPCHA SUBJOINED LETTER YA..LEPCHA VOWEL SIGN UU
		(0x1C34 <= code && code <= 0x1C35) || // Mc   [2] LEPCHA CONSONANT SIGN NYIN-DO..LEPCHA CONSONANT SIGN KANG
		0x1CE1 == code || // Mc       VEDIC TONE ATHARVAVEDIC INDEPENDENT SVARITA
		(0x1CF2 <= code && code <= 0x1CF3) || // Mc   [2] VEDIC SIGN ARDHAVISARGA..VEDIC SIGN ROTATED ARDHAVISARGA
		0x1CF7 == code || // Mc       VEDIC SIGN ATIKRAMA
		(0xA823 <= code && code <= 0xA824) || // Mc   [2] SYLOTI NAGRI VOWEL SIGN A..SYLOTI NAGRI VOWEL SIGN I
		0xA827 == code || // Mc       SYLOTI NAGRI VOWEL SIGN OO
		(0xA880 <= code && code <= 0xA881) || // Mc   [2] SAURASHTRA SIGN ANUSVARA..SAURASHTRA SIGN VISARGA
		(0xA8B4 <= code && code <= 0xA8C3) || // Mc  [16] SAURASHTRA CONSONANT SIGN HAARU..SAURASHTRA VOWEL SIGN AU
		(0xA952 <= code && code <= 0xA953) || // Mc   [2] REJANG CONSONANT SIGN H..REJANG VIRAMA
		0xA983 == code || // Mc       JAVANESE SIGN WIGNYAN
		(0xA9B4 <= code && code <= 0xA9B5) || // Mc   [2] JAVANESE VOWEL SIGN TARUNG..JAVANESE VOWEL SIGN TOLONG
		(0xA9BA <= code && code <= 0xA9BB) || // Mc   [2] JAVANESE VOWEL SIGN TALING..JAVANESE VOWEL SIGN DIRGA MURE
		(0xA9BD <= code && code <= 0xA9C0) || // Mc   [4] JAVANESE CONSONANT SIGN KERET..JAVANESE PANGKON
		(0xAA2F <= code && code <= 0xAA30) || // Mc   [2] CHAM VOWEL SIGN O..CHAM VOWEL SIGN AI
		(0xAA33 <= code && code <= 0xAA34) || // Mc   [2] CHAM CONSONANT SIGN YA..CHAM CONSONANT SIGN RA
		0xAA4D == code || // Mc       CHAM CONSONANT SIGN FINAL H
		0xAAEB == code || // Mc       MEETEI MAYEK VOWEL SIGN II
		(0xAAEE <= code && code <= 0xAAEF) || // Mc   [2] MEETEI MAYEK VOWEL SIGN AU..MEETEI MAYEK VOWEL SIGN AAU
		0xAAF5 == code || // Mc       MEETEI MAYEK VOWEL SIGN VISARGA
		(0xABE3 <= code && code <= 0xABE4) || // Mc   [2] MEETEI MAYEK VOWEL SIGN ONAP..MEETEI MAYEK VOWEL SIGN INAP
		(0xABE6 <= code && code <= 0xABE7) || // Mc   [2] MEETEI MAYEK VOWEL SIGN YENAP..MEETEI MAYEK VOWEL SIGN SOUNAP
		(0xABE9 <= code && code <= 0xABEA) || // Mc   [2] MEETEI MAYEK VOWEL SIGN CHEINAP..MEETEI MAYEK VOWEL SIGN NUNG
		0xABEC == code || // Mc       MEETEI MAYEK LUM IYEK
		0x11000 == code || // Mc       BRAHMI SIGN CANDRABINDU
		0x11002 == code || // Mc       BRAHMI SIGN VISARGA
		0x11082 == code || // Mc       KAITHI SIGN VISARGA
		(0x110B0 <= code && code <= 0x110B2) || // Mc   [3] KAITHI VOWEL SIGN AA..KAITHI VOWEL SIGN II
		(0x110B7 <= code && code <= 0x110B8) || // Mc   [2] KAITHI VOWEL SIGN O..KAITHI VOWEL SIGN AU
		0x1112C == code || // Mc       CHAKMA VOWEL SIGN E
		0x11182 == code || // Mc       SHARADA SIGN VISARGA
		(0x111B3 <= code && code <= 0x111B5) || // Mc   [3] SHARADA VOWEL SIGN AA..SHARADA VOWEL SIGN II
		(0x111BF <= code && code <= 0x111C0) || // Mc   [2] SHARADA VOWEL SIGN AU..SHARADA SIGN VIRAMA
		(0x1122C <= code && code <= 0x1122E) || // Mc   [3] KHOJKI VOWEL SIGN AA..KHOJKI VOWEL SIGN II
		(0x11232 <= code && code <= 0x11233) || // Mc   [2] KHOJKI VOWEL SIGN O..KHOJKI VOWEL SIGN AU
		0x11235 == code || // Mc       KHOJKI SIGN VIRAMA
		(0x112E0 <= code && code <= 0x112E2) || // Mc   [3] KHUDAWADI VOWEL SIGN AA..KHUDAWADI VOWEL SIGN II
		(0x11302 <= code && code <= 0x11303) || // Mc   [2] GRANTHA SIGN ANUSVARA..GRANTHA SIGN VISARGA
		0x1133F == code || // Mc       GRANTHA VOWEL SIGN I
		(0x11341 <= code && code <= 0x11344) || // Mc   [4] GRANTHA VOWEL SIGN U..GRANTHA VOWEL SIGN VOCALIC RR
		(0x11347 <= code && code <= 0x11348) || // Mc   [2] GRANTHA VOWEL SIGN EE..GRANTHA VOWEL SIGN AI
		(0x1134B <= code && code <= 0x1134D) || // Mc   [3] GRANTHA VOWEL SIGN OO..GRANTHA SIGN VIRAMA
		(0x11362 <= code && code <= 0x11363) || // Mc   [2] GRANTHA VOWEL SIGN VOCALIC L..GRANTHA VOWEL SIGN VOCALIC LL
		(0x11435 <= code && code <= 0x11437) || // Mc   [3] NEWA VOWEL SIGN AA..NEWA VOWEL SIGN II
		(0x11440 <= code && code <= 0x11441) || // Mc   [2] NEWA VOWEL SIGN O..NEWA VOWEL SIGN AU
		0x11445 == code || // Mc       NEWA SIGN VISARGA
		(0x114B1 <= code && code <= 0x114B2) || // Mc   [2] TIRHUTA VOWEL SIGN I..TIRHUTA VOWEL SIGN II
		0x114B9 == code || // Mc       TIRHUTA VOWEL SIGN E
		(0x114BB <= code && code <= 0x114BC) || // Mc   [2] TIRHUTA VOWEL SIGN AI..TIRHUTA VOWEL SIGN O
		0x114BE == code || // Mc       TIRHUTA VOWEL SIGN AU
		0x114C1 == code || // Mc       TIRHUTA SIGN VISARGA
		(0x115B0 <= code && code <= 0x115B1) || // Mc   [2] SIDDHAM VOWEL SIGN I..SIDDHAM VOWEL SIGN II
		(0x115B8 <= code && code <= 0x115BB) || // Mc   [4] SIDDHAM VOWEL SIGN E..SIDDHAM VOWEL SIGN AU
		0x115BE == code || // Mc       SIDDHAM SIGN VISARGA
		(0x11630 <= code && code <= 0x11632) || // Mc   [3] MODI VOWEL SIGN AA..MODI VOWEL SIGN II
		(0x1163B <= code && code <= 0x1163C) || // Mc   [2] MODI VOWEL SIGN O..MODI VOWEL SIGN AU
		0x1163E == code || // Mc       MODI SIGN VISARGA
		0x116AC == code || // Mc       TAKRI SIGN VISARGA
		(0x116AE <= code && code <= 0x116AF) || // Mc   [2] TAKRI VOWEL SIGN I..TAKRI VOWEL SIGN II
		0x116B6 == code || // Mc       TAKRI SIGN VIRAMA
		(0x11720 <= code && code <= 0x11721) || // Mc   [2] AHOM VOWEL SIGN A..AHOM VOWEL SIGN AA
		0x11726 == code || // Mc       AHOM VOWEL SIGN E
		(0x11A07 <= code && code <= 0x11A08) || // Mc   [2] ZANABAZAR SQUARE VOWEL SIGN AI..ZANABAZAR SQUARE VOWEL SIGN AU
		0x11A39 == code || // Mc       ZANABAZAR SQUARE SIGN VISARGA
		(0x11A57 <= code && code <= 0x11A58) || // Mc   [2] SOYOMBO VOWEL SIGN AI..SOYOMBO VOWEL SIGN AU
		0x11A97 == code || // Mc       SOYOMBO SIGN VISARGA
		0x11C2F == code || // Mc       BHAIKSUKI VOWEL SIGN AA
		0x11C3E == code || // Mc       BHAIKSUKI SIGN VISARGA
		0x11CA9 == code || // Mc       MARCHEN SUBJOINED LETTER YA
		0x11CB1 == code || // Mc       MARCHEN VOWEL SIGN I
		0x11CB4 == code || // Mc       MARCHEN VOWEL SIGN O
		(0x16F51 <= code && code <= 0x16F7E) || // Mc  [46] MIAO SIGN ASPIRATION..MIAO VOWEL SIGN NG
		0x1D166 == code || // Mc       MUSICAL SYMBOL COMBINING SPRECHGESANG STEM
		0x1D16D == code // Mc       MUSICAL SYMBOL COMBINING AUGMENTATION DOT
		){
			return SpacingMark;
		}
		
		
		if(
		(0x1100 <= code && code <= 0x115F) || // Lo  [96] HANGUL CHOSEONG KIYEOK..HANGUL CHOSEONG FILLER
		(0xA960 <= code && code <= 0xA97C) // Lo  [29] HANGUL CHOSEONG TIKEUT-MIEUM..HANGUL CHOSEONG SSANGYEORINHIEUH
		){
			return L;
		}
		
		if(
		(0x1160 <= code && code <= 0x11A7) || // Lo  [72] HANGUL JUNGSEONG FILLER..HANGUL JUNGSEONG O-YAE
		(0xD7B0 <= code && code <= 0xD7C6) // Lo  [23] HANGUL JUNGSEONG O-YEO..HANGUL JUNGSEONG ARAEA-E
		){
			return V;
		}
		
		
		if(
		(0x11A8 <= code && code <= 0x11FF) || // Lo  [88] HANGUL JONGSEONG KIYEOK..HANGUL JONGSEONG SSANGNIEUN
		(0xD7CB <= code && code <= 0xD7FB) // Lo  [49] HANGUL JONGSEONG NIEUN-RIEUL..HANGUL JONGSEONG PHIEUPH-THIEUTH
		){
			return T;
		}
		
		if(
		0xAC00 == code || // Lo       HANGUL SYLLABLE GA
		0xAC1C == code || // Lo       HANGUL SYLLABLE GAE
		0xAC38 == code || // Lo       HANGUL SYLLABLE GYA
		0xAC54 == code || // Lo       HANGUL SYLLABLE GYAE
		0xAC70 == code || // Lo       HANGUL SYLLABLE GEO
		0xAC8C == code || // Lo       HANGUL SYLLABLE GE
		0xACA8 == code || // Lo       HANGUL SYLLABLE GYEO
		0xACC4 == code || // Lo       HANGUL SYLLABLE GYE
		0xACE0 == code || // Lo       HANGUL SYLLABLE GO
		0xACFC == code || // Lo       HANGUL SYLLABLE GWA
		0xAD18 == code || // Lo       HANGUL SYLLABLE GWAE
		0xAD34 == code || // Lo       HANGUL SYLLABLE GOE
		0xAD50 == code || // Lo       HANGUL SYLLABLE GYO
		0xAD6C == code || // Lo       HANGUL SYLLABLE GU
		0xAD88 == code || // Lo       HANGUL SYLLABLE GWEO
		0xADA4 == code || // Lo       HANGUL SYLLABLE GWE
		0xADC0 == code || // Lo       HANGUL SYLLABLE GWI
		0xADDC == code || // Lo       HANGUL SYLLABLE GYU
		0xADF8 == code || // Lo       HANGUL SYLLABLE GEU
		0xAE14 == code || // Lo       HANGUL SYLLABLE GYI
		0xAE30 == code || // Lo       HANGUL SYLLABLE GI
		0xAE4C == code || // Lo       HANGUL SYLLABLE GGA
		0xAE68 == code || // Lo       HANGUL SYLLABLE GGAE
		0xAE84 == code || // Lo       HANGUL SYLLABLE GGYA
		0xAEA0 == code || // Lo       HANGUL SYLLABLE GGYAE
		0xAEBC == code || // Lo       HANGUL SYLLABLE GGEO
		0xAED8 == code || // Lo       HANGUL SYLLABLE GGE
		0xAEF4 == code || // Lo       HANGUL SYLLABLE GGYEO
		0xAF10 == code || // Lo       HANGUL SYLLABLE GGYE
		0xAF2C == code || // Lo       HANGUL SYLLABLE GGO
		0xAF48 == code || // Lo       HANGUL SYLLABLE GGWA
		0xAF64 == code || // Lo       HANGUL SYLLABLE GGWAE
		0xAF80 == code || // Lo       HANGUL SYLLABLE GGOE
		0xAF9C == code || // Lo       HANGUL SYLLABLE GGYO
		0xAFB8 == code || // Lo       HANGUL SYLLABLE GGU
		0xAFD4 == code || // Lo       HANGUL SYLLABLE GGWEO
		0xAFF0 == code || // Lo       HANGUL SYLLABLE GGWE
		0xB00C == code || // Lo       HANGUL SYLLABLE GGWI
		0xB028 == code || // Lo       HANGUL SYLLABLE GGYU
		0xB044 == code || // Lo       HANGUL SYLLABLE GGEU
		0xB060 == code || // Lo       HANGUL SYLLABLE GGYI
		0xB07C == code || // Lo       HANGUL SYLLABLE GGI
		0xB098 == code || // Lo       HANGUL SYLLABLE NA
		0xB0B4 == code || // Lo       HANGUL SYLLABLE NAE
		0xB0D0 == code || // Lo       HANGUL SYLLABLE NYA
		0xB0EC == code || // Lo       HANGUL SYLLABLE NYAE
		0xB108 == code || // Lo       HANGUL SYLLABLE NEO
		0xB124 == code || // Lo       HANGUL SYLLABLE NE
		0xB140 == code || // Lo       HANGUL SYLLABLE NYEO
		0xB15C == code || // Lo       HANGUL SYLLABLE NYE
		0xB178 == code || // Lo       HANGUL SYLLABLE NO
		0xB194 == code || // Lo       HANGUL SYLLABLE NWA
		0xB1B0 == code || // Lo       HANGUL SYLLABLE NWAE
		0xB1CC == code || // Lo       HANGUL SYLLABLE NOE
		0xB1E8 == code || // Lo       HANGUL SYLLABLE NYO
		0xB204 == code || // Lo       HANGUL SYLLABLE NU
		0xB220 == code || // Lo       HANGUL SYLLABLE NWEO
		0xB23C == code || // Lo       HANGUL SYLLABLE NWE
		0xB258 == code || // Lo       HANGUL SYLLABLE NWI
		0xB274 == code || // Lo       HANGUL SYLLABLE NYU
		0xB290 == code || // Lo       HANGUL SYLLABLE NEU
		0xB2AC == code || // Lo       HANGUL SYLLABLE NYI
		0xB2C8 == code || // Lo       HANGUL SYLLABLE NI
		0xB2E4 == code || // Lo       HANGUL SYLLABLE DA
		0xB300 == code || // Lo       HANGUL SYLLABLE DAE
		0xB31C == code || // Lo       HANGUL SYLLABLE DYA
		0xB338 == code || // Lo       HANGUL SYLLABLE DYAE
		0xB354 == code || // Lo       HANGUL SYLLABLE DEO
		0xB370 == code || // Lo       HANGUL SYLLABLE DE
		0xB38C == code || // Lo       HANGUL SYLLABLE DYEO
		0xB3A8 == code || // Lo       HANGUL SYLLABLE DYE
		0xB3C4 == code || // Lo       HANGUL SYLLABLE DO
		0xB3E0 == code || // Lo       HANGUL SYLLABLE DWA
		0xB3FC == code || // Lo       HANGUL SYLLABLE DWAE
		0xB418 == code || // Lo       HANGUL SYLLABLE DOE
		0xB434 == code || // Lo       HANGUL SYLLABLE DYO
		0xB450 == code || // Lo       HANGUL SYLLABLE DU
		0xB46C == code || // Lo       HANGUL SYLLABLE DWEO
		0xB488 == code || // Lo       HANGUL SYLLABLE DWE
		0xB4A4 == code || // Lo       HANGUL SYLLABLE DWI
		0xB4C0 == code || // Lo       HANGUL SYLLABLE DYU
		0xB4DC == code || // Lo       HANGUL SYLLABLE DEU
		0xB4F8 == code || // Lo       HANGUL SYLLABLE DYI
		0xB514 == code || // Lo       HANGUL SYLLABLE DI
		0xB530 == code || // Lo       HANGUL SYLLABLE DDA
		0xB54C == code || // Lo       HANGUL SYLLABLE DDAE
		0xB568 == code || // Lo       HANGUL SYLLABLE DDYA
		0xB584 == code || // Lo       HANGUL SYLLABLE DDYAE
		0xB5A0 == code || // Lo       HANGUL SYLLABLE DDEO
		0xB5BC == code || // Lo       HANGUL SYLLABLE DDE
		0xB5D8 == code || // Lo       HANGUL SYLLABLE DDYEO
		0xB5F4 == code || // Lo       HANGUL SYLLABLE DDYE
		0xB610 == code || // Lo       HANGUL SYLLABLE DDO
		0xB62C == code || // Lo       HANGUL SYLLABLE DDWA
		0xB648 == code || // Lo       HANGUL SYLLABLE DDWAE
		0xB664 == code || // Lo       HANGUL SYLLABLE DDOE
		0xB680 == code || // Lo       HANGUL SYLLABLE DDYO
		0xB69C == code || // Lo       HANGUL SYLLABLE DDU
		0xB6B8 == code || // Lo       HANGUL SYLLABLE DDWEO
		0xB6D4 == code || // Lo       HANGUL SYLLABLE DDWE
		0xB6F0 == code || // Lo       HANGUL SYLLABLE DDWI
		0xB70C == code || // Lo       HANGUL SYLLABLE DDYU
		0xB728 == code || // Lo       HANGUL SYLLABLE DDEU
		0xB744 == code || // Lo       HANGUL SYLLABLE DDYI
		0xB760 == code || // Lo       HANGUL SYLLABLE DDI
		0xB77C == code || // Lo       HANGUL SYLLABLE RA
		0xB798 == code || // Lo       HANGUL SYLLABLE RAE
		0xB7B4 == code || // Lo       HANGUL SYLLABLE RYA
		0xB7D0 == code || // Lo       HANGUL SYLLABLE RYAE
		0xB7EC == code || // Lo       HANGUL SYLLABLE REO
		0xB808 == code || // Lo       HANGUL SYLLABLE RE
		0xB824 == code || // Lo       HANGUL SYLLABLE RYEO
		0xB840 == code || // Lo       HANGUL SYLLABLE RYE
		0xB85C == code || // Lo       HANGUL SYLLABLE RO
		0xB878 == code || // Lo       HANGUL SYLLABLE RWA
		0xB894 == code || // Lo       HANGUL SYLLABLE RWAE
		0xB8B0 == code || // Lo       HANGUL SYLLABLE ROE
		0xB8CC == code || // Lo       HANGUL SYLLABLE RYO
		0xB8E8 == code || // Lo       HANGUL SYLLABLE RU
		0xB904 == code || // Lo       HANGUL SYLLABLE RWEO
		0xB920 == code || // Lo       HANGUL SYLLABLE RWE
		0xB93C == code || // Lo       HANGUL SYLLABLE RWI
		0xB958 == code || // Lo       HANGUL SYLLABLE RYU
		0xB974 == code || // Lo       HANGUL SYLLABLE REU
		0xB990 == code || // Lo       HANGUL SYLLABLE RYI
		0xB9AC == code || // Lo       HANGUL SYLLABLE RI
		0xB9C8 == code || // Lo       HANGUL SYLLABLE MA
		0xB9E4 == code || // Lo       HANGUL SYLLABLE MAE
		0xBA00 == code || // Lo       HANGUL SYLLABLE MYA
		0xBA1C == code || // Lo       HANGUL SYLLABLE MYAE
		0xBA38 == code || // Lo       HANGUL SYLLABLE MEO
		0xBA54 == code || // Lo       HANGUL SYLLABLE ME
		0xBA70 == code || // Lo       HANGUL SYLLABLE MYEO
		0xBA8C == code || // Lo       HANGUL SYLLABLE MYE
		0xBAA8 == code || // Lo       HANGUL SYLLABLE MO
		0xBAC4 == code || // Lo       HANGUL SYLLABLE MWA
		0xBAE0 == code || // Lo       HANGUL SYLLABLE MWAE
		0xBAFC == code || // Lo       HANGUL SYLLABLE MOE
		0xBB18 == code || // Lo       HANGUL SYLLABLE MYO
		0xBB34 == code || // Lo       HANGUL SYLLABLE MU
		0xBB50 == code || // Lo       HANGUL SYLLABLE MWEO
		0xBB6C == code || // Lo       HANGUL SYLLABLE MWE
		0xBB88 == code || // Lo       HANGUL SYLLABLE MWI
		0xBBA4 == code || // Lo       HANGUL SYLLABLE MYU
		0xBBC0 == code || // Lo       HANGUL SYLLABLE MEU
		0xBBDC == code || // Lo       HANGUL SYLLABLE MYI
		0xBBF8 == code || // Lo       HANGUL SYLLABLE MI
		0xBC14 == code || // Lo       HANGUL SYLLABLE BA
		0xBC30 == code || // Lo       HANGUL SYLLABLE BAE
		0xBC4C == code || // Lo       HANGUL SYLLABLE BYA
		0xBC68 == code || // Lo       HANGUL SYLLABLE BYAE
		0xBC84 == code || // Lo       HANGUL SYLLABLE BEO
		0xBCA0 == code || // Lo       HANGUL SYLLABLE BE
		0xBCBC == code || // Lo       HANGUL SYLLABLE BYEO
		0xBCD8 == code || // Lo       HANGUL SYLLABLE BYE
		0xBCF4 == code || // Lo       HANGUL SYLLABLE BO
		0xBD10 == code || // Lo       HANGUL SYLLABLE BWA
		0xBD2C == code || // Lo       HANGUL SYLLABLE BWAE
		0xBD48 == code || // Lo       HANGUL SYLLABLE BOE
		0xBD64 == code || // Lo       HANGUL SYLLABLE BYO
		0xBD80 == code || // Lo       HANGUL SYLLABLE BU
		0xBD9C == code || // Lo       HANGUL SYLLABLE BWEO
		0xBDB8 == code || // Lo       HANGUL SYLLABLE BWE
		0xBDD4 == code || // Lo       HANGUL SYLLABLE BWI
		0xBDF0 == code || // Lo       HANGUL SYLLABLE BYU
		0xBE0C == code || // Lo       HANGUL SYLLABLE BEU
		0xBE28 == code || // Lo       HANGUL SYLLABLE BYI
		0xBE44 == code || // Lo       HANGUL SYLLABLE BI
		0xBE60 == code || // Lo       HANGUL SYLLABLE BBA
		0xBE7C == code || // Lo       HANGUL SYLLABLE BBAE
		0xBE98 == code || // Lo       HANGUL SYLLABLE BBYA
		0xBEB4 == code || // Lo       HANGUL SYLLABLE BBYAE
		0xBED0 == code || // Lo       HANGUL SYLLABLE BBEO
		0xBEEC == code || // Lo       HANGUL SYLLABLE BBE
		0xBF08 == code || // Lo       HANGUL SYLLABLE BBYEO
		0xBF24 == code || // Lo       HANGUL SYLLABLE BBYE
		0xBF40 == code || // Lo       HANGUL SYLLABLE BBO
		0xBF5C == code || // Lo       HANGUL SYLLABLE BBWA
		0xBF78 == code || // Lo       HANGUL SYLLABLE BBWAE
		0xBF94 == code || // Lo       HANGUL SYLLABLE BBOE
		0xBFB0 == code || // Lo       HANGUL SYLLABLE BBYO
		0xBFCC == code || // Lo       HANGUL SYLLABLE BBU
		0xBFE8 == code || // Lo       HANGUL SYLLABLE BBWEO
		0xC004 == code || // Lo       HANGUL SYLLABLE BBWE
		0xC020 == code || // Lo       HANGUL SYLLABLE BBWI
		0xC03C == code || // Lo       HANGUL SYLLABLE BBYU
		0xC058 == code || // Lo       HANGUL SYLLABLE BBEU
		0xC074 == code || // Lo       HANGUL SYLLABLE BBYI
		0xC090 == code || // Lo       HANGUL SYLLABLE BBI
		0xC0AC == code || // Lo       HANGUL SYLLABLE SA
		0xC0C8 == code || // Lo       HANGUL SYLLABLE SAE
		0xC0E4 == code || // Lo       HANGUL SYLLABLE SYA
		0xC100 == code || // Lo       HANGUL SYLLABLE SYAE
		0xC11C == code || // Lo       HANGUL SYLLABLE SEO
		0xC138 == code || // Lo       HANGUL SYLLABLE SE
		0xC154 == code || // Lo       HANGUL SYLLABLE SYEO
		0xC170 == code || // Lo       HANGUL SYLLABLE SYE
		0xC18C == code || // Lo       HANGUL SYLLABLE SO
		0xC1A8 == code || // Lo       HANGUL SYLLABLE SWA
		0xC1C4 == code || // Lo       HANGUL SYLLABLE SWAE
		0xC1E0 == code || // Lo       HANGUL SYLLABLE SOE
		0xC1FC == code || // Lo       HANGUL SYLLABLE SYO
		0xC218 == code || // Lo       HANGUL SYLLABLE SU
		0xC234 == code || // Lo       HANGUL SYLLABLE SWEO
		0xC250 == code || // Lo       HANGUL SYLLABLE SWE
		0xC26C == code || // Lo       HANGUL SYLLABLE SWI
		0xC288 == code || // Lo       HANGUL SYLLABLE SYU
		0xC2A4 == code || // Lo       HANGUL SYLLABLE SEU
		0xC2C0 == code || // Lo       HANGUL SYLLABLE SYI
		0xC2DC == code || // Lo       HANGUL SYLLABLE SI
		0xC2F8 == code || // Lo       HANGUL SYLLABLE SSA
		0xC314 == code || // Lo       HANGUL SYLLABLE SSAE
		0xC330 == code || // Lo       HANGUL SYLLABLE SSYA
		0xC34C == code || // Lo       HANGUL SYLLABLE SSYAE
		0xC368 == code || // Lo       HANGUL SYLLABLE SSEO
		0xC384 == code || // Lo       HANGUL SYLLABLE SSE
		0xC3A0 == code || // Lo       HANGUL SYLLABLE SSYEO
		0xC3BC == code || // Lo       HANGUL SYLLABLE SSYE
		0xC3D8 == code || // Lo       HANGUL SYLLABLE SSO
		0xC3F4 == code || // Lo       HANGUL SYLLABLE SSWA
		0xC410 == code || // Lo       HANGUL SYLLABLE SSWAE
		0xC42C == code || // Lo       HANGUL SYLLABLE SSOE
		0xC448 == code || // Lo       HANGUL SYLLABLE SSYO
		0xC464 == code || // Lo       HANGUL SYLLABLE SSU
		0xC480 == code || // Lo       HANGUL SYLLABLE SSWEO
		0xC49C == code || // Lo       HANGUL SYLLABLE SSWE
		0xC4B8 == code || // Lo       HANGUL SYLLABLE SSWI
		0xC4D4 == code || // Lo       HANGUL SYLLABLE SSYU
		0xC4F0 == code || // Lo       HANGUL SYLLABLE SSEU
		0xC50C == code || // Lo       HANGUL SYLLABLE SSYI
		0xC528 == code || // Lo       HANGUL SYLLABLE SSI
		0xC544 == code || // Lo       HANGUL SYLLABLE A
		0xC560 == code || // Lo       HANGUL SYLLABLE AE
		0xC57C == code || // Lo       HANGUL SYLLABLE YA
		0xC598 == code || // Lo       HANGUL SYLLABLE YAE
		0xC5B4 == code || // Lo       HANGUL SYLLABLE EO
		0xC5D0 == code || // Lo       HANGUL SYLLABLE E
		0xC5EC == code || // Lo       HANGUL SYLLABLE YEO
		0xC608 == code || // Lo       HANGUL SYLLABLE YE
		0xC624 == code || // Lo       HANGUL SYLLABLE O
		0xC640 == code || // Lo       HANGUL SYLLABLE WA
		0xC65C == code || // Lo       HANGUL SYLLABLE WAE
		0xC678 == code || // Lo       HANGUL SYLLABLE OE
		0xC694 == code || // Lo       HANGUL SYLLABLE YO
		0xC6B0 == code || // Lo       HANGUL SYLLABLE U
		0xC6CC == code || // Lo       HANGUL SYLLABLE WEO
		0xC6E8 == code || // Lo       HANGUL SYLLABLE WE
		0xC704 == code || // Lo       HANGUL SYLLABLE WI
		0xC720 == code || // Lo       HANGUL SYLLABLE YU
		0xC73C == code || // Lo       HANGUL SYLLABLE EU
		0xC758 == code || // Lo       HANGUL SYLLABLE YI
		0xC774 == code || // Lo       HANGUL SYLLABLE I
		0xC790 == code || // Lo       HANGUL SYLLABLE JA
		0xC7AC == code || // Lo       HANGUL SYLLABLE JAE
		0xC7C8 == code || // Lo       HANGUL SYLLABLE JYA
		0xC7E4 == code || // Lo       HANGUL SYLLABLE JYAE
		0xC800 == code || // Lo       HANGUL SYLLABLE JEO
		0xC81C == code || // Lo       HANGUL SYLLABLE JE
		0xC838 == code || // Lo       HANGUL SYLLABLE JYEO
		0xC854 == code || // Lo       HANGUL SYLLABLE JYE
		0xC870 == code || // Lo       HANGUL SYLLABLE JO
		0xC88C == code || // Lo       HANGUL SYLLABLE JWA
		0xC8A8 == code || // Lo       HANGUL SYLLABLE JWAE
		0xC8C4 == code || // Lo       HANGUL SYLLABLE JOE
		0xC8E0 == code || // Lo       HANGUL SYLLABLE JYO
		0xC8FC == code || // Lo       HANGUL SYLLABLE JU
		0xC918 == code || // Lo       HANGUL SYLLABLE JWEO
		0xC934 == code || // Lo       HANGUL SYLLABLE JWE
		0xC950 == code || // Lo       HANGUL SYLLABLE JWI
		0xC96C == code || // Lo       HANGUL SYLLABLE JYU
		0xC988 == code || // Lo       HANGUL SYLLABLE JEU
		0xC9A4 == code || // Lo       HANGUL SYLLABLE JYI
		0xC9C0 == code || // Lo       HANGUL SYLLABLE JI
		0xC9DC == code || // Lo       HANGUL SYLLABLE JJA
		0xC9F8 == code || // Lo       HANGUL SYLLABLE JJAE
		0xCA14 == code || // Lo       HANGUL SYLLABLE JJYA
		0xCA30 == code || // Lo       HANGUL SYLLABLE JJYAE
		0xCA4C == code || // Lo       HANGUL SYLLABLE JJEO
		0xCA68 == code || // Lo       HANGUL SYLLABLE JJE
		0xCA84 == code || // Lo       HANGUL SYLLABLE JJYEO
		0xCAA0 == code || // Lo       HANGUL SYLLABLE JJYE
		0xCABC == code || // Lo       HANGUL SYLLABLE JJO
		0xCAD8 == code || // Lo       HANGUL SYLLABLE JJWA
		0xCAF4 == code || // Lo       HANGUL SYLLABLE JJWAE
		0xCB10 == code || // Lo       HANGUL SYLLABLE JJOE
		0xCB2C == code || // Lo       HANGUL SYLLABLE JJYO
		0xCB48 == code || // Lo       HANGUL SYLLABLE JJU
		0xCB64 == code || // Lo       HANGUL SYLLABLE JJWEO
		0xCB80 == code || // Lo       HANGUL SYLLABLE JJWE
		0xCB9C == code || // Lo       HANGUL SYLLABLE JJWI
		0xCBB8 == code || // Lo       HANGUL SYLLABLE JJYU
		0xCBD4 == code || // Lo       HANGUL SYLLABLE JJEU
		0xCBF0 == code || // Lo       HANGUL SYLLABLE JJYI
		0xCC0C == code || // Lo       HANGUL SYLLABLE JJI
		0xCC28 == code || // Lo       HANGUL SYLLABLE CA
		0xCC44 == code || // Lo       HANGUL SYLLABLE CAE
		0xCC60 == code || // Lo       HANGUL SYLLABLE CYA
		0xCC7C == code || // Lo       HANGUL SYLLABLE CYAE
		0xCC98 == code || // Lo       HANGUL SYLLABLE CEO
		0xCCB4 == code || // Lo       HANGUL SYLLABLE CE
		0xCCD0 == code || // Lo       HANGUL SYLLABLE CYEO
		0xCCEC == code || // Lo       HANGUL SYLLABLE CYE
		0xCD08 == code || // Lo       HANGUL SYLLABLE CO
		0xCD24 == code || // Lo       HANGUL SYLLABLE CWA
		0xCD40 == code || // Lo       HANGUL SYLLABLE CWAE
		0xCD5C == code || // Lo       HANGUL SYLLABLE COE
		0xCD78 == code || // Lo       HANGUL SYLLABLE CYO
		0xCD94 == code || // Lo       HANGUL SYLLABLE CU
		0xCDB0 == code || // Lo       HANGUL SYLLABLE CWEO
		0xCDCC == code || // Lo       HANGUL SYLLABLE CWE
		0xCDE8 == code || // Lo       HANGUL SYLLABLE CWI
		0xCE04 == code || // Lo       HANGUL SYLLABLE CYU
		0xCE20 == code || // Lo       HANGUL SYLLABLE CEU
		0xCE3C == code || // Lo       HANGUL SYLLABLE CYI
		0xCE58 == code || // Lo       HANGUL SYLLABLE CI
		0xCE74 == code || // Lo       HANGUL SYLLABLE KA
		0xCE90 == code || // Lo       HANGUL SYLLABLE KAE
		0xCEAC == code || // Lo       HANGUL SYLLABLE KYA
		0xCEC8 == code || // Lo       HANGUL SYLLABLE KYAE
		0xCEE4 == code || // Lo       HANGUL SYLLABLE KEO
		0xCF00 == code || // Lo       HANGUL SYLLABLE KE
		0xCF1C == code || // Lo       HANGUL SYLLABLE KYEO
		0xCF38 == code || // Lo       HANGUL SYLLABLE KYE
		0xCF54 == code || // Lo       HANGUL SYLLABLE KO
		0xCF70 == code || // Lo       HANGUL SYLLABLE KWA
		0xCF8C == code || // Lo       HANGUL SYLLABLE KWAE
		0xCFA8 == code || // Lo       HANGUL SYLLABLE KOE
		0xCFC4 == code || // Lo       HANGUL SYLLABLE KYO
		0xCFE0 == code || // Lo       HANGUL SYLLABLE KU
		0xCFFC == code || // Lo       HANGUL SYLLABLE KWEO
		0xD018 == code || // Lo       HANGUL SYLLABLE KWE
		0xD034 == code || // Lo       HANGUL SYLLABLE KWI
		0xD050 == code || // Lo       HANGUL SYLLABLE KYU
		0xD06C == code || // Lo       HANGUL SYLLABLE KEU
		0xD088 == code || // Lo       HANGUL SYLLABLE KYI
		0xD0A4 == code || // Lo       HANGUL SYLLABLE KI
		0xD0C0 == code || // Lo       HANGUL SYLLABLE TA
		0xD0DC == code || // Lo       HANGUL SYLLABLE TAE
		0xD0F8 == code || // Lo       HANGUL SYLLABLE TYA
		0xD114 == code || // Lo       HANGUL SYLLABLE TYAE
		0xD130 == code || // Lo       HANGUL SYLLABLE TEO
		0xD14C == code || // Lo       HANGUL SYLLABLE TE
		0xD168 == code || // Lo       HANGUL SYLLABLE TYEO
		0xD184 == code || // Lo       HANGUL SYLLABLE TYE
		0xD1A0 == code || // Lo       HANGUL SYLLABLE TO
		0xD1BC == code || // Lo       HANGUL SYLLABLE TWA
		0xD1D8 == code || // Lo       HANGUL SYLLABLE TWAE
		0xD1F4 == code || // Lo       HANGUL SYLLABLE TOE
		0xD210 == code || // Lo       HANGUL SYLLABLE TYO
		0xD22C == code || // Lo       HANGUL SYLLABLE TU
		0xD248 == code || // Lo       HANGUL SYLLABLE TWEO
		0xD264 == code || // Lo       HANGUL SYLLABLE TWE
		0xD280 == code || // Lo       HANGUL SYLLABLE TWI
		0xD29C == code || // Lo       HANGUL SYLLABLE TYU
		0xD2B8 == code || // Lo       HANGUL SYLLABLE TEU
		0xD2D4 == code || // Lo       HANGUL SYLLABLE TYI
		0xD2F0 == code || // Lo       HANGUL SYLLABLE TI
		0xD30C == code || // Lo       HANGUL SYLLABLE PA
		0xD328 == code || // Lo       HANGUL SYLLABLE PAE
		0xD344 == code || // Lo       HANGUL SYLLABLE PYA
		0xD360 == code || // Lo       HANGUL SYLLABLE PYAE
		0xD37C == code || // Lo       HANGUL SYLLABLE PEO
		0xD398 == code || // Lo       HANGUL SYLLABLE PE
		0xD3B4 == code || // Lo       HANGUL SYLLABLE PYEO
		0xD3D0 == code || // Lo       HANGUL SYLLABLE PYE
		0xD3EC == code || // Lo       HANGUL SYLLABLE PO
		0xD408 == code || // Lo       HANGUL SYLLABLE PWA
		0xD424 == code || // Lo       HANGUL SYLLABLE PWAE
		0xD440 == code || // Lo       HANGUL SYLLABLE POE
		0xD45C == code || // Lo       HANGUL SYLLABLE PYO
		0xD478 == code || // Lo       HANGUL SYLLABLE PU
		0xD494 == code || // Lo       HANGUL SYLLABLE PWEO
		0xD4B0 == code || // Lo       HANGUL SYLLABLE PWE
		0xD4CC == code || // Lo       HANGUL SYLLABLE PWI
		0xD4E8 == code || // Lo       HANGUL SYLLABLE PYU
		0xD504 == code || // Lo       HANGUL SYLLABLE PEU
		0xD520 == code || // Lo       HANGUL SYLLABLE PYI
		0xD53C == code || // Lo       HANGUL SYLLABLE PI
		0xD558 == code || // Lo       HANGUL SYLLABLE HA
		0xD574 == code || // Lo       HANGUL SYLLABLE HAE
		0xD590 == code || // Lo       HANGUL SYLLABLE HYA
		0xD5AC == code || // Lo       HANGUL SYLLABLE HYAE
		0xD5C8 == code || // Lo       HANGUL SYLLABLE HEO
		0xD5E4 == code || // Lo       HANGUL SYLLABLE HE
		0xD600 == code || // Lo       HANGUL SYLLABLE HYEO
		0xD61C == code || // Lo       HANGUL SYLLABLE HYE
		0xD638 == code || // Lo       HANGUL SYLLABLE HO
		0xD654 == code || // Lo       HANGUL SYLLABLE HWA
		0xD670 == code || // Lo       HANGUL SYLLABLE HWAE
		0xD68C == code || // Lo       HANGUL SYLLABLE HOE
		0xD6A8 == code || // Lo       HANGUL SYLLABLE HYO
		0xD6C4 == code || // Lo       HANGUL SYLLABLE HU
		0xD6E0 == code || // Lo       HANGUL SYLLABLE HWEO
		0xD6FC == code || // Lo       HANGUL SYLLABLE HWE
		0xD718 == code || // Lo       HANGUL SYLLABLE HWI
		0xD734 == code || // Lo       HANGUL SYLLABLE HYU
		0xD750 == code || // Lo       HANGUL SYLLABLE HEU
		0xD76C == code || // Lo       HANGUL SYLLABLE HYI
		0xD788 == code // Lo       HANGUL SYLLABLE HI
		){
			return LV;
		}
		
		if(
		(0xAC01 <= code && code <= 0xAC1B) || // Lo  [27] HANGUL SYLLABLE GAG..HANGUL SYLLABLE GAH
		(0xAC1D <= code && code <= 0xAC37) || // Lo  [27] HANGUL SYLLABLE GAEG..HANGUL SYLLABLE GAEH
		(0xAC39 <= code && code <= 0xAC53) || // Lo  [27] HANGUL SYLLABLE GYAG..HANGUL SYLLABLE GYAH
		(0xAC55 <= code && code <= 0xAC6F) || // Lo  [27] HANGUL SYLLABLE GYAEG..HANGUL SYLLABLE GYAEH
		(0xAC71 <= code && code <= 0xAC8B) || // Lo  [27] HANGUL SYLLABLE GEOG..HANGUL SYLLABLE GEOH
		(0xAC8D <= code && code <= 0xACA7) || // Lo  [27] HANGUL SYLLABLE GEG..HANGUL SYLLABLE GEH
		(0xACA9 <= code && code <= 0xACC3) || // Lo  [27] HANGUL SYLLABLE GYEOG..HANGUL SYLLABLE GYEOH
		(0xACC5 <= code && code <= 0xACDF) || // Lo  [27] HANGUL SYLLABLE GYEG..HANGUL SYLLABLE GYEH
		(0xACE1 <= code && code <= 0xACFB) || // Lo  [27] HANGUL SYLLABLE GOG..HANGUL SYLLABLE GOH
		(0xACFD <= code && code <= 0xAD17) || // Lo  [27] HANGUL SYLLABLE GWAG..HANGUL SYLLABLE GWAH
		(0xAD19 <= code && code <= 0xAD33) || // Lo  [27] HANGUL SYLLABLE GWAEG..HANGUL SYLLABLE GWAEH
		(0xAD35 <= code && code <= 0xAD4F) || // Lo  [27] HANGUL SYLLABLE GOEG..HANGUL SYLLABLE GOEH
		(0xAD51 <= code && code <= 0xAD6B) || // Lo  [27] HANGUL SYLLABLE GYOG..HANGUL SYLLABLE GYOH
		(0xAD6D <= code && code <= 0xAD87) || // Lo  [27] HANGUL SYLLABLE GUG..HANGUL SYLLABLE GUH
		(0xAD89 <= code && code <= 0xADA3) || // Lo  [27] HANGUL SYLLABLE GWEOG..HANGUL SYLLABLE GWEOH
		(0xADA5 <= code && code <= 0xADBF) || // Lo  [27] HANGUL SYLLABLE GWEG..HANGUL SYLLABLE GWEH
		(0xADC1 <= code && code <= 0xADDB) || // Lo  [27] HANGUL SYLLABLE GWIG..HANGUL SYLLABLE GWIH
		(0xADDD <= code && code <= 0xADF7) || // Lo  [27] HANGUL SYLLABLE GYUG..HANGUL SYLLABLE GYUH
		(0xADF9 <= code && code <= 0xAE13) || // Lo  [27] HANGUL SYLLABLE GEUG..HANGUL SYLLABLE GEUH
		(0xAE15 <= code && code <= 0xAE2F) || // Lo  [27] HANGUL SYLLABLE GYIG..HANGUL SYLLABLE GYIH
		(0xAE31 <= code && code <= 0xAE4B) || // Lo  [27] HANGUL SYLLABLE GIG..HANGUL SYLLABLE GIH
		(0xAE4D <= code && code <= 0xAE67) || // Lo  [27] HANGUL SYLLABLE GGAG..HANGUL SYLLABLE GGAH
		(0xAE69 <= code && code <= 0xAE83) || // Lo  [27] HANGUL SYLLABLE GGAEG..HANGUL SYLLABLE GGAEH
		(0xAE85 <= code && code <= 0xAE9F) || // Lo  [27] HANGUL SYLLABLE GGYAG..HANGUL SYLLABLE GGYAH
		(0xAEA1 <= code && code <= 0xAEBB) || // Lo  [27] HANGUL SYLLABLE GGYAEG..HANGUL SYLLABLE GGYAEH
		(0xAEBD <= code && code <= 0xAED7) || // Lo  [27] HANGUL SYLLABLE GGEOG..HANGUL SYLLABLE GGEOH
		(0xAED9 <= code && code <= 0xAEF3) || // Lo  [27] HANGUL SYLLABLE GGEG..HANGUL SYLLABLE GGEH
		(0xAEF5 <= code && code <= 0xAF0F) || // Lo  [27] HANGUL SYLLABLE GGYEOG..HANGUL SYLLABLE GGYEOH
		(0xAF11 <= code && code <= 0xAF2B) || // Lo  [27] HANGUL SYLLABLE GGYEG..HANGUL SYLLABLE GGYEH
		(0xAF2D <= code && code <= 0xAF47) || // Lo  [27] HANGUL SYLLABLE GGOG..HANGUL SYLLABLE GGOH
		(0xAF49 <= code && code <= 0xAF63) || // Lo  [27] HANGUL SYLLABLE GGWAG..HANGUL SYLLABLE GGWAH
		(0xAF65 <= code && code <= 0xAF7F) || // Lo  [27] HANGUL SYLLABLE GGWAEG..HANGUL SYLLABLE GGWAEH
		(0xAF81 <= code && code <= 0xAF9B) || // Lo  [27] HANGUL SYLLABLE GGOEG..HANGUL SYLLABLE GGOEH
		(0xAF9D <= code && code <= 0xAFB7) || // Lo  [27] HANGUL SYLLABLE GGYOG..HANGUL SYLLABLE GGYOH
		(0xAFB9 <= code && code <= 0xAFD3) || // Lo  [27] HANGUL SYLLABLE GGUG..HANGUL SYLLABLE GGUH
		(0xAFD5 <= code && code <= 0xAFEF) || // Lo  [27] HANGUL SYLLABLE GGWEOG..HANGUL SYLLABLE GGWEOH
		(0xAFF1 <= code && code <= 0xB00B) || // Lo  [27] HANGUL SYLLABLE GGWEG..HANGUL SYLLABLE GGWEH
		(0xB00D <= code && code <= 0xB027) || // Lo  [27] HANGUL SYLLABLE GGWIG..HANGUL SYLLABLE GGWIH
		(0xB029 <= code && code <= 0xB043) || // Lo  [27] HANGUL SYLLABLE GGYUG..HANGUL SYLLABLE GGYUH
		(0xB045 <= code && code <= 0xB05F) || // Lo  [27] HANGUL SYLLABLE GGEUG..HANGUL SYLLABLE GGEUH
		(0xB061 <= code && code <= 0xB07B) || // Lo  [27] HANGUL SYLLABLE GGYIG..HANGUL SYLLABLE GGYIH
		(0xB07D <= code && code <= 0xB097) || // Lo  [27] HANGUL SYLLABLE GGIG..HANGUL SYLLABLE GGIH
		(0xB099 <= code && code <= 0xB0B3) || // Lo  [27] HANGUL SYLLABLE NAG..HANGUL SYLLABLE NAH
		(0xB0B5 <= code && code <= 0xB0CF) || // Lo  [27] HANGUL SYLLABLE NAEG..HANGUL SYLLABLE NAEH
		(0xB0D1 <= code && code <= 0xB0EB) || // Lo  [27] HANGUL SYLLABLE NYAG..HANGUL SYLLABLE NYAH
		(0xB0ED <= code && code <= 0xB107) || // Lo  [27] HANGUL SYLLABLE NYAEG..HANGUL SYLLABLE NYAEH
		(0xB109 <= code && code <= 0xB123) || // Lo  [27] HANGUL SYLLABLE NEOG..HANGUL SYLLABLE NEOH
		(0xB125 <= code && code <= 0xB13F) || // Lo  [27] HANGUL SYLLABLE NEG..HANGUL SYLLABLE NEH
		(0xB141 <= code && code <= 0xB15B) || // Lo  [27] HANGUL SYLLABLE NYEOG..HANGUL SYLLABLE NYEOH
		(0xB15D <= code && code <= 0xB177) || // Lo  [27] HANGUL SYLLABLE NYEG..HANGUL SYLLABLE NYEH
		(0xB179 <= code && code <= 0xB193) || // Lo  [27] HANGUL SYLLABLE NOG..HANGUL SYLLABLE NOH
		(0xB195 <= code && code <= 0xB1AF) || // Lo  [27] HANGUL SYLLABLE NWAG..HANGUL SYLLABLE NWAH
		(0xB1B1 <= code && code <= 0xB1CB) || // Lo  [27] HANGUL SYLLABLE NWAEG..HANGUL SYLLABLE NWAEH
		(0xB1CD <= code && code <= 0xB1E7) || // Lo  [27] HANGUL SYLLABLE NOEG..HANGUL SYLLABLE NOEH
		(0xB1E9 <= code && code <= 0xB203) || // Lo  [27] HANGUL SYLLABLE NYOG..HANGUL SYLLABLE NYOH
		(0xB205 <= code && code <= 0xB21F) || // Lo  [27] HANGUL SYLLABLE NUG..HANGUL SYLLABLE NUH
		(0xB221 <= code && code <= 0xB23B) || // Lo  [27] HANGUL SYLLABLE NWEOG..HANGUL SYLLABLE NWEOH
		(0xB23D <= code && code <= 0xB257) || // Lo  [27] HANGUL SYLLABLE NWEG..HANGUL SYLLABLE NWEH
		(0xB259 <= code && code <= 0xB273) || // Lo  [27] HANGUL SYLLABLE NWIG..HANGUL SYLLABLE NWIH
		(0xB275 <= code && code <= 0xB28F) || // Lo  [27] HANGUL SYLLABLE NYUG..HANGUL SYLLABLE NYUH
		(0xB291 <= code && code <= 0xB2AB) || // Lo  [27] HANGUL SYLLABLE NEUG..HANGUL SYLLABLE NEUH
		(0xB2AD <= code && code <= 0xB2C7) || // Lo  [27] HANGUL SYLLABLE NYIG..HANGUL SYLLABLE NYIH
		(0xB2C9 <= code && code <= 0xB2E3) || // Lo  [27] HANGUL SYLLABLE NIG..HANGUL SYLLABLE NIH
		(0xB2E5 <= code && code <= 0xB2FF) || // Lo  [27] HANGUL SYLLABLE DAG..HANGUL SYLLABLE DAH
		(0xB301 <= code && code <= 0xB31B) || // Lo  [27] HANGUL SYLLABLE DAEG..HANGUL SYLLABLE DAEH
		(0xB31D <= code && code <= 0xB337) || // Lo  [27] HANGUL SYLLABLE DYAG..HANGUL SYLLABLE DYAH
		(0xB339 <= code && code <= 0xB353) || // Lo  [27] HANGUL SYLLABLE DYAEG..HANGUL SYLLABLE DYAEH
		(0xB355 <= code && code <= 0xB36F) || // Lo  [27] HANGUL SYLLABLE DEOG..HANGUL SYLLABLE DEOH
		(0xB371 <= code && code <= 0xB38B) || // Lo  [27] HANGUL SYLLABLE DEG..HANGUL SYLLABLE DEH
		(0xB38D <= code && code <= 0xB3A7) || // Lo  [27] HANGUL SYLLABLE DYEOG..HANGUL SYLLABLE DYEOH
		(0xB3A9 <= code && code <= 0xB3C3) || // Lo  [27] HANGUL SYLLABLE DYEG..HANGUL SYLLABLE DYEH
		(0xB3C5 <= code && code <= 0xB3DF) || // Lo  [27] HANGUL SYLLABLE DOG..HANGUL SYLLABLE DOH
		(0xB3E1 <= code && code <= 0xB3FB) || // Lo  [27] HANGUL SYLLABLE DWAG..HANGUL SYLLABLE DWAH
		(0xB3FD <= code && code <= 0xB417) || // Lo  [27] HANGUL SYLLABLE DWAEG..HANGUL SYLLABLE DWAEH
		(0xB419 <= code && code <= 0xB433) || // Lo  [27] HANGUL SYLLABLE DOEG..HANGUL SYLLABLE DOEH
		(0xB435 <= code && code <= 0xB44F) || // Lo  [27] HANGUL SYLLABLE DYOG..HANGUL SYLLABLE DYOH
		(0xB451 <= code && code <= 0xB46B) || // Lo  [27] HANGUL SYLLABLE DUG..HANGUL SYLLABLE DUH
		(0xB46D <= code && code <= 0xB487) || // Lo  [27] HANGUL SYLLABLE DWEOG..HANGUL SYLLABLE DWEOH
		(0xB489 <= code && code <= 0xB4A3) || // Lo  [27] HANGUL SYLLABLE DWEG..HANGUL SYLLABLE DWEH
		(0xB4A5 <= code && code <= 0xB4BF) || // Lo  [27] HANGUL SYLLABLE DWIG..HANGUL SYLLABLE DWIH
		(0xB4C1 <= code && code <= 0xB4DB) || // Lo  [27] HANGUL SYLLABLE DYUG..HANGUL SYLLABLE DYUH
		(0xB4DD <= code && code <= 0xB4F7) || // Lo  [27] HANGUL SYLLABLE DEUG..HANGUL SYLLABLE DEUH
		(0xB4F9 <= code && code <= 0xB513) || // Lo  [27] HANGUL SYLLABLE DYIG..HANGUL SYLLABLE DYIH
		(0xB515 <= code && code <= 0xB52F) || // Lo  [27] HANGUL SYLLABLE DIG..HANGUL SYLLABLE DIH
		(0xB531 <= code && code <= 0xB54B) || // Lo  [27] HANGUL SYLLABLE DDAG..HANGUL SYLLABLE DDAH
		(0xB54D <= code && code <= 0xB567) || // Lo  [27] HANGUL SYLLABLE DDAEG..HANGUL SYLLABLE DDAEH
		(0xB569 <= code && code <= 0xB583) || // Lo  [27] HANGUL SYLLABLE DDYAG..HANGUL SYLLABLE DDYAH
		(0xB585 <= code && code <= 0xB59F) || // Lo  [27] HANGUL SYLLABLE DDYAEG..HANGUL SYLLABLE DDYAEH
		(0xB5A1 <= code && code <= 0xB5BB) || // Lo  [27] HANGUL SYLLABLE DDEOG..HANGUL SYLLABLE DDEOH
		(0xB5BD <= code && code <= 0xB5D7) || // Lo  [27] HANGUL SYLLABLE DDEG..HANGUL SYLLABLE DDEH
		(0xB5D9 <= code && code <= 0xB5F3) || // Lo  [27] HANGUL SYLLABLE DDYEOG..HANGUL SYLLABLE DDYEOH
		(0xB5F5 <= code && code <= 0xB60F) || // Lo  [27] HANGUL SYLLABLE DDYEG..HANGUL SYLLABLE DDYEH
		(0xB611 <= code && code <= 0xB62B) || // Lo  [27] HANGUL SYLLABLE DDOG..HANGUL SYLLABLE DDOH
		(0xB62D <= code && code <= 0xB647) || // Lo  [27] HANGUL SYLLABLE DDWAG..HANGUL SYLLABLE DDWAH
		(0xB649 <= code && code <= 0xB663) || // Lo  [27] HANGUL SYLLABLE DDWAEG..HANGUL SYLLABLE DDWAEH
		(0xB665 <= code && code <= 0xB67F) || // Lo  [27] HANGUL SYLLABLE DDOEG..HANGUL SYLLABLE DDOEH
		(0xB681 <= code && code <= 0xB69B) || // Lo  [27] HANGUL SYLLABLE DDYOG..HANGUL SYLLABLE DDYOH
		(0xB69D <= code && code <= 0xB6B7) || // Lo  [27] HANGUL SYLLABLE DDUG..HANGUL SYLLABLE DDUH
		(0xB6B9 <= code && code <= 0xB6D3) || // Lo  [27] HANGUL SYLLABLE DDWEOG..HANGUL SYLLABLE DDWEOH
		(0xB6D5 <= code && code <= 0xB6EF) || // Lo  [27] HANGUL SYLLABLE DDWEG..HANGUL SYLLABLE DDWEH
		(0xB6F1 <= code && code <= 0xB70B) || // Lo  [27] HANGUL SYLLABLE DDWIG..HANGUL SYLLABLE DDWIH
		(0xB70D <= code && code <= 0xB727) || // Lo  [27] HANGUL SYLLABLE DDYUG..HANGUL SYLLABLE DDYUH
		(0xB729 <= code && code <= 0xB743) || // Lo  [27] HANGUL SYLLABLE DDEUG..HANGUL SYLLABLE DDEUH
		(0xB745 <= code && code <= 0xB75F) || // Lo  [27] HANGUL SYLLABLE DDYIG..HANGUL SYLLABLE DDYIH
		(0xB761 <= code && code <= 0xB77B) || // Lo  [27] HANGUL SYLLABLE DDIG..HANGUL SYLLABLE DDIH
		(0xB77D <= code && code <= 0xB797) || // Lo  [27] HANGUL SYLLABLE RAG..HANGUL SYLLABLE RAH
		(0xB799 <= code && code <= 0xB7B3) || // Lo  [27] HANGUL SYLLABLE RAEG..HANGUL SYLLABLE RAEH
		(0xB7B5 <= code && code <= 0xB7CF) || // Lo  [27] HANGUL SYLLABLE RYAG..HANGUL SYLLABLE RYAH
		(0xB7D1 <= code && code <= 0xB7EB) || // Lo  [27] HANGUL SYLLABLE RYAEG..HANGUL SYLLABLE RYAEH
		(0xB7ED <= code && code <= 0xB807) || // Lo  [27] HANGUL SYLLABLE REOG..HANGUL SYLLABLE REOH
		(0xB809 <= code && code <= 0xB823) || // Lo  [27] HANGUL SYLLABLE REG..HANGUL SYLLABLE REH
		(0xB825 <= code && code <= 0xB83F) || // Lo  [27] HANGUL SYLLABLE RYEOG..HANGUL SYLLABLE RYEOH
		(0xB841 <= code && code <= 0xB85B) || // Lo  [27] HANGUL SYLLABLE RYEG..HANGUL SYLLABLE RYEH
		(0xB85D <= code && code <= 0xB877) || // Lo  [27] HANGUL SYLLABLE ROG..HANGUL SYLLABLE ROH
		(0xB879 <= code && code <= 0xB893) || // Lo  [27] HANGUL SYLLABLE RWAG..HANGUL SYLLABLE RWAH
		(0xB895 <= code && code <= 0xB8AF) || // Lo  [27] HANGUL SYLLABLE RWAEG..HANGUL SYLLABLE RWAEH
		(0xB8B1 <= code && code <= 0xB8CB) || // Lo  [27] HANGUL SYLLABLE ROEG..HANGUL SYLLABLE ROEH
		(0xB8CD <= code && code <= 0xB8E7) || // Lo  [27] HANGUL SYLLABLE RYOG..HANGUL SYLLABLE RYOH
		(0xB8E9 <= code && code <= 0xB903) || // Lo  [27] HANGUL SYLLABLE RUG..HANGUL SYLLABLE RUH
		(0xB905 <= code && code <= 0xB91F) || // Lo  [27] HANGUL SYLLABLE RWEOG..HANGUL SYLLABLE RWEOH
		(0xB921 <= code && code <= 0xB93B) || // Lo  [27] HANGUL SYLLABLE RWEG..HANGUL SYLLABLE RWEH
		(0xB93D <= code && code <= 0xB957) || // Lo  [27] HANGUL SYLLABLE RWIG..HANGUL SYLLABLE RWIH
		(0xB959 <= code && code <= 0xB973) || // Lo  [27] HANGUL SYLLABLE RYUG..HANGUL SYLLABLE RYUH
		(0xB975 <= code && code <= 0xB98F) || // Lo  [27] HANGUL SYLLABLE REUG..HANGUL SYLLABLE REUH
		(0xB991 <= code && code <= 0xB9AB) || // Lo  [27] HANGUL SYLLABLE RYIG..HANGUL SYLLABLE RYIH
		(0xB9AD <= code && code <= 0xB9C7) || // Lo  [27] HANGUL SYLLABLE RIG..HANGUL SYLLABLE RIH
		(0xB9C9 <= code && code <= 0xB9E3) || // Lo  [27] HANGUL SYLLABLE MAG..HANGUL SYLLABLE MAH
		(0xB9E5 <= code && code <= 0xB9FF) || // Lo  [27] HANGUL SYLLABLE MAEG..HANGUL SYLLABLE MAEH
		(0xBA01 <= code && code <= 0xBA1B) || // Lo  [27] HANGUL SYLLABLE MYAG..HANGUL SYLLABLE MYAH
		(0xBA1D <= code && code <= 0xBA37) || // Lo  [27] HANGUL SYLLABLE MYAEG..HANGUL SYLLABLE MYAEH
		(0xBA39 <= code && code <= 0xBA53) || // Lo  [27] HANGUL SYLLABLE MEOG..HANGUL SYLLABLE MEOH
		(0xBA55 <= code && code <= 0xBA6F) || // Lo  [27] HANGUL SYLLABLE MEG..HANGUL SYLLABLE MEH
		(0xBA71 <= code && code <= 0xBA8B) || // Lo  [27] HANGUL SYLLABLE MYEOG..HANGUL SYLLABLE MYEOH
		(0xBA8D <= code && code <= 0xBAA7) || // Lo  [27] HANGUL SYLLABLE MYEG..HANGUL SYLLABLE MYEH
		(0xBAA9 <= code && code <= 0xBAC3) || // Lo  [27] HANGUL SYLLABLE MOG..HANGUL SYLLABLE MOH
		(0xBAC5 <= code && code <= 0xBADF) || // Lo  [27] HANGUL SYLLABLE MWAG..HANGUL SYLLABLE MWAH
		(0xBAE1 <= code && code <= 0xBAFB) || // Lo  [27] HANGUL SYLLABLE MWAEG..HANGUL SYLLABLE MWAEH
		(0xBAFD <= code && code <= 0xBB17) || // Lo  [27] HANGUL SYLLABLE MOEG..HANGUL SYLLABLE MOEH
		(0xBB19 <= code && code <= 0xBB33) || // Lo  [27] HANGUL SYLLABLE MYOG..HANGUL SYLLABLE MYOH
		(0xBB35 <= code && code <= 0xBB4F) || // Lo  [27] HANGUL SYLLABLE MUG..HANGUL SYLLABLE MUH
		(0xBB51 <= code && code <= 0xBB6B) || // Lo  [27] HANGUL SYLLABLE MWEOG..HANGUL SYLLABLE MWEOH
		(0xBB6D <= code && code <= 0xBB87) || // Lo  [27] HANGUL SYLLABLE MWEG..HANGUL SYLLABLE MWEH
		(0xBB89 <= code && code <= 0xBBA3) || // Lo  [27] HANGUL SYLLABLE MWIG..HANGUL SYLLABLE MWIH
		(0xBBA5 <= code && code <= 0xBBBF) || // Lo  [27] HANGUL SYLLABLE MYUG..HANGUL SYLLABLE MYUH
		(0xBBC1 <= code && code <= 0xBBDB) || // Lo  [27] HANGUL SYLLABLE MEUG..HANGUL SYLLABLE MEUH
		(0xBBDD <= code && code <= 0xBBF7) || // Lo  [27] HANGUL SYLLABLE MYIG..HANGUL SYLLABLE MYIH
		(0xBBF9 <= code && code <= 0xBC13) || // Lo  [27] HANGUL SYLLABLE MIG..HANGUL SYLLABLE MIH
		(0xBC15 <= code && code <= 0xBC2F) || // Lo  [27] HANGUL SYLLABLE BAG..HANGUL SYLLABLE BAH
		(0xBC31 <= code && code <= 0xBC4B) || // Lo  [27] HANGUL SYLLABLE BAEG..HANGUL SYLLABLE BAEH
		(0xBC4D <= code && code <= 0xBC67) || // Lo  [27] HANGUL SYLLABLE BYAG..HANGUL SYLLABLE BYAH
		(0xBC69 <= code && code <= 0xBC83) || // Lo  [27] HANGUL SYLLABLE BYAEG..HANGUL SYLLABLE BYAEH
		(0xBC85 <= code && code <= 0xBC9F) || // Lo  [27] HANGUL SYLLABLE BEOG..HANGUL SYLLABLE BEOH
		(0xBCA1 <= code && code <= 0xBCBB) || // Lo  [27] HANGUL SYLLABLE BEG..HANGUL SYLLABLE BEH
		(0xBCBD <= code && code <= 0xBCD7) || // Lo  [27] HANGUL SYLLABLE BYEOG..HANGUL SYLLABLE BYEOH
		(0xBCD9 <= code && code <= 0xBCF3) || // Lo  [27] HANGUL SYLLABLE BYEG..HANGUL SYLLABLE BYEH
		(0xBCF5 <= code && code <= 0xBD0F) || // Lo  [27] HANGUL SYLLABLE BOG..HANGUL SYLLABLE BOH
		(0xBD11 <= code && code <= 0xBD2B) || // Lo  [27] HANGUL SYLLABLE BWAG..HANGUL SYLLABLE BWAH
		(0xBD2D <= code && code <= 0xBD47) || // Lo  [27] HANGUL SYLLABLE BWAEG..HANGUL SYLLABLE BWAEH
		(0xBD49 <= code && code <= 0xBD63) || // Lo  [27] HANGUL SYLLABLE BOEG..HANGUL SYLLABLE BOEH
		(0xBD65 <= code && code <= 0xBD7F) || // Lo  [27] HANGUL SYLLABLE BYOG..HANGUL SYLLABLE BYOH
		(0xBD81 <= code && code <= 0xBD9B) || // Lo  [27] HANGUL SYLLABLE BUG..HANGUL SYLLABLE BUH
		(0xBD9D <= code && code <= 0xBDB7) || // Lo  [27] HANGUL SYLLABLE BWEOG..HANGUL SYLLABLE BWEOH
		(0xBDB9 <= code && code <= 0xBDD3) || // Lo  [27] HANGUL SYLLABLE BWEG..HANGUL SYLLABLE BWEH
		(0xBDD5 <= code && code <= 0xBDEF) || // Lo  [27] HANGUL SYLLABLE BWIG..HANGUL SYLLABLE BWIH
		(0xBDF1 <= code && code <= 0xBE0B) || // Lo  [27] HANGUL SYLLABLE BYUG..HANGUL SYLLABLE BYUH
		(0xBE0D <= code && code <= 0xBE27) || // Lo  [27] HANGUL SYLLABLE BEUG..HANGUL SYLLABLE BEUH
		(0xBE29 <= code && code <= 0xBE43) || // Lo  [27] HANGUL SYLLABLE BYIG..HANGUL SYLLABLE BYIH
		(0xBE45 <= code && code <= 0xBE5F) || // Lo  [27] HANGUL SYLLABLE BIG..HANGUL SYLLABLE BIH
		(0xBE61 <= code && code <= 0xBE7B) || // Lo  [27] HANGUL SYLLABLE BBAG..HANGUL SYLLABLE BBAH
		(0xBE7D <= code && code <= 0xBE97) || // Lo  [27] HANGUL SYLLABLE BBAEG..HANGUL SYLLABLE BBAEH
		(0xBE99 <= code && code <= 0xBEB3) || // Lo  [27] HANGUL SYLLABLE BBYAG..HANGUL SYLLABLE BBYAH
		(0xBEB5 <= code && code <= 0xBECF) || // Lo  [27] HANGUL SYLLABLE BBYAEG..HANGUL SYLLABLE BBYAEH
		(0xBED1 <= code && code <= 0xBEEB) || // Lo  [27] HANGUL SYLLABLE BBEOG..HANGUL SYLLABLE BBEOH
		(0xBEED <= code && code <= 0xBF07) || // Lo  [27] HANGUL SYLLABLE BBEG..HANGUL SYLLABLE BBEH
		(0xBF09 <= code && code <= 0xBF23) || // Lo  [27] HANGUL SYLLABLE BBYEOG..HANGUL SYLLABLE BBYEOH
		(0xBF25 <= code && code <= 0xBF3F) || // Lo  [27] HANGUL SYLLABLE BBYEG..HANGUL SYLLABLE BBYEH
		(0xBF41 <= code && code <= 0xBF5B) || // Lo  [27] HANGUL SYLLABLE BBOG..HANGUL SYLLABLE BBOH
		(0xBF5D <= code && code <= 0xBF77) || // Lo  [27] HANGUL SYLLABLE BBWAG..HANGUL SYLLABLE BBWAH
		(0xBF79 <= code && code <= 0xBF93) || // Lo  [27] HANGUL SYLLABLE BBWAEG..HANGUL SYLLABLE BBWAEH
		(0xBF95 <= code && code <= 0xBFAF) || // Lo  [27] HANGUL SYLLABLE BBOEG..HANGUL SYLLABLE BBOEH
		(0xBFB1 <= code && code <= 0xBFCB) || // Lo  [27] HANGUL SYLLABLE BBYOG..HANGUL SYLLABLE BBYOH
		(0xBFCD <= code && code <= 0xBFE7) || // Lo  [27] HANGUL SYLLABLE BBUG..HANGUL SYLLABLE BBUH
		(0xBFE9 <= code && code <= 0xC003) || // Lo  [27] HANGUL SYLLABLE BBWEOG..HANGUL SYLLABLE BBWEOH
		(0xC005 <= code && code <= 0xC01F) || // Lo  [27] HANGUL SYLLABLE BBWEG..HANGUL SYLLABLE BBWEH
		(0xC021 <= code && code <= 0xC03B) || // Lo  [27] HANGUL SYLLABLE BBWIG..HANGUL SYLLABLE BBWIH
		(0xC03D <= code && code <= 0xC057) || // Lo  [27] HANGUL SYLLABLE BBYUG..HANGUL SYLLABLE BBYUH
		(0xC059 <= code && code <= 0xC073) || // Lo  [27] HANGUL SYLLABLE BBEUG..HANGUL SYLLABLE BBEUH
		(0xC075 <= code && code <= 0xC08F) || // Lo  [27] HANGUL SYLLABLE BBYIG..HANGUL SYLLABLE BBYIH
		(0xC091 <= code && code <= 0xC0AB) || // Lo  [27] HANGUL SYLLABLE BBIG..HANGUL SYLLABLE BBIH
		(0xC0AD <= code && code <= 0xC0C7) || // Lo  [27] HANGUL SYLLABLE SAG..HANGUL SYLLABLE SAH
		(0xC0C9 <= code && code <= 0xC0E3) || // Lo  [27] HANGUL SYLLABLE SAEG..HANGUL SYLLABLE SAEH
		(0xC0E5 <= code && code <= 0xC0FF) || // Lo  [27] HANGUL SYLLABLE SYAG..HANGUL SYLLABLE SYAH
		(0xC101 <= code && code <= 0xC11B) || // Lo  [27] HANGUL SYLLABLE SYAEG..HANGUL SYLLABLE SYAEH
		(0xC11D <= code && code <= 0xC137) || // Lo  [27] HANGUL SYLLABLE SEOG..HANGUL SYLLABLE SEOH
		(0xC139 <= code && code <= 0xC153) || // Lo  [27] HANGUL SYLLABLE SEG..HANGUL SYLLABLE SEH
		(0xC155 <= code && code <= 0xC16F) || // Lo  [27] HANGUL SYLLABLE SYEOG..HANGUL SYLLABLE SYEOH
		(0xC171 <= code && code <= 0xC18B) || // Lo  [27] HANGUL SYLLABLE SYEG..HANGUL SYLLABLE SYEH
		(0xC18D <= code && code <= 0xC1A7) || // Lo  [27] HANGUL SYLLABLE SOG..HANGUL SYLLABLE SOH
		(0xC1A9 <= code && code <= 0xC1C3) || // Lo  [27] HANGUL SYLLABLE SWAG..HANGUL SYLLABLE SWAH
		(0xC1C5 <= code && code <= 0xC1DF) || // Lo  [27] HANGUL SYLLABLE SWAEG..HANGUL SYLLABLE SWAEH
		(0xC1E1 <= code && code <= 0xC1FB) || // Lo  [27] HANGUL SYLLABLE SOEG..HANGUL SYLLABLE SOEH
		(0xC1FD <= code && code <= 0xC217) || // Lo  [27] HANGUL SYLLABLE SYOG..HANGUL SYLLABLE SYOH
		(0xC219 <= code && code <= 0xC233) || // Lo  [27] HANGUL SYLLABLE SUG..HANGUL SYLLABLE SUH
		(0xC235 <= code && code <= 0xC24F) || // Lo  [27] HANGUL SYLLABLE SWEOG..HANGUL SYLLABLE SWEOH
		(0xC251 <= code && code <= 0xC26B) || // Lo  [27] HANGUL SYLLABLE SWEG..HANGUL SYLLABLE SWEH
		(0xC26D <= code && code <= 0xC287) || // Lo  [27] HANGUL SYLLABLE SWIG..HANGUL SYLLABLE SWIH
		(0xC289 <= code && code <= 0xC2A3) || // Lo  [27] HANGUL SYLLABLE SYUG..HANGUL SYLLABLE SYUH
		(0xC2A5 <= code && code <= 0xC2BF) || // Lo  [27] HANGUL SYLLABLE SEUG..HANGUL SYLLABLE SEUH
		(0xC2C1 <= code && code <= 0xC2DB) || // Lo  [27] HANGUL SYLLABLE SYIG..HANGUL SYLLABLE SYIH
		(0xC2DD <= code && code <= 0xC2F7) || // Lo  [27] HANGUL SYLLABLE SIG..HANGUL SYLLABLE SIH
		(0xC2F9 <= code && code <= 0xC313) || // Lo  [27] HANGUL SYLLABLE SSAG..HANGUL SYLLABLE SSAH
		(0xC315 <= code && code <= 0xC32F) || // Lo  [27] HANGUL SYLLABLE SSAEG..HANGUL SYLLABLE SSAEH
		(0xC331 <= code && code <= 0xC34B) || // Lo  [27] HANGUL SYLLABLE SSYAG..HANGUL SYLLABLE SSYAH
		(0xC34D <= code && code <= 0xC367) || // Lo  [27] HANGUL SYLLABLE SSYAEG..HANGUL SYLLABLE SSYAEH
		(0xC369 <= code && code <= 0xC383) || // Lo  [27] HANGUL SYLLABLE SSEOG..HANGUL SYLLABLE SSEOH
		(0xC385 <= code && code <= 0xC39F) || // Lo  [27] HANGUL SYLLABLE SSEG..HANGUL SYLLABLE SSEH
		(0xC3A1 <= code && code <= 0xC3BB) || // Lo  [27] HANGUL SYLLABLE SSYEOG..HANGUL SYLLABLE SSYEOH
		(0xC3BD <= code && code <= 0xC3D7) || // Lo  [27] HANGUL SYLLABLE SSYEG..HANGUL SYLLABLE SSYEH
		(0xC3D9 <= code && code <= 0xC3F3) || // Lo  [27] HANGUL SYLLABLE SSOG..HANGUL SYLLABLE SSOH
		(0xC3F5 <= code && code <= 0xC40F) || // Lo  [27] HANGUL SYLLABLE SSWAG..HANGUL SYLLABLE SSWAH
		(0xC411 <= code && code <= 0xC42B) || // Lo  [27] HANGUL SYLLABLE SSWAEG..HANGUL SYLLABLE SSWAEH
		(0xC42D <= code && code <= 0xC447) || // Lo  [27] HANGUL SYLLABLE SSOEG..HANGUL SYLLABLE SSOEH
		(0xC449 <= code && code <= 0xC463) || // Lo  [27] HANGUL SYLLABLE SSYOG..HANGUL SYLLABLE SSYOH
		(0xC465 <= code && code <= 0xC47F) || // Lo  [27] HANGUL SYLLABLE SSUG..HANGUL SYLLABLE SSUH
		(0xC481 <= code && code <= 0xC49B) || // Lo  [27] HANGUL SYLLABLE SSWEOG..HANGUL SYLLABLE SSWEOH
		(0xC49D <= code && code <= 0xC4B7) || // Lo  [27] HANGUL SYLLABLE SSWEG..HANGUL SYLLABLE SSWEH
		(0xC4B9 <= code && code <= 0xC4D3) || // Lo  [27] HANGUL SYLLABLE SSWIG..HANGUL SYLLABLE SSWIH
		(0xC4D5 <= code && code <= 0xC4EF) || // Lo  [27] HANGUL SYLLABLE SSYUG..HANGUL SYLLABLE SSYUH
		(0xC4F1 <= code && code <= 0xC50B) || // Lo  [27] HANGUL SYLLABLE SSEUG..HANGUL SYLLABLE SSEUH
		(0xC50D <= code && code <= 0xC527) || // Lo  [27] HANGUL SYLLABLE SSYIG..HANGUL SYLLABLE SSYIH
		(0xC529 <= code && code <= 0xC543) || // Lo  [27] HANGUL SYLLABLE SSIG..HANGUL SYLLABLE SSIH
		(0xC545 <= code && code <= 0xC55F) || // Lo  [27] HANGUL SYLLABLE AG..HANGUL SYLLABLE AH
		(0xC561 <= code && code <= 0xC57B) || // Lo  [27] HANGUL SYLLABLE AEG..HANGUL SYLLABLE AEH
		(0xC57D <= code && code <= 0xC597) || // Lo  [27] HANGUL SYLLABLE YAG..HANGUL SYLLABLE YAH
		(0xC599 <= code && code <= 0xC5B3) || // Lo  [27] HANGUL SYLLABLE YAEG..HANGUL SYLLABLE YAEH
		(0xC5B5 <= code && code <= 0xC5CF) || // Lo  [27] HANGUL SYLLABLE EOG..HANGUL SYLLABLE EOH
		(0xC5D1 <= code && code <= 0xC5EB) || // Lo  [27] HANGUL SYLLABLE EG..HANGUL SYLLABLE EH
		(0xC5ED <= code && code <= 0xC607) || // Lo  [27] HANGUL SYLLABLE YEOG..HANGUL SYLLABLE YEOH
		(0xC609 <= code && code <= 0xC623) || // Lo  [27] HANGUL SYLLABLE YEG..HANGUL SYLLABLE YEH
		(0xC625 <= code && code <= 0xC63F) || // Lo  [27] HANGUL SYLLABLE OG..HANGUL SYLLABLE OH
		(0xC641 <= code && code <= 0xC65B) || // Lo  [27] HANGUL SYLLABLE WAG..HANGUL SYLLABLE WAH
		(0xC65D <= code && code <= 0xC677) || // Lo  [27] HANGUL SYLLABLE WAEG..HANGUL SYLLABLE WAEH
		(0xC679 <= code && code <= 0xC693) || // Lo  [27] HANGUL SYLLABLE OEG..HANGUL SYLLABLE OEH
		(0xC695 <= code && code <= 0xC6AF) || // Lo  [27] HANGUL SYLLABLE YOG..HANGUL SYLLABLE YOH
		(0xC6B1 <= code && code <= 0xC6CB) || // Lo  [27] HANGUL SYLLABLE UG..HANGUL SYLLABLE UH
		(0xC6CD <= code && code <= 0xC6E7) || // Lo  [27] HANGUL SYLLABLE WEOG..HANGUL SYLLABLE WEOH
		(0xC6E9 <= code && code <= 0xC703) || // Lo  [27] HANGUL SYLLABLE WEG..HANGUL SYLLABLE WEH
		(0xC705 <= code && code <= 0xC71F) || // Lo  [27] HANGUL SYLLABLE WIG..HANGUL SYLLABLE WIH
		(0xC721 <= code && code <= 0xC73B) || // Lo  [27] HANGUL SYLLABLE YUG..HANGUL SYLLABLE YUH
		(0xC73D <= code && code <= 0xC757) || // Lo  [27] HANGUL SYLLABLE EUG..HANGUL SYLLABLE EUH
		(0xC759 <= code && code <= 0xC773) || // Lo  [27] HANGUL SYLLABLE YIG..HANGUL SYLLABLE YIH
		(0xC775 <= code && code <= 0xC78F) || // Lo  [27] HANGUL SYLLABLE IG..HANGUL SYLLABLE IH
		(0xC791 <= code && code <= 0xC7AB) || // Lo  [27] HANGUL SYLLABLE JAG..HANGUL SYLLABLE JAH
		(0xC7AD <= code && code <= 0xC7C7) || // Lo  [27] HANGUL SYLLABLE JAEG..HANGUL SYLLABLE JAEH
		(0xC7C9 <= code && code <= 0xC7E3) || // Lo  [27] HANGUL SYLLABLE JYAG..HANGUL SYLLABLE JYAH
		(0xC7E5 <= code && code <= 0xC7FF) || // Lo  [27] HANGUL SYLLABLE JYAEG..HANGUL SYLLABLE JYAEH
		(0xC801 <= code && code <= 0xC81B) || // Lo  [27] HANGUL SYLLABLE JEOG..HANGUL SYLLABLE JEOH
		(0xC81D <= code && code <= 0xC837) || // Lo  [27] HANGUL SYLLABLE JEG..HANGUL SYLLABLE JEH
		(0xC839 <= code && code <= 0xC853) || // Lo  [27] HANGUL SYLLABLE JYEOG..HANGUL SYLLABLE JYEOH
		(0xC855 <= code && code <= 0xC86F) || // Lo  [27] HANGUL SYLLABLE JYEG..HANGUL SYLLABLE JYEH
		(0xC871 <= code && code <= 0xC88B) || // Lo  [27] HANGUL SYLLABLE JOG..HANGUL SYLLABLE JOH
		(0xC88D <= code && code <= 0xC8A7) || // Lo  [27] HANGUL SYLLABLE JWAG..HANGUL SYLLABLE JWAH
		(0xC8A9 <= code && code <= 0xC8C3) || // Lo  [27] HANGUL SYLLABLE JWAEG..HANGUL SYLLABLE JWAEH
		(0xC8C5 <= code && code <= 0xC8DF) || // Lo  [27] HANGUL SYLLABLE JOEG..HANGUL SYLLABLE JOEH
		(0xC8E1 <= code && code <= 0xC8FB) || // Lo  [27] HANGUL SYLLABLE JYOG..HANGUL SYLLABLE JYOH
		(0xC8FD <= code && code <= 0xC917) || // Lo  [27] HANGUL SYLLABLE JUG..HANGUL SYLLABLE JUH
		(0xC919 <= code && code <= 0xC933) || // Lo  [27] HANGUL SYLLABLE JWEOG..HANGUL SYLLABLE JWEOH
		(0xC935 <= code && code <= 0xC94F) || // Lo  [27] HANGUL SYLLABLE JWEG..HANGUL SYLLABLE JWEH
		(0xC951 <= code && code <= 0xC96B) || // Lo  [27] HANGUL SYLLABLE JWIG..HANGUL SYLLABLE JWIH
		(0xC96D <= code && code <= 0xC987) || // Lo  [27] HANGUL SYLLABLE JYUG..HANGUL SYLLABLE JYUH
		(0xC989 <= code && code <= 0xC9A3) || // Lo  [27] HANGUL SYLLABLE JEUG..HANGUL SYLLABLE JEUH
		(0xC9A5 <= code && code <= 0xC9BF) || // Lo  [27] HANGUL SYLLABLE JYIG..HANGUL SYLLABLE JYIH
		(0xC9C1 <= code && code <= 0xC9DB) || // Lo  [27] HANGUL SYLLABLE JIG..HANGUL SYLLABLE JIH
		(0xC9DD <= code && code <= 0xC9F7) || // Lo  [27] HANGUL SYLLABLE JJAG..HANGUL SYLLABLE JJAH
		(0xC9F9 <= code && code <= 0xCA13) || // Lo  [27] HANGUL SYLLABLE JJAEG..HANGUL SYLLABLE JJAEH
		(0xCA15 <= code && code <= 0xCA2F) || // Lo  [27] HANGUL SYLLABLE JJYAG..HANGUL SYLLABLE JJYAH
		(0xCA31 <= code && code <= 0xCA4B) || // Lo  [27] HANGUL SYLLABLE JJYAEG..HANGUL SYLLABLE JJYAEH
		(0xCA4D <= code && code <= 0xCA67) || // Lo  [27] HANGUL SYLLABLE JJEOG..HANGUL SYLLABLE JJEOH
		(0xCA69 <= code && code <= 0xCA83) || // Lo  [27] HANGUL SYLLABLE JJEG..HANGUL SYLLABLE JJEH
		(0xCA85 <= code && code <= 0xCA9F) || // Lo  [27] HANGUL SYLLABLE JJYEOG..HANGUL SYLLABLE JJYEOH
		(0xCAA1 <= code && code <= 0xCABB) || // Lo  [27] HANGUL SYLLABLE JJYEG..HANGUL SYLLABLE JJYEH
		(0xCABD <= code && code <= 0xCAD7) || // Lo  [27] HANGUL SYLLABLE JJOG..HANGUL SYLLABLE JJOH
		(0xCAD9 <= code && code <= 0xCAF3) || // Lo  [27] HANGUL SYLLABLE JJWAG..HANGUL SYLLABLE JJWAH
		(0xCAF5 <= code && code <= 0xCB0F) || // Lo  [27] HANGUL SYLLABLE JJWAEG..HANGUL SYLLABLE JJWAEH
		(0xCB11 <= code && code <= 0xCB2B) || // Lo  [27] HANGUL SYLLABLE JJOEG..HANGUL SYLLABLE JJOEH
		(0xCB2D <= code && code <= 0xCB47) || // Lo  [27] HANGUL SYLLABLE JJYOG..HANGUL SYLLABLE JJYOH
		(0xCB49 <= code && code <= 0xCB63) || // Lo  [27] HANGUL SYLLABLE JJUG..HANGUL SYLLABLE JJUH
		(0xCB65 <= code && code <= 0xCB7F) || // Lo  [27] HANGUL SYLLABLE JJWEOG..HANGUL SYLLABLE JJWEOH
		(0xCB81 <= code && code <= 0xCB9B) || // Lo  [27] HANGUL SYLLABLE JJWEG..HANGUL SYLLABLE JJWEH
		(0xCB9D <= code && code <= 0xCBB7) || // Lo  [27] HANGUL SYLLABLE JJWIG..HANGUL SYLLABLE JJWIH
		(0xCBB9 <= code && code <= 0xCBD3) || // Lo  [27] HANGUL SYLLABLE JJYUG..HANGUL SYLLABLE JJYUH
		(0xCBD5 <= code && code <= 0xCBEF) || // Lo  [27] HANGUL SYLLABLE JJEUG..HANGUL SYLLABLE JJEUH
		(0xCBF1 <= code && code <= 0xCC0B) || // Lo  [27] HANGUL SYLLABLE JJYIG..HANGUL SYLLABLE JJYIH
		(0xCC0D <= code && code <= 0xCC27) || // Lo  [27] HANGUL SYLLABLE JJIG..HANGUL SYLLABLE JJIH
		(0xCC29 <= code && code <= 0xCC43) || // Lo  [27] HANGUL SYLLABLE CAG..HANGUL SYLLABLE CAH
		(0xCC45 <= code && code <= 0xCC5F) || // Lo  [27] HANGUL SYLLABLE CAEG..HANGUL SYLLABLE CAEH
		(0xCC61 <= code && code <= 0xCC7B) || // Lo  [27] HANGUL SYLLABLE CYAG..HANGUL SYLLABLE CYAH
		(0xCC7D <= code && code <= 0xCC97) || // Lo  [27] HANGUL SYLLABLE CYAEG..HANGUL SYLLABLE CYAEH
		(0xCC99 <= code && code <= 0xCCB3) || // Lo  [27] HANGUL SYLLABLE CEOG..HANGUL SYLLABLE CEOH
		(0xCCB5 <= code && code <= 0xCCCF) || // Lo  [27] HANGUL SYLLABLE CEG..HANGUL SYLLABLE CEH
		(0xCCD1 <= code && code <= 0xCCEB) || // Lo  [27] HANGUL SYLLABLE CYEOG..HANGUL SYLLABLE CYEOH
		(0xCCED <= code && code <= 0xCD07) || // Lo  [27] HANGUL SYLLABLE CYEG..HANGUL SYLLABLE CYEH
		(0xCD09 <= code && code <= 0xCD23) || // Lo  [27] HANGUL SYLLABLE COG..HANGUL SYLLABLE COH
		(0xCD25 <= code && code <= 0xCD3F) || // Lo  [27] HANGUL SYLLABLE CWAG..HANGUL SYLLABLE CWAH
		(0xCD41 <= code && code <= 0xCD5B) || // Lo  [27] HANGUL SYLLABLE CWAEG..HANGUL SYLLABLE CWAEH
		(0xCD5D <= code && code <= 0xCD77) || // Lo  [27] HANGUL SYLLABLE COEG..HANGUL SYLLABLE COEH
		(0xCD79 <= code && code <= 0xCD93) || // Lo  [27] HANGUL SYLLABLE CYOG..HANGUL SYLLABLE CYOH
		(0xCD95 <= code && code <= 0xCDAF) || // Lo  [27] HANGUL SYLLABLE CUG..HANGUL SYLLABLE CUH
		(0xCDB1 <= code && code <= 0xCDCB) || // Lo  [27] HANGUL SYLLABLE CWEOG..HANGUL SYLLABLE CWEOH
		(0xCDCD <= code && code <= 0xCDE7) || // Lo  [27] HANGUL SYLLABLE CWEG..HANGUL SYLLABLE CWEH
		(0xCDE9 <= code && code <= 0xCE03) || // Lo  [27] HANGUL SYLLABLE CWIG..HANGUL SYLLABLE CWIH
		(0xCE05 <= code && code <= 0xCE1F) || // Lo  [27] HANGUL SYLLABLE CYUG..HANGUL SYLLABLE CYUH
		(0xCE21 <= code && code <= 0xCE3B) || // Lo  [27] HANGUL SYLLABLE CEUG..HANGUL SYLLABLE CEUH
		(0xCE3D <= code && code <= 0xCE57) || // Lo  [27] HANGUL SYLLABLE CYIG..HANGUL SYLLABLE CYIH
		(0xCE59 <= code && code <= 0xCE73) || // Lo  [27] HANGUL SYLLABLE CIG..HANGUL SYLLABLE CIH
		(0xCE75 <= code && code <= 0xCE8F) || // Lo  [27] HANGUL SYLLABLE KAG..HANGUL SYLLABLE KAH
		(0xCE91 <= code && code <= 0xCEAB) || // Lo  [27] HANGUL SYLLABLE KAEG..HANGUL SYLLABLE KAEH
		(0xCEAD <= code && code <= 0xCEC7) || // Lo  [27] HANGUL SYLLABLE KYAG..HANGUL SYLLABLE KYAH
		(0xCEC9 <= code && code <= 0xCEE3) || // Lo  [27] HANGUL SYLLABLE KYAEG..HANGUL SYLLABLE KYAEH
		(0xCEE5 <= code && code <= 0xCEFF) || // Lo  [27] HANGUL SYLLABLE KEOG..HANGUL SYLLABLE KEOH
		(0xCF01 <= code && code <= 0xCF1B) || // Lo  [27] HANGUL SYLLABLE KEG..HANGUL SYLLABLE KEH
		(0xCF1D <= code && code <= 0xCF37) || // Lo  [27] HANGUL SYLLABLE KYEOG..HANGUL SYLLABLE KYEOH
		(0xCF39 <= code && code <= 0xCF53) || // Lo  [27] HANGUL SYLLABLE KYEG..HANGUL SYLLABLE KYEH
		(0xCF55 <= code && code <= 0xCF6F) || // Lo  [27] HANGUL SYLLABLE KOG..HANGUL SYLLABLE KOH
		(0xCF71 <= code && code <= 0xCF8B) || // Lo  [27] HANGUL SYLLABLE KWAG..HANGUL SYLLABLE KWAH
		(0xCF8D <= code && code <= 0xCFA7) || // Lo  [27] HANGUL SYLLABLE KWAEG..HANGUL SYLLABLE KWAEH
		(0xCFA9 <= code && code <= 0xCFC3) || // Lo  [27] HANGUL SYLLABLE KOEG..HANGUL SYLLABLE KOEH
		(0xCFC5 <= code && code <= 0xCFDF) || // Lo  [27] HANGUL SYLLABLE KYOG..HANGUL SYLLABLE KYOH
		(0xCFE1 <= code && code <= 0xCFFB) || // Lo  [27] HANGUL SYLLABLE KUG..HANGUL SYLLABLE KUH
		(0xCFFD <= code && code <= 0xD017) || // Lo  [27] HANGUL SYLLABLE KWEOG..HANGUL SYLLABLE KWEOH
		(0xD019 <= code && code <= 0xD033) || // Lo  [27] HANGUL SYLLABLE KWEG..HANGUL SYLLABLE KWEH
		(0xD035 <= code && code <= 0xD04F) || // Lo  [27] HANGUL SYLLABLE KWIG..HANGUL SYLLABLE KWIH
		(0xD051 <= code && code <= 0xD06B) || // Lo  [27] HANGUL SYLLABLE KYUG..HANGUL SYLLABLE KYUH
		(0xD06D <= code && code <= 0xD087) || // Lo  [27] HANGUL SYLLABLE KEUG..HANGUL SYLLABLE KEUH
		(0xD089 <= code && code <= 0xD0A3) || // Lo  [27] HANGUL SYLLABLE KYIG..HANGUL SYLLABLE KYIH
		(0xD0A5 <= code && code <= 0xD0BF) || // Lo  [27] HANGUL SYLLABLE KIG..HANGUL SYLLABLE KIH
		(0xD0C1 <= code && code <= 0xD0DB) || // Lo  [27] HANGUL SYLLABLE TAG..HANGUL SYLLABLE TAH
		(0xD0DD <= code && code <= 0xD0F7) || // Lo  [27] HANGUL SYLLABLE TAEG..HANGUL SYLLABLE TAEH
		(0xD0F9 <= code && code <= 0xD113) || // Lo  [27] HANGUL SYLLABLE TYAG..HANGUL SYLLABLE TYAH
		(0xD115 <= code && code <= 0xD12F) || // Lo  [27] HANGUL SYLLABLE TYAEG..HANGUL SYLLABLE TYAEH
		(0xD131 <= code && code <= 0xD14B) || // Lo  [27] HANGUL SYLLABLE TEOG..HANGUL SYLLABLE TEOH
		(0xD14D <= code && code <= 0xD167) || // Lo  [27] HANGUL SYLLABLE TEG..HANGUL SYLLABLE TEH
		(0xD169 <= code && code <= 0xD183) || // Lo  [27] HANGUL SYLLABLE TYEOG..HANGUL SYLLABLE TYEOH
		(0xD185 <= code && code <= 0xD19F) || // Lo  [27] HANGUL SYLLABLE TYEG..HANGUL SYLLABLE TYEH
		(0xD1A1 <= code && code <= 0xD1BB) || // Lo  [27] HANGUL SYLLABLE TOG..HANGUL SYLLABLE TOH
		(0xD1BD <= code && code <= 0xD1D7) || // Lo  [27] HANGUL SYLLABLE TWAG..HANGUL SYLLABLE TWAH
		(0xD1D9 <= code && code <= 0xD1F3) || // Lo  [27] HANGUL SYLLABLE TWAEG..HANGUL SYLLABLE TWAEH
		(0xD1F5 <= code && code <= 0xD20F) || // Lo  [27] HANGUL SYLLABLE TOEG..HANGUL SYLLABLE TOEH
		(0xD211 <= code && code <= 0xD22B) || // Lo  [27] HANGUL SYLLABLE TYOG..HANGUL SYLLABLE TYOH
		(0xD22D <= code && code <= 0xD247) || // Lo  [27] HANGUL SYLLABLE TUG..HANGUL SYLLABLE TUH
		(0xD249 <= code && code <= 0xD263) || // Lo  [27] HANGUL SYLLABLE TWEOG..HANGUL SYLLABLE TWEOH
		(0xD265 <= code && code <= 0xD27F) || // Lo  [27] HANGUL SYLLABLE TWEG..HANGUL SYLLABLE TWEH
		(0xD281 <= code && code <= 0xD29B) || // Lo  [27] HANGUL SYLLABLE TWIG..HANGUL SYLLABLE TWIH
		(0xD29D <= code && code <= 0xD2B7) || // Lo  [27] HANGUL SYLLABLE TYUG..HANGUL SYLLABLE TYUH
		(0xD2B9 <= code && code <= 0xD2D3) || // Lo  [27] HANGUL SYLLABLE TEUG..HANGUL SYLLABLE TEUH
		(0xD2D5 <= code && code <= 0xD2EF) || // Lo  [27] HANGUL SYLLABLE TYIG..HANGUL SYLLABLE TYIH
		(0xD2F1 <= code && code <= 0xD30B) || // Lo  [27] HANGUL SYLLABLE TIG..HANGUL SYLLABLE TIH
		(0xD30D <= code && code <= 0xD327) || // Lo  [27] HANGUL SYLLABLE PAG..HANGUL SYLLABLE PAH
		(0xD329 <= code && code <= 0xD343) || // Lo  [27] HANGUL SYLLABLE PAEG..HANGUL SYLLABLE PAEH
		(0xD345 <= code && code <= 0xD35F) || // Lo  [27] HANGUL SYLLABLE PYAG..HANGUL SYLLABLE PYAH
		(0xD361 <= code && code <= 0xD37B) || // Lo  [27] HANGUL SYLLABLE PYAEG..HANGUL SYLLABLE PYAEH
		(0xD37D <= code && code <= 0xD397) || // Lo  [27] HANGUL SYLLABLE PEOG..HANGUL SYLLABLE PEOH
		(0xD399 <= code && code <= 0xD3B3) || // Lo  [27] HANGUL SYLLABLE PEG..HANGUL SYLLABLE PEH
		(0xD3B5 <= code && code <= 0xD3CF) || // Lo  [27] HANGUL SYLLABLE PYEOG..HANGUL SYLLABLE PYEOH
		(0xD3D1 <= code && code <= 0xD3EB) || // Lo  [27] HANGUL SYLLABLE PYEG..HANGUL SYLLABLE PYEH
		(0xD3ED <= code && code <= 0xD407) || // Lo  [27] HANGUL SYLLABLE POG..HANGUL SYLLABLE POH
		(0xD409 <= code && code <= 0xD423) || // Lo  [27] HANGUL SYLLABLE PWAG..HANGUL SYLLABLE PWAH
		(0xD425 <= code && code <= 0xD43F) || // Lo  [27] HANGUL SYLLABLE PWAEG..HANGUL SYLLABLE PWAEH
		(0xD441 <= code && code <= 0xD45B) || // Lo  [27] HANGUL SYLLABLE POEG..HANGUL SYLLABLE POEH
		(0xD45D <= code && code <= 0xD477) || // Lo  [27] HANGUL SYLLABLE PYOG..HANGUL SYLLABLE PYOH
		(0xD479 <= code && code <= 0xD493) || // Lo  [27] HANGUL SYLLABLE PUG..HANGUL SYLLABLE PUH
		(0xD495 <= code && code <= 0xD4AF) || // Lo  [27] HANGUL SYLLABLE PWEOG..HANGUL SYLLABLE PWEOH
		(0xD4B1 <= code && code <= 0xD4CB) || // Lo  [27] HANGUL SYLLABLE PWEG..HANGUL SYLLABLE PWEH
		(0xD4CD <= code && code <= 0xD4E7) || // Lo  [27] HANGUL SYLLABLE PWIG..HANGUL SYLLABLE PWIH
		(0xD4E9 <= code && code <= 0xD503) || // Lo  [27] HANGUL SYLLABLE PYUG..HANGUL SYLLABLE PYUH
		(0xD505 <= code && code <= 0xD51F) || // Lo  [27] HANGUL SYLLABLE PEUG..HANGUL SYLLABLE PEUH
		(0xD521 <= code && code <= 0xD53B) || // Lo  [27] HANGUL SYLLABLE PYIG..HANGUL SYLLABLE PYIH
		(0xD53D <= code && code <= 0xD557) || // Lo  [27] HANGUL SYLLABLE PIG..HANGUL SYLLABLE PIH
		(0xD559 <= code && code <= 0xD573) || // Lo  [27] HANGUL SYLLABLE HAG..HANGUL SYLLABLE HAH
		(0xD575 <= code && code <= 0xD58F) || // Lo  [27] HANGUL SYLLABLE HAEG..HANGUL SYLLABLE HAEH
		(0xD591 <= code && code <= 0xD5AB) || // Lo  [27] HANGUL SYLLABLE HYAG..HANGUL SYLLABLE HYAH
		(0xD5AD <= code && code <= 0xD5C7) || // Lo  [27] HANGUL SYLLABLE HYAEG..HANGUL SYLLABLE HYAEH
		(0xD5C9 <= code && code <= 0xD5E3) || // Lo  [27] HANGUL SYLLABLE HEOG..HANGUL SYLLABLE HEOH
		(0xD5E5 <= code && code <= 0xD5FF) || // Lo  [27] HANGUL SYLLABLE HEG..HANGUL SYLLABLE HEH
		(0xD601 <= code && code <= 0xD61B) || // Lo  [27] HANGUL SYLLABLE HYEOG..HANGUL SYLLABLE HYEOH
		(0xD61D <= code && code <= 0xD637) || // Lo  [27] HANGUL SYLLABLE HYEG..HANGUL SYLLABLE HYEH
		(0xD639 <= code && code <= 0xD653) || // Lo  [27] HANGUL SYLLABLE HOG..HANGUL SYLLABLE HOH
		(0xD655 <= code && code <= 0xD66F) || // Lo  [27] HANGUL SYLLABLE HWAG..HANGUL SYLLABLE HWAH
		(0xD671 <= code && code <= 0xD68B) || // Lo  [27] HANGUL SYLLABLE HWAEG..HANGUL SYLLABLE HWAEH
		(0xD68D <= code && code <= 0xD6A7) || // Lo  [27] HANGUL SYLLABLE HOEG..HANGUL SYLLABLE HOEH
		(0xD6A9 <= code && code <= 0xD6C3) || // Lo  [27] HANGUL SYLLABLE HYOG..HANGUL SYLLABLE HYOH
		(0xD6C5 <= code && code <= 0xD6DF) || // Lo  [27] HANGUL SYLLABLE HUG..HANGUL SYLLABLE HUH
		(0xD6E1 <= code && code <= 0xD6FB) || // Lo  [27] HANGUL SYLLABLE HWEOG..HANGUL SYLLABLE HWEOH
		(0xD6FD <= code && code <= 0xD717) || // Lo  [27] HANGUL SYLLABLE HWEG..HANGUL SYLLABLE HWEH
		(0xD719 <= code && code <= 0xD733) || // Lo  [27] HANGUL SYLLABLE HWIG..HANGUL SYLLABLE HWIH
		(0xD735 <= code && code <= 0xD74F) || // Lo  [27] HANGUL SYLLABLE HYUG..HANGUL SYLLABLE HYUH
		(0xD751 <= code && code <= 0xD76B) || // Lo  [27] HANGUL SYLLABLE HEUG..HANGUL SYLLABLE HEUH
		(0xD76D <= code && code <= 0xD787) || // Lo  [27] HANGUL SYLLABLE HYIG..HANGUL SYLLABLE HYIH
		(0xD789 <= code && code <= 0xD7A3) // Lo  [27] HANGUL SYLLABLE HIG..HANGUL SYLLABLE HIH
		){
			return LVT;
		}
		
		if(
		0x261D == code || // So       WHITE UP POINTING INDEX
		0x26F9 == code || // So       PERSON WITH BALL
		(0x270A <= code && code <= 0x270D) || // So   [4] RAISED FIST..WRITING HAND
		0x1F385 == code || // So       FATHER CHRISTMAS
		(0x1F3C2 <= code && code <= 0x1F3C4) || // So   [3] SNOWBOARDER..SURFER
		0x1F3C7 == code || // So       HORSE RACING
		(0x1F3CA <= code && code <= 0x1F3CC) || // So   [3] SWIMMER..GOLFER
		(0x1F442 <= code && code <= 0x1F443) || // So   [2] EAR..NOSE
		(0x1F446 <= code && code <= 0x1F450) || // So  [11] WHITE UP POINTING BACKHAND INDEX..OPEN HANDS SIGN
		0x1F46E == code || // So       POLICE OFFICER
		(0x1F470 <= code && code <= 0x1F478) || // So   [9] BRIDE WITH VEIL..PRINCESS
		0x1F47C == code || // So       BABY ANGEL
		(0x1F481 <= code && code <= 0x1F483) || // So   [3] INFORMATION DESK PERSON..DANCER
		(0x1F485 <= code && code <= 0x1F487) || // So   [3] NAIL POLISH..HAIRCUT
		0x1F4AA == code || // So       FLEXED BICEPS
		(0x1F574 <= code && code <= 0x1F575) || // So   [2] MAN IN BUSINESS SUIT LEVITATING..SLEUTH OR SPY
		0x1F57A == code || // So       MAN DANCING
		0x1F590 == code || // So       RAISED HAND WITH FINGERS SPLAYED
		(0x1F595 <= code && code <= 0x1F596) || // So   [2] REVERSED HAND WITH MIDDLE FINGER EXTENDED..RAISED HAND WITH PART BETWEEN MIDDLE AND RING FINGERS
		(0x1F645 <= code && code <= 0x1F647) || // So   [3] FACE WITH NO GOOD GESTURE..PERSON BOWING DEEPLY
		(0x1F64B <= code && code <= 0x1F64F) || // So   [5] HAPPY PERSON RAISING ONE HAND..PERSON WITH FOLDED HANDS
		0x1F6A3 == code || // So       ROWBOAT
		(0x1F6B4 <= code && code <= 0x1F6B6) || // So   [3] BICYCLIST..PEDESTRIAN
		0x1F6C0 == code || // So       BATH
		0x1F6CC == code || // So       SLEEPING ACCOMMODATION
		(0x1F918 <= code && code <= 0x1F91C) || // So   [5] SIGN OF THE HORNS..RIGHT-FACING FIST
		(0x1F91E <= code && code <= 0x1F91F) || // So   [2] HAND WITH INDEX AND MIDDLE FINGERS CROSSED..I LOVE YOU HAND SIGN
		0x1F926 == code || // So       FACE PALM
		(0x1F930 <= code && code <= 0x1F939) || // So  [10] PREGNANT WOMAN..JUGGLING
		(0x1F93D <= code && code <= 0x1F93E) || // So   [2] WATER POLO..HANDBALL
		(0x1F9D1 <= code && code <= 0x1F9DD) // So  [13] ADULT..ELF
		){
			return E_Base;
		}

		if(
		(0x1F3FB <= code && code <= 0x1F3FF) // Sk   [5] EMOJI MODIFIER FITZPATRICK TYPE-1-2..EMOJI MODIFIER FITZPATRICK TYPE-6
		){
			return E_Modifier;
		}

		if(
		0x200D == code // Cf       ZERO WIDTH JOINER
		){
			return ZWJ;
		}

		if(
		0x2640 == code || // So       FEMALE SIGN
		0x2642 == code || // So       MALE SIGN
		(0x2695 <= code && code <= 0x2696) || // So   [2] STAFF OF AESCULAPIUS..SCALES
		0x2708 == code || // So       AIRPLANE
		0x2764 == code || // So       HEAVY BLACK HEART
		0x1F308 == code || // So       RAINBOW
		0x1F33E == code || // So       EAR OF RICE
		0x1F373 == code || // So       COOKING
		0x1F393 == code || // So       GRADUATION CAP
		0x1F3A4 == code || // So       MICROPHONE
		0x1F3A8 == code || // So       ARTIST PALETTE
		0x1F3EB == code || // So       SCHOOL
		0x1F3ED == code || // So       FACTORY
		0x1F48B == code || // So       KISS MARK
		(0x1F4BB <= code && code <= 0x1F4BC) || // So   [2] PERSONAL COMPUTER..BRIEFCASE
		0x1F527 == code || // So       WRENCH
		0x1F52C == code || // So       MICROSCOPE
		0x1F5E8 == code || // So       LEFT SPEECH BUBBLE
		0x1F680 == code || // So       ROCKET
		0x1F692 == code // So       FIRE ENGINE
		){
			return Glue_After_Zwj;
		}

		if(
		(0x1F466 <= code && code <= 0x1F469) // So   [4] BOY..WOMAN
		){
			return E_Base_GAZ;
		}
		
		
		//all unlisted characters have a grapheme break property of "Other"
		return Other;
	}
	return this;
}

if ( true && module.exports) {
    module.exports = GraphemeSplitter;
}


/***/ }),

/***/ 177:
/***/ (function(module, exports, __webpack_require__) {

var isarray = __webpack_require__(321)

/**
 * Expose `pathToRegexp`.
 */
module.exports = pathToRegexp
module.exports.parse = parse
module.exports.compile = compile
module.exports.tokensToFunction = tokensToFunction
module.exports.tokensToRegExp = tokensToRegExp

/**
 * The main path matching regexp utility.
 *
 * @type {RegExp}
 */
var PATH_REGEXP = new RegExp([
  // Match escaped characters that would otherwise appear in future matches.
  // This allows the user to escape special characters that won't transform.
  '(\\\\.)',
  // Match Express-style parameters and un-named parameters with a prefix
  // and optional suffixes. Matches appear as:
  //
  // "/:test(\\d+)?" => ["/", "test", "\d+", undefined, "?", undefined]
  // "/route(\\d+)"  => [undefined, undefined, undefined, "\d+", undefined, undefined]
  // "/*"            => ["/", undefined, undefined, undefined, undefined, "*"]
  '([\\/.])?(?:(?:\\:(\\w+)(?:\\(((?:\\\\.|[^\\\\()])+)\\))?|\\(((?:\\\\.|[^\\\\()])+)\\))([+*?])?|(\\*))'
].join('|'), 'g')

/**
 * Parse a string for the raw tokens.
 *
 * @param  {string}  str
 * @param  {Object=} options
 * @return {!Array}
 */
function parse (str, options) {
  var tokens = []
  var key = 0
  var index = 0
  var path = ''
  var defaultDelimiter = options && options.delimiter || '/'
  var res

  while ((res = PATH_REGEXP.exec(str)) != null) {
    var m = res[0]
    var escaped = res[1]
    var offset = res.index
    path += str.slice(index, offset)
    index = offset + m.length

    // Ignore already escaped sequences.
    if (escaped) {
      path += escaped[1]
      continue
    }

    var next = str[index]
    var prefix = res[2]
    var name = res[3]
    var capture = res[4]
    var group = res[5]
    var modifier = res[6]
    var asterisk = res[7]

    // Push the current path onto the tokens.
    if (path) {
      tokens.push(path)
      path = ''
    }

    var partial = prefix != null && next != null && next !== prefix
    var repeat = modifier === '+' || modifier === '*'
    var optional = modifier === '?' || modifier === '*'
    var delimiter = res[2] || defaultDelimiter
    var pattern = capture || group

    tokens.push({
      name: name || key++,
      prefix: prefix || '',
      delimiter: delimiter,
      optional: optional,
      repeat: repeat,
      partial: partial,
      asterisk: !!asterisk,
      pattern: pattern ? escapeGroup(pattern) : (asterisk ? '.*' : '[^' + escapeString(delimiter) + ']+?')
    })
  }

  // Match any characters still remaining.
  if (index < str.length) {
    path += str.substr(index)
  }

  // If the path exists, push it onto the end.
  if (path) {
    tokens.push(path)
  }

  return tokens
}

/**
 * Compile a string to a template function for the path.
 *
 * @param  {string}             str
 * @param  {Object=}            options
 * @return {!function(Object=, Object=)}
 */
function compile (str, options) {
  return tokensToFunction(parse(str, options), options)
}

/**
 * Prettier encoding of URI path segments.
 *
 * @param  {string}
 * @return {string}
 */
function encodeURIComponentPretty (str) {
  return encodeURI(str).replace(/[\/?#]/g, function (c) {
    return '%' + c.charCodeAt(0).toString(16).toUpperCase()
  })
}

/**
 * Encode the asterisk parameter. Similar to `pretty`, but allows slashes.
 *
 * @param  {string}
 * @return {string}
 */
function encodeAsterisk (str) {
  return encodeURI(str).replace(/[?#]/g, function (c) {
    return '%' + c.charCodeAt(0).toString(16).toUpperCase()
  })
}

/**
 * Expose a method for transforming tokens into the path function.
 */
function tokensToFunction (tokens, options) {
  // Compile all the tokens into regexps.
  var matches = new Array(tokens.length)

  // Compile all the patterns before compilation.
  for (var i = 0; i < tokens.length; i++) {
    if (typeof tokens[i] === 'object') {
      matches[i] = new RegExp('^(?:' + tokens[i].pattern + ')$', flags(options))
    }
  }

  return function (obj, opts) {
    var path = ''
    var data = obj || {}
    var options = opts || {}
    var encode = options.pretty ? encodeURIComponentPretty : encodeURIComponent

    for (var i = 0; i < tokens.length; i++) {
      var token = tokens[i]

      if (typeof token === 'string') {
        path += token

        continue
      }

      var value = data[token.name]
      var segment

      if (value == null) {
        if (token.optional) {
          // Prepend partial segment prefixes.
          if (token.partial) {
            path += token.prefix
          }

          continue
        } else {
          throw new TypeError('Expected "' + token.name + '" to be defined')
        }
      }

      if (isarray(value)) {
        if (!token.repeat) {
          throw new TypeError('Expected "' + token.name + '" to not repeat, but received `' + JSON.stringify(value) + '`')
        }

        if (value.length === 0) {
          if (token.optional) {
            continue
          } else {
            throw new TypeError('Expected "' + token.name + '" to not be empty')
          }
        }

        for (var j = 0; j < value.length; j++) {
          segment = encode(value[j])

          if (!matches[i].test(segment)) {
            throw new TypeError('Expected all "' + token.name + '" to match "' + token.pattern + '", but received `' + JSON.stringify(segment) + '`')
          }

          path += (j === 0 ? token.prefix : token.delimiter) + segment
        }

        continue
      }

      segment = token.asterisk ? encodeAsterisk(value) : encode(value)

      if (!matches[i].test(segment)) {
        throw new TypeError('Expected "' + token.name + '" to match "' + token.pattern + '", but received "' + segment + '"')
      }

      path += token.prefix + segment
    }

    return path
  }
}

/**
 * Escape a regular expression string.
 *
 * @param  {string} str
 * @return {string}
 */
function escapeString (str) {
  return str.replace(/([.+*?=^!:${}()[\]|\/\\])/g, '\\$1')
}

/**
 * Escape the capturing group by escaping special characters and meaning.
 *
 * @param  {string} group
 * @return {string}
 */
function escapeGroup (group) {
  return group.replace(/([=!:$\/()])/g, '\\$1')
}

/**
 * Attach the keys as a property of the regexp.
 *
 * @param  {!RegExp} re
 * @param  {Array}   keys
 * @return {!RegExp}
 */
function attachKeys (re, keys) {
  re.keys = keys
  return re
}

/**
 * Get the flags for a regexp from the options.
 *
 * @param  {Object} options
 * @return {string}
 */
function flags (options) {
  return options && options.sensitive ? '' : 'i'
}

/**
 * Pull out keys from a regexp.
 *
 * @param  {!RegExp} path
 * @param  {!Array}  keys
 * @return {!RegExp}
 */
function regexpToRegexp (path, keys) {
  // Use a negative lookahead to match only capturing groups.
  var groups = path.source.match(/\((?!\?)/g)

  if (groups) {
    for (var i = 0; i < groups.length; i++) {
      keys.push({
        name: i,
        prefix: null,
        delimiter: null,
        optional: false,
        repeat: false,
        partial: false,
        asterisk: false,
        pattern: null
      })
    }
  }

  return attachKeys(path, keys)
}

/**
 * Transform an array into a regexp.
 *
 * @param  {!Array}  path
 * @param  {Array}   keys
 * @param  {!Object} options
 * @return {!RegExp}
 */
function arrayToRegexp (path, keys, options) {
  var parts = []

  for (var i = 0; i < path.length; i++) {
    parts.push(pathToRegexp(path[i], keys, options).source)
  }

  var regexp = new RegExp('(?:' + parts.join('|') + ')', flags(options))

  return attachKeys(regexp, keys)
}

/**
 * Create a path regexp from string input.
 *
 * @param  {string}  path
 * @param  {!Array}  keys
 * @param  {!Object} options
 * @return {!RegExp}
 */
function stringToRegexp (path, keys, options) {
  return tokensToRegExp(parse(path, options), keys, options)
}

/**
 * Expose a function for taking tokens and returning a RegExp.
 *
 * @param  {!Array}          tokens
 * @param  {(Array|Object)=} keys
 * @param  {Object=}         options
 * @return {!RegExp}
 */
function tokensToRegExp (tokens, keys, options) {
  if (!isarray(keys)) {
    options = /** @type {!Object} */ (keys || options)
    keys = []
  }

  options = options || {}

  var strict = options.strict
  var end = options.end !== false
  var route = ''

  // Iterate over the tokens and create our regexp string.
  for (var i = 0; i < tokens.length; i++) {
    var token = tokens[i]

    if (typeof token === 'string') {
      route += escapeString(token)
    } else {
      var prefix = escapeString(token.prefix)
      var capture = '(?:' + token.pattern + ')'

      keys.push(token)

      if (token.repeat) {
        capture += '(?:' + prefix + capture + ')*'
      }

      if (token.optional) {
        if (!token.partial) {
          capture = '(?:' + prefix + '(' + capture + '))?'
        } else {
          capture = prefix + '(' + capture + ')?'
        }
      } else {
        capture = prefix + '(' + capture + ')'
      }

      route += capture
    }
  }

  var delimiter = escapeString(options.delimiter || '/')
  var endsWithDelimiter = route.slice(-delimiter.length) === delimiter

  // In non-strict mode we allow a slash at the end of match. If the path to
  // match already ends with a slash, we remove it for consistency. The slash
  // is valid at the end of a path match, not in the middle. This is important
  // in non-ending mode, where "/test/" shouldn't match "/test//route".
  if (!strict) {
    route = (endsWithDelimiter ? route.slice(0, -delimiter.length) : route) + '(?:' + delimiter + '(?=$))?'
  }

  if (end) {
    route += '$'
  } else {
    // In non-ending mode, we need the capturing groups to match as much as
    // possible by using a positive lookahead to the end or next path segment.
    route += strict && endsWithDelimiter ? '' : '(?=' + delimiter + '|$)'
  }

  return attachKeys(new RegExp('^' + route, flags(options)), keys)
}

/**
 * Normalize the given path string, returning a regular expression.
 *
 * An empty array can be passed in for the keys, which will hold the
 * placeholder key descriptions. For example, using `/user/:id`, `keys` will
 * contain `[{ name: 'id', delimiter: '/', optional: false, repeat: false }]`.
 *
 * @param  {(string|RegExp|Array)} path
 * @param  {(Array|Object)=}       keys
 * @param  {Object=}               options
 * @return {!RegExp}
 */
function pathToRegexp (path, keys, options) {
  if (!isarray(keys)) {
    options = /** @type {!Object} */ (keys || options)
    keys = []
  }

  options = options || {}

  if (path instanceof RegExp) {
    return regexpToRegexp(path, /** @type {!Array} */ (keys))
  }

  if (isarray(path)) {
    return arrayToRegexp(/** @type {!Array} */ (path), /** @type {!Array} */ (keys), options)
  }

  return stringToRegexp(/** @type {string} */ (path), /** @type {!Array} */ (keys), options)
}


/***/ }),

/***/ 18:
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var undefined;

var $SyntaxError = SyntaxError;
var $Function = Function;
var $TypeError = TypeError;

// eslint-disable-next-line consistent-return
var getEvalledConstructor = function (expressionSyntax) {
	try {
		return $Function('"use strict"; return (' + expressionSyntax + ').constructor;')();
	} catch (e) {}
};

var $gOPD = Object.getOwnPropertyDescriptor;
if ($gOPD) {
	try {
		$gOPD({}, '');
	} catch (e) {
		$gOPD = null; // this is IE 8, which has a broken gOPD
	}
}

var throwTypeError = function () {
	throw new $TypeError();
};
var ThrowTypeError = $gOPD
	? (function () {
		try {
			// eslint-disable-next-line no-unused-expressions, no-caller, no-restricted-properties
			arguments.callee; // IE 8 does not throw here
			return throwTypeError;
		} catch (calleeThrows) {
			try {
				// IE 8 throws on Object.getOwnPropertyDescriptor(arguments, '')
				return $gOPD(arguments, 'callee').get;
			} catch (gOPDthrows) {
				return throwTypeError;
			}
		}
	}())
	: throwTypeError;

var hasSymbols = __webpack_require__(57)();

var getProto = Object.getPrototypeOf || function (x) { return x.__proto__; }; // eslint-disable-line no-proto

var needsEval = {};

var TypedArray = typeof Uint8Array === 'undefined' ? undefined : getProto(Uint8Array);

var INTRINSICS = {
	'%AggregateError%': typeof AggregateError === 'undefined' ? undefined : AggregateError,
	'%Array%': Array,
	'%ArrayBuffer%': typeof ArrayBuffer === 'undefined' ? undefined : ArrayBuffer,
	'%ArrayIteratorPrototype%': hasSymbols ? getProto([][Symbol.iterator]()) : undefined,
	'%AsyncFromSyncIteratorPrototype%': undefined,
	'%AsyncFunction%': needsEval,
	'%AsyncGenerator%': needsEval,
	'%AsyncGeneratorFunction%': needsEval,
	'%AsyncIteratorPrototype%': needsEval,
	'%Atomics%': typeof Atomics === 'undefined' ? undefined : Atomics,
	'%BigInt%': typeof BigInt === 'undefined' ? undefined : BigInt,
	'%Boolean%': Boolean,
	'%DataView%': typeof DataView === 'undefined' ? undefined : DataView,
	'%Date%': Date,
	'%decodeURI%': decodeURI,
	'%decodeURIComponent%': decodeURIComponent,
	'%encodeURI%': encodeURI,
	'%encodeURIComponent%': encodeURIComponent,
	'%Error%': Error,
	'%eval%': eval, // eslint-disable-line no-eval
	'%EvalError%': EvalError,
	'%Float32Array%': typeof Float32Array === 'undefined' ? undefined : Float32Array,
	'%Float64Array%': typeof Float64Array === 'undefined' ? undefined : Float64Array,
	'%FinalizationRegistry%': typeof FinalizationRegistry === 'undefined' ? undefined : FinalizationRegistry,
	'%Function%': $Function,
	'%GeneratorFunction%': needsEval,
	'%Int8Array%': typeof Int8Array === 'undefined' ? undefined : Int8Array,
	'%Int16Array%': typeof Int16Array === 'undefined' ? undefined : Int16Array,
	'%Int32Array%': typeof Int32Array === 'undefined' ? undefined : Int32Array,
	'%isFinite%': isFinite,
	'%isNaN%': isNaN,
	'%IteratorPrototype%': hasSymbols ? getProto(getProto([][Symbol.iterator]())) : undefined,
	'%JSON%': typeof JSON === 'object' ? JSON : undefined,
	'%Map%': typeof Map === 'undefined' ? undefined : Map,
	'%MapIteratorPrototype%': typeof Map === 'undefined' || !hasSymbols ? undefined : getProto(new Map()[Symbol.iterator]()),
	'%Math%': Math,
	'%Number%': Number,
	'%Object%': Object,
	'%parseFloat%': parseFloat,
	'%parseInt%': parseInt,
	'%Promise%': typeof Promise === 'undefined' ? undefined : Promise,
	'%Proxy%': typeof Proxy === 'undefined' ? undefined : Proxy,
	'%RangeError%': RangeError,
	'%ReferenceError%': ReferenceError,
	'%Reflect%': typeof Reflect === 'undefined' ? undefined : Reflect,
	'%RegExp%': RegExp,
	'%Set%': typeof Set === 'undefined' ? undefined : Set,
	'%SetIteratorPrototype%': typeof Set === 'undefined' || !hasSymbols ? undefined : getProto(new Set()[Symbol.iterator]()),
	'%SharedArrayBuffer%': typeof SharedArrayBuffer === 'undefined' ? undefined : SharedArrayBuffer,
	'%String%': String,
	'%StringIteratorPrototype%': hasSymbols ? getProto(''[Symbol.iterator]()) : undefined,
	'%Symbol%': hasSymbols ? Symbol : undefined,
	'%SyntaxError%': $SyntaxError,
	'%ThrowTypeError%': ThrowTypeError,
	'%TypedArray%': TypedArray,
	'%TypeError%': $TypeError,
	'%Uint8Array%': typeof Uint8Array === 'undefined' ? undefined : Uint8Array,
	'%Uint8ClampedArray%': typeof Uint8ClampedArray === 'undefined' ? undefined : Uint8ClampedArray,
	'%Uint16Array%': typeof Uint16Array === 'undefined' ? undefined : Uint16Array,
	'%Uint32Array%': typeof Uint32Array === 'undefined' ? undefined : Uint32Array,
	'%URIError%': URIError,
	'%WeakMap%': typeof WeakMap === 'undefined' ? undefined : WeakMap,
	'%WeakRef%': typeof WeakRef === 'undefined' ? undefined : WeakRef,
	'%WeakSet%': typeof WeakSet === 'undefined' ? undefined : WeakSet
};

var doEval = function doEval(name) {
	var value;
	if (name === '%AsyncFunction%') {
		value = getEvalledConstructor('async function () {}');
	} else if (name === '%GeneratorFunction%') {
		value = getEvalledConstructor('function* () {}');
	} else if (name === '%AsyncGeneratorFunction%') {
		value = getEvalledConstructor('async function* () {}');
	} else if (name === '%AsyncGenerator%') {
		var fn = doEval('%AsyncGeneratorFunction%');
		if (fn) {
			value = fn.prototype;
		}
	} else if (name === '%AsyncIteratorPrototype%') {
		var gen = doEval('%AsyncGenerator%');
		if (gen) {
			value = getProto(gen.prototype);
		}
	}

	INTRINSICS[name] = value;

	return value;
};

var LEGACY_ALIASES = {
	'%ArrayBufferPrototype%': ['ArrayBuffer', 'prototype'],
	'%ArrayPrototype%': ['Array', 'prototype'],
	'%ArrayProto_entries%': ['Array', 'prototype', 'entries'],
	'%ArrayProto_forEach%': ['Array', 'prototype', 'forEach'],
	'%ArrayProto_keys%': ['Array', 'prototype', 'keys'],
	'%ArrayProto_values%': ['Array', 'prototype', 'values'],
	'%AsyncFunctionPrototype%': ['AsyncFunction', 'prototype'],
	'%AsyncGenerator%': ['AsyncGeneratorFunction', 'prototype'],
	'%AsyncGeneratorPrototype%': ['AsyncGeneratorFunction', 'prototype', 'prototype'],
	'%BooleanPrototype%': ['Boolean', 'prototype'],
	'%DataViewPrototype%': ['DataView', 'prototype'],
	'%DatePrototype%': ['Date', 'prototype'],
	'%ErrorPrototype%': ['Error', 'prototype'],
	'%EvalErrorPrototype%': ['EvalError', 'prototype'],
	'%Float32ArrayPrototype%': ['Float32Array', 'prototype'],
	'%Float64ArrayPrototype%': ['Float64Array', 'prototype'],
	'%FunctionPrototype%': ['Function', 'prototype'],
	'%Generator%': ['GeneratorFunction', 'prototype'],
	'%GeneratorPrototype%': ['GeneratorFunction', 'prototype', 'prototype'],
	'%Int8ArrayPrototype%': ['Int8Array', 'prototype'],
	'%Int16ArrayPrototype%': ['Int16Array', 'prototype'],
	'%Int32ArrayPrototype%': ['Int32Array', 'prototype'],
	'%JSONParse%': ['JSON', 'parse'],
	'%JSONStringify%': ['JSON', 'stringify'],
	'%MapPrototype%': ['Map', 'prototype'],
	'%NumberPrototype%': ['Number', 'prototype'],
	'%ObjectPrototype%': ['Object', 'prototype'],
	'%ObjProto_toString%': ['Object', 'prototype', 'toString'],
	'%ObjProto_valueOf%': ['Object', 'prototype', 'valueOf'],
	'%PromisePrototype%': ['Promise', 'prototype'],
	'%PromiseProto_then%': ['Promise', 'prototype', 'then'],
	'%Promise_all%': ['Promise', 'all'],
	'%Promise_reject%': ['Promise', 'reject'],
	'%Promise_resolve%': ['Promise', 'resolve'],
	'%RangeErrorPrototype%': ['RangeError', 'prototype'],
	'%ReferenceErrorPrototype%': ['ReferenceError', 'prototype'],
	'%RegExpPrototype%': ['RegExp', 'prototype'],
	'%SetPrototype%': ['Set', 'prototype'],
	'%SharedArrayBufferPrototype%': ['SharedArrayBuffer', 'prototype'],
	'%StringPrototype%': ['String', 'prototype'],
	'%SymbolPrototype%': ['Symbol', 'prototype'],
	'%SyntaxErrorPrototype%': ['SyntaxError', 'prototype'],
	'%TypedArrayPrototype%': ['TypedArray', 'prototype'],
	'%TypeErrorPrototype%': ['TypeError', 'prototype'],
	'%Uint8ArrayPrototype%': ['Uint8Array', 'prototype'],
	'%Uint8ClampedArrayPrototype%': ['Uint8ClampedArray', 'prototype'],
	'%Uint16ArrayPrototype%': ['Uint16Array', 'prototype'],
	'%Uint32ArrayPrototype%': ['Uint32Array', 'prototype'],
	'%URIErrorPrototype%': ['URIError', 'prototype'],
	'%WeakMapPrototype%': ['WeakMap', 'prototype'],
	'%WeakSetPrototype%': ['WeakSet', 'prototype']
};

var bind = __webpack_require__(33);
var hasOwn = __webpack_require__(41);
var $concat = bind.call(Function.call, Array.prototype.concat);
var $spliceApply = bind.call(Function.apply, Array.prototype.splice);
var $replace = bind.call(Function.call, String.prototype.replace);
var $strSlice = bind.call(Function.call, String.prototype.slice);

/* adapted from https://github.com/lodash/lodash/blob/4.17.15/dist/lodash.js#L6735-L6744 */
var rePropName = /[^%.[\]]+|\[(?:(-?\d+(?:\.\d+)?)|(["'])((?:(?!\2)[^\\]|\\.)*?)\2)\]|(?=(?:\.|\[\])(?:\.|\[\]|%$))/g;
var reEscapeChar = /\\(\\)?/g; /** Used to match backslashes in property paths. */
var stringToPath = function stringToPath(string) {
	var first = $strSlice(string, 0, 1);
	var last = $strSlice(string, -1);
	if (first === '%' && last !== '%') {
		throw new $SyntaxError('invalid intrinsic syntax, expected closing `%`');
	} else if (last === '%' && first !== '%') {
		throw new $SyntaxError('invalid intrinsic syntax, expected opening `%`');
	}
	var result = [];
	$replace(string, rePropName, function (match, number, quote, subString) {
		result[result.length] = quote ? $replace(subString, reEscapeChar, '$1') : number || match;
	});
	return result;
};
/* end adaptation */

var getBaseIntrinsic = function getBaseIntrinsic(name, allowMissing) {
	var intrinsicName = name;
	var alias;
	if (hasOwn(LEGACY_ALIASES, intrinsicName)) {
		alias = LEGACY_ALIASES[intrinsicName];
		intrinsicName = '%' + alias[0] + '%';
	}

	if (hasOwn(INTRINSICS, intrinsicName)) {
		var value = INTRINSICS[intrinsicName];
		if (value === needsEval) {
			value = doEval(intrinsicName);
		}
		if (typeof value === 'undefined' && !allowMissing) {
			throw new $TypeError('intrinsic ' + name + ' exists, but is not available. Please file an issue!');
		}

		return {
			alias: alias,
			name: intrinsicName,
			value: value
		};
	}

	throw new $SyntaxError('intrinsic ' + name + ' does not exist!');
};

module.exports = function GetIntrinsic(name, allowMissing) {
	if (typeof name !== 'string' || name.length === 0) {
		throw new $TypeError('intrinsic name must be a non-empty string');
	}
	if (arguments.length > 1 && typeof allowMissing !== 'boolean') {
		throw new $TypeError('"allowMissing" argument must be a boolean');
	}

	var parts = stringToPath(name);
	var intrinsicBaseName = parts.length > 0 ? parts[0] : '';

	var intrinsic = getBaseIntrinsic('%' + intrinsicBaseName + '%', allowMissing);
	var intrinsicRealName = intrinsic.name;
	var value = intrinsic.value;
	var skipFurtherCaching = false;

	var alias = intrinsic.alias;
	if (alias) {
		intrinsicBaseName = alias[0];
		$spliceApply(parts, $concat([0, 1], alias));
	}

	for (var i = 1, isOwn = true; i < parts.length; i += 1) {
		var part = parts[i];
		var first = $strSlice(part, 0, 1);
		var last = $strSlice(part, -1);
		if (
			(
				(first === '"' || first === "'" || first === '`')
				|| (last === '"' || last === "'" || last === '`')
			)
			&& first !== last
		) {
			throw new $SyntaxError('property names with quotes must have matching quotes');
		}
		if (part === 'constructor' || !isOwn) {
			skipFurtherCaching = true;
		}

		intrinsicBaseName += '.' + part;
		intrinsicRealName = '%' + intrinsicBaseName + '%';

		if (hasOwn(INTRINSICS, intrinsicRealName)) {
			value = INTRINSICS[intrinsicRealName];
		} else if (value != null) {
			if (!(part in value)) {
				if (!allowMissing) {
					throw new $TypeError('base intrinsic for ' + name + ' exists, but the property is not available.');
				}
				return void undefined;
			}
			if ($gOPD && (i + 1) >= parts.length) {
				var desc = $gOPD(value, part);
				isOwn = !!desc;

				// By convention, when a data property is converted to an accessor
				// property to emulate a data property that does not suffer from
				// the override mistake, that accessor's getter is marked with
				// an `originalValue` property. Here, when we detect this, we
				// uphold the illusion by pretending to see that original data
				// property, i.e., returning the value rather than the getter
				// itself.
				if (isOwn && 'get' in desc && !('originalValue' in desc.get)) {
					value = desc.get;
				} else {
					value = value[part];
				}
			} else {
				isOwn = hasOwn(value, part);
				value = value[part];
			}

			if (isOwn && !skipFurtherCaching) {
				INTRINSICS[intrinsicRealName] = value;
			}
		}
	}
	return value;
};


/***/ }),

/***/ 180:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";

// EXPORTS
__webpack_require__.d(__webpack_exports__, "a", function() { return /* binding */ Fill; });
__webpack_require__.d(__webpack_exports__, "b", function() { return /* binding */ DisplayOptions; });

// EXTERNAL MODULE: external ["wp","element"]
var external_wp_element_ = __webpack_require__(0);

// EXTERNAL MODULE: external ["wp","components"]
var external_wp_components_ = __webpack_require__(4);

// EXTERNAL MODULE: external ["wp","data"]
var external_wp_data_ = __webpack_require__(8);

// EXTERNAL MODULE: external ["wp","i18n"]
var external_wp_i18n_ = __webpack_require__(2);

// EXTERNAL MODULE: external ["wc","data"]
var external_wc_data_ = __webpack_require__(12);

// EXTERNAL MODULE: external ["wc","tracks"]
var external_wc_tracks_ = __webpack_require__(17);

// CONCATENATED MODULE: ./client/activity-panel/display-options/icons/display.js


/**
 * External dependencies
 */

const DisplayIcon = () => Object(external_wp_element_["createElement"])(external_wp_element_["Fragment"], null, Object(external_wp_element_["createElement"])("svg", {
  className: "woocommerce-layout__activity-panel-tab-icon",
  width: "24",
  height: "24",
  viewBox: "3 3 24 24",
  fill: "none",
  xmlns: "http://www.w3.org/2000/svg"
}, Object(external_wp_element_["createElement"])("path", {
  d: "M13.8053 15.3982C13.8053 15.7965 13.4867 16.1947 13.0089 16.1947H6.79646C6.55752 16.1947 6.39823 16.115 6.23894 15.9558C6.07965 15.7965 6 15.6372 6 15.3982V6.79646C6 6.63717 6.15929 6.39823 6.23894 6.23894C6.39823 6.07965 6.55752 6 6.79646 6H13.0089C13.4071 6 13.8053 6.31858 13.8053 6.79646V15.3982Z",
  strokeWidth: "1.5",
  strokeLinecap: "round",
  strokeLinejoin: "round"
}), Object(external_wp_element_["createElement"])("path", {
  d: "M23.9203 10.6195C23.9203 11.0177 23.6017 11.4159 23.1238 11.4159H16.9115C16.6725 11.4159 16.5132 11.3363 16.3539 11.177C16.1946 11.0177 16.115 10.8584 16.115 10.6195V6.79646C16.115 6.39823 16.4336 6 16.9115 6H23.1238C23.5221 6 23.9203 6.31858 23.9203 6.79646V10.6195Z",
  strokeWidth: "1.5",
  strokeLinecap: "round",
  strokeLinejoin: "round"
}), Object(external_wp_element_["createElement"])("path", {
  d: "M13.8053 23.2035C13.8053 23.4424 13.7257 23.6017 13.5664 23.761C13.4071 23.9203 13.2478 23.9999 13.0089 23.9999H6.79646C6.39823 23.9999 6 23.6813 6 23.2035V19.3804C6 19.1415 6.07965 18.9822 6.23894 18.8229C6.39823 18.6636 6.55752 18.584 6.79646 18.584H13.0089C13.4071 18.584 13.8053 18.9026 13.8053 19.3804V23.2035Z",
  strokeWidth: "1.5",
  strokeLinecap: "round",
  strokeLinejoin: "round"
}), Object(external_wp_element_["createElement"])("path", {
  d: "M16.9912 23.9999C16.7522 23.9999 16.5929 23.9202 16.4336 23.7609C16.2743 23.6016 16.1947 23.4423 16.1947 23.2034V14.6016C16.1947 14.3627 16.2743 14.2034 16.4336 14.0441C16.5929 13.8848 16.7522 13.8052 16.9912 13.8052H23.2036C23.4425 13.8052 23.6018 13.8848 23.7611 14.0441C23.9204 14.2034 24 14.3627 24 14.6016V23.2034C24 23.6016 23.6814 23.9999 23.2036 23.9999H16.9912Z",
  strokeWidth: "1.5",
  strokeLinecap: "round",
  strokeLinejoin: "round"
})), Object(external_wp_i18n_["__"])('Display', 'woocommerce-admin'));
// CONCATENATED MODULE: ./client/activity-panel/display-options/icons/single-column.js

const SingleColumnIcon = () => Object(external_wp_element_["createElement"])("svg", {
  className: "woocommerce-layout__activity-panel-tab-icon",
  width: "12",
  height: "14",
  viewBox: "0 0 12 14",
  fill: "none",
  xmlns: "http://www.w3.org/2000/svg"
}, Object(external_wp_element_["createElement"])("rect", {
  x: "0.5",
  y: "0.5",
  width: "11",
  height: "13",
  strokeWidth: "1"
}));
// CONCATENATED MODULE: ./client/activity-panel/display-options/icons/two-columns.js

const TwoColumnsIcon = () => Object(external_wp_element_["createElement"])("svg", {
  className: "woocommerce-layout__activity-panel-tab-icon",
  width: "18",
  height: "14",
  viewBox: "0 0 18 14",
  fill: "none",
  xmlns: "http://www.w3.org/2000/svg"
}, Object(external_wp_element_["createElement"])("rect", {
  x: "0.5",
  y: "0.5",
  width: "7",
  height: "13",
  strokeWidth: "1"
}), Object(external_wp_element_["createElement"])("rect", {
  x: "9.5",
  y: "0.5",
  width: "7",
  height: "13",
  strokeWidth: "1"
}));
// CONCATENATED MODULE: ./client/activity-panel/display-options/index.js


/**
 * External dependencies
 */





/**
 * Internal dependencies
 */




const {
  Fill,
  Slot
} = Object(external_wp_components_["createSlotFill"])('DisplayOptions');
Fill.Slot = Slot;

const LAYOUTS = [{
  value: 'single_column',
  label: Object(external_wp_element_["createElement"])(external_wp_element_["Fragment"], null, Object(external_wp_element_["createElement"])(SingleColumnIcon, null), Object(external_wp_i18n_["__"])('Single column', 'woocommerce-admin'))
}, {
  value: 'two_columns',
  label: Object(external_wp_element_["createElement"])(external_wp_element_["Fragment"], null, Object(external_wp_element_["createElement"])(TwoColumnsIcon, null), Object(external_wp_i18n_["__"])('Two columns', 'woocommerce-admin'))
}];
const DisplayOptions = () => {
  const {
    defaultHomescreenLayout,
    taskListComplete,
    isTaskListHidden
  } = Object(external_wp_data_["useSelect"])(select => {
    const {
      getOption
    } = select(external_wc_data_["OPTIONS_STORE_NAME"]);
    const {
      getTaskList
    } = select(external_wc_data_["ONBOARDING_STORE_NAME"]);
    const taskList = getTaskList('setup');
    return {
      defaultHomescreenLayout: getOption('woocommerce_default_homepage_layout') || 'single_column',
      taskListComplete: taskList === null || taskList === void 0 ? void 0 : taskList.isComplete,
      isTaskListHidden: taskList === null || taskList === void 0 ? void 0 : taskList.isHidden
    };
  });
  const {
    updateUserPreferences,
    homepage_layout: layout
  } = Object(external_wc_data_["useUserPreferences"])();
  const shouldShowStoreLinks = taskListComplete || isTaskListHidden;
  const hasTwoColumnContent = shouldShowStoreLinks || window.wcAdminFeatures.analytics;
  return Object(external_wp_element_["createElement"])(Slot, null, fills => {
    // If there is no fill to render and only single column content, don't render the display.
    if (fills.length === 0 && !hasTwoColumnContent) {
      return null;
    }

    return Object(external_wp_element_["createElement"])(external_wp_components_["DropdownMenu"], {
      icon: Object(external_wp_element_["createElement"])(DisplayIcon, null)
      /* translators: button label text should, if possible, be under 16 characters. */
      ,
      label: Object(external_wp_i18n_["__"])('Display options', 'woocommerce-admin'),
      toggleProps: {
        className: 'woocommerce-layout__activity-panel-tab display-options',
        onClick: () => Object(external_wc_tracks_["recordEvent"])('homescreen_display_click')
      },
      popoverProps: {
        className: 'woocommerce-layout__activity-panel-popover'
      }
    }, _ref => {
      let {
        onClose
      } = _ref;
      return Object(external_wp_element_["createElement"])(external_wp_element_["Fragment"], null, fills, hasTwoColumnContent ? Object(external_wp_element_["createElement"])(external_wp_components_["MenuGroup"], {
        className: "woocommerce-layout__homescreen-display-options",
        label: Object(external_wp_i18n_["__"])('Layout', 'woocommerce-admin')
      }, Object(external_wp_element_["createElement"])(external_wp_components_["MenuItemsChoice"], {
        choices: LAYOUTS,
        onSelect: newLayout => {
          updateUserPreferences({
            homepage_layout: newLayout
          });
          onClose();
          Object(external_wc_tracks_["recordEvent"])('homescreen_display_option', {
            display_option: newLayout
          });
        },
        value: layout || defaultHomescreenLayout
      })) : null);
    });
  });
};

/***/ }),

/***/ 184:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "a", function() { return NoMatch; });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(2);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(4);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _woocommerce_experimental__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(19);
/* harmony import */ var _woocommerce_experimental__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_experimental__WEBPACK_IMPORTED_MODULE_3__);


/**
 * External dependencies
 */




const NoMatch = () => {
  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("div", {
    className: "woocommerce-layout__no-match"
  }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__["Card"], null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__["CardBody"], null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_woocommerce_experimental__WEBPACK_IMPORTED_MODULE_3__["Text"], null, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Sorry, you are not allowed to access this page.', 'woocommerce-admin')))));
};



/***/ }),

/***/ 185:
/***/ (function(module, exports) {



/***/ }),

/***/ 186:
/***/ (function(module, exports) {



/***/ }),

/***/ 187:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "a", function() { return getUnreadNotesCount; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "b", function() { return hasValidNotes; });
/* unused harmony export truncate */
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "c", function() { return truncateRenderableHTML; });
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(5);
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var grapheme_splitter__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(176);
/* harmony import */ var grapheme_splitter__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(grapheme_splitter__WEBPACK_IMPORTED_MODULE_1__);
/**
 * External dependencies
 */


/**
 * Get the count of the unread notes from the received list.
 *
 * @param {Array} notes - List of notes, contains read and unread notes.
 * @param {number} lastRead - The timestamp that the user read a note.
 * @return {number} - Unread notes count.
 */

function getUnreadNotesCount(notes, lastRead) {
  const unreadNotes = Object(lodash__WEBPACK_IMPORTED_MODULE_0__["filter"])(notes, note => {
    const {
      is_deleted: isDeleted,
      date_created_gmt: dateCreatedGmt,
      status
    } = note;

    if (!isDeleted) {
      const unread = !lastRead || !dateCreatedGmt || new Date(dateCreatedGmt + 'Z').getTime() > lastRead;
      return unread && status === 'unactioned';
    }
  });
  return unreadNotes.length;
}
/**
 * Verifies if there are any valid notes in the list.
 *
 * @param {Array} notes - List of notes, contains read and unread notes.
 * @return {boolean} - Whether there are valid notes or not.
 */

function hasValidNotes(notes) {
  const validNotes = Object(lodash__WEBPACK_IMPORTED_MODULE_0__["filter"])(notes, note => {
    const {
      is_deleted: isDeleted
    } = note;
    return !isDeleted;
  });
  return validNotes.length > 0;
}
/**
 * Truncates array of text characters.
 *
 * @param {Array} letters The letter array to truncate.
 * @param {number} limit number of characters to limit to
 * @param {string} separator The separator string to truncate to.
 */

const truncate = function (letters, limit) {
  let separator = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : ' ';
  let truncatedLetters = letters.slice(0, limit);

  if (letters.indexOf(separator, limit) !== limit) {
    // If there's a space in the text, we need to truncate at the space to preserve whole words.
    const index = truncatedLetters.lastIndexOf(separator);

    if (index > -1) {
      truncatedLetters = truncatedLetters.slice(0, index);
    }
  }

  return truncatedLetters.join('');
};
/**
 * Truncates characters inside of an element.
 * Currently does not count <br> as a character even though it should.
 *
 * @param {HTMLElement} element HTML element
 * @param {number} limit number of characters to limit to
 */

const truncateElement = (element, limit) => {
  const truncatedNode = document.createElement('div');
  const childNodes = Array.from(element.childNodes);
  const splitter = new grapheme_splitter__WEBPACK_IMPORTED_MODULE_1___default.a();
  let truncatedTextLength = 0;

  for (let i = 0; i < childNodes.length; i++) {
    // Deep clone.
    let clone = childNodes[i].cloneNode(true);
    const cloneNodeLetters = splitter.splitGraphemes(clone.textContent);

    if (truncatedTextLength + cloneNodeLetters.length <= limit) {
      // No problem including a whole child node, no need to consider truncating at all.
      truncatedNode.appendChild(clone);
      truncatedTextLength += cloneNodeLetters.length;
      continue;
    }

    const charactersRemaining = limit - truncatedTextLength;

    if (clone.hasChildNodes()) {
      clone = truncateElement(clone, charactersRemaining);
    } else {
      clone.textContent = truncate(cloneNodeLetters, charactersRemaining);
    }

    truncatedNode.appendChild(clone); // Exceeded limit at this point, safe to exit loop.

    break;
  }

  return truncatedNode;
};
/**
 * Truncates characters from a HTML string excluding markup. Truncated strings will be appended with ellipsis.
 *
 * @param {string} originalHTML HTML string
 * @param {number} limit number of characters to limit to
 */


const truncateRenderableHTML = (originalHTML, limit) => {
  const tempNode = document.createElement('div');
  const splitter = new grapheme_splitter__WEBPACK_IMPORTED_MODULE_1___default.a();
  tempNode.innerHTML = originalHTML;

  if (splitter.splitGraphemes(tempNode.textContent).length > limit) {
    return truncateElement(tempNode, limit).innerHTML + '...';
  }

  return originalHTML;
};

/***/ }),

/***/ 19:
/***/ (function(module, exports) {

(function() { module.exports = window["wc"]["experimental"]; }());

/***/ }),

/***/ 2:
/***/ (function(module, exports) {

(function() { module.exports = window["wp"]["i18n"]; }());

/***/ }),

/***/ 20:
/***/ (function(module, exports) {

(function() { module.exports = window["wc"]["date"]; }());

/***/ }),

/***/ 21:
/***/ (function(module, exports) {

(function() { module.exports = window["wp"]["apiFetch"]; }());

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

/***/ 27:
/***/ (function(module, exports) {

(function() { module.exports = window["wp"]["hooks"]; }());

/***/ }),

/***/ 275:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";

// EXPORTS
__webpack_require__.d(__webpack_exports__, "a", function() { return /* binding */ ABBREVIATED_NOTIFICATION_SLOT_NAME; });
__webpack_require__.d(__webpack_exports__, "b", function() { return /* binding */ AbbreviatedNotificationsPanel; });

// EXTERNAL MODULE: external ["wp","element"]
var external_wp_element_ = __webpack_require__(0);

// EXTERNAL MODULE: external ["wp","i18n"]
var external_wp_i18n_ = __webpack_require__(2);

// EXTERNAL MODULE: external ["wc","experimental"]
var external_wc_experimental_ = __webpack_require__(19);

// EXTERNAL MODULE: external ["wc","tracks"]
var external_wc_tracks_ = __webpack_require__(17);

// EXTERNAL MODULE: external ["wc","components"]
var external_wc_components_ = __webpack_require__(22);

// EXTERNAL MODULE: external ["wp","data"]
var external_wp_data_ = __webpack_require__(8);

// EXTERNAL MODULE: external ["wc","data"]
var external_wc_data_ = __webpack_require__(12);

// EXTERNAL MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/@wordpress+icons@6.3.0/node_modules/@wordpress/icons/build-module/library/page.js
var page = __webpack_require__(532);

// EXTERNAL MODULE: external ["wp","primitives"]
var external_wp_primitives_ = __webpack_require__(9);

// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/@wordpress+icons@6.3.0/node_modules/@wordpress/icons/build-module/library/comment.js


/**
 * WordPress dependencies
 */

const comment = Object(external_wp_element_["createElement"])(external_wp_primitives_["SVG"], {
  viewBox: "0 0 24 24",
  xmlns: "http://www.w3.org/2000/svg"
}, Object(external_wp_element_["createElement"])(external_wp_primitives_["Path"], {
  d: "M18 4H6c-1.1 0-2 .9-2 2v12.9c0 .6.5 1.1 1.1 1.1.3 0 .5-.1.8-.3L8.5 17H18c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm.5 11c0 .3-.2.5-.5.5H7.9l-2.4 2.4V6c0-.3.2-.5.5-.5h12c.3 0 .5.2.5.5v9z"
}));
/* harmony default export */ var library_comment = (comment);
//# sourceMappingURL=comment.js.map
// EXTERNAL MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/@wordpress+icons@6.3.0/node_modules/@wordpress/icons/build-module/library/box.js
var box = __webpack_require__(533);

// EXTERNAL MODULE: external ["wp","components"]
var external_wp_components_ = __webpack_require__(4);

// EXTERNAL MODULE: ./client/homescreen/activity-panel/orders/utils.js
var utils = __webpack_require__(88);

// EXTERNAL MODULE: ./client/homescreen/activity-panel/reviews/utils.js
var reviews_utils = __webpack_require__(115);

// EXTERNAL MODULE: ./client/dashboard/utils.js
var dashboard_utils = __webpack_require__(80);

// CONCATENATED MODULE: ./client/activity-panel/panels/inbox/icons/bell.js

const Bell = () => Object(external_wp_element_["createElement"])("svg", {
  width: "24",
  height: "24",
  viewBox: "0 0 24 24",
  xmlns: "http://www.w3.org/2000/svg"
}, Object(external_wp_element_["createElement"])("path", {
  d: "M0 0h24v24H0z",
  fill: "none"
}), Object(external_wp_element_["createElement"])("path", {
  d: "M12 22c1.1 0 2-.9 2-2h-4c0 1.1.9 2 2 2zm6-6v-5c0-3.07-1.63-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.64 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2zm-2 1H8v-6c0-2.48 1.51-4.5 4-4.5s4 2.02 4 4.5v6z"
}));
// CONCATENATED MODULE: ./client/activity-panel/panels/inbox/abbreviated-notifications-panel.js


/**
 * External dependencies
 */








/**
 * Internal dependencies
 */





const EXTENDED_TASK_LIST_ID = 'extended_task_list';
const ORDER_PANEL_ID = 'orders-panel';
const REVIEWS_PANEL_ID = 'reviews-panel';
const STOCK_PANEL_ID = 'stock-panel';
const ABBREVIATED_NOTIFICATION_SLOT_NAME = 'AbbreviatedNotification';
const AbbreviatedNotificationsPanel = _ref => {
  let {
    thingsToDoNextCount
  } = _ref;
  const {
    ordersToProcessCount,
    reviewsToModerateCount,
    stockNoticesCount,
    isSetupTaskListHidden,
    isExtendedTaskListHidden
  } = Object(external_wp_data_["useSelect"])(select => {
    var _getTaskList, _getTaskList2;

    const {
      getTaskList
    } = select(external_wc_data_["ONBOARDING_STORE_NAME"]);
    const orderStatuses = Object(utils["c" /* getOrderStatuses */])(select);
    return {
      ordersToProcessCount: Object(utils["d" /* getUnreadOrders */])(select, orderStatuses),
      reviewsToModerateCount: Object(reviews_utils["b" /* getUnapprovedReviews */])(select),
      stockNoticesCount: Object(utils["a" /* getLowStockCount */])(select),
      isSetupTaskListHidden: (_getTaskList = getTaskList('setup')) === null || _getTaskList === void 0 ? void 0 : _getTaskList.isHidden,
      isExtendedTaskListHidden: (_getTaskList2 = getTaskList('extended')) === null || _getTaskList2 === void 0 ? void 0 : _getTaskList2.isHidden
    };
  });

  const trackAbbreviatedCardClick = name => {
    Object(external_wc_tracks_["recordEvent"])('activity_panel_click', {
      task: name
    });
  };

  const {
    Slot
  } = Object(external_wp_components_["createSlotFill"])(ABBREVIATED_NOTIFICATION_SLOT_NAME);
  const isWCAdminPage = Object(dashboard_utils["f" /* isWCAdmin */])(window.location.href);
  return Object(external_wp_element_["createElement"])("div", {
    className: "woocommerce-abbreviated-notifications"
  }, thingsToDoNextCount > 0 && !isExtendedTaskListHidden && Object(external_wp_element_["createElement"])(external_wc_components_["AbbreviatedCard"], {
    className: "woocommerce-abbreviated-notification",
    icon: Object(external_wp_element_["createElement"])(Bell, null),
    href: `admin.php?page=wc-admin#${EXTENDED_TASK_LIST_ID}`,
    onClick: () => trackAbbreviatedCardClick('thingsToDoNext'),
    type: isWCAdminPage ? 'wc-admin' : 'wp-admin'
  }, Object(external_wp_element_["createElement"])(external_wc_experimental_["Text"], {
    as: "h3"
  }, Object(external_wp_i18n_["__"])('Things to do next', 'woocommerce-admin')), Object(external_wp_element_["createElement"])(external_wc_experimental_["Text"], {
    as: "p"
  }, Object(external_wp_i18n_["sprintf"])(
  /* translators: Things the user has to do */
  Object(external_wp_i18n_["_n"])('You have %d new thing to do', 'You have %d new things to do', thingsToDoNextCount, 'woocommerce-admin'), thingsToDoNextCount))), ordersToProcessCount > 0 && isSetupTaskListHidden && Object(external_wp_element_["createElement"])(external_wc_components_["AbbreviatedCard"], {
    className: "woocommerce-abbreviated-notification",
    icon: page["a" /* default */],
    href: `admin.php?page=wc-admin&opened_panel=${ORDER_PANEL_ID}`,
    onClick: () => trackAbbreviatedCardClick('ordersToProcess'),
    type: isWCAdminPage ? 'wc-admin' : 'wp-admin'
  }, Object(external_wp_element_["createElement"])(external_wc_experimental_["Text"], {
    as: "h3"
  }, Object(external_wp_i18n_["__"])('Orders to fulfill', 'woocommerce-admin')), Object(external_wp_element_["createElement"])(external_wc_experimental_["Text"], null, Object(external_wp_i18n_["sprintf"])(
  /* translators: Number of orders the user has to fulfill */
  Object(external_wp_i18n_["_n"])('You have %d order to fulfill', 'You have %d orders to fulfill', ordersToProcessCount, 'woocommerce-admin'), ordersToProcessCount))), reviewsToModerateCount > 0 && isSetupTaskListHidden && Object(external_wp_element_["createElement"])(external_wc_components_["AbbreviatedCard"], {
    className: "woocommerce-abbreviated-notification",
    icon: library_comment,
    href: `admin.php?page=wc-admin&opened_panel=${REVIEWS_PANEL_ID}`,
    onClick: () => trackAbbreviatedCardClick('reviewsToModerate'),
    type: isWCAdminPage ? 'wc-admin' : 'wp-admin'
  }, Object(external_wp_element_["createElement"])(external_wc_experimental_["Text"], {
    as: "h3"
  }, Object(external_wp_i18n_["__"])('Reviews to moderate', 'woocommerce-admin')), Object(external_wp_element_["createElement"])(external_wc_experimental_["Text"], null, Object(external_wp_i18n_["sprintf"])(
  /* translators: Number of reviews the user has to moderate */
  Object(external_wp_i18n_["_n"])('You have %d review to moderate', 'You have %d reviews to moderate', reviewsToModerateCount, 'woocommerce-admin'), reviewsToModerateCount))), stockNoticesCount > 0 && isSetupTaskListHidden && Object(external_wp_element_["createElement"])(external_wc_components_["AbbreviatedCard"], {
    className: "woocommerce-abbreviated-notification",
    icon: box["a" /* default */],
    href: `admin.php?page=wc-admin&opened_panel=${STOCK_PANEL_ID}`,
    onClick: () => trackAbbreviatedCardClick('stockNotices'),
    type: isWCAdminPage ? 'wc-admin' : 'wp-admin'
  }, Object(external_wp_element_["createElement"])(external_wc_experimental_["Text"], {
    as: "h3"
  }, Object(external_wp_i18n_["__"])('Inventory to review', 'woocommerce-admin')), Object(external_wp_element_["createElement"])(external_wc_experimental_["Text"], null, Object(external_wp_i18n_["__"])('You have inventory to review and update', 'woocommerce-admin'))), !isExtendedTaskListHidden && Object(external_wp_element_["createElement"])(Slot, null));
};
/* harmony default export */ var abbreviated_notifications_panel = (AbbreviatedNotificationsPanel);

/***/ }),

/***/ 276:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";

// EXPORTS
__webpack_require__.d(__webpack_exports__, "a", function() { return /* binding */ DEFAULT_ACTIONABLE_STATUSES; });
__webpack_require__.d(__webpack_exports__, "b", function() { return /* binding */ config; });

// UNUSED EXPORTS: DEFAULT_ORDER_STATUSES, DEFAULT_DATE_RANGE

// EXTERNAL MODULE: external ["wp","element"]
var external_wp_element_ = __webpack_require__(0);

// EXTERNAL MODULE: external ["wp","i18n"]
var external_wp_i18n_ = __webpack_require__(2);

// EXTERNAL MODULE: external ["wp","hooks"]
var external_wp_hooks_ = __webpack_require__(27);

// EXTERNAL MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/@automattic+interpolate-components@1.2.0_react@17.0.2/node_modules/@automattic/interpolate-components/dist/esm/index.js + 1 modules
var esm = __webpack_require__(79);

// EXTERNAL MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/qs@6.10.3/node_modules/qs/lib/index.js
var lib = __webpack_require__(31);

// EXTERNAL MODULE: external ["wc","components"]
var external_wc_components_ = __webpack_require__(22);

// EXTERNAL MODULE: external ["wc","data"]
var external_wc_data_ = __webpack_require__(12);

// EXTERNAL MODULE: external ["wc","date"]
var external_wc_date_ = __webpack_require__(20);

// CONCATENATED MODULE: ./client/analytics/settings/default-date.js


/**
 * External dependencies
 */





const DefaultDate = _ref => {
  let {
    value,
    onChange
  } = _ref;
  const {
    wcAdminSettings
  } = Object(external_wc_data_["useSettings"])('wc_admin', ['wcAdminSettings']);
  const {
    woocommerce_default_date_range: defaultDateRange
  } = wcAdminSettings;

  const change = query => {
    onChange({
      target: {
        name: 'woocommerce_default_date_range',
        value: Object(lib["stringify"])(query)
      }
    });
  };

  const query = Object(lib["parse"])(value.replace(/&amp;/g, '&'));
  const {
    period,
    compare,
    before,
    after
  } = Object(external_wc_date_["getDateParamsFromQuery"])(query, defaultDateRange);
  const {
    primary: primaryDate,
    secondary: secondaryDate
  } = Object(external_wc_date_["getCurrentDates"])(query, defaultDateRange);
  const dateQuery = {
    period,
    compare,
    before,
    after,
    primaryDate,
    secondaryDate
  };
  return Object(external_wp_element_["createElement"])(external_wc_components_["DateRangeFilterPicker"], {
    query: query,
    onRangeSelect: change,
    dateQuery: dateQuery,
    isoDateFormat: external_wc_date_["isoDateFormat"]
  });
};

/* harmony default export */ var default_date = (DefaultDate);
// EXTERNAL MODULE: ./client/utils/admin-settings.js
var admin_settings = __webpack_require__(23);

// CONCATENATED MODULE: ./client/analytics/settings/config.js


/**
 * External dependencies
 */



/**
 * Internal dependencies
 */



const SETTINGS_FILTER = 'woocommerce_admin_analytics_settings';
const DEFAULT_ACTIONABLE_STATUSES = ['processing', 'on-hold'];
const DEFAULT_ORDER_STATUSES = ['completed', 'processing', 'refunded', 'cancelled', 'failed', 'pending', 'on-hold'];
const DEFAULT_DATE_RANGE = 'period=month&compare=previous_year';
const filteredOrderStatuses = Object.keys(admin_settings["c" /* ORDER_STATUSES */]).filter(status => status !== 'refunded').map(key => {
  return {
    value: key,
    label: admin_settings["c" /* ORDER_STATUSES */][key],
    description: Object(external_wp_i18n_["sprintf"])(Object(external_wp_i18n_["__"])('Exclude the %s status from reports', 'woocommerce-admin'), admin_settings["c" /* ORDER_STATUSES */][key])
  };
});
const unregisteredOrderStatuses = Object(admin_settings["d" /* getAdminSetting */])('unregisteredOrderStatuses', {});
const orderStatusOptions = [{
  key: 'defaultStatuses',
  options: filteredOrderStatuses.filter(status => DEFAULT_ORDER_STATUSES.includes(status.value))
}, {
  key: 'customStatuses',
  label: Object(external_wp_i18n_["__"])('Custom Statuses', 'woocommerce-admin'),
  options: filteredOrderStatuses.filter(status => !DEFAULT_ORDER_STATUSES.includes(status.value))
}, {
  key: 'unregisteredStatuses',
  label: Object(external_wp_i18n_["__"])('Unregistered Statuses', 'woocommerce-admin'),
  options: Object.keys(unregisteredOrderStatuses).map(key => {
    return {
      value: key,
      label: key,
      description: Object(external_wp_i18n_["sprintf"])(Object(external_wp_i18n_["__"])('Exclude the %s status from reports', 'woocommerce-admin'), key)
    };
  })
}];
/**
 * Filter Analytics Report settings. Add a UI element to the Analytics Settings page.
 *
 * @filter woocommerce_admin_analytics_settings
 * @param {Object} reportSettings Report settings.
 */

const config = Object(external_wp_hooks_["applyFilters"])(SETTINGS_FILTER, {
  woocommerce_excluded_report_order_statuses: {
    label: Object(external_wp_i18n_["__"])('Excluded statuses:', 'woocommerce-admin'),
    inputType: 'checkboxGroup',
    options: orderStatusOptions,
    helpText: Object(esm["a" /* default */])({
      mixedString: Object(external_wp_i18n_["__"])('Orders with these statuses are excluded from the totals in your reports. ' + 'The {{strong}}Refunded{{/strong}} status can not be excluded.', 'woocommerce-admin'),
      components: {
        strong: Object(external_wp_element_["createElement"])("strong", null)
      }
    }),
    defaultValue: ['pending', 'cancelled', 'failed']
  },
  woocommerce_actionable_order_statuses: {
    label: Object(external_wp_i18n_["__"])('Actionable statuses:', 'woocommerce-admin'),
    inputType: 'checkboxGroup',
    options: orderStatusOptions,
    helpText: Object(external_wp_i18n_["__"])('Orders with these statuses require action on behalf of the store admin. ' + 'These orders will show up in the Home Screen - Orders task.', 'woocommerce-admin'),
    defaultValue: DEFAULT_ACTIONABLE_STATUSES
  },
  woocommerce_default_date_range: {
    name: 'woocommerce_default_date_range',
    label: Object(external_wp_i18n_["__"])('Default date range:', 'woocommerce-admin'),
    inputType: 'component',
    component: default_date,
    helpText: Object(external_wp_i18n_["__"])('Select a default date range. When no range is selected, reports will be viewed by ' + 'the default date range.', 'woocommerce-admin'),
    defaultValue: DEFAULT_DATE_RANGE
  }
});

/***/ }),

/***/ 277:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* WEBPACK VAR INJECTION */(function(process) {/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(5);
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(7);
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(classnames__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _wordpress_a11y__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(278);
/* harmony import */ var _wordpress_a11y__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_a11y__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(2);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _wordpress_warning__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(279);
/* harmony import */ var _wordpress_warning__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_wordpress_warning__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(4);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_6__);


/**
 * External dependencies
 */







const NOTICE_TIMEOUT = 10000;
/** @typedef {import('@wordpress/element').WPElement} WPElement */

/**
 * Custom hook which announces the message with the given politeness, if a
 * valid message is provided.
 *
 * @param {string|WPElement}     [message]  Message to announce.
 * @param {'polite'|'assertive'} politeness Politeness to announce.
 */

function useSpokenMessage(message, politeness) {
  const spokenMessage = typeof message === 'string' ? message : Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["renderToString"])(message);
  Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["useEffect"])(() => {
    if (spokenMessage) {
      Object(_wordpress_a11y__WEBPACK_IMPORTED_MODULE_3__["speak"])(spokenMessage, politeness);
    }
  }, [spokenMessage, politeness]);
}

function Snackbar(_ref, ref) {
  let {
    className,
    children,
    spokenMessage = children,
    politeness = 'polite',
    actions = [],
    onRemove = lodash__WEBPACK_IMPORTED_MODULE_1__["noop"],
    icon = null,
    explicitDismiss = false,
    // onDismiss is a callback executed when the snackbar is dismissed.
    // It is distinct from onRemove, which _looks_ like a callback but is
    // actually the function to call to remove the snackbar from the UI.
    onDismiss = null,
    __unstableHTML = false
  } = _ref;
  onDismiss = onDismiss || lodash__WEBPACK_IMPORTED_MODULE_1__["noop"];

  function dismissMe(event) {
    if (event && event.preventDefault) {
      event.preventDefault();
    }

    onDismiss();
    onRemove();
  }

  function onActionClick(event, onClick) {
    event.stopPropagation();
    onRemove();

    if (onClick) {
      onClick(event);
    }
  }

  useSpokenMessage(spokenMessage, politeness); // Only set up the timeout dismiss if we're not explicitly dismissing.

  Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["useEffect"])(() => {
    const timeoutHandle = setTimeout(() => {
      if (!explicitDismiss) {
        onDismiss();
        onRemove();
      }
    }, NOTICE_TIMEOUT);
    return () => clearTimeout(timeoutHandle);
  }, [explicitDismiss, onDismiss, onRemove]);
  const classes = classnames__WEBPACK_IMPORTED_MODULE_2___default()(className, 'components-snackbar', {
    'components-snackbar-explicit-dismiss': !!explicitDismiss
  });

  if (actions && actions.length > 1) {
    // we need to inform developers that snackbar only accepts 1 action
    typeof process !== "undefined" && process.env && "production" !== "production" ? _wordpress_warning__WEBPACK_IMPORTED_MODULE_5___default()('Snackbar can only have 1 action, use Notice if your message require many messages') : void 0; // return first element only while keeping it inside an array

    actions = [actions[0]];
  }

  const snackbarContentClassnames = classnames__WEBPACK_IMPORTED_MODULE_2___default()('components-snackbar__content', {
    'components-snackbar__content-with-icon': !!icon
  });

  if (__unstableHTML === true) {
    children = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["RawHTML"], null, children);
  }

  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("div", {
    ref: ref,
    className: classes,
    onClick: !explicitDismiss ? dismissMe : lodash__WEBPACK_IMPORTED_MODULE_1__["noop"],
    tabIndex: "0",
    role: !explicitDismiss ? 'button' : '',
    onKeyPress: !explicitDismiss ? dismissMe : lodash__WEBPACK_IMPORTED_MODULE_1__["noop"],
    "aria-label": !explicitDismiss ? Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__["__"])('Dismiss this notice') : ''
  }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("div", {
    className: snackbarContentClassnames
  }, icon && Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("div", {
    className: "components-snackbar__icon"
  }, icon), children, actions.map((_ref2, index) => {
    let {
      label,
      onClick,
      url
    } = _ref2;
    return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_6__["Button"], {
      key: index,
      href: url,
      isTertiary: true,
      onClick: event => onActionClick(event, onClick),
      className: "components-snackbar__action"
    }, label);
  }), explicitDismiss && Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("span", {
    role: "button",
    "aria-label": "Dismiss this notice",
    tabIndex: "0",
    className: "components-snackbar__dismiss-button",
    onClick: dismissMe,
    onKeyPress: dismissMe
  }, "\u2715")));
}

/* harmony default export */ __webpack_exports__["a"] = (Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["forwardRef"])(Snackbar));
/* WEBPACK VAR INJECTION */}.call(this, __webpack_require__(145)))

/***/ }),

/***/ 278:
/***/ (function(module, exports) {

(function() { module.exports = window["wp"]["a11y"]; }());

/***/ }),

/***/ 279:
/***/ (function(module, exports) {

(function() { module.exports = window["wp"]["warning"]; }());

/***/ }),

/***/ 280:
/***/ (function(module, exports, __webpack_require__) {

"use strict";
Object.defineProperty(exports,"__esModule",{value:!0}),exports["default"]=_default;var _react=_interopRequireDefault(__webpack_require__(6)),_excluded=["size","onClick","icon","className"];function _interopRequireDefault(a){return a&&a.__esModule?a:{default:a}}function _extends(){return _extends=Object.assign||function(a){for(var b,c=1;c<arguments.length;c++)for(var d in b=arguments[c],b)Object.prototype.hasOwnProperty.call(b,d)&&(a[d]=b[d]);return a},_extends.apply(this,arguments)}function _objectWithoutProperties(a,b){if(null==a)return{};var c,d,e=_objectWithoutPropertiesLoose(a,b);if(Object.getOwnPropertySymbols){var f=Object.getOwnPropertySymbols(a);for(d=0;d<f.length;d++)c=f[d],0<=b.indexOf(c)||Object.prototype.propertyIsEnumerable.call(a,c)&&(e[c]=a[c])}return e}function _objectWithoutPropertiesLoose(a,b){if(null==a)return{};var c,d,e={},f=Object.keys(a);for(d=0;d<f.length;d++)c=f[d],0<=b.indexOf(c)||(e[c]=a[c]);return e}function _default(a){var b=a.size,c=void 0===b?24:b,d=a.onClick,e=a.icon,f=a.className,g=_objectWithoutProperties(a,_excluded),h=["gridicon","gridicons-cross-small",f,!1,!1,!1].filter(Boolean).join(" ");return _react["default"].createElement("svg",_extends({className:h,height:c,width:c,onClick:d},g,{xmlns:"http://www.w3.org/2000/svg",viewBox:"0 0 24 24"}),_react["default"].createElement("g",null,_react["default"].createElement("path",{d:"M17.705 7.705l-1.41-1.41L12 10.59 7.705 6.295l-1.41 1.41L10.59 12l-4.295 4.295 1.41 1.41L12 13.41l4.295 4.295 1.41-1.41L13.41 12l4.295-4.295z"})))}


/***/ }),

/***/ 281:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* WEBPACK VAR INJECTION */(function(global) {/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(6);
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(26);
/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(1);
/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(prop_types__WEBPACK_IMPORTED_MODULE_2__);





var MAX_SIGNED_31_BIT_INT = 1073741823;
var commonjsGlobal = typeof globalThis !== 'undefined' ? globalThis : typeof window !== 'undefined' ? window : typeof global !== 'undefined' ? global : {};

function getUniqueId() {
  var key = '__global_unique_id__';
  return commonjsGlobal[key] = (commonjsGlobal[key] || 0) + 1;
}

function objectIs(x, y) {
  if (x === y) {
    return x !== 0 || 1 / x === 1 / y;
  } else {
    return x !== x && y !== y;
  }
}

function createEventEmitter(value) {
  var handlers = [];
  return {
    on: function on(handler) {
      handlers.push(handler);
    },
    off: function off(handler) {
      handlers = handlers.filter(function (h) {
        return h !== handler;
      });
    },
    get: function get() {
      return value;
    },
    set: function set(newValue, changedBits) {
      value = newValue;
      handlers.forEach(function (handler) {
        return handler(value, changedBits);
      });
    }
  };
}

function onlyChild(children) {
  return Array.isArray(children) ? children[0] : children;
}

function createReactContext(defaultValue, calculateChangedBits) {
  var _Provider$childContex, _Consumer$contextType;

  var contextProp = '__create-react-context-' + getUniqueId() + '__';

  var Provider = /*#__PURE__*/function (_Component) {
    Object(_babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_1__[/* default */ "a"])(Provider, _Component);

    function Provider() {
      var _this;

      _this = _Component.apply(this, arguments) || this;
      _this.emitter = createEventEmitter(_this.props.value);
      return _this;
    }

    var _proto = Provider.prototype;

    _proto.getChildContext = function getChildContext() {
      var _ref;

      return _ref = {}, _ref[contextProp] = this.emitter, _ref;
    };

    _proto.componentWillReceiveProps = function componentWillReceiveProps(nextProps) {
      if (this.props.value !== nextProps.value) {
        var oldValue = this.props.value;
        var newValue = nextProps.value;
        var changedBits;

        if (objectIs(oldValue, newValue)) {
          changedBits = 0;
        } else {
          changedBits = typeof calculateChangedBits === 'function' ? calculateChangedBits(oldValue, newValue) : MAX_SIGNED_31_BIT_INT;

          if (false) {}

          changedBits |= 0;

          if (changedBits !== 0) {
            this.emitter.set(nextProps.value, changedBits);
          }
        }
      }
    };

    _proto.render = function render() {
      return this.props.children;
    };

    return Provider;
  }(react__WEBPACK_IMPORTED_MODULE_0__["Component"]);

  Provider.childContextTypes = (_Provider$childContex = {}, _Provider$childContex[contextProp] = prop_types__WEBPACK_IMPORTED_MODULE_2___default.a.object.isRequired, _Provider$childContex);

  var Consumer = /*#__PURE__*/function (_Component2) {
    Object(_babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_1__[/* default */ "a"])(Consumer, _Component2);

    function Consumer() {
      var _this2;

      _this2 = _Component2.apply(this, arguments) || this;
      _this2.state = {
        value: _this2.getValue()
      };

      _this2.onUpdate = function (newValue, changedBits) {
        var observedBits = _this2.observedBits | 0;

        if ((observedBits & changedBits) !== 0) {
          _this2.setState({
            value: _this2.getValue()
          });
        }
      };

      return _this2;
    }

    var _proto2 = Consumer.prototype;

    _proto2.componentWillReceiveProps = function componentWillReceiveProps(nextProps) {
      var observedBits = nextProps.observedBits;
      this.observedBits = observedBits === undefined || observedBits === null ? MAX_SIGNED_31_BIT_INT : observedBits;
    };

    _proto2.componentDidMount = function componentDidMount() {
      if (this.context[contextProp]) {
        this.context[contextProp].on(this.onUpdate);
      }

      var observedBits = this.props.observedBits;
      this.observedBits = observedBits === undefined || observedBits === null ? MAX_SIGNED_31_BIT_INT : observedBits;
    };

    _proto2.componentWillUnmount = function componentWillUnmount() {
      if (this.context[contextProp]) {
        this.context[contextProp].off(this.onUpdate);
      }
    };

    _proto2.getValue = function getValue() {
      if (this.context[contextProp]) {
        return this.context[contextProp].get();
      } else {
        return defaultValue;
      }
    };

    _proto2.render = function render() {
      return onlyChild(this.props.children)(this.state.value);
    };

    return Consumer;
  }(react__WEBPACK_IMPORTED_MODULE_0__["Component"]);

  Consumer.contextTypes = (_Consumer$contextType = {}, _Consumer$contextType[contextProp] = prop_types__WEBPACK_IMPORTED_MODULE_2___default.a.object, _Consumer$contextType);
  return {
    Provider: Provider,
    Consumer: Consumer
  };
}

var index = react__WEBPACK_IMPORTED_MODULE_0___default.a.createContext || createReactContext;

/* harmony default export */ __webpack_exports__["a"] = (index);

/* WEBPACK VAR INJECTION */}.call(this, __webpack_require__(92)))

/***/ }),

/***/ 282:
/***/ (function(module, exports) {

(function() { module.exports = window["wc"]["customerEffortScore"]; }());

/***/ }),

/***/ 29:
/***/ (function(module, exports) {

(function() { module.exports = window["ReactDOM"]; }());

/***/ }),

/***/ 297:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";

// EXPORTS
__webpack_require__.d(__webpack_exports__, "a", function() { return /* reexport */ customer_effort_score_tracks_container; });

// UNUSED EXPORTS: CustomerEffortScoreTracks

// NAMESPACE OBJECT: ./client/customer-effort-score-tracks/data/actions.js
var actions_namespaceObject = {};
__webpack_require__.r(actions_namespaceObject);
__webpack_require__.d(actions_namespaceObject, "setCesSurveyQueue", function() { return setCesSurveyQueue; });
__webpack_require__.d(actions_namespaceObject, "addCesSurvey", function() { return addCesSurvey; });
__webpack_require__.d(actions_namespaceObject, "addCesSurveyForAnalytics", function() { return addCesSurveyForAnalytics; });
__webpack_require__.d(actions_namespaceObject, "addCesSurveyForCustomerSearch", function() { return addCesSurveyForCustomerSearch; });

// NAMESPACE OBJECT: ./client/customer-effort-score-tracks/data/resolvers.js
var resolvers_namespaceObject = {};
__webpack_require__.r(resolvers_namespaceObject);
__webpack_require__.d(resolvers_namespaceObject, "getCesSurveyQueue", function() { return resolvers_getCesSurveyQueue; });

// NAMESPACE OBJECT: ./client/customer-effort-score-tracks/data/selectors.js
var selectors_namespaceObject = {};
__webpack_require__.r(selectors_namespaceObject);
__webpack_require__.d(selectors_namespaceObject, "getCesSurveyQueue", function() { return selectors_getCesSurveyQueue; });

// EXTERNAL MODULE: external ["wp","element"]
var external_wp_element_ = __webpack_require__(0);

// EXTERNAL MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/prop-types@15.8.1/node_modules/prop-types/index.js
var prop_types = __webpack_require__(1);
var prop_types_default = /*#__PURE__*/__webpack_require__.n(prop_types);

// EXTERNAL MODULE: external ["wc","tracks"]
var external_wc_tracks_ = __webpack_require__(17);

// EXTERNAL MODULE: external ["wc","customerEffortScore"]
var external_wc_customerEffortScore_ = __webpack_require__(282);
var external_wc_customerEffortScore_default = /*#__PURE__*/__webpack_require__.n(external_wc_customerEffortScore_);

// EXTERNAL MODULE: external ["wp","compose"]
var external_wp_compose_ = __webpack_require__(14);

// EXTERNAL MODULE: external ["wp","data"]
var external_wp_data_ = __webpack_require__(8);

// EXTERNAL MODULE: external ["wc","data"]
var external_wc_data_ = __webpack_require__(12);

// EXTERNAL MODULE: external ["wp","i18n"]
var external_wp_i18n_ = __webpack_require__(2);

// CONCATENATED MODULE: ./client/customer-effort-score-tracks/customer-effort-score-tracks.js


/**
 * External dependencies
 */








const SHOWN_FOR_ACTIONS_OPTION_NAME = 'woocommerce_ces_shown_for_actions';
const ADMIN_INSTALL_TIMESTAMP_OPTION_NAME = 'woocommerce_admin_install_timestamp';
const ALLOW_TRACKING_OPTION_NAME = 'woocommerce_allow_tracking';
/**
 * A CustomerEffortScore wrapper that uses tracks to track the selected
 * customer effort score.
 *
 * @param {Object}   props                    Component props.
 * @param {string}   props.action             The action name sent to Tracks.
 * @param {Object}   props.trackProps         Additional props sent to Tracks.
 * @param {string}   props.label              The label displayed in the modal.
 * @param {string}   props.onSubmitLabel      The label displayed upon survey submission.
 * @param {Array}    props.cesShownForActions The array of actions that the CES modal has been shown for.
 * @param {boolean}  props.allowTracking      Whether tracking is allowed or not.
 * @param {boolean}  props.resolving          Are values still being resolved.
 * @param {number}   props.storeAgeInWeeks    The age of the store in weeks.
 * @param {Function} props.updateOptions      Function to update options.
 * @param {Function} props.createNotice       Function to create a snackbar.
 */

function CustomerEffortScoreTracks(_ref) {
  let {
    action,
    trackProps,
    label,
    onSubmitLabel = Object(external_wp_i18n_["__"])('Thank you for your feedback!', 'woocommerce-admin'),
    cesShownForActions,
    allowTracking,
    resolving,
    storeAgeInWeeks,
    updateOptions,
    createNotice
  } = _ref;
  const [modalShown, setModalShown] = Object(external_wp_element_["useState"])(false);

  if (resolving) {
    return null;
  } // Don't show if tracking is disallowed.


  if (!allowTracking) {
    return null;
  } // We only want to return null early if the modal was already shown
  // for this action *before* this component was initially instantiated.
  //
  // We want to make sure we still render CustomerEffortScore below
  // (we don't want to return null early), if the modal was shown for this
  // instantiation, so that the component doesn't go away while we are
  // still showing it.


  if (cesShownForActions.indexOf(action) !== -1 && !modalShown) {
    return null;
  }

  const onNoticeShown = () => {
    Object(external_wc_tracks_["recordEvent"])('ces_snackbar_view', {
      action,
      store_age: storeAgeInWeeks,
      ...trackProps
    });
  };

  const addActionToShownOption = () => {
    updateOptions({
      [SHOWN_FOR_ACTIONS_OPTION_NAME]: [action, ...cesShownForActions]
    });
  };

  const onNoticeDismissed = () => {
    Object(external_wc_tracks_["recordEvent"])('ces_snackbar_dismiss', {
      action,
      store_age: storeAgeInWeeks,
      ...trackProps
    });
    addActionToShownOption();
  };

  const onModalShown = () => {
    setModalShown(true);
    Object(external_wc_tracks_["recordEvent"])('ces_view', {
      action,
      store_age: storeAgeInWeeks,
      ...trackProps
    });
    addActionToShownOption();
  };

  const recordScore = (score, comments) => {
    Object(external_wc_tracks_["recordEvent"])('ces_feedback', {
      action,
      score,
      comments: comments || '',
      store_age: storeAgeInWeeks,
      ...trackProps
    });
    createNotice('success', onSubmitLabel);
  };

  return Object(external_wp_element_["createElement"])(external_wc_customerEffortScore_default.a, {
    recordScoreCallback: recordScore,
    label: label,
    onNoticeShownCallback: onNoticeShown,
    onNoticeDismissedCallback: onNoticeDismissed,
    onModalShownCallback: onModalShown,
    icon: Object(external_wp_element_["createElement"])("span", {
      style: {
        height: 21,
        width: 21
      },
      role: "img",
      "aria-label": Object(external_wp_i18n_["__"])('Pencil icon', 'woocommerce-admin')
    }, "\u270F\uFE0F")
  });
}

CustomerEffortScoreTracks.propTypes = {
  /**
   * The action name sent to Tracks.
   */
  action: prop_types_default.a.string.isRequired,

  /**
   * Additional props sent to Tracks.
   */
  trackProps: prop_types_default.a.object,

  /**
   * The label displayed in the modal.
   */
  label: prop_types_default.a.string.isRequired,

  /**
   * The label for the snackbar that appears upon survey submission.
   */
  onSubmitLabel: prop_types_default.a.string,

  /**
   * The array of actions that the CES modal has been shown for.
   */
  cesShownForActions: prop_types_default.a.arrayOf(prop_types_default.a.string).isRequired,

  /**
   * Whether tracking is allowed or not.
   */
  allowTracking: prop_types_default.a.bool,

  /**
   * Whether props are still being resolved.
   */
  resolving: prop_types_default.a.bool.isRequired,

  /**
   * The age of the store in weeks.
   */
  storeAgeInWeeks: prop_types_default.a.number,

  /**
   * Function to update options.
   */
  updateOptions: prop_types_default.a.func,

  /**
   * Function to create a snackbar
   */
  createNotice: prop_types_default.a.func
};

function getStoreAgeInWeeks(adminInstallTimestamp) {
  if (adminInstallTimestamp === 0) {
    return null;
  } // Date.now() is ms since Unix epoch, adminInstallTimestamp is in
  // seconds since Unix epoch.


  const storeAgeInMs = Date.now() - adminInstallTimestamp * 1000;
  const storeAgeInWeeks = Math.round(storeAgeInMs / external_wc_data_["WEEK"]);
  return storeAgeInWeeks;
}

/* harmony default export */ var customer_effort_score_tracks = (Object(external_wp_compose_["compose"])(Object(external_wp_data_["withSelect"])(select => {
  const {
    getOption,
    isResolving
  } = select(external_wc_data_["OPTIONS_STORE_NAME"]);
  const cesShownForActions = getOption(SHOWN_FOR_ACTIONS_OPTION_NAME) || [];
  const adminInstallTimestamp = getOption(ADMIN_INSTALL_TIMESTAMP_OPTION_NAME) || 0;
  const storeAgeInWeeks = getStoreAgeInWeeks(adminInstallTimestamp);
  const allowTrackingOption = getOption(ALLOW_TRACKING_OPTION_NAME) || 'no';
  const allowTracking = allowTrackingOption === 'yes';
  const resolving = isResolving('getOption', [SHOWN_FOR_ACTIONS_OPTION_NAME]) || storeAgeInWeeks === null || isResolving('getOption', [ADMIN_INSTALL_TIMESTAMP_OPTION_NAME]) || isResolving('getOption', [ALLOW_TRACKING_OPTION_NAME]);
  return {
    cesShownForActions,
    allowTracking,
    storeAgeInWeeks,
    resolving
  };
}), Object(external_wp_data_["withDispatch"])(dispatch => {
  const {
    updateOptions
  } = dispatch(external_wc_data_["OPTIONS_STORE_NAME"]);
  const {
    createNotice
  } = dispatch('core/notices');
  return {
    updateOptions,
    createNotice
  };
}))(CustomerEffortScoreTracks));
// EXTERNAL MODULE: ./client/customer-effort-score-tracks/data/constants.js
var constants = __webpack_require__(69);

// EXTERNAL MODULE: external ["wp","dataControls"]
var external_wp_dataControls_ = __webpack_require__(10);

// CONCATENATED MODULE: ./client/customer-effort-score-tracks/data/action-types.js
const TYPES = {
  SET_CES_SURVEY_QUEUE: 'SET_CES_SURVEY_QUEUE',
  ADD_CES_SURVEY: 'ADD_CES_SURVEY'
};
/* harmony default export */ var action_types = (TYPES);
// CONCATENATED MODULE: ./client/customer-effort-score-tracks/data/actions.js
/**
 * External dependencies
 */

/**
 * Internal dependencies
 */


/**
 * Initialize the state
 *
 * @param {Object} queue	initial queue
 */

function setCesSurveyQueue(queue) {
  return {
    type: action_types.SET_CES_SURVEY_QUEUE,
    queue
  };
}
/**
 * Add a new CES track to the state.
 *
 * @param {string} action action name for the survey
 * @param {string} label label for the snackback
 * @param {string} pageNow value of window.pagenow
 * @param {string} adminPage value of window.adminpage
 * @param {string} onsubmit_label label for the snackback onsubmit
 * @param {Object} props object for optional props
 */

function addCesSurvey(action, label) {
  let pageNow = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : window.pagenow;
  let adminPage = arguments.length > 3 && arguments[3] !== undefined ? arguments[3] : window.adminpage;
  let onsubmit_label = arguments.length > 4 && arguments[4] !== undefined ? arguments[4] : undefined;
  let props = arguments.length > 5 && arguments[5] !== undefined ? arguments[5] : {};
  return {
    type: action_types.ADD_CES_SURVEY,
    action,
    label,
    pageNow,
    adminPage,
    onsubmit_label,
    props
  };
}
/**
 * Add a new CES survey track for the pages in Analytics menu
 */

function addCesSurveyForAnalytics() {
  return addCesSurvey('analytics_filtered', Object(external_wp_i18n_["__"])('How easy was it to filter your store analytics?', 'woocommerce-admin'), 'woocommerce_page_wc-admin', 'woocommerce_page_wc-admin');
}
/**
 * Add a new CES survey track on searching customers.
 */

function addCesSurveyForCustomerSearch() {
  return addCesSurvey('ces_search', Object(external_wp_i18n_["__"])('How easy was it to use search?', 'woocommerce-admin'), 'woocommerce_page_wc-admin', 'woocommerce_page_wc-admin', undefined, {
    search_area: 'customer'
  });
}
// CONCATENATED MODULE: ./client/customer-effort-score-tracks/data/resolvers.js
/**
 * External dependencies
 */

/**
 * Internal dependencies
 */



function* resolvers_getCesSurveyQueue() {
  const response = yield Object(external_wp_dataControls_["apiFetch"])({
    path: `${constants["a" /* API_NAMESPACE */]}/options?options=${constants["b" /* QUEUE_OPTION_NAME */]}`
  });

  if (response) {
    yield setCesSurveyQueue(response[constants["b" /* QUEUE_OPTION_NAME */]] || []);
  } else {
    throw new Error();
  }
}
// CONCATENATED MODULE: ./client/customer-effort-score-tracks/data/selectors.js
function selectors_getCesSurveyQueue(state) {
  return state.queue;
}
// CONCATENATED MODULE: ./client/customer-effort-score-tracks/data/reducer.js
/**
 * Internal dependencies
 */

const DEFAULT_STATE = {
  queue: []
};

const reducer = function () {
  let state = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : DEFAULT_STATE;
  let action = arguments.length > 1 ? arguments[1] : undefined;

  switch (action.type) {
    case action_types.SET_CES_SURVEY_QUEUE:
      return { ...state,
        queue: action.queue
      };

    case action_types.ADD_CES_SURVEY:
      // Prevent duplicate
      const hasDuplicate = state.queue.filter(track => track.action === action.action);

      if (hasDuplicate.length) {
        return state;
      }

      const newTrack = {
        action: action.action,
        label: action.label,
        pagenow: action.pageNow,
        adminpage: action.adminPage,
        onSubmitLabel: action.onSubmitLabel,
        props: action.props
      };
      return { ...state,
        queue: [...state.queue, newTrack]
      };

    default:
      return state;
  }
};

/* harmony default export */ var data_reducer = (reducer);
// CONCATENATED MODULE: ./client/customer-effort-score-tracks/data/index.js
/**
 * External dependencies
 */


/**
 * Internal dependencies
 */






/* harmony default export */ var data = (Object(external_wp_data_["registerStore"])(constants["c" /* STORE_KEY */], {
  actions: actions_namespaceObject,
  selectors: selectors_namespaceObject,
  resolvers: resolvers_namespaceObject,
  controls: external_wp_dataControls_["controls"],
  reducer: data_reducer
}));
// CONCATENATED MODULE: ./client/customer-effort-score-tracks/customer-effort-score-tracks-container.js


/**
 * External dependencies
 */




/**
 * Internal dependencies
 */




/**
 * Maps the queue of CES tracks surveys to CustomerEffortScoreTracks
 * components. Note that generally there will only be a single survey per page
 * however this is designed to be flexible if multiple surveys per page are
 * added in the future.
 *
 * @param {Object}   props            Component props.
 * @param {Array}    props.queue      The queue of surveys.
 * @param {boolean}  props.resolving  Whether the queue is resolving.
 * @param {Function} props.clearQueue Sets up clearing of the queue on the next page load.
 */

function CustomerEffortScoreTracksContainer(_ref) {
  let {
    queue,
    resolving,
    clearQueue
  } = _ref;

  if (resolving) {
    return null;
  }

  const queueForPage = queue.filter(item => item.pagenow === window.pagenow && item.adminpage === window.adminpage);

  if (queueForPage.length) {
    clearQueue();
  }

  return Object(external_wp_element_["createElement"])(external_wp_element_["Fragment"], null, queueForPage.map((item, index) => Object(external_wp_element_["createElement"])(customer_effort_score_tracks, {
    key: index,
    action: item.action,
    label: item.label,
    onSubmitLabel: item.onsubmit_label,
    trackProps: item.props || {}
  })));
}

CustomerEffortScoreTracksContainer.propTypes = {
  /**
   * The queue of CES tracks surveys to display.
   */
  queue: prop_types_default.a.arrayOf(prop_types_default.a.object),

  /**
   * If the queue option is being resolved.
   */
  resolving: prop_types_default.a.bool,

  /**
   * Set up clearing the queue on the next page load.
   */
  clearQueue: prop_types_default.a.func
};
/* harmony default export */ var customer_effort_score_tracks_container = (Object(external_wp_compose_["compose"])(Object(external_wp_data_["withSelect"])(select => {
  const {
    getCesSurveyQueue,
    isResolving
  } = select(constants["c" /* STORE_KEY */]);
  const queue = getCesSurveyQueue();
  const resolving = isResolving('getOption', [constants["b" /* QUEUE_OPTION_NAME */]]);
  return {
    queue,
    resolving
  };
}), Object(external_wp_data_["withDispatch"])(dispatch => {
  const {
    updateOptions
  } = dispatch(external_wc_data_["OPTIONS_STORE_NAME"]);
  return {
    clearQueue: () => {
      // This sets an option that should be used on the next page
      // load to clear the CES tracks queue for the current page (see
      // CustomerEffortScoreTracks.php) - clearing the queue
      // directly puts this into an infinite loop which is picked
      // up by React.
      updateOptions({
        woocommerce_clear_ces_tracks_queue_for_page: {
          pagenow: window.pagenow,
          adminpage: window.adminpage
        }
      });
    }
  };
}))(CustomerEffortScoreTracksContainer));
// CONCATENATED MODULE: ./client/customer-effort-score-tracks/index.js



/***/ }),

/***/ 298:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";

// EXPORTS
__webpack_require__.d(__webpack_exports__, "a", function() { return /* reexport */ EmbeddedBodyLayout; });

// EXTERNAL MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/@babel+runtime@7.17.2/node_modules/@babel/runtime/helpers/extends.js
var helpers_extends = __webpack_require__(40);
var extends_default = /*#__PURE__*/__webpack_require__.n(helpers_extends);

// EXTERNAL MODULE: external ["wp","element"]
var external_wp_element_ = __webpack_require__(0);

// EXTERNAL MODULE: external ["wp","hooks"]
var external_wp_hooks_ = __webpack_require__(27);

// EXTERNAL MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/qs@6.10.3/node_modules/qs/lib/index.js
var lib = __webpack_require__(31);

// EXTERNAL MODULE: external ["wp","data"]
var external_wp_data_ = __webpack_require__(8);

// EXTERNAL MODULE: external ["wc","data"]
var external_wc_data_ = __webpack_require__(12);

// CONCATENATED MODULE: ./client/settings-recommendations/recommendations-eligibility-wrapper.tsx


/**
 * External dependencies
 */


const SHOW_MARKETPLACE_SUGGESTION_OPTION = 'woocommerce_show_marketplace_suggestions';

const RecommendationsEligibilityWrapper = _ref => {
  let {
    children
  } = _ref;
  const {
    currentUserCan
  } = Object(external_wc_data_["useUser"])();
  const isMarketplaceSuggestionsEnabled = Object(external_wp_data_["useSelect"])(select => {
    const {
      getOption,
      hasFinishedResolution
    } = select(external_wc_data_["OPTIONS_STORE_NAME"]);
    const hasFinishedResolving = hasFinishedResolution('getOption', [SHOW_MARKETPLACE_SUGGESTION_OPTION]);
    const canShowMarketplaceSuggestions = getOption(SHOW_MARKETPLACE_SUGGESTION_OPTION) !== 'no';
    return hasFinishedResolving && canShowMarketplaceSuggestions;
  });

  if (!currentUserCan('install_plugins')) {
    return null;
  }

  if (!isMarketplaceSuggestionsEnabled) {
    return null;
  }

  return Object(external_wp_element_["createElement"])(external_wp_element_["Fragment"], null, children);
};

/* harmony default export */ var recommendations_eligibility_wrapper = (RecommendationsEligibilityWrapper);
// CONCATENATED MODULE: ./client/payments/payment-recommendations-wrapper.tsx


/**
 * External dependencies
 */

/**
 * Internal dependencies
 */


const PaymentRecommendationsChunk = Object(external_wp_element_["lazy"])(() => __webpack_require__.e(/* import() | payment-recommendations */ 46).then(__webpack_require__.bind(null, 657)));
const PaymentRecommendations = _ref => {
  let {
    page,
    tab,
    section
  } = _ref;

  if (page === 'wc-settings' && tab === 'checkout' && !section) {
    return Object(external_wp_element_["createElement"])(recommendations_eligibility_wrapper, null, Object(external_wp_element_["createElement"])(external_wp_element_["Suspense"], {
      fallback: null
    }, Object(external_wp_element_["createElement"])(PaymentRecommendationsChunk, null)));
  }

  return null;
};
// CONCATENATED MODULE: ./client/payments/index.ts

// CONCATENATED MODULE: ./client/shipping/shipping-recommendations-wrapper.tsx


/**
 * External dependencies
 */

/**
 * Internal dependencies
 */


const ShippingRecommendationsLoader = Object(external_wp_element_["lazy"])(() => __webpack_require__.e(/* import() | shipping-recommendations */ 49).then(__webpack_require__.bind(null, 667)));
const ShippingRecommendations = _ref => {
  let {
    page,
    tab,
    section,
    zone_id
  } = _ref;

  if (page !== 'wc-settings') {
    return null;
  }

  if (tab !== 'shipping') {
    return null;
  }

  if (Boolean(section)) {
    return null;
  }

  if (Boolean(zone_id)) {
    return null;
  }

  return Object(external_wp_element_["createElement"])(recommendations_eligibility_wrapper, null, Object(external_wp_element_["createElement"])(external_wp_element_["Suspense"], {
    fallback: null
  }, Object(external_wp_element_["createElement"])(ShippingRecommendationsLoader, null)));
};
// CONCATENATED MODULE: ./client/shipping/index.ts

// EXTERNAL MODULE: ./client/embedded-body-layout/style.scss
var style = __webpack_require__(322);

// CONCATENATED MODULE: ./client/embedded-body-layout/embedded-body-layout.tsx



/**
 * External dependencies
 */


/**
 * Internal dependencies
 */





function isWPPage(params) {
  return params.page !== undefined;
}

const EMBEDDED_BODY_COMPONENT_LIST = [PaymentRecommendations, ShippingRecommendations];
/**
 * This component is appended to the bottom of the WooCommerce non-react pages (like settings).
 * You can add a component by writing a Fill component from slot-fill with the `embedded-body-layout` name.
 *
 * Each Fill component receives QueryParams, consisting of a page, tab, and section string.
 */

const EmbeddedBodyLayout = () => {
  const query = Object(lib["parse"])(location.search.substring(1));
  let queryParams = {
    page: '',
    tab: ''
  };

  if (isWPPage(query)) {
    queryParams = query;
  }
  /**
   * Filter an array of body components for WooCommerce non-react pages.
   *
   * @filter woocommerce_admin_embedded_layout_components
   * @param {Array.<Node>} embeddedBodyComponentList Array of body components.
   * @param {Object} query url query parameters.
   */


  const componentList = Object(external_wp_hooks_["applyFilters"])('woocommerce_admin_embedded_layout_components', EMBEDDED_BODY_COMPONENT_LIST, queryParams);
  return Object(external_wp_element_["createElement"])("div", {
    className: "woocommerce-embedded-layout__primary",
    id: "woocommerce-embedded-layout__primary"
  }, componentList.map((Comp, index) => {
    return Object(external_wp_element_["createElement"])(Comp, extends_default()({
      key: index
    }, queryParams));
  }));
};
// CONCATENATED MODULE: ./client/embedded-body-layout/index.ts


/***/ }),

/***/ 3:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";

// EXPORTS
__webpack_require__.d(__webpack_exports__, "w", function() { return /* reexport */ raf; });
__webpack_require__.d(__webpack_exports__, "a", function() { return /* binding */ FluidValue; });
__webpack_require__.d(__webpack_exports__, "b", function() { return /* binding */ react_spring_shared_esm_globals; });
__webpack_require__.d(__webpack_exports__, "c", function() { return /* binding */ addFluidObserver; });
__webpack_require__.d(__webpack_exports__, "d", function() { return /* binding */ callFluidObservers; });
__webpack_require__.d(__webpack_exports__, "e", function() { return /* binding */ colors; });
__webpack_require__.d(__webpack_exports__, "f", function() { return /* binding */ createInterpolator; });
__webpack_require__.d(__webpack_exports__, "g", function() { return /* binding */ createStringInterpolator; });
__webpack_require__.d(__webpack_exports__, "h", function() { return /* binding */ defineHidden; });
__webpack_require__.d(__webpack_exports__, "i", function() { return /* binding */ deprecateDirectCall; });
__webpack_require__.d(__webpack_exports__, "j", function() { return /* binding */ deprecateInterpolate; });
__webpack_require__.d(__webpack_exports__, "k", function() { return /* binding */ each; });
__webpack_require__.d(__webpack_exports__, "l", function() { return /* binding */ eachProp; });
__webpack_require__.d(__webpack_exports__, "m", function() { return /* binding */ flush; });
__webpack_require__.d(__webpack_exports__, "n", function() { return /* binding */ flushCalls; });
__webpack_require__.d(__webpack_exports__, "o", function() { return /* binding */ frameLoop; });
__webpack_require__.d(__webpack_exports__, "p", function() { return /* binding */ getFluidObservers; });
__webpack_require__.d(__webpack_exports__, "q", function() { return /* binding */ getFluidValue; });
__webpack_require__.d(__webpack_exports__, "r", function() { return /* binding */ hasFluidValue; });
__webpack_require__.d(__webpack_exports__, "s", function() { return /* binding */ is; });
__webpack_require__.d(__webpack_exports__, "t", function() { return /* binding */ isAnimatedString; });
__webpack_require__.d(__webpack_exports__, "u", function() { return /* binding */ isEqual; });
__webpack_require__.d(__webpack_exports__, "v", function() { return /* binding */ noop; });
__webpack_require__.d(__webpack_exports__, "x", function() { return /* binding */ removeFluidObserver; });
__webpack_require__.d(__webpack_exports__, "y", function() { return /* binding */ toArray; });
__webpack_require__.d(__webpack_exports__, "z", function() { return /* binding */ useForceUpdate; });
__webpack_require__.d(__webpack_exports__, "A", function() { return /* binding */ useLayoutEffect; });
__webpack_require__.d(__webpack_exports__, "B", function() { return /* binding */ useMemoOne; });
__webpack_require__.d(__webpack_exports__, "C", function() { return /* binding */ useOnce; });
__webpack_require__.d(__webpack_exports__, "D", function() { return /* binding */ usePrev; });

// UNUSED EXPORTS: callFluidObserver, colorToRgba, hex3, hex4, hex6, hex8, hsl, hsla, isSSR, rgb, rgba, setFluidGetter

// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/@react-spring+rafz@9.4.3/node_modules/@react-spring/rafz/dist/react-spring-rafz.esm.js
let updateQueue = makeQueue();
const raf = fn => schedule(fn, updateQueue);
let writeQueue = makeQueue();

raf.write = fn => schedule(fn, writeQueue);

let onStartQueue = makeQueue();

raf.onStart = fn => schedule(fn, onStartQueue);

let onFrameQueue = makeQueue();

raf.onFrame = fn => schedule(fn, onFrameQueue);

let onFinishQueue = makeQueue();

raf.onFinish = fn => schedule(fn, onFinishQueue);

let timeouts = [];

raf.setTimeout = (handler, ms) => {
  let time = raf.now() + ms;

  let cancel = () => {
    let i = timeouts.findIndex(t => t.cancel == cancel);
    if (~i) timeouts.splice(i, 1);
    pendingCount -= ~i ? 1 : 0;
  };

  let timeout = {
    time,
    handler,
    cancel
  };
  timeouts.splice(findTimeout(time), 0, timeout);
  pendingCount += 1;
  start();
  return timeout;
};

let findTimeout = time => ~(~timeouts.findIndex(t => t.time > time) || ~timeouts.length);

raf.cancel = fn => {
  onStartQueue.delete(fn);
  onFrameQueue.delete(fn);
  updateQueue.delete(fn);
  writeQueue.delete(fn);
  onFinishQueue.delete(fn);
};

raf.sync = fn => {
  sync = true;
  raf.batchedUpdates(fn);
  sync = false;
};

raf.throttle = fn => {
  let lastArgs;

  function queuedFn() {
    try {
      fn(...lastArgs);
    } finally {
      lastArgs = null;
    }
  }

  function throttled(...args) {
    lastArgs = args;
    raf.onStart(queuedFn);
  }

  throttled.handler = fn;

  throttled.cancel = () => {
    onStartQueue.delete(queuedFn);
    lastArgs = null;
  };

  return throttled;
};

let nativeRaf = typeof window != 'undefined' ? window.requestAnimationFrame : () => {};

raf.use = impl => nativeRaf = impl;

raf.now = typeof performance != 'undefined' ? () => performance.now() : Date.now;

raf.batchedUpdates = fn => fn();

raf.catch = console.error;
raf.frameLoop = 'always';

raf.advance = () => {
  if (raf.frameLoop !== 'demand') {
    console.warn('Cannot call the manual advancement of rafz whilst frameLoop is not set as demand');
  } else {
    react_spring_rafz_esm_update();
  }
};

let ts = -1;
let pendingCount = 0;
let sync = false;

function schedule(fn, queue) {
  if (sync) {
    queue.delete(fn);
    fn(0);
  } else {
    queue.add(fn);
    start();
  }
}

function start() {
  if (ts < 0) {
    ts = 0;

    if (raf.frameLoop !== 'demand') {
      nativeRaf(loop);
    }
  }
}

function stop() {
  ts = -1;
}

function loop() {
  if (~ts) {
    nativeRaf(loop);
    raf.batchedUpdates(react_spring_rafz_esm_update);
  }
}

function react_spring_rafz_esm_update() {
  let prevTs = ts;
  ts = raf.now();
  let count = findTimeout(ts);

  if (count) {
    eachSafely(timeouts.splice(0, count), t => t.handler());
    pendingCount -= count;
  }

  onStartQueue.flush();
  updateQueue.flush(prevTs ? Math.min(64, ts - prevTs) : 16.667);
  onFrameQueue.flush();
  writeQueue.flush();
  onFinishQueue.flush();

  if (!pendingCount) {
    stop();
  }
}

function makeQueue() {
  let next = new Set();
  let current = next;
  return {
    add(fn) {
      pendingCount += current == next && !next.has(fn) ? 1 : 0;
      next.add(fn);
    },

    delete(fn) {
      pendingCount -= current == next && next.has(fn) ? 1 : 0;
      return next.delete(fn);
    },

    flush(arg) {
      if (current.size) {
        next = new Set();
        pendingCount -= current.size;
        eachSafely(current, fn => fn(arg) && next.add(fn));
        pendingCount += next.size;
        current = next;
      }
    }

  };
}

function eachSafely(values, each) {
  values.forEach(value => {
    try {
      each(value);
    } catch (e) {
      raf.catch(e);
    }
  });
}

const __raf = {
  count() {
    return pendingCount;
  },

  isRunning() {
    return ts >= 0;
  },

  clear() {
    ts = -1;
    timeouts = [];
    onStartQueue = makeQueue();
    updateQueue = makeQueue();
    onFrameQueue = makeQueue();
    writeQueue = makeQueue();
    onFinishQueue = makeQueue();
    pendingCount = 0;
  }

};



// EXTERNAL MODULE: external "React"
var external_React_ = __webpack_require__(6);

// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/@react-spring+shared@9.4.3_react@17.0.2/node_modules/@react-spring/shared/dist/react-spring-shared.esm.js





function noop() {}
const defineHidden = (obj, key, value) => Object.defineProperty(obj, key, {
  value,
  writable: true,
  configurable: true
});
const is = {
  arr: Array.isArray,
  obj: a => !!a && a.constructor.name === 'Object',
  fun: a => typeof a === 'function',
  str: a => typeof a === 'string',
  num: a => typeof a === 'number',
  und: a => a === undefined
};
function isEqual(a, b) {
  if (is.arr(a)) {
    if (!is.arr(b) || a.length !== b.length) return false;

    for (let i = 0; i < a.length; i++) {
      if (a[i] !== b[i]) return false;
    }

    return true;
  }

  return a === b;
}
const each = (obj, fn) => obj.forEach(fn);
function eachProp(obj, fn, ctx) {
  if (is.arr(obj)) {
    for (let i = 0; i < obj.length; i++) {
      fn.call(ctx, obj[i], `${i}`);
    }

    return;
  }

  for (const key in obj) {
    if (obj.hasOwnProperty(key)) {
      fn.call(ctx, obj[key], key);
    }
  }
}
const toArray = a => is.und(a) ? [] : is.arr(a) ? a : [a];
function flush(queue, iterator) {
  if (queue.size) {
    const items = Array.from(queue);
    queue.clear();
    each(items, iterator);
  }
}
const flushCalls = (queue, ...args) => flush(queue, fn => fn(...args));
const isSSR = () => typeof window === 'undefined' || !window.navigator || /ServerSideRendering|^Deno\//.test(window.navigator.userAgent);

let createStringInterpolator$1;
let to;
let colors$1 = null;
let skipAnimation = false;
let willAdvance = noop;
const react_spring_shared_esm_assign = globals => {
  if (globals.to) to = globals.to;
  if (globals.now) raf.now = globals.now;
  if (globals.colors !== undefined) colors$1 = globals.colors;
  if (globals.skipAnimation != null) skipAnimation = globals.skipAnimation;
  if (globals.createStringInterpolator) createStringInterpolator$1 = globals.createStringInterpolator;
  if (globals.requestAnimationFrame) raf.use(globals.requestAnimationFrame);
  if (globals.batchedUpdates) raf.batchedUpdates = globals.batchedUpdates;
  if (globals.willAdvance) willAdvance = globals.willAdvance;
  if (globals.frameLoop) raf.frameLoop = globals.frameLoop;
};

var react_spring_shared_esm_globals = /*#__PURE__*/Object.freeze({
  __proto__: null,
  get createStringInterpolator () { return createStringInterpolator$1; },
  get to () { return to; },
  get colors () { return colors$1; },
  get skipAnimation () { return skipAnimation; },
  get willAdvance () { return willAdvance; },
  assign: react_spring_shared_esm_assign
});

const startQueue = new Set();
let currentFrame = [];
let prevFrame = [];
let priority = 0;
const frameLoop = {
  get idle() {
    return !startQueue.size && !currentFrame.length;
  },

  start(animation) {
    if (priority > animation.priority) {
      startQueue.add(animation);
      raf.onStart(flushStartQueue);
    } else {
      startSafely(animation);
      raf(advance);
    }
  },

  advance,

  sort(animation) {
    if (priority) {
      raf.onFrame(() => frameLoop.sort(animation));
    } else {
      const prevIndex = currentFrame.indexOf(animation);

      if (~prevIndex) {
        currentFrame.splice(prevIndex, 1);
        startUnsafely(animation);
      }
    }
  },

  clear() {
    currentFrame = [];
    startQueue.clear();
  }

};

function flushStartQueue() {
  startQueue.forEach(startSafely);
  startQueue.clear();
  raf(advance);
}

function startSafely(animation) {
  if (!currentFrame.includes(animation)) startUnsafely(animation);
}

function startUnsafely(animation) {
  currentFrame.splice(findIndex(currentFrame, other => other.priority > animation.priority), 0, animation);
}

function advance(dt) {
  const nextFrame = prevFrame;

  for (let i = 0; i < currentFrame.length; i++) {
    const animation = currentFrame[i];
    priority = animation.priority;

    if (!animation.idle) {
      willAdvance(animation);
      animation.advance(dt);

      if (!animation.idle) {
        nextFrame.push(animation);
      }
    }
  }

  priority = 0;
  prevFrame = currentFrame;
  prevFrame.length = 0;
  currentFrame = nextFrame;
  return currentFrame.length > 0;
}

function findIndex(arr, test) {
  const index = arr.findIndex(test);
  return index < 0 ? arr.length : index;
}

const colors = {
  transparent: 0x00000000,
  aliceblue: 0xf0f8ffff,
  antiquewhite: 0xfaebd7ff,
  aqua: 0x00ffffff,
  aquamarine: 0x7fffd4ff,
  azure: 0xf0ffffff,
  beige: 0xf5f5dcff,
  bisque: 0xffe4c4ff,
  black: 0x000000ff,
  blanchedalmond: 0xffebcdff,
  blue: 0x0000ffff,
  blueviolet: 0x8a2be2ff,
  brown: 0xa52a2aff,
  burlywood: 0xdeb887ff,
  burntsienna: 0xea7e5dff,
  cadetblue: 0x5f9ea0ff,
  chartreuse: 0x7fff00ff,
  chocolate: 0xd2691eff,
  coral: 0xff7f50ff,
  cornflowerblue: 0x6495edff,
  cornsilk: 0xfff8dcff,
  crimson: 0xdc143cff,
  cyan: 0x00ffffff,
  darkblue: 0x00008bff,
  darkcyan: 0x008b8bff,
  darkgoldenrod: 0xb8860bff,
  darkgray: 0xa9a9a9ff,
  darkgreen: 0x006400ff,
  darkgrey: 0xa9a9a9ff,
  darkkhaki: 0xbdb76bff,
  darkmagenta: 0x8b008bff,
  darkolivegreen: 0x556b2fff,
  darkorange: 0xff8c00ff,
  darkorchid: 0x9932ccff,
  darkred: 0x8b0000ff,
  darksalmon: 0xe9967aff,
  darkseagreen: 0x8fbc8fff,
  darkslateblue: 0x483d8bff,
  darkslategray: 0x2f4f4fff,
  darkslategrey: 0x2f4f4fff,
  darkturquoise: 0x00ced1ff,
  darkviolet: 0x9400d3ff,
  deeppink: 0xff1493ff,
  deepskyblue: 0x00bfffff,
  dimgray: 0x696969ff,
  dimgrey: 0x696969ff,
  dodgerblue: 0x1e90ffff,
  firebrick: 0xb22222ff,
  floralwhite: 0xfffaf0ff,
  forestgreen: 0x228b22ff,
  fuchsia: 0xff00ffff,
  gainsboro: 0xdcdcdcff,
  ghostwhite: 0xf8f8ffff,
  gold: 0xffd700ff,
  goldenrod: 0xdaa520ff,
  gray: 0x808080ff,
  green: 0x008000ff,
  greenyellow: 0xadff2fff,
  grey: 0x808080ff,
  honeydew: 0xf0fff0ff,
  hotpink: 0xff69b4ff,
  indianred: 0xcd5c5cff,
  indigo: 0x4b0082ff,
  ivory: 0xfffff0ff,
  khaki: 0xf0e68cff,
  lavender: 0xe6e6faff,
  lavenderblush: 0xfff0f5ff,
  lawngreen: 0x7cfc00ff,
  lemonchiffon: 0xfffacdff,
  lightblue: 0xadd8e6ff,
  lightcoral: 0xf08080ff,
  lightcyan: 0xe0ffffff,
  lightgoldenrodyellow: 0xfafad2ff,
  lightgray: 0xd3d3d3ff,
  lightgreen: 0x90ee90ff,
  lightgrey: 0xd3d3d3ff,
  lightpink: 0xffb6c1ff,
  lightsalmon: 0xffa07aff,
  lightseagreen: 0x20b2aaff,
  lightskyblue: 0x87cefaff,
  lightslategray: 0x778899ff,
  lightslategrey: 0x778899ff,
  lightsteelblue: 0xb0c4deff,
  lightyellow: 0xffffe0ff,
  lime: 0x00ff00ff,
  limegreen: 0x32cd32ff,
  linen: 0xfaf0e6ff,
  magenta: 0xff00ffff,
  maroon: 0x800000ff,
  mediumaquamarine: 0x66cdaaff,
  mediumblue: 0x0000cdff,
  mediumorchid: 0xba55d3ff,
  mediumpurple: 0x9370dbff,
  mediumseagreen: 0x3cb371ff,
  mediumslateblue: 0x7b68eeff,
  mediumspringgreen: 0x00fa9aff,
  mediumturquoise: 0x48d1ccff,
  mediumvioletred: 0xc71585ff,
  midnightblue: 0x191970ff,
  mintcream: 0xf5fffaff,
  mistyrose: 0xffe4e1ff,
  moccasin: 0xffe4b5ff,
  navajowhite: 0xffdeadff,
  navy: 0x000080ff,
  oldlace: 0xfdf5e6ff,
  olive: 0x808000ff,
  olivedrab: 0x6b8e23ff,
  orange: 0xffa500ff,
  orangered: 0xff4500ff,
  orchid: 0xda70d6ff,
  palegoldenrod: 0xeee8aaff,
  palegreen: 0x98fb98ff,
  paleturquoise: 0xafeeeeff,
  palevioletred: 0xdb7093ff,
  papayawhip: 0xffefd5ff,
  peachpuff: 0xffdab9ff,
  peru: 0xcd853fff,
  pink: 0xffc0cbff,
  plum: 0xdda0ddff,
  powderblue: 0xb0e0e6ff,
  purple: 0x800080ff,
  rebeccapurple: 0x663399ff,
  red: 0xff0000ff,
  rosybrown: 0xbc8f8fff,
  royalblue: 0x4169e1ff,
  saddlebrown: 0x8b4513ff,
  salmon: 0xfa8072ff,
  sandybrown: 0xf4a460ff,
  seagreen: 0x2e8b57ff,
  seashell: 0xfff5eeff,
  sienna: 0xa0522dff,
  silver: 0xc0c0c0ff,
  skyblue: 0x87ceebff,
  slateblue: 0x6a5acdff,
  slategray: 0x708090ff,
  slategrey: 0x708090ff,
  snow: 0xfffafaff,
  springgreen: 0x00ff7fff,
  steelblue: 0x4682b4ff,
  tan: 0xd2b48cff,
  teal: 0x008080ff,
  thistle: 0xd8bfd8ff,
  tomato: 0xff6347ff,
  turquoise: 0x40e0d0ff,
  violet: 0xee82eeff,
  wheat: 0xf5deb3ff,
  white: 0xffffffff,
  whitesmoke: 0xf5f5f5ff,
  yellow: 0xffff00ff,
  yellowgreen: 0x9acd32ff
};

const NUMBER = '[-+]?\\d*\\.?\\d+';
const PERCENTAGE = NUMBER + '%';

function call(...parts) {
  return '\\(\\s*(' + parts.join(')\\s*,\\s*(') + ')\\s*\\)';
}

const rgb = new RegExp('rgb' + call(NUMBER, NUMBER, NUMBER));
const rgba = new RegExp('rgba' + call(NUMBER, NUMBER, NUMBER, NUMBER));
const hsl = new RegExp('hsl' + call(NUMBER, PERCENTAGE, PERCENTAGE));
const hsla = new RegExp('hsla' + call(NUMBER, PERCENTAGE, PERCENTAGE, NUMBER));
const hex3 = /^#([0-9a-fA-F]{1})([0-9a-fA-F]{1})([0-9a-fA-F]{1})$/;
const hex4 = /^#([0-9a-fA-F]{1})([0-9a-fA-F]{1})([0-9a-fA-F]{1})([0-9a-fA-F]{1})$/;
const hex6 = /^#([0-9a-fA-F]{6})$/;
const hex8 = /^#([0-9a-fA-F]{8})$/;

function normalizeColor(color) {
  let match;

  if (typeof color === 'number') {
    return color >>> 0 === color && color >= 0 && color <= 0xffffffff ? color : null;
  }

  if (match = hex6.exec(color)) return parseInt(match[1] + 'ff', 16) >>> 0;

  if (colors$1 && colors$1[color] !== undefined) {
    return colors$1[color];
  }

  if (match = rgb.exec(color)) {
    return (parse255(match[1]) << 24 | parse255(match[2]) << 16 | parse255(match[3]) << 8 | 0x000000ff) >>> 0;
  }

  if (match = rgba.exec(color)) {
    return (parse255(match[1]) << 24 | parse255(match[2]) << 16 | parse255(match[3]) << 8 | parse1(match[4])) >>> 0;
  }

  if (match = hex3.exec(color)) {
    return parseInt(match[1] + match[1] + match[2] + match[2] + match[3] + match[3] + 'ff', 16) >>> 0;
  }

  if (match = hex8.exec(color)) return parseInt(match[1], 16) >>> 0;

  if (match = hex4.exec(color)) {
    return parseInt(match[1] + match[1] + match[2] + match[2] + match[3] + match[3] + match[4] + match[4], 16) >>> 0;
  }

  if (match = hsl.exec(color)) {
    return (hslToRgb(parse360(match[1]), parsePercentage(match[2]), parsePercentage(match[3])) | 0x000000ff) >>> 0;
  }

  if (match = hsla.exec(color)) {
    return (hslToRgb(parse360(match[1]), parsePercentage(match[2]), parsePercentage(match[3])) | parse1(match[4])) >>> 0;
  }

  return null;
}

function hue2rgb(p, q, t) {
  if (t < 0) t += 1;
  if (t > 1) t -= 1;
  if (t < 1 / 6) return p + (q - p) * 6 * t;
  if (t < 1 / 2) return q;
  if (t < 2 / 3) return p + (q - p) * (2 / 3 - t) * 6;
  return p;
}

function hslToRgb(h, s, l) {
  const q = l < 0.5 ? l * (1 + s) : l + s - l * s;
  const p = 2 * l - q;
  const r = hue2rgb(p, q, h + 1 / 3);
  const g = hue2rgb(p, q, h);
  const b = hue2rgb(p, q, h - 1 / 3);
  return Math.round(r * 255) << 24 | Math.round(g * 255) << 16 | Math.round(b * 255) << 8;
}

function parse255(str) {
  const int = parseInt(str, 10);
  if (int < 0) return 0;
  if (int > 255) return 255;
  return int;
}

function parse360(str) {
  const int = parseFloat(str);
  return (int % 360 + 360) % 360 / 360;
}

function parse1(str) {
  const num = parseFloat(str);
  if (num < 0) return 0;
  if (num > 1) return 255;
  return Math.round(num * 255);
}

function parsePercentage(str) {
  const int = parseFloat(str);
  if (int < 0) return 0;
  if (int > 100) return 1;
  return int / 100;
}

function colorToRgba(input) {
  let int32Color = normalizeColor(input);
  if (int32Color === null) return input;
  int32Color = int32Color || 0;
  let r = (int32Color & 0xff000000) >>> 24;
  let g = (int32Color & 0x00ff0000) >>> 16;
  let b = (int32Color & 0x0000ff00) >>> 8;
  let a = (int32Color & 0x000000ff) / 255;
  return `rgba(${r}, ${g}, ${b}, ${a})`;
}

const createInterpolator = (range, output, extrapolate) => {
  if (is.fun(range)) {
    return range;
  }

  if (is.arr(range)) {
    return createInterpolator({
      range,
      output: output,
      extrapolate
    });
  }

  if (is.str(range.output[0])) {
    return createStringInterpolator$1(range);
  }

  const config = range;
  const outputRange = config.output;
  const inputRange = config.range || [0, 1];
  const extrapolateLeft = config.extrapolateLeft || config.extrapolate || 'extend';
  const extrapolateRight = config.extrapolateRight || config.extrapolate || 'extend';

  const easing = config.easing || (t => t);

  return input => {
    const range = findRange(input, inputRange);
    return interpolate(input, inputRange[range], inputRange[range + 1], outputRange[range], outputRange[range + 1], easing, extrapolateLeft, extrapolateRight, config.map);
  };
};

function interpolate(input, inputMin, inputMax, outputMin, outputMax, easing, extrapolateLeft, extrapolateRight, map) {
  let result = map ? map(input) : input;

  if (result < inputMin) {
    if (extrapolateLeft === 'identity') return result;else if (extrapolateLeft === 'clamp') result = inputMin;
  }

  if (result > inputMax) {
    if (extrapolateRight === 'identity') return result;else if (extrapolateRight === 'clamp') result = inputMax;
  }

  if (outputMin === outputMax) return outputMin;
  if (inputMin === inputMax) return input <= inputMin ? outputMin : outputMax;
  if (inputMin === -Infinity) result = -result;else if (inputMax === Infinity) result = result - inputMin;else result = (result - inputMin) / (inputMax - inputMin);
  result = easing(result);
  if (outputMin === -Infinity) result = -result;else if (outputMax === Infinity) result = result + outputMin;else result = result * (outputMax - outputMin) + outputMin;
  return result;
}

function findRange(input, inputRange) {
  for (var i = 1; i < inputRange.length - 1; ++i) if (inputRange[i] >= input) break;

  return i - 1;
}

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

const $get = Symbol.for('FluidValue.get');
const $observers = Symbol.for('FluidValue.observers');

const hasFluidValue = arg => Boolean(arg && arg[$get]);

const getFluidValue = arg => arg && arg[$get] ? arg[$get]() : arg;

const getFluidObservers = target => target[$observers] || null;

function callFluidObserver(observer, event) {
  if (observer.eventObserved) {
    observer.eventObserved(event);
  } else {
    observer(event);
  }
}

function callFluidObservers(target, event) {
  let observers = target[$observers];

  if (observers) {
    observers.forEach(observer => {
      callFluidObserver(observer, event);
    });
  }
}

class FluidValue {
  constructor(get) {
    this[$get] = void 0;
    this[$observers] = void 0;

    if (!get && !(get = this.get)) {
      throw Error('Unknown getter');
    }

    setFluidGetter(this, get);
  }

}

const setFluidGetter = (target, get) => setHidden(target, $get, get);

function addFluidObserver(target, observer) {
  if (target[$get]) {
    let observers = target[$observers];

    if (!observers) {
      setHidden(target, $observers, observers = new Set());
    }

    if (!observers.has(observer)) {
      observers.add(observer);

      if (target.observerAdded) {
        target.observerAdded(observers.size, observer);
      }
    }
  }

  return observer;
}

function removeFluidObserver(target, observer) {
  let observers = target[$observers];

  if (observers && observers.has(observer)) {
    const count = observers.size - 1;

    if (count) {
      observers.delete(observer);
    } else {
      target[$observers] = null;
    }

    if (target.observerRemoved) {
      target.observerRemoved(count, observer);
    }
  }
}

const setHidden = (target, key, value) => Object.defineProperty(target, key, {
  value,
  writable: true,
  configurable: true
});

const numberRegex = /[+\-]?(?:0|[1-9]\d*)(?:\.\d*)?(?:[eE][+\-]?\d+)?/g;
const colorRegex = /(#(?:[0-9a-f]{2}){2,4}|(#[0-9a-f]{3})|(rgb|hsl)a?\((-?\d+%?[,\s]+){2,3}\s*[\d\.]+%?\))/gi;
const unitRegex = new RegExp(`(${numberRegex.source})(%|[a-z]+)`, 'i');
const rgbaRegex = /rgba\(([0-9\.-]+), ([0-9\.-]+), ([0-9\.-]+), ([0-9\.-]+)\)/gi;
const cssVariableRegex = /var\((--[a-zA-Z0-9-_]+),? ?([a-zA-Z0-9 ()%#.,-]+)?\)/;

const variableToRgba = input => {
  const [token, fallback] = parseCSSVariable(input);

  if (!token || isSSR()) {
    return input;
  }

  const value = window.getComputedStyle(document.documentElement).getPropertyValue(token);

  if (value) {
    return value.trim();
  } else if (fallback && fallback.startsWith('--')) {
    const _value = window.getComputedStyle(document.documentElement).getPropertyValue(fallback);

    if (_value) {
      return _value;
    } else {
      return input;
    }
  } else if (fallback && cssVariableRegex.test(fallback)) {
    return variableToRgba(fallback);
  } else if (fallback) {
    return fallback;
  }

  return input;
};

const parseCSSVariable = current => {
  const match = cssVariableRegex.exec(current);
  if (!match) return [,];
  const [, token, fallback] = match;
  return [token, fallback];
};

let namedColorRegex;

const rgbaRound = (_, p1, p2, p3, p4) => `rgba(${Math.round(p1)}, ${Math.round(p2)}, ${Math.round(p3)}, ${p4})`;

const createStringInterpolator = config => {
  if (!namedColorRegex) namedColorRegex = colors$1 ? new RegExp(`(${Object.keys(colors$1).join('|')})(?!\\w)`, 'g') : /^\b$/;
  const output = config.output.map(value => {
    return getFluidValue(value).replace(cssVariableRegex, variableToRgba).replace(colorRegex, colorToRgba).replace(namedColorRegex, colorToRgba);
  });
  const keyframes = output.map(value => value.match(numberRegex).map(Number));
  const outputRanges = keyframes[0].map((_, i) => keyframes.map(values => {
    if (!(i in values)) {
      throw Error('The arity of each "output" value must be equal');
    }

    return values[i];
  }));
  const interpolators = outputRanges.map(output => createInterpolator(_extends({}, config, {
    output
  })));
  return input => {
    var _output$find;

    const missingUnit = !unitRegex.test(output[0]) && ((_output$find = output.find(value => unitRegex.test(value))) == null ? void 0 : _output$find.replace(numberRegex, ''));
    let i = 0;
    return output[0].replace(numberRegex, () => `${interpolators[i++](input)}${missingUnit || ''}`).replace(rgbaRegex, rgbaRound);
  };
};

const prefix = 'react-spring: ';

const once = fn => {
  const func = fn;
  let called = false;

  if (typeof func != 'function') {
    throw new TypeError(`${prefix}once requires a function parameter`);
  }

  return (...args) => {
    if (!called) {
      func(...args);
      called = true;
    }
  };
};

const warnInterpolate = once(console.warn);
function deprecateInterpolate() {
  warnInterpolate(`${prefix}The "interpolate" function is deprecated in v9 (use "to" instead)`);
}
const warnDirectCall = once(console.warn);
function deprecateDirectCall() {
  warnDirectCall(`${prefix}Directly calling start instead of using the api object is deprecated in v9 (use ".start" instead), this will be removed in later 0.X.0 versions`);
}

function isAnimatedString(value) {
  return is.str(value) && (value[0] == '#' || /\d/.test(value) || !isSSR() && cssVariableRegex.test(value) || value in (colors$1 || {}));
}

const useOnce = effect => Object(external_React_["useEffect"])(effect, emptyDeps);
const emptyDeps = [];

function useForceUpdate() {
  const update = Object(external_React_["useState"])()[1];
  const mounted = Object(external_React_["useState"])(makeMountedRef)[0];
  useOnce(mounted.unmount);
  return () => {
    if (mounted.current) {
      update({});
    }
  };
}

function makeMountedRef() {
  const mounted = {
    current: true,
    unmount: () => () => {
      mounted.current = false;
    }
  };
  return mounted;
}

function useMemoOne(getResult, inputs) {
  const [initial] = Object(external_React_["useState"])(() => ({
    inputs,
    result: getResult()
  }));
  const committed = Object(external_React_["useRef"])();
  const prevCache = committed.current;
  let cache = prevCache;

  if (cache) {
    const useCache = Boolean(inputs && cache.inputs && areInputsEqual(inputs, cache.inputs));

    if (!useCache) {
      cache = {
        inputs,
        result: getResult()
      };
    }
  } else {
    cache = initial;
  }

  Object(external_React_["useEffect"])(() => {
    committed.current = cache;

    if (prevCache == initial) {
      initial.inputs = initial.result = undefined;
    }
  }, [cache]);
  return cache.result;
}

function areInputsEqual(next, prev) {
  if (next.length !== prev.length) {
    return false;
  }

  for (let i = 0; i < next.length; i++) {
    if (next[i] !== prev[i]) {
      return false;
    }
  }

  return true;
}

function usePrev(value) {
  const prevRef = Object(external_React_["useRef"])();
  Object(external_React_["useEffect"])(() => {
    prevRef.current = value;
  });
  return prevRef.current;
}

const useLayoutEffect = typeof window !== 'undefined' && window.document && window.document.createElement ? external_React_["useLayoutEffect"] : external_React_["useEffect"];




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

/***/ 308:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* WEBPACK VAR INJECTION */(function(global) {/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_notices__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(144);
/* harmony import */ var _wordpress_notices__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_notices__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _woocommerce_data__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(12);
/* harmony import */ var _woocommerce_data__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_data__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _stylesheets_index_scss__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(310);
/* harmony import */ var _stylesheets_index_scss__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_stylesheets_index_scss__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _utils_admin_settings__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(23);
/* harmony import */ var _layout__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(135);
/* harmony import */ var _customer_effort_score_tracks__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(297);
/* harmony import */ var _embedded_body_layout__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(298);


/**
 * External dependencies
 */



/**
 * Internal dependencies
 */





 // Modify webpack pubilcPath at runtime based on location of WordPress Plugin.
// eslint-disable-next-line no-undef,camelcase

__webpack_require__.p = global.wcAdminAssets.path;
const appRoot = document.getElementById('root');
const embeddedRoot = document.getElementById('woocommerce-embedded-root');
const settingsGroup = 'wc_admin';
const hydrateUser = Object(_utils_admin_settings__WEBPACK_IMPORTED_MODULE_4__[/* getAdminSetting */ "d"])('currentUserData');

if (appRoot) {
  let HydratedPageLayout = Object(_woocommerce_data__WEBPACK_IMPORTED_MODULE_2__["withSettingsHydration"])(settingsGroup, window.wcSettings.admin)(_layout__WEBPACK_IMPORTED_MODULE_5__[/* PageLayout */ "b"]);
  const preloadSettings = window.wcSettings.admin ? window.wcSettings.admin.preloadSettings : false;
  const hydrateSettings = preloadSettings && preloadSettings.general;

  if (hydrateSettings) {
    HydratedPageLayout = Object(_woocommerce_data__WEBPACK_IMPORTED_MODULE_2__["withSettingsHydration"])('general', {
      general: preloadSettings.general
    })(HydratedPageLayout);
  }

  if (hydrateUser) {
    HydratedPageLayout = Object(_woocommerce_data__WEBPACK_IMPORTED_MODULE_2__["withCurrentUserHydration"])(hydrateUser)(HydratedPageLayout);
  }

  Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["render"])(Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(HydratedPageLayout, null), appRoot);
} else if (embeddedRoot) {
  let HydratedEmbedLayout = Object(_woocommerce_data__WEBPACK_IMPORTED_MODULE_2__["withSettingsHydration"])(settingsGroup, window.wcSettings.admin)(_layout__WEBPACK_IMPORTED_MODULE_5__[/* EmbedLayout */ "a"]);

  if (hydrateUser) {
    HydratedEmbedLayout = Object(_woocommerce_data__WEBPACK_IMPORTED_MODULE_2__["withCurrentUserHydration"])(hydrateUser)(HydratedEmbedLayout);
  } // Render the header.


  Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["render"])(Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(HydratedEmbedLayout, null), embeddedRoot);
  embeddedRoot.classList.remove('is-embed-loading'); // Render notices just above the WP content div.

  const wpBody = document.getElementById('wpbody-content');
  const wrap = wpBody.querySelector('.wrap.woocommerce') || wpBody.querySelector('.wrap');
  const noticeContainer = document.createElement('div');
  Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["render"])(Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("div", {
    className: "woocommerce-layout"
  }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_layout__WEBPACK_IMPORTED_MODULE_5__[/* PrimaryLayout */ "c"], null)), wpBody.insertBefore(noticeContainer, wrap));
  const embeddedBodyContainer = document.createElement('div');
  Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["render"])(Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_embedded_body_layout__WEBPACK_IMPORTED_MODULE_7__[/* EmbeddedBodyLayout */ "a"], null), wpBody.insertBefore(embeddedBodyContainer, wrap.nextSibling));
} // Render the CustomerEffortScoreTracksContainer only if
// the feature flag is enabled.


if (window.wcAdminFeatures && window.wcAdminFeatures['customer-effort-score-tracks'] === true) {
  // Set up customer effort score survey.
  (function () {
    const root = appRoot || embeddedRoot;
    Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["render"])(Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_customer_effort_score_tracks__WEBPACK_IMPORTED_MODULE_6__[/* CustomerEffortScoreTracksContainer */ "a"], null), root.insertBefore(document.createElement('div'), null));
  })();
}
/* WEBPACK VAR INJECTION */}.call(this, __webpack_require__(92)))

/***/ }),

/***/ 31:
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var stringify = __webpack_require__(70);
var parse = __webpack_require__(72);
var formats = __webpack_require__(39);

module.exports = {
    formats: formats,
    parse: parse,
    stringify: stringify
};


/***/ }),

/***/ 310:
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),

/***/ 311:
/***/ (function(module, exports) {

(function() { module.exports = window["wc"]["notices"]; }());

/***/ }),

/***/ 312:
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),

/***/ 313:
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),

/***/ 314:
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),

/***/ 315:
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),

/***/ 316:
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),

/***/ 317:
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),

/***/ 318:
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),

/***/ 319:
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),

/***/ 32:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* unused harmony export Animated */
/* unused harmony export AnimatedArray */
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "a", function() { return AnimatedObject; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "b", function() { return AnimatedString; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "c", function() { return AnimatedValue; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "d", function() { return createHost; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "e", function() { return getAnimated; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "f", function() { return getAnimatedType; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "g", function() { return getPayload; });
/* unused harmony export isAnimated */
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "h", function() { return setAnimated; });
/* harmony import */ var _react_spring_shared__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(3);
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(6);
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_1__);




const $node = Symbol.for('Animated:node');
const isAnimated = value => !!value && value[$node] === value;
const getAnimated = owner => owner && owner[$node];
const setAnimated = (owner, node) => Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* defineHidden */ "h"])(owner, $node, node);
const getPayload = owner => owner && owner[$node] && owner[$node].getPayload();
class Animated {
  constructor() {
    this.payload = void 0;
    setAnimated(this, this);
  }

  getPayload() {
    return this.payload || [];
  }

}

class AnimatedValue extends Animated {
  constructor(_value) {
    super();
    this.done = true;
    this.elapsedTime = void 0;
    this.lastPosition = void 0;
    this.lastVelocity = void 0;
    this.v0 = void 0;
    this.durationProgress = 0;
    this._value = _value;

    if (_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* is */ "s"].num(this._value)) {
      this.lastPosition = this._value;
    }
  }

  static create(value) {
    return new AnimatedValue(value);
  }

  getPayload() {
    return [this];
  }

  getValue() {
    return this._value;
  }

  setValue(value, step) {
    if (_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* is */ "s"].num(value)) {
      this.lastPosition = value;

      if (step) {
        value = Math.round(value / step) * step;

        if (this.done) {
          this.lastPosition = value;
        }
      }
    }

    if (this._value === value) {
      return false;
    }

    this._value = value;
    return true;
  }

  reset() {
    const {
      done
    } = this;
    this.done = false;

    if (_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* is */ "s"].num(this._value)) {
      this.elapsedTime = 0;
      this.durationProgress = 0;
      this.lastPosition = this._value;
      if (done) this.lastVelocity = null;
      this.v0 = null;
    }
  }

}

class AnimatedString extends AnimatedValue {
  constructor(value) {
    super(0);
    this._string = null;
    this._toString = void 0;
    this._toString = Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* createInterpolator */ "f"])({
      output: [value, value]
    });
  }

  static create(value) {
    return new AnimatedString(value);
  }

  getValue() {
    let value = this._string;
    return value == null ? this._string = this._toString(this._value) : value;
  }

  setValue(value) {
    if (_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* is */ "s"].str(value)) {
      if (value == this._string) {
        return false;
      }

      this._string = value;
      this._value = 1;
    } else if (super.setValue(value)) {
      this._string = null;
    } else {
      return false;
    }

    return true;
  }

  reset(goal) {
    if (goal) {
      this._toString = Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* createInterpolator */ "f"])({
        output: [this.getValue(), goal]
      });
    }

    this._value = 0;
    super.reset();
  }

}

const TreeContext = {
  dependencies: null
};

class AnimatedObject extends Animated {
  constructor(source) {
    super();
    this.source = source;
    this.setValue(source);
  }

  getValue(animated) {
    const values = {};
    Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* eachProp */ "l"])(this.source, (source, key) => {
      if (isAnimated(source)) {
        values[key] = source.getValue(animated);
      } else if (Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* hasFluidValue */ "r"])(source)) {
        values[key] = Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* getFluidValue */ "q"])(source);
      } else if (!animated) {
        values[key] = source;
      }
    });
    return values;
  }

  setValue(source) {
    this.source = source;
    this.payload = this._makePayload(source);
  }

  reset() {
    if (this.payload) {
      Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* each */ "k"])(this.payload, node => node.reset());
    }
  }

  _makePayload(source) {
    if (source) {
      const payload = new Set();
      Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* eachProp */ "l"])(source, this._addToPayload, payload);
      return Array.from(payload);
    }
  }

  _addToPayload(source) {
    if (TreeContext.dependencies && Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* hasFluidValue */ "r"])(source)) {
      TreeContext.dependencies.add(source);
    }

    const payload = getPayload(source);

    if (payload) {
      Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* each */ "k"])(payload, node => this.add(node));
    }
  }

}

class AnimatedArray extends AnimatedObject {
  constructor(source) {
    super(source);
  }

  static create(source) {
    return new AnimatedArray(source);
  }

  getValue() {
    return this.source.map(node => node.getValue());
  }

  setValue(source) {
    const payload = this.getPayload();

    if (source.length == payload.length) {
      return payload.map((node, i) => node.setValue(source[i])).some(Boolean);
    }

    super.setValue(source.map(makeAnimated));
    return true;
  }

}

function makeAnimated(value) {
  const nodeType = Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* isAnimatedString */ "t"])(value) ? AnimatedString : AnimatedValue;
  return nodeType.create(value);
}

function getAnimatedType(value) {
  const parentNode = getAnimated(value);
  return parentNode ? parentNode.constructor : _react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* is */ "s"].arr(value) ? AnimatedArray : Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* isAnimatedString */ "t"])(value) ? AnimatedString : AnimatedValue;
}

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

const withAnimated = (Component, host) => {
  const hasInstance = !_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* is */ "s"].fun(Component) || Component.prototype && Component.prototype.isReactComponent;
  return Object(react__WEBPACK_IMPORTED_MODULE_1__["forwardRef"])((givenProps, givenRef) => {
    const instanceRef = Object(react__WEBPACK_IMPORTED_MODULE_1__["useRef"])(null);
    const ref = hasInstance && Object(react__WEBPACK_IMPORTED_MODULE_1__["useCallback"])(value => {
      instanceRef.current = updateRef(givenRef, value);
    }, [givenRef]);
    const [props, deps] = getAnimatedState(givenProps, host);
    const forceUpdate = Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* useForceUpdate */ "z"])();

    const callback = () => {
      const instance = instanceRef.current;

      if (hasInstance && !instance) {
        return;
      }

      const didUpdate = instance ? host.applyAnimatedValues(instance, props.getValue(true)) : false;

      if (didUpdate === false) {
        forceUpdate();
      }
    };

    const observer = new PropsObserver(callback, deps);
    const observerRef = Object(react__WEBPACK_IMPORTED_MODULE_1__["useRef"])();
    Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* useLayoutEffect */ "A"])(() => {
      const lastObserver = observerRef.current;
      observerRef.current = observer;
      Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* each */ "k"])(deps, dep => Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* addFluidObserver */ "c"])(dep, observer));

      if (lastObserver) {
        Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* each */ "k"])(lastObserver.deps, dep => Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* removeFluidObserver */ "x"])(dep, lastObserver));
        _react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* raf */ "w"].cancel(lastObserver.update);
      }
    });
    Object(react__WEBPACK_IMPORTED_MODULE_1__["useEffect"])(callback, []);
    Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* useOnce */ "C"])(() => () => {
      const observer = observerRef.current;
      Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* each */ "k"])(observer.deps, dep => Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* removeFluidObserver */ "x"])(dep, observer));
    });
    const usedProps = host.getComponentProps(props.getValue());
    return react__WEBPACK_IMPORTED_MODULE_1__["createElement"](Component, _extends({}, usedProps, {
      ref: ref
    }));
  });
};

class PropsObserver {
  constructor(update, deps) {
    this.update = update;
    this.deps = deps;
  }

  eventObserved(event) {
    if (event.type == 'change') {
      _react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* raf */ "w"].write(this.update);
    }
  }

}

function getAnimatedState(props, host) {
  const dependencies = new Set();
  TreeContext.dependencies = dependencies;
  if (props.style) props = _extends({}, props, {
    style: host.createAnimatedStyle(props.style)
  });
  props = new AnimatedObject(props);
  TreeContext.dependencies = null;
  return [props, dependencies];
}

function updateRef(ref, value) {
  if (ref) {
    if (_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* is */ "s"].fun(ref)) ref(value);else ref.current = value;
  }

  return value;
}

const cacheKey = Symbol.for('AnimatedComponent');
const createHost = (components, {
  applyAnimatedValues: _applyAnimatedValues = () => false,
  createAnimatedStyle: _createAnimatedStyle = style => new AnimatedObject(style),
  getComponentProps: _getComponentProps = props => props
} = {}) => {
  const hostConfig = {
    applyAnimatedValues: _applyAnimatedValues,
    createAnimatedStyle: _createAnimatedStyle,
    getComponentProps: _getComponentProps
  };

  const animated = Component => {
    const displayName = getDisplayName(Component) || 'Anonymous';

    if (_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* is */ "s"].str(Component)) {
      Component = animated[Component] || (animated[Component] = withAnimated(Component, hostConfig));
    } else {
      Component = Component[cacheKey] || (Component[cacheKey] = withAnimated(Component, hostConfig));
    }

    Component.displayName = `Animated(${displayName})`;
    return Component;
  };

  Object(_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* eachProp */ "l"])(components, (Component, key) => {
    if (_react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* is */ "s"].arr(components)) {
      key = getDisplayName(Component);
    }

    animated[key] = animated(Component);
  });
  return {
    animated
  };
};

const getDisplayName = arg => _react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* is */ "s"].str(arg) ? arg : arg && _react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* is */ "s"].str(arg.displayName) ? arg.displayName : _react_spring_shared__WEBPACK_IMPORTED_MODULE_0__[/* is */ "s"].fun(arg) && arg.name || null;




/***/ }),

/***/ 320:
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),

/***/ 321:
/***/ (function(module, exports) {

module.exports = Array.isArray || function (arr) {
  return Object.prototype.toString.call(arr) == '[object Array]';
};


/***/ }),

/***/ 322:
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),

/***/ 33:
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var implementation = __webpack_require__(59);

module.exports = Function.prototype.bind || implementation;


/***/ }),

/***/ 34:
/***/ (function(module, exports) {

(function() { module.exports = window["wp"]["htmlEntities"]; }());

/***/ }),

/***/ 35:
/***/ (function(module, exports) {

(function() { module.exports = window["wp"]["keycodes"]; }());

/***/ }),

/***/ 38:
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var GetIntrinsic = __webpack_require__(18);

var callBind = __webpack_require__(45);

var $indexOf = callBind(GetIntrinsic('String.prototype.indexOf'));

module.exports = function callBoundIntrinsic(name, allowMissing) {
	var intrinsic = GetIntrinsic(name, !!allowMissing);
	if (typeof intrinsic === 'function' && $indexOf(name, '.prototype.') > -1) {
		return callBind(intrinsic);
	}
	return intrinsic;
};


/***/ }),

/***/ 39:
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var replace = String.prototype.replace;
var percentTwenties = /%20/g;

var Format = {
    RFC1738: 'RFC1738',
    RFC3986: 'RFC3986'
};

module.exports = {
    'default': Format.RFC3986,
    formatters: {
        RFC1738: function (value) {
            return replace.call(value, percentTwenties, '+');
        },
        RFC3986: function (value) {
            return String(value);
        }
    },
    RFC1738: Format.RFC1738,
    RFC3986: Format.RFC3986
};


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

/***/ 41:
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var bind = __webpack_require__(33);

module.exports = bind.call(Function.call, Object.prototype.hasOwnProperty);


/***/ }),

/***/ 45:
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var bind = __webpack_require__(33);
var GetIntrinsic = __webpack_require__(18);

var $apply = GetIntrinsic('%Function.prototype.apply%');
var $call = GetIntrinsic('%Function.prototype.call%');
var $reflectApply = GetIntrinsic('%Reflect.apply%', true) || bind.call($call, $apply);

var $gOPD = GetIntrinsic('%Object.getOwnPropertyDescriptor%', true);
var $defineProperty = GetIntrinsic('%Object.defineProperty%', true);
var $max = GetIntrinsic('%Math.max%');

if ($defineProperty) {
	try {
		$defineProperty({}, 'a', { value: 1 });
	} catch (e) {
		// IE 8 has a broken defineProperty
		$defineProperty = null;
	}
}

module.exports = function callBind(originalFunction) {
	var func = $reflectApply(bind, $call, arguments);
	if ($gOPD && $defineProperty) {
		var desc = $gOPD(func, 'length');
		if (desc.configurable) {
			// original length, plus the receiver, minus any additional arguments (after the receiver)
			$defineProperty(
				func,
				'length',
				{ value: 1 + $max(0, originalFunction.length - (arguments.length - 1)) }
			);
		}
	}
	return func;
};

var applyBind = function applyBind() {
	return $reflectApply(bind, $apply, arguments);
};

if ($defineProperty) {
	$defineProperty(module.exports, 'apply', { value: applyBind });
} else {
	module.exports.apply = applyBind;
}


/***/ }),

/***/ 48:
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var formats = __webpack_require__(39);

var has = Object.prototype.hasOwnProperty;
var isArray = Array.isArray;

var hexTable = (function () {
    var array = [];
    for (var i = 0; i < 256; ++i) {
        array.push('%' + ((i < 16 ? '0' : '') + i.toString(16)).toUpperCase());
    }

    return array;
}());

var compactQueue = function compactQueue(queue) {
    while (queue.length > 1) {
        var item = queue.pop();
        var obj = item.obj[item.prop];

        if (isArray(obj)) {
            var compacted = [];

            for (var j = 0; j < obj.length; ++j) {
                if (typeof obj[j] !== 'undefined') {
                    compacted.push(obj[j]);
                }
            }

            item.obj[item.prop] = compacted;
        }
    }
};

var arrayToObject = function arrayToObject(source, options) {
    var obj = options && options.plainObjects ? Object.create(null) : {};
    for (var i = 0; i < source.length; ++i) {
        if (typeof source[i] !== 'undefined') {
            obj[i] = source[i];
        }
    }

    return obj;
};

var merge = function merge(target, source, options) {
    /* eslint no-param-reassign: 0 */
    if (!source) {
        return target;
    }

    if (typeof source !== 'object') {
        if (isArray(target)) {
            target.push(source);
        } else if (target && typeof target === 'object') {
            if ((options && (options.plainObjects || options.allowPrototypes)) || !has.call(Object.prototype, source)) {
                target[source] = true;
            }
        } else {
            return [target, source];
        }

        return target;
    }

    if (!target || typeof target !== 'object') {
        return [target].concat(source);
    }

    var mergeTarget = target;
    if (isArray(target) && !isArray(source)) {
        mergeTarget = arrayToObject(target, options);
    }

    if (isArray(target) && isArray(source)) {
        source.forEach(function (item, i) {
            if (has.call(target, i)) {
                var targetItem = target[i];
                if (targetItem && typeof targetItem === 'object' && item && typeof item === 'object') {
                    target[i] = merge(targetItem, item, options);
                } else {
                    target.push(item);
                }
            } else {
                target[i] = item;
            }
        });
        return target;
    }

    return Object.keys(source).reduce(function (acc, key) {
        var value = source[key];

        if (has.call(acc, key)) {
            acc[key] = merge(acc[key], value, options);
        } else {
            acc[key] = value;
        }
        return acc;
    }, mergeTarget);
};

var assign = function assignSingleSource(target, source) {
    return Object.keys(source).reduce(function (acc, key) {
        acc[key] = source[key];
        return acc;
    }, target);
};

var decode = function (str, decoder, charset) {
    var strWithoutPlus = str.replace(/\+/g, ' ');
    if (charset === 'iso-8859-1') {
        // unescape never throws, no try...catch needed:
        return strWithoutPlus.replace(/%[0-9a-f]{2}/gi, unescape);
    }
    // utf-8
    try {
        return decodeURIComponent(strWithoutPlus);
    } catch (e) {
        return strWithoutPlus;
    }
};

var encode = function encode(str, defaultEncoder, charset, kind, format) {
    // This code was originally written by Brian White (mscdex) for the io.js core querystring library.
    // It has been adapted here for stricter adherence to RFC 3986
    if (str.length === 0) {
        return str;
    }

    var string = str;
    if (typeof str === 'symbol') {
        string = Symbol.prototype.toString.call(str);
    } else if (typeof str !== 'string') {
        string = String(str);
    }

    if (charset === 'iso-8859-1') {
        return escape(string).replace(/%u[0-9a-f]{4}/gi, function ($0) {
            return '%26%23' + parseInt($0.slice(2), 16) + '%3B';
        });
    }

    var out = '';
    for (var i = 0; i < string.length; ++i) {
        var c = string.charCodeAt(i);

        if (
            c === 0x2D // -
            || c === 0x2E // .
            || c === 0x5F // _
            || c === 0x7E // ~
            || (c >= 0x30 && c <= 0x39) // 0-9
            || (c >= 0x41 && c <= 0x5A) // a-z
            || (c >= 0x61 && c <= 0x7A) // A-Z
            || (format === formats.RFC1738 && (c === 0x28 || c === 0x29)) // ( )
        ) {
            out += string.charAt(i);
            continue;
        }

        if (c < 0x80) {
            out = out + hexTable[c];
            continue;
        }

        if (c < 0x800) {
            out = out + (hexTable[0xC0 | (c >> 6)] + hexTable[0x80 | (c & 0x3F)]);
            continue;
        }

        if (c < 0xD800 || c >= 0xE000) {
            out = out + (hexTable[0xE0 | (c >> 12)] + hexTable[0x80 | ((c >> 6) & 0x3F)] + hexTable[0x80 | (c & 0x3F)]);
            continue;
        }

        i += 1;
        c = 0x10000 + (((c & 0x3FF) << 10) | (string.charCodeAt(i) & 0x3FF));
        /* eslint operator-linebreak: [2, "before"] */
        out += hexTable[0xF0 | (c >> 18)]
            + hexTable[0x80 | ((c >> 12) & 0x3F)]
            + hexTable[0x80 | ((c >> 6) & 0x3F)]
            + hexTable[0x80 | (c & 0x3F)];
    }

    return out;
};

var compact = function compact(value) {
    var queue = [{ obj: { o: value }, prop: 'o' }];
    var refs = [];

    for (var i = 0; i < queue.length; ++i) {
        var item = queue[i];
        var obj = item.obj[item.prop];

        var keys = Object.keys(obj);
        for (var j = 0; j < keys.length; ++j) {
            var key = keys[j];
            var val = obj[key];
            if (typeof val === 'object' && val !== null && refs.indexOf(val) === -1) {
                queue.push({ obj: obj, prop: key });
                refs.push(val);
            }
        }
    }

    compactQueue(queue);

    return value;
};

var isRegExp = function isRegExp(obj) {
    return Object.prototype.toString.call(obj) === '[object RegExp]';
};

var isBuffer = function isBuffer(obj) {
    if (!obj || typeof obj !== 'object') {
        return false;
    }

    return !!(obj.constructor && obj.constructor.isBuffer && obj.constructor.isBuffer(obj));
};

var combine = function combine(a, b) {
    return [].concat(a, b);
};

var maybeMap = function maybeMap(val, fn) {
    if (isArray(val)) {
        var mapped = [];
        for (var i = 0; i < val.length; i += 1) {
            mapped.push(fn(val[i]));
        }
        return mapped;
    }
    return fn(val);
};

module.exports = {
    arrayToObject: arrayToObject,
    assign: assign,
    combine: combine,
    compact: compact,
    decode: decode,
    encode: encode,
    isBuffer: isBuffer,
    isRegExp: isRegExp,
    maybeMap: maybeMap,
    merge: merge
};


/***/ }),

/***/ 5:
/***/ (function(module, exports) {

(function() { module.exports = window["lodash"]; }());

/***/ }),

/***/ 508:
/***/ (function(module, exports) {

(function() { module.exports = window["wc"]["csvExport"]; }());

/***/ }),

/***/ 51:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "a", function() { return invariant; });
var isProduction = "production" === 'production';
var prefix = 'Invariant failed';
function invariant(condition, message) {
    if (condition) {
        return;
    }
    if (isProduction) {
        throw new Error(prefix);
    }
    var provided = typeof message === 'function' ? message() : message;
    var value = provided ? prefix + ": " + provided : prefix;
    throw new Error(value);
}




/***/ }),

/***/ 52:
/***/ (function(module, exports, __webpack_require__) {

"use strict";


/* eslint complexity: [2, 18], max-statements: [2, 33] */
module.exports = function hasSymbols() {
	if (typeof Symbol !== 'function' || typeof Object.getOwnPropertySymbols !== 'function') { return false; }
	if (typeof Symbol.iterator === 'symbol') { return true; }

	var obj = {};
	var sym = Symbol('test');
	var symObj = Object(sym);
	if (typeof sym === 'string') { return false; }

	if (Object.prototype.toString.call(sym) !== '[object Symbol]') { return false; }
	if (Object.prototype.toString.call(symObj) !== '[object Symbol]') { return false; }

	// temp disabled per https://github.com/ljharb/object.assign/issues/17
	// if (sym instanceof Symbol) { return false; }
	// temp disabled per https://github.com/WebReflection/get-own-property-symbols/issues/4
	// if (!(symObj instanceof Symbol)) { return false; }

	// if (typeof Symbol.prototype.toString !== 'function') { return false; }
	// if (String(sym) !== Symbol.prototype.toString.call(sym)) { return false; }

	var symVal = 42;
	obj[sym] = symVal;
	for (sym in obj) { return false; } // eslint-disable-line no-restricted-syntax, no-unreachable-loop
	if (typeof Object.keys === 'function' && Object.keys(obj).length !== 0) { return false; }

	if (typeof Object.getOwnPropertyNames === 'function' && Object.getOwnPropertyNames(obj).length !== 0) { return false; }

	var syms = Object.getOwnPropertySymbols(obj);
	if (syms.length !== 1 || syms[0] !== sym) { return false; }

	if (!Object.prototype.propertyIsEnumerable.call(obj, sym)) { return false; }

	if (typeof Object.getOwnPropertyDescriptor === 'function') {
		var descriptor = Object.getOwnPropertyDescriptor(obj, sym);
		if (descriptor.value !== symVal || descriptor.enumerable !== true) { return false; }
	}

	return true;
};


/***/ }),

/***/ 531:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(9);
/* harmony import */ var _wordpress_primitives__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__);


/**
 * WordPress dependencies
 */

const close = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__["SVG"], {
  xmlns: "http://www.w3.org/2000/svg",
  viewBox: "0 0 24 24"
}, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__["Path"], {
  d: "M13 11.8l6.1-6.3-1-1-6.1 6.2-6.1-6.2-1 1 6.1 6.3-6.5 6.7 1 1 6.5-6.6 6.5 6.6 1-1z"
}));
/* harmony default export */ __webpack_exports__["a"] = (close);
//# sourceMappingURL=close.js.map

/***/ }),

/***/ 532:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(9);
/* harmony import */ var _wordpress_primitives__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__);


/**
 * WordPress dependencies
 */

const page = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__["SVG"], {
  xmlns: "http://www.w3.org/2000/svg",
  viewBox: "0 0 24 24"
}, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__["Path"], {
  d: "M7 5.5h10a.5.5 0 01.5.5v12a.5.5 0 01-.5.5H7a.5.5 0 01-.5-.5V6a.5.5 0 01.5-.5zM17 4H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V6a2 2 0 00-2-2zm-1 3.75H8v1.5h8v-1.5zM8 11h8v1.5H8V11zm6 3.25H8v1.5h6v-1.5z"
}));
/* harmony default export */ __webpack_exports__["a"] = (page);
//# sourceMappingURL=page.js.map

/***/ }),

/***/ 533:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(9);
/* harmony import */ var _wordpress_primitives__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__);


/**
 * WordPress dependencies
 */

const box = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__["SVG"], {
  xmlns: "http://www.w3.org/2000/svg",
  viewBox: "0 0 24 24"
}, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__["Path"], {
  fillRule: "evenodd",
  d: "M5 5.5h14a.5.5 0 01.5.5v1.5a.5.5 0 01-.5.5H5a.5.5 0 01-.5-.5V6a.5.5 0 01.5-.5zM4 9.232A2 2 0 013 7.5V6a2 2 0 012-2h14a2 2 0 012 2v1.5a2 2 0 01-1 1.732V18a2 2 0 01-2 2H6a2 2 0 01-2-2V9.232zm1.5.268V18a.5.5 0 00.5.5h12a.5.5 0 00.5-.5V9.5h-13z",
  clipRule: "evenodd"
}));
/* harmony default export */ __webpack_exports__["a"] = (box);
//# sourceMappingURL=box.js.map

/***/ }),

/***/ 534:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(9);
/* harmony import */ var _wordpress_primitives__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__);


/**
 * WordPress dependencies
 */

const help = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__["SVG"], {
  xmlns: "http://www.w3.org/2000/svg",
  viewBox: "0 0 24 24"
}, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__["Path"], {
  d: "M12 4.75a7.25 7.25 0 100 14.5 7.25 7.25 0 000-14.5zM3.25 12a8.75 8.75 0 1117.5 0 8.75 8.75 0 01-17.5 0zM12 8.75a1.5 1.5 0 01.167 2.99c-.465.052-.917.44-.917 1.01V14h1.5v-.845A3 3 0 109 10.25h1.5a1.5 1.5 0 011.5-1.5zM11.25 15v1.5h1.5V15h-1.5z"
}));
/* harmony default export */ __webpack_exports__["a"] = (help);
//# sourceMappingURL=help.js.map

/***/ }),

/***/ 535:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(9);
/* harmony import */ var _wordpress_primitives__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__);


/**
 * WordPress dependencies
 */

const external = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__["SVG"], {
  xmlns: "http://www.w3.org/2000/svg",
  viewBox: "0 0 24 24"
}, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__["Path"], {
  d: "M18.2 17c0 .7-.6 1.2-1.2 1.2H7c-.7 0-1.2-.6-1.2-1.2V7c0-.7.6-1.2 1.2-1.2h3.2V4.2H7C5.5 4.2 4.2 5.5 4.2 7v10c0 1.5 1.2 2.8 2.8 2.8h10c1.5 0 2.8-1.2 2.8-2.8v-3.6h-1.5V17zM14.9 3v1.5h3.7l-6.4 6.4 1.1 1.1 6.4-6.4v3.7h1.5V3h-6.3z"
}));
/* harmony default export */ __webpack_exports__["a"] = (external);
//# sourceMappingURL=external.js.map

/***/ }),

/***/ 57:
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var origSymbol = typeof Symbol !== 'undefined' && Symbol;
var hasSymbolSham = __webpack_require__(52);

module.exports = function hasNativeSymbols() {
	if (typeof origSymbol !== 'function') { return false; }
	if (typeof Symbol !== 'function') { return false; }
	if (typeof origSymbol('foo') !== 'symbol') { return false; }
	if (typeof Symbol('bar') !== 'symbol') { return false; }

	return hasSymbolSham();
};


/***/ }),

/***/ 59:
/***/ (function(module, exports, __webpack_require__) {

"use strict";


/* eslint no-invalid-this: 1 */

var ERROR_MESSAGE = 'Function.prototype.bind called on incompatible ';
var slice = Array.prototype.slice;
var toStr = Object.prototype.toString;
var funcType = '[object Function]';

module.exports = function bind(that) {
    var target = this;
    if (typeof target !== 'function' || toStr.call(target) !== funcType) {
        throw new TypeError(ERROR_MESSAGE + target);
    }
    var args = slice.call(arguments, 1);

    var bound;
    var binder = function () {
        if (this instanceof bound) {
            var result = target.apply(
                this,
                args.concat(slice.call(arguments))
            );
            if (Object(result) === result) {
                return result;
            }
            return this;
        } else {
            return target.apply(
                that,
                args.concat(slice.call(arguments))
            );
        }
    };

    var boundLength = Math.max(0, target.length - args.length);
    var boundArgs = [];
    for (var i = 0; i < boundLength; i++) {
        boundArgs.push('$' + i);
    }

    bound = Function('binder', 'return function (' + boundArgs.join(',') + '){ return binder.apply(this,arguments); }')(binder);

    if (target.prototype) {
        var Empty = function Empty() {};
        Empty.prototype = target.prototype;
        bound.prototype = new Empty();
        Empty.prototype = null;
    }

    return bound;
};


/***/ }),

/***/ 6:
/***/ (function(module, exports) {

(function() { module.exports = window["React"]; }());

/***/ }),

/***/ 60:
/***/ (function(module, exports, __webpack_require__) {

var hasMap = typeof Map === 'function' && Map.prototype;
var mapSizeDescriptor = Object.getOwnPropertyDescriptor && hasMap ? Object.getOwnPropertyDescriptor(Map.prototype, 'size') : null;
var mapSize = hasMap && mapSizeDescriptor && typeof mapSizeDescriptor.get === 'function' ? mapSizeDescriptor.get : null;
var mapForEach = hasMap && Map.prototype.forEach;
var hasSet = typeof Set === 'function' && Set.prototype;
var setSizeDescriptor = Object.getOwnPropertyDescriptor && hasSet ? Object.getOwnPropertyDescriptor(Set.prototype, 'size') : null;
var setSize = hasSet && setSizeDescriptor && typeof setSizeDescriptor.get === 'function' ? setSizeDescriptor.get : null;
var setForEach = hasSet && Set.prototype.forEach;
var hasWeakMap = typeof WeakMap === 'function' && WeakMap.prototype;
var weakMapHas = hasWeakMap ? WeakMap.prototype.has : null;
var hasWeakSet = typeof WeakSet === 'function' && WeakSet.prototype;
var weakSetHas = hasWeakSet ? WeakSet.prototype.has : null;
var hasWeakRef = typeof WeakRef === 'function' && WeakRef.prototype;
var weakRefDeref = hasWeakRef ? WeakRef.prototype.deref : null;
var booleanValueOf = Boolean.prototype.valueOf;
var objectToString = Object.prototype.toString;
var functionToString = Function.prototype.toString;
var match = String.prototype.match;
var bigIntValueOf = typeof BigInt === 'function' ? BigInt.prototype.valueOf : null;
var gOPS = Object.getOwnPropertySymbols;
var symToString = typeof Symbol === 'function' && typeof Symbol.iterator === 'symbol' ? Symbol.prototype.toString : null;
var hasShammedSymbols = typeof Symbol === 'function' && typeof Symbol.iterator === 'object';
var isEnumerable = Object.prototype.propertyIsEnumerable;

var gPO = (typeof Reflect === 'function' ? Reflect.getPrototypeOf : Object.getPrototypeOf) || (
    [].__proto__ === Array.prototype // eslint-disable-line no-proto
        ? function (O) {
            return O.__proto__; // eslint-disable-line no-proto
        }
        : null
);

var inspectCustom = __webpack_require__(61).custom;
var inspectSymbol = inspectCustom && isSymbol(inspectCustom) ? inspectCustom : null;
var toStringTag = typeof Symbol === 'function' && typeof Symbol.toStringTag !== 'undefined' ? Symbol.toStringTag : null;

module.exports = function inspect_(obj, options, depth, seen) {
    var opts = options || {};

    if (has(opts, 'quoteStyle') && (opts.quoteStyle !== 'single' && opts.quoteStyle !== 'double')) {
        throw new TypeError('option "quoteStyle" must be "single" or "double"');
    }
    if (
        has(opts, 'maxStringLength') && (typeof opts.maxStringLength === 'number'
            ? opts.maxStringLength < 0 && opts.maxStringLength !== Infinity
            : opts.maxStringLength !== null
        )
    ) {
        throw new TypeError('option "maxStringLength", if provided, must be a positive integer, Infinity, or `null`');
    }
    var customInspect = has(opts, 'customInspect') ? opts.customInspect : true;
    if (typeof customInspect !== 'boolean' && customInspect !== 'symbol') {
        throw new TypeError('option "customInspect", if provided, must be `true`, `false`, or `\'symbol\'`');
    }

    if (
        has(opts, 'indent')
        && opts.indent !== null
        && opts.indent !== '\t'
        && !(parseInt(opts.indent, 10) === opts.indent && opts.indent > 0)
    ) {
        throw new TypeError('options "indent" must be "\\t", an integer > 0, or `null`');
    }

    if (typeof obj === 'undefined') {
        return 'undefined';
    }
    if (obj === null) {
        return 'null';
    }
    if (typeof obj === 'boolean') {
        return obj ? 'true' : 'false';
    }

    if (typeof obj === 'string') {
        return inspectString(obj, opts);
    }
    if (typeof obj === 'number') {
        if (obj === 0) {
            return Infinity / obj > 0 ? '0' : '-0';
        }
        return String(obj);
    }
    if (typeof obj === 'bigint') {
        return String(obj) + 'n';
    }

    var maxDepth = typeof opts.depth === 'undefined' ? 5 : opts.depth;
    if (typeof depth === 'undefined') { depth = 0; }
    if (depth >= maxDepth && maxDepth > 0 && typeof obj === 'object') {
        return isArray(obj) ? '[Array]' : '[Object]';
    }

    var indent = getIndent(opts, depth);

    if (typeof seen === 'undefined') {
        seen = [];
    } else if (indexOf(seen, obj) >= 0) {
        return '[Circular]';
    }

    function inspect(value, from, noIndent) {
        if (from) {
            seen = seen.slice();
            seen.push(from);
        }
        if (noIndent) {
            var newOpts = {
                depth: opts.depth
            };
            if (has(opts, 'quoteStyle')) {
                newOpts.quoteStyle = opts.quoteStyle;
            }
            return inspect_(value, newOpts, depth + 1, seen);
        }
        return inspect_(value, opts, depth + 1, seen);
    }

    if (typeof obj === 'function') {
        var name = nameOf(obj);
        var keys = arrObjKeys(obj, inspect);
        return '[Function' + (name ? ': ' + name : ' (anonymous)') + ']' + (keys.length > 0 ? ' { ' + keys.join(', ') + ' }' : '');
    }
    if (isSymbol(obj)) {
        var symString = hasShammedSymbols ? String(obj).replace(/^(Symbol\(.*\))_[^)]*$/, '$1') : symToString.call(obj);
        return typeof obj === 'object' && !hasShammedSymbols ? markBoxed(symString) : symString;
    }
    if (isElement(obj)) {
        var s = '<' + String(obj.nodeName).toLowerCase();
        var attrs = obj.attributes || [];
        for (var i = 0; i < attrs.length; i++) {
            s += ' ' + attrs[i].name + '=' + wrapQuotes(quote(attrs[i].value), 'double', opts);
        }
        s += '>';
        if (obj.childNodes && obj.childNodes.length) { s += '...'; }
        s += '</' + String(obj.nodeName).toLowerCase() + '>';
        return s;
    }
    if (isArray(obj)) {
        if (obj.length === 0) { return '[]'; }
        var xs = arrObjKeys(obj, inspect);
        if (indent && !singleLineValues(xs)) {
            return '[' + indentedJoin(xs, indent) + ']';
        }
        return '[ ' + xs.join(', ') + ' ]';
    }
    if (isError(obj)) {
        var parts = arrObjKeys(obj, inspect);
        if (parts.length === 0) { return '[' + String(obj) + ']'; }
        return '{ [' + String(obj) + '] ' + parts.join(', ') + ' }';
    }
    if (typeof obj === 'object' && customInspect) {
        if (inspectSymbol && typeof obj[inspectSymbol] === 'function') {
            return obj[inspectSymbol]();
        } else if (customInspect !== 'symbol' && typeof obj.inspect === 'function') {
            return obj.inspect();
        }
    }
    if (isMap(obj)) {
        var mapParts = [];
        mapForEach.call(obj, function (value, key) {
            mapParts.push(inspect(key, obj, true) + ' => ' + inspect(value, obj));
        });
        return collectionOf('Map', mapSize.call(obj), mapParts, indent);
    }
    if (isSet(obj)) {
        var setParts = [];
        setForEach.call(obj, function (value) {
            setParts.push(inspect(value, obj));
        });
        return collectionOf('Set', setSize.call(obj), setParts, indent);
    }
    if (isWeakMap(obj)) {
        return weakCollectionOf('WeakMap');
    }
    if (isWeakSet(obj)) {
        return weakCollectionOf('WeakSet');
    }
    if (isWeakRef(obj)) {
        return weakCollectionOf('WeakRef');
    }
    if (isNumber(obj)) {
        return markBoxed(inspect(Number(obj)));
    }
    if (isBigInt(obj)) {
        return markBoxed(inspect(bigIntValueOf.call(obj)));
    }
    if (isBoolean(obj)) {
        return markBoxed(booleanValueOf.call(obj));
    }
    if (isString(obj)) {
        return markBoxed(inspect(String(obj)));
    }
    if (!isDate(obj) && !isRegExp(obj)) {
        var ys = arrObjKeys(obj, inspect);
        var isPlainObject = gPO ? gPO(obj) === Object.prototype : obj instanceof Object || obj.constructor === Object;
        var protoTag = obj instanceof Object ? '' : 'null prototype';
        var stringTag = !isPlainObject && toStringTag && Object(obj) === obj && toStringTag in obj ? toStr(obj).slice(8, -1) : protoTag ? 'Object' : '';
        var constructorTag = isPlainObject || typeof obj.constructor !== 'function' ? '' : obj.constructor.name ? obj.constructor.name + ' ' : '';
        var tag = constructorTag + (stringTag || protoTag ? '[' + [].concat(stringTag || [], protoTag || []).join(': ') + '] ' : '');
        if (ys.length === 0) { return tag + '{}'; }
        if (indent) {
            return tag + '{' + indentedJoin(ys, indent) + '}';
        }
        return tag + '{ ' + ys.join(', ') + ' }';
    }
    return String(obj);
};

function wrapQuotes(s, defaultStyle, opts) {
    var quoteChar = (opts.quoteStyle || defaultStyle) === 'double' ? '"' : "'";
    return quoteChar + s + quoteChar;
}

function quote(s) {
    return String(s).replace(/"/g, '&quot;');
}

function isArray(obj) { return toStr(obj) === '[object Array]' && (!toStringTag || !(typeof obj === 'object' && toStringTag in obj)); }
function isDate(obj) { return toStr(obj) === '[object Date]' && (!toStringTag || !(typeof obj === 'object' && toStringTag in obj)); }
function isRegExp(obj) { return toStr(obj) === '[object RegExp]' && (!toStringTag || !(typeof obj === 'object' && toStringTag in obj)); }
function isError(obj) { return toStr(obj) === '[object Error]' && (!toStringTag || !(typeof obj === 'object' && toStringTag in obj)); }
function isString(obj) { return toStr(obj) === '[object String]' && (!toStringTag || !(typeof obj === 'object' && toStringTag in obj)); }
function isNumber(obj) { return toStr(obj) === '[object Number]' && (!toStringTag || !(typeof obj === 'object' && toStringTag in obj)); }
function isBoolean(obj) { return toStr(obj) === '[object Boolean]' && (!toStringTag || !(typeof obj === 'object' && toStringTag in obj)); }

// Symbol and BigInt do have Symbol.toStringTag by spec, so that can't be used to eliminate false positives
function isSymbol(obj) {
    if (hasShammedSymbols) {
        return obj && typeof obj === 'object' && obj instanceof Symbol;
    }
    if (typeof obj === 'symbol') {
        return true;
    }
    if (!obj || typeof obj !== 'object' || !symToString) {
        return false;
    }
    try {
        symToString.call(obj);
        return true;
    } catch (e) {}
    return false;
}

function isBigInt(obj) {
    if (!obj || typeof obj !== 'object' || !bigIntValueOf) {
        return false;
    }
    try {
        bigIntValueOf.call(obj);
        return true;
    } catch (e) {}
    return false;
}

var hasOwn = Object.prototype.hasOwnProperty || function (key) { return key in this; };
function has(obj, key) {
    return hasOwn.call(obj, key);
}

function toStr(obj) {
    return objectToString.call(obj);
}

function nameOf(f) {
    if (f.name) { return f.name; }
    var m = match.call(functionToString.call(f), /^function\s*([\w$]+)/);
    if (m) { return m[1]; }
    return null;
}

function indexOf(xs, x) {
    if (xs.indexOf) { return xs.indexOf(x); }
    for (var i = 0, l = xs.length; i < l; i++) {
        if (xs[i] === x) { return i; }
    }
    return -1;
}

function isMap(x) {
    if (!mapSize || !x || typeof x !== 'object') {
        return false;
    }
    try {
        mapSize.call(x);
        try {
            setSize.call(x);
        } catch (s) {
            return true;
        }
        return x instanceof Map; // core-js workaround, pre-v2.5.0
    } catch (e) {}
    return false;
}

function isWeakMap(x) {
    if (!weakMapHas || !x || typeof x !== 'object') {
        return false;
    }
    try {
        weakMapHas.call(x, weakMapHas);
        try {
            weakSetHas.call(x, weakSetHas);
        } catch (s) {
            return true;
        }
        return x instanceof WeakMap; // core-js workaround, pre-v2.5.0
    } catch (e) {}
    return false;
}

function isWeakRef(x) {
    if (!weakRefDeref || !x || typeof x !== 'object') {
        return false;
    }
    try {
        weakRefDeref.call(x);
        return true;
    } catch (e) {}
    return false;
}

function isSet(x) {
    if (!setSize || !x || typeof x !== 'object') {
        return false;
    }
    try {
        setSize.call(x);
        try {
            mapSize.call(x);
        } catch (m) {
            return true;
        }
        return x instanceof Set; // core-js workaround, pre-v2.5.0
    } catch (e) {}
    return false;
}

function isWeakSet(x) {
    if (!weakSetHas || !x || typeof x !== 'object') {
        return false;
    }
    try {
        weakSetHas.call(x, weakSetHas);
        try {
            weakMapHas.call(x, weakMapHas);
        } catch (s) {
            return true;
        }
        return x instanceof WeakSet; // core-js workaround, pre-v2.5.0
    } catch (e) {}
    return false;
}

function isElement(x) {
    if (!x || typeof x !== 'object') { return false; }
    if (typeof HTMLElement !== 'undefined' && x instanceof HTMLElement) {
        return true;
    }
    return typeof x.nodeName === 'string' && typeof x.getAttribute === 'function';
}

function inspectString(str, opts) {
    if (str.length > opts.maxStringLength) {
        var remaining = str.length - opts.maxStringLength;
        var trailer = '... ' + remaining + ' more character' + (remaining > 1 ? 's' : '');
        return inspectString(str.slice(0, opts.maxStringLength), opts) + trailer;
    }
    // eslint-disable-next-line no-control-regex
    var s = str.replace(/(['\\])/g, '\\$1').replace(/[\x00-\x1f]/g, lowbyte);
    return wrapQuotes(s, 'single', opts);
}

function lowbyte(c) {
    var n = c.charCodeAt(0);
    var x = {
        8: 'b',
        9: 't',
        10: 'n',
        12: 'f',
        13: 'r'
    }[n];
    if (x) { return '\\' + x; }
    return '\\x' + (n < 0x10 ? '0' : '') + n.toString(16).toUpperCase();
}

function markBoxed(str) {
    return 'Object(' + str + ')';
}

function weakCollectionOf(type) {
    return type + ' { ? }';
}

function collectionOf(type, size, entries, indent) {
    var joinedEntries = indent ? indentedJoin(entries, indent) : entries.join(', ');
    return type + ' (' + size + ') {' + joinedEntries + '}';
}

function singleLineValues(xs) {
    for (var i = 0; i < xs.length; i++) {
        if (indexOf(xs[i], '\n') >= 0) {
            return false;
        }
    }
    return true;
}

function getIndent(opts, depth) {
    var baseIndent;
    if (opts.indent === '\t') {
        baseIndent = '\t';
    } else if (typeof opts.indent === 'number' && opts.indent > 0) {
        baseIndent = Array(opts.indent + 1).join(' ');
    } else {
        return null;
    }
    return {
        base: baseIndent,
        prev: Array(depth + 1).join(baseIndent)
    };
}

function indentedJoin(xs, indent) {
    if (xs.length === 0) { return ''; }
    var lineJoiner = '\n' + indent.prev + indent.base;
    return lineJoiner + xs.join(',' + lineJoiner) + '\n' + indent.prev;
}

function arrObjKeys(obj, inspect) {
    var isArr = isArray(obj);
    var xs = [];
    if (isArr) {
        xs.length = obj.length;
        for (var i = 0; i < obj.length; i++) {
            xs[i] = has(obj, i) ? inspect(obj[i], obj) : '';
        }
    }
    var syms = typeof gOPS === 'function' ? gOPS(obj) : [];
    var symMap;
    if (hasShammedSymbols) {
        symMap = {};
        for (var k = 0; k < syms.length; k++) {
            symMap['$' + syms[k]] = syms[k];
        }
    }

    for (var key in obj) { // eslint-disable-line no-restricted-syntax
        if (!has(obj, key)) { continue; } // eslint-disable-line no-restricted-syntax, no-continue
        if (isArr && String(Number(key)) === key && key < obj.length) { continue; } // eslint-disable-line no-restricted-syntax, no-continue
        if (hasShammedSymbols && symMap['$' + key] instanceof Symbol) {
            // this is to prevent shammed Symbols, which are stored as strings, from being included in the string key section
            continue; // eslint-disable-line no-restricted-syntax, no-continue
        } else if ((/[^\w$]/).test(key)) {
            xs.push(inspect(key, obj) + ': ' + inspect(obj[key], obj));
        } else {
            xs.push(key + ': ' + inspect(obj[key], obj));
        }
    }
    if (typeof gOPS === 'function') {
        for (var j = 0; j < syms.length; j++) {
            if (isEnumerable.call(obj, syms[j])) {
                xs.push('[' + inspect(syms[j]) + ']: ' + inspect(obj[syms[j]], obj));
            }
        }
    }
    return xs;
}


/***/ }),

/***/ 61:
/***/ (function(module, exports) {

/* (ignored) */

/***/ }),

/***/ 64:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "a", function() { return WooHeaderItem; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "b", function() { return WooHeaderNavigationItem; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "c", function() { return WooHeaderPageTitle; });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(4);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__);


/**
 * External dependencies
 */


/**
 * Ordered header item.
 *
 * @param {Node} children - Node children.
 * @param {number} order - Node order.
 * @param {Array} props - Fill props.
 * @return {Node} Node.
 */

const createOrderedChildren = (children, order, props) => {
  return typeof children === 'function' ? Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["cloneElement"])(children(props), {
    order
  }) : Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["cloneElement"])(children, { ...props,
    order
  });
};
/**
 * Create a Fill for extensions to add items to the WooCommerce Admin header.
 *
 * @slotFill WooHeaderItem
 * @scope woocommerce-admin
 * @example
 * const MyHeaderItem = () => (
 * <WooHeaderItem>My header item</WooHeaderItem>
 * );
 *
 * registerPlugin( 'my-extension', {
 * render: MyHeaderItem,
 * scope: 'woocommerce-admin',
 * } );
 * @param {Object} param0
 * @param {Array} param0.children - Node children.
 * @param {Array} param0.order - Node order.
 */


const WooHeaderItem = _ref => {
  let {
    children,
    order = 1
  } = _ref;
  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__["Fill"], {
    name: 'woocommerce_header_item'
  }, fillProps => {
    return createOrderedChildren(children, order, fillProps);
  });
};

WooHeaderItem.Slot = _ref2 => {
  let {
    fillProps
  } = _ref2;
  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__["Slot"], {
    name: 'woocommerce_header_item',
    fillProps: fillProps
  }, fills => {
    return fills.sort((a, b) => {
      return a[0].props.order - b[0].props.order;
    });
  });
};
/**
 * Create a Fill for extensions to add items to the WooCommerce Admin
 * navigation area left of the page title.
 *
 * @slotFill WooHeaderNavigationItem
 * @scope woocommerce-admin
 * @example
 * const MyNavigationItem = () => (
 * <WooHeaderNavigationItem>My nav item</WooHeaderNavigationItem>
 * );
 *
 * registerPlugin( 'my-extension', {
 * render: MyNavigationItem,
 * scope: 'woocommerce-admin',
 * } );
 * @param {Object} param0
 * @param {Array} param0.children - Node children.
 * @param {Array} param0.order - Node order.
 */


const WooHeaderNavigationItem = _ref3 => {
  let {
    children,
    order = 1
  } = _ref3;
  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__["Fill"], {
    name: 'woocommerce_header_navigation_item'
  }, fillProps => {
    return createOrderedChildren(children, order, fillProps);
  });
};

WooHeaderNavigationItem.Slot = _ref4 => {
  let {
    fillProps
  } = _ref4;
  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__["Slot"], {
    name: 'woocommerce_header_navigation_item',
    fillProps: fillProps
  }, fills => {
    return fills.sort((a, b) => {
      return a[0].props.order - b[0].props.order;
    });
  });
};
/**
 * Create a Fill for extensions to add custom page titles.
 *
 * @slotFill WooHeaderPageTitle
 * @scope woocommerce-admin
 * @example
 * const MyPageTitle = () => (
 * 	<WooHeaderPageTitle>My page title</WooHeaderPageTitle>
 * );
 *
 * registerPlugin( 'my-page-title', {
 * 	render: MyPageTitle,
 * 	scope: 'woocommerce-admin',
 * } );
 * @param {Object} param0
 * @param {Array} param0.children - Node children.
 */


const WooHeaderPageTitle = _ref5 => {
  let {
    children
  } = _ref5;
  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__["Fill"], {
    name: 'woocommerce_header_page_title'
  }, children);
};

WooHeaderPageTitle.Slot = _ref6 => {
  let {
    fillProps
  } = _ref6;
  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__["Slot"], {
    name: 'woocommerce_header_page_title',
    fillProps: fillProps
  }, fills => {
    const last = fills.pop();
    return [last];
  });
};

/***/ }),

/***/ 65:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";

// EXPORTS
__webpack_require__.d(__webpack_exports__, "a", function() { return /* binding */ createBrowserHistory; });
__webpack_require__.d(__webpack_exports__, "c", function() { return /* binding */ createMemoryHistory; });
__webpack_require__.d(__webpack_exports__, "b", function() { return /* binding */ createLocation; });
__webpack_require__.d(__webpack_exports__, "e", function() { return /* binding */ locationsAreEqual; });
__webpack_require__.d(__webpack_exports__, "d", function() { return /* binding */ createPath; });

// UNUSED EXPORTS: createHashHistory, parsePath

// EXTERNAL MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/@babel+runtime@7.17.2/node_modules/@babel/runtime/helpers/esm/extends.js
var esm_extends = __webpack_require__(24);

// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/resolve-pathname@3.0.0/node_modules/resolve-pathname/esm/resolve-pathname.js
function isAbsolute(pathname) {
  return pathname.charAt(0) === '/';
}

// About 1.5x faster than the two-arg version of Array#splice()
function spliceOne(list, index) {
  for (var i = index, k = i + 1, n = list.length; k < n; i += 1, k += 1) {
    list[i] = list[k];
  }

  list.pop();
}

// This implementation is based heavily on node's url.parse
function resolvePathname(to, from) {
  if (from === undefined) from = '';

  var toParts = (to && to.split('/')) || [];
  var fromParts = (from && from.split('/')) || [];

  var isToAbs = to && isAbsolute(to);
  var isFromAbs = from && isAbsolute(from);
  var mustEndAbs = isToAbs || isFromAbs;

  if (to && isAbsolute(to)) {
    // to is absolute
    fromParts = toParts;
  } else if (toParts.length) {
    // to is relative, drop the filename
    fromParts.pop();
    fromParts = fromParts.concat(toParts);
  }

  if (!fromParts.length) return '/';

  var hasTrailingSlash;
  if (fromParts.length) {
    var last = fromParts[fromParts.length - 1];
    hasTrailingSlash = last === '.' || last === '..' || last === '';
  } else {
    hasTrailingSlash = false;
  }

  var up = 0;
  for (var i = fromParts.length; i >= 0; i--) {
    var part = fromParts[i];

    if (part === '.') {
      spliceOne(fromParts, i);
    } else if (part === '..') {
      spliceOne(fromParts, i);
      up++;
    } else if (up) {
      spliceOne(fromParts, i);
      up--;
    }
  }

  if (!mustEndAbs) for (; up--; up) fromParts.unshift('..');

  if (
    mustEndAbs &&
    fromParts[0] !== '' &&
    (!fromParts[0] || !isAbsolute(fromParts[0]))
  )
    fromParts.unshift('');

  var result = fromParts.join('/');

  if (hasTrailingSlash && result.substr(-1) !== '/') result += '/';

  return result;
}

/* harmony default export */ var resolve_pathname = (resolvePathname);

// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/value-equal@1.0.1/node_modules/value-equal/esm/value-equal.js
function value_equal_valueOf(obj) {
  return obj.valueOf ? obj.valueOf() : Object.prototype.valueOf.call(obj);
}

function valueEqual(a, b) {
  // Test for strict equality first.
  if (a === b) return true;

  // Otherwise, if either of them == null they are not equal.
  if (a == null || b == null) return false;

  if (Array.isArray(a)) {
    return (
      Array.isArray(b) &&
      a.length === b.length &&
      a.every(function(item, index) {
        return valueEqual(item, b[index]);
      })
    );
  }

  if (typeof a === 'object' || typeof b === 'object') {
    var aValue = value_equal_valueOf(a);
    var bValue = value_equal_valueOf(b);

    if (aValue !== a || bValue !== b) return valueEqual(aValue, bValue);

    return Object.keys(Object.assign({}, a, b)).every(function(key) {
      return valueEqual(a[key], b[key]);
    });
  }

  return false;
}

/* harmony default export */ var value_equal = (valueEqual);

// EXTERNAL MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/tiny-invariant@1.2.0/node_modules/tiny-invariant/dist/tiny-invariant.esm.js
var tiny_invariant_esm = __webpack_require__(51);

// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/history@4.10.1/node_modules/history/esm/history.js






function addLeadingSlash(path) {
  return path.charAt(0) === '/' ? path : '/' + path;
}
function stripLeadingSlash(path) {
  return path.charAt(0) === '/' ? path.substr(1) : path;
}
function hasBasename(path, prefix) {
  return path.toLowerCase().indexOf(prefix.toLowerCase()) === 0 && '/?#'.indexOf(path.charAt(prefix.length)) !== -1;
}
function stripBasename(path, prefix) {
  return hasBasename(path, prefix) ? path.substr(prefix.length) : path;
}
function stripTrailingSlash(path) {
  return path.charAt(path.length - 1) === '/' ? path.slice(0, -1) : path;
}
function parsePath(path) {
  var pathname = path || '/';
  var search = '';
  var hash = '';
  var hashIndex = pathname.indexOf('#');

  if (hashIndex !== -1) {
    hash = pathname.substr(hashIndex);
    pathname = pathname.substr(0, hashIndex);
  }

  var searchIndex = pathname.indexOf('?');

  if (searchIndex !== -1) {
    search = pathname.substr(searchIndex);
    pathname = pathname.substr(0, searchIndex);
  }

  return {
    pathname: pathname,
    search: search === '?' ? '' : search,
    hash: hash === '#' ? '' : hash
  };
}
function createPath(location) {
  var pathname = location.pathname,
      search = location.search,
      hash = location.hash;
  var path = pathname || '/';
  if (search && search !== '?') path += search.charAt(0) === '?' ? search : "?" + search;
  if (hash && hash !== '#') path += hash.charAt(0) === '#' ? hash : "#" + hash;
  return path;
}

function createLocation(path, state, key, currentLocation) {
  var location;

  if (typeof path === 'string') {
    // Two-arg form: push(path, state)
    location = parsePath(path);
    location.state = state;
  } else {
    // One-arg form: push(location)
    location = Object(esm_extends["a" /* default */])({}, path);
    if (location.pathname === undefined) location.pathname = '';

    if (location.search) {
      if (location.search.charAt(0) !== '?') location.search = '?' + location.search;
    } else {
      location.search = '';
    }

    if (location.hash) {
      if (location.hash.charAt(0) !== '#') location.hash = '#' + location.hash;
    } else {
      location.hash = '';
    }

    if (state !== undefined && location.state === undefined) location.state = state;
  }

  try {
    location.pathname = decodeURI(location.pathname);
  } catch (e) {
    if (e instanceof URIError) {
      throw new URIError('Pathname "' + location.pathname + '" could not be decoded. ' + 'This is likely caused by an invalid percent-encoding.');
    } else {
      throw e;
    }
  }

  if (key) location.key = key;

  if (currentLocation) {
    // Resolve incomplete/relative pathname relative to current location.
    if (!location.pathname) {
      location.pathname = currentLocation.pathname;
    } else if (location.pathname.charAt(0) !== '/') {
      location.pathname = resolve_pathname(location.pathname, currentLocation.pathname);
    }
  } else {
    // When there is no prior location and pathname is empty, set it to /
    if (!location.pathname) {
      location.pathname = '/';
    }
  }

  return location;
}
function locationsAreEqual(a, b) {
  return a.pathname === b.pathname && a.search === b.search && a.hash === b.hash && a.key === b.key && value_equal(a.state, b.state);
}

function createTransitionManager() {
  var prompt = null;

  function setPrompt(nextPrompt) {
     false ? undefined : void 0;
    prompt = nextPrompt;
    return function () {
      if (prompt === nextPrompt) prompt = null;
    };
  }

  function confirmTransitionTo(location, action, getUserConfirmation, callback) {
    // TODO: If another transition starts while we're still confirming
    // the previous one, we may end up in a weird state. Figure out the
    // best way to handle this.
    if (prompt != null) {
      var result = typeof prompt === 'function' ? prompt(location, action) : prompt;

      if (typeof result === 'string') {
        if (typeof getUserConfirmation === 'function') {
          getUserConfirmation(result, callback);
        } else {
           false ? undefined : void 0;
          callback(true);
        }
      } else {
        // Return false from a transition hook to cancel the transition.
        callback(result !== false);
      }
    } else {
      callback(true);
    }
  }

  var listeners = [];

  function appendListener(fn) {
    var isActive = true;

    function listener() {
      if (isActive) fn.apply(void 0, arguments);
    }

    listeners.push(listener);
    return function () {
      isActive = false;
      listeners = listeners.filter(function (item) {
        return item !== listener;
      });
    };
  }

  function notifyListeners() {
    for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
      args[_key] = arguments[_key];
    }

    listeners.forEach(function (listener) {
      return listener.apply(void 0, args);
    });
  }

  return {
    setPrompt: setPrompt,
    confirmTransitionTo: confirmTransitionTo,
    appendListener: appendListener,
    notifyListeners: notifyListeners
  };
}

var canUseDOM = !!(typeof window !== 'undefined' && window.document && window.document.createElement);
function getConfirmation(message, callback) {
  callback(window.confirm(message)); // eslint-disable-line no-alert
}
/**
 * Returns true if the HTML5 history API is supported. Taken from Modernizr.
 *
 * https://github.com/Modernizr/Modernizr/blob/master/LICENSE
 * https://github.com/Modernizr/Modernizr/blob/master/feature-detects/history.js
 * changed to avoid false negatives for Windows Phones: https://github.com/reactjs/react-router/issues/586
 */

function supportsHistory() {
  var ua = window.navigator.userAgent;
  if ((ua.indexOf('Android 2.') !== -1 || ua.indexOf('Android 4.0') !== -1) && ua.indexOf('Mobile Safari') !== -1 && ua.indexOf('Chrome') === -1 && ua.indexOf('Windows Phone') === -1) return false;
  return window.history && 'pushState' in window.history;
}
/**
 * Returns true if browser fires popstate on hash change.
 * IE10 and IE11 do not.
 */

function supportsPopStateOnHashChange() {
  return window.navigator.userAgent.indexOf('Trident') === -1;
}
/**
 * Returns false if using go(n) with hash history causes a full page reload.
 */

function supportsGoWithoutReloadUsingHash() {
  return window.navigator.userAgent.indexOf('Firefox') === -1;
}
/**
 * Returns true if a given popstate event is an extraneous WebKit event.
 * Accounts for the fact that Chrome on iOS fires real popstate events
 * containing undefined state when pressing the back button.
 */

function isExtraneousPopstateEvent(event) {
  return event.state === undefined && navigator.userAgent.indexOf('CriOS') === -1;
}

var PopStateEvent = 'popstate';
var HashChangeEvent = 'hashchange';

function getHistoryState() {
  try {
    return window.history.state || {};
  } catch (e) {
    // IE 11 sometimes throws when accessing window.history.state
    // See https://github.com/ReactTraining/history/pull/289
    return {};
  }
}
/**
 * Creates a history object that uses the HTML5 history API including
 * pushState, replaceState, and the popstate event.
 */


function createBrowserHistory(props) {
  if (props === void 0) {
    props = {};
  }

  !canUseDOM ?  false ? undefined : Object(tiny_invariant_esm["a" /* default */])(false) : void 0;
  var globalHistory = window.history;
  var canUseHistory = supportsHistory();
  var needsHashChangeListener = !supportsPopStateOnHashChange();
  var _props = props,
      _props$forceRefresh = _props.forceRefresh,
      forceRefresh = _props$forceRefresh === void 0 ? false : _props$forceRefresh,
      _props$getUserConfirm = _props.getUserConfirmation,
      getUserConfirmation = _props$getUserConfirm === void 0 ? getConfirmation : _props$getUserConfirm,
      _props$keyLength = _props.keyLength,
      keyLength = _props$keyLength === void 0 ? 6 : _props$keyLength;
  var basename = props.basename ? stripTrailingSlash(addLeadingSlash(props.basename)) : '';

  function getDOMLocation(historyState) {
    var _ref = historyState || {},
        key = _ref.key,
        state = _ref.state;

    var _window$location = window.location,
        pathname = _window$location.pathname,
        search = _window$location.search,
        hash = _window$location.hash;
    var path = pathname + search + hash;
     false ? undefined : void 0;
    if (basename) path = stripBasename(path, basename);
    return createLocation(path, state, key);
  }

  function createKey() {
    return Math.random().toString(36).substr(2, keyLength);
  }

  var transitionManager = createTransitionManager();

  function setState(nextState) {
    Object(esm_extends["a" /* default */])(history, nextState);

    history.length = globalHistory.length;
    transitionManager.notifyListeners(history.location, history.action);
  }

  function handlePopState(event) {
    // Ignore extraneous popstate events in WebKit.
    if (isExtraneousPopstateEvent(event)) return;
    handlePop(getDOMLocation(event.state));
  }

  function handleHashChange() {
    handlePop(getDOMLocation(getHistoryState()));
  }

  var forceNextPop = false;

  function handlePop(location) {
    if (forceNextPop) {
      forceNextPop = false;
      setState();
    } else {
      var action = 'POP';
      transitionManager.confirmTransitionTo(location, action, getUserConfirmation, function (ok) {
        if (ok) {
          setState({
            action: action,
            location: location
          });
        } else {
          revertPop(location);
        }
      });
    }
  }

  function revertPop(fromLocation) {
    var toLocation = history.location; // TODO: We could probably make this more reliable by
    // keeping a list of keys we've seen in sessionStorage.
    // Instead, we just default to 0 for keys we don't know.

    var toIndex = allKeys.indexOf(toLocation.key);
    if (toIndex === -1) toIndex = 0;
    var fromIndex = allKeys.indexOf(fromLocation.key);
    if (fromIndex === -1) fromIndex = 0;
    var delta = toIndex - fromIndex;

    if (delta) {
      forceNextPop = true;
      go(delta);
    }
  }

  var initialLocation = getDOMLocation(getHistoryState());
  var allKeys = [initialLocation.key]; // Public interface

  function createHref(location) {
    return basename + createPath(location);
  }

  function push(path, state) {
     false ? undefined : void 0;
    var action = 'PUSH';
    var location = createLocation(path, state, createKey(), history.location);
    transitionManager.confirmTransitionTo(location, action, getUserConfirmation, function (ok) {
      if (!ok) return;
      var href = createHref(location);
      var key = location.key,
          state = location.state;

      if (canUseHistory) {
        globalHistory.pushState({
          key: key,
          state: state
        }, null, href);

        if (forceRefresh) {
          window.location.href = href;
        } else {
          var prevIndex = allKeys.indexOf(history.location.key);
          var nextKeys = allKeys.slice(0, prevIndex + 1);
          nextKeys.push(location.key);
          allKeys = nextKeys;
          setState({
            action: action,
            location: location
          });
        }
      } else {
         false ? undefined : void 0;
        window.location.href = href;
      }
    });
  }

  function replace(path, state) {
     false ? undefined : void 0;
    var action = 'REPLACE';
    var location = createLocation(path, state, createKey(), history.location);
    transitionManager.confirmTransitionTo(location, action, getUserConfirmation, function (ok) {
      if (!ok) return;
      var href = createHref(location);
      var key = location.key,
          state = location.state;

      if (canUseHistory) {
        globalHistory.replaceState({
          key: key,
          state: state
        }, null, href);

        if (forceRefresh) {
          window.location.replace(href);
        } else {
          var prevIndex = allKeys.indexOf(history.location.key);
          if (prevIndex !== -1) allKeys[prevIndex] = location.key;
          setState({
            action: action,
            location: location
          });
        }
      } else {
         false ? undefined : void 0;
        window.location.replace(href);
      }
    });
  }

  function go(n) {
    globalHistory.go(n);
  }

  function goBack() {
    go(-1);
  }

  function goForward() {
    go(1);
  }

  var listenerCount = 0;

  function checkDOMListeners(delta) {
    listenerCount += delta;

    if (listenerCount === 1 && delta === 1) {
      window.addEventListener(PopStateEvent, handlePopState);
      if (needsHashChangeListener) window.addEventListener(HashChangeEvent, handleHashChange);
    } else if (listenerCount === 0) {
      window.removeEventListener(PopStateEvent, handlePopState);
      if (needsHashChangeListener) window.removeEventListener(HashChangeEvent, handleHashChange);
    }
  }

  var isBlocked = false;

  function block(prompt) {
    if (prompt === void 0) {
      prompt = false;
    }

    var unblock = transitionManager.setPrompt(prompt);

    if (!isBlocked) {
      checkDOMListeners(1);
      isBlocked = true;
    }

    return function () {
      if (isBlocked) {
        isBlocked = false;
        checkDOMListeners(-1);
      }

      return unblock();
    };
  }

  function listen(listener) {
    var unlisten = transitionManager.appendListener(listener);
    checkDOMListeners(1);
    return function () {
      checkDOMListeners(-1);
      unlisten();
    };
  }

  var history = {
    length: globalHistory.length,
    action: 'POP',
    location: initialLocation,
    createHref: createHref,
    push: push,
    replace: replace,
    go: go,
    goBack: goBack,
    goForward: goForward,
    block: block,
    listen: listen
  };
  return history;
}

var HashChangeEvent$1 = 'hashchange';
var HashPathCoders = {
  hashbang: {
    encodePath: function encodePath(path) {
      return path.charAt(0) === '!' ? path : '!/' + stripLeadingSlash(path);
    },
    decodePath: function decodePath(path) {
      return path.charAt(0) === '!' ? path.substr(1) : path;
    }
  },
  noslash: {
    encodePath: stripLeadingSlash,
    decodePath: addLeadingSlash
  },
  slash: {
    encodePath: addLeadingSlash,
    decodePath: addLeadingSlash
  }
};

function stripHash(url) {
  var hashIndex = url.indexOf('#');
  return hashIndex === -1 ? url : url.slice(0, hashIndex);
}

function getHashPath() {
  // We can't use window.location.hash here because it's not
  // consistent across browsers - Firefox will pre-decode it!
  var href = window.location.href;
  var hashIndex = href.indexOf('#');
  return hashIndex === -1 ? '' : href.substring(hashIndex + 1);
}

function pushHashPath(path) {
  window.location.hash = path;
}

function replaceHashPath(path) {
  window.location.replace(stripHash(window.location.href) + '#' + path);
}

function createHashHistory(props) {
  if (props === void 0) {
    props = {};
  }

  !canUseDOM ?  false ? undefined : Object(tiny_invariant_esm["a" /* default */])(false) : void 0;
  var globalHistory = window.history;
  var canGoWithoutReload = supportsGoWithoutReloadUsingHash();
  var _props = props,
      _props$getUserConfirm = _props.getUserConfirmation,
      getUserConfirmation = _props$getUserConfirm === void 0 ? getConfirmation : _props$getUserConfirm,
      _props$hashType = _props.hashType,
      hashType = _props$hashType === void 0 ? 'slash' : _props$hashType;
  var basename = props.basename ? stripTrailingSlash(addLeadingSlash(props.basename)) : '';
  var _HashPathCoders$hashT = HashPathCoders[hashType],
      encodePath = _HashPathCoders$hashT.encodePath,
      decodePath = _HashPathCoders$hashT.decodePath;

  function getDOMLocation() {
    var path = decodePath(getHashPath());
     false ? undefined : void 0;
    if (basename) path = stripBasename(path, basename);
    return createLocation(path);
  }

  var transitionManager = createTransitionManager();

  function setState(nextState) {
    Object(esm_extends["a" /* default */])(history, nextState);

    history.length = globalHistory.length;
    transitionManager.notifyListeners(history.location, history.action);
  }

  var forceNextPop = false;
  var ignorePath = null;

  function locationsAreEqual$$1(a, b) {
    return a.pathname === b.pathname && a.search === b.search && a.hash === b.hash;
  }

  function handleHashChange() {
    var path = getHashPath();
    var encodedPath = encodePath(path);

    if (path !== encodedPath) {
      // Ensure we always have a properly-encoded hash.
      replaceHashPath(encodedPath);
    } else {
      var location = getDOMLocation();
      var prevLocation = history.location;
      if (!forceNextPop && locationsAreEqual$$1(prevLocation, location)) return; // A hashchange doesn't always == location change.

      if (ignorePath === createPath(location)) return; // Ignore this change; we already setState in push/replace.

      ignorePath = null;
      handlePop(location);
    }
  }

  function handlePop(location) {
    if (forceNextPop) {
      forceNextPop = false;
      setState();
    } else {
      var action = 'POP';
      transitionManager.confirmTransitionTo(location, action, getUserConfirmation, function (ok) {
        if (ok) {
          setState({
            action: action,
            location: location
          });
        } else {
          revertPop(location);
        }
      });
    }
  }

  function revertPop(fromLocation) {
    var toLocation = history.location; // TODO: We could probably make this more reliable by
    // keeping a list of paths we've seen in sessionStorage.
    // Instead, we just default to 0 for paths we don't know.

    var toIndex = allPaths.lastIndexOf(createPath(toLocation));
    if (toIndex === -1) toIndex = 0;
    var fromIndex = allPaths.lastIndexOf(createPath(fromLocation));
    if (fromIndex === -1) fromIndex = 0;
    var delta = toIndex - fromIndex;

    if (delta) {
      forceNextPop = true;
      go(delta);
    }
  } // Ensure the hash is encoded properly before doing anything else.


  var path = getHashPath();
  var encodedPath = encodePath(path);
  if (path !== encodedPath) replaceHashPath(encodedPath);
  var initialLocation = getDOMLocation();
  var allPaths = [createPath(initialLocation)]; // Public interface

  function createHref(location) {
    var baseTag = document.querySelector('base');
    var href = '';

    if (baseTag && baseTag.getAttribute('href')) {
      href = stripHash(window.location.href);
    }

    return href + '#' + encodePath(basename + createPath(location));
  }

  function push(path, state) {
     false ? undefined : void 0;
    var action = 'PUSH';
    var location = createLocation(path, undefined, undefined, history.location);
    transitionManager.confirmTransitionTo(location, action, getUserConfirmation, function (ok) {
      if (!ok) return;
      var path = createPath(location);
      var encodedPath = encodePath(basename + path);
      var hashChanged = getHashPath() !== encodedPath;

      if (hashChanged) {
        // We cannot tell if a hashchange was caused by a PUSH, so we'd
        // rather setState here and ignore the hashchange. The caveat here
        // is that other hash histories in the page will consider it a POP.
        ignorePath = path;
        pushHashPath(encodedPath);
        var prevIndex = allPaths.lastIndexOf(createPath(history.location));
        var nextPaths = allPaths.slice(0, prevIndex + 1);
        nextPaths.push(path);
        allPaths = nextPaths;
        setState({
          action: action,
          location: location
        });
      } else {
         false ? undefined : void 0;
        setState();
      }
    });
  }

  function replace(path, state) {
     false ? undefined : void 0;
    var action = 'REPLACE';
    var location = createLocation(path, undefined, undefined, history.location);
    transitionManager.confirmTransitionTo(location, action, getUserConfirmation, function (ok) {
      if (!ok) return;
      var path = createPath(location);
      var encodedPath = encodePath(basename + path);
      var hashChanged = getHashPath() !== encodedPath;

      if (hashChanged) {
        // We cannot tell if a hashchange was caused by a REPLACE, so we'd
        // rather setState here and ignore the hashchange. The caveat here
        // is that other hash histories in the page will consider it a POP.
        ignorePath = path;
        replaceHashPath(encodedPath);
      }

      var prevIndex = allPaths.indexOf(createPath(history.location));
      if (prevIndex !== -1) allPaths[prevIndex] = path;
      setState({
        action: action,
        location: location
      });
    });
  }

  function go(n) {
     false ? undefined : void 0;
    globalHistory.go(n);
  }

  function goBack() {
    go(-1);
  }

  function goForward() {
    go(1);
  }

  var listenerCount = 0;

  function checkDOMListeners(delta) {
    listenerCount += delta;

    if (listenerCount === 1 && delta === 1) {
      window.addEventListener(HashChangeEvent$1, handleHashChange);
    } else if (listenerCount === 0) {
      window.removeEventListener(HashChangeEvent$1, handleHashChange);
    }
  }

  var isBlocked = false;

  function block(prompt) {
    if (prompt === void 0) {
      prompt = false;
    }

    var unblock = transitionManager.setPrompt(prompt);

    if (!isBlocked) {
      checkDOMListeners(1);
      isBlocked = true;
    }

    return function () {
      if (isBlocked) {
        isBlocked = false;
        checkDOMListeners(-1);
      }

      return unblock();
    };
  }

  function listen(listener) {
    var unlisten = transitionManager.appendListener(listener);
    checkDOMListeners(1);
    return function () {
      checkDOMListeners(-1);
      unlisten();
    };
  }

  var history = {
    length: globalHistory.length,
    action: 'POP',
    location: initialLocation,
    createHref: createHref,
    push: push,
    replace: replace,
    go: go,
    goBack: goBack,
    goForward: goForward,
    block: block,
    listen: listen
  };
  return history;
}

function clamp(n, lowerBound, upperBound) {
  return Math.min(Math.max(n, lowerBound), upperBound);
}
/**
 * Creates a history object that stores locations in memory.
 */


function createMemoryHistory(props) {
  if (props === void 0) {
    props = {};
  }

  var _props = props,
      getUserConfirmation = _props.getUserConfirmation,
      _props$initialEntries = _props.initialEntries,
      initialEntries = _props$initialEntries === void 0 ? ['/'] : _props$initialEntries,
      _props$initialIndex = _props.initialIndex,
      initialIndex = _props$initialIndex === void 0 ? 0 : _props$initialIndex,
      _props$keyLength = _props.keyLength,
      keyLength = _props$keyLength === void 0 ? 6 : _props$keyLength;
  var transitionManager = createTransitionManager();

  function setState(nextState) {
    Object(esm_extends["a" /* default */])(history, nextState);

    history.length = history.entries.length;
    transitionManager.notifyListeners(history.location, history.action);
  }

  function createKey() {
    return Math.random().toString(36).substr(2, keyLength);
  }

  var index = clamp(initialIndex, 0, initialEntries.length - 1);
  var entries = initialEntries.map(function (entry) {
    return typeof entry === 'string' ? createLocation(entry, undefined, createKey()) : createLocation(entry, undefined, entry.key || createKey());
  }); // Public interface

  var createHref = createPath;

  function push(path, state) {
     false ? undefined : void 0;
    var action = 'PUSH';
    var location = createLocation(path, state, createKey(), history.location);
    transitionManager.confirmTransitionTo(location, action, getUserConfirmation, function (ok) {
      if (!ok) return;
      var prevIndex = history.index;
      var nextIndex = prevIndex + 1;
      var nextEntries = history.entries.slice(0);

      if (nextEntries.length > nextIndex) {
        nextEntries.splice(nextIndex, nextEntries.length - nextIndex, location);
      } else {
        nextEntries.push(location);
      }

      setState({
        action: action,
        location: location,
        index: nextIndex,
        entries: nextEntries
      });
    });
  }

  function replace(path, state) {
     false ? undefined : void 0;
    var action = 'REPLACE';
    var location = createLocation(path, state, createKey(), history.location);
    transitionManager.confirmTransitionTo(location, action, getUserConfirmation, function (ok) {
      if (!ok) return;
      history.entries[history.index] = location;
      setState({
        action: action,
        location: location
      });
    });
  }

  function go(n) {
    var nextIndex = clamp(history.index + n, 0, history.entries.length - 1);
    var action = 'POP';
    var location = history.entries[nextIndex];
    transitionManager.confirmTransitionTo(location, action, getUserConfirmation, function (ok) {
      if (ok) {
        setState({
          action: action,
          location: location,
          index: nextIndex
        });
      } else {
        // Mimic the behavior of DOM histories by
        // causing a render after a cancelled POP.
        setState();
      }
    });
  }

  function goBack() {
    go(-1);
  }

  function goForward() {
    go(1);
  }

  function canGo(n) {
    var nextIndex = history.index + n;
    return nextIndex >= 0 && nextIndex < history.entries.length;
  }

  function block(prompt) {
    if (prompt === void 0) {
      prompt = false;
    }

    return transitionManager.setPrompt(prompt);
  }

  function listen(listener) {
    return transitionManager.appendListener(listener);
  }

  var history = {
    length: entries.length,
    action: 'POP',
    location: entries[index],
    index: index,
    entries: entries,
    createHref: createHref,
    push: push,
    replace: replace,
    go: go,
    goBack: goBack,
    goForward: goForward,
    canGo: canGo,
    block: block,
    listen: listen
  };
  return history;
}




/***/ }),

/***/ 66:
/***/ (function(module, exports) {

(function() { module.exports = window["wp"]["plugins"]; }());

/***/ }),

/***/ 68:
/***/ (function(module, exports) {

(function() { module.exports = window["wp"]["date"]; }());

/***/ }),

/***/ 69:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "c", function() { return STORE_KEY; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "a", function() { return API_NAMESPACE; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "b", function() { return QUEUE_OPTION_NAME; });
const STORE_KEY = 'wc/customer-effort-score';
const API_NAMESPACE = '/wc-admin';
const QUEUE_OPTION_NAME = 'woocommerce_ces_tracks_queue';

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

/***/ 70:
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var getSideChannel = __webpack_require__(71);
var utils = __webpack_require__(48);
var formats = __webpack_require__(39);
var has = Object.prototype.hasOwnProperty;

var arrayPrefixGenerators = {
    brackets: function brackets(prefix) {
        return prefix + '[]';
    },
    comma: 'comma',
    indices: function indices(prefix, key) {
        return prefix + '[' + key + ']';
    },
    repeat: function repeat(prefix) {
        return prefix;
    }
};

var isArray = Array.isArray;
var split = String.prototype.split;
var push = Array.prototype.push;
var pushToArray = function (arr, valueOrArray) {
    push.apply(arr, isArray(valueOrArray) ? valueOrArray : [valueOrArray]);
};

var toISO = Date.prototype.toISOString;

var defaultFormat = formats['default'];
var defaults = {
    addQueryPrefix: false,
    allowDots: false,
    charset: 'utf-8',
    charsetSentinel: false,
    delimiter: '&',
    encode: true,
    encoder: utils.encode,
    encodeValuesOnly: false,
    format: defaultFormat,
    formatter: formats.formatters[defaultFormat],
    // deprecated
    indices: false,
    serializeDate: function serializeDate(date) {
        return toISO.call(date);
    },
    skipNulls: false,
    strictNullHandling: false
};

var isNonNullishPrimitive = function isNonNullishPrimitive(v) {
    return typeof v === 'string'
        || typeof v === 'number'
        || typeof v === 'boolean'
        || typeof v === 'symbol'
        || typeof v === 'bigint';
};

var sentinel = {};

var stringify = function stringify(
    object,
    prefix,
    generateArrayPrefix,
    strictNullHandling,
    skipNulls,
    encoder,
    filter,
    sort,
    allowDots,
    serializeDate,
    format,
    formatter,
    encodeValuesOnly,
    charset,
    sideChannel
) {
    var obj = object;

    var tmpSc = sideChannel;
    var step = 0;
    var findFlag = false;
    while ((tmpSc = tmpSc.get(sentinel)) !== void undefined && !findFlag) {
        // Where object last appeared in the ref tree
        var pos = tmpSc.get(object);
        step += 1;
        if (typeof pos !== 'undefined') {
            if (pos === step) {
                throw new RangeError('Cyclic object value');
            } else {
                findFlag = true; // Break while
            }
        }
        if (typeof tmpSc.get(sentinel) === 'undefined') {
            step = 0;
        }
    }

    if (typeof filter === 'function') {
        obj = filter(prefix, obj);
    } else if (obj instanceof Date) {
        obj = serializeDate(obj);
    } else if (generateArrayPrefix === 'comma' && isArray(obj)) {
        obj = utils.maybeMap(obj, function (value) {
            if (value instanceof Date) {
                return serializeDate(value);
            }
            return value;
        });
    }

    if (obj === null) {
        if (strictNullHandling) {
            return encoder && !encodeValuesOnly ? encoder(prefix, defaults.encoder, charset, 'key', format) : prefix;
        }

        obj = '';
    }

    if (isNonNullishPrimitive(obj) || utils.isBuffer(obj)) {
        if (encoder) {
            var keyValue = encodeValuesOnly ? prefix : encoder(prefix, defaults.encoder, charset, 'key', format);
            if (generateArrayPrefix === 'comma' && encodeValuesOnly) {
                var valuesArray = split.call(String(obj), ',');
                var valuesJoined = '';
                for (var i = 0; i < valuesArray.length; ++i) {
                    valuesJoined += (i === 0 ? '' : ',') + formatter(encoder(valuesArray[i], defaults.encoder, charset, 'value', format));
                }
                return [formatter(keyValue) + '=' + valuesJoined];
            }
            return [formatter(keyValue) + '=' + formatter(encoder(obj, defaults.encoder, charset, 'value', format))];
        }
        return [formatter(prefix) + '=' + formatter(String(obj))];
    }

    var values = [];

    if (typeof obj === 'undefined') {
        return values;
    }

    var objKeys;
    if (generateArrayPrefix === 'comma' && isArray(obj)) {
        // we need to join elements in
        objKeys = [{ value: obj.length > 0 ? obj.join(',') || null : void undefined }];
    } else if (isArray(filter)) {
        objKeys = filter;
    } else {
        var keys = Object.keys(obj);
        objKeys = sort ? keys.sort(sort) : keys;
    }

    for (var j = 0; j < objKeys.length; ++j) {
        var key = objKeys[j];
        var value = typeof key === 'object' && typeof key.value !== 'undefined' ? key.value : obj[key];

        if (skipNulls && value === null) {
            continue;
        }

        var keyPrefix = isArray(obj)
            ? typeof generateArrayPrefix === 'function' ? generateArrayPrefix(prefix, key) : prefix
            : prefix + (allowDots ? '.' + key : '[' + key + ']');

        sideChannel.set(object, step);
        var valueSideChannel = getSideChannel();
        valueSideChannel.set(sentinel, sideChannel);
        pushToArray(values, stringify(
            value,
            keyPrefix,
            generateArrayPrefix,
            strictNullHandling,
            skipNulls,
            encoder,
            filter,
            sort,
            allowDots,
            serializeDate,
            format,
            formatter,
            encodeValuesOnly,
            charset,
            valueSideChannel
        ));
    }

    return values;
};

var normalizeStringifyOptions = function normalizeStringifyOptions(opts) {
    if (!opts) {
        return defaults;
    }

    if (opts.encoder !== null && typeof opts.encoder !== 'undefined' && typeof opts.encoder !== 'function') {
        throw new TypeError('Encoder has to be a function.');
    }

    var charset = opts.charset || defaults.charset;
    if (typeof opts.charset !== 'undefined' && opts.charset !== 'utf-8' && opts.charset !== 'iso-8859-1') {
        throw new TypeError('The charset option must be either utf-8, iso-8859-1, or undefined');
    }

    var format = formats['default'];
    if (typeof opts.format !== 'undefined') {
        if (!has.call(formats.formatters, opts.format)) {
            throw new TypeError('Unknown format option provided.');
        }
        format = opts.format;
    }
    var formatter = formats.formatters[format];

    var filter = defaults.filter;
    if (typeof opts.filter === 'function' || isArray(opts.filter)) {
        filter = opts.filter;
    }

    return {
        addQueryPrefix: typeof opts.addQueryPrefix === 'boolean' ? opts.addQueryPrefix : defaults.addQueryPrefix,
        allowDots: typeof opts.allowDots === 'undefined' ? defaults.allowDots : !!opts.allowDots,
        charset: charset,
        charsetSentinel: typeof opts.charsetSentinel === 'boolean' ? opts.charsetSentinel : defaults.charsetSentinel,
        delimiter: typeof opts.delimiter === 'undefined' ? defaults.delimiter : opts.delimiter,
        encode: typeof opts.encode === 'boolean' ? opts.encode : defaults.encode,
        encoder: typeof opts.encoder === 'function' ? opts.encoder : defaults.encoder,
        encodeValuesOnly: typeof opts.encodeValuesOnly === 'boolean' ? opts.encodeValuesOnly : defaults.encodeValuesOnly,
        filter: filter,
        format: format,
        formatter: formatter,
        serializeDate: typeof opts.serializeDate === 'function' ? opts.serializeDate : defaults.serializeDate,
        skipNulls: typeof opts.skipNulls === 'boolean' ? opts.skipNulls : defaults.skipNulls,
        sort: typeof opts.sort === 'function' ? opts.sort : null,
        strictNullHandling: typeof opts.strictNullHandling === 'boolean' ? opts.strictNullHandling : defaults.strictNullHandling
    };
};

module.exports = function (object, opts) {
    var obj = object;
    var options = normalizeStringifyOptions(opts);

    var objKeys;
    var filter;

    if (typeof options.filter === 'function') {
        filter = options.filter;
        obj = filter('', obj);
    } else if (isArray(options.filter)) {
        filter = options.filter;
        objKeys = filter;
    }

    var keys = [];

    if (typeof obj !== 'object' || obj === null) {
        return '';
    }

    var arrayFormat;
    if (opts && opts.arrayFormat in arrayPrefixGenerators) {
        arrayFormat = opts.arrayFormat;
    } else if (opts && 'indices' in opts) {
        arrayFormat = opts.indices ? 'indices' : 'repeat';
    } else {
        arrayFormat = 'indices';
    }

    var generateArrayPrefix = arrayPrefixGenerators[arrayFormat];

    if (!objKeys) {
        objKeys = Object.keys(obj);
    }

    if (options.sort) {
        objKeys.sort(options.sort);
    }

    var sideChannel = getSideChannel();
    for (var i = 0; i < objKeys.length; ++i) {
        var key = objKeys[i];

        if (options.skipNulls && obj[key] === null) {
            continue;
        }
        pushToArray(keys, stringify(
            obj[key],
            key,
            generateArrayPrefix,
            options.strictNullHandling,
            options.skipNulls,
            options.encode ? options.encoder : null,
            options.filter,
            options.sort,
            options.allowDots,
            options.serializeDate,
            options.format,
            options.formatter,
            options.encodeValuesOnly,
            options.charset,
            sideChannel
        ));
    }

    var joined = keys.join(options.delimiter);
    var prefix = options.addQueryPrefix === true ? '?' : '';

    if (options.charsetSentinel) {
        if (options.charset === 'iso-8859-1') {
            // encodeURIComponent('&#10003;'), the "numeric entity" representation of a checkmark
            prefix += 'utf8=%26%2310003%3B&';
        } else {
            // encodeURIComponent('✓')
            prefix += 'utf8=%E2%9C%93&';
        }
    }

    return joined.length > 0 ? prefix + joined : '';
};


/***/ }),

/***/ 71:
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var GetIntrinsic = __webpack_require__(18);
var callBound = __webpack_require__(38);
var inspect = __webpack_require__(60);

var $TypeError = GetIntrinsic('%TypeError%');
var $WeakMap = GetIntrinsic('%WeakMap%', true);
var $Map = GetIntrinsic('%Map%', true);

var $weakMapGet = callBound('WeakMap.prototype.get', true);
var $weakMapSet = callBound('WeakMap.prototype.set', true);
var $weakMapHas = callBound('WeakMap.prototype.has', true);
var $mapGet = callBound('Map.prototype.get', true);
var $mapSet = callBound('Map.prototype.set', true);
var $mapHas = callBound('Map.prototype.has', true);

/*
 * This function traverses the list returning the node corresponding to the
 * given key.
 *
 * That node is also moved to the head of the list, so that if it's accessed
 * again we don't need to traverse the whole list. By doing so, all the recently
 * used nodes can be accessed relatively quickly.
 */
var listGetNode = function (list, key) { // eslint-disable-line consistent-return
	for (var prev = list, curr; (curr = prev.next) !== null; prev = curr) {
		if (curr.key === key) {
			prev.next = curr.next;
			curr.next = list.next;
			list.next = curr; // eslint-disable-line no-param-reassign
			return curr;
		}
	}
};

var listGet = function (objects, key) {
	var node = listGetNode(objects, key);
	return node && node.value;
};
var listSet = function (objects, key, value) {
	var node = listGetNode(objects, key);
	if (node) {
		node.value = value;
	} else {
		// Prepend the new node to the beginning of the list
		objects.next = { // eslint-disable-line no-param-reassign
			key: key,
			next: objects.next,
			value: value
		};
	}
};
var listHas = function (objects, key) {
	return !!listGetNode(objects, key);
};

module.exports = function getSideChannel() {
	var $wm;
	var $m;
	var $o;
	var channel = {
		assert: function (key) {
			if (!channel.has(key)) {
				throw new $TypeError('Side channel does not contain ' + inspect(key));
			}
		},
		get: function (key) { // eslint-disable-line consistent-return
			if ($WeakMap && key && (typeof key === 'object' || typeof key === 'function')) {
				if ($wm) {
					return $weakMapGet($wm, key);
				}
			} else if ($Map) {
				if ($m) {
					return $mapGet($m, key);
				}
			} else {
				if ($o) { // eslint-disable-line no-lonely-if
					return listGet($o, key);
				}
			}
		},
		has: function (key) {
			if ($WeakMap && key && (typeof key === 'object' || typeof key === 'function')) {
				if ($wm) {
					return $weakMapHas($wm, key);
				}
			} else if ($Map) {
				if ($m) {
					return $mapHas($m, key);
				}
			} else {
				if ($o) { // eslint-disable-line no-lonely-if
					return listHas($o, key);
				}
			}
			return false;
		},
		set: function (key, value) {
			if ($WeakMap && key && (typeof key === 'object' || typeof key === 'function')) {
				if (!$wm) {
					$wm = new $WeakMap();
				}
				$weakMapSet($wm, key, value);
			} else if ($Map) {
				if (!$m) {
					$m = new $Map();
				}
				$mapSet($m, key, value);
			} else {
				if (!$o) {
					/*
					 * Initialize the linked list as an empty node, so that we don't have
					 * to special-case handling of the first node: we can always refer to
					 * it as (previous node).next, instead of something like (list).head
					 */
					$o = { key: {}, next: null };
				}
				listSet($o, key, value);
			}
		}
	};
	return channel;
};


/***/ }),

/***/ 72:
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var utils = __webpack_require__(48);

var has = Object.prototype.hasOwnProperty;
var isArray = Array.isArray;

var defaults = {
    allowDots: false,
    allowPrototypes: false,
    allowSparse: false,
    arrayLimit: 20,
    charset: 'utf-8',
    charsetSentinel: false,
    comma: false,
    decoder: utils.decode,
    delimiter: '&',
    depth: 5,
    ignoreQueryPrefix: false,
    interpretNumericEntities: false,
    parameterLimit: 1000,
    parseArrays: true,
    plainObjects: false,
    strictNullHandling: false
};

var interpretNumericEntities = function (str) {
    return str.replace(/&#(\d+);/g, function ($0, numberStr) {
        return String.fromCharCode(parseInt(numberStr, 10));
    });
};

var parseArrayValue = function (val, options) {
    if (val && typeof val === 'string' && options.comma && val.indexOf(',') > -1) {
        return val.split(',');
    }

    return val;
};

// This is what browsers will submit when the ✓ character occurs in an
// application/x-www-form-urlencoded body and the encoding of the page containing
// the form is iso-8859-1, or when the submitted form has an accept-charset
// attribute of iso-8859-1. Presumably also with other charsets that do not contain
// the ✓ character, such as us-ascii.
var isoSentinel = 'utf8=%26%2310003%3B'; // encodeURIComponent('&#10003;')

// These are the percent-encoded utf-8 octets representing a checkmark, indicating that the request actually is utf-8 encoded.
var charsetSentinel = 'utf8=%E2%9C%93'; // encodeURIComponent('✓')

var parseValues = function parseQueryStringValues(str, options) {
    var obj = {};
    var cleanStr = options.ignoreQueryPrefix ? str.replace(/^\?/, '') : str;
    var limit = options.parameterLimit === Infinity ? undefined : options.parameterLimit;
    var parts = cleanStr.split(options.delimiter, limit);
    var skipIndex = -1; // Keep track of where the utf8 sentinel was found
    var i;

    var charset = options.charset;
    if (options.charsetSentinel) {
        for (i = 0; i < parts.length; ++i) {
            if (parts[i].indexOf('utf8=') === 0) {
                if (parts[i] === charsetSentinel) {
                    charset = 'utf-8';
                } else if (parts[i] === isoSentinel) {
                    charset = 'iso-8859-1';
                }
                skipIndex = i;
                i = parts.length; // The eslint settings do not allow break;
            }
        }
    }

    for (i = 0; i < parts.length; ++i) {
        if (i === skipIndex) {
            continue;
        }
        var part = parts[i];

        var bracketEqualsPos = part.indexOf(']=');
        var pos = bracketEqualsPos === -1 ? part.indexOf('=') : bracketEqualsPos + 1;

        var key, val;
        if (pos === -1) {
            key = options.decoder(part, defaults.decoder, charset, 'key');
            val = options.strictNullHandling ? null : '';
        } else {
            key = options.decoder(part.slice(0, pos), defaults.decoder, charset, 'key');
            val = utils.maybeMap(
                parseArrayValue(part.slice(pos + 1), options),
                function (encodedVal) {
                    return options.decoder(encodedVal, defaults.decoder, charset, 'value');
                }
            );
        }

        if (val && options.interpretNumericEntities && charset === 'iso-8859-1') {
            val = interpretNumericEntities(val);
        }

        if (part.indexOf('[]=') > -1) {
            val = isArray(val) ? [val] : val;
        }

        if (has.call(obj, key)) {
            obj[key] = utils.combine(obj[key], val);
        } else {
            obj[key] = val;
        }
    }

    return obj;
};

var parseObject = function (chain, val, options, valuesParsed) {
    var leaf = valuesParsed ? val : parseArrayValue(val, options);

    for (var i = chain.length - 1; i >= 0; --i) {
        var obj;
        var root = chain[i];

        if (root === '[]' && options.parseArrays) {
            obj = [].concat(leaf);
        } else {
            obj = options.plainObjects ? Object.create(null) : {};
            var cleanRoot = root.charAt(0) === '[' && root.charAt(root.length - 1) === ']' ? root.slice(1, -1) : root;
            var index = parseInt(cleanRoot, 10);
            if (!options.parseArrays && cleanRoot === '') {
                obj = { 0: leaf };
            } else if (
                !isNaN(index)
                && root !== cleanRoot
                && String(index) === cleanRoot
                && index >= 0
                && (options.parseArrays && index <= options.arrayLimit)
            ) {
                obj = [];
                obj[index] = leaf;
            } else if (cleanRoot !== '__proto__') {
                obj[cleanRoot] = leaf;
            }
        }

        leaf = obj;
    }

    return leaf;
};

var parseKeys = function parseQueryStringKeys(givenKey, val, options, valuesParsed) {
    if (!givenKey) {
        return;
    }

    // Transform dot notation to bracket notation
    var key = options.allowDots ? givenKey.replace(/\.([^.[]+)/g, '[$1]') : givenKey;

    // The regex chunks

    var brackets = /(\[[^[\]]*])/;
    var child = /(\[[^[\]]*])/g;

    // Get the parent

    var segment = options.depth > 0 && brackets.exec(key);
    var parent = segment ? key.slice(0, segment.index) : key;

    // Stash the parent if it exists

    var keys = [];
    if (parent) {
        // If we aren't using plain objects, optionally prefix keys that would overwrite object prototype properties
        if (!options.plainObjects && has.call(Object.prototype, parent)) {
            if (!options.allowPrototypes) {
                return;
            }
        }

        keys.push(parent);
    }

    // Loop through children appending to the array until we hit depth

    var i = 0;
    while (options.depth > 0 && (segment = child.exec(key)) !== null && i < options.depth) {
        i += 1;
        if (!options.plainObjects && has.call(Object.prototype, segment[1].slice(1, -1))) {
            if (!options.allowPrototypes) {
                return;
            }
        }
        keys.push(segment[1]);
    }

    // If there's a remainder, just add whatever is left

    if (segment) {
        keys.push('[' + key.slice(segment.index) + ']');
    }

    return parseObject(keys, val, options, valuesParsed);
};

var normalizeParseOptions = function normalizeParseOptions(opts) {
    if (!opts) {
        return defaults;
    }

    if (opts.decoder !== null && opts.decoder !== undefined && typeof opts.decoder !== 'function') {
        throw new TypeError('Decoder has to be a function.');
    }

    if (typeof opts.charset !== 'undefined' && opts.charset !== 'utf-8' && opts.charset !== 'iso-8859-1') {
        throw new TypeError('The charset option must be either utf-8, iso-8859-1, or undefined');
    }
    var charset = typeof opts.charset === 'undefined' ? defaults.charset : opts.charset;

    return {
        allowDots: typeof opts.allowDots === 'undefined' ? defaults.allowDots : !!opts.allowDots,
        allowPrototypes: typeof opts.allowPrototypes === 'boolean' ? opts.allowPrototypes : defaults.allowPrototypes,
        allowSparse: typeof opts.allowSparse === 'boolean' ? opts.allowSparse : defaults.allowSparse,
        arrayLimit: typeof opts.arrayLimit === 'number' ? opts.arrayLimit : defaults.arrayLimit,
        charset: charset,
        charsetSentinel: typeof opts.charsetSentinel === 'boolean' ? opts.charsetSentinel : defaults.charsetSentinel,
        comma: typeof opts.comma === 'boolean' ? opts.comma : defaults.comma,
        decoder: typeof opts.decoder === 'function' ? opts.decoder : defaults.decoder,
        delimiter: typeof opts.delimiter === 'string' || utils.isRegExp(opts.delimiter) ? opts.delimiter : defaults.delimiter,
        // eslint-disable-next-line no-implicit-coercion, no-extra-parens
        depth: (typeof opts.depth === 'number' || opts.depth === false) ? +opts.depth : defaults.depth,
        ignoreQueryPrefix: opts.ignoreQueryPrefix === true,
        interpretNumericEntities: typeof opts.interpretNumericEntities === 'boolean' ? opts.interpretNumericEntities : defaults.interpretNumericEntities,
        parameterLimit: typeof opts.parameterLimit === 'number' ? opts.parameterLimit : defaults.parameterLimit,
        parseArrays: opts.parseArrays !== false,
        plainObjects: typeof opts.plainObjects === 'boolean' ? opts.plainObjects : defaults.plainObjects,
        strictNullHandling: typeof opts.strictNullHandling === 'boolean' ? opts.strictNullHandling : defaults.strictNullHandling
    };
};

module.exports = function (str, opts) {
    var options = normalizeParseOptions(opts);

    if (str === '' || str === null || typeof str === 'undefined') {
        return options.plainObjects ? Object.create(null) : {};
    }

    var tempObj = typeof str === 'string' ? parseValues(str, options) : str;
    var obj = options.plainObjects ? Object.create(null) : {};

    // Iterate over the keys and setup the new object

    var keys = Object.keys(tempObj);
    for (var i = 0; i < keys.length; ++i) {
        var key = keys[i];
        var newObj = parseKeys(key, tempObj[key], options, typeof str === 'string');
        obj = utils.merge(obj, newObj, options);
    }

    if (options.allowSparse === true) {
        return obj;
    }

    return utils.compact(obj);
};


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

/***/ 80:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "b", function() { return getCountryCode; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "c", function() { return getCurrencyRegion; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "e", function() { return getProductIdsForCart; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "a", function() { return getCategorizedOnboardingProducts; });
/* unused harmony export getProductList */
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "d", function() { return getPriceValue; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "f", function() { return isWCAdmin; });
/* harmony import */ var _wordpress_html_entities__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(34);
/* harmony import */ var _wordpress_html_entities__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_html_entities__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(5);
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _utils_admin_settings__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(23);
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
  let region = getCountryCode(countryState);
  const euCountries = Object(lodash__WEBPACK_IMPORTED_MODULE_1__["without"])(Object(_utils_admin_settings__WEBPACK_IMPORTED_MODULE_2__[/* getAdminSetting */ "d"])('onboarding', {
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
 * @param {Object} productTypes Product Types.
 * @param {Object} profileItems Onboarding profile.
 * @param {boolean} includeInstalledItems Include installed items in returned product IDs.
 * @param {Array} installedPlugins Installed plugins.
 * @return {Array} Product Ids.
 */

function getProductIdsForCart(productTypes, profileItems) {
  let includeInstalledItems = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : false;
  let installedPlugins = arguments.length > 3 ? arguments[3] : undefined;
  const productList = getProductList(profileItems, includeInstalledItems, installedPlugins, productTypes);
  const productIds = productList.map(product => product.id || product.product);
  return productIds;
}
/**
 * Gets the labeled/categorized product names and types for items based on the product types and theme selected in the onboarding profiler.
 *
 * @param {Object} productTypes Product Types.
 * @param {Object} profileItems Onboarding profile.
 * @param {Array} installedPlugins Installed plugins.
 * @return {Array} Objects with labeled/categorized product names and types.
 */

function getCategorizedOnboardingProducts(productTypes, profileItems, installedPlugins) {
  const productList = {};
  productList.products = getProductList(profileItems, true, installedPlugins, productTypes);
  productList.remainingProducts = getProductList(profileItems, false, installedPlugins, productTypes);
  const uniqueItemsList = [...new Set([...productList.products, ...productList.remainingProducts])];
  productList.uniqueItemsList = uniqueItemsList.map(product => {
    let cleanedProduct;

    if (product.label) {
      cleanedProduct = {
        type: 'extension',
        name: product.label
      };
    } else {
      cleanedProduct = {
        type: 'theme',
        name: product.title
      };
    }

    return cleanedProduct;
  });
  return productList;
}
/**
 * Gets a product list for items based on the product types and theme selected in the onboarding profiler.
 *
 * @param {Object} profileItems Onboarding profile.
 * @param {boolean} includeInstalledItems Include installed items in returned product list.
 * @param {Array} installedPlugins Installed plugins.
 * @param {Object} productTypes Product Types.
 * @return {Array} Products.
 */

function getProductList(profileItems) {
  let includeInstalledItems = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : false;
  let installedPlugins = arguments.length > 2 ? arguments[2] : undefined;
  let productTypes = arguments.length > 3 ? arguments[3] : undefined;
  const productList = [];

  if (!productTypes) {
    return productList;
  }

  const profileItemsProductTypes = profileItems.product_types || [];
  profileItemsProductTypes.forEach(productType => {
    if (productTypes[productType] && productTypes[productType].product && (includeInstalledItems || !installedPlugins.includes(productTypes[productType].slug))) {
      productList.push(productTypes[productType]);
    }
  });
  const onboarding = Object(_utils_admin_settings__WEBPACK_IMPORTED_MODULE_2__[/* getAdminSetting */ "d"])('onboarding', {});
  let theme = null;

  if (onboarding && onboarding.themes) {
    theme = onboarding.themes.find(themeData => themeData.slug === profileItems.theme);
  }

  if (theme && theme.id && getPriceValue(theme.price) > 0 && (includeInstalledItems || !theme.is_installed)) {
    productList.push(theme);
  }

  return productList;
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
 * Determines if a URL is a WC admin url.
 *
 * @param {*} url - the url to test
 * @return {boolean} true if the url is a wc-admin URL
 */

function isWCAdmin(url) {
  return /admin.php\?page=wc-admin/.test(url);
}

/***/ }),

/***/ 88:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "d", function() { return getUnreadOrders; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "c", function() { return getOrderStatuses; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "b", function() { return getLowStockCountQuery; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "a", function() { return getLowStockCount; });
/* harmony import */ var _woocommerce_data__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(12);
/* harmony import */ var _woocommerce_data__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_data__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _analytics_settings_config__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(276);
/**
 * External dependencies
 */

/**
 * Internal dependencies
 */


function getUnreadOrders(select, orderStatuses) {
  const {
    getItemsTotalCount,
    getItemsError,
    isResolving
  } = select(_woocommerce_data__WEBPACK_IMPORTED_MODULE_0__["ITEMS_STORE_NAME"]);

  if (!orderStatuses.length) {
    return 0;
  }

  const ordersQuery = {
    page: 1,
    per_page: 1,
    // Core endpoint requires per_page > 0.
    status: orderStatuses,
    _fields: ['id']
  };
  const defaultValue = null; // Disable eslint rule requiring `totalOrders` to be defined below because the next two statements
  // depend on `getItemsTotalCount` to have been called.
  // eslint-disable-next-line @wordpress/no-unused-vars-before-return

  const totalOrders = getItemsTotalCount('orders', ordersQuery, defaultValue);
  const isError = Boolean(getItemsError('orders', ordersQuery));
  const isRequesting = isResolving('getItemsTotalCount', ['orders', ordersQuery, defaultValue]);

  if (isError || isRequesting) {
    return null;
  }

  return totalOrders;
}
function getOrderStatuses(select) {
  const {
    getSetting: getMutableSetting
  } = select(_woocommerce_data__WEBPACK_IMPORTED_MODULE_0__["SETTINGS_STORE_NAME"]);
  const {
    woocommerce_actionable_order_statuses: orderStatuses = _analytics_settings_config__WEBPACK_IMPORTED_MODULE_1__[/* DEFAULT_ACTIONABLE_STATUSES */ "a"]
  } = getMutableSetting('wc_admin', 'wcAdminSettings', {});
  return orderStatuses;
}
const getLowStockCountQuery = {
  page: 1,
  per_page: 1,
  low_in_stock: true,
  status: 'publish',
  _fields: ['id']
};
function getLowStockCount(select) {
  const {
    getItemsTotalCount,
    getItemsError,
    isResolving
  } = select(_woocommerce_data__WEBPACK_IMPORTED_MODULE_0__["ITEMS_STORE_NAME"]);
  const defaultValue = null; // Disable eslint rule requiring `totalLowStockProducts` to be defined below because the next two statements
  // depend on `getItemsTotalCount` to have been called.
  // eslint-disable-next-line @wordpress/no-unused-vars-before-return

  const totalLowStockProducts = getItemsTotalCount('products/low-in-stock', getLowStockCountQuery, defaultValue);
  const isError = Boolean(getItemsError('products/low-in-stock', getLowStockCountQuery));
  const isRequesting = isResolving('getItemsTotalCount', ['products/low-in-stock', getLowStockCountQuery, defaultValue]);

  if (isError || isRequesting && totalLowStockProducts === defaultValue) {
    return null;
  }

  return totalLowStockProducts;
}

/***/ }),

/***/ 9:
/***/ (function(module, exports) {

(function() { module.exports = window["wp"]["primitives"]; }());

/***/ }),

/***/ 92:
/***/ (function(module, exports) {

var g;

// This works in non-strict mode
g = (function() {
	return this;
})();

try {
	// This works if eval is allowed (see CSP)
	g = g || new Function("return this")();
} catch (e) {
	// This works if the window reference is available
	if (typeof window === "object") g = window;
}

// g can still be undefined, but nothing to do about it...
// We return undefined, instead of nothing here, so it's
// easier to handle this case. if(!global) { ...}

module.exports = g;


/***/ })

/******/ });