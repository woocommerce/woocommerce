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
/******/ 	return __webpack_require__(__webpack_require__.s = 719);
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
  module.exports = __webpack_require__(113)();
}


/***/ }),

/***/ 10:
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

/***/ 11:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "a", function() { return _possibleConstructorReturn; });
/* harmony import */ var _helpers_esm_typeof__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(40);
/* harmony import */ var _assertThisInitialized__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(5);


function _possibleConstructorReturn(self, call) {
  if (call && (Object(_helpers_esm_typeof__WEBPACK_IMPORTED_MODULE_0__[/* default */ "a"])(call) === "object" || typeof call === "function")) {
    return call;
  }

  return Object(_assertThisInitialized__WEBPACK_IMPORTED_MODULE_1__[/* default */ "a"])(self);
}

/***/ }),

/***/ 113:
/***/ (function(module, exports, __webpack_require__) {

"use strict";
/**
 * Copyright (c) 2013-present, Facebook, Inc.
 *
 * This source code is licensed under the MIT license found in the
 * LICENSE file in the root directory of this source tree.
 */



var ReactPropTypesSecret = __webpack_require__(114);

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

/***/ 114:
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

/***/ 117:
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

/***/ 118:
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

/***/ 119:
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
  createDebug.humanize = __webpack_require__(120);
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

/***/ 12:
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

/***/ 120:
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

/***/ 13:
/***/ (function(module, exports) {

(function() { module.exports = this["React"]; }());

/***/ }),

/***/ 14:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "a", function() { return _objectWithoutProperties; });
/* harmony import */ var _objectWithoutPropertiesLoose__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(46);

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

/***/ 146:
/***/ (function(module, exports, __webpack_require__) {

var arrayWithHoles = __webpack_require__(419);

var iterableToArrayLimit = __webpack_require__(420);

var unsupportedIterableToArray = __webpack_require__(172);

var nonIterableRest = __webpack_require__(421);

function _slicedToArray(arr, i) {
  return arrayWithHoles(arr) || iterableToArrayLimit(arr, i) || unsupportedIterableToArray(arr, i) || nonIterableRest();
}

module.exports = _slicedToArray;

/***/ }),

/***/ 147:
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

/***/ 15:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";

// EXPORTS
__webpack_require__.d(__webpack_exports__, "a", function() { return /* binding */ _toConsumableArray; });

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/esm/arrayLikeToArray.js
var arrayLikeToArray = __webpack_require__(30);

// CONCATENATED MODULE: ./node_modules/@babel/runtime/helpers/esm/arrayWithoutHoles.js

function _arrayWithoutHoles(arr) {
  if (Array.isArray(arr)) return Object(arrayLikeToArray["a" /* default */])(arr);
}
// CONCATENATED MODULE: ./node_modules/@babel/runtime/helpers/esm/iterableToArray.js
function _iterableToArray(iter) {
  if (typeof Symbol !== "undefined" && Symbol.iterator in Object(iter)) return Array.from(iter);
}
// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/esm/unsupportedIterableToArray.js
var unsupportedIterableToArray = __webpack_require__(49);

// CONCATENATED MODULE: ./node_modules/@babel/runtime/helpers/esm/nonIterableSpread.js
function _nonIterableSpread() {
  throw new TypeError("Invalid attempt to spread non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method.");
}
// CONCATENATED MODULE: ./node_modules/@babel/runtime/helpers/esm/toConsumableArray.js




function _toConsumableArray(arr) {
  return _arrayWithoutHoles(arr) || _iterableToArray(arr) || Object(unsupportedIterableToArray["a" /* default */])(arr) || _nonIterableSpread();
}

/***/ }),

/***/ 152:
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

/***/ 169:
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

/***/ 17:
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

/***/ 172:
/***/ (function(module, exports, __webpack_require__) {

var arrayLikeToArray = __webpack_require__(152);

function _unsupportedIterableToArray(o, minLen) {
  if (!o) return;
  if (typeof o === "string") return arrayLikeToArray(o, minLen);
  var n = Object.prototype.toString.call(o).slice(8, -1);
  if (n === "Object" && o.constructor) n = o.constructor.name;
  if (n === "Map" || n === "Set") return Array.from(o);
  if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return arrayLikeToArray(o, minLen);
}

module.exports = _unsupportedIterableToArray;

/***/ }),

/***/ 174:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";

// EXPORTS
__webpack_require__.d(__webpack_exports__, "a", function() { return /* reexport */ components_button; });
__webpack_require__.d(__webpack_exports__, "b", function() { return /* reexport */ product_icon; });
__webpack_require__.d(__webpack_exports__, "c", function() { return /* reexport */ slider; });

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/extends.js
var helpers_extends = __webpack_require__(80);
var extends_default = /*#__PURE__*/__webpack_require__.n(helpers_extends);

// EXTERNAL MODULE: external {"this":["wp","element"]}
var external_this_wp_element_ = __webpack_require__(0);

// EXTERNAL MODULE: ./node_modules/@wordpress/components/build-module/button/index.js
var build_module_button = __webpack_require__(67);

// EXTERNAL MODULE: ./node_modules/classnames/index.js
var classnames = __webpack_require__(8);
var classnames_default = /*#__PURE__*/__webpack_require__.n(classnames);

// EXTERNAL MODULE: ./client/marketing/components/button/style.scss
var button_style = __webpack_require__(426);

// CONCATENATED MODULE: ./client/marketing/components/button/index.js



/**
 * External dependencies
 */


/**
 * Internal dependencies
 */


/* harmony default export */ var components_button = (function (props) {
  return Object(external_this_wp_element_["createElement"])(build_module_button["a" /* default */], extends_default()({}, props, {
    className: classnames_default()(props.className, 'woocommere-admin-marketing-button')
  }));
});
// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/classCallCheck.js
var classCallCheck = __webpack_require__(38);
var classCallCheck_default = /*#__PURE__*/__webpack_require__.n(classCallCheck);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/createClass.js
var createClass = __webpack_require__(37);
var createClass_default = /*#__PURE__*/__webpack_require__.n(createClass);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/inherits.js
var inherits = __webpack_require__(39);
var inherits_default = /*#__PURE__*/__webpack_require__.n(inherits);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/possibleConstructorReturn.js
var possibleConstructorReturn = __webpack_require__(42);
var possibleConstructorReturn_default = /*#__PURE__*/__webpack_require__.n(possibleConstructorReturn);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/getPrototypeOf.js
var getPrototypeOf = __webpack_require__(26);
var getPrototypeOf_default = /*#__PURE__*/__webpack_require__.n(getPrototypeOf);

// EXTERNAL MODULE: ./node_modules/prop-types/index.js
var prop_types = __webpack_require__(1);
var prop_types_default = /*#__PURE__*/__webpack_require__.n(prop_types);

// EXTERNAL MODULE: ./client/marketing/components/product-icon/style.scss
var product_icon_style = __webpack_require__(427);

// CONCATENATED MODULE: ./client/marketing/components/product-icon/index.js







function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = getPrototypeOf_default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = getPrototypeOf_default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return possibleConstructorReturn_default()(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/**
 * External dependencies
 */



/**
 * Internal dependencies
 */



var product_icon_ProductIcon = /*#__PURE__*/function (_Component) {
  inherits_default()(ProductIcon, _Component);

  var _super = _createSuper(ProductIcon);

  function ProductIcon() {
    classCallCheck_default()(this, ProductIcon);

    return _super.apply(this, arguments);
  }

  createClass_default()(ProductIcon, [{
    key: "render",
    value: function render() {
      return Object(external_this_wp_element_["createElement"])("img", {
        src: this.props.src,
        className: classnames_default()(this.props.className, 'woocommere-admin-marketing-product-icon'),
        alt: ""
      });
    }
  }]);

  return ProductIcon;
}(external_this_wp_element_["Component"]);

product_icon_ProductIcon.propTypes = {
  /**
   * Icon src.
   */
  src: prop_types_default.a.string.isRequired,

  /**
   * Additional classNames.
   */
  className: prop_types_default.a.string
};
/* harmony default export */ var product_icon = (product_icon_ProductIcon);
// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/slicedToArray.js
var slicedToArray = __webpack_require__(146);
var slicedToArray_default = /*#__PURE__*/__webpack_require__.n(slicedToArray);

// EXTERNAL MODULE: ./node_modules/react-transition-group/esm/TransitionGroup.js + 1 modules
var TransitionGroup = __webpack_require__(433);

// EXTERNAL MODULE: ./node_modules/react-transition-group/esm/CSSTransition.js + 5 modules
var CSSTransition = __webpack_require__(432);

// EXTERNAL MODULE: external "lodash"
var external_lodash_ = __webpack_require__(2);

// EXTERNAL MODULE: ./client/marketing/components/slider/style.scss
var slider_style = __webpack_require__(428);

// CONCATENATED MODULE: ./client/marketing/components/slider/index.js



/**
 * External dependencies
 */





/**
 * Internal dependencies
 */



var slider_Slider = function Slider(_ref) {
  var children = _ref.children,
      animationKey = _ref.animationKey,
      animate = _ref.animate;

  var _useState = Object(external_this_wp_element_["useState"])(null),
      _useState2 = slicedToArray_default()(_useState, 2),
      height = _useState2[0],
      updateHeight = _useState2[1];

  var container = Object(external_this_wp_element_["useRef"])();
  var containerClasses = classnames_default()('woocommerce-marketing-slider', animate && "animate-".concat(animate));
  var style = {};

  if (height) {
    style.height = height;
  } // timeout should be slightly longer than the CSS animation


  var timeout = 320;

  var updateSliderHeight = function updateSliderHeight() {
    var slide = container.current.querySelector('.woocommerce-marketing-slider__slide');
    updateHeight(slide.clientHeight);
  };

  var debouncedUpdateSliderHeight = Object(external_lodash_["debounce"])(updateSliderHeight, 50);
  Object(external_this_wp_element_["useEffect"])(function () {
    // Update the slider height on Resize
    window.addEventListener('resize', debouncedUpdateSliderHeight);
    return function () {
      window.removeEventListener('resize', debouncedUpdateSliderHeight);
    };
  }, []);
  /**
   * Fix slider height before a slide enters because slides are absolutely position
   */

  var onEnter = function onEnter() {
    var newSlide = container.current.querySelector('.slide-enter');
    updateHeight(newSlide.clientHeight);
  };

  return Object(external_this_wp_element_["createElement"])("div", {
    className: containerClasses,
    ref: container,
    style: style
  }, Object(external_this_wp_element_["createElement"])(TransitionGroup["a" /* default */], null, Object(external_this_wp_element_["createElement"])(CSSTransition["a" /* default */], {
    timeout: timeout,
    classNames: "slide",
    key: animationKey,
    onEnter: onEnter
  }, Object(external_this_wp_element_["createElement"])("div", {
    className: "woocommerce-marketing-slider__slide"
  }, children))));
};

slider_Slider.propTypes = {
  /**
   * A unique identifier for each slideable page.
   */
  animationKey: prop_types_default.a.any.isRequired,

  /**
   * null, 'left', 'right', to designate which direction to slide on a change.
   */
  animate: prop_types_default.a.oneOf([null, 'left', 'right'])
};
/* harmony default export */ var slider = (slider_Slider);
// CONCATENATED MODULE: ./client/marketing/components/index.js




/***/ }),

/***/ 18:
/***/ (function(module, exports) {

(function() { module.exports = this["wp"]["data"]; }());

/***/ }),

/***/ 19:
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
var unsupportedIterableToArray = __webpack_require__(49);

// CONCATENATED MODULE: ./node_modules/@babel/runtime/helpers/esm/nonIterableRest.js
function _nonIterableRest() {
  throw new TypeError("Invalid attempt to destructure non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method.");
}
// CONCATENATED MODULE: ./node_modules/@babel/runtime/helpers/esm/slicedToArray.js




function _slicedToArray(arr, i) {
  return _arrayWithHoles(arr) || _iterableToArrayLimit(arr, i) || Object(unsupportedIterableToArray["a" /* default */])(arr, i) || _nonIterableRest();
}

/***/ }),

/***/ 2:
/***/ (function(module, exports) {

(function() { module.exports = this["lodash"]; }());

/***/ }),

/***/ 208:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _use_media_query__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(88);
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

/***/ 209:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(32);


/**
 * WordPress dependencies
 */

var close = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__[/* SVG */ "b"], {
  xmlns: "http://www.w3.org/2000/svg",
  viewBox: "0 0 24 24"
}, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__[/* Path */ "a"], {
  d: "M13 11.8l6.1-6.3-1-1-6.1 6.2-6.1-6.2-1 1 6.1 6.3-6.5 6.7 1 1 6.5-6.6 6.5 6.6 1-1z"
}));
/* harmony default export */ __webpack_exports__["a"] = (close);
//# sourceMappingURL=close.js.map

/***/ }),

/***/ 211:
/***/ (function(module, exports, __webpack_require__) {

var arrayLikeToArray = __webpack_require__(152);

function _arrayWithoutHoles(arr) {
  if (Array.isArray(arr)) return arrayLikeToArray(arr);
}

module.exports = _arrayWithoutHoles;

/***/ }),

/***/ 212:
/***/ (function(module, exports) {

function _iterableToArray(iter) {
  if (typeof Symbol !== "undefined" && Symbol.iterator in Object(iter)) return Array.from(iter);
}

module.exports = _iterableToArray;

/***/ }),

/***/ 213:
/***/ (function(module, exports) {

function _nonIterableSpread() {
  throw new TypeError("Invalid attempt to spread non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method.");
}

module.exports = _nonIterableSpread;

/***/ }),

/***/ 22:
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
/* harmony import */ var _babel_runtime_helpers_typeof__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(35);
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

/***/ 26:
/***/ (function(module, exports) {

function _getPrototypeOf(o) {
  module.exports = _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf : function _getPrototypeOf(o) {
    return o.__proto__ || Object.getPrototypeOf(o);
  };
  return _getPrototypeOf(o);
}

module.exports = _getPrototypeOf;

/***/ }),

/***/ 263:
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),

/***/ 264:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "a", function() { return getInAppPurchaseUrl; });
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(17);
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_url__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(27);
/* harmony import */ var _wordpress_url__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_url__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(22);


function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0___default()(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

/**
 * External dependencies
 */

/**
 * WooCommerce dependencies
 */


/**
 * Returns an in-app-purchase URL.
 *
 * @param {string} url
 * @param {Object} queryArgs
 * @return {string} url with in-app-purchase query parameters
 */

var getInAppPurchaseUrl = function getInAppPurchaseUrl(url) {
  var queryArgs = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};
  var _window$location = window.location,
      pathname = _window$location.pathname,
      search = _window$location.search;
  var connectNonce = Object(_woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_2__[/* getSetting */ "g"])('connectNonce', '');
  queryArgs = _objectSpread({
    'wccom-site': Object(_woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_2__[/* getSetting */ "g"])('siteUrl'),
    // If the site is installed in a directory the directory must be included in the back param path.
    'wccom-back': pathname + search,
    'wccom-woo-version': Object(_woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_2__[/* getSetting */ "g"])('wcVersion'),
    'wccom-connect-nonce': connectNonce
  }, queryArgs);
  return Object(_wordpress_url__WEBPACK_IMPORTED_MODULE_1__["addQueryArgs"])(url, queryArgs);
};

/***/ }),

/***/ 265:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "a", function() { return Spinner; });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);

function Spinner() {
  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("span", {
    className: "components-spinner"
  });
}
//# sourceMappingURL=index.js.map

/***/ }),

