"use strict";
(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[1910,2522],{

/***/ "../../node_modules/.pnpm/@storybook+addon-docs@10.5._85f129379a90bb78984532142b2134db/node_modules/@storybook/addon-docs/dist/_browser-chunks/DocsRenderer-JROSPFPF.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  DocsRenderer: () => (/* reexport */ DocsRenderer)
});

// UNUSED EXPORTS: defaultComponents

// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@storybook+react-dom-shim@1_eb30881cc954c67b36a9fc52cf34a1a7/node_modules/@storybook/react-dom-shim/dist/react-18.js
var react_18 = __webpack_require__("../../node_modules/.pnpm/@storybook+react-dom-shim@1_eb30881cc954c67b36a9fc52cf34a1a7/node_modules/@storybook/react-dom-shim/dist/react-18.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@storybook+addon-docs@10.5._85f129379a90bb78984532142b2134db/node_modules/@storybook/addon-docs/dist/blocks.js + 4 modules
var blocks = __webpack_require__("../../node_modules/.pnpm/@storybook+addon-docs@10.5._85f129379a90bb78984532142b2134db/node_modules/@storybook/addon-docs/dist/blocks.js");
;// ../../node_modules/.pnpm/@storybook+addon-docs@10.5._85f129379a90bb78984532142b2134db/node_modules/@storybook/addon-docs/dist/_browser-chunks/chunk-OATZR77O.js
// src/DocsRenderer.tsx



var defaultComponents = {
  code: blocks/* CodeOrSourceMdx */.XA,
  a: blocks/* AnchorMdx */.zE,
  ...blocks/* HeadersMdx */.Sw
}, ErrorBoundary = class extends react.Component {
  constructor() {
    super(...arguments);
    this.state = { hasError: !1 };
  }
  static getDerivedStateFromError() {
    return { hasError: !0 };
  }
  componentDidCatch(err) {
    let { showException } = this.props;
    showException(err);
  }
  render() {
    let { hasError } = this.state, { children } = this.props;
    return hasError ? null : react.createElement(react.Fragment, null, children);
  }
}, DocsRenderer = class {
  constructor() {
    this.render = async (context, docsParameter, element) => {
      let components = {
        ...defaultComponents,
        ...docsParameter?.components
      }, TDocs = blocks/* Docs */.kQ;
      return new Promise((resolve, reject) => {
        __webpack_require__.e(/* import() */ 4664).then(__webpack_require__.bind(__webpack_require__, "../../node_modules/.pnpm/@mdx-js+react@3.1.1_@types+react@18.3.28_react@18.3.1/node_modules/@mdx-js/react/index.js")).then(
          ({ MDXProvider }) => (
            // We use a `key={}` here to reset the `hasError` state each time we render ErrorBoundary
            (0,react_18.renderElement)(
              react.createElement(ErrorBoundary, { showException: reject, key: Math.random() }, react.createElement(MDXProvider, { components }, react.createElement(TDocs, { context, docsParameter }))),
              element
            )
          )
        ).then(() => resolve());
      });
    }, this.unmount = (element) => {
      (0,react_18.unmountElement)(element);
    };
  }
};



// EXTERNAL MODULE: ../../node_modules/.pnpm/@storybook+addon-docs@10.5._85f129379a90bb78984532142b2134db/node_modules/@storybook/addon-docs/dist/_browser-chunks/chunk-UAWMPV5J.js
var chunk_UAWMPV5J = __webpack_require__("../../node_modules/.pnpm/@storybook+addon-docs@10.5._85f129379a90bb78984532142b2134db/node_modules/@storybook/addon-docs/dist/_browser-chunks/chunk-UAWMPV5J.js");
;// ../../node_modules/.pnpm/@storybook+addon-docs@10.5._85f129379a90bb78984532142b2134db/node_modules/@storybook/addon-docs/dist/_browser-chunks/DocsRenderer-JROSPFPF.js





/***/ }),

/***/ "../../node_modules/.pnpm/@storybook+react-dom-shim@1_eb30881cc954c67b36a9fc52cf34a1a7/node_modules/@storybook/react-dom-shim/dist/react-18.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   renderElement: () => (/* binding */ renderElement),
/* harmony export */   unmountElement: () => (/* binding */ unmountElement)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var react_dom_client__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/react-dom@18.3.1_react@18.3.1/node_modules/react-dom/client.js");
// src/react-18.tsx


var nodes = /* @__PURE__ */ new Map();
function getIsReactActEnvironment() {
  return globalThis.IS_REACT_ACT_ENVIRONMENT;
}
var WithCallback = ({
  callback,
  children
}) => {
  let once = react__WEBPACK_IMPORTED_MODULE_0__.useRef();
  return react__WEBPACK_IMPORTED_MODULE_0__.useLayoutEffect(() => {
    once.current !== callback && (once.current = callback, callback());
  }, [callback]), children;
};
typeof Promise.withResolvers > "u" && (Promise.withResolvers = () => {
  let resolve = null, reject = null;
  return { promise: new Promise((res, rej) => {
    resolve = res, reject = rej;
  }), resolve, reject };
});
var renderElement = async (node, el, rootOptions) => {
  let root = await getReactRoot(el, rootOptions);
  if (getIsReactActEnvironment()) {
    root.render(node);
    return;
  }
  let { promise, resolve } = Promise.withResolvers();
  return root.render(react__WEBPACK_IMPORTED_MODULE_0__.createElement(WithCallback, { callback: resolve }, node)), promise;
}, unmountElement = (el, shouldUseNewRootApi) => {
  let root = nodes.get(el);
  root && (root.unmount(), nodes.delete(el));
}, getReactRoot = async (el, rootOptions) => {
  let root = nodes.get(el);
  return root || (root = react_dom_client__WEBPACK_IMPORTED_MODULE_1__/* .createRoot */ .H(el, rootOptions), nodes.set(el, root)), root;
};



/***/ }),

/***/ "../../node_modules/.pnpm/react-dom@18.3.1_react@18.3.1/node_modules/react-dom/client.js":
/***/ ((__unused_webpack_module, exports, __webpack_require__) => {

var __webpack_unused_export__;


var m = __webpack_require__("../../node_modules/.pnpm/react-dom@18.3.1_react@18.3.1/node_modules/react-dom/index.js");
if (true) {
  exports.H = m.createRoot;
  __webpack_unused_export__ = m.hydrateRoot;
} else { var i; }


/***/ })

}]);