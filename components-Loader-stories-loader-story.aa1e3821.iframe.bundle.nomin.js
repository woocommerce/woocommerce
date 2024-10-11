(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[9167],{

/***/ "../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/arrayLikeToArray.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (/* binding */ _arrayLikeToArray)
/* harmony export */ });
function _arrayLikeToArray(arr, len) {
  if (len == null || len > arr.length) len = arr.length;
  for (var i = 0, arr2 = new Array(len); i < len; i++) arr2[i] = arr[i];
  return arr2;
}

/***/ }),

/***/ "../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/arrayWithHoles.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (/* binding */ _arrayWithHoles)
/* harmony export */ });
function _arrayWithHoles(arr) {
  if (Array.isArray(arr)) return arr;
}

/***/ }),

/***/ "../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/nonIterableRest.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (/* binding */ _nonIterableRest)
/* harmony export */ });
function _nonIterableRest() {
  throw new TypeError("Invalid attempt to destructure non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method.");
}

/***/ }),

/***/ "../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/slicedToArray.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";

// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  A: () => (/* binding */ _slicedToArray)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/arrayWithHoles.js
var arrayWithHoles = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/arrayWithHoles.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/iterableToArrayLimit.js
function _iterableToArrayLimit(r, l) {
  var t = null == r ? null : "undefined" != typeof Symbol && r[Symbol.iterator] || r["@@iterator"];
  if (null != t) {
    var e,
      n,
      i,
      u,
      a = [],
      f = !0,
      o = !1;
    try {
      if (i = (t = t.call(r)).next, 0 === l) {
        if (Object(t) !== t) return;
        f = !1;
      } else for (; !(f = (e = i.call(t)).done) && (a.push(e.value), a.length !== l); f = !0);
    } catch (r) {
      o = !0, n = r;
    } finally {
      try {
        if (!f && null != t["return"] && (u = t["return"](), Object(u) !== u)) return;
      } finally {
        if (o) throw n;
      }
    }
    return a;
  }
}
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/unsupportedIterableToArray.js
var unsupportedIterableToArray = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/unsupportedIterableToArray.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/nonIterableRest.js
var nonIterableRest = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/nonIterableRest.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/slicedToArray.js




function _slicedToArray(arr, i) {
  return (0,arrayWithHoles/* default */.A)(arr) || _iterableToArrayLimit(arr, i) || (0,unsupportedIterableToArray/* default */.A)(arr, i) || (0,nonIterableRest/* default */.A)();
}

/***/ }),

/***/ "../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/unsupportedIterableToArray.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (/* binding */ _unsupportedIterableToArray)
/* harmony export */ });
/* harmony import */ var _arrayLikeToArray_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/arrayLikeToArray.js");

function _unsupportedIterableToArray(o, minLen) {
  if (!o) return;
  if (typeof o === "string") return (0,_arrayLikeToArray_js__WEBPACK_IMPORTED_MODULE_0__/* ["default"] */ .A)(o, minLen);
  var n = Object.prototype.toString.call(o).slice(8, -1);
  if (n === "Object" && o.constructor) n = o.constructor.name;
  if (n === "Map" || n === "Set") return Array.from(o);
  if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return (0,_arrayLikeToArray_js__WEBPACK_IMPORTED_MODULE_0__/* ["default"] */ .A)(o, minLen);
}

/***/ }),

/***/ "../../node_modules/.pnpm/classnames@2.3.2/node_modules/classnames/index.js":
/***/ ((module, exports) => {

var __WEBPACK_AMD_DEFINE_ARRAY__, __WEBPACK_AMD_DEFINE_RESULT__;/*!
	Copyright (c) 2018 Jed Watson.
	Licensed under the MIT License (MIT), see
	http://jedwatson.github.io/classnames
*/
/* global define */

(function () {
	'use strict';

	var hasOwn = {}.hasOwnProperty;
	var nativeCodeString = '[native code]';

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
				if (arg.toString !== Object.prototype.toString && !arg.toString.toString().includes('[native code]')) {
					classes.push(arg.toString());
					continue;
				}

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
	} else if (true) {
		// register as 'classnames', consistent with npm package name
		!(__WEBPACK_AMD_DEFINE_ARRAY__ = [], __WEBPACK_AMD_DEFINE_RESULT__ = (function () {
			return classNames;
		}).apply(exports, __WEBPACK_AMD_DEFINE_ARRAY__),
		__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));
	} else {}
}());


/***/ }),

/***/ "../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/array-iteration.js":
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

"use strict";

