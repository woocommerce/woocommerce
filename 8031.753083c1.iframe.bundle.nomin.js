"use strict";
(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[8031],{

/***/ "../../node_modules/.pnpm/@wordpress+data@10.33.1_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/data/build-module/default-registry.js"
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (/* binding */ default_registry_default)
/* harmony export */ });
/* harmony import */ var _registry__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+data@10.33.1_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/data/build-module/registry.js");

var default_registry_default = (0,_registry__WEBPACK_IMPORTED_MODULE_0__/* .createRegistry */ .I)();

//# sourceMappingURL=default-registry.js.map


/***/ },

/***/ "../../node_modules/.pnpm/@wordpress+data@10.33.1_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/data/build-module/dispatch.js"
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   J: () => (/* binding */ dispatch)
/* harmony export */ });
/* harmony import */ var _default_registry__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+data@10.33.1_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/data/build-module/default-registry.js");

function dispatch(storeNameOrDescriptor) {
  return _default_registry__WEBPACK_IMPORTED_MODULE_0__/* ["default"] */ .A.dispatch(storeNameOrDescriptor);
}

//# sourceMappingURL=dispatch.js.map


/***/ },

/***/ "../../node_modules/.pnpm/@wordpress+data@10.33.1_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/data/build-module/index.js"
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   kz: () => (/* binding */ register)
/* harmony export */ });
/* unused harmony exports combineReducers, registerGenericStore, registerStore, resolveSelect, subscribe, suspendSelect, use */
/* unused harmony import specifier */ var combineReducersModule;
/* harmony import */ var _default_registry__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+data@10.33.1_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/data/build-module/default-registry.js");

















const combineReducers = (/* unused pure expression or super */ null && (combineReducersModule));
const resolveSelect = _default_registry__WEBPACK_IMPORTED_MODULE_0__/* ["default"] */ .A.resolveSelect;
const suspendSelect = _default_registry__WEBPACK_IMPORTED_MODULE_0__/* ["default"] */ .A.suspendSelect;
const subscribe = _default_registry__WEBPACK_IMPORTED_MODULE_0__/* ["default"] */ .A.subscribe;
const registerGenericStore = _default_registry__WEBPACK_IMPORTED_MODULE_0__/* ["default"] */ .A.registerGenericStore;
const registerStore = _default_registry__WEBPACK_IMPORTED_MODULE_0__/* ["default"] */ .A.registerStore;
const use = _default_registry__WEBPACK_IMPORTED_MODULE_0__/* ["default"] */ .A.use;
const register = _default_registry__WEBPACK_IMPORTED_MODULE_0__/* ["default"] */ .A.register;

//# sourceMappingURL=index.js.map


/***/ },

/***/ "../../node_modules/.pnpm/@wordpress+data@10.33.1_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/data/build-module/lock-unlock.js"
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

/* harmony import */ var _wordpress_private_apis__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+private-apis@1.48.1/node_modules/@wordpress/private-apis/build-module/implementation.mjs");

const { lock, unlock } = (0,_wordpress_private_apis__WEBPACK_IMPORTED_MODULE_0__/* .__dangerousOptInToUnstableAPIsOnlyForCoreModules */ .yf)(
  "I acknowledge private features are not for use in themes or plugins and doing so will break in the next version of WordPress.",
  "@wordpress/data"
);

//# sourceMappingURL=lock-unlock.js.map

/* harmony export */ __webpack_require__.d(__webpack_exports__, [
/* harmony export */   "T", 0, /* binding */ unlock,
/* harmony export */   "s", 0, /* binding */ lock
/* harmony export */ ]);


/***/ },

/***/ "../../node_modules/.pnpm/@wordpress+data@10.33.1_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/data/build-module/redux-store/index.js"
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  A: () => (/* binding */ createReduxStore)
});

// UNUSED EXPORTS: combineReducers

// MODULE: ../../node_modules/.pnpm/equivalent-key-map@0.2.2/node_modules/equivalent-key-map/equivalent-key-map.js
var equivalent_key_map_namespaceFn = /*#__PURE__*/__webpack_require__.cw((module) => {


function _typeof(obj) {
  if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") {
    _typeof = function (obj) {
      return typeof obj;
    };
  } else {
    _typeof = function (obj) {
      return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj;
    };
  }

  return _typeof(obj);
}

function _classCallCheck(instance, Constructor) {
  if (!(instance instanceof Constructor)) {
    throw new TypeError("Cannot call a class as a function");
  }
}

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

/**
 * Given an instance of EquivalentKeyMap, returns its internal value pair tuple
 * for a key, if one exists. The tuple members consist of the last reference
 * value for the key (used in efficient subsequent lookups) and the value
 * assigned for the key at the leaf node.
 *
 * @param {EquivalentKeyMap} instance EquivalentKeyMap instance.
 * @param {*} key                     The key for which to return value pair.
 *
 * @return {?Array} Value pair, if exists.
 */
function getValuePair(instance, key) {
  var _map = instance._map,
      _arrayTreeMap = instance._arrayTreeMap,
      _objectTreeMap = instance._objectTreeMap; // Map keeps a reference to the last object-like key used to set the
  // value, which can be used to shortcut immediately to the value.

  if (_map.has(key)) {
    return _map.get(key);
  } // Sort keys to ensure stable retrieval from tree.


  var properties = Object.keys(key).sort(); // Tree by type to avoid conflicts on numeric object keys, empty value.

  var map = Array.isArray(key) ? _arrayTreeMap : _objectTreeMap;

  for (var i = 0; i < properties.length; i++) {
    var property = properties[i];
    map = map.get(property);

    if (map === undefined) {
      return;
    }

    var propertyValue = key[property];
    map = map.get(propertyValue);

    if (map === undefined) {
      return;
    }
  }

  var valuePair = map.get('_ekm_value');

  if (!valuePair) {
    return;
  } // If reached, it implies that an object-like key was set with another
  // reference, so delete the reference and replace with the current.


  _map.delete(valuePair[0]);

  valuePair[0] = key;
  map.set('_ekm_value', valuePair);

  _map.set(key, valuePair);

  return valuePair;
}
/**
 * Variant of a Map object which enables lookup by equivalent (deeply equal)
 * object and array keys.
 */


var EquivalentKeyMap =
/*#__PURE__*/
function () {
  /**
   * Constructs a new instance of EquivalentKeyMap.
   *
   * @param {Iterable.<*>} iterable Initial pair of key, value for map.
   */
  function EquivalentKeyMap(iterable) {
    _classCallCheck(this, EquivalentKeyMap);

    this.clear();

    if (iterable instanceof EquivalentKeyMap) {
      // Map#forEach is only means of iterating with support for IE11.
      var iterablePairs = [];
      iterable.forEach(function (value, key) {
        iterablePairs.push([key, value]);
      });
      iterable = iterablePairs;
    }

    if (iterable != null) {
      for (var i = 0; i < iterable.length; i++) {
        this.set(iterable[i][0], iterable[i][1]);
      }
    }
  }
  /**
   * Accessor property returning the number of elements.
   *
   * @return {number} Number of elements.
   */


  _createClass(EquivalentKeyMap, [{
    key: "set",

    /**
     * Add or update an element with a specified key and value.
     *
     * @param {*} key   The key of the element to add.
     * @param {*} value The value of the element to add.
     *
     * @return {EquivalentKeyMap} Map instance.
     */
    value: function set(key, value) {
      // Shortcut non-object-like to set on internal Map.
      if (key === null || _typeof(key) !== 'object') {
        this._map.set(key, value);

        return this;
      } // Sort keys to ensure stable assignment into tree.


      var properties = Object.keys(key).sort();
      var valuePair = [key, value]; // Tree by type to avoid conflicts on numeric object keys, empty value.

      var map = Array.isArray(key) ? this._arrayTreeMap : this._objectTreeMap;

      for (var i = 0; i < properties.length; i++) {
        var property = properties[i];

        if (!map.has(property)) {
          map.set(property, new EquivalentKeyMap());
        }

        map = map.get(property);
        var propertyValue = key[property];

        if (!map.has(propertyValue)) {
          map.set(propertyValue, new EquivalentKeyMap());
        }

        map = map.get(propertyValue);
      } // If an _ekm_value exists, there was already an equivalent key. Before
      // overriding, ensure that the old key reference is removed from map to
      // avoid memory leak of accumulating equivalent keys. This is, in a
      // sense, a poor man's WeakMap, while still enabling iterability.


      var previousValuePair = map.get('_ekm_value');

      if (previousValuePair) {
        this._map.delete(previousValuePair[0]);
      }

      map.set('_ekm_value', valuePair);

      this._map.set(key, valuePair);

      return this;
    }
    /**
     * Returns a specified element.
     *
     * @param {*} key The key of the element to return.
     *
     * @return {?*} The element associated with the specified key or undefined
     *              if the key can't be found.
     */

  }, {
    key: "get",
    value: function get(key) {
      // Shortcut non-object-like to get from internal Map.
      if (key === null || _typeof(key) !== 'object') {
        return this._map.get(key);
      }

      var valuePair = getValuePair(this, key);

      if (valuePair) {
        return valuePair[1];
      }
    }
    /**
     * Returns a boolean indicating whether an element with the specified key
     * exists or not.
     *
     * @param {*} key The key of the element to test for presence.
     *
     * @return {boolean} Whether an element with the specified key exists.
     */

  }, {
    key: "has",
    value: function has(key) {
      if (key === null || _typeof(key) !== 'object') {
        return this._map.has(key);
      } // Test on the _presence_ of the pair, not its value, as even undefined
      // can be a valid member value for a key.


      return getValuePair(this, key) !== undefined;
    }
    /**
     * Removes the specified element.
     *
     * @param {*} key The key of the element to remove.
     *
     * @return {boolean} Returns true if an element existed and has been
     *                   removed, or false if the element does not exist.
     */

  }, {
    key: "delete",
    value: function _delete(key) {
      if (!this.has(key)) {
        return false;
      } // This naive implementation will leave orphaned child trees. A better
      // implementation should traverse and remove orphans.


      this.set(key, undefined);
      return true;
    }
    /**
     * Executes a provided function once per each key/value pair, in insertion
     * order.
     *
     * @param {Function} callback Function to execute for each element.
     * @param {*}        thisArg  Value to use as `this` when executing
     *                            `callback`.
     */

  }, {
    key: "forEach",
    value: function forEach(callback) {
      var _this = this;

      var thisArg = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : this;

      this._map.forEach(function (value, key) {
        // Unwrap value from object-like value pair.
        if (key !== null && _typeof(key) === 'object') {
          value = value[1];
        }

        callback.call(thisArg, value, key, _this);
      });
    }
    /**
     * Removes all elements.
     */

  }, {
    key: "clear",
    value: function clear() {
      this._map = new Map();
      this._arrayTreeMap = new Map();
      this._objectTreeMap = new Map();
    }
  }, {
    key: "size",
    get: function get() {
      return this._map.size;
    }
  }]);

  return EquivalentKeyMap;
}();

module.exports = EquivalentKeyMap;

});

