(function(e, a) { for(var i in a) e[i] = a[i]; }(this, /******/ (function(modules) { // webpackBootstrap
/******/ 	// install a JSONP callback for chunk loading
/******/ 	function webpackJsonpCallback(data) {
/******/ 		var chunkIds = data[0];
/******/ 		var moreModules = data[1];
/******/ 		var executeModules = data[2];
/******/
/******/ 		// add "moreModules" to the modules object,
/******/ 		// then flag all "chunkIds" as loaded and fire callback
/******/ 		var moduleId, chunkId, i = 0, resolves = [];
/******/ 		for(;i < chunkIds.length; i++) {
/******/ 			chunkId = chunkIds[i];
/******/ 			if(installedChunks[chunkId]) {
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
/******/ 		// add entry modules from loaded chunk to deferred list
/******/ 		deferredModules.push.apply(deferredModules, executeModules || []);
/******/
/******/ 		// run deferred modules when all chunks ready
/******/ 		return checkDeferredModules();
/******/ 	};
/******/ 	function checkDeferredModules() {
/******/ 		var result;
/******/ 		for(var i = 0; i < deferredModules.length; i++) {
/******/ 			var deferredModule = deferredModules[i];
/******/ 			var fulfilled = true;
/******/ 			for(var j = 1; j < deferredModule.length; j++) {
/******/ 				var depId = deferredModule[j];
/******/ 				if(installedChunks[depId] !== 0) fulfilled = false;
/******/ 			}
/******/ 			if(fulfilled) {
/******/ 				deferredModules.splice(i--, 1);
/******/ 				result = __webpack_require__(__webpack_require__.s = deferredModule[0]);
/******/ 			}
/******/ 		}
/******/ 		return result;
/******/ 	}
/******/
/******/ 	// The module cache
/******/ 	var installedModules = {};
/******/
/******/ 	// object to store loaded and loading chunks
/******/ 	// undefined = chunk not loaded, null = chunk preloaded/prefetched
/******/ 	// Promise = chunk loading, 0 = chunk loaded
/******/ 	var installedChunks = {
/******/ 		"product-top-rated": 0
/******/ 	};
/******/
/******/ 	var deferredModules = [];
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
/******/ 	var jsonpArray = window["webpackJsonp"] = window["webpackJsonp"] || [];
/******/ 	var oldJsonpFunction = jsonpArray.push.bind(jsonpArray);
/******/ 	jsonpArray.push = webpackJsonpCallback;
/******/ 	jsonpArray = jsonpArray.slice();
/******/ 	for(var i = 0; i < jsonpArray.length; i++) webpackJsonpCallback(jsonpArray[i]);
/******/ 	var parentJsonpFunction = oldJsonpFunction;
/******/
/******/
/******/ 	// add entry module to deferred list
/******/ 	deferredModules.push(["./assets/js/blocks/product-top-rated/index.js","editor","style","vendors"]);
/******/ 	// run deferred modules when ready
/******/ 	return checkDeferredModules();
/******/ })
/************************************************************************/
/******/ ({

/***/ "./assets/js/blocks/product-top-rated/block.js":
/*!*****************************************************!*\
  !*** ./assets/js/blocks/product-top-rated/block.js ***!
  \*****************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/defineProperty */ \"./node_modules/@babel/runtime/helpers/defineProperty.js\");\n/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0__);\n/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/classCallCheck */ \"./node_modules/@babel/runtime/helpers/classCallCheck.js\");\n/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_1__);\n/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @babel/runtime/helpers/createClass */ \"./node_modules/@babel/runtime/helpers/createClass.js\");\n/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_2__);\n/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @babel/runtime/helpers/possibleConstructorReturn */ \"./node_modules/@babel/runtime/helpers/possibleConstructorReturn.js\");\n/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_3__);\n/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @babel/runtime/helpers/getPrototypeOf */ \"./node_modules/@babel/runtime/helpers/getPrototypeOf.js\");\n/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_4__);\n/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @babel/runtime/helpers/inherits */ \"./node_modules/@babel/runtime/helpers/inherits.js\");\n/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_5__);\n/* harmony import */ var _babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @babel/runtime/helpers/assertThisInitialized */ \"./node_modules/@babel/runtime/helpers/assertThisInitialized.js\");\n/* harmony import */ var _babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_6__);\n/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @wordpress/element */ \"@wordpress/element\");\n/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__);\n/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! @wordpress/i18n */ \"@wordpress/i18n\");\n/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_8__);\n/* harmony import */ var _wordpress_url__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! @wordpress/url */ \"@wordpress/url\");\n/* harmony import */ var _wordpress_url__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(_wordpress_url__WEBPACK_IMPORTED_MODULE_9__);\n/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! @wordpress/api-fetch */ \"@wordpress/api-fetch\");\n/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_10___default = /*#__PURE__*/__webpack_require__.n(_wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_10__);\n/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! classnames */ \"./node_modules/classnames/index.js\");\n/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_11___default = /*#__PURE__*/__webpack_require__.n(classnames__WEBPACK_IMPORTED_MODULE_11__);\n/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! lodash */ \"lodash\");\n/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_12___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_12__);\n/* harmony import */ var gridicons__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! gridicons */ \"./node_modules/gridicons/dist/index.js\");\n/* harmony import */ var gridicons__WEBPACK_IMPORTED_MODULE_13___default = /*#__PURE__*/__webpack_require__.n(gridicons__WEBPACK_IMPORTED_MODULE_13__);\n/* harmony import */ var _wordpress_editor__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! @wordpress/editor */ \"@wordpress/editor\");\n/* harmony import */ var _wordpress_editor__WEBPACK_IMPORTED_MODULE_14___default = /*#__PURE__*/__webpack_require__.n(_wordpress_editor__WEBPACK_IMPORTED_MODULE_14__);\n/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(/*! @wordpress/components */ \"@wordpress/components\");\n/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_15___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_15__);\n/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__(/*! prop-types */ \"./node_modules/prop-types/index.js\");\n/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_16___default = /*#__PURE__*/__webpack_require__.n(prop_types__WEBPACK_IMPORTED_MODULE_16__);\n/* harmony import */ var _utils_get_query__WEBPACK_IMPORTED_MODULE_17__ = __webpack_require__(/*! ../../utils/get-query */ \"./assets/js/utils/get-query.js\");\n/* harmony import */ var _components_grid_content_control__WEBPACK_IMPORTED_MODULE_18__ = __webpack_require__(/*! ../../components/grid-content-control */ \"./assets/js/components/grid-content-control/index.js\");\n/* harmony import */ var _components_grid_layout_control__WEBPACK_IMPORTED_MODULE_19__ = __webpack_require__(/*! ../../components/grid-layout-control */ \"./assets/js/components/grid-layout-control/index.js\");\n/* harmony import */ var _components_product_category_control__WEBPACK_IMPORTED_MODULE_20__ = __webpack_require__(/*! ../../components/product-category-control */ \"./assets/js/components/product-category-control/index.js\");\n/* harmony import */ var _components_product_preview__WEBPACK_IMPORTED_MODULE_21__ = __webpack_require__(/*! ../../components/product-preview */ \"./assets/js/components/product-preview/index.js\");\n\n\n\n\n\n\n\n\n\n/**\n * External dependencies\n */\n\n\n\n\n\n\n\n\n\n\n/**\n * Internal dependencies\n */\n\n\n\n\n\n\n/**\n * Component to handle edit mode of \"Top Rated Products\".\n */\n\nvar ProductTopRatedBlock =\n/*#__PURE__*/\nfunction (_Component) {\n  _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_5___default()(ProductTopRatedBlock, _Component);\n\n  function ProductTopRatedBlock() {\n    var _this;\n\n    _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_1___default()(this, ProductTopRatedBlock);\n\n    _this = _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_3___default()(this, _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_4___default()(ProductTopRatedBlock).apply(this, arguments));\n    _this.state = {\n      products: [],\n      loaded: false\n    };\n    _this.debouncedGetProducts = Object(lodash__WEBPACK_IMPORTED_MODULE_12__[\"debounce\"])(_this.getProducts.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_6___default()(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_6___default()(_this))), 200);\n    return _this;\n  }\n\n  _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_2___default()(ProductTopRatedBlock, [{\n    key: \"componentDidMount\",\n    value: function componentDidMount() {\n      if (this.props.attributes.categories) {\n        this.getProducts();\n      }\n    }\n  }, {\n    key: \"componentDidUpdate\",\n    value: function componentDidUpdate(prevProps) {\n      var _this2 = this;\n\n      var hasChange = ['rows', 'columns', 'categories', 'catOperator'].reduce(function (acc, key) {\n        return acc || prevProps.attributes[key] !== _this2.props.attributes[key];\n      }, false);\n\n      if (hasChange) {\n        this.debouncedGetProducts();\n      }\n    }\n  }, {\n    key: \"getProducts\",\n    value: function getProducts() {\n      var _this3 = this;\n\n      _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_10___default()({\n        path: Object(_wordpress_url__WEBPACK_IMPORTED_MODULE_9__[\"addQueryArgs\"])('/wc-pb/v3/products', Object(_utils_get_query__WEBPACK_IMPORTED_MODULE_17__[\"default\"])(this.props.attributes, this.props.name))\n      }).then(function (products) {\n        _this3.setState({\n          products: products,\n          loaded: true\n        });\n      }).catch(function () {\n        _this3.setState({\n          products: [],\n          loaded: true\n        });\n      });\n    }\n  }, {\n    key: \"getInspectorControls\",\n    value: function getInspectorControls() {\n      var _this$props = this.props,\n          attributes = _this$props.attributes,\n          setAttributes = _this$props.setAttributes;\n      var categories = attributes.categories,\n          catOperator = attributes.catOperator,\n          columns = attributes.columns,\n          contentVisibility = attributes.contentVisibility,\n          rows = attributes.rows;\n      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__[\"createElement\"])(_wordpress_editor__WEBPACK_IMPORTED_MODULE_14__[\"InspectorControls\"], {\n        key: \"inspector\"\n      }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__[\"createElement\"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_15__[\"PanelBody\"], {\n        title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_8__[\"__\"])('Layout', 'woocommerce'),\n        initialOpen: true\n      }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__[\"createElement\"])(_components_grid_layout_control__WEBPACK_IMPORTED_MODULE_19__[\"default\"], {\n        columns: columns,\n        rows: rows,\n        setAttributes: setAttributes\n      })), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__[\"createElement\"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_15__[\"PanelBody\"], {\n        title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_8__[\"__\"])('Content', 'woocommerce'),\n        initialOpen: true\n      }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__[\"createElement\"])(_components_grid_content_control__WEBPACK_IMPORTED_MODULE_18__[\"default\"], {\n        settings: contentVisibility,\n        onChange: function onChange(value) {\n          return setAttributes({\n            contentVisibility: value\n          });\n        }\n      })), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__[\"createElement\"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_15__[\"PanelBody\"], {\n        title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_8__[\"__\"])('Filter by Product Category', 'woocommerce'),\n        initialOpen: false\n      }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__[\"createElement\"])(_components_product_category_control__WEBPACK_IMPORTED_MODULE_20__[\"default\"], {\n        selected: categories,\n        onChange: function onChange() {\n          var value = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : [];\n          var ids = value.map(function (_ref) {\n            var id = _ref.id;\n            return id;\n          });\n          setAttributes({\n            categories: ids\n          });\n        },\n        operator: catOperator,\n        onOperatorChange: function onOperatorChange() {\n          var value = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : 'any';\n          return setAttributes({\n            catOperator: value\n          });\n        }\n      })));\n    }\n  }, {\n    key: \"render\",\n    value: function render() {\n      var _classnames;\n\n      var _this$props$attribute = this.props.attributes,\n          columns = _this$props$attribute.columns,\n          contentVisibility = _this$props$attribute.contentVisibility;\n      var _this$state = this.state,\n          loaded = _this$state.loaded,\n          _this$state$products = _this$state.products,\n          products = _this$state$products === void 0 ? [] : _this$state$products;\n      var classes = classnames__WEBPACK_IMPORTED_MODULE_11___default()((_classnames = {\n        'wc-block-products-grid': true,\n        'wc-block-top-rated-products': true\n      }, _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0___default()(_classnames, \"cols-\".concat(columns), columns), _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0___default()(_classnames, 'is-loading', !loaded), _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0___default()(_classnames, 'is-not-found', loaded && !products.length), _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0___default()(_classnames, 'is-hidden-title', !contentVisibility.title), _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0___default()(_classnames, 'is-hidden-price', !contentVisibility.price), _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0___default()(_classnames, 'is-hidden-button', !contentVisibility.button), _classnames));\n      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__[\"createElement\"])(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__[\"Fragment\"], null, this.getInspectorControls(), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__[\"createElement\"])(\"div\", {\n        className: classes\n      }, products.length ? products.map(function (product) {\n        return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__[\"createElement\"])(_components_product_preview__WEBPACK_IMPORTED_MODULE_21__[\"default\"], {\n          product: product,\n          key: product.id\n        });\n      }) : Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__[\"createElement\"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_15__[\"Placeholder\"], {\n        icon: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__[\"createElement\"])(gridicons__WEBPACK_IMPORTED_MODULE_13___default.a, {\n          icon: \"trophy\"\n        }),\n        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_8__[\"__\"])('Top Rated Products', 'woocommerce')\n      }, !loaded ? Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__[\"createElement\"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_15__[\"Spinner\"], null) : Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_8__[\"__\"])('No products found.', 'woocommerce'))));\n    }\n  }]);\n\n  return ProductTopRatedBlock;\n}(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__[\"Component\"]);\n\nProductTopRatedBlock.propTypes = {\n  /**\n   * The attributes for this block\n   */\n  attributes: prop_types__WEBPACK_IMPORTED_MODULE_16___default.a.object.isRequired,\n\n  /**\n   * The register block name.\n   */\n  name: prop_types__WEBPACK_IMPORTED_MODULE_16___default.a.string.isRequired,\n\n  /**\n   * A callback to update attributes\n   */\n  setAttributes: prop_types__WEBPACK_IMPORTED_MODULE_16___default.a.func.isRequired\n};\n/* harmony default export */ __webpack_exports__[\"default\"] = (ProductTopRatedBlock);\n\n//# sourceURL=webpack:///./assets/js/blocks/product-top-rated/block.js?");

/***/ }),

/***/ "./assets/js/blocks/product-top-rated/index.js":
/*!*****************************************************!*\
  !*** ./assets/js/blocks/product-top-rated/index.js ***!
  \*****************************************************/
/*! no exports provided */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony import */ var _babel_runtime_helpers_objectSpread__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/objectSpread */ \"./node_modules/@babel/runtime/helpers/objectSpread.js\");\n/* harmony import */ var _babel_runtime_helpers_objectSpread__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_objectSpread__WEBPACK_IMPORTED_MODULE_0__);\n/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/element */ \"@wordpress/element\");\n/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__);\n/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/i18n */ \"@wordpress/i18n\");\n/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__);\n/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! classnames */ \"./node_modules/classnames/index.js\");\n/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(classnames__WEBPACK_IMPORTED_MODULE_3__);\n/* harmony import */ var gridicons__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! gridicons */ \"./node_modules/gridicons/dist/index.js\");\n/* harmony import */ var gridicons__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(gridicons__WEBPACK_IMPORTED_MODULE_4__);\n/* harmony import */ var _wordpress_blocks__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @wordpress/blocks */ \"@wordpress/blocks\");\n/* harmony import */ var _wordpress_blocks__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_wordpress_blocks__WEBPACK_IMPORTED_MODULE_5__);\n/* harmony import */ var _block__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./block */ \"./assets/js/blocks/product-top-rated/block.js\");\n/* harmony import */ var _utils_get_shortcode__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ../../utils/get-shortcode */ \"./assets/js/utils/get-shortcode.js\");\n/* harmony import */ var _utils_shared_attributes__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ../../utils/shared-attributes */ \"./assets/js/utils/shared-attributes.js\");\n\n\n\n/**\n * External dependencies\n */\n\n\n\n\n\n/**\n * Internal dependencies\n */\n\n\n\n\nObject(_wordpress_blocks__WEBPACK_IMPORTED_MODULE_5__[\"registerBlockType\"])('woocommerce/product-top-rated', {\n  title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__[\"__\"])('Top Rated Products', 'woocommerce'),\n  icon: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__[\"createElement\"])(gridicons__WEBPACK_IMPORTED_MODULE_4___default.a, {\n    icon: \"trophy\"\n  }),\n  category: 'woocommerce',\n  keywords: [Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__[\"__\"])('WooCommerce', 'woocommerce')],\n  description: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__[\"__\"])('Display a grid of your top rated products.', 'woocommerce'),\n  supports: {\n    align: ['wide', 'full']\n  },\n  attributes: _babel_runtime_helpers_objectSpread__WEBPACK_IMPORTED_MODULE_0___default()({}, _utils_shared_attributes__WEBPACK_IMPORTED_MODULE_8__[\"default\"]),\n\n  /**\n   * Renders and manages the block.\n   */\n  edit: function edit(props) {\n    return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__[\"createElement\"])(_block__WEBPACK_IMPORTED_MODULE_6__[\"default\"], props);\n  },\n\n  /**\n   * Save the block content in the post content. Block content is saved as a products shortcode.\n   *\n   * @return string\n   */\n  save: function save(props) {\n    var _props$attributes = props.attributes,\n        align = _props$attributes.align,\n        contentVisibility = _props$attributes.contentVisibility;\n    /* eslint-disable-line react/prop-types */\n\n    var classes = classnames__WEBPACK_IMPORTED_MODULE_3___default()(align ? \"align\".concat(align) : '', {\n      'is-hidden-title': !contentVisibility.title,\n      'is-hidden-price': !contentVisibility.price,\n      'is-hidden-button': !contentVisibility.button\n    });\n    return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__[\"createElement\"])(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__[\"RawHTML\"], {\n      className: classes\n    }, Object(_utils_get_shortcode__WEBPACK_IMPORTED_MODULE_7__[\"default\"])(props, 'woocommerce/product-top-rated'));\n  }\n});\n\n//# sourceURL=webpack:///./assets/js/blocks/product-top-rated/index.js?");

/***/ }),

