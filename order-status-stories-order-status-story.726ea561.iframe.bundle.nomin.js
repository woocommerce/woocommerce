"use strict";
(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[6322],{

/***/ "../../packages/js/components/src/order-status/stories/order-status.story.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

// ESM COMPAT FLAG
__webpack_require__.r(__webpack_exports__);

// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  Basic: () => (/* binding */ Basic),
  "default": () => (/* binding */ order_status_story)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+i18n@6.6.1/node_modules/@wordpress/i18n/build-module/index.js + 3 modules
var build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@6.6.1/node_modules/@wordpress/i18n/build-module/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs
var clsx = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js
var jsx_runtime = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
;// ../../packages/js/components/src/order-status/index.js
/**
 * External dependencies
 */




/**
 * Use `OrderStatus` to display a badge with human-friendly text describing the current order status.
 *
 * @param {Object}  props
 * @param {Object}  props.order
 * @param {string}  props.order.status
 * @param {string}  props.className
 * @param {Object}  props.orderStatusMap
 * @param {boolean} props.labelPositionToLeft
 * @return {Object} -
 */

const OrderStatus = ({
  order: {
    status
  },
  className,
  orderStatusMap,
  labelPositionToLeft = false
}) => {
  const indicatorClasses = (0,clsx/* default */.A)('woocommerce-order-status__indicator', {
    ['is-' + status]: true
  });
  const label = orderStatusMap[status] || status;
  return /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
    className: (0,clsx/* default */.A)('woocommerce-order-status', className),
    children: labelPositionToLeft ? /*#__PURE__*/(0,jsx_runtime.jsxs)(react.Fragment, {
      children: [label, /*#__PURE__*/(0,jsx_runtime.jsx)("span", {
        className: indicatorClasses
      })]
    }) : /*#__PURE__*/(0,jsx_runtime.jsxs)(react.Fragment, {
      children: [/*#__PURE__*/(0,jsx_runtime.jsx)("span", {
        className: indicatorClasses
      }), label]
    })
  });
};
/* harmony default export */ const order_status = (OrderStatus);
;
OrderStatus.__docgenInfo = {
  "description": "Use `OrderStatus` to display a badge with human-friendly text describing the current order status.\n\n@param {Object}  props\n@param {Object}  props.order\n@param {string}  props.order.status\n@param {string}  props.className\n@param {Object}  props.orderStatusMap\n@param {boolean} props.labelPositionToLeft\n@return {Object} -",
  "methods": [],
  "displayName": "OrderStatus",
  "props": {
    "labelPositionToLeft": {
      "defaultValue": {
        "value": "false",
        "computed": false
      },
      "required": false
    },
    "order": {
      "description": "The order to display a status for.",
      "type": {
        "name": "object"
      },
      "required": true
    },
    "className": {
      "description": "Additional CSS classes.",
      "type": {
        "name": "string"
      },
      "required": false
    },
    "orderStatusMap": {
      "description": "A map of status to label for order statuses.",
      "type": {
        "name": "object"
      },
      "required": false
    }
  }
};
;// ../../packages/js/components/src/order-status/stories/order-status.story.js
/**
 * External dependencies
 */



const orderStatusMap = {
  processing: (0,build_module.__)('Processing Order', 'woocommerce'),
  pending: (0,build_module.__)('Pending Order', 'woocommerce'),
  completed: (0,build_module.__)('Completed Order', 'woocommerce')
};
const Basic = () => /*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
  children: [/*#__PURE__*/(0,jsx_runtime.jsx)(order_status, {
    order: {
      status: 'processing'
    },
    orderStatusMap: orderStatusMap
  }), /*#__PURE__*/(0,jsx_runtime.jsx)(order_status, {
    order: {
      status: 'pending'
    },
    orderStatusMap: orderStatusMap
  }), /*#__PURE__*/(0,jsx_runtime.jsx)(order_status, {
    order: {
      status: 'completed'
    },
    orderStatusMap: orderStatusMap
  })]
});
/* harmony default export */ const order_status_story = ({
  title: 'Components/OrderStatus',
  component: order_status
});
Basic.parameters = {
  ...Basic.parameters,
  docs: {
    ...Basic.parameters?.docs,
    source: {
      originalSource: "() => <div>\n        <OrderStatus order={{\n    status: 'processing'\n  }} orderStatusMap={orderStatusMap} />\n        <OrderStatus order={{\n    status: 'pending'\n  }} orderStatusMap={orderStatusMap} />\n        <OrderStatus order={{\n    status: 'completed'\n  }} orderStatusMap={orderStatusMap} />\n    </div>",
      ...Basic.parameters?.docs?.source
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