// EXTERNAL MODULE: ../../node_modules/.pnpm/rungen@0.3.2/node_modules/rungen/dist/controls/async.js
var async_namespaceFn = () => {
	return __webpack_require__("../../node_modules/.pnpm/rungen@0.3.2/node_modules/rungen/dist/controls/async.js");
};

// EXTERNAL MODULE: ../../node_modules/.pnpm/rungen@0.3.2/node_modules/rungen/dist/controls/wrap.js
var wrap_namespaceFn = () => {
	return __webpack_require__("../../node_modules/.pnpm/rungen@0.3.2/node_modules/rungen/dist/controls/wrap.js");
};

// EXTERNAL MODULE: ../../node_modules/.pnpm/rungen@0.3.2/node_modules/rungen/dist/create.js
var create_namespaceFn = () => {
	return __webpack_require__("../../node_modules/.pnpm/rungen@0.3.2/node_modules/rungen/dist/create.js");
};

// MODULE: ../../node_modules/.pnpm/rungen@0.3.2/node_modules/rungen/dist/index.js
var dist_namespaceFn = /*#__PURE__*/__webpack_require__.cw((__unused_webpack_module, exports) => {


Object.defineProperty(exports, "__esModule", ({
  value: true
}));
exports.wrapControls = exports.asyncControls = exports.create = undefined;

var _helpers = (helpers_namespaceFn());

Object.keys(_helpers).forEach(function (key) {
  if (key === "default") return;
  Object.defineProperty(exports, key, {
    enumerable: true,
    get: function get() {
      return _helpers[key];
    }
  });
});

var _create = (create_namespaceFn());

var _create2 = _interopRequireDefault(_create);

var _async = (async_namespaceFn());

var _async2 = _interopRequireDefault(_async);

var _wrap = (wrap_namespaceFn());

var _wrap2 = _interopRequireDefault(_wrap);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

exports.create = _create2.default;
exports.asyncControls = _async2.default;
exports.wrapControls = _wrap2.default;
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/rungen@0.3.2/node_modules/rungen/dist/utils/helpers.js
var helpers_namespaceFn = () => {
	return __webpack_require__("../../node_modules/.pnpm/rungen@0.3.2/node_modules/rungen/dist/utils/helpers.js");
};

// NAMESPACE OBJECT (decoupled): ../../node_modules/.pnpm/@wordpress+data@10.33.1_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/data/build-module/redux-store/metadata/selectors.js
var selectors_namespaceObject = {};
__webpack_require__.r(selectors_namespaceObject);
__webpack_require__.d(selectors_namespaceObject, {
  countSelectorsByStatus: () => (countSelectorsByStatus),
  getCachedResolvers: () => (getCachedResolvers),
  getIsResolving: () => (getIsResolving),
  getResolutionError: () => (getResolutionError),
  getResolutionState: () => (getResolutionState),
  hasFinishedResolution: () => (hasFinishedResolution),
  hasResolutionFailed: () => (hasResolutionFailed),
  hasResolvingSelectors: () => (hasResolvingSelectors),
  hasStartedResolution: () => (hasStartedResolution),
  isResolving: () => (isResolving)
});

// NAMESPACE OBJECT (decoupled): ../../node_modules/.pnpm/@wordpress+data@10.33.1_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/data/build-module/redux-store/metadata/actions.js
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

;// ../../node_modules/.pnpm/redux@5.0.1/node_modules/redux/dist/redux.mjs
// src/utils/formatProdErrorMessage.ts
function formatProdErrorMessage(code) {
  return `Minified Redux error #${code}; visit https://redux.js.org/Errors?code=${code} for the full message or use the non-minified dev environment for full errors. `;
}

// src/utils/symbol-observable.ts
var $$observable = /* @__PURE__ */ (() => typeof Symbol === "function" && Symbol.observable || "@@observable")();
var symbol_observable_default = $$observable;

// src/utils/actionTypes.ts
var randomString = () => Math.random().toString(36).substring(7).split("").join(".");
var ActionTypes = {
  INIT: `@@redux/INIT${/* @__PURE__ */ randomString()}`,
  REPLACE: `@@redux/REPLACE${/* @__PURE__ */ randomString()}`,
  PROBE_UNKNOWN_ACTION: () => `@@redux/PROBE_UNKNOWN_ACTION${randomString()}`
};
var actionTypes_default = ActionTypes;

// src/utils/isPlainObject.ts
function isPlainObject(obj) {
  if (typeof obj !== "object" || obj === null)
    return false;
  let proto = obj;
  while (Object.getPrototypeOf(proto) !== null) {
    proto = Object.getPrototypeOf(proto);
  }
  return Object.getPrototypeOf(obj) === proto || Object.getPrototypeOf(obj) === null;
}

// src/utils/kindOf.ts
function miniKindOf(val) {
  if (val === void 0)
    return "undefined";
  if (val === null)
    return "null";
  const type = typeof val;
  switch (type) {
    case "boolean":
    case "string":
    case "number":
    case "symbol":
    case "function": {
      return type;
    }
  }
  if (Array.isArray(val))
    return "array";
  if (isDate(val))
    return "date";
  if (isError(val))
    return "error";
  const constructorName = ctorName(val);
  switch (constructorName) {
    case "Symbol":
    case "Promise":
    case "WeakMap":
    case "WeakSet":
    case "Map":
    case "Set":
      return constructorName;
  }
  return Object.prototype.toString.call(val).slice(8, -1).toLowerCase().replace(/\s/g, "");
}
function ctorName(val) {
  return typeof val.constructor === "function" ? val.constructor.name : null;
}
function isError(val) {
  return val instanceof Error || typeof val.message === "string" && val.constructor && typeof val.constructor.stackTraceLimit === "number";
}
function isDate(val) {
  if (val instanceof Date)
    return true;
  return typeof val.toDateString === "function" && typeof val.getDate === "function" && typeof val.setDate === "function";
}
function kindOf(val) {
  let typeOfVal = typeof val;
  if (false) // removed by dead control flow
{}
  return typeOfVal;
}

// src/createStore.ts
function createStore(reducer, preloadedState, enhancer) {
  if (typeof reducer !== "function") {
    throw new Error( true ? formatProdErrorMessage(2) : 0);
  }
  if (typeof preloadedState === "function" && typeof enhancer === "function" || typeof enhancer === "function" && typeof arguments[3] === "function") {
    throw new Error( true ? formatProdErrorMessage(0) : 0);
  }
  if (typeof preloadedState === "function" && typeof enhancer === "undefined") {
    enhancer = preloadedState;
    preloadedState = void 0;
  }
  if (typeof enhancer !== "undefined") {
    if (typeof enhancer !== "function") {
      throw new Error( true ? formatProdErrorMessage(1) : 0);
    }
    return enhancer(createStore)(reducer, preloadedState);
  }
  let currentReducer = reducer;
  let currentState = preloadedState;
  let currentListeners = /* @__PURE__ */ new Map();
  let nextListeners = currentListeners;
  let listenerIdCounter = 0;
  let isDispatching = false;
  function ensureCanMutateNextListeners() {
    if (nextListeners === currentListeners) {
      nextListeners = /* @__PURE__ */ new Map();
      currentListeners.forEach((listener, key) => {
        nextListeners.set(key, listener);
      });
    }
  }
  function getState() {
    if (isDispatching) {
      throw new Error( true ? formatProdErrorMessage(3) : 0);
    }
    return currentState;
  }
  function subscribe(listener) {
    if (typeof listener !== "function") {
      throw new Error( true ? formatProdErrorMessage(4) : 0);
    }
    if (isDispatching) {
      throw new Error( true ? formatProdErrorMessage(5) : 0);
    }
    let isSubscribed = true;
    ensureCanMutateNextListeners();
    const listenerId = listenerIdCounter++;
    nextListeners.set(listenerId, listener);
    return function unsubscribe() {
      if (!isSubscribed) {
        return;
      }
      if (isDispatching) {
        throw new Error( true ? formatProdErrorMessage(6) : 0);
      }
      isSubscribed = false;
      ensureCanMutateNextListeners();
      nextListeners.delete(listenerId);
      currentListeners = null;
    };
  }
  function dispatch(action) {
    if (!isPlainObject(action)) {
      throw new Error( true ? formatProdErrorMessage(7) : 0);
    }
    if (typeof action.type === "undefined") {
      throw new Error( true ? formatProdErrorMessage(8) : 0);
    }
    if (typeof action.type !== "string") {
      throw new Error( true ? formatProdErrorMessage(17) : 0);
    }
    if (isDispatching) {
      throw new Error( true ? formatProdErrorMessage(9) : 0);
    }
    try {
      isDispatching = true;
      currentState = currentReducer(currentState, action);
    } finally {
      isDispatching = false;
    }
    const listeners = currentListeners = nextListeners;
    listeners.forEach((listener) => {
      listener();
    });
    return action;
  }
  function replaceReducer(nextReducer) {
    if (typeof nextReducer !== "function") {
      throw new Error( true ? formatProdErrorMessage(10) : 0);
    }
    currentReducer = nextReducer;
    dispatch({
      type: actionTypes_default.REPLACE
    });
  }
  function observable() {
    const outerSubscribe = subscribe;
    return {
      /**
       * The minimal observable subscription method.
       * @param observer Any object that can be used as an observer.
       * The observer object should have a `next` method.
       * @returns An object with an `unsubscribe` method that can
       * be used to unsubscribe the observable from the store, and prevent further
       * emission of values from the observable.
       */
      subscribe(observer) {
        if (typeof observer !== "object" || observer === null) {
          throw new Error( true ? formatProdErrorMessage(11) : 0);
        }
        function observeState() {
          const observerAsObserver = observer;
          if (observerAsObserver.next) {
            observerAsObserver.next(getState());
          }
        }
        observeState();
        const unsubscribe = outerSubscribe(observeState);
        return {
          unsubscribe
        };
      },
      [symbol_observable_default]() {
        return this;
      }
    };
  }
  dispatch({
    type: actionTypes_default.INIT
  });
  const store = {
    dispatch,
    subscribe,
    getState,
    replaceReducer,
    [symbol_observable_default]: observable
  };
  return store;
}
function legacy_createStore(reducer, preloadedState, enhancer) {
  return createStore(reducer, preloadedState, enhancer);
}

// src/utils/warning.ts
function warning(message) {
  if (typeof console !== "undefined" && typeof console.error === "function") {
    console.error(message);
  }
  try {
    throw new Error(message);
  } catch (e) {
  }
}

// src/combineReducers.ts
function getUnexpectedStateShapeWarningMessage(inputState, reducers, action, unexpectedKeyCache) {
  const reducerKeys = Object.keys(reducers);
  const argumentName = action && action.type === actionTypes_default.INIT ? "preloadedState argument passed to createStore" : "previous state received by the reducer";
  if (reducerKeys.length === 0) {
    return "Store does not have a valid reducer. Make sure the argument passed to combineReducers is an object whose values are reducers.";
  }
  if (!isPlainObject(inputState)) {
    return `The ${argumentName} has unexpected type of "${kindOf(inputState)}". Expected argument to be an object with the following keys: "${reducerKeys.join('", "')}"`;
  }
  const unexpectedKeys = Object.keys(inputState).filter((key) => !reducers.hasOwnProperty(key) && !unexpectedKeyCache[key]);
  unexpectedKeys.forEach((key) => {
    unexpectedKeyCache[key] = true;
  });
  if (action && action.type === actionTypes_default.REPLACE)
    return;
  if (unexpectedKeys.length > 0) {
    return `Unexpected ${unexpectedKeys.length > 1 ? "keys" : "key"} "${unexpectedKeys.join('", "')}" found in ${argumentName}. Expected to find one of the known reducer keys instead: "${reducerKeys.join('", "')}". Unexpected keys will be ignored.`;
  }
}
function assertReducerShape(reducers) {
  Object.keys(reducers).forEach((key) => {
    const reducer = reducers[key];
    const initialState = reducer(void 0, {
      type: actionTypes_default.INIT
    });
    if (typeof initialState === "undefined") {
      throw new Error( true ? formatProdErrorMessage(12) : 0);
    }
    if (typeof reducer(void 0, {
      type: actionTypes_default.PROBE_UNKNOWN_ACTION()
    }) === "undefined") {
      throw new Error( true ? formatProdErrorMessage(13) : 0);
    }
  });
}
function combineReducers(reducers) {
  const reducerKeys = Object.keys(reducers);
  const finalReducers = {};
  for (let i = 0; i < reducerKeys.length; i++) {
    const key = reducerKeys[i];
    if (false) // removed by dead control flow
{}
    if (typeof reducers[key] === "function") {
      finalReducers[key] = reducers[key];
    }
  }
  const finalReducerKeys = Object.keys(finalReducers);
  let unexpectedKeyCache;
  if (false) // removed by dead control flow
{}
  let shapeAssertionError;
  try {
    assertReducerShape(finalReducers);
  } catch (e) {
    shapeAssertionError = e;
  }
  return function combination(state = {}, action) {
    if (shapeAssertionError) {
      throw shapeAssertionError;
    }
    if (false) // removed by dead control flow
{}
    let hasChanged = false;
    const nextState = {};
    for (let i = 0; i < finalReducerKeys.length; i++) {
      const key = finalReducerKeys[i];
      const reducer = finalReducers[key];
      const previousStateForKey = state[key];
      const nextStateForKey = reducer(previousStateForKey, action);
      if (typeof nextStateForKey === "undefined") {
        const actionType = action && action.type;
        throw new Error( true ? formatProdErrorMessage(14) : 0);
      }
      nextState[key] = nextStateForKey;
      hasChanged = hasChanged || nextStateForKey !== previousStateForKey;
    }
    hasChanged = hasChanged || finalReducerKeys.length !== Object.keys(state).length;
    return hasChanged ? nextState : state;
  };
}

// src/bindActionCreators.ts
function bindActionCreator(actionCreator, dispatch) {
  return function(...args) {
    return dispatch(actionCreator.apply(this, args));
  };
}
function bindActionCreators(actionCreators, dispatch) {
  if (typeof actionCreators === "function") {
    return bindActionCreator(actionCreators, dispatch);
  }
  if (typeof actionCreators !== "object" || actionCreators === null) {
    throw new Error( true ? formatProdErrorMessage(16) : 0);
  }
  const boundActionCreators = {};
  for (const key in actionCreators) {
    const actionCreator = actionCreators[key];
    if (typeof actionCreator === "function") {
      boundActionCreators[key] = bindActionCreator(actionCreator, dispatch);
    }
  }
  return boundActionCreators;
}

// src/compose.ts
function compose(...funcs) {
  if (funcs.length === 0) {
    return (arg) => arg;
  }
  if (funcs.length === 1) {
    return funcs[0];
  }
  return funcs.reduce((a, b) => (...args) => a(b(...args)));
}

// src/applyMiddleware.ts
function applyMiddleware(...middlewares) {
  return (createStore2) => (reducer, preloadedState) => {
    const store = createStore2(reducer, preloadedState);
    let dispatch = () => {
      throw new Error( true ? formatProdErrorMessage(15) : 0);
    };
    const middlewareAPI = {
      getState: store.getState,
      dispatch: (action, ...args) => dispatch(action, ...args)
    };
    const chain = middlewares.map((middleware) => middleware(middlewareAPI));
    dispatch = compose(...chain)(store.dispatch);
    return {
      ...store,
      dispatch
    };
  };
}

// src/utils/isAction.ts
function isAction(action) {
  return isPlainObject(action) && "type" in action && typeof action.type === "string";
}

//# sourceMappingURL=redux.mjs.map
;// ../../node_modules/.pnpm/equivalent-key-map@0.2.2/node_modules/equivalent-key-map/equivalent-key-map.js
equivalent_key_map_namespaceFn();

function equivalent_key_map_default() { return equivalent_key_map_default.c || (equivalent_key_map_default.c = __webpack_require__.n(equivalent_key_map_namespaceFn())); }
;// ../../node_modules/.pnpm/@wordpress+redux-routine@5.57.0_redux@5.0.1/node_modules/@wordpress/redux-routine/build-module/is-generator.mjs
// packages/redux-routine/src/is-generator.ts
function isGenerator(object) {
  return !!object && typeof object[Symbol.iterator] === "function" && typeof object.next === "function";
}

//# sourceMappingURL=is-generator.mjs.map

;// ../../node_modules/.pnpm/rungen@0.3.2/node_modules/rungen/dist/index.js
dist_namespaceFn();

;// ../../node_modules/.pnpm/is-promise@4.0.0/node_modules/is-promise/index.mjs
function isPromise(obj) {
  return !!obj && (typeof obj === 'object' || typeof obj === 'function') && typeof obj.then === 'function';
}

// EXTERNAL MODULE: ../../node_modules/.pnpm/is-plain-object@5.1.0/node_modules/is-plain-object/dist/is-plain-object.mjs
var is_plain_object = __webpack_require__("../../node_modules/.pnpm/is-plain-object@5.1.0/node_modules/is-plain-object/dist/is-plain-object.mjs");
;// ../../node_modules/.pnpm/@wordpress+redux-routine@5.57.0_redux@5.0.1/node_modules/@wordpress/redux-routine/build-module/is-action.mjs
// packages/redux-routine/src/is-action.ts

function is_action_isAction(object) {
  return (0,is_plain_object/* isPlainObject */.Q)(object) && typeof object.type === "string";
}
function isActionOfType(object, expectedType) {
  return is_action_isAction(object) && object.type === expectedType;
}

//# sourceMappingURL=is-action.mjs.map

;// ../../node_modules/.pnpm/@wordpress+redux-routine@5.57.0_redux@5.0.1/node_modules/@wordpress/redux-routine/build-module/runtime.mjs
// packages/redux-routine/src/runtime.ts



function createRuntime(controls = {}, dispatch) {
  const rungenControls = Object.entries(controls).map(
    ([actionType, control]) => (value, next, iterate, yieldNext, yieldError) => {
      if (!isActionOfType(value, actionType)) {
        return false;
      }
      const routine = control(value);
      if (isPromise(routine)) {
        routine.then(yieldNext, yieldError);
      } else {
        yieldNext(routine);
      }
      return true;
    }
  );
  const unhandledActionControl = (value, next) => {
    if (!is_action_isAction(value)) {
      return false;
    }
    dispatch(value);
    next();
    return true;
  };
  rungenControls.push(unhandledActionControl);
  const rungenRuntime = (0,dist_namespaceFn().create)(rungenControls);
  return (action) => new Promise(
    (resolve, reject) => rungenRuntime(
      action,
      (result) => {
        if (is_action_isAction(result)) {
          dispatch(result);
        }
        resolve(result);
      },
      reject
    )
  );
}

//# sourceMappingURL=runtime.mjs.map

;// ../../node_modules/.pnpm/@wordpress+redux-routine@5.57.0_redux@5.0.1/node_modules/@wordpress/redux-routine/build-module/index.mjs
// packages/redux-routine/src/index.ts


function createMiddleware(controls = {}) {
  return (store) => {
    const runtime = createRuntime(controls, store.dispatch);
    return (next) => (action) => {
      if (!isGenerator(action)) {
        return next(action);
      }
      return runtime(action);
    };
  };
}

//# sourceMappingURL=index.mjs.map

// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+compose@7.33.1_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/compose/build-module/higher-order/compose.js + 1 modules
var higher_order_compose = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@7.33.1_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/compose/build-module/higher-order/compose.js");
;// ../../node_modules/.pnpm/@wordpress+data@10.33.1_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/data/build-module/redux-store/combine-reducers.js
function combine_reducers_combineReducers(reducers) {
  const keys = Object.keys(reducers);
  return function combinedReducer(state = {}, action) {
    const nextState = {};
    let hasChanged = false;
    for (const key of keys) {
      const reducer = reducers[key];
      const prevStateForKey = state[key];
      const nextStateForKey = reducer(prevStateForKey, action);
      nextState[key] = nextStateForKey;
      hasChanged = hasChanged || nextStateForKey !== prevStateForKey;
    }
    return hasChanged ? nextState : state;
  };
}

//# sourceMappingURL=combine-reducers.js.map

;// ../../node_modules/.pnpm/@wordpress+data@10.33.1_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/data/build-module/factory.js
function createRegistrySelector(registrySelector) {
  const selectorsByRegistry = /* @__PURE__ */ new WeakMap();
  const wrappedSelector = (...args) => {
    let selector = selectorsByRegistry.get(wrappedSelector.registry);
    if (!selector) {
      selector = registrySelector(wrappedSelector.registry.select);
      selectorsByRegistry.set(wrappedSelector.registry, selector);
    }
    return selector(...args);
  };
  wrappedSelector.isRegistrySelector = true;
  return wrappedSelector;
}
function createRegistryControl(registryControl) {
  registryControl.isRegistryControl = true;
  return registryControl;
}

//# sourceMappingURL=factory.js.map

;// ../../node_modules/.pnpm/@wordpress+data@10.33.1_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/data/build-module/controls.js

const SELECT = "@@data/SELECT";
const RESOLVE_SELECT = "@@data/RESOLVE_SELECT";
const DISPATCH = "@@data/DISPATCH";
function isObject(object) {
  return object !== null && typeof object === "object";
}
function controls_select(storeNameOrDescriptor, selectorName, ...args) {
  return {
    type: SELECT,
    storeKey: isObject(storeNameOrDescriptor) ? storeNameOrDescriptor.name : storeNameOrDescriptor,
    selectorName,
    args
  };
}
function resolveSelect(storeNameOrDescriptor, selectorName, ...args) {
  return {
    type: RESOLVE_SELECT,
    storeKey: isObject(storeNameOrDescriptor) ? storeNameOrDescriptor.name : storeNameOrDescriptor,
    selectorName,
    args
  };
}
function dispatch(storeNameOrDescriptor, actionName, ...args) {
  return {
    type: DISPATCH,
    storeKey: isObject(storeNameOrDescriptor) ? storeNameOrDescriptor.name : storeNameOrDescriptor,
    actionName,
    args
  };
}
const controls = (/* unused pure expression or super */ null && ({ select: controls_select, resolveSelect, dispatch }));
const builtinControls = {
  [SELECT]: createRegistryControl(
    (registry) => ({ storeKey, selectorName, args }) => registry.select(storeKey)[selectorName](...args)
  ),
  [RESOLVE_SELECT]: createRegistryControl(
    (registry) => ({ storeKey, selectorName, args }) => {
      const method = registry.select(storeKey)[selectorName].hasResolver ? "resolveSelect" : "select";
      return registry[method](storeKey)[selectorName](
        ...args
      );
    }
  ),
  [DISPATCH]: createRegistryControl(
    (registry) => ({ storeKey, actionName, args }) => registry.dispatch(storeKey)[actionName](...args)
  )
};

//# sourceMappingURL=controls.js.map

// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+data@10.33.1_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/data/build-module/lock-unlock.js
var lock_unlock = __webpack_require__("../../node_modules/.pnpm/@wordpress+data@10.33.1_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/data/build-module/lock-unlock.js");
;// ../../node_modules/.pnpm/@wordpress+data@10.33.1_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/data/build-module/promise-middleware.js

const promiseMiddleware = () => (next) => (action) => {
  if (isPromise(action)) {
    return action.then((resolvedAction) => {
      if (resolvedAction) {
        return next(resolvedAction);
      }
    });
  }
  return next(action);
};
var promise_middleware_default = promiseMiddleware;

//# sourceMappingURL=promise-middleware.js.map

;// ../../node_modules/.pnpm/@wordpress+data@10.33.1_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/data/build-module/resolvers-cache-middleware.js
const createResolversCacheMiddleware = (registry, storeName) => () => (next) => (action) => {
  const resolvers = registry.select(storeName).getCachedResolvers();
  const resolverEntries = Object.entries(resolvers);
  resolverEntries.forEach(([selectorName, resolversByArgs]) => {
    const resolver = registry.stores[storeName]?.resolvers?.[selectorName];
    if (!resolver || !resolver.shouldInvalidate) {
      return;
    }
    resolversByArgs.forEach((value, args) => {
      if (value === void 0) {
        return;
      }
      if (value.status !== "finished" && value.status !== "error") {
        return;
      }
      if (!resolver.shouldInvalidate(action, ...args)) {
        return;
      }
      registry.dispatch(storeName).invalidateResolution(selectorName, args);
    });
  });
  return next(action);
};
var resolvers_cache_middleware_default = createResolversCacheMiddleware;

//# sourceMappingURL=resolvers-cache-middleware.js.map

;// ../../node_modules/.pnpm/@wordpress+data@10.33.1_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/data/build-module/redux-store/thunk-middleware.js
function createThunkMiddleware(args) {
  return () => (next) => (action) => {
    if (typeof action === "function") {
      return action(args);
    }
    return next(action);
  };
}

//# sourceMappingURL=thunk-middleware.js.map

;// ../../node_modules/.pnpm/@wordpress+data@10.33.1_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/data/build-module/redux-store/metadata/utils.js
const onSubKey = (actionProperty) => (reducer) => (state = {}, action) => {
  const key = action[actionProperty];
  if (key === void 0) {
    return state;
  }
  const nextKeyState = reducer(state[key], action);
  if (nextKeyState === state[key]) {
    return state;
  }
  return {
    ...state,
    [key]: nextKeyState
  };
};
function selectorArgsToStateKey(args) {
  if (args === void 0 || args === null) {
    return [];
  }
  const len = args.length;
  let idx = len;
  while (idx > 0 && args[idx - 1] === void 0) {
    idx--;
  }
  return idx === len ? args : args.slice(0, idx);
}

//# sourceMappingURL=utils.js.map

;// ../../node_modules/.pnpm/@wordpress+data@10.33.1_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/data/build-module/redux-store/metadata/reducer.js


const subKeysIsResolved = onSubKey("selectorName")((state = new (equivalent_key_map_default()())(), action) => {
  switch (action.type) {
    case "START_RESOLUTION": {
      const nextState = new (equivalent_key_map_default()())(state);
      nextState.set(selectorArgsToStateKey(action.args), {
        status: "resolving"
      });
      return nextState;
    }
    case "FINISH_RESOLUTION": {
      const nextState = new (equivalent_key_map_default()())(state);
      nextState.set(selectorArgsToStateKey(action.args), {
        status: "finished"
      });
      return nextState;
    }
    case "FAIL_RESOLUTION": {
      const nextState = new (equivalent_key_map_default()())(state);
      nextState.set(selectorArgsToStateKey(action.args), {
        status: "error",
        error: action.error
      });
      return nextState;
    }
    case "START_RESOLUTIONS": {
      const nextState = new (equivalent_key_map_default()())(state);
      for (const resolutionArgs of action.args) {
        nextState.set(selectorArgsToStateKey(resolutionArgs), {
          status: "resolving"
        });
      }
      return nextState;
    }
    case "FINISH_RESOLUTIONS": {
      const nextState = new (equivalent_key_map_default()())(state);
      for (const resolutionArgs of action.args) {
        nextState.set(selectorArgsToStateKey(resolutionArgs), {
          status: "finished"
        });
      }
      return nextState;
    }
    case "FAIL_RESOLUTIONS": {
      const nextState = new (equivalent_key_map_default()())(state);
      action.args.forEach((resolutionArgs, idx) => {
        const resolutionState = {
          status: "error",
          error: void 0
        };
        const error = action.errors[idx];
        if (error) {
          resolutionState.error = error;
        }
        nextState.set(
          selectorArgsToStateKey(resolutionArgs),
          resolutionState
        );
      });
      return nextState;
    }
    case "INVALIDATE_RESOLUTION": {
      const nextState = new (equivalent_key_map_default()())(state);
      nextState.delete(selectorArgsToStateKey(action.args));
      return nextState;
    }
  }
  return state;
});
const isResolved = (state = {}, action) => {
  switch (action.type) {
    case "INVALIDATE_RESOLUTION_FOR_STORE":
      return {};
    case "INVALIDATE_RESOLUTION_FOR_STORE_SELECTOR": {
      if (action.selectorName in state) {
        const {
          [action.selectorName]: removedSelector,
          ...restState
        } = state;
        return restState;
      }
      return state;
    }
    case "START_RESOLUTION":
    case "FINISH_RESOLUTION":
    case "FAIL_RESOLUTION":
    case "START_RESOLUTIONS":
    case "FINISH_RESOLUTIONS":
    case "FAIL_RESOLUTIONS":
    case "INVALIDATE_RESOLUTION":
      return subKeysIsResolved(state, action);
  }
  return state;
};
var reducer_default = isResolved;

//# sourceMappingURL=reducer.js.map

// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+deprecated@4.33.1/node_modules/@wordpress/deprecated/build-module/index.js
var build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+deprecated@4.33.1/node_modules/@wordpress/deprecated/build-module/index.js");
;// ../../node_modules/.pnpm/rememo@4.0.2/node_modules/rememo/rememo.js
__webpack_require__.dn(rememo);


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
/* harmony default export */ function rememo(selector, getDependants) {
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
}

;// ../../node_modules/.pnpm/@wordpress+data@10.33.1_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/data/build-module/redux-store/metadata/selectors.js



function getResolutionState(state, selectorName, args) {
  const map = state[selectorName];
  if (!map) {
    return;
  }
  return map.get(selectorArgsToStateKey(args));
}
function getIsResolving(state, selectorName, args) {
  ;(0,build_module/* default */.A)("wp.data.select( store ).getIsResolving", {
    since: "6.6",
    version: "6.8",
    alternative: "wp.data.select( store ).getResolutionState"
  });
  const resolutionState = getResolutionState(state, selectorName, args);
  return resolutionState && resolutionState.status === "resolving";
}
function hasStartedResolution(state, selectorName, args) {
  return getResolutionState(state, selectorName, args) !== void 0;
}
function hasFinishedResolution(state, selectorName, args) {
  const status = getResolutionState(state, selectorName, args)?.status;
  return status === "finished" || status === "error";
}
function hasResolutionFailed(state, selectorName, args) {
  return getResolutionState(state, selectorName, args)?.status === "error";
}
function getResolutionError(state, selectorName, args) {
  const resolutionState = getResolutionState(state, selectorName, args);
  return resolutionState?.status === "error" ? resolutionState.error : null;
}
function isResolving(state, selectorName, args) {
  return getResolutionState(state, selectorName, args)?.status === "resolving";
}
function getCachedResolvers(state) {
  return state;
}
function hasResolvingSelectors(state) {
  return Object.values(state).some(
    (selectorState) => (
      /**
       * This uses the internal `_map` property of `EquivalentKeyMap` for
       * optimization purposes, since the `EquivalentKeyMap` implementation
       * does not support a `.values()` implementation.
       *
       * @see https://github.com/aduth/equivalent-key-map
       */
      Array.from(selectorState._map.values()).some(
        (resolution) => resolution[1]?.status === "resolving"
      )
    )
  );
}
const countSelectorsByStatus = rememo(
  (state) => {
    const selectorsByStatus = {};
    Object.values(state).forEach(
      (selectorState) => (
        /**
         * This uses the internal `_map` property of `EquivalentKeyMap` for
         * optimization purposes, since the `EquivalentKeyMap` implementation
         * does not support a `.values()` implementation.
         *
         * @see https://github.com/aduth/equivalent-key-map
         */
        Array.from(selectorState._map.values()).forEach(
          (resolution) => {
            const currentStatus = resolution[1]?.status ?? "error";
            if (!selectorsByStatus[currentStatus]) {
              selectorsByStatus[currentStatus] = 0;
            }
            selectorsByStatus[currentStatus]++;
          }
        )
      )
    );
    return selectorsByStatus;
  },
  (state) => [state]
);

//# sourceMappingURL=selectors.js.map

;// ../../node_modules/.pnpm/@wordpress+data@10.33.1_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/data/build-module/redux-store/metadata/actions.js
function startResolution(selectorName, args) {
  return {
    type: "START_RESOLUTION",
    selectorName,
    args
  };
}
function finishResolution(selectorName, args) {
  return {
    type: "FINISH_RESOLUTION",
    selectorName,
    args
  };
}
function failResolution(selectorName, args, error) {
  return {
    type: "FAIL_RESOLUTION",
    selectorName,
    args,
    error
  };
}
function startResolutions(selectorName, args) {
  return {
    type: "START_RESOLUTIONS",
    selectorName,
    args
  };
}
function finishResolutions(selectorName, args) {
  return {
    type: "FINISH_RESOLUTIONS",
    selectorName,
    args
  };
}
function failResolutions(selectorName, args, errors) {
  return {
    type: "FAIL_RESOLUTIONS",
    selectorName,
    args,
    errors
  };
}
function invalidateResolution(selectorName, args) {
  return {
    type: "INVALIDATE_RESOLUTION",
    selectorName,
    args
  };
}
function invalidateResolutionForStore() {
  return {
    type: "INVALIDATE_RESOLUTION_FOR_STORE"
  };
}
function invalidateResolutionForStoreSelector(selectorName) {
  return {
    type: "INVALIDATE_RESOLUTION_FOR_STORE_SELECTOR",
    selectorName
  };
}

//# sourceMappingURL=actions.js.map

;// ../../node_modules/.pnpm/@wordpress+data@10.33.1_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/data/build-module/redux-store/index.js













const trimUndefinedValues = (array) => {
  const result = [...array];
  for (let i = result.length - 1; i >= 0; i--) {
    if (result[i] === void 0) {
      result.splice(i, 1);
    }
  }
  return result;
};
const mapValues = (obj, callback) => Object.fromEntries(
  Object.entries(obj ?? {}).map(([key, value]) => [
    key,
    callback(value, key)
  ])
);
const devToolsReplacer = (key, state) => {
  if (state instanceof Map) {
    return Object.fromEntries(state);
  }
  if (state instanceof window.HTMLElement) {
    return null;
  }
  return state;
};
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
        cache[selectorName] = new (equivalent_key_map_default()())();
      }
      cache[selectorName].set(trimUndefinedValues(args), true);
    }
  };
}
function createBindingCache(getItem, bindItem) {
  const cache = /* @__PURE__ */ new WeakMap();
  return {
    get(itemName) {
      const item = getItem(itemName);
      if (!item) {
        return null;
      }
      let boundItem = cache.get(item);
      if (!boundItem) {
        boundItem = bindItem(item, itemName);
        cache.set(item, boundItem);
      }
      return boundItem;
    }
  };
}
function createPrivateProxy(publicItems, privateItems) {
  return new Proxy(publicItems, {
    get: (target, itemName) => privateItems.get(itemName) || Reflect.get(target, itemName)
  });
}
function createReduxStore(key, options) {
  const privateActions = {};
  const privateSelectors = {};
  const privateRegistrationFunctions = {
    privateActions,
    registerPrivateActions: (actions) => {
      Object.assign(privateActions, actions);
    },
    privateSelectors,
    registerPrivateSelectors: (selectors) => {
      Object.assign(privateSelectors, selectors);
    }
  };
  const storeDescriptor = {
    name: key,
    instantiate: (registry) => {
      const listeners = /* @__PURE__ */ new Set();
      const reducer = options.reducer;
      const thunkArgs = {
        registry,
        get dispatch() {
          return thunkDispatch;
        },
        get select() {
          return thunkSelect;
        },
        get resolveSelect() {
          return resolveSelectors;
        }
      };
      const store = instantiateReduxStore(
        key,
        options,
        registry,
        thunkArgs
      );
      (0,lock_unlock/* lock */.s)(store, privateRegistrationFunctions);
      const resolversCache = createResolversCache();
      function bindAction(action) {
        return (...args) => Promise.resolve(store.dispatch(action(...args)));
      }
      const actions = {
        ...mapValues(actions_namespaceObject, bindAction),
        ...mapValues(options.actions, bindAction)
      };
      const allActions = createPrivateProxy(
        actions,
        createBindingCache(
          (name) => privateActions[name],
          bindAction
        )
      );
      const thunkDispatch = new Proxy(
        (action) => store.dispatch(action),
        { get: (target, name) => allActions[name] }
      );
      (0,lock_unlock/* lock */.s)(actions, allActions);
      const resolvers = options.resolvers ? mapValues(options.resolvers, mapResolver) : {};
      function bindSelector(selector, selectorName) {
        if (selector.isRegistrySelector) {
          selector.registry = registry;
        }
        const boundSelector = (...args) => {
          args = normalize(selector, args);
          const state = store.__unstableOriginalGetState();
          if (selector.isRegistrySelector) {
            selector.registry = registry;
          }
          return selector(state.root, ...args);
        };
        boundSelector.__unstableNormalizeArgs = selector.__unstableNormalizeArgs;
        const resolver = resolvers[selectorName];
        if (!resolver) {
          boundSelector.hasResolver = false;
          return boundSelector;
        }
        return mapSelectorWithResolver(
          boundSelector,
          selectorName,
          resolver,
          store,
          resolversCache,
          boundMetadataSelectors
        );
      }
      function bindMetadataSelector(metaDataSelector) {
        const boundSelector = (selectorName, selectorArgs, ...args) => {
          if (selectorName) {
            const targetSelector = options.selectors?.[selectorName];
            if (targetSelector) {
              selectorArgs = normalize(
                targetSelector,
                selectorArgs
              );
            }
          }
          const state = store.__unstableOriginalGetState();
          return metaDataSelector(
            state.metadata,
            selectorName,
            selectorArgs,
            ...args
          );
        };
        boundSelector.hasResolver = false;
        return boundSelector;
      }
      const boundMetadataSelectors = mapValues(
        selectors_namespaceObject,
        bindMetadataSelector
      );
      const boundSelectors = mapValues(options.selectors, bindSelector);
      const selectors = {
        ...boundMetadataSelectors,
        ...boundSelectors
      };
      const boundPrivateSelectors = createBindingCache(
        (name) => privateSelectors[name],
        bindSelector
      );
      const allSelectors = createPrivateProxy(
        selectors,
        boundPrivateSelectors
      );
      for (const selectorName of Object.keys(privateSelectors)) {
        boundPrivateSelectors.get(selectorName);
      }
      const thunkSelect = new Proxy(
        (selector) => selector(store.__unstableOriginalGetState()),
        { get: (target, name) => allSelectors[name] }
      );
      (0,lock_unlock/* lock */.s)(selectors, allSelectors);
      const bindResolveSelector = mapResolveSelector(
        store,
        boundMetadataSelectors
      );
      const resolveSelectors = mapValues(
        boundSelectors,
        bindResolveSelector
      );
      const allResolveSelectors = createPrivateProxy(
        resolveSelectors,
        createBindingCache(
          (name) => boundPrivateSelectors.get(name),
          bindResolveSelector
        )
      );
      (0,lock_unlock/* lock */.s)(resolveSelectors, allResolveSelectors);
      const bindSuspendSelector = mapSuspendSelector(
        store,
        boundMetadataSelectors
      );
      const suspendSelectors = {
        ...boundMetadataSelectors,
        // no special suspense behavior
        ...mapValues(boundSelectors, bindSuspendSelector)
      };
      const allSuspendSelectors = createPrivateProxy(
        suspendSelectors,
        createBindingCache(
          (name) => boundPrivateSelectors.get(name),
          bindSuspendSelector
        )
      );
      (0,lock_unlock/* lock */.s)(suspendSelectors, allSuspendSelectors);
      const getSelectors = () => selectors;
      const getActions = () => actions;
      const getResolveSelectors = () => resolveSelectors;
      const getSuspendSelectors = () => suspendSelectors;
      store.__unstableOriginalGetState = store.getState;
      store.getState = () => store.__unstableOriginalGetState().root;
      const subscribe = store && ((listener) => {
        listeners.add(listener);
        return () => listeners.delete(listener);
      });
      let lastState = store.__unstableOriginalGetState();
      store.subscribe(() => {
        const state = store.__unstableOriginalGetState();
        const hasChanged = state !== lastState;
        lastState = state;
        if (hasChanged) {
          for (const listener of listeners) {
            listener();
          }
        }
      });
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
  (0,lock_unlock/* lock */.s)(storeDescriptor, privateRegistrationFunctions);
  return storeDescriptor;
}
function instantiateReduxStore(key, options, registry, thunkArgs) {
  const controls = {
    ...options.controls,
    ...builtinControls
  };
  const normalizedControls = mapValues(
    controls,
    (control) => control.isRegistryControl ? control(registry) : control
  );
  const middlewares = [
    resolvers_cache_middleware_default(registry, key),
    promise_middleware_default,
    createMiddleware(normalizedControls),
    createThunkMiddleware(thunkArgs)
  ];
  const enhancers = [applyMiddleware(...middlewares)];
  if (typeof window !== "undefined" && window.__REDUX_DEVTOOLS_EXTENSION__) {
    enhancers.push(
      window.__REDUX_DEVTOOLS_EXTENSION__({
        name: key,
        instanceId: key,
        serialize: {
          replacer: devToolsReplacer
        }
      })
    );
  }
  const { reducer, initialState } = options;
  const enhancedReducer = combine_reducers_combineReducers({
    metadata: reducer_default,
    root: reducer
  });
  return createStore(
    enhancedReducer,
    { root: initialState },
    (0,higher_order_compose/* default */.A)(enhancers)
  );
}
function mapResolveSelector(store, boundMetadataSelectors) {
  return (selector, selectorName) => {
    if (!selector.hasResolver) {
      return async (...args) => selector.apply(null, args);
    }
    return (...args) => new Promise((resolve, reject) => {
      const hasFinished = () => {
        return boundMetadataSelectors.hasFinishedResolution(
          selectorName,
          args
        );
      };
      const finalize = (result2) => {
        const hasFailed = boundMetadataSelectors.hasResolutionFailed(
          selectorName,
          args
        );
        if (hasFailed) {
          const error = boundMetadataSelectors.getResolutionError(
            selectorName,
            args
          );
          reject(error);
        } else {
          resolve(result2);
        }
      };
      const getResult = () => selector.apply(null, args);
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
}
function mapSuspendSelector(store, boundMetadataSelectors) {
  return (selector, selectorName) => {
    if (!selector.hasResolver) {
      return selector;
    }
    return (...args) => {
      const result = selector.apply(null, args);
      if (boundMetadataSelectors.hasFinishedResolution(
        selectorName,
        args
      )) {
        if (boundMetadataSelectors.hasResolutionFailed(
          selectorName,
          args
        )) {
          throw boundMetadataSelectors.getResolutionError(
            selectorName,
            args
          );
        }
        return result;
      }
      throw new Promise((resolve) => {
        const unsubscribe = store.subscribe(() => {
          if (boundMetadataSelectors.hasFinishedResolution(
            selectorName,
            args
          )) {
            resolve();
            unsubscribe();
          }
        });
      });
    };
  };
}
function mapResolver(resolver) {
  if (resolver.fulfill) {
    return resolver;
  }
  return {
    ...resolver,
    // Copy the enumerable properties of the resolver function.
    fulfill: resolver
    // Add the fulfill method.
  };
}
function mapSelectorWithResolver(selector, selectorName, resolver, store, resolversCache, boundMetadataSelectors) {
  function fulfillSelector(args) {
    const state = store.getState();
    if (resolversCache.isRunning(selectorName, args) || typeof resolver.isFulfilled === "function" && resolver.isFulfilled(state, ...args)) {
      return;
    }
    if (boundMetadataSelectors.hasStartedResolution(selectorName, args)) {
      return;
    }
    resolversCache.markAsRunning(selectorName, args);
    setTimeout(async () => {
      resolversCache.clear(selectorName, args);
      store.dispatch(
        startResolution(selectorName, args)
      );
      try {
        const action = resolver.fulfill(...args);
        if (action) {
          await store.dispatch(action);
        }
        store.dispatch(
          finishResolution(selectorName, args)
        );
      } catch (error) {
        store.dispatch(
          failResolution(selectorName, args, error)
        );
      }
    }, 0);
  }
  const selectorResolver = (...args) => {
    args = normalize(selector, args);
    fulfillSelector(args);
    return selector(...args);
  };
  selectorResolver.hasResolver = true;
  return selectorResolver;
}
function normalize(selector, args) {
  if (selector.__unstableNormalizeArgs && typeof selector.__unstableNormalizeArgs === "function" && args?.length) {
    return selector.__unstableNormalizeArgs(args);
  }
  return args;
}

//# sourceMappingURL=index.js.map


/***/ },

/***/ "../../node_modules/.pnpm/@wordpress+data@10.33.1_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/data/build-module/registry.js"
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  I: () => (/* binding */ createRegistry)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+deprecated@4.33.1/node_modules/@wordpress/deprecated/build-module/index.js
var build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+deprecated@4.33.1/node_modules/@wordpress/deprecated/build-module/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+data@10.33.1_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/data/build-module/redux-store/index.js + 19 modules
var redux_store = __webpack_require__("../../node_modules/.pnpm/@wordpress+data@10.33.1_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/data/build-module/redux-store/index.js");
;// ../../node_modules/.pnpm/@wordpress+data@10.33.1_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/data/build-module/store/index.js
const coreDataStore = {
  name: "core/data",
  instantiate(registry) {
    const getCoreDataSelector = (selectorName) => (key, ...args) => {
      return registry.select(key)[selectorName](...args);
    };
    const getCoreDataAction = (actionName) => (key, ...args) => {
      return registry.dispatch(key)[actionName](...args);
    };
    return {
      getSelectors() {
        return Object.fromEntries(
          [
            "getIsResolving",
            "hasStartedResolution",
            "hasFinishedResolution",
            "isResolving",
            "getCachedResolvers"
          ].map((selectorName) => [
            selectorName,
            getCoreDataSelector(selectorName)
          ])
        );
      },
      getActions() {
        return Object.fromEntries(
          [
            "startResolution",
            "finishResolution",
            "invalidateResolution",
            "invalidateResolutionForStore",
            "invalidateResolutionForStoreSelector"
          ].map((actionName) => [
            actionName,
            getCoreDataAction(actionName)
          ])
        );
      },
      subscribe() {
        return () => () => {
        };
      }
    };
  }
};
var store_default = coreDataStore;

//# sourceMappingURL=index.js.map

;// ../../node_modules/.pnpm/@wordpress+data@10.33.1_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/data/build-module/utils/emitter.js
function createEmitter() {
  let isPaused = false;
  let isPending = false;
  const listeners = /* @__PURE__ */ new Set();
  const notifyListeners = () => (
    // We use Array.from to clone the listeners Set
    // This ensures that we don't run a listener
    // that was added as a response to another listener.
    Array.from(listeners).forEach((listener) => listener())
  );
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

// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+data@10.33.1_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/data/build-module/lock-unlock.js
var lock_unlock = __webpack_require__("../../node_modules/.pnpm/@wordpress+data@10.33.1_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/data/build-module/lock-unlock.js");
;// ../../node_modules/.pnpm/@wordpress+data@10.33.1_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/data/build-module/registry.js





function getStoreName(storeNameOrDescriptor) {
  return typeof storeNameOrDescriptor === "string" ? storeNameOrDescriptor : storeNameOrDescriptor.name;
}
function createRegistry(storeConfigs = {}, parent = null) {
  const stores = {};
  const emitter = createEmitter();
  let listeningStores = null;
  function globalListener() {
    emitter.emit();
  }
  const subscribe = (listener, storeNameOrDescriptor) => {
    if (!storeNameOrDescriptor) {
      return emitter.subscribe(listener);
    }
    const storeName = getStoreName(storeNameOrDescriptor);
    const store = stores[storeName];
    if (store) {
      return store.subscribe(listener);
    }
    if (!parent) {
      return emitter.subscribe(listener);
    }
    return parent.subscribe(listener, storeNameOrDescriptor);
  };
  function select(storeNameOrDescriptor) {
    const storeName = getStoreName(storeNameOrDescriptor);
    listeningStores?.add(storeName);
    const store = stores[storeName];
    if (store) {
      return store.getSelectors();
    }
    return parent?.select(storeName);
  }
  function __unstableMarkListeningStores(callback, ref) {
    listeningStores = /* @__PURE__ */ new Set();
    try {
      return callback.call(this);
    } finally {
      ref.current = Array.from(listeningStores);
      listeningStores = null;
    }
  }
  function resolveSelect(storeNameOrDescriptor) {
    const storeName = getStoreName(storeNameOrDescriptor);
    listeningStores?.add(storeName);
    const store = stores[storeName];
    if (store) {
      return store.getResolveSelectors();
    }
    return parent && parent.resolveSelect(storeName);
  }
  function suspendSelect(storeNameOrDescriptor) {
    const storeName = getStoreName(storeNameOrDescriptor);
    listeningStores?.add(storeName);
    const store = stores[storeName];
    if (store) {
      return store.getSuspendSelectors();
    }
    return parent && parent.suspendSelect(storeName);
  }
  function dispatch(storeNameOrDescriptor) {
    const storeName = getStoreName(storeNameOrDescriptor);
    const store = stores[storeName];
    if (store) {
      return store.getActions();
    }
    return parent && parent.dispatch(storeName);
  }
  function withPlugins(attributes) {
    return Object.fromEntries(
      Object.entries(attributes).map(([key, attribute]) => {
        if (typeof attribute !== "function") {
          return [key, attribute];
        }
        return [
          key,
          function() {
            return registry[key].apply(null, arguments);
          }
        ];
      })
    );
  }
  function registerStoreInstance(name, createStore) {
    if (stores[name]) {
      console.error('Store "' + name + '" is already registered.');
      return stores[name];
    }
    const store = createStore();
    if (typeof store.getSelectors !== "function") {
      throw new TypeError("store.getSelectors must be a function");
    }
    if (typeof store.getActions !== "function") {
      throw new TypeError("store.getActions must be a function");
    }
    if (typeof store.subscribe !== "function") {
      throw new TypeError("store.subscribe must be a function");
    }
    store.emitter = createEmitter();
    const currentSubscribe = store.subscribe;
    store.subscribe = (listener) => {
      const unsubscribeFromEmitter = store.emitter.subscribe(listener);
      const unsubscribeFromStore = currentSubscribe(() => {
        if (store.emitter.isPaused) {
          store.emitter.emit();
          return;
        }
        listener();
      });
      return () => {
        unsubscribeFromStore?.();
        unsubscribeFromEmitter?.();
      };
    };
    stores[name] = store;
    store.subscribe(globalListener);
    if (parent) {
      try {
        (0,lock_unlock/* unlock */.T)(store.store).registerPrivateActions(
          (0,lock_unlock/* unlock */.T)(parent).privateActionsOf(name)
        );
        (0,lock_unlock/* unlock */.T)(store.store).registerPrivateSelectors(
          (0,lock_unlock/* unlock */.T)(parent).privateSelectorsOf(name)
        );
      } catch (e) {
      }
    }
    return store;
  }
  function register(store) {
    registerStoreInstance(
      store.name,
      () => store.instantiate(registry)
    );
  }
  function registerGenericStore(name, store) {
    ;(0,build_module/* default */.A)("wp.data.registerGenericStore", {
      since: "5.9",
      alternative: "wp.data.register( storeDescriptor )"
    });
    registerStoreInstance(name, () => store);
  }
  function registerStore(storeName, options) {
    if (!options.reducer) {
      throw new TypeError("Must specify store reducer");
    }
    const store = registerStoreInstance(
      storeName,
      () => (0,redux_store/* default */.A)(storeName, options).instantiate(registry)
    );
    return store.store;
  }
  function batch(callback) {
    if (emitter.isPaused) {
      callback();
      return;
    }
    emitter.pause();
    Object.values(stores).forEach((store) => store.emitter.pause());
    try {
      callback();
    } finally {
      emitter.resume();
      Object.values(stores).forEach(
        (store) => store.emitter.resume()
      );
    }
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
    __unstableMarkListeningStores
  };
  function use(plugin, options) {
    if (!plugin) {
      return;
    }
    registry = {
      ...registry,
      ...plugin(registry, options)
    };
    return registry;
  }
  registry.register(store_default);
  for (const [name, config] of Object.entries(storeConfigs)) {
    registry.register((0,redux_store/* default */.A)(name, config));
  }
  if (parent) {
    parent.subscribe(globalListener);
  }
  const registryWithPlugins = withPlugins(registry);
  (0,lock_unlock/* lock */.s)(registryWithPlugins, {
    privateActionsOf: (name) => {
      try {
        return (0,lock_unlock/* unlock */.T)(stores[name].store).privateActions;
      } catch (e) {
        return {};
      }
    },
    privateSelectorsOf: (name) => {
      try {
        return (0,lock_unlock/* unlock */.T)(stores[name].store).privateSelectors;
      } catch (e) {
        return {};
      }
    }
  });
  return registryWithPlugins;
}

//# sourceMappingURL=registry.js.map


/***/ },

/***/ "../../node_modules/.pnpm/@wordpress+deprecated@4.33.1/node_modules/@wordpress/deprecated/build-module/index.js"
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (/* binding */ deprecated)
/* harmony export */ });
/* unused harmony export logged */
/* harmony import */ var _wordpress_hooks__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+hooks@4.57.0/node_modules/@wordpress/hooks/build-module/index.mjs");

const logged = /* @__PURE__ */ Object.create(null);
function deprecated(feature, options = {}) {
  const { since, version, alternative, plugin, link, hint } = options;
  const pluginMessage = plugin ? ` from ${plugin}` : "";
  const sinceMessage = since ? ` since version ${since}` : "";
  const versionMessage = version ? ` and will be removed${pluginMessage} in version ${version}` : "";
  const useInsteadMessage = alternative ? ` Please use ${alternative} instead.` : "";
  const linkMessage = link ? ` See: ${link}` : "";
  const hintMessage = hint ? ` Note: ${hint}` : "";
  const message = `${feature} is deprecated${sinceMessage}${versionMessage}.${useInsteadMessage}${linkMessage}${hintMessage}`;
  if (message in logged) {
    return;
  }
  ;(0,_wordpress_hooks__WEBPACK_IMPORTED_MODULE_0__/* .doAction */ .Eo)("deprecated", feature, options, message);
  console.warn(message);
  logged[message] = true;
}

//# sourceMappingURL=index.js.map


/***/ },

/***/ "../../node_modules/.pnpm/@wordpress+private-apis@1.48.1/node_modules/@wordpress/private-apis/build-module/implementation.mjs"
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   yf: () => (/* binding */ __dangerousOptInToUnstableAPIsOnlyForCoreModules)
/* harmony export */ });
/* unused harmony exports allowCoreModule, resetAllowedCoreModules */
// packages/private-apis/src/implementation.ts
var CORE_MODULES_USING_PRIVATE_APIS = [
  "@wordpress/admin-ui",
  "@wordpress/api-fetch",
  "@wordpress/block-directory",
  "@wordpress/block-editor",
  "@wordpress/block-library",
  "@wordpress/blocks",
  "@wordpress/boot",
  "@wordpress/commands",
  "@wordpress/compose",
  "@wordpress/connectors",
  "@wordpress/workflows",
  "@wordpress/components",
  "@wordpress/content-types",
  "@wordpress/core-commands",
  "@wordpress/core-data",
  "@wordpress/customize-widgets",
  "@wordpress/data",
  "@wordpress/edit-post",
  "@wordpress/edit-site",
  "@wordpress/edit-widgets",
  "@wordpress/editor",
  "@wordpress/font-list-route",
  "@wordpress/format-library",
  "@wordpress/patterns",
  "@wordpress/preferences",
  "@wordpress/reusable-blocks",
  "@wordpress/rich-text",
  "@wordpress/route",
  "@wordpress/router",
  "@wordpress/routes",
  "@wordpress/storybook",
  "@wordpress/sync",
  "@wordpress/theme",
  "@wordpress/dataviews",
  "@wordpress/fields",
  "@wordpress/lazy-editor",
  "@wordpress/media-editor",
  "@wordpress/media-utils",
  "@wordpress/upload-media",
  "@wordpress/global-styles-ui",
  "@wordpress/ui",
  "@wordpress/views"
];
var requiredConsent = "I acknowledge private features are not for use in themes or plugins and doing so will break in the next version of WordPress.";
var __dangerousOptInToUnstableAPIsOnlyForCoreModules = (consent, moduleName) => {
  if (!CORE_MODULES_USING_PRIVATE_APIS.includes(moduleName)) {
    throw new Error(
      `You tried to opt-in to unstable APIs as module "${moduleName}". This feature is only for JavaScript modules shipped with WordPress core. Please do not use it in plugins and themes as the unstable APIs will be removed without a warning. If you ignore this error and depend on unstable features, your product will inevitably break on one of the next WordPress releases.`
    );
  }
  if (consent !== requiredConsent) {
    throw new Error(
      `You tried to opt-in to unstable APIs without confirming you know the consequences. This feature is only for JavaScript modules shipped with WordPress core. Please do not use it in plugins and themes as the unstable APIs will removed without a warning. If you ignore this error and depend on unstable features, your product will inevitably break on the next WordPress release.`
    );
  }
  return {
    lock,
    unlock
  };
};
function lock(object, privateData) {
  if (!object) {
    throw new Error("Cannot lock an undefined object.");
  }
  const _object = object;
  if (!(__private in _object)) {
    _object[__private] = {};
  }
  lockedData.set(_object[__private], privateData);
}
function unlock(object) {
  if (!object) {
    throw new Error("Cannot unlock an undefined object.");
  }
  const _object = object;
  if (!(__private in _object)) {
    throw new Error(
      "Cannot unlock an object that was not locked before. "
    );
  }
  return lockedData.get(_object[__private]);
}
var lockedData = /* @__PURE__ */ new WeakMap();
var __private = /* @__PURE__ */ Symbol("Private API ID");
function allowCoreModule(name) {
  CORE_MODULES_USING_PRIVATE_APIS.push(name);
}
function resetAllowedCoreModules() {
  while (CORE_MODULES_USING_PRIVATE_APIS.length) {
    CORE_MODULES_USING_PRIVATE_APIS.pop();
  }
}

