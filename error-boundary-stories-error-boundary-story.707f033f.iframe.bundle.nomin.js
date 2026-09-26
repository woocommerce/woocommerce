"use strict";
(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[2590],{

/***/ "../../packages/js/components/src/error-boundary/stories/error-boundary.story.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

// ESM COMPAT FLAG
__webpack_require__.r(__webpack_exports__);

// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  CustomActionCallback: () => (/* binding */ CustomActionCallback),
  CustomActionLabel: () => (/* binding */ CustomActionLabel),
  CustomErrorMessage: () => (/* binding */ CustomErrorMessage),
  Default: () => (/* binding */ Default),
  WithoutActionButton: () => (/* binding */ WithoutActionButton),
  "default": () => (/* binding */ error_boundary_story)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+i18n@6.6.1/node_modules/@wordpress/i18n/build-module/index.js + 3 modules
var build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@6.6.1/node_modules/@wordpress/i18n/build-module/index.js");
// EXTERNAL MODULE: ../../packages/js/components/src/empty-content/index.js
var empty_content = __webpack_require__("../../packages/js/components/src/empty-content/index.js");
;// ../../packages/js/components/src/error-boundary/constants.ts
const alertIcon = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAiIGhlaWdodD0iMjAiIHZpZXdCb3g9IjAgMCAyMCAyMCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHBhdGggZD0iTTguNTc0NjUgMy4yMTYzNUwxLjUxNjMyIDE0Ljk5OTdDMS4zNzA3OSAxNS4yNTE3IDEuMjkzNzkgMTUuNTM3NCAxLjI5Mjk4IDE1LjgyODRDMS4yOTIxNiAxNi4xMTk1IDEuMzY3NTYgMTYuNDA1NiAxLjUxMTY3IDE2LjY1ODVDMS42NTU3OSAxNi45MTEzIDEuODYzNTkgMTcuMTIyIDIuMTE0NDEgMTcuMjY5NkMyLjM2NTIzIDE3LjQxNzEgMi42NTAzMiAxNy40OTY1IDIuOTQxMzIgMTcuNDk5N0gxNy4wNThDMTcuMzQ5IDE3LjQ5NjUgMTcuNjM0MSAxNy40MTcxIDE3Ljg4NDkgMTcuMjY5NkMxOC4xMzU3IDE3LjEyMiAxOC4zNDM1IDE2LjkxMTMgMTguNDg3NiAxNi42NTg1QzE4LjYzMTcgMTYuNDA1NiAxOC43MDcxIDE2LjExOTUgMTguNzA2MyAxNS44Mjg0QzE4LjcwNTUgMTUuNTM3NCAxOC42Mjg1IDE1LjI1MTcgMTguNDgzIDE0Ljk5OTdMMTEuNDI0NyAzLjIxNjM1QzExLjI3NjEgMi45NzE0NCAxMS4wNjY5IDIuNzY4OTUgMTAuODE3MyAyLjYyODQyQzEwLjU2NzcgMi40ODc4OSAxMC4yODYxIDIuNDE0MDYgOS45OTk2NSAyLjQxNDA2QzkuNzEzMjEgMi40MTQwNiA5LjQzMTU5IDIuNDg3ODkgOS4xODE5OSAyLjYyODQyQzguOTMyMzggMi43Njg5NSA4LjcyMzIxIDIuOTcxNDQgOC41NzQ2NSAzLjIxNjM1VjMuMjE2MzVaIiBzdHJva2U9IiMxZTFlMWUiIHN0cm9rZS13aWR0aD0iMiIgc3Ryb2tlLWxpbmVjYXA9InJvdW5kIiBzdHJva2UtbGluZWpvaW49InJvdW5kIi8+CjxwYXRoIGQ9Ik0xMCA3LjVWMTAuODMzMyIgc3Ryb2tlPSIjMWUxZTFlIiBzdHJva2Utd2lkdGg9IjIiIHN0cm9rZS1saW5lY2FwPSJyb3VuZCIgc3Ryb2tlLWxpbmVqb2luPSJyb3VuZCIvPgo8cGF0aCBkPSJNMTAgMTQuMTY4SDEwLjAwODMiIHN0cm9rZT0iIzFlMWUxZSIgc3Ryb2tlLXdpZHRoPSIyIiBzdHJva2UtbGluZWNhcD0icm91bmQiIHN0cm9rZS1saW5lam9pbj0icm91bmQiLz4KPC9zdmc+Cg==';
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js
var jsx_runtime = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
;// ../../packages/js/components/src/error-boundary/index.tsx
/**
 * External dependencies
 */



/**
 * Internal dependencies
 */



class ErrorBoundary extends react.Component {
  static defaultProps = {
    showActionButton: true,
    resetErrorAfterAction: true
  };
  constructor(props) {
    super(props);
    this.state = {
      hasError: false,
      error: null,
      errorInfo: null
    };
  }
  static getDerivedStateFromError(error) {
    return {
      hasError: true,
      error
    };
  }
  componentDidCatch(_error, errorInfo) {
    this.setState({
      errorInfo
    });
    if (this.props.onError) {
      this.props.onError(_error, errorInfo);
    }
    // TODO: Log error to error tracking service
  }
  handleReload = () => {
    window.location.reload();
  };
  handleAction = () => {
    const {
      actionCallback,
      resetErrorAfterAction
    } = this.props;
    if (actionCallback) {
      actionCallback(this.state.error);
    } else {
      this.handleReload();
    }
    if (resetErrorAfterAction) {
      this.setState({
        hasError: false,
        error: null,
        errorInfo: null
      });
    }
  };
  render() {
    const {
      children,
      errorMessage,
      showActionButton,
      actionLabel
    } = this.props;
    if (this.state.hasError) {
      return /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
        className: "woocommerce-error-boundary",
        children: /*#__PURE__*/(0,jsx_runtime.jsx)(empty_content/* default */.A, {
          title: "",
          actionLabel: "",
          message: errorMessage || (0,build_module.__)('Oops, something went wrong. Please try again', 'woocommerce'),
          secondaryActionLabel: actionLabel || (0,build_module.__)('Reload', 'woocommerce'),
          secondaryActionURL: null,
          secondaryActionCallback: showActionButton ? this.handleAction : undefined,
          illustrationWidth: 36,
          illustrationHeight: 36,
          illustration: alertIcon
        })
      });
    }
    return children;
  }
}
try {
    // @ts-ignore
    ErrorBoundary.displayName = "ErrorBoundary";
    // @ts-ignore
    ErrorBoundary.__docgenInfo = { "description": "", "displayName": "ErrorBoundary", "props": { "children": { "defaultValue": null, "description": "The content to be rendered inside the ErrorBoundary component.", "name": "children", "required": true, "type": { "name": "ReactNode" } }, "errorMessage": { "defaultValue": null, "description": "The custom error message to be displayed. Defaults to a generic message.", "name": "errorMessage", "required": false, "type": { "name": "ReactNode" } }, "showActionButton": { "defaultValue": { value: "true" }, "description": "Determines whether to show an action button. Defaults to true.", "name": "showActionButton", "required": false, "type": { "name": "boolean" } }, "actionLabel": { "defaultValue": null, "description": "The label to be used for the action button. Defaults to 'Reload'.", "name": "actionLabel", "required": false, "type": { "name": "string" } }, "actionCallback": { "defaultValue": null, "description": "The callback function to be executed when the action button is clicked. Defaults to window.location.reload.\n@param error - The error that was caught.", "name": "actionCallback", "required": false, "type": { "name": "((error: Error) => void)" } }, "resetErrorAfterAction": { "defaultValue": { value: "true" }, "description": "Determines whether to reset the error boundary state after the action is performed. Defaults to true.", "name": "resetErrorAfterAction", "required": false, "type": { "name": "boolean" } }, "onError": { "defaultValue": null, "description": "Callback function to be executed when an error is caught.\n@param error - The error that was caught.\n@param errorInfo - The error info object.", "name": "onError", "required": false, "type": { "name": "((error: Error, errorInfo: ErrorInfo) => void)" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/error-boundary/index.tsx#ErrorBoundary"] = { docgenInfo: ErrorBoundary.__docgenInfo, name: "ErrorBoundary", path: "../../packages/js/components/src/error-boundary/index.tsx#ErrorBoundary" };
}
catch (__react_docgen_typescript_loader_error) { }
;// ../../packages/js/components/src/error-boundary/stories/error-boundary.story.tsx
// ErrorBoundary.stories.tsx

/**
 * External dependencies
 */

/**
 * Internal dependencies
 */


const ChildComponent = () => {
  throw new Error('This is a test error');
};
const Template = args => /*#__PURE__*/(0,jsx_runtime.jsx)(ErrorBoundary, {
  ...args,
  children: /*#__PURE__*/(0,jsx_runtime.jsx)(ChildComponent, {})
});
const Default = Template.bind({});
Default.args = {};
const CustomErrorMessage = Template.bind({});
CustomErrorMessage.args = {
  errorMessage: 'Custom error message'
};
const WithoutActionButton = Template.bind({});
WithoutActionButton.args = {
  showActionButton: false
};
const CustomActionLabel = Template.bind({});
CustomActionLabel.args = {
  actionLabel: 'Retry'
};
const CustomActionCallback = Template.bind({});
CustomActionCallback.args = {
  actionCallback: () => {
    // eslint-disable-next-line no-alert
    window.alert('Custom action callback triggered');
  }
};
/* harmony default export */ const error_boundary_story = ({
  title: 'Experimental/Error Boundary',
  component: ErrorBoundary,
  argTypes: {
    errorMessage: {
      control: 'text',
      defaultValue: 'Oops, something went wrong. Please try again'
    },
    showActionButton: {
      control: 'boolean',
      defaultValue: true
    },
    actionLabel: {
      control: 'text',
      defaultValue: 'Reload'
    },
    actionCallback: {
      action: 'clicked'
    }
  }
});
Default.parameters = {
  ...Default.parameters,
  docs: {
    ...Default.parameters?.docs,
    source: {
      originalSource: "args => <ErrorBoundary {...args}>\n        <ChildComponent />\n    </ErrorBoundary>",
      ...Default.parameters?.docs?.source
    }
  }
};
CustomErrorMessage.parameters = {
  ...CustomErrorMessage.parameters,
  docs: {
    ...CustomErrorMessage.parameters?.docs,
    source: {
      originalSource: "args => <ErrorBoundary {...args}>\n        <ChildComponent />\n    </ErrorBoundary>",
      ...CustomErrorMessage.parameters?.docs?.source
    }
  }
};
WithoutActionButton.parameters = {
  ...WithoutActionButton.parameters,
  docs: {
    ...WithoutActionButton.parameters?.docs,
    source: {
      originalSource: "args => <ErrorBoundary {...args}>\n        <ChildComponent />\n    </ErrorBoundary>",
      ...WithoutActionButton.parameters?.docs?.source
    }
  }
};
CustomActionLabel.parameters = {
  ...CustomActionLabel.parameters,
  docs: {
    ...CustomActionLabel.parameters?.docs,
    source: {
      originalSource: "args => <ErrorBoundary {...args}>\n        <ChildComponent />\n    </ErrorBoundary>",
      ...CustomActionLabel.parameters?.docs?.source
    }
  }
};
CustomActionCallback.parameters = {
  ...CustomActionCallback.parameters,
  docs: {
    ...CustomActionCallback.parameters?.docs,
    source: {
      originalSource: "args => <ErrorBoundary {...args}>\n        <ChildComponent />\n    </ErrorBoundary>",
      ...CustomActionCallback.parameters?.docs?.source
    }
  }
};

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