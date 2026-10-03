"use strict";
(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[1620],{

/***/ "../../node_modules/.pnpm/@babel+runtime@7.25.7/node_modules/@babel/runtime/helpers/esm/extends.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (/* binding */ _extends)
/* harmony export */ });
function _extends() {
  return _extends = Object.assign ? Object.assign.bind() : function (n) {
    for (var e = 1; e < arguments.length; e++) {
      var t = arguments[e];
      for (var r in t) ({}).hasOwnProperty.call(t, r) && (n[r] = t[r]);
    }
    return n;
  }, _extends.apply(null, arguments);
}


/***/ }),

/***/ "../../packages/js/components/src/link/stories/link.story.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   External: () => (/* binding */ External),
/* harmony export */   WCAdmin: () => (/* binding */ WCAdmin),
/* harmony export */   WPAdmin: () => (/* binding */ WPAdmin),
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var ___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../packages/js/components/src/link/index.tsx");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
/**
 * External dependencies
 */

/**
 * Internal dependencies
 */


function logLinkClick(event) {
  const a = event.currentTarget;
  const logMessage = `[${a.textContent}](${a.href}) ${a.dataset.linkType} link clicked`;

  // eslint-disable-next-line no-console
  console.log(logMessage);
  event.preventDefault();
  return false;
}
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  title: 'Components/Link',
  component: ___WEBPACK_IMPORTED_MODULE_1__/* ["default"] */ .A
});
const External = () => {
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(___WEBPACK_IMPORTED_MODULE_1__/* ["default"] */ .A, {
    href: "https://woocommerce.com",
    type: "external",
    onClick: logLinkClick,
    children: "WooCommerce.com"
  });
};
const WCAdmin = () => {
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(___WEBPACK_IMPORTED_MODULE_1__/* ["default"] */ .A, {
    href: "admin.php?page=wc-admin&path=%2Fanalytics%2Forders",
    type: "wc-admin",
    onClick: logLinkClick,
    children: "Analytics: Orders"
  });
};
const WPAdmin = () => {
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(___WEBPACK_IMPORTED_MODULE_1__/* ["default"] */ .A, {
    href: "post-new.php?post_type=product",
    type: "wp-admin",
    onClick: logLinkClick,
    children: "New Product"
  });
};
External.parameters = {
  ...External.parameters,
  docs: {
    ...External.parameters?.docs,
    source: {
      originalSource: "() => {\n  return <Link href=\"https://woocommerce.com\" type=\"external\" onClick={logLinkClick}>\n            WooCommerce.com\n        </Link>;\n}",
      ...External.parameters?.docs?.source
    }
  }
};
WCAdmin.parameters = {
  ...WCAdmin.parameters,
  docs: {
    ...WCAdmin.parameters?.docs,
    source: {
      originalSource: "() => {\n  return <Link href=\"admin.php?page=wc-admin&path=%2Fanalytics%2Forders\" type=\"wc-admin\" onClick={logLinkClick}>\n            Analytics: Orders\n        </Link>;\n}",
      ...WCAdmin.parameters?.docs?.source
    }
  }
};
WPAdmin.parameters = {
  ...WPAdmin.parameters,
  docs: {
    ...WPAdmin.parameters?.docs,
    source: {
      originalSource: "() => {\n  return <Link href=\"post-new.php?post_type=product\" type=\"wp-admin\" onClick={logLinkClick}>\n            New Product\n        </Link>;\n}",
      ...WPAdmin.parameters?.docs?.source
    }
  }
};
try {
    // @ts-ignore
    Link.displayName = "Link";
    // @ts-ignore
    Link.__docgenInfo = { "description": "Use `Link` to create a link to another resource. It accepts a type to automatically\ncreate wp-admin links, wc-admin links, and external links.", "displayName": "Link", "props": {} };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/link/stories/link.story.tsx#Link"] = { docgenInfo: Link.__docgenInfo, name: "Link", path: "../../packages/js/components/src/link/stories/link.story.tsx#Link" };
}
catch (__react_docgen_typescript_loader_error) { }

/***/ }),

/***/ "../../packages/js/components/src/link/index.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (__WEBPACK_DEFAULT_EXPORT__),
/* harmony export */   N: () => (/* binding */ Link)
/* harmony export */ });
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/lodash@4.18.1/node_modules/lodash/lodash.js");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _woocommerce_navigation__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../packages/js/navigation/src/index.js");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
/**
 * External dependencies
 */



// eslint-disable-next-line @typescript-eslint/no-explicit-any
// we don't want to restrict this function at all

/**
 * Use `Link` to create a link to another resource. It accepts a type to automatically
 * create wp-admin links, wc-admin links, and external links.
 */
const Link = ({
  href,
  children,
  type = 'wc-admin',
  ...props
}) => {
  // ( { children, href, type, ...props } ) => {
  // @todo Investigate further if we can use <Link /> directly.
  // With React Router 5+, <RouterLink /> cannot be used outside of the main <Router /> elements,
  // which seems to include components imported from @woocommerce/components. For now, we can use the history object directly.
  const wcAdminLinkHandler = (onClick, event) => {
    // If cmd, ctrl, alt, or shift are used, use default behavior to allow opening in a new tab.
    if (event?.ctrlKey || event?.metaKey || event?.altKey || event?.shiftKey) {
      return;
    }
    event?.preventDefault();

    // If there is an onclick event, execute it.
    const onClickResult = onClick && event ? onClick(event) : true;

    // Mimic browser behavior and only continue if onClickResult is not explicitly false.
    if (onClickResult === false) {
      return;
    }
    if (event?.target instanceof Element) {
      const closestEventTarget = event.target.closest('a')?.getAttribute('href');
      if (closestEventTarget) {
        (0,_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_1__/* .getHistory */ .JK)().push(closestEventTarget);
      } else {
        // eslint-disable-next-line no-console
        console.error('@woocommerce/components/link is trying to push an undefined state into navigation stack'); // This shouldn't happen as we wrap with <a> below
      }
    }
  };
  const passProps = {
    ...props,
    'data-link-type': type
  };
  if (type === 'wc-admin') {
    passProps.onClick = (0,lodash__WEBPACK_IMPORTED_MODULE_0__.partial)(wcAdminLinkHandler, passProps.onClick);
  }
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("a", {
    href: href,
    ...passProps,
    children: children
  });
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (Link);
try {
    // @ts-ignore
    Link.displayName = "Link";
    // @ts-ignore
    Link.__docgenInfo = { "description": "Use `Link` to create a link to another resource. It accepts a type to automatically\ncreate wp-admin links, wc-admin links, and external links.", "displayName": "Link", "props": {} };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/link/index.tsx#Link"] = { docgenInfo: Link.__docgenInfo, name: "Link", path: "../../packages/js/components/src/link/index.tsx#Link" };
}
catch (__react_docgen_typescript_loader_error) { }
try {
    // @ts-ignore
    link.displayName = "link";
    // @ts-ignore
    link.__docgenInfo = { "description": "Use `Link` to create a link to another resource. It accepts a type to automatically\ncreate wp-admin links, wc-admin links, and external links.", "displayName": "link", "props": {} };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/link/index.tsx#link"] = { docgenInfo: link.__docgenInfo, name: "link", path: "../../packages/js/components/src/link/index.tsx#link" };
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