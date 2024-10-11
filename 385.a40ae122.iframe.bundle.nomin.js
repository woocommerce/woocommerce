(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[385],{

/***/ "../../node_modules/.pnpm/@wordpress+compose@5.20.0_react@17.0.2/node_modules/@wordpress/compose/build-module/higher-order/compose.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _pipe__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@5.20.0_react@17.0.2/node_modules/@wordpress/compose/build-module/higher-order/pipe.js");
/**
 * Internal dependencies
 */

/**
 * Composes multiple higher-order components into a single higher-order component. Performs right-to-left function
 * composition, where each successive invocation is supplied the return value of the previous.
 *
 * This is inspired by `lodash`'s `flowRight` function.
 *
 * @see https://docs-lodash.com/v4/flow-right/
 */

const compose = (0,_pipe__WEBPACK_IMPORTED_MODULE_0__/* .basePipe */ .A)(true);
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (compose);
//# sourceMappingURL=compose.js.map

/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+compose@5.20.0_react@17.0.2/node_modules/@wordpress/compose/build-module/higher-order/pipe.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (/* binding */ basePipe),
/* harmony export */   h: () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/**
 * Parts of this source were derived and modified from lodash,
 * released under the MIT license.
 *
 * https://github.com/lodash/lodash
 *
 * Copyright JS Foundation and other contributors <https://js.foundation/>
 *
 * Based on Underscore.js, copyright Jeremy Ashkenas,
 * DocumentCloud and Investigative Reporters & Editors <http://underscorejs.org/>
 *
 * This software consists of voluntary contributions made by many
 * individuals. For exact contribution history, see the revision history
 * available at https://github.com/lodash/lodash
 *
 * The following license applies to all parts of this software except as
 * documented below:
 *
 * ====
 *
 * Permission is hereby granted, free of charge, to any person obtaining
 * a copy of this software and associated documentation files (the
 * "Software"), to deal in the Software without restriction, including
 * without limitation the rights to use, copy, modify, merge, publish,
 * distribute, sublicense, and/or sell copies of the Software, and to
 * permit persons to whom the Software is furnished to do so, subject to
 * the following conditions:
 *
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
 * LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
 * OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

/**
 * Creates a pipe function.
 *
 * Allows to choose whether to perform left-to-right or right-to-left composition.
 *
 * @see https://docs-lodash.com/v4/flow/
 *
 * @param {boolean} reverse True if right-to-left, false for left-to-right composition.
 */
const basePipe = function () {
  let reverse = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : false;
  return function () {
    for (var _len = arguments.length, funcs = new Array(_len), _key = 0; _key < _len; _key++) {
      funcs[_key] = arguments[_key];
    }

    return function () {
      const functions = funcs.flat();

      if (reverse) {
        functions.reverse();
      }

      for (var _len2 = arguments.length, args = new Array(_len2), _key2 = 0; _key2 < _len2; _key2++) {
        args[_key2] = arguments[_key2];
      }

      return functions.reduce((prev, func) => [func(...prev)], args)[0];
    };
  };
};
/**
 * Composes multiple higher-order components into a single higher-order component. Performs left-to-right function
 * composition, where each successive invocation is supplied the return value of the previous.
 *
 * This is inspired by `lodash`'s `flow` function.
 *
 * @see https://docs-lodash.com/v4/flow/
 */


const pipe = basePipe();

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (pipe);
//# sourceMappingURL=pipe.js.map

/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+compose@5.20.0_react@17.0.2/node_modules/@wordpress/compose/build-module/higher-order/pure/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var _wordpress_is_shallow_equal__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+is-shallow-equal@4.47.0/node_modules/@wordpress/is-shallow-equal/build-module/index.js");
/* harmony import */ var _utils_create_higher_order_component__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@5.20.0_react@17.0.2/node_modules/@wordpress/compose/build-module/utils/create-higher-order-component/index.js");


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
 * Given a component returns the enhanced component augmented with a component
 * only re-rendering when its props/state change
 */

const pure = (0,_utils_create_higher_order_component__WEBPACK_IMPORTED_MODULE_0__/* .createHigherOrderComponent */ .f)(function (WrappedComponent) {
  if (WrappedComponent.prototype instanceof _wordpress_element__WEBPACK_IMPORTED_MODULE_1__.Component) {
    return class extends WrappedComponent {
      shouldComponentUpdate(nextProps, nextState) {
        return !(0,_wordpress_is_shallow_equal__WEBPACK_IMPORTED_MODULE_2__/* ["default"] */ .Ay)(nextProps, this.props) || !(0,_wordpress_is_shallow_equal__WEBPACK_IMPORTED_MODULE_2__/* ["default"] */ .Ay)(nextState, this.state);
      }

    };
  }

  return class extends _wordpress_element__WEBPACK_IMPORTED_MODULE_1__.Component {
    shouldComponentUpdate(nextProps) {
      return !(0,_wordpress_is_shallow_equal__WEBPACK_IMPORTED_MODULE_2__/* ["default"] */ .Ay)(nextProps, this.props);
    }

    render() {
      return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.createElement)(WrappedComponent, this.props);
    }

  };
}, 'pure');
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (pure);
//# sourceMappingURL=index.js.map

/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+compose@5.20.0_react@17.0.2/node_modules/@wordpress/compose/build-module/hooks/use-media-query/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (/* binding */ useMediaQuery)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
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
  const [match, setMatch] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)(() => !!(query && typeof window !== 'undefined' && window.matchMedia(query).matches));
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    if (!query) {
      return;
    }

    const updateMatch = () => setMatch(window.matchMedia(query).matches);

    updateMatch();
    const list = window.matchMedia(query);
    list.addListener(updateMatch);
    return () => {
      list.removeListener(updateMatch);
    };
  }, [query]);
  return !!query && match;
}
//# sourceMappingURL=index.js.map

/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+compose@5.20.0_react@17.0.2/node_modules/@wordpress/compose/build-module/hooks/use-viewport-match/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var _use_media_query__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@5.20.0_react@17.0.2/node_modules/@wordpress/compose/build-module/hooks/use-media-query/index.js");
/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */


/**
 * @typedef {"huge" | "wide" | "large" | "medium" | "small" | "mobile"} WPBreakpoint
 */

/**
 * Hash of breakpoint names with pixel width at which it becomes effective.
 *
 * @see _breakpoints.scss
 *
 * @type {Record<WPBreakpoint, number>}
 */

const BREAKPOINTS = {
  huge: 1440,
  wide: 1280,
  large: 960,
  medium: 782,
  small: 600,
  mobile: 480
};
/**
 * @typedef {">=" | "<"} WPViewportOperator
 */

/**
 * Object mapping media query operators to the condition to be used.
 *
 * @type {Record<WPViewportOperator, string>}
 */

const CONDITIONS = {
  '>=': 'min-width',
  '<': 'max-width'
};
/**
 * Object mapping media query operators to a function that given a breakpointValue and a width evaluates if the operator matches the values.
 *
 * @type {Record<WPViewportOperator, (breakpointValue: number, width: number) => boolean>}
 */

const OPERATOR_EVALUATORS = {
  '>=': (breakpointValue, width) => width >= breakpointValue,
  '<': (breakpointValue, width) => width < breakpointValue
};
const ViewportMatchWidthContext = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createContext)(
/** @type {null | number} */
null);
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

const useViewportMatch = function (breakpoint) {
  let operator = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : '>=';
  const simulatedWidth = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useContext)(ViewportMatchWidthContext);
  const mediaQuery = !simulatedWidth && `(${CONDITIONS[operator]}: ${BREAKPOINTS[breakpoint]}px)`;
  const mediaQueryResult = (0,_use_media_query__WEBPACK_IMPORTED_MODULE_1__/* ["default"] */ .A)(mediaQuery || undefined);

  if (simulatedWidth) {
    return OPERATOR_EVALUATORS[operator](BREAKPOINTS[breakpoint], simulatedWidth);
  }

  return mediaQueryResult;
};

useViewportMatch.__experimentalWidthProvider = ViewportMatchWidthContext.Provider;
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (useViewportMatch);
//# sourceMappingURL=index.js.map

/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+compose@5.20.0_react@17.0.2/node_modules/@wordpress/compose/build-module/utils/create-higher-order-component/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";

// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  f: () => (/* binding */ createHigherOrderComponent)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/tslib@2.6.3/node_modules/tslib/tslib.es6.mjs
var tslib_es6 = __webpack_require__("../../node_modules/.pnpm/tslib@2.6.3/node_modules/tslib/tslib.es6.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/no-case@3.0.4/node_modules/no-case/dist.es2015/index.js + 1 modules
var dist_es2015 = __webpack_require__("../../node_modules/.pnpm/no-case@3.0.4/node_modules/no-case/dist.es2015/index.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/pascal-case@3.1.2/node_modules/pascal-case/dist.es2015/index.js


function pascalCaseTransform(input, index) {
    var firstChar = input.charAt(0);
    var lowerChars = input.substr(1).toLowerCase();
    if (index > 0 && firstChar >= "0" && firstChar <= "9") {
        return "_" + firstChar + lowerChars;
    }
    return "" + firstChar.toUpperCase() + lowerChars;
}
function pascalCaseTransformMerge(input) {
    return input.charAt(0).toUpperCase() + input.slice(1).toLowerCase();
}
function pascalCase(input, options) {
    if (options === void 0) { options = {}; }
    return (0,dist_es2015/* noCase */.W)(input, (0,tslib_es6/* __assign */.Cl)({ delimiter: "", transform: pascalCaseTransform }, options));
}
//# sourceMappingURL=index.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+compose@5.20.0_react@17.0.2/node_modules/@wordpress/compose/build-module/utils/create-higher-order-component/index.js
/**
 * External dependencies
 */


/**
 * Given a function mapping a component to an enhanced component and modifier
 * name, returns the enhanced component augmented with a generated displayName.
 *
 * @param  mapComponent Function mapping component to enhanced component.
 * @param  modifierName Seed name from which to generated display name.
 *
 * @return Component class with generated display name assigned.
 */
function createHigherOrderComponent(mapComponent, modifierName) {
  return Inner => {
    const Outer = mapComponent(Inner);
    Outer.displayName = hocName(modifierName, Inner);
    return Outer;
  };
}
/**
 * Returns a displayName for a higher-order component, given a wrapper name.
 *
 * @example
 *     hocName( 'MyMemo', Widget ) === 'MyMemo(Widget)';
 *     hocName( 'MyMemo', <div /> ) === 'MyMemo(Component)';
 *
 * @param  name  Name assigned to higher-order component's wrapper component.
 * @param  Inner Wrapped component inside higher-order component.
 * @return       Wrapped name of higher-order component.
 */

const hocName = (name, Inner) => {
  const inner = Inner.displayName || Inner.name || 'Component';
  const outer = pascalCase(name !== null && name !== void 0 ? name : '');
  return `${outer}(${inner})`;
};
//# sourceMappingURL=index.js.map

/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+compose@5.20.0_react@17.0.2/node_modules/@wordpress/compose/build-module/utils/debounce/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   s: () => (/* binding */ debounce)
/* harmony export */ });
/**
 * Parts of this source were derived and modified from lodash,
 * released under the MIT license.
 *
 * https://github.com/lodash/lodash
 *
 * Copyright JS Foundation and other contributors <https://js.foundation/>
 *
 * Based on Underscore.js, copyright Jeremy Ashkenas,
 * DocumentCloud and Investigative Reporters & Editors <http://underscorejs.org/>
 *
 * This software consists of voluntary contributions made by many
 * individuals. For exact contribution history, see the revision history
 * available at https://github.com/lodash/lodash
 *
 * The following license applies to all parts of this software except as
 * documented below:
 *
 * ====
 *
 * Permission is hereby granted, free of charge, to any person obtaining
 * a copy of this software and associated documentation files (the
 * "Software"), to deal in the Software without restriction, including
 * without limitation the rights to use, copy, modify, merge, publish,
 * distribute, sublicense, and/or sell copies of the Software, and to
 * permit persons to whom the Software is furnished to do so, subject to
 * the following conditions:
 *
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
 * LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
 * OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

/**
 * A simplified and properly typed version of lodash's `debounce`, that
 * always uses timers instead of sometimes using rAF.
 *
 * Creates a debounced function that delays invoking `func` until after `wait`
 * milliseconds have elapsed since the last time the debounced function was
 * invoked. The debounced function comes with a `cancel` method to cancel delayed
 * `func` invocations and a `flush` method to immediately invoke them. Provide
 * `options` to indicate whether `func` should be invoked on the leading and/or
 * trailing edge of the `wait` timeout. The `func` is invoked with the last
 * arguments provided to the debounced function. Subsequent calls to the debounced
 * function return the result of the last `func` invocation.
 *
 * **Note:** If `leading` and `trailing` options are `true`, `func` is
 * invoked on the trailing edge of the timeout only if the debounced function
 * is invoked more than once during the `wait` timeout.
 *
 * If `wait` is `0` and `leading` is `false`, `func` invocation is deferred
 * until the next tick, similar to `setTimeout` with a timeout of `0`.
 *
 * @param {Function}                   func             The function to debounce.
 * @param {number}                     wait             The number of milliseconds to delay.
 * @param {Partial< DebounceOptions >} options          The options object.
 * @param {boolean}                    options.leading  Specify invoking on the leading edge of the timeout.
 * @param {number}                     options.maxWait  The maximum time `func` is allowed to be delayed before it's invoked.
 * @param {boolean}                    options.trailing Specify invoking on the trailing edge of the timeout.
 *
 * @return Returns the new debounced function.
 */
