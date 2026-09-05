"use strict";
(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[3979],{

/***/ "../../plugins/woocommerce/client/admin/client/core-profiler/stories/UserProfile.story.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

// ESM COMPAT FLAG
__webpack_require__.r(__webpack_exports__);

// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  Basic: () => (/* binding */ Basic),
  "default": () => (/* binding */ UserProfile_story)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+i18n@6.6.1/node_modules/@wordpress/i18n/build-module/index.js + 3 modules
var build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@6.6.1/node_modules/@wordpress/i18n/build-module/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/button/index.js
var build_module_button = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/button/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../packages/js/components/src/select-control/index.tsx + 3 modules
var select_control = __webpack_require__("../../packages/js/components/src/select-control/index.tsx");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/icon/index.js
var icon = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/icon/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/library/chevron-down.js
var chevron_down = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/library/chevron-down.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs
var clsx = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
// EXTERNAL MODULE: ../../plugins/woocommerce/client/admin/client/core-profiler/components/navigation/navigation.tsx + 2 modules
var navigation = __webpack_require__("../../plugins/woocommerce/client/admin/client/core-profiler/components/navigation/navigation.tsx");
// EXTERNAL MODULE: ../../plugins/woocommerce/client/admin/client/core-profiler/components/heading/heading.tsx
var heading = __webpack_require__("../../plugins/woocommerce/client/admin/client/core-profiler/components/heading/heading.tsx");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js
var jsx_runtime = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
;// ../../plugins/woocommerce/client/admin/client/core-profiler/components/choice/choice.tsx
/**
 * External dependencies
 */


/**
 * Internal dependencies
 */


const Choice = ({
  className,
  selected,
  title,
  name,
  value,
  onChange,
  subOptionsComponent = null
}) => {
  const changeHandler = () => {
    onChange(value);
  };
  const inputId = 'woocommerce-' + value.replace(/_/g, '-');
  return /*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
    role: "radio",
    className: (0,clsx/* default */.A)('woocommerce-profiler-choice-container', className),
    onClick: changeHandler,
    onKeyDown: e => {
      if (e.key === 'Enter') {
        changeHandler();
      }
    },
    "data-selected": selected ? selected : null,
    tabIndex: 0,
    children: [/*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
      className: "woocommerce-profiler-choice",
      children: [/*#__PURE__*/(0,jsx_runtime.jsx)("input", {
        className: "woocommerce-profiler-choice-input",
        id: inputId,
        name: name,
        type: "radio",
        value: value,
        checked: !!selected,
        onChange: changeHandler,
        "data-selected": selected ? selected : null
        // Stop the input from being focused when the parent div is clicked
        ,
        tabIndex: -1
      }), /*#__PURE__*/(0,jsx_runtime.jsx)("label", {
        htmlFor: inputId,
        className: "choice__title",
        children: title
      })]
    }), selected && subOptionsComponent && /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
      className: "woocommerce-profiler-choice-sub-options",
      children: subOptionsComponent
    })]
  });
};
try {
    // @ts-ignore
    Choice.displayName = "Choice";
    // @ts-ignore
    Choice.__docgenInfo = { "description": "", "displayName": "Choice", "props": { "className": { "defaultValue": null, "description": "", "name": "className", "required": false, "type": { "name": "string" } }, "selected": { "defaultValue": null, "description": "", "name": "selected", "required": true, "type": { "name": "boolean" } }, "title": { "defaultValue": null, "description": "", "name": "title", "required": true, "type": { "name": "ReactNode" } }, "name": { "defaultValue": null, "description": "", "name": "name", "required": true, "type": { "name": "string" } }, "value": { "defaultValue": null, "description": "", "name": "value", "required": true, "type": { "name": "string" } }, "onChange": { "defaultValue": null, "description": "", "name": "onChange", "required": true, "type": { "name": "(value: string) => void" } }, "subOptionsComponent": { "defaultValue": { value: "null" }, "description": "", "name": "subOptionsComponent", "required": false, "type": { "name": "ReactNode" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../plugins/woocommerce/client/admin/client/core-profiler/components/choice/choice.tsx#Choice"] = { docgenInfo: Choice.__docgenInfo, name: "Choice", path: "../../plugins/woocommerce/client/admin/client/core-profiler/components/choice/choice.tsx#Choice" };
}
catch (__react_docgen_typescript_loader_error) { }
// EXTERNAL MODULE: ../../packages/js/components/src/experimental-select-control/select-control.tsx + 1 modules
var experimental_select_control_select_control = __webpack_require__("../../packages/js/components/src/experimental-select-control/select-control.tsx");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/checkbox-control/index.js + 1 modules
var checkbox_control = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/checkbox-control/index.js");
// EXTERNAL MODULE: ../../packages/js/components/src/experimental-select-control/menu.tsx
var menu = __webpack_require__("../../packages/js/components/src/experimental-select-control/menu.tsx");
// EXTERNAL MODULE: ../../packages/js/components/src/experimental-select-control/menu-item.tsx
var menu_item = __webpack_require__("../../packages/js/components/src/experimental-select-control/menu-item.tsx");
;// ../../plugins/woocommerce/client/admin/client/core-profiler/components/multiple-selector/render-menu.tsx
/**
 * External dependencies
 */





const renderMenu = ({
  selectedOptions,
  onOpenClose
}) => ({
  items,
  highlightedIndex,
  isOpen,
  getItemProps,
  getMenuProps
}) => {
  (0,react.useEffect)(() => {
    onOpenClose(isOpen);
  }, [isOpen]);
  return /*#__PURE__*/(0,jsx_runtime.jsx)(menu/* Menu */.W, {
    isOpen: isOpen,
    getMenuProps: getMenuProps,
    scrollIntoViewOnOpen: true,
    children: items.map((item, menuIndex) => {
      const isSelected = selectedOptions.includes(item);
      return /*#__PURE__*/(0,jsx_runtime.jsx)(menu_item/* MenuItem */.D, {
        index: menuIndex,
        item: item,
        getItemProps: getItemProps,
        isActive: highlightedIndex === menuIndex,
        activeStyle: {
          backgroundColor: '#f6f7f7'
        },
        children: /*#__PURE__*/(0,jsx_runtime.jsx)(checkbox_control/* default */.A, {
          __nextHasNoMarginBottom: true,
          className: (0,clsx/* default */.A)('core-profiler__checkbox', {
            'is-selected': isSelected
          }),
          onChange: () => {},
          checked: isSelected,
          label: item.label
        })
      }, `${item.value}`);
    })
  });
};
try {
    // @ts-ignore
    renderMenu.displayName = "renderMenu";
    // @ts-ignore
    renderMenu.__docgenInfo = { "description": "", "displayName": "renderMenu", "props": { "selectedOptions": { "defaultValue": null, "description": "", "name": "selectedOptions", "required": true, "type": { "name": "{ label: string; value: string; }[]" } }, "onOpenClose": { "defaultValue": null, "description": "", "name": "onOpenClose", "required": true, "type": { "name": "(isOpen: boolean) => void" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../plugins/woocommerce/client/admin/client/core-profiler/components/multiple-selector/render-menu.tsx#renderMenu"] = { docgenInfo: renderMenu.__docgenInfo, name: "renderMenu", path: "../../plugins/woocommerce/client/admin/client/core-profiler/components/multiple-selector/render-menu.tsx#renderMenu" };
}
catch (__react_docgen_typescript_loader_error) { }
;// ../../plugins/woocommerce/client/admin/client/core-profiler/components/multiple-selector/multiple-selector.tsx
/**
 * External dependencies
 */



/**
 * Internal dependencies
 */



const MultipleSelector = ({
  options,
  onSelect,
  selectedOptions = [],
  placeholder = (0,build_module.__)('Select platforms', 'woocommerce'),
  onOpenClose = () => {}
}) => {
  return /*#__PURE__*/(0,jsx_runtime.jsx)(experimental_select_control_select_control/* SelectControl */.Y, {
    className: "woocommerce-profiler-platform-selector",
    label: "",
    multiple: true,
    __experimentalOpenMenuOnFocus: true,
    readOnlyWhenClosed: false,
    items: options,
    getFilteredItems: allItems => allItems,
    selected: selectedOptions,
    inputProps: {
      'aria-readonly': true,
      'aria-label': (0,build_module.__)('Use up and down arrow keys to navigate', 'woocommerce')
    },
    onKeyDown: e => {
      if (e.key.length <= 1) {
        e.preventDefault();
        return false;
      }
    },
    placeholder: selectedOptions.length ? '' : placeholder,
    stateReducer: (state, actionAndChanges) => {
      const {
        changes,
        type
      } = actionAndChanges;
      switch (type) {
        case experimental_select_control_select_control/* selectControlStateChangeTypes */.U.ControlledPropUpdatedSelectedItem:
          return {
            ...changes,
            inputValue: state.inputValue
          };
        case experimental_select_control_select_control/* selectControlStateChangeTypes */.U.ItemClick:
          return {
            ...changes,
            isOpen: true,
            inputValue: state.inputValue,
            highlightedIndex: state.highlightedIndex
          };
        case experimental_select_control_select_control/* selectControlStateChangeTypes */.U.InputBlur:
          if (state.isOpen && actionAndChanges.selectItem) {
            // Prevent the menu from closing when clicking on a selected item.
            return {
              ...changes,
              isOpen: true
            };
          }
          return changes;
        default:
          return changes;
      }
    },
    onSelect: item => {
      if (!item) {
        return;
      }
      const exist = selectedOptions.find(existingItem => existingItem.value === item.value);
      const updatedPlatforms = exist ? selectedOptions.filter(existingItem => existingItem.value !== item.value) : [...selectedOptions, item];
      onSelect(updatedPlatforms);
    },
    onRemove: item => onSelect(selectedOptions.filter(i => i !== item)),
    children: renderMenu({
      selectedOptions,
      onOpenClose
    })
  });
};
try {
    // @ts-ignore
    MultipleSelector.displayName = "MultipleSelector";
    // @ts-ignore
    MultipleSelector.__docgenInfo = { "description": "", "displayName": "MultipleSelector", "props": { "options": { "defaultValue": null, "description": "", "name": "options", "required": true, "type": { "name": "{ label: string; value: string; }[]" } }, "onSelect": { "defaultValue": null, "description": "", "name": "onSelect", "required": true, "type": { "name": "(selectedOptions: { label: string; value: string; }[]) => void" } }, "selectedOptions": { "defaultValue": { value: "[]" }, "description": "", "name": "selectedOptions", "required": false, "type": { "name": "{ label: string; value: string; }[]" } }, "placeholder": { "defaultValue": { value: "__( 'Select platforms', 'woocommerce' )" }, "description": "", "name": "placeholder", "required": false, "type": { "name": "string" } }, "onOpenClose": { "defaultValue": { value: "() => {}" }, "description": "", "name": "onOpenClose", "required": false, "type": { "name": "((isOpen: boolean) => void)" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../plugins/woocommerce/client/admin/client/core-profiler/components/multiple-selector/multiple-selector.tsx#MultipleSelector"] = { docgenInfo: MultipleSelector.__docgenInfo, name: "MultipleSelector", path: "../../plugins/woocommerce/client/admin/client/core-profiler/components/multiple-selector/multiple-selector.tsx#MultipleSelector" };
}
catch (__react_docgen_typescript_loader_error) { }
;// ../../plugins/woocommerce/client/admin/client/core-profiler/pages/UserProfile.tsx
/**
 * External dependencies
 */







/**
 * Internal dependencies
 */






const businessOptions = [{
  title: (0,build_module.__)('I’m just starting my business', 'woocommerce'),
  value: 'im_just_starting_my_business'
}, {
  title: (0,build_module.__)('I’m already selling', 'woocommerce'),
  value: 'im_already_selling'
}, {
  title: (0,build_module.__)('I’m setting up a store for a client', 'woocommerce'),
  value: 'im_setting_up_a_store_for_a_client'
}];
const sellingOnlineOptions = [{
  label: (0,build_module.__)('Yes, I’m selling online', 'woocommerce'),
  value: 'yes_im_selling_online',
  key: 'yes_im_selling_online'
}, {
  label: (0,build_module.__)('No, I’m selling offline', 'woocommerce'),
  value: 'no_im_selling_offline',
  key: 'no_im_selling_offline'
}, {
  label: (0,build_module.__)('I’m selling both online and offline', 'woocommerce'),
  value: 'im_selling_both_online_and_offline',
  key: 'im_selling_both_online_and_offline'
}];
const platformOptions = [{
  label: (0,build_module.__)('Amazon', 'woocommerce'),
  value: 'amazon'
}, {
  label: (0,build_module.__)('Adobe Commerce', 'woocommerce'),
  value: 'adobe_commerce'
}, {
  label: (0,build_module.__)('Big Cartel', 'woocommerce'),
  value: 'big_cartel'
}, {
  label: (0,build_module.__)('Big Commerce', 'woocommerce'),
  value: 'big_commerce'
}, {
  label: (0,build_module.__)('Ebay', 'woocommerce'),
  value: 'ebay'
}, {
  label: (0,build_module.__)('Ecwid', 'woocommerce'),
  value: 'ecwid'
}, {
  label: (0,build_module.__)('Etsy', 'woocommerce'),
  value: 'etsy'
}, {
  label: (0,build_module.__)('Facebook Marketplace', 'woocommerce'),
  value: 'facebook_marketplace'
}, {
  label: (0,build_module.__)('Google Shopping', 'woocommerce'),
  value: 'google_shopping'
}, {
  label: (0,build_module.__)('Pinterest', 'woocommerce'),
  value: 'pinterest'
}, {
  label: (0,build_module.__)('Shopify', 'woocommerce'),
  value: 'shopify'
}, {
  label: (0,build_module.__)('Square', 'woocommerce'),
  value: 'square'
}, {
  label: (0,build_module.__)('Squarespace', 'woocommerce'),
  value: 'squarespace'
}, {
  label: (0,build_module.__)('Wix', 'woocommerce'),
  value: 'wix'
}, {
  label: (0,build_module.__)('WordPress', 'woocommerce'),
  value: 'wordpress'
}];
const UserProfile_UserProfile = ({
  sendEvent,
  navigationProgress,
  context
}) => {
  const [businessChoice, setBusinessChoice] = (0,react.useState)(context.userProfile.businessChoice || 'im_just_starting_my_business');
  const [sellingOnlineAnswer, setSellingOnlineAnswer] = (0,react.useState)(context.userProfile.sellingOnlineAnswer || null);
  const [sellingPlatforms, setSellingPlatforms] = (0,react.useState)(context.userProfile.sellingPlatforms || null);
  const [isPlatformDropdownOpen, setIsPlatformDropdownOpen] = (0,react.useState)(false);
  const renderAlreadySellingOptions = () => {
    return /*#__PURE__*/(0,jsx_runtime.jsxs)(jsx_runtime.Fragment, {
      children: [/*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
        className: "woocommerce-profiler-selling-online-question",
        children: [/*#__PURE__*/(0,jsx_runtime.jsx)("p", {
          className: "woocommerce-profiler-question-label",
          children: (0,build_module.__)('Are you selling online?', 'woocommerce')
        }), /*#__PURE__*/(0,jsx_runtime.jsx)(select_control/* default */.A, {
          className: "woocommerce-profiler-select-control__selling-online-question",
          instanceId: 1,
          label: (0,build_module.__)('Select an option', 'woocommerce'),
          autoComplete: "new-password" // disable autocomplete and autofill
          ,
          options: sellingOnlineOptions,
          excludeSelectedOptions: false,
          help: /*#__PURE__*/(0,jsx_runtime.jsx)(icon/* default */.A, {
            icon: chevron_down/* default */.A
          }),
          onChange: selectedOptionKey => {
            setSellingOnlineAnswer(selectedOptionKey);
          },
          multiple: false,
          selected: String(sellingOnlineAnswer)
        })]
      }), sellingOnlineAnswer && ['yes_im_selling_online', 'im_selling_both_online_and_offline'].includes(sellingOnlineAnswer) && /*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
        className: "woocommerce-profiler-selling-platform",
        children: [/*#__PURE__*/(0,jsx_runtime.jsx)("p", {
          className: "woocommerce-profiler-question-label",
          children: (0,build_module.__)('Which platform(s) are you currently using?', 'woocommerce')
        }), /*#__PURE__*/(0,jsx_runtime.jsx)(MultipleSelector, {
          options: platformOptions,
          selectedOptions: platformOptions.filter(option => sellingPlatforms?.includes(option.value)),
          onSelect: items => {
            setSellingPlatforms(items.map(item => item.value));
          },
          onOpenClose: setIsPlatformDropdownOpen
        })]
      })]
    });
  };
  const onContinue = () => {
    sendEvent({
      type: 'USER_PROFILE_COMPLETED',
      payload: {
        userProfile: {
          businessChoice,
          sellingOnlineAnswer: businessChoice === 'im_already_selling' ? sellingOnlineAnswer : null,
          sellingPlatforms: businessChoice === 'im_already_selling' ? sellingPlatforms : null
        }
      }
    });
  };
  return /*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
    className: "woocommerce-profiler-user-profile",
    "data-testid": "core-profiler-user-profile",
    children: [/*#__PURE__*/(0,jsx_runtime.jsx)(navigation/* Navigation */.V, {
      percentage: navigationProgress,
      skipText: (0,build_module.__)('Skip this step', 'woocommerce'),
      onSkip: () => sendEvent({
        type: 'USER_PROFILE_SKIPPED',
        payload: {
          userProfile: {
            skipped: true
          }
        }
      })
    }), /*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
      className: (0,clsx/* default */.A)('woocommerce-profiler-page__content woocommerce-profiler-user-profile__content', {
        'is-platform-selector-open': isPlatformDropdownOpen
      }),
      children: [/*#__PURE__*/(0,jsx_runtime.jsx)(heading/* Heading */.D, {
        className: "woocommerce-profiler__stepper-heading",
        title: (0,build_module.__)('Which one of these best describes you?', 'woocommerce'),
        subTitle: (0,build_module.__)('Let us know where you are in your commerce journey so that we can tailor your Woo experience for you.', 'woocommerce')
      }), /*#__PURE__*/(0,jsx_runtime.jsx)("form", {
        className: "woocommerce-user-profile-choices",
        children: /*#__PURE__*/(0,jsx_runtime.jsxs)("fieldset", {
          children: [/*#__PURE__*/(0,jsx_runtime.jsx)("legend", {
            className: "screen-reader-text",
            children: (0,build_module.__)('Which one of these best describes you?', 'woocommerce')
          }), businessOptions.map(({
            title,
            value
          }) => {
            return /*#__PURE__*/(0,jsx_runtime.jsx)(Choice, {
              name: "user-profile-choice",
              title: title,
              selected: businessChoice === value,
              value: value,
              onChange: _value => {
                setBusinessChoice(_value);
              },
              subOptionsComponent: value === 'im_already_selling' ? renderAlreadySellingOptions() : null
            }, value);
          })]
        })
      }), /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
        className: "woocommerce-profiler-button-container",
        children: /*#__PURE__*/(0,jsx_runtime.jsx)(build_module_button/* default */.Ay, {
          className: "woocommerce-profiler-button",
          variant: "primary",
          onClick: onContinue,
          children: (0,build_module.__)('Continue', 'woocommerce')
        })
      })]
    })]
  });
};
try {
    // @ts-ignore
    UserProfile_UserProfile.displayName = "UserProfile";
    // @ts-ignore
    UserProfile_UserProfile.__docgenInfo = { "description": "", "displayName": "UserProfile", "props": { "sendEvent": { "defaultValue": null, "description": "", "name": "sendEvent", "required": true, "type": { "name": "(event: UserProfileEvent) => void" } }, "navigationProgress": { "defaultValue": null, "description": "", "name": "navigationProgress", "required": true, "type": { "name": "number" } }, "context": { "defaultValue": null, "description": "", "name": "context", "required": true, "type": { "name": "Pick<CoreProfilerStateMachineContext, \"userProfile\">" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../plugins/woocommerce/client/admin/client/core-profiler/pages/UserProfile.tsx#UserProfile"] = { docgenInfo: UserProfile_UserProfile.__docgenInfo, name: "UserProfile", path: "../../plugins/woocommerce/client/admin/client/core-profiler/pages/UserProfile.tsx#UserProfile" };
}
catch (__react_docgen_typescript_loader_error) { }
// EXTERNAL MODULE: ../../plugins/woocommerce/client/admin/client/core-profiler/stories/WithSetupWizardLayout.tsx
var WithSetupWizardLayout = __webpack_require__("../../plugins/woocommerce/client/admin/client/core-profiler/stories/WithSetupWizardLayout.tsx");
;// ../../plugins/woocommerce/client/admin/client/core-profiler/stories/UserProfile.story.tsx
/**
 * Internal dependencies
 */




const Basic = () => /*#__PURE__*/(0,jsx_runtime.jsx)(UserProfile_UserProfile, {
  sendEvent: () => {},
  navigationProgress: 40,
  context: {
    userProfile: {}
  }
});
/* harmony default export */ const UserProfile_story = ({
  title: 'WooCommerce Admin/Core Profiler/User Profile',
  component: UserProfile_UserProfile,
  decorators: [WithSetupWizardLayout/* WithSetupWizardLayout */.b]
});
Basic.parameters = {
  ...Basic.parameters,
  docs: {
    ...Basic.parameters?.docs,
    source: {
      originalSource: "() => <UserProfile sendEvent={() => {}} navigationProgress={40} context={{\n  userProfile: {}\n}} />",
      ...Basic.parameters?.docs?.source
    }
  }
};
try {
    // @ts-ignore
    UserProfile.displayName = "UserProfile";
    // @ts-ignore
    UserProfile.__docgenInfo = { "description": "", "displayName": "UserProfile", "props": { "sendEvent": { "defaultValue": null, "description": "", "name": "sendEvent", "required": true, "type": { "name": "(event: UserProfileEvent) => void" } }, "navigationProgress": { "defaultValue": null, "description": "", "name": "navigationProgress", "required": true, "type": { "name": "number" } }, "context": { "defaultValue": null, "description": "", "name": "context", "required": true, "type": { "name": "Pick<CoreProfilerStateMachineContext, \"userProfile\">" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../plugins/woocommerce/client/admin/client/core-profiler/stories/UserProfile.story.tsx#UserProfile"] = { docgenInfo: UserProfile.__docgenInfo, name: "UserProfile", path: "../../plugins/woocommerce/client/admin/client/core-profiler/stories/UserProfile.story.tsx#UserProfile" };
}
catch (__react_docgen_typescript_loader_error) { }

/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/checkbox-control/index.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  A: () => (/* binding */ checkbox_control_default)
});

// UNUSED EXPORTS: CheckboxControl

// EXTERNAL MODULE: ../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs
var clsx = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+compose@7.45.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-ref-effect/index.mjs
var use_ref_effect = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@7.45.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-ref-effect/index.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+compose@7.45.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-instance-id/index.mjs
var use_instance_id = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@7.45.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-instance-id/index.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+deprecated@4.48.1/node_modules/@wordpress/deprecated/build-module/index.mjs
var build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+deprecated@4.48.1/node_modules/@wordpress/deprecated/build-module/index.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+icons@11.8.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/icons/build-module/icon/index.mjs
var icon = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@11.8.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/icons/build-module/icon/index.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+icons@11.8.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/icons/build-module/library/reset.mjs
var library_reset = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@11.8.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/icons/build-module/library/reset.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+primitives@4.50._58b142b34ba9966bc817120019190c93/node_modules/@wordpress/primitives/build-module/svg/index.mjs
var svg = __webpack_require__("../../node_modules/.pnpm/@wordpress+primitives@4.50._58b142b34ba9966bc817120019190c93/node_modules/@wordpress/primitives/build-module/svg/index.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js
var jsx_runtime = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
;// ../../node_modules/.pnpm/@wordpress+icons@11.8.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/icons/build-module/library/check.mjs
// packages/icons/src/library/check.tsx


var check_default = /* @__PURE__ */ (0,jsx_runtime.jsx)(svg/* SVG */.t4, { xmlns: "http://www.w3.org/2000/svg", viewBox: "0 0 24 24", children: /* @__PURE__ */ (0,jsx_runtime.jsx)(svg/* Path */.wA, { d: "M16.5 7.5 10 13.9l-2.5-2.4-1 1 3.5 3.6 7.5-7.6z" }) });

//# sourceMappingURL=check.mjs.map

// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/base-control/index.js
var base_control = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/base-control/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/h-stack/component.js
var component = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/h-stack/component.js");
;// ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/checkbox-control/index.js








function CheckboxControl(props) {
  const {
    __nextHasNoMarginBottom,
    label,
    className,
    heading,
    checked,
    indeterminate,
    help,
    id: idProp,
    onChange,
    onClick,
    ...additionalProps
  } = props;
  if (heading) {
    (0,build_module/* default */.A)("`heading` prop in `CheckboxControl`", {
      alternative: "a separate element to implement a heading",
      since: "5.8"
    });
  }
  const [showCheckedIcon, setShowCheckedIcon] = (0,react.useState)(false);
  const [showIndeterminateIcon, setShowIndeterminateIcon] = (0,react.useState)(false);
  const ref = (0,use_ref_effect/* default */.A)((node) => {
    if (!node) {
      return;
    }
    node.indeterminate = !!indeterminate;
    setShowCheckedIcon(node.matches(":checked"));
    setShowIndeterminateIcon(node.matches(":indeterminate"));
  }, [checked, indeterminate]);
  const id = (0,use_instance_id/* default */.A)(CheckboxControl, "inspector-checkbox-control", idProp);
  const onChangeValue = (event) => onChange(event.target.checked);
  return /* @__PURE__ */ (0,jsx_runtime.jsx)(base_control/* default */.Ay, {
    __nextHasNoMarginBottom,
    __associatedWPComponentName: "CheckboxControl",
    label: heading,
    id,
    help: help && /* @__PURE__ */ (0,jsx_runtime.jsx)("span", {
      className: "components-checkbox-control__help",
      children: help
    }),
    className: (0,clsx/* default */.A)("components-checkbox-control", className),
    children: /* @__PURE__ */ (0,jsx_runtime.jsxs)(component/* default */.A, {
      spacing: 0,
      justify: "start",
      alignment: "top",
      children: [/* @__PURE__ */ (0,jsx_runtime.jsxs)("span", {
        className: "components-checkbox-control__input-container",
        children: [/* @__PURE__ */ (0,jsx_runtime.jsx)("input", {
          ref,
          id,
          className: "components-checkbox-control__input",
          type: "checkbox",
          value: "1",
          onChange: onChangeValue,
          checked,
          "aria-describedby": !!help ? id + "__help" : void 0,
          onClick: (event) => {
            event.currentTarget.focus();
            onClick?.(event);
          },
          ...additionalProps
        }), showIndeterminateIcon ? /* @__PURE__ */ (0,jsx_runtime.jsx)(icon/* default */.A, {
          icon: library_reset/* default */.A,
          className: "components-checkbox-control__indeterminate",
          role: "presentation"
        }) : null, showCheckedIcon ? /* @__PURE__ */ (0,jsx_runtime.jsx)(icon/* default */.A, {
          icon: check_default,
          className: "components-checkbox-control__checked",
          role: "presentation"
        }) : null]
      }), label && /* @__PURE__ */ (0,jsx_runtime.jsx)("label", {
        className: "components-checkbox-control__label",
        htmlFor: id,
        children: label
      })]
    })
  });
}
var checkbox_control_default = CheckboxControl;

//# sourceMappingURL=index.js.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/utils/values.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   GB: () => (/* binding */ ensureNumber),
/* harmony export */   J5: () => (/* binding */ isValueDefined),
/* harmony export */   r6: () => (/* binding */ isValueEmpty)
/* harmony export */ });
/* unused harmony exports getDefinedValue, stringToNumber */
function isValueDefined(value) {
  return value !== void 0 && value !== null;
}
function isValueEmpty(value) {
  const isEmptyString = value === "";
  return !isValueDefined(value) || isEmptyString;
}
function getDefinedValue(values = [], fallbackValue) {
  var _values$find;
  return (_values$find = values.find(isValueDefined)) !== null && _values$find !== void 0 ? _values$find : fallbackValue;
}
const stringToNumber = (value) => {
  return parseFloat(value);
};
const ensureNumber = (value) => {
  return typeof value === "string" ? stringToNumber(value) : value;
};

//# sourceMappingURL=values.js.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+icons@11.8.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/icons/build-module/icon/index.mjs":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (/* binding */ icon_default)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// packages/icons/src/icon/index.ts

var icon_default = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.forwardRef)(
  ({ icon, size = 24, ...props }, ref) => {
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.cloneElement)(icon, {
      width: size,
      height: size,
      ...props,
      ref
    });
  }
);

//# sourceMappingURL=index.mjs.map


/***/ }),

/***/ "../../node_modules/.pnpm/@wordpress+icons@11.8.0_@types+react@18.3.28_react@18.3.1/node_modules/@wordpress/icons/build-module/library/reset.mjs":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (/* binding */ reset_default)
/* harmony export */ });
/* harmony import */ var _wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+primitives@4.50._58b142b34ba9966bc817120019190c93/node_modules/@wordpress/primitives/build-module/svg/index.mjs");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
// packages/icons/src/library/reset.tsx


