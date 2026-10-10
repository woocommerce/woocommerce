"use strict";
(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[4318],{

/***/ "../../packages/js/components/src/empty-content/stories/empty-content.story.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   Basic: () => (/* binding */ Basic),
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../packages/js/components/src/empty-content/index.js");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
/**
 * External dependencies
 */


const Basic = () => /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(_woocommerce_components__WEBPACK_IMPORTED_MODULE_1__/* ["default"] */ .A, {
  title: "Nothing here",
  message: "Some descriptive text",
  actionLabel: "Reload page",
  actionURL: "#"
});
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  title: 'Components/EmptyContent',
  component: _woocommerce_components__WEBPACK_IMPORTED_MODULE_1__/* ["default"] */ .A
});
Basic.parameters = {
  ...Basic.parameters,
  docs: {
    ...Basic.parameters?.docs,
    source: {
      originalSource: "() => <EmptyContent title=\"Nothing here\" message=\"Some descriptive text\" actionLabel=\"Reload page\" actionURL=\"#\" />",
      ...Basic.parameters?.docs?.source
    }
  }
};

/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+hooks@4.50.0/node_modules/@wordpress/hooks/build-module/index.mjs":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  se: () => (/* binding */ defaultHooks),
  Eo: () => (/* binding */ doAction)
});

// UNUSED EXPORTS: actions, addAction, addFilter, applyFilters, applyFiltersAsync, createHooks, currentAction, currentFilter, didAction, didFilter, doActionAsync, doingAction, doingFilter, filters, hasAction, hasFilter, removeAction, removeAllActions, removeAllFilters, removeFilter

;// ../../node_modules/.pnpm/@wordpress+hooks@4.50.0/node_modules/@wordpress/hooks/build-module/validateNamespace.mjs
// packages/hooks/src/validateNamespace.ts
function validateNamespace(namespace) {
  if ("string" !== typeof namespace || "" === namespace) {
    console.error("The namespace must be a non-empty string.");
    return false;
  }
  if (!/^[a-zA-Z][a-zA-Z0-9_.\-\/]*$/.test(namespace)) {
    console.error(
      "The namespace can only contain numbers, letters, dashes, periods, underscores and slashes."
    );
    return false;
  }
  return true;
}
var validateNamespace_default = validateNamespace;

//# sourceMappingURL=validateNamespace.mjs.map

;// ../../node_modules/.pnpm/@wordpress+hooks@4.50.0/node_modules/@wordpress/hooks/build-module/validateHookName.mjs
// packages/hooks/src/validateHookName.ts
function validateHookName(hookName) {
  if ("string" !== typeof hookName || "" === hookName) {
    console.error("The hook name must be a non-empty string.");
    return false;
  }
  if (/^__/.test(hookName)) {
    console.error("The hook name cannot begin with `__`.");
    return false;
  }
  if (!/^[a-zA-Z][a-zA-Z0-9_.-]*$/.test(hookName)) {
    console.error(
      "The hook name can only contain numbers, letters, dashes, periods and underscores."
    );
    return false;
  }
  return true;
}
var validateHookName_default = validateHookName;

//# sourceMappingURL=validateHookName.mjs.map

;// ../../node_modules/.pnpm/@wordpress+hooks@4.50.0/node_modules/@wordpress/hooks/build-module/createAddHook.mjs
// packages/hooks/src/createAddHook.ts


function createAddHook(hooks, storeKey) {
  return function addHook(hookName, namespace, callback, priority = 10) {
    const hooksStore = hooks[storeKey];
    if (!validateHookName_default(hookName)) {
      return;
    }
    if (!validateNamespace_default(namespace)) {
      return;
    }
    if ("function" !== typeof callback) {
      console.error("The hook callback must be a function.");
      return;
    }
    if ("number" !== typeof priority) {
      console.error(
        "If specified, the hook priority must be a number."
      );
      return;
    }
    const handler = { callback, priority, namespace };
    if (hooksStore[hookName]) {
      const handlers = hooksStore[hookName].handlers;
      let i;
      for (i = handlers.length; i > 0; i--) {
        if (priority >= handlers[i - 1].priority) {
          break;
        }
      }
      if (i === handlers.length) {
        handlers[i] = handler;
      } else {
        handlers.splice(i, 0, handler);
      }
      hooksStore.__current.forEach((hookInfo) => {
        if (hookInfo.name === hookName && hookInfo.currentIndex >= i) {
          hookInfo.currentIndex++;
        }
      });
    } else {
      hooksStore[hookName] = {
        handlers: [handler],
        runs: 0
      };
    }
    if (hookName !== "hookAdded") {
      hooks.doAction(
        "hookAdded",
        hookName,
        namespace,
        callback,
        priority
      );
    }
  };
}
var createAddHook_default = createAddHook;

