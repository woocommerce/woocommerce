"use strict";
(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[9286],{

/***/ "../../packages/js/components/src/analytics/error/stories/analytics-error.story.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

// ESM COMPAT FLAG
__webpack_require__.r(__webpack_exports__);

// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  Basic: () => (/* binding */ Basic),
  "default": () => (/* binding */ analytics_error_story)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+i18n@6.6.1/node_modules/@wordpress/i18n/build-module/index.js + 3 modules
var build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@6.6.1/node_modules/@wordpress/i18n/build-module/index.js");
// EXTERNAL MODULE: ../../packages/js/components/src/empty-content/index.js
var empty_content = __webpack_require__("../../packages/js/components/src/empty-content/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js
var jsx_runtime = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
;// ../../packages/js/components/src/analytics/error/index.js
/**
 * External dependencies
 */




/**
 * Internal dependencies
 */


/**
 * Component to render when there is an error in an analytics component due to data
 * not being loaded or being invalid.
 *
 * @param {Object} props             React props.
 * @param {string} [props.className] Additional class name to style the component.
 */

function error_AnalyticsError({
  className
}) {
  const title = (0,build_module.__)('There was an error getting your stats. Please try again.', 'woocommerce');
  const actionLabel = (0,build_module.__)('Reload', 'woocommerce');
  const actionCallback = () => {
    // @todo Add tracking for how often an error is displayed, and the reload action is clicked.
    window.location.reload();
  };
  return /*#__PURE__*/(0,jsx_runtime.jsx)(empty_content/* default */.A, {
    className: className,
    title: title,
    actionLabel: actionLabel,
    actionCallback: actionCallback
  });
}
/* harmony default export */ const error = (error_AnalyticsError);
;
error_AnalyticsError.__docgenInfo = {
  "description": "Component to render when there is an error in an analytics component due to data\nnot being loaded or being invalid.\n\n@param {Object} props             React props.\n@param {string} [props.className] Additional class name to style the component.",
  "methods": [],
  "displayName": "AnalyticsError",
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
;// ../../packages/js/components/src/analytics/error/stories/analytics-error.story.tsx
/**
 * External dependencies
 */

/**
 * Internal dependencies
 */


const Template = args => /*#__PURE__*/(0,jsx_runtime.jsx)(error, {
  ...args
});
const Basic = Template.bind({});
/* harmony default export */ const analytics_error_story = ({
  title: 'Components/analytics/AnalyticsError',
  component: error
});
Basic.parameters = {
  ...Basic.parameters,
  docs: {
    ...Basic.parameters?.docs,
    source: {
      originalSource: "args => <AnalyticsError {...args} />",
      ...Basic.parameters?.docs?.source
    }
  }
};
try {
    // @ts-ignore
    AnalyticsError.displayName = "AnalyticsError";
    // @ts-ignore
    AnalyticsError.__docgenInfo = { "description": "Component to render when there is an error in an analytics component due to data\nnot being loaded or being invalid.", "displayName": "AnalyticsError", "props": { "className": { "defaultValue": null, "description": "Additional class name to style the component.", "name": "className", "required": false, "type": { "name": "string" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/analytics/error/stories/analytics-error.story.tsx#AnalyticsError"] = { docgenInfo: AnalyticsError.__docgenInfo, name: "AnalyticsError", path: "../../packages/js/components/src/analytics/error/stories/analytics-error.story.tsx#AnalyticsError" };
}
catch (__react_docgen_typescript_loader_error) { }

/***/ }),

/***/ "../../packages/js/components/src/empty-content/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/button/index.js");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var clsx__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
/* harmony import */ var _section__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__("../../packages/js/components/src/section/header.tsx");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
/**
 * External dependencies
 */





/**
 * Internal dependencies
 */


/**
 * A component to be used when there is no data to show.
 * It can be used as an opportunity to provide explanation or guidance to help a user progress.
 */

class EmptyContent extends _wordpress_element__WEBPACK_IMPORTED_MODULE_1__.Component {
  renderIllustration() {
    const {
      illustrationWidth,
      illustrationHeight,
      illustration
    } = this.props;
    return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("img", {
      alt: "",
      src: illustration,
      width: illustrationWidth,
      height: illustrationHeight,
      className: "woocommerce-empty-content__illustration"
    });
  }
  renderActionButtons(type) {
    const actionLabel = type === 'secondary' ? this.props.secondaryActionLabel : this.props.actionLabel;
    const actionURL = type === 'secondary' ? this.props.secondaryActionURL : this.props.actionURL;
    const actionCallback = type === 'secondary' ? this.props.secondaryActionCallback : this.props.actionCallback;
    const isPrimary = type === 'secondary' ? false : true;
    const buttonVariant = isPrimary ? 'primary' : 'secondary';
    if (actionURL && actionCallback) {
      return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__/* ["default"] */ .Ay, {
        className: "woocommerce-empty-content__action",
        variant: buttonVariant,
        onClick: actionCallback,
        href: actionURL,
        children: actionLabel
      });
    } else if (actionURL) {
      return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__/* ["default"] */ .Ay, {
        className: "woocommerce-empty-content__action",
        variant: buttonVariant,
        href: actionURL,
        children: actionLabel
      });
    } else if (actionCallback) {
      return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__/* ["default"] */ .Ay, {
        className: "woocommerce-empty-content__action",
        variant: buttonVariant,
        onClick: actionCallback,
        children: actionLabel
      });
    }
    return null;
  }
  renderActions() {
    const {
      actionLabel,
      secondaryActionLabel
    } = this.props;
    return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsxs)("div", {
      className: "woocommerce-empty-content__actions",
      children: [actionLabel && this.renderActionButtons('primary'), secondaryActionLabel && this.renderActionButtons('secondary')]
    });
  }
  render() {
    const {
      className,
      title,
      message,
      illustration
    } = this.props;
    return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsxs)("div", {
      className: (0,clsx__WEBPACK_IMPORTED_MODULE_3__/* ["default"] */ .A)('woocommerce-empty-content', className),
      children: [illustration && this.renderIllustration(), title ? /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(_section__WEBPACK_IMPORTED_MODULE_4__.H, {
        className: "woocommerce-empty-content__title",
        children: title
      }) : null, message ? /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("p", {
        className: "woocommerce-empty-content__message",
        children: message
      }) : null, this.renderActions()]
    });
  }
}
EmptyContent.defaultProps = {
  // eslint-disable-next-line max-len
  illustration: 'data:image/svg+xml;utf8,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 400 400"%3E%3Cpath d="M226.153073,88.3099993 L355.380187,301.446227 C363.970299,315.614028 359.448689,334.062961 345.280888,342.653073 C340.591108,345.496544 335.21158,347 329.727115,347 L71.2728854,347 C54.7043429,347 41.2728854,333.568542 41.2728854,317 C41.2728854,311.515534 42.7763415,306.136007 45.6198127,301.446227 L174.846927,88.3099993 C183.437039,74.1421985 201.885972,69.6205881 216.053773,78.2106999 C220.184157,80.7150022 223.64877,84.1796157 226.153073,88.3099993 Z M184.370159,153 L186.899684,255.024156 L213.459691,255.024156 L215.989216,153 L184.370159,153 Z M200.179688,307.722584 C209.770801,307.722584 217.359375,300.450201 217.359375,291.175278 C217.359375,281.900355 209.770801,274.627972 200.179688,274.627972 C190.588574,274.627972 183,281.900355 183,291.175278 C183,300.450201 190.588574,307.722584 200.179688,307.722584 Z" id="Combined-Shape" stroke="%23c0c0c0" fill="%23c0c0c0"' + ' fill-rule="nonzero"%3E%3C/path%3E%3C/svg%3E',
  illustrationWidth: 100
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (EmptyContent);
;
EmptyContent.__docgenInfo = {
  "description": "A component to be used when there is no data to show.\nIt can be used as an opportunity to provide explanation or guidance to help a user progress.",
  "methods": [{
    "name": "renderIllustration",
    "docblock": null,
    "modifiers": [],
    "params": [],
    "returns": null
  }, {
    "name": "renderActionButtons",
    "docblock": null,
    "modifiers": [],
    "params": [{
      "name": "type",
      "optional": false,
      "type": null
    }],
    "returns": null
  }, {
    "name": "renderActions",
    "docblock": null,
    "modifiers": [],
    "params": [],
    "returns": null
  }],
  "displayName": "EmptyContent",
  "props": {
    "illustration": {
      "defaultValue": {
        "value": "'data:image/svg+xml;utf8,%3Csvg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 400 400\"%3E%3Cpath d=\"M226.153073,88.3099993 L355.380187,301.446227 C363.970299,315.614028 359.448689,334.062961 345.280888,342.653073 C340.591108,345.496544 335.21158,347 329.727115,347 L71.2728854,347 C54.7043429,347 41.2728854,333.568542 41.2728854,317 C41.2728854,311.515534 42.7763415,306.136007 45.6198127,301.446227 L174.846927,88.3099993 C183.437039,74.1421985 201.885972,69.6205881 216.053773,78.2106999 C220.184157,80.7150022 223.64877,84.1796157 226.153073,88.3099993 Z M184.370159,153 L186.899684,255.024156 L213.459691,255.024156 L215.989216,153 L184.370159,153 Z M200.179688,307.722584 C209.770801,307.722584 217.359375,300.450201 217.359375,291.175278 C217.359375,281.900355 209.770801,274.627972 200.179688,274.627972 C190.588574,274.627972 183,281.900355 183,291.175278 C183,300.450201 190.588574,307.722584 200.179688,307.722584 Z\" id=\"Combined-Shape\" stroke=\"%23c0c0c0\" fill=\"%23c0c0c0\"' +\n' fill-rule=\"nonzero\"%3E%3C/path%3E%3C/svg%3E'",
        "computed": false
      },
      "description": "The url string of an image path for img src.",
      "type": {
        "name": "string"
      },
      "required": false
    },
    "illustrationWidth": {
      "defaultValue": {
        "value": "100",
        "computed": false
      },
      "description": "Width to use for the illustration.",
      "type": {
        "name": "number"
      },
      "required": false
    },
    "title": {
      "description": "The title to be displayed.",
      "type": {
        "name": "string"
      },
      "required": true
    },
    "message": {
      "description": "An additional message to be displayed.",
      "type": {
        "name": "node"
      },
      "required": false
    },
    "illustrationHeight": {
      "description": "Height to use for the illustration.",
      "type": {
        "name": "number"
      },
      "required": false
    },
    "actionLabel": {
      "description": "Label to be used for the primary action button.",
      "type": {
        "name": "string"
      },
      "required": true
    },
    "actionURL": {
      "description": "URL to be used for the primary action button.",
      "type": {
        "name": "string"
      },
      "required": false
    },
    "actionCallback": {
      "description": "Callback to be used for the primary action button.",
      "type": {
        "name": "func"
      },
      "required": false
    },
    "secondaryActionLabel": {
      "description": "Label to be used for the secondary action button.",
      "type": {
        "name": "string"
      },
      "required": false
    },
    "secondaryActionURL": {
      "description": "URL to be used for the secondary action button.",
      "type": {
        "name": "string"
      },
      "required": false
    },
    "secondaryActionCallback": {
      "description": "Callback to be used for the secondary action button.",
      "type": {
        "name": "func"
      },
      "required": false
    },
    "className": {
      "description": "Additional CSS classes.",
      "type": {
        "name": "string"
      },
      "required": false
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

/***/ })

}]);