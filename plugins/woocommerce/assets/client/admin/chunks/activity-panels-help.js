(window["__wcAdmin_webpackJsonp"] = window["__wcAdmin_webpackJsonp"] || []).push([[5],{

/***/ 503:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(9);
/* harmony import */ var _wordpress_primitives__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__);


/**
 * WordPress dependencies
 */

const chevronRight = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__["SVG"], {
  xmlns: "http://www.w3.org/2000/svg",
  viewBox: "0 0 24 24"
}, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__["Path"], {
  d: "M10.6 6L9.4 7l4.6 5-4.6 5 1.2 1 5.4-6z"
}));
/* harmony default export */ __webpack_exports__["a"] = (chevronRight);
//# sourceMappingURL=chevron-right.js.map

/***/ }),

/***/ 556:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(7);
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(classnames__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(1);
/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(prop_types__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _woocommerce_experimental__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(19);
/* harmony import */ var _woocommerce_experimental__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_experimental__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(22);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_components__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _style_scss__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(557);
/* harmony import */ var _style_scss__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_style_scss__WEBPACK_IMPORTED_MODULE_5__);


/**
 * External dependencies
 */





/**
 * Internal dependencies
 */



class ActivityHeader extends _wordpress_element__WEBPACK_IMPORTED_MODULE_0__["Component"] {
  render() {
    const {
      className,
      menu,
      subtitle,
      title,
      unreadMessages
    } = this.props;
    const cardClassName = classnames__WEBPACK_IMPORTED_MODULE_1___default()({
      'woocommerce-layout__inbox-panel-header': subtitle,
      'woocommerce-layout__activity-panel-header': !subtitle
    }, className);
    const countUnread = unreadMessages ? unreadMessages : 0;
    return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("div", {
      className: cardClassName
    }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("div", {
      className: "woocommerce-layout__inbox-title"
    }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_woocommerce_experimental__WEBPACK_IMPORTED_MODULE_3__["Text"], {
      size: 16,
      weight: 600,
      color: "#23282d"
    }, title), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_woocommerce_experimental__WEBPACK_IMPORTED_MODULE_3__["Text"], {
      variant: "button",
      weight: "600",
      size: "14",
      lineHeight: "20px"
    }, countUnread > 0 && Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("span", {
      className: "woocommerce-layout__inbox-badge"
    }, unreadMessages))), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("div", {
      className: "woocommerce-layout__inbox-subtitle"
    }, subtitle && Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_woocommerce_experimental__WEBPACK_IMPORTED_MODULE_3__["Text"], {
      variant: "body.small",
      size: "14",
      lineHeight: "20px"
    }, subtitle)), menu && Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("div", {
      className: "woocommerce-layout__activity-panel-header-menu"
    }, menu));
  }

}

ActivityHeader.propTypes = {
  className: prop_types__WEBPACK_IMPORTED_MODULE_2___default.a.string,
  unreadMessages: prop_types__WEBPACK_IMPORTED_MODULE_2___default.a.number,
  title: prop_types__WEBPACK_IMPORTED_MODULE_2___default.a.string.isRequired,
  subtitle: prop_types__WEBPACK_IMPORTED_MODULE_2___default.a.string,
  menu: prop_types__WEBPACK_IMPORTED_MODULE_2___default.a.shape({
    type: prop_types__WEBPACK_IMPORTED_MODULE_2___default.a.oneOf([_woocommerce_components__WEBPACK_IMPORTED_MODULE_4__["EllipsisMenu"]])
  })
};
/* harmony default export */ __webpack_exports__["a"] = (ActivityHeader);

/***/ }),

/***/ 557:
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),

/***/ 666:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
// ESM COMPAT FLAG
__webpack_require__.r(__webpack_exports__);

// EXPORTS
__webpack_require__.d(__webpack_exports__, "SETUP_TASK_HELP_ITEMS_FILTER", function() { return /* binding */ SETUP_TASK_HELP_ITEMS_FILTER; });
__webpack_require__.d(__webpack_exports__, "HelpPanel", function() { return /* binding */ HelpPanel; });