/***/ "./assets/js/components/grid-content-control/index.js":
/*!************************************************************!*\
  !*** ./assets/js/components/grid-content-control/index.js ***!
  \************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony import */ var _babel_runtime_helpers_objectSpread__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/objectSpread */ \"./node_modules/@babel/runtime/helpers/objectSpread.js\");\n/* harmony import */ var _babel_runtime_helpers_objectSpread__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_objectSpread__WEBPACK_IMPORTED_MODULE_0__);\n/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/element */ \"@wordpress/element\");\n/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__);\n/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/i18n */ \"@wordpress/i18n\");\n/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__);\n/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! prop-types */ \"./node_modules/prop-types/index.js\");\n/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(prop_types__WEBPACK_IMPORTED_MODULE_3__);\n/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @wordpress/components */ \"@wordpress/components\");\n/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__);\n\n\n\n/**\n * External dependencies\n */\n\n\n\n\n/**\n * A combination of range controls for product grid layout settings.\n */\n\nvar GridContentControl = function GridContentControl(_ref) {\n  var _onChange = _ref.onChange,\n      settings = _ref.settings;\n  var button = settings.button,\n      price = settings.price,\n      title = settings.title;\n  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__[\"createElement\"])(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__[\"Fragment\"], null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__[\"createElement\"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__[\"ToggleControl\"], {\n    label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__[\"__\"])('Product title', 'woocommerce'),\n    help: title ? Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__[\"__\"])('Product title is visible.', 'woocommerce') : Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__[\"__\"])('Product title is hidden.', 'woocommerce'),\n    checked: title,\n    onChange: function onChange() {\n      return _onChange(_babel_runtime_helpers_objectSpread__WEBPACK_IMPORTED_MODULE_0___default()({}, settings, {\n        title: !title\n      }));\n    }\n  }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__[\"createElement\"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__[\"ToggleControl\"], {\n    label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__[\"__\"])('Product price', 'woocommerce'),\n    help: price ? Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__[\"__\"])('Product price is visible.', 'woocommerce') : Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__[\"__\"])('Product price is hidden.', 'woocommerce'),\n    checked: price,\n    onChange: function onChange() {\n      return _onChange(_babel_runtime_helpers_objectSpread__WEBPACK_IMPORTED_MODULE_0___default()({}, settings, {\n        price: !price\n      }));\n    }\n  }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__[\"createElement\"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__[\"ToggleControl\"], {\n    label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__[\"__\"])('Add to Cart button', 'woocommerce'),\n    help: button ? Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__[\"__\"])('Add to Cart button is visible.', 'woocommerce') : Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__[\"__\"])('Add to Cart button is hidden.', 'woocommerce'),\n    checked: button,\n    onChange: function onChange() {\n      return _onChange(_babel_runtime_helpers_objectSpread__WEBPACK_IMPORTED_MODULE_0___default()({}, settings, {\n        button: !button\n      }));\n    }\n  }));\n};\n\nGridContentControl.propTypes = {\n  /**\n   * The current title visibility.\n   */\n  settings: prop_types__WEBPACK_IMPORTED_MODULE_3___default.a.shape({\n    button: prop_types__WEBPACK_IMPORTED_MODULE_3___default.a.bool.isRequired,\n    price: prop_types__WEBPACK_IMPORTED_MODULE_3___default.a.bool.isRequired,\n    title: prop_types__WEBPACK_IMPORTED_MODULE_3___default.a.bool.isRequired\n  }).isRequired,\n\n  /**\n   * Callback to update the layout settings.\n   */\n  onChange: prop_types__WEBPACK_IMPORTED_MODULE_3___default.a.func.isRequired\n};\n/* harmony default export */ __webpack_exports__[\"default\"] = (GridContentControl);\n\n//# sourceURL=webpack:///./assets/js/components/grid-content-control/index.js?");

