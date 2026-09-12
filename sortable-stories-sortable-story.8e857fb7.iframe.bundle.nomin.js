"use strict";
(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[5264],{

/***/ "../../packages/js/components/src/sortable/stories/sortable.story.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

// ESM COMPAT FLAG
__webpack_require__.r(__webpack_exports__);

// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  Basic: () => (/* binding */ Basic),
  CustomHandle: () => (/* binding */ CustomHandle),
  "default": () => (/* binding */ sortable_story)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/icon/index.js
var icon = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/icon/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js
var jsx_runtime = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+primitives@4.48.1_react@18.3.1/node_modules/@wordpress/primitives/build-module/svg/index.mjs
var svg = __webpack_require__("../../node_modules/.pnpm/@wordpress+primitives@4.48.1_react@18.3.1/node_modules/@wordpress/primitives/build-module/svg/index.mjs");
;// ../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/library/wordpress.js


var wordpress_default = /* @__PURE__ */ (0,jsx_runtime.jsx)(svg/* SVG */.t4, { xmlns: "http://www.w3.org/2000/svg", viewBox: "-2 -2 24 24", children: /* @__PURE__ */ (0,jsx_runtime.jsx)(svg/* Path */.wA, { d: "M20 10c0-5.51-4.49-10-10-10C4.48 0 0 4.49 0 10c0 5.52 4.48 10 10 10 5.51 0 10-4.48 10-10zM7.78 15.37L4.37 6.22c.55-.02 1.17-.08 1.17-.08.5-.06.44-1.13-.06-1.11 0 0-1.45.11-2.37.11-.18 0-.37 0-.58-.01C4.12 2.69 6.87 1.11 10 1.11c2.33 0 4.45.87 6.05 2.34-.68-.11-1.65.39-1.65 1.58 0 .74.45 1.36.9 2.1.35.61.55 1.36.55 2.46 0 1.49-1.4 5-1.4 5l-3.03-8.37c.54-.02.82-.17.82-.17.5-.05.44-1.25-.06-1.22 0 0-1.44.12-2.38.12-.87 0-2.33-.12-2.33-.12-.5-.03-.56 1.2-.06 1.22l.92.08 1.26 3.41zM17.41 10c.24-.64.74-1.87.43-4.25.7 1.29 1.05 2.71 1.05 4.25 0 3.29-1.73 6.24-4.4 7.78.97-2.59 1.94-5.2 2.92-7.78zM6.1 18.09C3.12 16.65 1.11 13.53 1.11 10c0-1.3.23-2.48.72-3.59C3.25 10.3 4.67 14.2 6.1 18.09zm4.03-6.63l2.58 6.98c-.86.29-1.76.45-2.71.45-.79 0-1.57-.11-2.29-.33.81-2.38 1.62-4.74 2.42-7.1z" }) });

//# sourceMappingURL=wordpress.js.map

// EXTERNAL MODULE: ../../packages/js/components/src/sortable/sortable.tsx
var sortable = __webpack_require__("../../packages/js/components/src/sortable/sortable.tsx");
// EXTERNAL MODULE: ../../packages/js/components/src/sortable/sortable-handle.tsx + 1 modules
var sortable_handle = __webpack_require__("../../packages/js/components/src/sortable/sortable-handle.tsx");
// EXTERNAL MODULE: ../../packages/js/components/src/list-item/list-item.tsx + 1 modules
var list_item = __webpack_require__("../../packages/js/components/src/list-item/list-item.tsx");
;// ../../packages/js/components/src/sortable/stories/sortable.story.tsx
/**
 * External dependencies
 */



/**
 * Internal dependencies
 */



const Basic = () => {
  return /*#__PURE__*/(0,jsx_runtime.jsxs)(sortable/* Sortable */.L, {
    onOrderChange: items =>
    // eslint-disable-next-line no-console
    console.log('Order changed: ' + items.map(item => item.key)),
    children: [/*#__PURE__*/(0,jsx_runtime.jsx)(list_item/* ListItem */.c, {
      children: "Item 1"
    }, 'item-1'), /*#__PURE__*/(0,jsx_runtime.jsx)(list_item/* ListItem */.c, {
      children: "Item 2"
    }, 'item-2'), /*#__PURE__*/(0,jsx_runtime.jsx)(list_item/* ListItem */.c, {
      children: "Item 3"
    }, 'item-3'), /*#__PURE__*/(0,jsx_runtime.jsx)(list_item/* ListItem */.c, {
      children: "Item 4"
    }, 'item-4'), /*#__PURE__*/(0,jsx_runtime.jsx)(list_item/* ListItem */.c, {
      children: "Item 5"
    }, 'item-5')]
  });
};
const CustomHandle = () => {
  const CustomListItem = ({
    children
  }) => {
    return /*#__PURE__*/(0,jsx_runtime.jsxs)(jsx_runtime.Fragment, {
      children: [/*#__PURE__*/(0,jsx_runtime.jsx)(sortable_handle/* SortableHandle */.D, {
        children: /*#__PURE__*/(0,jsx_runtime.jsx)(icon/* default */.A, {
          icon: wordpress_default,
          size: 16
        })
      }), children]
    });
  };
  return /*#__PURE__*/(0,jsx_runtime.jsxs)(sortable/* Sortable */.L, {
    children: [/*#__PURE__*/(0,jsx_runtime.jsx)(CustomListItem, {
      children: "Item 1"
    }, "item-1"), /*#__PURE__*/(0,jsx_runtime.jsx)(CustomListItem, {
      children: "Item 2"
    }, "item-2"), /*#__PURE__*/(0,jsx_runtime.jsx)(CustomListItem, {
      children: "Item 3"
    }, "item-3"), /*#__PURE__*/(0,jsx_runtime.jsx)(CustomListItem, {
      children: "Item 4"
    }, "item-4"), /*#__PURE__*/(0,jsx_runtime.jsx)(CustomListItem, {
      children: "Item 5"
    }, "item-5")]
  });
};
/* harmony default export */ const sortable_story = ({
  title: 'Components/Sortable',
  component: sortable/* Sortable */.L
});
Basic.parameters = {
  ...Basic.parameters,
  docs: {
    ...Basic.parameters?.docs,
    source: {
      originalSource: "() => {\n  return <Sortable onOrderChange={items =>\n  // eslint-disable-next-line no-console\n  console.log('Order changed: ' + items.map(item => item.key))}>\n            <ListItem key={'item-1'}>Item 1</ListItem>\n            <ListItem key={'item-2'}>Item 2</ListItem>\n            <ListItem key={'item-3'}>Item 3</ListItem>\n            <ListItem key={'item-4'}>Item 4</ListItem>\n            <ListItem key={'item-5'}>Item 5</ListItem>\n        </Sortable>;\n}",
      ...Basic.parameters?.docs?.source
    }
  }
};
CustomHandle.parameters = {
  ...CustomHandle.parameters,
  docs: {
    ...CustomHandle.parameters?.docs,
    source: {
      originalSource: "() => {\n  type CustomListItemProps = {\n    children: React.ReactNode;\n    onDragEnd?: DragEventHandler<Element>;\n    onDragStart?: DragEventHandler<Element>;\n  };\n  const CustomListItem = ({\n    children\n  }: CustomListItemProps) => {\n    return <>\n                <SortableHandle>\n                    <Icon icon={wordpress} size={16} />\n                </SortableHandle>\n                {children}\n            </>;\n  };\n  return <Sortable>\n            <CustomListItem key=\"item-1\">Item 1</CustomListItem>\n            <CustomListItem key=\"item-2\">Item 2</CustomListItem>\n            <CustomListItem key=\"item-3\">Item 3</CustomListItem>\n            <CustomListItem key=\"item-4\">Item 4</CustomListItem>\n            <CustomListItem key=\"item-5\">Item 5</CustomListItem>\n        </Sortable>;\n}",
      ...CustomHandle.parameters?.docs?.source
    }
  }
};
try {
    // @ts-ignore
    Sortable.displayName = "Sortable";
    // @ts-ignore
    Sortable.__docgenInfo = { "description": "", "displayName": "Sortable", "props": { "isHorizontal": { "defaultValue": { value: "false" }, "description": "", "name": "isHorizontal", "required": false, "type": { "name": "boolean" } }, "onOrderChange": { "defaultValue": { value: "() => null" }, "description": "", "name": "onOrderChange", "required": false, "type": { "name": "((items: Element[]) => void)" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/sortable/stories/sortable.story.tsx#Sortable"] = { docgenInfo: Sortable.__docgenInfo, name: "Sortable", path: "../../packages/js/components/src/sortable/stories/sortable.story.tsx#Sortable" };
}
catch (__react_docgen_typescript_loader_error) { }

/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/icon/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (/* binding */ icon_default)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");

var icon_default = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.forwardRef)(
  ({ icon, size = 24, ...props }, ref) => {
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.cloneElement)(icon, {
      width: size,
      height: size,
      ...props,
      ref
    });
  }
);

//# sourceMappingURL=index.js.map


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

/***/ "../../packages/js/components/src/list-item/list-item.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  c: () => (/* binding */ ListItem)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs
var clsx = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
// EXTERNAL MODULE: ../../packages/js/components/src/sortable/sortable-handle.tsx + 1 modules
var sortable_handle = __webpack_require__("../../packages/js/components/src/sortable/sortable-handle.tsx");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+i18n@6.6.1/node_modules/@wordpress/i18n/build-module/index.js + 3 modules
var build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@6.6.1/node_modules/@wordpress/i18n/build-module/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/draggable/index.js + 1 modules
var draggable = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/draggable/index.js");
// EXTERNAL MODULE: ../../packages/js/components/src/sortable/sortable.tsx
var sortable = __webpack_require__("../../packages/js/components/src/sortable/sortable.tsx");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js
var jsx_runtime = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
;// ../../packages/js/components/src/sortable/sortable-item.tsx
/**
 * External dependencies
 */






/**
 * Internal dependencies
 */


const SortableItem = ({
  id,
  children,
  className,
  isDragging = false,
  isSelected = false,
  onDragStart = () => null,
  onDragEnd = () => null,
  role = 'listitem',
  ...props
}) => {
  const ref = (0,react.useRef)(null);
  const sortableContext = (0,react.useContext)(sortable/* SortableContext */.g);
  const handleDragStart = event => {
    onDragStart(event);
  };
  const handleDragEnd = event => {
    event.preventDefault();
    onDragEnd(event);
  };
  (0,react.useEffect)(() => {
    if (isSelected && ref.current) {
      ref.current.focus();
    }
  }, [isSelected]);
  return /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
    ...props,
    "aria-selected": isSelected,
    className: (0,clsx/* default */.A)('woocommerce-sortable__item', className, {
      'is-dragging': isDragging,
      'is-selected': isSelected
    }),
    id: `woocommerce-sortable__item-${id}`,
    role: role,
    onDrop: event => event.preventDefault(),
    ref: ref,
    tabIndex: isSelected ? 0 : -1
    // eslint-disable-next-line jsx-a11y/aria-props
    ,
    "aria-description": (0,build_module.__)('Press spacebar to reorder', 'woocommerce'),
    children: /*#__PURE__*/(0,jsx_runtime.jsx)(draggable/* default */.A, {
      elementId: `woocommerce-sortable__item-${id}`,
      transferData: {},
      onDragStart: handleDragStart,
      onDragEnd: handleDragEnd,
      children: ({
        onDraggableStart,
        onDraggableEnd
      }) => {
        return /*#__PURE__*/(0,jsx_runtime.jsx)(sortable/* SortableContext */.g.Provider, {
          value: {
            ...sortableContext,
            onDragStart: onDraggableStart,
            onDragEnd: onDraggableEnd
          },
          children: children
        });
      }
    })
  });
};
try {
    // @ts-ignore
    SortableItem.displayName = "SortableItem";
    // @ts-ignore
    SortableItem.__docgenInfo = { "description": "", "displayName": "SortableItem", "props": { "index": { "defaultValue": null, "description": "", "name": "index", "required": true, "type": { "name": "number" } }, "isDragging": { "defaultValue": { value: "false" }, "description": "", "name": "isDragging", "required": false, "type": { "name": "boolean" } }, "isSelected": { "defaultValue": { value: "false" }, "description": "", "name": "isSelected", "required": false, "type": { "name": "boolean" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/sortable/sortable-item.tsx#SortableItem"] = { docgenInfo: SortableItem.__docgenInfo, name: "SortableItem", path: "../../packages/js/components/src/sortable/sortable-item.tsx#SortableItem" };
}
catch (__react_docgen_typescript_loader_error) { }
;// ../../packages/js/components/src/list-item/list-item.tsx
/**
 * External dependencies
 */

/**
 * Internal dependencies
 */



const ListItem = ({
  children,
  className,
  index = 0,
  onDragStart,
  onDragEnd,
  ...props
}) => {
  const isDraggable = onDragEnd && onDragStart;
  return /*#__PURE__*/(0,jsx_runtime.jsxs)(SortableItem, {
    ...props,
    index: index,
    className: (0,clsx/* default */.A)('woocommerce-list-item', className),
    children: [isDraggable && /*#__PURE__*/(0,jsx_runtime.jsx)(sortable_handle/* SortableHandle */.D, {}), children]
  });
};
try {
    // @ts-ignore
    ListItem.displayName = "ListItem";
    // @ts-ignore
    ListItem.__docgenInfo = { "description": "", "displayName": "ListItem", "props": { "isDragging": { "defaultValue": null, "description": "", "name": "isDragging", "required": false, "type": { "name": "boolean" } }, "isSelected": { "defaultValue": null, "description": "", "name": "isSelected", "required": false, "type": { "name": "boolean" } }, "index": { "defaultValue": { value: "0" }, "description": "", "name": "index", "required": false, "type": { "name": "number" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/list-item/list-item.tsx#ListItem"] = { docgenInfo: ListItem.__docgenInfo, name: "ListItem", path: "../../packages/js/components/src/list-item/list-item.tsx#ListItem" };
}
catch (__react_docgen_typescript_loader_error) { }

/***/ }),

/***/ "../../packages/js/components/src/sortable/sortable-handle.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  D: () => (/* binding */ SortableHandle)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js
var jsx_runtime = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
;// ../../packages/js/components/src/sortable/draggable-icon.tsx

/**
 * External dependencies
 */

const DraggableIcon = () => /*#__PURE__*/(0,jsx_runtime.jsxs)("svg", {
  width: "8",
  height: "14",
  viewBox: "0 0 8 14",
  fill: "none",
  xmlns: "http://www.w3.org/2000/svg",
  children: [/*#__PURE__*/(0,jsx_runtime.jsx)("rect", {
    width: "2",
    height: "2",
    fill: "#757575"
  }), /*#__PURE__*/(0,jsx_runtime.jsx)("rect", {
    y: "6",
    width: "2",
    height: "2",
    fill: "#757575"
  }), /*#__PURE__*/(0,jsx_runtime.jsx)("rect", {
    y: "12",
    width: "2",
    height: "2",
    fill: "#757575"
  }), /*#__PURE__*/(0,jsx_runtime.jsx)("rect", {
    x: "6",
    width: "2",
    height: "2",
    fill: "#757575"
  }), /*#__PURE__*/(0,jsx_runtime.jsx)("rect", {
    x: "6",
    y: "6",
    width: "2",
    height: "2",
    fill: "#757575"
  }), /*#__PURE__*/(0,jsx_runtime.jsx)("rect", {
    x: "6",
    y: "12",
    width: "2",
    height: "2",
    fill: "#757575"
  })]
});
// EXTERNAL MODULE: ../../packages/js/components/src/sortable/sortable.tsx
var sortable = __webpack_require__("../../packages/js/components/src/sortable/sortable.tsx");
;// ../../packages/js/components/src/sortable/sortable-handle.tsx
/**
 * External dependencies
 */


/**
 * Internal dependencies
 */



const SortableHandle = ({
  children,
  itemIndex
}) => {
  const {
    onDragStart,
    onDragEnd
  } = (0,react.useContext)(sortable/* SortableContext */.g);
  return /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
    className: "woocommerce-sortable__handle",
    draggable: true,
    onDragStart: onDragStart,
    onDragEnd: onDragEnd,
    "data-index": itemIndex,
    children: children ? children : /*#__PURE__*/(0,jsx_runtime.jsx)(DraggableIcon, {})
  });
};
try {
    // @ts-ignore
    SortableHandle.displayName = "SortableHandle";
    // @ts-ignore
    SortableHandle.__docgenInfo = { "description": "", "displayName": "SortableHandle", "props": { "itemIndex": { "defaultValue": null, "description": "", "name": "itemIndex", "required": false, "type": { "name": "number" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/sortable/sortable-handle.tsx#SortableHandle"] = { docgenInfo: SortableHandle.__docgenInfo, name: "SortableHandle", path: "../../packages/js/components/src/sortable/sortable-handle.tsx#SortableHandle" };
}
catch (__react_docgen_typescript_loader_error) { }

/***/ }),

/***/ "../../packages/js/components/src/sortable/sortable.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   L: () => (/* binding */ Sortable),
/* harmony export */   g: () => (/* binding */ SortableContext)
/* harmony export */ });
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@6.6.1/node_modules/@wordpress/i18n/build-module/index.js");
/* harmony import */ var clsx__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var _wordpress_a11y__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+a11y@4.33.1/node_modules/@wordpress/a11y/build-module/index.js");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/lodash@4.18.1/node_modules/lodash/lodash.js");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var uuid__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__("../../node_modules/.pnpm/uuid@9.0.1/node_modules/uuid/dist/esm-browser/v4.js");
/* harmony import */ var _utils__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__("../../packages/js/components/src/sortable/utils.ts");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
/**
 * External dependencies
 */







/**
 * Internal dependencies
 */


const THROTTLE_TIME = 16;
const SortableContext = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_4__.createContext)({});
const Sortable = ({
  children,
  isHorizontal = false,
  onDragEnd = () => null,
  onDragOver = () => null,
  onDragStart = () => null,
  onOrderChange = () => null,
  className,
  role = 'listbox',
  ...props
}) => {
  const ref = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_4__.useRef)(null);
  const [items, setItems] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_4__.useState)([]);
  const [selectedIndex, setSelectedIndex] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_4__.useState)(-1);
  const [dragIndex, setDragIndex] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_4__.useState)(null);
  const [dropIndex, setDropIndex] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_4__.useState)(null);
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_4__.useEffect)(() => {
    if (!children) {
      return;
    }
    setItems(Array.isArray(children) ? children : [children]);
  }, [children]);
  const resetIndexes = () => {
    setTimeout(() => {
      setDragIndex(null);
      setDropIndex(null);
    }, THROTTLE_TIME);
  };
  const persistItemOrder = () => {
    if (dropIndex !== null && dragIndex !== null && dropIndex !== dragIndex) {
      const nextItems = (0,_utils__WEBPACK_IMPORTED_MODULE_5__/* .moveIndex */ .e6)(dragIndex, dropIndex, items);
      setItems(nextItems);
      onOrderChange(nextItems);
    }
    resetIndexes();
  };
  const handleDragStart = (event, index) => {
    setDropIndex(index);
    setDragIndex(index);
    onDragStart(event);
  };
  const handleDragEnd = event => {
    persistItemOrder();
    onDragEnd(event);
  };
  const handleDragOver = (event, index) => {
    if (dragIndex === null) {
      return;
    }

    // Items before the current item cause a one off error when
    // removed from the old array and spliced into the new array.
    // TODO: Issue with dragging into same position having to do with isBefore returning true initially.
    let targetIndex = dragIndex < index ? index : index + 1;
    if ((0,_utils__WEBPACK_IMPORTED_MODULE_5__/* .isBefore */ .Y8)(event, isHorizontal)) {
      targetIndex--;
    }
    setDropIndex(targetIndex);
    onDragOver(event);
  };
  const throttledHandleDragOver = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_4__.useCallback)((0,lodash__WEBPACK_IMPORTED_MODULE_2__.throttle)(handleDragOver, THROTTLE_TIME), [dragIndex]);
  const handleKeyDown = event => {
    const {
      key
    } = event;
    const isSelecting = dragIndex === null || dropIndex === null;
    const selectedLabel = (0,_utils__WEBPACK_IMPORTED_MODULE_5__/* .getItemName */ .H0)(ref.current, selectedIndex);

    // Select or drop on spacebar press.
    if (key === ' ') {
      if (isSelecting) {
        (0,_wordpress_a11y__WEBPACK_IMPORTED_MODULE_1__/* .speak */ .L)((0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__/* .sprintf */ .nv)(/** Translators: Selected item label */
        (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('%s selected, use up and down arrow keys to reorder', 'woocommerce'), selectedLabel ?? ''), 'assertive');
        setDragIndex(selectedIndex);
        setDropIndex(selectedIndex);
        return;
      }
      setSelectedIndex(dropIndex);
      (0,_wordpress_a11y__WEBPACK_IMPORTED_MODULE_1__/* .speak */ .L)((0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__/* .sprintf */ .nv)(/* translators: %1$s: Selected item label, %2$d: Current position in list, %3$d: List total length */
      (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('%1$s dropped, position in list: %2$d of %3$d', 'woocommerce'), selectedLabel ?? '', dropIndex + 1, items.length), 'assertive');
      persistItemOrder();
      return;
    }
    if (key === 'ArrowUp') {
      if (isSelecting) {
        setSelectedIndex((0,_utils__WEBPACK_IMPORTED_MODULE_5__/* .getPreviousIndex */ .S1)(selectedIndex, items.length));
        return;
      }
      const previousDropIndex = (0,_utils__WEBPACK_IMPORTED_MODULE_5__/* .getPreviousIndex */ .S1)(dropIndex, items.length);
      setDropIndex(previousDropIndex);
      (0,_wordpress_a11y__WEBPACK_IMPORTED_MODULE_1__/* .speak */ .L)((0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__/* .sprintf */ .nv)(/* translators: %1$s: Selected item label, %2$d: Current position in list, %3$d: List total length */
      (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('%1$s, position in list: %2$d of %3$d', 'woocommerce'), selectedLabel ?? '', previousDropIndex + 1, items.length), 'assertive');
      return;
    }
    if (key === 'ArrowDown') {
      if (isSelecting) {
        setSelectedIndex((0,_utils__WEBPACK_IMPORTED_MODULE_5__/* .getNextIndex */ .g0)(selectedIndex, items.length));
        return;
      }
      const nextDropIndex = (0,_utils__WEBPACK_IMPORTED_MODULE_5__/* .getNextIndex */ .g0)(dropIndex, items.length);
      setDropIndex(nextDropIndex);
      (0,_wordpress_a11y__WEBPACK_IMPORTED_MODULE_1__/* .speak */ .L)((0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__/* .sprintf */ .nv)(/* translators: %1$s: Selected item label, %2$d: Current position in list, %3$d: List total length */
      (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('%1$s, position in list: %2$d of %3$d', 'woocommerce'), selectedLabel ?? '', nextDropIndex + 1, items.length), 'assertive');
      return;
    }
    if (key === 'Escape') {
      resetIndexes();
      (0,_wordpress_a11y__WEBPACK_IMPORTED_MODULE_1__/* .speak */ .L)((0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Reordering cancelled. Restoring the original list order', 'woocommerce'), 'assertive');
    }
  };
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)(SortableContext.Provider, {
    value: {},
    children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("div", {
      ...props,
      className: (0,clsx__WEBPACK_IMPORTED_MODULE_6__/* ["default"] */ .A)('woocommerce-sortable', className, {
        'is-dragging': dragIndex !== null,
        'is-horizontal': isHorizontal
      }),
      ref: ref,
      role: role,
      children: items.map((child, index) => {
        const isDragging = index === dragIndex;
        if (child.props.className && child.props.className.indexOf('non-sortable-item') !== -1) {
          return child;
        }
        const itemClasses = (0,clsx__WEBPACK_IMPORTED_MODULE_6__/* ["default"] */ .A)(child.props.className, {
          'is-dragging-over-after': (0,_utils__WEBPACK_IMPORTED_MODULE_5__/* .isDraggingOverAfter */ .Km)(index, dragIndex, dropIndex),
          'is-dragging-over-before': (0,_utils__WEBPACK_IMPORTED_MODULE_5__/* .isDraggingOverBefore */ .PZ)(index, dragIndex, dropIndex),
          'is-last-droppable': (0,_utils__WEBPACK_IMPORTED_MODULE_5__/* .isLastDroppable */ .Ib)(index, dragIndex, items.length)
        });
        return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_4__.cloneElement)(child, {
          key: child.key || index,
          className: itemClasses,
          id: `${index}-${(0,uuid__WEBPACK_IMPORTED_MODULE_7__/* ["default"] */ .A)()}`,
          index,
          isDragging,
          isSelected: selectedIndex === index,
          onDragEnd: handleDragEnd,
          onDragStart: event => handleDragStart(event, index),
          onDragOver: event => {
            event.preventDefault();
            throttledHandleDragOver(event, index);
          },
          onKeyDown: event => handleKeyDown(event)
        });
      })
    })
  });
};
try {
    // @ts-ignore
    Sortable.displayName = "Sortable";
    // @ts-ignore
    Sortable.__docgenInfo = { "description": "", "displayName": "Sortable", "props": { "isHorizontal": { "defaultValue": { value: "false" }, "description": "", "name": "isHorizontal", "required": false, "type": { "name": "boolean" } }, "onOrderChange": { "defaultValue": { value: "() => null" }, "description": "", "name": "onOrderChange", "required": false, "type": { "name": "((items: Element[]) => void)" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/sortable/sortable.tsx#Sortable"] = { docgenInfo: Sortable.__docgenInfo, name: "Sortable", path: "../../packages/js/components/src/sortable/sortable.tsx#Sortable" };
}
catch (__react_docgen_typescript_loader_error) { }

/***/ }),

/***/ "../../packages/js/components/src/sortable/utils.ts":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   H0: () => (/* binding */ getItemName),
/* harmony export */   Ib: () => (/* binding */ isLastDroppable),
/* harmony export */   Km: () => (/* binding */ isDraggingOverAfter),
/* harmony export */   PZ: () => (/* binding */ isDraggingOverBefore),
/* harmony export */   S1: () => (/* binding */ getPreviousIndex),
/* harmony export */   Y8: () => (/* binding */ isBefore),
/* harmony export */   e6: () => (/* binding */ moveIndex),
/* harmony export */   g0: () => (/* binding */ getNextIndex)
/* harmony export */ });
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@6.6.1/node_modules/@wordpress/i18n/build-module/index.js");
/**
 * External dependencies
 */

/**
 * Move an item from an index in an array to a new index.s
 *
 * @param fromIndex Index to move the item from.
 * @param toIndex   Index to move the item to.
 * @param arr       The array to copy.
 * @return array
 */
const moveIndex = (fromIndex, toIndex, arr) => {
  const newArr = [...arr];
  const item = arr[fromIndex];
  newArr.splice(fromIndex, 1);
  newArr.splice(toIndex, 0, item);
  return newArr;
};

/**
 * Check whether the mouse is over the first half of the event target.
 *
 * @param event        Drag event.
 * @param isHorizontal Check horizontally or vertically.
 * @return boolean
 */
const isBefore = (event, isHorizontal = false) => {
  const target = event.target;
  if (isHorizontal) {
    const middle = target.offsetWidth / 2;
    const rect = target.getBoundingClientRect();
    const relativeX = event.clientX - rect.left;
    return relativeX < middle;
  }
  const middle = target.offsetHeight / 2;
  const rect = target.getBoundingClientRect();
  const relativeY = event.clientY - rect.top;
  return relativeY < middle;
};
const isDraggingOverAfter = (index, dragIndex, dropIndex) => {
  if (dragIndex === null) {
    return false;
  }
  if (dragIndex < index) {
    return dropIndex === index;
  }
  return dropIndex === index + 1;
};
const isDraggingOverBefore = (index, dragIndex, dropIndex) => {
  if (dragIndex === null) {
    return false;
  }
  if (dragIndex < index) {
    return dropIndex === index - 1;
  }
  return dropIndex === index;
};
const isLastDroppable = (index, dragIndex, itemCount) => {
  if (dragIndex === index) {
    return false;
  }
  if (index === itemCount - 1) {
    return true;
  }
  if (dragIndex === itemCount - 1 && index === itemCount - 2) {
    return true;
  }
  return false;
};
const getNextIndex = (currentIndex, itemCount) => {
  let index = currentIndex + 1;
  if (index > itemCount - 1) {
    index = 0;
  }
  return index;
};
const getPreviousIndex = (currentIndex, itemCount) => {
  let index = currentIndex - 1;
  if (index < 0) {
    index = itemCount - 1;
  }
  return index;
};
const getItemName = (parentNode, index) => {
  const listItemNode = parentNode?.childNodes[index];
  if (index === null || !listItemNode) {
    return null;
  }
  if (listItemNode.querySelector('[aria-label]')) {
    return listItemNode.querySelector('[aria-label]')?.ariaLabel;
  }
  if (listItemNode.textContent) {
    return listItemNode.textContent;
  }
  if (listItemNode.querySelector('[alt]')) {
    return listItemNode.querySelector('[alt]').alt;
  }
  return (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Item', 'woocommerce');
};

/***/ })

}]);