//# sourceMappingURL=implementation.mjs.map


/***/ },

/***/ "../../node_modules/.pnpm/rungen@0.3.2/node_modules/rungen/dist/controls/async.js"
(__unused_webpack_module, exports, __webpack_require__) {



Object.defineProperty(exports, "__esModule", ({
  value: true
}));
exports.race = exports.join = exports.fork = exports.promise = undefined;

var _is = __webpack_require__("../../node_modules/.pnpm/rungen@0.3.2/node_modules/rungen/dist/utils/is.js");

var _is2 = _interopRequireDefault(_is);

var _helpers = __webpack_require__("../../node_modules/.pnpm/rungen@0.3.2/node_modules/rungen/dist/utils/helpers.js");

var _dispatcher = __webpack_require__("../../node_modules/.pnpm/rungen@0.3.2/node_modules/rungen/dist/utils/dispatcher.js");

var _dispatcher2 = _interopRequireDefault(_dispatcher);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

var promise = exports.promise = function promise(value, next, rungen, yieldNext, raiseNext) {
  if (!_is2.default.promise(value)) return false;
  value.then(next, raiseNext);
  return true;
};

var forkedTasks = new Map();
var fork = exports.fork = function fork(value, next, rungen) {
  if (!_is2.default.fork(value)) return false;
  var task = Symbol('fork');
  var dispatcher = (0, _dispatcher2.default)();
  forkedTasks.set(task, dispatcher);
  rungen(value.iterator.apply(null, value.args), function (result) {
    return dispatcher.dispatch(result);
  }, function (err) {
    return dispatcher.dispatch((0, _helpers.error)(err));
  });
  var unsubscribe = dispatcher.subscribe(function () {
    unsubscribe();
    forkedTasks.delete(task);
  });
  next(task);
  return true;
};

var join = exports.join = function join(value, next, rungen, yieldNext, raiseNext) {
  if (!_is2.default.join(value)) return false;
  var dispatcher = forkedTasks.get(value.task);
  if (!dispatcher) {
    raiseNext('join error : task not found');
  } else {
    (function () {
      var unsubscribe = dispatcher.subscribe(function (result) {
        unsubscribe();
        next(result);
      });
    })();
  }
  return true;
};

var race = exports.race = function race(value, next, rungen, yieldNext, raiseNext) {
  if (!_is2.default.race(value)) return false;
  var finished = false;
  var success = function success(result, k, v) {
    if (finished) return;
    finished = true;
    result[k] = v;
    next(result);
  };

  var fail = function fail(err) {
    if (finished) return;
    raiseNext(err);
  };
  if (_is2.default.array(value.competitors)) {
    (function () {
      var result = value.competitors.map(function () {
        return false;
      });
      value.competitors.forEach(function (competitor, index) {
        rungen(competitor, function (output) {
          return success(result, index, output);
        }, fail);
      });
    })();
  } else {
    (function () {
      var result = Object.keys(value.competitors).reduce(function (p, c) {
        p[c] = false;
        return p;
      }, {});
      Object.keys(value.competitors).forEach(function (index) {
        rungen(value.competitors[index], function (output) {
          return success(result, index, output);
        }, fail);
      });
    })();
  }
  return true;
};

var subscribe = function subscribe(value, next) {
  if (!_is2.default.subscribe(value)) return false;
  if (!_is2.default.channel(value.channel)) {
    throw new Error('the first argument of "subscribe" must be a valid channel');
  }
  var unsubscribe = value.channel.subscribe(function (ret) {
    unsubscribe && unsubscribe();
    next(ret);
  });

  return true;
};

exports["default"] = [promise, fork, join, race, subscribe];

/***/ },

