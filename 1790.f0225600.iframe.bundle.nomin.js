"use strict";
(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[1790],{

/***/ "../../node_modules/.pnpm/@wordpress+a11y@4.48.1/node_modules/@wordpress/a11y/build-module/index.mjs":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  L: () => (/* reexport */ speak)
});

// UNUSED EXPORTS: setup

// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+dom-ready@4.48.1/node_modules/@wordpress/dom-ready/build-module/index.mjs
var build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+dom-ready@4.48.1/node_modules/@wordpress/dom-ready/build-module/index.mjs");
;// ../../node_modules/.pnpm/@wordpress+a11y@4.48.1/node_modules/@wordpress/a11y/build-module/script/add-container.mjs
// packages/a11y/src/script/add-container.ts
function addContainer(ariaLive = "polite") {
  const container = document.createElement("div");
  container.id = `a11y-speak-${ariaLive}`;
  container.className = "a11y-speak-region";
  container.setAttribute(
    "style",
    "position:absolute;margin:-1px;padding:0;height:1px;width:1px;overflow:hidden;clip-path:inset(50%);border:0;word-wrap:normal !important;word-break:normal !important;"
  );
  container.setAttribute("aria-live", ariaLive);
  container.setAttribute("aria-relevant", "additions text");
  container.setAttribute("aria-atomic", "true");
  const { body } = document;
  if (body) {
    body.appendChild(container);
  }
  return container;
}

//# sourceMappingURL=add-container.mjs.map

// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+i18n@6.23.0/node_modules/@wordpress/i18n/build-module/index.mjs + 2 modules
var i18n_build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@6.23.0/node_modules/@wordpress/i18n/build-module/index.mjs");
;// ../../node_modules/.pnpm/@wordpress+a11y@4.48.1/node_modules/@wordpress/a11y/build-module/script/add-intro-text.mjs
// packages/a11y/src/script/add-intro-text.ts

function addIntroText() {
  const introText = document.createElement("p");
  introText.id = "a11y-speak-intro-text";
  introText.className = "a11y-speak-intro-text";
  introText.textContent = (0,i18n_build_module.__)("Notifications");
  introText.setAttribute(
    "style",
    "position:absolute;margin:-1px;padding:0;height:1px;width:1px;overflow:hidden;clip-path:inset(50%);border:0;word-wrap:normal !important;word-break:normal !important;"
  );
  introText.setAttribute("hidden", "");
  const { body } = document;
  if (body) {
    body.appendChild(introText);
  }
  return introText;
}

//# sourceMappingURL=add-intro-text.mjs.map

;// ../../node_modules/.pnpm/@wordpress+a11y@4.48.1/node_modules/@wordpress/a11y/build-module/shared/clear.mjs
// packages/a11y/src/shared/clear.ts
function clear() {
  const regions = document.getElementsByClassName("a11y-speak-region");
  const introText = document.getElementById("a11y-speak-intro-text");
  for (let i = 0; i < regions.length; i++) {
    regions[i].textContent = "";
  }
  if (introText) {
    introText.setAttribute("hidden", "hidden");
  }
}

//# sourceMappingURL=clear.mjs.map

;// ../../node_modules/.pnpm/@wordpress+a11y@4.48.1/node_modules/@wordpress/a11y/build-module/shared/filter-message.mjs
// packages/a11y/src/shared/filter-message.ts
var previousMessage = "";
function filterMessage(message) {
  message = message.replace(/<[^<>]+>/g, " ");
  if (previousMessage === message) {
    message += "\xA0";
  }
  previousMessage = message;
  return message;
}

//# sourceMappingURL=filter-message.mjs.map

;// ../../node_modules/.pnpm/@wordpress+a11y@4.48.1/node_modules/@wordpress/a11y/build-module/shared/index.mjs
// packages/a11y/src/shared/index.ts


function speak(message, ariaLive) {
  clear();
  message = filterMessage(message);
  const introText = document.getElementById("a11y-speak-intro-text");
  const containerAssertive = document.getElementById(
    "a11y-speak-assertive"
  );
  const containerPolite = document.getElementById("a11y-speak-polite");
  if (containerAssertive && ariaLive === "assertive") {
    containerAssertive.textContent = message;
  } else if (containerPolite) {
    containerPolite.textContent = message;
  }
  if (introText) {
    introText.removeAttribute("hidden");
  }
}

//# sourceMappingURL=index.mjs.map

;// ../../node_modules/.pnpm/@wordpress+a11y@4.48.1/node_modules/@wordpress/a11y/build-module/index.mjs
// packages/a11y/src/index.ts




function setup() {
  const introText = document.getElementById("a11y-speak-intro-text");
  const containerAssertive = document.getElementById(
    "a11y-speak-assertive"
  );
  const containerPolite = document.getElementById("a11y-speak-polite");
  if (introText === null) {
    addIntroText();
  }
  if (containerAssertive === null) {
    addContainer("assertive");
  }
  if (containerPolite === null) {
    addContainer("polite");
  }
}
(0,build_module/* default */.A)(setup);

//# sourceMappingURL=index.mjs.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/flex/context.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   R: () => (/* binding */ FlexContext),
/* harmony export */   a: () => (/* binding */ useFlexContext)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");

const FlexContext = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createContext)({
  flexItemDisplay: void 0
});
const useFlexContext = () => (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useContext)(FlexContext);