var reset_default = /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(_wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__/* .SVG */ .t4, { xmlns: "http://www.w3.org/2000/svg", viewBox: "0 0 24 24", children: /* @__PURE__ */ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(_wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__/* .Path */ .wA, { d: "M7 11.5h10V13H7z" }) });

//# sourceMappingURL=reset.mjs.map


/***/ }),

/***/ "../../packages/js/components/src/experimental-select-control/combo-box.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   a: () => (/* binding */ ComboBox)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var clsx__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
/* harmony import */ var _wordpress_icons__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/icon/index.js");
/* harmony import */ var _wordpress_icons__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/library/chevron-down.js");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
/**
 * External dependencies
 */




const ToggleButton = (0,react__WEBPACK_IMPORTED_MODULE_0__.forwardRef)((props, ref) => {
  // using forwardRef here because getToggleButtonProps injects a ref prop
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("button", {
    className: "woocommerce-experimental-select-control__combox-box-toggle-button",
    ...props,
    ref: ref,
    children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(_wordpress_icons__WEBPACK_IMPORTED_MODULE_2__/* ["default"] */ .A, {
      icon: _wordpress_icons__WEBPACK_IMPORTED_MODULE_3__/* ["default"] */ .A
    })
  });
});
const ComboBox = ({
  children,
  comboBoxProps,
  getToggleButtonProps = () => ({}),
  inputProps,
  suffix,
  showToggleButton
}) => {
  const inputRef = (0,react__WEBPACK_IMPORTED_MODULE_0__.useRef)(null);
  const maybeFocusInput = event => {
    if (!inputRef || !inputRef.current) {
      return;
    }
    if (document.activeElement !== inputRef.current) {
      event.preventDefault();
      inputRef.current.focus();
      event.stopPropagation();
    }
  };
  return (
    /*#__PURE__*/
    // Disable reason: The click event is purely for accidental clicks around the input.
    // Keyboard users are still able to tab to and interact with elements in the combobox.
    /* eslint-disable jsx-a11y/no-static-element-interactions, jsx-a11y/click-events-have-key-events */
    (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsxs)("div", {
      className: (0,clsx__WEBPACK_IMPORTED_MODULE_4__/* ["default"] */ .A)('woocommerce-experimental-select-control__combo-box-wrapper', {
        'woocommerce-experimental-select-control__combo-box-wrapper--disabled': inputProps.disabled
      }),
      onMouseDown: maybeFocusInput,
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsxs)("div", {
        className: "woocommerce-experimental-select-control__items-wrapper",
        children: [children, /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("div", {
          ...comboBoxProps,
          className: "woocommerce-experimental-select-control__combox-box",
          children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("input", {
            ...inputProps,
            ref: node => {
              inputRef.current = node;
              if (typeof inputProps.ref === 'function') {
                inputProps.ref(node);
              }
            }
          })
        })]
      }), suffix && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("div", {
        className: "woocommerce-experimental-select-control__suffix",
        children: suffix
      }), showToggleButton && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(ToggleButton, {
        ...getToggleButtonProps()
      })]
    })
  );
};
try {
    // @ts-ignore
    ComboBox.displayName = "ComboBox";
    // @ts-ignore
    ComboBox.__docgenInfo = { "description": "", "displayName": "ComboBox", "props": { "comboBoxProps": { "defaultValue": null, "description": "", "name": "comboBoxProps", "required": true, "type": { "name": "DetailedHTMLProps<HTMLAttributes<HTMLDivElement>, HTMLDivElement>" } }, "inputProps": { "defaultValue": null, "description": "", "name": "inputProps", "required": true, "type": { "name": "DetailedHTMLProps<InputHTMLAttributes<HTMLInputElement>, HTMLInputElement>" } }, "getToggleButtonProps": { "defaultValue": { value: "() => ( {} )" }, "description": "", "name": "getToggleButtonProps", "required": false, "type": { "name": "(() => Omit<DetailedHTMLProps<ButtonHTMLAttributes<HTMLButtonElement>, HTMLButtonElement>, \"ref\">)" } }, "suffix": { "defaultValue": null, "description": "", "name": "suffix", "required": false, "type": { "name": "Element | null" } }, "showToggleButton": { "defaultValue": null, "description": "", "name": "showToggleButton", "required": false, "type": { "name": "boolean" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/experimental-select-control/combo-box.tsx#ComboBox"] = { docgenInfo: ComboBox.__docgenInfo, name: "ComboBox", path: "../../packages/js/components/src/experimental-select-control/combo-box.tsx#ComboBox" };
}
catch (__react_docgen_typescript_loader_error) { }

/***/ }),

/***/ "../../packages/js/components/src/experimental-select-control/menu-item.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   D: () => (/* binding */ MenuItem)
/* harmony export */ });
/* harmony import */ var clsx__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/tooltip/index.js");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
/**
 * External dependencies
 */