/***/ }),

/***/ "./assets/js/components/grid-layout-control/index.js":
/*!***********************************************************!*\
  !*** ./assets/js/components/grid-layout-control/index.js ***!
  \***********************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ \"@wordpress/element\");\n/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);\n/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/i18n */ \"@wordpress/i18n\");\n/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__);\n/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! lodash */ \"lodash\");\n/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_2__);\n/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! prop-types */ \"./node_modules/prop-types/index.js\");\n/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(prop_types__WEBPACK_IMPORTED_MODULE_3__);\n/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @wordpress/components */ \"@wordpress/components\");\n/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__);\n\n\n/**\n * External dependencies\n */\n\n\n\n\n\n/**\n * A combination of range controls for product grid layout settings.\n */\n\nvar GridLayoutControl = function GridLayoutControl(_ref) {\n  var columns = _ref.columns,\n      rows = _ref.rows,\n      setAttributes = _ref.setAttributes;\n  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__[\"createElement\"])(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__[\"Fragment\"], null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__[\"createElement\"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__[\"RangeControl\"], {\n    label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__[\"__\"])('Columns', 'woocommerce'),\n    value: columns,\n    onChange: function onChange(value) {\n      var newValue = Object(lodash__WEBPACK_IMPORTED_MODULE_2__[\"clamp\"])(value, wc_product_block_data.min_columns, wc_product_block_data.max_columns);\n      setAttributes({\n        columns: Object(lodash__WEBPACK_IMPORTED_MODULE_2__[\"isNaN\"])(newValue) ? '' : newValue\n      });\n    },\n    min: wc_product_block_data.min_columns,\n    max: wc_product_block_data.max_columns\n  }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__[\"createElement\"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__[\"RangeControl\"], {\n    label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__[\"__\"])('Rows', 'woocommerce'),\n    value: rows,\n    onChange: function onChange(value) {\n      var newValue = Object(lodash__WEBPACK_IMPORTED_MODULE_2__[\"clamp\"])(value, wc_product_block_data.min_rows, wc_product_block_data.max_rows);\n      setAttributes({\n        rows: Object(lodash__WEBPACK_IMPORTED_MODULE_2__[\"isNaN\"])(newValue) ? '' : newValue\n      });\n    },\n    min: wc_product_block_data.min_rows,\n    max: wc_product_block_data.max_rows\n  }));\n};\n\nGridLayoutControl.propTypes = {\n  /**\n   * The current columns count.\n   */\n  columns: prop_types__WEBPACK_IMPORTED_MODULE_3___default.a.oneOfType([prop_types__WEBPACK_IMPORTED_MODULE_3___default.a.number, prop_types__WEBPACK_IMPORTED_MODULE_3___default.a.string]).isRequired,\n\n  /**\n   * The current rows count.\n   */\n  rows: prop_types__WEBPACK_IMPORTED_MODULE_3___default.a.oneOfType([prop_types__WEBPACK_IMPORTED_MODULE_3___default.a.number, prop_types__WEBPACK_IMPORTED_MODULE_3___default.a.string]).isRequired,\n\n  /**\n   * Callback to update the layout settings.\n   */\n  setAttributes: prop_types__WEBPACK_IMPORTED_MODULE_3___default.a.func.isRequired\n};\n/* harmony default export */ __webpack_exports__[\"default\"] = (GridLayoutControl);\n\n//# sourceURL=webpack:///./assets/js/components/grid-layout-control/index.js?");

/***/ }),

