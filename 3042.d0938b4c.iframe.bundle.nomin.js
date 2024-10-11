"use strict";
(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[3042],{

/***/ "../../node_modules/.pnpm/reakit-system@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-system/es/SystemContext.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   r: () => (/* binding */ SystemContext)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");


var SystemContext = /*#__PURE__*/(0,react__WEBPACK_IMPORTED_MODULE_0__.createContext)({});




/***/ }),

/***/ "../../node_modules/.pnpm/reakit-system@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-system/es/_rollupPluginBabelHelpers-0c84a174.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   _: () => (/* binding */ _objectSpread2),
/* harmony export */   a: () => (/* binding */ _objectWithoutPropertiesLoose),
/* harmony export */   b: () => (/* binding */ _createForOfIteratorHelperLoose)
/* harmony export */ });
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

function _objectSpread2(target) {
  for (var i = 1; i < arguments.length; i++) {
    var source = arguments[i] != null ? arguments[i] : {};

    if (i % 2) {
      ownKeys(Object(source), true).forEach(function (key) {
        _defineProperty(target, key, source[key]);
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

function _unsupportedIterableToArray(o, minLen) {
  if (!o) return;
  if (typeof o === "string") return _arrayLikeToArray(o, minLen);
  var n = Object.prototype.toString.call(o).slice(8, -1);
  if (n === "Object" && o.constructor) n = o.constructor.name;
  if (n === "Map" || n === "Set") return Array.from(o);
  if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen);
}

function _arrayLikeToArray(arr, len) {
  if (len == null || len > arr.length) len = arr.length;

  for (var i = 0, arr2 = new Array(len); i < len; i++) arr2[i] = arr[i];

  return arr2;
}

function _createForOfIteratorHelperLoose(o, allowArrayLike) {
  var it;

  if (typeof Symbol === "undefined" || o[Symbol.iterator] == null) {
    if (Array.isArray(o) || (it = _unsupportedIterableToArray(o)) || allowArrayLike && o && typeof o.length === "number") {
      if (it) o = it;
      var i = 0;
      return function () {
        if (i >= o.length) return {
          done: true
        };
        return {
          done: false,
          value: o[i++]
        };
      };
    }

    throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method.");
  }

  it = o[Symbol.iterator]();
  return it.next.bind(it);
}




/***/ }),

/***/ "../../node_modules/.pnpm/reakit-system@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-system/es/createComponent.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  a: () => (/* binding */ createComponent)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/reakit-system@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-system/es/_rollupPluginBabelHelpers-0c84a174.js
var _rollupPluginBabelHelpers_0c84a174 = __webpack_require__("../../node_modules/.pnpm/reakit-system@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-system/es/_rollupPluginBabelHelpers-0c84a174.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/reakit-system@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-system/es/useCreateElement.js
var useCreateElement = __webpack_require__("../../node_modules/.pnpm/reakit-system@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-system/es/useCreateElement.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/reakit-utils@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-utils/es/_rollupPluginBabelHelpers-1f0bf8c2.js
var _rollupPluginBabelHelpers_1f0bf8c2 = __webpack_require__("../../node_modules/.pnpm/reakit-utils@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-utils/es/_rollupPluginBabelHelpers-1f0bf8c2.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/reakit-utils@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-utils/es/isObject.js
/**
 * Checks whether `arg` is an object or not.
 *
 * @returns {boolean}
 */
function isObject(arg) {
  return typeof arg === "object" && arg != null;
}



;// CONCATENATED MODULE: ../../node_modules/.pnpm/reakit-utils@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-utils/es/isPlainObject.js


/**
 * Checks whether `arg` is a plain object or not.
 *
 * @returns {boolean}
 */

function isPlainObject(arg) {
  var _proto$constructor;

  if (!isObject(arg)) return false;
  var proto = Object.getPrototypeOf(arg);
  if (proto == null) return true;
  return ((_proto$constructor = proto.constructor) === null || _proto$constructor === void 0 ? void 0 : _proto$constructor.toString()) === Object.toString();
}



;// CONCATENATED MODULE: ../../node_modules/.pnpm/reakit-utils@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-utils/es/splitProps.js




/**
 * Splits an object (`props`) into a tuple where the first item is an object
 * with the passed `keys`, and the second item is an object with these keys
 * omitted.
 *
 * @deprecated will be removed in version 2
 *
 * @example
 * import { splitProps } from "reakit-utils";
 *
 * splitProps({ a: "a", b: "b" }, ["a"]); // [{ a: "a" }, { b: "b" }]
 */

function __deprecatedSplitProps(props, keys) {
  var propsKeys = Object.keys(props);
  var picked = {};
  var omitted = {};

  for (var _i = 0, _propsKeys = propsKeys; _i < _propsKeys.length; _i++) {
    var key = _propsKeys[_i];

    if (keys.indexOf(key) >= 0) {
      picked[key] = props[key];
    } else {
      omitted[key] = props[key];
    }
  }

  return [picked, omitted];
}
/**
 * Splits an object (`props`) into a tuple where the first item
 * is the `state` property, and the second item is the rest of the properties.
 *
 * It is also backward compatible with version 1. If `keys` are passed then
 * splits an object (`props`) into a tuple where the first item is an object
 * with the passed `keys`, and the second item is an object with these keys
 * omitted.
 *
 * @example
 * import { splitProps } from "reakit-utils";
 *
 * splitProps({ a: "a", b: "b" }, ["a"]); // [{ a: "a" }, { b: "b" }]
 *
 * @example
 * import { splitProps } from "reakit-utils";
 *
 * splitProps({ state: { a: "a" }, b: "b" }); // [{ a: "a" }, { b: "b" }]
 */


function splitProps(props, keys) {
  if (keys === void 0) {
    keys = [];
  }

  if (!isPlainObject(props.state)) {
    return __deprecatedSplitProps(props, keys);
  }

  var _deprecatedSplitProp = __deprecatedSplitProps(props, [].concat(keys, ["state"])),
      picked = _deprecatedSplitProp[0],
      omitted = _deprecatedSplitProp[1];

  var state = picked.state,
      restPicked = (0,_rollupPluginBabelHelpers_1f0bf8c2._)(picked, ["state"]);

  return [(0,_rollupPluginBabelHelpers_1f0bf8c2.a)((0,_rollupPluginBabelHelpers_1f0bf8c2.a)({}, state), restPicked), omitted];
}



// EXTERNAL MODULE: ../../node_modules/.pnpm/reakit-utils@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-utils/es/shallowEqual.js
var shallowEqual = __webpack_require__("../../node_modules/.pnpm/reakit-utils@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-utils/es/shallowEqual.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/reakit-utils@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-utils/es/normalizePropsAreEqual.js




/**
 * This higher order functions take `propsAreEqual` function and
 * returns a new function which normalizes the props.
 *
 * Normalizing in our case is making sure the `propsAreEqual` works with
 * both version 1 (object spreading) and version 2 (state object) state passing.
 *
 * To achieve this, the returned function in case of a state object
 * will spread the state object in both `prev` and `next props.
 *
 * Other case it just returns the function as is which makes sure
 * that we are still backward compatible
 */
function normalizePropsAreEqual(propsAreEqual) {
  if (propsAreEqual.name === "normalizePropsAreEqualInner") {
    return propsAreEqual;
  }

  return function normalizePropsAreEqualInner(prev, next) {
    if (!isPlainObject(prev.state) || !isPlainObject(next.state)) {
      return propsAreEqual(prev, next);
    }

    return propsAreEqual((0,_rollupPluginBabelHelpers_1f0bf8c2.a)((0,_rollupPluginBabelHelpers_1f0bf8c2.a)({}, prev.state), prev), (0,_rollupPluginBabelHelpers_1f0bf8c2.a)((0,_rollupPluginBabelHelpers_1f0bf8c2.a)({}, next.state), next));
  };
}



;// CONCATENATED MODULE: ../../node_modules/.pnpm/reakit-system@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-system/es/createComponent.js








function forwardRef(component) {
  return /*#__PURE__*/(0,react.forwardRef)(component);
}

function memo(component, propsAreEqual) {
  return /*#__PURE__*/(0,react.memo)(component, propsAreEqual);
}

/**
 * Creates a React component.
 *
 * @example
 * import { createComponent } from "reakit-system";
 *
 * const A = createComponent({ as: "a" });
 *
 * @param options
 */
function createComponent(_ref) {
  var type = _ref.as,
      useHook = _ref.useHook,
      shouldMemo = _ref.memo,
      _ref$propsAreEqual = _ref.propsAreEqual,
      propsAreEqual = _ref$propsAreEqual === void 0 ? useHook === null || useHook === void 0 ? void 0 : useHook.unstable_propsAreEqual : _ref$propsAreEqual,
      _ref$keys = _ref.keys,
      keys = _ref$keys === void 0 ? (useHook === null || useHook === void 0 ? void 0 : useHook.__keys) || [] : _ref$keys,
      _ref$useCreateElement = _ref.useCreateElement,
      useCreateElement$1 = _ref$useCreateElement === void 0 ? useCreateElement/* useCreateElement */.U : _ref$useCreateElement;

  var Comp = function Comp(_ref2, ref) {
    var _ref2$as = _ref2.as,
        as = _ref2$as === void 0 ? type : _ref2$as,
        props = (0,_rollupPluginBabelHelpers_0c84a174.a)(_ref2, ["as"]);

    if (useHook) {
      var _as$render;

      var _splitProps = splitProps(props, keys),
          _options = _splitProps[0],
          htmlProps = _splitProps[1];

      var _useHook = useHook(_options, (0,_rollupPluginBabelHelpers_0c84a174._)({
        ref: ref
      }, htmlProps)),
          wrapElement = _useHook.wrapElement,
          elementProps = (0,_rollupPluginBabelHelpers_0c84a174.a)(_useHook, ["wrapElement"]); // @ts-ignore


      var asKeys = ((_as$render = as.render) === null || _as$render === void 0 ? void 0 : _as$render.__keys) || as.__keys;
      var asOptions = asKeys && splitProps(props, asKeys)[0];
      var allProps = asOptions ? (0,_rollupPluginBabelHelpers_0c84a174._)((0,_rollupPluginBabelHelpers_0c84a174._)({}, elementProps), asOptions) : elementProps;

      var _element = useCreateElement$1(as, allProps);

      if (wrapElement) {
        return wrapElement(_element);
      }

      return _element;
    }

    return useCreateElement$1(as, (0,_rollupPluginBabelHelpers_0c84a174._)({
      ref: ref
    }, props));
  };

  if (false) {}

  Comp = forwardRef(Comp);

  if (shouldMemo) {
    Comp = memo(Comp, propsAreEqual && normalizePropsAreEqual(propsAreEqual));
  }

  Comp.__keys = keys;
  Comp.unstable_propsAreEqual = normalizePropsAreEqual(propsAreEqual || shallowEqual/* shallowEqual */.b);
  return Comp;
}




/***/ }),

/***/ "../../node_modules/.pnpm/reakit-system@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-system/es/createHook.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  a: () => (/* binding */ createHook)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/reakit-system@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-system/es/SystemContext.js
var SystemContext = __webpack_require__("../../node_modules/.pnpm/reakit-system@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-system/es/SystemContext.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/reakit-system@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-system/es/useToken.js



/**
 * React custom hook that returns the value of any token defined in the
 * SystemContext. It's mainly used internally in [`useOptions`](#useoptions)
 * and [`useProps`](#useprops).
 *
 * @example
 * import { SystemProvider, useToken } from "reakit-system";
 *
 * const system = {
 *   token: "value",
 * };
 *
 * function Component(props) {
 *   const token = useToken("token", "default value");
 *   return <div {...props}>{token}</div>;
 * }
 *
 * function App() {
 *   return (
 *     <SystemProvider unstable_system={system}>
 *       <Component />
 *     </SystemProvider>
 *   );
 * }
 */

function useToken(token, defaultValue) {
  (0,react.useDebugValue)(token);
  var context = (0,react.useContext)(SystemContext/* SystemContext */.r);
  return context[token] != null ? context[token] : defaultValue;
}



;// CONCATENATED MODULE: ../../node_modules/.pnpm/reakit-system@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-system/es/useProps.js




/**
 * React custom hook that returns the props returned by a given
 * `use${name}Props` in the SystemContext.
 *
 * @example
 * import { SystemProvider, useProps } from "reakit-system";
 *
 * const system = {
 *   useAProps(options, htmlProps) {
 *     return {
 *       ...htmlProps,
 *       href: options.url,
 *     };
 *   },
 * };
 *
 * function A({ url, ...htmlProps }) {
 *   const props = useProps("A", { url }, htmlProps);
 *   return <a {...props} />;
 * }
 *
 * function App() {
 *   return (
 *     <SystemProvider unstable_system={system}>
 *       <A url="url">It will convert url into href in useAProps</A>
 *     </SystemProvider>
 *   );
 * }
 */

function useProps(name, options, htmlProps) {
  if (options === void 0) {
    options = {};
  }

  if (htmlProps === void 0) {
    htmlProps = {};
  }

  var hookName = "use" + name + "Props";
  (0,react.useDebugValue)(hookName);
  var useHook = useToken(hookName);

  if (useHook) {
    return useHook(options, htmlProps);
  }

  return htmlProps;
}



// EXTERNAL MODULE: ../../node_modules/.pnpm/reakit-system@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-system/es/_rollupPluginBabelHelpers-0c84a174.js
var _rollupPluginBabelHelpers_0c84a174 = __webpack_require__("../../node_modules/.pnpm/reakit-system@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-system/es/_rollupPluginBabelHelpers-0c84a174.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/reakit-system@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-system/es/useOptions.js





/**
 * React custom hook that returns the options returned by a given
 * `use${name}Options` in the SystemContext.
 *
 * @example
 * import React from "react";
 * import { SystemProvider, useOptions } from "reakit-system";
 *
 * const system = {
 *   useAOptions(options, htmlProps) {
 *     return {
 *       ...options,
 *       url: htmlProps.href,
 *     };
 *   },
 * };
 *
 * function A({ url, ...htmlProps }) {
 *   const options = useOptions("A", { url }, htmlProps);
 *   return <a href={options.url} {...htmlProps} />;
 * }
 *
 * function App() {
 *   return (
 *     <SystemProvider unstable_system={system}>
 *       <A href="url">
 *         It will convert href into url in useAOptions and then url into href in A
 *       </A>
 *     </SystemProvider>
 *   );
 * }
 */

function useOptions(name, options, htmlProps) {
  if (options === void 0) {
    options = {};
  }

  if (htmlProps === void 0) {
    htmlProps = {};
  }

  var hookName = "use" + name + "Options";
  (0,react.useDebugValue)(hookName);
  var useHook = useToken(hookName);

  if (useHook) {
    return (0,_rollupPluginBabelHelpers_0c84a174._)((0,_rollupPluginBabelHelpers_0c84a174._)({}, options), useHook(options, htmlProps));
  }

  return options;
}



// EXTERNAL MODULE: ../../node_modules/.pnpm/reakit-utils@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-utils/es/shallowEqual.js
var shallowEqual = __webpack_require__("../../node_modules/.pnpm/reakit-utils@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-utils/es/shallowEqual.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/reakit-utils@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-utils/es/toArray.js
/**
 * Transforms `arg` into an array if it's not already.
 *
 * @example
 * import { toArray } from "reakit-utils";
 *
 * toArray("a"); // ["a"]
 * toArray(["a"]); // ["a"]
 */
function toArray(arg) {
  if (Array.isArray(arg)) {
    return arg;
  }

  return typeof arg !== "undefined" ? [arg] : [];
}



;// CONCATENATED MODULE: ../../node_modules/.pnpm/reakit-system@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-system/es/createHook.js









/**
 * Creates a React custom hook that will return component props.
 *
 * @example
 * import { createHook } from "reakit-system";
 *
 * const useA = createHook({
 *   name: "A",
 *   keys: ["url"], // custom props/options keys
 *   useProps(options, htmlProps) {
 *     return {
 *       ...htmlProps,
 *       href: options.url,
 *     };
 *   },
 * });
 *
 * function A({ url, ...htmlProps }) {
 *   const props = useA({ url }, htmlProps);
 *   return <a {...props} />;
 * }
 *
 * @param options
 */
function createHook(options) {
  var _options$useState, _composedHooks$;

  var composedHooks = toArray(options.compose);

  var __useOptions = function __useOptions(hookOptions, htmlProps) {
    // Call the current hook's useOptions first
    if (options.useOptions) {
      hookOptions = options.useOptions(hookOptions, htmlProps);
    } // If there's name, call useOptions from the system context


    if (options.name) {
      hookOptions = useOptions(options.name, hookOptions, htmlProps);
    } // Run composed hooks useOptions


    if (options.compose) {
      for (var _iterator = (0,_rollupPluginBabelHelpers_0c84a174.b)(composedHooks), _step; !(_step = _iterator()).done;) {
        var hook = _step.value;
        hookOptions = hook.__useOptions(hookOptions, htmlProps);
      }
    }

    return hookOptions;
  };

  var useHook = function useHook(hookOptions, htmlProps, unstable_ignoreUseOptions) {
    if (hookOptions === void 0) {
      hookOptions = {};
    }

    if (htmlProps === void 0) {
      htmlProps = {};
    }

    if (unstable_ignoreUseOptions === void 0) {
      unstable_ignoreUseOptions = false;
    }

    // This won't execute when useHook was called from within another useHook
    if (!unstable_ignoreUseOptions) {
      hookOptions = __useOptions(hookOptions, htmlProps);
    } // Call the current hook's useProps


    if (options.useProps) {
      htmlProps = options.useProps(hookOptions, htmlProps);
    } // If there's name, call useProps from the system context


    if (options.name) {
      htmlProps = useProps(options.name, hookOptions, htmlProps);
    }

    if (options.compose) {
      if (options.useComposeOptions) {
        hookOptions = options.useComposeOptions(hookOptions, htmlProps);
      }

      if (options.useComposeProps) {
        htmlProps = options.useComposeProps(hookOptions, htmlProps);
      } else {
        for (var _iterator2 = (0,_rollupPluginBabelHelpers_0c84a174.b)(composedHooks), _step2; !(_step2 = _iterator2()).done;) {
          var hook = _step2.value;
          htmlProps = hook(hookOptions, htmlProps, true);
        }
      }
    } // Remove undefined values from htmlProps


    var finalHTMLProps = {};
    var definedHTMLProps = htmlProps || {};

    for (var prop in definedHTMLProps) {
      if (definedHTMLProps[prop] !== undefined) {
        finalHTMLProps[prop] = definedHTMLProps[prop];
      }
    }

    return finalHTMLProps;
  };

  useHook.__useOptions = __useOptions;
  var composedKeys = composedHooks.reduce(function (keys, hook) {
    keys.push.apply(keys, hook.__keys || []);
    return keys;
  }, []); // It's used by createComponent to split option props (keys) and html props

  useHook.__keys = [].concat(composedKeys, ((_options$useState = options.useState) === null || _options$useState === void 0 ? void 0 : _options$useState.__keys) || [], options.keys || []);
  useHook.unstable_propsAreEqual = options.propsAreEqual || ((_composedHooks$ = composedHooks[0]) === null || _composedHooks$ === void 0 ? void 0 : _composedHooks$.unstable_propsAreEqual) || shallowEqual/* shallowEqual */.b;

  if (false) {}

  return useHook;
}




/***/ }),

/***/ "../../node_modules/.pnpm/reakit-system@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-system/es/useCreateElement.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   U: () => (/* binding */ useCreateElement)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var _SystemContext_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/reakit-system@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-system/es/SystemContext.js");
/* harmony import */ var _rollupPluginBabelHelpers_0c84a174_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/reakit-system@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-system/es/_rollupPluginBabelHelpers-0c84a174.js");




function isRenderProp(children) {
  return typeof children === "function";
}

/**
 * Custom hook that will call `children` if it's a function. If
 * `useCreateElement` has been passed to the context, it'll be used instead.
 *
 * @example
 * import React from "react";
 * import { SystemProvider, useCreateElement } from "reakit-system";
 *
 * const system = {
 *   useCreateElement(type, props, children = props.children) {
 *     // very similar to what `useCreateElement` does already
 *     if (typeof children === "function") {
 *       const { children: _, ...rest } = props;
 *       return children(rest);
 *     }
 *     return React.createElement(type, props, children);
 *   },
 * };
 *
 * function Component(props) {
 *   return useCreateElement("div", props);
 * }
 *
 * function App() {
 *   return (
 *     <SystemProvider unstable_system={system}>
 *       <Component url="url">{({ url }) => <a href={url}>link</a>}</Component>
 *     </SystemProvider>
 *   );
 * }
 */

var useCreateElement = function useCreateElement(type, props, children) {
  if (children === void 0) {
    children = props.children;
  }

  var context = (0,react__WEBPACK_IMPORTED_MODULE_0__.useContext)(_SystemContext_js__WEBPACK_IMPORTED_MODULE_1__/* .SystemContext */ .r);

  if (context.useCreateElement) {
    return context.useCreateElement(type, props, children);
  }

  if (typeof type === "string" && isRenderProp(children)) {
    var _ = props.children,
        rest = (0,_rollupPluginBabelHelpers_0c84a174_js__WEBPACK_IMPORTED_MODULE_2__.a)(props, ["children"]);

    return children(rest);
  }

  return /*#__PURE__*/(0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(type, props, children);
};




/***/ }),

/***/ "../../node_modules/.pnpm/reakit-utils@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-utils/es/_rollupPluginBabelHelpers-1f0bf8c2.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   _: () => (/* binding */ _objectWithoutPropertiesLoose),
/* harmony export */   a: () => (/* binding */ _objectSpread2)
/* harmony export */ });
/* unused harmony export b */
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

function _objectSpread2(target) {
  for (var i = 1; i < arguments.length; i++) {
    var source = arguments[i] != null ? arguments[i] : {};

    if (i % 2) {
      ownKeys(Object(source), true).forEach(function (key) {
        _defineProperty(target, key, source[key]);
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

function _unsupportedIterableToArray(o, minLen) {
  if (!o) return;
  if (typeof o === "string") return _arrayLikeToArray(o, minLen);
  var n = Object.prototype.toString.call(o).slice(8, -1);
  if (n === "Object" && o.constructor) n = o.constructor.name;
  if (n === "Map" || n === "Set") return Array.from(o);
  if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen);
}

function _arrayLikeToArray(arr, len) {
  if (len == null || len > arr.length) len = arr.length;

  for (var i = 0, arr2 = new Array(len); i < len; i++) arr2[i] = arr[i];

  return arr2;
}

function _createForOfIteratorHelperLoose(o, allowArrayLike) {
  var it;

  if (typeof Symbol === "undefined" || o[Symbol.iterator] == null) {
    if (Array.isArray(o) || (it = _unsupportedIterableToArray(o)) || allowArrayLike && o && typeof o.length === "number") {
      if (it) o = it;
      var i = 0;
      return function () {
        if (i >= o.length) return {
          done: true
        };
        return {
          done: false,
          value: o[i++]
        };
      };
    }

    throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method.");
  }

  it = o[Symbol.iterator]();
  return it.next.bind(it);
}




/***/ }),

/***/ "../../node_modules/.pnpm/reakit-utils@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-utils/es/canUseDOM.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   S: () => (/* binding */ canUseDOM)
/* harmony export */ });
/* harmony import */ var _getWindow_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/reakit-utils@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-utils/es/getWindow.js");



function checkIsBrowser() {
  var _window = (0,_getWindow_js__WEBPACK_IMPORTED_MODULE_0__/* .getWindow */ .z)();

  return Boolean(typeof _window !== "undefined" && _window.document && _window.document.createElement);
}
/**
 * It's `true` if it is running in a browser environment or `false` if it is not (SSR).
 *
 * @example
 * import { canUseDOM } from "reakit-utils";
 *
 * const title = canUseDOM ? document.title : "";
 */


var canUseDOM = checkIsBrowser();




/***/ }),