/***/ "../../node_modules/.pnpm/rungen@0.3.2/node_modules/rungen/dist/controls/builtin.js"
(__unused_webpack_module, exports, __webpack_require__) {



Object.defineProperty(exports, "__esModule", ({
  value: true
}));
exports.iterator = exports.array = exports.object = exports.error = exports.any = undefined;

var _is = __webpack_require__("../../node_modules/.pnpm/rungen@0.3.2/node_modules/rungen/dist/utils/is.js");

var _is2 = _interopRequireDefault(_is);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

var any = exports.any = function any(value, next, rungen, yieldNext) {
  yieldNext(value);
  return true;
};

var error = exports.error = function error(value, next, rungen, yieldNext, raiseNext) {
  if (!_is2.default.error(value)) return false;
  raiseNext(value.error);
  return true;
};

var object = exports.object = function object(value, next, rungen, yieldNext, raiseNext) {
  if (!_is2.default.all(value) || !_is2.default.obj(value.value)) return false;
  var result = {};
  var keys = Object.keys(value.value);
  var count = 0;
  var hasError = false;
  var gotResultSuccess = function gotResultSuccess(key, ret) {
    if (hasError) return;
    result[key] = ret;
    count++;
    if (count === keys.length) {
      yieldNext(result);
    }
  };

  var gotResultError = function gotResultError(key, error) {
    if (hasError) return;
    hasError = true;
    raiseNext(error);
  };

  keys.map(function (key) {
    rungen(value.value[key], function (ret) {
      return gotResultSuccess(key, ret);
    }, function (err) {
      return gotResultError(key, err);
    });
  });

  return true;
};

var array = exports.array = function array(value, next, rungen, yieldNext, raiseNext) {
  if (!_is2.default.all(value) || !_is2.default.array(value.value)) return false;
  var result = [];
  var count = 0;
  var hasError = false;
  var gotResultSuccess = function gotResultSuccess(key, ret) {
    if (hasError) return;
    result[key] = ret;
    count++;
    if (count === value.value.length) {
      yieldNext(result);
    }
  };

  var gotResultError = function gotResultError(key, error) {
    if (hasError) return;
    hasError = true;
    raiseNext(error);
  };

  value.value.map(function (v, key) {
    rungen(v, function (ret) {
      return gotResultSuccess(key, ret);
    }, function (err) {
      return gotResultError(key, err);
    });
  });

  return true;
};

var iterator = exports.iterator = function iterator(value, next, rungen, yieldNext, raiseNext) {
  if (!_is2.default.iterator(value)) return false;
  rungen(value, next, raiseNext);
  return true;
};

exports["default"] = [error, iterator, array, object, any];

/***/ },

