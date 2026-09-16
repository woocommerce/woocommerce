"use strict";
(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[5302],{

/***/ "../../packages/js/components/src/stepper/stories/stepper.story.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

// ESM COMPAT FLAG
__webpack_require__.r(__webpack_exports__);

// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  Examples: () => (/* binding */ Examples),
  "default": () => (/* binding */ stepper_story)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs
var clsx = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../packages/js/components/src/spinner/index.js
var spinner = __webpack_require__("../../packages/js/components/src/spinner/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js
var jsx_runtime = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
;// ../../packages/js/components/src/stepper/check-icon.tsx

/**
 * External dependencies
 */

/* harmony default export */ const check_icon = (() => {
  // we need a unique mask id because HTML ids are global in nature and collisions result in strange outcomes
  const maskId = `check-icon-mask-${Math.floor(Math.random() * 10000000)}`;
  return /*#__PURE__*/(0,jsx_runtime.jsxs)("svg", {
    role: "img",
    "aria-hidden": "true",
    focusable: "false",
    width: "18",
    height: "18",
    viewBox: "0 0 18 18",
    fill: "none",
    xmlns: "http://www.w3.org/2000/svg",
    children: [/*#__PURE__*/(0,jsx_runtime.jsx)("mask", {
      id: maskId,
      style: {
        maskType: 'alpha'
      },
      maskUnits: "userSpaceOnUse",
      x: "2",
      y: "3",
      width: "14",
      height: "12",
      children: /*#__PURE__*/(0,jsx_runtime.jsx)("path", {
        d: "M6.59631 11.9062L3.46881 8.77875L2.40381 9.83625L6.59631 14.0287L15.5963 5.02875L14.5388 3.97125L6.59631 11.9062Z",
        fill: "white"
      })
    }), /*#__PURE__*/(0,jsx_runtime.jsx)("g", {
      mask: `url(#${maskId})`,
      children: /*#__PURE__*/(0,jsx_runtime.jsx)("rect", {
        width: "18",
        height: "18",
        fill: "white"
      })
    })]
  });
});
;// ../../packages/js/components/src/stepper/index.tsx
/**
 * External dependencies
 */


/**
 * Internal dependencies
 */



/**
 * A stepper component to indicate progress in a set number of steps.
 */
const Stepper = ({
  className,
  currentStep,
  steps,
  isVertical = false,
  isPending = false
}) => {
  const renderCurrentStepContent = () => {
    const step = steps.find(s => currentStep === s.key);
    if (!step || !step.content) {
      return null;
    }
    return /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
      className: "woocommerce-stepper_content",
      children: step.content
    });
  };
  const currentIndex = steps.findIndex(s => currentStep === s.key);
  const stepperClassName = (0,clsx/* default */.A)('woocommerce-stepper', className, {
    'is-vertical': isVertical
  });
  return /*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
    className: stepperClassName,
    children: [/*#__PURE__*/(0,jsx_runtime.jsx)("div", {
      className: "woocommerce-stepper__steps",
      children: steps.map((step, i) => {
        const {
          key,
          label,
          description,
          isComplete,
          onClick
        } = step;
        const isCurrentStep = key === currentStep;
        const stepClassName = (0,clsx/* default */.A)('woocommerce-stepper__step', {
          'is-active': isCurrentStep,
          'is-complete': typeof isComplete !== 'undefined' ? isComplete : currentIndex > i
        });
        const icon = isCurrentStep && isPending ? /*#__PURE__*/(0,jsx_runtime.jsx)(spinner/* default */.A, {}) : /*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
          className: "woocommerce-stepper__step-icon",
          children: [/*#__PURE__*/(0,jsx_runtime.jsx)("span", {
            className: "woocommerce-stepper__step-number",
            children: i + 1
          }), /*#__PURE__*/(0,jsx_runtime.jsx)(check_icon, {})]
        });
        const LabelWrapper = typeof onClick === 'function' ? 'button' : 'div';
        return /*#__PURE__*/(0,jsx_runtime.jsxs)(react.Fragment, {
          children: [/*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
            className: stepClassName,
            children: [/*#__PURE__*/(0,jsx_runtime.jsxs)(LabelWrapper, {
              className: "woocommerce-stepper__step-label-wrapper",
              onClick: typeof onClick === 'function' ? () => onClick(key) : undefined,
              children: [icon, /*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
                className: "woocommerce-stepper__step-text",
                children: [/*#__PURE__*/(0,jsx_runtime.jsx)("span", {
                  className: "woocommerce-stepper__step-label",
                  children: label
                }), description && /*#__PURE__*/(0,jsx_runtime.jsx)("p", {
                  className: "woocommerce-stepper__step-description",
                  children: description
                })]
              })]
            }), isCurrentStep && isVertical && renderCurrentStepContent()]
          }), !isVertical && /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
            className: "woocommerce-stepper__step-divider"
          })]
        }, key);
      })
    }), !isVertical && renderCurrentStepContent()]
  });
};
/* harmony default export */ const src_stepper = (Stepper);
try {
    // @ts-ignore
    Stepper.displayName = "Stepper";
    // @ts-ignore
    Stepper.__docgenInfo = { "description": "A stepper component to indicate progress in a set number of steps.", "displayName": "Stepper", "props": { "className": { "defaultValue": null, "description": "Additional class name to style the component.", "name": "className", "required": false, "type": { "name": "string" } }, "currentStep": { "defaultValue": null, "description": "The current step's key.", "name": "currentStep", "required": true, "type": { "name": "string" } }, "steps": { "defaultValue": null, "description": "An array of steps used.", "name": "steps", "required": true, "type": { "name": "{ content: ReactNode; description: string | string[]; isComplete?: boolean | undefined; key: string; label: string; onClick?: ((key: string) => void) | undefined; }[]" } }, "isVertical": { "defaultValue": { value: "false" }, "description": "If the stepper is vertical instead of horizontal.", "name": "isVertical", "required": false, "type": { "name": "boolean" } }, "isPending": { "defaultValue": { value: "false" }, "description": "Optionally mark the current step as pending to show a spinner.", "name": "isPending", "required": false, "type": { "name": "boolean" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/stepper/index.tsx#Stepper"] = { docgenInfo: Stepper.__docgenInfo, name: "Stepper", path: "../../packages/js/components/src/stepper/index.tsx#Stepper" };
}
catch (__react_docgen_typescript_loader_error) { }
try {
    // @ts-ignore
    stepper.displayName = "stepper";
    // @ts-ignore
    stepper.__docgenInfo = { "description": "A stepper component to indicate progress in a set number of steps.", "displayName": "stepper", "props": { "className": { "defaultValue": null, "description": "Additional class name to style the component.", "name": "className", "required": false, "type": { "name": "string" } }, "currentStep": { "defaultValue": null, "description": "The current step's key.", "name": "currentStep", "required": true, "type": { "name": "string" } }, "steps": { "defaultValue": null, "description": "An array of steps used.", "name": "steps", "required": true, "type": { "name": "{ content: ReactNode; description: string | string[]; isComplete?: boolean | undefined; key: string; label: string; onClick?: ((key: string) => void) | undefined; }[]" } }, "isVertical": { "defaultValue": { value: "false" }, "description": "If the stepper is vertical instead of horizontal.", "name": "isVertical", "required": false, "type": { "name": "boolean" } }, "isPending": { "defaultValue": { value: "false" }, "description": "Optionally mark the current step as pending to show a spinner.", "name": "isPending", "required": false, "type": { "name": "boolean" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/stepper/index.tsx#stepper"] = { docgenInfo: stepper.__docgenInfo, name: "stepper", path: "../../packages/js/components/src/stepper/index.tsx#stepper" };
}
catch (__react_docgen_typescript_loader_error) { }
;// ../../packages/js/components/src/stepper/stories/stepper.story.js
/**
 * External dependencies
 */



const BasicExamples = () => {
  const [state, setState] = (0,react.useState)({
    currentStep: 'first',
    isComplete: false,
    isPending: false
  });
  const {
    currentStep,
    isComplete,
    isPending
  } = state;
  const goToStep = key => {
    setState({
      currentStep: key
    });
  };
  const steps = [{
    key: 'first',
    label: 'First',
    description: 'Step item description',
    content: /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
      children: "First step content."
    }),
    onClick: goToStep
  }, {
    key: 'second',
    label: 'Second',
    description: 'Step item description',
    content: /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
      children: "Second step content."
    }),
    onClick: goToStep
  }, {
    label: 'Third',
    key: 'third',
    description: 'Step item description',
    content: /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
      children: "Third step content."
    }),
    onClick: goToStep
  }, {
    label: 'Fourth',
    key: 'fourth',
    description: 'Step item description',
    content: /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
      children: "Fourth step content."
    }),
    onClick: goToStep
  }];
  const currentIndex = steps.findIndex(s => currentStep === s.key);
  if (isComplete) {
    steps.forEach(s => s.isComplete = true);
  }
  return /*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
    children: [isComplete ? /*#__PURE__*/(0,jsx_runtime.jsx)("button", {
      onClick: () => setState({
        ...state,
        currentStep: 'first',
        isComplete: false
      }),
      children: "Reset"
    }) : /*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
      children: [/*#__PURE__*/(0,jsx_runtime.jsx)("button", {
        onClick: () => setState({
          ...state,
          currentStep: steps[currentIndex - 1].key
        }),
        disabled: currentIndex < 1,
        children: "Previous step"
      }), /*#__PURE__*/(0,jsx_runtime.jsx)("button", {
        onClick: () => setState({
          ...state,
          currentStep: steps[currentIndex + 1].key
        }),
        disabled: currentIndex >= steps.length - 1,
        children: "Next step"
      }), /*#__PURE__*/(0,jsx_runtime.jsx)("button", {
        onClick: () => setState({
          ...state,
          isComplete: true
        }),
        disabled: currentIndex !== steps.length - 1,
        children: "Complete"
      }), /*#__PURE__*/(0,jsx_runtime.jsx)("button", {
        onClick: () => setState({
          ...state,
          isPending: !isPending
        }),
        children: "Toggle Spinner"
      })]
    }), /*#__PURE__*/(0,jsx_runtime.jsx)(src_stepper, {
      steps: steps,
      currentStep: currentStep,
      isPending: isPending
    }), /*#__PURE__*/(0,jsx_runtime.jsx)("br", {}), /*#__PURE__*/(0,jsx_runtime.jsx)(src_stepper, {
      isPending: isPending,
      isVertical: true,
      steps: steps,
      currentStep: currentStep
    })]
  });
};
const Examples = () => /*#__PURE__*/(0,jsx_runtime.jsx)(BasicExamples, {});
/* harmony default export */ const stepper_story = ({
  title: 'Components/Stepper',
  component: src_stepper
});
Examples.parameters = {
  ...Examples.parameters,
  docs: {
    ...Examples.parameters?.docs,
    source: {
      originalSource: "() => <BasicExamples />",
      ...Examples.parameters?.docs?.source
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