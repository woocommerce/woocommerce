(window["__wcAdmin_webpackJsonp"] = window["__wcAdmin_webpackJsonp"] || []).push([[48],{

/***/ 306:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(9);
/* harmony import */ var _wordpress_primitives__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__);


/**
 * WordPress dependencies
 */

const chevronUp = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__["SVG"], {
  viewBox: "0 0 24 24",
  xmlns: "http://www.w3.org/2000/svg"
}, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__["Path"], {
  d: "M6.5 12.4L12 8l5.5 4.4-.9 1.2L12 10l-4.5 3.6-1-1.2z"
}));
/* harmony default export */ __webpack_exports__["a"] = (chevronUp);
//# sourceMappingURL=chevron-up.js.map

/***/ }),

/***/ 307:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(9);
/* harmony import */ var _wordpress_primitives__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__);


/**
 * WordPress dependencies
 */

const chevronDown = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__["SVG"], {
  viewBox: "0 0 24 24",
  xmlns: "http://www.w3.org/2000/svg"
}, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__["Path"], {
  d: "M17.5 11.6L12 16l-5.5-4.4.9-1.2L12 14l4.5-3.6 1 1.2z"
}));
/* harmony default export */ __webpack_exports__["a"] = (chevronDown);
//# sourceMappingURL=chevron-down.js.map

/***/ }),

/***/ 506:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(9);
/* harmony import */ var _wordpress_primitives__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__);


/**
 * WordPress dependencies
 */

const info = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__["SVG"], {
  xmlns: "http://www.w3.org/2000/svg",
  viewBox: "0 0 24 24"
}, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__["Path"], {
  d: "M12 3.2c-4.8 0-8.8 3.9-8.8 8.8 0 4.8 3.9 8.8 8.8 8.8 4.8 0 8.8-3.9 8.8-8.8 0-4.8-4-8.8-8.8-8.8zm0 16c-4 0-7.2-3.3-7.2-7.2C4.8 8 8 4.8 12 4.8s7.2 3.3 7.2 7.2c0 4-3.2 7.2-7.2 7.2zM11 17h2v-6h-2v6zm0-8h2V7h-2v2z"
}));
/* harmony default export */ __webpack_exports__["a"] = (info);
//# sourceMappingURL=info.js.map

/***/ }),

/***/ 536:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "b", function() { return getFilteredCurrencyInstance; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "a", function() { return CurrencyContext; });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_hooks__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(27);
/* harmony import */ var _wordpress_hooks__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_hooks__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _woocommerce_currency__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(101);
/* harmony import */ var _woocommerce_currency__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_currency__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _utils_admin_settings__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(23);
/**
 * External dependencies
 */



/**
 * Internal dependencies
 */


const appCurrency = _woocommerce_currency__WEBPACK_IMPORTED_MODULE_2___default()(_utils_admin_settings__WEBPACK_IMPORTED_MODULE_3__[/* CURRENCY */ "a"]);
const getFilteredCurrencyInstance = query => {
  const config = appCurrency.getCurrencyConfig();
  /**
   * Filter the currency context. This affects all WooCommerce Admin currency formatting.
   *
   * @filter woocommerce_admin_report_currency
   * @param {Object} config Currency configuration.
   * @param {Object} query Url query parameters.
   */

  const filteredConfig = Object(_wordpress_hooks__WEBPACK_IMPORTED_MODULE_1__["applyFilters"])('woocommerce_admin_report_currency', config, query);
  return _woocommerce_currency__WEBPACK_IMPORTED_MODULE_2___default()(filteredConfig);
};
const CurrencyContext = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createContext"])(appCurrency // default value
);

/***/ }),

/***/ 541:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "a", function() { return createNoticesFromResponse; });
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(8);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_0__);
/**
 * External dependencies
 */

function createNoticesFromResponse(response) {
  const {
    createNotice
  } = Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_0__["dispatch"])('core/notices');

  if (response.error_data && response.errors && Object.keys(response.errors).length) {
    // Loop over multi-error responses.
    Object.keys(response.errors).forEach(errorKey => {
      createNotice('error', response.errors[errorKey].join(' '));
    });
  } else if (response.message) {
    // Handle generic messages.
    createNotice(response.code ? 'error' : 'success', response.message);
  }
}

/***/ }),

/***/ 546:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* unused harmony export ALLOWED_TAGS */
/* unused harmony export ALLOWED_ATTR */
/* harmony import */ var dompurify__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(53);
/* harmony import */ var dompurify__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(dompurify__WEBPACK_IMPORTED_MODULE_0__);
/**
 * External dependencies
 */

const ALLOWED_TAGS = ['a', 'b', 'em', 'i', 'strong', 'p', 'br'];
const ALLOWED_ATTR = ['target', 'href', 'rel', 'name', 'download'];
/* harmony default export */ __webpack_exports__["a"] = (html => {
  return {
    __html: Object(dompurify__WEBPACK_IMPORTED_MODULE_0__["sanitize"])(html, {
      ALLOWED_TAGS,
      ALLOWED_ATTR
    })
  };
});

/***/ }),

/***/ 547:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";

// EXPORTS
__webpack_require__.d(__webpack_exports__, "a", function() { return /* reexport */ getPluginSlug; });
__webpack_require__.d(__webpack_exports__, "b", function() { return /* reexport */ getPluginTrackKey; });
__webpack_require__.d(__webpack_exports__, "d", function() { return /* binding */ getUrlParams; });
__webpack_require__.d(__webpack_exports__, "c", function() { return /* binding */ getScreenName; });

// UNUSED EXPORTS: sift

// CONCATENATED MODULE: ./client/utils/plugins.ts
function getPluginSlug(id) {
  return (id || '').split(':', 1)[0];
}
function getPluginTrackKey(id) {
  const slug = getPluginSlug(id);
  const key = /^woocommerce(-|_)payments$/.test(slug) ? 'wcpay' : `${slug.replace(/-/g, '_')}`.split(':', 1)[0];
  return key;
}
// CONCATENATED MODULE: ./client/utils/index.js

/**
 * Get the URL params.
 *
 * @param {string} locationSearch - Querystring part of a URL, including the question mark (?).
 * @return {Object} - URL params.
 */

function getUrlParams(locationSearch) {
  if (locationSearch) {
    return locationSearch.substr(1).split('&').reduce((params, query) => {
      const chunks = query.split('=');
      const key = chunks[0];
      let value = decodeURIComponent(chunks[1]);
      value = isNaN(Number(value)) ? value : Number(value);
      return params[key] = value, params;
    }, {});
  }

  return {};
}
/**
 * Get the current screen name.
 *
 * @return {string} - Screen name.
 */

function getScreenName() {
  let screenName = '';
  const {
    page,
    path,
    post_type: postType
  } = getUrlParams(window.location.search);

  if (page) {
    const currentPage = page === 'wc-admin' ? 'home_screen' : page;
    screenName = path ? path.replace(/\//g, '_').substring(1) : currentPage;
  } else if (postType) {
    screenName = postType;
  }

  return screenName;
}
/**
 * Similar to filter, but return two arrays separated by a partitioner function
 *
 * @param {Array} arr - Original array of values.
 * @param {Function} partitioner - Function to return truthy/falsy values to separate items in array.
 *
 * @return {Array} - Array of two arrays, first including truthy values, and second including falsy.
 */

const sift = (arr, partitioner) => arr.reduce((all, curr) => {
  all[!!partitioner(curr) ? 0 : 1].push(curr);
  return all;
}, [[], []]);

/***/ }),

/***/ 549:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(2);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(14);
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_compose__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(8);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _automattic_interpolate_components__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(79);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(4);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(22);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_components__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var _woocommerce_data__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(12);
/* harmony import */ var _woocommerce_data__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_data__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var _woocommerce_explat__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(137);
/* harmony import */ var _woocommerce_explat__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_explat__WEBPACK_IMPORTED_MODULE_8__);


/**
 * External dependencies
 */










class UsageModal extends _wordpress_element__WEBPACK_IMPORTED_MODULE_0__["Component"] {
  constructor(props) {
    super(props);
    this.state = {
      isLoadingScripts: false,
      isRequestStarted: false
    };
  }

  async componentDidUpdate(prevProps, prevState) {
    const {
      hasErrors,
      isRequesting,
      onClose,
      onContinue,
      createNotice
    } = this.props;
    const {
      isLoadingScripts,
      isRequestStarted
    } = this.state; // We can't rely on isRequesting props only because option update might be triggered by other component.

    if (!isRequestStarted) {
      return;
    }

    const isRequestSuccessful = !isRequesting && !isLoadingScripts && (prevProps.isRequesting || prevState.isLoadingScripts) && !hasErrors;
    const isRequestError = !isRequesting && prevProps.isRequesting && hasErrors;

    if (isRequestSuccessful) {
      onClose();
      onContinue();
    }

    if (isRequestError) {
      createNotice('error', Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('There was a problem updating your preferences', 'woocommerce-admin'));
      onClose();
    }
  }

  updateTracking(_ref) {
    let {
      allowTracking
    } = _ref;
    const {
      updateOptions
    } = this.props;

    if (allowTracking && typeof window.wcTracks.enable === 'function') {
      this.setState({
        isLoadingScripts: true
      });
      window.wcTracks.enable(() => {
        // Don't update state if component is unmounted already
        if (!this._isMounted) {
          return;
        }

        Object(_woocommerce_explat__WEBPACK_IMPORTED_MODULE_8__["initializeExPlat"])();
        this.setState({
          isLoadingScripts: false
        });
      });
    } else if (!allowTracking) {
      window.wcTracks.isEnabled = false;
    }

    const trackingValue = allowTracking ? 'yes' : 'no';
    this.setState({
      isRequestStarted: true
    });
    updateOptions({
      woocommerce_allow_tracking: trackingValue
    });
  }

  componentDidMount() {
    this._isMounted = true;
  }

  componentWillUnmount() {
    this._isMounted = false;
  }

  render() {
    const {
      allowTracking,
      isResolving,
      onClose,
      onContinue
    } = this.props;

    if (isResolving) {
      return null;
    } // Bail if site has already opted in to tracking


    if (allowTracking) {
      onClose();
      onContinue();
      return null;
    }

    const {
      isRequesting,
      title = Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Build a better WooCommerce', 'woocommerce-admin'),
      message = Object(_automattic_interpolate_components__WEBPACK_IMPORTED_MODULE_4__[/* default */ "a"])({
        mixedString: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Get improved features and faster fixes by sharing non-sensitive data via {{link}}usage tracking{{/link}} ' + 'that shows us how WooCommerce is used. No personal data is tracked or stored.', 'woocommerce-admin'),
        components: {
          link: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_6__["Link"], {
            href: "https://woocommerce.com/usage-tracking?utm_medium=product",
            target: "_blank",
            type: "external"
          })
        }
      }),
      dismissActionText = Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('No thanks', 'woocommerce-admin'),
      acceptActionText = Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Yes, count me in!', 'woocommerce-admin')
    } = this.props;
    const {
      isRequestStarted
    } = this.state;
    const isBusy = isRequestStarted && isRequesting;
    return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_5__["Modal"], {
      title: title,
      isDismissible: this.props.isDismissible,
      onRequestClose: () => this.props.onClose(),
      className: "woocommerce-usage-modal"
    }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("div", {
      className: "woocommerce-usage-modal__wrapper"
    }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("div", {
      className: "woocommerce-usage-modal__message"
    }, message), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("div", {
      className: "woocommerce-usage-modal__actions"
    }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_5__["Button"], {
      isSecondary: true,
      isBusy: isBusy,
      onClick: () => this.updateTracking({
        allowTracking: false
      })
    }, dismissActionText), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_5__["Button"], {
      isPrimary: true,
      isBusy: isBusy,
      onClick: () => this.updateTracking({
        allowTracking: true
      })
    }, acceptActionText))));
  }

}

/* harmony default export */ __webpack_exports__["a"] = (Object(_wordpress_compose__WEBPACK_IMPORTED_MODULE_2__["compose"])(Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_3__["withSelect"])(select => {
  const {
    getOption,
    getOptionsUpdatingError,
    isOptionsUpdating,
    hasFinishedResolution
  } = select(_woocommerce_data__WEBPACK_IMPORTED_MODULE_7__["OPTIONS_STORE_NAME"]);
  return {
    allowTracking: getOption('woocommerce_allow_tracking') === 'yes',
    isRequesting: Boolean(isOptionsUpdating()),
    isResolving: !hasFinishedResolution('getOption', ['woocommerce_allow_tracking']) || typeof getOption('woocommerce_allow_tracking') === 'undefined',
    hasErrors: Boolean(getOptionsUpdatingError())
  };
}), Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_3__["withDispatch"])(dispatch => {
  const {
    createNotice
  } = dispatch('core/notices');
  const {
    updateOptions
  } = dispatch(_woocommerce_data__WEBPACK_IMPORTED_MODULE_7__["OPTIONS_STORE_NAME"]);
  return {
    createNotice,
    updateOptions
  };
}))(UsageModal));

/***/ }),

/***/ 561:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* unused harmony export isAddressFieldRequired */
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "b", function() { return getStoreAddressValidator; });
/* unused harmony export getCountryStateOptions */
/* unused harmony export normalizeState */
/* unused harmony export getStateFilter */
/* unused harmony export useGetCountryStateAutofill */
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "a", function() { return StoreAddress; });
/* harmony import */ var _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(40);
/* harmony import */ var _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(2);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _woocommerce_data__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(12);
/* harmony import */ var _woocommerce_data__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_data__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _wordpress_html_entities__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(34);
/* harmony import */ var _wordpress_html_entities__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_wordpress_html_entities__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(5);
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(22);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_components__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(4);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(8);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_8__);



/**
 * External dependencies
 */








/**
 * Internal dependencies
 */

const storeAddressFields = ['addressLine1', 'addressLine2', 'city', 'countryState', 'postCode'];

/**
 * Check if a given address field is required for the locale.
 *
 * @param {string} fieldName Name of the field to check.
 * @param {Object} locale Locale data.
 * @return {boolean} Field requirement.
 */
function isAddressFieldRequired(fieldName) {
  var _locale$fieldName;

  let locale = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};

  if ((_locale$fieldName = locale[fieldName]) !== null && _locale$fieldName !== void 0 && _locale$fieldName.hasOwnProperty('required')) {
    var _locale$fieldName2;

    return (_locale$fieldName2 = locale[fieldName]) === null || _locale$fieldName2 === void 0 ? void 0 : _locale$fieldName2.required;
  }

  if (fieldName === 'address_2') {
    return false;
  }

  return true;
}
/**
 * Form validation.
 *
 * @param {Object} locale The store locale.
 * @return {Function} Validator function.
 */

function getStoreAddressValidator() {
  let locale = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};

  /**
   * Form validator.
   *
   * @param {Object} values Keyed values of all fields in the form.
   * @return {Object} Key value of fields and error messages, { myField: 'This field is required' }
   */
  return values => {
    const errors = {};

    if (isAddressFieldRequired('address_1', locale) && !values.addressLine1.trim().length) {
      errors.addressLine1 = Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__["__"])('Please add an address', 'woocommerce-admin');
    }

    if (!values.countryState.trim().length) {
      errors.countryState = Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__["__"])('Please select a country / region', 'woocommerce-admin');
    }

    if (isAddressFieldRequired('city', locale) && !values.city.trim().length) {
      errors.city = Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__["__"])('Please add a city', 'woocommerce-admin');
    }

    if (isAddressFieldRequired('postcode', locale) && !values.postCode.trim().length) {
      errors.postCode = Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__["__"])('Please add a post code', 'woocommerce-admin');
    }

    return errors;
  };
}
/**
 * Get all country and state combinations used for select dropdowns.
 *
 * @return {Object} Select options, { value: 'US:GA', label: 'United States - Georgia' }
 */

function getCountryStateOptions(countries) {
  const countryStateOptions = countries.reduce((acc, country) => {
    if (!country.states.length) {
      acc.push({
        key: country.code,
        label: Object(_wordpress_html_entities__WEBPACK_IMPORTED_MODULE_4__["decodeEntities"])(country.name)
      });
      return acc;
    }

    const countryStates = country.states.map(state => {
      return {
        key: country.code + ':' + state.code,
        label: Object(_wordpress_html_entities__WEBPACK_IMPORTED_MODULE_4__["decodeEntities"])(country.name) + ' — ' + Object(_wordpress_html_entities__WEBPACK_IMPORTED_MODULE_4__["decodeEntities"])(state.name)
      };
    });
    acc.push(...countryStates);
    return acc;
  }, []);
  return countryStateOptions;
}
/**
 * Normalize state string for matching.
 *
 * @param {string} state The state to normalize.
 * @return {Function} filter function.
 */

const normalizeState = state => {
  return state.replace(/\s/g, '').toLowerCase();
};
/**
 * Get state filter
 *
 * @param {string} isStateAbbreviation Whether to use state abbreviation or not.
 * @param {string} normalizedAutofillState The value of the autofillState field.
 * @return {Function} filter function.
 */

const getStateFilter = (isStateAbbreviation, normalizedAutofillState) => option => {
  const countryStateArray = isStateAbbreviation ? option.key.split(':') : option.label.split('—'); // No region options in the country

  if (countryStateArray.length <= 1) {
    return false;
  }

  const state = countryStateArray[1]; // Handle special case, for example: China — Beijing / 北京

  if (state.includes('/')) {
    const stateStrList = state.split('/');
    return normalizeState(stateStrList[0]) === normalizedAutofillState || normalizeState(stateStrList[1]) === normalizedAutofillState;
  } // Handle special case, for example: Iran — Alborz (البرز)


  if (state.includes('(') && state.includes(')')) {
    const stateStrList = state.replace(')', '').split('(');
    return normalizeState(stateStrList[0]) === normalizedAutofillState || normalizeState(stateStrList[1]) === normalizedAutofillState;
  }

  return normalizeState(state) === normalizedAutofillState;
};
/**
 * Get the autofill countryState fields and set value from filtered options.
 *
 * @param {Array} options Array of filterable options.
 * @param {string} countryState The value of the countryState field.
 * @param {Function} setValue Set value of the countryState input.
 * @return {Object} React component.
 */

function useGetCountryStateAutofill(options, countryState, setValue) {
  const [autofillCountry, setAutofillCountry] = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["useState"])('');
  const [autofillState, setAutofillState] = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["useState"])('');
  const isAutofillChange = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["useRef"])(); // Sync the autofill fields on first render and the countryState value changes.

  Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["useEffect"])(() => {
    if (!isAutofillChange.current) {
      const option = options.find(opt => opt.key === countryState);
      const labels = option ? option.label.split(/\u2013|\u2014|\-/) : [];
      const newCountry = (labels[0] || '').trim();
      const newState = (labels[1] || '').trim();

      if (newCountry !== autofillCountry || newState !== autofillState) {
        setAutofillCountry(newCountry);
        setAutofillState(newState);
      }
    }

    isAutofillChange.current = false; // Disable reason: If we include autofillCountry/autofillState in the dependency array, we will have an unnecessary function call because we also update them in this function.
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [countryState, options]); // Sync the countryState value the autofill fields changes

  Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["useEffect"])(() => {
    // Skip on first render since we only want to update the value when the autofill fields changes.
    if (isAutofillChange.current === undefined) {
      return;
    }

    if (!autofillCountry && !autofillState && countryState) {
      // Clear form
      isAutofillChange.current = true;
      setValue('countryState', '');
      return;
    }

    const countrySearch = new RegExp(Object(lodash__WEBPACK_IMPORTED_MODULE_5__["escapeRegExp"])(autofillCountry), 'i');
    const isCountryAbbreviation = autofillCountry.length < 3;
    const isStateAbbreviation = autofillState.length < 3 && !!autofillState.match(/^[\w]+$/);
    let filteredOptions = [];

    if (autofillCountry.length && autofillState.length) {
      filteredOptions = options.filter(option => countrySearch.test(isCountryAbbreviation ? option.key : option.label)); // no country matches so use all options for state filter.

      if (!filteredOptions.length) {
        filteredOptions = [...options];
      }

      if (filteredOptions.length > 1) {
        filteredOptions = filteredOptions.filter(getStateFilter(isStateAbbreviation, normalizeState(autofillState)));
      }
    } else if (autofillCountry.length) {
      filteredOptions = options.filter(option => countrySearch.test(isCountryAbbreviation ? option.key : option.label));
    } else if (autofillState.length) {
      filteredOptions = options.filter(getStateFilter(isStateAbbreviation, normalizeState(autofillState)));
    }

    if (filteredOptions.length === 1 && countryState !== filteredOptions[0].key) {
      isAutofillChange.current = true;
      setValue('countryState', filteredOptions[0].key);
    } // Disable reason: If we include countryState in the dependency array, we will have an unnecessary function call because we also update it in this function.
    // eslint-disable-next-line react-hooks/exhaustive-deps

  }, [autofillCountry, autofillState, options, setValue]);
  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["Fragment"], null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])("input", {
    onChange: event => setAutofillCountry(event.target.value),
    value: autofillCountry,
    name: "country",
    type: "text",
    className: "woocommerce-select-control__autofill-input",
    tabIndex: -1,
    autoComplete: "country"
  }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])("input", {
    onChange: event => setAutofillState(event.target.value),
    value: autofillState,
    name: "state",
    type: "text",
    className: "woocommerce-select-control__autofill-input",
    tabIndex: -1,
    autoComplete: "address-level1"
  }));
}

/**
 * Store address fields.
 *
 * @param {Object} props Props for input components.
 * @param {Function} props.getInputProps Get input props.
 * @param {Function} props.setValue Set value of the countryState input.
 * @return {Object} -
 */
