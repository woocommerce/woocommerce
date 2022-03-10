this["wc"] = this["wc"] || {}; this["wc"]["data"] =
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
/******/ 	return __webpack_require__(__webpack_require__.s = 491);
/******/ })
/************************************************************************/
/******/ ({

/***/ 0:
/***/ (function(module, exports) {

(function() { module.exports = window["wp"]["element"]; }());

/***/ }),

/***/ 10:
/***/ (function(module, exports) {

(function() { module.exports = window["wp"]["dataControls"]; }());

/***/ }),

/***/ 11:
/***/ (function(module, exports) {

(function() { module.exports = window["moment"]; }());

/***/ }),

/***/ 13:
/***/ (function(module, exports) {

(function() { module.exports = window["wc"]["navigation"]; }());

/***/ }),

/***/ 14:
/***/ (function(module, exports) {

(function() { module.exports = window["wp"]["compose"]; }());

/***/ }),

/***/ 16:
/***/ (function(module, exports) {

(function() { module.exports = window["wp"]["url"]; }());

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

/***/ 27:
/***/ (function(module, exports) {

(function() { module.exports = window["wp"]["hooks"]; }());

/***/ }),

/***/ 272:
/***/ (function(module, exports) {

var charenc = {
  // UTF-8 encoding
  utf8: {
    // Convert a string to a byte array
    stringToBytes: function(str) {
      return charenc.bin.stringToBytes(unescape(encodeURIComponent(str)));
    },

    // Convert a byte array to a string
    bytesToString: function(bytes) {
      return decodeURIComponent(escape(charenc.bin.bytesToString(bytes)));
    }
  },

  // Binary encoding
  bin: {
    // Convert a string to a byte array
    stringToBytes: function(str) {
      for (var bytes = [], i = 0; i < str.length; i++)
        bytes.push(str.charCodeAt(i) & 0xFF);
      return bytes;
    },

    // Convert a byte array to a string
    bytesToString: function(bytes) {
      for (var str = [], i = 0; i < bytes.length; i++)
        str.push(String.fromCharCode(bytes[i]));
      return str.join('');
    }
  }
};

module.exports = charenc;


/***/ }),

/***/ 295:
/***/ (function(module, exports, __webpack_require__) {

(function(){
  var crypt = __webpack_require__(477),
      utf8 = __webpack_require__(272).utf8,
      isBuffer = __webpack_require__(478),
      bin = __webpack_require__(272).bin,

  // The core
  md5 = function (message, options) {
    // Convert to byte array
    if (message.constructor == String)
      if (options && options.encoding === 'binary')
        message = bin.stringToBytes(message);
      else
        message = utf8.stringToBytes(message);
    else if (isBuffer(message))
      message = Array.prototype.slice.call(message, 0);
    else if (!Array.isArray(message) && message.constructor !== Uint8Array)
      message = message.toString();
    // else, assume byte array already

    var m = crypt.bytesToWords(message),
        l = message.length * 8,
        a =  1732584193,
        b = -271733879,
        c = -1732584194,
        d =  271733878;

    // Swap endian
    for (var i = 0; i < m.length; i++) {
      m[i] = ((m[i] <<  8) | (m[i] >>> 24)) & 0x00FF00FF |
             ((m[i] << 24) | (m[i] >>>  8)) & 0xFF00FF00;
    }

    // Padding
    m[l >>> 5] |= 0x80 << (l % 32);
    m[(((l + 64) >>> 9) << 4) + 14] = l;

    // Method shortcuts
    var FF = md5._ff,
        GG = md5._gg,
        HH = md5._hh,
        II = md5._ii;

    for (var i = 0; i < m.length; i += 16) {

      var aa = a,
          bb = b,
          cc = c,
          dd = d;

      a = FF(a, b, c, d, m[i+ 0],  7, -680876936);
      d = FF(d, a, b, c, m[i+ 1], 12, -389564586);
      c = FF(c, d, a, b, m[i+ 2], 17,  606105819);
      b = FF(b, c, d, a, m[i+ 3], 22, -1044525330);
      a = FF(a, b, c, d, m[i+ 4],  7, -176418897);
      d = FF(d, a, b, c, m[i+ 5], 12,  1200080426);
      c = FF(c, d, a, b, m[i+ 6], 17, -1473231341);
      b = FF(b, c, d, a, m[i+ 7], 22, -45705983);
      a = FF(a, b, c, d, m[i+ 8],  7,  1770035416);
      d = FF(d, a, b, c, m[i+ 9], 12, -1958414417);
      c = FF(c, d, a, b, m[i+10], 17, -42063);
      b = FF(b, c, d, a, m[i+11], 22, -1990404162);
      a = FF(a, b, c, d, m[i+12],  7,  1804603682);
      d = FF(d, a, b, c, m[i+13], 12, -40341101);
      c = FF(c, d, a, b, m[i+14], 17, -1502002290);
      b = FF(b, c, d, a, m[i+15], 22,  1236535329);

      a = GG(a, b, c, d, m[i+ 1],  5, -165796510);
      d = GG(d, a, b, c, m[i+ 6],  9, -1069501632);
      c = GG(c, d, a, b, m[i+11], 14,  643717713);
      b = GG(b, c, d, a, m[i+ 0], 20, -373897302);
      a = GG(a, b, c, d, m[i+ 5],  5, -701558691);
      d = GG(d, a, b, c, m[i+10],  9,  38016083);
      c = GG(c, d, a, b, m[i+15], 14, -660478335);
      b = GG(b, c, d, a, m[i+ 4], 20, -405537848);
      a = GG(a, b, c, d, m[i+ 9],  5,  568446438);
      d = GG(d, a, b, c, m[i+14],  9, -1019803690);
      c = GG(c, d, a, b, m[i+ 3], 14, -187363961);
      b = GG(b, c, d, a, m[i+ 8], 20,  1163531501);
      a = GG(a, b, c, d, m[i+13],  5, -1444681467);
      d = GG(d, a, b, c, m[i+ 2],  9, -51403784);
      c = GG(c, d, a, b, m[i+ 7], 14,  1735328473);
      b = GG(b, c, d, a, m[i+12], 20, -1926607734);

      a = HH(a, b, c, d, m[i+ 5],  4, -378558);
      d = HH(d, a, b, c, m[i+ 8], 11, -2022574463);
      c = HH(c, d, a, b, m[i+11], 16,  1839030562);
      b = HH(b, c, d, a, m[i+14], 23, -35309556);
      a = HH(a, b, c, d, m[i+ 1],  4, -1530992060);
      d = HH(d, a, b, c, m[i+ 4], 11,  1272893353);
      c = HH(c, d, a, b, m[i+ 7], 16, -155497632);
      b = HH(b, c, d, a, m[i+10], 23, -1094730640);
      a = HH(a, b, c, d, m[i+13],  4,  681279174);
      d = HH(d, a, b, c, m[i+ 0], 11, -358537222);
      c = HH(c, d, a, b, m[i+ 3], 16, -722521979);
      b = HH(b, c, d, a, m[i+ 6], 23,  76029189);
      a = HH(a, b, c, d, m[i+ 9],  4, -640364487);
      d = HH(d, a, b, c, m[i+12], 11, -421815835);
      c = HH(c, d, a, b, m[i+15], 16,  530742520);
      b = HH(b, c, d, a, m[i+ 2], 23, -995338651);

      a = II(a, b, c, d, m[i+ 0],  6, -198630844);
      d = II(d, a, b, c, m[i+ 7], 10,  1126891415);
      c = II(c, d, a, b, m[i+14], 15, -1416354905);
      b = II(b, c, d, a, m[i+ 5], 21, -57434055);
      a = II(a, b, c, d, m[i+12],  6,  1700485571);
      d = II(d, a, b, c, m[i+ 3], 10, -1894986606);
      c = II(c, d, a, b, m[i+10], 15, -1051523);
      b = II(b, c, d, a, m[i+ 1], 21, -2054922799);
      a = II(a, b, c, d, m[i+ 8],  6,  1873313359);
      d = II(d, a, b, c, m[i+15], 10, -30611744);
      c = II(c, d, a, b, m[i+ 6], 15, -1560198380);
      b = II(b, c, d, a, m[i+13], 21,  1309151649);
      a = II(a, b, c, d, m[i+ 4],  6, -145523070);
      d = II(d, a, b, c, m[i+11], 10, -1120210379);
      c = II(c, d, a, b, m[i+ 2], 15,  718787259);
      b = II(b, c, d, a, m[i+ 9], 21, -343485551);

      a = (a + aa) >>> 0;
      b = (b + bb) >>> 0;
      c = (c + cc) >>> 0;
      d = (d + dd) >>> 0;
    }

    return crypt.endian([a, b, c, d]);
  };

  // Auxiliary functions
  md5._ff  = function (a, b, c, d, x, s, t) {
    var n = a + (b & c | ~b & d) + (x >>> 0) + t;
    return ((n << s) | (n >>> (32 - s))) + b;
  };
  md5._gg  = function (a, b, c, d, x, s, t) {
    var n = a + (b & d | c & ~d) + (x >>> 0) + t;
    return ((n << s) | (n >>> (32 - s))) + b;
  };
  md5._hh  = function (a, b, c, d, x, s, t) {
    var n = a + (b ^ c ^ d) + (x >>> 0) + t;
    return ((n << s) | (n >>> (32 - s))) + b;
  };
  md5._ii  = function (a, b, c, d, x, s, t) {
    var n = a + (c ^ (b | ~d)) + (x >>> 0) + t;
    return ((n << s) | (n >>> (32 - s))) + b;
  };

  // Package private blocksize
  md5._blocksize = 16;
  md5._digestsize = 16;

  module.exports = function (message, options) {
    if (message === undefined || message === null)
      throw new Error('Illegal argument ' + message);

    var digestbytes = crypt.wordsToBytes(md5(message, options));
    return options && options.asBytes ? digestbytes :
        options && options.asString ? bin.bytesToString(digestbytes) :
        crypt.bytesToHex(digestbytes);
  };

})();


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

/***/ 33:
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var implementation = __webpack_require__(59);

module.exports = Function.prototype.bind || implementation;


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

/***/ 47:
/***/ (function(module, exports) {

(function() { module.exports = window["wp"]["deprecated"]; }());

/***/ }),

/***/ 476:
/***/ (function(module, exports) {

(function() { module.exports = window["wp"]["coreData"]; }());

/***/ }),

/***/ 477:
/***/ (function(module, exports) {

(function() {
  var base64map
      = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/',

  crypt = {
    // Bit-wise rotation left
    rotl: function(n, b) {
      return (n << b) | (n >>> (32 - b));
    },

    // Bit-wise rotation right
    rotr: function(n, b) {
      return (n << (32 - b)) | (n >>> b);
    },

    // Swap big-endian to little-endian and vice versa
    endian: function(n) {
      // If number given, swap endian
      if (n.constructor == Number) {
        return crypt.rotl(n, 8) & 0x00FF00FF | crypt.rotl(n, 24) & 0xFF00FF00;
      }

      // Else, assume array and swap all items
      for (var i = 0; i < n.length; i++)
        n[i] = crypt.endian(n[i]);
      return n;
    },

    // Generate an array of any length of random bytes
    randomBytes: function(n) {
      for (var bytes = []; n > 0; n--)
        bytes.push(Math.floor(Math.random() * 256));
      return bytes;
    },

    // Convert a byte array to big-endian 32-bit words
    bytesToWords: function(bytes) {
      for (var words = [], i = 0, b = 0; i < bytes.length; i++, b += 8)
        words[b >>> 5] |= bytes[i] << (24 - b % 32);
      return words;
    },

    // Convert big-endian 32-bit words to a byte array
    wordsToBytes: function(words) {
      for (var bytes = [], b = 0; b < words.length * 32; b += 8)
        bytes.push((words[b >>> 5] >>> (24 - b % 32)) & 0xFF);
      return bytes;
    },

    // Convert a byte array to a hex string
    bytesToHex: function(bytes) {
      for (var hex = [], i = 0; i < bytes.length; i++) {
        hex.push((bytes[i] >>> 4).toString(16));
        hex.push((bytes[i] & 0xF).toString(16));
      }
      return hex.join('');
    },

    // Convert a hex string to a byte array
    hexToBytes: function(hex) {
      for (var bytes = [], c = 0; c < hex.length; c += 2)
        bytes.push(parseInt(hex.substr(c, 2), 16));
      return bytes;
    },

    // Convert a byte array to a base-64 string
    bytesToBase64: function(bytes) {
      for (var base64 = [], i = 0; i < bytes.length; i += 3) {
        var triplet = (bytes[i] << 16) | (bytes[i + 1] << 8) | bytes[i + 2];
        for (var j = 0; j < 4; j++)
          if (i * 8 + j * 6 <= bytes.length * 8)
            base64.push(base64map.charAt((triplet >>> 6 * (3 - j)) & 0x3F));
          else
            base64.push('=');
      }
      return base64.join('');
    },

    // Convert a base-64 string to a byte array
    base64ToBytes: function(base64) {
      // Remove non-base-64 characters
      base64 = base64.replace(/[^A-Z0-9+\/]/ig, '');

      for (var bytes = [], i = 0, imod4 = 0; i < base64.length;
          imod4 = ++i % 4) {
        if (imod4 == 0) continue;
        bytes.push(((base64map.indexOf(base64.charAt(i - 1))
            & (Math.pow(2, -2 * imod4 + 8) - 1)) << (imod4 * 2))
            | (base64map.indexOf(base64.charAt(i)) >>> (6 - imod4 * 2)));
      }
      return bytes;
    }
  };

  module.exports = crypt;
})();


/***/ }),

/***/ 478:
/***/ (function(module, exports) {

/*!
 * Determine if an object is a Buffer
 *
 * @author   Feross Aboukhadijeh <https://feross.org>
 * @license  MIT
 */

// The _isBuffer check is for Safari 5-7 support, because it's missing
// Object.prototype.constructor. Remove this eventually
module.exports = function (obj) {
  return obj != null && (isBuffer(obj) || isSlowBuffer(obj) || !!obj._isBuffer)
}

function isBuffer (obj) {
  return !!obj.constructor && typeof obj.constructor.isBuffer === 'function' && obj.constructor.isBuffer(obj)
}

// For Node v0.10 support. Remove this eventually.
function isSlowBuffer (obj) {
  return typeof obj.readFloatLE === 'function' && typeof obj.slice === 'function' && isBuffer(obj.slice(0, 0))
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

/***/ 491:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
// ESM COMPAT FLAG
__webpack_require__.r(__webpack_exports__);

// EXPORTS
__webpack_require__.d(__webpack_exports__, "SETTINGS_STORE_NAME", function() { return /* reexport */ SETTINGS_STORE_NAME; });
__webpack_require__.d(__webpack_exports__, "withSettingsHydration", function() { return /* reexport */ withSettingsHydration; });
__webpack_require__.d(__webpack_exports__, "useSettings", function() { return /* reexport */ useSettings; });
__webpack_require__.d(__webpack_exports__, "PLUGINS_STORE_NAME", function() { return /* reexport */ PLUGINS_STORE_NAME; });
__webpack_require__.d(__webpack_exports__, "pluginNames", function() { return /* reexport */ pluginNames; });
__webpack_require__.d(__webpack_exports__, "withPluginsHydration", function() { return /* reexport */ withPluginsHydration; });
__webpack_require__.d(__webpack_exports__, "ONBOARDING_STORE_NAME", function() { return /* reexport */ ONBOARDING_STORE_NAME; });
__webpack_require__.d(__webpack_exports__, "withOnboardingHydration", function() { return /* reexport */ withOnboardingHydration; });
__webpack_require__.d(__webpack_exports__, "USER_STORE_NAME", function() { return /* reexport */ USER_STORE_NAME; });
__webpack_require__.d(__webpack_exports__, "withCurrentUserHydration", function() { return /* reexport */ withCurrentUserHydration; });
__webpack_require__.d(__webpack_exports__, "useUser", function() { return /* reexport */ useUser; });
__webpack_require__.d(__webpack_exports__, "useUserPreferences", function() { return /* reexport */ useUserPreferences; });
__webpack_require__.d(__webpack_exports__, "OPTIONS_STORE_NAME", function() { return /* reexport */ OPTIONS_STORE_NAME; });
__webpack_require__.d(__webpack_exports__, "withOptionsHydration", function() { return /* reexport */ withOptionsHydration; });
__webpack_require__.d(__webpack_exports__, "useOptionsHydration", function() { return /* reexport */ useOptionsHydration; });
__webpack_require__.d(__webpack_exports__, "REVIEWS_STORE_NAME", function() { return /* reexport */ REVIEWS_STORE_NAME; });
__webpack_require__.d(__webpack_exports__, "NOTES_STORE_NAME", function() { return /* reexport */ NOTES_STORE_NAME; });
__webpack_require__.d(__webpack_exports__, "REPORTS_STORE_NAME", function() { return /* reexport */ REPORTS_STORE_NAME; });
__webpack_require__.d(__webpack_exports__, "ITEMS_STORE_NAME", function() { return /* reexport */ ITEMS_STORE_NAME; });
__webpack_require__.d(__webpack_exports__, "getLeaderboard", function() { return /* reexport */ getLeaderboard; });
__webpack_require__.d(__webpack_exports__, "searchItemsByString", function() { return /* reexport */ searchItemsByString; });
__webpack_require__.d(__webpack_exports__, "COUNTRIES_STORE_NAME", function() { return /* reexport */ COUNTRIES_STORE_NAME; });
__webpack_require__.d(__webpack_exports__, "NAVIGATION_STORE_NAME", function() { return /* reexport */ NAVIGATION_STORE_NAME; });
__webpack_require__.d(__webpack_exports__, "withNavigationHydration", function() { return /* reexport */ withNavigationHydration; });
__webpack_require__.d(__webpack_exports__, "PAYMENT_GATEWAYS_STORE_NAME", function() { return /* reexport */ PAYMENT_GATEWAYS_STORE_NAME; });
__webpack_require__.d(__webpack_exports__, "getFilterQuery", function() { return /* reexport */ getFilterQuery; });
__webpack_require__.d(__webpack_exports__, "getSummaryNumbers", function() { return /* reexport */ getSummaryNumbers; });
__webpack_require__.d(__webpack_exports__, "getReportTableData", function() { return /* reexport */ getReportTableData; });
__webpack_require__.d(__webpack_exports__, "getReportTableQuery", function() { return /* reexport */ getReportTableQuery; });
__webpack_require__.d(__webpack_exports__, "getReportChartData", function() { return /* reexport */ getReportChartData; });
__webpack_require__.d(__webpack_exports__, "getTooltipValueFormat", function() { return /* reexport */ getTooltipValueFormat; });
__webpack_require__.d(__webpack_exports__, "MAX_PER_PAGE", function() { return /* reexport */ MAX_PER_PAGE; });
__webpack_require__.d(__webpack_exports__, "QUERY_DEFAULTS", function() { return /* reexport */ QUERY_DEFAULTS; });
__webpack_require__.d(__webpack_exports__, "NAMESPACE", function() { return /* reexport */ NAMESPACE; });
__webpack_require__.d(__webpack_exports__, "WC_ADMIN_NAMESPACE", function() { return /* reexport */ WC_ADMIN_NAMESPACE; });
__webpack_require__.d(__webpack_exports__, "WCS_NAMESPACE", function() { return /* reexport */ WCS_NAMESPACE; });
__webpack_require__.d(__webpack_exports__, "SECOND", function() { return /* reexport */ SECOND; });
__webpack_require__.d(__webpack_exports__, "MINUTE", function() { return /* reexport */ MINUTE; });
__webpack_require__.d(__webpack_exports__, "HOUR", function() { return /* reexport */ HOUR; });
__webpack_require__.d(__webpack_exports__, "DAY", function() { return /* reexport */ DAY; });
__webpack_require__.d(__webpack_exports__, "WEEK", function() { return /* reexport */ WEEK; });
__webpack_require__.d(__webpack_exports__, "MONTH", function() { return /* reexport */ MONTH; });
__webpack_require__.d(__webpack_exports__, "EXPORT_STORE_NAME", function() { return /* reexport */ EXPORT_STORE_NAME; });
__webpack_require__.d(__webpack_exports__, "IMPORT_STORE_NAME", function() { return /* reexport */ IMPORT_STORE_NAME; });
__webpack_require__.d(__webpack_exports__, "getFreeExtensions", function() { return /* reexport */ getFreeExtensions; });
__webpack_require__.d(__webpack_exports__, "getProfileItems", function() { return /* reexport */ getProfileItems; });
__webpack_require__.d(__webpack_exports__, "getTaskLists", function() { return /* reexport */ getTaskLists; });
__webpack_require__.d(__webpack_exports__, "getTaskListsByIds", function() { return /* reexport */ getTaskListsByIds; });
__webpack_require__.d(__webpack_exports__, "getTaskList", function() { return /* reexport */ getTaskList; });
__webpack_require__.d(__webpack_exports__, "getTask", function() { return /* reexport */ getTask; });
__webpack_require__.d(__webpack_exports__, "getPaymentGatewaySuggestions", function() { return /* reexport */ getPaymentGatewaySuggestions; });
__webpack_require__.d(__webpack_exports__, "getOnboardingError", function() { return /* reexport */ getOnboardingError; });
__webpack_require__.d(__webpack_exports__, "isOnboardingRequesting", function() { return /* reexport */ isOnboardingRequesting; });
__webpack_require__.d(__webpack_exports__, "getEmailPrefill", function() { return /* reexport */ getEmailPrefill; });
__webpack_require__.d(__webpack_exports__, "getProductTypes", function() { return /* reexport */ getProductTypes; });

// NAMESPACE OBJECT: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/settings/selectors.js
var selectors_namespaceObject = {};
__webpack_require__.r(selectors_namespaceObject);
__webpack_require__.d(selectors_namespaceObject, "getSettingsGroupNames", function() { return getSettingsGroupNames; });
__webpack_require__.d(selectors_namespaceObject, "getSettings", function() { return getSettings; });
__webpack_require__.d(selectors_namespaceObject, "getDirtyKeys", function() { return getDirtyKeys; });
__webpack_require__.d(selectors_namespaceObject, "getIsDirty", function() { return selectors_getIsDirty; });
__webpack_require__.d(selectors_namespaceObject, "getSettingsForGroup", function() { return selectors_getSettingsForGroup; });
__webpack_require__.d(selectors_namespaceObject, "isUpdateSettingsRequesting", function() { return selectors_isUpdateSettingsRequesting; });
__webpack_require__.d(selectors_namespaceObject, "getSetting", function() { return getSetting; });
__webpack_require__.d(selectors_namespaceObject, "getLastSettingsErrorForGroup", function() { return selectors_getLastSettingsErrorForGroup; });
__webpack_require__.d(selectors_namespaceObject, "getSettingsError", function() { return getSettingsError; });

// NAMESPACE OBJECT: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/settings/actions.js
var actions_namespaceObject = {};
__webpack_require__.r(actions_namespaceObject);
__webpack_require__.d(actions_namespaceObject, "updateSettingsForGroup", function() { return actions_updateSettingsForGroup; });
__webpack_require__.d(actions_namespaceObject, "updateErrorForGroup", function() { return updateErrorForGroup; });
__webpack_require__.d(actions_namespaceObject, "setIsRequesting", function() { return setIsRequesting; });
__webpack_require__.d(actions_namespaceObject, "clearIsDirty", function() { return actions_clearIsDirty; });
__webpack_require__.d(actions_namespaceObject, "updateAndPersistSettingsForGroup", function() { return actions_updateAndPersistSettingsForGroup; });
__webpack_require__.d(actions_namespaceObject, "persistSettingsForGroup", function() { return actions_persistSettingsForGroup; });
__webpack_require__.d(actions_namespaceObject, "clearSettings", function() { return clearSettings; });

// NAMESPACE OBJECT: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/settings/resolvers.js
var resolvers_namespaceObject = {};
__webpack_require__.r(resolvers_namespaceObject);
__webpack_require__.d(resolvers_namespaceObject, "getSettings", function() { return resolvers_getSettings; });
__webpack_require__.d(resolvers_namespaceObject, "getSettingsForGroup", function() { return resolvers_getSettingsForGroup; });

// NAMESPACE OBJECT: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/plugins/selectors.js
var plugins_selectors_namespaceObject = {};
__webpack_require__.r(plugins_selectors_namespaceObject);
__webpack_require__.d(plugins_selectors_namespaceObject, "getActivePlugins", function() { return getActivePlugins; });
__webpack_require__.d(plugins_selectors_namespaceObject, "getInstalledPlugins", function() { return getInstalledPlugins; });
__webpack_require__.d(plugins_selectors_namespaceObject, "isPluginsRequesting", function() { return isPluginsRequesting; });
__webpack_require__.d(plugins_selectors_namespaceObject, "getPluginsError", function() { return getPluginsError; });
__webpack_require__.d(plugins_selectors_namespaceObject, "isJetpackConnected", function() { return isJetpackConnected; });
__webpack_require__.d(plugins_selectors_namespaceObject, "getJetpackConnectUrl", function() { return getJetpackConnectUrl; });
__webpack_require__.d(plugins_selectors_namespaceObject, "getPluginInstallState", function() { return getPluginInstallState; });
__webpack_require__.d(plugins_selectors_namespaceObject, "getPaypalOnboardingStatus", function() { return getPaypalOnboardingStatus; });
__webpack_require__.d(plugins_selectors_namespaceObject, "getRecommendedPlugins", function() { return getRecommendedPlugins; });

// NAMESPACE OBJECT: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/plugins/actions.js
var plugins_actions_namespaceObject = {};
__webpack_require__.r(plugins_actions_namespaceObject);
__webpack_require__.d(plugins_actions_namespaceObject, "formatErrors", function() { return formatErrors; });
__webpack_require__.d(plugins_actions_namespaceObject, "updateActivePlugins", function() { return actions_updateActivePlugins; });
__webpack_require__.d(plugins_actions_namespaceObject, "updateInstalledPlugins", function() { return actions_updateInstalledPlugins; });
__webpack_require__.d(plugins_actions_namespaceObject, "setIsRequesting", function() { return actions_setIsRequesting; });
__webpack_require__.d(plugins_actions_namespaceObject, "setError", function() { return setError; });
__webpack_require__.d(plugins_actions_namespaceObject, "updateIsJetpackConnected", function() { return actions_updateIsJetpackConnected; });
__webpack_require__.d(plugins_actions_namespaceObject, "updateJetpackConnectUrl", function() { return updateJetpackConnectUrl; });
__webpack_require__.d(plugins_actions_namespaceObject, "installPlugins", function() { return installPlugins; });
__webpack_require__.d(plugins_actions_namespaceObject, "activatePlugins", function() { return activatePlugins; });
__webpack_require__.d(plugins_actions_namespaceObject, "installAndActivatePlugins", function() { return installAndActivatePlugins; });
__webpack_require__.d(plugins_actions_namespaceObject, "createErrorNotice", function() { return createErrorNotice; });
__webpack_require__.d(plugins_actions_namespaceObject, "connectToJetpack", function() { return connectToJetpack; });
__webpack_require__.d(plugins_actions_namespaceObject, "installJetpackAndConnect", function() { return installJetpackAndConnect; });
__webpack_require__.d(plugins_actions_namespaceObject, "connectToJetpackWithFailureRedirect", function() { return connectToJetpackWithFailureRedirect; });
__webpack_require__.d(plugins_actions_namespaceObject, "setPaypalOnboardingStatus", function() { return setPaypalOnboardingStatus; });
__webpack_require__.d(plugins_actions_namespaceObject, "setRecommendedPlugins", function() { return setRecommendedPlugins; });
__webpack_require__.d(plugins_actions_namespaceObject, "dismissRecommendedPlugins", function() { return dismissRecommendedPlugins; });

// NAMESPACE OBJECT: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/options/selectors.js
var options_selectors_namespaceObject = {};
__webpack_require__.r(options_selectors_namespaceObject);
__webpack_require__.d(options_selectors_namespaceObject, "getOption", function() { return getOption; });
__webpack_require__.d(options_selectors_namespaceObject, "getOptionsRequestingError", function() { return getOptionsRequestingError; });
__webpack_require__.d(options_selectors_namespaceObject, "isOptionsUpdating", function() { return isOptionsUpdating; });
__webpack_require__.d(options_selectors_namespaceObject, "getOptionsUpdatingError", function() { return getOptionsUpdatingError; });

// NAMESPACE OBJECT: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/options/actions.js
var options_actions_namespaceObject = {};
__webpack_require__.r(options_actions_namespaceObject);
__webpack_require__.d(options_actions_namespaceObject, "receiveOptions", function() { return actions_receiveOptions; });
__webpack_require__.d(options_actions_namespaceObject, "setRequestingError", function() { return setRequestingError; });
__webpack_require__.d(options_actions_namespaceObject, "setUpdatingError", function() { return setUpdatingError; });
__webpack_require__.d(options_actions_namespaceObject, "setIsUpdating", function() { return setIsUpdating; });
__webpack_require__.d(options_actions_namespaceObject, "updateOptions", function() { return updateOptions; });

// NAMESPACE OBJECT: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/options/resolvers.js
var options_resolvers_namespaceObject = {};
__webpack_require__.r(options_resolvers_namespaceObject);
__webpack_require__.d(options_resolvers_namespaceObject, "getOption", function() { return resolvers_getOption; });

// NAMESPACE OBJECT: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/plugins/resolvers.js
var plugins_resolvers_namespaceObject = {};
__webpack_require__.r(plugins_resolvers_namespaceObject);
__webpack_require__.d(plugins_resolvers_namespaceObject, "getActivePlugins", function() { return resolvers_getActivePlugins; });
__webpack_require__.d(plugins_resolvers_namespaceObject, "getInstalledPlugins", function() { return resolvers_getInstalledPlugins; });
__webpack_require__.d(plugins_resolvers_namespaceObject, "isJetpackConnected", function() { return resolvers_isJetpackConnected; });
__webpack_require__.d(plugins_resolvers_namespaceObject, "getJetpackConnectUrl", function() { return resolvers_getJetpackConnectUrl; });
__webpack_require__.d(plugins_resolvers_namespaceObject, "getPaypalOnboardingStatus", function() { return resolvers_getPaypalOnboardingStatus; });
__webpack_require__.d(plugins_resolvers_namespaceObject, "getRecommendedPlugins", function() { return resolvers_getRecommendedPlugins; });

// NAMESPACE OBJECT: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/onboarding/selectors.js
var onboarding_selectors_namespaceObject = {};
__webpack_require__.r(onboarding_selectors_namespaceObject);
__webpack_require__.d(onboarding_selectors_namespaceObject, "getFreeExtensions", function() { return getFreeExtensions; });
__webpack_require__.d(onboarding_selectors_namespaceObject, "getProfileItems", function() { return getProfileItems; });
__webpack_require__.d(onboarding_selectors_namespaceObject, "getTaskLists", function() { return getTaskLists; });
__webpack_require__.d(onboarding_selectors_namespaceObject, "getTaskListsByIds", function() { return getTaskListsByIds; });
__webpack_require__.d(onboarding_selectors_namespaceObject, "getTaskList", function() { return getTaskList; });
__webpack_require__.d(onboarding_selectors_namespaceObject, "getTask", function() { return getTask; });
__webpack_require__.d(onboarding_selectors_namespaceObject, "getPaymentGatewaySuggestions", function() { return getPaymentGatewaySuggestions; });
__webpack_require__.d(onboarding_selectors_namespaceObject, "getOnboardingError", function() { return getOnboardingError; });
__webpack_require__.d(onboarding_selectors_namespaceObject, "isOnboardingRequesting", function() { return isOnboardingRequesting; });
__webpack_require__.d(onboarding_selectors_namespaceObject, "getEmailPrefill", function() { return getEmailPrefill; });
__webpack_require__.d(onboarding_selectors_namespaceObject, "getProductTypes", function() { return getProductTypes; });

// NAMESPACE OBJECT: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/onboarding/actions.js
var onboarding_actions_namespaceObject = {};
__webpack_require__.r(onboarding_actions_namespaceObject);
__webpack_require__.d(onboarding_actions_namespaceObject, "getFreeExtensionsError", function() { return getFreeExtensionsError; });
__webpack_require__.d(onboarding_actions_namespaceObject, "getFreeExtensionsSuccess", function() { return getFreeExtensionsSuccess; });
__webpack_require__.d(onboarding_actions_namespaceObject, "setError", function() { return actions_setError; });
__webpack_require__.d(onboarding_actions_namespaceObject, "setIsRequesting", function() { return onboarding_actions_setIsRequesting; });
__webpack_require__.d(onboarding_actions_namespaceObject, "setProfileItems", function() { return actions_setProfileItems; });
__webpack_require__.d(onboarding_actions_namespaceObject, "getTaskListsError", function() { return getTaskListsError; });
__webpack_require__.d(onboarding_actions_namespaceObject, "getTaskListsSuccess", function() { return getTaskListsSuccess; });
__webpack_require__.d(onboarding_actions_namespaceObject, "snoozeTaskError", function() { return snoozeTaskError; });
__webpack_require__.d(onboarding_actions_namespaceObject, "snoozeTaskRequest", function() { return snoozeTaskRequest; });
__webpack_require__.d(onboarding_actions_namespaceObject, "snoozeTaskSuccess", function() { return snoozeTaskSuccess; });
__webpack_require__.d(onboarding_actions_namespaceObject, "undoSnoozeTaskError", function() { return undoSnoozeTaskError; });
__webpack_require__.d(onboarding_actions_namespaceObject, "undoSnoozeTaskRequest", function() { return undoSnoozeTaskRequest; });
__webpack_require__.d(onboarding_actions_namespaceObject, "undoSnoozeTaskSuccess", function() { return undoSnoozeTaskSuccess; });
__webpack_require__.d(onboarding_actions_namespaceObject, "dismissTaskError", function() { return dismissTaskError; });
__webpack_require__.d(onboarding_actions_namespaceObject, "dismissTaskRequest", function() { return dismissTaskRequest; });
__webpack_require__.d(onboarding_actions_namespaceObject, "dismissTaskSuccess", function() { return dismissTaskSuccess; });
__webpack_require__.d(onboarding_actions_namespaceObject, "undoDismissTaskError", function() { return undoDismissTaskError; });
__webpack_require__.d(onboarding_actions_namespaceObject, "undoDismissTaskRequest", function() { return undoDismissTaskRequest; });
__webpack_require__.d(onboarding_actions_namespaceObject, "undoDismissTaskSuccess", function() { return undoDismissTaskSuccess; });
__webpack_require__.d(onboarding_actions_namespaceObject, "hideTaskListError", function() { return hideTaskListError; });
__webpack_require__.d(onboarding_actions_namespaceObject, "hideTaskListRequest", function() { return hideTaskListRequest; });
__webpack_require__.d(onboarding_actions_namespaceObject, "hideTaskListSuccess", function() { return hideTaskListSuccess; });
__webpack_require__.d(onboarding_actions_namespaceObject, "unhideTaskListError", function() { return unhideTaskListError; });
__webpack_require__.d(onboarding_actions_namespaceObject, "unhideTaskListRequest", function() { return unhideTaskListRequest; });
__webpack_require__.d(onboarding_actions_namespaceObject, "unhideTaskListSuccess", function() { return unhideTaskListSuccess; });
__webpack_require__.d(onboarding_actions_namespaceObject, "optimisticallyCompleteTaskRequest", function() { return optimisticallyCompleteTaskRequest; });
__webpack_require__.d(onboarding_actions_namespaceObject, "setPaymentMethods", function() { return setPaymentMethods; });
__webpack_require__.d(onboarding_actions_namespaceObject, "setEmailPrefill", function() { return setEmailPrefill; });
__webpack_require__.d(onboarding_actions_namespaceObject, "actionTaskError", function() { return actionTaskError; });
__webpack_require__.d(onboarding_actions_namespaceObject, "actionTaskRequest", function() { return actionTaskRequest; });
__webpack_require__.d(onboarding_actions_namespaceObject, "actionTaskSuccess", function() { return actionTaskSuccess; });
__webpack_require__.d(onboarding_actions_namespaceObject, "getProductTypesSuccess", function() { return getProductTypesSuccess; });
__webpack_require__.d(onboarding_actions_namespaceObject, "getProductTypesError", function() { return getProductTypesError; });
__webpack_require__.d(onboarding_actions_namespaceObject, "updateProfileItems", function() { return updateProfileItems; });
__webpack_require__.d(onboarding_actions_namespaceObject, "snoozeTask", function() { return snoozeTask; });
__webpack_require__.d(onboarding_actions_namespaceObject, "undoSnoozeTask", function() { return undoSnoozeTask; });
__webpack_require__.d(onboarding_actions_namespaceObject, "dismissTask", function() { return dismissTask; });
__webpack_require__.d(onboarding_actions_namespaceObject, "undoDismissTask", function() { return undoDismissTask; });
__webpack_require__.d(onboarding_actions_namespaceObject, "hideTaskList", function() { return hideTaskList; });
__webpack_require__.d(onboarding_actions_namespaceObject, "unhideTaskList", function() { return unhideTaskList; });
__webpack_require__.d(onboarding_actions_namespaceObject, "optimisticallyCompleteTask", function() { return optimisticallyCompleteTask; });
__webpack_require__.d(onboarding_actions_namespaceObject, "actionTask", function() { return actionTask; });

// NAMESPACE OBJECT: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/onboarding/resolvers.js
var onboarding_resolvers_namespaceObject = {};
__webpack_require__.r(onboarding_resolvers_namespaceObject);
__webpack_require__.d(onboarding_resolvers_namespaceObject, "getProfileItems", function() { return resolvers_getProfileItems; });
__webpack_require__.d(onboarding_resolvers_namespaceObject, "getEmailPrefill", function() { return resolvers_getEmailPrefill; });
__webpack_require__.d(onboarding_resolvers_namespaceObject, "getTaskLists", function() { return resolvers_getTaskLists; });
__webpack_require__.d(onboarding_resolvers_namespaceObject, "getTaskListsByIds", function() { return resolvers_getTaskListsByIds; });
__webpack_require__.d(onboarding_resolvers_namespaceObject, "getTaskList", function() { return resolvers_getTaskList; });
__webpack_require__.d(onboarding_resolvers_namespaceObject, "getTask", function() { return resolvers_getTask; });
__webpack_require__.d(onboarding_resolvers_namespaceObject, "getPaymentGatewaySuggestions", function() { return resolvers_getPaymentGatewaySuggestions; });
__webpack_require__.d(onboarding_resolvers_namespaceObject, "getFreeExtensions", function() { return resolvers_getFreeExtensions; });
__webpack_require__.d(onboarding_resolvers_namespaceObject, "getProductTypes", function() { return resolvers_getProductTypes; });

// NAMESPACE OBJECT: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/reviews/selectors.js
var reviews_selectors_namespaceObject = {};
__webpack_require__.r(reviews_selectors_namespaceObject);
__webpack_require__.d(reviews_selectors_namespaceObject, "getReviews", function() { return getReviews; });
__webpack_require__.d(reviews_selectors_namespaceObject, "getReviewsTotalCount", function() { return getReviewsTotalCount; });
__webpack_require__.d(reviews_selectors_namespaceObject, "getReviewsError", function() { return getReviewsError; });

// NAMESPACE OBJECT: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/reviews/actions.js
var reviews_actions_namespaceObject = {};
__webpack_require__.r(reviews_actions_namespaceObject);
__webpack_require__.d(reviews_actions_namespaceObject, "updateReviews", function() { return updateReviews; });
__webpack_require__.d(reviews_actions_namespaceObject, "updateReview", function() { return updateReview; });
__webpack_require__.d(reviews_actions_namespaceObject, "deleteReview", function() { return deleteReview; });
__webpack_require__.d(reviews_actions_namespaceObject, "setReviewIsUpdating", function() { return setReviewIsUpdating; });
__webpack_require__.d(reviews_actions_namespaceObject, "setReview", function() { return setReview; });
__webpack_require__.d(reviews_actions_namespaceObject, "setError", function() { return reviews_actions_setError; });

// NAMESPACE OBJECT: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/reviews/resolvers.js
var reviews_resolvers_namespaceObject = {};
__webpack_require__.r(reviews_resolvers_namespaceObject);
__webpack_require__.d(reviews_resolvers_namespaceObject, "getReviews", function() { return resolvers_getReviews; });
__webpack_require__.d(reviews_resolvers_namespaceObject, "getReviewsTotalCount", function() { return resolvers_getReviewsTotalCount; });

// NAMESPACE OBJECT: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/notes/selectors.js
var notes_selectors_namespaceObject = {};
__webpack_require__.r(notes_selectors_namespaceObject);
__webpack_require__.d(notes_selectors_namespaceObject, "getNotes", function() { return getNotes; });
__webpack_require__.d(notes_selectors_namespaceObject, "getNotesError", function() { return getNotesError; });
__webpack_require__.d(notes_selectors_namespaceObject, "isNotesRequesting", function() { return isNotesRequesting; });

// NAMESPACE OBJECT: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/notes/actions.js
var notes_actions_namespaceObject = {};
__webpack_require__.r(notes_actions_namespaceObject);
__webpack_require__.d(notes_actions_namespaceObject, "triggerNoteAction", function() { return triggerNoteAction; });
__webpack_require__.d(notes_actions_namespaceObject, "removeNote", function() { return removeNote; });
__webpack_require__.d(notes_actions_namespaceObject, "removeAllNotes", function() { return removeAllNotes; });
__webpack_require__.d(notes_actions_namespaceObject, "batchUpdateNotes", function() { return batchUpdateNotes; });
__webpack_require__.d(notes_actions_namespaceObject, "updateNote", function() { return updateNote; });
__webpack_require__.d(notes_actions_namespaceObject, "setNote", function() { return setNote; });
__webpack_require__.d(notes_actions_namespaceObject, "setNoteIsUpdating", function() { return setNoteIsUpdating; });
__webpack_require__.d(notes_actions_namespaceObject, "setNotes", function() { return setNotes; });
__webpack_require__.d(notes_actions_namespaceObject, "setNotesQuery", function() { return setNotesQuery; });
__webpack_require__.d(notes_actions_namespaceObject, "setError", function() { return notes_actions_setError; });
__webpack_require__.d(notes_actions_namespaceObject, "setIsRequesting", function() { return notes_actions_setIsRequesting; });

// NAMESPACE OBJECT: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/notes/resolvers.js
var notes_resolvers_namespaceObject = {};
__webpack_require__.r(notes_resolvers_namespaceObject);
__webpack_require__.d(notes_resolvers_namespaceObject, "getNotes", function() { return resolvers_getNotes; });

// NAMESPACE OBJECT: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/reports/selectors.js
var reports_selectors_namespaceObject = {};
__webpack_require__.r(reports_selectors_namespaceObject);
__webpack_require__.d(reports_selectors_namespaceObject, "getReportItemsError", function() { return selectors_getReportItemsError; });
__webpack_require__.d(reports_selectors_namespaceObject, "getReportItems", function() { return selectors_getReportItems; });
__webpack_require__.d(reports_selectors_namespaceObject, "getReportStats", function() { return selectors_getReportStats; });
__webpack_require__.d(reports_selectors_namespaceObject, "getReportStatsError", function() { return selectors_getReportStatsError; });

// NAMESPACE OBJECT: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/reports/actions.js
var reports_actions_namespaceObject = {};
__webpack_require__.r(reports_actions_namespaceObject);
__webpack_require__.d(reports_actions_namespaceObject, "setReportItemsError", function() { return setReportItemsError; });
__webpack_require__.d(reports_actions_namespaceObject, "setReportItems", function() { return setReportItems; });
__webpack_require__.d(reports_actions_namespaceObject, "setReportStats", function() { return setReportStats; });
__webpack_require__.d(reports_actions_namespaceObject, "setReportStatsError", function() { return setReportStatsError; });

// NAMESPACE OBJECT: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/reports/resolvers.js
var reports_resolvers_namespaceObject = {};
__webpack_require__.r(reports_resolvers_namespaceObject);
__webpack_require__.d(reports_resolvers_namespaceObject, "getReportItems", function() { return resolvers_getReportItems; });
__webpack_require__.d(reports_resolvers_namespaceObject, "getReportStats", function() { return resolvers_getReportStats; });

// NAMESPACE OBJECT: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/items/selectors.js
var items_selectors_namespaceObject = {};
__webpack_require__.r(items_selectors_namespaceObject);
__webpack_require__.d(items_selectors_namespaceObject, "getItems", function() { return selectors_getItems; });
__webpack_require__.d(items_selectors_namespaceObject, "getItemsTotalCount", function() { return getItemsTotalCount; });
__webpack_require__.d(items_selectors_namespaceObject, "getItemsError", function() { return selectors_getItemsError; });

// NAMESPACE OBJECT: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/items/actions.js
var items_actions_namespaceObject = {};
__webpack_require__.r(items_actions_namespaceObject);
__webpack_require__.d(items_actions_namespaceObject, "setItem", function() { return setItem; });
__webpack_require__.d(items_actions_namespaceObject, "setItems", function() { return setItems; });
__webpack_require__.d(items_actions_namespaceObject, "setItemsTotalCount", function() { return setItemsTotalCount; });
__webpack_require__.d(items_actions_namespaceObject, "setError", function() { return items_actions_setError; });
__webpack_require__.d(items_actions_namespaceObject, "updateProductStock", function() { return updateProductStock; });
__webpack_require__.d(items_actions_namespaceObject, "createProductFromTemplate", function() { return createProductFromTemplate; });

// NAMESPACE OBJECT: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/items/resolvers.js
var items_resolvers_namespaceObject = {};
__webpack_require__.r(items_resolvers_namespaceObject);
__webpack_require__.d(items_resolvers_namespaceObject, "getItems", function() { return resolvers_getItems; });
__webpack_require__.d(items_resolvers_namespaceObject, "getReviewsTotalCount", function() { return items_resolvers_getReviewsTotalCount; });
__webpack_require__.d(items_resolvers_namespaceObject, "getItemsTotalCount", function() { return resolvers_getItemsTotalCount; });

// NAMESPACE OBJECT: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/countries/selectors.js
var countries_selectors_namespaceObject = {};
__webpack_require__.r(countries_selectors_namespaceObject);
__webpack_require__.d(countries_selectors_namespaceObject, "getLocales", function() { return getLocales; });
__webpack_require__.d(countries_selectors_namespaceObject, "getLocale", function() { return getLocale; });
__webpack_require__.d(countries_selectors_namespaceObject, "getCountries", function() { return getCountries; });
__webpack_require__.d(countries_selectors_namespaceObject, "getCountry", function() { return getCountry; });

// NAMESPACE OBJECT: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/countries/actions.js
var countries_actions_namespaceObject = {};
__webpack_require__.r(countries_actions_namespaceObject);
__webpack_require__.d(countries_actions_namespaceObject, "getLocalesSuccess", function() { return getLocalesSuccess; });
__webpack_require__.d(countries_actions_namespaceObject, "getLocalesError", function() { return getLocalesError; });
__webpack_require__.d(countries_actions_namespaceObject, "getCountriesSuccess", function() { return getCountriesSuccess; });
__webpack_require__.d(countries_actions_namespaceObject, "getCountriesError", function() { return getCountriesError; });

// NAMESPACE OBJECT: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/countries/resolvers.js
var countries_resolvers_namespaceObject = {};
__webpack_require__.r(countries_resolvers_namespaceObject);
__webpack_require__.d(countries_resolvers_namespaceObject, "getLocale", function() { return resolvers_getLocale; });
__webpack_require__.d(countries_resolvers_namespaceObject, "getLocales", function() { return resolvers_getLocales; });
__webpack_require__.d(countries_resolvers_namespaceObject, "getCountry", function() { return resolvers_getCountry; });
__webpack_require__.d(countries_resolvers_namespaceObject, "getCountries", function() { return resolvers_getCountries; });

// NAMESPACE OBJECT: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/navigation/selectors.js
var navigation_selectors_namespaceObject = {};
__webpack_require__.r(navigation_selectors_namespaceObject);
__webpack_require__.d(navigation_selectors_namespaceObject, "getMenuItems", function() { return getMenuItems; });
__webpack_require__.d(navigation_selectors_namespaceObject, "getFavorites", function() { return getFavorites; });
__webpack_require__.d(navigation_selectors_namespaceObject, "isNavigationRequesting", function() { return isNavigationRequesting; });
__webpack_require__.d(navigation_selectors_namespaceObject, "getPersistedQuery", function() { return getPersistedQuery; });

// NAMESPACE OBJECT: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/navigation/actions.js
var navigation_actions_namespaceObject = {};
__webpack_require__.r(navigation_actions_namespaceObject);
__webpack_require__.d(navigation_actions_namespaceObject, "setMenuItems", function() { return actions_setMenuItems; });
__webpack_require__.d(navigation_actions_namespaceObject, "addMenuItems", function() { return addMenuItems; });
__webpack_require__.d(navigation_actions_namespaceObject, "getFavoritesFailure", function() { return getFavoritesFailure; });
__webpack_require__.d(navigation_actions_namespaceObject, "getFavoritesRequest", function() { return getFavoritesRequest; });
__webpack_require__.d(navigation_actions_namespaceObject, "getFavoritesSuccess", function() { return getFavoritesSuccess; });
__webpack_require__.d(navigation_actions_namespaceObject, "addFavoriteRequest", function() { return addFavoriteRequest; });
__webpack_require__.d(navigation_actions_namespaceObject, "addFavoriteFailure", function() { return addFavoriteFailure; });
__webpack_require__.d(navigation_actions_namespaceObject, "addFavoriteSuccess", function() { return addFavoriteSuccess; });
__webpack_require__.d(navigation_actions_namespaceObject, "removeFavoriteRequest", function() { return removeFavoriteRequest; });
__webpack_require__.d(navigation_actions_namespaceObject, "removeFavoriteFailure", function() { return removeFavoriteFailure; });
__webpack_require__.d(navigation_actions_namespaceObject, "removeFavoriteSuccess", function() { return removeFavoriteSuccess; });
__webpack_require__.d(navigation_actions_namespaceObject, "onLoad", function() { return actions_onLoad; });
__webpack_require__.d(navigation_actions_namespaceObject, "onHistoryChange", function() { return actions_onHistoryChange; });
__webpack_require__.d(navigation_actions_namespaceObject, "addFavorite", function() { return addFavorite; });
__webpack_require__.d(navigation_actions_namespaceObject, "removeFavorite", function() { return removeFavorite; });

// NAMESPACE OBJECT: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/navigation/resolvers.js
var navigation_resolvers_namespaceObject = {};
__webpack_require__.r(navigation_resolvers_namespaceObject);
__webpack_require__.d(navigation_resolvers_namespaceObject, "getFavorites", function() { return resolvers_getFavorites; });

// NAMESPACE OBJECT: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/payment-gateways/actions.js
var payment_gateways_actions_namespaceObject = {};
__webpack_require__.r(payment_gateways_actions_namespaceObject);
__webpack_require__.d(payment_gateways_actions_namespaceObject, "getPaymentGatewaysRequest", function() { return getPaymentGatewaysRequest; });
__webpack_require__.d(payment_gateways_actions_namespaceObject, "getPaymentGatewaysSuccess", function() { return getPaymentGatewaysSuccess; });
__webpack_require__.d(payment_gateways_actions_namespaceObject, "getPaymentGatewaysError", function() { return getPaymentGatewaysError; });
__webpack_require__.d(payment_gateways_actions_namespaceObject, "getPaymentGatewayRequest", function() { return getPaymentGatewayRequest; });
__webpack_require__.d(payment_gateways_actions_namespaceObject, "getPaymentGatewayError", function() { return getPaymentGatewayError; });
__webpack_require__.d(payment_gateways_actions_namespaceObject, "getPaymentGatewaySuccess", function() { return getPaymentGatewaySuccess; });
__webpack_require__.d(payment_gateways_actions_namespaceObject, "updatePaymentGatewaySuccess", function() { return updatePaymentGatewaySuccess; });
__webpack_require__.d(payment_gateways_actions_namespaceObject, "updatePaymentGatewayRequest", function() { return updatePaymentGatewayRequest; });
__webpack_require__.d(payment_gateways_actions_namespaceObject, "updatePaymentGatewayError", function() { return updatePaymentGatewayError; });
__webpack_require__.d(payment_gateways_actions_namespaceObject, "updatePaymentGateway", function() { return updatePaymentGateway; });

// NAMESPACE OBJECT: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/payment-gateways/resolvers.js
var payment_gateways_resolvers_namespaceObject = {};
__webpack_require__.r(payment_gateways_resolvers_namespaceObject);
__webpack_require__.d(payment_gateways_resolvers_namespaceObject, "getPaymentGateways", function() { return getPaymentGateways; });
__webpack_require__.d(payment_gateways_resolvers_namespaceObject, "getPaymentGateway", function() { return getPaymentGateway; });

// NAMESPACE OBJECT: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/payment-gateways/selectors.js
var payment_gateways_selectors_namespaceObject = {};
__webpack_require__.r(payment_gateways_selectors_namespaceObject);
__webpack_require__.d(payment_gateways_selectors_namespaceObject, "getPaymentGateway", function() { return selectors_getPaymentGateway; });
__webpack_require__.d(payment_gateways_selectors_namespaceObject, "getPaymentGateways", function() { return selectors_getPaymentGateways; });
__webpack_require__.d(payment_gateways_selectors_namespaceObject, "getPaymentGatewayError", function() { return selectors_getPaymentGatewayError; });
__webpack_require__.d(payment_gateways_selectors_namespaceObject, "isPaymentGatewayUpdating", function() { return isPaymentGatewayUpdating; });

// NAMESPACE OBJECT: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/export/selectors.js
var export_selectors_namespaceObject = {};
__webpack_require__.r(export_selectors_namespaceObject);
__webpack_require__.d(export_selectors_namespaceObject, "isExportRequesting", function() { return isExportRequesting; });
__webpack_require__.d(export_selectors_namespaceObject, "getExportId", function() { return getExportId; });
__webpack_require__.d(export_selectors_namespaceObject, "getError", function() { return getError; });

// NAMESPACE OBJECT: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/export/actions.js
var export_actions_namespaceObject = {};
__webpack_require__.r(export_actions_namespaceObject);
__webpack_require__.d(export_actions_namespaceObject, "setExportId", function() { return setExportId; });
__webpack_require__.d(export_actions_namespaceObject, "setIsRequesting", function() { return export_actions_setIsRequesting; });
__webpack_require__.d(export_actions_namespaceObject, "setError", function() { return export_actions_setError; });
__webpack_require__.d(export_actions_namespaceObject, "startExport", function() { return startExport; });

// NAMESPACE OBJECT: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/import/selectors.js
var import_selectors_namespaceObject = {};
__webpack_require__.r(import_selectors_namespaceObject);
__webpack_require__.d(import_selectors_namespaceObject, "getImportStarted", function() { return getImportStarted; });
__webpack_require__.d(import_selectors_namespaceObject, "getFormSettings", function() { return getFormSettings; });
__webpack_require__.d(import_selectors_namespaceObject, "getImportStatus", function() { return getImportStatus; });
__webpack_require__.d(import_selectors_namespaceObject, "getImportTotals", function() { return getImportTotals; });
__webpack_require__.d(import_selectors_namespaceObject, "getImportError", function() { return getImportError; });

// NAMESPACE OBJECT: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/import/actions.js
var import_actions_namespaceObject = {};
__webpack_require__.r(import_actions_namespaceObject);
__webpack_require__.d(import_actions_namespaceObject, "setImportStarted", function() { return setImportStarted; });
__webpack_require__.d(import_actions_namespaceObject, "setImportPeriod", function() { return setImportPeriod; });
__webpack_require__.d(import_actions_namespaceObject, "setSkipPrevious", function() { return setSkipPrevious; });
__webpack_require__.d(import_actions_namespaceObject, "setImportStatus", function() { return setImportStatus; });
__webpack_require__.d(import_actions_namespaceObject, "setImportTotals", function() { return setImportTotals; });
__webpack_require__.d(import_actions_namespaceObject, "setImportError", function() { return setImportError; });
__webpack_require__.d(import_actions_namespaceObject, "updateImportation", function() { return updateImportation; });

// NAMESPACE OBJECT: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/import/resolvers.js
var import_resolvers_namespaceObject = {};
__webpack_require__.r(import_resolvers_namespaceObject);
__webpack_require__.d(import_resolvers_namespaceObject, "getImportStatus", function() { return resolvers_getImportStatus; });
__webpack_require__.d(import_resolvers_namespaceObject, "getImportTotals", function() { return resolvers_getImportTotals; });

// EXTERNAL MODULE: external ["wp","coreData"]
var external_wp_coreData_ = __webpack_require__(476);

// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/types/wp-data.js

// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/types/rule-processor.js

// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/types/api.js

// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/types/index.js



// EXTERNAL MODULE: external ["wp","data"]
var external_wp_data_ = __webpack_require__(8);

// EXTERNAL MODULE: external ["wp","dataControls"]
var external_wp_dataControls_ = __webpack_require__(10);

// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/settings/constants.js
const STORE_NAME = 'wc/admin/settings';
// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/utils.js
function getResourceName(prefix, identifier) {
  const identifierString = JSON.stringify(identifier, Object.keys(identifier).sort());
  return `${prefix}:${identifierString}`;
}
function getResourcePrefix(resourceName) {
  const hasPrefixIndex = resourceName.indexOf(':');
  return hasPrefixIndex < 0 ? resourceName : resourceName.substring(0, hasPrefixIndex);
}
function isResourcePrefix(resourceName, prefix) {
  const resourcePrefix = getResourcePrefix(resourceName);
  return resourcePrefix === prefix;
}
function getResourceIdentifier(resourceName) {
  const identifierString = resourceName.substring(resourceName.indexOf(':') + 1);
  return JSON.parse(identifierString);
}
// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/settings/selectors.js
/**
 * Internal dependencies
 */

const getSettingsGroupNames = state => {
  const groupNames = new Set(Object.keys(state).map(resourceName => {
    return getResourcePrefix(resourceName);
  }));
  return [...groupNames];
};
const getSettings = (state, group) => {
  const settings = {};
  const settingIds = state[group] && state[group].data || [];

  if (settingIds.length === 0) {
    return settings;
  }

  settingIds.forEach(id => {
    settings[id] = state[getResourceName(group, id)].data;
  });
  return settings;
};
const getDirtyKeys = (state, group) => {
  return state[group].dirty || [];
};
const selectors_getIsDirty = function (state, group) {
  let keys = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : [];
  const dirtyMap = getDirtyKeys(state, group); // if empty array bail

  if (dirtyMap.length === 0) {
    return false;
  } // if at least one of the keys is in the dirty map then the state is dirty
  // meaning it hasn't been persisted.


  return keys.some(key => dirtyMap.includes(key));
};
const selectors_getSettingsForGroup = (state, group, keys) => {
  const allSettings = getSettings(state, group);
  return keys.reduce((accumulator, key) => {
    accumulator[key] = allSettings[key] || {};
    return accumulator;
  }, {});
};
const selectors_isUpdateSettingsRequesting = (state, group) => {
  return state[group] && Boolean(state[group].isRequesting);
};
/**
 * Retrieves a setting value from the setting store.
 *
 * @param {Object}   state                        State param added by wp.data.
 * @param {string}   group                        The settings group.
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

function getSetting(state, group, name) {
  let fallback = arguments.length > 3 && arguments[3] !== undefined ? arguments[3] : false;
  let filter = arguments.length > 4 && arguments[4] !== undefined ? arguments[4] : val => val;
  const resourceName = getResourceName(group, name);
  const value = state[resourceName] && state[resourceName].data || fallback;
  return filter(value, fallback);
}
const selectors_getLastSettingsErrorForGroup = (state, group) => {
  const settingsIds = state[group].data;

  if (settingsIds.length === 0) {
    return state[group].error;
  }

  return [...settingsIds].pop().error;
};
const getSettingsError = (state, group, id) => {
  if (!id) {
    return state[group] && state[group].error || false;
  }

  return state[getResourceName(group, id)].error || false;
};
// EXTERNAL MODULE: external ["wp","i18n"]
var external_wp_i18n_ = __webpack_require__(2);

// EXTERNAL MODULE: external "lodash"
var external_lodash_ = __webpack_require__(5);

// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/constants.js
const JETPACK_NAMESPACE = '/jetpack/v4';
const NAMESPACE = '/wc-analytics';
const WC_ADMIN_NAMESPACE = '/wc-admin';
const WCS_NAMESPACE = '/wc/v1'; // WCS endpoints like Stripe are not avaiable on later /wc versions
// WordPress & WooCommerce both set a hard limit of 100 for the per_page parameter

const MAX_PER_PAGE = 100;
const SECOND = 1000;
const MINUTE = 60 * SECOND;
const HOUR = 60 * MINUTE;
const DAY = 24 * HOUR;
const WEEK = 7 * DAY;
const MONTH = 365 * DAY / 12;
const DEFAULT_REQUIREMENT = {
  timeout: 1 * MINUTE,
  freshness: 30 * MINUTE
};
const DEFAULT_ACTIONABLE_STATUSES = ['processing', 'on-hold'];
const QUERY_DEFAULTS = {
  pageSize: 25,
  period: 'month',
  compare: 'previous_year',
  noteTypes: ['info', 'marketing', 'survey', 'warning']
};
// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/settings/action-types.js
const TYPES = {
  UPDATE_SETTINGS_FOR_GROUP: 'UPDATE_SETTINGS_FOR_GROUP',
  UPDATE_ERROR_FOR_GROUP: 'UPDATE_ERROR_FOR_GROUP',
  CLEAR_SETTINGS: 'CLEAR_SETTINGS',
  SET_IS_REQUESTING: 'SET_IS_REQUESTING',
  CLEAR_IS_DIRTY: 'CLEAR_IS_DIRTY'
};
/* harmony default export */ var action_types = (TYPES);
// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/settings/actions.js
/**
 * External dependencies
 */




/**
 * Internal dependencies
 */



 // Can be removed in WP 5.9, wp.data is supported in >5.7.

const resolveSelect = external_wp_data_["controls"] && external_wp_data_["controls"].resolveSelect ? external_wp_data_["controls"].resolveSelect : external_wp_dataControls_["select"];
function actions_updateSettingsForGroup(group, data) {
  let time = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : new Date();
  return {
    type: action_types.UPDATE_SETTINGS_FOR_GROUP,
    group,
    data,
    time
  };
}
function updateErrorForGroup(group, data, error) {
  let time = arguments.length > 3 && arguments[3] !== undefined ? arguments[3] : new Date();
  return {
    type: action_types.UPDATE_ERROR_FOR_GROUP,
    group,
    data,
    error,
    time
  };
}
function setIsRequesting(group, isRequesting) {
  return {
    type: action_types.SET_IS_REQUESTING,
    group,
    isRequesting
  };
}
function actions_clearIsDirty(group) {
  return {
    type: action_types.CLEAR_IS_DIRTY,
    group
  };
} // allows updating and persisting immediately in one action.

function* actions_updateAndPersistSettingsForGroup(group, data) {
  yield actions_updateSettingsForGroup(group, data);
  yield* actions_persistSettingsForGroup(group);
} // this would replace setSettingsForGroup

function* actions_persistSettingsForGroup(group) {
  // first dispatch the is persisting action
  yield setIsRequesting(group, true); // get all dirty keys with select control

  const dirtyKeys = yield resolveSelect(STORE_NAME, 'getDirtyKeys', group); // if there is nothing dirty, bail

  if (dirtyKeys.length === 0) {
    yield setIsRequesting(group, false);
    return;
  } // get data slice for keys


  const dirtyData = yield resolveSelect(STORE_NAME, 'getSettingsForGroup', group, dirtyKeys);
  const url = `${NAMESPACE}/settings/${group}/batch`;
  const update = dirtyKeys.reduce((updates, key) => {
    const u = Object.keys(dirtyData[key]).map(k => {
      return {
        id: k,
        value: dirtyData[key][k]
      };
    });
    return Object(external_lodash_["concat"])(updates, u);
  }, []);

  try {
    const results = yield Object(external_wp_dataControls_["apiFetch"])({
      path: url,
      method: 'POST',
      data: {
        update
      }
    });
    yield setIsRequesting(group, false);

    if (!results) {
      throw new Error(Object(external_wp_i18n_["__"])('There was a problem updating your settings.', 'woocommerce-admin'));
    } // remove dirtyKeys from map - note we're only doing this if there is no error.


    yield actions_clearIsDirty(group);
  } catch (e) {
    yield updateErrorForGroup(group, null, e);
    yield setIsRequesting(group, false);
    throw e;
  }
}
function clearSettings() {
  return {
    type: action_types.CLEAR_SETTINGS
  };
}
// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/settings/resolvers.js
/**
 * External dependencies
 */


/**
 * Internal dependencies
 */



 // Can be removed in WP 5.9.

const resolvers_dispatch = external_wp_data_["controls"] && external_wp_data_["controls"].dispatch ? external_wp_data_["controls"].dispatch : external_wp_dataControls_["dispatch"];

function settingsToSettingsResource(settings) {
  return settings.reduce((resource, setting) => {
    resource[setting.id] = setting.value;
    return resource;
  }, {});
}

function* resolvers_getSettings(group) {
  yield resolvers_dispatch(STORE_NAME, 'setIsRequesting', group, true);

  try {
    const url = NAMESPACE + '/settings/' + group;
    const results = yield Object(external_wp_dataControls_["apiFetch"])({
      path: url,
      method: 'GET'
    });
    const resource = settingsToSettingsResource(results);
    return actions_updateSettingsForGroup(group, {
      [group]: resource
    });
  } catch (error) {
    return updateErrorForGroup(group, null, error.message);
  }
}
function* resolvers_getSettingsForGroup(group) {
  return resolvers_getSettings(group);
}
// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/settings/reducer.js
/**
 * External dependencies
 */

/**
 * Internal dependencies
 */




const updateGroupDataInNewState = (newState, _ref) => {
  let {
    group,
    groupIds,
    data,
    time,
    error
  } = _ref;
  groupIds.forEach(id => {
    newState[getResourceName(group, id)] = {
      data: data[id],
      lastReceived: time,
      error
    };
  });
  return newState;
};

const receiveSettings = function () {
  let state = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
  let {
    type,
    group,
    data,
    error,
    time,
    isRequesting
  } = arguments.length > 1 ? arguments[1] : undefined;
  const newState = {};

  switch (type) {
    case action_types.SET_IS_REQUESTING:
      state = { ...state,
        [group]: { ...state[group],
          isRequesting
        }
      };
      break;

    case action_types.CLEAR_IS_DIRTY:
      state = { ...state,
        [group]: { ...state[group],
          dirty: []
        }
      };
      break;

    case action_types.UPDATE_SETTINGS_FOR_GROUP:
    case action_types.UPDATE_ERROR_FOR_GROUP:
      const groupIds = data ? Object.keys(data) : [];

      if (data === null) {
        state = { ...state,
          [group]: {
            data: state[group] ? state[group].data : [],
            error,
            lastReceived: time
          }
        };
      } else {
        state = { ...state,
          [group]: {
            data: state[group] && state[group].data ? [...state[group].data, ...groupIds] : groupIds,
            error,
            lastReceived: time,
            isRequesting: false,
            dirty: state[group] && state[group].dirty ? Object(external_lodash_["union"])(state[group].dirty, groupIds) : groupIds
          },
          ...updateGroupDataInNewState(newState, {
            group,
            groupIds,
            data,
            time,
            error
          })
        };
      }

      break;

    case action_types.CLEAR_SETTINGS:
      state = {};
  }

  return state;
};

/* harmony default export */ var reducer = (receiveSettings);
// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/settings/index.js
/**
 * External dependencies
 */


/**
 * Internal dependencies
 */






Object(external_wp_data_["registerStore"])(STORE_NAME, {
  reducer: reducer,
  actions: actions_namespaceObject,
  controls: external_wp_dataControls_["controls"],
  selectors: selectors_namespaceObject,
  resolvers: resolvers_namespaceObject
});
const SETTINGS_STORE_NAME = STORE_NAME;
// EXTERNAL MODULE: external ["wp","compose"]
var external_wp_compose_ = __webpack_require__(14);

// EXTERNAL MODULE: external ["wp","element"]
var external_wp_element_ = __webpack_require__(0);

// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/settings/with-settings-hydration.js
/**
 * External dependencies
 */



/**
 * Internal dependencies
 */


const withSettingsHydration = (group, settings) => Object(external_wp_compose_["createHigherOrderComponent"])(OriginalComponent => props => {
  const settingsRef = Object(external_wp_element_["useRef"])(settings);
  Object(external_wp_data_["useSelect"])((select, registry) => {
    if (!settingsRef.current) {
      return;
    }

    const {
      isResolving,
      hasFinishedResolution
    } = select(STORE_NAME);
    const {
      startResolution,
      finishResolution,
      updateSettingsForGroup,
      clearIsDirty
    } = registry.dispatch(STORE_NAME);

    if (!isResolving('getSettings', [group]) && !hasFinishedResolution('getSettings', [group])) {
      startResolution('getSettings', [group]);
      updateSettingsForGroup(group, settingsRef.current);
      clearIsDirty(group);
      finishResolution('getSettings', [group]);
    }
  }, []);
  return Object(external_wp_element_["createElement"])(OriginalComponent, { ...props
  });
}, 'withSettingsHydration');
// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/settings/use-settings.js
/**
 * External dependencies
 */


/**
 * Internal dependencies
 */


const useSettings = function (group) {
  let settingsKeys = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : [];
  const {
    requestedSettings,
    settingsError,
    isRequesting,
    isDirty
  } = Object(external_wp_data_["useSelect"])(select => {
    const {
      getLastSettingsErrorForGroup,
      getSettingsForGroup,
      getIsDirty,
      isUpdateSettingsRequesting
    } = select(STORE_NAME);
    return {
      requestedSettings: getSettingsForGroup(group, settingsKeys),
      settingsError: Boolean(getLastSettingsErrorForGroup(group)),
      isRequesting: isUpdateSettingsRequesting(group),
      isDirty: getIsDirty(group, settingsKeys)
    };
  }, [group, ...settingsKeys.sort()]);
  const {
    persistSettingsForGroup,
    updateAndPersistSettingsForGroup,
    updateSettingsForGroup
  } = Object(external_wp_data_["useDispatch"])(STORE_NAME);
  const updateSettings = Object(external_wp_element_["useCallback"])((name, data) => {
    updateSettingsForGroup(group, {
      [name]: data
    });
  }, [group]);
  const persistSettings = Object(external_wp_element_["useCallback"])(() => {
    // this action would simply persist all settings marked as dirty in the
    // store state and then remove the dirty record in the isDirtyMap
    persistSettingsForGroup(group);
  }, [group]);
  const updateAndPersistSettings = Object(external_wp_element_["useCallback"])((name, data) => {
    updateAndPersistSettingsForGroup(group, {
      [name]: data
    });
  }, [group]);
  return {
    settingsError,
    isRequesting,
    isDirty,
    ...requestedSettings,
    persistSettings,
    updateAndPersistSettings,
    updateSettings
  };
};
// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/plugins/constants.js
/**
 * External dependencies
 */

const constants_STORE_NAME = 'wc/admin/plugins';
const PAYPAL_NAMESPACE = '/wc-paypal/v1';
/**
 * Plugin slugs and names as key/value pairs.
 */

const pluginNames = {
  'facebook-for-woocommerce': Object(external_wp_i18n_["__"])('Facebook for WooCommerce', 'woocommerce-admin'),
  jetpack: Object(external_wp_i18n_["__"])('Jetpack', 'woocommerce-admin'),
  'klarna-checkout-for-woocommerce': Object(external_wp_i18n_["__"])('Klarna Checkout for WooCommerce', 'woocommerce-admin'),
  'klarna-payments-for-woocommerce': Object(external_wp_i18n_["__"])('Klarna Payments for WooCommerce', 'woocommerce-admin'),
  'mailchimp-for-woocommerce': Object(external_wp_i18n_["__"])('Mailchimp for WooCommerce', 'woocommerce-admin'),
  'creative-mail-by-constant-contact': Object(external_wp_i18n_["__"])('Creative Mail for WooCommerce', 'woocommerce-admin'),
  'woocommerce-gateway-paypal-express-checkout': Object(external_wp_i18n_["__"])('WooCommerce PayPal', 'woocommerce-admin'),
  'woocommerce-gateway-stripe': Object(external_wp_i18n_["__"])('WooCommerce Stripe', 'woocommerce-admin'),
  'woocommerce-payfast-gateway': Object(external_wp_i18n_["__"])('WooCommerce PayFast', 'woocommerce-admin'),
  'woocommerce-payments': Object(external_wp_i18n_["__"])('WooCommerce Payments', 'woocommerce-admin'),
  'woocommerce-services': Object(external_wp_i18n_["__"])('WooCommerce Shipping & Tax', 'woocommerce-admin'),
  'woocommerce-services:shipping': Object(external_wp_i18n_["__"])('WooCommerce Shipping & Tax', 'woocommerce-admin'),
  'woocommerce-services:tax': Object(external_wp_i18n_["__"])('WooCommerce Shipping & Tax', 'woocommerce-admin'),
  'woocommerce-shipstation-integration': Object(external_wp_i18n_["__"])('WooCommerce ShipStation Gateway', 'woocommerce-admin'),
  'woocommerce-mercadopago': Object(external_wp_i18n_["__"])('Mercado Pago payments for WooCommerce', 'woocommerce-admin'),
  'google-listings-and-ads': Object(external_wp_i18n_["__"])('Google Listings and Ads', 'woocommerce-admin'),
  'woo-razorpay': Object(external_wp_i18n_["__"])('Razorpay', 'woocommerce-admin'),
  mailpoet: Object(external_wp_i18n_["__"])('MailPoet', 'woocommerce-admin')
};
// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/plugins/selectors.js
const getActivePlugins = state => {
  return state.active || [];
};
const getInstalledPlugins = state => {
  return state.installed || [];
};
const isPluginsRequesting = (state, selector) => {
  return state.requesting[selector] || false;
};
const getPluginsError = (state, selector) => {
  return state.errors[selector] || false;
};
const isJetpackConnected = state => state.jetpackConnection;
const getJetpackConnectUrl = (state, query) => {
  return state.jetpackConnectUrls[query.redirect_url];
};
const getPluginInstallState = (state, plugin) => {
  if (state.active.includes(plugin)) {
    return 'activated';
  } else if (state.installed.includes(plugin)) {
    return 'installed';
  }

  return 'unavailable';
};
const getPaypalOnboardingStatus = state => state.paypalOnboardingStatus;
const getRecommendedPlugins = (state, type) => {
  return state.recommended[type];
};
// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/plugins/action-types.js
var ACTION_TYPES;

(function (ACTION_TYPES) {
  ACTION_TYPES["UPDATE_ACTIVE_PLUGINS"] = "UPDATE_ACTIVE_PLUGINS";
  ACTION_TYPES["UPDATE_INSTALLED_PLUGINS"] = "UPDATE_INSTALLED_PLUGINS";
  ACTION_TYPES["SET_IS_REQUESTING"] = "SET_IS_REQUESTING";
  ACTION_TYPES["SET_ERROR"] = "SET_ERROR";
  ACTION_TYPES["UPDATE_JETPACK_CONNECTION"] = "UPDATE_JETPACK_CONNECTION";
  ACTION_TYPES["UPDATE_JETPACK_CONNECT_URL"] = "UPDATE_JETPACK_CONNECT_URL";
  ACTION_TYPES["SET_PAYPAL_ONBOARDING_STATUS"] = "SET_PAYPAL_ONBOARDING_STATUS";
  ACTION_TYPES["SET_RECOMMENDED_PLUGINS"] = "SET_RECOMMENDED_PLUGINS";
})(ACTION_TYPES || (ACTION_TYPES = {}));
// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/plugins/actions.js
/**
 * External dependencies
 */



/**
 * Internal dependencies
 */



 // Can be removed in WP 5.9, wp.data is supported in >5.7.

const actions_dispatch = external_wp_data_["controls"] && external_wp_data_["controls"].dispatch ? external_wp_data_["controls"].dispatch : external_wp_dataControls_["dispatch"];
const actions_resolveSelect = external_wp_data_["controls"] && external_wp_data_["controls"].resolveSelect ? external_wp_data_["controls"].resolveSelect : external_wp_dataControls_["select"];

function isWPError(error) {
  return error.errors !== undefined;
}

class PluginError extends Error {
  constructor(message, data) {
    super(message);
    this.data = data;
  }

}

function formatErrors(response) {
  if (isWPError(response)) {
    // Replace the slug with a plugin name if a constant exists.
    Object.keys(response.errors).forEach(plugin => {
      response.errors[plugin] = response.errors[plugin].map(pluginError => {
        return pluginNames[plugin] ? pluginError.replace(`\`${plugin}\``, pluginNames[plugin]) : pluginError;
      });
    });
  } else if (typeof response === 'string') {
    return response;
  } else {
    return response.message;
  }

  return '';
}

const formatErrorMessage = function (pluginErrors) {
  let actionType = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : 'install';
  return Object(external_wp_i18n_["sprintf"])(
  /* translators: %(actionType): install or activate (the plugin). %(pluginName): a plugin slug (e.g. woocommerce-services). %(error): a single error message or in plural a comma separated error message list.*/
  Object(external_wp_i18n_["_n"])('Could not %(actionType)s %(pluginName)s plugin, %(error)s', 'Could not %(actionType)s the following plugins: %(pluginName)s with these Errors: %(error)s', Object.keys(pluginErrors).length || 1, 'woocommerce-admin'), {
    actionType,
    pluginName: Object.keys(pluginErrors).join(', '),
    error: Object.values(pluginErrors).join(', \n')
  });
};

function actions_updateActivePlugins(active) {
  let replace = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : false;
  return {
    type: ACTION_TYPES.UPDATE_ACTIVE_PLUGINS,
    active,
    replace
  };
}
function actions_updateInstalledPlugins(installed) {
  let replace = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : false;
  return {
    type: ACTION_TYPES.UPDATE_INSTALLED_PLUGINS,
    installed,
    replace
  };
}
function actions_setIsRequesting(selector, isRequesting) {
  return {
    type: ACTION_TYPES.SET_IS_REQUESTING,
    selector,
    isRequesting
  };
}
function setError(selector, error) {
  return {
    type: ACTION_TYPES.SET_ERROR,
    selector,
    error
  };
}
function actions_updateIsJetpackConnected(jetpackConnection) {
  return {
    type: ACTION_TYPES.UPDATE_JETPACK_CONNECTION,
    jetpackConnection
  };
}
function updateJetpackConnectUrl(redirectUrl, jetpackConnectUrl) {
  return {
    type: ACTION_TYPES.UPDATE_JETPACK_CONNECT_URL,
    jetpackConnectUrl,
    redirectUrl
  };
}
function* installPlugins(plugins) {
  yield actions_setIsRequesting('installPlugins', true);

  try {
    const results = yield Object(external_wp_dataControls_["apiFetch"])({
      path: `${WC_ADMIN_NAMESPACE}/plugins/install`,
      method: 'POST',
      data: {
        plugins: plugins.join(',')
      }
    });

    if (results.data.installed.length) {
      yield actions_updateInstalledPlugins(results.data.installed);
    }

    if (Object.keys(results.errors.errors).length) {
      throw results.errors.errors;
    }

    yield actions_setIsRequesting('installPlugins', false);
    return results;
  } catch (error) {
    if (error instanceof Error && plugins.length === 1) {
      // Incase of a network error
      error = {
        [plugins[0]]: error.message
      };
    }

    const errors = error;
    yield setError('installPlugins', errors);
    throw new PluginError(formatErrorMessage(errors), errors);
  }
}
function* activatePlugins(plugins) {
  yield actions_setIsRequesting('activatePlugins', true);

  try {
    const results = yield Object(external_wp_dataControls_["apiFetch"])({
      path: `${WC_ADMIN_NAMESPACE}/plugins/activate`,
      method: 'POST',
      data: {
        plugins: plugins.join(',')
      }
    });

    if (results.data.activated.length) {
      yield actions_updateActivePlugins(results.data.activated);
    }

    if (Object.keys(results.errors.errors).length) {
      throw results.errors.errors;
    }

    yield actions_setIsRequesting('activatePlugins', false);
    return results;
  } catch (error) {
    if (error instanceof Error && plugins.length === 1) {
      // Incase of a network error
      error = {
        [plugins[0]]: error.message
      };
    }

    const errors = error;
    yield setError('installPlugins', errors);
    throw new PluginError(formatErrorMessage(errors), errors);
  }
}
function* installAndActivatePlugins(plugins) {
  try {
    const installations = yield actions_dispatch(constants_STORE_NAME, 'installPlugins', plugins);
    const activations = yield actions_dispatch(constants_STORE_NAME, 'activatePlugins', plugins);
    return { ...activations,
      data: { ...activations.data,
        ...installations.data
      }
    };
  } catch (error) {
    throw error;
  }
}
const createErrorNotice = errorMessage => {
  return actions_dispatch('core/notices', 'createNotice', 'error', errorMessage);
};
function* connectToJetpack(getAdminLink) {
  const url = yield actions_resolveSelect(constants_STORE_NAME, 'getJetpackConnectUrl', {
    redirect_url: getAdminLink('admin.php?page=wc-admin')
  });
  const error = yield actions_resolveSelect(constants_STORE_NAME, 'getPluginsError', 'getJetpackConnectUrl');

  if (error) {
    throw new Error(error);
  } else {
    return url;
  }
}
function* installJetpackAndConnect(errorAction, getAdminLink) {
  try {
    yield actions_dispatch(constants_STORE_NAME, 'installPlugins', ['jetpack']);
    yield actions_dispatch(constants_STORE_NAME, 'activatePlugins', ['jetpack']);
    const url = yield actions_dispatch(constants_STORE_NAME, 'connectToJetpack', getAdminLink);
    window.location.href = url;
  } catch (error) {
    if (error instanceof Error) {
      yield errorAction(error.message);
    } else {
      throw error;
    }
  }
}
function* connectToJetpackWithFailureRedirect(failureRedirect, errorAction, getAdminLink) {
  try {
    const url = yield actions_dispatch(constants_STORE_NAME, 'connectToJetpack', getAdminLink);
    window.location.href = url;
  } catch (error) {
    if (error instanceof Error) {
      yield errorAction(error.message);
    } else {
      throw error;
    }

    window.location.href = failureRedirect;
  }
}
function setPaypalOnboardingStatus(status) {
  return {
    type: ACTION_TYPES.SET_PAYPAL_ONBOARDING_STATUS,
    paypalOnboardingStatus: status
  };
}
function setRecommendedPlugins(type, plugins) {
  return {
    type: ACTION_TYPES.SET_RECOMMENDED_PLUGINS,
    recommendedType: type,
    plugins
  };
}
const SUPPORTED_TYPES = ['payments'];
function* dismissRecommendedPlugins(type) {
  if (!SUPPORTED_TYPES.includes(type)) {
    return [];
  }

  const plugins = yield actions_resolveSelect(constants_STORE_NAME, 'getRecommendedPlugins', type);
  yield setRecommendedPlugins(type, []);
  let success;

  try {
    const url = WC_ADMIN_NAMESPACE + '/payment-gateway-suggestions/dismiss';
    success = yield Object(external_wp_dataControls_["apiFetch"])({
      path: url,
      method: 'POST'
    });
  } catch (error) {
    success = false;
  }

  if (!success) {
    // Reset recommended plugins
    yield setRecommendedPlugins(type, plugins);
  }

  return success;
}
// EXTERNAL MODULE: external ["wp","url"]
var external_wp_url_ = __webpack_require__(16);

// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/options/constants.js
const options_constants_STORE_NAME = 'wc/admin/options';
// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/options/selectors.js
/**
 * Get option from state tree.
 *
 * @param {Object} state - Reducer state
 * @param {Array} name - Option name
 */
const getOption = (state, name) => {
  return state[name];
};
/**
 * Determine if an options request resulted in an error.
 *
 * @param {Object} state - Reducer state
 * @param {string} name - Option name
 */

const getOptionsRequestingError = (state, name) => {
  return state.requestingErrors[name] || false;
};
/**
 * Determine if options are being updated.
 *
 * @param {Object} state - Reducer state
 */

const isOptionsUpdating = state => {
  return state.isUpdating || false;
};
/**
 * Determine if an options update resulted in an error.
 *
 * @param {Object} state - Reducer state
 */

const getOptionsUpdatingError = state => {
  return state.updatingError || false;
};
// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/options/action-types.js
const action_types_TYPES = {
  RECEIVE_OPTIONS: 'RECEIVE_OPTIONS',
  SET_IS_REQUESTING: 'SET_IS_REQUESTING',
  SET_IS_UPDATING: 'SET_IS_UPDATING',
  SET_REQUESTING_ERROR: 'SET_REQUESTING_ERROR',
  SET_UPDATING_ERROR: 'SET_UPDATING_ERROR'
};
/* harmony default export */ var options_action_types = (action_types_TYPES);
// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/options/actions.js
/**
 * External dependencies
 */

/**
 * Internal dependencies
 */



function actions_receiveOptions(options) {
  return {
    type: options_action_types.RECEIVE_OPTIONS,
    options
  };
}
function setRequestingError(error, name) {
  return {
    type: options_action_types.SET_REQUESTING_ERROR,
    error,
    name
  };
}
function setUpdatingError(error) {
  return {
    type: options_action_types.SET_UPDATING_ERROR,
    error
  };
}
function setIsUpdating(isUpdating) {
  return {
    type: options_action_types.SET_IS_UPDATING,
    isUpdating
  };
}
function* updateOptions(data) {
  yield setIsUpdating(true);
  yield actions_receiveOptions(data);

  try {
    const results = yield Object(external_wp_dataControls_["apiFetch"])({
      path: WC_ADMIN_NAMESPACE + '/options',
      method: 'POST',
      data
    });
    yield setIsUpdating(false);
    return {
      success: true,
      ...results
    };
  } catch (error) {
    yield setUpdatingError(error);
    return {
      success: false,
      ...error
    };
  }
}
// EXTERNAL MODULE: external ["wp","apiFetch"]
var external_wp_apiFetch_ = __webpack_require__(21);
var external_wp_apiFetch_default = /*#__PURE__*/__webpack_require__.n(external_wp_apiFetch_);

// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/options/controls.js
/**
 * External dependencies
 */


/**
 * Internal dependencies
 */


let optionNames = [];
const fetches = {};
const batchFetch = optionName => {
  return {
    type: 'BATCH_FETCH',
    optionName
  };
};
const controls = { ...external_wp_dataControls_["controls"],

  BATCH_FETCH(_ref) {
    let {
      optionName
    } = _ref;
    optionNames.push(optionName);
    return new Promise(resolve => {
      setTimeout(function () {
        if (fetches[optionName]) {
          return fetches[optionName].then(result => {
            resolve(result);
          });
        } // Get unique option names.


        const names = [...new Set(optionNames)].join(','); // Send request for a group of options.

        const url = WC_ADMIN_NAMESPACE + '/options?options=' + names;
        const fetch = external_wp_apiFetch_default()({
          path: url
        });
        fetch.then(result => resolve(result));
        optionNames.forEach(option => {
          fetches[option] = fetch;
          fetches[option].then(() => {
            // Delete the fetch after to allow wp data to handle cache invalidation.
            delete fetches[option];
          });
        }); // Clear option names after we've sent the request for a group of options.

        optionNames = [];
      }, 1);
    });
  }

};
// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/options/resolvers.js
/**
 * Internal dependencies
 */


/**
 * Request an option value.
 *
 * @param {string} name - Option name
 */

function* resolvers_getOption(name) {
  try {
    const result = yield batchFetch(name);
    yield actions_receiveOptions(result);
  } catch (error) {
    yield setRequestingError(error, name);
  }
}
// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/options/reducer.js
/**
 * Internal dependencies
 */


const optionsReducer = function () {
  let state = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {
    isUpdating: false,
    requestingErrors: {}
  };
  let {
    type,
    options,
    error,
    isUpdating,
    name
  } = arguments.length > 1 ? arguments[1] : undefined;

  switch (type) {
    case options_action_types.RECEIVE_OPTIONS:
      state = { ...state,
        ...options
      };
      break;

    case options_action_types.SET_IS_UPDATING:
      state = { ...state,
        isUpdating
      };
      break;

    case options_action_types.SET_REQUESTING_ERROR:
      state = { ...state,
        requestingErrors: {
          [name]: error
        }
      };
      break;

    case options_action_types.SET_UPDATING_ERROR:
      state = { ...state,
        error,
        updatingError: error,
        isUpdating: false
      };
      break;
  }

  return state;
};

/* harmony default export */ var options_reducer = (optionsReducer);
// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/options/index.js
/**
 * External dependencies
 */

/**
 * Internal dependencies
 */







Object(external_wp_data_["registerStore"])(options_constants_STORE_NAME, {
  reducer: options_reducer,
  actions: options_actions_namespaceObject,
  controls: controls,
  selectors: options_selectors_namespaceObject,
  resolvers: options_resolvers_namespaceObject
});
const OPTIONS_STORE_NAME = options_constants_STORE_NAME;
// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/plugins/resolvers.js
/**
 * External dependencies
 */



/**
 * Internal dependencies
 */




 // Can be removed in WP 5.9, wp.data is supported in >5.7.

const resolvers_resolveSelect = external_wp_data_["controls"] && external_wp_data_["controls"].resolveSelect ? external_wp_data_["controls"].resolveSelect : external_wp_dataControls_["select"];
function* resolvers_getActivePlugins() {
  yield actions_setIsRequesting('getActivePlugins', true);

  try {
    const url = WC_ADMIN_NAMESPACE + '/plugins/active';
    const results = yield Object(external_wp_dataControls_["apiFetch"])({
      path: url,
      method: 'GET'
    });
    yield actions_updateActivePlugins(results.plugins, true);
  } catch (error) {
    yield setError('getActivePlugins', error);
  }
}
function* resolvers_getInstalledPlugins() {
  yield actions_setIsRequesting('getInstalledPlugins', true);

  try {
    const url = WC_ADMIN_NAMESPACE + '/plugins/installed';
    const results = yield Object(external_wp_dataControls_["apiFetch"])({
      path: url,
      method: 'GET'
    });
    yield actions_updateInstalledPlugins(results.plugins, true);
  } catch (error) {
    yield setError('getInstalledPlugins', error);
  }
}
function* resolvers_isJetpackConnected() {
  yield actions_setIsRequesting('isJetpackConnected', true);

  try {
    const url = JETPACK_NAMESPACE + '/connection';
    const results = yield Object(external_wp_dataControls_["apiFetch"])({
      path: url,
      method: 'GET'
    });
    yield actions_updateIsJetpackConnected(results.isActive);
  } catch (error) {
    yield setError('isJetpackConnected', error);
  }

  yield actions_setIsRequesting('isJetpackConnected', false);
}
function* resolvers_getJetpackConnectUrl(query) {
  yield actions_setIsRequesting('getJetpackConnectUrl', true);

  try {
    const url = Object(external_wp_url_["addQueryArgs"])(WC_ADMIN_NAMESPACE + '/plugins/connect-jetpack', query);
    const results = yield Object(external_wp_dataControls_["apiFetch"])({
      path: url,
      method: 'GET'
    });
    yield updateJetpackConnectUrl(query.redirect_url, results.connectAction);
  } catch (error) {
    yield setError('getJetpackConnectUrl', error);
  }

  yield actions_setIsRequesting('getJetpackConnectUrl', false);
}

function* setOnboardingStatusWithOptions() {
  const options = yield resolvers_resolveSelect(OPTIONS_STORE_NAME, 'getOption', 'woocommerce-ppcp-settings');
  const onboarded = options.merchant_email_production && options.merchant_id_production && options.client_id_production && options.client_secret_production;
  yield setPaypalOnboardingStatus({
    production: {
      state: onboarded ? 'onboarded' : 'unknown',
      onboarded: onboarded ? true : false
    }
  });
}

function* resolvers_getPaypalOnboardingStatus() {
  yield actions_setIsRequesting('getPaypalOnboardingStatus', true);
  const errorData = yield resolvers_resolveSelect(constants_STORE_NAME, 'getPluginsError', 'getPaypalOnboardingStatus');

  if (errorData && errorData.data && errorData.data.status === 404) {
    // The get-status request doesn't exist fall back to using options.
    yield setOnboardingStatusWithOptions();
  } else {
    try {
      const url = PAYPAL_NAMESPACE + '/onboarding/get-status';
      const results = yield Object(external_wp_dataControls_["apiFetch"])({
        path: url,
        method: 'GET'
      });
      yield setPaypalOnboardingStatus(results);
    } catch (error) {
      yield setOnboardingStatusWithOptions();
      yield setError('getPaypalOnboardingStatus', error);
    }
  }

  yield actions_setIsRequesting('getPaypalOnboardingStatus', false);
}
const resolvers_SUPPORTED_TYPES = ['payments'];
function* resolvers_getRecommendedPlugins(type) {
  if (!resolvers_SUPPORTED_TYPES.includes(type)) {
    return [];
  }

  yield actions_setIsRequesting('getRecommendedPlugins', true);

  try {
    const url = WC_ADMIN_NAMESPACE + '/payment-gateway-suggestions';
    const results = yield Object(external_wp_dataControls_["apiFetch"])({
      path: url,
      method: 'GET'
    });
    yield setRecommendedPlugins(type, results);
  } catch (error) {
    yield setError('getRecommendedPlugins', error);
  }

  yield actions_setIsRequesting('getRecommendedPlugins', false);
}
// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/plugins/reducer.js
/**
 * External dependencies
 */

/**
 * Internal dependencies
 */



const reducer_plugins = function () {
  let state = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {
    active: [],
    installed: [],
    requesting: {},
    errors: {},
    jetpackConnectUrls: {},
    recommended: {}
  };
  let payload = arguments.length > 1 ? arguments[1] : undefined;

  if (payload && 'type' in payload) {
    switch (payload.type) {
      case ACTION_TYPES.UPDATE_ACTIVE_PLUGINS:
        state = { ...state,
          active: payload.replace ? payload.active : Object(external_lodash_["concat"])(state.active, payload.active),
          requesting: { ...state.requesting,
            getActivePlugins: false,
            activatePlugins: false
          },
          errors: { ...state.errors,
            getActivePlugins: false,
            activatePlugins: false
          }
        };
        break;

      case ACTION_TYPES.UPDATE_INSTALLED_PLUGINS:
        state = { ...state,
          installed: payload.replace ? payload.installed : Object(external_lodash_["concat"])(state.installed, payload.installed),
          requesting: { ...state.requesting,
            getInstalledPlugins: false,
            installPlugins: false
          },
          errors: { ...state.errors,
            getInstalledPlugins: false,
            installPlugin: false
          }
        };
        break;

      case ACTION_TYPES.SET_IS_REQUESTING:
        state = { ...state,
          requesting: { ...state.requesting,
            [payload.selector]: payload.isRequesting
          }
        };
        break;

      case ACTION_TYPES.SET_ERROR:
        state = { ...state,
          requesting: { ...state.requesting,
            [payload.selector]: false
          },
          errors: { ...state.errors,
            [payload.selector]: payload.error
          }
        };
        break;

      case ACTION_TYPES.UPDATE_JETPACK_CONNECTION:
        state = { ...state,
          jetpackConnection: payload.jetpackConnection
        };
        break;

      case ACTION_TYPES.UPDATE_JETPACK_CONNECT_URL:
        state = { ...state,
          jetpackConnectUrls: { ...state.jetpackConnectUrls,
            [payload.redirectUrl]: payload.jetpackConnectUrl
          }
        };
        break;

      case ACTION_TYPES.SET_PAYPAL_ONBOARDING_STATUS:
        state = { ...state,
          paypalOnboardingStatus: payload.paypalOnboardingStatus
        };
        break;

      case ACTION_TYPES.SET_RECOMMENDED_PLUGINS:
        state = { ...state,
          recommended: { ...state.recommended,
            [payload.recommendedType]: payload.plugins
          }
        };
        break;
    }
  }

  return state;
};

/* harmony default export */ var plugins_reducer = (reducer_plugins);
// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/plugins/index.js
/**
 * External dependencies
 */


/**
 * Internal dependencies
 */






Object(external_wp_data_["registerStore"])(constants_STORE_NAME, {
  reducer: plugins_reducer,
  actions: plugins_actions_namespaceObject,
  controls: external_wp_dataControls_["controls"],
  selectors: plugins_selectors_namespaceObject,
  resolvers: plugins_resolvers_namespaceObject
});
const PLUGINS_STORE_NAME = constants_STORE_NAME;
// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/plugins/with-plugins-hydration.js
/**
 * External dependencies
 */



/**
 * Internal dependencies
 */


const withPluginsHydration = data => Object(external_wp_compose_["createHigherOrderComponent"])(OriginalComponent => props => {
  const dataRef = Object(external_wp_element_["useRef"])(data);
  Object(external_wp_data_["useSelect"])((select, registry) => {
    if (!dataRef.current) {
      return;
    }

    const {
      isResolving,
      hasFinishedResolution
    } = select(constants_STORE_NAME);
    const {
      startResolution,
      finishResolution,
      updateActivePlugins,
      updateInstalledPlugins,
      updateIsJetpackConnected
    } = registry.dispatch(constants_STORE_NAME);

    if (!isResolving('getActivePlugins', []) && !hasFinishedResolution('getActivePlugins', [])) {
      startResolution('getActivePlugins', []);
      startResolution('getInstalledPlugins', []);
      startResolution('isJetpackConnected', []);
      updateActivePlugins(dataRef.current.activePlugins, true);
      updateInstalledPlugins(dataRef.current.installedPlugins, true);
      updateIsJetpackConnected(dataRef.current.jetpackStatus && dataRef.current.jetpackStatus.isActive ? true : false);
      finishResolution('getActivePlugins', []);
      finishResolution('getInstalledPlugins', []);
      finishResolution('isJetpackConnected', []);
    }
  }, []);
  return Object(external_wp_element_["createElement"])(OriginalComponent, { ...props
  });
}, 'withPluginsHydration');
// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/onboarding/constants.js
/**
 * Internal dependencies
 */
const onboarding_constants_STORE_NAME = 'wc/admin/onboarding';
// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/rememo@4.0.0/node_modules/rememo/es/rememo.js


/** @typedef {(...args: any[]) => *[]} GetDependants */

/** @typedef {() => void} Clear */

/**
 * @typedef {{
 *   getDependants: GetDependants,
 *   clear: Clear
 * }} EnhancedSelector
 */

/**
 * Internal cache entry.
 *
 * @typedef CacheNode
 *
 * @property {?CacheNode|undefined} [prev] Previous node.
 * @property {?CacheNode|undefined} [next] Next node.
 * @property {*[]} args Function arguments for cache entry.
 * @property {*} val Function result.
 */

/**
 * @typedef Cache
 *
 * @property {Clear} clear Function to clear cache.
 * @property {boolean} [isUniqueByDependants] Whether dependants are valid in
 * considering cache uniqueness. A cache is unique if dependents are all arrays
 * or objects.
 * @property {CacheNode?} [head] Cache head.
 * @property {*[]} [lastDependants] Dependants from previous invocation.
 */

/**
 * Arbitrary value used as key for referencing cache object in WeakMap tree.
 *
 * @type {{}}
 */
var LEAF_KEY = {};

/**
 * Returns the first argument as the sole entry in an array.
 *
 * @template T
 *
 * @param {T} value Value to return.
 *
 * @return {[T]} Value returned as entry in array.
 */
function arrayOf(value) {
	return [value];
}

/**
 * Returns true if the value passed is object-like, or false otherwise. A value
 * is object-like if it can support property assignment, e.g. object or array.
 *
 * @param {*} value Value to test.
 *
 * @return {boolean} Whether value is object-like.
 */
function isObjectLike(value) {
	return !!value && 'object' === typeof value;
}

/**
 * Creates and returns a new cache object.
 *
 * @return {Cache} Cache object.
 */
function createCache() {
	/** @type {Cache} */
	var cache = {
		clear: function () {
			cache.head = null;
		},
	};

	return cache;
}

/**
 * Returns true if entries within the two arrays are strictly equal by
 * reference from a starting index.
 *
 * @param {*[]} a First array.
 * @param {*[]} b Second array.
 * @param {number} fromIndex Index from which to start comparison.
 *
 * @return {boolean} Whether arrays are shallowly equal.
 */
function isShallowEqual(a, b, fromIndex) {
	var i;

	if (a.length !== b.length) {
		return false;
	}

	for (i = fromIndex; i < a.length; i++) {
		if (a[i] !== b[i]) {
			return false;
		}
	}

	return true;
}

/**
 * Returns a memoized selector function. The getDependants function argument is
 * called before the memoized selector and is expected to return an immutable
 * reference or array of references on which the selector depends for computing
 * its own return value. The memoize cache is preserved only as long as those
 * dependant references remain the same. If getDependants returns a different
 * reference(s), the cache is cleared and the selector value regenerated.
 *
 * @template {(...args: *[]) => *} S
 *
 * @param {S} selector Selector function.
 * @param {GetDependants=} getDependants Dependant getter returning an array of
 * references used in cache bust consideration.
 */
/* harmony default export */ var rememo = (function (selector, getDependants) {
	/** @type {WeakMap<*,*>} */
	var rootCache;

	/** @type {GetDependants} */
	var normalizedGetDependants = getDependants ? getDependants : arrayOf;

	/**
	 * Returns the cache for a given dependants array. When possible, a WeakMap
	 * will be used to create a unique cache for each set of dependants. This
	 * is feasible due to the nature of WeakMap in allowing garbage collection
	 * to occur on entries where the key object is no longer referenced. Since
	 * WeakMap requires the key to be an object, this is only possible when the
	 * dependant is object-like. The root cache is created as a hierarchy where
	 * each top-level key is the first entry in a dependants set, the value a
	 * WeakMap where each key is the next dependant, and so on. This continues
	 * so long as the dependants are object-like. If no dependants are object-
	 * like, then the cache is shared across all invocations.
	 *
	 * @see isObjectLike
	 *
	 * @param {*[]} dependants Selector dependants.
	 *
	 * @return {Cache} Cache object.
	 */
	function getCache(dependants) {
		var caches = rootCache,
			isUniqueByDependants = true,
			i,
			dependant,
			map,
			cache;

		for (i = 0; i < dependants.length; i++) {
			dependant = dependants[i];

			// Can only compose WeakMap from object-like key.
			if (!isObjectLike(dependant)) {
				isUniqueByDependants = false;
				break;
			}

			// Does current segment of cache already have a WeakMap?
			if (caches.has(dependant)) {
				// Traverse into nested WeakMap.
				caches = caches.get(dependant);
			} else {
				// Create, set, and traverse into a new one.
				map = new WeakMap();
				caches.set(dependant, map);
				caches = map;
			}
		}

		// We use an arbitrary (but consistent) object as key for the last item
		// in the WeakMap to serve as our running cache.
		if (!caches.has(LEAF_KEY)) {
			cache = createCache();
			cache.isUniqueByDependants = isUniqueByDependants;
			caches.set(LEAF_KEY, cache);
		}

		return caches.get(LEAF_KEY);
	}

	/**
	 * Resets root memoization cache.
	 */
	function clear() {
		rootCache = new WeakMap();
	}

	/* eslint-disable jsdoc/check-param-names */
	/**
	 * The augmented selector call, considering first whether dependants have
	 * changed before passing it to underlying memoize function.
	 *
	 * @param {*}    source    Source object for derivation.
	 * @param {...*} extraArgs Additional arguments to pass to selector.
	 *
	 * @return {*} Selector result.
	 */
	/* eslint-enable jsdoc/check-param-names */
	function callSelector(/* source, ...extraArgs */) {
		var len = arguments.length,
			cache,
			node,
			i,
			args,
			dependants;

		// Create copy of arguments (avoid leaking deoptimization).
		args = new Array(len);
		for (i = 0; i < len; i++) {
			args[i] = arguments[i];
		}

		dependants = normalizedGetDependants.apply(null, args);
		cache = getCache(dependants);

		// If not guaranteed uniqueness by dependants (primitive type), shallow
		// compare against last dependants and, if references have changed,
		// destroy cache to recalculate result.
		if (!cache.isUniqueByDependants) {
			if (
				cache.lastDependants &&
				!isShallowEqual(dependants, cache.lastDependants, 0)
			) {
				cache.clear();
			}

			cache.lastDependants = dependants;
		}

		node = cache.head;
		while (node) {
			// Check whether node arguments match arguments
			if (!isShallowEqual(node.args, args, 1)) {
				node = node.next;
				continue;
			}

			// At this point we can assume we've found a match

			// Surface matched node to head if not already
			if (node !== cache.head) {
				// Adjust siblings to point to each other.
				/** @type {CacheNode} */ (node.prev).next = node.next;
				if (node.next) {
					node.next.prev = node.prev;
				}

				node.next = cache.head;
				node.prev = null;
				/** @type {CacheNode} */ (cache.head).prev = node;
				cache.head = node;
			}

			// Return immediately
			return node.val;
		}

		// No cached value found. Continue to insertion phase:

		node = /** @type {CacheNode} */ ({
			// Generate the result from original function
			val: selector.apply(null, args),
		});

		// Avoid including the source object in the cache.
		args[0] = null;
		node.args = args;

		// Don't need to check whether node is already head, since it would
		// have been returned above already if it was

		// Shift existing head down list
		if (cache.head) {
			cache.head.prev = node;
			node.next = cache.head;
		}

		cache.head = node;

		return node.val;
	}

	callSelector.getDependants = normalizedGetDependants;
	callSelector.clear = clear;
	clear();

	return /** @type {S & EnhancedSelector} */ (callSelector);
});

// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/onboarding/selectors.js
/**
 * External dependencies
 */

const getFreeExtensions = state => {
  return state.freeExtensions || [];
};
const getProfileItems = state => {
  return state.profileItems || {};
};
const EMPTY_ARRAY = [];
const getTaskLists = rememo(state => {
  return Object.values(state.taskLists);
}, state => [state.taskLists]);
const getTaskListsByIds = rememo((state, ids) => {
  return ids.map(id => state.taskLists[id]);
}, (state, ids) => ids.map(id => state.taskLists[id]));
const getTaskList = (state, selector) => {
  return state.taskLists[selector];
};
const getTask = (state, selector) => {
  return Object.keys(state.taskLists).reduce((value, listId) => {
    return value || state.taskLists[listId].tasks.find(task => task.id === selector);
  }, undefined);
};
const getPaymentGatewaySuggestions = state => {
  return state.paymentMethods || [];
};
const getOnboardingError = (state, selector) => {
  return state.errors[selector] || false;
};
const isOnboardingRequesting = (state, selector) => {
  return state.requesting[selector] || false;
};
const getEmailPrefill = state => {
  return state.emailPrefill || '';
};
const getProductTypes = state => {
  return state.productTypes || EMPTY_ARRAY;
};
// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/onboarding/action-types.js
const onboarding_action_types_TYPES = {
  SET_ERROR: 'SET_ERROR',
  SET_IS_REQUESTING: 'SET_IS_REQUESTING',
  SET_PROFILE_ITEMS: 'SET_PROFILE_ITEMS',
  SET_EMAIL_PREFILL: 'SET_EMAIL_PREFILL',
  GET_PAYMENT_METHODS_SUCCESS: 'GET_PAYMENT_METHODS_SUCCESS',
  GET_PRODUCT_TYPES_SUCCESS: 'GET_PRODUCT_TYPES_SUCCESS',
  GET_PRODUCT_TYPES_ERROR: 'GET_PRODUCT_TYPES_ERROR',
  GET_FREE_EXTENSIONS_ERROR: 'GET_FREE_EXTENSIONS_ERROR',
  GET_FREE_EXTENSIONS_SUCCESS: 'GET_FREE_EXTENSIONS_SUCCESS',
  GET_TASK_LISTS_ERROR: 'GET_TASK_LISTS_ERROR',
  GET_TASK_LISTS_SUCCESS: 'GET_TASK_LISTS_SUCCESS',
  DISMISS_TASK_ERROR: 'DISMISS_TASK_ERROR',
  DISMISS_TASK_REQUEST: 'DISMISS_TASK_REQUEST',
  DISMISS_TASK_SUCCESS: 'DISMISS_TASK_SUCCESS',
  UNDO_DISMISS_TASK_ERROR: 'UNDO_DISMISS_TASK_ERROR',
  UNDO_DISMISS_TASK_REQUEST: 'UNDO_DISMISS_TASK_REQUEST',
  UNDO_DISMISS_TASK_SUCCESS: 'UNDO_DISMISS_TASK_SUCCESS',
  SNOOZE_TASK_ERROR: 'SNOOZE_TASK_ERROR',
  SNOOZE_TASK_REQUEST: 'SNOOZE_TASK_REQUEST',
  SNOOZE_TASK_SUCCESS: 'SNOOZE_TASK_SUCCESS',
  UNDO_SNOOZE_TASK_ERROR: 'UNDO_SNOOZE_TASK_ERROR',
  UNDO_SNOOZE_TASK_REQUEST: 'UNDO_SNOOZE_TASK_REQUEST',
  UNDO_SNOOZE_TASK_SUCCESS: 'UNDO_SNOOZE_TASK_SUCCESS',
  HIDE_TASK_LIST_ERROR: 'HIDE_TASK_LIST_ERROR',
  HIDE_TASK_LIST_REQUEST: 'HIDE_TASK_LIST_REQUEST',
  HIDE_TASK_LIST_SUCCESS: 'HIDE_TASK_LIST_SUCCESS',
  UNHIDE_TASK_LIST_ERROR: 'UNHIDE_TASK_LIST_ERROR',
  UNHIDE_TASK_LIST_REQUEST: 'UNHIDE_TASK_LIST_REQUEST',
  UNHIDE_TASK_LIST_SUCCESS: 'UNHIDE_TASK_LIST_SUCCESS',
  OPTIMISTICALLY_COMPLETE_TASK_REQUEST: 'OPTIMISTICALLY_COMPLETE_TASK_REQUEST',
  ACTION_TASK_ERROR: 'ACTION_TASK_ERROR',
  ACTION_TASK_REQUEST: 'ACTION_TASK_REQUEST',
  ACTION_TASK_SUCCESS: 'ACTION_TASK_SUCCESS'
};
/* harmony default export */ var onboarding_action_types = (onboarding_action_types_TYPES);
// EXTERNAL MODULE: external ["wp","hooks"]
var external_wp_hooks_ = __webpack_require__(27);

// EXTERNAL MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/qs@6.10.3/node_modules/qs/lib/index.js
var lib = __webpack_require__(31);

// EXTERNAL MODULE: external ["wp","deprecated"]
var external_wp_deprecated_ = __webpack_require__(47);
var external_wp_deprecated_default = /*#__PURE__*/__webpack_require__.n(external_wp_deprecated_);

// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/onboarding/deprecated-tasks.js
/**
 * External dependencies
 */




function getQuery() {
  const searchString = window.location && window.location.search;

  if (!searchString) {
    return {};
  }

  const search = searchString.substring(1);
  return Object(lib["parse"])(search);
}
/**
 * A simple class to handle deprecated tasks using the woocommerce_admin_onboarding_task_list filter.
 */


class deprecated_tasks_DeprecatedTasks {
  constructor() {
    /**
     * **Deprecated** Filter Onboarding tasks.
     *
     * @filter woocommerce_admin_onboarding_task_list
     * @deprecated
     * @param {Array} tasks Array of tasks.
     * @param {Array} query Url query parameters.
     */
    this.filteredTasks = Object(external_wp_hooks_["applyFilters"])('woocommerce_admin_onboarding_task_list', [], getQuery());

    if (this.filteredTasks && this.filteredTasks.length > 0) {
      external_wp_deprecated_default()('woocommerce_admin_onboarding_task_list', {
        version: '2.10.0',
        alternative: 'TaskLists::add_task()',
        plugin: '@woocommerce/data'
      });
    }

    this.tasks = this.filteredTasks.reduce((org, task) => {
      return { ...org,
        [task.key]: task
      };
    }, {});
  }

  hasDeprecatedTasks() {
    return this.filteredTasks.length > 0;
  }

  getPostData() {
    return this.hasDeprecatedTasks() ? {
      extended_tasks: this.filteredTasks.map(task => ({
        title: task.title,
        content: task.content,
        additional_info: task.additionalInfo,
        time: task.time,
        level: task.level ? parseInt(task.level, 10) : 3,
        list_id: task.type || 'extended',
        can_view: task.visible,
        id: task.key,
        is_snoozeable: task.allowRemindMeLater,
        is_dismissable: task.isDismissable,
        is_complete: task.completed
      }))
    } : null;
  }

  mergeDeprecatedCallbackFunctions(taskLists) {
    if (this.filteredTasks.length > 0) {
      for (const taskList of taskLists) {
        // Merge any extended task list items, to keep the callback functions around.
        taskList.tasks = taskList.tasks.map(task => {
          if (this.tasks && this.tasks[task.id]) {
            return { ...this.tasks[task.id],
              ...task,
              isDeprecated: true
            };
          }

          return task;
        });
      }
    }

    return taskLists;
  }
  /**
   * Used to keep backwards compatibility with the extended task list filter on the client.
   * This can be removed after version WC Admin 2.10 (see deprecated notice in resolvers.js).
   *
   * @param {Object} task the returned task object.
   * @param {Array}  keys to keep in the task object.
   * @return {Object} task with the keys specified.
   */


  static possiblyPruneTaskData(task, keys) {
    if (!task.time && !task.title) {
      // client side task
      return keys.reduce((simplifiedTask, key) => {
        return { ...simplifiedTask,
          [key]: task[key]
        };
      }, {
        id: task.id
      });
    }

    return task;
  }

}
// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/onboarding/actions.js
/**
 * External dependencies
 */

/**
 * Internal dependencies
 */




function getFreeExtensionsError(error) {
  return {
    type: onboarding_action_types.GET_FREE_EXTENSIONS_ERROR,
    error
  };
}
function getFreeExtensionsSuccess(freeExtensions) {
  return {
    type: onboarding_action_types.GET_FREE_EXTENSIONS_SUCCESS,
    freeExtensions
  };
}
function actions_setError(selector, error) {
  return {
    type: onboarding_action_types.SET_ERROR,
    selector,
    error
  };
}
function onboarding_actions_setIsRequesting(selector, isRequesting) {
  return {
    type: onboarding_action_types.SET_IS_REQUESTING,
    selector,
    isRequesting
  };
}
function actions_setProfileItems(profileItems) {
  let replace = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : false;
  return {
    type: onboarding_action_types.SET_PROFILE_ITEMS,
    profileItems,
    replace
  };
}
function getTaskListsError(error) {
  return {
    type: onboarding_action_types.GET_TASK_LISTS_ERROR,
    error
  };
}
function getTaskListsSuccess(taskLists) {
  return {
    type: onboarding_action_types.GET_TASK_LISTS_SUCCESS,
    taskLists
  };
}
function snoozeTaskError(taskId, error) {
  return {
    type: onboarding_action_types.SNOOZE_TASK_ERROR,
    taskId,
    error
  };
}
function snoozeTaskRequest(taskId) {
  return {
    type: onboarding_action_types.SNOOZE_TASK_REQUEST,
    taskId
  };
}
function snoozeTaskSuccess(task) {
  return {
    type: onboarding_action_types.SNOOZE_TASK_SUCCESS,
    task
  };
}
function undoSnoozeTaskError(taskId, error) {
  return {
    type: onboarding_action_types.UNDO_SNOOZE_TASK_ERROR,
    taskId,
    error
  };
}
function undoSnoozeTaskRequest(taskId) {
  return {
    type: onboarding_action_types.UNDO_SNOOZE_TASK_REQUEST,
    taskId
  };
}
function undoSnoozeTaskSuccess(task) {
  return {
    type: onboarding_action_types.UNDO_SNOOZE_TASK_SUCCESS,
    task
  };
}
function dismissTaskError(taskId, error) {
  return {
    type: onboarding_action_types.DISMISS_TASK_ERROR,
    taskId,
    error
  };
}
function dismissTaskRequest(taskId) {
  return {
    type: onboarding_action_types.DISMISS_TASK_REQUEST,
    taskId
  };
}
function dismissTaskSuccess(task) {
  return {
    type: onboarding_action_types.DISMISS_TASK_SUCCESS,
    task
  };
}
function undoDismissTaskError(taskId, error) {
  return {
    type: onboarding_action_types.UNDO_DISMISS_TASK_ERROR,
    taskId,
    error
  };
}
function undoDismissTaskRequest(taskId) {
  return {
    type: onboarding_action_types.UNDO_DISMISS_TASK_REQUEST,
    taskId
  };
}
function undoDismissTaskSuccess(task) {
  return {
    type: onboarding_action_types.UNDO_DISMISS_TASK_SUCCESS,
    task
  };
}
function hideTaskListError(taskListId, error) {
  return {
    type: onboarding_action_types.HIDE_TASK_LIST_ERROR,
    taskListId,
    error
  };
}
function hideTaskListRequest(taskListId) {
  return {
    type: onboarding_action_types.HIDE_TASK_LIST_REQUEST,
    taskListId
  };
}
function hideTaskListSuccess(taskList) {
  return {
    type: onboarding_action_types.HIDE_TASK_LIST_SUCCESS,
    taskList
  };
}
function unhideTaskListError(taskListId, error) {
  return {
    type: onboarding_action_types.UNHIDE_TASK_LIST_ERROR,
    taskListId,
    error
  };
}
function unhideTaskListRequest(taskListId) {
  return {
    type: onboarding_action_types.UNHIDE_TASK_LIST_REQUEST,
    taskListId
  };
}
function unhideTaskListSuccess(taskList) {
  return {
    type: onboarding_action_types.UNHIDE_TASK_LIST_SUCCESS,
    taskList
  };
}
function optimisticallyCompleteTaskRequest(taskId) {
  return {
    type: onboarding_action_types.OPTIMISTICALLY_COMPLETE_TASK_REQUEST,
    taskId
  };
}
function setPaymentMethods(paymentMethods) {
  return {
    type: onboarding_action_types.GET_PAYMENT_METHODS_SUCCESS,
    paymentMethods
  };
}
function setEmailPrefill(email) {
  return {
    type: onboarding_action_types.SET_EMAIL_PREFILL,
    emailPrefill: email
  };
}
function actionTaskError(taskId, error) {
  return {
    type: onboarding_action_types.ACTION_TASK_ERROR,
    taskId,
    error
  };
}
function actionTaskRequest(taskId) {
  return {
    type: onboarding_action_types.ACTION_TASK_REQUEST,
    taskId
  };
}
function actionTaskSuccess(task) {
  return {
    type: onboarding_action_types.ACTION_TASK_SUCCESS,
    task
  };
}
function getProductTypesSuccess(productTypes) {
  return {
    type: onboarding_action_types.GET_PRODUCT_TYPES_SUCCESS,
    productTypes
  };
}
function getProductTypesError(error) {
  return {
    type: onboarding_action_types.GET_PRODUCT_TYPES_ERROR,
    error
  };
}
function* updateProfileItems(items) {
  yield onboarding_actions_setIsRequesting('updateProfileItems', true);
  yield actions_setError('updateProfileItems', null);

  try {
    const results = yield Object(external_wp_dataControls_["apiFetch"])({
      path: `${WC_ADMIN_NAMESPACE}/onboarding/profile`,
      method: 'POST',
      data: items
    });

    if (results && results.status === 'success') {
      yield actions_setProfileItems(items);
      yield onboarding_actions_setIsRequesting('updateProfileItems', false);
      return results;
    }

    throw new Error();
  } catch (error) {
    yield actions_setError('updateProfileItems', error);
    yield onboarding_actions_setIsRequesting('updateProfileItems', false);
    throw error;
  }
}
function* snoozeTask(id) {
  yield snoozeTaskRequest(id);

  try {
    const task = yield Object(external_wp_dataControls_["apiFetch"])({
      path: `${WC_ADMIN_NAMESPACE}/onboarding/tasks/${id}/snooze`,
      method: 'POST'
    });
    yield snoozeTaskSuccess(deprecated_tasks_DeprecatedTasks.possiblyPruneTaskData(task, ['isSnoozed', 'isDismissed', 'snoozedUntil']));
  } catch (error) {
    yield snoozeTaskError(id, error);
    throw new Error();
  }
}
function* undoSnoozeTask(id) {
  yield undoSnoozeTaskRequest(id);

  try {
    const task = yield Object(external_wp_dataControls_["apiFetch"])({
      path: `${WC_ADMIN_NAMESPACE}/onboarding/tasks/${id}/undo_snooze`,
      method: 'POST'
    });
    yield undoSnoozeTaskSuccess(deprecated_tasks_DeprecatedTasks.possiblyPruneTaskData(task, ['isSnoozed', 'isDismissed', 'snoozedUntil']));
  } catch (error) {
    yield undoSnoozeTaskError(id, error);
    throw new Error();
  }
}
function* dismissTask(id) {
  yield dismissTaskRequest(id);

  try {
    const task = yield Object(external_wp_dataControls_["apiFetch"])({
      path: `${WC_ADMIN_NAMESPACE}/onboarding/tasks/${id}/dismiss`,
      method: 'POST'
    });
    yield dismissTaskSuccess(deprecated_tasks_DeprecatedTasks.possiblyPruneTaskData(task, ['isDismissed', 'isSnoozed']));
  } catch (error) {
    yield dismissTaskError(id, error);
    throw new Error();
  }
}
function* undoDismissTask(id) {
  yield undoDismissTaskRequest(id);

  try {
    const task = yield Object(external_wp_dataControls_["apiFetch"])({
      path: `${WC_ADMIN_NAMESPACE}/onboarding/tasks/${id}/undo_dismiss`,
      method: 'POST'
    });
    yield undoDismissTaskSuccess(deprecated_tasks_DeprecatedTasks.possiblyPruneTaskData(task, ['isDismissed', 'isSnoozed']));
  } catch (error) {
    yield undoDismissTaskError(id, error);
    throw new Error();
  }
}
function* hideTaskList(id) {
  yield hideTaskListRequest(id);

  try {
    const taskList = yield Object(external_wp_dataControls_["apiFetch"])({
      path: `${WC_ADMIN_NAMESPACE}/onboarding/tasks/${id}/hide`,
      method: 'POST'
    });
    yield hideTaskListSuccess(taskList);
  } catch (error) {
    yield hideTaskListError(id, error);
    throw new Error();
  }
}
function* unhideTaskList(id) {
  yield unhideTaskListRequest(id);

  try {
    const taskList = yield Object(external_wp_dataControls_["apiFetch"])({
      path: `${WC_ADMIN_NAMESPACE}/onboarding/tasks/${id}/unhide`,
      method: 'POST'
    });
    yield unhideTaskListSuccess(taskList);
  } catch (error) {
    yield unhideTaskListError(id, error);
    throw new Error();
  }
}
function* optimisticallyCompleteTask(id) {
  yield optimisticallyCompleteTaskRequest(id);
}
function* actionTask(id) {
  yield actionTaskRequest(id);

  try {
    const task = yield Object(external_wp_dataControls_["apiFetch"])({
      path: `${WC_ADMIN_NAMESPACE}/onboarding/tasks/${id}/action`,
      method: 'POST'
    });
    yield actionTaskSuccess(deprecated_tasks_DeprecatedTasks.possiblyPruneTaskData(task, ['isActioned']));
  } catch (error) {
    yield actionTaskError(id, error);
    throw new Error();
  }
}
// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/onboarding/resolvers.js
/**
 * External dependencies
 */


/**
 * Internal dependencies
 */





const onboarding_resolvers_resolveSelect = external_wp_data_["controls"] && external_wp_data_["controls"].resolveSelect ? external_wp_data_["controls"].resolveSelect : external_wp_dataControls_["select"];
function* resolvers_getProfileItems() {
  try {
    const results = yield Object(external_wp_dataControls_["apiFetch"])({
      path: WC_ADMIN_NAMESPACE + '/onboarding/profile',
      method: 'GET'
    });
    yield actions_setProfileItems(results, true);
  } catch (error) {
    yield actions_setError('getProfileItems', error);
  }
}
function* resolvers_getEmailPrefill() {
  try {
    const results = yield Object(external_wp_dataControls_["apiFetch"])({
      path: WC_ADMIN_NAMESPACE + '/onboarding/profile/experimental_get_email_prefill',
      method: 'GET'
    });
    yield setEmailPrefill(results.email);
  } catch (error) {
    yield actions_setError('getEmailPrefill', error);
  }
}
function* resolvers_getTaskLists() {
  const deprecatedTasks = new deprecated_tasks_DeprecatedTasks();

  try {
    const results = yield Object(external_wp_dataControls_["apiFetch"])({
      path: WC_ADMIN_NAMESPACE + '/onboarding/tasks',
      method: deprecatedTasks.hasDeprecatedTasks() ? 'POST' : 'GET',
      data: deprecatedTasks.getPostData()
    });
    deprecatedTasks.mergeDeprecatedCallbackFunctions(results);
    yield getTaskListsSuccess(results);
  } catch (error) {
    yield getTaskListsError(error);
  }
}
function* resolvers_getTaskListsByIds() {
  yield onboarding_resolvers_resolveSelect(onboarding_constants_STORE_NAME, 'getTaskLists');
}
function* resolvers_getTaskList() {
  yield onboarding_resolvers_resolveSelect(onboarding_constants_STORE_NAME, 'getTaskLists');
}
function* resolvers_getTask() {
  yield onboarding_resolvers_resolveSelect(onboarding_constants_STORE_NAME, 'getTaskLists');
}
function* resolvers_getPaymentGatewaySuggestions() {
  try {
    const results = yield Object(external_wp_dataControls_["apiFetch"])({
      path: WC_ADMIN_NAMESPACE + '/payment-gateway-suggestions',
      method: 'GET'
    });
    yield setPaymentMethods(results);
  } catch (error) {
    yield actions_setError('getPaymentGatewaySuggestions', error);
  }
}
function* resolvers_getFreeExtensions() {
  try {
    const results = yield Object(external_wp_dataControls_["apiFetch"])({
      path: WC_ADMIN_NAMESPACE + '/onboarding/free-extensions',
      method: 'GET'
    });
    yield getFreeExtensionsSuccess(results);
  } catch (error) {
    yield getFreeExtensionsError(error);
  }
}
function* resolvers_getProductTypes() {
  try {
    const results = yield Object(external_wp_dataControls_["apiFetch"])({
      path: WC_ADMIN_NAMESPACE + '/onboarding/product-types',
      method: 'GET'
    });
    yield getProductTypesSuccess(results);
  } catch (error) {
    yield getProductTypesError(error);
  }
}
// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/onboarding/reducer.js
/**
 * Internal dependencies
 */

const defaultState = {
  errors: {},
  freeExtensions: [],
  profileItems: {
    business_extensions: null,
    completed: null,
    industry: null,
    number_employees: null,
    other_platform: null,
    other_platform_name: null,
    product_count: null,
    product_types: null,
    revenue: null,
    selling_venues: null,
    setup_client: null,
    skipped: null,
    theme: null,
    wccom_connected: null,
    is_agree_marketing: null,
    store_email: null
  },
  emailPrefill: '',
  paymentMethods: [],
  productTypes: [],
  requesting: {},
  taskLists: {}
};

const getUpdatedTaskLists = (taskLists, args) => {
  return Object.keys(taskLists).reduce((lists, taskListId) => {
    return { ...lists,
      [taskListId]: { ...taskLists[taskListId],
        tasks: taskLists[taskListId].tasks.map(task => {
          if (args.id === task.id) {
            return { ...task,
              ...args
            };
          }

          return task;
        })
      }
    };
  }, { ...taskLists
  });
};

const onboarding = function () {
  let state = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : defaultState;
  let {
    freeExtensions,
    type,
    profileItems,
    emailPrefill,
    paymentMethods,
    productTypes,
    replace,
    error,
    isRequesting,
    selector,
    task,
    taskId,
    taskListId,
    taskList,
    taskLists
  } = arguments.length > 1 ? arguments[1] : undefined;

  switch (type) {
    case onboarding_action_types.SET_PROFILE_ITEMS:
      return { ...state,
        profileItems: replace ? profileItems : { ...state.profileItems,
          ...profileItems
        }
      };

    case onboarding_action_types.SET_EMAIL_PREFILL:
      return { ...state,
        emailPrefill
      };

    case onboarding_action_types.SET_ERROR:
      return { ...state,
        errors: { ...state.errors,
          [selector]: error
        }
      };

    case onboarding_action_types.SET_IS_REQUESTING:
      return { ...state,
        requesting: { ...state.requesting,
          [selector]: isRequesting
        }
      };

    case onboarding_action_types.GET_PAYMENT_METHODS_SUCCESS:
      return { ...state,
        paymentMethods
      };

    case onboarding_action_types.GET_PRODUCT_TYPES_SUCCESS:
      return { ...state,
        productTypes
      };

    case onboarding_action_types.GET_PRODUCT_TYPES_ERROR:
      return { ...state,
        errors: { ...state.errors,
          productTypes: error
        }
      };

    case onboarding_action_types.GET_FREE_EXTENSIONS_ERROR:
      return { ...state,
        errors: { ...state.errors,
          getFreeExtensions: error
        }
      };

    case onboarding_action_types.GET_FREE_EXTENSIONS_SUCCESS:
      return { ...state,
        freeExtensions
      };

    case onboarding_action_types.GET_TASK_LISTS_ERROR:
      return { ...state,
        errors: { ...state.errors,
          getTaskLists: error
        }
      };

    case onboarding_action_types.GET_TASK_LISTS_SUCCESS:
      return { ...state,
        taskLists: taskLists.reduce((lists, list) => {
          return { ...lists,
            [list.id]: list
          };
        }, state.taskLists || {})
      };

    case onboarding_action_types.DISMISS_TASK_ERROR:
      return { ...state,
        errors: { ...state.errors,
          dismissTask: error
        },
        taskLists: getUpdatedTaskLists(state.taskLists, {
          id: taskId,
          isDismissed: false
        })
      };

    case onboarding_action_types.DISMISS_TASK_REQUEST:
      return { ...state,
        requesting: { ...state.requesting,
          dismissTask: true
        },
        taskLists: getUpdatedTaskLists(state.taskLists, {
          id: taskId,
          isDismissed: true
        })
      };

    case onboarding_action_types.DISMISS_TASK_SUCCESS:
      return { ...state,
        requesting: { ...state.requesting,
          dismissTask: false
        },
        taskLists: getUpdatedTaskLists(state.taskLists, task)
      };

    case onboarding_action_types.UNDO_DISMISS_TASK_ERROR:
      return { ...state,
        errors: { ...state.errors,
          undoDismissTask: error
        },
        taskLists: getUpdatedTaskLists(state.taskLists, {
          id: taskId,
          isDismissed: true
        })
      };

    case onboarding_action_types.UNDO_DISMISS_TASK_REQUEST:
      return { ...state,
        requesting: { ...state.requesting,
          undoDismissTask: true
        },
        taskLists: getUpdatedTaskLists(state.taskLists, {
          id: taskId,
          isDismissed: false
        })
      };

    case onboarding_action_types.UNDO_DISMISS_TASK_SUCCESS:
      return { ...state,
        requesting: { ...state.requesting,
          undoDismissTask: false
        },
        taskLists: getUpdatedTaskLists(state.taskLists, task)
      };

    case onboarding_action_types.SNOOZE_TASK_ERROR:
      return { ...state,
        errors: { ...state.errors,
          snoozeTask: error
        },
        taskLists: getUpdatedTaskLists(state.taskLists, {
          id: taskId,
          isSnoozed: false
        })
      };

    case onboarding_action_types.SNOOZE_TASK_REQUEST:
      return { ...state,
        requesting: { ...state.requesting,
          snoozeTask: true
        },
        taskLists: getUpdatedTaskLists(state.taskLists, {
          id: taskId,
          isSnoozed: true
        })
      };

    case onboarding_action_types.SNOOZE_TASK_SUCCESS:
      return { ...state,
        requesting: { ...state.requesting,
          snoozeTask: false
        },
        taskLists: getUpdatedTaskLists(state.taskLists, task)
      };

    case onboarding_action_types.UNDO_SNOOZE_TASK_ERROR:
      return { ...state,
        errors: { ...state.errors,
          undoSnoozeTask: error
        },
        taskLists: getUpdatedTaskLists(state.taskLists, {
          id: taskId,
          isSnoozed: true
        })
      };

    case onboarding_action_types.UNDO_SNOOZE_TASK_REQUEST:
      return { ...state,
        requesting: { ...state.requesting,
          undoSnoozeTask: true
        },
        taskLists: getUpdatedTaskLists(state.taskLists, {
          id: taskId,
          isSnoozed: false
        })
      };

    case onboarding_action_types.UNDO_SNOOZE_TASK_SUCCESS:
      return { ...state,
        requesting: { ...state.requesting,
          undoSnoozeTask: false
        },
        taskLists: getUpdatedTaskLists(state.taskLists, task)
      };

    case onboarding_action_types.HIDE_TASK_LIST_ERROR:
      return { ...state,
        errors: { ...state.errors,
          hideTaskList: error
        },
        taskLists: { ...state.taskLists,
          [taskListId]: { ...state.taskLists[taskListId],
            isHidden: false,
            isVisible: true
          }
        }
      };

    case onboarding_action_types.HIDE_TASK_LIST_REQUEST:
      return { ...state,
        requesting: { ...state.requesting,
          hideTaskList: true
        },
        taskLists: { ...state.taskLists,
          [taskListId]: { ...state.taskLists[taskListId],
            isHidden: true,
            isVisible: false
          }
        }
      };

    case onboarding_action_types.HIDE_TASK_LIST_SUCCESS:
      return { ...state,
        requesting: { ...state.requesting,
          hideTaskList: false
        },
        taskLists: { ...state.taskLists,
          [taskListId]: taskList
        }
      };

    case onboarding_action_types.UNHIDE_TASK_LIST_ERROR:
      return { ...state,
        errors: { ...state.errors,
          unhideTaskList: error
        },
        taskLists: { ...state.taskLists,
          [taskListId]: { ...state.taskLists[taskListId],
            isHidden: true,
            isVisible: false
          }
        }
      };

    case onboarding_action_types.UNHIDE_TASK_LIST_REQUEST:
      return { ...state,
        requesting: { ...state.requesting,
          unhideTaskList: true
        },
        taskLists: { ...state.taskLists,
          [taskListId]: { ...state.taskLists[taskListId],
            isHidden: false,
            isVisible: true
          }
        }
      };

    case onboarding_action_types.UNHIDE_TASK_LIST_SUCCESS:
      return { ...state,
        requesting: { ...state.requesting,
          unhideTaskList: false
        },
        taskLists: { ...state.taskLists,
          [taskListId]: taskList
        }
      };

    case onboarding_action_types.OPTIMISTICALLY_COMPLETE_TASK_REQUEST:
      return { ...state,
        taskLists: getUpdatedTaskLists(state.taskLists, {
          id: taskId,
          isComplete: true
        })
      };

    case onboarding_action_types.ACTION_TASK_ERROR:
      return { ...state,
        errors: { ...state.errors,
          actionTask: error
        },
        taskLists: getUpdatedTaskLists(state.taskLists, {
          id: taskId,
          isActioned: false
        })
      };

    case onboarding_action_types.ACTION_TASK_REQUEST:
      return { ...state,
        requesting: { ...state.requesting,
          actionTask: true
        },
        taskLists: getUpdatedTaskLists(state.taskLists, {
          id: taskId,
          isActioned: true
        })
      };

    case onboarding_action_types.ACTION_TASK_SUCCESS:
      return { ...state,
        requesting: { ...state.requesting,
          actionTask: false
        },
        taskLists: getUpdatedTaskLists(state.taskLists, task)
      };

    default:
      return state;
  }
};

/* harmony default export */ var onboarding_reducer = (onboarding);
// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/onboarding/index.js
/**
 * External dependencies
 */


/**
 * Internal dependencies
 */






Object(external_wp_data_["registerStore"])(onboarding_constants_STORE_NAME, {
  reducer: onboarding_reducer,
  actions: onboarding_actions_namespaceObject,
  controls: external_wp_dataControls_["controls"],
  selectors: onboarding_selectors_namespaceObject,
  resolvers: onboarding_resolvers_namespaceObject
});
const ONBOARDING_STORE_NAME = onboarding_constants_STORE_NAME;
// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/onboarding/with-onboarding-hydration.js
/**
 * External dependencies
 */



/**
 * Internal dependencies
 */


const withOnboardingHydration = data => {
  let hydratedProfileItems = false;
  return Object(external_wp_compose_["createHigherOrderComponent"])(OriginalComponent => props => {
    const onboardingRef = Object(external_wp_element_["useRef"])(data);
    Object(external_wp_data_["useSelect"])((select, registry) => {
      if (!onboardingRef.current) {
        return;
      }

      const {
        isResolving,
        hasFinishedResolution
      } = select(onboarding_constants_STORE_NAME);
      const {
        startResolution,
        finishResolution,
        setProfileItems
      } = registry.dispatch(onboarding_constants_STORE_NAME);
      const {
        profileItems
      } = onboardingRef.current;

      if (profileItems && !hydratedProfileItems && !isResolving('getProfileItems', []) && !hasFinishedResolution('getProfileItems', [])) {
        startResolution('getProfileItems', []);
        setProfileItems(profileItems, true);
        finishResolution('getProfileItems', []);
        hydratedProfileItems = true;
      }
    }, []);
    return Object(external_wp_element_["createElement"])(OriginalComponent, { ...props
    });
  }, 'withOnboardingHydration');
};
// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/user/constants.js
const user_constants_STORE_NAME = 'core';
// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/user/index.js
/**
 * Internal dependencies
 */

const USER_STORE_NAME = user_constants_STORE_NAME;
// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/user/with-current-user-hydration.js
/**
 * External dependencies
 */



/**
 * Internal dependencies
 */


/**
 * Higher-order component used to hydrate current user data.
 *
 * @param {Object} currentUser Current user object in the same format as the WP REST API returns.
 */

const withCurrentUserHydration = currentUser => Object(external_wp_compose_["createHigherOrderComponent"])(OriginalComponent => props => {
  const userRef = Object(external_wp_element_["useRef"])(currentUser); // Use currentUser to hydrate calls to @wordpress/core-data's getCurrentUser().

  Object(external_wp_data_["useSelect"])((select, registry) => {
    if (!userRef.current) {
      return;
    }

    const {
      isResolving,
      hasFinishedResolution
    } = select(user_constants_STORE_NAME);
    const {
      startResolution,
      finishResolution,
      receiveCurrentUser
    } = registry.dispatch(user_constants_STORE_NAME);

    if (!isResolving('getCurrentUser') && !hasFinishedResolution('getCurrentUser')) {
      startResolution('getCurrentUser', []);
      receiveCurrentUser(userRef.current);
      finishResolution('getCurrentUser', []);
    }
  });
  return Object(external_wp_element_["createElement"])(OriginalComponent, { ...props
  });
}, 'withCurrentUserHydration');
// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/user/use-user.js
/**
 * External dependencies
 */

/**
 * Internal dependencies
 */


/**
 * Custom react hook for shortcut methods around user.
 *
 * This is a wrapper around @wordpress/core-data's getCurrentUser().
 */

const useUser = () => {
  const userData = Object(external_wp_data_["useSelect"])(select => {
    const {
      getCurrentUser,
      hasStartedResolution,
      hasFinishedResolution
    } = select(user_constants_STORE_NAME);
    return {
      isRequesting: hasStartedResolution('getCurrentUser') && !hasFinishedResolution('getCurrentUser'),
      user: getCurrentUser(),
      getCurrentUser
    };
  });

  const currentUserCan = capability => {
    if (userData.user && userData.user.is_super_admin) {
      return true;
    }

    if (userData.user && userData.user.capabilities[capability]) {
      return true;
    }

    return false;
  };

  return {
    currentUserCan,
    user: userData.user,
    isRequesting: userData.isRequesting
  };
};
// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/user/use-user-preferences.js
/**
 * External dependencies
 */


/**
 * Internal dependencies
 */


/**
 * Retrieve and decode the user's WooCommerce meta values.
 *
 * @param {Object} user WP User object.
 * @return {Object} User's WooCommerce preferences.
 */

const getWooCommerceMeta = user => {
  const wooMeta = user.woocommerce_meta || {};
  const userData = Object(external_lodash_["mapValues"])(wooMeta, (data, key) => {
    if (!data || data.length === 0) {
      return '';
    }

    try {
      return JSON.parse(data);
    } catch (e) {
      /* eslint-disable no-console */
      console.error(`Error parsing value '${data}' for ${key}`, e.message);
      /* eslint-enable no-console */

      return '';
    }
  });
  return userData;
}; // Create wrapper for updating user's `woocommerce_meta`.


async function updateUserPrefs(receiveCurrentUser, user, saveUser, getLastEntitySaveError, userPrefs) {
  // @todo Handle unresolved getCurrentUser() here.
  // Prep fields for update.
  const metaData = Object(external_lodash_["mapValues"])(userPrefs, JSON.stringify);

  if (Object.keys(metaData).length === 0) {
    return {
      error: new Error('Invalid woocommerce_meta data for update.'),
      updatedUser: undefined
    };
  } // Optimistically propagate new woocommerce_meta to the store for instant update.


  receiveCurrentUser({ ...user,
    woocommerce_meta: { ...user.woocommerce_meta,
      ...metaData
    }
  }); // Use saveUser() to update WooCommerce meta values.

  const updatedUser = await saveUser({
    id: user.id,
    woocommerce_meta: metaData
  });

  if (undefined === updatedUser) {
    // Return the encountered error to the caller.
    const error = getLastEntitySaveError('root', 'user', user.id);
    return {
      error,
      updatedUser
    };
  } // Decode the WooCommerce meta after save.


  const updatedUserResponse = { ...updatedUser,
    woocommerce_meta: getWooCommerceMeta(updatedUser)
  };
  return {
    updatedUser: updatedUserResponse
  };
}
/**
 * Custom react hook for retrieving thecurrent user's WooCommerce preferences.
 *
 * This is a wrapper around @wordpress/core-data's getCurrentUser() and saveUser().
 */


const useUserPreferences = () => {
  // Get our dispatch methods now - this can't happen inside the callback below.
  const dispatch = Object(external_wp_data_["useDispatch"])(user_constants_STORE_NAME);
  const {
    addEntities,
    receiveCurrentUser,
    saveEntityRecord
  } = dispatch;
  let {
    saveUser
  } = dispatch;
  const userData = Object(external_wp_data_["useSelect"])(select => {
    const {
      getCurrentUser,
      getEntity,
      getEntityRecord,
      getLastEntitySaveError,
      hasStartedResolution,
      hasFinishedResolution
    } = select(user_constants_STORE_NAME);
    return {
      isRequesting: hasStartedResolution('getCurrentUser') && !hasFinishedResolution('getCurrentUser'),
      user: getCurrentUser(),
      getCurrentUser,
      getEntity,
      getEntityRecord,
      getLastEntitySaveError
    };
  });

  const updateUserPreferences = userPrefs => {
    // WP 5.3.x doesn't have the User entity defined.
    if (typeof saveUser !== 'function') {
      // Polyfill saveUser() - wrapper of saveEntityRecord.
      saveUser = async userToSave => {
        const entityDefined = Boolean(userData.getEntity('root', 'user'));

        if (!entityDefined) {
          // Add the User entity so saveEntityRecord works.
          await addEntities([{
            name: 'user',
            kind: 'root',
            baseURL: '/wp/v2/users',
            plural: 'users'
          }]);
        } // Fire off the save action.


        await saveEntityRecord('root', 'user', userToSave); // Respond with the updated user.

        return userData.getEntityRecord('root', 'user', userToSave.id);
      };
    } // Get most recent user before update.


    const currentUser = userData.getCurrentUser();
    return updateUserPrefs(receiveCurrentUser, currentUser, saveUser, userData.getLastEntitySaveError, userPrefs);
  };

  const userPreferences = userData.user ? getWooCommerceMeta(userData.user) : {};
  return {
    isRequesting: userData.isRequesting,
    ...userPreferences,
    updateUserPreferences
  };
};
// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/options/with-options-hydration.js
/**
 * External dependencies
 */



/**
 * Internal dependencies
 */


const useOptionsHydration = data => {
  const dataRef = Object(external_wp_element_["useRef"])(data);
  Object(external_wp_data_["useSelect"])((select, registry) => {
    if (!dataRef.current) {
      return;
    }

    const {
      isResolving,
      hasFinishedResolution
    } = select(options_constants_STORE_NAME);
    const {
      startResolution,
      finishResolution,
      receiveOptions
    } = registry.dispatch(options_constants_STORE_NAME);
    const names = Object.keys(dataRef.current);
    names.forEach(name => {
      if (!isResolving('getOption', [name]) && !hasFinishedResolution('getOption', [name])) {
        startResolution('getOption', [name]);
        receiveOptions({
          [name]: dataRef.current[name]
        });
        finishResolution('getOption', [name]);
      }
    });
  }, []);
};
const withOptionsHydration = data => Object(external_wp_compose_["createHigherOrderComponent"])(OriginalComponent => props => {
  useOptionsHydration(data);
  return Object(external_wp_element_["createElement"])(OriginalComponent, { ...props
  });
}, 'withOptionsHydration');
// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/reviews/constants.js
const reviews_constants_STORE_NAME = 'wc/admin/reviews';
// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/reviews/selectors.js
const getReviews = (state, query) => {
  const stringifiedQuery = JSON.stringify(query);
  const ids = state.reviews[stringifiedQuery] && state.reviews[stringifiedQuery].data || [];
  return ids.map(id => state.data[id]);
};
const getReviewsTotalCount = (state, query) => {
  const stringifiedQuery = JSON.stringify(query);
  return state.reviews[stringifiedQuery] && state.reviews[stringifiedQuery].totalCount;
};
const getReviewsError = (state, query) => {
  const stringifiedQuery = JSON.stringify(query);
  return state.errors[stringifiedQuery];
};
// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/reviews/action-types.js
const reviews_action_types_TYPES = {
  UPDATE_REVIEWS: 'UPDATE_REVIEWS',
  SET_REVIEW: 'SET_REVIEW',
  SET_ERROR: 'SET_ERROR',
  SET_REVIEW_IS_UPDATING: 'SET_REVIEW_IS_UPDATING'
};
/* harmony default export */ var reviews_action_types = (reviews_action_types_TYPES);
// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/reviews/actions.js
/**
 * External dependencies
 */


/**
 * Internal dependencies
 */



function updateReviews(query, reviews, totalCount) {
  return {
    type: reviews_action_types.UPDATE_REVIEWS,
    reviews,
    query,
    totalCount
  };
}
function* updateReview(reviewId, reviewFields, query) {
  yield setReviewIsUpdating(reviewId, true);

  try {
    const url = Object(external_wp_url_["addQueryArgs"])(`${NAMESPACE}/products/reviews/${reviewId}`, query || {});
    const review = yield Object(external_wp_dataControls_["apiFetch"])({
      path: url,
      method: 'PUT',
      data: reviewFields
    });
    yield setReview(reviewId, review);
    yield setReviewIsUpdating(reviewId, false);
  } catch (error) {
    yield reviews_actions_setError('updateReview', error);
    yield setReviewIsUpdating(reviewId, false);
    throw new Error();
  }
}
function* deleteReview(reviewId) {
  yield setReviewIsUpdating(reviewId, true);

  try {
    const url = `${NAMESPACE}/products/reviews/${reviewId}`;
    const response = yield Object(external_wp_dataControls_["apiFetch"])({
      path: url,
      method: 'DELETE'
    });
    yield setReview(reviewId, response);
    yield setReviewIsUpdating(reviewId, false);
    return response;
  } catch (error) {
    yield reviews_actions_setError('deleteReview', error);
    yield setReviewIsUpdating(reviewId, false);
    throw new Error();
  }
}
function setReviewIsUpdating(reviewId, isUpdating) {
  return {
    type: reviews_action_types.SET_REVIEW_IS_UPDATING,
    reviewId,
    isUpdating
  };
}
function setReview(reviewId, reviewData) {
  return {
    type: reviews_action_types.SET_REVIEW,
    reviewId,
    reviewData
  };
}
function reviews_actions_setError(query, error) {
  return {
    type: reviews_action_types.SET_ERROR,
    query,
    error
  };
}
// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/controls.js
/**
 * External dependencies
 */


const fetchWithHeaders = options => {
  return {
    type: 'FETCH_WITH_HEADERS',
    options
  };
};
const controls_controls = { ...external_wp_dataControls_["controls"],

  FETCH_WITH_HEADERS(_ref) {
    let {
      options
    } = _ref;
    return external_wp_apiFetch_default()({ ...options,
      parse: false
    }).then(response => {
      return Promise.all([response.headers, response.status, response.json()]);
    }).then(_ref2 => {
      let [headers, status, data] = _ref2;
      return {
        headers,
        status,
        data
      };
    });
  }

};
/* harmony default export */ var build_module_controls = (controls_controls);
// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/reviews/resolvers.js
/**
 * External dependencies
 */

/**
 * Internal dependencies
 */




function* resolvers_getReviews(query) {
  try {
    const url = Object(external_wp_url_["addQueryArgs"])(`${NAMESPACE}/products/reviews`, query);
    const response = yield fetchWithHeaders({
      path: url,
      method: 'GET'
    });
    const totalCount = parseInt(response.headers.get('x-wp-total'), 10);
    yield updateReviews(query, response.data, totalCount);
  } catch (error) {
    yield reviews_actions_setError(query, error);
  }
}
function* resolvers_getReviewsTotalCount(query) {
  yield resolvers_getReviews(query);
}
// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/reviews/reducer.js
/**
 * Internal dependencies
 */


const reducer_reducer = function () {
  let state = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {
    reviews: {},
    errors: {},
    data: {}
  };
  let {
    type,
    query,
    reviews,
    reviewId,
    reviewData,
    totalCount,
    error,
    isUpdating
  } = arguments.length > 1 ? arguments[1] : undefined;

  switch (type) {
    case reviews_action_types.UPDATE_REVIEWS:
      const ids = [];
      const nextReviews = reviews.reduce((result, review) => {
        ids.push(review.id);
        result[review.id] = { ...(state.data[review.id] || {}),
          ...review
        };
        return result;
      }, {});
      return { ...state,
        reviews: { ...state.reviews,
          [JSON.stringify(query)]: {
            data: ids,
            totalCount
          }
        },
        data: { ...state.data,
          ...nextReviews
        }
      };

    case reviews_action_types.SET_REVIEW:
      return { ...state,
        data: { ...state.data,
          [reviewId]: reviewData
        }
      };

    case reviews_action_types.SET_ERROR:
      return { ...state,
        errors: { ...state.errors,
          [JSON.stringify(query)]: error
        }
      };

    case reviews_action_types.SET_REVIEW_IS_UPDATING:
      return { ...state,
        data: { ...state.data,
          [reviewId]: { ...state.data[reviewId],
            isUpdating
          }
        }
      };

    default:
      return state;
  }
};

/* harmony default export */ var reviews_reducer = (reducer_reducer);
// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/reviews/index.js
/**
 * External dependencies
 */

/**
 * Internal dependencies
 */







Object(external_wp_data_["registerStore"])(reviews_constants_STORE_NAME, {
  reducer: reviews_reducer,
  actions: reviews_actions_namespaceObject,
  controls: build_module_controls,
  selectors: reviews_selectors_namespaceObject,
  resolvers: reviews_resolvers_namespaceObject
});
const REVIEWS_STORE_NAME = reviews_constants_STORE_NAME;
// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/notes/constants.js
/**
 * Internal dependencies
 */
const notes_constants_STORE_NAME = 'wc/admin/notes';
// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/notes/selectors.js
/**
 * External dependencies
 */

const getNotes = rememo((state, query) => {
  const noteIds = state.noteQueries[JSON.stringify(query)] || [];
  return noteIds.map(id => state.notes[id]);
}, (state, query) => [state.noteQueries[JSON.stringify(query)], state.notes]);
const getNotesError = (state, selector) => {
  return state.errors[selector] || false;
};
const isNotesRequesting = (state, selector) => {
  return state.requesting[selector] || false;
};
// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/notes/action-types.js
const notes_action_types_TYPES = {
  SET_ERROR: 'SET_ERROR',
  SET_NOTE: 'SET_NOTE',
  SET_NOTE_IS_UPDATING: 'SET_NOTE_IS_UPDATING',
  SET_NOTES: 'SET_NOTES',
  SET_NOTES_QUERY: 'SET_NOTES_QUERY',
  SET_IS_REQUESTING: 'SET_IS_REQUESTING'
};
/* harmony default export */ var notes_action_types = (notes_action_types_TYPES);
// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/notes/actions.js
/**
 * External dependencies
 */


/**
 * Internal dependencies
 */



function* triggerNoteAction(noteId, actionId) {
  yield notes_actions_setIsRequesting('triggerNoteAction', true);
  const url = `${NAMESPACE}/admin/notes/${noteId}/action/${actionId}`;

  try {
    const result = yield Object(external_wp_dataControls_["apiFetch"])({
      path: url,
      method: 'POST'
    });
    yield updateNote(noteId, result);
    yield notes_actions_setIsRequesting('triggerNoteAction', false);
  } catch (error) {
    yield notes_actions_setError('triggerNoteAction', error);
    yield notes_actions_setIsRequesting('triggerNoteAction', false);
    throw new Error();
  }
}
function* removeNote(noteId) {
  yield notes_actions_setIsRequesting('removeNote', true);
  yield setNoteIsUpdating(noteId, true);

  try {
    const url = `${NAMESPACE}/admin/notes/delete/${noteId}`;
    const response = yield Object(external_wp_dataControls_["apiFetch"])({
      path: url,
      method: 'DELETE'
    });
    yield setNote(noteId, response);
    yield notes_actions_setIsRequesting('removeNote', false);
    return response;
  } catch (error) {
    yield notes_actions_setError('removeNote', error);
    yield notes_actions_setIsRequesting('removeNote', false);
    yield setNoteIsUpdating(noteId, false);
    throw new Error();
  }
}
function* removeAllNotes() {
  let query = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
  yield notes_actions_setIsRequesting('removeAllNotes', true);

  try {
    const url = Object(external_wp_url_["addQueryArgs"])(`${NAMESPACE}/admin/notes/delete/all`, query);
    const notes = yield Object(external_wp_dataControls_["apiFetch"])({
      path: url,
      method: 'DELETE'
    });
    yield setNotes(notes);
    yield notes_actions_setIsRequesting('removeAllNotes', false);
    return notes;
  } catch (error) {
    yield notes_actions_setError('removeAllNotes', error);
    yield notes_actions_setIsRequesting('removeAllNotes', false);
    throw new Error();
  }
}
function* batchUpdateNotes(noteIds, noteFields) {
  yield notes_actions_setIsRequesting('batchUpdateNotes', true);

  try {
    const url = `${NAMESPACE}/admin/notes/update`;
    const notes = yield Object(external_wp_dataControls_["apiFetch"])({
      path: url,
      method: 'PUT',
      data: {
        noteIds,
        ...noteFields
      }
    });
    yield setNotes(notes);
    yield notes_actions_setIsRequesting('batchUpdateNotes', false);
  } catch (error) {
    yield notes_actions_setError('updateNote', error);
    yield notes_actions_setIsRequesting('batchUpdateNotes', false);
    throw new Error();
  }
}
function* updateNote(noteId, noteFields) {
  yield notes_actions_setIsRequesting('updateNote', true);
  yield setNoteIsUpdating(noteId, true);

  try {
    const url = `${NAMESPACE}/admin/notes/${noteId}`;
    const note = yield Object(external_wp_dataControls_["apiFetch"])({
      path: url,
      method: 'PUT',
      data: noteFields
    });
    yield setNote(noteId, note);
    yield notes_actions_setIsRequesting('updateNote', false);
    yield setNoteIsUpdating(noteId, false);
  } catch (error) {
    yield notes_actions_setError('updateNote', error);
    yield notes_actions_setIsRequesting('updateNote', false);
    yield setNoteIsUpdating(noteId, false);
    throw new Error();
  }
}
function setNote(noteId, noteFields) {
  return {
    type: notes_action_types.SET_NOTE,
    noteId,
    noteFields
  };
}
function setNoteIsUpdating(noteId, isUpdating) {
  return {
    type: notes_action_types.SET_NOTE_IS_UPDATING,
    noteId,
    isUpdating
  };
}
function setNotes(notes) {
  return {
    type: notes_action_types.SET_NOTES,
    notes
  };
}
function setNotesQuery(query, noteIds) {
  return {
    type: notes_action_types.SET_NOTES_QUERY,
    query,
    noteIds
  };
}
function notes_actions_setError(selector, error) {
  return {
    type: notes_action_types.SET_ERROR,
    error,
    selector
  };
}
function notes_actions_setIsRequesting(selector, isRequesting) {
  return {
    type: notes_action_types.SET_IS_REQUESTING,
    selector,
    isRequesting
  };
}
// EXTERNAL MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/dompurify@2.3.6/node_modules/dompurify/dist/purify.js
var purify = __webpack_require__(53);

// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/notes/resolvers.js
/**
 * External dependencies
 */




/**
 * Internal dependencies
 */



let notesExceededWarningShown = false;
function* resolvers_getNotes() {
  let query = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
  const url = Object(external_wp_url_["addQueryArgs"])(`${NAMESPACE}/admin/notes`, query);

  try {
    const notes = yield Object(external_wp_dataControls_["apiFetch"])({
      path: url
    });

    if (!notesExceededWarningShown) {
      const noteNames = notes.reduce((filtered, note) => {
        const content = Object(purify["sanitize"])(note.content, {
          ALLOWED_TAGS: []
        });

        if (content.length > 320) {
          filtered.push(note.name);
        }

        return filtered;
      }, []);

      if (noteNames.length) {
        /* eslint-disable no-console */
        console.warn(Object(external_wp_i18n_["sprintf"])(
        /* translators: %s = link to developer blog */
        Object(external_wp_i18n_["__"])('WooCommerce Admin will soon limit inbox note contents to 320 characters. For more information, please visit %s. The following notes currently exceeds that limit:', 'woocommerce-admin'), 'https://developer.woocommerce.com/?p=10749') + '\n' + noteNames.map((name, idx) => {
          return `  ${idx + 1}. ${name}`;
        }).join('\n'));
        /* eslint-enable no-console */

        notesExceededWarningShown = true;
      }
    }

    yield setNotes(notes);
    yield setNotesQuery(query, notes.map(note => note.id));
  } catch (error) {
    yield notes_actions_setError('getNotes', error);
  }
}
// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/notes/reducer.js
/**
 * Internal dependencies
 */


const notesReducer = function () {
  let state = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {
    errors: {},
    noteQueries: {},
    notes: {},
    requesting: {}
  };
  let {
    error,
    isRequesting,
    isUpdating,
    noteFields,
    noteId,
    noteIds,
    notes,
    query,
    selector,
    type
  } = arguments.length > 1 ? arguments[1] : undefined;

  switch (type) {
    case notes_action_types.SET_NOTES:
      state = { ...state,
        notes: { ...state.notes,
          ...notes.reduce((acc, item) => {
            acc[item.id] = item;
            return acc;
          }, {})
        }
      };
      break;

    case notes_action_types.SET_NOTES_QUERY:
      state = { ...state,
        noteQueries: { ...state.noteQueries,
          [JSON.stringify(query)]: noteIds
        }
      };
      break;

    case notes_action_types.SET_ERROR:
      state = { ...state,
        errors: { ...state.errors,
          [selector]: error
        }
      };
      break;

    case notes_action_types.SET_NOTE:
      state = { ...state,
        notes: { ...state.notes,
          [noteId]: noteFields
        }
      };
      break;

    case notes_action_types.SET_NOTE_IS_UPDATING:
      state = { ...state,
        notes: { ...state.notes,
          [noteId]: { ...state.notes[noteId],
            isUpdating
          }
        }
      };
      break;

    case notes_action_types.SET_IS_REQUESTING:
      state = { ...state,
        requesting: { ...state.requesting,
          [selector]: isRequesting
        }
      };
      break;
  }

  return state;
};

/* harmony default export */ var notes_reducer = (notesReducer);
// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/notes/index.js
/**
 * External dependencies
 */


/**
 * Internal dependencies
 */






Object(external_wp_data_["registerStore"])(notes_constants_STORE_NAME, {
  reducer: notes_reducer,
  actions: notes_actions_namespaceObject,
  controls: external_wp_dataControls_["controls"],
  selectors: notes_selectors_namespaceObject,
  resolvers: notes_resolvers_namespaceObject
});
const NOTES_STORE_NAME = notes_constants_STORE_NAME;
// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/reports/constants.js
/**
 * Internal dependencies
 */
const reports_constants_STORE_NAME = 'wc/admin/reports';
// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/reports/selectors.js
/**
 * Internal dependencies
 */

const EMPTY_OBJECT = {};
const selectors_getReportItemsError = (state, endpoint, query) => {
  const resourceName = getResourceName(endpoint, query);
  return state.itemErrors[resourceName] || false;
};
const selectors_getReportItems = (state, endpoint, query) => {
  const resourceName = getResourceName(endpoint, query);
  return state.items[resourceName] || EMPTY_OBJECT;
};
const selectors_getReportStats = (state, endpoint, query) => {
  const resourceName = getResourceName(endpoint, query);
  return state.stats[resourceName] || EMPTY_OBJECT;
};
const selectors_getReportStatsError = (state, endpoint, query) => {
  const resourceName = getResourceName(endpoint, query);
  return state.statErrors[resourceName] || false;
};
// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/reports/action-types.js
const reports_action_types_TYPES = {
  SET_ITEM_ERROR: 'SET_ITEM_ERROR',
  SET_STAT_ERROR: 'SET_STAT_ERROR',
  SET_REPORT_ITEMS: 'SET_REPORT_ITEMS',
  SET_REPORT_STATS: 'SET_REPORT_STATS'
};
/* harmony default export */ var reports_action_types = (reports_action_types_TYPES);
// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/reports/actions.js
/**
 * Internal dependencies
 */


function setReportItemsError(endpoint, query, error) {
  const resourceName = getResourceName(endpoint, query);
  return {
    type: reports_action_types.SET_ITEM_ERROR,
    resourceName,
    error
  };
}
function setReportItems(endpoint, query, items) {
  const resourceName = getResourceName(endpoint, query);
  return {
    type: reports_action_types.SET_REPORT_ITEMS,
    resourceName,
    items
  };
}
function setReportStats(endpoint, query, stats) {
  const resourceName = getResourceName(endpoint, query);
  return {
    type: reports_action_types.SET_REPORT_STATS,
    resourceName,
    stats
  };
}
function setReportStatsError(endpoint, query, error) {
  const resourceName = getResourceName(endpoint, query);
  return {
    type: reports_action_types.SET_STAT_ERROR,
    resourceName,
    error
  };
}
// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/reports/resolvers.js
/**
 * External dependencies
 */

/**
 * Internal dependencies
 */




function* resolvers_getReportItems(endpoint, query) {
  const fetchArgs = {
    parse: false,
    path: Object(external_wp_url_["addQueryArgs"])(`${NAMESPACE}/reports/${endpoint}`, query)
  };

  try {
    const response = yield fetchWithHeaders(fetchArgs);
    const data = response.data;
    const totalResults = parseInt(response.headers.get('x-wp-total'), 10);
    const totalPages = parseInt(response.headers.get('x-wp-totalpages'), 10);
    yield setReportItems(endpoint, query, {
      data,
      totalResults,
      totalPages
    });
  } catch (error) {
    yield setReportItemsError(endpoint, query, error);
  }
}
function* resolvers_getReportStats(endpoint, query) {
  const fetchArgs = {
    parse: false,
    path: Object(external_wp_url_["addQueryArgs"])(`${NAMESPACE}/reports/${endpoint}/stats`, query)
  };

  try {
    const response = yield fetchWithHeaders(fetchArgs);
    const data = response.data;
    const totalResults = parseInt(response.headers.get('x-wp-total'), 10);
    const totalPages = parseInt(response.headers.get('x-wp-totalpages'), 10);
    yield setReportStats(endpoint, query, {
      data,
      totalResults,
      totalPages
    });
  } catch (error) {
    yield setReportStatsError(endpoint, query, error);
  }
}
// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/reports/reducer.js
/**
 * Internal dependencies
 */


const reports = function () {
  let state = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {
    itemErrors: {},
    items: {},
    statErrors: {},
    stats: {}
  };
  let {
    type,
    items,
    stats,
    error,
    resourceName
  } = arguments.length > 1 ? arguments[1] : undefined;

  switch (type) {
    case reports_action_types.SET_REPORT_ITEMS:
      return { ...state,
        items: { ...state.items,
          [resourceName]: items
        }
      };

    case reports_action_types.SET_REPORT_STATS:
      return { ...state,
        stats: { ...state.stats,
          [resourceName]: stats
        }
      };

    case reports_action_types.SET_ITEM_ERROR:
      return { ...state,
        itemErrors: { ...state.itemErrors,
          [resourceName]: error
        }
      };

    case reports_action_types.SET_STAT_ERROR:
      return { ...state,
        statErrors: { ...state.statErrors,
          [resourceName]: error
        }
      };

    default:
      return state;
  }
};

/* harmony default export */ var reports_reducer = (reports);
// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/reports/index.js
/**
 * External dependencies
 */

/**
 * Internal dependencies
 */







Object(external_wp_data_["registerStore"])(reports_constants_STORE_NAME, {
  reducer: reports_reducer,
  actions: reports_actions_namespaceObject,
  controls: build_module_controls,
  selectors: reports_selectors_namespaceObject,
  resolvers: reports_resolvers_namespaceObject
});
const REPORTS_STORE_NAME = reports_constants_STORE_NAME;
// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/items/constants.js
const items_constants_STORE_NAME = 'wc/admin/items';
// EXTERNAL MODULE: external ["wc","date"]
var external_wc_date_ = __webpack_require__(20);

// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/items/utils.js
/**
 * External dependencies
 */

/**
 * Internal dependencies
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
  const endpoint = 'leaderboards';
  const {
    per_page: perPage,
    persisted_query: persistedQuery,
    query,
    select,
    filterQuery
  } = options;
  const {
    getItems,
    getItemsError,
    isResolving
  } = select(items_constants_STORE_NAME);
  const response = {
    isRequesting: false,
    isError: false,
    rows: []
  };
  const datesFromQuery = Object(external_wc_date_["getCurrentDates"])(query, options.defaultDateRange);
  const leaderboardQuery = { ...filterQuery,
    after: Object(external_wc_date_["appendTimestamp"])(datesFromQuery.primary.after, 'start'),
    before: Object(external_wc_date_["appendTimestamp"])(datesFromQuery.primary.before, 'end'),
    per_page: perPage,
    persisted_query: JSON.stringify(persistedQuery)
  }; // Disable eslint rule requiring `getItems` to be defined below because the next two statements
  // depend on `getItems` to have been called.
  // eslint-disable-next-line @wordpress/no-unused-vars-before-return

  const leaderboards = getItems(endpoint, leaderboardQuery);

  if (isResolving('getItems', [endpoint, leaderboardQuery])) {
    return { ...response,
      isRequesting: true
    };
  } else if (getItemsError(endpoint, leaderboardQuery)) {
    return { ...response,
      isError: true
    };
  }

  const leaderboard = leaderboards.get(options.id);
  return { ...response,
    rows: leaderboard === null || leaderboard === void 0 ? void 0 : leaderboard.rows
  };
}
/**
 * Returns items based on a search query.
 *
 * @param  {Object}   selector    Instance of @wordpress/select response
 * @param  {string}   endpoint  Report API Endpoint
 * @param  {string[]} search    Array of search strings.
 * @param  {Object}   options  Query options.
 * @return {Object}   Object containing API request information and the matching items.
 */

function searchItemsByString(selector, endpoint, search) {
  let options = arguments.length > 3 && arguments[3] !== undefined ? arguments[3] : {};
  const {
    getItems,
    getItemsError,
    isResolving
  } = selector;
  const items = {};
  let isRequesting = false;
  let isError = false;
  search.forEach(searchWord => {
    const query = {
      search: searchWord,
      per_page: 10,
      ...options
    };
    const newItems = getItems(endpoint, query);
    newItems.forEach((item, id) => {
      items[id] = item;
    });

    if (isResolving('getItems', [endpoint, query])) {
      isRequesting = true;
    }

    if (getItemsError(endpoint, query)) {
      isError = true;
    }
  });
  return {
    items,
    isRequesting,
    isError
  };
}
/**
 * Generate a resource name for item totals count.
 *
 * It omits query parameters from the identifier that don't affect
 * totals values like pagination and response field filtering.
 *
 * @param {string} itemType Item type for totals count.
 * @param {Object} query Query for item totals count.
 * @return {string} Resource name for item totals.
 */

function getTotalCountResourceName(itemType, query) {
  // Disable eslint rule because we're using this spread to omit properties
  // that don't affect item totals count results.
  // eslint-disable-next-line no-unused-vars, camelcase
  const {
    _fields,
    page,
    per_page,
    ...totalsQuery
  } = query;
  return getResourceName('total-' + itemType, totalsQuery);
}
// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/items/selectors.js
/**
 * External dependencies
 */

/**
 * Internal dependencies
 */



const selectors_getItems = rememo(function (state, itemType, query) {
  let defaultValue = arguments.length > 3 && arguments[3] !== undefined ? arguments[3] : new Map();
  const resourceName = getResourceName(itemType, query);
  const ids = state.items[resourceName] && state.items[resourceName].data;

  if (!ids) {
    return defaultValue;
  }

  return ids.reduce((map, id) => {
    map.set(id, state.data[itemType][id]);
    return map;
  }, new Map());
}, (state, itemType, query) => {
  const resourceName = getResourceName(itemType, query);
  return [state.items[resourceName]];
});
const getItemsTotalCount = function (state, itemType, query) {
  let defaultValue = arguments.length > 3 && arguments[3] !== undefined ? arguments[3] : 0;
  const resourceName = getTotalCountResourceName(itemType, query);
  const totalCount = state.items.hasOwnProperty(resourceName) ? state.items[resourceName] : defaultValue;
  return totalCount;
};
const selectors_getItemsError = (state, itemType, query) => {
  const resourceName = getResourceName(itemType, query);
  return state.errors[resourceName];
};
// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/items/action-types.js
const items_action_types_TYPES = {
  SET_ITEM: 'SET_ITEM',
  SET_ITEMS: 'SET_ITEMS',
  SET_ITEMS_TOTAL_COUNT: 'SET_ITEMS_TOTAL_COUNT',
  SET_ERROR: 'SET_ERROR'
};
/* harmony default export */ var items_action_types = (items_action_types_TYPES);
// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/items/actions.js
/**
 * External dependencies
 */


/**
 * Internal dependencies
 */



function setItem(itemType, id, item) {
  return {
    type: items_action_types.SET_ITEM,
    id,
    item,
    itemType
  };
}
function setItems(itemType, query, items, totalCount) {
  return {
    type: items_action_types.SET_ITEMS,
    items,
    itemType,
    query,
    totalCount
  };
}
function setItemsTotalCount(itemType, query, totalCount) {
  return {
    type: items_action_types.SET_ITEMS_TOTAL_COUNT,
    itemType,
    query,
    totalCount
  };
}
function items_actions_setError(itemType, query, error) {
  return {
    type: items_action_types.SET_ERROR,
    itemType,
    query,
    error
  };
}
function* updateProductStock(product, quantity) {
  const updatedProduct = { ...product,
    stock_quantity: quantity
  };
  const {
    id,
    parent_id: parentId,
    type
  } = updatedProduct; // Optimistically update product stock.

  yield setItem('products', id, updatedProduct);
  let url = NAMESPACE;

  switch (type) {
    case 'variation':
      url += `/products/${parentId}/variations/${id}`;
      break;

    case 'variable':
    case 'simple':
    default:
      url += `/products/${id}`;
  }

  try {
    yield Object(external_wp_dataControls_["apiFetch"])({
      path: url,
      method: 'PUT',
      data: updatedProduct
    });
    return true;
  } catch (error) {
    // Update failed, return product back to original state.
    yield setItem('products', id, product);
    yield items_actions_setError('products', id, error);
    return false;
  }
}
function* createProductFromTemplate(itemFields, query) {
  try {
    const url = Object(external_wp_url_["addQueryArgs"])(`${WC_ADMIN_NAMESPACE}/onboarding/tasks/create_product_from_template`, query || {});
    const newItem = yield Object(external_wp_dataControls_["apiFetch"])({
      path: url,
      method: 'POST',
      data: itemFields
    });
    yield setItem('products', newItem.id, newItem);
    return newItem;
  } catch (error) {
    yield items_actions_setError('createProductFromTemplate', query, error);
    throw error;
  }
}
// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/items/resolvers.js
/**
 * External dependencies
 */


/**
 * Internal dependencies
 */





function* request(itemType, query) {
  const endpoint = itemType === 'categories' ? 'products/categories' : itemType;
  const url = Object(external_wp_url_["addQueryArgs"])(`${NAMESPACE}/${endpoint}`, query);
  const isUnboundedRequest = query.per_page === -1;
  const fetch = isUnboundedRequest ? external_wp_dataControls_["apiFetch"] : fetchWithHeaders;
  const response = yield fetch({
    path: url,
    method: 'GET'
  });

  if (isUnboundedRequest) {
    return {
      items: response,
      totalCount: response.length
    };
  }

  const totalCount = parseInt(response.headers.get('x-wp-total'), 10);
  return {
    items: response.data,
    totalCount
  };
}

function* resolvers_getItems(itemType, query) {
  try {
    const {
      items,
      totalCount
    } = yield request(itemType, query);
    yield setItemsTotalCount(itemType, query, totalCount);
    yield setItems(itemType, query, items);
  } catch (error) {
    yield items_actions_setError(itemType, query, error);
  }
}
function* items_resolvers_getReviewsTotalCount(itemType, query) {
  yield resolvers_getItemsTotalCount(itemType, query);
}
function* resolvers_getItemsTotalCount(itemType, query) {
  try {
    const totalsQuery = { ...query,
      page: 1,
      per_page: 1
    };
    const {
      totalCount
    } = yield request(itemType, totalsQuery);
    yield setItemsTotalCount(itemType, query, totalCount);
  } catch (error) {
    yield items_actions_setError(itemType, query, error);
  }
}
// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/items/reducer.js
/**
 * Internal dependencies
 */




const items_reducer_reducer = function () {
  let state = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {
    items: {},
    errors: {},
    data: {}
  };
  let {
    type,
    id,
    itemType,
    query,
    item,
    items,
    totalCount,
    error
  } = arguments.length > 1 ? arguments[1] : undefined;

  switch (type) {
    case items_action_types.SET_ITEM:
      const itemData = state.data[itemType] || {};
      return { ...state,
        data: { ...state.data,
          [itemType]: { ...itemData,
            [id]: { ...(itemData[id] || {}),
              ...item
            }
          }
        }
      };

    case items_action_types.SET_ITEMS:
      const ids = [];
      const nextItems = items.reduce((result, theItem) => {
        ids.push(theItem.id);
        result[theItem.id] = theItem;
        return result;
      }, {});
      const resourceName = getResourceName(itemType, query);
      return { ...state,
        items: { ...state.items,
          [resourceName]: {
            data: ids
          }
        },
        data: { ...state.data,
          [itemType]: { ...state.data[itemType],
            ...nextItems
          }
        }
      };

    case items_action_types.SET_ITEMS_TOTAL_COUNT:
      const totalResourceName = getTotalCountResourceName(itemType, query);
      return { ...state,
        items: { ...state.items,
          [totalResourceName]: totalCount
        }
      };

    case items_action_types.SET_ERROR:
      return { ...state,
        errors: { ...state.errors,
          [getResourceName(itemType, query)]: error
        }
      };

    default:
      return state;
  }
};

/* harmony default export */ var items_reducer = (items_reducer_reducer);
// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/items/index.js
/**
 * External dependencies
 */

/**
 * Internal dependencies
 */







Object(external_wp_data_["registerStore"])(items_constants_STORE_NAME, {
  reducer: items_reducer,
  actions: items_actions_namespaceObject,
  controls: build_module_controls,
  selectors: items_selectors_namespaceObject,
  resolvers: items_resolvers_namespaceObject
});
const ITEMS_STORE_NAME = items_constants_STORE_NAME;
// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/countries/constants.js
const countries_constants_STORE_NAME = 'wc/admin/countries';
// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/countries/selectors.js
const getLocales = state => {
  return state.locales;
};
const getLocale = (state, id) => {
  const country = id.split(':')[0];
  return state.locales[country];
};
const getCountries = state => {
  return state.countries;
};
const getCountry = (state, code) => {
  return state.countries.find(country => country.code === code);
};
// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/countries/action-types.js
var countries_action_types_TYPES;

(function (TYPES) {
  TYPES["GET_LOCALES_ERROR"] = "GET_LOCALES_ERROR";
  TYPES["GET_LOCALES_SUCCESS"] = "GET_LOCALES_SUCCESS";
  TYPES["GET_COUNTRIES_ERROR"] = "GET_COUNTRIES_ERROR";
  TYPES["GET_COUNTRIES_SUCCESS"] = "GET_COUNTRIES_SUCCESS";
})(countries_action_types_TYPES || (countries_action_types_TYPES = {}));

/* harmony default export */ var countries_action_types = (countries_action_types_TYPES);
// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/countries/actions.js
/**
 * Internal dependencies
 */

function getLocalesSuccess(locales) {
  return {
    type: countries_action_types.GET_LOCALES_SUCCESS,
    locales
  };
}
function getLocalesError(error) {
  return {
    type: countries_action_types.GET_LOCALES_ERROR,
    error
  };
}
function getCountriesSuccess(countries) {
  return {
    type: countries_action_types.GET_COUNTRIES_SUCCESS,
    countries
  };
}
function getCountriesError(error) {
  return {
    type: countries_action_types.GET_COUNTRIES_ERROR,
    error
  };
}
// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/countries/resolvers.js
/**
 * External dependencies
 */


/**
 * Internal dependencies
 */




const countries_resolvers_resolveSelect = external_wp_data_["controls"] && external_wp_data_["controls"].resolveSelect ? external_wp_data_["controls"].resolveSelect : external_wp_dataControls_["select"];
function* resolvers_getLocale() {
  yield countries_resolvers_resolveSelect(countries_constants_STORE_NAME, 'getLocales');
}
function* resolvers_getLocales() {
  try {
    const url = NAMESPACE + '/data/countries/locales';
    const results = yield Object(external_wp_dataControls_["apiFetch"])({
      path: url,
      method: 'GET'
    });
    return getLocalesSuccess(results);
  } catch (error) {
    return getLocalesError(error);
  }
}
function* resolvers_getCountry() {
  yield countries_resolvers_resolveSelect(countries_constants_STORE_NAME, 'getCountries');
}
function* resolvers_getCountries() {
  try {
    const url = NAMESPACE + '/data/countries';
    const results = yield Object(external_wp_dataControls_["apiFetch"])({
      path: url,
      method: 'GET'
    });
    return getCountriesSuccess(results);
  } catch (error) {
    return getCountriesError(error);
  }
}
// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/countries/reducer.js
/**
 * Internal dependencies
 */


const countries_reducer_reducer = function () {
  let state = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {
    errors: {},
    locales: {},
    countries: []
  };
  let {
    type,
    error,
    locales,
    countries
  } = arguments.length > 1 ? arguments[1] : undefined;

  switch (type) {
    case countries_action_types.GET_LOCALES_SUCCESS:
      state = { ...state,
        locales
      };
      break;

    case countries_action_types.GET_LOCALES_ERROR:
      state = { ...state,
        errors: { ...state.errors,
          locales: error
        }
      };
      break;

    case countries_action_types.GET_COUNTRIES_SUCCESS:
      state = { ...state,
        countries
      };
      break;

    case countries_action_types.GET_COUNTRIES_ERROR:
      state = { ...state,
        errors: { ...state.errors,
          countries: error
        }
      };
      break;
  }

  return state;
};

/* harmony default export */ var countries_reducer = (countries_reducer_reducer);
// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/countries/index.js
/**
 * External dependencies
 */


/**
 * Internal dependencies
 */






Object(external_wp_data_["registerStore"])(countries_constants_STORE_NAME, {
  reducer: countries_reducer,
  actions: countries_actions_namespaceObject,
  controls: external_wp_dataControls_["controls"],
  selectors: countries_selectors_namespaceObject,
  resolvers: countries_resolvers_namespaceObject
});
const COUNTRIES_STORE_NAME = countries_constants_STORE_NAME;
// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/navigation/constants.js
const navigation_constants_STORE_NAME = 'woocommerce-navigation';
// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/navigation/selectors.js
/**
 * External dependencies
 */

const MENU_ITEMS_HOOK = 'woocommerce_navigation_menu_items';
const getMenuItems = state => {
  /**
   * Navigation Menu Items.
   *
   * @filter woocommerce_navigation_menu_items
   * @param {Array.<Object>} menuItems Array of Navigation menu items.
   */
  return Object(external_wp_hooks_["applyFilters"])(MENU_ITEMS_HOOK, state.menuItems);
};
const getFavorites = state => {
  return state.favorites || [];
};
const isNavigationRequesting = (state, selector) => {
  return state.requesting[selector] || false;
};
const getPersistedQuery = state => {
  return state.persistedQuery || {};
};
// EXTERNAL MODULE: external ["wc","navigation"]
var external_wc_navigation_ = __webpack_require__(13);

// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/navigation/action-types.js
const navigation_action_types_TYPES = {
  ADD_MENU_ITEMS: 'ADD_MENU_ITEMS',
  SET_MENU_ITEMS: 'SET_MENU_ITEMS',
  ON_HISTORY_CHANGE: 'ON_HISTORY_CHANGE',
  ADD_FAVORITE_FAILURE: 'ADD_FAVORITE_FAILURE',
  ADD_FAVORITE_REQUEST: 'ADD_FAVORITE_REQUEST',
  ADD_FAVORITE_SUCCESS: 'ADD_FAVORITE_SUCCESS',
  GET_FAVORITES_FAILURE: 'GET_FAVORITES_FAILURE',
  GET_FAVORITES_REQUEST: 'GET_FAVORITES_REQUEST',
  GET_FAVORITES_SUCCESS: 'GET_FAVORITES_SUCCESS',
  REMOVE_FAVORITE_FAILURE: 'REMOVE_FAVORITE_FAILURE',
  REMOVE_FAVORITE_REQUEST: 'REMOVE_FAVORITE_REQUEST',
  REMOVE_FAVORITE_SUCCESS: 'REMOVE_FAVORITE_SUCCESS'
};
/* harmony default export */ var navigation_action_types = (navigation_action_types_TYPES);
// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/navigation/actions.js
/**
 * External dependencies
 */


/**
 * Internal dependencies
 */



function actions_setMenuItems(menuItems) {
  return {
    type: navigation_action_types.SET_MENU_ITEMS,
    menuItems
  };
}
function addMenuItems(menuItems) {
  return {
    type: navigation_action_types.ADD_MENU_ITEMS,
    menuItems
  };
}
function getFavoritesFailure(error) {
  return {
    type: navigation_action_types.GET_FAVORITES_FAILURE,
    error
  };
}
function getFavoritesRequest(favorites) {
  return {
    type: navigation_action_types.GET_FAVORITES_REQUEST,
    favorites
  };
}
function getFavoritesSuccess(favorites) {
  return {
    type: navigation_action_types.GET_FAVORITES_SUCCESS,
    favorites
  };
}
function addFavoriteRequest(favorite) {
  return {
    type: navigation_action_types.ADD_FAVORITE_REQUEST,
    favorite
  };
}
function addFavoriteFailure(favorite, error) {
  return {
    type: navigation_action_types.ADD_FAVORITE_FAILURE,
    favorite,
    error
  };
}
function addFavoriteSuccess(favorite) {
  return {
    type: navigation_action_types.ADD_FAVORITE_SUCCESS,
    favorite
  };
}
function removeFavoriteRequest(favorite) {
  return {
    type: navigation_action_types.REMOVE_FAVORITE_REQUEST,
    favorite
  };
}
function removeFavoriteFailure(favorite, error) {
  return {
    type: navigation_action_types.REMOVE_FAVORITE_FAILURE,
    favorite,
    error
  };
}
function removeFavoriteSuccess(favorite, error) {
  return {
    type: navigation_action_types.REMOVE_FAVORITE_SUCCESS,
    favorite,
    error
  };
}
function* actions_onLoad() {
  yield actions_onHistoryChange();
}
function* actions_onHistoryChange() {
  const persistedQuery = Object(external_wc_navigation_["getPersistedQuery"])();

  if (!Object.keys(persistedQuery).length) {
    return null;
  }

  yield {
    type: navigation_action_types.ON_HISTORY_CHANGE,
    persistedQuery
  };
}
function* addFavorite(favorite) {
  yield addFavoriteRequest(favorite);

  try {
    const results = yield external_wp_apiFetch_default()({
      path: `${WC_ADMIN_NAMESPACE}/navigation/favorites/me`,
      method: 'POST',
      data: {
        item_id: favorite
      }
    });

    if (results) {
      yield addFavoriteSuccess(favorite);
      return results;
    }

    throw new Error();
  } catch (error) {
    yield addFavoriteFailure(favorite, error);
    throw new Error();
  }
}
function* removeFavorite(favorite) {
  yield removeFavoriteRequest(favorite);

  try {
    const results = yield external_wp_apiFetch_default()({
      path: `${WC_ADMIN_NAMESPACE}/navigation/favorites/me`,
      method: 'DELETE',
      data: {
        item_id: favorite
      }
    });

    if (results) {
      yield removeFavoriteSuccess(favorite);
      return results;
    }

    throw new Error();
  } catch (error) {
    yield removeFavoriteFailure(favorite, error);
    throw new Error();
  }
}
// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/navigation/reducer.js
/**
 * Internal dependencies
 */


const navigation_reducer_reducer = function () {
  let state = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {
    error: null,
    menuItems: [],
    favorites: [],
    requesting: {},
    persistedQuery: {}
  };
  let {
    type,
    error,
    favorite,
    favorites,
    menuItems,
    persistedQuery
  } = arguments.length > 1 ? arguments[1] : undefined;

  switch (type) {
    case navigation_action_types.SET_MENU_ITEMS:
      state = { ...state,
        menuItems
      };
      break;

    case navigation_action_types.ADD_MENU_ITEMS:
      state = { ...state,
        menuItems: [...state.menuItems, ...menuItems]
      };
      break;

    case navigation_action_types.ON_HISTORY_CHANGE:
      state = { ...state,
        persistedQuery
      };
      break;

    case navigation_action_types.GET_FAVORITES_FAILURE:
      state = { ...state,
        requesting: { ...state.requesting,
          getFavorites: false
        }
      };
      break;

    case navigation_action_types.GET_FAVORITES_REQUEST:
      state = { ...state,
        requesting: { ...state.requesting,
          getFavorites: true
        }
      };
      break;

    case navigation_action_types.GET_FAVORITES_SUCCESS:
      state = { ...state,
        favorites,
        requesting: { ...state.requesting,
          getFavorites: false
        }
      };
      break;

    case navigation_action_types.ADD_FAVORITE_FAILURE:
      state = { ...state,
        error,
        requesting: { ...state.requesting,
          addFavorite: false
        }
      };
      break;

    case navigation_action_types.ADD_FAVORITE_REQUEST:
      state = { ...state,
        requesting: { ...state.requesting,
          addFavorite: true
        }
      };
      break;

    case navigation_action_types.ADD_FAVORITE_SUCCESS:
      const newFavorites = !state.favorites.includes(favorite) ? [...state.favorites, favorite] : state.favorites;
      state = { ...state,
        favorites: newFavorites,
        menuItems: state.menuItems.map(item => {
          if (item.id === favorite) {
            return { ...item,
              menuId: 'favorites'
            };
          }

          return item;
        }),
        requesting: { ...state.requesting,
          addFavorite: false
        }
      };
      break;

    case navigation_action_types.REMOVE_FAVORITE_FAILURE:
      state = { ...state,
        requesting: { ...state.requesting,
          error,
          removeFavorite: false
        }
      };
      break;

    case navigation_action_types.REMOVE_FAVORITE_REQUEST:
      state = { ...state,
        requesting: { ...state.requesting,
          removeFavorite: true
        }
      };
      break;

    case navigation_action_types.REMOVE_FAVORITE_SUCCESS:
      const filteredFavorites = state.favorites.filter(f => f !== favorite);
      state = { ...state,
        favorites: filteredFavorites,
        menuItems: state.menuItems.map(item => {
          if (item.id === favorite) {
            return { ...item,
              menuId: 'plugins'
            };
          }

          return item;
        }),
        requesting: { ...state.requesting,
          removeFavorite: false
        }
      };
      break;
  }

  return state;
};

/* harmony default export */ var navigation_reducer = (navigation_reducer_reducer);
// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/navigation/resolvers.js
/**
 * External dependencies
 */

/**
 * Internal dependencies
 */



function* resolvers_getFavorites() {
  yield getFavoritesRequest();

  try {
    const results = yield Object(external_wp_dataControls_["apiFetch"])({
      path: `${WC_ADMIN_NAMESPACE}/navigation/favorites/me`
    });

    if (results) {
      yield getFavoritesSuccess(results);
      return;
    }

    throw new Error();
  } catch (error) {
    yield getFavoritesFailure(error);
    throw new Error();
  }
}
// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/navigation/dispatchers.js
/**
 * External dependencies
 */


/**
 * Internal dependencies
 */


/* harmony default export */ var dispatchers = (async () => {
  const {
    onLoad,
    onHistoryChange
  } = Object(external_wp_data_["dispatch"])(navigation_constants_STORE_NAME);
  await onLoad();
  Object(external_wc_navigation_["addHistoryListener"])(async () => {
    setTimeout(async () => {
      await onHistoryChange();
    }, 0);
  });
});
// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/navigation/index.js
/**
 * External dependencies
 */


/**
 * Internal dependencies
 */







Object(external_wp_data_["registerStore"])(navigation_constants_STORE_NAME, {
  reducer: navigation_reducer,
  actions: navigation_actions_namespaceObject,
  controls: external_wp_dataControls_["controls"],
  resolvers: navigation_resolvers_namespaceObject,
  selectors: navigation_selectors_namespaceObject
});
dispatchers();
const NAVIGATION_STORE_NAME = navigation_constants_STORE_NAME;
// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/navigation/with-navigation-hydration.js
/**
 * External dependencies
 */



/**
 * Internal dependencies
 */


/**
 * Higher-order component used to hydrate navigation data.
 *
 * @param {Object} data Data object with menu items and site information.
 */

const withNavigationHydration = data => Object(external_wp_compose_["createHigherOrderComponent"])(OriginalComponent => props => {
  const dataRef = Object(external_wp_element_["useRef"])(data);
  Object(external_wp_data_["useSelect"])((select, registry) => {
    if (!dataRef.current) {
      return;
    }

    const {
      isResolving,
      hasFinishedResolution
    } = select(navigation_constants_STORE_NAME);
    const {
      startResolution,
      finishResolution,
      setMenuItems
    } = registry.dispatch(navigation_constants_STORE_NAME);

    if (!isResolving('getMenuItems') && !hasFinishedResolution('getMenuItems')) {
      startResolution('getMenuItems', []);
      setMenuItems(dataRef.current.menuItems);
      finishResolution('getMenuItems', []);
    }
  });
  return Object(external_wp_element_["createElement"])(OriginalComponent, { ...props
  });
}, 'withNavigationHydration');
// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/payment-gateways/action-types.js
var action_types_ACTION_TYPES;

(function (ACTION_TYPES) {
  ACTION_TYPES["GET_PAYMENT_GATEWAYS_REQUEST"] = "GET_PAYMENT_GATEWAYS_REQUEST";
  ACTION_TYPES["GET_PAYMENT_GATEWAYS_SUCCESS"] = "GET_PAYMENT_GATEWAYS_SUCCESS";
  ACTION_TYPES["GET_PAYMENT_GATEWAYS_ERROR"] = "GET_PAYMENT_GATEWAYS_ERROR";
  ACTION_TYPES["UPDATE_PAYMENT_GATEWAY_REQUEST"] = "UPDATE_PAYMENT_GATEWAY_REQUEST";
  ACTION_TYPES["UPDATE_PAYMENT_GATEWAY_SUCCESS"] = "UPDATE_PAYMENT_GATEWAY_SUCCESS";
  ACTION_TYPES["UPDATE_PAYMENT_GATEWAY_ERROR"] = "UPDATE_PAYMENT_GATEWAY_ERROR";
  ACTION_TYPES["GET_PAYMENT_GATEWAY_REQUEST"] = "GET_PAYMENT_GATEWAY_REQUEST";
  ACTION_TYPES["GET_PAYMENT_GATEWAY_SUCCESS"] = "GET_PAYMENT_GATEWAY_SUCCESS";
  ACTION_TYPES["GET_PAYMENT_GATEWAY_ERROR"] = "GET_PAYMENT_GATEWAY_ERROR";
})(action_types_ACTION_TYPES || (action_types_ACTION_TYPES = {}));
// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/payment-gateways/constants.js
const STORE_KEY = 'wc/payment-gateways';
const API_NAMESPACE = 'wc/v3';
// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/payment-gateways/actions.js
/**
 * External dependencies
 */

/**
 * Internal dependencies
 */



function getPaymentGatewaysRequest() {
  return {
    type: action_types_ACTION_TYPES.GET_PAYMENT_GATEWAYS_REQUEST
  };
}
function getPaymentGatewaysSuccess(paymentGateways) {
  return {
    type: action_types_ACTION_TYPES.GET_PAYMENT_GATEWAYS_SUCCESS,
    paymentGateways
  };
}
function getPaymentGatewaysError(error) {
  return {
    type: action_types_ACTION_TYPES.GET_PAYMENT_GATEWAYS_ERROR,
    error
  };
}
function getPaymentGatewayRequest() {
  return {
    type: action_types_ACTION_TYPES.GET_PAYMENT_GATEWAY_REQUEST
  };
}
function getPaymentGatewayError(error) {
  return {
    type: action_types_ACTION_TYPES.GET_PAYMENT_GATEWAY_ERROR,
    error
  };
}
function getPaymentGatewaySuccess(paymentGateway) {
  return {
    type: action_types_ACTION_TYPES.GET_PAYMENT_GATEWAY_SUCCESS,
    paymentGateway
  };
}
function updatePaymentGatewaySuccess(paymentGateway) {
  return {
    type: action_types_ACTION_TYPES.UPDATE_PAYMENT_GATEWAY_SUCCESS,
    paymentGateway
  };
}
function updatePaymentGatewayRequest() {
  return {
    type: action_types_ACTION_TYPES.UPDATE_PAYMENT_GATEWAY_REQUEST
  };
}
function updatePaymentGatewayError(error) {
  return {
    type: action_types_ACTION_TYPES.UPDATE_PAYMENT_GATEWAY_ERROR,
    error
  };
}
function* updatePaymentGateway(id, data) {
  try {
    yield updatePaymentGatewayRequest();
    const response = yield Object(external_wp_dataControls_["apiFetch"])({
      method: 'PUT',
      path: API_NAMESPACE + '/payment_gateways/' + id,
      body: JSON.stringify(data)
    });

    if (response && response.id === id) {
      // Update the already loaded payment gateway list with the new data
      yield updatePaymentGatewaySuccess(response);
      return response;
    }
  } catch (e) {
    yield updatePaymentGatewayError(e);
    throw e;
  }
}
// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/payment-gateways/resolvers.js
/**
 * External dependencies
 */


/**
 * Internal dependencies
 */


 // Can be removed in WP 5.9.

const payment_gateways_resolvers_dispatch = external_wp_data_["controls"] && external_wp_data_["controls"].dispatch ? external_wp_data_["controls"].dispatch : external_wp_dataControls_["dispatch"];
function* getPaymentGateways() {
  yield getPaymentGatewaysRequest();

  try {
    const response = yield Object(external_wp_dataControls_["apiFetch"])({
      path: API_NAMESPACE + '/payment_gateways'
    });
    yield getPaymentGatewaysSuccess(response);

    for (let i = 0; i < response.length; i++) {
      yield payment_gateways_resolvers_dispatch(STORE_KEY, 'finishResolution', 'getPaymentGateway', [response[i].id]);
    }
  } catch (e) {
    yield getPaymentGatewaysError(e);
  }
}
function* getPaymentGateway(id) {
  yield getPaymentGatewayRequest();

  try {
    const response = yield Object(external_wp_dataControls_["apiFetch"])({
      path: API_NAMESPACE + '/payment_gateways/' + id
    });

    if (response && response.id) {
      yield getPaymentGatewaySuccess(response);
      return response;
    }
  } catch (e) {
    yield getPaymentGatewayError(e);
  }
}
// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/payment-gateways/selectors.js
function selectors_getPaymentGateway(state, id) {
  return state.paymentGateways.find(paymentGateway => paymentGateway.id === id);
}
function selectors_getPaymentGateways(state) {
  return state.paymentGateways;
}
function selectors_getPaymentGatewayError(state, selector) {
  return state.errors[selector] || null;
}
function isPaymentGatewayUpdating(state) {
  return state.isUpdating || false;
}
// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/payment-gateways/reducer.js
/**
 * Internal dependencies
 */


function updatePaymentGatewayList(state, paymentGateway) {
  const targetIndex = state.paymentGateways.findIndex(gateway => gateway.id === paymentGateway.id);

  if (targetIndex === -1) {
    return { ...state,
      paymentGateways: [...state.paymentGateways, paymentGateway],
      isUpdating: false
    };
  }

  return { ...state,
    paymentGateways: [...state.paymentGateways.slice(0, targetIndex), paymentGateway, ...state.paymentGateways.slice(targetIndex + 1)],
    isUpdating: false
  };
}

const payment_gateways_reducer_reducer = function () {
  let state = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {
    paymentGateways: [],
    isUpdating: false,
    errors: {}
  };
  let payload = arguments.length > 1 ? arguments[1] : undefined;

  if (payload && 'type' in payload) {
    switch (payload.type) {
      case action_types_ACTION_TYPES.GET_PAYMENT_GATEWAYS_REQUEST:
      case action_types_ACTION_TYPES.GET_PAYMENT_GATEWAY_REQUEST:
        return state;

      case action_types_ACTION_TYPES.GET_PAYMENT_GATEWAYS_SUCCESS:
        return { ...state,
          paymentGateways: payload.paymentGateways
        };

      case action_types_ACTION_TYPES.GET_PAYMENT_GATEWAYS_ERROR:
        return { ...state,
          errors: { ...state.errors,
            getPaymentGateways: payload.error
          }
        };

      case action_types_ACTION_TYPES.GET_PAYMENT_GATEWAY_ERROR:
        return { ...state,
          errors: { ...state.errors,
            getPaymentGateway: payload.error
          }
        };

      case action_types_ACTION_TYPES.UPDATE_PAYMENT_GATEWAY_REQUEST:
        return { ...state,
          isUpdating: true
        };

      case action_types_ACTION_TYPES.UPDATE_PAYMENT_GATEWAY_SUCCESS:
        return updatePaymentGatewayList(state, payload.paymentGateway);

      case action_types_ACTION_TYPES.GET_PAYMENT_GATEWAY_SUCCESS:
        return updatePaymentGatewayList(state, payload.paymentGateway);

      case action_types_ACTION_TYPES.UPDATE_PAYMENT_GATEWAY_ERROR:
        return { ...state,
          errors: { ...state.errors,
            updatePaymentGateway: payload.error
          },
          isUpdating: false
        };
    }
  }

  return state;
};

/* harmony default export */ var payment_gateways_reducer = (payment_gateways_reducer_reducer);
// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/payment-gateways/index.js
/**
 * External dependencies
 */


/**
 * Internal dependencies
 */






const PAYMENT_GATEWAYS_STORE_NAME = STORE_KEY;
Object(external_wp_data_["registerStore"])(STORE_KEY, {
  actions: payment_gateways_actions_namespaceObject,
  selectors: payment_gateways_selectors_namespaceObject,
  resolvers: payment_gateways_resolvers_namespaceObject,
  controls: external_wp_dataControls_["controls"],
  reducer: payment_gateways_reducer
});
// EXTERNAL MODULE: external "moment"
var external_moment_ = __webpack_require__(11);
var external_moment_default = /*#__PURE__*/__webpack_require__.n(external_moment_);

// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/reports/utils.js
/**
 * External dependencies
 */





/**
 * Internal dependencies
 */





/**
 * Add filters and advanced filters values to a query object.
 *
 * @param  {Object} options                   arguments
 * @param  {string} options.endpoint          Report API Endpoint
 * @param  {Object} options.query             Query parameters in the url
 * @param  {Array}  options.limitBy           Properties used to limit the results. It will be used in the API call to send the IDs.
 * @param  {Array}  [options.filters]         config filters
 * @param  {Object} [options.advancedFilters] config advanced filters
 * @return {Object} A query object with the values from filters and advanced fitlters applied.
 */

function getFilterQuery(options) {
  const {
    endpoint,
    query,
    limitBy,
    filters = [],
    advancedFilters = {}
  } = options;

  if (query.search) {
    const limitProperties = limitBy || [endpoint];
    return limitProperties.reduce((result, limitProperty) => {
      result[limitProperty] = query[limitProperty];
      return result;
    }, {});
  }

  return filters.map(filter => getQueryFromConfig(filter, advancedFilters, query)).reduce((result, configQuery) => Object.assign(result, configQuery), {});
} // Some stats endpoints don't have interval data, so they can ignore after/before params and omit that part of the response.

const noIntervalEndpoints = ['stock', 'customers'];
/**
 * Add timestamp to advanced filter parameters involving date. The api
 * expects a timestamp for these values similar to `before` and `after`.
 *
 * @param {Object} config - advancedFilters config object.
 * @param {Object} activeFilter - an active filter.
 * @return {Object} - an active filter with timestamp added to date values.
 */

function timeStampFilterDates(config, activeFilter) {
  const advancedFilterConfig = config.filters[activeFilter.key];

  if (Object(external_lodash_["get"])(advancedFilterConfig, ['input', 'component']) !== 'Date') {
    return activeFilter;
  }

  const {
    rule,
    value
  } = activeFilter;
  const timeOfDayMap = {
    after: 'start',
    before: 'end'
  }; // If the value is an array, it signifies "between" values which must have a timestamp
  // appended to each value.

  if (Array.isArray(value)) {
    const [after, before] = value;
    return Object.assign({}, activeFilter, {
      value: [Object(external_wc_date_["appendTimestamp"])(external_moment_default()(after), timeOfDayMap.after), Object(external_wc_date_["appendTimestamp"])(external_moment_default()(before), timeOfDayMap.before)]
    });
  }

  return Object.assign({}, activeFilter, {
    value: Object(external_wc_date_["appendTimestamp"])(external_moment_default()(value), timeOfDayMap[rule])
  });
}
function getQueryFromConfig(config, advancedFilters, query) {
  const queryValue = query[config.param];

  if (!queryValue) {
    return {};
  }

  if (queryValue === 'advanced') {
    const activeFilters = Object(external_wc_navigation_["getActiveFiltersFromQuery"])(query, advancedFilters.filters);

    if (activeFilters.length === 0) {
      return {};
    }

    const filterQuery = Object(external_wc_navigation_["getQueryFromActiveFilters"])(activeFilters.map(filter => timeStampFilterDates(advancedFilters, filter)), {}, advancedFilters.filters);
    return {
      match: query.match || 'all',
      ...filterQuery
    };
  }

  const filter = Object(external_lodash_["find"])(Object(external_wc_navigation_["flattenFilters"])(config.filters), {
    value: queryValue
  });

  if (!filter) {
    return {};
  }

  if (filter.settings && filter.settings.param) {
    const {
      param
    } = filter.settings;

    if (query[param]) {
      return {
        [param]: query[param]
      };
    }

    return {};
  }

  return {
    [config.param]: queryValue
  };
}
/**
 * Returns true if a report object is empty.
 *
 * @param  {Object}  report   Report to check
 * @param  {string}  endpoint Endpoint slug
 * @return {boolean}        True if report is data is empty.
 */

function isReportDataEmpty(report, endpoint) {
  if (!report) {
    return true;
  }

  if (!report.data) {
    return true;
  }

  if (!report.data.totals || Object(external_lodash_["isNull"])(report.data.totals)) {
    return true;
  }

  const checkIntervals = !Object(external_lodash_["includes"])(noIntervalEndpoints, endpoint);

  if (checkIntervals && (!report.data.intervals || report.data.intervals.length === 0)) {
    return true;
  }

  return false;
}
/**
 * Constructs and returns a query associated with a Report data request.
 *
 * @param  {Object} options           arguments
 * @param  {string} options.endpoint  Report API Endpoint
 * @param  {string} options.dataType  'primary' or 'secondary'.
 * @param  {Object} options.query     Query parameters in the url.
 * @param  {Array}  options.limitBy   Properties used to limit the results. It will be used in the API call to send the IDs.
 * @param  {string}  options.defaultDateRange   User specified default date range.
 * @return {Object} data request query parameters.
 */

function getRequestQuery(options) {
  const {
    endpoint,
    dataType,
    query,
    fields,
    defaultDateRange
  } = options;
  const datesFromQuery = Object(external_wc_date_["getCurrentDates"])(query, defaultDateRange);
  const interval = Object(external_wc_date_["getIntervalForQuery"])(query, defaultDateRange);
  const filterQuery = getFilterQuery(options);
  const end = datesFromQuery[dataType].before;
  const noIntervals = Object(external_lodash_["includes"])(noIntervalEndpoints, endpoint);
  return noIntervals ? { ...filterQuery,
    fields
  } : {
    order: 'asc',
    interval,
    per_page: MAX_PER_PAGE,
    after: Object(external_wc_date_["appendTimestamp"])(datesFromQuery[dataType].after, 'start'),
    before: Object(external_wc_date_["appendTimestamp"])(end, 'end'),
    segmentby: query.segmentby,
    fields,
    ...filterQuery
  };
}
/**
 * Returns summary number totals needed to render a report page.
 *
 * @param  {Object} options           arguments
 * @param  {string} options.endpoint  Report API Endpoint
 * @param  {Object} options.query     Query parameters in the url
 * @param  {Object} options.select    Instance of @wordpress/select
 * @param  {Array}  options.limitBy   Properties used to limit the results. It will be used in the API call to send the IDs.
 * @param  {string}  options.defaultDateRange   User specified default date range.
 * @return {Object} Object containing summary number responses.
 */


function getSummaryNumbers(options) {
  const {
    endpoint,
    select
  } = options;
  const {
    getReportStats,
    getReportStatsError,
    isResolving
  } = select(reports_constants_STORE_NAME);
  const response = {
    isRequesting: false,
    isError: false,
    totals: {
      primary: null,
      secondary: null
    }
  };
  const primaryQuery = getRequestQuery({ ...options,
    dataType: 'primary'
  }); // Disable eslint rule requiring `getReportStats` to be defined below because the next two statements
  // depend on `getReportStats` to have been called.
  // eslint-disable-next-line @wordpress/no-unused-vars-before-return

  const primary = getReportStats(endpoint, primaryQuery);

  if (isResolving('getReportStats', [endpoint, primaryQuery])) {
    return { ...response,
      isRequesting: true
    };
  } else if (getReportStatsError(endpoint, primaryQuery)) {
    return { ...response,
      isError: true
    };
  }

  const primaryTotals = primary && primary.data && primary.data.totals || null;
  const secondaryQuery = getRequestQuery({ ...options,
    dataType: 'secondary'
  }); // Disable eslint rule requiring `getReportStats` to be defined below because the next two statements
  // depend on `getReportStats` to have been called.
  // eslint-disable-next-line @wordpress/no-unused-vars-before-return

  const secondary = getReportStats(endpoint, secondaryQuery);

  if (isResolving('getReportStats', [endpoint, secondaryQuery])) {
    return { ...response,
      isRequesting: true
    };
  } else if (getReportStatsError(endpoint, secondaryQuery)) {
    return { ...response,
      isError: true
    };
  }

  const secondaryTotals = secondary && secondary.data && secondary.data.totals || null;
  return { ...response,
    totals: {
      primary: primaryTotals,
      secondary: secondaryTotals
    }
  };
}
/**
 * Static responses object to avoid returning new references each call.
 */

const reportChartDataResponses = {
  requesting: {
    isEmpty: false,
    isError: false,
    isRequesting: true,
    data: {
      totals: {},
      intervals: []
    }
  },
  error: {
    isEmpty: false,
    isError: true,
    isRequesting: false,
    data: {
      totals: {},
      intervals: []
    }
  },
  empty: {
    isEmpty: true,
    isError: false,
    isRequesting: false,
    data: {
      totals: {},
      intervals: []
    }
  }
};
const utils_EMPTY_ARRAY = [];
/**
 * Cache helper for returning the full chart dataset after multiple
 * requests. Memoized on the request query (string), only called after
 * all the requests have resolved successfully.
 */

const getReportChartDataResponse = Object(external_lodash_["memoize"])((requestString, totals, intervals) => ({
  isEmpty: false,
  isError: false,
  isRequesting: false,
  data: {
    totals,
    intervals
  }
}), (requestString, totals, intervals) => [requestString, totals.length, intervals.length].join(':'));
/**
 * Returns all of the data needed to render a chart with summary numbers on a report page.
 *
 * @param  {Object} options           arguments
 * @param  {string} options.endpoint  Report API Endpoint
 * @param  {string} options.dataType  'primary' or 'secondary'
 * @param  {Object} options.query     Query parameters in the url
 * @param  {Object} options.selector    Instance of @wordpress/select response
 * @param  {Object} options.select    (Depreciated) Instance of @wordpress/select
 * @param  {Array}  options.limitBy   Properties used to limit the results. It will be used in the API call to send the IDs.
 * @param  {string}  options.defaultDateRange   User specified default date range.
 * @return {Object}  Object containing API request information (response, fetching, and error details)
 */

function getReportChartData(options) {
  const {
    endpoint
  } = options;
  let reportSelectors = options.selector;

  if (options.select && !options.selector) {
    external_wp_deprecated_default()('option.select', {
      version: '1.7.0',
      hint: 'You can pass the report selectors through option.selector now.'
    });
    reportSelectors = options.select(reports_constants_STORE_NAME);
  }

  const {
    getReportStats,
    getReportStatsError,
    isResolving
  } = reportSelectors;
  const requestQuery = getRequestQuery(options); // Disable eslint rule requiring `stats` to be defined below because the next two if statements
  // depend on `getReportStats` to have been called.
  // eslint-disable-next-line @wordpress/no-unused-vars-before-return

  const stats = getReportStats(endpoint, requestQuery);

  if (isResolving('getReportStats', [endpoint, requestQuery])) {
    return reportChartDataResponses.requesting;
  }

  if (getReportStatsError(endpoint, requestQuery)) {
    return reportChartDataResponses.error;
  }

  if (isReportDataEmpty(stats, endpoint)) {
    return reportChartDataResponses.empty;
  }

  const totals = stats && stats.data && stats.data.totals || null;
  let intervals = stats && stats.data && stats.data.intervals || utils_EMPTY_ARRAY; // If we have more than 100 results for this time period,
  // we need to make additional requests to complete the response.

  if (stats.totalResults > MAX_PER_PAGE) {
    let isFetching = true;
    let isError = false;
    const pagedData = [];
    const totalPages = Math.ceil(stats.totalResults / MAX_PER_PAGE);
    let pagesFetched = 1;

    for (let i = 2; i <= totalPages; i++) {
      const nextQuery = { ...requestQuery,
        page: i
      };

      const _data = getReportStats(endpoint, nextQuery);

      if (isResolving('getReportStats', [endpoint, nextQuery])) {
        continue;
      }

      if (getReportStatsError(endpoint, nextQuery)) {
        isError = true;
        isFetching = false;
        break;
      }

      pagedData.push(_data);
      pagesFetched++;

      if (pagesFetched === totalPages) {
        isFetching = false;
        break;
      }
    }

    if (isFetching) {
      return reportChartDataResponses.requesting;
    } else if (isError) {
      return reportChartDataResponses.error;
    }

    Object(external_lodash_["forEach"])(pagedData, function (_data) {
      if (_data.data && _data.data.intervals && Array.isArray(_data.data.intervals)) {
        intervals = intervals.concat(_data.data.intervals);
      }
    });
  }

  return getReportChartDataResponse(getResourceName(endpoint, requestQuery), totals, intervals);
}
/**
 * Returns a formatting function or string to be used by d3-format
 *
 * @param  {string} type Type of number, 'currency', 'number', 'percent', 'average'
 * @param  {Function} formatAmount format currency function
 * @return {string|Function}  returns a number format based on the type or an overriding formatting function
 */

function getTooltipValueFormat(type, formatAmount) {
  switch (type) {
    case 'currency':
      return formatAmount;

    case 'percent':
      return '.0%';

    case 'number':
      return ',';

    case 'average':
      return ',.2r';

    default:
      return ',';
  }
}
/**
 * Returns query needed for a request to populate a table.
 *
 * @param  {Object} options              arguments
 * @param  {Object} options.query        Query parameters in the url
 * @param  {Object} options.tableQuery   Query parameters specific for that endpoint
 * @param  {string} options.defaultDateRange   User specified default date range.
 * @return {Object} Object    Table data response
 */

function getReportTableQuery(options) {
  const {
    query,
    tableQuery = {}
  } = options;
  const filterQuery = getFilterQuery(options);
  const datesFromQuery = Object(external_wc_date_["getCurrentDates"])(query, options.defaultDateRange);
  const noIntervals = Object(external_lodash_["includes"])(noIntervalEndpoints, options.endpoint);
  return {
    orderby: query.orderby || 'date',
    order: query.order || 'desc',
    after: noIntervals ? undefined : Object(external_wc_date_["appendTimestamp"])(datesFromQuery.primary.after, 'start'),
    before: noIntervals ? undefined : Object(external_wc_date_["appendTimestamp"])(datesFromQuery.primary.before, 'end'),
    page: query.paged || 1,
    per_page: query.per_page || QUERY_DEFAULTS.pageSize,
    ...filterQuery,
    ...tableQuery
  };
}
/**
 * Returns table data needed to render a report page.
 *
 * @param  {Object} options                arguments
 * @param  {string} options.endpoint       Report API Endpoint
 * @param  {Object} options.query          Query parameters in the url
 * @param  {Object} options.selector       Instance of @wordpress/select response
 * @param  {Object} options.select         (depreciated) Instance of @wordpress/select
 * @param  {Object} options.tableQuery     Query parameters specific for that endpoint
 * @param  {string}  options.defaultDateRange   User specified default date range.
 * @return {Object} Object    Table data response
 */

function getReportTableData(options) {
  const {
    endpoint
  } = options;
  let reportSelectors = options.selector;

  if (options.select && !options.selector) {
    external_wp_deprecated_default()('option.select', {
      version: '1.7.0',
      hint: 'You can pass the report selectors through option.selector now.'
    });
    reportSelectors = options.select(reports_constants_STORE_NAME);
  }

  const {
    getReportItems,
    getReportItemsError,
    hasFinishedResolution
  } = reportSelectors;
  const tableQuery = getReportTableQuery(options);
  const response = {
    query: tableQuery,
    isRequesting: false,
    isError: false,
    items: {
      data: [],
      totalResults: 0
    }
  }; // Disable eslint rule requiring `items` to be defined below because the next two if statements
  // depend on `getReportItems` to have been called.
  // eslint-disable-next-line @wordpress/no-unused-vars-before-return

  const items = getReportItems(endpoint, tableQuery);
  const queryResolved = hasFinishedResolution('getReportItems', [endpoint, tableQuery]);

  if (!queryResolved) {
    return { ...response,
      isRequesting: true
    };
  }

  if (getReportItemsError(endpoint, tableQuery)) {
    return { ...response,
      isError: true
    };
  }

  return { ...response,
    items
  };
}
// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/export/constants.js
/**
 * Internal dependencies
 */
const export_constants_STORE_NAME = 'wc/admin/export';
// EXTERNAL MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/md5@2.3.0/node_modules/md5/md5.js
var md5 = __webpack_require__(295);
var md5_default = /*#__PURE__*/__webpack_require__.n(md5);

// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/export/utils.js
/**
 * External dependencies
 */

/**
 * Internal dependencies
 */


const hashExportArgs = args => {
  return md5_default()(getResourceName('export', args));
};
// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/export/selectors.js
/**
 * Internal dependencies
 */

const isExportRequesting = (state, selector, selectorArgs) => {
  return Boolean(state.requesting[selector] && state.requesting[selector][hashExportArgs(selectorArgs)]);
};
const getExportId = (state, exportType, exportArgs) => {
  return state.exportIds[exportType] && state.exportIds[exportType][hashExportArgs(exportArgs)];
};
const getError = (state, selector, selectorArgs) => {
  return state.errors[selector] && state.errors[selector][hashExportArgs(selectorArgs)];
};
// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/export/action-types.js
const export_action_types_TYPES = {
  START_EXPORT: 'START_EXPORT',
  SET_EXPORT_ID: 'SET_EXPORT_ID',
  SET_ERROR: 'SET_ERROR',
  SET_IS_REQUESTING: 'SET_IS_REQUESTING'
};
/* harmony default export */ var export_action_types = (export_action_types_TYPES);
// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/export/actions.js
/**
 * Internal dependencies
 */



function setExportId(exportType, exportArgs, exportId) {
  return {
    type: export_action_types.SET_EXPORT_ID,
    exportType,
    exportArgs,
    exportId
  };
}
function export_actions_setIsRequesting(selector, selectorArgs, isRequesting) {
  return {
    type: export_action_types.SET_IS_REQUESTING,
    selector,
    selectorArgs,
    isRequesting
  };
}
function export_actions_setError(selector, selectorArgs, error) {
  return {
    type: export_action_types.SET_ERROR,
    selector,
    selectorArgs,
    error
  };
}
function* startExport(type, args) {
  yield export_actions_setIsRequesting('startExport', {
    type,
    args
  }, true);

  try {
    const response = yield fetchWithHeaders({
      path: `${NAMESPACE}/reports/${type}/export`,
      method: 'POST',
      data: {
        report_args: args,
        email: true
      }
    });
    yield export_actions_setIsRequesting('startExport', {
      type,
      args
    }, false);
    const {
      export_id: exportId,
      message
    } = response.data;

    if (exportId) {
      yield setExportId(type, args, exportId);
    } else {
      throw new Error(message);
    }

    return response.data;
  } catch (error) {
    yield export_actions_setError('startExport', {
      type,
      args
    }, error.message);
    yield export_actions_setIsRequesting('startExport', {
      type,
      args
    }, false);
    throw error;
  }
}
// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/export/reducer.js
/**
 * Internal dependencies
 */



const exportReducer = function () {
  let state = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {
    errors: {},
    requesting: {},
    exportMeta: {},
    exportIds: {}
  };
  let {
    error,
    exportArgs,
    exportId,
    exportType,
    isRequesting,
    selector,
    selectorArgs,
    type
  } = arguments.length > 1 ? arguments[1] : undefined;

  switch (type) {
    case export_action_types.SET_IS_REQUESTING:
      return { ...state,
        requesting: { ...state.requesting,
          [selector]: { ...state.requesting[selector],
            [hashExportArgs(selectorArgs)]: isRequesting
          }
        }
      };

    case export_action_types.SET_EXPORT_ID:
      return { ...state,
        exportMeta: { ...state.exportMeta,
          [exportId]: {
            exportType,
            exportArgs
          }
        },
        exportIds: { ...state.exportIds,
          [exportType]: { ...state.exportIds[exportType],
            [hashExportArgs({
              type: exportType,
              args: exportArgs
            })]: exportId
          }
        }
      };

    case export_action_types.SET_ERROR:
      return { ...state,
        errors: { ...state.errors,
          [selector]: { ...state.errors[selector],
            [hashExportArgs(selectorArgs)]: error
          }
        }
      };

    default:
      return state;
  }
};

/* harmony default export */ var export_reducer = (exportReducer);
// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/export/index.js
/**
 * External dependencies
 */

/**
 * Internal dependencies
 */






Object(external_wp_data_["registerStore"])(export_constants_STORE_NAME, {
  reducer: export_reducer,
  actions: export_actions_namespaceObject,
  controls: build_module_controls,
  selectors: export_selectors_namespaceObject
});
const EXPORT_STORE_NAME = export_constants_STORE_NAME;
// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/import/constants.js
/**
 * Internal dependencies
 */
const import_constants_STORE_NAME = 'wc/admin/import';
// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/import/selectors.js
const getImportStarted = state => {
  const {
    activeImport,
    lastImportStartTimestamp
  } = state;
  return {
    activeImport,
    lastImportStartTimestamp
  } || {};
};
const getFormSettings = state => {
  const {
    period,
    skipPrevious
  } = state;
  return {
    period,
    skipPrevious
  } || {};
};
const getImportStatus = (state, query) => {
  const stringifiedQuery = JSON.stringify(query);
  return state.importStatus[stringifiedQuery] || {};
};
const getImportTotals = (state, query) => {
  const {
    importTotals,
    lastImportStartTimestamp
  } = state;
  const stringifiedQuery = JSON.stringify(query);
  return { ...importTotals[stringifiedQuery],
    lastImportStartTimestamp
  } || {};
};
const getImportError = (state, query) => {
  const stringifiedQuery = JSON.stringify(query);
  return state.errors[stringifiedQuery] || false;
};
// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/import/action-types.js
const import_action_types_TYPES = {
  SET_IMPORT_DATE: 'SET_IMPORT_DATE',
  SET_IMPORT_ERROR: 'SET_IMPORT_ERROR',
  SET_IMPORT_PERIOD: 'SET_IMPORT_PERIOD',
  SET_IMPORT_STARTED: 'SET_IMPORT_STARTED',
  SET_IMPORT_STATUS: 'SET_IMPORT_STATUS',
  SET_IMPORT_TOTALS: 'SET_IMPORT_TOTALS',
  SET_SKIP_IMPORTED: 'SET_SKIP_IMPORTED'
};
/* harmony default export */ var import_action_types = (import_action_types_TYPES);
// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/import/actions.js
/**
 * External dependencies
 */

/**
 * Internal dependencies
 */


function setImportStarted(activeImport) {
  return {
    type: import_action_types.SET_IMPORT_STARTED,
    activeImport
  };
}
function setImportPeriod(date, dateModified) {
  if (!dateModified) {
    return {
      type: import_action_types.SET_IMPORT_PERIOD,
      date
    };
  }

  return {
    type: import_action_types.SET_IMPORT_DATE,
    date
  };
}
function setSkipPrevious(skipPrevious) {
  return {
    type: import_action_types.SET_SKIP_IMPORTED,
    skipPrevious
  };
}
function setImportStatus(query, importStatus) {
  return {
    type: import_action_types.SET_IMPORT_STATUS,
    importStatus,
    query
  };
}
function setImportTotals(query, importTotals) {
  return {
    type: import_action_types.SET_IMPORT_TOTALS,
    importTotals,
    query
  };
}
function setImportError(query, error) {
  return {
    type: import_action_types.SET_IMPORT_ERROR,
    error,
    query
  };
}
function* updateImportation(path) {
  let importStarted = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : false;
  yield setImportStarted(importStarted);

  try {
    const response = yield Object(external_wp_dataControls_["apiFetch"])({
      path,
      method: 'POST'
    });
    return response;
  } catch (error) {
    yield setImportError(path, error);
    throw error;
  }
}
// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/import/resolvers.js
/**
 * External dependencies
 */



/**
 * Internal dependencies
 */



function* resolvers_getImportStatus(query) {
  try {
    const url = Object(external_wp_url_["addQueryArgs"])(`${NAMESPACE}/reports/import/status`, Object(external_lodash_["omit"])(query, ['timestamp']));
    const response = yield Object(external_wp_dataControls_["apiFetch"])({
      path: url
    });
    yield setImportStatus(query, response);
  } catch (error) {
    yield setImportError(query, error);
  }
}
function* resolvers_getImportTotals(query) {
  try {
    const url = Object(external_wp_url_["addQueryArgs"])(`${NAMESPACE}/reports/import/totals`, query);
    const response = yield Object(external_wp_dataControls_["apiFetch"])({
      path: url
    });
    yield setImportTotals(query, response);
  } catch (error) {
    yield setImportError(query, error);
  }
}
// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/import/reducer.js
/**
 * External dependencies
 */


/**
 * Internal dependencies
 */



const import_reducer_reducer = function () {
  let state = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {
    activeImport: false,
    importStatus: {},
    importTotals: {},
    errors: {},
    lastImportStartTimestamp: 0,
    period: {
      date: external_moment_default()().format(Object(external_wp_i18n_["__"])('MM/DD/YYYY', 'woocommerce-admin')),
      label: 'all'
    },
    skipPrevious: true
  };
  let {
    type,
    query,
    importStatus,
    importTotals,
    activeImport,
    date,
    error,
    skipPrevious
  } = arguments.length > 1 ? arguments[1] : undefined;

  switch (type) {
    case import_action_types.SET_IMPORT_STARTED:
      state = { ...state,
        activeImport,
        lastImportStartTimestamp: activeImport ? Date.now() : state.lastImportStartTimestamp
      };
      break;

    case import_action_types.SET_IMPORT_PERIOD:
      state = { ...state,
        period: { ...state.period,
          label: date
        },
        activeImport: false
      };
      break;

    case import_action_types.SET_IMPORT_DATE:
      state = { ...state,
        period: {
          date,
          label: 'custom'
        },
        activeImport: false
      };
      break;

    case import_action_types.SET_SKIP_IMPORTED:
      state = { ...state,
        skipPrevious,
        activeImport: false
      };
      break;

    case import_action_types.SET_IMPORT_STATUS:
      state = { ...state,
        importStatus: { ...state.importStatus,
          [JSON.stringify(query)]: importStatus
        },
        errors: { ...state.errors,
          [JSON.stringify(query)]: false
        }
      };
      break;

    case import_action_types.SET_IMPORT_TOTALS:
      state = { ...state,
        importTotals: { ...state.importTotals,
          [JSON.stringify(query)]: importTotals
        }
      };
      break;

    case import_action_types.SET_IMPORT_ERROR:
      state = { ...state,
        errors: { ...state.errors,
          [JSON.stringify(query)]: error
        }
      };
      break;
  }

  return state;
};

/* harmony default export */ var import_reducer = (import_reducer_reducer);
// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/import/index.js
/**
 * External dependencies
 */


/**
 * Internal dependencies
 */






Object(external_wp_data_["registerStore"])(import_constants_STORE_NAME, {
  reducer: import_reducer,
  actions: import_actions_namespaceObject,
  controls: external_wp_dataControls_["controls"],
  selectors: import_selectors_namespaceObject,
  resolvers: import_resolvers_namespaceObject
});
const IMPORT_STORE_NAME = import_constants_STORE_NAME;
// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/onboarding/types.js

// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/countries/types.js

// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/packages/js/data/build-module/index.js
/**
 * External dependencies
 */

































/***/ }),

/***/ 5:
/***/ (function(module, exports) {

(function() { module.exports = window["lodash"]; }());

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

/***/ 53:
/***/ (function(module, exports, __webpack_require__) {

/*! @license DOMPurify 2.3.6 | (c) Cure53 and other contributors | Released under the Apache license 2.0 and Mozilla Public License 2.0 | github.com/cure53/DOMPurify/blob/2.3.6/LICENSE */

(function (global, factory) {
   true ? module.exports = factory() :
  undefined;
}(this, function () { 'use strict';

  function _toConsumableArray(arr) { if (Array.isArray(arr)) { for (var i = 0, arr2 = Array(arr.length); i < arr.length; i++) { arr2[i] = arr[i]; } return arr2; } else { return Array.from(arr); } }

  var hasOwnProperty = Object.hasOwnProperty,
      setPrototypeOf = Object.setPrototypeOf,
      isFrozen = Object.isFrozen,
      getPrototypeOf = Object.getPrototypeOf,
      getOwnPropertyDescriptor = Object.getOwnPropertyDescriptor;
  var freeze = Object.freeze,
      seal = Object.seal,
      create = Object.create; // eslint-disable-line import/no-mutable-exports

  var _ref = typeof Reflect !== 'undefined' && Reflect,
      apply = _ref.apply,
      construct = _ref.construct;

  if (!apply) {
    apply = function apply(fun, thisValue, args) {
      return fun.apply(thisValue, args);
    };
  }

  if (!freeze) {
    freeze = function freeze(x) {
      return x;
    };
  }

  if (!seal) {
    seal = function seal(x) {
      return x;
    };
  }

  if (!construct) {
    construct = function construct(Func, args) {
      return new (Function.prototype.bind.apply(Func, [null].concat(_toConsumableArray(args))))();
    };
  }

  var arrayForEach = unapply(Array.prototype.forEach);
  var arrayPop = unapply(Array.prototype.pop);
  var arrayPush = unapply(Array.prototype.push);

  var stringToLowerCase = unapply(String.prototype.toLowerCase);
  var stringMatch = unapply(String.prototype.match);
  var stringReplace = unapply(String.prototype.replace);
  var stringIndexOf = unapply(String.prototype.indexOf);
  var stringTrim = unapply(String.prototype.trim);

  var regExpTest = unapply(RegExp.prototype.test);

  var typeErrorCreate = unconstruct(TypeError);

  function unapply(func) {
    return function (thisArg) {
      for (var _len = arguments.length, args = Array(_len > 1 ? _len - 1 : 0), _key = 1; _key < _len; _key++) {
        args[_key - 1] = arguments[_key];
      }

      return apply(func, thisArg, args);
    };
  }

  function unconstruct(func) {
    return function () {
      for (var _len2 = arguments.length, args = Array(_len2), _key2 = 0; _key2 < _len2; _key2++) {
        args[_key2] = arguments[_key2];
      }

      return construct(func, args);
    };
  }

  /* Add properties to a lookup table */
  function addToSet(set, array) {
    if (setPrototypeOf) {
      // Make 'in' and truthy checks like Boolean(set.constructor)
      // independent of any properties defined on Object.prototype.
      // Prevent prototype setters from intercepting set as a this value.
      setPrototypeOf(set, null);
    }

    var l = array.length;
    while (l--) {
      var element = array[l];
      if (typeof element === 'string') {
        var lcElement = stringToLowerCase(element);
        if (lcElement !== element) {
          // Config presets (e.g. tags.js, attrs.js) are immutable.
          if (!isFrozen(array)) {
            array[l] = lcElement;
          }

          element = lcElement;
        }
      }

      set[element] = true;
    }

    return set;
  }

  /* Shallow clone an object */
  function clone(object) {
    var newObject = create(null);

    var property = void 0;
    for (property in object) {
      if (apply(hasOwnProperty, object, [property])) {
        newObject[property] = object[property];
      }
    }

    return newObject;
  }

  /* IE10 doesn't support __lookupGetter__ so lets'
   * simulate it. It also automatically checks
   * if the prop is function or getter and behaves
   * accordingly. */
  function lookupGetter(object, prop) {
    while (object !== null) {
      var desc = getOwnPropertyDescriptor(object, prop);
      if (desc) {
        if (desc.get) {
          return unapply(desc.get);
        }

        if (typeof desc.value === 'function') {
          return unapply(desc.value);
        }
      }

      object = getPrototypeOf(object);
    }

    function fallbackValue(element) {
      console.warn('fallback value for', element);
      return null;
    }

    return fallbackValue;
  }

  var html = freeze(['a', 'abbr', 'acronym', 'address', 'area', 'article', 'aside', 'audio', 'b', 'bdi', 'bdo', 'big', 'blink', 'blockquote', 'body', 'br', 'button', 'canvas', 'caption', 'center', 'cite', 'code', 'col', 'colgroup', 'content', 'data', 'datalist', 'dd', 'decorator', 'del', 'details', 'dfn', 'dialog', 'dir', 'div', 'dl', 'dt', 'element', 'em', 'fieldset', 'figcaption', 'figure', 'font', 'footer', 'form', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'head', 'header', 'hgroup', 'hr', 'html', 'i', 'img', 'input', 'ins', 'kbd', 'label', 'legend', 'li', 'main', 'map', 'mark', 'marquee', 'menu', 'menuitem', 'meter', 'nav', 'nobr', 'ol', 'optgroup', 'option', 'output', 'p', 'picture', 'pre', 'progress', 'q', 'rp', 'rt', 'ruby', 's', 'samp', 'section', 'select', 'shadow', 'small', 'source', 'spacer', 'span', 'strike', 'strong', 'style', 'sub', 'summary', 'sup', 'table', 'tbody', 'td', 'template', 'textarea', 'tfoot', 'th', 'thead', 'time', 'tr', 'track', 'tt', 'u', 'ul', 'var', 'video', 'wbr']);

  // SVG
  var svg = freeze(['svg', 'a', 'altglyph', 'altglyphdef', 'altglyphitem', 'animatecolor', 'animatemotion', 'animatetransform', 'circle', 'clippath', 'defs', 'desc', 'ellipse', 'filter', 'font', 'g', 'glyph', 'glyphref', 'hkern', 'image', 'line', 'lineargradient', 'marker', 'mask', 'metadata', 'mpath', 'path', 'pattern', 'polygon', 'polyline', 'radialgradient', 'rect', 'stop', 'style', 'switch', 'symbol', 'text', 'textpath', 'title', 'tref', 'tspan', 'view', 'vkern']);

  var svgFilters = freeze(['feBlend', 'feColorMatrix', 'feComponentTransfer', 'feComposite', 'feConvolveMatrix', 'feDiffuseLighting', 'feDisplacementMap', 'feDistantLight', 'feFlood', 'feFuncA', 'feFuncB', 'feFuncG', 'feFuncR', 'feGaussianBlur', 'feImage', 'feMerge', 'feMergeNode', 'feMorphology', 'feOffset', 'fePointLight', 'feSpecularLighting', 'feSpotLight', 'feTile', 'feTurbulence']);

  // List of SVG elements that are disallowed by default.
  // We still need to know them so that we can do namespace
  // checks properly in case one wants to add them to
  // allow-list.
  var svgDisallowed = freeze(['animate', 'color-profile', 'cursor', 'discard', 'fedropshadow', 'font-face', 'font-face-format', 'font-face-name', 'font-face-src', 'font-face-uri', 'foreignobject', 'hatch', 'hatchpath', 'mesh', 'meshgradient', 'meshpatch', 'meshrow', 'missing-glyph', 'script', 'set', 'solidcolor', 'unknown', 'use']);

  var mathMl = freeze(['math', 'menclose', 'merror', 'mfenced', 'mfrac', 'mglyph', 'mi', 'mlabeledtr', 'mmultiscripts', 'mn', 'mo', 'mover', 'mpadded', 'mphantom', 'mroot', 'mrow', 'ms', 'mspace', 'msqrt', 'mstyle', 'msub', 'msup', 'msubsup', 'mtable', 'mtd', 'mtext', 'mtr', 'munder', 'munderover']);

  // Similarly to SVG, we want to know all MathML elements,
  // even those that we disallow by default.
  var mathMlDisallowed = freeze(['maction', 'maligngroup', 'malignmark', 'mlongdiv', 'mscarries', 'mscarry', 'msgroup', 'mstack', 'msline', 'msrow', 'semantics', 'annotation', 'annotation-xml', 'mprescripts', 'none']);

  var text = freeze(['#text']);

  var html$1 = freeze(['accept', 'action', 'align', 'alt', 'autocapitalize', 'autocomplete', 'autopictureinpicture', 'autoplay', 'background', 'bgcolor', 'border', 'capture', 'cellpadding', 'cellspacing', 'checked', 'cite', 'class', 'clear', 'color', 'cols', 'colspan', 'controls', 'controlslist', 'coords', 'crossorigin', 'datetime', 'decoding', 'default', 'dir', 'disabled', 'disablepictureinpicture', 'disableremoteplayback', 'download', 'draggable', 'enctype', 'enterkeyhint', 'face', 'for', 'headers', 'height', 'hidden', 'high', 'href', 'hreflang', 'id', 'inputmode', 'integrity', 'ismap', 'kind', 'label', 'lang', 'list', 'loading', 'loop', 'low', 'max', 'maxlength', 'media', 'method', 'min', 'minlength', 'multiple', 'muted', 'name', 'nonce', 'noshade', 'novalidate', 'nowrap', 'open', 'optimum', 'pattern', 'placeholder', 'playsinline', 'poster', 'preload', 'pubdate', 'radiogroup', 'readonly', 'rel', 'required', 'rev', 'reversed', 'role', 'rows', 'rowspan', 'spellcheck', 'scope', 'selected', 'shape', 'size', 'sizes', 'span', 'srclang', 'start', 'src', 'srcset', 'step', 'style', 'summary', 'tabindex', 'title', 'translate', 'type', 'usemap', 'valign', 'value', 'width', 'xmlns', 'slot']);

  var svg$1 = freeze(['accent-height', 'accumulate', 'additive', 'alignment-baseline', 'ascent', 'attributename', 'attributetype', 'azimuth', 'basefrequency', 'baseline-shift', 'begin', 'bias', 'by', 'class', 'clip', 'clippathunits', 'clip-path', 'clip-rule', 'color', 'color-interpolation', 'color-interpolation-filters', 'color-profile', 'color-rendering', 'cx', 'cy', 'd', 'dx', 'dy', 'diffuseconstant', 'direction', 'display', 'divisor', 'dur', 'edgemode', 'elevation', 'end', 'fill', 'fill-opacity', 'fill-rule', 'filter', 'filterunits', 'flood-color', 'flood-opacity', 'font-family', 'font-size', 'font-size-adjust', 'font-stretch', 'font-style', 'font-variant', 'font-weight', 'fx', 'fy', 'g1', 'g2', 'glyph-name', 'glyphref', 'gradientunits', 'gradienttransform', 'height', 'href', 'id', 'image-rendering', 'in', 'in2', 'k', 'k1', 'k2', 'k3', 'k4', 'kerning', 'keypoints', 'keysplines', 'keytimes', 'lang', 'lengthadjust', 'letter-spacing', 'kernelmatrix', 'kernelunitlength', 'lighting-color', 'local', 'marker-end', 'marker-mid', 'marker-start', 'markerheight', 'markerunits', 'markerwidth', 'maskcontentunits', 'maskunits', 'max', 'mask', 'media', 'method', 'mode', 'min', 'name', 'numoctaves', 'offset', 'operator', 'opacity', 'order', 'orient', 'orientation', 'origin', 'overflow', 'paint-order', 'path', 'pathlength', 'patterncontentunits', 'patterntransform', 'patternunits', 'points', 'preservealpha', 'preserveaspectratio', 'primitiveunits', 'r', 'rx', 'ry', 'radius', 'refx', 'refy', 'repeatcount', 'repeatdur', 'restart', 'result', 'rotate', 'scale', 'seed', 'shape-rendering', 'specularconstant', 'specularexponent', 'spreadmethod', 'startoffset', 'stddeviation', 'stitchtiles', 'stop-color', 'stop-opacity', 'stroke-dasharray', 'stroke-dashoffset', 'stroke-linecap', 'stroke-linejoin', 'stroke-miterlimit', 'stroke-opacity', 'stroke', 'stroke-width', 'style', 'surfacescale', 'systemlanguage', 'tabindex', 'targetx', 'targety', 'transform', 'transform-origin', 'text-anchor', 'text-decoration', 'text-rendering', 'textlength', 'type', 'u1', 'u2', 'unicode', 'values', 'viewbox', 'visibility', 'version', 'vert-adv-y', 'vert-origin-x', 'vert-origin-y', 'width', 'word-spacing', 'wrap', 'writing-mode', 'xchannelselector', 'ychannelselector', 'x', 'x1', 'x2', 'xmlns', 'y', 'y1', 'y2', 'z', 'zoomandpan']);

  var mathMl$1 = freeze(['accent', 'accentunder', 'align', 'bevelled', 'close', 'columnsalign', 'columnlines', 'columnspan', 'denomalign', 'depth', 'dir', 'display', 'displaystyle', 'encoding', 'fence', 'frame', 'height', 'href', 'id', 'largeop', 'length', 'linethickness', 'lspace', 'lquote', 'mathbackground', 'mathcolor', 'mathsize', 'mathvariant', 'maxsize', 'minsize', 'movablelimits', 'notation', 'numalign', 'open', 'rowalign', 'rowlines', 'rowspacing', 'rowspan', 'rspace', 'rquote', 'scriptlevel', 'scriptminsize', 'scriptsizemultiplier', 'selection', 'separator', 'separators', 'stretchy', 'subscriptshift', 'supscriptshift', 'symmetric', 'voffset', 'width', 'xmlns']);

  var xml = freeze(['xlink:href', 'xml:id', 'xlink:title', 'xml:space', 'xmlns:xlink']);

  // eslint-disable-next-line unicorn/better-regex
  var MUSTACHE_EXPR = seal(/\{\{[\s\S]*|[\s\S]*\}\}/gm); // Specify template detection regex for SAFE_FOR_TEMPLATES mode
  var ERB_EXPR = seal(/<%[\s\S]*|[\s\S]*%>/gm);
  var DATA_ATTR = seal(/^data-[\-\w.\u00B7-\uFFFF]/); // eslint-disable-line no-useless-escape
  var ARIA_ATTR = seal(/^aria-[\-\w]+$/); // eslint-disable-line no-useless-escape
  var IS_ALLOWED_URI = seal(/^(?:(?:(?:f|ht)tps?|mailto|tel|callto|cid|xmpp):|[^a-z]|[a-z+.\-]+(?:[^a-z+.\-:]|$))/i // eslint-disable-line no-useless-escape
  );
  var IS_SCRIPT_OR_DATA = seal(/^(?:\w+script|data):/i);
  var ATTR_WHITESPACE = seal(/[\u0000-\u0020\u00A0\u1680\u180E\u2000-\u2029\u205F\u3000]/g // eslint-disable-line no-control-regex
  );
  var DOCTYPE_NAME = seal(/^html$/i);

  var _typeof = typeof Symbol === "function" && typeof Symbol.iterator === "symbol" ? function (obj) { return typeof obj; } : function (obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; };

  function _toConsumableArray$1(arr) { if (Array.isArray(arr)) { for (var i = 0, arr2 = Array(arr.length); i < arr.length; i++) { arr2[i] = arr[i]; } return arr2; } else { return Array.from(arr); } }

  var getGlobal = function getGlobal() {
    return typeof window === 'undefined' ? null : window;
  };

  /**
   * Creates a no-op policy for internal use only.
   * Don't export this function outside this module!
   * @param {?TrustedTypePolicyFactory} trustedTypes The policy factory.
   * @param {Document} document The document object (to determine policy name suffix)
   * @return {?TrustedTypePolicy} The policy created (or null, if Trusted Types
   * are not supported).
   */
  var _createTrustedTypesPolicy = function _createTrustedTypesPolicy(trustedTypes, document) {
    if ((typeof trustedTypes === 'undefined' ? 'undefined' : _typeof(trustedTypes)) !== 'object' || typeof trustedTypes.createPolicy !== 'function') {
      return null;
    }

    // Allow the callers to control the unique policy name
    // by adding a data-tt-policy-suffix to the script element with the DOMPurify.
    // Policy creation with duplicate names throws in Trusted Types.
    var suffix = null;
    var ATTR_NAME = 'data-tt-policy-suffix';
    if (document.currentScript && document.currentScript.hasAttribute(ATTR_NAME)) {
      suffix = document.currentScript.getAttribute(ATTR_NAME);
    }

    var policyName = 'dompurify' + (suffix ? '#' + suffix : '');

    try {
      return trustedTypes.createPolicy(policyName, {
        createHTML: function createHTML(html$$1) {
          return html$$1;
        }
      });
    } catch (_) {
      // Policy creation failed (most likely another DOMPurify script has
      // already run). Skip creating the policy, as this will only cause errors
      // if TT are enforced.
      console.warn('TrustedTypes policy ' + policyName + ' could not be created.');
      return null;
    }
  };

  function createDOMPurify() {
    var window = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : getGlobal();

    var DOMPurify = function DOMPurify(root) {
      return createDOMPurify(root);
    };

    /**
     * Version label, exposed for easier checks
     * if DOMPurify is up to date or not
     */
    DOMPurify.version = '2.3.6';

    /**
     * Array of elements that DOMPurify removed during sanitation.
     * Empty if nothing was removed.
     */
    DOMPurify.removed = [];

    if (!window || !window.document || window.document.nodeType !== 9) {
      // Not running in a browser, provide a factory function
      // so that you can pass your own Window
      DOMPurify.isSupported = false;

      return DOMPurify;
    }

    var originalDocument = window.document;

    var document = window.document;
    var DocumentFragment = window.DocumentFragment,
        HTMLTemplateElement = window.HTMLTemplateElement,
        Node = window.Node,
        Element = window.Element,
        NodeFilter = window.NodeFilter,
        _window$NamedNodeMap = window.NamedNodeMap,
        NamedNodeMap = _window$NamedNodeMap === undefined ? window.NamedNodeMap || window.MozNamedAttrMap : _window$NamedNodeMap,
        HTMLFormElement = window.HTMLFormElement,
        DOMParser = window.DOMParser,
        trustedTypes = window.trustedTypes;


    var ElementPrototype = Element.prototype;

    var cloneNode = lookupGetter(ElementPrototype, 'cloneNode');
    var getNextSibling = lookupGetter(ElementPrototype, 'nextSibling');
    var getChildNodes = lookupGetter(ElementPrototype, 'childNodes');
    var getParentNode = lookupGetter(ElementPrototype, 'parentNode');

    // As per issue #47, the web-components registry is inherited by a
    // new document created via createHTMLDocument. As per the spec
    // (http://w3c.github.io/webcomponents/spec/custom/#creating-and-passing-registries)
    // a new empty registry is used when creating a template contents owner
    // document, so we use that as our parent document to ensure nothing
    // is inherited.
    if (typeof HTMLTemplateElement === 'function') {
      var template = document.createElement('template');
      if (template.content && template.content.ownerDocument) {
        document = template.content.ownerDocument;
      }
    }

    var trustedTypesPolicy = _createTrustedTypesPolicy(trustedTypes, originalDocument);
    var emptyHTML = trustedTypesPolicy ? trustedTypesPolicy.createHTML('') : '';

    var _document = document,
        implementation = _document.implementation,
        createNodeIterator = _document.createNodeIterator,
        createDocumentFragment = _document.createDocumentFragment,
        getElementsByTagName = _document.getElementsByTagName;
    var importNode = originalDocument.importNode;


    var documentMode = {};
    try {
      documentMode = clone(document).documentMode ? document.documentMode : {};
    } catch (_) {}

    var hooks = {};

    /**
     * Expose whether this browser supports running the full DOMPurify.
     */
    DOMPurify.isSupported = typeof getParentNode === 'function' && implementation && typeof implementation.createHTMLDocument !== 'undefined' && documentMode !== 9;

    var MUSTACHE_EXPR$$1 = MUSTACHE_EXPR,
        ERB_EXPR$$1 = ERB_EXPR,
        DATA_ATTR$$1 = DATA_ATTR,
        ARIA_ATTR$$1 = ARIA_ATTR,
        IS_SCRIPT_OR_DATA$$1 = IS_SCRIPT_OR_DATA,
        ATTR_WHITESPACE$$1 = ATTR_WHITESPACE;
    var IS_ALLOWED_URI$$1 = IS_ALLOWED_URI;

    /**
     * We consider the elements and attributes below to be safe. Ideally
     * don't add any new ones but feel free to remove unwanted ones.
     */

    /* allowed element names */

    var ALLOWED_TAGS = null;
    var DEFAULT_ALLOWED_TAGS = addToSet({}, [].concat(_toConsumableArray$1(html), _toConsumableArray$1(svg), _toConsumableArray$1(svgFilters), _toConsumableArray$1(mathMl), _toConsumableArray$1(text)));

    /* Allowed attribute names */
    var ALLOWED_ATTR = null;
    var DEFAULT_ALLOWED_ATTR = addToSet({}, [].concat(_toConsumableArray$1(html$1), _toConsumableArray$1(svg$1), _toConsumableArray$1(mathMl$1), _toConsumableArray$1(xml)));

    /*
     * Configure how DOMPUrify should handle custom elements and their attributes as well as customized built-in elements.
     * @property {RegExp|Function|null} tagNameCheck one of [null, regexPattern, predicate]. Default: `null` (disallow any custom elements)
     * @property {RegExp|Function|null} attributeNameCheck one of [null, regexPattern, predicate]. Default: `null` (disallow any attributes not on the allow list)
     * @property {boolean} allowCustomizedBuiltInElements allow custom elements derived from built-ins if they pass CUSTOM_ELEMENT_HANDLING.tagNameCheck. Default: `false`.
     */
    var CUSTOM_ELEMENT_HANDLING = Object.seal(Object.create(null, {
      tagNameCheck: {
        writable: true,
        configurable: false,
        enumerable: true,
        value: null
      },
      attributeNameCheck: {
        writable: true,
        configurable: false,
        enumerable: true,
        value: null
      },
      allowCustomizedBuiltInElements: {
        writable: true,
        configurable: false,
        enumerable: true,
        value: false
      }
    }));

    /* Explicitly forbidden tags (overrides ALLOWED_TAGS/ADD_TAGS) */
    var FORBID_TAGS = null;

    /* Explicitly forbidden attributes (overrides ALLOWED_ATTR/ADD_ATTR) */
    var FORBID_ATTR = null;

    /* Decide if ARIA attributes are okay */
    var ALLOW_ARIA_ATTR = true;

    /* Decide if custom data attributes are okay */
    var ALLOW_DATA_ATTR = true;

    /* Decide if unknown protocols are okay */
    var ALLOW_UNKNOWN_PROTOCOLS = false;

    /* Output should be safe for common template engines.
     * This means, DOMPurify removes data attributes, mustaches and ERB
     */
    var SAFE_FOR_TEMPLATES = false;

    /* Decide if document with <html>... should be returned */
    var WHOLE_DOCUMENT = false;

    /* Track whether config is already set on this instance of DOMPurify. */
    var SET_CONFIG = false;

    /* Decide if all elements (e.g. style, script) must be children of
     * document.body. By default, browsers might move them to document.head */
    var FORCE_BODY = false;

    /* Decide if a DOM `HTMLBodyElement` should be returned, instead of a html
     * string (or a TrustedHTML object if Trusted Types are supported).
     * If `WHOLE_DOCUMENT` is enabled a `HTMLHtmlElement` will be returned instead
     */
    var RETURN_DOM = false;

    /* Decide if a DOM `DocumentFragment` should be returned, instead of a html
     * string  (or a TrustedHTML object if Trusted Types are supported) */
    var RETURN_DOM_FRAGMENT = false;

    /* Try to return a Trusted Type object instead of a string, return a string in
     * case Trusted Types are not supported  */
    var RETURN_TRUSTED_TYPE = false;

    /* Output should be free from DOM clobbering attacks? */
    var SANITIZE_DOM = true;

    /* Keep element content when removing element? */
    var KEEP_CONTENT = true;

    /* If a `Node` is passed to sanitize(), then performs sanitization in-place instead
     * of importing it into a new Document and returning a sanitized copy */
    var IN_PLACE = false;

    /* Allow usage of profiles like html, svg and mathMl */
    var USE_PROFILES = {};

    /* Tags to ignore content of when KEEP_CONTENT is true */
    var FORBID_CONTENTS = null;
    var DEFAULT_FORBID_CONTENTS = addToSet({}, ['annotation-xml', 'audio', 'colgroup', 'desc', 'foreignobject', 'head', 'iframe', 'math', 'mi', 'mn', 'mo', 'ms', 'mtext', 'noembed', 'noframes', 'noscript', 'plaintext', 'script', 'style', 'svg', 'template', 'thead', 'title', 'video', 'xmp']);

    /* Tags that are safe for data: URIs */
    var DATA_URI_TAGS = null;
    var DEFAULT_DATA_URI_TAGS = addToSet({}, ['audio', 'video', 'img', 'source', 'image', 'track']);

    /* Attributes safe for values like "javascript:" */
    var URI_SAFE_ATTRIBUTES = null;
    var DEFAULT_URI_SAFE_ATTRIBUTES = addToSet({}, ['alt', 'class', 'for', 'id', 'label', 'name', 'pattern', 'placeholder', 'role', 'summary', 'title', 'value', 'style', 'xmlns']);

    var MATHML_NAMESPACE = 'http://www.w3.org/1998/Math/MathML';
    var SVG_NAMESPACE = 'http://www.w3.org/2000/svg';
    var HTML_NAMESPACE = 'http://www.w3.org/1999/xhtml';
    /* Document namespace */
    var NAMESPACE = HTML_NAMESPACE;
    var IS_EMPTY_INPUT = false;

    /* Parsing of strict XHTML documents */
    var PARSER_MEDIA_TYPE = void 0;
    var SUPPORTED_PARSER_MEDIA_TYPES = ['application/xhtml+xml', 'text/html'];
    var DEFAULT_PARSER_MEDIA_TYPE = 'text/html';
    var transformCaseFunc = void 0;

    /* Keep a reference to config to pass to hooks */
    var CONFIG = null;

    /* Ideally, do not touch anything below this line */
    /* ______________________________________________ */

    var formElement = document.createElement('form');

    var isRegexOrFunction = function isRegexOrFunction(testValue) {
      return testValue instanceof RegExp || testValue instanceof Function;
    };

    /**
     * _parseConfig
     *
     * @param  {Object} cfg optional config literal
     */
    // eslint-disable-next-line complexity
    var _parseConfig = function _parseConfig(cfg) {
      if (CONFIG && CONFIG === cfg) {
        return;
      }

      /* Shield configuration object from tampering */
      if (!cfg || (typeof cfg === 'undefined' ? 'undefined' : _typeof(cfg)) !== 'object') {
        cfg = {};
      }

      /* Shield configuration object from prototype pollution */
      cfg = clone(cfg);

      /* Set configuration parameters */
      ALLOWED_TAGS = 'ALLOWED_TAGS' in cfg ? addToSet({}, cfg.ALLOWED_TAGS) : DEFAULT_ALLOWED_TAGS;
      ALLOWED_ATTR = 'ALLOWED_ATTR' in cfg ? addToSet({}, cfg.ALLOWED_ATTR) : DEFAULT_ALLOWED_ATTR;
      URI_SAFE_ATTRIBUTES = 'ADD_URI_SAFE_ATTR' in cfg ? addToSet(clone(DEFAULT_URI_SAFE_ATTRIBUTES), cfg.ADD_URI_SAFE_ATTR) : DEFAULT_URI_SAFE_ATTRIBUTES;
      DATA_URI_TAGS = 'ADD_DATA_URI_TAGS' in cfg ? addToSet(clone(DEFAULT_DATA_URI_TAGS), cfg.ADD_DATA_URI_TAGS) : DEFAULT_DATA_URI_TAGS;
      FORBID_CONTENTS = 'FORBID_CONTENTS' in cfg ? addToSet({}, cfg.FORBID_CONTENTS) : DEFAULT_FORBID_CONTENTS;
      FORBID_TAGS = 'FORBID_TAGS' in cfg ? addToSet({}, cfg.FORBID_TAGS) : {};
      FORBID_ATTR = 'FORBID_ATTR' in cfg ? addToSet({}, cfg.FORBID_ATTR) : {};
      USE_PROFILES = 'USE_PROFILES' in cfg ? cfg.USE_PROFILES : false;
      ALLOW_ARIA_ATTR = cfg.ALLOW_ARIA_ATTR !== false; // Default true
      ALLOW_DATA_ATTR = cfg.ALLOW_DATA_ATTR !== false; // Default true
      ALLOW_UNKNOWN_PROTOCOLS = cfg.ALLOW_UNKNOWN_PROTOCOLS || false; // Default false
      SAFE_FOR_TEMPLATES = cfg.SAFE_FOR_TEMPLATES || false; // Default false
      WHOLE_DOCUMENT = cfg.WHOLE_DOCUMENT || false; // Default false
      RETURN_DOM = cfg.RETURN_DOM || false; // Default false
      RETURN_DOM_FRAGMENT = cfg.RETURN_DOM_FRAGMENT || false; // Default false
      RETURN_TRUSTED_TYPE = cfg.RETURN_TRUSTED_TYPE || false; // Default false
      FORCE_BODY = cfg.FORCE_BODY || false; // Default false
      SANITIZE_DOM = cfg.SANITIZE_DOM !== false; // Default true
      KEEP_CONTENT = cfg.KEEP_CONTENT !== false; // Default true
      IN_PLACE = cfg.IN_PLACE || false; // Default false
      IS_ALLOWED_URI$$1 = cfg.ALLOWED_URI_REGEXP || IS_ALLOWED_URI$$1;
      NAMESPACE = cfg.NAMESPACE || HTML_NAMESPACE;
      if (cfg.CUSTOM_ELEMENT_HANDLING && isRegexOrFunction(cfg.CUSTOM_ELEMENT_HANDLING.tagNameCheck)) {
        CUSTOM_ELEMENT_HANDLING.tagNameCheck = cfg.CUSTOM_ELEMENT_HANDLING.tagNameCheck;
      }

      if (cfg.CUSTOM_ELEMENT_HANDLING && isRegexOrFunction(cfg.CUSTOM_ELEMENT_HANDLING.attributeNameCheck)) {
        CUSTOM_ELEMENT_HANDLING.attributeNameCheck = cfg.CUSTOM_ELEMENT_HANDLING.attributeNameCheck;
      }

      if (cfg.CUSTOM_ELEMENT_HANDLING && typeof cfg.CUSTOM_ELEMENT_HANDLING.allowCustomizedBuiltInElements === 'boolean') {
        CUSTOM_ELEMENT_HANDLING.allowCustomizedBuiltInElements = cfg.CUSTOM_ELEMENT_HANDLING.allowCustomizedBuiltInElements;
      }

      PARSER_MEDIA_TYPE =
      // eslint-disable-next-line unicorn/prefer-includes
      SUPPORTED_PARSER_MEDIA_TYPES.indexOf(cfg.PARSER_MEDIA_TYPE) === -1 ? PARSER_MEDIA_TYPE = DEFAULT_PARSER_MEDIA_TYPE : PARSER_MEDIA_TYPE = cfg.PARSER_MEDIA_TYPE;

      // HTML tags and attributes are not case-sensitive, converting to lowercase. Keeping XHTML as is.
      transformCaseFunc = PARSER_MEDIA_TYPE === 'application/xhtml+xml' ? function (x) {
        return x;
      } : stringToLowerCase;

      if (SAFE_FOR_TEMPLATES) {
        ALLOW_DATA_ATTR = false;
      }

      if (RETURN_DOM_FRAGMENT) {
        RETURN_DOM = true;
      }

      /* Parse profile info */
      if (USE_PROFILES) {
        ALLOWED_TAGS = addToSet({}, [].concat(_toConsumableArray$1(text)));
        ALLOWED_ATTR = [];
        if (USE_PROFILES.html === true) {
          addToSet(ALLOWED_TAGS, html);
          addToSet(ALLOWED_ATTR, html$1);
        }

        if (USE_PROFILES.svg === true) {
          addToSet(ALLOWED_TAGS, svg);
          addToSet(ALLOWED_ATTR, svg$1);
          addToSet(ALLOWED_ATTR, xml);
        }

        if (USE_PROFILES.svgFilters === true) {
          addToSet(ALLOWED_TAGS, svgFilters);
          addToSet(ALLOWED_ATTR, svg$1);
          addToSet(ALLOWED_ATTR, xml);
        }

        if (USE_PROFILES.mathMl === true) {
          addToSet(ALLOWED_TAGS, mathMl);
          addToSet(ALLOWED_ATTR, mathMl$1);
          addToSet(ALLOWED_ATTR, xml);
        }
      }

      /* Merge configuration parameters */
      if (cfg.ADD_TAGS) {
        if (ALLOWED_TAGS === DEFAULT_ALLOWED_TAGS) {
          ALLOWED_TAGS = clone(ALLOWED_TAGS);
        }

        addToSet(ALLOWED_TAGS, cfg.ADD_TAGS);
      }

      if (cfg.ADD_ATTR) {
        if (ALLOWED_ATTR === DEFAULT_ALLOWED_ATTR) {
          ALLOWED_ATTR = clone(ALLOWED_ATTR);
        }

        addToSet(ALLOWED_ATTR, cfg.ADD_ATTR);
      }

      if (cfg.ADD_URI_SAFE_ATTR) {
        addToSet(URI_SAFE_ATTRIBUTES, cfg.ADD_URI_SAFE_ATTR);
      }

      if (cfg.FORBID_CONTENTS) {
        if (FORBID_CONTENTS === DEFAULT_FORBID_CONTENTS) {
          FORBID_CONTENTS = clone(FORBID_CONTENTS);
        }

        addToSet(FORBID_CONTENTS, cfg.FORBID_CONTENTS);
      }

      /* Add #text in case KEEP_CONTENT is set to true */
      if (KEEP_CONTENT) {
        ALLOWED_TAGS['#text'] = true;
      }

      /* Add html, head and body to ALLOWED_TAGS in case WHOLE_DOCUMENT is true */
      if (WHOLE_DOCUMENT) {
        addToSet(ALLOWED_TAGS, ['html', 'head', 'body']);
      }

      /* Add tbody to ALLOWED_TAGS in case tables are permitted, see #286, #365 */
      if (ALLOWED_TAGS.table) {
        addToSet(ALLOWED_TAGS, ['tbody']);
        delete FORBID_TAGS.tbody;
      }

      // Prevent further manipulation of configuration.
      // Not available in IE8, Safari 5, etc.
      if (freeze) {
        freeze(cfg);
      }

      CONFIG = cfg;
    };

    var MATHML_TEXT_INTEGRATION_POINTS = addToSet({}, ['mi', 'mo', 'mn', 'ms', 'mtext']);

    var HTML_INTEGRATION_POINTS = addToSet({}, ['foreignobject', 'desc', 'title', 'annotation-xml']);

    /* Keep track of all possible SVG and MathML tags
     * so that we can perform the namespace checks
     * correctly. */
    var ALL_SVG_TAGS = addToSet({}, svg);
    addToSet(ALL_SVG_TAGS, svgFilters);
    addToSet(ALL_SVG_TAGS, svgDisallowed);

    var ALL_MATHML_TAGS = addToSet({}, mathMl);
    addToSet(ALL_MATHML_TAGS, mathMlDisallowed);

    /**
     *
     *
     * @param  {Element} element a DOM element whose namespace is being checked
     * @returns {boolean} Return false if the element has a
     *  namespace that a spec-compliant parser would never
     *  return. Return true otherwise.
     */
    var _checkValidNamespace = function _checkValidNamespace(element) {
      var parent = getParentNode(element);

      // In JSDOM, if we're inside shadow DOM, then parentNode
      // can be null. We just simulate parent in this case.
      if (!parent || !parent.tagName) {
        parent = {
          namespaceURI: HTML_NAMESPACE,
          tagName: 'template'
        };
      }

      var tagName = stringToLowerCase(element.tagName);
      var parentTagName = stringToLowerCase(parent.tagName);

      if (element.namespaceURI === SVG_NAMESPACE) {
        // The only way to switch from HTML namespace to SVG
        // is via <svg>. If it happens via any other tag, then
        // it should be killed.
        if (parent.namespaceURI === HTML_NAMESPACE) {
          return tagName === 'svg';
        }

        // The only way to switch from MathML to SVG is via
        // svg if parent is either <annotation-xml> or MathML
        // text integration points.
        if (parent.namespaceURI === MATHML_NAMESPACE) {
          return tagName === 'svg' && (parentTagName === 'annotation-xml' || MATHML_TEXT_INTEGRATION_POINTS[parentTagName]);
        }

        // We only allow elements that are defined in SVG
        // spec. All others are disallowed in SVG namespace.
        return Boolean(ALL_SVG_TAGS[tagName]);
      }

      if (element.namespaceURI === MATHML_NAMESPACE) {
        // The only way to switch from HTML namespace to MathML
        // is via <math>. If it happens via any other tag, then
        // it should be killed.
        if (parent.namespaceURI === HTML_NAMESPACE) {
          return tagName === 'math';
        }

        // The only way to switch from SVG to MathML is via
        // <math> and HTML integration points
        if (parent.namespaceURI === SVG_NAMESPACE) {
          return tagName === 'math' && HTML_INTEGRATION_POINTS[parentTagName];
        }

        // We only allow elements that are defined in MathML
        // spec. All others are disallowed in MathML namespace.
        return Boolean(ALL_MATHML_TAGS[tagName]);
      }

      if (element.namespaceURI === HTML_NAMESPACE) {
        // The only way to switch from SVG to HTML is via
        // HTML integration points, and from MathML to HTML
        // is via MathML text integration points
        if (parent.namespaceURI === SVG_NAMESPACE && !HTML_INTEGRATION_POINTS[parentTagName]) {
          return false;
        }

        if (parent.namespaceURI === MATHML_NAMESPACE && !MATHML_TEXT_INTEGRATION_POINTS[parentTagName]) {
          return false;
        }

        // Certain elements are allowed in both SVG and HTML
        // namespace. We need to specify them explicitly
        // so that they don't get erronously deleted from
        // HTML namespace.
        var commonSvgAndHTMLElements = addToSet({}, ['title', 'style', 'font', 'a', 'script']);

        // We disallow tags that are specific for MathML
        // or SVG and should never appear in HTML namespace
        return !ALL_MATHML_TAGS[tagName] && (commonSvgAndHTMLElements[tagName] || !ALL_SVG_TAGS[tagName]);
      }

      // The code should never reach this place (this means
      // that the element somehow got namespace that is not
      // HTML, SVG or MathML). Return false just in case.
      return false;
    };

    /**
     * _forceRemove
     *
     * @param  {Node} node a DOM node
     */
    var _forceRemove = function _forceRemove(node) {
      arrayPush(DOMPurify.removed, { element: node });
      try {
        // eslint-disable-next-line unicorn/prefer-dom-node-remove
        node.parentNode.removeChild(node);
      } catch (_) {
        try {
          node.outerHTML = emptyHTML;
        } catch (_) {
          node.remove();
        }
      }
    };

    /**
     * _removeAttribute
     *
     * @param  {String} name an Attribute name
     * @param  {Node} node a DOM node
     */
    var _removeAttribute = function _removeAttribute(name, node) {
      try {
        arrayPush(DOMPurify.removed, {
          attribute: node.getAttributeNode(name),
          from: node
        });
      } catch (_) {
        arrayPush(DOMPurify.removed, {
          attribute: null,
          from: node
        });
      }

      node.removeAttribute(name);

      // We void attribute values for unremovable "is"" attributes
      if (name === 'is' && !ALLOWED_ATTR[name]) {
        if (RETURN_DOM || RETURN_DOM_FRAGMENT) {
          try {
            _forceRemove(node);
          } catch (_) {}
        } else {
          try {
            node.setAttribute(name, '');
          } catch (_) {}
        }
      }
    };

    /**
     * _initDocument
     *
     * @param  {String} dirty a string of dirty markup
     * @return {Document} a DOM, filled with the dirty markup
     */
    var _initDocument = function _initDocument(dirty) {
      /* Create a HTML document */
      var doc = void 0;
      var leadingWhitespace = void 0;

      if (FORCE_BODY) {
        dirty = '<remove></remove>' + dirty;
      } else {
        /* If FORCE_BODY isn't used, leading whitespace needs to be preserved manually */
        var matches = stringMatch(dirty, /^[\r\n\t ]+/);
        leadingWhitespace = matches && matches[0];
      }

      if (PARSER_MEDIA_TYPE === 'application/xhtml+xml') {
        // Root of XHTML doc must contain xmlns declaration (see https://www.w3.org/TR/xhtml1/normative.html#strict)
        dirty = '<html xmlns="http://www.w3.org/1999/xhtml"><head></head><body>' + dirty + '</body></html>';
      }

      var dirtyPayload = trustedTypesPolicy ? trustedTypesPolicy.createHTML(dirty) : dirty;
      /*
       * Use the DOMParser API by default, fallback later if needs be
       * DOMParser not work for svg when has multiple root element.
       */
      if (NAMESPACE === HTML_NAMESPACE) {
        try {
          doc = new DOMParser().parseFromString(dirtyPayload, PARSER_MEDIA_TYPE);
        } catch (_) {}
      }

      /* Use createHTMLDocument in case DOMParser is not available */
      if (!doc || !doc.documentElement) {
        doc = implementation.createDocument(NAMESPACE, 'template', null);
        try {
          doc.documentElement.innerHTML = IS_EMPTY_INPUT ? '' : dirtyPayload;
        } catch (_) {
          // Syntax error if dirtyPayload is invalid xml
        }
      }

      var body = doc.body || doc.documentElement;

      if (dirty && leadingWhitespace) {
        body.insertBefore(document.createTextNode(leadingWhitespace), body.childNodes[0] || null);
      }

      /* Work on whole document or just its body */
      if (NAMESPACE === HTML_NAMESPACE) {
        return getElementsByTagName.call(doc, WHOLE_DOCUMENT ? 'html' : 'body')[0];
      }

      return WHOLE_DOCUMENT ? doc.documentElement : body;
    };

    /**
     * _createIterator
     *
     * @param  {Document} root document/fragment to create iterator for
     * @return {Iterator} iterator instance
     */
    var _createIterator = function _createIterator(root) {
      return createNodeIterator.call(root.ownerDocument || root, root,
      // eslint-disable-next-line no-bitwise
      NodeFilter.SHOW_ELEMENT | NodeFilter.SHOW_COMMENT | NodeFilter.SHOW_TEXT, null, false);
    };

    /**
     * _isClobbered
     *
     * @param  {Node} elm element to check for clobbering attacks
     * @return {Boolean} true if clobbered, false if safe
     */
    var _isClobbered = function _isClobbered(elm) {
      return elm instanceof HTMLFormElement && (typeof elm.nodeName !== 'string' || typeof elm.textContent !== 'string' || typeof elm.removeChild !== 'function' || !(elm.attributes instanceof NamedNodeMap) || typeof elm.removeAttribute !== 'function' || typeof elm.setAttribute !== 'function' || typeof elm.namespaceURI !== 'string' || typeof elm.insertBefore !== 'function');
    };

    /**
     * _isNode
     *
     * @param  {Node} obj object to check whether it's a DOM node
     * @return {Boolean} true is object is a DOM node
     */
    var _isNode = function _isNode(object) {
      return (typeof Node === 'undefined' ? 'undefined' : _typeof(Node)) === 'object' ? object instanceof Node : object && (typeof object === 'undefined' ? 'undefined' : _typeof(object)) === 'object' && typeof object.nodeType === 'number' && typeof object.nodeName === 'string';
    };

    /**
     * _executeHook
     * Execute user configurable hooks
     *
     * @param  {String} entryPoint  Name of the hook's entry point
     * @param  {Node} currentNode node to work on with the hook
     * @param  {Object} data additional hook parameters
     */
    var _executeHook = function _executeHook(entryPoint, currentNode, data) {
      if (!hooks[entryPoint]) {
        return;
      }

      arrayForEach(hooks[entryPoint], function (hook) {
        hook.call(DOMPurify, currentNode, data, CONFIG);
      });
    };

    /**
     * _sanitizeElements
     *
     * @protect nodeName
     * @protect textContent
     * @protect removeChild
     *
     * @param   {Node} currentNode to check for permission to exist
     * @return  {Boolean} true if node was killed, false if left alive
     */
    var _sanitizeElements = function _sanitizeElements(currentNode) {
      var content = void 0;

      /* Execute a hook if present */
      _executeHook('beforeSanitizeElements', currentNode, null);

      /* Check if element is clobbered or can clobber */
      if (_isClobbered(currentNode)) {
        _forceRemove(currentNode);
        return true;
      }

      /* Check if tagname contains Unicode */
      if (stringMatch(currentNode.nodeName, /[\u0080-\uFFFF]/)) {
        _forceRemove(currentNode);
        return true;
      }

      /* Now let's check the element's type and name */
      var tagName = transformCaseFunc(currentNode.nodeName);

      /* Execute a hook if present */
      _executeHook('uponSanitizeElement', currentNode, {
        tagName: tagName,
        allowedTags: ALLOWED_TAGS
      });

      /* Detect mXSS attempts abusing namespace confusion */
      if (!_isNode(currentNode.firstElementChild) && (!_isNode(currentNode.content) || !_isNode(currentNode.content.firstElementChild)) && regExpTest(/<[/\w]/g, currentNode.innerHTML) && regExpTest(/<[/\w]/g, currentNode.textContent)) {
        _forceRemove(currentNode);
        return true;
      }

      /* Mitigate a problem with templates inside select */
      if (tagName === 'select' && regExpTest(/<template/i, currentNode.innerHTML)) {
        _forceRemove(currentNode);
        return true;
      }

      /* Remove element if anything forbids its presence */
      if (!ALLOWED_TAGS[tagName] || FORBID_TAGS[tagName]) {
        /* Check if we have a custom element to handle */
        if (!FORBID_TAGS[tagName] && _basicCustomElementTest(tagName)) {
          if (CUSTOM_ELEMENT_HANDLING.tagNameCheck instanceof RegExp && regExpTest(CUSTOM_ELEMENT_HANDLING.tagNameCheck, tagName)) return false;
          if (CUSTOM_ELEMENT_HANDLING.tagNameCheck instanceof Function && CUSTOM_ELEMENT_HANDLING.tagNameCheck(tagName)) return false;
        }

        /* Keep content except for bad-listed elements */
        if (KEEP_CONTENT && !FORBID_CONTENTS[tagName]) {
          var parentNode = getParentNode(currentNode) || currentNode.parentNode;
          var childNodes = getChildNodes(currentNode) || currentNode.childNodes;

          if (childNodes && parentNode) {
            var childCount = childNodes.length;

            for (var i = childCount - 1; i >= 0; --i) {
              parentNode.insertBefore(cloneNode(childNodes[i], true), getNextSibling(currentNode));
            }
          }
        }

        _forceRemove(currentNode);
        return true;
      }

      /* Check whether element has a valid namespace */
      if (currentNode instanceof Element && !_checkValidNamespace(currentNode)) {
        _forceRemove(currentNode);
        return true;
      }

      if ((tagName === 'noscript' || tagName === 'noembed') && regExpTest(/<\/no(script|embed)/i, currentNode.innerHTML)) {
        _forceRemove(currentNode);
        return true;
      }

      /* Sanitize element content to be template-safe */
      if (SAFE_FOR_TEMPLATES && currentNode.nodeType === 3) {
        /* Get the element's text content */
        content = currentNode.textContent;
        content = stringReplace(content, MUSTACHE_EXPR$$1, ' ');
        content = stringReplace(content, ERB_EXPR$$1, ' ');
        if (currentNode.textContent !== content) {
          arrayPush(DOMPurify.removed, { element: currentNode.cloneNode() });
          currentNode.textContent = content;
        }
      }

      /* Execute a hook if present */
      _executeHook('afterSanitizeElements', currentNode, null);

      return false;
    };

    /**
     * _isValidAttribute
     *
     * @param  {string} lcTag Lowercase tag name of containing element.
     * @param  {string} lcName Lowercase attribute name.
     * @param  {string} value Attribute value.
     * @return {Boolean} Returns true if `value` is valid, otherwise false.
     */
    // eslint-disable-next-line complexity
    var _isValidAttribute = function _isValidAttribute(lcTag, lcName, value) {
      /* Make sure attribute cannot clobber */
      if (SANITIZE_DOM && (lcName === 'id' || lcName === 'name') && (value in document || value in formElement)) {
        return false;
      }

      /* Allow valid data-* attributes: At least one character after "-"
          (https://html.spec.whatwg.org/multipage/dom.html#embedding-custom-non-visible-data-with-the-data-*-attributes)
          XML-compatible (https://html.spec.whatwg.org/multipage/infrastructure.html#xml-compatible and http://www.w3.org/TR/xml/#d0e804)
          We don't need to check the value; it's always URI safe. */
      if (ALLOW_DATA_ATTR && !FORBID_ATTR[lcName] && regExpTest(DATA_ATTR$$1, lcName)) ; else if (ALLOW_ARIA_ATTR && regExpTest(ARIA_ATTR$$1, lcName)) ; else if (!ALLOWED_ATTR[lcName] || FORBID_ATTR[lcName]) {
        if (
        // First condition does a very basic check if a) it's basically a valid custom element tagname AND
        // b) if the tagName passes whatever the user has configured for CUSTOM_ELEMENT_HANDLING.tagNameCheck
        // and c) if the attribute name passes whatever the user has configured for CUSTOM_ELEMENT_HANDLING.attributeNameCheck
        _basicCustomElementTest(lcTag) && (CUSTOM_ELEMENT_HANDLING.tagNameCheck instanceof RegExp && regExpTest(CUSTOM_ELEMENT_HANDLING.tagNameCheck, lcTag) || CUSTOM_ELEMENT_HANDLING.tagNameCheck instanceof Function && CUSTOM_ELEMENT_HANDLING.tagNameCheck(lcTag)) && (CUSTOM_ELEMENT_HANDLING.attributeNameCheck instanceof RegExp && regExpTest(CUSTOM_ELEMENT_HANDLING.attributeNameCheck, lcName) || CUSTOM_ELEMENT_HANDLING.attributeNameCheck instanceof Function && CUSTOM_ELEMENT_HANDLING.attributeNameCheck(lcName)) ||
        // Alternative, second condition checks if it's an `is`-attribute, AND
        // the value passes whatever the user has configured for CUSTOM_ELEMENT_HANDLING.tagNameCheck
        lcName === 'is' && CUSTOM_ELEMENT_HANDLING.allowCustomizedBuiltInElements && (CUSTOM_ELEMENT_HANDLING.tagNameCheck instanceof RegExp && regExpTest(CUSTOM_ELEMENT_HANDLING.tagNameCheck, value) || CUSTOM_ELEMENT_HANDLING.tagNameCheck instanceof Function && CUSTOM_ELEMENT_HANDLING.tagNameCheck(value))) ; else {
          return false;
        }
        /* Check value is safe. First, is attr inert? If so, is safe */
      } else if (URI_SAFE_ATTRIBUTES[lcName]) ; else if (regExpTest(IS_ALLOWED_URI$$1, stringReplace(value, ATTR_WHITESPACE$$1, ''))) ; else if ((lcName === 'src' || lcName === 'xlink:href' || lcName === 'href') && lcTag !== 'script' && stringIndexOf(value, 'data:') === 0 && DATA_URI_TAGS[lcTag]) ; else if (ALLOW_UNKNOWN_PROTOCOLS && !regExpTest(IS_SCRIPT_OR_DATA$$1, stringReplace(value, ATTR_WHITESPACE$$1, ''))) ; else if (!value) ; else {
        return false;
      }

      return true;
    };

    /**
     * _basicCustomElementCheck
     * checks if at least one dash is included in tagName, and it's not the first char
     * for more sophisticated checking see https://github.com/sindresorhus/validate-element-name
     * @param {string} tagName name of the tag of the node to sanitize
     */
    var _basicCustomElementTest = function _basicCustomElementTest(tagName) {
      return tagName.indexOf('-') > 0;
    };

    /**
     * _sanitizeAttributes
     *
     * @protect attributes
     * @protect nodeName
     * @protect removeAttribute
     * @protect setAttribute
     *
     * @param  {Node} currentNode to sanitize
     */
    var _sanitizeAttributes = function _sanitizeAttributes(currentNode) {
      var attr = void 0;
      var value = void 0;
      var lcName = void 0;
      var l = void 0;
      /* Execute a hook if present */
      _executeHook('beforeSanitizeAttributes', currentNode, null);

      var attributes = currentNode.attributes;

      /* Check if we have attributes; if not we might have a text node */

      if (!attributes) {
        return;
      }

      var hookEvent = {
        attrName: '',
        attrValue: '',
        keepAttr: true,
        allowedAttributes: ALLOWED_ATTR
      };
      l = attributes.length;

      /* Go backwards over all attributes; safely remove bad ones */
      while (l--) {
        attr = attributes[l];
        var _attr = attr,
            name = _attr.name,
            namespaceURI = _attr.namespaceURI;

        value = stringTrim(attr.value);
        lcName = transformCaseFunc(name);

        /* Execute a hook if present */
        hookEvent.attrName = lcName;
        hookEvent.attrValue = value;
        hookEvent.keepAttr = true;
        hookEvent.forceKeepAttr = undefined; // Allows developers to see this is a property they can set
        _executeHook('uponSanitizeAttribute', currentNode, hookEvent);
        value = hookEvent.attrValue;
        /* Did the hooks approve of the attribute? */
        if (hookEvent.forceKeepAttr) {
          continue;
        }

        /* Remove attribute */
        _removeAttribute(name, currentNode);

        /* Did the hooks approve of the attribute? */
        if (!hookEvent.keepAttr) {
          continue;
        }

        /* Work around a security issue in jQuery 3.0 */
        if (regExpTest(/\/>/i, value)) {
          _removeAttribute(name, currentNode);
          continue;
        }

        /* Sanitize attribute content to be template-safe */
        if (SAFE_FOR_TEMPLATES) {
          value = stringReplace(value, MUSTACHE_EXPR$$1, ' ');
          value = stringReplace(value, ERB_EXPR$$1, ' ');
        }

        /* Is `value` valid for this attribute? */
        var lcTag = transformCaseFunc(currentNode.nodeName);
        if (!_isValidAttribute(lcTag, lcName, value)) {
          continue;
        }

        /* Handle invalid data-* attribute set by try-catching it */
        try {
          if (namespaceURI) {
            currentNode.setAttributeNS(namespaceURI, name, value);
          } else {
            /* Fallback to setAttribute() for browser-unrecognized namespaces e.g. "x-schema". */
            currentNode.setAttribute(name, value);
          }

          arrayPop(DOMPurify.removed);
        } catch (_) {}
      }

      /* Execute a hook if present */
      _executeHook('afterSanitizeAttributes', currentNode, null);
    };

    /**
     * _sanitizeShadowDOM
     *
     * @param  {DocumentFragment} fragment to iterate over recursively
     */
    var _sanitizeShadowDOM = function _sanitizeShadowDOM(fragment) {
      var shadowNode = void 0;
      var shadowIterator = _createIterator(fragment);

      /* Execute a hook if present */
      _executeHook('beforeSanitizeShadowDOM', fragment, null);

      while (shadowNode = shadowIterator.nextNode()) {
        /* Execute a hook if present */
        _executeHook('uponSanitizeShadowNode', shadowNode, null);

        /* Sanitize tags and elements */
        if (_sanitizeElements(shadowNode)) {
          continue;
        }

        /* Deep shadow DOM detected */
        if (shadowNode.content instanceof DocumentFragment) {
          _sanitizeShadowDOM(shadowNode.content);
        }

        /* Check attributes, sanitize if necessary */
        _sanitizeAttributes(shadowNode);
      }

      /* Execute a hook if present */
      _executeHook('afterSanitizeShadowDOM', fragment, null);
    };

    /**
     * Sanitize
     * Public method providing core sanitation functionality
     *
     * @param {String|Node} dirty string or DOM node
     * @param {Object} configuration object
     */
    // eslint-disable-next-line complexity
    DOMPurify.sanitize = function (dirty, cfg) {
      var body = void 0;
      var importedNode = void 0;
      var currentNode = void 0;
      var oldNode = void 0;
      var returnNode = void 0;
      /* Make sure we have a string to sanitize.
        DO NOT return early, as this will return the wrong type if
        the user has requested a DOM object rather than a string */
      IS_EMPTY_INPUT = !dirty;
      if (IS_EMPTY_INPUT) {
        dirty = '<!-->';
      }

      /* Stringify, in case dirty is an object */
      if (typeof dirty !== 'string' && !_isNode(dirty)) {
        // eslint-disable-next-line no-negated-condition
        if (typeof dirty.toString !== 'function') {
          throw typeErrorCreate('toString is not a function');
        } else {
          dirty = dirty.toString();
          if (typeof dirty !== 'string') {
            throw typeErrorCreate('dirty is not a string, aborting');
          }
        }
      }

      /* Check we can run. Otherwise fall back or ignore */
      if (!DOMPurify.isSupported) {
        if (_typeof(window.toStaticHTML) === 'object' || typeof window.toStaticHTML === 'function') {
          if (typeof dirty === 'string') {
            return window.toStaticHTML(dirty);
          }

          if (_isNode(dirty)) {
            return window.toStaticHTML(dirty.outerHTML);
          }
        }

        return dirty;
      }

      /* Assign config vars */
      if (!SET_CONFIG) {
        _parseConfig(cfg);
      }

      /* Clean up removed elements */
      DOMPurify.removed = [];

      /* Check if dirty is correctly typed for IN_PLACE */
      if (typeof dirty === 'string') {
        IN_PLACE = false;
      }

      if (IN_PLACE) {
        /* Do some early pre-sanitization to avoid unsafe root nodes */
        if (dirty.nodeName) {
          var tagName = transformCaseFunc(dirty.nodeName);
          if (!ALLOWED_TAGS[tagName] || FORBID_TAGS[tagName]) {
            throw typeErrorCreate('root node is forbidden and cannot be sanitized in-place');
          }
        }
      } else if (dirty instanceof Node) {
        /* If dirty is a DOM element, append to an empty document to avoid
           elements being stripped by the parser */
        body = _initDocument('<!---->');
        importedNode = body.ownerDocument.importNode(dirty, true);
        if (importedNode.nodeType === 1 && importedNode.nodeName === 'BODY') {
          /* Node is already a body, use as is */
          body = importedNode;
        } else if (importedNode.nodeName === 'HTML') {
          body = importedNode;
        } else {
          // eslint-disable-next-line unicorn/prefer-dom-node-append
          body.appendChild(importedNode);
        }
      } else {
        /* Exit directly if we have nothing to do */
        if (!RETURN_DOM && !SAFE_FOR_TEMPLATES && !WHOLE_DOCUMENT &&
        // eslint-disable-next-line unicorn/prefer-includes
        dirty.indexOf('<') === -1) {
          return trustedTypesPolicy && RETURN_TRUSTED_TYPE ? trustedTypesPolicy.createHTML(dirty) : dirty;
        }

        /* Initialize the document to work on */
        body = _initDocument(dirty);

        /* Check we have a DOM node from the data */
        if (!body) {
          return RETURN_DOM ? null : RETURN_TRUSTED_TYPE ? emptyHTML : '';
        }
      }

      /* Remove first element node (ours) if FORCE_BODY is set */
      if (body && FORCE_BODY) {
        _forceRemove(body.firstChild);
      }

      /* Get node iterator */
      var nodeIterator = _createIterator(IN_PLACE ? dirty : body);

      /* Now start iterating over the created document */
      while (currentNode = nodeIterator.nextNode()) {
        /* Fix IE's strange behavior with manipulated textNodes #89 */
        if (currentNode.nodeType === 3 && currentNode === oldNode) {
          continue;
        }

        /* Sanitize tags and elements */
        if (_sanitizeElements(currentNode)) {
          continue;
        }

        /* Shadow DOM detected, sanitize it */
        if (currentNode.content instanceof DocumentFragment) {
          _sanitizeShadowDOM(currentNode.content);
        }

        /* Check attributes, sanitize if necessary */
        _sanitizeAttributes(currentNode);

        oldNode = currentNode;
      }

      oldNode = null;

      /* If we sanitized `dirty` in-place, return it. */
      if (IN_PLACE) {
        return dirty;
      }

      /* Return sanitized string or DOM */
      if (RETURN_DOM) {
        if (RETURN_DOM_FRAGMENT) {
          returnNode = createDocumentFragment.call(body.ownerDocument);

          while (body.firstChild) {
            // eslint-disable-next-line unicorn/prefer-dom-node-append
            returnNode.appendChild(body.firstChild);
          }
        } else {
          returnNode = body;
        }

        if (ALLOWED_ATTR.shadowroot) {
          /*
            AdoptNode() is not used because internal state is not reset
            (e.g. the past names map of a HTMLFormElement), this is safe
            in theory but we would rather not risk another attack vector.
            The state that is cloned by importNode() is explicitly defined
            by the specs.
          */
          returnNode = importNode.call(originalDocument, returnNode, true);
        }

        return returnNode;
      }

      var serializedHTML = WHOLE_DOCUMENT ? body.outerHTML : body.innerHTML;

      /* Serialize doctype if allowed */
      if (WHOLE_DOCUMENT && ALLOWED_TAGS['!doctype'] && body.ownerDocument && body.ownerDocument.doctype && body.ownerDocument.doctype.name && regExpTest(DOCTYPE_NAME, body.ownerDocument.doctype.name)) {
        serializedHTML = '<!DOCTYPE ' + body.ownerDocument.doctype.name + '>\n' + serializedHTML;
      }

      /* Sanitize final string template-safe */
      if (SAFE_FOR_TEMPLATES) {
        serializedHTML = stringReplace(serializedHTML, MUSTACHE_EXPR$$1, ' ');
        serializedHTML = stringReplace(serializedHTML, ERB_EXPR$$1, ' ');
      }

      return trustedTypesPolicy && RETURN_TRUSTED_TYPE ? trustedTypesPolicy.createHTML(serializedHTML) : serializedHTML;
    };

    /**
     * Public method to set the configuration once
     * setConfig
     *
     * @param {Object} cfg configuration object
     */
    DOMPurify.setConfig = function (cfg) {
      _parseConfig(cfg);
      SET_CONFIG = true;
    };

    /**
     * Public method to remove the configuration
     * clearConfig
     *
     */
    DOMPurify.clearConfig = function () {
      CONFIG = null;
      SET_CONFIG = false;
    };

    /**
     * Public method to check if an attribute value is valid.
     * Uses last set config, if any. Otherwise, uses config defaults.
     * isValidAttribute
     *
     * @param  {string} tag Tag name of containing element.
     * @param  {string} attr Attribute name.
     * @param  {string} value Attribute value.
     * @return {Boolean} Returns true if `value` is valid. Otherwise, returns false.
     */
    DOMPurify.isValidAttribute = function (tag, attr, value) {
      /* Initialize shared config vars if necessary. */
      if (!CONFIG) {
        _parseConfig({});
      }

      var lcTag = transformCaseFunc(tag);
      var lcName = transformCaseFunc(attr);
      return _isValidAttribute(lcTag, lcName, value);
    };

    /**
     * AddHook
     * Public method to add DOMPurify hooks
     *
     * @param {String} entryPoint entry point for the hook to add
     * @param {Function} hookFunction function to execute
     */
    DOMPurify.addHook = function (entryPoint, hookFunction) {
      if (typeof hookFunction !== 'function') {
        return;
      }

      hooks[entryPoint] = hooks[entryPoint] || [];
      arrayPush(hooks[entryPoint], hookFunction);
    };

    /**
     * RemoveHook
     * Public method to remove a DOMPurify hook at a given entryPoint
     * (pops it from the stack of hooks if more are present)
     *
     * @param {String} entryPoint entry point for the hook to remove
     */
    DOMPurify.removeHook = function (entryPoint) {
      if (hooks[entryPoint]) {
        arrayPop(hooks[entryPoint]);
      }
    };

    /**
     * RemoveHooks
     * Public method to remove all DOMPurify hooks at a given entryPoint
     *
     * @param  {String} entryPoint entry point for the hooks to remove
     */
    DOMPurify.removeHooks = function (entryPoint) {
      if (hooks[entryPoint]) {
        hooks[entryPoint] = [];
      }
    };

    /**
     * RemoveAllHooks
     * Public method to remove all DOMPurify hooks
     *
     */
    DOMPurify.removeAllHooks = function () {
      hooks = {};
    };

    return DOMPurify;
  }

  var purify = createDOMPurify();

  return purify;

}));
//# sourceMappingURL=purify.js.map


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

/***/ 8:
/***/ (function(module, exports) {

(function() { module.exports = window["wp"]["data"]; }());

/***/ })

/******/ });