/***/ 269:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* unused harmony export KnowledgeBase */
/* harmony import */ var _babel_runtime_helpers_slicedToArray__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(146);
/* harmony import */ var _babel_runtime_helpers_slicedToArray__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_slicedToArray__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(169);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(3);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(265);
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(8);
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(classnames__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(18);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var lib_tracks__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(63);
/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(1);
/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(prop_types__WEBPACK_IMPORTED_MODULE_8__);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(53);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_components__WEBPACK_IMPORTED_MODULE_9__);
/* harmony import */ var _style_scss__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(429);
/* harmony import */ var _style_scss__WEBPACK_IMPORTED_MODULE_10___default = /*#__PURE__*/__webpack_require__.n(_style_scss__WEBPACK_IMPORTED_MODULE_10__);
/* harmony import */ var _components__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(174);
/* harmony import */ var _data_constants__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(93);



/**
 * External dependencies
 */








/**
 * WooCommerce dependencies
 */


/**
 * Internal dependencies
 */





var KnowledgeBase = function KnowledgeBase(_ref) {
  var posts = _ref.posts,
      isLoading = _ref.isLoading,
      title = _ref.title,
      description = _ref.description,
      category = _ref.category;

  var _useState = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["useState"])(1),
      _useState2 = _babel_runtime_helpers_slicedToArray__WEBPACK_IMPORTED_MODULE_0___default()(_useState, 2),
      page = _useState2[0],
      updatePage = _useState2[1];

  var _useState3 = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["useState"])(null),
      _useState4 = _babel_runtime_helpers_slicedToArray__WEBPACK_IMPORTED_MODULE_0___default()(_useState3, 2),
      animate = _useState4[0],
      updateAnimate = _useState4[1];

  var onPaginationPageChange = function onPaginationPageChange(newPage) {
    var newAnimate;

    if (newPage > page) {
      newAnimate = 'left';
      Object(lib_tracks__WEBPACK_IMPORTED_MODULE_7__[/* recordEvent */ "b"])('marketing_knowledge_carousel', {
        direction: 'forward',
        page: newPage
      });
    } else {
      newAnimate = 'right';
      Object(lib_tracks__WEBPACK_IMPORTED_MODULE_7__[/* recordEvent */ "b"])('marketing_knowledge_carousel', {
        direction: 'back',
        page: newPage
      });
    }

    updatePage(newPage);
    updateAnimate(newAnimate);
  };

  var onPostClick = function onPostClick(post) {
    Object(lib_tracks__WEBPACK_IMPORTED_MODULE_7__[/* recordEvent */ "b"])('marketing_knowledge_article', {
      title: post.title
    });
  };
  /**
   * Get the 2 posts we need for the current page
   */


  var getCurrentSlide = function getCurrentSlide() {
    var currentPosts = posts.slice((page - 1) * 2, (page - 1) * 2 + 2);
    var pageClass = classnames__WEBPACK_IMPORTED_MODULE_5___default()('woocommerce-marketing-knowledgebase-card__page', {
      'page-with-single-post': currentPosts.length === 1
    });
    var displayPosts = currentPosts.map(function (post, index) {
      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])("a", {
        className: "woocommerce-marketing-knowledgebase-card__post",
        href: post.link,
        key: index,
        onClick: function onClick() {
          onPostClick(post);
        },
        target: "_blank",
        rel: "noopener noreferrer"
      }, post.image && Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])("div", {
        className: "woocommerce-marketing-knowledgebase-card__post-img"
      }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])("img", {
        src: post.image,
        alt: ""
      })), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])("div", {
        className: "woocommerce-marketing-knowledgebase-card__post-text"
      }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])("h3", null, post.title), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])("p", {
        className: "woocommerce-marketing-knowledgebase-card__post-meta"
      }, "By ", post.author_name, post.author_avatar && Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])("img", {
        src: post.author_avatar.replace('s=96', 's=32'),
        className: "woocommerce-gravatar",
        alt: "",
        width: "16",
        height: "16"
      }))));
    });
    return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])("div", {
      className: pageClass
    }, displayPosts);
  };

  var renderEmpty = function renderEmpty() {
    var emptyTitle = Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__["__"])('There was an error loading knowledge base posts. Please check again later.', 'woocommerce');

    return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_9__["EmptyContent"], {
      title: emptyTitle,
      illustrationWidth: 250,
      actionLabel: ""
    });
  };

  var renderPosts = function renderPosts() {
    return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])("div", {
      className: "woocommerce-marketing-knowledgebase-card__posts"
    }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])(_components__WEBPACK_IMPORTED_MODULE_11__[/* Slider */ "c"], {
      animationKey: page,
      animate: animate
    }, getCurrentSlide()), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_9__["Pagination"], {
      page: page,
      perPage: 2,
      total: posts.length,
      onPageChange: onPaginationPageChange,
      showPagePicker: false,
      showPerPagePicker: false,
      showPageArrowsLabel: false
    }));
  };

  var renderCardBody = function renderCardBody() {
    if (isLoading) {
      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__[/* default */ "a"], null);
    }

    return posts.length === 0 ? renderEmpty() : renderPosts();
  };

  var categoryClass = category ? "woocommerce-marketing-knowledgebase-card__category-".concat(category) : '';
  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_9__["Card"], {
    title: title,
    description: description,
    className: classnames__WEBPACK_IMPORTED_MODULE_5___default()('woocommerce-marketing-knowledgebase-card', categoryClass)
  }, renderCardBody());
};

KnowledgeBase.propTypes = {
  /**
   * Array of posts.
   */
  posts: prop_types__WEBPACK_IMPORTED_MODULE_8___default.a.arrayOf(prop_types__WEBPACK_IMPORTED_MODULE_8___default.a.object).isRequired,

  /**
   * Whether the card is loading.
   */
  isLoading: prop_types__WEBPACK_IMPORTED_MODULE_8___default.a.bool.isRequired,

  /**
   * Cart title.
   */
  title: prop_types__WEBPACK_IMPORTED_MODULE_8___default.a.string,

  /**
   * Card description.
   */
  description: prop_types__WEBPACK_IMPORTED_MODULE_8___default.a.string,

  /**
   * Category of extensions to display.
   */
  category: prop_types__WEBPACK_IMPORTED_MODULE_8___default.a.string
};
KnowledgeBase.defaultProps = {
  title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__["__"])('WooCommerce knowledge base', 'woocommerce'),
  description: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__["__"])('Learn the ins and outs of successful marketing from the experts at WooCommerce.', 'woocommerce')
};

/* harmony default export */ __webpack_exports__["a"] = (Object(_wordpress_compose__WEBPACK_IMPORTED_MODULE_2__[/* default */ "a"])(Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_6__["withSelect"])(function (select, props) {
  var _select = select(_data_constants__WEBPACK_IMPORTED_MODULE_12__[/* STORE_KEY */ "b"]),
      getBlogPosts = _select.getBlogPosts,
      isResolving = _select.isResolving;

  return {
    posts: getBlogPosts(props.category),
    isLoading: isResolving('getBlogPosts', [props.category])
  };
}), Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_6__["withDispatch"])(function (dispatch) {
  var _dispatch = dispatch('core/notices'),
      createNotice = _dispatch.createNotice;

  return {
    createNotice: createNotice
  };
}))(KnowledgeBase));

/***/ }),

/***/ 27:
/***/ (function(module, exports) {

(function() { module.exports = this["wp"]["url"]; }());

/***/ }),

/***/ 271:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";

// UNUSED EXPORTS: RecommendedExtensions

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/extends.js
var helpers_extends = __webpack_require__(80);
var extends_default = /*#__PURE__*/__webpack_require__.n(helpers_extends);

// EXTERNAL MODULE: external {"this":["wp","element"]}
var external_this_wp_element_ = __webpack_require__(0);

// EXTERNAL MODULE: external {"this":["wp","i18n"]}
var external_this_wp_i18n_ = __webpack_require__(3);

// EXTERNAL MODULE: ./node_modules/@wordpress/compose/build-module/higher-order/compose.js
var compose = __webpack_require__(169);

// EXTERNAL MODULE: ./node_modules/@wordpress/components/build-module/spinner/index.js
var spinner = __webpack_require__(265);

// EXTERNAL MODULE: ./node_modules/classnames/index.js
var classnames = __webpack_require__(8);
var classnames_default = /*#__PURE__*/__webpack_require__.n(classnames);

// EXTERNAL MODULE: external {"this":["wp","data"]}
var external_this_wp_data_ = __webpack_require__(18);

// EXTERNAL MODULE: ./node_modules/prop-types/index.js
var prop_types = __webpack_require__(1);
var prop_types_default = /*#__PURE__*/__webpack_require__.n(prop_types);

// EXTERNAL MODULE: external {"this":["wc","components"]}
var external_this_wc_components_ = __webpack_require__(53);

// EXTERNAL MODULE: ./client/marketing/components/recommended-extensions/style.scss
var style = __webpack_require__(263);

// EXTERNAL MODULE: ./client/marketing/components/index.js + 3 modules
var components = __webpack_require__(174);

// EXTERNAL MODULE: ./client/lib/tracks.js
var tracks = __webpack_require__(63);

// EXTERNAL MODULE: ./client/lib/in-app-purchase.js
var in_app_purchase = __webpack_require__(264);

// CONCATENATED MODULE: ./client/marketing/components/recommended-extensions/item.js


/**
 * External dependencies
 */

/**
 * Internal dependencies
 */






var item_RecommendedExtensionsItem = function RecommendedExtensionsItem(_ref) {
  var title = _ref.title,
      description = _ref.description,
      icon = _ref.icon,
      url = _ref.url;

  var onProductClick = function onProductClick() {
    Object(tracks["b" /* recordEvent */])('marketing_recommended_extension', {
      name: title
    });
  };

  var classNameBase = 'woocommerce-marketing-recommended-extensions-item';
  var connectURL = Object(in_app_purchase["a" /* getInAppPurchaseUrl */])(url);
  return Object(external_this_wp_element_["createElement"])("a", {
    href: connectURL,
    className: classNameBase,
    onClick: onProductClick
  }, Object(external_this_wp_element_["createElement"])(components["b" /* ProductIcon */], {
    src: icon
  }), Object(external_this_wp_element_["createElement"])("div", {
    className: "".concat(classNameBase, "__text")
  }, Object(external_this_wp_element_["createElement"])("h4", null, title), Object(external_this_wp_element_["createElement"])("p", null, description)));
};

item_RecommendedExtensionsItem.propTypes = {
  title: prop_types_default.a.string.isRequired,
  description: prop_types_default.a.string.isRequired,
  icon: prop_types_default.a.string.isRequired,
  url: prop_types_default.a.string.isRequired
};
/* harmony default export */ var item = (item_RecommendedExtensionsItem);
// EXTERNAL MODULE: ./client/marketing/data/constants.js
var constants = __webpack_require__(93);

// CONCATENATED MODULE: ./client/marketing/components/recommended-extensions/index.js



/**
 * External dependencies
 */







/**
 * WooCommerce dependencies
 */


/**
 * Internal dependencies
 */





var recommended_extensions_RecommendedExtensions = function RecommendedExtensions(_ref) {
  var extensions = _ref.extensions,
      isLoading = _ref.isLoading,
      title = _ref.title,
      description = _ref.description,
      category = _ref.category;

  if (extensions.length === 0 && !isLoading) {
    return null;
  }

  var categoryClass = category ? "woocommerce-marketing-recommended-extensions-card__category-".concat(category) : '';
  return Object(external_this_wp_element_["createElement"])(external_this_wc_components_["Card"], {
    title: title,
    description: description,
    className: classnames_default()('woocommerce-marketing-recommended-extensions-card', categoryClass)
  }, Object(external_this_wp_element_["createElement"])(external_this_wp_element_["Fragment"], null, isLoading ? Object(external_this_wp_element_["createElement"])(spinner["a" /* default */], null) : Object(external_this_wp_element_["createElement"])("div", {
    className: classnames_default()('woocommerce-marketing-recommended-extensions-card__items', "woocommerce-marketing-recommended-extensions-card__items--count-".concat(extensions.length))
  }, extensions.map(function (extension) {
    return Object(external_this_wp_element_["createElement"])(item, extends_default()({
      key: extension.product
    }, extension));
  }))));
};

recommended_extensions_RecommendedExtensions.propTypes = {
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
recommended_extensions_RecommendedExtensions.defaultProps = {
  title: Object(external_this_wp_i18n_["__"])('Recommended extensions', 'woocommerce'),
  description: Object(external_this_wp_i18n_["__"])('Great marketing requires the right tools. Take your marketing to the next level with our recommended marketing extensions.', 'woocommerce')
};

/* harmony default export */ var recommended_extensions = __webpack_exports__["a"] = (Object(compose["a" /* default */])(Object(external_this_wp_data_["withSelect"])(function (select, props) {
  var _select = select(constants["b" /* STORE_KEY */]),
      getRecommendedPlugins = _select.getRecommendedPlugins,
      isResolving = _select.isResolving;

  return {
    extensions: getRecommendedPlugins(props.category),
    isLoading: isResolving('getRecommendedPlugins', [props.category])
  };
}), Object(external_this_wp_data_["withDispatch"])(function (dispatch) {
  var _dispatch = dispatch('core/notices'),
      createNotice = _dispatch.createNotice;

  return {
    createNotice: createNotice
  };
}))(recommended_extensions_RecommendedExtensions));

/***/ }),

/***/ 29:
/***/ (function(module, exports) {

(function() { module.exports = this["wp"]["dataControls"]; }());

/***/ }),

/***/ 3:
/***/ (function(module, exports) {

(function() { module.exports = this["wp"]["i18n"]; }());

/***/ }),

/***/ 30:
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

/***/ 31:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";

// EXPORTS
__webpack_require__.d(__webpack_exports__, "e", function() { return /* binding */ TAB; });
__webpack_require__.d(__webpack_exports__, "b", function() { return /* binding */ ESCAPE; });
__webpack_require__.d(__webpack_exports__, "c", function() { return /* binding */ LEFT; });
__webpack_require__.d(__webpack_exports__, "f", function() { return /* binding */ UP; });
__webpack_require__.d(__webpack_exports__, "d", function() { return /* binding */ RIGHT; });
__webpack_require__.d(__webpack_exports__, "a", function() { return /* binding */ DOWN; });

// UNUSED EXPORTS: BACKSPACE, ENTER, SPACE, DELETE, F10, ALT, CTRL, COMMAND, SHIFT, ZERO, modifiers, rawShortcut, displayShortcutList, displayShortcut, shortcutAriaLabel, isKeyboardEvent

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/esm/defineProperty.js
var defineProperty = __webpack_require__(6);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/esm/toConsumableArray.js + 3 modules
var toConsumableArray = __webpack_require__(15);

// EXTERNAL MODULE: external "lodash"
var external_lodash_ = __webpack_require__(2);

// EXTERNAL MODULE: external {"this":["wp","i18n"]}
var external_this_wp_i18n_ = __webpack_require__(3);

// CONCATENATED MODULE: ./node_modules/@wordpress/components/node_modules/@wordpress/keycodes/build-module/platform.js
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
// CONCATENATED MODULE: ./node_modules/@wordpress/components/node_modules/@wordpress/keycodes/build-module/index.js



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
 * @typedef {Record<WPKeycodeModifier, (key:string)=>any>} WPKeycodeHandlerByModifier
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
 * Keycode for ZERO key.
 */

var ZERO = 48;
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
 * From a given KeyboardEvent, returns an array of active modifier constants for
 * the event.
 *
 * @param {KeyboardEvent} event Keyboard event.
 *
 * @return {Array<ALT|CTRL|COMMAND|SHIFT>} Active modifier constants.
 */