//# sourceMappingURL=context.js.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/flex/flex-item/component.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (/* binding */ component_default)
/* harmony export */ });
/* unused harmony export FlexItem */
/* harmony import */ var _context__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/context/context-connect.js");
/* harmony import */ var _view__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/view/component.js");
/* harmony import */ var _hook__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/flex/flex-item/hook.js");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");




function UnconnectedFlexItem(props, forwardedRef) {
  const flexItemProps = (0,_hook__WEBPACK_IMPORTED_MODULE_1__/* .useFlexItem */ .K)(props);
  return /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(_view__WEBPACK_IMPORTED_MODULE_2__/* ["default"] */ .A, {
    ...flexItemProps,
    ref: forwardedRef
  });
}
const FlexItem = (0,_context__WEBPACK_IMPORTED_MODULE_3__/* .contextConnect */ .KZ)(UnconnectedFlexItem, "FlexItem");
var component_default = FlexItem;

//# sourceMappingURL=component.js.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/flex/flex-item/hook.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   K: () => (/* binding */ useFlexItem)
/* harmony export */ });
/* harmony import */ var _emotion_react__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/@emotion+react@11.14.0_@types+react@18.3.28_react@18.3.1/node_modules/@emotion/react/dist/emotion-react.browser.esm.js");
/* harmony import */ var _context__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/context/use-context-system.js");
/* harmony import */ var _context__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/flex/context.js");
/* harmony import */ var _styles__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/flex/styles.js");
/* harmony import */ var _utils_hooks_use_cx__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/utils/hooks/use-cx.js");





function useFlexItem(props) {
  const {
    className,
    display: displayProp,
    isBlock = false,
    ...otherProps
  } = (0,_context__WEBPACK_IMPORTED_MODULE_0__/* .useContextSystem */ .A)(props, "FlexItem");
  const sx = {};
  const contextDisplay = (0,_context__WEBPACK_IMPORTED_MODULE_1__/* .useFlexContext */ .a)().flexItemDisplay;
  sx.Base = /* @__PURE__ */ (0,_emotion_react__WEBPACK_IMPORTED_MODULE_2__/* .css */ .AH)({
    display: displayProp || contextDisplay
  },  true ? "" : 0,  true ? "" : 0);
  const cx = (0,_utils_hooks_use_cx__WEBPACK_IMPORTED_MODULE_3__/* .useCx */ .l)();
  const classes = cx(_styles__WEBPACK_IMPORTED_MODULE_4__/* .Item */ .q7, sx.Base, isBlock && _styles__WEBPACK_IMPORTED_MODULE_4__/* .block */ .om, className);
  return {
    ...otherProps,
    className: classes
  };
}

