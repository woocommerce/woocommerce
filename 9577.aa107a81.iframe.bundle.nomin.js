(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[9577],{

/***/ "../../node_modules/.pnpm/@automattic+tour-kit@1.1.3_@types+react@17.0.71_@wordpress+data@10.6.0_react@17.0.2__react-do_wpr2orkhsavt4ls5ttip2qkwai/node_modules/@automattic/tour-kit/dist/esm/components/tour-kit.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";

// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  A: () => (/* binding */ tour_kit)
});

// NAMESPACE OBJECT: ../../node_modules/.pnpm/@wordpress+dom@3.57.0/node_modules/@wordpress/dom/build-module/focusable.js
var focusable_namespaceObject = {};
__webpack_require__.r(focusable_namespaceObject);
__webpack_require__.d(focusable_namespaceObject, {
  find: () => (find)
});

// NAMESPACE OBJECT: ../../node_modules/.pnpm/@wordpress+dom@3.57.0/node_modules/@wordpress/dom/build-module/tabbable.js
var tabbable_namespaceObject = {};
__webpack_require__.r(tabbable_namespaceObject);
__webpack_require__.d(tabbable_namespaceObject, {
  find: () => (tabbable_find),
  findNext: () => (findNext),
  findPrevious: () => (findPrevious),
  isTabbableIndex: () => (isTabbableIndex)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/react@17.0.2/node_modules/react/jsx-runtime.js
var jsx_runtime = __webpack_require__("../../node_modules/.pnpm/react@17.0.2/node_modules/react/jsx-runtime.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react-dom@18.3.1_react@18.3.1/node_modules/react-dom/index.js
var react_dom = __webpack_require__("../../node_modules/.pnpm/react-dom@18.3.1_react@18.3.1/node_modules/react-dom/index.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@automattic+tour-kit@1.1.3_@types+react@17.0.71_@wordpress+data@10.6.0_react@17.0.2__react-do_wpr2orkhsavt4ls5ttip2qkwai/node_modules/@automattic/tour-kit/dist/esm/error-boundary.js


class ErrorBoundary extends react.Component {
    state = {
        hasError: false,
    };
    static getDerivedStateFromError() {
        // Update state so the next render will show the fallback UI.
        return { hasError: true };
    }
    componentDidCatch(error, errorInfo) {
        // You can also log the error to an error reporting service
        // eslint-disable-next-line no-console
        console.error(error, errorInfo);
    }
    render() {
        if (this.state.hasError) {
            // You can render any custom fallback UI
            return (0,jsx_runtime.jsx)("h1", { children: "Something went wrong." });
        }
        return this.props.children;
    }
}
/* harmony default export */ const error_boundary = (ErrorBoundary);
//# sourceMappingURL=error-boundary.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@automattic+tour-kit@1.1.3_@types+react@17.0.71_@wordpress+data@10.6.0_react@17.0.2__react-do_wpr2orkhsavt4ls5ttip2qkwai/node_modules/@automattic/tour-kit/dist/esm/components/tour-kit-context.js


const TourKitContext = (0,react.createContext)({});
const TourKitContextProvider = ({ config, children }) => {
    return (0,jsx_runtime.jsx)(TourKitContext.Provider, { value: { config }, children: children });
};
const useTourKitContext = () => useContext(TourKitContext);
/* harmony default export */ const tour_kit_context = (TourKitContextProvider);
//# sourceMappingURL=tour-kit-context.js.map
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/extends.js
var esm_extends = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/extends.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/slicedToArray.js + 1 modules
var slicedToArray = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/slicedToArray.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/lodash@4.17.21/node_modules/lodash/lodash.js
var lodash = __webpack_require__("../../node_modules/.pnpm/lodash@4.17.21/node_modules/lodash/lodash.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+compose@3.25.3_react@17.0.2/node_modules/@wordpress/compose/build-module/utils/create-higher-order-component/index.js
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

function create_higher_order_component_createHigherOrderComponent(mapComponentToEnhancedComponent, modifierName) {
  return function (OriginalComponent) {
    var EnhancedComponent = mapComponentToEnhancedComponent(OriginalComponent);
    var _OriginalComponent$di = OriginalComponent.displayName,
        displayName = _OriginalComponent$di === void 0 ? OriginalComponent.name || 'Component' : _OriginalComponent$di;
    EnhancedComponent.displayName = "".concat((0,lodash.upperFirst)((0,lodash.camelCase)(modifierName)), "(").concat(displayName, ")");
    return EnhancedComponent;
  };
}

/* harmony default export */ const create_higher_order_component = (create_higher_order_component_createHigherOrderComponent);
//# sourceMappingURL=index.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@automattic+viewport@1.1.0/node_modules/@automattic/viewport/dist/esm/index.js
// Determine whether a user is viewing calypso from a device within a
// particular mobile-first responsive breakpoint, matching our existing media
// queries. [1]
//
// This function takes a string matching one of our mobile-first breakpoints
// and returns a boolean based on whether the current `window.innerWidth`
// matches. This is used to segment behavior based on device or browser size,
// but should not be used in place of css for design.
//
// Valid breakpoints include:
// - '<480px'
// - '<660px'
// - '<800px'
// - '<960px'
// - '<1040px'
// - '<1280px'
// - '<1400px'
// - '>480px'
// - '>660px'
// - '>800px'
// - '>960px'
// - '>1040px'
// - '>1280px'
// - '>1400px'
// - '480px-660px'
// - '480px-960px'
// - '660px-960px'
//
// As implemented in our sass media query mixins, minimums are exclusive, while
// maximums are inclusive. i.e.,
//
// - '>480px' is equivalent to `@media (min-width: 481px)`
// - '<960px' is equivalent to `@media (max-width: 960px)`
// - '480px-960px' is equivalent to `@media (max-width: 960px) and (min-width: 481px)`
//
// [1] https://github.com/Automattic/wp-calypso/blob/HEAD/docs/coding-guidelines/css.md#media-queries
//
// FIXME: We can't detect window size on the server, so until we have more intelligent detection,
// use 769, which is just above the general maximum mobile screen width.
const SERVER_WIDTH = 769;
const MOBILE_BREAKPOINT = '<480px';
const esm_DESKTOP_BREAKPOINT = '>960px';
const isServer = typeof window === 'undefined' || !window.matchMedia;
const noop = () => null;
function addListenerFunctions(obj) {
    return {
        addListener: () => undefined,
        removeListener: () => undefined,
        ...obj,
    };
}
function createMediaQueryList(args) {
    const { min, max } = args ?? {};
    if (min !== undefined && max !== undefined) {
        return isServer
            ? addListenerFunctions({ matches: SERVER_WIDTH > min && SERVER_WIDTH <= max })
            : window.matchMedia(`(min-width: ${min + 1}px) and (max-width: ${max}px)`);
    }
    if (min !== undefined) {
        return isServer
            ? addListenerFunctions({ matches: SERVER_WIDTH > min })
            : window.matchMedia(`(min-width: ${min + 1}px)`);
    }
    if (max !== undefined) {
        return isServer
            ? addListenerFunctions({ matches: SERVER_WIDTH <= max })
            : window.matchMedia(`(max-width: ${max}px)`);
    }
    return false;
}
const mediaQueryLists = {
    '<480px': createMediaQueryList({ max: 480 }),
    '<660px': createMediaQueryList({ max: 660 }),
    '<782px': createMediaQueryList({ max: 782 }),
    '<800px': createMediaQueryList({ max: 800 }),
    '<960px': createMediaQueryList({ max: 960 }),
    '<1040px': createMediaQueryList({ max: 1040 }),
    '<1280px': createMediaQueryList({ max: 1280 }),
    '<1400px': createMediaQueryList({ max: 1400 }),
    '>480px': createMediaQueryList({ min: 480 }),
    '>660px': createMediaQueryList({ min: 660 }),
    '>782px': createMediaQueryList({ min: 782 }),
    '>800px': createMediaQueryList({ min: 800 }),
    '>960px': createMediaQueryList({ min: 960 }),
    '>1040px': createMediaQueryList({ min: 1040 }),
    '>1280px': createMediaQueryList({ min: 1280 }),
    '>1400px': createMediaQueryList({ min: 1400 }),
    '480px-660px': createMediaQueryList({ min: 480, max: 660 }),
    '660px-960px': createMediaQueryList({ min: 660, max: 960 }),
    '480px-960px': createMediaQueryList({ min: 480, max: 960 }),
};
function getMediaQueryList(breakpoint) {
    if (!mediaQueryLists.hasOwnProperty(breakpoint)) {
        try {
            // eslint-disable-next-line no-console
            console.warn('Undefined breakpoint used in `mobile-first-breakpoint`', breakpoint);
        }
        catch (e) { }
        return undefined;
    }
    return mediaQueryLists[breakpoint];
}
/**
 * Returns whether the current window width matches a breakpoint.
 *
 * @param {string} breakpoint The breakpoint to consider.
 * @returns {boolean|undefined} Whether the provided breakpoint is matched.
 */
function isWithinBreakpoint(breakpoint) {
    const mediaQueryList = getMediaQueryList(breakpoint);
    return mediaQueryList ? mediaQueryList.matches : undefined;
}
/**
 * Registers a listener to be notified of changes to breakpoint matching status.
 *
 * @param {string} breakpoint The breakpoint to consider.
 * @param {Function} listener The listener to be called on change.
 * @returns {Function} The function to be called when unsubscribing.
 */
function subscribeIsWithinBreakpoint(breakpoint, listener) {
    if (!listener) {
        return noop;
    }
    const mediaQueryList = getMediaQueryList(breakpoint);
    if (mediaQueryList && !isServer) {
        const wrappedListener = (evt) => listener(evt.matches);
        mediaQueryList.addListener(wrappedListener);
        // Return unsubscribe function.
        return () => mediaQueryList.removeListener(wrappedListener);
    }
    return noop;
}
/**
 * Returns whether the current window width matches the mobile breakpoint.
 *
 * @returns {boolean|undefined} Whether the mobile breakpoint is matched.
 */
function isMobile() {
    return isWithinBreakpoint(MOBILE_BREAKPOINT);
}
/**
 * Registers a listener to be notified of changes to mobile breakpoint matching status.
 *
 * @param {Function} listener The listener to be called on change.
 * @returns {Function} The registered subscription; undefined if none.
 */
function subscribeIsMobile(listener) {
    return subscribeIsWithinBreakpoint(MOBILE_BREAKPOINT, listener);
}
/**
 * Returns whether the current window width matches the desktop breakpoint.
 *
 * @returns {boolean|undefined} Whether the desktop breakpoint is matched.
 */
function isDesktop() {
    return isWithinBreakpoint(esm_DESKTOP_BREAKPOINT);
}
/**
 * Registers a listener to be notified of changes to desktop breakpoint matching status.
 *
 * @param {Function} listener The listener to be called on change.
 * @returns {Function} The registered subscription; undefined if none.
 */
function subscribeIsDesktop(listener) {
    return subscribeIsWithinBreakpoint(esm_DESKTOP_BREAKPOINT, listener);
}
/**
 * Returns the current window width.
 * Avoid using this method, as it triggers a layout recalc.
 *
 * @returns {number} The current window width, in pixels.
 */
function getWindowInnerWidth() {
    return isServer ? SERVER_WIDTH : window.innerWidth;
}
/******************************************/
/*       Vertical Scroll Experiment       */
/*  	       pcbrnV-XN-p2               */
/******************************************/
//TODO: To be refactored using above using the DESKTOP_BREAKPOINT constant
function isTabletResolution() {
    if (!isServer) {
        return window.innerWidth < 1040;
    }
    return false;
}
const DEVICE_MOBILE = 'mobile';
const DEVICE_TABLET = 'tablet';
const DEVICE_DESKTOP = 'desktop';
function resolveDeviceTypeByViewPort() {
    if (isMobile()) {
        return DEVICE_MOBILE;
    }
    else if (isTabletResolution()) {
        return DEVICE_TABLET;
    }
    return DEVICE_DESKTOP;
}
/******************************************/
//# sourceMappingURL=index.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@automattic+viewport-react@1.0.0_react@17.0.2/node_modules/@automattic/viewport-react/dist/esm/index.js



/**
 * External dependencies
 */


/**
 * Internal dependencies
 */


/**
 * React hook for getting the status for a breakpoint and keeping it updated.
 *
 * @param {string} breakpoint The breakpoint to consider.
 *
 * @returns {boolean} The current status for the breakpoint.
 */

function useBreakpoint(breakpoint) {
  var _useState = (0,react.useState)(function () {
    return {
      isActive: isWithinBreakpoint(breakpoint),
      breakpoint: breakpoint
    };
  }),
      _useState2 = (0,slicedToArray/* default */.A)(_useState, 2),
      state = _useState2[0],
      setState = _useState2[1];

  (0,react.useEffect)(function () {
    function handleBreakpointChange(isActive) {
      setState(function (prevState) {
        // Ensure we bail out without rendering if nothing changes, by preserving state.
        if (prevState.isActive === isActive && prevState.breakpoint === breakpoint) {
          return prevState;
        }

        return {
          isActive: isActive,
          breakpoint: breakpoint
        };
      });
    }

    var unsubscribe = subscribeIsWithinBreakpoint(breakpoint, handleBreakpointChange); // The unsubscribe function is the entire cleanup for the effect.

    return unsubscribe;
  }, [breakpoint]);
  return breakpoint === state.breakpoint ? state.isActive : isWithinBreakpoint(breakpoint);
}
/**
 * React hook for getting the status for the mobile breakpoint and keeping it
 * updated.
 *
 * @returns {boolean} The current status for the breakpoint.
 */

function useMobileBreakpoint() {
  return useBreakpoint(MOBILE_BREAKPOINT);
}
/**
 * React hook for getting the status for the desktop breakpoint and keeping it
 * updated.
 *
 * @returns {boolean} The current status for the breakpoint.
 */

function useDesktopBreakpoint() {
  return useBreakpoint(DESKTOP_BREAKPOINT);
}
/**
 * React higher order component for getting the status for a breakpoint and
 * keeping it updated.
 *
 * @param {string} breakpoint The breakpoint to consider.
 *
 * @returns {Function} A function that given a component returns the
 * wrapped component.
 */

var withBreakpoint = function withBreakpoint(breakpoint) {
  return createHigherOrderComponent(function (WrappedComponent) {
    return forwardRef(function (props, ref) {
      var isActive = useBreakpoint(breakpoint);
      return React.createElement(WrappedComponent, _extends({}, props, {
        isBreakpointActive: isActive,
        ref: ref
      }));
    });
  }, 'WithBreakpoint');
};
/**
 * React higher order component for getting the status for the mobile
 * breakpoint and keeping it updated.
 *
 * @param {React.Component|Function} Wrapped The component to wrap.
 *
 * @returns {Function} The wrapped component.
 */

var withMobileBreakpoint = create_higher_order_component(function (WrappedComponent) {
  return (0,react.forwardRef)(function (props, ref) {
    var isActive = useBreakpoint(MOBILE_BREAKPOINT);
    return react.createElement(WrappedComponent, (0,esm_extends/* default */.A)({}, props, {
      isBreakpointActive: isActive,
      ref: ref
    }));
  });
}, 'WithMobileBreakpoint');
/**
 * React higher order component for getting the status for the desktop
 * breakpoint and keeping it updated.
 *
 * @param {React.Component|Function} Wrapped The component to wrap.
 *
 * @returns {Function} The wrapped component.
 */

var withDesktopBreakpoint = create_higher_order_component(function (WrappedComponent) {
  return (0,react.forwardRef)(function (props, ref) {
    var isActive = useBreakpoint(esm_DESKTOP_BREAKPOINT);
    return react.createElement(WrappedComponent, (0,esm_extends/* default */.A)({}, props, {
      isBreakpointActive: isActive,
      ref: ref
    }));
  });
}, 'WithDesktopBreakpoint');
// EXTERNAL MODULE: ../../node_modules/.pnpm/classnames@2.3.2/node_modules/classnames/index.js
var classnames = __webpack_require__("../../node_modules/.pnpm/classnames@2.3.2/node_modules/classnames/index.js");
var classnames_default = /*#__PURE__*/__webpack_require__.n(classnames);
// EXTERNAL MODULE: ../../node_modules/.pnpm/@popperjs+core@2.11.8/node_modules/@popperjs/core/lib/popper.js + 53 modules
var popper = __webpack_require__("../../node_modules/.pnpm/@popperjs+core@2.11.8/node_modules/@popperjs/core/lib/popper.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react-fast-compare@3.2.2/node_modules/react-fast-compare/index.js
var react_fast_compare = __webpack_require__("../../node_modules/.pnpm/react-fast-compare@3.2.2/node_modules/react-fast-compare/index.js");
var react_fast_compare_default = /*#__PURE__*/__webpack_require__.n(react_fast_compare);
;// CONCATENATED MODULE: ../../node_modules/.pnpm/react-popper@2.3.0_@popperjs+core@2.11.8_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/react-popper/lib/esm/utils.js


/**
 * Takes an argument and if it's an array, returns the first item in the array,
 * otherwise returns the argument. Used for Preact compatibility.
 */
var unwrapArray = function unwrapArray(arg) {
  return Array.isArray(arg) ? arg[0] : arg;
};
/**
 * Takes a maybe-undefined function and arbitrary args and invokes the function
 * only if it is defined.
 */

var safeInvoke = function safeInvoke(fn) {
  if (typeof fn === 'function') {
    for (var _len = arguments.length, args = new Array(_len > 1 ? _len - 1 : 0), _key = 1; _key < _len; _key++) {
      args[_key - 1] = arguments[_key];
    }

    return fn.apply(void 0, args);
  }
};
/**
 * Sets a ref using either a ref callback or a ref object
 */

var setRef = function setRef(ref, node) {
  // if its a function call it
  if (typeof ref === 'function') {
    return safeInvoke(ref, node);
  } // otherwise we should treat it as a ref object
  else if (ref != null) {
      ref.current = node;
    }
};
/**
 * Simple ponyfill for Object.fromEntries
 */

var fromEntries = function fromEntries(entries) {
  return entries.reduce(function (acc, _ref) {
    var key = _ref[0],
        value = _ref[1];
    acc[key] = value;
    return acc;
  }, {});
};
/**
 * Small wrapper around `useLayoutEffect` to get rid of the warning on SSR envs
 */

var useIsomorphicLayoutEffect = typeof window !== 'undefined' && window.document && window.document.createElement ? react.useLayoutEffect : react.useEffect;
;// CONCATENATED MODULE: ../../node_modules/.pnpm/react-popper@2.3.0_@popperjs+core@2.11.8_react-dom@17.0.2_react@17.0.2__react@17.0.2/node_modules/react-popper/lib/esm/usePopper.js





var EMPTY_MODIFIERS = [];
var usePopper = function usePopper(referenceElement, popperElement, options) {
  if (options === void 0) {
    options = {};
  }

  var prevOptions = react.useRef(null);
  var optionsWithDefaults = {
    onFirstUpdate: options.onFirstUpdate,
    placement: options.placement || 'bottom',
    strategy: options.strategy || 'absolute',
    modifiers: options.modifiers || EMPTY_MODIFIERS
  };

  var _React$useState = react.useState({
    styles: {
      popper: {
        position: optionsWithDefaults.strategy,
        left: '0',
        top: '0'
      },
      arrow: {
        position: 'absolute'
      }
    },
    attributes: {}
  }),
      state = _React$useState[0],
      setState = _React$useState[1];

  var updateStateModifier = react.useMemo(function () {
    return {
      name: 'updateState',
      enabled: true,
      phase: 'write',
      fn: function fn(_ref) {
        var state = _ref.state;
        var elements = Object.keys(state.elements);
        react_dom.flushSync(function () {
          setState({
            styles: fromEntries(elements.map(function (element) {
              return [element, state.styles[element] || {}];
            })),
            attributes: fromEntries(elements.map(function (element) {
              return [element, state.attributes[element]];
            }))
          });
        });
      },
      requires: ['computeStyles']
    };
  }, []);
  var popperOptions = react.useMemo(function () {
    var newOptions = {
      onFirstUpdate: optionsWithDefaults.onFirstUpdate,
      placement: optionsWithDefaults.placement,
      strategy: optionsWithDefaults.strategy,
      modifiers: [].concat(optionsWithDefaults.modifiers, [updateStateModifier, {
        name: 'applyStyles',
        enabled: false
      }])
    };

    if (react_fast_compare_default()(prevOptions.current, newOptions)) {
      return prevOptions.current || newOptions;
    } else {
      prevOptions.current = newOptions;
      return newOptions;
    }
  }, [optionsWithDefaults.onFirstUpdate, optionsWithDefaults.placement, optionsWithDefaults.strategy, optionsWithDefaults.modifiers, updateStateModifier]);
  var popperInstanceRef = react.useRef();
  useIsomorphicLayoutEffect(function () {
    if (popperInstanceRef.current) {
      popperInstanceRef.current.setOptions(popperOptions);
    }
  }, [popperOptions]);
  useIsomorphicLayoutEffect(function () {
    if (referenceElement == null || popperElement == null) {
      return;
    }

    var createPopper = options.createPopper || popper/* createPopper */.n4;
    var popperInstance = createPopper(referenceElement, popperElement, popperOptions);
    popperInstanceRef.current = popperInstance;
    return function () {
      popperInstance.destroy();
      popperInstanceRef.current = null;
    };
  }, [referenceElement, popperElement, options.createPopper]);
  return {
    state: popperInstanceRef.current ? popperInstanceRef.current.state : null,
    styles: state.styles,
    attributes: state.attributes,
    update: popperInstanceRef.current ? popperInstanceRef.current.update : null,
    forceUpdate: popperInstanceRef.current ? popperInstanceRef.current.forceUpdate : null
  };
};
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@automattic+tour-kit@1.1.3_@types+react@17.0.71_@wordpress+data@10.6.0_react@17.0.2__react-do_wpr2orkhsavt4ls5ttip2qkwai/node_modules/@automattic/tour-kit/dist/esm/hooks/use-step-tracking.js
/**
 * External Dependencies
 */

const useStepTracking = (currentStepIndex, onStepViewOnce) => {
    const [stepsViewed, setStepsViewed] = (0,react.useState)([]);
    (0,react.useEffect)(() => {
        if (stepsViewed.includes(currentStepIndex)) {
            return;
        }
        setStepsViewed((prev) => [...prev, currentStepIndex]);
        onStepViewOnce?.(currentStepIndex);
    }, [currentStepIndex, onStepViewOnce, stepsViewed]);
};
/* harmony default export */ const use_step_tracking = (useStepTracking);
//# sourceMappingURL=use-step-tracking.js.map
// EXTERNAL MODULE: ../../node_modules/.pnpm/debug@4.3.4_supports-color@8.1.1/node_modules/debug/src/browser.js
var browser = __webpack_require__("../../node_modules/.pnpm/debug@4.3.4_supports-color@8.1.1/node_modules/debug/src/browser.js");
var browser_default = /*#__PURE__*/__webpack_require__.n(browser);
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@automattic+tour-kit@1.1.3_@types+react@17.0.71_@wordpress+data@10.6.0_react@17.0.2__react-do_wpr2orkhsavt4ls5ttip2qkwai/node_modules/@automattic/tour-kit/dist/esm/utils/index.js

/**
 * Helper to convert CSV of `classes` to an array.
 * @param classes String or array of classes to format.
 * @returns Array of classes
 */
function classParser(classes) {
    if (classes?.length) {
        return classes.toString().split(',');
    }
    return null;
}
const debug = browser_default()('tour-kit');
//# sourceMappingURL=index.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@automattic+tour-kit@1.1.3_@types+react@17.0.71_@wordpress+data@10.6.0_react@17.0.2__react-do_wpr2orkhsavt4ls5ttip2qkwai/node_modules/@automattic/tour-kit/dist/esm/utils/live-resize-modifier.js

/**
 * Function that returns a Popper modifier that observes the specified root element as well as
 * reference element for any changes. The reason for being a currying function is so that
 * we can customise the root element selector, otherwise observing at a higher than necessary
 * level might cause unnecessary performance penalties.
 *
 * The Popper modifier queues an asynchronous update on the Popper instance whenever either of the
 * Observers trigger its callback.
 * @returns custom Popper modifier
 */
const liveResizeModifier = ({ rootElementSelector, mutation = false, resize = false } = {
    mutation: false,
    resize: false,
}) => ({
    name: 'liveResizeModifier',
    enabled: true,
    phase: 'main',
    fn: () => {
        return;
    },
    effect: (arg0) => {
        try {
            const { state, instance } = arg0; // augment types here because we are mutating the properties on the argument that is passed in
            const ObserversProp = Symbol(); // use a symbol here so that we don't clash with multiple poppers using this modifier on the same reference node
            const { reference } = state.elements;
            reference[ObserversProp] = {
                resizeObserver: new ResizeObserver(() => {
                    instance.update();
                }),
                mutationObserver: new MutationObserver(() => {
                    instance.update();
                }),
            };
            if (resize) {
                if (reference instanceof Element) {
                    reference[ObserversProp].resizeObserver.observe(reference);
                }
                else {
                    debug('Error: ResizeObserver does not work with virtual elements, Tour Kit will not resize automatically if the size of the referenced element changes.');
                }
            }
            if (mutation) {
                const rootElementNode = document.querySelector(rootElementSelector || '#wpwrap');
                if (rootElementNode instanceof Element) {
                    reference[ObserversProp].mutationObserver.observe(rootElementNode, {
                        attributes: true,
                        characterData: true,
                        childList: true,
                        subtree: true,
                    });
                }
                else {
                    debug(`Error: ${rootElementSelector} selector did not find a valid DOM element, Tour Kit will not update automatically if the DOM layout changes.`);
                }
            }
            return () => {
                reference[ObserversProp].resizeObserver.disconnect();
                reference[ObserversProp].mutationObserver.disconnect();
                delete reference[ObserversProp];
            };
        }
        catch (error) {
            debug('Error: Tour Kit live resize modifier failed unexpectedly:', error);
        }
    },
});
//# sourceMappingURL=live-resize-modifier.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@automattic+tour-kit@1.1.3_@types+react@17.0.71_@wordpress+data@10.6.0_react@17.0.2__react-do_wpr2orkhsavt4ls5ttip2qkwai/node_modules/@automattic/tour-kit/dist/esm/hooks/use-focus-handler.js
/**
 * External Dependencies
 */

/**
 * A hook that returns true/false if ref node receives focus by either tabbing or clicking into any of its children.
 * @param ref React.MutableRefObject< null | HTMLElement >
 */
const useFocusHandler = (ref) => {
    const [hasFocus, setHasFocus] = (0,react.useState)(false);
    const handleFocus = (0,react.useCallback)(() => {
        if (document.hasFocus() && ref.current?.contains(document.activeElement)) {
            setHasFocus(true);
        }
        else {
            setHasFocus(false);
        }
    }, [ref]);
    const handleMousedown = (0,react.useCallback)((event) => {
        if (ref.current?.contains(event.target)) {
            setHasFocus(true);
        }
        else {
            setHasFocus(false);
        }
    }, [ref]);
    const handleKeyup = (0,react.useCallback)((event) => {
        if (event.key === 'Tab') {
            if (ref.current?.contains(event.target)) {
                setHasFocus(true);
            }
            else {
                setHasFocus(false);
            }
        }
    }, [ref]);
    (0,react.useEffect)(() => {
        document.addEventListener('focusin', handleFocus);
        document.addEventListener('mousedown', handleMousedown);
        document.addEventListener('keyup', handleKeyup);
        return () => {
            document.removeEventListener('focusin', handleFocus);
            document.removeEventListener('mousedown', handleMousedown);
            document.removeEventListener('keyup', handleKeyup);
        };
    }, [ref, handleFocus, handleKeyup, handleMousedown]);
    return hasFocus;
};
/* harmony default export */ const use_focus_handler = (useFocusHandler);
//# sourceMappingURL=use-focus-handler.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+dom@3.57.0/node_modules/@wordpress/dom/build-module/focusable.js
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

/**
 * Returns a CSS selector used to query for focusable elements.
 *
 * @param {boolean} sequential If set, only query elements that are sequentially
 *                             focusable. Non-interactive elements with a
 *                             negative `tabindex` are focusable but not
 *                             sequentially focusable.
 *                             https://html.spec.whatwg.org/multipage/interaction.html#the-tabindex-attribute
 *
 * @return {string} CSS selector.
 */
function buildSelector(sequential) {
  return [sequential ? '[tabindex]:not([tabindex^="-"])' : '[tabindex]', 'a[href]', 'button:not([disabled])', 'input:not([type="hidden"]):not([disabled])', 'select:not([disabled])', 'textarea:not([disabled])', 'iframe:not([tabindex^="-"])', 'object', 'embed', 'area[href]', '[contenteditable]:not([contenteditable=false])'].join(',');
}

/**
 * Returns true if the specified element is visible (i.e. neither display: none
 * nor visibility: hidden).
 *
 * @param {HTMLElement} element DOM element to test.
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
 * @param {HTMLAreaElement} element DOM area element to test.
 *
 * @return {boolean} Whether area element is valid for focus.
 */
function isValidFocusableArea(element) {
  /** @type {HTMLMapElement | null} */
  const map = element.closest('map[name]');
  if (!map) {
    return false;
  }

  /** @type {HTMLImageElement | null} */
  const img = element.ownerDocument.querySelector('img[usemap="#' + map.name + '"]');
  return !!img && isVisible(img);
}

/**
 * Returns all focusable elements within a given context.
 *
 * @param {Element} context              Element in which to search.
 * @param {Object}  options
 * @param {boolean} [options.sequential] If set, only return elements that are
 *                                       sequentially focusable.
 *                                       Non-interactive elements with a
 *                                       negative `tabindex` are focusable but
 *                                       not sequentially focusable.
 *                                       https://html.spec.whatwg.org/multipage/interaction.html#the-tabindex-attribute
 *
 * @return {HTMLElement[]} Focusable elements.
 */
function find(context, {
  sequential = false
} = {}) {
  /** @type {NodeListOf<HTMLElement>} */
  const elements = context.querySelectorAll(buildSelector(sequential));
  return Array.from(elements).filter(element => {
    if (!isVisible(element)) {
      return false;
    }
    const {
      nodeName
    } = element;
    if ('AREA' === nodeName) {
      return isValidFocusableArea( /** @type {HTMLAreaElement} */element);
    }
    return true;
  });
}
//# sourceMappingURL=focusable.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+dom@3.57.0/node_modules/@wordpress/dom/build-module/tabbable.js
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
 * @return {number} Tab index of element (default 0).
 */
function getTabIndex(element) {
  const tabIndex = element.getAttribute('tabindex');
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

/** @typedef {HTMLElement & { type?: string, checked?: boolean, name?: string }} MaybeHTMLInputElement */

/**
 * Returns a stateful reducer function which constructs a filtered array of
 * tabbable elements, where at most one radio input is selected for a given
 * name, giving priority to checked input, falling back to the first
 * encountered.
 *
 * @return {(acc: MaybeHTMLInputElement[], el: MaybeHTMLInputElement) => MaybeHTMLInputElement[]} Radio group collapse reducer.
 */
function createStatefulCollapseRadioGroup() {
  /** @type {Record<string, MaybeHTMLInputElement>} */
  const CHOSEN_RADIO_BY_NAME = {};
  return function collapseRadioGroup( /** @type {MaybeHTMLInputElement[]} */result, /** @type {MaybeHTMLInputElement} */element) {
    const {
      nodeName,
      type,
      checked,
      name
    } = element;

    // For all non-radio tabbables, construct to array by concatenating.
    if (nodeName !== 'INPUT' || type !== 'radio' || !name) {
      return result.concat(element);
    }
    const hasChosen = CHOSEN_RADIO_BY_NAME.hasOwnProperty(name);

    // Omit by skipping concatenation if the radio element is not chosen.
    const isChosen = checked || !hasChosen;
    if (!isChosen) {
      return result;
    }

    // At this point, if there had been a chosen element, the current
    // element is checked and should take priority. Retroactively remove
    // the element which had previously been considered the chosen one.
    if (hasChosen) {
      const hadChosenElement = CHOSEN_RADIO_BY_NAME[name];
      result = result.filter(e => e !== hadChosenElement);
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
 * @param {HTMLElement} element Element.
 * @param {number}      index   Array index of element.
 *
 * @return {{ element: HTMLElement, index: number }} Mapped object with element, index.
 */
function mapElementToObjectTabbable(element, index) {
  return {
    element,
    index
  };
}

/**
 * An array map callback, returning an element of the given mapped object's
 * element value.
 *
 * @param {{ element: HTMLElement }} object Mapped object with element.
 *
 * @return {HTMLElement} Mapped object element.
 */
function mapObjectTabbableToElement(object) {
  return object.element;
}

/**
 * A sort comparator function used in comparing two objects of mapped elements.
 *
 * @see mapElementToObjectTabbable
 *
 * @param {{ element: HTMLElement, index: number }} a First object to compare.
 * @param {{ element: HTMLElement, index: number }} b Second object to compare.
 *
 * @return {number} Comparator result.
 */
function compareObjectTabbables(a, b) {
  const aTabIndex = getTabIndex(a.element);
  const bTabIndex = getTabIndex(b.element);
  if (aTabIndex === bTabIndex) {
    return a.index - b.index;
  }
  return aTabIndex - bTabIndex;
}

/**
 * Givin focusable elements, filters out tabbable element.
 *
 * @param {HTMLElement[]} focusables Focusable elements to filter.
 *
 * @return {HTMLElement[]} Tabbable elements.
 */
function filterTabbable(focusables) {
  return focusables.filter(isTabbableIndex).map(mapElementToObjectTabbable).sort(compareObjectTabbables).map(mapObjectTabbableToElement).reduce(createStatefulCollapseRadioGroup(), []);
}

/**
 * @param {Element} context
 * @return {HTMLElement[]} Tabbable elements within the context.
 */
function tabbable_find(context) {
  return filterTabbable(find(context));
}

/**
 * Given a focusable element, find the preceding tabbable element.
 *
 * @param {Element} element The focusable element before which to look. Defaults
 *                          to the active element.
 *
 * @return {HTMLElement|undefined} Preceding tabbable element.
 */
function findPrevious(element) {
  return filterTabbable(find(element.ownerDocument.body)).reverse().find(focusable =>
  // eslint-disable-next-line no-bitwise
  element.compareDocumentPosition(focusable) & element.DOCUMENT_POSITION_PRECEDING);
}

/**
 * Given a focusable element, find the next tabbable element.
 *
 * @param {Element} element The focusable element after which to look. Defaults
 *                          to the active element.
 *
 * @return {HTMLElement|undefined} Next tabbable element.
 */
function findNext(element) {
  return filterTabbable(find(element.ownerDocument.body)).find(focusable =>
  // eslint-disable-next-line no-bitwise
  element.compareDocumentPosition(focusable) & element.DOCUMENT_POSITION_FOLLOWING);
}
//# sourceMappingURL=tabbable.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+dom@3.57.0/node_modules/@wordpress/dom/build-module/index.js
/**
 * Internal dependencies
 */



/**
 * Object grouping `focusable` and `tabbable` utils
 * under the keys with the same name.
 */
const build_module_focus = {
  focusable: focusable_namespaceObject,
  tabbable: tabbable_namespaceObject
};



//# sourceMappingURL=index.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@automattic+tour-kit@1.1.3_@types+react@17.0.71_@wordpress+data@10.6.0_react@17.0.2__react-do_wpr2orkhsavt4ls5ttip2qkwai/node_modules/@automattic/tour-kit/dist/esm/hooks/use-focus-trap.js
/**
 * External Dependencies
 */


/**
 * A hook that constraints tabbing/focus on focuable elements in the given element ref.
 * @param ref React.MutableRefObject< null | HTMLElement >
 */
const useFocusTrap = (ref) => {
    const [firstFocusableElement, setFirstFocusableElement] = (0,react.useState)();
    const [lastFocusableElement, setLastFocusableElement] = (0,react.useState)();
    const handleTrapFocus = (0,react.useCallback)((event) => {
        let handled = false;
        if (event.key === 'Tab') {
            if (event.shiftKey) {
                // Shift + Tab
                if (document.activeElement === firstFocusableElement) {
                    lastFocusableElement?.focus();
                    handled = true;
                }
            }
            else if (document.activeElement === lastFocusableElement) {
                // Tab
                firstFocusableElement?.focus();
                handled = true;
            }
        }
        if (handled) {
            event.preventDefault();
            event.stopPropagation();
        }
    }, [firstFocusableElement, lastFocusableElement]);
    (0,react.useEffect)(() => {
        const focusableElements = ref.current ? build_module_focus.focusable.find(ref.current) : [];
        if (focusableElements && focusableElements.length) {
            setFirstFocusableElement(focusableElements[0]);
            setLastFocusableElement(focusableElements[focusableElements.length - 1]);
        }
        document.addEventListener('keydown', handleTrapFocus);
        return () => {
            document.removeEventListener('keydown', handleTrapFocus);
        };
    }, [ref, handleTrapFocus]);
};
/* harmony default export */ const use_focus_trap = (useFocusTrap);
//# sourceMappingURL=use-focus-trap.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@automattic+tour-kit@1.1.3_@types+react@17.0.71_@wordpress+data@10.6.0_react@17.0.2__react-do_wpr2orkhsavt4ls5ttip2qkwai/node_modules/@automattic/tour-kit/dist/esm/hooks/use-keydown-handler.js
/* eslint-disable jsdoc/require-param */
/**
 * External Dependencies
 */

/**
 * A hook the applies the respective callbacks in response to keydown events.
 */
const useKeydownHandler = ({ onEscape, onArrowRight, onArrowLeft }) => {
    const handleKeydown = (0,react.useCallback)((event) => {
        let handled = false;
        switch (event.key) {
            case 'Escape':
                onEscape && (onEscape(), (handled = true));
                break;
            case 'ArrowRight':
                onArrowRight && (onArrowRight(), (handled = true));
                break;
            case 'ArrowLeft':
                onArrowLeft && (onArrowLeft(), (handled = true));
                break;
            default:
                break;
        }
        if (handled) {
            event.preventDefault();
            event.stopPropagation();
        }
    }, [onEscape, onArrowRight, onArrowLeft]);
    (0,react.useEffect)(() => {
        document.addEventListener('keydown', handleKeydown);
        return () => {
            document.removeEventListener('keydown', handleKeydown);
        };
    }, [handleKeydown]);
};
/* harmony default export */ const use_keydown_handler = (useKeydownHandler);
//# sourceMappingURL=use-keydown-handler.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@automattic+tour-kit@1.1.3_@types+react@17.0.71_@wordpress+data@10.6.0_react@17.0.2__react-do_wpr2orkhsavt4ls5ttip2qkwai/node_modules/@automattic/tour-kit/dist/esm/components/keyboard-navigation.js

/**
 * Internal dependencies
 */



const KeyboardNavigation = ({ onMinimize, onDismiss, onNextStepProgression, onPreviousStepProgression, tourContainerRef, isMinimized, }) => {
    function ExpandedTourNav() {
        use_keydown_handler({
            onEscape: onMinimize,
            onArrowRight: onNextStepProgression,
            onArrowLeft: onPreviousStepProgression,
        });
        use_focus_trap(tourContainerRef);
        return null;
    }
    function MinimizedTourNav() {
        use_keydown_handler({ onEscape: onDismiss('esc-key-minimized') });
        return null;
    }
    const isTourFocused = use_focus_handler(tourContainerRef);
    if (!isTourFocused) {
        return null;
    }
    return isMinimized ? (0,jsx_runtime.jsx)(MinimizedTourNav, {}) : (0,jsx_runtime.jsx)(ExpandedTourNav, {});
};
/* harmony default export */ const keyboard_navigation = (KeyboardNavigation);
//# sourceMappingURL=keyboard-navigation.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@automattic+tour-kit@1.1.3_@types+react@17.0.71_@wordpress+data@10.6.0_react@17.0.2__react-do_wpr2orkhsavt4ls5ttip2qkwai/node_modules/@automattic/tour-kit/dist/esm/components/tour-kit-minimized.js

const TourKitMinimized = ({ config, steps, currentStepIndex, onMaximize, onDismiss, }) => {
    return ((0,jsx_runtime.jsx)("div", { className: "tour-kit-minimized", children: (0,jsx_runtime.jsx)(config.renderers.tourMinimized, { steps: steps, currentStepIndex: currentStepIndex, onMaximize: onMaximize, onDismiss: onDismiss }) }));
};
/* harmony default export */ const tour_kit_minimized = (TourKitMinimized);
//# sourceMappingURL=tour-kit-minimized.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@automattic+tour-kit@1.1.3_@types+react@17.0.71_@wordpress+data@10.6.0_react@17.0.2__react-do_wpr2orkhsavt4ls5ttip2qkwai/node_modules/@automattic/tour-kit/dist/esm/components/tour-kit-overlay.js

/**
 * External Dependencies
 */

const TourKitOverlay = ({ visible }) => {
    return ((0,jsx_runtime.jsx)("div", { className: classnames_default()('tour-kit-overlay', {
            'is-visible': visible,
        }) }));
};
/* harmony default export */ const tour_kit_overlay = (TourKitOverlay);
//# sourceMappingURL=tour-kit-overlay.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@automattic+tour-kit@1.1.3_@types+react@17.0.71_@wordpress+data@10.6.0_react@17.0.2__react-do_wpr2orkhsavt4ls5ttip2qkwai/node_modules/@automattic/tour-kit/dist/esm/components/tour-kit-spotlight-interactivity.js


const SpotlightInteractivity = ({ enabled = false, rootElementSelector = '#wpwrap', }) => {
    if (!enabled) {
        return null;
    }
    return ((0,jsx_runtime.jsx)("style", { children: `
    .${SPOTLIT_ELEMENT_CLASS}, .${SPOTLIT_ELEMENT_CLASS} * {
        pointer-events: auto;
    }
    .tour-kit-frame__container button {
        pointer-events: auto;
    }
    .tour-kit-spotlight, .tour-kit-overlay {
        pointer-events: none;
    }
    ${rootElementSelector} :not(.${SPOTLIT_ELEMENT_CLASS}, .${SPOTLIT_ELEMENT_CLASS} *) {
        pointer-events: none;
    }
    ` }));
};
//# sourceMappingURL=tour-kit-spotlight-interactivity.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@automattic+tour-kit@1.1.3_@types+react@17.0.71_@wordpress+data@10.6.0_react@17.0.2__react-do_wpr2orkhsavt4ls5ttip2qkwai/node_modules/@automattic/tour-kit/dist/esm/components/tour-kit-spotlight.js







const SPOTLIT_ELEMENT_CLASS = 'wp-tour-kit-spotlit';
const TourKitSpotlight = ({ referenceElement, styles, interactivity, liveResize, }) => {
    const [popperElement, sePopperElement] = (0,react.useState)(null);
    const referenceRect = referenceElement?.getBoundingClientRect();
    const modifiers = [
        {
            name: 'flip',
            enabled: false,
        },
        {
            name: 'preventOverflow',
            options: {
                mainAxis: false, // true by default
            },
        },
        (0,react.useMemo)(() => ({
            name: 'offset',
            options: {
                offset: ({ placement, reference, popper, }) => {
                    if (placement === 'bottom') {
                        return [0, -(reference.height + (popper.height - reference.height) / 2)];
                    }
                    return [0, 0];
                },
            },
        }), []),
        // useMemo because https://popper.js.org/react-popper/v2/faq/#why-i-get-render-loop-whenever-i-put-a-function-inside-the-popper-configuration
        (0,react.useMemo)(() => {
            return liveResizeModifier(liveResize);
        }, [liveResize]),
    ];
    const { styles: popperStyles, attributes: popperAttributes } = usePopper(referenceElement, popperElement, {
        strategy: 'fixed',
        placement: 'bottom',
        modifiers,
    });
    const clipDimensions = referenceRect
        ? {
            width: `${referenceRect.width}px`,
            height: `${referenceRect.height}px`,
        }
        : null;
    const clipRepositionProps = referenceElement
        ? {
            style: {
                ...(clipDimensions && clipDimensions),
                ...popperStyles?.popper,
                ...(styles && styles),
            },
            ...popperAttributes?.popper,
        }
        : null;
    /**
     * Add a .wp-spotlit class to the referenced element so that we can
     * apply CSS styles to it, for whatever purposes such as interactivity
     */
    (0,react.useEffect)(() => {
        referenceElement?.classList.add(SPOTLIT_ELEMENT_CLASS);
        return () => {
            referenceElement?.classList.remove(SPOTLIT_ELEMENT_CLASS);
        };
    }, [referenceElement]);
    return ((0,jsx_runtime.jsxs)(jsx_runtime.Fragment, { children: [(0,jsx_runtime.jsx)(SpotlightInteractivity, { ...interactivity }), (0,jsx_runtime.jsx)(tour_kit_overlay, { visible: !clipRepositionProps }), (0,jsx_runtime.jsx)("div", { className: classnames_default()('tour-kit-spotlight', {
                    'is-visible': !!clipRepositionProps,
                }), ref: sePopperElement, ...clipRepositionProps })] }));
};
/* harmony default export */ const tour_kit_spotlight = (TourKitSpotlight);
//# sourceMappingURL=tour-kit-spotlight.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@automattic+tour-kit@1.1.3_@types+react@17.0.71_@wordpress+data@10.6.0_react@17.0.2__react-do_wpr2orkhsavt4ls5ttip2qkwai/node_modules/@automattic/tour-kit/dist/esm/components/tour-kit-step.js

/**
 * External Dependencies
 */


/**
 * Internal Dependencies
 */

const TourKitStep = ({ config, steps, currentStepIndex, onMinimize, onDismiss, onNextStep, onPreviousStep, setInitialFocusedElement, onGoToStep, }) => {
    const isMobile = useMobileBreakpoint();
    const classes = classnames_default()('tour-kit-step', `is-step-${currentStepIndex}`, classParser(config.steps[currentStepIndex].options?.classNames?.[isMobile ? 'mobile' : 'desktop']));
    return ((0,jsx_runtime.jsx)("div", { className: classes, children: (0,jsx_runtime.jsx)(config.renderers.tourStep, { steps: steps, currentStepIndex: currentStepIndex, onDismiss: onDismiss, onNextStep: onNextStep, onPreviousStep: onPreviousStep, onMinimize: onMinimize, setInitialFocusedElement: setInitialFocusedElement, onGoToStep: onGoToStep }) }));
};
/* harmony default export */ const tour_kit_step = (TourKitStep);
//# sourceMappingURL=tour-kit-step.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@automattic+tour-kit@1.1.3_@types+react@17.0.71_@wordpress+data@10.6.0_react@17.0.2__react-do_wpr2orkhsavt4ls5ttip2qkwai/node_modules/@automattic/tour-kit/dist/esm/components/tour-kit-frame.js

/**
 * External Dependencies
 */




/**
 * Internal Dependencies
 */








const handleCallback = (currentStepIndex, callback) => {
    typeof callback === 'function' && callback(currentStepIndex);
};
const TourKitFrame = ({ config }) => {
    const [currentStepIndex, setCurrentStepIndex] = (0,react.useState)(0);
    const [initialFocusedElement, setInitialFocusedElement] = (0,react.useState)(null);
    const [isMinimized, setIsMinimized] = (0,react.useState)(config.isMinimized ?? false);
    const [popperElement, setPopperElement] = (0,react.useState)(null);
    const [tourReady, setTourReady] = (0,react.useState)(false);
    const tourContainerRef = (0,react.useRef)(null);
    const isMobile = useMobileBreakpoint();
    const lastStepIndex = config.steps.length - 1;
    const referenceElements = config.steps[currentStepIndex].referenceElements;
    const referenceElementSelector = referenceElements?.[isMobile ? 'mobile' : 'desktop'] || referenceElements?.desktop;
    const referenceElement = referenceElementSelector
        ? document.querySelector(referenceElementSelector)
        : null;
    (0,react.useEffect)(() => {
        if (config.isMinimized) {
            setIsMinimized(true);
        }
    }, [config.isMinimized]);
    const showArrowIndicator = (0,react.useCallback)(() => {
        if (config.options?.effects?.arrowIndicator === false) {
            return false;
        }
        return !!(referenceElement && !isMinimized && tourReady);
    }, [config.options?.effects?.arrowIndicator, isMinimized, referenceElement, tourReady]);
    const showSpotlight = (0,react.useCallback)(() => {
        if (!config.options?.effects?.spotlight) {
            return false;
        }
        return !isMinimized;
    }, [config.options?.effects?.spotlight, isMinimized]);
    const showOverlay = (0,react.useCallback)(() => {
        if (showSpotlight() || !config.options?.effects?.overlay) {
            return false;
        }
        return !isMinimized;
    }, [config.options?.effects?.overlay, isMinimized, showSpotlight]);
    const handleDismiss = (0,react.useCallback)((source) => {
        return () => {
            config.closeHandler(config.steps, currentStepIndex, source);
        };
    }, [config, currentStepIndex]);
    const handleNextStepProgression = (0,react.useCallback)(() => {
        let newStepIndex = currentStepIndex;
        if (lastStepIndex > currentStepIndex) {
            newStepIndex = currentStepIndex + 1;
            setCurrentStepIndex(newStepIndex);
        }
        handleCallback(newStepIndex, config.options?.callbacks?.onNextStep);
    }, [config.options?.callbacks?.onNextStep, currentStepIndex, lastStepIndex]);
    const handlePreviousStepProgression = (0,react.useCallback)(() => {
        let newStepIndex = currentStepIndex;
        if (currentStepIndex > 0) {
            newStepIndex = currentStepIndex - 1;
            setCurrentStepIndex(newStepIndex);
        }
        handleCallback(newStepIndex, config.options?.callbacks?.onPreviousStep);
    }, [config.options?.callbacks?.onPreviousStep, currentStepIndex]);
    const handleGoToStep = (0,react.useCallback)((stepIndex) => {
        setCurrentStepIndex(stepIndex);
        handleCallback(stepIndex, config.options?.callbacks?.onGoToStep);
    }, [config.options?.callbacks?.onGoToStep, currentStepIndex]);
    const handleMinimize = (0,react.useCallback)(() => {
        setIsMinimized(true);
        handleCallback(currentStepIndex, config.options?.callbacks?.onMinimize);
    }, [config.options?.callbacks?.onMinimize, currentStepIndex]);
    const handleMaximize = (0,react.useCallback)(() => {
        setIsMinimized(false);
        handleCallback(currentStepIndex, config.options?.callbacks?.onMaximize);
    }, [config.options?.callbacks?.onMaximize, currentStepIndex]);
    const { styles: popperStyles, attributes: popperAttributes, update: popperUpdate, } = usePopper(referenceElement, popperElement, {
        strategy: 'fixed',
        placement: config?.placement ?? 'bottom',
        modifiers: [
            {
                name: 'preventOverflow',
                options: {
                    rootBoundary: 'document',
                    padding: 16, // same as the left/margin of the tour frame
                },
            },
            {
                name: 'arrow',
                options: {
                    padding: 12,
                },
            },
            {
                name: 'offset',
                options: {
                    offset: [0, showArrowIndicator() ? 12 : 10],
                },
            },
            {
                name: 'flip',
                options: {
                    fallbackPlacements: ['top', 'left', 'right'],
                },
            },
            (0,react.useMemo)(() => liveResizeModifier(config.options?.effects?.liveResize), [config.options?.effects?.liveResize]),
            ...(config.options?.popperModifiers || []),
        ],
    });
    const stepRepositionProps = !isMinimized && referenceElement && tourReady
        ? {
            style: popperStyles?.popper,
            ...popperAttributes?.popper,
        }
        : null;
    const arrowPositionProps = !isMinimized && referenceElement && tourReady
        ? {
            style: popperStyles?.arrow,
            ...popperAttributes?.arrow,
        }
        : null;
    /*
     * Focus first interactive element when step renders.
     */
    (0,react.useEffect)(() => {
        setTimeout(() => initialFocusedElement?.focus());
    }, [initialFocusedElement]);
    /*
     * Fixes issue with Popper misplacing the instance on mount
     * See: https://stackoverflow.com/questions/65585859/react-popper-incorrect-position-on-mount
     */
    (0,react.useEffect)(() => {
        // If no reference element to position step near
        if (!referenceElement) {
            setTourReady(true);
            return;
        }
        setTourReady(false);
        if (popperUpdate) {
            popperUpdate()
                .then(() => setTourReady(true))
                .catch(() => setTourReady(true));
        }
    }, [popperUpdate, referenceElement]);
    (0,react.useEffect)(() => {
        if (referenceElement && config.options?.effects?.autoScroll) {
            referenceElement.scrollIntoView(config.options.effects.autoScroll);
        }
    }, [config.options?.effects?.autoScroll, referenceElement]);
    const classes = classnames_default()('tour-kit-frame', isMobile ? 'is-mobile' : 'is-desktop', { 'is-visible': tourReady }, classParser(config.options?.classNames));
    use_step_tracking(currentStepIndex, config.options?.callbacks?.onStepViewOnce);
    (0,react.useEffect)(() => {
        if (config.options?.callbacks?.onStepView) {
            handleCallback(currentStepIndex, config.options?.callbacks?.onStepView);
        }
    }, [config.options?.callbacks?.onStepView, currentStepIndex]);
    return ((0,jsx_runtime.jsxs)(jsx_runtime.Fragment, { children: [(0,jsx_runtime.jsx)(keyboard_navigation, { onMinimize: handleMinimize, onDismiss: handleDismiss, onNextStepProgression: handleNextStepProgression, onPreviousStepProgression: handlePreviousStepProgression, tourContainerRef: tourContainerRef, isMinimized: isMinimized }), (0,jsx_runtime.jsxs)("div", { className: classes, ref: tourContainerRef, children: [showOverlay() && (0,jsx_runtime.jsx)(tour_kit_overlay, { visible: true }), showSpotlight() && ((0,jsx_runtime.jsx)(tour_kit_spotlight, { referenceElement: referenceElement, liveResize: config.options?.effects?.liveResize || {}, ...(config.options?.effects?.spotlight || {}) })), (0,jsx_runtime.jsxs)("div", { className: "tour-kit-frame__container", ref: setPopperElement, ...stepRepositionProps, children: [showArrowIndicator() && ((0,jsx_runtime.jsx)("div", { className: "tour-kit-frame__arrow", "data-popper-arrow": true, ...arrowPositionProps })), !isMinimized ? ((0,jsx_runtime.jsx)(tour_kit_step, { config: config, steps: config.steps, currentStepIndex: currentStepIndex, onMinimize: handleMinimize, onDismiss: handleDismiss, onNextStep: handleNextStepProgression, onPreviousStep: handlePreviousStepProgression, onGoToStep: handleGoToStep, setInitialFocusedElement: setInitialFocusedElement })) : ((0,jsx_runtime.jsx)(tour_kit_minimized, { config: config, steps: config.steps, currentStepIndex: currentStepIndex, onMaximize: handleMaximize, onDismiss: handleDismiss }))] })] })] }));
};
/* harmony default export */ const tour_kit_frame = (TourKitFrame);
//# sourceMappingURL=tour-kit-frame.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@automattic+tour-kit@1.1.3_@types+react@17.0.71_@wordpress+data@10.6.0_react@17.0.2__react-do_wpr2orkhsavt4ls5ttip2qkwai/node_modules/@automattic/tour-kit/dist/esm/styles.scss
// extracted by mini-css-extract-plugin

;// CONCATENATED MODULE: ../../node_modules/.pnpm/@automattic+tour-kit@1.1.3_@types+react@17.0.71_@wordpress+data@10.6.0_react@17.0.2__react-do_wpr2orkhsavt4ls5ttip2qkwai/node_modules/@automattic/tour-kit/dist/esm/components/tour-kit.js

// import { TourKitContextProvider } from './tour-kit-context';





const TourKit = ({ config, __temp__className }) => {
    const portalParent = (0,react.useRef)(document.createElement('div')).current;
    (0,react.useEffect)(() => {
        const classes = ['tour-kit', ...(__temp__className ? [__temp__className] : [])];
        portalParent.classList.add(...classes);
        const portalParentElement = config.options?.portalParentElement || document.body;
        portalParentElement.appendChild(portalParent);
        return () => {
            portalParentElement.removeChild(portalParent);
        };
    }, [__temp__className, portalParent, config.options?.portalParentElement]);
    return ((0,jsx_runtime.jsx)(error_boundary, { children: (0,jsx_runtime.jsx)(tour_kit_context, { config: config, children: (0,jsx_runtime.jsx)("div", { children: (0,react_dom.createPortal)((0,jsx_runtime.jsx)(tour_kit_frame, { config: config }), portalParent) }) }) }));
};
/* harmony default export */ const tour_kit = (TourKit);
//# sourceMappingURL=tour-kit.js.map

/***/ }),

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

/***/ "../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/defineProperty.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (/* binding */ _defineProperty)
/* harmony export */ });
/* harmony import */ var _toPropertyKey_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/toPropertyKey.js");

function _defineProperty(obj, key, value) {
  key = (0,_toPropertyKey_js__WEBPACK_IMPORTED_MODULE_0__/* ["default"] */ .A)(key);
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

/***/ "../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/toPropertyKey.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";

// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  A: () => (/* binding */ _toPropertyKey)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/typeof.js
var esm_typeof = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/typeof.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/toPrimitive.js

function _toPrimitive(input, hint) {
  if ((0,esm_typeof/* default */.A)(input) !== "object" || input === null) return input;
  var prim = input[Symbol.toPrimitive];
  if (prim !== undefined) {
    var res = prim.call(input, hint || "default");
    if ((0,esm_typeof/* default */.A)(res) !== "object") return res;
    throw new TypeError("@@toPrimitive must return a primitive value.");
  }
  return (hint === "string" ? String : Number)(input);
}
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/toPropertyKey.js


function _toPropertyKey(arg) {
  var key = _toPrimitive(arg, "string");
  return (0,esm_typeof/* default */.A)(key) === "symbol" ? key : String(key);
}

/***/ }),

/***/ "../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/typeof.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (/* binding */ _typeof)
/* harmony export */ });
function _typeof(o) {
  "@babel/helpers - typeof";

  return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) {
    return typeof o;
  } : function (o) {
    return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o;
  }, _typeof(o);
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

/***/ "../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/card/card-footer/component.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";

// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  A: () => (/* binding */ card_footer_component)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/extends.js
var esm_extends = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/extends.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/ui/context/context-connect.js
var context_connect = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/ui/context/context-connect.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/flex/flex/component.js + 2 modules
var component = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/flex/flex/component.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/ui/context/use-context-system.js + 1 modules
var use_context_system = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/ui/context/use-context-system.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/card/styles.js
var styles = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/card/styles.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/utils/hooks/use-cx.js
var use_cx = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/utils/hooks/use-cx.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/card/card-footer/hook.js
/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */




/**
 * @param {import('../../ui/context').WordPressComponentProps<import('../types').FooterProps, 'div'>} props
 */

function useCardFooter(props) {
  const {
    className,
    justify,
    isBorderless = false,
    isShady = false,
    size = 'medium',
    ...otherProps
  } = (0,use_context_system/* useContextSystem */.A)(props, 'CardFooter');
  const cx = (0,use_cx/* useCx */.l)();
  const classes = (0,react.useMemo)(() => cx(styles/* Footer */.wi, styles/* borderRadius */.Vq, styles/* borderColor */.Cz, styles/* cardPaddings */.L7[size], isBorderless && styles/* borderless */.Gn, isShady && styles/* shady */.QC, // This classname is added for legacy compatibility reasons.
  'components-card__footer', className), [className, cx, isBorderless, isShady, size]);
  return { ...otherProps,
    className: classes,
    justify
  };
}
//# sourceMappingURL=hook.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/card/card-footer/component.js



/**
 * Internal dependencies
 */



/**
 * @param {import('../../ui/context').WordPressComponentProps<import('../types').FooterProps, 'div'>} props
 * @param {import('react').ForwardedRef<any>}                                                         forwardedRef
 */

function CardFooter(props, forwardedRef) {
  const footerProps = useCardFooter(props);
  return (0,react.createElement)(component/* default */.A, (0,esm_extends/* default */.A)({}, footerProps, {
    ref: forwardedRef
  }));
}
/**
 * `CardFooter` renders an optional footer within a `Card`.
 *
 * @example
 * ```jsx
 * import { Card, CardBody, CardFooter } from `@wordpress/components`;
 *
 * <Card>
 * 	<CardBody>...</CardBody>
 * 	<CardFooter>...</CardFooter>
 * </Card>
 * ```
 */


const ConnectedCardFooter = (0,context_connect/* contextConnect */.KZ)(CardFooter, 'CardFooter');
/* harmony default export */ const card_footer_component = (ConnectedCardFooter);
//# sourceMappingURL=component.js.map

/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/card/card-header/component.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";

// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  A: () => (/* binding */ card_header_component)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/extends.js
var esm_extends = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/extends.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/ui/context/context-connect.js
var context_connect = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/ui/context/context-connect.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/flex/flex/component.js + 2 modules
var component = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/flex/flex/component.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/ui/context/use-context-system.js + 1 modules
var use_context_system = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/ui/context/use-context-system.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/card/styles.js
var styles = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/card/styles.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/utils/hooks/use-cx.js
var use_cx = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/utils/hooks/use-cx.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/card/card-header/hook.js
/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */




/**
 * @param {import('../../ui/context').WordPressComponentProps<import('../types').HeaderProps, 'div'>} props
 */

function useCardHeader(props) {
  const {
    className,
    isBorderless = false,
    isShady = false,
    size = 'medium',
    ...otherProps
  } = (0,use_context_system/* useContextSystem */.A)(props, 'CardHeader');
  const cx = (0,use_cx/* useCx */.l)();
  const classes = (0,react.useMemo)(() => cx(styles/* Header */.Y9, styles/* borderRadius */.Vq, styles/* borderColor */.Cz, styles/* cardPaddings */.L7[size], isBorderless && styles/* borderless */.Gn, isShady && styles/* shady */.QC, // This classname is added for legacy compatibility reasons.
  'components-card__header', className), [className, cx, isBorderless, isShady, size]);
  return { ...otherProps,
    className: classes
  };
}
//# sourceMappingURL=hook.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/card/card-header/component.js



/**
 * Internal dependencies
 */



/**
 * @param {import('../../ui/context').WordPressComponentProps<import('../types').HeaderProps, 'div'>} props
 * @param {import('react').ForwardedRef<any>}                                                         forwardedRef
 */

function CardHeader(props, forwardedRef) {
  const headerProps = useCardHeader(props);
  return (0,react.createElement)(component/* default */.A, (0,esm_extends/* default */.A)({}, headerProps, {
    ref: forwardedRef
  }));
}
/**
 * `CardHeader` renders an optional header within a `Card`.
 *
 * @example
 * ```jsx
 * import { Card, CardBody, CardHeader } from `@wordpress/components`;
 *
 * <Card>
 * 	<CardHeader>...</CardHeader>
 * 	<CardBody>...</CardBody>
 * </Card>
 * ```
 */


const ConnectedCardHeader = (0,context_connect/* contextConnect */.KZ)(CardHeader, 'CardHeader');
/* harmony default export */ const card_header_component = (ConnectedCardHeader);
//# sourceMappingURL=component.js.map

/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/ui/utils/space.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   x: () => (/* binding */ space)
/* harmony export */ });
/**
 * A real number or something parsable as a number
 */
const GRID_BASE = '4px';
/**
 * A function that handles numbers, numeric strings, and unit values.
 *
 * When given a number or a numeric string, it will return the grid-based
 * value as a factor of GRID_BASE, defined above.
 *
 * When given a unit value or one of the named CSS values like `auto`,
 * it will simply return the value back.
 *
 * @param  value A number, numeric string, or a unit value.
 */

function space(value) {
  var _window$CSS, _window$CSS$supports;

  if (typeof value === 'undefined') {
    return undefined;
  } // Handle empty strings, if it's the number 0 this still works.


  if (!value) {
    return '0';
  }

  const asInt = typeof value === 'number' ? value : Number(value); // Test if the input has a unit, was NaN, or was one of the named CSS values (like `auto`), in which case just use that value.

  if (typeof window !== 'undefined' && (_window$CSS = window.CSS) !== null && _window$CSS !== void 0 && (_window$CSS$supports = _window$CSS.supports) !== null && _window$CSS$supports !== void 0 && _window$CSS$supports.call(_window$CSS, 'margin', value.toString()) || Number.isNaN(asInt)) {
    return value.toString();
  }

  return `calc(${GRID_BASE} * ${value})`;
}
//# sourceMappingURL=space.js.map

/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/utils/colors-values.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";

// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  lm: () => (/* binding */ COLORS)
});