/***/ "../../node_modules/.pnpm/reakit-utils@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-utils/es/contains.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   g: () => (/* binding */ contains)
/* harmony export */ });
/**
 * Similar to `Element.prototype.contains`, but a little bit faster when
 * `element` is the same as `child`.
 *
 * @example
 * import { contains } from "reakit-utils";
 *
 * contains(document.getElementById("parent"), document.getElementById("child"));
 */
function contains(parent, child) {
  return parent === child || parent.contains(child);
}




/***/ }),

/***/ "../../node_modules/.pnpm/reakit-utils@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-utils/es/createEvent.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   l: () => (/* binding */ createEvent)
/* harmony export */ });
/* harmony import */ var _getDocument_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/reakit-utils@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-utils/es/getDocument.js");


/**
 * Creates an `Event` in a way that also works on IE 11.
 *
 * @example
 * import { createEvent } from "reakit-utils";
 *
 * const el = document.getElementById("id");
 * el.dispatchEvent(createEvent(el, "blur", { bubbles: false }));
 */

function createEvent(element, type, eventInit) {
  if (typeof Event === "function") {
    return new Event(type, eventInit);
  } // IE 11 doesn't support Event constructors


  var event = (0,_getDocument_js__WEBPACK_IMPORTED_MODULE_0__/* .getDocument */ .Y)(element).createEvent("Event");
  event.initEvent(type, eventInit === null || eventInit === void 0 ? void 0 : eventInit.bubbles, eventInit === null || eventInit === void 0 ? void 0 : eventInit.cancelable);
  return event;
}




/***/ }),

/***/ "../../node_modules/.pnpm/reakit-utils@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-utils/es/dom.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   p: () => (/* binding */ isUA)
/* harmony export */ });
/* harmony import */ var _canUseDOM_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/reakit-utils@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-utils/es/canUseDOM.js");




/**
 * Checks if a given string exists in the user agent string.
 */

function isUA(string) {
  if (!_canUseDOM_js__WEBPACK_IMPORTED_MODULE_0__/* .canUseDOM */ .S) return false;
  return window.navigator.userAgent.indexOf(string) !== -1;
}




/***/ }),

/***/ "../../node_modules/.pnpm/reakit-utils@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-utils/es/getActiveElement.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   b: () => (/* binding */ getActiveElement)
/* harmony export */ });
/* harmony import */ var _getDocument_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/reakit-utils@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-utils/es/getDocument.js");


/**
 * Returns `element.ownerDocument.activeElement`.
 */

function getActiveElement(element) {
  var _getDocument = (0,_getDocument_js__WEBPACK_IMPORTED_MODULE_0__/* .getDocument */ .Y)(element),
      activeElement = _getDocument.activeElement;

  if (!(activeElement !== null && activeElement !== void 0 && activeElement.nodeName)) {
    // In IE11, activeElement might be an empty object if we're interacting
    // with elements inside of an iframe.
    return null;
  }

  return activeElement;
}




/***/ }),

/***/ "../../node_modules/.pnpm/reakit-utils@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-utils/es/getDocument.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   Y: () => (/* binding */ getDocument)
/* harmony export */ });
/**
 * Returns `element.ownerDocument || document`.
 */
function getDocument(element) {
  return element ? element.ownerDocument || element : document;
}




/***/ }),

/***/ "../../node_modules/.pnpm/reakit-utils@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-utils/es/getWindow.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   z: () => (/* binding */ getWindow)
/* harmony export */ });
/* harmony import */ var _getDocument_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/reakit-utils@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-utils/es/getDocument.js");


// Thanks to Fluent UI for doing the [research on IE11 memory leak](https://github.com/microsoft/fluentui/pull/9010#issuecomment-490768427)

var _window; // Note: Accessing "window" in IE11 is somewhat expensive, and calling "typeof window"
// hits a memory leak, whereas aliasing it and calling "typeof _window" does not.
// Caching the window value at the file scope lets us minimize the impact.


try {
  _window = window;
} catch (e) {
  /* no-op */
}
/**
 * Returns `element.ownerDocument.defaultView || window`.
 */


function getWindow(element) {
  if (!element) {
    return _window;
  }

  return (0,_getDocument_js__WEBPACK_IMPORTED_MODULE_0__/* .getDocument */ .Y)(element).defaultView || _window;
}




/***/ }),

/***/ "../../node_modules/.pnpm/reakit-utils@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-utils/es/hasFocusWithin.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   o: () => (/* binding */ hasFocusWithin)
/* harmony export */ });
/* harmony import */ var _getActiveElement_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/reakit-utils@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-utils/es/getActiveElement.js");
/* harmony import */ var _contains_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/reakit-utils@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-utils/es/contains.js");




/**
 * Checks if `element` has focus within. Elements that are referenced by
 * `aria-activedescendant` are also considered.
 *
 * @example
 * import { hasFocusWithin } from "reakit-utils";
 *
 * hasFocusWithin(document.getElementById("id"));
 */

function hasFocusWithin(element) {
  var activeElement = (0,_getActiveElement_js__WEBPACK_IMPORTED_MODULE_0__/* .getActiveElement */ .b)(element);
  if (!activeElement) return false;
  if ((0,_contains_js__WEBPACK_IMPORTED_MODULE_1__/* .contains */ .g)(element, activeElement)) return true;
  var activeDescendant = activeElement.getAttribute("aria-activedescendant");
  if (!activeDescendant) return false;
  if (activeDescendant === element.id) return true;
  return !!element.querySelector("#" + activeDescendant);
}




/***/ }),

/***/ "../../node_modules/.pnpm/reakit-utils@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-utils/es/isButton.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   B: () => (/* binding */ isButton)
/* harmony export */ });
var buttonInputTypes = ["button", "color", "file", "image", "reset", "submit"];
/**
 * Checks whether `element` is a native HTML button element.
 *
 * @example
 * import { isButton } from "reakit-utils";
 *
 * isButton(document.querySelector("button")); // true
 * isButton(document.querySelector("input[type='button']")); // true
 * isButton(document.querySelector("div")); // false
 * isButton(document.querySelector("input[type='text']")); // false
 * isButton(document.querySelector("div[role='button']")); // false
 *
 * @returns {boolean}
 */

function isButton(element) {
  if (element.tagName === "BUTTON") return true;

  if (element.tagName === "INPUT") {
    var input = element;
    return buttonInputTypes.indexOf(input.type) !== -1;
  }

  return false;
}




/***/ }),

/***/ "../../node_modules/.pnpm/reakit-utils@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-utils/es/isPortalEvent.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   h: () => (/* binding */ isPortalEvent)
/* harmony export */ });
/* harmony import */ var _contains_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/reakit-utils@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-utils/es/contains.js");


/**
 * Returns `true` if `event` has been fired within a React Portal element.
 */

function isPortalEvent(event) {
  return !(0,_contains_js__WEBPACK_IMPORTED_MODULE_0__/* .contains */ .g)(event.currentTarget, event.target);
}




/***/ }),

/***/ "../../node_modules/.pnpm/reakit-utils@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-utils/es/isSelfTarget.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   u: () => (/* binding */ isSelfTarget)
/* harmony export */ });
/**
 * Returns `true` if `event.target` and `event.currentTarget` are the same.
 */
function isSelfTarget(event) {
  return event.target === event.currentTarget;
}




/***/ }),

/***/ "../../node_modules/.pnpm/reakit-utils@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-utils/es/shallowEqual.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   b: () => (/* binding */ shallowEqual)
/* harmony export */ });
/**
 * Compares two objects.
 *
 * @example
 * import { shallowEqual } from "reakit-utils";
 *
 * shallowEqual({ a: "a" }, {}); // false
 * shallowEqual({ a: "a" }, { b: "b" }); // false
 * shallowEqual({ a: "a" }, { a: "a" }); // true
 * shallowEqual({ a: "a" }, { a: "a", b: "b" }); // false
 */
function shallowEqual(objA, objB) {
  if (objA === objB) return true;
  if (!objA) return false;
  if (!objB) return false;
  if (typeof objA !== "object") return false;
  if (typeof objB !== "object") return false;
  var aKeys = Object.keys(objA);
  var bKeys = Object.keys(objB);
  var length = aKeys.length;
  if (bKeys.length !== length) return false;

  for (var _i = 0, _aKeys = aKeys; _i < _aKeys.length; _i++) {
    var key = _aKeys[_i];

    if (objA[key] !== objB[key]) {
      return false;
    }
  }

  return true;
}




/***/ }),

/***/ "../../node_modules/.pnpm/reakit-utils@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-utils/es/useForkRef.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   N: () => (/* binding */ useForkRef)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");


// https://github.com/mui-org/material-ui/blob/2bcc874cf07b81202968f769cb9c2398c7c11311/packages/material-ui/src/utils/useForkRef.js

function setRef(ref, value) {
  if (value === void 0) {
    value = null;
  }

  if (!ref) return;

  if (typeof ref === "function") {
    ref(value);
  } else {
    ref.current = value;
  }
}
/**
 * Merges up to two React Refs into a single memoized function React Ref so you
 * can pass it to an element.
 *
 * @example
 * import React from "react";
 * import { useForkRef } from "reakit-utils";
 *
 * const Component = React.forwardRef((props, ref) => {
 *   const internalRef = React.useRef();
 *   return <div {...props} ref={useForkRef(internalRef, ref)} />;
 * });
 */


function useForkRef(refA, refB) {
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.useMemo)(function () {
    if (refA == null && refB == null) {
      return null;
    }

    return function (value) {
      setRef(refA, value);
      setRef(refB, value);
    };
  }, [refA, refB]);
}




/***/ }),

/***/ "../../node_modules/.pnpm/reakit-utils@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-utils/es/useIsomorphicEffect.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   o: () => (/* binding */ useIsomorphicEffect)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var _canUseDOM_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/reakit-utils@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-utils/es/canUseDOM.js");





/**
 * `React.useLayoutEffect` that fallbacks to `React.useEffect` on server side
 * rendering.
 */

var useIsomorphicEffect = !_canUseDOM_js__WEBPACK_IMPORTED_MODULE_1__/* .canUseDOM */ .S ? react__WEBPACK_IMPORTED_MODULE_0__.useEffect : react__WEBPACK_IMPORTED_MODULE_0__.useLayoutEffect;




/***/ }),

/***/ "../../node_modules/.pnpm/reakit-utils@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-utils/es/useLiveRef.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   h: () => (/* binding */ useLiveRef)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var _useIsomorphicEffect_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/reakit-utils@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-utils/es/useIsomorphicEffect.js");






/**
 * A `React.Ref` that keeps track of the passed `value`.
 */

function useLiveRef(value) {
  var ref = (0,react__WEBPACK_IMPORTED_MODULE_0__.useRef)(value);
  (0,_useIsomorphicEffect_js__WEBPACK_IMPORTED_MODULE_1__/* .useIsomorphicEffect */ .o)(function () {
    ref.current = value;
  });
  return ref;
}




/***/ }),

/***/ "../../node_modules/.pnpm/reakit-utils@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-utils/es/useSealedState.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   N: () => (/* binding */ useSealedState)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");


/**
 * React custom hook that returns the very first value passed to `initialState`,
 * even if it changes between re-renders.
 */
function useSealedState(initialState) {
  var _React$useState = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(initialState),
      sealed = _React$useState[0];

  return sealed;
}




/***/ }),

/***/ "../../node_modules/.pnpm/reakit-warning@0.6.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-warning/es/index.js":
/***/ ((__unused_webpack_module, __unused_webpack___webpack_exports__, __webpack_require__) => {


// UNUSED EXPORTS: useWarning, warning

// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/reakit-warning@0.6.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-warning/es/useWarning.js





function isRefObject(ref) {
  return isObject(ref) && "current" in ref;
}
/**
 * Logs `messages` to the console using `console.warn` based on a `condition`.
 * This should be used inside components.
 */


function useWarning(condition) {
  for (var _len = arguments.length, messages = new Array(_len > 1 ? _len - 1 : 0), _key = 1; _key < _len; _key++) {
    messages[_key - 1] = arguments[_key];
  }

  if (false) {}
}



;// CONCATENATED MODULE: ../../node_modules/.pnpm/reakit-warning@0.6.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-warning/es/index.js







/***/ }),

/***/ "../../node_modules/.pnpm/reakit@1.3.11_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit/es/Composite/Composite.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  e: () => (/* binding */ Composite),
  T: () => (/* binding */ useComposite)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/reakit@1.3.11_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit/es/_rollupPluginBabelHelpers-1f0bf8c2.js
