"use strict";
(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[9206],{

/***/ "../../node_modules/.pnpm/@storybook+react-dom-shim@1_ebda121423b406c137870dfe401873a9/node_modules/@storybook/react-dom-shim/dist/react-18.js"
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  renderElement: () => (/* binding */ renderElement),
  unmountElement: () => (/* binding */ unmountElement)
});

// MODULE: ../../node_modules/.pnpm/react-dom@18.3.1_react@18.3.1/node_modules/react-dom/client.js
var client_namespaceFn = /*#__PURE__*/__webpack_require__.cw((__unused_webpack_module, exports) => {
var __webpack_unused_export__;


var m = (react_dom_namespaceFn());
if (true) {
  exports.H = m.createRoot;
  __webpack_unused_export__ = m.hydrateRoot;
} else // removed by dead control flow
{ var i; }

});

// EXTERNAL MODULE: ../../node_modules/.pnpm/react-dom@18.3.1_react@18.3.1/node_modules/react-dom/index.js
var react_dom_namespaceFn = () => {
	return __webpack_require__("../../node_modules/.pnpm/react-dom@18.3.1_react@18.3.1/node_modules/react-dom/index.js");
};

// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
;// ../../node_modules/.pnpm/react-dom@18.3.1_react@18.3.1/node_modules/react-dom/client.js
client_namespaceFn();

;// ../../node_modules/.pnpm/@storybook+react-dom-shim@1_ebda121423b406c137870dfe401873a9/node_modules/@storybook/react-dom-shim/dist/react-18.js
// src/react-18.tsx


var nodes = /* @__PURE__ */ new Map();
function getIsReactActEnvironment() {
  return globalThis.IS_REACT_ACT_ENVIRONMENT;
}
var WithCallback = ({
  callback,
  children
}) => {
  let once = react.useRef();
  return react.useLayoutEffect(() => {
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
  return root.render(react.createElement(WithCallback, { callback: resolve }, node)), promise;
}, unmountElement = (el, shouldUseNewRootApi) => {
  let root = nodes.get(el);
  root && (root.unmount(), nodes.delete(el));
}, getReactRoot = async (el, rootOptions) => {
  let root = nodes.get(el);
  return root || (root = (client_namespaceFn().H)(el, rootOptions), nodes.set(el, root)), root;
};



/***/ }

}]);