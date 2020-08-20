this["wc"] = this["wc"] || {}; this["wc"]["currency"] =
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
/******/ 	return __webpack_require__(__webpack_require__.s = "./packages/currency/build-module/index.js");
/******/ })
/************************************************************************/
/******/ ({

/***/ "./node_modules/@babel/runtime/helpers/defineProperty.js":
/*!***************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/defineProperty.js ***!
  \***************************************************************/
/*! no static exports found */
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

/***/ "./node_modules/@wordpress/deprecated/build-module/index.js":
/*!******************************************************************!*\
  !*** ./node_modules/@wordpress/deprecated/build-module/index.js ***!
  \******************************************************************/
/*! exports provided: logged, default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "logged", function() { return logged; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return deprecated; });
/* harmony import */ var _wordpress_hooks__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/hooks */ "@wordpress/hooks");
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

/***/ "./packages/currency/build-module/index.js":
/*!*************************************************!*\
  !*** ./packages/currency/build-module/index.js ***!
  \*************************************************/
/*! exports provided: default, getCurrencyData */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "getCurrencyData", function() { return getCurrencyData; });
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/defineProperty */ "./node_modules/@babel/runtime/helpers/defineProperty.js");
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _woocommerce_number__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @woocommerce/number */ "@woocommerce/number");
/* harmony import */ var _woocommerce_number__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_number__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _wordpress_deprecated__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @wordpress/deprecated */ "./node_modules/@wordpress/deprecated/build-module/index.js");



function ownKeys(object, enumerableOnly) {
  var keys = Object.keys(object);

  if (Object.getOwnPropertySymbols) {
    var symbols = Object.getOwnPropertySymbols(object);
    if (enumerableOnly) symbols = symbols.filter(function (sym) {
      return Object.getOwnPropertyDescriptor(object, sym).enumerable;
    });
    keys.push.apply(keys, symbols);
  }

  return keys;
}

function _objectSpread(target) {
  for (var i = 1; i < arguments.length; i++) {
    var source = arguments[i] != null ? arguments[i] : {};

    if (i % 2) {
      ownKeys(Object(source), true).forEach(function (key) {
        _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0___default()(target, key, source[key]);
      });
    } else if (Object.getOwnPropertyDescriptors) {
      Object.defineProperties(target, Object.getOwnPropertyDescriptors(source));
    } else {
      ownKeys(Object(source)).forEach(function (key) {
        Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key));
      });
    }
  }

  return target;
}
/**
 * External dependencies
 */






var CurrencyFactory = function CurrencyFactory(currencySetting) {
  var currency;
  setCurrency(currencySetting);

  function setCurrency(setting) {
    var defaultCurrency = getCurrencyData().US;

    var config = _objectSpread(_objectSpread({}, defaultCurrency), setting);

    currency = {
      code: config.code.toString(),
      symbol: config.symbol.toString(),
      symbolPosition: config.symbolPosition.toString(),
      decimalSeparator: config.decimalSeparator.toString(),
      priceFormat: getPriceFormat(config),
      thousandSeparator: config.thousandSeparator.toString(),
      precision: parseInt(config.precision, 10)
    };
  }

  function stripTags(str) {
    var tmp = document.createElement('DIV');
    tmp.innerHTML = str;
    return tmp.textContent || tmp.innerText || '';
  }
  /**
   * Formats money value.
   *
   * @param   {number|string} number number to format
   * @return {?string} A formatted string.
   */


  function formatAmount(number) {
    var formattedNumber = Object(_woocommerce_number__WEBPACK_IMPORTED_MODULE_3__["numberFormat"])(currency, number);

    if (formattedNumber === '') {
      return formattedNumber;
    }

    var _currency = currency,
        priceFormat = _currency.priceFormat,
        symbol = _currency.symbol; // eslint-disable-next-line @wordpress/valid-sprintf

    return Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__["sprintf"])(priceFormat, symbol, formattedNumber);
  }
  /**
   * Formats money value.
   *
   * @deprecated
   *
   * @param   {number|string} number number to format
   * @return {?string} A formatted string.
   */


  function formatCurrency(number) {
    Object(_wordpress_deprecated__WEBPACK_IMPORTED_MODULE_4__["default"])('Currency().formatCurrency', {
      version: '5.0.0',
      alternative: 'Currency().formatAmount',
      plugin: 'WooCommerce',
      hint: '`formatAmount` accepts the same arguments as formatCurrency'
    });
    return formatAmount(number);
  }
  /**
   * Get the default price format from a currency.
   *
   * @param {Object} config Currency configuration.
   * @return {string} Price format.
   */


  function getPriceFormat(config) {
    if (config.priceFormat) {
      return stripTags(config.priceFormat.toString());
    }

    switch (config.symbolPosition) {
      case 'left':
        return '%1$s%2$s';

      case 'right':
        return '%2$s%1$s';

      case 'left_space':
        return '%1$s&nbsp;%2$s';

      case 'right_space':
        return '%2$s&nbsp;%1$s';
    }

    return '%1$s%2$s';
  }

  return {
    getCurrencyConfig: function getCurrencyConfig() {
      return _objectSpread({}, currency);
    },
    setCurrency: setCurrency,
    formatAmount: formatAmount,
    formatCurrency: formatCurrency,
    getPriceFormat: getPriceFormat,

    /**
     * Get the rounded decimal value of a number at the precision used for the current currency.
     * This is a work-around for fraction-cents, meant to be used like `wc_format_decimal`
     *
     * @param {number|string} number A floating point number (or integer), or string that converts to a number
     * @return {number} The original number rounded to a decimal point
     */
    formatDecimal: function formatDecimal(number) {
      if (typeof number !== 'number') {
        number = parseFloat(number);
      }

      if (Number.isNaN(number)) {
        return 0;
      }

      var _currency2 = currency,
          precision = _currency2.precision;
      return Math.round(number * Math.pow(10, precision)) / Math.pow(10, precision);
    },

    /**
     * Get the string representation of a floating point number to the precision used by the current currency.
     * This is different from `formatAmount` by not returning the currency symbol.
     *
     * @param  {number|string} number A floating point number (or integer), or string that converts to a number
     * @return {string}               The original number rounded to a decimal point
     */
    formatDecimalString: function formatDecimalString(number) {
      if (typeof number !== 'number') {
        number = parseFloat(number);
      }

      if (Number.isNaN(number)) {
        return '';
      }

      var _currency3 = currency,
          precision = _currency3.precision;
      return number.toFixed(precision);
    },

    /**
     * Render a currency for display in a component.
     *
     * @param  {number|string} number A floating point number (or integer), or string that converts to a number
     * @return {Node|string} The number formatted as currency and rendered for display.
     */
    render: function render(number) {
      if (typeof number !== 'number') {
        number = parseFloat(number);
      }

      if (number < 0) {
        return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])("span", {
          className: "is-negative"
        }, formatAmount(number));
      }

      return formatAmount(number);
    }
  };
};