/***/ "./assets/js/components/product-category-control/index.js":
/*!****************************************************************!*\
  !*** ./assets/js/components/product-category-control/index.js ***!
  \****************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony import */ var _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/extends */ \"./node_modules/@babel/runtime/helpers/extends.js\");\n/* harmony import */ var _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0__);\n/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/classCallCheck */ \"./node_modules/@babel/runtime/helpers/classCallCheck.js\");\n/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_1__);\n/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @babel/runtime/helpers/createClass */ \"./node_modules/@babel/runtime/helpers/createClass.js\");\n/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_2__);\n/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @babel/runtime/helpers/possibleConstructorReturn */ \"./node_modules/@babel/runtime/helpers/possibleConstructorReturn.js\");\n/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_3__);\n/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @babel/runtime/helpers/getPrototypeOf */ \"./node_modules/@babel/runtime/helpers/getPrototypeOf.js\");\n/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_4__);\n/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @babel/runtime/helpers/inherits */ \"./node_modules/@babel/runtime/helpers/inherits.js\");\n/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_5__);\n/* harmony import */ var _babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @babel/runtime/helpers/assertThisInitialized */ \"./node_modules/@babel/runtime/helpers/assertThisInitialized.js\");\n/* harmony import */ var _babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_6__);\n/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @wordpress/element */ \"@wordpress/element\");\n/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__);\n/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! @wordpress/i18n */ \"@wordpress/i18n\");\n/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_8__);\n/* harmony import */ var _wordpress_url__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! @wordpress/url */ \"@wordpress/url\");\n/* harmony import */ var _wordpress_url__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(_wordpress_url__WEBPACK_IMPORTED_MODULE_9__);\n/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! @wordpress/api-fetch */ \"@wordpress/api-fetch\");\n/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_10___default = /*#__PURE__*/__webpack_require__.n(_wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_10__);\n/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! lodash */ \"lodash\");\n/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_11___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_11__);\n/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! prop-types */ \"./node_modules/prop-types/index.js\");\n/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_12___default = /*#__PURE__*/__webpack_require__.n(prop_types__WEBPACK_IMPORTED_MODULE_12__);\n/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! @woocommerce/components */ \"./node_modules/@woocommerce/components/build-module/index.js\");\n/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! @wordpress/components */ \"@wordpress/components\");\n/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_14___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_14__);\n/* harmony import */ var _style_scss__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(/*! ./style.scss */ \"./assets/js/components/product-category-control/style.scss\");\n/* harmony import */ var _style_scss__WEBPACK_IMPORTED_MODULE_15___default = /*#__PURE__*/__webpack_require__.n(_style_scss__WEBPACK_IMPORTED_MODULE_15__);\n\n\n\n\n\n\n\n\n\n/**\n * External dependencies\n */\n\n\n\n\n\n\n\n\n/**\n * Internal dependencies\n */\n\n\n\nvar ProductCategoryControl =\n/*#__PURE__*/\nfunction (_Component) {\n  _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_5___default()(ProductCategoryControl, _Component);\n\n  function ProductCategoryControl() {\n    var _this;\n\n    _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_1___default()(this, ProductCategoryControl);\n\n    _this = _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_3___default()(this, _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_4___default()(ProductCategoryControl).apply(this, arguments));\n    _this.state = {\n      list: [],\n      loading: true\n    };\n    _this.renderItem = _this.renderItem.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_6___default()(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_6___default()(_this)));\n    return _this;\n  }\n\n  _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_2___default()(ProductCategoryControl, [{\n    key: \"componentDidMount\",\n    value: function componentDidMount() {\n      var _this2 = this;\n\n      _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_10___default()({\n        path: Object(_wordpress_url__WEBPACK_IMPORTED_MODULE_9__[\"addQueryArgs\"])('/wc-pb/v3/products/categories', {\n          per_page: -1\n        })\n      }).then(function (list) {\n        _this2.setState({\n          list: list,\n          loading: false\n        });\n      }).catch(function () {\n        _this2.setState({\n          list: [],\n          loading: false\n        });\n      });\n    }\n  }, {\n    key: \"renderItem\",\n    value: function renderItem(args) {\n      var item = args.item,\n          search = args.search,\n          _args$depth = args.depth,\n          depth = _args$depth === void 0 ? 0 : _args$depth;\n      var classes = ['woocommerce-product-categories__item'];\n\n      if (search.length) {\n        classes.push('is-searching');\n      }\n\n      if (depth === 0 && item.parent !== 0) {\n        classes.push('is-skip-level');\n      }\n\n      var accessibleName = !item.breadcrumbs.length ? item.name : \"\".concat(item.breadcrumbs.join(', '), \", \").concat(item.name);\n      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__[\"createElement\"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_13__[\"SearchListItem\"], _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0___default()({\n        className: classes.join(' ')\n      }, args, {\n        showCount: true,\n        \"aria-label\": Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_8__[\"sprintf\"])(Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_8__[\"_n\"])('%s, has %d product', '%s, has %d products', item.count, 'woocommerce'), accessibleName, item.count)\n      }));\n    }\n  }, {\n    key: \"render\",\n    value: function render() {\n      var _this$state = this.state,\n          list = _this$state.list,\n          loading = _this$state.loading;\n      var _this$props = this.props,\n          onChange = _this$props.onChange,\n          onOperatorChange = _this$props.onOperatorChange,\n          operator = _this$props.operator,\n          selected = _this$props.selected;\n      var messages = {\n        clear: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_8__[\"__\"])('Clear all product categories', 'woocommerce'),\n        list: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_8__[\"__\"])('Product Categories', 'woocommerce'),\n        noItems: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_8__[\"__\"])(\"Your store doesn't have any product categories.\", 'woocommerce'),\n        search: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_8__[\"__\"])('Search for product categories', 'woocommerce'),\n        selected: function selected(n) {\n          return Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_8__[\"sprintf\"])(Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_8__[\"_n\"])('%d category selected', '%d categories selected', n, 'woocommerce'), n);\n        },\n        updated: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_8__[\"__\"])('Category search results updated.', 'woocommerce')\n      };\n      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__[\"createElement\"])(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__[\"Fragment\"], null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__[\"createElement\"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_13__[\"SearchListControl\"], {\n        className: \"woocommerce-product-categories\",\n        list: list,\n        isLoading: loading,\n        selected: selected.map(function (id) {\n          return Object(lodash__WEBPACK_IMPORTED_MODULE_11__[\"find\"])(list, {\n            id: id\n          });\n        }).filter(Boolean),\n        onChange: onChange,\n        renderItem: this.renderItem,\n        messages: messages,\n        isHierarchical: true\n      }), !!onOperatorChange && Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__[\"createElement\"])(\"div\", {\n        className: selected.length < 2 ? 'screen-reader-text' : ''\n      }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__[\"createElement\"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_14__[\"SelectControl\"], {\n        className: \"woocommerce-product-categories__operator\",\n        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_8__[\"__\"])('Display products matching', 'woocommerce'),\n        help: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_8__[\"__\"])('Pick at least two categories to use this setting.', 'woocommerce'),\n        value: operator,\n        onChange: onOperatorChange,\n        options: [{\n          label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_8__[\"__\"])('Any selected categories', 'woocommerce'),\n          value: 'any'\n        }, {\n          label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_8__[\"__\"])('All selected categories', 'woocommerce'),\n          value: 'all'\n        }]\n      })));\n    }\n  }]);\n\n  return ProductCategoryControl;\n}(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__[\"Component\"]);\n\nProductCategoryControl.propTypes = {\n  /**\n   * Callback to update the selected product categories.\n   */\n  onChange: prop_types__WEBPACK_IMPORTED_MODULE_12___default.a.func.isRequired,\n\n  /**\n   * Callback to update the category operator. If not passed in, setting is not used.\n   */\n  onOperatorChange: prop_types__WEBPACK_IMPORTED_MODULE_12___default.a.func,\n\n  /**\n   * Setting for whether products should match all or any selected categories.\n   */\n  operator: prop_types__WEBPACK_IMPORTED_MODULE_12___default.a.oneOf(['all', 'any']),\n\n  /**\n   * The list of currently selected category IDs.\n   */\n  selected: prop_types__WEBPACK_IMPORTED_MODULE_12___default.a.array.isRequired\n};\nProductCategoryControl.defaultProps = {\n  operator: 'any'\n};\n/* harmony default export */ __webpack_exports__[\"default\"] = (ProductCategoryControl);\n\n//# sourceURL=webpack:///./assets/js/components/product-category-control/index.js?");

/***/ }),

/***/ "./assets/js/components/product-preview/index.js":
/*!*******************************************************!*\
  !*** ./assets/js/components/product-preview/index.js ***!
  \*******************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ \"@wordpress/element\");\n/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);\n/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/i18n */ \"@wordpress/i18n\");\n/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__);\n/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! prop-types */ \"./node_modules/prop-types/index.js\");\n/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(prop_types__WEBPACK_IMPORTED_MODULE_2__);\n/* harmony import */ var _style_scss__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./style.scss */ \"./assets/js/components/product-preview/style.scss\");\n/* harmony import */ var _style_scss__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_style_scss__WEBPACK_IMPORTED_MODULE_3__);\n\n\n/**\n * External dependencies\n */\n\n\n/**\n * Internal dependencies\n */\n\n\n/**\n * Display a preview for a given product.\n */\n\nvar ProductPreview = function ProductPreview(_ref) {\n  var product = _ref.product;\n  var _wc_product_block_dat = wc_product_block_data,\n      placeholderImgSrc = _wc_product_block_dat.placeholderImgSrc;\n  /* eslint-disable-line camelcase */\n\n  var image = null;\n\n  if (product.images.length) {\n    image = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__[\"createElement\"])(\"img\", {\n      src: product.images[0].src,\n      alt: \"\"\n    });\n  } else {\n    image = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__[\"createElement\"])(\"img\", {\n      src: placeholderImgSrc,\n      alt: \"\"\n    });\n  }\n\n  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__[\"createElement\"])(\"div\", {\n    className: \"wc-product-preview\"\n  }, image, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__[\"createElement\"])(\"div\", {\n    className: \"wc-product-preview__title\"\n  }, product.name), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__[\"createElement\"])(\"div\", {\n    className: \"wc-product-preview__price\",\n    dangerouslySetInnerHTML: {\n      __html: product.price_html\n    }\n  }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__[\"createElement\"])(\"span\", {\n    className: \"wp-block-button\"\n  }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__[\"createElement\"])(\"span\", {\n    className: \"wc-product-preview__add-to-cart wp-block-button__link\"\n  }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__[\"__\"])('Add to cart', 'woocommerce'))));\n};\n\nProductPreview.propTypes = {\n  /**\n   * The product object as returned from the API.\n   */\n  product: prop_types__WEBPACK_IMPORTED_MODULE_2___default.a.shape({\n    id: prop_types__WEBPACK_IMPORTED_MODULE_2___default.a.number,\n    images: prop_types__WEBPACK_IMPORTED_MODULE_2___default.a.array,\n    name: prop_types__WEBPACK_IMPORTED_MODULE_2___default.a.string,\n    price_html: prop_types__WEBPACK_IMPORTED_MODULE_2___default.a.string\n  }).isRequired\n};\n/* harmony default export */ __webpack_exports__[\"default\"] = (ProductPreview);\n\n//# sourceURL=webpack:///./assets/js/components/product-preview/index.js?");

