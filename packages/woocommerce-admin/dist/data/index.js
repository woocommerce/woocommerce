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
/******/ 	return __webpack_require__(__webpack_require__.s = "./packages/data/build-module/index.js");
/******/ })
/************************************************************************/
/******/ ({

/***/ "./node_modules/@babel/runtime/helpers/arrayLikeToArray.js":
/*!*****************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/arrayLikeToArray.js ***!
  \*****************************************************************/
/*! no static exports found */
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

/***/ "./node_modules/@babel/runtime/helpers/arrayWithoutHoles.js":
/*!******************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/arrayWithoutHoles.js ***!
  \******************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

var arrayLikeToArray = __webpack_require__(/*! ./arrayLikeToArray */ "./node_modules/@babel/runtime/helpers/arrayLikeToArray.js");

function _arrayWithoutHoles(arr) {
  if (Array.isArray(arr)) return arrayLikeToArray(arr);
}

module.exports = _arrayWithoutHoles;

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/asyncToGenerator.js":
/*!*****************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/asyncToGenerator.js ***!
  \*****************************************************************/
/*! no static exports found */
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

/***/ "./node_modules/@babel/runtime/helpers/iterableToArray.js":
/*!****************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/iterableToArray.js ***!
  \****************************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

function _iterableToArray(iter) {
  if (typeof Symbol !== "undefined" && Symbol.iterator in Object(iter)) return Array.from(iter);
}

module.exports = _iterableToArray;

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/nonIterableSpread.js":
/*!******************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/nonIterableSpread.js ***!
  \******************************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

function _nonIterableSpread() {
  throw new TypeError("Invalid attempt to spread non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method.");
}

module.exports = _nonIterableSpread;

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/toConsumableArray.js":
/*!******************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/toConsumableArray.js ***!
  \******************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

var arrayWithoutHoles = __webpack_require__(/*! ./arrayWithoutHoles */ "./node_modules/@babel/runtime/helpers/arrayWithoutHoles.js");

var iterableToArray = __webpack_require__(/*! ./iterableToArray */ "./node_modules/@babel/runtime/helpers/iterableToArray.js");

var unsupportedIterableToArray = __webpack_require__(/*! ./unsupportedIterableToArray */ "./node_modules/@babel/runtime/helpers/unsupportedIterableToArray.js");

var nonIterableSpread = __webpack_require__(/*! ./nonIterableSpread */ "./node_modules/@babel/runtime/helpers/nonIterableSpread.js");

function _toConsumableArray(arr) {
  return arrayWithoutHoles(arr) || iterableToArray(arr) || unsupportedIterableToArray(arr) || nonIterableSpread();
}

module.exports = _toConsumableArray;

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/unsupportedIterableToArray.js":
/*!***************************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/unsupportedIterableToArray.js ***!
  \***************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

var arrayLikeToArray = __webpack_require__(/*! ./arrayLikeToArray */ "./node_modules/@babel/runtime/helpers/arrayLikeToArray.js");

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

/***/ "./node_modules/@babel/runtime/regenerator/index.js":
/*!**********************************************************!*\
  !*** ./node_modules/@babel/runtime/regenerator/index.js ***!
  \**********************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

module.exports = __webpack_require__(/*! regenerator-runtime */ "./node_modules/regenerator-runtime/runtime.js");


/***/ }),

/***/ "./node_modules/memize/index.js":
/*!**************************************!*\
  !*** ./node_modules/memize/index.js ***!
  \**************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

/**
 * Memize options object.
 *
 * @typedef MemizeOptions
 *
 * @property {number} [maxSize] Maximum size of the cache.
 */

/**
 * Internal cache entry.
 *
 * @typedef MemizeCacheNode
 *
 * @property {?MemizeCacheNode|undefined} [prev] Previous node.
 * @property {?MemizeCacheNode|undefined} [next] Next node.
 * @property {Array<*>}                   args   Function arguments for cache
 *                                               entry.
 * @property {*}                          val    Function result.
 */

/**
 * Properties of the enhanced function for controlling cache.
 *
 * @typedef MemizeMemoizedFunction
 *
 * @property {()=>void} clear Clear the cache.
 */

/**
 * Accepts a function to be memoized, and returns a new memoized function, with
 * optional options.
 *
 * @template {Function} F
 *
 * @param {F}             fn        Function to memoize.
 * @param {MemizeOptions} [options] Options object.
 *
 * @return {F & MemizeMemoizedFunction} Memoized function.
 */
function memize( fn, options ) {
	var size = 0;

	/** @type {?MemizeCacheNode|undefined} */
	var head;

	/** @type {?MemizeCacheNode|undefined} */
	var tail;

	options = options || {};

	function memoized( /* ...args */ ) {
		var node = head,
			len = arguments.length,
			args, i;

		searchCache: while ( node ) {
			// Perform a shallow equality test to confirm that whether the node
			// under test is a candidate for the arguments passed. Two arrays
			// are shallowly equal if their length matches and each entry is
			// strictly equal between the two sets. Avoid abstracting to a
			// function which could incur an arguments leaking deoptimization.

			// Check whether node arguments match arguments length
			if ( node.args.length !== arguments.length ) {
				node = node.next;
				continue;
			}

			// Check whether node arguments match arguments values
			for ( i = 0; i < len; i++ ) {
				if ( node.args[ i ] !== arguments[ i ] ) {
					node = node.next;
					continue searchCache;
				}
			}

			// At this point we can assume we've found a match

			// Surface matched node to head if not already
			if ( node !== head ) {
				// As tail, shift to previous. Must only shift if not also
				// head, since if both head and tail, there is no previous.
				if ( node === tail ) {
					tail = node.prev;
				}

				// Adjust siblings to point to each other. If node was tail,
				// this also handles new tail's empty `next` assignment.
				/** @type {MemizeCacheNode} */ ( node.prev ).next = node.next;
				if ( node.next ) {
					node.next.prev = node.prev;
				}

				node.next = head;
				node.prev = null;
				/** @type {MemizeCacheNode} */ ( head ).prev = node;
				head = node;
			}

			// Return immediately
			return node.val;
		}

		// No cached value found. Continue to insertion phase:

		// Create a copy of arguments (avoid leaking deoptimization)
		args = new Array( len );
		for ( i = 0; i < len; i++ ) {
			args[ i ] = arguments[ i ];
		}

		node = {
			args: args,

			// Generate the result from original function
			val: fn.apply( null, args ),
		};

		// Don't need to check whether node is already head, since it would
		// have been returned above already if it was

		// Shift existing head down list
		if ( head ) {
			head.prev = node;
			node.next = head;
		} else {
			// If no head, follows that there's no tail (at initial or reset)
			tail = node;
		}

		// Trim tail if we're reached max size and are pending cache insertion
		if ( size === /** @type {MemizeOptions} */ ( options ).maxSize ) {
			tail = /** @type {MemizeCacheNode} */ ( tail ).prev;
			/** @type {MemizeCacheNode} */ ( tail ).next = null;
		} else {
			size++;
		}

		head = node;

		return node.val;
	}

	memoized.clear = function() {
		head = null;
		tail = null;
		size = 0;
	};

	if ( false ) {}

	// Ignore reason: There's not a clear solution to create an intersection of
	// the function with additional properties, where the goal is to retain the
	// function signature of the incoming argument and add control properties
	// on the return value.

	// @ts-ignore
	return memoized;
}

module.exports = memize;


/***/ }),

/***/ "./node_modules/regenerator-runtime/runtime.js":
/*!*****************************************************!*\
  !*** ./node_modules/regenerator-runtime/runtime.js ***!
  \*****************************************************/
/*! no static exports found */
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

  function define(obj, key, value) {
    Object.defineProperty(obj, key, {
      value: value,
      enumerable: true,
      configurable: true,
      writable: true
    });
    return obj[key];
  }
  try {
    // IE 8 has a broken Object.defineProperty that only works on DOM objects.
    define({}, "");
  } catch (err) {
    define = function(obj, key, value) {
      return obj[key] = value;
    };
  }

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
  GeneratorFunction.displayName = define(
    GeneratorFunctionPrototype,
    toStringTagSymbol,
    "GeneratorFunction"
  );

  // Helper for defining the .next, .throw, and .return methods of the
  // Iterator interface in terms of a single ._invoke method.
  function defineIteratorMethods(prototype) {
    ["next", "throw", "return"].forEach(function(method) {
      define(prototype, method, function(arg) {
        return this._invoke(method, arg);
      });
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
      define(genFun, toStringTagSymbol, "GeneratorFunction");
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

  define(Gp, toStringTagSymbol, "Generator");

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

/***/ "./packages/data/build-module/constants.js":
/*!*************************************************!*\
  !*** ./packages/data/build-module/constants.js ***!
  \*************************************************/
/*! exports provided: JETPACK_NAMESPACE, NAMESPACE, WC_ADMIN_NAMESPACE, WCS_NAMESPACE, MAX_PER_PAGE, SECOND, MINUTE, HOUR, DEFAULT_REQUIREMENT, DEFAULT_ACTIONABLE_STATUSES, QUERY_DEFAULTS */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "JETPACK_NAMESPACE", function() { return JETPACK_NAMESPACE; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "NAMESPACE", function() { return NAMESPACE; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "WC_ADMIN_NAMESPACE", function() { return WC_ADMIN_NAMESPACE; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "WCS_NAMESPACE", function() { return WCS_NAMESPACE; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "MAX_PER_PAGE", function() { return MAX_PER_PAGE; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "SECOND", function() { return SECOND; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "MINUTE", function() { return MINUTE; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "HOUR", function() { return HOUR; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "DEFAULT_REQUIREMENT", function() { return DEFAULT_REQUIREMENT; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "DEFAULT_ACTIONABLE_STATUSES", function() { return DEFAULT_ACTIONABLE_STATUSES; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "QUERY_DEFAULTS", function() { return QUERY_DEFAULTS; });
var JETPACK_NAMESPACE = '/jetpack/v4';
var NAMESPACE = '/wc-analytics';
var WC_ADMIN_NAMESPACE = '/wc-admin';
var WCS_NAMESPACE = '/wc/v1'; // WCS endpoints like Stripe are not avaiable on later /wc versions
// WordPress & WooCommerce both set a hard limit of 100 for the per_page parameter

var MAX_PER_PAGE = 100;
var SECOND = 1000;
var MINUTE = 60 * SECOND;
var HOUR = 60 * MINUTE;
var DEFAULT_REQUIREMENT = {
  timeout: 1 * MINUTE,
  freshness: 30 * MINUTE
};
var DEFAULT_ACTIONABLE_STATUSES = ['processing', 'on-hold'];
var QUERY_DEFAULTS = {
  pageSize: 25,
  period: 'month',
  compare: 'previous_year'
};

/***/ }),

/***/ "./packages/data/build-module/index.js":
/*!*********************************************!*\
  !*** ./packages/data/build-module/index.js ***!
  \*********************************************/
/*! exports provided: SETTINGS_STORE_NAME, withSettingsHydration, useSettings, PLUGINS_STORE_NAME, pluginNames, withPluginsHydration, ONBOARDING_STORE_NAME, withOnboardingHydration, USER_STORE_NAME, withCurrentUserHydration, useUserPreferences, OPTIONS_STORE_NAME, withOptionsHydration, __experimentalResolveSelect */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _settings__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./settings */ "./packages/data/build-module/settings/index.js");
/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "SETTINGS_STORE_NAME", function() { return _settings__WEBPACK_IMPORTED_MODULE_0__["SETTINGS_STORE_NAME"]; });

/* harmony import */ var _settings_with_settings_hydration__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./settings/with-settings-hydration */ "./packages/data/build-module/settings/with-settings-hydration.js");
/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "withSettingsHydration", function() { return _settings_with_settings_hydration__WEBPACK_IMPORTED_MODULE_1__["withSettingsHydration"]; });

/* harmony import */ var _settings_use_settings__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./settings/use-settings */ "./packages/data/build-module/settings/use-settings.js");
/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "useSettings", function() { return _settings_use_settings__WEBPACK_IMPORTED_MODULE_2__["useSettings"]; });

/* harmony import */ var _plugins__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./plugins */ "./packages/data/build-module/plugins/index.js");
/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "PLUGINS_STORE_NAME", function() { return _plugins__WEBPACK_IMPORTED_MODULE_3__["PLUGINS_STORE_NAME"]; });

/* harmony import */ var _plugins_constants__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./plugins/constants */ "./packages/data/build-module/plugins/constants.js");
/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "pluginNames", function() { return _plugins_constants__WEBPACK_IMPORTED_MODULE_4__["pluginNames"]; });

/* harmony import */ var _plugins_with_plugins_hydration__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./plugins/with-plugins-hydration */ "./packages/data/build-module/plugins/with-plugins-hydration.js");
/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "withPluginsHydration", function() { return _plugins_with_plugins_hydration__WEBPACK_IMPORTED_MODULE_5__["withPluginsHydration"]; });

/* harmony import */ var _onboarding__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./onboarding */ "./packages/data/build-module/onboarding/index.js");
/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "ONBOARDING_STORE_NAME", function() { return _onboarding__WEBPACK_IMPORTED_MODULE_6__["ONBOARDING_STORE_NAME"]; });

/* harmony import */ var _onboarding_with_onboarding_hydration__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ./onboarding/with-onboarding-hydration */ "./packages/data/build-module/onboarding/with-onboarding-hydration.js");
/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "withOnboardingHydration", function() { return _onboarding_with_onboarding_hydration__WEBPACK_IMPORTED_MODULE_7__["withOnboardingHydration"]; });

/* harmony import */ var _user_preferences__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ./user-preferences */ "./packages/data/build-module/user-preferences/index.js");
/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "USER_STORE_NAME", function() { return _user_preferences__WEBPACK_IMPORTED_MODULE_8__["USER_STORE_NAME"]; });