//# sourceMappingURL=hook.js.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/flex/flex/hook.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  v: () => (/* binding */ useFlex)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/@emotion+react@11.14.0_@types+react@18.3.28_react@18.3.1/node_modules/@emotion/react/dist/emotion-react.browser.esm.js
var emotion_react_browser_esm = __webpack_require__("../../node_modules/.pnpm/@emotion+react@11.14.0_@types+react@18.3.28_react@18.3.1/node_modules/@emotion/react/dist/emotion-react.browser.esm.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+deprecated@4.48.1/node_modules/@wordpress/deprecated/build-module/index.mjs
var build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+deprecated@4.48.1/node_modules/@wordpress/deprecated/build-module/index.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/context/use-context-system.js + 1 modules
var use_context_system = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/context/use-context-system.js");
;// ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/utils/use-responsive-value.js

const breakpoints = ["40em", "52em", "64em"];
const useBreakpointIndex = (options = {}) => {
  const {
    defaultIndex = 0
  } = options;
  if (typeof defaultIndex !== "number") {
    throw new TypeError(`Default breakpoint index should be a number. Got: ${defaultIndex}, ${typeof defaultIndex}`);
  } else if (defaultIndex < 0 || defaultIndex > breakpoints.length - 1) {
    throw new RangeError(`Default breakpoint index out of range. Theme has ${breakpoints.length} breakpoints, got index ${defaultIndex}`);
  }
  const [value, setValue] = (0,react.useState)(defaultIndex);
  (0,react.useEffect)(() => {
    const getIndex = () => breakpoints.filter((bp) => {
      return typeof window !== "undefined" ? window.matchMedia(`screen and (min-width: ${bp})`).matches : false;
    }).length;
    const onResize = () => {
      const newValue = getIndex();
      if (value !== newValue) {
        setValue(newValue);
      }
    };
    onResize();
    if (typeof window !== "undefined") {
      window.addEventListener("resize", onResize);
    }
    return () => {
      if (typeof window !== "undefined") {
        window.removeEventListener("resize", onResize);
      }
    };
  }, [value]);
  return value;
};
function useResponsiveValue(values, options = {}) {
  const index = useBreakpointIndex(options);
  if (!Array.isArray(values) && typeof values !== "function") {
    return values;
  }
  const array = values || [];
  return (
    /** @type {T[]} */
    array[
      /* eslint-enable jsdoc/no-undefined-types */
      index >= array.length ? array.length - 1 : index
    ]
  );
}

//# sourceMappingURL=use-responsive-value.js.map

// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/utils/space.js
var space = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/utils/space.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/flex/styles.js
var styles = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/flex/styles.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/utils/hooks/use-cx.js + 2 modules
var use_cx = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/utils/hooks/use-cx.js");
;// ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/flex/flex/hook.js








function useDeprecatedProps(props) {
  const {
    isReversed,
    ...otherProps
  } = props;
  if (typeof isReversed !== "undefined") {
    (0,build_module/* default */.A)("Flex isReversed", {
      alternative: 'Flex direction="row-reverse" or "column-reverse"',
      since: "5.9"
    });
    return {
      ...otherProps,
      direction: isReversed ? "row-reverse" : "row"
    };
  }
  return otherProps;
}
function useFlex(props) {
  const {
    align,
    className,
    direction: directionProp = "row",
    expanded = true,
    gap = 2,
    justify = "space-between",
    wrap = false,
    ...otherProps
  } = (0,use_context_system/* useContextSystem */.A)(useDeprecatedProps(props), "Flex");
  const directionAsArray = Array.isArray(directionProp) ? directionProp : [directionProp];
  const direction = useResponsiveValue(directionAsArray);
  const isColumn = typeof direction === "string" && !!direction.includes("column");
  const cx = (0,use_cx/* useCx */.l)();
  const classes = (0,react.useMemo)(() => {
    const base = /* @__PURE__ */ (0,emotion_react_browser_esm/* css */.AH)({
      alignItems: align !== null && align !== void 0 ? align : isColumn ? "normal" : "center",
      flexDirection: direction,
      flexWrap: wrap ? "wrap" : void 0,
      gap: (0,space/* space */.x)(gap),
      justifyContent: justify,
      height: isColumn && expanded ? "100%" : void 0,
      width: !isColumn && expanded ? "100%" : void 0
    },  true ? "" : 0,  true ? "" : 0);
    return cx(styles/* Flex */.so, base, isColumn ? styles/* ItemsColumn */.Z2 : styles/* ItemsRow */.RI, className);
  }, [align, className, cx, direction, expanded, gap, isColumn, justify, wrap]);
  return {
    ...otherProps,
    className: classes,
    isColumn
  };
}

//# sourceMappingURL=hook.js.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/flex/styles.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   RI: () => (/* binding */ ItemsRow),
/* harmony export */   Z2: () => (/* binding */ ItemsColumn),
/* harmony export */   om: () => (/* binding */ block),
/* harmony export */   q7: () => (/* binding */ Item),
/* harmony export */   so: () => (/* binding */ Flex)
/* harmony export */ });
function _EMOTION_STRINGIFIED_CSS_ERROR__() {
  return "You have tried to stringify object returned from `css` function. It isn't supposed to be used directly (e.g. as value of the `className` prop), but rather handed to emotion so it can handle it (e.g. as value of `css` prop).";
}

const Flex =  true ? {
  name: "zjik7",
  styles: "display:flex"
} : 0;
const Item =  true ? {
  name: "qgaee5",
  styles: "display:block;max-height:100%;max-width:100%;min-height:0;min-width:0"
} : 0;
const block =  true ? {
  name: "82a6rk",
  styles: "flex:1"
} : 0;
const ItemsColumn =  true ? {
  name: "13nosa1",
  styles: ">*{min-height:0;}"
} : 0;
const ItemsRow =  true ? {
  name: "1pwxzk4",
  styles: ">*{min-width:0;}"
} : 0;

//# sourceMappingURL=styles.js.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/form-toggle/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   Ay: () => (/* binding */ form_toggle_default)
/* harmony export */ });
/* unused harmony exports FormToggle, noop */
/* harmony import */ var clsx__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");



