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

// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
;// ../../packages/js/components/src/tour-kit/stories/style.scss
// extracted by mini-css-extract-plugin

;// ../../packages/js/components/src/tour-kit/style.scss
// extracted by mini-css-extract-plugin

// EXTERNAL MODULE: ../../node_modules/.pnpm/@automattic+tour-kit@1.1.3__b4d21e829a720309956a17d8c881cdc9/node_modules/@automattic/tour-kit/dist/esm/components/tour-kit.js + 77 modules
var tour_kit = __webpack_require__("../../node_modules/.pnpm/@automattic+tour-kit@1.1.3__b4d21e829a720309956a17d8c881cdc9/node_modules/@automattic/tour-kit/dist/esm/components/tour-kit.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+viewport@6.33.1_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/viewport/build-module/index.js + 29 modules
var build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+viewport@6.33.1_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/viewport/build-module/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/card/card/component.js + 6 modules
var component = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/card/card/component.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/card/card-header/component.js + 1 modules
var card_header_component = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/card/card-header/component.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/card/card-body/component.js + 4 modules
var card_body_component = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/card/card-body/component.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/card/card-footer/component.js + 1 modules
var card_footer_component = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/card/card-footer/component.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/button/index.js
var build_module_button = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/button/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+i18n@6.6.1/node_modules/@wordpress/i18n/build-module/index.js + 3 modules
var i18n_build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@6.6.1/node_modules/@wordpress/i18n/build-module/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js
var jsx_runtime = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
;// ../../packages/js/components/src/tour-kit/components/step-navigation.tsx
/**
 * External dependencies
 */



/**
 * Internal dependencies
 */

const StepNavigation = ({
  currentStepIndex,
  onNextStep,
  onPreviousStep,
  onDismiss,
  steps
}) => {
  const isFirstStep = currentStepIndex === 0;
  const isLastStep = currentStepIndex === steps.length - 1;
  const {
    primaryButton = {
      text: '',
      isDisabled: false,
      isHidden: false
    }
  } = steps[currentStepIndex].meta;
  const {
    secondaryButton = {
      text: ''
    }
  } = steps[currentStepIndex].meta;
  const {
    skipButton = {
      text: '',
      isVisible: false
    }
  } = steps[currentStepIndex].meta;
  const SkipButton = /*#__PURE__*/(0,jsx_runtime.jsx)(build_module_button/* default */.Ay, {
    className: "woocommerce-tour-kit-step-navigation__skip-btn",
    variant: "tertiary",
    onClick: onDismiss('skip-btn'),
    children: skipButton.text || (0,i18n_build_module.__)('Skip', 'woocommerce')
  });
  const NextButton = /*#__PURE__*/(0,jsx_runtime.jsx)(build_module_button/* default */.Ay, {
    className: "woocommerce-tour-kit-step-navigation__next-btn",
    variant: "primary",
    disabled: primaryButton.isDisabled,
    onClick: onNextStep,
    children: primaryButton.text || (0,i18n_build_module.__)('Next', 'woocommerce')
  });
  const BackButton = /*#__PURE__*/(0,jsx_runtime.jsx)(build_module_button/* default */.Ay, {
    className: "woocommerce-tour-kit-step-navigation__back-btn",
    variant: "secondary",
    onClick: onPreviousStep,
    children: secondaryButton.text || (0,i18n_build_module.__)('Back', 'woocommerce')
  });
  const renderButtons = () => {
    if (isLastStep) {
      return /*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
        children: [skipButton.isVisible ? SkipButton : null, !isFirstStep ? BackButton : null // For 1 step tours, isFirstStep and isLastStep can be true simultaneously.
        , /*#__PURE__*/(0,jsx_runtime.jsx)(build_module_button/* default */.Ay, {
          variant: "primary",
          disabled: primaryButton.isDisabled,
          className: "woocommerce-tour-kit-step-navigation__done-btn",
          onClick: onDismiss('done-btn'),
          children: primaryButton.text || (0,i18n_build_module.__)('Done', 'woocommerce')
        })]
      });
    }
    if (isFirstStep) {
      return /*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
        children: [skipButton.isVisible ? SkipButton : null, NextButton]
      });
    }
    return /*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
      children: [skipButton.isVisible ? SkipButton : null, BackButton, NextButton]
    });
  };
  if (primaryButton.isHidden) {
    return null;
  }
  return /*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
    className: "woocommerce-tour-kit-step-navigation",
    children: [/*#__PURE__*/(0,jsx_runtime.jsx)("div", {
      className: "woocommerce-tour-kit-step-navigation__step",
      children: steps.length > 1 ? (0,i18n_build_module/* sprintf */.nv)(/* translators: current progress in tour, eg: "Step 2 of 4" */
      (0,i18n_build_module.__)('Step %1$d of %2$d', 'woocommerce'), currentStepIndex + 1, steps.length) : null
    }), renderButtons()]
  });
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
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/flex/flex/component.js
var flex_component = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/flex/flex/component.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/icon/index.js + 1 modules
var icon = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/icon/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/library/close-small.js
var close_small = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/library/close-small.js");
;// ../../packages/js/components/src/tour-kit/components/step-controls.tsx
/**
 * External dependencies
 */