/***/ }),

/***/ "./assets/js/utils/get-query.js":
/*!**************************************!*\
  !*** ./assets/js/utils/get-query.js ***!
  \**************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, \"default\", function() { return getQuery; });\nfunction getQuery(blockAttributes, name) {\n  var attributes = blockAttributes.attributes,\n      attrOperator = blockAttributes.attrOperator,\n      categories = blockAttributes.categories,\n      catOperator = blockAttributes.catOperator,\n      orderby = blockAttributes.orderby,\n      products = blockAttributes.products;\n  var columns = blockAttributes.columns || wc_product_block_data.default_columns;\n  var rows = blockAttributes.rows || wc_product_block_data.default_rows;\n  var query = {\n    status: 'publish',\n    per_page: rows * columns,\n    catalog_visibility: 'visible'\n  };\n\n  if (categories && categories.length) {\n    query.category = categories.join(',');\n\n    if (catOperator && 'all' === catOperator) {\n      query.cat_operator = 'AND';\n    }\n  }\n\n  if (orderby) {\n    if ('price_desc' === orderby) {\n      query.orderby = 'price';\n      query.order = 'desc';\n    } else if ('price_asc' === orderby) {\n      query.orderby = 'price';\n      query.order = 'asc';\n    } else if ('title' === orderby) {\n      query.orderby = 'title';\n      query.order = 'asc';\n    } else if ('menu_order' === orderby) {\n      query.orderby = 'menu_order';\n      query.order = 'asc';\n    } else {\n      query.orderby = orderby;\n    }\n  }\n\n  if (attributes && attributes.length > 0) {\n    query.attribute_term = attributes.map(function (_ref) {\n      var id = _ref.id;\n      return id;\n    }).join(',');\n    query.attribute = attributes[0].attr_slug;\n\n    if (attrOperator) {\n      query.attr_operator = 'all' === attrOperator ? 'AND' : 'IN';\n    }\n  } // Toggle query parameters depending on block type.\n\n\n  switch (name) {\n    case 'woocommerce/product-best-sellers':\n      query.orderby = 'popularity';\n      break;\n\n    case 'woocommerce/product-top-rated':\n      query.orderby = 'rating';\n      break;\n\n    case 'woocommerce/product-on-sale':\n      query.on_sale = 1;\n      break;\n\n    case 'woocommerce/product-new':\n      query.orderby = 'date';\n      break;\n\n    case 'woocommerce/handpicked-products':\n      query.include = products;\n      query.per_page = products.length;\n      break;\n  }\n\n  return query;\n}\n\n//# sourceURL=webpack:///./assets/js/utils/get-query.js?");

/***/ }),

/***/ "./assets/js/utils/get-shortcode.js":
/*!******************************************!*\
  !*** ./assets/js/utils/get-shortcode.js ***!
  \******************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, \"default\", function() { return getShortcode; });\n/* harmony import */ var _babel_runtime_helpers_slicedToArray__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/slicedToArray */ \"./node_modules/@babel/runtime/helpers/slicedToArray.js\");\n/* harmony import */ var _babel_runtime_helpers_slicedToArray__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_slicedToArray__WEBPACK_IMPORTED_MODULE_0__);\n\nfunction getShortcode(props, name) {\n  var blockAttributes = props.attributes;\n  var attributes = blockAttributes.attributes,\n      attrOperator = blockAttributes.attrOperator,\n      categories = blockAttributes.categories,\n      catOperator = blockAttributes.catOperator,\n      orderby = blockAttributes.orderby,\n      products = blockAttributes.products;\n  var columns = blockAttributes.columns || wc_product_block_data.default_columns;\n  var rows = blockAttributes.rows || wc_product_block_data.default_rows;\n  var shortcodeAtts = new Map();\n  shortcodeAtts.set('limit', rows * columns);\n  shortcodeAtts.set('columns', columns);\n\n  if (categories && categories.length) {\n    shortcodeAtts.set('category', categories.join(','));\n\n    if (catOperator && 'all' === catOperator) {\n      shortcodeAtts.set('cat_operator', 'AND');\n    }\n  }\n\n  if (attributes && attributes.length) {\n    shortcodeAtts.set('terms', attributes.map(function (_ref) {\n      var id = _ref.id;\n      return id;\n    }).join(','));\n    shortcodeAtts.set('attribute', attributes[0].attr_slug);\n\n    if (attrOperator && 'all' === attrOperator) {\n      shortcodeAtts.set('terms_operator', 'AND');\n    }\n  }\n\n  if (orderby) {\n    if ('price_desc' === orderby) {\n      shortcodeAtts.set('orderby', 'price');\n      shortcodeAtts.set('order', 'DESC');\n    } else if ('price_asc' === orderby) {\n      shortcodeAtts.set('orderby', 'price');\n      shortcodeAtts.set('order', 'ASC');\n    } else if ('date' === orderby) {\n      shortcodeAtts.set('orderby', 'date');\n      shortcodeAtts.set('order', 'DESC');\n    } else {\n      shortcodeAtts.set('orderby', orderby);\n    }\n  } // Toggle shortcode atts depending on block type.\n\n\n  switch (name) {\n    case 'woocommerce/product-best-sellers':\n      shortcodeAtts.set('best_selling', '1');\n      break;\n\n    case 'woocommerce/product-top-rated':\n      shortcodeAtts.set('orderby', 'rating');\n      break;\n\n    case 'woocommerce/product-on-sale':\n      shortcodeAtts.set('on_sale', '1');\n      break;\n\n    case 'woocommerce/product-new':\n      shortcodeAtts.set('orderby', 'date');\n      shortcodeAtts.set('order', 'DESC');\n      break;\n\n    case 'woocommerce/handpicked-products':\n      if (!products.length) {\n        return '';\n      }\n\n      shortcodeAtts.set('ids', products.join(','));\n      shortcodeAtts.set('limit', products.length);\n      break;\n\n    case 'woocommerce/product-category':\n      if (!categories || !categories.length) {\n        return '';\n      }\n\n      break;\n\n    case 'woocommerce/products-by-attribute':\n      if (!attributes || !attributes.length) {\n        return '';\n      }\n\n      break;\n  } // Build the shortcode string out of the set shortcode attributes.\n\n\n  var shortcode = '[products';\n  var _iteratorNormalCompletion = true;\n  var _didIteratorError = false;\n  var _iteratorError = undefined;\n\n  try {\n    for (var _iterator = shortcodeAtts[Symbol.iterator](), _step; !(_iteratorNormalCompletion = (_step = _iterator.next()).done); _iteratorNormalCompletion = true) {\n      var _step$value = _babel_runtime_helpers_slicedToArray__WEBPACK_IMPORTED_MODULE_0___default()(_step.value, 2),\n          key = _step$value[0],\n          value = _step$value[1];\n\n      shortcode += ' ' + key + '=\"' + value + '\"';\n    }\n  } catch (err) {\n    _didIteratorError = true;\n    _iteratorError = err;\n  } finally {\n    try {\n      if (!_iteratorNormalCompletion && _iterator.return != null) {\n        _iterator.return();\n      }\n    } finally {\n      if (_didIteratorError) {\n        throw _iteratorError;\n      }\n    }\n  }\n\n  shortcode += ']';\n  return shortcode;\n}\n\n//# sourceURL=webpack:///./assets/js/utils/get-shortcode.js?");

/***/ }),

/***/ "./assets/js/utils/shared-attributes.js":
/*!**********************************************!*\
  !*** ./assets/js/utils/shared-attributes.js ***!
  \**********************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony default export */ __webpack_exports__[\"default\"] = ({\n  /**\n   * Number of columns.\n   */\n  columns: {\n    type: 'number',\n    default: wc_product_block_data.default_columns\n  },\n\n  /**\n   * Number of rows.\n   */\n  rows: {\n    type: 'number',\n    default: wc_product_block_data.default_rows\n  },\n\n  /**\n   * Product category, used to display only products in the given categories.\n   */\n  categories: {\n    type: 'array',\n    default: []\n  },\n\n  /**\n   * Product category operator, used to restrict to products in all or any selected categories.\n   */\n  catOperator: {\n    type: 'string',\n    default: 'any'\n  },\n\n  /**\n   * Content visibility setting\n   */\n  contentVisibility: {\n    type: 'object',\n    default: {\n      title: true,\n      price: true,\n      button: true\n    }\n  }\n});\n\n//# sourceURL=webpack:///./assets/js/utils/shared-attributes.js?");

