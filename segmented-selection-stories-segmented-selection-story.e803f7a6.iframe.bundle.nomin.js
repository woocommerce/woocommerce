"use strict";
(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[2390],{

/***/ "../../packages/js/components/src/segmented-selection/stories/segmented-selection.story.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   Basic: () => (/* binding */ Basic),
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../packages/js/components/src/segmented-selection/index.js");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
/**
 * External dependencies
 */



const name = 'number';
const SegmentedSelectionExample = () => {
  const [selected, setSelected] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)('two');
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(_woocommerce_components__WEBPACK_IMPORTED_MODULE_2__/* ["default"] */ .A, {
    options: [{
      value: 'one',
      label: 'One'
    }, {
      value: 'two',
      label: 'Two'
    }, {
      value: 'three',
      label: 'Three'
    }, {
      value: 'four',
      label: 'Four'
    }],
    selected: selected,
    legend: "Select a number",
    onSelect: data => setSelected(data[name]),
    name: name
  });
};
const Basic = () => /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(SegmentedSelectionExample, {});
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  title: 'Components/SegmentedSelection',
  component: _woocommerce_components__WEBPACK_IMPORTED_MODULE_2__/* ["default"] */ .A
});
Basic.parameters = {
  ...Basic.parameters,
  docs: {
    ...Basic.parameters?.docs,
    source: {
      originalSource: "() => <SegmentedSelectionExample />",
      ...Basic.parameters?.docs?.source
    }
  }
};

/***/ }),

/***/ "../../packages/js/components/src/segmented-selection/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var clsx__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/lodash@4.18.1/node_modules/lodash/lodash.js");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
/**
 * External dependencies
 */





/**
 * Create a panel of styled selectable options rendering stylized checkboxes and labels
 */

class SegmentedSelection extends _wordpress_element__WEBPACK_IMPORTED_MODULE_2__.Component {
  render() {
    const {
      className,
      options,
      selected,
      onSelect,
      name,
      legend
    } = this.props;
    return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsxs)("fieldset", {
      className: "woocommerce-segmented-selection",
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("legend", {
        className: "screen-reader-text",
        children: legend
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("div", {
        className: (0,clsx__WEBPACK_IMPORTED_MODULE_3__/* ["default"] */ .A)(className, 'woocommerce-segmented-selection__container'),
        children: options.map(({
          value,
          label
        }) => {
          if (!value || !label) {
            return null;
          }
          const id = (0,lodash__WEBPACK_IMPORTED_MODULE_0__.uniqueId)(`${value}_`);
          return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsxs)("div", {
            className: "woocommerce-segmented-selection__item",
            children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("input", {
              className: "woocommerce-segmented-selection__input",
              type: "radio",
              name: name,
              id: id,
              checked: selected === value,
              onChange: (0,lodash__WEBPACK_IMPORTED_MODULE_0__.partial)(onSelect, {
                [name]: value
              })
            }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("label", {
              htmlFor: id,
              children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("span", {
                className: "woocommerce-segmented-selection__label",
                children: label
              })
            })]
          }, value);
        })
      })]
    });
  }
}
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (SegmentedSelection);
;
SegmentedSelection.__docgenInfo = {
  "description": "Create a panel of styled selectable options rendering stylized checkboxes and labels",
  "methods": [],
  "displayName": "SegmentedSelection",
  "props": {
    "className": {
      "description": "Additional CSS classes.",
      "type": {
        "name": "string"
      },
      "required": false
    },
    "options": {
      "description": "An Array of options to render. The array needs to be composed of objects with properties `label` and `value`.",
      "type": {
        "name": "arrayOf",
        "value": {
          "name": "shape",
          "value": {
            "value": {
              "name": "string",
              "required": true
            },
            "label": {
              "name": "string",
              "required": true
            }
          }
        }
      },
      "required": true
    },
    "selected": {
      "description": "Value of selected item.",
      "type": {
        "name": "string"
      },
      "required": false
    },
    "onSelect": {
      "description": "Callback to be executed after selection",
      "type": {
        "name": "func"
      },
      "required": true
    },
    "name": {
      "description": "This will be the key in the key and value arguments supplied to `onSelect`.",
      "type": {
        "name": "string"
      },
      "required": true
    },
    "legend": {
      "description": "Create a legend visible to screen readers.",
      "type": {
        "name": "string"
      },
      "required": true
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