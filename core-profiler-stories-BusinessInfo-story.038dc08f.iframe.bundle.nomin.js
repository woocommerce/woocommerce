"use strict";
(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[9891],{

/***/ "../../plugins/woocommerce/client/admin/client/core-profiler/stories/BusinessInfo.story.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

// ESM COMPAT FLAG
__webpack_require__.r(__webpack_exports__);

// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  Basic: () => (/* binding */ Basic),
  "default": () => (/* binding */ BusinessInfo_story)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+i18n@6.6.1/node_modules/@wordpress/i18n/build-module/index.js + 3 modules
var build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@6.6.1/node_modules/@wordpress/i18n/build-module/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/text-control/index.js
var text_control = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/text-control/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/notice/index.js + 4 modules
var notice = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/notice/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/button/index.js
var build_module_button = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/button/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/checkbox-control/index.js + 1 modules
var checkbox_control = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/checkbox-control/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/spinner/index.js + 1 modules
var spinner = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/spinner/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@automattic+components@2.2._435207ec680c9cfbb4d39a274a897f24/node_modules/@automattic/components/dist/esm/forms/form-input-validation/index.js + 6 modules
var form_input_validation = __webpack_require__("../../node_modules/.pnpm/@automattic+components@2.2._435207ec680c9cfbb4d39a274a897f24/node_modules/@automattic/components/dist/esm/forms/form-input-validation/index.js");
// EXTERNAL MODULE: ../../packages/js/components/src/select-control/index.tsx + 3 modules
var select_control = __webpack_require__("../../packages/js/components/src/select-control/index.tsx");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/icon/index.js
var icon = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/icon/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/library/chevron-down.js
var chevron_down = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/library/chevron-down.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+element@6.33.1/node_modules/@wordpress/element/build-module/create-interpolate-element.js
var create_interpolate_element = __webpack_require__("../../node_modules/.pnpm/@wordpress+element@6.33.1/node_modules/@wordpress/element/build-module/create-interpolate-element.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+html-entities@4.33.1/node_modules/@wordpress/html-entities/build-module/index.js
var html_entities_build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+html-entities@4.33.1/node_modules/@wordpress/html-entities/build-module/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/zod@3.25.76/node_modules/zod/v3/types.js + 6 modules
var types = __webpack_require__("../../node_modules/.pnpm/zod@3.25.76/node_modules/zod/v3/types.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs
var clsx = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
// EXTERNAL MODULE: ../../plugins/woocommerce/client/admin/client/core-profiler/components/heading/heading.tsx
var heading = __webpack_require__("../../plugins/woocommerce/client/admin/client/core-profiler/components/heading/heading.tsx");
// EXTERNAL MODULE: ../../plugins/woocommerce/client/admin/client/core-profiler/components/navigation/navigation.tsx + 2 modules
var navigation = __webpack_require__("../../plugins/woocommerce/client/admin/client/core-profiler/components/navigation/navigation.tsx");
// EXTERNAL MODULE: ../../plugins/woocommerce/client/admin/client/core-profiler/components/geolocation-country-select/geolocation-country-select.tsx
var geolocation_country_select = __webpack_require__("../../plugins/woocommerce/client/admin/client/core-profiler/components/geolocation-country-select/geolocation-country-select.tsx");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js
var jsx_runtime = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
;// ../../plugins/woocommerce/client/admin/client/core-profiler/pages/BusinessInfo.tsx
/**
 * External dependencies
 */










/**
 * Internal dependencies
 */





/** These are some store names that are known to be set by default and not likely to be used as actual names */

const POSSIBLY_DEFAULT_STORE_NAMES = (/* unused pure expression or super */ null && ([undefined, 'woocommerce', 'Site Title', '']));
const industryChoices = [{
  label: (0,build_module.__)('Clothing and accessories', 'woocommerce'),
  key: 'clothing_and_accessories'
}, {
  label: (0,build_module.__)('Food and drink', 'woocommerce'),
  key: 'food_and_drink'
}, {
  label: (0,build_module.__)('Electronics and computers', 'woocommerce'),
  key: 'electronics_and_computers'
}, {
  label: (0,build_module.__)('Health and beauty', 'woocommerce'),
  key: 'health_and_beauty'
}, {
  label: (0,build_module.__)('Education and learning', 'woocommerce'),
  key: 'education_and_learning'
}, {
  label: (0,build_module.__)('Home, furniture and garden', 'woocommerce'),
  key: 'home_furniture_and_garden'
}, {
  label: (0,build_module.__)('Arts and crafts', 'woocommerce'),
  key: 'arts_and_crafts'
}, {
  label: (0,build_module.__)('Sports and recreation', 'woocommerce'),
  key: 'sports_and_recreation'
}, {
  label: (0,build_module.__)('Other', 'woocommerce'),
  key: 'other'
}];
const selectIndustryMapping = {
  im_just_starting_my_business: (0,build_module.__)('What type of products or services do you plan to sell?', 'woocommerce'),
  im_already_selling: (0,build_module.__)('Which industry is your business in?', 'woocommerce'),
  im_setting_up_a_store_for_a_client: (0,build_module.__)('Which industry is your client’s business in?', 'woocommerce')
};
const BusinessInfo_BusinessInfo = ({
  context,
  navigationProgress,
  sendEvent
}) => {
  const {
    geolocatedLocation,
    userProfile: {
      businessChoice
    },
    businessInfo,
    countries,
    onboardingProfile: {
      is_store_country_set: isStoreCountrySet = false,
      industry: industryFromOnboardingProfile = [],
      business_choice: businessChoiceFromOnboardingProfile = '',
      is_agree_marketing: isOptInMarketingFromOnboardingProfile = false,
      store_email: storeEmailAddressFromOnboardingProfile = ''
    } = {},
    currentUserEmail
  } = context;
  const [storeName, setStoreName] = (0,react.useState)(businessInfo.storeName || '');
  const [storeCountry, setStoreCountry] = (0,react.useState)({
    key: '',
    label: ''
  });
  (0,react.useEffect)(() => {
    if (isStoreCountrySet) {
      const previouslyStoredCountryOption = countries.find(country => country.key === businessInfo.location);
      setStoreCountry(previouslyStoredCountryOption || {
        key: '',
        label: ''
      });
    }
  }, [businessInfo.location, countries, isStoreCountrySet]);
  const [industry, setIndustry] = (0,react.useState)(industryFromOnboardingProfile ? industryChoices.find(choice => choice.key === industryFromOnboardingProfile[0]) : undefined);
  const selectCountryLabel = (0,build_module.__)('Select country/region', 'woocommerce');
  const selectIndustryQuestionLabel = selectIndustryMapping[businessChoice || businessChoiceFromOnboardingProfile || 'im_just_starting_my_business'];
  const [hasSubmitted, setHasSubmitted] = (0,react.useState)(false);
  const [isEmailInvalid, setIsEmailInvalid] = (0,react.useState)(false);
  const [storeEmailAddress, setEmailAddress] = (0,react.useState)(storeEmailAddressFromOnboardingProfile || currentUserEmail || '');
  const [isOptInMarketing, setIsOptInMarketing] = (0,react.useState)(isOptInMarketingFromOnboardingProfile || false);
  const [doValidate, setDoValidate] = (0,react.useState)(false);
  const [geolocationOverruled, setGeolocationOverruled] = (0,react.useState)(false);
  (0,react.useEffect)(() => {
    if (doValidate) {
      const parseEmail = types/* string */.Yj().email().safeParse(storeEmailAddress);
      setIsEmailInvalid(isOptInMarketing && !parseEmail.success);
      setDoValidate(false);
    }
  }, [isOptInMarketing, doValidate, storeEmailAddress]);
  return /*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
    className: "woocommerce-profiler-business-information",
    "data-testid": "core-profiler-business-information",
    children: [/*#__PURE__*/(0,jsx_runtime.jsx)(navigation/* Navigation */.V, {
      percentage: navigationProgress
    }), /*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
      className: "woocommerce-profiler-page__content woocommerce-profiler-business-information__content",
      children: [/*#__PURE__*/(0,jsx_runtime.jsx)(heading/* Heading */.D, {
        className: "woocommerce-profiler__stepper-heading",
        title: (0,build_module.__)('Tell us a bit about your store', 'woocommerce'),
        subTitle: (0,build_module.__)('We’ll use this information to help you set up payments, shipping, and taxes, as well as recommending the best theme for your store.', 'woocommerce')
      }), /*#__PURE__*/(0,jsx_runtime.jsxs)("form", {
        className: "woocommerce-profiler-business-information-form",
        autoComplete: "off",
        children: [/*#__PURE__*/(0,jsx_runtime.jsx)(text_control/* default */.A, {
          __nextHasNoMarginBottom: true,
          className: "woocommerce-profiler-business-info-store-name",
          onChange: value => {
            setStoreName(value);
          },
          value: (0,html_entities_build_module/* decodeEntities */.S)(storeName),
          label: /*#__PURE__*/(0,jsx_runtime.jsx)(jsx_runtime.Fragment, {
            children: (0,build_module.__)('Give your store a name', 'woocommerce')
          }),
          placeholder: (0,build_module.__)('Ex. My awesome store', 'woocommerce')
        }), /*#__PURE__*/(0,jsx_runtime.jsx)("p", {
          className: "woocommerce-profiler-question-subtext",
          children: (0,build_module.__)('Don’t worry — you can always change it later!', 'woocommerce')
        }), /*#__PURE__*/(0,jsx_runtime.jsx)("p", {
          className: "woocommerce-profiler-question-label",
          children: selectIndustryQuestionLabel
        }), /*#__PURE__*/(0,jsx_runtime.jsx)(select_control/* default */.A, {
          className: "woocommerce-profiler-select-control__industry",
          instanceId: 1,
          placeholder: (0,build_module.__)('Select an industry', 'woocommerce'),
          label: (0,build_module.__)('Select an industry', 'woocommerce'),
          options: industryChoices,
          excludeSelectedOptions: false,
          help: /*#__PURE__*/(0,jsx_runtime.jsx)(icon/* default */.A, {
            icon: chevron_down/* default */.A
          }),
          onChange: results => {
            if (Array.isArray(results) && results.length) {
              setIndustry(results[0]);
            }
          },
          selected: industry ? [industry] : [],
          showAllOnFocus: true,
          isSearchable: true
        }), /*#__PURE__*/(0,jsx_runtime.jsxs)("p", {
          className: "woocommerce-profiler-question-label",
          children: [(0,build_module.__)('Where is your store located?', 'woocommerce'), /*#__PURE__*/(0,jsx_runtime.jsx)("span", {
            className: "woocommerce-profiler-question-required",
            children: '*'
          })]
        }), /*#__PURE__*/(0,jsx_runtime.jsx)(geolocation_country_select/* GeolocationCountrySelect */.p, {
          label: selectCountryLabel,
          placeholder: selectCountryLabel,
          countries: countries,
          initialValue: storeCountry,
          onChange: countryStateOption => {
            setStoreCountry(countryStateOption);
          },
          geolocatedLocation: geolocatedLocation,
          onGeolocationOverruledChange: overruled => {
            setGeolocationOverruled(overruled);
          }
        }), countries.length === 0 && /*#__PURE__*/(0,jsx_runtime.jsx)(notice/* default */.A, {
          className: "woocommerce-profiler-select-control__country-error",
          isDismissible: false,
          status: "error",
          children: (0,create_interpolate_element/* default */.A)((0,build_module.__)('Oops! We encountered a problem while fetching the list of countries to choose from. <retryButton/> or <skipButton/>', 'woocommerce'), {
            retryButton: /*#__PURE__*/(0,jsx_runtime.jsx)(build_module_button/* default */.Ay, {
              onClick: () => {
                sendEvent({
                  type: 'RETRY_PRE_BUSINESS_INFO'
                });
              },
              variant: "tertiary",
              children: (0,build_module.__)('Please try again', 'woocommerce')
            }),
            skipButton: /*#__PURE__*/(0,jsx_runtime.jsx)(build_module_button/* default */.Ay, {
              onClick: () => {
                sendEvent({
                  type: 'SKIP_BUSINESS_INFO_STEP'
                });
              },
              variant: "tertiary",
              children: (0,build_module.__)('Skip this step', 'woocommerce')
            })
          })
        }), /*#__PURE__*/(0,jsx_runtime.jsxs)(jsx_runtime.Fragment, {
          children: [/*#__PURE__*/(0,jsx_runtime.jsx)(text_control/* default */.A, {
            __nextHasNoMarginBottom: true,
            className: (0,clsx/* default */.A)('woocommerce-profiler-business-info-email-address', {
              'is-error': isEmailInvalid
            }),
            onChange: value => {
              if (isEmailInvalid) {
                setDoValidate(true); // trigger validation as we want to feedback to the user as soon as it becomes valid
              }
              setEmailAddress(value);
            },
            onBlur: () => {
              setDoValidate(true);
            },
            value: (0,html_entities_build_module/* decodeEntities */.S)(storeEmailAddress),
            label: /*#__PURE__*/(0,jsx_runtime.jsxs)(jsx_runtime.Fragment, {
              children: [(0,build_module.__)('Your email address', 'woocommerce'), isOptInMarketing && /*#__PURE__*/(0,jsx_runtime.jsx)("span", {
                className: "woocommerce-profiler-question-required",
                children: '*'
              })]
            }),
            placeholder: (0,build_module.__)('wordpress@example.com', 'woocommerce')
          }), isEmailInvalid && /*#__PURE__*/(0,jsx_runtime.jsx)(form_input_validation/* default */.A, {
            isError: true,
            text: (0,build_module.__)('This email is not valid.', 'woocommerce')
          }), /*#__PURE__*/(0,jsx_runtime.jsx)(checkbox_control/* default */.A, {
            __nextHasNoMarginBottom: true,
            className: "core-profiler__checkbox",
            label: (0,build_module.__)('Opt-in to receive tips, discounts, and recommendations from the Woo team directly in your inbox.', 'woocommerce'),
            checked: isOptInMarketing,
            onChange: isChecked => {
              setIsOptInMarketing(isChecked);
              setDoValidate(true);
            }
          })]
        })]
      }), /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
        className: "woocommerce-profiler-button-container",
        children: /*#__PURE__*/(0,jsx_runtime.jsx)(build_module_button/* default */.Ay, {
          className: "woocommerce-profiler-button",
          variant: "primary",
          disabled: !storeCountry.key || isEmailInvalid,
          onClick: () => {
            sendEvent({
              type: 'BUSINESS_INFO_COMPLETED',
              payload: {
                storeName,
                industry: industry?.key,
                storeLocation: storeCountry.key,
                geolocationOverruled: geolocationOverruled || false,
                isOptInMarketing,
                storeEmailAddress
              }
            });
            setHasSubmitted(true);
          },
          children: hasSubmitted ? /*#__PURE__*/(0,jsx_runtime.jsx)(spinner/* default */.Ay, {}) : (0,build_module.__)('Continue', 'woocommerce')
        })
      })]
    })]
  });
};
try {
    // @ts-ignore
    POSSIBLY_DEFAULT_STORE_NAMES.displayName = "POSSIBLY_DEFAULT_STORE_NAMES";
    // @ts-ignore
    POSSIBLY_DEFAULT_STORE_NAMES.__docgenInfo = { "description": "These are some store names that are known to be set by default and not likely to be used as actual names", "displayName": "POSSIBLY_DEFAULT_STORE_NAMES", "props": {} };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../plugins/woocommerce/client/admin/client/core-profiler/pages/BusinessInfo.tsx#POSSIBLY_DEFAULT_STORE_NAMES"] = { docgenInfo: POSSIBLY_DEFAULT_STORE_NAMES.__docgenInfo, name: "POSSIBLY_DEFAULT_STORE_NAMES", path: "../../plugins/woocommerce/client/admin/client/core-profiler/pages/BusinessInfo.tsx#POSSIBLY_DEFAULT_STORE_NAMES" };
}
catch (__react_docgen_typescript_loader_error) { }
try {
    // @ts-ignore
    BusinessInfo_BusinessInfo.displayName = "BusinessInfo";
    // @ts-ignore
    BusinessInfo_BusinessInfo.__docgenInfo = { "description": "", "displayName": "BusinessInfo", "props": { "context": { "defaultValue": null, "description": "", "name": "context", "required": true, "type": { "name": "BusinessInfoContextProps" } }, "navigationProgress": { "defaultValue": null, "description": "", "name": "navigationProgress", "required": true, "type": { "name": "number" } }, "sendEvent": { "defaultValue": null, "description": "", "name": "sendEvent", "required": true, "type": { "name": "(event: BusinessInfoEvent) => void" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../plugins/woocommerce/client/admin/client/core-profiler/pages/BusinessInfo.tsx#BusinessInfo"] = { docgenInfo: BusinessInfo_BusinessInfo.__docgenInfo, name: "BusinessInfo", path: "../../plugins/woocommerce/client/admin/client/core-profiler/pages/BusinessInfo.tsx#BusinessInfo" };
}
catch (__react_docgen_typescript_loader_error) { }
// EXTERNAL MODULE: ../../plugins/woocommerce/client/admin/client/core-profiler/stories/WithSetupWizardLayout.tsx
var WithSetupWizardLayout = __webpack_require__("../../plugins/woocommerce/client/admin/client/core-profiler/stories/WithSetupWizardLayout.tsx");
;// ../../plugins/woocommerce/client/admin/client/core-profiler/stories/BusinessInfo.story.tsx
/**
 * Internal dependencies
 */




const Basic = () => /*#__PURE__*/(0,jsx_runtime.jsx)(BusinessInfo_BusinessInfo, {
  sendEvent: () => {},
  navigationProgress: 60,
  context: {
    geolocatedLocation: {
      latitude: '-37.83961',
      longitude: '144.94228',
      country_short: 'AU',
      country_long: 'Australia',
      region: 'Victoria',
      city: 'Port Melbourne'
    },
    userProfile: {},
    businessInfo: {},
    countries: [{
      key: 'US',
      label: 'United States'
    }],
    onboardingProfile: {
      is_store_country_set: false,
      industry: ['clothing_and_accessories'],
      business_choice: 'im_just_starting_my_business'
    }
  }
});
/* harmony default export */ const BusinessInfo_story = ({
  title: 'WooCommerce Admin/Core Profiler/Business Info',
  component: BusinessInfo_BusinessInfo,
  decorators: [WithSetupWizardLayout/* WithSetupWizardLayout */.b]
});
Basic.parameters = {
  ...Basic.parameters,
  docs: {
    ...Basic.parameters?.docs,
    source: {
      originalSource: "() => <BusinessInfo sendEvent={() => {}} navigationProgress={60} context={{\n  geolocatedLocation: {\n    latitude: '-37.83961',\n    longitude: '144.94228',\n    country_short: 'AU',\n    country_long: 'Australia',\n    region: 'Victoria',\n    city: 'Port Melbourne'\n  },\n  userProfile: {},\n  businessInfo: {},\n  countries: [{\n    key: 'US',\n    label: 'United States'\n  }],\n  onboardingProfile: {\n    is_store_country_set: false,\n    industry: ['clothing_and_accessories'],\n    business_choice: 'im_just_starting_my_business'\n  }\n}} />",
      ...Basic.parameters?.docs?.source
    }
  }
};
try {
    // @ts-ignore
    BusinessInfo.displayName = "BusinessInfo";
    // @ts-ignore
    BusinessInfo.__docgenInfo = { "description": "", "displayName": "BusinessInfo", "props": { "context": { "defaultValue": null, "description": "", "name": "context", "required": true, "type": { "name": "BusinessInfoContextProps" } }, "navigationProgress": { "defaultValue": null, "description": "", "name": "navigationProgress", "required": true, "type": { "name": "number" } }, "sendEvent": { "defaultValue": null, "description": "", "name": "sendEvent", "required": true, "type": { "name": "(event: BusinessInfoEvent) => void" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../plugins/woocommerce/client/admin/client/core-profiler/stories/BusinessInfo.story.tsx#BusinessInfo"] = { docgenInfo: BusinessInfo.__docgenInfo, name: "BusinessInfo", path: "../../plugins/woocommerce/client/admin/client/core-profiler/stories/BusinessInfo.story.tsx#BusinessInfo" };
}
catch (__react_docgen_typescript_loader_error) { }

/***/ }),

/***/ "../../plugins/woocommerce/client/admin/client/core-profiler/components/geolocation-country-select/geolocation-country-select.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   p: () => (/* binding */ GeolocationCountrySelect)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+element@6.33.1/node_modules/@wordpress/element/build-module/create-interpolate-element.js");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@6.6.1/node_modules/@wordpress/i18n/build-module/index.js");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/notice/index.js");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/button/index.js");
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__("../../packages/js/components/src/select-control/index.tsx");
/* harmony import */ var _wordpress_icons__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/icon/index.js");
/* harmony import */ var _wordpress_icons__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/library/chevron-down.js");
/* harmony import */ var _woocommerce_onboarding__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../packages/js/onboarding/src/index.ts");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
/**
 * External dependencies
 */







/**
 * Internal dependencies
 */

const GeolocationCountrySelect = ({
  countries,
  geolocatedLocation,
  initialValue,
  label,
  placeholder,
  onChange,
  onGeolocationOverruledChange
}) => {
  const [selectedCountry, setSelectedCountry] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.useState)(initialValue);
  const [geolocationMatch, setGeolocationMatch] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.useState)({
    key: '',
    label: ''
  });
  const [dismissedNotice, setDismissedNotice] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.useState)(false);
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.useEffect)(() => {
    setSelectedCountry(initialValue);
  }, [initialValue]);
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.useEffect)(() => {
    if (geolocatedLocation) {
      const match = (0,_woocommerce_onboarding__WEBPACK_IMPORTED_MODULE_1__/* .findCountryOption */ .b$)(countries, geolocatedLocation);
      if (match) {
        setGeolocationMatch(match);
        if (!initialValue?.key) {
          setSelectedCountry(match);
          onChange(match);
        }
      }
    }
  }, [countries, geolocatedLocation, initialValue?.key]);
  const [geolocationOverruled, setGeolocationOverruled] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.useState)(false);
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.useEffect)(() => {
    const overruled = Boolean(geolocatedLocation && (0,_woocommerce_onboarding__WEBPACK_IMPORTED_MODULE_1__/* .getCountry */ .JJ)(selectedCountry?.key) !== (0,_woocommerce_onboarding__WEBPACK_IMPORTED_MODULE_1__/* .getCountry */ .JJ)(geolocationMatch?.key));
    setGeolocationOverruled(overruled);
    onGeolocationOverruledChange?.(overruled);
  }, [selectedCountry, geolocationMatch, geolocatedLocation]);
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsxs)("div", {
    className: "woocommerce-geolocation-country-select",
    children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)(_woocommerce_components__WEBPACK_IMPORTED_MODULE_4__/* ["default"] */ .A, {
      className: "woocommerce-profiler-select-control__country",
      instanceId: 2,
      placeholder: placeholder,
      label: selectedCountry.key === '' ? label : '',
      ignoreDiacritics: true,
      getSearchExpression: query => {
        return new RegExp(`(^${query}| — (${query}))`, 'i');
      },
      options: countries,
      help: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)(_wordpress_icons__WEBPACK_IMPORTED_MODULE_5__/* ["default"] */ .A, {
        icon: _wordpress_icons__WEBPACK_IMPORTED_MODULE_6__/* ["default"] */ .A
      }),
      onChange: results => {
        if (Array.isArray(results) && results.length) {
          onChange?.(results[0]);
        }
      },
      selected: selectedCountry ? [selectedCountry] : [],
      showAllOnFocus: true,
      isSearchable: true,
      virtualScroll: true,
      virtualItemHeight: 40,
      virtualListHeight: 40 * 9
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("div", {
      className: "woocommerce-profiler-select-control__country-spacer"
    }), geolocationOverruled && !dismissedNotice && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_7__/* ["default"] */ .A, {
      className: "woocommerce-profiler-geolocation-notice",
      onRemove: () => setDismissedNotice(true),
      status: "warning",
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("p", {
        children: (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_8__/* ["default"] */ .A)((0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('It looks like you’re located in <geolocatedCountry></geolocatedCountry>. Are you sure you want to create a store in <selectedCountry></selectedCountry>?', 'woocommerce'), {
          geolocatedCountry: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_9__/* ["default"] */ .Ay, {
            className: "geolocation-notice-geolocated-country",
            variant: "link",
            onClick: () => {
              setSelectedCountry(geolocationMatch);
              onChange(geolocationMatch);
            },
            children: geolocatedLocation?.country_long
          }),
          selectedCountry: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("span", {
            className: "geolocation-notice-selected-country",
            children: selectedCountry.label
          })
        })
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("p", {
        children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Setting up your store in the wrong country may lead to the following issues:', 'woocommerce')
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsxs)("ul", {
        className: "woocommerce-profiler-geolocation-notice__list",
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("li", {
          children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Tax and duty obligations', 'woocommerce')
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("li", {
          children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Payment issues', 'woocommerce')
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("li", {
          children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Shipping issues', 'woocommerce')
        })]
      })]
    })]
  });
};
try {
    // @ts-ignore
    GeolocationCountrySelect.displayName = "GeolocationCountrySelect";
    // @ts-ignore
    GeolocationCountrySelect.__docgenInfo = { "description": "", "displayName": "GeolocationCountrySelect", "props": { "countries": { "defaultValue": null, "description": "", "name": "countries", "required": true, "type": { "name": "CountryStateOption[]" } }, "geolocatedLocation": { "defaultValue": null, "description": "", "name": "geolocatedLocation", "required": false, "type": { "name": "any" } }, "initialValue": { "defaultValue": null, "description": "", "name": "initialValue", "required": true, "type": { "name": "CountryStateOption" } }, "label": { "defaultValue": null, "description": "", "name": "label", "required": true, "type": { "name": "string" } }, "placeholder": { "defaultValue": null, "description": "", "name": "placeholder", "required": true, "type": { "name": "string" } }, "onChange": { "defaultValue": null, "description": "", "name": "onChange", "required": true, "type": { "name": "(country: CountryStateOption) => void" } }, "onGeolocationOverruledChange": { "defaultValue": null, "description": "", "name": "onGeolocationOverruledChange", "required": false, "type": { "name": "((overruled: boolean) => void)" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../plugins/woocommerce/client/admin/client/core-profiler/components/geolocation-country-select/geolocation-country-select.tsx#GeolocationCountrySelect"] = { docgenInfo: GeolocationCountrySelect.__docgenInfo, name: "GeolocationCountrySelect", path: "../../plugins/woocommerce/client/admin/client/core-profiler/components/geolocation-country-select/geolocation-country-select.tsx#GeolocationCountrySelect" };
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

/***/ })

}]);