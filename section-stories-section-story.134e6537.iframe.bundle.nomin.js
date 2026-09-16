"use strict";
(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[7714],{

/***/ "../../packages/js/components/src/section/stories/section.story.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   Basic: () => (/* binding */ Basic),
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../packages/js/components/src/section/header.tsx");
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../packages/js/components/src/section/section.tsx");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
/**
 * External dependencies
 */


const Basic = () => /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsxs)("div", {
  children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__.H, {
    children: "Header using a contextual level (h3)"
  }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsxs)(_woocommerce_components__WEBPACK_IMPORTED_MODULE_2__/* .Section */ .w, {
    component: "article",
    children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("p", {
      children: "This is an article component wrapper."
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__.H, {
      children: "Another header with contextual level (h4)"
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsxs)(_woocommerce_components__WEBPACK_IMPORTED_MODULE_2__/* .Section */ .w, {
      component: false,
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("p", {
        children: "There is no wrapper component here."
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__.H, {
        children: "This is an h5"
      })]
    })]
  })]
});
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  title: 'Components/Section',
  component: _woocommerce_components__WEBPACK_IMPORTED_MODULE_2__/* .Section */ .w
});
Basic.parameters = {
  ...Basic.parameters,
  docs: {
    ...Basic.parameters?.docs,
    source: {
      originalSource: "() => <div>\n        <H>Header using a contextual level (h3)</H>\n        <Section component=\"article\">\n            <p>This is an article component wrapper.</p>\n            <H>Another header with contextual level (h4)</H>\n            <Section component={false}>\n                <p>There is no wrapper component here.</p>\n                <H>This is an h5</H>\n            </Section>\n        </Section>\n    </div>",
      ...Basic.parameters?.docs?.source
    }
  }
};

/***/ }),

/***/ "../../packages/js/components/src/section/context.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   $: () => (/* binding */ Level)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/**
 * External dependencies
 */


/**
 * Context container for heading level. We start at 2 because the `h1` is defined in <Header />
 *
 * See https://medium.com/@Heydon/managing-heading-levels-in-design-systems-18be9a746fa3
 */
const Level = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createContext)(2);

try {
    // @ts-ignore
    Context.displayName = "Context";
    // @ts-ignore
    Context.__docgenInfo = { "description": "Context lets components pass information deep down without explicitly\npassing props.\n\nCreated from {@link createContext}", "displayName": "Context", "props": {} };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/section/context.tsx#Context"] = { docgenInfo: Context.__docgenInfo, name: "Context", path: "../../packages/js/components/src/section/context.tsx#Context" };
}
catch (__react_docgen_typescript_loader_error) { }

/***/ }),

/***/ "../../packages/js/components/src/section/header.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   H: () => (/* binding */ H)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var _context__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../packages/js/components/src/section/context.tsx");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
/**
 * External dependencies
 */


/**
 * Internal dependencies
 */


/**
 * These components are used to frame out the page content for accessible heading hierarchy. Instead of defining fixed heading levels
 * (`h2`, `h3`, …) you can use `<H />` to create "section headings", which look to the parent `<Section />`s for the appropriate
 * heading level.
 *
 * @type {HTMLElement}
 */

function H(props) {
  const level = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useContext)(_context__WEBPACK_IMPORTED_MODULE_2__/* .Level */ .$);
  const Heading = 'h' + Math.min(level, 6);
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(Heading, {
    ...props
  });
}
try {
    // @ts-ignore
    H.displayName = "H";
    // @ts-ignore
    H.__docgenInfo = { "description": "These components are used to frame out the page content for accessible heading hierarchy. Instead of defining fixed heading levels\n(`h2`, `h3`, \u2026) you can use `<H />` to create \"section headings\", which look to the parent `<Section />`s for the appropriate\nheading level.", "displayName": "H", "props": {} };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/section/header.tsx#H"] = { docgenInfo: H.__docgenInfo, name: "H", path: "../../packages/js/components/src/section/header.tsx#H" };
}
catch (__react_docgen_typescript_loader_error) { }

/***/ }),

/***/ "../../packages/js/components/src/section/section.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   w: () => (/* binding */ Section)
/* harmony export */ });
/* harmony import */ var _context__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../packages/js/components/src/section/context.tsx");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
/**
 * External dependencies
 */

/**
 * Internal dependencies
 */


/**
 * The section wrapper, used to indicate a sub-section (and change the header level context).
 */
const Section = ({
  component,
  children,
  ...props
}) => {
  const Component = component || 'div';
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(_context__WEBPACK_IMPORTED_MODULE_1__/* .Level */ .$.Consumer, {
    children: level => /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(_context__WEBPACK_IMPORTED_MODULE_1__/* .Level */ .$.Provider, {
      value: level + 1,
      children: component === false ? children : /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(Component, {
        ...props,
        children: children
      })
    })
  });
};
try {
    // @ts-ignore
    Section.displayName = "Section";
    // @ts-ignore
    Section.__docgenInfo = { "description": "The section wrapper, used to indicate a sub-section (and change the header level context).", "displayName": "Section", "props": { "component": { "defaultValue": null, "description": "The wrapper component for this section. Optional, defaults to `div`. If passed false, no wrapper is used. Additional props passed to Section are passed on to the component.", "name": "component", "required": false, "type": { "name": "string | false | ComponentType<{ className?: string; }>" } }, "className": { "defaultValue": null, "description": "Optional classname", "name": "className", "required": false, "type": { "name": "string" } }, "children": { "defaultValue": null, "description": "The children inside this section, rendered in the `component`. This increases the context level for the next heading used.", "name": "children", "required": true, "type": { "name": "ReactNode" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/section/section.tsx#Section"] = { docgenInfo: Section.__docgenInfo, name: "Section", path: "../../packages/js/components/src/section/section.tsx#Section" };
}
catch (__react_docgen_typescript_loader_error) { }

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