/* harmony import */ var _user_preferences_with_current_user_hydration__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ./user-preferences/with-current-user-hydration */ "./packages/data/build-module/user-preferences/with-current-user-hydration.js");
/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "withCurrentUserHydration", function() { return _user_preferences_with_current_user_hydration__WEBPACK_IMPORTED_MODULE_9__["withCurrentUserHydration"]; });

/* harmony import */ var _user_preferences_use_user_preferences__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! ./user-preferences/use-user-preferences */ "./packages/data/build-module/user-preferences/use-user-preferences.js");
/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "useUserPreferences", function() { return _user_preferences_use_user_preferences__WEBPACK_IMPORTED_MODULE_10__["useUserPreferences"]; });

/* harmony import */ var _options__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! ./options */ "./packages/data/build-module/options/index.js");
/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "OPTIONS_STORE_NAME", function() { return _options__WEBPACK_IMPORTED_MODULE_11__["OPTIONS_STORE_NAME"]; });

/* harmony import */ var _options_with_options_hydration__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! ./options/with-options-hydration */ "./packages/data/build-module/options/with-options-hydration.js");
/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "withOptionsHydration", function() { return _options_with_options_hydration__WEBPACK_IMPORTED_MODULE_12__["withOptionsHydration"]; });

/* harmony import */ var _registry__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! ./registry */ "./packages/data/build-module/registry.js");
/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "__experimentalResolveSelect", function() { return _registry__WEBPACK_IMPORTED_MODULE_13__["__experimentalResolveSelect"]; });
















/***/ }),

/***/ "./packages/data/build-module/onboarding/action-types.js":
/*!***************************************************************!*\
  !*** ./packages/data/build-module/onboarding/action-types.js ***!
  \***************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
var TYPES = {
  SET_PROFILE_ITEMS: 'SET_PROFILE_ITEMS',
  SET_ERROR: 'SET_ERROR',
  SET_IS_REQUESTING: 'SET_IS_REQUESTING'
};
/* harmony default export */ __webpack_exports__["default"] = (TYPES);

/***/ }),

/***/ "./packages/data/build-module/onboarding/actions.js":
/*!**********************************************************!*\
  !*** ./packages/data/build-module/onboarding/actions.js ***!
  \**********************************************************/
/*! exports provided: setError, setIsRequesting, setProfileItems, updateProfileItems */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "setError", function() { return setError; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "setIsRequesting", function() { return setIsRequesting; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "setProfileItems", function() { return setProfileItems; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "updateProfileItems", function() { return updateProfileItems; });
/* harmony import */ var _babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/regenerator */ "./node_modules/@babel/runtime/regenerator/index.js");
/* harmony import */ var _babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_data_controls__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/data-controls */ "@wordpress/data-controls");
/* harmony import */ var _wordpress_data_controls__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data_controls__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _action_types__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./action-types */ "./packages/data/build-module/onboarding/action-types.js");
/* harmony import */ var _constants__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../constants */ "./packages/data/build-module/constants.js");


var _marked = /*#__PURE__*/_babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0___default.a.mark(updateProfileItems);
/**
 * External dependencies
 */



/**
 * Internal dependencies
 */



function setError(selector, error) {
  return {
    type: _action_types__WEBPACK_IMPORTED_MODULE_2__["default"].SET_ERROR,
    selector: selector,
    error: error
  };
}
function setIsRequesting(selector, isRequesting) {
  return {
    type: _action_types__WEBPACK_IMPORTED_MODULE_2__["default"].SET_IS_REQUESTING,
    selector: selector,
    isRequesting: isRequesting
  };
}
function setProfileItems(profileItems) {
  var replace = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : false;
  return {
    type: _action_types__WEBPACK_IMPORTED_MODULE_2__["default"].SET_PROFILE_ITEMS,
    profileItems: profileItems,
    replace: replace
  };
}
function updateProfileItems(items) {
  var results;
  return _babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0___default.a.wrap(function updateProfileItems$(_context) {
    while (1) {
      switch (_context.prev = _context.next) {
        case 0:
          _context.next = 2;
          return setIsRequesting('updateProfileItems', true);

        case 2:
          _context.prev = 2;
          _context.next = 5;
          return Object(_wordpress_data_controls__WEBPACK_IMPORTED_MODULE_1__["apiFetch"])({
            path: "".concat(_constants__WEBPACK_IMPORTED_MODULE_3__["WC_ADMIN_NAMESPACE"], "/onboarding/profile"),
            method: 'POST',
            data: items
          });

        case 5:
          results = _context.sent;

          if (!(results && results.status === 'success')) {
            _context.next = 12;
            break;
          }

          _context.next = 9;
          return setProfileItems(items);

        case 9:
          _context.next = 11;
          return setIsRequesting('updateProfileItems', false);

        case 11:
          return _context.abrupt("return", results);

        case 12:
          throw new Error();

        case 15:
          _context.prev = 15;
          _context.t0 = _context["catch"](2);
          _context.next = 19;
          return setError('updateProfileItems', _context.t0);

        case 19:
          _context.next = 21;
          return setIsRequesting('updateProfileItems', false);

        case 21:
          throw new Error();

        case 22:
        case "end":
          return _context.stop();
      }
    }
  }, _marked, null, [[2, 15]]);
}

/***/ }),

/***/ "./packages/data/build-module/onboarding/constants.js":
/*!************************************************************!*\
  !*** ./packages/data/build-module/onboarding/constants.js ***!
  \************************************************************/
/*! exports provided: STORE_NAME */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "STORE_NAME", function() { return STORE_NAME; });
/**
 * Internal dependencies
 */
var STORE_NAME = 'wc/admin/onboarding';

/***/ }),

/***/ "./packages/data/build-module/onboarding/index.js":
/*!********************************************************!*\
  !*** ./packages/data/build-module/onboarding/index.js ***!
  \********************************************************/
/*! exports provided: ONBOARDING_STORE_NAME */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "ONBOARDING_STORE_NAME", function() { return ONBOARDING_STORE_NAME; });
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_data_controls__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/data-controls */ "@wordpress/data-controls");
/* harmony import */ var _wordpress_data_controls__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data_controls__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _constants__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./constants */ "./packages/data/build-module/onboarding/constants.js");
/* harmony import */ var _selectors__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./selectors */ "./packages/data/build-module/onboarding/selectors.js");
/* harmony import */ var _actions__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./actions */ "./packages/data/build-module/onboarding/actions.js");
/* harmony import */ var _resolvers__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./resolvers */ "./packages/data/build-module/onboarding/resolvers.js");
/* harmony import */ var _reducer__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./reducer */ "./packages/data/build-module/onboarding/reducer.js");
/**
 * External dependencies
 */


/**
 * Internal dependencies
 */






var storeSelectors = Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_0__["select"])(_constants__WEBPACK_IMPORTED_MODULE_2__["STORE_NAME"]); // @todo This is used to prevent double registration of the store due to webpack chunks.
// The `storeSelectors` condition can be removed once this is fixed.

if (!storeSelectors) {
  Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_0__["registerStore"])(_constants__WEBPACK_IMPORTED_MODULE_2__["STORE_NAME"], {
    reducer: _reducer__WEBPACK_IMPORTED_MODULE_6__["default"],
    actions: _actions__WEBPACK_IMPORTED_MODULE_4__,
    controls: _wordpress_data_controls__WEBPACK_IMPORTED_MODULE_1__["controls"],
    selectors: _selectors__WEBPACK_IMPORTED_MODULE_3__,
    resolvers: _resolvers__WEBPACK_IMPORTED_MODULE_5__
  });
}

var ONBOARDING_STORE_NAME = _constants__WEBPACK_IMPORTED_MODULE_2__["STORE_NAME"];

/***/ }),

/***/ "./packages/data/build-module/onboarding/reducer.js":
/*!**********************************************************!*\
  !*** ./packages/data/build-module/onboarding/reducer.js ***!
  \**********************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/defineProperty */ "./node_modules/@babel/runtime/helpers/defineProperty.js");
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _action_types__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./action-types */ "./packages/data/build-module/onboarding/action-types.js");


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
 * Internal dependencies
 */




var onboarding = function onboarding() {
  var state = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {
    profileItems: {},
    errors: {},
    requesting: {}
  };

  var _ref = arguments.length > 1 ? arguments[1] : undefined,
      type = _ref.type,
      profileItems = _ref.profileItems,
      replace = _ref.replace,
      error = _ref.error,
      isRequesting = _ref.isRequesting,
      selector = _ref.selector;

  switch (type) {
    case _action_types__WEBPACK_IMPORTED_MODULE_1__["default"].SET_PROFILE_ITEMS:
      state = _objectSpread(_objectSpread({}, state), {}, {
        profileItems: replace ? profileItems : _objectSpread(_objectSpread({}, state.profileItems), profileItems)
      });
      break;

    case _action_types__WEBPACK_IMPORTED_MODULE_1__["default"].SET_ERROR:
      state = _objectSpread(_objectSpread({}, state), {}, {
        errors: _objectSpread(_objectSpread({}, state.errors), {}, _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0___default()({}, selector, error))
      });
      break;

    case _action_types__WEBPACK_IMPORTED_MODULE_1__["default"].SET_IS_REQUESTING:
      state = _objectSpread(_objectSpread({}, state), {}, {
        requesting: _objectSpread(_objectSpread({}, state.requesting), {}, _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0___default()({}, selector, isRequesting))
      });
      break;
  }

  return state;
};

/* harmony default export */ __webpack_exports__["default"] = (onboarding);

/***/ }),

/***/ "./packages/data/build-module/onboarding/resolvers.js":
/*!************************************************************!*\
  !*** ./packages/data/build-module/onboarding/resolvers.js ***!
  \************************************************************/
/*! exports provided: getProfileItems */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "getProfileItems", function() { return getProfileItems; });
/* harmony import */ var _babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/regenerator */ "./node_modules/@babel/runtime/regenerator/index.js");
/* harmony import */ var _babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_data_controls__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/data-controls */ "@wordpress/data-controls");
/* harmony import */ var _wordpress_data_controls__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data_controls__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _constants__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../constants */ "./packages/data/build-module/constants.js");
/* harmony import */ var _actions__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./actions */ "./packages/data/build-module/onboarding/actions.js");


var _marked = /*#__PURE__*/_babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0___default.a.mark(getProfileItems);
/**
 * External dependencies
 */



/**
 * Internal dependencies
 */



function getProfileItems() {
  var results;
  return _babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0___default.a.wrap(function getProfileItems$(_context) {
    while (1) {
      switch (_context.prev = _context.next) {
        case 0:
          _context.prev = 0;
          _context.next = 3;
          return Object(_wordpress_data_controls__WEBPACK_IMPORTED_MODULE_1__["apiFetch"])({
            path: _constants__WEBPACK_IMPORTED_MODULE_2__["WC_ADMIN_NAMESPACE"] + '/onboarding/profile',
            method: 'GET'
          });

        case 3:
          results = _context.sent;
          _context.next = 6;
          return Object(_actions__WEBPACK_IMPORTED_MODULE_3__["setProfileItems"])(results, true);

        case 6:
          _context.next = 12;
          break;

        case 8:
          _context.prev = 8;
          _context.t0 = _context["catch"](0);
          _context.next = 12;
          return Object(_actions__WEBPACK_IMPORTED_MODULE_3__["setError"])('getProfileItems', _context.t0);

        case 12:
        case "end":
          return _context.stop();
      }
    }
  }, _marked, null, [[0, 8]]);
}

/***/ }),

/***/ "./packages/data/build-module/onboarding/selectors.js":
/*!************************************************************!*\
  !*** ./packages/data/build-module/onboarding/selectors.js ***!
  \************************************************************/
/*! exports provided: getProfileItems, getOnboardingError, isOnboardingRequesting */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "getProfileItems", function() { return getProfileItems; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "getOnboardingError", function() { return getOnboardingError; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "isOnboardingRequesting", function() { return isOnboardingRequesting; });
var getProfileItems = function getProfileItems(state) {
  return state.profileItems || {};
};
var getOnboardingError = function getOnboardingError(state, selector) {
  return state.errors[selector] || false;
};
var isOnboardingRequesting = function isOnboardingRequesting(state, selector) {
  return state.requesting[selector] || false;
};

/***/ }),

/***/ "./packages/data/build-module/onboarding/with-onboarding-hydration.js":
/*!****************************************************************************!*\
  !*** ./packages/data/build-module/onboarding/with-onboarding-hydration.js ***!
  \****************************************************************************/
/*! exports provided: withOnboardingHydration */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "withOnboardingHydration", function() { return withOnboardingHydration; });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _constants__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./constants */ "./packages/data/build-module/onboarding/constants.js");

/**
 * External dependencies
 */



/**
 * Internal dependencies
 */


var withOnboardingHydration = function withOnboardingHydration(data) {
  return function (OriginalComponent) {
    return function (props) {
      var onboardingRef = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["useRef"])(data);
      Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_1__["useSelect"])(function (select, registry) {
        if (!onboardingRef.current) {
          return;
        }

        var _select = select(_constants__WEBPACK_IMPORTED_MODULE_2__["STORE_NAME"]),
            isResolving = _select.isResolving,
            hasFinishedResolution = _select.hasFinishedResolution;

        var _registry$dispatch = registry.dispatch(_constants__WEBPACK_IMPORTED_MODULE_2__["STORE_NAME"]),
            startResolution = _registry$dispatch.startResolution,
            finishResolution = _registry$dispatch.finishResolution,
            setProfileItems = _registry$dispatch.setProfileItems;

        if (!isResolving('getProfileItems', []) && !hasFinishedResolution('getProfileItems', [])) {
          startResolution('getProfileItems', []);
          setProfileItems(onboardingRef.current, true);
          finishResolution('getProfileItems', []);
        }
      }, []);
      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(OriginalComponent, props);
    };
  };
};

