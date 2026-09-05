"use strict";
(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[5452],{

/***/ "../../packages/js/components/src/pagination/stories/pagination.story.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

// ESM COMPAT FLAG
__webpack_require__.r(__webpack_exports__);

// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  CustomWithHook: () => (/* binding */ CustomWithHook),
  Default: () => (/* binding */ Default),
  "default": () => (/* binding */ pagination_story)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../packages/js/components/src/pagination/pagination.tsx + 2 modules
var pagination = __webpack_require__("../../packages/js/components/src/pagination/pagination.tsx");
;// ../../packages/js/components/src/pagination/use-pagination.ts
/**
 * External dependencies
 */

function usePagination({
  totalCount,
  defaultPerPage = 25,
  onPageChange,
  onPerPageChange
}) {
  const [currentPage, setCurrentPage] = (0,react.useState)(1);
  const [perPage, setPerPage] = (0,react.useState)(defaultPerPage);
  const pageCount = Math.ceil(totalCount / perPage);
  const start = perPage * (currentPage - 1) + 1;
  const end = Math.min(perPage * currentPage, totalCount);
  return {
    start,
    end,
    currentPage,
    perPage,
    pageCount,
    setCurrentPage: newPage => {
      setCurrentPage(newPage);
      if (onPageChange) {
        onPageChange(newPage);
      }
    },
    setPerPageChange: newPerPage => {
      setCurrentPage(1);
      setPerPage(newPerPage);
      if (onPerPageChange) {
        onPerPageChange(newPerPage);
      }
    }
  };
}
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/button/index.js
var build_module_button = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/button/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/library/chevron-right.js
var chevron_right = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/library/chevron-right.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/library/chevron-left.js
var chevron_left = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/library/chevron-left.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+i18n@6.6.1/node_modules/@wordpress/i18n/build-module/index.js + 3 modules
var build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@6.6.1/node_modules/@wordpress/i18n/build-module/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs
var clsx = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/lodash@4.18.1/node_modules/lodash/lodash.js
var lodash = __webpack_require__("../../node_modules/.pnpm/lodash@4.18.1/node_modules/lodash/lodash.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js
var jsx_runtime = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
;// ../../packages/js/components/src/pagination/page-arrows-with-picker.tsx
/**
 * External dependencies
 */







function PageArrowsWithPicker({
  pageCount,
  currentPage,
  setCurrentPage
}) {
  const [inputValue, setInputValue] = (0,react.useState)(currentPage);
  (0,react.useEffect)(() => {
    if (currentPage !== inputValue) {
      setInputValue(currentPage);
    }
  }, [currentPage]);
  function onInputChange(event) {
    setInputValue(parseInt(event.currentTarget.value, 10));
  }
  function onInputBlur(event) {
    const newPage = parseInt(event.target.value, 10);
    if (newPage !== currentPage && Number.isFinite(newPage) && newPage > 0 && pageCount && pageCount >= newPage) {
      setCurrentPage(newPage, 'goto');
    } else {
      setInputValue(currentPage);
    }
  }
  function previousPage(event) {
    event.stopPropagation();
    if (currentPage - 1 < 1) {
      return;
    }
    setInputValue(currentPage - 1);
    setCurrentPage(currentPage - 1, 'previous');
  }
  function nextPage(event) {
    event.stopPropagation();
    if (currentPage + 1 > pageCount) {
      return;
    }
    setInputValue(currentPage + 1);
    setCurrentPage(currentPage + 1, 'next');
  }
  if (pageCount <= 1) {
    return null;
  }
  const previousLinkClass = (0,clsx/* default */.A)('woocommerce-pagination__link', {
    'is-active': currentPage > 1
  });
  const nextLinkClass = (0,clsx/* default */.A)('woocommerce-pagination__link', {
    'is-active': currentPage < pageCount
  });
  const isError = currentPage < 1 || currentPage > pageCount;
  const inputClass = (0,clsx/* default */.A)('woocommerce-pagination__page-arrow-picker-input', {
    'has-error': isError
  });
  const instanceId = (0,lodash.uniqueId)('woocommerce-pagination-page-picker-');
  return /*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
    className: "woocommerce-pagination__page-arrows",
    children: [/*#__PURE__*/(0,jsx_runtime.jsx)(build_module_button/* default */.Ay, {
      className: previousLinkClass,
      icon: (0,build_module/* isRTL */.V8)() ? chevron_right/* default */.A : chevron_left/* default */.A,
      disabled: !(currentPage > 1),
      onClick: previousPage,
      label: (0,build_module.__)('Previous Page', 'woocommerce')
    }), /*#__PURE__*/(0,jsx_runtime.jsx)("input", {
      id: instanceId,
      className: inputClass,
      "aria-invalid": isError,
      type: "number",
      onChange: onInputChange,
      onBlur: onInputBlur,
      value: inputValue,
      min: 1,
      max: pageCount
    }), (0,build_module/* sprintf */.nv)(/* translators: %d: total number of pages */
    (0,build_module.__)('of %d', 'woocommerce'), pageCount), /*#__PURE__*/(0,jsx_runtime.jsx)(build_module_button/* default */.Ay, {
      className: nextLinkClass,
      icon: (0,build_module/* isRTL */.V8)() ? chevron_left/* default */.A : chevron_right/* default */.A,
      disabled: !(currentPage < pageCount),
      onClick: nextPage,
      label: (0,build_module.__)('Next Page', 'woocommerce')
    })]
  });
}
try {
    // @ts-ignore
    PageArrowsWithPicker.displayName = "PageArrowsWithPicker";
    // @ts-ignore
    PageArrowsWithPicker.__docgenInfo = { "description": "", "displayName": "PageArrowsWithPicker", "props": { "currentPage": { "defaultValue": null, "description": "", "name": "currentPage", "required": true, "type": { "name": "number" } }, "pageCount": { "defaultValue": null, "description": "", "name": "pageCount", "required": true, "type": { "name": "number" } }, "setCurrentPage": { "defaultValue": null, "description": "", "name": "setCurrentPage", "required": true, "type": { "name": "(page: number, action?: \"next\" | \"previous\" | \"goto\" | undefined) => void" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/pagination/page-arrows-with-picker.tsx#PageArrowsWithPicker"] = { docgenInfo: PageArrowsWithPicker.__docgenInfo, name: "PageArrowsWithPicker", path: "../../packages/js/components/src/pagination/page-arrows-with-picker.tsx#PageArrowsWithPicker" };
}
catch (__react_docgen_typescript_loader_error) { }
// EXTERNAL MODULE: ../../packages/js/components/src/pagination/page-size-picker.tsx
var page_size_picker = __webpack_require__("../../packages/js/components/src/pagination/page-size-picker.tsx");
;// ../../packages/js/components/src/pagination/stories/pagination.story.js
/**
 * External dependencies
 */



/**
 * Internal dependencies
 */


/* harmony default export */ const pagination_story = ({
  title: 'Components/Pagination',
  component: pagination/* Pagination */.d,
  args: {
    total: 500,
    showPagePicker: true,
    showPerPagePicker: true,
    showPageArrowsLabel: true
  },
  argTypes: {
    onPageChange: {
      action: 'onPageChange'
    },
    onPerPageChange: {
      action: 'onPerPageChange'
    }
  }
});
const Default = args => {
  const [statePage, setPage] = (0,react.useState)(2);
  const [statePerPage, setPerPage] = (0,react.useState)(50);
  return /*#__PURE__*/(0,jsx_runtime.jsx)(pagination/* Pagination */.d, {
    page: statePage,
    perPage: statePerPage,
    onPageChange: newPage => setPage(newPage),
    onPerPageChange: newPerPage => setPerPage(newPerPage),
    ...args
  });
};
const CustomWithHook = args => {
  const paginationProps = usePagination({
    totalCount: args.total,
    defaultPerPage: 25,
    onPageChange: args.onPageChange,
    onPerPageChange: args.onPerPageChange
  });
  return /*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
    children: [/*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
      children: ["Viewing ", paginationProps.start, "-", paginationProps.end, " of", ' ', args.total, " items"]
    }), /*#__PURE__*/(0,jsx_runtime.jsx)(PageArrowsWithPicker, {
      ...paginationProps
    }), /*#__PURE__*/(0,jsx_runtime.jsx)(page_size_picker/* PageSizePicker */.$, {
      ...paginationProps,
      total: args.total,
      perPageOptions: [5, 10, 25],
      label: ""
    })]
  });
};
Default.parameters = {
  ...Default.parameters,
  docs: {
    ...Default.parameters?.docs,
    source: {
      originalSource: "args => {\n  const [statePage, setPage] = useState(2);\n  const [statePerPage, setPerPage] = useState(50);\n  return <Pagination page={statePage} perPage={statePerPage} onPageChange={newPage => setPage(newPage)} onPerPageChange={newPerPage => setPerPage(newPerPage)} {...args} />;\n}",
      ...Default.parameters?.docs?.source
    }
  }
};
CustomWithHook.parameters = {
  ...CustomWithHook.parameters,
  docs: {
    ...CustomWithHook.parameters?.docs,
    source: {
      originalSource: "args => {\n  const paginationProps = usePagination({\n    totalCount: args.total,\n    defaultPerPage: 25,\n    onPageChange: args.onPageChange,\n    onPerPageChange: args.onPerPageChange\n  });\n  return <div>\n            <div>\n                Viewing {paginationProps.start}-{paginationProps.end} of{' '}\n                {args.total} items\n            </div>\n            <PaginationPageArrowsWithPicker {...paginationProps} />\n            <PaginationPageSizePicker {...paginationProps} total={args.total} perPageOptions={[5, 10, 25]} label=\"\" />\n        </div>;\n}",
      ...CustomWithHook.parameters?.docs?.source
    }
  }
};

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

/***/ "../../node_modules/.pnpm/@wordpress+i18n@6.21.1/node_modules/@wordpress/i18n/build-module/index.mjs":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  __: () => (/* reexport */ __),
  _n: () => (/* reexport */ _n),
  V8: () => (/* reexport */ isRTL),
  nv: () => (/* reexport */ sprintf)
});

