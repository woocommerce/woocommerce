(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[7811],{

/***/ "../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/slot-fill/bubbles-virtually/slot-fill-context.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (/* binding */ slot_fill_context_default)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var _wordpress_warning__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+warning@3.48.1/node_modules/@wordpress/warning/build-module/index.mjs");
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@7.45.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/compose/build-module/utils/observable-map/index.mjs");



const initialContextValue = {
  slots: (0,_wordpress_compose__WEBPACK_IMPORTED_MODULE_0__/* .observableMap */ .u)(),
  fills: (0,_wordpress_compose__WEBPACK_IMPORTED_MODULE_0__/* .observableMap */ .u)(),
  registerSlot: () => {
    globalThis.SCRIPT_DEBUG === true ? (0,_wordpress_warning__WEBPACK_IMPORTED_MODULE_1__/* ["default"] */ .A)("Components must be wrapped within `SlotFillProvider`. See https://developer.wordpress.org/block-editor/components/slot-fill/") : void 0;
  },
  updateSlot: () => {
  },
  unregisterSlot: () => {
  },
  registerFill: () => {
  },
  unregisterFill: () => {
  },
  // This helps the provider know if it's using the default context value or not.
  isDefault: true
};
const SlotFillContext = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.createContext)(initialContextValue);
SlotFillContext.displayName = "SlotFillContext";
var slot_fill_context_default = SlotFillContext;

//# sourceMappingURL=slot-fill-context.js.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/slot-fill/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";

// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  SQ: () => (/* binding */ slot_fill_Fill),
  Kq: () => (/* binding */ Provider),
  DX: () => (/* binding */ slot_fill_Slot)
});

// UNUSED EXPORTS: UnforwardedSlot, createSlotFill, useSlot, useSlotFills

// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+compose@7.45.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/compose/build-module/utils/observable-map/index.mjs
var observable_map = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@7.45.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/compose/build-module/utils/observable-map/index.mjs");
;// ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/slot-fill/context.js


const initialValue = {
  slots: (0,observable_map/* observableMap */.u)(),
  fills: (0,observable_map/* observableMap */.u)(),
  registerSlot: () => {
  },
  unregisterSlot: () => {
  },
  registerFill: () => {
  },
  unregisterFill: () => {
  },
  updateFill: () => {
  }
};
const SlotFillContext = (0,react.createContext)(initialValue);
SlotFillContext.displayName = "SlotFillContext";
var context_default = SlotFillContext;

//# sourceMappingURL=context.js.map

;// ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/slot-fill/fill.js


function Fill({
  name,
  children
}) {
  const registry = (0,react.useContext)(context_default);
  const instanceRef = (0,react.useRef)({});
  const childrenRef = (0,react.useRef)(children);
  (0,react.useLayoutEffect)(() => {
    childrenRef.current = children;
  }, [children]);
  (0,react.useLayoutEffect)(() => {
    const instance = instanceRef.current;
    registry.registerFill(name, instance, childrenRef.current);
    return () => registry.unregisterFill(name, instance);
  }, [registry, name]);
  (0,react.useLayoutEffect)(() => {
    registry.updateFill(name, instanceRef.current, childrenRef.current);
  });
  return null;
}

//# sourceMappingURL=fill.js.map

// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+compose@7.45.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-observable-value/index.mjs
var use_observable_value = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@7.45.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-observable-value/index.mjs");
;// ../../node_modules/.pnpm/@wordpress+element@6.45.0/node_modules/@wordpress/element/build-module/utils.mjs
// packages/element/src/utils.ts
var isEmptyElement = (element) => {
  if (typeof element === "number") {
    return false;
  }
  if (typeof element?.valueOf() === "string" || Array.isArray(element)) {
    return !element.length;
  }
  return !element;
};

//# sourceMappingURL=utils.mjs.map

// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js
var jsx_runtime = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
;// ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/slot-fill/slot.js




