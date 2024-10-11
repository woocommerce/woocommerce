"use strict";
(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[670],{

/***/ "../../packages/js/components/src/tour-kit/stories/tour-kit.story.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

// ESM COMPAT FLAG
__webpack_require__.r(__webpack_exports__);

// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  AutoScroll: () => (/* binding */ AutoScroll),
  NoEffects: () => (/* binding */ NoEffects),
  Overlay: () => (/* binding */ Overlay),
  Placement: () => (/* binding */ Placement),
  Spotlight: () => (/* binding */ Spotlight),
  SpotlightInteractivity: () => (/* binding */ SpotlightInteractivity),
  "default": () => (/* binding */ tour_kit_story)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.keys.js
var es_object_keys = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.keys.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.symbol.js
var es_symbol = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.symbol.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.filter.js
var es_array_filter = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.filter.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.to-string.js
var es_object_to_string = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.to-string.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.get-own-property-descriptor.js
var es_object_get_own_property_descriptor = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.get-own-property-descriptor.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.for-each.js
var es_array_for_each = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.for-each.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/web.dom-collections.for-each.js
var web_dom_collections_for_each = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/web.dom-collections.for-each.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.get-own-property-descriptors.js
var es_object_get_own_property_descriptors = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.get-own-property-descriptors.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.define-properties.js
var es_object_define_properties = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.define-properties.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.define-property.js
var es_object_define_property = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.define-property.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/slicedToArray.js + 1 modules
var slicedToArray = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/slicedToArray.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/defineProperty.js
var defineProperty = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/defineProperty.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
;// CONCATENATED MODULE: ../../packages/js/components/src/tour-kit/stories/style.scss
// extracted by mini-css-extract-plugin

;// CONCATENATED MODULE: ../../packages/js/components/src/tour-kit/style.scss
// extracted by mini-css-extract-plugin

// EXTERNAL MODULE: ../../node_modules/.pnpm/@automattic+tour-kit@1.1.3_@types+react@17.0.71_@wordpress+data@10.6.0_react@17.0.2__react-do_wpr2orkhsavt4ls5ttip2qkwai/node_modules/@automattic/tour-kit/dist/esm/components/tour-kit.js + 24 modules
var tour_kit = __webpack_require__("../../node_modules/.pnpm/@automattic+tour-kit@1.1.3_@types+react@17.0.71_@wordpress+data@10.6.0_react@17.0.2__react-do_wpr2orkhsavt4ls5ttip2qkwai/node_modules/@automattic/tour-kit/dist/esm/components/tour-kit.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+viewport@4.20.0_react@17.0.2/node_modules/@wordpress/viewport/build-module/index.js + 6 modules
var build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+viewport@4.20.0_react@17.0.2/node_modules/@wordpress/viewport/build-module/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/card/card/component.js + 7 modules
var component = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/card/card/component.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/card/card-header/component.js + 1 modules
var card_header_component = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/card/card-header/component.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/card/card-body/component.js + 4 modules
var card_body_component = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/card/card-body/component.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/card/card-footer/component.js + 1 modules
var card_footer_component = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/card/card-footer/component.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/button/index.js
var build_module_button = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/button/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+i18n@4.6.1/node_modules/@wordpress/i18n/build-module/index.js + 3 modules
var i18n_build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@4.6.1/node_modules/@wordpress/i18n/build-module/index.js");
;// CONCATENATED MODULE: ../../packages/js/components/src/tour-kit/components/step-navigation.tsx

/**
 * External dependencies
 */



/**
 * Internal dependencies
 */

var StepNavigation = function StepNavigation(_ref) {
  var currentStepIndex = _ref.currentStepIndex,
    onNextStep = _ref.onNextStep,
    onPreviousStep = _ref.onPreviousStep,
    onDismiss = _ref.onDismiss,
    steps = _ref.steps;
  var isFirstStep = currentStepIndex === 0;
  var isLastStep = currentStepIndex === steps.length - 1;
  var _steps$currentStepInd = steps[currentStepIndex].meta.primaryButton,
    primaryButton = _steps$currentStepInd === void 0 ? {
      text: '',
      isDisabled: false,
      isHidden: false
    } : _steps$currentStepInd;
  var _steps$currentStepInd2 = steps[currentStepIndex].meta.secondaryButton,
    secondaryButton = _steps$currentStepInd2 === void 0 ? {
      text: ''
    } : _steps$currentStepInd2;
  var _steps$currentStepInd3 = steps[currentStepIndex].meta.skipButton,
    skipButton = _steps$currentStepInd3 === void 0 ? {
      text: '',
      isVisible: false
    } : _steps$currentStepInd3;
  var SkipButton = (0,react.createElement)(build_module_button/* default */.A, {
    className: "woocommerce-tour-kit-step-navigation__skip-btn",
    variant: "tertiary",
    onClick: onDismiss('skip-btn')
  }, skipButton.text || (0,i18n_build_module.__)('Skip', 'woocommerce'));
  var NextButton = (0,react.createElement)(build_module_button/* default */.A, {
    className: "woocommerce-tour-kit-step-navigation__next-btn",
    variant: "primary",
    disabled: primaryButton.isDisabled,
    onClick: onNextStep
  }, primaryButton.text || (0,i18n_build_module.__)('Next', 'woocommerce'));
  var BackButton = (0,react.createElement)(build_module_button/* default */.A, {
    className: "woocommerce-tour-kit-step-navigation__back-btn",
    variant: "secondary",
    onClick: onPreviousStep
  }, secondaryButton.text || (0,i18n_build_module.__)('Back', 'woocommerce'));
  var renderButtons = function renderButtons() {
    if (isLastStep) {
      return (0,react.createElement)("div", null, skipButton.isVisible ? SkipButton : null, !isFirstStep ? BackButton : null // For 1 step tours, isFirstStep and isLastStep can be true simultaneously.
      , (0,react.createElement)(build_module_button/* default */.A, {
        variant: "primary",
        disabled: primaryButton.isDisabled,
        className: "woocommerce-tour-kit-step-navigation__done-btn",
        onClick: onDismiss('done-btn')
      }, primaryButton.text || (0,i18n_build_module.__)('Done', 'woocommerce')));
    }
    if (isFirstStep) {
      return (0,react.createElement)("div", null, skipButton.isVisible ? SkipButton : null, NextButton);
    }
    return (0,react.createElement)("div", null, skipButton.isVisible ? SkipButton : null, BackButton, NextButton);
  };
  if (primaryButton.isHidden) {
    return null;
  }
  return (0,react.createElement)("div", {
    className: "woocommerce-tour-kit-step-navigation"
  }, (0,react.createElement)("div", {
    className: "woocommerce-tour-kit-step-navigation__step"
  }, steps.length > 1 ? (0,i18n_build_module/* sprintf */.nv)( /* translators: current progress in tour, eg: "Step 2 of 4" */
  (0,i18n_build_module.__)('Step %1$d of %2$d', 'woocommerce'), currentStepIndex + 1, steps.length) : null), renderButtons());
};
/* harmony default export */ const step_navigation = (StepNavigation);
try {
    // @ts-ignore
    stepnavigation.displayName = "stepnavigation";
    // @ts-ignore
    stepnavigation.__docgenInfo = { "description": "", "displayName": "stepnavigation", "props": { "steps": { "defaultValue": null, "description": "", "name": "steps", "required": true, "type": { "name": "WooStep[]" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/tour-kit/components/step-navigation.tsx#stepnavigation"] = { docgenInfo: stepnavigation.__docgenInfo, name: "stepnavigation", path: "../../packages/js/components/src/tour-kit/components/step-navigation.tsx#stepnavigation" };
}
catch (__react_docgen_typescript_loader_error) { }
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/flex/flex/component.js + 2 modules
var flex_component = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/flex/flex/component.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/icon/index.js + 1 modules
var icon = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@19.8.5_@types+react@17.0.71_react-dom@17.0.2_react@17.0.2__react-with-d_oli5xz3n7pc4ztqokra47llglu/node_modules/@wordpress/components/build-module/icon/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+icons@8.2.3/node_modules/@wordpress/icons/build-module/library/close-small.js
var close_small = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@8.2.3/node_modules/@wordpress/icons/build-module/library/close-small.js");
;// CONCATENATED MODULE: ../../packages/js/components/src/tour-kit/components/step-controls.tsx

/**
 * External dependencies
 */



var StepControls = function StepControls(_ref) {
  var onDismiss = _ref.onDismiss;
  return (0,react.createElement)(flex_component/* default */.A, {
    className: "woocommerce-tour-kit-step-controls",
    justify: "flex-end"
  }, (0,react.createElement)(build_module_button/* default */.A, {
    className: "woocommerce-tour-kit-step-controls__close-btn",
    label: (0,i18n_build_module.__)('Close Tour', 'woocommerce'),
    icon: (0,react.createElement)(icon/* default */.A, {
      icon: close_small/* default */.A,
      viewBox: "6 4 12 14"
    }),
    iconSize: 16,
    size: 16,
    onClick: onDismiss('close-btn')
  }));
};
/* harmony default export */ const step_controls = (StepControls);
try {
    // @ts-ignore
    stepcontrols.displayName = "stepcontrols";
    // @ts-ignore
    stepcontrols.__docgenInfo = { "description": "", "displayName": "stepcontrols", "props": { "onDismiss": { "defaultValue": null, "description": "", "name": "onDismiss", "required": true, "type": { "name": "(source: string) => () => void" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/tour-kit/components/step-controls.tsx#stepcontrols"] = { docgenInfo: stepcontrols.__docgenInfo, name: "stepcontrols", path: "../../packages/js/components/src/tour-kit/components/step-controls.tsx#stepcontrols" };
}
catch (__react_docgen_typescript_loader_error) { }
;// CONCATENATED MODULE: ../../packages/js/components/src/tour-kit/components/step.tsx

/**
 * External dependencies
 */




/**
 * Internal dependencies
 */


var getFocusElement = function getFocusElement(focusElementSelector, iframeSelector) {
  if (!focusElementSelector) {
    return null;
  }
  if (iframeSelector) {
    var iframeElement = document.querySelector(iframeSelector);
    if (!iframeElement) {
      return null;
    }
    var innerDoc = iframeElement.contentDocument || iframeElement.contentWindow && iframeElement.contentWindow.document;
    if (!innerDoc) {
      return null;
    }
    return innerDoc.querySelector(focusElementSelector);
  }
  return document.querySelector(focusElementSelector);
};
var Step = function Step(_ref) {
  var _descriptions, _steps$currentStepInd2, _steps$currentStepInd3;
  var steps = _ref.steps,
    currentStepIndex = _ref.currentStepIndex,
    onDismiss = _ref.onDismiss,
    onNextStep = _ref.onNextStep,
    onPreviousStep = _ref.onPreviousStep,
    setInitialFocusedElement = _ref.setInitialFocusedElement,
    onGoToStep = _ref.onGoToStep,
    isViewportMobile = _ref.isViewportMobile;
  var _steps$currentStepInd = steps[currentStepIndex].meta,
    descriptions = _steps$currentStepInd.descriptions,
    heading = _steps$currentStepInd.heading;
  var description = (_descriptions = descriptions[isViewportMobile ? 'mobile' : 'desktop']) !== null && _descriptions !== void 0 ? _descriptions : descriptions.desktop;
  var stepRef = (0,react.useRef)();
  var focusElementSelector = ((_steps$currentStepInd2 = steps[currentStepIndex].focusElement) === null || _steps$currentStepInd2 === void 0 ? void 0 : _steps$currentStepInd2[isViewportMobile ? 'mobile' : 'desktop']) || null;
  var iframeSelector = ((_steps$currentStepInd3 = steps[currentStepIndex].focusElement) === null || _steps$currentStepInd3 === void 0 ? void 0 : _steps$currentStepInd3.iframe) || null;
  var focusElement = getFocusElement(focusElementSelector, iframeSelector);

  /*
   * Focus the element when step renders.
   */
  (0,react.useEffect)(function () {
    if (focusElement) {
      setInitialFocusedElement(focusElement);
    } else {
      var _stepRef$current;
      // If no focus element is found, focus the last button in the step so that the user can navigate using keyboard.
      var buttons = (_stepRef$current = stepRef.current) === null || _stepRef$current === void 0 ? void 0 : _stepRef$current.querySelectorAll('button');
      if (buttons && buttons.length) {
        setInitialFocusedElement(buttons[buttons.length - 1]);
      }
    }
  }, [focusElement, setInitialFocusedElement]);
  return (0,react.createElement)(component/* default */.A, {
    ref: stepRef,
    className: "woocommerce-tour-kit-step",
    elevation: 2
  }, (0,react.createElement)(card_header_component/* default */.A, {
    isBorderless: true,
    size: "small"
  }, (0,react.createElement)(step_controls, {
    onDismiss: onDismiss
  })), (0,react.createElement)(card_body_component/* default */.A, {
    className: "woocommerce-tour-kit-step__body",
    size: "small"
  }, (0,react.createElement)("h2", {
    className: "woocommerce-tour-kit-step__heading"
  }, heading), (0,react.createElement)("p", {
    className: "woocommerce-tour-kit-step__description"
  }, description)), (0,react.createElement)(card_footer_component/* default */.A, {
    isBorderless: true,
    size: "small"
  }, (0,react.createElement)(step_navigation, {
    currentStepIndex: currentStepIndex,
    onGoToStep: onGoToStep,
    onNextStep: onNextStep,
    onPreviousStep: onPreviousStep,
    onDismiss: onDismiss,
    steps: steps
  })));
};
/* harmony default export */ const step = ((0,build_module/* withViewportMatch */.uE)({
  isViewportMobile: '< medium'
})(Step));
;// CONCATENATED MODULE: ../../packages/js/components/src/tour-kit/index.tsx












function ownKeys(e, r) {
  var t = Object.keys(e);
  if (Object.getOwnPropertySymbols) {
    var o = Object.getOwnPropertySymbols(e);
    r && (o = o.filter(function (r) {
      return Object.getOwnPropertyDescriptor(e, r).enumerable;
    })), t.push.apply(t, o);
  }
  return t;
}
function _objectSpread(e) {
  for (var r = 1; r < arguments.length; r++) {
    var t = null != arguments[r] ? arguments[r] : {};
    r % 2 ? ownKeys(Object(t), !0).forEach(function (r) {
      (0,defineProperty/* default */.A)(e, r, t[r]);
    }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : ownKeys(Object(t)).forEach(function (r) {
      Object.defineProperty(e, r, Object.getOwnPropertyDescriptor(t, r));
    });
  }
  return e;
}
/**
 * External dependencies
 */



/**
 * Internal dependencies
 */

var defaultOptions = {
  effects: {
    spotlight: {
      interactivity: {
        enabled: true,
        rootElementSelector: '#wpwrap'
      }
    },
    arrowIndicator: true,
    liveResize: {
      mutation: true,
      resize: true,
      rootElementSelector: '#wpwrap'
    }
  }
};
var WooTourKit = function WooTourKit(_ref) {
  var config = _ref.config;
  return (0,react.createElement)(tour_kit/* default */.A, {
    __temp__className: 'woocommerce-tour-kit',
    config: _objectSpread(_objectSpread({
      options: _objectSpread(_objectSpread({}, defaultOptions), config.options)
    }, config), {}, {
      renderers: {
        tourStep: step,
        // Disable minimize feature for woo tour kit.
        tourMinimized: function tourMinimized() {
          return null;
        }
      }
    })
  });
};
/* harmony default export */ const src_tour_kit = (WooTourKit);
try {
    // @ts-ignore
    tourkit.displayName = "tourkit";
    // @ts-ignore
    tourkit.__docgenInfo = { "description": "", "displayName": "tourkit", "props": { "config": { "defaultValue": null, "description": "", "name": "config", "required": true, "type": { "name": "WooConfig" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/tour-kit/index.tsx#tourkit"] = { docgenInfo: tourkit.__docgenInfo, name: "tourkit", path: "../../packages/js/components/src/tour-kit/index.tsx#tourkit" };
}
catch (__react_docgen_typescript_loader_error) { }
;// CONCATENATED MODULE: ../../packages/js/components/src/tour-kit/stories/tour-kit.story.tsx













function tour_kit_story_ownKeys(e, r) {
  var t = Object.keys(e);
  if (Object.getOwnPropertySymbols) {
    var o = Object.getOwnPropertySymbols(e);
    r && (o = o.filter(function (r) {
      return Object.getOwnPropertyDescriptor(e, r).enumerable;
    })), t.push.apply(t, o);
  }
  return t;
}
function tour_kit_story_objectSpread(e) {
  for (var r = 1; r < arguments.length; r++) {
    var t = null != arguments[r] ? arguments[r] : {};
    r % 2 ? tour_kit_story_ownKeys(Object(t), !0).forEach(function (r) {
      (0,defineProperty/* default */.A)(e, r, t[r]);
    }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : tour_kit_story_ownKeys(Object(t)).forEach(function (r) {
      Object.defineProperty(e, r, Object.getOwnPropertyDescriptor(t, r));
    });
  }
  return e;
}
/**
 * External dependencies
 */


/**
 * Internal dependencies
 */



/* harmony default export */ const tour_kit_story = ({
  title: 'WooCommerce Admin/components/TourKit',
  component: src_tour_kit
});
var References = function References() {
  return (0,react.createElement)("div", {
    className: 'storybook__tourkit-references'
  }, (0,react.createElement)("div", {
    className: 'storybook__tourkit-references-container'
  }, (0,react.createElement)("div", {
    className: 'storybook__tourkit-references-a'
  }, (0,react.createElement)("p", null, "Reference A")), (0,react.createElement)("div", {
    className: 'storybook__tourkit-references-b'
  }, (0,react.createElement)("p", null, "Reference B"), (0,react.createElement)("div", {
    style: {
      display: 'grid',
      placeItems: 'center'
    }
  }, (0,react.createElement)("input", {
    style: {
      margin: 'auto',
      display: 'block'
    }
  }))), (0,react.createElement)("div", {
    className: 'storybook__tourkit-references-c'
  }, (0,react.createElement)("p", null, "Reference C")), (0,react.createElement)("div", {
    className: 'storybook__tourkit-references-d'
  }, (0,react.createElement)("p", null, "Reference D"))));
};
var Tour = function Tour(_ref) {
  var onClose = _ref.onClose,
    options = _ref.options,
    placement = _ref.placement;
  var config = {
    placement: placement,
    steps: [{
      referenceElements: {
        desktop: '.storybook__tourkit-references-a',
        mobile: '.storybook__tourkit-references-a'
      },
      meta: {
        heading: 'Change content',
        descriptions: {
          desktop: 'You can change the content and add any relevant links.',
          mobile: 'You can change the content and add any relevant links.'
        }
      }
    }, {
      referenceElements: {
        desktop: '.storybook__tourkit-references-b',
        mobile: '.storybook__tourkit-references-b'
      },
      focusElement: {
        desktop: '.storybook__tourkit-references-b input'
      },
      meta: {
        heading: 'Shipping zones',
        descriptions: {
          desktop: 'We added a few shipping zones for you based on your location, but you can manage them at any time.',
          mobile: 'A shipping zone is a geographic area where a certain set of shipping methods are offered.'
        }
      }
    }, {
      referenceElements: {
        desktop: '.storybook__tourkit-references-c',
        mobile: '.storybook__tourkit-references-c'
      },
      meta: {
        heading: 'Shipping methods',
        descriptions: {
          desktop: 'We defaulted to some recommended shipping methods based on your store location, but you can manage them at any time within each shipping zone settings.   ',
          mobile: 'We defaulted to some recommended shipping methods based on your store location, but you can manage them at any time within each shipping zone settings.   '
        }
      }
    }, {
      referenceElements: {
        desktop: '.storybook__tourkit-references-d',
        mobile: '.storybook__tourkit-references-d'
      },
      meta: {
        heading: 'Laura 4',
        descriptions: {
          desktop: 'Lorem ipsum dolor sit amet.',
          mobile: 'Lorem ipsum dolor sit amet.'
        },
        primaryButton: {
          isDisabled: true,
          text: 'Keep editing'
        }
      }
    }],
    closeHandler: onClose,
    options: tour_kit_story_objectSpread({
      classNames: ['mytour']
    }, options)
  };
  return (0,react.createElement)(src_tour_kit, {
    config: config
  });
};
var StoryTour = function StoryTour(_ref2) {
  var _ref2$options = _ref2.options,
    options = _ref2$options === void 0 ? {} : _ref2$options,
    placement = _ref2.placement;
  var _useState = (0,react.useState)(false),
    _useState2 = (0,slicedToArray/* default */.A)(_useState, 2),
    showTour = _useState2[0],
    setShowTour = _useState2[1];
  return (0,react.createElement)("div", {
    className: "storybook__tourkit"
  }, (0,react.createElement)(References, null), !showTour && (0,react.createElement)("button", {
    onClick: function onClick() {
      return setShowTour(true);
    }
  }, "Start Tour"), showTour && (0,react.createElement)(Tour, {
    placement: placement,
    onClose: function onClose() {
      return setShowTour(false);
    },
    options: options
  }));
};
var NoEffects = function NoEffects() {
  return (0,react.createElement)(StoryTour, {
    options: {
      effects: {}
    }
  });
};
var Spotlight = function Spotlight() {
  return (0,react.createElement)(StoryTour, {
    options: {
      effects: {
        arrowIndicator: true,
        spotlight: {}
      }
    }
  });
};
var Overlay = function Overlay() {
  return (0,react.createElement)(StoryTour, {
    options: {
      effects: {
        arrowIndicator: true,
        overlay: true
      }
    }
  });
};
var SpotlightInteractivity = function SpotlightInteractivity() {
  return (0,react.createElement)(StoryTour, {
    options: {
      effects: {
        spotlight: {
          interactivity: {
            rootElementSelector: '#root',
            enabled: true
          }
        }
      }
    }
  });
};
var AutoScroll = function AutoScroll() {
  return (0,react.createElement)(react.Fragment, null, (0,react.createElement)("div", {
    style: {
      height: '10vh'
    }
  }), (0,react.createElement)(StoryTour, {
    options: {
      effects: {
        autoScroll: {
          behavior: 'smooth'
        }
      }
    }
  }));
};
var Placement = function Placement() {
  return (0,react.createElement)(StoryTour, {
    placement: 'left'
  });
};
NoEffects.parameters = {
  ...NoEffects.parameters,
  docs: {
    ...NoEffects.parameters?.docs,
    source: {
      originalSource: "() => <StoryTour options={{\n  effects: {}\n}} />",
      ...NoEffects.parameters?.docs?.source
    }
  }
};
Spotlight.parameters = {
  ...Spotlight.parameters,
  docs: {
    ...Spotlight.parameters?.docs,
    source: {
      originalSource: "() => <StoryTour options={{\n  effects: {\n    arrowIndicator: true,\n    spotlight: {}\n  }\n}} />",
      ...Spotlight.parameters?.docs?.source
    }
  }
};
Overlay.parameters = {
  ...Overlay.parameters,
  docs: {
    ...Overlay.parameters?.docs,
    source: {
      originalSource: "() => <StoryTour options={{\n  effects: {\n    arrowIndicator: true,\n    overlay: true\n  }\n}} />",
      ...Overlay.parameters?.docs?.source
    }
  }
};
SpotlightInteractivity.parameters = {
  ...SpotlightInteractivity.parameters,
  docs: {
    ...SpotlightInteractivity.parameters?.docs,
    source: {
      originalSource: "() => <StoryTour options={{\n  effects: {\n    spotlight: {\n      interactivity: {\n        rootElementSelector: '#root',\n        enabled: true\n      }\n    }\n  }\n}} />",
      ...SpotlightInteractivity.parameters?.docs?.source
    }
  }
};
AutoScroll.parameters = {
  ...AutoScroll.parameters,
  docs: {
    ...AutoScroll.parameters?.docs,
    source: {
      originalSource: "() => <>\n        <div style={{\n    height: '10vh'\n  }}></div>\n        <StoryTour options={{\n    effects: {\n      autoScroll: {\n        behavior: 'smooth'\n      }\n    }\n  }} />\n    </>",
      ...AutoScroll.parameters?.docs?.source
    }
  }
};
Placement.parameters = {
  ...Placement.parameters,
  docs: {
    ...Placement.parameters?.docs,
    source: {
      originalSource: "() => <StoryTour placement={'left'} />",
      ...Placement.parameters?.docs?.source
    }
  }
};

/***/ })

}]);