/**
 * Internal dependencies
 */

const MenuItem = ({
  children,
  getItemProps,
  index,
  isActive,
  activeStyle = {
    backgroundColor: '#bde4ff'
  },
  item,
  tooltipText,
  className
}) => {
  function renderListItem() {
    const itemProps = getItemProps({
      item,
      index
    });
    return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("li", {
      ...itemProps,
      style: isActive ? activeStyle : itemProps.style,
      className: (0,clsx__WEBPACK_IMPORTED_MODULE_1__/* ["default"] */ .A)('woocommerce-experimental-select-control__menu-item', itemProps.className, className),
      children: children
    });
  }
  if (tooltipText) {
    return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__/* ["default"] */ .Ay, {
      text: tooltipText,
      position: "top center",
      children: renderListItem()
    });
  }
  return renderListItem();
};
try {
    // @ts-ignore
    MenuItem.displayName = "MenuItem";
    // @ts-ignore
    MenuItem.__docgenInfo = { "description": "", "displayName": "MenuItem", "props": { "index": { "defaultValue": null, "description": "", "name": "index", "required": true, "type": { "name": "number" } }, "isActive": { "defaultValue": null, "description": "", "name": "isActive", "required": true, "type": { "name": "boolean" } }, "item": { "defaultValue": null, "description": "", "name": "item", "required": true, "type": { "name": "ItemType" } }, "getItemProps": { "defaultValue": null, "description": "", "name": "getItemProps", "required": true, "type": { "name": "getItemPropsType<ItemType>" } }, "activeStyle": { "defaultValue": { value: "{ backgroundColor: '#bde4ff' }" }, "description": "", "name": "activeStyle", "required": false, "type": { "name": "CSSProperties" } }, "tooltipText": { "defaultValue": null, "description": "", "name": "tooltipText", "required": false, "type": { "name": "string" } }, "className": { "defaultValue": null, "description": "", "name": "className", "required": false, "type": { "name": "string" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/experimental-select-control/menu-item.tsx#MenuItem"] = { docgenInfo: MenuItem.__docgenInfo, name: "MenuItem", path: "../../packages/js/components/src/experimental-select-control/menu-item.tsx#MenuItem" };
}
catch (__react_docgen_typescript_loader_error) { }

/***/ }),

/***/ "../../packages/js/components/src/experimental-select-control/menu.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   W: () => (/* binding */ Menu),
/* harmony export */   c: () => (/* binding */ MenuSlot)
/* harmony export */ });
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/popover/index.js");
/* harmony import */ var clsx__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__("../../node_modules/.pnpm/react-dom@18.3.1_react@18.3.1/node_modules/react-dom/index.js");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
/**
 * External dependencies
 */




