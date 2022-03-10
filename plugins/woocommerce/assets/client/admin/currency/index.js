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
/******/ 	return __webpack_require__(__webpack_require__.s = 470);
/******/ })
/************************************************************************/
/******/ ({

/***/ 0:
/***/ (function(module, exports) {

(function() { module.exports = window["wp"]["element"]; }());

/***/ }),

/***/ 136:
/***/ (function(module, exports) {

(function() { module.exports = window["wc"]["number"]; }());

/***/ }),

/***/ 2:
/***/ (function(module, exports) {

(function() { module.exports = window["wp"]["i18n"]; }());

/***/ }),

/***/ 34:
/***/ (function(module, exports) {

(function() { module.exports = window["wp"]["htmlEntities"]; }());

/***/ }),

/***/ 47:
/***/ (function(module, exports) {

(function() { module.exports = window["wp"]["deprecated"]; }());

/***/ }),

/***/ 470:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "getCurrencyData", function() { return getCurrencyData; });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_html_entities__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(34);
/* harmony import */ var _wordpress_html_entities__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_html_entities__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(2);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _woocommerce_number__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(136);
/* harmony import */ var _woocommerce_number__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_number__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _wordpress_deprecated__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(47);
/* harmony import */ var _wordpress_deprecated__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_wordpress_deprecated__WEBPACK_IMPORTED_MODULE_4__);
/**
 * External dependencies
 */






const CurrencyFactory = function (currencySetting) {
  let currency;
  setCurrency(currencySetting);

  function setCurrency(setting) {
    const defaultCurrency = {
      code: 'USD',
      symbol: '$',
      symbolPosition: 'left',
      thousandSeparator: ',',
      decimalSeparator: '.',
      precision: 2
    };
    const config = { ...defaultCurrency,
      ...setting
    };
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
    const tmp = document.createElement('DIV');
    tmp.innerHTML = str;
    return tmp.textContent || tmp.innerText || '';
  }
  /**
   * Formats money value.
   *
   * @param   {number|string} number number to format
   * @param   {boolean} [useCode=false] Set to `true` to use the currency code instead of the symbol.
   * @return {?string} A formatted string.
   */


  function formatAmount(number) {
    let useCode = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : false;
    const formattedNumber = Object(_woocommerce_number__WEBPACK_IMPORTED_MODULE_3__["numberFormat"])(currency, number);

    if (formattedNumber === '') {
      return formattedNumber;
    }

    const {
      priceFormat,
      symbol,
      code
    } = currency; // eslint-disable-next-line @wordpress/valid-sprintf

    return Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__["sprintf"])(priceFormat, useCode ? code : symbol, formattedNumber);
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
    _wordpress_deprecated__WEBPACK_IMPORTED_MODULE_4___default()('Currency().formatCurrency', {
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
        return '%1$s %2$s';

      case 'right_space':
        return '%2$s %1$s';
    }

    return '%1$s%2$s';
  }
  /**
   * Get formatted data for a country from supplied locale and symbol info.
   *
   * @param {string} countryCode Country code.
   * @param {Object} localeInfo Locale info by country code.
   * @param {Object} currencySymbols Currency symbols by symbol code.
   * @return {Object} Formatted currency data for country.
   */


  function getDataForCountry(countryCode) {
    let localeInfo = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};
    let currencySymbols = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : {};
    const countryInfo = localeInfo[countryCode] || {};
    const symbol = currencySymbols[countryInfo.currency_code];

    if (!symbol) {
      return {};
    }

    return {
      code: countryInfo.currency_code,
      symbol: Object(_wordpress_html_entities__WEBPACK_IMPORTED_MODULE_1__["decodeEntities"])(symbol),
      symbolPosition: countryInfo.currency_pos,
      thousandSeparator: countryInfo.thousand_sep,
      decimalSeparator: countryInfo.decimal_sep,
      precision: countryInfo.num_decimals
    };
  }

  return {
    getCurrencyConfig: () => {
      return { ...currency
      };
    },
    getDataForCountry,
    setCurrency,
    formatAmount,
    formatCurrency,
    getPriceFormat,

    /**
     * Get the rounded decimal value of a number at the precision used for the current currency.
     * This is a work-around for fraction-cents, meant to be used like `wc_format_decimal`
     *
     * @param {number|string} number A floating point number (or integer), or string that converts to a number
     * @return {number} The original number rounded to a decimal point
     */
    formatDecimal(number) {
      if (typeof number !== 'number') {
        number = parseFloat(number);
      }

      if (Number.isNaN(number)) {
        return 0;
      }

      const {
        precision
      } = currency;
      return Math.round(number * Math.pow(10, precision)) / Math.pow(10, precision);
    },

    /**
     * Get the string representation of a floating point number to the precision used by the current currency.
     * This is different from `formatAmount` by not returning the currency symbol.
     *
     * @param  {number|string} number A floating point number (or integer), or string that converts to a number
     * @return {string}               The original number rounded to a decimal point
     */
    formatDecimalString(number) {
      if (typeof number !== 'number') {
        number = parseFloat(number);
      }

      if (Number.isNaN(number)) {
        return '';
      }

      const {
        precision
      } = currency;
      return number.toFixed(precision);
    },

    /**
     * Render a currency for display in a component.
     *
     * @param  {number|string} number A floating point number (or integer), or string that converts to a number
     * @return {Node|string} The number formatted as currency and rendered for display.
     */
    render(number) {
      if (typeof number !== 'number') {
        number = parseFloat(number);
      }

      if (number < 0) {
        return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("span", {
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
 * @deprecated
 *
 * @return {Object} Curreny data.
 */

function getCurrencyData() {
  _wordpress_deprecated__WEBPACK_IMPORTED_MODULE_4___default()('getCurrencyData', {
    version: '3.1.0',
    alternative: 'CurrencyFactory.getDataForCountry',
    plugin: 'WooCommerce Admin',
    hint: 'Pass in the country, locale data, and symbol info to use getDataForCountry'
  }); // See https://github.com/woocommerce/woocommerce-admin/issues/3101.

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

/***/ })

/******/ });