function getEventModifiers(event) {
  return [ALT, CTRL, COMMAND, SHIFT].filter(function (key) {
    return event["".concat(key, "Key")];
  });
}
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
    var eventMods = getEventModifiers(event);

    if (Object(external_lodash_["xor"])(mods, eventMods).length) {
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

/***/ 32:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* unused harmony export Circle */
/* unused harmony export G */
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "a", function() { return Path; });
/* unused harmony export Polygon */
/* unused harmony export Rect */
/* unused harmony export Defs */
/* unused harmony export RadialGradient */
/* unused harmony export LinearGradient */
/* unused harmony export Stop */
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "b", function() { return SVG; });
/* harmony import */ var _babel_runtime_helpers_esm_defineProperty__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(6);
/* harmony import */ var _babel_runtime_helpers_esm_objectWithoutProperties__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(14);
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(8);
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(classnames__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_3__);



function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { Object(_babel_runtime_helpers_esm_defineProperty__WEBPACK_IMPORTED_MODULE_0__[/* default */ "a"])(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */

 // Disable reason: JSDoc linter doesn't seem to parse the union (`&`) correctly.

/* eslint-disable jsdoc/valid-types */

/** @typedef {{isPressed?: boolean} & import('react').ComponentPropsWithoutRef<'svg'>} SVGProps */

/* eslint-enable jsdoc/valid-types */

/**
 * @param {import('react').ComponentPropsWithoutRef<'circle'>} props
 *
 * @return {JSX.Element} Circle component
 */

var Circle = function Circle(props) {
  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_3__["createElement"])('circle', props);
};
/**
 * @param {import('react').ComponentPropsWithoutRef<'g'>} props
 *
 * @return {JSX.Element} G component
 */

var G = function G(props) {
  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_3__["createElement"])('g', props);
};
/**
 * @param {import('react').ComponentPropsWithoutRef<'path'>} props
 *
 * @return {JSX.Element} Path component
 */

var Path = function Path(props) {
  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_3__["createElement"])('path', props);
};
/**
 * @param {import('react').ComponentPropsWithoutRef<'polygon'>} props
 *
 * @return {JSX.Element} Polygon component
 */

var Polygon = function Polygon(props) {
  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_3__["createElement"])('polygon', props);
};
/**
 * @param {import('react').ComponentPropsWithoutRef<'rect'>} props
 *
 * @return {JSX.Element} Rect component
 */

var Rect = function Rect(props) {
  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_3__["createElement"])('rect', props);
};
/**
 * @param {import('react').ComponentPropsWithoutRef<'defs'>} props
 *
 * @return {JSX.Element} Defs component
 */

var Defs = function Defs(props) {
  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_3__["createElement"])('defs', props);
};
/**
 * @param {import('react').ComponentPropsWithoutRef<'radialGradient'>} props
 *
 * @return {JSX.Element} RadialGradient component
 */

var RadialGradient = function RadialGradient(props) {
  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_3__["createElement"])('radialGradient', props);
};
/**
 * @param {import('react').ComponentPropsWithoutRef<'linearGradient'>} props
 *
 * @return {JSX.Element} LinearGradient component
 */

var LinearGradient = function LinearGradient(props) {
  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_3__["createElement"])('linearGradient', props);
};
/**
 * @param {import('react').ComponentPropsWithoutRef<'stop'>} props
 *
 * @return {JSX.Element} Stop component
 */

var Stop = function Stop(props) {
  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_3__["createElement"])('stop', props);
};
/**
 *
 * @param {SVGProps} props isPressed indicates whether the SVG should appear as pressed.
 *                         Other props will be passed through to svg component.
 *
 * @return {JSX.Element} Stop component
 */

var SVG = function SVG(_ref) {
  var className = _ref.className,
      isPressed = _ref.isPressed,
      props = Object(_babel_runtime_helpers_esm_objectWithoutProperties__WEBPACK_IMPORTED_MODULE_1__[/* default */ "a"])(_ref, ["className", "isPressed"]);

  var appliedProps = _objectSpread({}, props, {
    className: classnames__WEBPACK_IMPORTED_MODULE_2___default()(className, {
      'is-pressed': isPressed
    }) || undefined,
    role: 'img',
    'aria-hidden': true,
    focusable: false
  }); // Disable reason: We need to have a way to render HTML tag for web.
  // eslint-disable-next-line react/forbid-elements


  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_3__["createElement"])("svg", appliedProps);
};
//# sourceMappingURL=index.js.map

/***/ }),

/***/ 35:
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

/***/ 37:
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

/***/ 38:
/***/ (function(module, exports) {

function _classCallCheck(instance, Constructor) {
  if (!(instance instanceof Constructor)) {
    throw new TypeError("Cannot call a class as a function");
  }
}

module.exports = _classCallCheck;

/***/ }),

/***/ 39:
/***/ (function(module, exports, __webpack_require__) {

var setPrototypeOf = __webpack_require__(147);

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

/***/ 41:
/***/ (function(module, exports, __webpack_require__) {

var arrayWithoutHoles = __webpack_require__(211);

var iterableToArray = __webpack_require__(212);

var unsupportedIterableToArray = __webpack_require__(172);

var nonIterableSpread = __webpack_require__(213);

function _toConsumableArray(arr) {
  return arrayWithoutHoles(arr) || iterableToArray(arr) || unsupportedIterableToArray(arr) || nonIterableSpread();
}

module.exports = _toConsumableArray;

/***/ }),

/***/ 419:
/***/ (function(module, exports) {

function _arrayWithHoles(arr) {
  if (Array.isArray(arr)) return arr;
}

module.exports = _arrayWithHoles;

/***/ }),

/***/ 42:
/***/ (function(module, exports, __webpack_require__) {

var _typeof = __webpack_require__(35);

var assertThisInitialized = __webpack_require__(62);

function _possibleConstructorReturn(self, call) {
  if (call && (_typeof(call) === "object" || typeof call === "function")) {
    return call;
  }

  return assertThisInitialized(self);
}

module.exports = _possibleConstructorReturn;

/***/ }),

/***/ 420:
/***/ (function(module, exports) {

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

module.exports = _iterableToArrayLimit;

/***/ }),

/***/ 421:
/***/ (function(module, exports) {

function _nonIterableRest() {
  throw new TypeError("Invalid attempt to destructure non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method.");
}

module.exports = _nonIterableRest;

/***/ }),

/***/ 426:
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),

/***/ 427:
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),

/***/ 428:
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),

/***/ 429:
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),

/***/ 430:
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
__webpack_require__.d(actions_namespaceObject, "activateInstalledPlugin", function() { return activateInstalledPlugin; });
__webpack_require__.d(actions_namespaceObject, "loadInstalledPluginsAfterActivation", function() { return loadInstalledPluginsAfterActivation; });

// NAMESPACE OBJECT: ./client/marketing/data/selectors.js
var selectors_namespaceObject = {};
__webpack_require__.r(selectors_namespaceObject);
__webpack_require__.d(selectors_namespaceObject, "getInstalledPlugins", function() { return getInstalledPlugins; });
__webpack_require__.d(selectors_namespaceObject, "getActivatingPlugins", function() { return getActivatingPlugins; });
__webpack_require__.d(selectors_namespaceObject, "getRecommendedPlugins", function() { return getRecommendedPlugins; });
__webpack_require__.d(selectors_namespaceObject, "getBlogPosts", function() { return getBlogPosts; });

// NAMESPACE OBJECT: ./client/marketing/data/resolvers.js
var resolvers_namespaceObject = {};
__webpack_require__.r(resolvers_namespaceObject);
__webpack_require__.d(resolvers_namespaceObject, "getRecommendedPlugins", function() { return resolvers_getRecommendedPlugins; });
__webpack_require__.d(resolvers_namespaceObject, "getBlogPosts", function() { return resolvers_getBlogPosts; });

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/toConsumableArray.js
var toConsumableArray = __webpack_require__(41);
var toConsumableArray_default = /*#__PURE__*/__webpack_require__.n(toConsumableArray);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/defineProperty.js
var defineProperty = __webpack_require__(17);
var defineProperty_default = /*#__PURE__*/__webpack_require__.n(defineProperty);

// EXTERNAL MODULE: external {"this":["wp","dataControls"]}
var external_this_wp_dataControls_ = __webpack_require__(29);

// EXTERNAL MODULE: external {"this":["wp","data"]}
var external_this_wp_data_ = __webpack_require__(18);

// EXTERNAL MODULE: ./client/settings/index.js
var settings = __webpack_require__(22);

// EXTERNAL MODULE: external "lodash"
var external_lodash_ = __webpack_require__(2);

// EXTERNAL MODULE: ./client/marketing/data/constants.js
var constants = __webpack_require__(93);

// EXTERNAL MODULE: external {"this":["wp","i18n"]}
var external_this_wp_i18n_ = __webpack_require__(3);

// CONCATENATED MODULE: ./client/marketing/data/actions.js
var _marked = /*#__PURE__*/regeneratorRuntime.mark(activateInstalledPlugin),
    _marked2 = /*#__PURE__*/regeneratorRuntime.mark(loadInstalledPluginsAfterActivation);

/**
 * External dependencies
 */


/**
 * Internal dependencies
 */



