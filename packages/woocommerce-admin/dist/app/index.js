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
/******/ 	function jsonpScriptSrc(chunkId) {
/******/ 		return __webpack_require__.p + "chunks/" + ({"0":"analytics-report-categories~analytics-report-coupons~analytics-report-customers~analytics-report-dow~99eefb40","1":"vendors~analytics-report-categories~analytics-report-coupons~analytics-report-downloads~analytics-re~2579715d","2":"vendors~activity-panels-inbox~activity-panels-orders~activity-panels-stock~dashboard-charts~devdocs~~f6270017","3":"vendors~activity-panels-inbox~leaderboards~store-alerts~task-list","4":"analytics-report-categories~analytics-report-products","5":"vendors~profile-wizard~task-list","6":"activity-panels-inbox","7":"activity-panels-orders","8":"activity-panels-stock","9":"analytics-report-categories","10":"analytics-report-coupons","11":"analytics-report-customers","12":"analytics-report-downloads","13":"analytics-report-orders","14":"analytics-report-products","15":"analytics-report-revenue","16":"analytics-report-stock","17":"analytics-report-taxes","18":"analytics-settings","23":"customizable-dashboard","24":"dashboard","25":"dashboard-charts","28":"devdocs","29":"homepage","31":"leaderboards","32":"marketing-overview","40":"profile-wizard","41":"store-alerts","42":"store-performance","43":"task-list","44":"vendors~devdocs","45":"vendors~marketing-overview"}[chunkId]||chunkId) + "." + {"0":"14e64a592bdaa342c3a7","1":"3c56dd5478f01faa4b26","2":"20e4990156e0a34c18b0","3":"dd09e8a86b0bcaa9aff7","4":"b94ffcec7f813ed34edb","5":"114a8214ba34dbd3c0f0","6":"99246a03e7b8aa1b8b26","7":"7b32de0acca079abb2bc","8":"6c1332749a67d65f449a","9":"13b966705db9cfeb4814","10":"43d42c8cb52eecf8abdf","11":"86adf07632d91914c6c6","12":"70ac0d2cc5bff1ecc84d","13":"f2d90c48c3f4b0a88512","14":"28c933e5937d420efe22","15":"edcb0bfb0b19d47c76b8","16":"2939053e1a82a2ae848c","17":"d538eaf5233c6bc713b6","18":"8c382b88264494e5d4eb","23":"0857e5e4c0df1171e886","24":"c96cd95056189481403f","25":"d100272c0e3ae443b829","28":"1eb85d89a9e04d20a4c1","29":"e069e25f913b20f89b53","31":"7186f133618817f88e04","32":"c105acbfa46d79f7a6d6","40":"314f6a9f9e0ee86eb8bf","41":"a6374b7de173331a7254","42":"26c7ca1b51a55e317e81","43":"52fe0e41a24d55f1b9b1","44":"e38a65da5bf9ffb4b567","45":"d1653cdc07670674a95f"}[chunkId] + ".js"
/******/ 	}
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
/******/ 		var cssChunks = {"0":1,"4":1,"6":1,"7":1,"8":1,"13":1,"18":1,"24":1,"25":1,"28":1,"31":1,"32":1,"40":1,"41":1,"42":1,"43":1};
/******/ 		if(installedCssChunks[chunkId]) promises.push(installedCssChunks[chunkId]);
/******/ 		else if(installedCssChunks[chunkId] !== 0 && cssChunks[chunkId]) {
/******/ 			promises.push(installedCssChunks[chunkId] = new Promise(function(resolve, reject) {
/******/ 				var href = "./" + ({"0":"analytics-report-categories~analytics-report-coupons~analytics-report-customers~analytics-report-dow~99eefb40","1":"vendors~analytics-report-categories~analytics-report-coupons~analytics-report-downloads~analytics-re~2579715d","2":"vendors~activity-panels-inbox~activity-panels-orders~activity-panels-stock~dashboard-charts~devdocs~~f6270017","3":"vendors~activity-panels-inbox~leaderboards~store-alerts~task-list","4":"analytics-report-categories~analytics-report-products","5":"vendors~profile-wizard~task-list","6":"activity-panels-inbox","7":"activity-panels-orders","8":"activity-panels-stock","9":"analytics-report-categories","10":"analytics-report-coupons","11":"analytics-report-customers","12":"analytics-report-downloads","13":"analytics-report-orders","14":"analytics-report-products","15":"analytics-report-revenue","16":"analytics-report-stock","17":"analytics-report-taxes","18":"analytics-settings","23":"customizable-dashboard","24":"dashboard","25":"dashboard-charts","28":"devdocs","29":"homepage","31":"leaderboards","32":"marketing-overview","40":"profile-wizard","41":"store-alerts","42":"store-performance","43":"task-list","44":"vendors~devdocs","45":"vendors~marketing-overview"}[chunkId]||chunkId) + "/style.css";
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
/******/ 	var jsonpArray = window["webpackJsonp"] = window["webpackJsonp"] || [];
/******/ 	var oldJsonpFunction = jsonpArray.push.bind(jsonpArray);
/******/ 	jsonpArray.push = webpackJsonpCallback;
/******/ 	jsonpArray = jsonpArray.slice();
/******/ 	for(var i = 0; i < jsonpArray.length; i++) webpackJsonpCallback(jsonpArray[i]);
/******/ 	var parentJsonpFunction = oldJsonpFunction;
/******/
/******/
/******/ 	// Load entry module and return exports
/******/ 	return __webpack_require__(__webpack_require__.s = 430);
/******/ })
/************************************************************************/
/******/ ({

/***/ 0:
/***/ (function(module, exports) {

(function() { module.exports = this["wp"]["element"]; }());

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
  module.exports = __webpack_require__(138)();
}


/***/ }),

/***/ 10:
/***/ (function(module, exports, __webpack_require__) {

/*!
  Copyright (c) 2017 Jed Watson.
  Licensed under the MIT License (MIT), see
  http://jedwatson.github.io/classnames
*/
/* global define */

(function () {
	'use strict';

	var hasOwn = {}.hasOwnProperty;

	function classNames () {
		var classes = [];

		for (var i = 0; i < arguments.length; i++) {
			var arg = arguments[i];
			if (!arg) continue;

			var argType = typeof arg;

			if (argType === 'string' || argType === 'number') {
				classes.push(arg);
			} else if (Array.isArray(arg) && arg.length) {
				var inner = classNames.apply(null, arg);
				if (inner) {
					classes.push(inner);
				}
			} else if (argType === 'object') {
				for (var key in arg) {
					if (hasOwn.call(arg, key) && arg[key]) {
						classes.push(key);
					}
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

/***/ 100:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(2);
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_1__);


/**
 * External dependencies
 */


function Shortcut(_ref) {
  var shortcut = _ref.shortcut,
      className = _ref.className;

  if (!shortcut) {
    return null;
  }

  var displayText;
  var ariaLabel;

  if (Object(lodash__WEBPACK_IMPORTED_MODULE_1__["isString"])(shortcut)) {
    displayText = shortcut;
  }

  if (Object(lodash__WEBPACK_IMPORTED_MODULE_1__["isObject"])(shortcut)) {
    displayText = shortcut.display;
    ariaLabel = shortcut.ariaLabel;
  }

  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("span", {
    className: className,
    "aria-label": ariaLabel
  }, displayText);
}

/* harmony default export */ __webpack_exports__["a"] = (Shortcut);
//# sourceMappingURL=index.js.map

/***/ }),

/***/ 101:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(105);
/* harmony import */ var _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(41);
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(40);
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(59);
/* harmony import */ var _babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(44);
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(29);
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(42);
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(2);
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_8__);
/* harmony import */ var _wordpress_is_shallow_equal__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(77);
/* harmony import */ var _wordpress_is_shallow_equal__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(_wordpress_is_shallow_equal__WEBPACK_IMPORTED_MODULE_9__);
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(53);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(19);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_11___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_11__);









function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function () { var Super = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_5___default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_5___default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_4___default()(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/**
 * NOTE: This is temporary code. It exists only until a version of `@wordpress/data`
 * is released which supports this functionality.
 *
 * @todo Remove this and use `@wordpress/data` `withSelect` instead after
 * this PR is merged: https://github.com/WordPress/gutenberg/pull/11460
 */

/**
 * External dependencies
 */





/**
 * Higher-order component used to inject state-derived props using registered
 * selectors.
 *
 * @param {Function} mapSelectToProps Function called on every state change,
 *                                   expected to return object of props to
 *                                   merge with the component's own props.
 *
 * @return {Component} Enhanced component with merged state data props.
 */

var withSelect = function withSelect(mapSelectToProps) {
  return Object(_wordpress_compose__WEBPACK_IMPORTED_MODULE_10__[/* default */ "a"])(function (WrappedComponent) {
    /**
     * Default merge props. A constant value is used as the fallback since it
     * can be more efficiently shallow compared in case component is repeatedly
     * rendered without its own merge props.
     *
     * @type {Object}
     */
    var DEFAULT_MERGE_PROPS = {};

    var ComponentWithSelect = /*#__PURE__*/function (_Component) {
      _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_6___default()(ComponentWithSelect, _Component);

      var _super = _createSuper(ComponentWithSelect);

      function ComponentWithSelect(props) {
        var _this;

        _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_1___default()(this, ComponentWithSelect);

        _this = _super.call(this, props);
        _this.onStoreChange = _this.onStoreChange.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_3___default()(_this));

        _this.subscribe(props.registry);

        _this.onUnmounts = {};
        _this.mergeProps = _this.getNextMergeProps(props);
        return _this;
      }
      /**
       * Given a props object, returns the next merge props by mapSelectToProps.
       *
       * @param {Object} props Props to pass as argument to mapSelectToProps.
       *
       * @return {Object} Props to merge into rendered wrapped element.
       */


      _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_2___default()(ComponentWithSelect, [{
        key: "getNextMergeProps",
        value: function getNextMergeProps(props) {
          var _this2 = this;

          var storeSelectors = {};
          var onCompletes = [];
          var componentContext = {
            component: this
          };

          var getStoreFromRegistry = function getStoreFromRegistry(key, registry, context) {
            // This is our first time selecting from this store.
            // Do some lazy-loading of handling at this time.
            var selectorsForKey = registry.select(key);

            if (Object(lodash__WEBPACK_IMPORTED_MODULE_8__["isFunction"])(selectorsForKey)) {
              // This store has special handling for its selectors.
              // We give it a context, and we check for a "resolve"
              var _selectorsForKey = selectorsForKey(context),
                  selectors = _selectorsForKey.selectors,
                  onComplete = _selectorsForKey.onComplete,
                  onUnmount = _selectorsForKey.onUnmount;

              if (onComplete) {
                onCompletes.push(onComplete);
              }

              if (onUnmount) {
                _this2.onUnmounts[key] = onUnmount;
              }

              storeSelectors[key] = selectors;
            } else {
              storeSelectors[key] = selectorsForKey;
            }
          };

          var select = function select(key) {
            if (!storeSelectors[key]) {
              getStoreFromRegistry(key, props.registry, componentContext);
            }

            return storeSelectors[key];
          };

          var selectedProps = mapSelectToProps(select, props.ownProps) || DEFAULT_MERGE_PROPS; // Complete the select for those stores which support it.

          onCompletes.forEach(function (onComplete) {
            return onComplete();
          });
          return selectedProps;
        }
      }, {
        key: "componentDidMount",
        value: function componentDidMount() {
          this.canRunSelection = true; // A state change may have occurred between the constructor and
          // mount of the component (e.g. during the wrapped component's own
          // constructor), in which case selection should be rerun.

          if (this.hasQueuedSelection) {
            this.hasQueuedSelection = false;
            this.onStoreChange();
          }
        }
      }, {
        key: "componentWillUnmount",
        value: function componentWillUnmount() {
          var _this3 = this;

          this.canRunSelection = false;
          this.unsubscribe();
          Object.keys(this.onUnmounts).forEach(function (key) {
            return _this3.onUnmounts[key]();
          });
        }
      }, {
        key: "shouldComponentUpdate",
        value: function shouldComponentUpdate(nextProps, nextState) {
          // Cycle subscription if registry changes.
          var hasRegistryChanged = nextProps.registry !== this.props.registry;

          if (hasRegistryChanged) {
            this.unsubscribe();
            this.subscribe(nextProps.registry);
          } // Treat a registry change as equivalent to `ownProps`, to reflect
          // `mergeProps` to rendered component if and only if updated.


          var hasPropsChanged = hasRegistryChanged || !_wordpress_is_shallow_equal__WEBPACK_IMPORTED_MODULE_9___default()(this.props.ownProps, nextProps.ownProps); // Only render if props have changed or merge props have been updated
          // from the store subscriber.

          if (this.state === nextState && !hasPropsChanged) {
            return false;
          }

          if (hasPropsChanged) {
            var nextMergeProps = this.getNextMergeProps(nextProps);

            if (!_wordpress_is_shallow_equal__WEBPACK_IMPORTED_MODULE_9___default()(this.mergeProps, nextMergeProps)) {
              // If merge props change as a result of the incoming props,
              // they should be reflected as such in the upcoming render.
              // While side effects are discouraged in lifecycle methods,
              // this component is used heavily, and prior efforts to use
              // `getDerivedStateFromProps` had demonstrated miserable
              // performance.
              this.mergeProps = nextMergeProps;
            } // Regardless whether merge props are changing, fall through to
            // incur the render since the component will need to receive
            // the changed `ownProps`.

          }

          return true;
        }
      }, {
        key: "onStoreChange",
        value: function onStoreChange() {
          if (!this.canRunSelection) {
            this.hasQueuedSelection = true;
            return;
          }

          var nextMergeProps = this.getNextMergeProps(this.props);

          if (_wordpress_is_shallow_equal__WEBPACK_IMPORTED_MODULE_9___default()(this.mergeProps, nextMergeProps)) {
            return;
          }

          this.mergeProps = nextMergeProps; // Schedule an update. Merge props are not assigned to state since
          // derivation of merge props from incoming props occurs within
          // shouldComponentUpdate, where setState is not allowed. setState
          // is used here instead of forceUpdate because forceUpdate bypasses
          // shouldComponentUpdate altogether, which isn't desireable if both
          // state and props change within the same render. Unfortunately,
          // this requires that next merge props are generated twice.

          this.setState({});
        }
      }, {
        key: "subscribe",
        value: function subscribe(registry) {
          this.unsubscribe = registry.subscribe(this.onStoreChange);
        }
      }, {
        key: "render",
        value: function render() {
          return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])(WrappedComponent, _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0___default()({}, this.props.ownProps, this.mergeProps));
        }
      }]);

      return ComponentWithSelect;
    }(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["Component"]);

    return function (ownProps) {
      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])(_wordpress_data__WEBPACK_IMPORTED_MODULE_11__["RegistryConsumer"], null, function (registry) {
        return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])(ComponentWithSelect, {
          ownProps: ownProps,
          registry: registry
        });
      });
    };
  }, 'withSelect');
};

/* harmony default export */ __webpack_exports__["a"] = (withSelect);

/***/ }),

/***/ 104:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var _woocommerce_date__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(23);
/* harmony import */ var _woocommerce_date__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_date__WEBPACK_IMPORTED_MODULE_0__);
/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "k", function() { return _woocommerce_date__WEBPACK_IMPORTED_MODULE_0__["isoDateFormat"]; });

/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "a", function() { return _woocommerce_date__WEBPACK_IMPORTED_MODULE_0__["appendTimestamp"]; });

/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "h", function() { return _woocommerce_date__WEBPACK_IMPORTED_MODULE_0__["getDateParamsFromQuery"]; });

/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "f", function() { return _woocommerce_date__WEBPACK_IMPORTED_MODULE_0__["getCurrentDates"]; });

/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "j", function() { return _woocommerce_date__WEBPACK_IMPORTED_MODULE_0__["getPreviousDate"]; });

/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "d", function() { return _woocommerce_date__WEBPACK_IMPORTED_MODULE_0__["getAllowedIntervalsForQuery"]; });

/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "i", function() { return _woocommerce_date__WEBPACK_IMPORTED_MODULE_0__["getIntervalForQuery"]; });

/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "e", function() { return _woocommerce_date__WEBPACK_IMPORTED_MODULE_0__["getChartTypeForQuery"]; });

/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "c", function() { return _woocommerce_date__WEBPACK_IMPORTED_MODULE_0__["defaultTableDateFormat"]; });

/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "g", function() { return _woocommerce_date__WEBPACK_IMPORTED_MODULE_0__["getDateFormatsForInterval"]; });

/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "b", function() { return _woocommerce_date__WEBPACK_IMPORTED_MODULE_0__["dateValidationMessages"]; });

/**
 * External dependencies
 */

/**
 * Internal dependencies
 */

/**
 * WooCommerce dependencies
 */
 // Export the expected API for the consuming app.



/***/ }),

/***/ 105:
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
  };

  return _extends.apply(this, arguments);
}

module.exports = _extends;

/***/ }),

/***/ 106:
/***/ (function(module, exports, __webpack_require__) {

"use strict";


/**
 * Copyright (c) 2013-present, Facebook, Inc.
 *
 * This source code is licensed under the MIT license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * 
 */

function makeEmptyFunction(arg) {
  return function () {
    return arg;
  };
}

/**
 * This function accepts and discards inputs; it has no side effects. This is
 * primarily useful idiomatically for overridable function endpoints which
 * always need to be callable, since JS lacks a null-call idiom ala Cocoa.
 */
var emptyFunction = function emptyFunction() {};

emptyFunction.thatReturns = makeEmptyFunction;
emptyFunction.thatReturnsFalse = makeEmptyFunction(false);
emptyFunction.thatReturnsTrue = makeEmptyFunction(true);
emptyFunction.thatReturnsNull = makeEmptyFunction(null);
emptyFunction.thatReturnsThis = function () {
  return this;
};
emptyFunction.thatReturnsArgument = function (arg) {
  return arg;
};

module.exports = emptyFunction;

/***/ }),

/***/ 107:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* unused harmony export isHorizontalEdge */
/* unused harmony export isVerticalEdge */
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "a", function() { return getRectangleFromRange; });
/* unused harmony export computeCaretRect */
/* unused harmony export placeCaretAtHorizontalEdge */
/* unused harmony export placeCaretAtVerticalEdge */
/* unused harmony export isTextField */
/* unused harmony export documentHasSelection */
/* unused harmony export isEntirelySelected */
/* unused harmony export getScrollContainer */
/* unused harmony export getOffsetParent */
/* unused harmony export replace */
/* unused harmony export remove */
/* unused harmony export insertAfter */
/* unused harmony export unwrap */
/* unused harmony export replaceTag */
/* unused harmony export wrap */
/* unused harmony export __unstableStripHTML */
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(2);
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_0__);
/**
 * External dependencies
 */

/**
 * Browser dependencies
 */

var _window = window,
    DOMParser = _window.DOMParser,
    getComputedStyle = _window.getComputedStyle;
var _window$Node = window.Node,
    TEXT_NODE = _window$Node.TEXT_NODE,
    ELEMENT_NODE = _window$Node.ELEMENT_NODE,
    DOCUMENT_POSITION_PRECEDING = _window$Node.DOCUMENT_POSITION_PRECEDING,
    DOCUMENT_POSITION_FOLLOWING = _window$Node.DOCUMENT_POSITION_FOLLOWING;
/**
 * Returns true if the given selection object is in the forward direction, or
 * false otherwise.
 *
 * @see https://developer.mozilla.org/en-US/docs/Web/API/Node/compareDocumentPosition
 *
 * @param {Selection} selection Selection object to check.
 *
 * @return {boolean} Whether the selection is forward.
 */

function isSelectionForward(selection) {
  var anchorNode = selection.anchorNode,
      focusNode = selection.focusNode,
      anchorOffset = selection.anchorOffset,
      focusOffset = selection.focusOffset;
  var position = anchorNode.compareDocumentPosition(focusNode); // Disable reason: `Node#compareDocumentPosition` returns a bitmask value,
  // so bitwise operators are intended.

  /* eslint-disable no-bitwise */
  // Compare whether anchor node precedes focus node. If focus node (where
  // end of selection occurs) is after the anchor node, it is forward.

  if (position & DOCUMENT_POSITION_PRECEDING) {
    return false;
  }

  if (position & DOCUMENT_POSITION_FOLLOWING) {
    return true;
  }
  /* eslint-enable no-bitwise */
  // `compareDocumentPosition` returns 0 when passed the same node, in which
  // case compare offsets.


  if (position === 0) {
    return anchorOffset <= focusOffset;
  } // This should never be reached, but return true as default case.


  return true;
}
/**
 * Check whether the selection is at the edge of the container. Checks for
 * horizontal position by default. Set `onlyVertical` to true to check only
 * vertically.
 *
 * @param {Element} container    Focusable element.
 * @param {boolean} isReverse    Set to true to check left, false to check right.
 * @param {boolean} onlyVertical Set to true to check only vertical position.
 *
 * @return {boolean} True if at the edge, false if not.
 */


function isEdge(container, isReverse, onlyVertical) {
  if (Object(lodash__WEBPACK_IMPORTED_MODULE_0__["includes"])(['INPUT', 'TEXTAREA'], container.tagName)) {
    if (container.selectionStart !== container.selectionEnd) {
      return false;
    }

    if (isReverse) {
      return container.selectionStart === 0;
    }

    return container.value.length === container.selectionStart;
  }

  if (!container.isContentEditable) {
    return true;
  }

  var selection = window.getSelection();

  if (!selection.rangeCount) {
    return false;
  }

  var originalRange = selection.getRangeAt(0);
  var range = originalRange.cloneRange();
  var isForward = isSelectionForward(selection);
  var isCollapsed = selection.isCollapsed; // Collapse in direction of selection.

  if (!isCollapsed) {
    range.collapse(!isForward);
  }

  var rangeRect = getRectangleFromRange(range);

  if (!rangeRect) {
    return false;
  }

  var computedStyle = window.getComputedStyle(container);
  var lineHeight = parseInt(computedStyle.lineHeight, 10) || 0; // Only consider the multiline selection at the edge if the direction is
  // towards the edge.

  if (!isCollapsed && rangeRect.height > lineHeight && isForward === isReverse) {
    return false;
  }

  var padding = parseInt(computedStyle["padding".concat(isReverse ? 'Top' : 'Bottom')], 10) || 0; // Calculate a buffer that is half the line height. In some browsers, the
  // selection rectangle may not fill the entire height of the line, so we add
  // 3/4 the line height to the selection rectangle to ensure that it is well
  // over its line boundary.

  var buffer = 3 * parseInt(lineHeight, 10) / 4;
  var containerRect = container.getBoundingClientRect();
  var originalRangeRect = getRectangleFromRange(originalRange);
  var verticalEdge = isReverse ? containerRect.top + padding > originalRangeRect.top - buffer : containerRect.bottom - padding < originalRangeRect.bottom + buffer;

  if (!verticalEdge) {
    return false;
  }

  if (onlyVertical) {
    return true;
  } // In the case of RTL scripts, the horizontal edge is at the opposite side.


  var direction = computedStyle.direction;
  var isReverseDir = direction === 'rtl' ? !isReverse : isReverse; // To calculate the horizontal position, we insert a test range and see if
  // this test range has the same horizontal position. This method proves to
  // be better than a DOM-based calculation, because it ignores empty text
  // nodes and a trailing line break element. In other words, we need to check
  // visual positioning, not DOM positioning.

  var x = isReverseDir ? containerRect.left + 1 : containerRect.right - 1;
  var y = isReverse ? containerRect.top + buffer : containerRect.bottom - buffer;
  var testRange = hiddenCaretRangeFromPoint(document, x, y, container);

  if (!testRange) {
    return false;
  }

  var side = isReverseDir ? 'left' : 'right';
  var testRect = getRectangleFromRange(testRange); // Allow the position to be 1px off.

  return Math.abs(testRect[side] - rangeRect[side]) <= 1;
}
/**
 * Check whether the selection is horizontally at the edge of the container.
 *
 * @param {Element} container Focusable element.
 * @param {boolean} isReverse Set to true to check left, false for right.
 *
 * @return {boolean} True if at the horizontal edge, false if not.
 */


function isHorizontalEdge(container, isReverse) {
  return isEdge(container, isReverse);
}
/**
 * Check whether the selection is vertically at the edge of the container.
 *
 * @param {Element} container Focusable element.
 * @param {boolean} isReverse Set to true to check top, false for bottom.
 *
 * @return {boolean} True if at the vertical edge, false if not.
 */

function isVerticalEdge(container, isReverse) {
  return isEdge(container, isReverse, true);
}
/**
 * Get the rectangle of a given Range.
 *
 * @param {Range} range The range.
 *
 * @return {DOMRect} The rectangle.
 */

function getRectangleFromRange(range) {
  // For uncollapsed ranges, get the rectangle that bounds the contents of the
  // range; this a rectangle enclosing the union of the bounding rectangles
  // for all the elements in the range.
  if (!range.collapsed) {
    return range.getBoundingClientRect();
  }

  var _range = range,
      startContainer = _range.startContainer; // Correct invalid "BR" ranges. The cannot contain any children.

  if (startContainer.nodeName === 'BR') {
    var parentNode = startContainer.parentNode;
    var index = Array.from(parentNode.childNodes).indexOf(startContainer);
    range = document.createRange();
    range.setStart(parentNode, index);
    range.setEnd(parentNode, index);
  }

  var rect = range.getClientRects()[0]; // If the collapsed range starts (and therefore ends) at an element node,
  // `getClientRects` can be empty in some browsers. This can be resolved
  // by adding a temporary text node with zero-width space to the range.
  //
  // See: https://stackoverflow.com/a/6847328/995445

  if (!rect) {
    var padNode = document.createTextNode("\u200B"); // Do not modify the live range.

    range = range.cloneRange();
    range.insertNode(padNode);
    rect = range.getClientRects()[0];
    padNode.parentNode.removeChild(padNode);
  }

  return rect;
}
/**
 * Get the rectangle for the selection in a container.
 *
 * @return {?DOMRect} The rectangle.
 */

function computeCaretRect() {
  var selection = window.getSelection();
  var range = selection.rangeCount ? selection.getRangeAt(0) : null;

  if (!range) {
    return;
  }

  return getRectangleFromRange(range);
}
/**
 * Places the caret at start or end of a given element.
 *
 * @param {Element} container Focusable element.
 * @param {boolean} isReverse True for end, false for start.
 */

function placeCaretAtHorizontalEdge(container, isReverse) {
  if (!container) {
    return;
  }

  if (Object(lodash__WEBPACK_IMPORTED_MODULE_0__["includes"])(['INPUT', 'TEXTAREA'], container.tagName)) {
    container.focus();

    if (isReverse) {
      container.selectionStart = container.value.length;
      container.selectionEnd = container.value.length;
    } else {
      container.selectionStart = 0;
      container.selectionEnd = 0;
    }

    return;
  }

  container.focus();

  if (!container.isContentEditable) {
    return;
  } // Select on extent child of the container, not the container itself. This
  // avoids the selection always being `endOffset` of 1 when placed at end,
  // where `startContainer`, `endContainer` would always be container itself.


  var rangeTarget = container[isReverse ? 'lastChild' : 'firstChild']; // If no range target, it implies that the container is empty. Focusing is
  // sufficient for caret to be placed correctly.

  if (!rangeTarget) {
    return;
  }

  var selection = window.getSelection();
  var range = document.createRange();
  range.selectNodeContents(rangeTarget);
  range.collapse(!isReverse);
  selection.removeAllRanges();
  selection.addRange(range);
}
/**
 * Polyfill.
 * Get a collapsed range for a given point.
 *
 * @see https://developer.mozilla.org/en-US/docs/Web/API/Document/caretRangeFromPoint
 *
 * @param {Document} doc The document of the range.
 * @param {number}    x   Horizontal position within the current viewport.
 * @param {number}    y   Vertical position within the current viewport.
 *
 * @return {?Range} The best range for the given point.
 */

function caretRangeFromPoint(doc, x, y) {
  if (doc.caretRangeFromPoint) {
    return doc.caretRangeFromPoint(x, y);
  }

  if (!doc.caretPositionFromPoint) {
    return null;
  }

  var point = doc.caretPositionFromPoint(x, y); // If x or y are negative, outside viewport, or there is no text entry node.
  // https://developer.mozilla.org/en-US/docs/Web/API/Document/caretRangeFromPoint

  if (!point) {
    return null;
  }

  var range = doc.createRange();
  range.setStart(point.offsetNode, point.offset);
  range.collapse(true);
  return range;
}
/**
 * Get a collapsed range for a given point.
 * Gives the container a temporary high z-index (above any UI).
 * This is preferred over getting the UI nodes and set styles there.
 *
 * @param {Document} doc       The document of the range.
 * @param {number}    x         Horizontal position within the current viewport.
 * @param {number}    y         Vertical position within the current viewport.
 * @param {Element}  container Container in which the range is expected to be found.
 *
 * @return {?Range} The best range for the given point.
 */


function hiddenCaretRangeFromPoint(doc, x, y, container) {
  var originalZIndex = container.style.zIndex;
  var originalPosition = container.style.position; // A z-index only works if the element position is not static.

  container.style.zIndex = '10000';
  container.style.position = 'relative';
  var range = caretRangeFromPoint(doc, x, y);
  container.style.zIndex = originalZIndex;
  container.style.position = originalPosition;
  return range;
}
/**
 * Places the caret at the top or bottom of a given element.
 *
 * @param {Element} container           Focusable element.
 * @param {boolean} isReverse           True for bottom, false for top.
 * @param {DOMRect} [rect]              The rectangle to position the caret with.
 * @param {boolean} [mayUseScroll=true] True to allow scrolling, false to disallow.
 */


function placeCaretAtVerticalEdge(container, isReverse, rect) {
  var mayUseScroll = arguments.length > 3 && arguments[3] !== undefined ? arguments[3] : true;

  if (!container) {
    return;
  }

  if (!rect || !container.isContentEditable) {
    placeCaretAtHorizontalEdge(container, isReverse);
    return;
  } // Offset by a buffer half the height of the caret rect. This is needed
  // because caretRangeFromPoint may default to the end of the selection if
  // offset is too close to the edge. It's unclear how to precisely calculate
  // this threshold; it may be the padded area of some combination of line
  // height, caret height, and font size. The buffer offset is effectively
  // equivalent to a point at half the height of a line of text.


  var buffer = rect.height / 2;
  var editableRect = container.getBoundingClientRect();
  var x = rect.left;
  var y = isReverse ? editableRect.bottom - buffer : editableRect.top + buffer;
  var range = hiddenCaretRangeFromPoint(document, x, y, container);

  if (!range || !container.contains(range.startContainer)) {
    if (mayUseScroll && (!range || !range.startContainer || !range.startContainer.contains(container))) {
      // Might be out of view.
      // Easier than attempting to calculate manually.
      container.scrollIntoView(isReverse);
      placeCaretAtVerticalEdge(container, isReverse, rect, false);
      return;
    }

    placeCaretAtHorizontalEdge(container, isReverse);
    return;
  }

  var selection = window.getSelection();
  selection.removeAllRanges();
  selection.addRange(range);
  container.focus(); // Editable was already focussed, it goes back to old range...
  // This fixes it.

  selection.removeAllRanges();
  selection.addRange(range);
}
/**
 * Check whether the given element is a text field, where text field is defined
 * by the ability to select within the input, or that it is contenteditable.
 *
 * See: https://html.spec.whatwg.org/#textFieldSelection
 *
 * @param {HTMLElement} element The HTML element.
 *
 * @return {boolean} True if the element is an text field, false if not.
 */

function isTextField(element) {
  try {
    var nodeName = element.nodeName,
        selectionStart = element.selectionStart,
        contentEditable = element.contentEditable;
    return nodeName === 'INPUT' && selectionStart !== null || nodeName === 'TEXTAREA' || contentEditable === 'true';
  } catch (error) {
    // Safari throws an exception when trying to get `selectionStart`
    // on non-text <input> elements (which, understandably, don't
    // have the text selection API). We catch this via a try/catch
    // block, as opposed to a more explicit check of the element's
    // input types, because of Safari's non-standard behavior. This
    // also means we don't have to worry about the list of input
    // types that support `selectionStart` changing as the HTML spec
    // evolves over time.
    return false;
  }
}
/**
 * Check wether the current document has a selection.
 * This checks both for focus in an input field and general text selection.
 *
 * @return {boolean} True if there is selection, false if not.
 */

function documentHasSelection() {
  if (isTextField(document.activeElement)) {
    return true;
  }

  var selection = window.getSelection();
  var range = selection.rangeCount ? selection.getRangeAt(0) : null;
  return range && !range.collapsed;
}
/**
 * Check whether the contents of the element have been entirely selected.
 * Returns true if there is no possibility of selection.
 *
 * @param {Element} element The element to check.
 *
 * @return {boolean} True if entirely selected, false if not.
 */

function isEntirelySelected(element) {
  if (Object(lodash__WEBPACK_IMPORTED_MODULE_0__["includes"])(['INPUT', 'TEXTAREA'], element.nodeName)) {
    return element.selectionStart === 0 && element.value.length === element.selectionEnd;
  }

  if (!element.isContentEditable) {
    return true;
  }

  var selection = window.getSelection();
  var range = selection.rangeCount ? selection.getRangeAt(0) : null;

  if (!range) {
    return true;
  }

  var startContainer = range.startContainer,
      endContainer = range.endContainer,
      startOffset = range.startOffset,
      endOffset = range.endOffset;

  if (startContainer === element && endContainer === element && startOffset === 0 && endOffset === element.childNodes.length) {
    return true;
  }

  var lastChild = element.lastChild;
  var lastChildContentLength = lastChild.nodeType === TEXT_NODE ? lastChild.data.length : lastChild.childNodes.length;
  return startContainer === element.firstChild && endContainer === element.lastChild && startOffset === 0 && endOffset === lastChildContentLength;
}
/**
 * Given a DOM node, finds the closest scrollable container node.
 *
 * @param {Element} node Node from which to start.
 *
 * @return {?Element} Scrollable container node, if found.
 */

function getScrollContainer(node) {
  if (!node) {
    return;
  } // Scrollable if scrollable height exceeds displayed...


  if (node.scrollHeight > node.clientHeight) {
    // ...except when overflow is defined to be hidden or visible
    var _window$getComputedSt = window.getComputedStyle(node),
        overflowY = _window$getComputedSt.overflowY;

    if (/(auto|scroll)/.test(overflowY)) {
      return node;
    }
  } // Continue traversing


  return getScrollContainer(node.parentNode);
}
/**
 * Returns the closest positioned element, or null under any of the conditions
 * of the offsetParent specification. Unlike offsetParent, this function is not
 * limited to HTMLElement and accepts any Node (e.g. Node.TEXT_NODE).
 *
 * @see https://drafts.csswg.org/cssom-view/#dom-htmlelement-offsetparent
 *
 * @param {Node} node Node from which to find offset parent.
 *
 * @return {?Node} Offset parent.
 */

function getOffsetParent(node) {
  // Cannot retrieve computed style or offset parent only anything other than
  // an element node, so find the closest element node.
  var closestElement;

  while (closestElement = node.parentNode) {
    if (closestElement.nodeType === ELEMENT_NODE) {
      break;
    }
  }

  if (!closestElement) {
    return null;
  } // If the closest element is already positioned, return it, as offsetParent
  // does not otherwise consider the node itself.


  if (getComputedStyle(closestElement).position !== 'static') {
    return closestElement;
  }

  return closestElement.offsetParent;
}
/**
 * Given two DOM nodes, replaces the former with the latter in the DOM.
 *
 * @param {Element} processedNode Node to be removed.
 * @param {Element} newNode       Node to be inserted in its place.
 * @return {void}
 */

function replace(processedNode, newNode) {
  insertAfter(newNode, processedNode.parentNode);
  remove(processedNode);
}
/**
 * Given a DOM node, removes it from the DOM.
 *
 * @param {Element} node Node to be removed.
 * @return {void}
 */

function remove(node) {
  node.parentNode.removeChild(node);
}
/**
 * Given two DOM nodes, inserts the former in the DOM as the next sibling of
 * the latter.
 *
 * @param {Element} newNode       Node to be inserted.
 * @param {Element} referenceNode Node after which to perform the insertion.
 * @return {void}
 */

function insertAfter(newNode, referenceNode) {
  referenceNode.parentNode.insertBefore(newNode, referenceNode.nextSibling);
}
/**
 * Unwrap the given node. This means any child nodes are moved to the parent.
 *
 * @param {Node} node The node to unwrap.
 *
 * @return {void}
 */

function unwrap(node) {
  var parent = node.parentNode;

  while (node.firstChild) {
    parent.insertBefore(node.firstChild, node);
  }

  parent.removeChild(node);
}
/**
 * Replaces the given node with a new node with the given tag name.
 *
 * @param {Element}  node    The node to replace
 * @param {string}   tagName The new tag name.
 *
 * @return {Element} The new node.
 */

function replaceTag(node, tagName) {
  var newNode = node.ownerDocument.createElement(tagName);

  while (node.firstChild) {
    newNode.appendChild(node.firstChild);
  }

  node.parentNode.replaceChild(newNode, node);
  return newNode;
}
/**
 * Wraps the given node with a new node with the given tag name.
 *
 * @param {Element} newNode       The node to insert.
 * @param {Element} referenceNode The node to wrap.
 */

function wrap(newNode, referenceNode) {
  referenceNode.parentNode.insertBefore(newNode, referenceNode);
  newNode.appendChild(referenceNode);
}
/**
 * Removes any HTML tags from the provided string.
 *
 * @param {string} html The string containing html.
 *
 * @return {string} The text content with any html removed.
 */

function __unstableStripHTML(html) {
  var document = new DOMParser().parseFromString(html, 'text/html');
  return document.body.textContent || '';
}
//# sourceMappingURL=dom.js.map

/***/ }),

/***/ 109:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var _babel_runtime_helpers_esm_objectSpread__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(27);
/* harmony import */ var _babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(11);
/* harmony import */ var _babel_runtime_helpers_esm_objectWithoutProperties__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(16);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _dashicon__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(80);
/* harmony import */ var _primitives__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(62);




/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */




function Icon(_ref) {
  var _ref$icon = _ref.icon,
      icon = _ref$icon === void 0 ? null : _ref$icon,
      size = _ref.size,
      additionalProps = Object(_babel_runtime_helpers_esm_objectWithoutProperties__WEBPACK_IMPORTED_MODULE_2__[/* default */ "a"])(_ref, ["icon", "size"]);

  // Dashicons should be 20x20 by default.
  var dashiconSize = size || 20;

  if ('string' === typeof icon) {
    return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_3__["createElement"])(_dashicon__WEBPACK_IMPORTED_MODULE_4__[/* default */ "a"], Object(_babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_1__[/* default */ "a"])({
      icon: icon,
      size: dashiconSize
    }, additionalProps));
  }

  if (icon && _dashicon__WEBPACK_IMPORTED_MODULE_4__[/* default */ "a"] === icon.type) {
    return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_3__["cloneElement"])(icon, Object(_babel_runtime_helpers_esm_objectSpread__WEBPACK_IMPORTED_MODULE_0__[/* default */ "a"])({
      size: dashiconSize
    }, additionalProps));
  } // Icons should be 24x24 by default.


  var iconSize = size || 24;

  if ('function' === typeof icon) {
    if (icon.prototype instanceof _wordpress_element__WEBPACK_IMPORTED_MODULE_3__["Component"]) {
      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_3__["createElement"])(icon, Object(_babel_runtime_helpers_esm_objectSpread__WEBPACK_IMPORTED_MODULE_0__[/* default */ "a"])({
        size: iconSize
      }, additionalProps));
    }

    return icon(Object(_babel_runtime_helpers_esm_objectSpread__WEBPACK_IMPORTED_MODULE_0__[/* default */ "a"])({
      size: iconSize
    }, additionalProps));
  }

  if (icon && (icon.type === 'svg' || icon.type === _primitives__WEBPACK_IMPORTED_MODULE_5__[/* SVG */ "b"])) {
    var appliedProps = Object(_babel_runtime_helpers_esm_objectSpread__WEBPACK_IMPORTED_MODULE_0__[/* default */ "a"])({
      width: iconSize,
      height: iconSize
    }, icon.props, additionalProps);

    return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_3__["createElement"])(_primitives__WEBPACK_IMPORTED_MODULE_5__[/* SVG */ "b"], appliedProps);
  }

  if (Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_3__["isValidElement"])(icon)) {
    return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_3__["cloneElement"])(icon, Object(_babel_runtime_helpers_esm_objectSpread__WEBPACK_IMPORTED_MODULE_0__[/* default */ "a"])({
      size: iconSize
    }, additionalProps));
  }

  return icon;
}

/* harmony default export */ __webpack_exports__["a"] = (Icon);
//# sourceMappingURL=index.js.map

/***/ }),

/***/ 11:
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

/***/ 110:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var _babel_runtime_helpers_esm_classCallCheck__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(7);
/* harmony import */ var _babel_runtime_helpers_esm_createClass__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(6);
/* harmony import */ var _babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(8);
/* harmony import */ var _babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(4);
/* harmony import */ var _babel_runtime_helpers_esm_inherits__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(9);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(2);
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var _popover__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(92);
/* harmony import */ var _shortcut__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(100);







/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */


/**
 * Internal dependencies
 */



/**
 * Time over children to wait before showing tooltip
 *
 * @type {number}
 */

var TOOLTIP_DELAY = 700;

var Tooltip =
/*#__PURE__*/
function (_Component) {
  Object(_babel_runtime_helpers_esm_inherits__WEBPACK_IMPORTED_MODULE_4__[/* default */ "a"])(Tooltip, _Component);

  function Tooltip() {
    var _this;

    Object(_babel_runtime_helpers_esm_classCallCheck__WEBPACK_IMPORTED_MODULE_0__[/* default */ "a"])(this, Tooltip);

    _this = Object(_babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_2__[/* default */ "a"])(this, Object(_babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_3__[/* default */ "a"])(Tooltip).apply(this, arguments));
    _this.delayedSetIsOver = Object(lodash__WEBPACK_IMPORTED_MODULE_6__["debounce"])(function (isOver) {
      return _this.setState({
        isOver: isOver
      });
    }, TOOLTIP_DELAY);
    /**
     * Prebound `isInMouseDown` handler, created as a constant reference to
     * assure ability to remove in component unmount.
     *
     * @type {Function}
     */

    _this.cancelIsMouseDown = _this.createSetIsMouseDown(false);
    /**
     * Whether a the mouse is currently pressed, used in determining whether
     * to handle a focus event as displaying the tooltip immediately.
     *
     * @type {boolean}
     */

    _this.isInMouseDown = false;
    _this.state = {
      isOver: false
    };
    return _this;
  }

  Object(_babel_runtime_helpers_esm_createClass__WEBPACK_IMPORTED_MODULE_1__[/* default */ "a"])(Tooltip, [{
    key: "componentWillUnmount",
    value: function componentWillUnmount() {
      this.delayedSetIsOver.cancel();
      document.removeEventListener('mouseup', this.cancelIsMouseDown);
    }
  }, {
    key: "emitToChild",
    value: function emitToChild(eventName, event) {
      var children = this.props.children;

      if (_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["Children"].count(children) !== 1) {
        return;
      }

      var child = _wordpress_element__WEBPACK_IMPORTED_MODULE_5__["Children"].only(children);

      if (typeof child.props[eventName] === 'function') {
        child.props[eventName](event);
      }
    }
  }, {
    key: "createToggleIsOver",
    value: function createToggleIsOver(eventName, isDelayed) {
      var _this2 = this;

      return function (event) {
        // Preserve original child callback behavior
        _this2.emitToChild(eventName, event); // Mouse events behave unreliably in React for disabled elements,
        // firing on mouseenter but not mouseleave.  Further, the default
        // behavior for disabled elements in some browsers is to ignore
        // mouse events. Don't bother trying to to handle them.
        //
        // See: https://github.com/facebook/react/issues/4251


        if (event.currentTarget.disabled) {
          return;
        } // A focus event will occur as a result of a mouse click, but it
        // should be disambiguated between interacting with the button and
        // using an explicit focus shift as a cue to display the tooltip.


        if ('focus' === event.type && _this2.isInMouseDown) {
          return;
        } // Needed in case unsetting is over while delayed set pending, i.e.
        // quickly blur/mouseleave before delayedSetIsOver is called


        _this2.delayedSetIsOver.cancel();

        var isOver = Object(lodash__WEBPACK_IMPORTED_MODULE_6__["includes"])(['focus', 'mouseenter'], event.type);

        if (isOver === _this2.state.isOver) {
          return;
        }

        if (isDelayed) {
          _this2.delayedSetIsOver(isOver);
        } else {
          _this2.setState({
            isOver: isOver
          });
        }
      };
    }
    /**
     * Creates an event callback to handle assignment of the `isInMouseDown`
     * instance property in response to a `mousedown` or `mouseup` event.
     *
     * @param {boolean} isMouseDown Whether handler is to be created for the
     *                              `mousedown` event, as opposed to `mouseup`.
     *
     * @return {Function} Event callback handler.
     */

  }, {
    key: "createSetIsMouseDown",
    value: function createSetIsMouseDown(isMouseDown) {
      var _this3 = this;

      return function (event) {
        // Preserve original child callback behavior
        _this3.emitToChild(isMouseDown ? 'onMouseDown' : 'onMouseUp', event); // On mouse down, the next `mouseup` should revert the value of the
        // instance property and remove its own event handler. The bind is
        // made on the document since the `mouseup` might not occur within
        // the bounds of the element.


        document[isMouseDown ? 'addEventListener' : 'removeEventListener']('mouseup', _this3.cancelIsMouseDown);
        _this3.isInMouseDown = isMouseDown;
      };
    }
  }, {
    key: "render",
    value: function render() {
      var _this$props = this.props,
          children = _this$props.children,
          position = _this$props.position,
          text = _this$props.text,
          shortcut = _this$props.shortcut;

      if (_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["Children"].count(children) !== 1) {
        if (false) {}

        return children;
      }

      var child = _wordpress_element__WEBPACK_IMPORTED_MODULE_5__["Children"].only(children);
      var isOver = this.state.isOver;
      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["cloneElement"])(child, {
        onMouseEnter: this.createToggleIsOver('onMouseEnter', true),
        onMouseLeave: this.createToggleIsOver('onMouseLeave'),
        onClick: this.createToggleIsOver('onClick'),
        onFocus: this.createToggleIsOver('onFocus'),
        onBlur: this.createToggleIsOver('onBlur'),
        onMouseDown: this.createSetIsMouseDown(true),
        children: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["concatChildren"])(child.props.children, isOver && Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(_popover__WEBPACK_IMPORTED_MODULE_7__[/* default */ "a"], {
          focusOnMount: false,
          position: position,
          className: "components-tooltip",
          "aria-hidden": "true",
          animate: false
        }, text, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(_shortcut__WEBPACK_IMPORTED_MODULE_8__[/* default */ "a"], {
          className: "components-tooltip__shortcut",
          shortcut: shortcut
        })))
      });
    }
  }]);

  return Tooltip;
}(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["Component"]);

/* harmony default export */ __webpack_exports__["a"] = (Tooltip);
//# sourceMappingURL=index.js.map

/***/ }),

/***/ 111:
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var utils = __webpack_require__(71);
var formats = __webpack_require__(86);
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
    formatter,
    encodeValuesOnly,
    charset
) {
    var obj = object;
    if (typeof filter === 'function') {
        obj = filter(prefix, obj);
    } else if (obj instanceof Date) {
        obj = serializeDate(obj);
    } else if (generateArrayPrefix === 'comma' && isArray(obj)) {
        obj = obj.join(',');
    }

    if (obj === null) {
        if (strictNullHandling) {
            return encoder && !encodeValuesOnly ? encoder(prefix, defaults.encoder, charset, 'key') : prefix;
        }

        obj = '';
    }

    if (isNonNullishPrimitive(obj) || utils.isBuffer(obj)) {
        if (encoder) {
            var keyValue = encodeValuesOnly ? prefix : encoder(prefix, defaults.encoder, charset, 'key');
            return [formatter(keyValue) + '=' + formatter(encoder(obj, defaults.encoder, charset, 'value'))];
        }
        return [formatter(prefix) + '=' + formatter(String(obj))];
    }

    var values = [];

    if (typeof obj === 'undefined') {
        return values;
    }

    var objKeys;
    if (isArray(filter)) {
        objKeys = filter;
    } else {
        var keys = Object.keys(obj);
        objKeys = sort ? keys.sort(sort) : keys;
    }

    for (var i = 0; i < objKeys.length; ++i) {
        var key = objKeys[i];

        if (skipNulls && obj[key] === null) {
            continue;
        }

        if (isArray(obj)) {
            pushToArray(values, stringify(
                obj[key],
                typeof generateArrayPrefix === 'function' ? generateArrayPrefix(prefix, key) : prefix,
                generateArrayPrefix,
                strictNullHandling,
                skipNulls,
                encoder,
                filter,
                sort,
                allowDots,
                serializeDate,
                formatter,
                encodeValuesOnly,
                charset
            ));
        } else {
            pushToArray(values, stringify(
                obj[key],
                prefix + (allowDots ? '.' + key : '[' + key + ']'),
                generateArrayPrefix,
                strictNullHandling,
                skipNulls,
                encoder,
                filter,
                sort,
                allowDots,
                serializeDate,
                formatter,
                encodeValuesOnly,
                charset
            ));
        }
    }

    return values;
};

var normalizeStringifyOptions = function normalizeStringifyOptions(opts) {
    if (!opts) {
        return defaults;
    }

    if (opts.encoder !== null && opts.encoder !== undefined && typeof opts.encoder !== 'function') {
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
            options.formatter,
            options.encodeValuesOnly,
            options.charset
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

/***/ 112:
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var utils = __webpack_require__(71);

var has = Object.prototype.hasOwnProperty;
var isArray = Array.isArray;

var defaults = {
    allowDots: false,
    allowPrototypes: false,
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
            val = maybeMap(
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
            } else {
                obj[cleanRoot] = leaf;
            }
        }

        leaf = obj; // eslint-disable-line no-param-reassign
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

    return utils.compact(obj);
};


/***/ }),

/***/ 12:
/***/ (function(module, exports) {

(function() { module.exports = this["moment"]; }());

/***/ }),

/***/ 121:
/***/ (function(module, exports, __webpack_require__) {

var objectWithoutPropertiesLoose = __webpack_require__(266);

function _objectWithoutProperties(source, excluded) {
  if (source == null) return {};
  var target = objectWithoutPropertiesLoose(source, excluded);
  var key, i;

  if (Object.getOwnPropertySymbols) {
    var sourceSymbolKeys = Object.getOwnPropertySymbols(source);

    for (i = 0; i < sourceSymbolKeys.length; i++) {
      key = sourceSymbolKeys[i];
      if (excluded.indexOf(key) >= 0) continue;
      if (!Object.prototype.propertyIsEnumerable.call(source, key)) continue;
      target[key] = source[key];
    }
  }

  return target;
}

module.exports = _objectWithoutProperties;

/***/ }),

/***/ 13:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "a", function() { return _defineProperty; });
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

/***/ }),

/***/ 137:
/***/ (function(module, exports) {

(function() { module.exports = this["wc"]["currency"]; }());

/***/ }),

/***/ 138:
/***/ (function(module, exports, __webpack_require__) {

"use strict";
/**
 * Copyright (c) 2013-present, Facebook, Inc.
 *
 * This source code is licensed under the MIT license found in the
 * LICENSE file in the root directory of this source tree.
 */



var ReactPropTypesSecret = __webpack_require__(139);

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

/***/ 139:
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

/***/ 14:
/***/ (function(module, exports) {

(function() { module.exports = this["React"]; }());

/***/ }),

/***/ 140:
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var keys = Object.keys;

/**
 * Returns true if the two objects are shallow equal, or false otherwise.
 *
 * @param {import('.').ComparableObject} a First object to compare.
 * @param {import('.').ComparableObject} b Second object to compare.
 *
 * @return {boolean} Whether the two objects are shallow equal.
 */
function isShallowEqualObjects( a, b ) {
	var aKeys, bKeys, i, key, aValue;

	if ( a === b ) {
		return true;
	}

	aKeys = keys( a );
	bKeys = keys( b );

	if ( aKeys.length !== bKeys.length ) {
		return false;
	}

	i = 0;

	while ( i < aKeys.length ) {
		key = aKeys[ i ];
		aValue = a[ key ];

		if (
			// In iterating only the keys of the first object after verifying
			// equal lengths, account for the case that an explicit `undefined`
			// value in the first is implicitly undefined in the second.
			//
			// Example: isShallowEqualObjects( { a: undefined }, { b: 5 } )
			( aValue === undefined && ! b.hasOwnProperty( key ) ) ||
			aValue !== b[ key ]
		) {
			return false;
		}

		i++;
	}

	return true;
}

module.exports = isShallowEqualObjects;


/***/ }),

/***/ 141:
/***/ (function(module, exports, __webpack_require__) {

"use strict";


/**
 * Returns true if the two arrays are shallow equal, or false otherwise.
 *
 * @param {any[]} a First array to compare.
 * @param {any[]} b Second array to compare.
 *
 * @return {boolean} Whether the two arrays are shallow equal.
 */
function isShallowEqualArrays( a, b ) {
	var i;

	if ( a === b ) {
		return true;
	}

	if ( a.length !== b.length ) {
		return false;
	}

	for ( i = 0; i < a.length; i++ ) {
		if ( a[ i ] !== b[ i ] ) {
			return false;
		}
	}

	return true;
}

module.exports = isShallowEqualArrays;


/***/ }),

/***/ 142:
/***/ (function(module, exports, __webpack_require__) {

"use strict";
/**
 * Copyright (c) 2015-present, Facebook, Inc.
 *
 * This source code is licensed under the MIT license found in the
 * LICENSE file in the root directory of this source tree.
 */



var React = __webpack_require__(14);

var REACT_ELEMENT_TYPE =
  (typeof Symbol === 'function' && Symbol.for && Symbol.for('react.element')) ||
  0xeac7;

var emptyFunction = __webpack_require__(106);
var invariant = __webpack_require__(143);
var warning = __webpack_require__(144);

var SEPARATOR = '.';
var SUBSEPARATOR = ':';

var didWarnAboutMaps = false;

var ITERATOR_SYMBOL = typeof Symbol === 'function' && Symbol.iterator;
var FAUX_ITERATOR_SYMBOL = '@@iterator'; // Before Symbol spec.

function getIteratorFn(maybeIterable) {
  var iteratorFn =
    maybeIterable &&
    ((ITERATOR_SYMBOL && maybeIterable[ITERATOR_SYMBOL]) ||
      maybeIterable[FAUX_ITERATOR_SYMBOL]);
  if (typeof iteratorFn === 'function') {
    return iteratorFn;
  }
}

function escape(key) {
  var escapeRegex = /[=:]/g;
  var escaperLookup = {
    '=': '=0',
    ':': '=2'
  };
  var escapedString = ('' + key).replace(escapeRegex, function(match) {
    return escaperLookup[match];
  });

  return '$' + escapedString;
}

function getComponentKey(component, index) {
  // Do some typechecking here since we call this blindly. We want to ensure
  // that we don't block potential future ES APIs.
  if (component && typeof component === 'object' && component.key != null) {
    // Explicit key
    return escape(component.key);
  }
  // Implicit key determined by the index in the set
  return index.toString(36);
}

function traverseAllChildrenImpl(
  children,
  nameSoFar,
  callback,
  traverseContext
) {
  var type = typeof children;

  if (type === 'undefined' || type === 'boolean') {
    // All of the above are perceived as null.
    children = null;
  }

  if (
    children === null ||
    type === 'string' ||
    type === 'number' ||
    // The following is inlined from ReactElement. This means we can optimize
    // some checks. React Fiber also inlines this logic for similar purposes.
    (type === 'object' && children.$$typeof === REACT_ELEMENT_TYPE)
  ) {
    callback(
      traverseContext,
      children,
      // If it's the only child, treat the name as if it was wrapped in an array
      // so that it's consistent if the number of children grows.
      nameSoFar === '' ? SEPARATOR + getComponentKey(children, 0) : nameSoFar
    );
    return 1;
  }

  var child;
  var nextName;
  var subtreeCount = 0; // Count of children found in the current subtree.
  var nextNamePrefix = nameSoFar === '' ? SEPARATOR : nameSoFar + SUBSEPARATOR;

  if (Array.isArray(children)) {
    for (var i = 0; i < children.length; i++) {
      child = children[i];
      nextName = nextNamePrefix + getComponentKey(child, i);
      subtreeCount += traverseAllChildrenImpl(
        child,
        nextName,
        callback,
        traverseContext
      );
    }
  } else {
    var iteratorFn = getIteratorFn(children);
    if (iteratorFn) {
      if (false) {}

      var iterator = iteratorFn.call(children);
      var step;
      var ii = 0;
      while (!(step = iterator.next()).done) {
        child = step.value;
        nextName = nextNamePrefix + getComponentKey(child, ii++);
        subtreeCount += traverseAllChildrenImpl(
          child,
          nextName,
          callback,
          traverseContext
        );
      }
    } else if (type === 'object') {
      var addendum = '';
      if (false) {}
      var childrenString = '' + children;
      invariant(
        false,
        'Objects are not valid as a React child (found: %s).%s',
        childrenString === '[object Object]'
          ? 'object with keys {' + Object.keys(children).join(', ') + '}'
          : childrenString,
        addendum
      );
    }
  }

  return subtreeCount;
}

function traverseAllChildren(children, callback, traverseContext) {
  if (children == null) {
    return 0;
  }

  return traverseAllChildrenImpl(children, '', callback, traverseContext);
}

var userProvidedKeyEscapeRegex = /\/+/g;
function escapeUserProvidedKey(text) {
  return ('' + text).replace(userProvidedKeyEscapeRegex, '$&/');
}

function cloneAndReplaceKey(oldElement, newKey) {
  return React.cloneElement(
    oldElement,
    {key: newKey},
    oldElement.props !== undefined ? oldElement.props.children : undefined
  );
}

var DEFAULT_POOL_SIZE = 10;
var DEFAULT_POOLER = oneArgumentPooler;

var oneArgumentPooler = function(copyFieldsFrom) {
  var Klass = this;
  if (Klass.instancePool.length) {
    var instance = Klass.instancePool.pop();
    Klass.call(instance, copyFieldsFrom);
    return instance;
  } else {
    return new Klass(copyFieldsFrom);
  }
};

var addPoolingTo = function addPoolingTo(CopyConstructor, pooler) {
  // Casting as any so that flow ignores the actual implementation and trusts
  // it to match the type we declared
  var NewKlass = CopyConstructor;
  NewKlass.instancePool = [];
  NewKlass.getPooled = pooler || DEFAULT_POOLER;
  if (!NewKlass.poolSize) {
    NewKlass.poolSize = DEFAULT_POOL_SIZE;
  }
  NewKlass.release = standardReleaser;
  return NewKlass;
};

var standardReleaser = function standardReleaser(instance) {
  var Klass = this;
  invariant(
    instance instanceof Klass,
    'Trying to release an instance into a pool of a different type.'
  );
  instance.destructor();
  if (Klass.instancePool.length < Klass.poolSize) {
    Klass.instancePool.push(instance);
  }
};

var fourArgumentPooler = function fourArgumentPooler(a1, a2, a3, a4) {
  var Klass = this;
  if (Klass.instancePool.length) {
    var instance = Klass.instancePool.pop();
    Klass.call(instance, a1, a2, a3, a4);
    return instance;
  } else {
    return new Klass(a1, a2, a3, a4);
  }
};

function MapBookKeeping(mapResult, keyPrefix, mapFunction, mapContext) {
  this.result = mapResult;
  this.keyPrefix = keyPrefix;
  this.func = mapFunction;
  this.context = mapContext;
  this.count = 0;
}
MapBookKeeping.prototype.destructor = function() {
  this.result = null;
  this.keyPrefix = null;
  this.func = null;
  this.context = null;
  this.count = 0;
};
addPoolingTo(MapBookKeeping, fourArgumentPooler);

function mapSingleChildIntoContext(bookKeeping, child, childKey) {
  var result = bookKeeping.result;
  var keyPrefix = bookKeeping.keyPrefix;
  var func = bookKeeping.func;
  var context = bookKeeping.context;

  var mappedChild = func.call(context, child, bookKeeping.count++);
  if (Array.isArray(mappedChild)) {
    mapIntoWithKeyPrefixInternal(
      mappedChild,
      result,
      childKey,
      emptyFunction.thatReturnsArgument
    );
  } else if (mappedChild != null) {
    if (React.isValidElement(mappedChild)) {
      mappedChild = cloneAndReplaceKey(
        mappedChild,
        // Keep both the (mapped) and old keys if they differ, just as
        // traverseAllChildren used to do for objects as children
        keyPrefix +
          (mappedChild.key && (!child || child.key !== mappedChild.key)
            ? escapeUserProvidedKey(mappedChild.key) + '/'
            : '') +
          childKey
      );
    }
    result.push(mappedChild);
  }
}

function mapIntoWithKeyPrefixInternal(children, array, prefix, func, context) {
  var escapedPrefix = '';
  if (prefix != null) {
    escapedPrefix = escapeUserProvidedKey(prefix) + '/';
  }
  var traverseContext = MapBookKeeping.getPooled(
    array,
    escapedPrefix,
    func,
    context
  );
  traverseAllChildren(children, mapSingleChildIntoContext, traverseContext);
  MapBookKeeping.release(traverseContext);
}

var numericPropertyRegex = /^\d+$/;

var warnedAboutNumeric = false;

function createReactFragment(object) {
  if (typeof object !== 'object' || !object || Array.isArray(object)) {
    warning(
      false,
      'React.addons.createFragment only accepts a single object. Got: %s',
      object
    );
    return object;
  }
  if (React.isValidElement(object)) {
    warning(
      false,
      'React.addons.createFragment does not accept a ReactElement ' +
        'without a wrapper object.'
    );
    return object;
  }

  invariant(
    object.nodeType !== 1,
    'React.addons.createFragment(...): Encountered an invalid child; DOM ' +
      'elements are not valid children of React components.'
  );

  var result = [];

  for (var key in object) {
    if (false) {}
    mapIntoWithKeyPrefixInternal(
      object[key],
      result,
      key,
      emptyFunction.thatReturnsArgument
    );
  }

  return result;
}

module.exports = createReactFragment;


/***/ }),

/***/ 143:
/***/ (function(module, exports, __webpack_require__) {

"use strict";
/**
 * Copyright (c) 2013-present, Facebook, Inc.
 *
 * This source code is licensed under the MIT license found in the
 * LICENSE file in the root directory of this source tree.
 *
 */



/**
 * Use invariant() to assert state which your program assumes to be true.
 *
 * Provide sprintf-style format (only %s is supported) and arguments
 * to provide information about what broke and what you were
 * expecting.
 *
 * The invariant message will be stripped in production, but the invariant
 * will remain to ensure logic does not differ in production.
 */

var validateFormat = function validateFormat(format) {};

if (false) {}

function invariant(condition, format, a, b, c, d, e, f) {
  validateFormat(format);

  if (!condition) {
    var error;
    if (format === undefined) {
      error = new Error('Minified exception occurred; use the non-minified dev environment ' + 'for the full error message and additional helpful warnings.');
    } else {
      var args = [a, b, c, d, e, f];
      var argIndex = 0;
      error = new Error(format.replace(/%s/g, function () {
        return args[argIndex++];
      }));
      error.name = 'Invariant Violation';
    }

    error.framesToPop = 1; // we don't care about invariant's own frame
    throw error;
  }
}

module.exports = invariant;

/***/ }),

/***/ 144:
/***/ (function(module, exports, __webpack_require__) {

"use strict";
/**
 * Copyright (c) 2014-present, Facebook, Inc.
 *
 * This source code is licensed under the MIT license found in the
 * LICENSE file in the root directory of this source tree.
 *
 */



var emptyFunction = __webpack_require__(106);

/**
 * Similar to invariant but only logs a warning if the condition is not met.
 * This can be used to log issues in development environments in critical
 * paths. Removing the logging code for production environments will keep the
 * same logic and follow the same code paths.
 */

var warning = emptyFunction;

if (false) { var printWarning; }

module.exports = warning;

/***/ }),

/***/ 145:
/***/ (function(module, exports, __webpack_require__) {

"use strict";


function identifyToken(item) {
	// {{/example}}
	if (item.match(/^\{\{\//)) {
		return {
			type: 'componentClose',
			value: item.replace(/\W/g, '')
		};
	}
	// {{example /}}
	if (item.match(/\/\}\}$/)) {
		return {
			type: 'componentSelfClosing',
			value: item.replace(/\W/g, '')
		};
	}
	// {{example}}
	if (item.match(/^\{\{/)) {
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

module.exports = function (mixedString) {
	var tokenStrings = mixedString.split(/(\{\{\/?\s*\w+\s*\/?\}\})/g); // split to components and strings
	return tokenStrings.map(identifyToken);
};
//# sourceMappingURL=tokenize.js.map

/***/ }),

/***/ 146:
/***/ (function(module, exports, __webpack_require__) {

"use strict";


/**
 * This is the common logic for both the Node.js and web browser
 * implementations of `debug()`.
 */
function setup(env) {
  createDebug.debug = createDebug;
  createDebug.default = createDebug;
  createDebug.coerce = coerce;
  createDebug.disable = disable;
  createDebug.enable = enable;
  createDebug.enabled = enabled;
  createDebug.humanize = __webpack_require__(147);
  Object.keys(env).forEach(function (key) {
    createDebug[key] = env[key];
  });
  /**
  * Active `debug` instances.
  */

  createDebug.instances = [];
  /**
  * The currently active debug mode names, and names to skip.
  */

  createDebug.names = [];
  createDebug.skips = [];
  /**
  * Map of special "%n" handling functions, for the debug "format" argument.
  *
  * Valid key names are a single, lower or upper-case letter, i.e. "n" and "N".
  */

  createDebug.formatters = {};
  /**
  * Selects a color for a debug namespace
  * @param {String} namespace The namespace string for the for the debug instance to be colored
  * @return {Number|String} An ANSI color code for the given namespace
  * @api private
  */

  function selectColor(namespace) {
    var hash = 0;

    for (var i = 0; i < namespace.length; i++) {
      hash = (hash << 5) - hash + namespace.charCodeAt(i);
      hash |= 0; // Convert to 32bit integer
    }

    return createDebug.colors[Math.abs(hash) % createDebug.colors.length];
  }

  createDebug.selectColor = selectColor;
  /**
  * Create a debugger with the given `namespace`.
  *
  * @param {String} namespace
  * @return {Function}
  * @api public
  */

  function createDebug(namespace) {
    var prevTime;

    function debug() {
      for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
        args[_key] = arguments[_key];
      }

      // Disabled?
      if (!debug.enabled) {
        return;
      }

      var self = debug; // Set `diff` timestamp

      var curr = Number(new Date());
      var ms = curr - (prevTime || curr);
      self.diff = ms;
      self.prev = prevTime;
      self.curr = curr;
      prevTime = curr;
      args[0] = createDebug.coerce(args[0]);

      if (typeof args[0] !== 'string') {
        // Anything else let's inspect with %O
        args.unshift('%O');
      } // Apply any `formatters` transformations


      var index = 0;
      args[0] = args[0].replace(/%([a-zA-Z%])/g, function (match, format) {
        // If we encounter an escaped % then don't increase the array index
        if (match === '%%') {
          return match;
        }

        index++;
        var formatter = createDebug.formatters[format];

        if (typeof formatter === 'function') {
          var val = args[index];
          match = formatter.call(self, val); // Now we need to remove `args[index]` since it's inlined in the `format`

          args.splice(index, 1);
          index--;
        }

        return match;
      }); // Apply env-specific formatting (colors, etc.)

      createDebug.formatArgs.call(self, args);
      var logFn = self.log || createDebug.log;
      logFn.apply(self, args);
    }

    debug.namespace = namespace;
    debug.enabled = createDebug.enabled(namespace);
    debug.useColors = createDebug.useColors();
    debug.color = selectColor(namespace);
    debug.destroy = destroy;
    debug.extend = extend; // Debug.formatArgs = formatArgs;
    // debug.rawLog = rawLog;
    // env-specific initialization logic for debug instances

    if (typeof createDebug.init === 'function') {
      createDebug.init(debug);
    }

    createDebug.instances.push(debug);
    return debug;
  }

  function destroy() {
    var index = createDebug.instances.indexOf(this);

    if (index !== -1) {
      createDebug.instances.splice(index, 1);
      return true;
    }

    return false;
  }

  function extend(namespace, delimiter) {
    var newDebug = createDebug(this.namespace + (typeof delimiter === 'undefined' ? ':' : delimiter) + namespace);
    newDebug.log = this.log;
    return newDebug;
  }
  /**
  * Enables a debug mode by namespaces. This can include modes
  * separated by a colon and wildcards.
  *
  * @param {String} namespaces
  * @api public
  */


  function enable(namespaces) {
    createDebug.save(namespaces);
    createDebug.names = [];
    createDebug.skips = [];
    var i;
    var split = (typeof namespaces === 'string' ? namespaces : '').split(/[\s,]+/);
    var len = split.length;

    for (i = 0; i < len; i++) {
      if (!split[i]) {
        // ignore empty strings
        continue;
      }

      namespaces = split[i].replace(/\*/g, '.*?');

      if (namespaces[0] === '-') {
        createDebug.skips.push(new RegExp('^' + namespaces.substr(1) + '$'));
      } else {
        createDebug.names.push(new RegExp('^' + namespaces + '$'));
      }
    }

    for (i = 0; i < createDebug.instances.length; i++) {
      var instance = createDebug.instances[i];
      instance.enabled = createDebug.enabled(instance.namespace);
    }
  }
  /**
  * Disable debug output.
  *
  * @return {String} namespaces
  * @api public
  */


  function disable() {
    var namespaces = [].concat(createDebug.names.map(toNamespace), createDebug.skips.map(toNamespace).map(function (namespace) {
      return '-' + namespace;
    })).join(',');
    createDebug.enable('');
    return namespaces;
  }
  /**
  * Returns true if the given mode name is enabled, false otherwise.
  *
  * @param {String} name
  * @return {Boolean}
  * @api public
  */


  function enabled(name) {
    if (name[name.length - 1] === '*') {
      return true;
    }

    var i;
    var len;

    for (i = 0, len = createDebug.skips.length; i < len; i++) {
      if (createDebug.skips[i].test(name)) {
        return false;
      }
    }

    for (i = 0, len = createDebug.names.length; i < len; i++) {
      if (createDebug.names[i].test(name)) {
        return true;
      }
    }

    return false;
  }
  /**
  * Convert regexp to namespace
  *
  * @param {RegExp} regxep
  * @return {String} namespace
  * @api private
  */


  function toNamespace(regexp) {
    return regexp.toString().substring(2, regexp.toString().length - 2).replace(/\.\*\?$/, '*');
  }
  /**
  * Coerce `val`.
  *
  * @param {Mixed} val
  * @return {Mixed}
  * @api private
  */


  function coerce(val) {
    if (val instanceof Error) {
      return val.stack || val.message;
    }

    return val;
  }

  createDebug.enable(createDebug.load());
  return createDebug;
}

module.exports = setup;

/***/ }),

/***/ 147:
/***/ (function(module, exports) {

/**
 * Helpers.
 */

var s = 1000;
var m = s * 60;
var h = m * 60;
var d = h * 24;
var w = d * 7;
var y = d * 365.25;

/**
 * Parse or format the given `val`.
 *
 * Options:
 *
 *  - `long` verbose formatting [false]
 *
 * @param {String|Number} val
 * @param {Object} [options]
 * @throws {Error} throw an error if val is not a non-empty string or a number
 * @return {String|Number}
 * @api public
 */

module.exports = function(val, options) {
  options = options || {};
  var type = typeof val;
  if (type === 'string' && val.length > 0) {
    return parse(val);
  } else if (type === 'number' && isFinite(val)) {
    return options.long ? fmtLong(val) : fmtShort(val);
  }
  throw new Error(
    'val is not a non-empty string or a valid number. val=' +
      JSON.stringify(val)
  );
};

/**
 * Parse the given `str` and return milliseconds.
 *
 * @param {String} str
 * @return {Number}
 * @api private
 */

function parse(str) {
  str = String(str);
  if (str.length > 100) {
    return;
  }
  var match = /^(-?(?:\d+)?\.?\d+) *(milliseconds?|msecs?|ms|seconds?|secs?|s|minutes?|mins?|m|hours?|hrs?|h|days?|d|weeks?|w|years?|yrs?|y)?$/i.exec(
    str
  );
  if (!match) {
    return;
  }
  var n = parseFloat(match[1]);
  var type = (match[2] || 'ms').toLowerCase();
  switch (type) {
    case 'years':
    case 'year':
    case 'yrs':
    case 'yr':
    case 'y':
      return n * y;
    case 'weeks':
    case 'week':
    case 'w':
      return n * w;
    case 'days':
    case 'day':
    case 'd':
      return n * d;
    case 'hours':
    case 'hour':
    case 'hrs':
    case 'hr':
    case 'h':
      return n * h;
    case 'minutes':
    case 'minute':
    case 'mins':
    case 'min':
    case 'm':
      return n * m;
    case 'seconds':
    case 'second':
    case 'secs':
    case 'sec':
    case 's':
      return n * s;
    case 'milliseconds':
    case 'millisecond':
    case 'msecs':
    case 'msec':
    case 'ms':
      return n;
    default:
      return undefined;
  }
}

/**
 * Short format for `ms`.
 *
 * @param {Number} ms
 * @return {String}
 * @api private
 */

function fmtShort(ms) {
  var msAbs = Math.abs(ms);
  if (msAbs >= d) {
    return Math.round(ms / d) + 'd';
  }
  if (msAbs >= h) {
    return Math.round(ms / h) + 'h';
  }
  if (msAbs >= m) {
    return Math.round(ms / m) + 'm';
  }
  if (msAbs >= s) {
    return Math.round(ms / s) + 's';
  }
  return ms + 'ms';
}

/**
 * Long format for `ms`.
 *
 * @param {Number} ms
 * @return {String}
 * @api private
 */

function fmtLong(ms) {
  var msAbs = Math.abs(ms);
  if (msAbs >= d) {
    return plural(ms, msAbs, d, 'day');
  }
  if (msAbs >= h) {
    return plural(ms, msAbs, h, 'hour');
  }
  if (msAbs >= m) {
    return plural(ms, msAbs, m, 'minute');
  }
  if (msAbs >= s) {
    return plural(ms, msAbs, s, 'second');
  }
  return ms + ' ms';
}

/**
 * Pluralization helper.
 */

function plural(ms, msAbs, n, name) {
  var isPlural = msAbs >= n * 1.5;
  return Math.round(ms / n) + ' ' + name + (isPlural ? 's' : '');
}


/***/ }),

/***/ 15:
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

module.exports = _defineProperty;

/***/ }),

/***/ 16:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "a", function() { return _objectWithoutProperties; });
/* harmony import */ var _objectWithoutPropertiesLoose__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(54);

function _objectWithoutProperties(source, excluded) {
  if (source == null) return {};
  var target = Object(_objectWithoutPropertiesLoose__WEBPACK_IMPORTED_MODULE_0__[/* default */ "a"])(source, excluded);
  var key, i;

  if (Object.getOwnPropertySymbols) {
    var sourceSymbolKeys = Object.getOwnPropertySymbols(source);

    for (i = 0; i < sourceSymbolKeys.length; i++) {
      key = sourceSymbolKeys[i];
      if (excluded.indexOf(key) >= 0) continue;
      if (!Object.prototype.propertyIsEnumerable.call(source, key)) continue;
      target[key] = source[key];
    }
  }

  return target;
}

/***/ }),

/***/ 169:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";

// EXPORTS
__webpack_require__.d(__webpack_exports__, "a", function() { return /* binding */ build_module_speak; });

// UNUSED EXPORTS: setup

// EXTERNAL MODULE: ./node_modules/@wordpress/dom-ready/build-module/index.js
var build_module = __webpack_require__(65);

// CONCATENATED MODULE: ./node_modules/@wordpress/a11y/build-module/addContainer.js
/**
 * Build the live regions markup.
 *
 * @param {string} ariaLive Optional. Value for the 'aria-live' attribute, default 'polite'.
 *
 * @return {HTMLDivElement} The ARIA live region HTML element.
 */
var addContainer = function addContainer(ariaLive) {
  ariaLive = ariaLive || 'polite';
  var container = document.createElement('div');
  container.id = 'a11y-speak-' + ariaLive;
  container.className = 'a11y-speak-region';
  container.setAttribute('style', 'position: absolute;' + 'margin: -1px;' + 'padding: 0;' + 'height: 1px;' + 'width: 1px;' + 'overflow: hidden;' + 'clip: rect(1px, 1px, 1px, 1px);' + '-webkit-clip-path: inset(50%);' + 'clip-path: inset(50%);' + 'border: 0;' + 'word-wrap: normal !important;');
  container.setAttribute('aria-live', ariaLive);
  container.setAttribute('aria-relevant', 'additions text');
  container.setAttribute('aria-atomic', 'true');
  var body = document.querySelector('body');

  if (body) {
    body.appendChild(container);
  }

  return container;
};

/* harmony default export */ var build_module_addContainer = (addContainer);
//# sourceMappingURL=addContainer.js.map
// CONCATENATED MODULE: ./node_modules/@wordpress/a11y/build-module/clear.js
/**
 * Clear the a11y-speak-region elements.
 */
var clear = function clear() {
  var regions = document.querySelectorAll('.a11y-speak-region');

  for (var i = 0; i < regions.length; i++) {
    regions[i].textContent = '';
  }
};

/* harmony default export */ var build_module_clear = (clear);
//# sourceMappingURL=clear.js.map
// CONCATENATED MODULE: ./node_modules/@wordpress/a11y/build-module/filterMessage.js
var previousMessage = '';
/**
 * Filter the message to be announced to the screenreader.
 *
 * @param {string} message The message to be announced.
 *
 * @return {string} The filtered message.
 */

var filterMessage = function filterMessage(message) {
  /*
   * Strip HTML tags (if any) from the message string. Ideally, messages should
   * be simple strings, carefully crafted for specific use with A11ySpeak.
   * When re-using already existing strings this will ensure simple HTML to be
   * stripped out and replaced with a space. Browsers will collapse multiple
   * spaces natively.
   */
  message = message.replace(/<[^<>]+>/g, ' ');

  if (previousMessage === message) {
    message += "\xA0";
  }

  previousMessage = message;
  return message;
};

/* harmony default export */ var build_module_filterMessage = (filterMessage);
//# sourceMappingURL=filterMessage.js.map
// CONCATENATED MODULE: ./node_modules/@wordpress/a11y/build-module/index.js
/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */




/**
 * Create the live regions.
 */

var build_module_setup = function setup() {
  var containerPolite = document.getElementById('a11y-speak-polite');
  var containerAssertive = document.getElementById('a11y-speak-assertive');

  if (containerPolite === null) {
    build_module_addContainer('polite');
  }

  if (containerAssertive === null) {
    build_module_addContainer('assertive');
  }
};
/**
 * Run setup on domReady.
 */

Object(build_module["a" /* default */])(build_module_setup);
/**
 * Allows you to easily announce dynamic interface updates to screen readers using ARIA live regions.
 * This module is inspired by the `speak` function in wp-a11y.js
 *
 * @param {string} message  The message to be announced by Assistive Technologies.
 * @param {string} ariaLive Optional. The politeness level for aria-live. Possible values:
 *                          polite or assertive. Default polite.
 *
 * @example
 * ```js
 * import { speak } from '@wordpress/a11y';
 *
 * // For polite messages that shouldn't interrupt what screen readers are currently announcing.
 * speak( 'The message you want to send to the ARIA live region' );
 *
 * // For assertive messages that should interrupt what screen readers are currently announcing.
 * speak( 'The message you want to send to the ARIA live region', 'assertive' );
 * ```
 */

var build_module_speak = function speak(message, ariaLive) {
  // Clear previous messages to allow repeated strings being read out.
  build_module_clear();
  message = build_module_filterMessage(message);
  var containerPolite = document.getElementById('a11y-speak-polite');
  var containerAssertive = document.getElementById('a11y-speak-assertive');

  if (containerAssertive && 'assertive' === ariaLive) {
    containerAssertive.textContent = message;
  } else if (containerPolite) {
    containerPolite.textContent = message;
  }
};
//# sourceMappingURL=index.js.map

/***/ }),

/***/ 17:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";

// EXPORTS
__webpack_require__.d(__webpack_exports__, "a", function() { return /* binding */ _toConsumableArray; });

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/esm/arrayLikeToArray.js
var arrayLikeToArray = __webpack_require__(37);

// CONCATENATED MODULE: ./node_modules/@babel/runtime/helpers/esm/arrayWithoutHoles.js

function _arrayWithoutHoles(arr) {
  if (Array.isArray(arr)) return Object(arrayLikeToArray["a" /* default */])(arr);
}
// CONCATENATED MODULE: ./node_modules/@babel/runtime/helpers/esm/iterableToArray.js
function _iterableToArray(iter) {
  if (typeof Symbol !== "undefined" && Symbol.iterator in Object(iter)) return Array.from(iter);
}
// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/esm/unsupportedIterableToArray.js
var unsupportedIterableToArray = __webpack_require__(52);

// CONCATENATED MODULE: ./node_modules/@babel/runtime/helpers/esm/nonIterableSpread.js
function _nonIterableSpread() {
  throw new TypeError("Invalid attempt to spread non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method.");
}
// CONCATENATED MODULE: ./node_modules/@babel/runtime/helpers/esm/toConsumableArray.js




function _toConsumableArray(arr) {
  return _arrayWithoutHoles(arr) || _iterableToArray(arr) || Object(unsupportedIterableToArray["a" /* default */])(arr) || _nonIterableSpread();
}

/***/ }),

/***/ 170:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";

// EXPORTS
__webpack_require__.d(__webpack_exports__, "a", function() { return /* reexport */ client_ApiClient; });
__webpack_require__.d(__webpack_exports__, "b", function() { return /* reexport */ MINUTE; });
__webpack_require__.d(__webpack_exports__, "c", function() { return /* reexport */ SECOND; });

// UNUSED EXPORTS: HOUR

// EXTERNAL MODULE: ./node_modules/@babel/runtime/regenerator/index.js
var regenerator = __webpack_require__(73);
var regenerator_default = /*#__PURE__*/__webpack_require__.n(regenerator);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/esm/asyncToGenerator.js
var asyncToGenerator = __webpack_require__(70);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/esm/extends.js
var esm_extends = __webpack_require__(11);

// EXTERNAL MODULE: ./node_modules/debug/src/browser.js
var browser = __webpack_require__(98);
var browser_default = /*#__PURE__*/__webpack_require__.n(browser);

// EXTERNAL MODULE: external "lodash"
var external_lodash_ = __webpack_require__(2);

// CONCATENATED MODULE: ./node_modules/@fresh-data/framework/es/client/calculate-updates.js
var DEFAULT_MAX_UPDATE = 30000;
var DEFAULT_MIN_UPDATE = 500;
/**
 * Compares requirements against current state for update information.
 * Takes a list of requirements and the current state, both keyed by resourceName,
 * and returns update information which contains an array of resourceNames that are
 * currently needed and when the next update cycle should run, in milleseconds.
 * @param {Object} requirementsByResource List of requirements keyed by resourceName.
 * @param {Object} resourceState State indexed by resourceName.
 * @param {number} [minUpdate] Minimum nextUpdate value.
 * @param {number} [maxUpdate] Maximum nextUpdate value.
 * @param {Date} [now] Current time (used for tests).
 * @return {Object} updateInfo: { nextUpdate, updates }
 * @see combineComponentRequirements
 */

function calculateUpdates(requirementsByResource, resourceState, minUpdate, maxUpdate, now) {
  if (minUpdate === void 0) {
    minUpdate = DEFAULT_MIN_UPDATE;
  }

  if (maxUpdate === void 0) {
    maxUpdate = DEFAULT_MAX_UPDATE;
  }

  if (now === void 0) {
    now = new Date();
  }

  var updateInfo = {
    updates: [],
    nextUpdate: maxUpdate
  };
  appendUpdatesForResources(updateInfo, requirementsByResource, resourceState, now);
  updateInfo.nextUpdate = Math.max(updateInfo.nextUpdate, minUpdate);
  return updateInfo;
}
/**
 * Iterates resources to analyze needed updates.
 * @param {Object} updateInfo Update information to be mutated by this function.
 * @param {Object} requirementsByResource List of requirements keyed by resource.
 * @param {Object} resourceState State indexed by resourceName.
 * @param {Date} [now] Current time (used for tests).
 * @see calculateUpdates
 * @see appendUpdatesForResource
 */

function appendUpdatesForResources(updateInfo, requirementsByResource, resourceState, now) {
  Object.keys(requirementsByResource).forEach(function (resourceName) {
    var requirements = requirementsByResource[resourceName];
    var state = resourceState[resourceName] || {};
    appendUpdatesForResource(updateInfo, resourceName, requirements, state, now);
  });
}
/**
 * Analyzes a resource's requirements against its current state..
 * @param {Object} updateInfo Update information to be mutated by this function.
 * @param {string} resourceName Name of the resource to be analyzed.
 * @param {Object} requirements The requirements for this level of the tree.
 * @param {Object} state The current state for this resource.
 * @param {Date} [now] Current time (used for tests).
 * @see appendUpdatesForResources
 */


function appendUpdatesForResource(updateInfo, resourceName, requirements, state, now) {
  var lastRequested = state.lastRequested,
      lastReceived = state.lastReceived;
  var isRequested = lastRequested && (!lastReceived || lastRequested > lastReceived);
  var timeoutLeft = getTimeoutLeft(requirements.timeout, state, now);
  var freshnessLeft = getFreshnessLeft(requirements.freshness, state, now);
  var nextUpdate = isRequested && 0 >= freshnessLeft ? timeoutLeft : freshnessLeft;
  updateInfo.nextUpdate = Math.min(updateInfo.nextUpdate, nextUpdate);

  if (nextUpdate < 0) {
    updateInfo.updates.push(resourceName);
  }
}
/**
 * Calculates the remaining time left until a timeout is reached.
 * @param {Object} timeout The timeout requirements in milliseconds.
 * @param {Object} state The matching state for the resource.
 * @param {Date} now Current time (used for tests).
 * @return {number} Time left until timeout, or MAX_SAFE_INTEGER if not applicable.
 */


function getTimeoutLeft(timeout, state, now) {
  var lastRequested = state.lastRequested || Number.MIN_SAFE_INTEGER;
  var lastReceived = state.lastReceived || Number.MIN_SAFE_INTEGER;

  if (timeout && lastRequested && lastRequested > lastReceived) {
    return timeout - (now - lastRequested);
  }

  return Number.MAX_SAFE_INTEGER;
}
/**
 * Calculates the time remaining until this data is considered stale.
 * @param {Object} freshness The freshness requirements in milliseconds.
 * @param {Object} state The matching state for the resource.
 * @param {Date} now Current time (used for tests).
 * @return {number} Time left until stale, or MAX_SAFE_INTEGER if not applicable.
 */

function getFreshnessLeft(freshness, state, now) {
  var lastReceived = state.lastReceived;

  if (freshness && lastReceived) {
    return freshness - (now - lastReceived);
  }

  return freshness ? Number.MIN_SAFE_INTEGER : Number.MAX_SAFE_INTEGER;
}
// CONCATENATED MODULE: ./node_modules/@fresh-data/framework/es/devinfo/components.js
/**
 * Generates information about components that require resources.
 * @param {Object} client The client to inspect.
 * @return {Array} An array of objects that describe components and their requirements.
 */
function components_components(client) {
  var componentSummaries = [];
  client.requirementsByComponent.forEach(function (requirements, component) {
    componentSummaries.push({
      component: component,
      requirements: requirements
    });
  });
  return componentSummaries;
}
// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/esm/objectWithoutPropertiesLoose.js
var objectWithoutPropertiesLoose = __webpack_require__(54);

// CONCATENATED MODULE: ./node_modules/@fresh-data/framework/es/devinfo/resources.js




/**
 * Possible statuses of a resource.
 */

var STATUS = {
  overdue: 'overdue',
  fetching: 'fetching',
  stale: 'stale',
  fresh: 'fresh',
  notRequired: 'notRequired'
};
/**
 * Compiles information about the resources available from a fresh-data client.
 * @param {Object} client A fresh-data client to be inspected.
 * @return {Object} A list of detailed resource info objects, keyed by resource name.
 */

function resources_resources(client) {
  var resourceState = client.state.resources || {};
  var requirements = client.requirementsByResource;
  var resourceNames = Object(external_lodash_["union"])(Object.keys(resourceState), Object.keys(requirements));
  return resourceNames.reduce(function (resourceInfo, resourceName) {
    var resource = resourceState[resourceName] || {};
    var data = resource.data;
    var status = getStatus(resource, requirements[resourceName]);
    var summary = getSummary(status, resource, requirements[resourceName]);
    resourceInfo[resourceName] = {
      status: status,
      summary: summary,
      data: data
    };

    if (requirements[resourceName]) {
      var combinedRequirement = convertRequirement(requirements[resourceName]);
      var componentsRequiring = getComponentsForResource(client.requirementsByComponent, resourceName);
      resourceInfo[resourceName].combinedRequirement = combinedRequirement;
      resourceInfo[resourceName].componentsRequiring = componentsRequiring;
    }

    return resourceInfo;
  }, {});
}

function getStatus(resource, requirement) {
  if (!requirement) {
    return STATUS.notRequired;
  }

  var freshness = requirement.freshness,
      timeout = requirement.timeout;
  var now = new Date();
  var freshnessLeft = getFreshnessLeft(freshness, resource, now);

  if (resource && resource.lastRequested > resource.lastReceived) {
    var timeoutLeft = getTimeoutLeft(timeout, resource, now);

    if (timeoutLeft < 0) {
      return STATUS.overdue;
    }

    return STATUS.fetching;
  }

  if (freshnessLeft < 0) {
    return STATUS.stale;
  }

  return STATUS.fresh;
}

function getSummary(status, resource, requirement) {
  var now = new Date();

  switch (status) {
    case STATUS.overdue:
      var timeout = getTimeoutLeft(requirement.timeout, resource, now);
      return "Timed out for " + millisToString(-timeout);

    case STATUS.fetching:
      var timeoutLeft = getTimeoutLeft(requirement.timeout, resource, now);
      return millisToString(timeoutLeft) + " until timeout";

    case STATUS.stale:
      var staleness = getFreshnessLeft(requirement.freshness, resource, now);
      return "Stale for " + millisToString(-staleness);

    case STATUS.fresh:
      var freshnessLeft = getFreshnessLeft(requirement.freshness, resource, now);
      return "Fresh for " + millisToString(freshnessLeft);

    case STATUS.notRequired:
    default:
      return 'Resource is not fetched directly.';
  }
}

function convertRequirement(requirement) {
  if (requirement === void 0) {
    requirement = {};
  }

  var _requirement = requirement,
      freshness = _requirement.freshness,
      timeout = _requirement.timeout,
      other = Object(objectWithoutPropertiesLoose["a" /* default */])(_requirement, ["freshness", "timeout"]);

  return Object(esm_extends["a" /* default */])({
    freshness: millisToString(freshness),
    timeout: millisToString(timeout)
  }, other);
}

var SECOND_IN_MILLIS = 1000;
var MINUTE_IN_MILLIS = SECOND_IN_MILLIS * 60;
var HOUR_IN_MILLIS = MINUTE_IN_MILLIS * 60;

function millisToString(millis) {
  if (!millis) {
    return '';
  }

  var hours = Math.floor(millis / HOUR_IN_MILLIS);
  millis -= hours * HOUR_IN_MILLIS;
  var minutes = Math.floor(millis / MINUTE_IN_MILLIS);
  millis -= minutes * MINUTE_IN_MILLIS;
  var seconds = millis / SECOND_IN_MILLIS;
  var str = '';
  str = hours ? hours + " hours " : str;
  str = minutes ? "" + str + minutes + " mins " : str;
  str = seconds ? "" + str + seconds + " secs " : str;
  return str;
}

function getComponentsForResource(requirementsByComponent, resourceName) {
  var components = [];
  requirementsByComponent.forEach(function (requirements, component) {
    var requirement = Object(external_lodash_["find"])(requirements, {
      resourceName: resourceName
    });

    if (requirement) {
      components.push(component);
    }
  });
  return components.length ? components : null;
}
// CONCATENATED MODULE: ./node_modules/@fresh-data/framework/es/devinfo/summary.js


/**
 * A string summary of fresh-data client resources
 * @param {Object} resources Resources generated by ./resources.js for a client.
 * @return {string} A single-line string summary.
 */

function summary_summary(resources) {
  var resourceNames = Object.keys(resources);
  var components = [];
  var freshCount = 0;
  var staleCount = 0;
  var timedOutCount = 0;
  var fetchingCount = 0;
  var notRequiredCount = 0;
  resourceNames.forEach(function (resourceName) {
    var resource = resources[resourceName];
    components = Object(external_lodash_["union"])(components, resource.componentsRequiring);

    switch (resource.status) {
      case STATUS.overdue:
        timedOutCount++;
        break;

      case STATUS.fetching:
        fetchingCount++;
        break;

      case STATUS.stale:
        staleCount++;
        break;

      case STATUS.fresh:
        freshCount++;
        break;

      case STATUS.notRequired:
        notRequiredCount++;
    }
  });
  var text = resourceNames.length + " resources, " + components.length + " components ( ";

  if (freshCount) {
    text += freshCount + " fresh ";
  }

  if (staleCount) {
    text += staleCount + " stale ";
  }

  if (notRequiredCount) {
    text += notRequiredCount + " not required ";
  }

  if (timedOutCount) {
    text += timedOutCount + " timed out ";
  }

  if (fetchingCount) {
    text += fetchingCount + " fetching ";
  }

  text += ')';
  return text;
}
// CONCATENATED MODULE: ./node_modules/@fresh-data/framework/es/devinfo/index.js



var devInfo = {};
/**
 * Checks if devInfo is enabled and available.
 * @return {boolean} True if dev info is enabled, false if not.
 */

function isDevInfoEnabled() {
  return true === window.__FRESH_DATA_DEV_INFO__;
}
/**
 * Called by the ApiClient class to update the dev info data.
 * This is called when the client state or requirements change.
 * @param {Object} client The client which has been updated.
 */

function updateDevInfo(client) {
  if (isDevInfoEnabled()) {
    devInfo[client.getName()] = generateDevInfo(client);
    setDevInfoGlobal();
  }
}
/**
 * Generates the devInfo object for a given client.
 * @param {Object} client The client for which the info is generated.
 * @return {Object} A devInfo object with summary, resources, and components.
 */

function generateDevInfo(client) {
  var components = components_components(client);
  var resources = resources_resources(client);
  var summary = summary_summary(resources);
  var info = {
    summary: summary,
    resources: resources,
    components: components
  };
  return info;
}
/**
 * Sets the dev info to the global window context.
 * This is so it can be referenced by the JavaScript console in the browser.
 */


function setDevInfoGlobal() {
  if (!window.freshData) {
    window.freshData = devInfo;
  }
}
// CONCATENATED MODULE: ./node_modules/@fresh-data/framework/es/utils/constants.js
var SECOND = 1000;
var MINUTE = 60 * SECOND;
var HOUR = 60 * MINUTE;
// CONCATENATED MODULE: ./node_modules/@fresh-data/framework/es/client/requirements.js



var DEFAULTS = {
  freshness: Number.MAX_SAFE_INTEGER,
  timeout: 20 * SECOND
};
/**
 * Combines component requirements into a requirements list by resourceName.
 * @param {Map} requirementsByComponent Key: Component, Value: requirements parameters with resourceName.
 * @return {Object} List of requirements by resource name.
 */

function combineComponentRequirements(requirementsByComponent) {
  var requirementsByResource = {};
  requirementsByComponent.forEach(function (requirements) {
    requirements.forEach(function (requirement) {
      var resourceName = requirement.resourceName,
          reqParams = Object(objectWithoutPropertiesLoose["a" /* default */])(requirement, ["resourceName"]);

      addResourceRequirement(requirementsByResource, reqParams, resourceName);
    });
  });
  return requirementsByResource;
}
/**
 * Mutates the state of requirementsByResource by adding requirement parameters to it.
 * @param {Object} requirementsByResource List of requirements keyed by resourceName.
 * @param {Object} reqParams New requirement parameters ( e.g. { freshness: 30 * SECOND } )
 * @param {string} resourceName Name of resource being required.
 */

function addResourceRequirement(requirementsByResource, reqParams, resourceName) {
  var requirement = requirementsByResource[resourceName] || Object(esm_extends["a" /* default */])({}, DEFAULTS);

  addRequirementParams(requirement, reqParams);
  requirementsByResource[resourceName] = requirement;
}
/**
 * Merges new requirement parameters into existing ones.
 * @param {Object} requirements Contains requirement parameters.
 * @param {Object} reqParams New requirement parameters (freshness, timeout), to be merged with existing ones.
 */

function addRequirementParams(requirements, reqParams) {
  var freshness = requirements.freshness || DEFAULTS.freshness;
  var timeout = requirements.timeout || DEFAULTS.timeout;
  var newFreshness = reqParams.freshness || Number.MAX_SAFE_INTEGER;
  var newTimeout = reqParams.timeout || Number.MAX_SAFE_INTEGER;
  requirements.freshness = Math.min(freshness, newFreshness);
  requirements.timeout = Math.min(timeout, newTimeout);
}
// CONCATENATED MODULE: ./node_modules/@fresh-data/framework/es/client/index.js








var DEFAULT_READ_OPERATION = 'read';

function _setTimer(callback, delay) {
  return window.setTimeout(callback, delay);
}

function _clearTimer(id) {
  return window.clearTimeout(id);
}

var client_ApiClient = function ApiClient(apiSpec, setTimer, clearTimer) {
  var _this = this;

  if (setTimer === void 0) {
    setTimer = _setTimer;
  }

  if (clearTimer === void 0) {
    clearTimer = _clearTimer;
  }

  this.getName = function () {
    return _this.name || 'UID_' + _this.uid;
  };

  this.mapOperations = function (apiOperations) {
    return Object.keys(apiOperations).reduce(function (operations, operationName) {
      operations[operationName] = function (resourceNames, data) {
        var apiOperation = apiOperations[operationName];
        return _this.applyOperation(apiOperation, resourceNames, data);
      };

      return operations;
    }, {});
  };

  this.setDataHandlers = function (_ref) {
    var dataRequested = _ref.dataRequested,
        dataReceived = _ref.dataReceived;
    _this.dataHandlers = {
      dataRequested: dataRequested,
      dataReceived: dataReceived
    };
  };

  this.setState = function (state, now) {
    if (now === void 0) {
      now = new Date();
    }

    if (_this.state !== state) {
      _this.state = state;

      _this.updateTimer(now);

      _this.subscriptionCallbacks.forEach(function (callback) {
        return callback(_this);
      });

      updateDevInfo(_this);
    }
  };

  this.subscribe = function (callback) {
    if (_this.subscriptionCallbacks.has(callback)) {
      _this.debug('Attempting to add a subscription callback twice:', callback);

      return false;
    }

    _this.subscriptionCallbacks.add(callback);

    return callback;
  };

  this.unsubscribe = function (callback) {
    if (!_this.subscriptionCallbacks.has(callback)) {
      _this.debug('Attempting to remove a callback that is not subscribed:', callback);

      return false;
    }

    _this.subscriptionCallbacks["delete"](callback);

    return callback;
  };

  this.getResource = function (resourceName) {
    var resources = _this.state.resources || {};
    var resource = resources[resourceName] || {};
    return resource;
  };

  this.requireResource = function (componentRequirements) {
    return function (requirement, resourceName) {
      componentRequirements.push(Object(esm_extends["a" /* default */])({}, requirement, {
        resourceName: resourceName
      }));
      return _this.getResource(resourceName);
    };
  };

  this.getMutations = function () {
    return _this.mutations;
  };

  this.getSelectors = function (componentRequirements) {
    return mapFunctions(_this.selectors, _this.getResource, _this.requireResource(componentRequirements));
  };

  this.clearComponentRequirements = function (component, now) {
    if (now === void 0) {
      now = new Date();
    }

    _this.requirementsByComponent["delete"](component);

    _this.updateRequirementsByResource(now);
  };

  this.setComponentRequirements = function (component, componentRequirements, now) {
    if (now === void 0) {
      now = new Date();
    }

    _this.requirementsByComponent.set(component, componentRequirements);

    _this.updateRequirementsByResource(now);
  };

  this.setComponentData = function (component, selectorFunc, now) {
    if (now === void 0) {
      now = new Date();
    }

    if (selectorFunc) {
      var componentRequirements = [];

      var selectors = _this.getSelectors(componentRequirements);

      selectorFunc(selectors);

      _this.setComponentRequirements(component, componentRequirements, now);
    } else {
      _this.clearComponentRequirements(component, now);
    }
  };

  this.updateRequirementsByResource = function (now) {
    if (now === void 0) {
      now = new Date();
    }

    // TODO: Consider using a reducer style function for resource requirements so we don't
    // have to do a deep equals check.
    var requirementsByResource = combineComponentRequirements(_this.requirementsByComponent);

    if (!Object(external_lodash_["isEqual"])(_this.requirementsByResource, requirementsByResource)) {
      _this.requirementsByResource = requirementsByResource;

      _this.updateTimer(now);
    }
  };

  this.updateRequirementsData =
  /*#__PURE__*/
  function () {
    var _ref2 = Object(asyncToGenerator["a" /* default */])(
    /*#__PURE__*/
    regenerator_default.a.mark(function _callee(now) {
      var requirementsByComponent, requirementsByResource, state, minUpdate, maxUpdate, resourceState, componentCount, resourceCount, _calculateUpdates, nextUpdate, updates, readOperationName, readOperation;

      return regenerator_default.a.wrap(function _callee$(_context) {
        while (1) {
          switch (_context.prev = _context.next) {
            case 0:
              requirementsByComponent = _this.requirementsByComponent, requirementsByResource = _this.requirementsByResource, state = _this.state, minUpdate = _this.minUpdate, maxUpdate = _this.maxUpdate;
              resourceState = state.resources || {};
              componentCount = requirementsByComponent.size;
              resourceCount = Object.keys(requirementsByResource).length;

              _this.debug("Updating requirements for " + componentCount + " components and " + resourceCount + " resources.");

              updateDevInfo(_this);

              if (Object(external_lodash_["isEmpty"])(requirementsByResource)) {
                _context.next = 20;
                break;
              }

              _calculateUpdates = calculateUpdates(requirementsByResource, resourceState, minUpdate, maxUpdate, now), nextUpdate = _calculateUpdates.nextUpdate, updates = _calculateUpdates.updates;

              if (!(updates && updates.length > 0)) {
                _context.next = 15;
                break;
              }

              readOperationName = _this.readOperationName;
              readOperation = _this.operations[readOperationName];

              if (readOperation) {
                _context.next = 13;
                break;
              }

              throw new Error("Operation \"" + readOperationName + "\" not found.");

            case 13:
              _context.next = 15;
              return _this.operations[readOperationName](updates);

            case 15:
              _this.debug("Scheduling next update for " + nextUpdate / 1000 + " seconds.");

              updateDevInfo(_this);

              _this.updateTimer(now, nextUpdate);

              _context.next = 21;
              break;

            case 20:
              if (_this.timeoutId) {
                _this.debug('Unscheduling future updates');

                updateDevInfo(_this);

                _this.updateTimer(now, null);
              }

            case 21:
            case "end":
              return _context.stop();
          }
        }
      }, _callee);
    }));

    return function (_x) {
      return _ref2.apply(this, arguments);
    };
  }();

  this.updateTimer = function (now, nextUpdate) {
    if (nextUpdate === void 0) {
      nextUpdate = undefined;
    }

    var requirementsByResource = _this.requirementsByResource,
        state = _this.state,
        minUpdate = _this.minUpdate,
        maxUpdate = _this.maxUpdate;
    var resourceState = state.resources || {};

    if (undefined === nextUpdate) {
      nextUpdate = calculateUpdates(requirementsByResource, resourceState, minUpdate, maxUpdate, now).nextUpdate;
    }

    if (_this.timeoutId) {
      _this.clearTimer(_this.timeoutId);

      _this.timeoutId = null;
    }

    if (nextUpdate) {
      _this.timeoutId = _this.setTimer(_this.updateRequirementsData, nextUpdate);
    }
  };

  this.applyOperation =
  /*#__PURE__*/
  function () {
    var _ref3 = Object(asyncToGenerator["a" /* default */])(
    /*#__PURE__*/
    regenerator_default.a.mark(function _callee3(apiOperation, resourceNames, data) {
      var operationResult, values, requests;
      return regenerator_default.a.wrap(function _callee3$(_context3) {
        while (1) {
          switch (_context3.prev = _context3.next) {
            case 0:
              _context3.prev = 0;

              _this.dataRequested(resourceNames);

              operationResult = apiOperation(resourceNames, data) || [];
              values = Object(external_lodash_["isArray"])(operationResult) ? operationResult : [operationResult];
              requests = values.map(
              /*#__PURE__*/
              function () {
                var _ref4 = Object(asyncToGenerator["a" /* default */])(
                /*#__PURE__*/
                regenerator_default.a.mark(function _callee2(value) {
                  var resources;
                  return regenerator_default.a.wrap(function _callee2$(_context2) {
                    while (1) {
                      switch (_context2.prev = _context2.next) {
                        case 0:
                          _context2.next = 2;
                          return value;

                        case 2:
                          resources = _context2.sent;

                          _this.dataReceived(resources);

                          return _context2.abrupt("return", resources);

                        case 5:
                        case "end":
                          return _context2.stop();
                      }
                    }
                  }, _callee2);
                }));

                return function (_x5) {
                  return _ref4.apply(this, arguments);
                };
              }());
              _context3.next = 7;
              return Promise.all(requests);

            case 7:
              return _context3.abrupt("return", _context3.sent);

            case 10:
              _context3.prev = 10;
              _context3.t0 = _context3["catch"](0);

              _this.debug('Error caught while applying operation: ', apiOperation);

              throw _context3.t0;

            case 14:
            case "end":
              return _context3.stop();
          }
        }
      }, _callee3, null, [[0, 10]]);
    }));

    return function (_x2, _x3, _x4) {
      return _ref3.apply(this, arguments);
    };
  }();

  this.dataRequested = function (resourceNames) {
    if (!_this.dataHandlers) {
      _this.debug('Data requested before dataHandlers set. Disregarding.');

      return;
    }

    _this.dataHandlers.dataRequested(resourceNames);

    return resourceNames;
  };

  this.dataReceived = function (resources) {
    if (!_this.dataHandlers) {
      _this.debug('Data received before dataHandlers set. Disregarding.');

      return;
    }

    _this.dataHandlers.dataReceived(resources);

    return resources;
  };

  var _operations = apiSpec.operations,
      mutations = apiSpec.mutations,
      _selectors = apiSpec.selectors;

  var _readOperationName = apiSpec.readOperationName || DEFAULT_READ_OPERATION;

  this.uid = Object(external_lodash_["uniqueId"])();
  this.name = apiSpec.name;
  this.debug = browser_default()("fresh-data:api-client[" + this.uid + "]");
  this.debug('New ApiClient for apiSpec: ', apiSpec);
  this.operations = _operations && this.mapOperations(_operations);
  this.mutations = mutations && mapFunctions(mutations, this.operations);
  this.selectors = _selectors;
  this.readOperationName = _readOperationName;
  this.dataHandlers = null;
  this.subscriptionCallbacks = new Set();
  this.requirementsByComponent = new Map();
  this.requirementsByResource = {};
  this.minUpdate = DEFAULT_MIN_UPDATE;
  this.maxUpdate = DEFAULT_MAX_UPDATE;
  this.setTimer = setTimer;
  this.clearTimer = clearTimer;
  this.timeoutId = null;
  this.state = {};
  updateDevInfo(this);
};



function mapFunctions(functionsByName) {
  for (var _len = arguments.length, params = new Array(_len > 1 ? _len - 1 : 0), _key = 1; _key < _len; _key++) {
    params[_key - 1] = arguments[_key];
  }

  return Object.keys(functionsByName).reduce(function (mappedFunctions, functionName) {
    mappedFunctions[functionName] = functionsByName[functionName].apply(functionsByName, params);
    return mappedFunctions;
  }, {});
}
// CONCATENATED MODULE: ./node_modules/@fresh-data/framework/es/index.js
/**
 * This is the public API of Fresh Data.
 * Below are the parts that can be used within your own application
 * in order to use Fresh Data with your own APIs.
 */
// Instantiate an ApiClient with a given apiSpec.
 // Use these to express requirement times like freshness and timeout.



/***/ }),

/***/ 174:
/***/ (function(module, exports, __webpack_require__) {

"use strict";


if (true) {
  module.exports = __webpack_require__(210);
} else {}


/***/ }),

/***/ 18:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";

// EXPORTS
__webpack_require__.d(__webpack_exports__, "a", function() { return /* binding */ BACKSPACE; });
__webpack_require__.d(__webpack_exports__, "h", function() { return /* binding */ TAB; });
__webpack_require__.d(__webpack_exports__, "c", function() { return /* binding */ ENTER; });
__webpack_require__.d(__webpack_exports__, "d", function() { return /* binding */ ESCAPE; });
__webpack_require__.d(__webpack_exports__, "g", function() { return /* binding */ SPACE; });
__webpack_require__.d(__webpack_exports__, "e", function() { return /* binding */ LEFT; });
__webpack_require__.d(__webpack_exports__, "i", function() { return /* binding */ UP; });
__webpack_require__.d(__webpack_exports__, "f", function() { return /* binding */ RIGHT; });
__webpack_require__.d(__webpack_exports__, "b", function() { return /* binding */ DOWN; });

// UNUSED EXPORTS: DELETE, F10, ALT, CTRL, COMMAND, SHIFT, modifiers, rawShortcut, displayShortcutList, displayShortcut, shortcutAriaLabel, isKeyboardEvent

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/esm/defineProperty.js
var defineProperty = __webpack_require__(13);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/esm/toConsumableArray.js + 3 modules
var toConsumableArray = __webpack_require__(17);

// EXTERNAL MODULE: external "lodash"
var external_lodash_ = __webpack_require__(2);

// EXTERNAL MODULE: external {"this":["wp","i18n"]}
var external_this_wp_i18n_ = __webpack_require__(3);

// CONCATENATED MODULE: ./node_modules/@wordpress/keycodes/build-module/platform.js
/**
 * External dependencies
 */

/**
 * Return true if platform is MacOS.
 *
 * @param {Object} _window   window object by default; used for DI testing.
 *
 * @return {boolean}         True if MacOS; false otherwise.
 */

function isAppleOS() {
  var _window = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : window;

  var platform = _window.navigator.platform;
  return platform.indexOf('Mac') !== -1 || Object(external_lodash_["includes"])(['iPad', 'iPhone'], platform);
}
//# sourceMappingURL=platform.js.map
// CONCATENATED MODULE: ./node_modules/@wordpress/keycodes/build-module/index.js



/**
 * Note: The order of the modifier keys in many of the [foo]Shortcut()
 * functions in this file are intentional and should not be changed. They're
 * designed to fit with the standard menu keyboard shortcuts shown in the
 * user's platform.
 *
 * For example, on MacOS menu shortcuts will place Shift before Command, but
 * on Windows Control will usually come first. So don't provide your own
 * shortcut combos directly to keyboardShortcut().
 */

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */


/**
 * Internal dependencies
 */


/**
 * @typedef {'primary'|'primaryShift'|'primaryAlt'|'secondary'|'access'|'ctrl'|'alt'|'ctrlShift'|'shift'|'shiftAlt'} WPKeycodeModifier
 */

/**
 * An object of handler functions for each of the possible modifier
 * combinations. A handler will return a value for a given key.
 *
 * @typedef {{[M in WPKeycodeModifier]:(key:string)=>any}} WPKeycodeHandlerByModifier
 */

/**
 * Keycode for BACKSPACE key.
 */

var BACKSPACE = 8;
/**
 * Keycode for TAB key.
 */

var TAB = 9;
/**
 * Keycode for ENTER key.
 */

var ENTER = 13;
/**
 * Keycode for ESCAPE key.
 */

var ESCAPE = 27;
/**
 * Keycode for SPACE key.
 */

var SPACE = 32;
/**
 * Keycode for LEFT key.
 */

var LEFT = 37;
/**
 * Keycode for UP key.
 */

var UP = 38;
/**
 * Keycode for RIGHT key.
 */

var RIGHT = 39;
/**
 * Keycode for DOWN key.
 */

var DOWN = 40;
/**
 * Keycode for DELETE key.
 */

var DELETE = 46;
/**
 * Keycode for F10 key.
 */

var F10 = 121;
/**
 * Keycode for ALT key.
 */

var ALT = 'alt';
/**
 * Keycode for CTRL key.
 */

var CTRL = 'ctrl';
/**
 * Keycode for COMMAND/META key.
 */

var COMMAND = 'meta';
/**
 * Keycode for SHIFT key.
 */

var SHIFT = 'shift';
/**
 * Object that contains functions that return the available modifier
 * depending on platform.
 *
 * - `primary`: takes a isApple function as a parameter.
 * - `primaryShift`: takes a isApple function as a parameter.
 * - `primaryAlt`: takes a isApple function as a parameter.
 * - `secondary`: takes a isApple function as a parameter.
 * - `access`: takes a isApple function as a parameter.
 * - `ctrl`
 * - `alt`
 * - `ctrlShift`
 * - `shift`
 * - `shiftAlt`
 */

var modifiers = {
  primary: function primary(_isApple) {
    return _isApple() ? [COMMAND] : [CTRL];
  },
  primaryShift: function primaryShift(_isApple) {
    return _isApple() ? [SHIFT, COMMAND] : [CTRL, SHIFT];
  },
  primaryAlt: function primaryAlt(_isApple) {
    return _isApple() ? [ALT, COMMAND] : [CTRL, ALT];
  },
  secondary: function secondary(_isApple) {
    return _isApple() ? [SHIFT, ALT, COMMAND] : [CTRL, SHIFT, ALT];
  },
  access: function access(_isApple) {
    return _isApple() ? [CTRL, ALT] : [SHIFT, ALT];
  },
  ctrl: function ctrl() {
    return [CTRL];
  },
  alt: function alt() {
    return [ALT];
  },
  ctrlShift: function ctrlShift() {
    return [CTRL, SHIFT];
  },
  shift: function shift() {
    return [SHIFT];
  },
  shiftAlt: function shiftAlt() {
    return [SHIFT, ALT];
  }
};
/**
 * An object that contains functions to get raw shortcuts.
 * E.g. rawShortcut.primary( 'm' ) will return 'meta+m' on Mac.
 * These are intended for user with the KeyboardShortcuts component or TinyMCE.
 *
 * @type {WPKeycodeHandlerByModifier} Keyed map of functions to raw shortcuts.
 */

var rawShortcut = Object(external_lodash_["mapValues"])(modifiers, function (modifier) {
  return function (character) {
    var _isApple = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : isAppleOS;

    return [].concat(Object(toConsumableArray["a" /* default */])(modifier(_isApple)), [character.toLowerCase()]).join('+');
  };
});
/**
 * Return an array of the parts of a keyboard shortcut chord for display
 * E.g displayShortcutList.primary( 'm' ) will return [ '⌘', 'M' ] on Mac.
 *
 * @type {WPKeycodeHandlerByModifier} Keyed map of functions to shortcut
 *                                    sequences.
 */

var displayShortcutList = Object(external_lodash_["mapValues"])(modifiers, function (modifier) {
  return function (character) {
    var _replacementKeyMap;

    var _isApple = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : isAppleOS;

    var isApple = _isApple();

    var replacementKeyMap = (_replacementKeyMap = {}, Object(defineProperty["a" /* default */])(_replacementKeyMap, ALT, isApple ? '⌥' : 'Alt'), Object(defineProperty["a" /* default */])(_replacementKeyMap, CTRL, isApple ? '^' : 'Ctrl'), Object(defineProperty["a" /* default */])(_replacementKeyMap, COMMAND, '⌘'), Object(defineProperty["a" /* default */])(_replacementKeyMap, SHIFT, isApple ? '⇧' : 'Shift'), _replacementKeyMap);
    var modifierKeys = modifier(_isApple).reduce(function (accumulator, key) {
      var replacementKey = Object(external_lodash_["get"])(replacementKeyMap, key, key); // If on the Mac, adhere to platform convention and don't show plus between keys.

      if (isApple) {
        return [].concat(Object(toConsumableArray["a" /* default */])(accumulator), [replacementKey]);
      }

      return [].concat(Object(toConsumableArray["a" /* default */])(accumulator), [replacementKey, '+']);
    }, []);
    var capitalizedCharacter = Object(external_lodash_["capitalize"])(character);
    return [].concat(Object(toConsumableArray["a" /* default */])(modifierKeys), [capitalizedCharacter]);
  };
});
/**
 * An object that contains functions to display shortcuts.
 * E.g. displayShortcut.primary( 'm' ) will return '⌘M' on Mac.
 *
 * @type {WPKeycodeHandlerByModifier} Keyed map of functions to display
 *                                    shortcuts.
 */

var displayShortcut = Object(external_lodash_["mapValues"])(displayShortcutList, function (shortcutList) {
  return function (character) {
    var _isApple = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : isAppleOS;

    return shortcutList(character, _isApple).join('');
  };
});
/**
 * An object that contains functions to return an aria label for a keyboard shortcut.
 * E.g. shortcutAriaLabel.primary( '.' ) will return 'Command + Period' on Mac.
 *
 * @type {WPKeycodeHandlerByModifier} Keyed map of functions to shortcut ARIA
 *                                    labels.
 */

var shortcutAriaLabel = Object(external_lodash_["mapValues"])(modifiers, function (modifier) {
  return function (character) {
    var _replacementKeyMap2;

    var _isApple = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : isAppleOS;

    var isApple = _isApple();

    var replacementKeyMap = (_replacementKeyMap2 = {}, Object(defineProperty["a" /* default */])(_replacementKeyMap2, SHIFT, 'Shift'), Object(defineProperty["a" /* default */])(_replacementKeyMap2, COMMAND, isApple ? 'Command' : 'Control'), Object(defineProperty["a" /* default */])(_replacementKeyMap2, CTRL, 'Control'), Object(defineProperty["a" /* default */])(_replacementKeyMap2, ALT, isApple ? 'Option' : 'Alt'), Object(defineProperty["a" /* default */])(_replacementKeyMap2, ',', Object(external_this_wp_i18n_["__"])('Comma')), Object(defineProperty["a" /* default */])(_replacementKeyMap2, '.', Object(external_this_wp_i18n_["__"])('Period')), Object(defineProperty["a" /* default */])(_replacementKeyMap2, '`', Object(external_this_wp_i18n_["__"])('Backtick')), _replacementKeyMap2);
    return [].concat(Object(toConsumableArray["a" /* default */])(modifier(_isApple)), [character]).map(function (key) {
      return Object(external_lodash_["capitalize"])(Object(external_lodash_["get"])(replacementKeyMap, key, key));
    }).join(isApple ? ' ' : ' + ');
  };
});
/**
 * An object that contains functions to check if a keyboard event matches a
 * predefined shortcut combination.
 * E.g. isKeyboardEvent.primary( event, 'm' ) will return true if the event
 * signals pressing ⌘M.
 *
 * @type {WPKeycodeHandlerByModifier} Keyed map of functions to match events.
 */

var isKeyboardEvent = Object(external_lodash_["mapValues"])(modifiers, function (getModifiers) {
  return function (event, character) {
    var _isApple = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : isAppleOS;

    var mods = getModifiers(_isApple);

    if (!mods.every(function (key) {
      return event["".concat(key, "Key")];
    })) {
      return false;
    }

    if (!character) {
      return Object(external_lodash_["includes"])(mods, event.key.toLowerCase());
    }

    return event.key === character;
  };
});
//# sourceMappingURL=index.js.map

/***/ }),

/***/ 19:
/***/ (function(module, exports) {

(function() { module.exports = this["wp"]["data"]; }());

/***/ }),

/***/ 198:
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var reactIs = __webpack_require__(174);

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

/***/ 199:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(3);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_hooks__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(48);
/* harmony import */ var _wordpress_hooks__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_hooks__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _settings__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(26);
/* harmony import */ var _index__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(200);
/**
 * External dependencies
 */



/**
 * WooCommerce dependencies
 */


var manageStock = Object(_settings__WEBPACK_IMPORTED_MODULE_3__[/* getSetting */ "g"])('manageStock', 'no');
/**
 * Internal dependencies
 */

var RevenueReport = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__["lazy"])(function () {
  return Promise.all(/* import() | analytics-report-revenue */[__webpack_require__.e(1), __webpack_require__.e(0), __webpack_require__.e(15)]).then(__webpack_require__.bind(null, 728));
});
var ProductsReport = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__["lazy"])(function () {
  return Promise.all(/* import() | analytics-report-products */[__webpack_require__.e(1), __webpack_require__.e(0), __webpack_require__.e(4), __webpack_require__.e(14)]).then(__webpack_require__.bind(null, 731));
});
var OrdersReport = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__["lazy"])(function () {
  return Promise.all(/* import() | analytics-report-orders */[__webpack_require__.e(1), __webpack_require__.e(0), __webpack_require__.e(13)]).then(__webpack_require__.bind(null, 725));
});
var CategoriesReport = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__["lazy"])(function () {
  return Promise.all(/* import() | analytics-report-categories */[__webpack_require__.e(1), __webpack_require__.e(0), __webpack_require__.e(4), __webpack_require__.e(9)]).then(__webpack_require__.bind(null, 724));
});
var CouponsReport = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__["lazy"])(function () {
  return Promise.all(/* import() | analytics-report-coupons */[__webpack_require__.e(1), __webpack_require__.e(0), __webpack_require__.e(10)]).then(__webpack_require__.bind(null, 730));
});
var TaxesReport = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__["lazy"])(function () {
  return Promise.all(/* import() | analytics-report-taxes */[__webpack_require__.e(1), __webpack_require__.e(0), __webpack_require__.e(17)]).then(__webpack_require__.bind(null, 729));
});
var DownloadsReport = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__["lazy"])(function () {
  return Promise.all(/* import() | analytics-report-downloads */[__webpack_require__.e(1), __webpack_require__.e(0), __webpack_require__.e(12)]).then(__webpack_require__.bind(null, 727));
});
var StockReport = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__["lazy"])(function () {
  return Promise.all(/* import() | analytics-report-stock */[__webpack_require__.e(0), __webpack_require__.e(16)]).then(__webpack_require__.bind(null, 722));
});
var CustomersReport = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__["lazy"])(function () {
  return Promise.all(/* import() | analytics-report-customers */[__webpack_require__.e(0), __webpack_require__.e(11)]).then(__webpack_require__.bind(null, 723));
});

/* harmony default export */ __webpack_exports__["a"] = (function () {
  var reports = [{
    report: 'revenue',
    title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Revenue', 'woocommerce'),
    component: RevenueReport
  }, {
    report: 'products',
    title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Products', 'woocommerce'),
    component: ProductsReport
  }, {
    report: 'orders',
    title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Orders', 'woocommerce'),
    component: OrdersReport
  }, {
    report: 'categories',
    title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Categories', 'woocommerce'),
    component: CategoriesReport
  }, {
    report: 'coupons',
    title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Coupons', 'woocommerce'),
    component: CouponsReport
  }, {
    report: 'taxes',
    title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Taxes', 'woocommerce'),
    component: TaxesReport
  }, {
    report: 'downloads',
    title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Downloads', 'woocommerce'),
    component: DownloadsReport
  }, manageStock === 'yes' ? {
    report: 'stock',
    title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Stock', 'woocommerce'),
    component: StockReport
  } : null, {
    report: 'customers',
    title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Customers', 'woocommerce'),
    component: CustomersReport
  }, {
    report: 'downloads',
    title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Downloads', 'woocommerce'),
    component: DownloadsReport
  }].filter(Boolean);
  return Object(_wordpress_hooks__WEBPACK_IMPORTED_MODULE_1__["applyFilters"])(_index__WEBPACK_IMPORTED_MODULE_4__["REPORTS_FILTER"], reports);
});

/***/ }),

/***/ 2:
/***/ (function(module, exports) {

(function() { module.exports = this["lodash"]; }());

/***/ }),

/***/ 20:
/***/ (function(module, exports) {

(function() { module.exports = this["wp"]["apiFetch"]; }());

/***/ }),

/***/ 200:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "REPORTS_FILTER", function() { return REPORTS_FILTER; });
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(15);
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(41);
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(40);
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(44);
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(29);
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(42);
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(256);
/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(1);
/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(prop_types__WEBPACK_IMPORTED_MODULE_8__);
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(2);
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_9__);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(63);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_10___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_components__WEBPACK_IMPORTED_MODULE_10__);
/* harmony import */ var _woocommerce_navigation__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(22);
/* harmony import */ var _woocommerce_navigation__WEBPACK_IMPORTED_MODULE_11___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_11__);
/* harmony import */ var _style_scss__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(435);
/* harmony import */ var _style_scss__WEBPACK_IMPORTED_MODULE_12___default = /*#__PURE__*/__webpack_require__.n(_style_scss__WEBPACK_IMPORTED_MODULE_12__);
/* harmony import */ var analytics_components_report_error__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(261);
/* harmony import */ var wc_api_items_utils__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(267);
/* harmony import */ var wc_api_with_select__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(101);
/* harmony import */ var lib_currency_context__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__(203);
/* harmony import */ var _get_reports__WEBPACK_IMPORTED_MODULE_17__ = __webpack_require__(199);








function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0___default()(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function () { var Super = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_4___default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_4___default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_3___default()(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/**
 * External dependencies
 */




/**
 * WooCommerce dependencies
 */



/**
 * Internal dependencies
 */







var REPORTS_FILTER = 'woocommerce_admin_reports_list';
/**
 * The Customers Report will not have the `report` param supplied by the router/
 * because it no longer exists under the path `/analytics/:report`. Use `props.path`/
 * instead to determine if the Customers Report is being rendered.
 *
 * @param {Object} params -url parameters
 * @return {string} - report parameter
 */

var getReportParam = function getReportParam(_ref) {
  var params = _ref.params,
      path = _ref.path;
  return params.report || path.replace(/^\/+/, '');
};

var Report = /*#__PURE__*/function (_Component) {
  _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_5___default()(Report, _Component);

  var _super = _createSuper(Report);

  function Report() {
    var _this;

    _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_1___default()(this, Report);

    _this = _super.apply(this, arguments);
    _this.state = {
      hasError: false
    };
    return _this;
  }

  _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_2___default()(Report, [{
    key: "componentDidCatch",
    value: function componentDidCatch(error) {
      this.setState({
        hasError: true
      });
      /* eslint-disable no-console */

      console.warn(error);
      /* eslint-enable no-console */
    }
  }, {
    key: "render",
    value: function render() {
      if (this.state.hasError) {
        return null;
      }

      var isError = this.props.isError;

      if (isError) {
        return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])(analytics_components_report_error__WEBPACK_IMPORTED_MODULE_13__[/* default */ "a"], {
          isError: true
        });
      }

      var reportParam = getReportParam(this.props);
      var report = Object(lodash__WEBPACK_IMPORTED_MODULE_9__["find"])(Object(_get_reports__WEBPACK_IMPORTED_MODULE_17__[/* default */ "a"])(), {
        report: reportParam
      });

      if (!report) {
        return null;
      }

      var Container = report.component;
      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])(lib_currency_context__WEBPACK_IMPORTED_MODULE_16__[/* CurrencyContext */ "a"].Provider, {
        value: Object(lib_currency_context__WEBPACK_IMPORTED_MODULE_16__[/* getFilteredCurrencyInstance */ "b"])(Object(_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_11__["getQuery"])())
      }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])(Container, this.props));
    }
  }]);

  return Report;
}(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["Component"]);

Report.propTypes = {
  params: prop_types__WEBPACK_IMPORTED_MODULE_8___default.a.object.isRequired
};
/* harmony default export */ __webpack_exports__["default"] = (Object(_wordpress_compose__WEBPACK_IMPORTED_MODULE_7__[/* default */ "a"])(Object(_woocommerce_components__WEBPACK_IMPORTED_MODULE_10__["useFilters"])(REPORTS_FILTER), Object(wc_api_with_select__WEBPACK_IMPORTED_MODULE_15__[/* default */ "a"])(function (select, props) {
  var query = Object(_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_11__["getQuery"])();
  var search = query.search;

  if (!search) {
    return {};
  }

  var report = getReportParam(props);
  var searchWords = Object(_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_11__["getSearchWords"])(query); // Single Category view in Categories Report uses the products endpoint, so search must also.

  var mappedReport = report === 'categories' && query.filter === 'single_category' ? 'products' : report;
  var itemsResult = Object(wc_api_items_utils__WEBPACK_IMPORTED_MODULE_14__[/* searchItemsByString */ "b"])(select, mappedReport, searchWords);
  var isError = itemsResult.isError,
      isRequesting = itemsResult.isRequesting,
      items = itemsResult.items;
  var ids = Object.keys(items);

  if (!ids.length) {
    return {
      isError: isError,
      isRequesting: isRequesting
    };
  }

  return {
    isError: isError,
    isRequesting: isRequesting,
    query: _objectSpread({}, props.query, _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0___default()({}, mappedReport, ids.join(',')))
  };
}))(Report));

/***/ }),

/***/ 201:
/***/ (function(module, exports) {

function _inheritsLoose(subClass, superClass) {
  subClass.prototype = Object.create(superClass.prototype);
  subClass.prototype.constructor = subClass;
  subClass.__proto__ = superClass;
}

module.exports = _inheritsLoose;

/***/ }),

/***/ 202:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";

// EXPORTS
__webpack_require__.d(__webpack_exports__, "c", function() { return /* binding */ layout_PrimaryLayout; });
__webpack_require__.d(__webpack_exports__, "b", function() { return /* binding */ PageLayout; });
__webpack_require__.d(__webpack_exports__, "a", function() { return /* binding */ layout_EmbedLayout; });

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/extends.js
var helpers_extends = __webpack_require__(105);
var extends_default = /*#__PURE__*/__webpack_require__.n(helpers_extends);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/defineProperty.js
var defineProperty = __webpack_require__(15);
var defineProperty_default = /*#__PURE__*/__webpack_require__.n(defineProperty);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/objectWithoutProperties.js
var objectWithoutProperties = __webpack_require__(121);
var objectWithoutProperties_default = /*#__PURE__*/__webpack_require__.n(objectWithoutProperties);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/classCallCheck.js
var classCallCheck = __webpack_require__(41);
var classCallCheck_default = /*#__PURE__*/__webpack_require__.n(classCallCheck);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/createClass.js
var createClass = __webpack_require__(40);
var createClass_default = /*#__PURE__*/__webpack_require__.n(createClass);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/possibleConstructorReturn.js
var possibleConstructorReturn = __webpack_require__(44);
var possibleConstructorReturn_default = /*#__PURE__*/__webpack_require__.n(possibleConstructorReturn);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/getPrototypeOf.js
var getPrototypeOf = __webpack_require__(29);
var getPrototypeOf_default = /*#__PURE__*/__webpack_require__.n(getPrototypeOf);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/inherits.js
var inherits = __webpack_require__(42);
var inherits_default = /*#__PURE__*/__webpack_require__.n(inherits);

// EXTERNAL MODULE: external {"this":["wp","element"]}
var external_this_wp_element_ = __webpack_require__(0);

// EXTERNAL MODULE: ./node_modules/@wordpress/compose/build-module/higher-order/compose.js
var compose = __webpack_require__(256);

// EXTERNAL MODULE: external {"this":["wp","data"]}
var external_this_wp_data_ = __webpack_require__(19);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/esm/inheritsLoose.js
var inheritsLoose = __webpack_require__(76);

// EXTERNAL MODULE: external "React"
var external_React_ = __webpack_require__(14);
var external_React_default = /*#__PURE__*/__webpack_require__.n(external_React_);

// EXTERNAL MODULE: ./node_modules/prop-types/index.js
var prop_types = __webpack_require__(1);
var prop_types_default = /*#__PURE__*/__webpack_require__.n(prop_types);

// EXTERNAL MODULE: ./node_modules/history/esm/history.js + 2 modules
var esm_history = __webpack_require__(90);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/inheritsLoose.js
var helpers_inheritsLoose = __webpack_require__(201);
var inheritsLoose_default = /*#__PURE__*/__webpack_require__.n(helpers_inheritsLoose);

// EXTERNAL MODULE: ./node_modules/gud/index.js
var gud = __webpack_require__(416);
var gud_default = /*#__PURE__*/__webpack_require__.n(gud);

// CONCATENATED MODULE: ./node_modules/mini-create-react-context/dist/esm/index.js






var MAX_SIGNED_31_BIT_INT = 1073741823;

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

  var contextProp = '__create-react-context-' + gud_default()() + '__';

  var Provider =
  /*#__PURE__*/
  function (_Component) {
    inheritsLoose_default()(Provider, _Component);

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
  }(external_React_["Component"]);

  Provider.childContextTypes = (_Provider$childContex = {}, _Provider$childContex[contextProp] = prop_types_default.a.object.isRequired, _Provider$childContex);

  var Consumer =
  /*#__PURE__*/
  function (_Component2) {
    inheritsLoose_default()(Consumer, _Component2);

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
  }(external_React_["Component"]);

  Consumer.contextTypes = (_Consumer$contextType = {}, _Consumer$contextType[contextProp] = prop_types_default.a.object, _Consumer$contextType);
  return {
    Provider: Provider,
    Consumer: Consumer
  };
}

var index = external_React_default.a.createContext || createReactContext;

/* harmony default export */ var esm = (index);

// EXTERNAL MODULE: ./node_modules/tiny-invariant/dist/tiny-invariant.esm.js
var tiny_invariant_esm = __webpack_require__(78);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/esm/extends.js
var esm_extends = __webpack_require__(11);

// EXTERNAL MODULE: ./node_modules/react-router/node_modules/path-to-regexp/index.js
var path_to_regexp = __webpack_require__(258);
var path_to_regexp_default = /*#__PURE__*/__webpack_require__.n(path_to_regexp);

// EXTERNAL MODULE: ./node_modules/react-is/index.js
var react_is = __webpack_require__(174);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/esm/objectWithoutPropertiesLoose.js
var objectWithoutPropertiesLoose = __webpack_require__(54);

// EXTERNAL MODULE: ./node_modules/hoist-non-react-statics/dist/hoist-non-react-statics.cjs.js
var hoist_non_react_statics_cjs = __webpack_require__(198);
var hoist_non_react_statics_cjs_default = /*#__PURE__*/__webpack_require__.n(hoist_non_react_statics_cjs);

// CONCATENATED MODULE: ./node_modules/react-router/esm/react-router.js













// TODO: Replace with React.createContext once we can assume React 16+

var react_router_createNamedContext = function createNamedContext(name) {
  var context = esm();
  context.displayName = name;
  return context;
};

var react_router_context =
/*#__PURE__*/
react_router_createNamedContext("Router");

/**
 * The public API for putting history on context.
 */

var react_router_Router =
/*#__PURE__*/
function (_React$Component) {
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
    if (this.unlisten) this.unlisten();
  };

  _proto.render = function render() {
    return external_React_default.a.createElement(react_router_context.Provider, {
      children: this.props.children || null,
      value: {
        history: this.props.history,
        location: this.state.location,
        match: Router.computeRootMatch(this.state.location.pathname),
        staticContext: this.props.staticContext
      }
    });
  };

  return Router;
}(external_React_default.a.Component);

if (false) {}

/**
 * The public API for a <Router> that stores location in memory.
 */

var react_router_MemoryRouter =
/*#__PURE__*/
function (_React$Component) {
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
    return external_React_default.a.createElement(react_router_Router, {
      history: this.history,
      children: this.props.children
    });
  };

  return MemoryRouter;
}(external_React_default.a.Component);

if (false) {}

var react_router_Lifecycle =
/*#__PURE__*/
function (_React$Component) {
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
  return external_React_default.a.createElement(react_router_context.Consumer, null, function (context) {
    !context ?  false ? undefined : Object(tiny_invariant_esm["a" /* default */])(false) : void 0;
    if (!when || context.staticContext) return null;
    var method = context.history.block;
    return external_React_default.a.createElement(react_router_Lifecycle, {
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
  return external_React_default.a.createElement(react_router_context.Consumer, null, function (context) {
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

    return external_React_default.a.createElement(react_router_Lifecycle, {
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


var react_router_Route =
/*#__PURE__*/
function (_React$Component) {
  Object(inheritsLoose["a" /* default */])(Route, _React$Component);

  function Route() {
    return _React$Component.apply(this, arguments) || this;
  }

  var _proto = Route.prototype;

  _proto.render = function render() {
    var _this = this;

    return external_React_default.a.createElement(react_router_context.Consumer, null, function (context$1) {
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

      if (Array.isArray(children) && children.length === 0) {
        children = null;
      }

      return external_React_default.a.createElement(react_router_context.Provider, {
        value: props
      }, props.match ? children ? typeof children === "function" ?  false ? undefined : children(props) : children : component ? external_React_default.a.createElement(component, props) : render ? render(props) : null : typeof children === "function" ?  false ? undefined : children(props) : null);
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


var react_router_StaticRouter =
/*#__PURE__*/
function (_React$Component) {
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
    return external_React_default.a.createElement(react_router_Router, Object(esm_extends["a" /* default */])({}, rest, {
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

var react_router_Switch =
/*#__PURE__*/
function (_React$Component) {
  Object(inheritsLoose["a" /* default */])(Switch, _React$Component);

  function Switch() {
    return _React$Component.apply(this, arguments) || this;
  }

  var _proto = Switch.prototype;

  _proto.render = function render() {
    var _this = this;

    return external_React_default.a.createElement(react_router_context.Consumer, null, function (context) {
      !context ?  false ? undefined : Object(tiny_invariant_esm["a" /* default */])(false) : void 0;
      var location = _this.props.location || context.location;
      var element, match; // We use React.Children.forEach instead of React.Children.toArray().find()
      // here because toArray adds keys to all child elements and we do not want
      // to trigger an unmount/remount for two <Route>s that render the same
      // component at different URLs.

      external_React_default.a.Children.forEach(_this.props.children, function (child) {
        if (match == null && external_React_default.a.isValidElement(child)) {
          element = child;
          var path = child.props.path || child.props.from;
          match = path ? matchPath(location.pathname, Object(esm_extends["a" /* default */])({}, child.props, {
            path: path
          })) : context.match;
        }
      });
      return match ? external_React_default.a.cloneElement(element, {
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

    return external_React_default.a.createElement(react_router_context.Consumer, null, function (context) {
      !context ?  false ? undefined : Object(tiny_invariant_esm["a" /* default */])(false) : void 0;
      return external_React_default.a.createElement(Component, Object(esm_extends["a" /* default */])({}, remainingProps, context, {
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

  return useContext(react_router_context).history;
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

  return path ? matchPath(useLocation().pathname, path) : useContext(react_router_context).match;
}

if (false) { var secondaryBuildName, initialBuildName, buildNames, react_router_key, global; }


//# sourceMappingURL=react-router.js.map

// EXTERNAL MODULE: external "lodash"
var external_lodash_ = __webpack_require__(2);

// EXTERNAL MODULE: external {"this":["wc","components"]}
var external_this_wc_components_ = __webpack_require__(63);

// EXTERNAL MODULE: external {"this":["wc","navigation"]}
var external_this_wc_navigation_ = __webpack_require__(22);

// EXTERNAL MODULE: ./client/settings/index.js
var settings = __webpack_require__(26);

// EXTERNAL MODULE: external {"this":["wc","data"]}
var external_this_wc_data_ = __webpack_require__(51);

// EXTERNAL MODULE: ./client/layout/style.scss
var layout_style = __webpack_require__(434);

// EXTERNAL MODULE: ./node_modules/qs/lib/index.js
var lib = __webpack_require__(58);

// EXTERNAL MODULE: external {"this":["wp","hooks"]}
var external_this_wp_hooks_ = __webpack_require__(48);

// EXTERNAL MODULE: external {"this":["wp","i18n"]}
var external_this_wp_i18n_ = __webpack_require__(3);

// EXTERNAL MODULE: ./client/analytics/report/get-reports.js
var get_reports = __webpack_require__(199);

// CONCATENATED MODULE: ./client/layout/controller.js







function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function () { var Super = getPrototypeOf_default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = getPrototypeOf_default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return possibleConstructorReturn_default()(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/**
 * External dependencies
 */





/**
 * WooCommerce dependencies
 */



/**
 * Internal dependencies
 */

var AnalyticsReport = Object(external_this_wp_element_["lazy"])(function () {
  return Promise.resolve(/* import() */).then(__webpack_require__.bind(null, 200));
});
var AnalyticsSettings = Object(external_this_wp_element_["lazy"])(function () {
  return __webpack_require__.e(/* import() | analytics-settings */ 18).then(__webpack_require__.bind(null, 906));
});
var Dashboard = Object(external_this_wp_element_["lazy"])(function () {
  return __webpack_require__.e(/* import() | dashboard */ 24).then(__webpack_require__.bind(null, 900));
});
var DevDocs = Object(external_this_wp_element_["lazy"])(function () {
  return Promise.all(/* import() | devdocs */[__webpack_require__.e(2), __webpack_require__.e(44), __webpack_require__.e(28)]).then(__webpack_require__.bind(null, 907));
});
var Homepage = Object(external_this_wp_element_["lazy"])(function () {
  return __webpack_require__.e(/* import() | homepage */ 29).then(__webpack_require__.bind(null, 908));
});
var MarketingOverview = Object(external_this_wp_element_["lazy"])(function () {
  return Promise.all(/* import() | marketing-overview */[__webpack_require__.e(2), __webpack_require__.e(45), __webpack_require__.e(32)]).then(__webpack_require__.bind(null, 905));
});

var TIME_EXCLUDED_SCREENS_FILTER = 'woocommerce_admin_time_excluded_screens';
var PAGES_FILTER = 'woocommerce_admin_pages_list';
var controller_getPages = function getPages() {
  var pages = [];
  var initialBreadcrumbs = [['', wcSettings.woocommerceTranslation]];

  if (false) {}

  if (true) {
    pages.push({
      container: Dashboard,
      path: '/',
      breadcrumbs: [].concat(initialBreadcrumbs, [Object(external_this_wp_i18n_["__"])('Dashboard', 'woocommerce')]),
      wpOpenMenu: 'toplevel_page_woocommerce'
    });
  }

  if (false) {}

  if (true) {
    if (false) {}

    var ReportWpOpenMenu = "toplevel_page_wc-admin-path--analytics-".concat( false ? undefined : 'revenue');
    pages.push({
      container: AnalyticsSettings,
      path: '/analytics/settings',
      breadcrumbs: [].concat(initialBreadcrumbs, [['/analytics/revenue', Object(external_this_wp_i18n_["__"])('Analytics', 'woocommerce')], Object(external_this_wp_i18n_["__"])('Settings', 'woocommerce')]),
      wpOpenMenu: ReportWpOpenMenu
    });
    pages.push({
      container: AnalyticsReport,
      path: '/customers',
      breadcrumbs: [].concat(initialBreadcrumbs, [Object(external_this_wp_i18n_["__"])('Customers', 'woocommerce')]),
      wpOpenMenu: 'toplevel_page_woocommerce'
    });
    pages.push({
      container: AnalyticsReport,
      path: '/analytics/:report',
      breadcrumbs: function breadcrumbs(_ref2) {
        var match = _ref2.match;
        var report = Object(external_lodash_["find"])(Object(get_reports["a" /* default */])(), {
          report: match.params.report
        });

        if (!report) {
          return [];
        }

        return [].concat(initialBreadcrumbs, [['/analytics/revenue', Object(external_this_wp_i18n_["__"])('Analytics', 'woocommerce')], report.title]);
      },
      wpOpenMenu: ReportWpOpenMenu
    });
  }

  if (true) {
    pages.push({
      container: MarketingOverview,
      path: '/marketing',
      breadcrumbs: [].concat(initialBreadcrumbs, [Object(external_this_wp_i18n_["__"])('Marketing', 'woocommerce')]),
      wpOpenMenu: 'toplevel_page_wc-admin-path--marketing'
    });
  }

  return Object(external_this_wp_hooks_["applyFilters"])(PAGES_FILTER, pages);
};
var controller_Controller = /*#__PURE__*/function (_Component) {
  inherits_default()(Controller, _Component);

  var _super = _createSuper(Controller);

  function Controller() {
    classCallCheck_default()(this, Controller);

    return _super.apply(this, arguments);
  }

  createClass_default()(Controller, [{
    key: "componentDidMount",
    value: function componentDidMount() {
      window.document.documentElement.scrollTop = 0;
    }
  }, {
    key: "componentDidUpdate",
    value: function componentDidUpdate(prevProps) {
      var prevQuery = this.getQuery(prevProps.location.search);
      var prevBaseQuery = Object(external_lodash_["omit"])(this.getQuery(prevProps.location.search), 'paged');
      var baseQuery = Object(external_lodash_["omit"])(this.getQuery(this.props.location.search), 'paged');

      if (prevQuery.paged > 1 && !Object(external_lodash_["isEqual"])(prevBaseQuery, baseQuery)) {
        Object(external_this_wc_navigation_["getHistory"])().replace(Object(external_this_wc_navigation_["getNewPath"])({
          paged: 1
        }));
      }

      if (prevProps.match.url !== this.props.match.url) {
        window.document.documentElement.scrollTop = 0;
      }
    }
  }, {
    key: "getQuery",
    value: function getQuery(searchString) {
      if (!searchString) {
        return {};
      }

      var search = searchString.substring(1);
      return Object(lib["parse"])(search);
    }
  }, {
    key: "render",
    value: function render() {
      var _this$props = this.props,
          page = _this$props.page,
          match = _this$props.match,
          location = _this$props.location;
      var url = match.url,
          params = match.params;
      var query = this.getQuery(location.search);
      window.wpNavMenuUrlUpdate(query);
      window.wpNavMenuClassChange(page, url);
      return Object(external_this_wp_element_["createElement"])(external_this_wp_element_["Suspense"], {
        fallback: Object(external_this_wp_element_["createElement"])(external_this_wc_components_["Spinner"], null)
      }, Object(external_this_wp_element_["createElement"])(page.container, {
        params: params,
        path: url,
        pathMatch: page.path,
        query: query
      }));
    }
  }]);

  return Controller;
}(external_this_wp_element_["Component"]);
/**
 * Update an anchor's link in sidebar to include persisted queries. Leave excluded screens
 * as is.
 *
 * @param {HTMLElement} item - Sidebar anchor link.
 * @param {Object} nextQuery - A query object to be added to updated hrefs.
 * @param {Array} excludedScreens - wc-admin screens to avoid updating.
 */

function updateLinkHref(item, nextQuery, excludedScreens) {
  var isWCAdmin = /admin.php\?page=wc-admin/.test(item.href);

  if (isWCAdmin) {
    var search = Object(external_lodash_["last"])(item.href.split('?'));
    var query = Object(lib["parse"])(search);
    var path = query.path || 'dashboard';
    var screen = path.replace('/analytics', '').replace('/', '');
    var isExcludedScreen = excludedScreens.includes(screen);
    var href = 'admin.php?' + Object(lib["stringify"])(Object.assign(query, isExcludedScreen ? {} : nextQuery)); // Replace the href so you can see the url on hover.

    item.href = href;

    item.onclick = function (e) {
      e.preventDefault();
      Object(external_this_wc_navigation_["getHistory"])().push(href);
    };
  }
} // Update's wc-admin links in wp-admin menu

window.wpNavMenuUrlUpdate = function (query) {
  var excludedScreens = Object(external_this_wp_hooks_["applyFilters"])(TIME_EXCLUDED_SCREENS_FILTER, ['devdocs', 'stock', 'settings', 'customers']);
  var nextQuery = Object(external_this_wc_navigation_["getPersistedQuery"])(query);
  Array.from(document.querySelectorAll('#adminmenu a')).forEach(function (item) {
    return updateLinkHref(item, nextQuery, excludedScreens);
  });
}; // When the route changes, we need to update wp-admin's menu with the correct section & current link


window.wpNavMenuClassChange = function (page, url) {
  Array.from(document.getElementsByClassName('current')).forEach(function (item) {
    item.classList.remove('current');
  });
  var submenu = Array.from(document.querySelectorAll('.wp-has-current-submenu'));
  submenu.forEach(function (element) {
    element.classList.remove('wp-has-current-submenu');
    element.classList.remove('wp-menu-open');
    element.classList.remove('selected');
    element.classList.add('wp-not-current-submenu');
    element.classList.add('menu-top');
  });
  var pageUrl = url === '/' ? 'admin.php?page=wc-admin' : 'admin.php?page=wc-admin&path=' + encodeURIComponent(url);
  var currentItemsSelector = url === '/' ? "li > a[href$=\"".concat(pageUrl, "\"], li > a[href*=\"").concat(pageUrl, "?\"]") : "li > a[href*=\"".concat(pageUrl, "\"]");
  var currentItems = document.querySelectorAll(currentItemsSelector);
  Array.from(currentItems).forEach(function (item) {
    item.parentElement.classList.add('current');
  });

  if (page.wpOpenMenu) {
    var currentMenu = document.querySelector('#' + page.wpOpenMenu);
    currentMenu.classList.remove('wp-not-current-submenu');
    currentMenu.classList.add('wp-has-current-submenu');
    currentMenu.classList.add('wp-menu-open');
    currentMenu.classList.add('current');
  }

  var wpWrap = document.querySelector('#wpwrap');
  wpWrap.classList.remove('wp-responsive-open');
};
// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/assertThisInitialized.js
var assertThisInitialized = __webpack_require__(59);
var assertThisInitialized_default = /*#__PURE__*/__webpack_require__.n(assertThisInitialized);

// EXTERNAL MODULE: ./node_modules/classnames/index.js
var classnames = __webpack_require__(10);
var classnames_default = /*#__PURE__*/__webpack_require__.n(classnames);

// EXTERNAL MODULE: external {"this":["wp","htmlEntities"]}
var external_this_wp_htmlEntities_ = __webpack_require__(69);

// EXTERNAL MODULE: ./client/header/style.scss
var header_style = __webpack_require__(436);

// EXTERNAL MODULE: ./node_modules/react-click-outside/dist/index.js
var dist = __webpack_require__(412);
var dist_default = /*#__PURE__*/__webpack_require__.n(dist);

// EXTERNAL MODULE: ./node_modules/@wordpress/components/build-module/icon-button/index.js
var icon_button = __webpack_require__(85);

// EXTERNAL MODULE: ./node_modules/@wordpress/components/build-module/navigable-container/menu.js + 1 modules
var menu = __webpack_require__(424);

// EXTERNAL MODULE: ./node_modules/gridicons/dist/pages.js
var dist_pages = __webpack_require__(413);
var pages_default = /*#__PURE__*/__webpack_require__.n(dist_pages);

// EXTERNAL MODULE: ./node_modules/gridicons/dist/cross-small.js
var cross_small = __webpack_require__(414);
var cross_small_default = /*#__PURE__*/__webpack_require__.n(cross_small);

// EXTERNAL MODULE: ./client/header/activity-panel/style.scss
var activity_panel_style = __webpack_require__(438);

// CONCATENATED MODULE: ./client/header/activity-panel/toggle-bubble.js


/**
 * External dependencies
 */



var toggle_bubble_ActivityPanelToggleBubble = function ActivityPanelToggleBubble(_ref) {
  var _ref$height = _ref.height,
      height = _ref$height === void 0 ? 24 : _ref$height,
      _ref$width = _ref.width,
      width = _ref$width === void 0 ? 24 : _ref$width,
      _ref$hasUnread = _ref.hasUnread,
      hasUnread = _ref$hasUnread === void 0 ? false : _ref$hasUnread;
  var classes = classnames_default()('woocommerce-layout__activity-panel-toggle-bubble', {
    'has-unread': hasUnread
  });
  /* eslint-disable max-len */

  return Object(external_this_wp_element_["createElement"])("div", {
    className: classes
  }, Object(external_this_wp_element_["createElement"])("svg", {
    height: height,
    width: width,
    viewBox: "0 0 24 24"
  }, Object(external_this_wp_element_["createElement"])("path", {
    d: "M18.9 2H5.1C3.4 2 2 3.4 2 5.1v10.7C2 17.6 3.4 19 5.1 19H9l6 3-1-3h4.9c1.7 0 3.1-1.4 3.1-3.1V5.1C22 3.4 20.6 2 18.9 2zm-1.5 4.5c-.4.8-.8 2.1-1 3.9-.3 1.8-.4 3.1-.3 4.1 0 .3 0 .5-.1.7-.1.2-.3.4-.6.4s-.6-.1-.9-.4c-1-1-1.8-2.6-2.4-4.6-.7 1.4-1.2 2.4-1.6 3.1-.6 1.2-1.2 1.8-1.6 1.9-.3 0-.5-.2-.8-.7-.5-1.4-1.1-4.2-1.7-8.2 0-.3 0-.5.2-.7.1-.2.4-.3.7-.4.5 0 .9.2.9.8.3 2.3.7 4.2 1.1 5.7l2.4-4.5c.2-.4.4-.6.8-.6.5 0 .8.3.9.9.3 1.4.6 2.6 1 3.7.3-2.7.8-4.7 1.4-5.9.2-.3.4-.5.7-.5.2 0 .5.1.7.2.2.2.3.4.3.6 0 .2 0 .4-.1.5z"
  })));
  /* eslint-enable max-len */
};

toggle_bubble_ActivityPanelToggleBubble.propTypes = {
  height: prop_types_default.a.number,
  width: prop_types_default.a.number,
  hasUnread: prop_types_default.a.bool
};
/* harmony default export */ var toggle_bubble = (toggle_bubble_ActivityPanelToggleBubble);
// EXTERNAL MODULE: ./client/analytics/settings/config.js + 1 modules
var config = __webpack_require__(263);

// CONCATENATED MODULE: ./client/header/activity-panel/unread-indicators.js
/**
 * WooCommerce dependencies
 */

/**
 * Internal dependencies
 */



function getUnreadNotes(select) {
  var _select = select('wc-api'),
      getCurrentUserData = _select.getCurrentUserData,
      getNotes = _select.getNotes,
      getNotesError = _select.getNotesError,
      isGetNotesRequesting = _select.isGetNotesRequesting;

  var userData = getCurrentUserData();

  if (!userData) {
    return null;
  }

  var notesQuery = {
    page: 1,
    per_page: 1,
    type: 'info,warning',
    orderby: 'date',
    order: 'desc'
  }; // Disable eslint rule requiring `latestNote` to be defined below because the next two statements
  // depend on `getNotes` to have been called.
  // eslint-disable-next-line @wordpress/no-unused-vars-before-return

  var latestNote = getNotes(notesQuery);
  var isError = Boolean(getNotesError(notesQuery));
  var isRequesting = isGetNotesRequesting(notesQuery);

  if (isError || isRequesting) {
    return null;
  }

  return latestNote[0] && new Date(latestNote[0].date_created_gmt + 'Z').getTime() > userData.activity_panel_inbox_last_read;
}
function getUnreadOrders(select) {
  var _select2 = select('wc-api'),
      getItems = _select2.getItems,
      getItemsTotalCount = _select2.getItemsTotalCount,
      getItemsError = _select2.getItemsError,
      isGetItemsRequesting = _select2.isGetItemsRequesting;

  var _select3 = select(external_this_wc_data_["SETTINGS_STORE_NAME"]),
      getMutableSetting = _select3.getSetting;

  var _getMutableSetting = getMutableSetting('wc_admin', 'wcAdminSettings', {}),
      _getMutableSetting$wo = _getMutableSetting.woocommerce_actionable_order_statuses,
      orderStatuses = _getMutableSetting$wo === void 0 ? config["a" /* DEFAULT_ACTIONABLE_STATUSES */] : _getMutableSetting$wo;

  if (!orderStatuses.length) {
    return false;
  }

  var ordersQuery = {
    page: 1,
    per_page: 1,
    // Core endpoint requires per_page > 0.
    status: orderStatuses,
    _fields: ['id']
  };
  getItems('orders', ordersQuery); // Disable eslint rule requiring `latestNote` to be defined below because the next two statements
  // depend on `getItemsTotalCount` to have been called.
  // eslint-disable-next-line @wordpress/no-unused-vars-before-return

  var totalOrders = getItemsTotalCount('orders', ordersQuery);
  var isError = Boolean(getItemsError('orders', ordersQuery));
  var isRequesting = isGetItemsRequesting('orders', ordersQuery);

  if (isError || isRequesting) {
    return null;
  }

  return totalOrders > 0;
}
function getUnapprovedReviews(select) {
  var _select4 = select('wc-api'),
      getReviewsTotalCount = _select4.getReviewsTotalCount,
      getReviewsError = _select4.getReviewsError,
      isGetReviewsRequesting = _select4.isGetReviewsRequesting;

  var reviewsEnabled = Object(settings["g" /* getSetting */])('reviewsEnabled');

  if (reviewsEnabled === 'yes') {
    var actionableReviewsQuery = {
      page: 1,
      // @todo we are not using this review, so when the endpoint supports it,
      // it could be replaced with `per_page: 0`
      per_page: 1,
      status: 'hold'
    };
    var totalActionableReviews = getReviewsTotalCount(actionableReviewsQuery);
    var isActionableReviewsError = Boolean(getReviewsError(actionableReviewsQuery));
    var isActionableReviewsRequesting = isGetReviewsRequesting(actionableReviewsQuery);

    if (!isActionableReviewsError && !isActionableReviewsRequesting) {
      return totalActionableReviews > 0;
    }
  }

  return false;
}
function getUnreadStock() {
  return Object(settings["g" /* getSetting */])('hasLowStock', false);
}
// EXTERNAL MODULE: ./client/lib/tracks.js
var tracks = __webpack_require__(79);

// EXTERNAL MODULE: ./client/wc-api/with-select.js
var with_select = __webpack_require__(101);

// CONCATENATED MODULE: ./client/header/activity-panel/index.js








function activity_panel_createSuper(Derived) { var hasNativeReflectConstruct = activity_panel_isNativeReflectConstruct(); return function () { var Super = getPrototypeOf_default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = getPrototypeOf_default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return possibleConstructorReturn_default()(this, result); }; }

function activity_panel_isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/**
 * External dependencies
 */









/**
 * Internal dependencies
 */





var InboxPanel = Object(external_this_wp_element_["lazy"])(function () {
  return Promise.all(/* import() | activity-panels-inbox */[__webpack_require__.e(2), __webpack_require__.e(3), __webpack_require__.e(6)]).then(__webpack_require__.bind(null, 909));
});
var OrdersPanel = Object(external_this_wp_element_["lazy"])(function () {
  return Promise.all(/* import() | activity-panels-orders */[__webpack_require__.e(2), __webpack_require__.e(7)]).then(__webpack_require__.bind(null, 911));
});
var StockPanel = Object(external_this_wp_element_["lazy"])(function () {
  return Promise.all(/* import() | activity-panels-stock */[__webpack_require__.e(2), __webpack_require__.e(8)]).then(__webpack_require__.bind(null, 914));
});
var ReviewsPanel = Object(external_this_wp_element_["lazy"])(function () {
  return Promise.all(/* import() | activity-panels-inbox */[__webpack_require__.e(2), __webpack_require__.e(3), __webpack_require__.e(6)]).then(__webpack_require__.bind(null, 902));
});


var manageStock = Object(settings["g" /* getSetting */])('manageStock', 'no');
var activity_panel_reviewsEnabled = Object(settings["g" /* getSetting */])('reviewsEnabled', 'no');

var activity_panel_ActivityPanel = /*#__PURE__*/function (_Component) {
  inherits_default()(ActivityPanel, _Component);

  var _super = activity_panel_createSuper(ActivityPanel);

  function ActivityPanel() {
    var _this;

    classCallCheck_default()(this, ActivityPanel);

    _this = _super.apply(this, arguments);
    _this.togglePanel = _this.togglePanel.bind(assertThisInitialized_default()(_this));
    _this.clearPanel = _this.clearPanel.bind(assertThisInitialized_default()(_this));
    _this.toggleMobile = _this.toggleMobile.bind(assertThisInitialized_default()(_this));
    _this.renderTab = _this.renderTab.bind(assertThisInitialized_default()(_this));
    _this.state = {
      isPanelOpen: false,
      mobileOpen: false,
      currentTab: '',
      isPanelSwitching: false
    };
    return _this;
  }

  createClass_default()(ActivityPanel, [{
    key: "togglePanel",
    value: function togglePanel(tabName) {
      var _this$state = this.state,
          isPanelOpen = _this$state.isPanelOpen,
          currentTab = _this$state.currentTab; // If a panel is being opened, or if an existing panel is already open and a different one is being opened, record a track.

      if (!isPanelOpen || tabName !== currentTab) {
        Object(tracks["b" /* recordEvent */])('activity_panel_open', {
          tab: tabName
        });
      }

      this.setState(function (state) {
        if (tabName === state.currentTab || state.currentTab === '') {
          return {
            isPanelOpen: !state.isPanelOpen,
            currentTab: tabName,
            mobileOpen: !state.isPanelOpen
          };
        }

        return {
          currentTab: tabName,
          isPanelSwitching: true
        };
      });
    }
  }, {
    key: "clearPanel",
    value: function clearPanel() {
      this.setState(function (_ref) {
        var isPanelOpen = _ref.isPanelOpen;
        return isPanelOpen ? {
          isPanelSwitching: false
        } : {
          currentTab: ''
        };
      });
    } // On smaller screen, the panel buttons are hidden behind a toggle.

  }, {
    key: "toggleMobile",
    value: function toggleMobile() {
      var tabs = this.getTabs();
      this.setState(function (state) {
        return {
          mobileOpen: !state.mobileOpen,
          currentTab: state.mobileOpen ? '' : tabs[0].name,
          isPanelOpen: !state.mobileOpen
        };
      });
    }
  }, {
    key: "handleClickOutside",
    value: function handleClickOutside() {
      var _this$state2 = this.state,
          isPanelOpen = _this$state2.isPanelOpen,
          currentTab = _this$state2.currentTab;

      if (isPanelOpen) {
        this.togglePanel(currentTab);
      }
    } // @todo Pull in dynamic unread status/count

  }, {
    key: "getTabs",
    value: function getTabs() {
      var _this$props = this.props,
          hasUnreadNotes = _this$props.hasUnreadNotes,
          hasUnreadOrders = _this$props.hasUnreadOrders,
          hasUnapprovedReviews = _this$props.hasUnapprovedReviews,
          hasUnreadStock = _this$props.hasUnreadStock;
      return [{
        name: 'inbox',
        title: Object(external_this_wp_i18n_["__"])('Inbox', 'woocommerce'),
        icon: Object(external_this_wp_element_["createElement"])("i", {
          className: "material-icons-outlined"
        }, "inbox"),
        unread: hasUnreadNotes
      }, {
        name: 'orders',
        title: Object(external_this_wp_i18n_["__"])('Orders', 'woocommerce'),
        icon: Object(external_this_wp_element_["createElement"])(pages_default.a, null),
        unread: hasUnreadOrders
      }, manageStock === 'yes' ? {
        name: 'stock',
        title: Object(external_this_wp_i18n_["__"])('Stock', 'woocommerce'),
        icon: Object(external_this_wp_element_["createElement"])("i", {
          className: "material-icons-outlined"
        }, "widgets"),
        unread: hasUnreadStock
      } : null, activity_panel_reviewsEnabled === 'yes' ? {
        name: 'reviews',
        title: Object(external_this_wp_i18n_["__"])('Reviews', 'woocommerce'),
        icon: Object(external_this_wp_element_["createElement"])("i", {
          className: "material-icons-outlined"
        }, "star_border"),
        unread: hasUnapprovedReviews
      } : null].filter(Boolean);
    }
  }, {
    key: "getPanelContent",
    value: function getPanelContent(tab) {
      switch (tab) {
        case 'inbox':
          return Object(external_this_wp_element_["createElement"])(InboxPanel, null);

        case 'orders':
          var hasUnreadOrders = this.props.hasUnreadOrders;
          return Object(external_this_wp_element_["createElement"])(OrdersPanel, {
            hasActionableOrders: hasUnreadOrders
          });

        case 'stock':
          return Object(external_this_wp_element_["createElement"])(StockPanel, null);

        case 'reviews':
          var hasUnapprovedReviews = this.props.hasUnapprovedReviews;
          return Object(external_this_wp_element_["createElement"])(ReviewsPanel, {
            hasUnapprovedReviews: hasUnapprovedReviews
          });

        default:
          return null;
      }
    }
  }, {
    key: "renderPanel",
    value: function renderPanel() {
      var _this$state3 = this.state,
          isPanelOpen = _this$state3.isPanelOpen,
          currentTab = _this$state3.currentTab,
          isPanelSwitching = _this$state3.isPanelSwitching;
      var tab = Object(external_lodash_["find"])(this.getTabs(), {
        name: currentTab
      });

      if (!tab) {
        return Object(external_this_wp_element_["createElement"])("div", {
          className: "woocommerce-layout__activity-panel-wrapper"
        });
      }

      var classNames = classnames_default()('woocommerce-layout__activity-panel-wrapper', {
        'is-open': isPanelOpen,
        'is-switching': isPanelSwitching
      });
      return Object(external_this_wp_element_["createElement"])("div", {
        className: classNames,
        tabIndex: 0,
        role: "tabpanel",
        "aria-label": tab.title,
        onTransitionEnd: this.clearPanel,
        onAnimationEnd: this.clearPanel
      }, Object(external_this_wp_element_["createElement"])("div", {
        className: "woocommerce-layout__activity-panel-content",
        key: 'activity-panel-' + currentTab,
        id: 'activity-panel-' + currentTab
      }, Object(external_this_wp_element_["createElement"])(external_this_wp_element_["Suspense"], {
        fallback: Object(external_this_wp_element_["createElement"])(external_this_wc_components_["Spinner"], null)
      }, this.getPanelContent(currentTab))));
    }
  }, {
    key: "renderTab",
    value: function renderTab(tab, i) {
      var _this$state4 = this.state,
          currentTab = _this$state4.currentTab,
          isPanelOpen = _this$state4.isPanelOpen;
      var className = classnames_default()('woocommerce-layout__activity-panel-tab', {
        'is-active': isPanelOpen && tab.name === currentTab,
        'has-unread': tab.unread
      });
      var selected = tab.name === currentTab;
      var tabIndex = -1; // Only make this item tabbable if it is the currently selected item, or the panel is closed and the item is the first item.

      if (selected || !isPanelOpen && i === 0) {
        tabIndex = null;
      }

      return Object(external_this_wp_element_["createElement"])(icon_button["a" /* default */], {
        role: "tab",
        className: className,
        tabIndex: tabIndex,
        "aria-selected": selected,
        "aria-controls": 'activity-panel-' + tab.name,
        key: 'activity-panel-tab-' + tab.name,
        id: 'activity-panel-tab-' + tab.name,
        onClick: Object(external_lodash_["partial"])(this.togglePanel, tab.name),
        icon: tab.icon
      }, tab.title, ' ', tab.unread && Object(external_this_wp_element_["createElement"])("span", {
        className: "screen-reader-text"
      }, Object(external_this_wp_i18n_["__"])('unread activity', 'woocommerce')));
    }
  }, {
    key: "render",
    value: function render() {
      var tabs = this.getTabs();
      var mobileOpen = this.state.mobileOpen;
      var headerId = Object(external_lodash_["uniqueId"])('activity-panel-header_');
      var panelClasses = classnames_default()('woocommerce-layout__activity-panel', {
        'is-mobile-open': this.state.mobileOpen
      });
      var hasUnread = tabs.some(function (tab) {
        return tab.unread;
      });
      var viewLabel = hasUnread ? Object(external_this_wp_i18n_["__"])('View Activity Panel, you have unread activity', 'woocommerce') : Object(external_this_wp_i18n_["__"])('View Activity Panel', 'woocommerce');
      return Object(external_this_wp_element_["createElement"])("div", null, Object(external_this_wp_element_["createElement"])(external_this_wc_components_["H"], {
        id: headerId,
        className: "screen-reader-text"
      }, Object(external_this_wp_i18n_["__"])('Store Activity', 'woocommerce')), Object(external_this_wp_element_["createElement"])(external_this_wc_components_["Section"], {
        component: "aside",
        id: "woocommerce-activity-panel",
        "aria-labelledby": headerId
      }, Object(external_this_wp_element_["createElement"])(icon_button["a" /* default */], {
        onClick: this.toggleMobile,
        icon: mobileOpen ? Object(external_this_wp_element_["createElement"])(cross_small_default.a, null) : Object(external_this_wp_element_["createElement"])(toggle_bubble, {
          hasUnread: hasUnread
        }),
        label: mobileOpen ? Object(external_this_wp_i18n_["__"])('Close Activity Panel', 'woocommerce') : viewLabel,
        "aria-expanded": mobileOpen,
        tooltip: false,
        className: "woocommerce-layout__activity-panel-mobile-toggle"
      }), Object(external_this_wp_element_["createElement"])("div", {
        className: panelClasses
      }, Object(external_this_wp_element_["createElement"])(menu["a" /* default */], {
        role: "tablist",
        orientation: "horizontal",
        className: "woocommerce-layout__activity-panel-tabs"
      }, tabs && tabs.map(this.renderTab)), this.renderPanel())));
    }
  }]);

  return ActivityPanel;
}(external_this_wp_element_["Component"]);

/* harmony default export */ var activity_panel = (Object(with_select["a" /* default */])(function (select) {
  var hasUnreadNotes = getUnreadNotes(select);
  var hasUnreadOrders = getUnreadOrders(select);
  var hasUnreadStock = getUnreadStock();
  var hasUnapprovedReviews = getUnapprovedReviews(select);
  return {
    hasUnreadNotes: hasUnreadNotes,
    hasUnreadOrders: hasUnreadOrders,
    hasUnreadStock: hasUnreadStock,
    hasUnapprovedReviews: hasUnapprovedReviews
  };
})(dist_default()(activity_panel_ActivityPanel)));
// CONCATENATED MODULE: ./client/header/index.js








function header_createSuper(Derived) { var hasNativeReflectConstruct = header_isNativeReflectConstruct(); return function () { var Super = getPrototypeOf_default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = getPrototypeOf_default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return possibleConstructorReturn_default()(this, result); }; }

function header_isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/**
 * External dependencies
 */





/**
 * WooCommerce dependencies
 */




/**
 * Internal dependencies
 */





var header_Header = /*#__PURE__*/function (_Component) {
  inherits_default()(Header, _Component);

  var _super = header_createSuper(Header);

  function Header() {
    var _this;

    classCallCheck_default()(this, Header);

    _this = _super.call(this);
    _this.state = {
      isScrolled: false
    };
    _this.headerRef = Object(external_this_wp_element_["createRef"])();
    _this.onWindowScroll = _this.onWindowScroll.bind(assertThisInitialized_default()(_this));
    _this.updateIsScrolled = _this.updateIsScrolled.bind(assertThisInitialized_default()(_this));
    _this.trackLinkClick = _this.trackLinkClick.bind(assertThisInitialized_default()(_this));
    _this.updateDocumentTitle = _this.updateDocumentTitle.bind(assertThisInitialized_default()(_this));
    return _this;
  }

  createClass_default()(Header, [{
    key: "componentDidMount",
    value: function componentDidMount() {
      this.threshold = this.headerRef.current.offsetTop;
      window.addEventListener('scroll', this.onWindowScroll);
      this.updateIsScrolled();
    }
  }, {
    key: "componentWillUnmount",
    value: function componentWillUnmount() {
      window.removeEventListener('scroll', this.onWindowScroll);
      window.cancelAnimationFrame(this.handle);
    }
  }, {
    key: "onWindowScroll",
    value: function onWindowScroll() {
      this.handle = window.requestAnimationFrame(this.updateIsScrolled);
    }
  }, {
    key: "updateIsScrolled",
    value: function updateIsScrolled() {
      var isScrolled = window.pageYOffset > this.threshold - 20;

      if (isScrolled !== this.state.isScrolled) {
        this.setState({
          isScrolled: isScrolled
        });
      }
    }
  }, {
    key: "trackLinkClick",
    value: function trackLinkClick(event) {
      var href = event.target.closest('a').getAttribute('href');
      Object(tracks["b" /* recordEvent */])('navbar_breadcrumb_click', {
        href: href,
        text: event.target.innerText
      });
    }
  }, {
    key: "updateDocumentTitle",
    value: function updateDocumentTitle() {
      var _this$props = this.props,
          sections = _this$props.sections,
          isEmbedded = _this$props.isEmbedded; // Don't modify the document title on existing WooCommerce pages.

      if (isEmbedded) {
        return;
      }

      var _sections = Array.isArray(sections) ? sections : [sections];

      var documentTitle = _sections.map(function (section) {
        return Array.isArray(section) ? section[1] : section;
      }).reverse().join(' &lsaquo; ');

      document.title = Object(external_this_wp_htmlEntities_["decodeEntities"])(Object(external_this_wp_i18n_["sprintf"])(Object(external_this_wp_i18n_["__"])('%1$s &lsaquo; %2$s &#8212; WooCommerce', 'woocommerce'), documentTitle, Object(settings["g" /* getSetting */])('siteTitle', '')));
    }
  }, {
    key: "render",
    value: function render() {
      var _this2 = this;

      var _this$props2 = this.props,
          sections = _this$props2.sections,
          isEmbedded = _this$props2.isEmbedded;
      var isScrolled = this.state.isScrolled;

      var _sections = Array.isArray(sections) ? sections : [sections];

      this.updateDocumentTitle();
      var className = classnames_default()('woocommerce-layout__header', {
        'is-scrolled': isScrolled
      });
      return Object(external_this_wp_element_["createElement"])("div", {
        className: className,
        ref: this.headerRef
      }, Object(external_this_wp_element_["createElement"])("h1", {
        className: "woocommerce-layout__header-breadcrumbs"
      }, _sections.map(function (section, i) {
        var sectionPiece = Array.isArray(section) ? Object(external_this_wp_element_["createElement"])(external_this_wc_components_["Link"], {
          href: isEmbedded ? Object(settings["f" /* getAdminLink */])(section[0]) : Object(external_this_wc_navigation_["getNewPath"])({}, section[0], {}),
          type: isEmbedded ? 'wp-admin' : 'wc-admin',
          onClick: _this2.trackLinkClick
        }, section[1]) : section;
        return Object(external_this_wp_element_["createElement"])("span", {
          key: i
        }, Object(external_this_wp_htmlEntities_["decodeEntities"])(sectionPiece));
      })),  true && Object(external_this_wp_element_["createElement"])(activity_panel, null));
    }
  }]);

  return Header;
}(external_this_wp_element_["Component"]);

header_Header.propTypes = {
  sections: prop_types_default.a.node.isRequired,
  isEmbedded: prop_types_default.a.bool
};
header_Header.defaultProps = {
  isEmbedded: false
};
/* harmony default export */ var header = (header_Header);
// CONCATENATED MODULE: ./client/layout/notices.js







function notices_createSuper(Derived) { var hasNativeReflectConstruct = notices_isNativeReflectConstruct(); return function () { var Super = getPrototypeOf_default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = getPrototypeOf_default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return possibleConstructorReturn_default()(this, result); }; }

function notices_isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/**
 * External dependencies
 */


var notices_Notices = /*#__PURE__*/function (_Component) {
  inherits_default()(Notices, _Component);

  var _super = notices_createSuper(Notices);

  function Notices() {
    classCallCheck_default()(this, Notices);

    return _super.apply(this, arguments);
  }

  createClass_default()(Notices, [{
    key: "render",
    value: function render() {
      return Object(external_this_wp_element_["createElement"])("div", {
        id: "woocommerce-layout__notice-list",
        className: "woocommerce-layout__notice-list"
      });
    }
  }]);

  return Notices;
}(external_this_wp_element_["Component"]);

/* harmony default export */ var layout_notices = (notices_Notices);
// EXTERNAL MODULE: ./node_modules/@babel/runtime/regenerator/index.js
var regenerator = __webpack_require__(73);
var regenerator_default = /*#__PURE__*/__webpack_require__.n(regenerator);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/esm/asyncToGenerator.js
var asyncToGenerator = __webpack_require__(70);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/esm/slicedToArray.js + 3 modules
var slicedToArray = __webpack_require__(21);

// EXTERNAL MODULE: ./node_modules/react-spring/web.cjs.js
var web_cjs = __webpack_require__(257);

// EXTERNAL MODULE: ./node_modules/@wordpress/compose/build-module/hooks/use-reduced-motion/index.js
var use_reduced_motion = __webpack_require__(737);

// EXTERNAL MODULE: ./node_modules/@wordpress/components/build-module/snackbar/index.js
var snackbar = __webpack_require__(415);

// CONCATENATED MODULE: ./node_modules/@wordpress/components/build-module/snackbar/list.js






/**
 * External dependencies
 */



/**
 * WordPress dependencies
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
 * @param  {Object}   $0.className Name of the class used by the component.
 * @param  {Object}   $0.children  Array of children to be rendered inside the notice list.
 * @return {Object}                The rendered notices list.
 */

function SnackbarList(_ref) {
  var notices = _ref.notices,
      className = _ref.className,
      children = _ref.children,
      _ref$onRemove = _ref.onRemove,
      onRemove = _ref$onRemove === void 0 ? external_lodash_["noop"] : _ref$onRemove;
  var isReducedMotion = Object(use_reduced_motion["a" /* default */])();

  var _useState = Object(external_this_wp_element_["useState"])(function () {
    return new WeakMap();
  }),
      _useState2 = Object(slicedToArray["a" /* default */])(_useState, 1),
      refMap = _useState2[0];

  var transitions = Object(web_cjs["useTransition"])(notices, function (notice) {
    return notice.id;
  }, {
    from: {
      opacity: 0,
      height: 0
    },
    enter: function enter(item) {
      return (
        /*#__PURE__*/
        function () {
          var _ref2 = Object(asyncToGenerator["a" /* default */])(
          /*#__PURE__*/
          regenerator_default.a.mark(function _callee(next) {
            return regenerator_default.a.wrap(function _callee$(_context) {
              while (1) {
                switch (_context.prev = _context.next) {
                  case 0:
                    _context.next = 2;
                    return next({
                      opacity: 1,
                      height: refMap.get(item).offsetHeight
                    });

                  case 2:
                    return _context.abrupt("return", _context.sent);

                  case 3:
                  case "end":
                    return _context.stop();
                }
              }
            }, _callee);
          }));

          return function (_x) {
            return _ref2.apply(this, arguments);
          };
        }()
      );
    },
    leave: function leave() {
      return (
        /*#__PURE__*/
        function () {
          var _ref3 = Object(asyncToGenerator["a" /* default */])(
          /*#__PURE__*/
          regenerator_default.a.mark(function _callee2(next) {
            return regenerator_default.a.wrap(function _callee2$(_context2) {
              while (1) {
                switch (_context2.prev = _context2.next) {
                  case 0:
                    _context2.next = 2;
                    return next({
                      opacity: 0
                    });

                  case 2:
                    _context2.next = 4;
                    return next({
                      height: 0
                    });

                  case 4:
                  case "end":
                    return _context2.stop();
                }
              }
            }, _callee2);
          }));

          return function (_x2) {
            return _ref3.apply(this, arguments);
          };
        }()
      );
    },
    immediate: isReducedMotion
  });
  className = classnames_default()('components-snackbar-list', className);

  var removeNotice = function removeNotice(notice) {
    return function () {
      return onRemove(notice.id);
    };
  };

  return Object(external_this_wp_element_["createElement"])("div", {
    className: className
  }, children, transitions.map(function (_ref4) {
    var notice = _ref4.item,
        key = _ref4.key,
        style = _ref4.props;
    return Object(external_this_wp_element_["createElement"])(web_cjs["animated"].div, {
      key: key,
      style: style
    }, Object(external_this_wp_element_["createElement"])("div", {
      className: "components-snackbar-list__notice-container",
      ref: function ref(_ref5) {
        return _ref5 && refMap.set(notice, _ref5);
      }
    }, Object(external_this_wp_element_["createElement"])(snackbar["a" /* default */], Object(esm_extends["a" /* default */])({}, Object(external_lodash_["omit"])(notice, ['content']), {
      onRemove: removeNotice(notice)
    }), notice.content)));
  }));
}

/* harmony default export */ var list = (SnackbarList);
//# sourceMappingURL=list.js.map
// EXTERNAL MODULE: ./client/layout/transient-notices/style.scss
var transient_notices_style = __webpack_require__(439);

// CONCATENATED MODULE: ./client/layout/transient-notices/index.js







function transient_notices_createSuper(Derived) { var hasNativeReflectConstruct = transient_notices_isNativeReflectConstruct(); return function () { var Super = getPrototypeOf_default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = getPrototypeOf_default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return possibleConstructorReturn_default()(this, result); }; }

function transient_notices_isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/**
 * External dependencies
 */






/**
 * Internal dependencies
 */




var transient_notices_TransientNotices = /*#__PURE__*/function (_Component) {
  inherits_default()(TransientNotices, _Component);

  var _super = transient_notices_createSuper(TransientNotices);

  function TransientNotices() {
    classCallCheck_default()(this, TransientNotices);

    return _super.apply(this, arguments);
  }

  createClass_default()(TransientNotices, [{
    key: "render",
    value: function render() {
      var _this$props = this.props,
          className = _this$props.className,
          notices = _this$props.notices,
          onRemove = _this$props.onRemove;
      var classes = classnames_default()('woocommerce-transient-notices', 'components-notices__snackbar', className);
      return Object(external_this_wp_element_["createElement"])(list, {
        notices: notices,
        className: classes,
        onRemove: onRemove
      });
    }
  }]);

  return TransientNotices;
}(external_this_wp_element_["Component"]);

transient_notices_TransientNotices.propTypes = {
  /**
   * Additional class name to style the component.
   */
  className: prop_types_default.a.string,

  /**
   * Array of notices to be displayed.
   */
  notices: prop_types_default.a.array
};
/* harmony default export */ var transient_notices = (Object(compose["a" /* default */])(Object(with_select["a" /* default */])(function (select) {
  var notices = select('core/notices').getNotices();
  return {
    notices: notices
  };
}), Object(external_this_wp_data_["withDispatch"])(function (dispatch) {
  return {
    onRemove: dispatch('core/notices').removeNotice
  };
}))(transient_notices_TransientNotices));
// EXTERNAL MODULE: ./client/analytics/report/index.js
var analytics_report = __webpack_require__(200);

// CONCATENATED MODULE: ./client/layout/index.js










function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { defineProperty_default()(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function layout_createSuper(Derived) { var hasNativeReflectConstruct = layout_isNativeReflectConstruct(); return function () { var Super = getPrototypeOf_default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = getPrototypeOf_default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return possibleConstructorReturn_default()(this, result); }; }

function layout_isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/**
 * External dependencies
 */






/**
 * WooCommerce dependencies
 */





/**
 * Internal dependencies
 */







var StoreAlerts = Object(external_this_wp_element_["lazy"])(function () {
  return Promise.all(/* import() | store-alerts */[__webpack_require__.e(3), __webpack_require__.e(41)]).then(__webpack_require__.bind(null, 912));
});

var layout_PrimaryLayout = /*#__PURE__*/function (_Component) {
  inherits_default()(PrimaryLayout, _Component);

  var _super = layout_createSuper(PrimaryLayout);

  function PrimaryLayout() {
    classCallCheck_default()(this, PrimaryLayout);

    return _super.apply(this, arguments);
  }

  createClass_default()(PrimaryLayout, [{
    key: "render",
    value: function render() {
      var children = this.props.children;
      return Object(external_this_wp_element_["createElement"])("div", {
        className: "woocommerce-layout__primary",
        id: "woocommerce-layout__primary"
      },  true && Object(external_this_wp_element_["createElement"])(external_this_wp_element_["Suspense"], {
        fallback: Object(external_this_wp_element_["createElement"])(external_this_wc_components_["Spinner"], null)
      }, Object(external_this_wp_element_["createElement"])(StoreAlerts, null)), Object(external_this_wp_element_["createElement"])(layout_notices, null), children);
    }
  }]);

  return PrimaryLayout;
}(external_this_wp_element_["Component"]);

var layout_Layout = /*#__PURE__*/function (_Component2) {
  inherits_default()(_Layout, _Component2);

  var _super2 = layout_createSuper(_Layout);

  function _Layout() {
    classCallCheck_default()(this, _Layout);

    return _super2.apply(this, arguments);
  }

  createClass_default()(_Layout, [{
    key: "componentDidMount",
    value: function componentDidMount() {
      this.recordPageViewTrack();
    }
  }, {
    key: "componentDidUpdate",
    value: function componentDidUpdate(prevProps) {
      var previousPath = Object(external_lodash_["get"])(prevProps, 'location.pathname');
      var currentPath = Object(external_lodash_["get"])(this.props, 'location.pathname');

      if (!previousPath || !currentPath) {
        return;
      }

      if (previousPath !== currentPath) {
        this.recordPageViewTrack();
      }
    }
  }, {
    key: "recordPageViewTrack",
    value: function recordPageViewTrack() {
      var _this$props = this.props,
          activePlugins = _this$props.activePlugins,
          installedPlugins = _this$props.installedPlugins,
          isEmbedded = _this$props.isEmbedded,
          isJetpackConnected = _this$props.isJetpackConnected;

      if (isEmbedded) {
        var _path = document.location.pathname + document.location.search;

        Object(tracks["c" /* recordPageView */])(_path, {
          isEmbedded: isEmbedded
        });
        return;
      }

      var pathname = Object(external_lodash_["get"])(this.props, 'location.pathname');

      if (!pathname) {
        return;
      } // Remove leading slash, and camel case remaining pathname


      var path = pathname.substring(1).replace(/\//g, '_'); // When pathname is `/` we are on the dashboard

      if (path.length === 0) {
        path =  false ? undefined : 'dashboard';
      }

      Object(tracks["c" /* recordPageView */])(path, {
        jetpack_installed: installedPlugins.includes('jetpack'),
        jetpack_active: activePlugins.includes('jetpack'),
        jetpack_connected: isJetpackConnected
      });
    }
  }, {
    key: "render",
    value: function render() {
      var _this$props2 = this.props,
          isEmbedded = _this$props2.isEmbedded,
          restProps = objectWithoutProperties_default()(_this$props2, ["isEmbedded"]);

      var breadcrumbs = this.props.page.breadcrumbs;
      return Object(external_this_wp_element_["createElement"])("div", {
        className: "woocommerce-layout"
      }, Object(external_this_wp_element_["createElement"])(header, {
        sections: Object(external_lodash_["isFunction"])(breadcrumbs) ? breadcrumbs(this.props) : breadcrumbs,
        isEmbedded: isEmbedded
      }), Object(external_this_wp_element_["createElement"])(transient_notices, null), !isEmbedded && Object(external_this_wp_element_["createElement"])(layout_PrimaryLayout, null, Object(external_this_wp_element_["createElement"])("div", {
        className: "woocommerce-layout__main"
      }, Object(external_this_wp_element_["createElement"])(controller_Controller, restProps))));
    }
  }]);

  return _Layout;
}(external_this_wp_element_["Component"]);

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
var Layout = Object(compose["a" /* default */])(Object(external_this_wc_data_["withPluginsHydration"])(_objectSpread({}, window.wcSettings.plugins || {}, {
  jetpackStatus: window.wcSettings.dataEndpoints && window.wcSettings.dataEndpoints.jetpackStatus || false
})), Object(external_this_wp_data_["withSelect"])(function (select, _ref) {
  var isEmbedded = _ref.isEmbedded;

  // Embedded pages don't send plugin info to Tracks.
  if (isEmbedded) {
    return;
  }

  var _select = select(external_this_wc_data_["PLUGINS_STORE_NAME"]),
      getActivePlugins = _select.getActivePlugins,
      getInstalledPlugins = _select.getInstalledPlugins,
      isJetpackConnected = _select.isJetpackConnected;

  return {
    activePlugins: getActivePlugins(),
    isJetpackConnected: isJetpackConnected(),
    installedPlugins: getInstalledPlugins()
  };
}))(layout_Layout);

var layout_PageLayout = /*#__PURE__*/function (_Component3) {
  inherits_default()(_PageLayout, _Component3);

  var _super3 = layout_createSuper(_PageLayout);

  function _PageLayout() {
    classCallCheck_default()(this, _PageLayout);

    return _super3.apply(this, arguments);
  }

  createClass_default()(_PageLayout, [{
    key: "render",
    value: function render() {
      return Object(external_this_wp_element_["createElement"])(react_router_Router, {
        history: Object(external_this_wc_navigation_["getHistory"])()
      }, Object(external_this_wp_element_["createElement"])(react_router_Switch, null, controller_getPages().map(function (page) {
        return Object(external_this_wp_element_["createElement"])(react_router_Route, {
          key: page.path,
          path: page.path,
          exact: true,
          render: function render(props) {
            return Object(external_this_wp_element_["createElement"])(Layout, extends_default()({
              page: page
            }, props));
          }
        });
      })));
    }
  }]);

  return _PageLayout;
}(external_this_wp_element_["Component"]); // Use the useFilters HoC so PageLayout is re-rendered when filters are used to add new pages or reports


var PageLayout = Object(external_this_wc_components_["useFilters"])([PAGES_FILTER, analytics_report["REPORTS_FILTER"]])(layout_PageLayout);
var layout_EmbedLayout = /*#__PURE__*/function (_Component4) {
  inherits_default()(EmbedLayout, _Component4);

  var _super4 = layout_createSuper(EmbedLayout);

  function EmbedLayout() {
    classCallCheck_default()(this, EmbedLayout);

    return _super4.apply(this, arguments);
  }

  createClass_default()(EmbedLayout, [{
    key: "render",
    value: function render() {
      return Object(external_this_wp_element_["createElement"])(Layout, {
        page: {
          breadcrumbs: Object(settings["g" /* getSetting */])('embedBreadcrumbs', [])
        },
        isEmbedded: true
      });
    }
  }]);

  return EmbedLayout;
}(external_this_wp_element_["Component"]);

/***/ }),

/***/ 203:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "b", function() { return getFilteredCurrencyInstance; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "a", function() { return CurrencyContext; });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_hooks__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(48);
/* harmony import */ var _wordpress_hooks__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_hooks__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _woocommerce_currency__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(137);
/* harmony import */ var _woocommerce_currency__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_currency__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(26);
/**
 * External dependencies
 */


/**
 * WooCommerce dependencies
 */


/**
 * Internal dependencies
 */


var appCurrency = _woocommerce_currency__WEBPACK_IMPORTED_MODULE_2___default()(_woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_3__[/* CURRENCY */ "b"]);
var getFilteredCurrencyInstance = function getFilteredCurrencyInstance(query) {
  var config = appCurrency.getCurrency();
  var filteredConfig = Object(_wordpress_hooks__WEBPACK_IMPORTED_MODULE_1__["applyFilters"])('woocommerce_admin_report_currency', config, query);
  return new _woocommerce_currency__WEBPACK_IMPORTED_MODULE_2___default.a(filteredConfig);
};
var CurrencyContext = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createContext"])(appCurrency // default value
);

/***/ }),

/***/ 204:
/***/ (function(module, exports) {

(function() { module.exports = this["wc"]["number"]; }());

/***/ }),

/***/ 209:
/***/ (function(module, exports) {

function _setPrototypeOf(o, p) {
  module.exports = _setPrototypeOf = Object.setPrototypeOf || function _setPrototypeOf(o, p) {
    o.__proto__ = p;
    return o;
  };

  return _setPrototypeOf(o, p);
}

module.exports = _setPrototypeOf;

/***/ }),

/***/ 21:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";

// EXPORTS
__webpack_require__.d(__webpack_exports__, "a", function() { return /* binding */ _slicedToArray; });

// CONCATENATED MODULE: ./node_modules/@babel/runtime/helpers/esm/arrayWithHoles.js
function _arrayWithHoles(arr) {
  if (Array.isArray(arr)) return arr;
}
// CONCATENATED MODULE: ./node_modules/@babel/runtime/helpers/esm/iterableToArrayLimit.js
function _iterableToArrayLimit(arr, i) {
  if (typeof Symbol === "undefined" || !(Symbol.iterator in Object(arr))) return;
  var _arr = [];
  var _n = true;
  var _d = false;
  var _e = undefined;

  try {
    for (var _i = arr[Symbol.iterator](), _s; !(_n = (_s = _i.next()).done); _n = true) {
      _arr.push(_s.value);

      if (i && _arr.length === i) break;
    }
  } catch (err) {
    _d = true;
    _e = err;
  } finally {
    try {
      if (!_n && _i["return"] != null) _i["return"]();
    } finally {
      if (_d) throw _e;
    }
  }

  return _arr;
}
// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/esm/unsupportedIterableToArray.js
var unsupportedIterableToArray = __webpack_require__(52);

// CONCATENATED MODULE: ./node_modules/@babel/runtime/helpers/esm/nonIterableRest.js
function _nonIterableRest() {
  throw new TypeError("Invalid attempt to destructure non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method.");
}
// CONCATENATED MODULE: ./node_modules/@babel/runtime/helpers/esm/slicedToArray.js




function _slicedToArray(arr, i) {
  return _arrayWithHoles(arr) || _iterableToArrayLimit(arr, i) || Object(unsupportedIterableToArray["a" /* default */])(arr, i) || _nonIterableRest();
}

/***/ }),

/***/ 210:
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

/***/ 22:
/***/ (function(module, exports) {

(function() { module.exports = this["wc"]["navigation"]; }());

/***/ }),

/***/ 23:
/***/ (function(module, exports) {

(function() { module.exports = this["wc"]["date"]; }());

/***/ }),

/***/ 24:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* unused harmony export JETPACK_NAMESPACE */
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "c", function() { return NAMESPACE; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "f", function() { return WC_ADMIN_NAMESPACE; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "e", function() { return WCS_NAMESPACE; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "a", function() { return DEFAULT_REQUIREMENT; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "b", function() { return MAX_PER_PAGE; });
/* unused harmony export DEFAULT_ACTIONABLE_STATUSES */
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "d", function() { return QUERY_DEFAULTS; });
/* harmony import */ var _fresh_data_framework__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(170);
/**
 * External dependencies
 */

var JETPACK_NAMESPACE = '/jetpack/v4';
var NAMESPACE = '/wc-analytics';
var WC_ADMIN_NAMESPACE = '/wc-admin';
var WCS_NAMESPACE = '/wc/v1'; // WCS endpoints like Stripe are not avaiable on later /wc versions

var DEFAULT_REQUIREMENT = {
  timeout: 1 * _fresh_data_framework__WEBPACK_IMPORTED_MODULE_0__[/* MINUTE */ "b"],
  freshness: 30 * _fresh_data_framework__WEBPACK_IMPORTED_MODULE_0__[/* MINUTE */ "b"]
}; // WordPress & WooCommerce both set a hard limit of 100 for the per_page parameter

var MAX_PER_PAGE = 100;
var DEFAULT_ACTIONABLE_STATUSES = ['processing', 'on-hold'];
var QUERY_DEFAULTS = {
  pageSize: 25,
  period: 'month',
  compare: 'previous_year'
};

/***/ }),

/***/ 256:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(2);
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_0__);
/**
 * External dependencies
 */

/**
 * Composes multiple higher-order components into a single higher-order component. Performs right-to-left function
 * composition, where each successive invocation is supplied the return value of the previous.
 *
 * @param {...Function} hocs The HOC functions to invoke.
 *
 * @return {Function} Returns the new composite function.
 */

/* harmony default export */ __webpack_exports__["a"] = (lodash__WEBPACK_IMPORTED_MODULE_0__["flowRight"]);
//# sourceMappingURL=compose.js.map

/***/ }),

/***/ 257:
/***/ (function(module, exports, __webpack_require__) {

"use strict";


Object.defineProperty(exports, '__esModule', { value: true });

function _interopDefault (ex) { return (ex && (typeof ex === 'object') && 'default' in ex) ? ex['default'] : ex; }

var _extends = _interopDefault(__webpack_require__(105));
var _objectWithoutPropertiesLoose = _interopDefault(__webpack_require__(266));
var React = __webpack_require__(14);
var React__default = _interopDefault(React);
var _inheritsLoose = _interopDefault(__webpack_require__(201));
var _assertThisInitialized = _interopDefault(__webpack_require__(59));

var is = {
  arr: Array.isArray,
  obj: function obj(a) {
    return Object.prototype.toString.call(a) === '[object Object]';
  },
  fun: function fun(a) {
    return typeof a === 'function';
  },
  str: function str(a) {
    return typeof a === 'string';
  },
  num: function num(a) {
    return typeof a === 'number';
  },
  und: function und(a) {
    return a === void 0;
  },
  nul: function nul(a) {
    return a === null;
  },
  set: function set(a) {
    return a instanceof Set;
  },
  map: function map(a) {
    return a instanceof Map;
  },
  equ: function equ(a, b) {
    if (typeof a !== typeof b) return false;
    if (is.str(a) || is.num(a)) return a === b;
    if (is.obj(a) && is.obj(b) && Object.keys(a).length + Object.keys(b).length === 0) return true;
    var i;

    for (i in a) {
      if (!(i in b)) return false;
    }

    for (i in b) {
      if (a[i] !== b[i]) return false;
    }

    return is.und(i) ? a === b : true;
  }
};
function merge(target, lowercase) {
  if (lowercase === void 0) {
    lowercase = true;
  }

  return function (object) {
    return (is.arr(object) ? object : Object.keys(object)).reduce(function (acc, element) {
      var key = lowercase ? element[0].toLowerCase() + element.substring(1) : element;
      acc[key] = target(key);
      return acc;
    }, target);
  };
}
function useForceUpdate() {
  var _useState = React.useState(false),
      f = _useState[1];

  var forceUpdate = React.useCallback(function () {
    return f(function (v) {
      return !v;
    });
  }, []);
  return forceUpdate;
}
function withDefault(value, defaultValue) {
  return is.und(value) || is.nul(value) ? defaultValue : value;
}
function toArray(a) {
  return !is.und(a) ? is.arr(a) ? a : [a] : [];
}
function callProp(obj) {
  for (var _len = arguments.length, args = new Array(_len > 1 ? _len - 1 : 0), _key = 1; _key < _len; _key++) {
    args[_key - 1] = arguments[_key];
  }

  return is.fun(obj) ? obj.apply(void 0, args) : obj;
}

function getForwardProps(props) {
  var to = props.to,
      from = props.from,
      config = props.config,
      onStart = props.onStart,
      onRest = props.onRest,
      onFrame = props.onFrame,
      children = props.children,
      reset = props.reset,
      reverse = props.reverse,
      force = props.force,
      immediate = props.immediate,
      delay = props.delay,
      attach = props.attach,
      destroyed = props.destroyed,
      interpolateTo = props.interpolateTo,
      ref = props.ref,
      lazy = props.lazy,
      forward = _objectWithoutPropertiesLoose(props, ["to", "from", "config", "onStart", "onRest", "onFrame", "children", "reset", "reverse", "force", "immediate", "delay", "attach", "destroyed", "interpolateTo", "ref", "lazy"]);

  return forward;
}

function interpolateTo(props) {
  var forward = getForwardProps(props);
  if (is.und(forward)) return _extends({
    to: forward
  }, props);
  var rest = Object.keys(props).reduce(function (a, k) {
    var _extends2;

    return !is.und(forward[k]) ? a : _extends({}, a, (_extends2 = {}, _extends2[k] = props[k], _extends2));
  }, {});
  return _extends({
    to: forward
  }, rest);
}
function handleRef(ref, forward) {
  if (forward) {
    // If it's a function, assume it's a ref callback
    if (is.fun(forward)) forward(ref);else if (is.obj(forward)) {
      forward.current = ref;
    }
  }

  return ref;
}

var Animated =
/*#__PURE__*/
function () {
  function Animated() {
    this.payload = void 0;
    this.children = [];
  }

  var _proto = Animated.prototype;

  _proto.getAnimatedValue = function getAnimatedValue() {
    return this.getValue();
  };

  _proto.getPayload = function getPayload() {
    return this.payload || this;
  };

  _proto.attach = function attach() {};

  _proto.detach = function detach() {};

  _proto.getChildren = function getChildren() {
    return this.children;
  };

  _proto.addChild = function addChild(child) {
    if (this.children.length === 0) this.attach();
    this.children.push(child);
  };

  _proto.removeChild = function removeChild(child) {
    var index = this.children.indexOf(child);
    this.children.splice(index, 1);
    if (this.children.length === 0) this.detach();
  };

  return Animated;
}();
var AnimatedArray =
/*#__PURE__*/
function (_Animated) {
  _inheritsLoose(AnimatedArray, _Animated);

  function AnimatedArray() {
    var _this;

    for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
      args[_key] = arguments[_key];
    }

    _this = _Animated.call.apply(_Animated, [this].concat(args)) || this;
    _this.payload = [];

    _this.attach = function () {
      return _this.payload.forEach(function (p) {
        return p instanceof Animated && p.addChild(_assertThisInitialized(_this));
      });
    };

    _this.detach = function () {
      return _this.payload.forEach(function (p) {
        return p instanceof Animated && p.removeChild(_assertThisInitialized(_this));
      });
    };

    return _this;
  }

  return AnimatedArray;
}(Animated);
var AnimatedObject =
/*#__PURE__*/
function (_Animated2) {
  _inheritsLoose(AnimatedObject, _Animated2);

  function AnimatedObject() {
    var _this2;

    for (var _len3 = arguments.length, args = new Array(_len3), _key3 = 0; _key3 < _len3; _key3++) {
      args[_key3] = arguments[_key3];
    }

    _this2 = _Animated2.call.apply(_Animated2, [this].concat(args)) || this;
    _this2.payload = {};

    _this2.attach = function () {
      return Object.values(_this2.payload).forEach(function (s) {
        return s instanceof Animated && s.addChild(_assertThisInitialized(_this2));
      });
    };

    _this2.detach = function () {
      return Object.values(_this2.payload).forEach(function (s) {
        return s instanceof Animated && s.removeChild(_assertThisInitialized(_this2));
      });
    };

    return _this2;
  }

  var _proto2 = AnimatedObject.prototype;

  _proto2.getValue = function getValue(animated) {
    if (animated === void 0) {
      animated = false;
    }

    var payload = {};

    for (var _key4 in this.payload) {
      var value = this.payload[_key4];
      if (animated && !(value instanceof Animated)) continue;
      payload[_key4] = value instanceof Animated ? value[animated ? 'getAnimatedValue' : 'getValue']() : value;
    }

    return payload;
  };

  _proto2.getAnimatedValue = function getAnimatedValue() {
    return this.getValue(true);
  };

  return AnimatedObject;
}(Animated);

var applyAnimatedValues;
function injectApplyAnimatedValues(fn, transform) {
  applyAnimatedValues = {
    fn: fn,
    transform: transform
  };
}
var colorNames;
function injectColorNames(names) {
  colorNames = names;
}
var requestFrame = function requestFrame(cb) {
  return typeof window !== 'undefined' ? window.requestAnimationFrame(cb) : -1;
};
var cancelFrame = function cancelFrame(id) {
  typeof window !== 'undefined' && window.cancelAnimationFrame(id);
};
function injectFrame(raf, caf) {
  requestFrame = raf;
  cancelFrame = caf;
}
var interpolation;
function injectStringInterpolator(fn) {
  interpolation = fn;
}
var now = function now() {
  return Date.now();
};
function injectNow(nowFn) {
  now = nowFn;
}
var defaultElement;
function injectDefaultElement(el) {
  defaultElement = el;
}
var animatedApi = function animatedApi(node) {
  return node.current;
};
function injectAnimatedApi(fn) {
  animatedApi = fn;
}
var createAnimatedStyle;
function injectCreateAnimatedStyle(factory) {
  createAnimatedStyle = factory;
}
var manualFrameloop;
function injectManualFrameloop(callback) {
  manualFrameloop = callback;
}

var Globals = /*#__PURE__*/Object.freeze({
  get applyAnimatedValues () { return applyAnimatedValues; },
  injectApplyAnimatedValues: injectApplyAnimatedValues,
  get colorNames () { return colorNames; },
  injectColorNames: injectColorNames,
  get requestFrame () { return requestFrame; },
  get cancelFrame () { return cancelFrame; },
  injectFrame: injectFrame,
  get interpolation () { return interpolation; },
  injectStringInterpolator: injectStringInterpolator,
  get now () { return now; },
  injectNow: injectNow,
  get defaultElement () { return defaultElement; },
  injectDefaultElement: injectDefaultElement,
  get animatedApi () { return animatedApi; },
  injectAnimatedApi: injectAnimatedApi,
  get createAnimatedStyle () { return createAnimatedStyle; },
  injectCreateAnimatedStyle: injectCreateAnimatedStyle,
  get manualFrameloop () { return manualFrameloop; },
  injectManualFrameloop: injectManualFrameloop
});

/**
 * Wraps the `style` property with `AnimatedStyle`.
 */

var AnimatedProps =
/*#__PURE__*/
function (_AnimatedObject) {
  _inheritsLoose(AnimatedProps, _AnimatedObject);

  function AnimatedProps(props, callback) {
    var _this;

    _this = _AnimatedObject.call(this) || this;
    _this.update = void 0;
    _this.payload = !props.style ? props : _extends({}, props, {
      style: createAnimatedStyle(props.style)
    });
    _this.update = callback;

    _this.attach();

    return _this;
  }

  return AnimatedProps;
}(AnimatedObject);

var isFunctionComponent = function isFunctionComponent(val) {
  return is.fun(val) && !(val.prototype instanceof React__default.Component);
};

var createAnimatedComponent = function createAnimatedComponent(Component) {
  var AnimatedComponent = React.forwardRef(function (props, ref) {
    var forceUpdate = useForceUpdate();
    var mounted = React.useRef(true);
    var propsAnimated = React.useRef(null);
    var node = React.useRef(null);
    var attachProps = React.useCallback(function (props) {
      var oldPropsAnimated = propsAnimated.current;

      var callback = function callback() {
        var didUpdate = false;

        if (node.current) {
          didUpdate = applyAnimatedValues.fn(node.current, propsAnimated.current.getAnimatedValue());
        }

        if (!node.current || didUpdate === false) {
          // If no referenced node has been found, or the update target didn't have a
          // native-responder, then forceUpdate the animation ...
          forceUpdate();
        }
      };

      propsAnimated.current = new AnimatedProps(props, callback);
      oldPropsAnimated && oldPropsAnimated.detach();
    }, []);
    React.useEffect(function () {
      return function () {
        mounted.current = false;
        propsAnimated.current && propsAnimated.current.detach();
      };
    }, []);
    React.useImperativeHandle(ref, function () {
      return animatedApi(node, mounted, forceUpdate);
    });
    attachProps(props);

    var _getValue = propsAnimated.current.getValue(),
        scrollTop = _getValue.scrollTop,
        scrollLeft = _getValue.scrollLeft,
        animatedProps = _objectWithoutPropertiesLoose(_getValue, ["scrollTop", "scrollLeft"]); // Functions cannot have refs, see:
    // See: https://github.com/react-spring/react-spring/issues/569


    var refFn = isFunctionComponent(Component) ? undefined : function (childRef) {
      return node.current = handleRef(childRef, ref);
    };
    return React__default.createElement(Component, _extends({}, animatedProps, {
      ref: refFn
    }));
  });
  return AnimatedComponent;
};

var active = false;
var controllers = new Set();

var update = function update() {
  if (!active) return false;
  var time = now();

  for (var _iterator = controllers, _isArray = Array.isArray(_iterator), _i = 0, _iterator = _isArray ? _iterator : _iterator[Symbol.iterator]();;) {
    var _ref;

    if (_isArray) {
      if (_i >= _iterator.length) break;
      _ref = _iterator[_i++];
    } else {
      _i = _iterator.next();
      if (_i.done) break;
      _ref = _i.value;
    }

    var controller = _ref;
    var isActive = false;

    for (var configIdx = 0; configIdx < controller.configs.length; configIdx++) {
      var config = controller.configs[configIdx];
      var endOfAnimation = void 0,
          lastTime = void 0;

      for (var valIdx = 0; valIdx < config.animatedValues.length; valIdx++) {
        var animation = config.animatedValues[valIdx]; // If an animation is done, skip, until all of them conclude

        if (animation.done) continue;
        var from = config.fromValues[valIdx];
        var to = config.toValues[valIdx];
        var position = animation.lastPosition;
        var isAnimated = to instanceof Animated;
        var velocity = Array.isArray(config.initialVelocity) ? config.initialVelocity[valIdx] : config.initialVelocity;
        if (isAnimated) to = to.getValue(); // Conclude animation if it's either immediate, or from-values match end-state

        if (config.immediate) {
          animation.setValue(to);
          animation.done = true;
          continue;
        } // Break animation when string values are involved


        if (typeof from === 'string' || typeof to === 'string') {
          animation.setValue(to);
          animation.done = true;
          continue;
        }

        if (config.duration !== void 0) {
          /** Duration easing */
          position = from + config.easing((time - animation.startTime) / config.duration) * (to - from);
          endOfAnimation = time >= animation.startTime + config.duration;
        } else if (config.decay) {
          /** Decay easing */
          position = from + velocity / (1 - 0.998) * (1 - Math.exp(-(1 - 0.998) * (time - animation.startTime)));
          endOfAnimation = Math.abs(animation.lastPosition - position) < 0.1;
          if (endOfAnimation) to = position;
        } else {
          /** Spring easing */
          lastTime = animation.lastTime !== void 0 ? animation.lastTime : time;
          velocity = animation.lastVelocity !== void 0 ? animation.lastVelocity : config.initialVelocity; // If we lost a lot of frames just jump to the end.

          if (time > lastTime + 64) lastTime = time; // http://gafferongames.com/game-physics/fix-your-timestep/

          var numSteps = Math.floor(time - lastTime);

          for (var i = 0; i < numSteps; ++i) {
            var force = -config.tension * (position - to);
            var damping = -config.friction * velocity;
            var acceleration = (force + damping) / config.mass;
            velocity = velocity + acceleration * 1 / 1000;
            position = position + velocity * 1 / 1000;
          } // Conditions for stopping the spring animation


          var isOvershooting = config.clamp && config.tension !== 0 ? from < to ? position > to : position < to : false;
          var isVelocity = Math.abs(velocity) <= config.precision;
          var isDisplacement = config.tension !== 0 ? Math.abs(to - position) <= config.precision : true;
          endOfAnimation = isOvershooting || isVelocity && isDisplacement;
          animation.lastVelocity = velocity;
          animation.lastTime = time;
        } // Trails aren't done until their parents conclude


        if (isAnimated && !config.toValues[valIdx].done) endOfAnimation = false;

        if (endOfAnimation) {
          // Ensure that we end up with a round value
          if (animation.value !== to) position = to;
          animation.done = true;
        } else isActive = true;

        animation.setValue(position);
        animation.lastPosition = position;
      } // Keep track of updated values only when necessary


      if (controller.props.onFrame) controller.values[config.name] = config.interpolation.getValue();
    } // Update callbacks in the end of the frame


    if (controller.props.onFrame) controller.props.onFrame(controller.values); // Either call onEnd or next frame

    if (!isActive) {
      controllers.delete(controller);
      controller.stop(true);
    }
  } // Loop over as long as there are controllers ...


  if (controllers.size) {
    if (manualFrameloop) manualFrameloop();else requestFrame(update);
  } else {
    active = false;
  }

  return active;
};

var start = function start(controller) {
  if (!controllers.has(controller)) controllers.add(controller);

  if (!active) {
    active = true;
    if (manualFrameloop) requestFrame(manualFrameloop);else requestFrame(update);
  }
};

var stop = function stop(controller) {
  if (controllers.has(controller)) controllers.delete(controller);
};

function createInterpolator(range, output, extrapolate) {
  if (typeof range === 'function') {
    return range;
  }

  if (Array.isArray(range)) {
    return createInterpolator({
      range: range,
      output: output,
      extrapolate: extrapolate
    });
  }

  if (interpolation && typeof range.output[0] === 'string') {
    return interpolation(range);
  }

  var config = range;
  var outputRange = config.output;
  var inputRange = config.range || [0, 1];
  var extrapolateLeft = config.extrapolateLeft || config.extrapolate || 'extend';
  var extrapolateRight = config.extrapolateRight || config.extrapolate || 'extend';

  var easing = config.easing || function (t) {
    return t;
  };

  return function (input) {
    var range = findRange(input, inputRange);
    return interpolate(input, inputRange[range], inputRange[range + 1], outputRange[range], outputRange[range + 1], easing, extrapolateLeft, extrapolateRight, config.map);
  };
}

function interpolate(input, inputMin, inputMax, outputMin, outputMax, easing, extrapolateLeft, extrapolateRight, map) {
  var result = map ? map(input) : input; // Extrapolate

  if (result < inputMin) {
    if (extrapolateLeft === 'identity') return result;else if (extrapolateLeft === 'clamp') result = inputMin;
  }

  if (result > inputMax) {
    if (extrapolateRight === 'identity') return result;else if (extrapolateRight === 'clamp') result = inputMax;
  }

  if (outputMin === outputMax) return outputMin;
  if (inputMin === inputMax) return input <= inputMin ? outputMin : outputMax; // Input Range

  if (inputMin === -Infinity) result = -result;else if (inputMax === Infinity) result = result - inputMin;else result = (result - inputMin) / (inputMax - inputMin); // Easing

  result = easing(result); // Output Range

  if (outputMin === -Infinity) result = -result;else if (outputMax === Infinity) result = result + outputMin;else result = result * (outputMax - outputMin) + outputMin;
  return result;
}

function findRange(input, inputRange) {
  for (var i = 1; i < inputRange.length - 1; ++i) {
    if (inputRange[i] >= input) break;
  }

  return i - 1;
}

var AnimatedInterpolation =
/*#__PURE__*/
function (_AnimatedArray) {
  _inheritsLoose(AnimatedInterpolation, _AnimatedArray);

  function AnimatedInterpolation(parents, range, output, extrapolate) {
    var _this;

    _this = _AnimatedArray.call(this) || this;
    _this.calc = void 0;
    _this.payload = parents instanceof AnimatedArray && !(parents instanceof AnimatedInterpolation) ? parents.getPayload() : Array.isArray(parents) ? parents : [parents];
    _this.calc = createInterpolator(range, output, extrapolate);
    return _this;
  }

  var _proto = AnimatedInterpolation.prototype;

  _proto.getValue = function getValue() {
    return this.calc.apply(this, this.payload.map(function (value) {
      return value.getValue();
    }));
  };

  _proto.updateConfig = function updateConfig(range, output, extrapolate) {
    this.calc = createInterpolator(range, output, extrapolate);
  };

  _proto.interpolate = function interpolate(range, output, extrapolate) {
    return new AnimatedInterpolation(this, range, output, extrapolate);
  };

  return AnimatedInterpolation;
}(AnimatedArray);

var interpolate$1 = function interpolate(parents, range, output) {
  return parents && new AnimatedInterpolation(parents, range, output);
};

var config = {
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

/** API
 *  useChain(references, timeSteps, timeFrame)
 */

function useChain(refs, timeSteps, timeFrame) {
  if (timeFrame === void 0) {
    timeFrame = 1000;
  }

  var previous = React.useRef();
  React.useEffect(function () {
    if (is.equ(refs, previous.current)) refs.forEach(function (_ref) {
      var current = _ref.current;
      return current && current.start();
    });else if (timeSteps) {
      refs.forEach(function (_ref2, index) {
        var current = _ref2.current;

        if (current) {
          var ctrls = current.controllers;

          if (ctrls.length) {
            var t = timeFrame * timeSteps[index];
            ctrls.forEach(function (ctrl) {
              ctrl.queue = ctrl.queue.map(function (e) {
                return _extends({}, e, {
                  delay: e.delay + t
                });
              });
              ctrl.start();
            });
          }
        }
      });
    } else refs.reduce(function (q, _ref3, rI) {
      var current = _ref3.current;
      return q = q.then(function () {
        return current.start();
      });
    }, Promise.resolve());
    previous.current = refs;
  });
}

/**
 * Animated works by building a directed acyclic graph of dependencies
 * transparently when you render your Animated components.
 *
 *               new Animated.Value(0)
 *     .interpolate()        .interpolate()    new Animated.Value(1)
 *         opacity               translateY      scale
 *          style                         transform
 *         View#234                         style
 *                                         View#123
 *
 * A) Top Down phase
 * When an AnimatedValue is updated, we recursively go down through this
 * graph in order to find leaf nodes: the views that we flag as needing
 * an update.
 *
 * B) Bottom Up phase
 * When a view is flagged as needing an update, we recursively go back up
 * in order to build the new value that it needs. The reason why we need
 * this two-phases process is to deal with composite props such as
 * transform which can receive values from multiple parents.
 */
function addAnimatedStyles(node, styles) {
  if ('update' in node) {
    styles.add(node);
  } else {
    node.getChildren().forEach(function (child) {
      return addAnimatedStyles(child, styles);
    });
  }
}

var AnimatedValue =
/*#__PURE__*/
function (_Animated) {
  _inheritsLoose(AnimatedValue, _Animated);

  function AnimatedValue(_value) {
    var _this;

    _this = _Animated.call(this) || this;
    _this.animatedStyles = new Set();
    _this.value = void 0;
    _this.startPosition = void 0;
    _this.lastPosition = void 0;
    _this.lastVelocity = void 0;
    _this.startTime = void 0;
    _this.lastTime = void 0;
    _this.done = false;

    _this.setValue = function (value, flush) {
      if (flush === void 0) {
        flush = true;
      }

      _this.value = value;
      if (flush) _this.flush();
    };

    _this.value = _value;
    _this.startPosition = _value;
    _this.lastPosition = _value;
    return _this;
  }

  var _proto = AnimatedValue.prototype;

  _proto.flush = function flush() {
    if (this.animatedStyles.size === 0) {
      addAnimatedStyles(this, this.animatedStyles);
    }

    this.animatedStyles.forEach(function (animatedStyle) {
      return animatedStyle.update();
    });
  };

  _proto.clearStyles = function clearStyles() {
    this.animatedStyles.clear();
  };

  _proto.getValue = function getValue() {
    return this.value;
  };

  _proto.interpolate = function interpolate(range, output, extrapolate) {
    return new AnimatedInterpolation(this, range, output, extrapolate);
  };

  return AnimatedValue;
}(Animated);

var AnimatedValueArray =
/*#__PURE__*/
function (_AnimatedArray) {
  _inheritsLoose(AnimatedValueArray, _AnimatedArray);

  function AnimatedValueArray(values) {
    var _this;

    _this = _AnimatedArray.call(this) || this;
    _this.payload = values.map(function (n) {
      return new AnimatedValue(n);
    });
    return _this;
  }

  var _proto = AnimatedValueArray.prototype;

  _proto.setValue = function setValue(value, flush) {
    var _this2 = this;

    if (flush === void 0) {
      flush = true;
    }

    if (Array.isArray(value)) {
      if (value.length === this.payload.length) {
        value.forEach(function (v, i) {
          return _this2.payload[i].setValue(v, flush);
        });
      }
    } else {
      this.payload.forEach(function (p) {
        return p.setValue(value, flush);
      });
    }
  };

  _proto.getValue = function getValue() {
    return this.payload.map(function (v) {
      return v.getValue();
    });
  };

  _proto.interpolate = function interpolate(range, output) {
    return new AnimatedInterpolation(this, range, output);
  };

  return AnimatedValueArray;
}(AnimatedArray);

var G = 0;

var Controller =
/*#__PURE__*/
function () {
  function Controller() {
    var _this = this;

    this.id = void 0;
    this.idle = true;
    this.hasChanged = false;
    this.guid = 0;
    this.local = 0;
    this.props = {};
    this.merged = {};
    this.animations = {};
    this.interpolations = {};
    this.values = {};
    this.configs = [];
    this.listeners = [];
    this.queue = [];
    this.localQueue = void 0;

    this.getValues = function () {
      return _this.interpolations;
    };

    this.id = G++;
  }
  /** update(props)
   *  This function filters input props and creates an array of tasks which are executed in .start()
   *  Each task is allowed to carry a delay, which means it can execute asnychroneously */


  var _proto = Controller.prototype;

  _proto.update = function update$$1(args) {
    //this._id = n + this.id
    if (!args) return this; // Extract delay and the to-prop from props

    var _ref = interpolateTo(args),
        _ref$delay = _ref.delay,
        delay = _ref$delay === void 0 ? 0 : _ref$delay,
        to = _ref.to,
        props = _objectWithoutPropertiesLoose(_ref, ["delay", "to"]);

    if (is.arr(to) || is.fun(to)) {
      // If config is either a function or an array queue it up as is
      this.queue.push(_extends({}, props, {
        delay: delay,
        to: to
      }));
    } else if (to) {
      // Otherwise go through each key since it could be delayed individually
      var ops = {};
      Object.entries(to).forEach(function (_ref2) {
        var _to;

        var k = _ref2[0],
            v = _ref2[1];

        // Fetch delay and create an entry, consisting of the to-props, the delay, and basic props
        var entry = _extends({
          to: (_to = {}, _to[k] = v, _to),
          delay: callProp(delay, k)
        }, props);

        var previous = ops[entry.delay] && ops[entry.delay].to;
        ops[entry.delay] = _extends({}, ops[entry.delay], entry, {
          to: _extends({}, previous, entry.to)
        });
      });
      this.queue = Object.values(ops);
    } // Sort queue, so that async calls go last


    this.queue = this.queue.sort(function (a, b) {
      return a.delay - b.delay;
    }); // Diff the reduced props immediately (they'll contain the from-prop and some config)

    this.diff(props);
    return this;
  }
  /** start(onEnd)
   *  This function either executes a queue, if present, or starts the frameloop, which animates */
  ;

  _proto.start = function start$$1(onEnd) {
    var _this2 = this;

    // If a queue is present we must excecute it
    if (this.queue.length) {
      this.idle = false; // Updates can interrupt trailing queues, in that case we just merge values

      if (this.localQueue) {
        this.localQueue.forEach(function (_ref3) {
          var _ref3$from = _ref3.from,
              from = _ref3$from === void 0 ? {} : _ref3$from,
              _ref3$to = _ref3.to,
              to = _ref3$to === void 0 ? {} : _ref3$to;
          if (is.obj(from)) _this2.merged = _extends({}, from, _this2.merged);
          if (is.obj(to)) _this2.merged = _extends({}, _this2.merged, to);
        });
      } // The guid helps us tracking frames, a new queue over an old one means an override
      // We discard async calls in that caseÍ


      var local = this.local = ++this.guid;
      var queue = this.localQueue = this.queue;
      this.queue = []; // Go through each entry and execute it

      queue.forEach(function (_ref4, index) {
        var delay = _ref4.delay,
            props = _objectWithoutPropertiesLoose(_ref4, ["delay"]);

        var cb = function cb(finished) {
          if (index === queue.length - 1 && local === _this2.guid && finished) {
            _this2.idle = true;
            if (_this2.props.onRest) _this2.props.onRest(_this2.merged);
          }

          if (onEnd) onEnd();
        }; // Entries can be delayed, ansyc or immediate


        var async = is.arr(props.to) || is.fun(props.to);

        if (delay) {
          setTimeout(function () {
            if (local === _this2.guid) {
              if (async) _this2.runAsync(props, cb);else _this2.diff(props).start(cb);
            }
          }, delay);
        } else if (async) _this2.runAsync(props, cb);else _this2.diff(props).start(cb);
      });
    } // Otherwise we kick of the frameloop
    else {
        if (is.fun(onEnd)) this.listeners.push(onEnd);
        if (this.props.onStart) this.props.onStart();

        start(this);
      }

    return this;
  };

  _proto.stop = function stop$$1(finished) {
    this.listeners.forEach(function (onEnd) {
      return onEnd(finished);
    });
    this.listeners = [];
    return this;
  }
  /** Pause sets onEnd listeners free, but also removes the controller from the frameloop */
  ;

  _proto.pause = function pause(finished) {
    this.stop(true);
    if (finished) stop(this);
    return this;
  };

  _proto.runAsync = function runAsync(_ref5, onEnd) {
    var _this3 = this;

    var delay = _ref5.delay,
        props = _objectWithoutPropertiesLoose(_ref5, ["delay"]);

    var local = this.local; // If "to" is either a function or an array it will be processed async, therefor "to" should be empty right now
    // If the view relies on certain values "from" has to be present

    var queue = Promise.resolve(undefined);

    if (is.arr(props.to)) {
      var _loop = function _loop(i) {
        var index = i;

        var fresh = _extends({}, props, interpolateTo(props.to[index]));

        if (is.arr(fresh.config)) fresh.config = fresh.config[index];
        queue = queue.then(function () {
          //this.stop()
          if (local === _this3.guid) return new Promise(function (r) {
            return _this3.diff(fresh).start(r);
          });
        });
      };

      for (var i = 0; i < props.to.length; i++) {
        _loop(i);
      }
    } else if (is.fun(props.to)) {
      var index = 0;
      var last;
      queue = queue.then(function () {
        return props.to( // next(props)
        function (p) {
          var fresh = _extends({}, props, interpolateTo(p));

          if (is.arr(fresh.config)) fresh.config = fresh.config[index];
          index++; //this.stop()

          if (local === _this3.guid) return last = new Promise(function (r) {
            return _this3.diff(fresh).start(r);
          });
          return;
        }, // cancel()
        function (finished) {
          if (finished === void 0) {
            finished = true;
          }

          return _this3.stop(finished);
        }).then(function () {
          return last;
        });
      });
    }

    queue.then(onEnd);
  };

  _proto.diff = function diff(props) {
    var _this4 = this;

    this.props = _extends({}, this.props, props);
    var _this$props = this.props,
        _this$props$from = _this$props.from,
        from = _this$props$from === void 0 ? {} : _this$props$from,
        _this$props$to = _this$props.to,
        to = _this$props$to === void 0 ? {} : _this$props$to,
        _this$props$config = _this$props.config,
        config = _this$props$config === void 0 ? {} : _this$props$config,
        reverse = _this$props.reverse,
        attach = _this$props.attach,
        reset = _this$props.reset,
        immediate = _this$props.immediate; // Reverse values when requested

    if (reverse) {
      var _ref6 = [to, from];
      from = _ref6[0];
      to = _ref6[1];
    } // This will collect all props that were ever set, reset merged props when necessary


    this.merged = _extends({}, from, this.merged, to);
    this.hasChanged = false; // Attachment handling, trailed springs can "attach" themselves to a previous spring

    var target = attach && attach(this); // Reduces input { name: value } pairs into animated values

    this.animations = Object.entries(this.merged).reduce(function (acc, _ref7) {
      var name = _ref7[0],
          value = _ref7[1];
      // Issue cached entries, except on reset
      var entry = acc[name] || {}; // Figure out what the value is supposed to be

      var isNumber = is.num(value);
      var isString = is.str(value) && !value.startsWith('#') && !/\d/.test(value) && !colorNames[value];
      var isArray = is.arr(value);
      var isInterpolation = !isNumber && !isArray && !isString;
      var fromValue = !is.und(from[name]) ? from[name] : value;
      var toValue = isNumber || isArray ? value : isString ? value : 1;
      var toConfig = callProp(config, name);
      if (target) toValue = target.animations[name].parent;
      var parent = entry.parent,
          interpolation$$1 = entry.interpolation,
          toValues = toArray(target ? toValue.getPayload() : toValue),
          animatedValues;
      var newValue = value;
      if (isInterpolation) newValue = interpolation({
        range: [0, 1],
        output: [value, value]
      })(1);
      var currentValue = interpolation$$1 && interpolation$$1.getValue(); // Change detection flags

      var isFirst = is.und(parent);
      var isActive = !isFirst && entry.animatedValues.some(function (v) {
        return !v.done;
      });
      var currentValueDiffersFromGoal = !is.equ(newValue, currentValue);
      var hasNewGoal = !is.equ(newValue, entry.previous);
      var hasNewConfig = !is.equ(toConfig, entry.config); // Change animation props when props indicate a new goal (new value differs from previous one)
      // and current values differ from it. Config changes trigger a new update as well (though probably shouldn't?)

      if (reset || hasNewGoal && currentValueDiffersFromGoal || hasNewConfig) {
        var _extends2;

        // Convert regular values into animated values, ALWAYS re-use if possible
        if (isNumber || isString) parent = interpolation$$1 = entry.parent || new AnimatedValue(fromValue);else if (isArray) parent = interpolation$$1 = entry.parent || new AnimatedValueArray(fromValue);else if (isInterpolation) {
          var prev = entry.interpolation && entry.interpolation.calc(entry.parent.value);
          prev = prev !== void 0 && !reset ? prev : fromValue;

          if (entry.parent) {
            parent = entry.parent;
            parent.setValue(0, false);
          } else parent = new AnimatedValue(0);

          var range = {
            output: [prev, value]
          };

          if (entry.interpolation) {
            interpolation$$1 = entry.interpolation;
            entry.interpolation.updateConfig(range);
          } else interpolation$$1 = parent.interpolate(range);
        }
        toValues = toArray(target ? toValue.getPayload() : toValue);
        animatedValues = toArray(parent.getPayload());
        if (reset && !isInterpolation) parent.setValue(fromValue, false);
        _this4.hasChanged = true; // Reset animated values

        animatedValues.forEach(function (value) {
          value.startPosition = value.value;
          value.lastPosition = value.value;
          value.lastVelocity = isActive ? value.lastVelocity : undefined;
          value.lastTime = isActive ? value.lastTime : undefined;
          value.startTime = now();
          value.done = false;
          value.animatedStyles.clear();
        }); // Set immediate values

        if (callProp(immediate, name)) {
          parent.setValue(isInterpolation ? toValue : value, false);
        }

        return _extends({}, acc, (_extends2 = {}, _extends2[name] = _extends({}, entry, {
          name: name,
          parent: parent,
          interpolation: interpolation$$1,
          animatedValues: animatedValues,
          toValues: toValues,
          previous: newValue,
          config: toConfig,
          fromValues: toArray(parent.getValue()),
          immediate: callProp(immediate, name),
          initialVelocity: withDefault(toConfig.velocity, 0),
          clamp: withDefault(toConfig.clamp, false),
          precision: withDefault(toConfig.precision, 0.01),
          tension: withDefault(toConfig.tension, 170),
          friction: withDefault(toConfig.friction, 26),
          mass: withDefault(toConfig.mass, 1),
          duration: toConfig.duration,
          easing: withDefault(toConfig.easing, function (t) {
            return t;
          }),
          decay: toConfig.decay
        }), _extends2));
      } else {
        if (!currentValueDiffersFromGoal) {
          var _extends3;

          // So ... the current target value (newValue) appears to be different from the previous value,
          // which normally constitutes an update, but the actual value (currentValue) matches the target!
          // In order to resolve this without causing an animation update we silently flag the animation as done,
          // which it technically is. Interpolations also needs a config update with their target set to 1.
          if (isInterpolation) {
            parent.setValue(1, false);
            interpolation$$1.updateConfig({
              output: [newValue, newValue]
            });
          }

          parent.done = true;
          _this4.hasChanged = true;
          return _extends({}, acc, (_extends3 = {}, _extends3[name] = _extends({}, acc[name], {
            previous: newValue
          }), _extends3));
        }

        return acc;
      }
    }, this.animations);

    if (this.hasChanged) {
      // Make animations available to frameloop
      this.configs = Object.values(this.animations);
      this.values = {};
      this.interpolations = {};

      for (var key in this.animations) {
        this.interpolations[key] = this.animations[key].interpolation;
        this.values[key] = this.animations[key].interpolation.getValue();
      }
    }

    return this;
  };

  _proto.destroy = function destroy() {
    this.stop();
    this.props = {};
    this.merged = {};
    this.animations = {};
    this.interpolations = {};
    this.values = {};
    this.configs = [];
    this.local = 0;
  };

  return Controller;
}();

/** API
 * const props = useSprings(number, [{ ... }, { ... }, ...])
 * const [props, set] = useSprings(number, (i, controller) => ({ ... }))
 */

var useSprings = function useSprings(length, props) {
  var mounted = React.useRef(false);
  var ctrl = React.useRef();
  var isFn = is.fun(props); // The controller maintains the animation values, starts and stops animations

  var _useMemo = React.useMemo(function () {
    // Remove old controllers
    if (ctrl.current) {
      ctrl.current.map(function (c) {
        return c.destroy();
      });
      ctrl.current = undefined;
    }

    var ref;
    return [new Array(length).fill().map(function (_, i) {
      var ctrl = new Controller();
      var newProps = isFn ? callProp(props, i, ctrl) : props[i];
      if (i === 0) ref = newProps.ref;
      ctrl.update(newProps);
      if (!ref) ctrl.start();
      return ctrl;
    }), ref];
  }, [length]),
      controllers = _useMemo[0],
      ref = _useMemo[1];

  ctrl.current = controllers; // The hooks reference api gets defined here ...

  var api = React.useImperativeHandle(ref, function () {
    return {
      start: function start() {
        return Promise.all(ctrl.current.map(function (c) {
          return new Promise(function (r) {
            return c.start(r);
          });
        }));
      },
      stop: function stop(finished) {
        return ctrl.current.forEach(function (c) {
          return c.stop(finished);
        });
      },

      get controllers() {
        return ctrl.current;
      }

    };
  }); // This function updates the controllers

  var updateCtrl = React.useMemo(function () {
    return function (updateProps) {
      return ctrl.current.map(function (c, i) {
        c.update(isFn ? callProp(updateProps, i, c) : updateProps[i]);
        if (!ref) c.start();
      });
    };
  }, [length]); // Update controller if props aren't functional

  React.useEffect(function () {
    if (mounted.current) {
      if (!isFn) updateCtrl(props);
    } else if (!ref) ctrl.current.forEach(function (c) {
      return c.start();
    });
  }); // Update mounted flag and destroy controller on unmount

  React.useEffect(function () {
    return mounted.current = true, function () {
      return ctrl.current.forEach(function (c) {
        return c.destroy();
      });
    };
  }, []); // Return animated props, or, anim-props + the update-setter above

  var propValues = ctrl.current.map(function (c) {
    return c.getValues();
  });
  return isFn ? [propValues, updateCtrl, function (finished) {
    return ctrl.current.forEach(function (c) {
      return c.pause(finished);
    });
  }] : propValues;
};

/** API
 * const props = useSpring({ ... })
 * const [props, set] = useSpring(() => ({ ... }))
 */

var useSpring = function useSpring(props) {
  var isFn = is.fun(props);

  var _useSprings = useSprings(1, isFn ? props : [props]),
      result = _useSprings[0],
      set = _useSprings[1],
      pause = _useSprings[2];

  return isFn ? [result[0], set, pause] : result;
};

/** API
 * const trails = useTrail(number, { ... })
 * const [trails, set] = useTrail(number, () => ({ ... }))
 */

var useTrail = function useTrail(length, props) {
  var mounted = React.useRef(false);
  var isFn = is.fun(props);
  var updateProps = callProp(props);
  var instances = React.useRef();

  var _useSprings = useSprings(length, function (i, ctrl) {
    if (i === 0) instances.current = [];
    instances.current.push(ctrl);
    return _extends({}, updateProps, {
      config: callProp(updateProps.config, i),
      attach: i > 0 && function () {
        return instances.current[i - 1];
      }
    });
  }),
      result = _useSprings[0],
      set = _useSprings[1],
      pause = _useSprings[2]; // Set up function to update controller


  var updateCtrl = React.useMemo(function () {
    return function (props) {
      return set(function (i, ctrl) {
        var last = props.reverse ? i === 0 : length - 1 === i;
        var attachIdx = props.reverse ? i + 1 : i - 1;
        var attachController = instances.current[attachIdx];
        return _extends({}, props, {
          config: callProp(props.config || updateProps.config, i),
          attach: attachController && function () {
            return attachController;
          }
        });
      });
    };
  }, [length, updateProps.reverse]); // Update controller if props aren't functional

  React.useEffect(function () {
    return void (mounted.current && !isFn && updateCtrl(props));
  }); // Update mounted flag and destroy controller on unmount

  React.useEffect(function () {
    return void (mounted.current = true);
  }, []);
  return isFn ? [result, updateCtrl, pause] : result;
};

/** API
 * const transitions = useTransition(items, itemKeys, { ... })
 * const [transitions, update] = useTransition(items, itemKeys, () => ({ ... }))
 */

var guid = 0;
var ENTER = 'enter';
var LEAVE = 'leave';
var UPDATE = 'update';

var mapKeys = function mapKeys(items, keys) {
  return (typeof keys === 'function' ? items.map(keys) : toArray(keys)).map(String);
};

var get = function get(props) {
  var items = props.items,
      _props$keys = props.keys,
      keys = _props$keys === void 0 ? function (item) {
    return item;
  } : _props$keys,
      rest = _objectWithoutPropertiesLoose(props, ["items", "keys"]);

  items = toArray(items !== void 0 ? items : null);
  return _extends({
    items: items,
    keys: mapKeys(items, keys)
  }, rest);
};

function useTransition(input, keyTransform, config) {
  var props = _extends({
    items: input,
    keys: keyTransform || function (i) {
      return i;
    }
  }, config);

  var _get = get(props),
      _get$lazy = _get.lazy,
      lazy = _get$lazy === void 0 ? false : _get$lazy,
      _get$unique = _get.unique,
      _get$reset = _get.reset,
      reset = _get$reset === void 0 ? false : _get$reset,
      enter = _get.enter,
      leave = _get.leave,
      update = _get.update,
      onDestroyed = _get.onDestroyed,
      keys = _get.keys,
      items = _get.items,
      onFrame = _get.onFrame,
      _onRest = _get.onRest,
      onStart = _get.onStart,
      ref = _get.ref,
      extra = _objectWithoutPropertiesLoose(_get, ["lazy", "unique", "reset", "enter", "leave", "update", "onDestroyed", "keys", "items", "onFrame", "onRest", "onStart", "ref"]);

  var forceUpdate = useForceUpdate();
  var mounted = React.useRef(false);
  var state = React.useRef({
    mounted: false,
    first: true,
    deleted: [],
    current: {},
    transitions: [],
    prevProps: {},
    paused: !!props.ref,
    instances: !mounted.current && new Map(),
    forceUpdate: forceUpdate
  });
  React.useImperativeHandle(props.ref, function () {
    return {
      start: function start() {
        return Promise.all(Array.from(state.current.instances).map(function (_ref) {
          var c = _ref[1];
          return new Promise(function (r) {
            return c.start(r);
          });
        }));
      },
      stop: function stop(finished) {
        return Array.from(state.current.instances).forEach(function (_ref2) {
          var c = _ref2[1];
          return c.stop(finished);
        });
      },

      get controllers() {
        return Array.from(state.current.instances).map(function (_ref3) {
          var c = _ref3[1];
          return c;
        });
      }

    };
  }); // Update state

  state.current = diffItems(state.current, props);

  if (state.current.changed) {
    // Update state
    state.current.transitions.forEach(function (transition) {
      var slot = transition.slot,
          from = transition.from,
          to = transition.to,
          config = transition.config,
          trail = transition.trail,
          key = transition.key,
          item = transition.item;
      if (!state.current.instances.has(key)) state.current.instances.set(key, new Controller()); // update the map object

      var ctrl = state.current.instances.get(key);

      var newProps = _extends({}, extra, {
        to: to,
        from: from,
        config: config,
        ref: ref,
        onRest: function onRest(values) {
          if (state.current.mounted) {
            if (transition.destroyed) {
              // If no ref is given delete destroyed items immediately
              if (!ref && !lazy) cleanUp(state, key);
              if (onDestroyed) onDestroyed(item);
            } // A transition comes to rest once all its springs conclude


            var curInstances = Array.from(state.current.instances);
            var active = curInstances.some(function (_ref4) {
              var c = _ref4[1];
              return !c.idle;
            });
            if (!active && (ref || lazy) && state.current.deleted.length > 0) cleanUp(state);
            if (_onRest) _onRest(item, slot, values);
          }
        },
        onStart: onStart && function () {
          return onStart(item, slot);
        },
        onFrame: onFrame && function (values) {
          return onFrame(item, slot, values);
        },
        delay: trail,
        reset: reset && slot === ENTER // Update controller

      });

      ctrl.update(newProps);
      if (!state.current.paused) ctrl.start();
    });
  }

  React.useEffect(function () {
    state.current.mounted = mounted.current = true;
    return function () {
      state.current.mounted = mounted.current = false;
      Array.from(state.current.instances).map(function (_ref5) {
        var c = _ref5[1];
        return c.destroy();
      });
      state.current.instances.clear();
    };
  }, []);
  return state.current.transitions.map(function (_ref6) {
    var item = _ref6.item,
        slot = _ref6.slot,
        key = _ref6.key;
    return {
      item: item,
      key: key,
      state: slot,
      props: state.current.instances.get(key).getValues()
    };
  });
}

function cleanUp(state, filterKey) {
  var deleted = state.current.deleted;

  var _loop = function _loop() {
    if (_isArray) {
      if (_i >= _iterator.length) return "break";
      _ref8 = _iterator[_i++];
    } else {
      _i = _iterator.next();
      if (_i.done) return "break";
      _ref8 = _i.value;
    }

    var _ref7 = _ref8;
    var key = _ref7.key;

    var filter = function filter(t) {
      return t.key !== key;
    };

    if (is.und(filterKey) || filterKey === key) {
      state.current.instances.delete(key);
      state.current.transitions = state.current.transitions.filter(filter);
      state.current.deleted = state.current.deleted.filter(filter);
    }
  };

  for (var _iterator = deleted, _isArray = Array.isArray(_iterator), _i = 0, _iterator = _isArray ? _iterator : _iterator[Symbol.iterator]();;) {
    var _ref8;

    var _ret = _loop();

    if (_ret === "break") break;
  }

  state.current.forceUpdate();
}

function diffItems(_ref9, props) {
  var first = _ref9.first,
      prevProps = _ref9.prevProps,
      state = _objectWithoutPropertiesLoose(_ref9, ["first", "prevProps"]);

  var _get2 = get(props),
      items = _get2.items,
      keys = _get2.keys,
      initial = _get2.initial,
      from = _get2.from,
      enter = _get2.enter,
      leave = _get2.leave,
      update = _get2.update,
      _get2$trail = _get2.trail,
      trail = _get2$trail === void 0 ? 0 : _get2$trail,
      unique = _get2.unique,
      config = _get2.config,
      _get2$order = _get2.order,
      order = _get2$order === void 0 ? [ENTER, LEAVE, UPDATE] : _get2$order;

  var _get3 = get(prevProps),
      _keys = _get3.keys,
      _items = _get3.items;

  var current = _extends({}, state.current);

  var deleted = [].concat(state.deleted); // Compare next keys with current keys

  var currentKeys = Object.keys(current);
  var currentSet = new Set(currentKeys);
  var nextSet = new Set(keys);
  var added = keys.filter(function (item) {
    return !currentSet.has(item);
  });
  var removed = state.transitions.filter(function (item) {
    return !item.destroyed && !nextSet.has(item.originalKey);
  }).map(function (i) {
    return i.originalKey;
  });
  var updated = keys.filter(function (item) {
    return currentSet.has(item);
  });
  var delay = -trail;

  while (order.length) {
    var changeType = order.shift();

    switch (changeType) {
      case ENTER:
        {
          added.forEach(function (key, index) {
            // In unique mode, remove fading out transitions if their key comes in again
            if (unique && deleted.find(function (d) {
              return d.originalKey === key;
            })) deleted = deleted.filter(function (t) {
              return t.originalKey !== key;
            });
            var keyIndex = keys.indexOf(key);
            var item = items[keyIndex];
            var slot = first && initial !== void 0 ? 'initial' : ENTER;
            current[key] = {
              slot: slot,
              originalKey: key,
              key: unique ? String(key) : guid++,
              item: item,
              trail: delay = delay + trail,
              config: callProp(config, item, slot),
              from: callProp(first ? initial !== void 0 ? initial || {} : from : from, item),
              to: callProp(enter, item)
            };
          });
          break;
        }

      case LEAVE:
        {
          removed.forEach(function (key) {
            var keyIndex = _keys.indexOf(key);

            var item = _items[keyIndex];
            var slot = LEAVE;
            deleted.unshift(_extends({}, current[key], {
              slot: slot,
              destroyed: true,
              left: _keys[Math.max(0, keyIndex - 1)],
              right: _keys[Math.min(_keys.length, keyIndex + 1)],
              trail: delay = delay + trail,
              config: callProp(config, item, slot),
              to: callProp(leave, item)
            }));
            delete current[key];
          });
          break;
        }

      case UPDATE:
        {
          updated.forEach(function (key) {
            var keyIndex = keys.indexOf(key);
            var item = items[keyIndex];
            var slot = UPDATE;
            current[key] = _extends({}, current[key], {
              item: item,
              slot: slot,
              trail: delay = delay + trail,
              config: callProp(config, item, slot),
              to: callProp(update, item)
            });
          });
          break;
        }
    }
  }

  var out = keys.map(function (key) {
    return current[key];
  }); // This tries to restore order for deleted items by finding their last known siblings
  // only using the left sibling to keep order placement consistent for all deleted items

  deleted.forEach(function (_ref10) {
    var left = _ref10.left,
        right = _ref10.right,
        item = _objectWithoutPropertiesLoose(_ref10, ["left", "right"]);

    var pos; // Was it the element on the left, if yes, move there ...

    if ((pos = out.findIndex(function (t) {
      return t.originalKey === left;
    })) !== -1) pos += 1; // And if nothing else helps, move it to the start ¯\_(ツ)_/¯

    pos = Math.max(0, pos);
    out = [].concat(out.slice(0, pos), [item], out.slice(pos));
  });
  return _extends({}, state, {
    changed: added.length || removed.length || updated.length,
    first: first && added.length === 0,
    transitions: out,
    current: current,
    deleted: deleted,
    prevProps: props
  });
}

var AnimatedStyle =
/*#__PURE__*/
function (_AnimatedObject) {
  _inheritsLoose(AnimatedStyle, _AnimatedObject);

  function AnimatedStyle(style) {
    var _this;

    if (style === void 0) {
      style = {};
    }

    _this = _AnimatedObject.call(this) || this;

    if (style.transform && !(style.transform instanceof Animated)) {
      style = applyAnimatedValues.transform(style);
    }

    _this.payload = style;
    return _this;
  }

  return AnimatedStyle;
}(AnimatedObject);

// http://www.w3.org/TR/css3-color/#svg-color
var colors = {
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

// const INTEGER = '[-+]?\\d+';
var NUMBER = '[-+]?\\d*\\.?\\d+';
var PERCENTAGE = NUMBER + '%';

function call() {
  for (var _len = arguments.length, parts = new Array(_len), _key = 0; _key < _len; _key++) {
    parts[_key] = arguments[_key];
  }

  return '\\(\\s*(' + parts.join(')\\s*,\\s*(') + ')\\s*\\)';
}

var rgb = new RegExp('rgb' + call(NUMBER, NUMBER, NUMBER));
var rgba = new RegExp('rgba' + call(NUMBER, NUMBER, NUMBER, NUMBER));
var hsl = new RegExp('hsl' + call(NUMBER, PERCENTAGE, PERCENTAGE));
var hsla = new RegExp('hsla' + call(NUMBER, PERCENTAGE, PERCENTAGE, NUMBER));
var hex3 = /^#([0-9a-fA-F]{1})([0-9a-fA-F]{1})([0-9a-fA-F]{1})$/;
var hex4 = /^#([0-9a-fA-F]{1})([0-9a-fA-F]{1})([0-9a-fA-F]{1})([0-9a-fA-F]{1})$/;
var hex6 = /^#([0-9a-fA-F]{6})$/;
var hex8 = /^#([0-9a-fA-F]{8})$/;

/*
https://github.com/react-community/normalize-css-color

BSD 3-Clause License

Copyright (c) 2016, React Community
All rights reserved.

Redistribution and use in source and binary forms, with or without
modification, are permitted provided that the following conditions are met:

* Redistributions of source code must retain the above copyright notice, this
  list of conditions and the following disclaimer.

* Redistributions in binary form must reproduce the above copyright notice,
  this list of conditions and the following disclaimer in the documentation
  and/or other materials provided with the distribution.

* Neither the name of the copyright holder nor the names of its
  contributors may be used to endorse or promote products derived from
  this software without specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE
FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY,
OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/
function normalizeColor(color) {
  var match;

  if (typeof color === 'number') {
    return color >>> 0 === color && color >= 0 && color <= 0xffffffff ? color : null;
  } // Ordered based on occurrences on Facebook codebase


  if (match = hex6.exec(color)) return parseInt(match[1] + 'ff', 16) >>> 0;
  if (colors.hasOwnProperty(color)) return colors[color];

  if (match = rgb.exec(color)) {
    return (parse255(match[1]) << 24 | // r
    parse255(match[2]) << 16 | // g
    parse255(match[3]) << 8 | // b
    0x000000ff) >>> // a
    0;
  }

  if (match = rgba.exec(color)) {
    return (parse255(match[1]) << 24 | // r
    parse255(match[2]) << 16 | // g
    parse255(match[3]) << 8 | // b
    parse1(match[4])) >>> // a
    0;
  }

  if (match = hex3.exec(color)) {
    return parseInt(match[1] + match[1] + // r
    match[2] + match[2] + // g
    match[3] + match[3] + // b
    'ff', // a
    16) >>> 0;
  } // https://drafts.csswg.org/css-color-4/#hex-notation


  if (match = hex8.exec(color)) return parseInt(match[1], 16) >>> 0;

  if (match = hex4.exec(color)) {
    return parseInt(match[1] + match[1] + // r
    match[2] + match[2] + // g
    match[3] + match[3] + // b
    match[4] + match[4], // a
    16) >>> 0;
  }

  if (match = hsl.exec(color)) {
    return (hslToRgb(parse360(match[1]), // h
    parsePercentage(match[2]), // s
    parsePercentage(match[3]) // l
    ) | 0x000000ff) >>> // a
    0;
  }

  if (match = hsla.exec(color)) {
    return (hslToRgb(parse360(match[1]), // h
    parsePercentage(match[2]), // s
    parsePercentage(match[3]) // l
    ) | parse1(match[4])) >>> // a
    0;
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
  var q = l < 0.5 ? l * (1 + s) : l + s - l * s;
  var p = 2 * l - q;
  var r = hue2rgb(p, q, h + 1 / 3);
  var g = hue2rgb(p, q, h);
  var b = hue2rgb(p, q, h - 1 / 3);
  return Math.round(r * 255) << 24 | Math.round(g * 255) << 16 | Math.round(b * 255) << 8;
}

function parse255(str) {
  var int = parseInt(str, 10);
  if (int < 0) return 0;
  if (int > 255) return 255;
  return int;
}

function parse360(str) {
  var int = parseFloat(str);
  return (int % 360 + 360) % 360 / 360;
}

function parse1(str) {
  var num = parseFloat(str);
  if (num < 0) return 0;
  if (num > 1) return 255;
  return Math.round(num * 255);
}

function parsePercentage(str) {
  // parseFloat conveniently ignores the final %
  var int = parseFloat(str);
  if (int < 0) return 0;
  if (int > 100) return 1;
  return int / 100;
}

function colorToRgba(input) {
  var int32Color = normalizeColor(input);
  if (int32Color === null) return input;
  int32Color = int32Color || 0;
  var r = (int32Color & 0xff000000) >>> 24;
  var g = (int32Color & 0x00ff0000) >>> 16;
  var b = (int32Color & 0x0000ff00) >>> 8;
  var a = (int32Color & 0x000000ff) / 255;
  return "rgba(" + r + ", " + g + ", " + b + ", " + a + ")";
} // Problem: https://github.com/animatedjs/animated/pull/102
// Solution: https://stackoverflow.com/questions/638565/parsing-scientific-notation-sensibly/658662


var stringShapeRegex = /[+\-]?(?:0|[1-9]\d*)(?:\.\d*)?(?:[eE][+\-]?\d+)?/g; // Covers rgb, rgba, hsl, hsla
// Taken from https://gist.github.com/olmokramer/82ccce673f86db7cda5e

var colorRegex = /(#(?:[0-9a-f]{2}){2,4}|(#[0-9a-f]{3})|(rgb|hsl)a?\((-?\d+%?[,\s]+){2,3}\s*[\d\.]+%?\))/gi; // Covers color names (transparent, blue, etc.)

var colorNamesRegex = new RegExp("(" + Object.keys(colors).join('|') + ")", 'g');
/**
 * Supports string shapes by extracting numbers so new values can be computed,
 * and recombines those values into new strings of the same shape.  Supports
 * things like:
 *
 *   rgba(123, 42, 99, 0.36)           // colors
 *   -45deg                            // values with units
 *   0 2px 2px 0px rgba(0, 0, 0, 0.12) // box shadows
 */

var createStringInterpolator = function createStringInterpolator(config) {
  // Replace colors with rgba
  var outputRange = config.output.map(function (rangeValue) {
    return rangeValue.replace(colorRegex, colorToRgba);
  }).map(function (rangeValue) {
    return rangeValue.replace(colorNamesRegex, colorToRgba);
  });
  var outputRanges = outputRange[0].match(stringShapeRegex).map(function () {
    return [];
  });
  outputRange.forEach(function (value) {
    value.match(stringShapeRegex).forEach(function (number, i) {
      return outputRanges[i].push(+number);
    });
  });
  var interpolations = outputRange[0].match(stringShapeRegex).map(function (_value, i) {
    return createInterpolator(_extends({}, config, {
      output: outputRanges[i]
    }));
  });
  return function (input) {
    var i = 0;
    return outputRange[0] // 'rgba(0, 100, 200, 0)'
    // ->
    // 'rgba(${interpolations[0](input)}, ${interpolations[1](input)}, ...'
    .replace(stringShapeRegex, function () {
      return interpolations[i++](input);
    }) // rgba requires that the r,g,b are integers.... so we want to round them, but we *dont* want to
    // round the opacity (4th column).
    .replace(/rgba\(([0-9\.-]+), ([0-9\.-]+), ([0-9\.-]+), ([0-9\.-]+)\)/gi, function (_, p1, p2, p3, p4) {
      return "rgba(" + Math.round(p1) + ", " + Math.round(p2) + ", " + Math.round(p3) + ", " + p4 + ")";
    });
  };
};

var isUnitlessNumber = {
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
  // SVG-related properties
  fillOpacity: true,
  floodOpacity: true,
  stopOpacity: true,
  strokeDasharray: true,
  strokeDashoffset: true,
  strokeMiterlimit: true,
  strokeOpacity: true,
  strokeWidth: true
};

var prefixKey = function prefixKey(prefix, key) {
  return prefix + key.charAt(0).toUpperCase() + key.substring(1);
};

var prefixes = ['Webkit', 'Ms', 'Moz', 'O'];
isUnitlessNumber = Object.keys(isUnitlessNumber).reduce(function (acc, prop) {
  prefixes.forEach(function (prefix) {
    return acc[prefixKey(prefix, prop)] = acc[prop];
  });
  return acc;
}, isUnitlessNumber);

function dangerousStyleValue(name, value, isCustomProperty) {
  if (value == null || typeof value === 'boolean' || value === '') return '';
  if (!isCustomProperty && typeof value === 'number' && value !== 0 && !(isUnitlessNumber.hasOwnProperty(name) && isUnitlessNumber[name])) return value + 'px'; // Presumes implicit 'px' suffix for unitless numbers

  return ('' + value).trim();
}

var attributeCache = {};
injectCreateAnimatedStyle(function (style) {
  return new AnimatedStyle(style);
});
injectDefaultElement('div');
injectStringInterpolator(createStringInterpolator);
injectColorNames(colors);
injectApplyAnimatedValues(function (instance, props) {
  if (instance.nodeType && instance.setAttribute !== undefined) {
    var style = props.style,
        children = props.children,
        scrollTop = props.scrollTop,
        scrollLeft = props.scrollLeft,
        attributes = _objectWithoutPropertiesLoose(props, ["style", "children", "scrollTop", "scrollLeft"]);

    var filter = instance.nodeName === 'filter' || instance.parentNode && instance.parentNode.nodeName === 'filter';
    if (scrollTop !== void 0) instance.scrollTop = scrollTop;
    if (scrollLeft !== void 0) instance.scrollLeft = scrollLeft; // Set textContent, if children is an animatable value

    if (children !== void 0) instance.textContent = children; // Set styles ...

    for (var styleName in style) {
      if (!style.hasOwnProperty(styleName)) continue;
      var isCustomProperty = styleName.indexOf('--') === 0;
      var styleValue = dangerousStyleValue(styleName, style[styleName], isCustomProperty);
      if (styleName === 'float') styleName = 'cssFloat';
      if (isCustomProperty) instance.style.setProperty(styleName, styleValue);else instance.style[styleName] = styleValue;
    } // Set attributes ...


    for (var name in attributes) {
      // Attributes are written in dash case
      var dashCase = filter ? name : attributeCache[name] || (attributeCache[name] = name.replace(/([A-Z])/g, function (n) {
        return '-' + n.toLowerCase();
      }));
      if (typeof instance.getAttribute(dashCase) !== 'undefined') instance.setAttribute(dashCase, attributes[name]);
    }

    return;
  } else return false;
}, function (style) {
  return style;
});

var domElements = ['a', 'abbr', 'address', 'area', 'article', 'aside', 'audio', 'b', 'base', 'bdi', 'bdo', 'big', 'blockquote', 'body', 'br', 'button', 'canvas', 'caption', 'cite', 'code', 'col', 'colgroup', 'data', 'datalist', 'dd', 'del', 'details', 'dfn', 'dialog', 'div', 'dl', 'dt', 'em', 'embed', 'fieldset', 'figcaption', 'figure', 'footer', 'form', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'head', 'header', 'hgroup', 'hr', 'html', 'i', 'iframe', 'img', 'input', 'ins', 'kbd', 'keygen', 'label', 'legend', 'li', 'link', 'main', 'map', 'mark', 'menu', 'menuitem', 'meta', 'meter', 'nav', 'noscript', 'object', 'ol', 'optgroup', 'option', 'output', 'p', 'param', 'picture', 'pre', 'progress', 'q', 'rp', 'rt', 'ruby', 's', 'samp', 'script', 'section', 'select', 'small', 'source', 'span', 'strong', 'style', 'sub', 'summary', 'sup', 'table', 'tbody', 'td', 'textarea', 'tfoot', 'th', 'thead', 'time', 'title', 'tr', 'track', 'u', 'ul', 'var', 'video', 'wbr', // SVG
'circle', 'clipPath', 'defs', 'ellipse', 'foreignObject', 'g', 'image', 'line', 'linearGradient', 'mask', 'path', 'pattern', 'polygon', 'polyline', 'radialGradient', 'rect', 'stop', 'svg', 'text', 'tspan'];
// Extend animated with all the available THREE elements
var apply = merge(createAnimatedComponent, false);
var extendedAnimated = apply(domElements);

exports.apply = apply;
exports.config = config;
exports.update = update;
exports.animated = extendedAnimated;
exports.a = extendedAnimated;
exports.interpolate = interpolate$1;
exports.Globals = Globals;
exports.useSpring = useSpring;
exports.useTrail = useTrail;
exports.useTransition = useTransition;
exports.useChain = useChain;
exports.useSprings = useSprings;


/***/ }),

/***/ 258:
/***/ (function(module, exports, __webpack_require__) {

var isarray = __webpack_require__(440)

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

/***/ 259:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* WEBPACK VAR INJECTION */(function(global, module) {/* harmony import */ var _ponyfill_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(418);
/* global window */


var root;

if (typeof self !== 'undefined') {
  root = self;
} else if (typeof window !== 'undefined') {
  root = window;
} else if (typeof global !== 'undefined') {
  root = global;
} else if (true) {
  root = module;
} else {}

var result = Object(_ponyfill_js__WEBPACK_IMPORTED_MODULE_0__[/* default */ "a"])(root);
/* harmony default export */ __webpack_exports__["a"] = (result);

/* WEBPACK VAR INJECTION */}.call(this, __webpack_require__(64), __webpack_require__(442)(module)))

/***/ }),

/***/ 26:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "a", function() { return ADMIN_URL; });
/* unused harmony export COUNTRIES */
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "b", function() { return CURRENCY; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "c", function() { return LOCALE; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "d", function() { return ORDER_STATUSES; });
/* unused harmony export SITE_TITLE */
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "e", function() { return WC_ASSET_URL; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "g", function() { return getSetting; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "h", function() { return setSetting; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "f", function() { return getAdminLink; });
/* harmony import */ var _babel_runtime_helpers_typeof__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(43);
/* harmony import */ var _babel_runtime_helpers_typeof__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_typeof__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(3);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__);


/**
 * External dependencies
 */
 // Remove mutable data from settings object to prevent access. Data stores should be used instead.

var mutableSources = ['wcAdminSettings', 'preloadSettings'];
var settings = (typeof wcSettings === "undefined" ? "undefined" : _babel_runtime_helpers_typeof__WEBPACK_IMPORTED_MODULE_0___default()(wcSettings)) === 'object' ? wcSettings : {};
var SOURCE = Object.keys(settings).reduce(function (source, key) {
  if (!mutableSources.includes(key)) {
    source[key] = settings[key];
  }

  return source;
}, {});
var ADMIN_URL = SOURCE.adminUrl;
var COUNTRIES = SOURCE.countries;
var CURRENCY = SOURCE.currency;
var LOCALE = SOURCE.locale;
var ORDER_STATUSES = SOURCE.orderStatuses;
var SITE_TITLE = SOURCE.siteTitle;
var WC_ASSET_URL = SOURCE.wcAssetUrl;
/**
 * Retrieves a setting value from the setting state.
 *
 * @export
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

function getSetting(name) {
  var fallback = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : false;
  var filter = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : function (val) {
    return val;
  };

  if (mutableSources.includes(name)) {
    throw new Error(Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Mutable settings should be accessed via data store.'));
  }

  var value = SOURCE.hasOwnProperty(name) ? SOURCE[name] : fallback;
  return filter(value, fallback);
}
/**
 * Sets a value to a property on the settings state.
 *
 * NOTE: This feature is to be removed in favour of data stores when a full migration
 * is complete.
 *
 * @deprecated
 *
 * @export
 * @param {string}   name                        The setting property key for the
 *                                               setting being mutated.
 * @param {*}    value                       The value to set.
 * @param {Function} [filter=( val ) => val]     Allows for providing a callback
 *                                               to sanitize the setting (eg.
 *                                               ensure it's a number)
 */

function setSetting(name, value) {
  var filter = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : function (val) {
    return val;
  };

  if (mutableSources.includes(name)) {
    throw new Error(Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Mutable settings should be mutated via data store.'));
  }

  SOURCE[name] = filter(value);
}
/**
 * Returns a string with the site's wp-admin URL appended. JS version of `admin_url`.
 *
 * @param {string} path Relative path.
 * @return {string} Full admin URL.
 */

function getAdminLink(path) {
  return (ADMIN_URL || '') + path;
}

/***/ }),

/***/ 261:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(41);
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(40);
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(44);
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(29);
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(42);
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(3);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(1);
/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(prop_types__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(63);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_components__WEBPACK_IMPORTED_MODULE_8__);
/* harmony import */ var _woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(26);







function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function () { var Super = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_3___default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_3___default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_2___default()(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/**
 * External dependencies
 */



/**
 * WooCommerce dependencies
 */



/**
 * Component to render when there is an error in a report component due to data
 * not being loaded or being invalid.
 */

var ReportError = /*#__PURE__*/function (_Component) {
  _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_4___default()(ReportError, _Component);

  var _super = _createSuper(ReportError);

  function ReportError() {
    _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0___default()(this, ReportError);

    return _super.apply(this, arguments);
  }

  _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1___default()(ReportError, [{
    key: "render",
    value: function render() {
      var _this$props = this.props,
          className = _this$props.className,
          isError = _this$props.isError,
          isEmpty = _this$props.isEmpty;
      var title, actionLabel, actionURL, actionCallback;

      if (isError) {
        title = Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__["__"])('There was an error getting your stats. Please try again.', 'woocommerce');
        actionLabel = Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__["__"])('Reload', 'woocommerce');

        actionCallback = function actionCallback() {
          // @todo Add tracking for how often an error is displayed, and the reload action is clicked.
          window.location.reload();
        };
      } else if (isEmpty) {
        title = Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__["__"])('No results could be found for this date range.', 'woocommerce');
        actionLabel = Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__["__"])('View Orders', 'woocommerce');
        actionURL = Object(_woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_9__[/* getAdminLink */ "f"])('edit.php?post_type=shop_order');
      }

      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_8__["EmptyContent"], {
        className: className,
        title: title,
        actionLabel: actionLabel,
        actionURL: actionURL,
        actionCallback: actionCallback
      });
    }
  }]);

  return ReportError;
}(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["Component"]);

ReportError.propTypes = {
  /**
   * Additional class name to style the component.
   */
  className: prop_types__WEBPACK_IMPORTED_MODULE_7___default.a.string,

  /**
   * Boolean representing whether there was an error.
   */
  isError: prop_types__WEBPACK_IMPORTED_MODULE_7___default.a.bool,

  /**
   * Boolean representing whether the issue is that there is no data.
   */
  isEmpty: prop_types__WEBPACK_IMPORTED_MODULE_7___default.a.bool
};
ReportError.defaultProps = {
  className: ''
};
/* harmony default export */ __webpack_exports__["a"] = (ReportError);

/***/ }),

/***/ 263:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";

// EXPORTS
__webpack_require__.d(__webpack_exports__, "a", function() { return /* binding */ DEFAULT_ACTIONABLE_STATUSES; });
__webpack_require__.d(__webpack_exports__, "b", function() { return /* binding */ config; });

// UNUSED EXPORTS: DEFAULT_ORDER_STATUSES, DEFAULT_DATE_RANGE

// EXTERNAL MODULE: external {"this":["wp","element"]}
var external_this_wp_element_ = __webpack_require__(0);

// EXTERNAL MODULE: external {"this":["wp","i18n"]}
var external_this_wp_i18n_ = __webpack_require__(3);

// EXTERNAL MODULE: external {"this":["wp","hooks"]}
var external_this_wp_hooks_ = __webpack_require__(48);

// EXTERNAL MODULE: ./node_modules/interpolate-components/lib/index.js
var lib = __webpack_require__(35);
var lib_default = /*#__PURE__*/__webpack_require__.n(lib);

// EXTERNAL MODULE: ./client/settings/index.js
var settings = __webpack_require__(26);

// EXTERNAL MODULE: ./node_modules/qs/lib/index.js
var qs_lib = __webpack_require__(58);

// EXTERNAL MODULE: ./client/lib/date.js
var date = __webpack_require__(104);

// EXTERNAL MODULE: external {"this":["wc","components"]}
var external_this_wc_components_ = __webpack_require__(63);

// EXTERNAL MODULE: external {"this":["wc","data"]}
var external_this_wc_data_ = __webpack_require__(51);

// CONCATENATED MODULE: ./client/analytics/settings/default-date.js


/**
 * External dependencies
 */

/**
 * Internal dependencies
 */


/**
 * WooCommerce dependencies
 */




var default_date_DefaultDate = function DefaultDate(_ref) {
  var value = _ref.value,
      onChange = _ref.onChange;

  var _useSettings = Object(external_this_wc_data_["useSettings"])('wc_admin', ['wcAdminSettings']),
      wcAdminSettings = _useSettings.wcAdminSettings;

  var defaultDateRange = wcAdminSettings.woocommerce_default_date_range;

  var change = function change(query) {
    onChange({
      target: {
        name: 'woocommerce_default_date_range',
        value: Object(qs_lib["stringify"])(query)
      }
    });
  };

  var query = Object(qs_lib["parse"])(value.replace(/&amp;/g, '&'));

  var _getDateParamsFromQue = Object(date["h" /* getDateParamsFromQuery */])(query, defaultDateRange),
      period = _getDateParamsFromQue.period,
      compare = _getDateParamsFromQue.compare,
      before = _getDateParamsFromQue.before,
      after = _getDateParamsFromQue.after;

  var _getCurrentDates = Object(date["f" /* getCurrentDates */])(query, defaultDateRange),
      primaryDate = _getCurrentDates.primary,
      secondaryDate = _getCurrentDates.secondary;

  var dateQuery = {
    period: period,
    compare: compare,
    before: before,
    after: after,
    primaryDate: primaryDate,
    secondaryDate: secondaryDate
  };
  return Object(external_this_wp_element_["createElement"])(external_this_wc_components_["DateRangeFilterPicker"], {
    query: query,
    onRangeSelect: change,
    dateQuery: dateQuery,
    isoDateFormat: date["k" /* isoDateFormat */]
  });
};

/* harmony default export */ var default_date = (default_date_DefaultDate);
// CONCATENATED MODULE: ./client/analytics/settings/config.js


/**
 * External dependencies
 */




/**
 * Internal dependencies
 */


var SETTINGS_FILTER = 'woocommerce_admin_analytics_settings';
var DEFAULT_ACTIONABLE_STATUSES = ['processing', 'on-hold'];
var DEFAULT_ORDER_STATUSES = ['completed', 'processing', 'refunded', 'cancelled', 'failed', 'pending', 'on-hold'];
var DEFAULT_DATE_RANGE = 'period=month&compare=previous_year';
var filteredOrderStatuses = Object.keys(settings["d" /* ORDER_STATUSES */]).filter(function (status) {
  return status !== 'refunded';
}).map(function (key) {
  return {
    value: key,
    label: settings["d" /* ORDER_STATUSES */][key],
    description: Object(external_this_wp_i18n_["sprintf"])(Object(external_this_wp_i18n_["__"])('Exclude the %s status from reports', 'woocommerce'), settings["d" /* ORDER_STATUSES */][key])
  };
});
var unregisteredOrderStatuses = Object(settings["g" /* getSetting */])('unregisteredOrderStatuses', {});
var orderStatusOptions = [{
  key: 'defaultStatuses',
  options: filteredOrderStatuses.filter(function (status) {
    return DEFAULT_ORDER_STATUSES.includes(status.value);
  })
}, {
  key: 'customStatuses',
  label: Object(external_this_wp_i18n_["__"])('Custom Statuses', 'woocommerce'),
  options: filteredOrderStatuses.filter(function (status) {
    return !DEFAULT_ORDER_STATUSES.includes(status.value);
  })
}, {
  key: 'unregisteredStatuses',
  label: Object(external_this_wp_i18n_["__"])('Unregistered Statuses', 'woocommerce'),
  options: Object.keys(unregisteredOrderStatuses).map(function (key) {
    return {
      value: key,
      label: key,
      description: Object(external_this_wp_i18n_["sprintf"])(Object(external_this_wp_i18n_["__"])('Exclude the %s status from reports', 'woocommerce'), key)
    };
  })
}];
var config = Object(external_this_wp_hooks_["applyFilters"])(SETTINGS_FILTER, {
  woocommerce_excluded_report_order_statuses: {
    label: Object(external_this_wp_i18n_["__"])('Excluded Statuses:', 'woocommerce'),
    inputType: 'checkboxGroup',
    options: orderStatusOptions,
    helpText: lib_default()({
      mixedString: Object(external_this_wp_i18n_["__"])('Orders with these statuses are excluded from the totals in your reports. ' + 'The {{strong}}Refunded{{/strong}} status can not be excluded.', 'woocommerce'),
      components: {
        strong: Object(external_this_wp_element_["createElement"])("strong", null)
      }
    }),
    defaultValue: ['pending', 'cancelled', 'failed']
  },
  woocommerce_actionable_order_statuses: {
    label: Object(external_this_wp_i18n_["__"])('Actionable Statuses:', 'woocommerce'),
    inputType: 'checkboxGroup',
    options: orderStatusOptions,
    helpText: Object(external_this_wp_i18n_["__"])('Orders with these statuses require action on behalf of the store admin.' + 'These orders will show up in the Orders tab under the activity panel.', 'woocommerce'),
    defaultValue: DEFAULT_ACTIONABLE_STATUSES
  },
  woocommerce_default_date_range: {
    name: 'woocommerce_default_date_range',
    label: Object(external_this_wp_i18n_["__"])('Default Date Range:', 'woocommerce'),
    inputType: 'component',
    component: default_date,
    helpText: Object(external_this_wp_i18n_["__"])('Select a default date range. When no range is selected, reports will be viewed by ' + 'the default date range.', 'woocommerce'),
    defaultValue: DEFAULT_DATE_RANGE
  }
});

/***/ }),

/***/ 266:
/***/ (function(module, exports) {

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

module.exports = _objectWithoutPropertiesLoose;

/***/ }),

/***/ 267:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "a", function() { return getLeaderboard; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "b", function() { return searchItemsByString; });
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(15);
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var lib_date__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(104);


function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0___default()(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

/**
 * External dependencies
 */

/**
 * WooCommerce dependencies
 */

/**
 * Returns leaderboard data to render a leaderboard table.
 *
 * @param  {Object} options                 arguments
 * @param  {string} options.id              Leaderboard ID
 * @param  {number} options.per_page       Per page limit
 * @param  {Object} options.persisted_query Persisted query passed to endpoint
 * @param  {Object} options.query           Query parameters in the url
 * @param  {Object} options.select          Instance of @wordpress/select
 * @param  {string} options.defaultDateRange   User specified default date range.
 * @return {Object} Object containing leaderboard responses.
 */

function getLeaderboard(options) {
  var endpoint = 'leaderboards';
  var perPage = options.per_page,
      persistedQuery = options.persisted_query,
      query = options.query,
      select = options.select;

  var _select = select('wc-api'),
      getItems = _select.getItems,
      getItemsError = _select.getItemsError,
      isGetItemsRequesting = _select.isGetItemsRequesting;

  var response = {
    isRequesting: false,
    isError: false,
    rows: []
  };
  var datesFromQuery = Object(lib_date__WEBPACK_IMPORTED_MODULE_1__[/* getCurrentDates */ "f"])(query, options.defaultDateRange);
  var leaderboardQuery = {
    after: Object(lib_date__WEBPACK_IMPORTED_MODULE_1__[/* appendTimestamp */ "a"])(datesFromQuery.primary.after, 'start'),
    before: Object(lib_date__WEBPACK_IMPORTED_MODULE_1__[/* appendTimestamp */ "a"])(datesFromQuery.primary.before, 'end'),
    per_page: perPage,
    persisted_query: JSON.stringify(persistedQuery)
  }; // Disable eslint rule requiring `getItems` to be defined below because the next two statements
  // depend on `getItems` to have been called.
  // eslint-disable-next-line @wordpress/no-unused-vars-before-return

  var leaderboards = getItems(endpoint, leaderboardQuery);

  if (isGetItemsRequesting(endpoint, leaderboardQuery)) {
    return _objectSpread({}, response, {
      isRequesting: true
    });
  } else if (getItemsError(endpoint, leaderboardQuery)) {
    return _objectSpread({}, response, {
      isError: true
    });
  }

  var leaderboard = leaderboards.get(options.id);
  return _objectSpread({}, response, {
    rows: leaderboard.rows
  });
}
/**
 * Returns items based on a search query.
 *
 * @param  {Object}   select    Instance of @wordpress/select
 * @param  {string}   endpoint  Report API Endpoint
 * @param  {string[]} search    Array of search strings.
 * @return {Object}   Object containing API request information and the matching items.
 */

function searchItemsByString(select, endpoint, search) {
  var _select2 = select('wc-api'),
      getItems = _select2.getItems,
      getItemsError = _select2.getItemsError,
      isGetItemsRequesting = _select2.isGetItemsRequesting;

  var items = {};
  var isRequesting = false;
  var isError = false;
  search.forEach(function (searchWord) {
    var query = {
      search: searchWord,
      per_page: 10
    };
    var newItems = getItems(endpoint, query);
    newItems.forEach(function (item, id) {
      items[id] = item;
    });

    if (isGetItemsRequesting(endpoint, query)) {
      isRequesting = true;
    }

    if (getItemsError(endpoint, query)) {
      isError = true;
    }
  });
  return {
    items: items,
    isRequesting: isRequesting,
    isError: isError
  };
}

/***/ }),

/***/ 268:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _use_media_query__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(99);
/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */


/**
 * @typedef {"huge"|"wide"|"large"|"medium"|"small"|"mobile"} WPBreakpoint
 */

/**
 * Hash of breakpoint names with pixel width at which it becomes effective.
 *
 * @see _breakpoints.scss
 *
 * @type {Object<WPBreakpoint,number>}
 */

var BREAKPOINTS = {
  huge: 1440,
  wide: 1280,
  large: 960,
  medium: 782,
  small: 600,
  mobile: 480
};
/**
 * @typedef {">="|"<"} WPViewportOperator
 */

/**
 * Object mapping media query operators to the condition to be used.
 *
 * @type {Object<WPViewportOperator,string>}
 */

var CONDITIONS = {
  '>=': 'min-width',
  '<': 'max-width'
};
/**
 * Object mapping media query operators to a function that given a breakpointValue and a width evaluates if the operator matches the values.
 *
 * @type {Object<WPViewportOperator,Function>}
 */

var OPERATOR_EVALUATORS = {
  '>=': function _(breakpointValue, width) {
    return width >= breakpointValue;
  },
  '<': function _(breakpointValue, width) {
    return width < breakpointValue;
  }
};
var ViewportMatchWidthContext = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createContext"])(null);
/**
 * Returns true if the viewport matches the given query, or false otherwise.
 *
 * @param {WPBreakpoint}       breakpoint      Breakpoint size name.
 * @param {WPViewportOperator} [operator=">="] Viewport operator.
 *
 * @example
 *
 * ```js
 * useViewportMatch( 'huge', '<' );
 * useViewportMatch( 'medium' );
 * ```
 *
 * @return {boolean} Whether viewport matches query.
 */

var useViewportMatch = function useViewportMatch(breakpoint) {
  var operator = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : '>=';
  var simulatedWidth = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["useContext"])(ViewportMatchWidthContext);
  var mediaQuery = !simulatedWidth && "(".concat(CONDITIONS[operator], ": ").concat(BREAKPOINTS[breakpoint], "px)");
  var mediaQueryResult = Object(_use_media_query__WEBPACK_IMPORTED_MODULE_1__[/* default */ "a"])(mediaQuery);

  if (simulatedWidth) {
    return OPERATOR_EVALUATORS[operator](BREAKPOINTS[breakpoint], simulatedWidth);
  }

  return mediaQueryResult;
};

useViewportMatch.__experimentalWidthProvider = ViewportMatchWidthContext.Provider;
/* harmony default export */ __webpack_exports__["a"] = (useViewportMatch);
//# sourceMappingURL=index.js.map

/***/ }),

/***/ 269:
/***/ (function(module, exports) {

function _arrayLikeToArray(arr, len) {
  if (len == null || len > arr.length) len = arr.length;

  for (var i = 0, arr2 = new Array(len); i < len; i++) {
    arr2[i] = arr[i];
  }

  return arr2;
}

module.exports = _arrayLikeToArray;

/***/ }),

/***/ 27:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "a", function() { return _objectSpread; });
/* harmony import */ var _defineProperty__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(13);

function _objectSpread(target) {
  for (var i = 1; i < arguments.length; i++) {
    var source = arguments[i] != null ? Object(arguments[i]) : {};
    var ownKeys = Object.keys(source);

    if (typeof Object.getOwnPropertySymbols === 'function') {
      ownKeys = ownKeys.concat(Object.getOwnPropertySymbols(source).filter(function (sym) {
        return Object.getOwnPropertyDescriptor(source, sym).enumerable;
      }));
    }

    ownKeys.forEach(function (key) {
      Object(_defineProperty__WEBPACK_IMPORTED_MODULE_0__[/* default */ "a"])(target, key, source[key]);
    });
  }

  return target;
}

/***/ }),

/***/ 29:
/***/ (function(module, exports) {

function _getPrototypeOf(o) {
  module.exports = _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf : function _getPrototypeOf(o) {
    return o.__proto__ || Object.getPrototypeOf(o);
  };
  return _getPrototypeOf(o);
}

module.exports = _getPrototypeOf;

/***/ }),

/***/ 3:
/***/ (function(module, exports) {

(function() { module.exports = this["wp"]["i18n"]; }());

/***/ }),

/***/ 30:
/***/ (function(module, exports) {

(function() { module.exports = this["wp"]["url"]; }());

/***/ }),

/***/ 32:
/***/ (function(module, exports, __webpack_require__) {

var arrayWithoutHoles = __webpack_require__(443);

var iterableToArray = __webpack_require__(444);

var unsupportedIterableToArray = __webpack_require__(425);

var nonIterableSpread = __webpack_require__(445);

function _toConsumableArray(arr) {
  return arrayWithoutHoles(arr) || iterableToArray(arr) || unsupportedIterableToArray(arr) || nonIterableSpread();
}

module.exports = _toConsumableArray;

/***/ }),

/***/ 35:
/***/ (function(module, exports, __webpack_require__) {

"use strict";


Object.defineProperty(exports, "__esModule", {
	value: true
});

var _typeof = typeof Symbol === "function" && typeof Symbol.iterator === "symbol" ? function (obj) { return typeof obj; } : function (obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; /**
                                                                                                                                                                                                                                                                               * External Dependencies
                                                                                                                                                                                                                                                                               */


/**
 * Internal Dependencies
 */


var _react = __webpack_require__(14);

var _react2 = _interopRequireDefault(_react);

var _reactAddonsCreateFragment = __webpack_require__(142);

var _reactAddonsCreateFragment2 = _interopRequireDefault(_reactAddonsCreateFragment);

var _tokenize = __webpack_require__(145);

var _tokenize2 = _interopRequireDefault(_tokenize);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

var currentMixedString = void 0;

function getCloseIndex(openIndex, tokens) {
	var openToken = tokens[openIndex],
	    nestLevel = 0,
	    token,
	    i;
	for (i = openIndex + 1; i < tokens.length; i++) {
		token = tokens[i];
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
	}
	// if we get this far, there was no matching close token
	throw new Error('Missing closing component token `' + openToken.value + '`');
}

function buildChildren(tokens, components) {
	var children = [],
	    childrenObject = {},
	    openComponent,
	    clonedOpenComponent,
	    openIndex,
	    closeIndex,
	    token,
	    i,
	    grandChildTokens,
	    grandChildren,
	    siblingTokens,
	    siblings;

	for (i = 0; i < tokens.length; i++) {
		token = tokens[i];
		if (token.type === 'string') {
			children.push(token.value);
			continue;
		}
		// component node should at least be set
		if (!components.hasOwnProperty(token.value) || typeof components[token.value] === 'undefined') {
			throw new Error('Invalid interpolation, missing component node: `' + token.value + '`');
		}
		// should be either ReactElement or null (both type "object"), all other types deprecated
		if (_typeof(components[token.value]) !== 'object') {
			throw new Error('Invalid interpolation, component node must be a ReactElement or null: `' + token.value + '`', '\n> ' + currentMixedString);
		}
		// we should never see a componentClose token in this loop
		if (token.type === 'componentClose') {
			throw new Error('Missing opening component token: `' + token.value + '`');
		}
		if (token.type === 'componentOpen') {
			openComponent = components[token.value];
			openIndex = i;
			break;
		}
		// componentSelfClosing token
		children.push(components[token.value]);
		continue;
	}

	if (openComponent) {
		closeIndex = getCloseIndex(openIndex, tokens);
		grandChildTokens = tokens.slice(openIndex + 1, closeIndex);
		grandChildren = buildChildren(grandChildTokens, components);
		clonedOpenComponent = _react2.default.cloneElement(openComponent, {}, grandChildren);
		children.push(clonedOpenComponent);

		if (closeIndex < tokens.length - 1) {
			siblingTokens = tokens.slice(closeIndex + 1);
			siblings = buildChildren(siblingTokens, components);
			children = children.concat(siblings);
		}
	}

	if (children.length === 1) {
		return children[0];
	}

	children.forEach(function (child, index) {
		if (child) {
			childrenObject['interpolation-child-' + index] = child;
		}
	});

	return (0, _reactAddonsCreateFragment2.default)(childrenObject);
}

function interpolate(options) {
	var mixedString = options.mixedString,
	    components = options.components,
	    throwErrors = options.throwErrors;


	currentMixedString = mixedString;

	if (!components) {
		return mixedString;
	}

	if ((typeof components === 'undefined' ? 'undefined' : _typeof(components)) !== 'object') {
		if (throwErrors) {
			throw new Error('Interpolation Error: unable to process `' + mixedString + '` because components is not an object');
		}

		return mixedString;
	}

	var tokens = (0, _tokenize2.default)(mixedString);

	try {
		return buildChildren(tokens, components);
	} catch (error) {
		if (throwErrors) {
			throw new Error('Interpolation Error: unable to process `' + mixedString + '` because of error `' + error.message + '`');
		}

		return mixedString;
	}
};

exports.default = interpolate;
//# sourceMappingURL=index.js.map

/***/ }),

/***/ 37:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "a", function() { return _arrayLikeToArray; });
function _arrayLikeToArray(arr, len) {
  if (len == null || len > arr.length) len = arr.length;

  for (var i = 0, arr2 = new Array(len); i < len; i++) {
    arr2[i] = arr[i];
  }

  return arr2;
}

/***/ }),

/***/ 4:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "a", function() { return _getPrototypeOf; });
function _getPrototypeOf(o) {
  _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf : function _getPrototypeOf(o) {
    return o.__proto__ || Object.getPrototypeOf(o);
  };
  return _getPrototypeOf(o);
}

/***/ }),

/***/ 40:
/***/ (function(module, exports) {

function _defineProperties(target, props) {
  for (var i = 0; i < props.length; i++) {
    var descriptor = props[i];
    descriptor.enumerable = descriptor.enumerable || false;
    descriptor.configurable = true;
    if ("value" in descriptor) descriptor.writable = true;
    Object.defineProperty(target, descriptor.key, descriptor);
  }
}

function _createClass(Constructor, protoProps, staticProps) {
  if (protoProps) _defineProperties(Constructor.prototype, protoProps);
  if (staticProps) _defineProperties(Constructor, staticProps);
  return Constructor;
}

module.exports = _createClass;

/***/ }),

/***/ 41:
/***/ (function(module, exports) {

function _classCallCheck(instance, Constructor) {
  if (!(instance instanceof Constructor)) {
    throw new TypeError("Cannot call a class as a function");
  }
}

module.exports = _classCallCheck;

/***/ }),

/***/ 412:
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var _extends = Object.assign || function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; };

var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

function _objectWithoutProperties(obj, keys) { var target = {}; for (var i in obj) { if (keys.indexOf(i) >= 0) continue; if (!Object.prototype.hasOwnProperty.call(obj, i)) continue; target[i] = obj[i]; } return target; }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _possibleConstructorReturn(self, call) { if (!self) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return call && (typeof call === "object" || typeof call === "function") ? call : self; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function, not " + typeof superClass); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, enumerable: false, writable: true, configurable: true } }); if (superClass) Object.setPrototypeOf ? Object.setPrototypeOf(subClass, superClass) : subClass.__proto__ = superClass; }

var hoistNonReactStatic = __webpack_require__(437);
var React = __webpack_require__(14);
var ReactDOM = __webpack_require__(87);

module.exports = function enhanceWithClickOutside(WrappedComponent) {
  var componentName = WrappedComponent.displayName || WrappedComponent.name;

  var EnhancedComponent = function (_React$Component) {
    _inherits(EnhancedComponent, _React$Component);

    function EnhancedComponent(props) {
      _classCallCheck(this, EnhancedComponent);

      var _this = _possibleConstructorReturn(this, (EnhancedComponent.__proto__ || Object.getPrototypeOf(EnhancedComponent)).call(this, props));

      _this.handleClickOutside = _this.handleClickOutside.bind(_this);
      return _this;
    }

    _createClass(EnhancedComponent, [{
      key: 'componentDidMount',
      value: function componentDidMount() {
        document.addEventListener('click', this.handleClickOutside, true);
      }
    }, {
      key: 'componentWillUnmount',
      value: function componentWillUnmount() {
        document.removeEventListener('click', this.handleClickOutside, true);
      }
    }, {
      key: 'handleClickOutside',
      value: function handleClickOutside(e) {
        var domNode = this.__domNode;
        if ((!domNode || !domNode.contains(e.target)) && this.__wrappedInstance && typeof this.__wrappedInstance.handleClickOutside === 'function') {
          this.__wrappedInstance.handleClickOutside(e);
        }
      }
    }, {
      key: 'render',
      value: function render() {
        var _this2 = this;

        var _props = this.props,
            wrappedRef = _props.wrappedRef,
            rest = _objectWithoutProperties(_props, ['wrappedRef']);

        return React.createElement(WrappedComponent, _extends({}, rest, {
          ref: function ref(c) {
            _this2.__wrappedInstance = c;
            _this2.__domNode = ReactDOM.findDOMNode(c);
            wrappedRef && wrappedRef(c);
          }
        }));
      }
    }]);

    return EnhancedComponent;
  }(React.Component);

  EnhancedComponent.displayName = 'clickOutside(' + componentName + ')';

  return hoistNonReactStatic(EnhancedComponent, WrappedComponent);
};

/***/ }),

/***/ 413:
/***/ (function(module, exports, __webpack_require__) {

"use strict";
var _extends=Object.assign||function(a){for(var c,b=1;b<arguments.length;b++)for(var d in c=arguments[b],c)Object.prototype.hasOwnProperty.call(c,d)&&(a[d]=c[d]);return a};Object.defineProperty(exports,'__esModule',{value:!0});exports.default=function(a){var b=a.size,c=b===void 0?24:b,d=a.onClick,e=a.icon,f=a.className,g=_objectWithoutProperties(a,['size','onClick','icon','className']),j=['gridicon','gridicons-pages',f,!1,!1,!1].filter(Boolean).join(' ');return _react2.default.createElement('svg',_extends({className:j,height:c,width:c,onClick:d},g,{xmlns:'http://www.w3.org/2000/svg',viewBox:'0 0 24 24'}),_react2.default.createElement('g',null,_react2.default.createElement('path',{d:'M16 8H8V6h8v2zm0 2H8v2h8v-2zm4-6v12l-6 6H6c-1.105 0-2-.895-2-2V4c0-1.105.895-2 2-2h12c1.105 0 2 .895 2 2zm-2 10V4H6v16h6v-4c0-1.105.895-2 2-2h4z'})))};var _react=__webpack_require__(14),_react2=_interopRequireDefault(_react);function _interopRequireDefault(a){return a&&a.__esModule?a:{default:a}}function _objectWithoutProperties(a,b){var d={};for(var c in a)0<=b.indexOf(c)||Object.prototype.hasOwnProperty.call(a,c)&&(d[c]=a[c]);return d}module.exports=exports['default'];


/***/ }),

/***/ 414:
/***/ (function(module, exports, __webpack_require__) {

"use strict";
var _extends=Object.assign||function(a){for(var c,b=1;b<arguments.length;b++)for(var d in c=arguments[b],c)Object.prototype.hasOwnProperty.call(c,d)&&(a[d]=c[d]);return a};Object.defineProperty(exports,'__esModule',{value:!0});exports.default=function(a){var b=a.size,c=b===void 0?24:b,d=a.onClick,e=a.icon,f=a.className,g=_objectWithoutProperties(a,['size','onClick','icon','className']),j=['gridicon','gridicons-cross-small',f,!1,!1,!1].filter(Boolean).join(' ');return _react2.default.createElement('svg',_extends({className:j,height:c,width:c,onClick:d},g,{xmlns:'http://www.w3.org/2000/svg',viewBox:'0 0 24 24'}),_react2.default.createElement('g',null,_react2.default.createElement('path',{d:'M17.705 7.705l-1.41-1.41L12 10.59 7.705 6.295l-1.41 1.41L10.59 12l-4.295 4.295 1.41 1.41L12 13.41l4.295 4.295 1.41-1.41L13.41 12l4.295-4.295z'})))};var _react=__webpack_require__(14),_react2=_interopRequireDefault(_react);function _interopRequireDefault(a){return a&&a.__esModule?a:{default:a}}function _objectWithoutProperties(a,b){var d={};for(var c in a)0<=b.indexOf(c)||Object.prototype.hasOwnProperty.call(a,c)&&(d[c]=a[c]);return d}module.exports=exports['default'];


/***/ }),

/***/ 415:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(2);
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(10);
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(classnames__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(3);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var ___WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(88);


/**
 * External dependencies
 */


/**
 * WordPress dependencies
 */



/**
 * Internal dependencies
 */


var NOTICE_TIMEOUT = 10000;

function Snackbar(_ref, ref) {
  var className = _ref.className,
      children = _ref.children,
      _ref$actions = _ref.actions,
      actions = _ref$actions === void 0 ? [] : _ref$actions,
      _ref$onRemove = _ref.onRemove,
      onRemove = _ref$onRemove === void 0 ? lodash__WEBPACK_IMPORTED_MODULE_1__["noop"] : _ref$onRemove;
  Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["useEffect"])(function () {
    var timeoutHandle = setTimeout(function () {
      onRemove();
    }, NOTICE_TIMEOUT);
    return function () {
      return clearTimeout(timeoutHandle);
    };
  }, []);
  var classes = classnames__WEBPACK_IMPORTED_MODULE_2___default()(className, 'components-snackbar');

  if (actions && actions.length > 1) {
    // we need to inform developers that snackbar only accepts 1 action
    // eslint-disable-next-line no-console
    console.warn('Snackbar can only have 1 action, use Notice if your message require many messages'); // return first element only while keeping it inside an array

    actions = [actions[0]];
  }

  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("div", {
    ref: ref,
    className: classes,
    onClick: onRemove,
    tabIndex: "0",
    role: "button",
    onKeyPress: onRemove,
    label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__["__"])('Dismiss this notice')
  }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("div", {
    className: "components-snackbar__content"
  }, children, actions.map(function (_ref2, index) {
    var label = _ref2.label,
        _onClick = _ref2.onClick,
        url = _ref2.url;
    return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(___WEBPACK_IMPORTED_MODULE_4__[/* default */ "a"], {
      key: index,
      href: url,
      isTertiary: true,
      onClick: function onClick(event) {
        event.stopPropagation();

        if (_onClick) {
          _onClick(event);
        }
      },
      className: "components-snackbar__action"
    }, label);
  })));
}

/* harmony default export */ __webpack_exports__["a"] = (Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["forwardRef"])(Snackbar));
//# sourceMappingURL=index.js.map

/***/ }),

/***/ 416:
/***/ (function(module, exports, __webpack_require__) {

"use strict";
/* WEBPACK VAR INJECTION */(function(global) {// @flow


var key = '__global_unique_id__';

module.exports = function() {
  return global[key] = (global[key] || 0) + 1;
};

/* WEBPACK VAR INJECTION */}.call(this, __webpack_require__(64)))

/***/ }),

/***/ 417:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(41);
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(40);
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(44);
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(29);
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(42);
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(19);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var _woocommerce_data__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(51);
/* harmony import */ var _woocommerce_data__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_data__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var _style_scss__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(441);
/* harmony import */ var _style_scss__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(_style_scss__WEBPACK_IMPORTED_MODULE_8__);







function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function () { var Super = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_3___default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_3___default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_2___default()(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/**
 * External dependencies
 */


/**
 * WooCommerce dependencies
 */


/**
 * Internal dependencies
 */



var Navigation = /*#__PURE__*/function (_Component) {
  _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_4___default()(Navigation, _Component);

  var _super = _createSuper(Navigation);

  function Navigation() {
    _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0___default()(this, Navigation);

    return _super.apply(this, arguments);
  }

  _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1___default()(Navigation, [{
    key: "renderMenuItem",
    value: function renderMenuItem(item) {
      var _this = this;

      var depth = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : 0;
      var slug = item.slug,
          title = item.title,
          url = item.url;
      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])("li", {
        key: slug,
        className: "woocommerce-navigation__menu-item woocommerce-navigation__menu-item-depth-".concat(depth)
      }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])("a", {
        href: url
      }, title), item.children && item.children.length && Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])("ul", {
        className: "woocommerce-navigation__submenu"
      }, item.children.map(function (childItem) {
        return _this.renderMenuItem(childItem, depth + 1);
      })));
    }
  }, {
    key: "render",
    value: function render() {
      var _this2 = this;

      var items = this.props.items;
      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])("div", {
        className: "woocommerce-navigation"
      }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])("ul", {
        className: "woocommerce-navigation__menu"
      }, items.map(function (item) {
        return _this2.renderMenuItem(item);
      })));
    }
  }]);

  return Navigation;
}(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["Component"]);

/* harmony default export */ __webpack_exports__["a"] = (Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_6__["withSelect"])(function (select) {
  var items = select(_woocommerce_data__WEBPACK_IMPORTED_MODULE_7__["SETTINGS_STORE_NAME"]).getSetting('wc_admin', 'wcNavigation');
  return {
    items: items
  };
})(Navigation));

/***/ }),

/***/ 418:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "a", function() { return symbolObservablePonyfill; });
function symbolObservablePonyfill(root) {
	var result;
	var Symbol = root.Symbol;

	if (typeof Symbol === 'function') {
		if (Symbol.observable) {
			result = Symbol.observable;
		} else {
			result = Symbol('observable');
			Symbol.observable = result;
		}
	} else {
		result = '@@observable';
	}

	return result;
};


/***/ }),

/***/ 42:
/***/ (function(module, exports, __webpack_require__) {

var setPrototypeOf = __webpack_require__(209);

function _inherits(subClass, superClass) {
  if (typeof superClass !== "function" && superClass !== null) {
    throw new TypeError("Super expression must either be null or a function");
  }

  subClass.prototype = Object.create(superClass && superClass.prototype, {
    constructor: {
      value: subClass,
      writable: true,
      configurable: true
    }
  });
  if (superClass) setPrototypeOf(subClass, superClass);
}

module.exports = _inherits;

/***/ }),

/***/ 424:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";

// UNUSED EXPORTS: NavigableMenu

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/esm/extends.js
var esm_extends = __webpack_require__(11);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/esm/objectWithoutProperties.js
var objectWithoutProperties = __webpack_require__(16);

// EXTERNAL MODULE: external {"this":["wp","element"]}
var external_this_wp_element_ = __webpack_require__(0);

// EXTERNAL MODULE: external "lodash"
var external_lodash_ = __webpack_require__(2);

// EXTERNAL MODULE: ./node_modules/@wordpress/keycodes/build-module/index.js + 1 modules
var build_module = __webpack_require__(18);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/esm/classCallCheck.js
var classCallCheck = __webpack_require__(7);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/esm/createClass.js
var createClass = __webpack_require__(6);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/esm/possibleConstructorReturn.js
var possibleConstructorReturn = __webpack_require__(8);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/esm/getPrototypeOf.js
var getPrototypeOf = __webpack_require__(4);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/esm/assertThisInitialized.js
var assertThisInitialized = __webpack_require__(5);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/esm/inherits.js + 1 modules
var inherits = __webpack_require__(9);

// EXTERNAL MODULE: ./node_modules/@wordpress/dom/build-module/index.js + 2 modules
var dom_build_module = __webpack_require__(50);

// CONCATENATED MODULE: ./node_modules/@wordpress/components/build-module/navigable-container/container.js










/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */




function cycleValue(value, total, offset) {
  var nextValue = value + offset;

  if (nextValue < 0) {
    return total + nextValue;
  } else if (nextValue >= total) {
    return nextValue - total;
  }

  return nextValue;
}

var container_NavigableContainer =
/*#__PURE__*/
function (_Component) {
  Object(inherits["a" /* default */])(NavigableContainer, _Component);

  function NavigableContainer() {
    var _this;

    Object(classCallCheck["a" /* default */])(this, NavigableContainer);

    _this = Object(possibleConstructorReturn["a" /* default */])(this, Object(getPrototypeOf["a" /* default */])(NavigableContainer).apply(this, arguments));
    _this.onKeyDown = _this.onKeyDown.bind(Object(assertThisInitialized["a" /* default */])(_this));
    _this.bindContainer = _this.bindContainer.bind(Object(assertThisInitialized["a" /* default */])(_this));
    _this.getFocusableContext = _this.getFocusableContext.bind(Object(assertThisInitialized["a" /* default */])(_this));
    _this.getFocusableIndex = _this.getFocusableIndex.bind(Object(assertThisInitialized["a" /* default */])(_this));
    return _this;
  }

  Object(createClass["a" /* default */])(NavigableContainer, [{
    key: "componentDidMount",
    value: function componentDidMount() {
      // We use DOM event listeners instead of React event listeners
      // because we want to catch events from the underlying DOM tree
      // The React Tree can be different from the DOM tree when using
      // portals. Block Toolbars for instance are rendered in a separate
      // React Trees.
      this.container.addEventListener('keydown', this.onKeyDown);
      this.container.addEventListener('focus', this.onFocus);
    }
  }, {
    key: "componentWillUnmount",
    value: function componentWillUnmount() {
      this.container.removeEventListener('keydown', this.onKeyDown);
      this.container.removeEventListener('focus', this.onFocus);
    }
  }, {
    key: "bindContainer",
    value: function bindContainer(ref) {
      var forwardedRef = this.props.forwardedRef;
      this.container = ref;

      if (Object(external_lodash_["isFunction"])(forwardedRef)) {
        forwardedRef(ref);
      } else if (forwardedRef && 'current' in forwardedRef) {
        forwardedRef.current = ref;
      }
    }
  }, {
    key: "getFocusableContext",
    value: function getFocusableContext(target) {
      var onlyBrowserTabstops = this.props.onlyBrowserTabstops;
      var finder = onlyBrowserTabstops ? dom_build_module["a" /* focus */].tabbable : dom_build_module["a" /* focus */].focusable;
      var focusables = finder.find(this.container);
      var index = this.getFocusableIndex(focusables, target);

      if (index > -1 && target) {
        return {
          index: index,
          target: target,
          focusables: focusables
        };
      }

      return null;
    }
  }, {
    key: "getFocusableIndex",
    value: function getFocusableIndex(focusables, target) {
      var directIndex = focusables.indexOf(target);

      if (directIndex !== -1) {
        return directIndex;
      }
    }
  }, {
    key: "onKeyDown",
    value: function onKeyDown(event) {
      if (this.props.onKeyDown) {
        this.props.onKeyDown(event);
      }

      var getFocusableContext = this.getFocusableContext;
      var _this$props = this.props,
          _this$props$cycle = _this$props.cycle,
          cycle = _this$props$cycle === void 0 ? true : _this$props$cycle,
          eventToOffset = _this$props.eventToOffset,
          _this$props$onNavigat = _this$props.onNavigate,
          onNavigate = _this$props$onNavigat === void 0 ? external_lodash_["noop"] : _this$props$onNavigat,
          stopNavigationEvents = _this$props.stopNavigationEvents;
      var offset = eventToOffset(event); // eventToOffset returns undefined if the event is not handled by the component

      if (offset !== undefined && stopNavigationEvents) {
        // Prevents arrow key handlers bound to the document directly interfering
        event.stopImmediatePropagation(); // When navigating a collection of items, prevent scroll containers
        // from scrolling.

        if (event.target.getAttribute('role') === 'menuitem') {
          event.preventDefault();
        }
      }

      if (!offset) {
        return;
      }

      var context = getFocusableContext(document.activeElement);

      if (!context) {
        return;
      }

      var index = context.index,
          focusables = context.focusables;
      var nextIndex = cycle ? cycleValue(index, focusables.length, offset) : index + offset;

      if (nextIndex >= 0 && nextIndex < focusables.length) {
        focusables[nextIndex].focus();
        onNavigate(nextIndex, focusables[nextIndex]);
      }
    }
  }, {
    key: "render",
    value: function render() {
      var _this$props2 = this.props,
          children = _this$props2.children,
          props = Object(objectWithoutProperties["a" /* default */])(_this$props2, ["children"]);

      return Object(external_this_wp_element_["createElement"])("div", Object(esm_extends["a" /* default */])({
        ref: this.bindContainer
      }, Object(external_lodash_["omit"])(props, ['stopNavigationEvents', 'eventToOffset', 'onNavigate', 'cycle', 'onlyBrowserTabstops', 'forwardedRef'])), children);
    }
  }]);

  return NavigableContainer;
}(external_this_wp_element_["Component"]);

var container_forwardedNavigableContainer = function forwardedNavigableContainer(props, ref) {
  return Object(external_this_wp_element_["createElement"])(container_NavigableContainer, Object(esm_extends["a" /* default */])({}, props, {
    forwardedRef: ref
  }));
};

container_forwardedNavigableContainer.displayName = 'NavigableContainer';
/* harmony default export */ var container = (Object(external_this_wp_element_["forwardRef"])(container_forwardedNavigableContainer));
//# sourceMappingURL=container.js.map
// CONCATENATED MODULE: ./node_modules/@wordpress/components/build-module/navigable-container/menu.js




/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */



/**
 * Internal dependencies
 */


function NavigableMenu(_ref, ref) {
  var _ref$role = _ref.role,
      role = _ref$role === void 0 ? 'menu' : _ref$role,
      _ref$orientation = _ref.orientation,
      orientation = _ref$orientation === void 0 ? 'vertical' : _ref$orientation,
      rest = Object(objectWithoutProperties["a" /* default */])(_ref, ["role", "orientation"]);

  var eventToOffset = function eventToOffset(evt) {
    var keyCode = evt.keyCode;
    var next = [build_module["b" /* DOWN */]];
    var previous = [build_module["i" /* UP */]];

    if (orientation === 'horizontal') {
      next = [build_module["f" /* RIGHT */]];
      previous = [build_module["e" /* LEFT */]];
    }

    if (orientation === 'both') {
      next = [build_module["f" /* RIGHT */], build_module["b" /* DOWN */]];
      previous = [build_module["e" /* LEFT */], build_module["i" /* UP */]];
    }

    if (Object(external_lodash_["includes"])(next, keyCode)) {
      return 1;
    } else if (Object(external_lodash_["includes"])(previous, keyCode)) {
      return -1;
    }
  };

  return Object(external_this_wp_element_["createElement"])(container, Object(esm_extends["a" /* default */])({
    ref: ref,
    stopNavigationEvents: true,
    onlyBrowserTabstops: false,
    role: role,
    "aria-orientation": role === 'presentation' ? null : orientation,
    eventToOffset: eventToOffset
  }, rest));
}
/* harmony default export */ var menu = __webpack_exports__["a"] = (Object(external_this_wp_element_["forwardRef"])(NavigableMenu));
//# sourceMappingURL=menu.js.map

/***/ }),

/***/ 425:
/***/ (function(module, exports, __webpack_require__) {

var arrayLikeToArray = __webpack_require__(269);

function _unsupportedIterableToArray(o, minLen) {
  if (!o) return;
  if (typeof o === "string") return arrayLikeToArray(o, minLen);
  var n = Object.prototype.toString.call(o).slice(8, -1);
  if (n === "Object" && o.constructor) n = o.constructor.name;
  if (n === "Map" || n === "Set") return Array.from(n);
  if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return arrayLikeToArray(o, minLen);
}

module.exports = _unsupportedIterableToArray;

/***/ }),

/***/ 43:
/***/ (function(module, exports) {

function _typeof(obj) {
  "@babel/helpers - typeof";

  if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") {
    module.exports = _typeof = function _typeof(obj) {
      return typeof obj;
    };
  } else {
    module.exports = _typeof = function _typeof(obj) {
      return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj;
    };
  }

  return _typeof(obj);
}

module.exports = _typeof;

/***/ }),

/***/ 430:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* WEBPACK VAR INJECTION */(function(global) {/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_notices__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(706);
/* harmony import */ var _stylesheets_index_scss__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(433);
/* harmony import */ var _stylesheets_index_scss__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_stylesheets_index_scss__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _layout__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(202);
/* harmony import */ var _navigation__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(417);
/* harmony import */ var wc_api_wp_data_store__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(704);
/* harmony import */ var _woocommerce_data__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(51);
/* harmony import */ var _woocommerce_data__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_data__WEBPACK_IMPORTED_MODULE_6__);


/**
 * External dependencies
 */


/**
 * Internal dependencies
 */





 // Modify webpack pubilcPath at runtime based on location of WordPress Plugin.
// eslint-disable-next-line no-undef,camelcase

__webpack_require__.p = global.wcAdminAssets.path;
var appRoot = document.getElementById('root');
var navigationRoot = document.getElementById('woocommerce-embedded-navigation');
var settingsGroup = 'wc_admin';

if (navigationRoot) {
  var HydratedNavigation = Object(_woocommerce_data__WEBPACK_IMPORTED_MODULE_6__["withSettingsHydration"])(settingsGroup, window.wcSettings)(_navigation__WEBPACK_IMPORTED_MODULE_4__[/* default */ "a"]);
  Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["render"])(Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(HydratedNavigation, null), navigationRoot); // Collapse the WP Menu.

  var adminMenu = document.getElementById('adminmenumain');
  adminMenu.classList.add('folded');
}

if (appRoot) {
  var HydratedPageLayout = Object(_woocommerce_data__WEBPACK_IMPORTED_MODULE_6__["withSettingsHydration"])(settingsGroup, window.wcSettings)(_layout__WEBPACK_IMPORTED_MODULE_3__[/* PageLayout */ "b"]);
  Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["render"])(Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(HydratedPageLayout, null), appRoot);
} else {
  var embeddedRoot = document.getElementById('woocommerce-embedded-root');
  var HydratedEmbedLayout = Object(_woocommerce_data__WEBPACK_IMPORTED_MODULE_6__["withSettingsHydration"])(settingsGroup, window.wcSettings)(_layout__WEBPACK_IMPORTED_MODULE_3__[/* EmbedLayout */ "a"]); // Render the header.

  Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["render"])(Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(HydratedEmbedLayout, null), embeddedRoot);
  embeddedRoot.classList.remove('is-embed-loading'); // Render notices just above the WP content div.

  var wpBody = document.getElementById('wpbody-content');
  var wrap = wpBody.querySelector('.wrap.woocommerce') || wpBody.querySelector('[class="wrap"]');
  var noticeContainer = document.createElement('div');
  Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["render"])(Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("div", {
    className: "woocommerce-layout"
  }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_layout__WEBPACK_IMPORTED_MODULE_3__[/* PrimaryLayout */ "c"], null)), wpBody.insertBefore(noticeContainer, wrap));
}
/* WEBPACK VAR INJECTION */}.call(this, __webpack_require__(64)))

/***/ }),

/***/ 432:
/***/ (function(module, exports, __webpack_require__) {

/**
 * Copyright (c) 2014-present, Facebook, Inc.
 *
 * This source code is licensed under the MIT license found in the
 * LICENSE file in the root directory of this source tree.
 */

var runtime = (function (exports) {
  "use strict";

  var Op = Object.prototype;
  var hasOwn = Op.hasOwnProperty;
  var undefined; // More compressible than void 0.
  var $Symbol = typeof Symbol === "function" ? Symbol : {};
  var iteratorSymbol = $Symbol.iterator || "@@iterator";
  var asyncIteratorSymbol = $Symbol.asyncIterator || "@@asyncIterator";
  var toStringTagSymbol = $Symbol.toStringTag || "@@toStringTag";

  function wrap(innerFn, outerFn, self, tryLocsList) {
    // If outerFn provided and outerFn.prototype is a Generator, then outerFn.prototype instanceof Generator.
    var protoGenerator = outerFn && outerFn.prototype instanceof Generator ? outerFn : Generator;
    var generator = Object.create(protoGenerator.prototype);
    var context = new Context(tryLocsList || []);

    // The ._invoke method unifies the implementations of the .next,
    // .throw, and .return methods.
    generator._invoke = makeInvokeMethod(innerFn, self, context);

    return generator;
  }
  exports.wrap = wrap;

  // Try/catch helper to minimize deoptimizations. Returns a completion
  // record like context.tryEntries[i].completion. This interface could
  // have been (and was previously) designed to take a closure to be
  // invoked without arguments, but in all the cases we care about we
  // already have an existing method we want to call, so there's no need
  // to create a new function object. We can even get away with assuming
  // the method takes exactly one argument, since that happens to be true
  // in every case, so we don't have to touch the arguments object. The
  // only additional allocation required is the completion record, which
  // has a stable shape and so hopefully should be cheap to allocate.
  function tryCatch(fn, obj, arg) {
    try {
      return { type: "normal", arg: fn.call(obj, arg) };
    } catch (err) {
      return { type: "throw", arg: err };
    }
  }

  var GenStateSuspendedStart = "suspendedStart";
  var GenStateSuspendedYield = "suspendedYield";
  var GenStateExecuting = "executing";
  var GenStateCompleted = "completed";

  // Returning this object from the innerFn has the same effect as
  // breaking out of the dispatch switch statement.
  var ContinueSentinel = {};

  // Dummy constructor functions that we use as the .constructor and
  // .constructor.prototype properties for functions that return Generator
  // objects. For full spec compliance, you may wish to configure your
  // minifier not to mangle the names of these two functions.
  function Generator() {}
  function GeneratorFunction() {}
  function GeneratorFunctionPrototype() {}

  // This is a polyfill for %IteratorPrototype% for environments that
  // don't natively support it.
  var IteratorPrototype = {};
  IteratorPrototype[iteratorSymbol] = function () {
    return this;
  };

  var getProto = Object.getPrototypeOf;
  var NativeIteratorPrototype = getProto && getProto(getProto(values([])));
  if (NativeIteratorPrototype &&
      NativeIteratorPrototype !== Op &&
      hasOwn.call(NativeIteratorPrototype, iteratorSymbol)) {
    // This environment has a native %IteratorPrototype%; use it instead
    // of the polyfill.
    IteratorPrototype = NativeIteratorPrototype;
  }

  var Gp = GeneratorFunctionPrototype.prototype =
    Generator.prototype = Object.create(IteratorPrototype);
  GeneratorFunction.prototype = Gp.constructor = GeneratorFunctionPrototype;
  GeneratorFunctionPrototype.constructor = GeneratorFunction;
  GeneratorFunctionPrototype[toStringTagSymbol] =
    GeneratorFunction.displayName = "GeneratorFunction";

  // Helper for defining the .next, .throw, and .return methods of the
  // Iterator interface in terms of a single ._invoke method.
  function defineIteratorMethods(prototype) {
    ["next", "throw", "return"].forEach(function(method) {
      prototype[method] = function(arg) {
        return this._invoke(method, arg);
      };
    });
  }

  exports.isGeneratorFunction = function(genFun) {
    var ctor = typeof genFun === "function" && genFun.constructor;
    return ctor
      ? ctor === GeneratorFunction ||
        // For the native GeneratorFunction constructor, the best we can
        // do is to check its .name property.
        (ctor.displayName || ctor.name) === "GeneratorFunction"
      : false;
  };

  exports.mark = function(genFun) {
    if (Object.setPrototypeOf) {
      Object.setPrototypeOf(genFun, GeneratorFunctionPrototype);
    } else {
      genFun.__proto__ = GeneratorFunctionPrototype;
      if (!(toStringTagSymbol in genFun)) {
        genFun[toStringTagSymbol] = "GeneratorFunction";
      }
    }
    genFun.prototype = Object.create(Gp);
    return genFun;
  };

  // Within the body of any async function, `await x` is transformed to
  // `yield regeneratorRuntime.awrap(x)`, so that the runtime can test
  // `hasOwn.call(value, "__await")` to determine if the yielded value is
  // meant to be awaited.
  exports.awrap = function(arg) {
    return { __await: arg };
  };

  function AsyncIterator(generator, PromiseImpl) {
    function invoke(method, arg, resolve, reject) {
      var record = tryCatch(generator[method], generator, arg);
      if (record.type === "throw") {
        reject(record.arg);
      } else {
        var result = record.arg;
        var value = result.value;
        if (value &&
            typeof value === "object" &&
            hasOwn.call(value, "__await")) {
          return PromiseImpl.resolve(value.__await).then(function(value) {
            invoke("next", value, resolve, reject);
          }, function(err) {
            invoke("throw", err, resolve, reject);
          });
        }

        return PromiseImpl.resolve(value).then(function(unwrapped) {
          // When a yielded Promise is resolved, its final value becomes
          // the .value of the Promise<{value,done}> result for the
          // current iteration.
          result.value = unwrapped;
          resolve(result);
        }, function(error) {
          // If a rejected Promise was yielded, throw the rejection back
          // into the async generator function so it can be handled there.
          return invoke("throw", error, resolve, reject);
        });
      }
    }

    var previousPromise;

    function enqueue(method, arg) {
      function callInvokeWithMethodAndArg() {
        return new PromiseImpl(function(resolve, reject) {
          invoke(method, arg, resolve, reject);
        });
      }

      return previousPromise =
        // If enqueue has been called before, then we want to wait until
        // all previous Promises have been resolved before calling invoke,
        // so that results are always delivered in the correct order. If
        // enqueue has not been called before, then it is important to
        // call invoke immediately, without waiting on a callback to fire,
        // so that the async generator function has the opportunity to do
        // any necessary setup in a predictable way. This predictability
        // is why the Promise constructor synchronously invokes its
        // executor callback, and why async functions synchronously
        // execute code before the first await. Since we implement simple
        // async functions in terms of async generators, it is especially
        // important to get this right, even though it requires care.
        previousPromise ? previousPromise.then(
          callInvokeWithMethodAndArg,
          // Avoid propagating failures to Promises returned by later
          // invocations of the iterator.
          callInvokeWithMethodAndArg
        ) : callInvokeWithMethodAndArg();
    }

    // Define the unified helper method that is used to implement .next,
    // .throw, and .return (see defineIteratorMethods).
    this._invoke = enqueue;
  }

  defineIteratorMethods(AsyncIterator.prototype);
  AsyncIterator.prototype[asyncIteratorSymbol] = function () {
    return this;
  };
  exports.AsyncIterator = AsyncIterator;

  // Note that simple async functions are implemented on top of
  // AsyncIterator objects; they just return a Promise for the value of
  // the final result produced by the iterator.
  exports.async = function(innerFn, outerFn, self, tryLocsList, PromiseImpl) {
    if (PromiseImpl === void 0) PromiseImpl = Promise;

    var iter = new AsyncIterator(
      wrap(innerFn, outerFn, self, tryLocsList),
      PromiseImpl
    );

    return exports.isGeneratorFunction(outerFn)
      ? iter // If outerFn is a generator, return the full iterator.
      : iter.next().then(function(result) {
          return result.done ? result.value : iter.next();
        });
  };

  function makeInvokeMethod(innerFn, self, context) {
    var state = GenStateSuspendedStart;

    return function invoke(method, arg) {
      if (state === GenStateExecuting) {
        throw new Error("Generator is already running");
      }

      if (state === GenStateCompleted) {
        if (method === "throw") {
          throw arg;
        }

        // Be forgiving, per 25.3.3.3.3 of the spec:
        // https://people.mozilla.org/~jorendorff/es6-draft.html#sec-generatorresume
        return doneResult();
      }

      context.method = method;
      context.arg = arg;

      while (true) {
        var delegate = context.delegate;
        if (delegate) {
          var delegateResult = maybeInvokeDelegate(delegate, context);
          if (delegateResult) {
            if (delegateResult === ContinueSentinel) continue;
            return delegateResult;
          }
        }

        if (context.method === "next") {
          // Setting context._sent for legacy support of Babel's
          // function.sent implementation.
          context.sent = context._sent = context.arg;

        } else if (context.method === "throw") {
          if (state === GenStateSuspendedStart) {
            state = GenStateCompleted;
            throw context.arg;
          }

          context.dispatchException(context.arg);

        } else if (context.method === "return") {
          context.abrupt("return", context.arg);
        }

        state = GenStateExecuting;

        var record = tryCatch(innerFn, self, context);
        if (record.type === "normal") {
          // If an exception is thrown from innerFn, we leave state ===
          // GenStateExecuting and loop back for another invocation.
          state = context.done
            ? GenStateCompleted
            : GenStateSuspendedYield;

          if (record.arg === ContinueSentinel) {
            continue;
          }

          return {
            value: record.arg,
            done: context.done
          };

        } else if (record.type === "throw") {
          state = GenStateCompleted;
          // Dispatch the exception by looping back around to the
          // context.dispatchException(context.arg) call above.
          context.method = "throw";
          context.arg = record.arg;
        }
      }
    };
  }

  // Call delegate.iterator[context.method](context.arg) and handle the
  // result, either by returning a { value, done } result from the
  // delegate iterator, or by modifying context.method and context.arg,
  // setting context.delegate to null, and returning the ContinueSentinel.
  function maybeInvokeDelegate(delegate, context) {
    var method = delegate.iterator[context.method];
    if (method === undefined) {
      // A .throw or .return when the delegate iterator has no .throw
      // method always terminates the yield* loop.
      context.delegate = null;

      if (context.method === "throw") {
        // Note: ["return"] must be used for ES3 parsing compatibility.
        if (delegate.iterator["return"]) {
          // If the delegate iterator has a return method, give it a
          // chance to clean up.
          context.method = "return";
          context.arg = undefined;
          maybeInvokeDelegate(delegate, context);

          if (context.method === "throw") {
            // If maybeInvokeDelegate(context) changed context.method from
            // "return" to "throw", let that override the TypeError below.
            return ContinueSentinel;
          }
        }

        context.method = "throw";
        context.arg = new TypeError(
          "The iterator does not provide a 'throw' method");
      }

      return ContinueSentinel;
    }

    var record = tryCatch(method, delegate.iterator, context.arg);

    if (record.type === "throw") {
      context.method = "throw";
      context.arg = record.arg;
      context.delegate = null;
      return ContinueSentinel;
    }

    var info = record.arg;

    if (! info) {
      context.method = "throw";
      context.arg = new TypeError("iterator result is not an object");
      context.delegate = null;
      return ContinueSentinel;
    }

    if (info.done) {
      // Assign the result of the finished delegate to the temporary
      // variable specified by delegate.resultName (see delegateYield).
      context[delegate.resultName] = info.value;

      // Resume execution at the desired location (see delegateYield).
      context.next = delegate.nextLoc;

      // If context.method was "throw" but the delegate handled the
      // exception, let the outer generator proceed normally. If
      // context.method was "next", forget context.arg since it has been
      // "consumed" by the delegate iterator. If context.method was
      // "return", allow the original .return call to continue in the
      // outer generator.
      if (context.method !== "return") {
        context.method = "next";
        context.arg = undefined;
      }

    } else {
      // Re-yield the result returned by the delegate method.
      return info;
    }

    // The delegate iterator is finished, so forget it and continue with
    // the outer generator.
    context.delegate = null;
    return ContinueSentinel;
  }

  // Define Generator.prototype.{next,throw,return} in terms of the
  // unified ._invoke helper method.
  defineIteratorMethods(Gp);

  Gp[toStringTagSymbol] = "Generator";

  // A Generator should always return itself as the iterator object when the
  // @@iterator function is called on it. Some browsers' implementations of the
  // iterator prototype chain incorrectly implement this, causing the Generator
  // object to not be returned from this call. This ensures that doesn't happen.
  // See https://github.com/facebook/regenerator/issues/274 for more details.
  Gp[iteratorSymbol] = function() {
    return this;
  };

  Gp.toString = function() {
    return "[object Generator]";
  };

  function pushTryEntry(locs) {
    var entry = { tryLoc: locs[0] };

    if (1 in locs) {
      entry.catchLoc = locs[1];
    }

    if (2 in locs) {
      entry.finallyLoc = locs[2];
      entry.afterLoc = locs[3];
    }

    this.tryEntries.push(entry);
  }

  function resetTryEntry(entry) {
    var record = entry.completion || {};
    record.type = "normal";
    delete record.arg;
    entry.completion = record;
  }

  function Context(tryLocsList) {
    // The root entry object (effectively a try statement without a catch
    // or a finally block) gives us a place to store values thrown from
    // locations where there is no enclosing try statement.
    this.tryEntries = [{ tryLoc: "root" }];
    tryLocsList.forEach(pushTryEntry, this);
    this.reset(true);
  }

  exports.keys = function(object) {
    var keys = [];
    for (var key in object) {
      keys.push(key);
    }
    keys.reverse();

    // Rather than returning an object with a next method, we keep
    // things simple and return the next function itself.
    return function next() {
      while (keys.length) {
        var key = keys.pop();
        if (key in object) {
          next.value = key;
          next.done = false;
          return next;
        }
      }

      // To avoid creating an additional object, we just hang the .value
      // and .done properties off the next function object itself. This
      // also ensures that the minifier will not anonymize the function.
      next.done = true;
      return next;
    };
  };

  function values(iterable) {
    if (iterable) {
      var iteratorMethod = iterable[iteratorSymbol];
      if (iteratorMethod) {
        return iteratorMethod.call(iterable);
      }

      if (typeof iterable.next === "function") {
        return iterable;
      }

      if (!isNaN(iterable.length)) {
        var i = -1, next = function next() {
          while (++i < iterable.length) {
            if (hasOwn.call(iterable, i)) {
              next.value = iterable[i];
              next.done = false;
              return next;
            }
          }

          next.value = undefined;
          next.done = true;

          return next;
        };

        return next.next = next;
      }
    }

    // Return an iterator with no values.
    return { next: doneResult };
  }
  exports.values = values;

  function doneResult() {
    return { value: undefined, done: true };
  }

  Context.prototype = {
    constructor: Context,

    reset: function(skipTempReset) {
      this.prev = 0;
      this.next = 0;
      // Resetting context._sent for legacy support of Babel's
      // function.sent implementation.
      this.sent = this._sent = undefined;
      this.done = false;
      this.delegate = null;

      this.method = "next";
      this.arg = undefined;

      this.tryEntries.forEach(resetTryEntry);

      if (!skipTempReset) {
        for (var name in this) {
          // Not sure about the optimal order of these conditions:
          if (name.charAt(0) === "t" &&
              hasOwn.call(this, name) &&
              !isNaN(+name.slice(1))) {
            this[name] = undefined;
          }
        }
      }
    },

    stop: function() {
      this.done = true;

      var rootEntry = this.tryEntries[0];
      var rootRecord = rootEntry.completion;
      if (rootRecord.type === "throw") {
        throw rootRecord.arg;
      }

      return this.rval;
    },

    dispatchException: function(exception) {
      if (this.done) {
        throw exception;
      }

      var context = this;
      function handle(loc, caught) {
        record.type = "throw";
        record.arg = exception;
        context.next = loc;

        if (caught) {
          // If the dispatched exception was caught by a catch block,
          // then let that catch block handle the exception normally.
          context.method = "next";
          context.arg = undefined;
        }

        return !! caught;
      }

      for (var i = this.tryEntries.length - 1; i >= 0; --i) {
        var entry = this.tryEntries[i];
        var record = entry.completion;

        if (entry.tryLoc === "root") {
          // Exception thrown outside of any try block that could handle
          // it, so set the completion value of the entire function to
          // throw the exception.
          return handle("end");
        }

        if (entry.tryLoc <= this.prev) {
          var hasCatch = hasOwn.call(entry, "catchLoc");
          var hasFinally = hasOwn.call(entry, "finallyLoc");

          if (hasCatch && hasFinally) {
            if (this.prev < entry.catchLoc) {
              return handle(entry.catchLoc, true);
            } else if (this.prev < entry.finallyLoc) {
              return handle(entry.finallyLoc);
            }

          } else if (hasCatch) {
            if (this.prev < entry.catchLoc) {
              return handle(entry.catchLoc, true);
            }

          } else if (hasFinally) {
            if (this.prev < entry.finallyLoc) {
              return handle(entry.finallyLoc);
            }

          } else {
            throw new Error("try statement without catch or finally");
          }
        }
      }
    },

    abrupt: function(type, arg) {
      for (var i = this.tryEntries.length - 1; i >= 0; --i) {
        var entry = this.tryEntries[i];
        if (entry.tryLoc <= this.prev &&
            hasOwn.call(entry, "finallyLoc") &&
            this.prev < entry.finallyLoc) {
          var finallyEntry = entry;
          break;
        }
      }

      if (finallyEntry &&
          (type === "break" ||
           type === "continue") &&
          finallyEntry.tryLoc <= arg &&
          arg <= finallyEntry.finallyLoc) {
        // Ignore the finally entry if control is not jumping to a
        // location outside the try/catch block.
        finallyEntry = null;
      }

      var record = finallyEntry ? finallyEntry.completion : {};
      record.type = type;
      record.arg = arg;

      if (finallyEntry) {
        this.method = "next";
        this.next = finallyEntry.finallyLoc;
        return ContinueSentinel;
      }

      return this.complete(record);
    },

    complete: function(record, afterLoc) {
      if (record.type === "throw") {
        throw record.arg;
      }

      if (record.type === "break" ||
          record.type === "continue") {
        this.next = record.arg;
      } else if (record.type === "return") {
        this.rval = this.arg = record.arg;
        this.method = "return";
        this.next = "end";
      } else if (record.type === "normal" && afterLoc) {
        this.next = afterLoc;
      }

      return ContinueSentinel;
    },

    finish: function(finallyLoc) {
      for (var i = this.tryEntries.length - 1; i >= 0; --i) {
        var entry = this.tryEntries[i];
        if (entry.finallyLoc === finallyLoc) {
          this.complete(entry.completion, entry.afterLoc);
          resetTryEntry(entry);
          return ContinueSentinel;
        }
      }
    },

    "catch": function(tryLoc) {
      for (var i = this.tryEntries.length - 1; i >= 0; --i) {
        var entry = this.tryEntries[i];
        if (entry.tryLoc === tryLoc) {
          var record = entry.completion;
          if (record.type === "throw") {
            var thrown = record.arg;
            resetTryEntry(entry);
          }
          return thrown;
        }
      }

      // The context.catch method must only be called with a location
      // argument that corresponds to a known catch block.
      throw new Error("illegal catch attempt");
    },

    delegateYield: function(iterable, resultName, nextLoc) {
      this.delegate = {
        iterator: values(iterable),
        resultName: resultName,
        nextLoc: nextLoc
      };

      if (this.method === "next") {
        // Deliberately forget the last sent value so that we don't
        // accidentally pass it on to the delegate.
        this.arg = undefined;
      }

      return ContinueSentinel;
    }
  };

  // Regardless of whether this script is executing as a CommonJS module
  // or not, return the runtime object so that we can declare the variable
  // regeneratorRuntime in the outer scope, which allows this module to be
  // injected easily by `bin/regenerator --include-runtime script.js`.
  return exports;

}(
  // If this script is executing as a CommonJS module, use module.exports
  // as the regeneratorRuntime namespace. Otherwise create a new empty
  // object. Either way, the resulting object will be used to initialize
  // the regeneratorRuntime variable at the top of this file.
   true ? module.exports : undefined
));

try {
  regeneratorRuntime = runtime;
} catch (accidentalStrictMode) {
  // This module should not be running in strict mode, so the above
  // assignment should always work unless something is misconfigured. Just
  // in case runtime.js accidentally runs in strict mode, we can escape
  // strict mode using a global Function call. This could conceivably fail
  // if a Content Security Policy forbids using Function, but in that case
  // the proper solution is to fix the accidental strict mode problem. If
  // you've misconfigured your bundler to force strict mode and applied a
  // CSP to forbid Function, and you're not willing to fix either of those
  // problems, please detail your unique predicament in a GitHub issue.
  Function("r", "regeneratorRuntime = r")(runtime);
}


/***/ }),

/***/ 433:
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),

/***/ 434:
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),

/***/ 435:
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),

/***/ 436:
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),

/***/ 437:
/***/ (function(module, exports, __webpack_require__) {

"use strict";


/**
 * Copyright 2015, Yahoo! Inc.
 * Copyrights licensed under the New BSD License. See the accompanying LICENSE file for terms.
 */
var REACT_STATICS = {
    childContextTypes: true,
    contextTypes: true,
    defaultProps: true,
    displayName: true,
    getDefaultProps: true,
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

var defineProperty = Object.defineProperty;
var getOwnPropertyNames = Object.getOwnPropertyNames;
var getOwnPropertySymbols = Object.getOwnPropertySymbols;
var getOwnPropertyDescriptor = Object.getOwnPropertyDescriptor;
var getPrototypeOf = Object.getPrototypeOf;
var objectPrototype = getPrototypeOf && getPrototypeOf(Object);

function hoistNonReactStatics(targetComponent, sourceComponent, blacklist) {
    if (typeof sourceComponent !== 'string') { // don't hoist over string (html) components

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

        for (var i = 0; i < keys.length; ++i) {
            var key = keys[i];
            if (!REACT_STATICS[key] && !KNOWN_STATICS[key] && (!blacklist || !blacklist[key])) {
                var descriptor = getOwnPropertyDescriptor(sourceComponent, key);
                try { // Avoid failures from read-only properties
                    defineProperty(targetComponent, key, descriptor);
                } catch (e) {}
            }
        }

        return targetComponent;
    }

    return targetComponent;
}

module.exports = hoistNonReactStatics;


/***/ }),

/***/ 438:
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),

/***/ 439:
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),

/***/ 44:
/***/ (function(module, exports, __webpack_require__) {

var _typeof = __webpack_require__(43);

var assertThisInitialized = __webpack_require__(59);

function _possibleConstructorReturn(self, call) {
  if (call && (_typeof(call) === "object" || typeof call === "function")) {
    return call;
  }

  return assertThisInitialized(self);
}

module.exports = _possibleConstructorReturn;

/***/ }),

/***/ 440:
/***/ (function(module, exports) {

module.exports = Array.isArray || function (arr) {
  return Object.prototype.toString.call(arr) == '[object Array]';
};


/***/ }),

/***/ 441:
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),

/***/ 442:
/***/ (function(module, exports) {

module.exports = function(originalModule) {
	if (!originalModule.webpackPolyfill) {
		var module = Object.create(originalModule);
		// module.parent = undefined by default
		if (!module.children) module.children = [];
		Object.defineProperty(module, "loaded", {
			enumerable: true,
			get: function() {
				return module.l;
			}
		});
		Object.defineProperty(module, "id", {
			enumerable: true,
			get: function() {
				return module.i;
			}
		});
		Object.defineProperty(module, "exports", {
			enumerable: true
		});
		module.webpackPolyfill = 1;
	}
	return module;
};


/***/ }),

/***/ 443:
/***/ (function(module, exports, __webpack_require__) {

var arrayLikeToArray = __webpack_require__(269);

function _arrayWithoutHoles(arr) {
  if (Array.isArray(arr)) return arrayLikeToArray(arr);
}

module.exports = _arrayWithoutHoles;

/***/ }),

/***/ 444:
/***/ (function(module, exports) {

function _iterableToArray(iter) {
  if (typeof Symbol !== "undefined" && Symbol.iterator in Object(iter)) return Array.from(iter);
}

module.exports = _iterableToArray;

/***/ }),

/***/ 445:
/***/ (function(module, exports) {

function _nonIterableSpread() {
  throw new TypeError("Invalid attempt to spread non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method.");
}

module.exports = _nonIterableSpread;

/***/ }),

/***/ 46:
/***/ (function(module, exports) {

function asyncGeneratorStep(gen, resolve, reject, _next, _throw, key, arg) {
  try {
    var info = gen[key](arg);
    var value = info.value;
  } catch (error) {
    reject(error);
    return;
  }

  if (info.done) {
    resolve(value);
  } else {
    Promise.resolve(value).then(_next, _throw);
  }
}

function _asyncToGenerator(fn) {
  return function () {
    var self = this,
        args = arguments;
    return new Promise(function (resolve, reject) {
      var gen = fn.apply(self, args);

      function _next(value) {
        asyncGeneratorStep(gen, resolve, reject, _next, _throw, "next", value);
      }

      function _throw(err) {
        asyncGeneratorStep(gen, resolve, reject, _next, _throw, "throw", err);
      }

      _next(undefined);
    });
  };
}

module.exports = _asyncToGenerator;

/***/ }),

/***/ 48:
/***/ (function(module, exports) {

(function() { module.exports = this["wp"]["hooks"]; }());

/***/ }),

/***/ 5:
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

/***/ 50:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";

// EXPORTS
__webpack_require__.d(__webpack_exports__, "a", function() { return /* binding */ build_module_focus; });

// UNUSED EXPORTS: isHorizontalEdge, isVerticalEdge, getRectangleFromRange, computeCaretRect, placeCaretAtHorizontalEdge, placeCaretAtVerticalEdge, isTextField, documentHasSelection, isEntirelySelected, getScrollContainer, getOffsetParent, replace, remove, insertAfter, unwrap, replaceTag, wrap, __unstableStripHTML

// NAMESPACE OBJECT: ./node_modules/@wordpress/dom/build-module/focusable.js
var focusable_namespaceObject = {};
__webpack_require__.r(focusable_namespaceObject);
__webpack_require__.d(focusable_namespaceObject, "find", function() { return find; });

// NAMESPACE OBJECT: ./node_modules/@wordpress/dom/build-module/tabbable.js
var tabbable_namespaceObject = {};
__webpack_require__.r(tabbable_namespaceObject);
__webpack_require__.d(tabbable_namespaceObject, "isTabbableIndex", function() { return isTabbableIndex; });
__webpack_require__.d(tabbable_namespaceObject, "find", function() { return tabbable_find; });
__webpack_require__.d(tabbable_namespaceObject, "findPrevious", function() { return findPrevious; });
__webpack_require__.d(tabbable_namespaceObject, "findNext", function() { return findNext; });

// CONCATENATED MODULE: ./node_modules/@wordpress/dom/build-module/focusable.js
/**
 * References:
 *
 * Focusable:
 *  - https://www.w3.org/TR/html5/editing.html#focus-management
 *
 * Sequential focus navigation:
 *  - https://www.w3.org/TR/html5/editing.html#sequential-focus-navigation-and-the-tabindex-attribute
 *
 * Disabled elements:
 *  - https://www.w3.org/TR/html5/disabled-elements.html#disabled-elements
 *
 * getClientRects algorithm (requiring layout box):
 *  - https://www.w3.org/TR/cssom-view-1/#extension-to-the-element-interface
 *
 * AREA elements associated with an IMG:
 *  - https://w3c.github.io/html/editing.html#data-model
 */
var SELECTOR = ['[tabindex]', 'a[href]', 'button:not([disabled])', 'input:not([type="hidden"]):not([disabled])', 'select:not([disabled])', 'textarea:not([disabled])', 'iframe', 'object', 'embed', 'area[href]', '[contenteditable]:not([contenteditable=false])'].join(',');
/**
 * Returns true if the specified element is visible (i.e. neither display: none
 * nor visibility: hidden).
 *
 * @param {Element} element DOM element to test.
 *
 * @return {boolean} Whether element is visible.
 */

function isVisible(element) {
  return element.offsetWidth > 0 || element.offsetHeight > 0 || element.getClientRects().length > 0;
}
/**
 * Returns true if the specified area element is a valid focusable element, or
 * false otherwise. Area is only focusable if within a map where a named map
 * referenced by an image somewhere in the document.
 *
 * @param {Element} element DOM area element to test.
 *
 * @return {boolean} Whether area element is valid for focus.
 */


function isValidFocusableArea(element) {
  var map = element.closest('map[name]');

  if (!map) {
    return false;
  }

  var img = document.querySelector('img[usemap="#' + map.name + '"]');
  return !!img && isVisible(img);
}
/**
 * Returns all focusable elements within a given context.
 *
 * @param {Element} context Element in which to search.
 *
 * @return {Element[]} Focusable elements.
 */


function find(context) {
  var elements = context.querySelectorAll(SELECTOR);
  return Array.from(elements).filter(function (element) {
    if (!isVisible(element)) {
      return false;
    }

    var nodeName = element.nodeName;

    if ('AREA' === nodeName) {
      return isValidFocusableArea(element);
    }

    return true;
  });
}
//# sourceMappingURL=focusable.js.map
// EXTERNAL MODULE: external "lodash"
var external_lodash_ = __webpack_require__(2);

// CONCATENATED MODULE: ./node_modules/@wordpress/dom/build-module/tabbable.js
/**
 * External dependencies
 */

/**
 * Internal dependencies
 */


/**
 * Returns the tab index of the given element. In contrast with the tabIndex
 * property, this normalizes the default (0) to avoid browser inconsistencies,
 * operating under the assumption that this function is only ever called with a
 * focusable node.
 *
 * @see https://bugzilla.mozilla.org/show_bug.cgi?id=1190261
 *
 * @param {Element} element Element from which to retrieve.
 *
 * @return {?number} Tab index of element (default 0).
 */

function getTabIndex(element) {
  var tabIndex = element.getAttribute('tabindex');
  return tabIndex === null ? 0 : parseInt(tabIndex, 10);
}
/**
 * Returns true if the specified element is tabbable, or false otherwise.
 *
 * @param {Element} element Element to test.
 *
 * @return {boolean} Whether element is tabbable.
 */


function isTabbableIndex(element) {
  return getTabIndex(element) !== -1;
}
/**
 * Returns a stateful reducer function which constructs a filtered array of
 * tabbable elements, where at most one radio input is selected for a given
 * name, giving priority to checked input, falling back to the first
 * encountered.
 *
 * @return {Function} Radio group collapse reducer.
 */

function createStatefulCollapseRadioGroup() {
  var CHOSEN_RADIO_BY_NAME = {};
  return function collapseRadioGroup(result, element) {
    var nodeName = element.nodeName,
        type = element.type,
        checked = element.checked,
        name = element.name; // For all non-radio tabbables, construct to array by concatenating.

    if (nodeName !== 'INPUT' || type !== 'radio' || !name) {
      return result.concat(element);
    }

    var hasChosen = CHOSEN_RADIO_BY_NAME.hasOwnProperty(name); // Omit by skipping concatenation if the radio element is not chosen.

    var isChosen = checked || !hasChosen;

    if (!isChosen) {
      return result;
    } // At this point, if there had been a chosen element, the current
    // element is checked and should take priority. Retroactively remove
    // the element which had previously been considered the chosen one.


    if (hasChosen) {
      var hadChosenElement = CHOSEN_RADIO_BY_NAME[name];
      result = Object(external_lodash_["without"])(result, hadChosenElement);
    }

    CHOSEN_RADIO_BY_NAME[name] = element;
    return result.concat(element);
  };
}
/**
 * An array map callback, returning an object with the element value and its
 * array index location as properties. This is used to emulate a proper stable
 * sort where equal tabIndex should be left in order of their occurrence in the
 * document.
 *
 * @param {Element} element Element.
 * @param {number}  index   Array index of element.
 *
 * @return {Object} Mapped object with element, index.
 */


function mapElementToObjectTabbable(element, index) {
  return {
    element: element,
    index: index
  };
}
/**
 * An array map callback, returning an element of the given mapped object's
 * element value.
 *
 * @param {Object} object Mapped object with index.
 *
 * @return {Element} Mapped object element.
 */


function mapObjectTabbableToElement(object) {
  return object.element;
}
/**
 * A sort comparator function used in comparing two objects of mapped elements.
 *
 * @see mapElementToObjectTabbable
 *
 * @param {Object} a First object to compare.
 * @param {Object} b Second object to compare.
 *
 * @return {number} Comparator result.
 */


function compareObjectTabbables(a, b) {
  var aTabIndex = getTabIndex(a.element);
  var bTabIndex = getTabIndex(b.element);

  if (aTabIndex === bTabIndex) {
    return a.index - b.index;
  }

  return aTabIndex - bTabIndex;
}
/**
 * Givin focusable elements, filters out tabbable element.
 *
 * @param {Array} focusables Focusable elements to filter.
 *
 * @return {Array} Tabbable elements.
 */


function filterTabbable(focusables) {
  return focusables.filter(isTabbableIndex).map(mapElementToObjectTabbable).sort(compareObjectTabbables).map(mapObjectTabbableToElement).reduce(createStatefulCollapseRadioGroup(), []);
}

function tabbable_find(context) {
  return filterTabbable(find(context));
}
/**
 * Given a focusable element, find the preceding tabbable element.
 *
 * @param {Element} element The focusable element before which to look. Defaults
 *                          to the active element.
 */

function findPrevious() {
  var element = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : document.activeElement;
  var focusables = find(document.body);
  var index = focusables.indexOf(element); // Remove all focusables after and including `element`.

  focusables.length = index;
  return Object(external_lodash_["last"])(filterTabbable(focusables));
}
/**
 * Given a focusable element, find the next tabbable element.
 *
 * @param {Element} element The focusable element after which to look. Defaults
 *                          to the active element.
 */

function findNext() {
  var element = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : document.activeElement;
  var focusables = find(document.body);
  var index = focusables.indexOf(element); // Remove all focusables before and inside `element`.

  var remaining = focusables.slice(index + 1).filter(function (node) {
    return !element.contains(node);
  });
  return Object(external_lodash_["first"])(filterTabbable(remaining));
}
//# sourceMappingURL=tabbable.js.map
// CONCATENATED MODULE: ./node_modules/@wordpress/dom/build-module/index.js
/**
 * Internal dependencies
 */


/**
 * Object grouping `focusable` and `tabbable` utils
 * under the keys with the same name.
 */

var build_module_focus = {
  focusable: focusable_namespaceObject,
  tabbable: tabbable_namespaceObject
};

//# sourceMappingURL=index.js.map

/***/ }),

/***/ 51:
/***/ (function(module, exports) {

(function() { module.exports = this["wc"]["data"]; }());

/***/ }),

/***/ 52:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "a", function() { return _unsupportedIterableToArray; });
/* harmony import */ var _arrayLikeToArray__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(37);

function _unsupportedIterableToArray(o, minLen) {
  if (!o) return;
  if (typeof o === "string") return Object(_arrayLikeToArray__WEBPACK_IMPORTED_MODULE_0__[/* default */ "a"])(o, minLen);
  var n = Object.prototype.toString.call(o).slice(8, -1);
  if (n === "Object" && o.constructor) n = o.constructor.name;
  if (n === "Map" || n === "Set") return Array.from(n);
  if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return Object(_arrayLikeToArray__WEBPACK_IMPORTED_MODULE_0__[/* default */ "a"])(o, minLen);
}

/***/ }),

/***/ 53:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(2);
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_0__);
/**
 * External dependencies
 */

/**
 * Given a function mapping a component to an enhanced component and modifier
 * name, returns the enhanced component augmented with a generated displayName.
 *
 * @param {Function} mapComponentToEnhancedComponent Function mapping component
 *                                                   to enhanced component.
 * @param {string}   modifierName                    Seed name from which to
 *                                                   generated display name.
 *
 * @return {WPComponent} Component class with generated display name assigned.
 */

function createHigherOrderComponent(mapComponentToEnhancedComponent, modifierName) {
  return function (OriginalComponent) {
    var EnhancedComponent = mapComponentToEnhancedComponent(OriginalComponent);
    var _OriginalComponent$di = OriginalComponent.displayName,
        displayName = _OriginalComponent$di === void 0 ? OriginalComponent.name || 'Component' : _OriginalComponent$di;
    EnhancedComponent.displayName = "".concat(Object(lodash__WEBPACK_IMPORTED_MODULE_0__["upperFirst"])(Object(lodash__WEBPACK_IMPORTED_MODULE_0__["camelCase"])(modifierName)), "(").concat(displayName, ")");
    return EnhancedComponent;
  };
}

/* harmony default export */ __webpack_exports__["a"] = (createHigherOrderComponent);
//# sourceMappingURL=index.js.map

/***/ }),

/***/ 54:
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

/***/ 55:
/***/ (function(module, exports) {

function _interopRequireDefault(obj) {
  return obj && obj.__esModule ? obj : {
    "default": obj
  };
}

module.exports = _interopRequireDefault;

/***/ }),

/***/ 57:
/***/ (function(module, exports) {

(function() { module.exports = this["wp"]["dataControls"]; }());

/***/ }),

/***/ 58:
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var stringify = __webpack_require__(111);
var parse = __webpack_require__(112);
var formats = __webpack_require__(86);

module.exports = {
    formats: formats,
    parse: parse,
    stringify: stringify
};


/***/ }),

/***/ 59:
/***/ (function(module, exports) {

function _assertThisInitialized(self) {
  if (self === void 0) {
    throw new ReferenceError("this hasn't been initialised - super() hasn't been called");
  }

  return self;
}

module.exports = _assertThisInitialized;

/***/ }),

/***/ 6:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "a", function() { return _createClass; });
function _defineProperties(target, props) {
  for (var i = 0; i < props.length; i++) {
    var descriptor = props[i];
    descriptor.enumerable = descriptor.enumerable || false;
    descriptor.configurable = true;
    if ("value" in descriptor) descriptor.writable = true;
    Object.defineProperty(target, descriptor.key, descriptor);
  }
}

function _createClass(Constructor, protoProps, staticProps) {
  if (protoProps) _defineProperties(Constructor.prototype, protoProps);
  if (staticProps) _defineProperties(Constructor, staticProps);
  return Constructor;
}

/***/ }),

/***/ 62:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* unused harmony export Circle */
/* unused harmony export G */
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "a", function() { return Path; });
/* unused harmony export Polygon */
/* unused harmony export Rect */
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "b", function() { return SVG; });
/* harmony import */ var _babel_runtime_helpers_esm_objectSpread__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(27);
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(2);
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__);


/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */


var Circle = function Circle(props) {
  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__["createElement"])('circle', props);
};
var G = function G(props) {
  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__["createElement"])('g', props);
};
var Path = function Path(props) {
  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__["createElement"])('path', props);
};
var Polygon = function Polygon(props) {
  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__["createElement"])('polygon', props);
};
var Rect = function Rect(props) {
  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__["createElement"])('rect', props);
};
var SVG = function SVG(props) {
  var appliedProps = Object(_babel_runtime_helpers_esm_objectSpread__WEBPACK_IMPORTED_MODULE_0__[/* default */ "a"])({}, props, {
    role: 'img',
    'aria-hidden': 'true',
    focusable: 'false'
  }); // Disable reason: We need to have a way to render HTML tag for web.
  // eslint-disable-next-line react/forbid-elements


  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__["createElement"])("svg", Object(lodash__WEBPACK_IMPORTED_MODULE_1__["omit"])(appliedProps, '__unstableActive'));
};
//# sourceMappingURL=index.js.map

/***/ }),

/***/ 63:
/***/ (function(module, exports) {

(function() { module.exports = this["wc"]["components"]; }());

/***/ }),

/***/ 64:
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


/***/ }),

/***/ 65:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "a", function() { return domReady; });
/**
 * @typedef {() => void} Callback
 *
 * TODO: Remove this typedef and inline `() => void` type.
 *
 * This typedef is used so that a descriptive type is provided in our
 * automatically generated documentation.
 *
 * An in-line type `() => void` would be preferable, but the generated
 * documentation is `null` in that case.
 *
 * @see https://github.com/WordPress/gutenberg/issues/18045
 */

/**
 * Specify a function to execute when the DOM is fully loaded.
 *
 * @param {Callback} callback A function to execute after the DOM is ready.
 *
 * @example
 * ```js
 * import domReady from '@wordpress/dom-ready';
 *
 * domReady( function() {
 * 	//do something after DOM loads.
 * } );
 * ```
 *
 * @return {void}
 */
function domReady(callback) {
  if (document.readyState === 'complete' || // DOMContentLoaded + Images/Styles/etc loaded, so we call directly.
  document.readyState === 'interactive' // DOMContentLoaded fires at this point, so we call directly.
  ) {
      return void callback();
    } // DOMContentLoaded has not fired yet, delay callback until then.


  document.addEventListener('DOMContentLoaded', callback);
}
//# sourceMappingURL=index.js.map

/***/ }),

/***/ 69:
/***/ (function(module, exports) {

(function() { module.exports = this["wp"]["htmlEntities"]; }());

/***/ }),

/***/ 7:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "a", function() { return _classCallCheck; });
function _classCallCheck(instance, Constructor) {
  if (!(instance instanceof Constructor)) {
    throw new TypeError("Cannot call a class as a function");
  }
}

/***/ }),

/***/ 70:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "a", function() { return _asyncToGenerator; });
function asyncGeneratorStep(gen, resolve, reject, _next, _throw, key, arg) {
  try {
    var info = gen[key](arg);
    var value = info.value;
  } catch (error) {
    reject(error);
    return;
  }

  if (info.done) {
    resolve(value);
  } else {
    Promise.resolve(value).then(_next, _throw);
  }
}

function _asyncToGenerator(fn) {
  return function () {
    var self = this,
        args = arguments;
    return new Promise(function (resolve, reject) {
      var gen = fn.apply(self, args);

      function _next(value) {
        asyncGeneratorStep(gen, resolve, reject, _next, _throw, "next", value);
      }

      function _throw(err) {
        asyncGeneratorStep(gen, resolve, reject, _next, _throw, "throw", err);
      }

      _next(undefined);
    });
  };
}

/***/ }),

/***/ 704:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";

// EXTERNAL MODULE: external {"this":["wp","data"]}
var external_this_wp_data_ = __webpack_require__(19);

// EXTERNAL MODULE: ./node_modules/@fresh-data/framework/es/index.js + 8 modules
var es = __webpack_require__(170);

// EXTERNAL MODULE: ./node_modules/symbol-observable/es/index.js
var symbol_observable_es = __webpack_require__(259);

// CONCATENATED MODULE: ./node_modules/redux/es/redux.js


/**
 * These are private action types reserved by Redux.
 * For any unknown actions, you must return the current state.
 * If the current state is undefined, you must return the initial state.
 * Do not reference these action types directly in your code.
 */
var randomString = function randomString() {
  return Math.random().toString(36).substring(7).split('').join('.');
};

var ActionTypes = {
  INIT: "@@redux/INIT" + randomString(),
  REPLACE: "@@redux/REPLACE" + randomString(),
  PROBE_UNKNOWN_ACTION: function PROBE_UNKNOWN_ACTION() {
    return "@@redux/PROBE_UNKNOWN_ACTION" + randomString();
  }
};

/**
 * @param {any} obj The object to inspect.
 * @returns {boolean} True if the argument appears to be a plain object.
 */
function isPlainObject(obj) {
  if (typeof obj !== 'object' || obj === null) return false;
  var proto = obj;

  while (Object.getPrototypeOf(proto) !== null) {
    proto = Object.getPrototypeOf(proto);
  }

  return Object.getPrototypeOf(obj) === proto;
}

/**
 * Creates a Redux store that holds the state tree.
 * The only way to change the data in the store is to call `dispatch()` on it.
 *
 * There should only be a single store in your app. To specify how different
 * parts of the state tree respond to actions, you may combine several reducers
 * into a single reducer function by using `combineReducers`.
 *
 * @param {Function} reducer A function that returns the next state tree, given
 * the current state tree and the action to handle.
 *
 * @param {any} [preloadedState] The initial state. You may optionally specify it
 * to hydrate the state from the server in universal apps, or to restore a
 * previously serialized user session.
 * If you use `combineReducers` to produce the root reducer function, this must be
 * an object with the same shape as `combineReducers` keys.
 *
 * @param {Function} [enhancer] The store enhancer. You may optionally specify it
 * to enhance the store with third-party capabilities such as middleware,
 * time travel, persistence, etc. The only store enhancer that ships with Redux
 * is `applyMiddleware()`.
 *
 * @returns {Store} A Redux store that lets you read the state, dispatch actions
 * and subscribe to changes.
 */

function createStore(reducer, preloadedState, enhancer) {
  var _ref2;

  if (typeof preloadedState === 'function' && typeof enhancer === 'function' || typeof enhancer === 'function' && typeof arguments[3] === 'function') {
    throw new Error('It looks like you are passing several store enhancers to ' + 'createStore(). This is not supported. Instead, compose them ' + 'together to a single function.');
  }

  if (typeof preloadedState === 'function' && typeof enhancer === 'undefined') {
    enhancer = preloadedState;
    preloadedState = undefined;
  }

  if (typeof enhancer !== 'undefined') {
    if (typeof enhancer !== 'function') {
      throw new Error('Expected the enhancer to be a function.');
    }

    return enhancer(createStore)(reducer, preloadedState);
  }

  if (typeof reducer !== 'function') {
    throw new Error('Expected the reducer to be a function.');
  }

  var currentReducer = reducer;
  var currentState = preloadedState;
  var currentListeners = [];
  var nextListeners = currentListeners;
  var isDispatching = false;
  /**
   * This makes a shallow copy of currentListeners so we can use
   * nextListeners as a temporary list while dispatching.
   *
   * This prevents any bugs around consumers calling
   * subscribe/unsubscribe in the middle of a dispatch.
   */

  function ensureCanMutateNextListeners() {
    if (nextListeners === currentListeners) {
      nextListeners = currentListeners.slice();
    }
  }
  /**
   * Reads the state tree managed by the store.
   *
   * @returns {any} The current state tree of your application.
   */


  function getState() {
    if (isDispatching) {
      throw new Error('You may not call store.getState() while the reducer is executing. ' + 'The reducer has already received the state as an argument. ' + 'Pass it down from the top reducer instead of reading it from the store.');
    }

    return currentState;
  }
  /**
   * Adds a change listener. It will be called any time an action is dispatched,
   * and some part of the state tree may potentially have changed. You may then
   * call `getState()` to read the current state tree inside the callback.
   *
   * You may call `dispatch()` from a change listener, with the following
   * caveats:
   *
   * 1. The subscriptions are snapshotted just before every `dispatch()` call.
   * If you subscribe or unsubscribe while the listeners are being invoked, this
   * will not have any effect on the `dispatch()` that is currently in progress.
   * However, the next `dispatch()` call, whether nested or not, will use a more
   * recent snapshot of the subscription list.
   *
   * 2. The listener should not expect to see all state changes, as the state
   * might have been updated multiple times during a nested `dispatch()` before
   * the listener is called. It is, however, guaranteed that all subscribers
   * registered before the `dispatch()` started will be called with the latest
   * state by the time it exits.
   *
   * @param {Function} listener A callback to be invoked on every dispatch.
   * @returns {Function} A function to remove this change listener.
   */


  function subscribe(listener) {
    if (typeof listener !== 'function') {
      throw new Error('Expected the listener to be a function.');
    }

    if (isDispatching) {
      throw new Error('You may not call store.subscribe() while the reducer is executing. ' + 'If you would like to be notified after the store has been updated, subscribe from a ' + 'component and invoke store.getState() in the callback to access the latest state. ' + 'See https://redux.js.org/api-reference/store#subscribelistener for more details.');
    }

    var isSubscribed = true;
    ensureCanMutateNextListeners();
    nextListeners.push(listener);
    return function unsubscribe() {
      if (!isSubscribed) {
        return;
      }

      if (isDispatching) {
        throw new Error('You may not unsubscribe from a store listener while the reducer is executing. ' + 'See https://redux.js.org/api-reference/store#subscribelistener for more details.');
      }

      isSubscribed = false;
      ensureCanMutateNextListeners();
      var index = nextListeners.indexOf(listener);
      nextListeners.splice(index, 1);
      currentListeners = null;
    };
  }
  /**
   * Dispatches an action. It is the only way to trigger a state change.
   *
   * The `reducer` function, used to create the store, will be called with the
   * current state tree and the given `action`. Its return value will
   * be considered the **next** state of the tree, and the change listeners
   * will be notified.
   *
   * The base implementation only supports plain object actions. If you want to
   * dispatch a Promise, an Observable, a thunk, or something else, you need to
   * wrap your store creating function into the corresponding middleware. For
   * example, see the documentation for the `redux-thunk` package. Even the
   * middleware will eventually dispatch plain object actions using this method.
   *
   * @param {Object} action A plain object representing “what changed”. It is
   * a good idea to keep actions serializable so you can record and replay user
   * sessions, or use the time travelling `redux-devtools`. An action must have
   * a `type` property which may not be `undefined`. It is a good idea to use
   * string constants for action types.
   *
   * @returns {Object} For convenience, the same action object you dispatched.
   *
   * Note that, if you use a custom middleware, it may wrap `dispatch()` to
   * return something else (for example, a Promise you can await).
   */


  function dispatch(action) {
    if (!isPlainObject(action)) {
      throw new Error('Actions must be plain objects. ' + 'Use custom middleware for async actions.');
    }

    if (typeof action.type === 'undefined') {
      throw new Error('Actions may not have an undefined "type" property. ' + 'Have you misspelled a constant?');
    }

    if (isDispatching) {
      throw new Error('Reducers may not dispatch actions.');
    }

    try {
      isDispatching = true;
      currentState = currentReducer(currentState, action);
    } finally {
      isDispatching = false;
    }

    var listeners = currentListeners = nextListeners;

    for (var i = 0; i < listeners.length; i++) {
      var listener = listeners[i];
      listener();
    }

    return action;
  }
  /**
   * Replaces the reducer currently used by the store to calculate the state.
   *
   * You might need this if your app implements code splitting and you want to
   * load some of the reducers dynamically. You might also need this if you
   * implement a hot reloading mechanism for Redux.
   *
   * @param {Function} nextReducer The reducer for the store to use instead.
   * @returns {void}
   */


  function replaceReducer(nextReducer) {
    if (typeof nextReducer !== 'function') {
      throw new Error('Expected the nextReducer to be a function.');
    }

    currentReducer = nextReducer; // This action has a similiar effect to ActionTypes.INIT.
    // Any reducers that existed in both the new and old rootReducer
    // will receive the previous state. This effectively populates
    // the new state tree with any relevant data from the old one.

    dispatch({
      type: ActionTypes.REPLACE
    });
  }
  /**
   * Interoperability point for observable/reactive libraries.
   * @returns {observable} A minimal observable of state changes.
   * For more information, see the observable proposal:
   * https://github.com/tc39/proposal-observable
   */


  function observable() {
    var _ref;

    var outerSubscribe = subscribe;
    return _ref = {
      /**
       * The minimal observable subscription method.
       * @param {Object} observer Any object that can be used as an observer.
       * The observer object should have a `next` method.
       * @returns {subscription} An object with an `unsubscribe` method that can
       * be used to unsubscribe the observable from the store, and prevent further
       * emission of values from the observable.
       */
      subscribe: function subscribe(observer) {
        if (typeof observer !== 'object' || observer === null) {
          throw new TypeError('Expected the observer to be an object.');
        }

        function observeState() {
          if (observer.next) {
            observer.next(getState());
          }
        }

        observeState();
        var unsubscribe = outerSubscribe(observeState);
        return {
          unsubscribe: unsubscribe
        };
      }
    }, _ref[symbol_observable_es["a" /* default */]] = function () {
      return this;
    }, _ref;
  } // When a store is created, an "INIT" action is dispatched so that every
  // reducer returns their initial state. This effectively populates
  // the initial state tree.


  dispatch({
    type: ActionTypes.INIT
  });
  return _ref2 = {
    dispatch: dispatch,
    subscribe: subscribe,
    getState: getState,
    replaceReducer: replaceReducer
  }, _ref2[symbol_observable_es["a" /* default */]] = observable, _ref2;
}

/**
 * Prints a warning in the console if it exists.
 *
 * @param {String} message The warning message.
 * @returns {void}
 */
function warning(message) {
  /* eslint-disable no-console */
  if (typeof console !== 'undefined' && typeof console.error === 'function') {
    console.error(message);
  }
  /* eslint-enable no-console */


  try {
    // This error was thrown as a convenience so that if you enable
    // "break on all exceptions" in your console,
    // it would pause the execution at this line.
    throw new Error(message);
  } catch (e) {} // eslint-disable-line no-empty

}

function getUndefinedStateErrorMessage(key, action) {
  var actionType = action && action.type;
  var actionDescription = actionType && "action \"" + String(actionType) + "\"" || 'an action';
  return "Given " + actionDescription + ", reducer \"" + key + "\" returned undefined. " + "To ignore an action, you must explicitly return the previous state. " + "If you want this reducer to hold no value, you can return null instead of undefined.";
}

function getUnexpectedStateShapeWarningMessage(inputState, reducers, action, unexpectedKeyCache) {
  var reducerKeys = Object.keys(reducers);
  var argumentName = action && action.type === ActionTypes.INIT ? 'preloadedState argument passed to createStore' : 'previous state received by the reducer';

  if (reducerKeys.length === 0) {
    return 'Store does not have a valid reducer. Make sure the argument passed ' + 'to combineReducers is an object whose values are reducers.';
  }

  if (!isPlainObject(inputState)) {
    return "The " + argumentName + " has unexpected type of \"" + {}.toString.call(inputState).match(/\s([a-z|A-Z]+)/)[1] + "\". Expected argument to be an object with the following " + ("keys: \"" + reducerKeys.join('", "') + "\"");
  }

  var unexpectedKeys = Object.keys(inputState).filter(function (key) {
    return !reducers.hasOwnProperty(key) && !unexpectedKeyCache[key];
  });
  unexpectedKeys.forEach(function (key) {
    unexpectedKeyCache[key] = true;
  });
  if (action && action.type === ActionTypes.REPLACE) return;

  if (unexpectedKeys.length > 0) {
    return "Unexpected " + (unexpectedKeys.length > 1 ? 'keys' : 'key') + " " + ("\"" + unexpectedKeys.join('", "') + "\" found in " + argumentName + ". ") + "Expected to find one of the known reducer keys instead: " + ("\"" + reducerKeys.join('", "') + "\". Unexpected keys will be ignored.");
  }
}

function assertReducerShape(reducers) {
  Object.keys(reducers).forEach(function (key) {
    var reducer = reducers[key];
    var initialState = reducer(undefined, {
      type: ActionTypes.INIT
    });

    if (typeof initialState === 'undefined') {
      throw new Error("Reducer \"" + key + "\" returned undefined during initialization. " + "If the state passed to the reducer is undefined, you must " + "explicitly return the initial state. The initial state may " + "not be undefined. If you don't want to set a value for this reducer, " + "you can use null instead of undefined.");
    }

    if (typeof reducer(undefined, {
      type: ActionTypes.PROBE_UNKNOWN_ACTION()
    }) === 'undefined') {
      throw new Error("Reducer \"" + key + "\" returned undefined when probed with a random type. " + ("Don't try to handle " + ActionTypes.INIT + " or other actions in \"redux/*\" ") + "namespace. They are considered private. Instead, you must return the " + "current state for any unknown actions, unless it is undefined, " + "in which case you must return the initial state, regardless of the " + "action type. The initial state may not be undefined, but can be null.");
    }
  });
}
/**
 * Turns an object whose values are different reducer functions, into a single
 * reducer function. It will call every child reducer, and gather their results
 * into a single state object, whose keys correspond to the keys of the passed
 * reducer functions.
 *
 * @param {Object} reducers An object whose values correspond to different
 * reducer functions that need to be combined into one. One handy way to obtain
 * it is to use ES6 `import * as reducers` syntax. The reducers may never return
 * undefined for any action. Instead, they should return their initial state
 * if the state passed to them was undefined, and the current state for any
 * unrecognized action.
 *
 * @returns {Function} A reducer function that invokes every reducer inside the
 * passed object, and builds a state object with the same shape.
 */


function combineReducers(reducers) {
  var reducerKeys = Object.keys(reducers);
  var finalReducers = {};

  for (var i = 0; i < reducerKeys.length; i++) {
    var key = reducerKeys[i];

    if (false) {}

    if (typeof reducers[key] === 'function') {
      finalReducers[key] = reducers[key];
    }
  }

  var finalReducerKeys = Object.keys(finalReducers); // This is used to make sure we don't warn about the same
  // keys multiple times.

  var unexpectedKeyCache;

  if (false) {}

  var shapeAssertionError;

  try {
    assertReducerShape(finalReducers);
  } catch (e) {
    shapeAssertionError = e;
  }

  return function combination(state, action) {
    if (state === void 0) {
      state = {};
    }

    if (shapeAssertionError) {
      throw shapeAssertionError;
    }

    if (false) { var warningMessage; }

    var hasChanged = false;
    var nextState = {};

    for (var _i = 0; _i < finalReducerKeys.length; _i++) {
      var _key = finalReducerKeys[_i];
      var reducer = finalReducers[_key];
      var previousStateForKey = state[_key];
      var nextStateForKey = reducer(previousStateForKey, action);

      if (typeof nextStateForKey === 'undefined') {
        var errorMessage = getUndefinedStateErrorMessage(_key, action);
        throw new Error(errorMessage);
      }

      nextState[_key] = nextStateForKey;
      hasChanged = hasChanged || nextStateForKey !== previousStateForKey;
    }

    hasChanged = hasChanged || finalReducerKeys.length !== Object.keys(state).length;
    return hasChanged ? nextState : state;
  };
}

function bindActionCreator(actionCreator, dispatch) {
  return function () {
    return dispatch(actionCreator.apply(this, arguments));
  };
}
/**
 * Turns an object whose values are action creators, into an object with the
 * same keys, but with every function wrapped into a `dispatch` call so they
 * may be invoked directly. This is just a convenience method, as you can call
 * `store.dispatch(MyActionCreators.doSomething())` yourself just fine.
 *
 * For convenience, you can also pass an action creator as the first argument,
 * and get a dispatch wrapped function in return.
 *
 * @param {Function|Object} actionCreators An object whose values are action
 * creator functions. One handy way to obtain it is to use ES6 `import * as`
 * syntax. You may also pass a single function.
 *
 * @param {Function} dispatch The `dispatch` function available on your Redux
 * store.
 *
 * @returns {Function|Object} The object mimicking the original object, but with
 * every action creator wrapped into the `dispatch` call. If you passed a
 * function as `actionCreators`, the return value will also be a single
 * function.
 */


function bindActionCreators(actionCreators, dispatch) {
  if (typeof actionCreators === 'function') {
    return bindActionCreator(actionCreators, dispatch);
  }

  if (typeof actionCreators !== 'object' || actionCreators === null) {
    throw new Error("bindActionCreators expected an object or a function, instead received " + (actionCreators === null ? 'null' : typeof actionCreators) + ". " + "Did you write \"import ActionCreators from\" instead of \"import * as ActionCreators from\"?");
  }

  var boundActionCreators = {};

  for (var key in actionCreators) {
    var actionCreator = actionCreators[key];

    if (typeof actionCreator === 'function') {
      boundActionCreators[key] = bindActionCreator(actionCreator, dispatch);
    }
  }

  return boundActionCreators;
}

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

function ownKeys(object, enumerableOnly) {
  var keys = Object.keys(object);

  if (Object.getOwnPropertySymbols) {
    keys.push.apply(keys, Object.getOwnPropertySymbols(object));
  }

  if (enumerableOnly) keys = keys.filter(function (sym) {
    return Object.getOwnPropertyDescriptor(object, sym).enumerable;
  });
  return keys;
}

function _objectSpread2(target) {
  for (var i = 1; i < arguments.length; i++) {
    var source = arguments[i] != null ? arguments[i] : {};

    if (i % 2) {
      ownKeys(source, true).forEach(function (key) {
        _defineProperty(target, key, source[key]);
      });
    } else if (Object.getOwnPropertyDescriptors) {
      Object.defineProperties(target, Object.getOwnPropertyDescriptors(source));
    } else {
      ownKeys(source).forEach(function (key) {
        Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key));
      });
    }
  }

  return target;
}

/**
 * Composes single-argument functions from right to left. The rightmost
 * function can take multiple arguments as it provides the signature for
 * the resulting composite function.
 *
 * @param {...Function} funcs The functions to compose.
 * @returns {Function} A function obtained by composing the argument functions
 * from right to left. For example, compose(f, g, h) is identical to doing
 * (...args) => f(g(h(...args))).
 */
function compose() {
  for (var _len = arguments.length, funcs = new Array(_len), _key = 0; _key < _len; _key++) {
    funcs[_key] = arguments[_key];
  }

  if (funcs.length === 0) {
    return function (arg) {
      return arg;
    };
  }

  if (funcs.length === 1) {
    return funcs[0];
  }

  return funcs.reduce(function (a, b) {
    return function () {
      return a(b.apply(void 0, arguments));
    };
  });
}

/**
 * Creates a store enhancer that applies middleware to the dispatch method
 * of the Redux store. This is handy for a variety of tasks, such as expressing
 * asynchronous actions in a concise manner, or logging every action payload.
 *
 * See `redux-thunk` package as an example of the Redux middleware.
 *
 * Because middleware is potentially asynchronous, this should be the first
 * store enhancer in the composition chain.
 *
 * Note that each middleware will be given the `dispatch` and `getState` functions
 * as named arguments.
 *
 * @param {...Function} middlewares The middleware chain to be applied.
 * @returns {Function} A store enhancer applying the middleware.
 */

function applyMiddleware() {
  for (var _len = arguments.length, middlewares = new Array(_len), _key = 0; _key < _len; _key++) {
    middlewares[_key] = arguments[_key];
  }

  return function (createStore) {
    return function () {
      var store = createStore.apply(void 0, arguments);

      var _dispatch = function dispatch() {
        throw new Error('Dispatching while constructing your middleware is not allowed. ' + 'Other middleware would not be applied to this dispatch.');
      };

      var middlewareAPI = {
        getState: store.getState,
        dispatch: function dispatch() {
          return _dispatch.apply(void 0, arguments);
        }
      };
      var chain = middlewares.map(function (middleware) {
        return middleware(middlewareAPI);
      });
      _dispatch = compose.apply(void 0, chain)(store.dispatch);
      return _objectSpread2({}, store, {
        dispatch: _dispatch
      });
    };
  };
}

/*
 * This is a dummy function to check if the function name has been altered by minification.
 * If the function has been minified and NODE_ENV !== 'production', warn the user.
 */

function isCrushed() {}

if (false) {}



// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/defineProperty.js
var defineProperty = __webpack_require__(15);
var defineProperty_default = /*#__PURE__*/__webpack_require__.n(defineProperty);

// CONCATENATED MODULE: ./client/wc-api/wp-data-store/reducer.js


function reducer_ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { reducer_ownKeys(Object(source), true).forEach(function (key) { defineProperty_default()(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { reducer_ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

var defaultState = {
  resources: {}
};
/**
 * Primary reducer for fresh-data apiclient data.
 *
 * @param {Object} state The base state for fresh-data.
 * @param {Object} action Action object to be processed.
 * @return {Object} The new state, or the previous state if this action doesn't belong to fresh-data.
 */

function reducer_reducer() {
  var state = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : defaultState;
  var action = arguments.length > 1 ? arguments[1] : undefined;

  switch (action.type) {
    case 'FRESH_DATA_REQUESTED':
      return reduceRequested(state, action);

    case 'FRESH_DATA_RECEIVED':
      return reduceReceived(state, action);

    default:
      return state;
  }
}
function reduceRequested(state, action) {
  var newResources = action.resourceNames.reduce(function (resources, name) {
    resources[name] = {
      lastRequested: action.time
    };
    return resources;
  }, {});
  return reduceResources(state, newResources);
}
function reduceReceived(state, action) {
  var newResources = Object.keys(action.resources).reduce(function (resources, name) {
    var resource = _objectSpread({}, action.resources[name]);

    if (resource.data) {
      resource.lastReceived = action.time;
    }

    if (resource.error) {
      resource.lastError = action.time;
    }

    resources[name] = resource;
    return resources;
  }, {});
  return reduceResources(state, newResources);
}
function reduceResources(state, newResources) {
  var updatedResources = Object.keys(newResources).reduce(function (resources, resourceName) {
    var resource = resources[resourceName];
    var newResource = newResources[resourceName];
    resources[resourceName] = _objectSpread({}, resource, {}, newResource);
    return resources;
  }, _objectSpread({}, state.resources));
  return _objectSpread({}, state, {
    resources: updatedResources
  });
}
// CONCATENATED MODULE: ./client/wc-api/wp-data-store/create-api-client.js
/**
 * External dependencies
 */


/**
 * Internal dependencies
 */



function create_api_client_createStore(name) {
  var devTools = window.__REDUX_DEVTOOLS_EXTENSION__;
  return createStore(reducer_reducer, devTools && devTools({
    name: name,
    instanceId: name
  }));
}

function createDataHandlers(store) {
  return {
    dataRequested: function dataRequested(resourceNames) {
      // This is a temporary fix until it can be resolved upstream in fresh-data.
      // See: https://github.com/woocommerce/woocommerce-admin/pull/2387/files#r292355276
      if (document.hidden) {
        return;
      }

      store.dispatch({
        type: 'FRESH_DATA_REQUESTED',
        resourceNames: resourceNames,
        time: new Date()
      });
    },
    dataReceived: function dataReceived(resources) {
      store.dispatch({
        type: 'FRESH_DATA_RECEIVED',
        resources: resources,
        time: new Date()
      });
    }
  };
}

function createApiClient(name, apiSpec) {
  var store = create_api_client_createStore(name);
  var dataHandlers = createDataHandlers(store);
  var apiClient = new es["a" /* ApiClient */](apiSpec);
  apiClient.setDataHandlers(dataHandlers);

  var storeChanged = function storeChanged() {
    apiClient.setState(store.getState());
  };

  store.subscribe(storeChanged);
  return apiClient;
}

/* harmony default export */ var create_api_client = (createApiClient);
// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/toConsumableArray.js
var toConsumableArray = __webpack_require__(32);
var toConsumableArray_default = /*#__PURE__*/__webpack_require__.n(toConsumableArray);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/asyncToGenerator.js
var asyncToGenerator = __webpack_require__(46);
var asyncToGenerator_default = /*#__PURE__*/__webpack_require__.n(asyncToGenerator);

// EXTERNAL MODULE: external {"this":["wp","i18n"]}
var external_this_wp_i18n_ = __webpack_require__(3);

// CONCATENATED MODULE: ./client/wc-api/utils.js
function getResourceName(prefix, identifier) {
  var identifierString = JSON.stringify(identifier, Object.keys(identifier).sort());
  return "".concat(prefix, ":").concat(identifierString);
}
function getResourcePrefix(resourceName) {
  return resourceName.substring(0, resourceName.indexOf(':'));
}
function isResourcePrefix(resourceName, prefix) {
  var resourcePrefix = getResourcePrefix(resourceName);
  return resourcePrefix === prefix;
}
function getResourceIdentifier(resourceName) {
  var identifierString = resourceName.substring(resourceName.indexOf(':') + 1);
  return JSON.parse(identifierString);
}
// CONCATENATED MODULE: ./client/wc-api/export/mutations.js



/**
 * External dependencies
 */


/**
 * Internal dependencies
 */



var mutations_initiateReportExport = function initiateReportExport(operations) {
  return /*#__PURE__*/function () {
    var _ref = asyncToGenerator_default()( /*#__PURE__*/regeneratorRuntime.mark(function _callee(reportType, reportTitle, reportArgs) {
      var _dispatch, createNotice, resourceName, result, response;

      return regeneratorRuntime.wrap(function _callee$(_context) {
        while (1) {
          switch (_context.prev = _context.next) {
            case 0:
              _dispatch = Object(external_this_wp_data_["dispatch"])('core/notices'), createNotice = _dispatch.createNotice;
              resourceName = getResourceName("report-export-".concat(reportType), reportArgs);
              _context.next = 4;
              return operations.update([resourceName], defineProperty_default()({}, resourceName, reportArgs));

            case 4:
              result = _context.sent;
              response = result[0][resourceName];

              if (response && response.success) {
                createNotice('success', Object(external_this_wp_i18n_["sprintf"])(Object(external_this_wp_i18n_["__"])('Your %s Report will be emailed to you.', 'woocommerce'), reportTitle));
              }

              if (response && response.error) {
                createNotice('error', Object(external_this_wp_i18n_["sprintf"])(Object(external_this_wp_i18n_["__"])('There was a problem exporting your %s Report. Please try again.', 'woocommerce'), reportTitle));
              }

            case 8:
            case "end":
              return _context.stop();
          }
        }
      }, _callee);
    }));

    return function (_x, _x2, _x3) {
      return _ref.apply(this, arguments);
    };
  }();
};

/* harmony default export */ var mutations = ({
  initiateReportExport: mutations_initiateReportExport
});
// EXTERNAL MODULE: external {"this":["wp","apiFetch"]}
var external_this_wp_apiFetch_ = __webpack_require__(20);
var external_this_wp_apiFetch_default = /*#__PURE__*/__webpack_require__.n(external_this_wp_apiFetch_);

// EXTERNAL MODULE: ./client/wc-api/constants.js
var constants = __webpack_require__(24);

// CONCATENATED MODULE: ./client/wc-api/export/operations.js




/**
 * External dependencies
 */

/**
 * Internal dependencies
 */




function operations_update(resourceNames, data) {
  var fetch = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : external_this_wp_apiFetch_default.a;
  return toConsumableArray_default()(initiateExport(resourceNames, data, fetch));
}

function initiateExport(resourceNames, data, fetch) {
  var filteredNames = resourceNames.filter(function (name) {
    return name.startsWith('report-export-');
  });
  return filteredNames.map( /*#__PURE__*/function () {
    var _ref = asyncToGenerator_default()( /*#__PURE__*/regeneratorRuntime.mark(function _callee(resourceName) {
      var prefix, reportType, url, result;
      return regeneratorRuntime.wrap(function _callee$(_context) {
        while (1) {
          switch (_context.prev = _context.next) {
            case 0:
              prefix = getResourcePrefix(resourceName);
              reportType = prefix.split('-').pop();
              url = constants["c" /* NAMESPACE */] + '/reports/' + reportType + '/export';
              _context.prev = 3;
              _context.next = 6;
              return fetch({
                path: url,
                method: 'POST',
                data: {
                  report_args: data[resourceName],
                  email: true
                }
              });

            case 6:
              result = _context.sent;
              return _context.abrupt("return", defineProperty_default()({}, resourceName, defineProperty_default()({}, result.status, result.message)));

            case 10:
              _context.prev = 10;
              _context.t0 = _context["catch"](3);
              return _context.abrupt("return", defineProperty_default()({}, resourceName, {
                error: _context.t0
              }));

            case 13:
            case "end":
              return _context.stop();
          }
        }
      }, _callee, null, [[3, 10]]);
    }));

    return function (_x) {
      return _ref.apply(this, arguments);
    };
  }());
}

/* harmony default export */ var export_operations = ({
  update: operations_update
});
// CONCATENATED MODULE: ./client/wc-api/export/index.js
/**
 * Internal dependencies
 */


/* harmony default export */ var wc_api_export = ({
  mutations: mutations,
  operations: export_operations
});
// CONCATENATED MODULE: ./client/wc-api/items/mutations.js



function mutations_ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function mutations_objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { mutations_ownKeys(Object(source), true).forEach(function (key) { defineProperty_default()(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { mutations_ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

/**
 * External dependencies
 */


/**
 * Internal dependencies
 */



var mutations_updateProductStock = function updateProductStock(operations) {
  return /*#__PURE__*/function () {
    var _ref = asyncToGenerator_default()( /*#__PURE__*/regeneratorRuntime.mark(function _callee(product, newStock) {
      var _dispatch, createNotice, oldStockQuantity, resourceName, result, response;

      return regeneratorRuntime.wrap(function _callee$(_context) {
        while (1) {
          switch (_context.prev = _context.next) {
            case 0:
              _dispatch = Object(external_this_wp_data_["dispatch"])('core/notices'), createNotice = _dispatch.createNotice;
              oldStockQuantity = product.stock_quantity;
              resourceName = getResourceName('items-query-products-item', product.id); // Optimistically update product stock

              operations.updateLocally([resourceName], defineProperty_default()({}, resourceName, mutations_objectSpread({}, product, {
                stock_quantity: newStock
              })));
              _context.next = 6;
              return operations.update([resourceName], defineProperty_default()({}, resourceName, {
                id: product.id,
                type: product.type,
                parent_id: product.parent_id,
                stock_quantity: newStock
              }));

            case 6:
              result = _context.sent;
              response = result[0][resourceName];

              if (response && response.data) {
                createNotice('success', Object(external_this_wp_i18n_["sprintf"])(Object(external_this_wp_i18n_["__"])('%s stock updated.', 'woocommerce'), product.name));
              }

              if (response && response.error) {
                createNotice('error', Object(external_this_wp_i18n_["sprintf"])(Object(external_this_wp_i18n_["__"])('%s stock could not be updated.', 'woocommerce'), product.name)); // Revert local changes if the operation failed in the server

                operations.updateLocally([resourceName], defineProperty_default()({}, resourceName, mutations_objectSpread({}, product, {
                  stock_quantity: oldStockQuantity
                })));
              }

            case 10:
            case "end":
              return _context.stop();
          }
        }
      }, _callee);
    }));

    return function (_x, _x2) {
      return _ref.apply(this, arguments);
    };
  }();
};

/* harmony default export */ var items_mutations = ({
  updateProductStock: mutations_updateProductStock
});
// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/objectWithoutProperties.js
var objectWithoutProperties = __webpack_require__(121);
var objectWithoutProperties_default = /*#__PURE__*/__webpack_require__.n(objectWithoutProperties);

// EXTERNAL MODULE: external {"this":["wp","url"]}
var external_this_wp_url_ = __webpack_require__(30);

// CONCATENATED MODULE: ./client/wc-api/items/operations.js




function operations_ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function operations_objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { operations_ownKeys(Object(source), true).forEach(function (key) { defineProperty_default()(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { operations_ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

/**
 * External dependencies
 */


/**
 * Internal dependencies
 */



var typeEndpointMap = {
  'items-query-categories': 'products/categories',
  'items-query-customers': 'customers',
  'items-query-coupons': 'coupons',
  'items-query-leaderboards': 'leaderboards',
  'items-query-orders': 'orders',
  'items-query-products': 'products',
  'items-query-taxes': 'taxes'
};

function operations_read(resourceNames) {
  var fetch = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : external_this_wp_apiFetch_default.a;
  var filteredNames = resourceNames.filter(function (name) {
    var prefix = getResourcePrefix(name);
    return Boolean(typeEndpointMap[prefix]);
  });
  return filteredNames.map( /*#__PURE__*/function () {
    var _ref = asyncToGenerator_default()( /*#__PURE__*/regeneratorRuntime.mark(function _callee(resourceName) {
      var prefix, endpoint, query, url, isUnboundedRequest, response, items, totalCount, ids, itemResources;
      return regeneratorRuntime.wrap(function _callee$(_context) {
        while (1) {
          switch (_context.prev = _context.next) {
            case 0:
              prefix = getResourcePrefix(resourceName);
              endpoint = typeEndpointMap[prefix];
              query = getResourceIdentifier(resourceName);
              url = Object(external_this_wp_url_["addQueryArgs"])("".concat(constants["c" /* NAMESPACE */], "/").concat(endpoint), query);
              isUnboundedRequest = query.per_page === -1;
              _context.prev = 5;
              _context.next = 8;
              return fetch({
                /* eslint-disable max-len */

                /**
                 * A false parse flag allows a full response including headers which are useful
                 * to determine totalCount. However, this invalidates an unbounded request, ie
                 * `per_page: -1` by skipping middleware in apiFetch.
                 *
                 * See the Gutenberg code for more:
                 * https://github.com/WordPress/gutenberg/blob/dee3dcf49028717b4af3164e3096bfe747c41ed2/packages/api-fetch/src/middlewares/fetch-all-middleware.js#L39-L45
                 */

                /* eslint-enable max-len */
                parse: isUnboundedRequest,
                path: url
              });

            case 8:
              response = _context.sent;

              if (!isUnboundedRequest) {
                _context.next = 14;
                break;
              }

              items = response;
              totalCount = items.length;
              _context.next = 18;
              break;

            case 14:
              _context.next = 16;
              return response.json();

            case 16:
              items = _context.sent;
              totalCount = parseInt(response.headers.get('x-wp-total'), 10);

            case 18:
              ids = items.map(function (item) {
                return item.id;
              });
              itemResources = items.reduce(function (resources, item) {
                resources[getResourceName("".concat(prefix, "-item"), item.id)] = {
                  data: item
                };
                return resources;
              }, {});
              return _context.abrupt("return", operations_objectSpread(defineProperty_default()({}, resourceName, {
                data: ids,
                totalCount: totalCount
              }), itemResources));

            case 23:
              _context.prev = 23;
              _context.t0 = _context["catch"](5);
              return _context.abrupt("return", defineProperty_default()({}, resourceName, {
                error: _context.t0
              }));

            case 26:
            case "end":
              return _context.stop();
          }
        }
      }, _callee, null, [[5, 23]]);
    }));

    return function (_x) {
      return _ref.apply(this, arguments);
    };
  }());
}

function items_operations_update(resourceNames, data) {
  var fetch = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : external_this_wp_apiFetch_default.a;
  var updateableTypes = ['items-query-products-item'];
  var filteredNames = resourceNames.filter(function (name) {
    return updateableTypes.includes(getResourcePrefix(name));
  });
  return filteredNames.map( /*#__PURE__*/function () {
    var _ref3 = asyncToGenerator_default()( /*#__PURE__*/regeneratorRuntime.mark(function _callee2(resourceName) {
      var _data$resourceName, id, parentId, type, itemData, url;

      return regeneratorRuntime.wrap(function _callee2$(_context2) {
        while (1) {
          switch (_context2.prev = _context2.next) {
            case 0:
              _data$resourceName = data[resourceName], id = _data$resourceName.id, parentId = _data$resourceName.parent_id, type = _data$resourceName.type, itemData = objectWithoutProperties_default()(_data$resourceName, ["id", "parent_id", "type"]);
              url = constants["c" /* NAMESPACE */];
              _context2.t0 = type;
              _context2.next = _context2.t0 === 'variation' ? 5 : _context2.t0 === 'variable' ? 7 : _context2.t0 === 'simple' ? 7 : 7;
              break;

            case 5:
              url += "/products/".concat(parentId, "/variations/").concat(id);
              return _context2.abrupt("break", 8);

            case 7:
              url += "/products/".concat(id);

            case 8:
              return _context2.abrupt("return", fetch({
                path: url,
                method: 'PUT',
                data: itemData
              }).then(function (item) {
                return defineProperty_default()({}, resourceName, {
                  data: item
                });
              }).catch(function (error) {
                return defineProperty_default()({}, resourceName, {
                  error: error
                });
              }));

            case 9:
            case "end":
              return _context2.stop();
          }
        }
      }, _callee2);
    }));

    return function (_x2) {
      return _ref3.apply(this, arguments);
    };
  }());
}

function operations_updateLocally(resourceNames, data) {
  var updateableTypes = ['items-query-products-item'];
  var filteredNames = resourceNames.filter(function (name) {
    return updateableTypes.includes(getResourcePrefix(name));
  });
  var lowStockResourceName = getResourceName('items-query-products', {
    page: 1,
    per_page: 1,
    low_in_stock: true,
    status: 'publish'
  });
  return filteredNames.map( /*#__PURE__*/function () {
    var _ref6 = asyncToGenerator_default()( /*#__PURE__*/regeneratorRuntime.mark(function _callee3(resourceName) {
      var _ref7;

      return regeneratorRuntime.wrap(function _callee3$(_context3) {
        while (1) {
          switch (_context3.prev = _context3.next) {
            case 0:
              return _context3.abrupt("return", (_ref7 = {}, defineProperty_default()(_ref7, resourceName, {
                data: data[resourceName]
              }), defineProperty_default()(_ref7, lowStockResourceName, {
                lastReceived: null
              }), _ref7));

            case 1:
            case "end":
              return _context3.stop();
          }
        }
      }, _callee3);
    }));

    return function (_x3) {
      return _ref6.apply(this, arguments);
    };
  }());
}

/* harmony default export */ var items_operations = ({
  read: operations_read,
  update: items_operations_update,
  updateLocally: operations_updateLocally
});
// EXTERNAL MODULE: external "lodash"
var external_lodash_ = __webpack_require__(2);

// CONCATENATED MODULE: ./client/wc-api/items/selectors.js
/**
 * External dependencies
 */

/**
 * Internal dependencies
 */




var selectors_getItems = function getItems(getResource, requireResource) {
  return function (type) {
    var query = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};
    var requirement = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : constants["a" /* DEFAULT_REQUIREMENT */];
    var resourceName = getResourceName("items-query-".concat(type), query);
    var ids = requireResource(requirement, resourceName).data || [];
    var items = new Map();
    ids.forEach(function (id) {
      items.set(id, getResource(getResourceName("items-query-".concat(type, "-item"), id)).data);
    });
    return items;
  };
};

var selectors_getItemsTotalCount = function getItemsTotalCount(getResource) {
  return function (type) {
    var query = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};
    var resourceName = getResourceName("items-query-".concat(type), query);
    return getResource(resourceName).totalCount || 0;
  };
};

var selectors_getItemsError = function getItemsError(getResource) {
  return function (type) {
    var query = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};
    var resourceName = getResourceName("items-query-".concat(type), query);
    return getResource(resourceName).error;
  };
};

var selectors_isGetItemsRequesting = function isGetItemsRequesting(getResource) {
  return function (type) {
    var query = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};
    var resourceName = getResourceName("items-query-".concat(type), query);

    var _getResource = getResource(resourceName),
        lastRequested = _getResource.lastRequested,
        lastReceived = _getResource.lastReceived;

    if (Object(external_lodash_["isNil"])(lastRequested) || Object(external_lodash_["isNil"])(lastReceived)) {
      return true;
    }

    return lastRequested > lastReceived;
  };
};

/* harmony default export */ var selectors = ({
  getItems: selectors_getItems,
  getItemsError: selectors_getItemsError,
  getItemsTotalCount: selectors_getItemsTotalCount,
  isGetItemsRequesting: selectors_isGetItemsRequesting
});
// CONCATENATED MODULE: ./client/wc-api/items/index.js
/**
 * Internal dependencies
 */



/* harmony default export */ var wc_api_items = ({
  mutations: items_mutations,
  operations: items_operations,
  selectors: selectors
});
// CONCATENATED MODULE: ./client/wc-api/imports/operations.js



/**
 * External dependencies
 */



/**
 * Internal dependencies
 */



var operations_typeEndpointMap = {
  'import-status': 'reports/import/status',
  'import-totals': 'reports/import/totals'
};

function imports_operations_read(resourceNames) {
  var fetch = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : external_this_wp_apiFetch_default.a;
  var filteredNames = resourceNames.filter(function (name) {
    var prefix = getResourcePrefix(name);
    return Boolean(operations_typeEndpointMap[prefix]);
  });
  return filteredNames.map( /*#__PURE__*/function () {
    var _ref = asyncToGenerator_default()( /*#__PURE__*/regeneratorRuntime.mark(function _callee(resourceName) {
      var prefix, endpoint, query, fetchArgs, response, data;
      return regeneratorRuntime.wrap(function _callee$(_context) {
        while (1) {
          switch (_context.prev = _context.next) {
            case 0:
              prefix = getResourcePrefix(resourceName);
              endpoint = operations_typeEndpointMap[prefix];
              query = getResourceIdentifier(resourceName);
              fetchArgs = {
                parse: false,
                path: Object(external_this_wp_url_["addQueryArgs"])("".concat(constants["c" /* NAMESPACE */], "/").concat(endpoint), Object(external_lodash_["omit"])(query, ['timestamp']))
              };
              _context.prev = 4;
              _context.next = 7;
              return fetch(fetchArgs);

            case 7:
              response = _context.sent;
              _context.next = 10;
              return response.json();

            case 10:
              data = _context.sent;
              return _context.abrupt("return", defineProperty_default()({}, resourceName, {
                data: data
              }));

            case 14:
              _context.prev = 14;
              _context.t0 = _context["catch"](4);
              return _context.abrupt("return", defineProperty_default()({}, resourceName, {
                error: _context.t0
              }));

            case 17:
            case "end":
              return _context.stop();
          }
        }
      }, _callee, null, [[4, 14]]);
    }));

    return function (_x) {
      return _ref.apply(this, arguments);
    };
  }());
}

/* harmony default export */ var imports_operations = ({
  read: imports_operations_read
});
// CONCATENATED MODULE: ./client/wc-api/imports/selectors.js


function selectors_ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function selectors_objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { selectors_ownKeys(Object(source), true).forEach(function (key) { defineProperty_default()(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { selectors_ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

/**
 * External dependencies
 */

/**
 * Internal dependencies
 */




var selectors_getImportStatus = function getImportStatus(getResource, requireResource) {
  return function (timestamp) {
    var requirement = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : constants["a" /* DEFAULT_REQUIREMENT */];
    var resourceName = getResourceName('import-status', timestamp);
    return requireResource(requirement, resourceName).data || {};
  };
};

var selectors_isGetImportStatusRequesting = function isGetImportStatusRequesting(getResource) {
  return function (timestamp) {
    var resourceName = getResourceName('import-status', timestamp);

    var _getResource = getResource(resourceName),
        lastRequested = _getResource.lastRequested,
        lastReceived = _getResource.lastReceived;

    if (Object(external_lodash_["isNil"])(lastRequested) || Object(external_lodash_["isNil"])(lastReceived)) {
      return true;
    }

    return lastRequested > lastReceived;
  };
};

var selectors_getImportTotals = function getImportTotals(getResource, requireResource) {
  return function () {
    var query = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
    var timestamp = arguments.length > 1 ? arguments[1] : undefined;
    var requirement = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : constants["a" /* DEFAULT_REQUIREMENT */];

    var identifier = selectors_objectSpread({}, query, {
      timestamp: timestamp
    });

    var resourceName = getResourceName('import-totals', identifier);
    return requireResource(requirement, resourceName).data || {};
  };
};

/* harmony default export */ var imports_selectors = ({
  getImportStatus: selectors_getImportStatus,
  isGetImportStatusRequesting: selectors_isGetImportStatusRequesting,
  getImportTotals: selectors_getImportTotals
});
// CONCATENATED MODULE: ./client/wc-api/imports/index.js
/**
 * Internal dependencies
 */


/* harmony default export */ var imports = ({
  operations: imports_operations,
  selectors: imports_selectors
});
// CONCATENATED MODULE: ./client/wc-api/notes/operations.js





function notes_operations_ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function notes_operations_objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { notes_operations_ownKeys(Object(source), true).forEach(function (key) { defineProperty_default()(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { notes_operations_ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

/**
 * External dependencies
 */


/**
 * Internal dependencies
 */




function notes_operations_read(resourceNames) {
  var fetch = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : external_this_wp_apiFetch_default.a;
  return [].concat(toConsumableArray_default()(readNotes(resourceNames, fetch)), toConsumableArray_default()(readNoteQueries(resourceNames, fetch)));
}

function notes_operations_update(resourceNames, data) {
  var fetch = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : external_this_wp_apiFetch_default.a;
  return [].concat(toConsumableArray_default()(operations_updateNote(resourceNames, data, fetch)), toConsumableArray_default()(triggerAction(resourceNames, data, fetch)));
}

function readNoteQueries(resourceNames, fetch) {
  var filteredNames = resourceNames.filter(function (name) {
    return isResourcePrefix(name, 'note-query');
  });
  return filteredNames.map( /*#__PURE__*/function () {
    var _ref = asyncToGenerator_default()( /*#__PURE__*/regeneratorRuntime.mark(function _callee(resourceName) {
      var query, url, response, notes, totalCount, ids, noteResources;
      return regeneratorRuntime.wrap(function _callee$(_context) {
        while (1) {
          switch (_context.prev = _context.next) {
            case 0:
              query = getResourceIdentifier(resourceName);
              url = Object(external_this_wp_url_["addQueryArgs"])("".concat(constants["c" /* NAMESPACE */], "/admin/notes"), query);
              _context.prev = 2;
              _context.next = 5;
              return fetch({
                parse: false,
                path: url
              });

            case 5:
              response = _context.sent;
              _context.next = 8;
              return response.json();

            case 8:
              notes = _context.sent;
              totalCount = parseInt(response.headers.get('x-wp-total'), 10);
              ids = notes.map(function (note) {
                return note.id;
              });
              noteResources = notes.reduce(function (resources, note) {
                resources[getResourceName('note', note.id)] = {
                  data: note
                };
                return resources;
              }, {});
              return _context.abrupt("return", notes_operations_objectSpread(defineProperty_default()({}, resourceName, {
                data: ids,
                totalCount: totalCount
              }), noteResources));

            case 15:
              _context.prev = 15;
              _context.t0 = _context["catch"](2);
              return _context.abrupt("return", defineProperty_default()({}, resourceName, {
                error: _context.t0
              }));

            case 18:
            case "end":
              return _context.stop();
          }
        }
      }, _callee, null, [[2, 15]]);
    }));

    return function (_x) {
      return _ref.apply(this, arguments);
    };
  }());
}

function readNotes(resourceNames, fetch) {
  var filteredNames = resourceNames.filter(function (name) {
    return isResourcePrefix(name, 'note');
  });
  return filteredNames.map(function (resourceName) {
    return readNote(resourceName, fetch);
  });
}

function readNote(resourceName, fetch) {
  var id = getResourceIdentifier(resourceName);
  var url = "".concat(constants["c" /* NAMESPACE */], "/admin/notes/").concat(id);
  return fetch({
    path: url
  }).then(function (note) {
    return defineProperty_default()({}, resourceName, {
      data: note
    });
  }).catch(function (error) {
    return defineProperty_default()({}, resourceName, {
      error: error
    });
  });
}

function operations_updateNote(resourceNames, data, fetch) {
  var resourceName = 'note';

  if (resourceNames.includes(resourceName)) {
    var _data$resourceName = data[resourceName],
        noteId = _data$resourceName.noteId,
        noteFields = objectWithoutProperties_default()(_data$resourceName, ["noteId"]);

    var url = "".concat(constants["c" /* NAMESPACE */], "/admin/notes/").concat(noteId);
    return [fetch({
      path: url,
      method: 'PUT',
      data: noteFields
    }).then(function (note) {
      return defineProperty_default()({}, resourceName + ':' + noteId, {
        data: note
      });
    }).catch(function (error) {
      return defineProperty_default()({}, resourceName + ':' + noteId, {
        error: error
      });
    })];
  }

  return [];
}

function triggerAction(resourceNames, data, fetch) {
  var resourceName = 'note-action';

  if (resourceNames.includes(resourceName)) {
    var _data$resourceName2 = data[resourceName],
        noteId = _data$resourceName2.noteId,
        actionId = _data$resourceName2.actionId;
    var url = "".concat(constants["c" /* NAMESPACE */], "/admin/notes/").concat(noteId, "/action/").concat(actionId);
    return [fetch({
      path: url,
      method: 'POST'
    }).then(function (note) {
      return defineProperty_default()({}, 'note:' + noteId, {
        data: note
      });
    }).catch(function (error) {
      return defineProperty_default()({}, 'note:' + noteId, {
        error: error
      });
    })];
  }

  return [];
}

/* harmony default export */ var notes_operations = ({
  read: notes_operations_read,
  update: notes_operations_update,
  triggerAction: triggerAction
});
// CONCATENATED MODULE: ./client/wc-api/notes/selectors.js
/**
 * External dependencies
 */

/**
 * Internal dependencies
 */




var selectors_getNotes = function getNotes(getResource, requireResource) {
  return function () {
    var query = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
    var requirement = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : constants["a" /* DEFAULT_REQUIREMENT */];
    var resourceName = getResourceName('note-query', query);
    var ids = requireResource(requirement, resourceName).data || [];
    var notes = ids.map(function (id) {
      return getResource(getResourceName('note', id)).data || {};
    });
    return notes;
  };
};

var selectors_getNotesError = function getNotesError(getResource) {
  return function () {
    var query = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
    var resourceName = getResourceName('note-query', query);
    return getResource(resourceName).error;
  };
};

var selectors_isGetNotesRequesting = function isGetNotesRequesting(getResource) {
  return function () {
    var query = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
    var resourceName = getResourceName('note-query', query);

    var _getResource = getResource(resourceName),
        lastRequested = _getResource.lastRequested,
        lastReceived = _getResource.lastReceived;

    if (Object(external_lodash_["isNil"])(lastRequested) || Object(external_lodash_["isNil"])(lastReceived)) {
      return true;
    }

    return lastRequested > lastReceived;
  };
};

/* harmony default export */ var notes_selectors = ({
  getNotes: selectors_getNotes,
  getNotesError: selectors_getNotesError,
  isGetNotesRequesting: selectors_isGetNotesRequesting
});
// CONCATENATED MODULE: ./client/wc-api/notes/mutations.js


function notes_mutations_ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function notes_mutations_objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { notes_mutations_ownKeys(Object(source), true).forEach(function (key) { defineProperty_default()(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { notes_mutations_ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

var mutations_updateNote = function updateNote(operations) {
  return function (noteId, noteFields) {
    var resourceKey = 'note';
    operations.update([resourceKey], defineProperty_default()({}, resourceKey, notes_mutations_objectSpread({
      noteId: noteId
    }, noteFields)));
  };
};

var mutations_triggerNoteAction = function triggerNoteAction(operations) {
  return function (noteId, actionId) {
    var resourceKey = 'note-action';
    operations.update([resourceKey], defineProperty_default()({}, resourceKey, {
      noteId: noteId,
      actionId: actionId
    }));
  };
};

/* harmony default export */ var notes_mutations = ({
  updateNote: mutations_updateNote,
  triggerNoteAction: mutations_triggerNoteAction
});
// CONCATENATED MODULE: ./client/wc-api/notes/index.js
/**
 * Internal dependencies
 */



/* harmony default export */ var wc_api_notes = ({
  operations: notes_operations,
  selectors: notes_selectors,
  mutations: notes_mutations
});
// CONCATENATED MODULE: ./client/wc-api/onboarding/operations.js



function onboarding_operations_ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function onboarding_operations_objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { onboarding_operations_ownKeys(Object(source), true).forEach(function (key) { defineProperty_default()(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { onboarding_operations_ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

/**
 * External dependencies
 */

/**
 * Internal dependencies
 */




function onboarding_operations_read(resourceNames) {
  var fetch = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : external_this_wp_apiFetch_default.a;
  return toConsumableArray_default()(readProfileItems(resourceNames, fetch));
}

function onboarding_operations_update(resourceNames, data) {
  var fetch = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : external_this_wp_apiFetch_default.a;
  return toConsumableArray_default()(operations_updateProfileItems(resourceNames, data, fetch));
}

function readProfileItems(resourceNames, fetch) {
  var resourceName = 'onboarding-profile';

  if (resourceNames.includes(resourceName)) {
    var url = constants["f" /* WC_ADMIN_NAMESPACE */] + '/onboarding/profile';
    return [fetch({
      path: url
    }).then(profileItemsToResources).catch(function (error) {
      return defineProperty_default()({}, resourceName, {
        error: String(error.message)
      });
    })];
  }

  return [];
}

function operations_updateProfileItems(resourceNames, data, fetch) {
  var resourceName = 'onboarding-profile';

  if (resourceNames.includes(resourceName)) {
    var url = constants["f" /* WC_ADMIN_NAMESPACE */] + '/onboarding/profile';
    return [fetch({
      path: url,
      method: 'POST',
      data: data[resourceName]
    }).then(profileItemToResource.bind(null, data[resourceName])).catch(function (error) {
      return defineProperty_default()({}, resourceName, {
        error: error
      });
    })];
  }

  return [];
}

function profileItemsToResources(items) {
  var resourceName = 'onboarding-profile';
  var itemKeys = Object.keys(items);
  var resources = {};
  itemKeys.forEach(function (key) {
    var item = items[key];
    resources[getResourceName(resourceName, key)] = {
      data: item
    };
  });
  return onboarding_operations_objectSpread(defineProperty_default()({}, resourceName, {
    data: itemKeys
  }), resources);
}

function profileItemToResource(items) {
  var resourceName = 'onboarding-profile';
  var resources = {};
  Object.keys(items).forEach(function (key) {
    var item = items[key];
    resources[getResourceName(resourceName, key)] = {
      data: item
    };
  });
  return onboarding_operations_objectSpread(defineProperty_default()({}, resourceName, {
    lastReceived: Date.now()
  }), resources);
}

/* harmony default export */ var onboarding_operations = ({
  read: onboarding_operations_read,
  update: onboarding_operations_update
});
// EXTERNAL MODULE: ./client/settings/index.js
var settings = __webpack_require__(26);

// CONCATENATED MODULE: ./client/wc-api/onboarding/selectors.js
/**
 * External dependencies
 */

/**
 * WooCommerce dependencies
 */


/**
 * Internal dependencies
 */




var selectors_getProfileItems = function getProfileItems(getResource, requireResource) {
  return function () {
    var requirement = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : constants["a" /* DEFAULT_REQUIREMENT */];
    var resourceName = 'onboarding-profile';
    var ids = requireResource(requirement, resourceName).data || [];

    var _getSetting = Object(settings["g" /* getSetting */])('onboarding', {}),
        profile = _getSetting.profile;

    if (!ids.length) {
      var data = {};
      Object.keys(profile).forEach(function (id) {
        data[id] = getResource(getResourceName(resourceName, id)).data || profile[id];
      });
      return data;
    }

    var items = {};
    ids.forEach(function (id) {
      items[id] = getResource(getResourceName(resourceName, id)).data;
    });
    return items;
  };
};

var getProfileItemsError = function getProfileItemsError(getResource) {
  return function () {
    return getResource('onboarding-profile').error;
  };
};

var selectors_isGetProfileItemsRequesting = function isGetProfileItemsRequesting(getResource) {
  return function () {
    var _getResource = getResource('onboarding-profile'),
        lastReceived = _getResource.lastReceived,
        lastRequested = _getResource.lastRequested;

    if (Object(external_lodash_["isNil"])(lastRequested) || Object(external_lodash_["isNil"])(lastReceived)) {
      return true;
    }

    return lastRequested > lastReceived;
  };
};

/* harmony default export */ var onboarding_selectors = ({
  getProfileItems: selectors_getProfileItems,
  getProfileItemsError: getProfileItemsError,
  isGetProfileItemsRequesting: selectors_isGetProfileItemsRequesting
});
// CONCATENATED MODULE: ./client/wc-api/onboarding/mutations.js


var mutations_updateProfileItems = function updateProfileItems(operations) {
  return function (fields) {
    var resourceKey = 'onboarding-profile';
    operations.update([resourceKey], defineProperty_default()({}, resourceKey, fields));
  };
};

/* harmony default export */ var onboarding_mutations = ({
  updateProfileItems: mutations_updateProfileItems
});
// CONCATENATED MODULE: ./client/wc-api/onboarding/index.js
/**
 * Internal dependencies
 */



/* harmony default export */ var onboarding = ({
  operations: onboarding_operations,
  selectors: onboarding_selectors,
  mutations: onboarding_mutations
});
// CONCATENATED MODULE: ./client/wc-api/options/operations.js




function options_operations_ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function options_operations_objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { options_operations_ownKeys(Object(source), true).forEach(function (key) { defineProperty_default()(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { options_operations_ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

/**
 * External dependencies
 */

/**
 * Internal dependencies
 */




function options_operations_read(resourceNames) {
  var fetch = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : external_this_wp_apiFetch_default.a;
  return toConsumableArray_default()(readOptions(resourceNames, fetch));
}

function options_operations_update(resourceNames, data) {
  var fetch = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : external_this_wp_apiFetch_default.a;
  return toConsumableArray_default()(operations_updateOptions(resourceNames, data, fetch));
}

function readOptions(resourceNames, fetch) {
  var filteredNames = resourceNames.filter(function (name) {
    return name.startsWith('options');
  });
  return filteredNames.map( /*#__PURE__*/function () {
    var _ref = asyncToGenerator_default()( /*#__PURE__*/regeneratorRuntime.mark(function _callee(resourceName) {
      var optionNames, url;
      return regeneratorRuntime.wrap(function _callee$(_context) {
        while (1) {
          switch (_context.prev = _context.next) {
            case 0:
              optionNames = getResourceIdentifier(resourceName);
              url = constants["f" /* WC_ADMIN_NAMESPACE */] + '/options?options=' + optionNames.join(',');
              return _context.abrupt("return", fetch({
                path: url
              }).then(optionsToResource).catch(function (error) {
                return defineProperty_default()({}, resourceName, {
                  error: String(error.message)
                });
              }));

            case 3:
            case "end":
              return _context.stop();
          }
        }
      }, _callee);
    }));

    return function (_x) {
      return _ref.apply(this, arguments);
    };
  }());
}

function operations_updateOptions(resourceNames, data, fetch) {
  var url = constants["f" /* WC_ADMIN_NAMESPACE */] + '/options';
  var filteredNames = resourceNames.filter(function (name) {
    return name.startsWith('options-update');
  });
  return filteredNames.map( /*#__PURE__*/function () {
    var _ref3 = asyncToGenerator_default()( /*#__PURE__*/regeneratorRuntime.mark(function _callee2(resourceName) {
      return regeneratorRuntime.wrap(function _callee2$(_context2) {
        while (1) {
          switch (_context2.prev = _context2.next) {
            case 0:
              return _context2.abrupt("return", fetch({
                path: url,
                method: 'POST',
                data: data[resourceName]
              }).then(function () {
                return optionsToResource(data[resourceName], true);
              }).catch(function (error) {
                return defineProperty_default()({}, resourceName, {
                  data: {},
                  error: error
                });
              }));

            case 1:
            case "end":
              return _context2.stop();
          }
        }
      }, _callee2);
    }));

    return function (_x2) {
      return _ref3.apply(this, arguments);
    };
  }());
}

function optionsToResource(options) {
  var updateResource = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : false;
  var optionNames = Object.keys(options);
  var resourceName = getResourceName(updateResource ? 'options-update' : 'options', optionNames);
  var resources = {};
  optionNames.forEach(function (optionName) {
    return resources[getResourceName('options', optionName)] = {
      data: options[optionName]
    };
  });
  return options_operations_objectSpread(defineProperty_default()({}, resourceName, {
    data: optionNames
  }), resources);
}

/* harmony default export */ var options_operations = ({
  read: options_operations_read,
  update: options_operations_update
});
// CONCATENATED MODULE: ./client/wc-api/options/selectors.js
/**
 * External dependencies
 */

/**
 * WooCommerce dependencies
 */


/**
 * Internal dependencies
 */




var selectors_getOptions = function getOptions(getResource, requireResource) {
  return function (optionNames) {
    var requirement = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : constants["a" /* DEFAULT_REQUIREMENT */];
    var resourceName = getResourceName('options', optionNames);
    var options = {};
    var names = requireResource(requirement, resourceName).data || optionNames;
    names.forEach(function (name) {
      var data = Object(settings["g" /* getSetting */])('preloadOptions', {}, function (po) {
        return getResource(getResourceName('options', name)).data || po[name];
      });

      if (data) {
        options[name] = data;
      }
    });
    return options;
  };
};

var selectors_getOptionsError = function getOptionsError(getResource) {
  return function (optionNames) {
    return getResource(getResourceName('options', optionNames)).error;
  };
};

var selectors_getUpdateOptionsError = function getUpdateOptionsError(getResource) {
  return function (optionNames) {
    return getResource(getResourceName('options-update', optionNames)).error;
  };
};

var selectors_isGetOptionsRequesting = function isGetOptionsRequesting(getResource) {
  return function (optionNames) {
    var _getResource = getResource(getResourceName('options', optionNames)),
        lastReceived = _getResource.lastReceived,
        lastRequested = _getResource.lastRequested;

    if (!Object(external_lodash_["isNil"])(lastRequested) && Object(external_lodash_["isNil"])(lastReceived)) {
      return true;
    }

    return lastRequested > lastReceived;
  };
};

var selectors_isUpdateOptionsRequesting = function isUpdateOptionsRequesting(getResource) {
  return function (optionNames) {
    var _getResource2 = getResource(getResourceName('options-update', optionNames)),
        lastReceived = _getResource2.lastReceived,
        lastRequested = _getResource2.lastRequested;

    if (!Object(external_lodash_["isNil"])(lastRequested) && Object(external_lodash_["isNil"])(lastReceived)) {
      return true;
    }

    return lastRequested > lastReceived;
  };
};

/* harmony default export */ var options_selectors = ({
  getOptions: selectors_getOptions,
  getOptionsError: selectors_getOptionsError,
  getUpdateOptionsError: selectors_getUpdateOptionsError,
  isGetOptionsRequesting: selectors_isGetOptionsRequesting,
  isUpdateOptionsRequesting: selectors_isUpdateOptionsRequesting
});
// CONCATENATED MODULE: ./client/wc-api/options/mutations.js


/**
 * Internal dependencies
 */


var mutations_updateOptions = function updateOptions(operations) {
  return function (options) {
    var resourceName = getResourceName('options-update', Object.keys(options));
    operations.update([resourceName], defineProperty_default()({}, resourceName, options));
  };
};

/* harmony default export */ var options_mutations = ({
  updateOptions: mutations_updateOptions
});
// CONCATENATED MODULE: ./client/wc-api/options/index.js
/**
 * Internal dependencies
 */



/* harmony default export */ var wc_api_options = ({
  operations: options_operations,
  selectors: options_selectors,
  mutations: options_mutations
});
// CONCATENATED MODULE: ./client/wc-api/reports/items/operations.js



/**
 * External dependencies
 */


/**
 * Internal dependencies
 */



var items_operations_typeEndpointMap = {
  'report-items-query-orders': 'orders',
  'report-items-query-revenue': 'revenue',
  'report-items-query-products': 'products',
  'report-items-query-categories': 'categories',
  'report-items-query-coupons': 'coupons',
  'report-items-query-taxes': 'taxes',
  'report-items-query-variations': 'variations',
  'report-items-query-downloads': 'downloads',
  'report-items-query-customers': 'customers',
  'report-items-query-stock': 'stock',
  'report-items-query-performance-indicators': 'performance-indicators'
};

function items_operations_read(resourceNames) {
  var fetch = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : external_this_wp_apiFetch_default.a;
  var filteredNames = resourceNames.filter(function (name) {
    var prefix = getResourcePrefix(name);
    return Boolean(items_operations_typeEndpointMap[prefix]);
  });
  return filteredNames.map( /*#__PURE__*/function () {
    var _ref = asyncToGenerator_default()( /*#__PURE__*/regeneratorRuntime.mark(function _callee(resourceName) {
      var prefix, endpoint, query, fetchArgs, response, report, totalResults, totalPages;
      return regeneratorRuntime.wrap(function _callee$(_context) {
        while (1) {
          switch (_context.prev = _context.next) {
            case 0:
              prefix = getResourcePrefix(resourceName);
              endpoint = items_operations_typeEndpointMap[prefix];
              query = getResourceIdentifier(resourceName);
              fetchArgs = {
                parse: false,
                path: Object(external_this_wp_url_["addQueryArgs"])("".concat(constants["c" /* NAMESPACE */], "/reports/").concat(endpoint), query)
              };
              _context.prev = 4;
              _context.next = 7;
              return fetch(fetchArgs);

            case 7:
              response = _context.sent;
              _context.next = 10;
              return response.json();

            case 10:
              report = _context.sent;
              totalResults = parseInt(response.headers.get('x-wp-total'), 10);
              totalPages = parseInt(response.headers.get('x-wp-totalpages'), 10);
              return _context.abrupt("return", defineProperty_default()({}, resourceName, {
                data: report,
                totalResults: totalResults,
                totalPages: totalPages
              }));

            case 16:
              _context.prev = 16;
              _context.t0 = _context["catch"](4);
              return _context.abrupt("return", defineProperty_default()({}, resourceName, {
                error: _context.t0
              }));

            case 19:
            case "end":
              return _context.stop();
          }
        }
      }, _callee, null, [[4, 16]]);
    }));

    return function (_x) {
      return _ref.apply(this, arguments);
    };
  }());
}

/* harmony default export */ var reports_items_operations = ({
  read: items_operations_read
});
// CONCATENATED MODULE: ./client/wc-api/reports/items/selectors.js
/**
 * External dependencies
 */

/**
 * Internal dependencies
 */




var selectors_getReportItems = function getReportItems(getResource, requireResource) {
  return function (type) {
    var query = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};
    var requirement = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : constants["a" /* DEFAULT_REQUIREMENT */];
    var resourceName = getResourceName("report-items-query-".concat(type), query);
    return requireResource(requirement, resourceName) || {};
  };
};

var selectors_getReportItemsError = function getReportItemsError(getResource) {
  return function (type) {
    var query = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};
    var resourceName = getResourceName("report-items-query-".concat(type), query);
    return getResource(resourceName).error;
  };
};

var selectors_isReportItemsRequesting = function isReportItemsRequesting(getResource) {
  return function (type) {
    var query = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};
    var resourceName = getResourceName("report-items-query-".concat(type), query);

    var _getResource = getResource(resourceName),
        lastRequested = _getResource.lastRequested,
        lastReceived = _getResource.lastReceived;

    if (Object(external_lodash_["isNil"])(lastRequested) || Object(external_lodash_["isNil"])(lastReceived)) {
      return true;
    }

    return lastRequested > lastReceived;
  };
};

/* harmony default export */ var items_selectors = ({
  getReportItems: selectors_getReportItems,
  getReportItemsError: selectors_getReportItemsError,
  isReportItemsRequesting: selectors_isReportItemsRequesting
});
// CONCATENATED MODULE: ./client/wc-api/reports/items/index.js
/**
 * Internal dependencies
 */


/* harmony default export */ var reports_items = ({
  operations: reports_items_operations,
  selectors: items_selectors
});
// CONCATENATED MODULE: ./client/wc-api/reports/stats/operations.js



/**
 * External dependencies
 */


/**
 * Internal dependencies
 */



var statEndpoints = ['coupons', 'downloads', 'orders', 'products', 'revenue', 'stock', 'taxes', 'customers'];
var stats_operations_typeEndpointMap = {
  'report-stats-query-orders': 'orders',
  'report-stats-query-revenue': 'revenue',
  'report-stats-query-products': 'products',
  'report-stats-query-categories': 'categories',
  'report-stats-query-downloads': 'downloads',
  'report-stats-query-coupons': 'coupons',
  'report-stats-query-stock': 'stock',
  'report-stats-query-taxes': 'taxes',
  'report-stats-query-customers': 'customers'
};

function stats_operations_read(resourceNames) {
  var fetch = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : external_this_wp_apiFetch_default.a;
  var filteredNames = resourceNames.filter(function (name) {
    var prefix = getResourcePrefix(name);
    return Boolean(stats_operations_typeEndpointMap[prefix]);
  });
  return filteredNames.map( /*#__PURE__*/function () {
    var _ref = asyncToGenerator_default()( /*#__PURE__*/regeneratorRuntime.mark(function _callee(resourceName) {
      var prefix, endpoint, query, fetchArgs, response, report, totalResults, totalPages;
      return regeneratorRuntime.wrap(function _callee$(_context) {
        while (1) {
          switch (_context.prev = _context.next) {
            case 0:
              prefix = getResourcePrefix(resourceName);
              endpoint = stats_operations_typeEndpointMap[prefix];
              query = getResourceIdentifier(resourceName);
              fetchArgs = {
                parse: false
              };

              if (statEndpoints.indexOf(endpoint) >= 0) {
                fetchArgs.path = Object(external_this_wp_url_["addQueryArgs"])("".concat(constants["c" /* NAMESPACE */], "/reports/").concat(endpoint, "/stats"), query);
              } else {
                fetchArgs.path = Object(external_this_wp_url_["addQueryArgs"])(endpoint, query);
              }

              _context.prev = 5;
              _context.next = 8;
              return fetch(fetchArgs);

            case 8:
              response = _context.sent;
              _context.next = 11;
              return response.json();

            case 11:
              report = _context.sent;
              totalResults = parseInt(response.headers.get('x-wp-total'), 10);
              totalPages = parseInt(response.headers.get('x-wp-totalpages'), 10);
              return _context.abrupt("return", defineProperty_default()({}, resourceName, {
                data: report,
                totalResults: totalResults,
                totalPages: totalPages
              }));

            case 17:
              _context.prev = 17;
              _context.t0 = _context["catch"](5);
              return _context.abrupt("return", defineProperty_default()({}, resourceName, {
                error: _context.t0
              }));

            case 20:
            case "end":
              return _context.stop();
          }
        }
      }, _callee, null, [[5, 17]]);
    }));

    return function (_x) {
      return _ref.apply(this, arguments);
    };
  }());
}

/* harmony default export */ var stats_operations = ({
  read: stats_operations_read
});
// CONCATENATED MODULE: ./client/wc-api/reports/stats/selectors.js
/**
 * External dependencies
 */

/**
 * Internal dependencies
 */




var selectors_getReportStats = function getReportStats(getResource, requireResource) {
  return function (type) {
    var query = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};
    var requirement = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : constants["a" /* DEFAULT_REQUIREMENT */];
    var resourceName = getResourceName("report-stats-query-".concat(type), query);
    var data = requireResource(requirement, resourceName) || {};
    return data;
  };
};

var selectors_getReportStatsError = function getReportStatsError(getResource) {
  return function (type) {
    var query = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};
    var resourceName = getResourceName("report-stats-query-".concat(type), query);
    return getResource(resourceName).error;
  };
};

var selectors_isReportStatsRequesting = function isReportStatsRequesting(getResource) {
  return function (type) {
    var query = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};
    var resourceName = getResourceName("report-stats-query-".concat(type), query);

    var _getResource = getResource(resourceName),
        lastRequested = _getResource.lastRequested,
        lastReceived = _getResource.lastReceived;

    if (Object(external_lodash_["isNil"])(lastRequested) || Object(external_lodash_["isNil"])(lastReceived)) {
      return true;
    }

    return lastRequested > lastReceived;
  };
};

/* harmony default export */ var stats_selectors = ({
  getReportStats: selectors_getReportStats,
  getReportStatsError: selectors_getReportStatsError,
  isReportStatsRequesting: selectors_isReportStatsRequesting
});
// CONCATENATED MODULE: ./client/wc-api/reports/stats/index.js
/**
 * Internal dependencies
 */


/* harmony default export */ var stats = ({
  operations: stats_operations,
  selectors: stats_selectors
});
// CONCATENATED MODULE: ./client/wc-api/reviews/operations.js




function reviews_operations_ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function reviews_operations_objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { reviews_operations_ownKeys(Object(source), true).forEach(function (key) { defineProperty_default()(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { reviews_operations_ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

/**
 * External dependencies
 */


/**
 * Internal dependencies
 */




function reviews_operations_read(resourceNames) {
  var fetch = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : external_this_wp_apiFetch_default.a;
  return [].concat(toConsumableArray_default()(readReviews(resourceNames, fetch)), toConsumableArray_default()(readReviewQueries(resourceNames, fetch)));
}

function readReviewQueries(resourceNames, fetch) {
  var filteredNames = resourceNames.filter(function (name) {
    return isResourcePrefix(name, 'review-query');
  });
  return filteredNames.map( /*#__PURE__*/function () {
    var _ref = asyncToGenerator_default()( /*#__PURE__*/regeneratorRuntime.mark(function _callee(resourceName) {
      var query, url, response, reviews, totalCount, ids, reviewResources;
      return regeneratorRuntime.wrap(function _callee$(_context) {
        while (1) {
          switch (_context.prev = _context.next) {
            case 0:
              query = getResourceIdentifier(resourceName);
              url = Object(external_this_wp_url_["addQueryArgs"])("".concat(constants["c" /* NAMESPACE */], "/products/reviews"), query);
              _context.prev = 2;
              _context.next = 5;
              return fetch({
                parse: false,
                path: url
              });

            case 5:
              response = _context.sent;
              _context.next = 8;
              return response.json();

            case 8:
              reviews = _context.sent;
              totalCount = parseInt(response.headers.get('x-wp-total'), 10);
              ids = reviews.map(function (review) {
                return review.id;
              });
              reviewResources = reviews.reduce(function (resources, review) {
                resources[getResourceName('review', review.id)] = {
                  data: review
                };
                return resources;
              }, {});
              return _context.abrupt("return", reviews_operations_objectSpread(defineProperty_default()({}, resourceName, {
                data: ids,
                totalCount: totalCount
              }), reviewResources));

            case 15:
              _context.prev = 15;
              _context.t0 = _context["catch"](2);
              return _context.abrupt("return", defineProperty_default()({}, resourceName, {
                error: _context.t0
              }));

            case 18:
            case "end":
              return _context.stop();
          }
        }
      }, _callee, null, [[2, 15]]);
    }));

    return function (_x) {
      return _ref.apply(this, arguments);
    };
  }());
}

function readReviews(resourceNames, fetch) {
  var filteredNames = resourceNames.filter(function (name) {
    return isResourcePrefix(name, 'review');
  });
  return filteredNames.map(function (resourceName) {
    return readReview(resourceName, fetch);
  });
}

function readReview(resourceName, fetch) {
  var id = getResourceIdentifier(resourceName);
  var url = "".concat(constants["c" /* NAMESPACE */], "/products/reviews/").concat(id);
  return fetch({
    path: url
  }).then(function (review) {
    return defineProperty_default()({}, resourceName, {
      data: review
    });
  }).catch(function (error) {
    return defineProperty_default()({}, resourceName, {
      error: error
    });
  });
}

/* harmony default export */ var reviews_operations = ({
  read: reviews_operations_read
});
// CONCATENATED MODULE: ./client/wc-api/reviews/selectors.js
/**
 * External dependencies
 */

/**
 * Internal dependencies
 */




var selectors_getReviews = function getReviews(getResource, requireResource) {
  return function () {
    var query = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
    var requirement = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : constants["a" /* DEFAULT_REQUIREMENT */];
    var resourceName = getResourceName('review-query', query);
    var ids = requireResource(requirement, resourceName).data || [];
    var reviews = ids.map(function (id) {
      return getResource(getResourceName('review', id)).data || {};
    });
    return reviews;
  };
};

var selectors_getReviewsTotalCount = function getReviewsTotalCount(getResource, requireResource) {
  return function () {
    var query = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
    var requirement = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : constants["a" /* DEFAULT_REQUIREMENT */];
    var resourceName = getResourceName('review-query', query);
    return requireResource(requirement, resourceName).totalCount || 0;
  };
};

var selectors_getReviewsError = function getReviewsError(getResource) {
  return function () {
    var query = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
    var resourceName = getResourceName('review-query', query);
    return getResource(resourceName).error;
  };
};

var selectors_isGetReviewsRequesting = function isGetReviewsRequesting(getResource) {
  return function () {
    var query = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
    var resourceName = getResourceName('review-query', query);

    var _getResource = getResource(resourceName),
        lastRequested = _getResource.lastRequested,
        lastReceived = _getResource.lastReceived;

    if (Object(external_lodash_["isNil"])(lastRequested) || Object(external_lodash_["isNil"])(lastReceived)) {
      return true;
    }

    return lastRequested > lastReceived;
  };
};

/* harmony default export */ var reviews_selectors = ({
  getReviews: selectors_getReviews,
  getReviewsError: selectors_getReviewsError,
  getReviewsTotalCount: selectors_getReviewsTotalCount,
  isGetReviewsRequesting: selectors_isGetReviewsRequesting
});
// CONCATENATED MODULE: ./client/wc-api/reviews/index.js
/**
 * Internal dependencies
 */


/* harmony default export */ var wc_api_reviews = ({
  operations: reviews_operations,
  selectors: reviews_selectors
});
// CONCATENATED MODULE: ./client/wc-api/user/operations.js



/**
 * External dependencies
 */



function user_operations_read(resourceNames) {
  var fetch = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : external_this_wp_apiFetch_default.a;
  return toConsumableArray_default()(readCurrentUserData(resourceNames, fetch));
}

function user_operations_update(resourceNames, data) {
  var fetch = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : external_this_wp_apiFetch_default.a;
  return toConsumableArray_default()(operations_updateCurrentUserData(resourceNames, data, fetch));
}

function readCurrentUserData(resourceNames, fetch) {
  if (resourceNames.includes('current-user-data')) {
    var url = '/wp/v2/users/me?context=edit';
    return [fetch({
      path: url
    }).then(userToUserDataResource).catch(function (error) {
      return {
        'current-user-data': {
          error: String(error.message)
        }
      };
    })];
  }

  return [];
}

function operations_updateCurrentUserData(resourceNames, data, fetch) {
  var resourceName = 'current-user-data';
  var userDataFields = ['categories_report_columns', 'coupons_report_columns', 'customers_report_columns', 'orders_report_columns', 'products_report_columns', 'revenue_report_columns', 'taxes_report_columns', 'variations_report_columns', 'dashboard_sections', 'dashboard_chart_type', 'dashboard_chart_interval', 'dashboard_leaderboard_rows', 'activity_panel_inbox_last_read', 'homepage_stats'];

  if (resourceNames.includes(resourceName)) {
    var url = '/wp/v2/users/me';
    var userData = Object(external_lodash_["pick"])(data[resourceName], userDataFields);
    var meta = Object(external_lodash_["mapValues"])(userData, JSON.stringify);
    var user = {
      woocommerce_meta: meta
    };
    return [fetch({
      path: url,
      method: 'POST',
      data: user
    }).then(userToUserDataResource).catch(function (error) {
      return defineProperty_default()({}, resourceName, {
        error: error
      });
    })];
  }

  return [];
}

function userToUserDataResource(user) {
  var userData = Object(external_lodash_["mapValues"])(user.woocommerce_meta, function (data) {
    if (!data || data.length === 0) {
      return '';
    }

    return JSON.parse(data);
  });
  return {
    'current-user-data': {
      data: userData
    }
  };
}

/* harmony default export */ var user_operations = ({
  read: user_operations_read,
  update: user_operations_update
});
// CONCATENATED MODULE: ./client/wc-api/user/selectors.js
/**
 * Internal dependencies
 */

/**
 * WooCommerce dependencies
 */



var selectors_getCurrentUserData = function getCurrentUserData(getResource, requireResource) {
  return function () {
    var requirement = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : constants["a" /* DEFAULT_REQUIREMENT */];
    return Object(settings["g" /* getSetting */])('currentUserData', {}, function (cud) {
      return requireResource(requirement, 'current-user-data').data || cud;
    });
  };
};

/* harmony default export */ var user_selectors = ({
  getCurrentUserData: selectors_getCurrentUserData
});
// CONCATENATED MODULE: ./client/wc-api/user/mutations.js


var mutations_updateCurrentUserData = function updateCurrentUserData(operations) {
  return function (userDataFields) {
    var resourceKey = 'current-user-data';
    operations.update([resourceKey], defineProperty_default()({}, resourceKey, userDataFields));
  };
};

/* harmony default export */ var user_mutations = ({
  updateCurrentUserData: mutations_updateCurrentUserData
});
// CONCATENATED MODULE: ./client/wc-api/user/index.js
/**
 * Internal dependencies
 */



/* harmony default export */ var wc_api_user = ({
  operations: user_operations,
  selectors: user_selectors,
  mutations: user_mutations
});
// CONCATENATED MODULE: ./client/wc-api/wc-api-spec.js



function wc_api_spec_ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function wc_api_spec_objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { wc_api_spec_ownKeys(Object(source), true).forEach(function (key) { defineProperty_default()(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { wc_api_spec_ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

/**
 * Internal dependencies
 */











function createWcApiSpec() {
  return {
    name: 'wcApi',
    mutations: wc_api_spec_objectSpread({}, wc_api_export.mutations, {}, wc_api_items.mutations, {}, wc_api_notes.mutations, {}, onboarding.mutations, {}, wc_api_options.mutations, {}, wc_api_user.mutations),
    selectors: wc_api_spec_objectSpread({}, imports.selectors, {}, wc_api_items.selectors, {}, wc_api_notes.selectors, {}, onboarding.selectors, {}, wc_api_options.selectors, {}, reports_items.selectors, {}, stats.selectors, {}, wc_api_reviews.selectors, {}, wc_api_user.selectors),
    operations: {
      read: function read(resourceNames) {
        if (document.hidden) {
          // Don't do any read updates while the tab isn't active.
          return [];
        }

        return [].concat(toConsumableArray_default()(imports.operations.read(resourceNames)), toConsumableArray_default()(wc_api_items.operations.read(resourceNames)), toConsumableArray_default()(wc_api_notes.operations.read(resourceNames)), toConsumableArray_default()(onboarding.operations.read(resourceNames)), toConsumableArray_default()(wc_api_options.operations.read(resourceNames)), toConsumableArray_default()(reports_items.operations.read(resourceNames)), toConsumableArray_default()(stats.operations.read(resourceNames)), toConsumableArray_default()(wc_api_reviews.operations.read(resourceNames)), toConsumableArray_default()(wc_api_user.operations.read(resourceNames)));
      },
      update: function update(resourceNames, data) {
        return [].concat(toConsumableArray_default()(wc_api_export.operations.update(resourceNames, data)), toConsumableArray_default()(wc_api_items.operations.update(resourceNames, data)), toConsumableArray_default()(wc_api_notes.operations.update(resourceNames, data)), toConsumableArray_default()(onboarding.operations.update(resourceNames, data)), toConsumableArray_default()(wc_api_options.operations.update(resourceNames, data)), toConsumableArray_default()(wc_api_user.operations.update(resourceNames, data)));
      },
      updateLocally: function updateLocally(resourceNames, data) {
        return toConsumableArray_default()(wc_api_items.operations.updateLocally(resourceNames, data));
      }
    }
  };
}

/* harmony default export */ var wc_api_spec = (createWcApiSpec());
// CONCATENATED MODULE: ./client/wc-api/wp-data-store/index.js
/**
 * External dependencies
 */

/**
 * Internal dependencies
 */




if (false) {}

function createWcApiStore() {
  var apiClient = create_api_client('wc-api', wc_api_spec);

  function getComponentSelectors(component) {
    var componentRequirements = [];
    return {
      selectors: apiClient.getSelectors(componentRequirements),
      onComplete: function onComplete() {
        if (componentRequirements.length === 0) {
          apiClient.clearComponentRequirements(component);
        } else {
          apiClient.setComponentRequirements(component, componentRequirements);
        }
      },
      onUnmount: function onUnmount() {
        apiClient.clearComponentRequirements(component);
      }
    };
  }

  return {
    // The wrapped function for getSelectors is temporary code.
    //
    // @todo Remove the `() =>` after the `@wordpress/data` PR is merged:
    // https://github.com/WordPress/gutenberg/pull/11460
    //
    getSelectors: function getSelectors() {
      return function (context) {
        var component = context && context.component ? context.component : context;
        return getComponentSelectors(component);
      };
    },
    getActions: function getActions() {
      var mutations = apiClient.getMutations();
      return mutations;
    },
    subscribe: apiClient.subscribe
  };
}

Object(external_this_wp_data_["registerGenericStore"])('wc-api', createWcApiStore());

/***/ }),

/***/ 706:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";

// NAMESPACE OBJECT: ./node_modules/@wordpress/notices/build-module/store/actions.js
var actions_namespaceObject = {};
__webpack_require__.r(actions_namespaceObject);
__webpack_require__.d(actions_namespaceObject, "createNotice", function() { return createNotice; });
__webpack_require__.d(actions_namespaceObject, "createSuccessNotice", function() { return createSuccessNotice; });
__webpack_require__.d(actions_namespaceObject, "createInfoNotice", function() { return createInfoNotice; });
__webpack_require__.d(actions_namespaceObject, "createErrorNotice", function() { return createErrorNotice; });
__webpack_require__.d(actions_namespaceObject, "createWarningNotice", function() { return createWarningNotice; });
__webpack_require__.d(actions_namespaceObject, "removeNotice", function() { return removeNotice; });

// NAMESPACE OBJECT: ./node_modules/@wordpress/notices/build-module/store/selectors.js
var selectors_namespaceObject = {};
__webpack_require__.r(selectors_namespaceObject);
__webpack_require__.d(selectors_namespaceObject, "getNotices", function() { return getNotices; });

// EXTERNAL MODULE: external {"this":["wp","data"]}
var external_this_wp_data_ = __webpack_require__(19);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/esm/toConsumableArray.js + 3 modules
var toConsumableArray = __webpack_require__(17);

// EXTERNAL MODULE: external "lodash"
var external_lodash_ = __webpack_require__(2);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/esm/defineProperty.js
var defineProperty = __webpack_require__(13);

// CONCATENATED MODULE: ./node_modules/@wordpress/notices/build-module/store/utils/on-sub-key.js


function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { Object(defineProperty["a" /* default */])(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

/**
 * Higher-order reducer creator which creates a combined reducer object, keyed
 * by a property on the action object.
 *
 * @param {string} actionProperty Action property by which to key object.
 *
 * @return {Function} Higher-order reducer.
 */
var on_sub_key_onSubKey = function onSubKey(actionProperty) {
  return function (reducer) {
    return function () {
      var state = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
      var action = arguments.length > 1 ? arguments[1] : undefined;
      // Retrieve subkey from action. Do not track if undefined; useful for cases
      // where reducer is scoped by action shape.
      var key = action[actionProperty];

      if (key === undefined) {
        return state;
      } // Avoid updating state if unchanged. Note that this also accounts for a
      // reducer which returns undefined on a key which is not yet tracked.


      var nextKeyState = reducer(state[key], action);

      if (nextKeyState === state[key]) {
        return state;
      }

      return _objectSpread({}, state, Object(defineProperty["a" /* default */])({}, key, nextKeyState));
    };
  };
};
/* harmony default export */ var on_sub_key = (on_sub_key_onSubKey);
//# sourceMappingURL=on-sub-key.js.map
// CONCATENATED MODULE: ./node_modules/@wordpress/notices/build-module/store/reducer.js


/**
 * External dependencies
 */

/**
 * Internal dependencies
 */


/**
 * Reducer returning the next notices state. The notices state is an object
 * where each key is a context, its value an array of notice objects.
 *
 * @param {Object} state  Current state.
 * @param {Object} action Dispatched action.
 *
 * @return {Object} Updated state.
 */

var notices = on_sub_key('context')(function () {
  var state = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : [];
  var action = arguments.length > 1 ? arguments[1] : undefined;

  switch (action.type) {
    case 'CREATE_NOTICE':
      // Avoid duplicates on ID.
      return [].concat(Object(toConsumableArray["a" /* default */])(Object(external_lodash_["reject"])(state, {
        id: action.notice.id
      })), [action.notice]);

    case 'REMOVE_NOTICE':
      return Object(external_lodash_["reject"])(state, {
        id: action.id
      });
  }

  return state;
});
/* harmony default export */ var store_reducer = (notices);
//# sourceMappingURL=reducer.js.map
// EXTERNAL MODULE: ./node_modules/@babel/runtime/regenerator/index.js
var regenerator = __webpack_require__(73);
var regenerator_default = /*#__PURE__*/__webpack_require__.n(regenerator);

// CONCATENATED MODULE: ./node_modules/@wordpress/notices/build-module/store/constants.js
/**
 * Default context to use for notice grouping when not otherwise specified. Its
 * specific value doesn't hold much meaning, but it must be reasonably unique
 * and, more importantly, referenced consistently in the store implementation.
 *
 * @type {string}
 */
var DEFAULT_CONTEXT = 'global';
/**
 * Default notice status.
 *
 * @type {string}
 */

var DEFAULT_STATUS = 'info';
//# sourceMappingURL=constants.js.map
// CONCATENATED MODULE: ./node_modules/@wordpress/notices/build-module/store/actions.js


var _marked =
/*#__PURE__*/
regenerator_default.a.mark(createNotice);

/**
 * External dependencies
 */

/**
 * Internal dependencies
 */


/**
 * @typedef {Object} WPNoticeAction Object describing a user action option associated with a notice.
 *
 * @property {string}    label    Message to use as action label.
 * @property {?string}   url      Optional URL of resource if action incurs
 *                                browser navigation.
 * @property {?Function} onClick  Optional function to invoke when action is
 *                                triggered by user.
 *
 */

/**
 * Yields action objects used in signalling that a notice is to be created.
 *
 * @param {string}                [status='info']              Notice status.
 * @param {string}                content                      Notice message.
 * @param {Object}                [options]                    Notice options.
 * @param {string}                [options.context='global']   Context under which to
 *                                                             group notice.
 * @param {string}                [options.id]                 Identifier for notice.
 *                                                             Automatically assigned
 *                                                             if not specified.
 * @param {boolean}               [options.isDismissible=true] Whether the notice can
 *                                                             be dismissed by user.
 * @param {string}                [options.type='default']     Type of notice, one of
 *                                                             `default`, or `snackbar`.
 * @param {boolean}               [options.speak=true]         Whether the notice
 *                                                             content should be
 *                                                             announced to screen
 *                                                             readers.
 * @param {Array<WPNoticeAction>} [options.actions]            User actions to be
 *                                                             presented with notice.
 */

function createNotice() {
  var status,
      content,
      options,
      _options$speak,
      speak,
      _options$isDismissibl,
      isDismissible,
      _options$context,
      context,
      _options$id,
      id,
      _options$actions,
      actions,
      _options$type,
      type,
      __unstableHTML,
      _args = arguments;

  return regenerator_default.a.wrap(function createNotice$(_context) {
    while (1) {
      switch (_context.prev = _context.next) {
        case 0:
          status = _args.length > 0 && _args[0] !== undefined ? _args[0] : DEFAULT_STATUS;
          content = _args.length > 1 ? _args[1] : undefined;
          options = _args.length > 2 && _args[2] !== undefined ? _args[2] : {};
          _options$speak = options.speak, speak = _options$speak === void 0 ? true : _options$speak, _options$isDismissibl = options.isDismissible, isDismissible = _options$isDismissibl === void 0 ? true : _options$isDismissibl, _options$context = options.context, context = _options$context === void 0 ? DEFAULT_CONTEXT : _options$context, _options$id = options.id, id = _options$id === void 0 ? Object(external_lodash_["uniqueId"])(context) : _options$id, _options$actions = options.actions, actions = _options$actions === void 0 ? [] : _options$actions, _options$type = options.type, type = _options$type === void 0 ? 'default' : _options$type, __unstableHTML = options.__unstableHTML; // The supported value shape of content is currently limited to plain text
          // strings. To avoid setting expectation that e.g. a WPElement could be
          // supported, cast to a string.

          content = String(content);

          if (!speak) {
            _context.next = 8;
            break;
          }

          _context.next = 8;
          return {
            type: 'SPEAK',
            message: content,
            ariaLive: type === 'snackbar' ? 'polite' : 'assertive'
          };

        case 8:
          _context.next = 10;
          return {
            type: 'CREATE_NOTICE',
            context: context,
            notice: {
              id: id,
              status: status,
              content: content,
              __unstableHTML: __unstableHTML,
              isDismissible: isDismissible,
              actions: actions,
              type: type
            }
          };

        case 10:
        case "end":
          return _context.stop();
      }
    }
  }, _marked);
}
/**
 * Returns an action object used in signalling that a success notice is to be
 * created. Refer to `createNotice` for options documentation.
 *
 * @see createNotice
 *
 * @param {string} content   Notice message.
 * @param {Object} [options] Optional notice options.
 *
 * @return {Object} Action object.
 */

function createSuccessNotice(content, options) {
  return createNotice('success', content, options);
}
/**
 * Returns an action object used in signalling that an info notice is to be
 * created. Refer to `createNotice` for options documentation.
 *
 * @see createNotice
 *
 * @param {string} content   Notice message.
 * @param {Object} [options] Optional notice options.
 *
 * @return {Object} Action object.
 */

function createInfoNotice(content, options) {
  return createNotice('info', content, options);
}
/**
 * Returns an action object used in signalling that an error notice is to be
 * created. Refer to `createNotice` for options documentation.
 *
 * @see createNotice
 *
 * @param {string} content   Notice message.
 * @param {Object} [options] Optional notice options.
 *
 * @return {Object} Action object.
 */

function createErrorNotice(content, options) {
  return createNotice('error', content, options);
}
/**
 * Returns an action object used in signalling that a warning notice is to be
 * created. Refer to `createNotice` for options documentation.
 *
 * @see createNotice
 *
 * @param {string} content   Notice message.
 * @param {Object} [options] Optional notice options.
 *
 * @return {Object} Action object.
 */

function createWarningNotice(content, options) {
  return createNotice('warning', content, options);
}
/**
 * Returns an action object used in signalling that a notice is to be removed.
 *
 * @param {string} id                 Notice unique identifier.
 * @param {string} [context='global'] Optional context (grouping) in which the notice is
 *                                    intended to appear. Defaults to default context.
 *
 * @return {Object} Action object.
 */

function removeNotice(id) {
  var context = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : DEFAULT_CONTEXT;
  return {
    type: 'REMOVE_NOTICE',
    id: id,
    context: context
  };
}
//# sourceMappingURL=actions.js.map
// CONCATENATED MODULE: ./node_modules/@wordpress/notices/build-module/store/selectors.js
/**
 * Internal dependencies
 */

/** @typedef {import('./actions').WPNoticeAction} WPNoticeAction */

/**
 * The default empty set of notices to return when there are no notices
 * assigned for a given notices context. This can occur if the getNotices
 * selector is called without a notice ever having been created for the
 * context. A shared value is used to ensure referential equality between
 * sequential selector calls, since otherwise `[] !== []`.
 *
 * @type {Array}
 */

var DEFAULT_NOTICES = [];
/**
 * @typedef {Object} WPNotice Notice object.
 *
 * @property {string}  id               Unique identifier of notice.
 * @property {string}  status           Status of notice, one of `success`,
 *                                      `info`, `error`, or `warning`. Defaults
 *                                      to `info`.
 * @property {string}  content          Notice message.
 * @property {string}  __unstableHTML   Notice message as raw HTML. Intended to
 *                                      serve primarily for compatibility of
 *                                      server-rendered notices, and SHOULD NOT
 *                                      be used for notices. It is subject to
 *                                      removal without notice.
 * @property {boolean} isDismissible    Whether the notice can be dismissed by
 *                                      user. Defaults to `true`.
 * @property {string}  type             Type of notice, one of `default`,
 *                                      or `snackbar`. Defaults to `default`.
 * @property {boolean} speak            Whether the notice content should be
 *                                      announced to screen readers. Defaults to
 *                                      `true`.
 * @property {WPNoticeAction[]} actions User actions to present with notice.
 *
 */

/**
 * Returns all notices as an array, optionally for a given context. Defaults to
 * the global context.
 *
 * @param {Object}  state   Notices state.
 * @param {?string} context Optional grouping context.
 *
 * @return {WPNotice[]} Array of notices.
 */

function getNotices(state) {
  var context = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : DEFAULT_CONTEXT;
  return state[context] || DEFAULT_NOTICES;
}
//# sourceMappingURL=selectors.js.map
// EXTERNAL MODULE: ./node_modules/@wordpress/a11y/build-module/index.js + 3 modules
var build_module = __webpack_require__(169);

// CONCATENATED MODULE: ./node_modules/@wordpress/notices/build-module/store/controls.js
/**
 * WordPress dependencies
 */

/* harmony default export */ var controls = ({
  SPEAK: function SPEAK(action) {
    Object(build_module["a" /* speak */])(action.message, action.ariaLive || 'assertive');
  }
});
//# sourceMappingURL=controls.js.map
// CONCATENATED MODULE: ./node_modules/@wordpress/notices/build-module/store/index.js
/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */





/* harmony default export */ var store = (Object(external_this_wp_data_["registerStore"])('core/notices', {
  reducer: store_reducer,
  actions: actions_namespaceObject,
  selectors: selectors_namespaceObject,
  controls: controls
}));
//# sourceMappingURL=index.js.map
// CONCATENATED MODULE: ./node_modules/@wordpress/notices/build-module/index.js
/**
 * Internal dependencies
 */

//# sourceMappingURL=index.js.map

/***/ }),

/***/ 71:
/***/ (function(module, exports, __webpack_require__) {

"use strict";


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

var encode = function encode(str, defaultEncoder, charset) {
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

module.exports = {
    arrayToObject: arrayToObject,
    assign: assign,
    combine: combine,
    compact: compact,
    decode: decode,
    encode: encode,
    isBuffer: isBuffer,
    isRegExp: isRegExp,
    merge: merge
};


/***/ }),

/***/ 719:
/***/ (function(module, exports) {

(function() { module.exports = this["wc"]["csvExport"]; }());

/***/ }),

/***/ 72:
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

/***/ 73:
/***/ (function(module, exports, __webpack_require__) {

module.exports = __webpack_require__(432);


/***/ }),

/***/ 737:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* WEBPACK VAR INJECTION */(function(process) {/* harmony import */ var _use_media_query__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(99);
/**
 * Internal dependencies
 */

/**
 * Whether or not the user agent is Internet Explorer.
 *
 * @type {boolean}
 */

var IS_IE = typeof window !== 'undefined' && window.navigator.userAgent.indexOf('Trident') >= 0;
/**
 * Hook returning whether the user has a preference for reduced motion.
 *
 * @return {boolean} Reduced motion preference value.
 */

var useReducedMotion = process.env.FORCE_REDUCED_MOTION || IS_IE ? function () {
  return true;
} : function () {
  return Object(_use_media_query__WEBPACK_IMPORTED_MODULE_0__[/* default */ "a"])('(prefers-reduced-motion: reduce)');
};
/* harmony default export */ __webpack_exports__["a"] = (useReducedMotion);
//# sourceMappingURL=index.js.map
/* WEBPACK VAR INJECTION */}.call(this, __webpack_require__(72)))

/***/ }),

/***/ 76:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "a", function() { return _inheritsLoose; });
function _inheritsLoose(subClass, superClass) {
  subClass.prototype = Object.create(superClass.prototype);
  subClass.prototype.constructor = subClass;
  subClass.__proto__ = superClass;
}

/***/ }),

/***/ 77:
/***/ (function(module, exports, __webpack_require__) {

"use strict";


/**
 * Internal dependencies;
 */
var isShallowEqualObjects = __webpack_require__( 140 );
var isShallowEqualArrays = __webpack_require__( 141 );

var isArray = Array.isArray;

/**
 * @typedef {{[key: string]: any}} ComparableObject
 */

/**
 * Returns true if the two arrays or objects are shallow equal, or false
 * otherwise.
 *
 * @param {any[]|ComparableObject} a First object or array to compare.
 * @param {any[]|ComparableObject} b Second object or array to compare.
 *
 * @return {boolean} Whether the two values are shallow equal.
 */
function isShallowEqual( a, b ) {
	if ( a && b ) {
		if ( a.constructor === Object && b.constructor === Object ) {
			return isShallowEqualObjects( a, b );
		} else if ( isArray( a ) && isArray( b ) ) {
			return isShallowEqualArrays( a, b );
		}
	}

	return a === b;
}

module.exports = isShallowEqual;
module.exports.isShallowEqualObjects = isShallowEqualObjects;
module.exports.isShallowEqualArrays = isShallowEqualArrays;


/***/ }),

/***/ 78:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
var isProduction = "production" === 'production';
var prefix = 'Invariant failed';
function invariant(condition, message) {
    if (condition) {
        return;
    }
    if (isProduction) {
        throw new Error(prefix);
    }
    throw new Error(prefix + ": " + (message || ''));
}

/* harmony default export */ __webpack_exports__["a"] = (invariant);


/***/ }),

/***/ 79:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "b", function() { return recordEvent; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "a", function() { return queueRecordEvent; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "c", function() { return recordPageView; });
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(15);
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _babel_runtime_helpers_typeof__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(43);
/* harmony import */ var _babel_runtime_helpers_typeof__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_typeof__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var debug__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(98);
/* harmony import */ var debug__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(debug__WEBPACK_IMPORTED_MODULE_2__);



function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0___default()(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

/**
 * External dependencies
 */

/**
 * Module variables
 */

var tracksDebug = debug__WEBPACK_IMPORTED_MODULE_2___default()('wc-admin:tracks');
/**
 * Record an event to Tracks
 *
 * @param {string} eventName The name of the event to record, don't include the wcadmin_ prefix
 * @param {Object} eventProperties event properties to include in the event
 */

function recordEvent(eventName, eventProperties) {
  tracksDebug('recordevent %s %o', 'wcadmin_' + eventName, eventProperties, {
    _tqk: window._tkq,
    shouldRecord:  true && !!window._tkq && !!window.wcTracks && !!window.wcTracks.isEnabled
  });

  if (!window.wcTracks || typeof window.wcTracks.recordEvent !== 'function' || "production" === 'development') {
    return false;
  }

  window.wcTracks.recordEvent(eventName, eventProperties);
}
var tracksQueue = {
  localStorageKey: function localStorageKey() {
    return 'tracksQueue';
  },
  clear: function clear() {
    if (!window.localStorage) {
      return;
    }

    window.localStorage.removeItem(tracksQueue.localStorageKey());
  },
  get: function get() {
    if (!window.localStorage) {
      return [];
    }

    var items = window.localStorage.getItem(tracksQueue.localStorageKey());
    items = items ? JSON.parse(items) : [];
    items = Array.isArray(items) ? items : [];
    return items;
  },
  add: function add() {
    for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
      args[_key] = arguments[_key];
    }

    if (!window.localStorage) {
      // If unable to queue, run it now.
      tracksDebug('Unable to queue, running now', {
        args: args
      });
      recordEvent.apply(null, args || undefined);
      return;
    }

    var items = tracksQueue.get();
    var newItem = {
      args: args
    };
    items.push(newItem);
    items = items.slice(-100); // Upper limit.

    tracksDebug('Adding new item to queue.', newItem);
    window.localStorage.setItem(tracksQueue.localStorageKey(), JSON.stringify(items));
  },
  process: function process() {
    if (!window.localStorage) {
      return; // Not possible.
    }

    var items = tracksQueue.get();
    tracksQueue.clear();
    tracksDebug('Processing items in queue.', items);
    items.forEach(function (item) {
      if (_babel_runtime_helpers_typeof__WEBPACK_IMPORTED_MODULE_1___default()(item) === 'object') {
        tracksDebug('Processing item in queue.', item);
        recordEvent.apply(null, item.args || undefined);
      }
    });
  }
};
/**
 * Queue a tracks event.
 *
 * This allows you to delay tracks  events that would otherwise cause a race condition.
 * For example, when we trigger `wcadmin_tasklist_appearance_continue_setup` we're simultaneously moving the user to a new page via
 * `window.location`. This is an example of a race condition that should be avoided by enqueueing the event,
 * and therefore running it on the next pageview.
 *
 * @param {string} eventName The name of the event to record, don't include the wcadmin_ prefix
 * @param {Object} eventProperties event properties to include in the event
 */

function queueRecordEvent(eventName, eventProperties) {
  tracksQueue.add(eventName, eventProperties);
}
/**
 * Record a page view to Tracks
 *
 * @param {string} path the page/path to record a page view for
 * @param {Object} extraProperties extra event properties to include in the event
 */

function recordPageView(path, extraProperties) {
  if (!path) {
    return;
  }

  recordEvent('page_view', _objectSpread({
    path: path
  }, extraProperties)); // Process queue.

  tracksQueue.process();
}

/***/ }),

/***/ 8:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "a", function() { return _possibleConstructorReturn; });
/* harmony import */ var _helpers_esm_typeof__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(89);
/* harmony import */ var _assertThisInitialized__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(5);


function _possibleConstructorReturn(self, call) {
  if (call && (Object(_helpers_esm_typeof__WEBPACK_IMPORTED_MODULE_0__[/* default */ "a"])(call) === "object" || typeof call === "function")) {
    return call;
  }

  return Object(_assertThisInitialized__WEBPACK_IMPORTED_MODULE_1__[/* default */ "a"])(self);
}

/***/ }),

/***/ 80:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "a", function() { return Dashicon; });
/* harmony import */ var _babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(11);
/* harmony import */ var _babel_runtime_helpers_esm_objectWithoutProperties__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(16);
/* harmony import */ var _babel_runtime_helpers_esm_classCallCheck__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(7);
/* harmony import */ var _babel_runtime_helpers_esm_createClass__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(6);
/* harmony import */ var _babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(8);
/* harmony import */ var _babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(4);
/* harmony import */ var _babel_runtime_helpers_esm_inherits__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(9);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var _primitives__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(62);









/* !!!
IF YOU ARE EDITING dashicon/index.jsx
THEN YOU ARE EDITING A FILE THAT GETS OUTPUT FROM THE DASHICONS REPO!
DO NOT EDIT THAT FILE! EDIT index-header.jsx and index-footer.jsx instead
OR if you're looking to change now SVGs get output, you'll need to edit strings in the Gruntfile :)
!!! */

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */



var Dashicon =
/*#__PURE__*/
function (_Component) {
  Object(_babel_runtime_helpers_esm_inherits__WEBPACK_IMPORTED_MODULE_6__[/* default */ "a"])(Dashicon, _Component);

  function Dashicon() {
    Object(_babel_runtime_helpers_esm_classCallCheck__WEBPACK_IMPORTED_MODULE_2__[/* default */ "a"])(this, Dashicon);

    return Object(_babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_4__[/* default */ "a"])(this, Object(_babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_5__[/* default */ "a"])(Dashicon).apply(this, arguments));
  }

  Object(_babel_runtime_helpers_esm_createClass__WEBPACK_IMPORTED_MODULE_3__[/* default */ "a"])(Dashicon, [{
    key: "render",
    value: function render() {
      var _this$props = this.props,
          icon = _this$props.icon,
          _this$props$size = _this$props.size,
          size = _this$props$size === void 0 ? 20 : _this$props$size,
          className = _this$props.className,
          extraProps = Object(_babel_runtime_helpers_esm_objectWithoutProperties__WEBPACK_IMPORTED_MODULE_1__[/* default */ "a"])(_this$props, ["icon", "size", "className"]);

      var path;

      switch (icon) {
        case 'admin-appearance':
          path = 'M14.48 11.06L7.41 3.99l1.5-1.5c.5-.56 2.3-.47 3.51.32 1.21.8 1.43 1.28 2.91 2.1 1.18.64 2.45 1.26 4.45.85zm-.71.71L6.7 4.7 4.93 6.47c-.39.39-.39 1.02 0 1.41l1.06 1.06c.39.39.39 1.03 0 1.42-.6.6-1.43 1.11-2.21 1.69-.35.26-.7.53-1.01.84C1.43 14.23.4 16.08 1.4 17.07c.99 1 2.84-.03 4.18-1.36.31-.31.58-.66.85-1.02.57-.78 1.08-1.61 1.69-2.21.39-.39 1.02-.39 1.41 0l1.06 1.06c.39.39 1.02.39 1.41 0z';
          break;

        case 'admin-collapse':
          path = 'M10 2.16c4.33 0 7.84 3.51 7.84 7.84s-3.51 7.84-7.84 7.84S2.16 14.33 2.16 10 5.71 2.16 10 2.16zm2 11.72V6.12L6.18 9.97z';
          break;

        case 'admin-comments':
          path = 'M5 2h9c1.1 0 2 .9 2 2v7c0 1.1-.9 2-2 2h-2l-5 5v-5H5c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2z';
          break;

        case 'admin-customizer':
          path = 'M18.33 3.57s.27-.8-.31-1.36c-.53-.52-1.22-.24-1.22-.24-.61.3-5.76 3.47-7.67 5.57-.86.96-2.06 3.79-1.09 4.82.92.98 3.96-.17 4.79-1 2.06-2.06 5.21-7.17 5.5-7.79zM1.4 17.65c2.37-1.56 1.46-3.41 3.23-4.64.93-.65 2.22-.62 3.08.29.63.67.8 2.57-.16 3.46-1.57 1.45-4 1.55-6.15.89z';
          break;

        case 'admin-generic':
          path = 'M18 12h-2.18c-.17.7-.44 1.35-.81 1.93l1.54 1.54-2.1 2.1-1.54-1.54c-.58.36-1.23.63-1.91.79V19H8v-2.18c-.68-.16-1.33-.43-1.91-.79l-1.54 1.54-2.12-2.12 1.54-1.54c-.36-.58-.63-1.23-.79-1.91H1V9.03h2.17c.16-.7.44-1.35.8-1.94L2.43 5.55l2.1-2.1 1.54 1.54c.58-.37 1.24-.64 1.93-.81V2h3v2.18c.68.16 1.33.43 1.91.79l1.54-1.54 2.12 2.12-1.54 1.54c.36.59.64 1.24.8 1.94H18V12zm-8.5 1.5c1.66 0 3-1.34 3-3s-1.34-3-3-3-3 1.34-3 3 1.34 3 3 3z';
          break;

        case 'admin-home':
          path = 'M16 8.5l1.53 1.53-1.06 1.06L10 4.62l-6.47 6.47-1.06-1.06L10 2.5l4 4v-2h2v4zm-6-2.46l6 5.99V18H4v-5.97zM12 17v-5H8v5h4z';
          break;

        case 'admin-links':
          path = 'M17.74 2.76c1.68 1.69 1.68 4.41 0 6.1l-1.53 1.52c-1.12 1.12-2.7 1.47-4.14 1.09l2.62-2.61.76-.77.76-.76c.84-.84.84-2.2 0-3.04-.84-.85-2.2-.85-3.04 0l-.77.76-3.38 3.38c-.37-1.44-.02-3.02 1.1-4.14l1.52-1.53c1.69-1.68 4.42-1.68 6.1 0zM8.59 13.43l5.34-5.34c.42-.42.42-1.1 0-1.52-.44-.43-1.13-.39-1.53 0l-5.33 5.34c-.42.42-.42 1.1 0 1.52.44.43 1.13.39 1.52 0zm-.76 2.29l4.14-4.15c.38 1.44.03 3.02-1.09 4.14l-1.52 1.53c-1.69 1.68-4.41 1.68-6.1 0-1.68-1.68-1.68-4.42 0-6.1l1.53-1.52c1.12-1.12 2.7-1.47 4.14-1.1l-4.14 4.15c-.85.84-.85 2.2 0 3.05.84.84 2.2.84 3.04 0z';
          break;

        case 'admin-media':
          path = 'M13 11V4c0-.55-.45-1-1-1h-1.67L9 1H5L3.67 3H2c-.55 0-1 .45-1 1v7c0 .55.45 1 1 1h10c.55 0 1-.45 1-1zM7 4.5c1.38 0 2.5 1.12 2.5 2.5S8.38 9.5 7 9.5 4.5 8.38 4.5 7 5.62 4.5 7 4.5zM14 6h5v10.5c0 1.38-1.12 2.5-2.5 2.5S14 17.88 14 16.5s1.12-2.5 2.5-2.5c.17 0 .34.02.5.05V9h-3V6zm-4 8.05V13h2v3.5c0 1.38-1.12 2.5-2.5 2.5S7 17.88 7 16.5 8.12 14 9.5 14c.17 0 .34.02.5.05z';
          break;

        case 'admin-multisite':
          path = 'M14.27 6.87L10 3.14 5.73 6.87 5 6.14l5-4.38 5 4.38zM14 8.42l-4.05 3.43L6 8.38v-.74l4-3.5 4 3.5v.78zM11 9.7V8H9v1.7h2zm-1.73 4.03L5 10 .73 13.73 0 13l5-4.38L10 13zm10 0L15 10l-4.27 3.73L10 13l5-4.38L20 13zM5 11l4 3.5V18H1v-3.5zm10 0l4 3.5V18h-8v-3.5zm-9 6v-2H4v2h2zm10 0v-2h-2v2h2z';
          break;

        case 'admin-network':
          path = 'M16.95 2.58c1.96 1.95 1.96 5.12 0 7.07-1.51 1.51-3.75 1.84-5.59 1.01l-1.87 3.31-2.99.31L5 18H2l-1-2 7.95-7.69c-.92-1.87-.62-4.18.93-5.73 1.95-1.96 5.12-1.96 7.07 0zm-2.51 3.79c.74 0 1.33-.6 1.33-1.34 0-.73-.59-1.33-1.33-1.33-.73 0-1.33.6-1.33 1.33 0 .74.6 1.34 1.33 1.34z';
          break;

        case 'admin-page':
          path = 'M6 15V2h10v13H6zm-1 1h8v2H3V5h2v11z';
          break;

        case 'admin-plugins':
          path = 'M13.11 4.36L9.87 7.6 8 5.73l3.24-3.24c.35-.34 1.05-.2 1.56.32.52.51.66 1.21.31 1.55zm-8 1.77l.91-1.12 9.01 9.01-1.19.84c-.71.71-2.63 1.16-3.82 1.16H6.14L4.9 17.26c-.59.59-1.54.59-2.12 0-.59-.58-.59-1.53 0-2.12l1.24-1.24v-3.88c0-1.13.4-3.19 1.09-3.89zm7.26 3.97l3.24-3.24c.34-.35 1.04-.21 1.55.31.52.51.66 1.21.31 1.55l-3.24 3.25z';
          break;

        case 'admin-post':
          path = 'M10.44 3.02l1.82-1.82 6.36 6.35-1.83 1.82c-1.05-.68-2.48-.57-3.41.36l-.75.75c-.92.93-1.04 2.35-.35 3.41l-1.83 1.82-2.41-2.41-2.8 2.79c-.42.42-3.38 2.71-3.8 2.29s1.86-3.39 2.28-3.81l2.79-2.79L4.1 9.36l1.83-1.82c1.05.69 2.48.57 3.4-.36l.75-.75c.93-.92 1.05-2.35.36-3.41z';
          break;

        case 'admin-settings':
          path = 'M18 16V4c0-.55-.45-1-1-1H4c-.55 0-1 .45-1 1v12c0 .55.45 1 1 1h13c.55 0 1-.45 1-1zM8 11h1c.55 0 1 .45 1 1s-.45 1-1 1H8v1.5c0 .28-.22.5-.5.5s-.5-.22-.5-.5V13H6c-.55 0-1-.45-1-1s.45-1 1-1h1V5.5c0-.28.22-.5.5-.5s.5.22.5.5V11zm5-2h-1c-.55 0-1-.45-1-1s.45-1 1-1h1V5.5c0-.28.22-.5.5-.5s.5.22.5.5V7h1c.55 0 1 .45 1 1s-.45 1-1 1h-1v5.5c0 .28-.22.5-.5.5s-.5-.22-.5-.5V9z';
          break;

        case 'admin-site-alt':
          path = 'M9 0C4.03 0 0 4.03 0 9s4.03 9 9 9 9-4.03 9-9-4.03-9-9-9zm7.5 6.48c-.274.896-.908 1.64-1.75 2.05-.45-1.69-1.658-3.074-3.27-3.75.13-.444.41-.83.79-1.09-.43-.28-1-.42-1.34.07-.53.69 0 1.61.21 2v.14c-.555-.337-.99-.84-1.24-1.44-.966-.03-1.922.208-2.76.69-.087-.565-.032-1.142.16-1.68.733.07 1.453-.23 1.92-.8.46-.52-.13-1.18-.59-1.58h.36c1.36-.01 2.702.335 3.89 1 1.36 1.005 2.194 2.57 2.27 4.26.24 0 .7-.55.91-.92.172.34.32.69.44 1.05zM9 16.84c-2.05-2.08.25-3.75-1-5.24-.92-.85-2.29-.26-3.11-1.23-.282-1.473.267-2.982 1.43-3.93.52-.44 4-1 5.42.22.83.715 1.415 1.674 1.67 2.74.46.035.918-.066 1.32-.29.41 2.98-3.15 6.74-5.73 7.73zM5.15 2.09c.786-.3 1.676-.028 2.16.66-.42.38-.94.63-1.5.72.02-.294.085-.584.19-.86l-.85-.52z';
          break;

        case 'admin-site-alt2':
          path = 'M9 0C4.03 0 0 4.03 0 9s4.03 9 9 9 9-4.03 9-9-4.03-9-9-9zm2.92 12.34c0 .35.14.63.36.66.22.03.47-.22.58-.6l.2.08c.718.384 1.07 1.22.84 2-.15.69-.743 1.198-1.45 1.24-.49-1.21-2.11.06-3.56-.22-.612-.154-1.11-.6-1.33-1.19 1.19-.11 2.85-1.73 4.36-1.97zM8 11.27c.918 0 1.695-.68 1.82-1.59.44.54.41 1.324-.07 1.83-.255.223-.594.325-.93.28-.335-.047-.635-.236-.82-.52zm3-.76c.41.39 3-.06 3.52 1.09-.95-.2-2.95.61-3.47-1.08l-.05-.01zM9.73 5.45v.27c-.65-.77-1.33-1.07-1.61-.57-.28.5 1 1.11.76 1.88-.24.77-1.27.56-1.88 1.61-.61 1.05-.49 2.42 1.24 3.67-1.192-.132-2.19-.962-2.54-2.11-.4-1.2-.09-2.26-.78-2.46C4 7.46 3 8.71 3 9.8c-1.26-1.26.05-2.86-1.2-4.18C3.5 1.998 7.644.223 11.44 1.49c-1.1 1.02-1.722 2.458-1.71 3.96z';
          break;

        case 'admin-site-alt3':
          path = 'M9 0C4.03 0 0 4.03 0 9s4.03 9 9 9 9-4.03 9-9-4.03-9-9-9zM1.11 9.68h2.51c.04.91.167 1.814.38 2.7H1.84c-.403-.85-.65-1.764-.73-2.7zm8.57-5.4V1.19c.964.366 1.756 1.08 2.22 2 .205.347.386.708.54 1.08l-2.76.01zm3.22 1.35c.232.883.37 1.788.41 2.7H9.68v-2.7h3.22zM8.32 1.19v3.09H5.56c.154-.372.335-.733.54-1.08.462-.924 1.255-1.64 2.22-2.01zm0 4.44v2.7H4.7c.04-.912.178-1.817.41-2.7h3.21zm-4.7 2.69H1.11c.08-.936.327-1.85.73-2.7H4c-.213.886-.34 1.79-.38 2.7zM4.7 9.68h3.62v2.7H5.11c-.232-.883-.37-1.788-.41-2.7zm3.63 4v3.09c-.964-.366-1.756-1.08-2.22-2-.205-.347-.386-.708-.54-1.08l2.76-.01zm1.35 3.09v-3.04h2.76c-.154.372-.335.733-.54 1.08-.464.92-1.256 1.634-2.22 2v-.04zm0-4.44v-2.7h3.62c-.04.912-.178 1.817-.41 2.7H9.68zm4.71-2.7h2.51c-.08.936-.327 1.85-.73 2.7H14c.21-.87.337-1.757.38-2.65l.01-.05zm0-1.35c-.046-.894-.176-1.78-.39-2.65h2.16c.403.85.65 1.764.73 2.7l-2.5-.05zm1-4H13.6c-.324-.91-.793-1.76-1.39-2.52 1.244.56 2.325 1.426 3.14 2.52h.04zm-9.6-2.52c-.597.76-1.066 1.61-1.39 2.52H2.65c.815-1.094 1.896-1.96 3.14-2.52zm-3.15 12H4.4c.324.91.793 1.76 1.39 2.52-1.248-.567-2.33-1.445-3.14-2.55l-.01.03zm9.56 2.52c.597-.76 1.066-1.61 1.39-2.52h1.76c-.82 1.08-1.9 1.933-3.14 2.48l-.01.04z';
          break;

        case 'admin-site':
          path = 'M9 0C4.03 0 0 4.03 0 9s4.03 9 9 9 9-4.03 9-9-4.03-9-9-9zm3.46 11.95c0 1.47-.8 3.3-4.06 4.7.3-4.17-2.52-3.69-3.2-5 .126-1.1.804-2.063 1.8-2.55-1.552-.266-3-.96-4.18-2 .05.47.28.904.64 1.21-.782-.295-1.458-.817-1.94-1.5.977-3.225 3.883-5.482 7.25-5.63-.84 1.38-1.5 4.13 0 5.57C7.23 7 6.26 5 5.41 5.79c-1.13 1.06.33 2.51 3.42 3.08 3.29.59 3.66 1.58 3.63 3.08zm1.34-4c-.32-1.11.62-2.23 1.69-3.14 1.356 1.955 1.67 4.45.84 6.68-.77-1.89-2.17-2.32-2.53-3.57v.03z';
          break;

        case 'admin-tools':
          path = 'M16.68 9.77c-1.34 1.34-3.3 1.67-4.95.99l-5.41 6.52c-.99.99-2.59.99-3.58 0s-.99-2.59 0-3.57l6.52-5.42c-.68-1.65-.35-3.61.99-4.95 1.28-1.28 3.12-1.62 4.72-1.06l-2.89 2.89 2.82 2.82 2.86-2.87c.53 1.58.18 3.39-1.08 4.65zM3.81 16.21c.4.39 1.04.39 1.43 0 .4-.4.4-1.04 0-1.43-.39-.4-1.03-.4-1.43 0-.39.39-.39 1.03 0 1.43z';
          break;

        case 'admin-users':
          path = 'M10 9.25c-2.27 0-2.73-3.44-2.73-3.44C7 4.02 7.82 2 9.97 2c2.16 0 2.98 2.02 2.71 3.81 0 0-.41 3.44-2.68 3.44zm0 2.57L12.72 10c2.39 0 4.52 2.33 4.52 4.53v2.49s-3.65 1.13-7.24 1.13c-3.65 0-7.24-1.13-7.24-1.13v-2.49c0-2.25 1.94-4.48 4.47-4.48z';
          break;

        case 'album':
          path = 'M0 18h10v-.26c1.52.4 3.17.35 4.76-.24 4.14-1.52 6.27-6.12 4.75-10.26-1.43-3.89-5.58-6-9.51-4.98V2H0v16zM9 3v14H1V3h8zm5.45 8.22c-.68 1.35-2.32 1.9-3.67 1.23-.31-.15-.57-.35-.78-.59V8.13c.8-.86 2.11-1.13 3.22-.58 1.35.68 1.9 2.32 1.23 3.67zm-2.75-.82c.22.16.53.12.7-.1.16-.22.12-.53-.1-.7s-.53-.12-.7.1c-.16.21-.12.53.1.7zm3.01 3.67c-1.17.78-2.56.99-3.83.69-.27-.06-.44-.34-.37-.61s.34-.43.62-.36l.17.04c.96.17 1.98-.01 2.86-.59.47-.32.86-.72 1.14-1.18.15-.23.45-.3.69-.16.23.15.3.46.16.69-.36.57-.84 1.08-1.44 1.48zm1.05 1.57c-1.48.99-3.21 1.32-4.84 1.06-.28-.05-.47-.32-.41-.6.05-.27.32-.45.61-.39l.22.04c1.31.15 2.68-.14 3.87-.94.71-.47 1.27-1.07 1.7-1.74.14-.24.45-.31.68-.16.24.14.31.45.16.69-.49.79-1.16 1.49-1.99 2.04z';
          break;

        case 'align-center':
          path = 'M3 5h14V3H3v2zm12 8V7H5v6h10zM3 17h14v-2H3v2z';
          break;

        case 'align-full-width':
          path = 'M17 13V3H3v10h14zM5 17h10v-2H5v2z';
          break;

        case 'align-left':
          path = 'M3 5h14V3H3v2zm9 8V7H3v6h9zm2-4h3V7h-3v2zm0 4h3v-2h-3v2zM3 17h14v-2H3v2z';
          break;

        case 'align-none':
          path = 'M3 5h14V3H3v2zm10 8V7H3v6h10zM3 17h14v-2H3v2z';
          break;

        case 'align-pull-left':
          path = 'M9 16V4H3v12h6zm2-7h6V7h-6v2zm0 4h6v-2h-6v2z';
          break;

        case 'align-pull-right':
          path = 'M17 16V4h-6v12h6zM9 7H3v2h6V7zm0 4H3v2h6v-2z';
          break;

        case 'align-right':
          path = 'M3 5h14V3H3v2zm0 4h3V7H3v2zm14 4V7H8v6h9zM3 13h3v-2H3v2zm0 4h14v-2H3v2z';
          break;

        case 'align-wide':
          path = 'M5 5h10V3H5v2zm12 8V7H3v6h14zM5 17h10v-2H5v2z';
          break;

        case 'analytics':
          path = 'M18 18V2H2v16h16zM16 5H4V4h12v1zM7 7v3h3c0 1.66-1.34 3-3 3s-3-1.34-3-3 1.34-3 3-3zm1 2V7c1.1 0 2 .9 2 2H8zm8-1h-4V7h4v1zm0 3h-4V9h4v2zm0 2h-4v-1h4v1zm0 3H4v-1h12v1z';
          break;

        case 'archive':
          path = 'M19 4v2H1V4h18zM2 7h16v10H2V7zm11 3V9H7v1h6z';
          break;

        case 'arrow-down-alt':
          path = 'M9 2h2v12l4-4 2 1-7 7-7-7 2-1 4 4V2z';
          break;

        case 'arrow-down-alt2':
          path = 'M5 6l5 5 5-5 2 1-7 7-7-7z';
          break;

        case 'arrow-down':
          path = 'M15 8l-4.03 6L7 8h8z';
          break;

        case 'arrow-left-alt':
          path = 'M18 9v2H6l4 4-1 2-7-7 7-7 1 2-4 4h12z';
          break;

        case 'arrow-left-alt2':
          path = 'M14 5l-5 5 5 5-1 2-7-7 7-7z';
          break;

        case 'arrow-left':
          path = 'M13 14L7 9.97 13 6v8z';
          break;

        case 'arrow-right-alt':
          path = 'M2 11V9h12l-4-4 1-2 7 7-7 7-1-2 4-4H2z';
          break;

        case 'arrow-right-alt2':
          path = 'M6 15l5-5-5-5 1-2 7 7-7 7z';
          break;

        case 'arrow-right':
          path = 'M8 6l6 4.03L8 14V6z';
          break;

        case 'arrow-up-alt':
          path = 'M11 18H9V6l-4 4-2-1 7-7 7 7-2 1-4-4v12z';
          break;

        case 'arrow-up-alt2':
          path = 'M15 14l-5-5-5 5-2-1 7-7 7 7z';
          break;

        case 'arrow-up':
          path = 'M7 13l4.03-6L15 13H7z';
          break;

        case 'art':
          path = 'M8.55 3.06c1.01.34-1.95 2.01-.1 3.13 1.04.63 3.31-2.22 4.45-2.86.97-.54 2.67-.65 3.53 1.23 1.09 2.38.14 8.57-3.79 11.06-3.97 2.5-8.97 1.23-10.7-2.66-2.01-4.53 3.12-11.09 6.61-9.9zm1.21 6.45c.73 1.64 4.7-.5 3.79-2.8-.59-1.49-4.48 1.25-3.79 2.8z';
          break;

        case 'awards':
          path = 'M4.46 5.16L5 7.46l-.54 2.29 2.01 1.24L7.7 13l2.3-.54 2.3.54 1.23-2.01 2.01-1.24L15 7.46l.54-2.3-2-1.24-1.24-2.01-2.3.55-2.29-.54-1.25 2zm5.55 6.34C7.79 11.5 6 9.71 6 7.49c0-2.2 1.79-3.99 4.01-3.99 2.2 0 3.99 1.79 3.99 3.99 0 2.22-1.79 4.01-3.99 4.01zm-.02-1C8.33 10.5 7 9.16 7 7.5c0-1.65 1.33-3 2.99-3S13 5.85 13 7.5c0 1.66-1.35 3-3.01 3zm3.84 1.1l-1.28 2.24-2.08-.47L13 19.2l1.4-2.2h2.5zm-7.7.07l1.25 2.25 2.13-.51L7 19.2 5.6 17H3.1z';
          break;

        case 'backup':
          path = 'M13.65 2.88c3.93 2.01 5.48 6.84 3.47 10.77s-6.83 5.48-10.77 3.47c-1.87-.96-3.2-2.56-3.86-4.4l1.64-1.03c.45 1.57 1.52 2.95 3.08 3.76 3.01 1.54 6.69.35 8.23-2.66 1.55-3.01.36-6.69-2.65-8.24C9.78 3.01 6.1 4.2 4.56 7.21l1.88.97-4.95 3.08-.39-5.82 1.78.91C4.9 2.4 9.75.89 13.65 2.88zm-4.36 7.83C9.11 10.53 9 10.28 9 10c0-.07.03-.12.04-.19h-.01L10 5l.97 4.81L14 13l-4.5-2.12.02-.02c-.08-.04-.16-.09-.23-.15z';
          break;

        case 'block-default':
          path = 'M15 6V4h-3v2H8V4H5v2H4c-.6 0-1 .4-1 1v8h14V7c0-.6-.4-1-1-1h-1z';
          break;

        case 'book-alt':
          path = 'M5 17h13v2H5c-1.66 0-3-1.34-3-3V4c0-1.66 1.34-3 3-3h13v14H5c-.55 0-1 .45-1 1s.45 1 1 1zm2-3.5v-11c0-.28-.22-.5-.5-.5s-.5.22-.5.5v11c0 .28.22.5.5.5s.5-.22.5-.5z';
          break;

        case 'book':
          path = 'M16 3h2v16H5c-1.66 0-3-1.34-3-3V4c0-1.66 1.34-3 3-3h9v14H5c-.55 0-1 .45-1 1s.45 1 1 1h11V3z';
          break;

        case 'buddicons-activity':
          path = 'M8 1v7h2V6c0-1.52 1.45-3 3-3v.86c.55-.52 1.26-.86 2-.86v3h1c1.1 0 2 .9 2 2s-.9 2-2 2h-1v6c0 .55-.45 1-1 1s-1-.45-1-1v-2.18c-.31.11-.65.18-1 .18v2c0 .55-.45 1-1 1s-1-.45-1-1v-2H8v2c0 .55-.45 1-1 1s-1-.45-1-1v-2c-.35 0-.69-.07-1-.18V16c0 .55-.45 1-1 1s-1-.45-1-1v-4H2v-1c0-1.66 1.34-3 3-3h2V1h1zm5 7c.55 0 1-.45 1-1s-.45-1-1-1-1 .45-1 1 .45 1 1 1z';
          break;

        case 'buddicons-bbpress-logo':
          path = 'M8.5 12.6c.3-1.3 0-2.3-1.1-2.3-.8 0-1.6.6-1.8 1.5l-.3 1.7c-.3 1 .3 1.5 1 1.5 1.2 0 1.9-1.1 2.2-2.4zm-4-6.4C3.7 7.3 3.3 8.6 3.3 10c0 1 .2 1.9.6 2.8l1-4.6c.3-1.7.4-2-.4-2zm9.3 6.4c.3-1.3 0-2.3-1.1-2.3-.8 0-1.6.6-1.8 1.5l-.4 1.7c-.2 1.1.4 1.6 1.1 1.6 1.1-.1 1.9-1.2 2.2-2.5zM10 3.3c-2 0-3.9.9-5.1 2.3.6-.1 1.4-.2 1.8-.3.2 0 .2.1.2.2 0 .2-1 4.8-1 4.8.5-.3 1.2-.7 1.8-.7.9 0 1.5.4 1.9.9l.5-2.4c.4-1.6.4-1.9-.4-1.9-.4 0-.4-.5 0-.6.6-.1 1.8-.2 2.3-.3.2 0 .2.1.2.2l-1 4.8c.5-.4 1.2-.7 1.9-.7 1.7 0 2.5 1.3 2.1 3-.3 1.7-2 3-3.8 3-1.3 0-2.1-.7-2.3-1.4-.7.8-1.7 1.3-2.8 1.4 1.1.7 2.4 1.1 3.7 1.1 3.7 0 6.7-3 6.7-6.7s-3-6.7-6.7-6.7zM10 2c-4.4 0-8 3.6-8 8s3.6 8 8 8 8-3.6 8-8-3.6-8-8-8zm0 15.5c-2.1 0-4-.8-5.3-2.2-.3-.4-.7-.8-1-1.2-.7-1.2-1.2-2.6-1.2-4.1 0-4.1 3.4-7.5 7.5-7.5s7.5 3.4 7.5 7.5-3.4 7.5-7.5 7.5z';
          break;

        case 'buddicons-buddypress-logo':
          path = 'M10 0c5.52 0 10 4.48 10 10s-4.48 10-10 10S0 15.52 0 10 4.48 0 10 0zm0 .5C4.75.5.5 4.75.5 10s4.25 9.5 9.5 9.5 9.5-4.25 9.5-9.5S15.25.5 10 .5zm0 1c4.7 0 8.5 3.8 8.5 8.5s-3.8 8.5-8.5 8.5-8.5-3.8-8.5-8.5S5.3 1.5 10 1.5zm1.8 1.71c-.57 0-1.1.17-1.55.45 1.56.37 2.73 1.77 2.73 3.45 0 .69-.21 1.33-.55 1.87 1.31-.29 2.29-1.45 2.29-2.85 0-1.61-1.31-2.92-2.92-2.92zm-2.38 1c-1.61 0-2.92 1.31-2.92 2.93 0 1.61 1.31 2.92 2.92 2.92 1.62 0 2.93-1.31 2.93-2.92 0-1.62-1.31-2.93-2.93-2.93zm4.25 5.01l-.51.59c2.34.69 2.45 3.61 2.45 3.61h1.28c0-4.71-3.22-4.2-3.22-4.2zm-2.1.8l-2.12 2.09-2.12-2.09C3.12 10.24 3.89 15 3.89 15h11.08c.47-4.98-3.4-4.98-3.4-4.98z';
          break;

        case 'buddicons-community':
          path = 'M9 3c0-.67-.47-1.43-1-2-.5.5-1 1.38-1 2 0 .48.45 1 1 1s1-.47 1-1zm4 0c0-.67-.47-1.43-1-2-.5.5-1 1.38-1 2 0 .48.45 1 1 1s1-.47 1-1zM9 9V5.5c0-.55-.45-1-1-1-.57 0-1 .49-1 1V9c0 .55.45 1 1 1 .57 0 1-.49 1-1zm4 0V5.5c0-.55-.45-1-1-1-.57 0-1 .49-1 1V9c0 .55.45 1 1 1 .57 0 1-.49 1-1zm4 1c0-1.48-1.41-2.77-3.5-3.46V9c0 .83-.67 1.5-1.5 1.5s-1.5-.67-1.5-1.5V6.01c-.17 0-.33-.01-.5-.01s-.33.01-.5.01V9c0 .83-.67 1.5-1.5 1.5S6.5 9.83 6.5 9V6.54C4.41 7.23 3 8.52 3 10c0 1.41.95 2.65 3.21 3.37 1.11.35 2.39 1.12 3.79 1.12s2.69-.78 3.79-1.13C16.04 12.65 17 11.41 17 10zm-7 5.43c1.43 0 2.74-.79 3.88-1.11 1.9-.53 2.49-1.34 3.12-2.32v3c0 2.21-3.13 4-7 4s-7-1.79-7-4v-3c.64.99 1.32 1.8 3.15 2.33 1.13.33 2.44 1.1 3.85 1.1z';
          break;

        case 'buddicons-forums':
          path = 'M13.5 7h-7C5.67 7 5 6.33 5 5.5S5.67 4 6.5 4h1.59C8.04 3.84 8 3.68 8 3.5 8 2.67 8.67 2 9.5 2h1c.83 0 1.5.67 1.5 1.5 0 .18-.04.34-.09.5h1.59c.83 0 1.5.67 1.5 1.5S14.33 7 13.5 7zM4 8h12c.55 0 1 .45 1 1s-.45 1-1 1H4c-.55 0-1-.45-1-1s.45-1 1-1zm1 3h10c.55 0 1 .45 1 1s-.45 1-1 1H5c-.55 0-1-.45-1-1s.45-1 1-1zm2 3h6c.55 0 1 .45 1 1s-.45 1-1 1h-1.09c.05.16.09.32.09.5 0 .83-.67 1.5-1.5 1.5h-1c-.83 0-1.5-.67-1.5-1.5 0-.18.04-.34.09-.5H7c-.55 0-1-.45-1-1s.45-1 1-1z';
          break;

        case 'buddicons-friends':
          path = 'M8.75 5.77C8.75 4.39 7 2 7 2S5.25 4.39 5.25 5.77 5.9 7.5 7 7.5s1.75-.35 1.75-1.73zm6 0C14.75 4.39 13 2 13 2s-1.75 2.39-1.75 3.77S11.9 7.5 13 7.5s1.75-.35 1.75-1.73zM9 17V9c0-.55-.45-1-1-1H6c-.55 0-1 .45-1 1v8c0 .55.45 1 1 1h2c.55 0 1-.45 1-1zm6 0V9c0-.55-.45-1-1-1h-2c-.55 0-1 .45-1 1v8c0 .55.45 1 1 1h2c.55 0 1-.45 1-1zm-9-6l2-1v2l-2 1v-2zm6 0l2-1v2l-2 1v-2zm-6 3l2-1v2l-2 1v-2zm6 0l2-1v2l-2 1v-2z';
          break;

        case 'buddicons-groups':
          path = 'M15.45 6.25c1.83.94 1.98 3.18.7 4.98-.8 1.12-2.33 1.88-3.46 1.78L10.05 18H9l-2.65-4.99c-1.13.16-2.73-.63-3.55-1.79-1.28-1.8-1.13-4.04.71-4.97.48-.24.96-.33 1.43-.31-.01.4.01.8.07 1.21.26 1.69 1.41 3.53 2.86 4.37-.19.55-.49.99-.88 1.25L9 16.58v-5.66C7.64 10.55 6.26 8.76 6 7c-.4-2.65 1-5 3.5-5s3.9 2.35 3.5 5c-.26 1.76-1.64 3.55-3 3.92v5.77l2.07-3.84c-.44-.23-.77-.71-.99-1.3 1.48-.83 2.65-2.69 2.91-4.4.06-.41.08-.82.07-1.22.46-.01.92.08 1.39.32z';
          break;

        case 'buddicons-pm':
          path = 'M10 2c3 0 8 5 8 5v11H2V7s5-5 8-5zm7 14.72l-3.73-2.92L17 11l-.43-.37-2.26 1.3.24-4.31-8.77-.52-.46 4.54-1.99-.95L3 11l3.73 2.8-3.44 2.85.4.43L10 13l6.53 4.15z';
          break;

        case 'buddicons-replies':
          path = 'M17.54 10.29c1.17 1.17 1.17 3.08 0 4.25-1.18 1.17-3.08 1.17-4.25 0l-.34-.52c0 3.66-2 4.38-2.95 4.98-.82-.6-2.95-1.28-2.95-4.98l-.34.52c-1.17 1.17-3.07 1.17-4.25 0-1.17-1.17-1.17-3.08 0-4.25 0 0 1.02-.67 2.1-1.3C3.71 7.84 3.2 6.42 3.2 4.88c0-.34.03-.67.08-1C3.53 5.66 4.47 7.22 5.8 8.3c.67-.35 1.85-.83 2.37-.92H8c-1.1 0-2-.9-2-2s.9-2 2-2v-.5c0-.28.22-.5.5-.5s.5.22.5.5v.5h2v-.5c0-.28.22-.5.5-.5s.5.22.5.5v.5c1.1 0 2 .9 2 2s-.9 2-2 2h-.17c.51.09 1.78.61 2.38.92 1.33-1.08 2.27-2.64 2.52-4.42.05.33.08.66.08 1 0 1.54-.51 2.96-1.36 4.11 1.08.63 2.09 1.3 2.09 1.3zM8.5 6.38c.5 0 1-.45 1-1s-.45-1-1-1-1 .45-1 1 .45 1 1 1zm3-2c-.55 0-1 .45-1 1s.45 1 1 1 1-.45 1-1-.45-1-1-1zm-2.3 5.73c-.12.11-.19.26-.19.43.02.25.23.46.49.46h1c.26 0 .47-.21.49-.46 0-.15-.07-.29-.19-.43-.08-.06-.18-.11-.3-.11h-1c-.12 0-.22.05-.3.11zM12 12.5c0-.12-.06-.28-.19-.38-.09-.07-.19-.12-.31-.12h-3c-.12 0-.22.05-.31.12-.11.1-.19.25-.19.38 0 .28.22.5.5.5h3c.28 0 .5-.22.5-.5zM8.5 15h3c.28 0 .5-.22.5-.5s-.22-.5-.5-.5h-3c-.28 0-.5.22-.5.5s.22.5.5.5zm1 2h1c.28 0 .5-.22.5-.5s-.22-.5-.5-.5h-1c-.28 0-.5.22-.5.5s.22.5.5.5z';
          break;

        case 'buddicons-topics':
          path = 'M10.44 1.66c-.59-.58-1.54-.58-2.12 0L2.66 7.32c-.58.58-.58 1.53 0 2.12.6.6 1.56.56 2.12 0l5.66-5.66c.58-.58.59-1.53 0-2.12zm2.83 2.83c-.59-.59-1.54-.59-2.12 0l-5.66 5.66c-.59.58-.59 1.53 0 2.12.6.6 1.56.55 2.12 0l5.66-5.66c.58-.58.58-1.53 0-2.12zm1.06 6.72l4.18 4.18c.59.58.59 1.53 0 2.12s-1.54.59-2.12 0l-4.18-4.18-1.77 1.77c-.59.58-1.54.58-2.12 0-.59-.59-.59-1.54 0-2.13l5.66-5.65c.58-.59 1.53-.59 2.12 0 .58.58.58 1.53 0 2.12zM5 15c0-1.59-1.66-4-1.66-4S2 13.78 2 15s.6 2 1.34 2h.32C4.4 17 5 16.59 5 15z';
          break;

        case 'buddicons-tracking':
          path = 'M10.98 6.78L15.5 15c-1 2-3.5 3-5.5 3s-4.5-1-5.5-3L9 6.82c-.75-1.23-2.28-1.98-4.29-2.03l2.46-2.92c1.68 1.19 2.46 2.32 2.97 3.31.56-.87 1.2-1.68 2.7-2.12l1.83 2.86c-1.42-.34-2.64.08-3.69.86zM8.17 10.4l-.93 1.69c.49.11 1 .16 1.54.16 1.35 0 2.58-.36 3.55-.95l-1.01-1.82c-.87.53-1.96.86-3.15.92zm.86 5.38c1.99 0 3.73-.74 4.74-1.86l-.98-1.76c-1 1.12-2.74 1.87-4.74 1.87-.62 0-1.21-.08-1.76-.21l-.63 1.15c.94.5 2.1.81 3.37.81z';
          break;

        case 'building':
          path = 'M3 20h14V0H3v20zM7 3H5V1h2v2zm4 0H9V1h2v2zm4 0h-2V1h2v2zM7 6H5V4h2v2zm4 0H9V4h2v2zm4 0h-2V4h2v2zM7 9H5V7h2v2zm4 0H9V7h2v2zm4 0h-2V7h2v2zm-8 3H5v-2h2v2zm4 0H9v-2h2v2zm4 0h-2v-2h2v2zm-4 7H5v-6h6v6zm4-4h-2v-2h2v2zm0 3h-2v-2h2v2z';
          break;

        case 'businessman':
          path = 'M7.3 6l-.03-.19c-.04-.37-.05-.73-.03-1.08.02-.36.1-.71.25-1.04.14-.32.31-.61.52-.86s.49-.46.83-.6c.34-.15.72-.23 1.13-.23.69 0 1.26.2 1.71.59s.76.87.91 1.44.18 1.16.09 1.78l-.03.19c-.01.09-.05.25-.11.48-.05.24-.12.47-.2.69-.08.21-.19.45-.34.72-.14.27-.3.49-.47.69-.18.19-.4.34-.67.48-.27.13-.55.19-.86.19s-.59-.06-.87-.19c-.26-.13-.49-.29-.67-.5-.18-.2-.34-.42-.49-.66-.15-.25-.26-.49-.34-.73-.09-.25-.16-.47-.21-.67-.06-.21-.1-.37-.12-.5zm9.2 6.24c.41.7.5 1.41.5 2.14v2.49c0 .03-.12.08-.29.13-.18.04-.42.13-.97.27-.55.12-1.1.24-1.65.34s-1.19.19-1.95.27c-.75.08-1.46.12-2.13.12-.68 0-1.39-.04-2.14-.12-.75-.07-1.4-.17-1.98-.27-.58-.11-1.08-.23-1.56-.34-.49-.11-.8-.21-1.06-.29L3 16.87v-2.49c0-.75.07-1.46.46-2.15s.81-1.25 1.5-1.68C5.66 10.12 7.19 10 8 10l1.67 1.67L9 13v3l1.02 1.08L11 16v-3l-.68-1.33L11.97 10c.77 0 2.2.07 2.9.52.71.45 1.21 1.02 1.63 1.72z';
          break;

        case 'button':
          path = 'M17 5H3c-1.1 0-2 .9-2 2v6c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2zm1 7c0 .6-.4 1-1 1H3c-.6 0-1-.4-1-1V7c0-.6.4-1 1-1h14c.6 0 1 .4 1 1v5z';
          break;

        case 'calendar-alt':
          path = 'M15 4h3v15H2V4h3V3c0-.41.15-.76.44-1.06.29-.29.65-.44 1.06-.44s.77.15 1.06.44c.29.3.44.65.44 1.06v1h4V3c0-.41.15-.76.44-1.06.29-.29.65-.44 1.06-.44s.77.15 1.06.44c.29.3.44.65.44 1.06v1zM6 3v2.5c0 .14.05.26.15.36.09.09.21.14.35.14s.26-.05.35-.14c.1-.1.15-.22.15-.36V3c0-.14-.05-.26-.15-.35-.09-.1-.21-.15-.35-.15s-.26.05-.35.15c-.1.09-.15.21-.15.35zm7 0v2.5c0 .14.05.26.14.36.1.09.22.14.36.14s.26-.05.36-.14c.09-.1.14-.22.14-.36V3c0-.14-.05-.26-.14-.35-.1-.1-.22-.15-.36-.15s-.26.05-.36.15c-.09.09-.14.21-.14.35zm4 15V8H3v10h14zM7 9v2H5V9h2zm2 0h2v2H9V9zm4 2V9h2v2h-2zm-6 1v2H5v-2h2zm2 0h2v2H9v-2zm4 2v-2h2v2h-2zm-6 1v2H5v-2h2zm4 2H9v-2h2v2zm4 0h-2v-2h2v2z';
          break;

        case 'calendar':
          path = 'M15 4h3v14H2V4h3V3c0-.83.67-1.5 1.5-1.5S8 2.17 8 3v1h4V3c0-.83.67-1.5 1.5-1.5S15 2.17 15 3v1zM6 3v2.5c0 .28.22.5.5.5s.5-.22.5-.5V3c0-.28-.22-.5-.5-.5S6 2.72 6 3zm7 0v2.5c0 .28.22.5.5.5s.5-.22.5-.5V3c0-.28-.22-.5-.5-.5s-.5.22-.5.5zm4 14V8H3v9h14zM7 16V9H5v7h2zm4 0V9H9v7h2zm4 0V9h-2v7h2z';
          break;

        case 'camera':
          path = 'M6 5V3H3v2h3zm12 10V4H9L7 6H2v9h16zm-7-8c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3z';
          break;

        case 'carrot':
          path = 'M2 18.43c1.51 1.36 11.64-4.67 13.14-7.21.72-1.22-.13-3.01-1.52-4.44C15.2 5.73 16.59 9 17.91 8.31c.6-.32.99-1.31.7-1.92-.52-1.08-2.25-1.08-3.42-1.21.83-.2 2.82-1.05 2.86-2.25.04-.92-1.13-1.97-2.05-1.86-1.21.14-1.65 1.88-2.06 3-.05-.71-.2-2.27-.98-2.95-1.04-.91-2.29-.05-2.32 1.05-.04 1.33 2.82 2.07 1.92 3.67C11.04 4.67 9.25 4.03 8.1 4.7c-.49.31-1.05.91-1.63 1.69.89.94 2.12 2.07 3.09 2.72.2.14.26.42.11.62-.14.21-.42.26-.62.12-.99-.67-2.2-1.78-3.1-2.71-.45.67-.91 1.43-1.34 2.23.85.86 1.93 1.83 2.79 2.41.2.14.25.42.11.62-.14.21-.42.26-.63.12-.85-.58-1.86-1.48-2.71-2.32C2.4 13.69 1.1 17.63 2 18.43z';
          break;

        case 'cart':
          path = 'M6 13h9c.55 0 1 .45 1 1s-.45 1-1 1H5c-.55 0-1-.45-1-1V4H2c-.55 0-1-.45-1-1s.45-1 1-1h3c.55 0 1 .45 1 1v2h13l-4 7H6v1zm-.5 3c.83 0 1.5.67 1.5 1.5S6.33 19 5.5 19 4 18.33 4 17.5 4.67 16 5.5 16zm9 0c.83 0 1.5.67 1.5 1.5s-.67 1.5-1.5 1.5-1.5-.67-1.5-1.5.67-1.5 1.5-1.5z';
          break;

        case 'category':
          path = 'M5 7h13v10H2V4h7l2 2H4v9h1V7z';
          break;

        case 'chart-area':
          path = 'M18 18l.01-12.28c.59-.35.99-.99.99-1.72 0-1.1-.9-2-2-2s-2 .9-2 2c0 .8.47 1.48 1.14 1.8l-4.13 6.58c-.33-.24-.73-.38-1.16-.38-.84 0-1.55.51-1.85 1.24l-2.14-1.53c.09-.22.14-.46.14-.71 0-1.11-.89-2-2-2-1.1 0-2 .89-2 2 0 .73.4 1.36.98 1.71L1 18h17zM17 3c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zM5 10c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zm5.85 3c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1z';
          break;

        case 'chart-bar':
          path = 'M18 18V2h-4v16h4zm-6 0V7H8v11h4zm-6 0v-8H2v8h4z';
          break;

        case 'chart-line':
          path = 'M18 3.5c0 .62-.38 1.16-.92 1.38v13.11H1.99l4.22-6.73c-.13-.23-.21-.48-.21-.76C6 9.67 6.67 9 7.5 9S9 9.67 9 10.5c0 .13-.02.25-.05.37l1.44.63c.27-.3.67-.5 1.11-.5.18 0 .35.04.51.09l3.58-6.41c-.36-.27-.59-.7-.59-1.18 0-.83.67-1.5 1.5-1.5.19 0 .36.04.53.1l.05-.09v.11c.54.22.92.76.92 1.38zm-1.92 13.49V5.85l-3.29 5.89c.13.23.21.48.21.76 0 .83-.67 1.5-1.5 1.5s-1.5-.67-1.5-1.5l.01-.07-1.63-.72c-.25.18-.55.29-.88.29-.18 0-.35-.04-.51-.1l-3.2 5.09h12.29z';
          break;

        case 'chart-pie':
          path = 'M10 10V3c3.87 0 7 3.13 7 7h-7zM9 4v7h7c0 3.87-3.13 7-7 7s-7-3.13-7-7 3.13-7 7-7z';
          break;

        case 'clipboard':
          path = 'M11.9.39l1.4 1.4c1.61.19 3.5-.74 4.61.37s.18 3 .37 4.61l1.4 1.4c.39.39.39 1.02 0 1.41l-9.19 9.2c-.4.39-1.03.39-1.42 0L1.29 11c-.39-.39-.39-1.02 0-1.42l9.2-9.19c.39-.39 1.02-.39 1.41 0zm.58 2.25l-.58.58 4.95 4.95.58-.58c-.19-.6-.2-1.22-.15-1.82.02-.31.05-.62.09-.92.12-1 .18-1.63-.17-1.98s-.98-.29-1.98-.17c-.3.04-.61.07-.92.09-.6.05-1.22.04-1.82-.15zm4.02.93c.39.39.39 1.03 0 1.42s-1.03.39-1.42 0-.39-1.03 0-1.42 1.03-.39 1.42 0zm-6.72.36l-.71.7L15.44 11l.7-.71zM8.36 5.34l-.7.71 6.36 6.36.71-.7zM6.95 6.76l-.71.7 6.37 6.37.7-.71zM5.54 8.17l-.71.71 6.36 6.36.71-.71zM4.12 9.58l-.71.71 6.37 6.37.71-.71z';
          break;

        case 'clock':
          path = 'M10 2c4.42 0 8 3.58 8 8s-3.58 8-8 8-8-3.58-8-8 3.58-8 8-8zm0 14c3.31 0 6-2.69 6-6s-2.69-6-6-6-6 2.69-6 6 2.69 6 6 6zm-.71-5.29c.07.05.14.1.23.15l-.02.02L14 13l-3.03-3.19L10 5l-.97 4.81h.01c0 .02-.01.05-.02.09S9 9.97 9 10c0 .28.1.52.29.71z';
          break;

        case 'cloud-saved':
          path = 'M14.8 9c.1-.3.2-.6.2-1 0-2.2-1.8-4-4-4-1.5 0-2.9.9-3.5 2.2-.3-.1-.7-.2-1-.2C5.1 6 4 7.1 4 8.5c0 .2 0 .4.1.5-1.8.3-3.1 1.7-3.1 3.5C1 14.4 2.6 16 4.5 16h10c1.9 0 3.5-1.6 3.5-3.5 0-1.8-1.4-3.3-3.2-3.5zm-6.3 5.9l-3.2-3.2 1.4-1.4 1.8 1.8 3.8-3.8 1.4 1.4-5.2 5.2z';
          break;

        case 'cloud-upload':
          path = 'M14.8 9c.1-.3.2-.6.2-1 0-2.2-1.8-4-4-4-1.5 0-2.9.9-3.5 2.2-.3-.1-.7-.2-1-.2C5.1 6 4 7.1 4 8.5c0 .2 0 .4.1.5-1.8.3-3.1 1.7-3.1 3.5C1 14.4 2.6 16 4.5 16H8v-3H5l4.5-4.5L14 13h-3v3h3.5c1.9 0 3.5-1.6 3.5-3.5 0-1.8-1.4-3.3-3.2-3.5z';
          break;

        case 'cloud':
          path = 'M14.9 9c1.8.2 3.1 1.7 3.1 3.5 0 1.9-1.6 3.5-3.5 3.5h-10C2.6 16 1 14.4 1 12.5 1 10.7 2.3 9.3 4.1 9 4 8.9 4 8.7 4 8.5 4 7.1 5.1 6 6.5 6c.3 0 .7.1.9.2C8.1 4.9 9.4 4 11 4c2.2 0 4 1.8 4 4 0 .4-.1.7-.1 1z';
          break;

        case 'columns':
          path = 'M3 15h6V5H3v10zm8 0h6V5h-6v10z';
          break;

        case 'controls-back':
          path = 'M2 10l10-6v3.6L18 4v12l-6-3.6V16z';
          break;

        case 'controls-forward':
          path = 'M18 10L8 16v-3.6L2 16V4l6 3.6V4z';
          break;

        case 'controls-pause':
          path = 'M5 16V4h3v12H5zm7-12h3v12h-3V4z';
          break;

        case 'controls-play':
          path = 'M5 4l10 6-10 6V4z';
          break;

        case 'controls-repeat':
          path = 'M5 7v3l-2 1.5V5h11V3l4 3.01L14 9V7H5zm10 6v-3l2-1.5V15H6v2l-4-3.01L6 11v2h9z';
          break;

        case 'controls-skipback':
          path = 'M11.98 7.63l6-3.6v12l-6-3.6v3.6l-8-4.8v4.8h-2v-12h2v4.8l8-4.8v3.6z';
          break;

        case 'controls-skipforward':
          path = 'M8 12.4L2 16V4l6 3.6V4l8 4.8V4h2v12h-2v-4.8L8 16v-3.6z';
          break;

        case 'controls-volumeoff':
          path = 'M2 7h4l5-4v14l-5-4H2V7z';
          break;

        case 'controls-volumeon':
          path = 'M2 7h4l5-4v14l-5-4H2V7zm12.69-2.46C14.82 4.59 18 5.92 18 10s-3.18 5.41-3.31 5.46c-.06.03-.13.04-.19.04-.2 0-.39-.12-.46-.31-.11-.26.02-.55.27-.65.11-.05 2.69-1.15 2.69-4.54 0-3.41-2.66-4.53-2.69-4.54-.25-.1-.38-.39-.27-.65.1-.25.39-.38.65-.27zM16 10c0 2.57-2.23 3.43-2.32 3.47-.06.02-.12.03-.18.03-.2 0-.39-.12-.47-.32-.1-.26.04-.55.29-.65.07-.02 1.68-.67 1.68-2.53s-1.61-2.51-1.68-2.53c-.25-.1-.38-.39-.29-.65.1-.25.39-.39.65-.29.09.04 2.32.9 2.32 3.47z';
          break;

        case 'cover-image':
          path = 'M2.2 1h15.5c.7 0 1.3.6 1.3 1.2v11.5c0 .7-.6 1.2-1.2 1.2H2.2c-.6.1-1.2-.5-1.2-1.1V2.2C1 1.6 1.6 1 2.2 1zM17 13V3H3v10h14zm-4-4s0-5 3-5v7c0 .6-.4 1-1 1H5c-.6 0-1-.4-1-1V7c2 0 3 4 3 4s1-4 3-4 3 2 3 2zM4 17h12v2H4z';
          break;

        case 'dashboard':
          path = 'M3.76 16h12.48c1.1-1.37 1.76-3.11 1.76-5 0-4.42-3.58-8-8-8s-8 3.58-8 8c0 1.89.66 3.63 1.76 5zM10 4c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zM6 6c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zm8 0c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zm-5.37 5.55L12 7v6c0 1.1-.9 2-2 2s-2-.9-2-2c0-.57.24-1.08.63-1.45zM4 10c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zm12 0c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zm-5 3c0-.55-.45-1-1-1s-1 .45-1 1 .45 1 1 1 1-.45 1-1z';
          break;

        case 'desktop':
          path = 'M3 2h14c.55 0 1 .45 1 1v10c0 .55-.45 1-1 1h-5v2h2c.55 0 1 .45 1 1v1H5v-1c0-.55.45-1 1-1h2v-2H3c-.55 0-1-.45-1-1V3c0-.55.45-1 1-1zm13 9V4H4v7h12zM5 5h9L5 9V5z';
          break;

        case 'dismiss':
          path = 'M10 2c4.42 0 8 3.58 8 8s-3.58 8-8 8-8-3.58-8-8 3.58-8 8-8zm5 11l-3-3 3-3-2-2-3 3-3-3-2 2 3 3-3 3 2 2 3-3 3 3z';
          break;

        case 'download':
          path = 'M14.01 4v6h2V2H4v8h2.01V4h8zm-2 2v6h3l-5 6-5-6h3V6h4z';
          break;

        case 'edit':
          path = 'M13.89 3.39l2.71 2.72c.46.46.42 1.24.03 1.64l-8.01 8.02-5.56 1.16 1.16-5.58s7.6-7.63 7.99-8.03c.39-.39 1.22-.39 1.68.07zm-2.73 2.79l-5.59 5.61 1.11 1.11 5.54-5.65zm-2.97 8.23l5.58-5.6-1.07-1.08-5.59 5.6z';
          break;

        case 'editor-aligncenter':
          path = 'M14 5V3H6v2h8zm3 4V7H3v2h14zm-3 4v-2H6v2h8zm3 4v-2H3v2h14z';
          break;

        case 'editor-alignleft':
          path = 'M12 5V3H3v2h9zm5 4V7H3v2h14zm-5 4v-2H3v2h9zm5 4v-2H3v2h14z';
          break;

        case 'editor-alignright':
          path = 'M17 5V3H8v2h9zm0 4V7H3v2h14zm0 4v-2H8v2h9zm0 4v-2H3v2h14z';
          break;

        case 'editor-bold':
          path = 'M6 4v13h4.54c1.37 0 2.46-.33 3.26-1 .8-.66 1.2-1.58 1.2-2.77 0-.84-.17-1.51-.51-2.01s-.9-.85-1.67-1.03v-.09c.57-.1 1.02-.4 1.36-.9s.51-1.13.51-1.91c0-1.14-.39-1.98-1.17-2.5C12.75 4.26 11.5 4 9.78 4H6zm2.57 5.15V6.26h1.36c.73 0 1.27.11 1.61.32.34.22.51.58.51 1.07 0 .54-.16.92-.47 1.15s-.82.35-1.51.35h-1.5zm0 2.19h1.6c1.44 0 2.16.53 2.16 1.61 0 .6-.17 1.05-.51 1.34s-.86.43-1.57.43H8.57v-3.38z';
          break;

        case 'editor-break':
          path = 'M16 4h2v9H7v3l-5-4 5-4v3h9V4z';
          break;

        case 'editor-code':
          path = 'M9 6l-4 4 4 4-1 2-6-6 6-6zm2 8l4-4-4-4 1-2 6 6-6 6z';
          break;

        case 'editor-contract':
          path = 'M15.75 6.75L18 3v14l-2.25-3.75L17 12h-4v4l1.25-1.25L18 17H2l3.75-2.25L7 16v-4H3l1.25 1.25L2 17V3l2.25 3.75L3 8h4V4L5.75 5.25 2 3h16l-3.75 2.25L13 4v4h4z';
          break;

        case 'editor-customchar':
          path = 'M10 5.4c1.27 0 2.24.36 2.91 1.08.66.71 1 1.76 1 3.13 0 1.28-.23 2.37-.69 3.27-.47.89-1.27 1.52-2.22 2.12v2h6v-2h-3.69c.92-.64 1.62-1.34 2.12-2.34.49-1.01.74-2.13.74-3.35 0-1.78-.55-3.19-1.65-4.22S11.92 3.54 10 3.54s-3.43.53-4.52 1.57c-1.1 1.04-1.65 2.44-1.65 4.2 0 1.21.24 2.31.73 3.33.48 1.01 1.19 1.71 2.1 2.36H3v2h6v-2c-.98-.64-1.8-1.28-2.24-2.17-.45-.89-.67-1.96-.67-3.22 0-1.37.33-2.41 1-3.13C7.75 5.76 8.72 5.4 10 5.4z';
          break;

        case 'editor-expand':
          path = 'M7 8h6v4H7zm-5 5v4h4l-1.2-1.2L7 12l-3.8 2.2M14 17h4v-4l-1.2 1.2L13 12l2.2 3.8M14 3l1.3 1.3L13 8l3.8-2.2L18 7V3M6 3H2v4l1.2-1.2L7 8 4.7 4.3';
          break;

        case 'editor-help':
          path = 'M17 10c0-3.87-3.14-7-7-7-3.87 0-7 3.13-7 7s3.13 7 7 7c3.86 0 7-3.13 7-7zm-6.3 1.48H9.14v-.43c0-.38.08-.7.24-.98s.46-.57.88-.89c.41-.29.68-.53.81-.71.14-.18.2-.39.2-.62 0-.25-.09-.44-.28-.58-.19-.13-.45-.19-.79-.19-.58 0-1.25.19-2 .57l-.64-1.28c.87-.49 1.8-.74 2.77-.74.81 0 1.45.2 1.92.58.48.39.71.91.71 1.55 0 .43-.09.8-.29 1.11-.19.32-.57.67-1.11 1.06-.38.28-.61.49-.71.63-.1.15-.15.34-.15.57v.35zm-1.47 2.74c-.18-.17-.27-.42-.27-.73 0-.33.08-.58.26-.75s.43-.25.77-.25c.32 0 .57.09.75.26s.27.42.27.74c0 .3-.09.55-.27.72-.18.18-.43.27-.75.27-.33 0-.58-.09-.76-.26z';
          break;

        case 'editor-indent':
          path = 'M3 5V3h9v2H3zm10-1V3h4v1h-4zm0 3h2V5l4 3.5-4 3.5v-2h-2V7zM3 8V6h9v2H3zm2 3V9h7v2H5zm-2 3v-2h9v2H3zm10 0v-1h4v1h-4zm-4 3v-2h3v2H9z';
          break;

        case 'editor-insertmore':
          path = 'M17 7V3H3v4h14zM6 11V9H3v2h3zm6 0V9H8v2h4zm5 0V9h-3v2h3zm0 6v-4H3v4h14z';
          break;

        case 'editor-italic':
          path = 'M14.78 6h-2.13l-2.8 9h2.12l-.62 2H4.6l.62-2h2.14l2.8-9H8.03l.62-2h6.75z';
          break;

        case 'editor-justify':
          path = 'M2 3h16v2H2V3zm0 4h16v2H2V7zm0 4h16v2H2v-2zm0 4h16v2H2v-2z';
          break;

        case 'editor-kitchensink':
          path = 'M19 2v6H1V2h18zm-1 5V3H2v4h16zM5 4v2H3V4h2zm3 0v2H6V4h2zm3 0v2H9V4h2zm3 0v2h-2V4h2zm3 0v2h-2V4h2zm2 5v9H1V9h18zm-1 8v-7H2v7h16zM5 11v2H3v-2h2zm3 0v2H6v-2h2zm3 0v2H9v-2h2zm6 0v2h-5v-2h5zm-6 3v2H3v-2h8zm3 0v2h-2v-2h2zm3 0v2h-2v-2h2z';
          break;

        case 'editor-ltr':
          path = 'M5.52 2h7.43c.55 0 1 .45 1 1s-.45 1-1 1h-1v13c0 .55-.45 1-1 1s-1-.45-1-1V5c0-.55-.45-1-1-1s-1 .45-1 1v12c0 .55-.45 1-1 1s-1-.45-1-1v-5.96h-.43C3.02 11.04 1 9.02 1 6.52S3.02 2 5.52 2zM14 14l5-4-5-4v8z';
          break;

        case 'editor-ol-rtl':
          path = 'M15.025 8.75a1.048 1.048 0 0 1 .45-.1.507.507 0 0 1 .35.11.455.455 0 0 1 .13.36.803.803 0 0 1-.06.3 1.448 1.448 0 0 1-.19.33c-.09.11-.29.32-.58.62l-.99 1v.58h2.76v-.7h-1.72v-.04l.51-.48a7.276 7.276 0 0 0 .7-.71 1.75 1.75 0 0 0 .3-.49 1.254 1.254 0 0 0 .1-.51.968.968 0 0 0-.16-.56 1.007 1.007 0 0 0-.44-.37 1.512 1.512 0 0 0-.65-.14 1.98 1.98 0 0 0-.51.06 1.9 1.9 0 0 0-.42.15 3.67 3.67 0 0 0-.48.35l.45.54a2.505 2.505 0 0 1 .45-.3zM16.695 15.29a1.29 1.29 0 0 0-.74-.3v-.02a1.203 1.203 0 0 0 .65-.37.973.973 0 0 0 .23-.65.81.81 0 0 0-.37-.71 1.72 1.72 0 0 0-1-.26 2.185 2.185 0 0 0-1.33.4l.4.6a1.79 1.79 0 0 1 .46-.23 1.18 1.18 0 0 1 .41-.07c.38 0 .58.15.58.46a.447.447 0 0 1-.22.43 1.543 1.543 0 0 1-.7.12h-.31v.66h.31a1.764 1.764 0 0 1 .75.12.433.433 0 0 1 .23.41.55.55 0 0 1-.2.47 1.084 1.084 0 0 1-.63.15 2.24 2.24 0 0 1-.57-.08 2.671 2.671 0 0 1-.52-.2v.74a2.923 2.923 0 0 0 1.18.22 1.948 1.948 0 0 0 1.22-.33 1.077 1.077 0 0 0 .43-.92.836.836 0 0 0-.26-.64zM15.005 4.17c.06-.05.16-.14.3-.28l-.02.42V7h.84V3h-.69l-1.29 1.03.4.51zM4.02 5h9v1h-9zM4.02 10h9v1h-9zM4.02 15h9v1h-9z';
          break;

        case 'editor-ol':
          path = 'M6 7V3h-.69L4.02 4.03l.4.51.46-.37c.06-.05.16-.14.3-.28l-.02.42V7H6zm2-2h9v1H8V5zm-1.23 6.95v-.7H5.05v-.04l.51-.48c.33-.31.57-.54.7-.71.14-.17.24-.33.3-.49.07-.16.1-.33.1-.51 0-.21-.05-.4-.16-.56-.1-.16-.25-.28-.44-.37s-.41-.14-.65-.14c-.19 0-.36.02-.51.06-.15.03-.29.09-.42.15-.12.07-.29.19-.48.35l.45.54c.16-.13.31-.23.45-.3.15-.07.3-.1.45-.1.14 0 .26.03.35.11s.13.2.13.36c0 .1-.02.2-.06.3s-.1.21-.19.33c-.09.11-.29.32-.58.62l-.99 1v.58h2.76zM8 10h9v1H8v-1zm-1.29 3.95c0-.3-.12-.54-.37-.71-.24-.17-.58-.26-1-.26-.52 0-.96.13-1.33.4l.4.6c.17-.11.32-.19.46-.23.14-.05.27-.07.41-.07.38 0 .58.15.58.46 0 .2-.07.35-.22.43s-.38.12-.7.12h-.31v.66h.31c.34 0 .59.04.75.12.15.08.23.22.23.41 0 .22-.07.37-.2.47-.14.1-.35.15-.63.15-.19 0-.38-.03-.57-.08s-.36-.12-.52-.2v.74c.34.15.74.22 1.18.22.53 0 .94-.11 1.22-.33.29-.22.43-.52.43-.92 0-.27-.09-.48-.26-.64s-.42-.26-.74-.3v-.02c.27-.06.49-.19.65-.37.15-.18.23-.39.23-.65zM8 15h9v1H8v-1z';
          break;

        case 'editor-outdent':
          path = 'M7 4V3H3v1h4zm10 1V3H8v2h9zM7 7H5V5L1 8.5 5 12v-2h2V7zm10 1V6H8v2h9zm-2 3V9H8v2h7zm2 3v-2H8v2h9zM7 14v-1H3v1h4zm4 3v-2H8v2h3z';
          break;

        case 'editor-paragraph':
          path = 'M15 2H7.54c-.83 0-1.59.2-2.28.6-.7.41-1.25.96-1.65 1.65C3.2 4.94 3 5.7 3 6.52s.2 1.58.61 2.27c.4.69.95 1.24 1.65 1.64.69.41 1.45.61 2.28.61h.43V17c0 .27.1.51.29.71.2.19.44.29.71.29.28 0 .51-.1.71-.29.2-.2.3-.44.3-.71V5c0-.27.09-.51.29-.71.2-.19.44-.29.71-.29s.51.1.71.29c.19.2.29.44.29.71v12c0 .27.1.51.3.71.2.19.43.29.71.29.27 0 .51-.1.71-.29.19-.2.29-.44.29-.71V4H15c.27 0 .5-.1.7-.3.2-.19.3-.43.3-.7s-.1-.51-.3-.71C15.5 2.1 15.27 2 15 2z';
          break;

        case 'editor-paste-text':
          path = 'M12.38 2L15 5v1H5V5l2.64-3h4.74zM10 5c.55 0 1-.44 1-1 0-.55-.45-1-1-1s-1 .45-1 1c0 .56.45 1 1 1zm5.45-1H17c.55 0 1 .45 1 1v12c0 .56-.45 1-1 1H3c-.55 0-1-.44-1-1V5c0-.55.45-1 1-1h1.55L4 4.63V7h12V4.63zM14 11V9H6v2h3v5h2v-5h3z';
          break;

        case 'editor-paste-word':
          path = 'M12.38 2L15 5v1H5V5l2.64-3h4.74zM10 5c.55 0 1-.45 1-1s-.45-1-1-1-1 .45-1 1 .45 1 1 1zm8 12V5c0-.55-.45-1-1-1h-1.54l.54.63V7H4V4.62L4.55 4H3c-.55 0-1 .45-1 1v12c0 .55.45 1 1 1h14c.55 0 1-.45 1-1zm-3-8l-2 7h-2l-1-5-1 5H6.92L5 9h2l1 5 1-5h2l1 5 1-5h2z';
          break;

        case 'editor-quote':
          path = 'M9.49 13.22c0-.74-.2-1.38-.61-1.9-.62-.78-1.83-.88-2.53-.72-.29-1.65 1.11-3.75 2.92-4.65L7.88 4c-2.73 1.3-5.42 4.28-4.96 8.05C3.21 14.43 4.59 16 6.54 16c.85 0 1.56-.25 2.12-.75s.83-1.18.83-2.03zm8.05 0c0-.74-.2-1.38-.61-1.9-.63-.78-1.83-.88-2.53-.72-.29-1.65 1.11-3.75 2.92-4.65L15.93 4c-2.73 1.3-5.41 4.28-4.95 8.05.29 2.38 1.66 3.95 3.61 3.95.85 0 1.56-.25 2.12-.75s.83-1.18.83-2.03z';
          break;

        case 'editor-removeformatting':
          path = 'M14.29 4.59l1.1 1.11c.41.4.61.94.61 1.47v2.12c0 .53-.2 1.07-.61 1.47l-6.63 6.63c-.4.41-.94.61-1.47.61s-1.07-.2-1.47-.61l-1.11-1.1-1.1-1.11c-.41-.4-.61-.94-.61-1.47v-2.12c0-.54.2-1.07.61-1.48l6.63-6.62c.4-.41.94-.61 1.47-.61s1.06.2 1.47.61zm-6.21 9.7l6.42-6.42c.39-.39.39-1.03 0-1.43L12.36 4.3c-.19-.19-.45-.29-.72-.29s-.52.1-.71.29l-6.42 6.42c-.39.4-.39 1.04 0 1.43l2.14 2.14c.38.38 1.04.38 1.43 0z';
          break;

        case 'editor-rtl':
          path = 'M5.52 2h7.43c.55 0 1 .45 1 1s-.45 1-1 1h-1v13c0 .55-.45 1-1 1s-1-.45-1-1V5c0-.55-.45-1-1-1s-1 .45-1 1v12c0 .55-.45 1-1 1s-1-.45-1-1v-5.96h-.43C3.02 11.04 1 9.02 1 6.52S3.02 2 5.52 2zM19 6l-5 4 5 4V6z';
          break;

        case 'editor-spellcheck':
          path = 'M15.84 2.76c.25 0 .49.04.71.11.23.07.44.16.64.25l.35-.81c-.52-.26-1.08-.39-1.69-.39-.58 0-1.09.13-1.52.37-.43.25-.76.61-.99 1.08C13.11 3.83 13 4.38 13 5c0 .99.23 1.75.7 2.28s1.15.79 2.02.79c.6 0 1.13-.09 1.6-.26v-.84c-.26.08-.51.14-.74.19-.24.05-.49.08-.74.08-.59 0-1.04-.19-1.34-.57-.32-.37-.47-.93-.47-1.66 0-.7.16-1.25.48-1.65.33-.4.77-.6 1.33-.6zM6.5 8h1.04L5.3 2H4.24L2 8h1.03l.58-1.66H5.9zM8 2v6h2.17c.67 0 1.19-.15 1.57-.46.38-.3.56-.72.56-1.26 0-.4-.1-.72-.3-.95-.19-.24-.5-.39-.93-.47v-.04c.35-.06.6-.21.78-.44.18-.24.28-.53.28-.88 0-.52-.19-.9-.56-1.14-.36-.24-.96-.36-1.79-.36H8zm.98 2.48V2.82h.85c.44 0 .77.06.97.19.21.12.31.33.31.61 0 .31-.1.53-.29.66-.18.13-.48.2-.89.2h-.95zM5.64 5.5H3.9l.54-1.56c.14-.4.25-.76.32-1.1l.15.52c.07.23.13.4.17.51zm3.34-.23h.99c.44 0 .76.08.98.23.21.15.32.38.32.69 0 .34-.11.59-.32.75s-.52.24-.93.24H8.98V5.27zM4 13l5 5 9-8-1-1-8 6-4-3z';
          break;

        case 'editor-strikethrough':
          path = 'M15.82 12.25c.26 0 .5-.02.74-.07.23-.05.48-.12.73-.2v.84c-.46.17-.99.26-1.58.26-.88 0-1.54-.26-2.01-.79-.39-.44-.62-1.04-.68-1.79h-.94c.12.21.18.48.18.79 0 .54-.18.95-.55 1.26-.38.3-.9.45-1.56.45H8v-2.5H6.59l.93 2.5H6.49l-.59-1.67H3.62L3.04 13H2l.93-2.5H2v-1h1.31l.93-2.49H5.3l.92 2.49H8V7h1.77c1 0 1.41.17 1.77.41.37.24.55.62.55 1.13 0 .35-.09.64-.27.87l-.08.09h1.29c.05-.4.15-.77.31-1.1.23-.46.55-.82.98-1.06.43-.25.93-.37 1.51-.37.61 0 1.17.12 1.69.38l-.35.81c-.2-.1-.42-.18-.64-.25s-.46-.11-.71-.11c-.55 0-.99.2-1.31.59-.23.29-.38.66-.44 1.11H17v1h-2.95c.06.5.2.9.44 1.19.3.37.75.56 1.33.56zM4.44 8.96l-.18.54H5.3l-.22-.61c-.04-.11-.09-.28-.17-.51-.07-.24-.12-.41-.14-.51-.08.33-.18.69-.33 1.09zm4.53-1.09V9.5h1.19c.28-.02.49-.09.64-.18.19-.13.28-.35.28-.66 0-.28-.1-.48-.3-.61-.2-.12-.53-.18-.97-.18h-.84zm-3.33 2.64v-.01H3.91v.01h1.73zm5.28.01l-.03-.02H8.97v1.68h1.04c.4 0 .71-.08.92-.23.21-.16.31-.4.31-.74 0-.31-.11-.54-.32-.69z';
          break;

        case 'editor-table':
          path = 'M18 17V3H2v14h16zM16 7H4V5h12v2zm-7 4H4V9h5v2zm7 0h-5V9h5v2zm-7 4H4v-2h5v2zm7 0h-5v-2h5v2z';
          break;

        case 'editor-textcolor':
          path = 'M13.23 15h1.9L11 4H9L5 15h1.88l1.07-3h4.18zm-1.53-4.54H8.51L10 5.6z';
          break;

        case 'editor-ul':
          path = 'M5.5 7C4.67 7 4 6.33 4 5.5 4 4.68 4.67 4 5.5 4 6.32 4 7 4.68 7 5.5 7 6.33 6.32 7 5.5 7zM8 5h9v1H8V5zm-2.5 7c-.83 0-1.5-.67-1.5-1.5C4 9.68 4.67 9 5.5 9c.82 0 1.5.68 1.5 1.5 0 .83-.68 1.5-1.5 1.5zM8 10h9v1H8v-1zm-2.5 7c-.83 0-1.5-.67-1.5-1.5 0-.82.67-1.5 1.5-1.5.82 0 1.5.68 1.5 1.5 0 .83-.68 1.5-1.5 1.5zM8 15h9v1H8v-1z';
          break;

        case 'editor-underline':
          path = 'M14 5h-2v5.71c0 1.99-1.12 2.98-2.45 2.98-1.32 0-2.55-1-2.55-2.96V5H5v5.87c0 1.91 1 4.54 4.48 4.54 3.49 0 4.52-2.58 4.52-4.5V5zm0 13v-2H5v2h9z';
          break;

        case 'editor-unlink':
          path = 'M17.74 2.26c1.68 1.69 1.68 4.41 0 6.1l-1.53 1.52c-.32.33-.69.58-1.08.77L13 10l1.69-1.64.76-.77.76-.76c.84-.84.84-2.2 0-3.04-.84-.85-2.2-.85-3.04 0l-.77.76-.76.76L10 7l-.65-2.14c.19-.38.44-.75.77-1.07l1.52-1.53c1.69-1.68 4.42-1.68 6.1 0zM2 4l8 6-6-8zm4-2l4 8-2-8H6zM2 6l8 4-8-2V6zm7.36 7.69L10 13l.74 2.35-1.38 1.39c-1.69 1.68-4.41 1.68-6.1 0-1.68-1.68-1.68-4.42 0-6.1l1.39-1.38L7 10l-.69.64-1.52 1.53c-.85.84-.85 2.2 0 3.04.84.85 2.2.85 3.04 0zM18 16l-8-6 6 8zm-4 2l-4-8 2 8h2zm4-4l-8-4 8 2v2z';
          break;

        case 'editor-video':
          path = 'M16 2h-3v1H7V2H4v15h3v-1h6v1h3V2zM6 3v1H5V3h1zm9 0v1h-1V3h1zm-2 1v5H7V4h6zM6 5v1H5V5h1zm9 0v1h-1V5h1zM6 7v1H5V7h1zm9 0v1h-1V7h1zM6 9v1H5V9h1zm9 0v1h-1V9h1zm-2 1v5H7v-5h6zm-7 1v1H5v-1h1zm9 0v1h-1v-1h1zm-9 2v1H5v-1h1zm9 0v1h-1v-1h1zm-9 2v1H5v-1h1zm9 0v1h-1v-1h1z';
          break;

        case 'ellipsis':
          path = 'M5 10c0 1.1-.9 2-2 2s-2-.9-2-2 .9-2 2-2 2 .9 2 2zm12-2c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm-7 0c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z';
          break;

        case 'email-alt':
          path = 'M19 14.5v-9c0-.83-.67-1.5-1.5-1.5H3.49c-.83 0-1.5.67-1.5 1.5v9c0 .83.67 1.5 1.5 1.5H17.5c.83 0 1.5-.67 1.5-1.5zm-1.31-9.11c.33.33.15.67-.03.84L13.6 9.95l3.9 4.06c.12.14.2.36.06.51-.13.16-.43.15-.56.05l-4.37-3.73-2.14 1.95-2.13-1.95-4.37 3.73c-.13.1-.43.11-.56-.05-.14-.15-.06-.37.06-.51l3.9-4.06-4.06-3.72c-.18-.17-.36-.51-.03-.84s.67-.17.95.07l6.24 5.04 6.25-5.04c.28-.24.62-.4.95-.07z';
          break;

        case 'email-alt2':
          path = 'M18.01 11.18V2.51c0-1.19-.9-1.81-2-1.37L4 5.91c-1.1.44-2 1.77-2 2.97v8.66c0 1.2.9 1.81 2 1.37l12.01-4.77c1.1-.44 2-1.76 2-2.96zm-1.43-7.46l-6.04 9.33-6.65-4.6c-.1-.07-.36-.32-.17-.64.21-.36.65-.21.65-.21l6.3 2.32s4.83-6.34 5.11-6.7c.13-.17.43-.34.73-.13.29.2.16.49.07.63z';
          break;

        case 'email':
          path = 'M3.87 4h13.25C18.37 4 19 4.59 19 5.79v8.42c0 1.19-.63 1.79-1.88 1.79H3.87c-1.25 0-1.88-.6-1.88-1.79V5.79c0-1.2.63-1.79 1.88-1.79zm6.62 8.6l6.74-5.53c.24-.2.43-.66.13-1.07-.29-.41-.82-.42-1.17-.17l-5.7 3.86L4.8 5.83c-.35-.25-.88-.24-1.17.17-.3.41-.11.87.13 1.07z';
          break;

        case 'embed-audio':
          path = 'M17 4H3c-1.1 0-2 .9-2 2v8c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm-7 3H7v4c0 1.1-.9 2-2 2s-2-.9-2-2 .9-2 2-2c.4 0 .7.1 1 .3V5h4v2zm4 3.5L12.5 12l1.5 1.5V15l-3-3 3-3v1.5zm1 4.5v-1.5l1.5-1.5-1.5-1.5V9l3 3-3 3z';
          break;

        case 'embed-generic':
          path = 'M17 4H3c-1.1 0-2 .9-2 2v8c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm-3 6.5L12.5 12l1.5 1.5V15l-3-3 3-3v1.5zm1 4.5v-1.5l1.5-1.5-1.5-1.5V9l3 3-3 3z';
          break;

        case 'embed-photo':
          path = 'M17 4H3c-1.1 0-2 .9-2 2v8c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm-7 8H3V6h7v6zm4-1.5L12.5 12l1.5 1.5V15l-3-3 3-3v1.5zm1 4.5v-1.5l1.5-1.5-1.5-1.5V9l3 3-3 3zm-6-4V8.5L7.2 10 6 9.2 4 11h5zM4.6 8.6c.6 0 1-.4 1-1s-.4-1-1-1-1 .4-1 1 .4 1 1 1z';
          break;

        case 'embed-post':
          path = 'M17 4H3c-1.1 0-2 .9-2 2v8c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zM8.6 9l-.4.3c-.4.4-.5 1.1-.2 1.6l-.8.8-1.1-1.1-1.3 1.3c-.2.2-1.6 1.3-1.8 1.1-.2-.2.9-1.6 1.1-1.8l1.3-1.3-1.1-1.1.8-.8c.5.3 1.2.3 1.6-.2l.3-.3c.5-.5.5-1.2.2-1.7L8 5l3 2.9-.8.8c-.5-.2-1.2-.2-1.6.3zm5.4 1.5L12.5 12l1.5 1.5V15l-3-3 3-3v1.5zm1 4.5v-1.5l1.5-1.5-1.5-1.5V9l3 3-3 3z';
          break;

        case 'embed-video':
          path = 'M17 4H3c-1.1 0-2 .9-2 2v8c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm-7 6.5L8 9.1V11H3V6h5v1.8l2-1.3v4zm4 0L12.5 12l1.5 1.5V15l-3-3 3-3v1.5zm1 4.5v-1.5l1.5-1.5-1.5-1.5V9l3 3-3 3z';
          break;

        case 'excerpt-view':
          path = 'M19 18V2c0-.55-.45-1-1-1H2c-.55 0-1 .45-1 1v16c0 .55.45 1 1 1h16c.55 0 1-.45 1-1zM4 3c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zm13 0v6H6V3h11zM4 11c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zm13 0v6H6v-6h11z';
          break;

        case 'exit':
          path = 'M13 3v2h2v10h-2v2h4V3h-4zm0 8V9H5.4l4.3-4.3-1.4-1.4L1.6 10l6.7 6.7 1.4-1.4L5.4 11H13z';
          break;

        case 'external':
          path = 'M9 3h8v8l-2-1V6.92l-5.6 5.59-1.41-1.41L14.08 5H10zm3 12v-3l2-2v7H3V6h8L9 8H5v7h7z';
          break;

        case 'facebook-alt':
          path = 'M8.46 18h2.93v-7.3h2.45l.37-2.84h-2.82V6.04c0-.82.23-1.38 1.41-1.38h1.51V2.11c-.26-.03-1.15-.11-2.19-.11-2.18 0-3.66 1.33-3.66 3.76v2.1H6v2.84h2.46V18z';
          break;

        case 'facebook':
          path = 'M2.89 2h14.23c.49 0 .88.39.88.88v14.24c0 .48-.39.88-.88.88h-4.08v-6.2h2.08l.31-2.41h-2.39V7.85c0-.7.2-1.18 1.2-1.18h1.28V4.51c-.22-.03-.98-.09-1.86-.09-1.85 0-3.11 1.12-3.11 3.19v1.78H8.46v2.41h2.09V18H2.89c-.49 0-.89-.4-.89-.88V2.88c0-.49.4-.88.89-.88z';
          break;

        case 'feedback':
          path = 'M2 2h16c.55 0 1 .45 1 1v14c0 .55-.45 1-1 1H2c-.55 0-1-.45-1-1V3c0-.55.45-1 1-1zm15 14V7H3v9h14zM4 8v1h3V8H4zm4 0v3h8V8H8zm-4 4v1h3v-1H4zm4 0v3h8v-3H8z';
          break;

        case 'filter':
          path = 'M3 4.5v-2s3.34-1 7-1 7 1 7 1v2l-5 7.03v6.97s-1.22-.09-2.25-.59S8 16.5 8 16.5v-4.97z';
          break;

        case 'flag':
          path = 'M5 18V3H3v15h2zm1-6V4c3-1 7 1 11 0v8c-3 1.27-8-1-11 0z';
          break;

        case 'format-aside':
          path = 'M1 1h18v12l-6 6H1V1zm3 3v1h12V4H4zm0 4v1h12V8H4zm6 5v-1H4v1h6zm2 4l5-5h-5v5z';
          break;

        case 'format-audio':
          path = 'M6.99 3.08l11.02-2c.55-.08.99.45.99 1V14.5c0 1.94-1.57 3.5-3.5 3.5S12 16.44 12 14.5c0-1.93 1.57-3.5 3.5-3.5.54 0 1.04.14 1.5.35V5.08l-9 2V16c-.24 1.7-1.74 3-3.5 3C2.57 19 1 17.44 1 15.5 1 13.57 2.57 12 4.5 12c.54 0 1.04.14 1.5.35V4.08c0-.55.44-.91.99-1z';
          break;

        case 'format-chat':
          path = 'M11 6h-.82C9.07 6 8 7.2 8 8.16V10l-3 3v-3H3c-1.1 0-2-.9-2-2V3c0-1.1.9-2 2-2h6c1.1 0 2 .9 2 2v3zm0 1h6c1.1 0 2 .9 2 2v5c0 1.1-.9 2-2 2h-2v3l-3-3h-1c-1.1 0-2-.9-2-2V9c0-1.1.9-2 2-2z';
          break;

        case 'format-gallery':
          path = 'M16 4h1.96c.57 0 1.04.47 1.04 1.04v12.92c0 .57-.47 1.04-1.04 1.04H5.04C4.47 19 4 18.53 4 17.96V16H2.04C1.47 16 1 15.53 1 14.96V2.04C1 1.47 1.47 1 2.04 1h12.92c.57 0 1.04.47 1.04 1.04V4zM3 14h11V3H3v11zm5-8.5C8 4.67 7.33 4 6.5 4S5 4.67 5 5.5 5.67 7 6.5 7 8 6.33 8 5.5zm2 4.5s1-5 3-5v8H4V7c2 0 2 3 2 3s.33-2 2-2 2 2 2 2zm7 7V6h-1v8.96c0 .57-.47 1.04-1.04 1.04H6v1h11z';
          break;

        case 'format-image':
          path = 'M2.25 1h15.5c.69 0 1.25.56 1.25 1.25v15.5c0 .69-.56 1.25-1.25 1.25H2.25C1.56 19 1 18.44 1 17.75V2.25C1 1.56 1.56 1 2.25 1zM17 17V3H3v14h14zM10 6c0-1.1-.9-2-2-2s-2 .9-2 2 .9 2 2 2 2-.9 2-2zm3 5s0-6 3-6v10c0 .55-.45 1-1 1H5c-.55 0-1-.45-1-1V8c2 0 3 4 3 4s1-3 3-3 3 2 3 2z';
          break;

        case 'format-quote':
          path = 'M8.54 12.74c0-.87-.24-1.61-.72-2.22-.73-.92-2.14-1.03-2.96-.85-.34-1.93 1.3-4.39 3.42-5.45L6.65 1.94C3.45 3.46.31 6.96.85 11.37 1.19 14.16 2.8 16 5.08 16c1 0 1.83-.29 2.48-.88.66-.59.98-1.38.98-2.38zm9.43 0c0-.87-.24-1.61-.72-2.22-.73-.92-2.14-1.03-2.96-.85-.34-1.93 1.3-4.39 3.42-5.45l-1.63-2.28c-3.2 1.52-6.34 5.02-5.8 9.43.34 2.79 1.95 4.63 4.23 4.63 1 0 1.83-.29 2.48-.88.66-.59.98-1.38.98-2.38z';
          break;

        case 'format-status':
          path = 'M10 1c7 0 9 2.91 9 6.5S17 14 10 14s-9-2.91-9-6.5S3 1 10 1zM5.5 9C6.33 9 7 8.33 7 7.5S6.33 6 5.5 6 4 6.67 4 7.5 4.67 9 5.5 9zM10 9c.83 0 1.5-.67 1.5-1.5S10.83 6 10 6s-1.5.67-1.5 1.5S9.17 9 10 9zm4.5 0c.83 0 1.5-.67 1.5-1.5S15.33 6 14.5 6 13 6.67 13 7.5 13.67 9 14.5 9zM6 14.5c.83 0 1.5.67 1.5 1.5s-.67 1.5-1.5 1.5-1.5-.67-1.5-1.5.67-1.5 1.5-1.5zm-3 2c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1z';
          break;

        case 'format-video':
          path = 'M2 1h16c.55 0 1 .45 1 1v16l-18-.02V2c0-.55.45-1 1-1zm4 1L4 5h1l2-3H6zm4 0H9L7 5h1zm3 0h-1l-2 3h1zm3 0h-1l-2 3h1zm1 14V6H3v10h14zM8 7l6 4-6 4V7z';
          break;

        case 'forms':
          path = 'M2 2h7v7H2V2zm9 0v7h7V2h-7zM5.5 4.5L7 3H4zM12 8V3h5v5h-5zM4.5 5.5L3 4v3zM8 4L6.5 5.5 8 7V4zM5.5 6.5L4 8h3zM9 18v-7H2v7h7zm9 0h-7v-7h7v7zM8 12v5H3v-5h5zm6.5 1.5L16 12h-3zM12 16l1.5-1.5L12 13v3zm3.5-1.5L17 16v-3zm-1 1L13 17h3z';
          break;

        case 'googleplus':
          path = 'M6.73 10h5.4c.05.29.09.57.09.95 0 3.27-2.19 5.6-5.49 5.6-3.17 0-5.73-2.57-5.73-5.73 0-3.17 2.56-5.73 5.73-5.73 1.54 0 2.84.57 3.83 1.5l-1.55 1.5c-.43-.41-1.17-.89-2.28-.89-1.96 0-3.55 1.62-3.55 3.62 0 1.99 1.59 3.61 3.55 3.61 2.26 0 3.11-1.62 3.24-2.47H6.73V10zM19 10v1.64h-1.64v1.63h-1.63v-1.63h-1.64V10h1.64V8.36h1.63V10H19z';
          break;

        case 'grid-view':
          path = 'M2 1h16c.55 0 1 .45 1 1v16c0 .55-.45 1-1 1H2c-.55 0-1-.45-1-1V2c0-.55.45-1 1-1zm7.01 7.99v-6H3v6h6.01zm8 0v-6h-6v6h6zm-8 8.01v-6H3v6h6.01zm8 0v-6h-6v6h6z';
          break;

        case 'groups':
          path = 'M8.03 4.46c-.29 1.28.55 3.46 1.97 3.46 1.41 0 2.25-2.18 1.96-3.46-.22-.98-1.08-1.63-1.96-1.63-.89 0-1.74.65-1.97 1.63zm-4.13.9c-.25 1.08.47 2.93 1.67 2.93s1.92-1.85 1.67-2.93c-.19-.83-.92-1.39-1.67-1.39s-1.48.56-1.67 1.39zm8.86 0c-.25 1.08.47 2.93 1.66 2.93 1.2 0 1.92-1.85 1.67-2.93-.19-.83-.92-1.39-1.67-1.39-.74 0-1.47.56-1.66 1.39zm-.59 11.43l1.25-4.3C14.2 10 12.71 8.47 10 8.47c-2.72 0-4.21 1.53-3.44 4.02l1.26 4.3C8.05 17.51 9 18 10 18c.98 0 1.94-.49 2.17-1.21zm-6.1-7.63c-.49.67-.96 1.83-.42 3.59l1.12 3.79c-.34.2-.77.31-1.2.31-.85 0-1.65-.41-1.85-1.03l-1.07-3.65c-.65-2.11.61-3.4 2.92-3.4.27 0 .54.02.79.06-.1.1-.2.22-.29.33zm8.35-.39c2.31 0 3.58 1.29 2.92 3.4l-1.07 3.65c-.2.62-1 1.03-1.85 1.03-.43 0-.86-.11-1.2-.31l1.11-3.77c.55-1.78.08-2.94-.42-3.61-.08-.11-.18-.23-.28-.33.25-.04.51-.06.79-.06z';
          break;

        case 'hammer':
          path = 'M17.7 6.32l1.41 1.42-3.47 3.41-1.42-1.42.84-.82c-.32-.76-.81-1.57-1.51-2.31l-4.61 6.59-5.26 4.7c-.39.39-1.02.39-1.42 0l-1.2-1.21c-.39-.39-.39-1.02 0-1.41l10.97-9.92c-1.37-.86-3.21-1.46-5.67-1.48 2.7-.82 4.95-.93 6.58-.3 1.7.66 2.82 2.2 3.91 3.58z';
          break;

        case 'heading':
          path = 'M12.5 4v5.2h-5V4H5v13h2.5v-5.2h5V17H15V4';
          break;

        case 'heart':
          path = 'M10 17.12c3.33-1.4 5.74-3.79 7.04-6.21 1.28-2.41 1.46-4.81.32-6.25-1.03-1.29-2.37-1.78-3.73-1.74s-2.68.63-3.63 1.46c-.95-.83-2.27-1.42-3.63-1.46s-2.7.45-3.73 1.74c-1.14 1.44-.96 3.84.34 6.25 1.28 2.42 3.69 4.81 7.02 6.21z';
          break;

        case 'hidden':
          path = 'M17.2 3.3l.16.17c.39.39.39 1.02 0 1.41L4.55 17.7c-.39.39-1.03.39-1.41 0l-.17-.17c-.39-.39-.39-1.02 0-1.41l1.59-1.6c-1.57-1-2.76-2.3-3.56-3.93.81-1.65 2.03-2.98 3.64-3.99S8.04 5.09 10 5.09c1.2 0 2.33.21 3.4.6l2.38-2.39c.39-.39 1.03-.39 1.42 0zm-7.09 4.01c-.23.25-.34.54-.34.88 0 .31.12.58.31.81l1.8-1.79c-.13-.12-.28-.21-.45-.26-.11-.01-.28-.03-.49-.04-.33.03-.6.16-.83.4zM2.4 10.59c.69 1.23 1.71 2.25 3.05 3.05l1.28-1.28c-.51-.69-.77-1.47-.77-2.36 0-1.06.36-1.98 1.09-2.76-1.04.27-1.96.7-2.76 1.26-.8.58-1.43 1.27-1.89 2.09zm13.22-2.13l.96-.96c1.02.86 1.83 1.89 2.42 3.09-.81 1.65-2.03 2.98-3.64 3.99s-3.4 1.51-5.36 1.51c-.63 0-1.24-.07-1.83-.18l1.07-1.07c.25.02.5.05.76.05 1.63 0 3.13-.4 4.5-1.21s2.4-1.84 3.1-3.09c-.46-.82-1.09-1.51-1.89-2.09-.03-.01-.06-.03-.09-.04zm-5.58 5.58l4-4c-.01 1.1-.41 2.04-1.18 2.81-.78.78-1.72 1.18-2.82 1.19z';
          break;

        case 'html':
          path = 'M4 16v-2H2v2H1v-5h1v2h2v-2h1v5H4zM7 16v-4H5.6v-1h3.7v1H8v4H7zM10 16v-5h1l1.4 3.4h.1L14 11h1v5h-1v-3.1h-.1l-1.1 2.5h-.6l-1.1-2.5H11V16h-1zM19 16h-3v-5h1v4h2v1zM9.4 4.2L7.1 6.5l2.3 2.3-.6 1.2-3.5-3.5L8.8 3l.6 1.2zm1.2 4.6l2.3-2.3-2.3-2.3.6-1.2 3.5 3.5-3.5 3.5-.6-1.2z';
          break;

        case 'id-alt':
          path = 'M18 18H2V2h16v16zM8.05 7.53c.13-.07.24-.15.33-.24.09-.1.17-.21.24-.34.07-.14.13-.26.17-.37s.07-.22.1-.34L8.95 6c0-.04.01-.07.01-.09.05-.32.03-.61-.04-.9-.08-.28-.23-.52-.46-.72C8.23 4.1 7.95 4 7.6 4c-.2 0-.39.04-.56.11-.17.08-.31.18-.41.3-.11.13-.2.27-.27.44-.07.16-.11.33-.12.51s0 .36.01.55l.02.09c.01.06.03.15.06.25s.06.21.1.33.1.25.17.37c.08.12.16.23.25.33s.2.19.34.25c.13.06.28.09.43.09s.3-.03.43-.09zM16 5V4h-5v1h5zm0 2V6h-5v1h5zM7.62 8.83l-1.38-.88c-.41 0-.79.11-1.14.32-.35.22-.62.5-.81.85-.19.34-.29.7-.29 1.07v1.25l.2.05c.13.04.31.09.55.14.24.06.51.12.8.17.29.06.62.1 1 .14.37.04.73.06 1.07.06s.69-.02 1.07-.06.7-.09.98-.14c.27-.05.54-.1.82-.17.27-.06.45-.11.54-.13.09-.03.16-.05.21-.06v-1.25c0-.36-.1-.72-.31-1.07s-.49-.64-.84-.86-.72-.33-1.11-.33zM16 9V8h-3v1h3zm0 2v-1h-3v1h3zm0 3v-1H4v1h12zm0 2v-1H4v1h12z';
          break;

        case 'id':
          path = 'M18 16H2V4h16v12zM7.05 8.53c.13-.07.24-.15.33-.24.09-.1.17-.21.24-.34.07-.14.13-.26.17-.37s.07-.22.1-.34L7.95 7c0-.04.01-.07.01-.09.05-.32.03-.61-.04-.9-.08-.28-.23-.52-.46-.72C7.23 5.1 6.95 5 6.6 5c-.2 0-.39.04-.56.11-.17.08-.31.18-.41.3-.11.13-.2.27-.27.44-.07.16-.11.33-.12.51s0 .36.01.55l.02.09c.01.06.03.15.06.25s.06.21.1.33.1.25.17.37c.08.12.16.23.25.33s.2.19.34.25c.13.06.28.09.43.09s.3-.03.43-.09zM17 9V5h-5v4h5zm-10.38.83l-1.38-.88c-.41 0-.79.11-1.14.32-.35.22-.62.5-.81.85-.19.34-.29.7-.29 1.07v1.25l.2.05c.13.04.31.09.55.14.24.06.51.12.8.17.29.06.62.1 1 .14.37.04.73.06 1.07.06s.69-.02 1.07-.06.7-.09.98-.14c.27-.05.54-.1.82-.17.27-.06.45-.11.54-.13.09-.03.16-.05.21-.06v-1.25c0-.36-.1-.72-.31-1.07s-.49-.64-.84-.86-.72-.33-1.11-.33zM17 11v-1h-5v1h5zm0 2v-1h-5v1h5zm0 2v-1H3v1h14z';
          break;

        case 'image-crop':
          path = 'M19 12v3h-4v4h-3v-4H4V7H0V4h4V0h3v4h7l3-3 1 1-3 3v7h4zm-8-5H7v4zm-3 5h4V8z';
          break;

        case 'image-filter':
          path = 'M14 5.87c0-2.2-1.79-4-4-4s-4 1.8-4 4c0 2.21 1.79 4 4 4s4-1.79 4-4zM3.24 10.66c-1.92 1.1-2.57 3.55-1.47 5.46 1.11 1.92 3.55 2.57 5.47 1.47 1.91-1.11 2.57-3.55 1.46-5.47-1.1-1.91-3.55-2.56-5.46-1.46zm9.52 6.93c1.92 1.1 4.36.45 5.47-1.46 1.1-1.92.45-4.36-1.47-5.47-1.91-1.1-4.36-.45-5.46 1.46-1.11 1.92-.45 4.36 1.46 5.47z';
          break;

        case 'image-flip-horizontal':
          path = 'M19 3v14h-8v3H9v-3H1V3h8V0h2v3h8zm-8.5 14V3h-1v14h1zM7 6.5L3 10l4 3.5v-7zM17 10l-4-3.5v7z';
          break;

        case 'image-flip-vertical':
          path = 'M20 9v2h-3v8H3v-8H0V9h3V1h14v8h3zM6.5 7h7L10 3zM17 9.5H3v1h14v-1zM13.5 13h-7l3.5 4z';
          break;

        case 'image-rotate-left':
          path = 'M7 5H5.05c0-1.74.85-2.9 2.95-2.9V0C4.85 0 2.96 2.11 2.96 5H1.18L3.8 8.39zm13-4v14h-5v5H1V10h9V1h10zm-2 2h-6v7h3v3h3V3zm-5 9H3v6h10v-6z';
          break;

        case 'image-rotate-right':
          path = 'M15.95 5H14l3.2 3.39L19.82 5h-1.78c0-2.89-1.89-5-5.04-5v2.1c2.1 0 2.95 1.16 2.95 2.9zM1 1h10v9h9v10H6v-5H1V1zm2 2v10h3v-3h3V3H3zm5 9v6h10v-6H8z';
          break;

        case 'image-rotate':
          path = 'M10.25 1.02c5.1 0 8.75 4.04 8.75 9s-3.65 9-8.75 9c-3.2 0-6.02-1.59-7.68-3.99l2.59-1.52c1.1 1.5 2.86 2.51 4.84 2.51 3.3 0 6-2.79 6-6s-2.7-6-6-6c-1.97 0-3.72 1-4.82 2.49L7 8.02l-6 2v-7L2.89 4.6c1.69-2.17 4.36-3.58 7.36-3.58z';
          break;

        case 'images-alt':
          path = 'M4 15v-3H2V2h12v3h2v3h2v10H6v-3H4zm7-12c-1.1 0-2 .9-2 2h4c0-1.1-.89-2-2-2zm-7 8V6H3v5h1zm7-3h4c0-1.1-.89-2-2-2-1.1 0-2 .9-2 2zm-5 6V9H5v5h1zm9-1c1.1 0 2-.89 2-2 0-1.1-.9-2-2-2s-2 .9-2 2c0 1.11.9 2 2 2zm2 4v-2c-5 0-5-3-10-3v5h10z';
          break;

        case 'images-alt2':
          path = 'M5 3h14v11h-2v2h-2v2H1V7h2V5h2V3zm13 10V4H6v9h12zm-3-4c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm1 6v-1H5V6H4v9h12zM7 6l10 6H7V6zm7 11v-1H3V8H2v9h12z';
          break;

        case 'index-card':
          path = 'M1 3.17V18h18V4H8v-.83c0-.32-.12-.6-.35-.83S7.14 2 6.82 2H2.18c-.33 0-.6.11-.83.34-.24.23-.35.51-.35.83zM10 6v2H3V6h7zm7 0v10h-5V6h5zm-7 4v2H3v-2h7zm0 4v2H3v-2h7z';
          break;

        case 'info-outline':
          path = 'M9 15h2V9H9v6zm1-10c-.5 0-1 .5-1 1s.5 1 1 1 1-.5 1-1-.5-1-1-1zm0-4c-5 0-9 4-9 9s4 9 9 9 9-4 9-9-4-9-9-9zm0 16c-3.9 0-7-3.1-7-7s3.1-7 7-7 7 3.1 7 7-3.1 7-7 7z';
          break;

        case 'info':
          path = 'M10 2c4.42 0 8 3.58 8 8s-3.58 8-8 8-8-3.58-8-8 3.58-8 8-8zm1 4c0-.55-.45-1-1-1s-1 .45-1 1 .45 1 1 1 1-.45 1-1zm0 9V9H9v6h2z';
          break;

        case 'insert-after':
          path = 'M9 12h2v-2h2V8h-2V6H9v2H7v2h2v2zm1 4c3.9 0 7-3.1 7-7s-3.1-7-7-7-7 3.1-7 7 3.1 7 7 7zm0-12c2.8 0 5 2.2 5 5s-2.2 5-5 5-5-2.2-5-5 2.2-5 5-5zM3 19h14v-2H3v2z';
          break;

        case 'insert-before':
          path = 'M11 8H9v2H7v2h2v2h2v-2h2v-2h-2V8zm-1-4c-3.9 0-7 3.1-7 7s3.1 7 7 7 7-3.1 7-7-3.1-7-7-7zm0 12c-2.8 0-5-2.2-5-5s2.2-5 5-5 5 2.2 5 5-2.2 5-5 5zM3 1v2h14V1H3z';
          break;

        case 'insert':
          path = 'M10 1c-5 0-9 4-9 9s4 9 9 9 9-4 9-9-4-9-9-9zm0 16c-3.9 0-7-3.1-7-7s3.1-7 7-7 7 3.1 7 7-3.1 7-7 7zm1-11H9v3H6v2h3v3h2v-3h3V9h-3V6z';
          break;

        case 'instagram':
          path = 'M12.67 10A2.67 2.67 0 1 0 10 12.67 2.68 2.68 0 0 0 12.67 10zm1.43 0A4.1 4.1 0 1 1 10 5.9a4.09 4.09 0 0 1 4.1 4.1zm1.13-4.27a1 1 0 1 1-1-1 1 1 0 0 1 1 1zM10 3.44c-1.17 0-3.67-.1-4.72.32a2.67 2.67 0 0 0-1.52 1.52c-.42 1-.32 3.55-.32 4.72s-.1 3.67.32 4.72a2.74 2.74 0 0 0 1.52 1.52c1 .42 3.55.32 4.72.32s3.67.1 4.72-.32a2.83 2.83 0 0 0 1.52-1.52c.42-1.05.32-3.55.32-4.72s.1-3.67-.32-4.72a2.74 2.74 0 0 0-1.52-1.52c-1.05-.42-3.55-.32-4.72-.32zM18 10c0 1.1 0 2.2-.05 3.3a4.84 4.84 0 0 1-1.29 3.36A4.8 4.8 0 0 1 13.3 18H6.7a4.84 4.84 0 0 1-3.36-1.29 4.84 4.84 0 0 1-1.29-3.41C2 12.2 2 11.1 2 10V6.7a4.84 4.84 0 0 1 1.34-3.36A4.8 4.8 0 0 1 6.7 2.05C7.8 2 8.9 2 10 2h3.3a4.84 4.84 0 0 1 3.36 1.29A4.8 4.8 0 0 1 18 6.7V10z';
          break;

        case 'keyboard-hide':
          path = 'M18,0 L2,0 C0.9,0 0.01,0.9 0.01,2 L0,12 C0,13.1 0.9,14 2,14 L18,14 C19.1,14 20,13.1 20,12 L20,2 C20,0.9 19.1,0 18,0 Z M18,12 L2,12 L2,2 L18,2 L18,12 Z M9,3 L11,3 L11,5 L9,5 L9,3 Z M9,6 L11,6 L11,8 L9,8 L9,6 Z M6,3 L8,3 L8,5 L6,5 L6,3 Z M6,6 L8,6 L8,8 L6,8 L6,6 Z M3,6 L5,6 L5,8 L3,8 L3,6 Z M3,3 L5,3 L5,5 L3,5 L3,3 Z M6,9 L14,9 L14,11 L6,11 L6,9 Z M12,6 L14,6 L14,8 L12,8 L12,6 Z M12,3 L14,3 L14,5 L12,5 L12,3 Z M15,6 L17,6 L17,8 L15,8 L15,6 Z M15,3 L17,3 L17,5 L15,5 L15,3 Z M10,20 L14,16 L6,16 L10,20 Z';
          break;

        case 'laptop':
          path = 'M3 3h14c.6 0 1 .4 1 1v10c0 .6-.4 1-1 1H3c-.6 0-1-.4-1-1V4c0-.6.4-1 1-1zm13 2H4v8h12V5zm-3 1H5v4zm6 11v-1H1v1c0 .6.5 1 1.1 1h15.8c.6 0 1.1-.4 1.1-1z';
          break;

        case 'layout':
          path = 'M2 2h5v11H2V2zm6 0h5v5H8V2zm6 0h4v16h-4V2zM8 8h5v5H8V8zm-6 6h11v4H2v-4z';
          break;

        case 'leftright':
          path = 'M3 10.03L9 6v8zM11 6l6 4.03L11 14V6z';
          break;

        case 'lightbulb':
          path = 'M10 1c3.11 0 5.63 2.52 5.63 5.62 0 1.84-2.03 4.58-2.03 4.58-.33.44-.6 1.25-.6 1.8v1c0 .55-.45 1-1 1H8c-.55 0-1-.45-1-1v-1c0-.55-.27-1.36-.6-1.8 0 0-2.02-2.74-2.02-4.58C4.38 3.52 6.89 1 10 1zM7 16.87V16h6v.87c0 .62-.13 1.13-.75 1.13H12c0 .62-.4 1-1.02 1h-2c-.61 0-.98-.38-.98-1h-.25c-.62 0-.75-.51-.75-1.13z';
          break;

        case 'list-view':
          path = 'M2 19h16c.55 0 1-.45 1-1V2c0-.55-.45-1-1-1H2c-.55 0-1 .45-1 1v16c0 .55.45 1 1 1zM4 3c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zm13 0v2H6V3h11zM4 7c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zm13 0v2H6V7h11zM4 11c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zm13 0v2H6v-2h11zM4 15c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zm13 0v2H6v-2h11z';
          break;

        case 'location-alt':
          path = 'M13 13.14l1.17-5.94c.79-.43 1.33-1.25 1.33-2.2 0-1.38-1.12-2.5-2.5-2.5S10.5 3.62 10.5 5c0 .95.54 1.77 1.33 2.2zm0-9.64c.83 0 1.5.67 1.5 1.5s-.67 1.5-1.5 1.5-1.5-.67-1.5-1.5.67-1.5 1.5-1.5zm1.72 4.8L18 6.97v9L13.12 18 7 15.97l-5 2v-9l5-2 4.27 1.41 1.73 7.3z';
          break;

        case 'location':
          path = 'M10 2C6.69 2 4 4.69 4 8c0 2.02 1.17 3.71 2.53 4.89.43.37 1.18.96 1.85 1.83.74.97 1.41 2.01 1.62 2.71.21-.7.88-1.74 1.62-2.71.67-.87 1.42-1.46 1.85-1.83C14.83 11.71 16 10.02 16 8c0-3.31-2.69-6-6-6zm0 2.56c1.9 0 3.44 1.54 3.44 3.44S11.9 11.44 10 11.44 6.56 9.9 6.56 8 8.1 4.56 10 4.56z';
          break;

        case 'lock':
          path = 'M14 9h1c.55 0 1 .45 1 1v7c0 .55-.45 1-1 1H5c-.55 0-1-.45-1-1v-7c0-.55.45-1 1-1h1V6c0-2.21 1.79-4 4-4s4 1.79 4 4v3zm-2 0V6c0-1.1-.9-2-2-2s-2 .9-2 2v3h4zm-1 7l-.36-2.15c.51-.24.86-.75.86-1.35 0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5c0 .6.35 1.11.86 1.35L9 16h2z';
          break;

        case 'marker':
          path = 'M10 2c4.42 0 8 3.58 8 8s-3.58 8-8 8-8-3.58-8-8 3.58-8 8-8zm0 13c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5z';
          break;

        case 'media-archive':
          path = 'M12 2l4 4v12H4V2h8zm0 4h3l-3-3v3zM8 3.5v2l1.8-1zM11 5L9.2 6 11 7V5zM8 6.5v2l1.8-1zM11 8L9.2 9l1.8 1V8zM8 9.5v2l1.8-1zm3 1.5l-1.8 1 1.8 1v-2zm-1.5 6c.83 0 1.62-.72 1.5-1.63-.05-.38-.49-1.61-.49-1.61l-1.99-1.1s-.45 1.95-.52 2.71c-.07.77.67 1.63 1.5 1.63zm0-2.39c.42 0 .76.34.76.76 0 .43-.34.77-.76.77s-.76-.34-.76-.77c0-.42.34-.76.76-.76z';
          break;

        case 'media-audio':
          path = 'M12 2l4 4v12H4V2h8zm0 4h3l-3-3v3zm1 7.26V8.09c0-.11-.04-.21-.12-.29-.07-.08-.16-.11-.27-.1 0 0-3.97.71-4.25.78C8.07 8.54 8 8.8 8 9v3.37c-.2-.09-.42-.07-.6-.07-.38 0-.7.13-.96.39-.26.27-.4.58-.4.96 0 .37.14.69.4.95.26.27.58.4.96.4.34 0 .7-.04.96-.26.26-.23.64-.65.64-1.12V10.3l3-.6V12c-.67-.2-1.17.04-1.44.31-.26.26-.39.58-.39.95 0 .38.13.69.39.96.27.26.71.39 1.08.39.38 0 .7-.13.96-.39.26-.27.4-.58.4-.96z';
          break;

        case 'media-code':
          path = 'M12 2l4 4v12H4V2h8zM9 13l-2-2 2-2-1-1-3 3 3 3zm3 1l3-3-3-3-1 1 2 2-2 2z';
          break;

        case 'media-default':
          path = 'M12 2l4 4v12H4V2h8zm0 4h3l-3-3v3z';
          break;

        case 'media-document':
          path = 'M12 2l4 4v12H4V2h8zM5 3v1h6V3H5zm7 3h3l-3-3v3zM5 5v1h6V5H5zm10 3V7H5v1h10zM5 9v1h4V9H5zm10 3V9h-5v3h5zM5 11v1h4v-1H5zm10 3v-1H5v1h10zm-3 2v-1H5v1h7z';
          break;

        case 'media-interactive':
          path = 'M12 2l4 4v12H4V2h8zm0 4h3l-3-3v3zm2 8V8H6v6h3l-1 2h1l1-2 1 2h1l-1-2h3zm-6-3c-.55 0-1-.45-1-1s.45-1 1-1 1 .45 1 1-.45 1-1 1zm5-2v2h-3V9h3zm0 3v1H7v-1h6z';
          break;

        case 'media-spreadsheet':
          path = 'M12 2l4 4v12H4V2h8zm-1 4V3H5v3h6zM8 8V7H5v1h3zm3 0V7H9v1h2zm4 0V7h-3v1h3zm-7 2V9H5v1h3zm3 0V9H9v1h2zm4 0V9h-3v1h3zm-7 2v-1H5v1h3zm3 0v-1H9v1h2zm4 0v-1h-3v1h3zm-7 2v-1H5v1h3zm3 0v-1H9v1h2zm4 0v-1h-3v1h3zm-7 2v-1H5v1h3zm3 0v-1H9v1h2z';
          break;

        case 'media-text':
          path = 'M12 2l4 4v12H4V2h8zM5 3v1h6V3H5zm7 3h3l-3-3v3zM5 5v1h6V5H5zm10 3V7H5v1h10zm0 2V9H5v1h10zm0 2v-1H5v1h10zm-4 2v-1H5v1h6z';
          break;

        case 'media-video':
          path = 'M12 2l4 4v12H4V2h8zm0 4h3l-3-3v3zm-1 8v-3c0-.27-.1-.51-.29-.71-.2-.19-.44-.29-.71-.29H7c-.27 0-.51.1-.71.29-.19.2-.29.44-.29.71v3c0 .27.1.51.29.71.2.19.44.29.71.29h3c.27 0 .51-.1.71-.29.19-.2.29-.44.29-.71zm3 1v-5l-2 2v1z';
          break;

        case 'megaphone':
          path = 'M18.15 5.94c.46 1.62.38 3.22-.02 4.48-.42 1.28-1.26 2.18-2.3 2.48-.16.06-.26.06-.4.06-.06.02-.12.02-.18.02-.06.02-.14.02-.22.02h-6.8l2.22 5.5c.02.14-.06.26-.14.34-.08.1-.24.16-.34.16H6.95c-.1 0-.26-.06-.34-.16-.08-.08-.16-.2-.14-.34l-1-5.5H4.25l-.02-.02c-.5.06-1.08-.18-1.54-.62s-.88-1.08-1.06-1.88c-.24-.8-.2-1.56-.02-2.2.18-.62.58-1.08 1.06-1.3l.02-.02 9-5.4c.1-.06.18-.1.24-.16.06-.04.14-.08.24-.12.16-.08.28-.12.5-.18 1.04-.3 2.24.1 3.22.98s1.84 2.24 2.26 3.86zm-2.58 5.98h-.02c.4-.1.74-.34 1.04-.7.58-.7.86-1.76.86-3.04 0-.64-.1-1.3-.28-1.98-.34-1.36-1.02-2.5-1.78-3.24s-1.68-1.1-2.46-.88c-.82.22-1.4.96-1.7 2-.32 1.04-.28 2.36.06 3.72.38 1.36 1 2.5 1.8 3.24.78.74 1.62 1.1 2.48.88zm-2.54-7.08c.22-.04.42-.02.62.04.38.16.76.48 1.02 1s.42 1.2.42 1.78c0 .3-.04.56-.12.8-.18.48-.44.84-.86.94-.34.1-.8-.06-1.14-.4s-.64-.86-.78-1.5c-.18-.62-.12-1.24.02-1.72s.48-.84.82-.94z';
          break;

        case 'menu-alt':
          path = 'M3 4h14v2H3V4zm0 5h14v2H3V9zm0 5h14v2H3v-2z';
          break;

        case 'menu':
          path = 'M17 7V5H3v2h14zm0 4V9H3v2h14zm0 4v-2H3v2h14z';
          break;

        case 'microphone':
          path = 'M12 9V3c0-1.1-.89-2-2-2-1.12 0-2 .94-2 2v6c0 1.1.9 2 2 2 1.13 0 2-.94 2-2zm4 0c0 2.97-2.16 5.43-5 5.91V17h2c.56 0 1 .45 1 1s-.44 1-1 1H7c-.55 0-1-.45-1-1s.45-1 1-1h2v-2.09C6.17 14.43 4 11.97 4 9c0-.55.45-1 1-1 .56 0 1 .45 1 1 0 2.21 1.8 4 4 4 2.21 0 4-1.79 4-4 0-.55.45-1 1-1 .56 0 1 .45 1 1z';
          break;

        case 'migrate':
          path = 'M4 6h6V4H2v12.01h8V14H4V6zm2 2h6V5l6 5-6 5v-3H6V8z';
          break;

        case 'minus':
          path = 'M4 9h12v2H4V9z';
          break;

        case 'money':
          path = 'M0 3h20v12h-.75c0-1.79-1.46-3.25-3.25-3.25-1.31 0-2.42.79-2.94 1.91-.25-.1-.52-.16-.81-.16-.98 0-1.8.63-2.11 1.5H0V3zm8.37 3.11c-.06.15-.1.31-.11.47s-.01.33.01.5l.02.08c.01.06.02.14.05.23.02.1.06.2.1.31.03.11.09.22.15.33.07.12.15.22.23.31s.18.17.31.23c.12.06.25.09.4.09.14 0 .27-.03.39-.09s.22-.14.3-.22c.09-.09.16-.2.22-.32.07-.12.12-.23.16-.33s.07-.2.09-.31c.03-.11.04-.18.05-.22s.01-.07.01-.09c.05-.29.03-.56-.04-.82s-.21-.48-.41-.66c-.21-.18-.47-.27-.79-.27-.19 0-.36.03-.52.1-.15.07-.28.16-.38.28-.09.11-.17.25-.24.4zm4.48 6.04v-1.14c0-.33-.1-.66-.29-.98s-.45-.59-.77-.79c-.32-.21-.66-.31-1.02-.31l-1.24.84-1.28-.82c-.37 0-.72.1-1.04.3-.31.2-.56.46-.74.77-.18.32-.27.65-.27.99v1.14l.18.05c.12.04.29.08.51.14.23.05.47.1.74.15.26.05.57.09.91.13.34.03.67.05.99.05.3 0 .63-.02.98-.05.34-.04.64-.08.89-.13.25-.04.5-.1.76-.16l.5-.12c.08-.02.14-.04.19-.06zm3.15.1c1.52 0 2.75 1.23 2.75 2.75s-1.23 2.75-2.75 2.75c-.73 0-1.38-.3-1.87-.77.23-.35.37-.78.37-1.23 0-.77-.39-1.46-.99-1.86.43-.96 1.37-1.64 2.49-1.64zm-5.5 3.5c0-.96.79-1.75 1.75-1.75s1.75.79 1.75 1.75-.79 1.75-1.75 1.75-1.75-.79-1.75-1.75z';
          break;

        case 'move':
          path = 'M19 10l-4 4v-3h-4v4h3l-4 4-4-4h3v-4H5v3l-4-4 4-4v3h4V5H6l4-4 4 4h-3v4h4V6z';
          break;

        case 'nametag':
          path = 'M12 5V2c0-.55-.45-1-1-1H9c-.55 0-1 .45-1 1v3c0 .55.45 1 1 1h2c.55 0 1-.45 1-1zm-2-3c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zm8 13V7c0-1.1-.9-2-2-2h-3v.33C13 6.25 12.25 7 11.33 7H8.67C7.75 7 7 6.25 7 5.33V5H4c-1.1 0-2 .9-2 2v8c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2zm-1-6v6H3V9h14zm-8 2c0-.55-.22-1-.5-1s-.5.45-.5 1 .22 1 .5 1 .5-.45.5-1zm3 0c0-.55-.22-1-.5-1s-.5.45-.5 1 .22 1 .5 1 .5-.45.5-1zm-5.96 1.21c.92.48 2.34.79 3.96.79s3.04-.31 3.96-.79c-.21 1-1.89 1.79-3.96 1.79s-3.75-.79-3.96-1.79z';
          break;

        case 'networking':
          path = 'M18 13h1c.55 0 1 .45 1 1.01v2.98c0 .56-.45 1.01-1 1.01h-4c-.55 0-1-.45-1-1.01v-2.98c0-.56.45-1.01 1-1.01h1v-2h-5v2h1c.55 0 1 .45 1 1.01v2.98c0 .56-.45 1.01-1 1.01H8c-.55 0-1-.45-1-1.01v-2.98c0-.56.45-1.01 1-1.01h1v-2H4v2h1c.55 0 1 .45 1 1.01v2.98C6 17.55 5.55 18 5 18H1c-.55 0-1-.45-1-1.01v-2.98C0 13.45.45 13 1 13h1v-2c0-1.1.9-2 2-2h5V7H8c-.55 0-1-.45-1-1.01V3.01C7 2.45 7.45 2 8 2h4c.55 0 1 .45 1 1.01v2.98C13 6.55 12.55 7 12 7h-1v2h5c1.1 0 2 .9 2 2v2z';
          break;

        case 'no-alt':
          path = 'M14.95 6.46L11.41 10l3.54 3.54-1.41 1.41L10 11.42l-3.53 3.53-1.42-1.42L8.58 10 5.05 6.47l1.42-1.42L10 8.58l3.54-3.53z';
          break;

        case 'no':
          path = 'M12.12 10l3.53 3.53-2.12 2.12L10 12.12l-3.54 3.54-2.12-2.12L7.88 10 4.34 6.46l2.12-2.12L10 7.88l3.54-3.53 2.12 2.12z';
          break;

        case 'palmtree':
          path = 'M8.58 2.39c.32 0 .59.05.81.14 1.25.55 1.69 2.24 1.7 3.97.59-.82 2.15-2.29 3.41-2.29s2.94.73 3.53 3.55c-1.13-.65-2.42-.94-3.65-.94-1.26 0-2.45.32-3.29.89.4-.11.86-.16 1.33-.16 1.39 0 2.9.45 3.4 1.31.68 1.16.47 3.38-.76 4.14-.14-2.1-1.69-4.12-3.47-4.12-.44 0-.88.12-1.33.38C8 10.62 7 14.56 7 19H2c0-5.53 4.21-9.65 7.68-10.79-.56-.09-1.17-.15-1.82-.15C6.1 8.06 4.05 8.5 2 10c.76-2.96 2.78-4.1 4.69-4.1 1.25 0 2.45.5 3.2 1.29-.66-2.24-2.49-2.86-4.08-2.86-.8 0-1.55.16-2.05.35.91-1.29 3.31-2.29 4.82-2.29zM13 11.5c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5.67 1.5 1.5 1.5 1.5-.67 1.5-1.5z';
          break;

        case 'paperclip':
          path = 'M17.05 2.7c1.93 1.94 1.93 5.13 0 7.07L10 16.84c-1.88 1.89-4.91 1.93-6.86.15-.06-.05-.13-.09-.19-.15-1.93-1.94-1.93-5.12 0-7.07l4.94-4.95c.91-.92 2.28-1.1 3.39-.58.3.15.59.33.83.58 1.17 1.17 1.17 3.07 0 4.24l-4.93 4.95c-.39.39-1.02.39-1.41 0s-.39-1.02 0-1.41l4.93-4.95c.39-.39.39-1.02 0-1.41-.38-.39-1.02-.39-1.4 0l-4.94 4.95c-.91.92-1.1 2.29-.57 3.4.14.3.32.59.57.84s.54.43.84.57c1.11.53 2.47.35 3.39-.57l7.05-7.07c1.16-1.17 1.16-3.08 0-4.25-.56-.55-1.28-.83-2-.86-.08.01-.16.01-.24 0-.22-.03-.43-.11-.6-.27-.39-.4-.38-1.05.02-1.45.16-.16.36-.24.56-.28.14-.02.27-.01.4.02 1.19.06 2.36.52 3.27 1.43z';
          break;

        case 'performance':
          path = 'M3.76 17.01h12.48C17.34 15.63 18 13.9 18 12c0-4.41-3.58-8-8-8s-8 3.59-8 8c0 1.9.66 3.63 1.76 5.01zM9 6c0-.55.45-1 1-1s1 .45 1 1c0 .56-.45 1-1 1s-1-.44-1-1zM4 8c0-.55.45-1 1-1s1 .45 1 1c0 .56-.45 1-1 1s-1-.44-1-1zm4.52 3.4c.84-.83 6.51-3.5 6.51-3.5s-2.66 5.68-3.49 6.51c-.84.84-2.18.84-3.02 0-.83-.83-.83-2.18 0-3.01zM3 13c0-.55.45-1 1-1s1 .45 1 1c0 .56-.45 1-1 1s-1-.44-1-1zm6 0c0-.55.45-1 1-1s1 .45 1 1c0 .56-.45 1-1 1s-1-.44-1-1zm6 0c0-.55.45-1 1-1s1 .45 1 1c0 .56-.45 1-1 1s-1-.44-1-1z';
          break;

        case 'phone':
          path = 'M12.06 6l-.21-.2c-.52-.54-.43-.79.08-1.3l2.72-2.75c.81-.82.96-1.21 1.73-.48l.21.2zm.53.45l4.4-4.4c.7.94 2.34 3.47 1.53 5.34-.73 1.67-1.09 1.75-2 3-1.85 2.11-4.18 4.37-6 6.07-1.26.91-1.31 1.33-3 2-1.8.71-4.4-.89-5.38-1.56l4.4-4.4 1.18 1.62c.34.46 1.2-.06 1.8-.66 1.04-1.05 3.18-3.18 4-4.07.59-.59 1.12-1.45.66-1.8zM1.57 16.5l-.21-.21c-.68-.74-.29-.9.52-1.7l2.74-2.72c.51-.49.75-.6 1.27-.11l.2.21z';
          break;

        case 'playlist-audio':
          path = 'M17 3V1H2v2h15zm0 4V5H2v2h15zm-7 4V9H2v2h8zm7.45-1.96l-6 1.12c-.16.02-.19.03-.29.13-.11.09-.16.22-.16.37v4.59c-.29-.13-.66-.14-.93-.14-.54 0-1 .19-1.38.57s-.56.84-.56 1.38c0 .53.18.99.56 1.37s.84.57 1.38.57c.49 0 .92-.16 1.29-.48s.59-.71.65-1.19v-4.95L17 11.27v3.48c-.29-.13-.56-.19-.83-.19-.54 0-1.11.19-1.49.57-.38.37-.57.83-.57 1.37s.19.99.57 1.37.84.57 1.38.57c.53 0 .99-.19 1.37-.57s.57-.83.57-1.37V9.6c0-.16-.05-.3-.16-.41-.11-.12-.24-.17-.39-.15zM8 15v-2H2v2h6zm-2 4v-2H2v2h4z';
          break;

        case 'playlist-video':
          path = 'M17 3V1H2v2h15zm0 4V5H2v2h15zM6 11V9H2v2h4zm2-2h9c.55 0 1 .45 1 1v8c0 .55-.45 1-1 1H8c-.55 0-1-.45-1-1v-8c0-.55.45-1 1-1zm3 7l3.33-2L11 12v4zm-5-1v-2H2v2h4zm0 4v-2H2v2h4z';
          break;

        case 'plus-alt':
          path = 'M15.8 4.2c3.2 3.21 3.2 8.39 0 11.6-3.21 3.2-8.39 3.2-11.6 0C1 12.59 1 7.41 4.2 4.2 7.41 1 12.59 1 15.8 4.2zm-4.3 11.3v-4h4v-3h-4v-4h-3v4h-4v3h4v4h3z';
          break;

        case 'plus-light':
          path = 'M17 9v2h-6v6H9v-6H3V9h6V3h2v6h6z';
          break;

        case 'plus':
          path = 'M17 7v3h-5v5H9v-5H4V7h5V2h3v5h5z';
          break;

        case 'portfolio':
          path = 'M4 5H.78c-.37 0-.74.32-.69.84l1.56 9.99S3.5 8.47 3.86 6.7c.11-.53.61-.7.98-.7H10s-.7-2.08-.77-2.31C9.11 3.25 8.89 3 8.45 3H5.14c-.36 0-.7.23-.8.64C4.25 4.04 4 5 4 5zm4.88 0h-4s.42-1 .87-1h2.13c.48 0 1 1 1 1zM2.67 16.25c-.31.47-.76.75-1.26.75h15.73c.54 0 .92-.31 1.03-.83.44-2.19 1.68-8.44 1.68-8.44.07-.5-.3-.73-.62-.73H16V5.53c0-.16-.26-.53-.66-.53h-3.76c-.52 0-.87.58-.87.58L10 7H5.59c-.32 0-.63.19-.69.5 0 0-1.59 6.7-1.72 7.33-.07.37-.22.99-.51 1.42zM15.38 7H11s.58-1 1.13-1h2.29c.71 0 .96 1 .96 1z';
          break;

        case 'post-status':
          path = 'M14 6c0 1.86-1.28 3.41-3 3.86V16c0 1-2 2-2 2V9.86c-1.72-.45-3-2-3-3.86 0-2.21 1.79-4 4-4s4 1.79 4 4zM8 5c0 .55.45 1 1 1s1-.45 1-1-.45-1-1-1-1 .45-1 1z';
          break;

        case 'pressthis':
          path = 'M14.76 1C16.55 1 18 2.46 18 4.25c0 1.78-1.45 3.24-3.24 3.24-.23 0-.47-.03-.7-.08L13 8.47V19H2V4h9.54c.13-2 1.52-3 3.22-3zm0 5.49C16 6.49 17 5.48 17 4.25 17 3.01 16 2 14.76 2s-2.24 1.01-2.24 2.25c0 .37.1.72.27 1.03L9.57 8.5c-.28.28-1.77 2.22-1.5 2.49.02.03.06.04.1.04.49 0 2.14-1.28 2.39-1.53l3.24-3.24c.29.14.61.23.96.23z';
          break;

        case 'products':
          path = 'M17 8h1v11H2V8h1V6c0-2.76 2.24-5 5-5 .71 0 1.39.15 2 .42.61-.27 1.29-.42 2-.42 2.76 0 5 2.24 5 5v2zM5 6v2h2V6c0-1.13.39-2.16 1.02-3H8C6.35 3 5 4.35 5 6zm10 2V6c0-1.65-1.35-3-3-3h-.02c.63.84 1.02 1.87 1.02 3v2h2zm-5-4.22C9.39 4.33 9 5.12 9 6v2h2V6c0-.88-.39-1.67-1-2.22z';
          break;

        case 'randomize':
          path = 'M18 6.01L14 9V7h-4l-5 8H2v-2h2l5-8h5V3zM2 5h3l1.15 2.17-1.12 1.8L4 7H2V5zm16 9.01L14 17v-2H9l-1.15-2.17 1.12-1.8L10 13h4v-2z';
          break;

        case 'redo':
          path = 'M8 5h5V2l6 4-6 4V7H8c-2.2 0-4 1.8-4 4s1.8 4 4 4h5v2H8c-3.3 0-6-2.7-6-6s2.7-6 6-6z';
          break;

        case 'rest-api':
          path = 'M3 4h2v12H3z';
          break;

        case 'rss':
          path = 'M14.92 18H18C18 9.32 10.82 2.25 2 2.25v3.02c7.12 0 12.92 5.71 12.92 12.73zm-5.44 0h3.08C12.56 12.27 7.82 7.6 2 7.6v3.02c2 0 3.87.77 5.29 2.16C8.7 14.17 9.48 16.03 9.48 18zm-5.35-.02c1.17 0 2.13-.93 2.13-2.09 0-1.15-.96-2.09-2.13-2.09-1.18 0-2.13.94-2.13 2.09 0 1.16.95 2.09 2.13 2.09z';
          break;

        case 'saved':
          path = 'M15.3 5.3l-6.8 6.8-2.8-2.8-1.4 1.4 4.2 4.2 8.2-8.2';
          break;

        case 'schedule':
          path = 'M2 2h16v4H2V2zm0 10V8h4v4H2zm6-2V8h4v2H8zm6 3V8h4v5h-4zm-6 5v-6h4v6H8zm-6 0v-4h4v4H2zm12 0v-3h4v3h-4z';
          break;

        case 'screenoptions':
          path = 'M9 9V3H3v6h6zm8 0V3h-6v6h6zm-8 8v-6H3v6h6zm8 0v-6h-6v6h6z';
          break;

        case 'search':
          path = 'M12.14 4.18c1.87 1.87 2.11 4.75.72 6.89.12.1.22.21.36.31.2.16.47.36.81.59.34.24.56.39.66.47.42.31.73.57.94.78.32.32.6.65.84 1 .25.35.44.69.59 1.04.14.35.21.68.18 1-.02.32-.14.59-.36.81s-.49.34-.81.36c-.31.02-.65-.04-.99-.19-.35-.14-.7-.34-1.04-.59-.35-.24-.68-.52-1-.84-.21-.21-.47-.52-.77-.93-.1-.13-.25-.35-.47-.66-.22-.32-.4-.57-.56-.78-.16-.2-.29-.35-.44-.5-2.07 1.09-4.69.76-6.44-.98-2.14-2.15-2.14-5.64 0-7.78 2.15-2.15 5.63-2.15 7.78 0zm-1.41 6.36c1.36-1.37 1.36-3.58 0-4.95-1.37-1.37-3.59-1.37-4.95 0-1.37 1.37-1.37 3.58 0 4.95 1.36 1.37 3.58 1.37 4.95 0z';
          break;

        case 'share-alt':
          path = 'M16.22 5.8c.47.69.29 1.62-.4 2.08-.69.47-1.62.29-2.08-.4-.16-.24-.35-.46-.55-.67-.21-.2-.43-.39-.67-.55s-.5-.3-.77-.41c-.27-.12-.55-.21-.84-.26-.59-.13-1.23-.13-1.82-.01-.29.06-.57.15-.84.27-.27.11-.53.25-.77.41s-.46.35-.66.55c-.21.21-.4.43-.56.67s-.3.5-.41.76c-.01.02-.01.03-.01.04-.1.24-.17.48-.23.72H1V6h2.66c.04-.07.07-.13.12-.2.27-.4.57-.77.91-1.11s.72-.65 1.11-.91c.4-.27.83-.51 1.28-.7s.93-.34 1.41-.43c.99-.21 2.03-.21 3.02 0 .48.09.96.24 1.41.43s.88.43 1.28.7c.39.26.77.57 1.11.91s.64.71.91 1.11zM12.5 10c0-1.38-1.12-2.5-2.5-2.5S7.5 8.62 7.5 10s1.12 2.5 2.5 2.5 2.5-1.12 2.5-2.5zm-8.72 4.2c-.47-.69-.29-1.62.4-2.09.69-.46 1.62-.28 2.08.41.16.24.35.46.55.67.21.2.43.39.67.55s.5.3.77.41c.27.12.55.2.84.26.59.13 1.23.12 1.82 0 .29-.06.57-.14.84-.26.27-.11.53-.25.77-.41s.46-.35.66-.55c.21-.21.4-.44.56-.67.16-.25.3-.5.41-.76.01-.02.01-.03.01-.04.1-.24.17-.48.23-.72H19v3h-2.66c-.04.06-.07.13-.12.2-.27.4-.57.77-.91 1.11s-.72.65-1.11.91c-.4.27-.83.51-1.28.7s-.93.33-1.41.43c-.99.21-2.03.21-3.02 0-.48-.1-.96-.24-1.41-.43s-.88-.43-1.28-.7c-.39-.26-.77-.57-1.11-.91s-.64-.71-.91-1.11z';
          break;

        case 'share-alt2':
          path = 'M18 8l-5 4V9.01c-2.58.06-4.88.45-7 2.99.29-3.57 2.66-5.66 7-5.94V3zM4 14h11v-2l2-1.6V16H2V5h9.43c-1.83.32-3.31 1-4.41 2H4v7z';
          break;

        case 'share':
          path = 'M14.5 12c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3c0-.24.03-.46.09-.69l-4.38-2.3c-.55.61-1.33.99-2.21.99-1.66 0-3-1.34-3-3s1.34-3 3-3c.88 0 1.66.39 2.21.99l4.38-2.3c-.06-.23-.09-.45-.09-.69 0-1.66 1.34-3 3-3s3 1.34 3 3-1.34 3-3 3c-.88 0-1.66-.39-2.21-.99l-4.38 2.3c.06.23.09.45.09.69s-.03.46-.09.69l4.38 2.3c.55-.61 1.33-.99 2.21-.99z';
          break;

        case 'shield-alt':
          path = 'M10 2s3 2 7 2c0 11-7 14-7 14S3 15 3 4c4 0 7-2 7-2z';
          break;

        case 'shield':
          path = 'M10 2s3 2 7 2c0 11-7 14-7 14S3 15 3 4c4 0 7-2 7-2zm0 8h5s1-1 1-5c0 0-5-1-6-2v7H5c1 4 5 7 5 7v-7z';
          break;

        case 'shortcode':
          path = 'M6 14H4V6h2V4H2v12h4M7.1 17h2.1l3.7-14h-2.1M14 4v2h2v8h-2v2h4V4';
          break;

        case 'slides':
          path = 'M5 14V6h10v8H5zm-3-1V7h2v6H2zm4-6v6h8V7H6zm10 0h2v6h-2V7zm-3 2V8H7v1h6zm0 3v-2H7v2h6z';
          break;

        case 'smartphone':
          path = 'M6 2h8c.55 0 1 .45 1 1v14c0 .55-.45 1-1 1H6c-.55 0-1-.45-1-1V3c0-.55.45-1 1-1zm7 12V4H7v10h6zM8 5h4l-4 5V5z';
          break;

        case 'smiley':
          path = 'M7 5.2c1.1 0 2 .89 2 2 0 .37-.11.71-.28 1C8.72 8.2 8 8 7 8s-1.72.2-1.72.2c-.17-.29-.28-.63-.28-1 0-1.11.9-2 2-2zm6 0c1.11 0 2 .89 2 2 0 .37-.11.71-.28 1 0 0-.72-.2-1.72-.2s-1.72.2-1.72.2c-.17-.29-.28-.63-.28-1 0-1.11.89-2 2-2zm-3 13.7c3.72 0 7.03-2.36 8.23-5.88l-1.32-.46C15.9 15.52 13.12 17.5 10 17.5s-5.9-1.98-6.91-4.94l-1.32.46c1.2 3.52 4.51 5.88 8.23 5.88z';
          break;

        case 'sort':
          path = 'M11 7H1l5 7zm-2 7h10l-5-7z';
          break;

        case 'sos':
          path = 'M18 10c0-4.42-3.58-8-8-8s-8 3.58-8 8 3.58 8 8 8 8-3.58 8-8zM7.23 3.57L8.72 7.3c-.62.29-1.13.8-1.42 1.42L3.57 7.23c.71-1.64 2.02-2.95 3.66-3.66zm9.2 3.66L12.7 8.72c-.29-.62-.8-1.13-1.42-1.42l1.49-3.73c1.64.71 2.95 2.02 3.66 3.66zM10 12c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm-6.43.77l3.73-1.49c.29.62.8 1.13 1.42 1.42l-1.49 3.73c-1.64-.71-2.95-2.02-3.66-3.66zm9.2 3.66l-1.49-3.73c.62-.29 1.13-.8 1.42-1.42l3.73 1.49c-.71 1.64-2.02 2.95-3.66 3.66z';
          break;

        case 'star-empty':
          path = 'M10 1L7 7l-6 .75 4.13 4.62L4 19l6-3 6 3-1.12-6.63L19 7.75 13 7zm0 2.24l2.34 4.69 4.65.58-3.18 3.56.87 5.15L10 14.88l-4.68 2.34.87-5.15-3.18-3.56 4.65-.58z';
          break;

        case 'star-filled':
          path = 'M10 1l3 6 6 .75-4.12 4.62L16 19l-6-3-6 3 1.13-6.63L1 7.75 7 7z';
          break;

        case 'star-half':
          path = 'M10 1L7 7l-6 .75 4.13 4.62L4 19l6-3 6 3-1.12-6.63L19 7.75 13 7zm0 2.24l2.34 4.69 4.65.58-3.18 3.56.87 5.15L10 14.88V3.24z';
          break;

        case 'sticky':
          path = 'M5 3.61V1.04l8.99-.01-.01 2.58c-1.22.26-2.16 1.35-2.16 2.67v.5c.01 1.31.93 2.4 2.17 2.66l-.01 2.58h-3.41l-.01 2.57c0 .6-.47 4.41-1.06 4.41-.6 0-1.08-3.81-1.08-4.41v-2.56L5 12.02l.01-2.58c1.23-.25 2.15-1.35 2.15-2.66v-.5c0-1.31-.92-2.41-2.16-2.67z';
          break;

        case 'store':
          path = 'M1 10c.41.29.96.43 1.5.43.55 0 1.09-.14 1.5-.43.62-.46 1-1.17 1-2 0 .83.37 1.54 1 2 .41.29.96.43 1.5.43.55 0 1.09-.14 1.5-.43.62-.46 1-1.17 1-2 0 .83.37 1.54 1 2 .41.29.96.43 1.51.43.54 0 1.08-.14 1.49-.43.62-.46 1-1.17 1-2 0 .83.37 1.54 1 2 .41.29.96.43 1.5.43.55 0 1.09-.14 1.5-.43.63-.46 1-1.17 1-2V7l-3-7H4L0 7v1c0 .83.37 1.54 1 2zm2 8.99h5v-5h4v5h5v-7c-.37-.05-.72-.22-1-.43-.63-.45-1-.73-1-1.56 0 .83-.38 1.11-1 1.56-.41.3-.95.43-1.49.44-.55 0-1.1-.14-1.51-.44-.63-.45-1-.73-1-1.56 0 .83-.38 1.11-1 1.56-.41.3-.95.43-1.5.44-.54 0-1.09-.14-1.5-.44-.63-.45-1-.73-1-1.57 0 .84-.38 1.12-1 1.57-.29.21-.63.38-1 .44v6.99z';
          break;

        case 'table-col-after':
          path = 'M14.08 12.864V9.216h3.648V7.424H14.08V3.776h-1.728v3.648H8.64v1.792h3.712v3.648zM0 17.92V0h20.48v17.92H0zM6.4 1.28H1.28v3.84H6.4V1.28zm0 5.12H1.28v3.84H6.4V6.4zm0 5.12H1.28v3.84H6.4v-3.84zM19.2 1.28H7.68v14.08H19.2V1.28z';
          break;

        case 'table-col-before':
          path = 'M6.4 3.776v3.648H2.752v1.792H6.4v3.648h1.728V9.216h3.712V7.424H8.128V3.776zM0 17.92V0h20.48v17.92H0zM12.8 1.28H1.28v14.08H12.8V1.28zm6.4 0h-5.12v3.84h5.12V1.28zm0 5.12h-5.12v3.84h5.12V6.4zm0 5.12h-5.12v3.84h5.12v-3.84z';
          break;

        case 'table-col-delete':
          path = 'M6.4 9.98L7.68 8.7v-.256L6.4 7.164V9.98zm6.4-1.532l1.28-1.28V9.92L12.8 8.64v-.192zm7.68 9.472V0H0v17.92h20.48zm-1.28-2.56h-5.12v-1.024l-.256.256-1.024-1.024v1.792H7.68v-1.792l-1.024 1.024-.256-.256v1.024H1.28V1.28H6.4v2.368l.704-.704.576.576V1.216h5.12V3.52l.96-.96.32.32V1.216h5.12V15.36zm-5.76-2.112l-3.136-3.136-3.264 3.264-1.536-1.536 3.264-3.264L5.632 5.44l1.536-1.536 3.136 3.136 3.2-3.2 1.536 1.536-3.2 3.2 3.136 3.136-1.536 1.536z';
          break;

        case 'table-row-after':
          path = 'M13.824 10.176h-2.88v-2.88H9.536v2.88h-2.88v1.344h2.88v2.88h1.408v-2.88h2.88zM0 17.92V0h20.48v17.92H0zM6.4 1.28H1.28v3.84H6.4V1.28zm6.4 0H7.68v3.84h5.12V1.28zm6.4 0h-5.12v3.84h5.12V1.28zm0 5.056H1.28v9.024H19.2V6.336z';
          break;

        case 'table-row-before':
          path = 'M6.656 6.464h2.88v2.88h1.408v-2.88h2.88V5.12h-2.88V2.24H9.536v2.88h-2.88zM0 17.92V0h20.48v17.92H0zm7.68-2.56h5.12v-3.84H7.68v3.84zm-6.4 0H6.4v-3.84H1.28v3.84zM19.2 1.28H1.28v9.024H19.2V1.28zm0 10.24h-5.12v3.84h5.12v-3.84z';
          break;

        case 'table-row-delete':
          path = 'M17.728 11.456L14.592 8.32l3.2-3.2-1.536-1.536-3.2 3.2L9.92 3.648 8.384 5.12l3.2 3.2-3.264 3.264 1.536 1.536 3.264-3.264 3.136 3.136 1.472-1.536zM0 17.92V0h20.48v17.92H0zm19.2-6.4h-.448l-1.28-1.28H19.2V6.4h-1.792l1.28-1.28h.512V1.28H1.28v3.84h6.208l1.28 1.28H1.28v3.84h7.424l-1.28 1.28H1.28v3.84H19.2v-3.84z';
          break;

        case 'tablet':
          path = 'M4 2h12c.55 0 1 .45 1 1v14c0 .55-.45 1-1 1H4c-.55 0-1-.45-1-1V3c0-.55.45-1 1-1zm11 14V4H5v12h10zM6 5h6l-6 5V5z';
          break;

        case 'tag':
          path = 'M11 2h7v7L8 19l-7-7zm3 6c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2z';
          break;

        case 'tagcloud':
          path = 'M11 3v4H1V3h10zm8 0v4h-7V3h7zM7 8v3H1V8h6zm12 0v3H8V8h11zM9 12v2H1v-2h8zm10 0v2h-9v-2h9zM6 15v1H1v-1h5zm5 0v1H7v-1h4zm3 0v1h-2v-1h2zm5 0v1h-4v-1h4z';
          break;

        case 'testimonial':
          path = 'M4 3h12c.55 0 1.02.2 1.41.59S18 4.45 18 5v7c0 .55-.2 1.02-.59 1.41S16.55 14 16 14h-1l-5 5v-5H4c-.55 0-1.02-.2-1.41-.59S2 12.55 2 12V5c0-.55.2-1.02.59-1.41S3.45 3 4 3zm11 2H4v1h11V5zm1 3H4v1h12V8zm-3 3H4v1h9v-1z';
          break;

        case 'text':
          path = 'M18 3v2H2V3h16zm-6 4v2H2V7h10zm6 0v2h-4V7h4zM8 11v2H2v-2h6zm10 0v2h-8v-2h8zm-4 4v2H2v-2h12z';
          break;

        case 'thumbs-down':
          path = 'M7.28 18c-.15.02-.26-.02-.41-.07-.56-.19-.83-.79-.66-1.35.17-.55 1-3.04 1-3.58 0-.53-.75-1-1.35-1h-3c-.6 0-1-.4-1-1s2-7 2-7c.17-.39.55-1 1-1H14v9h-2.14c-.41.41-3.3 4.71-3.58 5.27-.21.41-.6.68-1 .73zM18 12h-2V3h2v9z';
          break;

        case 'thumbs-up':
          path = 'M12.72 2c.15-.02.26.02.41.07.56.19.83.79.66 1.35-.17.55-1 3.04-1 3.58 0 .53.75 1 1.35 1h3c.6 0 1 .4 1 1s-2 7-2 7c-.17.39-.55 1-1 1H6V8h2.14c.41-.41 3.3-4.71 3.58-5.27.21-.41.6-.68 1-.73zM2 8h2v9H2V8z';
          break;

        case 'tickets-alt':
          path = 'M20 6.38L18.99 9.2v-.01c-.52-.19-1.03-.16-1.53.08s-.85.62-1.04 1.14-.16 1.03.07 1.53c.24.5.62.84 1.15 1.03v.01l-1.01 2.82-15.06-5.38.99-2.79c.52.19 1.03.16 1.53-.08.5-.23.84-.61 1.03-1.13s.16-1.03-.08-1.53c-.23-.49-.61-.83-1.13-1.02L4.93 1zm-4.97 5.69l1.37-3.76c.12-.31.1-.65-.04-.95s-.39-.53-.7-.65L8.14 3.98c-.64-.23-1.37.12-1.6.74L5.17 8.48c-.24.65.1 1.37.74 1.6l7.52 2.74c.14.05.28.08.43.08.52 0 1-.33 1.17-.83zM7.97 4.45l7.51 2.73c.19.07.34.21.43.39.08.18.09.38.02.57l-1.37 3.76c-.13.38-.58.59-.96.45L6.09 9.61c-.39-.14-.59-.57-.45-.96l1.37-3.76c.1-.29.39-.49.7-.49.09 0 .17.02.26.05zm6.82 12.14c.35.27.75.41 1.2.41H16v3H0v-2.96c.55 0 1.03-.2 1.41-.59.39-.38.59-.86.59-1.41s-.2-1.02-.59-1.41-.86-.59-1.41-.59V10h1.05l-.28.8 2.87 1.02c-.51.16-.89.62-.89 1.18v4c0 .69.56 1.25 1.25 1.25h8c.69 0 1.25-.56 1.25-1.25v-1.75l.83.3c.12.43.36.78.71 1.04zM3.25 17v-4c0-.41.34-.75.75-.75h.83l7.92 2.83V17c0 .41-.34.75-.75.75H4c-.41 0-.75-.34-.75-.75z';
          break;

        case 'tickets':
          path = 'M20 5.38L18.99 8.2v-.01c-1.04-.37-2.19.18-2.57 1.22-.37 1.04.17 2.19 1.22 2.56v.01l-1.01 2.82L1.57 9.42l.99-2.79c1.04.38 2.19-.17 2.56-1.21s-.17-2.18-1.21-2.55L4.93 0zm-5.45 3.37c.74-2.08-.34-4.37-2.42-5.12-2.08-.74-4.37.35-5.11 2.42-.74 2.08.34 4.38 2.42 5.12 2.07.74 4.37-.35 5.11-2.42zm-2.56-4.74c.89.32 1.57.94 1.97 1.71-.01-.01-.02-.01-.04-.02-.33-.12-.67.09-.78.4-.1.28-.03.57.05.91.04.27.09.62-.06 1.04-.1.29-.33.58-.65 1l-.74 1.01.08-4.08.4.11c.19.04.26-.24.08-.29 0 0-.57-.15-.92-.28-.34-.12-.88-.36-.88-.36-.18-.08-.3.19-.12.27 0 0 .16.08.34.16l.01 1.63L9.2 9.18l.08-4.11c.2.06.4.11.4.11.19.04.26-.23.07-.29 0 0-.56-.15-.91-.28-.07-.02-.14-.05-.22-.08.93-.7 2.19-.94 3.37-.52zM7.4 6.19c.17-.49.44-.92.78-1.27l.04 5c-.94-.95-1.3-2.39-.82-3.73zm4.04 4.75l2.1-2.63c.37-.41.57-.77.69-1.12.05-.12.08-.24.11-.35.09.57.04 1.18-.17 1.77-.45 1.25-1.51 2.1-2.73 2.33zm-.7-3.22l.02 3.22c0 .02 0 .04.01.06-.4 0-.8-.07-1.2-.21-.33-.12-.63-.28-.9-.48zm1.24 6.08l2.1.75c.24.84 1 1.45 1.91 1.45H16v3H0v-2.96c1.1 0 2-.89 2-2 0-1.1-.9-2-2-2V9h1.05l-.28.8 4.28 1.52C4.4 12.03 4 12.97 4 14c0 2.21 1.79 4 4 4s4-1.79 4-4c0-.07-.02-.13-.02-.2zm-6.53-2.33l1.48.53c-.14.04-.15.27.03.28 0 0 .18.02.37.03l.56 1.54-.78 2.36-1.31-3.9c.21-.01.41-.03.41-.03.19-.02.17-.31-.02-.3 0 0-.59.05-.96.05-.07 0-.15 0-.23-.01.13-.2.28-.38.45-.55zM4.4 14c0-.52.12-1.02.32-1.46l1.71 4.7C5.23 16.65 4.4 15.42 4.4 14zm4.19-1.41l1.72.62c.07.17.12.37.12.61 0 .31-.12.66-.28 1.16l-.35 1.2zM11.6 14c0 1.33-.72 2.49-1.79 3.11l1.1-3.18c.06-.17.1-.31.14-.46l.52.19c.02.11.03.22.03.34zm-4.62 3.45l1.08-3.14 1.11 3.03c.01.02.01.04.02.05-.37.13-.77.21-1.19.21-.35 0-.69-.06-1.02-.15z';
          break;

        case 'tide':
          path = 'M17 7.2V3H3v7.1c2.6-.5 4.5-1.5 6.4-2.6.2-.2.4-.3.6-.5v3c-1.9 1.1-4 2.2-7 2.8V17h14V9.9c-2.6.5-4.4 1.5-6.2 2.6-.3.1-.5.3-.8.4V10c2-1.1 4-2.2 7-2.8z';
          break;

        case 'translation':
          path = 'M11 7H9.49c-.63 0-1.25.3-1.59.7L7 5H4.13l-2.39 7h1.69l.74-2H7v4H2c-1.1 0-2-.9-2-2V5c0-1.1.9-2 2-2h7c1.1 0 2 .9 2 2v2zM6.51 9H4.49l1-2.93zM10 8h7c1.1 0 2 .9 2 2v7c0 1.1-.9 2-2 2h-7c-1.1 0-2-.9-2-2v-7c0-1.1.9-2 2-2zm7.25 5v-1.08h-3.17V9.75h-1.16v2.17H9.75V13h1.28c.11.85.56 1.85 1.28 2.62-.87.36-1.89.62-2.31.62-.01.02.22.97.2 1.46.84 0 2.21-.5 3.28-1.15 1.09.65 2.48 1.15 3.34 1.15-.02-.49.2-1.44.2-1.46-.43 0-1.49-.27-2.38-.63.7-.77 1.14-1.77 1.25-2.61h1.36zm-3.81 1.93c-.5-.46-.85-1.13-1.01-1.93h2.09c-.17.8-.51 1.47-1 1.93l-.04.03s-.03-.02-.04-.03z';
          break;

        case 'trash':
          path = 'M12 4h3c.6 0 1 .4 1 1v1H3V5c0-.6.5-1 1-1h3c.2-1.1 1.3-2 2.5-2s2.3.9 2.5 2zM8 4h3c-.2-.6-.9-1-1.5-1S8.2 3.4 8 4zM4 7h11l-.9 10.1c0 .5-.5.9-1 .9H5.9c-.5 0-.9-.4-1-.9L4 7z';
          break;

        case 'twitter':
          path = 'M18.94 4.46c-.49.73-1.11 1.38-1.83 1.9.01.15.01.31.01.47 0 4.85-3.69 10.44-10.43 10.44-2.07 0-4-.61-5.63-1.65.29.03.58.05.88.05 1.72 0 3.3-.59 4.55-1.57-1.6-.03-2.95-1.09-3.42-2.55.22.04.45.07.69.07.33 0 .66-.05.96-.13-1.67-.34-2.94-1.82-2.94-3.6v-.04c.5.27 1.06.44 1.66.46-.98-.66-1.63-1.78-1.63-3.06 0-.67.18-1.3.5-1.84 1.81 2.22 4.51 3.68 7.56 3.83-.06-.27-.1-.55-.1-.84 0-2.02 1.65-3.66 3.67-3.66 1.06 0 2.01.44 2.68 1.16.83-.17 1.62-.47 2.33-.89-.28.85-.86 1.57-1.62 2.02.75-.08 1.45-.28 2.11-.57z';
          break;

        case 'undo':
          path = 'M12 5H7V2L1 6l6 4V7h5c2.2 0 4 1.8 4 4s-1.8 4-4 4H7v2h5c3.3 0 6-2.7 6-6s-2.7-6-6-6z';
          break;

        case 'universal-access-alt':
          path = 'M19 10c0-4.97-4.03-9-9-9s-9 4.03-9 9 4.03 9 9 9 9-4.03 9-9zm-9-7.4c.83 0 1.5.67 1.5 1.5s-.67 1.51-1.5 1.51c-.82 0-1.5-.68-1.5-1.51s.68-1.5 1.5-1.5zM3.4 7.36c0-.65 6.6-.76 6.6-.76s6.6.11 6.6.76-4.47 1.4-4.47 1.4 1.69 8.14 1.06 8.38c-.62.24-3.19-5.19-3.19-5.19s-2.56 5.43-3.18 5.19c-.63-.24 1.06-8.38 1.06-8.38S3.4 8.01 3.4 7.36z';
          break;

        case 'universal-access':
          path = 'M10 2.6c.83 0 1.5.67 1.5 1.5s-.67 1.51-1.5 1.51c-.82 0-1.5-.68-1.5-1.51s.68-1.5 1.5-1.5zM3.4 7.36c0-.65 6.6-.76 6.6-.76s6.6.11 6.6.76-4.47 1.4-4.47 1.4 1.69 8.14 1.06 8.38c-.62.24-3.19-5.19-3.19-5.19s-2.56 5.43-3.18 5.19c-.63-.24 1.06-8.38 1.06-8.38S3.4 8.01 3.4 7.36z';
          break;

        case 'unlock':
          path = 'M12 9V6c0-1.1-.9-2-2-2s-2 .9-2 2H6c0-2.21 1.79-4 4-4s4 1.79 4 4v3h1c.55 0 1 .45 1 1v7c0 .55-.45 1-1 1H5c-.55 0-1-.45-1-1v-7c0-.55.45-1 1-1h7zm-1 7l-.36-2.15c.51-.24.86-.75.86-1.35 0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5c0 .6.35 1.11.86 1.35L9 16h2z';
          break;

        case 'update':
          path = 'M10.2 3.28c3.53 0 6.43 2.61 6.92 6h2.08l-3.5 4-3.5-4h2.32c-.45-1.97-2.21-3.45-4.32-3.45-1.45 0-2.73.71-3.54 1.78L4.95 5.66C6.23 4.2 8.11 3.28 10.2 3.28zm-.4 13.44c-3.52 0-6.43-2.61-6.92-6H.8l3.5-4c1.17 1.33 2.33 2.67 3.5 4H5.48c.45 1.97 2.21 3.45 4.32 3.45 1.45 0 2.73-.71 3.54-1.78l1.71 1.95c-1.28 1.46-3.15 2.38-5.25 2.38z';
          break;

        case 'upload':
          path = 'M8 14V8H5l5-6 5 6h-3v6H8zm-2 2v-6H4v8h12.01v-8H14v6H6z';
          break;

        case 'vault':
          path = 'M18 17V3c0-.55-.45-1-1-1H3c-.55 0-1 .45-1 1v14c0 .55.45 1 1 1h14c.55 0 1-.45 1-1zm-1 0H3V3h14v14zM4.75 4h10.5c.41 0 .75.34.75.75V6h-1v3h1v2h-1v3h1v1.25c0 .41-.34.75-.75.75H4.75c-.41 0-.75-.34-.75-.75V4.75c0-.41.34-.75.75-.75zM13 10c0-2.21-1.79-4-4-4s-4 1.79-4 4 1.79 4 4 4 4-1.79 4-4zM9 7l.77 1.15C10.49 8.46 11 9.17 11 10c0 1.1-.9 2-2 2s-2-.9-2-2c0-.83.51-1.54 1.23-1.85z';
          break;

        case 'video-alt':
          path = 'M8 5c0-.55-.45-1-1-1H2c-.55 0-1 .45-1 1 0 .57.49 1 1 1h5c.55 0 1-.45 1-1zm6 5l4-4v10l-4-4v-2zm-1 4V8c0-.55-.45-1-1-1H4c-.55 0-1 .45-1 1v6c0 .55.45 1 1 1h8c.55 0 1-.45 1-1z';
          break;

        case 'video-alt2':
          path = 'M12 13V7c0-1.1-.9-2-2-2H3c-1.1 0-2 .9-2 2v6c0 1.1.9 2 2 2h7c1.1 0 2-.9 2-2zm1-2.5l6 4.5V5l-6 4.5v1z';
          break;

        case 'video-alt3':
          path = 'M19 15V5c0-1.1-.9-2-2-2H4c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h13c1.1 0 2-.9 2-2zM8 14V6l6 4z';
          break;

        case 'visibility':
          path = 'M19.7 9.4C17.7 6 14 3.9 10 3.9S2.3 6 .3 9.4L0 10l.3.6c2 3.4 5.7 5.5 9.7 5.5s7.7-2.1 9.7-5.5l.3-.6-.3-.6zM10 14.1c-3.1 0-6-1.6-7.7-4.1C3.6 8 5.7 6.6 8 6.1c-.9.6-1.5 1.7-1.5 2.9 0 1.9 1.6 3.5 3.5 3.5s3.5-1.6 3.5-3.5c0-1.2-.6-2.3-1.5-2.9 2.3.5 4.4 1.9 5.7 3.9-1.7 2.5-4.6 4.1-7.7 4.1z';
          break;

        case 'warning':
          path = 'M10 2c4.42 0 8 3.58 8 8s-3.58 8-8 8-8-3.58-8-8 3.58-8 8-8zm1.13 9.38l.35-6.46H8.52l.35 6.46h2.26zm-.09 3.36c.24-.23.37-.55.37-.96 0-.42-.12-.74-.36-.97s-.59-.35-1.06-.35-.82.12-1.07.35-.37.55-.37.97c0 .41.13.73.38.96.26.23.61.34 1.06.34s.8-.11 1.05-.34z';
          break;

        case 'welcome-add-page':
          path = 'M17 7V4h-2V2h-3v1H3v15h11V9h1V7h2zm-1-2v1h-2v2h-1V6h-2V5h2V3h1v2h2z';
          break;

        case 'welcome-comments':
          path = 'M5 2h10c1.1 0 2 .9 2 2v8c0 1.1-.9 2-2 2h-2l-5 5v-5H5c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2zm8.5 8.5L11 8l2.5-2.5-1-1L10 7 7.5 4.5l-1 1L9 8l-2.5 2.5 1 1L10 9l2.5 2.5z';
          break;

        case 'welcome-learn-more':
          path = 'M10 10L2.54 7.02 3 18H1l.48-11.41L0 6l10-4 10 4zm0-5c-.55 0-1 .22-1 .5s.45.5 1 .5 1-.22 1-.5-.45-.5-1-.5zm0 6l5.57-2.23c.71.94 1.2 2.07 1.36 3.3-.3-.04-.61-.07-.93-.07-2.55 0-4.78 1.37-6 3.41C8.78 13.37 6.55 12 4 12c-.32 0-.63.03-.93.07.16-1.23.65-2.36 1.36-3.3z';
          break;

        case 'welcome-view-site':
          path = 'M18 14V4c0-.55-.45-1-1-1H3c-.55 0-1 .45-1 1v10c0 .55.45 1 1 1h14c.55 0 1-.45 1-1zm-8-8c2.3 0 4.4 1.14 6 3-1.6 1.86-3.7 3-6 3s-4.4-1.14-6-3c1.6-1.86 3.7-3 6-3zm2 3c0-1.1-.9-2-2-2s-2 .9-2 2 .9 2 2 2 2-.9 2-2zm2 8h3v1H3v-1h3v-1h8v1z';
          break;

        case 'welcome-widgets-menus':
          path = 'M19 16V3c0-.55-.45-1-1-1H3c-.55 0-1 .45-1 1v13c0 .55.45 1 1 1h15c.55 0 1-.45 1-1zM4 4h13v4H4V4zm1 1v2h3V5H5zm4 0v2h3V5H9zm4 0v2h3V5h-3zm-8.5 5c.28 0 .5.22.5.5s-.22.5-.5.5-.5-.22-.5-.5.22-.5.5-.5zM6 10h4v1H6v-1zm6 0h5v5h-5v-5zm-7.5 2c.28 0 .5.22.5.5s-.22.5-.5.5-.5-.22-.5-.5.22-.5.5-.5zM6 12h4v1H6v-1zm7 0v2h3v-2h-3zm-8.5 2c.28 0 .5.22.5.5s-.22.5-.5.5-.5-.22-.5-.5.22-.5.5-.5zM6 14h4v1H6v-1z';
          break;

        case 'welcome-write-blog':
          path = 'M16.89 1.2l1.41 1.41c.39.39.39 1.02 0 1.41L14 8.33V18H3V3h10.67l1.8-1.8c.4-.39 1.03-.4 1.42 0zm-5.66 8.48l5.37-5.36-1.42-1.42-5.36 5.37-.71 2.12z';
          break;

        case 'wordpress-alt':
          path = 'M20 10c0-5.51-4.49-10-10-10C4.48 0 0 4.49 0 10c0 5.52 4.48 10 10 10 5.51 0 10-4.48 10-10zM7.78 15.37L4.37 6.22c.55-.02 1.17-.08 1.17-.08.5-.06.44-1.13-.06-1.11 0 0-1.45.11-2.37.11-.18 0-.37 0-.58-.01C4.12 2.69 6.87 1.11 10 1.11c2.33 0 4.45.87 6.05 2.34-.68-.11-1.65.39-1.65 1.58 0 .74.45 1.36.9 2.1.35.61.55 1.36.55 2.46 0 1.49-1.4 5-1.4 5l-3.03-8.37c.54-.02.82-.17.82-.17.5-.05.44-1.25-.06-1.22 0 0-1.44.12-2.38.12-.87 0-2.33-.12-2.33-.12-.5-.03-.56 1.2-.06 1.22l.92.08 1.26 3.41zM17.41 10c.24-.64.74-1.87.43-4.25.7 1.29 1.05 2.71 1.05 4.25 0 3.29-1.73 6.24-4.4 7.78.97-2.59 1.94-5.2 2.92-7.78zM6.1 18.09C3.12 16.65 1.11 13.53 1.11 10c0-1.3.23-2.48.72-3.59C3.25 10.3 4.67 14.2 6.1 18.09zm4.03-6.63l2.58 6.98c-.86.29-1.76.45-2.71.45-.79 0-1.57-.11-2.29-.33.81-2.38 1.62-4.74 2.42-7.1z';
          break;

        case 'wordpress':
          path = 'M20 10c0-5.52-4.48-10-10-10S0 4.48 0 10s4.48 10 10 10 10-4.48 10-10zM10 1.01c4.97 0 8.99 4.02 8.99 8.99s-4.02 8.99-8.99 8.99S1.01 14.97 1.01 10 5.03 1.01 10 1.01zM8.01 14.82L4.96 6.61c.49-.03 1.05-.08 1.05-.08.43-.05.38-1.01-.06-.99 0 0-1.29.1-2.13.1-.15 0-.33 0-.52-.01 1.44-2.17 3.9-3.6 6.7-3.6 2.09 0 3.99.79 5.41 2.09-.6-.08-1.45.35-1.45 1.42 0 .66.38 1.22.79 1.88.31.54.5 1.22.5 2.21 0 1.34-1.27 4.48-1.27 4.48l-2.71-7.5c.48-.03.75-.16.75-.16.43-.05.38-1.1-.05-1.08 0 0-1.3.11-2.14.11-.78 0-2.11-.11-2.11-.11-.43-.02-.48 1.06-.05 1.08l.84.08 1.12 3.04zm6.02 2.15L16.64 10s.67-1.69.39-3.81c.63 1.14.94 2.42.94 3.81 0 2.96-1.56 5.58-3.94 6.97zM2.68 6.77L6.5 17.25c-2.67-1.3-4.47-4.08-4.47-7.25 0-1.16.2-2.23.65-3.23zm7.45 4.53l2.29 6.25c-.75.27-1.57.42-2.42.42-.72 0-1.41-.11-2.06-.3z';
          break;

        case 'yes-alt':
          path = 'M10 2c-4.42 0-8 3.58-8 8s3.58 8 8 8 8-3.58 8-8-3.58-8-8-8zm-.615 12.66h-1.34l-3.24-4.54 1.34-1.25 2.57 2.4 5.14-5.93 1.34.94-5.81 8.38z';
          break;

        case 'yes':
          path = 'M14.83 4.89l1.34.94-5.81 8.38H9.02L5.78 9.67l1.34-1.25 2.57 2.4z';
          break;
      }

      if (!path) {
        return null;
      }

      var iconClass = ['dashicon', 'dashicons-' + icon, className].filter(Boolean).join(' ');
      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])(_primitives__WEBPACK_IMPORTED_MODULE_8__[/* SVG */ "b"], Object(_babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_0__[/* default */ "a"])({
        "aria-hidden": true,
        role: "img",
        focusable: "false",
        className: iconClass,
        xmlns: "http://www.w3.org/2000/svg",
        width: size,
        height: size,
        viewBox: "0 0 20 20"
      }, extraProps), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])(_primitives__WEBPACK_IMPORTED_MODULE_8__[/* Path */ "a"], {
        d: path
      }));
    }
  }]);

  return Dashicon;
}(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["Component"]);


//# sourceMappingURL=index.js.map

/***/ }),

/***/ 85:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var _babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(11);
/* harmony import */ var _babel_runtime_helpers_esm_objectWithoutProperties__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(16);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(10);
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(classnames__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(2);
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _tooltip__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(110);
/* harmony import */ var _button__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(88);
/* harmony import */ var _icon__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(109);




/**
 * External dependencies
 */


/**
 * WordPress dependencies
 */


/**
 * Internal dependencies
 */





function IconButton(props, ref) {
  var icon = props.icon,
      children = props.children,
      label = props.label,
      className = props.className,
      tooltip = props.tooltip,
      shortcut = props.shortcut,
      labelPosition = props.labelPosition,
      size = props.size,
      additionalProps = Object(_babel_runtime_helpers_esm_objectWithoutProperties__WEBPACK_IMPORTED_MODULE_1__[/* default */ "a"])(props, ["icon", "children", "label", "className", "tooltip", "shortcut", "labelPosition", "size"]);

  var classes = classnames__WEBPACK_IMPORTED_MODULE_3___default()('components-icon-button', className, {
    'has-text': children
  });
  var tooltipText = tooltip || label; // Should show the tooltip if...

  var showTooltip = !additionalProps.disabled && ( // an explicit tooltip is passed or...
  tooltip || // there's a shortcut or...
  shortcut || // there's a label and...
  !!label && ( // the children are empty and...
  !children || Object(lodash__WEBPACK_IMPORTED_MODULE_4__["isArray"])(children) && !children.length) && // the tooltip is not explicitly disabled.
  false !== tooltip);
  var element = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__["createElement"])(_button__WEBPACK_IMPORTED_MODULE_6__[/* default */ "a"], Object(_babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_0__[/* default */ "a"])({
    "aria-label": label
  }, additionalProps, {
    className: classes,
    ref: ref
  }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__["createElement"])(_icon__WEBPACK_IMPORTED_MODULE_7__[/* default */ "a"], {
    icon: icon,
    size: size
  }), children);

  if (showTooltip) {
    element = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__["createElement"])(_tooltip__WEBPACK_IMPORTED_MODULE_5__[/* default */ "a"], {
      text: tooltipText,
      shortcut: shortcut,
      position: labelPosition
    }, element);
  }

  return element;
}

/* harmony default export */ __webpack_exports__["a"] = (Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__["forwardRef"])(IconButton));
//# sourceMappingURL=index.js.map

/***/ }),

/***/ 86:
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var replace = String.prototype.replace;
var percentTwenties = /%20/g;

var util = __webpack_require__(71);

var Format = {
    RFC1738: 'RFC1738',
    RFC3986: 'RFC3986'
};

module.exports = util.assign(
    {
        'default': Format.RFC3986,
        formatters: {
            RFC1738: function (value) {
                return replace.call(value, percentTwenties, '+');
            },
            RFC3986: function (value) {
                return String(value);
            }
        }
    },
    Format
);


/***/ }),

/***/ 87:
/***/ (function(module, exports) {

(function() { module.exports = this["ReactDOM"]; }());

/***/ }),

/***/ 88:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* unused harmony export Button */
/* harmony import */ var _babel_runtime_helpers_esm_objectSpread__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(27);
/* harmony import */ var _babel_runtime_helpers_esm_objectWithoutProperties__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(16);
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(10);
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(classnames__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_3__);



/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */


function Button(props, ref) {
  var href = props.href,
      target = props.target,
      isPrimary = props.isPrimary,
      isLarge = props.isLarge,
      isSmall = props.isSmall,
      isTertiary = props.isTertiary,
      isToggled = props.isToggled,
      isBusy = props.isBusy,
      isDefault = props.isDefault,
      isLink = props.isLink,
      isDestructive = props.isDestructive,
      className = props.className,
      disabled = props.disabled,
      additionalProps = Object(_babel_runtime_helpers_esm_objectWithoutProperties__WEBPACK_IMPORTED_MODULE_1__[/* default */ "a"])(props, ["href", "target", "isPrimary", "isLarge", "isSmall", "isTertiary", "isToggled", "isBusy", "isDefault", "isLink", "isDestructive", "className", "disabled"]);

  var classes = classnames__WEBPACK_IMPORTED_MODULE_2___default()('components-button', className, {
    'is-button': isDefault || isPrimary || isLarge || isSmall,
    'is-default': isDefault || !isPrimary && (isLarge || isSmall),
    'is-primary': isPrimary,
    'is-large': isLarge,
    'is-small': isSmall,
    'is-tertiary': isTertiary,
    'is-toggled': isToggled,
    'is-busy': isBusy,
    'is-link': isLink,
    'is-destructive': isDestructive
  });
  var tag = href !== undefined && !disabled ? 'a' : 'button';
  var tagProps = tag === 'a' ? {
    href: href,
    target: target
  } : {
    type: 'button',
    disabled: disabled
  };
  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_3__["createElement"])(tag, Object(_babel_runtime_helpers_esm_objectSpread__WEBPACK_IMPORTED_MODULE_0__[/* default */ "a"])({}, tagProps, additionalProps, {
    className: classes,
    ref: ref
  }));
}
/* harmony default export */ __webpack_exports__["a"] = (Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_3__["forwardRef"])(Button));
//# sourceMappingURL=index.js.map

/***/ }),

/***/ 89:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "a", function() { return _typeof; });
function _typeof(obj) {
  "@babel/helpers - typeof";

  if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") {
    _typeof = function _typeof(obj) {
      return typeof obj;
    };
  } else {
    _typeof = function _typeof(obj) {
      return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj;
    };
  }

  return _typeof(obj);
}

/***/ }),

/***/ 9:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";

// EXPORTS
__webpack_require__.d(__webpack_exports__, "a", function() { return /* binding */ _inherits; });

// CONCATENATED MODULE: ./node_modules/@babel/runtime/helpers/esm/setPrototypeOf.js
function _setPrototypeOf(o, p) {
  _setPrototypeOf = Object.setPrototypeOf || function _setPrototypeOf(o, p) {
    o.__proto__ = p;
    return o;
  };

  return _setPrototypeOf(o, p);
}
// CONCATENATED MODULE: ./node_modules/@babel/runtime/helpers/esm/inherits.js

function _inherits(subClass, superClass) {
  if (typeof superClass !== "function" && superClass !== null) {
    throw new TypeError("Super expression must either be null or a function");
  }

  subClass.prototype = Object.create(superClass && superClass.prototype, {
    constructor: {
      value: subClass,
      writable: true,
      configurable: true
    }
  });
  if (superClass) _setPrototypeOf(subClass, superClass);
}

/***/ }),

/***/ 90:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";

// EXPORTS
__webpack_require__.d(__webpack_exports__, "a", function() { return /* binding */ createBrowserHistory; });
__webpack_require__.d(__webpack_exports__, "c", function() { return /* binding */ createMemoryHistory; });
__webpack_require__.d(__webpack_exports__, "b", function() { return /* binding */ createLocation; });
__webpack_require__.d(__webpack_exports__, "e", function() { return /* binding */ locationsAreEqual; });
__webpack_require__.d(__webpack_exports__, "d", function() { return /* binding */ createPath; });

// UNUSED EXPORTS: createHashHistory, parsePath

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/esm/extends.js
var esm_extends = __webpack_require__(11);

// CONCATENATED MODULE: ./node_modules/resolve-pathname/esm/resolve-pathname.js
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

// CONCATENATED MODULE: ./node_modules/value-equal/esm/value-equal.js
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

// EXTERNAL MODULE: ./node_modules/tiny-invariant/dist/tiny-invariant.esm.js
var tiny_invariant_esm = __webpack_require__(78);

// CONCATENATED MODULE: ./node_modules/history/esm/history.js






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

/***/ 92:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/esm/extends.js
var esm_extends = __webpack_require__(11);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/esm/slicedToArray.js + 3 modules
var slicedToArray = __webpack_require__(21);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/esm/objectWithoutProperties.js
var objectWithoutProperties = __webpack_require__(16);

// EXTERNAL MODULE: external {"this":["wp","element"]}
var external_this_wp_element_ = __webpack_require__(0);

// EXTERNAL MODULE: ./node_modules/classnames/index.js
var classnames = __webpack_require__(10);
var classnames_default = /*#__PURE__*/__webpack_require__.n(classnames);

// EXTERNAL MODULE: ./node_modules/@wordpress/dom/build-module/dom.js
var dom = __webpack_require__(107);

// EXTERNAL MODULE: ./node_modules/@wordpress/dom/build-module/index.js + 2 modules
var build_module = __webpack_require__(50);

// EXTERNAL MODULE: ./node_modules/@wordpress/keycodes/build-module/index.js + 1 modules
var keycodes_build_module = __webpack_require__(18);

// EXTERNAL MODULE: ./node_modules/@wordpress/deprecated/build-module/index.js
var deprecated_build_module = __webpack_require__(94);

// EXTERNAL MODULE: ./node_modules/@wordpress/compose/build-module/hooks/use-viewport-match/index.js
var use_viewport_match = __webpack_require__(268);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/esm/objectSpread.js
var objectSpread = __webpack_require__(27);

// CONCATENATED MODULE: ./node_modules/@wordpress/components/build-module/popover/utils.js



/**
 * Module constants
 */
var HEIGHT_OFFSET = 10; // used by the arrow and a bit of empty space

var isRTL = function isRTL() {
  return document.documentElement.dir === 'rtl';
};
/**
 * Utility used to compute the popover position over the xAxis
 *
 * @param {Object} anchorRect       Anchor Rect.
 * @param {Object} contentSize      Content Size.
 * @param {string} xAxis            Desired xAxis.
 * @param {string} chosenYAxis      yAxis to be used.
 *
 * @return {Object} Popover xAxis position and constraints.
 */


function computePopoverXAxisPosition(anchorRect, contentSize, xAxis, chosenYAxis) {
  var width = contentSize.width; // Correct xAxis for RTL support

  if (xAxis === 'left' && isRTL()) {
    xAxis = 'right';
  } else if (xAxis === 'right' && isRTL()) {
    xAxis = 'left';
  } // x axis alignment choices


  var anchorMidPoint = Math.round(anchorRect.left + anchorRect.width / 2);
  var centerAlignment = {
    popoverLeft: anchorMidPoint,
    contentWidth: (anchorMidPoint - width / 2 > 0 ? width / 2 : anchorMidPoint) + (anchorMidPoint + width / 2 > window.innerWidth ? window.innerWidth - anchorMidPoint : width / 2)
  };
  var leftAlignmentX = chosenYAxis === 'middle' ? anchorRect.left : anchorMidPoint;
  var leftAlignment = {
    popoverLeft: leftAlignmentX,
    contentWidth: leftAlignmentX - width > 0 ? width : leftAlignmentX
  };
  var rightAlignmentX = chosenYAxis === 'middle' ? anchorRect.right : anchorMidPoint;
  var rightAlignment = {
    popoverLeft: rightAlignmentX,
    contentWidth: rightAlignmentX + width > window.innerWidth ? window.innerWidth - rightAlignmentX : width
  }; // Choosing the x axis

  var chosenXAxis;
  var contentWidth = null;

  if (xAxis === 'center' && centerAlignment.contentWidth === width) {
    chosenXAxis = 'center';
  } else if (xAxis === 'left' && leftAlignment.contentWidth === width) {
    chosenXAxis = 'left';
  } else if (xAxis === 'right' && rightAlignment.contentWidth === width) {
    chosenXAxis = 'right';
  } else {
    chosenXAxis = leftAlignment.contentWidth > rightAlignment.contentWidth ? 'left' : 'right';
    var chosenWidth = chosenXAxis === 'left' ? leftAlignment.contentWidth : rightAlignment.contentWidth;
    contentWidth = chosenWidth !== width ? chosenWidth : null;
  }

  var popoverLeft;

  if (chosenXAxis === 'center') {
    popoverLeft = centerAlignment.popoverLeft;
  } else if (chosenXAxis === 'left') {
    popoverLeft = leftAlignment.popoverLeft;
  } else {
    popoverLeft = rightAlignment.popoverLeft;
  }

  return {
    xAxis: chosenXAxis,
    popoverLeft: popoverLeft,
    contentWidth: contentWidth
  };
}
/**
 * Utility used to compute the popover position over the yAxis
 *
 * @param {Object} anchorRect       Anchor Rect.
 * @param {Object} contentSize      Content Size.
 * @param {string} yAxis            Desired yAxis.
 *
 * @return {Object} Popover xAxis position and constraints.
 */

function computePopoverYAxisPosition(anchorRect, contentSize, yAxis) {
  var height = contentSize.height; // y axis alignment choices

  var anchorMidPoint = anchorRect.top + anchorRect.height / 2;
  var middleAlignment = {
    popoverTop: anchorMidPoint,
    contentHeight: (anchorMidPoint - height / 2 > 0 ? height / 2 : anchorMidPoint) + (anchorMidPoint + height / 2 > window.innerHeight ? window.innerHeight - anchorMidPoint : height / 2)
  };
  var topAlignment = {
    popoverTop: anchorRect.top,
    contentHeight: anchorRect.top - HEIGHT_OFFSET - height > 0 ? height : anchorRect.top - HEIGHT_OFFSET
  };
  var bottomAlignment = {
    popoverTop: anchorRect.bottom,
    contentHeight: anchorRect.bottom + HEIGHT_OFFSET + height > window.innerHeight ? window.innerHeight - HEIGHT_OFFSET - anchorRect.bottom : height
  }; // Choosing the y axis

  var chosenYAxis;
  var contentHeight = null;

  if (yAxis === 'middle' && middleAlignment.contentHeight === height) {
    chosenYAxis = 'middle';
  } else if (yAxis === 'top' && topAlignment.contentHeight === height) {
    chosenYAxis = 'top';
  } else if (yAxis === 'bottom' && bottomAlignment.contentHeight === height) {
    chosenYAxis = 'bottom';
  } else {
    chosenYAxis = topAlignment.contentHeight > bottomAlignment.contentHeight ? 'top' : 'bottom';
    var chosenHeight = chosenYAxis === 'top' ? topAlignment.contentHeight : bottomAlignment.contentHeight;
    contentHeight = chosenHeight !== height ? chosenHeight : null;
  }

  var popoverTop;

  if (chosenYAxis === 'middle') {
    popoverTop = middleAlignment.popoverTop;
  } else if (chosenYAxis === 'top') {
    popoverTop = topAlignment.popoverTop;
  } else {
    popoverTop = bottomAlignment.popoverTop;
  }

  return {
    yAxis: chosenYAxis,
    popoverTop: popoverTop,
    contentHeight: contentHeight
  };
}
/**
 * Utility used to compute the popover position and the content max width/height for a popover
 * given its anchor rect and its content size.
 *
 * @param {Object} anchorRect       Anchor Rect.
 * @param {Object} contentSize      Content Size.
 * @param {string} position         Position.
 *
 * @return {Object} Popover position and constraints.
 */

function computePopoverPosition(anchorRect, contentSize) {
  var position = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : 'top';

  var _position$split = position.split(' '),
      _position$split2 = Object(slicedToArray["a" /* default */])(_position$split, 2),
      yAxis = _position$split2[0],
      _position$split2$ = _position$split2[1],
      xAxis = _position$split2$ === void 0 ? 'center' : _position$split2$;

  var yAxisPosition = computePopoverYAxisPosition(anchorRect, contentSize, yAxis);
  var xAxisPosition = computePopoverXAxisPosition(anchorRect, contentSize, xAxis, yAxisPosition.yAxis);
  return Object(objectSpread["a" /* default */])({}, xAxisPosition, yAxisPosition);
}
//# sourceMappingURL=utils.js.map
// EXTERNAL MODULE: ./node_modules/@wordpress/components/build-module/higher-order/with-focus-return/index.js + 1 modules
var with_focus_return = __webpack_require__(97);

// EXTERNAL MODULE: ./node_modules/@wordpress/components/build-module/higher-order/with-constrained-tabbing/index.js
var with_constrained_tabbing = __webpack_require__(93);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/esm/classCallCheck.js
var classCallCheck = __webpack_require__(7);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/esm/createClass.js
var createClass = __webpack_require__(6);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/esm/possibleConstructorReturn.js
var possibleConstructorReturn = __webpack_require__(8);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/esm/getPrototypeOf.js
var getPrototypeOf = __webpack_require__(4);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/esm/inherits.js + 1 modules
var inherits = __webpack_require__(9);

// EXTERNAL MODULE: ./node_modules/@wordpress/components/build-module/higher-order/with-focus-outside/index.js
var with_focus_outside = __webpack_require__(95);

// CONCATENATED MODULE: ./node_modules/@wordpress/components/build-module/popover/detect-outside.js






/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */



var detect_outside_PopoverDetectOutside =
/*#__PURE__*/
function (_Component) {
  Object(inherits["a" /* default */])(PopoverDetectOutside, _Component);

  function PopoverDetectOutside() {
    Object(classCallCheck["a" /* default */])(this, PopoverDetectOutside);

    return Object(possibleConstructorReturn["a" /* default */])(this, Object(getPrototypeOf["a" /* default */])(PopoverDetectOutside).apply(this, arguments));
  }

  Object(createClass["a" /* default */])(PopoverDetectOutside, [{
    key: "handleFocusOutside",
    value: function handleFocusOutside(event) {
      this.props.onFocusOutside(event);
    }
  }, {
    key: "render",
    value: function render() {
      return this.props.children;
    }
  }]);

  return PopoverDetectOutside;
}(external_this_wp_element_["Component"]);

/* harmony default export */ var detect_outside = (Object(with_focus_outside["a" /* default */])(detect_outside_PopoverDetectOutside));
//# sourceMappingURL=detect-outside.js.map
// EXTERNAL MODULE: ./node_modules/@wordpress/components/build-module/icon-button/index.js
var icon_button = __webpack_require__(85);

// CONCATENATED MODULE: ./node_modules/@wordpress/components/build-module/scroll-lock/index.js






/**
 * WordPress dependencies
 */

/**
 * Creates a ScrollLock component bound to the specified document.
 *
 * This function creates a ScrollLock component for the specified document
 * and is exposed so we can create an isolated component for unit testing.
 *
 * @param {Object} args Keyword args.
 * @param {HTMLDocument} args.htmlDocument The document to lock the scroll for.
 * @param {string} args.className The name of the class used to lock scrolling.
 * @return {WPComponent} The bound ScrollLock component.
 */

function createScrollLockComponent() {
  var _ref = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {},
      _ref$htmlDocument = _ref.htmlDocument,
      htmlDocument = _ref$htmlDocument === void 0 ? document : _ref$htmlDocument,
      _ref$className = _ref.className,
      className = _ref$className === void 0 ? 'lockscroll' : _ref$className;

  var lockCounter = 0;
  /*
   * Setting `overflow: hidden` on html and body elements resets body scroll in iOS.
   * Save scroll top so we can restore it after locking scroll.
   *
   * NOTE: It would be cleaner and possibly safer to find a localized solution such
   * as preventing default on certain touchmove events.
   */

  var previousScrollTop = 0;
  /**
   * Locks and unlocks scroll depending on the boolean argument.
   *
   * @param {boolean} locked Whether or not scroll should be locked.
   */

  function setLocked(locked) {
    var scrollingElement = htmlDocument.scrollingElement || htmlDocument.body;

    if (locked) {
      previousScrollTop = scrollingElement.scrollTop;
    }

    var methodName = locked ? 'add' : 'remove';
    scrollingElement.classList[methodName](className); // Adding the class to the document element seems to be necessary in iOS.

    htmlDocument.documentElement.classList[methodName](className);

    if (!locked) {
      scrollingElement.scrollTop = previousScrollTop;
    }
  }
  /**
   * Requests scroll lock.
   *
   * This function tracks requests for scroll lock. It locks scroll on the first
   * request and counts each request so `releaseLock` can unlock scroll when
   * all requests have been released.
   */


  function requestLock() {
    if (lockCounter === 0) {
      setLocked(true);
    }

    ++lockCounter;
  }
  /**
   * Releases a request for scroll lock.
   *
   * This function tracks released requests for scroll lock. When all requests
   * have been released, it unlocks scroll.
   */


  function releaseLock() {
    if (lockCounter === 1) {
      setLocked(false);
    }

    --lockCounter;
  }

  return (
    /*#__PURE__*/
    function (_Component) {
      Object(inherits["a" /* default */])(ScrollLock, _Component);

      function ScrollLock() {
        Object(classCallCheck["a" /* default */])(this, ScrollLock);

        return Object(possibleConstructorReturn["a" /* default */])(this, Object(getPrototypeOf["a" /* default */])(ScrollLock).apply(this, arguments));
      }

      Object(createClass["a" /* default */])(ScrollLock, [{
        key: "componentDidMount",

        /**
         * Requests scroll lock on mount.
         */
        value: function componentDidMount() {
          requestLock();
        }
        /**
         * Releases scroll lock before unmount.
         */

      }, {
        key: "componentWillUnmount",
        value: function componentWillUnmount() {
          releaseLock();
        }
        /**
         * Render nothing as this component is merely a way to declare scroll lock.
         *
         * @return {null} Render nothing by returning `null`.
         */

      }, {
        key: "render",
        value: function render() {
          return null;
        }
      }]);

      return ScrollLock;
    }(external_this_wp_element_["Component"])
  );
}
/* harmony default export */ var scroll_lock = (createScrollLockComponent());
//# sourceMappingURL=index.js.map
// EXTERNAL MODULE: ./node_modules/@wordpress/components/build-module/isolated-event-container/index.js
var isolated_event_container = __webpack_require__(96);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/esm/toConsumableArray.js + 3 modules
var toConsumableArray = __webpack_require__(17);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/esm/assertThisInitialized.js
var assertThisInitialized = __webpack_require__(5);

// EXTERNAL MODULE: external "lodash"
var external_lodash_ = __webpack_require__(2);

// CONCATENATED MODULE: ./node_modules/@wordpress/components/build-module/slot-fill/context.js










/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */


var SlotFillContext = Object(external_this_wp_element_["createContext"])({
  registerSlot: function registerSlot() {},
  unregisterSlot: function unregisterSlot() {},
  registerFill: function registerFill() {},
  unregisterFill: function unregisterFill() {},
  getSlot: function getSlot() {},
  getFills: function getFills() {},
  subscribe: function subscribe() {}
});
var Provider = SlotFillContext.Provider,
    Consumer = SlotFillContext.Consumer;

var context_SlotFillProvider =
/*#__PURE__*/
function (_Component) {
  Object(inherits["a" /* default */])(SlotFillProvider, _Component);

  function SlotFillProvider() {
    var _this;

    Object(classCallCheck["a" /* default */])(this, SlotFillProvider);

    _this = Object(possibleConstructorReturn["a" /* default */])(this, Object(getPrototypeOf["a" /* default */])(SlotFillProvider).apply(this, arguments));
    _this.registerSlot = _this.registerSlot.bind(Object(assertThisInitialized["a" /* default */])(_this));
    _this.registerFill = _this.registerFill.bind(Object(assertThisInitialized["a" /* default */])(_this));
    _this.unregisterSlot = _this.unregisterSlot.bind(Object(assertThisInitialized["a" /* default */])(_this));
    _this.unregisterFill = _this.unregisterFill.bind(Object(assertThisInitialized["a" /* default */])(_this));
    _this.getSlot = _this.getSlot.bind(Object(assertThisInitialized["a" /* default */])(_this));
    _this.getFills = _this.getFills.bind(Object(assertThisInitialized["a" /* default */])(_this));
    _this.hasFills = _this.hasFills.bind(Object(assertThisInitialized["a" /* default */])(_this));
    _this.subscribe = _this.subscribe.bind(Object(assertThisInitialized["a" /* default */])(_this));
    _this.slots = {};
    _this.fills = {};
    _this.listeners = [];
    _this.contextValue = {
      registerSlot: _this.registerSlot,
      unregisterSlot: _this.unregisterSlot,
      registerFill: _this.registerFill,
      unregisterFill: _this.unregisterFill,
      getSlot: _this.getSlot,
      getFills: _this.getFills,
      hasFills: _this.hasFills,
      subscribe: _this.subscribe
    };
    return _this;
  }

  Object(createClass["a" /* default */])(SlotFillProvider, [{
    key: "registerSlot",
    value: function registerSlot(name, slot) {
      var previousSlot = this.slots[name];
      this.slots[name] = slot;
      this.triggerListeners(); // Sometimes the fills are registered after the initial render of slot
      // But before the registerSlot call, we need to rerender the slot

      this.forceUpdateSlot(name); // If a new instance of a slot is being mounted while another with the
      // same name exists, force its update _after_ the new slot has been
      // assigned into the instance, such that its own rendering of children
      // will be empty (the new Slot will subsume all fills for this name).

      if (previousSlot) {
        previousSlot.forceUpdate();
      }
    }
  }, {
    key: "registerFill",
    value: function registerFill(name, instance) {
      this.fills[name] = [].concat(Object(toConsumableArray["a" /* default */])(this.fills[name] || []), [instance]);
      this.forceUpdateSlot(name);
    }
  }, {
    key: "unregisterSlot",
    value: function unregisterSlot(name, instance) {
      // If a previous instance of a Slot by this name unmounts, do nothing,
      // as the slot and its fills should only be removed for the current
      // known instance.
      if (this.slots[name] !== instance) {
        return;
      }

      delete this.slots[name];
      this.triggerListeners();
    }
  }, {
    key: "unregisterFill",
    value: function unregisterFill(name, instance) {
      this.fills[name] = Object(external_lodash_["without"])(this.fills[name], instance);
      this.resetFillOccurrence(name);
      this.forceUpdateSlot(name);
    }
  }, {
    key: "getSlot",
    value: function getSlot(name) {
      return this.slots[name];
    }
  }, {
    key: "getFills",
    value: function getFills(name, slotInstance) {
      // Fills should only be returned for the current instance of the slot
      // in which they occupy.
      if (this.slots[name] !== slotInstance) {
        return [];
      }

      return Object(external_lodash_["sortBy"])(this.fills[name], 'occurrence');
    }
  }, {
    key: "hasFills",
    value: function hasFills(name) {
      return this.fills[name] && !!this.fills[name].length;
    }
  }, {
    key: "resetFillOccurrence",
    value: function resetFillOccurrence(name) {
      Object(external_lodash_["forEach"])(this.fills[name], function (instance) {
        instance.occurrence = undefined;
      });
    }
  }, {
    key: "forceUpdateSlot",
    value: function forceUpdateSlot(name) {
      var slot = this.getSlot(name);

      if (slot) {
        slot.forceUpdate();
      }
    }
  }, {
    key: "triggerListeners",
    value: function triggerListeners() {
      this.listeners.forEach(function (listener) {
        return listener();
      });
    }
  }, {
    key: "subscribe",
    value: function subscribe(listener) {
      var _this2 = this;

      this.listeners.push(listener);
      return function () {
        _this2.listeners = Object(external_lodash_["without"])(_this2.listeners, listener);
      };
    }
  }, {
    key: "render",
    value: function render() {
      return Object(external_this_wp_element_["createElement"])(Provider, {
        value: this.contextValue
      }, this.props.children);
    }
  }]);

  return SlotFillProvider;
}(external_this_wp_element_["Component"]);
/**
 * React hook returning the active slot given a name.
 *
 * @param {string} name Slot name.
 * @return {Object} Slot object.
 */


var context_useSlot = function useSlot(name) {
  var _useContext = Object(external_this_wp_element_["useContext"])(SlotFillContext),
      getSlot = _useContext.getSlot,
      subscribe = _useContext.subscribe;

  var _useState = Object(external_this_wp_element_["useState"])(getSlot(name)),
      _useState2 = Object(slicedToArray["a" /* default */])(_useState, 2),
      slot = _useState2[0],
      setSlot = _useState2[1];

  Object(external_this_wp_element_["useEffect"])(function () {
    setSlot(getSlot(name));
    var unsubscribe = subscribe(function () {
      setSlot(getSlot(name));
    });
    return unsubscribe;
  }, [name]);
  return slot;
};
/* harmony default export */ var context = (context_SlotFillProvider);

//# sourceMappingURL=context.js.map
// CONCATENATED MODULE: ./node_modules/@wordpress/components/build-module/slot-fill/fill.js



/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */


/**
 * Internal dependencies
 */


var occurrences = 0;

function FillComponent(_ref) {
  var name = _ref.name,
      children = _ref.children,
      registerFill = _ref.registerFill,
      unregisterFill = _ref.unregisterFill;
  var slot = context_useSlot(name);
  var ref = Object(external_this_wp_element_["useRef"])({
    name: name,
    children: children
  });

  if (!ref.current.occurrence) {
    ref.current.occurrence = ++occurrences;
  }

  Object(external_this_wp_element_["useLayoutEffect"])(function () {
    registerFill(name, ref.current);
    return function () {
      return unregisterFill(name, ref.current);
    };
  }, []);
  Object(external_this_wp_element_["useLayoutEffect"])(function () {
    ref.current.children = children;

    if (slot && !slot.props.bubblesVirtually) {
      slot.forceUpdate();
    }
  }, [children]);
  Object(external_this_wp_element_["useLayoutEffect"])(function () {
    if (name === ref.current.name) {
      // ignore initial effect
      return;
    }

    unregisterFill(ref.current.name, ref.current);
    ref.current.name = name;
    registerFill(name, ref.current);
  }, [name]);

  if (!slot || !slot.node || !slot.props.bubblesVirtually) {
    return null;
  } // If a function is passed as a child, provide it with the fillProps.


  if (Object(external_lodash_["isFunction"])(children)) {
    children = children(slot.props.fillProps);
  }

  return Object(external_this_wp_element_["createPortal"])(children, slot.node);
}

var fill_Fill = function Fill(props) {
  return Object(external_this_wp_element_["createElement"])(Consumer, null, function (_ref2) {
    var registerFill = _ref2.registerFill,
        unregisterFill = _ref2.unregisterFill;
    return Object(external_this_wp_element_["createElement"])(FillComponent, Object(esm_extends["a" /* default */])({}, props, {
      registerFill: registerFill,
      unregisterFill: unregisterFill
    }));
  });
};

/* harmony default export */ var slot_fill_fill = (fill_Fill);
//# sourceMappingURL=fill.js.map
// CONCATENATED MODULE: ./node_modules/@wordpress/components/build-module/slot-fill/slot.js









/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */


/**
 * Internal dependencies
 */



var slot_SlotComponent =
/*#__PURE__*/
function (_Component) {
  Object(inherits["a" /* default */])(SlotComponent, _Component);

  function SlotComponent() {
    var _this;

    Object(classCallCheck["a" /* default */])(this, SlotComponent);

    _this = Object(possibleConstructorReturn["a" /* default */])(this, Object(getPrototypeOf["a" /* default */])(SlotComponent).apply(this, arguments));
    _this.bindNode = _this.bindNode.bind(Object(assertThisInitialized["a" /* default */])(_this));
    return _this;
  }

  Object(createClass["a" /* default */])(SlotComponent, [{
    key: "componentDidMount",
    value: function componentDidMount() {
      var registerSlot = this.props.registerSlot;
      registerSlot(this.props.name, this);
    }
  }, {
    key: "componentWillUnmount",
    value: function componentWillUnmount() {
      var unregisterSlot = this.props.unregisterSlot;
      unregisterSlot(this.props.name, this);
    }
  }, {
    key: "componentDidUpdate",
    value: function componentDidUpdate(prevProps) {
      var _this$props = this.props,
          name = _this$props.name,
          unregisterSlot = _this$props.unregisterSlot,
          registerSlot = _this$props.registerSlot;

      if (prevProps.name !== name) {
        unregisterSlot(prevProps.name);
        registerSlot(name, this);
      }
    }
  }, {
    key: "bindNode",
    value: function bindNode(node) {
      this.node = node;
    }
  }, {
    key: "render",
    value: function render() {
      var _this$props2 = this.props,
          children = _this$props2.children,
          name = _this$props2.name,
          _this$props2$bubblesV = _this$props2.bubblesVirtually,
          bubblesVirtually = _this$props2$bubblesV === void 0 ? false : _this$props2$bubblesV,
          _this$props2$fillProp = _this$props2.fillProps,
          fillProps = _this$props2$fillProp === void 0 ? {} : _this$props2$fillProp,
          getFills = _this$props2.getFills,
          className = _this$props2.className;

      if (bubblesVirtually) {
        return Object(external_this_wp_element_["createElement"])("div", {
          ref: this.bindNode,
          className: className
        });
      }

      var fills = Object(external_lodash_["map"])(getFills(name, this), function (fill) {
        var fillKey = fill.occurrence;
        var fillChildren = Object(external_lodash_["isFunction"])(fill.children) ? fill.children(fillProps) : fill.children;
        return external_this_wp_element_["Children"].map(fillChildren, function (child, childIndex) {
          if (!child || Object(external_lodash_["isString"])(child)) {
            return child;
          }

          var childKey = "".concat(fillKey, "---").concat(child.key || childIndex);
          return Object(external_this_wp_element_["cloneElement"])(child, {
            key: childKey
          });
        });
      }).filter( // In some cases fills are rendered only when some conditions apply.
      // This ensures that we only use non-empty fills when rendering, i.e.,
      // it allows us to render wrappers only when the fills are actually present.
      Object(external_lodash_["negate"])(external_this_wp_element_["isEmptyElement"]));
      return Object(external_this_wp_element_["createElement"])(external_this_wp_element_["Fragment"], null, Object(external_lodash_["isFunction"])(children) ? children(fills) : fills);
    }
  }]);

  return SlotComponent;
}(external_this_wp_element_["Component"]);

var slot_Slot = function Slot(props) {
  return Object(external_this_wp_element_["createElement"])(Consumer, null, function (_ref) {
    var registerSlot = _ref.registerSlot,
        unregisterSlot = _ref.unregisterSlot,
        getFills = _ref.getFills;
    return Object(external_this_wp_element_["createElement"])(slot_SlotComponent, Object(esm_extends["a" /* default */])({}, props, {
      registerSlot: registerSlot,
      unregisterSlot: unregisterSlot,
      getFills: getFills
    }));
  });
};

/* harmony default export */ var slot_fill_slot = (slot_Slot);
//# sourceMappingURL=slot.js.map
// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/esm/defineProperty.js
var defineProperty = __webpack_require__(13);

// CONCATENATED MODULE: ./node_modules/@wordpress/components/build-module/animate/index.js



/**
 * External dependencies
 */


function Animate(_ref) {
  var type = _ref.type,
      _ref$options = _ref.options,
      options = _ref$options === void 0 ? {} : _ref$options,
      children = _ref.children;

  if (type === 'appear') {
    var _classnames;

    var _options$origin = options.origin,
        origin = _options$origin === void 0 ? 'top' : _options$origin;

    var _origin$split = origin.split(' '),
        _origin$split2 = Object(slicedToArray["a" /* default */])(_origin$split, 2),
        yAxis = _origin$split2[0],
        _origin$split2$ = _origin$split2[1],
        xAxis = _origin$split2$ === void 0 ? 'center' : _origin$split2$;

    return children({
      className: classnames_default()('components-animate__appear', (_classnames = {}, Object(defineProperty["a" /* default */])(_classnames, 'is-from-' + xAxis, xAxis !== 'center'), Object(defineProperty["a" /* default */])(_classnames, 'is-from-' + yAxis, yAxis !== 'middle'), _classnames))
    });
  }

  if (type === 'slide-in') {
    var _options$origin2 = options.origin,
        _origin = _options$origin2 === void 0 ? 'left' : _options$origin2;

    return children({
      className: classnames_default()('components-animate__slide-in', 'is-from-' + _origin)
    });
  }

  if (type === 'loading') {
    return children({
      className: classnames_default()('components-animate__loading')
    });
  }

  return children({});
}

/* harmony default export */ var build_module_animate = (Animate);
//# sourceMappingURL=index.js.map
// CONCATENATED MODULE: ./node_modules/@wordpress/components/build-module/popover/index.js





/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */






/**
 * Internal dependencies
 */










var FocusManaged = Object(with_constrained_tabbing["a" /* default */])(Object(with_focus_return["a" /* default */])(function (_ref) {
  var children = _ref.children;
  return children;
}));
/**
 * Name of slot in which popover should fill.
 *
 * @type {string}
 */

var SLOT_NAME = 'Popover';

function computeAnchorRect(anchorRefFallback, anchorRect, getAnchorRect) {
  var anchorRef = arguments.length > 3 && arguments[3] !== undefined ? arguments[3] : false;
  var shouldAnchorIncludePadding = arguments.length > 4 ? arguments[4] : undefined;

  if (anchorRect) {
    return anchorRect;
  }

  if (getAnchorRect) {
    if (!anchorRefFallback.current) {
      return;
    }

    return getAnchorRect(anchorRefFallback.current);
  }

  if (anchorRef !== false) {
    if (!anchorRef) {
      return;
    }

    if (anchorRef instanceof window.Range) {
      return Object(dom["a" /* getRectangleFromRange */])(anchorRef);
    }

    var _rect = anchorRef.getBoundingClientRect();

    if (shouldAnchorIncludePadding) {
      return _rect;
    }

    return withoutPadding(_rect, anchorRef);
  }

  if (!anchorRefFallback.current) {
    return;
  }

  var parentNode = anchorRefFallback.current.parentNode;
  var rect = parentNode.getBoundingClientRect();

  if (shouldAnchorIncludePadding) {
    return rect;
  }

  return withoutPadding(rect, parentNode);
}

function addBuffer(rect) {
  var verticalBuffer = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : 0;
  var horizontalBuffer = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : 0;
  return {
    x: rect.left - horizontalBuffer,
    y: rect.top - verticalBuffer,
    width: rect.width + 2 * horizontalBuffer,
    height: rect.height + 2 * verticalBuffer,
    left: rect.left - horizontalBuffer,
    right: rect.right + horizontalBuffer,
    top: rect.top - verticalBuffer,
    bottom: rect.bottom + verticalBuffer
  };
}

function withoutPadding(rect, element) {
  var _window$getComputedSt = window.getComputedStyle(element),
      paddingTop = _window$getComputedSt.paddingTop,
      paddingBottom = _window$getComputedSt.paddingBottom,
      paddingLeft = _window$getComputedSt.paddingLeft,
      paddingRight = _window$getComputedSt.paddingRight;

  var top = paddingTop ? parseInt(paddingTop, 10) : 0;
  var bottom = paddingBottom ? parseInt(paddingBottom, 10) : 0;
  var left = paddingLeft ? parseInt(paddingLeft, 10) : 0;
  var right = paddingRight ? parseInt(paddingRight, 10) : 0;
  return {
    x: rect.left + left,
    y: rect.top + top,
    width: rect.width - left - right,
    height: rect.height - top - bottom,
    left: rect.left + left,
    right: rect.right - right,
    top: rect.top + top,
    bottom: rect.bottom - bottom
  };
}
/**
 * Hook used to focus the first tabbable element on mount.
 *
 * @param {boolean|string} focusOnMount Focus on mount mode.
 * @param {Object}         contentRef   Reference to the popover content element.
 */


function useFocusContentOnMount(focusOnMount, contentRef) {
  // Focus handling
  Object(external_this_wp_element_["useEffect"])(function () {
    /*
     * Without the setTimeout, the dom node is not being focused. Related:
     * https://stackoverflow.com/questions/35522220/react-ref-with-focus-doesnt-work-without-settimeout-my-example
     *
     * TODO: Treat the cause, not the symptom.
     */
    var focusTimeout = setTimeout(function () {
      if (!focusOnMount || !contentRef.current) {
        return;
      }

      if (focusOnMount === 'firstElement') {
        // Find first tabbable node within content and shift focus, falling
        // back to the popover panel itself.
        var firstTabbable = build_module["a" /* focus */].tabbable.find(contentRef.current)[0];

        if (firstTabbable) {
          firstTabbable.focus();
        } else {
          contentRef.current.focus();
        }

        return;
      }

      if (focusOnMount === 'container') {
        // Focus the popover panel itself so items in the popover are easily
        // accessed via keyboard navigation.
        contentRef.current.focus();
      }
    }, 0);
    return function () {
      return clearTimeout(focusTimeout);
    };
  }, []);
}
/**
 * Sets or removes an element attribute.
 *
 * @param {Element} element The element to modify.
 * @param {string}  name    The attribute name to set or remove.
 * @param {?string} value   The value to set. A falsy value will remove the
 *                          attribute.
 */


function setAttribute(element, name, value) {
  if (!value) {
    if (element.hasAttribute(name)) {
      element.removeAttribute(name);
    }
  } else if (element.getAttribute(name) !== value) {
    element.setAttribute(name, value);
  }
}
/**
 * Sets or removes an element style property.
 *
 * @param {Element} element  The element to modify.
 * @param {string}  property The property to set or remove.
 * @param {?string} value    The value to set. A falsy value will remove the
 *                           property.
 */


function setStyle(element, property) {
  var value = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : '';

  if (element.style[property] !== value) {
    element.style[property] = value;
  }
}
/**
 * Sets or removes an element class.
 *
 * @param {Element} element The element to modify.
 * @param {string}  name    The class to set or remove.
 * @param {boolean} toggle  True to set the class, false to remove.
 */


function setClass(element, name, toggle) {
  if (toggle) {
    if (!element.classList.contains(name)) {
      element.classList.add(name);
    }
  } else if (element.classList.contains(name)) {
    element.classList.remove(name);
  }
}

var popover_Popover = function Popover(_ref2) {
  var headerTitle = _ref2.headerTitle,
      onClose = _ref2.onClose,
      onKeyDown = _ref2.onKeyDown,
      children = _ref2.children,
      className = _ref2.className,
      _ref2$noArrow = _ref2.noArrow,
      noArrow = _ref2$noArrow === void 0 ? false : _ref2$noArrow,
      _ref2$position = _ref2.position,
      position = _ref2$position === void 0 ? 'top' : _ref2$position,
      range = _ref2.range,
      _ref2$focusOnMount = _ref2.focusOnMount,
      focusOnMount = _ref2$focusOnMount === void 0 ? 'firstElement' : _ref2$focusOnMount,
      anchorRef = _ref2.anchorRef,
      shouldAnchorIncludePadding = _ref2.shouldAnchorIncludePadding,
      anchorVerticalBuffer = _ref2.anchorVerticalBuffer,
      anchorHorizontalBuffer = _ref2.anchorHorizontalBuffer,
      anchorRect = _ref2.anchorRect,
      getAnchorRect = _ref2.getAnchorRect,
      expandOnMobile = _ref2.expandOnMobile,
      _ref2$animate = _ref2.animate,
      animate = _ref2$animate === void 0 ? true : _ref2$animate,
      onClickOutside = _ref2.onClickOutside,
      onFocusOutside = _ref2.onFocusOutside,
      contentProps = Object(objectWithoutProperties["a" /* default */])(_ref2, ["headerTitle", "onClose", "onKeyDown", "children", "className", "noArrow", "position", "range", "focusOnMount", "anchorRef", "shouldAnchorIncludePadding", "anchorVerticalBuffer", "anchorHorizontalBuffer", "anchorRect", "getAnchorRect", "expandOnMobile", "animate", "onClickOutside", "onFocusOutside"]);

  var anchorRefFallback = Object(external_this_wp_element_["useRef"])(null);
  var contentRef = Object(external_this_wp_element_["useRef"])(null);
  var containerRef = Object(external_this_wp_element_["useRef"])();
  var contentRect = Object(external_this_wp_element_["useRef"])();
  var isMobileViewport = Object(use_viewport_match["a" /* default */])('medium', '<');

  var _useState = Object(external_this_wp_element_["useState"])(),
      _useState2 = Object(slicedToArray["a" /* default */])(_useState, 2),
      animateOrigin = _useState2[0],
      setAnimateOrigin = _useState2[1];

  var isExpanded = expandOnMobile && isMobileViewport;
  noArrow = isExpanded || noArrow;
  Object(external_this_wp_element_["useEffect"])(function () {
    var containerEl = containerRef.current;
    var contentEl = contentRef.current;

    if (isExpanded) {
      setClass(containerEl, 'is-without-arrow', noArrow);
      setAttribute(containerEl, 'data-x-axis');
      setAttribute(containerEl, 'data-y-axis');
      setStyle(containerEl, 'top');
      setStyle(containerEl, 'left');
      setStyle(contentEl, 'maxHeight');
      setStyle(contentEl, 'maxWidth');
      return;
    }

    var refresh = function refresh() {
      var anchor = computeAnchorRect(anchorRefFallback, anchorRect, getAnchorRect, anchorRef, shouldAnchorIncludePadding);

      if (!anchor) {
        return;
      }

      anchor = addBuffer(anchor, anchorVerticalBuffer, anchorHorizontalBuffer);

      if (!contentRect.current) {
        contentRect.current = contentEl.getBoundingClientRect();
      }

      var _computePopoverPositi = computePopoverPosition(anchor, contentRect.current, position),
          popoverTop = _computePopoverPositi.popoverTop,
          popoverLeft = _computePopoverPositi.popoverLeft,
          xAxis = _computePopoverPositi.xAxis,
          yAxis = _computePopoverPositi.yAxis,
          contentHeight = _computePopoverPositi.contentHeight,
          contentWidth = _computePopoverPositi.contentWidth;

      setClass(containerEl, 'is-without-arrow', noArrow || xAxis === 'center' && yAxis === 'middle');
      setAttribute(containerEl, 'data-x-axis', xAxis);
      setAttribute(containerEl, 'data-y-axis', yAxis);
      setStyle(containerEl, 'top', typeof popoverTop === 'number' ? popoverTop + 'px' : '');
      setStyle(containerEl, 'left', typeof popoverLeft === 'number' ? popoverLeft + 'px' : '');
      setStyle(contentEl, 'maxHeight', typeof contentHeight === 'number' ? contentHeight + 'px' : '');
      setStyle(contentEl, 'maxWidth', typeof contentWidth === 'number' ? contentWidth + 'px' : ''); // Compute the animation position

      var yAxisMapping = {
        top: 'bottom',
        bottom: 'top'
      };
      var xAxisMapping = {
        left: 'right',
        right: 'left'
      };
      var animateYAxis = yAxisMapping[yAxis] || 'middle';
      var animateXAxis = xAxisMapping[xAxis] || 'center';
      setAnimateOrigin(animateXAxis + ' ' + animateYAxis);
    }; // Height may still adjust between now and the next tick.


    var timeoutId = window.setTimeout(refresh);
    /*
     * There are sometimes we need to reposition or resize the popover that
     * are not handled by the resize/scroll window events (i.e. CSS changes
     * in the layout that changes the position of the anchor).
     *
     * For these situations, we refresh the popover every 0.5s
     */

    var intervalHandle = window.setInterval(refresh, 500);
    window.addEventListener('resize', refresh);
    window.addEventListener('scroll', refresh, true);
    return function () {
      window.clearTimeout(timeoutId);
      window.clearInterval(intervalHandle);
      window.removeEventListener('resize', refresh);
      window.removeEventListener('scroll', refresh, true);
    };
  }, [isExpanded, anchorRect, getAnchorRect, anchorRef, shouldAnchorIncludePadding, anchorVerticalBuffer, anchorHorizontalBuffer, position]);
  useFocusContentOnMount(focusOnMount, contentRef); // Event handlers

  var maybeClose = function maybeClose(event) {
    // Close on escape
    if (event.keyCode === keycodes_build_module["d" /* ESCAPE */] && onClose) {
      event.stopPropagation();
      onClose();
    } // Preserve original content prop behavior


    if (onKeyDown) {
      onKeyDown(event);
    }
  };
  /**
   * Shims an onFocusOutside callback to be compatible with a deprecated
   * onClickOutside prop function, if provided.
   *
   * @param {FocusEvent} event Focus event from onFocusOutside.
   */


  function handleOnFocusOutside(event) {
    // Defer to given `onFocusOutside` if specified. Call `onClose` only if
    // both `onFocusOutside` and `onClickOutside` are unspecified. Doing so
    // assures backwards-compatibility for prior `onClickOutside` default.
    if (onFocusOutside) {
      onFocusOutside(event);
      return;
    } else if (!onClickOutside) {
      if (onClose) {
        onClose();
      }

      return;
    } // Simulate MouseEvent using FocusEvent#relatedTarget as emulated click
    // target. MouseEvent constructor is unsupported in Internet Explorer.


    var clickEvent;

    try {
      clickEvent = new window.MouseEvent('click');
    } catch (error) {
      clickEvent = document.createEvent('MouseEvent');
      clickEvent.initMouseEvent('click', true, true, window, 0, 0, 0, 0, 0, false, false, false, false, 0, null);
    }

    Object.defineProperty(clickEvent, 'target', {
      get: function get() {
        return event.relatedTarget;
      }
    });
    Object(deprecated_build_module["a" /* default */])('Popover onClickOutside prop', {
      alternative: 'onFocusOutside'
    });
    onClickOutside(clickEvent);
  } // Disable reason: We care to capture the _bubbled_ events from inputs
  // within popover as inferring close intent.


  var content = Object(external_this_wp_element_["createElement"])(detect_outside, {
    onFocusOutside: handleOnFocusOutside
  }, Object(external_this_wp_element_["createElement"])(build_module_animate, {
    type: animate && animateOrigin ? 'appear' : null,
    options: {
      origin: animateOrigin
    }
  }, function (_ref3) {
    var animateClassName = _ref3.className;
    return Object(external_this_wp_element_["createElement"])(isolated_event_container["a" /* default */], Object(esm_extends["a" /* default */])({
      className: classnames_default()('components-popover', className, animateClassName, {
        'is-expanded': isExpanded,
        'is-without-arrow': noArrow
      })
    }, contentProps, {
      onKeyDown: maybeClose,
      ref: containerRef
    }), isExpanded && Object(external_this_wp_element_["createElement"])("div", {
      className: "components-popover__header"
    }, Object(external_this_wp_element_["createElement"])("span", {
      className: "components-popover__header-title"
    }, headerTitle), Object(external_this_wp_element_["createElement"])(icon_button["a" /* default */], {
      className: "components-popover__close",
      icon: "no-alt",
      onClick: onClose
    })), Object(external_this_wp_element_["createElement"])("div", {
      ref: contentRef,
      className: "components-popover__content",
      tabIndex: "-1"
    }, children));
  })); // Apply focus to element as long as focusOnMount is truthy; false is
  // the only "disabled" value.

  if (focusOnMount) {
    content = Object(external_this_wp_element_["createElement"])(FocusManaged, null, content);
  }

  return Object(external_this_wp_element_["createElement"])(Consumer, null, function (_ref4) {
    var getSlot = _ref4.getSlot;

    // In case there is no slot context in which to render,
    // default to an in-place rendering.
    if (getSlot && getSlot(SLOT_NAME)) {
      content = Object(external_this_wp_element_["createElement"])(slot_fill_fill, {
        name: SLOT_NAME
      }, content);
    }

    return Object(external_this_wp_element_["createElement"])("span", {
      ref: anchorRefFallback
    }, content, isMobileViewport && expandOnMobile && Object(external_this_wp_element_["createElement"])(scroll_lock, null));
  });
};

var PopoverContainer = popover_Popover;

PopoverContainer.Slot = function () {
  return Object(external_this_wp_element_["createElement"])(slot_fill_slot, {
    bubblesVirtually: true,
    name: SLOT_NAME
  });
};

/* harmony default export */ var popover = __webpack_exports__["a"] = (PopoverContainer);
//# sourceMappingURL=index.js.map

/***/ }),

/***/ 93:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var _babel_runtime_helpers_esm_classCallCheck__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(7);
/* harmony import */ var _babel_runtime_helpers_esm_createClass__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(6);
/* harmony import */ var _babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(8);
/* harmony import */ var _babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(4);
/* harmony import */ var _babel_runtime_helpers_esm_assertThisInitialized__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(5);
/* harmony import */ var _babel_runtime_helpers_esm_inherits__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(9);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(53);
/* harmony import */ var _wordpress_keycodes__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(18);
/* harmony import */ var _wordpress_dom__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(50);








/**
 * WordPress dependencies
 */




var withConstrainedTabbing = Object(_wordpress_compose__WEBPACK_IMPORTED_MODULE_7__[/* default */ "a"])(function (WrappedComponent) {
  return (
    /*#__PURE__*/
    function (_Component) {
      Object(_babel_runtime_helpers_esm_inherits__WEBPACK_IMPORTED_MODULE_5__[/* default */ "a"])(_class, _Component);

      function _class() {
        var _this;

        Object(_babel_runtime_helpers_esm_classCallCheck__WEBPACK_IMPORTED_MODULE_0__[/* default */ "a"])(this, _class);

        _this = Object(_babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_2__[/* default */ "a"])(this, Object(_babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_3__[/* default */ "a"])(_class).apply(this, arguments));
        _this.focusContainRef = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createRef"])();
        _this.handleTabBehaviour = _this.handleTabBehaviour.bind(Object(_babel_runtime_helpers_esm_assertThisInitialized__WEBPACK_IMPORTED_MODULE_4__[/* default */ "a"])(_this));
        return _this;
      }

      Object(_babel_runtime_helpers_esm_createClass__WEBPACK_IMPORTED_MODULE_1__[/* default */ "a"])(_class, [{
        key: "handleTabBehaviour",
        value: function handleTabBehaviour(event) {
          if (event.keyCode !== _wordpress_keycodes__WEBPACK_IMPORTED_MODULE_8__[/* TAB */ "h"]) {
            return;
          }

          var tabbables = _wordpress_dom__WEBPACK_IMPORTED_MODULE_9__[/* focus */ "a"].tabbable.find(this.focusContainRef.current);

          if (!tabbables.length) {
            return;
          }

          var firstTabbable = tabbables[0];
          var lastTabbable = tabbables[tabbables.length - 1];

          if (event.shiftKey && event.target === firstTabbable) {
            event.preventDefault();
            lastTabbable.focus();
          } else if (!event.shiftKey && event.target === lastTabbable) {
            event.preventDefault();
            firstTabbable.focus();
            /*
             * When pressing Tab and none of the tabbables has focus, the keydown
             * event happens on the wrapper div: move focus on the first tabbable.
             */
          } else if (!tabbables.includes(event.target)) {
            event.preventDefault();
            firstTabbable.focus();
          }
        }
      }, {
        key: "render",
        value: function render() {
          // Disable reason: this component is non-interactive, but must capture
          // events from the wrapped component to determine when the Tab key is used.

          /* eslint-disable jsx-a11y/no-static-element-interactions */
          return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("div", {
            onKeyDown: this.handleTabBehaviour,
            ref: this.focusContainRef,
            tabIndex: "-1"
          }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])(WrappedComponent, this.props));
          /* eslint-enable jsx-a11y/no-static-element-interactions */
        }
      }]);

      return _class;
    }(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["Component"])
  );
}, 'withConstrainedTabbing');
/* harmony default export */ __webpack_exports__["a"] = (withConstrainedTabbing);
//# sourceMappingURL=index.js.map

/***/ }),

/***/ 94:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* unused harmony export logged */
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "a", function() { return deprecated; });
/* harmony import */ var _wordpress_hooks__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(48);
/* harmony import */ var _wordpress_hooks__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_hooks__WEBPACK_IMPORTED_MODULE_0__);
/**
 * WordPress dependencies
 */

/**
 * Object map tracking messages which have been logged, for use in ensuring a
 * message is only logged once.
 *
 * @type {Object}
 */

var logged = Object.create(null);
/**
 * Logs a message to notify developers about a deprecated feature.
 *
 * @param {string}  feature             Name of the deprecated feature.
 * @param {?Object} options             Personalisation options
 * @param {?string} options.version     Version in which the feature will be removed.
 * @param {?string} options.alternative Feature to use instead
 * @param {?string} options.plugin      Plugin name if it's a plugin feature
 * @param {?string} options.link        Link to documentation
 * @param {?string} options.hint        Additional message to help transition away from the deprecated feature.
 *
 * @example
 * ```js
 * import deprecated from '@wordpress/deprecated';
 *
 * deprecated( 'Eating meat', {
 * 	version: 'the future',
 * 	alternative: 'vegetables',
 * 	plugin: 'the earth',
 * 	hint: 'You may find it beneficial to transition gradually.',
 * } );
 *
 * // Logs: 'Eating meat is deprecated and will be removed from the earth in the future. Please use vegetables instead. Note: You may find it beneficial to transition gradually.'
 * ```
 */

function deprecated(feature) {
  var options = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};
  var version = options.version,
      alternative = options.alternative,
      plugin = options.plugin,
      link = options.link,
      hint = options.hint;
  var pluginMessage = plugin ? " from ".concat(plugin) : '';
  var versionMessage = version ? " and will be removed".concat(pluginMessage, " in version ").concat(version) : '';
  var useInsteadMessage = alternative ? " Please use ".concat(alternative, " instead.") : '';
  var linkMessage = link ? " See: ".concat(link) : '';
  var hintMessage = hint ? " Note: ".concat(hint) : '';
  var message = "".concat(feature, " is deprecated").concat(versionMessage, ".").concat(useInsteadMessage).concat(linkMessage).concat(hintMessage); // Skip if already logged.

  if (message in logged) {
    return;
  }
  /**
   * Fires whenever a deprecated feature is encountered
   *
   * @param {string}  feature             Name of the deprecated feature.
   * @param {?Object} options             Personalisation options
   * @param {?string} options.version     Version in which the feature will be removed.
   * @param {?string} options.alternative Feature to use instead
   * @param {?string} options.plugin      Plugin name if it's a plugin feature
   * @param {?string} options.link        Link to documentation
   * @param {?string} options.hint        Additional message to help transition away from the deprecated feature.
   * @param {?string} message             Message sent to console.warn
   */


  Object(_wordpress_hooks__WEBPACK_IMPORTED_MODULE_0__["doAction"])('deprecated', feature, options, message); // eslint-disable-next-line no-console

  console.warn(message);
  logged[message] = true;
}
//# sourceMappingURL=index.js.map

/***/ }),

/***/ 95:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var _babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(11);
/* harmony import */ var _babel_runtime_helpers_esm_classCallCheck__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(7);
/* harmony import */ var _babel_runtime_helpers_esm_createClass__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(6);
/* harmony import */ var _babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(8);
/* harmony import */ var _babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(4);
/* harmony import */ var _babel_runtime_helpers_esm_assertThisInitialized__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(5);
/* harmony import */ var _babel_runtime_helpers_esm_inherits__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(9);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(2);
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_8__);
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(53);









/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */



/**
 * Input types which are classified as button types, for use in considering
 * whether element is a (focus-normalized) button.
 *
 * @type {string[]}
 */

var INPUT_BUTTON_TYPES = ['button', 'submit'];
/**
 * Returns true if the given element is a button element subject to focus
 * normalization, or false otherwise.
 *
 * @see https://developer.mozilla.org/en-US/docs/Web/HTML/Element/button#Clicking_and_focus
 *
 * @param {Element} element Element to test.
 *
 * @return {boolean} Whether element is a button.
 */

function isFocusNormalizedButton(element) {
  switch (element.nodeName) {
    case 'A':
    case 'BUTTON':
      return true;

    case 'INPUT':
      return Object(lodash__WEBPACK_IMPORTED_MODULE_8__["includes"])(INPUT_BUTTON_TYPES, element.type);
  }

  return false;
}

/* harmony default export */ __webpack_exports__["a"] = (Object(_wordpress_compose__WEBPACK_IMPORTED_MODULE_9__[/* default */ "a"])(function (WrappedComponent) {
  return (
    /*#__PURE__*/
    function (_Component) {
      Object(_babel_runtime_helpers_esm_inherits__WEBPACK_IMPORTED_MODULE_6__[/* default */ "a"])(_class, _Component);

      function _class() {
        var _this;

        Object(_babel_runtime_helpers_esm_classCallCheck__WEBPACK_IMPORTED_MODULE_1__[/* default */ "a"])(this, _class);

        _this = Object(_babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_3__[/* default */ "a"])(this, Object(_babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_4__[/* default */ "a"])(_class).apply(this, arguments));
        _this.bindNode = _this.bindNode.bind(Object(_babel_runtime_helpers_esm_assertThisInitialized__WEBPACK_IMPORTED_MODULE_5__[/* default */ "a"])(_this));
        _this.cancelBlurCheck = _this.cancelBlurCheck.bind(Object(_babel_runtime_helpers_esm_assertThisInitialized__WEBPACK_IMPORTED_MODULE_5__[/* default */ "a"])(_this));
        _this.queueBlurCheck = _this.queueBlurCheck.bind(Object(_babel_runtime_helpers_esm_assertThisInitialized__WEBPACK_IMPORTED_MODULE_5__[/* default */ "a"])(_this));
        _this.normalizeButtonFocus = _this.normalizeButtonFocus.bind(Object(_babel_runtime_helpers_esm_assertThisInitialized__WEBPACK_IMPORTED_MODULE_5__[/* default */ "a"])(_this));
        return _this;
      }

      Object(_babel_runtime_helpers_esm_createClass__WEBPACK_IMPORTED_MODULE_2__[/* default */ "a"])(_class, [{
        key: "componentWillUnmount",
        value: function componentWillUnmount() {
          this.cancelBlurCheck();
        }
      }, {
        key: "bindNode",
        value: function bindNode(node) {
          if (node) {
            this.node = node;
          } else {
            delete this.node;
            this.cancelBlurCheck();
          }
        }
      }, {
        key: "queueBlurCheck",
        value: function queueBlurCheck(event) {
          var _this2 = this;

          // React does not allow using an event reference asynchronously
          // due to recycling behavior, except when explicitly persisted.
          event.persist(); // Skip blur check if clicking button. See `normalizeButtonFocus`.

          if (this.preventBlurCheck) {
            return;
          }

          this.blurCheckTimeout = setTimeout(function () {
            // If document is not focused then focus should remain
            // inside the wrapped component and therefore we cancel
            // this blur event thereby leaving focus in place.
            // https://developer.mozilla.org/en-US/docs/Web/API/Document/hasFocus.
            if (!document.hasFocus()) {
              event.preventDefault();
              return;
            }

            if ('function' === typeof _this2.node.handleFocusOutside) {
              _this2.node.handleFocusOutside(event);
            }
          }, 0);
        }
      }, {
        key: "cancelBlurCheck",
        value: function cancelBlurCheck() {
          clearTimeout(this.blurCheckTimeout);
        }
        /**
         * Handles a mousedown or mouseup event to respectively assign and
         * unassign a flag for preventing blur check on button elements. Some
         * browsers, namely Firefox and Safari, do not emit a focus event on
         * button elements when clicked, while others do. The logic here
         * intends to normalize this as treating click on buttons as focus.
         *
         * @see https://developer.mozilla.org/en-US/docs/Web/HTML/Element/button#Clicking_and_focus
         *
         * @param {MouseEvent} event Event for mousedown or mouseup.
         */

      }, {
        key: "normalizeButtonFocus",
        value: function normalizeButtonFocus(event) {
          var type = event.type,
              target = event.target;
          var isInteractionEnd = Object(lodash__WEBPACK_IMPORTED_MODULE_8__["includes"])(['mouseup', 'touchend'], type);

          if (isInteractionEnd) {
            this.preventBlurCheck = false;
          } else if (isFocusNormalizedButton(target)) {
            this.preventBlurCheck = true;
          }
        }
      }, {
        key: "render",
        value: function render() {
          // Disable reason: See `normalizeButtonFocus` for browser-specific
          // focus event normalization.

          /* eslint-disable jsx-a11y/no-static-element-interactions */
          return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])("div", {
            onFocus: this.cancelBlurCheck,
            onMouseDown: this.normalizeButtonFocus,
            onMouseUp: this.normalizeButtonFocus,
            onTouchStart: this.normalizeButtonFocus,
            onTouchEnd: this.normalizeButtonFocus,
            onBlur: this.queueBlurCheck
          }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])(WrappedComponent, Object(_babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_0__[/* default */ "a"])({
            ref: this.bindNode
          }, this.props)));
          /* eslint-enable jsx-a11y/no-static-element-interactions */
        }
      }]);

      return _class;
    }(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["Component"])
  );
}, 'withFocusOutside'));
//# sourceMappingURL=index.js.map

/***/ }),

/***/ 96:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var _babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(11);
/* harmony import */ var _babel_runtime_helpers_esm_objectWithoutProperties__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(16);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__);




/**
 * WordPress dependencies
 */


function stopPropagation(event) {
  event.stopPropagation();
}

/* harmony default export */ __webpack_exports__["a"] = (Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__["forwardRef"])(function (_ref, ref) {
  var children = _ref.children,
      props = Object(_babel_runtime_helpers_esm_objectWithoutProperties__WEBPACK_IMPORTED_MODULE_1__[/* default */ "a"])(_ref, ["children"]);

  // Disable reason: this stops certain events from propagating outside of the component.
  //   - onMouseDown is disabled as this can cause interactions with other DOM elements

  /* eslint-disable jsx-a11y/no-static-element-interactions */
  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__["createElement"])("div", Object(_babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_0__[/* default */ "a"])({}, props, {
    ref: ref,
    onMouseDown: stopPropagation
  }), children);
  /* eslint-enable jsx-a11y/no-static-element-interactions */
}));
//# sourceMappingURL=index.js.map

/***/ }),

/***/ 97:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";

// UNUSED EXPORTS: Provider

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/esm/toConsumableArray.js + 3 modules
var toConsumableArray = __webpack_require__(17);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/esm/classCallCheck.js
var classCallCheck = __webpack_require__(7);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/esm/createClass.js
var createClass = __webpack_require__(6);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/esm/possibleConstructorReturn.js
var possibleConstructorReturn = __webpack_require__(8);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/esm/getPrototypeOf.js
var getPrototypeOf = __webpack_require__(4);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/esm/inherits.js + 1 modules
var inherits = __webpack_require__(9);

// EXTERNAL MODULE: external {"this":["wp","element"]}
var external_this_wp_element_ = __webpack_require__(0);

// EXTERNAL MODULE: external "lodash"
var external_lodash_ = __webpack_require__(2);

// EXTERNAL MODULE: ./node_modules/@wordpress/compose/build-module/utils/create-higher-order-component/index.js
var create_higher_order_component = __webpack_require__(53);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/esm/assertThisInitialized.js
var assertThisInitialized = __webpack_require__(5);

// CONCATENATED MODULE: ./node_modules/@wordpress/components/build-module/higher-order/with-focus-return/context.js









/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */



var _createContext = Object(external_this_wp_element_["createContext"])({
  focusHistory: []
}),
    Provider = _createContext.Provider,
    Consumer = _createContext.Consumer;

Provider.displayName = 'FocusReturnProvider';
Consumer.displayName = 'FocusReturnConsumer';
/**
 * The maximum history length to capture for the focus stack. When exceeded,
 * items should be shifted from the stack for each consecutive push.
 *
 * @type {number}
 */

var MAX_STACK_LENGTH = 100;

var context_FocusReturnProvider =
/*#__PURE__*/
function (_Component) {
  Object(inherits["a" /* default */])(FocusReturnProvider, _Component);

  function FocusReturnProvider() {
    var _this;

    Object(classCallCheck["a" /* default */])(this, FocusReturnProvider);

    _this = Object(possibleConstructorReturn["a" /* default */])(this, Object(getPrototypeOf["a" /* default */])(FocusReturnProvider).apply(this, arguments));
    _this.onFocus = _this.onFocus.bind(Object(assertThisInitialized["a" /* default */])(_this));
    _this.state = {
      focusHistory: []
    };
    return _this;
  }

  Object(createClass["a" /* default */])(FocusReturnProvider, [{
    key: "onFocus",
    value: function onFocus(event) {
      var focusHistory = this.state.focusHistory; // Push the focused element to the history stack, keeping only unique
      // members but preferring the _last_ occurrence of any duplicates.
      // Lodash's `uniq` behavior favors the first occurrence, so the array
      // is temporarily reversed prior to it being called upon. Uniqueness
      // helps avoid situations where, such as in a constrained tabbing area,
      // the user changes focus enough within a transient element that the
      // stack may otherwise only consist of members pending destruction, at
      // which point focus might have been lost.

      var nextFocusHistory = Object(external_lodash_["uniq"])([].concat(Object(toConsumableArray["a" /* default */])(focusHistory), [event.target]).slice(-1 * MAX_STACK_LENGTH).reverse()).reverse();
      this.setState({
        focusHistory: nextFocusHistory
      });
    }
  }, {
    key: "render",
    value: function render() {
      var _this$props = this.props,
          children = _this$props.children,
          className = _this$props.className;
      return Object(external_this_wp_element_["createElement"])(Provider, {
        value: this.state
      }, Object(external_this_wp_element_["createElement"])("div", {
        onFocus: this.onFocus,
        className: className
      }, children));
    }
  }]);

  return FocusReturnProvider;
}(external_this_wp_element_["Component"]);

/* harmony default export */ var with_focus_return_context = (context_FocusReturnProvider);

//# sourceMappingURL=context.js.map
// CONCATENATED MODULE: ./node_modules/@wordpress/components/build-module/higher-order/with-focus-return/index.js








/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */



/**
 * Internal dependencies
 */


/**
 * Returns true if the given object is component-like. An object is component-
 * like if it is an instance of wp.element.Component, or is a function.
 *
 * @param {*} object Object to test.
 *
 * @return {boolean} Whether object is component-like.
 */

function isComponentLike(object) {
  return object instanceof external_this_wp_element_["Component"] || typeof object === 'function';
}
/**
 * Higher Order Component used to be used to wrap disposable elements like
 * sidebars, modals, dropdowns. When mounting the wrapped component, we track a
 * reference to the current active element so we know where to restore focus
 * when the component is unmounted.
 *
 * @param {(WPComponent|Object)} options The component to be enhanced with
 *                                      focus return behavior, or an object
 *                                      describing the component and the
 *                                      focus return characteristics.
 *
 * @return {WPComponent} Component with the focus restauration behaviour.
 */


function withFocusReturn(options) {
  // Normalize as overloaded form `withFocusReturn( options )( Component )`
  // or as `withFocusReturn( Component )`.
  if (isComponentLike(options)) {
    var WrappedComponent = options;
    return withFocusReturn({})(WrappedComponent);
  }

  var _options$onFocusRetur = options.onFocusReturn,
      onFocusReturn = _options$onFocusRetur === void 0 ? external_lodash_["stubTrue"] : _options$onFocusRetur;
  return function (WrappedComponent) {
    var FocusReturn =
    /*#__PURE__*/
    function (_Component) {
      Object(inherits["a" /* default */])(FocusReturn, _Component);

      function FocusReturn() {
        var _this;

        Object(classCallCheck["a" /* default */])(this, FocusReturn);

        _this = Object(possibleConstructorReturn["a" /* default */])(this, Object(getPrototypeOf["a" /* default */])(FocusReturn).apply(this, arguments));
        _this.ownFocusedElements = new Set();
        _this.activeElementOnMount = document.activeElement;

        _this.setIsFocusedFalse = function () {
          return _this.isFocused = false;
        };

        _this.setIsFocusedTrue = function (event) {
          _this.ownFocusedElements.add(event.target);

          _this.isFocused = true;
        };

        return _this;
      }

      Object(createClass["a" /* default */])(FocusReturn, [{
        key: "componentWillUnmount",
        value: function componentWillUnmount() {
          var activeElementOnMount = this.activeElementOnMount,
              isFocused = this.isFocused,
              ownFocusedElements = this.ownFocusedElements;

          if (!isFocused) {
            return;
          } // Defer to the component's own explicit focus return behavior,
          // if specified. The function should return `false` to prevent
          // the default behavior otherwise occurring here. This allows
          // for support that the `onFocusReturn` decides to allow the
          // default behavior to occur under some conditions.


          if (onFocusReturn() === false) {
            return;
          }

          var stack = [].concat(Object(toConsumableArray["a" /* default */])(external_lodash_["without"].apply(void 0, [this.props.focus.focusHistory].concat(Object(toConsumableArray["a" /* default */])(ownFocusedElements)))), [activeElementOnMount]);
          var candidate;

          while (candidate = stack.pop()) {
            if (document.body.contains(candidate)) {
              candidate.focus();
              return;
            }
          }
        }
      }, {
        key: "render",
        value: function render() {
          return Object(external_this_wp_element_["createElement"])("div", {
            onFocus: this.setIsFocusedTrue,
            onBlur: this.setIsFocusedFalse
          }, Object(external_this_wp_element_["createElement"])(WrappedComponent, this.props.childProps));
        }
      }]);

      return FocusReturn;
    }(external_this_wp_element_["Component"]);

    return function (props) {
      return Object(external_this_wp_element_["createElement"])(Consumer, null, function (context) {
        return Object(external_this_wp_element_["createElement"])(FocusReturn, {
          childProps: props,
          focus: context
        });
      });
    };
  };
}

/* harmony default export */ var with_focus_return = __webpack_exports__["a"] = (Object(create_higher_order_component["a" /* default */])(withFocusReturn, 'withFocusReturn'));

//# sourceMappingURL=index.js.map

/***/ }),

/***/ 98:
/***/ (function(module, exports, __webpack_require__) {

"use strict";
/* WEBPACK VAR INJECTION */(function(process) {

var _interopRequireDefault = __webpack_require__(55);

var _typeof2 = _interopRequireDefault(__webpack_require__(43));

/* eslint-env browser */

/**
 * This is the web browser implementation of `debug()`.
 */
exports.log = log;
exports.formatArgs = formatArgs;
exports.save = save;
exports.load = load;
exports.useColors = useColors;
exports.storage = localstorage();
/**
 * Colors.
 */

exports.colors = ['#0000CC', '#0000FF', '#0033CC', '#0033FF', '#0066CC', '#0066FF', '#0099CC', '#0099FF', '#00CC00', '#00CC33', '#00CC66', '#00CC99', '#00CCCC', '#00CCFF', '#3300CC', '#3300FF', '#3333CC', '#3333FF', '#3366CC', '#3366FF', '#3399CC', '#3399FF', '#33CC00', '#33CC33', '#33CC66', '#33CC99', '#33CCCC', '#33CCFF', '#6600CC', '#6600FF', '#6633CC', '#6633FF', '#66CC00', '#66CC33', '#9900CC', '#9900FF', '#9933CC', '#9933FF', '#99CC00', '#99CC33', '#CC0000', '#CC0033', '#CC0066', '#CC0099', '#CC00CC', '#CC00FF', '#CC3300', '#CC3333', '#CC3366', '#CC3399', '#CC33CC', '#CC33FF', '#CC6600', '#CC6633', '#CC9900', '#CC9933', '#CCCC00', '#CCCC33', '#FF0000', '#FF0033', '#FF0066', '#FF0099', '#FF00CC', '#FF00FF', '#FF3300', '#FF3333', '#FF3366', '#FF3399', '#FF33CC', '#FF33FF', '#FF6600', '#FF6633', '#FF9900', '#FF9933', '#FFCC00', '#FFCC33'];
/**
 * Currently only WebKit-based Web Inspectors, Firefox >= v31,
 * and the Firebug extension (any Firefox version) are known
 * to support "%c" CSS customizations.
 *
 * TODO: add a `localStorage` variable to explicitly enable/disable colors
 */
// eslint-disable-next-line complexity

function useColors() {
  // NB: In an Electron preload script, document will be defined but not fully
  // initialized. Since we know we're in Chrome, we'll just detect this case
  // explicitly
  if (typeof window !== 'undefined' && window.process && (window.process.type === 'renderer' || window.process.__nwjs)) {
    return true;
  } // Internet Explorer and Edge do not support colors.


  if (typeof navigator !== 'undefined' && navigator.userAgent && navigator.userAgent.toLowerCase().match(/(edge|trident)\/(\d+)/)) {
    return false;
  } // Is webkit? http://stackoverflow.com/a/16459606/376773
  // document is undefined in react-native: https://github.com/facebook/react-native/pull/1632


  return typeof document !== 'undefined' && document.documentElement && document.documentElement.style && document.documentElement.style.WebkitAppearance || // Is firebug? http://stackoverflow.com/a/398120/376773
  typeof window !== 'undefined' && window.console && (window.console.firebug || window.console.exception && window.console.table) || // Is firefox >= v31?
  // https://developer.mozilla.org/en-US/docs/Tools/Web_Console#Styling_messages
  typeof navigator !== 'undefined' && navigator.userAgent && navigator.userAgent.toLowerCase().match(/firefox\/(\d+)/) && parseInt(RegExp.$1, 10) >= 31 || // Double check webkit in userAgent just in case we are in a worker
  typeof navigator !== 'undefined' && navigator.userAgent && navigator.userAgent.toLowerCase().match(/applewebkit\/(\d+)/);
}
/**
 * Colorize log arguments if enabled.
 *
 * @api public
 */


function formatArgs(args) {
  args[0] = (this.useColors ? '%c' : '') + this.namespace + (this.useColors ? ' %c' : ' ') + args[0] + (this.useColors ? '%c ' : ' ') + '+' + module.exports.humanize(this.diff);

  if (!this.useColors) {
    return;
  }

  var c = 'color: ' + this.color;
  args.splice(1, 0, c, 'color: inherit'); // The final "%c" is somewhat tricky, because there could be other
  // arguments passed either before or after the %c, so we need to
  // figure out the correct index to insert the CSS into

  var index = 0;
  var lastC = 0;
  args[0].replace(/%[a-zA-Z%]/g, function (match) {
    if (match === '%%') {
      return;
    }

    index++;

    if (match === '%c') {
      // We only are interested in the *last* %c
      // (the user may have provided their own)
      lastC = index;
    }
  });
  args.splice(lastC, 0, c);
}
/**
 * Invokes `console.log()` when available.
 * No-op when `console.log` is not a "function".
 *
 * @api public
 */


function log() {
  var _console;

  // This hackery is required for IE8/9, where
  // the `console.log` function doesn't have 'apply'
  return (typeof console === "undefined" ? "undefined" : (0, _typeof2.default)(console)) === 'object' && console.log && (_console = console).log.apply(_console, arguments);
}
/**
 * Save `namespaces`.
 *
 * @param {String} namespaces
 * @api private
 */


function save(namespaces) {
  try {
    if (namespaces) {
      exports.storage.setItem('debug', namespaces);
    } else {
      exports.storage.removeItem('debug');
    }
  } catch (error) {// Swallow
    // XXX (@Qix-) should we be logging these?
  }
}
/**
 * Load `namespaces`.
 *
 * @return {String} returns the previously persisted debug modes
 * @api private
 */


function load() {
  var r;

  try {
    r = exports.storage.getItem('debug');
  } catch (error) {} // Swallow
  // XXX (@Qix-) should we be logging these?
  // If debug isn't set in LS, and we're in Electron, try to load $DEBUG


  if (!r && typeof process !== 'undefined' && 'env' in process) {
    r = process.env.DEBUG;
  }

  return r;
}
/**
 * Localstorage attempts to return the localstorage.
 *
 * This is necessary because safari throws
 * when a user disables cookies/localstorage
 * and you attempt to access it.
 *
 * @return {LocalStorage}
 * @api private
 */


function localstorage() {
  try {
    // TVMLKit (Apple TV JS Runtime) does not have a window object, just localStorage in the global context
    // The Browser also has localStorage in the global context.
    return localStorage;
  } catch (error) {// Swallow
    // XXX (@Qix-) should we be logging these?
  }
}

module.exports = __webpack_require__(146)(exports);
var formatters = module.exports.formatters;
/**
 * Map %j to `JSON.stringify()`, since no Web Inspectors do that by default.
 */

formatters.j = function (v) {
  try {
    return JSON.stringify(v);
  } catch (error) {
    return '[UnexpectedJSONParseError]: ' + error.message;
  }
};
/* WEBPACK VAR INJECTION */}.call(this, __webpack_require__(72)))

/***/ }),

/***/ 99:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "a", function() { return useMediaQuery; });
/* harmony import */ var _babel_runtime_helpers_esm_slicedToArray__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(21);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__);


/**
 * WordPress dependencies
 */

/**
 * Runs a media query and returns its value when it changes.
 *
 * @param {string} [query] Media Query.
 * @return {boolean} return value of the media query.
 */

function useMediaQuery(query) {
  var _useState = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["useState"])(false),
      _useState2 = Object(_babel_runtime_helpers_esm_slicedToArray__WEBPACK_IMPORTED_MODULE_0__[/* default */ "a"])(_useState, 2),
      match = _useState2[0],
      setMatch = _useState2[1];

  Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["useEffect"])(function () {
    if (!query) {
      return;
    }

    var updateMatch = function updateMatch() {
      return setMatch(window.matchMedia(query).matches);
    };

    updateMatch();
    var list = window.matchMedia(query);
    list.addListener(updateMatch);
    return function () {
      list.removeListener(updateMatch);
    };
  }, [query]);
  return query && match;
}
//# sourceMappingURL=index.js.map

/***/ })

/******/ });