// UNUSED EXPORTS: _nx, _x, createI18n, defaultI18n, getLocaleData, hasTranslation, resetLocaleData, setLocaleData, subscribe

// EXTERNAL MODULE: ../../node_modules/.pnpm/@tannin+sprintf@1.3.3/node_modules/@tannin/sprintf/src/index.js
var src = __webpack_require__("../../node_modules/.pnpm/@tannin+sprintf@1.3.3/node_modules/@tannin/sprintf/src/index.js");
;// ../../node_modules/.pnpm/@wordpress+i18n@6.21.1/node_modules/@wordpress/i18n/build-module/sprintf.mjs
// packages/i18n/src/sprintf.ts

function sprintf(format, ...args) {
  return (0,src/* default */.A)(format, ...args);
}

//# sourceMappingURL=sprintf.mjs.map

// EXTERNAL MODULE: ../../node_modules/.pnpm/tannin@1.2.0/node_modules/tannin/index.js + 4 modules
var node_modules_tannin = __webpack_require__("../../node_modules/.pnpm/tannin@1.2.0/node_modules/tannin/index.js");
;// ../../node_modules/.pnpm/@wordpress+i18n@6.21.1/node_modules/@wordpress/i18n/build-module/create-i18n.mjs
// packages/i18n/src/create-i18n.ts