function receiveInstalledPlugins(plugins) {
  return {
    type: 'SET_INSTALLED_PLUGINS',
    plugins: plugins
  };
}
function receiveActivatingPlugin(pluginSlug) {
  return {
    type: 'SET_ACTIVATING_PLUGIN',
    pluginSlug: pluginSlug
  };
}
function removeActivatingPlugin(pluginSlug) {
  return {
    type: 'REMOVE_ACTIVATING_PLUGIN',
    pluginSlug: pluginSlug
  };
}
function receiveRecommendedPlugins(plugins, category) {
  return {
    type: 'SET_RECOMMENDED_PLUGINS',
    data: {
      plugins: plugins,
      category: category
    }
  };
}
function receiveBlogPosts(posts, category) {
  return {
    type: 'SET_BLOG_POSTS',
    data: {
      posts: posts,
      category: category
    }
  };
}
function handleFetchError(error, message) {
  var _dispatch = Object(external_this_wp_data_["dispatch"])('core/notices'),
      createNotice = _dispatch.createNotice;

  createNotice('error', message); // eslint-disable-next-line no-console

  console.log(error);
}
function activateInstalledPlugin(pluginSlug) {
  var _dispatch2, createNotice, response;

  return regeneratorRuntime.wrap(function activateInstalledPlugin$(_context) {
    while (1) {
      switch (_context.prev = _context.next) {
        case 0:
          _dispatch2 = Object(external_this_wp_data_["dispatch"])('core/notices'), createNotice = _dispatch2.createNotice;
          _context.next = 3;
          return receiveActivatingPlugin(pluginSlug);

        case 3:
          _context.prev = 3;
          _context.next = 6;
          return Object(external_this_wp_dataControls_["apiFetch"])({
            path: constants["a" /* API_NAMESPACE */] + '/overview/activate-plugin',
            method: 'POST',
            data: {
              plugin: pluginSlug
            }
          });

        case 6:
          response = _context.sent;

          if (!response) {
            _context.next = 14;
            break;
          }

          _context.next = 10;
          return createNotice('success', Object(external_this_wp_i18n_["__"])('The extension has been successfully activated.', 'woocommerce'));

        case 10:
          _context.next = 12;
          return loadInstalledPluginsAfterActivation(pluginSlug);

        case 12:
          _context.next = 15;
          break;

        case 14:
          throw new Error();

        case 15:
          _context.next = 23;
          break;

        case 17:
          _context.prev = 17;
          _context.t0 = _context["catch"](3);
          _context.next = 21;
          return handleFetchError(_context.t0, Object(external_this_wp_i18n_["__"])('There was an error trying to activate the extension.', 'woocommerce'));

        case 21:
          _context.next = 23;
          return removeActivatingPlugin(pluginSlug);

        case 23:
          return _context.abrupt("return", true);

        case 24:
        case "end":
          return _context.stop();
      }
    }
  }, _marked, null, [[3, 17]]);
}
function loadInstalledPluginsAfterActivation(activatedPluginSlug) {
  var response;
  return regeneratorRuntime.wrap(function loadInstalledPluginsAfterActivation$(_context2) {
    while (1) {
      switch (_context2.prev = _context2.next) {
        case 0:
          _context2.prev = 0;
          _context2.next = 3;
          return Object(external_this_wp_dataControls_["apiFetch"])({
            path: "".concat(constants["a" /* API_NAMESPACE */], "/overview/installed-plugins")
          });

        case 3:
          response = _context2.sent;

          if (!response) {
            _context2.next = 11;
            break;
          }

          _context2.next = 7;
          return receiveInstalledPlugins(response);

        case 7:
          _context2.next = 9;
          return removeActivatingPlugin(activatedPluginSlug);

        case 9:
          _context2.next = 12;
          break;

        case 11:
          throw new Error();

        case 12:
          _context2.next = 18;
          break;

        case 14:
          _context2.prev = 14;
          _context2.t0 = _context2["catch"](0);
          _context2.next = 18;
          return handleFetchError(_context2.t0, Object(external_this_wp_i18n_["__"])('There was an error loading installed extensions.', 'woocommerce'));

        case 18:
        case "end":
          return _context2.stop();
      }
    }
  }, _marked2, null, [[0, 14]]);
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
// CONCATENATED MODULE: ./client/marketing/data/resolvers.js
var resolvers_marked = /*#__PURE__*/regeneratorRuntime.mark(resolvers_getRecommendedPlugins),
    resolvers_marked2 = /*#__PURE__*/regeneratorRuntime.mark(resolvers_getBlogPosts);

/**
 * External dependencies
 */


/**
 * Internal dependencies
 */



function resolvers_getRecommendedPlugins(category) {
  var categoryParam, response;
  return regeneratorRuntime.wrap(function getRecommendedPlugins$(_context) {
    while (1) {
      switch (_context.prev = _context.next) {
        case 0:
          _context.prev = 0;
          _context.next = 3;
          return category ? "&category=".concat(category) : '';

        case 3:
          categoryParam = _context.sent;
          _context.next = 6;
          return Object(external_this_wp_dataControls_["apiFetch"])({
            path: "".concat(constants["a" /* API_NAMESPACE */], "/recommended?per_page=6").concat(categoryParam)
          });

        case 6:
          response = _context.sent;

          if (!response) {
            _context.next = 12;
            break;
          }

          _context.next = 10;
          return receiveRecommendedPlugins(response, category);

        case 10:
          _context.next = 13;
          break;

        case 12:
          throw new Error();

        case 13:
          _context.next = 19;
          break;

        case 15:
          _context.prev = 15;
          _context.t0 = _context["catch"](0);
          _context.next = 19;
          return handleFetchError(_context.t0, Object(external_this_wp_i18n_["__"])('There was an error loading recommended extensions.', 'woocommerce'));

        case 19:
        case "end":
          return _context.stop();
      }
    }
  }, resolvers_marked, null, [[0, 15]]);
}
function resolvers_getBlogPosts(category) {
  var categoryParam, response;
  return regeneratorRuntime.wrap(function getBlogPosts$(_context2) {
    while (1) {
      switch (_context2.prev = _context2.next) {
        case 0:
          _context2.prev = 0;
          _context2.next = 3;
          return category ? "?category=".concat(category) : '';

        case 3:
          categoryParam = _context2.sent;
          _context2.next = 6;
          return Object(external_this_wp_dataControls_["apiFetch"])({
            path: "".concat(constants["a" /* API_NAMESPACE */], "/knowledge-base").concat(categoryParam),
            method: 'GET'
          });

        case 6:
          response = _context2.sent;

          if (!response) {
            _context2.next = 12;
            break;
          }

          _context2.next = 10;
          return receiveBlogPosts(response, category);

        case 10:
          _context2.next = 13;
          break;

        case 12:
          throw new Error();

        case 13:
          _context2.next = 19;
          break;

        case 15:
          _context2.prev = 15;
          _context2.t0 = _context2["catch"](0);
          _context2.next = 19;
          return handleFetchError(_context2.t0, Object(external_this_wp_i18n_["__"])('There was an error loading knowledge base posts.', 'woocommerce'));

        case 19:
        case "end":
          return _context2.stop();
      }
    }
  }, resolvers_marked2, null, [[0, 15]]);
}
// CONCATENATED MODULE: ./client/marketing/data/index.js



function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { defineProperty_default()(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

/**
 * External dependencies
 */




/**
 * Internal dependencies
 */






var _getSetting = Object(settings["g" /* getSetting */])('marketing', {}),
    installedExtensions = _getSetting.installedExtensions;

var DEFAULT_STATE = {
  installedPlugins: installedExtensions,
  activatingPlugins: [],
  recommendedPlugins: {},
  blogPosts: {}
};
Object(external_this_wp_data_["registerStore"])(constants["b" /* STORE_KEY */], {
  actions: actions_namespaceObject,
  selectors: selectors_namespaceObject,
  resolvers: resolvers_namespaceObject,
  controls: external_this_wp_dataControls_["controls"],
  reducer: function reducer() {
    var state = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : DEFAULT_STATE;
    var action = arguments.length > 1 ? arguments[1] : undefined;

    switch (action.type) {
      case 'SET_INSTALLED_PLUGINS':
        return _objectSpread(_objectSpread({}, state), {}, {
          installedPlugins: action.plugins
        });

      case 'SET_ACTIVATING_PLUGIN':
        return _objectSpread(_objectSpread({}, state), {}, {
          activatingPlugins: [].concat(toConsumableArray_default()(state.activatingPlugins), [action.pluginSlug])
        });

      case 'REMOVE_ACTIVATING_PLUGIN':
        return _objectSpread(_objectSpread({}, state), {}, {
          activatingPlugins: Object(external_lodash_["without"])(state.activatingPlugins, action.pluginSlug)
        });

      case 'SET_RECOMMENDED_PLUGINS':
        return _objectSpread(_objectSpread({}, state), {}, {
          recommendedPlugins: _objectSpread(_objectSpread({}, state.recommendedPlugins), {}, defineProperty_default()({}, action.data.category, action.data.plugins))
        });

      case 'SET_BLOG_POSTS':
        return _objectSpread(_objectSpread({}, state), {}, {
          blogPosts: _objectSpread(_objectSpread({}, state.blogPosts), {}, defineProperty_default()({}, action.data.category, action.data.posts))
        });
    }

    return state;
  }
});

/***/ }),

/***/ 432:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/esm/extends.js
var esm_extends = __webpack_require__(12);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/esm/objectWithoutPropertiesLoose.js
var objectWithoutPropertiesLoose = __webpack_require__(46);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/esm/inheritsLoose.js
var inheritsLoose = __webpack_require__(60);

// EXTERNAL MODULE: ./node_modules/prop-types/index.js
var prop_types = __webpack_require__(1);

// CONCATENATED MODULE: ./node_modules/dom-helpers/esm/hasClass.js
function hasClass(element, className) {
  if (element.classList) return !!className && element.classList.contains(className);
  return (" " + (element.className.baseVal || element.className) + " ").indexOf(" " + className + " ") !== -1;
}
// CONCATENATED MODULE: ./node_modules/dom-helpers/esm/addClass.js

function addClass_addClass(element, className) {
  if (element.classList) element.classList.add(className);else if (!hasClass(element, className)) if (typeof element.className === 'string') element.className = element.className + " " + className;else element.setAttribute('class', (element.className && element.className.baseVal || '') + " " + className);
}
// CONCATENATED MODULE: ./node_modules/dom-helpers/esm/removeClass.js
function replaceClassName(origClass, classToRemove) {
  return origClass.replace(new RegExp("(^|\\s)" + classToRemove + "(?:\\s|$)", 'g'), '$1').replace(/\s+/g, ' ').replace(/^\s*|\s*$/g, '');
}

function removeClass_removeClass(element, className) {
  if (element.classList) {
    element.classList.remove(className);
  } else if (typeof element.className === 'string') {
    ;
    element.className = replaceClassName(element.className, className);
  } else {
    element.setAttribute('class', replaceClassName(element.className && element.className.baseVal || '', className));
  }
}
// EXTERNAL MODULE: external "React"
var external_React_ = __webpack_require__(13);
var external_React_default = /*#__PURE__*/__webpack_require__.n(external_React_);

// EXTERNAL MODULE: external "ReactDOM"
var external_ReactDOM_ = __webpack_require__(78);
var external_ReactDOM_default = /*#__PURE__*/__webpack_require__.n(external_ReactDOM_);

// CONCATENATED MODULE: ./node_modules/react-transition-group/esm/config.js
/* harmony default export */ var config = ({
  disabled: false
});
// EXTERNAL MODULE: ./node_modules/react-transition-group/esm/TransitionGroupContext.js
var TransitionGroupContext = __webpack_require__(74);

// CONCATENATED MODULE: ./node_modules/react-transition-group/esm/Transition.js








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

var Transition_Transition =
/*#__PURE__*/
function (_React$Component) {
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
  }; // getSnapshotBeforeUpdate(prevProps) {
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
      var node = external_ReactDOM_default.a.findDOMNode(this);

      if (nextStatus === ENTERING) {
        this.performEnter(node, mounting);
      } else {
        this.performExit(node);
      }
    } else if (this.props.unmountOnExit && this.state.status === EXITED) {
      this.setState({
        status: UNMOUNTED
      });
    }
  };

  _proto.performEnter = function performEnter(node, mounting) {
    var _this2 = this;

    var enter = this.props.enter;
    var appearing = this.context ? this.context.isMounting : mounting;
    var timeouts = this.getTimeouts();
    var enterTimeout = appearing ? timeouts.appear : timeouts.enter; // no enter animation skip right to ENTERED
    // if we are mounting and running this it means appear _must_ be set

    if (!mounting && !enter || config.disabled) {
      this.safeSetState({
        status: ENTERED
      }, function () {
        _this2.props.onEntered(node);
      });
      return;
    }

    this.props.onEnter(node, appearing);
    this.safeSetState({
      status: ENTERING
    }, function () {
      _this2.props.onEntering(node, appearing);

      _this2.onTransitionEnd(node, enterTimeout, function () {
        _this2.safeSetState({
          status: ENTERED
        }, function () {
          _this2.props.onEntered(node, appearing);
        });
      });
    });
  };

  _proto.performExit = function performExit(node) {
    var _this3 = this;

    var exit = this.props.exit;
    var timeouts = this.getTimeouts(); // no exit animation skip right to EXITED

    if (!exit || config.disabled) {
      this.safeSetState({
        status: EXITED
      }, function () {
        _this3.props.onExited(node);
      });
      return;
    }

    this.props.onExit(node);
    this.safeSetState({
      status: EXITING
    }, function () {
      _this3.props.onExiting(node);

      _this3.onTransitionEnd(node, timeouts.exit, function () {
        _this3.safeSetState({
          status: EXITED
        }, function () {
          _this3.props.onExited(node);
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

  _proto.onTransitionEnd = function onTransitionEnd(node, timeout, handler) {
    this.setNextCallback(handler);
    var doesNotHaveTimeoutOrListener = timeout == null && !this.props.addEndListener;

    if (!node || doesNotHaveTimeoutOrListener) {
      setTimeout(this.nextCallback, 0);
      return;
    }

    if (this.props.addEndListener) {
      this.props.addEndListener(node, this.nextCallback);
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
        childProps = Object(objectWithoutPropertiesLoose["a" /* default */])(_this$props, ["children"]); // filter props for Transtition


    delete childProps.in;
    delete childProps.mountOnEnter;
    delete childProps.unmountOnExit;
    delete childProps.appear;
    delete childProps.enter;
    delete childProps.exit;
    delete childProps.timeout;
    delete childProps.addEndListener;
    delete childProps.onEnter;
    delete childProps.onEntering;
    delete childProps.onEntered;
    delete childProps.onExit;
    delete childProps.onExiting;
    delete childProps.onExited;

    if (typeof children === 'function') {
      // allows for nested Transitions
      return external_React_default.a.createElement(TransitionGroupContext["a" /* default */].Provider, {
        value: null
      }, children(status, childProps));
    }

    var child = external_React_default.a.Children.only(children);
    return (// allows for nested Transitions
      external_React_default.a.createElement(TransitionGroupContext["a" /* default */].Provider, {
        value: null
      }, external_React_default.a.cloneElement(child, childProps))
    );
  };

  return Transition;
}(external_React_default.a.Component);

Transition_Transition.contextType = TransitionGroupContext["a" /* default */];
Transition_Transition.propTypes =  false ? undefined : {};

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
Transition_Transition.UNMOUNTED = 0;
Transition_Transition.EXITED = 1;
Transition_Transition.ENTERING = 2;
Transition_Transition.ENTERED = 3;
Transition_Transition.EXITING = 4;
/* harmony default export */ var esm_Transition = (Transition_Transition);
// CONCATENATED MODULE: ./node_modules/react-transition-group/esm/CSSTransition.js










var _addClass = function addClass(node, classes) {
  return node && classes && classes.split(' ').forEach(function (c) {
    return addClass_addClass(node, c);
  });
};

var CSSTransition_removeClass = function removeClass(node, classes) {
  return node && classes && classes.split(' ').forEach(function (c) {
    return removeClass_removeClass(node, c);
  });
};
/**
 * A transition component inspired by the excellent
 * [ng-animate](http://www.nganimate.org/) library, you should use it if you're
 * using CSS transitions or animations. It's built upon the
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
 * `*-active` classes represent which styles you want to animate **to**.
 *
 * **Note**: If you're using the
 * [`appear`](http://reactcommunity.org/react-transition-group/transition#Transition-prop-appear)
 * prop, make sure to define styles for `.appear-*` classes as well.
 */


var CSSTransition_CSSTransition =
/*#__PURE__*/
function (_React$Component) {
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

    _this.onEnter = function (node, appearing) {
      _this.removeClasses(node, 'exit');

      _this.addClass(node, appearing ? 'appear' : 'enter', 'base');

      if (_this.props.onEnter) {
        _this.props.onEnter(node, appearing);
      }
    };

    _this.onEntering = function (node, appearing) {
      var type = appearing ? 'appear' : 'enter';

      _this.addClass(node, type, 'active');

      if (_this.props.onEntering) {
        _this.props.onEntering(node, appearing);
      }
    };

    _this.onEntered = function (node, appearing) {
      var type = appearing ? 'appear' : 'enter';

      _this.removeClasses(node, type);

      _this.addClass(node, type, 'done');

      if (_this.props.onEntered) {
        _this.props.onEntered(node, appearing);
      }
    };

    _this.onExit = function (node) {
      _this.removeClasses(node, 'appear');

      _this.removeClasses(node, 'enter');

      _this.addClass(node, 'exit', 'base');

      if (_this.props.onExit) {
        _this.props.onExit(node);
      }
    };

    _this.onExiting = function (node) {
      _this.addClass(node, 'exit', 'active');

      if (_this.props.onExiting) {
        _this.props.onExiting(node);
      }
    };

    _this.onExited = function (node) {
      _this.removeClasses(node, 'exit');

      _this.addClass(node, 'exit', 'done');

      if (_this.props.onExited) {
        _this.props.onExited(node);
      }
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

    if (type === 'appear' && phase === 'done') {
      className += " " + this.getClassNames('enter').doneClassName;
    } // This is for to force a repaint,
    // which is necessary in order to transition styles when adding a class name.


    if (phase === 'active') {
      /* eslint-disable no-unused-expressions */
      node && node.scrollTop;
    }

    this.appliedClasses[type][phase] = className;

    _addClass(node, className);
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

    return external_React_default.a.createElement(esm_Transition, Object(esm_extends["a" /* default */])({}, props, {
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

/***/ 433:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/esm/objectWithoutPropertiesLoose.js
var objectWithoutPropertiesLoose = __webpack_require__(46);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/esm/extends.js
var esm_extends = __webpack_require__(12);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/esm/inheritsLoose.js
var inheritsLoose = __webpack_require__(60);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/esm/assertThisInitialized.js
var assertThisInitialized = __webpack_require__(5);

// EXTERNAL MODULE: ./node_modules/prop-types/index.js
var prop_types = __webpack_require__(1);

// EXTERNAL MODULE: external "React"
var external_React_ = __webpack_require__(13);
var external_React_default = /*#__PURE__*/__webpack_require__.n(external_React_);

// EXTERNAL MODULE: ./node_modules/react-transition-group/esm/TransitionGroupContext.js
var TransitionGroupContext = __webpack_require__(74);

// CONCATENATED MODULE: ./node_modules/react-transition-group/esm/utils/ChildMapping.js

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
    var hasPrev = key in prevChildMapping;
    var hasNext = key in nextChildMapping;
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
// CONCATENATED MODULE: ./node_modules/react-transition-group/esm/TransitionGroup.js









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

};

var TransitionGroup_TransitionGroup =
/*#__PURE__*/
function (_React$Component) {
  Object(inheritsLoose["a" /* default */])(TransitionGroup, _React$Component);

  function TransitionGroup(props, context) {
    var _this;

    _this = _React$Component.call(this, props, context) || this;

    var handleExited = _this.handleExited.bind(Object(assertThisInitialized["a" /* default */])(Object(assertThisInitialized["a" /* default */])(_this))); // Initial children should all be entering, dependent on appear


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
  };

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
      return external_React_default.a.createElement(TransitionGroupContext["a" /* default */].Provider, {
        value: contextValue
      }, children);
    }

    return external_React_default.a.createElement(TransitionGroupContext["a" /* default */].Provider, {
      value: contextValue
    }, external_React_default.a.createElement(Component, props, children));
  };

  return TransitionGroup;
}(external_React_default.a.Component);

TransitionGroup_TransitionGroup.propTypes =  false ? undefined : {};
TransitionGroup_TransitionGroup.defaultProps = defaultProps;
/* harmony default export */ var esm_TransitionGroup = __webpack_exports__["a"] = (TransitionGroup_TransitionGroup);

/***/ }),

/***/ 45:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";

// EXPORTS
__webpack_require__.d(__webpack_exports__, "a", function() { return /* binding */ build_module_focus; });

// UNUSED EXPORTS: isHorizontalEdge, isVerticalEdge, getRectangleFromRange, computeCaretRect, placeCaretAtHorizontalEdge, placeCaretAtVerticalEdge, isTextField, isNumberInput, documentHasSelection, isEntirelySelected, getScrollContainer, getOffsetParent, replace, remove, insertAfter, unwrap, replaceTag, wrap, __unstableStripHTML

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

/***/ 46:
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

/***/ 47:
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

/***/ 48:
/***/ (function(module, exports) {

(function() { module.exports = this["wp"]["hooks"]; }());

/***/ }),

/***/ 49:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "a", function() { return _unsupportedIterableToArray; });
/* harmony import */ var _arrayLikeToArray__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(30);

function _unsupportedIterableToArray(o, minLen) {
  if (!o) return;
  if (typeof o === "string") return Object(_arrayLikeToArray__WEBPACK_IMPORTED_MODULE_0__[/* default */ "a"])(o, minLen);
  var n = Object.prototype.toString.call(o).slice(8, -1);
  if (n === "Object" && o.constructor) n = o.constructor.name;
  if (n === "Map" || n === "Set") return Array.from(o);
  if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return Object(_arrayLikeToArray__WEBPACK_IMPORTED_MODULE_0__[/* default */ "a"])(o, minLen);
}

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

/***/ 53:
/***/ (function(module, exports) {

(function() { module.exports = this["wc"]["components"]; }());

/***/ }),

/***/ 54:
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

/***/ 55:
/***/ (function(module, exports) {

function _interopRequireDefault(obj) {
  return obj && obj.__esModule ? obj : {
    "default": obj
  };
}

module.exports = _interopRequireDefault;

/***/ }),

/***/ 59:
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

/***/ 6:
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

/***/ 60:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "a", function() { return _inheritsLoose; });
function _inheritsLoose(subClass, superClass) {
  subClass.prototype = Object.create(superClass.prototype);
  subClass.prototype.constructor = subClass;
  subClass.__proto__ = superClass;
}

/***/ }),

/***/ 62:
/***/ (function(module, exports) {

function _assertThisInitialized(self) {
  if (self === void 0) {
    throw new ReferenceError("this hasn't been initialised - super() hasn't been called");
  }

  return self;
}

module.exports = _assertThisInitialized;

/***/ }),

/***/ 63:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "b", function() { return recordEvent; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "a", function() { return queueRecordEvent; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "c", function() { return recordPageView; });
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(17);
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _babel_runtime_helpers_typeof__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(35);
/* harmony import */ var _babel_runtime_helpers_typeof__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_typeof__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var debug__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(87);
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

/***/ 65:
/***/ (function(module, exports, __webpack_require__) {

"use strict";


/**
 * Internal dependencies;
 */
var isShallowEqualObjects = __webpack_require__( 117 );
var isShallowEqualArrays = __webpack_require__( 118 );

var isArray = Array.isArray;

/**
 * @typedef {Record<string, any>} ComparableObject
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

/***/ 67:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* unused harmony export Button */
/* harmony import */ var _babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(12);
/* harmony import */ var _babel_runtime_helpers_esm_objectWithoutProperties__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(14);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(8);
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(classnames__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(2);
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _wordpress_deprecated__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(59);
/* harmony import */ var _tooltip__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(97);
/* harmony import */ var _icon__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(96);




function _createForOfIteratorHelper(o) { if (typeof Symbol === "undefined" || o[Symbol.iterator] == null) { if (Array.isArray(o) || (o = _unsupportedIterableToArray(o))) { var i = 0; var F = function F() {}; return { s: F, n: function n() { if (i >= o.length) return { done: true }; return { done: false, value: o[i++] }; }, e: function e(_e) { throw _e; }, f: F }; } throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); } var it, normalCompletion = true, didErr = false, err; return { s: function s() { it = o[Symbol.iterator](); }, n: function n() { var step = it.next(); normalCompletion = step.done; return step; }, e: function e(_e2) { didErr = true; err = _e2; }, f: function f() { try { if (!normalCompletion && it.return != null) it.return(); } finally { if (didErr) throw err; } } }; }

function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(n); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }

function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) { arr2[i] = arr[i]; } return arr2; }

/**
 * External dependencies
 */


/**
 * WordPress dependencies
 */



/**
 * Internal dependencies
 */



var disabledEventsOnDisabledButton = ['onMouseDown', 'onClick'];
function Button(props, ref) {
  var href = props.href,
      target = props.target,
      isPrimary = props.isPrimary,
      isLarge = props.isLarge,
      isSmall = props.isSmall,
      isTertiary = props.isTertiary,
      isPressed = props.isPressed,
      isBusy = props.isBusy,
      isDefault = props.isDefault,
      isSecondary = props.isSecondary,
      isLink = props.isLink,
      isDestructive = props.isDestructive,
      className = props.className,
      disabled = props.disabled,
      icon = props.icon,
      iconSize = props.iconSize,
      showTooltip = props.showTooltip,
      tooltipPosition = props.tooltipPosition,
      shortcut = props.shortcut,
      label = props.label,
      children = props.children,
      isFocusable = props.__experimentalIsFocusable,
      additionalProps = Object(_babel_runtime_helpers_esm_objectWithoutProperties__WEBPACK_IMPORTED_MODULE_1__[/* default */ "a"])(props, ["href", "target", "isPrimary", "isLarge", "isSmall", "isTertiary", "isPressed", "isBusy", "isDefault", "isSecondary", "isLink", "isDestructive", "className", "disabled", "icon", "iconSize", "showTooltip", "tooltipPosition", "shortcut", "label", "children", "__experimentalIsFocusable"]);

  if (isDefault) {
    Object(_wordpress_deprecated__WEBPACK_IMPORTED_MODULE_5__[/* default */ "a"])('Button isDefault prop', {
      alternative: 'isSecondary'
    });
  }

  var classes = classnames__WEBPACK_IMPORTED_MODULE_3___default()('components-button', className, {
    'is-secondary': isDefault || isSecondary,
    'is-primary': isPrimary,
    'is-large': isLarge,
    'is-small': isSmall,
    'is-tertiary': isTertiary,
    'is-pressed': isPressed,
    'is-busy': isBusy,
    'is-link': isLink,
    'is-destructive': isDestructive,
    'has-text': !!icon && !!children,
    'has-icon': !!icon
  });
  var trulyDisabled = disabled && !isFocusable;
  var Tag = href !== undefined && !trulyDisabled ? 'a' : 'button';
  var tagProps = Tag === 'a' ? {
    href: href,
    target: target
  } : {
    type: 'button',
    disabled: trulyDisabled,
    'aria-pressed': isPressed
  };

  if (disabled && isFocusable) {
    // In this case, the button will be disabled, but still focusable and
    // perceivable by screen reader users.
    tagProps['aria-disabled'] = true;

    var _iterator = _createForOfIteratorHelper(disabledEventsOnDisabledButton),
        _step;

    try {
      for (_iterator.s(); !(_step = _iterator.n()).done;) {
        var disabledEvent = _step.value;

        additionalProps[disabledEvent] = function (event) {
          event.stopPropagation();
          event.preventDefault();
        };
      }
    } catch (err) {
      _iterator.e(err);
    } finally {
      _iterator.f();
    }
  } // Should show the tooltip if...


  var shouldShowTooltip = !trulyDisabled && ( // an explicit tooltip is passed or...
  showTooltip && label || // there's a shortcut or...
  shortcut || // there's a label and...
  !!label && ( // the children are empty and...
  !children || Object(lodash__WEBPACK_IMPORTED_MODULE_4__["isArray"])(children) && !children.length) && // the tooltip is not explicitly disabled.
  false !== showTooltip);
  var element = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__["createElement"])(Tag, Object(_babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_0__[/* default */ "a"])({}, tagProps, additionalProps, {
    className: classes,
    "aria-label": additionalProps['aria-label'] || label,
    ref: ref
  }), icon && Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__["createElement"])(_icon__WEBPACK_IMPORTED_MODULE_7__[/* default */ "a"], {
    icon: icon,
    size: iconSize
  }), children);

  if (!shouldShowTooltip) {
    return element;
  }

  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__["createElement"])(_tooltip__WEBPACK_IMPORTED_MODULE_6__[/* default */ "a"], {
    text: label,
    shortcut: shortcut,
    position: tooltipPosition
  }, element);
}
/* harmony default export */ __webpack_exports__["a"] = (Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__["forwardRef"])(Button));
//# sourceMappingURL=index.js.map