function isFunction(maybeFunc) {
  return typeof maybeFunc === "function";
}
function addKeysToChildren(children) {
  return react.Children.map(children, (child, childIndex) => {
    if (!child || typeof child === "string") {
      return child;
    }
    let childKey = childIndex;
    if (typeof child === "object" && "key" in child && child?.key) {
      childKey = child.key;
    }
    return (0,react.cloneElement)(child, {
      key: childKey
    });
  });
}
function Slot(props) {
  var _useObservableValue;
  const registry = (0,react.useContext)(context_default);
  const instanceRef = (0,react.useRef)({});
  const {
    name,
    children,
    fillProps = {}
  } = props;
  (0,react.useLayoutEffect)(() => {
    const instance = instanceRef.current;
    registry.registerSlot(name, instance);
    return () => registry.unregisterSlot(name, instance);
  }, [registry, name]);
  let fills = (_useObservableValue = (0,use_observable_value/* default */.A)(registry.fills, name)) !== null && _useObservableValue !== void 0 ? _useObservableValue : [];
  const currentSlot = (0,use_observable_value/* default */.A)(registry.slots, name);
  if (currentSlot !== instanceRef.current) {
    fills = [];
  }
  const renderedFills = fills.map((fill) => {
    const fillChildren = isFunction(fill.children) ? fill.children(fillProps) : fill.children;
    return addKeysToChildren(fillChildren);
  }).filter(
    // In some cases fills are rendered only when some conditions apply.
    // This ensures that we only use non-empty fills when rendering, i.e.,
    // it allows us to render wrappers only when the fills are actually present.
    (element) => !isEmptyElement(element)
  );
  return /* @__PURE__ */ (0,jsx_runtime.jsx)(jsx_runtime.Fragment, {
    children: isFunction(children) ? children(renderedFills) : renderedFills
  });
}
var slot_default = Slot;

//# sourceMappingURL=slot.js.map