// UNUSED EXPORTS: ADMIN, ALERT, BASE, BLUE, DARK_GRAY, DARK_OPACITY, DARK_OPACITY_LIGHT, G2, LIGHT_GRAY, LIGHT_OPACITY_LIGHT, UI, default

// EXTERNAL MODULE: ../../node_modules/.pnpm/lodash@4.17.21/node_modules/lodash/lodash.js
var lodash = __webpack_require__("../../node_modules/.pnpm/lodash@4.17.21/node_modules/lodash/lodash.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/colord@2.9.3/node_modules/colord/index.mjs
var colord = __webpack_require__("../../node_modules/.pnpm/colord@2.9.3/node_modules/colord/index.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/colord@2.9.3/node_modules/colord/plugins/names.mjs
var names = __webpack_require__("../../node_modules/.pnpm/colord@2.9.3/node_modules/colord/plugins/names.mjs");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/utils/colors.js
/**
 * External dependencies
 */


(0,colord/* extend */.X$)([names/* default */.A]);
/**
 * Generating a CSS compliant rgba() color value.
 *
 * @param {string} hexValue The hex value to convert to rgba().
 * @param {number} alpha    The alpha value for opacity.
 * @return {string} The converted rgba() color value.
 *
 * @example
 * rgba( '#000000', 0.5 )
 * // rgba(0, 0, 0, 0.5)
 */

function rgba() {
  let hexValue = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : '';
  let alpha = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : 1;
  return (0,colord/* colord */.Mj)(hexValue).alpha(alpha).toRgbString();
}
//# sourceMappingURL=colors.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/utils/colors-values.js
/**
 * External dependencies
 */

/**
 * Internal dependencies
 */


const BASE = {
  black: '#000',
  white: '#fff'
};
/**
 * TODO: Continue to update values as "G2" design evolves.
 *
 * "G2" refers to the movement to advance the interface of the block editor.
 * https://github.com/WordPress/gutenberg/issues/18667
 */

const G2 = {
  blue: {
    medium: {
      focus: '#007cba',
      focusDark: '#fff'
    }
  },
  gray: {
    900: '#1e1e1e',
    700: '#757575',
    // Meets 4.6:1 text contrast against white.
    600: '#949494',
    // Meets 3:1 UI or large text contrast against white.
    400: '#ccc',
    300: '#ddd',
    // Used for most borders.
    200: '#e0e0e0',
    // Used sparingly for light borders.
    100: '#f0f0f0' // Used for light gray backgrounds.

  },
  darkGray: {
    primary: '#1e1e1e',
    heading: '#050505'
  },
  mediumGray: {
    text: '#757575'
  },
  lightGray: {
    ui: '#949494',
    secondary: '#ccc',
    tertiary: '#e7e8e9'
  }
};
const DARK_GRAY = {
  900: '#191e23',
  800: '#23282d',
  700: '#32373c',
  600: '#40464d',
  500: '#555d66',
  // Use this most of the time for dark items.
  400: '#606a73',
  300: '#6c7781',
  // Lightest gray that can be used for AA text contrast.
  200: '#7e8993',
  150: '#8d96a0',
  // Lightest gray that can be used for AA non-text contrast.
  100: '#8f98a1',
  placeholder: rgba(G2.gray[900], 0.62)
};
const DARK_OPACITY = {
  900: rgba('#000510', 0.9),
  800: rgba('#00000a', 0.85),
  700: rgba('#06060b', 0.8),
  600: rgba('#000913', 0.75),
  500: rgba('#0a1829', 0.7),
  400: rgba('#0a1829', 0.65),
  300: rgba('#0e1c2e', 0.62),
  200: rgba('#162435', 0.55),
  100: rgba('#223443', 0.5),
  backgroundFill: rgba(DARK_GRAY[700], 0.7)
};
const DARK_OPACITY_LIGHT = {
  900: rgba('#304455', 0.45),
  800: rgba('#425863', 0.4),
  700: rgba('#667886', 0.35),
  600: rgba('#7b86a2', 0.3),
  500: rgba('#9197a2', 0.25),
  400: rgba('#95959c', 0.2),
  300: rgba('#829493', 0.15),
  200: rgba('#8b8b96', 0.1),
  100: rgba('#747474', 0.05)
};
const LIGHT_GRAY = {
  900: '#a2aab2',
  800: '#b5bcc2',
  700: '#ccd0d4',
  600: '#d7dade',
  500: '#e2e4e7',
  // Good for "grayed" items and borders.
  400: '#e8eaeb',
  // Good for "readonly" input fields and special text selection.
  300: '#edeff0',
  200: '#f3f4f5',
  100: '#f8f9f9',
  placeholder: rgba(BASE.white, 0.65)
};
const LIGHT_OPACITY_LIGHT = {
  900: rgba(BASE.white, 0.5),
  800: rgba(BASE.white, 0.45),
  700: rgba(BASE.white, 0.4),
  600: rgba(BASE.white, 0.35),
  500: rgba(BASE.white, 0.3),
  400: rgba(BASE.white, 0.25),
  300: rgba(BASE.white, 0.2),
  200: rgba(BASE.white, 0.15),
  100: rgba(BASE.white, 0.1),
  backgroundFill: rgba(LIGHT_GRAY[300], 0.8)
}; // Additional colors.
// Some are from https://make.wordpress.org/design/handbook/foundations/colors/.

const BLUE = {
  wordpress: {
    700: '#00669b'
  },
  dark: {
    900: '#0071a1'
  },
  medium: {
    900: '#006589',
    800: '#00739c',
    700: '#007fac',
    600: '#008dbe',
    500: '#00a0d2',
    400: '#33b3db',
    300: '#66c6e4',
    200: '#bfe7f3',
    100: '#e5f5fa',
    highlight: '#b3e7fe',
    focus: '#007cba'
  }
};
const ALERT = {
  yellow: '#f0b849',
  red: '#d94f4f',
  green: '#4ab866'
};
const ADMIN = {
  theme: `var( --wp-admin-theme-color, ${BLUE.wordpress[700]})`,
  themeDark10: `var( --wp-admin-theme-color-darker-10, ${BLUE.medium.focus})`
}; // Namespaced values for raw colors hex codes.

const UI = {
  theme: ADMIN.theme,
  background: BASE.white,
  backgroundDisabled: LIGHT_GRAY[200],
  border: G2.gray[700],
  borderHover: G2.gray[700],
  borderFocus: ADMIN.themeDark10,
  borderDisabled: G2.gray[400],
  borderLight: G2.gray[300],
  label: DARK_GRAY[500],
  textDisabled: DARK_GRAY[150],
  textDark: BASE.white,
  textLight: BASE.black
}; // Using Object.assign instead of { ...spread } syntax helps TypeScript
// to extract the correct type defs here.

const COLORS = Object.assign({}, BASE, {
  darkGray: (0,lodash.merge)({}, DARK_GRAY, G2.darkGray),
  darkOpacity: DARK_OPACITY,
  darkOpacityLight: DARK_OPACITY_LIGHT,
  mediumGray: G2.mediumGray,
  gray: G2.gray,
  lightGray: (0,lodash.merge)({}, LIGHT_GRAY, G2.lightGray),
  lightGrayLight: LIGHT_OPACITY_LIGHT,
  blue: (0,lodash.merge)({}, BLUE, G2.blue),
  alert: ALERT,
  admin: ADMIN,
  ui: UI
});
/* harmony default export */ const colors_values = ((/* unused pure expression or super */ null && (COLORS)));
//# sourceMappingURL=colors-values.js.map

/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/utils/config-values.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _ui_utils_space__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/ui/utils/space.js");
/* harmony import */ var _colors_values__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/utils/colors-values.js");
/**
 * Internal dependencies
 */


const CONTROL_HEIGHT = '36px';
const CONTROL_PADDING_X = '12px';
const CONTROL_PROPS = {
  controlSurfaceColor: _colors_values__WEBPACK_IMPORTED_MODULE_0__/* .COLORS */ .lm.white,
  controlTextActiveColor: _colors_values__WEBPACK_IMPORTED_MODULE_0__/* .COLORS */ .lm.ui.theme,
  controlPaddingX: CONTROL_PADDING_X,
  controlPaddingXLarge: `calc(${CONTROL_PADDING_X} * 1.3334)`,
  controlPaddingXSmall: `calc(${CONTROL_PADDING_X} / 1.3334)`,
  controlBackgroundColor: _colors_values__WEBPACK_IMPORTED_MODULE_0__/* .COLORS */ .lm.white,
  controlBorderRadius: '2px',
  controlBorderColor: _colors_values__WEBPACK_IMPORTED_MODULE_0__/* .COLORS */ .lm.gray[700],
  controlBoxShadow: 'transparent',
  controlBorderColorHover: _colors_values__WEBPACK_IMPORTED_MODULE_0__/* .COLORS */ .lm.gray[700],
  controlBoxShadowFocus: `0 0 0 0.5px ${_colors_values__WEBPACK_IMPORTED_MODULE_0__/* .COLORS */ .lm.admin.theme}`,
  controlDestructiveBorderColor: _colors_values__WEBPACK_IMPORTED_MODULE_0__/* .COLORS */ .lm.alert.red,
  controlHeight: CONTROL_HEIGHT,
  controlHeightXSmall: `calc( ${CONTROL_HEIGHT} * 0.6 )`,
  controlHeightSmall: `calc( ${CONTROL_HEIGHT} * 0.8 )`,
  controlHeightLarge: `calc( ${CONTROL_HEIGHT} * 1.2 )`,
  controlHeightXLarge: `calc( ${CONTROL_HEIGHT} * 1.4 )`
};
const TOGGLE_GROUP_CONTROL_PROPS = {
  toggleGroupControlBackgroundColor: CONTROL_PROPS.controlBackgroundColor,
  toggleGroupControlBorderColor: _colors_values__WEBPACK_IMPORTED_MODULE_0__/* .COLORS */ .lm.ui.border,
  toggleGroupControlBackdropBackgroundColor: CONTROL_PROPS.controlSurfaceColor,
  toggleGroupControlBackdropBorderColor: _colors_values__WEBPACK_IMPORTED_MODULE_0__/* .COLORS */ .lm.ui.border,
  toggleGroupControlBackdropBoxShadow: 'transparent',
  toggleGroupControlButtonColorActive: CONTROL_PROPS.controlBackgroundColor
}; // Using Object.assign to avoid creating circular references when emitting
// TypeScript type declarations.

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (Object.assign({}, CONTROL_PROPS, TOGGLE_GROUP_CONTROL_PROPS, {
  colorDivider: 'rgba(0, 0, 0, 0.1)',
  colorScrollbarThumb: 'rgba(0, 0, 0, 0.2)',
  colorScrollbarThumbHover: 'rgba(0, 0, 0, 0.5)',
  colorScrollbarTrack: 'rgba(0, 0, 0, 0.04)',
  elevationIntensity: 1,
  radiusBlockUi: '2px',
  borderWidth: '1px',
  borderWidthFocus: '1.5px',
  borderWidthTab: '4px',
  spinnerSize: 16,
  fontSize: '13px',
  fontSizeH1: 'calc(2.44 * 13px)',
  fontSizeH2: 'calc(1.95 * 13px)',
  fontSizeH3: 'calc(1.56 * 13px)',
  fontSizeH4: 'calc(1.25 * 13px)',
  fontSizeH5: '13px',
  fontSizeH6: 'calc(0.8 * 13px)',
  fontSizeInputMobile: '16px',
  fontSizeMobile: '15px',
  fontSizeSmall: 'calc(0.92 * 13px)',
  fontSizeXSmall: 'calc(0.75 * 13px)',
  fontLineHeightBase: '1.2',
  fontWeight: 'normal',
  fontWeightHeading: '600',
  gridBase: '4px',
  cardBorderRadius: '2px',
  cardPaddingXSmall: `${(0,_ui_utils_space__WEBPACK_IMPORTED_MODULE_1__/* .space */ .x)(2)}`,
  cardPaddingSmall: `${(0,_ui_utils_space__WEBPACK_IMPORTED_MODULE_1__/* .space */ .x)(4)}`,
  cardPaddingMedium: `${(0,_ui_utils_space__WEBPACK_IMPORTED_MODULE_1__/* .space */ .x)(4)} ${(0,_ui_utils_space__WEBPACK_IMPORTED_MODULE_1__/* .space */ .x)(6)}`,
  cardPaddingLarge: `${(0,_ui_utils_space__WEBPACK_IMPORTED_MODULE_1__/* .space */ .x)(6)} ${(0,_ui_utils_space__WEBPACK_IMPORTED_MODULE_1__/* .space */ .x)(8)}`,
  surfaceBackgroundColor: _colors_values__WEBPACK_IMPORTED_MODULE_0__/* .COLORS */ .lm.white,
  surfaceBackgroundSubtleColor: '#F3F3F3',
  surfaceBackgroundTintColor: '#F5F5F5',
  surfaceBorderColor: 'rgba(0, 0, 0, 0.1)',
  surfaceBorderBoldColor: 'rgba(0, 0, 0, 0.15)',
  surfaceBorderSubtleColor: 'rgba(0, 0, 0, 0.05)',
  surfaceBackgroundTertiaryColor: _colors_values__WEBPACK_IMPORTED_MODULE_0__/* .COLORS */ .lm.white,
  surfaceColor: _colors_values__WEBPACK_IMPORTED_MODULE_0__/* .COLORS */ .lm.white,
  transitionDuration: '200ms',
  transitionDurationFast: '160ms',
  transitionDurationFaster: '120ms',
  transitionDurationFastest: '100ms',
  transitionTimingFunction: 'cubic-bezier(0.08, 0.52, 0.52, 1)',
  transitionTimingFunctionControl: 'cubic-bezier(0.12, 0.8, 0.32, 1)'
}));
//# sourceMappingURL=config-values.js.map

/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+icons@8.2.3/node_modules/@wordpress/icons/build-module/library/close-small.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var _wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+primitives@3.4.1/node_modules/@wordpress/primitives/build-module/svg/index.js");


/**
 * WordPress dependencies
 */

const closeSmall = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__/* .SVG */ .t4, {
  xmlns: "http://www.w3.org/2000/svg",
  viewBox: "0 0 24 24"
}, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__/* .Path */ .wA, {
  d: "M12 13.06l3.712 3.713 1.061-1.06L13.061 12l3.712-3.712-1.06-1.06L12 10.938 8.288 7.227l-1.061 1.06L10.939 12l-3.712 3.712 1.06 1.061L12 13.061z"
}));
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (closeSmall);
//# sourceMappingURL=close-small.js.map