const debounce = (func, wait, options) => {
  let lastArgs;
  let lastThis;
  let maxWait = 0;
  let result;
  let timerId;
  let lastCallTime;
  let lastInvokeTime = 0;
  let leading = false;
  let maxing = false;
  let trailing = true;

  if (options) {
    leading = !!options.leading;
    maxing = 'maxWait' in options;

    if (options.maxWait !== undefined) {
      maxWait = Math.max(options.maxWait, wait);
    }

    trailing = 'trailing' in options ? !!options.trailing : trailing;
  }

  function invokeFunc(time) {
    const args = lastArgs;
    const thisArg = lastThis;
    lastArgs = undefined;
    lastThis = undefined;
    lastInvokeTime = time;
    result = func.apply(thisArg, args);
    return result;
  }

  function startTimer(pendingFunc, waitTime) {
    timerId = setTimeout(pendingFunc, waitTime);
  }

  function cancelTimer() {
    if (timerId !== undefined) {
      clearTimeout(timerId);
    }
  }

  function leadingEdge(time) {
    // Reset any `maxWait` timer.
    lastInvokeTime = time; // Start the timer for the trailing edge.

    startTimer(timerExpired, wait); // Invoke the leading edge.

    return leading ? invokeFunc(time) : result;
  }

  function getTimeSinceLastCall(time) {
    return time - (lastCallTime || 0);
  }

  function remainingWait(time) {
    const timeSinceLastCall = getTimeSinceLastCall(time);
    const timeSinceLastInvoke = time - lastInvokeTime;
    const timeWaiting = wait - timeSinceLastCall;
    return maxing ? Math.min(timeWaiting, maxWait - timeSinceLastInvoke) : timeWaiting;
  }

  function shouldInvoke(time) {
    const timeSinceLastCall = getTimeSinceLastCall(time);
    const timeSinceLastInvoke = time - lastInvokeTime; // Either this is the first call, activity has stopped and we're at the
    // trailing edge, the system time has gone backwards and we're treating
    // it as the trailing edge, or we've hit the `maxWait` limit.

    return lastCallTime === undefined || timeSinceLastCall >= wait || timeSinceLastCall < 0 || maxing && timeSinceLastInvoke >= maxWait;
  }

  function timerExpired() {
    const time = Date.now();

    if (shouldInvoke(time)) {
      return trailingEdge(time);
    } // Restart the timer.


    startTimer(timerExpired, remainingWait(time));
    return undefined;
  }

  function clearTimer() {
    timerId = undefined;
  }

  function trailingEdge(time) {
    clearTimer(); // Only invoke if we have `lastArgs` which means `func` has been
    // debounced at least once.

    if (trailing && lastArgs) {
      return invokeFunc(time);
    }

    lastArgs = lastThis = undefined;
    return result;
  }

  function cancel() {
    cancelTimer();
    lastInvokeTime = 0;
    clearTimer();
    lastArgs = lastCallTime = lastThis = undefined;
  }

  function flush() {
    return pending() ? trailingEdge(Date.now()) : result;
  }

  function pending() {
    return timerId !== undefined;
  }

  function debounced() {
    const time = Date.now();
    const isInvoking = shouldInvoke(time);

    for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
      args[_key] = arguments[_key];
    }

    lastArgs = args;
    lastThis = this;
    lastCallTime = time;

    if (isInvoking) {
      if (!pending()) {
        return leadingEdge(lastCallTime);
      }

      if (maxing) {
        // Handle invocations in a tight loop.
        startTimer(timerExpired, wait);
        return invokeFunc(lastCallTime);
      }
    }

    if (!pending()) {
      startTimer(timerExpired, wait);
    }

    return result;
  }

  debounced.cancel = cancel;
  debounced.flush = flush;
  debounced.pending = pending;
  return debounced;
};
//# sourceMappingURL=index.js.map

/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+data@7.6.0_react@17.0.2/node_modules/@wordpress/data/build-module/default-registry.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _registry__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+data@7.6.0_react@17.0.2/node_modules/@wordpress/data/build-module/registry.js");
/**
 * Internal dependencies
 */

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ((0,_registry__WEBPACK_IMPORTED_MODULE_0__/* .createRegistry */ .I)());
//# sourceMappingURL=default-registry.js.map

/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+data@7.6.0_react@17.0.2/node_modules/@wordpress/data/build-module/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   HY: () => (/* binding */ combineReducers),
/* harmony export */   JD: () => (/* binding */ dispatch),
/* harmony export */   Lt: () => (/* binding */ select),
/* harmony export */   kz: () => (/* binding */ register),
/* harmony export */   ti: () => (/* binding */ registerStore)
/* harmony export */ });
/* unused harmony exports resolveSelect, suspendSelect, subscribe, registerGenericStore, use */
/* harmony import */ var turbo_combine_reducers__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/turbo-combine-reducers@1.0.2/node_modules/turbo-combine-reducers/index.js");
/* harmony import */ var turbo_combine_reducers__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(turbo_combine_reducers__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _default_registry__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+data@7.6.0_react@17.0.2/node_modules/@wordpress/data/build-module/default-registry.js");
/**
 * External dependencies
 */

/**
 * Internal dependencies
 */



/** @typedef {import('./types').StoreDescriptor} StoreDescriptor */












/**
 * Object of available plugins to use with a registry.
 *
 * @see [use](#use)
 *
 * @type {Object}
 */


/**
 * The combineReducers helper function turns an object whose values are different
 * reducing functions into a single reducing function you can pass to registerReducer.
 *
 * @type  {import('./types').combineReducers}
 * @param {Object} reducers An object whose values correspond to different reducing
 *                          functions that need to be combined into one.
 *
 * @example
 * ```js
 * import { combineReducers, createReduxStore, register } from '@wordpress/data';
 *
 * const prices = ( state = {}, action ) => {
 * 	return action.type === 'SET_PRICE' ?
 * 		{
 * 			...state,
 * 			[ action.item ]: action.price,
 * 		} :
 * 		state;
 * };
 *
 * const discountPercent = ( state = 0, action ) => {
 * 	return action.type === 'START_SALE' ?
 * 		action.discountPercent :
 * 		state;
 * };
 *
 * const store = createReduxStore( 'my-shop', {
 * 	reducer: combineReducers( {
 * 		prices,
 * 		discountPercent,
 * 	} ),
 * } );
 * register( store );
 * ```
 *
 * @return {Function} A reducer that invokes every reducer inside the reducers
 *                    object, and constructs a state object with the same shape.
 */

const combineReducers = (turbo_combine_reducers__WEBPACK_IMPORTED_MODULE_0___default());
/**
 * Given a store descriptor, returns an object of the store's selectors.
 * The selector functions are been pre-bound to pass the current state automatically.
 * As a consumer, you need only pass arguments of the selector, if applicable.
 *
 * @param {StoreDescriptor|string} storeNameOrDescriptor The store descriptor. The legacy calling
 *                                                       convention of passing the store name is
 *                                                       also supported.
 *
 * @example
 * ```js
 * import { select } from '@wordpress/data';
 * import { store as myCustomStore } from 'my-custom-store';
 *
 * select( myCustomStore ).getPrice( 'hammer' );
 * ```
 *
 * @return {Object} Object containing the store's selectors.
 */

const select = _default_registry__WEBPACK_IMPORTED_MODULE_1__/* ["default"] */ .A.select;
/**
 * Given a store descriptor, returns an object containing the store's selectors pre-bound to state
 * so that you only need to supply additional arguments, and modified so that they return promises
 * that resolve to their eventual values, after any resolvers have ran.
 *
 * @param {StoreDescriptor|string} storeNameOrDescriptor The store descriptor. The legacy calling
 *                                                       convention of passing the store name is
 *                                                       also supported.
 *
 * @example
 * ```js
 * import { resolveSelect } from '@wordpress/data';
 * import { store as myCustomStore } from 'my-custom-store';
 *
 * resolveSelect( myCustomStore ).getPrice( 'hammer' ).then(console.log)
 * ```
 *
 * @return {Object} Object containing the store's promise-wrapped selectors.
 */

const resolveSelect = _default_registry__WEBPACK_IMPORTED_MODULE_1__/* ["default"] */ .A.resolveSelect;
/**
 * Given a store descriptor, returns an object containing the store's selectors pre-bound to state
 * so that you only need to supply additional arguments, and modified so that they throw promises
 * in case the selector is not resolved yet.
 *
 * @param {StoreDescriptor|string} storeNameOrDescriptor The store descriptor. The legacy calling
 *                                                       convention of passing the store name is
 *                                                       also supported.
 *
 * @return {Object} Object containing the store's suspense-wrapped selectors.
 */

const suspendSelect = _default_registry__WEBPACK_IMPORTED_MODULE_1__/* ["default"] */ .A.suspendSelect;
/**
 * Given a store descriptor, returns an object of the store's action creators.
 * Calling an action creator will cause it to be dispatched, updating the state value accordingly.
 *
 * Note: Action creators returned by the dispatch will return a promise when
 * they are called.
 *
 * @param {StoreDescriptor|string} storeNameOrDescriptor The store descriptor. The legacy calling
 *                                                       convention of passing the store name is
 *                                                       also supported.
 *
 * @example
 * ```js
 * import { dispatch } from '@wordpress/data';
 * import { store as myCustomStore } from 'my-custom-store';
 *
 * dispatch( myCustomStore ).setPrice( 'hammer', 9.75 );
 * ```
 * @return {Object} Object containing the action creators.
 */

const dispatch = _default_registry__WEBPACK_IMPORTED_MODULE_1__/* ["default"] */ .A.dispatch;
/**
 * Given a listener function, the function will be called any time the state value
 * of one of the registered stores has changed. This function returns a `unsubscribe`
 * function used to stop the subscription.
 *
 * @param {Function} listener Callback function.
 *
 * @example
 * ```js
 * import { subscribe } from '@wordpress/data';
 *
 * const unsubscribe = subscribe( () => {
 * 	// You could use this opportunity to test whether the derived result of a
 * 	// selector has subsequently changed as the result of a state update.
 * } );
 *
 * // Later, if necessary...
 * unsubscribe();
 * ```
 */

const subscribe = _default_registry__WEBPACK_IMPORTED_MODULE_1__/* ["default"] */ .A.subscribe;
/**
 * Registers a generic store instance.
 *
 * @deprecated Use `register( storeDescriptor )` instead.
 *
 * @param {string} name  Store registry name.
 * @param {Object} store Store instance (`{ getSelectors, getActions, subscribe }`).
 */

const registerGenericStore = _default_registry__WEBPACK_IMPORTED_MODULE_1__/* ["default"] */ .A.registerGenericStore;
/**
 * Registers a standard `@wordpress/data` store.
 *
 * @deprecated Use `register` instead.
 *
 * @param {string} storeName Unique namespace identifier for the store.
 * @param {Object} options   Store description (reducer, actions, selectors, resolvers).
 *
 * @return {Object} Registered store object.
 */

const registerStore = _default_registry__WEBPACK_IMPORTED_MODULE_1__/* ["default"] */ .A.registerStore;
/**
 * Extends a registry to inherit functionality provided by a given plugin. A
 * plugin is an object with properties aligning to that of a registry, merged
 * to extend the default registry behavior.
 *
 * @param {Object} plugin Plugin object.
 */

const use = _default_registry__WEBPACK_IMPORTED_MODULE_1__/* ["default"] */ .A.use;
/**
 * Registers a standard `@wordpress/data` store descriptor.
 *
 * @example
 * ```js
 * import { createReduxStore, register } from '@wordpress/data';
 *
 * const store = createReduxStore( 'demo', {
 *     reducer: ( state = 'OK' ) => state,
 *     selectors: {
 *         getValue: ( state ) => state,
 *     },
 * } );
 * register( store );
 * ```
 *
 * @param {StoreDescriptor} store Store descriptor.
 */

const register = _default_registry__WEBPACK_IMPORTED_MODULE_1__/* ["default"] */ .A.register;
//# sourceMappingURL=index.js.map

/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+data@7.6.0_react@17.0.2/node_modules/@wordpress/data/build-module/redux-store/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";

// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  A: () => (/* binding */ createReduxStore)
});