const noop = () => {
};
function UnforwardedFormToggle(props, ref) {
  const {
    className,
    checked,
    id,
    disabled,
    onChange = noop,
    onClick,
    ...additionalProps
  } = props;
  const wrapperClasses = (0,clsx__WEBPACK_IMPORTED_MODULE_1__/* ["default"] */ .A)("components-form-toggle", className, {
    "is-checked": checked,
    "is-disabled": disabled
  });
  return /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsxs)("span", {
    className: wrapperClasses,
    children: [/* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("input", {
      className: "components-form-toggle__input",
      id,
      type: "checkbox",
      checked,
      onChange,
      disabled,
      onClick: (event) => {
        event.currentTarget.focus();
        onClick?.(event);
      },
      ...additionalProps,
      ref
    }), /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("span", {
      className: "components-form-toggle__track"
    }), /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("span", {
      className: "components-form-toggle__thumb"
    })]
  });
}
const FormToggle = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.forwardRef)(UnforwardedFormToggle);
var form_toggle_default = FormToggle;

//# sourceMappingURL=index.js.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/notice/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  A: () => (/* binding */ notice_default)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs
var clsx = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+i18n@6.21.1/node_modules/@wordpress/i18n/build-module/index.mjs + 3 modules
var build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@6.21.1/node_modules/@wordpress/i18n/build-module/index.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/is-plain-object@5.0.0/node_modules/is-plain-object/dist/is-plain-object.mjs
var is_plain_object = __webpack_require__("../../node_modules/.pnpm/is-plain-object@5.0.0/node_modules/is-plain-object/dist/is-plain-object.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/param-case@3.0.4/node_modules/param-case/dist.es2015/index.js + 1 modules
var dist_es2015 = __webpack_require__("../../node_modules/.pnpm/param-case@3.0.4/node_modules/param-case/dist.es2015/index.js");
;// ../../node_modules/.pnpm/@wordpress+escape-html@3.48.1/node_modules/@wordpress/escape-html/build-module/escape-greater.mjs
// packages/escape-html/src/escape-greater.ts
function __unstableEscapeGreaterThan(value) {
  return value.replace(/>/g, "&gt;");
}

//# sourceMappingURL=escape-greater.mjs.map

;// ../../node_modules/.pnpm/@wordpress+escape-html@3.48.1/node_modules/@wordpress/escape-html/build-module/index.mjs
// packages/escape-html/src/index.ts

var REGEXP_INVALID_ATTRIBUTE_NAME = /[\u007F-\u009F "'>/="\uFDD0-\uFDEF]/;
function escapeAmpersand(value) {
  return value.replace(/&(?!([a-z0-9]+|#[0-9]+|#x[a-f0-9]+);)/gi, "&amp;");
}
function escapeQuotationMark(value) {
  return value.replace(/"/g, "&quot;");
}
function escapeLessThan(value) {
  return value.replace(/</g, "&lt;");
}
function escapeAttribute(value) {
  return __unstableEscapeGreaterThan(
    escapeLessThan(escapeQuotationMark(escapeAmpersand(value)))
  );
}
function escapeHTML(value) {
  return escapeLessThan(escapeAmpersand(value));
}
function escapeEditableHTML(value) {
  return escapeLessThan(value.replace(/&/g, "&amp;"));
}
function isValidAttributeName(name) {
  return !REGEXP_INVALID_ATTRIBUTE_NAME.test(name);
}

//# sourceMappingURL=index.mjs.map

// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
;// ../../node_modules/.pnpm/@wordpress+element@6.45.0/node_modules/@wordpress/element/build-module/raw-html.mjs
// packages/element/src/raw-html.ts

function RawHTML({ children, ...props }) {
  let rawHtml = "";
  react.Children.toArray(children).forEach((child) => {
    if (typeof child === "string" && child.trim() !== "") {
      rawHtml += child;
    }
  });
  return (0,react.createElement)("div", {
    dangerouslySetInnerHTML: { __html: rawHtml },
    ...props
  });
}

//# sourceMappingURL=raw-html.mjs.map

;// ../../node_modules/.pnpm/@wordpress+element@6.45.0/node_modules/@wordpress/element/build-module/serialize.mjs
// packages/element/src/serialize.ts





var Context = (0,react.createContext)(void 0);
Context.displayName = "ElementContext";
var { Provider, Consumer } = Context;
var ForwardRef = (0,react.forwardRef)(() => {
  return null;
});
var ATTRIBUTES_TYPES = /* @__PURE__ */ new Set(["string", "boolean", "number"]);
var SELF_CLOSING_TAGS = /* @__PURE__ */ new Set([
  "area",
  "base",
  "br",
  "col",
  "command",
  "embed",
  "hr",
  "img",
  "input",
  "keygen",
  "link",
  "meta",
  "param",
  "source",
  "track",
  "wbr"
]);
var BOOLEAN_ATTRIBUTES = /* @__PURE__ */ new Set([
  "allowfullscreen",
  "allowpaymentrequest",
  "allowusermedia",
  "async",
  "autofocus",
  "autoplay",
  "checked",
  "controls",
  "default",
  "defer",
  "disabled",
  "download",
  "formnovalidate",
  "hidden",
  "ismap",
  "itemscope",
  "loop",
  "multiple",
  "muted",
  "nomodule",
  "novalidate",
  "open",
  "playsinline",
  "readonly",
  "required",
  "reversed",
  "selected",
  "typemustmatch"
]);
var ENUMERATED_ATTRIBUTES = /* @__PURE__ */ new Set([
  "autocapitalize",
  "autocomplete",
  "charset",
  "contenteditable",
  "crossorigin",
  "decoding",
  "dir",
  "draggable",
  "enctype",
  "formenctype",
  "formmethod",
  "http-equiv",
  "inputmode",
  "kind",
  "method",
  "preload",
  "scope",
  "shape",
  "spellcheck",
  "translate",
  "type",
  "wrap"
]);
var CSS_PROPERTIES_SUPPORTS_UNITLESS = /* @__PURE__ */ new Set([
  "animation",
  "animationIterationCount",
  "baselineShift",
  "borderImageOutset",
  "borderImageSlice",
  "borderImageWidth",
  "columnCount",
  "cx",
  "cy",
  "fillOpacity",
  "flexGrow",
  "flexShrink",
  "floodOpacity",
  "fontWeight",
  "gridColumnEnd",
  "gridColumnStart",
  "gridRowEnd",
  "gridRowStart",
  "lineHeight",
  "opacity",
  "order",
  "orphans",
  "r",
  "rx",
  "ry",
  "shapeImageThreshold",
  "stopOpacity",
  "strokeDasharray",
  "strokeDashoffset",
  "strokeMiterlimit",
  "strokeOpacity",
  "strokeWidth",
  "tabSize",
  "widows",
  "x",
  "y",
  "zIndex",
  "zoom"
]);
function hasPrefix(string, prefixes) {
  return prefixes.some((prefix) => string.indexOf(prefix) === 0);
}
function isInternalAttribute(attribute) {
  return "key" === attribute || "children" === attribute;
}
function getNormalAttributeValue(attribute, value) {
  switch (attribute) {
    case "style":
      return renderStyle(value);
  }
  return value;
}
var SVG_ATTRIBUTE_WITH_DASHES_LIST = [
  "accentHeight",
  "alignmentBaseline",
  "arabicForm",
  "baselineShift",
  "capHeight",
  "clipPath",
  "clipRule",
  "colorInterpolation",
  "colorInterpolationFilters",
  "colorProfile",
  "colorRendering",
  "dominantBaseline",
  "enableBackground",
  "fillOpacity",
  "fillRule",
  "floodColor",
  "floodOpacity",
  "fontFamily",
  "fontSize",
  "fontSizeAdjust",
  "fontStretch",
  "fontStyle",
  "fontVariant",
  "fontWeight",
  "glyphName",
  "glyphOrientationHorizontal",
  "glyphOrientationVertical",
  "horizAdvX",
  "horizOriginX",
  "imageRendering",
  "letterSpacing",
  "lightingColor",
  "markerEnd",
  "markerMid",
  "markerStart",
  "overlinePosition",
  "overlineThickness",
  "paintOrder",
  "panose1",
  "pointerEvents",
  "renderingIntent",
  "shapeRendering",
  "stopColor",
  "stopOpacity",
  "strikethroughPosition",
  "strikethroughThickness",
  "strokeDasharray",
  "strokeDashoffset",
  "strokeLinecap",
  "strokeLinejoin",
  "strokeMiterlimit",
  "strokeOpacity",
  "strokeWidth",
  "textAnchor",
  "textDecoration",
  "textRendering",
  "underlinePosition",
  "underlineThickness",
  "unicodeBidi",
  "unicodeRange",
  "unitsPerEm",
  "vAlphabetic",
  "vHanging",
  "vIdeographic",
  "vMathematical",
  "vectorEffect",
  "vertAdvY",
  "vertOriginX",
  "vertOriginY",
  "wordSpacing",
  "writingMode",
  "xmlnsXlink",
  "xHeight"
].reduce(
  (map, attribute) => {
    map[attribute.toLowerCase()] = attribute;
    return map;
  },
  {}
);
var CASE_SENSITIVE_SVG_ATTRIBUTES = [
  "allowReorder",
  "attributeName",
  "attributeType",
  "autoReverse",
  "baseFrequency",
  "baseProfile",
  "calcMode",
  "clipPathUnits",
  "contentScriptType",
  "contentStyleType",
  "diffuseConstant",
  "edgeMode",
  "externalResourcesRequired",
  "filterRes",
  "filterUnits",
  "glyphRef",
  "gradientTransform",
  "gradientUnits",
  "kernelMatrix",
  "kernelUnitLength",
  "keyPoints",
  "keySplines",
  "keyTimes",
  "lengthAdjust",
  "limitingConeAngle",
  "markerHeight",
  "markerUnits",
  "markerWidth",
  "maskContentUnits",
  "maskUnits",
  "numOctaves",
  "pathLength",
  "patternContentUnits",
  "patternTransform",
  "patternUnits",
  "pointsAtX",
  "pointsAtY",
  "pointsAtZ",
  "preserveAlpha",
  "preserveAspectRatio",
  "primitiveUnits",
  "refX",
  "refY",
  "repeatCount",
  "repeatDur",
  "requiredExtensions",
  "requiredFeatures",
  "specularConstant",
  "specularExponent",
  "spreadMethod",
  "startOffset",
  "stdDeviation",
  "stitchTiles",
  "suppressContentEditableWarning",
  "suppressHydrationWarning",
  "surfaceScale",
  "systemLanguage",
  "tableValues",
  "targetX",
  "targetY",
  "textLength",
  "viewBox",
  "viewTarget",
  "xChannelSelector",
  "yChannelSelector"
].reduce(
  (map, attribute) => {
    map[attribute.toLowerCase()] = attribute;
    return map;
  },
  {}
);
var SVG_ATTRIBUTES_WITH_COLONS = [
  "xlink:actuate",
  "xlink:arcrole",
  "xlink:href",
  "xlink:role",
  "xlink:show",
  "xlink:title",
  "xlink:type",
  "xml:base",
  "xml:lang",
  "xml:space",
  "xmlns:xlink"
].reduce(
  (map, attribute) => {
    map[attribute.replace(":", "").toLowerCase()] = attribute;
    return map;
  },
  {}
);
function getNormalAttributeName(attribute) {
  switch (attribute) {
    case "htmlFor":
      return "for";
    case "className":
      return "class";
  }
  const attributeLowerCase = attribute.toLowerCase();
  if (CASE_SENSITIVE_SVG_ATTRIBUTES[attributeLowerCase]) {
    return CASE_SENSITIVE_SVG_ATTRIBUTES[attributeLowerCase];
  } else if (SVG_ATTRIBUTE_WITH_DASHES_LIST[attributeLowerCase]) {
    return (0,dist_es2015/* paramCase */.c)(
      SVG_ATTRIBUTE_WITH_DASHES_LIST[attributeLowerCase]
    );
  } else if (SVG_ATTRIBUTES_WITH_COLONS[attributeLowerCase]) {
    return SVG_ATTRIBUTES_WITH_COLONS[attributeLowerCase];
  }
  return attributeLowerCase;
}
function getNormalStylePropertyName(property) {
  if (property.startsWith("--")) {
    return property;
  }
  if (hasPrefix(property, ["ms", "O", "Moz", "Webkit"])) {
    return "-" + (0,dist_es2015/* paramCase */.c)(property);
  }
  return (0,dist_es2015/* paramCase */.c)(property);
}
function getNormalStylePropertyValue(property, value) {
  if (typeof value === "number" && 0 !== value && !hasPrefix(property, ["--"]) && !CSS_PROPERTIES_SUPPORTS_UNITLESS.has(property)) {
    return value + "px";
  }
  return value;
}
function renderElement(element, context, legacyContext = {}) {
  if (null === element || void 0 === element || false === element) {
    return "";
  }
  if (Array.isArray(element)) {
    return renderChildren(element, context, legacyContext);
  }
  switch (typeof element) {
    case "string":
      return escapeHTML(element);
    case "number":
      return element.toString();
  }
  const { type, props } = element;
  switch (type) {
    case react.StrictMode:
    case react.Fragment:
      return renderChildren(props.children, context, legacyContext);
    case RawHTML:
      const { children, ...wrapperProps } = props;
      return renderNativeComponent(
        !Object.keys(wrapperProps).length ? null : "div",
        {
          ...wrapperProps,
          dangerouslySetInnerHTML: { __html: children }
        },
        context,
        legacyContext
      );
  }
  switch (typeof type) {
    case "string":
      return renderNativeComponent(type, props, context, legacyContext);
    case "function":
      if (type.prototype && typeof type.prototype.render === "function") {
        return renderComponent(type, props, context, legacyContext);
      }
      return renderElement(
        type(props, legacyContext),
        context,
        legacyContext
      );
  }
  switch (type && type.$$typeof) {
    case Provider.$$typeof:
      return renderChildren(props.children, props.value, legacyContext);
    case Consumer.$$typeof:
      return renderElement(
        props.children(context || type._currentValue),
        context,
        legacyContext
      );
    case ForwardRef.$$typeof:
      return renderElement(
        type.render(props),
        context,
        legacyContext
      );
  }
  return "";
}
function renderNativeComponent(type, props, context, legacyContext = {}) {
  let content = "";
  if (type === "textarea" && props.hasOwnProperty("value")) {
    content = renderChildren(props.value, context, legacyContext);
    const { value, ...restProps } = props;
    props = restProps;
  } else if (props.dangerouslySetInnerHTML && typeof props.dangerouslySetInnerHTML.__html === "string") {
    content = props.dangerouslySetInnerHTML.__html;
  } else if (typeof props.children !== "undefined") {
    content = renderChildren(props.children, context, legacyContext);
  }
  if (!type) {
    return content;
  }
  const attributes = renderAttributes(props);
  if (SELF_CLOSING_TAGS.has(type)) {
    return "<" + type + attributes + "/>";
  }
  return "<" + type + attributes + ">" + content + "</" + type + ">";
}
function renderComponent(Component, props, context, legacyContext = {}) {
  const instance = new Component(props, legacyContext);
  if (typeof instance.getChildContext === "function") {
    Object.assign(legacyContext, instance.getChildContext());
  }
  const html = renderElement(instance.render(), context, legacyContext);
  return html;
}
function renderChildren(children, context, legacyContext = {}) {
  let result = "";
  const childrenArray = Array.isArray(children) ? children : [children];
  for (let i = 0; i < childrenArray.length; i++) {
    const child = childrenArray[i];
    result += renderElement(child, context, legacyContext);
  }
  return result;
}
function renderAttributes(props) {
  let result = "";
  for (const key in props) {
    const attribute = getNormalAttributeName(key);
    if (!isValidAttributeName(attribute)) {
      continue;
    }
    let value = getNormalAttributeValue(key, props[key]);
    if (!ATTRIBUTES_TYPES.has(typeof value)) {
      continue;
    }
    if (isInternalAttribute(key)) {
      continue;
    }
    const isBooleanAttribute = BOOLEAN_ATTRIBUTES.has(attribute);
    if (isBooleanAttribute && value === false) {
      continue;
    }
    const isMeaningfulAttribute = isBooleanAttribute || hasPrefix(key, ["data-", "aria-"]) || ENUMERATED_ATTRIBUTES.has(attribute);
    if (typeof value === "boolean" && !isMeaningfulAttribute) {
      continue;
    }
    result += " " + attribute;
    if (isBooleanAttribute) {
      continue;
    }
    if (typeof value === "string") {
      value = escapeAttribute(value);
    }
    result += '="' + value + '"';
  }
  return result;
}
function renderStyle(style) {
  if (!(0,is_plain_object/* isPlainObject */.Q)(style)) {
    return style;
  }
  let result;
  const styleObj = style;
  for (const property in styleObj) {
    const value = styleObj[property];
    if (null === value || void 0 === value) {
      continue;
    }
    if (result) {
      result += ";";
    } else {
      result = "";
    }
    const normalName = getNormalStylePropertyName(property);
    const normalValue = getNormalStylePropertyValue(property, value);
    result += normalName + ":" + normalValue;
  }
  return result;
}
var serialize_default = renderElement;

//# sourceMappingURL=serialize.mjs.map

// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+a11y@4.48.1/node_modules/@wordpress/a11y/build-module/index.mjs + 5 modules
var a11y_build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+a11y@4.48.1/node_modules/@wordpress/a11y/build-module/index.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+icons@11.8.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/icons/build-module/library/close.mjs
var library_close = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@11.8.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/icons/build-module/library/close.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/button/index.js
var build_module_button = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/button/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/visually-hidden/component.js + 1 modules
var component = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/visually-hidden/component.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js
var jsx_runtime = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
;// ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/notice/index.js








const noop = () => {
};
function useSpokenMessage(message, politeness) {
  const spokenMessage = typeof message === "string" ? message : serialize_default(message);
  (0,react.useEffect)(() => {
    if (spokenMessage) {
      (0,a11y_build_module/* speak */.L)(spokenMessage, politeness);
    }
  }, [spokenMessage, politeness]);
}
function getDefaultPoliteness(status) {
  switch (status) {
    case "success":
    case "warning":
    case "info":
      return "polite";
    // The default will also catch the 'error' status.
    default:
      return "assertive";
  }
}
function getStatusLabel(status) {
  switch (status) {
    case "warning":
      return (0,build_module.__)("Warning notice");
    case "info":
      return (0,build_module.__)("Information notice");
    case "error":
      return (0,build_module.__)("Error notice");
    // The default will also catch the 'success' status.
    default:
      return (0,build_module.__)("Notice");
  }
}
function Notice({
  className,
  status = "info",
  children,
  spokenMessage = children,
  onRemove = noop,
  isDismissible = true,
  actions = [],
  politeness = getDefaultPoliteness(status),
  __unstableHTML,
  // onDismiss is a callback executed when the notice is dismissed.
  // It is distinct from onRemove, which _looks_ like a callback but is
  // actually the function to call to remove the notice from the UI.
  onDismiss = noop
}) {
  useSpokenMessage(spokenMessage, politeness);
  const classes = (0,clsx/* default */.A)(className, "components-notice", "is-" + status, {
    "is-dismissible": isDismissible
  });
  if (__unstableHTML && typeof children === "string") {
    children = /* @__PURE__ */ (0,jsx_runtime.jsx)(RawHTML, {
      children
    });
  }
  const onDismissNotice = () => {
    onDismiss();
    onRemove();
  };
  return /* @__PURE__ */ (0,jsx_runtime.jsxs)("div", {
    className: classes,
    children: [/* @__PURE__ */ (0,jsx_runtime.jsx)(component/* default */.A, {
      children: getStatusLabel(status)
    }), /* @__PURE__ */ (0,jsx_runtime.jsxs)("div", {
      className: "components-notice__content",
      children: [children, /* @__PURE__ */ (0,jsx_runtime.jsx)("div", {
        className: "components-notice__actions",
        children: actions.map(({
          className: buttonCustomClasses,
          label,
          isPrimary,
          variant,
          noDefaultClasses = false,
          onClick,
          url
        }, index) => {
          let computedVariant = variant;
          if (variant !== "primary" && !noDefaultClasses) {
            computedVariant = !url ? "secondary" : "link";
          }
          if (typeof computedVariant === "undefined" && isPrimary) {
            computedVariant = "primary";
          }
          return /* @__PURE__ */ (0,jsx_runtime.jsx)(build_module_button/* default */.Ay, {
            __next40pxDefaultSize: true,
            href: url,
            variant: computedVariant,
            onClick: url ? void 0 : onClick,
            className: (0,clsx/* default */.A)("components-notice__action", buttonCustomClasses),
            children: label
          }, index);
        })
      })]
    }), isDismissible && /* @__PURE__ */ (0,jsx_runtime.jsx)(build_module_button/* default */.Ay, {
      size: "small",
      className: "components-notice__dismiss",
      icon: library_close/* default */.A,
      label: (0,build_module.__)("Close"),
      onClick: onDismissNotice
    })]
  });
}
var notice_default = Notice;

//# sourceMappingURL=index.js.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+compose@7.33.1_react@18.3.1/node_modules/@wordpress/compose/build-module/higher-order/with-instance-id/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  A: () => (/* binding */ with_instance_id_default)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js
var jsx_runtime = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/pascal-case@3.1.2/node_modules/pascal-case/dist.es2015/index.js
var dist_es2015 = __webpack_require__("../../node_modules/.pnpm/pascal-case@3.1.2/node_modules/pascal-case/dist.es2015/index.js");
;// ../../node_modules/.pnpm/@wordpress+compose@7.33.1_react@18.3.1/node_modules/@wordpress/compose/build-module/utils/create-higher-order-component/index.js

function createHigherOrderComponent(mapComponent, modifierName) {
  return (Inner) => {
    const Outer = mapComponent(Inner);
    Outer.displayName = hocName(modifierName, Inner);
    return Outer;
  };
}
const hocName = (name, Inner) => {
  const inner = Inner.displayName || Inner.name || "Component";
  const outer = (0,dist_es2015/* pascalCase */.fL)(name ?? "");
  return `${outer}(${inner})`;
};

//# sourceMappingURL=index.js.map

// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+compose@7.33.1_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-instance-id/index.js
var use_instance_id = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@7.33.1_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-instance-id/index.js");
;// ../../node_modules/.pnpm/@wordpress+compose@7.33.1_react@18.3.1/node_modules/@wordpress/compose/build-module/higher-order/with-instance-id/index.js



const withInstanceId = createHigherOrderComponent(
  (WrappedComponent) => {
    return (props) => {
      const instanceId = (0,use_instance_id/* default */.A)(WrappedComponent);
      return /* @__PURE__ */ (0,jsx_runtime.jsx)(WrappedComponent, { ...props, instanceId });
    };
  },
  "instanceId"
);
var with_instance_id_default = withInstanceId;

//# sourceMappingURL=index.js.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+compose@7.33.1_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-instance-id/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (/* binding */ use_instance_id_default)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");

const instanceMap = /* @__PURE__ */ new WeakMap();
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

//# sourceMappingURL=index.js.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+deprecated@4.33.1/node_modules/@wordpress/deprecated/build-module/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (/* binding */ deprecated)
/* harmony export */ });
/* unused harmony export logged */
/* harmony import */ var _wordpress_hooks__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+hooks@4.48.1/node_modules/@wordpress/hooks/build-module/index.mjs");

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
  (0,_wordpress_hooks__WEBPACK_IMPORTED_MODULE_0__/* .doAction */ .Eo)("deprecated", feature, options, message);
  console.warn(message);
  logged[message] = true;
}

//# sourceMappingURL=index.js.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+dom-ready@4.48.1/node_modules/@wordpress/dom-ready/build-module/index.mjs":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (/* binding */ domReady)
/* harmony export */ });
// packages/dom-ready/src/index.ts
function domReady(callback) {
  if (typeof document === "undefined") {
    return;
  }
  if (document.readyState === "complete" || // DOMContentLoaded + Images/Styles/etc loaded, so we call directly.
  document.readyState === "interactive") {
    return void callback();
  }
  document.addEventListener("DOMContentLoaded", callback);
}

//# sourceMappingURL=index.mjs.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/library/chevron-down.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (/* binding */ chevron_down_default)
/* harmony export */ });
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
/* harmony import */ var _wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+primitives@4.48.1_react@18.3.1/node_modules/@wordpress/primitives/build-module/svg/index.mjs");


