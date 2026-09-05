"use strict";
(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[557],{

/***/ "../../node_modules/.pnpm/@ariakit+core@0.4.19/node_modules/@ariakit/core/esm/__chunks/G7XPWBXK.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   Bj: () => (/* binding */ isTextbox),
/* harmony export */   Bm: () => (/* binding */ isButton),
/* harmony export */   Mk: () => (/* binding */ getTextboxValue),
/* harmony export */   Sw: () => (/* binding */ canUseDOM),
/* harmony export */   YE: () => (/* binding */ getDocument),
/* harmony export */   Zy: () => (/* binding */ getTextboxSelection),
/* harmony export */   bq: () => (/* binding */ getActiveElement),
/* harmony export */   gH: () => (/* binding */ sortBasedOnDOMPosition),
/* harmony export */   gR: () => (/* binding */ contains),
/* harmony export */   mB: () => (/* binding */ isTextField),
/* harmony export */   qj: () => (/* binding */ getScrollingElement),
/* harmony export */   qt: () => (/* binding */ isFrame),
/* harmony export */   zN: () => (/* binding */ isVisible),
/* harmony export */   zk: () => (/* binding */ getWindow)
/* harmony export */ });
/* unused harmony exports getPopupRole, getPopupItemRole, scrollIntoViewIfNeeded, isPartiallyHidden, setSelectionRange */
"use client";

// src/utils/dom.ts
var canUseDOM = checkIsBrowser();
function checkIsBrowser() {
  var _a;
  return typeof window !== "undefined" && !!((_a = window.document) == null ? void 0 : _a.createElement);
}
function getDocument(node) {
  if (!node) return document;
  if ("self" in node) return node.document;
  return node.ownerDocument || document;
}
function getWindow(node) {
  if (!node) return self;
  if ("self" in node) return node.self;
  return getDocument(node).defaultView || window;
}
function getActiveElement(node, activeDescendant = false) {
  var _a;
  const { activeElement } = getDocument(node);
  if (!(activeElement == null ? void 0 : activeElement.nodeName)) {
    return null;
  }
  if (isFrame(activeElement) && ((_a = activeElement.contentDocument) == null ? void 0 : _a.body)) {
    return getActiveElement(
      activeElement.contentDocument.body,
      activeDescendant
    );
  }
  if (activeDescendant) {
    const id = activeElement.getAttribute("aria-activedescendant");
    if (id) {
      const element = getDocument(activeElement).getElementById(id);
      if (element) {
        return element;
      }
    }
  }
  return activeElement;
}
function contains(parent, child) {
  return parent === child || parent.contains(child);
}
function isFrame(element) {
  return element.tagName === "IFRAME";
}
function isButton(element) {
  const tagName = element.tagName.toLowerCase();
  if (tagName === "button") return true;
  if (tagName === "input" && element.type) {
    return buttonInputTypes.indexOf(element.type) !== -1;
  }
  return false;
}
var buttonInputTypes = [
  "button",
  "color",
  "file",
  "image",
  "reset",
  "submit"
];
function isVisible(element) {
  if (typeof element.checkVisibility === "function") {
    return element.checkVisibility();
  }
  const htmlElement = element;
  return htmlElement.offsetWidth > 0 || htmlElement.offsetHeight > 0 || element.getClientRects().length > 0;
}
function isTextField(element) {
  try {
    const isTextInput = element instanceof HTMLInputElement && element.selectionStart !== null;
    const isTextArea = element.tagName === "TEXTAREA";
    return isTextInput || isTextArea || false;
  } catch (_error) {
    return false;
  }
}
function isTextbox(element) {
  return element.isContentEditable || isTextField(element);
}
function getTextboxValue(element) {
  if (isTextField(element)) {
    return element.value;
  }
  if (element.isContentEditable) {
    const range = getDocument(element).createRange();
    range.selectNodeContents(element);
    return range.toString();
  }
  return "";
}
function getTextboxSelection(element) {
  let start = 0;
  let end = 0;
  if (isTextField(element)) {
    start = element.selectionStart || 0;
    end = element.selectionEnd || 0;
  } else if (element.isContentEditable) {
    const selection = getDocument(element).getSelection();
    if ((selection == null ? void 0 : selection.rangeCount) && selection.anchorNode && contains(element, selection.anchorNode) && selection.focusNode && contains(element, selection.focusNode)) {
      const range = selection.getRangeAt(0);
      const nextRange = range.cloneRange();
      nextRange.selectNodeContents(element);
      nextRange.setEnd(range.startContainer, range.startOffset);
      start = nextRange.toString().length;
      nextRange.setEnd(range.endContainer, range.endOffset);
      end = nextRange.toString().length;
    }
  }
  return { start, end };
}
function getPopupRole(element, fallback) {
  const allowedPopupRoles = ["dialog", "menu", "listbox", "tree", "grid"];
  const role = element == null ? void 0 : element.getAttribute("role");
  if (role && allowedPopupRoles.indexOf(role) !== -1) {
    return role;
  }
  return fallback;
}
function getPopupItemRole(element, fallback) {
  const itemRoleByPopupRole = {
    menu: "menuitem",
    listbox: "option",
    tree: "treeitem"
  };
  const popupRole = getPopupRole(element);
  if (!popupRole) return fallback;
  const key = popupRole;
  return itemRoleByPopupRole[key] ?? fallback;
}
function scrollIntoViewIfNeeded(element, arg) {
  if (isPartiallyHidden(element) && "scrollIntoView" in element) {
    element.scrollIntoView(arg);
  }
}
function getScrollingElement(element) {
  if (!element) return null;
  const isScrollableOverflow = (overflow) => {
    if (overflow === "auto") return true;
    if (overflow === "scroll") return true;
    return false;
  };
  if (element.clientHeight && element.scrollHeight > element.clientHeight) {
    const { overflowY } = getComputedStyle(element);
    if (isScrollableOverflow(overflowY)) return element;
  } else if (element.clientWidth && element.scrollWidth > element.clientWidth) {
    const { overflowX } = getComputedStyle(element);
    if (isScrollableOverflow(overflowX)) return element;
  }
  return getScrollingElement(element.parentElement) || document.scrollingElement || document.body;
}
function isPartiallyHidden(element) {
  const elementRect = element.getBoundingClientRect();
  const scroller = getScrollingElement(element);
  if (!scroller) return false;
  const scrollerRect = scroller.getBoundingClientRect();
  const isHTML = scroller.tagName === "HTML";
  const scrollerTop = isHTML ? scrollerRect.top + scroller.scrollTop : scrollerRect.top;
  const scrollerBottom = isHTML ? scroller.clientHeight : scrollerRect.bottom;
  const scrollerLeft = isHTML ? scrollerRect.left + scroller.scrollLeft : scrollerRect.left;
  const scrollerRight = isHTML ? scroller.clientWidth : scrollerRect.right;
  const top = elementRect.top < scrollerTop;
  const left = elementRect.left < scrollerLeft;
  const bottom = elementRect.bottom > scrollerBottom;
  const right = elementRect.right > scrollerRight;
  return top || left || bottom || right;
}
function setSelectionRange(element, ...args) {
  if (/text|search|password|tel|url/i.test(element.type)) {
    element.setSelectionRange(...args);
  }
}
function sortBasedOnDOMPosition(items, getElement) {
  const pairs = items.map((item, index) => [index, item]);
  let isOrderDifferent = false;
  pairs.sort(([indexA, a], [indexB, b]) => {
    const elementA = getElement(a);
    const elementB = getElement(b);
    if (elementA === elementB) return 0;
    if (!elementA || !elementB) return 0;
    if (isElementPreceding(elementA, elementB)) {
      if (indexA > indexB) {
        isOrderDifferent = true;
      }
      return -1;
    }
    if (indexA < indexB) {
      isOrderDifferent = true;
    }
    return 1;
  });
  if (isOrderDifferent) {
    return pairs.map(([_, item]) => item);
  }
  return items;
}
function isElementPreceding(a, b) {
  return Boolean(
    b.compareDocumentPosition(a) & Node.DOCUMENT_POSITION_PRECEDING
  );
}




/***/ }),

/***/ "../../node_modules/.pnpm/@ariakit+core@0.4.19/node_modules/@ariakit/core/esm/__chunks/GMGLSF2B.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   cX: () => (/* binding */ isMac),
/* harmony export */   f8: () => (/* binding */ isApple),
/* harmony export */   gm: () => (/* binding */ isFirefox),
/* harmony export */   nr: () => (/* binding */ isSafari)
/* harmony export */ });
/* unused harmony export isTouchDevice */
/* harmony import */ var _G7XPWBXK_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+core@0.4.19/node_modules/@ariakit/core/esm/__chunks/G7XPWBXK.js");
"use client";


// src/utils/platform.ts
function isTouchDevice() {
  return _G7XPWBXK_js__WEBPACK_IMPORTED_MODULE_0__/* .canUseDOM */ .Sw && !!navigator.maxTouchPoints;
}
function isApple() {
  if (!_G7XPWBXK_js__WEBPACK_IMPORTED_MODULE_0__/* .canUseDOM */ .Sw) return false;
  return /mac|iphone|ipad|ipod/i.test(navigator.platform);
}
function isSafari() {
  return _G7XPWBXK_js__WEBPACK_IMPORTED_MODULE_0__/* .canUseDOM */ .Sw && isApple() && /apple/i.test(navigator.vendor);
}
function isFirefox() {
  return _G7XPWBXK_js__WEBPACK_IMPORTED_MODULE_0__/* .canUseDOM */ .Sw && /firefox\//i.test(navigator.userAgent);
}
function isMac() {
  return _G7XPWBXK_js__WEBPACK_IMPORTED_MODULE_0__/* .canUseDOM */ .Sw && navigator.platform.startsWith("Mac") && !isTouchDevice();
}




/***/ }),

/***/ "../../node_modules/.pnpm/@ariakit+core@0.4.19/node_modules/@ariakit/core/esm/__chunks/IHNLLH3I.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   h: () => (/* binding */ createDisclosureStore)
/* harmony export */ });
/* harmony import */ var _XTZ53NXG_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+core@0.4.19/node_modules/@ariakit/core/esm/__chunks/XTZ53NXG.js");
/* harmony import */ var _UWJK2WK2_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+core@0.4.19/node_modules/@ariakit/core/esm/__chunks/UWJK2WK2.js");
"use client";



// src/disclosure/disclosure-store.ts
function createDisclosureStore(props = {}) {
  const store = (0,_XTZ53NXG_js__WEBPACK_IMPORTED_MODULE_0__/* .mergeStore */ .od)(
    props.store,
    (0,_XTZ53NXG_js__WEBPACK_IMPORTED_MODULE_0__/* .omit */ .cJ)(props.disclosure, ["contentElement", "disclosureElement"])
  );
  (0,_XTZ53NXG_js__WEBPACK_IMPORTED_MODULE_0__/* .throwOnConflictingProps */ .UE)(props, store);
  const syncState = store == null ? void 0 : store.getState();
  const open = (0,_UWJK2WK2_js__WEBPACK_IMPORTED_MODULE_1__/* .defaultValue */ .Jh)(
    props.open,
    syncState == null ? void 0 : syncState.open,
    props.defaultOpen,
    false
  );
  const animated = (0,_UWJK2WK2_js__WEBPACK_IMPORTED_MODULE_1__/* .defaultValue */ .Jh)(props.animated, syncState == null ? void 0 : syncState.animated, false);
  const initialState = {
    open,
    animated,
    animating: !!animated && open,
    mounted: open,
    contentElement: (0,_UWJK2WK2_js__WEBPACK_IMPORTED_MODULE_1__/* .defaultValue */ .Jh)(syncState == null ? void 0 : syncState.contentElement, null),
    disclosureElement: (0,_UWJK2WK2_js__WEBPACK_IMPORTED_MODULE_1__/* .defaultValue */ .Jh)(syncState == null ? void 0 : syncState.disclosureElement, null)
  };
  const disclosure = (0,_XTZ53NXG_js__WEBPACK_IMPORTED_MODULE_0__/* .createStore */ .y$)(initialState, store);
  (0,_XTZ53NXG_js__WEBPACK_IMPORTED_MODULE_0__/* .setup */ .mj)(
    disclosure,
    () => (0,_XTZ53NXG_js__WEBPACK_IMPORTED_MODULE_0__/* .sync */ .OH)(disclosure, ["animated", "animating"], (state) => {
      if (state.animated) return;
      disclosure.setState("animating", false);
    })
  );
  (0,_XTZ53NXG_js__WEBPACK_IMPORTED_MODULE_0__/* .setup */ .mj)(
    disclosure,
    () => (0,_XTZ53NXG_js__WEBPACK_IMPORTED_MODULE_0__/* .subscribe */ .B1)(disclosure, ["open"], () => {
      if (!disclosure.getState().animated) return;
      disclosure.setState("animating", true);
    })
  );
  (0,_XTZ53NXG_js__WEBPACK_IMPORTED_MODULE_0__/* .setup */ .mj)(
    disclosure,
    () => (0,_XTZ53NXG_js__WEBPACK_IMPORTED_MODULE_0__/* .sync */ .OH)(disclosure, ["open", "animating"], (state) => {
      disclosure.setState("mounted", state.open || state.animating);
    })
  );
  return {
    ...disclosure,
    disclosure: props.disclosure,
    setOpen: (value) => disclosure.setState("open", value),
    show: () => disclosure.setState("open", true),
    hide: () => disclosure.setState("open", false),
    toggle: () => disclosure.setState("open", (open2) => !open2),
    stopAnimation: () => disclosure.setState("animating", false),
    setContentElement: (value) => disclosure.setState("contentElement", value),
    setDisclosureElement: (value) => disclosure.setState("disclosureElement", value)
  };
}




/***/ }),

/***/ "../../node_modules/.pnpm/@ariakit+core@0.4.19/node_modules/@ariakit/core/esm/__chunks/UWJK2WK2.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   $f: () => (/* binding */ disabledFromProps),
/* harmony export */   D_: () => (/* binding */ identity),
/* harmony export */   HR: () => (/* binding */ removeUndefinedValues),
/* harmony export */   Jh: () => (/* binding */ defaultValue),
/* harmony export */   Qh: () => (/* binding */ applyState),
/* harmony export */   Up: () => (/* binding */ pick),
/* harmony export */   V1: () => (/* binding */ invariant),
/* harmony export */   cJ: () => (/* binding */ omit),
/* harmony export */   cy: () => (/* binding */ chain),
/* harmony export */   lQ: () => (/* binding */ noop),
/* harmony export */   mQ: () => (/* binding */ hasOwnProperty),
/* harmony export */   xD: () => (/* binding */ getKeys),
/* harmony export */   zO: () => (/* binding */ isFalsyBooleanCallback)
/* harmony export */ });
/* unused harmony exports shallowEqual, isObject, isEmpty, isInteger, cx, normalizeString, beforePaint, afterPaint, disabledFromElement */
"use client";

// src/utils/misc.ts
function noop(..._) {
}
function shallowEqual(a, b) {
  if (a === b) return true;
  if (!a) return false;
  if (!b) return false;
  if (typeof a !== "object") return false;
  if (typeof b !== "object") return false;
  const aKeys = Object.keys(a);
  const bKeys = Object.keys(b);
  const { length } = aKeys;
  if (bKeys.length !== length) return false;
  for (const key of aKeys) {
    if (a[key] !== b[key]) {
      return false;
    }
  }
  return true;
}
function applyState(argument, currentValue) {
  if (isUpdater(argument)) {
    const value = isLazyValue(currentValue) ? currentValue() : currentValue;
    return argument(value);
  }
  return argument;
}
function isUpdater(argument) {
  return typeof argument === "function";
}
function isLazyValue(value) {
  return typeof value === "function";
}
function isObject(arg) {
  return typeof arg === "object" && arg != null;
}
function isEmpty(arg) {
  if (Array.isArray(arg)) return !arg.length;
  if (isObject(arg)) return !Object.keys(arg).length;
  if (arg == null) return true;
  if (arg === "") return true;
  return false;
}
function isInteger(arg) {
  if (typeof arg === "number") {
    return Math.floor(arg) === arg;
  }
  return String(Math.floor(Number(arg))) === arg;
}
function hasOwnProperty(object, prop) {
  if (typeof Object.hasOwn === "function") {
    return Object.hasOwn(object, prop);
  }
  return Object.prototype.hasOwnProperty.call(object, prop);
}
function chain(...fns) {
  return (...args) => {
    for (const fn of fns) {
      if (typeof fn === "function") {
        fn(...args);
      }
    }
  };
}
function cx(...args) {
  return args.filter(Boolean).join(" ") || void 0;
}
function normalizeString(str) {
  return str.normalize("NFD").replace(/[\u0300-\u036f]/g, "");
}
function omit(object, keys) {
  const result = { ...object };
  for (const key of keys) {
    if (hasOwnProperty(result, key)) {
      delete result[key];
    }
  }
  return result;
}
function pick(object, paths) {
  const result = {};
  for (const key of paths) {
    if (hasOwnProperty(object, key)) {
      result[key] = object[key];
    }
  }
  return result;
}
function identity(value) {
  return value;
}
function beforePaint(cb = noop) {
  const raf = requestAnimationFrame(cb);
  return () => cancelAnimationFrame(raf);
}
function afterPaint(cb = noop) {
  let raf = requestAnimationFrame(() => {
    raf = requestAnimationFrame(cb);
  });
  return () => cancelAnimationFrame(raf);
}
function invariant(condition, message) {
  if (condition) return;
  if (typeof message !== "string") throw new Error("Invariant failed");
  throw new Error(message);
}
function getKeys(obj) {
  return Object.keys(obj);
}
function isFalsyBooleanCallback(booleanOrCallback, ...args) {
  const result = typeof booleanOrCallback === "function" ? booleanOrCallback(...args) : booleanOrCallback;
  if (result == null) return false;
  return !result;
}
function disabledFromProps(props) {
  return props.disabled || props["aria-disabled"] === true || props["aria-disabled"] === "true";
}
function disabledFromElement(element) {
  if (element.getAttribute("aria-disabled") === "true") return true;
  if ("disabled" in element && element.disabled === true) return true;
  return false;
}
function removeUndefinedValues(obj) {
  const result = {};
  for (const key in obj) {
    if (obj[key] !== void 0) {
      result[key] = obj[key];
    }
  }
  return result;
}
function defaultValue(...values) {
  for (const value of values) {
    if (value !== void 0) return value;
  }
  return void 0;
}




/***/ }),

/***/ "../../node_modules/.pnpm/@ariakit+core@0.4.19/node_modules/@ariakit/core/esm/__chunks/XTZ53NXG.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   B1: () => (/* binding */ subscribe),
/* harmony export */   OH: () => (/* binding */ sync),
/* harmony export */   Ts: () => (/* binding */ init),
/* harmony export */   UE: () => (/* binding */ throwOnConflictingProps),
/* harmony export */   cJ: () => (/* binding */ omit2),
/* harmony export */   mj: () => (/* binding */ setup),
/* harmony export */   od: () => (/* binding */ mergeStore),
/* harmony export */   vA: () => (/* binding */ batch),
/* harmony export */   y$: () => (/* binding */ createStore)
/* harmony export */ });
/* unused harmony export pick */
/* harmony import */ var _UWJK2WK2_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+core@0.4.19/node_modules/@ariakit/core/esm/__chunks/UWJK2WK2.js");
"use client";


// src/utils/store.ts
function getInternal(store, key) {
  const internals = store.__unstableInternals;
  (0,_UWJK2WK2_js__WEBPACK_IMPORTED_MODULE_0__/* .invariant */ .V1)(internals, "Invalid store");
  return internals[key];
}
function createStore(initialState, ...stores) {
  let state = initialState;
  let prevStateBatch = state;
  let lastUpdate = /* @__PURE__ */ Symbol();
  let destroy = _UWJK2WK2_js__WEBPACK_IMPORTED_MODULE_0__/* .noop */ .lQ;
  const instances = /* @__PURE__ */ new Set();
  const updatedKeys = /* @__PURE__ */ new Set();
  const setups = /* @__PURE__ */ new Set();
  const listeners = /* @__PURE__ */ new Set();
  const batchListeners = /* @__PURE__ */ new Set();
  const disposables = /* @__PURE__ */ new WeakMap();
  const listenerKeys = /* @__PURE__ */ new WeakMap();
  const storeSetup = (callback) => {
    setups.add(callback);
    return () => setups.delete(callback);
  };
  const storeInit = () => {
    const initialized = instances.size;
    const instance = /* @__PURE__ */ Symbol();
    instances.add(instance);
    const maybeDestroy = () => {
      instances.delete(instance);
      if (instances.size) return;
      destroy();
    };
    if (initialized) return maybeDestroy;
    const desyncs = (0,_UWJK2WK2_js__WEBPACK_IMPORTED_MODULE_0__/* .getKeys */ .xD)(state).map(
      (key) => (0,_UWJK2WK2_js__WEBPACK_IMPORTED_MODULE_0__/* .chain */ .cy)(
        ...stores.map((store) => {
          var _a;
          const storeState = (_a = store == null ? void 0 : store.getState) == null ? void 0 : _a.call(store);
          if (!storeState) return;
          if (!(0,_UWJK2WK2_js__WEBPACK_IMPORTED_MODULE_0__/* .hasOwnProperty */ .mQ)(storeState, key)) return;
          return sync(store, [key], (state2) => {
            setState(
              key,
              state2[key],
              // @ts-expect-error - Not public API. This is just to prevent
              // infinite loops.
              true
            );
          });
        })
      )
    );
    const teardowns = [];
    for (const setup2 of setups) {
      teardowns.push(setup2());
    }
    const cleanups = stores.map(init);
    destroy = (0,_UWJK2WK2_js__WEBPACK_IMPORTED_MODULE_0__/* .chain */ .cy)(...desyncs, ...teardowns, ...cleanups);
    return maybeDestroy;
  };
  const sub = (keys, listener, set = listeners) => {
    set.add(listener);
    listenerKeys.set(listener, keys);
    return () => {
      var _a;
      (_a = disposables.get(listener)) == null ? void 0 : _a();
      disposables.delete(listener);
      listenerKeys.delete(listener);
      set.delete(listener);
    };
  };
  const storeSubscribe = (keys, listener) => sub(keys, listener);
  const storeSync = (keys, listener) => {
    disposables.set(listener, listener(state, state));
    return sub(keys, listener);
  };
  const storeBatch = (keys, listener) => {
    disposables.set(listener, listener(state, prevStateBatch));
    return sub(keys, listener, batchListeners);
  };
  const storePick = (keys) => createStore((0,_UWJK2WK2_js__WEBPACK_IMPORTED_MODULE_0__/* .pick */ .Up)(state, keys), finalStore);
  const storeOmit = (keys) => createStore((0,_UWJK2WK2_js__WEBPACK_IMPORTED_MODULE_0__/* .omit */ .cJ)(state, keys), finalStore);
  const getState = () => state;
  const setState = (key, value, fromStores = false) => {
    var _a;
    if (!(0,_UWJK2WK2_js__WEBPACK_IMPORTED_MODULE_0__/* .hasOwnProperty */ .mQ)(state, key)) return;
    const nextValue = (0,_UWJK2WK2_js__WEBPACK_IMPORTED_MODULE_0__/* .applyState */ .Qh)(value, state[key]);
    if (nextValue === state[key]) return;
    if (!fromStores) {
      for (const store of stores) {
        (_a = store == null ? void 0 : store.setState) == null ? void 0 : _a.call(store, key, nextValue);
      }
    }
    const prevState = state;
    state = { ...state, [key]: nextValue };
    const thisUpdate = /* @__PURE__ */ Symbol();
    lastUpdate = thisUpdate;
    updatedKeys.add(key);
    const run = (listener, prev, uKeys) => {
      var _a2;
      const keys = listenerKeys.get(listener);
      const updated = (k) => uKeys ? uKeys.has(k) : k === key;
      if (!keys || keys.some(updated)) {
        (_a2 = disposables.get(listener)) == null ? void 0 : _a2();
        disposables.set(listener, listener(state, prev));
      }
    };
    for (const listener of listeners) {
      run(listener, prevState);
    }
    queueMicrotask(() => {
      if (lastUpdate !== thisUpdate) return;
      const snapshot = state;
      for (const listener of batchListeners) {
        run(listener, prevStateBatch, updatedKeys);
      }
      prevStateBatch = snapshot;
      updatedKeys.clear();
    });
  };
  const finalStore = {
    getState,
    setState,
    __unstableInternals: {
      setup: storeSetup,
      init: storeInit,
      subscribe: storeSubscribe,
      sync: storeSync,
      batch: storeBatch,
      pick: storePick,
      omit: storeOmit
    }
  };
  return finalStore;
}
function setup(store, ...args) {
  if (!store) return;
  return getInternal(store, "setup")(...args);
}
function init(store, ...args) {
  if (!store) return;
  return getInternal(store, "init")(...args);
}
function subscribe(store, ...args) {
  if (!store) return;
  return getInternal(store, "subscribe")(...args);
}
function sync(store, ...args) {
  if (!store) return;
  return getInternal(store, "sync")(...args);
}
function batch(store, ...args) {
  if (!store) return;
  return getInternal(store, "batch")(...args);
}
function omit2(store, ...args) {
  if (!store) return;
  return getInternal(store, "omit")(...args);
}
function pick2(store, ...args) {
  if (!store) return;
  return getInternal(store, "pick")(...args);
}
function mergeStore(...stores) {
  var _a;
  const initialState = {};
  for (const store2 of stores) {
    const nextState = (_a = store2 == null ? void 0 : store2.getState) == null ? void 0 : _a.call(store2);
    if (nextState) {
      Object.assign(initialState, nextState);
    }
  }
  const store = createStore(initialState, ...stores);
  return Object.assign({}, ...stores, store);
}
function throwOnConflictingProps(props, store) {
  if (true) return;
  if (!store) return;
  const defaultKeys = Object.entries(props).filter(([key, value]) => key.startsWith("default") && value !== void 0).map(([key]) => {
    var _a;
    const stateKey = key.replace("default", "");
    return `${((_a = stateKey[0]) == null ? void 0 : _a.toLowerCase()) || ""}${stateKey.slice(1)}`;
  });
  if (!defaultKeys.length) return;
  const storeState = store.getState();
  const conflictingProps = defaultKeys.filter(
    (key) => (0,_UWJK2WK2_js__WEBPACK_IMPORTED_MODULE_0__/* .hasOwnProperty */ .mQ)(storeState, key)
  );
  if (!conflictingProps.length) return;
  throw new Error(
    `Passing a store prop in conjunction with a default state is not supported.

const store = useSelectStore();
<SelectProvider store={store} defaultValue="Apple" />
                ^             ^

Instead, pass the default state to the topmost store:

const store = useSelectStore({ defaultValue: "Apple" });
<SelectProvider store={store} />

See https://github.com/ariakit/ariakit/pull/2745 for more details.

If there's a particular need for this, please submit a feature request at https://github.com/ariakit/ariakit
`
  );
}




/***/ }),

/***/ "../../node_modules/.pnpm/@ariakit+core@0.4.19/node_modules/@ariakit/core/esm/utils/events.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   aG: () => (/* binding */ isFocusEventOutside),
/* harmony export */   c$: () => (/* binding */ fireBlurEvent),
/* harmony export */   dg: () => (/* binding */ addGlobalEventListener),
/* harmony export */   hY: () => (/* binding */ fireClickEvent),
/* harmony export */   ho: () => (/* binding */ isPortalEvent),
/* harmony export */   nz: () => (/* binding */ queueBeforeEvent),
/* harmony export */   sz: () => (/* binding */ fireKeyboardEvent),
/* harmony export */   uh: () => (/* binding */ isSelfTarget)
/* harmony export */ });
/* unused harmony exports fireEvent, fireFocusEvent, getInputType, isDownloading, isOpeningInNewTab */
/* harmony import */ var _chunks_G7XPWBXK_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+core@0.4.19/node_modules/@ariakit/core/esm/__chunks/G7XPWBXK.js");
"use client";



// src/utils/events.ts
function isPortalEvent(event) {
  return Boolean(
    event.currentTarget && !(0,_chunks_G7XPWBXK_js__WEBPACK_IMPORTED_MODULE_0__/* .contains */ .gR)(event.currentTarget, event.target)
  );
}
function isSelfTarget(event) {
  return event.target === event.currentTarget;
}
function isOpeningInNewTab(event) {
  const element = event.currentTarget;
  if (!element) return false;
  const isAppleDevice = isApple();
  if (isAppleDevice && !event.metaKey) return false;
  if (!isAppleDevice && !event.ctrlKey) return false;
  const tagName = element.tagName.toLowerCase();
  if (tagName === "a") return true;
  if (tagName === "button" && element.type === "submit") return true;
  if (tagName === "input" && element.type === "submit") return true;
  return false;
}
function isDownloading(event) {
  const element = event.currentTarget;
  if (!element) return false;
  const tagName = element.tagName.toLowerCase();
  if (!event.altKey) return false;
  if (tagName === "a") return true;
  if (tagName === "button" && element.type === "submit") return true;
  if (tagName === "input" && element.type === "submit") return true;
  return false;
}
function fireEvent(element, type, eventInit) {
  const event = new Event(type, eventInit);
  return element.dispatchEvent(event);
}
function fireBlurEvent(element, eventInit) {
  const event = new FocusEvent("blur", eventInit);
  const defaultAllowed = element.dispatchEvent(event);
  const bubbleInit = { ...eventInit, bubbles: true };
  element.dispatchEvent(new FocusEvent("focusout", bubbleInit));
  return defaultAllowed;
}
function fireFocusEvent(element, eventInit) {
  const event = new FocusEvent("focus", eventInit);
  const defaultAllowed = element.dispatchEvent(event);
  const bubbleInit = { ...eventInit, bubbles: true };
  element.dispatchEvent(new FocusEvent("focusin", bubbleInit));
  return defaultAllowed;
}
function fireKeyboardEvent(element, type, eventInit) {
  const event = new KeyboardEvent(type, eventInit);
  return element.dispatchEvent(event);
}
function fireClickEvent(element, eventInit) {
  const event = new MouseEvent("click", eventInit);
  return element.dispatchEvent(event);
}
function isFocusEventOutside(event, container) {
  const containerElement = container || event.currentTarget;
  const relatedTarget = event.relatedTarget;
  return !relatedTarget || !(0,_chunks_G7XPWBXK_js__WEBPACK_IMPORTED_MODULE_0__/* .contains */ .gR)(containerElement, relatedTarget);
}
function getInputType(event) {
  const nativeEvent = "nativeEvent" in event ? event.nativeEvent : event;
  if (!nativeEvent) return;
  if (!("inputType" in nativeEvent)) return;
  if (typeof nativeEvent.inputType !== "string") return;
  return nativeEvent.inputType;
}
function queueBeforeEvent(element, type, callback, timeout) {
  const createTimer = (callback2) => {
    if (timeout) {
      const timerId2 = setTimeout(callback2, timeout);
      return () => clearTimeout(timerId2);
    }
    const timerId = requestAnimationFrame(callback2);
    return () => cancelAnimationFrame(timerId);
  };
  const cancelTimer = createTimer(() => {
    element.removeEventListener(type, callSync, true);
    callback();
  });
  const callSync = () => {
    cancelTimer();
    callback();
  };
  element.addEventListener(type, callSync, { once: true, capture: true });
  return cancelTimer;
}
function addGlobalEventListener(type, listener, options, scope = window) {
  const children = [];
  try {
    scope.document.addEventListener(type, listener, options);
    for (const frame of Array.from(scope.frames)) {
      children.push(addGlobalEventListener(type, listener, options, frame));
    }
  } catch {
  }
  const removeEventListener = () => {
    try {
      scope.document.removeEventListener(type, listener, options);
    } catch {
    }
    for (const remove of children) {
      remove();
    }
  };
  return removeEventListener;
}



/***/ }),

/***/ "../../node_modules/.pnpm/@ariakit+core@0.4.19/node_modules/@ariakit/core/esm/utils/focus.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   AJ: () => (/* binding */ hasFocus),
/* harmony export */   LC: () => (/* binding */ getFirstTabbableIn),
/* harmony export */   QZ: () => (/* binding */ restoreFocusIn),
/* harmony export */   WA: () => (/* binding */ focusIntoView),
/* harmony export */   XJ: () => (/* binding */ getPreviousTabbable),
/* harmony export */   a9: () => (/* binding */ getAllTabbableIn),
/* harmony export */   gw: () => (/* binding */ disableFocusIn),
/* harmony export */   oW: () => (/* binding */ hasFocusWithin),
/* harmony export */   tp: () => (/* binding */ isFocusable),
/* harmony export */   vF: () => (/* binding */ getNextTabbable)
/* harmony export */ });
/* unused harmony exports disableFocus, focusIfNeeded, getAllFocusable, getAllFocusableIn, getAllTabbable, getClosestFocusable, getFirstFocusable, getFirstFocusableIn, getFirstTabbable, getLastTabbable, getLastTabbableIn, getNextTabbableIn, getPreviousTabbableIn, isTabbable */
/* harmony import */ var _chunks_G7XPWBXK_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+core@0.4.19/node_modules/@ariakit/core/esm/__chunks/G7XPWBXK.js");
"use client";


// src/utils/focus.ts
var selector = "input:not([type='hidden']):not([disabled]), select:not([disabled]), textarea:not([disabled]), a[href], button:not([disabled]), [tabindex], summary, iframe, object, embed, area[href], audio[controls], video[controls], [contenteditable]:not([contenteditable='false'])";
function hasNegativeTabIndex(element) {
  const tabIndex = Number.parseInt(element.getAttribute("tabindex") || "0", 10);
  return tabIndex < 0;
}
function isFocusable(element) {
  if (!element.matches(selector)) return false;
  if (!(0,_chunks_G7XPWBXK_js__WEBPACK_IMPORTED_MODULE_0__/* .isVisible */ .zN)(element)) return false;
  if (element.closest("[inert]")) return false;
  return true;
}
function isTabbable(element) {
  if (!isFocusable(element)) return false;
  if (hasNegativeTabIndex(element)) return false;
  if (!("form" in element)) return true;
  if (!element.form) return true;
  if (element.checked) return true;
  if (element.type !== "radio") return true;
  const radioGroup = element.form.elements.namedItem(element.name);
  if (!radioGroup) return true;
  if (!("length" in radioGroup)) return true;
  const activeElement = (0,_chunks_G7XPWBXK_js__WEBPACK_IMPORTED_MODULE_0__/* .getActiveElement */ .bq)(element);
  if (!activeElement) return true;
  if (activeElement === element) return true;
  if (!("form" in activeElement)) return true;
  if (activeElement.form !== element.form) return true;
  if (activeElement.name !== element.name) return true;
  return false;
}
function getAllFocusableIn(container, includeContainer) {
  const elements = Array.from(
    container.querySelectorAll(selector)
  );
  if (includeContainer) {
    elements.unshift(container);
  }
  const focusableElements = elements.filter(isFocusable);
  focusableElements.forEach((element, i) => {
    var _a;
    if (!(0,_chunks_G7XPWBXK_js__WEBPACK_IMPORTED_MODULE_0__/* .isFrame */ .qt)(element)) return;
    const frameBody = (_a = element.contentDocument) == null ? void 0 : _a.body;
    if (!frameBody) return;
    focusableElements.splice(i, 1, ...getAllFocusableIn(frameBody));
  });
  return focusableElements;
}
function getAllFocusable(includeBody) {
  return getAllFocusableIn(document.body, includeBody);
}
function getFirstFocusableIn(container, includeContainer) {
  const [first] = getAllFocusableIn(container, includeContainer);
  return first || null;
}
function getFirstFocusable(includeBody) {
  return getFirstFocusableIn(document.body, includeBody);
}
function getAllTabbableIn(container, includeContainer, fallbackToFocusable) {
  const elements = Array.from(
    container.querySelectorAll(selector)
  );
  const tabbableElements = elements.filter(isTabbable);
  if (includeContainer && isTabbable(container)) {
    tabbableElements.unshift(container);
  }
  tabbableElements.forEach((element, i) => {
    var _a;
    if (!(0,_chunks_G7XPWBXK_js__WEBPACK_IMPORTED_MODULE_0__/* .isFrame */ .qt)(element)) return;
    const frameBody = (_a = element.contentDocument) == null ? void 0 : _a.body;
    if (!frameBody) return;
    const allFrameTabbable = getAllTabbableIn(
      frameBody,
      false,
      fallbackToFocusable
    );
    tabbableElements.splice(i, 1, ...allFrameTabbable);
  });
  if (!tabbableElements.length && fallbackToFocusable) {
    return elements;
  }
  return tabbableElements;
}
function getAllTabbable(fallbackToFocusable) {
  return getAllTabbableIn(document.body, false, fallbackToFocusable);
}
function getFirstTabbableIn(container, includeContainer, fallbackToFocusable) {
  const [first] = getAllTabbableIn(
    container,
    includeContainer,
    fallbackToFocusable
  );
  return first || null;
}
function getFirstTabbable(fallbackToFocusable) {
  return getFirstTabbableIn(document.body, false, fallbackToFocusable);
}
function getLastTabbableIn(container, includeContainer, fallbackToFocusable) {
  const allTabbable = getAllTabbableIn(
    container,
    includeContainer,
    fallbackToFocusable
  );
  return allTabbable[allTabbable.length - 1] || null;
}
function getLastTabbable(fallbackToFocusable) {
  return getLastTabbableIn(document.body, false, fallbackToFocusable);
}
function getNextTabbableIn(container, includeContainer, fallbackToFirst, fallbackToFocusable) {
  const activeElement = (0,_chunks_G7XPWBXK_js__WEBPACK_IMPORTED_MODULE_0__/* .getActiveElement */ .bq)(container);
  const allFocusable = getAllFocusableIn(container, includeContainer);
  const activeIndex = allFocusable.indexOf(activeElement);
  const nextFocusableElements = allFocusable.slice(activeIndex + 1);
  return nextFocusableElements.find(isTabbable) || (fallbackToFirst ? allFocusable.find(isTabbable) : null) || (fallbackToFocusable ? nextFocusableElements[0] : null) || null;
}
function getNextTabbable(fallbackToFirst, fallbackToFocusable) {
  return getNextTabbableIn(
    document.body,
    false,
    fallbackToFirst,
    fallbackToFocusable
  );
}
function getPreviousTabbableIn(container, includeContainer, fallbackToLast, fallbackToFocusable) {
  const activeElement = (0,_chunks_G7XPWBXK_js__WEBPACK_IMPORTED_MODULE_0__/* .getActiveElement */ .bq)(container);
  const allFocusable = getAllFocusableIn(container, includeContainer).reverse();
  const activeIndex = allFocusable.indexOf(activeElement);
  const previousFocusableElements = allFocusable.slice(activeIndex + 1);
  return previousFocusableElements.find(isTabbable) || (fallbackToLast ? allFocusable.find(isTabbable) : null) || (fallbackToFocusable ? previousFocusableElements[0] : null) || null;
}
function getPreviousTabbable(fallbackToFirst, fallbackToFocusable) {
  return getPreviousTabbableIn(
    document.body,
    false,
    fallbackToFirst,
    fallbackToFocusable
  );
}
function getClosestFocusable(element) {
  while (element && !isFocusable(element)) {
    element = element.closest(selector);
  }
  return element || null;
}
function hasFocus(element) {
  const activeElement = (0,_chunks_G7XPWBXK_js__WEBPACK_IMPORTED_MODULE_0__/* .getActiveElement */ .bq)(element);
  if (!activeElement) return false;
  if (activeElement === element) return true;
  const activeDescendant = activeElement.getAttribute("aria-activedescendant");
  if (!activeDescendant) return false;
  return activeDescendant === element.id;
}
function hasFocusWithin(element) {
  const activeElement = (0,_chunks_G7XPWBXK_js__WEBPACK_IMPORTED_MODULE_0__/* .getActiveElement */ .bq)(element);
  if (!activeElement) return false;
  if ((0,_chunks_G7XPWBXK_js__WEBPACK_IMPORTED_MODULE_0__/* .contains */ .gR)(element, activeElement)) return true;
  const activeDescendant = activeElement.getAttribute("aria-activedescendant");
  if (!activeDescendant) return false;
  if (!("id" in element)) return false;
  if (activeDescendant === element.id) return true;
  return !!element.querySelector(`#${CSS.escape(activeDescendant)}`);
}
function focusIfNeeded(element) {
  if (!hasFocusWithin(element) && isFocusable(element)) {
    element.focus();
  }
}
function disableFocus(element) {
  const currentTabindex = element.getAttribute("tabindex") ?? "";
  element.setAttribute("data-tabindex", currentTabindex);
  element.setAttribute("tabindex", "-1");
}
function disableFocusIn(container, includeContainer) {
  const tabbableElements = getAllTabbableIn(container, includeContainer);
  for (const element of tabbableElements) {
    disableFocus(element);
  }
}
function restoreFocusIn(container) {
  const elements = container.querySelectorAll("[data-tabindex]");
  const restoreTabIndex = (element) => {
    const tabindex = element.getAttribute("data-tabindex");
    element.removeAttribute("data-tabindex");
    if (tabindex) {
      element.setAttribute("tabindex", tabindex);
    } else {
      element.removeAttribute("tabindex");
    }
  };
  if (container.hasAttribute("data-tabindex")) {
    restoreTabIndex(container);
  }
  for (const element of elements) {
    restoreTabIndex(element);
  }
}
function focusIntoView(element, options) {
  if (!("scrollIntoView" in element)) {
    element.focus();
  } else {
    element.focus({ preventScroll: true });
    element.scrollIntoView({ block: "nearest", inline: "nearest", ...options });
  }
}