function StoreAddress(_ref) {
  var _locale$address_, _locale$address_2, _locale$address_3, _locale$address_4, _locale$city, _locale$city2, _locale$postcode, _locale$postcode2;

  let {
    getInputProps,
    setValue
  } = _ref;
  const countryState = getInputProps('countryState').value;
  const {
    locale,
    hasFinishedResolution,
    countries,
    loadingCountries
  } = Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_8__["useSelect"])(select => {
    const {
      getLocale,
      getCountries,
      hasFinishedResolution: hasFinishedCountryResolution
    } = select(_woocommerce_data__WEBPACK_IMPORTED_MODULE_3__["COUNTRIES_STORE_NAME"]);
    return {
      locale: getLocale(countryState),
      countries: getCountries(),
      loadingCountries: !hasFinishedCountryResolution('getCountries'),
      hasFinishedResolution: hasFinishedCountryResolution('getLocales')
    };
  });
  const countryStateOptions = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["useMemo"])(() => getCountryStateOptions(countries), [countries]);
  const countryStateAutofill = useGetCountryStateAutofill(countryStateOptions, countryState, setValue);
  Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["useEffect"])(() => {
    if (locale) {
      storeAddressFields.forEach(field => {
        var _locale$fieldKey, _props$value;

        const fieldKey = field.replace(/(address)Line([0-9])/, '$1$2').toLowerCase();
        const props = getInputProps(field);

        if ((_locale$fieldKey = locale[fieldKey]) !== null && _locale$fieldKey !== void 0 && _locale$fieldKey.hidden && ((_props$value = props.value) === null || _props$value === void 0 ? void 0 : _props$value.length) > 0) {
          // Clear hidden field.
          setValue(field, '');
        }
      });
    }
  }, [countryState, locale]);

  if (!hasFinishedResolution || loadingCountries) {
    return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_7__["Spinner"], null);
  }

  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])("div", {
    className: "woocommerce-store-address-fields"
  }, !(locale !== null && locale !== void 0 && (_locale$address_ = locale.address_1) !== null && _locale$address_ !== void 0 && _locale$address_.hidden) && Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_6__["TextControl"], _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0___default()({
    label: (locale === null || locale === void 0 ? void 0 : (_locale$address_2 = locale.address_1) === null || _locale$address_2 === void 0 ? void 0 : _locale$address_2.label) || Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__["__"])('Address line 1', 'woocommerce-admin'),
    required: isAddressFieldRequired('address_1', locale),
    autoComplete: "address-line1"
  }, getInputProps('addressLine1'))), !(locale !== null && locale !== void 0 && (_locale$address_3 = locale.address_2) !== null && _locale$address_3 !== void 0 && _locale$address_3.hidden) && Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_6__["TextControl"], _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0___default()({
    label: (locale === null || locale === void 0 ? void 0 : (_locale$address_4 = locale.address_2) === null || _locale$address_4 === void 0 ? void 0 : _locale$address_4.label) || Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__["__"])('Address line 2 (optional)', 'woocommerce-admin'),
    required: isAddressFieldRequired('address_2', locale),
    autoComplete: "address-line2"
  }, getInputProps('addressLine2'))), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_6__["SelectControl"], _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0___default()({
    label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__["__"])('Country / Region', 'woocommerce-admin'),
    required: true,
    autoComplete: "new-password" // disable autocomplete and autofill
    ,
    options: countryStateOptions,
    excludeSelectedOptions: false,
    showAllOnFocus: true,
    isSearchable: true
  }, getInputProps('countryState'), {
    controlClassName: getInputProps('countryState').className
  }), countryStateAutofill), !(locale !== null && locale !== void 0 && (_locale$city = locale.city) !== null && _locale$city !== void 0 && _locale$city.hidden) && Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_6__["TextControl"], _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0___default()({
    label: (locale === null || locale === void 0 ? void 0 : (_locale$city2 = locale.city) === null || _locale$city2 === void 0 ? void 0 : _locale$city2.label) || Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__["__"])('City', 'woocommerce-admin'),
    required: isAddressFieldRequired('city', locale)
  }, getInputProps('city'), {
    autoComplete: "address-level2"
  })), !(locale !== null && locale !== void 0 && (_locale$postcode = locale.postcode) !== null && _locale$postcode !== void 0 && _locale$postcode.hidden) && Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_6__["TextControl"], _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0___default()({
    label: (locale === null || locale === void 0 ? void 0 : (_locale$postcode2 = locale.postcode) === null || _locale$postcode2 === void 0 ? void 0 : _locale$postcode2.label) || Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__["__"])('Post code', 'woocommerce-admin'),
    required: isAddressFieldRequired('postcode', locale),
    autoComplete: "postal-code"
  }, getInputProps('postCode'))));
}

/***/ }),

/***/ 619:
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),

/***/ 620:
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),

/***/ 621:
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),

/***/ 622:
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),

/***/ 623:
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),

/***/ 624:
/***/ (function(module, exports, __webpack_require__) {

"use strict";
Object.defineProperty(exports,"__esModule",{value:!0}),exports["default"]=_default;var _react=_interopRequireDefault(__webpack_require__(6)),_excluded=["size","onClick","icon","className"];function _interopRequireDefault(a){return a&&a.__esModule?a:{default:a}}function _extends(){return _extends=Object.assign||function(a){for(var b,c=1;c<arguments.length;c++)for(var d in b=arguments[c],b)Object.prototype.hasOwnProperty.call(b,d)&&(a[d]=b[d]);return a},_extends.apply(this,arguments)}function _objectWithoutProperties(a,b){if(null==a)return{};var c,d,e=_objectWithoutPropertiesLoose(a,b);if(Object.getOwnPropertySymbols){var f=Object.getOwnPropertySymbols(a);for(d=0;d<f.length;d++)c=f[d],0<=b.indexOf(c)||Object.prototype.propertyIsEnumerable.call(a,c)&&(e[c]=a[c])}return e}function _objectWithoutPropertiesLoose(a,b){if(null==a)return{};var c,d,e={},f=Object.keys(a);for(d=0;d<f.length;d++)c=f[d],0<=b.indexOf(c)||(e[c]=a[c]);return e}function _default(a){var b=a.size,c=void 0===b?24:b,d=a.onClick,e=a.icon,f=a.className,g=_objectWithoutProperties(a,_excluded),h=["gridicon","gridicons-info",f,!!function isModulo18(a){return 0==a%18}(c)&&"needs-offset",!1,!1].filter(Boolean).join(" ");return _react["default"].createElement("svg",_extends({className:h,height:c,width:c,onClick:d},g,{xmlns:"http://www.w3.org/2000/svg",viewBox:"0 0 24 24"}),_react["default"].createElement("g",null,_react["default"].createElement("path",{d:"M12 2C6.477 2 2 6.477 2 12s4.477 10 10 10 10-4.477 10-10S17.523 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"})))}


/***/ }),

/***/ 625:
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),

/***/ 626:
/***/ (function(module, exports, __webpack_require__) {

"use strict";
Object.defineProperty(exports,"__esModule",{value:!0}),exports["default"]=_default;var _react=_interopRequireDefault(__webpack_require__(6)),_excluded=["size","onClick","icon","className"];function _interopRequireDefault(a){return a&&a.__esModule?a:{default:a}}function _extends(){return _extends=Object.assign||function(a){for(var b,c=1;c<arguments.length;c++)for(var d in b=arguments[c],b)Object.prototype.hasOwnProperty.call(b,d)&&(a[d]=b[d]);return a},_extends.apply(this,arguments)}function _objectWithoutProperties(a,b){if(null==a)return{};var c,d,e=_objectWithoutPropertiesLoose(a,b);if(Object.getOwnPropertySymbols){var f=Object.getOwnPropertySymbols(a);for(d=0;d<f.length;d++)c=f[d],0<=b.indexOf(c)||Object.prototype.propertyIsEnumerable.call(a,c)&&(e[c]=a[c])}return e}function _objectWithoutPropertiesLoose(a,b){if(null==a)return{};var c,d,e={},f=Object.keys(a);for(d=0;d<f.length;d++)c=f[d],0<=b.indexOf(c)||(e[c]=a[c]);return e}function _default(a){var b=a.size,c=void 0===b?24:b,d=a.onClick,e=a.icon,f=a.className,g=_objectWithoutProperties(a,_excluded),h=["gridicon","gridicons-cloud-upload",f,!1,!1,!1].filter(Boolean).join(" ");return _react["default"].createElement("svg",_extends({className:h,height:c,width:c,onClick:d},g,{xmlns:"http://www.w3.org/2000/svg",viewBox:"0 0 24 24"}),_react["default"].createElement("g",null,_react["default"].createElement("path",{d:"M18 9c-.009 0-.017.002-.025.003A6.496 6.496 0 005 9.5a6.5 6.5 0 00.186 1.519C5.123 11.016 5.064 11 5 11a4 4 0 00-4 4c0 1.202.541 2.267 1.38 3h18.593C22.196 17.089 23 15.643 23 14a5 5 0 00-5-5zm-5 4v3h-2v-3H8l4-5 4 5h-3z"})))}


/***/ }),

/***/ 627:
/***/ (function(module, exports, __webpack_require__) {

"use strict";
Object.defineProperty(exports,"__esModule",{value:!0}),exports["default"]=_default;var _react=_interopRequireDefault(__webpack_require__(6)),_excluded=["size","onClick","icon","className"];function _interopRequireDefault(a){return a&&a.__esModule?a:{default:a}}function _extends(){return _extends=Object.assign||function(a){for(var b,c=1;c<arguments.length;c++)for(var d in b=arguments[c],b)Object.prototype.hasOwnProperty.call(b,d)&&(a[d]=b[d]);return a},_extends.apply(this,arguments)}function _objectWithoutProperties(a,b){if(null==a)return{};var c,d,e=_objectWithoutPropertiesLoose(a,b);if(Object.getOwnPropertySymbols){var f=Object.getOwnPropertySymbols(a);for(d=0;d<f.length;d++)c=f[d],0<=b.indexOf(c)||Object.prototype.propertyIsEnumerable.call(a,c)&&(e[c]=a[c])}return e}function _objectWithoutPropertiesLoose(a,b){if(null==a)return{};var c,d,e={},f=Object.keys(a);for(d=0;d<f.length;d++)c=f[d],0<=b.indexOf(c)||(e[c]=a[c]);return e}function _default(a){var b=a.size,c=void 0===b?24:b,d=a.onClick,e=a.icon,f=a.className,g=_objectWithoutProperties(a,_excluded),h=["gridicon","gridicons-phone",f,!1,!1,!1].filter(Boolean).join(" ");return _react["default"].createElement("svg",_extends({className:h,height:c,width:c,onClick:d},g,{xmlns:"http://www.w3.org/2000/svg",viewBox:"0 0 24 24"}),_react["default"].createElement("g",null,_react["default"].createElement("path",{d:"M16 2H8a2 2 0 00-2 2v16a2 2 0 002 2h8a2 2 0 002-2V4a2 2 0 00-2-2zm-3 19h-2v-1h2v1zm3-2H8V5h8v14z"})))}


/***/ }),

/***/ 628:
/***/ (function(module, exports, __webpack_require__) {

"use strict";
Object.defineProperty(exports,"__esModule",{value:!0}),exports["default"]=_default;var _react=_interopRequireDefault(__webpack_require__(6)),_excluded=["size","onClick","icon","className"];function _interopRequireDefault(a){return a&&a.__esModule?a:{default:a}}function _extends(){return _extends=Object.assign||function(a){for(var b,c=1;c<arguments.length;c++)for(var d in b=arguments[c],b)Object.prototype.hasOwnProperty.call(b,d)&&(a[d]=b[d]);return a},_extends.apply(this,arguments)}function _objectWithoutProperties(a,b){if(null==a)return{};var c,d,e=_objectWithoutPropertiesLoose(a,b);if(Object.getOwnPropertySymbols){var f=Object.getOwnPropertySymbols(a);for(d=0;d<f.length;d++)c=f[d],0<=b.indexOf(c)||Object.prototype.propertyIsEnumerable.call(a,c)&&(e[c]=a[c])}return e}function _objectWithoutPropertiesLoose(a,b){if(null==a)return{};var c,d,e={},f=Object.keys(a);for(d=0;d<f.length;d++)c=f[d],0<=b.indexOf(c)||(e[c]=a[c]);return e}function _default(a){var b=a.size,c=void 0===b?24:b,d=a.onClick,e=a.icon,f=a.className,g=_objectWithoutProperties(a,_excluded),h=["gridicon","gridicons-tablet",f,!1,!1,!1].filter(Boolean).join(" ");return _react["default"].createElement("svg",_extends({className:h,height:c,width:c,onClick:d},g,{xmlns:"http://www.w3.org/2000/svg",viewBox:"0 0 24 24"}),_react["default"].createElement("g",null,_react["default"].createElement("path",{d:"M18 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V4a2 2 0 00-2-2zm-5 19h-2v-1h2v1zm5-2H6V5h12v14z"})))}


/***/ }),

/***/ 629:
/***/ (function(module, exports, __webpack_require__) {

"use strict";
Object.defineProperty(exports,"__esModule",{value:!0}),exports["default"]=_default;var _react=_interopRequireDefault(__webpack_require__(6)),_excluded=["size","onClick","icon","className"];function _interopRequireDefault(a){return a&&a.__esModule?a:{default:a}}function _extends(){return _extends=Object.assign||function(a){for(var b,c=1;c<arguments.length;c++)for(var d in b=arguments[c],b)Object.prototype.hasOwnProperty.call(b,d)&&(a[d]=b[d]);return a},_extends.apply(this,arguments)}function _objectWithoutProperties(a,b){if(null==a)return{};var c,d,e=_objectWithoutPropertiesLoose(a,b);if(Object.getOwnPropertySymbols){var f=Object.getOwnPropertySymbols(a);for(d=0;d<f.length;d++)c=f[d],0<=b.indexOf(c)||Object.prototype.propertyIsEnumerable.call(a,c)&&(e[c]=a[c])}return e}function _objectWithoutPropertiesLoose(a,b){if(null==a)return{};var c,d,e={},f=Object.keys(a);for(d=0;d<f.length;d++)c=f[d],0<=b.indexOf(c)||(e[c]=a[c]);return e}function _default(a){var b=a.size,c=void 0===b?24:b,d=a.onClick,e=a.icon,f=a.className,g=_objectWithoutProperties(a,_excluded),h=["gridicon","gridicons-computer",f,!1,!1,!1].filter(Boolean).join(" ");return _react["default"].createElement("svg",_extends({className:h,height:c,width:c,onClick:d},g,{xmlns:"http://www.w3.org/2000/svg",viewBox:"0 0 24 24"}),_react["default"].createElement("g",null,_react["default"].createElement("path",{d:"M20 2H4a2 2 0 00-2 2v12a2 2 0 002 2h6v2H7v2h10v-2h-3v-2h6a2 2 0 002-2V4a2 2 0 00-2-2zm0 14H4V4h16v12z"})))}


/***/ }),

/***/ 630:
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),

/***/ 655:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);

/* harmony default export */ __webpack_exports__["default"] = (_ref => {
  let {} = _ref;
  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("div", null, "Settings page");
});

/***/ }),

/***/ 661:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
// ESM COMPAT FLAG
__webpack_require__.r(__webpack_exports__);

// EXTERNAL MODULE: external ["wp","element"]
var external_wp_element_ = __webpack_require__(0);

// EXTERNAL MODULE: external ["wp","i18n"]
var external_wp_i18n_ = __webpack_require__(2);

// EXTERNAL MODULE: external ["wp","hooks"]
var external_wp_hooks_ = __webpack_require__(27);

// EXTERNAL MODULE: external ["wp","compose"]
var external_wp_compose_ = __webpack_require__(14);

// EXTERNAL MODULE: external "lodash"
var external_lodash_ = __webpack_require__(5);

// EXTERNAL MODULE: external ["wp","data"]
var external_wp_data_ = __webpack_require__(8);

// EXTERNAL MODULE: external ["wc","navigation"]
var external_wc_navigation_ = __webpack_require__(13);

// EXTERNAL MODULE: external ["wc","data"]
var external_wc_data_ = __webpack_require__(12);

// EXTERNAL MODULE: external ["wc","tracks"]
var external_wc_tracks_ = __webpack_require__(17);

// EXTERNAL MODULE: external ["wc","wcSettings"]
var external_wc_wcSettings_ = __webpack_require__(15);

// EXTERNAL MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/@babel+runtime@7.17.2/node_modules/@babel/runtime/helpers/extends.js
var helpers_extends = __webpack_require__(40);
var extends_default = /*#__PURE__*/__webpack_require__.n(helpers_extends);

// EXTERNAL MODULE: external ["wc","components"]
var external_wc_components_ = __webpack_require__(22);

// EXTERNAL MODULE: external ["wp","components"]
var external_wp_components_ = __webpack_require__(4);

// EXTERNAL MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/classnames@2.3.1/node_modules/classnames/index.js
var classnames = __webpack_require__(7);
var classnames_default = /*#__PURE__*/__webpack_require__.n(classnames);

// EXTERNAL MODULE: ./client/lib/currency-context.js
var currency_context = __webpack_require__(536);

// EXTERNAL MODULE: ./client/lib/notices/index.js
var notices = __webpack_require__(541);

// CONCATENATED MODULE: ./client/profile-wizard/steps/business-details/data/platform-options.js
/**
 * External dependencies
 */

const platformOptions = [{
  key: 'shopify',
  label: Object(external_wp_i18n_["__"])('Shopify', 'woocommerce-admin')
}, {
  key: 'bigcommerce',
  label: Object(external_wp_i18n_["__"])('BigCommerce', 'woocommerce-admin')
}, {
  key: 'magento',
  label: Object(external_wp_i18n_["__"])('Magento', 'woocommerce-admin')
}, {
  key: 'wix',
  label: Object(external_wp_i18n_["__"])('Wix', 'woocommerce-admin')
}, {
  key: 'amazon',
  label: Object(external_wp_i18n_["__"])('Amazon', 'woocommerce-admin')
}, {
  key: 'ebay',
  label: Object(external_wp_i18n_["__"])('eBay', 'woocommerce-admin')
}, {
  key: 'etsy',
  label: Object(external_wp_i18n_["__"])('Etsy', 'woocommerce-admin')
}, {
  key: 'squarespace',
  label: Object(external_wp_i18n_["__"])('Squarespace', 'woocommerce-admin')
}, {
  key: 'other',
  label: Object(external_wp_i18n_["__"])('Other', 'woocommerce-admin')
}];
// CONCATENATED MODULE: ./client/profile-wizard/steps/business-details/data/employee-options.js
/**
 * External dependencies
 */

const employeeOptions = [{
  key: '1',
  label: Object(external_wp_i18n_["__"])("It's just me", 'woocommerce-admin')
}, {
  key: '<10',
  label: Object(external_wp_i18n_["__"])('< 10', 'woocommerce-admin')
}, {
  key: '10-50',
  label: '10 - 50'
}, {
  key: '50-250',
  label: '50 - 250'
}, {
  key: '+250',
  label: Object(external_wp_i18n_["__"])('+250', 'woocommerce-admin')
}, {
  key: 'not specified',
  label: Object(external_wp_i18n_["__"])("I'd rather not say", 'woocommerce-admin')
}];
// CONCATENATED MODULE: ./client/profile-wizard/steps/business-details/data/selling-venue-options.js
/**
 * External dependencies
 */

const sellingVenueOptions = [{
  key: 'no',
  label: Object(external_wp_i18n_["__"])('No', 'woocommerce-admin')
}, {
  key: 'other',
  label: Object(external_wp_i18n_["__"])('Yes, on another platform', 'woocommerce-admin')
}, {
  key: 'other-woocommerce',
  label: Object(external_wp_i18n_["__"])('Yes, I own a different store powered by WooCommerce', 'woocommerce-admin')
}, {
  key: 'brick-mortar',
  label: Object(external_wp_i18n_["__"])('Yes, in person at physical stores and/or events', 'woocommerce-admin')
}, {
  key: 'brick-mortar-other',
  label: Object(external_wp_i18n_["__"])('Yes, on another platform and in person at physical stores and/or events', 'woocommerce-admin')
}];
// EXTERNAL MODULE: ./client/dashboard/utils.js
var utils = __webpack_require__(80);

// EXTERNAL MODULE: external ["wc","number"]
var external_wc_number_ = __webpack_require__(136);

// CONCATENATED MODULE: ./client/profile-wizard/steps/business-details/data/product-options.js
/**
 * External dependencies
 */



const formatNumber = (numberConfig, value) => {
  return Object(external_wc_number_["formatValue"])(numberConfig, 'number', value);
};

const getNumberRangeString = function (numberConfig, min) {
  let max = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : false;
  let formatAmount = arguments.length > 3 && arguments[3] !== undefined ? arguments[3] : formatNumber;

  if (!max) {
    return Object(external_wp_i18n_["sprintf"])(Object(external_wp_i18n_["_x"])('%s+', 'store product count or revenue', 'woocommerce-admin'), formatAmount(numberConfig, min));
  }

  return Object(external_wp_i18n_["sprintf"])(Object(external_wp_i18n_["_x"])('%1$s - %2$s', 'store product count or revenue range', 'woocommerce-admin'), formatAmount(numberConfig, min), formatAmount(numberConfig, max));
};
const getProductCountOptions = numberConfig => [{
  key: '0',
  label: Object(external_wp_i18n_["__"])("I don't have any products yet.", 'woocommerce-admin')
}, {
  key: '1-10',
  label: getNumberRangeString(numberConfig, 1, 10)
}, {
  key: '11-100',
  label: getNumberRangeString(numberConfig, 11, 100)
}, {
  key: '101-1000',
  label: getNumberRangeString(numberConfig, 101, 1000)
}, {
  key: '1000+',
  label: getNumberRangeString(numberConfig, 1000)
}];
// CONCATENATED MODULE: ./client/profile-wizard/steps/business-details/data/revenue-options.js
/**
 * External dependencies
 */

/**
 * Internal dependencies
 */


 // These are rough exchange rates from USD.  Precision is not paramount.
// The keys here should match the keys in `getCurrencyData`.

const exchangeRates = {
  US: 1,
  EU: 0.9,
  IN: 71.24,
  GB: 0.76,
  BR: 4.19,
  VN: 23172.5,
  ID: 14031.0,
  BD: 84.87,
  PK: 154.8,
  RU: 63.74,
  TR: 5.75,
  MX: 19.37,
  CA: 1.32
};