/***/ }),

/***/ "./packages/data/build-module/options/action-types.js":
/*!************************************************************!*\
  !*** ./packages/data/build-module/options/action-types.js ***!
  \************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
var TYPES = {
  RECEIVE_OPTIONS: 'RECEIVE_OPTIONS',
  SET_IS_REQUESTING: 'SET_IS_REQUESTING',
  SET_IS_UPDATING: 'SET_IS_UPDATING',
  SET_REQUESTING_ERROR: 'SET_REQUESTING_ERROR',
  SET_UPDATING_ERROR: 'SET_UPDATING_ERROR'
};
/* harmony default export */ __webpack_exports__["default"] = (TYPES);

/***/ }),

/***/ "./packages/data/build-module/options/actions.js":
/*!*******************************************************!*\
  !*** ./packages/data/build-module/options/actions.js ***!
  \*******************************************************/
/*! exports provided: receiveOptions, setRequestingError, setUpdatingError, setIsUpdating, updateOptions */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "receiveOptions", function() { return receiveOptions; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "setRequestingError", function() { return setRequestingError; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "setUpdatingError", function() { return setUpdatingError; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "setIsUpdating", function() { return setIsUpdating; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "updateOptions", function() { return updateOptions; });
/* harmony import */ var _babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/regenerator */ "./node_modules/@babel/runtime/regenerator/index.js");
/* harmony import */ var _babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/defineProperty */ "./node_modules/@babel/runtime/helpers/defineProperty.js");
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_data_controls__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/data-controls */ "@wordpress/data-controls");
/* harmony import */ var _wordpress_data_controls__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data_controls__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _action_types__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./action-types */ "./packages/data/build-module/options/action-types.js");
/* harmony import */ var _constants__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../constants */ "./packages/data/build-module/constants.js");



var _marked = /*#__PURE__*/_babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0___default.a.mark(updateOptions);

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
        _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_1___default()(target, key, source[key]);
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



/**
 * Internal dependencies
 */



function receiveOptions(options) {
  return {
    type: _action_types__WEBPACK_IMPORTED_MODULE_3__["default"].RECEIVE_OPTIONS,
    options: options
  };
}
function setRequestingError(error, name) {
  return {
    type: _action_types__WEBPACK_IMPORTED_MODULE_3__["default"].SET_REQUESTING_ERROR,
    error: error,
    name: name
  };
}
function setUpdatingError(error) {
  return {
    type: _action_types__WEBPACK_IMPORTED_MODULE_3__["default"].SET_UPDATING_ERROR,
    error: error
  };
}
function setIsUpdating(isUpdating) {
  return {
    type: _action_types__WEBPACK_IMPORTED_MODULE_3__["default"].SET_IS_UPDATING,
    isUpdating: isUpdating
  };
}
function updateOptions(data) {
  var results;
  return _babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0___default.a.wrap(function updateOptions$(_context) {
    while (1) {
      switch (_context.prev = _context.next) {
        case 0:
          _context.next = 2;
          return setIsUpdating(true);

        case 2:
          _context.next = 4;
          return receiveOptions(data);

        case 4:
          _context.prev = 4;
          _context.next = 7;
          return Object(_wordpress_data_controls__WEBPACK_IMPORTED_MODULE_2__["apiFetch"])({
            path: _constants__WEBPACK_IMPORTED_MODULE_4__["WC_ADMIN_NAMESPACE"] + '/options',
            method: 'POST',
            data: data
          });

        case 7:
          results = _context.sent;
          _context.next = 10;
          return setIsUpdating(false);

        case 10:
          return _context.abrupt("return", _objectSpread({
            success: true
          }, results));

        case 13:
          _context.prev = 13;
          _context.t0 = _context["catch"](4);
          _context.next = 17;
          return setUpdatingError(_context.t0);

        case 17:
          return _context.abrupt("return", _objectSpread({
            success: false
          }, _context.t0));

        case 18:
        case "end":
          return _context.stop();
      }
    }
  }, _marked, null, [[4, 13]]);
}

/***/ }),

/***/ "./packages/data/build-module/options/constants.js":
/*!*********************************************************!*\
  !*** ./packages/data/build-module/options/constants.js ***!
  \*********************************************************/
/*! exports provided: STORE_NAME */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "STORE_NAME", function() { return STORE_NAME; });
var STORE_NAME = 'wc/admin/options';

/***/ }),

/***/ "./packages/data/build-module/options/controls.js":
/*!********************************************************!*\
  !*** ./packages/data/build-module/options/controls.js ***!
  \********************************************************/
/*! exports provided: batchFetch, controls */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "batchFetch", function() { return batchFetch; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "controls", function() { return controls; });
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/defineProperty */ "./node_modules/@babel/runtime/helpers/defineProperty.js");
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_data_controls__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/data-controls */ "@wordpress/data-controls");
/* harmony import */ var _wordpress_data_controls__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data_controls__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/api-fetch */ "@wordpress/api-fetch");
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _constants__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../constants */ "./packages/data/build-module/constants.js");


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




/**
 * Internal dependencies
 */


var optionNames = [];
var fetches = {};
var batchFetch = function batchFetch(optionName) {
  return {
    type: 'BATCH_FETCH',
    optionName: optionName
  };
};
var controls = _objectSpread(_objectSpread({}, _wordpress_data_controls__WEBPACK_IMPORTED_MODULE_1__["controls"]), {}, {
  BATCH_FETCH: function BATCH_FETCH(_ref) {
    var optionName = _ref.optionName;
    optionNames.push(optionName);
    return new Promise(function (resolve) {
      setTimeout(function () {
        var names = optionNames.join(',');

        if (fetches[names]) {
          return fetches[names].then(function (result) {
            resolve(result[optionName]);
          });
        }

        var url = _constants__WEBPACK_IMPORTED_MODULE_3__["WC_ADMIN_NAMESPACE"] + '/options?options=' + names;
        fetches[names] = _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default()({
          path: url
        });
        fetches[names].then(function (result) {
          return resolve(result);
        }); // Clear option names after all resolved;

        setTimeout(function () {
          optionNames = []; // Delete the fetch after to allow wp data to handle cache invalidation.

          delete fetches[names];
        }, 1);
      }, 1);
    });
  }
});

/***/ }),

/***/ "./packages/data/build-module/options/index.js":
/*!*****************************************************!*\
  !*** ./packages/data/build-module/options/index.js ***!
  \*****************************************************/
/*! exports provided: OPTIONS_STORE_NAME */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "OPTIONS_STORE_NAME", function() { return OPTIONS_STORE_NAME; });
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _constants__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./constants */ "./packages/data/build-module/options/constants.js");
/* harmony import */ var _selectors__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./selectors */ "./packages/data/build-module/options/selectors.js");
/* harmony import */ var _actions__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./actions */ "./packages/data/build-module/options/actions.js");
/* harmony import */ var _resolvers__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./resolvers */ "./packages/data/build-module/options/resolvers.js");
/* harmony import */ var _controls__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./controls */ "./packages/data/build-module/options/controls.js");
/* harmony import */ var _reducer__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./reducer */ "./packages/data/build-module/options/reducer.js");
/**
 * External dependencies
 */

/**
 * Internal dependencies
 */







Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_0__["registerStore"])(_constants__WEBPACK_IMPORTED_MODULE_1__["STORE_NAME"], {
  reducer: _reducer__WEBPACK_IMPORTED_MODULE_6__["default"],
  actions: _actions__WEBPACK_IMPORTED_MODULE_3__,
  controls: _controls__WEBPACK_IMPORTED_MODULE_5__["controls"],
  selectors: _selectors__WEBPACK_IMPORTED_MODULE_2__,
  resolvers: _resolvers__WEBPACK_IMPORTED_MODULE_4__
});
var OPTIONS_STORE_NAME = _constants__WEBPACK_IMPORTED_MODULE_1__["STORE_NAME"];

/***/ }),

/***/ "./packages/data/build-module/options/reducer.js":
/*!*******************************************************!*\
  !*** ./packages/data/build-module/options/reducer.js ***!
  \*******************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/defineProperty */ "./node_modules/@babel/runtime/helpers/defineProperty.js");
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _action_types__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./action-types */ "./packages/data/build-module/options/action-types.js");


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
 * Internal dependencies
 */




var optionsReducer = function optionsReducer() {
  var state = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {
    isUpdating: false,
    requestingErrors: {}
  };

  var _ref = arguments.length > 1 ? arguments[1] : undefined,
      type = _ref.type,
      options = _ref.options,
      error = _ref.error,
      isUpdating = _ref.isUpdating,
      name = _ref.name;

  switch (type) {
    case _action_types__WEBPACK_IMPORTED_MODULE_1__["default"].RECEIVE_OPTIONS:
      state = _objectSpread(_objectSpread({}, state), options);
      break;

    case _action_types__WEBPACK_IMPORTED_MODULE_1__["default"].SET_IS_UPDATING:
      state = _objectSpread(_objectSpread({}, state), {}, {
        isUpdating: isUpdating
      });
      break;

    case _action_types__WEBPACK_IMPORTED_MODULE_1__["default"].SET_REQUESTING_ERROR:
      state = _objectSpread(_objectSpread({}, state), {}, {
        requestingErrors: _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0___default()({}, name, error)
      });
      break;

    case _action_types__WEBPACK_IMPORTED_MODULE_1__["default"].SET_UPDATING_ERROR:
      state = _objectSpread(_objectSpread({}, state), {}, {
        error: error,
        updatingError: error,
        isUpdating: false
      });
      break;
  }

  return state;
};

/* harmony default export */ __webpack_exports__["default"] = (optionsReducer);

/***/ }),

/***/ "./packages/data/build-module/options/resolvers.js":
/*!*********************************************************!*\
  !*** ./packages/data/build-module/options/resolvers.js ***!
  \*********************************************************/
/*! exports provided: getOption */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "getOption", function() { return getOption; });
/* harmony import */ var _babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/regenerator */ "./node_modules/@babel/runtime/regenerator/index.js");
/* harmony import */ var _babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _actions__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./actions */ "./packages/data/build-module/options/actions.js");
/* harmony import */ var _controls__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./controls */ "./packages/data/build-module/options/controls.js");


var _marked = /*#__PURE__*/_babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0___default.a.mark(getOption);
/**
 * Internal dependencies
 */




/**
 * Request an option value.
 *
 * @param {string} name - Option name
 */

function getOption(name) {
  var result;
  return _babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0___default.a.wrap(function getOption$(_context) {
    while (1) {
      switch (_context.prev = _context.next) {
        case 0:
          _context.prev = 0;
          _context.next = 3;
          return Object(_controls__WEBPACK_IMPORTED_MODULE_2__["batchFetch"])(name);

        case 3:
          result = _context.sent;
          _context.next = 6;
          return Object(_actions__WEBPACK_IMPORTED_MODULE_1__["receiveOptions"])(result);

        case 6:
          _context.next = 12;
          break;

        case 8:
          _context.prev = 8;
          _context.t0 = _context["catch"](0);
          _context.next = 12;
          return Object(_actions__WEBPACK_IMPORTED_MODULE_1__["setRequestingError"])(_context.t0, name);

        case 12:
        case "end":
          return _context.stop();
      }
    }
  }, _marked, null, [[0, 8]]);
}

/***/ }),

/***/ "./packages/data/build-module/options/selectors.js":
/*!*********************************************************!*\
  !*** ./packages/data/build-module/options/selectors.js ***!
  \*********************************************************/
/*! exports provided: getOption, getOptionsRequestingError, isOptionsUpdating, getOptionsUpdatingError */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "getOption", function() { return getOption; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "getOptionsRequestingError", function() { return getOptionsRequestingError; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "isOptionsUpdating", function() { return isOptionsUpdating; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "getOptionsUpdatingError", function() { return getOptionsUpdatingError; });
/**
 * Get option from state tree.
 *
 * @param {Object} state - Reducer state
 * @param {Array} name - Option name
 */
var getOption = function getOption(state, name) {
  return state[name];
};
/**
 * Determine if an options request resulted in an error.
 *
 * @param {Object} state - Reducer state
 * @param {string} name - Option name
 */

var getOptionsRequestingError = function getOptionsRequestingError(state, name) {
  return state.requestingErrors[name] || false;
};
/**
 * Determine if options are being updated.
 *
 * @param {Object} state - Reducer state
 */

var isOptionsUpdating = function isOptionsUpdating(state) {
  return state.isUpdating || false;
};
/**
 * Determine if an options update resulted in an error.
 *
 * @param {Object} state - Reducer state
 */

var getOptionsUpdatingError = function getOptionsUpdatingError(state) {
  return state.updatingError || false;
};

/***/ }),

/***/ "./packages/data/build-module/options/with-options-hydration.js":
/*!**********************************************************************!*\
  !*** ./packages/data/build-module/options/with-options-hydration.js ***!
  \**********************************************************************/
/*! exports provided: withOptionsHydration */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "withOptionsHydration", function() { return withOptionsHydration; });
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/defineProperty */ "./node_modules/@babel/runtime/helpers/defineProperty.js");
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _constants__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./constants */ "./packages/data/build-module/options/constants.js");


/**
 * External dependencies
 */



/**
 * Internal dependencies
 */