/***/ }),

/***/ "../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/CEM7J6TT.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

var react__WEBPACK_IMPORTED_MODULE_0___namespace_cache;
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   Bi: () => (/* binding */ useId),
/* harmony export */   CH: () => (/* binding */ useForceUpdate),
/* harmony export */   O4: () => (/* binding */ useBooleanEvent),
/* harmony export */   P$: () => (/* binding */ useIsMouseMoving),
/* harmony export */   P1: () => (/* binding */ useMetadataProps),
/* harmony export */   SV: () => (/* binding */ useMergeRefs),
/* harmony export */   TE: () => (/* binding */ usePortalRef),
/* harmony export */   UQ: () => (/* binding */ useSafeLayoutEffect),
/* harmony export */   XB: () => (/* binding */ useTransactionState),
/* harmony export */   _q: () => (/* binding */ useEvent),
/* harmony export */   hX: () => (/* binding */ useLiveRef),
/* harmony export */   vO: () => (/* binding */ useTagName),
/* harmony export */   w5: () => (/* binding */ useUpdateEffect),
/* harmony export */   w7: () => (/* binding */ useWrapElement)
/* harmony export */ });
/* unused harmony exports useInitialValue, useLazyValue, usePreviousValue, useDeferredValue, useAttribute, useUpdateLayoutEffect */
/* harmony import */ var _YXGXYGQX_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/YXGXYGQX.js");
/* harmony import */ var _ariakit_core_utils_dom__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+core@0.4.19/node_modules/@ariakit/core/esm/__chunks/G7XPWBXK.js");
/* harmony import */ var _ariakit_core_utils_events__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+core@0.4.19/node_modules/@ariakit/core/esm/utils/events.js");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
"use client";


// src/utils/hooks.ts




var _React = { .../*#__PURE__*/ (react__WEBPACK_IMPORTED_MODULE_0___namespace_cache || (react__WEBPACK_IMPORTED_MODULE_0___namespace_cache = __webpack_require__.t(react__WEBPACK_IMPORTED_MODULE_0__, 2))) };
var useReactId = _React.useId;
var useReactDeferredValue = _React.useDeferredValue;
var useReactInsertionEffect = _React.useInsertionEffect;
var useSafeLayoutEffect = _ariakit_core_utils_dom__WEBPACK_IMPORTED_MODULE_1__/* .canUseDOM */ .Sw ? react__WEBPACK_IMPORTED_MODULE_0__.useLayoutEffect : react__WEBPACK_IMPORTED_MODULE_0__.useEffect;
function useInitialValue(value) {
  const [initialValue] = useState(value);
  return initialValue;
}
function useLazyValue(init) {
  const ref = useRef(void 0);
  if (ref.current === void 0) {
    ref.current = init();
  }
  return ref.current;
}
function useLiveRef(value) {
  const ref = (0,react__WEBPACK_IMPORTED_MODULE_0__.useRef)(value);
  useSafeLayoutEffect(() => {
    ref.current = value;
  });
  return ref;
}
function usePreviousValue(value) {
  const [previousValue, setPreviousValue] = useState(value);
  if (value !== previousValue) {
    setPreviousValue(value);
  }
  return previousValue;
}
function useEvent(callback) {
  const ref = (0,react__WEBPACK_IMPORTED_MODULE_0__.useRef)(() => {
    throw new Error("Cannot call an event handler while rendering.");
  });
  if (useReactInsertionEffect) {
    useReactInsertionEffect(() => {
      ref.current = callback;
    });
  } else {
    ref.current = callback;
  }
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.useCallback)((...args) => {
    var _a;
    return (_a = ref.current) == null ? void 0 : _a.call(ref, ...args);
  }, []);
}
function useTransactionState(callback) {
  const [state, setState] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(null);
  useSafeLayoutEffect(() => {
    if (state == null) return;
    if (!callback) return;
    let prevState = null;
    callback((prev) => {
      prevState = prev;
      return state;
    });
    return () => {
      callback(prevState);
    };
  }, [state, callback]);
  return [state, setState];
}
function useMergeRefs(...refs) {
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.useMemo)(() => {
    if (!refs.some(Boolean)) return;
    return (value) => {
      for (const ref of refs) {
        (0,_YXGXYGQX_js__WEBPACK_IMPORTED_MODULE_2__/* .setRef */ .cZ)(ref, value);
      }
    };
  }, refs);
}
function useId(defaultId) {
  if (useReactId) {
    const reactId = useReactId();
    if (defaultId) return defaultId;
    return reactId;
  }
  const [id, setId] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(defaultId);
  useSafeLayoutEffect(() => {
    if (defaultId || id) return;
    const random = Math.random().toString(36).slice(2, 8);
    setId(`id-${random}`);
  }, [defaultId, id]);
  return defaultId || id;
}
function useDeferredValue(value) {
  if (useReactDeferredValue) {
    return useReactDeferredValue(value);
  }
  const [deferredValue, setDeferredValue] = useState(value);
  useEffect(() => {
    const raf = requestAnimationFrame(() => setDeferredValue(value));
    return () => cancelAnimationFrame(raf);
  }, [value]);
  return deferredValue;
}
function useTagName(refOrElement, type) {
  const stringOrUndefined = (type2) => {
    if (typeof type2 !== "string") return;
    return type2;
  };
  const [tagName, setTagName] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(() => stringOrUndefined(type));
  useSafeLayoutEffect(() => {
    const element = refOrElement && "current" in refOrElement ? refOrElement.current : refOrElement;
    setTagName((element == null ? void 0 : element.tagName.toLowerCase()) || stringOrUndefined(type));
  }, [refOrElement, type]);
  return tagName;
}
function useAttribute(refOrElement, attributeName, defaultValue) {
  const initialValue = useInitialValue(defaultValue);
  const [attribute, setAttribute] = useState(initialValue);
  useEffect(() => {
    const element = refOrElement && "current" in refOrElement ? refOrElement.current : refOrElement;
    if (!element) return;
    const callback = () => {
      const value = element.getAttribute(attributeName);
      setAttribute(value == null ? initialValue : value);
    };
    const observer = new MutationObserver(callback);
    observer.observe(element, { attributeFilter: [attributeName] });
    callback();
    return () => observer.disconnect();
  }, [refOrElement, attributeName, initialValue]);
  return attribute;
}
function useUpdateEffect(effect, deps) {
  const mounted = (0,react__WEBPACK_IMPORTED_MODULE_0__.useRef)(false);
  (0,react__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    if (mounted.current) {
      return effect();
    }
    mounted.current = true;
  }, deps);
  (0,react__WEBPACK_IMPORTED_MODULE_0__.useEffect)(
    () => () => {
      mounted.current = false;
    },
    []
  );
}
function useUpdateLayoutEffect(effect, deps) {
  const mounted = useRef(false);
  useSafeLayoutEffect(() => {
    if (mounted.current) {
      return effect();
    }
    mounted.current = true;
  }, deps);
  useSafeLayoutEffect(
    () => () => {
      mounted.current = false;
    },
    []
  );
}
function useForceUpdate() {
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.useReducer)(() => [], []);
}
function useBooleanEvent(booleanOrCallback) {
  return useEvent(
    typeof booleanOrCallback === "function" ? booleanOrCallback : () => booleanOrCallback
  );
}
function useWrapElement(props, callback, deps = []) {
  const wrapElement = (0,react__WEBPACK_IMPORTED_MODULE_0__.useCallback)(
    (element) => {
      if (props.wrapElement) {
        element = props.wrapElement(element);
      }
      return callback(element);
    },
    // oxlint-disable-next-line exhaustive-deps
    [...deps, props.wrapElement]
  );
  return { ...props, wrapElement };
}
function usePortalRef(portalProp = false, portalRefProp) {
  const [portalNode, setPortalNode] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(null);
  const portalRef = useMergeRefs(setPortalNode, portalRefProp);
  const domReady = !portalProp || portalNode;
  return { portalRef, portalNode, domReady };
}
function useMetadataProps(props, key, value) {
  const parent = props.onLoadedMetadataCapture;
  const onLoadedMetadataCapture = (0,react__WEBPACK_IMPORTED_MODULE_0__.useMemo)(() => {
    return Object.assign(
      () => {
      },
      parent,
      ...value !== void 0 ? [{ [key]: value }] : []
    );
  }, [parent, key, value]);
  return [parent == null ? void 0 : parent[key], { onLoadedMetadataCapture }];
}
var hasInstalledGlobalEventListeners = false;
function useIsMouseMoving() {
  (0,react__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    if (hasInstalledGlobalEventListeners) return;
    (0,_ariakit_core_utils_events__WEBPACK_IMPORTED_MODULE_3__/* .addGlobalEventListener */ .dg)("mousemove", setMouseMoving, true);
    (0,_ariakit_core_utils_events__WEBPACK_IMPORTED_MODULE_3__/* .addGlobalEventListener */ .dg)("mousedown", resetMouseMoving, true);
    (0,_ariakit_core_utils_events__WEBPACK_IMPORTED_MODULE_3__/* .addGlobalEventListener */ .dg)("mouseup", resetMouseMoving, true);
    (0,_ariakit_core_utils_events__WEBPACK_IMPORTED_MODULE_3__/* .addGlobalEventListener */ .dg)("keydown", resetMouseMoving, true);
    (0,_ariakit_core_utils_events__WEBPACK_IMPORTED_MODULE_3__/* .addGlobalEventListener */ .dg)("scroll", resetMouseMoving, true);
    hasInstalledGlobalEventListeners = true;
  }, []);
  const isMouseMoving = useEvent(() => mouseMoving);
  return isMouseMoving;
}
var mouseMoving = false;
var previousScreenX = 0;
var previousScreenY = 0;
function hasMouseMovement(event) {
  const movementX = event.movementX || event.screenX - previousScreenX;
  const movementY = event.movementY || event.screenY - previousScreenY;
  previousScreenX = event.screenX;
  previousScreenY = event.screenY;
  return movementX || movementY || "production" === "test";
}
function setMouseMoving(event) {
  if (!hasMouseMovement(event)) return;
  mouseMoving = true;
}
function resetMouseMoving() {
  mouseMoving = false;
}




/***/ }),

/***/ "../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/E4DZA6YM.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   s1: () => (/* binding */ PopoverScopedContextProvider),
/* harmony export */   wf: () => (/* binding */ PopoverContextProvider),
/* harmony export */   zG: () => (/* binding */ usePopoverProviderContext)
/* harmony export */ });
/* unused harmony exports usePopoverContext, usePopoverScopedContext */
/* harmony import */ var _W43ISTP6_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/W43ISTP6.js");
/* harmony import */ var _TVXRYIJB_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/TVXRYIJB.js");
"use client";



// src/popover/popover-context.tsx
var ctx = (0,_TVXRYIJB_js__WEBPACK_IMPORTED_MODULE_0__/* .createStoreContext */ .B0)(
  [_W43ISTP6_js__WEBPACK_IMPORTED_MODULE_1__/* .DialogContextProvider */ .Zg],
  [_W43ISTP6_js__WEBPACK_IMPORTED_MODULE_1__/* .DialogScopedContextProvider */ .XP]
);
var usePopoverContext = ctx.useContext;
var usePopoverScopedContext = ctx.useScopedContext;
var usePopoverProviderContext = ctx.useProviderContext;
var PopoverContextProvider = ctx.ContextProvider;
var PopoverScopedContextProvider = ctx.ScopedContextProvider;




/***/ }),

/***/ "../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/FL7NPBCY.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   E: () => (/* binding */ useDisclosureStore),
/* harmony export */   W: () => (/* binding */ useDisclosureStoreProps)
/* harmony export */ });
/* harmony import */ var _YAS7X7HB_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/YAS7X7HB.js");
/* harmony import */ var _CEM7J6TT_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/CEM7J6TT.js");
/* harmony import */ var _ariakit_core_disclosure_disclosure_store__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+core@0.4.19/node_modules/@ariakit/core/esm/__chunks/IHNLLH3I.js");
"use client";



// src/disclosure/disclosure-store.ts

function useDisclosureStoreProps(store, update, props) {
  (0,_CEM7J6TT_js__WEBPACK_IMPORTED_MODULE_0__/* .useUpdateEffect */ .w5)(update, [props.store, props.disclosure]);
  (0,_YAS7X7HB_js__WEBPACK_IMPORTED_MODULE_1__/* .useStoreProps */ .Tz)(store, props, "open", "setOpen");
  (0,_YAS7X7HB_js__WEBPACK_IMPORTED_MODULE_1__/* .useStoreProps */ .Tz)(store, props, "mounted", "setMounted");
  (0,_YAS7X7HB_js__WEBPACK_IMPORTED_MODULE_1__/* .useStoreProps */ .Tz)(store, props, "animated");
  return Object.assign(store, { disclosure: props.disclosure });
}
function useDisclosureStore(props = {}) {
  const [store, update] = (0,_YAS7X7HB_js__WEBPACK_IMPORTED_MODULE_1__/* .useStore */ .Pj)(_ariakit_core_disclosure_disclosure_store__WEBPACK_IMPORTED_MODULE_2__/* .createDisclosureStore */ .h, props);
  return useDisclosureStoreProps(store, update, props);
}




/***/ }),

/***/ "../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/GR523XJ6.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   W: () => (/* binding */ useFocusable)
/* harmony export */ });
/* unused harmony export Focusable */
/* harmony import */ var _SWN3JYXT_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/SWN3JYXT.js");
/* harmony import */ var _TVXRYIJB_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/TVXRYIJB.js");
/* harmony import */ var _CEM7J6TT_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/CEM7J6TT.js");
/* harmony import */ var _ariakit_core_utils_events__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+core@0.4.19/node_modules/@ariakit/core/esm/utils/events.js");
/* harmony import */ var _ariakit_core_utils_focus__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+core@0.4.19/node_modules/@ariakit/core/esm/utils/focus.js");
/* harmony import */ var _ariakit_core_utils_misc__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+core@0.4.19/node_modules/@ariakit/core/esm/__chunks/UWJK2WK2.js");
/* harmony import */ var _ariakit_core_utils_platform__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+core@0.4.19/node_modules/@ariakit/core/esm/__chunks/GMGLSF2B.js");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
"use client";




// src/focusable/focusable.tsx





var TagName = "div";
var accessibleWhenDisabledSymbol = /* @__PURE__ */ Symbol("accessibleWhenDisabled");
var isSafariBrowser = (0,_ariakit_core_utils_platform__WEBPACK_IMPORTED_MODULE_1__/* .isSafari */ .nr)();
var alwaysFocusVisibleInputTypes = [
  "text",
  "search",
  "url",
  "tel",
  "email",
  "password",
  "number",
  "date",
  "month",
  "week",
  "time",
  "datetime",
  "datetime-local"
];
function isAlwaysFocusVisible(element) {
  const { tagName, readOnly, type } = element;
  if (tagName === "TEXTAREA" && !readOnly) return true;
  if (tagName === "SELECT" && !readOnly) return true;
  if (tagName === "INPUT" && !readOnly) {
    return alwaysFocusVisibleInputTypes.includes(type);
  }
  if (element.isContentEditable) return true;
  const role = element.getAttribute("role");
  if (role === "combobox" && element.dataset.name) {
    return true;
  }
  return false;
}
function isNativeTabbable(tagName) {
  if (!tagName) return true;
  return tagName === "button" || tagName === "summary" || tagName === "input" || tagName === "select" || tagName === "textarea" || tagName === "a";
}
function supportsDisabledAttribute(tagName) {
  if (!tagName) return true;
  return tagName === "button" || tagName === "input" || tagName === "select" || tagName === "textarea";
}
var buttonInputTypes = [
  "button",
  "color",
  "file",
  "image",
  "reset",
  "submit"
];
function needsSafariTabIndex(tagName, inputType) {
  if (tagName === "button") return true;
  if (tagName === "input" && inputType) {
    if (inputType === "checkbox" || inputType === "radio") return true;
    return buttonInputTypes.includes(inputType);
  }
  return false;
}
function getTabIndex({
  focusable,
  trulyDisabled,
  nativeTabbable,
  supportsDisabled,
  safariTabIndex,
  tabIndexProp
}) {
  if (!focusable) return tabIndexProp;
  if (trulyDisabled) {
    if (nativeTabbable && !supportsDisabled) {
      return -1;
    }
    return;
  }
  if (nativeTabbable) {
    if (safariTabIndex && tabIndexProp == null) {
      return 0;
    }
    return tabIndexProp;
  }
  return tabIndexProp != null ? tabIndexProp : 0;
}
function useDisableEvent(onEvent, disabled) {
  return (0,_CEM7J6TT_js__WEBPACK_IMPORTED_MODULE_2__/* .useEvent */ ._q)((event) => {
    onEvent == null ? void 0 : onEvent(event);
    if (event.defaultPrevented) return;
    if (disabled) {
      event.stopPropagation();
      event.preventDefault();
    }
  });
}
var hasInstalledGlobalEventListeners = false;
var isKeyboardModality = true;
function onGlobalMouseDown(event) {
  const target = event.target;
  if (target && "hasAttribute" in target) {
    if (!target.hasAttribute("data-focus-visible")) {
      isKeyboardModality = false;
    }
  }
}
function onGlobalKeyDown(event) {
  if (event.metaKey) return;
  if (event.ctrlKey) return;
  if (event.altKey) return;
  isKeyboardModality = true;
}
var useFocusable = (0,_TVXRYIJB_js__WEBPACK_IMPORTED_MODULE_3__/* .createHook */ .ab)(
  function useFocusable2({
    focusable = true,
    accessibleWhenDisabled,
    autoFocus,
    onFocusVisible,
    ...props
  }) {
    const ref = (0,react__WEBPACK_IMPORTED_MODULE_0__.useRef)(null);
    const [parentAccessibleWhenDisabled, metadataProps] = (0,_CEM7J6TT_js__WEBPACK_IMPORTED_MODULE_2__/* .useMetadataProps */ .P1)(
      props,
      accessibleWhenDisabledSymbol,
      accessibleWhenDisabled
    );
    accessibleWhenDisabled != null ? accessibleWhenDisabled : accessibleWhenDisabled = parentAccessibleWhenDisabled;
    (0,react__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
      if (!focusable) return;
      if (hasInstalledGlobalEventListeners) return;
      (0,_ariakit_core_utils_events__WEBPACK_IMPORTED_MODULE_4__/* .addGlobalEventListener */ .dg)("mousedown", onGlobalMouseDown, true);
      (0,_ariakit_core_utils_events__WEBPACK_IMPORTED_MODULE_4__/* .addGlobalEventListener */ .dg)("keydown", onGlobalKeyDown, true);
      hasInstalledGlobalEventListeners = true;
    }, [focusable]);
    const disabled = focusable && (0,_ariakit_core_utils_misc__WEBPACK_IMPORTED_MODULE_5__/* .disabledFromProps */ .$f)(props);
    const trulyDisabled = !!disabled && !accessibleWhenDisabled;
    const [focusVisible, setFocusVisible] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(false);
    (0,react__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
      if (!focusable) return;
      if (trulyDisabled && focusVisible) {
        setFocusVisible(false);
      }
    }, [focusable, trulyDisabled, focusVisible]);
    (0,react__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
      if (!focusable) return;
      if (!focusVisible) return;
      const element = ref.current;
      if (!element) return;
      if (typeof IntersectionObserver === "undefined") return;
      const observer = new IntersectionObserver(() => {
        if (!(0,_ariakit_core_utils_focus__WEBPACK_IMPORTED_MODULE_6__/* .isFocusable */ .tp)(element)) {
          setFocusVisible(false);
        }
      });
      observer.observe(element);
      return () => observer.disconnect();
    }, [focusable, focusVisible]);
    const onKeyPressCapture = useDisableEvent(
      props.onKeyPressCapture,
      disabled
    );
    const onMouseDownCapture = useDisableEvent(
      props.onMouseDownCapture,
      disabled
    );
    const onClickCapture = useDisableEvent(props.onClickCapture, disabled);
    const handleFocusVisible = (event, currentTarget) => {
      if (currentTarget) {
        event.currentTarget = currentTarget;
      }
      if (!focusable) return;
      const element = event.currentTarget;
      if (!element) return;
      if (!(0,_ariakit_core_utils_focus__WEBPACK_IMPORTED_MODULE_6__/* .hasFocus */ .AJ)(element)) return;
      onFocusVisible == null ? void 0 : onFocusVisible(event);
      if (event.defaultPrevented) return;
      element.dataset.focusVisible = "true";
      setFocusVisible(true);
    };
    const onKeyDownCaptureProp = props.onKeyDownCapture;
    const onKeyDownCapture = (0,_CEM7J6TT_js__WEBPACK_IMPORTED_MODULE_2__/* .useEvent */ ._q)((event) => {
      onKeyDownCaptureProp == null ? void 0 : onKeyDownCaptureProp(event);
      if (event.defaultPrevented) return;
      if (!focusable) return;
      if (focusVisible) return;
      if (event.metaKey) return;
      if (event.altKey) return;
      if (event.ctrlKey) return;
      if (!(0,_ariakit_core_utils_events__WEBPACK_IMPORTED_MODULE_4__/* .isSelfTarget */ .uh)(event)) return;
      const element = event.currentTarget;
      const applyFocusVisible = () => handleFocusVisible(event, element);
      (0,_ariakit_core_utils_events__WEBPACK_IMPORTED_MODULE_4__/* .queueBeforeEvent */ .nz)(element, "focusout", applyFocusVisible);
    });
    const onFocusCaptureProp = props.onFocusCapture;
    const onFocusCapture = (0,_CEM7J6TT_js__WEBPACK_IMPORTED_MODULE_2__/* .useEvent */ ._q)((event) => {
      onFocusCaptureProp == null ? void 0 : onFocusCaptureProp(event);
      if (event.defaultPrevented) return;
      if (!focusable) return;
      if (!(0,_ariakit_core_utils_events__WEBPACK_IMPORTED_MODULE_4__/* .isSelfTarget */ .uh)(event)) {
        setFocusVisible(false);
        return;
      }
      const element = event.currentTarget;
      const applyFocusVisible = () => handleFocusVisible(event, element);
      if (isKeyboardModality || isAlwaysFocusVisible(event.target)) {
        (0,_ariakit_core_utils_events__WEBPACK_IMPORTED_MODULE_4__/* .queueBeforeEvent */ .nz)(event.target, "focusout", applyFocusVisible);
      } else {
        setFocusVisible(false);
      }
    });
    const onBlurProp = props.onBlur;
    const onBlur = (0,_CEM7J6TT_js__WEBPACK_IMPORTED_MODULE_2__/* .useEvent */ ._q)((event) => {
      onBlurProp == null ? void 0 : onBlurProp(event);
      if (!focusable) return;
      if (!(0,_ariakit_core_utils_events__WEBPACK_IMPORTED_MODULE_4__/* .isFocusEventOutside */ .aG)(event)) return;
      event.currentTarget.removeAttribute("data-focus-visible");
      setFocusVisible(false);
    });
    const autoFocusOnShow = (0,react__WEBPACK_IMPORTED_MODULE_0__.useContext)(_SWN3JYXT_js__WEBPACK_IMPORTED_MODULE_7__/* .FocusableContext */ .g);
    const autoFocusRef = (0,_CEM7J6TT_js__WEBPACK_IMPORTED_MODULE_2__/* .useEvent */ ._q)((element) => {
      if (!focusable) return;
      if (!autoFocus) return;
      if (!element) return;
      if (!autoFocusOnShow) return;
      queueMicrotask(() => {
        if ((0,_ariakit_core_utils_focus__WEBPACK_IMPORTED_MODULE_6__/* .hasFocus */ .AJ)(element)) return;
        if (!(0,_ariakit_core_utils_focus__WEBPACK_IMPORTED_MODULE_6__/* .isFocusable */ .tp)(element)) return;
        element.focus();
      });
    });
    const tagName = (0,_CEM7J6TT_js__WEBPACK_IMPORTED_MODULE_2__/* .useTagName */ .vO)(ref);
    const nativeTabbable = focusable && isNativeTabbable(tagName);
    const supportsDisabled = focusable && supportsDisabledAttribute(tagName);
    const [safariTabIndex, setSafariTabIndex] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(false);
    if (isSafariBrowser) {
      (0,react__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
        if (!focusable) return;
        const element = ref.current;
        if (!element) return;
        const tag = element.tagName.toLowerCase();
        const type = element.type;
        setSafariTabIndex(needsSafariTabIndex(tag, type));
      }, [focusable]);
    }
    const styleProp = props.style;
    const style = (0,react__WEBPACK_IMPORTED_MODULE_0__.useMemo)(() => {
      if (trulyDisabled) {
        return { pointerEvents: "none", ...styleProp };
      }
      return styleProp;
    }, [trulyDisabled, styleProp]);
    props = {
      "data-focus-visible": focusable && focusVisible || void 0,
      "data-autofocus": autoFocus || void 0,
      "aria-disabled": disabled || void 0,
      ...props,
      ...metadataProps,
      ref: (0,_CEM7J6TT_js__WEBPACK_IMPORTED_MODULE_2__/* .useMergeRefs */ .SV)(ref, autoFocusRef, props.ref),
      style,
      tabIndex: getTabIndex({
        focusable,
        trulyDisabled,
        nativeTabbable,
        supportsDisabled,
        safariTabIndex,
        tabIndexProp: props.tabIndex
      }),
      disabled: supportsDisabled && trulyDisabled ? true : void 0,
      // TODO: Test Focusable contentEditable.
      contentEditable: disabled ? void 0 : props.contentEditable,
      onKeyPressCapture,
      onClickCapture,
      onMouseDownCapture,
      onKeyDownCapture,
      onFocusCapture,
      onBlur
    };
    return (0,_ariakit_core_utils_misc__WEBPACK_IMPORTED_MODULE_5__/* .removeUndefinedValues */ .HR)(props);
  }
);
var Focusable = (0,_TVXRYIJB_js__WEBPACK_IMPORTED_MODULE_3__/* .forwardRef */ .Rf)(function Focusable2(props) {
  const htmlProps = useFocusable(props);
  return (0,_TVXRYIJB_js__WEBPACK_IMPORTED_MODULE_3__/* .createElement */ .n)(TagName, htmlProps);
});




/***/ }),

/***/ "../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/SF4KD2LD.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   WT: () => (/* binding */ DisclosureScopedContextProvider),
/* harmony export */   sI: () => (/* binding */ DisclosureContextProvider),
/* harmony export */   vO: () => (/* binding */ useDisclosureProviderContext)
/* harmony export */ });
/* unused harmony exports useDisclosureContext, useDisclosureScopedContext */
/* harmony import */ var _TVXRYIJB_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/TVXRYIJB.js");
"use client";


// src/disclosure/disclosure-context.tsx
var ctx = (0,_TVXRYIJB_js__WEBPACK_IMPORTED_MODULE_0__/* .createStoreContext */ .B0)();
var useDisclosureContext = ctx.useContext;
var useDisclosureScopedContext = ctx.useScopedContext;
var useDisclosureProviderContext = ctx.useProviderContext;
var DisclosureContextProvider = ctx.ContextProvider;
var DisclosureScopedContextProvider = ctx.ScopedContextProvider;




/***/ }),

/***/ "../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/SWN3JYXT.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   g: () => (/* binding */ FocusableContext)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
"use client";

// src/focusable/focusable-context.tsx

var FocusableContext = (0,react__WEBPACK_IMPORTED_MODULE_0__.createContext)(true);




/***/ }),

/***/ "../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/TVXRYIJB.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   B0: () => (/* binding */ createStoreContext),
/* harmony export */   Rf: () => (/* binding */ forwardRef2),
/* harmony export */   ab: () => (/* binding */ createHook),
/* harmony export */   n: () => (/* binding */ createElement),
/* harmony export */   ph: () => (/* binding */ memo2)
/* harmony export */ });
/* harmony import */ var _CEM7J6TT_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/CEM7J6TT.js");
/* harmony import */ var _YXGXYGQX_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/YXGXYGQX.js");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
"use client";



// src/utils/system.tsx


function forwardRef2(render) {
  const Role = react__WEBPACK_IMPORTED_MODULE_0__.forwardRef(
    // @ts-ignore Incompatible with React 19 types. Ignore for now.
    (props, ref) => render({ ...props, ref })
  );
  Role.displayName = render.displayName || render.name;
  return Role;
}
function memo2(Component, propsAreEqual) {
  return react__WEBPACK_IMPORTED_MODULE_0__.memo(Component, propsAreEqual);
}
function createElement(Type, props) {
  const { wrapElement, render, ...rest } = props;
  const mergedRef = (0,_CEM7J6TT_js__WEBPACK_IMPORTED_MODULE_2__/* .useMergeRefs */ .SV)(props.ref, (0,_YXGXYGQX_js__WEBPACK_IMPORTED_MODULE_3__/* .getRefProperty */ .v1)(render));
  let element;
  if (react__WEBPACK_IMPORTED_MODULE_0__.isValidElement(render)) {
    const renderProps = {
      // @ts-ignore Incompatible with React 19 types. Ignore for now.
      ...render.props,
      ref: mergedRef
    };
    element = react__WEBPACK_IMPORTED_MODULE_0__.cloneElement(render, (0,_YXGXYGQX_js__WEBPACK_IMPORTED_MODULE_3__/* .mergeProps */ .v6)(rest, renderProps));
  } else if (render) {
    element = render(rest);
  } else {
    element = /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(Type, { ...rest });
  }
  if (wrapElement) {
    return wrapElement(element);
  }
  return element;
}
function createHook(useProps) {
  const useRole = (props = {}) => {
    return useProps(props);
  };
  useRole.displayName = useProps.name;
  return useRole;
}
function createStoreContext(providers = [], scopedProviders = []) {
  const context = react__WEBPACK_IMPORTED_MODULE_0__.createContext(void 0);
  const scopedContext = react__WEBPACK_IMPORTED_MODULE_0__.createContext(void 0);
  const useContext2 = () => react__WEBPACK_IMPORTED_MODULE_0__.useContext(context);
  const useScopedContext = (onlyScoped = false) => {
    const scoped = react__WEBPACK_IMPORTED_MODULE_0__.useContext(scopedContext);
    const store = useContext2();
    if (onlyScoped) return scoped;
    return scoped || store;
  };
  const useProviderContext = () => {
    const scoped = react__WEBPACK_IMPORTED_MODULE_0__.useContext(scopedContext);
    const store = useContext2();
    if (scoped && scoped === store) return;
    return store;
  };
  const ContextProvider = (props) => {
    return providers.reduceRight(
      (children, Provider) => /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(Provider, { ...props, children }),
      /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(context.Provider, { ...props })
    );
  };
  const ScopedContextProvider = (props) => {
    return /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(ContextProvider, { ...props, children: scopedProviders.reduceRight(
      (children, Provider) => /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(Provider, { ...props, children }),
      /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(scopedContext.Provider, { ...props })
    ) });
  };
  return {
    context,
    scopedContext,
    useContext: useContext2,
    useScopedContext,
    useProviderContext,
    ContextProvider,
    ScopedContextProvider
  };
}




/***/ }),

/***/ "../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/W43ISTP6.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   XP: () => (/* binding */ DialogScopedContextProvider),
/* harmony export */   YH: () => (/* binding */ DialogHeadingContext),
/* harmony export */   Zg: () => (/* binding */ DialogContextProvider),
/* harmony export */   cH: () => (/* binding */ useDialogProviderContext),
/* harmony export */   oL: () => (/* binding */ DialogDescriptionContext)
/* harmony export */ });
/* unused harmony exports useDialogContext, useDialogScopedContext */
/* harmony import */ var _SF4KD2LD_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/SF4KD2LD.js");
/* harmony import */ var _TVXRYIJB_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/TVXRYIJB.js");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
"use client";



// src/dialog/dialog-context.tsx

var ctx = (0,_TVXRYIJB_js__WEBPACK_IMPORTED_MODULE_1__/* .createStoreContext */ .B0)(
  [_SF4KD2LD_js__WEBPACK_IMPORTED_MODULE_2__/* .DisclosureContextProvider */ .sI],
  [_SF4KD2LD_js__WEBPACK_IMPORTED_MODULE_2__/* .DisclosureScopedContextProvider */ .WT]
);
var useDialogContext = ctx.useContext;
var useDialogScopedContext = ctx.useScopedContext;
var useDialogProviderContext = ctx.useProviderContext;
var DialogContextProvider = ctx.ContextProvider;
var DialogScopedContextProvider = ctx.ScopedContextProvider;
var DialogHeadingContext = (0,react__WEBPACK_IMPORTED_MODULE_0__.createContext)(void 0);
var DialogDescriptionContext = (0,react__WEBPACK_IMPORTED_MODULE_0__.createContext)(void 0);




/***/ }),

/***/ "../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/WLLNEL2X.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   aT: () => (/* binding */ useDisclosureContent),
/* harmony export */   dK: () => (/* binding */ isHidden)
/* harmony export */ });
/* unused harmony export DisclosureContent */
/* harmony import */ var _W43ISTP6_js__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/W43ISTP6.js");
/* harmony import */ var _SF4KD2LD_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/SF4KD2LD.js");
/* harmony import */ var _YAS7X7HB_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/YAS7X7HB.js");
/* harmony import */ var _TVXRYIJB_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/TVXRYIJB.js");
/* harmony import */ var _CEM7J6TT_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/CEM7J6TT.js");
/* harmony import */ var _ariakit_core_utils_misc__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+core@0.4.19/node_modules/@ariakit/core/esm/__chunks/UWJK2WK2.js");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var react_dom__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/react-dom@18.3.1_react@18.3.1/node_modules/react-dom/index.js");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
"use client";






// src/disclosure/disclosure-content.tsx