/**
 * Internal dependencies
 */

const Menu = ({
  children,
  getMenuProps,
  isOpen,
  className,
  position = 'bottom right',
  scrollIntoViewOnOpen = false
}) => {
  const selectControlMenuRef = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useRef)(null);
  const popoverRef = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useRef)(null);
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useLayoutEffect)(() => {
    const comboboxWrapper = selectControlMenuRef.current?.closest('.woocommerce-experimental-select-control__combo-box-wrapper');
    const popoverContent = popoverRef.current?.querySelector('.components-popover__content');
    if (comboboxWrapper && comboboxWrapper?.clientWidth > 0) {
      if (popoverContent) {
        popoverContent.style.width = `${comboboxWrapper.getBoundingClientRect().width}px`;
      }
    }
  }, [selectControlMenuRef.current, selectControlMenuRef.current?.clientWidth, popoverRef.current]);

  // Scroll the selected item into view when the menu opens.
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useEffect)(() => {
    if (isOpen && scrollIntoViewOnOpen) {
      selectControlMenuRef.current?.scrollIntoView();
    }
  }, [isOpen, scrollIntoViewOnOpen]);

  /* eslint-disable jsx-a11y/no-noninteractive-element-interactions, jsx-a11y/click-events-have-key-events */
  /* Disabled because of the onmouseup on the ul element below. */
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("div", {
    ref: selectControlMenuRef,
    className: "woocommerce-experimental-select-control__menu",
    children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("div", {
      children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__/* ["default"] */ .Ay, {
        __unstableSlotName: "woocommerce-select-control-menu",
        focusOnMount: false,
        className: (0,clsx__WEBPACK_IMPORTED_MODULE_3__/* ["default"] */ .A)('woocommerce-experimental-select-control__popover-menu', {
          'is-open': isOpen,
          'has-results': _wordpress_element__WEBPACK_IMPORTED_MODULE_1__.Children.count(children) > 0
        }),
        position: position,
        animate: false,
        resize: false,
        ref: popoverRef,
        children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("ul", {
          ...getMenuProps(),
          className: (0,clsx__WEBPACK_IMPORTED_MODULE_3__/* ["default"] */ .A)('woocommerce-experimental-select-control__popover-menu-container', className),
          onMouseUp: e =>
          // Fix to prevent select control dropdown from closing when selecting within the Popover.
          e.stopPropagation(),
          children: isOpen && children
        })
      })
    })
  });
  /* eslint-enable jsx-a11y/no-noninteractive-element-interactions, jsx-a11y/click-events-have-key-events */
};
const MenuSlot = () => (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_4__.createPortal)(/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("div", {
  "aria-live": "off",
  children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__/* ["default"] */ .Ay.Slot, {
    name: "woocommerce-select-control-menu"
  })
}), document.body);
try {
    // @ts-ignore
    Menu.displayName = "Menu";
    // @ts-ignore
    Menu.__docgenInfo = { "description": "", "displayName": "Menu", "props": { "getMenuProps": { "defaultValue": null, "description": "", "name": "getMenuProps", "required": true, "type": { "name": "getMenuPropsType" } }, "isOpen": { "defaultValue": null, "description": "", "name": "isOpen", "required": true, "type": { "name": "boolean" } }, "className": { "defaultValue": null, "description": "", "name": "className", "required": false, "type": { "name": "string" } }, "position": { "defaultValue": { value: "bottom right" }, "description": "", "name": "position", "required": false, "type": { "name": "enum", "value": [{ "value": "\"top left\"" }, { "value": "\"top right\"" }, { "value": "\"top center\"" }, { "value": "\"middle left\"" }, { "value": "\"middle right\"" }, { "value": "\"middle center\"" }, { "value": "\"bottom left\"" }, { "value": "\"bottom right\"" }, { "value": "\"bottom center\"" }, { "value": "\"top\"" }, { "value": "\"bottom\"" }, { "value": "\"middle\"" }, { "value": "\"top center top\"" }, { "value": "\"top center bottom\"" }, { "value": "\"top center left\"" }, { "value": "\"top center right\"" }, { "value": "\"top left top\"" }, { "value": "\"top left bottom\"" }, { "value": "\"top left left\"" }, { "value": "\"top left right\"" }, { "value": "\"top right top\"" }, { "value": "\"top right bottom\"" }, { "value": "\"top right left\"" }, { "value": "\"top right right\"" }, { "value": "\"bottom center top\"" }, { "value": "\"bottom center bottom\"" }, { "value": "\"bottom center left\"" }, { "value": "\"bottom center right\"" }, { "value": "\"bottom left top\"" }, { "value": "\"bottom left bottom\"" }, { "value": "\"bottom left left\"" }, { "value": "\"bottom left right\"" }, { "value": "\"bottom right top\"" }, { "value": "\"bottom right bottom\"" }, { "value": "\"bottom right left\"" }, { "value": "\"bottom right right\"" }, { "value": "\"middle center top\"" }, { "value": "\"middle center bottom\"" }, { "value": "\"middle center left\"" }, { "value": "\"middle center right\"" }, { "value": "\"middle left top\"" }, { "value": "\"middle left bottom\"" }, { "value": "\"middle left left\"" }, { "value": "\"middle left right\"" }, { "value": "\"middle right top\"" }, { "value": "\"middle right bottom\"" }, { "value": "\"middle right left\"" }, { "value": "\"middle right right\"" }] } }, "scrollIntoViewOnOpen": { "defaultValue": { value: "false" }, "description": "", "name": "scrollIntoViewOnOpen", "required": false, "type": { "name": "boolean" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/experimental-select-control/menu.tsx#Menu"] = { docgenInfo: Menu.__docgenInfo, name: "Menu", path: "../../packages/js/components/src/experimental-select-control/menu.tsx#Menu" };
}
catch (__react_docgen_typescript_loader_error) { }

/***/ }),

/***/ "../../packages/js/components/src/experimental-select-control/select-control.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  Y: () => (/* binding */ SelectControl),
  U: () => (/* binding */ selectControlStateChangeTypes)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs
var clsx = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/downshift@6.1.12_react@18.3.1/node_modules/downshift/dist/downshift.esm.js + 1 modules
var downshift_esm = __webpack_require__("../../node_modules/.pnpm/downshift@6.1.12_react@18.3.1/node_modules/downshift/dist/downshift.esm.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+compose@7.33.1_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-instance-id/index.js
var use_instance_id = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@7.33.1_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-instance-id/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/library/chevron-down.js
var chevron_down = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/library/chevron-down.js");
// EXTERNAL MODULE: ../../packages/js/components/src/experimental-select-control/selected-items.tsx
var selected_items = __webpack_require__("../../packages/js/components/src/experimental-select-control/selected-items.tsx");
// EXTERNAL MODULE: ../../packages/js/components/src/experimental-select-control/combo-box.tsx
var combo_box = __webpack_require__("../../packages/js/components/src/experimental-select-control/combo-box.tsx");
// EXTERNAL MODULE: ../../packages/js/components/src/experimental-select-control/menu.tsx
var menu = __webpack_require__("../../packages/js/components/src/experimental-select-control/menu.tsx");
// EXTERNAL MODULE: ../../packages/js/components/src/experimental-select-control/menu-item.tsx
var menu_item = __webpack_require__("../../packages/js/components/src/experimental-select-control/menu-item.tsx");
// EXTERNAL MODULE: ../../packages/js/components/src/experimental-select-control/suffix-icon.tsx
var suffix_icon = __webpack_require__("../../packages/js/components/src/experimental-select-control/suffix-icon.tsx");
;// ../../packages/js/components/src/experimental-select-control/utils.ts
/**
 * Internal dependencies
 */

function isDefaultItemType(item) {
  return Boolean(item) && item.label !== undefined && item.value !== undefined;
}
const defaultGetItemLabel = item => {
  if (isDefaultItemType(item)) {
    return item.label;
  }
  return '';
};
const defaultGetItemValue = item => {
  if (isDefaultItemType(item)) {
    return item.value;
  }
  return '';
};
const defaultGetFilteredItems = (allItems, inputValue, selectedItems, getItemLabel) => {
  const escapedInputValue = inputValue.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
  const re = new RegExp(escapedInputValue, 'gi');
  return allItems.filter(item => {
    return selectedItems.indexOf(item) < 0 && re.test(getItemLabel(item).toLowerCase());
  });
};
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js
var jsx_runtime = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
;// ../../packages/js/components/src/experimental-select-control/select-control.tsx
/**
 * External dependencies
 */






/**
 * Internal dependencies
 */








const selectControlStateChangeTypes = downshift_esm/* useCombobox */.Bp.stateChangeTypes;
function SelectControl({
  getItemLabel = defaultGetItemLabel,
  getItemValue = defaultGetItemValue,
  hasExternalTags = false,
  children = ({
    items: renderItems,
    highlightedIndex,
    getItemProps,
    getMenuProps,
    isOpen
  }) => {
    return /*#__PURE__*/(0,jsx_runtime.jsx)(menu/* Menu */.W, {
      getMenuProps: getMenuProps,
      isOpen: isOpen,
      children: renderItems.map((item, index) => /*#__PURE__*/(0,jsx_runtime.jsx)(menu_item/* MenuItem */.D, {
        index: index,
        isActive: highlightedIndex === index,
        item: item,
        getItemProps: getItemProps,
        children: getItemLabel(item)
      }, `${getItemValue(item)}${index}`))
    });
  },
  multiple = false,
  items,
  label,
  getFilteredItems = defaultGetFilteredItems,
  onInputChange = () => null,
  onRemove = () => null,
  onSelect = () => null,
  onFocus = () => null,
  onBlur = () => null,
  onKeyDown = () => null,
  stateReducer = (state, actionAndChanges) => actionAndChanges.changes,
  placeholder,
  selected,
  className,
  disabled,
  inputProps = {},
  suffix = /*#__PURE__*/(0,jsx_runtime.jsx)(suffix_icon/* SuffixIcon */.f, {
    icon: chevron_down/* default */.A
  }),
  showToggleButton = false,
  readOnlyWhenClosed = true,
  __experimentalOpenMenuOnFocus = false
}) {
  const [isFocused, setIsFocused] = (0,react.useState)(false);
  const [inputValue, setInputValue] = (0,react.useState)('');
  const instanceId = (0,use_instance_id/* default */.A)(SelectControl, 'woocommerce-experimental-select-control');
  const innerInputClassName = 'woocommerce-experimental-select-control__input';
  const selectControlWrapperRef = (0,react.useRef)(null);
  let selectedItems = selected === null ? [] : selected;
  selectedItems = Array.isArray(selectedItems) ? selectedItems : [selectedItems].filter(Boolean);
  const singleSelectedItem = !multiple && selectedItems.length ? selectedItems[0] : null;
  const filteredItems = getFilteredItems(items, inputValue, selectedItems, getItemLabel);
  const {
    getSelectedItemProps,
    getDropdownProps,
    removeSelectedItem
    // eslint-disable-next-line @typescript-eslint/ban-ts-comment
    // @ts-ignore
  } = (0,downshift_esm/* useMultipleSelection */.mH)({
    itemToString: getItemLabel,
    selectedItems
  });
  (0,react.useEffect)(() => {
    if (multiple) {
      return;
    }
    setInputValue(getItemLabel(singleSelectedItem));
  }, [getItemLabel, multiple, singleSelectedItem]);
  const {
    isOpen,
    getLabelProps,
    getMenuProps,
    getToggleButtonProps,
    getInputProps,
    getComboboxProps,
    highlightedIndex,
    getItemProps,
    selectItem,
    // @ts-expect-error We're allowed to use the property.
    selectedItem: comboboxSingleSelectedItem,
    openMenu,
    closeMenu
  } = (0,downshift_esm/* useCombobox */.Bp)({
    id: instanceId,
    initialSelectedItem: singleSelectedItem,
    inputValue,
    items: filteredItems,
    selectedItem: multiple ? null : singleSelectedItem,
    itemToString: getItemLabel,
    onSelectedItemChange: ({
      selectedItem
    }) => {
      if (selectedItem) {
        onSelect(selectedItem);
      } else if (singleSelectedItem) {
        onRemove(singleSelectedItem);
      }
    },
    onInputValueChange: ({
      inputValue: value,
      ...changes
    }) => {
      if (value !== undefined) {
        setInputValue(value);
        onInputChange(value, changes);
      }
    },
    // @ts-expect-error We're allowed to use the property.
    stateReducer: (state, actionAndChanges) => {
      const {
        changes,
        type
      } = actionAndChanges;
      let newChanges;
      switch (type) {
        case selectControlStateChangeTypes.InputBlur:
          // Set input back to selected item if there is a selected item, blank otherwise.
          newChanges = {
            ...changes,
            selectedItem: !changes.inputValue?.length && !multiple ? null : changes.selectedItem,
            inputValue: changes.selectedItem === state.selectedItem && changes.inputValue?.length && !multiple ? getItemLabel(comboboxSingleSelectedItem) : ''
          };
          break;
        case selectControlStateChangeTypes.InputKeyDownEnter:
        case selectControlStateChangeTypes.FunctionSelectItem:
        case selectControlStateChangeTypes.ItemClick:
          if (changes.selectedItem && multiple) {
            newChanges = {
              ...changes,
              inputValue: ''
            };
          }
          break;
        default:
          break;
      }
      return stateReducer(state, {
        ...actionAndChanges,
        changes: newChanges ?? changes
      });
    }
  });
  const isEventOutside = event => {
    const selectControlWrapperElement = selectControlWrapperRef.current;
    const menuElement = document.getElementById(`${instanceId}-menu`);
    const parentPopoverMenuElement = menuElement?.closest('.woocommerce-experimental-select-control__popover-menu');
    return !selectControlWrapperElement?.contains(event.relatedTarget) && !parentPopoverMenuElement?.contains(event.relatedTarget);
  };
  const onRemoveItem = item => {
    selectItem(null);
    removeSelectedItem(item);
    onRemove(item);
  };
  const isReadOnly = readOnlyWhenClosed && !isOpen && !isFocused;
  const selectedItemTags = multiple ? /*#__PURE__*/(0,jsx_runtime.jsx)(selected_items/* SelectedItems */.K, {
    items: selectedItems,
    isReadOnly: isReadOnly,
    getItemLabel: getItemLabel,
    getItemValue: getItemValue,
    getSelectedItemProps: getSelectedItemProps,
    onRemove: onRemoveItem
  }) : null;
  return /*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
    id: instanceId,
    ref: selectControlWrapperRef,
    className: (0,clsx/* default */.A)('woocommerce-experimental-select-control', className, {
      'is-read-only': isReadOnly,
      'is-focused': isFocused,
      'is-multiple': multiple,
      'has-selected-items': selectedItems.length
    }),
    children: [label && /*#__PURE__*/(0,jsx_runtime.jsx)("label", {
      ...getLabelProps(),
      className: "woocommerce-experimental-select-control__label",
      children: label
    }), /*#__PURE__*/(0,jsx_runtime.jsx)(combo_box/* ComboBox */.a, {
      comboBoxProps: getComboboxProps(),
      getToggleButtonProps: getToggleButtonProps,
      inputProps: getInputProps({
        ...getDropdownProps({
          preventKeyAction: isOpen
        }),
        className: innerInputClassName,
        onFocus: () => {
          setIsFocused(true);
          onFocus({
            inputValue
          });
          if (__experimentalOpenMenuOnFocus) {
            openMenu();
          }
        },
        onBlur: event => {
          if (isEventOutside(event)) {
            setIsFocused(false);
            onBlur({
              inputValue
            });
          }
        },
        onKeyDown,
        placeholder,
        disabled,
        ...inputProps
      }),
      suffix: suffix,
      showToggleButton: showToggleButton,
      children: /*#__PURE__*/(0,jsx_runtime.jsxs)(jsx_runtime.Fragment, {
        children: [children({
          items: filteredItems,
          highlightedIndex,
          getItemProps,
          getMenuProps,
          isOpen,
          getItemLabel,
          getItemValue,
          selectItem,
          setInputValue,
          openMenu,
          closeMenu
        }), !hasExternalTags && selectedItemTags]
      })
    }), hasExternalTags && selectedItemTags]
  });
}