var withOptionsHydration = function withOptionsHydration(data) {
  return function (OriginalComponent) {
    return function (props) {
      var dataRef = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["useRef"])(data);
      Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_2__["useSelect"])(function (select, registry) {
        if (!dataRef.current) {
          return;
        }

        var _select = select(_constants__WEBPACK_IMPORTED_MODULE_3__["STORE_NAME"]),
            isResolving = _select.isResolving,
            hasFinishedResolution = _select.hasFinishedResolution;

        var _registry$dispatch = registry.dispatch(_constants__WEBPACK_IMPORTED_MODULE_3__["STORE_NAME"]),
            startResolution = _registry$dispatch.startResolution,
            finishResolution = _registry$dispatch.finishResolution,
            receiveOptions = _registry$dispatch.receiveOptions;

        var names = Object.keys(dataRef.current);
        names.forEach(function (name) {
          if (!isResolving('getOption', [name]) && !hasFinishedResolution('getOption', [name])) {
            startResolution('getOption', [name]);
            receiveOptions(_babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0___default()({}, name, dataRef.current[name]));
            finishResolution('getOption', [name]);
          }
        });
      }, []);
      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])(OriginalComponent, props);
    };
  };
};

/***/ }),

/***/ "./packages/data/build-module/plugins/action-types.js":
/*!************************************************************!*\
  !*** ./packages/data/build-module/plugins/action-types.js ***!
  \************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
var TYPES = {
  UPDATE_ACTIVE_PLUGINS: 'UPDATE_ACTIVE_PLUGINS',
  UPDATE_INSTALLED_PLUGINS: 'UPDATE_INSTALLED_PLUGINS',
  SET_IS_REQUESTING: 'SET_IS_REQUESTING',
  SET_ERROR: 'SET_ERROR',
  UPDATE_JETPACK_CONNECTION: 'UPDATE_JETPACK_CONNECTION',
  UPDATE_JETPACK_CONNECT_URL: 'UPDATE_JETPACK_CONNECT_URL'
};
/* harmony default export */ __webpack_exports__["default"] = (TYPES);

/***/ }),

/***/ "./packages/data/build-module/plugins/actions.js":
/*!*******************************************************!*\
  !*** ./packages/data/build-module/plugins/actions.js ***!
  \*******************************************************/
/*! exports provided: updateActivePlugins, updateInstalledPlugins, setIsRequesting, setError, updateIsJetpackConnected, updateJetpackConnectUrl, installPlugins, activatePlugins, installAndActivatePlugins, formatErrors */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "updateActivePlugins", function() { return updateActivePlugins; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "updateInstalledPlugins", function() { return updateInstalledPlugins; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "setIsRequesting", function() { return setIsRequesting; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "setError", function() { return setError; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "updateIsJetpackConnected", function() { return updateIsJetpackConnected; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "updateJetpackConnectUrl", function() { return updateJetpackConnectUrl; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "installPlugins", function() { return installPlugins; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "activatePlugins", function() { return activatePlugins; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "installAndActivatePlugins", function() { return installAndActivatePlugins; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "formatErrors", function() { return formatErrors; });
/* harmony import */ var _babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/regenerator */ "./node_modules/@babel/runtime/regenerator/index.js");
/* harmony import */ var _babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_data_controls__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/data-controls */ "@wordpress/data-controls");
/* harmony import */ var _wordpress_data_controls__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data_controls__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _constants__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./constants */ "./packages/data/build-module/plugins/constants.js");
/* harmony import */ var _action_types__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./action-types */ "./packages/data/build-module/plugins/action-types.js");
/* harmony import */ var _constants__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../constants */ "./packages/data/build-module/constants.js");


var _marked = /*#__PURE__*/_babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0___default.a.mark(installPlugins),
    _marked2 = /*#__PURE__*/_babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0___default.a.mark(activatePlugins),
    _marked3 = /*#__PURE__*/_babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0___default.a.mark(installAndActivatePlugins);
/**
 * External dependencies
 */



/**
 * Internal dependencies
 */




function updateActivePlugins(active) {
  var replace = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : false;
  return {
    type: _action_types__WEBPACK_IMPORTED_MODULE_3__["default"].UPDATE_ACTIVE_PLUGINS,
    active: active,
    replace: replace
  };
}
function updateInstalledPlugins(installed) {
  var replace = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : false;
  return {
    type: _action_types__WEBPACK_IMPORTED_MODULE_3__["default"].UPDATE_INSTALLED_PLUGINS,
    installed: installed,
    replace: replace
  };
}
function setIsRequesting(selector, isRequesting) {
  return {
    type: _action_types__WEBPACK_IMPORTED_MODULE_3__["default"].SET_IS_REQUESTING,
    selector: selector,
    isRequesting: isRequesting
  };
}
function setError(selector, error) {
  return {
    type: _action_types__WEBPACK_IMPORTED_MODULE_3__["default"].SET_ERROR,
    selector: selector,
    error: error
  };
}
function updateIsJetpackConnected(jetpackConnection) {
  return {
    type: _action_types__WEBPACK_IMPORTED_MODULE_3__["default"].UPDATE_JETPACK_CONNECTION,
    jetpackConnection: jetpackConnection
  };
}
function updateJetpackConnectUrl(redirectUrl, jetpackConnectUrl) {
  return {
    type: _action_types__WEBPACK_IMPORTED_MODULE_3__["default"].UPDATE_JETPACK_CONNECT_URL,
    jetpackConnectUrl: jetpackConnectUrl,
    redirectUrl: redirectUrl
  };
}
function installPlugins(plugins) {
  var results;
  return _babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0___default.a.wrap(function installPlugins$(_context) {
    while (1) {
      switch (_context.prev = _context.next) {
        case 0:
          _context.next = 2;
          return setIsRequesting('installPlugins', true);

        case 2:
          _context.prev = 2;
          _context.next = 5;
          return Object(_wordpress_data_controls__WEBPACK_IMPORTED_MODULE_1__["apiFetch"])({
            path: "".concat(_constants__WEBPACK_IMPORTED_MODULE_4__["WC_ADMIN_NAMESPACE"], "/plugins/install"),
            method: 'POST',
            data: {
              plugins: plugins.join(',')
            }
          });

        case 5:
          results = _context.sent;

          if (!results.data.installed.length) {
            _context.next = 9;
            break;
          }

          _context.next = 9;
          return updateInstalledPlugins(results.data.installed);

        case 9:
          if (!Object.keys(results.errors.errors).length) {
            _context.next = 11;
            break;
          }

          throw results.errors;

        case 11:
          _context.next = 13;
          return setIsRequesting('installPlugins', false);

        case 13:
          return _context.abrupt("return", results);

        case 16:
          _context.prev = 16;
          _context.t0 = _context["catch"](2);
          _context.next = 20;
          return setError('installPlugins', _context.t0);

        case 20:
          throw formatErrors(_context.t0);

        case 21:
        case "end":
          return _context.stop();
      }
    }
  }, _marked, null, [[2, 16]]);
}
function activatePlugins(plugins) {
  var results;
  return _babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0___default.a.wrap(function activatePlugins$(_context2) {
    while (1) {
      switch (_context2.prev = _context2.next) {
        case 0:
          _context2.next = 2;
          return setIsRequesting('activatePlugins', true);

        case 2:
          _context2.prev = 2;
          _context2.next = 5;
          return Object(_wordpress_data_controls__WEBPACK_IMPORTED_MODULE_1__["apiFetch"])({
            path: "".concat(_constants__WEBPACK_IMPORTED_MODULE_4__["WC_ADMIN_NAMESPACE"], "/plugins/activate"),
            method: 'POST',
            data: {
              plugins: plugins.join(',')
            }
          });

        case 5:
          results = _context2.sent;

          if (!results.data.activated.length) {
            _context2.next = 9;
            break;
          }

          _context2.next = 9;
          return updateActivePlugins(results.data.activated);

        case 9:
          if (!Object.keys(results.errors.errors).length) {
            _context2.next = 11;
            break;
          }

          throw results.errors;

        case 11:
          _context2.next = 13;
          return setIsRequesting('activatePlugins', false);

        case 13:
          return _context2.abrupt("return", results);

        case 16:
          _context2.prev = 16;
          _context2.t0 = _context2["catch"](2);
          _context2.next = 20;
          return setError('activatePlugins', _context2.t0);

        case 20:
          throw formatErrors(_context2.t0);

        case 21:
        case "end":
          return _context2.stop();
      }
    }
  }, _marked2, null, [[2, 16]]);
}
function installAndActivatePlugins(plugins) {
  var activations;
  return _babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0___default.a.wrap(function installAndActivatePlugins$(_context3) {
    while (1) {
      switch (_context3.prev = _context3.next) {
        case 0:
          _context3.prev = 0;
          _context3.next = 3;
          return Object(_wordpress_data_controls__WEBPACK_IMPORTED_MODULE_1__["dispatch"])(_constants__WEBPACK_IMPORTED_MODULE_2__["STORE_NAME"], 'installPlugins', plugins);

        case 3:
          _context3.next = 5;
          return Object(_wordpress_data_controls__WEBPACK_IMPORTED_MODULE_1__["dispatch"])(_constants__WEBPACK_IMPORTED_MODULE_2__["STORE_NAME"], 'activatePlugins', plugins);

        case 5:
          activations = _context3.sent;
          return _context3.abrupt("return", activations);

        case 9:
          _context3.prev = 9;
          _context3.t0 = _context3["catch"](0);
          throw _context3.t0;

        case 12:
        case "end":
          return _context3.stop();
      }
    }
  }, _marked3, null, [[0, 9]]);
}
function formatErrors(response) {
  if (response.errors) {
    // Replace the slug with a plugin name if a constant exists.
    Object.keys(response.errors).forEach(function (plugin) {
      response.errors[plugin] = response.errors[plugin].map(function (pluginError) {
        return _constants__WEBPACK_IMPORTED_MODULE_2__["pluginNames"][plugin] ? pluginError.replace("`".concat(plugin, "`"), _constants__WEBPACK_IMPORTED_MODULE_2__["pluginNames"][plugin]) : pluginError;
      });
    });
  }

  return response;
}

/***/ }),

/***/ "./packages/data/build-module/plugins/constants.js":
/*!*********************************************************!*\
  !*** ./packages/data/build-module/plugins/constants.js ***!
  \*********************************************************/
/*! exports provided: STORE_NAME, pluginNames */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "STORE_NAME", function() { return STORE_NAME; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "pluginNames", function() { return pluginNames; });
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__);
/**
 * External dependencies
 */

var STORE_NAME = 'wc/admin/plugins';
/**
 * Plugin slugs and names as key/value pairs.
 */

var pluginNames = {
  'facebook-for-woocommerce': Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Facebook for WooCommerce', 'woocommerce'),
  jetpack: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Jetpack', 'woocommerce'),
  'klarna-checkout-for-woocommerce': Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Klarna Checkout for WooCommerce', 'woocommerce'),
  'klarna-payments-for-woocommerce': Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Klarna Payments for WooCommerce', 'woocommerce'),
  'mailchimp-for-woocommerce': Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Mailchimp for WooCommerce', 'woocommerce'),
  'woocommerce-gateway-paypal-express-checkout': Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('WooCommerce PayPal', 'woocommerce'),
  'woocommerce-gateway-stripe': Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('WooCommerce Stripe', 'woocommerce'),
  'woocommerce-payfast-gateway': Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('WooCommerce PayFast', 'woocommerce'),
  'woocommerce-payments': Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('WooCommerce Payments', 'woocommerce'),
  'woocommerce-services': Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('WooCommerce Services', 'woocommerce'),
  'woocommerce-shipstation-integration': Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('WooCommerce ShipStation Gateway', 'woocommerce'),
  'kliken-marketing-for-google': Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Google Ads', 'woocommerce')
};

/***/ }),

/***/ "./packages/data/build-module/plugins/index.js":
/*!*****************************************************!*\
  !*** ./packages/data/build-module/plugins/index.js ***!
  \*****************************************************/
/*! exports provided: PLUGINS_STORE_NAME */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "PLUGINS_STORE_NAME", function() { return PLUGINS_STORE_NAME; });
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_data_controls__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/data-controls */ "@wordpress/data-controls");
/* harmony import */ var _wordpress_data_controls__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data_controls__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _constants__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./constants */ "./packages/data/build-module/plugins/constants.js");
/* harmony import */ var _selectors__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./selectors */ "./packages/data/build-module/plugins/selectors.js");
/* harmony import */ var _actions__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./actions */ "./packages/data/build-module/plugins/actions.js");
/* harmony import */ var _resolvers__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./resolvers */ "./packages/data/build-module/plugins/resolvers.js");
/* harmony import */ var _reducer__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./reducer */ "./packages/data/build-module/plugins/reducer.js");
/**
 * External dependencies
 */


/**
 * Internal dependencies
 */






var storeSelectors = Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_0__["select"])(_constants__WEBPACK_IMPORTED_MODULE_2__["STORE_NAME"]); // @todo This is used to prevent double registration of the store due to webpack chunks.
// The `storeSelectors` condition can be removed once this is fixed.

if (!storeSelectors) {
  Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_0__["registerStore"])(_constants__WEBPACK_IMPORTED_MODULE_2__["STORE_NAME"], {
    reducer: _reducer__WEBPACK_IMPORTED_MODULE_6__["default"],
    actions: _actions__WEBPACK_IMPORTED_MODULE_4__,
    controls: _wordpress_data_controls__WEBPACK_IMPORTED_MODULE_1__["controls"],
    selectors: _selectors__WEBPACK_IMPORTED_MODULE_3__,
    resolvers: _resolvers__WEBPACK_IMPORTED_MODULE_5__
  });
}

var PLUGINS_STORE_NAME = _constants__WEBPACK_IMPORTED_MODULE_2__["STORE_NAME"];

/***/ }),