var DEFAULT_LOCALE_DATA = {
  "": {
    plural_forms(n) {
      return n === 1 ? 0 : 1;
    }
  }
};
var I18N_HOOK_REGEXP = /^i18n\.(n?gettext|has_translation)(_|$)/;
var createI18n = (initialData, initialDomain, hooks) => {
  const tannin = new node_modules_tannin/* default */.A({});
  const listeners = /* @__PURE__ */ new Set();
  const notifyListeners = () => {
    listeners.forEach((listener) => listener());
  };
  const subscribe = (callback) => {
    listeners.add(callback);
    return () => listeners.delete(callback);
  };
  const getLocaleData = (domain = "default") => tannin.data[domain];
  const doSetLocaleData = (data, domain = "default") => {
    tannin.data[domain] = {
      ...tannin.data[domain],
      ...data
    };
    tannin.data[domain][""] = {
      ...DEFAULT_LOCALE_DATA[""],
      ...tannin.data[domain]?.[""]
    };
    delete tannin.pluralForms[domain];
  };
  const setLocaleData = (data, domain) => {
    doSetLocaleData(data, domain);
    notifyListeners();
  };
  const addLocaleData = (data, domain = "default") => {
    tannin.data[domain] = {
      ...tannin.data[domain],
      ...data,
      // Populate default domain configuration (supported locale date which omits
      // a plural forms expression).
      "": {
        ...DEFAULT_LOCALE_DATA[""],
        ...tannin.data[domain]?.[""],
        ...data?.[""]
      }
    };
    delete tannin.pluralForms[domain];
    notifyListeners();
  };
  const resetLocaleData = (data, domain) => {
    tannin.data = {};
    tannin.pluralForms = {};
    setLocaleData(data, domain);
  };
  const dcnpgettext = (domain = "default", context, single, plural, number) => {
    if (!tannin.data[domain]) {
      doSetLocaleData(void 0, domain);
    }
    return tannin.dcnpgettext(domain, context, single, plural, number);
  };
  const getFilterDomain = (domain) => domain || "default";
  const __ = (text, domain) => {
    let translation = dcnpgettext(domain, void 0, text);
    if (!hooks) {
      return translation;
    }
    translation = hooks.applyFilters(
      "i18n.gettext",
      translation,
      text,
      domain
    );
    return hooks.applyFilters(
      "i18n.gettext_" + getFilterDomain(domain),
      translation,
      text,
      domain
    );
  };
  const _x = (text, context, domain) => {
    let translation = dcnpgettext(domain, context, text);
    if (!hooks) {
      return translation;
    }
    translation = hooks.applyFilters(
      "i18n.gettext_with_context",
      translation,
      text,
      context,
      domain
    );
    return hooks.applyFilters(
      "i18n.gettext_with_context_" + getFilterDomain(domain),
      translation,
      text,
      context,
      domain
    );
  };
  const _n = (single, plural, number, domain) => {
    let translation = dcnpgettext(
      domain,
      void 0,
      single,
      plural,
      number
    );
    if (!hooks) {
      return translation;
    }
    translation = hooks.applyFilters(
      "i18n.ngettext",
      translation,
      single,
      plural,
      number,
      domain
    );
    return hooks.applyFilters(
      "i18n.ngettext_" + getFilterDomain(domain),
      translation,
      single,
      plural,
      number,
      domain
    );
  };
  const _nx = (single, plural, number, context, domain) => {
    let translation = dcnpgettext(
      domain,
      context,
      single,
      plural,
      number
    );
    if (!hooks) {
      return translation;
    }
    translation = hooks.applyFilters(
      "i18n.ngettext_with_context",
      translation,
      single,
      plural,
      number,
      context,
      domain
    );
    return hooks.applyFilters(
      "i18n.ngettext_with_context_" + getFilterDomain(domain),
      translation,
      single,
      plural,
      number,
      context,
      domain
    );
  };
  const isRTL = () => {
    return "rtl" === _x("ltr", "text direction");
  };
  const hasTranslation = (single, context, domain) => {
    const key = context ? context + "" + single : single;
    let result = !!tannin.data?.[domain ?? "default"]?.[key];
    if (hooks) {
      result = hooks.applyFilters(
        "i18n.has_translation",
        result,
        single,
        context,
        domain
      );
      result = hooks.applyFilters(
        "i18n.has_translation_" + getFilterDomain(domain),
        result,
        single,
        context,
        domain
      );
    }
    return result;
  };
  if (initialData) {
    setLocaleData(initialData, initialDomain);
  }
  if (hooks) {
    const onHookAddedOrRemoved = (hookName) => {
      if (I18N_HOOK_REGEXP.test(hookName)) {
        notifyListeners();
      }
    };
    hooks.addAction("hookAdded", "core/i18n", onHookAddedOrRemoved);
    hooks.addAction("hookRemoved", "core/i18n", onHookAddedOrRemoved);
  }
  return {
    getLocaleData,
    setLocaleData,
    addLocaleData,
    resetLocaleData,
    subscribe,
    __,
    _x,
    _n,
    _nx,
    isRTL,
    hasTranslation
  };
};