try {
    // @ts-ignore
    SelectControl.displayName = "SelectControl";
    // @ts-ignore
    SelectControl.__docgenInfo = { "description": "", "displayName": "SelectControl", "props": { "items": { "defaultValue": null, "description": "", "name": "items", "required": true, "type": { "name": "ItemType[]" } }, "label": { "defaultValue": null, "description": "", "name": "label", "required": true, "type": { "name": "string | Element" } }, "getItemLabel": { "defaultValue": { value: "< ItemType >( item: ItemType | null ) => {\n\tif ( isDefaultItemType< ItemType >( item ) ) {\n\t\treturn item.label;\n\t}\n\treturn '';\n}" }, "description": "", "name": "getItemLabel", "required": false, "type": { "name": "getItemLabelType<ItemType>" } }, "getItemValue": { "defaultValue": { value: "< ItemType >( item: ItemType | null ) => {\n\tif ( isDefaultItemType< ItemType >( item ) ) {\n\t\treturn item.value;\n\t}\n\treturn '';\n}" }, "description": "", "name": "getItemValue", "required": false, "type": { "name": "getItemValueType<ItemType>" } }, "getFilteredItems": { "defaultValue": { value: "< ItemType >(\n\tallItems: ItemType[],\n\tinputValue: string,\n\tselectedItems: ItemType[],\n\tgetItemLabel: getItemLabelType< ItemType >\n) => {\n\tconst escapedInputValue = inputValue.replace(\n\t\t/[.*+?^${}()|[\\]\\\\]/g,\n\t\t'\\\\$&'\n\t);\n\tconst re = new RegExp( escapedInputValue, 'gi' );\n\n\treturn allItems.filter( ( item ) => {\n\t\treturn (\n\t\t\tselectedItems.indexOf( item ) < 0 &&\n\t\t\tre.test( getItemLabel( item ).toLowerCase() )\n\t\t);\n\t} );\n}" }, "description": "", "name": "getFilteredItems", "required": false, "type": { "name": "((allItems: ItemType[], inputValue: string, selectedItems: ItemType[], getItemLabel: getItemLabelType<ItemType>) => ItemType[])" } }, "hasExternalTags": { "defaultValue": { value: "false" }, "description": "", "name": "hasExternalTags", "required": false, "type": { "name": "boolean" } }, "multiple": { "defaultValue": { value: "false" }, "description": "", "name": "multiple", "required": false, "type": { "name": "boolean" } }, "onInputChange": { "defaultValue": { value: "() => null" }, "description": "", "name": "onInputChange", "required": false, "type": { "name": "((value: string, changes: Partial<Omit<UseComboboxState<ItemType>, \"inputValue\">>) => void)" } }, "onRemove": { "defaultValue": { value: "() => null" }, "description": "", "name": "onRemove", "required": false, "type": { "name": "((item: ItemType) => void)" } }, "onSelect": { "defaultValue": { value: "() => null" }, "description": "", "name": "onSelect", "required": false, "type": { "name": "((selected: ItemType) => void)" } }, "onKeyDown": { "defaultValue": { value: "() => null" }, "description": "", "name": "onKeyDown", "required": false, "type": { "name": "((e: KeyboardEvent) => void)" } }, "onFocus": { "defaultValue": { value: "() => null" }, "description": "", "name": "onFocus", "required": false, "type": { "name": "((data: { inputValue: string; }) => void)" } }, "onBlur": { "defaultValue": { value: "() => null" }, "description": "", "name": "onBlur", "required": false, "type": { "name": "((data: { inputValue: string; }) => void)" } }, "stateReducer": { "defaultValue": { value: "( state, actionAndChanges ) => actionAndChanges.changes" }, "description": "", "name": "stateReducer", "required": false, "type": { "name": "((state: UseComboboxState<ItemType | null>, actionAndChanges: UseComboboxStateChangeOptions<ItemType | null>) => Partial<...>)" } }, "placeholder": { "defaultValue": null, "description": "", "name": "placeholder", "required": false, "type": { "name": "string" } }, "selected": { "defaultValue": null, "description": "", "name": "selected", "required": true, "type": { "name": "ItemType | ItemType[] | null" } }, "className": { "defaultValue": null, "description": "", "name": "className", "required": false, "type": { "name": "string" } }, "disabled": { "defaultValue": null, "description": "", "name": "disabled", "required": false, "type": { "name": "boolean" } }, "inputProps": { "defaultValue": { value: "{}" }, "description": "", "name": "inputProps", "required": false, "type": { "name": "GetInputPropsOptions" } }, "suffix": { "defaultValue": { value: "<SuffixIcon icon={ chevronDown } />" }, "description": "", "name": "suffix", "required": false, "type": { "name": "Element | null" } }, "showToggleButton": { "defaultValue": { value: "false" }, "description": "", "name": "showToggleButton", "required": false, "type": { "name": "boolean" } }, "readOnlyWhenClosed": { "defaultValue": { value: "true" }, "description": "", "name": "readOnlyWhenClosed", "required": false, "type": { "name": "boolean" } }, "__experimentalOpenMenuOnFocus": { "defaultValue": { value: "false" }, "description": "This is a feature already implemented in downshift@7.0.0 through the\nreducer. In order for us to use it this prop is added temporarily until\ncurrent downshift version get updated.\n@see https://www.downshift-js.com/use-multiple-selection#usage-with-combobox", "name": "__experimentalOpenMenuOnFocus", "required": false, "type": { "name": "boolean" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/experimental-select-control/select-control.tsx#SelectControl"] = { docgenInfo: SelectControl.__docgenInfo, name: "SelectControl", path: "../../packages/js/components/src/experimental-select-control/select-control.tsx#SelectControl" };
}
catch (__react_docgen_typescript_loader_error) { }

/***/ }),

/***/ "../../packages/js/components/src/experimental-select-control/selected-items.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   K: () => (/* binding */ SelectedItems)
/* harmony export */ });
/* harmony import */ var clsx__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var _wordpress_html_entities__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+html-entities@4.33.1/node_modules/@wordpress/html-entities/build-module/index.js");
/* harmony import */ var _tag__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__("../../packages/js/components/src/tag/index.tsx");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
/**
 * External dependencies
 */