const convertCurrency = (value, country) => {
  const region = Object(utils["c" /* getCurrencyRegion */])(country);

  if (region === 'US') {
    return value;
  }

  const exchangeRate = exchangeRates[region] || exchangeRates.US;
  const digits = exchangeRate.toString().split('.')[0].length;
  const multiplier = Math.pow(10, 2 + digits);
  return Math.round(value * exchangeRate / multiplier) * multiplier;
};

const getRevenueOptions = (numberConfig, country, formatAmount) => [{
  key: 'none',
  label: Object(external_wp_i18n_["sprintf"])(
  /* translators: %s: $0 revenue amount */
  Object(external_wp_i18n_["__"])("%s (I'm just getting started)", 'woocommerce-admin'), formatAmount(0))
}, {
  key: 'up-to-2500',
  label: Object(external_wp_i18n_["sprintf"])(
  /* translators: %s: A given revenue amount, e.g., $2500 */
  Object(external_wp_i18n_["__"])('Up to %s', 'woocommerce-admin'), formatAmount(convertCurrency(2500, country)))
}, {
  key: '2500-10000',
  label: getNumberRangeString(numberConfig, convertCurrency(2500, country), convertCurrency(10000, country), (_, amount) => formatAmount(amount))
}, {
  key: '10000-50000',
  label: getNumberRangeString(numberConfig, convertCurrency(10000, country), convertCurrency(50000, country), (_, amount) => formatAmount(amount))
}, {
  key: '50000-250000',
  label: getNumberRangeString(numberConfig, convertCurrency(50000, country), convertCurrency(250000, country), (_, amount) => formatAmount(amount))
}, {
  key: 'more-than-250000',
  label: Object(external_wp_i18n_["sprintf"])(
  /* translators: %s: A given revenue amount, e.g., $250000 */
  Object(external_wp_i18n_["__"])('More than %s', 'woocommerce-admin'), formatAmount(convertCurrency(250000, country)))
}, {
  key: 'rather-not-say',
  label: Object(external_wp_i18n_["__"])("I'd rather not say", 'woocommerce-admin')
}];
// EXTERNAL MODULE: external ["wc","experimental"]
var external_wc_experimental_ = __webpack_require__(19);

// EXTERNAL MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/@wordpress+icons@6.3.0/node_modules/@wordpress/icons/build-module/icon/index.js
var icon = __webpack_require__(116);

// EXTERNAL MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/@wordpress+icons@6.3.0/node_modules/@wordpress/icons/build-module/library/chevron-up.js
var chevron_up = __webpack_require__(306);

// EXTERNAL MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/@wordpress+icons@6.3.0/node_modules/@wordpress/icons/build-module/library/chevron-down.js
var chevron_down = __webpack_require__(307);

// EXTERNAL MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/@automattic+interpolate-components@1.2.0_react@17.0.2/node_modules/@automattic/interpolate-components/dist/esm/index.js + 1 modules
var esm = __webpack_require__(79);

// CONCATENATED MODULE: ./client/profile-wizard/steps/business-details/flows/selective-bundle/app-illustration.js

const AppIllustration = () => {
  return Object(external_wp_element_["createElement"])("svg", {
    width: "200",
    height: "148",
    viewBox: "0 0 200 148",
    fill: "none",
    xmlns: "http://www.w3.org/2000/svg"
  }, Object(external_wp_element_["createElement"])("g", {
    clipPath: "url(#clip0)"
  }, Object(external_wp_element_["createElement"])("path", {
    d: "M197.563 2.53875e-09H62.909C62.3961 0.000450584 61.9043 0.205742 61.5416 0.570805C61.179 0.935868 60.975 1.43087 60.9746 1.94714V50.9404H93.5623C94.4445 50.9415 95.2902 51.2947 95.9141 51.9226C96.5379 52.5505 96.8888 53.4019 96.8899 54.2899V95.7402H197.563C197.843 95.7402 198.119 95.6791 198.373 95.5612C198.627 95.4432 198.853 95.2712 199.034 95.0569C199.05 95.0402 199.064 95.0222 199.076 95.0033C199.192 94.8612 199.285 94.7024 199.354 94.5322C199.451 94.2981 199.501 94.0468 199.5 93.7931V1.94714C199.499 1.43051 199.295 0.935241 198.932 0.57014C198.569 0.20504 198.077 -2.63458e-05 197.563 2.53875e-09Z",
    fill: "#F2F2F2"
  }), Object(external_wp_element_["createElement"])("path", {
    d: "M199.222 7.80469H61.25V8.36132H199.222V7.80469Z",
    fill: "#CCCCCC"
  }), Object(external_wp_element_["createElement"])("path", {
    d: "M65.95 5.84371C66.8662 5.84371 67.609 5.09607 67.609 4.17381C67.609 3.25155 66.8662 2.50391 65.95 2.50391C65.0338 2.50391 64.291 3.25155 64.291 4.17381C64.291 5.09607 65.0338 5.84371 65.95 5.84371Z",
    fill: "#CCCCCC"
  }), Object(external_wp_element_["createElement"])("path", {
    d: "M70.72 5.84371C71.6363 5.84371 72.379 5.09607 72.379 4.17381C72.379 3.25155 71.6363 2.50391 70.72 2.50391C69.8038 2.50391 69.061 3.25155 69.061 4.17381C69.061 5.09607 69.8038 5.84371 70.72 5.84371Z",
    fill: "#CCCCCC"
  }), Object(external_wp_element_["createElement"])("path", {
    d: "M75.4896 5.84371C76.4058 5.84371 77.1486 5.09607 77.1486 4.17381C77.1486 3.25155 76.4058 2.50391 75.4896 2.50391C74.5733 2.50391 73.8306 3.25155 73.8306 4.17381C73.8306 5.09607 74.5733 5.84371 75.4896 5.84371Z",
    fill: "#CCCCCC"
  }), Object(external_wp_element_["createElement"])("path", {
    d: "M164.842 19.957H95.6295C94.8646 19.957 94.1311 20.2629 93.5903 20.8073C93.0494 21.3516 92.7456 22.09 92.7456 22.8599C92.7456 23.6298 93.0494 24.3681 93.5903 24.9125C94.1311 25.4569 94.8646 25.7627 95.6295 25.7627H164.842C165.607 25.7627 166.341 25.4569 166.882 24.9125C167.422 24.3681 167.726 23.6298 167.726 22.8599C167.726 22.09 167.422 21.3516 166.882 20.8073C166.341 20.2629 165.607 19.957 164.842 19.957ZM164.842 25.3161H95.6295C94.9823 25.3161 94.3616 25.0573 93.904 24.5967C93.4464 24.1361 93.1893 23.5113 93.1893 22.8599C93.1893 22.2084 93.4464 21.5837 93.904 21.123C94.3616 20.6624 94.9823 20.4036 95.6295 20.4036H164.842C165.489 20.4036 166.11 20.6624 166.568 21.123C167.025 21.5837 167.283 22.2084 167.283 22.8599C167.283 23.5113 167.025 24.1361 166.568 24.5967C166.11 25.0573 165.489 25.3161 164.842 25.3161Z",
    fill: "#CCCCCC"
  }), Object(external_wp_element_["createElement"])("path", {
    d: "M186.022 43.0859H116.809C116.044 43.0859 115.31 43.3918 114.769 43.9362C114.229 44.4806 113.925 45.2189 113.925 45.9888C113.925 46.7587 114.229 47.497 114.769 48.0414C115.31 48.5858 116.044 48.8916 116.809 48.8916H186.022C186.786 48.8916 187.52 48.5858 188.061 48.0414C188.602 47.497 188.905 46.7587 188.905 45.9888C188.905 45.2189 188.602 44.4806 188.061 43.9362C187.52 43.3918 186.786 43.0859 186.022 43.0859Z",
    fill: "white"
  }), Object(external_wp_element_["createElement"])("path", {
    d: "M186.022 53.8047H116.809C116.044 53.8047 115.31 54.1105 114.769 54.6549C114.229 55.1993 113.925 55.9376 113.925 56.7075C113.925 57.4774 114.229 58.2158 114.769 58.7601C115.31 59.3045 116.044 59.6104 116.809 59.6104H186.022C186.786 59.6104 187.52 59.3045 188.061 58.7601C188.602 58.2158 188.905 57.4774 188.905 56.7075C188.905 55.9376 188.602 55.1993 188.061 54.6549C187.52 54.1105 186.786 53.8047 186.022 53.8047Z",
    fill: "white"
  }), Object(external_wp_element_["createElement"])("path", {
    d: "M186.022 64.5195H116.809C116.044 64.5195 115.31 64.8254 114.769 65.3698C114.229 65.9141 113.925 66.6525 113.925 67.4224C113.925 68.1923 114.229 68.9306 114.769 69.475C115.31 70.0194 116.044 70.3252 116.809 70.3252H186.022C186.786 70.3252 187.52 70.0194 188.061 69.475C188.602 68.9306 188.905 68.1923 188.905 67.4224C188.905 66.6525 188.602 65.9141 188.061 65.3698C187.52 64.8254 186.786 64.5195 186.022 64.5195Z",
    fill: "white"
  }), Object(external_wp_element_["createElement"])("path", {
    d: "M105.623 38.2852H74.1183C73.4425 38.286 72.7947 38.5565 72.3168 39.0375C71.839 39.5185 71.5702 40.1706 71.5693 40.8508V50.9416H72.013V40.8508C72.0139 40.2891 72.2359 39.7506 72.6306 39.3533C73.0252 38.9561 73.5602 38.7326 74.1183 38.7317H105.623C106.182 38.7322 106.717 38.9556 107.112 39.3529C107.506 39.7502 107.728 40.289 107.729 40.8508V72.5633C107.728 73.1251 107.506 73.6638 107.112 74.0611C106.717 74.4585 106.182 74.6819 105.623 74.6824H96.8897V75.1289H105.623C106.299 75.1285 106.947 74.858 107.425 74.377C107.903 73.8959 108.172 73.2436 108.172 72.5633V40.8508C108.172 40.1705 107.903 39.5182 107.425 39.0371C106.947 38.556 106.299 38.2856 105.623 38.2852Z",
    fill: "#CCCCCC"
  }), Object(external_wp_element_["createElement"])("path", {
    d: "M23.9309 70.9116C23.8195 70.9162 19.0705 70.5847 18.9492 70.5806L19.3758 66.294L22.0808 66.212L27.2495 56.5756C26.5327 55.1996 27.4148 53.3739 28.9355 53.0925C32 52.3914 33.0526 57.2443 29.9789 57.8901L25.7036 69.6652C25.5695 70.03 25.3278 70.3449 25.011 70.5676C24.6942 70.7904 24.3174 70.9104 23.9309 70.9116Z",
    fill: "#FFB8B8"
  }), Object(external_wp_element_["createElement"])("path", {
    d: "M11.4107 73.118C6.89154 73.1291 6.49482 66.2544 11.024 65.7699C23.0006 65.0415 21.485 62.0137 22.3945 70.9448C22.4224 71.2097 22.3448 71.475 22.1787 71.6824C22.0126 71.8898 21.7715 72.0223 21.5084 72.051L11.803 73.0968C11.6727 73.1109 11.5417 73.1179 11.4107 73.118Z",
    className: "fill-theme-color"
  }), Object(external_wp_element_["createElement"])("path", {
    d: "M10.3793 51.3852C16.605 54.9512 11.494 64.3601 5.15222 61.0097C-1.0733 57.4438 4.03771 48.0349 10.3793 51.3852Z",
    fill: "#FFB8B8"
  }), Object(external_wp_element_["createElement"])("path", {
    d: "M16.0395 132.376L18.759 132.376L20.053 121.816L16.0391 121.817L16.0395 132.376Z",
    fill: "#FFB8B8"
  }), Object(external_wp_element_["createElement"])("path", {
    d: "M15.4567 134.915L24.0042 134.915C23.9716 130.476 18.2546 131.755 15.4565 131.591L15.4567 134.915Z",
    fill: "#2F2E41"
  }), Object(external_wp_element_["createElement"])("path", {
    d: "M4.28218 132.376L7.00167 132.376L8.29564 121.816L4.28174 121.817L4.28218 132.376Z",
    fill: "#FFB8B8"
  }), Object(external_wp_element_["createElement"])("path", {
    d: "M3.69937 134.915L12.2469 134.915C12.2142 130.476 6.49728 131.755 3.69922 131.591L3.69937 134.915Z",
    fill: "#2F2E41"
  }), Object(external_wp_element_["createElement"])("path", {
    d: "M7.37266 128.688C6.71536 128.507 3.14362 129.056 2.72209 128.335C1.24999 113.483 1.57722 98.9486 4.845 90.9619L16.0806 90.2695C18.5931 94.8863 24.3684 125.522 20.8847 127.385L16.4048 127.546C16.1493 127.554 15.8988 127.474 15.6952 127.318C15.4916 127.163 15.3475 126.941 15.2869 126.691L11.539 105.229C10.6057 103.916 8.77111 127.832 8.46815 127.742C8.42864 128.006 8.29626 128.247 8.09515 128.42C7.89404 128.594 7.63762 128.689 7.37266 128.688Z",
    fill: "#2F2E41"
  }), Object(external_wp_element_["createElement"])("path", {
    d: "M4.48843 92.8373C-1.18427 86.8634 2.43414 70.8475 2.25101 71.1881C2.26655 70.7958 3.55141 64.7536 6.87506 63.762C9.51196 62.886 12.4305 65.5063 12.7906 68.1566L16.7406 91.6368C16.7611 91.7646 16.7514 91.8955 16.7123 92.0188C16.6733 92.1422 16.6059 92.2546 16.5158 92.3469C16.71 92.9556 4.76613 92.7153 4.48843 92.8373Z",
    className: "fill-theme-color"
  }), Object(external_wp_element_["createElement"])("path", {
    d: "M6.32941 55.5845C6.82759 55.2675 7.07312 54.7238 7.36309 54.2285C8.89053 53.3522 10.6464 55.472 12.262 54.6809C16.8302 50.2665 12.1346 48.9642 7.92721 48.7314C6.9333 48.6081 6.08781 48.963 5.33637 49.5492C-3.06805 48.9031 0.962591 60.1519 6.26679 61.6376C7.2033 62.0505 7.90582 61.2148 7.07994 60.4551C5.93728 59.2493 4.52181 56.8221 6.32941 55.5845Z",
    fill: "#2F2E41"
  }), Object(external_wp_element_["createElement"])("path", {
    d: "M93.5621 50.4922H32.779C31.7793 50.4936 30.8209 50.894 30.1139 51.6056C29.407 52.3172 29.0092 53.2819 29.0078 54.2882V131.548C29.0092 132.555 29.407 133.52 30.1139 134.231C30.8209 134.943 31.7793 135.343 32.779 135.345H93.5621C94.5619 135.343 95.5202 134.943 96.2272 134.231C96.9341 133.52 97.3319 132.555 97.3333 131.548V54.2882C97.3319 53.2819 96.9341 52.3172 96.2272 51.6056C95.5202 50.894 94.5619 50.4936 93.5621 50.4922ZM96.8896 131.548C96.8886 132.436 96.5376 133.288 95.9138 133.916C95.29 134.544 94.4443 134.897 93.5621 134.898H32.779C31.8968 134.897 31.0511 134.544 30.4273 133.916C29.8035 133.288 29.4526 132.436 29.4515 131.548V54.2882C29.4526 53.4002 29.8035 52.5489 30.4273 51.921C31.0511 51.2931 31.8968 50.9399 32.779 50.9388H93.5621C94.4443 50.9399 95.29 51.2931 95.9138 51.921C96.5376 52.5489 96.8886 53.4002 96.8896 54.2882V131.548Z",
    fill: "#3F3D56"
  }), Object(external_wp_element_["createElement"])("path", {
    d: "M15.6527 83.0043C13.6494 83.2327 12.7698 78.5402 12.0039 77.3445L15.9936 75.7852L17.2918 78.1751L28.168 78.4472C28.2326 78.3591 28.3032 78.2755 28.3793 78.1971C30.5362 75.9238 34.0488 79.33 31.9 81.5984C31.663 81.8519 31.3752 82.0521 31.0557 82.1855C30.7362 82.319 30.3923 82.3828 30.0465 82.3727C29.7007 82.3627 29.361 82.2789 29.0497 82.1271C28.7384 81.9753 28.4626 81.7588 28.2405 81.4918C27.9719 81.5111 15.9153 83.0114 15.6527 83.0043Z",
    fill: "#FFB8B8"
  }), Object(external_wp_element_["createElement"])("path", {
    d: "M12.6985 80.7664C12.0314 81.4133 6.91061 72.3318 6.5365 72.0751C3.94893 68.0267 10.0405 64.0298 12.6727 68.0562L17.9961 76.2911C18.1402 76.5145 18.1904 76.7864 18.1356 77.047C18.0808 77.3076 17.9255 77.5357 17.7039 77.6812C17.4613 77.7956 12.8697 81.0123 12.6985 80.7664Z",
    className: "fill-theme-color"
  }), Object(external_wp_element_["createElement"])("path", {
    d: "M81.583 103.974H44.7583C40.9966 103.955 40.9809 98.1877 44.7584 98.168H81.583C85.3421 98.1857 85.3624 103.954 81.583 103.974Z",
    fill: "#CCCCCC"
  }), Object(external_wp_element_["createElement"])("path", {
    d: "M81.583 114.692H44.7583C40.9966 114.674 40.9809 108.906 44.7584 108.887H81.583C85.3421 108.904 85.3624 114.673 81.583 114.692Z",
    fill: "#CCCCCC"
  }), Object(external_wp_element_["createElement"])("path", {
    d: "M81.583 125.411H44.7583C40.9966 125.393 40.9809 119.625 44.7584 119.605H81.583C85.3421 119.623 85.3624 125.391 81.583 125.411Z",
    fill: "#CCCCCC"
  }), Object(external_wp_element_["createElement"])("path", {
    d: "M95.3371 57.6387C94.1963 57.6387 93.0812 57.2982 92.1327 56.6603C91.1842 56.0223 90.4449 55.1156 90.0084 54.0548C89.5718 52.9939 89.4576 51.8266 89.6802 50.7004C89.9027 49.5742 90.452 48.5397 91.2587 47.7278C92.0653 46.9159 93.093 46.3629 94.2118 46.1389C95.3307 45.9149 96.4904 46.0299 97.5443 46.4693C98.5982 46.9087 99.499 47.6528 100.133 48.6076C100.767 49.5623 101.105 50.6848 101.105 51.833C101.103 53.3723 100.495 54.8479 99.4136 55.9363C98.3323 57.0247 96.8662 57.637 95.3371 57.6387Z",
    className: "fill-theme-color"
  }), Object(external_wp_element_["createElement"])("path", {
    d: "M97.999 51.6121H95.5588V48.821C95.5588 48.7617 95.5355 48.7049 95.4939 48.6631C95.4523 48.6212 95.3958 48.5977 95.337 48.5977C95.2782 48.5977 95.2217 48.6212 95.1801 48.6631C95.1385 48.7049 95.1152 48.7617 95.1152 48.821V51.6121H92.675C92.6161 51.6121 92.5597 51.6357 92.5181 51.6775C92.4765 51.7194 92.4531 51.7762 92.4531 51.8354C92.4531 51.8947 92.4765 51.9515 92.5181 51.9933C92.5597 52.0352 92.6161 52.0587 92.675 52.0587H95.1152V54.8499C95.1152 54.9091 95.1385 54.9659 95.1801 55.0078C95.2217 55.0497 95.2782 55.0732 95.337 55.0732C95.3958 55.0732 95.4523 55.0497 95.4939 55.0078C95.5355 54.9659 95.5588 54.9091 95.5588 54.8499V52.0587H97.999C98.0579 52.0587 98.1143 52.0352 98.1559 51.9933C98.1975 51.9515 98.2209 51.8947 98.2209 51.8354C98.2209 51.7762 98.1975 51.7194 98.1559 51.6775C98.1143 51.6357 98.0579 51.6121 97.999 51.6121Z",
    fill: "white"
  }), Object(external_wp_element_["createElement"])("path", {
    d: "M80.9177 91.2002H45.424C44.4535 91.1991 43.5232 90.8105 42.837 90.1198C42.1508 89.4291 41.7648 88.4926 41.7637 87.5158V67.5086C41.7648 66.5318 42.1508 65.5953 42.837 64.9046C43.5232 64.2139 44.4535 63.8253 45.424 63.8242H80.9177C81.8882 63.8253 82.8185 64.2139 83.5047 64.9046C84.1909 65.5953 84.5769 66.5318 84.578 67.5086V87.5158C84.5769 88.4926 84.1909 89.4291 83.5047 90.1198C82.8185 90.8105 81.8882 91.1991 80.9177 91.2002Z",
    className: "fill-theme-color"
  })), Object(external_wp_element_["createElement"])("defs", null, Object(external_wp_element_["createElement"])("clipPath", {
    id: "clip0"
  }, Object(external_wp_element_["createElement"])("rect", {
    width: "199",
    height: "148",
    fill: "white",
    transform: "translate(0.5)"
  }))));
};
// EXTERNAL MODULE: ./client/profile-wizard/steps/business-details/flows/selective-bundle/selective-extensions-bundle/style.scss
var selective_extensions_bundle_style = __webpack_require__(619);

// EXTERNAL MODULE: ./client/lib/sanitize-html/index.js
var sanitize_html = __webpack_require__(546);

// CONCATENATED MODULE: ./client/lib/collections/index.js
/**
 * Returns an object with items grouped by the sent key.
 *
 * @param {Array} array array of objects.
 * @param {string} key the object prop that will be used to group elements.
 * @param {string} defaultKey if the key is not found in the object, it will use this value.
 * @return {Object} Object that contains the grouped elements.
 */
const groupListOfObjectsBy = function (array, key) {
  let defaultKey = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : 'undefined';

  if (array && Array.isArray(array) && array.length) {
    if (!key) {
      return array;
    }

    return array.reduce((result, currentValue) => {
      if (!currentValue[key]) {
        currentValue[key] = defaultKey;
      }

      (result[currentValue[key]] = result[currentValue[key]] || []).push(currentValue);
      return result;
    }, {});
  }

  return {};
};
/**
 * Returns a (shallow) copy of an object with all its props set to the specified value
 *
 * @param {*} obj the Object to copy.
 * @param {*} value the value to set all props on the object to.
 */