/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+viewport@4.20.0_react@17.0.2/node_modules/@wordpress/viewport/build-module/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";

// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  uE: () => (/* reexport */ with_viewport_match)
});

// UNUSED EXPORTS: ifViewportMatches, store

// NAMESPACE OBJECT: ../../node_modules/.pnpm/@wordpress+viewport@4.20.0_react@17.0.2/node_modules/@wordpress/viewport/build-module/store/actions.js
var actions_namespaceObject = {};
__webpack_require__.r(actions_namespaceObject);
__webpack_require__.d(actions_namespaceObject, {
  setIsMatching: () => (setIsMatching)
});

// NAMESPACE OBJECT: ../../node_modules/.pnpm/@wordpress+viewport@4.20.0_react@17.0.2/node_modules/@wordpress/viewport/build-module/store/selectors.js
var selectors_namespaceObject = {};
__webpack_require__.r(selectors_namespaceObject);
__webpack_require__.d(selectors_namespaceObject, {
  isViewportMatch: () => (isViewportMatch)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+compose@5.20.0_react@17.0.2/node_modules/@wordpress/compose/build-module/utils/debounce/index.js
var debounce = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@5.20.0_react@17.0.2/node_modules/@wordpress/compose/build-module/utils/debounce/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+data@7.6.0_react@17.0.2/node_modules/@wordpress/data/build-module/index.js
var build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+data@7.6.0_react@17.0.2/node_modules/@wordpress/data/build-module/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+data@7.6.0_react@17.0.2/node_modules/@wordpress/data/build-module/redux-store/index.js + 9 modules
var redux_store = __webpack_require__("../../node_modules/.pnpm/@wordpress+data@7.6.0_react@17.0.2/node_modules/@wordpress/data/build-module/redux-store/index.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+viewport@4.20.0_react@17.0.2/node_modules/@wordpress/viewport/build-module/store/reducer.js
/**
 * Reducer returning the viewport state, as keys of breakpoint queries with
 * boolean value representing whether query is matched.
 *
 * @param {Object} state  Current state.
 * @param {Object} action Dispatched action.
 *
 * @return {Object} Updated state.
 */
function reducer() {
  let state = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
  let action = arguments.length > 1 ? arguments[1] : undefined;

  switch (action.type) {
    case 'SET_IS_MATCHING':
      return action.values;
  }

  return state;
}

/* harmony default export */ const store_reducer = (reducer);
//# sourceMappingURL=reducer.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+viewport@4.20.0_react@17.0.2/node_modules/@wordpress/viewport/build-module/store/actions.js
/**
 * Returns an action object used in signalling that viewport queries have been
 * updated. Values are specified as an object of breakpoint query keys where
 * value represents whether query matches.
 * Ignored from documentation as it is for internal use only.
 *
 * @ignore
 *
 * @param {Object} values Breakpoint query matches.
 *
 * @return {Object} Action object.
 */
function setIsMatching(values) {
  return {
    type: 'SET_IS_MATCHING',
    values
  };
}
//# sourceMappingURL=actions.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+viewport@4.20.0_react@17.0.2/node_modules/@wordpress/viewport/build-module/store/selectors.js
/**
 * Returns true if the viewport matches the given query, or false otherwise.
 *
 * @param {Object} state Viewport state object.
 * @param {string} query Query string. Includes operator and breakpoint name,
 *                       space separated. Operator defaults to >=.
 *
 * @example
 *
 * ```js
 * import { store as viewportStore } from '@wordpress/viewport';
 * import { useSelect } from '@wordpress/data';
 * import { __ } from '@wordpress/i18n';
 * const ExampleComponent = () => {
 *     const isMobile = useSelect(
 *         ( select ) => select( viewportStore ).isViewportMatch( '< small' ),
 *         []
 *     );
 *
 *     return isMobile ? (
 *         <div>{ __( 'Mobile' ) }</div>
 *     ) : (
 *         <div>{ __( 'Not Mobile' ) }</div>
 *     );
 * };
 * ```
 *
 * @return {boolean} Whether viewport matches query.
 */
function isViewportMatch(state, query) {
  // Default to `>=` if no operator is present.
  if (query.indexOf(' ') === -1) {
    query = '>= ' + query;
  }

  return !!state[query];
}
//# sourceMappingURL=selectors.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+viewport@4.20.0_react@17.0.2/node_modules/@wordpress/viewport/build-module/store/index.js
/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */




const STORE_NAME = 'core/viewport';
/**
 * Store definition for the viewport namespace.
 *
 * @see https://github.com/WordPress/gutenberg/blob/HEAD/packages/data/README.md#createReduxStore
 *
 * @type {Object}
 */

const store = (0,redux_store/* default */.A)(STORE_NAME, {
  reducer: store_reducer,
  actions: actions_namespaceObject,
  selectors: selectors_namespaceObject
});
(0,build_module/* register */.kz)(store);
//# sourceMappingURL=index.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+viewport@4.20.0_react@17.0.2/node_modules/@wordpress/viewport/build-module/listener.js
/**
 * WordPress dependencies
 */


/**
 * Internal dependencies
 */



const addDimensionsEventListener = (breakpoints, operators) => {
  /**
   * Callback invoked when media query state should be updated. Is invoked a
   * maximum of one time per call stack.
   */
  const setIsMatching = (0,debounce/* debounce */.s)(() => {
    const values = Object.fromEntries(queries.map(_ref => {
      let [key, query] = _ref;
      return [key, query.matches];
    }));
    (0,build_module/* dispatch */.JD)(store).setIsMatching(values);
  }, 0, {
    leading: true
  });
  /**
   * Hash of breakpoint names with generated MediaQueryList for corresponding
   * media query.
   *
   * @see https://developer.mozilla.org/en-US/docs/Web/API/Window/matchMedia
   * @see https://developer.mozilla.org/en-US/docs/Web/API/MediaQueryList
   *
   * @type {Object<string,MediaQueryList>}
   */

  const operatorEntries = Object.entries(operators);
  const queries = Object.entries(breakpoints).flatMap(_ref2 => {
    let [name, width] = _ref2;
    return operatorEntries.map(_ref3 => {
      let [operator, condition] = _ref3;
      const list = window.matchMedia(`(${condition}: ${width}px)`);
      list.addEventListener('change', setIsMatching);
      return [`${operator} ${name}`, list];
    });
  });
  window.addEventListener('orientationchange', setIsMatching); // Set initial values.

  setIsMatching();
  setIsMatching.flush();
};

/* harmony default export */ const listener = (addDimensionsEventListener);
//# sourceMappingURL=listener.js.map
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.25.0/node_modules/@babel/runtime/helpers/esm/extends.js
var esm_extends = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.25.0/node_modules/@babel/runtime/helpers/esm/extends.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+compose@5.20.0_react@17.0.2/node_modules/@wordpress/compose/build-module/hooks/use-viewport-match/index.js
var use_viewport_match = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@5.20.0_react@17.0.2/node_modules/@wordpress/compose/build-module/hooks/use-viewport-match/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+compose@5.20.0_react@17.0.2/node_modules/@wordpress/compose/build-module/utils/create-higher-order-component/index.js + 1 modules
var create_higher_order_component = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@5.20.0_react@17.0.2/node_modules/@wordpress/compose/build-module/utils/create-higher-order-component/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+compose@5.20.0_react@17.0.2/node_modules/@wordpress/compose/build-module/higher-order/pure/index.js
var pure = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@5.20.0_react@17.0.2/node_modules/@wordpress/compose/build-module/higher-order/pure/index.js");
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+viewport@4.20.0_react@17.0.2/node_modules/@wordpress/viewport/build-module/with-viewport-match.js



/**
 * WordPress dependencies
 */

/**
 * Higher-order component creator, creating a new component which renders with
 * the given prop names, where the value passed to the underlying component is
 * the result of the query assigned as the object's value.
 *
 * @see isViewportMatch
 *
 * @param {Object} queries Object of prop name to viewport query.
 *
 * @example
 *
 * ```jsx
 * function MyComponent( { isMobile } ) {
 * 	return (
 * 		<div>Currently: { isMobile ? 'Mobile' : 'Not Mobile' }</div>
 * 	);
 * }
 *
 * MyComponent = withViewportMatch( { isMobile: '< small' } )( MyComponent );
 * ```
 *
 * @return {Function} Higher-order component.
 */

const withViewportMatch = queries => {
  const queryEntries = Object.entries(queries);

  const useViewPortQueriesResult = () => Object.fromEntries(queryEntries.map(_ref => {
    let [key, query] = _ref;
    let [operator, breakpointName] = query.split(' ');

    if (breakpointName === undefined) {
      breakpointName = operator;
      operator = '>=';
    } // Hooks should unconditionally execute in the same order,
    // we are respecting that as from the static query of the HOC we generate
    // a hook that calls other hooks always in the same order (because the query never changes).
    // eslint-disable-next-line react-hooks/rules-of-hooks


    return [key, (0,use_viewport_match/* default */.A)(breakpointName, operator)];
  }));

  return (0,create_higher_order_component/* createHigherOrderComponent */.f)(WrappedComponent => {
    return (0,pure/* default */.A)(props => {
      const queriesResult = useViewPortQueriesResult();
      return (0,react.createElement)(WrappedComponent, (0,esm_extends/* default */.A)({}, props, queriesResult));
    });
  }, 'withViewportMatch');
};

/* harmony default export */ const with_viewport_match = (withViewportMatch);
//# sourceMappingURL=with-viewport-match.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/@wordpress+viewport@4.20.0_react@17.0.2/node_modules/@wordpress/viewport/build-module/index.js
/**
 * Internal dependencies
 */




/**
 * Hash of breakpoint names with pixel width at which it becomes effective.
 *
 * @see _breakpoints.scss
 *
 * @type {Object}
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
 * Hash of query operators with corresponding condition for media query.
 *
 * @type {Object}
 */

const OPERATORS = {
  '<': 'max-width',
  '>=': 'min-width'
};
listener(BREAKPOINTS, OPERATORS);
//# sourceMappingURL=index.js.map

/***/ }),

/***/ "../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/array-for-each.js":
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

"use strict";

var $forEach = (__webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/array-iteration.js").forEach);
var arrayMethodIsStrict = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/array-method-is-strict.js");

var STRICT_METHOD = arrayMethodIsStrict('forEach');

// `Array.prototype.forEach` method implementation
// https://tc39.es/ecma262/#sec-array.prototype.foreach
module.exports = !STRICT_METHOD ? function forEach(callbackfn /* , thisArg */) {
  return $forEach(this, callbackfn, arguments.length > 1 ? arguments[1] : undefined);
// eslint-disable-next-line es/no-array-prototype-foreach -- safe
} : [].forEach;


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

/***/ "../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/array-method-is-strict.js":
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

"use strict";

var fails = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/fails.js");

module.exports = function (METHOD_NAME, argument) {
  var method = [][METHOD_NAME];
  return !!method && fails(function () {
    // eslint-disable-next-line no-useless-call -- required for testing
    method.call(null, argument || function () { return 1; }, 1);
  });
};


/***/ }),