//# sourceMappingURL=createAddHook.mjs.map

;// ../../node_modules/.pnpm/@wordpress+hooks@4.50.0/node_modules/@wordpress/hooks/build-module/createRemoveHook.mjs
// packages/hooks/src/createRemoveHook.ts


function createRemoveHook(hooks, storeKey, removeAll = false) {
  return function removeHook(hookName, namespace) {
    const hooksStore = hooks[storeKey];
    if (!validateHookName_default(hookName)) {
      return;
    }
    if (!removeAll && !validateNamespace_default(namespace)) {
      return;
    }
    if (!hooksStore[hookName]) {
      return 0;
    }
    let handlersRemoved = 0;
    if (removeAll) {
      handlersRemoved = hooksStore[hookName].handlers.length;
      hooksStore[hookName] = {
        runs: hooksStore[hookName].runs,
        handlers: []
      };
    } else {
      const handlers = hooksStore[hookName].handlers;
      for (let i = handlers.length - 1; i >= 0; i--) {
        if (handlers[i].namespace === namespace) {
          handlers.splice(i, 1);
          handlersRemoved++;
          hooksStore.__current.forEach((hookInfo) => {
            if (hookInfo.name === hookName && hookInfo.currentIndex >= i) {
              hookInfo.currentIndex--;
            }
          });
        }
      }
    }
    if (hookName !== "hookRemoved") {
      hooks.doAction("hookRemoved", hookName, namespace);
    }
    return handlersRemoved;
  };
}
var createRemoveHook_default = createRemoveHook;

//# sourceMappingURL=createRemoveHook.mjs.map

;// ../../node_modules/.pnpm/@wordpress+hooks@4.50.0/node_modules/@wordpress/hooks/build-module/createHasHook.mjs
// packages/hooks/src/createHasHook.ts
function createHasHook(hooks, storeKey) {
  return function hasHook(hookName, namespace) {
    const hooksStore = hooks[storeKey];
    if ("undefined" !== typeof namespace) {
      return hookName in hooksStore && hooksStore[hookName].handlers.some(
        (hook) => hook.namespace === namespace
      );
    }
    return hookName in hooksStore;
  };
}
var createHasHook_default = createHasHook;

//# sourceMappingURL=createHasHook.mjs.map

;// ../../node_modules/.pnpm/@wordpress+hooks@4.50.0/node_modules/@wordpress/hooks/build-module/createRunHook.mjs
// packages/hooks/src/createRunHook.ts
function createRunHook(hooks, storeKey, returnFirstArg, async) {
  return function runHook(hookName, ...args) {
    const hooksStore = hooks[storeKey];
    if (!hooksStore[hookName]) {
      hooksStore[hookName] = {
        handlers: [],
        runs: 0
      };
    }
    hooksStore[hookName].runs++;
    const handlers = hooksStore[hookName].handlers;
    if (false) {}
    if (!handlers || !handlers.length) {
      return returnFirstArg ? args[0] : void 0;
    }
    const hookInfo = {
      name: hookName,
      currentIndex: 0
    };
    async function asyncRunner() {
      try {
        hooksStore.__current.add(hookInfo);
        let result = returnFirstArg ? args[0] : void 0;
        while (hookInfo.currentIndex < handlers.length) {
          const handler = handlers[hookInfo.currentIndex];
          result = await handler.callback.apply(null, args);
          if (returnFirstArg) {
            args[0] = result;
          }
          hookInfo.currentIndex++;
        }
        return returnFirstArg ? result : void 0;
      } finally {
        hooksStore.__current.delete(hookInfo);
      }
    }
    function syncRunner() {
      try {
        hooksStore.__current.add(hookInfo);
        let result = returnFirstArg ? args[0] : void 0;
        while (hookInfo.currentIndex < handlers.length) {
          const handler = handlers[hookInfo.currentIndex];
          result = handler.callback.apply(null, args);
          if (returnFirstArg) {
            args[0] = result;
          }
          hookInfo.currentIndex++;
        }
        return returnFirstArg ? result : void 0;
      } finally {
        hooksStore.__current.delete(hookInfo);
      }
    }
    return (async ? asyncRunner : syncRunner)();
  };
}
var createRunHook_default = createRunHook;

//# sourceMappingURL=createRunHook.mjs.map