var bind = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/function-bind-context.js");
var uncurryThis = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/function-uncurry-this.js");
var IndexedObject = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/indexed-object.js");
var toObject = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/to-object.js");
var lengthOfArrayLike = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/length-of-array-like.js");
var arraySpeciesCreate = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/array-species-create.js");

var push = uncurryThis([].push);

// `Array.prototype.{ forEach, map, filter, some, every, find, findIndex, filterReject }` methods implementation
var createMethod = function (TYPE) {
  var IS_MAP = TYPE === 1;
  var IS_FILTER = TYPE === 2;
  var IS_SOME = TYPE === 3;
  var IS_EVERY = TYPE === 4;
  var IS_FIND_INDEX = TYPE === 6;
  var IS_FILTER_REJECT = TYPE === 7;
  var NO_HOLES = TYPE === 5 || IS_FIND_INDEX;
  return function ($this, callbackfn, that, specificCreate) {
    var O = toObject($this);
    var self = IndexedObject(O);
    var length = lengthOfArrayLike(self);
    var boundFunction = bind(callbackfn, that);
    var index = 0;
    var create = specificCreate || arraySpeciesCreate;
    var target = IS_MAP ? create($this, length) : IS_FILTER || IS_FILTER_REJECT ? create($this, 0) : undefined;
    var value, result;
    for (;length > index; index++) if (NO_HOLES || index in self) {
      value = self[index];
      result = boundFunction(value, index, O);
      if (TYPE) {
        if (IS_MAP) target[index] = result; // map
        else if (result) switch (TYPE) {
          case 3: return true;              // some
          case 5: return value;             // find
          case 6: return index;             // findIndex
          case 2: push(target, value);      // filter
        } else switch (TYPE) {
          case 4: return false;             // every
          case 7: push(target, value);      // filterReject
        }
      }
    }
    return IS_FIND_INDEX ? -1 : IS_SOME || IS_EVERY ? IS_EVERY : target;
  };
};

module.exports = {
  // `Array.prototype.forEach` method
  // https://tc39.es/ecma262/#sec-array.prototype.foreach
  forEach: createMethod(0),
  // `Array.prototype.map` method
  // https://tc39.es/ecma262/#sec-array.prototype.map
  map: createMethod(1),
  // `Array.prototype.filter` method
  // https://tc39.es/ecma262/#sec-array.prototype.filter
  filter: createMethod(2),
  // `Array.prototype.some` method
  // https://tc39.es/ecma262/#sec-array.prototype.some
  some: createMethod(3),
  // `Array.prototype.every` method
  // https://tc39.es/ecma262/#sec-array.prototype.every
  every: createMethod(4),
  // `Array.prototype.find` method
  // https://tc39.es/ecma262/#sec-array.prototype.find
  find: createMethod(5),
  // `Array.prototype.findIndex` method
  // https://tc39.es/ecma262/#sec-array.prototype.findIndex
  findIndex: createMethod(6),
  // `Array.prototype.filterReject` method
  // https://github.com/tc39/proposal-array-filtering
  filterReject: createMethod(7)
};


/***/ }),

/***/ "../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/array-method-has-species-support.js":
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

"use strict";

var fails = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/fails.js");
var wellKnownSymbol = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/well-known-symbol.js");
var V8_VERSION = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/engine-v8-version.js");

var SPECIES = wellKnownSymbol('species');

module.exports = function (METHOD_NAME) {
  // We can't use this feature detection in V8 since it causes
  // deoptimization and serious performance degradation
  // https://github.com/zloirock/core-js/issues/677
  return V8_VERSION >= 51 || !fails(function () {
    var array = [];
    var constructor = array.constructor = {};
    constructor[SPECIES] = function () {
      return { foo: 1 };
    };
    return array[METHOD_NAME](Boolean).foo !== 1;
  });
};


/***/ }),

/***/ "../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/array-species-constructor.js":
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

"use strict";

var isArray = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/is-array.js");
var isConstructor = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/is-constructor.js");
var isObject = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/is-object.js");
var wellKnownSymbol = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/well-known-symbol.js");

var SPECIES = wellKnownSymbol('species');
var $Array = Array;

// a part of `ArraySpeciesCreate` abstract operation
// https://tc39.es/ecma262/#sec-arrayspeciescreate
module.exports = function (originalArray) {
  var C;
  if (isArray(originalArray)) {
    C = originalArray.constructor;
    // cross-realm fallback
    if (isConstructor(C) && (C === $Array || isArray(C.prototype))) C = undefined;
    else if (isObject(C)) {
      C = C[SPECIES];
      if (C === null) C = undefined;
    }
  } return C === undefined ? $Array : C;
};