/***/ "../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.filter.js":
/***/ ((__unused_webpack_module, __unused_webpack_exports, __webpack_require__) => {

"use strict";

var $ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/export.js");
var $filter = (__webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/array-iteration.js").filter);
var arrayMethodHasSpeciesSupport = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/array-method-has-species-support.js");

var HAS_SPECIES_SUPPORT = arrayMethodHasSpeciesSupport('filter');

// `Array.prototype.filter` method
// https://tc39.es/ecma262/#sec-array.prototype.filter
// with adding support of @@species
$({ target: 'Array', proto: true, forced: !HAS_SPECIES_SUPPORT }, {
  filter: function filter(callbackfn /* , thisArg */) {
    return $filter(this, callbackfn, arguments.length > 1 ? arguments[1] : undefined);
  }
});


/***/ }),

/***/ "../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.for-each.js":
/***/ ((__unused_webpack_module, __unused_webpack_exports, __webpack_require__) => {

"use strict";

var $ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/export.js");
var forEach = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/array-for-each.js");

// `Array.prototype.forEach` method
// https://tc39.es/ecma262/#sec-array.prototype.foreach
// eslint-disable-next-line es/no-array-prototype-foreach -- safe
$({ target: 'Array', proto: true, forced: [].forEach !== forEach }, {
  forEach: forEach
});


/***/ }),

/***/ "../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.define-properties.js":
/***/ ((__unused_webpack_module, __unused_webpack_exports, __webpack_require__) => {

"use strict";

var $ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/export.js");
var DESCRIPTORS = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/descriptors.js");
var defineProperties = (__webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/object-define-properties.js").f);

// `Object.defineProperties` method
// https://tc39.es/ecma262/#sec-object.defineproperties
// eslint-disable-next-line es/no-object-defineproperties -- safe
$({ target: 'Object', stat: true, forced: Object.defineProperties !== defineProperties, sham: !DESCRIPTORS }, {
  defineProperties: defineProperties
});


/***/ }),

/***/ "../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.define-property.js":
/***/ ((__unused_webpack_module, __unused_webpack_exports, __webpack_require__) => {

"use strict";

var $ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/export.js");
var DESCRIPTORS = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/descriptors.js");
var defineProperty = (__webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/object-define-property.js").f);

// `Object.defineProperty` method
// https://tc39.es/ecma262/#sec-object.defineproperty
// eslint-disable-next-line es/no-object-defineproperty -- safe
$({ target: 'Object', stat: true, forced: Object.defineProperty !== defineProperty, sham: !DESCRIPTORS }, {
  defineProperty: defineProperty
});


/***/ }),

/***/ "../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.get-own-property-descriptor.js":
/***/ ((__unused_webpack_module, __unused_webpack_exports, __webpack_require__) => {

"use strict";

var $ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/export.js");
var fails = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/fails.js");
var toIndexedObject = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/to-indexed-object.js");
var nativeGetOwnPropertyDescriptor = (__webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/object-get-own-property-descriptor.js").f);
var DESCRIPTORS = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/descriptors.js");

var FORCED = !DESCRIPTORS || fails(function () { nativeGetOwnPropertyDescriptor(1); });

// `Object.getOwnPropertyDescriptor` method
// https://tc39.es/ecma262/#sec-object.getownpropertydescriptor
$({ target: 'Object', stat: true, forced: FORCED, sham: !DESCRIPTORS }, {
  getOwnPropertyDescriptor: function getOwnPropertyDescriptor(it, key) {
    return nativeGetOwnPropertyDescriptor(toIndexedObject(it), key);
  }
});


/***/ }),

/***/ "../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.get-own-property-descriptors.js":
/***/ ((__unused_webpack_module, __unused_webpack_exports, __webpack_require__) => {

"use strict";

var $ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/export.js");
var DESCRIPTORS = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/descriptors.js");
var ownKeys = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/own-keys.js");
var toIndexedObject = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/to-indexed-object.js");
var getOwnPropertyDescriptorModule = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/object-get-own-property-descriptor.js");
var createProperty = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/create-property.js");

// `Object.getOwnPropertyDescriptors` method
// https://tc39.es/ecma262/#sec-object.getownpropertydescriptors
$({ target: 'Object', stat: true, sham: !DESCRIPTORS }, {
  getOwnPropertyDescriptors: function getOwnPropertyDescriptors(object) {
    var O = toIndexedObject(object);
    var getOwnPropertyDescriptor = getOwnPropertyDescriptorModule.f;
    var keys = ownKeys(O);
    var result = {};
    var index = 0;
    var key, descriptor;
    while (keys.length > index) {
      descriptor = getOwnPropertyDescriptor(O, key = keys[index++]);
      if (descriptor !== undefined) createProperty(result, key, descriptor);
    }
    return result;
  }
});


/***/ }),

/***/ "../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.keys.js":
/***/ ((__unused_webpack_module, __unused_webpack_exports, __webpack_require__) => {

"use strict";

var $ = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/export.js");
var toObject = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/to-object.js");
var nativeKeys = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/object-keys.js");
var fails = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/fails.js");

var FAILS_ON_PRIMITIVES = fails(function () { nativeKeys(1); });

// `Object.keys` method
// https://tc39.es/ecma262/#sec-object.keys
$({ target: 'Object', stat: true, forced: FAILS_ON_PRIMITIVES }, {
  keys: function keys(it) {
    return nativeKeys(toObject(it));
  }
});


/***/ }),

/***/ "../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/web.dom-collections.for-each.js":
/***/ ((__unused_webpack_module, __unused_webpack_exports, __webpack_require__) => {

"use strict";

var global = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/global.js");
var DOMIterables = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/dom-iterables.js");
var DOMTokenListPrototype = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/dom-token-list-prototype.js");
var forEach = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/array-for-each.js");
var createNonEnumerableProperty = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/internals/create-non-enumerable-property.js");

var handlePrototype = function (CollectionPrototype) {
  // some Chrome versions have non-configurable methods on DOMTokenList
  if (CollectionPrototype && CollectionPrototype.forEach !== forEach) try {
    createNonEnumerableProperty(CollectionPrototype, 'forEach', forEach);
  } catch (error) {
    CollectionPrototype.forEach = forEach;
  }
};

for (var COLLECTION_NAME in DOMIterables) {
  if (DOMIterables[COLLECTION_NAME]) {
    handlePrototype(global[COLLECTION_NAME] && global[COLLECTION_NAME].prototype);
  }
}

handlePrototype(DOMTokenListPrototype);


/***/ }),

/***/ "../../node_modules/.pnpm/debug@4.3.4_supports-color@8.1.1/node_modules/debug/src/browser.js":
/***/ ((module, exports, __webpack_require__) => {

/* provided dependency */ var process = __webpack_require__("../../node_modules/.pnpm/process@0.11.10/node_modules/process/browser.js");
/* eslint-env browser */

/**
 * This is the web browser implementation of `debug()`.
 */

exports.formatArgs = formatArgs;
exports.save = save;
exports.load = load;
exports.useColors = useColors;
exports.storage = localstorage();
exports.destroy = (() => {
	let warned = false;

	return () => {
		if (!warned) {
			warned = true;
			console.warn('Instance method `debug.destroy()` is deprecated and no longer does anything. It will be removed in the next major version of `debug`.');
		}
	};
})();

/**
 * Colors.
 */

exports.colors = [
	'#0000CC',
	'#0000FF',
	'#0033CC',
	'#0033FF',
	'#0066CC',
	'#0066FF',
	'#0099CC',
	'#0099FF',
	'#00CC00',
	'#00CC33',
	'#00CC66',
	'#00CC99',
	'#00CCCC',
	'#00CCFF',
	'#3300CC',
	'#3300FF',
	'#3333CC',
	'#3333FF',
	'#3366CC',
	'#3366FF',
	'#3399CC',
	'#3399FF',
	'#33CC00',
	'#33CC33',
	'#33CC66',
	'#33CC99',
	'#33CCCC',
	'#33CCFF',
	'#6600CC',
	'#6600FF',
	'#6633CC',
	'#6633FF',
	'#66CC00',
	'#66CC33',
	'#9900CC',
	'#9900FF',
	'#9933CC',
	'#9933FF',
	'#99CC00',
	'#99CC33',
	'#CC0000',
	'#CC0033',
	'#CC0066',
	'#CC0099',
	'#CC00CC',
	'#CC00FF',
	'#CC3300',
	'#CC3333',
	'#CC3366',
	'#CC3399',
	'#CC33CC',
	'#CC33FF',
	'#CC6600',
	'#CC6633',
	'#CC9900',
	'#CC9933',
	'#CCCC00',
	'#CCCC33',
	'#FF0000',
	'#FF0033',
	'#FF0066',
	'#FF0099',
	'#FF00CC',
	'#FF00FF',
	'#FF3300',
	'#FF3333',
	'#FF3366',
	'#FF3399',
	'#FF33CC',
	'#FF33FF',
	'#FF6600',
	'#FF6633',
	'#FF9900',
	'#FF9933',
	'#FFCC00',
	'#FFCC33'
];

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
	}

	// Internet Explorer and Edge do not support colors.
	if (typeof navigator !== 'undefined' && navigator.userAgent && navigator.userAgent.toLowerCase().match(/(edge|trident)\/(\d+)/)) {
		return false;
	}

	// Is webkit? http://stackoverflow.com/a/16459606/376773
	// document is undefined in react-native: https://github.com/facebook/react-native/pull/1632
	return (typeof document !== 'undefined' && document.documentElement && document.documentElement.style && document.documentElement.style.WebkitAppearance) ||
		// Is firebug? http://stackoverflow.com/a/398120/376773
		(typeof window !== 'undefined' && window.console && (window.console.firebug || (window.console.exception && window.console.table))) ||
		// Is firefox >= v31?
		// https://developer.mozilla.org/en-US/docs/Tools/Web_Console#Styling_messages
		(typeof navigator !== 'undefined' && navigator.userAgent && navigator.userAgent.toLowerCase().match(/firefox\/(\d+)/) && parseInt(RegExp.$1, 10) >= 31) ||
		// Double check webkit in userAgent just in case we are in a worker
		(typeof navigator !== 'undefined' && navigator.userAgent && navigator.userAgent.toLowerCase().match(/applewebkit\/(\d+)/));
}

/**
 * Colorize log arguments if enabled.
 *
 * @api public
 */

function formatArgs(args) {
	args[0] = (this.useColors ? '%c' : '') +
		this.namespace +
		(this.useColors ? ' %c' : ' ') +
		args[0] +
		(this.useColors ? '%c ' : ' ') +
		'+' + module.exports.humanize(this.diff);

	if (!this.useColors) {
		return;
	}

	const c = 'color: ' + this.color;
	args.splice(1, 0, c, 'color: inherit');

	// The final "%c" is somewhat tricky, because there could be other
	// arguments passed either before or after the %c, so we need to
	// figure out the correct index to insert the CSS into
	let index = 0;
	let lastC = 0;
	args[0].replace(/%[a-zA-Z%]/g, match => {
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
 * Invokes `console.debug()` when available.
 * No-op when `console.debug` is not a "function".
 * If `console.debug` is not available, falls back
 * to `console.log`.
 *
 * @api public
 */
exports.log = console.debug || console.log || (() => {});

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
	} catch (error) {
		// Swallow
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
	let r;
	try {
		r = exports.storage.getItem('debug');
	} catch (error) {
		// Swallow
		// XXX (@Qix-) should we be logging these?
	}

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
	} catch (error) {
		// Swallow
		// XXX (@Qix-) should we be logging these?
	}
}

module.exports = __webpack_require__("../../node_modules/.pnpm/debug@4.3.4_supports-color@8.1.1/node_modules/debug/src/common.js")(exports);

const {formatters} = module.exports;

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


/***/ }),

/***/ "../../node_modules/.pnpm/debug@4.3.4_supports-color@8.1.1/node_modules/debug/src/common.js":
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {


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
	createDebug.humanize = __webpack_require__("../../node_modules/.pnpm/ms@2.1.2/node_modules/ms/index.js");
	createDebug.destroy = destroy;

	Object.keys(env).forEach(key => {
		createDebug[key] = env[key];
	});

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
	* @param {String} namespace The namespace string for the debug instance to be colored
	* @return {Number|String} An ANSI color code for the given namespace
	* @api private
	*/
	function selectColor(namespace) {
		let hash = 0;

		for (let i = 0; i < namespace.length; i++) {
			hash = ((hash << 5) - hash) + namespace.charCodeAt(i);
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
		let prevTime;
		let enableOverride = null;
		let namespacesCache;
		let enabledCache;

		function debug(...args) {
			// Disabled?
			if (!debug.enabled) {
				return;
			}

			const self = debug;

			// Set `diff` timestamp
			const curr = Number(new Date());
			const ms = curr - (prevTime || curr);
			self.diff = ms;
			self.prev = prevTime;
			self.curr = curr;
			prevTime = curr;

			args[0] = createDebug.coerce(args[0]);

			if (typeof args[0] !== 'string') {
				// Anything else let's inspect with %O
				args.unshift('%O');
			}

			// Apply any `formatters` transformations
			let index = 0;
			args[0] = args[0].replace(/%([a-zA-Z%])/g, (match, format) => {
				// If we encounter an escaped % then don't increase the array index
				if (match === '%%') {
					return '%';
				}
				index++;
				const formatter = createDebug.formatters[format];
				if (typeof formatter === 'function') {
					const val = args[index];
					match = formatter.call(self, val);

					// Now we need to remove `args[index]` since it's inlined in the `format`
					args.splice(index, 1);
					index--;
				}
				return match;
			});

			// Apply env-specific formatting (colors, etc.)
			createDebug.formatArgs.call(self, args);

			const logFn = self.log || createDebug.log;
			logFn.apply(self, args);
		}

		debug.namespace = namespace;
		debug.useColors = createDebug.useColors();
		debug.color = createDebug.selectColor(namespace);
		debug.extend = extend;
		debug.destroy = createDebug.destroy; // XXX Temporary. Will be removed in the next major release.

		Object.defineProperty(debug, 'enabled', {
			enumerable: true,
			configurable: false,
			get: () => {
				if (enableOverride !== null) {
					return enableOverride;
				}
				if (namespacesCache !== createDebug.namespaces) {
					namespacesCache = createDebug.namespaces;
					enabledCache = createDebug.enabled(namespace);
				}

				return enabledCache;
			},
			set: v => {
				enableOverride = v;
			}
		});

		// Env-specific initialization logic for debug instances
		if (typeof createDebug.init === 'function') {
			createDebug.init(debug);
		}

		return debug;
	}

	function extend(namespace, delimiter) {
		const newDebug = createDebug(this.namespace + (typeof delimiter === 'undefined' ? ':' : delimiter) + namespace);
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
		createDebug.namespaces = namespaces;

		createDebug.names = [];
		createDebug.skips = [];

		let i;
		const split = (typeof namespaces === 'string' ? namespaces : '').split(/[\s,]+/);
		const len = split.length;

		for (i = 0; i < len; i++) {
			if (!split[i]) {
				// ignore empty strings
				continue;
			}

			namespaces = split[i].replace(/\*/g, '.*?');

			if (namespaces[0] === '-') {
				createDebug.skips.push(new RegExp('^' + namespaces.slice(1) + '$'));
			} else {
				createDebug.names.push(new RegExp('^' + namespaces + '$'));
			}
		}
	}

	/**
	* Disable debug output.
	*
	* @return {String} namespaces
	* @api public
	*/
	function disable() {
		const namespaces = [
			...createDebug.names.map(toNamespace),
			...createDebug.skips.map(toNamespace).map(namespace => '-' + namespace)
		].join(',');
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

		let i;
		let len;

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
		return regexp.toString()
			.substring(2, regexp.toString().length - 2)
			.replace(/\.\*\?$/, '*');
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

	/**
	* XXX DO NOT USE. This is a temporary stub function.
	* XXX It WILL be removed in the next major release.
	*/
	function destroy() {
		console.warn('Instance method `debug.destroy()` is deprecated and no longer does anything. It will be removed in the next major version of `debug`.');
	}

	createDebug.enable(createDebug.load());

	return createDebug;
}

module.exports = setup;


/***/ }),

/***/ "../../node_modules/.pnpm/ms@2.1.2/node_modules/ms/index.js":
/***/ ((module) => {

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

/***/ "../../node_modules/.pnpm/no-case@3.0.4/node_modules/no-case/dist.es2015/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";

// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  W: () => (/* binding */ noCase)
});

;// CONCATENATED MODULE: ../../node_modules/.pnpm/lower-case@2.0.2/node_modules/lower-case/dist.es2015/index.js
/**
 * Source: ftp://ftp.unicode.org/Public/UCD/latest/ucd/SpecialCasing.txt
 */
var SUPPORTED_LOCALE = {
    tr: {
        regexp: /\u0130|\u0049|\u0049\u0307/g,
        map: {
            İ: "\u0069",
            I: "\u0131",
            İ: "\u0069",
        },
    },
    az: {
        regexp: /\u0130/g,
        map: {
            İ: "\u0069",
            I: "\u0131",
            İ: "\u0069",
        },
    },
    lt: {
        regexp: /\u0049|\u004A|\u012E|\u00CC|\u00CD|\u0128/g,
        map: {
            I: "\u0069\u0307",
            J: "\u006A\u0307",
            Į: "\u012F\u0307",
            Ì: "\u0069\u0307\u0300",
            Í: "\u0069\u0307\u0301",
            Ĩ: "\u0069\u0307\u0303",
        },
    },
};
/**
 * Localized lower case.
 */
function localeLowerCase(str, locale) {
    var lang = SUPPORTED_LOCALE[locale.toLowerCase()];
    if (lang)
        return lowerCase(str.replace(lang.regexp, function (m) { return lang.map[m]; }));
    return lowerCase(str);
}
/**
 * Lower case as a function.
 */
function lowerCase(str) {
    return str.toLowerCase();
}
//# sourceMappingURL=index.js.map
;// CONCATENATED MODULE: ../../node_modules/.pnpm/no-case@3.0.4/node_modules/no-case/dist.es2015/index.js

// Support camel case ("camelCase" -> "camel Case" and "CAMELCase" -> "CAMEL Case").
var DEFAULT_SPLIT_REGEXP = [/([a-z0-9])([A-Z])/g, /([A-Z])([A-Z][a-z])/g];
// Remove all non-word characters.
var DEFAULT_STRIP_REGEXP = /[^A-Z0-9]+/gi;
/**
 * Normalize the string into something other libraries can manipulate easier.
 */
function noCase(input, options) {
    if (options === void 0) { options = {}; }
    var _a = options.splitRegexp, splitRegexp = _a === void 0 ? DEFAULT_SPLIT_REGEXP : _a, _b = options.stripRegexp, stripRegexp = _b === void 0 ? DEFAULT_STRIP_REGEXP : _b, _c = options.transform, transform = _c === void 0 ? lowerCase : _c, _d = options.delimiter, delimiter = _d === void 0 ? " " : _d;
    var result = replace(replace(input, splitRegexp, "$1\0$2"), stripRegexp, "\0");
    var start = 0;
    var end = result.length;
    // Trim the delimiter from around the output string.
    while (result.charAt(start) === "\0")
        start++;
    while (result.charAt(end - 1) === "\0")
        end--;
    // Transform each token independently.
    return result.slice(start, end).split("\0").map(transform).join(delimiter);
}
/**
 * Replace `re` in the input string with the replacement value.
 */
function replace(input, re, value) {
    if (re instanceof RegExp)
        return input.replace(re, value);
    return re.reduce(function (input, re) { return input.replace(re, value); }, input);
}
//# sourceMappingURL=index.js.map

/***/ }),

/***/ "../../node_modules/.pnpm/object-assign@4.1.1/node_modules/object-assign/index.js":
/***/ ((module) => {

"use strict";
/*
object-assign
(c) Sindre Sorhus
@license MIT
*/


/* eslint-disable no-unused-vars */
var getOwnPropertySymbols = Object.getOwnPropertySymbols;
var hasOwnProperty = Object.prototype.hasOwnProperty;
var propIsEnumerable = Object.prototype.propertyIsEnumerable;

function toObject(val) {
	if (val === null || val === undefined) {
		throw new TypeError('Object.assign cannot be called with null or undefined');
	}

	return Object(val);
}

function shouldUseNative() {
	try {
		if (!Object.assign) {
			return false;
		}

		// Detect buggy property enumeration order in older V8 versions.

		// https://bugs.chromium.org/p/v8/issues/detail?id=4118
		var test1 = new String('abc');  // eslint-disable-line no-new-wrappers
		test1[5] = 'de';
		if (Object.getOwnPropertyNames(test1)[0] === '5') {
			return false;
		}

		// https://bugs.chromium.org/p/v8/issues/detail?id=3056
		var test2 = {};
		for (var i = 0; i < 10; i++) {
			test2['_' + String.fromCharCode(i)] = i;
		}
		var order2 = Object.getOwnPropertyNames(test2).map(function (n) {
			return test2[n];
		});
		if (order2.join('') !== '0123456789') {
			return false;
		}

		// https://bugs.chromium.org/p/v8/issues/detail?id=3056
		var test3 = {};
		'abcdefghijklmnopqrst'.split('').forEach(function (letter) {
			test3[letter] = letter;
		});
		if (Object.keys(Object.assign({}, test3)).join('') !==
				'abcdefghijklmnopqrst') {
			return false;
		}

		return true;
	} catch (err) {
		// We don't expect any of the above to throw, but better to be safe.
		return false;
	}
}

module.exports = shouldUseNative() ? Object.assign : function (target, source) {
	var from;
	var to = toObject(target);
	var symbols;

	for (var s = 1; s < arguments.length; s++) {
		from = Object(arguments[s]);

		for (var key in from) {
			if (hasOwnProperty.call(from, key)) {
				to[key] = from[key];
			}
		}

		if (getOwnPropertySymbols) {
			symbols = getOwnPropertySymbols(from);
			for (var i = 0; i < symbols.length; i++) {
				if (propIsEnumerable.call(from, symbols[i])) {
					to[symbols[i]] = from[symbols[i]];
				}
			}
		}
	}

	return to;
};


/***/ }),

/***/ "../../node_modules/.pnpm/react-fast-compare@3.2.2/node_modules/react-fast-compare/index.js":
/***/ ((module) => {

/* global Map:readonly, Set:readonly, ArrayBuffer:readonly */

var hasElementType = typeof Element !== 'undefined';
var hasMap = typeof Map === 'function';
var hasSet = typeof Set === 'function';
var hasArrayBuffer = typeof ArrayBuffer === 'function' && !!ArrayBuffer.isView;

// Note: We **don't** need `envHasBigInt64Array` in fde es6/index.js

function equal(a, b) {
  // START: fast-deep-equal es6/index.js 3.1.3
  if (a === b) return true;

  if (a && b && typeof a == 'object' && typeof b == 'object') {
    if (a.constructor !== b.constructor) return false;

    var length, i, keys;
    if (Array.isArray(a)) {
      length = a.length;
      if (length != b.length) return false;
      for (i = length; i-- !== 0;)
        if (!equal(a[i], b[i])) return false;
      return true;
    }

    // START: Modifications:
    // 1. Extra `has<Type> &&` helpers in initial condition allow es6 code
    //    to co-exist with es5.
    // 2. Replace `for of` with es5 compliant iteration using `for`.
    //    Basically, take:
    //
    //    ```js
    //    for (i of a.entries())
    //      if (!b.has(i[0])) return false;
    //    ```
    //
    //    ... and convert to:
    //
    //    ```js
    //    it = a.entries();
    //    while (!(i = it.next()).done)
    //      if (!b.has(i.value[0])) return false;
    //    ```
    //
    //    **Note**: `i` access switches to `i.value`.
    var it;
    if (hasMap && (a instanceof Map) && (b instanceof Map)) {
      if (a.size !== b.size) return false;
      it = a.entries();
      while (!(i = it.next()).done)
        if (!b.has(i.value[0])) return false;
      it = a.entries();
      while (!(i = it.next()).done)
        if (!equal(i.value[1], b.get(i.value[0]))) return false;
      return true;
    }

    if (hasSet && (a instanceof Set) && (b instanceof Set)) {
      if (a.size !== b.size) return false;
      it = a.entries();
      while (!(i = it.next()).done)
        if (!b.has(i.value[0])) return false;
      return true;
    }
    // END: Modifications

    if (hasArrayBuffer && ArrayBuffer.isView(a) && ArrayBuffer.isView(b)) {
      length = a.length;
      if (length != b.length) return false;
      for (i = length; i-- !== 0;)
        if (a[i] !== b[i]) return false;
      return true;
    }

    if (a.constructor === RegExp) return a.source === b.source && a.flags === b.flags;
    // START: Modifications:
    // Apply guards for `Object.create(null)` handling. See:
    // - https://github.com/FormidableLabs/react-fast-compare/issues/64
    // - https://github.com/epoberezkin/fast-deep-equal/issues/49
    if (a.valueOf !== Object.prototype.valueOf && typeof a.valueOf === 'function' && typeof b.valueOf === 'function') return a.valueOf() === b.valueOf();
    if (a.toString !== Object.prototype.toString && typeof a.toString === 'function' && typeof b.toString === 'function') return a.toString() === b.toString();
    // END: Modifications

    keys = Object.keys(a);
    length = keys.length;
    if (length !== Object.keys(b).length) return false;

    for (i = length; i-- !== 0;)
      if (!Object.prototype.hasOwnProperty.call(b, keys[i])) return false;
    // END: fast-deep-equal

    // START: react-fast-compare
    // custom handling for DOM elements
    if (hasElementType && a instanceof Element) return false;

    // custom handling for React/Preact
    for (i = length; i-- !== 0;) {
      if ((keys[i] === '_owner' || keys[i] === '__v' || keys[i] === '__o') && a.$$typeof) {
        // React-specific: avoid traversing React elements' _owner
        // Preact-specific: avoid traversing Preact elements' __v and __o
        //    __v = $_original / $_vnode
        //    __o = $_owner
        // These properties contain circular references and are not needed when
        // comparing the actual elements (and not their owners)
        // .$$typeof and ._store on just reasonable markers of elements

        continue;
      }

      // all other properties should be traversed as usual
      if (!equal(a[keys[i]], b[keys[i]])) return false;
    }
    // END: react-fast-compare

    // START: fast-deep-equal
    return true;
  }

  return a !== a && b !== b;
}
// end fast-deep-equal

module.exports = function isEqual(a, b) {
  try {
    return equal(a, b);
  } catch (error) {
    if (((error.message || '').match(/stack|recursion/i))) {
      // warn on circular references, don't crash
      // browsers give this different errors name and messages:
      // chrome/safari: "RangeError", "Maximum call stack size exceeded"
      // firefox: "InternalError", too much recursion"
      // edge: "Error", "Out of stack space"
      console.warn('react-fast-compare cannot handle circular refs');
      return false;
    }
    // some other error. we should definitely know about these
    throw error;
  }
};


/***/ }),

/***/ "../../node_modules/.pnpm/react@17.0.2/node_modules/react/cjs/react-jsx-runtime.production.min.js":
/***/ ((__unused_webpack_module, exports, __webpack_require__) => {

"use strict";
/** @license React v17.0.2
 * react-jsx-runtime.production.min.js
 *
 * Copyright (c) Facebook, Inc. and its affiliates.
 *
 * This source code is licensed under the MIT license found in the
 * LICENSE file in the root directory of this source tree.
 */
__webpack_require__("../../node_modules/.pnpm/object-assign@4.1.1/node_modules/object-assign/index.js");var f=__webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js"),g=60103;exports.Fragment=60107;if("function"===typeof Symbol&&Symbol.for){var h=Symbol.for;g=h("react.element");exports.Fragment=h("react.fragment")}var m=f.__SECRET_INTERNALS_DO_NOT_USE_OR_YOU_WILL_BE_FIRED.ReactCurrentOwner,n=Object.prototype.hasOwnProperty,p={key:!0,ref:!0,__self:!0,__source:!0};
function q(c,a,k){var b,d={},e=null,l=null;void 0!==k&&(e=""+k);void 0!==a.key&&(e=""+a.key);void 0!==a.ref&&(l=a.ref);for(b in a)n.call(a,b)&&!p.hasOwnProperty(b)&&(d[b]=a[b]);if(c&&c.defaultProps)for(b in a=c.defaultProps,a)void 0===d[b]&&(d[b]=a[b]);return{$$typeof:g,type:c,key:e,ref:l,props:d,_owner:m.current}}exports.jsx=q;exports.jsxs=q;


/***/ }),

/***/ "../../node_modules/.pnpm/react@17.0.2/node_modules/react/jsx-runtime.js":
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

"use strict";


if (true) {
  module.exports = __webpack_require__("../../node_modules/.pnpm/react@17.0.2/node_modules/react/cjs/react-jsx-runtime.production.min.js");
} else {}


/***/ }),