// EXTERNAL MODULE: ../../node_modules/.pnpm/react-dom@18.3.1_react@18.3.1/node_modules/react-dom/index.js
var react_dom = __webpack_require__("../../node_modules/.pnpm/react-dom@18.3.1_react@18.3.1/node_modules/react-dom/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/slot-fill/bubbles-virtually/slot-fill-context.js
var slot_fill_context = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/slot-fill/bubbles-virtually/slot-fill-context.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/style-provider/index.js
var style_provider = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/style-provider/index.js");
;// ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/slot-fill/bubbles-virtually/fill.js





function fill_Fill({
  name,
  children
}) {
  var _slot$fillProps;
  const registry = (0,react.useContext)(slot_fill_context/* default */.A);
  const slot = (0,use_observable_value/* default */.A)(registry.slots, name);
  const instanceRef = (0,react.useRef)({});
  (0,react.useEffect)(() => {
    const instance = instanceRef.current;
    registry.registerFill(name, instance);
    return () => registry.unregisterFill(name, instance);
  }, [registry, name]);
  if (!slot || !slot.ref.current) {
    return null;
  }
  const wrappedChildren = /* @__PURE__ */ (0,jsx_runtime.jsx)(style_provider/* default */.A, {
    document: slot.ref.current.ownerDocument,
    children: typeof children === "function" ? children((_slot$fillProps = slot.fillProps) !== null && _slot$fillProps !== void 0 ? _slot$fillProps : {}) : children
  });
  return (0,react_dom.createPortal)(wrappedChildren, slot.ref.current);
}

//# sourceMappingURL=fill.js.map

// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+compose@7.45.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-merge-refs/index.mjs
var use_merge_refs = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@7.45.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-merge-refs/index.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/view/component.js
var component = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/view/component.js");
;// ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/slot-fill/bubbles-virtually/slot.js





function slot_Slot(props, forwardedRef) {
  const {
    name,
    fillProps = {},
    as,
    // `children` is not allowed. However, if it is passed,
    // it will be displayed as is, so remove `children`.
    children,
    ...restProps
  } = props;
  const registry = (0,react.useContext)(slot_fill_context/* default */.A);
  const ref = (0,react.useRef)(null);
  const fillPropsRef = (0,react.useRef)(fillProps);
  (0,react.useLayoutEffect)(() => {
    fillPropsRef.current = fillProps;
  }, [fillProps]);
  (0,react.useLayoutEffect)(() => {
    registry.registerSlot(name, ref, fillPropsRef.current);
    return () => registry.unregisterSlot(name, ref);
  }, [registry, name]);
  (0,react.useLayoutEffect)(() => {
    registry.updateSlot(name, ref, fillPropsRef.current);
  });
  return /* @__PURE__ */ (0,jsx_runtime.jsx)(component/* default */.A, {
    as,
    ref: (0,use_merge_refs/* default */.A)([forwardedRef, ref]),
    ...restProps
  });
}
var slot_slot_default = (0,react.forwardRef)(slot_Slot);

//# sourceMappingURL=slot.js.map

;// ../../node_modules/.pnpm/@wordpress+is-shallow-equal@5.48.1/node_modules/@wordpress/is-shallow-equal/build-module/objects.mjs
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

;// ../../node_modules/.pnpm/@wordpress+is-shallow-equal@5.48.1/node_modules/@wordpress/is-shallow-equal/build-module/arrays.mjs
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

;// ../../node_modules/.pnpm/@wordpress+is-shallow-equal@5.48.1/node_modules/@wordpress/is-shallow-equal/build-module/index.mjs
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

;// ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/slot-fill/bubbles-virtually/slot-fill-provider.js





function createSlotRegistry() {
  const slots = (0,observable_map/* observableMap */.u)();
  const fills = (0,observable_map/* observableMap */.u)();
  const registerSlot = (name, ref, fillProps) => {
    slots.set(name, {
      ref,
      fillProps
    });
  };
  const unregisterSlot = (name, ref) => {
    const slot = slots.get(name);
    if (!slot) {
      return;
    }
    if (slot.ref !== ref) {
      return;
    }
    slots.delete(name);
  };
  const updateSlot = (name, ref, fillProps) => {
    const slot = slots.get(name);
    if (!slot) {
      return;
    }
    if (slot.ref !== ref) {
      return;
    }
    if (isShallowEqual(slot.fillProps, fillProps)) {
      return;
    }
    slots.set(name, {
      ref,
      fillProps
    });
  };
  const registerFill = (name, ref) => {
    fills.set(name, [...fills.get(name) || [], ref]);
  };
  const unregisterFill = (name, ref) => {
    const fillsForName = fills.get(name);
    if (!fillsForName) {
      return;
    }
    fills.set(name, fillsForName.filter((fillRef) => fillRef !== ref));
  };
  return {
    slots,
    fills,
    registerSlot,
    updateSlot,
    unregisterSlot,
    registerFill,
    unregisterFill
  };
}
function SlotFillProvider({
  children
}) {
  const [registry] = (0,react.useState)(createSlotRegistry);
  return /* @__PURE__ */ (0,jsx_runtime.jsx)(slot_fill_context/* default */.A.Provider, {
    value: registry,
    children
  });
}

//# sourceMappingURL=slot-fill-provider.js.map

;// ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/slot-fill/provider.js




function provider_createSlotRegistry() {
  const slots = (0,observable_map/* observableMap */.u)();
  const fills = (0,observable_map/* observableMap */.u)();
  function registerSlot(name, instance) {
    slots.set(name, instance);
  }
  function unregisterSlot(name, instance) {
    if (slots.get(name) !== instance) {
      return;
    }
    slots.delete(name);
  }
  function registerFill(name, instance, children) {
    fills.set(name, [...fills.get(name) || [], {
      instance,
      children
    }]);
  }
  function unregisterFill(name, instance) {
    const fillsForName = fills.get(name);
    if (!fillsForName) {
      return;
    }
    fills.set(name, fillsForName.filter((fill) => fill.instance !== instance));
  }
  function updateFill(name, instance, children) {
    const fillsForName = fills.get(name);
    if (!fillsForName) {
      return;
    }
    const fillForInstance = fillsForName.find((f) => f.instance === instance);
    if (!fillForInstance) {
      return;
    }
    if (fillForInstance.children === children) {
      return;
    }
    fills.set(name, fillsForName.map((f) => {
      if (f.instance === instance) {
        return {
          instance,
          children
        };
      }
      return f;
    }));
  }
  return {
    slots,
    fills,
    registerSlot,
    unregisterSlot,
    registerFill,
    unregisterFill,
    updateFill
  };
}
function provider_SlotFillProvider({
  children
}) {
  const [contextValue] = (0,react.useState)(provider_createSlotRegistry);
  return /* @__PURE__ */ (0,jsx_runtime.jsx)(context_default.Provider, {
    value: contextValue,
    children
  });
}
var provider_default = provider_SlotFillProvider;

//# sourceMappingURL=provider.js.map

;// ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/slot-fill/index.js











function slot_fill_Fill(props) {
  return /* @__PURE__ */ (0,jsx_runtime.jsxs)(jsx_runtime.Fragment, {
    children: [/* @__PURE__ */ (0,jsx_runtime.jsx)(Fill, {
      ...props
    }), /* @__PURE__ */ (0,jsx_runtime.jsx)(fill_Fill, {
      ...props
    })]
  });
}
function UnforwardedSlot(props, ref) {
  const {
    bubblesVirtually,
    ...restProps
  } = props;
  if (bubblesVirtually) {
    return /* @__PURE__ */ (0,jsx_runtime.jsx)(slot_slot_default, {
      ...restProps,
      ref
    });
  }
  return /* @__PURE__ */ (0,jsx_runtime.jsx)(slot_default, {
    ...restProps
  });
}
const slot_fill_Slot = (0,react.forwardRef)(UnforwardedSlot);
function Provider({
  children,
  passthrough = false
}) {
  const parent = (0,react.useContext)(slot_fill_context/* default */.A);
  if (!parent.isDefault && passthrough) {
    return /* @__PURE__ */ (0,jsx_runtime.jsx)(jsx_runtime.Fragment, {
      children
    });
  }
  return /* @__PURE__ */ (0,jsx_runtime.jsx)(provider_default, {
    children: /* @__PURE__ */ (0,jsx_runtime.jsx)(SlotFillProvider, {
      children
    })
  });
}
Provider.displayName = "SlotFillProvider";
function createSlotFill(key) {
  const baseName = typeof key === "symbol" ? key.description : key;
  const FillComponent = (props) => /* @__PURE__ */ _jsx(slot_fill_Fill, {
    name: key,
    ...props
  });
  FillComponent.displayName = `${baseName}Fill`;
  const SlotComponent = (props) => /* @__PURE__ */ _jsx(slot_fill_Slot, {
    name: key,
    ...props
  });
  SlotComponent.displayName = `${baseName}Slot`;
  SlotComponent.__unstableName = key;
  return {
    name: key,
    Fill: FillComponent,
    Slot: SlotComponent
  };
}

//# sourceMappingURL=index.js.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/style-provider/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (/* binding */ style_provider_default),
/* harmony export */   N: () => (/* binding */ StyleProvider)
/* harmony export */ });
/* harmony import */ var _emotion_react__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/@emotion+react@11.14.0_@types+react@18.3.28_react@18.3.1/node_modules/@emotion/react/dist/emotion-element-f0de968e.browser.esm.js");
/* harmony import */ var _emotion_cache__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/@emotion+cache@11.14.0/node_modules/@emotion/cache/dist/emotion-cache.browser.esm.js");
/* harmony import */ var uuid__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/uuid@9.0.1/node_modules/uuid/dist/esm-browser/v4.js");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");