/***/ }),

/***/ "../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/array-species-create.js":
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

"use strict";

var arraySpeciesConstructor = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/array-species-constructor.js");

// `ArraySpeciesCreate` abstract operation
// https://tc39.es/ecma262/#sec-arrayspeciescreate
module.exports = function (originalArray, length) {
  return new (arraySpeciesConstructor(originalArray))(length === 0 ? 0 : length);
};


/***/ }),

/***/ "../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/engine-is-bun.js":
/***/ ((module) => {

"use strict";

/* global Bun -- Bun case */
module.exports = typeof Bun == 'function' && Bun && typeof Bun.version == 'string';


/***/ }),

/***/ "../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/function-bind.js":
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

"use strict";

var uncurryThis = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/function-uncurry-this.js");
var aCallable = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/a-callable.js");
var isObject = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/is-object.js");
var hasOwn = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/has-own-property.js");
var arraySlice = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/array-slice.js");
var NATIVE_BIND = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/function-bind-native.js");

var $Function = Function;
var concat = uncurryThis([].concat);
var join = uncurryThis([].join);
var factories = {};

var construct = function (C, argsLength, args) {
  if (!hasOwn(factories, argsLength)) {
    var list = [];
    var i = 0;
    for (; i < argsLength; i++) list[i] = 'a[' + i + ']';
    factories[argsLength] = $Function('C,a', 'return new C(' + join(list, ',') + ')');
  } return factories[argsLength](C, args);
};

// `Function.prototype.bind` method implementation
// https://tc39.es/ecma262/#sec-function.prototype.bind
// eslint-disable-next-line es/no-function-prototype-bind -- detection
module.exports = NATIVE_BIND ? $Function.bind : function bind(that /* , ...args */) {
  var F = aCallable(this);
  var Prototype = F.prototype;
  var partArgs = arraySlice(arguments, 1);
  var boundFunction = function bound(/* args... */) {
    var args = concat(partArgs, arraySlice(arguments));
    return this instanceof boundFunction ? construct(F, args.length, args) : F.apply(that, args);
  };
  if (isObject(Prototype)) boundFunction.prototype = Prototype;
  return boundFunction;
};


/***/ }),

/***/ "../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/is-array.js":
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

"use strict";

var classof = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/classof-raw.js");

// `IsArray` abstract operation
// https://tc39.es/ecma262/#sec-isarray
// eslint-disable-next-line es/no-array-isarray -- safe
module.exports = Array.isArray || function isArray(argument) {
  return classof(argument) === 'Array';
};


/***/ }),

/***/ "../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/schedulers-fix.js":
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

"use strict";

var global = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/global.js");
var apply = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/function-apply.js");
var isCallable = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/is-callable.js");
var ENGINE_IS_BUN = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/engine-is-bun.js");
var USER_AGENT = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/engine-user-agent.js");
var arraySlice = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/array-slice.js");
var validateArgumentsLength = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/validate-arguments-length.js");

var Function = global.Function;
// dirty IE9- and Bun 0.3.0- checks
var WRAP = /MSIE .\./.test(USER_AGENT) || ENGINE_IS_BUN && (function () {
  var version = global.Bun.version.split('.');
  return version.length < 3 || version[0] === '0' && (version[1] < 3 || version[1] === '3' && version[2] === '0');
})();

// IE9- / Bun 0.3.0- setTimeout / setInterval / setImmediate additional parameters fix
// https://html.spec.whatwg.org/multipage/timers-and-user-prompts.html#timers
// https://github.com/oven-sh/bun/issues/1633
module.exports = function (scheduler, hasTimeArg) {
  var firstParamIndex = hasTimeArg ? 2 : 1;
  return WRAP ? function (handler, timeout /* , ...arguments */) {
    var boundArgs = validateArgumentsLength(arguments.length, 1) > firstParamIndex;
    var fn = isCallable(handler) ? handler : Function(handler);
    var params = boundArgs ? arraySlice(arguments, firstParamIndex) : [];
    var callback = boundArgs ? function () {
      apply(fn, this, params);
    } : fn;
    return hasTimeArg ? scheduler(callback, timeout) : scheduler(callback);
  } : scheduler;
};


/***/ }),