/***/ "../../node_modules/.pnpm/tslib@2.6.3/node_modules/tslib/tslib.es6.mjs":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   Cl: () => (/* binding */ __assign),
/* harmony export */   Tt: () => (/* binding */ __rest)
/* harmony export */ });
/* unused harmony exports __extends, __decorate, __param, __esDecorate, __runInitializers, __propKey, __setFunctionName, __metadata, __awaiter, __generator, __createBinding, __exportStar, __values, __read, __spread, __spreadArrays, __spreadArray, __await, __asyncGenerator, __asyncDelegator, __asyncValues, __makeTemplateObject, __importStar, __importDefault, __classPrivateFieldGet, __classPrivateFieldSet, __classPrivateFieldIn, __addDisposableResource, __disposeResources */
/******************************************************************************
Copyright (c) Microsoft Corporation.

Permission to use, copy, modify, and/or distribute this software for any
purpose with or without fee is hereby granted.

THE SOFTWARE IS PROVIDED "AS IS" AND THE AUTHOR DISCLAIMS ALL WARRANTIES WITH
REGARD TO THIS SOFTWARE INCLUDING ALL IMPLIED WARRANTIES OF MERCHANTABILITY
AND FITNESS. IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR ANY SPECIAL, DIRECT,
INDIRECT, OR CONSEQUENTIAL DAMAGES OR ANY DAMAGES WHATSOEVER RESULTING FROM
LOSS OF USE, DATA OR PROFITS, WHETHER IN AN ACTION OF CONTRACT, NEGLIGENCE OR
OTHER TORTIOUS ACTION, ARISING OUT OF OR IN CONNECTION WITH THE USE OR
PERFORMANCE OF THIS SOFTWARE.
***************************************************************************** */
/* global Reflect, Promise, SuppressedError, Symbol */