const uuidCache = /* @__PURE__ */ new Set();
const containerCacheMap = /* @__PURE__ */ new WeakMap();
const memoizedCreateCacheWithContainer = (container) => {
  if (containerCacheMap.has(container)) {
    return containerCacheMap.get(container);
  }
  let key = uuid__WEBPACK_IMPORTED_MODULE_2__/* ["default"] */ .A().replace(/[0-9]/g, "");
  while (uuidCache.has(key)) {
    key = uuid__WEBPACK_IMPORTED_MODULE_2__/* ["default"] */ .A().replace(/[0-9]/g, "");
  }
  uuidCache.add(key);
  const cache = (0,_emotion_cache__WEBPACK_IMPORTED_MODULE_0__/* ["default"] */ .A)({
    container,
    key
  });
  containerCacheMap.set(container, cache);
  return cache;
};
function StyleProvider(props) {
  const {
    children,
    document
  } = props;
  if (!document) {
    return null;
  }
  const cache = memoizedCreateCacheWithContainer(document.head);
  return /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(_emotion_react__WEBPACK_IMPORTED_MODULE_3__.C, {
    value: cache,
    children
  });
}
var style_provider_default = StyleProvider;

//# sourceMappingURL=index.js.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+compose@7.45.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-merge-refs/index.mjs":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (/* binding */ useMergeRefs)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// packages/compose/src/hooks/use-merge-refs/index.ts