/***/ }),

/***/ 7:
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

/***/ 708:
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),

/***/ 719:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
// ESM COMPAT FLAG
__webpack_require__.r(__webpack_exports__);

// EXTERNAL MODULE: external {"this":["wp","element"]}
var external_this_wp_element_ = __webpack_require__(0);

// EXTERNAL MODULE: external {"this":["wp","i18n"]}
var external_this_wp_i18n_ = __webpack_require__(3);

// EXTERNAL MODULE: ./client/settings/index.js
var settings = __webpack_require__(22);

// EXTERNAL MODULE: ./client/marketing/coupons/style.scss
var style = __webpack_require__(708);

// EXTERNAL MODULE: ./client/marketing/components/recommended-extensions/index.js + 1 modules
var recommended_extensions = __webpack_require__(271);

// EXTERNAL MODULE: ./client/marketing/components/knowledge-base/index.js
var knowledge_base = __webpack_require__(269);

// EXTERNAL MODULE: ./client/marketing/data/index.js + 3 modules
var data = __webpack_require__(430);

// CONCATENATED MODULE: ./client/marketing/coupons/index.js


/**
 * External depenencies
 */

/**
 * WooCommerce dependencies
 */


/**
 * Internal dependencies
 */






var coupons_CouponsOverview = function CouponsOverview() {
  var allowMarketplaceSuggestions = Object(settings["g" /* getSetting */])('allowMarketplaceSuggestions', false);
  return Object(external_this_wp_element_["createElement"])("div", {
    className: "woocommerce-marketing-coupons"
  }, allowMarketplaceSuggestions && Object(external_this_wp_element_["createElement"])(recommended_extensions["a" /* default */], {
    title: Object(external_this_wp_i18n_["__"])('Recommended coupon extensions', 'woocommerce'),
    description: Object(external_this_wp_i18n_["__"])('Take your coupon marketing to the next level with our recommended coupon extensions.', 'woocommerce'),
    category: "coupons"
  }), Object(external_this_wp_element_["createElement"])(knowledge_base["a" /* default */], {
    category: "coupons",
    description: Object(external_this_wp_i18n_["__"])('Learn the ins and outs of successful coupon marketing from the experts at WooCommerce.', 'woocommerce')
  }));
};

/* harmony default export */ var coupons = (coupons_CouponsOverview);
// CONCATENATED MODULE: ./client/wp-admin-scripts/marketing-coupons/index.js


/**
 * External dependencies
 */

/**
 * Internal dependencies
 */


var postForm = document.getElementById('posts-filter');

if (postForm) {
  var couponRoot = document.createElement('div');
  couponRoot.setAttribute('id', 'coupon-root');
  Object(external_this_wp_element_["render"])(Object(external_this_wp_element_["createElement"])(coupons, null), postForm.parentNode.appendChild(couponRoot));
}

/***/ }),

/***/ 74:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(13);
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);

/* harmony default export */ __webpack_exports__["a"] = (react__WEBPACK_IMPORTED_MODULE_0___default.a.createContext(null));

/***/ }),

/***/ 75:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "a", function() { return Dashicon; });
/* harmony import */ var _babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(12);
/* harmony import */ var _babel_runtime_helpers_esm_objectWithoutProperties__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(14);
/* harmony import */ var _babel_runtime_helpers_esm_classCallCheck__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(9);
/* harmony import */ var _babel_runtime_helpers_esm_createClass__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(7);
/* harmony import */ var _babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(11);
/* harmony import */ var _babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(4);
/* harmony import */ var _babel_runtime_helpers_esm_inherits__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(10);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var _wordpress_primitives__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(32);









function _createSuper(Derived) { return function () { var Super = Object(_babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_5__[/* default */ "a"])(Derived), result; if (_isNativeReflectConstruct()) { var NewTarget = Object(_babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_5__[/* default */ "a"])(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return Object(_babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_4__[/* default */ "a"])(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/* !!!
IF YOU ARE EDITING dashicon/index.jsx
THEN YOU ARE EDITING A FILE THAT GETS OUTPUT FROM THE DASHICONS REPO!
DO NOT EDIT THAT FILE! EDIT index-header.jsx and index-footer.jsx instead
OR if you're looking to change now SVGs get output, you'll need to edit strings in the Gruntfile :)
!!! */

/**
 * WordPress dependencies
 */



var Dashicon = /*#__PURE__*/function (_Component) {
  Object(_babel_runtime_helpers_esm_inherits__WEBPACK_IMPORTED_MODULE_6__[/* default */ "a"])(Dashicon, _Component);

  var _super = _createSuper(Dashicon);

  function Dashicon() {
    Object(_babel_runtime_helpers_esm_classCallCheck__WEBPACK_IMPORTED_MODULE_2__[/* default */ "a"])(this, Dashicon);

    return _super.apply(this, arguments);
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
      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])(_wordpress_primitives__WEBPACK_IMPORTED_MODULE_8__[/* SVG */ "b"], Object(_babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_0__[/* default */ "a"])({
        "aria-hidden": true,
        role: "img",
        focusable: "false",
        className: iconClass,
        xmlns: "http://www.w3.org/2000/svg",
        width: size,
        height: size,
        viewBox: "0 0 20 20"
      }, extraProps), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])(_wordpress_primitives__WEBPACK_IMPORTED_MODULE_8__[/* Path */ "a"], {
        d: path
      }));
    }
  }]);

  return Dashicon;
}(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["Component"]);


//# sourceMappingURL=index.js.map

/***/ }),

/***/ 78:
/***/ (function(module, exports) {

(function() { module.exports = this["ReactDOM"]; }());

/***/ }),

/***/ 8:
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

/***/ 80:
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

/***/ 82:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/esm/extends.js
var esm_extends = __webpack_require__(12);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/esm/slicedToArray.js + 3 modules
var slicedToArray = __webpack_require__(19);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/esm/objectWithoutProperties.js
var objectWithoutProperties = __webpack_require__(14);

// EXTERNAL MODULE: external {"this":["wp","element"]}
var external_this_wp_element_ = __webpack_require__(0);

// EXTERNAL MODULE: ./node_modules/classnames/index.js
var classnames = __webpack_require__(8);
var classnames_default = /*#__PURE__*/__webpack_require__.n(classnames);

// EXTERNAL MODULE: ./node_modules/@wordpress/dom/build-module/dom.js
var dom = __webpack_require__(95);

// EXTERNAL MODULE: ./node_modules/@wordpress/dom/build-module/index.js + 2 modules
var build_module = __webpack_require__(45);

// EXTERNAL MODULE: ./node_modules/@wordpress/components/node_modules/@wordpress/keycodes/build-module/index.js + 1 modules
var keycodes_build_module = __webpack_require__(31);

// EXTERNAL MODULE: ./node_modules/@wordpress/deprecated/build-module/index.js
var deprecated_build_module = __webpack_require__(59);

// EXTERNAL MODULE: ./node_modules/@wordpress/compose/build-module/hooks/use-viewport-match/index.js
var use_viewport_match = __webpack_require__(208);

// EXTERNAL MODULE: ./node_modules/@wordpress/icons/build-module/library/close.js
var library_close = __webpack_require__(209);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/esm/defineProperty.js
var defineProperty = __webpack_require__(6);

// CONCATENATED MODULE: ./node_modules/@wordpress/components/build-module/popover/utils.js



