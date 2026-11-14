"use strict";
(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[1950],{

/***/ "../../plugins/woocommerce/client/admin/client/core-profiler/stories/BusinessLocation.story.tsx"
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

// ESM COMPAT FLAG
__webpack_require__.r(__webpack_exports__);

// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  Basic: () => (/* binding */ Basic),
  "default": () => (/* binding */ BusinessLocation_story)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+i18n@6.6.1/node_modules/@wordpress/i18n/build-module/index.js + 3 modules
var build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@6.6.1/node_modules/@wordpress/i18n/build-module/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._e2945962480e90bc326a2a227b7daaad/node_modules/@wordpress/components/build-module/notice/index.js + 4 modules
var notice = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._e2945962480e90bc326a2a227b7daaad/node_modules/@wordpress/components/build-module/notice/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._e2945962480e90bc326a2a227b7daaad/node_modules/@wordpress/components/build-module/button/index.js
var build_module_button = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._e2945962480e90bc326a2a227b7daaad/node_modules/@wordpress/components/build-module/button/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+element@6.33.1/node_modules/@wordpress/element/build-module/create-interpolate-element.js
var create_interpolate_element = __webpack_require__("../../node_modules/.pnpm/@wordpress+element@6.33.1/node_modules/@wordpress/element/build-module/create-interpolate-element.js");
// EXTERNAL MODULE: ../../plugins/woocommerce/client/admin/client/core-profiler/components/heading/heading.tsx
var heading = __webpack_require__("../../plugins/woocommerce/client/admin/client/core-profiler/components/heading/heading.tsx");
// EXTERNAL MODULE: ../../plugins/woocommerce/client/admin/client/core-profiler/components/navigation/navigation.tsx + 2 modules
var navigation = __webpack_require__("../../plugins/woocommerce/client/admin/client/core-profiler/components/navigation/navigation.tsx");
// EXTERNAL MODULE: ../../plugins/woocommerce/client/admin/client/core-profiler/components/geolocation-country-select/geolocation-country-select.tsx
var geolocation_country_select = __webpack_require__("../../plugins/woocommerce/client/admin/client/core-profiler/components/geolocation-country-select/geolocation-country-select.tsx");
// EXTERNAL MODULE: ../../plugins/woocommerce/client/admin/client/utils/admin-settings.js
var admin_settings = __webpack_require__("../../plugins/woocommerce/client/admin/client/utils/admin-settings.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js
var jsx_runtime = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
;// ../../plugins/woocommerce/client/admin/client/core-profiler/pages/BusinessLocation.tsx
/**
 * External dependencies
 */




/**
 * Internal dependencies
 */






const BusinessLocation_BusinessLocation = ({
  sendEvent,
  navigationProgress,
  context
}) => {
  const [storeCountry, setStoreCountry] = (0,react.useState)({
    key: '',
    label: ''
  });
  const inputLabel = (0,build_module.__)('Select country/region', 'woocommerce');
  // Only preloaded for users who can open Site Health.
  const {
    siteHealthUrl
  } = (0,admin_settings/* getAdminSetting */.Qk)('onboarding', {});
  return /*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
    className: "woocommerce-profiler-business-location",
    "data-testid": "core-profiler-business-location",
    children: [/*#__PURE__*/(0,jsx_runtime.jsx)(navigation/* Navigation */.V, {
      percentage: navigationProgress
    }), /*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
      className: "woocommerce-profiler-page__content woocommerce-profiler-business-location__content",
      children: [/*#__PURE__*/(0,jsx_runtime.jsx)(heading/* Heading */.D, {
        className: "woocommerce-profiler__stepper-heading",
        title: (0,build_module.__)('Where is your business located?', 'woocommerce'),
        subTitle: (0,build_module.__)('We’ll use this information to help you set up payments, shipping, and taxes.', 'woocommerce')
      }), /*#__PURE__*/(0,jsx_runtime.jsx)(geolocation_country_select/* GeolocationCountrySelect */.p, {
        countries: context.countries,
        initialValue: storeCountry,
        label: inputLabel,
        geolocatedLocation: context.geolocatedLocation,
        placeholder: inputLabel,
        onChange: countryStateOption => {
          setStoreCountry(countryStateOption);
        }
      }), context.countries.length === 0 && /*#__PURE__*/(0,jsx_runtime.jsxs)(notice/* default */.A, {
        className: "woocommerce-profiler-select-control__country-error",
        isDismissible: false,
        status: "error",
        children: [(0,create_interpolate_element/* default */.A)((0,build_module.__)('Oops! We encountered a problem while fetching the list of countries to choose from. <retryButton/> or <skipButton/>', 'woocommerce'), {
          retryButton: /*#__PURE__*/(0,jsx_runtime.jsx)(build_module_button/* default */.Ay, {
            onClick: () => {
              sendEvent({
                type: 'RETRY_COUNTRIES_LIST'
              });
            },
            variant: "tertiary",
            children: (0,build_module.__)('Please try again', 'woocommerce')
          }),
          skipButton: /*#__PURE__*/(0,jsx_runtime.jsx)(build_module_button/* default */.Ay, {
            onClick: () => {
              sendEvent({
                type: 'BUSINESS_LOCATION_COMPLETED',
                payload: {
                  storeLocation: 'US:CA'
                }
              });
            },
            variant: "tertiary",
            children: (0,build_module.__)('Skip this step', 'woocommerce')
          })
        }), /*#__PURE__*/(0,jsx_runtime.jsx)("p", {
          children: siteHealthUrl ? (0,create_interpolate_element/* default */.A)((0,build_module.__)('If this keeps happening, your site’s REST API may be blocked. <siteHealthLink>Check Site Health</siteHealthLink> for details.', 'woocommerce'), {
            siteHealthLink: /*#__PURE__*/(0,jsx_runtime.jsx)(build_module_button/* default */.Ay, {
              variant: "link",
              href: siteHealthUrl
            })
          }) : (0,build_module.__)('If this keeps happening, ask your hosting provider to check that your site’s REST API is working.', 'woocommerce')
        })]
      }), /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
        className: "woocommerce-profiler-button-container woocommerce-profiler-go-to-mystore__button-container",
        children: /*#__PURE__*/(0,jsx_runtime.jsx)(build_module_button/* default */.Ay, {
          className: "woocommerce-profiler-button",
          variant: "primary",
          disabled: !storeCountry.key,
          onClick: () => {
            sendEvent({
              type: 'BUSINESS_LOCATION_COMPLETED',
              payload: {
                storeLocation: storeCountry.key
              }
            });
          },
          children: (0,build_module.__)('Go to my store', 'woocommerce')
        })
      })]
    })]
  });
};
try {
    // @ts-ignore
    BusinessLocation_BusinessLocation.displayName = "BusinessLocation";
    // @ts-ignore
    BusinessLocation_BusinessLocation.__docgenInfo = { "description": "", "displayName": "BusinessLocation", "props": { "sendEvent": { "defaultValue": null, "description": "", "name": "sendEvent", "required": true, "type": { "name": "(event: BusinessLocationEvent) => void" } }, "navigationProgress": { "defaultValue": null, "description": "", "name": "navigationProgress", "required": true, "type": { "name": "number" } }, "context": { "defaultValue": null, "description": "", "name": "context", "required": true, "type": { "name": "Pick<CoreProfilerStateMachineContext, \"countries\" | \"geolocatedLocation\">" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../plugins/woocommerce/client/admin/client/core-profiler/pages/BusinessLocation.tsx#BusinessLocation"] = { docgenInfo: BusinessLocation_BusinessLocation.__docgenInfo, name: "BusinessLocation", path: "../../plugins/woocommerce/client/admin/client/core-profiler/pages/BusinessLocation.tsx#BusinessLocation" };
}
catch (__react_docgen_typescript_loader_error) { }
// EXTERNAL MODULE: ../../plugins/woocommerce/client/admin/client/core-profiler/stories/WithSetupWizardLayout.tsx
var WithSetupWizardLayout = __webpack_require__("../../plugins/woocommerce/client/admin/client/core-profiler/stories/WithSetupWizardLayout.tsx");
;// ../../plugins/woocommerce/client/admin/client/core-profiler/stories/BusinessLocation.story.tsx
/**
 * Internal dependencies
 */




const Basic = () => /*#__PURE__*/(0,jsx_runtime.jsx)(BusinessLocation_BusinessLocation, {
  sendEvent: () => {},
  navigationProgress: 80,
  context: {
    countries: [{
      key: 'US',
      label: 'United States'
    }],
    geolocatedLocation: undefined
  }
});
/* harmony default export */ const BusinessLocation_story = ({
  title: 'WooCommerce Admin/Core Profiler/Business Location',
  component: BusinessLocation_BusinessLocation,
  decorators: [WithSetupWizardLayout/* WithSetupWizardLayout */.b]
});
Basic.parameters = {
  ...Basic.parameters,
  docs: {
    ...Basic.parameters?.docs,
    source: {
      originalSource: "() => <BusinessLocation sendEvent={() => {}} navigationProgress={80} context={{\n  countries: [{\n    key: 'US',\n    label: 'United States'\n  }],\n  geolocatedLocation: undefined\n}} />",
      ...Basic.parameters?.docs?.source
    }
  }
};
try {
    // @ts-ignore
    BusinessLocation.displayName = "BusinessLocation";
    // @ts-ignore
    BusinessLocation.__docgenInfo = { "description": "", "displayName": "BusinessLocation", "props": { "sendEvent": { "defaultValue": null, "description": "", "name": "sendEvent", "required": true, "type": { "name": "(event: BusinessLocationEvent) => void" } }, "navigationProgress": { "defaultValue": null, "description": "", "name": "navigationProgress", "required": true, "type": { "name": "number" } }, "context": { "defaultValue": null, "description": "", "name": "context", "required": true, "type": { "name": "Pick<CoreProfilerStateMachineContext, \"countries\" | \"geolocatedLocation\">" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../plugins/woocommerce/client/admin/client/core-profiler/stories/BusinessLocation.story.tsx#BusinessLocation"] = { docgenInfo: BusinessLocation.__docgenInfo, name: "BusinessLocation", path: "../../plugins/woocommerce/client/admin/client/core-profiler/stories/BusinessLocation.story.tsx#BusinessLocation" };
}
catch (__react_docgen_typescript_loader_error) { }

/***/ },

/***/ "../../node_modules/.pnpm/@wordpress+i18n@6.6.1/node_modules/@wordpress/i18n/build-module/index.js"
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  __: () => (/* reexport */ __),
  _n: () => (/* reexport */ _n),
  _x: () => (/* reexport */ _x),
  V8: () => (/* reexport */ isRTL),
  nv: () => (/* reexport */ sprintf)
});

// UNUSED EXPORTS: _nx, createI18n, defaultI18n, getLocaleData, hasTranslation, resetLocaleData, setLocaleData, subscribe

// EXTERNAL MODULE: ../../node_modules/.pnpm/@tannin+sprintf@1.3.3/node_modules/@tannin/sprintf/src/index.js
var src = __webpack_require__("../../node_modules/.pnpm/@tannin+sprintf@1.3.3/node_modules/@tannin/sprintf/src/index.js");
;// ../../node_modules/.pnpm/@wordpress+i18n@6.6.1/node_modules/@wordpress/i18n/build-module/sprintf.js

function sprintf(format, ...args) {
  return (0,src/* default */.A)(format, ...args);
}

//# sourceMappingURL=sprintf.js.map

// EXTERNAL MODULE: ../../node_modules/.pnpm/tannin@1.2.0/node_modules/tannin/index.js + 4 modules
var node_modules_tannin = __webpack_require__("../../node_modules/.pnpm/tannin@1.2.0/node_modules/tannin/index.js");
;// ../../node_modules/.pnpm/@wordpress+i18n@6.6.1/node_modules/@wordpress/i18n/build-module/create-i18n.js

const DEFAULT_LOCALE_DATA = {
  "": {
    plural_forms(n) {
      return n === 1 ? 0 : 1;
    }
  }
};
const I18N_HOOK_REGEXP = /^i18n\.(n?gettext|has_translation)(_|$)/;
const createI18n = (initialData, initialDomain, hooks) => {
  const tannin = new node_modules_tannin/* default */.A({});
  const listeners = /* @__PURE__ */ new Set();
  const notifyListeners = () => {
    listeners.forEach((listener) => listener());
  };
  const subscribe = (callback) => {
    listeners.add(callback);
    return () => listeners.delete(callback);
  };
  const getLocaleData = (domain = "default") => tannin.data[domain];
  const doSetLocaleData = (data, domain = "default") => {
    tannin.data[domain] = {
      ...tannin.data[domain],
      ...data
    };
    tannin.data[domain][""] = {
      ...DEFAULT_LOCALE_DATA[""],
      ...tannin.data[domain]?.[""]
    };
    delete tannin.pluralForms[domain];
  };
  const setLocaleData = (data, domain) => {
    doSetLocaleData(data, domain);
    notifyListeners();
  };
  const addLocaleData = (data, domain = "default") => {
    tannin.data[domain] = {
      ...tannin.data[domain],
      ...data,
      // Populate default domain configuration (supported locale date which omits
      // a plural forms expression).
      "": {
        ...DEFAULT_LOCALE_DATA[""],
        ...tannin.data[domain]?.[""],
        ...data?.[""]
      }
    };
    delete tannin.pluralForms[domain];
    notifyListeners();
  };
  const resetLocaleData = (data, domain) => {
    tannin.data = {};
    tannin.pluralForms = {};
    setLocaleData(data, domain);
  };
  const dcnpgettext = (domain = "default", context, single, plural, number) => {
    if (!tannin.data[domain]) {
      doSetLocaleData(void 0, domain);
    }
    return tannin.dcnpgettext(domain, context, single, plural, number);
  };
  const getFilterDomain = (domain) => domain || "default";
  const __ = (text, domain) => {
    let translation = dcnpgettext(domain, void 0, text);
    if (!hooks) {
      return translation;
    }
    translation = hooks.applyFilters(
      "i18n.gettext",
      translation,
      text,
      domain
    );
    return hooks.applyFilters(
      "i18n.gettext_" + getFilterDomain(domain),
      translation,
      text,
      domain
    );
  };
  const _x = (text, context, domain) => {
    let translation = dcnpgettext(domain, context, text);
    if (!hooks) {
      return translation;
    }
    translation = hooks.applyFilters(
      "i18n.gettext_with_context",
      translation,
      text,
      context,
      domain
    );
    return hooks.applyFilters(
      "i18n.gettext_with_context_" + getFilterDomain(domain),
      translation,
      text,
      context,
      domain
    );
  };
  const _n = (single, plural, number, domain) => {
    let translation = dcnpgettext(
      domain,
      void 0,
      single,
      plural,
      number
    );
    if (!hooks) {
      return translation;
    }
    translation = hooks.applyFilters(
      "i18n.ngettext",
      translation,
      single,
      plural,
      number,
      domain
    );
    return hooks.applyFilters(
      "i18n.ngettext_" + getFilterDomain(domain),
      translation,
      single,
      plural,
      number,
      domain
    );
  };
  const _nx = (single, plural, number, context, domain) => {
    let translation = dcnpgettext(
      domain,
      context,
      single,
      plural,
      number
    );
    if (!hooks) {
      return translation;
    }
    translation = hooks.applyFilters(
      "i18n.ngettext_with_context",
      translation,
      single,
      plural,
      number,
      context,
      domain
    );
    return hooks.applyFilters(
      "i18n.ngettext_with_context_" + getFilterDomain(domain),
      translation,
      single,
      plural,
      number,
      context,
      domain
    );
  };
  const isRTL = () => {
    return "rtl" === _x("ltr", "text direction");
  };
  const hasTranslation = (single, context, domain) => {
    const key = context ? context + "" + single : single;
    let result = !!tannin.data?.[domain ?? "default"]?.[key];
    if (hooks) {
      result = hooks.applyFilters(
        "i18n.has_translation",
        result,
        single,
        context,
        domain
      );
      result = hooks.applyFilters(
        "i18n.has_translation_" + getFilterDomain(domain),
        result,
        single,
        context,
        domain
      );
    }
    return result;
  };
  if (initialData) {
    setLocaleData(initialData, initialDomain);
  }
  if (hooks) {
    const onHookAddedOrRemoved = (hookName) => {
      if (I18N_HOOK_REGEXP.test(hookName)) {
        notifyListeners();
      }
    };
    hooks.addAction("hookAdded", "core/i18n", onHookAddedOrRemoved);
    hooks.addAction("hookRemoved", "core/i18n", onHookAddedOrRemoved);
  }
  return {
    getLocaleData,
    setLocaleData,
    addLocaleData,
    resetLocaleData,
    subscribe,
    __,
    _x,
    _n,
    _nx,
    isRTL,
    hasTranslation
  };
};

//# sourceMappingURL=create-i18n.js.map

// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+hooks@4.57.0/node_modules/@wordpress/hooks/build-module/index.mjs + 10 modules
var build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+hooks@4.57.0/node_modules/@wordpress/hooks/build-module/index.mjs");
;// ../../node_modules/.pnpm/@wordpress+i18n@6.6.1/node_modules/@wordpress/i18n/build-module/default-i18n.js


const i18n = createI18n(void 0, void 0, build_module/* defaultHooks */.se);
var default_i18n_default = (/* unused pure expression or super */ null && (i18n));
const getLocaleData = i18n.getLocaleData.bind(i18n);
const setLocaleData = i18n.setLocaleData.bind(i18n);
const resetLocaleData = i18n.resetLocaleData.bind(i18n);
const subscribe = i18n.subscribe.bind(i18n);
const __ = i18n.__.bind(i18n);
const _x = i18n._x.bind(i18n);
const _n = i18n._n.bind(i18n);
const _nx = i18n._nx.bind(i18n);
const isRTL = i18n.isRTL.bind(i18n);
const hasTranslation = i18n.hasTranslation.bind(i18n);

//# sourceMappingURL=default-i18n.js.map

;// ../../node_modules/.pnpm/@wordpress+i18n@6.6.1/node_modules/@wordpress/i18n/build-module/index.js




//# sourceMappingURL=index.js.map


/***/ },

/***/ "../../plugins/woocommerce/client/admin/client/utils/admin-settings.js"
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   Qk: () => (/* binding */ getAdminSetting)
/* harmony export */ });
/* unused harmony exports deprecatedAdminProperties, ADMIN_URL, COUNTRIES, CURRENCY, LOCALE, SITE_TITLE, WC_ASSET_URL, ORDER_STATUSES, PAYMENT_GATEWAYS, setAdminSetting */
/* unused harmony import specifier */ var __;
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@6.6.1/node_modules/@wordpress/i18n/build-module/index.js");
/* harmony import */ var _woocommerce_settings__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("./setting.mock.js");
/**
 * External dependencies
 */



/**
 * Internal dependencies
 */


// Remove mutable data from settings object to prevent access. Data stores should be used instead.
const mutableSources = ['wcAdminSettings', 'preloadSettings'];
const adminSettings = (0,_woocommerce_settings__WEBPACK_IMPORTED_MODULE_1__/* .getSetting */ .P)('admin', {});
const ADMIN_SETTINGS_SOURCE = Object.keys(adminSettings).reduce((source, key) => {
  if (!mutableSources.includes(key)) {
    source[key] = adminSettings[key];
  }
  return source;
}, {});
const deprecatedAdminProperties = {
  onboarding: {
    profile: 'Deprecated: wcSettings.admin.onboarding.profile is deprecated. It is planned to be released in WooCommerce 10.0.0. Please use `getProfileItems` from the onboarding store. See https://github.com/woocommerce/woocommerce/tree/trunk/packages/js/data/src/onboarding for more information.',
    euCountries: 'Deprecated: wcSettings.admin.onboarding.euCountries is deprecated. Please use `/wc/v3/data/continents/eu` from the REST API. See https://developer.woocommerce.com/docs/apis/rest-api/v3/data/#list-all-continents for more information.',
    localInfo: 'Deprecated: wcSettings.admin.onboarding.localInfo is deprecated. Please use `include WC()->plugin_path() . "/i18n/locale-info.php"` instead.',
    currencySymbols: '"Deprecated: wcSettings.admin.onboarding.currencySymbols is deprecated. Please use get_woocommerce_currency_symbols() function instead.'
  }
};

/**
 * Retrieves a setting value from the setting state.
 *
 * @param {string}   name                    The identifier for the setting.
 * @param {*}        [fallback=false]        The value to use as a fallback
 *                                           if the setting is not in the
 *                                           state.
 * @param {Function} [filter=( val ) => val] A callback for filtering the
 *                                           value before it's returned.
 *                                           Receives both the found value
 *                                           (if it exists for the key) and
 *                                           the provided fallback arg.
 *
 * @return {*}  The value present in the settings state for the given
 *                   name.
 */
function getAdminSetting(name, fallback = false, filter = val => val, deprecatedProperties = deprecatedAdminProperties) {
  if (mutableSources.includes(name)) {
    throw new Error((0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Mutable settings should be accessed via data store.', 'woocommerce'));
  }
  const value = ADMIN_SETTINGS_SOURCE.hasOwnProperty(name) ? ADMIN_SETTINGS_SOURCE[name] : fallback;
  const filtered = filter(value, fallback);

  // Return proxied object if the requested object has deprecated properties.
  return deprecatedProperties?.[name] && "production" === 'development' ? 0 : filtered;
}
const ADMIN_URL = (0,_woocommerce_settings__WEBPACK_IMPORTED_MODULE_1__/* .getSetting */ .P)('adminUrl');
const COUNTRIES = (0,_woocommerce_settings__WEBPACK_IMPORTED_MODULE_1__/* .getSetting */ .P)('countries');
const CURRENCY = (0,_woocommerce_settings__WEBPACK_IMPORTED_MODULE_1__/* .getSetting */ .P)('currency');
const LOCALE = (0,_woocommerce_settings__WEBPACK_IMPORTED_MODULE_1__/* .getSetting */ .P)('locale');
const SITE_TITLE = (0,_woocommerce_settings__WEBPACK_IMPORTED_MODULE_1__/* .getSetting */ .P)('siteTitle');
const WC_ASSET_URL = (0,_woocommerce_settings__WEBPACK_IMPORTED_MODULE_1__/* .getSetting */ .P)('wcAssetUrl');
const ORDER_STATUSES = getAdminSetting('orderStatuses');
const PAYMENT_GATEWAYS = getAdminSetting('paymentGateways', {});

/**
 * Sets a value to a property on the settings state.
 *
 * NOTE: This feature is to be removed in favour of data stores when a full migration
 * is complete.
 *
 * @deprecated
 *
 * @param {string}   name                    The setting property key for the
 *                                           setting being mutated.
 * @param {*}        value                   The value to set.
 * @param {Function} [filter=( val ) => val] Allows for providing a callback
 *                                           to sanitize the setting (eg.
 *                                           ensure it's a number)
 */
function setAdminSetting(name, value, filter = val => val) {
  if (mutableSources.includes(name)) {
    throw new Error(__('Mutable settings should be mutated via data store.', 'woocommerce'));
  }
  ADMIN_SETTINGS_SOURCE[name] = filter(value);
}

/***/ },

/***/ "./setting.mock.js"
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   P: () => (/* binding */ getSetting)
/* harmony export */ });
// @woocommerce/settings mocked module for storybook webpack resolve.alias config
// see ./webpack.config.js

function getSetting() {
  return {};
}

/***/ },