/**
 * Internal dependencies
 */


const PrivateSelectedItems = ({
  isReadOnly,
  items,
  getItemLabel,
  getItemValue,
  getSelectedItemProps,
  onRemove,
  onBlur,
  onSelectedItemsEnd
}, ref) => {
  const classes = (0,clsx__WEBPACK_IMPORTED_MODULE_1__/* ["default"] */ .A)('woocommerce-experimental-select-control__selected-items', {
    'is-read-only': isReadOnly
  });
  const lastRemoveButtonRef = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.useRef)(null);
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.useImperativeHandle)(ref, () => {
    return () => lastRemoveButtonRef.current?.focus();
  }, []);
  if (isReadOnly) {
    return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("div", {
      className: classes,
      children: items.map(item => {
        return (0,_wordpress_html_entities__WEBPACK_IMPORTED_MODULE_3__/* .decodeEntities */ .S)(getItemLabel(item));
      }).join(', ')
    });
  }
  const focusSibling = event => {
    const selectedItem = event.target.closest('.woocommerce-experimental-select-control__selected-item');
    const sibling = event.key === 'ArrowLeft' || event.key === 'Backspace' ? selectedItem?.previousSibling : selectedItem?.nextSibling;
    if (sibling) {
      sibling.querySelector('.woocommerce-tag__remove')?.focus();
      return true;
    }
    return false;
  };
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("div", {
    className: classes,
    children: items.map((item, index) => {
      return (
        /*#__PURE__*/
        // Disable reason: We prevent the default action to keep the input focused on click.
        // Keyboard users are unaffected by this change.
        /* eslint-disable jsx-a11y/no-static-element-interactions, jsx-a11y/click-events-have-key-events */
        (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("div", {
          className: "woocommerce-experimental-select-control__selected-item",
          ...getSelectedItemProps({
            selectedItem: item,
            index
          }),
          onMouseDown: event => {
            event.preventDefault();
          },
          onClick: event => {
            event.preventDefault();
          },
          onKeyDown: event => {
            if (event.key === 'ArrowLeft' || event.key === 'ArrowRight') {
              const focused = focusSibling(event);
              if (!focused && event.key === 'ArrowRight' && onSelectedItemsEnd) {
                onSelectedItemsEnd();
              }
            } else if (event.key === 'ArrowUp' || event.key === 'ArrowDown') {
              event.preventDefault(); // prevent unwanted scroll
            } else if (event.key === 'Backspace') {
              onRemove(item);
              focusSibling(event);
            }
          },
          onBlur: onBlur,
          children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(_tag__WEBPACK_IMPORTED_MODULE_4__/* ["default"] */ .A, {
            id: getItemValue(item),
            remove: () => () => onRemove(item),
            label: getItemLabel(item),
            ref: index === items.length - 1 ? lastRemoveButtonRef : undefined
          })
        }, `selected-item-${index}`)
      );
    })
  });
};
const SelectedItems = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.forwardRef)(PrivateSelectedItems);
try {
    // @ts-ignore
    SelectedItems.displayName = "SelectedItems";
    // @ts-ignore
    SelectedItems.__docgenInfo = { "description": "", "displayName": "SelectedItems", "props": { "isReadOnly": { "defaultValue": null, "description": "", "name": "isReadOnly", "required": true, "type": { "name": "boolean" } }, "items": { "defaultValue": null, "description": "", "name": "items", "required": true, "type": { "name": "ItemType[]" } }, "getItemLabel": { "defaultValue": null, "description": "", "name": "getItemLabel", "required": true, "type": { "name": "getItemLabelType<ItemType>" } }, "getItemValue": { "defaultValue": null, "description": "", "name": "getItemValue", "required": true, "type": { "name": "getItemValueType<ItemType>" } }, "getSelectedItemProps": { "defaultValue": null, "description": "", "name": "getSelectedItemProps", "required": true, "type": { "name": "({ selectedItem: any, index: any }: { selectedItem: any; index: any; }) => { [key: string]: string; }" } }, "onRemove": { "defaultValue": null, "description": "", "name": "onRemove", "required": true, "type": { "name": "(item: ItemType) => void" } }, "onBlur": { "defaultValue": null, "description": "", "name": "onBlur", "required": false, "type": { "name": "((event: FocusEvent<Element, Element>) => void)" } }, "onSelectedItemsEnd": { "defaultValue": null, "description": "", "name": "onSelectedItemsEnd", "required": false, "type": { "name": "(() => void)" } }, "ref": { "defaultValue": null, "description": "", "name": "ref", "required": false, "type": { "name": "ForwardedRef<SelectedItemFocusHandle>" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/experimental-select-control/selected-items.tsx#SelectedItems"] = { docgenInfo: SelectedItems.__docgenInfo, name: "SelectedItems", path: "../../packages/js/components/src/experimental-select-control/selected-items.tsx#SelectedItems" };
}
catch (__react_docgen_typescript_loader_error) { }

/***/ }),

/***/ "../../packages/js/components/src/experimental-select-control/suffix-icon.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   f: () => (/* binding */ SuffixIcon)
/* harmony export */ });
/* harmony import */ var _wordpress_icons__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/icon/index.js");
/* harmony import */ var clsx__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
/**
 * External dependencies
 */