var extendStatics = function(d, b) {
  extendStatics = Object.setPrototypeOf ||
      ({ __proto__: [] } instanceof Array && function (d, b) { d.__proto__ = b; }) ||
      function (d, b) { for (var p in b) if (Object.prototype.hasOwnProperty.call(b, p)) d[p] = b[p]; };
  return extendStatics(d, b);
};

function __extends(d, b) {
  if (typeof b !== "function" && b !== null)
      throw new TypeError("Class extends value " + String(b) + " is not a constructor or null");
  extendStatics(d, b);
  function __() { this.constructor = d; }
  d.prototype = b === null ? Object.create(b) : (__.prototype = b.prototype, new __());
}

var __assign = function() {
  __assign = Object.assign || function __assign(t) {
      for (var s, i = 1, n = arguments.length; i < n; i++) {
          s = arguments[i];
          for (var p in s) if (Object.prototype.hasOwnProperty.call(s, p)) t[p] = s[p];
      }
      return t;
  }
  return __assign.apply(this, arguments);
}

function __rest(s, e) {
  var t = {};
  for (var p in s) if (Object.prototype.hasOwnProperty.call(s, p) && e.indexOf(p) < 0)
      t[p] = s[p];
  if (s != null && typeof Object.getOwnPropertySymbols === "function")
      for (var i = 0, p = Object.getOwnPropertySymbols(s); i < p.length; i++) {
          if (e.indexOf(p[i]) < 0 && Object.prototype.propertyIsEnumerable.call(s, p[i]))
              t[p[i]] = s[p[i]];
      }
  return t;
}