var chevron_down_default = /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(_wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__/* .SVG */ .t4, { viewBox: "0 0 24 24", xmlns: "http://www.w3.org/2000/svg", children: /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(_wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__/* .Path */ .wA, { d: "M17.5 11.6L12 16l-5.5-4.4.9-1.2L12 14l4.5-3.6 1 1.2z" }) });

//# sourceMappingURL=chevron-down.js.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/library/chevron-left.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (/* binding */ chevron_left_default)
/* harmony export */ });
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
/* harmony import */ var _wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+primitives@4.48.1_react@18.3.1/node_modules/@wordpress/primitives/build-module/svg/index.mjs");


var chevron_left_default = /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(_wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__/* .SVG */ .t4, { xmlns: "http://www.w3.org/2000/svg", viewBox: "0 0 24 24", children: /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(_wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__/* .Path */ .wA, { d: "M14.6 7l-1.2-1L8 12l5.4 6 1.2-1-4.6-5z" }) });

//# sourceMappingURL=chevron-left.js.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/library/chevron-right.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (/* binding */ chevron_right_default)
/* harmony export */ });
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
/* harmony import */ var _wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+primitives@4.48.1_react@18.3.1/node_modules/@wordpress/primitives/build-module/svg/index.mjs");


var chevron_right_default = /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(_wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__/* .SVG */ .t4, { xmlns: "http://www.w3.org/2000/svg", viewBox: "0 0 24 24", children: /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(_wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__/* .Path */ .wA, { d: "M10.6 6L9.4 7l4.6 5-4.6 5 1.2 1 5.4-6z" }) });