/***/ "../../plugins/woocommerce/client/admin/client/core-profiler/components/geolocation-country-select/geolocation-country-select.tsx"
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+element@6.33.1/node_modules/@wordpress/element/build-module/create-interpolate-element.js");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@6.6.1/node_modules/@wordpress/i18n/build-module/index.js");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._e2945962480e90bc326a2a227b7daaad/node_modules/@wordpress/components/build-module/button/index.js");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._e2945962480e90bc326a2a227b7daaad/node_modules/@wordpress/components/build-module/notice/index.js");
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__("../../packages/js/components/src/select-control/index.tsx");
/* harmony import */ var _wordpress_icons__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@11.0.1_@ty_5f53abb8ba70778c64ea7e8549cea5d4/node_modules/@wordpress/icons/build-module/icon/index.js");
/* harmony import */ var _wordpress_icons__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@11.0.1_@ty_5f53abb8ba70778c64ea7e8549cea5d4/node_modules/@wordpress/icons/build-module/library/chevron-down.js");
/* harmony import */ var _woocommerce_onboarding__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__("../../packages/js/onboarding/src/index.ts");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
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
  const [selectedCountry, setSelectedCountry] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)(initialValue);
  const [geolocationMatch, setGeolocationMatch] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)({
    key: '',
    label: ''
  });
  const [dismissedNotice, setDismissedNotice] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)(false);
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    setSelectedCountry(initialValue);
  }, [initialValue]);
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    if (geolocatedLocation) {
      const match = (0,_woocommerce_onboarding__WEBPACK_IMPORTED_MODULE_8__/* .findCountryOption */ .b$)(countries, geolocatedLocation);
      if (match) {
        setGeolocationMatch(match);
        if (!initialValue?.key) {
          setSelectedCountry(match);
          onChange(match);
        }
      }
    }
  }, [countries, geolocatedLocation, initialValue?.key]);
  const [geolocationOverruled, setGeolocationOverruled] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)(false);
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    const overruled = Boolean(geolocatedLocation && (0,_woocommerce_onboarding__WEBPACK_IMPORTED_MODULE_8__/* .getCountry */ .JJ)(selectedCountry?.key) !== (0,_woocommerce_onboarding__WEBPACK_IMPORTED_MODULE_8__/* .getCountry */ .JJ)(geolocationMatch?.key));
    setGeolocationOverruled(overruled);
    onGeolocationOverruledChange?.(overruled);
  }, [selectedCountry, geolocationMatch, geolocatedLocation]);
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_9__.jsxs)("div", {
    className: "woocommerce-geolocation-country-select",
    children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_9__.jsx)(_woocommerce_components__WEBPACK_IMPORTED_MODULE_5__/* ["default"] */ .A, {
      className: "woocommerce-profiler-select-control__country",
      instanceId: 2,
      placeholder: placeholder,
      label: selectedCountry.key === '' ? label : '',
      ignoreDiacritics: true,
      getSearchExpression: query => {
        return new RegExp(`(^${query}| — (${query}))`, 'i');
      },
      options: countries,
      help: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_9__.jsx)(_wordpress_icons__WEBPACK_IMPORTED_MODULE_6__/* ["default"] */ .A, {
        icon: _wordpress_icons__WEBPACK_IMPORTED_MODULE_7__/* ["default"] */ .A
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
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_9__.jsx)("div", {
      className: "woocommerce-profiler-select-control__country-spacer"
    }), geolocationOverruled && !dismissedNotice && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_9__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__/* ["default"] */ .A, {
      className: "woocommerce-profiler-geolocation-notice",
      onRemove: () => setDismissedNotice(true),
      status: "warning",
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_9__.jsx)("p", {
        children: (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__/* ["default"] */ .A)((0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('It looks like you’re located in <geolocatedCountry></geolocatedCountry>. Are you sure you want to create a store in <selectedCountry></selectedCountry>?', 'woocommerce'), {
          geolocatedCountry: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_9__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__/* ["default"] */ .Ay, {
            className: "geolocation-notice-geolocated-country",
            variant: "link",
            onClick: () => {
              setSelectedCountry(geolocationMatch);
              onChange(geolocationMatch);
            },
            children: geolocatedLocation?.country_long
          }),
          selectedCountry: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_9__.jsx)("span", {
            className: "geolocation-notice-selected-country",
            children: selectedCountry.label
          })
        })
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_9__.jsx)("p", {
        children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Setting up your store in the wrong country may lead to the following issues:', 'woocommerce')
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_9__.jsxs)("ul", {
        className: "woocommerce-profiler-geolocation-notice__list",
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_9__.jsx)("li", {
          children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Tax and duty obligations', 'woocommerce')
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_9__.jsx)("li", {
          children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Payment issues', 'woocommerce')
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_9__.jsx)("li", {
          children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Shipping issues', 'woocommerce')
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
/* harmony export */ __webpack_require__.d(__webpack_exports__, [
/* harmony export */   "p", 0, /* binding */ GeolocationCountrySelect
/* harmony export */ ]);


/***/ },

/***/ "../../plugins/woocommerce/client/admin/client/core-profiler/components/heading/heading.tsx"
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

/* harmony import */ var clsx__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
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
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsxs)("div", {
    className: (0,clsx__WEBPACK_IMPORTED_MODULE_0__/* ["default"] */ .A)('woocommerce-profiler-heading', className),
    children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("h1", {
      className: "woocommerce-profiler-heading__title",
      children: title
    }), subTitle && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("h2", {
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
/* harmony export */ __webpack_require__.d(__webpack_exports__, [
/* harmony export */   "D", 0, /* binding */ Heading
/* harmony export */ ]);


/***/ },

/***/ "../../plugins/woocommerce/client/admin/client/core-profiler/components/navigation/navigation.tsx"
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  V: () => (/* binding */ Navigation)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+i18n@6.6.1/node_modules/@wordpress/i18n/build-module/index.js + 3 modules
var build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@6.6.1/node_modules/@wordpress/i18n/build-module/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs
var clsx = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._e2945962480e90bc326a2a227b7daaad/node_modules/@wordpress/components/build-module/button/index.js
var build_module_button = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._e2945962480e90bc326a2a227b7daaad/node_modules/@wordpress/components/build-module/button/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js
var jsx_runtime = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
;// ../../plugins/woocommerce/client/admin/client/core-profiler/components/navigation/woologo.tsx

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

/***/ }

}]);