function __decorate(decorators, target, key, desc) {
  var c = arguments.length, r = c < 3 ? target : desc === null ? desc = Object.getOwnPropertyDescriptor(target, key) : desc, d;
  if (typeof Reflect === "object" && typeof Reflect.decorate === "function") r = Reflect.decorate(decorators, target, key, desc);
  else for (var i = decorators.length - 1; i >= 0; i--) if (d = decorators[i]) r = (c < 3 ? d(r) : c > 3 ? d(target, key, r) : d(target, key)) || r;
  return c > 3 && r && Object.defineProperty(target, key, r), r;
}

function __param(paramIndex, decorator) {
  return function (target, key) { decorator(target, key, paramIndex); }
}

function __esDecorate(ctor, descriptorIn, decorators, contextIn, initializers, extraInitializers) {
  function accept(f) { if (f !== void 0 && typeof f !== "function") throw new TypeError("Function expected"); return f; }
  var kind = contextIn.kind, key = kind === "getter" ? "get" : kind === "setter" ? "set" : "value";
  var target = !descriptorIn && ctor ? contextIn["static"] ? ctor : ctor.prototype : null;
  var descriptor = descriptorIn || (target ? Object.getOwnPropertyDescriptor(target, contextIn.name) : {});
  var _, done = false;
  for (var i = decorators.length - 1; i >= 0; i--) {
      var context = {};
      for (var p in contextIn) context[p] = p === "access" ? {} : contextIn[p];
      for (var p in contextIn.access) context.access[p] = contextIn.access[p];
      context.addInitializer = function (f) { if (done) throw new TypeError("Cannot add initializers after decoration has completed"); extraInitializers.push(accept(f || null)); };
      var result = (0, decorators[i])(kind === "accessor" ? { get: descriptor.get, set: descriptor.set } : descriptor[key], context);
      if (kind === "accessor") {
          if (result === void 0) continue;
          if (result === null || typeof result !== "object") throw new TypeError("Object expected");
          if (_ = accept(result.get)) descriptor.get = _;
          if (_ = accept(result.set)) descriptor.set = _;
          if (_ = accept(result.init)) initializers.unshift(_);
      }
      else if (_ = accept(result)) {
          if (kind === "field") initializers.unshift(_);
          else descriptor[key] = _;
      }
  }
  if (target) Object.defineProperty(target, contextIn.name, descriptor);
  done = true;
};