const SuffixIcon = ({
  className = '',
  icon
}) => {
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("div", {
    className: (0,clsx__WEBPACK_IMPORTED_MODULE_1__/* ["default"] */ .A)('woocommerce-experimental-select-control__suffix-icon', className),
    children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(_wordpress_icons__WEBPACK_IMPORTED_MODULE_2__/* ["default"] */ .A, {
      icon: icon,
      size: 24
    })
  });
};
try {
    // @ts-ignore
    SuffixIcon.displayName = "SuffixIcon";
    // @ts-ignore
    SuffixIcon.__docgenInfo = { "description": "", "displayName": "SuffixIcon", "props": { "icon": { "defaultValue": null, "description": "", "name": "icon", "required": true, "type": { "name": "Element" } }, "className": { "defaultValue": { value: "" }, "description": "", "name": "className", "required": false, "type": { "name": "string" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/experimental-select-control/suffix-icon.tsx#SuffixIcon"] = { docgenInfo: SuffixIcon.__docgenInfo, name: "SuffixIcon", path: "../../packages/js/components/src/experimental-select-control/suffix-icon.tsx#SuffixIcon" };
}
catch (__react_docgen_typescript_loader_error) { }

/***/ }),

/***/ "../../plugins/woocommerce/client/admin/client/core-profiler/components/heading/heading.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   D: () => (/* binding */ Heading)
/* harmony export */ });
/* harmony import */ var clsx__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
/**
 * External dependencies
 */


/**
 * Internal dependencies
 */