//# sourceMappingURL=create-i18n.mjs.map

// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+hooks@4.50.0/node_modules/@wordpress/hooks/build-module/index.mjs + 10 modules
var build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+hooks@4.50.0/node_modules/@wordpress/hooks/build-module/index.mjs");
;// ../../node_modules/.pnpm/@wordpress+i18n@6.21.1/node_modules/@wordpress/i18n/build-module/default-i18n.mjs
// packages/i18n/src/default-i18n.ts


var i18n = createI18n(void 0, void 0, build_module/* defaultHooks */.se);
var default_i18n_default = (/* unused pure expression or super */ null && (i18n));
var getLocaleData = i18n.getLocaleData.bind(i18n);
var setLocaleData = i18n.setLocaleData.bind(i18n);
var resetLocaleData = i18n.resetLocaleData.bind(i18n);
var subscribe = i18n.subscribe.bind(i18n);
var __ = i18n.__.bind(i18n);
var _x = i18n._x.bind(i18n);
var _n = i18n._n.bind(i18n);
var _nx = i18n._nx.bind(i18n);
var isRTL = i18n.isRTL.bind(i18n);
var hasTranslation = i18n.hasTranslation.bind(i18n);

//# sourceMappingURL=default-i18n.mjs.map

;// ../../node_modules/.pnpm/@wordpress+i18n@6.21.1/node_modules/@wordpress/i18n/build-module/index.mjs
// packages/i18n/src/index.ts