var TagName = "div";
function afterTimeout(timeoutMs, cb) {
  const timeoutId = setTimeout(cb, timeoutMs);
  return () => clearTimeout(timeoutId);
}
function afterPaint(cb) {
  let raf = requestAnimationFrame(() => {
    raf = requestAnimationFrame(cb);
  });
  return () => cancelAnimationFrame(raf);
}
function parseCSSTime(...times) {
  return times.join(", ").split(", ").reduce((longestTime, currentTimeString) => {
    const multiplier = currentTimeString.endsWith("ms") ? 1 : 1e3;
    const currentTime = Number.parseFloat(currentTimeString || "0s") * multiplier;
    if (currentTime > longestTime) return currentTime;
    return longestTime;
  }, 0);
}
function isHidden(mounted, hidden, alwaysVisible) {
  return !alwaysVisible && hidden !== false && (!mounted || !!hidden);
}
var useDisclosureContent = (0,_TVXRYIJB_js__WEBPACK_IMPORTED_MODULE_3__/* .createHook */ .ab)(function useDisclosureContent2({ store, alwaysVisible, ...props }) {
  const context = (0,_SF4KD2LD_js__WEBPACK_IMPORTED_MODULE_4__/* .useDisclosureProviderContext */ .vO)();
  store = store || context;
  (0,_ariakit_core_utils_misc__WEBPACK_IMPORTED_MODULE_5__/* .invariant */ .V1)(
    store,
     false && 0
  );
  const ref = (0,react__WEBPACK_IMPORTED_MODULE_0__.useRef)(null);
  const id = (0,_CEM7J6TT_js__WEBPACK_IMPORTED_MODULE_6__/* .useId */ .Bi)(props.id);
  const [transition, setTransition] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(null);
  const open = (0,_YAS7X7HB_js__WEBPACK_IMPORTED_MODULE_7__/* .useStoreState */ .O$)(store, "open");
  const mounted = (0,_YAS7X7HB_js__WEBPACK_IMPORTED_MODULE_7__/* .useStoreState */ .O$)(store, "mounted");
  const animated = (0,_YAS7X7HB_js__WEBPACK_IMPORTED_MODULE_7__/* .useStoreState */ .O$)(store, "animated");
  const contentElement = (0,_YAS7X7HB_js__WEBPACK_IMPORTED_MODULE_7__/* .useStoreState */ .O$)(store, "contentElement");
  const otherElement = (0,_YAS7X7HB_js__WEBPACK_IMPORTED_MODULE_7__/* .useStoreState */ .O$)(store.disclosure, "contentElement");
  (0,_CEM7J6TT_js__WEBPACK_IMPORTED_MODULE_6__/* .useSafeLayoutEffect */ .UQ)(() => {
    if (!ref.current) return;
    store == null ? void 0 : store.setContentElement(ref.current);
  }, [store]);
  (0,_CEM7J6TT_js__WEBPACK_IMPORTED_MODULE_6__/* .useSafeLayoutEffect */ .UQ)(() => {
    let previousAnimated;
    store == null ? void 0 : store.setState("animated", (animated2) => {
      previousAnimated = animated2;
      return true;
    });
    return () => {
      if (previousAnimated === void 0) return;
      store == null ? void 0 : store.setState("animated", previousAnimated);
    };
  }, [store]);
  (0,_CEM7J6TT_js__WEBPACK_IMPORTED_MODULE_6__/* .useSafeLayoutEffect */ .UQ)(() => {
    if (!animated) return;
    if (!(contentElement == null ? void 0 : contentElement.isConnected)) {
      setTransition(null);
      return;
    }
    return afterPaint(() => {
      setTransition(open ? "enter" : mounted ? "leave" : null);
    });
  }, [animated, contentElement, open, mounted]);
  (0,_CEM7J6TT_js__WEBPACK_IMPORTED_MODULE_6__/* .useSafeLayoutEffect */ .UQ)(() => {
    if (!store) return;
    if (!animated) return;
    if (!transition) return;
    if (!contentElement) return;
    const stopAnimation = () => store == null ? void 0 : store.setState("animating", false);
    const stopAnimationSync = () => (0,react_dom__WEBPACK_IMPORTED_MODULE_1__.flushSync)(stopAnimation);
    if (transition === "leave" && open) return;
    if (transition === "enter" && !open) return;
    if (typeof animated === "number") {
      const timeout2 = animated;
      return afterTimeout(timeout2, stopAnimationSync);
    }
    const {
      transitionDuration,
      animationDuration,
      transitionDelay,
      animationDelay
    } = getComputedStyle(contentElement);
    const {
      transitionDuration: transitionDuration2 = "0",
      animationDuration: animationDuration2 = "0",
      transitionDelay: transitionDelay2 = "0",
      animationDelay: animationDelay2 = "0"
    } = otherElement ? getComputedStyle(otherElement) : {};
    const delay = parseCSSTime(
      transitionDelay,
      animationDelay,
      transitionDelay2,
      animationDelay2
    );
    const duration = parseCSSTime(
      transitionDuration,
      animationDuration,
      transitionDuration2,
      animationDuration2
    );
    const timeout = delay + duration;
    if (!timeout) {
      if (transition === "enter") {
        store.setState("animated", false);
      }
      stopAnimation();
      return;
    }
    const frameRate = 1e3 / 60;
    const maxTimeout = Math.max(timeout - frameRate, 0);
    return afterTimeout(maxTimeout, stopAnimationSync);
  }, [store, animated, contentElement, otherElement, open, transition]);
  props = (0,_CEM7J6TT_js__WEBPACK_IMPORTED_MODULE_6__/* .useWrapElement */ .w7)(
    props,
    (element) => /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)(_W43ISTP6_js__WEBPACK_IMPORTED_MODULE_8__/* .DialogScopedContextProvider */ .XP, { value: store, children: element }),
    [store]
  );
  const hidden = isHidden(mounted, props.hidden, alwaysVisible);
  const styleProp = props.style;
  const style = (0,react__WEBPACK_IMPORTED_MODULE_0__.useMemo)(() => {
    if (hidden) {
      return { ...styleProp, display: "none" };
    }
    return styleProp;
  }, [hidden, styleProp]);
  props = {
    "data-open": open || void 0,
    "data-enter": transition === "enter" || void 0,
    "data-leave": transition === "leave" || void 0,
    hidden,
    ...props,
    id,
    ref: (0,_CEM7J6TT_js__WEBPACK_IMPORTED_MODULE_6__/* .useMergeRefs */ .SV)(id ? store.setContentElement : null, ref, props.ref),
    style
  };
  return (0,_ariakit_core_utils_misc__WEBPACK_IMPORTED_MODULE_5__/* .removeUndefinedValues */ .HR)(props);
});
var DisclosureContentImpl = (0,_TVXRYIJB_js__WEBPACK_IMPORTED_MODULE_3__/* .forwardRef */ .Rf)(function DisclosureContentImpl2(props) {
  const htmlProps = useDisclosureContent(props);
  return (0,_TVXRYIJB_js__WEBPACK_IMPORTED_MODULE_3__/* .createElement */ .n)(TagName, htmlProps);
});
var DisclosureContent = (0,_TVXRYIJB_js__WEBPACK_IMPORTED_MODULE_3__/* .forwardRef */ .Rf)(function DisclosureContent2({
  unmountOnHide,
  ...props
}) {
  const context = (0,_SF4KD2LD_js__WEBPACK_IMPORTED_MODULE_4__/* .useDisclosureProviderContext */ .vO)();
  const store = props.store || context;
  const mounted = (0,_YAS7X7HB_js__WEBPACK_IMPORTED_MODULE_7__/* .useStoreState */ .O$)(
    store,
    (state) => !unmountOnHide || (state == null ? void 0 : state.mounted)
  );
  if (mounted === false) return null;
  return /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)(DisclosureContentImpl, { ...props });
});




/***/ }),

/***/ "../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/YAS7X7HB.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   O$: () => (/* binding */ useStoreState),
/* harmony export */   PX: () => (/* binding */ useStoreStateObject),
/* harmony export */   Pj: () => (/* binding */ useStore),
/* harmony export */   Tz: () => (/* binding */ useStoreProps)
/* harmony export */ });
/* harmony import */ var _CEM7J6TT_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/CEM7J6TT.js");
/* harmony import */ var _ariakit_core_utils_misc__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+core@0.4.19/node_modules/@ariakit/core/esm/__chunks/UWJK2WK2.js");
/* harmony import */ var _ariakit_core_utils_store__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+core@0.4.19/node_modules/@ariakit/core/esm/__chunks/XTZ53NXG.js");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var use_sync_external_store_shim__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/use-sync-external-store@1.6.0_react@18.3.1/node_modules/use-sync-external-store/shim/index.js");
"use client";


// src/utils/store.tsx




var noopSubscribe = () => () => {
};
function useStoreState(store, keyOrSelector = _ariakit_core_utils_misc__WEBPACK_IMPORTED_MODULE_2__/* .identity */ .D_) {
  const storeSubscribe = react__WEBPACK_IMPORTED_MODULE_0__.useCallback(
    (callback) => {
      if (!store) return noopSubscribe();
      return (0,_ariakit_core_utils_store__WEBPACK_IMPORTED_MODULE_3__/* .subscribe */ .B1)(store, null, callback);
    },
    [store]
  );
  const getSnapshot = () => {
    const key = typeof keyOrSelector === "string" ? keyOrSelector : null;
    const selector = typeof keyOrSelector === "function" ? keyOrSelector : null;
    const state = store == null ? void 0 : store.getState();
    if (selector) return selector(state);
    if (!state) return;
    if (!key) return;
    if (!(0,_ariakit_core_utils_misc__WEBPACK_IMPORTED_MODULE_2__/* .hasOwnProperty */ .mQ)(state, key)) return;
    return state[key];
  };
  return (0,use_sync_external_store_shim__WEBPACK_IMPORTED_MODULE_1__.useSyncExternalStore)(storeSubscribe, getSnapshot, getSnapshot);
}
function useStoreStateObject(store, object) {
  const objRef = react__WEBPACK_IMPORTED_MODULE_0__.useRef(
    {}
  );
  const storeSubscribe = react__WEBPACK_IMPORTED_MODULE_0__.useCallback(
    (callback) => {
      if (!store) return noopSubscribe();
      return (0,_ariakit_core_utils_store__WEBPACK_IMPORTED_MODULE_3__/* .subscribe */ .B1)(store, null, callback);
    },
    [store]
  );
  const getSnapshot = () => {
    const state = store == null ? void 0 : store.getState();
    let updated = false;
    const obj = objRef.current;
    for (const prop in object) {
      const keyOrSelector = object[prop];
      if (typeof keyOrSelector === "function") {
        const value = keyOrSelector(state);
        if (value !== obj[prop]) {
          obj[prop] = value;
          updated = true;
        }
      }
      if (typeof keyOrSelector === "string") {
        if (!state) continue;
        if (!(0,_ariakit_core_utils_misc__WEBPACK_IMPORTED_MODULE_2__/* .hasOwnProperty */ .mQ)(state, keyOrSelector)) continue;
        const value = state[keyOrSelector];
        if (value !== obj[prop]) {
          obj[prop] = value;
          updated = true;
        }
      }
    }
    if (updated) {
      objRef.current = { ...obj };
    }
    return objRef.current;
  };
  return (0,use_sync_external_store_shim__WEBPACK_IMPORTED_MODULE_1__.useSyncExternalStore)(storeSubscribe, getSnapshot, getSnapshot);
}
function useStoreProps(store, props, key, setKey) {
  const value = (0,_ariakit_core_utils_misc__WEBPACK_IMPORTED_MODULE_2__/* .hasOwnProperty */ .mQ)(props, key) ? props[key] : void 0;
  const setValue = setKey ? props[setKey] : void 0;
  const propsRef = (0,_CEM7J6TT_js__WEBPACK_IMPORTED_MODULE_4__/* .useLiveRef */ .hX)({ value, setValue });
  (0,_CEM7J6TT_js__WEBPACK_IMPORTED_MODULE_4__/* .useSafeLayoutEffect */ .UQ)(() => {
    return (0,_ariakit_core_utils_store__WEBPACK_IMPORTED_MODULE_3__/* .sync */ .OH)(store, [key], (state, prev) => {
      const { value: value2, setValue: setValue2 } = propsRef.current;
      if (!setValue2) return;
      if (state[key] === prev[key]) return;
      if (state[key] === value2) return;
      setValue2(state[key]);
    });
  }, [store, key]);
  (0,_CEM7J6TT_js__WEBPACK_IMPORTED_MODULE_4__/* .useSafeLayoutEffect */ .UQ)(() => {
    if (value === void 0) return;
    store.setState(key, value);
    return (0,_ariakit_core_utils_store__WEBPACK_IMPORTED_MODULE_3__/* .batch */ .vA)(store, [key], () => {
      if (value === void 0) return;
      store.setState(key, value);
    });
  });
}
function useStore(createStore, props) {
  const [store, setStore] = react__WEBPACK_IMPORTED_MODULE_0__.useState(() => createStore(props));
  (0,_CEM7J6TT_js__WEBPACK_IMPORTED_MODULE_4__/* .useSafeLayoutEffect */ .UQ)(() => (0,_ariakit_core_utils_store__WEBPACK_IMPORTED_MODULE_3__/* .init */ .Ts)(store), [store]);
  const useState2 = react__WEBPACK_IMPORTED_MODULE_0__.useCallback(
    (keyOrSelector) => useStoreState(store, keyOrSelector),
    [store]
  );
  const memoizedStore = react__WEBPACK_IMPORTED_MODULE_0__.useMemo(
    () => ({ ...store, useState: useState2 }),
    [store, useState2]
  );
  const updateStore = (0,_CEM7J6TT_js__WEBPACK_IMPORTED_MODULE_4__/* .useEvent */ ._q)(() => {
    setStore((store2) => createStore({ ...props, ...store2.getState() }));
  });
  return [memoizedStore, updateStore];
}




/***/ }),

/***/ "../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/YXGXYGQX.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   cZ: () => (/* binding */ setRef),
/* harmony export */   v1: () => (/* binding */ getRefProperty),
/* harmony export */   v6: () => (/* binding */ mergeProps)
/* harmony export */ });
/* unused harmony export isValidElementWithRef */
/* harmony import */ var _ariakit_core_utils_misc__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/@ariakit+core@0.4.19/node_modules/@ariakit/core/esm/__chunks/UWJK2WK2.js");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
"use client";

// src/utils/misc.ts


function setRef(ref, value) {
  if (typeof ref === "function") {
    ref(value);
  } else if (ref) {
    ref.current = value;
  }
}
function isValidElementWithRef(element) {
  if (!element) return false;
  if (!(0,react__WEBPACK_IMPORTED_MODULE_0__.isValidElement)(element)) return false;
  if ("ref" in element.props) return true;
  if ("ref" in element) return true;
  return false;
}
function getRefProperty(element) {
  if (!isValidElementWithRef(element)) return null;
  const props = { ...element.props };
  return props.ref || element.ref;
}
function mergeProps(base, overrides) {
  const props = { ...base };
  for (const key in overrides) {
    if (!(0,_ariakit_core_utils_misc__WEBPACK_IMPORTED_MODULE_1__/* .hasOwnProperty */ .mQ)(overrides, key)) continue;
    if (key === "className") {
      const prop = "className";
      props[prop] = base[prop] ? `${base[prop]} ${overrides[prop]}` : overrides[prop];
      continue;
    }
    if (key === "style") {
      const prop = "style";
      props[prop] = base[prop] ? { ...base[prop], ...overrides[prop] } : overrides[prop];
      continue;
    }
    const overrideValue = overrides[key];
    if (typeof overrideValue === "function" && key.startsWith("on")) {
      const baseValue = base[key];
      if (typeof baseValue === "function") {
        props[key] = (...args) => {
          overrideValue(...args);
          baseValue(...args);
        };
        continue;
      }
    }
    props[key] = overrideValue;
  }
  return props;
}




/***/ }),

/***/ "../../node_modules/.pnpm/@floating-ui+dom@1.7.6/node_modules/@floating-ui/dom/dist/floating-ui.dom.mjs":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  UE: () => (/* binding */ floating_ui_dom_arrow),
  ll: () => (/* binding */ autoUpdate),
  rD: () => (/* binding */ floating_ui_dom_computePosition),
  UU: () => (/* binding */ floating_ui_dom_flip),
  ER: () => (/* binding */ floating_ui_dom_limitShift),
  cY: () => (/* binding */ floating_ui_dom_offset),
  BN: () => (/* binding */ floating_ui_dom_shift),
  Ej: () => (/* binding */ floating_ui_dom_size)
});

// UNUSED EXPORTS: autoPlacement, detectOverflow, getOverflowAncestors, hide, inline, platform

;// ../../node_modules/.pnpm/@floating-ui+utils@0.2.11/node_modules/@floating-ui/utils/dist/floating-ui.utils.mjs
/**
 * Custom positioning reference element.
 * @see https://floating-ui.com/docs/virtual-elements
 */

const floating_ui_utils_sides = (/* unused pure expression or super */ null && (['top', 'right', 'bottom', 'left']));
const alignments = (/* unused pure expression or super */ null && (['start', 'end']));
const floating_ui_utils_placements = /*#__PURE__*/(/* unused pure expression or super */ null && (floating_ui_utils_sides.reduce((acc, side) => acc.concat(side, side + "-" + alignments[0], side + "-" + alignments[1]), [])));
const floating_ui_utils_min = Math.min;
const floating_ui_utils_max = Math.max;
const round = Math.round;
const floor = Math.floor;
const createCoords = v => ({
  x: v,
  y: v
});
const oppositeSideMap = {
  left: 'right',
  right: 'left',
  bottom: 'top',
  top: 'bottom'
};
function clamp(start, value, end) {
  return floating_ui_utils_max(start, floating_ui_utils_min(value, end));
}
function floating_ui_utils_evaluate(value, param) {
  return typeof value === 'function' ? value(param) : value;
}
function floating_ui_utils_getSide(placement) {
  return placement.split('-')[0];
}
function floating_ui_utils_getAlignment(placement) {
  return placement.split('-')[1];
}
function getOppositeAxis(axis) {
  return axis === 'x' ? 'y' : 'x';
}
function getAxisLength(axis) {
  return axis === 'y' ? 'height' : 'width';
}
function floating_ui_utils_getSideAxis(placement) {
  const firstChar = placement[0];
  return firstChar === 't' || firstChar === 'b' ? 'y' : 'x';
}
function getAlignmentAxis(placement) {
  return getOppositeAxis(floating_ui_utils_getSideAxis(placement));
}
function floating_ui_utils_getAlignmentSides(placement, rects, rtl) {
  if (rtl === void 0) {
    rtl = false;
  }
  const alignment = floating_ui_utils_getAlignment(placement);
  const alignmentAxis = getAlignmentAxis(placement);
  const length = getAxisLength(alignmentAxis);
  let mainAlignmentSide = alignmentAxis === 'x' ? alignment === (rtl ? 'end' : 'start') ? 'right' : 'left' : alignment === 'start' ? 'bottom' : 'top';
  if (rects.reference[length] > rects.floating[length]) {
    mainAlignmentSide = getOppositePlacement(mainAlignmentSide);
  }
  return [mainAlignmentSide, getOppositePlacement(mainAlignmentSide)];
}
function getExpandedPlacements(placement) {
  const oppositePlacement = getOppositePlacement(placement);
  return [floating_ui_utils_getOppositeAlignmentPlacement(placement), oppositePlacement, floating_ui_utils_getOppositeAlignmentPlacement(oppositePlacement)];
}
function floating_ui_utils_getOppositeAlignmentPlacement(placement) {
  return placement.includes('start') ? placement.replace('start', 'end') : placement.replace('end', 'start');
}
const lrPlacement = ['left', 'right'];
const rlPlacement = ['right', 'left'];
const tbPlacement = ['top', 'bottom'];
const btPlacement = ['bottom', 'top'];
function getSideList(side, isStart, rtl) {
  switch (side) {
    case 'top':
    case 'bottom':
      if (rtl) return isStart ? rlPlacement : lrPlacement;
      return isStart ? lrPlacement : rlPlacement;
    case 'left':
    case 'right':
      return isStart ? tbPlacement : btPlacement;
    default:
      return [];
  }
}
function getOppositeAxisPlacements(placement, flipAlignment, direction, rtl) {
  const alignment = floating_ui_utils_getAlignment(placement);
  let list = getSideList(floating_ui_utils_getSide(placement), direction === 'start', rtl);
  if (alignment) {
    list = list.map(side => side + "-" + alignment);
    if (flipAlignment) {
      list = list.concat(list.map(floating_ui_utils_getOppositeAlignmentPlacement));
    }
  }
  return list;
}
function getOppositePlacement(placement) {
  const side = floating_ui_utils_getSide(placement);
  return oppositeSideMap[side] + placement.slice(side.length);
}
function expandPaddingObject(padding) {
  return {
    top: 0,
    right: 0,
    bottom: 0,
    left: 0,
    ...padding
  };
}
function floating_ui_utils_getPaddingObject(padding) {
  return typeof padding !== 'number' ? expandPaddingObject(padding) : {
    top: padding,
    right: padding,
    bottom: padding,
    left: padding
  };
}
function floating_ui_utils_rectToClientRect(rect) {
  const {
    x,
    y,
    width,
    height
  } = rect;
  return {
    width,
    height,
    top: y,
    left: x,
    right: x + width,
    bottom: y + height,
    x,
    y
  };
}



;// ../../node_modules/.pnpm/@floating-ui+core@1.7.5/node_modules/@floating-ui/core/dist/floating-ui.core.mjs



function computeCoordsFromPlacement(_ref, placement, rtl) {
  let {
    reference,
    floating
  } = _ref;
  const sideAxis = floating_ui_utils_getSideAxis(placement);
  const alignmentAxis = getAlignmentAxis(placement);
  const alignLength = getAxisLength(alignmentAxis);
  const side = floating_ui_utils_getSide(placement);
  const isVertical = sideAxis === 'y';
  const commonX = reference.x + reference.width / 2 - floating.width / 2;
  const commonY = reference.y + reference.height / 2 - floating.height / 2;
  const commonAlign = reference[alignLength] / 2 - floating[alignLength] / 2;
  let coords;
  switch (side) {
    case 'top':
      coords = {
        x: commonX,
        y: reference.y - floating.height
      };
      break;
    case 'bottom':
      coords = {
        x: commonX,
        y: reference.y + reference.height
      };
      break;
    case 'right':
      coords = {
        x: reference.x + reference.width,
        y: commonY
      };
      break;
    case 'left':
      coords = {
        x: reference.x - floating.width,
        y: commonY
      };
      break;
    default:
      coords = {
        x: reference.x,
        y: reference.y
      };
  }
  switch (floating_ui_utils_getAlignment(placement)) {
    case 'start':
      coords[alignmentAxis] -= commonAlign * (rtl && isVertical ? -1 : 1);
      break;
    case 'end':
      coords[alignmentAxis] += commonAlign * (rtl && isVertical ? -1 : 1);
      break;
  }
  return coords;
}

/**
 * Resolves with an object of overflow side offsets that determine how much the
 * element is overflowing a given clipping boundary on each side.
 * - positive = overflowing the boundary by that number of pixels
 * - negative = how many pixels left before it will overflow
 * - 0 = lies flush with the boundary
 * @see https://floating-ui.com/docs/detectOverflow
 */
async function detectOverflow(state, options) {
  var _await$platform$isEle;
  if (options === void 0) {
    options = {};
  }
  const {
    x,
    y,
    platform,
    rects,
    elements,
    strategy
  } = state;
  const {
    boundary = 'clippingAncestors',
    rootBoundary = 'viewport',
    elementContext = 'floating',
    altBoundary = false,
    padding = 0
  } = floating_ui_utils_evaluate(options, state);
  const paddingObject = floating_ui_utils_getPaddingObject(padding);
  const altContext = elementContext === 'floating' ? 'reference' : 'floating';
  const element = elements[altBoundary ? altContext : elementContext];
  const clippingClientRect = floating_ui_utils_rectToClientRect(await platform.getClippingRect({
    element: ((_await$platform$isEle = await (platform.isElement == null ? void 0 : platform.isElement(element))) != null ? _await$platform$isEle : true) ? element : element.contextElement || (await (platform.getDocumentElement == null ? void 0 : platform.getDocumentElement(elements.floating))),
    boundary,
    rootBoundary,
    strategy
  }));
  const rect = elementContext === 'floating' ? {
    x,
    y,
    width: rects.floating.width,
    height: rects.floating.height
  } : rects.reference;
  const offsetParent = await (platform.getOffsetParent == null ? void 0 : platform.getOffsetParent(elements.floating));
  const offsetScale = (await (platform.isElement == null ? void 0 : platform.isElement(offsetParent))) ? (await (platform.getScale == null ? void 0 : platform.getScale(offsetParent))) || {
    x: 1,
    y: 1
  } : {
    x: 1,
    y: 1
  };
  const elementClientRect = floating_ui_utils_rectToClientRect(platform.convertOffsetParentRelativeRectToViewportRelativeRect ? await platform.convertOffsetParentRelativeRectToViewportRelativeRect({
    elements,
    rect,
    offsetParent,
    strategy
  }) : rect);
  return {
    top: (clippingClientRect.top - elementClientRect.top + paddingObject.top) / offsetScale.y,
    bottom: (elementClientRect.bottom - clippingClientRect.bottom + paddingObject.bottom) / offsetScale.y,
    left: (clippingClientRect.left - elementClientRect.left + paddingObject.left) / offsetScale.x,
    right: (elementClientRect.right - clippingClientRect.right + paddingObject.right) / offsetScale.x
  };
}

// Maximum number of resets that can occur before bailing to avoid infinite reset loops.
const MAX_RESET_COUNT = 50;

/**
 * Computes the `x` and `y` coordinates that will place the floating element
 * next to a given reference element.
 *
 * This export does not have any `platform` interface logic. You will need to
 * write one for the platform you are using Floating UI with.
 */
const computePosition = async (reference, floating, config) => {
  const {
    placement = 'bottom',
    strategy = 'absolute',
    middleware = [],
    platform
  } = config;
  const platformWithDetectOverflow = platform.detectOverflow ? platform : {
    ...platform,
    detectOverflow
  };
  const rtl = await (platform.isRTL == null ? void 0 : platform.isRTL(floating));
  let rects = await platform.getElementRects({
    reference,
    floating,
    strategy
  });
  let {
    x,
    y
  } = computeCoordsFromPlacement(rects, placement, rtl);
  let statefulPlacement = placement;
  let resetCount = 0;
  const middlewareData = {};
  for (let i = 0; i < middleware.length; i++) {
    const currentMiddleware = middleware[i];
    if (!currentMiddleware) {
      continue;
    }
    const {
      name,
      fn
    } = currentMiddleware;
    const {
      x: nextX,
      y: nextY,
      data,
      reset
    } = await fn({
      x,
      y,
      initialPlacement: placement,
      placement: statefulPlacement,
      strategy,
      middlewareData,
      rects,
      platform: platformWithDetectOverflow,
      elements: {
        reference,
        floating
      }
    });
    x = nextX != null ? nextX : x;
    y = nextY != null ? nextY : y;
    middlewareData[name] = {
      ...middlewareData[name],
      ...data
    };
    if (reset && resetCount < MAX_RESET_COUNT) {
      resetCount++;
      if (typeof reset === 'object') {
        if (reset.placement) {
          statefulPlacement = reset.placement;
        }
        if (reset.rects) {
          rects = reset.rects === true ? await platform.getElementRects({
            reference,
            floating,
            strategy
          }) : reset.rects;
        }
        ({
          x,
          y
        } = computeCoordsFromPlacement(rects, statefulPlacement, rtl));
      }
      i = -1;
    }
  }
  return {
    x,
    y,
    placement: statefulPlacement,
    strategy,
    middlewareData
  };
};

/**
 * Provides data to position an inner element of the floating element so that it
 * appears centered to the reference element.
 * @see https://floating-ui.com/docs/arrow
 */
const arrow = options => ({
  name: 'arrow',
  options,
  async fn(state) {
    const {
      x,
      y,
      placement,
      rects,
      platform,
      elements,
      middlewareData
    } = state;
    // Since `element` is required, we don't Partial<> the type.
    const {
      element,
      padding = 0
    } = floating_ui_utils_evaluate(options, state) || {};
    if (element == null) {
      return {};
    }
    const paddingObject = floating_ui_utils_getPaddingObject(padding);
    const coords = {
      x,
      y
    };
    const axis = getAlignmentAxis(placement);
    const length = getAxisLength(axis);
    const arrowDimensions = await platform.getDimensions(element);
    const isYAxis = axis === 'y';
    const minProp = isYAxis ? 'top' : 'left';
    const maxProp = isYAxis ? 'bottom' : 'right';
    const clientProp = isYAxis ? 'clientHeight' : 'clientWidth';
    const endDiff = rects.reference[length] + rects.reference[axis] - coords[axis] - rects.floating[length];
    const startDiff = coords[axis] - rects.reference[axis];
    const arrowOffsetParent = await (platform.getOffsetParent == null ? void 0 : platform.getOffsetParent(element));
    let clientSize = arrowOffsetParent ? arrowOffsetParent[clientProp] : 0;

    // DOM platform can return `window` as the `offsetParent`.
    if (!clientSize || !(await (platform.isElement == null ? void 0 : platform.isElement(arrowOffsetParent)))) {
      clientSize = elements.floating[clientProp] || rects.floating[length];
    }
    const centerToReference = endDiff / 2 - startDiff / 2;

    // If the padding is large enough that it causes the arrow to no longer be
    // centered, modify the padding so that it is centered.
    const largestPossiblePadding = clientSize / 2 - arrowDimensions[length] / 2 - 1;
    const minPadding = floating_ui_utils_min(paddingObject[minProp], largestPossiblePadding);
    const maxPadding = floating_ui_utils_min(paddingObject[maxProp], largestPossiblePadding);

    // Make sure the arrow doesn't overflow the floating element if the center
    // point is outside the floating element's bounds.
    const min$1 = minPadding;
    const max = clientSize - arrowDimensions[length] - maxPadding;
    const center = clientSize / 2 - arrowDimensions[length] / 2 + centerToReference;
    const offset = clamp(min$1, center, max);

    // If the reference is small enough that the arrow's padding causes it to
    // to point to nothing for an aligned placement, adjust the offset of the
    // floating element itself. To ensure `shift()` continues to take action,
    // a single reset is performed when this is true.
    const shouldAddOffset = !middlewareData.arrow && floating_ui_utils_getAlignment(placement) != null && center !== offset && rects.reference[length] / 2 - (center < min$1 ? minPadding : maxPadding) - arrowDimensions[length] / 2 < 0;
    const alignmentOffset = shouldAddOffset ? center < min$1 ? center - min$1 : center - max : 0;
    return {
      [axis]: coords[axis] + alignmentOffset,
      data: {
        [axis]: offset,
        centerOffset: center - offset - alignmentOffset,
        ...(shouldAddOffset && {
          alignmentOffset
        })
      },
      reset: shouldAddOffset
    };
  }
});

function getPlacementList(alignment, autoAlignment, allowedPlacements) {
  const allowedPlacementsSortedByAlignment = alignment ? [...allowedPlacements.filter(placement => getAlignment(placement) === alignment), ...allowedPlacements.filter(placement => getAlignment(placement) !== alignment)] : allowedPlacements.filter(placement => getSide(placement) === placement);
  return allowedPlacementsSortedByAlignment.filter(placement => {
    if (alignment) {
      return getAlignment(placement) === alignment || (autoAlignment ? getOppositeAlignmentPlacement(placement) !== placement : false);
    }
    return true;
  });
}
/**
 * Optimizes the visibility of the floating element by choosing the placement
 * that has the most space available automatically, without needing to specify a
 * preferred placement. Alternative to `flip`.
 * @see https://floating-ui.com/docs/autoPlacement
 */
const autoPlacement = function (options) {
  if (options === void 0) {
    options = {};
  }
  return {
    name: 'autoPlacement',
    options,
    async fn(state) {
      var _middlewareData$autoP, _middlewareData$autoP2, _placementsThatFitOnE;
      const {
        rects,
        middlewareData,
        placement,
        platform,
        elements
      } = state;
      const {
        crossAxis = false,
        alignment,
        allowedPlacements = placements,
        autoAlignment = true,
        ...detectOverflowOptions
      } = evaluate(options, state);
      const placements$1 = alignment !== undefined || allowedPlacements === placements ? getPlacementList(alignment || null, autoAlignment, allowedPlacements) : allowedPlacements;
      const overflow = await platform.detectOverflow(state, detectOverflowOptions);
      const currentIndex = ((_middlewareData$autoP = middlewareData.autoPlacement) == null ? void 0 : _middlewareData$autoP.index) || 0;
      const currentPlacement = placements$1[currentIndex];
      if (currentPlacement == null) {
        return {};
      }
      const alignmentSides = getAlignmentSides(currentPlacement, rects, await (platform.isRTL == null ? void 0 : platform.isRTL(elements.floating)));

      // Make `computeCoords` start from the right place.
      if (placement !== currentPlacement) {
        return {
          reset: {
            placement: placements$1[0]
          }
        };
      }
      const currentOverflows = [overflow[getSide(currentPlacement)], overflow[alignmentSides[0]], overflow[alignmentSides[1]]];
      const allOverflows = [...(((_middlewareData$autoP2 = middlewareData.autoPlacement) == null ? void 0 : _middlewareData$autoP2.overflows) || []), {
        placement: currentPlacement,
        overflows: currentOverflows
      }];
      const nextPlacement = placements$1[currentIndex + 1];

      // There are more placements to check.
      if (nextPlacement) {
        return {
          data: {
            index: currentIndex + 1,
            overflows: allOverflows
          },
          reset: {
            placement: nextPlacement
          }
        };
      }
      const placementsSortedByMostSpace = allOverflows.map(d => {
        const alignment = getAlignment(d.placement);
        return [d.placement, alignment && crossAxis ?
        // Check along the mainAxis and main crossAxis side.
        d.overflows.slice(0, 2).reduce((acc, v) => acc + v, 0) :
        // Check only the mainAxis.
        d.overflows[0], d.overflows];
      }).sort((a, b) => a[1] - b[1]);
      const placementsThatFitOnEachSide = placementsSortedByMostSpace.filter(d => d[2].slice(0,
      // Aligned placements should not check their opposite crossAxis
      // side.
      getAlignment(d[0]) ? 2 : 3).every(v => v <= 0));
      const resetPlacement = ((_placementsThatFitOnE = placementsThatFitOnEachSide[0]) == null ? void 0 : _placementsThatFitOnE[0]) || placementsSortedByMostSpace[0][0];
      if (resetPlacement !== placement) {
        return {
          data: {
            index: currentIndex + 1,
            overflows: allOverflows
          },
          reset: {
            placement: resetPlacement
          }
        };
      }
      return {};
    }
  };
};

/**
 * Optimizes the visibility of the floating element by flipping the `placement`
 * in order to keep it in view when the preferred placement(s) will overflow the
 * clipping boundary. Alternative to `autoPlacement`.
 * @see https://floating-ui.com/docs/flip
 */
const flip = function (options) {
  if (options === void 0) {
    options = {};
  }
  return {
    name: 'flip',
    options,
    async fn(state) {
      var _middlewareData$arrow, _middlewareData$flip;
      const {
        placement,
        middlewareData,
        rects,
        initialPlacement,
        platform,
        elements
      } = state;
      const {
        mainAxis: checkMainAxis = true,
        crossAxis: checkCrossAxis = true,
        fallbackPlacements: specifiedFallbackPlacements,
        fallbackStrategy = 'bestFit',
        fallbackAxisSideDirection = 'none',
        flipAlignment = true,
        ...detectOverflowOptions
      } = floating_ui_utils_evaluate(options, state);

      // If a reset by the arrow was caused due to an alignment offset being
      // added, we should skip any logic now since `flip()` has already done its
      // work.
      // https://github.com/floating-ui/floating-ui/issues/2549#issuecomment-1719601643
      if ((_middlewareData$arrow = middlewareData.arrow) != null && _middlewareData$arrow.alignmentOffset) {
        return {};
      }
      const side = floating_ui_utils_getSide(placement);
      const initialSideAxis = floating_ui_utils_getSideAxis(initialPlacement);
      const isBasePlacement = floating_ui_utils_getSide(initialPlacement) === initialPlacement;
      const rtl = await (platform.isRTL == null ? void 0 : platform.isRTL(elements.floating));
      const fallbackPlacements = specifiedFallbackPlacements || (isBasePlacement || !flipAlignment ? [getOppositePlacement(initialPlacement)] : getExpandedPlacements(initialPlacement));
      const hasFallbackAxisSideDirection = fallbackAxisSideDirection !== 'none';
      if (!specifiedFallbackPlacements && hasFallbackAxisSideDirection) {
        fallbackPlacements.push(...getOppositeAxisPlacements(initialPlacement, flipAlignment, fallbackAxisSideDirection, rtl));
      }
      const placements = [initialPlacement, ...fallbackPlacements];
      const overflow = await platform.detectOverflow(state, detectOverflowOptions);
      const overflows = [];
      let overflowsData = ((_middlewareData$flip = middlewareData.flip) == null ? void 0 : _middlewareData$flip.overflows) || [];
      if (checkMainAxis) {
        overflows.push(overflow[side]);
      }
      if (checkCrossAxis) {
        const sides = floating_ui_utils_getAlignmentSides(placement, rects, rtl);
        overflows.push(overflow[sides[0]], overflow[sides[1]]);
      }
      overflowsData = [...overflowsData, {
        placement,
        overflows
      }];

      // One or more sides is overflowing.
      if (!overflows.every(side => side <= 0)) {
        var _middlewareData$flip2, _overflowsData$filter;
        const nextIndex = (((_middlewareData$flip2 = middlewareData.flip) == null ? void 0 : _middlewareData$flip2.index) || 0) + 1;
        const nextPlacement = placements[nextIndex];
        if (nextPlacement) {
          const ignoreCrossAxisOverflow = checkCrossAxis === 'alignment' ? initialSideAxis !== floating_ui_utils_getSideAxis(nextPlacement) : false;
          if (!ignoreCrossAxisOverflow ||
          // We leave the current main axis only if every placement on that axis
          // overflows the main axis.
          overflowsData.every(d => floating_ui_utils_getSideAxis(d.placement) === initialSideAxis ? d.overflows[0] > 0 : true)) {
            // Try next placement and re-run the lifecycle.
            return {
              data: {
                index: nextIndex,
                overflows: overflowsData
              },
              reset: {
                placement: nextPlacement
              }
            };
          }
        }

        // First, find the candidates that fit on the mainAxis side of overflow,
        // then find the placement that fits the best on the main crossAxis side.
        let resetPlacement = (_overflowsData$filter = overflowsData.filter(d => d.overflows[0] <= 0).sort((a, b) => a.overflows[1] - b.overflows[1])[0]) == null ? void 0 : _overflowsData$filter.placement;

        // Otherwise fallback.
        if (!resetPlacement) {
          switch (fallbackStrategy) {
            case 'bestFit':
              {
                var _overflowsData$filter2;
                const placement = (_overflowsData$filter2 = overflowsData.filter(d => {
                  if (hasFallbackAxisSideDirection) {
                    const currentSideAxis = floating_ui_utils_getSideAxis(d.placement);
                    return currentSideAxis === initialSideAxis ||
                    // Create a bias to the `y` side axis due to horizontal
                    // reading directions favoring greater width.
                    currentSideAxis === 'y';
                  }
                  return true;
                }).map(d => [d.placement, d.overflows.filter(overflow => overflow > 0).reduce((acc, overflow) => acc + overflow, 0)]).sort((a, b) => a[1] - b[1])[0]) == null ? void 0 : _overflowsData$filter2[0];
                if (placement) {
                  resetPlacement = placement;
                }
                break;
              }
            case 'initialPlacement':
              resetPlacement = initialPlacement;
              break;
          }
        }
        if (placement !== resetPlacement) {
          return {
            reset: {
              placement: resetPlacement
            }
          };
        }
      }
      return {};
    }
  };
};