const Heading = ({
  className,
  title,
  subTitle
}) => {
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsxs)("div", {
    className: (0,clsx__WEBPACK_IMPORTED_MODULE_1__/* ["default"] */ .A)('woocommerce-profiler-heading', className),
    children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("h1", {
      className: "woocommerce-profiler-heading__title",
      children: title
    }), subTitle && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("h2", {
      className: "woocommerce-profiler-heading__subtitle",
      children: subTitle
    })]
  });
};
try {
    // @ts-ignore
    Heading.displayName = "Heading";
    // @ts-ignore
    Heading.__docgenInfo = { "description": "", "displayName": "Heading", "props": { "title": { "defaultValue": null, "description": "", "name": "title", "required": true, "type": { "name": "string | Element" } }, "subTitle": { "defaultValue": null, "description": "", "name": "subTitle", "required": false, "type": { "name": "string | Element" } }, "className": { "defaultValue": null, "description": "", "name": "className", "required": false, "type": { "name": "string" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../plugins/woocommerce/client/admin/client/core-profiler/components/heading/heading.tsx#Heading"] = { docgenInfo: Heading.__docgenInfo, name: "Heading", path: "../../plugins/woocommerce/client/admin/client/core-profiler/components/heading/heading.tsx#Heading" };
}
catch (__react_docgen_typescript_loader_error) { }

/***/ }),

/***/ "../../plugins/woocommerce/client/admin/client/core-profiler/components/navigation/navigation.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  V: () => (/* binding */ Navigation)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+i18n@6.6.1/node_modules/@wordpress/i18n/build-module/index.js + 3 modules
var build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@6.6.1/node_modules/@wordpress/i18n/build-module/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs
var clsx = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/button/index.js
var build_module_button = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/button/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js
var jsx_runtime = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
;// ../../plugins/woocommerce/client/admin/client/core-profiler/components/navigation/woologo.tsx

/* eslint-disable max-len */
const WooLogo = () => {
  return /*#__PURE__*/(0,jsx_runtime.jsxs)("svg", {
    width: "91",
    height: "24",
    viewBox: "0 0 91 24",
    fill: "none",
    xmlns: "http://www.w3.org/2000/svg",
    className: "wc-icon wc-icon__woo-logo new-branding",
    children: [/*#__PURE__*/(0,jsx_runtime.jsx)("path", {
      d: "M79.0537 0C72.2755 0 67.0874 5.10851 67.0874 12C67.0874 18.8915 72.2755 24 79.0537 24C85.832 24 91.0002 18.8915 91.0002 12C91.0002 5.10851 85.7923 0 79.0537 0ZM79.0537 16.6277C76.5094 16.6277 74.7602 14.6644 74.7602 12C74.7602 9.33555 76.4895 7.37228 79.0537 7.37228C81.6179 7.37228 83.3473 9.33555 83.3473 12C83.3473 14.6644 81.5981 16.6277 79.0537 16.6277Z",
      fill: "#873DFF"
    }), /*#__PURE__*/(0,jsx_runtime.jsx)("path", {
      d: "M53.7285 0C46.9503 0 41.7622 5.10851 41.7622 12C41.7622 18.8915 46.9701 24 53.7285 24C60.4869 24 65.675 18.8915 65.675 12C65.675 5.10851 60.4671 0 53.7285 0ZM53.7285 16.6277C51.1842 16.6277 49.435 14.6644 49.435 12C49.435 9.33555 51.1643 7.37228 53.7285 7.37228C56.2928 7.37228 58.0221 9.33555 58.0221 12C58.0221 14.6644 56.2928 16.6277 53.7285 16.6277Z",
      fill: "#873DFF"
    }), /*#__PURE__*/(0,jsx_runtime.jsx)("path", {
      d: "M11.688 24C14.3715 24 16.5183 22.6577 18.1483 19.5726L21.7461 12.7813V18.5509C21.7461 21.9365 23.9327 24 27.3317 24C29.9556 24 31.8837 22.798 33.792 19.5726L42.1207 5.44908C43.9494 2.36394 42.6574 0 38.6421 0C36.4953 0 35.1039 0.721201 33.8516 3.08514L28.107 13.9232V4.28714C28.107 1.40234 26.7553 0 24.2308 0C22.2629 0 20.6926 0.861435 19.4602 3.26544L14.0535 13.9032V4.38731C14.0535 1.30217 12.8012 0 9.74004 0H3.53822C1.19266 0 0 1.10184 0 3.14524C0 5.18864 1.23241 6.33054 3.53822 6.33054H6.08255V18.5309C6.10243 21.9365 8.3486 24 11.688 24Z",
      fill: "#873DFF"
    })]
  });
};
/* eslint-enable max-len */

/* harmony default export */ const woologo = (WooLogo);
;// ../../plugins/woocommerce/client/admin/client/core-profiler/components/progress-bar/progress-bar.tsx
/**
 * External dependencies
 */

/**
 * Internal dependencies
 */



const ProgressBar = ({
  className = '',
  percent = 0,
  color = '#674399',
  bgcolor = 'var(--wp-admin-theme-color)'
}) => {
  const containerStyles = {
    backgroundColor: bgcolor
  };
  const fillerStyles = {
    backgroundColor: color,
    width: `${percent}%`,
    display: percent === 0 ? 'none' : 'inherit'
  };
  return /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
    className: `woocommerce-profiler-progress-bar ${className}`,
    children: /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
      className: "woocommerce-profiler-progress-bar__container",
      style: containerStyles,
      children: /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
        className: "woocommerce-profiler-progress-bar__filler",
        style: fillerStyles
      })
    })
  });
};
/* harmony default export */ const progress_bar = (ProgressBar);
try {
    // @ts-ignore
    progressbar.displayName = "progressbar";
    // @ts-ignore
    progressbar.__docgenInfo = { "description": "", "displayName": "progressbar", "props": { "className": { "defaultValue": { value: "" }, "description": "", "name": "className", "required": false, "type": { "name": "string" } }, "percent": { "defaultValue": { value: "0" }, "description": "", "name": "percent", "required": false, "type": { "name": "number" } }, "color": { "defaultValue": { value: "#674399" }, "description": "", "name": "color", "required": false, "type": { "name": "string" } }, "bgcolor": { "defaultValue": { value: "var(--wp-admin-theme-color)" }, "description": "", "name": "bgcolor", "required": false, "type": { "name": "string" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../plugins/woocommerce/client/admin/client/core-profiler/components/progress-bar/progress-bar.tsx#progressbar"] = { docgenInfo: progressbar.__docgenInfo, name: "progressbar", path: "../../plugins/woocommerce/client/admin/client/core-profiler/components/progress-bar/progress-bar.tsx#progressbar" };
}
catch (__react_docgen_typescript_loader_error) { }
;// ../../plugins/woocommerce/client/admin/client/core-profiler/components/navigation/navigation.tsx
/**
 * External dependencies
 */



/**
 * Internal dependencies
 */




const Navigation = ({
  percentage = 0,
  onSkip,
  skipText = (0,build_module.__)('Skip this step', 'woocommerce'),
  showProgress = true,
  showLogo = true,
  classNames = {},
  progressBarColor = 'var(--wp-admin-theme-color)'
}) => {
  return /*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
    className: (0,clsx/* default */.A)('woocommerce-profiler-navigation-container', classNames),
    children: [showProgress && /*#__PURE__*/(0,jsx_runtime.jsx)(progress_bar, {
      className: 'progress-bar',
      percent: percentage,
      color: progressBarColor,
      bgcolor: 'transparent'
    }), /*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
      className: "woocommerce-profiler-navigation",
      children: [/*#__PURE__*/(0,jsx_runtime.jsx)("div", {
        className: "woocommerce-profiler-navigation-col-left",
        children: showLogo && /*#__PURE__*/(0,jsx_runtime.jsx)("span", {
          className: "woologo",
          children: /*#__PURE__*/(0,jsx_runtime.jsx)(woologo, {})
        })
      }), /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
        className: "woocommerce-profiler-navigation-col-right",
        children: typeof onSkip === 'function' && /*#__PURE__*/(0,jsx_runtime.jsx)(build_module_button/* default */.Ay, {
          onClick: onSkip,
          className: (0,clsx/* default */.A)('woocommerce-profiler-navigation-skip-link', classNames.mobile ? 'mobile' : ''),
          isLink: true,
          children: skipText
        })
      })]
    })]
  });
};
try {
    // @ts-ignore
    Navigation.displayName = "Navigation";
    // @ts-ignore
    Navigation.__docgenInfo = { "description": "", "displayName": "Navigation", "props": { "onSkip": { "defaultValue": null, "description": "", "name": "onSkip", "required": false, "type": { "name": "(() => void)" } }, "percentage": { "defaultValue": { value: "0" }, "description": "", "name": "percentage", "required": false, "type": { "name": "number" } }, "previous": { "defaultValue": null, "description": "", "name": "previous", "required": false, "type": { "name": "string" } }, "showProgress": { "defaultValue": { value: "true" }, "description": "", "name": "showProgress", "required": false, "type": { "name": "boolean" } }, "showLogo": { "defaultValue": { value: "true" }, "description": "", "name": "showLogo", "required": false, "type": { "name": "boolean" } }, "classNames": { "defaultValue": { value: "{}" }, "description": "", "name": "classNames", "required": false, "type": { "name": "{ mobile?: boolean; }" } }, "skipText": { "defaultValue": { value: "__( 'Skip this step', 'woocommerce' )" }, "description": "", "name": "skipText", "required": false, "type": { "name": "string" } }, "progressBarColor": { "defaultValue": { value: "var(--wp-admin-theme-color)" }, "description": "", "name": "progressBarColor", "required": false, "type": { "name": "string" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../plugins/woocommerce/client/admin/client/core-profiler/components/navigation/navigation.tsx#Navigation"] = { docgenInfo: Navigation.__docgenInfo, name: "Navigation", path: "../../plugins/woocommerce/client/admin/client/core-profiler/components/navigation/navigation.tsx#Navigation" };
}
catch (__react_docgen_typescript_loader_error) { }

/***/ }),

/***/ "../../plugins/woocommerce/client/admin/client/core-profiler/stories/WithSetupWizardLayout.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   b: () => (/* binding */ WithSetupWizardLayout)
/* harmony export */ });
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");

const WithSetupWizardLayout = Story => {
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("div", {
    className: "woocommerce-profile-wizard__body woocommerce-admin-full-screen",
    children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(Story, {})
  });
};
try {
    // @ts-ignore
    WithSetupWizardLayout.displayName = "WithSetupWizardLayout";
    // @ts-ignore
    WithSetupWizardLayout.__docgenInfo = { "description": "", "displayName": "WithSetupWizardLayout", "props": {} };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../plugins/woocommerce/client/admin/client/core-profiler/stories/WithSetupWizardLayout.tsx#WithSetupWizardLayout"] = { docgenInfo: WithSetupWizardLayout.__docgenInfo, name: "WithSetupWizardLayout", path: "../../plugins/woocommerce/client/admin/client/core-profiler/stories/WithSetupWizardLayout.tsx#WithSetupWizardLayout" };
}
catch (__react_docgen_typescript_loader_error) { }

/***/ })

}]);