// EXTERNAL MODULE: external ["wp","element"]
var external_wp_element_ = __webpack_require__(0);

// EXTERNAL MODULE: external ["wp","i18n"]
var external_wp_i18n_ = __webpack_require__(2);

// EXTERNAL MODULE: external ["wc","experimental"]
var external_wc_experimental_ = __webpack_require__(19);

// EXTERNAL MODULE: external ["wp","data"]
var external_wp_data_ = __webpack_require__(8);

// EXTERNAL MODULE: external ["wp","hooks"]
var external_wp_hooks_ = __webpack_require__(27);

// EXTERNAL MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/@wordpress+icons@6.3.0/node_modules/@wordpress/icons/build-module/icon/index.js
var icon = __webpack_require__(116);

// EXTERNAL MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/@wordpress+icons@6.3.0/node_modules/@wordpress/icons/build-module/library/page.js
var page = __webpack_require__(532);

// EXTERNAL MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/@wordpress+icons@6.3.0/node_modules/@wordpress/icons/build-module/library/chevron-right.js
var chevron_right = __webpack_require__(503);

// EXTERNAL MODULE: external "lodash"
var external_lodash_ = __webpack_require__(5);

// EXTERNAL MODULE: external ["wc","components"]
var external_wc_components_ = __webpack_require__(22);

// EXTERNAL MODULE: external ["wc","data"]
var external_wc_data_ = __webpack_require__(12);

// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/@babel+runtime@7.17.2/node_modules/@babel/runtime/helpers/esm/defineProperty.js
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
// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/@babel+runtime@7.17.2/node_modules/@babel/runtime/helpers/esm/objectSpread2.js


function ownKeys(object, enumerableOnly) {
  var keys = Object.keys(object);

  if (Object.getOwnPropertySymbols) {
    var symbols = Object.getOwnPropertySymbols(object);
    enumerableOnly && (symbols = symbols.filter(function (sym) {
      return Object.getOwnPropertyDescriptor(object, sym).enumerable;
    })), keys.push.apply(keys, symbols);
  }

  return keys;
}

function _objectSpread2(target) {
  for (var i = 1; i < arguments.length; i++) {
    var source = null != arguments[i] ? arguments[i] : {};
    i % 2 ? ownKeys(Object(source), !0).forEach(function (key) {
      _defineProperty(target, key, source[key]);
    }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys(Object(source)).forEach(function (key) {
      Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key));
    });
  }

  return target;
}
// CONCATENATED MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/redux@4.1.2/node_modules/redux/es/redux.js


/**
 * Adapted from React: https://github.com/facebook/react/blob/master/packages/shared/formatProdErrorMessage.js
 *
 * Do not require this module directly! Use normal throw error calls. These messages will be replaced with error codes
 * during build.
 * @param {number} code
 */
function formatProdErrorMessage(code) {
  return "Minified Redux error #" + code + "; visit https://redux.js.org/Errors?code=" + code + " for the full message or " + 'use the non-minified dev environment for full errors. ';
}

// Inlined version of the `symbol-observable` polyfill
var $$observable = (function () {
  return typeof Symbol === 'function' && Symbol.observable || '@@observable';
})();

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

// Inlined / shortened version of `kindOf` from https://github.com/jonschlinkert/kind-of
function miniKindOf(val) {
  if (val === void 0) return 'undefined';
  if (val === null) return 'null';
  var type = typeof val;

  switch (type) {
    case 'boolean':
    case 'string':
    case 'number':
    case 'symbol':
    case 'function':
      {
        return type;
      }
  }

  if (Array.isArray(val)) return 'array';
  if (isDate(val)) return 'date';
  if (isError(val)) return 'error';
  var constructorName = ctorName(val);

  switch (constructorName) {
    case 'Symbol':
    case 'Promise':
    case 'WeakMap':
    case 'WeakSet':
    case 'Map':
    case 'Set':
      return constructorName;
  } // other


  return type.slice(8, -1).toLowerCase().replace(/\s/g, '');
}