/* harmony default export */ __webpack_exports__["default"] = (CurrencyFactory);
/**
 * Returns currency data by country/region. Contains code, symbol, position, thousands separator, decimal separator, and precision.
 *
 * Dev Note: When adding new currencies below, the exchange rate array should also be updated in WooCommerce Admin's `business-details.js`.
 *
 * @return {Object} Curreny data.
 */

function getCurrencyData() {
  // See https://github.com/woocommerce/woocommerce-admin/issues/3101.
  return {
    US: {
      code: 'USD',
      symbol: '$',
      symbolPosition: 'left',
      thousandSeparator: ',',
      decimalSeparator: '.',
      precision: 2
    },
    EU: {
      code: 'EUR',
      symbol: '€',
      symbolPosition: 'left',
      thousandSeparator: '.',
      decimalSeparator: ',',
      precision: 2
    },
    IN: {
      code: 'INR',
      symbol: '₹',
      symbolPosition: 'left',
      thousandSeparator: ',',
      decimalSeparator: '.',
      precision: 2
    },
    GB: {
      code: 'GBP',
      symbol: '£',
      symbolPosition: 'left',
      thousandSeparator: ',',
      decimalSeparator: '.',
      precision: 2
    },
    BR: {
      code: 'BRL',
      symbol: 'R$',
      symbolPosition: 'left',
      thousandSeparator: '.',
      decimalSeparator: ',',
      precision: 2
    },
    VN: {
      code: 'VND',
      symbol: '₫',
      symbolPosition: 'right',
      thousandSeparator: '.',
      decimalSeparator: ',',
      precision: 1
    },
    ID: {
      code: 'IDR',
      symbol: 'Rp',
      symbolPosition: 'left',
      thousandSeparator: '.',
      decimalSeparator: ',',
      precision: 0
    },
    BD: {
      code: 'BDT',
      symbol: '৳',
      symbolPosition: 'left',
      thousandSeparator: ',',
      decimalSeparator: '.',
      precision: 0
    },
    PK: {
      code: 'PKR',
      symbol: '₨',
      symbolPosition: 'left',
      thousandSeparator: ',',
      decimalSeparator: '.',
      precision: 2
    },
    RU: {
      code: 'RUB',
      symbol: '₽',
      symbolPosition: 'right',
      thousandSeparator: ' ',
      decimalSeparator: ',',
      precision: 2
    },
    TR: {
      code: 'TRY',
      symbol: '₺',
      symbolPosition: 'left',
      thousandSeparator: '.',
      decimalSeparator: ',',
      precision: 2
    },
    MX: {
      code: 'MXN',
      symbol: '$',
      symbolPosition: 'left',
      thousandSeparator: ',',
      decimalSeparator: '.',
      precision: 2
    },
    CA: {
      code: 'CAD',
      symbol: '$',
      symbolPosition: 'left',
      thousandSeparator: ',',
      decimalSeparator: '.',
      precision: 2
    }
  };
}

/***/ }),

/***/ "@woocommerce/number":
/*!*****************************************!*\
  !*** external {"this":["wc","number"]} ***!
  \*****************************************/
/*! no static exports found */
/***/ (function(module, exports) {

(function() { module.exports = this["wc"]["number"]; }());

/***/ }),

/***/ "@wordpress/element":
/*!******************************************!*\
  !*** external {"this":["wp","element"]} ***!
  \******************************************/
/*! no static exports found */
/***/ (function(module, exports) {

(function() { module.exports = this["wp"]["element"]; }());

/***/ }),

/***/ "@wordpress/hooks":
/*!****************************************!*\
  !*** external {"this":["wp","hooks"]} ***!
  \****************************************/
/*! no static exports found */
/***/ (function(module, exports) {

(function() { module.exports = this["wp"]["hooks"]; }());

/***/ }),

/***/ "@wordpress/i18n":
/*!***************************************!*\
  !*** external {"this":["wp","i18n"]} ***!
  \***************************************/
/*! no static exports found */
/***/ (function(module, exports) {

(function() { module.exports = this["wp"]["i18n"]; }());

/***/ })

/******/ });
//# sourceMappingURL=index.min.js.map