/***/ "../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.map.js":
/***/ ((__unused_webpack_module, __unused_webpack_exports, __webpack_require__) => {

"use strict";

var $ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/export.js");
var $map = (__webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/array-iteration.js").map);
var arrayMethodHasSpeciesSupport = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/array-method-has-species-support.js");

var HAS_SPECIES_SUPPORT = arrayMethodHasSpeciesSupport('map');

// `Array.prototype.map` method
// https://tc39.es/ecma262/#sec-array.prototype.map
// with adding support of @@species
$({ target: 'Array', proto: true, forced: !HAS_SPECIES_SUPPORT }, {
  map: function map(callbackfn /* , thisArg */) {
    return $map(this, callbackfn, arguments.length > 1 ? arguments[1] : undefined);
  }
});


/***/ }),

/***/ "../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.function.bind.js":
/***/ ((__unused_webpack_module, __unused_webpack_exports, __webpack_require__) => {

"use strict";

// TODO: Remove from `core-js@4`
var $ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/export.js");
var bind = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/function-bind.js");

// `Function.prototype.bind` method
// https://tc39.es/ecma262/#sec-function.prototype.bind
// eslint-disable-next-line es/no-function-prototype-bind -- detection
$({ target: 'Function', proto: true, forced: Function.bind !== bind }, {
  bind: bind
});


/***/ }),

/***/ "../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/web.set-interval.js":
/***/ ((__unused_webpack_module, __unused_webpack_exports, __webpack_require__) => {

"use strict";

var $ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/export.js");
var global = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/global.js");
var schedulersFix = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/schedulers-fix.js");

var setInterval = schedulersFix(global.setInterval, true);

// Bun / IE9- setInterval additional parameters fix
// https://html.spec.whatwg.org/multipage/timers-and-user-prompts.html#dom-setinterval
$({ global: true, bind: true, forced: global.setInterval !== setInterval }, {
  setInterval: setInterval
});


/***/ }),

/***/ "../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/web.set-timeout.js":
/***/ ((__unused_webpack_module, __unused_webpack_exports, __webpack_require__) => {

"use strict";

var $ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/export.js");
var global = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/global.js");
var schedulersFix = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/schedulers-fix.js");

var setTimeout = schedulersFix(global.setTimeout, true);

// Bun / IE9- setTimeout additional parameters fix
// https://html.spec.whatwg.org/multipage/timers-and-user-prompts.html#dom-settimeout
$({ global: true, bind: true, forced: global.setTimeout !== setTimeout }, {
  setTimeout: setTimeout
});


/***/ }),

/***/ "../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/web.timers.js":
/***/ ((__unused_webpack_module, __unused_webpack_exports, __webpack_require__) => {

"use strict";

// TODO: Remove this module from `core-js@4` since it's split to modules listed below
__webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/web.set-interval.js");
__webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/web.set-timeout.js");


/***/ }),

/***/ "../../packages/js/onboarding/src/components/Loader/stories/loader.story.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
// ESM COMPAT FLAG
__webpack_require__.r(__webpack_exports__);

// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  ExampleLoaderWithControls: () => (/* binding */ ExampleLoaderWithControls),
  ExampleNonLoopingLoader: () => (/* binding */ ExampleNonLoopingLoader),
  ExampleSimpleLoader: () => (/* binding */ ExampleSimpleLoader),
  "default": () => (/* binding */ loader_story)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.map.js
var es_array_map = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.map.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.function.bind.js
var es_function_bind = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.function.bind.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/slicedToArray.js + 1 modules
var slicedToArray = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/slicedToArray.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/web.timers.js
var web_timers = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/web.timers.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.to-string.js
var es_object_to_string = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.to-string.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.promise.js
var es_promise = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.promise.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/classnames@2.3.2/node_modules/classnames/index.js
var classnames = __webpack_require__("../../node_modules/.pnpm/classnames@2.3.2/node_modules/classnames/index.js");
var classnames_default = /*#__PURE__*/__webpack_require__.n(classnames);
;// CONCATENATED MODULE: ../../packages/js/onboarding/src/components/Loader/ProgressBar.tsx

/**
 * External dependencies
 */

var ProgressBar_ProgressBar = function ProgressBar(_ref) {
  var _ref$className = _ref.className,
    className = _ref$className === void 0 ? '' : _ref$className,
    _ref$percent = _ref.percent,
    percent = _ref$percent === void 0 ? 0 : _ref$percent,
    _ref$color = _ref.color,
    color = _ref$color === void 0 ? '#674399' : _ref$color,
    _ref$bgcolor = _ref.bgcolor,
    bgcolor = _ref$bgcolor === void 0 ? 'var(--wp-admin-theme-color)' : _ref$bgcolor;
  var containerStyles = {
    backgroundColor: bgcolor
  };
  var fillerStyles = {
    backgroundColor: color,
    width: "".concat(percent, "%"),
    display: percent === 0 ? 'none' : 'inherit'
  };
  return (0,react.createElement)("div", {
    className: "woocommerce-onboarding-progress-bar ".concat(className)
  }, (0,react.createElement)("div", {
    className: "woocommerce-onboarding-progress-bar__container",
    style: containerStyles
  }, (0,react.createElement)("div", {
    className: "woocommerce-onboarding-progress-bar__filler",
    style: fillerStyles
  })));
};
/* harmony default export */ const Loader_ProgressBar = (ProgressBar_ProgressBar);
try {
    // @ts-ignore
    ProgressBar_ProgressBar.displayName = "ProgressBar";
    // @ts-ignore
    ProgressBar_ProgressBar.__docgenInfo = { "description": "", "displayName": "ProgressBar", "props": { "className": { "defaultValue": { value: "" }, "description": "Component classname", "name": "className", "required": false, "type": { "name": "string" } }, "percent": { "defaultValue": { value: "0" }, "description": "Progress percentage (0 to 100)", "name": "percent", "required": false, "type": { "name": "number" } }, "color": { "defaultValue": { value: "#674399" }, "description": "Color of the progress bar", "name": "color", "required": false, "type": { "name": "string" } }, "bgcolor": { "defaultValue": { value: "var(--wp-admin-theme-color)" }, "description": "Background color of the progress container", "name": "bgcolor", "required": false, "type": { "name": "string" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/onboarding/src/components/Loader/ProgressBar.tsx#ProgressBar"] = { docgenInfo: ProgressBar_ProgressBar.__docgenInfo, name: "ProgressBar", path: "../../packages/js/onboarding/src/components/Loader/ProgressBar.tsx#ProgressBar" };
}
catch (__react_docgen_typescript_loader_error) { }
;// CONCATENATED MODULE: ../../packages/js/onboarding/src/components/Loader/Loader.tsx





/**
 * External dependencies
 */


/**
 * Internal dependencies
 */

var Loader = function Loader(_ref) {
  var children = _ref.children,
    className = _ref.className;
  return (0,react.createElement)("div", {
    className: classnames_default()('woocommerce-onboarding-loader', className)
  }, children);
};
Loader.Layout = function (_ref2) {
  var children = _ref2.children,
    className = _ref2.className;
  return (0,react.createElement)("div", {
    className: classnames_default()('woocommerce-onboarding-loader-wrapper', className)
  }, (0,react.createElement)("div", {
    className: classnames_default()('woocommerce-onboarding-loader-container', className)
  }, children));
};
Loader.Illustration = function (_ref3) {
  var children = _ref3.children;
  return (0,react.createElement)(react.Fragment, null, children);
};
Loader.Title = function (_ref4) {
  var children = _ref4.children,
    className = _ref4.className;
  return (0,react.createElement)("h1", {
    className: classnames_default()('woocommerce-onboarding-loader__title', className)
  }, children);
};
Loader.ProgressBar = function (_ref5) {
  var progress = _ref5.progress,
    className = _ref5.className;
  return (0,react.createElement)(Loader_ProgressBar, {
    className: classnames_default()('progress-bar', className),
    percent: progress !== null && progress !== void 0 ? progress : 0,
    color: 'var(--wp-admin-theme-color)',
    bgcolor: '#E0E0E0'
  });
};
Loader.Subtext = function (_ref6) {
  var children = _ref6.children,
    className = _ref6.className;
  return (0,react.createElement)("p", {
    className: classnames_default()('woocommerce-onboarding-loader__paragraph', className)
  }, children);
};
var LoaderSequence = function LoaderSequence(_ref7) {
  var interval = _ref7.interval,
    _ref7$shouldLoop = _ref7.shouldLoop,
    shouldLoop = _ref7$shouldLoop === void 0 ? true : _ref7$shouldLoop,
    children = _ref7.children,
    _ref7$onChange = _ref7.onChange,
    onChange = _ref7$onChange === void 0 ? function () {} : _ref7$onChange;
  var _useState = (0,react.useState)(0),
    _useState2 = (0,slicedToArray/* default */.A)(_useState, 2),
    index = _useState2[0],
    setIndex = _useState2[1];
  var childCount = react.Children.count(children);
  (0,react.useEffect)(function () {
    var rotateInterval = setInterval(function () {
      setIndex(function (prevIndex) {
        var nextIndex = prevIndex + 1;
        if (shouldLoop) {
          var updatedIndex = nextIndex % childCount;
          onChange(updatedIndex);
          return updatedIndex;
        }
        if (nextIndex < childCount) {
          onChange(nextIndex);
          return nextIndex;
        }
        clearInterval(rotateInterval);
        return prevIndex;
      });
    }, interval);
    return function () {
      return clearInterval(rotateInterval);
    };
  }, [interval, children, shouldLoop, childCount]);
  var childToDisplay = react.Children.toArray(children)[index];
  return (0,react.createElement)(react.Fragment, null, childToDisplay);
};
Loader.Sequence = LoaderSequence; // eslint rule-of-hooks can't handle the compound component definition directly
try {
    // @ts-ignore
    Loader.displayName = "Loader";
    // @ts-ignore
    Loader.__docgenInfo = { "description": "", "displayName": "Loader", "props": { "className": { "defaultValue": null, "description": "", "name": "className", "required": false, "type": { "name": "string" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/onboarding/src/components/Loader/Loader.tsx#Loader"] = { docgenInfo: Loader.__docgenInfo, name: "Loader", path: "../../packages/js/onboarding/src/components/Loader/Loader.tsx#Loader" };
}
catch (__react_docgen_typescript_loader_error) { }
try {
    // @ts-ignore
    Layout.displayName = "Loader.Layout";
    // @ts-ignore
    Layout.__docgenInfo = { "description": "", "displayName": "Loader.Layout", "props": { "className": { "defaultValue": null, "description": "", "name": "className", "required": false, "type": { "name": "string" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/onboarding/src/components/Loader/Loader.tsx#Loader.Layout"] = { docgenInfo: Loader.Layout.__docgenInfo, name: "Loader.Layout", path: "../../packages/js/onboarding/src/components/Loader/Loader.tsx#Loader.Layout" };
}
catch (__react_docgen_typescript_loader_error) { }
try {
    // @ts-ignore
    Illustration.displayName = "Loader.Illustration";
    // @ts-ignore
    Illustration.__docgenInfo = { "description": "", "displayName": "Loader.Illustration", "props": {} };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/onboarding/src/components/Loader/Loader.tsx#Loader.Illustration"] = { docgenInfo: Loader.Illustration.__docgenInfo, name: "Loader.Illustration", path: "../../packages/js/onboarding/src/components/Loader/Loader.tsx#Loader.Illustration" };
}
catch (__react_docgen_typescript_loader_error) { }
try {
    // @ts-ignore
    Title.displayName = "Loader.Title";
    // @ts-ignore
    Title.__docgenInfo = { "description": "", "displayName": "Loader.Title", "props": { "className": { "defaultValue": null, "description": "", "name": "className", "required": false, "type": { "name": "string" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/onboarding/src/components/Loader/Loader.tsx#Loader.Title"] = { docgenInfo: Loader.Title.__docgenInfo, name: "Loader.Title", path: "../../packages/js/onboarding/src/components/Loader/Loader.tsx#Loader.Title" };
}
catch (__react_docgen_typescript_loader_error) { }
try {
    // @ts-ignore
    ProgressBar.displayName = "Loader.ProgressBar";
    // @ts-ignore
    ProgressBar.__docgenInfo = { "description": "", "displayName": "Loader.ProgressBar", "props": { "progress": { "defaultValue": null, "description": "", "name": "progress", "required": true, "type": { "name": "number" } }, "className": { "defaultValue": null, "description": "", "name": "className", "required": false, "type": { "name": "string" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/onboarding/src/components/Loader/Loader.tsx#Loader.ProgressBar"] = { docgenInfo: Loader.ProgressBar.__docgenInfo, name: "Loader.ProgressBar", path: "../../packages/js/onboarding/src/components/Loader/Loader.tsx#Loader.ProgressBar" };
}
catch (__react_docgen_typescript_loader_error) { }
try {
    // @ts-ignore
    Subtext.displayName = "Loader.Subtext";
    // @ts-ignore
    Subtext.__docgenInfo = { "description": "", "displayName": "Loader.Subtext", "props": { "className": { "defaultValue": null, "description": "", "name": "className", "required": false, "type": { "name": "string" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/onboarding/src/components/Loader/Loader.tsx#Loader.Subtext"] = { docgenInfo: Loader.Subtext.__docgenInfo, name: "Loader.Subtext", path: "../../packages/js/onboarding/src/components/Loader/Loader.tsx#Loader.Subtext" };
}
catch (__react_docgen_typescript_loader_error) { }
try {
    // @ts-ignore
    Sequence.displayName = "Loader.Sequence";
    // @ts-ignore
    Sequence.__docgenInfo = { "description": "", "displayName": "Loader.Sequence", "props": { "interval": { "defaultValue": null, "description": "", "name": "interval", "required": true, "type": { "name": "number" } }, "shouldLoop": { "defaultValue": { value: "true" }, "description": "", "name": "shouldLoop", "required": false, "type": { "name": "boolean" } }, "onChange": { "defaultValue": { value: "() => {}" }, "description": "", "name": "onChange", "required": false, "type": { "name": "((index: number) => void)" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/onboarding/src/components/Loader/Loader.tsx#Loader.Sequence"] = { docgenInfo: Loader.Sequence.__docgenInfo, name: "Loader.Sequence", path: "../../packages/js/onboarding/src/components/Loader/Loader.tsx#Loader.Sequence" };
}
catch (__react_docgen_typescript_loader_error) { }
;// CONCATENATED MODULE: ../../packages/js/onboarding/src/components/Loader/index.ts

;// CONCATENATED MODULE: ../../packages/js/onboarding/src/components/Loader/stories/loader.story.tsx



/**
 * External dependencies
 */


/**
 * Internal dependencies
 */


/** Simple straightforward example of how to use the <Loader> compound component */
var ExampleSimpleLoader = function ExampleSimpleLoader() {
  return (0,react.createElement)(Loader, null, (0,react.createElement)(Loader.Layout, null, (0,react.createElement)(Loader.Illustration, null, (0,react.createElement)("img", {
    src: "https://placekitten.com/200/200",
    alt: "a cute kitteh"
  })), (0,react.createElement)(Loader.Title, null, "Very Impressive Title"), (0,react.createElement)(Loader.ProgressBar, {
    progress: 30
  }), (0,react.createElement)(Loader.Sequence, {
    interval: 1000
  }, (0,react.createElement)(Loader.Subtext, null, "Message 1"), (0,react.createElement)(Loader.Subtext, null, "Message 2"), (0,react.createElement)(Loader.Subtext, null, "Message 3"))));
};
var ExampleNonLoopingLoader = function ExampleNonLoopingLoader() {
  return (0,react.createElement)(Loader, null, (0,react.createElement)(Loader.Layout, null, (0,react.createElement)(Loader.Illustration, null, (0,react.createElement)("img", {
    src: "https://placekitten.com/200/200",
    alt: "a cute kitteh"
  })), (0,react.createElement)(Loader.Title, null, "Very Impressive Title"), (0,react.createElement)(Loader.ProgressBar, {
    progress: 30
  }), (0,react.createElement)(Loader.Sequence, {
    interval: 1000,
    shouldLoop: false
  }, (0,react.createElement)(Loader.Subtext, null, "Message 1"), (0,react.createElement)(Loader.Subtext, null, "Message 2"), (0,react.createElement)(Loader.Subtext, null, "Message 3"))));
};

/** <Loader> component story with controls */
var Template = function Template(_ref) {
  var progress = _ref.progress,
    title = _ref.title,
    messages = _ref.messages,
    shouldLoop = _ref.shouldLoop;
  return (0,react.createElement)(Loader, null, (0,react.createElement)(Loader.Layout, null, (0,react.createElement)(Loader.Illustration, null, (0,react.createElement)("img", {
    src: "https://placekitten.com/200/200",
    alt: "a cute kitteh"
  })), (0,react.createElement)(Loader.Title, null, title), (0,react.createElement)(Loader.ProgressBar, {
    progress: progress
  }), (0,react.createElement)(Loader.Sequence, {
    interval: 1000,
    shouldLoop: shouldLoop
  }, messages.map(function (message, index) {
    return (0,react.createElement)(Loader.Subtext, {
      key: index
    }, message);
  }))));
};
var ExampleLoaderWithControls = Template.bind({});
ExampleLoaderWithControls.args = {
  title: 'Very Impressive Title',
  progress: 30,
  shouldLoop: true,
  messages: ['Message 1', 'Message 2', 'Message 3']
};
/* harmony default export */ const loader_story = ({
  title: 'WooCommerce Admin/Onboarding/Loader',
  component: ExampleLoaderWithControls,
  argTypes: {
    title: {
      control: 'text'
    },
    progress: {
      control: {
        type: 'range',
        min: 0,
        max: 100
      }
    },
    shouldLoop: {
      control: 'boolean'
    },
    messages: {
      control: 'object'
    }
  }
});
ExampleSimpleLoader.parameters = {
  ...ExampleSimpleLoader.parameters,
  docs: {
    ...ExampleSimpleLoader.parameters?.docs,
    source: {
      originalSource: "() => <Loader>\n        <Loader.Layout>\n            <Loader.Illustration>\n                <img src=\"https://placekitten.com/200/200\" alt=\"a cute kitteh\" />\n            </Loader.Illustration>\n            <Loader.Title>Very Impressive Title</Loader.Title>\n            <Loader.ProgressBar progress={30} />\n            <Loader.Sequence interval={1000}>\n                <Loader.Subtext>Message 1</Loader.Subtext>\n                <Loader.Subtext>Message 2</Loader.Subtext>\n                <Loader.Subtext>Message 3</Loader.Subtext>\n            </Loader.Sequence>\n        </Loader.Layout>\n    </Loader>",
      ...ExampleSimpleLoader.parameters?.docs?.source
    },
    description: {
      story: "Simple straightforward example of how to use the <Loader> compound component",
      ...ExampleSimpleLoader.parameters?.docs?.description
    }
  }
};
ExampleNonLoopingLoader.parameters = {
  ...ExampleNonLoopingLoader.parameters,
  docs: {
    ...ExampleNonLoopingLoader.parameters?.docs,
    source: {
      originalSource: "() => <Loader>\n        <Loader.Layout>\n            <Loader.Illustration>\n                <img src=\"https://placekitten.com/200/200\" alt=\"a cute kitteh\" />\n            </Loader.Illustration>\n            <Loader.Title>Very Impressive Title</Loader.Title>\n            <Loader.ProgressBar progress={30} />\n            <Loader.Sequence interval={1000} shouldLoop={false}>\n                <Loader.Subtext>Message 1</Loader.Subtext>\n                <Loader.Subtext>Message 2</Loader.Subtext>\n                <Loader.Subtext>Message 3</Loader.Subtext>\n            </Loader.Sequence>\n        </Loader.Layout>\n    </Loader>",
      ...ExampleNonLoopingLoader.parameters?.docs?.source
    }
  }
};
ExampleLoaderWithControls.parameters = {
  ...ExampleLoaderWithControls.parameters,
  docs: {
    ...ExampleLoaderWithControls.parameters?.docs,
    source: {
      originalSource: "({\n  progress,\n  title,\n  messages,\n  shouldLoop\n}) => <Loader>\n        <Loader.Layout>\n            <Loader.Illustration>\n                <img src=\"https://placekitten.com/200/200\" alt=\"a cute kitteh\" />\n            </Loader.Illustration>\n            <Loader.Title>{title}</Loader.Title>\n            <Loader.ProgressBar progress={progress} />\n            <Loader.Sequence interval={1000} shouldLoop={shouldLoop}>\n                {messages.map((message, index) => <Loader.Subtext key={index}>{message}</Loader.Subtext>)}\n            </Loader.Sequence>\n        </Loader.Layout>\n    </Loader>",
      ...ExampleLoaderWithControls.parameters?.docs?.source
    }
  }
};
try {
    // @ts-ignore
    ExampleSimpleLoader.displayName = "ExampleSimpleLoader";
    // @ts-ignore
    ExampleSimpleLoader.__docgenInfo = { "description": "Simple straightforward example of how to use the <Loader> compound component", "displayName": "ExampleSimpleLoader", "props": {} };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/onboarding/src/components/Loader/stories/loader.story.tsx#ExampleSimpleLoader"] = { docgenInfo: ExampleSimpleLoader.__docgenInfo, name: "ExampleSimpleLoader", path: "../../packages/js/onboarding/src/components/Loader/stories/loader.story.tsx#ExampleSimpleLoader" };
}
catch (__react_docgen_typescript_loader_error) { }
try {
    // @ts-ignore
    ExampleLoaderWithControls.displayName = "ExampleLoaderWithControls";
    // @ts-ignore
    ExampleLoaderWithControls.__docgenInfo = { "description": "", "displayName": "ExampleLoaderWithControls", "props": { "progress": { "defaultValue": null, "description": "", "name": "progress", "required": true, "type": { "name": "any" } }, "title": { "defaultValue": null, "description": "", "name": "title", "required": true, "type": { "name": "any" } }, "messages": { "defaultValue": null, "description": "", "name": "messages", "required": true, "type": { "name": "any" } }, "shouldLoop": { "defaultValue": null, "description": "", "name": "shouldLoop", "required": true, "type": { "name": "any" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/onboarding/src/components/Loader/stories/loader.story.tsx#ExampleLoaderWithControls"] = { docgenInfo: ExampleLoaderWithControls.__docgenInfo, name: "ExampleLoaderWithControls", path: "../../packages/js/onboarding/src/components/Loader/stories/loader.story.tsx#ExampleLoaderWithControls" };
}
catch (__react_docgen_typescript_loader_error) { }

/***/ })

}]);