function getSideOffsets(overflow, rect) {
  return {
    top: overflow.top - rect.height,
    right: overflow.right - rect.width,
    bottom: overflow.bottom - rect.height,
    left: overflow.left - rect.width
  };
}
function isAnySideFullyClipped(overflow) {
  return sides.some(side => overflow[side] >= 0);
}
/**
 * Provides data to hide the floating element in applicable situations, such as
 * when it is not in the same clipping context as the reference element.
 * @see https://floating-ui.com/docs/hide
 */
const hide = function (options) {
  if (options === void 0) {
    options = {};
  }
  return {
    name: 'hide',
    options,
    async fn(state) {
      const {
        rects,
        platform
      } = state;
      const {
        strategy = 'referenceHidden',
        ...detectOverflowOptions
      } = evaluate(options, state);
      switch (strategy) {
        case 'referenceHidden':
          {
            const overflow = await platform.detectOverflow(state, {
              ...detectOverflowOptions,
              elementContext: 'reference'
            });
            const offsets = getSideOffsets(overflow, rects.reference);
            return {
              data: {
                referenceHiddenOffsets: offsets,
                referenceHidden: isAnySideFullyClipped(offsets)
              }
            };
          }
        case 'escaped':
          {
            const overflow = await platform.detectOverflow(state, {
              ...detectOverflowOptions,
              altBoundary: true
            });
            const offsets = getSideOffsets(overflow, rects.floating);
            return {
              data: {
                escapedOffsets: offsets,
                escaped: isAnySideFullyClipped(offsets)
              }
            };
          }
        default:
          {
            return {};
          }
      }
    }
  };
};

function getBoundingRect(rects) {
  const minX = min(...rects.map(rect => rect.left));
  const minY = min(...rects.map(rect => rect.top));
  const maxX = max(...rects.map(rect => rect.right));
  const maxY = max(...rects.map(rect => rect.bottom));
  return {
    x: minX,
    y: minY,
    width: maxX - minX,
    height: maxY - minY
  };
}
function getRectsByLine(rects) {
  const sortedRects = rects.slice().sort((a, b) => a.y - b.y);
  const groups = [];
  let prevRect = null;
  for (let i = 0; i < sortedRects.length; i++) {
    const rect = sortedRects[i];
    if (!prevRect || rect.y - prevRect.y > prevRect.height / 2) {
      groups.push([rect]);
    } else {
      groups[groups.length - 1].push(rect);
    }
    prevRect = rect;
  }
  return groups.map(rect => rectToClientRect(getBoundingRect(rect)));
}
/**
 * Provides improved positioning for inline reference elements that can span
 * over multiple lines, such as hyperlinks or range selections.
 * @see https://floating-ui.com/docs/inline
 */
const inline = function (options) {
  if (options === void 0) {
    options = {};
  }
  return {
    name: 'inline',
    options,
    async fn(state) {
      const {
        placement,
        elements,
        rects,
        platform,
        strategy
      } = state;
      // A MouseEvent's client{X,Y} coords can be up to 2 pixels off a
      // ClientRect's bounds, despite the event listener being triggered. A
      // padding of 2 seems to handle this issue.
      const {
        padding = 2,
        x,
        y
      } = evaluate(options, state);
      const nativeClientRects = Array.from((await (platform.getClientRects == null ? void 0 : platform.getClientRects(elements.reference))) || []);
      const clientRects = getRectsByLine(nativeClientRects);
      const fallback = rectToClientRect(getBoundingRect(nativeClientRects));
      const paddingObject = getPaddingObject(padding);
      function getBoundingClientRect() {
        // There are two rects and they are disjoined.
        if (clientRects.length === 2 && clientRects[0].left > clientRects[1].right && x != null && y != null) {
          // Find the first rect in which the point is fully inside.
          return clientRects.find(rect => x > rect.left - paddingObject.left && x < rect.right + paddingObject.right && y > rect.top - paddingObject.top && y < rect.bottom + paddingObject.bottom) || fallback;
        }

        // There are 2 or more connected rects.
        if (clientRects.length >= 2) {
          if (getSideAxis(placement) === 'y') {
            const firstRect = clientRects[0];
            const lastRect = clientRects[clientRects.length - 1];
            const isTop = getSide(placement) === 'top';
            const top = firstRect.top;
            const bottom = lastRect.bottom;
            const left = isTop ? firstRect.left : lastRect.left;
            const right = isTop ? firstRect.right : lastRect.right;
            const width = right - left;
            const height = bottom - top;
            return {
              top,
              bottom,
              left,
              right,
              width,
              height,
              x: left,
              y: top
            };
          }
          const isLeftSide = getSide(placement) === 'left';
          const maxRight = max(...clientRects.map(rect => rect.right));
          const minLeft = min(...clientRects.map(rect => rect.left));
          const measureRects = clientRects.filter(rect => isLeftSide ? rect.left === minLeft : rect.right === maxRight);
          const top = measureRects[0].top;
          const bottom = measureRects[measureRects.length - 1].bottom;
          const left = minLeft;
          const right = maxRight;
          const width = right - left;
          const height = bottom - top;
          return {
            top,
            bottom,
            left,
            right,
            width,
            height,
            x: left,
            y: top
          };
        }
        return fallback;
      }
      const resetRects = await platform.getElementRects({
        reference: {
          getBoundingClientRect
        },
        floating: elements.floating,
        strategy
      });
      if (rects.reference.x !== resetRects.reference.x || rects.reference.y !== resetRects.reference.y || rects.reference.width !== resetRects.reference.width || rects.reference.height !== resetRects.reference.height) {
        return {
          reset: {
            rects: resetRects
          }
        };
      }
      return {};
    }
  };
};

const originSides = /*#__PURE__*/new Set(['left', 'top']);

// For type backwards-compatibility, the `OffsetOptions` type was also
// Derivable.

async function convertValueToCoords(state, options) {
  const {
    placement,
    platform,
    elements
  } = state;
  const rtl = await (platform.isRTL == null ? void 0 : platform.isRTL(elements.floating));
  const side = floating_ui_utils_getSide(placement);
  const alignment = floating_ui_utils_getAlignment(placement);
  const isVertical = floating_ui_utils_getSideAxis(placement) === 'y';
  const mainAxisMulti = originSides.has(side) ? -1 : 1;
  const crossAxisMulti = rtl && isVertical ? -1 : 1;
  const rawValue = floating_ui_utils_evaluate(options, state);

  // eslint-disable-next-line prefer-const
  let {
    mainAxis,
    crossAxis,
    alignmentAxis
  } = typeof rawValue === 'number' ? {
    mainAxis: rawValue,
    crossAxis: 0,
    alignmentAxis: null
  } : {
    mainAxis: rawValue.mainAxis || 0,
    crossAxis: rawValue.crossAxis || 0,
    alignmentAxis: rawValue.alignmentAxis
  };
  if (alignment && typeof alignmentAxis === 'number') {
    crossAxis = alignment === 'end' ? alignmentAxis * -1 : alignmentAxis;
  }
  return isVertical ? {
    x: crossAxis * crossAxisMulti,
    y: mainAxis * mainAxisMulti
  } : {
    x: mainAxis * mainAxisMulti,
    y: crossAxis * crossAxisMulti
  };
}

/**
 * Modifies the placement by translating the floating element along the
 * specified axes.
 * A number (shorthand for `mainAxis` or distance), or an axes configuration
 * object may be passed.
 * @see https://floating-ui.com/docs/offset
 */
const offset = function (options) {
  if (options === void 0) {
    options = 0;
  }
  return {
    name: 'offset',
    options,
    async fn(state) {
      var _middlewareData$offse, _middlewareData$arrow;
      const {
        x,
        y,
        placement,
        middlewareData
      } = state;
      const diffCoords = await convertValueToCoords(state, options);

      // If the placement is the same and the arrow caused an alignment offset
      // then we don't need to change the positioning coordinates.
      if (placement === ((_middlewareData$offse = middlewareData.offset) == null ? void 0 : _middlewareData$offse.placement) && (_middlewareData$arrow = middlewareData.arrow) != null && _middlewareData$arrow.alignmentOffset) {
        return {};
      }
      return {
        x: x + diffCoords.x,
        y: y + diffCoords.y,
        data: {
          ...diffCoords,
          placement
        }
      };
    }
  };
};

/**
 * Optimizes the visibility of the floating element by shifting it in order to
 * keep it in view when it will overflow the clipping boundary.
 * @see https://floating-ui.com/docs/shift
 */
const shift = function (options) {
  if (options === void 0) {
    options = {};
  }
  return {
    name: 'shift',
    options,
    async fn(state) {
      const {
        x,
        y,
        placement,
        platform
      } = state;
      const {
        mainAxis: checkMainAxis = true,
        crossAxis: checkCrossAxis = false,
        limiter = {
          fn: _ref => {
            let {
              x,
              y
            } = _ref;
            return {
              x,
              y
            };
          }
        },
        ...detectOverflowOptions
      } = floating_ui_utils_evaluate(options, state);
      const coords = {
        x,
        y
      };
      const overflow = await platform.detectOverflow(state, detectOverflowOptions);
      const crossAxis = floating_ui_utils_getSideAxis(floating_ui_utils_getSide(placement));
      const mainAxis = getOppositeAxis(crossAxis);
      let mainAxisCoord = coords[mainAxis];
      let crossAxisCoord = coords[crossAxis];
      if (checkMainAxis) {
        const minSide = mainAxis === 'y' ? 'top' : 'left';
        const maxSide = mainAxis === 'y' ? 'bottom' : 'right';
        const min = mainAxisCoord + overflow[minSide];
        const max = mainAxisCoord - overflow[maxSide];
        mainAxisCoord = clamp(min, mainAxisCoord, max);
      }
      if (checkCrossAxis) {
        const minSide = crossAxis === 'y' ? 'top' : 'left';
        const maxSide = crossAxis === 'y' ? 'bottom' : 'right';
        const min = crossAxisCoord + overflow[minSide];
        const max = crossAxisCoord - overflow[maxSide];
        crossAxisCoord = clamp(min, crossAxisCoord, max);
      }
      const limitedCoords = limiter.fn({
        ...state,
        [mainAxis]: mainAxisCoord,
        [crossAxis]: crossAxisCoord
      });
      return {
        ...limitedCoords,
        data: {
          x: limitedCoords.x - x,
          y: limitedCoords.y - y,
          enabled: {
            [mainAxis]: checkMainAxis,
            [crossAxis]: checkCrossAxis
          }
        }
      };
    }
  };
};
/**
 * Built-in `limiter` that will stop `shift()` at a certain point.
 */
const limitShift = function (options) {
  if (options === void 0) {
    options = {};
  }
  return {
    options,
    fn(state) {
      const {
        x,
        y,
        placement,
        rects,
        middlewareData
      } = state;
      const {
        offset = 0,
        mainAxis: checkMainAxis = true,
        crossAxis: checkCrossAxis = true
      } = floating_ui_utils_evaluate(options, state);
      const coords = {
        x,
        y
      };
      const crossAxis = floating_ui_utils_getSideAxis(placement);
      const mainAxis = getOppositeAxis(crossAxis);
      let mainAxisCoord = coords[mainAxis];
      let crossAxisCoord = coords[crossAxis];
      const rawOffset = floating_ui_utils_evaluate(offset, state);
      const computedOffset = typeof rawOffset === 'number' ? {
        mainAxis: rawOffset,
        crossAxis: 0
      } : {
        mainAxis: 0,
        crossAxis: 0,
        ...rawOffset
      };
      if (checkMainAxis) {
        const len = mainAxis === 'y' ? 'height' : 'width';
        const limitMin = rects.reference[mainAxis] - rects.floating[len] + computedOffset.mainAxis;
        const limitMax = rects.reference[mainAxis] + rects.reference[len] - computedOffset.mainAxis;
        if (mainAxisCoord < limitMin) {
          mainAxisCoord = limitMin;
        } else if (mainAxisCoord > limitMax) {
          mainAxisCoord = limitMax;
        }
      }
      if (checkCrossAxis) {
        var _middlewareData$offse, _middlewareData$offse2;
        const len = mainAxis === 'y' ? 'width' : 'height';
        const isOriginSide = originSides.has(floating_ui_utils_getSide(placement));
        const limitMin = rects.reference[crossAxis] - rects.floating[len] + (isOriginSide ? ((_middlewareData$offse = middlewareData.offset) == null ? void 0 : _middlewareData$offse[crossAxis]) || 0 : 0) + (isOriginSide ? 0 : computedOffset.crossAxis);
        const limitMax = rects.reference[crossAxis] + rects.reference[len] + (isOriginSide ? 0 : ((_middlewareData$offse2 = middlewareData.offset) == null ? void 0 : _middlewareData$offse2[crossAxis]) || 0) - (isOriginSide ? computedOffset.crossAxis : 0);
        if (crossAxisCoord < limitMin) {
          crossAxisCoord = limitMin;
        } else if (crossAxisCoord > limitMax) {
          crossAxisCoord = limitMax;
        }
      }
      return {
        [mainAxis]: mainAxisCoord,
        [crossAxis]: crossAxisCoord
      };
    }
  };
};

/**
 * Provides data that allows you to change the size of the floating element —
 * for instance, prevent it from overflowing the clipping boundary or match the
 * width of the reference element.
 * @see https://floating-ui.com/docs/size
 */
const size = function (options) {
  if (options === void 0) {
    options = {};
  }
  return {
    name: 'size',
    options,
    async fn(state) {
      var _state$middlewareData, _state$middlewareData2;
      const {
        placement,
        rects,
        platform,
        elements
      } = state;
      const {
        apply = () => {},
        ...detectOverflowOptions
      } = floating_ui_utils_evaluate(options, state);
      const overflow = await platform.detectOverflow(state, detectOverflowOptions);
      const side = floating_ui_utils_getSide(placement);
      const alignment = floating_ui_utils_getAlignment(placement);
      const isYAxis = floating_ui_utils_getSideAxis(placement) === 'y';
      const {
        width,
        height
      } = rects.floating;
      let heightSide;
      let widthSide;
      if (side === 'top' || side === 'bottom') {
        heightSide = side;
        widthSide = alignment === ((await (platform.isRTL == null ? void 0 : platform.isRTL(elements.floating))) ? 'start' : 'end') ? 'left' : 'right';
      } else {
        widthSide = side;
        heightSide = alignment === 'end' ? 'top' : 'bottom';
      }
      const maximumClippingHeight = height - overflow.top - overflow.bottom;
      const maximumClippingWidth = width - overflow.left - overflow.right;
      const overflowAvailableHeight = floating_ui_utils_min(height - overflow[heightSide], maximumClippingHeight);
      const overflowAvailableWidth = floating_ui_utils_min(width - overflow[widthSide], maximumClippingWidth);
      const noShift = !state.middlewareData.shift;
      let availableHeight = overflowAvailableHeight;
      let availableWidth = overflowAvailableWidth;
      if ((_state$middlewareData = state.middlewareData.shift) != null && _state$middlewareData.enabled.x) {
        availableWidth = maximumClippingWidth;
      }
      if ((_state$middlewareData2 = state.middlewareData.shift) != null && _state$middlewareData2.enabled.y) {
        availableHeight = maximumClippingHeight;
      }
      if (noShift && !alignment) {
        const xMin = floating_ui_utils_max(overflow.left, 0);
        const xMax = floating_ui_utils_max(overflow.right, 0);
        const yMin = floating_ui_utils_max(overflow.top, 0);
        const yMax = floating_ui_utils_max(overflow.bottom, 0);
        if (isYAxis) {
          availableWidth = width - 2 * (xMin !== 0 || xMax !== 0 ? xMin + xMax : floating_ui_utils_max(overflow.left, overflow.right));
        } else {
          availableHeight = height - 2 * (yMin !== 0 || yMax !== 0 ? yMin + yMax : floating_ui_utils_max(overflow.top, overflow.bottom));
        }
      }
      await apply({
        ...state,
        availableWidth,
        availableHeight
      });
      const nextDimensions = await platform.getDimensions(elements.floating);
      if (width !== nextDimensions.width || height !== nextDimensions.height) {
        return {
          reset: {
            rects: true
          }
        };
      }
      return {};
    }
  };
};



;// ../../node_modules/.pnpm/@floating-ui+utils@0.2.11/node_modules/@floating-ui/utils/dist/floating-ui.utils.dom.mjs
function hasWindow() {
  return typeof window !== 'undefined';
}
function getNodeName(node) {
  if (isNode(node)) {
    return (node.nodeName || '').toLowerCase();
  }
  // Mocked nodes in testing environments may not be instances of Node. By
  // returning `#document` an infinite loop won't occur.
  // https://github.com/floating-ui/floating-ui/issues/2317
  return '#document';
}
function getWindow(node) {
  var _node$ownerDocument;
  return (node == null || (_node$ownerDocument = node.ownerDocument) == null ? void 0 : _node$ownerDocument.defaultView) || window;
}
function getDocumentElement(node) {
  var _ref;
  return (_ref = (isNode(node) ? node.ownerDocument : node.document) || window.document) == null ? void 0 : _ref.documentElement;
}
function isNode(value) {
  if (!hasWindow()) {
    return false;
  }
  return value instanceof Node || value instanceof getWindow(value).Node;
}
function isElement(value) {
  if (!hasWindow()) {
    return false;
  }
  return value instanceof Element || value instanceof getWindow(value).Element;
}
function isHTMLElement(value) {
  if (!hasWindow()) {
    return false;
  }
  return value instanceof HTMLElement || value instanceof getWindow(value).HTMLElement;
}
function isShadowRoot(value) {
  if (!hasWindow() || typeof ShadowRoot === 'undefined') {
    return false;
  }
  return value instanceof ShadowRoot || value instanceof getWindow(value).ShadowRoot;
}
function isOverflowElement(element) {
  const {
    overflow,
    overflowX,
    overflowY,
    display
  } = floating_ui_utils_dom_getComputedStyle(element);
  return /auto|scroll|overlay|hidden|clip/.test(overflow + overflowY + overflowX) && display !== 'inline' && display !== 'contents';
}
function isTableElement(element) {
  return /^(table|td|th)$/.test(getNodeName(element));
}
function isTopLayer(element) {
  try {
    if (element.matches(':popover-open')) {
      return true;
    }
  } catch (_e) {
    // no-op
  }
  try {
    return element.matches(':modal');
  } catch (_e) {
    return false;
  }
}
const willChangeRe = /transform|translate|scale|rotate|perspective|filter/;
const containRe = /paint|layout|strict|content/;
const isNotNone = value => !!value && value !== 'none';
let isWebKitValue;
function isContainingBlock(elementOrCss) {
  const css = isElement(elementOrCss) ? floating_ui_utils_dom_getComputedStyle(elementOrCss) : elementOrCss;

  // https://developer.mozilla.org/en-US/docs/Web/CSS/Containing_block#identifying_the_containing_block
  // https://drafts.csswg.org/css-transforms-2/#individual-transforms
  return isNotNone(css.transform) || isNotNone(css.translate) || isNotNone(css.scale) || isNotNone(css.rotate) || isNotNone(css.perspective) || !isWebKit() && (isNotNone(css.backdropFilter) || isNotNone(css.filter)) || willChangeRe.test(css.willChange || '') || containRe.test(css.contain || '');
}
function getContainingBlock(element) {
  let currentNode = getParentNode(element);
  while (isHTMLElement(currentNode) && !isLastTraversableNode(currentNode)) {
    if (isContainingBlock(currentNode)) {
      return currentNode;
    } else if (isTopLayer(currentNode)) {
      return null;
    }
    currentNode = getParentNode(currentNode);
  }
  return null;
}
function isWebKit() {
  if (isWebKitValue == null) {
    isWebKitValue = typeof CSS !== 'undefined' && CSS.supports && CSS.supports('-webkit-backdrop-filter', 'none');
  }
  return isWebKitValue;
}
function isLastTraversableNode(node) {
  return /^(html|body|#document)$/.test(getNodeName(node));
}
function floating_ui_utils_dom_getComputedStyle(element) {
  return getWindow(element).getComputedStyle(element);
}
function getNodeScroll(element) {
  if (isElement(element)) {
    return {
      scrollLeft: element.scrollLeft,
      scrollTop: element.scrollTop
    };
  }
  return {
    scrollLeft: element.scrollX,
    scrollTop: element.scrollY
  };
}
function getParentNode(node) {
  if (getNodeName(node) === 'html') {
    return node;
  }
  const result =
  // Step into the shadow DOM of the parent of a slotted node.
  node.assignedSlot ||
  // DOM Element detected.
  node.parentNode ||
  // ShadowRoot detected.
  isShadowRoot(node) && node.host ||
  // Fallback.
  getDocumentElement(node);
  return isShadowRoot(result) ? result.host : result;
}
function getNearestOverflowAncestor(node) {
  const parentNode = getParentNode(node);
  if (isLastTraversableNode(parentNode)) {
    return node.ownerDocument ? node.ownerDocument.body : node.body;
  }
  if (isHTMLElement(parentNode) && isOverflowElement(parentNode)) {
    return parentNode;
  }
  return getNearestOverflowAncestor(parentNode);
}
function getOverflowAncestors(node, list, traverseIframes) {
  var _node$ownerDocument2;
  if (list === void 0) {
    list = [];
  }
  if (traverseIframes === void 0) {
    traverseIframes = true;
  }
  const scrollableAncestor = getNearestOverflowAncestor(node);
  const isBody = scrollableAncestor === ((_node$ownerDocument2 = node.ownerDocument) == null ? void 0 : _node$ownerDocument2.body);
  const win = getWindow(scrollableAncestor);
  if (isBody) {
    const frameElement = getFrameElement(win);
    return list.concat(win, win.visualViewport || [], isOverflowElement(scrollableAncestor) ? scrollableAncestor : [], frameElement && traverseIframes ? getOverflowAncestors(frameElement) : []);
  } else {
    return list.concat(scrollableAncestor, getOverflowAncestors(scrollableAncestor, [], traverseIframes));
  }
}
function getFrameElement(win) {
  return win.parent && Object.getPrototypeOf(win.parent) ? win.frameElement : null;
}



;// ../../node_modules/.pnpm/@floating-ui+dom@1.7.6/node_modules/@floating-ui/dom/dist/floating-ui.dom.mjs





function getCssDimensions(element) {
  const css = floating_ui_utils_dom_getComputedStyle(element);
  // In testing environments, the `width` and `height` properties are empty
  // strings for SVG elements, returning NaN. Fallback to `0` in this case.
  let width = parseFloat(css.width) || 0;
  let height = parseFloat(css.height) || 0;
  const hasOffset = isHTMLElement(element);
  const offsetWidth = hasOffset ? element.offsetWidth : width;
  const offsetHeight = hasOffset ? element.offsetHeight : height;
  const shouldFallback = round(width) !== offsetWidth || round(height) !== offsetHeight;
  if (shouldFallback) {
    width = offsetWidth;
    height = offsetHeight;
  }
  return {
    width,
    height,
    $: shouldFallback
  };
}

function unwrapElement(element) {
  return !isElement(element) ? element.contextElement : element;
}

function getScale(element) {
  const domElement = unwrapElement(element);
  if (!isHTMLElement(domElement)) {
    return createCoords(1);
  }
  const rect = domElement.getBoundingClientRect();
  const {
    width,
    height,
    $
  } = getCssDimensions(domElement);
  let x = ($ ? round(rect.width) : rect.width) / width;
  let y = ($ ? round(rect.height) : rect.height) / height;

  // 0, NaN, or Infinity should always fallback to 1.

  if (!x || !Number.isFinite(x)) {
    x = 1;
  }
  if (!y || !Number.isFinite(y)) {
    y = 1;
  }
  return {
    x,
    y
  };
}

const noOffsets = /*#__PURE__*/createCoords(0);
function getVisualOffsets(element) {
  const win = getWindow(element);
  if (!isWebKit() || !win.visualViewport) {
    return noOffsets;
  }
  return {
    x: win.visualViewport.offsetLeft,
    y: win.visualViewport.offsetTop
  };
}
function shouldAddVisualOffsets(element, isFixed, floatingOffsetParent) {
  if (isFixed === void 0) {
    isFixed = false;
  }
  if (!floatingOffsetParent || isFixed && floatingOffsetParent !== getWindow(element)) {
    return false;
  }
  return isFixed;
}

function getBoundingClientRect(element, includeScale, isFixedStrategy, offsetParent) {
  if (includeScale === void 0) {
    includeScale = false;
  }
  if (isFixedStrategy === void 0) {
    isFixedStrategy = false;
  }
  const clientRect = element.getBoundingClientRect();
  const domElement = unwrapElement(element);
  let scale = createCoords(1);
  if (includeScale) {
    if (offsetParent) {
      if (isElement(offsetParent)) {
        scale = getScale(offsetParent);
      }
    } else {
      scale = getScale(element);
    }
  }
  const visualOffsets = shouldAddVisualOffsets(domElement, isFixedStrategy, offsetParent) ? getVisualOffsets(domElement) : createCoords(0);
  let x = (clientRect.left + visualOffsets.x) / scale.x;
  let y = (clientRect.top + visualOffsets.y) / scale.y;
  let width = clientRect.width / scale.x;
  let height = clientRect.height / scale.y;
  if (domElement) {
    const win = getWindow(domElement);
    const offsetWin = offsetParent && isElement(offsetParent) ? getWindow(offsetParent) : offsetParent;
    let currentWin = win;
    let currentIFrame = getFrameElement(currentWin);
    while (currentIFrame && offsetParent && offsetWin !== currentWin) {
      const iframeScale = getScale(currentIFrame);
      const iframeRect = currentIFrame.getBoundingClientRect();
      const css = floating_ui_utils_dom_getComputedStyle(currentIFrame);
      const left = iframeRect.left + (currentIFrame.clientLeft + parseFloat(css.paddingLeft)) * iframeScale.x;
      const top = iframeRect.top + (currentIFrame.clientTop + parseFloat(css.paddingTop)) * iframeScale.y;
      x *= iframeScale.x;
      y *= iframeScale.y;
      width *= iframeScale.x;
      height *= iframeScale.y;
      x += left;
      y += top;
      currentWin = getWindow(currentIFrame);
      currentIFrame = getFrameElement(currentWin);
    }
  }
  return floating_ui_utils_rectToClientRect({
    width,
    height,
    x,
    y
  });
}

// If <html> has a CSS width greater than the viewport, then this will be
// incorrect for RTL.
function getWindowScrollBarX(element, rect) {
  const leftScroll = getNodeScroll(element).scrollLeft;
  if (!rect) {
    return getBoundingClientRect(getDocumentElement(element)).left + leftScroll;
  }
  return rect.left + leftScroll;
}

function getHTMLOffset(documentElement, scroll) {
  const htmlRect = documentElement.getBoundingClientRect();
  const x = htmlRect.left + scroll.scrollLeft - getWindowScrollBarX(documentElement, htmlRect);
  const y = htmlRect.top + scroll.scrollTop;
  return {
    x,
    y
  };
}

function convertOffsetParentRelativeRectToViewportRelativeRect(_ref) {
  let {
    elements,
    rect,
    offsetParent,
    strategy
  } = _ref;
  const isFixed = strategy === 'fixed';
  const documentElement = getDocumentElement(offsetParent);
  const topLayer = elements ? isTopLayer(elements.floating) : false;
  if (offsetParent === documentElement || topLayer && isFixed) {
    return rect;
  }
  let scroll = {
    scrollLeft: 0,
    scrollTop: 0
  };
  let scale = createCoords(1);
  const offsets = createCoords(0);
  const isOffsetParentAnElement = isHTMLElement(offsetParent);
  if (isOffsetParentAnElement || !isOffsetParentAnElement && !isFixed) {
    if (getNodeName(offsetParent) !== 'body' || isOverflowElement(documentElement)) {
      scroll = getNodeScroll(offsetParent);
    }
    if (isOffsetParentAnElement) {
      const offsetRect = getBoundingClientRect(offsetParent);
      scale = getScale(offsetParent);
      offsets.x = offsetRect.x + offsetParent.clientLeft;
      offsets.y = offsetRect.y + offsetParent.clientTop;
    }
  }
  const htmlOffset = documentElement && !isOffsetParentAnElement && !isFixed ? getHTMLOffset(documentElement, scroll) : createCoords(0);
  return {
    width: rect.width * scale.x,
    height: rect.height * scale.y,
    x: rect.x * scale.x - scroll.scrollLeft * scale.x + offsets.x + htmlOffset.x,
    y: rect.y * scale.y - scroll.scrollTop * scale.y + offsets.y + htmlOffset.y
  };
}

function getClientRects(element) {
  return Array.from(element.getClientRects());
}

// Gets the entire size of the scrollable document area, even extending outside
// of the `<html>` and `<body>` rect bounds if horizontally scrollable.
function getDocumentRect(element) {
  const html = getDocumentElement(element);
  const scroll = getNodeScroll(element);
  const body = element.ownerDocument.body;
  const width = floating_ui_utils_max(html.scrollWidth, html.clientWidth, body.scrollWidth, body.clientWidth);
  const height = floating_ui_utils_max(html.scrollHeight, html.clientHeight, body.scrollHeight, body.clientHeight);
  let x = -scroll.scrollLeft + getWindowScrollBarX(element);
  const y = -scroll.scrollTop;
  if (floating_ui_utils_dom_getComputedStyle(body).direction === 'rtl') {
    x += floating_ui_utils_max(html.clientWidth, body.clientWidth) - width;
  }
  return {
    width,
    height,
    x,
    y
  };
}

// Safety check: ensure the scrollbar space is reasonable in case this
// calculation is affected by unusual styles.
// Most scrollbars leave 15-18px of space.
const SCROLLBAR_MAX = 25;
function getViewportRect(element, strategy) {
  const win = getWindow(element);
  const html = getDocumentElement(element);
  const visualViewport = win.visualViewport;
  let width = html.clientWidth;
  let height = html.clientHeight;
  let x = 0;
  let y = 0;
  if (visualViewport) {
    width = visualViewport.width;
    height = visualViewport.height;
    const visualViewportBased = isWebKit();
    if (!visualViewportBased || visualViewportBased && strategy === 'fixed') {
      x = visualViewport.offsetLeft;
      y = visualViewport.offsetTop;
    }
  }
  const windowScrollbarX = getWindowScrollBarX(html);
  // <html> `overflow: hidden` + `scrollbar-gutter: stable` reduces the
  // visual width of the <html> but this is not considered in the size
  // of `html.clientWidth`.
  if (windowScrollbarX <= 0) {
    const doc = html.ownerDocument;
    const body = doc.body;
    const bodyStyles = getComputedStyle(body);
    const bodyMarginInline = doc.compatMode === 'CSS1Compat' ? parseFloat(bodyStyles.marginLeft) + parseFloat(bodyStyles.marginRight) || 0 : 0;
    const clippingStableScrollbarWidth = Math.abs(html.clientWidth - body.clientWidth - bodyMarginInline);
    if (clippingStableScrollbarWidth <= SCROLLBAR_MAX) {
      width -= clippingStableScrollbarWidth;
    }
  } else if (windowScrollbarX <= SCROLLBAR_MAX) {
    // If the <body> scrollbar is on the left, the width needs to be extended
    // by the scrollbar amount so there isn't extra space on the right.
    width += windowScrollbarX;
  }
  return {
    width,
    height,
    x,
    y
  };
}

// Returns the inner client rect, subtracting scrollbars if present.
function getInnerBoundingClientRect(element, strategy) {
  const clientRect = getBoundingClientRect(element, true, strategy === 'fixed');
  const top = clientRect.top + element.clientTop;
  const left = clientRect.left + element.clientLeft;
  const scale = isHTMLElement(element) ? getScale(element) : createCoords(1);
  const width = element.clientWidth * scale.x;
  const height = element.clientHeight * scale.y;
  const x = left * scale.x;
  const y = top * scale.y;
  return {
    width,
    height,
    x,
    y
  };
}
function getClientRectFromClippingAncestor(element, clippingAncestor, strategy) {
  let rect;
  if (clippingAncestor === 'viewport') {
    rect = getViewportRect(element, strategy);
  } else if (clippingAncestor === 'document') {
    rect = getDocumentRect(getDocumentElement(element));
  } else if (isElement(clippingAncestor)) {
    rect = getInnerBoundingClientRect(clippingAncestor, strategy);
  } else {
    const visualOffsets = getVisualOffsets(element);
    rect = {
      x: clippingAncestor.x - visualOffsets.x,
      y: clippingAncestor.y - visualOffsets.y,
      width: clippingAncestor.width,
      height: clippingAncestor.height
    };
  }
  return floating_ui_utils_rectToClientRect(rect);
}
function hasFixedPositionAncestor(element, stopNode) {
  const parentNode = getParentNode(element);
  if (parentNode === stopNode || !isElement(parentNode) || isLastTraversableNode(parentNode)) {
    return false;
  }
  return floating_ui_utils_dom_getComputedStyle(parentNode).position === 'fixed' || hasFixedPositionAncestor(parentNode, stopNode);
}

// A "clipping ancestor" is an `overflow` element with the characteristic of
// clipping (or hiding) child elements. This returns all clipping ancestors
// of the given element up the tree.
function getClippingElementAncestors(element, cache) {
  const cachedResult = cache.get(element);
  if (cachedResult) {
    return cachedResult;
  }
  let result = getOverflowAncestors(element, [], false).filter(el => isElement(el) && getNodeName(el) !== 'body');
  let currentContainingBlockComputedStyle = null;
  const elementIsFixed = floating_ui_utils_dom_getComputedStyle(element).position === 'fixed';
  let currentNode = elementIsFixed ? getParentNode(element) : element;

  // https://developer.mozilla.org/en-US/docs/Web/CSS/Containing_block#identifying_the_containing_block
  while (isElement(currentNode) && !isLastTraversableNode(currentNode)) {
    const computedStyle = floating_ui_utils_dom_getComputedStyle(currentNode);
    const currentNodeIsContaining = isContainingBlock(currentNode);
    if (!currentNodeIsContaining && computedStyle.position === 'fixed') {
      currentContainingBlockComputedStyle = null;
    }
    const shouldDropCurrentNode = elementIsFixed ? !currentNodeIsContaining && !currentContainingBlockComputedStyle : !currentNodeIsContaining && computedStyle.position === 'static' && !!currentContainingBlockComputedStyle && (currentContainingBlockComputedStyle.position === 'absolute' || currentContainingBlockComputedStyle.position === 'fixed') || isOverflowElement(currentNode) && !currentNodeIsContaining && hasFixedPositionAncestor(element, currentNode);
    if (shouldDropCurrentNode) {
      // Drop non-containing blocks.
      result = result.filter(ancestor => ancestor !== currentNode);
    } else {
      // Record last containing block for next iteration.
      currentContainingBlockComputedStyle = computedStyle;
    }
    currentNode = getParentNode(currentNode);
  }
  cache.set(element, result);
  return result;
}

// Gets the maximum area that the element is visible in due to any number of
// clipping ancestors.
function getClippingRect(_ref) {
  let {
    element,
    boundary,
    rootBoundary,
    strategy
  } = _ref;
  const elementClippingAncestors = boundary === 'clippingAncestors' ? isTopLayer(element) ? [] : getClippingElementAncestors(element, this._c) : [].concat(boundary);
  const clippingAncestors = [...elementClippingAncestors, rootBoundary];
  const firstRect = getClientRectFromClippingAncestor(element, clippingAncestors[0], strategy);
  let top = firstRect.top;
  let right = firstRect.right;
  let bottom = firstRect.bottom;
  let left = firstRect.left;
  for (let i = 1; i < clippingAncestors.length; i++) {
    const rect = getClientRectFromClippingAncestor(element, clippingAncestors[i], strategy);
    top = floating_ui_utils_max(rect.top, top);
    right = floating_ui_utils_min(rect.right, right);
    bottom = floating_ui_utils_min(rect.bottom, bottom);
    left = floating_ui_utils_max(rect.left, left);
  }
  return {
    width: right - left,
    height: bottom - top,
    x: left,
    y: top
  };
}

function getDimensions(element) {
  const {
    width,
    height
  } = getCssDimensions(element);
  return {
    width,
    height
  };
}

function getRectRelativeToOffsetParent(element, offsetParent, strategy) {
  const isOffsetParentAnElement = isHTMLElement(offsetParent);
  const documentElement = getDocumentElement(offsetParent);
  const isFixed = strategy === 'fixed';
  const rect = getBoundingClientRect(element, true, isFixed, offsetParent);
  let scroll = {
    scrollLeft: 0,
    scrollTop: 0
  };
  const offsets = createCoords(0);

  // If the <body> scrollbar appears on the left (e.g. RTL systems). Use
  // Firefox with layout.scrollbar.side = 3 in about:config to test this.
  function setLeftRTLScrollbarOffset() {
    offsets.x = getWindowScrollBarX(documentElement);
  }
  if (isOffsetParentAnElement || !isOffsetParentAnElement && !isFixed) {
    if (getNodeName(offsetParent) !== 'body' || isOverflowElement(documentElement)) {
      scroll = getNodeScroll(offsetParent);
    }
    if (isOffsetParentAnElement) {
      const offsetRect = getBoundingClientRect(offsetParent, true, isFixed, offsetParent);
      offsets.x = offsetRect.x + offsetParent.clientLeft;
      offsets.y = offsetRect.y + offsetParent.clientTop;
    } else if (documentElement) {
      setLeftRTLScrollbarOffset();
    }
  }
  if (isFixed && !isOffsetParentAnElement && documentElement) {
    setLeftRTLScrollbarOffset();
  }
  const htmlOffset = documentElement && !isOffsetParentAnElement && !isFixed ? getHTMLOffset(documentElement, scroll) : createCoords(0);
  const x = rect.left + scroll.scrollLeft - offsets.x - htmlOffset.x;
  const y = rect.top + scroll.scrollTop - offsets.y - htmlOffset.y;
  return {
    x,
    y,
    width: rect.width,
    height: rect.height
  };
}

function isStaticPositioned(element) {
  return floating_ui_utils_dom_getComputedStyle(element).position === 'static';
}

function getTrueOffsetParent(element, polyfill) {
  if (!isHTMLElement(element) || floating_ui_utils_dom_getComputedStyle(element).position === 'fixed') {
    return null;
  }
  if (polyfill) {
    return polyfill(element);
  }
  let rawOffsetParent = element.offsetParent;

  // Firefox returns the <html> element as the offsetParent if it's non-static,
  // while Chrome and Safari return the <body> element. The <body> element must
  // be used to perform the correct calculations even if the <html> element is
  // non-static.
  if (getDocumentElement(element) === rawOffsetParent) {
    rawOffsetParent = rawOffsetParent.ownerDocument.body;
  }
  return rawOffsetParent;
}

// Gets the closest ancestor positioned element. Handles some edge cases,
// such as table ancestors and cross browser bugs.
function getOffsetParent(element, polyfill) {
  const win = getWindow(element);
  if (isTopLayer(element)) {
    return win;
  }
  if (!isHTMLElement(element)) {
    let svgOffsetParent = getParentNode(element);
    while (svgOffsetParent && !isLastTraversableNode(svgOffsetParent)) {
      if (isElement(svgOffsetParent) && !isStaticPositioned(svgOffsetParent)) {
        return svgOffsetParent;
      }
      svgOffsetParent = getParentNode(svgOffsetParent);
    }
    return win;
  }
  let offsetParent = getTrueOffsetParent(element, polyfill);
  while (offsetParent && isTableElement(offsetParent) && isStaticPositioned(offsetParent)) {
    offsetParent = getTrueOffsetParent(offsetParent, polyfill);
  }
  if (offsetParent && isLastTraversableNode(offsetParent) && isStaticPositioned(offsetParent) && !isContainingBlock(offsetParent)) {
    return win;
  }
  return offsetParent || getContainingBlock(element) || win;
}

const getElementRects = async function (data) {
  const getOffsetParentFn = this.getOffsetParent || getOffsetParent;
  const getDimensionsFn = this.getDimensions;
  const floatingDimensions = await getDimensionsFn(data.floating);
  return {
    reference: getRectRelativeToOffsetParent(data.reference, await getOffsetParentFn(data.floating), data.strategy),
    floating: {
      x: 0,
      y: 0,
      width: floatingDimensions.width,
      height: floatingDimensions.height
    }
  };
};

function isRTL(element) {
  return floating_ui_utils_dom_getComputedStyle(element).direction === 'rtl';
}

const platform = {
  convertOffsetParentRelativeRectToViewportRelativeRect,
  getDocumentElement: getDocumentElement,
  getClippingRect,
  getOffsetParent,
  getElementRects,
  getClientRects,
  getDimensions,
  getScale,
  isElement: isElement,
  isRTL
};

function rectsAreEqual(a, b) {
  return a.x === b.x && a.y === b.y && a.width === b.width && a.height === b.height;
}

// https://samthor.au/2021/observing-dom/
function observeMove(element, onMove) {
  let io = null;
  let timeoutId;
  const root = getDocumentElement(element);
  function cleanup() {
    var _io;
    clearTimeout(timeoutId);
    (_io = io) == null || _io.disconnect();
    io = null;
  }
  function refresh(skip, threshold) {
    if (skip === void 0) {
      skip = false;
    }
    if (threshold === void 0) {
      threshold = 1;
    }
    cleanup();
    const elementRectForRootMargin = element.getBoundingClientRect();
    const {
      left,
      top,
      width,
      height
    } = elementRectForRootMargin;
    if (!skip) {
      onMove();
    }
    if (!width || !height) {
      return;
    }
    const insetTop = floor(top);
    const insetRight = floor(root.clientWidth - (left + width));
    const insetBottom = floor(root.clientHeight - (top + height));
    const insetLeft = floor(left);
    const rootMargin = -insetTop + "px " + -insetRight + "px " + -insetBottom + "px " + -insetLeft + "px";
    const options = {
      rootMargin,
      threshold: floating_ui_utils_max(0, floating_ui_utils_min(1, threshold)) || 1
    };
    let isFirstUpdate = true;
    function handleObserve(entries) {
      const ratio = entries[0].intersectionRatio;
      if (ratio !== threshold) {
        if (!isFirstUpdate) {
          return refresh();
        }
        if (!ratio) {
          // If the reference is clipped, the ratio is 0. Throttle the refresh
          // to prevent an infinite loop of updates.
          timeoutId = setTimeout(() => {
            refresh(false, 1e-7);
          }, 1000);
        } else {
          refresh(false, ratio);
        }
      }
      if (ratio === 1 && !rectsAreEqual(elementRectForRootMargin, element.getBoundingClientRect())) {
        // It's possible that even though the ratio is reported as 1, the
        // element is not actually fully within the IntersectionObserver's root
        // area anymore. This can happen under performance constraints. This may
        // be a bug in the browser's IntersectionObserver implementation. To
        // work around this, we compare the element's bounding rect now with
        // what it was at the time we created the IntersectionObserver. If they
        // are not equal then the element moved, so we refresh.
        refresh();
      }
      isFirstUpdate = false;
    }

    // Older browsers don't support a `document` as the root and will throw an
    // error.
    try {
      io = new IntersectionObserver(handleObserve, {
        ...options,
        // Handle <iframe>s
        root: root.ownerDocument
      });
    } catch (_e) {
      io = new IntersectionObserver(handleObserve, options);
    }
    io.observe(element);
  }
  refresh(true);
  return cleanup;
}

/**
 * Automatically updates the position of the floating element when necessary.
 * Should only be called when the floating element is mounted on the DOM or
 * visible on the screen.
 * @returns cleanup function that should be invoked when the floating element is
 * removed from the DOM or hidden from the screen.
 * @see https://floating-ui.com/docs/autoUpdate
 */
function autoUpdate(reference, floating, update, options) {
  if (options === void 0) {
    options = {};
  }
  const {
    ancestorScroll = true,
    ancestorResize = true,
    elementResize = typeof ResizeObserver === 'function',
    layoutShift = typeof IntersectionObserver === 'function',
    animationFrame = false
  } = options;
  const referenceEl = unwrapElement(reference);
  const ancestors = ancestorScroll || ancestorResize ? [...(referenceEl ? getOverflowAncestors(referenceEl) : []), ...(floating ? getOverflowAncestors(floating) : [])] : [];
  ancestors.forEach(ancestor => {
    ancestorScroll && ancestor.addEventListener('scroll', update, {
      passive: true
    });
    ancestorResize && ancestor.addEventListener('resize', update);
  });
  const cleanupIo = referenceEl && layoutShift ? observeMove(referenceEl, update) : null;
  let reobserveFrame = -1;
  let resizeObserver = null;
  if (elementResize) {
    resizeObserver = new ResizeObserver(_ref => {
      let [firstEntry] = _ref;
      if (firstEntry && firstEntry.target === referenceEl && resizeObserver && floating) {
        // Prevent update loops when using the `size` middleware.
        // https://github.com/floating-ui/floating-ui/issues/1740
        resizeObserver.unobserve(floating);
        cancelAnimationFrame(reobserveFrame);
        reobserveFrame = requestAnimationFrame(() => {
          var _resizeObserver;
          (_resizeObserver = resizeObserver) == null || _resizeObserver.observe(floating);
        });
      }
      update();
    });
    if (referenceEl && !animationFrame) {
      resizeObserver.observe(referenceEl);
    }
    if (floating) {
      resizeObserver.observe(floating);
    }
  }
  let frameId;
  let prevRefRect = animationFrame ? getBoundingClientRect(reference) : null;
  if (animationFrame) {
    frameLoop();
  }
  function frameLoop() {
    const nextRefRect = getBoundingClientRect(reference);
    if (prevRefRect && !rectsAreEqual(prevRefRect, nextRefRect)) {
      update();
    }
    prevRefRect = nextRefRect;
    frameId = requestAnimationFrame(frameLoop);
  }
  update();
  return () => {
    var _resizeObserver2;
    ancestors.forEach(ancestor => {
      ancestorScroll && ancestor.removeEventListener('scroll', update);
      ancestorResize && ancestor.removeEventListener('resize', update);
    });
    cleanupIo == null || cleanupIo();
    (_resizeObserver2 = resizeObserver) == null || _resizeObserver2.disconnect();
    resizeObserver = null;
    if (animationFrame) {
      cancelAnimationFrame(frameId);
    }
  };
}

/**
 * Resolves with an object of overflow side offsets that determine how much the
 * element is overflowing a given clipping boundary on each side.
 * - positive = overflowing the boundary by that number of pixels
 * - negative = how many pixels left before it will overflow
 * - 0 = lies flush with the boundary
 * @see https://floating-ui.com/docs/detectOverflow
 */
const floating_ui_dom_detectOverflow = (/* unused pure expression or super */ null && (detectOverflow$1));

/**
 * Modifies the placement by translating the floating element along the
 * specified axes.
 * A number (shorthand for `mainAxis` or distance), or an axes configuration
 * object may be passed.
 * @see https://floating-ui.com/docs/offset
 */
const floating_ui_dom_offset = offset;

/**
 * Optimizes the visibility of the floating element by choosing the placement
 * that has the most space available automatically, without needing to specify a
 * preferred placement. Alternative to `flip`.
 * @see https://floating-ui.com/docs/autoPlacement
 */
const floating_ui_dom_autoPlacement = (/* unused pure expression or super */ null && (autoPlacement$1));

/**
 * Optimizes the visibility of the floating element by shifting it in order to
 * keep it in view when it will overflow the clipping boundary.
 * @see https://floating-ui.com/docs/shift
 */
const floating_ui_dom_shift = shift;

/**
 * Optimizes the visibility of the floating element by flipping the `placement`
 * in order to keep it in view when the preferred placement(s) will overflow the
 * clipping boundary. Alternative to `autoPlacement`.
 * @see https://floating-ui.com/docs/flip
 */
const floating_ui_dom_flip = flip;

/**
 * Provides data that allows you to change the size of the floating element —
 * for instance, prevent it from overflowing the clipping boundary or match the
 * width of the reference element.
 * @see https://floating-ui.com/docs/size
 */
const floating_ui_dom_size = size;

/**
 * Provides data to hide the floating element in applicable situations, such as
 * when it is not in the same clipping context as the reference element.
 * @see https://floating-ui.com/docs/hide
 */
const floating_ui_dom_hide = (/* unused pure expression or super */ null && (hide$1));

/**
 * Provides data to position an inner element of the floating element so that it
 * appears centered to the reference element.
 * @see https://floating-ui.com/docs/arrow
 */
const floating_ui_dom_arrow = arrow;

/**
 * Provides improved positioning for inline reference elements that can span
 * over multiple lines, such as hyperlinks or range selections.
 * @see https://floating-ui.com/docs/inline
 */
const floating_ui_dom_inline = (/* unused pure expression or super */ null && (inline$1));

/**
 * Built-in `limiter` that will stop `shift()` at a certain point.
 */
const floating_ui_dom_limitShift = limitShift;

/**
 * Computes the `x` and `y` coordinates that will place the floating element
 * next to a given reference element.
 */
const floating_ui_dom_computePosition = (reference, floating, options) => {
  // This caches the expensive `getClippingElementAncestors` function so that
  // multiple lifecycle resets re-use the same result. It only lives for a
  // single call. If other functions become expensive, we can add them as well.
  const cache = new Map();
  const mergedOptions = {
    platform,
    ...options
  };
  const platformWithCache = {
    ...mergedOptions.platform,
    _c: cache
  };
  return computePosition(reference, floating, {
    ...mergedOptions,
    platform: platformWithCache
  });
};




/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/button/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   Ay: () => (/* binding */ button_default)
/* harmony export */ });
/* unused harmony exports Button, UnforwardedButton */
/* harmony import */ var clsx__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
/* harmony import */ var _wordpress_deprecated__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+deprecated@4.48.1/node_modules/@wordpress/deprecated/build-module/index.mjs");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@7.45.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-instance-id/index.mjs");
/* harmony import */ var _tooltip__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/tooltip/index.js");
/* harmony import */ var _icon__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/icon/index.js");
/* harmony import */ var _visually_hidden__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/visually-hidden/component.js");
/* harmony import */ var _popover_utils__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/popover/utils.js");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");