/***/ "./packages/data/build-module/plugins/reducer.js":
/*!*******************************************************!*\
  !*** ./packages/data/build-module/plugins/reducer.js ***!
  \*******************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/defineProperty */ "./node_modules/@babel/runtime/helpers/defineProperty.js");
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! lodash */ "lodash");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _action_types__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./action-types */ "./packages/data/build-module/plugins/action-types.js");


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



/**
 * Internal dependencies
 */



var plugins = function plugins() {
  var state = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {
    active: [],
    installed: [],
    requesting: {},
    errors: {},
    jetpackConnectUrls: {}
  };

  var _ref = arguments.length > 1 ? arguments[1] : undefined,
      type = _ref.type,
      active = _ref.active,
      installed = _ref.installed,
      selector = _ref.selector,
      isRequesting = _ref.isRequesting,
      error = _ref.error,
      jetpackConnection = _ref.jetpackConnection,
      redirectUrl = _ref.redirectUrl,
      jetpackConnectUrl = _ref.jetpackConnectUrl,
      replace = _ref.replace;

  switch (type) {
    case _action_types__WEBPACK_IMPORTED_MODULE_2__["default"].UPDATE_ACTIVE_PLUGINS:
      state = _objectSpread(_objectSpread({}, state), {}, {
        active: replace ? active : Object(lodash__WEBPACK_IMPORTED_MODULE_1__["concat"])(state.active, active),
        requesting: _objectSpread(_objectSpread({}, state.requesting), {}, {
          getActivePlugins: false,
          activatePlugins: false
        }),
        errors: _objectSpread(_objectSpread({}, state.errors), {}, {
          getActivePlugins: false,
          activatePlugins: false
        })
      });
      break;

    case _action_types__WEBPACK_IMPORTED_MODULE_2__["default"].UPDATE_INSTALLED_PLUGINS:
      state = _objectSpread(_objectSpread({}, state), {}, {
        installed: replace ? installed : Object(lodash__WEBPACK_IMPORTED_MODULE_1__["concat"])(state.installed, installed),
        requesting: _objectSpread(_objectSpread({}, state.requesting), {}, {
          getInstalledPlugins: false,
          installPlugin: false
        }),
        errors: _objectSpread(_objectSpread({}, state.errors), {}, {
          getInstalledPlugins: false,
          installPlugin: false
        })
      });
      break;

    case _action_types__WEBPACK_IMPORTED_MODULE_2__["default"].SET_IS_REQUESTING:
      state = _objectSpread(_objectSpread({}, state), {}, {
        requesting: _objectSpread(_objectSpread({}, state.requesting), {}, _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0___default()({}, selector, isRequesting))
      });
      break;

    case _action_types__WEBPACK_IMPORTED_MODULE_2__["default"].SET_ERROR:
      state = _objectSpread(_objectSpread({}, state), {}, {
        requesting: _objectSpread(_objectSpread({}, state.requesting), {}, _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0___default()({}, selector, false)),
        errors: _objectSpread(_objectSpread({}, state.errors), {}, _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0___default()({}, selector, error))
      });
      break;

    case _action_types__WEBPACK_IMPORTED_MODULE_2__["default"].UPDATE_JETPACK_CONNECTION:
      state = _objectSpread(_objectSpread({}, state), {}, {
        jetpackConnection: jetpackConnection
      });
      break;

    case _action_types__WEBPACK_IMPORTED_MODULE_2__["default"].UPDATE_JETPACK_CONNECT_URL:
      state = _objectSpread(_objectSpread({}, state), {}, {
        jetpackConnectUrls: _objectSpread(_objectSpread({}, state.jetpackConnectUrl), {}, _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0___default()({}, redirectUrl, jetpackConnectUrl))
      });
  }

  return state;
};

/* harmony default export */ __webpack_exports__["default"] = (plugins);

/***/ }),

/***/ "./packages/data/build-module/plugins/resolvers.js":
/*!*********************************************************!*\
  !*** ./packages/data/build-module/plugins/resolvers.js ***!
  \*********************************************************/
/*! exports provided: getActivePlugins, getInstalledPlugins, isJetpackConnected, getJetpackConnectUrl */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "getActivePlugins", function() { return getActivePlugins; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "getInstalledPlugins", function() { return getInstalledPlugins; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "isJetpackConnected", function() { return isJetpackConnected; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "getJetpackConnectUrl", function() { return getJetpackConnectUrl; });
/* harmony import */ var _babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/regenerator */ "./node_modules/@babel/runtime/regenerator/index.js");
/* harmony import */ var _babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_data_controls__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/data-controls */ "@wordpress/data-controls");
/* harmony import */ var _wordpress_data_controls__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data_controls__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_url__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/url */ "@wordpress/url");
/* harmony import */ var _wordpress_url__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_url__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _constants__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../constants */ "./packages/data/build-module/constants.js");
/* harmony import */ var _actions__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./actions */ "./packages/data/build-module/plugins/actions.js");


var _marked = /*#__PURE__*/_babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0___default.a.mark(getActivePlugins),
    _marked2 = /*#__PURE__*/_babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0___default.a.mark(getInstalledPlugins),
    _marked3 = /*#__PURE__*/_babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0___default.a.mark(isJetpackConnected),
    _marked4 = /*#__PURE__*/_babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0___default.a.mark(getJetpackConnectUrl);
/**
 * External dependencies
 */




/**
 * Internal dependencies
 */



function getActivePlugins() {
  var url, results;
  return _babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0___default.a.wrap(function getActivePlugins$(_context) {
    while (1) {
      switch (_context.prev = _context.next) {
        case 0:
          _context.next = 2;
          return Object(_actions__WEBPACK_IMPORTED_MODULE_4__["setIsRequesting"])('getActivePlugins', true);

        case 2:
          _context.prev = 2;
          url = _constants__WEBPACK_IMPORTED_MODULE_3__["WC_ADMIN_NAMESPACE"] + '/plugins/active';
          _context.next = 6;
          return Object(_wordpress_data_controls__WEBPACK_IMPORTED_MODULE_1__["apiFetch"])({
            path: url,
            method: 'GET'
          });

        case 6:
          results = _context.sent;
          _context.next = 9;
          return Object(_actions__WEBPACK_IMPORTED_MODULE_4__["updateActivePlugins"])(results.plugins, true);

        case 9:
          _context.next = 15;
          break;

        case 11:
          _context.prev = 11;
          _context.t0 = _context["catch"](2);
          _context.next = 15;
          return Object(_actions__WEBPACK_IMPORTED_MODULE_4__["setError"])('getActivePlugins', _context.t0);

        case 15:
        case "end":
          return _context.stop();
      }
    }
  }, _marked, null, [[2, 11]]);
}
function getInstalledPlugins() {
  var url, results;
  return _babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0___default.a.wrap(function getInstalledPlugins$(_context2) {
    while (1) {
      switch (_context2.prev = _context2.next) {
        case 0:
          _context2.next = 2;
          return Object(_actions__WEBPACK_IMPORTED_MODULE_4__["setIsRequesting"])('getInstalledPlugins', true);

        case 2:
          _context2.prev = 2;
          url = _constants__WEBPACK_IMPORTED_MODULE_3__["WC_ADMIN_NAMESPACE"] + '/plugins/installed';
          _context2.next = 6;
          return Object(_wordpress_data_controls__WEBPACK_IMPORTED_MODULE_1__["apiFetch"])({
            path: url,
            method: 'GET'
          });

        case 6:
          results = _context2.sent;
          _context2.next = 9;
          return Object(_actions__WEBPACK_IMPORTED_MODULE_4__["updateInstalledPlugins"])(results, true);

        case 9:
          _context2.next = 15;
          break;

        case 11:
          _context2.prev = 11;
          _context2.t0 = _context2["catch"](2);
          _context2.next = 15;
          return Object(_actions__WEBPACK_IMPORTED_MODULE_4__["setError"])('getInstalledPlugins', _context2.t0);

        case 15:
        case "end":
          return _context2.stop();
      }
    }
  }, _marked2, null, [[2, 11]]);
}
function isJetpackConnected() {
  var url, results;
  return _babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0___default.a.wrap(function isJetpackConnected$(_context3) {
    while (1) {
      switch (_context3.prev = _context3.next) {
        case 0:
          _context3.next = 2;
          return Object(_actions__WEBPACK_IMPORTED_MODULE_4__["setIsRequesting"])('isJetpackConnected', true);

        case 2:
          _context3.prev = 2;
          url = _constants__WEBPACK_IMPORTED_MODULE_3__["JETPACK_NAMESPACE"] + '/connection';
          _context3.next = 6;
          return Object(_wordpress_data_controls__WEBPACK_IMPORTED_MODULE_1__["apiFetch"])({
            path: url,
            method: 'GET'
          });

        case 6:
          results = _context3.sent;
          _context3.next = 9;
          return Object(_actions__WEBPACK_IMPORTED_MODULE_4__["updateIsJetpackConnected"])(results.isActive);

        case 9:
          _context3.next = 15;
          break;

        case 11:
          _context3.prev = 11;
          _context3.t0 = _context3["catch"](2);
          _context3.next = 15;
          return Object(_actions__WEBPACK_IMPORTED_MODULE_4__["setError"])('isJetpackConnected', _context3.t0);

        case 15:
          _context3.next = 17;
          return Object(_actions__WEBPACK_IMPORTED_MODULE_4__["setIsRequesting"])('isJetpackConnected', false);

        case 17:
        case "end":
          return _context3.stop();
      }
    }
  }, _marked3, null, [[2, 11]]);
}
function getJetpackConnectUrl(query) {
  var url, results;
  return _babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0___default.a.wrap(function getJetpackConnectUrl$(_context4) {
    while (1) {
      switch (_context4.prev = _context4.next) {
        case 0:
          _context4.next = 2;
          return Object(_actions__WEBPACK_IMPORTED_MODULE_4__["setIsRequesting"])('getJetpackConnectUrl', true);

        case 2:
          _context4.prev = 2;
          url = Object(_wordpress_url__WEBPACK_IMPORTED_MODULE_2__["addQueryArgs"])(_constants__WEBPACK_IMPORTED_MODULE_3__["WC_ADMIN_NAMESPACE"] + '/plugins/connect-jetpack', query);
          _context4.next = 6;
          return Object(_wordpress_data_controls__WEBPACK_IMPORTED_MODULE_1__["apiFetch"])({
            path: url,
            method: 'GET'
          });

        case 6:
          results = _context4.sent;
          _context4.next = 9;
          return Object(_actions__WEBPACK_IMPORTED_MODULE_4__["updateJetpackConnectUrl"])(query.redirect_url, results.connectAction);

        case 9:
          _context4.next = 15;
          break;

        case 11:
          _context4.prev = 11;
          _context4.t0 = _context4["catch"](2);
          _context4.next = 15;
          return Object(_actions__WEBPACK_IMPORTED_MODULE_4__["setError"])('getJetpackConnectUrl', _context4.t0);

        case 15:
          _context4.next = 17;
          return Object(_actions__WEBPACK_IMPORTED_MODULE_4__["setIsRequesting"])('getJetpackConnectUrl', false);

        case 17:
        case "end":
          return _context4.stop();
      }
    }
  }, _marked4, null, [[2, 11]]);
}

/***/ }),

/***/ "./packages/data/build-module/plugins/selectors.js":
/*!*********************************************************!*\
  !*** ./packages/data/build-module/plugins/selectors.js ***!
  \*********************************************************/
/*! exports provided: getActivePlugins, getInstalledPlugins, isPluginsRequesting, getPluginsError, isJetpackConnected, getJetpackConnectUrl */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "getActivePlugins", function() { return getActivePlugins; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "getInstalledPlugins", function() { return getInstalledPlugins; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "isPluginsRequesting", function() { return isPluginsRequesting; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "getPluginsError", function() { return getPluginsError; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "isJetpackConnected", function() { return isJetpackConnected; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "getJetpackConnectUrl", function() { return getJetpackConnectUrl; });
var getActivePlugins = function getActivePlugins(state) {
  return state.active || [];
};
var getInstalledPlugins = function getInstalledPlugins(state) {
  return state.installed || [];
};
var isPluginsRequesting = function isPluginsRequesting(state, selector) {
  return state.requesting[selector] || false;
};
var getPluginsError = function getPluginsError(state, selector) {
  return state.errors[selector] || false;
};
var isJetpackConnected = function isJetpackConnected(state) {
  return state.jetpackConnection;
};
var getJetpackConnectUrl = function getJetpackConnectUrl(state, query) {
  return state.jetpackConnectUrls[query.redirect_url];
};

/***/ }),

/***/ "./packages/data/build-module/plugins/with-plugins-hydration.js":
/*!**********************************************************************!*\
  !*** ./packages/data/build-module/plugins/with-plugins-hydration.js ***!
  \**********************************************************************/
/*! exports provided: withPluginsHydration */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "withPluginsHydration", function() { return withPluginsHydration; });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _constants__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./constants */ "./packages/data/build-module/plugins/constants.js");

/**
 * External dependencies
 */



/**
 * Internal dependencies
 */