/***/ }),

/***/ "./node_modules/moment/locale sync recursive ^\\.\\/.*$":
/*!**************************************************!*\
  !*** ./node_modules/moment/locale sync ^\.\/.*$ ***!
  \**************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

eval("var map = {\n\t\"./af\": \"./node_modules/moment/locale/af.js\",\n\t\"./af.js\": \"./node_modules/moment/locale/af.js\",\n\t\"./ar\": \"./node_modules/moment/locale/ar.js\",\n\t\"./ar-dz\": \"./node_modules/moment/locale/ar-dz.js\",\n\t\"./ar-dz.js\": \"./node_modules/moment/locale/ar-dz.js\",\n\t\"./ar-kw\": \"./node_modules/moment/locale/ar-kw.js\",\n\t\"./ar-kw.js\": \"./node_modules/moment/locale/ar-kw.js\",\n\t\"./ar-ly\": \"./node_modules/moment/locale/ar-ly.js\",\n\t\"./ar-ly.js\": \"./node_modules/moment/locale/ar-ly.js\",\n\t\"./ar-ma\": \"./node_modules/moment/locale/ar-ma.js\",\n\t\"./ar-ma.js\": \"./node_modules/moment/locale/ar-ma.js\",\n\t\"./ar-sa\": \"./node_modules/moment/locale/ar-sa.js\",\n\t\"./ar-sa.js\": \"./node_modules/moment/locale/ar-sa.js\",\n\t\"./ar-tn\": \"./node_modules/moment/locale/ar-tn.js\",\n\t\"./ar-tn.js\": \"./node_modules/moment/locale/ar-tn.js\",\n\t\"./ar.js\": \"./node_modules/moment/locale/ar.js\",\n\t\"./az\": \"./node_modules/moment/locale/az.js\",\n\t\"./az.js\": \"./node_modules/moment/locale/az.js\",\n\t\"./be\": \"./node_modules/moment/locale/be.js\",\n\t\"./be.js\": \"./node_modules/moment/locale/be.js\",\n\t\"./bg\": \"./node_modules/moment/locale/bg.js\",\n\t\"./bg.js\": \"./node_modules/moment/locale/bg.js\",\n\t\"./bm\": \"./node_modules/moment/locale/bm.js\",\n\t\"./bm.js\": \"./node_modules/moment/locale/bm.js\",\n\t\"./bn\": \"./node_modules/moment/locale/bn.js\",\n\t\"./bn.js\": \"./node_modules/moment/locale/bn.js\",\n\t\"./bo\": \"./node_modules/moment/locale/bo.js\",\n\t\"./bo.js\": \"./node_modules/moment/locale/bo.js\",\n\t\"./br\": \"./node_modules/moment/locale/br.js\",\n\t\"./br.js\": \"./node_modules/moment/locale/br.js\",\n\t\"./bs\": \"./node_modules/moment/locale/bs.js\",\n\t\"./bs.js\": \"./node_modules/moment/locale/bs.js\",\n\t\"./ca\": \"./node_modules/moment/locale/ca.js\",\n\t\"./ca.js\": \"./node_modules/moment/locale/ca.js\",\n\t\"./cs\": \"./node_modules/moment/locale/cs.js\",\n\t\"./cs.js\": \"./node_modules/moment/locale/cs.js\",\n\t\"./cv\": \"./node_modules/moment/locale/cv.js\",\n\t\"./cv.js\": \"./node_modules/moment/locale/cv.js\",\n\t\"./cy\": \"./node_modules/moment/locale/cy.js\",\n\t\"./cy.js\": \"./node_modules/moment/locale/cy.js\",\n\t\"./da\": \"./node_modules/moment/locale/da.js\",\n\t\"./da.js\": \"./node_modules/moment/locale/da.js\",\n\t\"./de\": \"./node_modules/moment/locale/de.js\",\n\t\"./de-at\": \"./node_modules/moment/locale/de-at.js\",\n\t\"./de-at.js\": \"./node_modules/moment/locale/de-at.js\",\n\t\"./de-ch\": \"./node_modules/moment/locale/de-ch.js\",\n\t\"./de-ch.js\": \"./node_modules/moment/locale/de-ch.js\",\n\t\"./de.js\": \"./node_modules/moment/locale/de.js\",\n\t\"./dv\": \"./node_modules/moment/locale/dv.js\",\n\t\"./dv.js\": \"./node_modules/moment/locale/dv.js\",\n\t\"./el\": \"./node_modules/moment/locale/el.js\",\n\t\"./el.js\": \"./node_modules/moment/locale/el.js\",\n\t\"./en-au\": \"./node_modules/moment/locale/en-au.js\",\n\t\"./en-au.js\": \"./node_modules/moment/locale/en-au.js\",\n\t\"./en-ca\": \"./node_modules/moment/locale/en-ca.js\",\n\t\"./en-ca.js\": \"./node_modules/moment/locale/en-ca.js\",\n\t\"./en-gb\": \"./node_modules/moment/locale/en-gb.js\",\n\t\"./en-gb.js\": \"./node_modules/moment/locale/en-gb.js\",\n\t\"./en-ie\": \"./node_modules/moment/locale/en-ie.js\",\n\t\"./en-ie.js\": \"./node_modules/moment/locale/en-ie.js\",\n\t\"./en-il\": \"./node_modules/moment/locale/en-il.js\",\n\t\"./en-il.js\": \"./node_modules/moment/locale/en-il.js\",\n\t\"./en-nz\": \"./node_modules/moment/locale/en-nz.js\",\n\t\"./en-nz.js\": \"./node_modules/moment/locale/en-nz.js\",\n\t\"./eo\": \"./node_modules/moment/locale/eo.js\",\n\t\"./eo.js\": \"./node_modules/moment/locale/eo.js\",\n\t\"./es\": \"./node_modules/moment/locale/es.js\",\n\t\"./es-do\": \"./node_modules/moment/locale/es-do.js\",\n\t\"./es-do.js\": \"./node_modules/moment/locale/es-do.js\",\n\t\"./es-us\": \"./node_modules/moment/locale/es-us.js\",\n\t\"./es-us.js\": \"./node_modules/moment/locale/es-us.js\",\n\t\"./es.js\": \"./node_modules/moment/locale/es.js\",\n\t\"./et\": \"./node_modules/moment/locale/et.js\",\n\t\"./et.js\": \"./node_modules/moment/locale/et.js\",\n\t\"./eu\": \"./node_modules/moment/locale/eu.js\",\n\t\"./eu.js\": \"./node_modules/moment/locale/eu.js\",\n\t\"./fa\": \"./node_modules/moment/locale/fa.js\",\n\t\"./fa.js\": \"./node_modules/moment/locale/fa.js\",\n\t\"./fi\": \"./node_modules/moment/locale/fi.js\",\n\t\"./fi.js\": \"./node_modules/moment/locale/fi.js\",\n\t\"./fo\": \"./node_modules/moment/locale/fo.js\",\n\t\"./fo.js\": \"./node_modules/moment/locale/fo.js\",\n\t\"./fr\": \"./node_modules/moment/locale/fr.js\",\n\t\"./fr-ca\": \"./node_modules/moment/locale/fr-ca.js\",\n\t\"./fr-ca.js\": \"./node_modules/moment/locale/fr-ca.js\",\n\t\"./fr-ch\": \"./node_modules/moment/locale/fr-ch.js\",\n\t\"./fr-ch.js\": \"./node_modules/moment/locale/fr-ch.js\",\n\t\"./fr.js\": \"./node_modules/moment/locale/fr.js\",\n\t\"./fy\": \"./node_modules/moment/locale/fy.js\",\n\t\"./fy.js\": \"./node_modules/moment/locale/fy.js\",\n\t\"./gd\": \"./node_modules/moment/locale/gd.js\",\n\t\"./gd.js\": \"./node_modules/moment/locale/gd.js\",\n\t\"./gl\": \"./node_modules/moment/locale/gl.js\",\n\t\"./gl.js\": \"./node_modules/moment/locale/gl.js\",\n\t\"./gom-latn\": \"./node_modules/moment/locale/gom-latn.js\",\n\t\"./gom-latn.js\": \"./node_modules/moment/locale/gom-latn.js\",\n\t\"./gu\": \"./node_modules/moment/locale/gu.js\",\n\t\"./gu.js\": \"./node_modules/moment/locale/gu.js\",\n\t\"./he\": \"./node_modules/moment/locale/he.js\",\n\t\"./he.js\": \"./node_modules/moment/locale/he.js\",\n\t\"./hi\": \"./node_modules/moment/locale/hi.js\",\n\t\"./hi.js\": \"./node_modules/moment/locale/hi.js\",\n\t\"./hr\": \"./node_modules/moment/locale/hr.js\",\n\t\"./hr.js\": \"./node_modules/moment/locale/hr.js\",\n\t\"./hu\": \"./node_modules/moment/locale/hu.js\",\n\t\"./hu.js\": \"./node_modules/moment/locale/hu.js\",\n\t\"./hy-am\": \"./node_modules/moment/locale/hy-am.js\",\n\t\"./hy-am.js\": \"./node_modules/moment/locale/hy-am.js\",\n\t\"./id\": \"./node_modules/moment/locale/id.js\",\n\t\"./id.js\": \"./node_modules/moment/locale/id.js\",\n\t\"./is\": \"./node_modules/moment/locale/is.js\",\n\t\"./is.js\": \"./node_modules/moment/locale/is.js\",\n\t\"./it\": \"./node_modules/moment/locale/it.js\",\n\t\"./it.js\": \"./node_modules/moment/locale/it.js\",\n\t\"./ja\": \"./node_modules/moment/locale/ja.js\",\n\t\"./ja.js\": \"./node_modules/moment/locale/ja.js\",\n\t\"./jv\": \"./node_modules/moment/locale/jv.js\",\n\t\"./jv.js\": \"./node_modules/moment/locale/jv.js\",\n\t\"./ka\": \"./node_modules/moment/locale/ka.js\",\n\t\"./ka.js\": \"./node_modules/moment/locale/ka.js\",\n\t\"./kk\": \"./node_modules/moment/locale/kk.js\",\n\t\"./kk.js\": \"./node_modules/moment/locale/kk.js\",\n\t\"./km\": \"./node_modules/moment/locale/km.js\",\n\t\"./km.js\": \"./node_modules/moment/locale/km.js\",\n\t\"./kn\": \"./node_modules/moment/locale/kn.js\",\n\t\"./kn.js\": \"./node_modules/moment/locale/kn.js\",\n\t\"./ko\": \"./node_modules/moment/locale/ko.js\",\n\t\"./ko.js\": \"./node_modules/moment/locale/ko.js\",\n\t\"./ky\": \"./node_modules/moment/locale/ky.js\",\n\t\"./ky.js\": \"./node_modules/moment/locale/ky.js\",\n\t\"./lb\": \"./node_modules/moment/locale/lb.js\",\n\t\"./lb.js\": \"./node_modules/moment/locale/lb.js\",\n\t\"./lo\": \"./node_modules/moment/locale/lo.js\",\n\t\"./lo.js\": \"./node_modules/moment/locale/lo.js\",\n\t\"./lt\": \"./node_modules/moment/locale/lt.js\",\n\t\"./lt.js\": \"./node_modules/moment/locale/lt.js\",\n\t\"./lv\": \"./node_modules/moment/locale/lv.js\",\n\t\"./lv.js\": \"./node_modules/moment/locale/lv.js\",\n\t\"./me\": \"./node_modules/moment/locale/me.js\",\n\t\"./me.js\": \"./node_modules/moment/locale/me.js\",\n\t\"./mi\": \"./node_modules/moment/locale/mi.js\",\n\t\"./mi.js\": \"./node_modules/moment/locale/mi.js\",\n\t\"./mk\": \"./node_modules/moment/locale/mk.js\",\n\t\"./mk.js\": \"./node_modules/moment/locale/mk.js\",\n\t\"./ml\": \"./node_modules/moment/locale/ml.js\",\n\t\"./ml.js\": \"./node_modules/moment/locale/ml.js\",\n\t\"./mn\": \"./node_modules/moment/locale/mn.js\",\n\t\"./mn.js\": \"./node_modules/moment/locale/mn.js\",\n\t\"./mr\": \"./node_modules/moment/locale/mr.js\",\n\t\"./mr.js\": \"./node_modules/moment/locale/mr.js\",\n\t\"./ms\": \"./node_modules/moment/locale/ms.js\",\n\t\"./ms-my\": \"./node_modules/moment/locale/ms-my.js\",\n\t\"./ms-my.js\": \"./node_modules/moment/locale/ms-my.js\",\n\t\"./ms.js\": \"./node_modules/moment/locale/ms.js\",\n\t\"./mt\": \"./node_modules/moment/locale/mt.js\",\n\t\"./mt.js\": \"./node_modules/moment/locale/mt.js\",\n\t\"./my\": \"./node_modules/moment/locale/my.js\",\n\t\"./my.js\": \"./node_modules/moment/locale/my.js\",\n\t\"./nb\": \"./node_modules/moment/locale/nb.js\",\n\t\"./nb.js\": \"./node_modules/moment/locale/nb.js\",\n\t\"./ne\": \"./node_modules/moment/locale/ne.js\",\n\t\"./ne.js\": \"./node_modules/moment/locale/ne.js\",\n\t\"./nl\": \"./node_modules/moment/locale/nl.js\",\n\t\"./nl-be\": \"./node_modules/moment/locale/nl-be.js\",\n\t\"./nl-be.js\": \"./node_modules/moment/locale/nl-be.js\",\n\t\"./nl.js\": \"./node_modules/moment/locale/nl.js\",\n\t\"./nn\": \"./node_modules/moment/locale/nn.js\",\n\t\"./nn.js\": \"./node_modules/moment/locale/nn.js\",\n\t\"./pa-in\": \"./node_modules/moment/locale/pa-in.js\",\n\t\"./pa-in.js\": \"./node_modules/moment/locale/pa-in.js\",\n\t\"./pl\": \"./node_modules/moment/locale/pl.js\",\n\t\"./pl.js\": \"./node_modules/moment/locale/pl.js\",\n\t\"./pt\": \"./node_modules/moment/locale/pt.js\",\n\t\"./pt-br\": \"./node_modules/moment/locale/pt-br.js\",\n\t\"./pt-br.js\": \"./node_modules/moment/locale/pt-br.js\",\n\t\"./pt.js\": \"./node_modules/moment/locale/pt.js\",\n\t\"./ro\": \"./node_modules/moment/locale/ro.js\",\n\t\"./ro.js\": \"./node_modules/moment/locale/ro.js\",\n\t\"./ru\": \"./node_modules/moment/locale/ru.js\",\n\t\"./ru.js\": \"./node_modules/moment/locale/ru.js\",\n\t\"./sd\": \"./node_modules/moment/locale/sd.js\",\n\t\"./sd.js\": \"./node_modules/moment/locale/sd.js\",\n\t\"./se\": \"./node_modules/moment/locale/se.js\",\n\t\"./se.js\": \"./node_modules/moment/locale/se.js\",\n\t\"./si\": \"./node_modules/moment/locale/si.js\",\n\t\"./si.js\": \"./node_modules/moment/locale/si.js\",\n\t\"./sk\": \"./node_modules/moment/locale/sk.js\",\n\t\"./sk.js\": \"./node_modules/moment/locale/sk.js\",\n\t\"./sl\": \"./node_modules/moment/locale/sl.js\",\n\t\"./sl.js\": \"./node_modules/moment/locale/sl.js\",\n\t\"./sq\": \"./node_modules/moment/locale/sq.js\",\n\t\"./sq.js\": \"./node_modules/moment/locale/sq.js\",\n\t\"./sr\": \"./node_modules/moment/locale/sr.js\",\n\t\"./sr-cyrl\": \"./node_modules/moment/locale/sr-cyrl.js\",\n\t\"./sr-cyrl.js\": \"./node_modules/moment/locale/sr-cyrl.js\",\n\t\"./sr.js\": \"./node_modules/moment/locale/sr.js\",\n\t\"./ss\": \"./node_modules/moment/locale/ss.js\",\n\t\"./ss.js\": \"./node_modules/moment/locale/ss.js\",\n\t\"./sv\": \"./node_modules/moment/locale/sv.js\",\n\t\"./sv.js\": \"./node_modules/moment/locale/sv.js\",\n\t\"./sw\": \"./node_modules/moment/locale/sw.js\",\n\t\"./sw.js\": \"./node_modules/moment/locale/sw.js\",\n\t\"./ta\": \"./node_modules/moment/locale/ta.js\",\n\t\"./ta.js\": \"./node_modules/moment/locale/ta.js\",\n\t\"./te\": \"./node_modules/moment/locale/te.js\",\n\t\"./te.js\": \"./node_modules/moment/locale/te.js\",\n\t\"./tet\": \"./node_modules/moment/locale/tet.js\",\n\t\"./tet.js\": \"./node_modules/moment/locale/tet.js\",\n\t\"./tg\": \"./node_modules/moment/locale/tg.js\",\n\t\"./tg.js\": \"./node_modules/moment/locale/tg.js\",\n\t\"./th\": \"./node_modules/moment/locale/th.js\",\n\t\"./th.js\": \"./node_modules/moment/locale/th.js\",\n\t\"./tl-ph\": \"./node_modules/moment/locale/tl-ph.js\",\n\t\"./tl-ph.js\": \"./node_modules/moment/locale/tl-ph.js\",\n\t\"./tlh\": \"./node_modules/moment/locale/tlh.js\",\n\t\"./tlh.js\": \"./node_modules/moment/locale/tlh.js\",\n\t\"./tr\": \"./node_modules/moment/locale/tr.js\",\n\t\"./tr.js\": \"./node_modules/moment/locale/tr.js\",\n\t\"./tzl\": \"./node_modules/moment/locale/tzl.js\",\n\t\"./tzl.js\": \"./node_modules/moment/locale/tzl.js\",\n\t\"./tzm\": \"./node_modules/moment/locale/tzm.js\",\n\t\"./tzm-latn\": \"./node_modules/moment/locale/tzm-latn.js\",\n\t\"./tzm-latn.js\": \"./node_modules/moment/locale/tzm-latn.js\",\n\t\"./tzm.js\": \"./node_modules/moment/locale/tzm.js\",\n\t\"./ug-cn\": \"./node_modules/moment/locale/ug-cn.js\",\n\t\"./ug-cn.js\": \"./node_modules/moment/locale/ug-cn.js\",\n\t\"./uk\": \"./node_modules/moment/locale/uk.js\",\n\t\"./uk.js\": \"./node_modules/moment/locale/uk.js\",\n\t\"./ur\": \"./node_modules/moment/locale/ur.js\",\n\t\"./ur.js\": \"./node_modules/moment/locale/ur.js\",\n\t\"./uz\": \"./node_modules/moment/locale/uz.js\",\n\t\"./uz-latn\": \"./node_modules/moment/locale/uz-latn.js\",\n\t\"./uz-latn.js\": \"./node_modules/moment/locale/uz-latn.js\",\n\t\"./uz.js\": \"./node_modules/moment/locale/uz.js\",\n\t\"./vi\": \"./node_modules/moment/locale/vi.js\",\n\t\"./vi.js\": \"./node_modules/moment/locale/vi.js\",\n\t\"./x-pseudo\": \"./node_modules/moment/locale/x-pseudo.js\",\n\t\"./x-pseudo.js\": \"./node_modules/moment/locale/x-pseudo.js\",\n\t\"./yo\": \"./node_modules/moment/locale/yo.js\",\n\t\"./yo.js\": \"./node_modules/moment/locale/yo.js\",\n\t\"./zh-cn\": \"./node_modules/moment/locale/zh-cn.js\",\n\t\"./zh-cn.js\": \"./node_modules/moment/locale/zh-cn.js\",\n\t\"./zh-hk\": \"./node_modules/moment/locale/zh-hk.js\",\n\t\"./zh-hk.js\": \"./node_modules/moment/locale/zh-hk.js\",\n\t\"./zh-tw\": \"./node_modules/moment/locale/zh-tw.js\",\n\t\"./zh-tw.js\": \"./node_modules/moment/locale/zh-tw.js\"\n};\n\n\nfunction webpackContext(req) {\n\tvar id = webpackContextResolve(req);\n\treturn __webpack_require__(id);\n}\nfunction webpackContextResolve(req) {\n\tif(!__webpack_require__.o(map, req)) {\n\t\tvar e = new Error(\"Cannot find module '\" + req + \"'\");\n\t\te.code = 'MODULE_NOT_FOUND';\n\t\tthrow e;\n\t}\n\treturn map[req];\n}\nwebpackContext.keys = function webpackContextKeys() {\n\treturn Object.keys(map);\n};\nwebpackContext.resolve = webpackContextResolve;\nmodule.exports = webpackContext;\nwebpackContext.id = \"./node_modules/moment/locale sync recursive ^\\\\.\\\\/.*$\";\n\n//# sourceURL=webpack:///./node_modules/moment/locale_sync_^\\.\\/.*$?");

/***/ }),