/***/ "../../node_modules/.pnpm/rungen@0.3.2/node_modules/rungen/dist/controls/wrap.js"
(__unused_webpack_module, exports, __webpack_require__) {



Object.defineProperty(exports, "__esModule", ({
  value: true
}));
exports.cps = exports.call = undefined;

var _is = __webpack_require__("../../node_modules/.pnpm/rungen@0.3.2/node_modules/rungen/dist/utils/is.js");

var _is2 = _interopRequireDefault(_is);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

function _toConsumableArray(arr) { if (Array.isArray(arr)) { for (var i = 0, arr2 = Array(arr.length); i < arr.length; i++) { arr2[i] = arr[i]; } return arr2; } else { return Array.from(arr); } }

var call = exports.call = function call(value, next, rungen, yieldNext, raiseNext) {
  if (!_is2.default.call(value)) return false;
  try {
    next(value.func.apply(value.context, value.args));
  } catch (err) {
    raiseNext(err);
  }
  return true;
};

var cps = exports.cps = function cps(value, next, rungen, yieldNext, raiseNext) {
  var _value$func;

  if (!_is2.default.cps(value)) return false;
  (_value$func = value.func).call.apply(_value$func, [null].concat(_toConsumableArray(value.args), [function (err, result) {
    if (err) raiseNext(err);else next(result);
  }]));
  return true;
};

exports["default"] = [call, cps];

/***/ },