// NAMESPACE OBJECT: ../../node_modules/.pnpm/@wordpress+data@7.6.0_react@17.0.2/node_modules/@wordpress/data/build-module/redux-store/metadata/selectors.js
var selectors_namespaceObject = {};
__webpack_require__.r(selectors_namespaceObject);
__webpack_require__.d(selectors_namespaceObject, {
  getCachedResolvers: () => (getCachedResolvers),
  getIsResolving: () => (getIsResolving),
  getResolutionError: () => (getResolutionError),
  getResolutionState: () => (getResolutionState),
  hasFinishedResolution: () => (hasFinishedResolution),
  hasResolutionFailed: () => (hasResolutionFailed),
  hasStartedResolution: () => (hasStartedResolution),
  isResolving: () => (isResolving)
});

// NAMESPACE OBJECT: ../../node_modules/.pnpm/@wordpress+data@7.6.0_react@17.0.2/node_modules/@wordpress/data/build-module/redux-store/metadata/actions.js
var actions_namespaceObject = {};
__webpack_require__.r(actions_namespaceObject);
__webpack_require__.d(actions_namespaceObject, {
  failResolution: () => (failResolution),
  failResolutions: () => (failResolutions),
  finishResolution: () => (finishResolution),
  finishResolutions: () => (finishResolutions),
  invalidateResolution: () => (invalidateResolution),
  invalidateResolutionForStore: () => (invalidateResolutionForStore),
  invalidateResolutionForStoreSelector: () => (invalidateResolutionForStoreSelector),
  startResolution: () => (startResolution),
  startResolutions: () => (startResolutions)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/redux@4.2.1/node_modules/redux/es/redux.js + 5 modules
var redux = __webpack_require__("../../node_modules/.pnpm/redux@4.2.1/node_modules/redux/es/redux.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/lodash@4.17.21/node_modules/lodash/lodash.js
var lodash = __webpack_require__("../../node_modules/.pnpm/lodash@4.17.21/node_modules/lodash/lodash.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/turbo-combine-reducers@1.0.2/node_modules/turbo-combine-reducers/index.js
var turbo_combine_reducers = __webpack_require__("../../node_modules/.pnpm/turbo-combine-reducers@1.0.2/node_modules/turbo-combine-reducers/index.js");
var turbo_combine_reducers_default = /*#__PURE__*/__webpack_require__.n(turbo_combine_reducers);
// EXTERNAL MODULE: ../../node_modules/.pnpm/equivalent-key-map@0.2.2/node_modules/equivalent-key-map/equivalent-key-map.js
var equivalent_key_map = __webpack_require__("../../node_modules/.pnpm/equivalent-key-map@0.2.2/node_modules/equivalent-key-map/equivalent-key-map.js");
var equivalent_key_map_default = /*#__PURE__*/__webpack_require__.n(equivalent_key_map);
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+redux-routine@4.47.0_redux@4.2.1/node_modules/@wordpress/redux-routine/build-module/index.js + 3 modules
var build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+redux-routine@4.47.0_redux@4.2.1/node_modules/@wordpress/redux-routine/build-module/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+compose@5.20.0_react@17.0.2/node_modules/@wordpress/compose/build-module/higher-order/compose.js
var compose = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@5.20.0_react@17.0.2/node_modules/@wordpress/compose/build-module/higher-order/compose.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+data@7.6.0_react@17.0.2/node_modules/@wordpress/data/build-module/factory.js
/**
 * Creates a selector function that takes additional curried argument with the
 * registry `select` function. While a regular selector has signature
 * ```js
 * ( state, ...selectorArgs ) => ( result )
 * ```
 * that allows to select data from the store's `state`, a registry selector
 * has signature:
 * ```js
 * ( select ) => ( state, ...selectorArgs ) => ( result )
 * ```
 * that supports also selecting from other registered stores.
 *
 * @example
 * ```js
 * import { store as coreStore } from '@wordpress/core-data';
 * import { store as editorStore } from '@wordpress/editor';
 *
 * const getCurrentPostId = createRegistrySelector( ( select ) => ( state ) => {
 *   return select( editorStore ).getCurrentPostId();
 * } );
 *
 * const getPostEdits = createRegistrySelector( ( select ) => ( state ) => {
 *   // calling another registry selector just like any other function
 *   const postType = getCurrentPostType( state );
 *   const postId = getCurrentPostId( state );
 *	 return select( coreStore ).getEntityRecordEdits( 'postType', postType, postId );
 * } );
 * ```
 *
 * Note how the `getCurrentPostId` selector can be called just like any other function,
 * (it works even inside a regular non-registry selector) and we don't need to pass the
 * registry as argument. The registry binding happens automatically when registering the selector
 * with a store.
 *
 * @param {Function} registrySelector Function receiving a registry `select`
 *                                    function and returning a state selector.
 *
 * @return {Function} Registry selector that can be registered with a store.
 */
function createRegistrySelector(registrySelector) {
  // Create a selector function that is bound to the registry referenced by `selector.registry`
  // and that has the same API as a regular selector. Binding it in such a way makes it
  // possible to call the selector directly from another selector.
  const selector = function () {
    return registrySelector(selector.registry.select)(...arguments);
  };
  /**
   * Flag indicating that the selector is a registry selector that needs the correct registry
   * reference to be assigned to `selecto.registry` to make it work correctly.
   * be mapped as a registry selector.
   *
   * @type {boolean}
   */


  selector.isRegistrySelector = true;
  return selector;
}
/**
 * Creates a control function that takes additional curried argument with the `registry` object.
 * While a regular control has signature
 * ```js
 * ( action ) => ( iteratorOrPromise )
 * ```
 * where the control works with the `action` that it's bound to, a registry control has signature:
 * ```js
 * ( registry ) => ( action ) => ( iteratorOrPromise )
 * ```
 * A registry control is typically used to select data or dispatch an action to a registered
 * store.
 *
 * When registering a control created with `createRegistryControl` with a store, the store
 * knows which calling convention to use when executing the control.
 *
 * @param {Function} registryControl Function receiving a registry object and returning a control.
 *
 * @return {Function} Registry control that can be registered with a store.
 */

function createRegistryControl(registryControl) {
  registryControl.isRegistryControl = true;
  return registryControl;
}
//# sourceMappingURL=factory.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+data@7.6.0_react@17.0.2/node_modules/@wordpress/data/build-module/controls.js
/**
 * Internal dependencies
 */

/** @typedef {import('./types').StoreDescriptor} StoreDescriptor */

const SELECT = '@@data/SELECT';
const RESOLVE_SELECT = '@@data/RESOLVE_SELECT';
const DISPATCH = '@@data/DISPATCH';

function isObject(object) {
  return object !== null && typeof object === 'object';
}
/**
 * Dispatches a control action for triggering a synchronous registry select.
 *
 * Note: This control synchronously returns the current selector value, triggering the
 * resolution, but not waiting for it.
 *
 * @param {string|StoreDescriptor} storeNameOrDescriptor Unique namespace identifier for the store
 * @param {string}                 selectorName          The name of the selector.
 * @param {Array}                  args                  Arguments for the selector.
 *
 * @example
 * ```js
 * import { controls } from '@wordpress/data';
 *
 * // Action generator using `select`.
 * export function* myAction() {
 *   const isEditorSideBarOpened = yield controls.select( 'core/edit-post', 'isEditorSideBarOpened' );
 *   // Do stuff with the result from the `select`.
 * }
 * ```
 *
 * @return {Object} The control descriptor.
 */


function controls_select(storeNameOrDescriptor, selectorName) {
  for (var _len = arguments.length, args = new Array(_len > 2 ? _len - 2 : 0), _key = 2; _key < _len; _key++) {
    args[_key - 2] = arguments[_key];
  }

  return {
    type: SELECT,
    storeKey: isObject(storeNameOrDescriptor) ? storeNameOrDescriptor.name : storeNameOrDescriptor,
    selectorName,
    args
  };
}
/**
 * Dispatches a control action for triggering and resolving a registry select.
 *
 * Note: when this control action is handled, it automatically considers
 * selectors that may have a resolver. In such case, it will return a `Promise` that resolves
 * after the selector finishes resolving, with the final result value.
 *
 * @param {string|StoreDescriptor} storeNameOrDescriptor Unique namespace identifier for the store
 * @param {string}                 selectorName          The name of the selector
 * @param {Array}                  args                  Arguments for the selector.
 *
 * @example
 * ```js
 * import { controls } from '@wordpress/data';
 *
 * // Action generator using resolveSelect
 * export function* myAction() {
 * 	const isSidebarOpened = yield controls.resolveSelect( 'core/edit-post', 'isEditorSideBarOpened' );
 * 	// do stuff with the result from the select.
 * }
 * ```
 *
 * @return {Object} The control descriptor.
 */


function resolveSelect(storeNameOrDescriptor, selectorName) {
  for (var _len2 = arguments.length, args = new Array(_len2 > 2 ? _len2 - 2 : 0), _key2 = 2; _key2 < _len2; _key2++) {
    args[_key2 - 2] = arguments[_key2];
  }

  return {
    type: RESOLVE_SELECT,
    storeKey: isObject(storeNameOrDescriptor) ? storeNameOrDescriptor.name : storeNameOrDescriptor,
    selectorName,
    args
  };
}
/**
 * Dispatches a control action for triggering a registry dispatch.
 *
 * @param {string|StoreDescriptor} storeNameOrDescriptor Unique namespace identifier for the store
 * @param {string}                 actionName            The name of the action to dispatch
 * @param {Array}                  args                  Arguments for the dispatch action.
 *
 * @example
 * ```js
 * import { controls } from '@wordpress/data-controls';
 *
 * // Action generator using dispatch
 * export function* myAction() {
 *   yield controls.dispatch( 'core/edit-post', 'togglePublishSidebar' );
 *   // do some other things.
 * }
 * ```
 *
 * @return {Object}  The control descriptor.
 */


function dispatch(storeNameOrDescriptor, actionName) {
  for (var _len3 = arguments.length, args = new Array(_len3 > 2 ? _len3 - 2 : 0), _key3 = 2; _key3 < _len3; _key3++) {
    args[_key3 - 2] = arguments[_key3];
  }

  return {
    type: DISPATCH,
    storeKey: isObject(storeNameOrDescriptor) ? storeNameOrDescriptor.name : storeNameOrDescriptor,
    actionName,
    args
  };
}

const controls = {
  select: controls_select,
  resolveSelect,
  dispatch
};
const builtinControls = {
  [SELECT]: createRegistryControl(registry => _ref => {
    let {
      storeKey,
      selectorName,
      args
    } = _ref;
    return registry.select(storeKey)[selectorName](...args);
  }),
  [RESOLVE_SELECT]: createRegistryControl(registry => _ref2 => {
    let {
      storeKey,
      selectorName,
      args
    } = _ref2;
    const method = registry.select(storeKey)[selectorName].hasResolver ? 'resolveSelect' : 'select';
    return registry[method](storeKey)[selectorName](...args);
  }),
  [DISPATCH]: createRegistryControl(registry => _ref3 => {
    let {
      storeKey,
      actionName,
      args
    } = _ref3;
    return registry.dispatch(storeKey)[actionName](...args);
  })
};
//# sourceMappingURL=controls.js.map
// EXTERNAL MODULE: ../../node_modules/.pnpm/is-promise@4.0.0/node_modules/is-promise/index.mjs
var is_promise = __webpack_require__("../../node_modules/.pnpm/is-promise@4.0.0/node_modules/is-promise/index.mjs");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+data@7.6.0_react@17.0.2/node_modules/@wordpress/data/build-module/promise-middleware.js
/**
 * External dependencies
 */

/**
 * Simplest possible promise redux middleware.
 *
 * @type {import('redux').Middleware}
 */

const promiseMiddleware = () => next => action => {
  if ((0,is_promise/* default */.A)(action)) {
    return action.then(resolvedAction => {
      if (resolvedAction) {
        return next(resolvedAction);
      }
    });
  }

  return next(action);
};

/* harmony default export */ const promise_middleware = (promiseMiddleware);
//# sourceMappingURL=promise-middleware.js.map
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+data@7.6.0_react@17.0.2/node_modules/@wordpress/data/build-module/store/index.js
var store = __webpack_require__("../../node_modules/.pnpm/@wordpress+data@7.6.0_react@17.0.2/node_modules/@wordpress/data/build-module/store/index.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+data@7.6.0_react@17.0.2/node_modules/@wordpress/data/build-module/resolvers-cache-middleware.js
/**
 * External dependencies
 */

/**
 * Internal dependencies
 */


/** @typedef {import('./registry').WPDataRegistry} WPDataRegistry */

/**
 * Creates a middleware handling resolvers cache invalidation.
 *
 * @param {WPDataRegistry} registry   The registry reference for which to create
 *                                    the middleware.
 * @param {string}         reducerKey The namespace for which to create the
 *                                    middleware.
 *
 * @return {Function} Middleware function.
 */

const createResolversCacheMiddleware = (registry, reducerKey) => () => next => action => {
  const resolvers = registry.select(store/* default */.A).getCachedResolvers(reducerKey);
  Object.entries(resolvers).forEach(_ref => {
    let [selectorName, resolversByArgs] = _ref;
    const resolver = (0,lodash.get)(registry.stores, [reducerKey, 'resolvers', selectorName]);

    if (!resolver || !resolver.shouldInvalidate) {
      return;
    }

    resolversByArgs.forEach((value, args) => {
      // resolversByArgs is the map Map([ args ] => boolean) storing the cache resolution status for a given selector.
      // If the value is "finished" or "error" it means this resolver has finished its resolution which means we need
      // to invalidate it, if it's true it means it's inflight and the invalidation is not necessary.
      if ((value === null || value === void 0 ? void 0 : value.status) !== 'finished' && (value === null || value === void 0 ? void 0 : value.status) !== 'error' || !resolver.shouldInvalidate(action, ...args)) {
        return;
      } // Trigger cache invalidation


      registry.dispatch(store/* default */.A).invalidateResolution(reducerKey, selectorName, args);
    });
  });
  return next(action);
};

/* harmony default export */ const resolvers_cache_middleware = (createResolversCacheMiddleware);
//# sourceMappingURL=resolvers-cache-middleware.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+data@7.6.0_react@17.0.2/node_modules/@wordpress/data/build-module/redux-store/thunk-middleware.js
function createThunkMiddleware(args) {
  return () => next => action => {
    if (typeof action === 'function') {
      return action(args);
    }

    return next(action);
  };
}
//# sourceMappingURL=thunk-middleware.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+data@7.6.0_react@17.0.2/node_modules/@wordpress/data/build-module/redux-store/metadata/utils.js
/**
 * External dependencies
 */

/**
 * Higher-order reducer creator which creates a combined reducer object, keyed
 * by a property on the action object.
 *
 * @param  actionProperty Action property by which to key object.
 * @return Higher-order reducer.
 */
const onSubKey = actionProperty => reducer => function () {
  let state = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
  let action = arguments.length > 1 ? arguments[1] : undefined;
  // Retrieve subkey from action. Do not track if undefined; useful for cases
  // where reducer is scoped by action shape.
  const key = action[actionProperty];

  if (key === undefined) {
    return state;
  } // Avoid updating state if unchanged. Note that this also accounts for a
  // reducer which returns undefined on a key which is not yet tracked.


  const nextKeyState = reducer(state[key], action);

  if (nextKeyState === state[key]) {
    return state;
  }

  return { ...state,
    [key]: nextKeyState
  };
};
/**
 * Normalize selector argument array by defaulting `undefined` value to an empty array
 * and removing trailing `undefined` values.
 *
 * @param  args Selector argument array
 * @return Normalized state key array
 */

function selectorArgsToStateKey(args) {
  if (args === undefined || args === null) {
    return [];
  }

  const len = args.length;
  let idx = len;

  while (idx > 0 && args[idx - 1] === undefined) {
    idx--;
  }

  return idx === len ? args : args.slice(0, idx);
}
//# sourceMappingURL=utils.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+data@7.6.0_react@17.0.2/node_modules/@wordpress/data/build-module/redux-store/metadata/reducer.js
/**
 * External dependencies
 */


/**
 * Internal dependencies
 */


/**
 * Reducer function returning next state for selector resolution of
 * subkeys, object form:
 *
 *  selectorName -> EquivalentKeyMap<Array,boolean>
 */
const subKeysIsResolved = onSubKey('selectorName')(function () {
  let state = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : new (equivalent_key_map_default())();
  let action = arguments.length > 1 ? arguments[1] : undefined;

  switch (action.type) {
    case 'START_RESOLUTION':
      {
        const nextState = new (equivalent_key_map_default())(state);
        nextState.set(selectorArgsToStateKey(action.args), {
          status: 'resolving'
        });
        return nextState;
      }

    case 'FINISH_RESOLUTION':
      {
        const nextState = new (equivalent_key_map_default())(state);
        nextState.set(selectorArgsToStateKey(action.args), {
          status: 'finished'
        });
        return nextState;
      }

    case 'FAIL_RESOLUTION':
      {
        const nextState = new (equivalent_key_map_default())(state);
        nextState.set(selectorArgsToStateKey(action.args), {
          status: 'error',
          error: action.error
        });
        return nextState;
      }

    case 'START_RESOLUTIONS':
      {
        const nextState = new (equivalent_key_map_default())(state);

        for (const resolutionArgs of action.args) {
          nextState.set(selectorArgsToStateKey(resolutionArgs), {
            status: 'resolving'
          });
        }

        return nextState;
      }

    case 'FINISH_RESOLUTIONS':
      {
        const nextState = new (equivalent_key_map_default())(state);

        for (const resolutionArgs of action.args) {
          nextState.set(selectorArgsToStateKey(resolutionArgs), {
            status: 'finished'
          });
        }

        return nextState;
      }

    case 'FAIL_RESOLUTIONS':
      {
        const nextState = new (equivalent_key_map_default())(state);
        action.args.forEach((resolutionArgs, idx) => {
          const resolutionState = {
            status: 'error',
            error: undefined
          };
          const error = action.errors[idx];

          if (error) {
            resolutionState.error = error;
          }

          nextState.set(selectorArgsToStateKey(resolutionArgs), resolutionState);
        });
        return nextState;
      }

    case 'INVALIDATE_RESOLUTION':
      {
        const nextState = new (equivalent_key_map_default())(state);
        nextState.delete(selectorArgsToStateKey(action.args));
        return nextState;
      }
  }

  return state;
});
/**
 * Reducer function returning next state for selector resolution, object form:
 *
 *   selectorName -> EquivalentKeyMap<Array, boolean>
 *
 * @param  state  Current state.
 * @param  action Dispatched action.
 *
 * @return Next state.
 */

const isResolved = function () {
  let state = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
  let action = arguments.length > 1 ? arguments[1] : undefined;

  switch (action.type) {
    case 'INVALIDATE_RESOLUTION_FOR_STORE':
      return {};

    case 'INVALIDATE_RESOLUTION_FOR_STORE_SELECTOR':
      {
        if (action.selectorName in state) {
          const {
            [action.selectorName]: removedSelector,
            ...restState
          } = state;
          return restState;
        }

        return state;
      }

    case 'START_RESOLUTION':
    case 'FINISH_RESOLUTION':
    case 'FAIL_RESOLUTION':
    case 'START_RESOLUTIONS':
    case 'FINISH_RESOLUTIONS':
    case 'FAIL_RESOLUTIONS':
    case 'INVALIDATE_RESOLUTION':
      return subKeysIsResolved(state, action);
  }

  return state;
};

/* harmony default export */ const metadata_reducer = (isResolved);
//# sourceMappingURL=reducer.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+data@7.6.0_react@17.0.2/node_modules/@wordpress/data/build-module/redux-store/metadata/selectors.js
/**
 * External dependencies
 */

/**
 * Internal dependencies
 */


/** @typedef {Record<string, import('./reducer').State>} State */

/** @typedef {import('./reducer').StateValue} StateValue */

/** @typedef {import('./reducer').Status} Status */

/**
 * Returns the raw resolution state value for a given selector name,
 * and arguments set. May be undefined if the selector has never been resolved
 * or not resolved for the given set of arguments, otherwise true or false for
 * resolution started and completed respectively.
 *
 * @param {State}      state        Data state.
 * @param {string}     selectorName Selector name.
 * @param {unknown[]?} args         Arguments passed to selector.
 *
 * @return {StateValue|undefined} isResolving value.
 */

function getResolutionState(state, selectorName, args) {
  const map = (0,lodash.get)(state, [selectorName]);

  if (!map) {
    return;
  }

  return map.get(selectorArgsToStateKey(args));
}
/**
 * Returns the raw `isResolving` value for a given selector name,
 * and arguments set. May be undefined if the selector has never been resolved
 * or not resolved for the given set of arguments, otherwise true or false for
 * resolution started and completed respectively.
 *
 * @param {State}      state        Data state.
 * @param {string}     selectorName Selector name.
 * @param {unknown[]?} args         Arguments passed to selector.
 *
 * @return {boolean | undefined} isResolving value.
 */

function getIsResolving(state, selectorName, args) {
  const resolutionState = getResolutionState(state, selectorName, args);
  return resolutionState && resolutionState.status === 'resolving';
}
/**
 * Returns true if resolution has already been triggered for a given
 * selector name, and arguments set.
 *
 * @param {State}      state        Data state.
 * @param {string}     selectorName Selector name.
 * @param {unknown[]?} args         Arguments passed to selector.
 *
 * @return {boolean} Whether resolution has been triggered.
 */

function hasStartedResolution(state, selectorName, args) {
  return getResolutionState(state, selectorName, args) !== undefined;
}
/**
 * Returns true if resolution has completed for a given selector
 * name, and arguments set.
 *
 * @param {State}      state        Data state.
 * @param {string}     selectorName Selector name.
 * @param {unknown[]?} args         Arguments passed to selector.
 *
 * @return {boolean} Whether resolution has completed.
 */

function hasFinishedResolution(state, selectorName, args) {
  var _getResolutionState;

  const status = (_getResolutionState = getResolutionState(state, selectorName, args)) === null || _getResolutionState === void 0 ? void 0 : _getResolutionState.status;
  return status === 'finished' || status === 'error';
}
/**
 * Returns true if resolution has failed for a given selector
 * name, and arguments set.
 *
 * @param {State}      state        Data state.
 * @param {string}     selectorName Selector name.
 * @param {unknown[]?} args         Arguments passed to selector.
 *
 * @return {boolean} Has resolution failed
 */

function hasResolutionFailed(state, selectorName, args) {
  var _getResolutionState2;

  return ((_getResolutionState2 = getResolutionState(state, selectorName, args)) === null || _getResolutionState2 === void 0 ? void 0 : _getResolutionState2.status) === 'error';
}
/**
 * Returns the resolution error for a given selector name, and arguments set.
 * Note it may be of an Error type, but may also be null, undefined, or anything else
 * that can be `throw`-n.
 *
 * @param {State}      state        Data state.
 * @param {string}     selectorName Selector name.
 * @param {unknown[]?} args         Arguments passed to selector.
 *
 * @return {Error|unknown} Last resolution error
 */

function getResolutionError(state, selectorName, args) {
  const resolutionState = getResolutionState(state, selectorName, args);
  return (resolutionState === null || resolutionState === void 0 ? void 0 : resolutionState.status) === 'error' ? resolutionState.error : null;
}
/**
 * Returns true if resolution has been triggered but has not yet completed for
 * a given selector name, and arguments set.
 *
 * @param {State}      state        Data state.
 * @param {string}     selectorName Selector name.
 * @param {unknown[]?} args         Arguments passed to selector.
 *
 * @return {boolean} Whether resolution is in progress.
 */

function isResolving(state, selectorName, args) {
  var _getResolutionState3;

  return ((_getResolutionState3 = getResolutionState(state, selectorName, args)) === null || _getResolutionState3 === void 0 ? void 0 : _getResolutionState3.status) === 'resolving';
}
/**
 * Returns the list of the cached resolvers.
 *
 * @param {State} state Data state.
 *
 * @return {State} Resolvers mapped by args and selectorName.
 */

function getCachedResolvers(state) {
  return state;
}
//# sourceMappingURL=selectors.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+data@7.6.0_react@17.0.2/node_modules/@wordpress/data/build-module/redux-store/metadata/actions.js
/**
 * Returns an action object used in signalling that selector resolution has
 * started.
 *
 * @param {string}    selectorName Name of selector for which resolver triggered.
 * @param {unknown[]} args         Arguments to associate for uniqueness.
 *
 * @return {{ type: 'START_RESOLUTION', selectorName: string, args: unknown[] }} Action object.
 */
function startResolution(selectorName, args) {
  return {
    type: 'START_RESOLUTION',
    selectorName,
    args
  };
}
/**
 * Returns an action object used in signalling that selector resolution has
 * completed.
 *
 * @param {string}    selectorName Name of selector for which resolver triggered.
 * @param {unknown[]} args         Arguments to associate for uniqueness.
 *
 * @return {{ type: 'FINISH_RESOLUTION', selectorName: string, args: unknown[] }} Action object.
 */

function finishResolution(selectorName, args) {
  return {
    type: 'FINISH_RESOLUTION',
    selectorName,
    args
  };
}
/**
 * Returns an action object used in signalling that selector resolution has
 * failed.
 *
 * @param {string}        selectorName Name of selector for which resolver triggered.
 * @param {unknown[]}     args         Arguments to associate for uniqueness.
 * @param {Error|unknown} error        The error that caused the failure.
 *
 * @return {{ type: 'FAIL_RESOLUTION', selectorName: string, args: unknown[], error: Error|unknown }} Action object.
 */

function failResolution(selectorName, args, error) {
  return {
    type: 'FAIL_RESOLUTION',
    selectorName,
    args,
    error
  };
}
/**
 * Returns an action object used in signalling that a batch of selector resolutions has
 * started.
 *
 * @param {string}      selectorName Name of selector for which resolver triggered.
 * @param {unknown[][]} args         Array of arguments to associate for uniqueness, each item
 *                                   is associated to a resolution.
 *
 * @return {{ type: 'START_RESOLUTIONS', selectorName: string, args: unknown[][] }} Action object.
 */

function startResolutions(selectorName, args) {
  return {
    type: 'START_RESOLUTIONS',
    selectorName,
    args
  };
}
/**
 * Returns an action object used in signalling that a batch of selector resolutions has
 * completed.
 *
 * @param {string}      selectorName Name of selector for which resolver triggered.
 * @param {unknown[][]} args         Array of arguments to associate for uniqueness, each item
 *                                   is associated to a resolution.
 *
 * @return {{ type: 'FINISH_RESOLUTIONS', selectorName: string, args: unknown[][] }} Action object.
 */

function finishResolutions(selectorName, args) {
  return {
    type: 'FINISH_RESOLUTIONS',
    selectorName,
    args
  };
}
/**
 * Returns an action object used in signalling that a batch of selector resolutions has
 * completed and at least one of them has failed.
 *
 * @param {string}            selectorName Name of selector for which resolver triggered.
 * @param {unknown[]}         args         Array of arguments to associate for uniqueness, each item
 *                                         is associated to a resolution.
 * @param {(Error|unknown)[]} errors       Array of errors to associate for uniqueness, each item
 *                                         is associated to a resolution.
 * @return {{ type: 'FAIL_RESOLUTIONS', selectorName: string, args: unknown[], errors: Array<Error|unknown> }} Action object.
 */

function failResolutions(selectorName, args, errors) {
  return {
    type: 'FAIL_RESOLUTIONS',
    selectorName,
    args,
    errors
  };
}
/**
 * Returns an action object used in signalling that we should invalidate the resolution cache.
 *
 * @param {string}    selectorName Name of selector for which resolver should be invalidated.
 * @param {unknown[]} args         Arguments to associate for uniqueness.
 *
 * @return {{ type: 'INVALIDATE_RESOLUTION', selectorName: string, args: any[] }} Action object.
 */

function invalidateResolution(selectorName, args) {
  return {
    type: 'INVALIDATE_RESOLUTION',
    selectorName,
    args
  };
}
/**
 * Returns an action object used in signalling that the resolution
 * should be invalidated.
 *
 * @return {{ type: 'INVALIDATE_RESOLUTION_FOR_STORE' }} Action object.
 */

function invalidateResolutionForStore() {
  return {
    type: 'INVALIDATE_RESOLUTION_FOR_STORE'
  };
}
/**
 * Returns an action object used in signalling that the resolution cache for a
 * given selectorName should be invalidated.
 *
 * @param {string} selectorName Name of selector for which all resolvers should
 *                              be invalidated.
 *
 * @return  {{ type: 'INVALIDATE_RESOLUTION_FOR_STORE_SELECTOR', selectorName: string }} Action object.
 */

function invalidateResolutionForStoreSelector(selectorName) {
  return {
    type: 'INVALIDATE_RESOLUTION_FOR_STORE_SELECTOR',
    selectorName
  };
}
//# sourceMappingURL=actions.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+data@7.6.0_react@17.0.2/node_modules/@wordpress/data/build-module/redux-store/index.js
/**
 * External dependencies
 */




/**
 * WordPress dependencies
 */



/**
 * Internal dependencies
 */








/** @typedef {import('../types').DataRegistry} DataRegistry */

/**
 * @typedef {import('../types').StoreDescriptor<C>} StoreDescriptor
 * @template C
 */

/**
 * @typedef {import('../types').ReduxStoreConfig<State,Actions,Selectors>} ReduxStoreConfig
 * @template State,Actions,Selectors
 */

const trimUndefinedValues = array => {
  const result = [...array];

  for (let i = result.length - 1; i >= 0; i--) {
    if (result[i] === undefined) {
      result.splice(i, 1);
    }
  }

  return result;
};
/**
 * Create a cache to track whether resolvers started running or not.
 *
 * @return {Object} Resolvers Cache.
 */


function createResolversCache() {
  const cache = {};
  return {
    isRunning(selectorName, args) {
      return cache[selectorName] && cache[selectorName].get(trimUndefinedValues(args));
    },

    clear(selectorName, args) {
      if (cache[selectorName]) {
        cache[selectorName].delete(trimUndefinedValues(args));
      }
    },

    markAsRunning(selectorName, args) {
      if (!cache[selectorName]) {
        cache[selectorName] = new (equivalent_key_map_default())();
      }

      cache[selectorName].set(trimUndefinedValues(args), true);
    }

  };
}
/**
 * Creates a data store descriptor for the provided Redux store configuration containing
 * properties describing reducer, actions, selectors, controls and resolvers.
 *
 * @example
 * ```js
 * import { createReduxStore } from '@wordpress/data';
 *
 * const store = createReduxStore( 'demo', {
 *     reducer: ( state = 'OK' ) => state,
 *     selectors: {
 *         getValue: ( state ) => state,
 *     },
 * } );
 * ```
 *
 * @template State,Actions,Selectors
 * @param {string}                                    key     Unique namespace identifier.
 * @param {ReduxStoreConfig<State,Actions,Selectors>} options Registered store options, with properties
 *                                                            describing reducer, actions, selectors,
 *                                                            and resolvers.
 *
 * @return   {StoreDescriptor<ReduxStoreConfig<State,Actions,Selectors>>} Store Object.
 */


function createReduxStore(key, options) {
  return {
    name: key,
    instantiate: registry => {
      const reducer = options.reducer;
      const thunkArgs = {
        registry,

        get dispatch() {
          return Object.assign(action => store.dispatch(action), getActions());
        },

        get select() {
          return Object.assign(selector => selector(store.__unstableOriginalGetState()), getSelectors());
        },

        get resolveSelect() {
          return getResolveSelectors();
        }

      };
      const store = instantiateReduxStore(key, options, registry, thunkArgs);
      const resolversCache = createResolversCache();
      let resolvers;
      const actions = mapActions({ ...actions_namespaceObject,
        ...options.actions
      }, store);
      let selectors = mapSelectors({ ...(0,lodash.mapValues)(selectors_namespaceObject, selector => function (state) {
          for (var _len = arguments.length, args = new Array(_len > 1 ? _len - 1 : 0), _key = 1; _key < _len; _key++) {
            args[_key - 1] = arguments[_key];
          }

          return selector(state.metadata, ...args);
        }),
        ...(0,lodash.mapValues)(options.selectors, selector => {
          if (selector.isRegistrySelector) {
            selector.registry = registry;
          }

          return function (state) {
            for (var _len2 = arguments.length, args = new Array(_len2 > 1 ? _len2 - 1 : 0), _key2 = 1; _key2 < _len2; _key2++) {
              args[_key2 - 1] = arguments[_key2];
            }

            return selector(state.root, ...args);
          };
        })
      }, store);

      if (options.resolvers) {
        const result = mapResolvers(options.resolvers, selectors, store, resolversCache);
        resolvers = result.resolvers;
        selectors = result.selectors;
      }

      const resolveSelectors = mapResolveSelectors(selectors, store);
      const suspendSelectors = mapSuspendSelectors(selectors, store);

      const getSelectors = () => selectors;

      const getActions = () => actions;

      const getResolveSelectors = () => resolveSelectors;

      const getSuspendSelectors = () => suspendSelectors; // We have some modules monkey-patching the store object
      // It's wrong to do so but until we refactor all of our effects to controls
      // We need to keep the same "store" instance here.


      store.__unstableOriginalGetState = store.getState;

      store.getState = () => store.__unstableOriginalGetState().root; // Customize subscribe behavior to call listeners only on effective change,
      // not on every dispatch.


      const subscribe = store && (listener => {
        let lastState = store.__unstableOriginalGetState();

        return store.subscribe(() => {
          const state = store.__unstableOriginalGetState();

          const hasChanged = state !== lastState;
          lastState = state;

          if (hasChanged) {
            listener();
          }
        });
      }); // This can be simplified to just { subscribe, getSelectors, getActions }
      // Once we remove the use function.


      return {
        reducer,
        store,
        actions,
        selectors,
        resolvers,
        getSelectors,
        getResolveSelectors,
        getSuspendSelectors,
        getActions,
        subscribe
      };
    }
  };
}
/**
 * Creates a redux store for a namespace.
 *
 * @param {string}       key       Unique namespace identifier.
 * @param {Object}       options   Registered store options, with properties
 *                                 describing reducer, actions, selectors,
 *                                 and resolvers.
 * @param {DataRegistry} registry  Registry reference.
 * @param {Object}       thunkArgs Argument object for the thunk middleware.
 * @return {Object} Newly created redux store.
 */

function instantiateReduxStore(key, options, registry, thunkArgs) {
  const controls = { ...options.controls,
    ...builtinControls
  };
  const normalizedControls = (0,lodash.mapValues)(controls, control => control.isRegistryControl ? control(registry) : control);
  const middlewares = [resolvers_cache_middleware(registry, key), promise_middleware, (0,build_module/* default */.A)(normalizedControls), createThunkMiddleware(thunkArgs)];
  const enhancers = [(0,redux/* applyMiddleware */.Tw)(...middlewares)];

  if (typeof window !== 'undefined' && window.__REDUX_DEVTOOLS_EXTENSION__) {
    enhancers.push(window.__REDUX_DEVTOOLS_EXTENSION__({
      name: key,
      instanceId: key
    }));
  }

  const {
    reducer,
    initialState
  } = options;
  const enhancedReducer = turbo_combine_reducers_default()({
    metadata: metadata_reducer,
    root: reducer
  });
  return (0,redux/* createStore */.y$)(enhancedReducer, {
    root: initialState
  }, (0,compose/* default */.A)(enhancers));
}
/**
 * Maps selectors to a store.
 *
 * @param {Object} selectors Selectors to register. Keys will be used as the
 *                           public facing API. Selectors will get passed the
 *                           state as first argument.
 * @param {Object} store     The store to which the selectors should be mapped.
 * @return {Object} Selectors mapped to the provided store.
 */


function mapSelectors(selectors, store) {
  const createStateSelector = registrySelector => {
    const selector = function runSelector() {
      // This function is an optimized implementation of:
      //
      //   selector( store.getState(), ...arguments )
      //
      // Where the above would incur an `Array#concat` in its application,
      // the logic here instead efficiently constructs an arguments array via
      // direct assignment.
      const argsLength = arguments.length;
      const args = new Array(argsLength + 1);
      args[0] = store.__unstableOriginalGetState();

      for (let i = 0; i < argsLength; i++) {
        args[i + 1] = arguments[i];
      }

      return registrySelector(...args);
    };

    selector.hasResolver = false;
    return selector;
  };

  return (0,lodash.mapValues)(selectors, createStateSelector);
}
/**
 * Maps actions to dispatch from a given store.
 *
 * @param {Object} actions Actions to register.
 * @param {Object} store   The redux store to which the actions should be mapped.
 *
 * @return {Object} Actions mapped to the redux store provided.
 */


function mapActions(actions, store) {
  const createBoundAction = action => function () {
    return Promise.resolve(store.dispatch(action(...arguments)));
  };

  return (0,lodash.mapValues)(actions, createBoundAction);
}
/**
 * Maps selectors to functions that return a resolution promise for them
 *
 * @param {Object} selectors Selectors to map.
 * @param {Object} store     The redux store the selectors select from.
 *
 * @return {Object} Selectors mapped to their resolution functions.
 */


function mapResolveSelectors(selectors, store) {
  const {
    getIsResolving,
    hasStartedResolution,
    hasFinishedResolution,
    hasResolutionFailed,
    isResolving,
    getCachedResolvers,
    getResolutionState,
    getResolutionError,
    ...storeSelectors
  } = selectors;
  return (0,lodash.mapValues)(storeSelectors, (selector, selectorName) => {
    // If the selector doesn't have a resolver, just convert the return value
    // (including exceptions) to a Promise, no additional extra behavior is needed.
    if (!selector.hasResolver) {
      return async function () {
        for (var _len3 = arguments.length, args = new Array(_len3), _key3 = 0; _key3 < _len3; _key3++) {
          args[_key3] = arguments[_key3];
        }

        return selector.apply(null, args);
      };
    }

    return function () {
      for (var _len4 = arguments.length, args = new Array(_len4), _key4 = 0; _key4 < _len4; _key4++) {
        args[_key4] = arguments[_key4];
      }

      return new Promise((resolve, reject) => {
        const hasFinished = () => selectors.hasFinishedResolution(selectorName, args);

        const finalize = result => {
          const hasFailed = selectors.hasResolutionFailed(selectorName, args);

          if (hasFailed) {
            const error = selectors.getResolutionError(selectorName, args);
            reject(error);
          } else {
            resolve(result);
          }
        };

        const getResult = () => selector.apply(null, args); // Trigger the selector (to trigger the resolver)


        const result = getResult();

        if (hasFinished()) {
          return finalize(result);
        }

        const unsubscribe = store.subscribe(() => {
          if (hasFinished()) {
            unsubscribe();
            finalize(getResult());
          }
        });
      });
    };
  });
}
/**
 * Maps selectors to functions that throw a suspense promise if not yet resolved.
 *
 * @param {Object} selectors Selectors to map.
 * @param {Object} store     The redux store the selectors select from.
 *
 * @return {Object} Selectors mapped to their suspense functions.
 */


function mapSuspendSelectors(selectors, store) {
  return (0,lodash.mapValues)(selectors, (selector, selectorName) => {
    // Selector without a resolver doesn't have any extra suspense behavior.
    if (!selector.hasResolver) {
      return selector;
    }

    return function () {
      for (var _len5 = arguments.length, args = new Array(_len5), _key5 = 0; _key5 < _len5; _key5++) {
        args[_key5] = arguments[_key5];
      }

      const result = selector.apply(null, args);

      if (selectors.hasFinishedResolution(selectorName, args)) {
        if (selectors.hasResolutionFailed(selectorName, args)) {
          throw selectors.getResolutionError(selectorName, args);
        }

        return result;
      }

      throw new Promise(resolve => {
        const unsubscribe = store.subscribe(() => {
          if (selectors.hasFinishedResolution(selectorName, args)) {
            resolve();
            unsubscribe();
          }
        });
      });
    };
  });
}
/**
 * Returns resolvers with matched selectors for a given namespace.
 * Resolvers are side effects invoked once per argument set of a given selector call,
 * used in ensuring that the data needs for the selector are satisfied.
 *
 * @param {Object} resolvers      Resolvers to register.
 * @param {Object} selectors      The current selectors to be modified.
 * @param {Object} store          The redux store to which the resolvers should be mapped.
 * @param {Object} resolversCache Resolvers Cache.
 */


function mapResolvers(resolvers, selectors, store, resolversCache) {
  // The `resolver` can be either a function that does the resolution, or, in more advanced
  // cases, an object with a `fullfill` method and other optional methods like `isFulfilled`.
  // Here we normalize the `resolver` function to an object with `fulfill` method.
  const mappedResolvers = (0,lodash.mapValues)(resolvers, resolver => {
    if (resolver.fulfill) {
      return resolver;
    }

    return { ...resolver,
      // Copy the enumerable properties of the resolver function.
      fulfill: resolver // Add the fulfill method.

    };
  });

  const mapSelector = (selector, selectorName) => {
    const resolver = resolvers[selectorName];

    if (!resolver) {
      selector.hasResolver = false;
      return selector;
    }

    const selectorResolver = function () {
      for (var _len6 = arguments.length, args = new Array(_len6), _key6 = 0; _key6 < _len6; _key6++) {
        args[_key6] = arguments[_key6];
      }

      async function fulfillSelector() {
        const state = store.getState();

        if (resolversCache.isRunning(selectorName, args) || typeof resolver.isFulfilled === 'function' && resolver.isFulfilled(state, ...args)) {
          return;
        }

        const {
          metadata
        } = store.__unstableOriginalGetState();

        if (hasStartedResolution(metadata, selectorName, args)) {
          return;
        }

        resolversCache.markAsRunning(selectorName, args);
        setTimeout(async () => {
          resolversCache.clear(selectorName, args);
          store.dispatch(startResolution(selectorName, args));

          try {
            await fulfillResolver(store, mappedResolvers, selectorName, ...args);
            store.dispatch(finishResolution(selectorName, args));
          } catch (error) {
            store.dispatch(failResolution(selectorName, args, error));
          }
        });
      }

      fulfillSelector(...args);
      return selector(...args);
    };

    selectorResolver.hasResolver = true;
    return selectorResolver;
  };

  return {
    resolvers: mappedResolvers,
    selectors: (0,lodash.mapValues)(selectors, mapSelector)
  };
}
/**
 * Calls a resolver given arguments
 *
 * @param {Object} store        Store reference, for fulfilling via resolvers
 * @param {Object} resolvers    Store Resolvers
 * @param {string} selectorName Selector name to fulfill.
 * @param {Array}  args         Selector Arguments.
 */


async function fulfillResolver(store, resolvers, selectorName) {
  const resolver = (0,lodash.get)(resolvers, [selectorName]);

  if (!resolver) {
    return;
  }

  for (var _len7 = arguments.length, args = new Array(_len7 > 3 ? _len7 - 3 : 0), _key7 = 3; _key7 < _len7; _key7++) {
    args[_key7 - 3] = arguments[_key7];
  }

  const action = resolver.fulfill(...args);

  if (action) {
    await store.dispatch(action);
  }
}
//# sourceMappingURL=index.js.map

/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+data@7.6.0_react@17.0.2/node_modules/@wordpress/data/build-module/registry.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";

// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  I: () => (/* binding */ createRegistry)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/lodash@4.17.21/node_modules/lodash/lodash.js
var lodash = __webpack_require__("../../node_modules/.pnpm/lodash@4.17.21/node_modules/lodash/lodash.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+deprecated@3.41.0/node_modules/@wordpress/deprecated/build-module/index.js
var build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+deprecated@3.41.0/node_modules/@wordpress/deprecated/build-module/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+data@7.6.0_react@17.0.2/node_modules/@wordpress/data/build-module/redux-store/index.js + 9 modules
var redux_store = __webpack_require__("../../node_modules/.pnpm/@wordpress+data@7.6.0_react@17.0.2/node_modules/@wordpress/data/build-module/redux-store/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+data@7.6.0_react@17.0.2/node_modules/@wordpress/data/build-module/store/index.js
var store = __webpack_require__("../../node_modules/.pnpm/@wordpress+data@7.6.0_react@17.0.2/node_modules/@wordpress/data/build-module/store/index.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+data@7.6.0_react@17.0.2/node_modules/@wordpress/data/build-module/utils/emitter.js
/**
 * Create an event emitter.
 *
 * @return {import("../types").DataEmitter} Emitter.
 */
function createEmitter() {
  let isPaused = false;
  let isPending = false;
  const listeners = new Set();

  const notifyListeners = () => // We use Array.from to clone the listeners Set
  // This ensures that we don't run a listener
  // that was added as a response to another listener.
  Array.from(listeners).forEach(listener => listener());

  return {
    get isPaused() {
      return isPaused;
    },

    subscribe(listener) {
      listeners.add(listener);
      return () => listeners.delete(listener);
    },

    pause() {
      isPaused = true;
    },

    resume() {
      isPaused = false;

      if (isPending) {
        isPending = false;
        notifyListeners();
      }
    },

    emit() {
      if (isPaused) {
        isPending = true;
        return;
      }

      notifyListeners();
    }

  };
}
//# sourceMappingURL=emitter.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+data@7.6.0_react@17.0.2/node_modules/@wordpress/data/build-module/registry.js
/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */


/**
 * Internal dependencies
 */




/** @typedef {import('./types').StoreDescriptor} StoreDescriptor */

/**
 * @typedef {Object} WPDataRegistry An isolated orchestrator of store registrations.
 *
 * @property {Function} registerGenericStore Given a namespace key and settings
 *                                           object, registers a new generic
 *                                           store.
 * @property {Function} registerStore        Given a namespace key and settings
 *                                           object, registers a new namespace
 *                                           store.
 * @property {Function} subscribe            Given a function callback, invokes
 *                                           the callback on any change to state
 *                                           within any registered store.
 * @property {Function} select               Given a namespace key, returns an
 *                                           object of the  store's registered
 *                                           selectors.
 * @property {Function} dispatch             Given a namespace key, returns an
 *                                           object of the store's registered
 *                                           action dispatchers.
 */

/**
 * @typedef {Object} WPDataPlugin An object of registry function overrides.
 *
 * @property {Function} registerStore registers store.
 */

function isObject(object) {
  return object !== null && typeof object === 'object';
}
/**
 * Creates a new store registry, given an optional object of initial store
 * configurations.
 *
 * @param {Object}  storeConfigs Initial store configurations.
 * @param {Object?} parent       Parent registry.
 *
 * @return {WPDataRegistry} Data registry.
 */


function createRegistry() {
  let storeConfigs = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
  let parent = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : null;
  const stores = {};
  const emitter = createEmitter();
  const listeningStores = new Set();
  /**
   * Global listener called for each store's update.
   */

  function globalListener() {
    emitter.emit();
  }
  /**
   * Subscribe to changes to any data.
   *
   * @param {Function} listener Listener function.
   *
   * @return {Function} Unsubscribe function.
   */


  const subscribe = listener => {
    return emitter.subscribe(listener);
  };
  /**
   * Calls a selector given the current state and extra arguments.
   *
   * @param {string|StoreDescriptor} storeNameOrDescriptor Unique namespace identifier for the store
   *                                                       or the store descriptor.
   *
   * @return {*} The selector's returned value.
   */


  function select(storeNameOrDescriptor) {
    const storeName = isObject(storeNameOrDescriptor) ? storeNameOrDescriptor.name : storeNameOrDescriptor;
    listeningStores.add(storeName);
    const store = stores[storeName];

    if (store) {
      return store.getSelectors();
    }

    return parent === null || parent === void 0 ? void 0 : parent.select(storeName);
  }

  function __unstableMarkListeningStores(callback, ref) {
    listeningStores.clear();

    try {
      return callback.call(this);
    } finally {
      ref.current = Array.from(listeningStores);
    }
  }
  /**
   * Given a store descriptor, returns an object containing the store's selectors pre-bound to
   * state so that you only need to supply additional arguments, and modified so that they return
   * promises that resolve to their eventual values, after any resolvers have ran.
   *
   * @param {StoreDescriptor|string} storeNameOrDescriptor The store descriptor. The legacy calling
   *                                                       convention of passing the store name is
   *                                                       also supported.
   *
   * @return {Object} Each key of the object matches the name of a selector.
   */


  function resolveSelect(storeNameOrDescriptor) {
    const storeName = isObject(storeNameOrDescriptor) ? storeNameOrDescriptor.name : storeNameOrDescriptor;
    listeningStores.add(storeName);
    const store = stores[storeName];

    if (store) {
      return store.getResolveSelectors();
    }

    return parent && parent.resolveSelect(storeName);
  }
  /**
   * Given a store descriptor, returns an object containing the store's selectors pre-bound to
   * state so that you only need to supply additional arguments, and modified so that they throw
   * promises in case the selector is not resolved yet.
   *
   * @param {StoreDescriptor|string} storeNameOrDescriptor The store descriptor. The legacy calling
   *                                                       convention of passing the store name is
   *                                                       also supported.
   *
   * @return {Object} Object containing the store's suspense-wrapped selectors.
   */


  function suspendSelect(storeNameOrDescriptor) {
    const storeName = isObject(storeNameOrDescriptor) ? storeNameOrDescriptor.name : storeNameOrDescriptor;
    listeningStores.add(storeName);
    const store = stores[storeName];

    if (store) {
      return store.getSuspendSelectors();
    }

    return parent && parent.suspendSelect(storeName);
  }
  /**
   * Returns the available actions for a part of the state.
   *
   * @param {string|StoreDescriptor} storeNameOrDescriptor Unique namespace identifier for the store
   *                                                       or the store descriptor.
   *
   * @return {*} The action's returned value.
   */


  function dispatch(storeNameOrDescriptor) {
    const storeName = isObject(storeNameOrDescriptor) ? storeNameOrDescriptor.name : storeNameOrDescriptor;
    const store = stores[storeName];

    if (store) {
      return store.getActions();
    }

    return parent && parent.dispatch(storeName);
  } //
  // Deprecated
  // TODO: Remove this after `use()` is removed.


  function withPlugins(attributes) {
    return (0,lodash.mapValues)(attributes, (attribute, key) => {
      if (typeof attribute !== 'function') {
        return attribute;
      }

      return function () {
        return registry[key].apply(null, arguments);
      };
    });
  }
  /**
   * Registers a store instance.
   *
   * @param {string} name  Store registry name.
   * @param {Object} store Store instance object (getSelectors, getActions, subscribe).
   */


  function registerStoreInstance(name, store) {
    if (typeof store.getSelectors !== 'function') {
      throw new TypeError('store.getSelectors must be a function');
    }

    if (typeof store.getActions !== 'function') {
      throw new TypeError('store.getActions must be a function');
    }

    if (typeof store.subscribe !== 'function') {
      throw new TypeError('store.subscribe must be a function');
    } // The emitter is used to keep track of active listeners when the registry
    // get paused, that way, when resumed we should be able to call all these
    // pending listeners.


    store.emitter = createEmitter();
    const currentSubscribe = store.subscribe;

    store.subscribe = listener => {
      const unsubscribeFromEmitter = store.emitter.subscribe(listener);
      const unsubscribeFromStore = currentSubscribe(() => {
        if (store.emitter.isPaused) {
          store.emitter.emit();
          return;
        }

        listener();
      });
      return () => {
        unsubscribeFromStore === null || unsubscribeFromStore === void 0 ? void 0 : unsubscribeFromStore();
        unsubscribeFromEmitter === null || unsubscribeFromEmitter === void 0 ? void 0 : unsubscribeFromEmitter();
      };
    };

    stores[name] = store;
    store.subscribe(globalListener);
  }
  /**
   * Registers a new store given a store descriptor.
   *
   * @param {StoreDescriptor} store Store descriptor.
   */


  function register(store) {
    registerStoreInstance(store.name, store.instantiate(registry));
  }

  function registerGenericStore(name, store) {
    (0,build_module/* default */.A)('wp.data.registerGenericStore', {
      since: '5.9',
      alternative: 'wp.data.register( storeDescriptor )'
    });
    registerStoreInstance(name, store);
  }
  /**
   * Registers a standard `@wordpress/data` store.
   *
   * @param {string} storeName Unique namespace identifier.
   * @param {Object} options   Store description (reducer, actions, selectors, resolvers).
   *
   * @return {Object} Registered store object.
   */


  function registerStore(storeName, options) {
    if (!options.reducer) {
      throw new TypeError('Must specify store reducer');
    }

    const store = (0,redux_store/* default */.A)(storeName, options).instantiate(registry);
    registerStoreInstance(storeName, store);
    return store.store;
  }
  /**
   * Subscribe handler to a store.
   *
   * @param {string|StoreDescriptor} storeNameOrDescriptor The store name.
   * @param {Function}               handler               The function subscribed to the store.
   * @return {Function} A function to unsubscribe the handler.
   */


  function __unstableSubscribeStore(storeNameOrDescriptor, handler) {
    const storeName = isObject(storeNameOrDescriptor) ? storeNameOrDescriptor.name : storeNameOrDescriptor;
    const store = stores[storeName];

    if (store) {
      return store.subscribe(handler);
    } // Trying to access a store that hasn't been registered,
    // this is a pattern rarely used but seen in some places.
    // We fallback to regular `subscribe` here for backward-compatibility for now.
    // See https://github.com/WordPress/gutenberg/pull/27466 for more info.


    if (!parent) {
      return subscribe(handler);
    }

    return parent.__unstableSubscribeStore(storeNameOrDescriptor, handler);
  }

  function batch(callback) {
    emitter.pause();
    Object.values(stores).forEach(store => store.emitter.pause());
    callback();
    emitter.resume();
    Object.values(stores).forEach(store => store.emitter.resume());
  }

  let registry = {
    batch,
    stores,
    namespaces: stores,
    // TODO: Deprecate/remove this.
    subscribe,
    select,
    resolveSelect,
    suspendSelect,
    dispatch,
    use,
    register,
    registerGenericStore,
    registerStore,
    __unstableMarkListeningStores,
    __unstableSubscribeStore
  }; //
  // TODO:
  // This function will be deprecated as soon as it is no longer internally referenced.

  function use(plugin, options) {
    if (!plugin) {
      return;
    }

    registry = { ...registry,
      ...plugin(registry, options)
    };
    return registry;
  }

  registry.register(store/* default */.A);

  for (const [name, config] of Object.entries(storeConfigs)) {
    registry.register((0,redux_store/* default */.A)(name, config));
  }

  if (parent) {
    parent.subscribe(globalListener);
  }

  return withPlugins(registry);
}
//# sourceMappingURL=registry.js.map

/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+data@7.6.0_react@17.0.2/node_modules/@wordpress/data/build-module/store/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
const coreDataStore = {
  name: 'core/data',

  instantiate(registry) {
    const getCoreDataSelector = selectorName => function (key) {
      for (var _len = arguments.length, args = new Array(_len > 1 ? _len - 1 : 0), _key = 1; _key < _len; _key++) {
        args[_key - 1] = arguments[_key];
      }

      return registry.select(key)[selectorName](...args);
    };

    const getCoreDataAction = actionName => function (key) {
      for (var _len2 = arguments.length, args = new Array(_len2 > 1 ? _len2 - 1 : 0), _key2 = 1; _key2 < _len2; _key2++) {
        args[_key2 - 1] = arguments[_key2];
      }

      return registry.dispatch(key)[actionName](...args);
    };

    return {
      getSelectors() {
        return Object.fromEntries(['getIsResolving', 'hasStartedResolution', 'hasFinishedResolution', 'isResolving', 'getCachedResolvers'].map(selectorName => [selectorName, getCoreDataSelector(selectorName)]));
      },

      getActions() {
        return Object.fromEntries(['startResolution', 'finishResolution', 'invalidateResolution', 'invalidateResolutionForStore', 'invalidateResolutionForStoreSelector'].map(actionName => [actionName, getCoreDataAction(actionName)]));
      },

      subscribe() {
        // There's no reasons to trigger any listener when we subscribe to this store
        // because there's no state stored in this store that need to retrigger selectors
        // if a change happens, the corresponding store where the tracking stated live
        // would have already triggered a "subscribe" call.
        return () => () => {};
      }

    };
  }

};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (coreDataStore);
//# sourceMappingURL=index.js.map

/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+is-shallow-equal@4.47.0/node_modules/@wordpress/is-shallow-equal/build-module/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";

// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  Ay: () => (/* binding */ isShallowEqual)
});

// UNUSED EXPORTS: isShallowEqualArrays, isShallowEqualObjects

;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+is-shallow-equal@4.47.0/node_modules/@wordpress/is-shallow-equal/build-module/objects.js
/**
 * Returns true if the two objects are shallow equal, or false otherwise.
 *
 * @param {import('.').ComparableObject} a First object to compare.
 * @param {import('.').ComparableObject} b Second object to compare.
 *
 * @return {boolean} Whether the two objects are shallow equal.
 */
function isShallowEqualObjects(a, b) {
  if (a === b) {
    return true;
  }
  const aKeys = Object.keys(a);
  const bKeys = Object.keys(b);
  if (aKeys.length !== bKeys.length) {
    return false;
  }
  let i = 0;
  while (i < aKeys.length) {
    const key = aKeys[i];
    const aValue = a[key];
    if (
    // In iterating only the keys of the first object after verifying
    // equal lengths, account for the case that an explicit `undefined`
    // value in the first is implicitly undefined in the second.
    //
    // Example: isShallowEqualObjects( { a: undefined }, { b: 5 } )
    aValue === undefined && !b.hasOwnProperty(key) || aValue !== b[key]) {
      return false;
    }
    i++;
  }
  return true;
}
//# sourceMappingURL=objects.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+is-shallow-equal@4.47.0/node_modules/@wordpress/is-shallow-equal/build-module/arrays.js
/**
 * Returns true if the two arrays are shallow equal, or false otherwise.
 *
 * @param {any[]} a First array to compare.
 * @param {any[]} b Second array to compare.
 *
 * @return {boolean} Whether the two arrays are shallow equal.
 */
function isShallowEqualArrays(a, b) {
  if (a === b) {
    return true;
  }
  if (a.length !== b.length) {
    return false;
  }
  for (let i = 0, len = a.length; i < len; i++) {
    if (a[i] !== b[i]) {
      return false;
    }
  }
  return true;
}
//# sourceMappingURL=arrays.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+is-shallow-equal@4.47.0/node_modules/@wordpress/is-shallow-equal/build-module/index.js
/**
 * Internal dependencies
 */





/**
 * @typedef {Record<string, any>} ComparableObject
 */

/**
 * Returns true if the two arrays or objects are shallow equal, or false
 * otherwise. Also handles primitive values, just in case.
 *
 * @param {unknown} a First object or array to compare.
 * @param {unknown} b Second object or array to compare.
 *
 * @return {boolean} Whether the two values are shallow equal.
 */
function isShallowEqual(a, b) {
  if (a && b) {
    if (a.constructor === Object && b.constructor === Object) {
      return isShallowEqualObjects(a, b);
    } else if (Array.isArray(a) && Array.isArray(b)) {
      return isShallowEqualArrays(a, b);
    }
  }
  return a === b;
}
//# sourceMappingURL=index.js.map

/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+redux-routine@4.47.0_redux@4.2.1/node_modules/@wordpress/redux-routine/build-module/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";

// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  A: () => (/* binding */ createMiddleware)
});

;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+redux-routine@4.47.0_redux@4.2.1/node_modules/@wordpress/redux-routine/build-module/is-generator.js
/* eslint-disable jsdoc/valid-types */
/**
 * Returns true if the given object is a generator, or false otherwise.
 *
 * @see https://www.ecma-international.org/ecma-262/6.0/#sec-generator-objects
 *
 * @param {any} object Object to test.
 *
 * @return {object is Generator} Whether object is a generator.
 */
function isGenerator(object) {
  /* eslint-enable jsdoc/valid-types */
  // Check that iterator (next) and iterable (Symbol.iterator) interfaces are satisfied.
  // These checks seem to be compatible with several generator helpers as well as the native implementation.
  return !!object && typeof object[Symbol.iterator] === 'function' && typeof object.next === 'function';
}
//# sourceMappingURL=is-generator.js.map
// EXTERNAL MODULE: ../../node_modules/.pnpm/rungen@0.3.2/node_modules/rungen/dist/index.js
var dist = __webpack_require__("../../node_modules/.pnpm/rungen@0.3.2/node_modules/rungen/dist/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/is-promise@4.0.0/node_modules/is-promise/index.mjs
var is_promise = __webpack_require__("../../node_modules/.pnpm/is-promise@4.0.0/node_modules/is-promise/index.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/is-plain-object@5.0.0/node_modules/is-plain-object/dist/is-plain-object.mjs
var is_plain_object = __webpack_require__("../../node_modules/.pnpm/is-plain-object@5.0.0/node_modules/is-plain-object/dist/is-plain-object.mjs");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+redux-routine@4.47.0_redux@4.2.1/node_modules/@wordpress/redux-routine/build-module/is-action.js
/**
 * External dependencies
 */


/* eslint-disable jsdoc/valid-types */
/**
 * Returns true if the given object quacks like an action.
 *
 * @param {any} object Object to test
 *
 * @return {object is import('redux').AnyAction}  Whether object is an action.
 */
function isAction(object) {
  return (0,is_plain_object/* isPlainObject */.Q)(object) && typeof object.type === 'string';
}

/**
 * Returns true if the given object quacks like an action and has a specific
 * action type
 *
 * @param {unknown} object       Object to test
 * @param {string}  expectedType The expected type for the action.
 *
 * @return {object is import('redux').AnyAction} Whether object is an action and is of specific type.
 */
function isActionOfType(object, expectedType) {
  /* eslint-enable jsdoc/valid-types */
  return isAction(object) && object.type === expectedType;
}
//# sourceMappingURL=is-action.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+redux-routine@4.47.0_redux@4.2.1/node_modules/@wordpress/redux-routine/build-module/runtime.js
/**
 * External dependencies
 */



/**
 * Internal dependencies
 */


/**
 * Create a co-routine runtime.
 *
 * @param controls Object of control handlers.
 * @param dispatch Unhandled action dispatch.
 */
function createRuntime(controls = {}, dispatch) {
  const rungenControls = Object.entries(controls).map(([actionType, control]) => (value, next, iterate, yieldNext, yieldError) => {
    if (!isActionOfType(value, actionType)) {
      return false;
    }
    const routine = control(value);
    if ((0,is_promise/* default */.A)(routine)) {
      // Async control routine awaits resolution.
      routine.then(yieldNext, yieldError);
    } else {
      yieldNext(routine);
    }
    return true;
  });
  const unhandledActionControl = (value, next) => {
    if (!isAction(value)) {
      return false;
    }
    dispatch(value);
    next();
    return true;
  };
  rungenControls.push(unhandledActionControl);
  const rungenRuntime = (0,dist.create)(rungenControls);
  return action => new Promise((resolve, reject) => rungenRuntime(action, result => {
    if (isAction(result)) {
      dispatch(result);
    }
    resolve(result);
  }, reject));
}
//# sourceMappingURL=runtime.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+redux-routine@4.47.0_redux@4.2.1/node_modules/@wordpress/redux-routine/build-module/index.js
/**
 * Internal dependencies
 */



/**
 * Creates a Redux middleware, given an object of controls where each key is an
 * action type for which to act upon, the value a function which returns either
 * a promise which is to resolve when evaluation of the action should continue,
 * or a value. The value or resolved promise value is assigned on the return
 * value of the yield assignment. If the control handler returns undefined, the
 * execution is not continued.
 *
 * @param {Record<string, (value: import('redux').AnyAction) => Promise<boolean> | boolean>} controls Object of control handlers.
 *
 * @return {import('redux').Middleware} Co-routine runtime
 */
function createMiddleware(controls = {}) {
  return store => {
    const runtime = createRuntime(controls, store.dispatch);
    return next => action => {
      if (!isGenerator(action)) {
        return next(action);
      }
      return runtime(action);
    };
  };
}
//# sourceMappingURL=index.js.map

/***/ }),

/***/ "../../node_modules/.pnpm/turbo-combine-reducers@1.0.2/node_modules/turbo-combine-reducers/index.js":
/***/ ((module) => {

function combineReducers( reducers ) {
	var keys = Object.keys( reducers ),
		getNextState;

	getNextState = ( function() {
		var fn, i, key;

		fn = 'return {';
		for ( i = 0; i < keys.length; i++ ) {
			// Rely on Quoted escaping of JSON.stringify with guarantee that
			// each member of Object.keys is a string.
			//
			// "If Type(value) is String, then return the result of calling the
			// abstract operation Quote with argument value. [...] The abstract
			// operation Quote(value) wraps a String value in double quotes and
			// escapes characters within it."
			//
			// https://www.ecma-international.org/ecma-262/5.1/#sec-15.12.3
			key = JSON.stringify( keys[ i ] );

			fn += key + ':r[' + key + '](s[' + key + '],a),';
		}
		fn += '}';

		return new Function( 'r,s,a', fn );
	} )();

	return function combinedReducer( state, action ) {
		var nextState, i, key;

		// Assumed changed if initial state.
		if ( state === undefined ) {
			return getNextState( reducers, {}, action );
		}

		nextState = getNextState( reducers, state, action );

		// Determine whether state has changed.
		i = keys.length;
		while ( i-- ) {
			key = keys[ i ];
			if ( state[ key ] !== nextState[ key ] ) {
				// Return immediately if a changed value is encountered.
				return nextState;
			}
		}

		return state;
	};
}

module.exports = combineReducers;


/***/ })

}]);