var _rollupPluginBabelHelpers_1f0bf8c2 = __webpack_require__("../../node_modules/.pnpm/reakit@1.3.11_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit/es/_rollupPluginBabelHelpers-1f0bf8c2.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/reakit-system@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-system/es/createComponent.js + 4 modules
var createComponent = __webpack_require__("../../node_modules/.pnpm/reakit-system@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-system/es/createComponent.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/reakit-system@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-system/es/createHook.js + 4 modules
var createHook = __webpack_require__("../../node_modules/.pnpm/reakit-system@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-system/es/createHook.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/reakit-utils@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-utils/es/useForkRef.js
var useForkRef = __webpack_require__("../../node_modules/.pnpm/reakit-utils@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-utils/es/useForkRef.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/reakit-warning@0.6.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-warning/es/index.js + 1 modules
var es = __webpack_require__("../../node_modules/.pnpm/reakit-warning@0.6.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-warning/es/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/reakit-utils@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-utils/es/useLiveRef.js
var useLiveRef = __webpack_require__("../../node_modules/.pnpm/reakit-utils@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-utils/es/useLiveRef.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/reakit-utils@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-utils/es/isSelfTarget.js
var isSelfTarget = __webpack_require__("../../node_modules/.pnpm/reakit-utils@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-utils/es/isSelfTarget.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/reakit@1.3.11_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit/es/Role/Role.js
var Role = __webpack_require__("../../node_modules/.pnpm/reakit@1.3.11_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit/es/Role/Role.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/reakit@1.3.11_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit/es/Tabbable/Tabbable.js + 2 modules
var Tabbable = __webpack_require__("../../node_modules/.pnpm/reakit@1.3.11_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit/es/Tabbable/Tabbable.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/reakit-system@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-system/es/useCreateElement.js
var useCreateElement = __webpack_require__("../../node_modules/.pnpm/reakit-system@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-system/es/useCreateElement.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/reakit-utils@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-utils/es/getDocument.js
var getDocument = __webpack_require__("../../node_modules/.pnpm/reakit-utils@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-utils/es/getDocument.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/reakit-utils@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-utils/es/_rollupPluginBabelHelpers-1f0bf8c2.js
var es_rollupPluginBabelHelpers_1f0bf8c2 = __webpack_require__("../../node_modules/.pnpm/reakit-utils@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-utils/es/_rollupPluginBabelHelpers-1f0bf8c2.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/reakit-utils@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-utils/es/createEvent.js
var createEvent = __webpack_require__("../../node_modules/.pnpm/reakit-utils@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-utils/es/createEvent.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/reakit-utils@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-utils/es/fireBlurEvent.js




function createFocusEvent(element, type, eventInit) {
  if (eventInit === void 0) {
    eventInit = {};
  }

  if (typeof FocusEvent === "function") {
    return new FocusEvent(type, eventInit);
  }

  return (0,createEvent/* createEvent */.l)(element, type, eventInit);
}
/**
 * Creates and dispatches a blur event in a way that also works on IE 11.
 *
 * @example
 * import { fireBlurEvent } from "reakit-utils";
 *
 * fireBlurEvent(document.getElementById("id"));
 */


function fireBlurEvent(element, eventInit) {
  var event = createFocusEvent(element, "blur", eventInit);
  var defaultAllowed = element.dispatchEvent(event);

  var bubbleInit = (0,es_rollupPluginBabelHelpers_1f0bf8c2.a)((0,es_rollupPluginBabelHelpers_1f0bf8c2.a)({}, eventInit), {}, {
    bubbles: true
  });

  element.dispatchEvent(createFocusEvent(element, "focusout", bubbleInit));
  return defaultAllowed;
}



// EXTERNAL MODULE: ../../node_modules/.pnpm/reakit-utils@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-utils/es/getWindow.js
var getWindow = __webpack_require__("../../node_modules/.pnpm/reakit-utils@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-utils/es/getWindow.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/reakit-utils@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-utils/es/fireKeyboardEvent.js



function createKeyboardEvent(element, type, eventInit) {
  if (eventInit === void 0) {
    eventInit = {};
  }

  if (typeof KeyboardEvent === "function") {
    return new KeyboardEvent(type, eventInit);
  } // IE 11 doesn't support Event constructors


  var event = (0,getDocument/* getDocument */.Y)(element).createEvent("KeyboardEvent");
  event.initKeyboardEvent(type, eventInit.bubbles, eventInit.cancelable, (0,getWindow/* getWindow */.z)(element), eventInit.key, eventInit.location, eventInit.ctrlKey, eventInit.altKey, eventInit.shiftKey, eventInit.metaKey);
  return event;
}
/**
 * Creates and dispatches `KeyboardEvent` in a way that also works on IE 11.
 *
 * @example
 * import { fireKeyboardEvent } from "reakit-utils";
 *
 * fireKeyboardEvent(document.getElementById("id"), "keydown", {
 *   key: "ArrowDown",
 *   shiftKey: true,
 * });
 */


function fireKeyboardEvent(element, type, eventInit) {
  return element.dispatchEvent(createKeyboardEvent(element, type, eventInit));
}



// EXTERNAL MODULE: ../../node_modules/.pnpm/reakit-utils@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-utils/es/canUseDOM.js
var canUseDOM = __webpack_require__("../../node_modules/.pnpm/reakit-utils@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-utils/es/canUseDOM.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/reakit-utils@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-utils/es/getActiveElement.js
var getActiveElement = __webpack_require__("../../node_modules/.pnpm/reakit-utils@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-utils/es/getActiveElement.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/reakit-utils@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-utils/es/getNextActiveElementOnBlur.js





var isIE11 = canUseDOM/* canUseDOM */.S && "msCrypto" in window;
/**
 * Cross-browser method that returns the next active element (the element that
 * is receiving focus) after a blur event is dispatched. It receives the blur
 * event object as the argument.
 *
 * @example
 * import { getNextActiveElementOnBlur } from "reakit-utils";
 *
 * const element = document.getElementById("id");
 * element.addEventListener("blur", (event) => {
 *   const nextActiveElement = getNextActiveElementOnBlur(event);
 * });
 */

function getNextActiveElementOnBlur(event) {
  // IE 11 doesn't support event.relatedTarget on blur.
  // document.activeElement points the the next active element.
  // On modern browsers, document.activeElement points to the current target.
  if (isIE11) {
    var activeElement = (0,getActiveElement/* getActiveElement */.b)(event.currentTarget);
    return activeElement;
  }

  return event.relatedTarget;
}



// EXTERNAL MODULE: ../../node_modules/.pnpm/reakit@1.3.11_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit/es/reverse-30eaa122.js
var reverse_30eaa122 = __webpack_require__("../../node_modules/.pnpm/reakit@1.3.11_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit/es/reverse-30eaa122.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/reakit@1.3.11_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit/es/getCurrentId-5aa9849e.js
var getCurrentId_5aa9849e = __webpack_require__("../../node_modules/.pnpm/reakit@1.3.11_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit/es/getCurrentId-5aa9849e.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/reakit@1.3.11_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit/es/findEnabledItemById-8ddca752.js
var findEnabledItemById_8ddca752 = __webpack_require__("../../node_modules/.pnpm/reakit@1.3.11_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit/es/findEnabledItemById-8ddca752.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/reakit@1.3.11_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit/es/__keys-6742f591.js
var _keys_6742f591 = __webpack_require__("../../node_modules/.pnpm/reakit@1.3.11_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit/es/__keys-6742f591.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/reakit@1.3.11_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit/es/userFocus-e16425e3.js
var userFocus_e16425e3 = __webpack_require__("../../node_modules/.pnpm/reakit@1.3.11_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit/es/userFocus-e16425e3.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/reakit@1.3.11_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit/es/Composite/Composite.js





























var Composite_isIE11 = canUseDOM/* canUseDOM */.S && "msCrypto" in window;

function canProxyKeyboardEvent(event) {
  if (!(0,isSelfTarget/* isSelfTarget */.u)(event)) return false;
  if (event.metaKey) return false;
  if (event.key === "Tab") return false;
  return true;
}

function useKeyboardEventProxy(virtual, currentItem, htmlEventHandler) {
  var eventHandlerRef = (0,useLiveRef/* useLiveRef */.h)(htmlEventHandler);
  return (0,react.useCallback)(function (event) {
    var _eventHandlerRef$curr;

    (_eventHandlerRef$curr = eventHandlerRef.current) === null || _eventHandlerRef$curr === void 0 ? void 0 : _eventHandlerRef$curr.call(eventHandlerRef, event);
    if (event.defaultPrevented) return;

    if (virtual && canProxyKeyboardEvent(event)) {
      var currentElement = currentItem === null || currentItem === void 0 ? void 0 : currentItem.ref.current;

      if (currentElement) {
        if (!fireKeyboardEvent(currentElement, event.type, event)) {
          event.preventDefault();
        } // The event will be triggered on the composite item and then
        // propagated up to this composite element again, so we can pretend
        // that it wasn't called on this component in the first place.


        if (event.currentTarget.contains(currentElement)) {
          event.stopPropagation();
        }
      }
    }
  }, [virtual, currentItem]);
} // istanbul ignore next


function useActiveElementRef(elementRef) {
  var activeElementRef = (0,react.useRef)(null);
  (0,react.useEffect)(function () {
    var document = (0,getDocument/* getDocument */.Y)(elementRef.current);

    var onFocus = function onFocus(event) {
      var target = event.target;
      activeElementRef.current = target;
    };

    document.addEventListener("focus", onFocus, true);
    return function () {
      document.removeEventListener("focus", onFocus, true);
    };
  }, []);
  return activeElementRef;
}

function findFirstEnabledItemInTheLastRow(items) {
  return (0,getCurrentId_5aa9849e.f)((0,reverse_30eaa122.f)((0,reverse_30eaa122.r)((0,reverse_30eaa122.g)(items))));
}

function isItem(items, element) {
  return items === null || items === void 0 ? void 0 : items.some(function (item) {
    return !!element && item.ref.current === element;
  });
}

function useScheduleUserFocus(currentItem) {
  var currentItemRef = (0,useLiveRef/* useLiveRef */.h)(currentItem);

  var _React$useReducer = (0,react.useReducer)(function (n) {
    return n + 1;
  }, 0),
      scheduled = _React$useReducer[0],
      schedule = _React$useReducer[1];

  (0,react.useEffect)(function () {
    var _currentItemRef$curre;

    var currentElement = (_currentItemRef$curre = currentItemRef.current) === null || _currentItemRef$curre === void 0 ? void 0 : _currentItemRef$curre.ref.current;

    if (scheduled && currentElement) {
      (0,userFocus_e16425e3.u)(currentElement);
    }
  }, [scheduled]);
  return schedule;
}

var useComposite = (0,createHook/* createHook */.a)({
  name: "Composite",
  compose: [Tabbable/* useTabbable */.b],
  keys: _keys_6742f591.C,
  useOptions: function useOptions(options) {
    return (0,_rollupPluginBabelHelpers_1f0bf8c2.a)((0,_rollupPluginBabelHelpers_1f0bf8c2.a)({}, options), {}, {
      currentId: (0,getCurrentId_5aa9849e.g)(options)
    });
  },
  useProps: function useProps(options, _ref) {
    var htmlRef = _ref.ref,
        htmlOnFocusCapture = _ref.onFocusCapture,
        htmlOnFocus = _ref.onFocus,
        htmlOnBlurCapture = _ref.onBlurCapture,
        htmlOnKeyDown = _ref.onKeyDown,
        htmlOnKeyDownCapture = _ref.onKeyDownCapture,
        htmlOnKeyUpCapture = _ref.onKeyUpCapture,
        htmlProps = (0,_rollupPluginBabelHelpers_1f0bf8c2._)(_ref, ["ref", "onFocusCapture", "onFocus", "onBlurCapture", "onKeyDown", "onKeyDownCapture", "onKeyUpCapture"]);

    var ref = (0,react.useRef)(null);
    var currentItem = (0,findEnabledItemById_8ddca752.f)(options.items, options.currentId);
    var previousElementRef = (0,react.useRef)(null);
    var onFocusCaptureRef = (0,useLiveRef/* useLiveRef */.h)(htmlOnFocusCapture);
    var onFocusRef = (0,useLiveRef/* useLiveRef */.h)(htmlOnFocus);
    var onBlurCaptureRef = (0,useLiveRef/* useLiveRef */.h)(htmlOnBlurCapture);
    var onKeyDownRef = (0,useLiveRef/* useLiveRef */.h)(htmlOnKeyDown);
    var scheduleUserFocus = useScheduleUserFocus(currentItem); // IE 11 doesn't support event.relatedTarget, so we use the active element
    // ref instead.

    var activeElementRef = Composite_isIE11 ? useActiveElementRef(ref) : undefined;
    (0,react.useEffect)(function () {
      var element = ref.current;

      if (options.unstable_moves && !currentItem) {
         false ? 0 : void 0; // If composite.move(null) has been called, the composite container
        // will receive focus.

        element === null || element === void 0 ? void 0 : element.focus();
      }
    }, [options.unstable_moves, currentItem]);
    var onKeyDownCapture = useKeyboardEventProxy(options.unstable_virtual, currentItem, htmlOnKeyDownCapture);
    var onKeyUpCapture = useKeyboardEventProxy(options.unstable_virtual, currentItem, htmlOnKeyUpCapture);
    var onFocusCapture = (0,react.useCallback)(function (event) {
      var _onFocusCaptureRef$cu;

      (_onFocusCaptureRef$cu = onFocusCaptureRef.current) === null || _onFocusCaptureRef$cu === void 0 ? void 0 : _onFocusCaptureRef$cu.call(onFocusCaptureRef, event);
      if (event.defaultPrevented) return;
      if (!options.unstable_virtual) return; // IE11 doesn't support event.relatedTarget, so we use the active
      // element ref instead.

      var previousActiveElement = (activeElementRef === null || activeElementRef === void 0 ? void 0 : activeElementRef.current) || event.relatedTarget;
      var previousActiveElementWasItem = isItem(options.items, previousActiveElement);

      if ((0,isSelfTarget/* isSelfTarget */.u)(event) && previousActiveElementWasItem) {
        // Composite has been focused as a result of an item receiving focus.
        // The composite item will move focus back to the composite
        // container. In this case, we don't want to propagate this
        // additional event nor call the onFocus handler passed to
        // <Composite onFocus={...} />.
        event.stopPropagation(); // We keep track of the previous active item element so we can
        // manually fire a blur event on it later when the focus is moved to
        // another item on the onBlurCapture event below.

        previousElementRef.current = previousActiveElement;
      }
    }, [options.unstable_virtual, options.items]);
    var onFocus = (0,react.useCallback)(function (event) {
      var _onFocusRef$current;

      (_onFocusRef$current = onFocusRef.current) === null || _onFocusRef$current === void 0 ? void 0 : _onFocusRef$current.call(onFocusRef, event);
      if (event.defaultPrevented) return;

      if (options.unstable_virtual) {
        if ((0,isSelfTarget/* isSelfTarget */.u)(event)) {
          // This means that the composite element has been focused while the
          // composite item has not. For example, by clicking on the
          // composite element without touching any item, or by tabbing into
          // the composite element. In this case, we want to trigger focus on
          // the item, just like it would happen with roving tabindex.
          // When it receives focus, the composite item will put focus back
          // on the composite element, in which case hasItemWithFocus will be
          // true.
          scheduleUserFocus();
        }
      } else if ((0,isSelfTarget/* isSelfTarget */.u)(event)) {
        var _options$setCurrentId;

        // When the roving tabindex composite gets intentionally focused (for
        // example, by clicking directly on it, and not on an item), we make
        // sure to set the current id to null (which means the composite
        // itself is focused).
        (_options$setCurrentId = options.setCurrentId) === null || _options$setCurrentId === void 0 ? void 0 : _options$setCurrentId.call(options, null);
      }
    }, [options.unstable_virtual, options.setCurrentId]);
    var onBlurCapture = (0,react.useCallback)(function (event) {
      var _onBlurCaptureRef$cur;

      (_onBlurCaptureRef$cur = onBlurCaptureRef.current) === null || _onBlurCaptureRef$cur === void 0 ? void 0 : _onBlurCaptureRef$cur.call(onBlurCaptureRef, event);
      if (event.defaultPrevented) return;
      if (!options.unstable_virtual) return; // When virtual is set to true, we move focus from the composite
      // container (this component) to the composite item that is being
      // selected. Then we move focus back to the composite container. This
      // is so we can provide the same API as the roving tabindex method,
      // which means people can attach onFocus/onBlur handlers on the
      // CompositeItem component regardless of whether it's virtual or not.
      // This sequence of blurring and focusing items and composite may be
      // confusing, so we ignore intermediate focus and blurs by stopping its
      // propagation and not calling the passed onBlur handler (htmlOnBlur).

      var currentElement = (currentItem === null || currentItem === void 0 ? void 0 : currentItem.ref.current) || null;
      var nextActiveElement = getNextActiveElementOnBlur(event);
      var nextActiveElementIsItem = isItem(options.items, nextActiveElement);

      if ((0,isSelfTarget/* isSelfTarget */.u)(event) && nextActiveElementIsItem) {
        // This is an intermediate blur event: blurring the composite
        // container to focus an item (nextActiveElement).
        if (nextActiveElement === currentElement) {
          // The next active element will be the same as the current item in
          // the state in two scenarios:
          //   - Moving focus with keyboard: the state is updated before the
          // blur event is triggered, so here the current item is already
          // pointing to the next active element.
          //   - Clicking on the current active item with a pointer: this
          // will trigger blur on the composite element and then the next
          // active element will be the same as the current item. Clicking on
          // an item other than the current one doesn't end up here as the
          // currentItem state will be updated only after it.
          if (previousElementRef.current && previousElementRef.current !== nextActiveElement) {
            // If there's a previous active item and it's not a click action,
            // then we fire a blur event on it so it will work just like if
            // it had DOM focus before (like when using roving tabindex).
            fireBlurEvent(previousElementRef.current, event);
          }
        } else if (currentElement) {
          // This will be true when the next active element is not the
          // current element, but there's a current item. This will only
          // happen when clicking with a pointer on a different item, when
          // there's already an item selected, in which case currentElement
          // is the item that is getting blurred, and nextActiveElement is
          // the item that is being clicked.
          fireBlurEvent(currentElement, event);
        } // We want to ignore intermediate blur events, so we stop its
        // propagation and return early so onFocus will not be called.


        event.stopPropagation();
      } else {
        var targetIsItem = isItem(options.items, event.target);

        if (!targetIsItem && currentElement) {
          // If target is not a composite item, it may be the composite
          // element itself (isSelfTarget) or a tabbable element inside the
          // composite widget. This may be triggered by clicking outside the
          // composite widget or by tabbing out of it. In either cases we
          // want to fire a blur event on the current item.
          fireBlurEvent(currentElement, event);
        }
      }
    }, [options.unstable_virtual, options.items, currentItem]);
    var onKeyDown = (0,react.useCallback)(function (event) {
      var _onKeyDownRef$current, _options$groups;

      (_onKeyDownRef$current = onKeyDownRef.current) === null || _onKeyDownRef$current === void 0 ? void 0 : _onKeyDownRef$current.call(onKeyDownRef, event);
      if (event.defaultPrevented) return;
      if (options.currentId !== null) return;
      if (!(0,isSelfTarget/* isSelfTarget */.u)(event)) return;
      var isVertical = options.orientation !== "horizontal";
      var isHorizontal = options.orientation !== "vertical";
      var isGrid = !!((_options$groups = options.groups) !== null && _options$groups !== void 0 && _options$groups.length);

      var up = function up() {
        if (isGrid) {
          var item = findFirstEnabledItemInTheLastRow(options.items);

          if (item !== null && item !== void 0 && item.id) {
            var _options$move;

            (_options$move = options.move) === null || _options$move === void 0 ? void 0 : _options$move.call(options, item.id);
          }
        } else {
          var _options$last;

          (_options$last = options.last) === null || _options$last === void 0 ? void 0 : _options$last.call(options);
        }
      };

      var keyMap = {
        ArrowUp: (isGrid || isVertical) && up,
        ArrowRight: (isGrid || isHorizontal) && options.first,
        ArrowDown: (isGrid || isVertical) && options.first,
        ArrowLeft: (isGrid || isHorizontal) && options.last,
        Home: options.first,
        End: options.last,
        PageUp: options.first,
        PageDown: options.last
      };
      var action = keyMap[event.key];

      if (action) {
        event.preventDefault();
        action();
      }
    }, [options.currentId, options.orientation, options.groups, options.items, options.move, options.last, options.first]);
    return (0,_rollupPluginBabelHelpers_1f0bf8c2.a)({
      ref: (0,useForkRef/* useForkRef */.N)(ref, htmlRef),
      id: options.baseId,
      onFocus: onFocus,
      onFocusCapture: onFocusCapture,
      onBlurCapture: onBlurCapture,
      onKeyDownCapture: onKeyDownCapture,
      onKeyDown: onKeyDown,
      onKeyUpCapture: onKeyUpCapture,
      "aria-activedescendant": options.unstable_virtual ? (currentItem === null || currentItem === void 0 ? void 0 : currentItem.id) || undefined : undefined
    }, htmlProps);
  },
  useComposeProps: function useComposeProps(options, htmlProps) {
    htmlProps = (0,Role/* useRole */.I)(options, htmlProps, true);
    var tabbableHTMLProps = (0,Tabbable/* useTabbable */.b)(options, htmlProps, true);

    if (options.unstable_virtual || options.currentId === null) {
      // Composite will only be tabbable by default if the focus is managed
      // using aria-activedescendant, which requires DOM focus on the container
      // element (the composite)
      return (0,_rollupPluginBabelHelpers_1f0bf8c2.a)({
        tabIndex: 0
      }, tabbableHTMLProps);
    }

    return (0,_rollupPluginBabelHelpers_1f0bf8c2.a)((0,_rollupPluginBabelHelpers_1f0bf8c2.a)({}, htmlProps), {}, {
      ref: tabbableHTMLProps.ref
    });
  }
});
var Composite = (0,createComponent/* createComponent */.a)({
  as: "div",
  useHook: useComposite,
  useCreateElement: function useCreateElement$1(type, props, children) {
     false ? 0 : void 0;
    return (0,useCreateElement/* useCreateElement */.U)(type, props, children);
  }
});




/***/ }),

/***/ "../../node_modules/.pnpm/reakit@1.3.11_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit/es/Composite/CompositeItem.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  l: () => (/* binding */ CompositeItem),
  k: () => (/* binding */ useCompositeItem)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/reakit@1.3.11_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit/es/_rollupPluginBabelHelpers-1f0bf8c2.js
var _rollupPluginBabelHelpers_1f0bf8c2 = __webpack_require__("../../node_modules/.pnpm/reakit@1.3.11_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit/es/_rollupPluginBabelHelpers-1f0bf8c2.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/reakit-system@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-system/es/createComponent.js + 4 modules
var createComponent = __webpack_require__("../../node_modules/.pnpm/reakit-system@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-system/es/createComponent.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/reakit-system@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-system/es/createHook.js + 4 modules
var createHook = __webpack_require__("../../node_modules/.pnpm/reakit-system@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-system/es/createHook.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/reakit-utils@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-utils/es/useForkRef.js
var useForkRef = __webpack_require__("../../node_modules/.pnpm/reakit-utils@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-utils/es/useForkRef.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/reakit-warning@0.6.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-warning/es/index.js + 1 modules
var es = __webpack_require__("../../node_modules/.pnpm/reakit-warning@0.6.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-warning/es/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/reakit-utils@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-utils/es/useLiveRef.js
var useLiveRef = __webpack_require__("../../node_modules/.pnpm/reakit-utils@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-utils/es/useLiveRef.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/reakit-utils@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-utils/es/isSelfTarget.js
var isSelfTarget = __webpack_require__("../../node_modules/.pnpm/reakit-utils@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-utils/es/isSelfTarget.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/reakit-utils@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-utils/es/hasFocusWithin.js
var hasFocusWithin = __webpack_require__("../../node_modules/.pnpm/reakit-utils@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-utils/es/hasFocusWithin.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/reakit-utils@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-utils/es/isPortalEvent.js
var isPortalEvent = __webpack_require__("../../node_modules/.pnpm/reakit-utils@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-utils/es/isPortalEvent.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/reakit-utils@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-utils/es/isButton.js
var isButton = __webpack_require__("../../node_modules/.pnpm/reakit-utils@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-utils/es/isButton.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/reakit@1.3.11_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit/es/Tabbable/Tabbable.js + 2 modules
var Tabbable = __webpack_require__("../../node_modules/.pnpm/reakit@1.3.11_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit/es/Tabbable/Tabbable.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/reakit@1.3.11_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit/es/Clickable/Clickable.js


















// Automatically generated
var CLICKABLE_KEYS = ["unstable_clickOnEnter", "unstable_clickOnSpace"];

function isNativeClick(event) {
  var element = event.currentTarget;
  if (!event.isTrusted) return false; // istanbul ignore next: can't test trusted events yet

  return (0,isButton/* isButton */.B)(element) || element.tagName === "INPUT" || element.tagName === "TEXTAREA" || element.tagName === "A" || element.tagName === "SELECT";
}

var useClickable = (0,createHook/* createHook */.a)({
  name: "Clickable",
  compose: Tabbable/* useTabbable */.b,
  keys: CLICKABLE_KEYS,
  useOptions: function useOptions(_ref) {
    var _ref$unstable_clickOn = _ref.unstable_clickOnEnter,
        unstable_clickOnEnter = _ref$unstable_clickOn === void 0 ? true : _ref$unstable_clickOn,
        _ref$unstable_clickOn2 = _ref.unstable_clickOnSpace,
        unstable_clickOnSpace = _ref$unstable_clickOn2 === void 0 ? true : _ref$unstable_clickOn2,
        options = (0,_rollupPluginBabelHelpers_1f0bf8c2._)(_ref, ["unstable_clickOnEnter", "unstable_clickOnSpace"]);

    return (0,_rollupPluginBabelHelpers_1f0bf8c2.a)({
      unstable_clickOnEnter: unstable_clickOnEnter,
      unstable_clickOnSpace: unstable_clickOnSpace
    }, options);
  },
  useProps: function useProps(options, _ref2) {
    var htmlOnKeyDown = _ref2.onKeyDown,
        htmlOnKeyUp = _ref2.onKeyUp,
        htmlProps = (0,_rollupPluginBabelHelpers_1f0bf8c2._)(_ref2, ["onKeyDown", "onKeyUp"]);

    var _React$useState = (0,react.useState)(false),
        active = _React$useState[0],
        setActive = _React$useState[1];

    var onKeyDownRef = (0,useLiveRef/* useLiveRef */.h)(htmlOnKeyDown);
    var onKeyUpRef = (0,useLiveRef/* useLiveRef */.h)(htmlOnKeyUp);
    var onKeyDown = (0,react.useCallback)(function (event) {
      var _onKeyDownRef$current;

      (_onKeyDownRef$current = onKeyDownRef.current) === null || _onKeyDownRef$current === void 0 ? void 0 : _onKeyDownRef$current.call(onKeyDownRef, event);
      if (event.defaultPrevented) return;
      if (options.disabled) return;
      if (event.metaKey) return;
      if (!(0,isSelfTarget/* isSelfTarget */.u)(event)) return;
      var isEnter = options.unstable_clickOnEnter && event.key === "Enter";
      var isSpace = options.unstable_clickOnSpace && event.key === " ";

      if (isEnter || isSpace) {
        if (isNativeClick(event)) return;
        event.preventDefault();

        if (isEnter) {
          event.currentTarget.click();
        } else if (isSpace) {
          setActive(true);
        }
      }
    }, [options.disabled, options.unstable_clickOnEnter, options.unstable_clickOnSpace]);
    var onKeyUp = (0,react.useCallback)(function (event) {
      var _onKeyUpRef$current;

      (_onKeyUpRef$current = onKeyUpRef.current) === null || _onKeyUpRef$current === void 0 ? void 0 : _onKeyUpRef$current.call(onKeyUpRef, event);
      if (event.defaultPrevented) return;
      if (options.disabled) return;
      if (event.metaKey) return;
      var isSpace = options.unstable_clickOnSpace && event.key === " ";

      if (active && isSpace) {
        setActive(false);
        event.currentTarget.click();
      }
    }, [options.disabled, options.unstable_clickOnSpace, active]);
    return (0,_rollupPluginBabelHelpers_1f0bf8c2.a)({
      "data-active": active || undefined,
      onKeyDown: onKeyDown,
      onKeyUp: onKeyUp
    }, htmlProps);
  }
});
var Clickable = (0,createComponent/* createComponent */.a)({
  as: "button",
  memo: true,
  useHook: useClickable
});



// EXTERNAL MODULE: ../../node_modules/.pnpm/reakit-utils@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-utils/es/getDocument.js
var getDocument = __webpack_require__("../../node_modules/.pnpm/reakit-utils@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-utils/es/getDocument.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/reakit@1.3.11_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit/es/getCurrentId-5aa9849e.js
var getCurrentId_5aa9849e = __webpack_require__("../../node_modules/.pnpm/reakit@1.3.11_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit/es/getCurrentId-5aa9849e.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/reakit@1.3.11_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit/es/__keys-6742f591.js
var _keys_6742f591 = __webpack_require__("../../node_modules/.pnpm/reakit@1.3.11_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit/es/__keys-6742f591.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/reakit@1.3.11_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit/es/userFocus-e16425e3.js
var userFocus_e16425e3 = __webpack_require__("../../node_modules/.pnpm/reakit@1.3.11_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit/es/userFocus-e16425e3.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/reakit-utils@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-utils/es/isTextField.js
/**
 * Check whether the given element is a text field, where text field is defined
 * by the ability to select within the input, or that it is contenteditable.
 *
 * @example
 * import { isTextField } from "reakit-utils";
 *
 * isTextField(document.querySelector("div")); // false
 * isTextField(document.querySelector("input")); // true
 * isTextField(document.querySelector("input[type='button']")); // false
 * isTextField(document.querySelector("textarea")); // true
 * isTextField(document.querySelector("div[contenteditable='true']")); // true
 */
function isTextField(element) {
  try {
    var isTextInput = element instanceof HTMLInputElement && element.selectionStart !== null;
    var isTextArea = element.tagName === "TEXTAREA";
    var isContentEditable = element.contentEditable === "true";
    return isTextInput || isTextArea || isContentEditable || false;
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



// EXTERNAL MODULE: ../../node_modules/.pnpm/reakit-utils@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-utils/es/getActiveElement.js
var getActiveElement = __webpack_require__("../../node_modules/.pnpm/reakit-utils@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-utils/es/getActiveElement.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/reakit-utils@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-utils/es/hasFocus.js



/**
 * Checks if `element` has focus. Elements that are referenced by
 * `aria-activedescendant` are also considered.
 *
 * @example
 * import { hasFocus } from "reakit-utils";
 *
 * hasFocus(document.getElementById("id"));
 */

function hasFocus(element) {
  var activeElement = (0,getActiveElement/* getActiveElement */.b)(element);
  if (!activeElement) return false;
  if (activeElement === element) return true;
  var activeDescendant = activeElement.getAttribute("aria-activedescendant");
  if (!activeDescendant) return false;
  return activeDescendant === element.id;
}



;// CONCATENATED MODULE: ../../node_modules/.pnpm/reakit-utils@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-utils/es/ensureFocus.js




/**
 * Ensures `element` will receive focus if it's not already.
 *
 * @example
 * import { ensureFocus } from "reakit-utils";
 *
 * ensureFocus(document.activeElement); // does nothing
 *
 * const element = document.querySelector("input");
 *
 * ensureFocus(element); // focuses element
 * ensureFocus(element, { preventScroll: true }); // focuses element preventing scroll jump
 *
 * function isActive(el) {
 *   return el.dataset.active === "true";
 * }
 *
 * ensureFocus(document.querySelector("[data-active='true']"), { isActive }); // does nothing
 *
 * @returns {number} `requestAnimationFrame` call ID so it can be passed to `cancelAnimationFrame` if needed.
 */
function ensureFocus(element, _temp) {
  var _ref = _temp === void 0 ? {} : _temp,
      preventScroll = _ref.preventScroll,
      _ref$isActive = _ref.isActive,
      isActive = _ref$isActive === void 0 ? hasFocus : _ref$isActive;

  if (isActive(element)) return -1;
  element.focus({
    preventScroll: preventScroll
  });
  if (isActive(element)) return -1;
  return requestAnimationFrame(function () {
    element.focus({
      preventScroll: preventScroll
    });
  });
}



// EXTERNAL MODULE: ../../node_modules/.pnpm/reakit@1.3.11_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit/es/Id/Id.js
var Id = __webpack_require__("../../node_modules/.pnpm/reakit@1.3.11_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit/es/Id/Id.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/reakit-utils@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-utils/es/createEvent.js
var createEvent = __webpack_require__("../../node_modules/.pnpm/reakit-utils@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-utils/es/createEvent.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/reakit-utils@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-utils/es/fireEvent.js



/**
 * Creates and dispatches `Event` in a way that also works on IE 11.
 *
 * @example
 * import { fireEvent } from "reakit-utils";
 *
 * fireEvent(document.getElementById("id"), "blur", {
 *   bubbles: true,
 *   cancelable: true,
 * });
 */

function fireEvent(element, type, eventInit) {
  return element.dispatchEvent((0,createEvent/* createEvent */.l)(element, type, eventInit));
}



;// CONCATENATED MODULE: ../../node_modules/.pnpm/reakit@1.3.11_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit/es/setTextFieldValue-0a221f4e.js


function setTextFieldValue(element, value) {
  if (element instanceof HTMLInputElement || element instanceof HTMLTextAreaElement) {
    var _Object$getOwnPropert;

    var proto = Object.getPrototypeOf(element);
    var setValue = (_Object$getOwnPropert = Object.getOwnPropertyDescriptor(proto, "value")) === null || _Object$getOwnPropert === void 0 ? void 0 : _Object$getOwnPropert.set;

    if (setValue) {
      setValue.call(element, value);
      fireEvent(element, "input", {
        bubbles: true
      });
    }
  }
}



;// CONCATENATED MODULE: ../../node_modules/.pnpm/reakit@1.3.11_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit/es/Composite/CompositeItem.js





























function getWidget(itemElement) {
  return itemElement.querySelector("[data-composite-item-widget]");
}

function useItem(options) {
  return (0,react.useMemo)(function () {
    var _options$items;

    return (_options$items = options.items) === null || _options$items === void 0 ? void 0 : _options$items.find(function (item) {
      return options.id && item.id === options.id;
    });
  }, [options.items, options.id]);
}

function targetIsAnotherItem(event, items) {
  if ((0,isSelfTarget/* isSelfTarget */.u)(event)) return false;

  for (var _iterator = (0,_rollupPluginBabelHelpers_1f0bf8c2.b)(items), _step; !(_step = _iterator()).done;) {
    var item = _step.value;

    if (item.ref.current === event.target) {
      return true;
    }
  }

  return false;
}

var useCompositeItem = (0,createHook/* createHook */.a)({
  name: "CompositeItem",
  compose: [useClickable, Id/* unstable_useId */.W],
  keys: _keys_6742f591.b,
  propsAreEqual: function propsAreEqual(prev, next) {
    if (!next.id || prev.id !== next.id) {
      return useClickable.unstable_propsAreEqual(prev, next);
    }

    var prevCurrentId = prev.currentId,
        prevMoves = prev.unstable_moves,
        prevProps = (0,_rollupPluginBabelHelpers_1f0bf8c2._)(prev, ["currentId", "unstable_moves"]);

    var nextCurrentId = next.currentId,
        nextMoves = next.unstable_moves,
        nextProps = (0,_rollupPluginBabelHelpers_1f0bf8c2._)(next, ["currentId", "unstable_moves"]);

    if (nextCurrentId !== prevCurrentId) {
      if (next.id === nextCurrentId || next.id === prevCurrentId) {
        return false;
      }
    } else if (prevMoves !== nextMoves) {
      return false;
    }

    return useClickable.unstable_propsAreEqual(prevProps, nextProps);
  },
  useOptions: function useOptions(options) {
    return (0,_rollupPluginBabelHelpers_1f0bf8c2.a)((0,_rollupPluginBabelHelpers_1f0bf8c2.a)({}, options), {}, {
      id: options.id,
      currentId: (0,getCurrentId_5aa9849e.g)(options),
      unstable_clickOnSpace: options.unstable_hasActiveWidget ? false : options.unstable_clickOnSpace
    });
  },
  useProps: function useProps(options, _ref) {
    var _options$items2;

    var htmlRef = _ref.ref,
        _ref$tabIndex = _ref.tabIndex,
        htmlTabIndex = _ref$tabIndex === void 0 ? 0 : _ref$tabIndex,
        htmlOnMouseDown = _ref.onMouseDown,
        htmlOnFocus = _ref.onFocus,
        htmlOnBlurCapture = _ref.onBlurCapture,
        htmlOnKeyDown = _ref.onKeyDown,
        htmlOnClick = _ref.onClick,
        htmlProps = (0,_rollupPluginBabelHelpers_1f0bf8c2._)(_ref, ["ref", "tabIndex", "onMouseDown", "onFocus", "onBlurCapture", "onKeyDown", "onClick"]);

    var ref = (0,react.useRef)(null);
    var id = options.id;
    var trulyDisabled = options.disabled && !options.focusable;
    var isCurrentItem = options.currentId === id;
    var isCurrentItemRef = (0,useLiveRef/* useLiveRef */.h)(isCurrentItem);
    var hasFocusedComposite = (0,react.useRef)(false);
    var item = useItem(options);
    var onMouseDownRef = (0,useLiveRef/* useLiveRef */.h)(htmlOnMouseDown);
    var onFocusRef = (0,useLiveRef/* useLiveRef */.h)(htmlOnFocus);
    var onBlurCaptureRef = (0,useLiveRef/* useLiveRef */.h)(htmlOnBlurCapture);
    var onKeyDownRef = (0,useLiveRef/* useLiveRef */.h)(htmlOnKeyDown);
    var onClickRef = (0,useLiveRef/* useLiveRef */.h)(htmlOnClick);
    var shouldTabIndex = !options.unstable_virtual && !options.unstable_hasActiveWidget && isCurrentItem || // We don't want to set tabIndex="-1" when using CompositeItem as a
    // standalone component, without state props.
    !((_options$items2 = options.items) !== null && _options$items2 !== void 0 && _options$items2.length);
    (0,react.useEffect)(function () {
      var _options$registerItem;

      if (!id) return undefined;
      (_options$registerItem = options.registerItem) === null || _options$registerItem === void 0 ? void 0 : _options$registerItem.call(options, {
        id: id,
        ref: ref,
        disabled: !!trulyDisabled
      });
      return function () {
        var _options$unregisterIt;

        (_options$unregisterIt = options.unregisterItem) === null || _options$unregisterIt === void 0 ? void 0 : _options$unregisterIt.call(options, id);
      };
    }, [id, trulyDisabled, options.registerItem, options.unregisterItem]);
    (0,react.useEffect)(function () {
      var element = ref.current;

      if (!element) {
         false ? 0 : void 0;
        return;
      } // `moves` will be incremented whenever next, previous, up, down, first,
      // last or move have been called. This means that the composite item will
      // be focused whenever some of these functions are called. We're using
      // isCurrentItemRef instead of isCurrentItem because we don't want to
      // focus the item if isCurrentItem changes (and options.moves doesn't).


      if (options.unstable_moves && isCurrentItemRef.current) {
        (0,userFocus_e16425e3.u)(element);
      }
    }, [options.unstable_moves]);
    var onMouseDown = (0,react.useCallback)(function (event) {
      var _onMouseDownRef$curre;

      (_onMouseDownRef$curre = onMouseDownRef.current) === null || _onMouseDownRef$curre === void 0 ? void 0 : _onMouseDownRef$curre.call(onMouseDownRef, event);
      (0,userFocus_e16425e3.s)(event.currentTarget, true);
    }, []);
    var onFocus = (0,react.useCallback)(function (event) {
      var _onFocusRef$current, _options$setCurrentId;

      var shouldFocusComposite = (0,userFocus_e16425e3.h)(event.currentTarget);
      (0,userFocus_e16425e3.s)(event.currentTarget, false);
      (_onFocusRef$current = onFocusRef.current) === null || _onFocusRef$current === void 0 ? void 0 : _onFocusRef$current.call(onFocusRef, event);
      if (event.defaultPrevented) return;
      if ((0,isPortalEvent/* isPortalEvent */.h)(event)) return;
      if (!id) return;
      if (targetIsAnotherItem(event, options.items)) return;
      (_options$setCurrentId = options.setCurrentId) === null || _options$setCurrentId === void 0 ? void 0 : _options$setCurrentId.call(options, id); // When using aria-activedescendant, we want to make sure that the
      // composite container receives focus, not the composite item.
      // But we don't want to do this if the target is another focusable
      // element inside the composite item, such as CompositeItemWidget.

      if (shouldFocusComposite && options.unstable_virtual && options.baseId && (0,isSelfTarget/* isSelfTarget */.u)(event)) {
        var target = event.target;
        var composite = (0,getDocument/* getDocument */.Y)(target).getElementById(options.baseId);

        if (composite) {
          hasFocusedComposite.current = true;
          ensureFocus(composite);
        }
      }
    }, [id, options.items, options.setCurrentId, options.unstable_virtual, options.baseId]);
    var onBlurCapture = (0,react.useCallback)(function (event) {
      var _onBlurCaptureRef$cur;

      (_onBlurCaptureRef$cur = onBlurCaptureRef.current) === null || _onBlurCaptureRef$cur === void 0 ? void 0 : _onBlurCaptureRef$cur.call(onBlurCaptureRef, event);
      if (event.defaultPrevented) return;

      if (options.unstable_virtual && hasFocusedComposite.current) {
        // When hasFocusedComposite is true, composite has been focused right
        // after focusing this item. This is an intermediate blur event, so
        // we ignore it.
        hasFocusedComposite.current = false;
        event.preventDefault();
        event.stopPropagation();
      }
    }, [options.unstable_virtual]);
    var onKeyDown = (0,react.useCallback)(function (event) {
      var _onKeyDownRef$current;

      if (!(0,isSelfTarget/* isSelfTarget */.u)(event)) return;
      var isVertical = options.orientation !== "horizontal";
      var isHorizontal = options.orientation !== "vertical";
      var isGrid = !!(item !== null && item !== void 0 && item.groupId);
      var keyMap = {
        ArrowUp: (isGrid || isVertical) && options.up,
        ArrowRight: (isGrid || isHorizontal) && options.next,
        ArrowDown: (isGrid || isVertical) && options.down,
        ArrowLeft: (isGrid || isHorizontal) && options.previous,
        Home: function Home() {
          if (!isGrid || event.ctrlKey) {
            var _options$first;

            (_options$first = options.first) === null || _options$first === void 0 ? void 0 : _options$first.call(options);
          } else {
            var _options$previous;

            (_options$previous = options.previous) === null || _options$previous === void 0 ? void 0 : _options$previous.call(options, true);
          }
        },
        End: function End() {
          if (!isGrid || event.ctrlKey) {
            var _options$last;

            (_options$last = options.last) === null || _options$last === void 0 ? void 0 : _options$last.call(options);
          } else {
            var _options$next;

            (_options$next = options.next) === null || _options$next === void 0 ? void 0 : _options$next.call(options, true);
          }
        },
        PageUp: function PageUp() {
          if (isGrid) {
            var _options$up;

            (_options$up = options.up) === null || _options$up === void 0 ? void 0 : _options$up.call(options, true);
          } else {
            var _options$first2;

            (_options$first2 = options.first) === null || _options$first2 === void 0 ? void 0 : _options$first2.call(options);
          }
        },
        PageDown: function PageDown() {
          if (isGrid) {
            var _options$down;

            (_options$down = options.down) === null || _options$down === void 0 ? void 0 : _options$down.call(options, true);
          } else {
            var _options$last2;

            (_options$last2 = options.last) === null || _options$last2 === void 0 ? void 0 : _options$last2.call(options);
          }
        }
      };
      var action = keyMap[event.key];

      if (action) {
        event.preventDefault();
        action();
        return;
      }

      (_onKeyDownRef$current = onKeyDownRef.current) === null || _onKeyDownRef$current === void 0 ? void 0 : _onKeyDownRef$current.call(onKeyDownRef, event);
      if (event.defaultPrevented) return;

      if (event.key.length === 1 && event.key !== " ") {
        var widget = getWidget(event.currentTarget);

        if (widget && isTextField(widget)) {
          widget.focus();
          setTextFieldValue(widget, "");
        }
      } else if (event.key === "Delete" || event.key === "Backspace") {
        var _widget = getWidget(event.currentTarget);

        if (_widget && isTextField(_widget)) {
          event.preventDefault();
          setTextFieldValue(_widget, "");
        }
      }
    }, [options.orientation, item, options.up, options.next, options.down, options.previous, options.first, options.last]);
    var onClick = (0,react.useCallback)(function (event) {
      var _onClickRef$current;

      (_onClickRef$current = onClickRef.current) === null || _onClickRef$current === void 0 ? void 0 : _onClickRef$current.call(onClickRef, event);
      if (event.defaultPrevented) return;
      var element = event.currentTarget;
      var widget = getWidget(element);

      if (widget && !(0,hasFocusWithin/* hasFocusWithin */.o)(widget)) {
        // If there's a widget inside the composite item, we make sure it's
        // focused when pressing enter, space or clicking on the composite item.
        widget.focus();
      }
    }, []);
    return (0,_rollupPluginBabelHelpers_1f0bf8c2.a)({
      ref: (0,useForkRef/* useForkRef */.N)(ref, htmlRef),
      id: id,
      tabIndex: shouldTabIndex ? htmlTabIndex : -1,
      "aria-selected": options.unstable_virtual && isCurrentItem ? true : undefined,
      onMouseDown: onMouseDown,
      onFocus: onFocus,
      onBlurCapture: onBlurCapture,
      onKeyDown: onKeyDown,
      onClick: onClick
    }, htmlProps);
  }
});
var CompositeItem = (0,createComponent/* createComponent */.a)({
  as: "button",
  memo: true,
  useHook: useCompositeItem
});




/***/ }),

/***/ "../../node_modules/.pnpm/reakit@1.3.11_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit/es/Composite/CompositeState.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  A: () => (/* binding */ useCompositeState)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/reakit@1.3.11_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit/es/_rollupPluginBabelHelpers-1f0bf8c2.js
var _rollupPluginBabelHelpers_1f0bf8c2 = __webpack_require__("../../node_modules/.pnpm/reakit@1.3.11_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit/es/_rollupPluginBabelHelpers-1f0bf8c2.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/reakit-utils@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-utils/es/useIsomorphicEffect.js
var useIsomorphicEffect = __webpack_require__("../../node_modules/.pnpm/reakit-utils@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-utils/es/useIsomorphicEffect.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/reakit-utils@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-utils/es/useSealedState.js
var useSealedState = __webpack_require__("../../node_modules/.pnpm/reakit-utils@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-utils/es/useSealedState.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/reakit-utils@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-utils/es/getDocument.js
var getDocument = __webpack_require__("../../node_modules/.pnpm/reakit-utils@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-utils/es/getDocument.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/reakit@1.3.11_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit/es/reverse-30eaa122.js
var reverse_30eaa122 = __webpack_require__("../../node_modules/.pnpm/reakit@1.3.11_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit/es/reverse-30eaa122.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/reakit@1.3.11_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit/es/getCurrentId-5aa9849e.js
var getCurrentId_5aa9849e = __webpack_require__("../../node_modules/.pnpm/reakit@1.3.11_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit/es/getCurrentId-5aa9849e.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/reakit@1.3.11_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit/es/findEnabledItemById-8ddca752.js
var findEnabledItemById_8ddca752 = __webpack_require__("../../node_modules/.pnpm/reakit@1.3.11_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit/es/findEnabledItemById-8ddca752.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/reakit-utils@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-utils/es/applyState.js
function isUpdater(argument) {
  return typeof argument === "function";
}
/**
 * Receives a `setState` argument and calls it with `currentValue` if it's a
 * function. Otherwise return the argument as the new value.
 *
 * @example
 * import { applyState } from "reakit-utils";
 *
 * applyState((value) => value + 1, 1); // 2
 * applyState(2, 1); // 2
 */


function applyState(argument, currentValue) {
  if (isUpdater(argument)) {
    return argument(currentValue);
  }

  return argument;
}



// EXTERNAL MODULE: ../../node_modules/.pnpm/reakit@1.3.11_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit/es/Id/IdState.js
var IdState = __webpack_require__("../../node_modules/.pnpm/reakit@1.3.11_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit/es/Id/IdState.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/reakit@1.3.11_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit/es/Composite/CompositeState.js












function isElementPreceding(element1, element2) {
  return Boolean(element2.compareDocumentPosition(element1) & Node.DOCUMENT_POSITION_PRECEDING);
}

function findDOMIndex(items, item) {
  return items.findIndex(function (currentItem) {
    if (!currentItem.ref.current || !item.ref.current) {
      return false;
    }

    return isElementPreceding(item.ref.current, currentItem.ref.current);
  });
}

function getMaxLength(rows) {
  var maxLength = 0;

  for (var _iterator = (0,_rollupPluginBabelHelpers_1f0bf8c2.b)(rows), _step; !(_step = _iterator()).done;) {
    var length = _step.value.length;

    if (length > maxLength) {
      maxLength = length;
    }
  }

  return maxLength;
}

/**
 * Turns [row1, row1, row2, row2] into [row1, row2, row1, row2]
 */

function verticalizeItems(items) {
  var groups = (0,reverse_30eaa122.g)(items);
  var maxLength = getMaxLength(groups);
  var verticalized = [];

  for (var i = 0; i < maxLength; i += 1) {
    for (var _iterator = (0,_rollupPluginBabelHelpers_1f0bf8c2.b)(groups), _step; !(_step = _iterator()).done;) {
      var group = _step.value;

      if (group[i]) {
        verticalized.push((0,_rollupPluginBabelHelpers_1f0bf8c2.a)((0,_rollupPluginBabelHelpers_1f0bf8c2.a)({}, group[i]), {}, {
          // If there's no groupId, it means that it's not a grid composite,
          // but a single row instead. So, instead of verticalizing it, that
          // is, assigning a different groupId based on the column index, we
          // keep it undefined so they will be part of the same group.
          // It's useful when using up/down on one-dimensional composites.
          groupId: group[i].groupId ? "" + i : undefined
        }));
      }
    }
  }

  return verticalized;
}

function createEmptyItem(groupId) {
  return {
    id: "__EMPTY_ITEM__",
    disabled: true,
    ref: {
      current: null
    },
    groupId: groupId
  };
}
/**
 * Turns [[row1, row1], [row2]] into [[row1, row1], [row2, row2]]
 */


function fillGroups(groups, currentId, shift) {
  var maxLength = getMaxLength(groups);

  for (var _iterator = (0,_rollupPluginBabelHelpers_1f0bf8c2.b)(groups), _step; !(_step = _iterator()).done;) {
    var group = _step.value;

    for (var i = 0; i < maxLength; i += 1) {
      var item = group[i];

      if (!item || shift && item.disabled) {
        var isFrist = i === 0;
        var previousItem = isFrist && shift ? (0,getCurrentId_5aa9849e.f)(group) : group[i - 1];
        group[i] = previousItem && currentId !== (previousItem === null || previousItem === void 0 ? void 0 : previousItem.id) && shift ? previousItem : createEmptyItem(previousItem === null || previousItem === void 0 ? void 0 : previousItem.groupId);
      }
    }
  }

  return groups;
}

var nullItem = {
  id: null,
  ref: {
    current: null
  }
};
function placeItemsAfter(items, id, shouldInsertNullItem) {
  var index = items.findIndex(function (item) {
    return item.id === id;
  });
  return [].concat(items.slice(index + 1), shouldInsertNullItem ? [nullItem] : [], items.slice(0, index));
}

function getItemsInGroup(items, groupId) {
  return items.filter(function (item) {
    return item.groupId === groupId;
  });
}

var map = {
  horizontal: "vertical",
  vertical: "horizontal"
};
function getOppositeOrientation(orientation) {
  return orientation && map[orientation];
}

function addItemAtIndex(array, item, index) {
  if (!(index in array)) {
    return [].concat(array, [item]);
  }

  return [].concat(array.slice(0, index), [item], array.slice(index));
}

function sortBasedOnDOMPosition(items) {
  var pairs = items.map(function (item, index) {
    return [index, item];
  });
  var isOrderDifferent = false;
  pairs.sort(function (_ref, _ref2) {
    var indexA = _ref[0],
        a = _ref[1];
    var indexB = _ref2[0],
        b = _ref2[1];
    var elementA = a.ref.current;
    var elementB = b.ref.current;
    if (!elementA || !elementB) return 0; // a before b

    if (isElementPreceding(elementA, elementB)) {
      if (indexA > indexB) {
        isOrderDifferent = true;
      }

      return -1;
    } // a after b


    if (indexA < indexB) {
      isOrderDifferent = true;
    }

    return 1;
  });

  if (isOrderDifferent) {
    return pairs.map(function (_ref3) {
      var _ = _ref3[0],
          item = _ref3[1];
      return item;
    });
  }

  return items;
}

function setItemsBasedOnDOMPosition(items, setItems) {
  var sortedItems = sortBasedOnDOMPosition(items);

  if (items !== sortedItems) {
    setItems(sortedItems);
  }
}

function getCommonParent(items) {
  var _firstItem$ref$curren;

  var firstItem = items[0],
      nextItems = items.slice(1);
  var parentElement = firstItem === null || firstItem === void 0 ? void 0 : (_firstItem$ref$curren = firstItem.ref.current) === null || _firstItem$ref$curren === void 0 ? void 0 : _firstItem$ref$curren.parentElement;

  var _loop = function _loop() {
    var parent = parentElement;

    if (nextItems.every(function (item) {
      return parent.contains(item.ref.current);
    })) {
      return {
        v: parentElement
      };
    }

    parentElement = parentElement.parentElement;
  };

  while (parentElement) {
    var _ret = _loop();

    if (typeof _ret === "object") return _ret.v;
  }

  return (0,getDocument/* getDocument */.Y)(parentElement).body;
} // istanbul ignore next: JSDOM doesn't support IntersectionObverser
// See https://github.com/jsdom/jsdom/issues/2032


function useIntersectionObserver(items, setItems) {
  var previousItems = (0,react.useRef)([]);
  (0,react.useEffect)(function () {
    var callback = function callback() {
      var hasPreviousItems = !!previousItems.current.length; // We don't want to sort items if items have been just registered.

      if (hasPreviousItems) {
        setItemsBasedOnDOMPosition(items, setItems);
      }

      previousItems.current = items;
    };

    var root = getCommonParent(items);
    var observer = new IntersectionObserver(callback, {
      root: root
    });

    for (var _iterator = (0,_rollupPluginBabelHelpers_1f0bf8c2.b)(items), _step; !(_step = _iterator()).done;) {
      var item = _step.value;

      if (item.ref.current) {
        observer.observe(item.ref.current);
      }
    }

    return function () {
      observer.disconnect();
    };
  }, [items]);
}

function useTimeoutObserver(items, setItems) {
  (0,react.useEffect)(function () {
    var callback = function callback() {
      return setItemsBasedOnDOMPosition(items, setItems);
    };

    var timeout = setTimeout(callback, 250);
    return function () {
      return clearTimeout(timeout);
    };
  });
}

function useSortBasedOnDOMPosition(items, setItems) {
  if (typeof IntersectionObserver === "function") {
    useIntersectionObserver(items, setItems);
  } else {
    useTimeoutObserver(items, setItems);
  }
}

function reducer(state, action) {
  var virtual = state.unstable_virtual,
      rtl = state.rtl,
      orientation = state.orientation,
      items = state.items,
      groups = state.groups,
      currentId = state.currentId,
      loop = state.loop,
      wrap = state.wrap,
      pastIds = state.pastIds,
      shift = state.shift,
      moves = state.unstable_moves,
      includesBaseElement = state.unstable_includesBaseElement,
      initialVirtual = state.initialVirtual,
      initialRTL = state.initialRTL,
      initialOrientation = state.initialOrientation,
      initialCurrentId = state.initialCurrentId,
      initialLoop = state.initialLoop,
      initialWrap = state.initialWrap,
      initialShift = state.initialShift,
      hasSetCurrentId = state.hasSetCurrentId;

  switch (action.type) {
    case "registerGroup":
      {
        var _group = action.group; // If there are no groups yet, just add it as the first one

        if (groups.length === 0) {
          return (0,_rollupPluginBabelHelpers_1f0bf8c2.a)((0,_rollupPluginBabelHelpers_1f0bf8c2.a)({}, state), {}, {
            groups: [_group]
          });
        } // Finds the group index based on DOM position


        var index = findDOMIndex(groups, _group);
        return (0,_rollupPluginBabelHelpers_1f0bf8c2.a)((0,_rollupPluginBabelHelpers_1f0bf8c2.a)({}, state), {}, {
          groups: addItemAtIndex(groups, _group, index)
        });
      }

    case "unregisterGroup":
      {
        var _id = action.id;
        var nextGroups = groups.filter(function (group) {
          return group.id !== _id;
        }); // The group isn't registered, so do nothing

        if (nextGroups.length === groups.length) {
          return state;
        }

        return (0,_rollupPluginBabelHelpers_1f0bf8c2.a)((0,_rollupPluginBabelHelpers_1f0bf8c2.a)({}, state), {}, {
          groups: nextGroups
        });
      }

    case "registerItem":
      {
        var _item = action.item; // Finds the item group based on the DOM hierarchy

        var _group2 = groups.find(function (r) {
          var _r$ref$current;

          return (_r$ref$current = r.ref.current) === null || _r$ref$current === void 0 ? void 0 : _r$ref$current.contains(_item.ref.current);
        }); // Group will be null if it's a one-dimensional composite


        var nextItem = (0,_rollupPluginBabelHelpers_1f0bf8c2.a)({
          groupId: _group2 === null || _group2 === void 0 ? void 0 : _group2.id
        }, _item);

        var _index = findDOMIndex(items, nextItem);

        var nextState = (0,_rollupPluginBabelHelpers_1f0bf8c2.a)((0,_rollupPluginBabelHelpers_1f0bf8c2.a)({}, state), {}, {
          items: addItemAtIndex(items, nextItem, _index)
        });

        if (!hasSetCurrentId && !moves && initialCurrentId === undefined) {
          var _findFirstEnabledItem;

          // Sets currentId to the first enabled item. This runs whenever an item
          // is registered because the first enabled item may be registered
          // asynchronously.
          return (0,_rollupPluginBabelHelpers_1f0bf8c2.a)((0,_rollupPluginBabelHelpers_1f0bf8c2.a)({}, nextState), {}, {
            currentId: (_findFirstEnabledItem = (0,getCurrentId_5aa9849e.f)(nextState.items)) === null || _findFirstEnabledItem === void 0 ? void 0 : _findFirstEnabledItem.id
          });
        }

        return nextState;
      }

    case "unregisterItem":
      {
        var _id2 = action.id;
        var nextItems = items.filter(function (item) {
          return item.id !== _id2;
        }); // The item isn't registered, so do nothing

        if (nextItems.length === items.length) {
          return state;
        } // Filters out the item that is being removed from the pastIds list


        var nextPastIds = pastIds.filter(function (pastId) {
          return pastId !== _id2;
        });

        var _nextState = (0,_rollupPluginBabelHelpers_1f0bf8c2.a)((0,_rollupPluginBabelHelpers_1f0bf8c2.a)({}, state), {}, {
          pastIds: nextPastIds,
          items: nextItems
        }); // If the current item is the item that is being removed, focus pastId


        if (currentId && currentId === _id2) {
          var nextId = includesBaseElement ? null : (0,getCurrentId_5aa9849e.g)((0,_rollupPluginBabelHelpers_1f0bf8c2.a)((0,_rollupPluginBabelHelpers_1f0bf8c2.a)({}, _nextState), {}, {
            currentId: nextPastIds[0]
          }));
          return (0,_rollupPluginBabelHelpers_1f0bf8c2.a)((0,_rollupPluginBabelHelpers_1f0bf8c2.a)({}, _nextState), {}, {
            currentId: nextId
          });
        }

        return _nextState;
      }

    case "move":
      {
        var _id3 = action.id; // move() does nothing

        if (_id3 === undefined) {
          return state;
        } // Removes the current item and the item that is receiving focus from the
        // pastIds list


        var filteredPastIds = pastIds.filter(function (pastId) {
          return pastId !== currentId && pastId !== _id3;
        }); // If there's a currentId, add it to the pastIds list so it can be focused
        // if the new item gets removed or disabled

        var _nextPastIds = currentId ? [currentId].concat(filteredPastIds) : filteredPastIds;

        var _nextState2 = (0,_rollupPluginBabelHelpers_1f0bf8c2.a)((0,_rollupPluginBabelHelpers_1f0bf8c2.a)({}, state), {}, {
          pastIds: _nextPastIds
        }); // move(null) will focus the composite element itself, not an item


        if (_id3 === null) {
          return (0,_rollupPluginBabelHelpers_1f0bf8c2.a)((0,_rollupPluginBabelHelpers_1f0bf8c2.a)({}, _nextState2), {}, {
            unstable_moves: moves + 1,
            currentId: (0,getCurrentId_5aa9849e.g)(_nextState2, _id3)
          });
        }

        var _item2 = (0,findEnabledItemById_8ddca752.f)(items, _id3);

        return (0,_rollupPluginBabelHelpers_1f0bf8c2.a)((0,_rollupPluginBabelHelpers_1f0bf8c2.a)({}, _nextState2), {}, {
          unstable_moves: _item2 ? moves + 1 : moves,
          currentId: (0,getCurrentId_5aa9849e.g)(_nextState2, _item2 === null || _item2 === void 0 ? void 0 : _item2.id)
        });
      }

    case "next":
      {
        // If there's no item focused, we just move the first one
        if (currentId == null) {
          return reducer(state, (0,_rollupPluginBabelHelpers_1f0bf8c2.a)((0,_rollupPluginBabelHelpers_1f0bf8c2.a)({}, action), {}, {
            type: "first"
          }));
        } // RTL doesn't make sense on vertical navigation


        var isHorizontal = orientation !== "vertical";
        var isRTL = rtl && isHorizontal;
        var allItems = isRTL ? (0,reverse_30eaa122.r)(items) : items;
        var currentItem = allItems.find(function (item) {
          return item.id === currentId;
        }); // If there's no item focused, we just move the first one

        if (!currentItem) {
          return reducer(state, (0,_rollupPluginBabelHelpers_1f0bf8c2.a)((0,_rollupPluginBabelHelpers_1f0bf8c2.a)({}, action), {}, {
            type: "first"
          }));
        }

        var isGrid = !!currentItem.groupId;
        var currentIndex = allItems.indexOf(currentItem);

        var _nextItems = allItems.slice(currentIndex + 1);

        var nextItemsInGroup = getItemsInGroup(_nextItems, currentItem.groupId); // Home, End

        if (action.allTheWay) {
          // We reverse so we can get the last enabled item in the group. If it's
          // RTL, nextItems and nextItemsInGroup are already reversed and don't
          // have the items before the current one anymore. So we have to get
          // items in group again with allItems.
          var _nextItem2 = (0,getCurrentId_5aa9849e.f)(isRTL ? getItemsInGroup(allItems, currentItem.groupId) : (0,reverse_30eaa122.r)(nextItemsInGroup));

          return reducer(state, (0,_rollupPluginBabelHelpers_1f0bf8c2.a)((0,_rollupPluginBabelHelpers_1f0bf8c2.a)({}, action), {}, {
            type: "move",
            id: _nextItem2 === null || _nextItem2 === void 0 ? void 0 : _nextItem2.id
          }));
        }

        var oppositeOrientation = getOppositeOrientation( // If it's a grid and orientation is not set, it's a next/previous
        // call, which is inherently horizontal. up/down will call next with
        // orientation set to vertical by default (see below on up/down cases).
        isGrid ? orientation || "horizontal" : orientation);
        var canLoop = loop && loop !== oppositeOrientation;
        var canWrap = isGrid && wrap && wrap !== oppositeOrientation;
        var hasNullItem = // `previous` and `up` will set action.hasNullItem, but when calling
        // next directly, hasNullItem will only be true if it's not a grid and
        // loop is set to true, which means that pressing right or down keys on
        // grids will never focus the composite element. On one-dimensional
        // composites that don't loop, pressing right or down keys also doesn't
        // focus the composite element.
        action.hasNullItem || !isGrid && canLoop && includesBaseElement;

        if (canLoop) {
          var loopItems = canWrap && !hasNullItem ? allItems : getItemsInGroup(allItems, currentItem.groupId); // Turns [0, 1, current, 3, 4] into [3, 4, 0, 1]

          var sortedItems = placeItemsAfter(loopItems, currentId, hasNullItem);

          var _nextItem3 = (0,getCurrentId_5aa9849e.f)(sortedItems, currentId);

          return reducer(state, (0,_rollupPluginBabelHelpers_1f0bf8c2.a)((0,_rollupPluginBabelHelpers_1f0bf8c2.a)({}, action), {}, {
            type: "move",
            id: _nextItem3 === null || _nextItem3 === void 0 ? void 0 : _nextItem3.id
          }));
        }

        if (canWrap) {
          var _nextItem4 = (0,getCurrentId_5aa9849e.f)( // We can use nextItems, which contains all the next items, including
          // items from other groups, to wrap between groups. However, if there
          // is a null item (the composite element), we'll only use the next
          // items in the group. So moving next from the last item will focus
          // the composite element (null). On grid composites, horizontal
          // navigation never focuses the composite element, only vertical.
          hasNullItem ? nextItemsInGroup : _nextItems, currentId);

          var _nextId = hasNullItem ? (_nextItem4 === null || _nextItem4 === void 0 ? void 0 : _nextItem4.id) || null : _nextItem4 === null || _nextItem4 === void 0 ? void 0 : _nextItem4.id;

          return reducer(state, (0,_rollupPluginBabelHelpers_1f0bf8c2.a)((0,_rollupPluginBabelHelpers_1f0bf8c2.a)({}, action), {}, {
            type: "move",
            id: _nextId
          }));
        }

        var _nextItem = (0,getCurrentId_5aa9849e.f)(nextItemsInGroup, currentId);

        if (!_nextItem && hasNullItem) {
          return reducer(state, (0,_rollupPluginBabelHelpers_1f0bf8c2.a)((0,_rollupPluginBabelHelpers_1f0bf8c2.a)({}, action), {}, {
            type: "move",
            id: null
          }));
        }

        return reducer(state, (0,_rollupPluginBabelHelpers_1f0bf8c2.a)((0,_rollupPluginBabelHelpers_1f0bf8c2.a)({}, action), {}, {
          type: "move",
          id: _nextItem === null || _nextItem === void 0 ? void 0 : _nextItem.id
        }));
      }

    case "previous":
      {
        // If currentId is initially set to null, the composite element will be
        // focusable while navigating with arrow keys. But, if it's a grid, we
        // don't want to focus the composite element with horizontal navigation.
        var _isGrid = !!groups.length;

        var _hasNullItem = !_isGrid && includesBaseElement;

        var _nextState3 = reducer((0,_rollupPluginBabelHelpers_1f0bf8c2.a)((0,_rollupPluginBabelHelpers_1f0bf8c2.a)({}, state), {}, {
          items: (0,reverse_30eaa122.r)(items)
        }), (0,_rollupPluginBabelHelpers_1f0bf8c2.a)((0,_rollupPluginBabelHelpers_1f0bf8c2.a)({}, action), {}, {
          type: "next",
          hasNullItem: _hasNullItem
        }));

        return (0,_rollupPluginBabelHelpers_1f0bf8c2.a)((0,_rollupPluginBabelHelpers_1f0bf8c2.a)({}, _nextState3), {}, {
          items: items
        });
      }

    case "down":
      {
        var shouldShift = shift && !action.allTheWay; // First, we make sure groups have the same number of items by filling it
        // with disabled fake items. Then, we reorganize the items list so
        // [1-1, 1-2, 2-1, 2-2] becomes [1-1, 2-1, 1-2, 2-2].

        var verticalItems = verticalizeItems((0,reverse_30eaa122.f)(fillGroups((0,reverse_30eaa122.g)(items), currentId, shouldShift)));

        var _canLoop = loop && loop !== "horizontal"; // Pressing down arrow key will only focus the composite element if loop
        // is true or vertical.


        var _hasNullItem2 = _canLoop && includesBaseElement;

        var _nextState4 = reducer((0,_rollupPluginBabelHelpers_1f0bf8c2.a)((0,_rollupPluginBabelHelpers_1f0bf8c2.a)({}, state), {}, {
          orientation: "vertical",
          items: verticalItems
        }), (0,_rollupPluginBabelHelpers_1f0bf8c2.a)((0,_rollupPluginBabelHelpers_1f0bf8c2.a)({}, action), {}, {
          type: "next",
          hasNullItem: _hasNullItem2
        }));

        return (0,_rollupPluginBabelHelpers_1f0bf8c2.a)((0,_rollupPluginBabelHelpers_1f0bf8c2.a)({}, _nextState4), {}, {
          orientation: orientation,
          items: items
        });
      }

    case "up":
      {
        var _shouldShift = shift && !action.allTheWay;

        var _verticalItems = verticalizeItems((0,reverse_30eaa122.r)((0,reverse_30eaa122.f)(fillGroups((0,reverse_30eaa122.g)(items), currentId, _shouldShift)))); // If currentId is initially set to null, we'll always focus the
        // composite element when the up arrow key is pressed in the first row.


        var _hasNullItem3 = includesBaseElement;

        var _nextState5 = reducer((0,_rollupPluginBabelHelpers_1f0bf8c2.a)((0,_rollupPluginBabelHelpers_1f0bf8c2.a)({}, state), {}, {
          orientation: "vertical",
          items: _verticalItems
        }), (0,_rollupPluginBabelHelpers_1f0bf8c2.a)((0,_rollupPluginBabelHelpers_1f0bf8c2.a)({}, action), {}, {
          type: "next",
          hasNullItem: _hasNullItem3
        }));

        return (0,_rollupPluginBabelHelpers_1f0bf8c2.a)((0,_rollupPluginBabelHelpers_1f0bf8c2.a)({}, _nextState5), {}, {
          orientation: orientation,
          items: items
        });
      }

    case "first":
      {
        var firstItem = (0,getCurrentId_5aa9849e.f)(items);
        return reducer(state, (0,_rollupPluginBabelHelpers_1f0bf8c2.a)((0,_rollupPluginBabelHelpers_1f0bf8c2.a)({}, action), {}, {
          type: "move",
          id: firstItem === null || firstItem === void 0 ? void 0 : firstItem.id
        }));
      }

    case "last":
      {
        var _nextState6 = reducer((0,_rollupPluginBabelHelpers_1f0bf8c2.a)((0,_rollupPluginBabelHelpers_1f0bf8c2.a)({}, state), {}, {
          items: (0,reverse_30eaa122.r)(items)
        }), (0,_rollupPluginBabelHelpers_1f0bf8c2.a)((0,_rollupPluginBabelHelpers_1f0bf8c2.a)({}, action), {}, {
          type: "first"
        }));

        return (0,_rollupPluginBabelHelpers_1f0bf8c2.a)((0,_rollupPluginBabelHelpers_1f0bf8c2.a)({}, _nextState6), {}, {
          items: items
        });
      }

    case "sort":
      {
        return (0,_rollupPluginBabelHelpers_1f0bf8c2.a)((0,_rollupPluginBabelHelpers_1f0bf8c2.a)({}, state), {}, {
          items: sortBasedOnDOMPosition(items),
          groups: sortBasedOnDOMPosition(groups)
        });
      }

    case "setVirtual":
      return (0,_rollupPluginBabelHelpers_1f0bf8c2.a)((0,_rollupPluginBabelHelpers_1f0bf8c2.a)({}, state), {}, {
        unstable_virtual: applyState(action.virtual, virtual)
      });

    case "setRTL":
      return (0,_rollupPluginBabelHelpers_1f0bf8c2.a)((0,_rollupPluginBabelHelpers_1f0bf8c2.a)({}, state), {}, {
        rtl: applyState(action.rtl, rtl)
      });

    case "setOrientation":
      return (0,_rollupPluginBabelHelpers_1f0bf8c2.a)((0,_rollupPluginBabelHelpers_1f0bf8c2.a)({}, state), {}, {
        orientation: applyState(action.orientation, orientation)
      });

    case "setCurrentId":
      {
        var nextCurrentId = (0,getCurrentId_5aa9849e.g)((0,_rollupPluginBabelHelpers_1f0bf8c2.a)((0,_rollupPluginBabelHelpers_1f0bf8c2.a)({}, state), {}, {
          currentId: applyState(action.currentId, currentId)
        }));
        return (0,_rollupPluginBabelHelpers_1f0bf8c2.a)((0,_rollupPluginBabelHelpers_1f0bf8c2.a)({}, state), {}, {
          currentId: nextCurrentId,
          hasSetCurrentId: true
        });
      }

    case "setLoop":
      return (0,_rollupPluginBabelHelpers_1f0bf8c2.a)((0,_rollupPluginBabelHelpers_1f0bf8c2.a)({}, state), {}, {
        loop: applyState(action.loop, loop)
      });

    case "setWrap":
      return (0,_rollupPluginBabelHelpers_1f0bf8c2.a)((0,_rollupPluginBabelHelpers_1f0bf8c2.a)({}, state), {}, {
        wrap: applyState(action.wrap, wrap)
      });

    case "setShift":
      return (0,_rollupPluginBabelHelpers_1f0bf8c2.a)((0,_rollupPluginBabelHelpers_1f0bf8c2.a)({}, state), {}, {
        shift: applyState(action.shift, shift)
      });

    case "setIncludesBaseElement":
      {
        return (0,_rollupPluginBabelHelpers_1f0bf8c2.a)((0,_rollupPluginBabelHelpers_1f0bf8c2.a)({}, state), {}, {
          unstable_includesBaseElement: applyState(action.includesBaseElement, includesBaseElement)
        });
      }

    case "reset":
      return (0,_rollupPluginBabelHelpers_1f0bf8c2.a)((0,_rollupPluginBabelHelpers_1f0bf8c2.a)({}, state), {}, {
        unstable_virtual: initialVirtual,
        rtl: initialRTL,
        orientation: initialOrientation,
        currentId: (0,getCurrentId_5aa9849e.g)((0,_rollupPluginBabelHelpers_1f0bf8c2.a)((0,_rollupPluginBabelHelpers_1f0bf8c2.a)({}, state), {}, {
          currentId: initialCurrentId
        })),
        loop: initialLoop,
        wrap: initialWrap,
        shift: initialShift,
        unstable_moves: 0,
        pastIds: []
      });

    case "setItems":
      {
        return (0,_rollupPluginBabelHelpers_1f0bf8c2.a)((0,_rollupPluginBabelHelpers_1f0bf8c2.a)({}, state), {}, {
          items: action.items
        });
      }

    default:
      throw new Error();
  }
}

function useAction(fn) {
  return (0,react.useCallback)(fn, []);
}

function useIsUnmountedRef() {
  var isUnmountedRef = (0,react.useRef)(false);
  (0,useIsomorphicEffect/* useIsomorphicEffect */.o)(function () {
    return function () {
      isUnmountedRef.current = true;
    };
  }, []);
  return isUnmountedRef;
}

function useCompositeState(initialState) {
  if (initialState === void 0) {
    initialState = {};
  }

  var _useSealedState = (0,useSealedState/* useSealedState */.N)(initialState),
      _useSealedState$unsta = _useSealedState.unstable_virtual,
      virtual = _useSealedState$unsta === void 0 ? false : _useSealedState$unsta,
      _useSealedState$rtl = _useSealedState.rtl,
      rtl = _useSealedState$rtl === void 0 ? false : _useSealedState$rtl,
      orientation = _useSealedState.orientation,
      currentId = _useSealedState.currentId,
      _useSealedState$loop = _useSealedState.loop,
      loop = _useSealedState$loop === void 0 ? false : _useSealedState$loop,
      _useSealedState$wrap = _useSealedState.wrap,
      wrap = _useSealedState$wrap === void 0 ? false : _useSealedState$wrap,
      _useSealedState$shift = _useSealedState.shift,
      shift = _useSealedState$shift === void 0 ? false : _useSealedState$shift,
      unstable_includesBaseElement = _useSealedState.unstable_includesBaseElement,
      sealed = (0,_rollupPluginBabelHelpers_1f0bf8c2._)(_useSealedState, ["unstable_virtual", "rtl", "orientation", "currentId", "loop", "wrap", "shift", "unstable_includesBaseElement"]);

  var idState = (0,IdState/* unstable_useIdState */.t)(sealed);

  var _React$useReducer = (0,react.useReducer)(reducer, {
    unstable_virtual: virtual,
    rtl: rtl,
    orientation: orientation,
    items: [],
    groups: [],
    currentId: currentId,
    loop: loop,
    wrap: wrap,
    shift: shift,
    unstable_moves: 0,
    pastIds: [],
    unstable_includesBaseElement: unstable_includesBaseElement != null ? unstable_includesBaseElement : currentId === null,
    initialVirtual: virtual,
    initialRTL: rtl,
    initialOrientation: orientation,
    initialCurrentId: currentId,
    initialLoop: loop,
    initialWrap: wrap,
    initialShift: shift
  }),
      _React$useReducer$ = _React$useReducer[0],
      pastIds = _React$useReducer$.pastIds,
      initialVirtual = _React$useReducer$.initialVirtual,
      initialRTL = _React$useReducer$.initialRTL,
      initialOrientation = _React$useReducer$.initialOrientation,
      initialCurrentId = _React$useReducer$.initialCurrentId,
      initialLoop = _React$useReducer$.initialLoop,
      initialWrap = _React$useReducer$.initialWrap,
      initialShift = _React$useReducer$.initialShift,
      hasSetCurrentId = _React$useReducer$.hasSetCurrentId,
      state = (0,_rollupPluginBabelHelpers_1f0bf8c2._)(_React$useReducer$, ["pastIds", "initialVirtual", "initialRTL", "initialOrientation", "initialCurrentId", "initialLoop", "initialWrap", "initialShift", "hasSetCurrentId"]),
      dispatch = _React$useReducer[1];

  var _React$useState = (0,react.useState)(false),
      hasActiveWidget = _React$useState[0],
      setHasActiveWidget = _React$useState[1]; // register/unregister may be called when this component is unmounted. We
  // store the unmounted state here so we don't update the state if it's true.
  // This only happens in a very specific situation.
  // See https://github.com/reakit/reakit/issues/650


  var isUnmountedRef = useIsUnmountedRef();
  var setItems = (0,react.useCallback)(function (items) {
    return dispatch({
      type: "setItems",
      items: items
    });
  }, []);
  useSortBasedOnDOMPosition(state.items, setItems);
  return (0,_rollupPluginBabelHelpers_1f0bf8c2.a)((0,_rollupPluginBabelHelpers_1f0bf8c2.a)((0,_rollupPluginBabelHelpers_1f0bf8c2.a)({}, idState), state), {}, {
    unstable_hasActiveWidget: hasActiveWidget,
    unstable_setHasActiveWidget: setHasActiveWidget,
    registerItem: useAction(function (item) {
      if (isUnmountedRef.current) return;
      dispatch({
        type: "registerItem",
        item: item
      });
    }),
    unregisterItem: useAction(function (id) {
      if (isUnmountedRef.current) return;
      dispatch({
        type: "unregisterItem",
        id: id
      });
    }),
    registerGroup: useAction(function (group) {
      if (isUnmountedRef.current) return;
      dispatch({
        type: "registerGroup",
        group: group
      });
    }),
    unregisterGroup: useAction(function (id) {
      if (isUnmountedRef.current) return;
      dispatch({
        type: "unregisterGroup",
        id: id
      });
    }),
    move: useAction(function (id) {
      return dispatch({
        type: "move",
        id: id
      });
    }),
    next: useAction(function (allTheWay) {
      return dispatch({
        type: "next",
        allTheWay: allTheWay
      });
    }),
    previous: useAction(function (allTheWay) {
      return dispatch({
        type: "previous",
        allTheWay: allTheWay
      });
    }),
    up: useAction(function (allTheWay) {
      return dispatch({
        type: "up",
        allTheWay: allTheWay
      });
    }),
    down: useAction(function (allTheWay) {
      return dispatch({
        type: "down",
        allTheWay: allTheWay
      });
    }),
    first: useAction(function () {
      return dispatch({
        type: "first"
      });
    }),
    last: useAction(function () {
      return dispatch({
        type: "last"
      });
    }),
    sort: useAction(function () {
      return dispatch({
        type: "sort"
      });
    }),
    unstable_setVirtual: useAction(function (value) {
      return dispatch({
        type: "setVirtual",
        virtual: value
      });
    }),
    setRTL: useAction(function (value) {
      return dispatch({
        type: "setRTL",
        rtl: value
      });
    }),
    setOrientation: useAction(function (value) {
      return dispatch({
        type: "setOrientation",
        orientation: value
      });
    }),
    setCurrentId: useAction(function (value) {
      return dispatch({
        type: "setCurrentId",
        currentId: value
      });
    }),
    setLoop: useAction(function (value) {
      return dispatch({
        type: "setLoop",
        loop: value
      });
    }),
    setWrap: useAction(function (value) {
      return dispatch({
        type: "setWrap",
        wrap: value
      });
    }),
    setShift: useAction(function (value) {
      return dispatch({
        type: "setShift",
        shift: value
      });
    }),
    unstable_setIncludesBaseElement: useAction(function (value) {
      return dispatch({
        type: "setIncludesBaseElement",
        includesBaseElement: value
      });
    }),
    reset: useAction(function () {
      return dispatch({
        type: "reset"
      });
    })
  });
}




/***/ }),

/***/ "../../node_modules/.pnpm/reakit@1.3.11_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit/es/Id/Id.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   W: () => (/* binding */ unstable_useId)
/* harmony export */ });
/* unused harmony export unstable_Id */
/* harmony import */ var _rollupPluginBabelHelpers_1f0bf8c2_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/reakit@1.3.11_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit/es/_rollupPluginBabelHelpers-1f0bf8c2.js");
/* harmony import */ var reakit_system_createComponent__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__("../../node_modules/.pnpm/reakit-system@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-system/es/createComponent.js");
/* harmony import */ var reakit_system_createHook__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/reakit-system@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-system/es/createHook.js");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var _IdProvider_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/reakit@1.3.11_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit/es/Id/IdProvider.js");






// Automatically generated
var ID_STATE_KEYS = ["baseId", "unstable_idCountRef", "setBaseId"];
var ID_KEYS = [].concat(ID_STATE_KEYS, ["id"]);

var unstable_useId = (0,reakit_system_createHook__WEBPACK_IMPORTED_MODULE_1__/* .createHook */ .a)({
  keys: ID_KEYS,
  useOptions: function useOptions(options, htmlProps) {
    var generateId = (0,react__WEBPACK_IMPORTED_MODULE_0__.useContext)(_IdProvider_js__WEBPACK_IMPORTED_MODULE_2__/* .unstable_IdContext */ .M);

    var _React$useState = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(function () {
      // This comes from useIdState
      if (options.unstable_idCountRef) {
        options.unstable_idCountRef.current += 1;
        return "-" + options.unstable_idCountRef.current;
      } // If there's no useIdState, we check if `baseId` was passed (as a prop,
      // not from useIdState).


      if (options.baseId) {
        return "-" + generateId("");
      }

      return "";
    }),
        suffix = _React$useState[0]; // `baseId` will be the prop passed directly as a prop or via useIdState.
    // If there's neither, then it'll fallback to Context's generateId.
    // This generateId can result in a sequential ID (if there's a Provider)
    // or a random string (without Provider).


    var baseId = (0,react__WEBPACK_IMPORTED_MODULE_0__.useMemo)(function () {
      return options.baseId || generateId();
    }, [options.baseId, generateId]);
    var id = htmlProps.id || options.id || "" + baseId + suffix;
    return (0,_rollupPluginBabelHelpers_1f0bf8c2_js__WEBPACK_IMPORTED_MODULE_3__.a)((0,_rollupPluginBabelHelpers_1f0bf8c2_js__WEBPACK_IMPORTED_MODULE_3__.a)({}, options), {}, {
      id: id
    });
  },
  useProps: function useProps(options, htmlProps) {
    return (0,_rollupPluginBabelHelpers_1f0bf8c2_js__WEBPACK_IMPORTED_MODULE_3__.a)({
      id: options.id
    }, htmlProps);
  }
});
var unstable_Id = (0,reakit_system_createComponent__WEBPACK_IMPORTED_MODULE_4__/* .createComponent */ .a)({
  as: "div",
  useHook: unstable_useId
});




/***/ }),

/***/ "../../node_modules/.pnpm/reakit@1.3.11_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit/es/Id/IdProvider.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   M: () => (/* binding */ unstable_IdContext)
/* harmony export */ });
/* unused harmony export unstable_IdProvider */
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");


var defaultPrefix = "id";
function generateRandomString(prefix) {
  if (prefix === void 0) {
    prefix = defaultPrefix;
  }

  return "" + (prefix ? prefix + "-" : "") + Math.random().toString(32).substr(2, 6);
}

var unstable_IdContext = /*#__PURE__*/(0,react__WEBPACK_IMPORTED_MODULE_0__.createContext)(generateRandomString);
function unstable_IdProvider(_ref) {
  var children = _ref.children,
      _ref$prefix = _ref.prefix,
      prefix = _ref$prefix === void 0 ? defaultPrefix : _ref$prefix;
  var count = useRef(0);
  var generateId = useCallback(function (localPrefix) {
    if (localPrefix === void 0) {
      localPrefix = prefix;
    }

    return "" + (localPrefix ? localPrefix + "-" : "") + ++count.current;
  }, [prefix]);
  return /*#__PURE__*/createElement(unstable_IdContext.Provider, {
    value: generateId
  }, children);
}




/***/ }),

/***/ "../../node_modules/.pnpm/reakit@1.3.11_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit/es/Id/IdState.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   t: () => (/* binding */ unstable_useIdState)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var reakit_utils_useSealedState__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/reakit-utils@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-utils/es/useSealedState.js");
/* harmony import */ var _IdProvider_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/reakit@1.3.11_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit/es/Id/IdProvider.js");




function unstable_useIdState(initialState) {
  if (initialState === void 0) {
    initialState = {};
  }

  var _useSealedState = (0,reakit_utils_useSealedState__WEBPACK_IMPORTED_MODULE_1__/* .useSealedState */ .N)(initialState),
      initialBaseId = _useSealedState.baseId;

  var generateId = (0,react__WEBPACK_IMPORTED_MODULE_0__.useContext)(_IdProvider_js__WEBPACK_IMPORTED_MODULE_2__/* .unstable_IdContext */ .M);
  var idCountRef = (0,react__WEBPACK_IMPORTED_MODULE_0__.useRef)(0);

  var _React$useState = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(function () {
    return initialBaseId || generateId();
  }),
      baseId = _React$useState[0],
      setBaseId = _React$useState[1];

  return {
    baseId: baseId,
    setBaseId: setBaseId,
    unstable_idCountRef: idCountRef
  };
}




/***/ }),

/***/ "../../node_modules/.pnpm/reakit@1.3.11_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit/es/Role/Role.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   I: () => (/* binding */ useRole)
/* harmony export */ });
/* unused harmony export Role */
/* harmony import */ var _rollupPluginBabelHelpers_1f0bf8c2_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/reakit@1.3.11_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit/es/_rollupPluginBabelHelpers-1f0bf8c2.js");
/* harmony import */ var reakit_system_createComponent__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/reakit-system@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-system/es/createComponent.js");
/* harmony import */ var reakit_system_createHook__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/reakit-system@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-system/es/createHook.js");
/* harmony import */ var reakit_utils_shallowEqual__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/reakit-utils@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-utils/es/shallowEqual.js");





// Automatically generated
var ROLE_KEYS = ["unstable_system"];

var useRole = (0,reakit_system_createHook__WEBPACK_IMPORTED_MODULE_0__/* .createHook */ .a)({
  name: "Role",
  keys: ROLE_KEYS,
  propsAreEqual: function propsAreEqual(prev, next) {
    var prevSystem = prev.unstable_system,
        prevProps = (0,_rollupPluginBabelHelpers_1f0bf8c2_js__WEBPACK_IMPORTED_MODULE_1__._)(prev, ["unstable_system"]);

    var nextSystem = next.unstable_system,
        nextProps = (0,_rollupPluginBabelHelpers_1f0bf8c2_js__WEBPACK_IMPORTED_MODULE_1__._)(next, ["unstable_system"]);

    if (prevSystem !== nextSystem && !(0,reakit_utils_shallowEqual__WEBPACK_IMPORTED_MODULE_2__/* .shallowEqual */ .b)(prevSystem, nextSystem)) {
      return false;
    }

    return (0,reakit_utils_shallowEqual__WEBPACK_IMPORTED_MODULE_2__/* .shallowEqual */ .b)(prevProps, nextProps);
  }
});
var Role = (0,reakit_system_createComponent__WEBPACK_IMPORTED_MODULE_3__/* .createComponent */ .a)({
  as: "div",
  useHook: useRole
});




/***/ }),

/***/ "../../node_modules/.pnpm/reakit@1.3.11_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit/es/Tabbable/Tabbable.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  b: () => (/* binding */ useTabbable)
});

// UNUSED EXPORTS: Tabbable

// EXTERNAL MODULE: ../../node_modules/.pnpm/reakit@1.3.11_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit/es/_rollupPluginBabelHelpers-1f0bf8c2.js
var _rollupPluginBabelHelpers_1f0bf8c2 = __webpack_require__("../../node_modules/.pnpm/reakit@1.3.11_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit/es/_rollupPluginBabelHelpers-1f0bf8c2.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/reakit-system@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-system/es/createComponent.js + 4 modules
var createComponent = __webpack_require__("../../node_modules/.pnpm/reakit-system@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-system/es/createComponent.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/reakit-system@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-system/es/createHook.js + 4 modules
var createHook = __webpack_require__("../../node_modules/.pnpm/reakit-system@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-system/es/createHook.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/reakit-utils@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-utils/es/useForkRef.js
var useForkRef = __webpack_require__("../../node_modules/.pnpm/reakit-utils@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-utils/es/useForkRef.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/reakit-utils@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-utils/es/isButton.js
var isButton = __webpack_require__("../../node_modules/.pnpm/reakit-utils@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-utils/es/isButton.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/reakit-warning@0.6.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-warning/es/index.js + 1 modules
var es = __webpack_require__("../../node_modules/.pnpm/reakit-warning@0.6.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-warning/es/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/reakit-utils@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-utils/es/useLiveRef.js
var useLiveRef = __webpack_require__("../../node_modules/.pnpm/reakit-utils@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-utils/es/useLiveRef.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/reakit-utils@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-utils/es/useIsomorphicEffect.js
var useIsomorphicEffect = __webpack_require__("../../node_modules/.pnpm/reakit-utils@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-utils/es/useIsomorphicEffect.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/reakit-utils@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-utils/es/hasFocusWithin.js
var hasFocusWithin = __webpack_require__("../../node_modules/.pnpm/reakit-utils@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-utils/es/hasFocusWithin.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/reakit-utils@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-utils/es/isPortalEvent.js
var isPortalEvent = __webpack_require__("../../node_modules/.pnpm/reakit-utils@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-utils/es/isPortalEvent.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/reakit-utils@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-utils/es/dom.js
var dom = __webpack_require__("../../node_modules/.pnpm/reakit-utils@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-utils/es/dom.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/reakit-utils@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-utils/es/matches.js
/**
 * Ponyfill for `Element.prototype.matches`
 *
 * @see https://developer.mozilla.org/en-US/docs/Web/API/Element/matches
 */
function matches(element, selectors) {
  if ("matches" in element) {
    return element.matches(selectors);
  }

  if ("msMatchesSelector" in element) {
    return element.msMatchesSelector(selectors);
  }

  return element.webkitMatchesSelector(selectors);
}



;// CONCATENATED MODULE: ../../node_modules/.pnpm/reakit-utils@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-utils/es/tabbable.js





/** @module tabbable */
var selector = "input:not([type='hidden']):not([disabled]), select:not([disabled]), " + "textarea:not([disabled]), a[href], button:not([disabled]), [tabindex], " + "iframe, object, embed, area[href], audio[controls], video[controls], " + "[contenteditable]:not([contenteditable='false'])";

function isVisible(element) {
  var htmlElement = element;
  return htmlElement.offsetWidth > 0 || htmlElement.offsetHeight > 0 || element.getClientRects().length > 0;
}

function hasNegativeTabIndex(element) {
  var tabIndex = parseInt(element.getAttribute("tabindex") || "0", 10);
  return tabIndex < 0;
}
/**
 * Checks whether `element` is focusable or not.
 *
 * @memberof tabbable
 *
 * @example
 * import { isFocusable } from "reakit-utils";
 *
 * isFocusable(document.querySelector("input")); // true
 * isFocusable(document.querySelector("input[tabindex='-1']")); // true
 * isFocusable(document.querySelector("input[hidden]")); // false
 * isFocusable(document.querySelector("input:disabled")); // false
 */


function isFocusable(element) {
  return matches(element, selector) && isVisible(element);
}
/**
 * Checks whether `element` is tabbable or not.
 *
 * @memberof tabbable
 *
 * @example
 * import { isTabbable } from "reakit-utils";
 *
 * isTabbable(document.querySelector("input")); // true
 * isTabbable(document.querySelector("input[tabindex='-1']")); // false
 * isTabbable(document.querySelector("input[hidden]")); // false
 * isTabbable(document.querySelector("input:disabled")); // false
 */

function isTabbable(element) {
  return isFocusable(element) && !hasNegativeTabIndex(element);
}
/**
 * Returns all the focusable elements in `container`.
 *
 * @memberof tabbable
 *
 * @param {Element} container
 *
 * @returns {Element[]}
 */

function getAllFocusableIn(container) {
  var allFocusable = Array.from(container.querySelectorAll(selector));
  allFocusable.unshift(container);
  return allFocusable.filter(isFocusable);
}
/**
 * Returns the first focusable element in `container`.
 *
 * @memberof tabbable
 *
 * @param {Element} container
 *
 * @returns {Element|null}
 */

function getFirstFocusableIn(container) {
  var _getAllFocusableIn = getAllFocusableIn(container),
      first = _getAllFocusableIn[0];

  return first || null;
}
/**
 * Returns all the tabbable elements in `container`, including the container
 * itself.
 *
 * @memberof tabbable
 *
 * @param {Element} container
 * @param fallbackToFocusable If `true`, it'll return focusable elements if there are no tabbable ones.
 *
 * @returns {Element[]}
 */

function getAllTabbableIn(container, fallbackToFocusable) {
  var allFocusable = Array.from(container.querySelectorAll(selector));
  var allTabbable = allFocusable.filter(isTabbable);

  if (isTabbable(container)) {
    allTabbable.unshift(container);
  }

  if (!allTabbable.length && fallbackToFocusable) {
    return allFocusable;
  }

  return allTabbable;
}
/**
 * Returns the first tabbable element in `container`, including the container
 * itself if it's tabbable.
 *
 * @memberof tabbable
 *
 * @param {Element} container
 * @param fallbackToFocusable If `true`, it'll return the first focusable element if there are no tabbable ones.
 *
 * @returns {Element|null}
 */

function getFirstTabbableIn(container, fallbackToFocusable) {
  var _getAllTabbableIn = getAllTabbableIn(container, fallbackToFocusable),
      first = _getAllTabbableIn[0];

  return first || null;
}
/**
 * Returns the last tabbable element in `container`, including the container
 * itself if it's tabbable.
 *
 * @memberof tabbable
 *
 * @param {Element} container
 * @param fallbackToFocusable If `true`, it'll return the last focusable element if there are no tabbable ones.
 *
 * @returns {Element|null}
 */

function getLastTabbableIn(container, fallbackToFocusable) {
  var allTabbable = getAllTabbableIn(container, fallbackToFocusable);
  return allTabbable[allTabbable.length - 1] || null;
}
/**
 * Returns the next tabbable element in `container`.
 *
 * @memberof tabbable
 *
 * @param {Element} container
 * @param fallbackToFocusable If `true`, it'll return the next focusable element if there are no tabbable ones.
 *
 * @returns {Element|null}
 */

function getNextTabbableIn(container, fallbackToFocusable) {
  var activeElement = getActiveElement(container);
  var allFocusable = getAllFocusableIn(container);
  var index = allFocusable.indexOf(activeElement);
  var slice = allFocusable.slice(index + 1);
  return slice.find(isTabbable) || allFocusable.find(isTabbable) || (fallbackToFocusable ? slice[0] : null);
}
/**
 * Returns the previous tabbable element in `container`.
 *
 * @memberof tabbable
 *
 * @param {Element} container
 * @param fallbackToFocusable If `true`, it'll return the previous focusable element if there are no tabbable ones.
 *
 * @returns {Element|null}
 */

function getPreviousTabbableIn(container, fallbackToFocusable) {
  var activeElement = getActiveElement(container);
  var allFocusable = getAllFocusableIn(container).reverse();
  var index = allFocusable.indexOf(activeElement);
  var slice = allFocusable.slice(index + 1);
  return slice.find(isTabbable) || allFocusable.find(isTabbable) || (fallbackToFocusable ? slice[0] : null);
}
/**
 * Returns the closest focusable element.
 *
 * @memberof tabbable
 *
 * @param {Element} container
 *
 * @returns {Element|null}
 */

function getClosestFocusable(element) {
  while (element && !isFocusable(element)) {
    element = closest(element, selector);
  }

  return element;
}



// EXTERNAL MODULE: ../../node_modules/.pnpm/reakit@1.3.11_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit/es/Role/Role.js
var Role = __webpack_require__("../../node_modules/.pnpm/reakit@1.3.11_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit/es/Role/Role.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/reakit@1.3.11_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit/es/Tabbable/Tabbable.js
















// Automatically generated
var TABBABLE_KEYS = ["disabled", "focusable"];

var isSafariOrFirefoxOnMac = (0,dom/* isUA */.p)("Mac") && !(0,dom/* isUA */.p)("Chrome") && ((0,dom/* isUA */.p)("Safari") || (0,dom/* isUA */.p)("Firefox"));

function focusIfNeeded(element) {
  if (!(0,hasFocusWithin/* hasFocusWithin */.o)(element) && isFocusable(element)) {
    element.focus();
  }
}

function isNativeTabbable(element) {
  return ["BUTTON", "INPUT", "SELECT", "TEXTAREA", "A"].includes(element.tagName);
}

function supportsDisabledAttribute(element) {
  return ["BUTTON", "INPUT", "SELECT", "TEXTAREA"].includes(element.tagName);
}

function getTabIndex(trulyDisabled, nativeTabbable, supportsDisabled, htmlTabIndex) {
  if (trulyDisabled) {
    if (nativeTabbable && !supportsDisabled) {
      // Anchor, audio and video tags don't support the `disabled` attribute.
      // We must pass tabIndex={-1} so they don't receive focus on tab.
      return -1;
    } // Elements that support the `disabled` attribute don't need tabIndex.


    return undefined;
  }

  if (nativeTabbable) {
    // If the element is enabled and it's natively tabbable, we don't need to
    // specify a tabIndex attribute unless it's explicitly set by the user.
    return htmlTabIndex;
  } // If the element is enabled and is not natively tabbable, we have to
  // fallback tabIndex={0}.


  return htmlTabIndex || 0;
}

function useDisableEvent(htmlEventRef, disabled) {
  return (0,react.useCallback)(function (event) {
    var _htmlEventRef$current;

    (_htmlEventRef$current = htmlEventRef.current) === null || _htmlEventRef$current === void 0 ? void 0 : _htmlEventRef$current.call(htmlEventRef, event);
    if (event.defaultPrevented) return;

    if (disabled) {
      event.stopPropagation();
      event.preventDefault();
    }
  }, [htmlEventRef, disabled]);
}

var useTabbable = (0,createHook/* createHook */.a)({
  name: "Tabbable",
  compose: Role/* useRole */.I,
  keys: TABBABLE_KEYS,
  useOptions: function useOptions(options, _ref) {
    var disabled = _ref.disabled;
    return (0,_rollupPluginBabelHelpers_1f0bf8c2.a)({
      disabled: disabled
    }, options);
  },
  useProps: function useProps(options, _ref2) {
    var htmlRef = _ref2.ref,
        htmlTabIndex = _ref2.tabIndex,
        htmlOnClickCapture = _ref2.onClickCapture,
        htmlOnMouseDownCapture = _ref2.onMouseDownCapture,
        htmlOnMouseDown = _ref2.onMouseDown,
        htmlOnKeyPressCapture = _ref2.onKeyPressCapture,
        htmlStyle = _ref2.style,
        htmlProps = (0,_rollupPluginBabelHelpers_1f0bf8c2._)(_ref2, ["ref", "tabIndex", "onClickCapture", "onMouseDownCapture", "onMouseDown", "onKeyPressCapture", "style"]);

    var ref = (0,react.useRef)(null);
    var onClickCaptureRef = (0,useLiveRef/* useLiveRef */.h)(htmlOnClickCapture);
    var onMouseDownCaptureRef = (0,useLiveRef/* useLiveRef */.h)(htmlOnMouseDownCapture);
    var onMouseDownRef = (0,useLiveRef/* useLiveRef */.h)(htmlOnMouseDown);
    var onKeyPressCaptureRef = (0,useLiveRef/* useLiveRef */.h)(htmlOnKeyPressCapture);
    var trulyDisabled = !!options.disabled && !options.focusable;

    var _React$useState = (0,react.useState)(true),
        nativeTabbable = _React$useState[0],
        setNativeTabbable = _React$useState[1];

    var _React$useState2 = (0,react.useState)(true),
        supportsDisabled = _React$useState2[0],
        setSupportsDisabled = _React$useState2[1];

    var style = options.disabled ? (0,_rollupPluginBabelHelpers_1f0bf8c2.a)({
      pointerEvents: "none"
    }, htmlStyle) : htmlStyle;
    (0,useIsomorphicEffect/* useIsomorphicEffect */.o)(function () {
      var tabbable = ref.current;

      if (!tabbable) {
         false ? 0 : void 0;
        return;
      }

      if (!isNativeTabbable(tabbable)) {
        setNativeTabbable(false);
      }

      if (!supportsDisabledAttribute(tabbable)) {
        setSupportsDisabled(false);
      }
    }, []);
    var onClickCapture = useDisableEvent(onClickCaptureRef, options.disabled);
    var onMouseDownCapture = useDisableEvent(onMouseDownCaptureRef, options.disabled);
    var onKeyPressCapture = useDisableEvent(onKeyPressCaptureRef, options.disabled);
    var onMouseDown = (0,react.useCallback)(function (event) {
      var _onMouseDownRef$curre;

      (_onMouseDownRef$curre = onMouseDownRef.current) === null || _onMouseDownRef$curre === void 0 ? void 0 : _onMouseDownRef$curre.call(onMouseDownRef, event);
      var element = event.currentTarget;
      if (event.defaultPrevented) return; // Safari and Firefox on MacOS don't focus on buttons on mouse down
      // like other browsers/platforms. Instead, they focus on the closest
      // focusable ancestor element, which is ultimately the body element. So
      // we make sure to give focus to the tabbable element on mouse down so
      // it works consistently across browsers.

      if (!isSafariOrFirefoxOnMac) return;
      if ((0,isPortalEvent/* isPortalEvent */.h)(event)) return;
      if (!(0,isButton/* isButton */.B)(element)) return; // We can't focus right away after on mouse down, otherwise it would
      // prevent drag events from happening. So we schedule the focus to the
      // next animation frame.

      var raf = requestAnimationFrame(function () {
        element.removeEventListener("mouseup", focusImmediately, true);
        focusIfNeeded(element);
      }); // If mouseUp happens before the next animation frame (which is common
      // on touch screens or by just tapping the trackpad on MacBook's), we
      // cancel the animation frame and immediately focus on the element.

      var focusImmediately = function focusImmediately() {
        cancelAnimationFrame(raf);
        focusIfNeeded(element);
      }; // By listening to the event in the capture phase, we make sure the
      // focus event is fired before the onMouseUp and onMouseUpCapture React
      // events, which is aligned with the default browser behavior.


      element.addEventListener("mouseup", focusImmediately, {
        once: true,
        capture: true
      });
    }, []);
    return (0,_rollupPluginBabelHelpers_1f0bf8c2.a)({
      ref: (0,useForkRef/* useForkRef */.N)(ref, htmlRef),
      style: style,
      tabIndex: getTabIndex(trulyDisabled, nativeTabbable, supportsDisabled, htmlTabIndex),
      disabled: trulyDisabled && supportsDisabled ? true : undefined,
      "aria-disabled": options.disabled ? true : undefined,
      onClickCapture: onClickCapture,
      onMouseDownCapture: onMouseDownCapture,
      onMouseDown: onMouseDown,
      onKeyPressCapture: onKeyPressCapture
    }, htmlProps);
  }
});
var Tabbable = (0,createComponent/* createComponent */.a)({
  as: "div",
  useHook: useTabbable
});




/***/ }),

/***/ "../../node_modules/.pnpm/reakit@1.3.11_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit/es/Toolbar/Toolbar.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   M: () => (/* binding */ Toolbar)
/* harmony export */ });
/* unused harmony export useToolbar */
/* harmony import */ var _rollupPluginBabelHelpers_1f0bf8c2_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__("../../node_modules/.pnpm/reakit@1.3.11_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit/es/_rollupPluginBabelHelpers-1f0bf8c2.js");
/* harmony import */ var reakit_system_createComponent__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__("../../node_modules/.pnpm/reakit-system@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-system/es/createComponent.js");
/* harmony import */ var reakit_system_createHook__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/reakit-system@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-system/es/createHook.js");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var reakit_warning__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/reakit-warning@0.6.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-warning/es/index.js");
/* harmony import */ var reakit_system_useCreateElement__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__("../../node_modules/.pnpm/reakit-system@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-system/es/useCreateElement.js");
/* harmony import */ var _Composite_Composite_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/reakit@1.3.11_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit/es/Composite/Composite.js");
/* harmony import */ var _keys_ae468c11_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__("../../node_modules/.pnpm/reakit@1.3.11_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit/es/__keys-ae468c11.js");































var useToolbar = (0,reakit_system_createHook__WEBPACK_IMPORTED_MODULE_2__/* .createHook */ .a)({
  name: "Toolbar",
  compose: _Composite_Composite_js__WEBPACK_IMPORTED_MODULE_3__/* .useComposite */ .T,
  keys: _keys_ae468c11_js__WEBPACK_IMPORTED_MODULE_4__.T,
  useProps: function useProps(options, htmlProps) {
    return (0,_rollupPluginBabelHelpers_1f0bf8c2_js__WEBPACK_IMPORTED_MODULE_5__.a)({
      role: "toolbar",
      "aria-orientation": options.orientation
    }, htmlProps);
  }
});
var Toolbar = (0,reakit_system_createComponent__WEBPACK_IMPORTED_MODULE_6__/* .createComponent */ .a)({
  as: "div",
  useHook: useToolbar,
  useCreateElement: function useCreateElement$1(type, props, children) {
     false ? 0 : void 0;
    return (0,reakit_system_useCreateElement__WEBPACK_IMPORTED_MODULE_7__/* .useCreateElement */ .U)(type, props, children);
  }
});




/***/ }),

/***/ "../../node_modules/.pnpm/reakit@1.3.11_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit/es/Toolbar/ToolbarItem.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   T: () => (/* binding */ ToolbarItem)
/* harmony export */ });
/* unused harmony export useToolbarItem */
/* harmony import */ var reakit_system_createComponent__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__("../../node_modules/.pnpm/reakit-system@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-system/es/createComponent.js");
/* harmony import */ var reakit_system_createHook__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/reakit-system@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-system/es/createHook.js");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var reakit_warning__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/reakit-warning@0.6.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-warning/es/index.js");
/* harmony import */ var _Composite_CompositeItem_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/reakit@1.3.11_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit/es/Composite/CompositeItem.js");
/* harmony import */ var _keys_ae468c11_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__("../../node_modules/.pnpm/reakit@1.3.11_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit/es/__keys-ae468c11.js");































var useToolbarItem = (0,reakit_system_createHook__WEBPACK_IMPORTED_MODULE_2__/* .createHook */ .a)({
  name: "ToolbarItem",
  compose: _Composite_CompositeItem_js__WEBPACK_IMPORTED_MODULE_3__/* .useCompositeItem */ .k,
  keys: _keys_ae468c11_js__WEBPACK_IMPORTED_MODULE_4__.a
});
var ToolbarItem = (0,reakit_system_createComponent__WEBPACK_IMPORTED_MODULE_5__/* .createComponent */ .a)({
  as: "button",
  memo: true,
  useHook: useToolbarItem
});




/***/ }),

/***/ "../../node_modules/.pnpm/reakit@1.3.11_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit/es/Toolbar/ToolbarState.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   S: () => (/* binding */ useToolbarState)
/* harmony export */ });
/* harmony import */ var _rollupPluginBabelHelpers_1f0bf8c2_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/reakit@1.3.11_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit/es/_rollupPluginBabelHelpers-1f0bf8c2.js");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var reakit_utils_useSealedState__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/reakit-utils@0.15.2_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit-utils/es/useSealedState.js");
/* harmony import */ var _Composite_CompositeState_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/reakit@1.3.11_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit/es/Composite/CompositeState.js");













function useToolbarState(initialState) {
  if (initialState === void 0) {
    initialState = {};
  }

  var _useSealedState = (0,reakit_utils_useSealedState__WEBPACK_IMPORTED_MODULE_1__/* .useSealedState */ .N)(initialState),
      _useSealedState$orien = _useSealedState.orientation,
      orientation = _useSealedState$orien === void 0 ? "horizontal" : _useSealedState$orien,
      sealed = (0,_rollupPluginBabelHelpers_1f0bf8c2_js__WEBPACK_IMPORTED_MODULE_2__._)(_useSealedState, ["orientation"]);

  return (0,_Composite_CompositeState_js__WEBPACK_IMPORTED_MODULE_3__/* .useCompositeState */ .A)((0,_rollupPluginBabelHelpers_1f0bf8c2_js__WEBPACK_IMPORTED_MODULE_2__.a)({
    orientation: orientation
  }, sealed));
}




/***/ }),

/***/ "../../node_modules/.pnpm/reakit@1.3.11_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit/es/__keys-6742f591.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   C: () => (/* binding */ COMPOSITE_KEYS),
/* harmony export */   a: () => (/* binding */ COMPOSITE_GROUP_KEYS),
/* harmony export */   b: () => (/* binding */ COMPOSITE_ITEM_KEYS)
/* harmony export */ });
/* unused harmony export c */
// Automatically generated
var COMPOSITE_STATE_KEYS = ["baseId", "unstable_idCountRef", "setBaseId", "unstable_virtual", "rtl", "orientation", "items", "groups", "currentId", "loop", "wrap", "shift", "unstable_moves", "unstable_hasActiveWidget", "unstable_includesBaseElement", "registerItem", "unregisterItem", "registerGroup", "unregisterGroup", "move", "next", "previous", "up", "down", "first", "last", "sort", "unstable_setVirtual", "setRTL", "setOrientation", "setCurrentId", "setLoop", "setWrap", "setShift", "reset", "unstable_setIncludesBaseElement", "unstable_setHasActiveWidget"];
var COMPOSITE_KEYS = COMPOSITE_STATE_KEYS;
var COMPOSITE_GROUP_KEYS = COMPOSITE_KEYS;
var COMPOSITE_ITEM_KEYS = COMPOSITE_GROUP_KEYS;
var COMPOSITE_ITEM_WIDGET_KEYS = (/* unused pure expression or super */ null && (COMPOSITE_ITEM_KEYS));




/***/ }),

/***/ "../../node_modules/.pnpm/reakit@1.3.11_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit/es/__keys-ae468c11.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   T: () => (/* binding */ TOOLBAR_KEYS),
/* harmony export */   a: () => (/* binding */ TOOLBAR_ITEM_KEYS)
/* harmony export */ });
/* unused harmony export b */
// Automatically generated
var TOOLBAR_STATE_KEYS = ["baseId", "unstable_idCountRef", "unstable_virtual", "rtl", "orientation", "items", "groups", "currentId", "loop", "wrap", "shift", "unstable_moves", "unstable_hasActiveWidget", "unstable_includesBaseElement", "setBaseId", "registerItem", "unregisterItem", "registerGroup", "unregisterGroup", "move", "next", "previous", "up", "down", "first", "last", "sort", "unstable_setVirtual", "setRTL", "setOrientation", "setCurrentId", "setLoop", "setWrap", "setShift", "reset", "unstable_setIncludesBaseElement", "unstable_setHasActiveWidget"];
var TOOLBAR_KEYS = TOOLBAR_STATE_KEYS;
var TOOLBAR_ITEM_KEYS = TOOLBAR_KEYS;
var TOOLBAR_SEPARATOR_KEYS = (/* unused pure expression or super */ null && (TOOLBAR_ITEM_KEYS));




/***/ }),

/***/ "../../node_modules/.pnpm/reakit@1.3.11_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit/es/_rollupPluginBabelHelpers-1f0bf8c2.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   _: () => (/* binding */ _objectWithoutPropertiesLoose),
/* harmony export */   a: () => (/* binding */ _objectSpread2),
/* harmony export */   b: () => (/* binding */ _createForOfIteratorHelperLoose)
/* harmony export */ });
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

function _objectSpread2(target) {
  for (var i = 1; i < arguments.length; i++) {
    var source = arguments[i] != null ? arguments[i] : {};

    if (i % 2) {
      ownKeys(Object(source), true).forEach(function (key) {
        _defineProperty(target, key, source[key]);
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

function _unsupportedIterableToArray(o, minLen) {
  if (!o) return;
  if (typeof o === "string") return _arrayLikeToArray(o, minLen);
  var n = Object.prototype.toString.call(o).slice(8, -1);
  if (n === "Object" && o.constructor) n = o.constructor.name;
  if (n === "Map" || n === "Set") return Array.from(o);
  if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen);
}

function _arrayLikeToArray(arr, len) {
  if (len == null || len > arr.length) len = arr.length;

  for (var i = 0, arr2 = new Array(len); i < len; i++) arr2[i] = arr[i];

  return arr2;
}

function _createForOfIteratorHelperLoose(o, allowArrayLike) {
  var it;

  if (typeof Symbol === "undefined" || o[Symbol.iterator] == null) {
    if (Array.isArray(o) || (it = _unsupportedIterableToArray(o)) || allowArrayLike && o && typeof o.length === "number") {
      if (it) o = it;
      var i = 0;
      return function () {
        if (i >= o.length) return {
          done: true
        };
        return {
          done: false,
          value: o[i++]
        };
      };
    }

    throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method.");
  }

  it = o[Symbol.iterator]();
  return it.next.bind(it);
}




/***/ }),

/***/ "../../node_modules/.pnpm/reakit@1.3.11_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit/es/findEnabledItemById-8ddca752.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   f: () => (/* binding */ findEnabledItemById)
/* harmony export */ });
function findEnabledItemById(items, id) {
  if (!id) return undefined;
  return items === null || items === void 0 ? void 0 : items.find(function (item) {
    return item.id === id && !item.disabled;
  });
}




/***/ }),

/***/ "../../node_modules/.pnpm/reakit@1.3.11_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit/es/getCurrentId-5aa9849e.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   f: () => (/* binding */ findFirstEnabledItem),
/* harmony export */   g: () => (/* binding */ getCurrentId)
/* harmony export */ });
function findFirstEnabledItem(items, excludeId) {
  if (excludeId) {
    return items.find(function (item) {
      return !item.disabled && item.id !== excludeId;
    });
  }

  return items.find(function (item) {
    return !item.disabled;
  });
}

function getCurrentId(options, passedId) {
  var _findFirstEnabledItem;

  if (passedId || passedId === null) {
    return passedId;
  }

  if (options.currentId || options.currentId === null) {
    return options.currentId;
  }

  return (_findFirstEnabledItem = findFirstEnabledItem(options.items || [])) === null || _findFirstEnabledItem === void 0 ? void 0 : _findFirstEnabledItem.id;
}




/***/ }),

/***/ "../../node_modules/.pnpm/reakit@1.3.11_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit/es/reverse-30eaa122.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   f: () => (/* binding */ flatten),
/* harmony export */   g: () => (/* binding */ groupItems),
/* harmony export */   r: () => (/* binding */ reverse)
/* harmony export */ });
/* harmony import */ var _rollupPluginBabelHelpers_1f0bf8c2_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/reakit@1.3.11_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit/es/_rollupPluginBabelHelpers-1f0bf8c2.js");


function groupItems(items) {
  var groups = [[]];

  var _loop = function _loop() {
    var item = _step.value;
    var group = groups.find(function (g) {
      return !g[0] || g[0].groupId === item.groupId;
    });

    if (group) {
      group.push(item);
    } else {
      groups.push([item]);
    }
  };

  for (var _iterator = (0,_rollupPluginBabelHelpers_1f0bf8c2_js__WEBPACK_IMPORTED_MODULE_0__.b)(items), _step; !(_step = _iterator()).done;) {
    _loop();
  }

  return groups;
}

function flatten(grid) {
  var flattened = [];

  for (var _iterator = (0,_rollupPluginBabelHelpers_1f0bf8c2_js__WEBPACK_IMPORTED_MODULE_0__.b)(grid), _step; !(_step = _iterator()).done;) {
    var row = _step.value;
    flattened.push.apply(flattened, row);
  }

  return flattened;
}

function reverse(array) {
  return array.slice().reverse();
}




/***/ }),

/***/ "../../node_modules/.pnpm/reakit@1.3.11_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/reakit/es/userFocus-e16425e3.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   h: () => (/* binding */ hasUserFocus),
/* harmony export */   s: () => (/* binding */ setUserFocus),
/* harmony export */   u: () => (/* binding */ userFocus)
/* harmony export */ });
function userFocus(element) {
  element.userFocus = true;
  element.focus();
  element.userFocus = false;
}
function hasUserFocus(element) {
  return !!element.userFocus;
}
function setUserFocus(element, value) {
  element.userFocus = value;
}




/***/ })

}]);