/***/ "../../node_modules/.pnpm/rungen@0.3.2/node_modules/rungen/dist/create.js"
(__unused_webpack_module, exports, __webpack_require__) {



Object.defineProperty(exports, "__esModule", ({
  value: true
}));

var _builtin = __webpack_require__("../../node_modules/.pnpm/rungen@0.3.2/node_modules/rungen/dist/controls/builtin.js");

var _builtin2 = _interopRequireDefault(_builtin);

var _is = __webpack_require__("../../node_modules/.pnpm/rungen@0.3.2/node_modules/rungen/dist/utils/is.js");

var _is2 = _interopRequireDefault(_is);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

function _toConsumableArray(arr) { if (Array.isArray(arr)) { for (var i = 0, arr2 = Array(arr.length); i < arr.length; i++) { arr2[i] = arr[i]; } return arr2; } else { return Array.from(arr); } }

var create = function create() {
  var userControls = arguments.length <= 0 || arguments[0] === undefined ? [] : arguments[0];

  var controls = [].concat(_toConsumableArray(userControls), _toConsumableArray(_builtin2.default));

  var runtime = function runtime(input) {
    var success = arguments.length <= 1 || arguments[1] === undefined ? function () {} : arguments[1];
    var error = arguments.length <= 2 || arguments[2] === undefined ? function () {} : arguments[2];

    var iterate = function iterate(gen) {
      var yieldValue = function yieldValue(isError) {
        return function (ret) {
          try {
            var _ref = isError ? gen.throw(ret) : gen.next(ret);

            var value = _ref.value;
            var done = _ref.done;

            if (done) return success(value);
            next(value);
          } catch (e) {
            return error(e);
          }
        };
      };

      var next = function next(ret) {
        controls.some(function (control) {
          return control(ret, next, runtime, yieldValue(false), yieldValue(true));
        });
      };

      yieldValue(false)();
    };

    var iterator = _is2.default.iterator(input) ? input : regeneratorRuntime.mark(function _callee() {
      return regeneratorRuntime.wrap(function _callee$(_context) {
        while (1) {
          switch (_context.prev = _context.next) {
            case 0:
              _context.next = 2;
              return input;

            case 2:
              return _context.abrupt('return', _context.sent);

            case 3:
            case 'end':
              return _context.stop();
          }
        }
      }, _callee, this);
    })();

    iterate(iterator, success, error);
  };

  return runtime;
};

exports["default"] = create;

/***/ },