var withPluginsHydration = function withPluginsHydration(data) {
  return function (OriginalComponent) {
    return function (props) {
      var dataRef = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["useRef"])(data);
      Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_1__["useSelect"])(function (select, registry) {
        if (!dataRef.current) {
          return;
        }

        var _select = select(_constants__WEBPACK_IMPORTED_MODULE_2__["STORE_NAME"]),
            isResolving = _select.isResolving,
            hasFinishedResolution = _select.hasFinishedResolution;

        var _registry$dispatch = registry.dispatch(_constants__WEBPACK_IMPORTED_MODULE_2__["STORE_NAME"]),
            startResolution = _registry$dispatch.startResolution,
            finishResolution = _registry$dispatch.finishResolution,
            updateActivePlugins = _registry$dispatch.updateActivePlugins,
            updateInstalledPlugins = _registry$dispatch.updateInstalledPlugins,
            updateIsJetpackConnected = _registry$dispatch.updateIsJetpackConnected;

        if (!isResolving('getActivePlugins', []) && !hasFinishedResolution('getActivePlugins', [])) {
          startResolution('getActivePlugins', []);
          startResolution('getInstalledPlugins', []);
          startResolution('isJetpackConnected', []);
          updateActivePlugins(dataRef.current.activePlugins, true);
          updateInstalledPlugins(dataRef.current.installedPlugins, true);
          updateIsJetpackConnected(dataRef.current.jetpackStatus && dataRef.current.jetpackStatus.isActive);
          finishResolution('getActivePlugins', []);
          finishResolution('getInstalledPlugins', []);
          finishResolution('isJetpackConnected', []);
        }
      }, []);
      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(OriginalComponent, props);
    };
  };
};

/***/ }),

/***/ "./packages/data/build-module/registry.js":
/*!************************************************!*\
  !*** ./packages/data/build-module/registry.js ***!
  \************************************************/
/*! exports provided: __experimentalResolveSelect */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "__experimentalResolveSelect", function() { return __experimentalResolveSelect; });
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! lodash */ "lodash");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var memize__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! memize */ "./node_modules/memize/index.js");
/* harmony import */ var memize__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(memize__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_2__);
/**
 * External dependencies
 */



function __experimentalResolveSelect(reducerKey) {
  return getResolveSelectors(Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_2__["select"])(reducerKey));
}
/**
 * Returns a promise that resolves once a selector has finished resolving.
 * This is directly pulled from https://github.com/WordPress/gutenberg/blob/909c9274b2440de5f6049ffddfcc8e0e6158df2d/packages/data/src/registry.js#L91-L130
 * and will be removed in favor of the @wordpress/data function.
 */

var getResolveSelectors = memize__WEBPACK_IMPORTED_MODULE_1___default()(function (selectors) {
  return Object(lodash__WEBPACK_IMPORTED_MODULE_0__["mapValues"])(Object(lodash__WEBPACK_IMPORTED_MODULE_0__["omit"])(selectors, ['getIsResolving', 'hasStartedResolution', 'hasFinishedResolution', 'isResolving', 'getCachedResolvers']), function (selector, selectorName) {
    return function () {
      for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
        args[_key] = arguments[_key];
      }

      return new Promise(function (resolve) {
        var hasFinished = function hasFinished() {
          return selectors.hasFinishedResolution(selectorName, args);
        };

        var getResult = function getResult() {
          return selector.apply(null, args);
        }; // trigger the selector (to trigger the resolver)


        var result = getResult();

        if (hasFinished()) {
          return resolve(result);
        }

        var unsubscribe = Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_2__["subscribe"])(function () {
          if (hasFinished()) {
            unsubscribe();
            resolve(getResult());
          }
        });
      });
    };
  });
}, {
  maxSize: 1
});

/***/ }),

/***/ "./packages/data/build-module/settings/action-types.js":
/*!*************************************************************!*\
  !*** ./packages/data/build-module/settings/action-types.js ***!
  \*************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
var TYPES = {
  UPDATE_SETTINGS_FOR_GROUP: 'UPDATE_SETTINGS_FOR_GROUP',
  UPDATE_ERROR_FOR_GROUP: 'UPDATE_ERROR_FOR_GROUP',
  CLEAR_SETTINGS: 'CLEAR_SETTINGS',
  SET_IS_REQUESTING: 'SET_IS_REQUESTING',
  CLEAR_IS_DIRTY: 'CLEAR_IS_DIRTY'
};
/* harmony default export */ __webpack_exports__["default"] = (TYPES);

/***/ }),

/***/ "./packages/data/build-module/settings/actions.js":
/*!********************************************************!*\
  !*** ./packages/data/build-module/settings/actions.js ***!
  \********************************************************/
/*! exports provided: updateSettingsForGroup, updateErrorForGroup, setIsRequesting, clearIsDirty, updateAndPersistSettingsForGroup, persistSettingsForGroup, clearSettings */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "updateSettingsForGroup", function() { return updateSettingsForGroup; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "updateErrorForGroup", function() { return updateErrorForGroup; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "setIsRequesting", function() { return setIsRequesting; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "clearIsDirty", function() { return clearIsDirty; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "updateAndPersistSettingsForGroup", function() { return updateAndPersistSettingsForGroup; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "persistSettingsForGroup", function() { return persistSettingsForGroup; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "clearSettings", function() { return clearSettings; });
/* harmony import */ var _babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/regenerator */ "./node_modules/@babel/runtime/regenerator/index.js");
/* harmony import */ var _babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_data_controls__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/data-controls */ "@wordpress/data-controls");
/* harmony import */ var _wordpress_data_controls__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data_controls__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! lodash */ "lodash");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _constants__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../constants */ "./packages/data/build-module/constants.js");
/* harmony import */ var _constants__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./constants */ "./packages/data/build-module/settings/constants.js");
/* harmony import */ var _action_types__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./action-types */ "./packages/data/build-module/settings/action-types.js");


var _marked = /*#__PURE__*/_babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0___default.a.mark(updateAndPersistSettingsForGroup),
    _marked2 = /*#__PURE__*/_babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0___default.a.mark(persistSettingsForGroup);
/**
 * External dependencies
 */





/**
 * Internal dependencies
 */




function updateSettingsForGroup(group, data) {
  var time = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : new Date();
  return {
    type: _action_types__WEBPACK_IMPORTED_MODULE_6__["default"].UPDATE_SETTINGS_FOR_GROUP,
    group: group,
    data: data,
    time: time
  };
}
function updateErrorForGroup(group, data, error) {
  var time = arguments.length > 3 && arguments[3] !== undefined ? arguments[3] : new Date();
  return {
    type: _action_types__WEBPACK_IMPORTED_MODULE_6__["default"].UPDATE_ERROR_FOR_GROUP,
    group: group,
    data: data,
    error: error,
    time: time
  };
}
function setIsRequesting(group, isRequesting) {
  return {
    type: _action_types__WEBPACK_IMPORTED_MODULE_6__["default"].SET_IS_REQUESTING,
    group: group,
    isRequesting: isRequesting
  };
}
function clearIsDirty(group) {
  return {
    type: _action_types__WEBPACK_IMPORTED_MODULE_6__["default"].CLEAR_IS_DIRTY,
    group: group
  };
} // allows updating and persisting immediately in one action.

function updateAndPersistSettingsForGroup(group, data) {
  return _babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0___default.a.wrap(function updateAndPersistSettingsForGroup$(_context) {
    while (1) {
      switch (_context.prev = _context.next) {
        case 0:
          _context.next = 2;
          return updateSettingsForGroup(group, data);

        case 2:
          return _context.delegateYield(persistSettingsForGroup(group), "t0", 3);

        case 3:
        case "end":
          return _context.stop();
      }
    }
  }, _marked);
} // this would replace setSettingsForGroup

function persistSettingsForGroup(group) {
  var dirtyKeys, dirtyData, url, update, results;
  return _babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0___default.a.wrap(function persistSettingsForGroup$(_context2) {
    while (1) {
      switch (_context2.prev = _context2.next) {
        case 0:
          _context2.next = 2;
          return setIsRequesting(group, true);

        case 2:
          _context2.next = 4;
          return Object(_wordpress_data_controls__WEBPACK_IMPORTED_MODULE_2__["select"])(_constants__WEBPACK_IMPORTED_MODULE_5__["STORE_NAME"], 'getDirtyKeys', group);

        case 4:
          dirtyKeys = _context2.sent;

          if (!(dirtyKeys.length === 0)) {
            _context2.next = 9;
            break;
          }

          _context2.next = 8;
          return setIsRequesting(group, false);

        case 8:
          return _context2.abrupt("return");

        case 9:
          _context2.next = 11;
          return Object(_wordpress_data_controls__WEBPACK_IMPORTED_MODULE_2__["select"])(_constants__WEBPACK_IMPORTED_MODULE_5__["STORE_NAME"], 'getSettingsForGroup', group, dirtyKeys);

        case 11:
          dirtyData = _context2.sent;
          url = "".concat(_constants__WEBPACK_IMPORTED_MODULE_4__["NAMESPACE"], "/settings/").concat(group, "/batch");
          update = dirtyKeys.reduce(function (updates, key) {
            var u = Object.keys(dirtyData[key]).map(function (k) {
              return {
                id: k,
                value: dirtyData[key][k]
              };
            });
            return Object(lodash__WEBPACK_IMPORTED_MODULE_3__["concat"])(updates, u);
          }, []);
          _context2.prev = 14;
          _context2.next = 17;
          return Object(_wordpress_data_controls__WEBPACK_IMPORTED_MODULE_2__["apiFetch"])({
            path: url,
            method: 'POST',
            data: {
              update: update
            }
          });

        case 17:
          results = _context2.sent;
          _context2.next = 20;
          return setIsRequesting(group, false);

        case 20:
          if (results) {
            _context2.next = 22;
            break;
          }

          throw new Error(Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('There was a problem updating your settings.', 'woocommerce'));

        case 22:
          _context2.next = 24;
          return clearIsDirty(group);

        case 24:
          _context2.next = 33;
          break;

        case 26:
          _context2.prev = 26;
          _context2.t0 = _context2["catch"](14);
          _context2.next = 30;
          return updateErrorForGroup(group, null, _context2.t0);

        case 30:
          _context2.next = 32;
          return setIsRequesting(group, false);

        case 32:
          throw _context2.t0;

        case 33:
        case "end":
          return _context2.stop();
      }
    }
  }, _marked2, null, [[14, 26]]);
}
function clearSettings() {
  return {
    type: _action_types__WEBPACK_IMPORTED_MODULE_6__["default"].CLEAR_SETTINGS
  };
}

/***/ }),

/***/ "./packages/data/build-module/settings/constants.js":
/*!**********************************************************!*\
  !*** ./packages/data/build-module/settings/constants.js ***!
  \**********************************************************/
/*! exports provided: STORE_NAME */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "STORE_NAME", function() { return STORE_NAME; });
var STORE_NAME = 'wc/admin/settings';

/***/ }),

/***/ "./packages/data/build-module/settings/index.js":
/*!******************************************************!*\
  !*** ./packages/data/build-module/settings/index.js ***!
  \******************************************************/
/*! exports provided: SETTINGS_STORE_NAME */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "SETTINGS_STORE_NAME", function() { return SETTINGS_STORE_NAME; });
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_data_controls__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/data-controls */ "@wordpress/data-controls");
/* harmony import */ var _wordpress_data_controls__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data_controls__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _constants__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./constants */ "./packages/data/build-module/settings/constants.js");
/* harmony import */ var _selectors__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./selectors */ "./packages/data/build-module/settings/selectors.js");
/* harmony import */ var _actions__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./actions */ "./packages/data/build-module/settings/actions.js");
/* harmony import */ var _resolvers__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./resolvers */ "./packages/data/build-module/settings/resolvers.js");
/* harmony import */ var _reducer__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./reducer */ "./packages/data/build-module/settings/reducer.js");
/**
 * External dependencies
 */


/**
 * Internal dependencies
 */






var storeSelectors = Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_0__["select"])(_constants__WEBPACK_IMPORTED_MODULE_2__["STORE_NAME"]); // @todo This is used to prevent double registration of the store due to webpack chunks.
// The `storeSelectors` condition can be removed once this is fixed.

if (!storeSelectors) {
  Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_0__["registerStore"])(_constants__WEBPACK_IMPORTED_MODULE_2__["STORE_NAME"], {
    reducer: _reducer__WEBPACK_IMPORTED_MODULE_6__["default"],
    actions: _actions__WEBPACK_IMPORTED_MODULE_4__,
    controls: _wordpress_data_controls__WEBPACK_IMPORTED_MODULE_1__["controls"],
    selectors: _selectors__WEBPACK_IMPORTED_MODULE_3__,
    resolvers: _resolvers__WEBPACK_IMPORTED_MODULE_5__
  });
}

var SETTINGS_STORE_NAME = _constants__WEBPACK_IMPORTED_MODULE_2__["STORE_NAME"];

/***/ }),

/***/ "./packages/data/build-module/settings/reducer.js":
/*!********************************************************!*\
  !*** ./packages/data/build-module/settings/reducer.js ***!
  \********************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _babel_runtime_helpers_toConsumableArray__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/toConsumableArray */ "./node_modules/@babel/runtime/helpers/toConsumableArray.js");
/* harmony import */ var _babel_runtime_helpers_toConsumableArray__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_toConsumableArray__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/defineProperty */ "./node_modules/@babel/runtime/helpers/defineProperty.js");
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! lodash */ "lodash");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _action_types__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./action-types */ "./packages/data/build-module/settings/action-types.js");
/* harmony import */ var _utils__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../utils */ "./packages/data/build-module/utils.js");



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
        _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_1___default()(target, key, source[key]);
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



/**
 * Internal dependencies
 */




var updateGroupDataInNewState = function updateGroupDataInNewState(newState, _ref) {
  var group = _ref.group,
      groupIds = _ref.groupIds,
      data = _ref.data,
      time = _ref.time,
      error = _ref.error;
  groupIds.forEach(function (id) {
    newState[Object(_utils__WEBPACK_IMPORTED_MODULE_4__["getResourceName"])(group, id)] = {
      data: data[id],
      lastReceived: time,
      error: error
    };
  });
  return newState;
};