//# sourceMappingURL=index.mjs.map


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

/***/ "../../node_modules/.pnpm/@wordpress+primitives@4.50._58b142b34ba9966bc817120019190c93/node_modules/@wordpress/primitives/build-module/svg/index.mjs":
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

/***/ "../../packages/js/components/src/pagination/page-size-picker.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   $: () => (/* binding */ PageSizePicker),
/* harmony export */   v: () => (/* binding */ DEFAULT_PER_PAGE_OPTIONS)
/* harmony export */ });
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/select-control/index.js");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@6.6.1/node_modules/@wordpress/i18n/build-module/index.js");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
/**
 * External dependencies
 */



const DEFAULT_PER_PAGE_OPTIONS = [25, 50, 75, 100];
function PageSizePicker({
  perPage,
  currentPage,
  total,
  setCurrentPage,
  setPerPageChange = () => {},
  perPageOptions = DEFAULT_PER_PAGE_OPTIONS,
  label = (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Rows per page', 'woocommerce')
}) {
  function perPageChange(newPerPage) {
    setPerPageChange(parseInt(newPerPage, 10));
    const newMaxPage = Math.ceil(total / parseInt(newPerPage, 10));
    if (currentPage > newMaxPage) {
      setCurrentPage(newMaxPage);
    }
  }

  // @todo Replace this with a styleized Select drop-down/control?
  const pickerOptions = perPageOptions.map(option => {
    return {
      value: option.toString(),
      label: option.toString()
    };
  });
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("div", {
    className: "woocommerce-pagination__per-page-picker",
    children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__/* ["default"] */ .A, {
      __next40pxDefaultSize: true,
      label: label,
      labelPosition: "side",
      value: perPage.toString(),
      onChange: perPageChange,
      options: pickerOptions
    })
  });
}
try {
    // @ts-ignore
    PageSizePicker.displayName = "PageSizePicker";
    // @ts-ignore
    PageSizePicker.__docgenInfo = { "description": "", "displayName": "PageSizePicker", "props": { "currentPage": { "defaultValue": null, "description": "", "name": "currentPage", "required": true, "type": { "name": "number" } }, "perPage": { "defaultValue": null, "description": "", "name": "perPage", "required": true, "type": { "name": "number" } }, "total": { "defaultValue": null, "description": "", "name": "total", "required": true, "type": { "name": "number" } }, "setCurrentPage": { "defaultValue": null, "description": "", "name": "setCurrentPage", "required": true, "type": { "name": "(page: number, action?: \"next\" | \"previous\" | \"goto\" | undefined) => void" } }, "setPerPageChange": { "defaultValue": { value: "() => {}" }, "description": "", "name": "setPerPageChange", "required": false, "type": { "name": "((perPage: number) => void)" } }, "perPageOptions": { "defaultValue": { value: "[ 25, 50, 75, 100 ]" }, "description": "", "name": "perPageOptions", "required": false, "type": { "name": "number[]" } }, "label": { "defaultValue": { value: "__( 'Rows per page', 'woocommerce' )" }, "description": "", "name": "label", "required": false, "type": { "name": "string" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/pagination/page-size-picker.tsx#PageSizePicker"] = { docgenInfo: PageSizePicker.__docgenInfo, name: "PageSizePicker", path: "../../packages/js/components/src/pagination/page-size-picker.tsx#PageSizePicker" };
}
catch (__react_docgen_typescript_loader_error) { }

/***/ }),

/***/ "../../packages/js/components/src/pagination/pagination.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  d: () => (/* binding */ Pagination)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs
var clsx = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/button/index.js
var build_module_button = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/button/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/icon/index.js + 1 modules
var icon = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/icon/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/library/chevron-right.js
var chevron_right = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/library/chevron-right.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/library/chevron-left.js
var chevron_left = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/library/chevron-left.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+i18n@6.6.1/node_modules/@wordpress/i18n/build-module/index.js + 3 modules
var build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@6.6.1/node_modules/@wordpress/i18n/build-module/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js
var jsx_runtime = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
;// ../../packages/js/components/src/pagination/page-arrows.tsx
/**
 * External dependencies
 */





function PageArrows({
  pageCount,
  currentPage,
  showPageArrowsLabel = true,
  setCurrentPage
}) {
  function previousPage(event) {
    event.stopPropagation();
    if (currentPage - 1 < 1) {
      return;
    }
    setCurrentPage(currentPage - 1, 'previous');
  }
  function nextPage(event) {
    event.stopPropagation();
    if (currentPage + 1 > pageCount) {
      return;
    }
    setCurrentPage(currentPage + 1, 'next');
  }
  if (pageCount <= 1) {
    return null;
  }
  const previousLinkClass = (0,clsx/* default */.A)('woocommerce-pagination__link', {
    'is-active': currentPage > 1
  });
  const nextLinkClass = (0,clsx/* default */.A)('woocommerce-pagination__link', {
    'is-active': currentPage < pageCount
  });
  return /*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
    className: "woocommerce-pagination__page-arrows",
    children: [showPageArrowsLabel && /*#__PURE__*/(0,jsx_runtime.jsx)("span", {
      className: "woocommerce-pagination__page-arrows-label",
      role: "status",
      "aria-live": "polite",
      children: (0,build_module/* sprintf */.nv)(/* translators: 1: current page number, 2: total number of pages */
      (0,build_module.__)('Page %1$d of %2$d', 'woocommerce'), currentPage, pageCount)
    }), /*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
      className: "woocommerce-pagination__page-arrows-buttons",
      children: [/*#__PURE__*/(0,jsx_runtime.jsx)(build_module_button/* default */.Ay, {
        className: previousLinkClass,
        disabled: !(currentPage > 1),
        onClick: previousPage,
        label: (0,build_module.__)('Previous Page', 'woocommerce'),
        children: /*#__PURE__*/(0,jsx_runtime.jsx)(icon/* default */.A, {
          icon: (0,build_module/* isRTL */.V8)() ? chevron_right/* default */.A : chevron_left/* default */.A
        })
      }), /*#__PURE__*/(0,jsx_runtime.jsx)(build_module_button/* default */.Ay, {
        className: nextLinkClass,
        disabled: !(currentPage < pageCount),
        onClick: nextPage,
        label: (0,build_module.__)('Next Page', 'woocommerce'),
        children: /*#__PURE__*/(0,jsx_runtime.jsx)(icon/* default */.A, {
          icon: (0,build_module/* isRTL */.V8)() ? chevron_left/* default */.A : chevron_right/* default */.A
        })
      })]
    })]
  });
}
try {
    // @ts-ignore
    PageArrows.displayName = "PageArrows";
    // @ts-ignore
    PageArrows.__docgenInfo = { "description": "", "displayName": "PageArrows", "props": { "currentPage": { "defaultValue": null, "description": "", "name": "currentPage", "required": true, "type": { "name": "number" } }, "pageCount": { "defaultValue": null, "description": "", "name": "pageCount", "required": true, "type": { "name": "number" } }, "showPageArrowsLabel": { "defaultValue": { value: "true" }, "description": "", "name": "showPageArrowsLabel", "required": false, "type": { "name": "boolean" } }, "setCurrentPage": { "defaultValue": null, "description": "", "name": "setCurrentPage", "required": true, "type": { "name": "(page: number, action?: \"next\" | \"previous\" | \"goto\" | undefined) => void" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/pagination/page-arrows.tsx#PageArrows"] = { docgenInfo: PageArrows.__docgenInfo, name: "PageArrows", path: "../../packages/js/components/src/pagination/page-arrows.tsx#PageArrows" };
}
catch (__react_docgen_typescript_loader_error) { }
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/lodash@4.18.1/node_modules/lodash/lodash.js
var lodash = __webpack_require__("../../node_modules/.pnpm/lodash@4.18.1/node_modules/lodash/lodash.js");
;// ../../packages/js/components/src/pagination/page-picker.tsx
/**
 * External dependencies
 */





function PagePicker({
  pageCount,
  currentPage,
  setCurrentPage
}) {
  const [inputValue, setInputValue] = (0,react.useState)(currentPage);
  function onInputChange(event) {
    setInputValue(parseInt(event.currentTarget.value, 10));
  }
  function onInputBlur(event) {
    const newPage = parseInt(event.target.value, 10);
    if (newPage !== currentPage && Number.isFinite(newPage) && newPage > 0 && pageCount && pageCount >= newPage) {
      setCurrentPage(newPage, 'goto');
    }
  }
  function selectInputValue(event) {
    event.currentTarget.select();
  }
  const isError = currentPage < 1 || currentPage > pageCount;
  const inputClass = (0,clsx/* default */.A)('woocommerce-pagination__page-picker-input', {
    'has-error': isError
  });
  const instanceId = (0,lodash.uniqueId)('woocommerce-pagination-page-picker-');
  return /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
    className: "woocommerce-pagination__page-picker",
    children: /*#__PURE__*/(0,jsx_runtime.jsxs)("label", {
      htmlFor: instanceId,
      className: "woocommerce-pagination__page-picker-label",
      children: [(0,build_module.__)('Go to page', 'woocommerce'), /*#__PURE__*/(0,jsx_runtime.jsx)("input", {
        id: instanceId,
        className: inputClass,
        "aria-invalid": isError,
        type: "number",
        onClick: selectInputValue,
        onChange: onInputChange,
        onBlur: onInputBlur,
        value: inputValue,
        min: 1,
        max: pageCount
      })]
    })
  });
}
try {
    // @ts-ignore
    PagePicker.displayName = "PagePicker";
    // @ts-ignore
    PagePicker.__docgenInfo = { "description": "", "displayName": "PagePicker", "props": { "currentPage": { "defaultValue": null, "description": "", "name": "currentPage", "required": true, "type": { "name": "number" } }, "pageCount": { "defaultValue": null, "description": "", "name": "pageCount", "required": true, "type": { "name": "number" } }, "setCurrentPage": { "defaultValue": null, "description": "", "name": "setCurrentPage", "required": true, "type": { "name": "(page: number, action?: \"next\" | \"previous\" | \"goto\" | undefined) => void" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/pagination/page-picker.tsx#PagePicker"] = { docgenInfo: PagePicker.__docgenInfo, name: "PagePicker", path: "../../packages/js/components/src/pagination/page-picker.tsx#PagePicker" };
}
catch (__react_docgen_typescript_loader_error) { }
// EXTERNAL MODULE: ../../packages/js/components/src/pagination/page-size-picker.tsx
var page_size_picker = __webpack_require__("../../packages/js/components/src/pagination/page-size-picker.tsx");
;// ../../packages/js/components/src/pagination/pagination.tsx
/**
 * External dependencies
 */



/**
 * Internal dependencies
 */




function Pagination({
  page,
  onPageChange = () => {},
  total,
  perPage,
  onPerPageChange = () => {},
  showPagePicker = true,
  showPerPagePicker = true,
  showPageArrowsLabel = true,
  className,
  perPageOptions = page_size_picker/* DEFAULT_PER_PAGE_OPTIONS */.v,
  children
}) {
  const pageCount = Math.ceil(total / perPage);
  if (children && typeof children === 'function') {
    return children({
      pageCount
    });
  }
  const classes = (0,clsx/* default */.A)('woocommerce-pagination', className);
  if (pageCount <= 1) {
    return total > perPageOptions[0] && /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
      className: classes,
      children: /*#__PURE__*/(0,jsx_runtime.jsx)(page_size_picker/* PageSizePicker */.$, {
        currentPage: page,
        perPage: perPage,
        setCurrentPage: onPageChange,
        total: total,
        setPerPageChange: onPerPageChange,
        perPageOptions: perPageOptions
      })
    }) || null;
  }
  return /*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
    className: classes,
    children: [/*#__PURE__*/(0,jsx_runtime.jsx)(PageArrows, {
      currentPage: page,
      pageCount: pageCount,
      showPageArrowsLabel: showPageArrowsLabel,
      setCurrentPage: onPageChange
    }), showPagePicker && /*#__PURE__*/(0,jsx_runtime.jsx)(PagePicker, {
      currentPage: page,
      pageCount: pageCount,
      setCurrentPage: onPageChange
    }), showPerPagePicker && /*#__PURE__*/(0,jsx_runtime.jsx)(page_size_picker/* PageSizePicker */.$, {
      currentPage: page,
      perPage: perPage,
      setCurrentPage: onPageChange,
      total: total,
      setPerPageChange: onPerPageChange,
      perPageOptions: perPageOptions
    })]
  });
}
try {
    // @ts-ignore
    Pagination.displayName = "Pagination";
    // @ts-ignore
    Pagination.__docgenInfo = { "description": "", "displayName": "Pagination", "props": { "page": { "defaultValue": null, "description": "", "name": "page", "required": true, "type": { "name": "number" } }, "perPage": { "defaultValue": null, "description": "", "name": "perPage", "required": true, "type": { "name": "number" } }, "total": { "defaultValue": null, "description": "", "name": "total", "required": true, "type": { "name": "number" } }, "onPageChange": { "defaultValue": { value: "() => {}" }, "description": "", "name": "onPageChange", "required": false, "type": { "name": "((page: number, action?: \"next\" | \"previous\" | \"goto\") => void)" } }, "onPerPageChange": { "defaultValue": { value: "() => {}" }, "description": "", "name": "onPerPageChange", "required": false, "type": { "name": "((perPage: number) => void)" } }, "className": { "defaultValue": null, "description": "", "name": "className", "required": false, "type": { "name": "string" } }, "showPagePicker": { "defaultValue": { value: "true" }, "description": "", "name": "showPagePicker", "required": false, "type": { "name": "boolean" } }, "showPerPagePicker": { "defaultValue": { value: "true" }, "description": "", "name": "showPerPagePicker", "required": false, "type": { "name": "boolean" } }, "showPageArrowsLabel": { "defaultValue": { value: "true" }, "description": "", "name": "showPageArrowsLabel", "required": false, "type": { "name": "boolean" } }, "perPageOptions": { "defaultValue": { value: "[ 25, 50, 75, 100 ]" }, "description": "", "name": "perPageOptions", "required": false, "type": { "name": "number[]" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/pagination/pagination.tsx#Pagination"] = { docgenInfo: Pagination.__docgenInfo, name: "Pagination", path: "../../packages/js/components/src/pagination/pagination.tsx#Pagination" };
}
catch (__react_docgen_typescript_loader_error) { }

/***/ })

}]);