/***/ 0:
/*!**********************!*\
  !*** util (ignored) ***!
  \**********************/
/*! no static exports found */
/***/ (function(module, exports) {

eval("/* (ignored) */\n\n//# sourceURL=webpack:///util_(ignored)?");

/***/ }),

/***/ 1:
/*!**********************!*\
  !*** util (ignored) ***!
  \**********************/
/*! no static exports found */
/***/ (function(module, exports) {

eval("/* (ignored) */\n\n//# sourceURL=webpack:///util_(ignored)?");

/***/ }),

/***/ 2:
/*!************************!*\
  !*** buffer (ignored) ***!
  \************************/
/*! no static exports found */
/***/ (function(module, exports) {

eval("/* (ignored) */\n\n//# sourceURL=webpack:///buffer_(ignored)?");

/***/ }),

/***/ 3:
/*!************************!*\
  !*** crypto (ignored) ***!
  \************************/
/*! no static exports found */
/***/ (function(module, exports) {

eval("/* (ignored) */\n\n//# sourceURL=webpack:///crypto_(ignored)?");

/***/ }),

/***/ "@wordpress/api-fetch":
/*!*******************************************!*\
  !*** external {"this":["wp","apiFetch"]} ***!
  \*******************************************/
/*! no static exports found */
/***/ (function(module, exports) {

eval("(function() { module.exports = this[\"wp\"][\"apiFetch\"]; }());\n\n//# sourceURL=webpack:///external_%7B%22this%22:%5B%22wp%22,%22apiFetch%22%5D%7D?");

/***/ }),