function ctorName(val) {
  return typeof val.constructor === 'function' ? val.constructor.name : null;
}

function isError(val) {
  return val instanceof Error || typeof val.message === 'string' && val.constructor && typeof val.constructor.stackTraceLimit === 'number';
}

function isDate(val) {
  if (val instanceof Date) return true;
  return typeof val.toDateString === 'function' && typeof val.getDate === 'function' && typeof val.setDate === 'function';
}

function kindOf(val) {
  var typeOfVal = typeof val;

  if (false) {}

  return typeOfVal;
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

function redux_createStore(reducer, preloadedState, enhancer) {
  var _ref2;

  if (typeof preloadedState === 'function' && typeof enhancer === 'function' || typeof enhancer === 'function' && typeof arguments[3] === 'function') {
    throw new Error( true ? formatProdErrorMessage(0) : undefined);
  }

  if (typeof preloadedState === 'function' && typeof enhancer === 'undefined') {
    enhancer = preloadedState;
    preloadedState = undefined;
  }

  if (typeof enhancer !== 'undefined') {
    if (typeof enhancer !== 'function') {
      throw new Error( true ? formatProdErrorMessage(1) : undefined);
    }

    return enhancer(redux_createStore)(reducer, preloadedState);
  }

  if (typeof reducer !== 'function') {
    throw new Error( true ? formatProdErrorMessage(2) : undefined);
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
      throw new Error( true ? formatProdErrorMessage(3) : undefined);
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
      throw new Error( true ? formatProdErrorMessage(4) : undefined);
    }

    if (isDispatching) {
      throw new Error( true ? formatProdErrorMessage(5) : undefined);
    }

    var isSubscribed = true;
    ensureCanMutateNextListeners();
    nextListeners.push(listener);
    return function unsubscribe() {
      if (!isSubscribed) {
        return;
      }

      if (isDispatching) {
        throw new Error( true ? formatProdErrorMessage(6) : undefined);
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
      throw new Error( true ? formatProdErrorMessage(7) : undefined);
    }

    if (typeof action.type === 'undefined') {
      throw new Error( true ? formatProdErrorMessage(8) : undefined);
    }

    if (isDispatching) {
      throw new Error( true ? formatProdErrorMessage(9) : undefined);
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
      throw new Error( true ? formatProdErrorMessage(10) : undefined);
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
          throw new Error( true ? formatProdErrorMessage(11) : undefined);
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
    }, _ref[$$observable] = function () {
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
  }, _ref2[$$observable] = observable, _ref2;
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

function getUnexpectedStateShapeWarningMessage(inputState, reducers, action, unexpectedKeyCache) {
  var reducerKeys = Object.keys(reducers);
  var argumentName = action && action.type === ActionTypes.INIT ? 'preloadedState argument passed to createStore' : 'previous state received by the reducer';

  if (reducerKeys.length === 0) {
    return 'Store does not have a valid reducer. Make sure the argument passed ' + 'to combineReducers is an object whose values are reducers.';
  }

  if (!isPlainObject(inputState)) {
    return "The " + argumentName + " has unexpected type of \"" + kindOf(inputState) + "\". Expected argument to be an object with the following " + ("keys: \"" + reducerKeys.join('", "') + "\"");
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
      throw new Error( true ? formatProdErrorMessage(12) : undefined);
    }

    if (typeof reducer(undefined, {
      type: ActionTypes.PROBE_UNKNOWN_ACTION()
    }) === 'undefined') {
      throw new Error( true ? formatProdErrorMessage(13) : undefined);
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
        var actionType = action && action.type;
        throw new Error( true ? formatProdErrorMessage(14) : undefined);
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
    throw new Error( true ? formatProdErrorMessage(16) : undefined);
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
        throw new Error( true ? formatProdErrorMessage(15) : undefined);
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
      return _objectSpread2(_objectSpread2({}, store), {}, {
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



// EXTERNAL MODULE: external ["wc","tracks"]
var external_wc_tracks_ = __webpack_require__(17);

// EXTERNAL MODULE: ./client/activity-panel/activity-header/index.js
var activity_header = __webpack_require__(556);

// EXTERNAL MODULE: ./client/dashboard/utils.js
var utils = __webpack_require__(80);

// CONCATENATED MODULE: ./client/activity-panel/panels/help.js


/**
 * External dependencies
 */











/**
 * Internal dependencies
 */



const SETUP_TASK_HELP_ITEMS_FILTER = 'woocommerce_admin_setup_task_help_items';

function getHomeItems() {
  return [{
    title: Object(external_wp_i18n_["__"])('Get Support', 'woocommerce-admin'),
    link: 'https://woocommerce.com/my-account/create-a-ticket/?utm_medium=product'
  }, {
    title: Object(external_wp_i18n_["__"])('Home Screen', 'woocommerce-admin'),
    link: 'https://woocommerce.com/document/home-screen/?utm_medium=product'
  }, {
    title: Object(external_wp_i18n_["__"])('Inbox', 'woocommerce-admin'),
    link: 'https://woocommerce.com/document/home-screen/?utm_medium=product#section-2'
  }, {
    title: Object(external_wp_i18n_["__"])('Stats Overview', 'woocommerce-admin'),
    link: 'https://woocommerce.com/document/home-screen/?utm_medium=product#section-4'
  }, {
    title: Object(external_wp_i18n_["__"])('Store Management', 'woocommerce-admin'),
    link: 'https://woocommerce.com/document/home-screen/?utm_medium=product#section-5'
  }, {
    title: Object(external_wp_i18n_["__"])('Store Setup Checklist', 'woocommerce-admin'),
    link: 'https://woocommerce.com/document/woocommerce-setup-wizard?utm_medium=product#store-setup-checklist'
  }];
}

function getAppearanceItems() {
  return [{
    title: Object(external_wp_i18n_["__"])('Showcase your products and tailor your shopping experience using Blocks', 'woocommerce-admin'),
    link: 'https://woocommerce.com/document/woocommerce-blocks/?utm_source=help_panel&utm_medium=product'
  }, {
    title: Object(external_wp_i18n_["__"])('Manage Store Notice, Catalog View and Product Images', 'woocommerce-admin'),
    link: 'https://woocommerce.com/document/woocommerce-customizer/?utm_source=help_panel&utm_medium=product'
  }, {
    title: Object(external_wp_i18n_["__"])('How to choose and change a theme', 'woocommerce-admin'),
    link: 'https://woocommerce.com/document/choose-change-theme/?utm_source=help_panel&utm_medium=product'
  }];
}

function getMarketingItems(props) {
  const {
    activePlugins
  } = props;
  return [activePlugins.includes('mailpoet') && {
    title: Object(external_wp_i18n_["__"])('Get started with Mailpoet', 'woocommerce-admin'),
    link: 'https://kb.mailpoet.com/category/114-getting-started'
  }, activePlugins.includes('google-listings-and-ads') && {
    title: Object(external_wp_i18n_["__"])('Set up Google Listing & Ads', 'woocommerce-admin'),
    link: 'https://woocommerce.com/document/google-listings-and-ads/?utm_medium=product#get-started'
  }, activePlugins.includes('mailchimp-for-woocommerce') && {
    title: Object(external_wp_i18n_["__"])('Connect Mailchimp for WooCommerce', 'woocommerce-admin'),
    link: 'https://mailchimp.com/help/connect-or-disconnect-mailchimp-for-woocommerce/'
  }, activePlugins.includes('creative-mail-by-constant-contact') && {
    title: Object(external_wp_i18n_["__"])('Set up Creative Mail for WooCommerce', 'woocommerce-admin'),
    link: 'https://app.creativemail.com/kb/help/WooCommerce'
  }].filter(Boolean);
}

function getPaymentGatewaySuggestions(props) {
  const {
    paymentGatewaySuggestions
  } = props;
  return [{
    title: Object(external_wp_i18n_["__"])('Which Payment Option is Right for Me?', 'woocommerce-admin'),
    link: 'https://woocommerce.com/document/premium-payment-gateway-extensions/?utm_source=help_panel&utm_medium=product'
  }, paymentGatewaySuggestions.woocommerce_payments && {
    title: Object(external_wp_i18n_["__"])('WooCommerce Payments Start Up Guide', 'woocommerce-admin'),
    link: 'https://woocommerce.com/document/payments/?utm_source=help_panel&utm_medium=product'
  }, paymentGatewaySuggestions.woocommerce_payments && {
    title: Object(external_wp_i18n_["__"])('WooCommerce Payments FAQs', 'woocommerce-admin'),
    link: 'https://woocommerce.com/documentation/woocommerce-payments/woocommerce-payments-faqs/?utm_source=help_panel&utm_medium=product'
  }, paymentGatewaySuggestions.stripe && {
    title: Object(external_wp_i18n_["__"])('Stripe Setup and Configuration', 'woocommerce-admin'),
    link: 'https://woocommerce.com/document/stripe/?utm_source=help_panel&utm_medium=product'
  }, paymentGatewaySuggestions['ppcp-gateway'] && {
    title: Object(external_wp_i18n_["__"])('PayPal Checkout Setup and Configuration', 'woocommerce-admin'),
    link: 'https://woocommerce.com/document/2-0/woocommerce-paypal-payments/?utm_medium=product#section-3'
  }, paymentGatewaySuggestions.square_credit_card && {
    title: Object(external_wp_i18n_["__"])('Square - Get started', 'woocommerce-admin'),
    link: 'https://woocommerce.com/document/woocommerce-square/?utm_source=help_panel&utm_medium=product'
  }, paymentGatewaySuggestions.kco && {
    title: Object(external_wp_i18n_["__"])('Klarna - Introduction', 'woocommerce-admin'),
    link: 'https://woocommerce.com/document/klarna-checkout/?utm_source=help_panel&utm_medium=product'
  }, paymentGatewaySuggestions.klarna_payments && {
    title: Object(external_wp_i18n_["__"])('Klarna - Introduction', 'woocommerce-admin'),
    link: 'https://woocommerce.com/document/klarna-payments/?utm_source=help_panel&utm_medium=product'
  }, paymentGatewaySuggestions.payfast && {
    title: Object(external_wp_i18n_["__"])('PayFast Setup and Configuration', 'woocommerce-admin'),
    link: 'https://woocommerce.com/document/payfast-payment-gateway/?utm_source=help_panel&utm_medium=product'
  }, paymentGatewaySuggestions.eway && {
    title: Object(external_wp_i18n_["__"])('Eway Setup and Configuration', 'woocommerce-admin'),
    link: 'https://woocommerce.com/document/eway/?utm_source=help_panel&utm_medium=product'
  }, {
    title: Object(external_wp_i18n_["__"])('Direct Bank Transfer (BACS)', 'woocommerce-admin'),
    link: 'https://woocommerce.com/document/bacs/?utm_source=help_panel&utm_medium=product'
  }, {
    title: Object(external_wp_i18n_["__"])('Cash on Delivery', 'woocommerce-admin'),
    link: 'https://woocommerce.com/document/cash-on-delivery/?utm_source=help_panel&utm_medium=product'
  }].filter(Boolean);
}

function getProductsItems() {
  return [{
    title: Object(external_wp_i18n_["__"])('Adding and Managing Products', 'woocommerce-admin'),
    link: 'https://woocommerce.com/document/managing-products/?utm_source=help_panel&utm_medium=product'
  }, {
    title: Object(external_wp_i18n_["__"])('Import products using the CSV Importer and Exporter', 'woocommerce-admin'),
    link: 'https://woocommerce.com/document/product-csv-importer-exporter/?utm_source=help_panel&utm_medium=product'
  }, {
    title: Object(external_wp_i18n_["__"])('Migrate products using Cart2Cart', 'woocommerce-admin'),
    link: 'https://woocommerce.com/products/cart2cart/?utm_source=help_panel&utm_medium=product'
  }, {
    title: Object(external_wp_i18n_["__"])('Learn more about setting up products', 'woocommerce-admin'),
    link: 'https://woocommerce.com/documentation/plugins/woocommerce/getting-started/setup-products/?utm_source=help_panel&utm_medium=product'
  }];
}

function getShippingItems(_ref) {
  let {
    activePlugins,
    countryCode
  } = _ref;
  const showWCS = countryCode === 'US' && !activePlugins.includes('woocommerce-services');
  return [{
    title: Object(external_wp_i18n_["__"])('Setting up Shipping Zones', 'woocommerce-admin'),
    link: 'https://woocommerce.com/document/setting-up-shipping-zones/?utm_source=help_panel&utm_medium=product'
  }, {
    title: Object(external_wp_i18n_["__"])('Core Shipping Options', 'woocommerce-admin'),
    link: 'https://woocommerce.com/documentation/plugins/woocommerce/getting-started/shipping/core-shipping-options/?utm_source=help_panel&utm_medium=product'
  }, {
    title: Object(external_wp_i18n_["__"])('Product Shipping Classes', 'woocommerce-admin'),
    link: 'https://woocommerce.com/document/product-shipping-classes/?utm_source=help_panel&utm_medium=product'
  }, showWCS && {
    title: Object(external_wp_i18n_["__"])('WooCommerce Shipping setup and configuration', 'woocommerce-admin'),
    link: 'https://woocommerce.com/document/woocommerce-shipping-and-tax/?utm_source=help_panel&utm_medium=product#section-3'
  }, {
    title: Object(external_wp_i18n_["__"])('Learn more about configuring your shipping settings', 'woocommerce-admin'),
    link: 'https://woocommerce.com/document/plugins/woocommerce/getting-started/shipping/?utm_source=help_panel&utm_medium=product'
  }].filter(Boolean);
}

function getTaxItems(props) {
  const {
    countryCode,
    taskLists
  } = props;
  const tasks = taskLists.reduce((acc, taskList) => [...acc, ...taskList.tasks], []);
  const task = tasks.find(t => t.id === 'tax');

  if (!task) {
    return;
  }

  const {
    additionalData
  } = task;
  const {
    woocommerceTaxCountries = [],
    taxJarActivated
  } = additionalData;
  const showWCS = !taxJarActivated && // WCS integration doesn't work with the official TaxJar plugin.
  woocommerceTaxCountries.includes(countryCode);
  return [{
    title: Object(external_wp_i18n_["__"])('Setting up Taxes in WooCommerce', 'woocommerce-admin'),
    link: 'https://woocommerce.com/document/setting-up-taxes-in-woocommerce/?utm_source=help_panel&utm_medium=product'
  }, showWCS && {
    title: Object(external_wp_i18n_["__"])('Automated Tax calculation using WooCommerce Tax', 'woocommerce-admin'),
    link: 'https://woocommerce.com/document/woocommerce-services/?utm_source=help_panel&utm_medium=product#section-10'
  }].filter(Boolean);
}

function getItems(props) {
  const {
    taskName
  } = props;

  switch (taskName) {
    case 'products':
      return getProductsItems();

    case 'appearance':
      return getAppearanceItems();

    case 'shipping':
      return getShippingItems(props);

    case 'tax':
      return getTaxItems(props);

    case 'payments':
      return getPaymentGatewaySuggestions(props);

    case 'marketing':
      return getMarketingItems(props);

    default:
      return getHomeItems();
  }
}

function handleOnItemClick(props, event) {
  const {
    taskName
  } = props; // event isn't initially set when triggering link with the keyboard.

  if (!event) {
    return;
  }

  props.recordEvent('help_panel_click', {
    task_name: taskName || 'homescreen',
    link: event.currentTarget.href
  });
}

function getListItems(props) {
  const itemsByType = getItems(props);
  const genericDocsLink = {
    title: Object(external_wp_i18n_["__"])('WooCommerce Docs', 'woocommerce-admin'),
    link: 'https://woocommerce.com/documentation/?utm_source=help_panel&utm_medium=product'
  };
  itemsByType.push(genericDocsLink);
  /**
   * Filter an array of help items for the setup task.
   *
   * @filter woocommerce_admin_setup_task_help_items
   * @param {Array.<Object>} items Array items object based on task.
   * @param {('products'|'appearance'|'shipping'|'tax'|'payments'|'marketing')} task url query parameters.
   * @param {Object} props React component props.
   */

  const filteredItems = Object(external_wp_hooks_["applyFilters"])(SETUP_TASK_HELP_ITEMS_FILTER, itemsByType, props.taskName, props); // Filter out items that aren't objects without `title` and `link` properties.

  let validatedItems = Array.isArray(filteredItems) ? filteredItems.filter(item => item instanceof Object && item.title && item.link) : []; // Default empty array to the generic docs link.

  if (!validatedItems.length) {
    validatedItems = [genericDocsLink];
  }

  const onClick = Object(external_lodash_["partial"])(handleOnItemClick, props);
  return validatedItems.map(item => ({
    title: Object(external_wp_element_["createElement"])(external_wc_experimental_["Text"], {
      as: "div",
      variant: "button",
      weight: "600",
      size: "14",
      lineHeight: "20px"
    }, item.title),
    before: Object(external_wp_element_["createElement"])(icon["a" /* default */], {
      icon: page["a" /* default */]
    }),
    after: Object(external_wp_element_["createElement"])(icon["a" /* default */], {
      icon: chevron_right["a" /* default */]
    }),
    linkType: 'external',
    target: '_blank',
    href: item.link,
    onClick
  }));
}

const HelpPanel = props => {
  const {
    taskName
  } = props;
  Object(external_wp_element_["useEffect"])(() => {
    props.recordEvent('help_panel_open', {
      task_name: taskName || 'homescreen'
    });
  }, [taskName]);
  const listItems = getListItems(props);
  return Object(external_wp_element_["createElement"])(external_wp_element_["Fragment"], null, Object(external_wp_element_["createElement"])(activity_header["a" /* default */], {
    title: Object(external_wp_i18n_["__"])('Documentation', 'woocommerce-admin')
  }), Object(external_wp_element_["createElement"])(external_wc_components_["Section"], null, Object(external_wp_element_["createElement"])(external_wc_components_["List"], {
    items: listItems,
    className: "woocommerce-quick-links__list"
  })));
};
HelpPanel.defaultProps = {
  recordEvent: external_wc_tracks_["recordEvent"]
};
/* harmony default export */ var help = __webpack_exports__["default"] = (compose(Object(external_wp_data_["withSelect"])(select => {
  const {
    getSettings
  } = select(external_wc_data_["SETTINGS_STORE_NAME"]);
  const {
    getActivePlugins
  } = select(external_wc_data_["PLUGINS_STORE_NAME"]);
  const {
    general: generalSettings = {}
  } = getSettings('general');
  const activePlugins = getActivePlugins();
  const paymentGatewaySuggestions = select(external_wc_data_["ONBOARDING_STORE_NAME"]).getPaymentGatewaySuggestions().reduce((suggestions, suggestion) => {
    const {
      id
    } = suggestion;
    suggestions[id] = true;
    return suggestions;
  }, {});
  const taskLists = select(external_wc_data_["ONBOARDING_STORE_NAME"]).getTaskLists();
  const countryCode = Object(utils["b" /* getCountryCode */])(generalSettings.woocommerce_default_country);
  return {
    activePlugins,
    countryCode,
    paymentGatewaySuggestions,
    taskLists
  };
}))(HelpPanel));

/***/ })

}]);