const disabledEventsOnDisabledButton = ["onMouseDown", "onClick"];
function useDeprecatedProps({
  __experimentalIsFocusable,
  isDefault,
  isPrimary,
  isSecondary,
  isTertiary,
  isLink,
  isPressed,
  isSmall,
  size,
  variant,
  describedBy,
  ...otherProps
}) {
  let computedSize = size;
  let computedVariant = variant;
  const newProps = {
    accessibleWhenDisabled: __experimentalIsFocusable,
    // @todo Mark `isPressed` as deprecated
    "aria-pressed": isPressed,
    description: describedBy
  };
  if (isSmall) {
    var _computedSize;
    (_computedSize = computedSize) !== null && _computedSize !== void 0 ? _computedSize : computedSize = "small";
  }
  if (isPrimary) {
    var _computedVariant;
    (_computedVariant = computedVariant) !== null && _computedVariant !== void 0 ? _computedVariant : computedVariant = "primary";
  }
  if (isTertiary) {
    var _computedVariant2;
    (_computedVariant2 = computedVariant) !== null && _computedVariant2 !== void 0 ? _computedVariant2 : computedVariant = "tertiary";
  }
  if (isSecondary) {
    var _computedVariant3;
    (_computedVariant3 = computedVariant) !== null && _computedVariant3 !== void 0 ? _computedVariant3 : computedVariant = "secondary";
  }
  if (isDefault) {
    var _computedVariant4;
    (0,_wordpress_deprecated__WEBPACK_IMPORTED_MODULE_1__/* ["default"] */ .A)("wp.components.Button `isDefault` prop", {
      since: "5.4",
      alternative: 'variant="secondary"'
    });
    (_computedVariant4 = computedVariant) !== null && _computedVariant4 !== void 0 ? _computedVariant4 : computedVariant = "secondary";
  }
  if (isLink) {
    var _computedVariant5;
    (_computedVariant5 = computedVariant) !== null && _computedVariant5 !== void 0 ? _computedVariant5 : computedVariant = "link";
  }
  return {
    ...newProps,
    ...otherProps,
    size: computedSize,
    variant: computedVariant
  };
}
function UnforwardedButton(props, ref) {
  const {
    __next40pxDefaultSize,
    accessibleWhenDisabled,
    isBusy,
    isDestructive,
    className,
    disabled,
    icon,
    iconPosition = "left",
    iconSize,
    showTooltip,
    tooltipPosition,
    shortcut,
    label,
    children,
    size = "default",
    text,
    variant,
    description,
    ...buttonOrAnchorProps
  } = useDeprecatedProps(props);
  const {
    href,
    target,
    "aria-checked": ariaChecked,
    "aria-pressed": ariaPressed,
    "aria-selected": ariaSelected,
    ...additionalProps
  } = "href" in buttonOrAnchorProps ? buttonOrAnchorProps : {
    href: void 0,
    target: void 0,
    ...buttonOrAnchorProps
  };
  const instanceId = (0,_wordpress_compose__WEBPACK_IMPORTED_MODULE_2__/* ["default"] */ .A)(Button, "components-button__description");
  const hasChildren = "string" === typeof children && !!children || Array.isArray(children) && children?.[0] && children[0] !== null && // Tooltip should not considered as a child
  children?.[0]?.props?.className !== "components-tooltip";
  const truthyAriaPressedValues = [true, "true", "mixed"];
  const classes = (0,clsx__WEBPACK_IMPORTED_MODULE_3__/* ["default"] */ .A)("components-button", className, {
    "is-next-40px-default-size": __next40pxDefaultSize,
    "is-secondary": variant === "secondary",
    "is-primary": variant === "primary",
    "is-small": size === "small",
    "is-compact": size === "compact",
    "is-tertiary": variant === "tertiary",
    "is-pressed": truthyAriaPressedValues.includes(ariaPressed),
    "is-pressed-mixed": ariaPressed === "mixed",
    "is-busy": isBusy,
    "is-link": variant === "link",
    "is-destructive": isDestructive,
    "has-text": !!icon && (hasChildren || text),
    "has-icon": !!icon,
    "has-icon-right": iconPosition === "right"
  });
  const trulyDisabled = disabled && !accessibleWhenDisabled;
  const Tag = href !== void 0 && !disabled ? "a" : "button";
  const buttonProps = Tag === "button" ? {
    type: "button",
    disabled: trulyDisabled,
    "aria-checked": ariaChecked,
    "aria-pressed": ariaPressed,
    "aria-selected": ariaSelected
  } : {};
  const anchorProps = Tag === "a" ? {
    href,
    target
  } : {};
  const disableEventProps = {};
  if (disabled && accessibleWhenDisabled) {
    buttonProps["aria-disabled"] = true;
    anchorProps["aria-disabled"] = true;
    for (const disabledEvent of disabledEventsOnDisabledButton) {
      disableEventProps[disabledEvent] = (event) => {
        if (event) {
          event.stopPropagation();
          event.preventDefault();
        }
      };
    }
  }
  const shouldShowTooltip = !trulyDisabled && // An explicit tooltip is passed or...
  (showTooltip && !!label || // There's a shortcut or...
  !!shortcut || // There's a label and...
  !!label && // The children are empty and...
  !children?.length && // The tooltip is not explicitly disabled.
  false !== showTooltip);
  const descriptionId = description ? instanceId : void 0;
  const describedById = additionalProps["aria-describedby"] || descriptionId;
  const commonProps = {
    className: classes,
    "aria-label": additionalProps["aria-label"] || label,
    "aria-describedby": describedById,
    ref
  };
  const elementChildren = /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsxs)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.Fragment, {
    children: [icon && iconPosition === "left" && /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(_icon__WEBPACK_IMPORTED_MODULE_4__/* ["default"] */ .A, {
      icon,
      size: iconSize
    }), text && /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.Fragment, {
      children: text
    }), children, icon && iconPosition === "right" && /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(_icon__WEBPACK_IMPORTED_MODULE_4__/* ["default"] */ .A, {
      icon,
      size: iconSize
    })]
  });
  const element = Tag === "a" ? /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("a", {
    ...anchorProps,
    ...additionalProps,
    ...disableEventProps,
    ...commonProps,
    children: elementChildren
  }) : /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("button", {
    ...buttonProps,
    ...additionalProps,
    ...disableEventProps,
    ...commonProps,
    children: elementChildren
  });
  const tooltipProps = shouldShowTooltip ? {
    text: children?.length && description ? description : label,
    shortcut,
    placement: tooltipPosition && // Convert legacy `position` values to be used with the new `placement` prop
    (0,_popover_utils__WEBPACK_IMPORTED_MODULE_5__/* .positionToPlacement */ .YK)(tooltipPosition)
  } : {};
  return /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsxs)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.Fragment, {
    children: [/* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(_tooltip__WEBPACK_IMPORTED_MODULE_6__/* ["default"] */ .Ay, {
      ...tooltipProps,
      children: element
    }), description && /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(_visually_hidden__WEBPACK_IMPORTED_MODULE_7__/* ["default"] */ .A, {
      children: /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("span", {
        id: descriptionId,
        children: description
      })
    })]
  });
}
const Button = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_8__.forwardRef)(UnforwardedButton);
var button_default = Button;

//# sourceMappingURL=index.js.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/icon/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  A: () => (/* binding */ icon_default)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+primitives@4.48.1_react@18.3.1/node_modules/@wordpress/primitives/build-module/svg/index.mjs
var svg = __webpack_require__("../../node_modules/.pnpm/@wordpress+primitives@4.48.1_react@18.3.1/node_modules/@wordpress/primitives/build-module/svg/index.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js
var jsx_runtime = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
;// ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/dashicon/index.js

function Dashicon({
  icon,
  className,
  size = 20,
  style = {},
  ...extraProps
}) {
  const iconClass = ["dashicon", "dashicons", "dashicons-" + icon, className].filter(Boolean).join(" ");
  const sizeStyles = (
    // using `!=` to catch both 20 and "20"
    // eslint-disable-next-line eqeqeq
    20 != size ? {
      fontSize: `${size}px`,
      width: `${size}px`,
      height: `${size}px`
    } : {}
  );
  const styles = {
    ...sizeStyles,
    ...style
  };
  return /* @__PURE__ */ (0,jsx_runtime.jsx)("span", {
    className: iconClass,
    style: styles,
    ...extraProps
  });
}
var dashicon_default = Dashicon;

//# sourceMappingURL=index.js.map

;// ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/icon/index.js




function Icon({
  icon = null,
  size = "string" === typeof icon ? 20 : 24,
  ...additionalProps
}) {
  if ("string" === typeof icon) {
    return /* @__PURE__ */ (0,jsx_runtime.jsx)(dashicon_default, {
      icon,
      size,
      ...additionalProps
    });
  }
  if ((0,react.isValidElement)(icon) && dashicon_default === icon.type) {
    return (0,react.cloneElement)(icon, {
      ...additionalProps
    });
  }
  if ("function" === typeof icon) {
    return (0,react.createElement)(icon, {
      size,
      ...additionalProps
    });
  }
  if (icon && (icon.type === "svg" || icon.type === svg/* SVG */.t4)) {
    const appliedProps = {
      ...icon.props,
      width: size,
      height: size,
      ...additionalProps
    };
    return /* @__PURE__ */ (0,jsx_runtime.jsx)(svg/* SVG */.t4, {
      ...appliedProps
    });
  }
  if ((0,react.isValidElement)(icon)) {
    return (0,react.cloneElement)(icon, {
      // @ts-ignore Just forwarding the size prop along
      size,
      width: size,
      height: size,
      ...additionalProps
    });
  }
  return icon;
}
var icon_default = Icon;

//# sourceMappingURL=index.js.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/popover/utils.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   Vn: () => (/* binding */ placementToMotionAnimationProps),
/* harmony export */   WS: () => (/* binding */ computePopoverPosition),
/* harmony export */   YK: () => (/* binding */ positionToPlacement),
/* harmony export */   _G: () => (/* binding */ getReferenceElement)
/* harmony export */ });
const POSITION_TO_PLACEMENT = {
  bottom: "bottom",
  top: "top",
  "middle left": "left",
  "middle right": "right",
  "bottom left": "bottom-end",
  "bottom center": "bottom",
  "bottom right": "bottom-start",
  "top left": "top-end",
  "top center": "top",
  "top right": "top-start",
  "middle left left": "left",
  "middle left right": "left",
  "middle left bottom": "left-end",
  "middle left top": "left-start",
  "middle right left": "right",
  "middle right right": "right",
  "middle right bottom": "right-end",
  "middle right top": "right-start",
  "bottom left left": "bottom-end",
  "bottom left right": "bottom-end",
  "bottom left bottom": "bottom-end",
  "bottom left top": "bottom-end",
  "bottom center left": "bottom",
  "bottom center right": "bottom",
  "bottom center bottom": "bottom",
  "bottom center top": "bottom",
  "bottom right left": "bottom-start",
  "bottom right right": "bottom-start",
  "bottom right bottom": "bottom-start",
  "bottom right top": "bottom-start",
  "top left left": "top-end",
  "top left right": "top-end",
  "top left bottom": "top-end",
  "top left top": "top-end",
  "top center left": "top",
  "top center right": "top",
  "top center bottom": "top",
  "top center top": "top",
  "top right left": "top-start",
  "top right right": "top-start",
  "top right bottom": "top-start",
  "top right top": "top-start",
  // `middle`/`middle center [corner?]` positions are associated to a fallback
  // `bottom` placement because there aren't any corresponding placement values.
  middle: "bottom",
  "middle center": "bottom",
  "middle center bottom": "bottom",
  "middle center left": "bottom",
  "middle center right": "bottom",
  "middle center top": "bottom"
};
const positionToPlacement = (position) => {
  var _POSITION_TO_PLACEMEN;
  return (_POSITION_TO_PLACEMEN = POSITION_TO_PLACEMENT[position]) !== null && _POSITION_TO_PLACEMEN !== void 0 ? _POSITION_TO_PLACEMEN : "bottom";
};
const PLACEMENT_TO_ANIMATION_ORIGIN = {
  top: {
    originX: 0.5,
    originY: 1
  },
  // open from bottom, center
  "top-start": {
    originX: 0,
    originY: 1
  },
  // open from bottom, left
  "top-end": {
    originX: 1,
    originY: 1
  },
  // open from bottom, right
  right: {
    originX: 0,
    originY: 0.5
  },
  // open from middle, left
  "right-start": {
    originX: 0,
    originY: 0
  },
  // open from top, left
  "right-end": {
    originX: 0,
    originY: 1
  },
  // open from bottom, left
  bottom: {
    originX: 0.5,
    originY: 0
  },
  // open from top, center
  "bottom-start": {
    originX: 0,
    originY: 0
  },
  // open from top, left
  "bottom-end": {
    originX: 1,
    originY: 0
  },
  // open from top, right
  left: {
    originX: 1,
    originY: 0.5
  },
  // open from middle, right
  "left-start": {
    originX: 1,
    originY: 0
  },
  // open from top, right
  "left-end": {
    originX: 1,
    originY: 1
  },
  // open from bottom, right
  overlay: {
    originX: 0.5,
    originY: 0.5
  }
  // open from center, center
};
const placementToMotionAnimationProps = (placement) => {
  const translateProp = placement.startsWith("top") || placement.startsWith("bottom") ? "translateY" : "translateX";
  const translateDirection = placement.startsWith("top") || placement.startsWith("left") ? 1 : -1;
  return {
    style: PLACEMENT_TO_ANIMATION_ORIGIN[placement],
    initial: {
      opacity: 0,
      scale: 0,
      [translateProp]: `${2 * translateDirection}em`
    },
    animate: {
      opacity: 1,
      scale: 1,
      [translateProp]: 0
    },
    transition: {
      duration: 0.1,
      ease: [0, 0, 0.2, 1]
    }
  };
};
function isTopBottom(anchorRef) {
  return !!anchorRef?.top;
}
function isRef(anchorRef) {
  return !!anchorRef?.current;
}
const getReferenceElement = ({
  anchor,
  anchorRef,
  anchorRect,
  getAnchorRect,
  fallbackReferenceElement
}) => {
  var _referenceElement;
  let referenceElement = null;
  if (anchor) {
    referenceElement = anchor;
  } else if (isTopBottom(anchorRef)) {
    referenceElement = {
      getBoundingClientRect() {
        const topRect = anchorRef.top.getBoundingClientRect();
        const bottomRect = anchorRef.bottom.getBoundingClientRect();
        return new window.DOMRect(topRect.x, topRect.y, topRect.width, bottomRect.bottom - topRect.top);
      }
    };
  } else if (isRef(anchorRef)) {
    referenceElement = anchorRef.current;
  } else if (anchorRef) {
    referenceElement = anchorRef;
  } else if (anchorRect) {
    referenceElement = {
      getBoundingClientRect() {
        return anchorRect;
      }
    };
  } else if (getAnchorRect) {
    referenceElement = {
      getBoundingClientRect() {
        var _rect$x, _rect$y, _rect$width, _rect$height;
        const rect = getAnchorRect(fallbackReferenceElement);
        return new window.DOMRect((_rect$x = rect.x) !== null && _rect$x !== void 0 ? _rect$x : rect.left, (_rect$y = rect.y) !== null && _rect$y !== void 0 ? _rect$y : rect.top, (_rect$width = rect.width) !== null && _rect$width !== void 0 ? _rect$width : rect.right - rect.left, (_rect$height = rect.height) !== null && _rect$height !== void 0 ? _rect$height : rect.bottom - rect.top);
      }
    };
  } else if (fallbackReferenceElement) {
    referenceElement = fallbackReferenceElement.parentElement;
  }
  return (_referenceElement = referenceElement) !== null && _referenceElement !== void 0 ? _referenceElement : null;
};
const computePopoverPosition = (c) => c === null || Number.isNaN(c) ? void 0 : Math.round(c);

//# sourceMappingURL=utils.js.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/shortcut/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (/* binding */ shortcut_default)
/* harmony export */ });
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");

function Shortcut(props) {
  const {
    shortcut,
    className
  } = props;
  if (!shortcut) {
    return null;
  }
  let displayText;
  let ariaLabel;
  if (typeof shortcut === "string") {
    displayText = shortcut;
  }
  if (shortcut !== null && typeof shortcut === "object") {
    displayText = shortcut.display;
    ariaLabel = shortcut.ariaLabel;
  }
  return /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("span", {
    className,
    "aria-label": ariaLabel,
    children: displayText
  });
}
var shortcut_default = Shortcut;

//# sourceMappingURL=index.js.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/tooltip/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  Ay: () => (/* binding */ tooltip_default)
});

// UNUSED EXPORTS: TOOLTIP_DELAY, Tooltip

// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/FL7NPBCY.js
var FL7NPBCY = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/FL7NPBCY.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/YAS7X7HB.js
var YAS7X7HB = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/YAS7X7HB.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+core@0.4.19/node_modules/@ariakit/core/esm/__chunks/IHNLLH3I.js
var IHNLLH3I = __webpack_require__("../../node_modules/.pnpm/@ariakit+core@0.4.19/node_modules/@ariakit/core/esm/__chunks/IHNLLH3I.js");
;// ../../node_modules/.pnpm/@ariakit+core@0.4.19/node_modules/@ariakit/core/esm/__chunks/7KNZCZ55.js
"use client";


// src/dialog/dialog-store.ts
function createDialogStore(props = {}) {
  return (0,IHNLLH3I/* createDisclosureStore */.h)(props);
}



;// ../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/EX4LS7ZU.js
"use client";



// src/dialog/dialog-store.ts

function useDialogStoreProps(store, update, props) {
  return (0,FL7NPBCY/* useDisclosureStoreProps */.W)(store, update, props);
}
function useDialogStore(props = {}) {
  const [store, update] = (0,YAS7X7HB/* useStore */.Pj)(createDialogStore, props);
  return useDialogStoreProps(store, update, props);
}



// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/CEM7J6TT.js
var CEM7J6TT = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/CEM7J6TT.js");
;// ../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/RPKQBJCW.js
"use client";




// src/popover/popover-store.ts

function usePopoverStoreProps(store, update, props) {
  (0,CEM7J6TT/* useUpdateEffect */.w5)(update, [props.popover]);
  (0,YAS7X7HB/* useStoreProps */.Tz)(store, props, "placement");
  return useDialogStoreProps(store, update, props);
}
function usePopoverStore(props = {}) {
  const [store, update] = useStore(Core.createPopoverStore, props);
  return usePopoverStoreProps(store, update, props);
}



;// ../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/NV5OUTYH.js
"use client";



// src/hovercard/hovercard-store.ts

function useHovercardStoreProps(store, update, props) {
  (0,YAS7X7HB/* useStoreProps */.Tz)(store, props, "timeout");
  (0,YAS7X7HB/* useStoreProps */.Tz)(store, props, "showTimeout");
  (0,YAS7X7HB/* useStoreProps */.Tz)(store, props, "hideTimeout");
  return usePopoverStoreProps(store, update, props);
}
function useHovercardStore(props = {}) {
  const [store, update] = useStore(Core.createHovercardStore, props);
  return useHovercardStoreProps(store, update, props);
}



// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+core@0.4.19/node_modules/@ariakit/core/esm/__chunks/XTZ53NXG.js
var XTZ53NXG = __webpack_require__("../../node_modules/.pnpm/@ariakit+core@0.4.19/node_modules/@ariakit/core/esm/__chunks/XTZ53NXG.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+core@0.4.19/node_modules/@ariakit/core/esm/__chunks/UWJK2WK2.js
var UWJK2WK2 = __webpack_require__("../../node_modules/.pnpm/@ariakit+core@0.4.19/node_modules/@ariakit/core/esm/__chunks/UWJK2WK2.js");
;// ../../node_modules/.pnpm/@ariakit+core@0.4.19/node_modules/@ariakit/core/esm/__chunks/MJ4ZJEIM.js
"use client";




// src/popover/popover-store.ts
function createPopoverStore({
  popover: otherPopover,
  ...props
} = {}) {
  const store = (0,XTZ53NXG/* mergeStore */.od)(
    props.store,
    (0,XTZ53NXG/* omit */.cJ)(otherPopover, [
      "arrowElement",
      "anchorElement",
      "contentElement",
      "popoverElement",
      "disclosureElement"
    ])
  );
  (0,XTZ53NXG/* throwOnConflictingProps */.UE)(props, store);
  const syncState = store == null ? void 0 : store.getState();
  const dialog = createDialogStore({ ...props, store });
  const placement = (0,UWJK2WK2/* defaultValue */.Jh)(
    props.placement,
    syncState == null ? void 0 : syncState.placement,
    "bottom"
  );
  const initialState = {
    ...dialog.getState(),
    placement,
    currentPlacement: placement,
    anchorElement: (0,UWJK2WK2/* defaultValue */.Jh)(syncState == null ? void 0 : syncState.anchorElement, null),
    popoverElement: (0,UWJK2WK2/* defaultValue */.Jh)(syncState == null ? void 0 : syncState.popoverElement, null),
    arrowElement: (0,UWJK2WK2/* defaultValue */.Jh)(syncState == null ? void 0 : syncState.arrowElement, null),
    rendered: /* @__PURE__ */ Symbol("rendered")
  };
  const popover = (0,XTZ53NXG/* createStore */.y$)(initialState, dialog, store);
  return {
    ...dialog,
    ...popover,
    setAnchorElement: (element) => popover.setState("anchorElement", element),
    setPopoverElement: (element) => popover.setState("popoverElement", element),
    setArrowElement: (element) => popover.setState("arrowElement", element),
    render: () => popover.setState("rendered", /* @__PURE__ */ Symbol("rendered"))
  };
}



;// ../../node_modules/.pnpm/@ariakit+core@0.4.19/node_modules/@ariakit/core/esm/__chunks/TEVO2DMI.js
"use client";




// src/hovercard/hovercard-store.ts
function createHovercardStore(props = {}) {
  var _a;
  const syncState = (_a = props.store) == null ? void 0 : _a.getState();
  const popover = createPopoverStore({
    ...props,
    placement: (0,UWJK2WK2/* defaultValue */.Jh)(
      props.placement,
      syncState == null ? void 0 : syncState.placement,
      "bottom"
    )
  });
  const timeout = (0,UWJK2WK2/* defaultValue */.Jh)(props.timeout, syncState == null ? void 0 : syncState.timeout, 500);
  const initialState = {
    ...popover.getState(),
    timeout,
    showTimeout: (0,UWJK2WK2/* defaultValue */.Jh)(props.showTimeout, syncState == null ? void 0 : syncState.showTimeout),
    hideTimeout: (0,UWJK2WK2/* defaultValue */.Jh)(props.hideTimeout, syncState == null ? void 0 : syncState.hideTimeout),
    autoFocusOnShow: (0,UWJK2WK2/* defaultValue */.Jh)(syncState == null ? void 0 : syncState.autoFocusOnShow, false)
  };
  const hovercard = (0,XTZ53NXG/* createStore */.y$)(initialState, popover, props.store);
  return {
    ...popover,
    ...hovercard,
    setAutoFocusOnShow: (value) => hovercard.setState("autoFocusOnShow", value)
  };
}



;// ../../node_modules/.pnpm/@ariakit+core@0.4.19/node_modules/@ariakit/core/esm/tooltip/tooltip-store.js
"use client";







// src/tooltip/tooltip-store.ts
function createTooltipStore(props = {}) {
  var _a;
  if (false) {}
  const syncState = (_a = props.store) == null ? void 0 : _a.getState();
  const hovercard = createHovercardStore({
    ...props,
    placement: (0,UWJK2WK2/* defaultValue */.Jh)(
      props.placement,
      syncState == null ? void 0 : syncState.placement,
      "top"
    ),
    hideTimeout: (0,UWJK2WK2/* defaultValue */.Jh)(props.hideTimeout, syncState == null ? void 0 : syncState.hideTimeout, 0)
  });
  const initialState = {
    ...hovercard.getState(),
    type: (0,UWJK2WK2/* defaultValue */.Jh)(props.type, syncState == null ? void 0 : syncState.type, "description"),
    skipTimeout: (0,UWJK2WK2/* defaultValue */.Jh)(props.skipTimeout, syncState == null ? void 0 : syncState.skipTimeout, 300)
  };
  const tooltip = (0,XTZ53NXG/* createStore */.y$)(initialState, hovercard, props.store);
  return {
    ...hovercard,
    ...tooltip
  };
}


;// ../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/4QASCBBU.js
"use client";



// src/tooltip/tooltip-store.ts

function useTooltipStoreProps(store, update, props) {
  (0,YAS7X7HB/* useStoreProps */.Tz)(store, props, "type");
  (0,YAS7X7HB/* useStoreProps */.Tz)(store, props, "skipTimeout");
  return useHovercardStoreProps(store, update, props);
}
function useTooltipStore(props = {}) {
  const [store, update] = (0,YAS7X7HB/* useStore */.Pj)(createTooltipStore, props);
  return useTooltipStoreProps(store, update, props);
}



// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/TVXRYIJB.js
var TVXRYIJB = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/TVXRYIJB.js");
;// ../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/XEQCSYLV.js
"use client";


// src/role/role.tsx
var TagName = "div";
var XEQCSYLV_elements = [
  "a",
  "button",
  "details",
  "dialog",
  "div",
  "form",
  "h1",
  "h2",
  "h3",
  "h4",
  "h5",
  "h6",
  "header",
  "img",
  "input",
  "label",
  "li",
  "nav",
  "ol",
  "p",
  "section",
  "select",
  "span",
  "summary",
  "textarea",
  "ul",
  "svg"
];
var useRole = (0,TVXRYIJB/* createHook */.ab)(
  function useRole2(props) {
    return props;
  }
);
var Role = (0,TVXRYIJB/* forwardRef */.Rf)(function Role2(props) {
  return (0,TVXRYIJB/* createElement */.n)(TagName, props);
});
Object.assign(
  Role,
  XEQCSYLV_elements.reduce((acc, element) => {
    acc[element] = (0,TVXRYIJB/* forwardRef */.Rf)(function Role3(props) {
      return (0,TVXRYIJB/* createElement */.n)(element, props);
    });
    return acc;
  }, {})
);



// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/E4DZA6YM.js
var E4DZA6YM = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/E4DZA6YM.js");
;// ../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/GLWQDZNX.js
"use client";



// src/hovercard/hovercard-context.tsx
var ctx = (0,TVXRYIJB/* createStoreContext */.B0)(
  [E4DZA6YM/* PopoverContextProvider */.wf],
  [E4DZA6YM/* PopoverScopedContextProvider */.s1]
);
var useHovercardContext = ctx.useContext;
var useHovercardScopedContext = ctx.useScopedContext;
var useHovercardProviderContext = ctx.useProviderContext;
var HovercardContextProvider = ctx.ContextProvider;
var HovercardScopedContextProvider = ctx.ScopedContextProvider;



;// ../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/MEKH22TX.js
"use client";



// src/tooltip/tooltip-context.tsx
var MEKH22TX_ctx = (0,TVXRYIJB/* createStoreContext */.B0)(
  [HovercardContextProvider],
  [HovercardScopedContextProvider]
);
var useTooltipContext = MEKH22TX_ctx.useContext;
var useTooltipScopedContext = MEKH22TX_ctx.useScopedContext;
var useTooltipProviderContext = MEKH22TX_ctx.useProviderContext;
var TooltipContextProvider = MEKH22TX_ctx.ContextProvider;
var TooltipScopedContextProvider = MEKH22TX_ctx.ScopedContextProvider;



// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/GR523XJ6.js
var GR523XJ6 = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/GR523XJ6.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+core@0.4.19/node_modules/@ariakit/core/esm/utils/events.js
var events = __webpack_require__("../../node_modules/.pnpm/@ariakit+core@0.4.19/node_modules/@ariakit/core/esm/utils/events.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
;// ../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/AMCJTO4G.js
"use client";





// src/hovercard/hovercard-anchor.tsx



var AMCJTO4G_TagName = "a";
var useHovercardAnchor = (0,TVXRYIJB/* createHook */.ab)(
  function useHovercardAnchor2({ store, showOnHover = true, ...props }) {
    const context = useHovercardProviderContext();
    store = store || context;
    (0,UWJK2WK2/* invariant */.V1)(
      store,
       false && 0
    );
    const disabled = (0,UWJK2WK2/* disabledFromProps */.$f)(props);
    const showTimeoutRef = (0,react.useRef)(0);
    (0,react.useEffect)(() => () => window.clearTimeout(showTimeoutRef.current), []);
    (0,react.useEffect)(() => {
      const onMouseLeave = (event) => {
        if (!store) return;
        const { anchorElement } = store.getState();
        if (!anchorElement) return;
        if (event.target !== anchorElement) return;
        window.clearTimeout(showTimeoutRef.current);
        showTimeoutRef.current = 0;
      };
      return (0,events/* addGlobalEventListener */.dg)("mouseleave", onMouseLeave, true);
    }, [store]);
    const onMouseMoveProp = props.onMouseMove;
    const showOnHoverProp = (0,CEM7J6TT/* useBooleanEvent */.O4)(showOnHover);
    const isMouseMoving = (0,CEM7J6TT/* useIsMouseMoving */.P$)();
    const onMouseMove = (0,CEM7J6TT/* useEvent */._q)((event) => {
      onMouseMoveProp == null ? void 0 : onMouseMoveProp(event);
      if (disabled) return;
      if (!store) return;
      if (event.defaultPrevented) return;
      if (showTimeoutRef.current) return;
      if (!isMouseMoving()) return;
      if (!showOnHoverProp(event)) return;
      const element = event.currentTarget;
      store.setAnchorElement(element);
      store.setDisclosureElement(element);
      const { showTimeout, timeout } = store.getState();
      const showHovercard = () => {
        showTimeoutRef.current = 0;
        if (!isMouseMoving()) return;
        store == null ? void 0 : store.setAnchorElement(element);
        store == null ? void 0 : store.show();
        queueMicrotask(() => {
          store == null ? void 0 : store.setDisclosureElement(element);
        });
      };
      const timeoutMs = showTimeout != null ? showTimeout : timeout;
      if (timeoutMs === 0) {
        showHovercard();
      } else {
        showTimeoutRef.current = window.setTimeout(showHovercard, timeoutMs);
      }
    });
    const onClickProp = props.onClick;
    const onClick = (0,CEM7J6TT/* useEvent */._q)((event) => {
      onClickProp == null ? void 0 : onClickProp(event);
      if (!store) return;
      window.clearTimeout(showTimeoutRef.current);
      showTimeoutRef.current = 0;
    });
    const ref = (0,react.useCallback)(
      (element) => {
        if (!store) return;
        const { anchorElement } = store.getState();
        if (anchorElement == null ? void 0 : anchorElement.isConnected) return;
        store.setAnchorElement(element);
      },
      [store]
    );
    props = {
      ...props,
      ref: (0,CEM7J6TT/* useMergeRefs */.SV)(ref, props.ref),
      onMouseMove,
      onClick
    };
    props = (0,GR523XJ6/* useFocusable */.W)(props);
    return props;
  }
);
var HovercardAnchor = (0,TVXRYIJB/* forwardRef */.Rf)(function HovercardAnchor2(props) {
  const htmlProps = useHovercardAnchor(props);
  return (0,TVXRYIJB/* createElement */.n)(AMCJTO4G_TagName, htmlProps);
});



;// ../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/tooltip/tooltip-anchor.js
"use client";













// src/tooltip/tooltip-anchor.tsx



var tooltip_anchor_TagName = "div";
var globalStore = (0,XTZ53NXG/* createStore */.y$)({
  activeStore: null
});
function createRemoveStoreCallback(store) {
  return () => {
    const { activeStore } = globalStore.getState();
    if (activeStore !== store) return;
    globalStore.setState("activeStore", null);
  };
}
var useTooltipAnchor = (0,TVXRYIJB/* createHook */.ab)(
  function useTooltipAnchor2({ store, showOnHover = true, ...props }) {
    const context = useTooltipProviderContext();
    store = store || context;
    (0,UWJK2WK2/* invariant */.V1)(
      store,
       false && 0
    );
    const canShowOnHoverRef = (0,react.useRef)(false);
    (0,react.useEffect)(() => {
      return (0,XTZ53NXG/* sync */.OH)(store, ["mounted"], (state) => {
        if (state.mounted) return;
        canShowOnHoverRef.current = false;
      });
    }, [store]);
    (0,react.useEffect)(() => {
      if (!store) return;
      return (0,UWJK2WK2/* chain */.cy)(
        // Immediately remove the current store from the global store when
        // the component unmounts. This is useful, for example, to avoid
        // showing tooltips immediately on serial tests.
        createRemoveStoreCallback(store),
        (0,XTZ53NXG/* sync */.OH)(store, ["mounted", "skipTimeout"], (state) => {
          if (!store) return;
          if (state.mounted) {
            const { activeStore } = globalStore.getState();
            if (activeStore !== store) {
              activeStore == null ? void 0 : activeStore.hide();
            }
            return globalStore.setState("activeStore", store);
          }
          const id = setTimeout(
            createRemoveStoreCallback(store),
            state.skipTimeout
          );
          return () => clearTimeout(id);
        })
      );
    }, [store]);
    const onMouseEnterProp = props.onMouseEnter;
    const onMouseEnter = (0,CEM7J6TT/* useEvent */._q)((event) => {
      onMouseEnterProp == null ? void 0 : onMouseEnterProp(event);
      canShowOnHoverRef.current = true;
    });
    const onFocusVisibleProp = props.onFocusVisible;
    const onFocusVisible = (0,CEM7J6TT/* useEvent */._q)((event) => {
      onFocusVisibleProp == null ? void 0 : onFocusVisibleProp(event);
      if (event.defaultPrevented) return;
      store == null ? void 0 : store.setAnchorElement(event.currentTarget);
      store == null ? void 0 : store.show();
    });
    const onBlurProp = props.onBlur;
    const onBlur = (0,CEM7J6TT/* useEvent */._q)((event) => {
      onBlurProp == null ? void 0 : onBlurProp(event);
      if (event.defaultPrevented) return;
      const { activeStore } = globalStore.getState();
      canShowOnHoverRef.current = false;
      if (activeStore === store) {
        globalStore.setState("activeStore", null);
      }
    });
    const type = (0,YAS7X7HB/* useStoreState */.O$)(store, "type");
    const contentId = (0,YAS7X7HB/* useStoreState */.O$)(store, (state) => {
      var _a;
      return (_a = state.contentElement) == null ? void 0 : _a.id;
    });
    props = {
      "aria-labelledby": type === "label" && props["aria-label"] == null ? contentId : void 0,
      ...props,
      onMouseEnter,
      onFocusVisible,
      onBlur
    };
    props = useHovercardAnchor({
      store,
      showOnHover(event) {
        if (!canShowOnHoverRef.current) return false;
        if ((0,UWJK2WK2/* isFalsyBooleanCallback */.zO)(showOnHover, event)) return false;
        const { activeStore } = globalStore.getState();
        if (!activeStore) return true;
        store == null ? void 0 : store.show();
        return false;
      },
      ...props
    });
    return props;
  }
);
var TooltipAnchor = (0,TVXRYIJB/* forwardRef */.Rf)(function TooltipAnchor2(props) {
  const htmlProps = useTooltipAnchor(props);
  return (0,TVXRYIJB/* createElement */.n)(tooltip_anchor_TagName, htmlProps);
});


;// ../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/PYFWN42T.js
"use client";

// src/hovercard/utils/polygon.ts
function getEventPoint(event) {
  return [event.clientX, event.clientY];
}
function isPointInPolygon(point, polygon) {
  const [x, y] = point;
  let inside = false;
  const length = polygon.length;
  for (let l = length, i = 0, j = l - 1; i < l; j = i++) {
    const currentPoint = polygon[i];
    const previousPoint = polygon[j];
    const vertexPoint = polygon[j === 0 ? l - 1 : j - 1];
    if (currentPoint == null) return false;
    if (previousPoint == null) return false;
    if (vertexPoint == null) return false;
    const [xi, yi] = currentPoint;
    const [xj, yj] = previousPoint;
    const [, vy] = vertexPoint;
    const where = (yi - yj) * (x - xi) - (xi - xj) * (y - yi);
    if (yj < yi) {
      if (y >= yj && y < yi) {
        if (where === 0) return true;
        if (where > 0) {
          if (y === yj) {
            if (y > vy) {
              inside = !inside;
            }
          } else {
            inside = !inside;
          }
        }
      }
    } else if (yi < yj) {
      if (y > yi && y <= yj) {
        if (where === 0) return true;
        if (where < 0) {
          if (y === yj) {
            if (y < vy) {
              inside = !inside;
            }
          } else {
            inside = !inside;
          }
        }
      }
    } else if (y === yi && (x >= xj && x <= xi || x >= xi && x <= xj)) {
      return true;
    }
  }
  return inside;
}
function getEnterPointPlacement(enterPoint, rect) {
  const { top, right, bottom, left } = rect;
  const [x, y] = enterPoint;
  const placementX = x < left ? "left" : x > right ? "right" : null;
  const placementY = y < top ? "top" : y > bottom ? "bottom" : null;
  return [placementX, placementY];
}
function getElementPolygon(element, enterPoint) {
  const rect = element.getBoundingClientRect();
  const { top, right, bottom, left } = rect;
  const [x, y] = getEnterPointPlacement(enterPoint, rect);
  const polygon = [enterPoint];
  if (x) {
    if (y !== "top") {
      polygon.push([x === "left" ? left : right, top]);
    }
    polygon.push([x === "left" ? right : left, top]);
    polygon.push([x === "left" ? right : left, bottom]);
    if (y !== "bottom") {
      polygon.push([x === "left" ? left : right, bottom]);
    }
  } else if (y === "top") {
    polygon.push([left, top]);
    polygon.push([left, bottom]);
    polygon.push([right, bottom]);
    polygon.push([right, top]);
  } else {
    polygon.push([left, bottom]);
    polygon.push([left, top]);
    polygon.push([right, top]);
    polygon.push([right, bottom]);
  }
  return polygon;
}



;// ../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/AOQQTIBO.js
"use client";

// src/portal/portal-context.tsx

var PortalContext = (0,react.createContext)(null);



;// ../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/A2TITI2Y.js
"use client";


// src/visually-hidden/visually-hidden.tsx
var A2TITI2Y_TagName = "span";
var useVisuallyHidden = (0,TVXRYIJB/* createHook */.ab)(
  function useVisuallyHidden2(props) {
    props = {
      ...props,
      style: {
        border: 0,
        clip: "rect(0 0 0 0)",
        height: "1px",
        margin: "-1px",
        overflow: "hidden",
        padding: 0,
        position: "absolute",
        whiteSpace: "nowrap",
        width: "1px",
        ...props.style
      }
    };
    return props;
  }
);
var VisuallyHidden = (0,TVXRYIJB/* forwardRef */.Rf)(function VisuallyHidden2(props) {
  const htmlProps = useVisuallyHidden(props);
  return (0,TVXRYIJB/* createElement */.n)(A2TITI2Y_TagName, htmlProps);
});



;// ../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/EIGWIYE4.js
"use client";



// src/focus-trap/focus-trap.tsx
var EIGWIYE4_TagName = "span";
var useFocusTrap = (0,TVXRYIJB/* createHook */.ab)(
  function useFocusTrap2(props) {
    props = {
      "data-focus-trap": "",
      tabIndex: 0,
      "aria-hidden": true,
      ...props,
      style: {
        // Prevents unintended scroll jumps.
        position: "fixed",
        top: 0,
        left: 0,
        ...props.style
      }
    };
    props = useVisuallyHidden(props);
    return props;
  }
);
var FocusTrap = (0,TVXRYIJB/* forwardRef */.Rf)(function FocusTrap2(props) {
  const htmlProps = useFocusTrap(props);
  return (0,TVXRYIJB/* createElement */.n)(EIGWIYE4_TagName, htmlProps);
});



// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/YXGXYGQX.js
var YXGXYGQX = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/YXGXYGQX.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+core@0.4.19/node_modules/@ariakit/core/esm/__chunks/G7XPWBXK.js
var G7XPWBXK = __webpack_require__("../../node_modules/.pnpm/@ariakit+core@0.4.19/node_modules/@ariakit/core/esm/__chunks/G7XPWBXK.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+core@0.4.19/node_modules/@ariakit/core/esm/utils/focus.js
var utils_focus = __webpack_require__("../../node_modules/.pnpm/@ariakit+core@0.4.19/node_modules/@ariakit/core/esm/utils/focus.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react-dom@18.3.1_react@18.3.1/node_modules/react-dom/index.js
var react_dom = __webpack_require__("../../node_modules/.pnpm/react-dom@18.3.1_react@18.3.1/node_modules/react-dom/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js
var jsx_runtime = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
;// ../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/NOHE2KUO.js
"use client";






// src/portal/portal.tsx






var NOHE2KUO_TagName = "div";
function getRootElement(element) {
  return (0,G7XPWBXK/* getDocument */.YE)(element).body;
}
function getPortalElement(element, portalElement) {
  if (!portalElement) {
    return (0,G7XPWBXK/* getDocument */.YE)(element).createElement("div");
  }
  if (typeof portalElement === "function") {
    return portalElement(element);
  }
  return portalElement;
}
function getRandomId(prefix = "id") {
  return `${prefix ? `${prefix}-` : ""}${Math.random().toString(36).slice(2, 8)}`;
}
function queueFocus(element) {
  queueMicrotask(() => {
    element == null ? void 0 : element.focus();
  });
}
var usePortal = (0,TVXRYIJB/* createHook */.ab)(function usePortal2({
  preserveTabOrder,
  preserveTabOrderAnchor,
  portalElement,
  portalRef,
  portal = true,
  ...props
}) {
  const ref = (0,react.useRef)(null);
  const refProp = (0,CEM7J6TT/* useMergeRefs */.SV)(ref, props.ref);
  const context = (0,react.useContext)(PortalContext);
  const [portalNode, setPortalNode] = (0,react.useState)(null);
  const [anchorPortalNode, setAnchorPortalNode] = (0,react.useState)(
    null
  );
  const outerBeforeRef = (0,react.useRef)(null);
  const innerBeforeRef = (0,react.useRef)(null);
  const innerAfterRef = (0,react.useRef)(null);
  const outerAfterRef = (0,react.useRef)(null);
  (0,CEM7J6TT/* useSafeLayoutEffect */.UQ)(() => {
    const element = ref.current;
    if (!element || !portal) {
      setPortalNode(null);
      return;
    }
    const portalEl = getPortalElement(element, portalElement);
    if (!portalEl) {
      setPortalNode(null);
      return;
    }
    const isPortalInDocument = portalEl.isConnected;
    if (!isPortalInDocument) {
      const rootElement = context || getRootElement(element);
      rootElement.appendChild(portalEl);
    }
    if (!portalEl.id) {
      portalEl.id = element.id ? `portal/${element.id}` : getRandomId();
    }
    setPortalNode(portalEl);
    (0,YXGXYGQX/* setRef */.cZ)(portalRef, portalEl);
    if (isPortalInDocument) return;
    return () => {
      portalEl.remove();
      (0,YXGXYGQX/* setRef */.cZ)(portalRef, null);
    };
  }, [portal, portalElement, context, portalRef]);
  (0,CEM7J6TT/* useSafeLayoutEffect */.UQ)(() => {
    if (!portal) return;
    if (!preserveTabOrder) return;
    if (!preserveTabOrderAnchor) return;
    const doc = (0,G7XPWBXK/* getDocument */.YE)(preserveTabOrderAnchor);
    const element = doc.createElement("span");
    element.style.position = "fixed";
    preserveTabOrderAnchor.insertAdjacentElement("afterend", element);
    setAnchorPortalNode(element);
    return () => {
      element.remove();
      setAnchorPortalNode(null);
    };
  }, [portal, preserveTabOrder, preserveTabOrderAnchor]);
  (0,react.useEffect)(() => {
    if (!portalNode) return;
    if (!preserveTabOrder) return;
    let raf = 0;
    const onFocus = (event) => {
      if (!(0,events/* isFocusEventOutside */.aG)(event)) return;
      const focusing = event.type === "focusin";
      cancelAnimationFrame(raf);
      if (focusing) {
        return (0,utils_focus/* restoreFocusIn */.QZ)(portalNode);
      }
      raf = requestAnimationFrame(() => {
        (0,utils_focus/* disableFocusIn */.gw)(portalNode, true);
      });
    };
    portalNode.addEventListener("focusin", onFocus, true);
    portalNode.addEventListener("focusout", onFocus, true);
    return () => {
      cancelAnimationFrame(raf);
      portalNode.removeEventListener("focusin", onFocus, true);
      portalNode.removeEventListener("focusout", onFocus, true);
    };
  }, [portalNode, preserveTabOrder]);
  props = (0,CEM7J6TT/* useWrapElement */.w7)(
    props,
    (element) => {
      element = // While the portal node is not in the DOM, we need to pass the
      // current context to the portal context, otherwise it's going to
      // reset to the body element on nested portals.
      /* @__PURE__ */ (0,jsx_runtime.jsx)(PortalContext.Provider, { value: portalNode || context, children: element });
      if (!portal) return element;
      if (!portalNode) {
        return /* @__PURE__ */ (0,jsx_runtime.jsx)(
          "span",
          {
            ref: refProp,
            id: props.id,
            style: { position: "fixed" },
            hidden: true
          }
        );
      }
      element = /* @__PURE__ */ (0,jsx_runtime.jsxs)(jsx_runtime.Fragment, { children: [
        preserveTabOrder && portalNode && /* @__PURE__ */ (0,jsx_runtime.jsx)(
          FocusTrap,
          {
            ref: innerBeforeRef,
            "data-focus-trap": props.id,
            className: "__focus-trap-inner-before",
            onFocus: (event) => {
              if ((0,events/* isFocusEventOutside */.aG)(event, portalNode)) {
                queueFocus((0,utils_focus/* getNextTabbable */.vF)());
              } else {
                queueFocus(outerBeforeRef.current);
              }
            }
          }
        ),
        element,
        preserveTabOrder && portalNode && /* @__PURE__ */ (0,jsx_runtime.jsx)(
          FocusTrap,
          {
            ref: innerAfterRef,
            "data-focus-trap": props.id,
            className: "__focus-trap-inner-after",
            onFocus: (event) => {
              if ((0,events/* isFocusEventOutside */.aG)(event, portalNode)) {
                queueFocus((0,utils_focus/* getPreviousTabbable */.XJ)());
              } else {
                queueFocus(outerAfterRef.current);
              }
            }
          }
        )
      ] });
      if (portalNode) {
        element = (0,react_dom.createPortal)(element, portalNode);
      }
      let preserveTabOrderElement = /* @__PURE__ */ (0,jsx_runtime.jsxs)(jsx_runtime.Fragment, { children: [
        preserveTabOrder && portalNode && /* @__PURE__ */ (0,jsx_runtime.jsx)(
          FocusTrap,
          {
            ref: outerBeforeRef,
            "data-focus-trap": props.id,
            className: "__focus-trap-outer-before",
            onFocus: (event) => {
              const fromOuter = event.relatedTarget === outerAfterRef.current;
              if (!fromOuter && (0,events/* isFocusEventOutside */.aG)(event, portalNode)) {
                queueFocus(innerBeforeRef.current);
              } else {
                queueFocus((0,utils_focus/* getPreviousTabbable */.XJ)());
              }
            }
          }
        ),
        preserveTabOrder && // We're using position: fixed here so that the browser doesn't
        // add margin to the element when setting gap on a parent element.
        /* @__PURE__ */ (0,jsx_runtime.jsx)("span", { "aria-owns": portalNode == null ? void 0 : portalNode.id, style: { position: "fixed" } }),
        preserveTabOrder && portalNode && /* @__PURE__ */ (0,jsx_runtime.jsx)(
          FocusTrap,
          {
            ref: outerAfterRef,
            "data-focus-trap": props.id,
            className: "__focus-trap-outer-after",
            onFocus: (event) => {
              if ((0,events/* isFocusEventOutside */.aG)(event, portalNode)) {
                queueFocus(innerAfterRef.current);
              } else {
                const nextTabbable = (0,utils_focus/* getNextTabbable */.vF)();
                if (nextTabbable === innerBeforeRef.current) {
                  requestAnimationFrame(() => {
                    var _a;
                    return (_a = (0,utils_focus/* getNextTabbable */.vF)()) == null ? void 0 : _a.focus();
                  });
                  return;
                }
                queueFocus(nextTabbable);
              }
            }
          }
        )
      ] });
      if (anchorPortalNode && preserveTabOrder) {
        preserveTabOrderElement = (0,react_dom.createPortal)(
          preserveTabOrderElement,
          anchorPortalNode
        );
      }
      return /* @__PURE__ */ (0,jsx_runtime.jsxs)(jsx_runtime.Fragment, { children: [
        preserveTabOrderElement,
        element
      ] });
    },
    [portalNode, context, portal, props.id, preserveTabOrder, anchorPortalNode]
  );
  props = {
    ...props,
    ref: refProp
  };
  return props;
});
var Portal = (0,TVXRYIJB/* forwardRef */.Rf)(function Portal2(props) {
  const htmlProps = usePortal(props);
  return (0,TVXRYIJB/* createElement */.n)(NOHE2KUO_TagName, htmlProps);
});



;// ../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/CZ4GFWYL.js
"use client";

// src/heading/heading-context.tsx

var HeadingContext = (0,react.createContext)(0);



;// ../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/5M6RIVE2.js
"use client";


// src/heading/heading-level.tsx


function HeadingLevel({ level, children }) {
  const contextLevel = (0,react.useContext)(HeadingContext);
  const nextLevel = Math.max(
    Math.min(level || contextLevel + 1, 6),
    1
  );
  return /* @__PURE__ */ (0,jsx_runtime.jsx)(HeadingContext.Provider, { value: nextLevel, children });
}



// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/SWN3JYXT.js
var SWN3JYXT = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/SWN3JYXT.js");
;// ../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/QJNORFLZ.js
"use client";




// src/focusable/focusable-container.tsx

var QJNORFLZ_TagName = "div";
var useFocusableContainer = (0,TVXRYIJB/* createHook */.ab)(function useFocusableContainer2({ autoFocusOnShow = true, ...props }) {
  props = (0,CEM7J6TT/* useWrapElement */.w7)(
    props,
    (element) => /* @__PURE__ */ (0,jsx_runtime.jsx)(SWN3JYXT/* FocusableContext */.g.Provider, { value: autoFocusOnShow, children: element }),
    [autoFocusOnShow]
  );
  return props;
});
var FocusableContainer = (0,TVXRYIJB/* forwardRef */.Rf)(function FocusableContainer2(props) {
  const htmlProps = useFocusableContainer(props);
  return (0,TVXRYIJB/* createElement */.n)(QJNORFLZ_TagName, htmlProps);
});



;// ../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/6GXEOXGT.js
"use client";

// src/dialog/utils/prepend-hidden-dismiss.ts

function prependHiddenDismiss(container, onClick) {
  const document = (0,G7XPWBXK/* getDocument */.YE)(container);
  const button = document.createElement("button");
  button.type = "button";
  button.tabIndex = -1;
  button.textContent = "Dismiss popup";
  Object.assign(button.style, {
    border: "0px",
    clip: "rect(0 0 0 0)",
    height: "1px",
    margin: "-1px",
    overflow: "hidden",
    padding: "0px",
    position: "absolute",
    whiteSpace: "nowrap",
    width: "1px"
  });
  button.addEventListener("click", onClick);
  container.prepend(button);
  const removeHiddenDismiss = () => {
    button.removeEventListener("click", onClick);
    button.remove();
  };
  return removeHiddenDismiss;
}



;// ../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/U3R3WHDK.js
"use client";

// src/dialog/utils/use-previous-mouse-down-ref.ts


function usePreviousMouseDownRef(enabled) {
  const previousMouseDownRef = (0,react.useRef)(null);
  (0,react.useEffect)(() => {
    if (!enabled) {
      previousMouseDownRef.current = null;
      return;
    }
    const onMouseDown = (event) => {
      previousMouseDownRef.current = event.target;
    };
    return (0,events/* addGlobalEventListener */.dg)("mousedown", onMouseDown, true);
  }, [enabled]);
  return previousMouseDownRef;
}



;// ../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/K2ZF5NU7.js
"use client";

// src/dialog/utils/orchestrate.ts
var cleanups = /* @__PURE__ */ new WeakMap();
function orchestrate(element, key, setup) {
  if (!cleanups.has(element)) {
    cleanups.set(element, /* @__PURE__ */ new Map());
  }
  const elementCleanups = cleanups.get(element);
  const prevCleanup = elementCleanups.get(key);
  if (!prevCleanup) {
    elementCleanups.set(key, setup());
    return () => {
      var _a;
      (_a = elementCleanups.get(key)) == null ? void 0 : _a();
      elementCleanups.delete(key);
    };
  }
  const cleanup = setup();
  const nextCleanup = () => {
    cleanup();
    prevCleanup();
    elementCleanups.delete(key);
  };
  elementCleanups.set(key, nextCleanup);
  return () => {
    const isCurrent = elementCleanups.get(key) === nextCleanup;
    if (!isCurrent) return;
    cleanup();
    elementCleanups.set(key, prevCleanup);
  };
}
function setAttribute(element, attr, value) {
  const setup = () => {
    const previousValue = element.getAttribute(attr);
    element.setAttribute(attr, value);
    return () => {
      if (previousValue == null) {
        element.removeAttribute(attr);
      } else {
        element.setAttribute(attr, previousValue);
      }
    };
  };
  return orchestrate(element, attr, setup);
}
function setProperty(element, property, value) {
  const setup = () => {
    const exists = property in element;
    const previousValue = element[property];
    element[property] = value;
    return () => {
      if (!exists) {
        delete element[property];
      } else {
        element[property] = previousValue;
      }
    };
  };
  return orchestrate(element, property, setup);
}
function assignStyle(element, style) {
  if (!element) return () => {
  };
  const setup = () => {
    const prevStyle = element.style.cssText;
    Object.assign(element.style, style);
    return () => {
      element.style.cssText = prevStyle;
    };
  };
  return orchestrate(element, "style", setup);
}
function setCSSProperty(element, property, value) {
  if (!element) return () => {
  };
  const setup = () => {
    const previousValue = element.style.getPropertyValue(property);
    element.style.setProperty(property, value);
    return () => {
      if (previousValue) {
        element.style.setProperty(property, previousValue);
      } else {
        element.style.removeProperty(property);
      }
    };
  };
  return orchestrate(element, property, setup);
}



;// ../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/AOUGVQZ3.js
"use client";


// src/dialog/utils/walk-tree-outside.ts


var ignoreTags = ["SCRIPT", "STYLE"];
function getSnapshotPropertyName(id) {
  return `__ariakit-dialog-snapshot-${id}`;
}
function inSnapshot(id, element) {
  const doc = (0,G7XPWBXK/* getDocument */.YE)(element);
  const propertyName = getSnapshotPropertyName(id);
  if (!doc.body[propertyName]) return true;
  do {
    if (element === doc.body) return false;
    if (element[propertyName]) return true;
    if (!element.parentElement) return false;
    element = element.parentElement;
  } while (true);
}
function isValidElement(id, element, ignoredElements) {
  if (ignoreTags.includes(element.tagName)) return false;
  if (!inSnapshot(id, element)) return false;
  return !ignoredElements.some(
    (enabledElement) => enabledElement && (0,G7XPWBXK/* contains */.gR)(element, enabledElement)
  );
}
function AOUGVQZ3_walkTreeOutside(id, elements, callback, ancestorCallback) {
  for (let element of elements) {
    if (!(element == null ? void 0 : element.isConnected)) continue;
    const hasAncestorAlready = elements.some((maybeAncestor) => {
      if (!maybeAncestor) return false;
      if (maybeAncestor === element) return false;
      return maybeAncestor.contains(element);
    });
    const doc = (0,G7XPWBXK/* getDocument */.YE)(element);
    const originalElement = element;
    while (element.parentElement && element !== doc.body) {
      ancestorCallback == null ? void 0 : ancestorCallback(element.parentElement, originalElement);
      if (!hasAncestorAlready) {
        for (const child of element.parentElement.children) {
          if (isValidElement(id, child, elements)) {
            callback(child, originalElement);
          }
        }
      }
      element = element.parentElement;
    }
  }
}
function createWalkTreeSnapshot(id, elements) {
  const { body } = (0,G7XPWBXK/* getDocument */.YE)(elements[0]);
  const cleanups = [];
  const markElement = (element) => {
    cleanups.push(setProperty(element, getSnapshotPropertyName(id), true));
  };
  AOUGVQZ3_walkTreeOutside(id, elements, markElement);
  return (0,UWJK2WK2/* chain */.cy)(setProperty(body, getSnapshotPropertyName(id), true), () => {
    for (const cleanup of cleanups) {
      cleanup();
    }
  });
}



;// ../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/63XF7ACK.js
"use client";

// src/dialog/utils/is-backdrop.ts
function _63XF7ACK_isBackdrop(element, ...ids) {
  if (!element) return false;
  const backdrop = element.getAttribute("data-backdrop");
  if (backdrop == null) return false;
  if (backdrop === "") return true;
  if (backdrop === "true") return true;
  if (!ids.length) return true;
  return ids.some((id) => backdrop === id);
}



;// ../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/3NDVDEB4.js
"use client";




// src/dialog/utils/mark-tree-outside.ts

function getPropertyName(id = "", ancestor = false) {
  return `__ariakit-dialog-${ancestor ? "ancestor" : "outside"}${id ? `-${id}` : ""}`;
}
function markElement(element, id = "") {
  return (0,UWJK2WK2/* chain */.cy)(
    setProperty(element, getPropertyName(), true),
    setProperty(element, getPropertyName(id), true)
  );
}
function markAncestor(element, id = "") {
  return (0,UWJK2WK2/* chain */.cy)(
    setProperty(element, getPropertyName("", true), true),
    setProperty(element, getPropertyName(id, true), true)
  );
}
function isElementMarked(element, id) {
  const ancestorProperty = getPropertyName(id, true);
  if (element[ancestorProperty]) return true;
  const elementProperty = getPropertyName(id);
  do {
    if (element[elementProperty]) return true;
    if (!element.parentElement) return false;
    element = element.parentElement;
  } while (true);
}
function markTreeOutside(id, elements) {
  const cleanups = [];
  const ids = elements.map((el) => el == null ? void 0 : el.id);
  AOUGVQZ3_walkTreeOutside(
    id,
    elements,
    (element) => {
      if (_63XF7ACK_isBackdrop(element, ...ids)) return;
      cleanups.unshift(markElement(element, id));
    },
    (ancestor, element) => {
      const isAnotherDialogAncestor = element.hasAttribute("data-dialog") && element.id !== id;
      if (isAnotherDialogAncestor) return;
      cleanups.unshift(markAncestor(ancestor, id));
    }
  );
  const restoreAccessibilityTree = () => {
    for (const cleanup of cleanups) {
      cleanup();
    }
  };
  return restoreAccessibilityTree;
}



;// ../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/UWTV2ZWL.js
"use client";





// src/dialog/utils/use-hide-on-interact-outside.ts



function isInDocument(target) {
  if (target.tagName === "HTML") return true;
  return (0,G7XPWBXK/* contains */.gR)((0,G7XPWBXK/* getDocument */.YE)(target).body, target);
}
function isDisclosure(disclosure, target) {
  if (!disclosure) return false;
  if ((0,G7XPWBXK/* contains */.gR)(disclosure, target)) return true;
  const activeId = target.getAttribute("aria-activedescendant");
  if (activeId) {
    const activeElement = (0,G7XPWBXK/* getDocument */.YE)(disclosure).getElementById(activeId);
    if (activeElement) {
      return (0,G7XPWBXK/* contains */.gR)(disclosure, activeElement);
    }
  }
  return false;
}
function isMouseEventOnDialog(event, dialog) {
  if (!("clientY" in event)) return false;
  const rect = dialog.getBoundingClientRect();
  if (rect.width === 0 || rect.height === 0) return false;
  return rect.top <= event.clientY && event.clientY <= rect.top + rect.height && rect.left <= event.clientX && event.clientX <= rect.left + rect.width;
}
function useEventOutside({
  store,
  type,
  listener,
  capture,
  domReady
}) {
  const callListener = (0,CEM7J6TT/* useEvent */._q)(listener);
  const open = (0,YAS7X7HB/* useStoreState */.O$)(store, "open");
  const focusedRef = (0,react.useRef)(false);
  (0,CEM7J6TT/* useSafeLayoutEffect */.UQ)(() => {
    if (!open) return;
    if (!domReady) return;
    const { contentElement } = store.getState();
    if (!contentElement) return;
    const onFocus = () => {
      focusedRef.current = true;
    };
    contentElement.addEventListener("focusin", onFocus, true);
    return () => contentElement.removeEventListener("focusin", onFocus, true);
  }, [store, open, domReady]);
  (0,react.useEffect)(() => {
    if (!open) return;
    const onEvent = (event) => {
      const { contentElement, disclosureElement } = store.getState();
      const target = event.target;
      if (!contentElement) return;
      if (!target) return;
      if (!isInDocument(target)) return;
      if ((0,G7XPWBXK/* contains */.gR)(contentElement, target)) return;
      if (isDisclosure(disclosureElement, target)) return;
      if (target.hasAttribute("data-focus-trap")) return;
      if (isMouseEventOnDialog(event, contentElement)) return;
      const focused = focusedRef.current;
      if (focused && !isElementMarked(target, contentElement.id)) return;
      callListener(event);
    };
    return (0,events/* addGlobalEventListener */.dg)(type, onEvent, capture);
  }, [open, capture, store, type, callListener]);
}
function shouldHideOnInteractOutside(hideOnInteractOutside, event) {
  if (typeof hideOnInteractOutside === "function") {
    return hideOnInteractOutside(event);
  }
  return !!hideOnInteractOutside;
}
function useHideOnInteractOutside(store, hideOnInteractOutside, domReady, interactedOutsideRef) {
  const open = (0,YAS7X7HB/* useStoreState */.O$)(store, "open");
  const previousMouseDownRef = usePreviousMouseDownRef(open);
  const props = { store, domReady, capture: true };
  useEventOutside({
    ...props,
    type: "click",
    listener: (event) => {
      const { contentElement } = store.getState();
      const previousMouseDown = previousMouseDownRef.current;
      if (!previousMouseDown) return;
      if (!isElementMarked(previousMouseDown, contentElement == null ? void 0 : contentElement.id)) return;
      if (!shouldHideOnInteractOutside(hideOnInteractOutside, event)) return;
      if (interactedOutsideRef) {
        interactedOutsideRef.current = true;
      }
      store.hide();
    }
  });
  useEventOutside({
    ...props,
    type: "focusin",
    listener: (event) => {
      const { contentElement } = store.getState();
      if (!contentElement) return;
      if (event.target === (0,G7XPWBXK/* getDocument */.YE)(contentElement)) return;
      if (!shouldHideOnInteractOutside(hideOnInteractOutside, event)) return;
      store.hide();
    }
  });
  useEventOutside({
    ...props,
    type: "contextmenu",
    listener: (event) => {
      if (!shouldHideOnInteractOutside(hideOnInteractOutside, event)) return;
      if (interactedOutsideRef) {
        interactedOutsideRef.current = true;
      }
      store.hide();
    }
  });
}



;// ../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/TKBSBKEC.js
"use client";


// src/dialog/utils/use-nested-dialogs.tsx




var NestedDialogsContext = (0,react.createContext)({});
function useNestedDialogs(store) {
  const context = (0,react.useContext)(NestedDialogsContext);
  const [dialogs, setDialogs] = (0,react.useState)([]);
  const add = (0,react.useCallback)(
    (dialog) => {
      var _a;
      setDialogs((dialogs2) => [...dialogs2, dialog]);
      return (0,UWJK2WK2/* chain */.cy)((_a = context.add) == null ? void 0 : _a.call(context, dialog), () => {
        setDialogs((dialogs2) => dialogs2.filter((d) => d !== dialog));
      });
    },
    [context]
  );
  (0,CEM7J6TT/* useSafeLayoutEffect */.UQ)(() => {
    return (0,XTZ53NXG/* sync */.OH)(store, ["open", "contentElement"], (state) => {
      var _a;
      if (!state.open) return;
      if (!state.contentElement) return;
      return (_a = context.add) == null ? void 0 : _a.call(context, store);
    });
  }, [store, context]);
  const providerValue = (0,react.useMemo)(() => ({ store, add }), [store, add]);
  const wrapElement = (0,react.useCallback)(
    (element) => /* @__PURE__ */ (0,jsx_runtime.jsx)(NestedDialogsContext.Provider, { value: providerValue, children: element }),
    [providerValue]
  );
  return { wrapElement, nestedDialogs: dialogs };
}



;// ../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/BUY5M5QX.js
"use client";


// src/dialog/utils/use-root-dialog.ts



function useRootDialog({
  attribute,
  contentId,
  contentElement,
  enabled
}) {
  const [updated, retry] = (0,CEM7J6TT/* useForceUpdate */.CH)();
  const isRootDialog = (0,react.useCallback)(() => {
    if (!enabled) return false;
    if (!contentElement) return false;
    const { body } = (0,G7XPWBXK/* getDocument */.YE)(contentElement);
    const id = body.getAttribute(attribute);
    return !id || id === contentId;
  }, [updated, enabled, contentElement, attribute, contentId]);
  (0,react.useEffect)(() => {
    if (!enabled) return;
    if (!contentId) return;
    if (!contentElement) return;
    const { body } = (0,G7XPWBXK/* getDocument */.YE)(contentElement);
    if (isRootDialog()) {
      body.setAttribute(attribute, contentId);
      return () => body.removeAttribute(attribute);
    }
    const observer = new MutationObserver(() => (0,react_dom.flushSync)(retry));
    observer.observe(body, { attributeFilter: [attribute] });
    return () => observer.disconnect();
  }, [updated, enabled, contentId, contentElement, isRootDialog, attribute]);
  return isRootDialog;
}



// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+core@0.4.19/node_modules/@ariakit/core/esm/__chunks/GMGLSF2B.js
var GMGLSF2B = __webpack_require__("../../node_modules/.pnpm/@ariakit+core@0.4.19/node_modules/@ariakit/core/esm/__chunks/GMGLSF2B.js");
;// ../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/YJTK2J3H.js
"use client";



// src/dialog/utils/use-prevent-body-scroll.ts




function getPaddingProperty(documentElement) {
  const documentLeft = documentElement.getBoundingClientRect().left;
  const scrollbarX = Math.round(documentLeft) + documentElement.scrollLeft;
  return scrollbarX ? "paddingLeft" : "paddingRight";
}
function usePreventBodyScroll(contentElement, contentId, enabled) {
  const isRootDialog = useRootDialog({
    attribute: "data-dialog-prevent-body-scroll",
    contentElement,
    contentId,
    enabled
  });
  (0,react.useEffect)(() => {
    if (!isRootDialog()) return;
    if (!contentElement) return;
    const doc = (0,G7XPWBXK/* getDocument */.YE)(contentElement);
    const win = (0,G7XPWBXK/* getWindow */.zk)(contentElement);
    const { documentElement, body } = doc;
    const cssScrollbarWidth = documentElement.style.getPropertyValue("--scrollbar-width");
    const scrollbarWidth = cssScrollbarWidth ? Number.parseInt(cssScrollbarWidth, 10) : win.innerWidth - documentElement.clientWidth;
    const setScrollbarWidthProperty = () => setCSSProperty(
      documentElement,
      "--scrollbar-width",
      `${scrollbarWidth}px`
    );
    const paddingProperty = getPaddingProperty(documentElement);
    const setStyle = () => assignStyle(body, {
      overflow: "hidden",
      [paddingProperty]: `${scrollbarWidth}px`
    });
    const setIOSStyle = () => {
      var _a, _b;
      const { scrollX, scrollY, visualViewport } = win;
      const offsetLeft = (_a = visualViewport == null ? void 0 : visualViewport.offsetLeft) != null ? _a : 0;
      const offsetTop = (_b = visualViewport == null ? void 0 : visualViewport.offsetTop) != null ? _b : 0;
      const restoreStyle = assignStyle(body, {
        position: "fixed",
        overflow: "hidden",
        top: `${-(scrollY - Math.floor(offsetTop))}px`,
        left: `${-(scrollX - Math.floor(offsetLeft))}px`,
        right: "0",
        [paddingProperty]: `${scrollbarWidth}px`
      });
      return () => {
        restoreStyle();
        if (true) {
          win.scrollTo({ left: scrollX, top: scrollY, behavior: "instant" });
        }
      };
    };
    const isIOS = (0,GMGLSF2B/* isApple */.f8)() && !(0,GMGLSF2B/* isMac */.cX)();
    return (0,UWJK2WK2/* chain */.cy)(
      setScrollbarWidthProperty(),
      isIOS ? setIOSStyle() : setStyle()
    );
  }, [isRootDialog, contentElement]);
}



;// ../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/IGR4SXG2.js
"use client";

// src/dialog/utils/is-focus-trap.ts
function isFocusTrap(element, ...ids) {
  if (!element) return false;
  const attr = element.getAttribute("data-focus-trap");
  if (attr == null) return false;
  if (!ids.length) return true;
  if (attr === "") return false;
  return ids.some((id) => attr === id);
}



;// ../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/677M2CI3.js
"use client";

// src/dialog/utils/supports-inert.ts
function supportsInert() {
  return "inert" in HTMLElement.prototype;
}



;// ../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/S7U6BLGA.js
"use client";




// src/dialog/utils/disable-accessibility-tree-outside.ts
function hideElementFromAccessibilityTree(element) {
  return setAttribute(element, "aria-hidden", "true");
}
function disableAccessibilityTreeOutside(id, elements) {
  const cleanups = [];
  const ids = elements.map((el) => el == null ? void 0 : el.id);
  walkTreeOutside(id, elements, (element) => {
    if (isBackdrop(element, ...ids)) return;
    cleanups.unshift(hideElementFromAccessibilityTree(element));
  });
  const restoreAccessibilityTree = () => {
    for (const cleanup of cleanups) {
      cleanup();
    }
  };
  return restoreAccessibilityTree;
}



;// ../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/Z5GCVBAY.js
"use client";







// src/dialog/utils/disable-tree.ts



function disableTree(element, ignoredElements) {
  if (!("style" in element)) return UWJK2WK2/* noop */.lQ;
  if (supportsInert()) {
    return setProperty(element, "inert", true);
  }
  const tabbableElements = (0,utils_focus/* getAllTabbableIn */.a9)(element, true);
  const enableElements = tabbableElements.map((element2) => {
    if (ignoredElements == null ? void 0 : ignoredElements.some((el) => el && (0,G7XPWBXK/* contains */.gR)(el, element2))) return UWJK2WK2/* noop */.lQ;
    const restoreFocusMethod = orchestrate(element2, "focus", () => {
      element2.focus = UWJK2WK2/* noop */.lQ;
      return () => {
        delete element2.focus;
      };
    });
    return (0,UWJK2WK2/* chain */.cy)(setAttribute(element2, "tabindex", "-1"), restoreFocusMethod);
  });
  return (0,UWJK2WK2/* chain */.cy)(
    ...enableElements,
    hideElementFromAccessibilityTree(element),
    assignStyle(element, {
      pointerEvents: "none",
      userSelect: "none",
      cursor: "default"
    })
  );
}
function disableTreeOutside(id, elements) {
  const cleanups = [];
  const ids = elements.map((el) => el == null ? void 0 : el.id);
  AOUGVQZ3_walkTreeOutside(
    id,
    elements,
    (element) => {
      if (_63XF7ACK_isBackdrop(element, ...ids)) return;
      if (isFocusTrap(element, ...ids)) return;
      cleanups.unshift(disableTree(element, elements));
    },
    (element) => {
      if (!element.hasAttribute("role")) return;
      if (elements.some((el) => el && (0,G7XPWBXK/* contains */.gR)(el, element))) return;
      cleanups.unshift(setAttribute(element, "role", "none"));
    }
  );
  const restoreTreeOutside = () => {
    for (const cleanup of cleanups) {
      cleanup();
    }
  };
  return restoreTreeOutside;
}



// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/WLLNEL2X.js
var WLLNEL2X = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/WLLNEL2X.js");
;// ../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/CZY7XL6S.js
"use client";







// src/dialog/dialog-backdrop.tsx


function DialogBackdrop({
  store,
  backdrop,
  alwaysVisible,
  hidden
}) {
  const ref = (0,react.useRef)(null);
  const disclosure = (0,FL7NPBCY/* useDisclosureStore */.E)({ disclosure: store });
  const contentElement = (0,YAS7X7HB/* useStoreState */.O$)(store, "contentElement");
  (0,react.useEffect)(() => {
    const backdrop2 = ref.current;
    const dialog = contentElement;
    if (!backdrop2) return;
    if (!dialog) return;
    backdrop2.style.zIndex = getComputedStyle(dialog).zIndex;
  }, [contentElement]);
  (0,CEM7J6TT/* useSafeLayoutEffect */.UQ)(() => {
    const id = contentElement == null ? void 0 : contentElement.id;
    if (!id) return;
    const backdrop2 = ref.current;
    if (!backdrop2) return;
    return markAncestor(backdrop2, id);
  }, [contentElement]);
  const props = (0,WLLNEL2X/* useDisclosureContent */.aT)({
    ref,
    store: disclosure,
    role: "presentation",
    "data-backdrop": (contentElement == null ? void 0 : contentElement.id) || "",
    alwaysVisible,
    hidden: hidden != null ? hidden : void 0,
    style: {
      position: "fixed",
      top: 0,
      right: 0,
      bottom: 0,
      left: 0
    }
  });
  if (!backdrop) return null;
  if ((0,react.isValidElement)(backdrop)) {
    return /* @__PURE__ */ (0,jsx_runtime.jsx)(Role, { ...props, render: backdrop });
  }
  const Component = typeof backdrop !== "boolean" ? backdrop : "div";
  return /* @__PURE__ */ (0,jsx_runtime.jsx)(Role, { ...props, render: /* @__PURE__ */ (0,jsx_runtime.jsx)(Component, {}) });
}



// EXTERNAL MODULE: ../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/W43ISTP6.js
var W43ISTP6 = __webpack_require__("../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/W43ISTP6.js");
;// ../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/7WPPMYFR.js
"use client";




















// src/dialog/dialog.tsx








var _7WPPMYFR_TagName = "div";
var isSafariBrowser = (0,GMGLSF2B/* isSafari */.nr)();
function isAlreadyFocusingAnotherElement(dialog) {
  const activeElement = (0,G7XPWBXK/* getActiveElement */.bq)();
  if (!activeElement) return false;
  if (dialog && (0,G7XPWBXK/* contains */.gR)(dialog, activeElement)) return false;
  if ((0,utils_focus/* isFocusable */.tp)(activeElement)) return true;
  return false;
}
function getElementFromProp(prop, focusable = false) {
  if (!prop) return null;
  const element = "current" in prop ? prop.current : prop;
  if (!element) return null;
  if (focusable) return (0,utils_focus/* isFocusable */.tp)(element) ? element : null;
  return element;
}
var useDialog = (0,TVXRYIJB/* createHook */.ab)(function useDialog2({
  store: storeProp,
  open: openProp,
  onClose,
  focusable = true,
  modal = true,
  portal = !!modal,
  backdrop = !!modal,
  hideOnEscape = true,
  hideOnInteractOutside = true,
  getPersistentElements,
  preventBodyScroll = !!modal,
  autoFocusOnShow = true,
  autoFocusOnHide = true,
  initialFocus,
  finalFocus,
  unmountOnHide,
  unstable_treeSnapshotKey,
  ...props
}) {
  const context = (0,W43ISTP6/* useDialogProviderContext */.cH)();
  const ref = (0,react.useRef)(null);
  const store = useDialogStore({
    store: storeProp || context,
    open: openProp,
    setOpen(open2) {
      if (open2) return;
      const dialog = ref.current;
      if (!dialog) return;
      const event = new Event("close", { bubbles: false, cancelable: true });
      if (onClose) {
        dialog.addEventListener("close", onClose, { once: true });
      }
      dialog.dispatchEvent(event);
      if (!event.defaultPrevented) return;
      store.setOpen(true);
    }
  });
  const { portalRef, domReady } = (0,CEM7J6TT/* usePortalRef */.TE)(portal, props.portalRef);
  const preserveTabOrderProp = props.preserveTabOrder;
  const preserveTabOrder = (0,YAS7X7HB/* useStoreState */.O$)(
    store,
    (state) => preserveTabOrderProp && !modal && state.mounted
  );
  const id = (0,CEM7J6TT/* useId */.Bi)(props.id);
  const open = (0,YAS7X7HB/* useStoreState */.O$)(store, "open");
  const mounted = (0,YAS7X7HB/* useStoreState */.O$)(store, "mounted");
  const contentElement = (0,YAS7X7HB/* useStoreState */.O$)(store, "contentElement");
  const hidden = (0,WLLNEL2X/* isHidden */.dK)(mounted, props.hidden, props.alwaysVisible);
  usePreventBodyScroll(contentElement, id, preventBodyScroll && !hidden);
  const interactedOutsideRef = (0,react.useRef)(false);
  (0,CEM7J6TT/* useSafeLayoutEffect */.UQ)(() => {
    return (0,XTZ53NXG/* sync */.OH)(store, ["open"], (state) => {
      if (!state.open) return;
      interactedOutsideRef.current = false;
    });
  }, [store]);
  useHideOnInteractOutside(
    store,
    hideOnInteractOutside,
    domReady,
    interactedOutsideRef
  );
  const { wrapElement, nestedDialogs } = useNestedDialogs(store);
  props = (0,CEM7J6TT/* useWrapElement */.w7)(props, wrapElement, [wrapElement]);
  const lastMousedownRef = (0,react.useRef)(null);
  if (isSafariBrowser) {
    (0,react.useEffect)(() => {
      if (!domReady) return;
      const dialog = ref.current;
      if (!dialog) return;
      const doc = (0,G7XPWBXK/* getDocument */.YE)(dialog);
      const onMousedown = (event) => {
        lastMousedownRef.current = event.target;
      };
      doc.addEventListener("mousedown", onMousedown, true);
      return () => {
        doc.removeEventListener("mousedown", onMousedown, true);
      };
    }, [domReady]);
  }
  (0,CEM7J6TT/* useSafeLayoutEffect */.UQ)(() => {
    if (!open) return;
    const dialog = ref.current;
    const activeElement = (0,G7XPWBXK/* getActiveElement */.bq)(dialog, true);
    if (!activeElement) return;
    if (activeElement.tagName === "BODY") {
      const fallback = lastMousedownRef.current;
      lastMousedownRef.current = null;
      if (!(fallback == null ? void 0 : fallback.isConnected)) return;
      if (!(0,utils_focus/* isFocusable */.tp)(fallback)) return;
      if (dialog && (0,G7XPWBXK/* contains */.gR)(dialog, fallback)) return;
      store.setDisclosureElement(fallback);
      return;
    }
    if (dialog && (0,G7XPWBXK/* contains */.gR)(dialog, activeElement)) return;
    store.setDisclosureElement(activeElement);
  }, [store, open]);
  (0,react.useEffect)(() => {
    if (!mounted) return;
    if (!domReady) return;
    const dialog = ref.current;
    if (!dialog) return;
    const win = (0,G7XPWBXK/* getWindow */.zk)(dialog);
    const viewport = win.visualViewport || win;
    const setViewportHeight = () => {
      var _a, _b;
      const height = (_b = (_a = win.visualViewport) == null ? void 0 : _a.height) != null ? _b : win.innerHeight;
      dialog.style.setProperty("--dialog-viewport-height", `${height}px`);
    };
    setViewportHeight();
    viewport.addEventListener("resize", setViewportHeight);
    return () => {
      viewport.removeEventListener("resize", setViewportHeight);
    };
  }, [mounted, domReady]);
  (0,react.useEffect)(() => {
    if (!modal) return;
    if (!mounted) return;
    if (!domReady) return;
    const dialog = ref.current;
    if (!dialog) return;
    const existingDismiss = dialog.querySelector("[data-dialog-dismiss]");
    if (existingDismiss) return;
    return prependHiddenDismiss(dialog, store.hide);
  }, [store, modal, mounted, domReady]);
  (0,CEM7J6TT/* useSafeLayoutEffect */.UQ)(() => {
    if (!supportsInert()) return;
    if (open) return;
    if (!mounted) return;
    if (!domReady) return;
    const dialog = ref.current;
    if (!dialog) return;
    return disableTree(dialog);
  }, [open, mounted, domReady]);
  const canTakeTreeSnapshot = open && domReady;
  (0,CEM7J6TT/* useSafeLayoutEffect */.UQ)(() => {
    if (!id) return;
    if (!canTakeTreeSnapshot) return;
    const dialog = ref.current;
    return createWalkTreeSnapshot(id, [dialog]);
  }, [id, canTakeTreeSnapshot, unstable_treeSnapshotKey]);
  const getPersistentElementsProp = (0,CEM7J6TT/* useEvent */._q)(getPersistentElements);
  (0,CEM7J6TT/* useSafeLayoutEffect */.UQ)(() => {
    if (!id) return;
    if (!canTakeTreeSnapshot) return;
    const { disclosureElement } = store.getState();
    const dialog = ref.current;
    const persistentElements = getPersistentElementsProp() || [];
    const allElements = [
      dialog,
      ...persistentElements,
      ...nestedDialogs.map((dialog2) => dialog2.getState().contentElement)
    ];
    if (modal) {
      return (0,UWJK2WK2/* chain */.cy)(
        markTreeOutside(id, allElements),
        disableTreeOutside(id, allElements)
      );
    }
    return markTreeOutside(id, [disclosureElement, ...allElements]);
  }, [
    id,
    store,
    canTakeTreeSnapshot,
    getPersistentElementsProp,
    nestedDialogs,
    modal,
    unstable_treeSnapshotKey
  ]);
  const mayAutoFocusOnShow = !!autoFocusOnShow;
  const autoFocusOnShowProp = (0,CEM7J6TT/* useBooleanEvent */.O4)(autoFocusOnShow);
  const [autoFocusEnabled, setAutoFocusEnabled] = (0,react.useState)(false);
  (0,react.useEffect)(() => {
    if (!open) return;
    if (!mayAutoFocusOnShow) return;
    if (!domReady) return;
    if (!(contentElement == null ? void 0 : contentElement.isConnected)) return;
    const element = getElementFromProp(initialFocus, true) || // If no initial focus is specified, we try to focus the first element
    // with the autofocus attribute. If it's an Ariakit component, the
    // Focusable component will consume the autoFocus prop and add the
    // data-autofocus attribute to the element instead.
    contentElement.querySelector(
      "[data-autofocus=true],[autofocus]"
    ) || // We have to fallback to the first focusable element otherwise portaled
    // dialogs with preserveTabOrder set to true will not receive focus
    // properly because the elements aren't tabbable until the dialog receives
    // focus.
    (0,utils_focus/* getFirstTabbableIn */.LC)(contentElement, true, portal && preserveTabOrder) || // Finally, we fallback to the dialog element itself.
    contentElement;
    const isElementFocusable = (0,utils_focus/* isFocusable */.tp)(element);
    if (!autoFocusOnShowProp(isElementFocusable ? element : null)) return;
    setAutoFocusEnabled(true);
    queueMicrotask(() => {
      if (!store.getState().open) return;
      element.focus();
      if (!isSafariBrowser) return;
      if (!isElementFocusable) return;
      element.scrollIntoView({ block: "nearest", inline: "nearest" });
    });
  }, [
    open,
    mayAutoFocusOnShow,
    domReady,
    contentElement,
    initialFocus,
    portal,
    preserveTabOrder,
    store,
    autoFocusOnShowProp
  ]);
  const mayAutoFocusOnHide = !!autoFocusOnHide;
  const autoFocusOnHideProp = (0,CEM7J6TT/* useBooleanEvent */.O4)(autoFocusOnHide);
  const [hasOpened, setHasOpened] = (0,react.useState)(false);
  (0,react.useEffect)(() => {
    if (!open) return;
    setHasOpened(true);
    return () => setHasOpened(false);
  }, [open]);
  const focusOnHide = (0,react.useCallback)(
    (dialog, retry = true) => {
      if (interactedOutsideRef.current) return;
      const { disclosureElement } = store.getState();
      if (isAlreadyFocusingAnotherElement(dialog)) return;
      let element = getElementFromProp(finalFocus) || disclosureElement;
      if (element == null ? void 0 : element.id) {
        const doc = (0,G7XPWBXK/* getDocument */.YE)(element);
        const selector = `[aria-activedescendant="${element.id}"]`;
        const composite = doc.querySelector(selector);
        if (composite) {
          element = composite;
        }
      }
      if (element && !(0,utils_focus/* isFocusable */.tp)(element)) {
        const maybeParentDialog = element.closest("[data-dialog]");
        if (maybeParentDialog == null ? void 0 : maybeParentDialog.id) {
          const doc = (0,G7XPWBXK/* getDocument */.YE)(maybeParentDialog);
          const selector = `[aria-controls~="${maybeParentDialog.id}"]`;
          const control = doc.querySelector(selector);
          if (control) {
            element = control;
          }
        }
      }
      const isElementFocusable = element && (0,utils_focus/* isFocusable */.tp)(element);
      if (!isElementFocusable && retry) {
        requestAnimationFrame(() => focusOnHide(dialog, false));
        return;
      }
      if (!autoFocusOnHideProp(isElementFocusable ? element : null)) return;
      if (!isElementFocusable) return;
      element == null ? void 0 : element.focus();
    },
    [store, finalFocus, autoFocusOnHideProp]
  );
  const focusedOnHideRef = (0,react.useRef)(false);
  (0,CEM7J6TT/* useSafeLayoutEffect */.UQ)(() => {
    if (open) return;
    if (!hasOpened) return;
    if (!mayAutoFocusOnHide) return;
    const dialog = ref.current;
    focusedOnHideRef.current = true;
    focusOnHide(dialog);
  }, [open, hasOpened, domReady, mayAutoFocusOnHide, focusOnHide]);
  (0,react.useEffect)(() => {
    if (!hasOpened) return;
    if (!mayAutoFocusOnHide) return;
    const dialog = ref.current;
    return () => {
      if (focusedOnHideRef.current) {
        focusedOnHideRef.current = false;
        return;
      }
      focusOnHide(dialog);
    };
  }, [hasOpened, mayAutoFocusOnHide, focusOnHide]);
  const hideOnEscapeProp = (0,CEM7J6TT/* useBooleanEvent */.O4)(hideOnEscape);
  (0,react.useEffect)(() => {
    if (!domReady) return;
    if (!mounted) return;
    const onKeyDown = (event) => {
      if (event.key !== "Escape") return;
      if (event.defaultPrevented) return;
      const dialog = ref.current;
      if (!dialog) return;
      if (isElementMarked(dialog)) return;
      const target = event.target;
      if (!target) return;
      const { disclosureElement } = store.getState();
      const isValidTarget = () => {
        if (target.tagName === "BODY") return true;
        if ((0,G7XPWBXK/* contains */.gR)(dialog, target)) return true;
        if (!disclosureElement) return true;
        if ((0,G7XPWBXK/* contains */.gR)(disclosureElement, target)) return true;
        return false;
      };
      if (!isValidTarget()) return;
      if (!hideOnEscapeProp(event)) return;
      store.hide();
    };
    return (0,events/* addGlobalEventListener */.dg)("keydown", onKeyDown, true);
  }, [store, domReady, mounted, hideOnEscapeProp]);
  props = (0,CEM7J6TT/* useWrapElement */.w7)(
    props,
    (element) => /* @__PURE__ */ (0,jsx_runtime.jsx)(HeadingLevel, { level: modal ? 1 : void 0, children: element }),
    [modal]
  );
  const hiddenProp = props.hidden;
  const alwaysVisible = props.alwaysVisible;
  props = (0,CEM7J6TT/* useWrapElement */.w7)(
    props,
    (element) => {
      if (!backdrop) return element;
      return /* @__PURE__ */ (0,jsx_runtime.jsxs)(jsx_runtime.Fragment, { children: [
        /* @__PURE__ */ (0,jsx_runtime.jsx)(
          DialogBackdrop,
          {
            store,
            backdrop,
            hidden: hiddenProp,
            alwaysVisible
          }
        ),
        element
      ] });
    },
    [store, backdrop, hiddenProp, alwaysVisible]
  );
  const [headingId, setHeadingId] = (0,react.useState)();
  const [descriptionId, setDescriptionId] = (0,react.useState)();
  props = (0,CEM7J6TT/* useWrapElement */.w7)(
    props,
    (element) => /* @__PURE__ */ (0,jsx_runtime.jsx)(W43ISTP6/* DialogScopedContextProvider */.XP, { value: store, children: /* @__PURE__ */ (0,jsx_runtime.jsx)(W43ISTP6/* DialogHeadingContext */.YH.Provider, { value: setHeadingId, children: /* @__PURE__ */ (0,jsx_runtime.jsx)(W43ISTP6/* DialogDescriptionContext */.oL.Provider, { value: setDescriptionId, children: element }) }) }),
    [store]
  );
  props = {
    "data-dialog": "",
    role: "dialog",
    tabIndex: focusable ? -1 : void 0,
    "aria-labelledby": props["aria-label"] != null ? void 0 : headingId,
    "aria-describedby": descriptionId,
    ...props,
    id,
    ref: (0,CEM7J6TT/* useMergeRefs */.SV)(ref, props.ref)
  };
  props = useFocusableContainer({
    ...props,
    autoFocusOnShow: autoFocusEnabled
  });
  props = (0,WLLNEL2X/* useDisclosureContent */.aT)({ store, ...props });
  props = (0,GR523XJ6/* useFocusable */.W)({ ...props, focusable });
  props = usePortal({ portal, ...props, portalRef, preserveTabOrder });
  return props;
});
function createDialogComponent(Component, useProviderContext = W43ISTP6/* useDialogProviderContext */.cH) {
  return (0,TVXRYIJB/* forwardRef */.Rf)(function DialogComponent(props) {
    const context = useProviderContext();
    const store = props.store || context;
    const mounted = (0,YAS7X7HB/* useStoreState */.O$)(
      store,
      (state) => !props.unmountOnHide || (state == null ? void 0 : state.mounted) || !!props.open
    );
    if (!mounted) return null;
    return /* @__PURE__ */ (0,jsx_runtime.jsx)(Component, { ...props });
  });
}
var Dialog = createDialogComponent(
  (0,TVXRYIJB/* forwardRef */.Rf)(function Dialog2(props) {
    const htmlProps = useDialog(props);
    return (0,TVXRYIJB/* createElement */.n)(_7WPPMYFR_TagName, htmlProps);
  }),
  W43ISTP6/* useDialogProviderContext */.cH
);



// EXTERNAL MODULE: ../../node_modules/.pnpm/@floating-ui+dom@1.7.6/node_modules/@floating-ui/dom/dist/floating-ui.dom.mjs + 3 modules
var floating_ui_dom = __webpack_require__("../../node_modules/.pnpm/@floating-ui+dom@1.7.6/node_modules/@floating-ui/dom/dist/floating-ui.dom.mjs");
;// ../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/JVH3F2PI.js
"use client";






// src/popover/popover.tsx




var JVH3F2PI_TagName = "div";
function createDOMRect(x = 0, y = 0, width = 0, height = 0) {
  if (typeof DOMRect === "function") {
    return new DOMRect(x, y, width, height);
  }
  const rect = {
    x,
    y,
    width,
    height,
    top: y,
    right: x + width,
    bottom: y + height,
    left: x
  };
  return { ...rect, toJSON: () => rect };
}
function getDOMRect(anchorRect) {
  if (!anchorRect) return createDOMRect();
  const { x, y, width, height } = anchorRect;
  return createDOMRect(x, y, width, height);
}
function getAnchorElement(anchorElement, getAnchorRect) {
  const contextElement = anchorElement || void 0;
  return {
    contextElement,
    getBoundingClientRect: () => {
      const anchor = anchorElement;
      const anchorRect = getAnchorRect == null ? void 0 : getAnchorRect(anchor);
      if (anchorRect || !anchor) {
        return getDOMRect(anchorRect);
      }
      return anchor.getBoundingClientRect();
    }
  };
}
function isValidPlacement(flip2) {
  return /^(?:top|bottom|left|right)(?:-(?:start|end))?$/.test(flip2);
}
function roundByDPR(value) {
  const dpr = window.devicePixelRatio || 1;
  return Math.round(value * dpr) / dpr;
}
function getOffsetMiddleware(arrowElement, props) {
  return (0,floating_ui_dom/* offset */.cY)(({ placement }) => {
    var _a;
    const arrowOffset = ((arrowElement == null ? void 0 : arrowElement.clientHeight) || 0) / 2;
    const finalGutter = typeof props.gutter === "number" ? props.gutter + arrowOffset : (_a = props.gutter) != null ? _a : arrowOffset;
    const hasAlignment = !!placement.split("-")[1];
    return {
      crossAxis: !hasAlignment ? props.shift : void 0,
      mainAxis: finalGutter,
      alignmentAxis: props.shift
    };
  });
}
function getFlipMiddleware(props) {
  if (props.flip === false) return;
  const fallbackPlacements = typeof props.flip === "string" ? props.flip.split(" ") : void 0;
  (0,UWJK2WK2/* invariant */.V1)(
    !fallbackPlacements || fallbackPlacements.every(isValidPlacement),
     false && 0
  );
  return (0,floating_ui_dom/* flip */.UU)({
    padding: props.overflowPadding,
    fallbackPlacements
  });
}
function getShiftMiddleware(props) {
  if (!props.slide && !props.overlap) return;
  return (0,floating_ui_dom/* shift */.BN)({
    mainAxis: props.slide,
    crossAxis: props.overlap,
    padding: props.overflowPadding,
    limiter: (0,floating_ui_dom/* limitShift */.ER)()
  });
}
function getSizeMiddleware(props) {
  return (0,floating_ui_dom/* size */.Ej)({
    padding: props.overflowPadding,
    apply({ elements, availableWidth, availableHeight, rects }) {
      const wrapper = elements.floating;
      const referenceWidth = Math.round(rects.reference.width);
      availableWidth = Math.floor(availableWidth);
      availableHeight = Math.floor(availableHeight);
      wrapper.style.setProperty(
        "--popover-anchor-width",
        `${referenceWidth}px`
      );
      wrapper.style.setProperty(
        "--popover-available-width",
        `${availableWidth}px`
      );
      wrapper.style.setProperty(
        "--popover-available-height",
        `${availableHeight}px`
      );
      if (props.sameWidth) {
        wrapper.style.width = `${referenceWidth}px`;
      }
      if (props.fitViewport) {
        wrapper.style.maxWidth = `${availableWidth}px`;
        wrapper.style.maxHeight = `${availableHeight}px`;
      }
    }
  });
}
function getArrowMiddleware(arrowElement, props) {
  if (!arrowElement) return;
  return (0,floating_ui_dom/* arrow */.UE)({
    element: arrowElement,
    padding: props.arrowPadding
  });
}
var usePopover = (0,TVXRYIJB/* createHook */.ab)(
  function usePopover2({
    store,
    modal = false,
    portal = !!modal,
    preserveTabOrder = true,
    autoFocusOnShow = true,
    wrapperProps,
    fixed = false,
    flip: flip2 = true,
    shift: shift2 = 0,
    slide = true,
    overlap = false,
    sameWidth = false,
    fitViewport = false,
    gutter,
    arrowPadding = 4,
    overflowPadding = 8,
    getAnchorRect,
    updatePosition,
    ...props
  }) {
    const context = (0,E4DZA6YM/* usePopoverProviderContext */.zG)();
    store = store || context;
    (0,UWJK2WK2/* invariant */.V1)(
      store,
       false && 0
    );
    const arrowElement = (0,YAS7X7HB/* useStoreState */.O$)(store, "arrowElement");
    const anchorElement = (0,YAS7X7HB/* useStoreState */.O$)(store, "anchorElement");
    const disclosureElement = (0,YAS7X7HB/* useStoreState */.O$)(store, "disclosureElement");
    const popoverElement = (0,YAS7X7HB/* useStoreState */.O$)(store, "popoverElement");
    const contentElement = (0,YAS7X7HB/* useStoreState */.O$)(store, "contentElement");
    const placement = (0,YAS7X7HB/* useStoreState */.O$)(store, "placement");
    const mounted = (0,YAS7X7HB/* useStoreState */.O$)(store, "mounted");
    const rendered = (0,YAS7X7HB/* useStoreState */.O$)(store, "rendered");
    const defaultArrowElementRef = (0,react.useRef)(null);
    const [positioned, setPositioned] = (0,react.useState)(false);
    const { portalRef, domReady } = (0,CEM7J6TT/* usePortalRef */.TE)(portal, props.portalRef);
    const getAnchorRectProp = (0,CEM7J6TT/* useEvent */._q)(getAnchorRect);
    const updatePositionProp = (0,CEM7J6TT/* useEvent */._q)(updatePosition);
    const hasCustomUpdatePosition = !!updatePosition;
    (0,CEM7J6TT/* useSafeLayoutEffect */.UQ)(() => {
      if (!(popoverElement == null ? void 0 : popoverElement.isConnected)) return;
      popoverElement.style.setProperty(
        "--popover-overflow-padding",
        `${overflowPadding}px`
      );
      const anchor = getAnchorElement(anchorElement, getAnchorRectProp);
      const updatePosition2 = async () => {
        if (!mounted) return;
        if (!arrowElement) {
          defaultArrowElementRef.current = defaultArrowElementRef.current || document.createElement("div");
        }
        const arrow2 = arrowElement || defaultArrowElementRef.current;
        const middleware = [
          getOffsetMiddleware(arrow2, { gutter, shift: shift2 }),
          getFlipMiddleware({ flip: flip2, overflowPadding }),
          getShiftMiddleware({ slide, shift: shift2, overlap, overflowPadding }),
          getArrowMiddleware(arrow2, { arrowPadding }),
          getSizeMiddleware({
            sameWidth,
            fitViewport,
            overflowPadding
          })
        ];
        const pos = await (0,floating_ui_dom/* computePosition */.rD)(anchor, popoverElement, {
          placement,
          strategy: fixed ? "fixed" : "absolute",
          middleware
        });
        store == null ? void 0 : store.setState("currentPlacement", pos.placement);
        setPositioned(true);
        const x = roundByDPR(pos.x);
        const y = roundByDPR(pos.y);
        Object.assign(popoverElement.style, {
          top: "0",
          left: "0",
          transform: `translate3d(${x}px,${y}px,0)`
        });
        if (arrow2 && pos.middlewareData.arrow) {
          const { x: arrowX, y: arrowY } = pos.middlewareData.arrow;
          const side = pos.placement.split("-")[0];
          const centerX = arrow2.clientWidth / 2;
          const centerY = arrow2.clientHeight / 2;
          const originX = arrowX != null ? arrowX + centerX : -centerX;
          const originY = arrowY != null ? arrowY + centerY : -centerY;
          popoverElement.style.setProperty(
            "--popover-transform-origin",
            {
              top: `${originX}px calc(100% + ${centerY}px)`,
              bottom: `${originX}px ${-centerY}px`,
              left: `calc(100% + ${centerX}px) ${originY}px`,
              right: `${-centerX}px ${originY}px`
            }[side]
          );
          Object.assign(arrow2.style, {
            left: arrowX != null ? `${arrowX}px` : "",
            top: arrowY != null ? `${arrowY}px` : "",
            [side]: "100%"
          });
        }
      };
      const update = async () => {
        if (hasCustomUpdatePosition) {
          await updatePositionProp({ updatePosition: updatePosition2 });
          setPositioned(true);
        } else {
          await updatePosition2();
        }
      };
      const cancelAutoUpdate = (0,floating_ui_dom/* autoUpdate */.ll)(anchor, popoverElement, update, {
        // JSDOM doesn't support ResizeObserver
        elementResize: typeof ResizeObserver === "function"
      });
      return () => {
        setPositioned(false);
        cancelAutoUpdate();
      };
    }, [
      store,
      rendered,
      popoverElement,
      arrowElement,
      anchorElement,
      popoverElement,
      placement,
      mounted,
      domReady,
      fixed,
      flip2,
      shift2,
      slide,
      overlap,
      sameWidth,
      fitViewport,
      gutter,
      arrowPadding,
      overflowPadding,
      getAnchorRectProp,
      hasCustomUpdatePosition,
      updatePositionProp
    ]);
    (0,CEM7J6TT/* useSafeLayoutEffect */.UQ)(() => {
      if (!mounted) return;
      if (!domReady) return;
      if (!(popoverElement == null ? void 0 : popoverElement.isConnected)) return;
      if (!(contentElement == null ? void 0 : contentElement.isConnected)) return;
      const applyZIndex = () => {
        popoverElement.style.zIndex = getComputedStyle(contentElement).zIndex;
      };
      applyZIndex();
      let raf = requestAnimationFrame(() => {
        raf = requestAnimationFrame(applyZIndex);
      });
      return () => cancelAnimationFrame(raf);
    }, [mounted, domReady, popoverElement, contentElement]);
    const position = fixed ? "fixed" : "absolute";
    props = (0,CEM7J6TT/* useWrapElement */.w7)(
      props,
      (element) => /* @__PURE__ */ (0,jsx_runtime.jsx)(
        "div",
        {
          ...wrapperProps,
          style: {
            // https://floating-ui.com/docs/computeposition#initial-layout
            position,
            top: 0,
            left: 0,
            width: "max-content",
            ...wrapperProps == null ? void 0 : wrapperProps.style
          },
          ref: store == null ? void 0 : store.setPopoverElement,
          children: element
        }
      ),
      [store, position, wrapperProps]
    );
    props = (0,CEM7J6TT/* useWrapElement */.w7)(
      props,
      (element) => /* @__PURE__ */ (0,jsx_runtime.jsx)(E4DZA6YM/* PopoverScopedContextProvider */.s1, { value: store, children: element }),
      [store]
    );
    props = {
      // data-placing is not part of the public API. We're setting this here so
      // we can wait for the popover to be positioned before other components
      // move focus into it. For example, this attribute is observed by the
      // Combobox component with the autoSelect behavior.
      "data-placing": !positioned || void 0,
      ...props,
      style: {
        position: "relative",
        ...props.style
      }
    };
    props = useDialog({
      store,
      modal,
      portal,
      preserveTabOrder,
      preserveTabOrderAnchor: disclosureElement || anchorElement,
      autoFocusOnShow: positioned && autoFocusOnShow,
      ...props,
      portalRef
    });
    return props;
  }
);
var Popover = createDialogComponent(
  (0,TVXRYIJB/* forwardRef */.Rf)(function Popover2(props) {
    const htmlProps = usePopover(props);
    return (0,TVXRYIJB/* createElement */.n)(JVH3F2PI_TagName, htmlProps);
  }),
  E4DZA6YM/* usePopoverProviderContext */.zG
);



;// ../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/__chunks/47DURVCG.js
"use client";








// src/hovercard/hovercard.tsx







var _47DURVCG_TagName = "div";
function isMovingOnHovercard(target, card, anchor, nested) {
  if ((0,utils_focus/* hasFocusWithin */.oW)(card)) return true;
  if (!target) return false;
  if ((0,G7XPWBXK/* contains */.gR)(card, target)) return true;
  if (anchor && (0,G7XPWBXK/* contains */.gR)(anchor, target)) return true;
  if (nested == null ? void 0 : nested.some((card2) => isMovingOnHovercard(target, card2, anchor))) {
    return true;
  }
  return false;
}
function useAutoFocusOnHide({
  store,
  ...props
}) {
  const [autoFocusOnHide, setAutoFocusOnHide] = (0,react.useState)(false);
  const mounted = (0,YAS7X7HB/* useStoreState */.O$)(store, "mounted");
  (0,react.useEffect)(() => {
    if (!mounted) {
      setAutoFocusOnHide(false);
    }
  }, [mounted]);
  const onFocusProp = props.onFocus;
  const onFocus = (0,CEM7J6TT/* useEvent */._q)((event) => {
    onFocusProp == null ? void 0 : onFocusProp(event);
    if (event.defaultPrevented) return;
    setAutoFocusOnHide(true);
  });
  const finalFocusRef = (0,react.useRef)(null);
  (0,react.useEffect)(() => {
    return (0,XTZ53NXG/* sync */.OH)(store, ["anchorElement"], (state) => {
      finalFocusRef.current = state.anchorElement;
    });
  }, [store]);
  props = {
    autoFocusOnHide,
    finalFocus: finalFocusRef,
    ...props,
    onFocus
  };
  return props;
}
var NestedHovercardContext = (0,react.createContext)(null);
var useHovercard = (0,TVXRYIJB/* createHook */.ab)(
  function useHovercard2({
    store,
    modal = false,
    portal = !!modal,
    hideOnEscape = true,
    hideOnHoverOutside = true,
    disablePointerEventsOnApproach = !!hideOnHoverOutside,
    ...props
  }) {
    const context = useHovercardProviderContext();
    store = store || context;
    (0,UWJK2WK2/* invariant */.V1)(
      store,
       false && 0
    );
    const ref = (0,react.useRef)(null);
    const [nestedHovercards, setNestedHovercards] = (0,react.useState)([]);
    const hideTimeoutRef = (0,react.useRef)(0);
    const enterPointRef = (0,react.useRef)(null);
    const { portalRef, domReady } = (0,CEM7J6TT/* usePortalRef */.TE)(portal, props.portalRef);
    const isMouseMoving = (0,CEM7J6TT/* useIsMouseMoving */.P$)();
    const mayHideOnHoverOutside = !!hideOnHoverOutside;
    const hideOnHoverOutsideProp = (0,CEM7J6TT/* useBooleanEvent */.O4)(hideOnHoverOutside);
    const mayDisablePointerEvents = !!disablePointerEventsOnApproach;
    const disablePointerEventsProp = (0,CEM7J6TT/* useBooleanEvent */.O4)(
      disablePointerEventsOnApproach
    );
    const open = (0,YAS7X7HB/* useStoreState */.O$)(store, "open");
    const mounted = (0,YAS7X7HB/* useStoreState */.O$)(store, "mounted");
    (0,react.useEffect)(() => {
      if (!domReady) return;
      if (!mounted) return;
      if (!mayHideOnHoverOutside && !mayDisablePointerEvents) return;
      const element = ref.current;
      if (!element) return;
      const onMouseMove = (event) => {
        if (!store) return;
        if (!isMouseMoving()) return;
        const { anchorElement, hideTimeout, timeout } = store.getState();
        const enterPoint = enterPointRef.current;
        const [target] = event.composedPath();
        const anchor = anchorElement;
        if (isMovingOnHovercard(target, element, anchor, nestedHovercards)) {
          enterPointRef.current = target && anchor && (0,G7XPWBXK/* contains */.gR)(anchor, target) ? getEventPoint(event) : null;
          window.clearTimeout(hideTimeoutRef.current);
          hideTimeoutRef.current = 0;
          return;
        }
        if (hideTimeoutRef.current) return;
        if (enterPoint) {
          const currentPoint = getEventPoint(event);
          const polygon = getElementPolygon(element, enterPoint);
          if (isPointInPolygon(currentPoint, polygon)) {
            enterPointRef.current = currentPoint;
            if (!disablePointerEventsProp(event)) return;
            event.preventDefault();
            event.stopPropagation();
            return;
          }
        }
        if (!hideOnHoverOutsideProp(event)) return;
        hideTimeoutRef.current = window.setTimeout(() => {
          hideTimeoutRef.current = 0;
          store == null ? void 0 : store.hide();
        }, hideTimeout != null ? hideTimeout : timeout);
      };
      return (0,UWJK2WK2/* chain */.cy)(
        (0,events/* addGlobalEventListener */.dg)("mousemove", onMouseMove, true),
        () => clearTimeout(hideTimeoutRef.current)
      );
    }, [
      store,
      isMouseMoving,
      domReady,
      mounted,
      mayHideOnHoverOutside,
      mayDisablePointerEvents,
      nestedHovercards,
      disablePointerEventsProp,
      hideOnHoverOutsideProp
    ]);
    (0,react.useEffect)(() => {
      if (!domReady) return;
      if (!mounted) return;
      if (!mayDisablePointerEvents) return;
      const disableEvent = (event) => {
        const element = ref.current;
        if (!element) return;
        const enterPoint = enterPointRef.current;
        if (!enterPoint) return;
        const polygon = getElementPolygon(element, enterPoint);
        if (isPointInPolygon(getEventPoint(event), polygon)) {
          if (!disablePointerEventsProp(event)) return;
          event.preventDefault();
          event.stopPropagation();
        }
      };
      return (0,UWJK2WK2/* chain */.cy)(
        // Note: we may need to add pointer events here in the future.
        (0,events/* addGlobalEventListener */.dg)("mouseenter", disableEvent, true),
        (0,events/* addGlobalEventListener */.dg)("mouseover", disableEvent, true),
        (0,events/* addGlobalEventListener */.dg)("mouseout", disableEvent, true),
        (0,events/* addGlobalEventListener */.dg)("mouseleave", disableEvent, true)
      );
    }, [domReady, mounted, mayDisablePointerEvents, disablePointerEventsProp]);
    (0,react.useEffect)(() => {
      if (!domReady) return;
      if (open) return;
      store == null ? void 0 : store.setAutoFocusOnShow(false);
    }, [store, domReady, open]);
    const openRef = (0,CEM7J6TT/* useLiveRef */.hX)(open);
    (0,react.useEffect)(() => {
      if (!domReady) return;
      return () => {
        if (!openRef.current) {
          store == null ? void 0 : store.setAutoFocusOnShow(false);
        }
      };
    }, [store, domReady]);
    const registerOnParent = (0,react.useContext)(NestedHovercardContext);
    (0,CEM7J6TT/* useSafeLayoutEffect */.UQ)(() => {
      if (modal) return;
      if (!portal) return;
      if (!mounted) return;
      if (!domReady) return;
      const element = ref.current;
      if (!element) return;
      return registerOnParent == null ? void 0 : registerOnParent(element);
    }, [modal, portal, mounted, domReady]);
    const registerNestedHovercard = (0,react.useCallback)(
      (element) => {
        setNestedHovercards((prevElements) => [...prevElements, element]);
        const parentUnregister = registerOnParent == null ? void 0 : registerOnParent(element);
        return () => {
          setNestedHovercards(
            (prevElements) => prevElements.filter((item) => item !== element)
          );
          parentUnregister == null ? void 0 : parentUnregister();
        };
      },
      [registerOnParent]
    );
    props = (0,CEM7J6TT/* useWrapElement */.w7)(
      props,
      (element) => /* @__PURE__ */ (0,jsx_runtime.jsx)(HovercardScopedContextProvider, { value: store, children: /* @__PURE__ */ (0,jsx_runtime.jsx)(NestedHovercardContext.Provider, { value: registerNestedHovercard, children: element }) }),
      [store, registerNestedHovercard]
    );
    props = {
      ...props,
      ref: (0,CEM7J6TT/* useMergeRefs */.SV)(ref, props.ref)
    };
    props = useAutoFocusOnHide({ store, ...props });
    const autoFocusOnShow = (0,YAS7X7HB/* useStoreState */.O$)(
      store,
      (state) => modal || state.autoFocusOnShow
    );
    props = usePopover({
      store,
      modal,
      portal,
      autoFocusOnShow,
      ...props,
      portalRef,
      hideOnEscape(event) {
        if ((0,UWJK2WK2/* isFalsyBooleanCallback */.zO)(hideOnEscape, event)) return false;
        requestAnimationFrame(() => {
          requestAnimationFrame(() => {
            store == null ? void 0 : store.hide();
          });
        });
        return true;
      }
    });
    return props;
  }
);
var Hovercard = createDialogComponent(
  (0,TVXRYIJB/* forwardRef */.Rf)(function Hovercard2(props) {
    const htmlProps = useHovercard(props);
    return (0,TVXRYIJB/* createElement */.n)(_47DURVCG_TagName, htmlProps);
  }),
  useHovercardProviderContext
);



;// ../../node_modules/.pnpm/@ariakit+react-core@0.4.25__1388364b8ee48ea0f657872373bbb61a/node_modules/@ariakit/react-core/esm/tooltip/tooltip.js
"use client";










































// src/tooltip/tooltip.tsx



var tooltip_TagName = "div";
var useTooltip = (0,TVXRYIJB/* createHook */.ab)(
  function useTooltip2({
    store,
    portal = true,
    gutter = 8,
    preserveTabOrder = false,
    hideOnHoverOutside = true,
    hideOnInteractOutside = true,
    ...props
  }) {
    const context = useTooltipProviderContext();
    store = store || context;
    (0,UWJK2WK2/* invariant */.V1)(
      store,
       false && 0
    );
    props = (0,CEM7J6TT/* useWrapElement */.w7)(
      props,
      (element) => /* @__PURE__ */ (0,jsx_runtime.jsx)(TooltipScopedContextProvider, { value: store, children: element }),
      [store]
    );
    const role = (0,YAS7X7HB/* useStoreState */.O$)(
      store,
      (state) => state.type === "description" ? "tooltip" : "none"
    );
    props = { role, ...props };
    props = useHovercard({
      ...props,
      store,
      portal,
      gutter,
      preserveTabOrder,
      hideOnHoverOutside(event) {
        if ((0,UWJK2WK2/* isFalsyBooleanCallback */.zO)(hideOnHoverOutside, event)) return false;
        const anchorElement = store == null ? void 0 : store.getState().anchorElement;
        if (!anchorElement) return true;
        if ("focusVisible" in anchorElement.dataset) return false;
        return true;
      },
      hideOnInteractOutside: (event) => {
        if ((0,UWJK2WK2/* isFalsyBooleanCallback */.zO)(hideOnInteractOutside, event)) return false;
        const anchorElement = store == null ? void 0 : store.getState().anchorElement;
        if (!anchorElement) return true;
        if ((0,G7XPWBXK/* contains */.gR)(anchorElement, event.target)) return false;
        return true;
      }
    });
    return props;
  }
);
var Tooltip = createDialogComponent(
  (0,TVXRYIJB/* forwardRef */.Rf)(function Tooltip2(props) {
    const htmlProps = useTooltip(props);
    return (0,TVXRYIJB/* createElement */.n)(tooltip_TagName, htmlProps);
  }),
  useTooltipProviderContext
);


// EXTERNAL MODULE: ../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs
var clsx = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+compose@7.45.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-instance-id/index.mjs
var use_instance_id = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@7.45.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-instance-id/index.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+deprecated@4.48.1/node_modules/@wordpress/deprecated/build-module/index.mjs
var build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+deprecated@4.48.1/node_modules/@wordpress/deprecated/build-module/index.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/shortcut/index.js
var build_module_shortcut = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/shortcut/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/popover/utils.js
var utils = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/popover/utils.js");
;// ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/tooltip/index.js








const TooltipInternalContext = (0,react.createContext)({
  isNestedInTooltip: false
});
TooltipInternalContext.displayName = "TooltipInternalContext";
const TOOLTIP_DELAY = 700;
const CONTEXT_VALUE = {
  isNestedInTooltip: true
};
function UnforwardedTooltip(props, ref) {
  const {
    children,
    className,
    delay = TOOLTIP_DELAY,
    hideOnClick = true,
    placement,
    position,
    shortcut,
    text,
    ...restProps
  } = props;
  const {
    isNestedInTooltip
  } = (0,react.useContext)(TooltipInternalContext);
  const baseId = (0,use_instance_id/* default */.A)(tooltip_Tooltip, "tooltip");
  const describedById = text || shortcut ? baseId : void 0;
  const isOnlyChild = react.Children.count(children) === 1;
  if (!isOnlyChild) {
    if (false) {}
  }
  let computedPlacement;
  if (placement !== void 0) {
    computedPlacement = placement;
  } else if (position !== void 0) {
    computedPlacement = (0,utils/* positionToPlacement */.YK)(position);
    (0,build_module/* default */.A)("`position` prop in wp.components.tooltip", {
      since: "6.4",
      alternative: "`placement` prop"
    });
  }
  computedPlacement = computedPlacement || "bottom";
  const tooltipStore = useTooltipStore({
    placement: computedPlacement,
    showTimeout: delay
  });
  const mounted = YAS7X7HB/* useStoreState */.O$(tooltipStore, "mounted");
  if (isNestedInTooltip) {
    return isOnlyChild ? /* @__PURE__ */ (0,jsx_runtime.jsx)(Role, {
      ...restProps,
      render: children
    }) : children;
  }
  function addDescribedById(element) {
    return describedById && mounted && element.props["aria-describedby"] === void 0 && element.props["aria-label"] !== text ? (0,react.cloneElement)(element, {
      "aria-describedby": describedById
    }) : element;
  }
  return /* @__PURE__ */ (0,jsx_runtime.jsxs)(TooltipInternalContext.Provider, {
    value: CONTEXT_VALUE,
    children: [/* @__PURE__ */ (0,jsx_runtime.jsx)(TooltipAnchor, {
      onClick: hideOnClick ? tooltipStore.hide : void 0,
      store: tooltipStore,
      render: isOnlyChild ? addDescribedById(children) : void 0,
      ref,
      children: isOnlyChild ? void 0 : children
    }), isOnlyChild && (text || shortcut) && /* @__PURE__ */ (0,jsx_runtime.jsxs)(Tooltip, {
      ...restProps,
      className: (0,clsx/* default */.A)("components-tooltip", className),
      unmountOnHide: true,
      gutter: 4,
      id: describedById,
      overflowPadding: 0.5,
      store: tooltipStore,
      children: [text, shortcut && /* @__PURE__ */ (0,jsx_runtime.jsx)(build_module_shortcut/* default */.A, {
        className: text ? "components-tooltip__shortcut" : "",
        shortcut
      })]
    })]
  });
}
const tooltip_Tooltip = (0,react.forwardRef)(UnforwardedTooltip);
var tooltip_default = tooltip_Tooltip;

//# sourceMappingURL=index.js.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/visually-hidden/component.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  A: () => (/* binding */ component_default)
});

// UNUSED EXPORTS: VisuallyHidden

// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/context/use-context-system.js + 1 modules
var use_context_system = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/context/use-context-system.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/context/context-connect.js
var context_connect = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/context/context-connect.js");
;// ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/visually-hidden/styles.js
const visuallyHidden = {
  border: 0,
  clip: "rect(1px, 1px, 1px, 1px)",
  WebkitClipPath: "inset( 50% )",
  clipPath: "inset( 50% )",
  height: "1px",
  margin: "-1px",
  overflow: "hidden",
  padding: 0,
  position: "absolute",
  width: "1px",
  wordWrap: "normal"
};

//# sourceMappingURL=styles.js.map

// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/view/component.js
var component = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/view/component.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js
var jsx_runtime = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
;// ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/visually-hidden/component.js




function UnconnectedVisuallyHidden(props, forwardedRef) {
  const {
    style: styleProp,
    ...contextProps
  } = (0,use_context_system/* useContextSystem */.A)(props, "VisuallyHidden");
  return /* @__PURE__ */ (0,jsx_runtime.jsx)(component/* default */.A, {
    ref: forwardedRef,
    ...contextProps,
    style: {
      ...visuallyHidden,
      ...styleProp || {}
    }
  });
}
const VisuallyHidden = (0,context_connect/* contextConnect */.KZ)(UnconnectedVisuallyHidden, "VisuallyHidden");
var component_default = VisuallyHidden;

//# sourceMappingURL=component.js.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+compose@7.45.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-instance-id/index.mjs":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (/* binding */ use_instance_id_default)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// packages/compose/src/hooks/use-instance-id/index.ts

var instanceMap = /* @__PURE__ */ new WeakMap();
function createId(object) {
  const instances = instanceMap.get(object) || 0;
  instanceMap.set(object, instances + 1);
  return instances;
}
function useInstanceId(object, prefix, preferredId) {
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useMemo)(() => {
    if (preferredId) {
      return preferredId;
    }
    const id = createId(object);
    return prefix ? `${prefix}-${id}` : id;
  }, [object, preferredId, prefix]);
}
var use_instance_id_default = useInstanceId;

//# sourceMappingURL=index.mjs.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+deprecated@4.48.1/node_modules/@wordpress/deprecated/build-module/index.mjs":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (/* binding */ deprecated)
/* harmony export */ });
/* unused harmony export logged */
/* harmony import */ var _wordpress_hooks__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+hooks@4.50.0/node_modules/@wordpress/hooks/build-module/index.mjs");
// packages/deprecated/src/index.ts