var receiveSettings = function receiveSettings() {
  var state = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};

  var _ref2 = arguments.length > 1 ? arguments[1] : undefined,
      type = _ref2.type,
      group = _ref2.group,
      data = _ref2.data,
      error = _ref2.error,
      time = _ref2.time,
      isRequesting = _ref2.isRequesting;

  var newState = {};

  switch (type) {
    case _action_types__WEBPACK_IMPORTED_MODULE_3__["default"].SET_IS_REQUESTING:
      state = _objectSpread(_objectSpread({}, state), {}, _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_1___default()({}, group, _objectSpread(_objectSpread({}, state[group]), {}, {
        isRequesting: isRequesting
      })));
      break;

    case _action_types__WEBPACK_IMPORTED_MODULE_3__["default"].CLEAR_IS_DIRTY:
      state = _objectSpread(_objectSpread({}, state), {}, _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_1___default()({}, group, _objectSpread(_objectSpread({}, state[group]), {}, {
        dirty: []
      })));
      break;

    case _action_types__WEBPACK_IMPORTED_MODULE_3__["default"].UPDATE_SETTINGS_FOR_GROUP:
    case _action_types__WEBPACK_IMPORTED_MODULE_3__["default"].UPDATE_ERROR_FOR_GROUP:
      var groupIds = data ? Object.keys(data) : [];

      if (data === null) {
        state = _objectSpread(_objectSpread({}, state), {}, _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_1___default()({}, group, {
          data: state[group] ? state[group].data : [],
          error: error,
          lastReceived: time
        }));
      } else {
        state = _objectSpread(_objectSpread({}, state), {}, _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_1___default()({}, group, {
          data: state[group] && state[group].data ? [].concat(_babel_runtime_helpers_toConsumableArray__WEBPACK_IMPORTED_MODULE_0___default()(state[group].data), _babel_runtime_helpers_toConsumableArray__WEBPACK_IMPORTED_MODULE_0___default()(groupIds)) : groupIds,
          error: error,
          lastReceived: time,
          isRequesting: false,
          dirty: state[group] && state[group].dirty ? Object(lodash__WEBPACK_IMPORTED_MODULE_2__["union"])(state[group].dirty, groupIds) : groupIds
        }), updateGroupDataInNewState(newState, {
          group: group,
          groupIds: groupIds,
          data: data,
          time: time,
          error: error
        }));
      }

      break;

    case _action_types__WEBPACK_IMPORTED_MODULE_3__["default"].CLEAR_SETTINGS:
      state = {};
  }

  return state;
};

/* harmony default export */ __webpack_exports__["default"] = (receiveSettings);

/***/ }),

/***/ "./packages/data/build-module/settings/resolvers.js":
/*!**********************************************************!*\
  !*** ./packages/data/build-module/settings/resolvers.js ***!
  \**********************************************************/
/*! exports provided: getSettings, getSettingsForGroup */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "getSettings", function() { return getSettings; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "getSettingsForGroup", function() { return getSettingsForGroup; });
/* harmony import */ var _babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/regenerator */ "./node_modules/@babel/runtime/regenerator/index.js");
/* harmony import */ var _babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/defineProperty */ "./node_modules/@babel/runtime/helpers/defineProperty.js");
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_data_controls__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/data-controls */ "@wordpress/data-controls");
/* harmony import */ var _wordpress_data_controls__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data_controls__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _constants__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../constants */ "./packages/data/build-module/constants.js");
/* harmony import */ var _constants__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./constants */ "./packages/data/build-module/settings/constants.js");
/* harmony import */ var _actions__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./actions */ "./packages/data/build-module/settings/actions.js");



var _marked = /*#__PURE__*/_babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0___default.a.mark(getSettings),
    _marked2 = /*#__PURE__*/_babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0___default.a.mark(getSettingsForGroup);
/**
 * External dependencies
 */



/**
 * Internal dependencies
 */





function settingsToSettingsResource(settings) {
  return settings.reduce(function (resource, setting) {
    resource[setting.id] = setting.value;
    return resource;
  }, {});
}

function getSettings(group) {
  var url, results, resource;
  return _babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0___default.a.wrap(function getSettings$(_context) {
    while (1) {
      switch (_context.prev = _context.next) {
        case 0:
          _context.next = 2;
          return Object(_wordpress_data_controls__WEBPACK_IMPORTED_MODULE_2__["dispatch"])(_constants__WEBPACK_IMPORTED_MODULE_4__["STORE_NAME"], 'setIsRequesting', group, true);

        case 2:
          _context.prev = 2;
          url = _constants__WEBPACK_IMPORTED_MODULE_3__["NAMESPACE"] + '/settings/' + group;
          _context.next = 6;
          return Object(_wordpress_data_controls__WEBPACK_IMPORTED_MODULE_2__["apiFetch"])({
            path: url,
            method: 'GET'
          });

        case 6:
          results = _context.sent;
          resource = settingsToSettingsResource(results);
          return _context.abrupt("return", Object(_actions__WEBPACK_IMPORTED_MODULE_5__["updateSettingsForGroup"])(group, _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_1___default()({}, group, resource)));

        case 11:
          _context.prev = 11;
          _context.t0 = _context["catch"](2);
          return _context.abrupt("return", Object(_actions__WEBPACK_IMPORTED_MODULE_5__["updateErrorForGroup"])(group, null, _context.t0.message));

        case 14:
        case "end":
          return _context.stop();
      }
    }
  }, _marked, null, [[2, 11]]);
}
function getSettingsForGroup(group) {
  return _babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0___default.a.wrap(function getSettingsForGroup$(_context2) {
    while (1) {
      switch (_context2.prev = _context2.next) {
        case 0:
          return _context2.abrupt("return", getSettings(group));

        case 1:
        case "end":
          return _context2.stop();
      }
    }
  }, _marked2);
}

/***/ }),

/***/ "./packages/data/build-module/settings/selectors.js":
/*!**********************************************************!*\
  !*** ./packages/data/build-module/settings/selectors.js ***!
  \**********************************************************/
/*! exports provided: getSettingsGroupNames, getSettings, getDirtyKeys, getIsDirty, getSettingsForGroup, isUpdateSettingsRequesting, getSetting, getLastSettingsErrorForGroup, getSettingsError */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "getSettingsGroupNames", function() { return getSettingsGroupNames; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "getSettings", function() { return getSettings; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "getDirtyKeys", function() { return getDirtyKeys; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "getIsDirty", function() { return getIsDirty; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "getSettingsForGroup", function() { return getSettingsForGroup; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "isUpdateSettingsRequesting", function() { return isUpdateSettingsRequesting; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "getSetting", function() { return getSetting; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "getLastSettingsErrorForGroup", function() { return getLastSettingsErrorForGroup; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "getSettingsError", function() { return getSettingsError; });
/* harmony import */ var _babel_runtime_helpers_toConsumableArray__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/toConsumableArray */ "./node_modules/@babel/runtime/helpers/toConsumableArray.js");
/* harmony import */ var _babel_runtime_helpers_toConsumableArray__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_toConsumableArray__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _utils__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../utils */ "./packages/data/build-module/utils.js");

/**
 * Internal dependencies
 */


var getSettingsGroupNames = function getSettingsGroupNames(state) {
  var groupNames = new Set(Object.keys(state).map(function (resourceName) {
    return Object(_utils__WEBPACK_IMPORTED_MODULE_1__["getResourcePrefix"])(resourceName);
  }));
  return _babel_runtime_helpers_toConsumableArray__WEBPACK_IMPORTED_MODULE_0___default()(groupNames);
};
var getSettings = function getSettings(state, group) {
  var settings = {};
  var settingIds = state[group] && state[group].data || [];

  if (settingIds.length === 0) {
    return settings;
  }

  settingIds.forEach(function (id) {
    settings[id] = state[Object(_utils__WEBPACK_IMPORTED_MODULE_1__["getResourceName"])(group, id)].data;
  });
  return settings;
};
var getDirtyKeys = function getDirtyKeys(state, group) {
  return state[group].dirty || [];
};
var getIsDirty = function getIsDirty(state, group) {
  var keys = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : [];
  var dirtyMap = getDirtyKeys(state, group); // if empty array bail

  if (dirtyMap.length === 0) {
    return false;
  } // if at least one of the keys is in the dirty map then the state is dirty
  // meaning it hasn't been persisted.


  return keys.some(function (key) {
    return dirtyMap.includes(key);
  });
};
var getSettingsForGroup = function getSettingsForGroup(state, group, keys) {
  var allSettings = getSettings(state, group);
  return keys.reduce(function (accumulator, key) {
    accumulator[key] = allSettings[key] || {};
    return accumulator;
  }, {});
};
var isUpdateSettingsRequesting = function isUpdateSettingsRequesting(state, group) {
  return state[group] && Boolean(state[group].isRequesting);
};
/**
 * Retrieves a setting value from the setting store.
 *
 * @export
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
  var fallback = arguments.length > 3 && arguments[3] !== undefined ? arguments[3] : false;
  var filter = arguments.length > 4 && arguments[4] !== undefined ? arguments[4] : function (val) {
    return val;
  };
  var resourceName = Object(_utils__WEBPACK_IMPORTED_MODULE_1__["getResourceName"])(group, name);
  var value = state[resourceName] && state[resourceName].data || fallback;
  return filter(value, fallback);
}
var getLastSettingsErrorForGroup = function getLastSettingsErrorForGroup(state, group) {
  var settingsIds = state[group].data;

  if (settingsIds.length === 0) {
    return state[group].error;
  }

  return _babel_runtime_helpers_toConsumableArray__WEBPACK_IMPORTED_MODULE_0___default()(settingsIds).pop().error;
};
var getSettingsError = function getSettingsError(state, group, id) {
  if (!id) {
    return state[group] && state[group].error || false;
  }

  return state[Object(_utils__WEBPACK_IMPORTED_MODULE_1__["getResourceName"])(group, id)].error || false;
};

/***/ }),

/***/ "./packages/data/build-module/settings/use-settings.js":
/*!*************************************************************!*\
  !*** ./packages/data/build-module/settings/use-settings.js ***!
  \*************************************************************/
/*! exports provided: useSettings */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "useSettings", function() { return useSettings; });
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/defineProperty */ "./node_modules/@babel/runtime/helpers/defineProperty.js");
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _constants__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./constants */ "./packages/data/build-module/settings/constants.js");


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




/**
 * Internal dependencies
 */


var useSettings = function useSettings(group) {
  var settingsKeys = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : [];

  var _useSelect = Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_1__["useSelect"])(function (select) {
    var _select = select(_constants__WEBPACK_IMPORTED_MODULE_3__["STORE_NAME"]),
        getLastSettingsErrorForGroup = _select.getLastSettingsErrorForGroup,
        getSettingsForGroup = _select.getSettingsForGroup,
        getIsDirty = _select.getIsDirty,
        isUpdateSettingsRequesting = _select.isUpdateSettingsRequesting;

    return {
      requestedSettings: getSettingsForGroup(group, settingsKeys),
      settingsError: Boolean(getLastSettingsErrorForGroup(group)),
      isRequesting: isUpdateSettingsRequesting(group),
      isDirty: getIsDirty(group, settingsKeys)
    };
  }, [group, settingsKeys]),
      requestedSettings = _useSelect.requestedSettings,
      settingsError = _useSelect.settingsError,
      isRequesting = _useSelect.isRequesting,
      isDirty = _useSelect.isDirty;

  var _useDispatch = Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_1__["useDispatch"])(_constants__WEBPACK_IMPORTED_MODULE_3__["STORE_NAME"]),
      persistSettingsForGroup = _useDispatch.persistSettingsForGroup,
      updateAndPersistSettingsForGroup = _useDispatch.updateAndPersistSettingsForGroup,
      updateSettingsForGroup = _useDispatch.updateSettingsForGroup;

  var updateSettings = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__["useCallback"])(function (name, data) {
    updateSettingsForGroup(group, _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0___default()({}, name, data));
  }, [group]);
  var persistSettings = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__["useCallback"])(function () {
    // this action would simply persist all settings marked as dirty in the
    // store state and then remove the dirty record in the isDirtyMap
    persistSettingsForGroup(group);
  }, [group]);
  var updateAndPersistSettings = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__["useCallback"])(function (name, data) {
    updateAndPersistSettingsForGroup(group, _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0___default()({}, name, data));
  }, [group]);
  return _objectSpread(_objectSpread({
    settingsError: settingsError,
    isRequesting: isRequesting,
    isDirty: isDirty
  }, requestedSettings), {}, {
    persistSettings: persistSettings,
    updateAndPersistSettings: updateAndPersistSettings,
    updateSettings: updateSettings
  });
};

/***/ }),

/***/ "./packages/data/build-module/settings/with-settings-hydration.js":
/*!************************************************************************!*\
  !*** ./packages/data/build-module/settings/with-settings-hydration.js ***!
  \************************************************************************/
/*! exports provided: withSettingsHydration */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "withSettingsHydration", function() { return withSettingsHydration; });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _constants__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./constants */ "./packages/data/build-module/settings/constants.js");

/**
 * External dependencies
 */



/**
 * Internal dependencies
 */


var withSettingsHydration = function withSettingsHydration(group, settings) {
  return function (OriginalComponent) {
    return function (props) {
      var settingsRef = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["useRef"])(settings);
      Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_1__["useSelect"])(function (select, registry) {
        if (!settingsRef.current) {
          return;
        }

        var _select = select(_constants__WEBPACK_IMPORTED_MODULE_2__["STORE_NAME"]),
            isResolving = _select.isResolving,
            hasFinishedResolution = _select.hasFinishedResolution;

        var _registry$dispatch = registry.dispatch(_constants__WEBPACK_IMPORTED_MODULE_2__["STORE_NAME"]),
            startResolution = _registry$dispatch.startResolution,
            finishResolution = _registry$dispatch.finishResolution,
            updateSettingsForGroup = _registry$dispatch.updateSettingsForGroup,
            clearIsDirty = _registry$dispatch.clearIsDirty;

        if (!isResolving('getSettings', [group]) && !hasFinishedResolution('getSettings', [group])) {
          startResolution('getSettings', [group]);
          updateSettingsForGroup(group, settingsRef.current);
          clearIsDirty(group);
          finishResolution('getSettings', [group]);
        }
      }, []);
      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(OriginalComponent, props);
    };
  };
};

