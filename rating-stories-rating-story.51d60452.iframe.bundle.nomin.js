"use strict";
(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[1346],{

/***/ "../../packages/js/components/src/rating/stories/rating.story.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

// ESM COMPAT FLAG
__webpack_require__.r(__webpack_exports__);

// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  Default: () => (/* binding */ Default),
  "default": () => (/* binding */ rating_story)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+i18n@6.6.1/node_modules/@wordpress/i18n/build-module/index.js + 3 modules
var build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@6.6.1/node_modules/@wordpress/i18n/build-module/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs
var clsx = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/gridicons@3.4.2_react@18.3.1/node_modules/gridicons/dist/star.js
var star = __webpack_require__("../../node_modules/.pnpm/gridicons@3.4.2_react@18.3.1/node_modules/gridicons/dist/star.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js
var jsx_runtime = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
;// ../../packages/js/components/src/rating/index.tsx
/**
 * External dependencies
 */




/**
 * Use `Rating` to display a set of stars, filled, empty or half-filled, that represents a
 * rating in a scale between 0 and the prop `totalStars` (default 5).
 */
const rating_Rating = ({
  rating = 0,
  totalStars = 5,
  size = 18,
  className,
  icon,
  outlineIcon
}) => {
  const stars = _icon => {
    const starStyles = {
      width: size + 'px',
      height: size + 'px'
    };
    const _stars = [];
    for (let i = 0; i < totalStars; i++) {
      const Icon = _icon || star/* default */.A;
      _stars.push(/*#__PURE__*/(0,jsx_runtime.jsx)(Icon, {
        style: starStyles
      }, 'star-' + i));
    }
    return _stars;
  };
  const classes = (0,clsx/* default */.A)('woocommerce-rating', className);
  const perStar = 100 / totalStars;
  const outlineStyles = {
    width: Math.round(perStar * rating) + '%'
  };
  const label = (0,build_module/* sprintf */.nv)(/* translators: %1$s: rating, %2$s: total number of stars */
  (0,build_module.__)('%1$s out of %2$s stars.', 'woocommerce'), rating.toString(), totalStars.toString());
  return /*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
    className: classes,
    "aria-label": label,
    children: [stars(icon), /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
      className: "woocommerce-rating__star-outline",
      style: outlineStyles,
      children: stars(outlineIcon || icon)
    })]
  });
};
/* harmony default export */ const src_rating = (rating_Rating);
try {
    // @ts-ignore
    rating.displayName = "rating";
    // @ts-ignore
    rating.__docgenInfo = { "description": "Use `Rating` to display a set of stars, filled, empty or half-filled, that represents a\nrating in a scale between 0 and the prop `totalStars` (default 5).", "displayName": "rating", "props": { "rating": { "defaultValue": { value: "0" }, "description": "", "name": "rating", "required": false, "type": { "name": "number" } }, "totalStars": { "defaultValue": { value: "5" }, "description": "", "name": "totalStars", "required": false, "type": { "name": "number" } }, "size": { "defaultValue": { value: "18" }, "description": "", "name": "size", "required": false, "type": { "name": "number" } }, "className": { "defaultValue": null, "description": "", "name": "className", "required": false, "type": { "name": "string" } }, "icon": { "defaultValue": null, "description": "", "name": "icon", "required": false, "type": { "name": "ReactNode" } }, "outlineIcon": { "defaultValue": null, "description": "", "name": "outlineIcon", "required": false, "type": { "name": "ReactNode" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/rating/index.tsx#rating"] = { docgenInfo: rating.__docgenInfo, name: "rating", path: "../../packages/js/components/src/rating/index.tsx#rating" };
}
catch (__react_docgen_typescript_loader_error) { }
;// ../../packages/js/components/src/rating/stories/rating.story.tsx
/**
 * External dependencies
 */

/**
 * Internal dependencies
 */


/* harmony default export */ const rating_story = ({
  title: 'Components/Rating',
  component: src_rating,
  args: {
    rating: 4.5,
    totalStars: 5,
    size: 18
  }
});
const Default = args => /*#__PURE__*/(0,jsx_runtime.jsx)(src_rating, {
  ...args
});
Default.parameters = {
  ...Default.parameters,
  docs: {
    ...Default.parameters?.docs,
    source: {
      originalSource: "args => <Rating {...args} />",
      ...Default.parameters?.docs?.source
    }
  }
};
try {
    // @ts-ignore
    Rating.displayName = "Rating";
    // @ts-ignore
    Rating.__docgenInfo = { "description": "Use `Rating` to display a set of stars, filled, empty or half-filled, that represents a\nrating in a scale between 0 and the prop `totalStars` (default 5).", "displayName": "Rating", "props": { "rating": { "defaultValue": { value: "0" }, "description": "", "name": "rating", "required": false, "type": { "name": "number" } }, "totalStars": { "defaultValue": { value: "5" }, "description": "", "name": "totalStars", "required": false, "type": { "name": "number" } }, "size": { "defaultValue": { value: "18" }, "description": "", "name": "size", "required": false, "type": { "name": "number" } }, "className": { "defaultValue": null, "description": "", "name": "className", "required": false, "type": { "name": "string" } }, "icon": { "defaultValue": null, "description": "", "name": "icon", "required": false, "type": { "name": "ReactNode" } }, "outlineIcon": { "defaultValue": null, "description": "", "name": "outlineIcon", "required": false, "type": { "name": "ReactNode" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/rating/stories/rating.story.tsx#Rating"] = { docgenInfo: Rating.__docgenInfo, name: "Rating", path: "../../packages/js/components/src/rating/stories/rating.story.tsx#Rating" };
}
catch (__react_docgen_typescript_loader_error) { }
try {
    // @ts-ignore
    Default.displayName = "Default";
    // @ts-ignore
    Default.__docgenInfo = { "description": "", "displayName": "Default", "props": {} };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/rating/stories/rating.story.tsx#Default"] = { docgenInfo: Default.__docgenInfo, name: "Default", path: "../../packages/js/components/src/rating/stories/rating.story.tsx#Default" };
}
catch (__react_docgen_typescript_loader_error) { }

/***/ }),

/***/ "../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* unused harmony export clsx */
function r(e){var t,f,n="";if("string"==typeof e||"number"==typeof e)n+=e;else if("object"==typeof e)if(Array.isArray(e)){var o=e.length;for(t=0;t<o;t++)e[t]&&(f=r(e[t]))&&(n&&(n+=" "),n+=f)}else for(f in e)e[f]&&(n&&(n+=" "),n+=f);return n}function clsx(){for(var e,t,f=0,n="",o=arguments.length;f<o;f++)(e=arguments[f])&&(t=r(e))&&(n&&(n+=" "),n+=t);return n}/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (clsx);

/***/ }),

/***/ "../../node_modules/.pnpm/gridicons@3.4.2_react@18.3.1/node_modules/gridicons/dist/star.js":
/***/ ((__unused_webpack_module, exports, __webpack_require__) => {

var __webpack_unused_export__;
__webpack_unused_export__ = ({value:!0}),exports.A=_default;var _react=_interopRequireDefault(__webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js")),_excluded=["size","onClick","icon","className"];function _interopRequireDefault(a){return a&&a.__esModule?a:{default:a}}function _extends(){return _extends=Object.assign?Object.assign.bind():function(a){for(var b,c=1;c<arguments.length;c++)for(var d in b=arguments[c],b)Object.prototype.hasOwnProperty.call(b,d)&&(a[d]=b[d]);return a},_extends.apply(this,arguments)}function _objectWithoutProperties(a,b){if(null==a)return{};var c,d,e=_objectWithoutPropertiesLoose(a,b);if(Object.getOwnPropertySymbols){var f=Object.getOwnPropertySymbols(a);for(d=0;d<f.length;d++)c=f[d],0<=b.indexOf(c)||Object.prototype.propertyIsEnumerable.call(a,c)&&(e[c]=a[c])}return e}function _objectWithoutPropertiesLoose(a,b){if(null==a)return{};var c,d,e={},f=Object.keys(a);for(d=0;d<f.length;d++)c=f[d],0<=b.indexOf(c)||(e[c]=a[c]);return e}function _default(a){var b=a.size,c=void 0===b?24:b,d=a.onClick,e=a.icon,f=a.className,g=_objectWithoutProperties(a,_excluded),h=["gridicon","gridicons-star",f,!!function isModulo18(a){return 0==a%18}(c)&&"needs-offset",!1,!1].filter(Boolean).join(" ");return _react["default"].createElement("svg",_extends({className:h,height:c,width:c,onClick:d},g,{xmlns:"http://www.w3.org/2000/svg",viewBox:"0 0 24 24"}),_react["default"].createElement("g",null,_react["default"].createElement("path",{d:"M12 2l2.582 6.953L22 9.257l-5.822 4.602L18.18 21 12 16.891 5.82 21l2.002-7.141L2 9.257l7.418-.304z"})))}


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