var logged = /* @__PURE__ */ Object.create(null);
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
  (0,_wordpress_hooks__WEBPACK_IMPORTED_MODULE_0__/* .doAction */ .Eo)("deprecated", feature, options, message);
  console.warn(message);
  logged[message] = true;
}

//# sourceMappingURL=index.mjs.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+hooks@4.50.0/node_modules/@wordpress/hooks/build-module/index.mjs":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  se: () => (/* binding */ defaultHooks),
  Eo: () => (/* binding */ doAction)
});

// UNUSED EXPORTS: actions, addAction, addFilter, applyFilters, applyFiltersAsync, createHooks, currentAction, currentFilter, didAction, didFilter, doActionAsync, doingAction, doingFilter, filters, hasAction, hasFilter, removeAction, removeAllActions, removeAllFilters, removeFilter

;// ../../node_modules/.pnpm/@wordpress+hooks@4.50.0/node_modules/@wordpress/hooks/build-module/validateNamespace.mjs
// packages/hooks/src/validateNamespace.ts
function validateNamespace(namespace) {
  if ("string" !== typeof namespace || "" === namespace) {
    console.error("The namespace must be a non-empty string.");
    return false;
  }
  if (!/^[a-zA-Z][a-zA-Z0-9_.\-\/]*$/.test(namespace)) {
    console.error(
      "The namespace can only contain numbers, letters, dashes, periods, underscores and slashes."
    );
    return false;
  }
  return true;
}
var validateNamespace_default = validateNamespace;