/***/ "../../node_modules/.pnpm/rungen@0.3.2/node_modules/rungen/dist/utils/dispatcher.js"
(__unused_webpack_module, exports) {



Object.defineProperty(exports, "__esModule", ({
  value: true
}));
var createDispatcher = function createDispatcher() {
  var listeners = [];

  return {
    subscribe: function subscribe(listener) {
      listeners.push(listener);
      return function () {
        listeners = listeners.filter(function (l) {
          return l !== listener;
        });
      };
    },
    dispatch: function dispatch(action) {
      listeners.slice().forEach(function (listener) {
        return listener(action);
      });
    }
  };
};

exports["default"] = createDispatcher;

/***/ },

/***/ "../../node_modules/.pnpm/rungen@0.3.2/node_modules/rungen/dist/utils/helpers.js"
(__unused_webpack_module, exports, __webpack_require__) {



Object.defineProperty(exports, "__esModule", ({
  value: true
}));
exports.createChannel = exports.subscribe = exports.cps = exports.apply = exports.call = exports.invoke = exports.delay = exports.race = exports.join = exports.fork = exports.error = exports.all = undefined;

var _keys = __webpack_require__("../../node_modules/.pnpm/rungen@0.3.2/node_modules/rungen/dist/utils/keys.js");

var _keys2 = _interopRequireDefault(_keys);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

var all = exports.all = function all(value) {
  return {
    type: _keys2.default.all,
    value: value
  };
};

var error = exports.error = function error(err) {
  return {
    type: _keys2.default.error,
    error: err
  };
};

var fork = exports.fork = function fork(iterator) {
  for (var _len = arguments.length, args = Array(_len > 1 ? _len - 1 : 0), _key = 1; _key < _len; _key++) {
    args[_key - 1] = arguments[_key];
  }

  return {
    type: _keys2.default.fork,
    iterator: iterator,
    args: args
  };
};

var join = exports.join = function join(task) {
  return {
    type: _keys2.default.join,
    task: task
  };
};

var race = exports.race = function race(competitors) {
  return {
    type: _keys2.default.race,
    competitors: competitors
  };
};

var delay = exports.delay = function delay(timeout) {
  return new Promise(function (resolve) {
    setTimeout(function () {
      return resolve(true);
    }, timeout);
  });
};

var invoke = exports.invoke = function invoke(func) {
  for (var _len2 = arguments.length, args = Array(_len2 > 1 ? _len2 - 1 : 0), _key2 = 1; _key2 < _len2; _key2++) {
    args[_key2 - 1] = arguments[_key2];
  }

  return {
    type: _keys2.default.call,
    func: func,
    context: null,
    args: args
  };
};

var call = exports.call = function call(func, context) {
  for (var _len3 = arguments.length, args = Array(_len3 > 2 ? _len3 - 2 : 0), _key3 = 2; _key3 < _len3; _key3++) {
    args[_key3 - 2] = arguments[_key3];
  }

  return {
    type: _keys2.default.call,
    func: func,
    context: context,
    args: args
  };
};

var apply = exports.apply = function apply(func, context, args) {
  return {
    type: _keys2.default.call,
    func: func,
    context: context,
    args: args
  };
};

var cps = exports.cps = function cps(func) {
  for (var _len4 = arguments.length, args = Array(_len4 > 1 ? _len4 - 1 : 0), _key4 = 1; _key4 < _len4; _key4++) {
    args[_key4 - 1] = arguments[_key4];
  }

  return {
    type: _keys2.default.cps,
    func: func,
    args: args
  };
};

var subscribe = exports.subscribe = function subscribe(channel) {
  return {
    type: _keys2.default.subscribe,
    channel: channel
  };
};

var createChannel = exports.createChannel = function createChannel(callback) {
  var listeners = [];
  var subscribe = function subscribe(l) {
    listeners.push(l);
    return function () {
      return listeners.splice(listeners.indexOf(l), 1);
    };
  };
  var next = function next(val) {
    return listeners.forEach(function (l) {
      return l(val);
    });
  };
  callback(next);

  return {
    subscribe: subscribe
  };
};

/***/ },