function __runInitializers(thisArg, initializers, value) {
  var useValue = arguments.length > 2;
  for (var i = 0; i < initializers.length; i++) {
      value = useValue ? initializers[i].call(thisArg, value) : initializers[i].call(thisArg);
  }
  return useValue ? value : void 0;
};

function __propKey(x) {
  return typeof x === "symbol" ? x : "".concat(x);
};

function __setFunctionName(f, name, prefix) {
  if (typeof name === "symbol") name = name.description ? "[".concat(name.description, "]") : "";
  return Object.defineProperty(f, "name", { configurable: true, value: prefix ? "".concat(prefix, " ", name) : name });
};

function __metadata(metadataKey, metadataValue) {
  if (typeof Reflect === "object" && typeof Reflect.metadata === "function") return Reflect.metadata(metadataKey, metadataValue);
}

function __awaiter(thisArg, _arguments, P, generator) {
  function adopt(value) { return value instanceof P ? value : new P(function (resolve) { resolve(value); }); }
  return new (P || (P = Promise))(function (resolve, reject) {
      function fulfilled(value) { try { step(generator.next(value)); } catch (e) { reject(e); } }
      function rejected(value) { try { step(generator["throw"](value)); } catch (e) { reject(e); } }
      function step(result) { result.done ? resolve(result.value) : adopt(result.value).then(fulfilled, rejected); }
      step((generator = generator.apply(thisArg, _arguments || [])).next());
  });
}

function __generator(thisArg, body) {
  var _ = { label: 0, sent: function() { if (t[0] & 1) throw t[1]; return t[1]; }, trys: [], ops: [] }, f, y, t, g;
  return g = { next: verb(0), "throw": verb(1), "return": verb(2) }, typeof Symbol === "function" && (g[Symbol.iterator] = function() { return this; }), g;
  function verb(n) { return function (v) { return step([n, v]); }; }
  function step(op) {
      if (f) throw new TypeError("Generator is already executing.");
      while (g && (g = 0, op[0] && (_ = 0)), _) try {
          if (f = 1, y && (t = op[0] & 2 ? y["return"] : op[0] ? y["throw"] || ((t = y["return"]) && t.call(y), 0) : y.next) && !(t = t.call(y, op[1])).done) return t;
          if (y = 0, t) op = [op[0] & 2, t.value];
          switch (op[0]) {
              case 0: case 1: t = op; break;
              case 4: _.label++; return { value: op[1], done: false };
              case 5: _.label++; y = op[1]; op = [0]; continue;
              case 7: op = _.ops.pop(); _.trys.pop(); continue;
              default:
                  if (!(t = _.trys, t = t.length > 0 && t[t.length - 1]) && (op[0] === 6 || op[0] === 2)) { _ = 0; continue; }
                  if (op[0] === 3 && (!t || (op[1] > t[0] && op[1] < t[3]))) { _.label = op[1]; break; }
                  if (op[0] === 6 && _.label < t[1]) { _.label = t[1]; t = op; break; }
                  if (t && _.label < t[2]) { _.label = t[2]; _.ops.push(op); break; }
                  if (t[2]) _.ops.pop();
                  _.trys.pop(); continue;
          }
          op = body.call(thisArg, _);
      } catch (e) { op = [6, e]; y = 0; } finally { f = t = 0; }
      if (op[0] & 5) throw op[1]; return { value: op[0] ? op[1] : void 0, done: true };
  }
}

var __createBinding = Object.create ? (function(o, m, k, k2) {
  if (k2 === undefined) k2 = k;
  var desc = Object.getOwnPropertyDescriptor(m, k);
  if (!desc || ("get" in desc ? !m.__esModule : desc.writable || desc.configurable)) {
      desc = { enumerable: true, get: function() { return m[k]; } };
  }
  Object.defineProperty(o, k2, desc);
}) : (function(o, m, k, k2) {
  if (k2 === undefined) k2 = k;
  o[k2] = m[k];
});

function __exportStar(m, o) {
  for (var p in m) if (p !== "default" && !Object.prototype.hasOwnProperty.call(o, p)) __createBinding(o, m, p);
}

function __values(o) {
  var s = typeof Symbol === "function" && Symbol.iterator, m = s && o[s], i = 0;
  if (m) return m.call(o);
  if (o && typeof o.length === "number") return {
      next: function () {
          if (o && i >= o.length) o = void 0;
          return { value: o && o[i++], done: !o };
      }
  };
  throw new TypeError(s ? "Object is not iterable." : "Symbol.iterator is not defined.");
}

function __read(o, n) {
  var m = typeof Symbol === "function" && o[Symbol.iterator];
  if (!m) return o;
  var i = m.call(o), r, ar = [], e;
  try {
      while ((n === void 0 || n-- > 0) && !(r = i.next()).done) ar.push(r.value);
  }
  catch (error) { e = { error: error }; }
  finally {
      try {
          if (r && !r.done && (m = i["return"])) m.call(i);
      }
      finally { if (e) throw e.error; }
  }
  return ar;
}

/** @deprecated */
function __spread() {
  for (var ar = [], i = 0; i < arguments.length; i++)
      ar = ar.concat(__read(arguments[i]));
  return ar;
}

/** @deprecated */
function __spreadArrays() {
  for (var s = 0, i = 0, il = arguments.length; i < il; i++) s += arguments[i].length;
  for (var r = Array(s), k = 0, i = 0; i < il; i++)
      for (var a = arguments[i], j = 0, jl = a.length; j < jl; j++, k++)
          r[k] = a[j];
  return r;
}

function __spreadArray(to, from, pack) {
  if (pack || arguments.length === 2) for (var i = 0, l = from.length, ar; i < l; i++) {
      if (ar || !(i in from)) {
          if (!ar) ar = Array.prototype.slice.call(from, 0, i);
          ar[i] = from[i];
      }
  }
  return to.concat(ar || Array.prototype.slice.call(from));
}

function __await(v) {
  return this instanceof __await ? (this.v = v, this) : new __await(v);
}

function __asyncGenerator(thisArg, _arguments, generator) {
  if (!Symbol.asyncIterator) throw new TypeError("Symbol.asyncIterator is not defined.");
  var g = generator.apply(thisArg, _arguments || []), i, q = [];
  return i = {}, verb("next"), verb("throw"), verb("return", awaitReturn), i[Symbol.asyncIterator] = function () { return this; }, i;
  function awaitReturn(f) { return function (v) { return Promise.resolve(v).then(f, reject); }; }
  function verb(n, f) { if (g[n]) { i[n] = function (v) { return new Promise(function (a, b) { q.push([n, v, a, b]) > 1 || resume(n, v); }); }; if (f) i[n] = f(i[n]); } }
  function resume(n, v) { try { step(g[n](v)); } catch (e) { settle(q[0][3], e); } }
  function step(r) { r.value instanceof __await ? Promise.resolve(r.value.v).then(fulfill, reject) : settle(q[0][2], r); }
  function fulfill(value) { resume("next", value); }
  function reject(value) { resume("throw", value); }
  function settle(f, v) { if (f(v), q.shift(), q.length) resume(q[0][0], q[0][1]); }
}

function __asyncDelegator(o) {
  var i, p;
  return i = {}, verb("next"), verb("throw", function (e) { throw e; }), verb("return"), i[Symbol.iterator] = function () { return this; }, i;
  function verb(n, f) { i[n] = o[n] ? function (v) { return (p = !p) ? { value: __await(o[n](v)), done: false } : f ? f(v) : v; } : f; }
}

function __asyncValues(o) {
  if (!Symbol.asyncIterator) throw new TypeError("Symbol.asyncIterator is not defined.");
  var m = o[Symbol.asyncIterator], i;
  return m ? m.call(o) : (o = typeof __values === "function" ? __values(o) : o[Symbol.iterator](), i = {}, verb("next"), verb("throw"), verb("return"), i[Symbol.asyncIterator] = function () { return this; }, i);
  function verb(n) { i[n] = o[n] && function (v) { return new Promise(function (resolve, reject) { v = o[n](v), settle(resolve, reject, v.done, v.value); }); }; }
  function settle(resolve, reject, d, v) { Promise.resolve(v).then(function(v) { resolve({ value: v, done: d }); }, reject); }
}

function __makeTemplateObject(cooked, raw) {
  if (Object.defineProperty) { Object.defineProperty(cooked, "raw", { value: raw }); } else { cooked.raw = raw; }
  return cooked;
};

var __setModuleDefault = Object.create ? (function(o, v) {
  Object.defineProperty(o, "default", { enumerable: true, value: v });
}) : function(o, v) {
  o["default"] = v;
};

function __importStar(mod) {
  if (mod && mod.__esModule) return mod;
  var result = {};
  if (mod != null) for (var k in mod) if (k !== "default" && Object.prototype.hasOwnProperty.call(mod, k)) __createBinding(result, mod, k);
  __setModuleDefault(result, mod);
  return result;
}

function __importDefault(mod) {
  return (mod && mod.__esModule) ? mod : { default: mod };
}

function __classPrivateFieldGet(receiver, state, kind, f) {
  if (kind === "a" && !f) throw new TypeError("Private accessor was defined without a getter");
  if (typeof state === "function" ? receiver !== state || !f : !state.has(receiver)) throw new TypeError("Cannot read private member from an object whose class did not declare it");
  return kind === "m" ? f : kind === "a" ? f.call(receiver) : f ? f.value : state.get(receiver);
}

function __classPrivateFieldSet(receiver, state, value, kind, f) {
  if (kind === "m") throw new TypeError("Private method is not writable");
  if (kind === "a" && !f) throw new TypeError("Private accessor was defined without a setter");
  if (typeof state === "function" ? receiver !== state || !f : !state.has(receiver)) throw new TypeError("Cannot write private member to an object whose class did not declare it");
  return (kind === "a" ? f.call(receiver, value) : f ? f.value = value : state.set(receiver, value)), value;
}

function __classPrivateFieldIn(state, receiver) {
  if (receiver === null || (typeof receiver !== "object" && typeof receiver !== "function")) throw new TypeError("Cannot use 'in' operator on non-object");
  return typeof state === "function" ? receiver === state : state.has(receiver);
}

function __addDisposableResource(env, value, async) {
  if (value !== null && value !== void 0) {
    if (typeof value !== "object" && typeof value !== "function") throw new TypeError("Object expected.");
    var dispose, inner;
    if (async) {
      if (!Symbol.asyncDispose) throw new TypeError("Symbol.asyncDispose is not defined.");
      dispose = value[Symbol.asyncDispose];
    }
    if (dispose === void 0) {
      if (!Symbol.dispose) throw new TypeError("Symbol.dispose is not defined.");
      dispose = value[Symbol.dispose];
      if (async) inner = dispose;
    }
    if (typeof dispose !== "function") throw new TypeError("Object not disposable.");
    if (inner) dispose = function() { try { inner.call(this); } catch (e) { return Promise.reject(e); } };
    env.stack.push({ value: value, dispose: dispose, async: async });
  }
  else if (async) {
    env.stack.push({ async: true });
  }
  return value;
}

var _SuppressedError = typeof SuppressedError === "function" ? SuppressedError : function (error, suppressed, message) {
  var e = new Error(message);
  return e.name = "SuppressedError", e.error = error, e.suppressed = suppressed, e;
};

function __disposeResources(env) {
  function fail(e) {
    env.error = env.hasError ? new _SuppressedError(e, env.error, "An error was suppressed during disposal.") : e;
    env.hasError = true;
  }
  function next() {
    while (env.stack.length) {
      var rec = env.stack.pop();
      try {
        var result = rec.dispose && rec.dispose.call(rec.value);
        if (rec.async) return Promise.resolve(result).then(next, function(e) { fail(e); return next(); });
      }
      catch (e) {
          fail(e);
      }
    }
    if (env.hasError) throw env.error;
  }
  return next();
}

/* unused harmony default export */ var __WEBPACK_DEFAULT_EXPORT__ = ({
  __extends,
  __assign,
  __rest,
  __decorate,
  __param,
  __metadata,
  __awaiter,
  __generator,
  __createBinding,
  __exportStar,
  __values,
  __read,
  __spread,
  __spreadArrays,
  __spreadArray,
  __await,
  __asyncGenerator,
  __asyncDelegator,
  __asyncValues,
  __makeTemplateObject,
  __importStar,
  __importDefault,
  __classPrivateFieldGet,
  __classPrivateFieldSet,
  __classPrivateFieldIn,
  __addDisposableResource,
  __disposeResources,
});


/***/ })

}]);