//# sourceMappingURL=validateNamespace.mjs.map

;// ../../node_modules/.pnpm/@wordpress+hooks@4.50.0/node_modules/@wordpress/hooks/build-module/validateHookName.mjs
// packages/hooks/src/validateHookName.ts
function validateHookName(hookName) {
  if ("string" !== typeof hookName || "" === hookName) {
    console.error("The hook name must be a non-empty string.");
    return false;
  }
  if (/^__/.test(hookName)) {
    console.error("The hook name cannot begin with `__`.");
    return false;
  }
  if (!/^[a-zA-Z][a-zA-Z0-9_.-]*$/.test(hookName)) {
    console.error(
      "The hook name can only contain numbers, letters, dashes, periods and underscores."
    );
    return false;
  }
  return true;
}
var validateHookName_default = validateHookName;

//# sourceMappingURL=validateHookName.mjs.map

;// ../../node_modules/.pnpm/@wordpress+hooks@4.50.0/node_modules/@wordpress/hooks/build-module/createAddHook.mjs
// packages/hooks/src/createAddHook.ts


function createAddHook(hooks, storeKey) {
  return function addHook(hookName, namespace, callback, priority = 10) {
    const hooksStore = hooks[storeKey];
    if (!validateHookName_default(hookName)) {
      return;
    }
    if (!validateNamespace_default(namespace)) {
      return;
    }
    if ("function" !== typeof callback) {
      console.error("The hook callback must be a function.");
      return;
    }
    if ("number" !== typeof priority) {
      console.error(
        "If specified, the hook priority must be a number."
      );
      return;
    }
    const handler = { callback, priority, namespace };
    if (hooksStore[hookName]) {
      const handlers = hooksStore[hookName].handlers;
      let i;
      for (i = handlers.length; i > 0; i--) {
        if (priority >= handlers[i - 1].priority) {
          break;
        }
      }
      if (i === handlers.length) {
        handlers[i] = handler;
      } else {
        handlers.splice(i, 0, handler);
      }
      hooksStore.__current.forEach((hookInfo) => {
        if (hookInfo.name === hookName && hookInfo.currentIndex >= i) {
          hookInfo.currentIndex++;
        }
      });
    } else {
      hooksStore[hookName] = {
        handlers: [handler],
        runs: 0
      };
    }
    if (hookName !== "hookAdded") {
      hooks.doAction(
        "hookAdded",
        hookName,
        namespace,
        callback,
        priority
      );
    }
  };
}
var createAddHook_default = createAddHook;

//# sourceMappingURL=createAddHook.mjs.map

;// ../../node_modules/.pnpm/@wordpress+hooks@4.50.0/node_modules/@wordpress/hooks/build-module/createRemoveHook.mjs
// packages/hooks/src/createRemoveHook.ts


function createRemoveHook(hooks, storeKey, removeAll = false) {
  return function removeHook(hookName, namespace) {
    const hooksStore = hooks[storeKey];
    if (!validateHookName_default(hookName)) {
      return;
    }
    if (!removeAll && !validateNamespace_default(namespace)) {
      return;
    }
    if (!hooksStore[hookName]) {
      return 0;
    }
    let handlersRemoved = 0;
    if (removeAll) {
      handlersRemoved = hooksStore[hookName].handlers.length;
      hooksStore[hookName] = {
        runs: hooksStore[hookName].runs,
        handlers: []
      };
    } else {
      const handlers = hooksStore[hookName].handlers;
      for (let i = handlers.length - 1; i >= 0; i--) {
        if (handlers[i].namespace === namespace) {
          handlers.splice(i, 1);
          handlersRemoved++;
          hooksStore.__current.forEach((hookInfo) => {
            if (hookInfo.name === hookName && hookInfo.currentIndex >= i) {
              hookInfo.currentIndex--;
            }
          });
        }
      }
    }
    if (hookName !== "hookRemoved") {
      hooks.doAction("hookRemoved", hookName, namespace);
    }
    return handlersRemoved;
  };
}
var createRemoveHook_default = createRemoveHook;

//# sourceMappingURL=createRemoveHook.mjs.map

;// ../../node_modules/.pnpm/@wordpress+hooks@4.50.0/node_modules/@wordpress/hooks/build-module/createHasHook.mjs
// packages/hooks/src/createHasHook.ts
function createHasHook(hooks, storeKey) {
  return function hasHook(hookName, namespace) {
    const hooksStore = hooks[storeKey];
    if ("undefined" !== typeof namespace) {
      return hookName in hooksStore && hooksStore[hookName].handlers.some(
        (hook) => hook.namespace === namespace
      );
    }
    return hookName in hooksStore;
  };
}
var createHasHook_default = createHasHook;

//# sourceMappingURL=createHasHook.mjs.map

;// ../../node_modules/.pnpm/@wordpress+hooks@4.50.0/node_modules/@wordpress/hooks/build-module/createRunHook.mjs
// packages/hooks/src/createRunHook.ts
function createRunHook(hooks, storeKey, returnFirstArg, async) {
  return function runHook(hookName, ...args) {
    const hooksStore = hooks[storeKey];
    if (!hooksStore[hookName]) {
      hooksStore[hookName] = {
        handlers: [],
        runs: 0
      };
    }
    hooksStore[hookName].runs++;
    const handlers = hooksStore[hookName].handlers;
    if (false) {}
    if (!handlers || !handlers.length) {
      return returnFirstArg ? args[0] : void 0;
    }
    const hookInfo = {
      name: hookName,
      currentIndex: 0
    };
    async function asyncRunner() {
      try {
        hooksStore.__current.add(hookInfo);
        let result = returnFirstArg ? args[0] : void 0;
        while (hookInfo.currentIndex < handlers.length) {
          const handler = handlers[hookInfo.currentIndex];
          result = await handler.callback.apply(null, args);
          if (returnFirstArg) {
            args[0] = result;
          }
          hookInfo.currentIndex++;
        }
        return returnFirstArg ? result : void 0;
      } finally {
        hooksStore.__current.delete(hookInfo);
      }
    }
    function syncRunner() {
      try {
        hooksStore.__current.add(hookInfo);
        let result = returnFirstArg ? args[0] : void 0;
        while (hookInfo.currentIndex < handlers.length) {
          const handler = handlers[hookInfo.currentIndex];
          result = handler.callback.apply(null, args);
          if (returnFirstArg) {
            args[0] = result;
          }
          hookInfo.currentIndex++;
        }
        return returnFirstArg ? result : void 0;
      } finally {
        hooksStore.__current.delete(hookInfo);
      }
    }
    return (async ? asyncRunner : syncRunner)();
  };
}
var createRunHook_default = createRunHook;

//# sourceMappingURL=createRunHook.mjs.map

;// ../../node_modules/.pnpm/@wordpress+hooks@4.50.0/node_modules/@wordpress/hooks/build-module/createCurrentHook.mjs
// packages/hooks/src/createCurrentHook.ts
function createCurrentHook(hooks, storeKey) {
  return function currentHook() {
    const hooksStore = hooks[storeKey];
    const currentArray = Array.from(hooksStore.__current);
    return currentArray.at(-1)?.name ?? null;
  };
}
var createCurrentHook_default = createCurrentHook;

//# sourceMappingURL=createCurrentHook.mjs.map

;// ../../node_modules/.pnpm/@wordpress+hooks@4.50.0/node_modules/@wordpress/hooks/build-module/createDoingHook.mjs
// packages/hooks/src/createDoingHook.ts
function createDoingHook(hooks, storeKey) {
  return function doingHook(hookName) {
    const hooksStore = hooks[storeKey];
    if ("undefined" === typeof hookName) {
      return hooksStore.__current.size > 0;
    }
    return Array.from(hooksStore.__current).some(
      (hook) => hook.name === hookName
    );
  };
}
var createDoingHook_default = createDoingHook;

//# sourceMappingURL=createDoingHook.mjs.map

;// ../../node_modules/.pnpm/@wordpress+hooks@4.50.0/node_modules/@wordpress/hooks/build-module/createDidHook.mjs
// packages/hooks/src/createDidHook.ts

function createDidHook(hooks, storeKey) {
  return function didHook(hookName) {
    const hooksStore = hooks[storeKey];
    if (!validateHookName_default(hookName)) {
      return;
    }
    return hooksStore[hookName] && hooksStore[hookName].runs ? hooksStore[hookName].runs : 0;
  };
}
var createDidHook_default = createDidHook;

//# sourceMappingURL=createDidHook.mjs.map

;// ../../node_modules/.pnpm/@wordpress+hooks@4.50.0/node_modules/@wordpress/hooks/build-module/createHooks.mjs
// packages/hooks/src/createHooks.ts







var _Hooks = class {
  actions;
  filters;
  addAction;
  addFilter;
  removeAction;
  removeFilter;
  hasAction;
  hasFilter;
  removeAllActions;
  removeAllFilters;
  doAction;
  doActionAsync;
  applyFilters;
  applyFiltersAsync;
  currentAction;
  currentFilter;
  doingAction;
  doingFilter;
  didAction;
  didFilter;
  constructor() {
    this.actions = /* @__PURE__ */ Object.create(null);
    this.actions.__current = /* @__PURE__ */ new Set();
    this.filters = /* @__PURE__ */ Object.create(null);
    this.filters.__current = /* @__PURE__ */ new Set();
    this.addAction = createAddHook_default(this, "actions");
    this.addFilter = createAddHook_default(this, "filters");
    this.removeAction = createRemoveHook_default(this, "actions");
    this.removeFilter = createRemoveHook_default(this, "filters");
    this.hasAction = createHasHook_default(this, "actions");
    this.hasFilter = createHasHook_default(this, "filters");
    this.removeAllActions = createRemoveHook_default(this, "actions", true);
    this.removeAllFilters = createRemoveHook_default(this, "filters", true);
    this.doAction = createRunHook_default(this, "actions", false, false);
    this.doActionAsync = createRunHook_default(this, "actions", false, true);
    this.applyFilters = createRunHook_default(this, "filters", true, false);
    this.applyFiltersAsync = createRunHook_default(this, "filters", true, true);
    this.currentAction = createCurrentHook_default(this, "actions");
    this.currentFilter = createCurrentHook_default(this, "filters");
    this.doingAction = createDoingHook_default(this, "actions");
    this.doingFilter = createDoingHook_default(this, "filters");
    this.didAction = createDidHook_default(this, "actions");
    this.didFilter = createDidHook_default(this, "filters");
  }
};
function createHooks() {
  return new _Hooks();
}
var createHooks_default = createHooks;

//# sourceMappingURL=createHooks.mjs.map

;// ../../node_modules/.pnpm/@wordpress+hooks@4.50.0/node_modules/@wordpress/hooks/build-module/index.mjs
// packages/hooks/src/index.ts

var defaultHooks = createHooks_default();
var {
  addAction,
  addFilter,
  removeAction,
  removeFilter,
  hasAction,
  hasFilter,
  removeAllActions,
  removeAllFilters,
  doAction,
  doActionAsync,
  applyFilters,
  applyFiltersAsync,
  currentAction,
  currentFilter,
  doingAction,
  doingFilter,
  didAction,
  didFilter,
  actions,
  filters
} = defaultHooks;

//# sourceMappingURL=index.mjs.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+primitives@4.48.1_react@18.3.1/node_modules/@wordpress/primitives/build-module/svg/index.mjs":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   t4: () => (/* binding */ SVG),
/* harmony export */   wA: () => (/* binding */ Path)
/* harmony export */ });
/* unused harmony exports Circle, Defs, G, Line, LinearGradient, Polygon, RadialGradient, Rect, Stop */
/* harmony import */ var clsx__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
// packages/primitives/src/svg/index.js



var Circle = (props) => createElement("circle", props);
var G = (props) => createElement("g", props);
var Line = (props) => createElement("line", props);
var Path = (props) => (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.createElement)("path", props);
var Polygon = (props) => createElement("polygon", props);
var Rect = (props) => createElement("rect", props);
var Defs = (props) => createElement("defs", props);
var RadialGradient = (props) => createElement("radialGradient", props);
var LinearGradient = (props) => createElement("linearGradient", props);
var Stop = (props) => createElement("stop", props);
var SVG = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.forwardRef)(
  /**
   * @param {SVGProps}                          props isPressed indicates whether the SVG should appear as pressed.
   *                                                  Other props will be passed through to svg component.
   * @param {React.ForwardedRef<SVGSVGElement>} ref   The forwarded ref to the SVG element.
   *
   * @return {React.JSX.Element} Stop component
   */
  ({ className, isPressed, ...props }, ref) => {
    const appliedProps = {
      ...props,
      className: (0,clsx__WEBPACK_IMPORTED_MODULE_2__/* ["default"] */ .A)(className, { "is-pressed": isPressed }) || void 0,
      "aria-hidden": true,
      focusable: false
    };
    return /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("svg", { ...appliedProps, ref });
  }
);
SVG.displayName = "SVG";

//# sourceMappingURL=index.mjs.map


/***/ }),

/***/ "../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* unused harmony export clsx */
function r(e){var t,f,n="";if("string"==typeof e||"number"==typeof e)n+=e;else if("object"==typeof e)if(Array.isArray(e)){var o=e.length;for(t=0;t<o;t++)e[t]&&(f=r(e[t]))&&(n&&(n+=" "),n+=f)}else for(f in e)e[f]&&(n&&(n+=" "),n+=f);return n}function clsx(){for(var e,t,f=0,n="",o=arguments.length;f<o;f++)(e=arguments[f])&&(t=r(e))&&(n&&(n+=" "),n+=t);return n}/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (clsx);

/***/ }),

/***/ "../../node_modules/.pnpm/use-sync-external-store@1.6.0_react@18.3.1/node_modules/use-sync-external-store/cjs/use-sync-external-store-shim.production.js":
/***/ ((__unused_webpack_module, exports, __webpack_require__) => {

/**
 * @license React
 * use-sync-external-store-shim.production.js
 *
 * Copyright (c) Meta Platforms, Inc. and affiliates.
 *
 * This source code is licensed under the MIT license found in the
 * LICENSE file in the root directory of this source tree.
 */


var React = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
function is(x, y) {
  return (x === y && (0 !== x || 1 / x === 1 / y)) || (x !== x && y !== y);
}
var objectIs = "function" === typeof Object.is ? Object.is : is,
  useState = React.useState,
  useEffect = React.useEffect,
  useLayoutEffect = React.useLayoutEffect,
  useDebugValue = React.useDebugValue;
function useSyncExternalStore$2(subscribe, getSnapshot) {
  var value = getSnapshot(),
    _useState = useState({ inst: { value: value, getSnapshot: getSnapshot } }),
    inst = _useState[0].inst,
    forceUpdate = _useState[1];
  useLayoutEffect(
    function () {
      inst.value = value;
      inst.getSnapshot = getSnapshot;
      checkIfSnapshotChanged(inst) && forceUpdate({ inst: inst });
    },
    [subscribe, value, getSnapshot]
  );
  useEffect(
    function () {
      checkIfSnapshotChanged(inst) && forceUpdate({ inst: inst });
      return subscribe(function () {
        checkIfSnapshotChanged(inst) && forceUpdate({ inst: inst });
      });
    },
    [subscribe]
  );
  useDebugValue(value);
  return value;
}
function checkIfSnapshotChanged(inst) {
  var latestGetSnapshot = inst.getSnapshot;
  inst = inst.value;
  try {
    var nextValue = latestGetSnapshot();
    return !objectIs(inst, nextValue);
  } catch (error) {
    return !0;
  }
}
function useSyncExternalStore$1(subscribe, getSnapshot) {
  return getSnapshot();
}
var shim =
  "undefined" === typeof window ||
  "undefined" === typeof window.document ||
  "undefined" === typeof window.document.createElement
    ? useSyncExternalStore$1
    : useSyncExternalStore$2;
exports.useSyncExternalStore =
  void 0 !== React.useSyncExternalStore ? React.useSyncExternalStore : shim;


/***/ }),

/***/ "../../node_modules/.pnpm/use-sync-external-store@1.6.0_react@18.3.1/node_modules/use-sync-external-store/shim/index.js":
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {



if (true) {
  module.exports = __webpack_require__("../../node_modules/.pnpm/use-sync-external-store@1.6.0_react@18.3.1/node_modules/use-sync-external-store/cjs/use-sync-external-store-shim.production.js");
} else {}


/***/ })

}]);