/***/ "../../node_modules/.pnpm/rungen@0.3.2/node_modules/rungen/dist/utils/is.js"
(__unused_webpack_module, exports, __webpack_require__) {



Object.defineProperty(exports, "__esModule", ({
  value: true
}));

var _typeof = typeof Symbol === "function" && typeof Symbol.iterator === "symbol" ? function (obj) { return typeof obj; } : function (obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol ? "symbol" : typeof obj; };

var _keys = __webpack_require__("../../node_modules/.pnpm/rungen@0.3.2/node_modules/rungen/dist/utils/keys.js");

var _keys2 = _interopRequireDefault(_keys);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

var is = {
  obj: function obj(value) {
    return (typeof value === 'undefined' ? 'undefined' : _typeof(value)) === 'object' && !!value;
  },
  all: function all(value) {
    return is.obj(value) && value.type === _keys2.default.all;
  },
  error: function error(value) {
    return is.obj(value) && value.type === _keys2.default.error;
  },
  array: Array.isArray,
  func: function func(value) {
    return typeof value === 'function';
  },
  promise: function promise(value) {
    return value && is.func(value.then);
  },
  iterator: function iterator(value) {
    return value && is.func(value.next) && is.func(value.throw);
  },
  fork: function fork(value) {
    return is.obj(value) && value.type === _keys2.default.fork;
  },
  join: function join(value) {
    return is.obj(value) && value.type === _keys2.default.join;
  },
  race: function race(value) {
    return is.obj(value) && value.type === _keys2.default.race;
  },
  call: function call(value) {
    return is.obj(value) && value.type === _keys2.default.call;
  },
  cps: function cps(value) {
    return is.obj(value) && value.type === _keys2.default.cps;
  },
  subscribe: function subscribe(value) {
    return is.obj(value) && value.type === _keys2.default.subscribe;
  },
  channel: function channel(value) {
    return is.obj(value) && is.func(value.subscribe);
  }
};

exports["default"] = is;

/***/ },

/***/ "../../node_modules/.pnpm/rungen@0.3.2/node_modules/rungen/dist/utils/keys.js"
(__unused_webpack_module, exports) {



Object.defineProperty(exports, "__esModule", ({
  value: true
}));
var keys = {
  all: Symbol('all'),
  error: Symbol('error'),
  fork: Symbol('fork'),
  join: Symbol('join'),
  race: Symbol('race'),
  call: Symbol('call'),
  cps: Symbol('cps'),
  subscribe: Symbol('subscribe')
};

exports["default"] = keys;

/***/ }

}]);