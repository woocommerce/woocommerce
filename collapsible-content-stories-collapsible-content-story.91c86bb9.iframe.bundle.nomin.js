"use strict";
(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[4926],{

/***/ "../../packages/js/components/src/collapsible-content/stories/collapsible-content.story.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

// ESM COMPAT FLAG
__webpack_require__.r(__webpack_exports__);

// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  Basic: () => (/* binding */ Basic),
  Expanded: () => (/* binding */ Expanded),
  "default": () => (/* binding */ collapsible_content_story)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+compose@7.33.1_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-instance-id/index.js
var use_instance_id = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@7.33.1_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-instance-id/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/icon/index.js
var icon = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/icon/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/library/chevron-down.js
var chevron_down = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/library/chevron-down.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/library/chevron-up.js
var chevron_up = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/library/chevron-up.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js
var jsx_runtime = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
;// ../../packages/js/components/src/display-state/display-state.tsx

/**
 * External dependencies
 */

/**
 * Internal dependencies
 */

const DisplayState = ({
  state = 'visible',
  children,
  ...props
}) => {
  if (state === 'visible') {
    return /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
      ...props,
      children: children
    });
  }
  if (state === 'visually-hidden') {
    return /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
      ...props,
      style: {
        display: 'none'
      },
      children: children
    });
  }
  return null;
};
try {
    // @ts-ignore
    DisplayState.displayName = "DisplayState";
    // @ts-ignore
    DisplayState.__docgenInfo = { "description": "", "displayName": "DisplayState", "props": { "state": { "defaultValue": { value: "visible" }, "description": "", "name": "state", "required": false, "type": { "name": "enum", "value": [{ "value": "\"hidden\"" }, { "value": "\"visible\"" }, { "value": "\"visually-hidden\"" }] } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/display-state/display-state.tsx#DisplayState"] = { docgenInfo: DisplayState.__docgenInfo, name: "DisplayState", path: "../../packages/js/components/src/display-state/display-state.tsx#DisplayState" };
}
catch (__react_docgen_typescript_loader_error) { }
;// ../../packages/js/components/src/collapsible-content/collapsible-content.tsx
/**
 * External dependencies
 */




/**
 * Internal dependencies
 */


const CollapsibleContent = ({
  initialCollapsed = true,
  toggleText,
  children,
  persistRender = false,
  hintText,
  ...props
}) => {
  const [collapsed, setCollapsed] = (0,react.useState)(initialCollapsed);
  const getState = () => {
    if (!collapsed) {
      return 'visible';
    }
    return persistRender ? 'visually-hidden' : 'hidden';
  };
  const collapsibleToggleId = (0,use_instance_id/* default */.A)(CollapsibleContent, 'woocommerce-collapsible-content__toggle');
  const collapsibleContentId = (0,use_instance_id/* default */.A)(CollapsibleContent, 'woocommerce-collapsible-content__content');
  const displayState = getState();
  return /*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
    className: "woocommerce-collapsible-content",
    children: [/*#__PURE__*/(0,jsx_runtime.jsxs)("button", {
      type: "button",
      id: collapsibleToggleId,
      className: "woocommerce-collapsible-content__toggle",
      onClick: () => setCollapsed(!collapsed),
      "aria-expanded": collapsed ? 'false' : 'true',
      "aria-controls": displayState !== 'hidden' ? collapsibleContentId : undefined,
      children: [/*#__PURE__*/(0,jsx_runtime.jsx)("span", {
        children: toggleText
      }), /*#__PURE__*/(0,jsx_runtime.jsx)(icon/* default */.A, {
        icon: collapsed ? chevron_down/* default */.A : chevron_up/* default */.A,
        size: 16
      })]
    }), hintText && /*#__PURE__*/(0,jsx_runtime.jsx)("p", {
      className: "woocommerce-collapsible-content-hint",
      children: hintText
    }), /*#__PURE__*/(0,jsx_runtime.jsx)(DisplayState, {
      state: displayState,
      children: /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
        ...props,
        className: "woocommerce-collapsible-content__content",
        id: collapsibleContentId,
        role: "region",
        "aria-labelledby": collapsibleToggleId,
        children: children
      })
    })]
  });
};
try {
    // @ts-ignore
    CollapsibleContent.displayName = "CollapsibleContent";
    // @ts-ignore
    CollapsibleContent.__docgenInfo = { "description": "", "displayName": "CollapsibleContent", "props": { "initialCollapsed": { "defaultValue": { value: "true" }, "description": "", "name": "initialCollapsed", "required": false, "type": { "name": "boolean" } }, "toggleText": { "defaultValue": null, "description": "", "name": "toggleText", "required": true, "type": { "name": "string" } }, "persistRender": { "defaultValue": { value: "false" }, "description": "", "name": "persistRender", "required": false, "type": { "name": "boolean" } }, "hintText": { "defaultValue": null, "description": "", "name": "hintText", "required": false, "type": { "name": "string" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/collapsible-content/collapsible-content.tsx#CollapsibleContent"] = { docgenInfo: CollapsibleContent.__docgenInfo, name: "CollapsibleContent", path: "../../packages/js/components/src/collapsible-content/collapsible-content.tsx#CollapsibleContent" };
}
catch (__react_docgen_typescript_loader_error) { }
;// ../../packages/js/components/src/collapsible-content/stories/collapsible-content.story.tsx
/**
 * External dependencies
 */

/**
 * Internal dependencies
 */


const Basic = () => {
  return /*#__PURE__*/(0,jsx_runtime.jsx)(CollapsibleContent, {
    toggleText: "Advanced",
    children: "All this business in here is collapsed."
  });
};
const Expanded = () => {
  return /*#__PURE__*/(0,jsx_runtime.jsx)(CollapsibleContent, {
    toggleText: "Advanced",
    initialCollapsed: false,
    children: "All this business in here is initially expanded."
  });
};
/* harmony default export */ const collapsible_content_story = ({
  title: 'Components/CollapsibleContent',
  component: Basic
});
Basic.parameters = {
  ...Basic.parameters,
  docs: {
    ...Basic.parameters?.docs,
    source: {
      originalSource: "() => {\n  return <CollapsibleContent toggleText=\"Advanced\">\n            All this business in here is collapsed.\n        </CollapsibleContent>;\n}",
      ...Basic.parameters?.docs?.source
    }
  }
};
Expanded.parameters = {
  ...Expanded.parameters,
  docs: {
    ...Expanded.parameters?.docs,
    source: {
      originalSource: "() => {\n  return <CollapsibleContent toggleText=\"Advanced\" initialCollapsed={false}>\n            All this business in here is initially expanded.\n        </CollapsibleContent>;\n}",
      ...Expanded.parameters?.docs?.source
    }
  }
};

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

/***/ "../../node_modules/.pnpm/react@18.3.1/node_modules/react/cjs/react-jsx-runtime.production.min.js":
/***/ ((__unused_webpack_module, exports, __webpack_require__) => {

/**
 * @license React
 * react-jsx-runtime.production.min.js
 *
 * Copyright (c) Facebook, Inc. and its affiliates.
 *
 * This source code is licensed under the MIT license found in the
 * LICENSE file in the root directory of this source tree.
 */
var f=__webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js"),k=Symbol.for("react.element"),l=Symbol.for("react.fragment"),m=Object.prototype.hasOwnProperty,n=f.__SECRET_INTERNALS_DO_NOT_USE_OR_YOU_WILL_BE_FIRED.ReactCurrentOwner,p={key:!0,ref:!0,__self:!0,__source:!0};
function q(c,a,g){var b,d={},e=null,h=null;void 0!==g&&(e=""+g);void 0!==a.key&&(e=""+a.key);void 0!==a.ref&&(h=a.ref);for(b in a)m.call(a,b)&&!p.hasOwnProperty(b)&&(d[b]=a[b]);if(c&&c.defaultProps)for(b in a=c.defaultProps,a)void 0===d[b]&&(d[b]=a[b]);return{$$typeof:k,type:c,key:e,ref:h,props:d,_owner:n.current}}exports.Fragment=l;exports.jsx=q;exports.jsxs=q;


/***/ }),

/***/ "../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js":
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {



if (true) {
  module.exports = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/cjs/react-jsx-runtime.production.min.js");
} else {}


/***/ })

}]);