//# sourceMappingURL=chevron-right.js.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/library/chevron-up.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (/* binding */ chevron_up_default)
/* harmony export */ });
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
/* harmony import */ var _wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+primitives@4.48.1_react@18.3.1/node_modules/@wordpress/primitives/build-module/svg/index.mjs");


var chevron_up_default = /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(_wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__/* .SVG */ .t4, { viewBox: "0 0 24 24", xmlns: "http://www.w3.org/2000/svg", children: /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(_wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__/* .Path */ .wA, { d: "M6.5 12.4L12 8l5.5 4.4-.9 1.2L12 10l-4.5 3.6-1-1.2z" }) });

//# sourceMappingURL=chevron-up.js.map


/***/ }),

/***/ "../../node_modules/.pnpm/pascal-case@3.1.2/node_modules/pascal-case/dist.es2015/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   fL: () => (/* binding */ pascalCase)
/* harmony export */ });
/* unused harmony exports pascalCaseTransform, pascalCaseTransformMerge */
/* harmony import */ var tslib__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/tslib@2.8.1/node_modules/tslib/tslib.es6.mjs");
/* harmony import */ var no_case__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/no-case@3.0.4/node_modules/no-case/dist.es2015/index.js");


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
    return (0,no_case__WEBPACK_IMPORTED_MODULE_0__/* .noCase */ .W)(input, (0,tslib__WEBPACK_IMPORTED_MODULE_1__/* .__assign */ .Cl)({ delimiter: "", transform: pascalCaseTransform }, options));
}
//# sourceMappingURL=index.js.map

/***/ })

}]);