/***/ }),

/***/ "./packages/data/build-module/user-preferences/constants.js":
/*!******************************************************************!*\
  !*** ./packages/data/build-module/user-preferences/constants.js ***!
  \******************************************************************/
/*! exports provided: STORE_NAME */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "STORE_NAME", function() { return STORE_NAME; });
var STORE_NAME = 'core';

/***/ }),

/***/ "./packages/data/build-module/user-preferences/index.js":
/*!**************************************************************!*\
  !*** ./packages/data/build-module/user-preferences/index.js ***!
  \**************************************************************/
/*! exports provided: USER_STORE_NAME */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "USER_STORE_NAME", function() { return USER_STORE_NAME; });
/* harmony import */ var _constants__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./constants */ "./packages/data/build-module/user-preferences/constants.js");
/**
 * Internal dependencies
 */

var USER_STORE_NAME = _constants__WEBPACK_IMPORTED_MODULE_0__["STORE_NAME"];

/***/ }),

/***/ "./packages/data/build-module/user-preferences/use-user-preferences.js":
/*!*****************************************************************************!*\
  !*** ./packages/data/build-module/user-preferences/use-user-preferences.js ***!
  \*****************************************************************************/
/*! exports provided: useUserPreferences */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "useUserPreferences", function() { return useUserPreferences; });
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/defineProperty */ "./node_modules/@babel/runtime/helpers/defineProperty.js");
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/regenerator */ "./node_modules/@babel/runtime/regenerator/index.js");
/* harmony import */ var _babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _babel_runtime_helpers_asyncToGenerator__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @babel/runtime/helpers/asyncToGenerator */ "./node_modules/@babel/runtime/helpers/asyncToGenerator.js");
/* harmony import */ var _babel_runtime_helpers_asyncToGenerator__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_asyncToGenerator__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! lodash */ "lodash");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _constants__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./constants */ "./packages/data/build-module/user-preferences/constants.js");




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




/**
 * Internal dependencies
 */


/**
 * Retrieve and decode the user's WooCommerce meta values.
 *
 * @param {Object} user WP User object.
 * @return {Object} User's WooCommerce preferences.
 */

var getWooCommerceMeta = function getWooCommerceMeta(user) {
  var wooMeta = user.woocommerce_meta || {};
  var userData = Object(lodash__WEBPACK_IMPORTED_MODULE_3__["mapValues"])(wooMeta, function (data) {
    if (!data || data.length === 0) {
      return '';
    }

    return JSON.parse(data);
  });
  return userData;
};
/**
 * Custom react hook for retrieving thecurrent user's WooCommerce preferences.
 *
 * This is a wrapper around @wordpress/core-data's getCurrentUser() and saveUser().
 */


var useUserPreferences = function useUserPreferences() {
  // Get our dispatch methods now - this can't happen inside the callback below.
  var dispatch = Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_4__["useDispatch"])(_constants__WEBPACK_IMPORTED_MODULE_5__["STORE_NAME"]);
  var addEntities = dispatch.addEntities,
      receiveCurrentUser = dispatch.receiveCurrentUser,
      saveEntityRecord = dispatch.saveEntityRecord;
  var saveUser = dispatch.saveUser;

  var _useSelect = Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_4__["useSelect"])(function (select) {
    var _select = select(_constants__WEBPACK_IMPORTED_MODULE_5__["STORE_NAME"]),
        getCurrentUser = _select.getCurrentUser,
        getEntity = _select.getEntity,
        getEntityRecord = _select.getEntityRecord,
        getLastEntitySaveError = _select.getLastEntitySaveError,
        hasStartedResolution = _select.hasStartedResolution,
        hasFinishedResolution = _select.hasFinishedResolution; // WP 5.3.x doesn't have the User entity defined.


    if (typeof saveUser !== 'function') {
      // Polyfill saveUser() - wrapper of saveEntityRecord.
      saveUser = /*#__PURE__*/function () {
        var _ref = _babel_runtime_helpers_asyncToGenerator__WEBPACK_IMPORTED_MODULE_2___default()( /*#__PURE__*/_babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_1___default.a.mark(function _callee(userToSave) {
          var entityDefined;
          return _babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_1___default.a.wrap(function _callee$(_context) {
            while (1) {
              switch (_context.prev = _context.next) {
                case 0:
                  entityDefined = Boolean(getEntity('root', 'user'));

                  if (entityDefined) {
                    _context.next = 4;
                    break;
                  }

                  _context.next = 4;
                  return addEntities([{
                    name: 'user',
                    kind: 'root',
                    baseURL: '/wp/v2/users',
                    plural: 'users'
                  }]);

                case 4:
                  _context.next = 6;
                  return saveEntityRecord('root', 'user', userToSave);

                case 6:
                  return _context.abrupt("return", getEntityRecord('root', 'user', userToSave.id));

                case 7:
                case "end":
                  return _context.stop();
              }
            }
          }, _callee);
        }));

        return function saveUser(_x) {
          return _ref.apply(this, arguments);
        };
      }();
    } // Use getCurrentUser() to get WooCommerce meta values.


    var user = getCurrentUser();
    var userData = getWooCommerceMeta(user); // Create wrapper for updating user's `woocommerce_meta`.

    var updateUserPrefs = /*#__PURE__*/function () {
      var _ref2 = _babel_runtime_helpers_asyncToGenerator__WEBPACK_IMPORTED_MODULE_2___default()( /*#__PURE__*/_babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_1___default.a.mark(function _callee2(userPrefs) {
        var userDataFields, metaData, updatedUser, error, updatedUserResponse;
        return _babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_1___default.a.wrap(function _callee2$(_context2) {
          while (1) {
            switch (_context2.prev = _context2.next) {
              case 0:
                // @todo Handle unresolved getCurrentUser() here.
                // Whitelist our meta fields.
                userDataFields = ['categories_report_columns', 'coupons_report_columns', 'customers_report_columns', 'orders_report_columns', 'products_report_columns', 'revenue_report_columns', 'taxes_report_columns', 'variations_report_columns', 'dashboard_sections', 'dashboard_chart_type', 'dashboard_chart_interval', 'dashboard_leaderboard_rows', 'activity_panel_inbox_last_read', 'homepage_stats']; // Prep valid fields for update.

                metaData = Object(lodash__WEBPACK_IMPORTED_MODULE_3__["mapValues"])(Object(lodash__WEBPACK_IMPORTED_MODULE_3__["pick"])(userPrefs, userDataFields), JSON.stringify);

                if (!(Object.keys(metaData).length === 0)) {
                  _context2.next = 4;
                  break;
                }

                return _context2.abrupt("return", {
                  error: new Error('No valid woocommerce_meta keys were provided for update.'),
                  updatedUser: undefined
                });

              case 4:
                // Optimistically propagate new woocommerce_meta to the store for instant update.
                receiveCurrentUser(_objectSpread(_objectSpread({}, user), {}, {
                  woocommerce_meta: _objectSpread(_objectSpread({}, user.woocommerce_meta), metaData)
                })); // Use saveUser() to update WooCommerce meta values.

                _context2.next = 7;
                return saveUser({
                  id: user.id,
                  woocommerce_meta: metaData
                });

              case 7:
                updatedUser = _context2.sent;

                if (!(undefined === updatedUser)) {
                  _context2.next = 11;
                  break;
                } // Return the encountered error to the caller.


                error = getLastEntitySaveError('root', 'user', user.id);
                return _context2.abrupt("return", {
                  error: error,
                  updatedUser: updatedUser
                });

              case 11:
                // Decode the WooCommerce meta after save.
                updatedUserResponse = _objectSpread(_objectSpread({}, updatedUser), {}, {
                  woocommerce_meta: getWooCommerceMeta(updatedUser)
                });
                return _context2.abrupt("return", {
                  updatedUser: updatedUserResponse
                });

              case 13:
              case "end":
                return _context2.stop();
            }
          }
        }, _callee2);
      }));

      return function updateUserPrefs(_x2) {
        return _ref2.apply(this, arguments);
      };
    }();

    return {
      isRequesting: hasStartedResolution('getCurrentUser') && !hasFinishedResolution('getCurrentUser'),
      userPreferences: userData,
      updateUserPreferences: updateUserPrefs
    };
  }),
      isRequesting = _useSelect.isRequesting,
      userPreferences = _useSelect.userPreferences,
      updateUserPreferences = _useSelect.updateUserPreferences;

  return _objectSpread(_objectSpread({
    isRequesting: isRequesting
  }, userPreferences), {}, {
    updateUserPreferences: updateUserPreferences
  });
};

/***/ }),

/***/ "./packages/data/build-module/user-preferences/with-current-user-hydration.js":
/*!************************************************************************************!*\
  !*** ./packages/data/build-module/user-preferences/with-current-user-hydration.js ***!
  \************************************************************************************/
/*! exports provided: withCurrentUserHydration */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "withCurrentUserHydration", function() { return withCurrentUserHydration; });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _constants__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./constants */ "./packages/data/build-module/user-preferences/constants.js");

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

var withCurrentUserHydration = function withCurrentUserHydration(currentUser) {
  return function (OriginalComponent) {
    return function (props) {
      var userRef = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["useRef"])(currentUser); // Use currentUser to hydrate calls to @wordpress/core-data's getCurrentUser().

      Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_1__["useSelect"])(function (select, registry) {
        if (!userRef.current) {
          return;
        }

        var _select = select(_constants__WEBPACK_IMPORTED_MODULE_2__["STORE_NAME"]),
            isResolving = _select.isResolving,
            hasFinishedResolution = _select.hasFinishedResolution;

        var _registry$dispatch = registry.dispatch(_constants__WEBPACK_IMPORTED_MODULE_2__["STORE_NAME"]),
            startResolution = _registry$dispatch.startResolution,
            finishResolution = _registry$dispatch.finishResolution,
            receiveCurrentUser = _registry$dispatch.receiveCurrentUser;

        if (!isResolving('getCurrentUser') && !hasFinishedResolution('getCurrentUser')) {
          startResolution('getCurrentUser', []);
          receiveCurrentUser(userRef.current);
          finishResolution('getCurrentUser', []);
        }
      });
      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(OriginalComponent, props);
    };
  };
};

/***/ }),

/***/ "./packages/data/build-module/utils.js":
/*!*********************************************!*\
  !*** ./packages/data/build-module/utils.js ***!
  \*********************************************/
/*! exports provided: getResourceName, getResourcePrefix, isResourcePrefix, getResourceIdentifier */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "getResourceName", function() { return getResourceName; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "getResourcePrefix", function() { return getResourcePrefix; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "isResourcePrefix", function() { return isResourcePrefix; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "getResourceIdentifier", function() { return getResourceIdentifier; });
function getResourceName(prefix, identifier) {
  var identifierString = JSON.stringify(identifier, Object.keys(identifier).sort());
  return "".concat(prefix, ":").concat(identifierString);
}
function getResourcePrefix(resourceName) {
  var hasPrefixIndex = resourceName.indexOf(':');
  return hasPrefixIndex < 0 ? resourceName : resourceName.substring(0, hasPrefixIndex);
}
function isResourcePrefix(resourceName, prefix) {
  var resourcePrefix = getResourcePrefix(resourceName);
  return resourcePrefix === prefix;
}
function getResourceIdentifier(resourceName) {
  var identifierString = resourceName.substring(resourceName.indexOf(':') + 1);
  return JSON.parse(identifierString);
}

/***/ }),

/***/ "@wordpress/api-fetch":
/*!*******************************************!*\
  !*** external {"this":["wp","apiFetch"]} ***!
  \*******************************************/
/*! no static exports found */
/***/ (function(module, exports) {

(function() { module.exports = this["wp"]["apiFetch"]; }());

/***/ }),

/***/ "@wordpress/data":
/*!***************************************!*\
  !*** external {"this":["wp","data"]} ***!
  \***************************************/
/*! no static exports found */
/***/ (function(module, exports) {

(function() { module.exports = this["wp"]["data"]; }());

/***/ }),

/***/ "@wordpress/data-controls":
/*!***********************************************!*\
  !*** external {"this":["wp","dataControls"]} ***!
  \***********************************************/
/*! no static exports found */
/***/ (function(module, exports) {

(function() { module.exports = this["wp"]["dataControls"]; }());

/***/ }),

/***/ "@wordpress/element":
/*!******************************************!*\
  !*** external {"this":["wp","element"]} ***!
  \******************************************/
/*! no static exports found */
/***/ (function(module, exports) {

(function() { module.exports = this["wp"]["element"]; }());

/***/ }),

/***/ "@wordpress/i18n":
/*!***************************************!*\
  !*** external {"this":["wp","i18n"]} ***!
  \***************************************/
/*! no static exports found */
/***/ (function(module, exports) {

(function() { module.exports = this["wp"]["i18n"]; }());

/***/ }),

/***/ "@wordpress/url":
/*!**************************************!*\
  !*** external {"this":["wp","url"]} ***!
  \**************************************/
/*! no static exports found */
/***/ (function(module, exports) {

(function() { module.exports = this["wp"]["url"]; }());

/***/ }),

/***/ "lodash":
/*!*************************!*\
  !*** external "lodash" ***!
  \*************************/
/*! no static exports found */
/***/ (function(module, exports) {

(function() { module.exports = this["lodash"]; }());

/***/ })

/******/ });
//# sourceMappingURL=index.min.js.map