function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { Object(defineProperty["a" /* default */])(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

/**
 * WordPress dependencies
 */

/**
 * Module constants
 */

var HEIGHT_OFFSET = 10; // used by the arrow and a bit of empty space

/**
 * Utility used to compute the popover position over the xAxis
 *
 * @param {Object}  anchorRect      Anchor Rect.
 * @param {Object}  contentSize     Content Size.
 * @param {string}  xAxis           Desired xAxis.
 * @param {string}  corner          Desired corner.
 * @param {boolean} sticky          Whether or not to stick the popover to the
 *                                  scroll container edge when part of the anchor
 *                                  leaves view.
 * @param {string}  chosenYAxis     yAxis to be used.
 * @param {Element} boundaryElement Boundary element.
 *
 * @return {Object} Popover xAxis position and constraints.
 */

function computePopoverXAxisPosition(anchorRect, contentSize, xAxis, corner, sticky, chosenYAxis, boundaryElement) {
  var width = contentSize.width;
  var isRTL = document.documentElement.dir === 'rtl'; // Correct xAxis for RTL support

  if (xAxis === 'left' && isRTL) {
    xAxis = 'right';
  } else if (xAxis === 'right' && isRTL) {
    xAxis = 'left';
  }

  if (corner === 'left' && isRTL) {
    corner = 'right';
  } else if (corner === 'right' && isRTL) {
    corner = 'left';
  } // x axis alignment choices


  var anchorMidPoint = Math.round(anchorRect.left + anchorRect.width / 2);
  var centerAlignment = {
    popoverLeft: anchorMidPoint,
    contentWidth: (anchorMidPoint - width / 2 > 0 ? width / 2 : anchorMidPoint) + (anchorMidPoint + width / 2 > window.innerWidth ? window.innerWidth - anchorMidPoint : width / 2)
  };
  var leftAlignmentX = anchorRect.left;

  if (corner === 'right') {
    leftAlignmentX = anchorRect.right;
  } else if (chosenYAxis !== 'middle') {
    leftAlignmentX = anchorMidPoint;
  }

  var rightAlignmentX = anchorRect.right;

  if (corner === 'left') {
    rightAlignmentX = anchorRect.left;
  } else if (chosenYAxis !== 'middle') {
    rightAlignmentX = anchorMidPoint;
  }

  var leftAlignment = {
    popoverLeft: leftAlignmentX,
    contentWidth: leftAlignmentX - width > 0 ? width : leftAlignmentX
  };
  var rightAlignment = {
    popoverLeft: rightAlignmentX,
    contentWidth: rightAlignmentX + width > window.innerWidth ? window.innerWidth - rightAlignmentX : width
  }; // Choosing the x axis

  var chosenXAxis = xAxis;
  var contentWidth = null;

  if (!sticky) {
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
  }

  var popoverLeft;

  if (chosenXAxis === 'center') {
    popoverLeft = centerAlignment.popoverLeft;
  } else if (chosenXAxis === 'left') {
    popoverLeft = leftAlignment.popoverLeft;
  } else {
    popoverLeft = rightAlignment.popoverLeft;
  }

  if (boundaryElement) {
    var boundaryRect = boundaryElement.getBoundingClientRect();
    popoverLeft = Math.min(popoverLeft, boundaryRect.right - width);
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
 * @param {Object}  anchorRect        Anchor Rect.
 * @param {Object}  contentSize       Content Size.
 * @param {string}  yAxis             Desired yAxis.
 * @param {string}  corner            Desired corner.
 * @param {boolean} sticky            Whether or not to stick the popover to the
 *                                    scroll container edge when part of the
 *                                    anchor leaves view.
 * @param {Element} anchorRef         The anchor element.
 * @param {Element} relativeOffsetTop If applicable, top offset of the relative
 *                                    positioned parent container.
 *
 * @return {Object} Popover xAxis position and constraints.
 */

function computePopoverYAxisPosition(anchorRect, contentSize, yAxis, corner, sticky, anchorRef, relativeOffsetTop) {
  var height = contentSize.height;

  if (sticky) {
    var scrollContainerEl = Object(dom["b" /* getScrollContainer */])(anchorRef) || document.body;
    var scrollRect = scrollContainerEl.getBoundingClientRect();

    if (anchorRect.top - height <= scrollRect.top) {
      return {
        yAxis: yAxis,
        popoverTop: Math.min(anchorRect.bottom - relativeOffsetTop, scrollRect.top + height - relativeOffsetTop)
      };
    }
  } // y axis alignment choices


  var anchorMidPoint = anchorRect.top + anchorRect.height / 2;

  if (corner === 'bottom') {
    anchorMidPoint = anchorRect.bottom;
  } else if (corner === 'top') {
    anchorMidPoint = anchorRect.top;
  }

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

  var chosenYAxis = yAxis;
  var contentHeight = null;

  if (!sticky) {
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
 * Utility used to compute the popover position and the content max width/height
 * for a popover given its anchor rect and its content size.
 *
 * @param {Object}  anchorRect        Anchor Rect.
 * @param {Object}  contentSize       Content Size.
 * @param {string}  position          Position.
 * @param {boolean} sticky            Whether or not to stick the popover to the
 *                                    scroll container edge when part of the
 *                                    anchor leaves view.
 * @param {Element} anchorRef         The anchor element.
 * @param {number}  relativeOffsetTop If applicable, top offset of the relative
 *                                    positioned parent container.
 * @param {Element} boundaryElement   Boundary element.
 *
 * @return {Object} Popover position and constraints.
 */

function computePopoverPosition(anchorRect, contentSize) {
  var position = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : 'top';
  var sticky = arguments.length > 3 ? arguments[3] : undefined;
  var anchorRef = arguments.length > 4 ? arguments[4] : undefined;
  var relativeOffsetTop = arguments.length > 5 ? arguments[5] : undefined;
  var boundaryElement = arguments.length > 6 ? arguments[6] : undefined;

  var _position$split = position.split(' '),
      _position$split2 = Object(slicedToArray["a" /* default */])(_position$split, 3),
      yAxis = _position$split2[0],
      _position$split2$ = _position$split2[1],
      xAxis = _position$split2$ === void 0 ? 'center' : _position$split2$,
      corner = _position$split2[2];

  var yAxisPosition = computePopoverYAxisPosition(anchorRect, contentSize, yAxis, corner, sticky, anchorRef, relativeOffsetTop);
  var xAxisPosition = computePopoverXAxisPosition(anchorRect, contentSize, xAxis, corner, sticky, yAxisPosition.yAxis, boundaryElement);
  return _objectSpread({}, xAxisPosition, {}, yAxisPosition);
}
//# sourceMappingURL=utils.js.map
// EXTERNAL MODULE: ./node_modules/@wordpress/components/build-module/higher-order/with-focus-return/index.js + 1 modules
var with_focus_return = __webpack_require__(86);

// EXTERNAL MODULE: ./node_modules/@wordpress/components/build-module/higher-order/with-constrained-tabbing/index.js
var with_constrained_tabbing = __webpack_require__(83);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/esm/classCallCheck.js
var classCallCheck = __webpack_require__(9);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/esm/createClass.js
var createClass = __webpack_require__(7);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/esm/possibleConstructorReturn.js
var possibleConstructorReturn = __webpack_require__(11);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/esm/getPrototypeOf.js
var getPrototypeOf = __webpack_require__(4);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/esm/inherits.js + 1 modules
var inherits = __webpack_require__(10);

// EXTERNAL MODULE: ./node_modules/@wordpress/components/build-module/higher-order/with-focus-outside/index.js
var with_focus_outside = __webpack_require__(84);

// CONCATENATED MODULE: ./node_modules/@wordpress/components/build-module/popover/detect-outside.js






function _createSuper(Derived) { return function () { var Super = Object(getPrototypeOf["a" /* default */])(Derived), result; if (_isNativeReflectConstruct()) { var NewTarget = Object(getPrototypeOf["a" /* default */])(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return Object(possibleConstructorReturn["a" /* default */])(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */



var detect_outside_PopoverDetectOutside = /*#__PURE__*/function (_Component) {
  Object(inherits["a" /* default */])(PopoverDetectOutside, _Component);

  var _super = _createSuper(PopoverDetectOutside);

  function PopoverDetectOutside() {
    Object(classCallCheck["a" /* default */])(this, PopoverDetectOutside);

    return _super.apply(this, arguments);
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
// EXTERNAL MODULE: ./node_modules/@wordpress/components/build-module/button/index.js
var build_module_button = __webpack_require__(67);

// CONCATENATED MODULE: ./node_modules/@wordpress/components/build-module/scroll-lock/index.js






function scroll_lock_createSuper(Derived) { return function () { var Super = Object(getPrototypeOf["a" /* default */])(Derived), result; if (scroll_lock_isNativeReflectConstruct()) { var NewTarget = Object(getPrototypeOf["a" /* default */])(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return Object(possibleConstructorReturn["a" /* default */])(this, result); }; }

function scroll_lock_isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

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

  return /*#__PURE__*/function (_Component) {
    Object(inherits["a" /* default */])(ScrollLock, _Component);

    var _super = scroll_lock_createSuper(ScrollLock);

    function ScrollLock() {
      Object(classCallCheck["a" /* default */])(this, ScrollLock);

      return _super.apply(this, arguments);
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
  }(external_this_wp_element_["Component"]);
}
/* harmony default export */ var scroll_lock = (createScrollLockComponent());
//# sourceMappingURL=index.js.map
// EXTERNAL MODULE: ./node_modules/@wordpress/components/build-module/isolated-event-container/index.js
var isolated_event_container = __webpack_require__(85);

// CONCATENATED MODULE: ./node_modules/@wordpress/components/build-module/slot-fill/bubbles-virtually/slot-fill-context.js
/**
 * WordPress dependencies
 */

var SlotFillContext = Object(external_this_wp_element_["createContext"])({
  slots: {},
  fills: {},
  registerSlot: function registerSlot() {},
  unregisterSlot: function unregisterSlot() {},
  registerFill: function registerFill() {},
  unregisterFill: function unregisterFill() {}
});
/* harmony default export */ var slot_fill_context = (SlotFillContext);
//# sourceMappingURL=slot-fill-context.js.map
// CONCATENATED MODULE: ./node_modules/@wordpress/components/build-module/slot-fill/bubbles-virtually/use-slot.js


function use_slot_ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function use_slot_objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { use_slot_ownKeys(Object(source), true).forEach(function (key) { Object(defineProperty["a" /* default */])(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { use_slot_ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */


function use_slot_useSlot(name) {
  var registry = Object(external_this_wp_element_["useContext"])(slot_fill_context);
  var slot = registry.slots[name] || {};
  var slotFills = registry.fills[name];
  var fills = Object(external_this_wp_element_["useMemo"])(function () {
    return slotFills || [];
  }, [slotFills]);
  var updateSlot = Object(external_this_wp_element_["useCallback"])(function (slotRef, slotFillProps) {
    registry.updateSlot(name, slotRef, slotFillProps);
  }, [name, registry.updateSlot]);
  var unregisterSlot = Object(external_this_wp_element_["useCallback"])(function (slotRef) {
    registry.unregisterSlot(name, slotRef);
  }, [name, registry.unregisterSlot]);
  var registerFill = Object(external_this_wp_element_["useCallback"])(function (fillRef) {
    registry.registerFill(name, fillRef);
  }, [name, registry.registerFill]);
  var unregisterFill = Object(external_this_wp_element_["useCallback"])(function (fillRef) {
    registry.unregisterFill(name, fillRef);
  }, [name, registry.unregisterFill]);
  return use_slot_objectSpread({}, slot, {
    updateSlot: updateSlot,
    unregisterSlot: unregisterSlot,
    fills: fills,
    registerFill: registerFill,
    unregisterFill: unregisterFill
  });
}
//# sourceMappingURL=use-slot.js.map
// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/esm/assertThisInitialized.js
var assertThisInitialized = __webpack_require__(5);

// EXTERNAL MODULE: external "lodash"
var external_lodash_ = __webpack_require__(2);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/esm/toConsumableArray.js + 3 modules
var toConsumableArray = __webpack_require__(15);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/esm/typeof.js
var esm_typeof = __webpack_require__(40);

// CONCATENATED MODULE: ./node_modules/@wordpress/components/build-module/slot-fill/bubbles-virtually/slot-fill-provider.js







function _toPropertyKey(arg) { var key = _toPrimitive(arg, "string"); return Object(esm_typeof["a" /* default */])(key) === "symbol" ? key : String(key); }

function _toPrimitive(input, hint) { if (Object(esm_typeof["a" /* default */])(input) !== "object" || input === null) return input; var prim = input[Symbol.toPrimitive]; if (prim !== undefined) { var res = prim.call(input, hint || "default"); if (Object(esm_typeof["a" /* default */])(res) !== "object") return res; throw new TypeError("@@toPrimitive must return a primitive value."); } return (hint === "string" ? String : Number)(input); }

function slot_fill_provider_ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function slot_fill_provider_objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { slot_fill_provider_ownKeys(Object(source), true).forEach(function (key) { Object(defineProperty["a" /* default */])(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { slot_fill_provider_ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */



function useSlotRegistry() {
  var _useState = Object(external_this_wp_element_["useState"])({}),
      _useState2 = Object(slicedToArray["a" /* default */])(_useState, 2),
      slots = _useState2[0],
      setSlots = _useState2[1];

  var _useState3 = Object(external_this_wp_element_["useState"])({}),
      _useState4 = Object(slicedToArray["a" /* default */])(_useState3, 2),
      fills = _useState4[0],
      setFills = _useState4[1];

  var registerSlot = Object(external_this_wp_element_["useCallback"])(function (name, ref, fillProps) {
    setSlots(function (prevSlots) {
      var currentSlot = prevSlots[name] || {};
      return slot_fill_provider_objectSpread({}, prevSlots, Object(defineProperty["a" /* default */])({}, name, slot_fill_provider_objectSpread({}, currentSlot, {
        ref: ref || currentSlot.ref,
        fillProps: fillProps || currentSlot.fillProps || {}
      })));
    });
  }, []);
  var unregisterSlot = Object(external_this_wp_element_["useCallback"])(function (name, ref) {
    setSlots(function (prevSlots) {
      var slot = prevSlots[name],
          nextSlots = Object(objectWithoutProperties["a" /* default */])(prevSlots, [name].map(_toPropertyKey)); // Make sure we're not unregistering a slot registered by another element
      // See https://github.com/WordPress/gutenberg/pull/19242#issuecomment-590295412


      if ((slot === null || slot === void 0 ? void 0 : slot.ref) === ref) {
        return nextSlots;
      }

      return prevSlots;
    });
  }, []);
  var registerFill = Object(external_this_wp_element_["useCallback"])(function (name, ref) {
    setFills(function (prevFills) {
      return slot_fill_provider_objectSpread({}, prevFills, Object(defineProperty["a" /* default */])({}, name, [].concat(Object(toConsumableArray["a" /* default */])(prevFills[name] || []), [ref])));
    });
  }, []);
  var unregisterFill = Object(external_this_wp_element_["useCallback"])(function (name, ref) {
    setFills(function (prevFills) {
      if (prevFills[name]) {
        return slot_fill_provider_objectSpread({}, prevFills, Object(defineProperty["a" /* default */])({}, name, prevFills[name].filter(function (fillRef) {
          return fillRef !== ref;
        })));
      }

      return prevFills;
    });
  }, []); // Memoizing the return value so it can be directly passed to Provider value

  var registry = Object(external_this_wp_element_["useMemo"])(function () {
    return {
      slots: slots,
      fills: fills,
      registerSlot: registerSlot,
      // Just for readability
      updateSlot: registerSlot,
      unregisterSlot: unregisterSlot,
      registerFill: registerFill,
      unregisterFill: unregisterFill
    };
  }, [slots, fills, registerSlot, unregisterSlot, registerFill, unregisterFill]);
  return registry;
}

function slot_fill_provider_SlotFillProvider(_ref) {
  var children = _ref.children;
  var registry = useSlotRegistry();
  return Object(external_this_wp_element_["createElement"])(slot_fill_context.Provider, {
    value: registry
  }, children);
}
//# sourceMappingURL=slot-fill-provider.js.map
// CONCATENATED MODULE: ./node_modules/@wordpress/components/build-module/slot-fill/context.js










function context_createSuper(Derived) { return function () { var Super = Object(getPrototypeOf["a" /* default */])(Derived), result; if (context_isNativeReflectConstruct()) { var NewTarget = Object(getPrototypeOf["a" /* default */])(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return Object(possibleConstructorReturn["a" /* default */])(this, result); }; }

function context_isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */


/**
 * Internal dependencies
 */


var context_SlotFillContext = Object(external_this_wp_element_["createContext"])({
  registerSlot: function registerSlot() {},
  unregisterSlot: function unregisterSlot() {},
  registerFill: function registerFill() {},
  unregisterFill: function unregisterFill() {},
  getSlot: function getSlot() {},
  getFills: function getFills() {},
  subscribe: function subscribe() {}
});
var Provider = context_SlotFillContext.Provider,
    Consumer = context_SlotFillContext.Consumer;

var context_SlotFillProvider = /*#__PURE__*/function (_Component) {
  Object(inherits["a" /* default */])(SlotFillProvider, _Component);

  var _super = context_createSuper(SlotFillProvider);

  function SlotFillProvider() {
    var _this;

    Object(classCallCheck["a" /* default */])(this, SlotFillProvider);

    _this = _super.apply(this, arguments);
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
      }, Object(external_this_wp_element_["createElement"])(slot_fill_provider_SlotFillProvider, null, this.props.children));
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
  var _useContext = Object(external_this_wp_element_["useContext"])(context_SlotFillContext),
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
// CONCATENATED MODULE: ./node_modules/@wordpress/components/build-module/slot-fill/slot.js









function slot_createSuper(Derived) { return function () { var Super = Object(getPrototypeOf["a" /* default */])(Derived), result; if (slot_isNativeReflectConstruct()) { var NewTarget = Object(getPrototypeOf["a" /* default */])(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return Object(possibleConstructorReturn["a" /* default */])(this, result); }; }

function slot_isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */


/**
 * Internal dependencies
 */



var slot_SlotComponent = /*#__PURE__*/function (_Component) {
  Object(inherits["a" /* default */])(SlotComponent, _Component);

  var _super = slot_createSuper(SlotComponent);

  function SlotComponent() {
    var _this;

    Object(classCallCheck["a" /* default */])(this, SlotComponent);

    _this = _super.apply(this, arguments);
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
          _this$props2$fillProp = _this$props2.fillProps,
          fillProps = _this$props2$fillProp === void 0 ? {} : _this$props2$fillProp,
          getFills = _this$props2.getFills;
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

function fill_FillComponent(_ref) {
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

    if (slot) {
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

  if (!slot || !slot.node) {
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
    return Object(external_this_wp_element_["createElement"])(fill_FillComponent, Object(esm_extends["a" /* default */])({}, props, {
      registerFill: registerFill,
      unregisterFill: unregisterFill
    }));
  });
};

/* harmony default export */ var slot_fill_fill = (fill_Fill);
//# sourceMappingURL=fill.js.map
// EXTERNAL MODULE: ./node_modules/@wordpress/is-shallow-equal/lib/index.js
var lib = __webpack_require__(65);
var lib_default = /*#__PURE__*/__webpack_require__.n(lib);

// CONCATENATED MODULE: ./node_modules/@wordpress/components/build-module/slot-fill/bubbles-virtually/slot.js




/**
 * WordPress dependencies
 */


/**
 * Internal dependencies
 */



function bubbles_virtually_slot_Slot(_ref) {
  var name = _ref.name,
      _ref$fillProps = _ref.fillProps,
      fillProps = _ref$fillProps === void 0 ? {} : _ref$fillProps,
      _ref$as = _ref.as,
      Component = _ref$as === void 0 ? 'div' : _ref$as,
      props = Object(objectWithoutProperties["a" /* default */])(_ref, ["name", "fillProps", "as"]);

  var registry = Object(external_this_wp_element_["useContext"])(slot_fill_context);
  var ref = Object(external_this_wp_element_["useRef"])();
  var slot = use_slot_useSlot(name);
  Object(external_this_wp_element_["useLayoutEffect"])(function () {
    registry.registerSlot(name, ref, fillProps);
    return function () {
      registry.unregisterSlot(name, ref);
    }; // We are not including fillProps in the deps because we don't want to
    // unregister and register the slot whenever fillProps change, which would
    // cause the fill to be re-mounted. We are only considering the initial value
    // of fillProps.
  }, [registry.registerSlot, registry.unregisterSlot, name]); // fillProps may be an update that interact with the layout, so
  // we useLayoutEffect

  Object(external_this_wp_element_["useLayoutEffect"])(function () {
    if (slot.fillProps && !lib_default()(slot.fillProps, fillProps)) {
      registry.updateSlot(name, ref, fillProps);
    }
  });
  return Object(external_this_wp_element_["createElement"])(Component, Object(esm_extends["a" /* default */])({
    ref: ref
  }, props));
}
//# sourceMappingURL=slot.js.map
// CONCATENATED MODULE: ./node_modules/@wordpress/components/build-module/slot-fill/bubbles-virtually/fill.js
/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */


function bubbles_virtually_fill_Fill(_ref) {
  var name = _ref.name,
      children = _ref.children;
  var slot = use_slot_useSlot(name);
  var ref = Object(external_this_wp_element_["useRef"])();
  Object(external_this_wp_element_["useEffect"])(function () {
    // We register fills so we can keep track of their existance.
    // Some Slot implementations need to know if there're already fills
    // registered so they can choose to render themselves or not.
    slot.registerFill(ref);
    return function () {
      slot.unregisterFill(ref);
    };
  }, [slot.registerFill, slot.unregisterFill]);

  if (!slot.ref || !slot.ref.current) {
    return null;
  }

  if (typeof children === 'function') {
    children = children(slot.fillProps);
  }

  return Object(external_this_wp_element_["createPortal"])(children, slot.ref.current);
}
//# sourceMappingURL=fill.js.map
// CONCATENATED MODULE: ./node_modules/@wordpress/components/build-module/slot-fill/index.js




/**
 * Internal dependencies
 */






function slot_fill_Slot(_ref) {
  var bubblesVirtually = _ref.bubblesVirtually,
      props = Object(objectWithoutProperties["a" /* default */])(_ref, ["bubblesVirtually"]);

  if (bubblesVirtually) {
    return Object(external_this_wp_element_["createElement"])(bubbles_virtually_slot_Slot, props);
  }

  return Object(external_this_wp_element_["createElement"])(slot_fill_slot, props);
}
function slot_fill_Fill(props) {
  // We're adding both Fills here so they can register themselves before
  // their respective slot has been registered. Only the Fill that has a slot
  // will render. The other one will return null.
  return Object(external_this_wp_element_["createElement"])(external_this_wp_element_["Fragment"], null, Object(external_this_wp_element_["createElement"])(slot_fill_fill, props), Object(external_this_wp_element_["createElement"])(bubbles_virtually_fill_Fill, props));
}
function createSlotFill(name) {
  var FillComponent = function FillComponent(props) {
    return Object(external_this_wp_element_["createElement"])(slot_fill_Fill, Object(esm_extends["a" /* default */])({
      name: name
    }, props));
  };

  FillComponent.displayName = name + 'Fill';

  var SlotComponent = function SlotComponent(props) {
    return Object(external_this_wp_element_["createElement"])(slot_fill_Slot, Object(esm_extends["a" /* default */])({
      name: name
    }, props));
  };

  SlotComponent.displayName = name + 'Slot';
  return {
    Fill: FillComponent,
    Slot: SlotComponent
  };
}

//# sourceMappingURL=index.js.map
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

    if (anchorRef instanceof window.Element) {
      var _rect2 = anchorRef.getBoundingClientRect();

      if (shouldAnchorIncludePadding) {
        return _rect2;
      }

      return withoutPadding(_rect2, anchorRef);
    }

    var top = anchorRef.top,
        bottom = anchorRef.bottom;
    var topRect = top.getBoundingClientRect();
    var bottomRect = bottom.getBoundingClientRect();

    var _rect = new window.DOMRect(topRect.left, topRect.top, topRect.width, bottomRect.bottom - topRect.top);

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
      noArrow = _ref2$noArrow === void 0 ? true : _ref2$noArrow,
      isAlternate = _ref2.isAlternate,
      _ref2$position = _ref2.position,
      position = _ref2$position === void 0 ? 'bottom right' : _ref2$position,
      range = _ref2.range,
      _ref2$focusOnMount = _ref2.focusOnMount,
      focusOnMount = _ref2$focusOnMount === void 0 ? 'firstElement' : _ref2$focusOnMount,
      anchorRef = _ref2.anchorRef,
      shouldAnchorIncludePadding = _ref2.shouldAnchorIncludePadding,
      anchorRect = _ref2.anchorRect,
      getAnchorRect = _ref2.getAnchorRect,
      expandOnMobile = _ref2.expandOnMobile,
      _ref2$animate = _ref2.animate,
      animate = _ref2$animate === void 0 ? true : _ref2$animate,
      onClickOutside = _ref2.onClickOutside,
      onFocusOutside = _ref2.onFocusOutside,
      __unstableSticky = _ref2.__unstableSticky,
      _ref2$__unstableSlotN = _ref2.__unstableSlotName,
      __unstableSlotName = _ref2$__unstableSlotN === void 0 ? SLOT_NAME : _ref2$__unstableSlotN,
      __unstableAllowVerticalSubpixelPosition = _ref2.__unstableAllowVerticalSubpixelPosition,
      __unstableAllowHorizontalSubpixelPosition = _ref2.__unstableAllowHorizontalSubpixelPosition,
      _ref2$__unstableFixed = _ref2.__unstableFixedPosition,
      __unstableFixedPosition = _ref2$__unstableFixed === void 0 ? true : _ref2$__unstableFixed,
      __unstableBoundaryParent = _ref2.__unstableBoundaryParent,
      contentProps = Object(objectWithoutProperties["a" /* default */])(_ref2, ["headerTitle", "onClose", "onKeyDown", "children", "className", "noArrow", "isAlternate", "position", "range", "focusOnMount", "anchorRef", "shouldAnchorIncludePadding", "anchorRect", "getAnchorRect", "expandOnMobile", "animate", "onClickOutside", "onFocusOutside", "__unstableSticky", "__unstableSlotName", "__unstableAllowVerticalSubpixelPosition", "__unstableAllowHorizontalSubpixelPosition", "__unstableFixedPosition", "__unstableBoundaryParent"]);

  var anchorRefFallback = Object(external_this_wp_element_["useRef"])(null);
  var contentRef = Object(external_this_wp_element_["useRef"])(null);
  var containerRef = Object(external_this_wp_element_["useRef"])();
  var contentRect = Object(external_this_wp_element_["useRef"])();
  var isMobileViewport = Object(use_viewport_match["a" /* default */])('medium', '<');

  var _useState = Object(external_this_wp_element_["useState"])(),
      _useState2 = Object(slicedToArray["a" /* default */])(_useState, 2),
      animateOrigin = _useState2[0],
      setAnimateOrigin = _useState2[1];

  var slot = use_slot_useSlot(__unstableSlotName);
  var isExpanded = expandOnMobile && isMobileViewport;
  noArrow = isExpanded || noArrow;
  Object(external_this_wp_element_["useEffect"])(function () {
    if (isExpanded) {
      setClass(containerRef.current, 'is-without-arrow', noArrow);
      setClass(containerRef.current, 'is-alternate', isAlternate);
      setAttribute(containerRef.current, 'data-x-axis');
      setAttribute(containerRef.current, 'data-y-axis');
      setStyle(containerRef.current, 'top');
      setStyle(containerRef.current, 'left');
      setStyle(contentRef.current, 'maxHeight');
      setStyle(contentRef.current, 'maxWidth');
      setStyle(containerRef.current, 'position');
      return;
    }

    var refresh = function refresh() {
      var _ref3 = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {},
          subpixels = _ref3.subpixels;

      if (!containerRef.current || !contentRef.current) {
        return;
      }

      var anchor = computeAnchorRect(anchorRefFallback, anchorRect, getAnchorRect, anchorRef, shouldAnchorIncludePadding);

      if (!anchor) {
        return;
      }

      if (!contentRect.current) {
        contentRect.current = contentRef.current.getBoundingClientRect();
      }

      var relativeOffsetTop = 0; // If there is a positioned ancestor element that is not the body,
      // subtract the position from the anchor rect. If the position of
      // the popover is fixed, the offset parent is null or the body
      // element, in which case the position is relative to the viewport.
      // See https://developer.mozilla.org/en-US/docs/Web/API/HTMLElement/offsetParent

      if (!__unstableFixedPosition) {
        setStyle(containerRef.current, 'position', 'absolute');
        var offsetParent = containerRef.current.offsetParent;
        var offsetParentRect = offsetParent.getBoundingClientRect();
        relativeOffsetTop = offsetParentRect.top;
        anchor = new window.DOMRect(anchor.left - offsetParentRect.left, anchor.top - offsetParentRect.top, anchor.width, anchor.height);
      } else {
        setStyle(containerRef.current, 'position');
      }

      var boundaryElement;

      if (__unstableBoundaryParent) {
        var _containerRef$current;

        boundaryElement = (_containerRef$current = containerRef.current.closest('.popover-slot')) === null || _containerRef$current === void 0 ? void 0 : _containerRef$current.parentNode;
      }

      var _computePopoverPositi = computePopoverPosition(anchor, contentRect.current, position, __unstableSticky, containerRef.current, relativeOffsetTop, boundaryElement),
          popoverTop = _computePopoverPositi.popoverTop,
          popoverLeft = _computePopoverPositi.popoverLeft,
          xAxis = _computePopoverPositi.xAxis,
          yAxis = _computePopoverPositi.yAxis,
          contentHeight = _computePopoverPositi.contentHeight,
          contentWidth = _computePopoverPositi.contentWidth;

      if (typeof popoverTop === 'number' && typeof popoverLeft === 'number') {
        if (subpixels && __unstableAllowVerticalSubpixelPosition) {
          setStyle(containerRef.current, 'left', popoverLeft + 'px');
          setStyle(containerRef.current, 'top');
          setStyle(containerRef.current, 'transform', "translateY(".concat(popoverTop, "px)"));
        } else if (subpixels && __unstableAllowHorizontalSubpixelPosition) {
          setStyle(containerRef.current, 'top', popoverTop + 'px');
          setStyle(containerRef.current, 'left');
          setStyle(containerRef.current, 'transform', "translate(".concat(popoverLeft, "px)"));
        } else {
          setStyle(containerRef.current, 'top', popoverTop + 'px');
          setStyle(containerRef.current, 'left', popoverLeft + 'px');
          setStyle(containerRef.current, 'transform');
        }
      }

      setClass(containerRef.current, 'is-without-arrow', noArrow || xAxis === 'center' && yAxis === 'middle');
      setClass(containerRef.current, 'is-alternate', isAlternate);
      setAttribute(containerRef.current, 'data-x-axis', xAxis);
      setAttribute(containerRef.current, 'data-y-axis', yAxis);
      setStyle(contentRef.current, 'maxHeight', typeof contentHeight === 'number' ? contentHeight + 'px' : '');
      setStyle(contentRef.current, 'maxWidth', typeof contentWidth === 'number' ? contentWidth + 'px' : ''); // Compute the animation position

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
    var rafId;

    var refreshOnAnimationFrame = function refreshOnAnimationFrame() {
      window.cancelAnimationFrame(rafId);
      rafId = window.requestAnimationFrame(refresh);
    }; // Sometimes a click trigger a layout change that affects the popover
    // position. This is an opportunity to immediately refresh rather than
    // at the interval.


    window.addEventListener('click', refreshOnAnimationFrame);
    window.addEventListener('resize', refresh);
    window.addEventListener('scroll', refresh, true);
    var observer;
    var observeElement = __unstableAllowVerticalSubpixelPosition || __unstableAllowHorizontalSubpixelPosition;

    if (observeElement) {
      observer = new window.MutationObserver(function () {
        return refresh({
          subpixels: true
        });
      });
      observer.observe(observeElement, {
        attributes: true
      });
    }

    return function () {
      window.clearTimeout(timeoutId);
      window.clearInterval(intervalHandle);
      window.removeEventListener('resize', refresh);
      window.removeEventListener('scroll', refresh, true);
      window.removeEventListener('click', refreshOnAnimationFrame);
      window.cancelAnimationFrame(rafId);

      if (observer) {
        observer.disconnect();
      }
    };
  }, [isExpanded, anchorRect, getAnchorRect, anchorRef, shouldAnchorIncludePadding, position, __unstableSticky, __unstableAllowVerticalSubpixelPosition, __unstableAllowHorizontalSubpixelPosition, __unstableBoundaryParent]);
  useFocusContentOnMount(focusOnMount, contentRef); // Event handlers

  var maybeClose = function maybeClose(event) {
    // Close on escape
    if (event.keyCode === keycodes_build_module["b" /* ESCAPE */] && onClose) {
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
  }, function (_ref4) {
    var animateClassName = _ref4.className;
    return Object(external_this_wp_element_["createElement"])(isolated_event_container["a" /* default */], Object(esm_extends["a" /* default */])({
      className: classnames_default()('components-popover', className, animateClassName, {
        'is-expanded': isExpanded,
        'is-without-arrow': noArrow,
        'is-alternate': isAlternate
      })
    }, contentProps, {
      onKeyDown: maybeClose,
      ref: containerRef
    }), isExpanded && Object(external_this_wp_element_["createElement"])(scroll_lock, null), isExpanded && Object(external_this_wp_element_["createElement"])("div", {
      className: "components-popover__header"
    }, Object(external_this_wp_element_["createElement"])("span", {
      className: "components-popover__header-title"
    }, headerTitle), Object(external_this_wp_element_["createElement"])(build_module_button["a" /* default */], {
      className: "components-popover__close",
      icon: library_close["a" /* default */],
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

  if (slot.ref) {
    content = Object(external_this_wp_element_["createElement"])(slot_fill_Fill, {
      name: __unstableSlotName
    }, content);
  }

  if (anchorRef || anchorRect) {
    return content;
  }

  return Object(external_this_wp_element_["createElement"])("span", {
    ref: anchorRefFallback
  }, content);
};

var PopoverContainer = popover_Popover;

PopoverContainer.Slot = function (_ref5) {
  var _ref5$name = _ref5.name,
      name = _ref5$name === void 0 ? SLOT_NAME : _ref5$name;
  return Object(external_this_wp_element_["createElement"])(slot_fill_Slot, {
    bubblesVirtually: true,
    name: name,
    className: "popover-slot"
  });
};

/* harmony default export */ var popover = __webpack_exports__["a"] = (PopoverContainer);
//# sourceMappingURL=index.js.map

/***/ }),

/***/ 83:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var _babel_runtime_helpers_esm_classCallCheck__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(9);
/* harmony import */ var _babel_runtime_helpers_esm_createClass__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(7);
/* harmony import */ var _babel_runtime_helpers_esm_assertThisInitialized__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(5);
/* harmony import */ var _babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(11);
/* harmony import */ var _babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(4);
/* harmony import */ var _babel_runtime_helpers_esm_inherits__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(10);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(54);
/* harmony import */ var _wordpress_keycodes__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(31);
/* harmony import */ var _wordpress_dom__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(45);








function _createSuper(Derived) { return function () { var Super = Object(_babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_4__[/* default */ "a"])(Derived), result; if (_isNativeReflectConstruct()) { var NewTarget = Object(_babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_4__[/* default */ "a"])(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return Object(_babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_3__[/* default */ "a"])(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/**
 * WordPress dependencies
 */




var withConstrainedTabbing = Object(_wordpress_compose__WEBPACK_IMPORTED_MODULE_7__[/* default */ "a"])(function (WrappedComponent) {
  return /*#__PURE__*/function (_Component) {
    Object(_babel_runtime_helpers_esm_inherits__WEBPACK_IMPORTED_MODULE_5__[/* default */ "a"])(_class, _Component);

    var _super = _createSuper(_class);

    function _class() {
      var _this;

      Object(_babel_runtime_helpers_esm_classCallCheck__WEBPACK_IMPORTED_MODULE_0__[/* default */ "a"])(this, _class);

      _this = _super.apply(this, arguments);
      _this.focusContainRef = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createRef"])();
      _this.handleTabBehaviour = _this.handleTabBehaviour.bind(Object(_babel_runtime_helpers_esm_assertThisInitialized__WEBPACK_IMPORTED_MODULE_2__[/* default */ "a"])(_this));
      return _this;
    }

    Object(_babel_runtime_helpers_esm_createClass__WEBPACK_IMPORTED_MODULE_1__[/* default */ "a"])(_class, [{
      key: "handleTabBehaviour",
      value: function handleTabBehaviour(event) {
        if (event.keyCode !== _wordpress_keycodes__WEBPACK_IMPORTED_MODULE_8__[/* TAB */ "e"]) {
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
  }(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["Component"]);
}, 'withConstrainedTabbing');
/* harmony default export */ __webpack_exports__["a"] = (withConstrainedTabbing);
//# sourceMappingURL=index.js.map

/***/ }),

/***/ 84:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var _babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(12);
/* harmony import */ var _babel_runtime_helpers_esm_classCallCheck__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(9);
/* harmony import */ var _babel_runtime_helpers_esm_createClass__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(7);
/* harmony import */ var _babel_runtime_helpers_esm_assertThisInitialized__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(5);
/* harmony import */ var _babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(11);
/* harmony import */ var _babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(4);
/* harmony import */ var _babel_runtime_helpers_esm_inherits__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(10);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(2);
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_8__);
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(54);









function _createSuper(Derived) { return function () { var Super = Object(_babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_5__[/* default */ "a"])(Derived), result; if (_isNativeReflectConstruct()) { var NewTarget = Object(_babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_5__[/* default */ "a"])(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return Object(_babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_4__[/* default */ "a"])(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

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
  return /*#__PURE__*/function (_Component) {
    Object(_babel_runtime_helpers_esm_inherits__WEBPACK_IMPORTED_MODULE_6__[/* default */ "a"])(_class, _Component);

    var _super = _createSuper(_class);

    function _class() {
      var _this;

      Object(_babel_runtime_helpers_esm_classCallCheck__WEBPACK_IMPORTED_MODULE_1__[/* default */ "a"])(this, _class);

      _this = _super.apply(this, arguments);
      _this.bindNode = _this.bindNode.bind(Object(_babel_runtime_helpers_esm_assertThisInitialized__WEBPACK_IMPORTED_MODULE_3__[/* default */ "a"])(_this));
      _this.cancelBlurCheck = _this.cancelBlurCheck.bind(Object(_babel_runtime_helpers_esm_assertThisInitialized__WEBPACK_IMPORTED_MODULE_3__[/* default */ "a"])(_this));
      _this.queueBlurCheck = _this.queueBlurCheck.bind(Object(_babel_runtime_helpers_esm_assertThisInitialized__WEBPACK_IMPORTED_MODULE_3__[/* default */ "a"])(_this));
      _this.normalizeButtonFocus = _this.normalizeButtonFocus.bind(Object(_babel_runtime_helpers_esm_assertThisInitialized__WEBPACK_IMPORTED_MODULE_3__[/* default */ "a"])(_this));
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
  }(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["Component"]);
}, 'withFocusOutside'));
//# sourceMappingURL=index.js.map

/***/ }),

/***/ 85:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var _babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(12);
/* harmony import */ var _babel_runtime_helpers_esm_objectWithoutProperties__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(14);
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

/***/ 86:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";

// UNUSED EXPORTS: Provider

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/esm/toConsumableArray.js + 3 modules
var toConsumableArray = __webpack_require__(15);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/esm/classCallCheck.js
var classCallCheck = __webpack_require__(9);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/esm/createClass.js
var createClass = __webpack_require__(7);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/esm/possibleConstructorReturn.js
var possibleConstructorReturn = __webpack_require__(11);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/esm/getPrototypeOf.js
var getPrototypeOf = __webpack_require__(4);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/esm/inherits.js + 1 modules
var inherits = __webpack_require__(10);

// EXTERNAL MODULE: external {"this":["wp","element"]}
var external_this_wp_element_ = __webpack_require__(0);

// EXTERNAL MODULE: external "lodash"
var external_lodash_ = __webpack_require__(2);

// EXTERNAL MODULE: ./node_modules/@wordpress/compose/build-module/utils/create-higher-order-component/index.js
var create_higher_order_component = __webpack_require__(54);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/esm/assertThisInitialized.js
var assertThisInitialized = __webpack_require__(5);

// CONCATENATED MODULE: ./node_modules/@wordpress/components/build-module/higher-order/with-focus-return/context.js









function _createSuper(Derived) { return function () { var Super = Object(getPrototypeOf["a" /* default */])(Derived), result; if (_isNativeReflectConstruct()) { var NewTarget = Object(getPrototypeOf["a" /* default */])(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return Object(possibleConstructorReturn["a" /* default */])(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

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

var context_FocusReturnProvider = /*#__PURE__*/function (_Component) {
  Object(inherits["a" /* default */])(FocusReturnProvider, _Component);

  var _super = _createSuper(FocusReturnProvider);

  function FocusReturnProvider() {
    var _this;

    Object(classCallCheck["a" /* default */])(this, FocusReturnProvider);

    _this = _super.apply(this, arguments);
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








function with_focus_return_createSuper(Derived) { return function () { var Super = Object(getPrototypeOf["a" /* default */])(Derived), result; if (with_focus_return_isNativeReflectConstruct()) { var NewTarget = Object(getPrototypeOf["a" /* default */])(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return Object(possibleConstructorReturn["a" /* default */])(this, result); }; }

function with_focus_return_isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

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
    var FocusReturn = /*#__PURE__*/function (_Component) {
      Object(inherits["a" /* default */])(FocusReturn, _Component);

      var _super = with_focus_return_createSuper(FocusReturn);

      function FocusReturn() {
        var _this;

        Object(classCallCheck["a" /* default */])(this, FocusReturn);

        _this = _super.apply(this, arguments);
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

/***/ 87:
/***/ (function(module, exports, __webpack_require__) {

"use strict";
/* WEBPACK VAR INJECTION */(function(process) {

var _interopRequireDefault = __webpack_require__(55);

var _typeof2 = _interopRequireDefault(__webpack_require__(35));

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

module.exports = __webpack_require__(119)(exports);
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
/* WEBPACK VAR INJECTION */}.call(this, __webpack_require__(47)))

/***/ }),

/***/ 88:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "a", function() { return useMediaQuery; });
/* harmony import */ var _babel_runtime_helpers_esm_slicedToArray__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(19);
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
  var _useState = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["useState"])(query && window.matchMedia(query).matches),
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

/***/ }),

/***/ 89:
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

/***/ 9:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "a", function() { return _classCallCheck; });
function _classCallCheck(instance, Constructor) {
  if (!(instance instanceof Constructor)) {
    throw new TypeError("Cannot call a class as a function");
  }
}

/***/ }),

/***/ 93:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "b", function() { return STORE_KEY; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "a", function() { return API_NAMESPACE; });
var STORE_KEY = 'wc/marketing';
var API_NAMESPACE = '/wc-admin/marketing';

/***/ }),

/***/ 95:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* unused harmony export isHorizontalEdge */
/* unused harmony export isVerticalEdge */
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "a", function() { return getRectangleFromRange; });
/* unused harmony export computeCaretRect */
/* unused harmony export placeCaretAtHorizontalEdge */
/* unused harmony export placeCaretAtVerticalEdge */
/* unused harmony export isTextField */
/* unused harmony export isNumberInput */
/* unused harmony export documentHasSelection */
/* unused harmony export isEntirelySelected */
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "b", function() { return getScrollContainer; });
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
 * Check whether the given element is an input field of type number
 * and has a valueAsNumber
 *
 * @param {HTMLElement} element The HTML element.
 *
 * @return {boolean} True if the element is input and holds a number.
 */

function isNumberInput(element) {
  var nodeName = element.nodeName,
      type = element.type,
      valueAsNumber = element.valueAsNumber;
  return nodeName === 'INPUT' && type === 'number' && !!valueAsNumber;
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

  if (isNumberInput(document.activeElement)) {
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

/***/ 96:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var _babel_runtime_helpers_esm_defineProperty__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(6);
/* harmony import */ var _babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(12);
/* harmony import */ var _babel_runtime_helpers_esm_objectWithoutProperties__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(14);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _wordpress_primitives__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(32);
/* harmony import */ var _dashicon__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(75);




function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { Object(_babel_runtime_helpers_esm_defineProperty__WEBPACK_IMPORTED_MODULE_0__[/* default */ "a"])(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

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
    return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_3__["createElement"])(_dashicon__WEBPACK_IMPORTED_MODULE_5__[/* default */ "a"], Object(_babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_1__[/* default */ "a"])({
      icon: icon,
      size: dashiconSize
    }, additionalProps));
  }

  if (icon && _dashicon__WEBPACK_IMPORTED_MODULE_5__[/* default */ "a"] === icon.type) {
    return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_3__["cloneElement"])(icon, _objectSpread({
      size: dashiconSize
    }, additionalProps));
  } // Icons should be 24x24 by default.


  var iconSize = size || 24;

  if ('function' === typeof icon) {
    if (icon.prototype instanceof _wordpress_element__WEBPACK_IMPORTED_MODULE_3__["Component"]) {
      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_3__["createElement"])(icon, _objectSpread({
        size: iconSize
      }, additionalProps));
    }

    return icon(_objectSpread({
      size: iconSize
    }, additionalProps));
  }

  if (icon && (icon.type === 'svg' || icon.type === _wordpress_primitives__WEBPACK_IMPORTED_MODULE_4__[/* SVG */ "b"])) {
    var appliedProps = _objectSpread({
      width: iconSize,
      height: iconSize
    }, icon.props, {}, additionalProps);

    return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_3__["createElement"])(_wordpress_primitives__WEBPACK_IMPORTED_MODULE_4__[/* SVG */ "b"], appliedProps);
  }

  if (Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_3__["isValidElement"])(icon)) {
    return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_3__["cloneElement"])(icon, _objectSpread({
      size: iconSize
    }, additionalProps));
  }

  return icon;
}

/* harmony default export */ __webpack_exports__["a"] = (Icon);
//# sourceMappingURL=index.js.map

/***/ }),

/***/ 97:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var _babel_runtime_helpers_esm_classCallCheck__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(9);
/* harmony import */ var _babel_runtime_helpers_esm_createClass__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(7);
/* harmony import */ var _babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(11);
/* harmony import */ var _babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(4);
/* harmony import */ var _babel_runtime_helpers_esm_inherits__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(10);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(2);
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var _popover__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(82);
/* harmony import */ var _shortcut__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(89);







function _createSuper(Derived) { return function () { var Super = Object(_babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_3__[/* default */ "a"])(Derived), result; if (_isNativeReflectConstruct()) { var NewTarget = Object(_babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_3__[/* default */ "a"])(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return Object(_babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_2__[/* default */ "a"])(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

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

var Tooltip = /*#__PURE__*/function (_Component) {
  Object(_babel_runtime_helpers_esm_inherits__WEBPACK_IMPORTED_MODULE_4__[/* default */ "a"])(Tooltip, _Component);

  var _super = _createSuper(Tooltip);

  function Tooltip() {
    var _this;

    Object(_babel_runtime_helpers_esm_classCallCheck__WEBPACK_IMPORTED_MODULE_0__[/* default */ "a"])(this, Tooltip);

    _this = _super.apply(this, arguments);
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
          animate: false,
          noArrow: true
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

/***/ })

/******/ });