const StepControls = ({
  onDismiss
}) => {
  return /*#__PURE__*/(0,jsx_runtime.jsx)(flex_component/* default */.A, {
    className: "woocommerce-tour-kit-step-controls",
    justify: "flex-end",
    children: /*#__PURE__*/(0,jsx_runtime.jsx)(build_module_button/* default */.Ay, {
      className: "woocommerce-tour-kit-step-controls__close-btn",
      label: (0,i18n_build_module.__)('Close Tour', 'woocommerce'),
      icon: /*#__PURE__*/(0,jsx_runtime.jsx)(icon/* default */.A, {
        icon: close_small/* default */.A,
        viewBox: "6 4 12 14"
      }),
      iconSize: 16,
      onClick: onDismiss('close-btn')
    })
  });
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
;// ../../packages/js/components/src/tour-kit/components/step.tsx
/**
 * External dependencies
 */




/**
 * Internal dependencies
 */



const getFocusElement = (focusElementSelector, iframeSelector) => {
  if (!focusElementSelector) {
    return null;
  }
  if (iframeSelector) {
    const iframeElement = document.querySelector(iframeSelector);
    if (!iframeElement) {
      return null;
    }
    const innerDoc = iframeElement.contentDocument || iframeElement.contentWindow && iframeElement.contentWindow.document;
    if (!innerDoc) {
      return null;
    }
    return innerDoc.querySelector(focusElementSelector);
  }
  return document.querySelector(focusElementSelector);
};
const Step = ({
  steps,
  currentStepIndex,
  onDismiss,
  onNextStep,
  onPreviousStep,
  setInitialFocusedElement,
  onGoToStep,
  isViewportMobile
}) => {
  const {
    descriptions,
    heading
  } = steps[currentStepIndex].meta;
  const description = descriptions[isViewportMobile ? 'mobile' : 'desktop'] ?? descriptions.desktop;
  const stepRef = (0,react.useRef)();
  const focusElementSelector = steps[currentStepIndex].focusElement?.[isViewportMobile ? 'mobile' : 'desktop'] || null;
  const iframeSelector = steps[currentStepIndex].focusElement?.iframe || null;
  const focusElement = getFocusElement(focusElementSelector, iframeSelector);

  /*
   * Focus the element when step renders.
   */
  (0,react.useEffect)(() => {
    if (focusElement) {
      setInitialFocusedElement(focusElement);
    } else {
      // If no focus element is found, focus the last button in the step so that the user can navigate using keyboard.
      const buttons = stepRef.current?.querySelectorAll('button');
      if (buttons && buttons.length) {
        setInitialFocusedElement(buttons[buttons.length - 1]);
      }
    }
  }, [focusElement, setInitialFocusedElement]);
  return /*#__PURE__*/(0,jsx_runtime.jsxs)(component/* default */.A, {
    ref: stepRef,
    className: "woocommerce-tour-kit-step",
    elevation: 2,
    children: [/*#__PURE__*/(0,jsx_runtime.jsx)(card_header_component/* default */.A, {
      isBorderless: true,
      size: "small",
      children: /*#__PURE__*/(0,jsx_runtime.jsx)(step_controls, {
        onDismiss: onDismiss
      })
    }), /*#__PURE__*/(0,jsx_runtime.jsxs)(card_body_component/* default */.A, {
      className: "woocommerce-tour-kit-step__body",
      size: "small",
      children: [/*#__PURE__*/(0,jsx_runtime.jsx)("h2", {
        className: "woocommerce-tour-kit-step__heading",
        children: heading
      }), /*#__PURE__*/(0,jsx_runtime.jsx)("p", {
        className: "woocommerce-tour-kit-step__description",
        children: description
      })]
    }), /*#__PURE__*/(0,jsx_runtime.jsx)(card_footer_component/* default */.A, {
      isBorderless: true,
      size: "small",
      children: /*#__PURE__*/(0,jsx_runtime.jsx)(step_navigation, {
        currentStepIndex: currentStepIndex,
        onGoToStep: onGoToStep,
        onNextStep: onNextStep,
        onPreviousStep: onPreviousStep,
        onDismiss: onDismiss,
        steps: steps
      })
    })]
  });
};
/* harmony default export */ const step = ((0,build_module/* withViewportMatch */.uE)({
  isViewportMobile: '< medium'
})(Step));
;// ../../packages/js/components/src/tour-kit/index.tsx
/**
 * External dependencies
 */



/**
 * Internal dependencies
 */


const defaultOptions = {
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
const tour_kit_WooTourKit = ({
  config
}) => {
  return /*#__PURE__*/(0,jsx_runtime.jsx)(tour_kit/* default */.A, {
    __temp__className: 'woocommerce-tour-kit',
    config: {
      options: {
        ...defaultOptions,
        ...config.options
      },
      ...config,
      renderers: {
        tourStep: step,
        // Disable minimize feature for woo tour kit.
        tourMinimized: () => null
      }
    }
  });
};
/* harmony default export */ const src_tour_kit = (tour_kit_WooTourKit);
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
;// ../../packages/js/components/src/tour-kit/stories/tour-kit.story.tsx
/**
 * External dependencies
 */


/**
 * Internal dependencies
 */




/* harmony default export */ const tour_kit_story = ({
  title: 'Components/TourKit',
  component: src_tour_kit
});
const References = () => {
  return /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
    className: 'storybook__tourkit-references',
    children: /*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
      className: 'storybook__tourkit-references-container',
      children: [/*#__PURE__*/(0,jsx_runtime.jsx)("div", {
        className: 'storybook__tourkit-references-a',
        children: /*#__PURE__*/(0,jsx_runtime.jsx)("p", {
          children: "Reference A"
        })
      }), /*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
        className: 'storybook__tourkit-references-b',
        children: [/*#__PURE__*/(0,jsx_runtime.jsx)("p", {
          children: "Reference B"
        }), /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
          style: {
            display: 'grid',
            placeItems: 'center'
          },
          children: /*#__PURE__*/(0,jsx_runtime.jsx)("input", {
            style: {
              margin: 'auto',
              display: 'block'
            }
          })
        })]
      }), /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
        className: 'storybook__tourkit-references-c',
        children: /*#__PURE__*/(0,jsx_runtime.jsx)("p", {
          children: "Reference C"
        })
      }), /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
        className: 'storybook__tourkit-references-d',
        children: /*#__PURE__*/(0,jsx_runtime.jsx)("p", {
          children: "Reference D"
        })
      })]
    })
  });
};
const Tour = ({
  onClose,
  options,
  placement
}) => {
  const config = {
    placement,
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
    options: {
      classNames: ['mytour'],
      ...options
    }
  };
  return /*#__PURE__*/(0,jsx_runtime.jsx)(src_tour_kit, {
    config: config
  });
};
const StoryTour = ({
  options = {},
  placement
}) => {
  const [showTour, setShowTour] = (0,react.useState)(false);
  return /*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
    className: "storybook__tourkit",
    children: [/*#__PURE__*/(0,jsx_runtime.jsx)(References, {}), !showTour && /*#__PURE__*/(0,jsx_runtime.jsx)("button", {
      onClick: () => setShowTour(true),
      children: "Start Tour"
    }), showTour && /*#__PURE__*/(0,jsx_runtime.jsx)(Tour, {
      placement: placement,
      onClose: () => setShowTour(false),
      options: options
    })]
  });
};
const NoEffects = () => /*#__PURE__*/(0,jsx_runtime.jsx)(StoryTour, {
  options: {
    effects: {}
  }
});
const Spotlight = () => /*#__PURE__*/(0,jsx_runtime.jsx)(StoryTour, {
  options: {
    effects: {
      arrowIndicator: true,
      spotlight: {}
    }
  }
});
const Overlay = () => /*#__PURE__*/(0,jsx_runtime.jsx)(StoryTour, {
  options: {
    effects: {
      arrowIndicator: true,
      overlay: true
    }
  }
});
const SpotlightInteractivity = () => /*#__PURE__*/(0,jsx_runtime.jsx)(StoryTour, {
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
const AutoScroll = () => /*#__PURE__*/(0,jsx_runtime.jsxs)(jsx_runtime.Fragment, {
  children: [/*#__PURE__*/(0,jsx_runtime.jsx)("div", {
    style: {
      height: '10vh'
    }
  }), /*#__PURE__*/(0,jsx_runtime.jsx)(StoryTour, {
    options: {
      effects: {
        autoScroll: {
          behavior: 'smooth'
        }
      }
    }
  })]
});
const Placement = () => /*#__PURE__*/(0,jsx_runtime.jsx)(StoryTour, {
  placement: 'left'
});
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
try {
    // @ts-ignore
    WooTourKit.displayName = "WooTourKit";
    // @ts-ignore
    WooTourKit.__docgenInfo = { "description": "", "displayName": "WooTourKit", "props": { "config": { "defaultValue": null, "description": "", "name": "config", "required": true, "type": { "name": "WooConfig" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/tour-kit/stories/tour-kit.story.tsx#WooTourKit"] = { docgenInfo: WooTourKit.__docgenInfo, name: "WooTourKit", path: "../../packages/js/components/src/tour-kit/stories/tour-kit.story.tsx#WooTourKit" };
}
catch (__react_docgen_typescript_loader_error) { }

/***/ })

}]);