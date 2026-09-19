"use strict";
(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[4222],{

/***/ "../../packages/js/components/src/web-preview/stories/web-preview.story.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

// ESM COMPAT FLAG
__webpack_require__.r(__webpack_exports__);

// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  Basic: () => (/* binding */ Basic),
  "default": () => (/* binding */ web_preview_story)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs
var clsx = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/lodash@4.18.1/node_modules/lodash/lodash.js
var lodash = __webpack_require__("../../node_modules/.pnpm/lodash@4.18.1/node_modules/lodash/lodash.js");
// EXTERNAL MODULE: ../../packages/js/components/src/spinner/index.js
var spinner = __webpack_require__("../../packages/js/components/src/spinner/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js
var jsx_runtime = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
;// ../../packages/js/components/src/web-preview/index.js
/**
 * External dependencies
 */





/**
 * Internal dependencies
 */


/**
 * WebPreview component to display an iframe of another page.
 */

class WebPreview extends react.Component {
  constructor(props) {
    super(props);
    this.state = {
      isLoading: true
    };
    this.iframeRef = (0,react.createRef)();
    this.setLoaded = this.setLoaded.bind(this);
  }
  componentDidMount() {
    this.iframeRef.current.addEventListener('load', this.setLoaded);
  }
  setLoaded() {
    this.setState({
      isLoading: false
    });
    this.props.onLoad();
  }
  render() {
    const {
      className,
      loadingContent,
      src,
      title
    } = this.props;
    const {
      isLoading
    } = this.state;
    const classes = (0,clsx/* default */.A)('woocommerce-web-preview', className, {
      'is-loading': isLoading
    });
    return /*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
      className: classes,
      children: [isLoading && loadingContent, /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
        className: "woocommerce-web-preview__iframe-wrapper",
        children: /*#__PURE__*/(0,jsx_runtime.jsx)("iframe", {
          ref: this.iframeRef,
          title: title,
          src: src
        })
      })]
    });
  }
}
WebPreview.defaultProps = {
  loadingContent: /*#__PURE__*/(0,jsx_runtime.jsx)(spinner/* default */.A, {}),
  onLoad: lodash.noop
};
/* harmony default export */ const web_preview = (WebPreview);
;
WebPreview.__docgenInfo = {
  "description": "WebPreview component to display an iframe of another page.",
  "methods": [{
    "name": "setLoaded",
    "docblock": null,
    "modifiers": [],
    "params": [],
    "returns": null
  }],
  "displayName": "WebPreview",
  "props": {
    "loadingContent": {
      "defaultValue": {
        "value": "<Spinner />",
        "computed": false
      },
      "description": "Content shown when iframe is still loading.",
      "type": {
        "name": "node"
      },
      "required": false
    },
    "onLoad": {
      "defaultValue": {
        "value": "noop",
        "computed": true
      },
      "description": "Function to fire when iframe content is loaded.",
      "type": {
        "name": "func"
      },
      "required": false
    },
    "className": {
      "description": "Additional class name to style the component.",
      "type": {
        "name": "string"
      },
      "required": false
    },
    "src": {
      "description": "Iframe src to load.",
      "type": {
        "name": "string"
      },
      "required": true
    },
    "title": {
      "description": "Iframe title.",
      "type": {
        "name": "string"
      },
      "required": true
    }
  }
};
;// ../../packages/js/components/src/web-preview/stories/web-preview.story.js
/**
 * External dependencies
 */


const Basic = () => /*#__PURE__*/(0,jsx_runtime.jsx)(web_preview, {
  src: "https://themes.woocommerce.com/?name=galleria",
  title: "My Web Preview"
});
/* harmony default export */ const web_preview_story = ({
  title: 'Components/WebPreview',
  component: web_preview
});
Basic.parameters = {
  ...Basic.parameters,
  docs: {
    ...Basic.parameters?.docs,
    source: {
      originalSource: "() => <WebPreview src=\"https://themes.woocommerce.com/?name=galleria\" title=\"My Web Preview\" />",
      ...Basic.parameters?.docs?.source
    }
  }
};

/***/ }),

/***/ "../../packages/js/components/src/spinner/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var clsx__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
/**
 * External dependencies
 */




/**
 * Spinner - An indeterminate circular progress indicator.
 */

class Spinner extends _wordpress_element__WEBPACK_IMPORTED_MODULE_1__.Component {
  render() {
    const {
      className
    } = this.props;
    const classes = (0,clsx__WEBPACK_IMPORTED_MODULE_2__/* ["default"] */ .A)('woocommerce-spinner', className);
    return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("svg", {
      className: classes,
      viewBox: "0 0 100 100",
      xmlns: "http://www.w3.org/2000/svg",
      children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("circle", {
        className: "woocommerce-spinner__circle",
        fill: "none",
        strokeWidth: "5",
        strokeLinecap: "round",
        cx: "50",
        cy: "50",
        r: "30"
      })
    });
  }
}
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (Spinner);
;
Spinner.__docgenInfo = {
  "description": "Spinner - An indeterminate circular progress indicator.",
  "methods": [],
  "displayName": "Spinner",
  "props": {
    "className": {
      "description": "Additional class name to style the component.",
      "type": {
        "name": "string"
      },
      "required": false
    }
  }
};

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