function assignRef(ref, value) {
  if (typeof ref === "function") {
    ref(value);
  } else if (ref && ref.hasOwnProperty("current")) {
    ref.current = value;
  }
}
function useMergeRefs(refs) {
  const element = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useRef)(null);
  const isAttachedRef = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useRef)(false);
  const didElementChangeRef = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useRef)(false);
  const previousRefsRef = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useRef)([]);
  const currentRefsRef = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useRef)(refs);
  currentRefsRef.current = refs;
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useLayoutEffect)(() => {
    if (didElementChangeRef.current === false && isAttachedRef.current === true) {
      refs.forEach((ref, index) => {
        const previousRef = previousRefsRef.current[index];
        if (ref !== previousRef) {
          assignRef(previousRef, null);
          assignRef(ref, element.current);
        }
      });
    }
    previousRefsRef.current = refs;
  }, refs);
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useLayoutEffect)(() => {
    didElementChangeRef.current = false;
  });
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useCallback)((value) => {
    assignRef(element, value);
    didElementChangeRef.current = true;
    isAttachedRef.current = value !== null;
    const refsToAssign = value ? currentRefsRef.current : previousRefsRef.current;
    for (const ref of refsToAssign) {
      assignRef(ref, value);
    }
  }, []);
}

//# sourceMappingURL=index.mjs.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+compose@7.45.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-observable-value/index.mjs":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (/* binding */ useObservableValue)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// packages/compose/src/hooks/use-observable-value/index.ts

function useObservableValue(map, name) {
  const [subscribe, getValue] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useMemo)(
    () => [
      (listener) => map.subscribe(name, listener),
      () => map.get(name)
    ],
    [map, name]
  );
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useSyncExternalStore)(subscribe, getValue, getValue);
}

//# sourceMappingURL=index.mjs.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+compose@7.45.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/compose/build-module/utils/observable-map/index.mjs":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   u: () => (/* binding */ observableMap)
/* harmony export */ });
// packages/compose/src/utils/observable-map/index.ts
function observableMap() {
  const map = /* @__PURE__ */ new Map();
  const listeners = /* @__PURE__ */ new Map();
  function callListeners(name) {
    const list = listeners.get(name);
    if (!list) {
      return;
    }
    for (const listener of list) {
      listener();
    }
  }
  return {
    get(name) {
      return map.get(name);
    },
    set(name, value) {
      map.set(name, value);
      callListeners(name);
    },
    delete(name) {
      map.delete(name);
      callListeners(name);
    },
    subscribe(name, listener) {
      let list = listeners.get(name);
      if (!list) {
        list = /* @__PURE__ */ new Set();
        listeners.set(name, list);
      }
      list.add(listener);
      return () => {
        list.delete(listener);
        if (list.size === 0) {
          listeners.delete(name);
        }
      };
    }
  };
}

//# sourceMappingURL=index.mjs.map


/***/ }),

/***/ "../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* unused harmony export clsx */
function r(e){var t,f,n="";if("string"==typeof e||"number"==typeof e)n+=e;else if("object"==typeof e)if(Array.isArray(e)){var o=e.length;for(t=0;t<o;t++)e[t]&&(f=r(e[t]))&&(n&&(n+=" "),n+=f)}else for(f in e)e[f]&&(n&&(n+=" "),n+=f);return n}function clsx(){for(var e,t,f=0,n="",o=arguments.length;f<o;f++)(e=arguments[f])&&(t=r(e))&&(n&&(n+=" "),n+=t);return n}/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (clsx);

/***/ }),