const setAllPropsToValue = (obj, value) => {
  return Object.entries(obj).reduce((acc, _ref) => {
    let [key] = _ref;
    return { ...acc,
      [key]: value
    };
  }, {});
};
// CONCATENATED MODULE: ./client/profile-wizard/steps/business-details/flows/selective-bundle/selective-extensions-bundle/index.js


/**
 * External dependencies
 */










/**
 * Internal dependencies
 */






const ALLOWED_PLUGIN_CATEGORIES = ['obw/basics', 'obw/grow'];

const FreeBadge = () => {
  return Object(external_wp_element_["createElement"])("div", {
    className: "woocommerce-admin__business-details__free-badge"
  }, Object(external_wp_i18n_["__"])('Free', 'woocommerce-admin'));
};

const renderBusinessExtensionHelpText = (values, isInstallingActivating) => {
  const extensions = Object.keys(values).filter(key => values[key] && key !== 'install_extensions');

  if (extensions.length === 0) {
    return null;
  }

  const extensionsList = extensions.reduce((uniqueExtensionList, extension) => {
    const extensionName = external_wc_data_["pluginNames"][extension];
    return uniqueExtensionList.includes(extensionName) ? uniqueExtensionList : [...uniqueExtensionList, extensionName];
  }, []).join(', ');

  if (isInstallingActivating) {
    return Object(external_wp_element_["createElement"])("div", {
      className: "woocommerce-profile-wizard__footnote"
    }, Object(external_wp_element_["createElement"])(external_wc_experimental_["Text"], {
      variant: "caption",
      as: "p",
      size: "12",
      lineHeight: "16px"
    }, Object(external_wp_i18n_["sprintf"])(
    /* translators: %s: a comma separated list of plugins, e.g. Jetpack, Woocommerce Shipping */
    Object(external_wp_i18n_["_n"])('Installing the following plugin: %s', 'Installing the following plugins: %s', extensions.length, 'woocommerce-admin'), extensionsList)));
  }

  const accountRequiredText = Object(external_wp_i18n_["__"])('User accounts are required to use these features.', 'woocommerce-admin');

  const extensionsWithToS = extensions.filter(extension => extension === 'jetpack' || extension.includes('woocommerce-services'));
  const isInstallingJetpackAndWCServices = extensionsWithToS.includes('jetpack') && (extensionsWithToS.includes('woocommerce-services:shipping') || extensionsWithToS.includes('woocommerce-services:tax'));
  const extensionsListText = isInstallingJetpackAndWCServices ? 'Jetpack and WooCommerce Shipping & Tax' : external_wc_data_["pluginNames"][extensionsWithToS[0]];
  const installingJetpackShippingTaxToS = Object(external_wp_i18n_["sprintf"])(
  /* translators: %s: a list of plugins, e.g. Jetpack */
  Object(external_wp_i18n_["_n"])('By installing %s plugin for free you agree to our {{link}}Terms of Service{{/link}}.', 'By installing %s plugins for free you agree to our {{link}}Terms of Service{{/link}}.', extensionsWithToS.length, 'woocommerce-admin'), extensionsListText);
  return Object(external_wp_element_["createElement"])("div", {
    className: "woocommerce-profile-wizard__footnote"
  }, Object(external_wp_element_["createElement"])(external_wc_experimental_["Text"], {
    variant: "caption",
    as: "p",
    size: "12",
    lineHeight: "16px"
  }, Object(external_wp_i18n_["sprintf"])(
  /* translators: %1$s: a comma separated list of plugins, e.g. Jetpack, Woocommerce Shipping, %2$s: text: 'User accounts are required to use these features.'  */
  Object(external_wp_i18n_["_n"])('The following plugin will be installed for free: %1$s. %2$s', 'The following plugins will be installed for free: %1$s. %2$s', extensions.length, 'woocommerce-admin'), extensionsList, accountRequiredText)), extensionsWithToS.length > 0 && Object(external_wp_element_["createElement"])(external_wc_experimental_["Text"], {
    variant: "caption",
    as: "p",
    size: "12",
    lineHeight: "16px"
  }, Object(esm["a" /* default */])({
    mixedString: installingJetpackShippingTaxToS,
    components: {
      link: Object(external_wp_element_["createElement"])(external_wc_components_["Link"], {
        href: "https://wordpress.com/tos/",
        target: "_blank",
        type: "external"
      })
    }
  })));
};

const BundleExtensionCheckbox = _ref => {
  let {
    onChange,
    description,
    isChecked
  } = _ref;

  const recordProductLinkClick = event => {
    const link = event.target.closest('a');

    if (!link || !event.currentTarget.contains(link) || !link.href.startsWith('https://woocommerce.com/products/')) {
      return;
    }

    Object(external_wc_tracks_["recordEvent"])('storeprofiler_store_business_features_link_click', {
      extension_name: link.href.split('https://woocommerce.com/products/')[1]
    });
  };

  return Object(external_wp_element_["createElement"])("div", {
    className: "woocommerce-admin__business-details__selective-extensions-bundle__extension"
  }, Object(external_wp_element_["createElement"])(external_wp_components_["CheckboxControl"], {
    id: "woocommerce-business-extensions__checkbox",
    checked: isChecked,
    onChange: onChange
  }), Object(external_wp_element_["createElement"])("p", {
    className: "woocommerce-admin__business-details__selective-extensions-bundle__description",
    dangerouslySetInnerHTML: Object(sanitize_html["a" /* default */])(description),
    onClick: recordProductLinkClick
  }), Object(external_wp_element_["createElement"])(FreeBadge, null));
};

