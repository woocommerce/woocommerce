"use strict";
(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[6382],{

/***/ "../../node_modules/.pnpm/@wordpress+viewport@6.33.1_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/viewport/build-module/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  uE: () => (/* reexport */ with_viewport_match_default)
});

// UNUSED EXPORTS: ifViewportMatches, store

// NAMESPACE OBJECT: ../../node_modules/.pnpm/@wordpress+data@10.33.1_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/data/build-module/redux-store/metadata/selectors.js
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

// NAMESPACE OBJECT: ../../node_modules/.pnpm/@wordpress+data@10.33.1_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/data/build-module/redux-store/metadata/actions.js
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

// NAMESPACE OBJECT: ../../node_modules/.pnpm/@wordpress+viewport@6.33.1_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/viewport/build-module/store/actions.js
var store_actions_namespaceObject = {};
__webpack_require__.r(store_actions_namespaceObject);
__webpack_require__.d(store_actions_namespaceObject, {
  setIsMatching: () => (setIsMatching)
});

// NAMESPACE OBJECT: ../../node_modules/.pnpm/@wordpress+viewport@6.33.1_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/viewport/build-module/store/selectors.js
var store_selectors_namespaceObject = {};
__webpack_require__.r(store_selectors_namespaceObject);
__webpack_require__.d(store_selectors_namespaceObject, {
  isViewportMatch: () => (isViewportMatch)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+compose@7.45.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/compose/build-module/utils/debounce/index.mjs
var debounce = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@7.45.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/compose/build-module/utils/debounce/index.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+deprecated@4.48.1/node_modules/@wordpress/deprecated/build-module/index.mjs
var build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+deprecated@4.48.1/node_modules/@wordpress/deprecated/build-module/index.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/redux@5.0.1/node_modules/redux/dist/redux.mjs
var redux = __webpack_require__("../../node_modules/.pnpm/redux@5.0.1/node_modules/redux/dist/redux.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/equivalent-key-map@0.2.2/node_modules/equivalent-key-map/equivalent-key-map.js
var equivalent_key_map = __webpack_require__("../../node_modules/.pnpm/equivalent-key-map@0.2.2/node_modules/equivalent-key-map/equivalent-key-map.js");
var equivalent_key_map_default = /*#__PURE__*/__webpack_require__.n(equivalent_key_map);
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+redux-routine@5.48.1_redux@5.0.1/node_modules/@wordpress/redux-routine/build-module/index.mjs + 3 modules
var redux_routine_build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+redux-routine@5.48.1_redux@5.0.1/node_modules/@wordpress/redux-routine/build-module/index.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+compose@7.45.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/compose/build-module/higher-order/compose.mjs + 1 modules
var higher_order_compose = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@7.45.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/compose/build-module/higher-order/compose.mjs");
;// ../../node_modules/.pnpm/@wordpress+data@10.33.1_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/data/build-module/redux-store/combine-reducers.js
function combineReducers(reducers) {
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
const controls = { select: controls_select, resolveSelect, dispatch };
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

// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+private-apis@1.48.1/node_modules/@wordpress/private-apis/build-module/implementation.mjs
var implementation = __webpack_require__("../../node_modules/.pnpm/@wordpress+private-apis@1.48.1/node_modules/@wordpress/private-apis/build-module/implementation.mjs");
;// ../../node_modules/.pnpm/@wordpress+data@10.33.1_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/data/build-module/lock-unlock.js

const { lock, unlock } = (0,implementation/* __dangerousOptInToUnstableAPIsOnlyForCoreModules */.yf)(
  "I acknowledge private features are not for use in themes or plugins and doing so will break in the next version of WordPress.",
  "@wordpress/data"
);

//# sourceMappingURL=lock-unlock.js.map

// EXTERNAL MODULE: ../../node_modules/.pnpm/is-promise@4.0.0/node_modules/is-promise/index.mjs
var is_promise = __webpack_require__("../../node_modules/.pnpm/is-promise@4.0.0/node_modules/is-promise/index.mjs");
;// ../../node_modules/.pnpm/@wordpress+data@10.33.1_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/data/build-module/promise-middleware.js

const promiseMiddleware = () => (next) => (action) => {
  if ((0,is_promise/* default */.A)(action)) {
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


const subKeysIsResolved = onSubKey("selectorName")((state = new (equivalent_key_map_default())(), action) => {
  switch (action.type) {
    case "START_RESOLUTION": {
      const nextState = new (equivalent_key_map_default())(state);
      nextState.set(selectorArgsToStateKey(action.args), {
        status: "resolving"
      });
      return nextState;
    }
    case "FINISH_RESOLUTION": {
      const nextState = new (equivalent_key_map_default())(state);
      nextState.set(selectorArgsToStateKey(action.args), {
        status: "finished"
      });
      return nextState;
    }
    case "FAIL_RESOLUTION": {
      const nextState = new (equivalent_key_map_default())(state);
      nextState.set(selectorArgsToStateKey(action.args), {
        status: "error",
        error: action.error
      });
      return nextState;
    }
    case "START_RESOLUTIONS": {
      const nextState = new (equivalent_key_map_default())(state);
      for (const resolutionArgs of action.args) {
        nextState.set(selectorArgsToStateKey(resolutionArgs), {
          status: "resolving"
        });
      }
      return nextState;
    }
    case "FINISH_RESOLUTIONS": {
      const nextState = new (equivalent_key_map_default())(state);
      for (const resolutionArgs of action.args) {
        nextState.set(selectorArgsToStateKey(resolutionArgs), {
          status: "finished"
        });
      }
      return nextState;
    }
    case "FAIL_RESOLUTIONS": {
      const nextState = new (equivalent_key_map_default())(state);
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
      const nextState = new (equivalent_key_map_default())(state);
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

// EXTERNAL MODULE: ../../node_modules/.pnpm/rememo@4.0.2/node_modules/rememo/rememo.js
var rememo = __webpack_require__("../../node_modules/.pnpm/rememo@4.0.2/node_modules/rememo/rememo.js");
;// ../../node_modules/.pnpm/@wordpress+data@10.33.1_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/data/build-module/redux-store/metadata/selectors.js



function getResolutionState(state, selectorName, args) {
  const map = state[selectorName];
  if (!map) {
    return;
  }
  return map.get(selectorArgsToStateKey(args));
}
function getIsResolving(state, selectorName, args) {
  (0,build_module/* default */.A)("wp.data.select( store ).getIsResolving", {
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
const countSelectorsByStatus = (0,rememo/* default */.A)(
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
        cache[selectorName] = new (equivalent_key_map_default())();
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
      lock(store, privateRegistrationFunctions);
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
      lock(actions, allActions);
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
      lock(selectors, allSelectors);
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
      lock(resolveSelectors, allResolveSelectors);
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
      lock(suspendSelectors, allSuspendSelectors);
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
  lock(storeDescriptor, privateRegistrationFunctions);
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
    (0,redux_routine_build_module/* default */.A)(normalizedControls),
    createThunkMiddleware(thunkArgs)
  ];
  const enhancers = [(0,redux/* applyMiddleware */.Tw)(...middlewares)];
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
  const enhancedReducer = combineReducers({
    metadata: reducer_default,
    root: reducer
  });
  return (0,redux/* createStore */.y$)(
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
        unlock(store.store).registerPrivateActions(
          unlock(parent).privateActionsOf(name)
        );
        unlock(store.store).registerPrivateSelectors(
          unlock(parent).privateSelectorsOf(name)
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
    (0,build_module/* default */.A)("wp.data.registerGenericStore", {
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
      () => createReduxStore(storeName, options).instantiate(registry)
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
    registry.register(createReduxStore(name, config));
  }
  if (parent) {
    parent.subscribe(globalListener);
  }
  const registryWithPlugins = withPlugins(registry);
  lock(registryWithPlugins, {
    privateActionsOf: (name) => {
      try {
        return unlock(stores[name].store).privateActions;
      } catch (e) {
        return {};
      }
    },
    privateSelectorsOf: (name) => {
      try {
        return unlock(stores[name].store).privateSelectors;
      } catch (e) {
        return {};
      }
    }
  });
  return registryWithPlugins;
}

//# sourceMappingURL=registry.js.map

;// ../../node_modules/.pnpm/@wordpress+data@10.33.1_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/data/build-module/default-registry.js

var default_registry_default = createRegistry();

//# sourceMappingURL=default-registry.js.map

;// ../../node_modules/.pnpm/@wordpress+data@10.33.1_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/data/build-module/dispatch.js

function dispatch_dispatch(storeNameOrDescriptor) {
  return default_registry_default.dispatch(storeNameOrDescriptor);
}

//# sourceMappingURL=dispatch.js.map

;// ../../node_modules/.pnpm/@wordpress+data@10.33.1_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/data/build-module/index.js

















const build_module_combineReducers = (/* unused pure expression or super */ null && (combineReducersModule));
const build_module_resolveSelect = default_registry_default.resolveSelect;
const suspendSelect = default_registry_default.suspendSelect;
const subscribe = default_registry_default.subscribe;
const registerGenericStore = default_registry_default.registerGenericStore;
const registerStore = default_registry_default.registerStore;
const use = default_registry_default.use;
const register = default_registry_default.register;

//# sourceMappingURL=index.js.map

;// ../../node_modules/.pnpm/@wordpress+viewport@6.33.1_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/viewport/build-module/store/reducer.js
function reducer(state = {}, action) {
  switch (action.type) {
    case "SET_IS_MATCHING":
      return action.values;
  }
  return state;
}
var reducer_reducer_default = reducer;

//# sourceMappingURL=reducer.js.map

;// ../../node_modules/.pnpm/@wordpress+viewport@6.33.1_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/viewport/build-module/store/actions.js
function setIsMatching(values) {
  return {
    type: "SET_IS_MATCHING",
    values
  };
}

//# sourceMappingURL=actions.js.map

;// ../../node_modules/.pnpm/@wordpress+viewport@6.33.1_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/viewport/build-module/store/selectors.js
function isViewportMatch(state, query) {
  if (query.indexOf(" ") === -1) {
    query = ">= " + query;
  }
  return !!state[query];
}

//# sourceMappingURL=selectors.js.map

;// ../../node_modules/.pnpm/@wordpress+viewport@6.33.1_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/viewport/build-module/store/index.js




const STORE_NAME = "core/viewport";
const store = createReduxStore(STORE_NAME, {
  reducer: reducer_reducer_default,
  actions: store_actions_namespaceObject,
  selectors: store_selectors_namespaceObject
});
register(store);

//# sourceMappingURL=index.js.map

;// ../../node_modules/.pnpm/@wordpress+viewport@6.33.1_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/viewport/build-module/listener.js



const addDimensionsEventListener = (breakpoints, operators) => {
  const setIsMatching = (0,debounce/* debounce */.s)(
    () => {
      const values = Object.fromEntries(
        queries.map(([key, query]) => [key, query.matches])
      );
      dispatch_dispatch(store).setIsMatching(values);
    },
    0,
    { leading: true }
  );
  const operatorEntries = Object.entries(operators);
  const queries = Object.entries(breakpoints).flatMap(
    ([name, width]) => {
      return operatorEntries.map(([operator, condition]) => {
        const list = window.matchMedia(
          `(${condition}: ${width}px)`
        );
        list.addEventListener("change", setIsMatching);
        return [`${operator} ${name}`, list];
      });
    }
  );
  window.addEventListener("orientationchange", setIsMatching);
  setIsMatching();
  setIsMatching.flush();
};
var listener_default = addDimensionsEventListener;

//# sourceMappingURL=listener.js.map

// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js
var jsx_runtime = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+compose@7.45.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-viewport-match/index.mjs
var use_viewport_match = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@7.45.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-viewport-match/index.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+compose@7.45.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/compose/build-module/utils/create-higher-order-component/index.mjs
var create_higher_order_component = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@7.45.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/compose/build-module/utils/create-higher-order-component/index.mjs");
;// ../../node_modules/.pnpm/@wordpress+is-shallow-equal@5.50.0/node_modules/@wordpress/is-shallow-equal/build-module/objects.mjs
// packages/is-shallow-equal/src/objects.ts
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
      aValue === void 0 && !b.hasOwnProperty(key) || aValue !== b[key]
    ) {
      return false;
    }
    i++;
  }
  return true;
}

//# sourceMappingURL=objects.mjs.map

;// ../../node_modules/.pnpm/@wordpress+is-shallow-equal@5.50.0/node_modules/@wordpress/is-shallow-equal/build-module/arrays.mjs
// packages/is-shallow-equal/src/arrays.ts
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

//# sourceMappingURL=arrays.mjs.map

;// ../../node_modules/.pnpm/@wordpress+is-shallow-equal@5.50.0/node_modules/@wordpress/is-shallow-equal/build-module/index.mjs
// packages/is-shallow-equal/src/index.ts


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

//# sourceMappingURL=index.mjs.map

// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
;// ../../node_modules/.pnpm/@wordpress+compose@7.45.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/compose/build-module/higher-order/pure/index.mjs
// packages/compose/src/higher-order/pure/index.tsx




var pure = (0,create_higher_order_component/* createHigherOrderComponent */.f)(function(WrappedComponent) {
  if (WrappedComponent.prototype instanceof react.Component) {
    return class extends WrappedComponent {
      shouldComponentUpdate(nextProps, nextState) {
        return !isShallowEqual(nextProps, this.props) || !isShallowEqual(nextState, this.state);
      }
    };
  }
  return class extends react.Component {
    shouldComponentUpdate(nextProps) {
      return !isShallowEqual(nextProps, this.props);
    }
    render() {
      return /* @__PURE__ */ (0,jsx_runtime.jsx)(WrappedComponent, { ...this.props });
    }
  };
}, "pure");
var pure_default = pure;

//# sourceMappingURL=index.mjs.map

;// ../../node_modules/.pnpm/@wordpress+viewport@6.33.1_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/viewport/build-module/with-viewport-match.js


const with_viewport_match_withViewportMatch = (queries) => {
  const queryEntries = Object.entries(queries);
  const useViewPortQueriesResult = () => Object.fromEntries(
    queryEntries.map(([key, query]) => {
      let [operator, breakpointName] = query.split(" ");
      if (breakpointName === void 0) {
        breakpointName = operator;
        operator = ">=";
      }
      return [key, (0,use_viewport_match/* default */.A)(breakpointName, operator)];
    })
  );
  return (0,create_higher_order_component/* createHigherOrderComponent */.f)((WrappedComponent) => {
    return pure_default((props) => {
      const queriesResult = useViewPortQueriesResult();
      return /* @__PURE__ */ (0,jsx_runtime.jsx)(WrappedComponent, { ...props, ...queriesResult });
    });
  }, "withViewportMatch");
};
var with_viewport_match_default = with_viewport_match_withViewportMatch;

//# sourceMappingURL=with-viewport-match.js.map

;// ../../node_modules/.pnpm/@wordpress+viewport@6.33.1_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/viewport/build-module/if-viewport-matches.js


const ifViewportMatches = (query) => createHigherOrderComponent(
  compose([
    withViewportMatch({
      isViewportMatch: query
    }),
    ifCondition((props) => props.isViewportMatch)
  ]),
  "ifViewportMatches"
);
var if_viewport_matches_default = (/* unused pure expression or super */ null && (ifViewportMatches));

//# sourceMappingURL=if-viewport-matches.js.map

;// ../../node_modules/.pnpm/@wordpress+viewport@6.33.1_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/viewport/build-module/index.js




const BREAKPOINTS = {
  huge: 1440,
  wide: 1280,
  large: 960,
  medium: 782,
  small: 600,
  mobile: 480
};
const OPERATORS = {
  "<": "max-width",
  ">=": "min-width"
};
listener_default(BREAKPOINTS, OPERATORS);

//# sourceMappingURL=index.js.map


/***/ })

}]);