;// ../../node_modules/.pnpm/@wordpress+hooks@4.50.0/node_modules/@wordpress/hooks/build-module/createCurrentHook.mjs
// packages/hooks/src/createCurrentHook.ts
function createCurrentHook(hooks, storeKey) {
  return function currentHook() {
    const hooksStore = hooks[storeKey];
    const currentArray = Array.from(hooksStore.__current);
    return currentArray.at(-1)?.name ?? null;
  };
}
var createCurrentHook_default = createCurrentHook;

//# sourceMappingURL=createCurrentHook.mjs.map

;// ../../node_modules/.pnpm/@wordpress+hooks@4.50.0/node_modules/@wordpress/hooks/build-module/createDoingHook.mjs
// packages/hooks/src/createDoingHook.ts
function createDoingHook(hooks, storeKey) {
  return function doingHook(hookName) {
    const hooksStore = hooks[storeKey];
    if ("undefined" === typeof hookName) {
      return hooksStore.__current.size > 0;
    }
    return Array.from(hooksStore.__current).some(
      (hook) => hook.name === hookName
    );
  };
}
var createDoingHook_default = createDoingHook;

//# sourceMappingURL=createDoingHook.mjs.map

;// ../../node_modules/.pnpm/@wordpress+hooks@4.50.0/node_modules/@wordpress/hooks/build-module/createDidHook.mjs
// packages/hooks/src/createDidHook.ts

function createDidHook(hooks, storeKey) {
  return function didHook(hookName) {
    const hooksStore = hooks[storeKey];
    if (!validateHookName_default(hookName)) {
      return;
    }
    return hooksStore[hookName] && hooksStore[hookName].runs ? hooksStore[hookName].runs : 0;
  };
}
var createDidHook_default = createDidHook;

//# sourceMappingURL=createDidHook.mjs.map

;// ../../node_modules/.pnpm/@wordpress+hooks@4.50.0/node_modules/@wordpress/hooks/build-module/createHooks.mjs
// packages/hooks/src/createHooks.ts







var _Hooks = class {
  actions;
  filters;
  addAction;
  addFilter;
  removeAction;
  removeFilter;
  hasAction;
  hasFilter;
  removeAllActions;
  removeAllFilters;
  doAction;
  doActionAsync;
  applyFilters;
  applyFiltersAsync;
  currentAction;
  currentFilter;
  doingAction;
  doingFilter;
  didAction;
  didFilter;
  constructor() {
    this.actions = /* @__PURE__ */ Object.create(null);
    this.actions.__current = /* @__PURE__ */ new Set();
    this.filters = /* @__PURE__ */ Object.create(null);
    this.filters.__current = /* @__PURE__ */ new Set();
    this.addAction = createAddHook_default(this, "actions");
    this.addFilter = createAddHook_default(this, "filters");
    this.removeAction = createRemoveHook_default(this, "actions");
    this.removeFilter = createRemoveHook_default(this, "filters");
    this.hasAction = createHasHook_default(this, "actions");
    this.hasFilter = createHasHook_default(this, "filters");
    this.removeAllActions = createRemoveHook_default(this, "actions", true);
    this.removeAllFilters = createRemoveHook_default(this, "filters", true);
    this.doAction = createRunHook_default(this, "actions", false, false);
    this.doActionAsync = createRunHook_default(this, "actions", false, true);
    this.applyFilters = createRunHook_default(this, "filters", true, false);
    this.applyFiltersAsync = createRunHook_default(this, "filters", true, true);
    this.currentAction = createCurrentHook_default(this, "actions");
    this.currentFilter = createCurrentHook_default(this, "filters");
    this.doingAction = createDoingHook_default(this, "actions");
    this.doingFilter = createDoingHook_default(this, "filters");
    this.didAction = createDidHook_default(this, "actions");
    this.didFilter = createDidHook_default(this, "filters");
  }
};
function createHooks() {
  return new _Hooks();
}
var createHooks_default = createHooks;

//# sourceMappingURL=createHooks.mjs.map

;// ../../node_modules/.pnpm/@wordpress+hooks@4.50.0/node_modules/@wordpress/hooks/build-module/index.mjs
// packages/hooks/src/index.ts

var defaultHooks = createHooks_default();
var {
  addAction,
  addFilter,
  removeAction,
  removeFilter,
  hasAction,
  hasFilter,
  removeAllActions,
  removeAllFilters,
  doAction,
  doActionAsync,
  applyFilters,
  applyFiltersAsync,
  currentAction,
  currentFilter,
  doingAction,
  doingFilter,
  didAction,
  didFilter,
  actions,
  filters
} = defaultHooks;

//# sourceMappingURL=index.mjs.map


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