/***/ "../../node_modules/.pnpm/debug@4.4.3_supports-color@8.1.1/node_modules/debug/src/browser.js":
/***/ ((module, exports, __webpack_require__) => {

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

	let m;

	// Is webkit? http://stackoverflow.com/a/16459606/376773
	// document is undefined in react-native: https://github.com/facebook/react-native/pull/1632
	// eslint-disable-next-line no-return-assign
	return (typeof document !== 'undefined' && document.documentElement && document.documentElement.style && document.documentElement.style.WebkitAppearance) ||
		// Is firebug? http://stackoverflow.com/a/398120/376773
		(typeof window !== 'undefined' && window.console && (window.console.firebug || (window.console.exception && window.console.table))) ||
		// Is firefox >= v31?
		// https://developer.mozilla.org/en-US/docs/Tools/Web_Console#Styling_messages
		(typeof navigator !== 'undefined' && navigator.userAgent && (m = navigator.userAgent.toLowerCase().match(/firefox\/(\d+)/)) && parseInt(m[1], 10) >= 31) ||
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
		r = exports.storage.getItem('debug') || exports.storage.getItem('DEBUG') ;
	} catch (error) {
		// Swallow
		// XXX (@Qix-) should we be logging these?
	}

	// If debug isn't set in LS, and we're in Electron, try to load $DEBUG
	if (!r && typeof process !== 'undefined' && 'env' in process) {
		r = ({"NODE_ENV":"production","NODE_PATH":["/home/runner/work/woocommerce/woocommerce/node_modules/.pnpm/storybook@10.5.10_@types+re_e57dec9e0ceed48a12a3cddc58799718/node_modules/storybook/node_modules","/home/runner/work/woocommerce/woocommerce/node_modules/.pnpm/storybook@10.5.10_@types+re_e57dec9e0ceed48a12a3cddc58799718/node_modules","/home/runner/work/woocommerce/woocommerce/node_modules/.pnpm/node_modules"],"STORYBOOK":"true","PUBLIC_URL":".","STORYBOOK_COMPOSITION_PATH_PREFIX":"/woocommerce"}).DEBUG;
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

module.exports = __webpack_require__("../../node_modules/.pnpm/debug@4.4.3_supports-color@8.1.1/node_modules/debug/src/common.js")(exports);

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

/***/ "../../node_modules/.pnpm/debug@4.4.3_supports-color@8.1.1/node_modules/debug/src/common.js":
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
	createDebug.humanize = __webpack_require__("../../node_modules/.pnpm/ms@2.1.3/node_modules/ms/index.js");
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

		const split = (typeof namespaces === 'string' ? namespaces : '')
			.trim()
			.replace(/\s+/g, ',')
			.split(',')
			.filter(Boolean);

		for (const ns of split) {
			if (ns[0] === '-') {
				createDebug.skips.push(ns.slice(1));
			} else {
				createDebug.names.push(ns);
			}
		}
	}

	/**
	 * Checks if the given string matches a namespace template, honoring
	 * asterisks as wildcards.
	 *
	 * @param {String} search
	 * @param {String} template
	 * @return {Boolean}
	 */
	function matchesTemplate(search, template) {
		let searchIndex = 0;
		let templateIndex = 0;
		let starIndex = -1;
		let matchIndex = 0;

		while (searchIndex < search.length) {
			if (templateIndex < template.length && (template[templateIndex] === search[searchIndex] || template[templateIndex] === '*')) {
				// Match character or proceed with wildcard
				if (template[templateIndex] === '*') {
					starIndex = templateIndex;
					matchIndex = searchIndex;
					templateIndex++; // Skip the '*'
				} else {
					searchIndex++;
					templateIndex++;
				}
			} else if (starIndex !== -1) { // eslint-disable-line no-negated-condition
				// Backtrack to the last '*' and try to match more characters
				templateIndex = starIndex + 1;
				matchIndex++;
				searchIndex = matchIndex;
			} else {
				return false; // No match
			}
		}

		// Handle trailing '*' in template
		while (templateIndex < template.length && template[templateIndex] === '*') {
			templateIndex++;
		}

		return templateIndex === template.length;
	}

	/**
	* Disable debug output.
	*
	* @return {String} namespaces
	* @api public
	*/
	function disable() {
		const namespaces = [
			...createDebug.names,
			...createDebug.skips.map(namespace => '-' + namespace)
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
		for (const skip of createDebug.skips) {
			if (matchesTemplate(name, skip)) {
				return false;
			}
		}

		for (const ns of createDebug.names) {
			if (matchesTemplate(name, ns)) {
				return true;
			}
		}

		return false;
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

/***/ "../../node_modules/.pnpm/gridicons@3.4.2_react@18.3.1/node_modules/gridicons/dist/notice-outline.js":
/***/ ((__unused_webpack_module, exports, __webpack_require__) => {

"use strict";
var __webpack_unused_export__;
__webpack_unused_export__ = ({value:!0}),exports.A=_default;var _react=_interopRequireDefault(__webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js")),_excluded=["size","onClick","icon","className"];function _interopRequireDefault(a){return a&&a.__esModule?a:{default:a}}function _extends(){return _extends=Object.assign?Object.assign.bind():function(a){for(var b,c=1;c<arguments.length;c++)for(var d in b=arguments[c],b)Object.prototype.hasOwnProperty.call(b,d)&&(a[d]=b[d]);return a},_extends.apply(this,arguments)}function _objectWithoutProperties(a,b){if(null==a)return{};var c,d,e=_objectWithoutPropertiesLoose(a,b);if(Object.getOwnPropertySymbols){var f=Object.getOwnPropertySymbols(a);for(d=0;d<f.length;d++)c=f[d],0<=b.indexOf(c)||Object.prototype.propertyIsEnumerable.call(a,c)&&(e[c]=a[c])}return e}function _objectWithoutPropertiesLoose(a,b){if(null==a)return{};var c,d,e={},f=Object.keys(a);for(d=0;d<f.length;d++)c=f[d],0<=b.indexOf(c)||(e[c]=a[c]);return e}function _default(a){var b=a.size,c=void 0===b?24:b,d=a.onClick,e=a.icon,f=a.className,g=_objectWithoutProperties(a,_excluded),h=["gridicon","gridicons-notice-outline",f,!!function isModulo18(a){return 0==a%18}(c)&&"needs-offset",!1,!1].filter(Boolean).join(" ");return _react["default"].createElement("svg",_extends({className:h,height:c,width:c,onClick:d},g,{xmlns:"http://www.w3.org/2000/svg",viewBox:"0 0 24 24"}),_react["default"].createElement("g",null,_react["default"].createElement("path",{d:"M12 4c4.411 0 8 3.589 8 8s-3.589 8-8 8-8-3.589-8-8 3.589-8 8-8m0-2C6.477 2 2 6.477 2 12s4.477 10 10 10 10-4.477 10-10S17.523 2 12 2zm1 13h-2v2h2v-2zm-2-2h2l.5-6h-3l.5 6z"})))}


/***/ }),

/***/ "../../node_modules/.pnpm/ms@2.1.3/node_modules/ms/index.js":
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

module.exports = function (val, options) {
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

/***/ "../../node_modules/.pnpm/string-similarity@4.0.4/node_modules/string-similarity/src/index.js":
/***/ ((module) => {

module.exports = {
	compareTwoStrings:compareTwoStrings,
	findBestMatch:findBestMatch
};

function compareTwoStrings(first, second) {
	first = first.replace(/\s+/g, '')
	second = second.replace(/\s+/g, '')

	if (first === second) return 1; // identical or empty
	if (first.length < 2 || second.length < 2) return 0; // if either is a 0-letter or 1-letter string

	let firstBigrams = new Map();
	for (let i = 0; i < first.length - 1; i++) {
		const bigram = first.substring(i, i + 2);
		const count = firstBigrams.has(bigram)
			? firstBigrams.get(bigram) + 1
			: 1;

		firstBigrams.set(bigram, count);
	};

	let intersectionSize = 0;
	for (let i = 0; i < second.length - 1; i++) {
		const bigram = second.substring(i, i + 2);
		const count = firstBigrams.has(bigram)
			? firstBigrams.get(bigram)
			: 0;

		if (count > 0) {
			firstBigrams.set(bigram, count - 1);
			intersectionSize++;
		}
	}

	return (2.0 * intersectionSize) / (first.length + second.length - 2);
}

function findBestMatch(mainString, targetStrings) {
	if (!areArgsValid(mainString, targetStrings)) throw new Error('Bad arguments: First argument should be a string, second should be an array of strings');
	
	const ratings = [];
	let bestMatchIndex = 0;

	for (let i = 0; i < targetStrings.length; i++) {
		const currentTargetString = targetStrings[i];
		const currentRating = compareTwoStrings(mainString, currentTargetString)
		ratings.push({target: currentTargetString, rating: currentRating})
		if (currentRating > ratings[bestMatchIndex].rating) {
			bestMatchIndex = i
		}
	}
	
	
	const bestMatch = ratings[bestMatchIndex]
	
	return { ratings: ratings, bestMatch: bestMatch, bestMatchIndex: bestMatchIndex };
}

function areArgsValid(mainString, targetStrings) {
	if (typeof mainString !== 'string') return false;
	if (!Array.isArray(targetStrings)) return false;
	if (!targetStrings.length) return false;
	if (targetStrings.find( function (s) { return typeof s !== 'string'})) return false;
	return true;
}


/***/ }),

/***/ "../../node_modules/.pnpm/uuid@9.0.1/node_modules/uuid/dist/esm-browser/v4.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";

// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  A: () => (/* binding */ esm_browser_v4)
});

;// ../../node_modules/.pnpm/uuid@9.0.1/node_modules/uuid/dist/esm-browser/native.js
const randomUUID = typeof crypto !== 'undefined' && crypto.randomUUID && crypto.randomUUID.bind(crypto);
/* harmony default export */ const esm_browser_native = ({
  randomUUID
});
;// ../../node_modules/.pnpm/uuid@9.0.1/node_modules/uuid/dist/esm-browser/rng.js
// Unique ID creation requires a high quality random # generator. In the browser we therefore
// require the crypto API and do not support built-in fallback to lower quality random number
// generators (like Math.random()).
let getRandomValues;
const rnds8 = new Uint8Array(16);
function rng() {
  // lazy load so that environments that need to polyfill have a chance to do so
  if (!getRandomValues) {
    // getRandomValues needs to be invoked in a context where "this" is a Crypto implementation.
    getRandomValues = typeof crypto !== 'undefined' && crypto.getRandomValues && crypto.getRandomValues.bind(crypto);

    if (!getRandomValues) {
      throw new Error('crypto.getRandomValues() not supported. See https://github.com/uuidjs/uuid#getrandomvalues-not-supported');
    }
  }

  return getRandomValues(rnds8);
}
;// ../../node_modules/.pnpm/uuid@9.0.1/node_modules/uuid/dist/esm-browser/stringify.js

/**
 * Convert array of 16 byte values to UUID string format of the form:
 * XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX
 */

const byteToHex = [];

for (let i = 0; i < 256; ++i) {
  byteToHex.push((i + 0x100).toString(16).slice(1));
}

function unsafeStringify(arr, offset = 0) {
  // Note: Be careful editing this code!  It's been tuned for performance
  // and works in ways you may not expect. See https://github.com/uuidjs/uuid/pull/434
  return byteToHex[arr[offset + 0]] + byteToHex[arr[offset + 1]] + byteToHex[arr[offset + 2]] + byteToHex[arr[offset + 3]] + '-' + byteToHex[arr[offset + 4]] + byteToHex[arr[offset + 5]] + '-' + byteToHex[arr[offset + 6]] + byteToHex[arr[offset + 7]] + '-' + byteToHex[arr[offset + 8]] + byteToHex[arr[offset + 9]] + '-' + byteToHex[arr[offset + 10]] + byteToHex[arr[offset + 11]] + byteToHex[arr[offset + 12]] + byteToHex[arr[offset + 13]] + byteToHex[arr[offset + 14]] + byteToHex[arr[offset + 15]];
}

function stringify(arr, offset = 0) {
  const uuid = unsafeStringify(arr, offset); // Consistency check for valid UUID.  If this throws, it's likely due to one
  // of the following:
  // - One or more input array values don't map to a hex octet (leading to
  // "undefined" in the uuid)
  // - Invalid input values for the RFC `version` or `variant` fields

  if (!validate(uuid)) {
    throw TypeError('Stringified UUID is invalid');
  }

  return uuid;
}

/* harmony default export */ const esm_browser_stringify = ((/* unused pure expression or super */ null && (stringify)));
;// ../../node_modules/.pnpm/uuid@9.0.1/node_modules/uuid/dist/esm-browser/v4.js




function v4(options, buf, offset) {
  if (esm_browser_native.randomUUID && !buf && !options) {
    return esm_browser_native.randomUUID();
  }

  options = options || {};
  const rnds = options.random || (options.rng || rng)(); // Per 4.4, set bits for version and `clock_seq_hi_and_reserved`

  rnds[6] = rnds[6] & 0x0f | 0x40;
  rnds[8] = rnds[8] & 0x3f | 0x80; // Copy bytes to buffer, if provided

  if (buf) {
    offset = offset || 0;

    for (let i = 0; i < 16; ++i) {
      buf[offset + i] = rnds[i];
    }

    return buf;
  }

  return unsafeStringify(rnds);
}

/* harmony default export */ const esm_browser_v4 = (v4);

/***/ })

}]);