const ExtensionSection = _ref2 => {
  let {
    isResolving,
    title,
    extensions,
    installExtensionOptions,
    onCheckboxChange
  } = _ref2;

  if (isResolving) {
    return Object(external_wp_element_["createElement"])("div", null, Object(external_wp_element_["createElement"])(external_wp_components_["Spinner"], null));
  }

  if (extensions.length === 0) {
    return null;
  }

  return Object(external_wp_element_["createElement"])("div", null, Object(external_wp_element_["createElement"])("div", {
    className: "woocommerce-admin__business-details__selective-extensions-bundle__category"
  }, title), extensions.map(_ref3 => {
    let {
      description,
      key
    } = _ref3;
    return Object(external_wp_element_["createElement"])(BundleExtensionCheckbox, {
      key: key,
      description: description,
      isChecked: installExtensionOptions[key],
      onChange: onCheckboxChange(key)
    });
  }));
};
const createInstallExtensionOptions = installableExtensions => {
  return installableExtensions.reduce((acc, curr) => {
    const plugins = curr.plugins.reduce((pluginAcc, plugin) => {
      return { ...pluginAcc,
        [plugin.key]: true
      };
    }, {});
    return { ...acc,
      ...plugins
    };
  }, {
    install_extensions: true
  });
};
const SelectiveExtensionsBundle = _ref4 => {
  let {
    isInstallingActivating,
    onSubmit,
    country,
    productTypes,
    industry
  } = _ref4;
  const [showExtensions, setShowExtensions] = Object(external_wp_element_["useState"])(false);
  const [installExtensionOptions, setInstallExtensionOptions] = Object(external_wp_element_["useState"])({
    install_extensions: true
  });
  const {
    freeExtensions: freeExtensionBundleByCategory,
    isResolving
  } = Object(external_wp_data_["useSelect"])(select => {
    const {
      getFreeExtensions,
      hasFinishedResolution
    } = select(external_wc_data_["ONBOARDING_STORE_NAME"]);
    return {
      freeExtensions: getFreeExtensions(),
      isResolving: !hasFinishedResolution('getFreeExtensions')
    };
  });
  const {
    invalidateResolutionForStoreSelector
  } = Object(external_wp_data_["useDispatch"])(external_wc_data_["ONBOARDING_STORE_NAME"]);
  Object(external_wp_element_["useEffect"])(() => {
    invalidateResolutionForStoreSelector('getFreeExtensions'); // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [country, industry]);
  const installableExtensions = Object(external_wp_element_["useMemo"])(() => {
    return freeExtensionBundleByCategory.filter(extensionBundle => {
      if (window.wcAdminFeatures && window.wcAdminFeatures.subscriptions && Object(utils["b" /* getCountryCode */])(country) === 'US') {
        if (productTypes.includes('subscriptions')) {
          extensionBundle.plugins = extensionBundle.plugins.filter(extension => extension.key !== 'woocommerce-payments' || extension.key === 'woocommerce-payments' && !extension.is_activated);
        }
      }

      return ALLOWED_PLUGIN_CATEGORIES.includes(extensionBundle.key);
    });
  }, [freeExtensionBundleByCategory, productTypes, country]);
  Object(external_wp_element_["useEffect"])(() => {
    if (!isInstallingActivating) {
      setInstallExtensionOptions(() => createInstallExtensionOptions(installableExtensions));
    } // Disable reason: This effect should only called when the installableExtensions are changed.
    // eslint-disable-next-line react-hooks/exhaustive-deps

  }, [installableExtensions]);

  const getCheckboxChangeHandler = key => {
    return checked => {
      const newState = { ...installExtensionOptions,
        [key]: checked
      };
      const allExtensionsDisabled = Object.entries(newState).filter(_ref5 => {
        let [, val] = _ref5;
        return val;
      }).length === 1 && newState.install_extensions;

      if (allExtensionsDisabled) {
        // If all the extensions are disabled then disable the "Install Extensions" checkbox too
        setInstallExtensionOptions({ ...newState,
          install_extensions: false
        });
      } else {
        setInstallExtensionOptions({ ...installExtensionOptions,
          [key]: checked,
          install_extensions: true
        });
      }
    };
  };

  return Object(external_wp_element_["createElement"])("div", {
    className: "woocommerce-profile-wizard__business-details__free-features"
  }, Object(external_wp_element_["createElement"])(external_wp_components_["Card"], null, Object(external_wp_element_["createElement"])("div", {
    className: "woocommerce-profile-wizard__business-details__free-features__illustration"
  }, Object(external_wp_element_["createElement"])(AppIllustration, null)), Object(external_wp_element_["createElement"])("div", {
    className: "woocommerce-admin__business-details__selective-extensions-bundle"
  }, Object(external_wp_element_["createElement"])("div", {
    className: "woocommerce-admin__business-details__selective-extensions-bundle__extension"
  }, Object(external_wp_element_["createElement"])(external_wp_components_["CheckboxControl"], {
    checked: installExtensionOptions.install_extensions,
    onChange: checked => {
      setInstallExtensionOptions(setAllPropsToValue(installExtensionOptions, checked));
    }
  }), Object(external_wp_element_["createElement"])("p", {
    className: "woocommerce-admin__business-details__selective-extensions-bundle__description"
  }, Object(external_wp_i18n_["__"])('Add recommended business features to my site', 'woocommerce-admin')), Object(external_wp_element_["createElement"])(external_wp_components_["Button"], {
    className: "woocommerce-admin__business-details__selective-extensions-bundle__expand",
    disabled: !installableExtensions || installableExtensions.length === 0,
    onClick: () => {
      setShowExtensions(!showExtensions);

      if (!showExtensions) {
        // only record the accordion click when the accordion is opened.
        Object(external_wc_tracks_["recordEvent"])('storeprofiler_store_business_features_accordion_click');
      }
    }
  }, Object(external_wp_element_["createElement"])(icon["a" /* default */], {
    icon: showExtensions ? chevron_up["a" /* default */] : chevron_down["a" /* default */]
  }))), showExtensions && installableExtensions.map(_ref6 => {
    let {
      plugins,
      key,
      title
    } = _ref6;
    return Object(external_wp_element_["createElement"])(ExtensionSection, {
      key: key,
      title: title,
      extensions: plugins,
      installExtensionOptions: installExtensionOptions,
      onCheckboxChange: getCheckboxChangeHandler
    });
  })), Object(external_wp_element_["createElement"])("div", {
    className: "woocommerce-profile-wizard__business-details__free-features__action"
  }, Object(external_wp_element_["createElement"])(external_wp_components_["Button"], {
    onClick: () => {
      onSubmit(installExtensionOptions, installableExtensions);
    },
    isBusy: isInstallingActivating || isResolving,
    disabled: isInstallingActivating || isResolving,
    isPrimary: true
  }, Object(external_wp_i18n_["__"])('Continue', 'woocommerce-admin')))), renderBusinessExtensionHelpText(installExtensionOptions, isInstallingActivating));
};
// EXTERNAL MODULE: ./client/utils/index.js + 1 modules
var client_utils = __webpack_require__(547);

// EXTERNAL MODULE: ./client/profile-wizard/steps/business-details/flows/selective-bundle/style.scss
var selective_bundle_style = __webpack_require__(620);

// CONCATENATED MODULE: ./client/profile-wizard/steps/business-details/flows/selective-bundle/index.js



/**
 * External dependencies
 */










/**
 * Internal dependencies
 */











const BUSINESS_DETAILS_TAB_NAME = 'business-details';
const BUSINESS_FEATURES_TAB_NAME = 'business-features';
const filterBusinessExtensions = function (extensionInstallationOptions) {
  let alreadyActivatedExtensions = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : [];
  return Object.keys(extensionInstallationOptions).filter(key => extensionInstallationOptions[key] && key !== 'install_extensions' && !alreadyActivatedExtensions.includes(key)).map(client_utils["a" /* getPluginSlug */]) // remove duplicate
  .filter((item, index, arr) => arr.indexOf(item) === index);
};
const timeFrames = [{
  name: '0-2s',
  max: 2
}, {
  name: '2-5s',
  max: 5
}, {
  name: '5-10s',
  max: 10
}, {
  name: '10-15s',
  max: 15
}, {
  name: '15-20s',
  max: 20
}, {
  name: '20-30s',
  max: 30
}, {
  name: '30-60s',
  max: 60
}, {
  name: '>60s'
}];

function getTimeFrame(timeInMs) {
  for (const timeFrame of timeFrames) {
    if (!timeFrame.max) {
      return timeFrame.name;
    }

    if (timeInMs < timeFrame.max * 1000) {
      return timeFrame.name;
    }
  }
}

const prepareExtensionTrackingData = extensionInstallationOptions => {
  const installedExtensions = {};

  for (let [fieldKey, value] of Object.entries(extensionInstallationOptions)) {
    fieldKey = Object(client_utils["a" /* getPluginSlug */])(fieldKey);
    const key = Object(client_utils["b" /* getPluginTrackKey */])(fieldKey);

    if (fieldKey !== 'install_extensions' && !installedExtensions[`install_${key}`]) {
      installedExtensions[`install_${key}`] = value;
    }
  }

  return installedExtensions;
};
const prepareExtensionTrackingInstallationData = (extensionInstallationOptions, installationData) => {
  const installed = [];
  const data = {};

  for (let [fieldKey] of Object.entries(extensionInstallationOptions)) {
    fieldKey = Object(client_utils["a" /* getPluginSlug */])(fieldKey);
    const key = Object(client_utils["b" /* getPluginTrackKey */])(fieldKey);

    if (installationData && installationData.data && installationData.data.install_time && installationData.data.install_time[fieldKey]) {
      installed.push(key);
      data[`install_time_${key}`] = getTimeFrame(installationData.data.install_time[fieldKey]);
    }
  }

  data.installed_extensions = installed;
  data.activated_extensions = installationData && installationData.data && installationData.data.activated ? installationData.data.activated : [];
  return data;
};
const isSellingElsewhere = selectedOption => ['other', 'brick-mortar', 'brick-mortar-other', 'other-woocommerce'].includes(selectedOption);
const isSellingOtherPlatformInPerson = selectedOption => ['other', 'brick-mortar-other'].includes(selectedOption);

class selective_bundle_BusinessDetails extends external_wp_element_["Component"] {
  constructor(props) {
    super();
    this.state = {
      isPopoverVisible: false,
      isValid: false,
      currentTab: 'business-details',
      savedValues: null
    };
    this.onContinue = this.onContinue.bind(this);
    this.validate = this.validate.bind(this);
    props.trackStepValueChanges(props.step.key, { ...(this.state.savedValues || props.initialValues)
    }, this.savedValues || props.initialValues, this.persistProfileItems.bind(this));
  }

  async onContinue(extensionInstallationOptions, installableExtensionsData) {
    const {
      createNotice,
      goToNextStep,
      installAndActivatePlugins
    } = this.props;
    const alreadyActivatedExtensions = installableExtensionsData.reduce((actExtensions, bundle) => {
      const activated = bundle.plugins.filter(plugin => plugin.is_activated).map(plugin => plugin.key);
      return [...actExtensions, ...activated];
    }, []);
    const businessExtensions = filterBusinessExtensions(extensionInstallationOptions, alreadyActivatedExtensions);
    const installedExtensions = prepareExtensionTrackingData(extensionInstallationOptions);
    Object(external_wc_tracks_["recordEvent"])('storeprofiler_store_business_features_continue', {
      all_extensions_installed: Object.values(extensionInstallationOptions).every(val => val),
      ...installedExtensions
    });
    const promises = [this.persistProfileItems({
      business_extensions: businessExtensions
    })];

    if (businessExtensions.length) {
      const installationStartTime = window.performance.now();
      promises.push(installAndActivatePlugins(businessExtensions).then(response => {
        const totalInstallationTime = window.performance.now() - installationStartTime;
        const installedExtensionsData = prepareExtensionTrackingInstallationData(extensionInstallationOptions, response);
        Object(external_wc_tracks_["recordEvent"])('storeprofiler_store_business_features_installed_and_activated', {
          success: true,
          total_time: getTimeFrame(totalInstallationTime),
          ...installedExtensionsData
        });
        Object(notices["a" /* createNoticesFromResponse */])(response);
      }).catch(error => {
        Object(external_wc_tracks_["recordEvent"])('storeprofiler_store_business_features_installed_and_activated', {
          success: false,
          failed_extensions: Object.keys(error.data || {}).map(key => Object(client_utils["b" /* getPluginTrackKey */])(key))
        });
        Object(notices["a" /* createNoticesFromResponse */])(error);
        throw new Error();
      }));
    }

    Promise.all(promises).then(() => {
      goToNextStep({
        step: BUSINESS_FEATURES_TAB_NAME
      });
    }).catch(() => {
      createNotice('error', Object(external_wp_i18n_["__"])('There was a problem updating your business details', 'woocommerce-admin'));
    });
  }

  async persistProfileItems() {
    let additions = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
    const {
      updateProfileItems,
      createNotice
    } = this.props;
    const {
      number_employees: numberEmployees,
      other_platform: otherPlatform,
      other_platform_name: otherPlatformName,
      product_count: productCount,
      revenue,
      selling_venues: sellingVenues,
      setup_client: isSetupClient
    } = this.state.savedValues;
    const updates = {
      number_employees: numberEmployees,
      other_platform: otherPlatform,
      other_platform_name: otherPlatform === 'other' ? otherPlatformName : '',
      product_count: productCount,
      revenue,
      selling_venues: sellingVenues,
      setup_client: isSetupClient,
      ...additions
    }; // Remove possible empty values like `revenue` and `other_platform`.

    const finalUpdates = Object.entries(updates).reduce((acc, _ref) => {
      let [key, val] = _ref;

      if (val !== '') {
        acc[key] = val;
      }

      return acc;
    }, {});
    return updateProfileItems(finalUpdates).catch(() => {
      createNotice('error', Object(external_wp_i18n_["__"])('There was a problem updating your business details', 'woocommerce-admin'));
    });
  }

  validate(values) {
    const errors = {};

    if (!values.product_count.length) {
      errors.product_count = Object(external_wp_i18n_["__"])('This field is required', 'woocommerce-admin');
    }

    if (!values.selling_venues.length) {
      errors.selling_venues = Object(external_wp_i18n_["__"])('This field is required', 'woocommerce-admin');
    }

    if (!values.other_platform.length && isSellingOtherPlatformInPerson(values.selling_venues)) {
      errors.other_platform = Object(external_wp_i18n_["__"])('This field is required', 'woocommerce-admin');
    }

    if (!values.other_platform_name.trim().length && values.other_platform === 'other' && isSellingOtherPlatformInPerson(values.selling_venues)) {
      errors.other_platform_name = Object(external_wp_i18n_["__"])('This field is required', 'woocommerce-admin');
    }

    if (!values.number_employees.length && isSellingElsewhere(values.selling_venues)) {
      errors.number_employees = Object(external_wp_i18n_["__"])('This field is required', 'woocommerce-admin');
    }

    if (!values.revenue.length && isSellingElsewhere(values.selling_venues)) {
      errors.revenue = Object(external_wp_i18n_["__"])('This field is required', 'woocommerce-admin');
    }

    if (Object.keys(errors).length === 0) {
      this.setState({
        isValid: true
      });
    }

    return errors;
  }

  trackBusinessDetailsStep(_ref2) {
    let {
      number_employees: numberEmployees,
      other_platform: otherPlatform,
      other_platform_name: otherPlatformName,
      product_count: productCount,
      selling_venues: sellingVenues,
      revenue,
      setup_client: isSetupClient
    } = _ref2;
    const {
      getCurrencyConfig
    } = this.context;
    Object(external_wc_tracks_["recordEvent"])('storeprofiler_store_business_details_continue_variant', {
      number_employees: numberEmployees,
      already_selling: sellingVenues,
      currency: getCurrencyConfig().code,
      product_number: productCount,
      revenue,
      used_platform: otherPlatform,
      used_platform_name: otherPlatformName,
      setup_client: isSetupClient
    });
    Object(external_wc_tracks_["recordEvent"])('storeprofiler_step_complete', {
      step: BUSINESS_DETAILS_TAB_NAME,
      wc_version: Object(external_wc_wcSettings_["getSetting"])('wcVersion')
    });
  }

  getSelectControlProps(getInputProps) {
    let name = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : '';
    const {
      className,
      ...props
    } = getInputProps(name);
    return { ...props,
      className: classnames_default()(`woocommerce-profile-wizard__${name.replace(/\_/g, '-')}`, className)
    };
  }

  renderBusinessDetailsStep() {
    const {
      goToNextStep,
      isInstallingActivating,
      hasInstallActivateError
    } = this.props;
    const {
      formatAmount,
      getCurrencyConfig
    } = this.context;
    const productCountOptions = getProductCountOptions(getCurrencyConfig());
    return Object(external_wp_element_["createElement"])(external_wc_components_["Form"], {
      initialValues: this.state.savedValues || this.props.initialValues,
      onSubmit: values => {
        this.setState({
          savedValues: values,
          currentTab: BUSINESS_FEATURES_TAB_NAME
        });
        this.trackBusinessDetailsStep(values);
        Object(external_wc_tracks_["recordEvent"])('storeprofiler_step_view', {
          step: BUSINESS_FEATURES_TAB_NAME,
          wc_version: Object(external_wc_wcSettings_["getSetting"])('wcVersion')
        });
      },
      onChange: (_, values, isValid) => {
        this.setState({
          savedValues: values,
          isValid
        });
        this.props.updateCurrentStepValues(this.props.step.key, values);
      },
      validate: this.validate
    }, _ref3 => {
      let {
        getInputProps,
        handleSubmit,
        values,
        isValidForm
      } = _ref3;
      return Object(external_wp_element_["createElement"])(external_wp_element_["Fragment"], null, Object(external_wp_element_["createElement"])("div", {
        className: "woocommerce-profile-wizard__step-header"
      }, Object(external_wp_element_["createElement"])(external_wp_components_["__experimentalText"], {
        variant: "title.small",
        as: "h2",
        size: "20",
        lineHeight: "28px"
      }, Object(external_wp_i18n_["__"])('Tell us about your business', 'woocommerce-admin')), Object(external_wp_element_["createElement"])(external_wp_components_["__experimentalText"], {
        variant: "body",
        as: "p"
      }, Object(external_wp_i18n_["__"])("We'd love to know if you are just getting started or you already have a business in place.", 'woocommerce-admin'))), Object(external_wp_element_["createElement"])(external_wp_components_["Card"], null, Object(external_wp_element_["createElement"])(external_wp_components_["CardBody"], null, Object(external_wp_element_["createElement"])(external_wc_components_["SelectControl"], extends_default()({
        excludeSelectedOptions: false,
        label: Object(external_wp_i18n_["__"])('How many products do you plan to display?', 'woocommerce-admin'),
        options: productCountOptions,
        required: true
      }, this.getSelectControlProps(getInputProps, 'product_count'))), Object(external_wp_element_["createElement"])(external_wc_components_["SelectControl"], extends_default()({
        excludeSelectedOptions: false,
        label: Object(external_wp_i18n_["__"])('Currently selling elsewhere?', 'woocommerce-admin'),
        options: sellingVenueOptions,
        required: true
      }, this.getSelectControlProps(getInputProps, 'selling_venues'))), isSellingElsewhere(values.selling_venues) && Object(external_wp_element_["createElement"])(external_wc_components_["SelectControl"], extends_default()({
        excludeSelectedOptions: false,
        label: Object(external_wp_i18n_["__"])('How many employees do you have?', 'woocommerce-admin'),
        options: employeeOptions,
        required: true
      }, this.getSelectControlProps(getInputProps, 'number_employees'))), isSellingElsewhere(values.selling_venues) && Object(external_wp_element_["createElement"])(external_wc_components_["SelectControl"], extends_default()({
        excludeSelectedOptions: false,
        label: Object(external_wp_i18n_["__"])("What's your current annual revenue?", 'woocommerce-admin'),
        options: getRevenueOptions(getCurrencyConfig(), this.props.settings.woocommerce_default_country, formatAmount),
        required: true
      }, this.getSelectControlProps(getInputProps, 'revenue'))), isSellingOtherPlatformInPerson(values.selling_venues) && Object(external_wp_element_["createElement"])(external_wp_element_["Fragment"], null, Object(external_wp_element_["createElement"])("div", {
        className: "business-competitors"
      }, Object(external_wp_element_["createElement"])(external_wc_components_["SelectControl"], extends_default()({
        excludeSelectedOptions: false,
        label: Object(external_wp_i18n_["__"])('Which platform is the store using?', 'woocommerce-admin'),
        options: platformOptions,
        required: true
      }, this.getSelectControlProps(getInputProps, 'other_platform'))), values.other_platform === 'other' && Object(external_wp_element_["createElement"])(external_wc_components_["TextControl"], extends_default()({
        label: Object(external_wp_i18n_["__"])('What is the platform name?', 'woocommerce-admin'),
        required: true
      }, this.getSelectControlProps(getInputProps, 'other_platform_name')))))), Object(external_wp_element_["createElement"])(external_wp_components_["CardFooter"], {
        isBorderless: true
      }, Object(external_wp_element_["createElement"])(external_wp_components_["FlexItem"], null, Object(external_wp_element_["createElement"])("div", {
        className: "woocommerce-profile-wizard__client"
      }, Object(external_wp_element_["createElement"])(external_wp_components_["CheckboxControl"], extends_default()({
        label: Object(external_wp_i18n_["__"])("I'm setting up a store for a client", 'woocommerce-admin')
      }, getInputProps('setup_client')))))), Object(external_wp_element_["createElement"])(external_wp_components_["CardFooter"], {
        justify: "center"
      }, Object(external_wp_element_["createElement"])(external_wp_components_["Button"], {
        isPrimary: true,
        onClick: async () => {
          await handleSubmit();
          this.persistProfileItems();
        },
        disabled: !isValidForm,
        isBusy: isInstallingActivating
      }, !hasInstallActivateError ? Object(external_wp_i18n_["__"])('Continue', 'woocommerce-admin') : Object(external_wp_i18n_["__"])('Retry', 'woocommerce-admin')), hasInstallActivateError && Object(external_wp_element_["createElement"])(external_wp_components_["Button"], {
        onClick: () => {
          this.persistProfileItems();
          goToNextStep({
            step: BUSINESS_FEATURES_TAB_NAME
          });
        }
      }, Object(external_wp_i18n_["__"])('Continue without installing', 'woocommerce-admin')))));
    });
  }

  renderFreeFeaturesStep() {
    const {
      isInstallingActivating,
      settings,
      profileItems
    } = this.props;
    const country = settings.woocommerce_default_country ? settings.woocommerce_default_country : null;
    return Object(external_wp_element_["createElement"])(external_wp_element_["Fragment"], null, Object(external_wp_element_["createElement"])("div", {
      className: "woocommerce-profile-wizard__step-header"
    }, Object(external_wp_element_["createElement"])(external_wp_components_["__experimentalText"], {
      variant: "title.small",
      as: "h2",
      size: "20",
      lineHeight: "28px"
    }, Object(external_wp_i18n_["__"])('Included business features', 'woocommerce-admin')), Object(external_wp_element_["createElement"])(external_wp_components_["__experimentalText"], {
      variant: "body",
      as: "p"
    }, Object(external_wp_i18n_["__"])('We recommend enhancing your store with these free extensions', 'woocommerce-admin')), Object(external_wp_element_["createElement"])(external_wp_components_["__experimentalText"], {
      variant: "body",
      as: "p"
    }, Object(external_wp_i18n_["__"])('No commitment required - you can remove them at any time.', 'woocommerce-admin'))), Object(external_wp_element_["createElement"])(SelectiveExtensionsBundle, {
      isInstallingActivating: isInstallingActivating,
      onSubmit: this.onContinue,
      country: country,
      industry: profileItems.industry,
      productTypes: profileItems.product_types
    }));
  }

  render() {
    const {
      initialValues
    } = this.props; // There is a hack here to help us manage the selected tab programatically.
    // We set the tab name "current-tab". when its the one we want selected. This tricks
    // the logic in the TabPanel and allows us to switch which tab has the name "current-tab"
    // and force it to re-render with a different tab selected.

    return Object(external_wp_element_["createElement"])(external_wp_components_["TabPanel"], {
      activeClass: "is-active",
      initialTabName: "current-tab",
      onSelect: tabName => {
        if (this.state.currentTab !== tabName) {
          this.setState({
            currentTab: tabName,
            savedValues: this.state.savedValues || initialValues
          });
          Object(external_wc_tracks_["recordEvent"])('storeprofiler_step_view', {
            step: tabName,
            wc_version: Object(external_wc_wcSettings_["getSetting"])('wcVersion')
          });
        }
      },
      tabs: [{
        name: this.state.currentTab === BUSINESS_DETAILS_TAB_NAME ? 'current-tab' : BUSINESS_DETAILS_TAB_NAME,
        id: BUSINESS_DETAILS_TAB_NAME,
        title: Object(external_wp_i18n_["__"])('Business details', 'woocommerce-admin')
      }, {
        name: this.state.currentTab === BUSINESS_FEATURES_TAB_NAME ? 'current-tab' : BUSINESS_FEATURES_TAB_NAME,
        id: BUSINESS_FEATURES_TAB_NAME,
        title: Object(external_wp_i18n_["__"])('Free features', 'woocommerce-admin'),
        className: this.state.isValid ? '' : 'is-disabled'
      }]
    }, tab => Object(external_wp_element_["createElement"])(external_wp_element_["Fragment"], null, this.getTab(tab.id)));
  }

  getTab(tabId) {
    if (tabId === BUSINESS_DETAILS_TAB_NAME) {
      return this.renderBusinessDetailsStep();
    }

    return this.renderFreeFeaturesStep();
  }

}

selective_bundle_BusinessDetails.contextType = currency_context["a" /* CurrencyContext */];
const BusinessFeaturesList = Object(external_wp_compose_["compose"])(Object(external_wp_data_["withSelect"])(select => {
  const {
    getSettings,
    getSettingsError
  } = select(external_wc_data_["SETTINGS_STORE_NAME"]);
  const {
    getProfileItems,
    getOnboardingError
  } = select(external_wc_data_["ONBOARDING_STORE_NAME"]);
  const {
    getPluginsError,
    isPluginsRequesting
  } = select(external_wc_data_["PLUGINS_STORE_NAME"]);
  const {
    general: settings = {}
  } = getSettings('general');
  return {
    hasInstallActivateError: getPluginsError('installPlugins') || getPluginsError('activatePlugins'),
    isError: Boolean(getOnboardingError('updateProfileItems')),
    profileItems: getProfileItems(),
    isSettingsError: Boolean(getSettingsError('general')),
    settings,
    isInstallingActivating: isPluginsRequesting('installPlugins') || isPluginsRequesting('activatePlugins') || isPluginsRequesting('getJetpackConnectUrl')
  };
}), Object(external_wp_data_["withDispatch"])(dispatch => {
  const {
    updateProfileItems
  } = dispatch(external_wc_data_["ONBOARDING_STORE_NAME"]);
  const {
    installAndActivatePlugins
  } = dispatch(external_wc_data_["PLUGINS_STORE_NAME"]);
  const {
    createNotice
  } = dispatch('core/notices');
  return {
    createNotice,
    installAndActivatePlugins,
    updateProfileItems
  };
}))(selective_bundle_BusinessDetails);
// EXTERNAL MODULE: ./client/profile-wizard/steps/business-details/style.scss
var business_details_style = __webpack_require__(621);

// CONCATENATED MODULE: ./client/profile-wizard/steps/business-details/index.js



/**
 * External dependencies
 */



/**
 * Internal dependencies
 */



const BusinessDetailsStep = props => {
  const {
    profileItems,
    isLoading
  } = Object(external_wp_data_["useSelect"])(select => {
    return {
      isLoading: !select(external_wc_data_["ONBOARDING_STORE_NAME"]).hasFinishedResolution('getProfileItems') || !select(external_wc_data_["SETTINGS_STORE_NAME"]).hasFinishedResolution('getSettings', ['general']),
      profileItems: select(external_wc_data_["ONBOARDING_STORE_NAME"]).getProfileItems()
    };
  });

  if (isLoading) {
    return Object(external_wp_element_["createElement"])("div", {
      className: "woocommerce-admin__business-details__spinner"
    }, Object(external_wp_element_["createElement"])(external_wc_components_["Spinner"], null));
  }

  const initialValues = {
    number_employees: profileItems.number_employees || '',
    other_platform: profileItems.other_platform || '',
    other_platform_name: profileItems.other_platform_name || '',
    product_count: profileItems.product_count || '',
    selling_venues: profileItems.selling_venues || '',
    revenue: profileItems.revenue || '',
    setup_client: profileItems.setup_client || false
  };
  return Object(external_wp_element_["createElement"])(BusinessFeaturesList, extends_default()({}, props, {
    initialValues: initialValues
  }));
};
// EXTERNAL MODULE: ./client/utils/admin-settings.js
var admin_settings = __webpack_require__(23);

// CONCATENATED MODULE: ./client/profile-wizard/steps/industry.js


/**
 * External dependencies
 */










/**
 * Internal dependencies
 */



const onboarding = Object(admin_settings["d" /* getAdminSetting */])('onboarding', {});

class industry_Industry extends external_wp_element_["Component"] {
  constructor(props) {
    const profileItems = Object(external_lodash_["get"])(props, 'profileItems', {});
    let selected = profileItems.industry || [];
    /**
     * @todo Remove block on `updateProfileItems` refactor to wp.data dataStores.
     *
     * The following block is a side effect of wc-api not being truly async
     * and is a temporary fix until a refactor to wp.data can take place.
     *
     * Calls to `updateProfileItems` in the previous screen happen async
     * and won't be updated in wc-api's state when this component is initialized.
     * As such, we need to make sure cbd is not initialized as selected when a
     * user has changed location to non-US based.
     */

    const {
      locationSettings
    } = props;
    const region = Object(utils["c" /* getCurrencyRegion */])(locationSettings.woocommerce_default_country);

    if (region !== 'US') {
      const cbdSlug = 'cbd-other-hemp-derived-products';
      selected = selected.filter(industry => {
        return cbdSlug !== industry && cbdSlug !== industry.slug;
      });
    }
    /**
     * End block to be removed after refactor.
     */


    super();
    this.state = {
      error: null,
      selected,
      textInputListContent: {}
    };
    this.onContinue = this.onContinue.bind(this);
    this.onIndustryChange = this.onIndustryChange.bind(this);
    this.onDetailChange = this.onDetailChange.bind(this);
    const selectedSlugs = this.getSelectedSlugs();
    props.trackStepValueChanges(props.step.key, selectedSlugs, selectedSlugs, this.onContinue);
  }

  getSelectedSlugs() {
    return this.state.selected.map(industry => industry.slug);
  }

  componentDidUpdate() {
    this.props.updateCurrentStepValues(this.props.step.key, this.getSelectedSlugs());
  }

  async onContinue() {
    await this.validateField();

    if (this.state.error) {
      return;
    }

    const {
      createNotice,
      isError,
      updateProfileItems
    } = this.props;
    const selectedIndustriesList = this.state.selected.map(industry => industry.slug); // Here the selected industries are converted to a string that is a comma separated list

    const industriesWithDetail = this.state.selected.map(industry => industry.detail).filter(n => n).join(',');
    Object(external_wc_tracks_["recordEvent"])('storeprofiler_store_industry_continue', {
      store_industry: selectedIndustriesList,
      industries_with_detail: industriesWithDetail
    });
    await updateProfileItems({
      industry: this.state.selected
    });

    if (isError) {
      createNotice('error', Object(external_wp_i18n_["__"])('There was a problem updating your industries', 'woocommerce-admin'));
      return Promise.reject();
    }

    return true;
  }

  async validateField() {
    const error = this.state.selected.length ? null : Object(external_wp_i18n_["__"])('Please select at least one industry', 'woocommerce-admin');
    this.setState({
      error
    });
  }

  onIndustryChange(slug) {
    this.setState(state => {
      const newSelected = state.selected;
      const selectedIndustry = Object(external_lodash_["find"])(newSelected, {
        slug
      });

      if (selectedIndustry) {
        const newTextInputListContent = state.textInputListContent;
        newTextInputListContent[slug] = selectedIndustry.detail;
        return {
          selected: Object(external_lodash_["filter"])(state.selected, value => {
            return value.slug !== slug;
          }) || [],
          textInputListContent: newTextInputListContent
        };
      }

      newSelected.push({
        slug,
        detail: state.textInputListContent[slug]
      });
      return {
        selected: newSelected
      };
    }, () => this.validateField());
  }

  onDetailChange(value, slug) {
    this.setState(state => {
      const newSelected = state.selected;
      const newTextInputListContent = state.textInputListContent;
      const industryIndex = Object(external_lodash_["findIndex"])(newSelected, {
        slug
      });
      newSelected[industryIndex].detail = value;
      newTextInputListContent[slug] = value;
      return {
        selected: newSelected,
        textInputListContent: newTextInputListContent
      };
    });
  }

  renderIndustryLabel(slug, industry, selectedIndustry) {
    const {
      textInputListContent
    } = this.state;
    return Object(external_wp_element_["createElement"])(external_wp_element_["Fragment"], null, industry.label, industry.use_description && selectedIndustry && Object(external_wp_element_["createElement"])(external_wc_components_["TextControl"], {
      key: `text-control-${slug}`,
      label: industry.description_label,
      value: selectedIndustry.detail || textInputListContent[slug] || '',
      onChange: value => this.onDetailChange(value, slug),
      className: "woocommerce-profile-wizard__text"
    }));
  }

  render() {
    const {
      industries
    } = onboarding;
    const {
      error,
      selected
    } = this.state;
    const {
      locationSettings,
      isProfileItemsRequesting
    } = this.props;
    const region = Object(utils["c" /* getCurrencyRegion */])(locationSettings.woocommerce_default_country);
    const industryKeys = Object.keys(industries);
    const filteredIndustryKeys = region === 'US' ? industryKeys : industryKeys.filter(slug => slug !== 'cbd-other-hemp-derived-products');
    return Object(external_wp_element_["createElement"])(external_wp_element_["Fragment"], null, Object(external_wp_element_["createElement"])("div", {
      className: "woocommerce-profile-wizard__step-header"
    }, Object(external_wp_element_["createElement"])(external_wc_experimental_["Text"], {
      variant: "title.small",
      as: "h2",
      size: "20",
      lineHeight: "28px"
    }, Object(external_wp_i18n_["__"])('In which industry does the store operate?', 'woocommerce-admin')), Object(external_wp_element_["createElement"])(external_wc_experimental_["Text"], {
      variant: "body",
      as: "p"
    }, Object(external_wp_i18n_["__"])('Choose any that apply', 'woocommerce-admin'))), Object(external_wp_element_["createElement"])(external_wp_components_["Card"], null, Object(external_wp_element_["createElement"])(external_wp_components_["CardBody"], {
      size: null
    }, Object(external_wp_element_["createElement"])("div", {
      className: "woocommerce-profile-wizard__checkbox-group"
    }, filteredIndustryKeys.map(slug => {
      const selectedIndustry = Object(external_lodash_["find"])(selected, {
        slug
      });
      return Object(external_wp_element_["createElement"])(external_wp_components_["CheckboxControl"], {
        key: `checkbox-control-${slug}`,
        label: this.renderIndustryLabel(slug, industries[slug], selectedIndustry),
        onChange: () => this.onIndustryChange(slug),
        checked: selectedIndustry || false,
        className: "woocommerce-profile-wizard__checkbox"
      });
    }), error && Object(external_wp_element_["createElement"])("span", {
      className: "woocommerce-profile-wizard__error"
    }, error))), Object(external_wp_element_["createElement"])(external_wp_components_["CardFooter"], {
      isBorderless: true,
      justify: "center"
    }, Object(external_wp_element_["createElement"])(external_wp_components_["Button"], {
      isPrimary: true,
      onClick: () => {
        this.onContinue().then(this.props.goToNextStep);
      },
      isBusy: isProfileItemsRequesting,
      disabled: !selected.length || isProfileItemsRequesting
    }, Object(external_wp_i18n_["__"])('Continue', 'woocommerce-admin')))));
  }

}

/* harmony default export */ var steps_industry = (Object(external_wp_compose_["compose"])(Object(external_wp_data_["withSelect"])(select => {
  const {
    getProfileItems,
    getOnboardingError,
    isOnboardingRequesting
  } = select(external_wc_data_["ONBOARDING_STORE_NAME"]);
  const {
    getSettings
  } = select(external_wc_data_["SETTINGS_STORE_NAME"]);
  const {
    general: locationSettings = {}
  } = getSettings('general');
  return {
    isError: Boolean(getOnboardingError('updateProfileItems')),
    profileItems: getProfileItems(),
    locationSettings,
    isProfileItemsRequesting: isOnboardingRequesting('updateProfileItems')
  };
}), Object(external_wp_data_["withDispatch"])(dispatch => {
  const {
    updateProfileItems
  } = dispatch(external_wc_data_["ONBOARDING_STORE_NAME"]);
  const {
    createNotice
  } = dispatch('core/notices');
  return {
    createNotice,
    updateProfileItems
  };
}))(industry_Industry));
// EXTERNAL MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/@wordpress+icons@6.3.0/node_modules/@wordpress/icons/build-module/library/info.js
var info = __webpack_require__(506);

// CONCATENATED MODULE: ./client/profile-wizard/steps/product-types/label.js


/**
 * External dependencies
 */







function ProductTypeLabel(_ref) {
  let {
    annualPrice,
    description,
    isMonthlyPricing,
    label,
    moreUrl,
    slug
  } = _ref;
  const [isPopoverVisible, setIsPopoverVisible] = Object(external_wp_element_["useState"])('');

  if (!annualPrice) {
    return label;
  }
  /* eslint-disable @wordpress/i18n-no-collapsible-whitespace */


  const toolTipText = Object(external_wp_i18n_["__"])("This product type requires a paid extension.\nWe'll add this to a cart so that\nyou can purchase and install it later.", 'woocommerce-admin');
  /* eslint-enable @wordpress/i18n-no-collapsible-whitespace */


  return Object(external_wp_element_["createElement"])(external_wp_element_["Fragment"], null, Object(external_wp_element_["createElement"])("span", {
    className: "woocommerce-product-wizard__product-types-label"
  }, label), Object(external_wp_element_["createElement"])(external_wp_components_["Button"], {
    isTertiary: true,
    label: Object(external_wp_i18n_["__"])('Learn more about recommended free business features', 'woocommerce-admin'),
    onClick: () => {
      setIsPopoverVisible(true);
    }
  }, Object(external_wp_element_["createElement"])(icon["a" /* default */], {
    icon: info["a" /* default */]
  })), isPopoverVisible && Object(external_wp_element_["createElement"])(external_wp_components_["Popover"], {
    focusOnMount: "container",
    position: "top center",
    onClose: () => setIsPopoverVisible(false)
  }, Object(esm["a" /* default */])({
    mixedString: description + (moreUrl ? ' {{moreLink/}}' : ''),
    components: {
      moreLink: moreUrl ? Object(external_wp_element_["createElement"])(external_wc_components_["Link"], {
        href: moreUrl,
        target: "_blank",
        type: "external",
        onClick: () => Object(external_wc_tracks_["recordEvent"])('storeprofiler_store_product_type_learn_more', {
          product_type: slug
        })
      }, Object(external_wp_i18n_["__"])('Learn more', 'woocommerce-admin')) : ''
    }
  })), Object(external_wp_element_["createElement"])(external_wp_components_["Tooltip"], {
    text: toolTipText,
    position: "bottom center"
  }, Object(external_wp_element_["createElement"])(external_wc_components_["Pill"], null, Object(external_wp_element_["createElement"])("span", {
    className: "screen-reader-text"
  }, toolTipText), isMonthlyPricing ? Object(external_wp_i18n_["sprintf"])(
  /* translators: Dollar amount (example: $4.08 ) */
  Object(external_wp_i18n_["__"])('$%f per month', 'woocommerce-admin'), (annualPrice / 12.0).toFixed(2)) : Object(external_wp_i18n_["sprintf"])(
  /* translators: Dollar amount (example: $49.00 ) */
  Object(external_wp_i18n_["__"])('$%f per year', 'woocommerce-admin'), annualPrice))));
}
// EXTERNAL MODULE: ./client/profile-wizard/steps/product-types/style.scss
var product_types_style = __webpack_require__(622);

// CONCATENATED MODULE: ./client/profile-wizard/steps/product-types/index.js


/**
 * External dependencies
 */









/**
 * Internal dependencies
 */





class product_types_ProductTypes extends external_wp_element_["Component"] {
  constructor() {
    super();
    this.state = {
      error: null,
      isMonthlyPricing: true,
      selected: [],
      isWCPayInstalled: null
    };
    this.onContinue = this.onContinue.bind(this);
    this.onChange = this.onChange.bind(this);
  }

  componentDidMount() {
    const {
      installedPlugins,
      invalidateResolution
    } = this.props;
    const {
      isWCPayInstalled
    } = this.state;
    invalidateResolution('getProductTypes', []);

    if (isWCPayInstalled === null && installedPlugins) {
      this.setState({
        isWCPayInstalled: installedPlugins.includes('woocommerce-payments')
      });
    }
  }

  componentDidUpdate(prevProps, prevState) {
    const {
      profileItems,
      productTypes
    } = this.props;

    if (this.state.selected !== prevState.selected) {
      this.props.updateCurrentStepValues(this.props.step.key, this.state.selected);
    }

    if (prevProps.productTypes !== productTypes) {
      const defaultProductTypes = Object.keys(productTypes).filter(key => !!productTypes[key].default);
      this.setState({
        selected: profileItems.product_types || defaultProductTypes
      }, () => {
        this.props.trackStepValueChanges(this.props.step.key, [...this.state.selected], this.state.selected, this.onContinue);
      });
    }
  }

  validateField() {
    const error = this.state.selected.length ? null : Object(external_wp_i18n_["__"])('Please select at least one product type', 'woocommerce-admin');
    this.setState({
      error
    });
    return !error;
  }

  onContinue(onSuccess) {
    const {
      selected
    } = this.state;
    const {
      installedPlugins = []
    } = this.props;

    if (!this.validateField()) {
      return;
    }

    const {
      countryCode,
      createNotice,
      installAndActivatePlugins,
      updateProfileItems,
      productTypes
    } = this.props;
    const eventProps = {
      product_type: selected,
      wcpay_installed: false
    };
    const promises = [updateProfileItems({
      product_types: selected
    })];

    if (window.wcAdminFeatures && window.wcAdminFeatures.subscriptions && countryCode === 'US' && productTypes.subscriptions && !productTypes.subscriptions.yearly_price && !installedPlugins.includes('woocommerce-payments') && selected.includes('subscriptions')) {
      promises.push(installAndActivatePlugins(['woocommerce-payments']).then(response => {
        eventProps.wcpay_installed = true;

        if (response.data && response.data.install_time && response.data.install_time['woocommerce-payments']) {
          eventProps.install_time_wcpay = response.data.install_time['woocommerce-payments'];
        }

        Object(notices["a" /* createNoticesFromResponse */])(response);
      }).catch(error => {
        Object(notices["a" /* createNoticesFromResponse */])(error);
        throw new Error();
      }));
    }

    Promise.all(promises).then(() => {
      Object(external_wc_tracks_["recordEvent"])('storeprofiler_store_product_type_continue', eventProps);

      if (typeof onSuccess === 'function') {
        onSuccess();
      }
    }).catch(() => createNotice('error', Object(external_wp_i18n_["__"])('There was a problem updating your product types', 'woocommerce-admin')));
  }

  onChange(slug) {
    this.setState(state => {
      if (Object(external_lodash_["includes"])(state.selected, slug)) {
        return {
          selected: Object(external_lodash_["filter"])(state.selected, value => {
            return value !== slug;
          }) || []
        };
      }

      const newSelected = state.selected;
      newSelected.push(slug);
      return {
        selected: newSelected
      };
    }, () => this.validateField());
  }

  render() {
    const {
      productTypes = []
    } = this.props;
    const {
      error,
      isMonthlyPricing,
      isWCPayInstalled,
      selected
    } = this.state;
    const {
      countryCode,
      isInstallingActivating,
      isProductTypesRequesting,
      isProfileItemsRequesting
    } = this.props;

    if (isProductTypesRequesting) {
      return Object(external_wp_element_["createElement"])("div", {
        className: "woocommerce-profile-wizard__product-types__spinner"
      }, Object(external_wp_element_["createElement"])(external_wp_components_["Spinner"], null));
    }

    return Object(external_wp_element_["createElement"])("div", {
      className: "woocommerce-profile-wizard__product-types"
    }, Object(external_wp_element_["createElement"])("div", {
      className: "woocommerce-profile-wizard__step-header"
    }, Object(external_wp_element_["createElement"])(external_wc_experimental_["Text"], {
      variant: "title.small",
      as: "h2",
      size: "20",
      lineHeight: "28px"
    }, Object(external_wp_i18n_["__"])('What type of products will be listed?', 'woocommerce-admin')), Object(external_wp_element_["createElement"])(external_wc_experimental_["Text"], {
      variant: "body",
      as: "p"
    }, Object(external_wp_i18n_["__"])('Choose any that apply', 'woocommerce-admin'))), Object(external_wp_element_["createElement"])(external_wp_components_["Card"], null, Object(external_wp_element_["createElement"])(external_wp_components_["CardBody"], {
      size: null
    }, Object.keys(productTypes).map(slug => {
      return Object(external_wp_element_["createElement"])(external_wp_components_["CheckboxControl"], {
        key: slug,
        label: Object(external_wp_element_["createElement"])(ProductTypeLabel, {
          description: productTypes[slug].description,
          label: productTypes[slug].label,
          annualPrice: productTypes[slug].yearly_price,
          isMonthlyPricing: isMonthlyPricing,
          moreUrl: productTypes[slug].more_url,
          slug: slug
        }),
        onChange: () => this.onChange(slug),
        checked: selected.includes(slug),
        className: "woocommerce-profile-wizard__checkbox"
      });
    }), error && Object(external_wp_element_["createElement"])("span", {
      className: "woocommerce-profile-wizard__error"
    }, error)), Object(external_wp_element_["createElement"])(external_wp_components_["CardFooter"], {
      isBorderless: true,
      justify: "center"
    }, Object(external_wp_element_["createElement"])(external_wp_components_["Button"], {
      isPrimary: true,
      onClick: () => {
        this.onContinue(this.props.goToNextStep);
      },
      isBusy: isProfileItemsRequesting || isInstallingActivating,
      disabled: !selected.length || isProfileItemsRequesting || isInstallingActivating
    }, Object(external_wp_i18n_["__"])('Continue', 'woocommerce-admin')))), Object(external_wp_element_["createElement"])("div", {
      className: "woocommerce-profile-wizard__card-help-footnote"
    }, Object(external_wp_element_["createElement"])("div", {
      className: "woocommerce-profile-wizard__product-types-pricing-toggle woocommerce-profile-wizard__checkbox"
    }, Object(external_wp_element_["createElement"])("label", {
      htmlFor: "woocommerce-product-types__pricing-toggle"
    }, Object(external_wp_element_["createElement"])(external_wc_experimental_["Text"], {
      variant: "body",
      as: "p"
    }, Object(external_wp_i18n_["__"])('Display monthly prices', 'woocommerce-admin')), Object(external_wp_element_["createElement"])(external_wp_components_["FormToggle"], {
      id: "woocommerce-product-types__pricing-toggle",
      checked: isMonthlyPricing,
      onChange: () => this.setState({
        isMonthlyPricing: !isMonthlyPricing
      })
    }))), Object(external_wp_element_["createElement"])(external_wc_experimental_["Text"], {
      variant: "caption",
      size: "12",
      lineHeight: "16px"
    }, Object(external_wp_i18n_["__"])('Billing is annual. All purchases are covered by our 30 day money back guarantee and include access to support and updates. Extensions will be added to a cart for you to purchase later.', 'woocommerce-admin')), window.wcAdminFeatures && window.wcAdminFeatures.subscriptions && countryCode === 'US' && !isWCPayInstalled && productTypes.subscriptions && !productTypes.subscriptions.yearly_price && selected.includes('subscriptions') && Object(external_wp_element_["createElement"])(external_wc_experimental_["Text"], {
      variant: "body",
      size: "12",
      lineHeight: "16px",
      as: "p"
    }, Object(external_wp_i18n_["__"])('The following extensions will be added to your site for free: WooCommerce Payments. An account is required to use this feature.', 'woocommerce-admin'))));
  }

}
/* harmony default export */ var product_types = (Object(external_wp_compose_["compose"])(Object(external_wp_data_["withSelect"])(select => {
  const {
    getProfileItems,
    getProductTypes,
    getOnboardingError,
    hasFinishedResolution,
    isOnboardingRequesting
  } = select(external_wc_data_["ONBOARDING_STORE_NAME"]);
  const {
    getSettings
  } = select(external_wc_data_["SETTINGS_STORE_NAME"]);
  const {
    getInstalledPlugins,
    isPluginsRequesting
  } = select(external_wc_data_["PLUGINS_STORE_NAME"]);
  const {
    general: settings = {}
  } = getSettings('general');
  return {
    isError: Boolean(getOnboardingError('updateProfileItems')),
    profileItems: getProfileItems(),
    isProfileItemsRequesting: isOnboardingRequesting('updateProfileItems'),
    installedPlugins: getInstalledPlugins(),
    isInstallingActivating: isPluginsRequesting('installPlugins') || isPluginsRequesting('activatePlugins'),
    countryCode: Object(utils["b" /* getCountryCode */])(settings.woocommerce_default_country),
    productTypes: getProductTypes(),
    isProductTypesRequesting: !hasFinishedResolution('getProductTypes')
  };
}), Object(external_wp_data_["withDispatch"])(dispatch => {
  const {
    updateProfileItems
  } = dispatch(external_wc_data_["ONBOARDING_STORE_NAME"]);
  const {
    createNotice
  } = dispatch('core/notices');
  const {
    installAndActivatePlugins
  } = dispatch(external_wc_data_["PLUGINS_STORE_NAME"]);
  const {
    invalidateResolution
  } = dispatch(external_wc_data_["ONBOARDING_STORE_NAME"]);
  return {
    createNotice,
    installAndActivatePlugins,
    invalidateResolution,
    updateProfileItems
  };
}))(product_types_ProductTypes));
// CONCATENATED MODULE: ./client/profile-wizard/unsaved-changes-modal.js


/**
 * External dependencies
 */



const UnsavedChangesModal = _ref => {
  let {
    onClose,
    onSave
  } = _ref;

  const title = Object(external_wp_i18n_["__"])('Save changes?', 'woocommerce-admin');

  const message = Object(external_wp_i18n_["__"])("You're about to go to a different step. Do you want to save the changes you've made here so far?", 'woocommerce-admin');

  const discardText = Object(external_wp_i18n_["__"])('Discard', 'woocommerce-admin');

  const saveText = Object(external_wp_i18n_["__"])('Save', 'woocommerce-admin');

  return Object(external_wp_element_["createElement"])(external_wp_element_["Fragment"], null, Object(external_wp_element_["createElement"])(external_wp_components_["Modal"], {
    title: title,
    className: "woocommerce-obw-unsaved-changes",
    onRequestClose: onClose
  }, Object(external_wp_element_["createElement"])("div", {
    className: "woocommerce-obw-unsaved-changes-modal__wrapper"
  }, Object(external_wp_element_["createElement"])("div", {
    className: "woocommerce-usage-modal__message"
  }, message), Object(external_wp_element_["createElement"])("div", {
    className: "woocommerce-usage-modal__actions"
  }, Object(external_wp_element_["createElement"])(external_wp_components_["Button"], {
    onClick: () => onClose()
  }, discardText), Object(external_wp_element_["createElement"])(external_wp_components_["Button"], {
    isPrimary: true,
    onClick: onSave
  }, saveText)))));
};

/* harmony default export */ var unsaved_changes_modal = (UnsavedChangesModal);
// CONCATENATED MODULE: ./client/profile-wizard/header.js


/**
 * External dependencies
 */




/**
 * Internal dependencies
 */


class header_ProfileWizardHeader extends external_wp_element_["Component"] {
  constructor(props) {
    super(props);
    this.state = {
      showUnsavedChangesModal: false
    };
    this.lastClickedStepKey = null;
  }

  shouldWarnForUnsavedChanges(step) {
    if (typeof this.props.stepValueChanges[step] !== 'undefined') {
      const initialValues = this.props.stepValueChanges[step].initialValues;
      const currentValues = this.props.stepValueChanges[step].currentValues;

      if (Array.isArray(initialValues) && Array.isArray(currentValues)) {
        initialValues.sort();
        currentValues.sort();
      }

      return !Object(external_lodash_["isEqual"])(initialValues, currentValues);
    }

    return false;
  }

  findCurrentStep() {
    return this.props.steps.find(s => s.key === this.props.currentStep);
  }

  moveToLastClickedStep() {
    if (this.lastClickedStepKey) {
      Object(external_wc_navigation_["updateQueryString"])({
        step: this.lastClickedStepKey
      });
      this.lastClickedStepKey = null;
    }
  }

  saveCurrentStepChanges() {
    const currentStep = this.findCurrentStep();

    if (!currentStep) {
      return null;
    }

    const stepValueChanges = this.props.stepValueChanges[currentStep.key];

    if (typeof stepValueChanges.onSave === 'function') {
      stepValueChanges.onSave();
    }
  }

  renderStepper() {
    const {
      currentStep,
      steps
    } = this.props;
    const visibleSteps = Object(external_lodash_["filter"])(steps, step => !!step.label);
    const currentStepIndex = visibleSteps.findIndex(step => step.key === currentStep);
    visibleSteps.map((step, index) => {
      const previousStep = visibleSteps[index - 1];

      if (index < currentStepIndex) {
        step.isComplete = true;
      }

      if (!previousStep || previousStep.isComplete) {
        step.onClick = key => {
          if (this.shouldWarnForUnsavedChanges(currentStep)) {
            this.setState({
              showUnsavedChangesModal: true
            });
            this.lastClickedStepKey = key;
          } else {
            Object(external_wc_navigation_["updateQueryString"])({
              step: key
            });
          }
        };
      }

      return step;
    });
    return Object(external_wp_element_["createElement"])(external_wc_components_["Stepper"], {
      steps: visibleSteps,
      currentStep: currentStep
    });
  }

  render() {
    const currentStep = this.findCurrentStep();

    if (!currentStep || !currentStep.label) {
      return null;
    }

    return Object(external_wp_element_["createElement"])("div", {
      className: "woocommerce-profile-wizard__header"
    }, this.state.showUnsavedChangesModal && Object(external_wp_element_["createElement"])(unsaved_changes_modal, {
      onClose: () => {
        this.setState({
          showUnsavedChangesModal: false
        });
        this.moveToLastClickedStep();
      },
      onSave: () => {
        this.saveCurrentStepChanges();
        this.setState({
          showUnsavedChangesModal: false
        });
        this.moveToLastClickedStep();
      }
    }), this.renderStepper());
  }

}
// EXTERNAL MODULE: external ["wp","url"]
var external_wp_url_ = __webpack_require__(16);

// EXTERNAL MODULE: ./client/dashboard/components/settings/general/store-address.tsx
var store_address = __webpack_require__(561);

// EXTERNAL MODULE: ./client/profile-wizard/steps/usage-modal.js
var usage_modal = __webpack_require__(549);

// EXTERNAL MODULE: ./client/profile-wizard/steps/store-details/style.scss
var store_details_style = __webpack_require__(623);

// CONCATENATED MODULE: ./client/profile-wizard/steps/store-details/index.js



/**
 * External dependencies
 */











/**
 * Internal dependencies
 */






 // FlexItem is not available until WP version 5.5. This code is safe to remove
// once the minimum WP supported version becomes 5.5.

const FlextItemSubstitute = _ref => {
  let {
    children,
    align
  } = _ref;
  const style = {
    display: 'flex',
    'justify-content': align ? 'center' : 'flex-start'
  };
  return Object(external_wp_element_["createElement"])("div", {
    style: style
  }, children);
};

const FlexItem = external_wp_components_["FlexItem"] || FlextItemSubstitute;

const LoadingPlaceholder = () => Object(external_wp_element_["createElement"])("div", {
  className: "woocommerce-admin__store-details__spinner"
}, Object(external_wp_element_["createElement"])(external_wp_components_["Spinner"], null));

class store_details_StoreDetails extends external_wp_element_["Component"] {
  constructor(props) {
    super(props);
    this.state = {
      showUsageModal: false,
      skipping: false,
      isStoreDetailsPopoverVisible: false,
      isSkipSetupPopoverVisible: false
    };
    this.onContinue = this.onContinue.bind(this);
    this.onSubmit = this.onSubmit.bind(this);
    this.validateStoreDetails = this.validateStoreDetails.bind(this);
    this.onFormValueChange = this.onFormValueChange.bind(this);
    this.changedFormValues = {};
  }

  componentDidUpdate() {
    if (this.props.isLoading === false && Object.keys(this.changedFormValues).length === 0) {
      // Make a copy of the initialValues.
      // The values in this object gets updated on onFormValueChange.
      this.changedFormValues = { ...this.props.initialValues
      };
      this.props.trackStepValueChanges(this.props.step.key, this.props.initialValues, this.changedFormValues, () => {
        this.onContinue(this.changedFormValues);
      });
    }
  }

  deriveCurrencySettings(countryState) {
    if (!countryState) {
      return null;
    }

    const Currency = this.context;
    const country = Object(utils["b" /* getCountryCode */])(countryState);
    const {
      currencySymbols = {},
      localeInfo = {}
    } = Object(admin_settings["d" /* getAdminSetting */])('onboarding', {});
    return Currency.getDataForCountry(country, localeInfo, currencySymbols);
  }

  onSubmit() {
    this.setState({
      showUsageModal: true,
      skipping: false
    });
  }

  onFormValueChange(changedFormValue) {
    this.changedFormValues[changedFormValue.name] = changedFormValue.value;
  }

  async onContinue(values) {
    const {
      createNotice,
      updateProfileItems,
      updateAndPersistSettingsForGroup,
      profileItems,
      settings,
      errorsRef
    } = this.props;
    const currencySettings = this.deriveCurrencySettings(values.countryState);
    const Currency = this.context;
    Currency.setCurrency(currencySettings);
    Object(external_wc_tracks_["recordEvent"])('storeprofiler_store_details_continue', {
      store_country: Object(utils["b" /* getCountryCode */])(values.countryState),
      derived_currency: currencySettings.code,
      email_signup: values.isAgreeMarketing
    });
    await updateAndPersistSettingsForGroup('general', {
      general: { ...settings,
        woocommerce_store_address: values.addressLine1,
        woocommerce_store_address_2: values.addressLine2,
        woocommerce_default_country: values.countryState,
        woocommerce_store_city: values.city,
        woocommerce_store_postcode: values.postCode,
        woocommerce_currency: currencySettings.code,
        woocommerce_currency_pos: currencySettings.symbolPosition,
        woocommerce_price_thousand_sep: currencySettings.thousandSeparator,
        woocommerce_price_decimal_sep: currencySettings.decimalSeparator,
        woocommerce_price_num_decimals: currencySettings.precision
      }
    });
    const profileItemsToUpdate = {
      is_agree_marketing: values.isAgreeMarketing,
      store_email: values.storeEmail
    };
    const region = Object(utils["c" /* getCurrencyRegion */])(values.countryState);
    /**
     * If a user has already selected cdb industry and returns to change to a
     * non US store, remove cbd industry.
     *
     * NOTE: the following call to `updateProfileItems` does not respect the
     * `await` and performs an update aysnchronously. This means the following
     * screen may not be initialized with correct profile settings.
     *
     * This comment may be removed when a refactor to wp.data datatores is complete.
     */

    if (region !== 'US' && profileItems.industry && profileItems.industry.length) {
      const cbdSlug = 'cbd-other-hemp-derived-products';
      const trimmedIndustries = profileItems.industry.filter(industry => {
        return cbdSlug !== industry && cbdSlug !== industry.slug;
      });
      profileItemsToUpdate.industry = trimmedIndustries;
    }

    let errorMessages = [];

    try {
      await updateProfileItems(profileItemsToUpdate);
    } catch (error) {
      var _error$data;

      // Array of error messages obtained from API response.
      if (error !== null && error !== void 0 && (_error$data = error.data) !== null && _error$data !== void 0 && _error$data.params) {
        errorMessages = Object.values(error.data.params);
      }
    }

    if (!Boolean(errorsRef.current.settings) && !errorMessages.length) {
      return true;
    }

    createNotice('error', Object(external_wp_i18n_["__"])('There was a problem saving your store details', 'woocommerce-admin'));
    errorMessages.forEach(message => createNotice('error', message));
  }

  validateStoreDetails(values) {
    const {
      getLocale
    } = this.props;
    const locale = getLocale(values.countryState);
    const validateAddress = Object(store_address["b" /* getStoreAddressValidator */])(locale);
    const errors = validateAddress(values);

    if (values.storeEmail && !Object(external_wp_url_["isEmail"])(values.storeEmail)) {
      errors.storeEmail = Object(external_wp_i18n_["__"])('Invalid email address', 'woocommerce-admin');
    }

    return errors;
  }

  render() {
    const {
      showUsageModal,
      skipping,
      isStoreDetailsPopoverVisible,
      isSkipSetupPopoverVisible
    } = this.state;
    const {
      skipProfiler,
      isLoading,
      isBusy,
      initialValues,
      invalidateResolutionForStoreSelector
    } = this.props;
    /* eslint-disable @wordpress/i18n-no-collapsible-whitespace */

    const skipSetupText = Object(external_wp_i18n_["__"])('Manual setup is only recommended for\n experienced WooCommerce users or developers.', 'woocommerce-admin');

    const configureCurrencyText = Object(external_wp_i18n_["__"])('Your store address will help us configure currency\n options and shipping rules automatically.\n This information will not be publicly visible and can\n easily be changed later.', 'woocommerce-admin');
    /* eslint-enable @wordpress/i18n-no-collapsible-whitespace */


    if (isLoading) {
      return Object(external_wp_element_["createElement"])("div", {
        className: "woocommerce-profile-wizard__store-details"
      }, Object(external_wp_element_["createElement"])(LoadingPlaceholder, null));
    }

    return Object(external_wp_element_["createElement"])("div", {
      className: "woocommerce-profile-wizard__store-details"
    }, Object(external_wp_element_["createElement"])("div", {
      className: "woocommerce-profile-wizard__step-header"
    }, Object(external_wp_element_["createElement"])(external_wc_experimental_["Text"], {
      variant: "title.small",
      as: "h2",
      size: "20",
      lineHeight: "28px"
    }, Object(external_wp_i18n_["__"])('Welcome to WooCommerce', 'woocommerce-admin')), Object(external_wp_element_["createElement"])(external_wc_experimental_["Text"], {
      variant: "body",
      as: "p"
    }, Object(external_wp_i18n_["__"])("Tell us about your store and we'll get you set up in no time", 'woocommerce-admin'), Object(external_wp_element_["createElement"])(external_wp_components_["Button"], {
      isTertiary: true,
      label: Object(external_wp_i18n_["__"])('Learn more about store details', 'woocommerce-admin'),
      onClick: () => this.setState({
        isStoreDetailsPopoverVisible: true
      })
    }, Object(external_wp_element_["createElement"])(icon["a" /* default */], {
      icon: info["a" /* default */]
    }))), isStoreDetailsPopoverVisible && Object(external_wp_element_["createElement"])(external_wp_components_["Popover"], {
      focusOnMount: "container",
      position: "top center",
      onClose: () => this.setState({
        isStoreDetailsPopoverVisible: false
      })
    }, configureCurrencyText)), Object(external_wp_element_["createElement"])(external_wc_components_["Form"], {
      initialValues: initialValues,
      onSubmit: this.onSubmit,
      validate: this.validateStoreDetails,
      onChange: this.onFormValueChange
    }, _ref2 => {
      let {
        getInputProps,
        handleSubmit,
        values,
        isValidForm,
        setValue
      } = _ref2;
      return Object(external_wp_element_["createElement"])(external_wp_components_["Card"], null, showUsageModal && Object(external_wp_element_["createElement"])(usage_modal["a" /* default */], {
        onContinue: () => {
          if (skipping) {
            skipProfiler();
          } else {
            this.onContinue(values).then(() => this.props.goToNextStep());
          }
        },
        onClose: () => this.setState({
          showUsageModal: false,
          skipping: false
        })
      }), Object(external_wp_element_["createElement"])(external_wp_components_["CardBody"], null, Object(external_wp_element_["createElement"])(store_address["a" /* StoreAddress */], {
        getInputProps: getInputProps,
        setValue: setValue
      }), Object(external_wp_element_["createElement"])(external_wc_components_["TextControl"], extends_default()({
        label: values.isAgreeMarketing ? Object(external_wp_i18n_["__"])('Email address', 'woocommerce-admin') : Object(external_wp_i18n_["__"])('Email address (Optional)', 'woocommerce-admin'),
        required: values.isAgreeMarketing,
        autoComplete: "email"
      }, getInputProps('storeEmail'))), values.isAgreeMarketing && (!values.storeEmail || !values.storeEmail.trim().length) && Object(external_wp_element_["createElement"])("div", {
        className: "woocommerce-profile-wizard__store-details-error"
      }, Object(external_wp_i18n_["__"])('Please enter your email address to subscribe', 'woocommerce-admin')), Object(external_wp_element_["createElement"])(FlexItem, null, Object(external_wp_element_["createElement"])("div", {
        className: "woocommerce-profile-wizard__newsletter-signup"
      }, Object(external_wp_element_["createElement"])(external_wp_components_["CheckboxControl"], extends_default()({
        label: Object(external_wp_element_["createElement"])(external_wp_element_["Fragment"], null, Object(external_wp_i18n_["__"])('Get tips, product updates and inspiration straight to your mailbox.', 'woocommerce-admin'), ' ', Object(external_wp_element_["createElement"])("span", {
          className: "woocommerce-profile-wizard__powered-by-mailchimp"
        }, Object(external_wp_i18n_["__"])('Powered by Mailchimp', 'woocommerce-admin')))
      }, getInputProps('isAgreeMarketing')))))), Object(external_wp_element_["createElement"])(external_wp_components_["CardFooter"], {
        justify: "center"
      }, Object(external_wp_element_["createElement"])(external_wp_components_["Button"], {
        isPrimary: true,
        onClick: handleSubmit,
        isBusy: isBusy,
        disabled: !isValidForm || isBusy
      }, Object(external_wp_i18n_["__"])('Continue', 'woocommerce-admin'))));
    }), Object(external_wp_element_["createElement"])("div", {
      className: "woocommerce-profile-wizard__footer"
    }, Object(external_wp_element_["createElement"])(external_wp_components_["Button"], {
      isLink: true,
      className: "woocommerce-profile-wizard__footer-link",
      onClick: () => {
        invalidateResolutionForStoreSelector('getTaskLists');
        this.setState({
          showUsageModal: true,
          skipping: true
        });
        return false;
      }
    }, Object(external_wp_i18n_["__"])('Skip setup store details', 'woocommerce-admin')), Object(external_wp_element_["createElement"])(external_wp_components_["Button"], {
      isTertiary: true,
      label: skipSetupText,
      onClick: () => this.setState({
        isSkipSetupPopoverVisible: true
      })
    }, Object(external_wp_element_["createElement"])(icon["a" /* default */], {
      icon: info["a" /* default */]
    })), isSkipSetupPopoverVisible && Object(external_wp_element_["createElement"])(external_wp_components_["Popover"], {
      focusOnMount: "container",
      position: "top center",
      onClose: () => this.setState({
        isSkipSetupPopoverVisible: false
      })
    }, skipSetupText)));
  }

}
store_details_StoreDetails.contextType = currency_context["a" /* CurrencyContext */];
/* harmony default export */ var store_details = (Object(external_wp_compose_["compose"])(Object(external_wp_data_["withSelect"])(select => {
  const {
    getSettings,
    getSettingsError,
    isUpdateSettingsRequesting
  } = select(external_wc_data_["SETTINGS_STORE_NAME"]);
  const {
    getProfileItems,
    isOnboardingRequesting,
    getEmailPrefill,
    hasFinishedResolution: hasFinishedResolutionOnboarding
  } = select(external_wc_data_["ONBOARDING_STORE_NAME"]);
  const {
    getLocale,
    getLocales,
    getCountries,
    hasFinishedResolution: hasFinishedResolutionCountries
  } = select(external_wc_data_["COUNTRIES_STORE_NAME"]);
  const {
    isResolving
  } = select(external_wc_data_["OPTIONS_STORE_NAME"]);
  const profileItems = getProfileItems();
  const emailPrefill = getEmailPrefill();
  const {
    general: settings = {}
  } = getSettings('general');
  const isBusy = isOnboardingRequesting('updateProfileItems') || isUpdateSettingsRequesting('general') || isResolving('getOption', ['woocommerce_allow_tracking']);
  const isLoading = !hasFinishedResolutionOnboarding('getProfileItems') || !hasFinishedResolutionOnboarding('getEmailPrefill') || !hasFinishedResolutionCountries('getLocales') || !hasFinishedResolutionCountries('getCountries');
  const errorsRef = Object(external_wp_element_["useRef"])({
    settings: null
  });
  errorsRef.current = {
    settings: getSettingsError('general')
  }; // Check if a store address is set so that we don't default
  // to WooCommerce's default country of the UK.

  const countryState = settings.woocommerce_store_address && settings.woocommerce_default_country || '';
  getCountries();
  getLocales();
  const initialValues = {
    addressLine1: settings.woocommerce_store_address || '',
    addressLine2: settings.woocommerce_store_address_2 || '',
    city: settings.woocommerce_store_city || '',
    countryState,
    postCode: settings.woocommerce_store_postcode || '',
    isAgreeMarketing: typeof profileItems.is_agree_marketing === 'boolean' ? profileItems.is_agree_marketing : true,
    storeEmail: typeof profileItems.store_email === 'string' ? profileItems.store_email : emailPrefill
  };
  return {
    getLocale,
    initialValues,
    isLoading,
    profileItems,
    isBusy,
    settings,
    errorsRef
  };
}), Object(external_wp_data_["withDispatch"])(dispatch => {
  const {
    createNotice
  } = dispatch('core/notices');
  const {
    invalidateResolutionForStoreSelector,
    updateProfileItems
  } = dispatch(external_wc_data_["ONBOARDING_STORE_NAME"]);
  const {
    updateAndPersistSettingsForGroup
  } = dispatch(external_wc_data_["SETTINGS_STORE_NAME"]);
  return {
    createNotice,
    invalidateResolutionForStoreSelector,
    updateProfileItems,
    updateAndPersistSettingsForGroup
  };
}))(store_details_StoreDetails));
// EXTERNAL MODULE: external ["wp","apiFetch"]
var external_wp_apiFetch_ = __webpack_require__(21);
var external_wp_apiFetch_default = /*#__PURE__*/__webpack_require__.n(external_wp_apiFetch_);

// EXTERNAL MODULE: external ["wp","htmlEntities"]
var external_wp_htmlEntities_ = __webpack_require__(34);

// EXTERNAL MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/gridicons@3.4.0_react@17.0.2/node_modules/gridicons/dist/info.js
var dist_info = __webpack_require__(624);
var info_default = /*#__PURE__*/__webpack_require__.n(dist_info);

// EXTERNAL MODULE: ./client/profile-wizard/steps/theme/style.scss
var theme_style = __webpack_require__(625);

// EXTERNAL MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/gridicons@3.4.0_react@17.0.2/node_modules/gridicons/dist/cloud-upload.js
var cloud_upload = __webpack_require__(626);
var cloud_upload_default = /*#__PURE__*/__webpack_require__.n(cloud_upload);

// EXTERNAL MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/prop-types@15.8.1/node_modules/prop-types/index.js
var prop_types = __webpack_require__(1);
var prop_types_default = /*#__PURE__*/__webpack_require__.n(prop_types);

// CONCATENATED MODULE: ./client/profile-wizard/steps/theme/uploader.js


/**
 * External dependencies
 */












/**
 * NOTE: This can be removed after WP version 6.0 and replaced with a div.
 *
 * @param {Object} props React props.
 * @param {Node} [props.children] Children of react component.
 * @param {string} [props.className] Additional class name to style the component.
 */

const DropZoneWrapper = _ref => {
  let {
    children,
    className
  } = _ref;
  const isDropzoneProviderDepreciated = Object(external_wc_wcSettings_["isWpVersion"])('5.8', '>=');

  if (isDropzoneProviderDepreciated) {
    return Object(external_wp_element_["createElement"])("div", {
      className: className
    }, children);
  }

  return Object(external_wp_element_["createElement"])(external_wp_components_["DropZoneProvider"], null, Object(external_wp_element_["createElement"])("div", {
    className: className
  }, children));
};

class uploader_ThemeUploader extends external_wp_element_["Component"] {
  constructor() {
    super();
    this.state = {
      isUploading: false
    };
    this.handleFilesUpload = this.handleFilesUpload.bind(this);
    this.handleFilesDrop = this.handleFilesDrop.bind(this);
  }

  handleFilesDrop(files) {
    const file = files[0];
    this.uploadTheme(file);
  }

  handleFilesUpload(e) {
    const file = e.target.files[0];
    this.uploadTheme(file);
  }

  uploadTheme(file) {
    const {
      createNotice,
      onUploadComplete
    } = this.props;
    this.setState({
      isUploading: true
    });
    const body = new window.FormData();
    body.append('pluginzip', file);
    return external_wp_apiFetch_default()({
      path: '/wc-admin/themes',
      method: 'POST',
      body
    }).then(response => {
      onUploadComplete(response);
      this.setState({
        isUploading: false
      });
      createNotice(response.status, response.message);
    }).catch(error => {
      this.setState({
        isUploading: false
      });

      if (error && error.message) {
        createNotice('error', error.message);
      }
    });
  }

  render() {
    const {
      className
    } = this.props;
    const {
      isUploading
    } = this.state;
    const classes = classnames_default()('woocommerce-theme-uploader', className, {
      'is-uploading': isUploading
    });
    return Object(external_wp_element_["createElement"])(external_wp_components_["Card"], {
      className: classes
    }, Object(external_wp_element_["createElement"])(DropZoneWrapper, {
      className: "woocommerce-theme-uploader__dropzone-wrapper"
    }, !isUploading ? Object(external_wp_element_["createElement"])(external_wp_element_["Fragment"], null, Object(external_wp_element_["createElement"])(external_wp_components_["FormFileUpload"], {
      accept: ".zip",
      onChange: this.handleFilesUpload
    }, Object(external_wp_element_["createElement"])(cloud_upload_default.a, null), Object(external_wp_element_["createElement"])(external_wc_components_["H"], {
      className: "woocommerce-theme-uploader__title"
    }, Object(external_wp_i18n_["__"])('Upload a theme', 'woocommerce-admin')), Object(external_wp_element_["createElement"])("p", null, Object(external_wp_i18n_["__"])('Drop a theme zip file here to upload', 'woocommerce-admin'))), Object(external_wp_element_["createElement"])(external_wp_components_["DropZone"], {
      label: Object(external_wp_i18n_["__"])('Drop your theme zip file here', 'woocommerce-admin'),
      onFilesDrop: this.handleFilesDrop
    })) : Object(external_wp_element_["createElement"])(external_wp_element_["Fragment"], null, Object(external_wp_element_["createElement"])(external_wc_components_["Spinner"], null), Object(external_wp_element_["createElement"])(external_wc_components_["H"], {
      className: "woocommerce-theme-uploader__title"
    }, Object(external_wp_i18n_["__"])('Uploading theme', 'woocommerce-admin')), Object(external_wp_element_["createElement"])("p", null, Object(external_wp_i18n_["__"])('Your theme is being uploaded', 'woocommerce-admin')))));
  }

}

uploader_ThemeUploader.propTypes = {
  /**
   * Additional class name to style the component.
   */
  className: prop_types_default.a.string,

  /**
   * Function called when an upload has finished.
   */
  onUploadComplete: prop_types_default.a.func
};
uploader_ThemeUploader.defaultProps = {
  onUploadComplete: external_lodash_["noop"]
};
/* harmony default export */ var uploader = (Object(external_wp_compose_["compose"])(Object(external_wp_data_["withDispatch"])(dispatch => {
  const {
    createNotice
  } = dispatch('core/notices');
  return {
    createNotice
  };
}))(uploader_ThemeUploader));
// EXTERNAL MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/@wordpress+icons@6.3.0/node_modules/@wordpress/icons/build-module/library/close.js
var library_close = __webpack_require__(531);

// EXTERNAL MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/gridicons@3.4.0_react@17.0.2/node_modules/gridicons/dist/phone.js
var phone = __webpack_require__(627);
var phone_default = /*#__PURE__*/__webpack_require__.n(phone);

// EXTERNAL MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/gridicons@3.4.0_react@17.0.2/node_modules/gridicons/dist/tablet.js
var tablet = __webpack_require__(628);
var tablet_default = /*#__PURE__*/__webpack_require__.n(tablet);

// EXTERNAL MODULE: /Users/obliviousharmony/Code/WooCommerce/Monorepo/node_modules/.pnpm/gridicons@3.4.0_react@17.0.2/node_modules/gridicons/dist/computer.js
var computer = __webpack_require__(629);
var computer_default = /*#__PURE__*/__webpack_require__.n(computer);

// CONCATENATED MODULE: ./client/profile-wizard/steps/theme/preview.js


/**
 * External dependencies
 */











/**
 * Internal dependencies
 */

const devices = [{
  key: 'mobile',
  icon: phone_default.a
}, {
  key: 'tablet',
  icon: tablet_default.a
}, {
  key: 'desktop',
  icon: computer_default.a
}];

class preview_ThemePreview extends external_wp_element_["Component"] {
  constructor() {
    super(...arguments);
    this.state = {
      device: 'desktop'
    };
    this.handleDeviceClick = this.handleDeviceClick.bind(this);
  }

  handleDeviceClick(device) {
    const {
      theme
    } = this.props;
    Object(external_wc_tracks_["recordEvent"])('storeprofiler_store_theme_demo_device', {
      device,
      theme: theme.slug
    });
    this.setState({
      device
    });
  }

  render() {
    const {
      isBusy,
      onChoose,
      onClose,
      theme
    } = this.props;
    const {
      demo_url: demoUrl,
      slug,
      title
    } = theme;
    const {
      device: currentDevice
    } = this.state;
    return Object(external_wp_element_["createElement"])("div", {
      className: "woocommerce-theme-preview"
    }, Object(external_wp_element_["createElement"])("div", {
      className: "woocommerce-theme-preview__toolbar"
    }, Object(external_wp_element_["createElement"])(external_wp_components_["Button"], {
      className: "woocommerce-theme-preview__close",
      onClick: onClose
    }, Object(external_wp_element_["createElement"])(icon["a" /* default */], {
      icon: library_close["a" /* default */]
    })), Object(external_wp_element_["createElement"])("div", {
      className: "woocommerce-theme-preview__theme-name"
    }, Object(esm["a" /* default */])({
      /* translators: Describing who a previewed theme is developed by. E.g., Storefront developed by WooCommerce */
      mixedString: Object(external_wp_i18n_["sprintf"])(Object(external_wp_i18n_["__"])('{{strong}}%s{{/strong}} developed by WooCommerce', 'woocommerce-admin'), title),
      components: {
        strong: Object(external_wp_element_["createElement"])("strong", null)
      }
    })), Object(external_wp_element_["createElement"])("div", {
      className: "woocommerce-theme-preview__devices"
    }, devices.map(device => {
      const DeviceTag = device.icon;
      return Object(external_wp_element_["createElement"])(external_wp_components_["Button"], {
        key: device.key,
        className: classnames_default()('woocommerce-theme-preview__device', {
          'is-selected': device.key === currentDevice
        }),
        onClick: () => this.handleDeviceClick(device.key)
      }, Object(external_wp_element_["createElement"])(DeviceTag, null));
    })), Object(external_wp_element_["createElement"])(external_wp_components_["Button"], {
      isPrimary: true,
      onClick: () => onChoose(slug, 'preview'),
      isBusy: isBusy
    }, Object(external_wp_i18n_["__"])('Choose', 'woocommerce-admin'))), Object(external_wp_element_["createElement"])(external_wc_components_["WebPreview"], {
      src: demoUrl,
      title: title,
      className: `is-${currentDevice}`
    }));
  }

}

/* harmony default export */ var preview = (preview_ThemePreview);
// CONCATENATED MODULE: ./client/profile-wizard/steps/theme/index.js


/**
 * External dependencies
 */












/**
 * Internal dependencies
 */







class theme_Theme extends external_wp_element_["Component"] {
  constructor() {
    super(...arguments);
    this.state = {
      activeTab: 'all',
      chosen: null,
      demo: null,
      uploadedThemes: []
    };
    this.handleUploadComplete = this.handleUploadComplete.bind(this);
    this.onChoose = this.onChoose.bind(this);
    this.onClosePreview = this.onClosePreview.bind(this);
    this.onSelectTab = this.onSelectTab.bind(this);
    this.openDemo = this.openDemo.bind(this);
    this.skipStep = this.skipStep.bind(this);
  }

  componentDidUpdate(prevProps) {
    const {
      isError,
      isUpdatingProfileItems,
      createNotice
    } = this.props;
    const {
      chosen
    } = this.state;
    const isRequestSuccessful = !isUpdatingProfileItems && prevProps.isUpdatingProfileItems && !isError && chosen;
    const isRequestError = !isUpdatingProfileItems && prevProps.isRequesting && isError;

    if (isRequestSuccessful) {
      /* eslint-disable react/no-did-update-set-state */
      this.setState({
        chosen: null
      });
      /* eslint-enable react/no-did-update-set-state */

      this.props.goToNextStep();
    }

    if (isRequestError) {
      /* eslint-disable react/no-did-update-set-state */
      this.setState({
        chosen: null
      });
      /* eslint-enable react/no-did-update-set-state */

      createNotice('error', Object(external_wp_i18n_["__"])('There was a problem selecting your store theme', 'woocommerce-admin'));
    }
  }

  onChoose(theme) {
    let location = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : '';
    const {
      updateProfileItems
    } = this.props;
    const {
      is_installed: isInstalled,
      price,
      slug
    } = theme;
    const {
      activeTheme = ''
    } = Object(admin_settings["d" /* getAdminSetting */])('onboarding', {});
    this.setState({
      chosen: slug
    });
    Object(external_wc_tracks_["recordEvent"])('storeprofiler_store_theme_choose', {
      theme: slug,
      location
    });

    if (slug !== activeTheme && Object(utils["d" /* getPriceValue */])(price) <= 0) {
      if (isInstalled) {
        this.activateTheme(slug);
      } else {
        this.installTheme(slug);
      }
    } else {
      updateProfileItems({
        theme: slug
      });
    }
  }

  installTheme(slug) {
    const {
      createNotice
    } = this.props;
    external_wp_apiFetch_default()({
      path: '/wc-admin/onboarding/themes/install?theme=' + slug,
      method: 'POST'
    }).then(() => {
      this.activateTheme(slug);
    }).catch(response => {
      this.setState({
        chosen: null
      });
      createNotice('error', response.message);
    });
  }

  activateTheme(slug) {
    const {
      createNotice,
      updateProfileItems
    } = this.props;
    external_wp_apiFetch_default()({
      path: '/wc-admin/onboarding/themes/activate?theme=' + slug,
      method: 'POST'
    }).then(response => {
      createNotice('success', Object(external_wp_i18n_["sprintf"])(
      /* translators: The name of the theme that was installed and activated */
      Object(external_wp_i18n_["__"])('%s was installed and activated on your site', 'woocommerce-admin'), response.name));
      Object(admin_settings["e" /* setAdminSetting */])('onboarding', { ...Object(admin_settings["d" /* getAdminSetting */])('onboarding', {}),
        activeTheme: response.slug
      });
      updateProfileItems({
        theme: slug
      });
    }).catch(response => {
      this.setState({
        chosen: null
      });
      createNotice('error', response.message);
    });
  }

  onClosePreview() {
    const {
      demo
    } = this.state;
    Object(external_wc_tracks_["recordEvent"])('storeprofiler_store_theme_demo_close', {
      theme: demo.slug
    });
    document.body.classList.remove('woocommerce-theme-preview-active');
    this.setState({
      demo: null
    });
  }

  openDemo(theme) {
    Object(external_wc_tracks_["recordEvent"])('storeprofiler_store_theme_live_demo', {
      theme: theme.slug
    });
    document.body.classList.add('woocommerce-theme-preview-active');
    this.setState({
      demo: theme
    });
  }

  skipStep() {
    const {
      activeTheme = ''
    } = Object(admin_settings["d" /* getAdminSetting */])('onboarding', {});
    Object(external_wc_tracks_["recordEvent"])('storeprofiler_store_theme_skip_step', {
      activeTheme
    });
    this.props.goToNextStep();
  }

  renderTheme(theme) {
    const {
      demo_url: demoUrl,
      has_woocommerce_support: hasSupport,
      image,
      slug,
      title
    } = theme;
    const {
      chosen
    } = this.state;
    const {
      activeTheme = ''
    } = Object(admin_settings["d" /* getAdminSetting */])('onboarding', {});
    return Object(external_wp_element_["createElement"])(external_wp_components_["Card"], {
      className: "woocommerce-profile-wizard__theme",
      key: slug
    }, Object(external_wp_element_["createElement"])(external_wp_components_["CardBody"], {
      size: null
    }, image && Object(external_wp_element_["createElement"])("div", {
      className: "woocommerce-profile-wizard__theme-image",
      style: {
        backgroundImage: `url(${image})`
      },
      role: "img",
      "aria-label": title
    })), Object(external_wp_element_["createElement"])(external_wp_components_["CardBody"], {
      className: "woocommerce-profile-wizard__theme-details"
    }, Object(external_wp_element_["createElement"])(external_wc_components_["H"], {
      className: "woocommerce-profile-wizard__theme-name"
    }, title, !hasSupport && Object(external_wp_element_["createElement"])(external_wp_components_["Tooltip"], {
      text: Object(external_wp_i18n_["__"])('This theme does not support WooCommerce.', 'woocommerce-admin')
    }, Object(external_wp_element_["createElement"])("span", null, Object(external_wp_element_["createElement"])(info_default.a, {
      role: "img",
      "aria-hidden": "true",
      focusable: "false"
    })))), Object(external_wp_element_["createElement"])("p", {
      className: "woocommerce-profile-wizard__theme-status"
    }, this.getThemeStatus(theme))), Object(external_wp_element_["createElement"])(external_wp_components_["CardFooter"], null, slug === activeTheme ? Object(external_wp_element_["createElement"])(external_wp_components_["Button"], {
      isPrimary: true,
      onClick: () => this.onChoose(theme, 'card'),
      isBusy: chosen === slug,
      disabled: chosen === slug
    }, Object(external_wp_i18n_["__"])('Continue with my active theme', 'woocommerce-admin')) : Object(external_wp_element_["createElement"])(external_wp_components_["Button"], {
      isSecondary: true,
      onClick: () => this.onChoose(theme, 'card'),
      isBusy: chosen === slug,
      disabled: chosen === slug
    }, Object(external_wp_i18n_["__"])('Choose', 'woocommerce-admin')), demoUrl && Object(external_wp_element_["createElement"])(external_wp_components_["Button"], {
      isTertiary: true,
      onClick: () => this.openDemo(theme)
    }, Object(external_wp_i18n_["__"])('Live demo', 'woocommerce-admin'))));
  }

  getThemeStatus(theme) {
    const {
      is_installed: isInstalled,
      price,
      slug
    } = theme;
    const {
      activeTheme = ''
    } = Object(admin_settings["d" /* getAdminSetting */])('onboarding', {});

    if (activeTheme === slug) {
      return Object(external_wp_i18n_["__"])('Currently active theme', 'woocommerce-admin');
    }

    if (isInstalled) {
      return Object(external_wp_i18n_["__"])('Installed', 'woocommerce-admin');
    } else if (Object(utils["d" /* getPriceValue */])(price) <= 0) {
      return Object(external_wp_i18n_["__"])('Free', 'woocommerce-admin');
    }

    return Object(external_wp_i18n_["sprintf"])(Object(external_wp_i18n_["__"])('%s per year', 'woocommerce-admin'), Object(external_wp_htmlEntities_["decodeEntities"])(price));
  }

  doesActiveThemeSupportWooCommerce() {
    const {
      activeTheme = ''
    } = Object(admin_settings["d" /* getAdminSetting */])('onboarding', {});
    const allThemes = this.getThemes();
    const currentTheme = allThemes.find(theme => theme.slug === activeTheme);
    return currentTheme && currentTheme.has_woocommerce_support;
  }

  onSelectTab(tab) {
    Object(external_wc_tracks_["recordEvent"])('storeprofiler_store_theme_navigate', {
      navigation: tab
    });
    this.setState({
      activeTab: tab
    });
  }

  getPriceValue(string) {
    return Number(Object(external_wp_htmlEntities_["decodeEntities"])(string).replace(/[^0-9.-]+/g, ''));
  }

  getThemes() {
    let activeTab = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : 'all';
    const {
      uploadedThemes
    } = this.state;
    const {
      activeTheme = '',
      themes = []
    } = Object(admin_settings["d" /* getAdminSetting */])('onboarding', {});
    const allThemes = [...themes.filter(theme => theme && (theme.has_woocommerce_support || theme.slug === activeTheme)), ...uploadedThemes];

    switch (activeTab) {
      case 'paid':
        return allThemes.filter(theme => Object(utils["d" /* getPriceValue */])(theme.price) > 0);

      case 'free':
        return allThemes.filter(theme => Object(utils["d" /* getPriceValue */])(theme.price) <= 0);

      case 'all':
      default:
        return allThemes;
    }
  }

  handleUploadComplete(upload) {
    if (upload.status === 'success' && upload.theme_data) {
      this.setState({
        uploadedThemes: [...this.state.uploadedThemes, upload.theme_data]
      });
      Object(external_wc_tracks_["recordEvent"])('storeprofiler_store_theme_upload', {
        theme: upload.theme_data.slug
      });
    }
  }

  render() {
    const {
      activeTab,
      chosen,
      demo
    } = this.state;
    const themes = this.getThemes(activeTab);
    const activeThemeSupportsWooCommerce = this.doesActiveThemeSupportWooCommerce();
    return Object(external_wp_element_["createElement"])(external_wp_element_["Fragment"], null, Object(external_wp_element_["createElement"])("div", {
      className: "woocommerce-profile-wizard__step-header"
    }, Object(external_wp_element_["createElement"])(external_wc_experimental_["Text"], {
      variant: "title.small",
      as: "h2",
      size: "20",
      lineHeight: "28px"
    }, Object(external_wp_i18n_["__"])('Choose a theme', 'woocommerce-admin')), Object(external_wp_element_["createElement"])(external_wc_experimental_["Text"], {
      variant: "body",
      as: "p"
    }, Object(external_wp_i18n_["__"])("Choose how your store appears to customers. And don't worry, you can always switch themes and edit them later.", 'woocommerce-admin'))), Object(external_wp_element_["createElement"])(external_wp_components_["TabPanel"], {
      className: "woocommerce-profile-wizard__themes-tab-panel",
      activeClass: "is-active",
      onSelect: this.onSelectTab,
      tabs: [{
        name: 'all',
        title: Object(external_wp_i18n_["__"])('All themes', 'woocommerce-admin')
      }, {
        name: 'paid',
        title: Object(external_wp_i18n_["__"])('Paid themes', 'woocommerce-admin')
      }, {
        name: 'free',
        title: Object(external_wp_i18n_["__"])('Free themes', 'woocommerce-admin')
      }]
    }, () => Object(external_wp_element_["createElement"])("div", {
      className: "woocommerce-profile-wizard__themes"
    }, themes && themes.map(theme => this.renderTheme(theme)), Object(external_wp_element_["createElement"])(uploader, {
      onUploadComplete: this.handleUploadComplete
    }))), demo && Object(external_wp_element_["createElement"])(preview, {
      theme: demo,
      onChoose: () => this.onChoose(demo, 'card'),
      onClose: this.onClosePreview,
      isBusy: chosen === demo.slug
    }), activeThemeSupportsWooCommerce && Object(external_wp_element_["createElement"])("p", {
      className: "woocommerce-profile-wizard__themes-skip-this-step"
    }, Object(external_wp_element_["createElement"])(external_wp_components_["Button"], {
      isLink: true,
      className: "woocommerce-profile-wizard__skip",
      onClick: () => this.skipStep()
    }, Object(external_wp_i18n_["__"])('Skip this step', 'woocommerce-admin'))));
  }

}

/* harmony default export */ var steps_theme = (Object(external_wp_compose_["compose"])(Object(external_wp_data_["withSelect"])(select => {
  const {
    getProfileItems,
    getOnboardingError,
    isOnboardingRequesting
  } = select(external_wc_data_["ONBOARDING_STORE_NAME"]);
  return {
    isError: Boolean(getOnboardingError('updateProfileItems')),
    isUpdatingProfileItems: isOnboardingRequesting('updateProfileItems'),
    profileItems: getProfileItems()
  };
}), Object(external_wp_data_["withDispatch"])(dispatch => {
  const {
    updateProfileItems
  } = dispatch(external_wc_data_["ONBOARDING_STORE_NAME"]);
  const {
    createNotice
  } = dispatch('core/notices');
  return {
    createNotice,
    updateProfileItems
  };
}))(theme_Theme));
// EXTERNAL MODULE: ./client/profile-wizard/style.scss
var profile_wizard_style = __webpack_require__(630);

// CONCATENATED MODULE: ./client/profile-wizard/index.js


/**
 * External dependencies
 */










/**
 * Internal dependencies
 */









const STEPS_FILTER = 'woocommerce_admin_profile_wizard_steps';

class profile_wizard_ProfileWizard extends external_wp_element_["Component"] {
  constructor(props) {
    super(props);
    this.cachedActivePlugins = props.activePlugins;
    this.goToNextStep = this.goToNextStep.bind(this);
    this.trackStepValueChanges = this.trackStepValueChanges.bind(this);
    this.updateCurrentStepValues = this.updateCurrentStepValues.bind(this);
    this.stepValueChanges = {};
  }

  componentDidUpdate(prevProps) {
    const {
      step: prevStep
    } = prevProps.query;
    const {
      step
    } = this.props.query;
    const {
      isError,
      isGetProfileItemsRequesting,
      createNotice
    } = this.props;
    const isRequestError = !isGetProfileItemsRequesting && prevProps.isRequesting && isError;

    if (isRequestError) {
      createNotice('error', Object(external_wp_i18n_["__"])('There was a problem finishing the setup wizard', 'woocommerce-admin'));
    }

    if (prevStep !== step) {
      window.document.documentElement.scrollTop = 0;
      Object(external_wc_tracks_["recordEvent"])('storeprofiler_step_view', {
        step: this.getCurrentStep().key,
        wc_version: Object(external_wc_wcSettings_["getSetting"])('wcVersion')
      });
    }
  }

  componentDidMount() {
    document.body.classList.remove('woocommerce-admin-is-loading');
    document.body.classList.add('woocommerce-profile-wizard__body');
    document.body.classList.add('woocommerce-admin-full-screen');
    document.body.classList.add('is-wp-toolbar-disabled');
    Object(external_wc_tracks_["recordEvent"])('storeprofiler_step_view', {
      step: this.getCurrentStep().key,
      wc_version: Object(external_wc_wcSettings_["getSetting"])('wcVersion')
    });
  }

  componentWillUnmount() {
    document.body.classList.remove('woocommerce-profile-wizard__body');
    document.body.classList.remove('woocommerce-admin-full-screen');
    document.body.classList.remove('is-wp-toolbar-disabled');
  }
  /**
   * Set the initial and current values of a step to track the state of the step.
   * This is used to determine if the step has been changes or not.
   *
   * @param {string} step key of the step
   * @param {*} initialValues the initial values of the step
   * @param {*} currentValues the current values of the step
   * @param {Function} onSave a function to call when the step is saved
   */


  trackStepValueChanges(step, initialValues, currentValues, onSave) {
    this.stepValueChanges[step] = {
      initialValues,
      currentValues,
      onSave
    };
  }
  /**
   * Update currentValues of the given step.
   *
   * @param {string} step key of the step
   * @param {*} currentValues the current values of the step
   */


  updateCurrentStepValues(step, currentValues) {
    if (!this.stepValueChanges[step]) {
      return;
    }

    this.stepValueChanges[step].currentValues = currentValues;
  }

  getSteps() {
    const {
      profileItems
    } = this.props;
    const steps = [];
    steps.push({
      key: 'store-details',
      container: store_details,
      label: Object(external_wp_i18n_["__"])('Store Details', 'woocommerce-admin'),
      isComplete: profileItems.hasOwnProperty('setup_client') && profileItems.setup_client !== null
    });
    steps.push({
      key: 'industry',
      container: steps_industry,
      label: Object(external_wp_i18n_["__"])('Industry', 'woocommerce-admin'),
      isComplete: profileItems.hasOwnProperty('industry') && profileItems.industry !== null
    });
    steps.push({
      key: 'product-types',
      container: product_types,
      label: Object(external_wp_i18n_["__"])('Product Types', 'woocommerce-admin'),
      isComplete: profileItems.hasOwnProperty('product_types') && profileItems.product_types !== null
    });
    steps.push({
      key: 'business-details',
      container: BusinessDetailsStep,
      label: Object(external_wp_i18n_["__"])('Business Details', 'woocommerce-admin'),
      isComplete: profileItems.hasOwnProperty('product_count') && profileItems.product_count !== null
    });
    steps.push({
      key: 'theme',
      container: steps_theme,
      label: Object(external_wp_i18n_["__"])('Theme', 'woocommerce-admin'),
      isComplete: profileItems.hasOwnProperty('theme') && profileItems.theme !== null
    });
    /**
     * Filter for Onboarding steps configuration.
     *
     * @filter woocommerce_admin_profile_wizard_steps
     * @param {Array.<Object>} steps Array of steps for Onboarding Wizard.
     */

    return Object(external_wp_hooks_["applyFilters"])(STEPS_FILTER, steps);
  }

  getCurrentStep() {
    const {
      step
    } = this.props.query;
    const currentStep = this.getSteps().find(s => s.key === step);

    if (!currentStep) {
      return this.getSteps()[0];
    }

    return currentStep;
  }
  /**
   * @param {Object} tracksArgs optional track arguments for the storeprofiler_step_complete track.
   */


  async goToNextStep() {
    let tracksArgs = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
    const {
      activePlugins
    } = this.props;
    const currentStep = this.getCurrentStep();
    const currentStepIndex = this.getSteps().findIndex(s => s.key === currentStep.key);
    Object(external_wc_tracks_["recordEvent"])('storeprofiler_step_complete', {
      step: currentStep.key,
      wc_version: Object(external_wc_wcSettings_["getSetting"])('wcVersion'),
      ...tracksArgs
    }); // Update the activePlugins cache in case plugins were installed
    // in the current step that affect the visibility of the next step.

    this.cachedActivePlugins = activePlugins;
    const nextStep = this.getSteps()[currentStepIndex + 1];

    if (typeof nextStep === 'undefined') {
      this.completeProfiler();
      return;
    }

    return Object(external_wc_navigation_["updateQueryString"])({
      step: nextStep.key
    });
  }

  completeProfiler() {
    const {
      activePlugins,
      isJetpackConnected,
      notes,
      updateNote,
      updateProfileItems,
      connectToJetpack
    } = this.props;
    Object(external_wc_tracks_["recordEvent"])('storeprofiler_complete');
    const shouldConnectJetpack = activePlugins.includes('jetpack') && !isJetpackConnected;
    const profilerNote = notes.find(note => note.name === 'wc-admin-onboarding-profiler-reminder');

    if (profilerNote) {
      updateNote(profilerNote.id, {
        status: 'actioned'
      });
    }

    updateProfileItems({
      completed: true
    }).then(() => {
      const homescreenUrl = new URL(Object(external_wc_navigation_["getNewPath"])({}, '/', {}), window.location.href).href;

      if (shouldConnectJetpack) {
        document.body.classList.add('woocommerce-admin-is-loading');
        connectToJetpack(() => {
          return homescreenUrl;
        });
      } else {
        window.location.href = homescreenUrl;
      }
    });
  }

  skipProfiler() {
    const {
      createNotice,
      updateProfileItems
    } = this.props;
    updateProfileItems({
      skipped: true
    }).then(() => {
      Object(external_wc_tracks_["recordEvent"])('storeprofiler_store_details_skip');
      Object(external_wc_navigation_["getHistory"])().push(Object(external_wc_navigation_["getNewPath"])({}, '/', {}));
    }).catch(() => {
      createNotice('error', Object(external_wp_i18n_["__"])('There was a problem skipping the setup wizard', 'woocommerce-admin'));
    });
  }

  render() {
    const {
      query
    } = this.props;
    const step = this.getCurrentStep();
    const stepKey = step.key;
    const container = Object(external_wp_element_["createElement"])(step.container, {
      query,
      step,
      goToNextStep: this.goToNextStep,
      skipProfiler: () => {
        this.skipProfiler();
      },
      trackStepValueChanges: this.trackStepValueChanges,
      updateCurrentStepValues: this.updateCurrentStepValues
    });
    const steps = this.getSteps().map(_step => Object(external_lodash_["pick"])(_step, ['key', 'label', 'isComplete']));
    const classNames = `woocommerce-profile-wizard__container ${stepKey}`;
    return Object(external_wp_element_["createElement"])(external_wp_element_["Fragment"], null, Object(external_wp_element_["createElement"])(header_ProfileWizardHeader, {
      currentStep: stepKey,
      steps: steps,
      stepValueChanges: this.stepValueChanges
    }), Object(external_wp_element_["createElement"])("div", {
      className: classNames
    }, container));
  }

}

/* harmony default export */ var profile_wizard = __webpack_exports__["default"] = (Object(external_wp_compose_["compose"])(Object(external_wp_data_["withSelect"])(select => {
  const {
    getNotes
  } = select(external_wc_data_["NOTES_STORE_NAME"]);
  const {
    getProfileItems,
    getOnboardingError
  } = select(external_wc_data_["ONBOARDING_STORE_NAME"]);
  const {
    getActivePlugins,
    getPluginsError,
    isJetpackConnected
  } = select(external_wc_data_["PLUGINS_STORE_NAME"]);
  const profileItems = getProfileItems();
  const notesQuery = {
    page: 1,
    per_page: external_wc_data_["QUERY_DEFAULTS"].pageSize,
    type: 'update',
    status: 'unactioned'
  };
  const notes = getNotes(notesQuery);
  const activePlugins = getActivePlugins();
  return {
    getPluginsError,
    isError: Boolean(getOnboardingError('updateProfileItems')),
    isJetpackConnected: isJetpackConnected(),
    notes,
    profileItems,
    activePlugins
  };
}), Object(external_wp_data_["withDispatch"])(dispatch => {
  const {
    connectToJetpackWithFailureRedirect,
    createErrorNotice
  } = dispatch(external_wc_data_["PLUGINS_STORE_NAME"]);
  const {
    updateNote
  } = dispatch(external_wc_data_["NOTES_STORE_NAME"]);
  const {
    updateOptions
  } = dispatch(external_wc_data_["OPTIONS_STORE_NAME"]);
  const {
    updateProfileItems
  } = dispatch(external_wc_data_["ONBOARDING_STORE_NAME"]);
  const {
    createNotice
  } = dispatch('core/notices');

  const connectToJetpack = failureRedirect => {
    connectToJetpackWithFailureRedirect(failureRedirect, createErrorNotice, external_wc_wcSettings_["getAdminLink"]);
  };

  return {
    connectToJetpack,
    createNotice,
    updateNote,
    updateOptions,
    updateProfileItems
  };
}), Object(admin_settings["d" /* getAdminSetting */])('plugins') ? Object(external_wc_data_["withPluginsHydration"])({ ...Object(admin_settings["d" /* getAdminSetting */])('plugins'),
  jetpackStatus: Object(admin_settings["d" /* getAdminSetting */])('dataEndpoints', {}).jetpackStatus
}) : external_lodash_["identity"])(profile_wizard_ProfileWizard));

/***/ })

}]);