/***/ "@wordpress/blocks":
/*!*****************************************!*\
  !*** external {"this":["wp","blocks"]} ***!
  \*****************************************/
/*! no static exports found */
/***/ (function(module, exports) {

eval("(function() { module.exports = this[\"wp\"][\"blocks\"]; }());\n\n//# sourceURL=webpack:///external_%7B%22this%22:%5B%22wp%22,%22blocks%22%5D%7D?");

/***/ }),

/***/ "@wordpress/components":
/*!*********************************************!*\
  !*** external {"this":["wp","components"]} ***!
  \*********************************************/
/*! no static exports found */
/***/ (function(module, exports) {

eval("(function() { module.exports = this[\"wp\"][\"components\"]; }());\n\n//# sourceURL=webpack:///external_%7B%22this%22:%5B%22wp%22,%22components%22%5D%7D?");

/***/ }),

/***/ "@wordpress/compose":
/*!******************************************!*\
  !*** external {"this":["wp","compose"]} ***!
  \******************************************/
/*! no static exports found */
/***/ (function(module, exports) {

eval("(function() { module.exports = this[\"wp\"][\"compose\"]; }());\n\n//# sourceURL=webpack:///external_%7B%22this%22:%5B%22wp%22,%22compose%22%5D%7D?");

/***/ }),

/***/ "@wordpress/data":
/*!***************************************!*\
  !*** external {"this":["wp","data"]} ***!
  \***************************************/
/*! no static exports found */
/***/ (function(module, exports) {

eval("(function() { module.exports = this[\"wp\"][\"data\"]; }());\n\n//# sourceURL=webpack:///external_%7B%22this%22:%5B%22wp%22,%22data%22%5D%7D?");

/***/ }),

/***/ "@wordpress/editor":
/*!*****************************************!*\
  !*** external {"this":["wp","editor"]} ***!
  \*****************************************/
/*! no static exports found */
/***/ (function(module, exports) {

eval("(function() { module.exports = this[\"wp\"][\"editor\"]; }());\n\n//# sourceURL=webpack:///external_%7B%22this%22:%5B%22wp%22,%22editor%22%5D%7D?");

/***/ }),

/***/ "@wordpress/element":
/*!******************************************!*\
  !*** external {"this":["wp","element"]} ***!
  \******************************************/
/*! no static exports found */
/***/ (function(module, exports) {

eval("(function() { module.exports = this[\"wp\"][\"element\"]; }());\n\n//# sourceURL=webpack:///external_%7B%22this%22:%5B%22wp%22,%22element%22%5D%7D?");

/***/ }),

/***/ "@wordpress/i18n":
/*!***************************************!*\
  !*** external {"this":["wp","i18n"]} ***!
  \***************************************/
/*! no static exports found */
/***/ (function(module, exports) {

eval("(function() { module.exports = this[\"wp\"][\"i18n\"]; }());\n\n//# sourceURL=webpack:///external_%7B%22this%22:%5B%22wp%22,%22i18n%22%5D%7D?");

/***/ }),

/***/ "@wordpress/url":
/*!**************************************!*\
  !*** external {"this":["wp","url"]} ***!
  \**************************************/
/*! no static exports found */
/***/ (function(module, exports) {

eval("(function() { module.exports = this[\"wp\"][\"url\"]; }());\n\n//# sourceURL=webpack:///external_%7B%22this%22:%5B%22wp%22,%22url%22%5D%7D?");

/***/ }),

/***/ "lodash":
/*!*************************!*\
  !*** external "lodash" ***!
  \*************************/
/*! no static exports found */
/***/ (function(module, exports) {

eval("(function() { module.exports = this[\"lodash\"]; }());\n\n//# sourceURL=webpack:///external_%22lodash%22?");

/***/ })

/******/ })));