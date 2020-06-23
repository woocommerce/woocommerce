(window["webpackJsonp"] = window["webpackJsonp"] || []).push([[43],{

/***/ 747:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* unused harmony export ALLOWED_TAGS */
/* unused harmony export ALLOWED_ATTR */
/* harmony import */ var dompurify__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(758);
/* harmony import */ var dompurify__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(dompurify__WEBPACK_IMPORTED_MODULE_0__);
/**
 * External dependencies
 */

var ALLOWED_TAGS = ['a', 'b', 'em', 'i', 'strong', 'p'];
var ALLOWED_ATTR = ['target', 'href', 'rel', 'name', 'download'];
/* harmony default export */ __webpack_exports__["a"] = (function (html) {
  return {
    __html: Object(dompurify__WEBPACK_IMPORTED_MODULE_0__["sanitize"])(html, {
      ALLOWED_TAGS: ALLOWED_TAGS,
      ALLOWED_ATTR: ALLOWED_ATTR
    })
  };
});

/***/ }),

/***/ 759:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var _babel_runtime_helpers_asyncToGenerator__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(46);
/* harmony import */ var _babel_runtime_helpers_asyncToGenerator__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_asyncToGenerator__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(41);
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(40);
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(59);
/* harmony import */ var _babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(44);
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(29);
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(42);
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(3);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_8__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(88);
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(256);
/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(1);
/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_11___default = /*#__PURE__*/__webpack_require__.n(prop_types__WEBPACK_IMPORTED_MODULE_11__);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(19);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_12___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_12__);
/* harmony import */ var _woocommerce_data__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(51);
/* harmony import */ var _woocommerce_data__WEBPACK_IMPORTED_MODULE_13___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_data__WEBPACK_IMPORTED_MODULE_13__);









function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function () { var Super = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_5___default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_5___default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_4___default()(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/**
 * External dependencies
 */






/**
 * WooCommerce dependencies
 */



var Connect = /*#__PURE__*/function (_Component) {
  _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_6___default()(Connect, _Component);

  var _super = _createSuper(Connect);

  function Connect(props) {
    var _this;

    _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_1___default()(this, Connect);

    _this = _super.call(this, props);
    _this.connectJetpack = _this.connectJetpack.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_3___default()(_this));
    props.setIsPending(true);
    return _this;
  }

  _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_2___default()(Connect, [{
    key: "componentDidMount",
    value: function componentDidMount() {
      var _this$props = this.props,
          autoConnect = _this$props.autoConnect,
          jetpackConnectUrl = _this$props.jetpackConnectUrl;

      if (autoConnect && jetpackConnectUrl) {
        this.connectJetpack();
      }
    }
  }, {
    key: "componentDidUpdate",
    value: function componentDidUpdate(prevProps) {
      var _this$props2 = this.props,
          autoConnect = _this$props2.autoConnect,
          createNotice = _this$props2.createNotice,
          error = _this$props2.error,
          isRequesting = _this$props2.isRequesting,
          jetpackConnectUrl = _this$props2.jetpackConnectUrl,
          onError = _this$props2.onError,
          setIsPending = _this$props2.setIsPending;

      if (prevProps.isRequesting && !isRequesting) {
        setIsPending(false);
      }

      if (error && error !== prevProps.error) {
        if (onError) {
          onError();
        }

        createNotice('error', error);
      }

      if (autoConnect && jetpackConnectUrl) {
        this.connectJetpack();
      }
    }
  }, {
    key: "connectJetpack",
    value: function () {
      var _connectJetpack = _babel_runtime_helpers_asyncToGenerator__WEBPACK_IMPORTED_MODULE_0___default()( /*#__PURE__*/regeneratorRuntime.mark(function _callee() {
        var _this$props3, jetpackConnectUrl, onConnect;

        return regeneratorRuntime.wrap(function _callee$(_context) {
          while (1) {
            switch (_context.prev = _context.next) {
              case 0:
                _this$props3 = this.props, jetpackConnectUrl = _this$props3.jetpackConnectUrl, onConnect = _this$props3.onConnect;

                if (onConnect) {
                  onConnect();
                }

                window.location = jetpackConnectUrl;

              case 3:
              case "end":
                return _context.stop();
            }
          }
        }, _callee, this);
      }));

      function connectJetpack() {
        return _connectJetpack.apply(this, arguments);
      }

      return connectJetpack;
    }()
  }, {
    key: "render",
    value: function render() {
      var _this$props4 = this.props,
          autoConnect = _this$props4.autoConnect,
          hasErrors = _this$props4.hasErrors,
          isRequesting = _this$props4.isRequesting,
          onSkip = _this$props4.onSkip,
          skipText = _this$props4.skipText;

      if (autoConnect) {
        return null;
      }

      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["Fragment"], null, hasErrors ? Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_9__[/* default */ "a"], {
        isPrimary: true,
        onClick: function onClick() {
          return window.location.reload();
        }
      }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_8__["__"])('Retry', 'woocommerce')) : Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_9__[/* default */ "a"], {
        disabled: isRequesting,
        isPrimary: true,
        onClick: this.connectJetpack
      }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_8__["__"])('Connect', 'woocommerce')), onSkip && Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_9__[/* default */ "a"], {
        onClick: onSkip
      }, skipText || Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_8__["__"])('No thanks', 'woocommerce')));
    }
  }]);

  return Connect;
}(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["Component"]);

Connect.propTypes = {
  /**
   * If connection should happen automatically, or requires user confirmation.
   */
  autoConnect: prop_types__WEBPACK_IMPORTED_MODULE_11___default.a.bool,

  /**
   * Method to create a displayed notice.
   */
  createNotice: prop_types__WEBPACK_IMPORTED_MODULE_11___default.a.func.isRequired,

  /**
   * Human readable error message.
   */
  error: prop_types__WEBPACK_IMPORTED_MODULE_11___default.a.string,

  /**
   * Bool to determine if the "Retry" button should be displayed.
   */
  hasErrors: prop_types__WEBPACK_IMPORTED_MODULE_11___default.a.bool,

  /**
   * Bool to check if the connection URL is still being requested.
   */
  isRequesting: prop_types__WEBPACK_IMPORTED_MODULE_11___default.a.bool,

  /**
   * Generated Jetpack connection URL.
   */
  jetpackConnectUrl: prop_types__WEBPACK_IMPORTED_MODULE_11___default.a.string,

  /**
   * Called before the redirect to Jetpack.
   */
  onConnect: prop_types__WEBPACK_IMPORTED_MODULE_11___default.a.func,

  /**
   * Called when the plugin has an error retrieving the jetpackConnectUrl.
   */
  onError: prop_types__WEBPACK_IMPORTED_MODULE_11___default.a.func,

  /**
   * Called when the plugin connection is skipped.
   */
  onSkip: prop_types__WEBPACK_IMPORTED_MODULE_11___default.a.func,

  /**
   * Redirect URL to encode as a URL param for the connection path.
   */
  redirectUrl: prop_types__WEBPACK_IMPORTED_MODULE_11___default.a.string,

  /**
   * Text used for the skip connection button.
   */
  skipText: prop_types__WEBPACK_IMPORTED_MODULE_11___default.a.string,

  /**
   * Control the `isPending` logic of the parent containing the Stepper.
   */
  setIsPending: prop_types__WEBPACK_IMPORTED_MODULE_11___default.a.func
};
Connect.defaultProps = {
  autoConnect: false,
  setIsPending: function setIsPending() {}
};
/* harmony default export */ __webpack_exports__["a"] = (Object(_wordpress_compose__WEBPACK_IMPORTED_MODULE_10__[/* default */ "a"])(Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_12__["withSelect"])(function (select, props) {
  var _select = select(_woocommerce_data__WEBPACK_IMPORTED_MODULE_13__["PLUGINS_STORE_NAME"]),
      getJetpackConnectUrl = _select.getJetpackConnectUrl,
      isPluginsRequesting = _select.isPluginsRequesting,
      getPluginsError = _select.getPluginsError;

  var queryArgs = {
    redirect_url: props.redirectUrl || window.location.href
  };
  var isRequesting = isPluginsRequesting('getJetpackConnectUrl');
  var error = getPluginsError('getJetpackConnectUrl') || '';
  var jetpackConnectUrl = getJetpackConnectUrl(queryArgs);
  return {
    error: error,
    isRequesting: isRequesting,
    jetpackConnectUrl: jetpackConnectUrl
  };
}), Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_12__["withDispatch"])(function (dispatch) {
  var _dispatch = dispatch('core/notices'),
      createNotice = _dispatch.createNotice;

  return {
    createNotice: createNotice
  };
}))(Connect));

/***/ }),

/***/ 760:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "a", function() { return pluginNames; });
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(3);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__);
/**
 * External dependencies
 */

/**
 * Plugin slugs and names as key/value pairs.
 */

var pluginNames = {
  'facebook-for-woocommerce': Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Facebook for WooCommerce', 'woocommerce'),
  jetpack: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Jetpack', 'woocommerce'),
  'klarna-checkout-for-woocommerce': Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Klarna Checkout for WooCommerce', 'woocommerce'),
  'klarna-payments-for-woocommerce': Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Klarna Payments for WooCommerce', 'woocommerce'),
  'mailchimp-for-woocommerce': Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Mailchimp for WooCommerce', 'woocommerce'),
  'woocommerce-gateway-paypal-express-checkout': Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('WooCommerce PayPal', 'woocommerce'),
  'woocommerce-gateway-stripe': Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('WooCommerce Stripe', 'woocommerce'),
  'woocommerce-payfast-gateway': Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('WooCommerce PayFast', 'woocommerce'),
  'woocommerce-payments': Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('WooCommerce Payments', 'woocommerce'),
  'woocommerce-services': Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('WooCommerce Services', 'woocommerce'),
  'woocommerce-shipstation-integration': Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('WooCommerce ShipStation Gateway', 'woocommerce'),
  'kliken-marketing-for-google': Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])('Google Ads', 'woocommerce')
};

/***/ }),

/***/ 769:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "b", function() { return validateStoreAddress; });
/* unused harmony export getCountryStateOptions */
/* unused harmony export useGetCountryStateAutofill */
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "a", function() { return StoreAddress; });
/* harmony import */ var _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(105);
/* harmony import */ var _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _babel_runtime_helpers_slicedToArray__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(749);
/* harmony import */ var _babel_runtime_helpers_slicedToArray__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_slicedToArray__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _babel_runtime_helpers_toConsumableArray__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(32);
/* harmony import */ var _babel_runtime_helpers_toConsumableArray__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_toConsumableArray__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(3);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _wordpress_html_entities__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(69);
/* harmony import */ var _wordpress_html_entities__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_wordpress_html_entities__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(2);
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(14);
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var _woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(26);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(63);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_components__WEBPACK_IMPORTED_MODULE_9__);





/**
 * External dependencies
 */






/**
 * Internal dependencies
 */



var _getSetting = Object(_woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_8__[/* getSetting */ "g"])('dataEndpoints', {
  countries: {}
}),
    countries = _getSetting.countries;
/**
 * Form validation.
 *
 * @param {Object} values Keyed values of all fields in the form.
 * @return {Object} Key value of fields and error messages, { myField: 'This field is required' }
 */


function validateStoreAddress(values) {
  var errors = {};

  if (!values.addressLine1.length) {
    errors.addressLine1 = Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__["__"])('Please add an address', 'woocommerce');
  }

  if (!values.countryState.length) {
    errors.countryState = Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__["__"])('Please select a country / region', 'woocommerce');
  }

  if (!values.city.length) {
    errors.city = Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__["__"])('Please add a city', 'woocommerce');
  }

  if (!values.postCode.length) {
    errors.postCode = Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__["__"])('Please add a post code', 'woocommerce');
  }

  return errors;
}
/**
 * Get all country and state combinations used for select dropdowns.
 *
 * @return {Object} Select options, { value: 'US:GA', label: 'United States - Georgia' }
 */

function getCountryStateOptions() {
  var countryStateOptions = countries.reduce(function (acc, country) {
    if (!country.states.length) {
      acc.push({
        key: country.code,
        label: Object(_wordpress_html_entities__WEBPACK_IMPORTED_MODULE_5__["decodeEntities"])(country.name)
      });
      return acc;
    }

    var countryStates = country.states.map(function (state) {
      return {
        key: country.code + ':' + state.code,
        label: Object(_wordpress_html_entities__WEBPACK_IMPORTED_MODULE_5__["decodeEntities"])(country.name) + ' -- ' + Object(_wordpress_html_entities__WEBPACK_IMPORTED_MODULE_5__["decodeEntities"])(state.name)
      };
    });
    acc.push.apply(acc, _babel_runtime_helpers_toConsumableArray__WEBPACK_IMPORTED_MODULE_2___default()(countryStates));
    return acc;
  }, []);
  return countryStateOptions;
}
/**
 * Get the autofill countryState fields and set value from filtered options.
 *
 * @param {Array} options Array of filterable options.
 * @param {string} countryState The value of the countryState field.
 * @param {Function} setValue Set value of the countryState input.
 * @return {Object} React component.
 */

function useGetCountryStateAutofill(options, countryState, setValue) {
  var _useState = Object(react__WEBPACK_IMPORTED_MODULE_7__["useState"])(''),
      _useState2 = _babel_runtime_helpers_slicedToArray__WEBPACK_IMPORTED_MODULE_1___default()(_useState, 2),
      autofillCountry = _useState2[0],
      setAutofillCountry = _useState2[1];

  var _useState3 = Object(react__WEBPACK_IMPORTED_MODULE_7__["useState"])(''),
      _useState4 = _babel_runtime_helpers_slicedToArray__WEBPACK_IMPORTED_MODULE_1___default()(_useState3, 2),
      autofillState = _useState4[0],
      setAutofillState = _useState4[1];

  Object(react__WEBPACK_IMPORTED_MODULE_7__["useEffect"])(function () {
    var filteredOptions = [];
    var countrySearch = new RegExp(Object(lodash__WEBPACK_IMPORTED_MODULE_6__["escapeRegExp"])(autofillCountry), 'i');

    if (autofillState.length || autofillCountry.length) {
      filteredOptions = options.filter(function (option) {
        return countrySearch.test(option.label);
      });
    }

    if (autofillCountry.length && autofillState.length) {
      var stateSearch = new RegExp(Object(lodash__WEBPACK_IMPORTED_MODULE_6__["escapeRegExp"])(autofillState.replace(/\s/g, '')), 'i');
      filteredOptions = filteredOptions.filter(function (option) {
        return stateSearch.test(option.label.replace('-', '').replace(/\s/g, ''));
      });

      if (filteredOptions.length > 1) {
        var countryKeyOptions = [];
        countryKeyOptions = filteredOptions.filter(function (option) {
          return countrySearch.test(option.key);
        });

        if (countryKeyOptions.length > 0) {
          filteredOptions = countryKeyOptions;
        }
      }

      if (filteredOptions.length > 1) {
        var stateKeyOptions = [];
        stateKeyOptions = filteredOptions.filter(function (option) {
          return stateSearch.test(option.key);
        });

        if (stateKeyOptions.length === 1) {
          filteredOptions = stateKeyOptions;
        }
      }
    }

    if (filteredOptions.length === 1 && countryState !== filteredOptions[0].key) {
      setValue('countryState', filteredOptions[0].key);
    }
  }, [autofillCountry, autofillState]);
  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_3__["createElement"])(_wordpress_element__WEBPACK_IMPORTED_MODULE_3__["Fragment"], null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_3__["createElement"])("input", {
    onChange: function onChange(event) {
      return setAutofillCountry(event.target.value);
    },
    value: autofillCountry,
    name: "country",
    type: "text",
    className: "woocommerce-select-control__autofill-input",
    tabIndex: "-1",
    autoComplete: "country"
  }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_3__["createElement"])("input", {
    onChange: function onChange(event) {
      return setAutofillState(event.target.value);
    },
    value: autofillState,
    name: "state",
    type: "text",
    className: "woocommerce-select-control__autofill-input",
    tabIndex: "-1",
    autoComplete: "address-level1"
  }));
}
/**
 * Store address fields.
 *
 * @param {Object} props Props for input components.
 * @return {Object} -
 */

function StoreAddress(props) {
  var getInputProps = props.getInputProps,
      setValue = props.setValue;
  var countryStateOptions = Object(react__WEBPACK_IMPORTED_MODULE_7__["useMemo"])(function () {
    return getCountryStateOptions();
  }, []);
  var countryStateAutofill = useGetCountryStateAutofill(countryStateOptions, getInputProps('countryState').value, setValue);
  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_3__["createElement"])("div", {
    className: "woocommerce-store-address-fields"
  }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_3__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_9__["TextControl"], _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0___default()({
    label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__["__"])('Address line 1', 'woocommerce'),
    required: true,
    autoComplete: "address-line1"
  }, getInputProps('addressLine1'))), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_3__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_9__["TextControl"], _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0___default()({
    label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__["__"])('Address line 2 (optional)', 'woocommerce'),
    required: true,
    autoComplete: "address-line2"
  }, getInputProps('addressLine2'))), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_3__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_9__["SelectControl"], _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0___default()({
    label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__["__"])('Country / Region', 'woocommerce'),
    required: true,
    options: countryStateOptions,
    excludeSelectedOptions: false,
    showAllOnFocus: true,
    isSearchable: true
  }, getInputProps('countryState'), {
    controlClassName: getInputProps('countryState').className
  }), countryStateAutofill), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_3__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_9__["TextControl"], _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0___default()({
    label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__["__"])('City', 'woocommerce'),
    required: true
  }, getInputProps('city'), {
    autoComplete: "address-level2"
  })), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_3__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_9__["TextControl"], _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0___default()({
    label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__["__"])('Post code', 'woocommerce'),
    required: true,
    autoComplete: "postal-code"
  }, getInputProps('postCode'))));
}

/***/ }),

/***/ 892:
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),

/***/ 904:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
// ESM COMPAT FLAG
__webpack_require__.r(__webpack_exports__);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/defineProperty.js
var defineProperty = __webpack_require__(15);
var defineProperty_default = /*#__PURE__*/__webpack_require__.n(defineProperty);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/classCallCheck.js
var classCallCheck = __webpack_require__(41);
var classCallCheck_default = /*#__PURE__*/__webpack_require__.n(classCallCheck);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/createClass.js
var createClass = __webpack_require__(40);
var createClass_default = /*#__PURE__*/__webpack_require__.n(createClass);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/possibleConstructorReturn.js
var possibleConstructorReturn = __webpack_require__(44);
var possibleConstructorReturn_default = /*#__PURE__*/__webpack_require__.n(possibleConstructorReturn);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/getPrototypeOf.js
var getPrototypeOf = __webpack_require__(29);
var getPrototypeOf_default = /*#__PURE__*/__webpack_require__.n(getPrototypeOf);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/inherits.js
var inherits = __webpack_require__(42);
var inherits_default = /*#__PURE__*/__webpack_require__.n(inherits);

// EXTERNAL MODULE: external {"this":["wp","element"]}
var external_this_wp_element_ = __webpack_require__(0);

// EXTERNAL MODULE: external {"this":["wp","i18n"]}
var external_this_wp_i18n_ = __webpack_require__(3);

// EXTERNAL MODULE: external "lodash"
var external_lodash_ = __webpack_require__(2);

// EXTERNAL MODULE: ./node_modules/@wordpress/compose/build-module/higher-order/compose.js
var compose = __webpack_require__(256);

// EXTERNAL MODULE: ./node_modules/classnames/index.js
var classnames = __webpack_require__(10);
var classnames_default = /*#__PURE__*/__webpack_require__.n(classnames);

// EXTERNAL MODULE: ./node_modules/@wordpress/components/build-module/snackbar/index.js
var snackbar = __webpack_require__(415);

// EXTERNAL MODULE: ./node_modules/@wordpress/components/build-module/button/index.js
var build_module_button = __webpack_require__(88);

// EXTERNAL MODULE: ./node_modules/@wordpress/components/build-module/icon/index.js
var icon = __webpack_require__(109);

// EXTERNAL MODULE: ./node_modules/@wordpress/components/build-module/modal/index.js + 3 modules
var modal = __webpack_require__(721);

// EXTERNAL MODULE: external {"this":["wp","data"]}
var external_this_wp_data_ = __webpack_require__(19);

// EXTERNAL MODULE: external {"this":["wc","components"]}
var external_this_wc_components_ = __webpack_require__(63);

// EXTERNAL MODULE: external {"this":["wc","navigation"]}
var external_this_wc_navigation_ = __webpack_require__(22);

// EXTERNAL MODULE: external {"this":["wc","data"]}
var external_this_wc_data_ = __webpack_require__(51);

// EXTERNAL MODULE: ./client/dashboard/task-list/style.scss
var style = __webpack_require__(892);

// EXTERNAL MODULE: external {"this":["wp","url"]}
var external_this_wp_url_ = __webpack_require__(30);

// EXTERNAL MODULE: external {"this":["wp","htmlEntities"]}
var external_this_wp_htmlEntities_ = __webpack_require__(69);

// EXTERNAL MODULE: ./client/settings/index.js
var client_settings = __webpack_require__(26);

// EXTERNAL MODULE: ./client/wc-api/with-select.js
var with_select = __webpack_require__(101);

// EXTERNAL MODULE: ./client/dashboard/utils.js
var utils = __webpack_require__(742);

// EXTERNAL MODULE: ./client/lib/sanitize-html/index.js
var sanitize_html = __webpack_require__(747);

// EXTERNAL MODULE: ./client/lib/tracks.js
var tracks = __webpack_require__(79);

// CONCATENATED MODULE: ./client/dashboard/components/cart-modal.js







function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function () { var Super = getPrototypeOf_default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = getPrototypeOf_default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return possibleConstructorReturn_default()(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/**
 * External dependencies
 */







/**
 * WooCommerce dependencies
 */




/**
 * Internal dependencies
 */






var cart_modal_CartModal = /*#__PURE__*/function (_Component) {
  inherits_default()(CartModal, _Component);

  var _super = _createSuper(CartModal);

  function CartModal(props) {
    var _this;

    classCallCheck_default()(this, CartModal);

    _this = _super.call(this, props);
    _this.state = {
      purchaseNowButtonBusy: false,
      purchaseLaterButtonBusy: false
    };
    return _this;
  }

  createClass_default()(CartModal, [{
    key: "onClickPurchaseNow",
    value: function onClickPurchaseNow() {
      var _this$props = this.props,
          productIds = _this$props.productIds,
          onClickPurchaseNow = _this$props.onClickPurchaseNow;
      this.setState({
        purchaseNowButtonBusy: true
      });

      if (!productIds.length) {
        return;
      }

      Object(tracks["b" /* recordEvent */])('tasklist_modal_proceed_checkout', {
        product_ids: productIds,
        purchase_install: true
      });

      var _getSetting = Object(client_settings["g" /* getSetting */])('onboarding', {}),
          connectNonce = _getSetting.connectNonce;

      var backPath = Object(external_this_wc_navigation_["getNewPath"])({}, '/', {});
      var url = Object(external_this_wp_url_["addQueryArgs"])('https://woocommerce.com/cart', {
        'wccom-site': Object(client_settings["g" /* getSetting */])('siteUrl'),
        'wccom-woo-version': Object(client_settings["g" /* getSetting */])('wcVersion'),
        'wccom-replace-with': productIds.join(','),
        'wccom-connect-nonce': connectNonce,
        'wccom-back': backPath
      });

      if (onClickPurchaseNow) {
        onClickPurchaseNow(url);
        return;
      }

      window.location = url;
    }
  }, {
    key: "onClickPurchaseLater",
    value: function onClickPurchaseLater() {
      var productIds = this.props.productIds;
      Object(tracks["b" /* recordEvent */])('tasklist_modal_proceed_checkout', {
        product_ids: productIds,
        purchase_install: false
      });
      this.setState({
        purchaseLaterButtonBusy: true
      });
      this.props.onClickPurchaseLater();
    }
  }, {
    key: "onClose",
    value: function onClose() {
      var _this$props2 = this.props,
          onClose = _this$props2.onClose,
          productIds = _this$props2.productIds;
      Object(tracks["b" /* recordEvent */])('tasklist_modal_proceed_checkout', {
        product_ids: productIds,
        purchase_install: false
      });
      onClose();
    }
  }, {
    key: "renderProducts",
    value: function renderProducts() {
      var productIds = this.props.productIds;

      var _getSetting2 = Object(client_settings["g" /* getSetting */])('onboarding', {}),
          _getSetting2$productT = _getSetting2.productTypes,
          productTypes = _getSetting2$productT === void 0 ? {} : _getSetting2$productT,
          _getSetting2$themes = _getSetting2.themes,
          themes = _getSetting2$themes === void 0 ? [] : _getSetting2$themes;

      var listItems = [];
      productIds.forEach(function (productId) {
        var productInfo = Object(external_lodash_["find"])(productTypes, function (productType) {
          return productType.product === productId;
        });

        if (productInfo) {
          listItems.push({
            title: productInfo.label,
            content: productInfo.description
          });
        }

        var themeInfo = Object(external_lodash_["find"])(themes, function (theme) {
          return theme.id === productId;
        });

        if (themeInfo) {
          listItems.push({
            title: Object(external_this_wp_i18n_["sprintf"])(Object(external_this_wp_i18n_["__"])('%s — %s per year', 'woocommerce'), themeInfo.title, Object(external_this_wp_htmlEntities_["decodeEntities"])(themeInfo.price)),
            content: Object(external_this_wp_element_["createElement"])("span", {
              dangerouslySetInnerHTML: Object(sanitize_html["a" /* default */])(themeInfo.excerpt)
            })
          });
        }
      });
      return Object(external_this_wp_element_["createElement"])(external_this_wc_components_["List"], {
        items: listItems
      });
    }
  }, {
    key: "render",
    value: function render() {
      var _this2 = this;

      var _this$state = this.state,
          purchaseNowButtonBusy = _this$state.purchaseNowButtonBusy,
          purchaseLaterButtonBusy = _this$state.purchaseLaterButtonBusy;
      return Object(external_this_wp_element_["createElement"])(modal["a" /* default */], {
        title: Object(external_this_wp_i18n_["__"])('Would you like to purchase and install the following features now?', 'woocommerce'),
        onRequestClose: function onRequestClose() {
          return _this2.onClose();
        },
        className: "woocommerce-cart-modal"
      }, this.renderProducts(), Object(external_this_wp_element_["createElement"])("p", {
        className: "woocommerce-cart-modal__help-text"
      }, Object(external_this_wp_i18n_["__"])("You won't have access to this functionality until the extensions have been purchased and installed.", 'woocommerce')), Object(external_this_wp_element_["createElement"])("div", {
        className: "woocommerce-cart-modal__actions"
      }, Object(external_this_wp_element_["createElement"])(build_module_button["a" /* default */], {
        isLink: true,
        isBusy: purchaseLaterButtonBusy,
        onClick: function onClick() {
          return _this2.onClickPurchaseLater();
        }
      }, Object(external_this_wp_i18n_["__"])("I'll do it later", 'woocommerce')), Object(external_this_wp_element_["createElement"])(build_module_button["a" /* default */], {
        isPrimary: true,
        isDefault: true,
        isBusy: purchaseNowButtonBusy,
        onClick: function onClick() {
          return _this2.onClickPurchaseNow();
        }
      }, Object(external_this_wp_i18n_["__"])('Purchase & install now', 'woocommerce'))));
    }
  }]);

  return CartModal;
}(external_this_wp_element_["Component"]);

/* harmony default export */ var cart_modal = (Object(compose["a" /* default */])(Object(with_select["a" /* default */])(function (select) {
  var _select = select('wc-api'),
      getProfileItems = _select.getProfileItems;

  var profileItems = getProfileItems();
  var productIds = Object(utils["d" /* getProductIdsForCart */])(profileItems);
  return {
    profileItems: profileItems,
    productIds: productIds
  };
}))(cart_modal_CartModal));
// EXTERNAL MODULE: external {"this":["wp","hooks"]}
var external_this_wp_hooks_ = __webpack_require__(48);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/asyncToGenerator.js
var asyncToGenerator = __webpack_require__(46);
var asyncToGenerator_default = /*#__PURE__*/__webpack_require__.n(asyncToGenerator);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/assertThisInitialized.js
var assertThisInitialized = __webpack_require__(59);
var assertThisInitialized_default = /*#__PURE__*/__webpack_require__.n(assertThisInitialized);

// EXTERNAL MODULE: external {"this":["wp","apiFetch"]}
var external_this_wp_apiFetch_ = __webpack_require__(20);
var external_this_wp_apiFetch_default = /*#__PURE__*/__webpack_require__.n(external_this_wp_apiFetch_);

// EXTERNAL MODULE: ./client/wc-api/constants.js
var constants = __webpack_require__(24);

// CONCATENATED MODULE: ./client/dashboard/task-list/tasks/appearance.js










function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { defineProperty_default()(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function appearance_createSuper(Derived) { var hasNativeReflectConstruct = appearance_isNativeReflectConstruct(); return function () { var Super = getPrototypeOf_default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = getPrototypeOf_default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return possibleConstructorReturn_default()(this, result); }; }

function appearance_isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/**
 * External dependencies
 */







/**
 * WooCommerce dependencies
 */




/**
 * Internal dependencies
 */





var appearance_Appearance = /*#__PURE__*/function (_Component) {
  inherits_default()(Appearance, _Component);

  var _super = appearance_createSuper(Appearance);

  function Appearance(props) {
    var _this;

    classCallCheck_default()(this, Appearance);

    _this = _super.call(this, props);

    var _getSetting = Object(client_settings["g" /* getSetting */])('onboarding', {}),
        hasHomepage = _getSetting.hasHomepage,
        hasProducts = _getSetting.hasProducts;

    _this.stepVisibility = {
      homepage: !hasHomepage,
      import: !hasProducts
    };
    _this.state = {
      isDirty: false,
      isPending: false,
      logo: null,
      stepIndex: 0,
      storeNoticeText: props.options.woocommerce_demo_store_notice || ''
    };
    _this.completeStep = _this.completeStep.bind(assertThisInitialized_default()(_this));
    _this.createHomepage = _this.createHomepage.bind(assertThisInitialized_default()(_this));
    _this.importProducts = _this.importProducts.bind(assertThisInitialized_default()(_this));
    _this.updateLogo = _this.updateLogo.bind(assertThisInitialized_default()(_this));
    _this.updateNotice = _this.updateNotice.bind(assertThisInitialized_default()(_this));
    return _this;
  }

  createClass_default()(Appearance, [{
    key: "componentDidMount",
    value: function componentDidMount() {
      var _getSetting2 = Object(client_settings["g" /* getSetting */])('onboarding', {}),
          themeMods = _getSetting2.themeMods;

      if (themeMods.custom_logo) {
        /* eslint-disable react/no-did-mount-set-state */
        this.setState({
          logo: {
            id: themeMods.custom_logo
          }
        });
        /* eslint-enable react/no-did-mount-set-state */
      }
    }
  }, {
    key: "componentDidUpdate",
    value: function () {
      var _componentDidUpdate = asyncToGenerator_default()( /*#__PURE__*/regeneratorRuntime.mark(function _callee(prevProps) {
        var _this2 = this;

        var _this$state, isPending, logo, stepIndex, _this$props, createNotice, errors, hasErrors, isRequesting, options, step, isRequestSuccessful, newErrors;

        return regeneratorRuntime.wrap(function _callee$(_context) {
          while (1) {
            switch (_context.prev = _context.next) {
              case 0:
                _this$state = this.state, isPending = _this$state.isPending, logo = _this$state.logo, stepIndex = _this$state.stepIndex;
                _this$props = this.props, createNotice = _this$props.createNotice, errors = _this$props.errors, hasErrors = _this$props.hasErrors, isRequesting = _this$props.isRequesting, options = _this$props.options;
                step = this.getSteps()[stepIndex].key;
                isRequestSuccessful = !isRequesting && prevProps.isRequesting && !hasErrors;

                if (logo && !logo.url && !isPending) {
                  /* eslint-disable react/no-did-update-set-state */
                  this.setState({
                    isPending: true
                  });
                  wp.media.attachment(logo.id).fetch().then(function () {
                    var logoUrl = wp.media.attachment(logo.id).get('url');

                    _this2.setState({
                      isPending: false,
                      logo: {
                        id: logo.id,
                        url: logoUrl
                      }
                    });
                  });
                  /* eslint-enable react/no-did-update-set-state */
                }

                if (options.woocommerce_demo_store_notice && prevProps.options.woocommerce_demo_store_notice !== options.woocommerce_demo_store_notice) {
                  /* eslint-disable react/no-did-update-set-state */
                  this.setState({
                    storeNoticeText: options.woocommerce_demo_store_notice
                  });
                  /* eslint-enable react/no-did-update-set-state */
                }

                if (step === 'logo' && isRequestSuccessful) {
                  createNotice('success', Object(external_this_wp_i18n_["__"])('Store logo updated sucessfully.', 'woocommerce'));
                  this.completeStep();
                }

                if (step === 'notice' && isRequestSuccessful) {
                  createNotice('success', Object(external_this_wp_i18n_["__"])("🎨 Your store is looking great! Don't forget to continue personalizing it.", 'woocommerce'));
                  this.completeStep();
                }

                newErrors = Object(external_lodash_["difference"])(errors, prevProps.errors);
                newErrors.map(function (error) {
                  return createNotice('error', error);
                });

              case 10:
              case "end":
                return _context.stop();
            }
          }
        }, _callee, this);
      }));

      function componentDidUpdate(_x) {
        return _componentDidUpdate.apply(this, arguments);
      }

      return componentDidUpdate;
    }()
  }, {
    key: "completeStep",
    value: function completeStep() {
      var stepIndex = this.state.stepIndex;
      var nextStep = this.getSteps()[stepIndex + 1];

      if (nextStep) {
        this.setState({
          stepIndex: stepIndex + 1
        });
      } else {
        Object(external_this_wc_navigation_["getHistory"])().push(Object(external_this_wc_navigation_["getNewPath"])({}, '/', {}));
      }
    }
  }, {
    key: "importProducts",
    value: function importProducts() {
      var _this3 = this;

      var createNotice = this.props.createNotice;
      this.setState({
        isPending: true
      });
      Object(tracks["b" /* recordEvent */])('tasklist_appearance_import_demo', {});
      external_this_wp_apiFetch_default()({
        path: "".concat(constants["f" /* WC_ADMIN_NAMESPACE */], "/onboarding/tasks/import_sample_products"),
        method: 'POST'
      }).then(function (result) {
        if (result.failed && result.failed.length) {
          createNotice('error', Object(external_this_wp_i18n_["__"])('There was an error importing some of the sample products.', 'woocommerce'));
        } else {
          createNotice('success', Object(external_this_wp_i18n_["__"])('All sample products have been imported.', 'woocommerce'));
          Object(client_settings["h" /* setSetting */])('onboarding', _objectSpread({}, Object(client_settings["g" /* getSetting */])('onboarding', {}), {
            hasProducts: true
          }));
        }

        _this3.setState({
          isPending: false
        });

        _this3.completeStep();
      }).catch(function (error) {
        createNotice('error', error.message);

        _this3.setState({
          isPending: false
        });
      });
    }
  }, {
    key: "createHomepage",
    value: function createHomepage() {
      var _this4 = this;

      var createNotice = this.props.createNotice;
      this.setState({
        isPending: true
      });
      Object(tracks["b" /* recordEvent */])('tasklist_appearance_create_homepage', {
        create_homepage: true
      });
      external_this_wp_apiFetch_default()({
        path: '/wc-admin/onboarding/tasks/create_homepage',
        method: 'POST'
      }).then(function (response) {
        createNotice(response.status, response.message, {
          actions: response.edit_post_link ? [{
            label: Object(external_this_wp_i18n_["__"])('Customize', 'woocommerce'),
            onClick: function onClick() {
              Object(tracks["a" /* queueRecordEvent */])('tasklist_appearance_customize_homepage', {});
              window.location = "".concat(response.edit_post_link, "&wc_onboarding_active_task=homepage");
            }
          }] : null
        });

        _this4.setState({
          isPending: false
        });

        _this4.completeStep();
      }).catch(function (error) {
        createNotice('error', error.message);

        _this4.setState({
          isPending: false
        });
      });
    }
  }, {
    key: "updateLogo",
    value: function updateLogo() {
      var updateOptions = this.props.updateOptions;
      var logo = this.state.logo;

      var _getSetting3 = Object(client_settings["g" /* getSetting */])('onboarding', {}),
          stylesheet = _getSetting3.stylesheet,
          themeMods = _getSetting3.themeMods;

      var updatedThemeMods = _objectSpread({}, themeMods, {
        custom_logo: logo ? logo.id : null
      });

      Object(tracks["b" /* recordEvent */])('tasklist_appearance_upload_logo');
      Object(client_settings["h" /* setSetting */])('onboarding', _objectSpread({}, Object(client_settings["g" /* getSetting */])('onboarding', {}), {
        themeMods: updatedThemeMods
      }));
      updateOptions(defineProperty_default()({}, "theme_mods_".concat(stylesheet), updatedThemeMods));
    }
  }, {
    key: "updateNotice",
    value: function updateNotice() {
      var updateOptions = this.props.updateOptions;
      var storeNoticeText = this.state.storeNoticeText;
      Object(tracks["b" /* recordEvent */])('tasklist_appearance_set_store_notice', {
        added_text: Boolean(storeNoticeText.length)
      });
      Object(client_settings["h" /* setSetting */])('onboarding', _objectSpread({}, Object(client_settings["g" /* getSetting */])('onboarding', {}), {
        isAppearanceComplete: true
      }));
      updateOptions({
        woocommerce_task_list_appearance_complete: true,
        woocommerce_demo_store: storeNoticeText.length ? 'yes' : 'no',
        woocommerce_demo_store_notice: storeNoticeText
      });
    }
  }, {
    key: "getSteps",
    value: function getSteps() {
      var _this5 = this;

      var _this$state2 = this.state,
          isDirty = _this$state2.isDirty,
          isPending = _this$state2.isPending,
          logo = _this$state2.logo,
          storeNoticeText = _this$state2.storeNoticeText;
      var isRequesting = this.props.isRequesting;
      var steps = [{
        key: 'import',
        label: Object(external_this_wp_i18n_["__"])('Import sample products', 'woocommerce'),
        description: Object(external_this_wp_i18n_["__"])('We’ll add some products that will make it easier to see what your store looks like', 'woocommerce'),
        content: Object(external_this_wp_element_["createElement"])(external_this_wp_element_["Fragment"], null, Object(external_this_wp_element_["createElement"])(build_module_button["a" /* default */], {
          onClick: this.importProducts,
          isBusy: isPending,
          isPrimary: true
        }, Object(external_this_wp_i18n_["__"])('Import products', 'woocommerce')), Object(external_this_wp_element_["createElement"])(build_module_button["a" /* default */], {
          onClick: function onClick() {
            return _this5.completeStep();
          }
        }, Object(external_this_wp_i18n_["__"])('Skip', 'woocommerce'))),
        visible: this.stepVisibility.import
      }, {
        key: 'homepage',
        label: Object(external_this_wp_i18n_["__"])('Create a custom homepage', 'woocommerce'),
        description: Object(external_this_wp_i18n_["__"])('Create a new homepage and customize it to suit your needs', 'woocommerce'),
        content: Object(external_this_wp_element_["createElement"])(external_this_wp_element_["Fragment"], null, Object(external_this_wp_element_["createElement"])(build_module_button["a" /* default */], {
          isPrimary: true,
          isBusy: isPending,
          onClick: this.createHomepage
        }, Object(external_this_wp_i18n_["__"])('Create homepage', 'woocommerce')), Object(external_this_wp_element_["createElement"])(build_module_button["a" /* default */], {
          onClick: function onClick() {
            Object(tracks["b" /* recordEvent */])('tasklist_appearance_create_homepage', {
              create_homepage: false
            });

            _this5.completeStep();
          }
        }, Object(external_this_wp_i18n_["__"])('Skip', 'woocommerce'))),
        visible: this.stepVisibility.homepage
      }, {
        key: 'logo',
        label: Object(external_this_wp_i18n_["__"])('Upload a logo', 'woocommerce'),
        description: Object(external_this_wp_i18n_["__"])('Ensure your store is on-brand by adding your logo', 'woocommerce'),
        content: isPending ? null : Object(external_this_wp_element_["createElement"])(external_this_wp_element_["Fragment"], null, Object(external_this_wp_element_["createElement"])(external_this_wc_components_["ImageUpload"], {
          image: logo,
          onChange: function onChange(image) {
            return _this5.setState({
              isDirty: true,
              logo: image
            });
          }
        }), Object(external_this_wp_element_["createElement"])(build_module_button["a" /* default */], {
          disabled: !logo && !isDirty,
          onClick: this.updateLogo,
          isBusy: isRequesting,
          isPrimary: true
        }, Object(external_this_wp_i18n_["__"])('Proceed', 'woocommerce')), Object(external_this_wp_element_["createElement"])(build_module_button["a" /* default */], {
          onClick: function onClick() {
            return _this5.completeStep();
          }
        }, Object(external_this_wp_i18n_["__"])('Skip', 'woocommerce'))),
        visible: true
      }, {
        key: 'notice',
        label: Object(external_this_wp_i18n_["__"])('Set a store notice', 'woocommerce'),
        description: Object(external_this_wp_i18n_["__"])('Optionally display a prominent notice across all pages of your store', 'woocommerce'),
        content: Object(external_this_wp_element_["createElement"])(external_this_wp_element_["Fragment"], null, Object(external_this_wp_element_["createElement"])(external_this_wc_components_["TextControl"], {
          label: Object(external_this_wp_i18n_["__"])('Store notice text', 'woocommerce'),
          placeholder: Object(external_this_wp_i18n_["__"])('Store notice text', 'woocommerce'),
          value: storeNoticeText,
          onChange: function onChange(value) {
            return _this5.setState({
              storeNoticeText: value
            });
          }
        }), Object(external_this_wp_element_["createElement"])(build_module_button["a" /* default */], {
          onClick: this.updateNotice,
          isPrimary: true
        }, Object(external_this_wp_i18n_["__"])('Complete task', 'woocommerce'))),
        visible: true
      }];
      return Object(external_lodash_["filter"])(steps, function (step) {
        return step.visible;
      });
    }
  }, {
    key: "render",
    value: function render() {
      var _this$state3 = this.state,
          isPending = _this$state3.isPending,
          stepIndex = _this$state3.stepIndex;
      var _this$props2 = this.props,
          isRequesting = _this$props2.isRequesting,
          hasErrors = _this$props2.hasErrors;
      var currentStep = this.getSteps()[stepIndex].key;
      return Object(external_this_wp_element_["createElement"])("div", {
        className: "woocommerce-task-appearance"
      }, Object(external_this_wp_element_["createElement"])(external_this_wc_components_["Card"], {
        className: "is-narrow"
      }, Object(external_this_wp_element_["createElement"])(external_this_wc_components_["Stepper"], {
        isPending: isRequesting && !hasErrors || isPending,
        isVertical: true,
        currentStep: currentStep,
        steps: this.getSteps()
      })));
    }
  }]);

  return Appearance;
}(external_this_wp_element_["Component"]);

/* harmony default export */ var appearance = (Object(compose["a" /* default */])(Object(with_select["a" /* default */])(function (select) {
  var _select = select('wc-api'),
      getOptions = _select.getOptions,
      getOptionsError = _select.getOptionsError,
      isUpdateOptionsRequesting = _select.isUpdateOptionsRequesting;

  var _getSetting4 = Object(client_settings["g" /* getSetting */])('onboarding', {}),
      stylesheet = _getSetting4.stylesheet;

  var options = getOptions(['woocommerce_demo_store', 'woocommerce_demo_store_notice']);
  var errors = [];
  var uploadLogoError = getOptionsError(["theme_mods_".concat(stylesheet)]);
  var storeNoticeError = getOptionsError(['woocommerce_demo_store', 'woocommerce_demo_store_notice']);

  if (uploadLogoError) {
    errors.push(uploadLogoError.message);
  }

  if (storeNoticeError) {
    errors.push(storeNoticeError.message);
  }

  var hasErrors = Boolean(errors.length);
  var isRequesting = Boolean(isUpdateOptionsRequesting(["theme_mods_".concat(stylesheet)])) || Boolean(isUpdateOptionsRequesting(['woocommerce_task_list_appearance_complete', 'woocommerce_demo_store', 'woocommerce_demo_store_notice']));
  return {
    errors: errors,
    getOptionsError: getOptionsError,
    hasErrors: hasErrors,
    isRequesting: isRequesting,
    options: options
  };
}), Object(external_this_wp_data_["withDispatch"])(function (dispatch) {
  var _dispatch = dispatch('core/notices'),
      createNotice = _dispatch.createNotice;

  var _dispatch2 = dispatch('wc-api'),
      updateOptions = _dispatch2.updateOptions;

  return {
    createNotice: createNotice,
    updateOptions: updateOptions
  };
}))(appearance_Appearance));
// CONCATENATED MODULE: ./client/dashboard/task-list/tasks/connect.js








function connect_ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function connect_objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { connect_ownKeys(Object(source), true).forEach(function (key) { defineProperty_default()(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { connect_ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function connect_createSuper(Derived) { var hasNativeReflectConstruct = connect_isNativeReflectConstruct(); return function () { var Super = getPrototypeOf_default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = getPrototypeOf_default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return possibleConstructorReturn_default()(this, result); }; }

function connect_isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/**
 * External dependencies
 */






/**
 * WooCommerce dependencies
 */


/**
 * Internal dependencies
 */




var connect_Connect = /*#__PURE__*/function (_Component) {
  inherits_default()(Connect, _Component);

  var _super = connect_createSuper(Connect);

  function Connect() {
    classCallCheck_default()(this, Connect);

    return _super.apply(this, arguments);
  }

  createClass_default()(Connect, [{
    key: "componentDidMount",
    value: function componentDidMount() {
      document.body.classList.add('woocommerce-admin-is-loading');
      var query = this.props.query;

      if (query.deny === '1') {
        this.errorMessage(Object(external_this_wp_i18n_["__"])('You must click approve to install your extensions and connect to WooCommerce.com.', 'woocommerce'));
        return;
      }

      if (!query['wccom-connected'] || !query.request_token) {
        this.request();
        return;
      }

      this.finish();
    }
  }, {
    key: "baseQuery",
    value: function baseQuery() {
      var query = this.props.query;
      var baseQuery = Object(external_lodash_["omit"])(connect_objectSpread({}, query, {
        page: 'wc-admin'
      }), ['task', 'wccom-connected', 'request_token', 'deny']);
      return Object(external_this_wc_navigation_["getNewPath"])({}, '/', baseQuery);
    }
  }, {
    key: "errorMessage",
    value: function errorMessage() {
      var message = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : Object(external_this_wp_i18n_["__"])('There was an error connecting to WooCommerce.com. Please try again.', 'woocommerce');
      document.body.classList.remove('woocommerce-admin-is-loading');
      Object(external_this_wc_navigation_["getHistory"])().push(this.baseQuery());
      this.props.createNotice('error', message);
    }
  }, {
    key: "request",
    value: function () {
      var _request = asyncToGenerator_default()( /*#__PURE__*/regeneratorRuntime.mark(function _callee() {
        var connectResponse;
        return regeneratorRuntime.wrap(function _callee$(_context) {
          while (1) {
            switch (_context.prev = _context.next) {
              case 0:
                _context.prev = 0;
                _context.next = 3;
                return external_this_wp_apiFetch_default()({
                  path: "".concat(constants["f" /* WC_ADMIN_NAMESPACE */], "/plugins/request-wccom-connect"),
                  method: 'POST'
                });

              case 3:
                connectResponse = _context.sent;

                if (!(connectResponse && connectResponse.connectAction)) {
                  _context.next = 7;
                  break;
                }

                window.location = connectResponse.connectAction;
                return _context.abrupt("return");

              case 7:
                throw new Error();

              case 10:
                _context.prev = 10;
                _context.t0 = _context["catch"](0);
                this.errorMessage();

              case 13:
              case "end":
                return _context.stop();
            }
          }
        }, _callee, this, [[0, 10]]);
      }));

      function request() {
        return _request.apply(this, arguments);
      }

      return request;
    }()
  }, {
    key: "finish",
    value: function () {
      var _finish = asyncToGenerator_default()( /*#__PURE__*/regeneratorRuntime.mark(function _callee2() {
        var query, connectResponse;
        return regeneratorRuntime.wrap(function _callee2$(_context2) {
          while (1) {
            switch (_context2.prev = _context2.next) {
              case 0:
                query = this.props.query;
                _context2.prev = 1;
                _context2.next = 4;
                return external_this_wp_apiFetch_default()({
                  path: "".concat(constants["f" /* WC_ADMIN_NAMESPACE */], "/plugins/finish-wccom-connect"),
                  method: 'POST',
                  data: {
                    request_token: query.request_token
                  }
                });

              case 4:
                connectResponse = _context2.sent;

                if (!(connectResponse && connectResponse.success)) {
                  _context2.next = 10;
                  break;
                }

                _context2.next = 8;
                return this.props.updateProfileItems({
                  wccom_connected: true
                });

              case 8:
                if (!this.props.isProfileItemsError) {
                  this.props.createNotice('success', Object(external_this_wp_i18n_["__"])('Store connected to WooCommerce.com and extensions are being installed.', 'woocommerce')); // @todo Show a notice for when extensions are correctly installed.

                  document.body.classList.remove('woocommerce-admin-is-loading');
                  Object(external_this_wc_navigation_["getHistory"])().push(this.baseQuery());
                } else {
                  this.errorMessage();
                }

                return _context2.abrupt("return");

              case 10:
                throw new Error();

              case 13:
                _context2.prev = 13;
                _context2.t0 = _context2["catch"](1);
                this.errorMessage();

              case 16:
              case "end":
                return _context2.stop();
            }
          }
        }, _callee2, this, [[1, 13]]);
      }));

      function finish() {
        return _finish.apply(this, arguments);
      }

      return finish;
    }()
  }, {
    key: "render",
    value: function render() {
      return null;
    }
  }]);

  return Connect;
}(external_this_wp_element_["Component"]);

/* harmony default export */ var tasks_connect = (Object(compose["a" /* default */])(Object(with_select["a" /* default */])(function (select) {
  var _select = select('wc-api'),
      getProfileItemsError = _select.getProfileItemsError;

  var isProfileItemsError = Boolean(getProfileItemsError());
  return {
    isProfileItemsError: isProfileItemsError
  };
}), Object(external_this_wp_data_["withDispatch"])(function (dispatch) {
  var _dispatch = dispatch('core/notices'),
      createNotice = _dispatch.createNotice;

  var _dispatch2 = dispatch('wc-api'),
      updateProfileItems = _dispatch2.updateProfileItems;

  return {
    createNotice: createNotice,
    updateProfileItems: updateProfileItems
  };
}))(connect_Connect));
// CONCATENATED MODULE: ./client/dashboard/task-list/tasks/products.js







function products_createSuper(Derived) { var hasNativeReflectConstruct = products_isNativeReflectConstruct(); return function () { var Super = getPrototypeOf_default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = getPrototypeOf_default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return possibleConstructorReturn_default()(this, result); }; }

function products_isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/**
 * External dependencies
 */


/**
 * WooCommerce dependencies
 */



/**
 * Internal dependencies
 */


var subTasks = [{
  title: Object(external_this_wp_i18n_["__"])('Add manually (recommended)', 'woocommerce'),
  content: Object(external_this_wp_i18n_["__"])('For small stores we recommend adding products manually', 'woocommerce'),
  before: Object(external_this_wp_element_["createElement"])("i", {
    className: "material-icons-outlined"
  }, "add_box"),
  after: Object(external_this_wp_element_["createElement"])("i", {
    className: "material-icons-outlined"
  }, "chevron_right"),
  onClick: function onClick() {
    return Object(tracks["b" /* recordEvent */])('tasklist_add_product', {
      method: 'manually'
    });
  },
  href: Object(client_settings["f" /* getAdminLink */])('post-new.php?post_type=product&wc_onboarding_active_task=products&tutorial=true')
}, {
  title: Object(external_this_wp_i18n_["__"])('Import', 'woocommerce'),
  content: Object(external_this_wp_i18n_["__"])('For larger stores we recommend importing all products at once via CSV file', 'woocommerce'),
  before: Object(external_this_wp_element_["createElement"])("i", {
    className: "material-icons-outlined"
  }, "import_export"),
  after: Object(external_this_wp_element_["createElement"])("i", {
    className: "material-icons-outlined"
  }, "chevron_right"),
  onClick: function onClick() {
    return Object(tracks["b" /* recordEvent */])('tasklist_add_product', {
      method: 'import'
    });
  },
  href: Object(client_settings["f" /* getAdminLink */])('edit.php?post_type=product&page=product_importer&wc_onboarding_active_task=product-import')
}, {
  title: Object(external_this_wp_i18n_["__"])('Migrate', 'woocommerce'),
  content: Object(external_this_wp_i18n_["__"])('For stores currently selling elsewhere we suggest using a product migration service', 'woocommerce'),
  before: Object(external_this_wp_element_["createElement"])("i", {
    className: "material-icons-outlined"
  }, "cloud_download"),
  after: Object(external_this_wp_element_["createElement"])("i", {
    className: "material-icons-outlined"
  }, "chevron_right"),
  onClick: function onClick() {
    return Object(tracks["b" /* recordEvent */])('tasklist_add_product', {
      method: 'migrate'
    });
  },
  // @todo This should be replaced with the in-app purchase iframe when ready.
  href: 'https://woocommerce.com/products/cart2cart/',
  target: '_blank'
}];

var products_Products = /*#__PURE__*/function (_Component) {
  inherits_default()(Products, _Component);

  var _super = products_createSuper(Products);

  function Products() {
    classCallCheck_default()(this, Products);

    return _super.apply(this, arguments);
  }

  createClass_default()(Products, [{
    key: "render",
    value: function render() {
      return Object(external_this_wp_element_["createElement"])(external_this_wp_element_["Fragment"], null, Object(external_this_wp_element_["createElement"])(external_this_wc_components_["Card"], {
        className: "woocommerce-task-card"
      }, Object(external_this_wp_element_["createElement"])(external_this_wc_components_["List"], {
        items: subTasks
      })));
    }
  }]);

  return Products;
}(external_this_wp_element_["Component"]);


// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/extends.js
var helpers_extends = __webpack_require__(105);
var extends_default = /*#__PURE__*/__webpack_require__.n(helpers_extends);

// EXTERNAL MODULE: ./node_modules/interpolate-components/lib/index.js
var lib = __webpack_require__(35);
var lib_default = /*#__PURE__*/__webpack_require__.n(lib);

// EXTERNAL MODULE: ./client/dashboard/components/connect/index.js
var components_connect = __webpack_require__(759);

// EXTERNAL MODULE: ./client/dashboard/components/settings/general/store-address.js
var store_address = __webpack_require__(769);

// CONCATENATED MODULE: ./client/dashboard/task-list/tasks/steps/location.js









function location_createSuper(Derived) { var hasNativeReflectConstruct = location_isNativeReflectConstruct(); return function () { var Super = getPrototypeOf_default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = getPrototypeOf_default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return possibleConstructorReturn_default()(this, result); }; }

function location_isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/**
 * External dependencies
 */



/**
 * WooCommerce dependencies
 */


/**
 * Internal dependencies
 */



var location_StoreLocation = /*#__PURE__*/function (_Component) {
  inherits_default()(StoreLocation, _Component);

  var _super = location_createSuper(StoreLocation);

  function StoreLocation() {
    var _this;

    classCallCheck_default()(this, StoreLocation);

    _this = _super.apply(this, arguments);
    _this.onSubmit = _this.onSubmit.bind(assertThisInitialized_default()(_this));
    return _this;
  }

  createClass_default()(StoreLocation, [{
    key: "onSubmit",
    value: function () {
      var _onSubmit = asyncToGenerator_default()( /*#__PURE__*/regeneratorRuntime.mark(function _callee(values) {
        var _this$props, onComplete, createNotice, isSettingsError, updateAndPersistSettingsForGroup;

        return regeneratorRuntime.wrap(function _callee$(_context) {
          while (1) {
            switch (_context.prev = _context.next) {
              case 0:
                _this$props = this.props, onComplete = _this$props.onComplete, createNotice = _this$props.createNotice, isSettingsError = _this$props.isSettingsError, updateAndPersistSettingsForGroup = _this$props.updateAndPersistSettingsForGroup;
                _context.next = 3;
                return updateAndPersistSettingsForGroup('general', {
                  general: {
                    woocommerce_store_address: values.addressLine1,
                    woocommerce_store_address_2: values.addressLine2,
                    woocommerce_default_country: values.countryState,
                    woocommerce_store_city: values.city,
                    woocommerce_store_postcode: values.postCode
                  }
                });

              case 3:
                if (!isSettingsError) {
                  onComplete(values);
                } else {
                  createNotice('error', Object(external_this_wp_i18n_["__"])('There was a problem saving your store location.', 'woocommerce'));
                }

              case 4:
              case "end":
                return _context.stop();
            }
          }
        }, _callee, this);
      }));

      function onSubmit(_x) {
        return _onSubmit.apply(this, arguments);
      }

      return onSubmit;
    }()
  }, {
    key: "getInitialValues",
    value: function getInitialValues() {
      var settings = this.props.settings;
      var storeAddress = settings.woocommerce_store_address,
          storeAddress2 = settings.woocommerce_store_address_2,
          storeCity = settings.woocommerce_store_city,
          defaultCountry = settings.woocommerce_default_country,
          storePostcode = settings.woocommerce_store_postcode;
      return {
        addressLine1: storeAddress || '',
        addressLine2: storeAddress2 || '',
        city: storeCity || '',
        countryState: defaultCountry || '',
        postCode: storePostcode || ''
      };
    }
  }, {
    key: "render",
    value: function render() {
      var isSettingsRequesting = this.props.isSettingsRequesting;

      if (isSettingsRequesting) {
        return null;
      }

      return Object(external_this_wp_element_["createElement"])(external_this_wc_components_["Form"], {
        initialValues: this.getInitialValues(),
        onSubmitCallback: this.onSubmit,
        validate: store_address["b" /* validateStoreAddress */]
      }, function (_ref) {
        var getInputProps = _ref.getInputProps,
            handleSubmit = _ref.handleSubmit,
            setValue = _ref.setValue;
        return Object(external_this_wp_element_["createElement"])(external_this_wp_element_["Fragment"], null, Object(external_this_wp_element_["createElement"])(store_address["a" /* StoreAddress */], {
          getInputProps: getInputProps,
          setValue: setValue
        }), Object(external_this_wp_element_["createElement"])(build_module_button["a" /* default */], {
          isPrimary: true,
          onClick: handleSubmit
        }, Object(external_this_wp_i18n_["__"])('Continue', 'woocommerce')));
      });
    }
  }]);

  return StoreLocation;
}(external_this_wp_element_["Component"]);


// EXTERNAL MODULE: ./node_modules/@wordpress/components/build-module/form-toggle/index.js
var form_toggle = __webpack_require__(718);

// EXTERNAL MODULE: ./node_modules/prop-types/index.js
var prop_types = __webpack_require__(1);
var prop_types_default = /*#__PURE__*/__webpack_require__.n(prop_types);

// EXTERNAL MODULE: ./client/lib/currency-context.js
var currency_context = __webpack_require__(203);

// CONCATENATED MODULE: ./client/dashboard/task-list/tasks/shipping/rates.js











function rates_ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function rates_objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { rates_ownKeys(Object(source), true).forEach(function (key) { defineProperty_default()(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { rates_ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function rates_createSuper(Derived) { var hasNativeReflectConstruct = rates_isNativeReflectConstruct(); return function () { var Super = getPrototypeOf_default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = getPrototypeOf_default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return possibleConstructorReturn_default()(this, result); }; }

function rates_isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/**
 * External dependencies
 */





/**
 * WooCommerce dependencies
 */



/**
 * Internal dependencies
 */




var rates_ShippingRates = /*#__PURE__*/function (_Component) {
  inherits_default()(ShippingRates, _Component);

  var _super = rates_createSuper(ShippingRates);

  function ShippingRates() {
    var _this;

    classCallCheck_default()(this, ShippingRates);

    _this = _super.apply(this, arguments);
    _this.updateShippingZones = _this.updateShippingZones.bind(assertThisInitialized_default()(_this));
    return _this;
  }

  createClass_default()(ShippingRates, [{
    key: "getShippingMethods",
    value: function getShippingMethods(zone) {
      var type = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : null;

      // Sometimes the wc/v3/shipping/zones response does not include a methods attribute, return early if so.
      if (!zone || !zone.methods || !Array.isArray(zone.methods)) {
        return [];
      }

      if (!type) {
        return zone.methods;
      }

      return zone.methods ? zone.methods.filter(function (method) {
        return method.method_id === type;
      }) : [];
    }
  }, {
    key: "disableShippingMethods",
    value: function disableShippingMethods(zone, methods) {
      if (!methods.length) {
        return;
      }

      methods.forEach(function (method) {
        external_this_wp_apiFetch_default()({
          method: 'POST',
          path: "/wc/v3/shipping/zones/".concat(zone.id, "/methods/").concat(method.instance_id),
          data: {
            enabled: false
          }
        });
      });
    }
  }, {
    key: "updateShippingZones",
    value: function () {
      var _updateShippingZones = asyncToGenerator_default()( /*#__PURE__*/regeneratorRuntime.mark(function _callee(values) {
        var _this2 = this;

        var _this$props, createNotice, shippingZones, restOfTheWorld, shippingCost;

        return regeneratorRuntime.wrap(function _callee$(_context) {
          while (1) {
            switch (_context.prev = _context.next) {
              case 0:
                _this$props = this.props, createNotice = _this$props.createNotice, shippingZones = _this$props.shippingZones;
                restOfTheWorld = false;
                shippingCost = false;
                shippingZones.forEach(function (zone) {
                  if (zone.id === 0) {
                    restOfTheWorld = zone.toggleable && values["".concat(zone.id, "_enabled")];
                  } else {
                    shippingCost = values["".concat(zone.id, "_rate")] !== '' && parseFloat(values["".concat(zone.id, "_rate")]) !== parseFloat(0);
                  }

                  var shippingMethods = _this2.getShippingMethods(zone);

                  var methodType = parseFloat(values["".concat(zone.id, "_rate")]) === parseFloat(0) ? 'free_shipping' : 'flat_rate';
                  var shippingMethod = _this2.getShippingMethods(zone, methodType).length ? _this2.getShippingMethods(zone, methodType)[0] : null;

                  if (zone.toggleable && !values["".concat(zone.id, "_enabled")]) {
                    // Disable any shipping methods that exist if toggled off.
                    _this2.disableShippingMethods(zone, shippingMethods);

                    return;
                  } else if (shippingMethod) {
                    // Disable all methods except the one being updated.
                    var methodsToDisable = shippingMethods.filter(function (method) {
                      return method.instance_id !== shippingMethod.instance_id;
                    });

                    _this2.disableShippingMethods(zone, methodsToDisable);
                  }

                  external_this_wp_apiFetch_default()({
                    method: 'POST',
                    path: shippingMethod ? // Update the first existing method if one exists, otherwise create a new one.
                    "/wc/v3/shipping/zones/".concat(zone.id, "/methods/").concat(shippingMethod.instance_id) : "/wc/v3/shipping/zones/".concat(zone.id, "/methods"),
                    data: {
                      method_id: methodType,
                      enabled: true,
                      settings: {
                        cost: values["".concat(zone.id, "_rate")]
                      }
                    }
                  });
                });
                Object(tracks["b" /* recordEvent */])('tasklist_shipping_set_costs', {
                  shipping_cost: shippingCost,
                  rest_world: restOfTheWorld
                }); // @todo This is a workaround to force the task to mark as complete.
                // This should probably be updated to use wc-api so we can fetch shipping methods.

                Object(client_settings["h" /* setSetting */])('onboarding', rates_objectSpread({}, Object(client_settings["g" /* getSetting */])('onboarding', {}), {
                  shippingZonesCount: 1
                }));
                createNotice('success', Object(external_this_wp_i18n_["__"])('Your shipping rates have been updated.', 'woocommerce'));
                this.props.onComplete();

              case 8:
              case "end":
                return _context.stop();
            }
          }
        }, _callee, this);
      }));

      function updateShippingZones(_x) {
        return _updateShippingZones.apply(this, arguments);
      }

      return updateShippingZones;
    }()
  }, {
    key: "renderInputPrefix",
    value: function renderInputPrefix() {
      var _this$context$getCurr = this.context.getCurrency(),
          symbolPosition = _this$context$getCurr.symbolPosition,
          symbol = _this$context$getCurr.symbol;

      if (symbolPosition.indexOf('right') === 0) {
        return null;
      }

      return Object(external_this_wp_element_["createElement"])("span", {
        className: "woocommerce-shipping-rate__control-prefix"
      }, symbol);
    }
  }, {
    key: "renderInputSuffix",
    value: function renderInputSuffix(rate) {
      var _this$context$getCurr2 = this.context.getCurrency(),
          symbolPosition = _this$context$getCurr2.symbolPosition,
          symbol = _this$context$getCurr2.symbol;

      if (symbolPosition.indexOf('right') === 0) {
        return Object(external_this_wp_element_["createElement"])("span", {
          className: "woocommerce-shipping-rate__control-suffix"
        }, symbol);
      }

      return parseFloat(rate) === parseFloat(0) ? Object(external_this_wp_element_["createElement"])("span", {
        className: "woocommerce-shipping-rate__control-suffix"
      }, Object(external_this_wp_i18n_["__"])('Free shipping', 'woocommerce')) : null;
    }
  }, {
    key: "getFormattedRate",
    value: function getFormattedRate(value) {
      var formatDecimalString = this.context.formatDecimalString;
      var currencyString = formatDecimalString(value);

      if (!value.length || !currencyString.length) {
        return formatDecimalString(0);
      }

      return formatDecimalString(value);
    }
  }, {
    key: "getInitialValues",
    value: function getInitialValues() {
      var _this3 = this;

      var formatDecimalString = this.context.formatDecimalString;
      var values = {};
      this.props.shippingZones.forEach(function (zone) {
        var shippingMethods = _this3.getShippingMethods(zone);

        var rate = shippingMethods.length && shippingMethods[0].settings.cost ? _this3.getFormattedRate(shippingMethods[0].settings.cost.value) : formatDecimalString(0);
        values["".concat(zone.id, "_rate")] = rate;

        if (shippingMethods.length && shippingMethods[0].enabled) {
          values["".concat(zone.id, "_enabled")] = true;
        } else {
          values["".concat(zone.id, "_enabled")] = false;
        }
      });
      return values;
    }
  }, {
    key: "validate",
    value: function validate(values) {
      var errors = {};
      var rates = Object.keys(values).filter(function (field) {
        return field.endsWith('_rate');
      });
      rates.forEach(function (rate) {
        if (values[rate] < 0) {
          errors[rate] = Object(external_this_wp_i18n_["__"])('Shipping rates can not be negative numbers.', 'woocommerce');
        }
      });
      return errors;
    }
  }, {
    key: "render",
    value: function render() {
      var _this4 = this;

      var _this$props2 = this.props,
          buttonText = _this$props2.buttonText,
          shippingZones = _this$props2.shippingZones;

      if (!shippingZones.length) {
        return null;
      }

      return Object(external_this_wp_element_["createElement"])(external_this_wc_components_["Form"], {
        initialValues: this.getInitialValues(),
        onSubmitCallback: this.updateShippingZones,
        validate: this.validate
      }, function (_ref) {
        var getInputProps = _ref.getInputProps,
            handleSubmit = _ref.handleSubmit,
            setTouched = _ref.setTouched,
            setValue = _ref.setValue,
            values = _ref.values;
        return Object(external_this_wp_element_["createElement"])(external_this_wp_element_["Fragment"], null, Object(external_this_wp_element_["createElement"])("div", {
          className: "woocommerce-shipping-rates"
        }, shippingZones.map(function (zone) {
          return Object(external_this_wp_element_["createElement"])("div", {
            className: "woocommerce-shipping-rate",
            key: zone.id
          }, Object(external_this_wp_element_["createElement"])("div", {
            className: "woocommerce-shipping-rate__icon"
          }, zone.locations ? zone.locations.map(function (location) {
            return Object(external_this_wp_element_["createElement"])(external_this_wc_components_["Flag"], {
              size: 24,
              code: location.code,
              key: location.code
            });
          }) : // Icon used for zones without locations or "Rest of the world".
          Object(external_this_wp_element_["createElement"])("i", {
            className: "material-icons-outlined"
          }, "public")), Object(external_this_wp_element_["createElement"])("div", {
            className: "woocommerce-shipping-rate__main"
          }, Object(external_this_wp_element_["createElement"])("div", {
            className: "woocommerce-shipping-rate__name"
          }, zone.name, zone.toggleable && Object(external_this_wp_element_["createElement"])(form_toggle["a" /* default */], getInputProps("".concat(zone.id, "_enabled")))), (!zone.toggleable || values["".concat(zone.id, "_enabled")]) && Object(external_this_wp_element_["createElement"])(external_this_wc_components_["TextControlWithAffixes"], extends_default()({
            label: Object(external_this_wp_i18n_["__"])('Shipping cost', 'woocommerce'),
            required: true
          }, getInputProps("".concat(zone.id, "_rate")), {
            onBlur: function onBlur() {
              setTouched("".concat(zone.id, "_rate"));
              setValue("".concat(zone.id, "_rate"), _this4.getFormattedRate(values["".concat(zone.id, "_rate")]));
            },
            prefix: _this4.renderInputPrefix(),
            suffix: _this4.renderInputSuffix(values["".concat(zone.id, "_rate")]),
            className: "muriel-input-text woocommerce-shipping-rate__control-wrapper"
          }))));
        })), Object(external_this_wp_element_["createElement"])(build_module_button["a" /* default */], {
          isPrimary: true,
          onClick: handleSubmit
        }, buttonText || Object(external_this_wp_i18n_["__"])('Update', 'woocommerce')));
      });
    }
  }]);

  return ShippingRates;
}(external_this_wp_element_["Component"]);

rates_ShippingRates.propTypes = {
  /**
   * Text displayed on the primary button.
   */
  buttonText: prop_types_default.a.string,

  /**
   * Function used to mark the step complete.
   */
  onComplete: prop_types_default.a.func.isRequired,

  /**
   * Function to create a transient notice in the store.
   */
  createNotice: prop_types_default.a.func.isRequired,

  /**
   * Array of shipping zones returned from the WC REST API with added
   * `methods` and `locations` properties appended from separate API calls.
   */
  shippingZones: prop_types_default.a.array
};
rates_ShippingRates.defaultProps = {
  shippingZones: []
};
rates_ShippingRates.contextType = currency_context["a" /* CurrencyContext */];
/* harmony default export */ var shipping_rates = (rates_ShippingRates);
// CONCATENATED MODULE: ./client/dashboard/task-list/tasks/shipping/index.js










function shipping_createSuper(Derived) { var hasNativeReflectConstruct = shipping_isNativeReflectConstruct(); return function () { var Super = getPrototypeOf_default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = getPrototypeOf_default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return possibleConstructorReturn_default()(this, result); }; }

function shipping_isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/**
 * External dependencies
 */







/**
 * WooCommerce dependencies
 */





/**
 * Internal dependencies
 */







var shipping_Shipping = /*#__PURE__*/function (_Component) {
  inherits_default()(Shipping, _Component);

  var _super = shipping_createSuper(Shipping);

  function Shipping(props) {
    var _this;

    classCallCheck_default()(this, Shipping);

    _this = _super.call(this, props);
    _this.initialState = {
      isPending: false,
      step: 'store_location',
      shippingZones: []
    }; // Cache active plugins to prevent removal mid-step.

    _this.activePlugins = props.activePlugins;
    _this.state = _this.initialState;
    _this.completeStep = _this.completeStep.bind(assertThisInitialized_default()(_this));
    return _this;
  }

  createClass_default()(Shipping, [{
    key: "componentDidMount",
    value: function componentDidMount() {
      this.reset();
    }
  }, {
    key: "reset",
    value: function reset() {
      this.setState(this.initialState);
    }
  }, {
    key: "fetchShippingZones",
    value: function () {
      var _fetchShippingZones = asyncToGenerator_default()( /*#__PURE__*/regeneratorRuntime.mark(function _callee2() {
        var _this$props, countryCode, countryName, shippingZones, zones, hasCountryZone, zone;

        return regeneratorRuntime.wrap(function _callee2$(_context2) {
          while (1) {
            switch (_context2.prev = _context2.next) {
              case 0:
                this.setState({
                  isPending: true
                });
                _this$props = this.props, countryCode = _this$props.countryCode, countryName = _this$props.countryName; // @todo The following fetches for shipping information should be moved into
                // the wc-api to make these methods and states more readily available.

                shippingZones = [];
                _context2.next = 5;
                return external_this_wp_apiFetch_default()({
                  path: '/wc/v3/shipping/zones'
                });

              case 5:
                zones = _context2.sent;
                hasCountryZone = false;
                _context2.next = 9;
                return Promise.all(zones.map( /*#__PURE__*/function () {
                  var _ref = asyncToGenerator_default()( /*#__PURE__*/regeneratorRuntime.mark(function _callee(zone) {
                    var countryLocation;
                    return regeneratorRuntime.wrap(function _callee$(_context) {
                      while (1) {
                        switch (_context.prev = _context.next) {
                          case 0:
                            if (!(zone.id === 0)) {
                              _context.next = 8;
                              break;
                            }

                            _context.next = 3;
                            return external_this_wp_apiFetch_default()({
                              path: "/wc/v3/shipping/zones/".concat(zone.id, "/methods")
                            });

                          case 3:
                            zone.methods = _context.sent;
                            zone.name = Object(external_this_wp_i18n_["__"])('Rest of the world', 'woocommerce');
                            zone.toggleable = true;
                            shippingZones.push(zone);
                            return _context.abrupt("return");

                          case 8:
                            _context.next = 10;
                            return external_this_wp_apiFetch_default()({
                              path: "/wc/v3/shipping/zones/".concat(zone.id, "/locations")
                            });

                          case 10:
                            zone.locations = _context.sent;
                            countryLocation = zone.locations.find(function (location) {
                              return countryCode === location.code;
                            });

                            if (!countryLocation) {
                              _context.next = 18;
                              break;
                            }

                            _context.next = 15;
                            return external_this_wp_apiFetch_default()({
                              path: "/wc/v3/shipping/zones/".concat(zone.id, "/methods")
                            });

                          case 15:
                            zone.methods = _context.sent;
                            shippingZones.push(zone);
                            hasCountryZone = true;

                          case 18:
                          case "end":
                            return _context.stop();
                        }
                      }
                    }, _callee);
                  }));

                  return function (_x) {
                    return _ref.apply(this, arguments);
                  };
                }()));

              case 9:
                if (hasCountryZone) {
                  _context2.next = 17;
                  break;
                }

                _context2.next = 12;
                return external_this_wp_apiFetch_default()({
                  method: 'POST',
                  path: '/wc/v3/shipping/zones',
                  data: {
                    name: countryName
                  }
                });

              case 12:
                zone = _context2.sent;
                _context2.next = 15;
                return external_this_wp_apiFetch_default()({
                  method: 'POST',
                  path: "/wc/v3/shipping/zones/".concat(zone.id, "/locations"),
                  data: [{
                    code: countryCode,
                    type: 'country'
                  }]
                });

              case 15:
                zone.locations = _context2.sent;
                shippingZones.push(zone);

              case 17:
                shippingZones.reverse();
                this.setState({
                  isPending: false,
                  shippingZones: shippingZones
                });

              case 19:
              case "end":
                return _context2.stop();
            }
          }
        }, _callee2, this);
      }));

      function fetchShippingZones() {
        return _fetchShippingZones.apply(this, arguments);
      }

      return fetchShippingZones;
    }()
  }, {
    key: "componentDidUpdate",
    value: function componentDidUpdate(prevProps, prevState) {
      var countryCode = this.props.countryCode;
      var step = this.state.step;

      if (step === 'rates' && (prevProps.countryCode !== countryCode || prevState.step !== 'rates')) {
        this.fetchShippingZones();
      }
    }
  }, {
    key: "completeStep",
    value: function completeStep() {
      var createNotice = this.props.createNotice;
      var step = this.state.step;
      var steps = this.getSteps();
      var currentStepIndex = steps.findIndex(function (s) {
        return s.key === step;
      });
      var nextStep = steps[currentStepIndex + 1];

      if (nextStep) {
        this.setState({
          step: nextStep.key
        });
      } else {
        createNotice('success', Object(external_this_wp_i18n_["__"])("📦 Shipping is done! Don't worry, you can always change it later.", 'woocommerce'));
        Object(external_this_wc_navigation_["getHistory"])().push(Object(external_this_wc_navigation_["getNewPath"])({}, '/', {}));
      }
    }
  }, {
    key: "getPluginsToActivate",
    value: function getPluginsToActivate() {
      var _this$props2 = this.props,
          countryCode = _this$props2.countryCode,
          isJetpackConnected = _this$props2.isJetpackConnected;
      var plugins = [];

      if (['GB', 'CA', 'AU'].includes(countryCode)) {
        plugins.push('woocommerce-shipstation-integration');
      } else if (countryCode === 'US') {
        plugins.push('woocommerce-services');

        if (!isJetpackConnected) {
          plugins.push('jetpack');
        }
      }

      return Object(external_lodash_["difference"])(plugins, this.activePlugins);
    }
  }, {
    key: "getSteps",
    value: function getSteps() {
      var _this2 = this;

      var pluginsToActivate = this.getPluginsToActivate();
      var steps = [{
        key: 'store_location',
        label: Object(external_this_wp_i18n_["__"])('Set store location', 'woocommerce'),
        description: Object(external_this_wp_i18n_["__"])('The address from which your business operates', 'woocommerce'),
        content: Object(external_this_wp_element_["createElement"])(location_StoreLocation, extends_default()({}, this.props, {
          onComplete: function onComplete(values) {
            var country = Object(utils["a" /* getCountryCode */])(values.countryState);
            Object(tracks["b" /* recordEvent */])('tasklist_shipping_set_location', {
              country: country
            });

            _this2.completeStep();
          }
        })),
        visible: true
      }, {
        key: 'rates',
        label: Object(external_this_wp_i18n_["__"])('Set shipping costs', 'woocommerce'),
        description: Object(external_this_wp_i18n_["__"])('Define how much customers pay to ship to different destinations', 'woocommerce'),
        content: Object(external_this_wp_element_["createElement"])(shipping_rates, extends_default()({
          buttonText: pluginsToActivate.length ? Object(external_this_wp_i18n_["__"])('Proceed', 'woocommerce') : Object(external_this_wp_i18n_["__"])('Complete task', 'woocommerce'),
          shippingZones: this.state.shippingZones,
          onComplete: this.completeStep
        }, this.props)),
        visible: true
      }, {
        key: 'label_printing',
        label: Object(external_this_wp_i18n_["__"])('Enable shipping label printing', 'woocommerce'),
        description: pluginsToActivate.includes('woocommerce-shipstation-integration') ? lib_default()({
          mixedString: Object(external_this_wp_i18n_["__"])('We recommend using ShipStation to save time at the post office by printing your shipping ' + 'labels at home. Try ShipStation free for 30 days. {{link}}Learn more{{/link}}.', 'woocommerce'),
          components: {
            link: Object(external_this_wp_element_["createElement"])(external_this_wc_components_["Link"], {
              href: "https://woocommerce.com/products/shipstation-integration",
              target: "_blank",
              type: "external"
            })
          }
        }) : Object(external_this_wp_i18n_["__"])('With WooCommerce Services and Jetpack you can save time at the ' + 'Post Office by printing your shipping labels at home', 'woocommerce'),
        content: Object(external_this_wp_element_["createElement"])(external_this_wc_components_["Plugins"], extends_default()({
          onComplete: function onComplete() {
            Object(tracks["b" /* recordEvent */])('tasklist_shipping_label_printing', {
              install: true,
              pluginsToActivate: pluginsToActivate
            });

            _this2.completeStep();
          },
          onSkip: function onSkip() {
            Object(tracks["b" /* recordEvent */])('tasklist_shipping_label_printing', {
              install: false,
              pluginsToActivate: pluginsToActivate
            });
            Object(external_this_wc_navigation_["getHistory"])().push(Object(external_this_wc_navigation_["getNewPath"])({}, '/', {}));
          },
          pluginSlugs: pluginsToActivate
        }, this.props)),
        visible: pluginsToActivate.length
      }, {
        key: 'connect',
        label: Object(external_this_wp_i18n_["__"])('Connect your store', 'woocommerce'),
        description: Object(external_this_wp_i18n_["__"])('Connect your store to WordPress.com to enable label printing', 'woocommerce'),
        content: Object(external_this_wp_element_["createElement"])(components_connect["a" /* default */], extends_default()({
          redirectUrl: Object(client_settings["f" /* getAdminLink */])('admin.php?page=wc-admin'),
          completeStep: this.completeStep
        }, this.props, {
          onConnect: function onConnect() {
            Object(tracks["b" /* recordEvent */])('tasklist_shipping_connect_store');
          }
        })),
        visible: pluginsToActivate.includes('jetpack')
      }];
      return Object(external_lodash_["filter"])(steps, function (step) {
        return step.visible;
      });
    }
  }, {
    key: "render",
    value: function render() {
      var _this$state = this.state,
          isPending = _this$state.isPending,
          step = _this$state.step;
      var isSettingsRequesting = this.props.isSettingsRequesting;
      return Object(external_this_wp_element_["createElement"])("div", {
        className: "woocommerce-task-shipping"
      }, Object(external_this_wp_element_["createElement"])(external_this_wc_components_["Card"], {
        className: "is-narrow"
      }, Object(external_this_wp_element_["createElement"])(external_this_wc_components_["Stepper"], {
        isPending: isPending || isSettingsRequesting,
        isVertical: true,
        currentStep: step,
        steps: this.getSteps()
      })));
    }
  }]);

  return Shipping;
}(external_this_wp_element_["Component"]);

/* harmony default export */ var shipping = (Object(compose["a" /* default */])(Object(external_this_wp_data_["withSelect"])(function (select) {
  var _select = select(external_this_wc_data_["SETTINGS_STORE_NAME"]),
      getSettings = _select.getSettings,
      getSettingsError = _select.getSettingsError,
      isGetSettingsRequesting = _select.isGetSettingsRequesting;

  var _select2 = select(external_this_wc_data_["PLUGINS_STORE_NAME"]),
      getActivePlugins = _select2.getActivePlugins,
      isJetpackConnected = _select2.isJetpackConnected;

  var _getSettings = getSettings('general'),
      _getSettings$general = _getSettings.general,
      settings = _getSettings$general === void 0 ? {} : _getSettings$general;

  var isSettingsError = Boolean(getSettingsError('general'));
  var isSettingsRequesting = isGetSettingsRequesting('general');
  var countryCode = Object(utils["a" /* getCountryCode */])(settings.woocommerce_default_country);

  var _getSetting = Object(client_settings["g" /* getSetting */])('dataEndpoints', {}),
      _getSetting$countries = _getSetting.countries,
      countries = _getSetting$countries === void 0 ? [] : _getSetting$countries;

  var country = countryCode ? countries.find(function (c) {
    return c.code === countryCode;
  }) : null;
  var countryName = country ? country.name : null;
  var activePlugins = getActivePlugins();
  return {
    countryCode: countryCode,
    countryName: countryName,
    isSettingsError: isSettingsError,
    isSettingsRequesting: isSettingsRequesting,
    settings: settings,
    activePlugins: activePlugins,
    isJetpackConnected: isJetpackConnected()
  };
}), Object(external_this_wp_data_["withDispatch"])(function (dispatch) {
  var _dispatch = dispatch('core/notices'),
      createNotice = _dispatch.createNotice;

  var _dispatch2 = dispatch(external_this_wc_data_["SETTINGS_STORE_NAME"]),
      updateAndPersistSettingsForGroup = _dispatch2.updateAndPersistSettingsForGroup;

  return {
    createNotice: createNotice,
    updateAndPersistSettingsForGroup: updateAndPersistSettingsForGroup
  };
}))(shipping_Shipping));
// CONCATENATED MODULE: ./client/dashboard/task-list/tasks/tax.js











function tax_ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function tax_objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { tax_ownKeys(Object(source), true).forEach(function (key) { defineProperty_default()(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { tax_ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function tax_createSuper(Derived) { var hasNativeReflectConstruct = tax_isNativeReflectConstruct(); return function () { var Super = getPrototypeOf_default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = getPrototypeOf_default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return possibleConstructorReturn_default()(this, result); }; }

function tax_isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/**
 * External dependencies
 */







/**
 * WooCommerce dependencies
 */





/**
 * Internal dependencies
 */







var tax_Tax = /*#__PURE__*/function (_Component) {
  inherits_default()(Tax, _Component);

  var _super = tax_createSuper(Tax);

  function Tax(props) {
    var _this;

    classCallCheck_default()(this, Tax);

    _this = _super.call(this, props);
    _this.initialState = {
      isPending: false,
      stepIndex: 0,
      automatedTaxEnabled: true,
      // Cache the value of pluginsToActivate so that we can show/hide tasks based on it, but not have them update mid task.
      pluginsToActivate: props.pluginsToActivate
    };
    _this.state = _this.initialState;
    _this.completeStep = _this.completeStep.bind(assertThisInitialized_default()(_this));
    _this.configureTaxRates = _this.configureTaxRates.bind(assertThisInitialized_default()(_this));
    _this.updateAutomatedTax = _this.updateAutomatedTax.bind(assertThisInitialized_default()(_this));
    _this.setIsPending = _this.setIsPending.bind(assertThisInitialized_default()(_this));
    _this.shouldShowSuccessScreen = _this.shouldShowSuccessScreen.bind(assertThisInitialized_default()(_this));
    return _this;
  }

  createClass_default()(Tax, [{
    key: "componentDidMount",
    value: function componentDidMount() {
      this.reset();
    }
  }, {
    key: "reset",
    value: function reset() {
      this.setState(this.initialState);
    }
  }, {
    key: "shouldShowSuccessScreen",
    value: function shouldShowSuccessScreen() {
      var stepIndex = this.state.stepIndex;
      var _this$props = this.props,
          isJetpackConnected = _this$props.isJetpackConnected,
          pluginsToActivate = _this$props.pluginsToActivate,
          generalSettings = _this$props.generalSettings;
      var storeAddress = generalSettings.woocommerce_store_address,
          defaultCountry = generalSettings.woocommerce_default_country,
          storePostCode = generalSettings.woocommerce_store_postcode;
      var isCompleteAddress = Boolean(storeAddress && defaultCountry && storePostCode);
      return stepIndex !== null && isCompleteAddress && !pluginsToActivate.length && isJetpackConnected && this.isTaxJarSupported();
    }
  }, {
    key: "componentDidUpdate",
    value: function componentDidUpdate(prevProps) {
      var _this$props2 = this.props,
          generalSettings = _this$props2.generalSettings,
          isJetpackConnected = _this$props2.isJetpackConnected,
          taxSettings = _this$props2.taxSettings,
          isGeneralSettingsRequesting = _this$props2.isGeneralSettingsRequesting;
      var calcTaxes = generalSettings.woocommerce_calc_taxes;
      var stepIndex = this.state.stepIndex;
      var currentStep = this.getSteps()[stepIndex];
      var currentStepKey = currentStep && currentStep.key; // If general settings have stopped requesting, check if we should show success screen.

      if (prevProps.isGeneralSettingsRequesting && !isGeneralSettingsRequesting) {
        if (this.shouldShowSuccessScreen()) {
          /* eslint-disable react/no-did-update-set-state */
          this.setState({
            stepIndex: null
          });
          /* eslint-enable react/no-did-update-set-state */

          return;
        }
      }

      if (taxSettings.wc_connect_taxes_enabled && taxSettings.wc_connect_taxes_enabled !== prevProps.taxSettings.wc_connect_taxes_enabled) {
        /* eslint-disable react/no-did-update-set-state */
        this.setState({
          automatedTaxEnabled: taxSettings.wc_connect_taxes_enabled === 'yes' ? true : false
        });
        /* eslint-enable react/no-did-update-set-state */
      }

      if (currentStepKey === 'connect' && isJetpackConnected) {
        this.completeStep();
      }

      var prevCalcTaxes = prevProps.generalSettings.woocommerce_calc_taxes;

      if (prevCalcTaxes === 'no' && calcTaxes === 'yes') {
        window.location = Object(client_settings["f" /* getAdminLink */])('admin.php?page=wc-settings&tab=tax&section=standard');
      }
    }
  }, {
    key: "isTaxJarSupported",
    value: function isTaxJarSupported() {
      var _this$props3 = this.props,
          countryCode = _this$props3.countryCode,
          tosAccepted = _this$props3.tosAccepted;

      var _getSetting = Object(client_settings["g" /* getSetting */])('onboarding', {}),
          _getSetting$automated = _getSetting.automatedTaxSupportedCountries,
          automatedTaxSupportedCountries = _getSetting$automated === void 0 ? [] : _getSetting$automated,
          taxJarActivated = _getSetting.taxJarActivated;

      return !taxJarActivated && // WCS integration doesn't work with the official TaxJar plugin.
      tosAccepted && automatedTaxSupportedCountries.includes(countryCode);
    }
  }, {
    key: "completeStep",
    value: function completeStep() {
      var stepIndex = this.state.stepIndex;
      var steps = this.getSteps();
      var nextStep = steps[stepIndex + 1];

      if (nextStep) {
        this.setState({
          stepIndex: stepIndex + 1
        });
      } else {
        Object(external_this_wc_navigation_["getHistory"])().push(Object(external_this_wc_navigation_["getNewPath"])({}, '/', {}));
      }
    }
  }, {
    key: "configureTaxRates",
    value: function () {
      var _configureTaxRates = asyncToGenerator_default()( /*#__PURE__*/regeneratorRuntime.mark(function _callee() {
        var _this$props4, generalSettings, updateAndPersistSettingsForGroup;

        return regeneratorRuntime.wrap(function _callee$(_context) {
          while (1) {
            switch (_context.prev = _context.next) {
              case 0:
                _this$props4 = this.props, generalSettings = _this$props4.generalSettings, updateAndPersistSettingsForGroup = _this$props4.updateAndPersistSettingsForGroup;

                if (!(generalSettings.woocommerce_calc_taxes !== 'yes')) {
                  _context.next = 5;
                  break;
                }

                this.setState({
                  isPending: true
                });
                _context.next = 5;
                return updateAndPersistSettingsForGroup('general', {
                  general: {
                    woocommerce_calc_taxes: 'yes'
                  }
                });

              case 5:
                window.location = Object(client_settings["f" /* getAdminLink */])('admin.php?page=wc-settings&tab=tax&section=standard&wc_onboarding_active_task=tax');

              case 6:
              case "end":
                return _context.stop();
            }
          }
        }, _callee, this);
      }));

      function configureTaxRates() {
        return _configureTaxRates.apply(this, arguments);
      }

      return configureTaxRates;
    }()
  }, {
    key: "updateAutomatedTax",
    value: function () {
      var _updateAutomatedTax = asyncToGenerator_default()( /*#__PURE__*/regeneratorRuntime.mark(function _callee2() {
        var _this$props5, createNotice, updateAndPersistSettingsForGroup, automatedTaxEnabled;

        return regeneratorRuntime.wrap(function _callee2$(_context2) {
          while (1) {
            switch (_context2.prev = _context2.next) {
              case 0:
                _this$props5 = this.props, createNotice = _this$props5.createNotice, updateAndPersistSettingsForGroup = _this$props5.updateAndPersistSettingsForGroup;
                automatedTaxEnabled = this.state.automatedTaxEnabled;
                _context2.next = 4;
                return updateAndPersistSettingsForGroup('tax', {
                  tax: {
                    wc_connect_taxes_enabled: automatedTaxEnabled ? 'yes' : 'no'
                  }
                });

              case 4:
                _context2.next = 6;
                return updateAndPersistSettingsForGroup('general', {
                  general: {
                    woocommerce_calc_taxes: 'yes'
                  }
                });

              case 6:
                if (!this.props.isTaxSettingsError && !this.props.isGeneralSettingsError) {
                  // @todo This is a workaround to force the task to mark as complete.
                  // This should probably be updated to use wc-api so we can fetch tax rates.
                  Object(client_settings["h" /* setSetting */])('onboarding', tax_objectSpread({}, Object(client_settings["g" /* getSetting */])('onboarding', {}), {
                    isTaxComplete: true
                  }));
                  createNotice('success', Object(external_this_wp_i18n_["__"])("You're awesome! One less item on your to-do list ✅", 'woocommerce'));

                  if (automatedTaxEnabled) {
                    Object(external_this_wc_navigation_["getHistory"])().push(Object(external_this_wc_navigation_["getNewPath"])({}, '/', {}));
                  } else {
                    this.configureTaxRates();
                  }
                } else {
                  createNotice('error', Object(external_this_wp_i18n_["__"])('There was a problem updating your tax settings.', 'woocommerce'));
                }

              case 7:
              case "end":
                return _context2.stop();
            }
          }
        }, _callee2, this);
      }));

      function updateAutomatedTax() {
        return _updateAutomatedTax.apply(this, arguments);
      }

      return updateAutomatedTax;
    }()
  }, {
    key: "setIsPending",
    value: function setIsPending(value) {
      this.setState({
        isPending: value
      });
    }
  }, {
    key: "getSteps",
    value: function getSteps() {
      var _this2 = this;

      var _this$props6 = this.props,
          generalSettings = _this$props6.generalSettings,
          isGeneralSettingsRequesting = _this$props6.isGeneralSettingsRequesting,
          isJetpackConnected = _this$props6.isJetpackConnected;
      var _this$state = this.state,
          isPending = _this$state.isPending,
          pluginsToActivate = _this$state.pluginsToActivate;
      var steps = [{
        key: 'store_location',
        label: Object(external_this_wp_i18n_["__"])('Set store location', 'woocommerce'),
        description: Object(external_this_wp_i18n_["__"])('The address from which your business operates', 'woocommerce'),
        content: Object(external_this_wp_element_["createElement"])(location_StoreLocation, extends_default()({}, this.props, {
          onComplete: function onComplete(values) {
            var country = Object(utils["a" /* getCountryCode */])(values.countryState);
            Object(tracks["b" /* recordEvent */])('tasklist_tax_set_location', {
              country: country
            });

            if (_this2.shouldShowSuccessScreen()) {
              _this2.setState({
                stepIndex: null
              }); // Only complete step if another update hasn't already shown succes screen.

            } else if (_this2.state.stepIndex !== null) {
              _this2.completeStep();
            }
          },
          isSettingsRequesting: isGeneralSettingsRequesting,
          settings: generalSettings
        })),
        visible: true
      }, {
        key: 'plugins',
        label: Object(external_this_wp_i18n_["__"])('Install Jetpack and WooCommerce Services', 'woocommerce'),
        description: Object(external_this_wp_i18n_["__"])('Jetpack and WooCommerce Services allow you to automate sales tax calculations', 'woocommerce'),
        content: Object(external_this_wp_element_["createElement"])(external_this_wc_components_["Plugins"], {
          onComplete: function onComplete() {
            Object(tracks["b" /* recordEvent */])('tasklist_tax_install_extensions', {
              install_extensions: true
            });

            _this2.completeStep();
          },
          onSkip: function onSkip() {
            Object(tracks["a" /* queueRecordEvent */])('tasklist_tax_install_extensions', {
              install_extensions: false
            });
            window.location.href = Object(client_settings["f" /* getAdminLink */])('admin.php?page=wc-settings&tab=tax&section=standard');
          },
          skipText: Object(external_this_wp_i18n_["__"])('Set up tax rates manually', 'woocommerce')
        }),
        visible: pluginsToActivate.length && this.isTaxJarSupported()
      }, {
        key: 'connect',
        label: Object(external_this_wp_i18n_["__"])('Connect your store', 'woocommerce'),
        description: Object(external_this_wp_i18n_["__"])('Connect your store to WordPress.com to enable automated sales tax calculations', 'woocommerce'),
        content: Object(external_this_wp_element_["createElement"])(components_connect["a" /* default */], extends_default()({}, this.props, {
          setIsPending: this.setIsPending,
          onConnect: function onConnect() {
            Object(tracks["b" /* recordEvent */])('tasklist_tax_connect_store', {
              connect: true
            });
          },
          onSkip: function onSkip() {
            Object(tracks["a" /* queueRecordEvent */])('tasklist_tax_connect_store', {
              connect: false
            });
            window.location.href = Object(client_settings["f" /* getAdminLink */])('admin.php?page=wc-settings&tab=tax&section=standard');
          },
          skipText: Object(external_this_wp_i18n_["__"])('Set up tax rates manually', 'woocommerce')
        })),
        visible: !isJetpackConnected && this.isTaxJarSupported()
      }, {
        key: 'manual_configuration',
        label: Object(external_this_wp_i18n_["__"])('Configure tax rates', 'woocommerce'),
        description: Object(external_this_wp_i18n_["__"])('Head over to the tax rate settings screen to configure your tax rates', 'woocommerce'),
        content: Object(external_this_wp_element_["createElement"])(external_this_wp_element_["Fragment"], null, Object(external_this_wp_element_["createElement"])(build_module_button["a" /* default */], {
          isPrimary: true,
          isBusy: isPending,
          onClick: function onClick() {
            Object(tracks["b" /* recordEvent */])('tasklist_tax_config_rates');

            _this2.configureTaxRates();
          }
        }, Object(external_this_wp_i18n_["__"])('Configure', 'woocommerce')), Object(external_this_wp_element_["createElement"])("p", null, generalSettings.woocommerce_calc_taxes !== 'yes' && lib_default()({
          mixedString: Object(external_this_wp_i18n_["__"])(
          /*eslint-disable max-len*/
          'By clicking "Configure" you\'re enabling tax rates and calculations. More info {{link}}here{{/link}}.',
          /*eslint-enable max-len*/
          'woocommerce'),
          components: {
            link: Object(external_this_wp_element_["createElement"])(external_this_wc_components_["Link"], {
              href: "https://docs.woocommerce.com/document/setting-up-taxes-in-woocommerce/#section-1",
              target: "_blank",
              type: "external"
            })
          }
        }))),
        visible: !this.isTaxJarSupported()
      }];
      return Object(external_lodash_["filter"])(steps, function (step) {
        return step.visible;
      });
    }
  }, {
    key: "render",
    value: function render() {
      var _this3 = this;

      var _this$state2 = this.state,
          isPending = _this$state2.isPending,
          stepIndex = _this$state2.stepIndex;
      var _this$props7 = this.props,
          isGeneralSettingsRequesting = _this$props7.isGeneralSettingsRequesting,
          isTaxSettingsRequesting = _this$props7.isTaxSettingsRequesting,
          taxSettings = _this$props7.taxSettings;
      var step = this.getSteps()[stepIndex];
      return Object(external_this_wp_element_["createElement"])("div", {
        className: "woocommerce-task-tax"
      }, Object(external_this_wp_element_["createElement"])(external_this_wc_components_["Card"], {
        className: "is-narrow"
      }, step ? Object(external_this_wp_element_["createElement"])(external_this_wc_components_["Stepper"], {
        isPending: isPending || isGeneralSettingsRequesting || isTaxSettingsRequesting,
        isVertical: true,
        currentStep: step.key,
        steps: this.getSteps()
      }) : Object(external_this_wp_element_["createElement"])("div", {
        className: "woocommerce-task-tax__success"
      }, Object(external_this_wp_element_["createElement"])("span", {
        className: "woocommerce-task-tax__success-icon",
        role: "img",
        "aria-labelledby": "woocommerce-task-tax__success-message"
      }, "\uD83C\uDF8A"), Object(external_this_wp_element_["createElement"])(external_this_wc_components_["H"], {
        id: "woocommerce-task-tax__success-message"
      }, Object(external_this_wp_i18n_["__"])('Good news!', 'woocommerce')), Object(external_this_wp_element_["createElement"])("p", null, lib_default()({
        mixedString: Object(external_this_wp_i18n_["__"])('{{strong}}Jetpack{{/strong}} and {{strong}}WooCommerce Services{{/strong}} ' + 'can automate your sales tax calculations for you.', 'woocommerce'),
        components: {
          strong: Object(external_this_wp_element_["createElement"])("strong", null)
        }
      })), Object(external_this_wp_element_["createElement"])(build_module_button["a" /* default */], {
        isPrimary: true,
        isBusy: Object.keys(taxSettings).length && isTaxSettingsRequesting,
        onClick: function onClick() {
          Object(tracks["b" /* recordEvent */])('tasklist_tax_setup_automated_proceed', {
            setup_automatically: true
          });

          _this3.setState({
            automatedTaxEnabled: true
          }, _this3.updateAutomatedTax);
        }
      }, Object(external_this_wp_i18n_["__"])('Yes please', 'woocommerce')), Object(external_this_wp_element_["createElement"])(build_module_button["a" /* default */], {
        onClick: function onClick() {
          Object(tracks["b" /* recordEvent */])('tasklist_tax_setup_automated_proceed', {
            setup_automatically: false
          });

          _this3.setState({
            automatedTaxEnabled: false
          }, _this3.updateAutomatedTax);
        }
      }, Object(external_this_wp_i18n_["__"])("No thanks, I'll configure taxes manually", 'woocommerce')))));
    }
  }]);

  return Tax;
}(external_this_wp_element_["Component"]);

/* harmony default export */ var tax = (Object(compose["a" /* default */])(Object(with_select["a" /* default */])(function (select) {
  var _select = select('wc-api'),
      getOptions = _select.getOptions;

  var _select2 = select(external_this_wc_data_["PLUGINS_STORE_NAME"]),
      getActivePlugins = _select2.getActivePlugins,
      isJetpackConnected = _select2.isJetpackConnected;

  var activePlugins = getActivePlugins();
  var pluginsToActivate = Object(external_lodash_["difference"])(['jetpack', 'woocommerce-services'], activePlugins);
  var options = getOptions(['wc_connect_options', 'woocommerce_setup_jetpack_opted_in']);
  var connectOptions = Object(external_lodash_["get"])(options, 'wc_connect_options', {});
  var tosAccepted = connectOptions.tos_accepted || options.woocommerce_setup_jetpack_opted_in;
  return {
    isJetpackConnected: isJetpackConnected(),
    pluginsToActivate: pluginsToActivate,
    tosAccepted: tosAccepted
  };
}), Object(external_this_wp_data_["withSelect"])(function (select) {
  var _select3 = select(external_this_wc_data_["SETTINGS_STORE_NAME"]),
      getSettings = _select3.getSettings,
      getSettingsError = _select3.getSettingsError,
      isGetSettingsRequesting = _select3.isGetSettingsRequesting;

  var _getSettings = getSettings('general'),
      _getSettings$general = _getSettings.general,
      generalSettings = _getSettings$general === void 0 ? {} : _getSettings$general;

  var isGeneralSettingsError = Boolean(getSettingsError('general'));
  var isGeneralSettingsRequesting = isGetSettingsRequesting('general');
  var countryCode = Object(utils["a" /* getCountryCode */])(generalSettings.woocommerce_default_country);

  var _getSettings2 = getSettings('tax'),
      _getSettings2$tax = _getSettings2.tax,
      taxSettings = _getSettings2$tax === void 0 ? {} : _getSettings2$tax;

  var isTaxSettingsError = Boolean(getSettingsError('tax'));
  var isTaxSettingsRequesting = isGetSettingsRequesting('tax');
  return {
    isGeneralSettingsError: isGeneralSettingsError,
    isGeneralSettingsRequesting: isGeneralSettingsRequesting,
    generalSettings: generalSettings,
    countryCode: countryCode,
    taxSettings: taxSettings,
    isTaxSettingsError: isTaxSettingsError,
    isTaxSettingsRequesting: isTaxSettingsRequesting
  };
}), Object(external_this_wp_data_["withDispatch"])(function (dispatch) {
  var _dispatch = dispatch('core/notices'),
      createNotice = _dispatch.createNotice;

  var _dispatch2 = dispatch(external_this_wc_data_["SETTINGS_STORE_NAME"]),
      updateAndPersistSettingsForGroup = _dispatch2.updateAndPersistSettingsForGroup;

  return {
    createNotice: createNotice,
    updateAndPersistSettingsForGroup: updateAndPersistSettingsForGroup
  };
}))(tax_Tax));
// EXTERNAL MODULE: ./client/wc-api/onboarding/constants.js
var onboarding_constants = __webpack_require__(760);

// CONCATENATED MODULE: ./client/dashboard/task-list/tasks/payments/bacs.js








function bacs_createSuper(Derived) { var hasNativeReflectConstruct = bacs_isNativeReflectConstruct(); return function () { var Super = getPrototypeOf_default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = getPrototypeOf_default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return possibleConstructorReturn_default()(this, result); }; }

function bacs_isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/**
 * External dependencies
 */





/**
 * WooCommerce dependencies
 */


/**
 * Internal dependencies
 */



var bacs_PayFast = /*#__PURE__*/function (_Component) {
  inherits_default()(PayFast, _Component);

  var _super = bacs_createSuper(PayFast);

  function PayFast() {
    var _temp, _this;

    classCallCheck_default()(this, PayFast);

    for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
      args[_key] = arguments[_key];
    }

    return possibleConstructorReturn_default()(_this, (_temp = _this = _super.call.apply(_super, [this].concat(args)), _this.getInitialConfigValues = function () {
      return {
        account_name: '',
        account_number: '',
        bank_name: '',
        sort_code: '',
        iban: '',
        bic: ''
      };
    }, _this.validate = function (values) {
      var errors = {};

      if (!values.account_number && !values.iban) {
        errors.account_number = errors.iban = Object(external_this_wp_i18n_["__"])('Please enter an account number or IBAN', 'woocommerce');
      }

      return errors;
    }, _this.updateSettings = function (values) {
      var updateOptions = _this.props.updateOptions;
      updateOptions({
        woocommerce_bacs_settings: {
          enabled: 'yes'
        },
        woocommerce_bacs_accounts: [values]
      });
    }, _temp));
  }

  createClass_default()(PayFast, [{
    key: "componentDidUpdate",
    value: function componentDidUpdate(prevProps) {
      var _this$props = this.props,
          createNotice = _this$props.createNotice,
          isOptionsRequesting = _this$props.isOptionsRequesting,
          hasOptionsError = _this$props.hasOptionsError,
          markConfigured = _this$props.markConfigured;

      if (prevProps.isOptionsRequesting && !isOptionsRequesting) {
        if (!hasOptionsError) {
          markConfigured('bacs');
          createNotice('success', Object(external_this_wp_i18n_["__"])('Direct bank transfer details added successfully', 'woocommerce'));
        } else {
          createNotice('error', Object(external_this_wp_i18n_["__"])('There was a problem saving your payment setings', 'woocommerce'));
        }
      }
    }
  }, {
    key: "render",
    value: function render() {
      var isOptionsRequesting = this.props.isOptionsRequesting;
      return Object(external_this_wp_element_["createElement"])(external_this_wc_components_["Form"], {
        initialValues: this.getInitialConfigValues(),
        onSubmitCallback: this.updateSettings,
        validate: this.validate
      }, function (_ref) {
        var getInputProps = _ref.getInputProps,
            handleSubmit = _ref.handleSubmit;
        return Object(external_this_wp_element_["createElement"])(external_this_wp_element_["Fragment"], null, Object(external_this_wp_element_["createElement"])(external_this_wc_components_["H"], null, Object(external_this_wp_i18n_["__"])('Add your bank details', 'woocommerce')), Object(external_this_wp_element_["createElement"])("p", null, Object(external_this_wp_i18n_["__"])('These details are required to receive payments via bank transfer', 'woocommerce')), Object(external_this_wp_element_["createElement"])("div", {
          className: "woocommerce-task-payment-method__fields"
        }, Object(external_this_wp_element_["createElement"])(external_this_wc_components_["TextControl"], extends_default()({
          label: Object(external_this_wp_i18n_["__"])('Account name', 'woocommerce'),
          required: true
        }, getInputProps('account_name'))), Object(external_this_wp_element_["createElement"])(external_this_wc_components_["TextControl"], extends_default()({
          label: Object(external_this_wp_i18n_["__"])('Account number', 'woocommerce'),
          required: true
        }, getInputProps('account_number'))), Object(external_this_wp_element_["createElement"])(external_this_wc_components_["TextControl"], extends_default()({
          label: Object(external_this_wp_i18n_["__"])('Bank name', 'woocommerce'),
          required: true
        }, getInputProps('bank_name'))), Object(external_this_wp_element_["createElement"])(external_this_wc_components_["TextControl"], extends_default()({
          label: Object(external_this_wp_i18n_["__"])('Sort code', 'woocommerce'),
          required: true
        }, getInputProps('sort_code'))), Object(external_this_wp_element_["createElement"])(external_this_wc_components_["TextControl"], extends_default()({
          label: Object(external_this_wp_i18n_["__"])('IBAN', 'woocommerce'),
          required: true
        }, getInputProps('iban'))), Object(external_this_wp_element_["createElement"])(external_this_wc_components_["TextControl"], extends_default()({
          label: Object(external_this_wp_i18n_["__"])('BIC / Swift', 'woocommerce'),
          required: true
        }, getInputProps('bic')))), Object(external_this_wp_element_["createElement"])(build_module_button["a" /* default */], {
          isPrimary: true,
          isBusy: isOptionsRequesting,
          onClick: handleSubmit
        }, Object(external_this_wp_i18n_["__"])('Save', 'woocommerce')));
      });
    }
  }]);

  return PayFast;
}(external_this_wp_element_["Component"]);

/* harmony default export */ var bacs = (Object(compose["a" /* default */])(Object(with_select["a" /* default */])(function (select) {
  var _select = select('wc-api'),
      getOptionsError = _select.getOptionsError,
      isUpdateOptionsRequesting = _select.isUpdateOptionsRequesting;

  var isOptionsRequesting = Boolean(isUpdateOptionsRequesting(['woocommerce_bacs_settings', 'woocommerce_bacs_accounts']));
  var hasOptionsError = getOptionsError(['woocommerce_bacs_settings', 'woocommerce_bacs_accounts']);
  return {
    hasOptionsError: hasOptionsError,
    isOptionsRequesting: isOptionsRequesting
  };
}), Object(external_this_wp_data_["withDispatch"])(function (dispatch) {
  var _dispatch = dispatch('core/notices'),
      createNotice = _dispatch.createNotice;

  var _dispatch2 = dispatch('wc-api'),
      updateOptions = _dispatch2.updateOptions;

  return {
    createNotice: createNotice,
    updateOptions: updateOptions
  };
}))(bacs_PayFast));
// CONCATENATED MODULE: ./client/dashboard/task-list/tasks/payments/images/bacs.js

/* harmony default export */ var images_bacs = (function () {
  return Object(external_this_wp_element_["createElement"])("svg", {
    width: "96",
    height: "32",
    viewBox: "0 0 96 32",
    fill: "none",
    xmlns: "http://www.w3.org/2000/svg"
  }, Object(external_this_wp_element_["createElement"])("rect", {
    width: "32",
    height: "32",
    rx: "16",
    fill: "#8E9196"
  }), Object(external_this_wp_element_["createElement"])("mask", {
    id: "bacs0",
    "mask-type": "alpha",
    maskUnits: "userSpaceOnUse",
    x: "8",
    y: "8",
    width: "16",
    height: "16"
  }, Object(external_this_wp_element_["createElement"])("path", {
    fillRule: "evenodd",
    clipRule: "evenodd",
    d: "M8.875 12.25L16 8.5L23.125 12.25V13.75H8.875V12.25ZM16 10.195L19.9075 12.25H12.0925L16 10.195ZM10.75 15.25H12.25V20.5H10.75V15.25ZM15.25 20.5V15.25H16.75V20.5H15.25ZM23.125 23.5V22H8.875V23.5H23.125ZM19.75 15.25H21.25V20.5H19.75V15.25Z",
    fill: "white"
  })), Object(external_this_wp_element_["createElement"])("g", {
    mask: "url(#bacs0)"
  }, Object(external_this_wp_element_["createElement"])("rect", {
    x: "7",
    y: "7",
    width: "18",
    height: "18",
    fill: "white"
  })), Object(external_this_wp_element_["createElement"])("mask", {
    id: "bacs1",
    "mask-type": "alpha",
    maskUnits: "userSpaceOnUse",
    x: "39",
    y: "10",
    width: "18",
    height: "12"
  }, Object(external_this_wp_element_["createElement"])("path", {
    d: "M39 17L53.17 17L49.59 20.59L51 22L57 16L51 10L49.59 11.41L53.17 15L39 15L39 17Z",
    fill: "white"
  })), Object(external_this_wp_element_["createElement"])("g", {
    mask: "url(#bacs1)"
  }, Object(external_this_wp_element_["createElement"])("rect", {
    x: "60",
    y: "28",
    width: "24",
    height: "24",
    transform: "rotate(-180 60 28)",
    fill: "#8E9196"
  })), Object(external_this_wp_element_["createElement"])("rect", {
    x: "64",
    width: "32",
    height: "32",
    rx: "16",
    fill: "#8E9196"
  }), Object(external_this_wp_element_["createElement"])("mask", {
    id: "bacs2",
    "mask-type": "alpha",
    maskUnits: "userSpaceOnUse",
    x: "72",
    y: "8",
    width: "16",
    height: "16"
  }, Object(external_this_wp_element_["createElement"])("path", {
    fillRule: "evenodd",
    clipRule: "evenodd",
    d: "M72.875 12.25L80 8.5L87.125 12.25V13.75H72.875V12.25ZM80 10.195L83.9075 12.25H76.0925L80 10.195ZM74.75 15.25H76.25V20.5H74.75V15.25ZM79.25 20.5V15.25H80.75V20.5H79.25ZM87.125 23.5V22H72.875V23.5H87.125ZM83.75 15.25H85.25V20.5H83.75V15.25Z",
    fill: "white"
  })), Object(external_this_wp_element_["createElement"])("g", {
    mask: "url(#bacs2)"
  }, Object(external_this_wp_element_["createElement"])("rect", {
    x: "71",
    y: "7",
    width: "18",
    height: "18",
    fill: "white"
  })));
});
// CONCATENATED MODULE: ./client/dashboard/task-list/tasks/payments/images/cod.js

/* harmony default export */ var cod = (function () {
  return Object(external_this_wp_element_["createElement"])("svg", {
    width: "96",
    height: "32",
    viewBox: "0 0 96 32",
    fill: "none",
    xmlns: "http://www.w3.org/2000/svg"
  }, Object(external_this_wp_element_["createElement"])("rect", {
    width: "32",
    height: "32",
    rx: "16",
    fill: "#8E9196"
  }), Object(external_this_wp_element_["createElement"])("mask", {
    id: "cod-mask-0",
    "mask-type": "alpha",
    maskUnits: "userSpaceOnUse",
    x: "7",
    y: "10",
    width: "18",
    height: "12"
  }, Object(external_this_wp_element_["createElement"])("path", {
    fillRule: "evenodd",
    clipRule: "evenodd",
    d: "M22 13H19.75V10H9.25C8.425 10 7.75 10.675 7.75 11.5V19.75H9.25C9.25 20.995 10.255 22 11.5 22C12.745 22 13.75 20.995 13.75 19.75H18.25C18.25 20.995 19.255 22 20.5 22C21.745 22 22.75 20.995 22.75 19.75H24.25V16L22 13ZM21.625 14.125L23.095 16H19.75V14.125H21.625ZM10.75 19.75C10.75 20.1625 11.0875 20.5 11.5 20.5C11.9125 20.5 12.25 20.1625 12.25 19.75C12.25 19.3375 11.9125 19 11.5 19C11.0875 19 10.75 19.3375 10.75 19.75ZM13.165 18.25C12.7525 17.7925 12.1675 17.5 11.5 17.5C10.8325 17.5 10.2475 17.7925 9.835 18.25H9.25V11.5H18.25V18.25H13.165ZM19.75 19.75C19.75 20.1625 20.0875 20.5 20.5 20.5C20.9125 20.5 21.25 20.1625 21.25 19.75C21.25 19.3375 20.9125 19 20.5 19C20.0875 19 19.75 19.3375 19.75 19.75Z",
    fill: "white"
  })), Object(external_this_wp_element_["createElement"])("g", {
    mask: "url(#cod-mask-0)"
  }, Object(external_this_wp_element_["createElement"])("rect", {
    x: "7",
    y: "7",
    width: "18",
    height: "18",
    fill: "white"
  })), Object(external_this_wp_element_["createElement"])("mask", {
    id: "cod-mask-1",
    "mask-type": "alpha",
    maskUnits: "userSpaceOnUse",
    x: "39",
    y: "10",
    width: "18",
    height: "12"
  }, Object(external_this_wp_element_["createElement"])("path", {
    d: "M39 17L53.17 17L49.59 20.59L51 22L57 16L51 10L49.59 11.41L53.17 15L39 15L39 17Z",
    fill: "white"
  })), Object(external_this_wp_element_["createElement"])("g", {
    mask: "url(#cod-mask-1)"
  }, Object(external_this_wp_element_["createElement"])("rect", {
    x: "60",
    y: "28",
    width: "24",
    height: "24",
    transform: "rotate(-180 60 28)",
    fill: "#8E9196"
  })), Object(external_this_wp_element_["createElement"])("rect", {
    x: "64",
    width: "32",
    height: "32",
    rx: "16",
    fill: "#8E9196"
  }), Object(external_this_wp_element_["createElement"])("mask", {
    id: "cod-mask-2",
    "mask-type": "alpha",
    maskUnits: "userSpaceOnUse",
    x: "76",
    y: "9",
    width: "8",
    height: "14"
  }, Object(external_this_wp_element_["createElement"])("path", {
    d: "M80.2926 15.175C78.5901 14.7325 78.0426 14.275 78.0426 13.5625C78.0426 12.745 78.8001 12.175 80.0676 12.175C81.4026 12.175 81.8976 12.8125 81.9426 13.75H83.6001C83.5476 12.46 82.7601 11.275 81.1926 10.8925V9.25H78.9426V10.87C77.4876 11.185 76.3176 12.13 76.3176 13.5775C76.3176 15.31 77.7501 16.1725 79.8426 16.675C81.7176 17.125 82.0926 17.785 82.0926 18.4825C82.0926 19 81.7251 19.825 80.0676 19.825C78.5226 19.825 77.9151 19.135 77.8326 18.25H76.1826C76.2726 19.8925 77.5026 20.815 78.9426 21.1225V22.75H81.1926V21.1375C82.6551 20.86 83.8176 20.0125 83.8176 18.475C83.8176 16.345 81.9951 15.6175 80.2926 15.175Z",
    fill: "white"
  })), Object(external_this_wp_element_["createElement"])("g", {
    mask: "url(#cod-mask-2)"
  }, Object(external_this_wp_element_["createElement"])("rect", {
    x: "71",
    y: "7",
    width: "18",
    height: "18",
    fill: "white"
  })));
});
// CONCATENATED MODULE: ./client/dashboard/task-list/tasks/payments/stripe.js











function stripe_ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function stripe_objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { stripe_ownKeys(Object(source), true).forEach(function (key) { defineProperty_default()(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { stripe_ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function stripe_createSuper(Derived) { var hasNativeReflectConstruct = stripe_isNativeReflectConstruct(); return function () { var Super = getPrototypeOf_default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = getPrototypeOf_default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return possibleConstructorReturn_default()(this, result); }; }

function stripe_isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/**
 * External dependencies
 */








/**
 * WooCommerce dependencies
 */








var stripe_Stripe = /*#__PURE__*/function (_Component) {
  inherits_default()(Stripe, _Component);

  var _super = stripe_createSuper(Stripe);

  function Stripe(props) {
    var _this;

    classCallCheck_default()(this, Stripe);

    _this = _super.call(this, props);
    _this.state = {
      oAuthConnectFailed: false,
      connectURL: null,
      isPending: false
    };
    _this.updateSettings = _this.updateSettings.bind(assertThisInitialized_default()(_this));
    return _this;
  }

  createClass_default()(Stripe, [{
    key: "componentDidMount",
    value: function componentDidMount() {
      var stripeSettings = this.props.stripeSettings;
      var query = Object(external_this_wc_navigation_["getQuery"])(); // Handle redirect back from Stripe.

      if (query['stripe-connect'] && query['stripe-connect'] === '1') {
        var isStripeConnected = stripeSettings.publishable_key && stripeSettings.secret_key;

        if (isStripeConnected) {
          this.completeMethod();
          return;
        }
      }

      if (!this.requiresManualConfig()) {
        this.fetchOAuthConnectURL();
      }
    }
  }, {
    key: "componentDidUpdate",
    value: function componentDidUpdate(prevProps) {
      var _this$props = this.props,
          activePlugins = _this$props.activePlugins,
          createNotice = _this$props.createNotice,
          isOptionsRequesting = _this$props.isOptionsRequesting,
          hasOptionsError = _this$props.hasOptionsError;

      if (prevProps.isOptionsRequesting && !isOptionsRequesting) {
        if (!hasOptionsError) {
          this.completeMethod();
        } else {
          createNotice('error', Object(external_this_wp_i18n_["__"])('There was a problem saving your payment setings', 'woocommerce'));
        }
      }

      if (!prevProps.activePlugins.includes('woocommerce-gateway-stripe') && activePlugins.includes('woocommerce-gateway-stripe')) {
        this.fetchOAuthConnectURL();
      }
    }
  }, {
    key: "requiresManualConfig",
    value: function requiresManualConfig() {
      var _this$props2 = this.props,
          activePlugins = _this$props2.activePlugins,
          isJetpackConnected = _this$props2.isJetpackConnected;
      var oAuthConnectFailed = this.state.oAuthConnectFailed;
      return !isJetpackConnected || !activePlugins.includes('woocommerce-services') || oAuthConnectFailed;
    }
  }, {
    key: "completeMethod",
    value: function completeMethod() {
      var _this$props3 = this.props,
          createNotice = _this$props3.createNotice,
          markConfigured = _this$props3.markConfigured;
      this.setState({
        isPending: false
      });
      createNotice('success', Object(external_this_wp_i18n_["__"])('Stripe connected successfully.', 'woocommerce'));
      markConfigured('stripe');
    }
  }, {
    key: "fetchOAuthConnectURL",
    value: function () {
      var _fetchOAuthConnectURL = asyncToGenerator_default()( /*#__PURE__*/regeneratorRuntime.mark(function _callee() {
        var activePlugins, result;
        return regeneratorRuntime.wrap(function _callee$(_context) {
          while (1) {
            switch (_context.prev = _context.next) {
              case 0:
                activePlugins = this.props.activePlugins;

                if (activePlugins.includes('woocommerce-gateway-stripe')) {
                  _context.next = 3;
                  break;
                }

                return _context.abrupt("return");

              case 3:
                _context.prev = 3;
                this.setState({
                  isPending: true
                });
                _context.next = 7;
                return external_this_wp_apiFetch_default()({
                  path: constants["e" /* WCS_NAMESPACE */] + '/connect/stripe/oauth/init',
                  method: 'POST',
                  data: {
                    returnUrl: Object(client_settings["f" /* getAdminLink */])('admin.php?page=wc-admin&task=payments&method=stripe&stripe-connect=1')
                  }
                });

              case 7:
                result = _context.sent;

                if (!(!result || !result.oauthUrl)) {
                  _context.next = 11;
                  break;
                }

                this.setState({
                  oAuthConnectFailed: true,
                  isPending: false
                });
                return _context.abrupt("return");

              case 11:
                this.setState({
                  connectURL: result.oauthUrl,
                  isPending: false
                });
                _context.next = 17;
                break;

              case 14:
                _context.prev = 14;
                _context.t0 = _context["catch"](3);
                this.setState({
                  oAuthConnectFailed: true,
                  isPending: false
                });

              case 17:
              case "end":
                return _context.stop();
            }
          }
        }, _callee, this, [[3, 14]]);
      }));

      function fetchOAuthConnectURL() {
        return _fetchOAuthConnectURL.apply(this, arguments);
      }

      return fetchOAuthConnectURL;
    }()
  }, {
    key: "renderConnectButton",
    value: function renderConnectButton() {
      var connectURL = this.state.connectURL;
      return Object(external_this_wp_element_["createElement"])(build_module_button["a" /* default */], {
        isPrimary: true,
        isDefault: true,
        href: connectURL
      }, Object(external_this_wp_i18n_["__"])('Connect', 'woocommerce'));
    }
  }, {
    key: "updateSettings",
    value: function updateSettings(values) {
      var _this$props4 = this.props,
          updateOptions = _this$props4.updateOptions,
          stripeSettings = _this$props4.stripeSettings;
      updateOptions({
        woocommerce_stripe_settings: stripe_objectSpread({}, stripeSettings, {
          publishable_key: values.publishable_key,
          secret_key: values.secret_key,
          enabled: 'yes'
        })
      });
    }
  }, {
    key: "getInitialConfigValues",
    value: function getInitialConfigValues() {
      return {
        publishable_key: '',
        secret_key: ''
      };
    }
  }, {
    key: "validateManualConfig",
    value: function validateManualConfig(values) {
      var errors = {};

      if (values.publishable_key.match(/^pk_live_/) === null) {
        errors.publishable_key = Object(external_this_wp_i18n_["__"])('Please enter a valid publishable key. Valid keys start with "pk_live".', 'woocommerce');
      }

      if (values.secret_key.match(/^[rs]k_live_/) === null) {
        errors.secret_key = Object(external_this_wp_i18n_["__"])('Please enter a valid secret key. Valid keys start with "sk_live" or "rk_live".', 'woocommerce');
      }

      return errors;
    }
  }, {
    key: "renderManualConfig",
    value: function renderManualConfig() {
      var isOptionsRequesting = this.props.isOptionsRequesting;
      var stripeHelp = lib_default()({
        mixedString: Object(external_this_wp_i18n_["__"])('Your API details can be obtained from your {{docsLink}}Stripe account{{/docsLink}}.  Don’t have a Stripe account? {{registerLink}}Create one.{{/registerLink}}', 'woocommerce'),
        components: {
          docsLink: Object(external_this_wp_element_["createElement"])(external_this_wc_components_["Link"], {
            href: "https://stripe.com/docs/keys",
            target: "_blank",
            type: "external"
          }),
          registerLink: Object(external_this_wp_element_["createElement"])(external_this_wc_components_["Link"], {
            href: "https://dashboard.stripe.com/register",
            target: "_blank",
            type: "external"
          })
        }
      });
      return Object(external_this_wp_element_["createElement"])(external_this_wc_components_["Form"], {
        initialValues: this.getInitialConfigValues(),
        onSubmitCallback: this.updateSettings,
        validate: this.validateManualConfig
      }, function (_ref) {
        var getInputProps = _ref.getInputProps,
            handleSubmit = _ref.handleSubmit;
        return Object(external_this_wp_element_["createElement"])(external_this_wp_element_["Fragment"], null, Object(external_this_wp_element_["createElement"])(external_this_wc_components_["TextControl"], extends_default()({
          label: Object(external_this_wp_i18n_["__"])('Live Publishable Key', 'woocommerce'),
          required: true
        }, getInputProps('publishable_key'))), Object(external_this_wp_element_["createElement"])(external_this_wc_components_["TextControl"], extends_default()({
          label: Object(external_this_wp_i18n_["__"])('Live Secret Key', 'woocommerce'),
          required: true
        }, getInputProps('secret_key'))), Object(external_this_wp_element_["createElement"])(build_module_button["a" /* default */], {
          isPrimary: true,
          isBusy: isOptionsRequesting,
          onClick: handleSubmit
        }, Object(external_this_wp_i18n_["__"])('Proceed', 'woocommerce')), Object(external_this_wp_element_["createElement"])("p", null, stripeHelp));
      });
    }
  }, {
    key: "getConnectStep",
    value: function getConnectStep() {
      var _this$state = this.state,
          connectURL = _this$state.connectURL,
          isPending = _this$state.isPending,
          oAuthConnectFailed = _this$state.oAuthConnectFailed;
      var connectStep = {
        key: 'connect',
        label: Object(external_this_wp_i18n_["__"])('Connect your Stripe account', 'woocommerce')
      };

      if (isPending) {
        return connectStep;
      }

      if (!oAuthConnectFailed && connectURL) {
        return stripe_objectSpread({}, connectStep, {
          description: Object(external_this_wp_i18n_["__"])('A Stripe account is required to process payments.', 'woocommerce'),
          content: this.renderConnectButton()
        });
      }

      return stripe_objectSpread({}, connectStep, {
        description: Object(external_this_wp_i18n_["__"])('Connect your store to your Stripe account. Don’t have a Stripe account? Create one.', 'woocommerce'),
        content: this.renderManualConfig()
      });
    }
  }, {
    key: "render",
    value: function render() {
      var _this$props5 = this.props,
          installStep = _this$props5.installStep,
          isOptionsRequesting = _this$props5.isOptionsRequesting;
      var isPending = this.state.isPending;
      return Object(external_this_wp_element_["createElement"])(external_this_wc_components_["Stepper"], {
        isVertical: true,
        isPending: !installStep.isComplete || isOptionsRequesting || isPending,
        currentStep: installStep.isComplete ? 'connect' : 'install',
        steps: [installStep, this.getConnectStep()]
      });
    }
  }]);

  return Stripe;
}(external_this_wp_element_["Component"]);

/* harmony default export */ var stripe = (Object(compose["a" /* default */])(Object(with_select["a" /* default */])(function (select) {
  var _select = select('wc-api'),
      getOptions = _select.getOptions,
      getOptionsError = _select.getOptionsError,
      isUpdateOptionsRequesting = _select.isUpdateOptionsRequesting;

  var _select2 = select(external_this_wc_data_["PLUGINS_STORE_NAME"]),
      getActivePlugins = _select2.getActivePlugins,
      isJetpackConnected = _select2.isJetpackConnected;

  var options = getOptions(['woocommerce_stripe_settings']);
  var stripeSettings = Object(external_lodash_["get"])(options, ['woocommerce_stripe_settings'], []);
  var isOptionsRequesting = Boolean(isUpdateOptionsRequesting(['woocommerce_stripe_settings']));
  var hasOptionsError = getOptionsError(['woocommerce_stripe_settings']);
  return {
    activePlugins: getActivePlugins(),
    hasOptionsError: hasOptionsError,
    isJetpackConnected: isJetpackConnected(),
    isOptionsRequesting: isOptionsRequesting,
    stripeSettings: stripeSettings
  };
}), Object(external_this_wp_data_["withDispatch"])(function (dispatch) {
  var _dispatch = dispatch('core/notices'),
      createNotice = _dispatch.createNotice;

  var _dispatch2 = dispatch('wc-api'),
      updateOptions = _dispatch2.updateOptions;

  return {
    createNotice: createNotice,
    updateOptions: updateOptions
  };
}))(stripe_Stripe));
// CONCATENATED MODULE: ./client/dashboard/task-list/tasks/payments/square.js










function square_ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function square_objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { square_ownKeys(Object(source), true).forEach(function (key) { defineProperty_default()(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { square_ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function square_createSuper(Derived) { var hasNativeReflectConstruct = square_isNativeReflectConstruct(); return function () { var Super = getPrototypeOf_default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = getPrototypeOf_default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return possibleConstructorReturn_default()(this, result); }; }

function square_isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/**
 * External dependencies
 */






/**
 * WooCommerce dependencies
 */







var square_Square = /*#__PURE__*/function (_Component) {
  inherits_default()(Square, _Component);

  var _super = square_createSuper(Square);

  function Square(props) {
    var _this;

    classCallCheck_default()(this, Square);

    _this = _super.call(this, props);
    _this.state = {
      isPending: false
    };
    _this.connect = _this.connect.bind(assertThisInitialized_default()(_this));
    return _this;
  }

  createClass_default()(Square, [{
    key: "componentDidMount",
    value: function componentDidMount() {
      var _this$props = this.props,
          createNotice = _this$props.createNotice,
          markConfigured = _this$props.markConfigured;
      var query = Object(external_this_wc_navigation_["getQuery"])(); // Handle redirect back from Square

      if (query['square-connect']) {
        if (query['square-connect'] === '1') {
          createNotice('success', Object(external_this_wp_i18n_["__"])('Square connected successfully.', 'woocommerce'));
          markConfigured('square');
        }
      }
    }
  }, {
    key: "connect",
    value: function () {
      var _connect = asyncToGenerator_default()( /*#__PURE__*/regeneratorRuntime.mark(function _callee() {
        var _this$props2, createNotice, hasCbdIndustry, options, updateOptions, errorMessage, newWindow, result;

        return regeneratorRuntime.wrap(function _callee$(_context) {
          while (1) {
            switch (_context.prev = _context.next) {
              case 0:
                _this$props2 = this.props, createNotice = _this$props2.createNotice, hasCbdIndustry = _this$props2.hasCbdIndustry, options = _this$props2.options, updateOptions = _this$props2.updateOptions;
                this.setState({
                  isPending: true
                });
                updateOptions({
                  woocommerce_square_credit_card_settings: square_objectSpread({}, options.woocommerce_square_credit_card_settings, {
                    enabled: 'yes'
                  })
                });
                errorMessage = Object(external_this_wp_i18n_["__"])('There was an error connecting to Square. Please try again or skip to connect later in store settings.', 'woocommerce');
                _context.prev = 4;
                newWindow = null;

                if (hasCbdIndustry) {
                  // It's necessary to declare the new tab before the async call,
                  // otherwise, it won't be possible to open it.
                  newWindow = window.open('/', '_blank');
                }

                _context.next = 9;
                return external_this_wp_apiFetch_default()({
                  path: constants["f" /* WC_ADMIN_NAMESPACE */] + '/plugins/connect-square',
                  method: 'POST'
                });

              case 9:
                result = _context.sent;

                if (!(!result || !result.connectUrl)) {
                  _context.next = 15;
                  break;
                }

                this.setState({
                  isPending: false
                });
                createNotice('error', errorMessage);

                if (hasCbdIndustry) {
                  newWindow.close();
                }

                return _context.abrupt("return");

              case 15:
                this.setState({
                  isPending: true
                });
                this.redirect(result.connectUrl, newWindow);
                _context.next = 23;
                break;

              case 19:
                _context.prev = 19;
                _context.t0 = _context["catch"](4);
                this.setState({
                  isPending: false
                });
                createNotice('error', errorMessage);

              case 23:
              case "end":
                return _context.stop();
            }
          }
        }, _callee, this, [[4, 19]]);
      }));

      function connect() {
        return _connect.apply(this, arguments);
      }

      return connect;
    }()
  }, {
    key: "redirect",
    value: function redirect(connectUrl, newWindow) {
      if (newWindow) {
        newWindow.location.href = connectUrl;
        window.location = Object(client_settings["f" /* getAdminLink */])('admin.php?page=wc-admin');
      } else {
        window.location = connectUrl;
      }
    }
  }, {
    key: "render",
    value: function render() {
      var installStep = this.props.installStep;
      var isPending = this.state.isPending;
      return Object(external_this_wp_element_["createElement"])(external_this_wc_components_["Stepper"], {
        isVertical: true,
        isPending: !installStep.isComplete || isPending,
        currentStep: installStep.isComplete ? 'connect' : 'install',
        steps: [installStep, {
          key: 'connect',
          label: Object(external_this_wp_i18n_["__"])('Connect your Square account', 'woocommerce'),
          description: Object(external_this_wp_i18n_["__"])('A Square account is required to process payments. You will be redirected to the Square website to create the connection.', 'woocommerce'),
          content: Object(external_this_wp_element_["createElement"])(external_this_wp_element_["Fragment"], null, Object(external_this_wp_element_["createElement"])(build_module_button["a" /* default */], {
            isPrimary: true,
            isDefault: true,
            isBusy: isPending,
            onClick: this.connect
          }, Object(external_this_wp_i18n_["__"])('Connect', 'woocommerce')))
        }]
      });
    }
  }]);

  return Square;
}(external_this_wp_element_["Component"]);

/* harmony default export */ var square = (Object(compose["a" /* default */])(Object(with_select["a" /* default */])(function (select) {
  var _select = select('wc-api'),
      getOptions = _select.getOptions,
      isGetOptionsRequesting = _select.isGetOptionsRequesting;

  var options = getOptions(['woocommerce_square_credit_card_settings']);
  var optionsIsRequesting = Boolean(isGetOptionsRequesting(['woocommerce_square_credit_card_settings']));
  return {
    options: options,
    optionsIsRequesting: optionsIsRequesting
  };
}), Object(external_this_wp_data_["withDispatch"])(function (dispatch) {
  var _dispatch = dispatch('core/notices'),
      createNotice = _dispatch.createNotice;

  var _dispatch2 = dispatch('wc-api'),
      updateOptions = _dispatch2.updateOptions;

  return {
    createNotice: createNotice,
    updateOptions: updateOptions
  };
}))(square_Square));
// CONCATENATED MODULE: ./client/dashboard/task-list/tasks/payments/wcpay.js









function wcpay_createSuper(Derived) { var hasNativeReflectConstruct = wcpay_isNativeReflectConstruct(); return function () { var Super = getPrototypeOf_default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = getPrototypeOf_default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return possibleConstructorReturn_default()(this, result); }; }

function wcpay_isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/**
 * External dependencies
 */





/**
 * WooCommerce dependencies
 */





var wcpay_WCPay = /*#__PURE__*/function (_Component) {
  inherits_default()(WCPay, _Component);

  var _super = wcpay_createSuper(WCPay);

  function WCPay(props) {
    var _this;

    classCallCheck_default()(this, WCPay);

    _this = _super.call(this, props);
    _this.state = {
      isPending: false
    };
    _this.connect = _this.connect.bind(assertThisInitialized_default()(_this));
    return _this;
  }

  createClass_default()(WCPay, [{
    key: "componentDidMount",
    value: function componentDidMount() {
      var _this$props = this.props,
          createNotice = _this$props.createNotice,
          markConfigured = _this$props.markConfigured;
      var query = Object(external_this_wc_navigation_["getQuery"])(); // Handle redirect back from WCPay on-boarding

      if (query['wcpay-connection-success']) {
        createNotice('success', Object(external_this_wp_i18n_["__"])('WooCommerce Payments connected successfully.', 'woocommerce'));
        markConfigured('wcpay');
      }
    }
  }, {
    key: "connect",
    value: function () {
      var _connect = asyncToGenerator_default()( /*#__PURE__*/regeneratorRuntime.mark(function _callee() {
        var createNotice, errorMessage, result;
        return regeneratorRuntime.wrap(function _callee$(_context) {
          while (1) {
            switch (_context.prev = _context.next) {
              case 0:
                createNotice = this.props.createNotice;
                this.setState({
                  isPending: true
                });
                errorMessage = Object(external_this_wp_i18n_["__"])('There was an error connecting to WooCommerce Payments. Please try again or skip to connect later in store settings.', 'woocommerce');
                _context.prev = 3;
                _context.next = 6;
                return external_this_wp_apiFetch_default()({
                  path: constants["f" /* WC_ADMIN_NAMESPACE */] + '/plugins/connect-wcpay',
                  method: 'POST'
                });

              case 6:
                result = _context.sent;

                if (!(!result || !result.connectUrl)) {
                  _context.next = 11;
                  break;
                }

                this.setState({
                  isPending: false
                });
                createNotice('error', errorMessage);
                return _context.abrupt("return");

              case 11:
                this.setState({
                  isPending: true
                });
                window.location = result.connectUrl;
                _context.next = 19;
                break;

              case 15:
                _context.prev = 15;
                _context.t0 = _context["catch"](3);
                this.setState({
                  isPending: false
                });
                createNotice('error', errorMessage);

              case 19:
              case "end":
                return _context.stop();
            }
          }
        }, _callee, this, [[3, 15]]);
      }));

      function connect() {
        return _connect.apply(this, arguments);
      }

      return connect;
    }()
  }, {
    key: "render",
    value: function render() {
      var installStep = this.props.installStep;
      var isPending = this.state.isPending;
      return Object(external_this_wp_element_["createElement"])(external_this_wc_components_["Stepper"], {
        isVertical: true,
        isPending: !installStep.isComplete || isPending,
        currentStep: installStep.isComplete ? 'connect' : 'install',
        steps: [installStep, {
          key: 'connect',
          label: Object(external_this_wp_i18n_["__"])('Verify business details', 'woocommerce'),
          description: Object(external_this_wp_i18n_["__"])('Verify your business details with our payment partner, Stripe.', 'woocommerce'),
          content: Object(external_this_wp_element_["createElement"])(build_module_button["a" /* default */], {
            isPrimary: true,
            isDefault: true,
            isBusy: isPending,
            onClick: this.connect
          }, Object(external_this_wp_i18n_["__"])('Verify details', 'woocommerce'))
        }]
      });
    }
  }]);

  return WCPay;
}(external_this_wp_element_["Component"]);

/* harmony default export */ var wcpay = (Object(external_this_wp_data_["withDispatch"])(function (dispatch) {
  var _dispatch = dispatch('core/notices'),
      createNotice = _dispatch.createNotice;

  return {
    createNotice: createNotice
  };
})(wcpay_WCPay));
// CONCATENATED MODULE: ./client/dashboard/task-list/tasks/payments/images/wcpay.js

/* harmony default export */ var images_wcpay = (function () {
  return Object(external_this_wp_element_["createElement"])("svg", {
    width: "100",
    height: "64",
    viewBox: "-10 0 120 64",
    fill: "none",
    xmlns: "http://www.w3.org/2000/svg"
  }, Object(external_this_wp_element_["createElement"])("path", {
    fillRule: "evenodd",
    clipRule: "evenodd",
    d: "M9.78073 0.5H91.1787C96.3299 0.5 100.5 4.77335 100.5 10.0522V41.8929C100.5 47.1717 96.3299 51.4451 91.1787 51.4451H61.9883L65.9948 61.5L48.3742 51.4451H9.82161C4.67036 51.4451 0.500298 47.1717 0.500298 41.8929V10.0522C0.459415 4.81524 4.62947 0.5 9.78073 0.5Z",
    fill: "#7F54B3"
  }), Object(external_this_wp_element_["createElement"])("path", {
    d: "M5.48791 9.1725C6.06028 8.37648 6.91882 7.95752 8.06354 7.87373C10.1486 7.70615 11.3342 8.71165 11.6204 10.8902C12.8877 19.6464 14.2778 27.0619 15.7495 33.1368L24.7029 15.6663C25.5206 14.0743 26.5426 13.2364 27.7691 13.1526C29.568 13.0269 30.6718 14.2 31.1215 16.6718C32.1436 22.2439 33.4519 26.9781 35.0054 31.0001C36.0684 20.3586 37.8672 12.6917 40.402 7.95753C41.0152 6.78445 41.9146 6.19791 43.1002 6.11412C44.0405 6.03033 44.8991 6.3236 45.6759 6.95203C46.4526 7.58047 46.8615 8.37648 46.9432 9.34008C46.9841 10.0942 46.8615 10.7226 46.5344 11.3511C44.94 14.3676 43.6317 19.4369 42.5688 26.4754C41.5467 33.3044 41.1787 38.6251 41.424 42.4376C41.5058 43.485 41.3423 44.4067 40.9334 45.2027C40.4428 46.1244 39.707 46.6272 38.7666 46.711C37.7037 46.7948 36.5998 46.292 35.5369 45.1608C31.7348 41.1807 28.7094 35.2316 26.5018 27.3133C23.8444 32.6759 21.882 36.6979 20.6146 39.3792C18.2025 44.1134 16.1584 46.5434 14.4413 46.6691C13.3374 46.7529 12.3971 45.7893 11.5795 43.7783C9.49445 38.2899 7.24589 27.6904 4.83379 11.9795C4.71114 10.8902 4.91555 9.92662 5.48791 9.1725Z",
    fill: "white"
  }), Object(external_this_wp_element_["createElement"])("path", {
    d: "M93.3864 15.7499C91.9146 13.1105 89.7478 11.5185 86.8451 10.89C86.0683 10.7225 85.3324 10.6387 84.6374 10.6387C80.7127 10.6387 77.5238 12.7335 75.0299 16.923C72.904 20.4841 71.8411 24.4223 71.8411 28.7376C71.8411 31.9635 72.4952 34.7286 73.8034 37.0329C75.2752 39.6723 77.442 41.2644 80.3447 41.8928C81.1215 42.0604 81.8574 42.1442 82.5524 42.1442C86.518 42.1442 89.7069 40.0494 92.1599 35.8598C94.2858 32.2568 95.3488 28.3186 95.3488 24.0034C95.3488 20.7355 94.6946 18.0123 93.3864 15.7499ZM88.2351 27.355C87.6628 30.1201 86.6407 32.173 85.128 33.5556C83.9424 34.6449 82.8386 35.1057 81.8165 34.8962C80.8353 34.6868 80.0177 33.8069 79.4044 32.173C78.9138 30.8742 78.6685 29.5755 78.6685 28.3605C78.6685 27.3131 78.7503 26.2657 78.9547 25.3021C79.3226 23.5844 80.0177 21.9086 81.1215 20.3166C82.4706 18.2637 83.9015 17.4258 85.3733 17.719C86.3545 17.9285 87.1722 18.8083 87.7854 20.4422C88.276 21.741 88.5213 23.0398 88.5213 24.2547C88.5213 25.344 88.3987 26.3914 88.2351 27.355Z",
    fill: "white"
  }), Object(external_this_wp_element_["createElement"])("path", {
    d: "M67.7528 15.7499C66.281 13.1105 64.0734 11.5185 61.2116 10.89C60.4348 10.7225 59.6989 10.6387 59.0039 10.6387C55.0791 10.6387 51.8903 12.7335 49.3964 16.923C47.2705 20.4841 46.2075 24.4223 46.2075 28.7376C46.2075 31.9635 46.8616 34.7286 48.1699 37.0329C49.6417 39.6723 51.8085 41.2644 54.7112 41.8928C55.488 42.0604 56.2238 42.1442 56.9189 42.1442C60.8845 42.1442 64.0734 40.0494 66.5263 35.8598C68.6523 32.2568 69.7152 28.3186 69.7152 24.0034C69.7152 20.7355 69.0611 18.0123 67.7528 15.7499ZM62.6016 27.355C62.0292 30.1201 61.0071 32.173 59.4945 33.5556C58.3089 34.6449 57.205 35.1057 56.183 34.8962C55.2018 34.6868 54.3841 33.8069 53.7709 32.173C53.2803 30.8742 53.035 29.5755 53.035 28.3605C53.035 27.3131 53.1167 26.2657 53.3212 25.3021C53.6891 23.5844 54.3841 21.9086 55.4879 20.3166C56.8371 18.2637 58.268 17.4258 59.7398 17.719C60.721 17.9285 61.5386 18.8083 62.1519 20.4422C62.6425 21.741 62.8878 23.0398 62.8878 24.2547C62.8878 25.344 62.806 26.3914 62.6016 27.355Z",
    fill: "white"
  }));
});
// CONCATENATED MODULE: ./client/dashboard/task-list/tasks/payments/paypal.js











function paypal_ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function paypal_objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { paypal_ownKeys(Object(source), true).forEach(function (key) { defineProperty_default()(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { paypal_ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function paypal_createSuper(Derived) { var hasNativeReflectConstruct = paypal_isNativeReflectConstruct(); return function () { var Super = getPrototypeOf_default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = getPrototypeOf_default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return possibleConstructorReturn_default()(this, result); }; }

function paypal_isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/**
 * External dependencies
 */







/**
 * WooCommerce dependencies
 */







var paypal_PayPal = /*#__PURE__*/function (_Component) {
  inherits_default()(PayPal, _Component);

  var _super = paypal_createSuper(PayPal);

  function PayPal(props) {
    var _this;

    classCallCheck_default()(this, PayPal);

    _this = _super.call(this, props);
    _this.state = {
      autoConnectFailed: false,
      connectURL: '',
      isPending: false
    };
    _this.updateSettings = _this.updateSettings.bind(assertThisInitialized_default()(_this));
    return _this;
  }

  createClass_default()(PayPal, [{
    key: "componentDidMount",
    value: function componentDidMount() {
      var _this$props = this.props,
          createNotice = _this$props.createNotice,
          markConfigured = _this$props.markConfigured;
      var query = Object(external_this_wc_navigation_["getQuery"])(); // Handle redirect back from PayPal

      if (query['paypal-connect']) {
        if (query['paypal-connect'] === '1') {
          createNotice('success', Object(external_this_wp_i18n_["__"])('PayPal connected successfully.', 'woocommerce'));
          markConfigured('paypal');
          return;
        }
        /* eslint-disable react/no-did-mount-set-state */


        this.setState({
          autoConnectFailed: true
        });
        /* eslint-enable react/no-did-mount-set-state */

        return;
      }

      this.fetchOAuthConnectURL();
    }
  }, {
    key: "componentDidUpdate",
    value: function componentDidUpdate(prevProps) {
      var activePlugins = this.props.activePlugins;

      if (!prevProps.activePlugins.includes('woocommerce-gateway-paypal-express-checkout') && activePlugins.includes('woocommerce-gateway-paypal-express-checkout')) {
        this.fetchOAuthConnectURL();
      }
    }
  }, {
    key: "fetchOAuthConnectURL",
    value: function () {
      var _fetchOAuthConnectURL = asyncToGenerator_default()( /*#__PURE__*/regeneratorRuntime.mark(function _callee() {
        var activePlugins, result;
        return regeneratorRuntime.wrap(function _callee$(_context) {
          while (1) {
            switch (_context.prev = _context.next) {
              case 0:
                activePlugins = this.props.activePlugins;

                if (activePlugins.includes('woocommerce-gateway-paypal-express-checkout')) {
                  _context.next = 3;
                  break;
                }

                return _context.abrupt("return");

              case 3:
                this.setState({
                  isPending: true
                });
                _context.prev = 4;
                _context.next = 7;
                return external_this_wp_apiFetch_default()({
                  path: constants["f" /* WC_ADMIN_NAMESPACE */] + '/plugins/connect-paypal',
                  method: 'POST'
                });

              case 7:
                result = _context.sent;

                if (!(!result || !result.connectUrl)) {
                  _context.next = 11;
                  break;
                }

                this.setState({
                  autoConnectFailed: true
                });
                return _context.abrupt("return");

              case 11:
                this.setState({
                  connectURL: result.connectUrl,
                  isPending: false
                });
                _context.next = 17;
                break;

              case 14:
                _context.prev = 14;
                _context.t0 = _context["catch"](4);
                this.setState({
                  autoConnectFailed: true,
                  isPending: false
                });

              case 17:
              case "end":
                return _context.stop();
            }
          }
        }, _callee, this, [[4, 14]]);
      }));

      function fetchOAuthConnectURL() {
        return _fetchOAuthConnectURL.apply(this, arguments);
      }

      return fetchOAuthConnectURL;
    }()
  }, {
    key: "renderConnectButton",
    value: function renderConnectButton() {
      var connectURL = this.state.connectURL;
      return Object(external_this_wp_element_["createElement"])(build_module_button["a" /* default */], {
        isPrimary: true,
        isDefault: true,
        href: connectURL
      }, Object(external_this_wp_i18n_["__"])('Connect', 'woocommerce'));
    }
  }, {
    key: "updateSettings",
    value: function () {
      var _updateSettings = asyncToGenerator_default()( /*#__PURE__*/regeneratorRuntime.mark(function _callee2(values) {
        var _this$props2, createNotice, isSettingsError, options, updateOptions, markConfigured;

        return regeneratorRuntime.wrap(function _callee2$(_context2) {
          while (1) {
            switch (_context2.prev = _context2.next) {
              case 0:
                _this$props2 = this.props, createNotice = _this$props2.createNotice, isSettingsError = _this$props2.isSettingsError, options = _this$props2.options, updateOptions = _this$props2.updateOptions, markConfigured = _this$props2.markConfigured;
                _context2.next = 3;
                return updateOptions({
                  woocommerce_ppec_paypal_settings: paypal_objectSpread({}, options.woocommerce_ppec_paypal_settings, {
                    api_username: values.api_username,
                    api_password: values.api_password,
                    enabled: 'yes'
                  })
                });

              case 3:
                if (!isSettingsError) {
                  createNotice('success', Object(external_this_wp_i18n_["__"])('PayPal connected successfully.', 'woocommerce'));
                  markConfigured('paypal');
                } else {
                  createNotice('error', Object(external_this_wp_i18n_["__"])('There was a problem saving your payment settings.', 'woocommerce'));
                }

              case 4:
              case "end":
                return _context2.stop();
            }
          }
        }, _callee2, this);
      }));

      function updateSettings(_x) {
        return _updateSettings.apply(this, arguments);
      }

      return updateSettings;
    }()
  }, {
    key: "getInitialConfigValues",
    value: function getInitialConfigValues() {
      return {
        api_username: '',
        api_password: ''
      };
    }
  }, {
    key: "validate",
    value: function validate(values) {
      var errors = {};

      if (!values.api_username) {
        errors.api_username = Object(external_this_wp_i18n_["__"])('Please enter your API username', 'woocommerce');
      }

      if (!values.api_password) {
        errors.api_password = Object(external_this_wp_i18n_["__"])('Please enter your API password', 'woocommerce');
      }

      return errors;
    }
  }, {
    key: "renderManualConfig",
    value: function renderManualConfig() {
      var isOptionsRequesting = this.props.isOptionsRequesting;
      var link = Object(external_this_wp_element_["createElement"])(external_this_wc_components_["Link"], {
        href: "https://docs.woocommerce.com/document/paypal-express-checkout/#section-8",
        target: "_blank",
        type: "external"
      });
      var help = lib_default()({
        mixedString: Object(external_this_wp_i18n_["__"])('Your API details can be obtained from your {{link}}PayPal account{{/link}}', 'woocommerce'),
        components: {
          link: link
        }
      });
      return Object(external_this_wp_element_["createElement"])(external_this_wc_components_["Form"], {
        initialValues: this.getInitialConfigValues(),
        onSubmitCallback: this.updateSettings,
        validate: this.validate
      }, function (_ref) {
        var getInputProps = _ref.getInputProps,
            handleSubmit = _ref.handleSubmit;
        return Object(external_this_wp_element_["createElement"])(external_this_wp_element_["Fragment"], null, Object(external_this_wp_element_["createElement"])(external_this_wc_components_["TextControl"], extends_default()({
          label: Object(external_this_wp_i18n_["__"])('API Username', 'woocommerce'),
          required: true
        }, getInputProps('api_username'))), Object(external_this_wp_element_["createElement"])(external_this_wc_components_["TextControl"], extends_default()({
          label: Object(external_this_wp_i18n_["__"])('API Password', 'woocommerce'),
          required: true
        }, getInputProps('api_password'))), Object(external_this_wp_element_["createElement"])(build_module_button["a" /* default */], {
          onClick: handleSubmit,
          isPrimary: true,
          disabled: isOptionsRequesting
        }, Object(external_this_wp_i18n_["__"])('Proceed', 'woocommerce')), Object(external_this_wp_element_["createElement"])("p", null, help));
      });
    }
  }, {
    key: "getConnectStep",
    value: function getConnectStep() {
      var _this$state = this.state,
          autoConnectFailed = _this$state.autoConnectFailed,
          connectURL = _this$state.connectURL,
          isPending = _this$state.isPending;
      var connectStep = {
        key: 'connect',
        label: Object(external_this_wp_i18n_["__"])('Connect your PayPal account', 'woocommerce')
      };

      if (isPending) {
        return connectStep;
      }

      if (!autoConnectFailed && connectURL) {
        return paypal_objectSpread({}, connectStep, {
          description: Object(external_this_wp_i18n_["__"])('A Paypal account is required to process payments. You will be redirected to the Paypal website to create the connection.', 'woocommerce'),
          content: this.renderConnectButton()
        });
      }

      return paypal_objectSpread({}, connectStep, {
        description: Object(external_this_wp_i18n_["__"])('Connect your store to your PayPal account. Don’t have a PayPal account? Create one.', 'woocommerce'),
        content: this.renderManualConfig()
      });
    }
  }, {
    key: "render",
    value: function render() {
      var installStep = this.props.installStep;
      var isPending = this.state.isPending;
      return Object(external_this_wp_element_["createElement"])(external_this_wc_components_["Stepper"], {
        isVertical: true,
        isPending: !installStep.isComplete || isPending,
        currentStep: installStep.isComplete ? 'connect' : 'install',
        steps: [installStep, this.getConnectStep()]
      });
    }
  }]);

  return PayPal;
}(external_this_wp_element_["Component"]);

paypal_PayPal.defaultProps = {
  manualConfig: false // WCS is not required for the PayPal OAuth flow, so we can default to smooth connection.

};
/* harmony default export */ var paypal = (Object(compose["a" /* default */])(Object(with_select["a" /* default */])(function (select) {
  var _select = select('wc-api'),
      getOptions = _select.getOptions,
      isGetOptionsRequesting = _select.isGetOptionsRequesting;

  var _select2 = select(external_this_wc_data_["PLUGINS_STORE_NAME"]),
      getActivePlugins = _select2.getActivePlugins;

  var options = getOptions(['woocommerce_ppec_paypal_settings']);
  var isOptionsRequesting = Boolean(isGetOptionsRequesting(['woocommerce_ppec_paypal_settings']));
  var activePlugins = getActivePlugins();
  return {
    activePlugins: activePlugins,
    options: options,
    isOptionsRequesting: isOptionsRequesting
  };
}), Object(external_this_wp_data_["withDispatch"])(function (dispatch) {
  var _dispatch = dispatch('core/notices'),
      createNotice = _dispatch.createNotice;

  var _dispatch2 = dispatch('wc-api'),
      updateOptions = _dispatch2.updateOptions;

  return {
    createNotice: createNotice,
    updateOptions: updateOptions
  };
}))(paypal_PayPal));
// CONCATENATED MODULE: ./client/dashboard/task-list/tasks/payments/klarna.js








function klarna_createSuper(Derived) { var hasNativeReflectConstruct = klarna_isNativeReflectConstruct(); return function () { var Super = getPrototypeOf_default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = getPrototypeOf_default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return possibleConstructorReturn_default()(this, result); }; }

function klarna_isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/**
 * External dependencies
 */




/**
 * WooCommerce dependencies
 */




var klarna_Klarna = /*#__PURE__*/function (_Component) {
  inherits_default()(Klarna, _Component);

  var _super = klarna_createSuper(Klarna);

  function Klarna(props) {
    var _this;

    classCallCheck_default()(this, Klarna);

    _this = _super.call(this, props);
    _this.continue = _this.continue.bind(assertThisInitialized_default()(_this));
    return _this;
  }

  createClass_default()(Klarna, [{
    key: "continue",
    value: function _continue() {
      var _this$props = this.props,
          markConfigured = _this$props.markConfigured,
          plugin = _this$props.plugin;
      var slug = plugin === 'checkout' ? 'klarna-checkout' : 'klarna-payments';
      markConfigured(slug);
    }
  }, {
    key: "renderConnectStep",
    value: function renderConnectStep() {
      var plugin = this.props.plugin;
      var slug = plugin === 'checkout' ? 'klarna-checkout' : 'klarna-payments';
      var section = plugin === 'checkout' ? 'kco' : 'klarna_payments';
      var link = Object(external_this_wp_element_["createElement"])(external_this_wc_components_["Link"], {
        href: client_settings["a" /* ADMIN_URL */] + 'admin.php?page=wc-settings&tab=checkout&section=' + section,
        target: "_blank",
        type: "external"
      });
      var helpLink = Object(external_this_wp_element_["createElement"])(external_this_wc_components_["Link"], {
        href: 'https://docs.woocommerce.com/document/' + slug + '/#section-3',
        target: "_blank",
        type: "external"
      });
      var configureText = lib_default()({
        mixedString: Object(external_this_wp_i18n_["__"])('Klarna can be configured under your {{link}}store settings{{/link}}. Figure out {{helpLink}}what you need{{/helpLink}}.', 'woocommerce'),
        components: {
          link: link,
          helpLink: helpLink
        }
      });
      return Object(external_this_wp_element_["createElement"])(external_this_wp_element_["Fragment"], null, Object(external_this_wp_element_["createElement"])("p", null, configureText), Object(external_this_wp_element_["createElement"])(build_module_button["a" /* default */], {
        isPrimary: true,
        isDefault: true,
        onClick: this.continue
      }, Object(external_this_wp_i18n_["__"])('Continue', 'woocommerce')));
    }
  }, {
    key: "render",
    value: function render() {
      var installStep = this.props.installStep;
      return Object(external_this_wp_element_["createElement"])(external_this_wc_components_["Stepper"], {
        isVertical: true,
        isPending: !installStep.isComplete,
        currentStep: installStep.isComplete ? 'connect' : 'install',
        steps: [installStep, {
          key: 'connect',
          label: Object(external_this_wp_i18n_["__"])('Connect your Klarna account', 'woocommerce'),
          content: this.renderConnectStep()
        }]
      });
    }
  }]);

  return Klarna;
}(external_this_wp_element_["Component"]);

/* harmony default export */ var klarna = (klarna_Klarna);
// CONCATENATED MODULE: ./client/dashboard/task-list/tasks/payments/payfast.js








function payfast_createSuper(Derived) { var hasNativeReflectConstruct = payfast_isNativeReflectConstruct(); return function () { var Super = getPrototypeOf_default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = getPrototypeOf_default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return possibleConstructorReturn_default()(this, result); }; }

function payfast_isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/**
 * External dependencies
 */






/**
 * WooCommerce dependencies
 */


/**
 * Internal dependencies
 */



var payfast_PayFast = /*#__PURE__*/function (_Component) {
  inherits_default()(PayFast, _Component);

  var _super = payfast_createSuper(PayFast);

  function PayFast() {
    var _temp, _this;

    classCallCheck_default()(this, PayFast);

    for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
      args[_key] = arguments[_key];
    }

    return possibleConstructorReturn_default()(_this, (_temp = _this = _super.call.apply(_super, [this].concat(args)), _this.getInitialConfigValues = function () {
      return {
        merchant_id: '',
        merchant_key: '',
        pass_phrase: ''
      };
    }, _this.validate = function (values) {
      var errors = {};

      if (!values.merchant_id) {
        errors.merchant_id = Object(external_this_wp_i18n_["__"])('Please enter your merchant ID', 'woocommerce');
      }

      if (!values.merchant_key) {
        errors.merchant_key = Object(external_this_wp_i18n_["__"])('Please enter your merchant key', 'woocommerce');
      }

      if (!values.pass_phrase) {
        errors.pass_phrase = Object(external_this_wp_i18n_["__"])('Please enter your passphrase', 'woocommerce');
      }

      return errors;
    }, _this.updateSettings = function (values) {
      var updateOptions = _this.props.updateOptions; // Because the PayFast extension only works with the South African Rand
      // currency, force the store to use it while setting the PayFast settings

      updateOptions({
        woocommerce_currency: 'ZAR',
        woocommerce_payfast_settings: {
          merchant_id: values.merchant_id,
          merchant_key: values.merchant_key,
          pass_phrase: values.pass_phrase,
          enabled: 'yes'
        }
      });
    }, _temp));
  }

  createClass_default()(PayFast, [{
    key: "componentDidUpdate",
    value: function componentDidUpdate(prevProps) {
      var _this$props = this.props,
          createNotice = _this$props.createNotice,
          isOptionsRequesting = _this$props.isOptionsRequesting,
          hasOptionsError = _this$props.hasOptionsError,
          markConfigured = _this$props.markConfigured;

      if (prevProps.isOptionsRequesting && !isOptionsRequesting) {
        if (!hasOptionsError) {
          markConfigured('payfast');
          createNotice('success', Object(external_this_wp_i18n_["__"])('PayFast connected successfully', 'woocommerce'));
        } else {
          createNotice('error', Object(external_this_wp_i18n_["__"])('There was a problem saving your payment setings', 'woocommerce'));
        }
      }
    }
  }, {
    key: "renderConnectStep",
    value: function renderConnectStep() {
      var isOptionsRequesting = this.props.isOptionsRequesting;
      var helpText = lib_default()({
        mixedString: Object(external_this_wp_i18n_["__"])('Your API details can be obtained from your {{link}}PayFast account{{/link}}', 'woocommerce'),
        components: {
          link: Object(external_this_wp_element_["createElement"])(external_this_wc_components_["Link"], {
            href: "https://www.payfast.co.za/",
            target: "_blank",
            type: "external"
          })
        }
      });
      return Object(external_this_wp_element_["createElement"])(external_this_wc_components_["Form"], {
        initialValues: this.getInitialConfigValues(),
        onSubmitCallback: this.updateSettings,
        validate: this.validate
      }, function (_ref) {
        var getInputProps = _ref.getInputProps,
            handleSubmit = _ref.handleSubmit;
        return Object(external_this_wp_element_["createElement"])(external_this_wp_element_["Fragment"], null, Object(external_this_wp_element_["createElement"])(external_this_wc_components_["TextControl"], extends_default()({
          label: Object(external_this_wp_i18n_["__"])('Merchant ID', 'woocommerce'),
          required: true
        }, getInputProps('merchant_id'))), Object(external_this_wp_element_["createElement"])(external_this_wc_components_["TextControl"], extends_default()({
          label: Object(external_this_wp_i18n_["__"])('Merchant Key', 'woocommerce'),
          required: true
        }, getInputProps('merchant_key'))), Object(external_this_wp_element_["createElement"])(external_this_wc_components_["TextControl"], extends_default()({
          label: Object(external_this_wp_i18n_["__"])('Passphrase', 'woocommerce'),
          required: true
        }, getInputProps('pass_phrase'))), Object(external_this_wp_element_["createElement"])(build_module_button["a" /* default */], {
          isPrimary: true,
          isBusy: isOptionsRequesting,
          onClick: handleSubmit
        }, Object(external_this_wp_i18n_["__"])('Proceed', 'woocommerce')), Object(external_this_wp_element_["createElement"])("p", null, helpText));
      });
    }
  }, {
    key: "render",
    value: function render() {
      var _this$props2 = this.props,
          installStep = _this$props2.installStep,
          isOptionsRequesting = _this$props2.isOptionsRequesting;
      return Object(external_this_wp_element_["createElement"])(external_this_wc_components_["Stepper"], {
        isVertical: true,
        isPending: !installStep.isComplete || isOptionsRequesting,
        currentStep: installStep.isComplete ? 'connect' : 'install',
        steps: [installStep, {
          key: 'connect',
          label: Object(external_this_wp_i18n_["__"])('Connect your PayFast account', 'woocommerce'),
          content: this.renderConnectStep()
        }]
      });
    }
  }]);

  return PayFast;
}(external_this_wp_element_["Component"]);

/* harmony default export */ var payfast = (Object(compose["a" /* default */])(Object(with_select["a" /* default */])(function (select) {
  var _select = select('wc-api'),
      getOptionsError = _select.getOptionsError,
      isUpdateOptionsRequesting = _select.isUpdateOptionsRequesting;

  var isOptionsRequesting = Boolean(isUpdateOptionsRequesting(['woocommerce_currency', 'woocommerce_payfast_settings']));
  var hasOptionsError = getOptionsError(['woocommerce_currency', 'woocommerce_payfast_settings']);
  return {
    hasOptionsError: hasOptionsError,
    isOptionsRequesting: isOptionsRequesting
  };
}), Object(external_this_wp_data_["withDispatch"])(function (dispatch) {
  var _dispatch = dispatch('core/notices'),
      createNotice = _dispatch.createNotice;

  var _dispatch2 = dispatch('wc-api'),
      updateOptions = _dispatch2.updateOptions;

  return {
    createNotice: createNotice,
    updateOptions: updateOptions
  };
}))(payfast_PayFast));
// CONCATENATED MODULE: ./client/dashboard/task-list/tasks/payments/methods.js


/**
 * External dependencies
 */




/**
 * WooCommerce dependencies
 */



/**
 * Internal dependencies
 */











function getPaymentMethods(_ref) {
  var activePlugins = _ref.activePlugins,
      countryCode = _ref.countryCode,
      isJetpackConnected = _ref.isJetpackConnected,
      options = _ref.options,
      profileItems = _ref.profileItems;
  var stripeCountries = Object(client_settings["g" /* getSetting */])('onboarding', {
    stripeSupportedCountries: []
  }).stripeSupportedCountries;
  var hasCbdIndustry = Object(external_lodash_["some"])(profileItems.industry, {
    slug: 'cbd-other-hemp-derived-products'
  }) || false;
  var methods = [];

  if (true) {
    var tosLink = Object(external_this_wp_element_["createElement"])(external_this_wc_components_["Link"], {
      href: 'https://wordpress.com/tos/',
      target: "_blank",
      type: "external"
    });
    var tosPrompt = lib_default()({
      mixedString: Object(external_this_wp_i18n_["__"])('By clicking "Set up," you agree to the {{link}}Terms of Service{{/link}}', 'woocommerce'),
      components: {
        link: tosLink
      }
    });
    var wcPayDocLink = Object(external_this_wp_element_["createElement"])(external_this_wc_components_["Link"], {
      href: 'https://docs.woocommerce.com/document/payments/testing/dev-mode/',
      target: "_blank",
      type: "external"
    });
    var wcPayDocPrompt = lib_default()({
      mixedString: Object(external_this_wp_i18n_["__"])('Setting up a store for a client? {{link}}Start here{{/link}}', 'woocommerce'),
      components: {
        link: wcPayDocLink
      }
    });
    var wcPaySettingsLink = Object(external_this_wp_element_["createElement"])(external_this_wc_components_["Link"], {
      href: Object(client_settings["f" /* getAdminLink */])('admin.php?page=wc-settings&tab=checkout&section=woocommerce_payments'),
      type: "wp-admin"
    }, Object(external_this_wp_i18n_["__"])('Settings', 'woocommerce')); // @todo This should check actual connection information.

    var wcPayIsConfigured = activePlugins.includes('woocommerce-payments');
    methods.push({
      key: 'wcpay',
      title: Object(external_this_wp_i18n_["__"])('WooCommerce Payments', 'woocommerce'),
      content: Object(external_this_wp_element_["createElement"])(external_this_wp_element_["Fragment"], null, Object(external_this_wp_i18n_["__"])('Accept credit card payments the easy way! No setup fees. No ' + 'monthly fees. Just 2.9% + $0.30 per transaction ' + 'on U.S. issued cards. ', 'woocommerce'), wcPayIsConfigured && wcPaySettingsLink, !wcPayIsConfigured && Object(external_this_wp_element_["createElement"])("p", null, tosPrompt), profileItems.setup_client && Object(external_this_wp_element_["createElement"])("p", null, wcPayDocPrompt)),
      before: Object(external_this_wp_element_["createElement"])(images_wcpay, null),
      visible: ['US'].includes(countryCode) && !hasCbdIndustry && isJetpackConnected,
      plugins: ['woocommerce-payments'],
      container: Object(external_this_wp_element_["createElement"])(wcpay, null),
      isConfigured: wcPayIsConfigured,
      isEnabled: options.woocommerce_woocommerce_payments_settings && options.woocommerce_woocommerce_payments_settings.enabled === 'yes',
      optionName: 'woocommerce_woocommerce_payments_settings'
    });
  }

  methods.push({
    key: 'stripe',
    title: Object(external_this_wp_i18n_["__"])('Credit cards - powered by Stripe', 'woocommerce'),
    content: Object(external_this_wp_element_["createElement"])(external_this_wp_element_["Fragment"], null, Object(external_this_wp_i18n_["__"])('Accept debit and credit cards in 135+ currencies, methods such as Alipay, ' + 'and one-touch checkout with Apple Pay.', 'woocommerce')),
    before: Object(external_this_wp_element_["createElement"])("img", {
      src: client_settings["e" /* WC_ASSET_URL */] + 'images/stripe.png',
      alt: ""
    }),
    visible: stripeCountries.includes(countryCode) && !hasCbdIndustry,
    plugins: ['woocommerce-gateway-stripe'],
    container: Object(external_this_wp_element_["createElement"])(stripe, null),
    isConfigured: options.woocommerce_stripe_settings && options.woocommerce_stripe_settings.publishable_key && options.woocommerce_stripe_settings.secret_key,
    isEnabled: options.woocommerce_stripe_settings && options.woocommerce_stripe_settings.enabled === 'yes',
    optionName: 'woocommerce_stripe_settings'
  }, {
    key: 'paypal',
    title: Object(external_this_wp_i18n_["__"])('PayPal Checkout', 'woocommerce'),
    content: Object(external_this_wp_element_["createElement"])(external_this_wp_element_["Fragment"], null, Object(external_this_wp_i18n_["__"])("Safe and secure payments using credit cards or your customer's PayPal account.", 'woocommerce')),
    before: Object(external_this_wp_element_["createElement"])("img", {
      src: client_settings["e" /* WC_ASSET_URL */] + 'images/paypal.png',
      alt: ""
    }),
    visible: !hasCbdIndustry,
    plugins: ['woocommerce-gateway-paypal-express-checkout'],
    container: Object(external_this_wp_element_["createElement"])(paypal, null),
    isConfigured: options.woocommerce_ppec_paypal_settings && options.woocommerce_ppec_paypal_settings.api_username && options.woocommerce_ppec_paypal_settings.api_password,
    isEnabled: options.woocommerce_ppec_paypal_settings && options.woocommerce_ppec_paypal_settings.enabled === 'yes',
    optionName: 'woocommerce_ppec_paypal_settings'
  }, {
    key: 'klarna_checkout',
    title: Object(external_this_wp_i18n_["__"])('Klarna Checkout', 'woocommerce'),
    content: Object(external_this_wp_i18n_["__"])('Choose the payment that you want, pay now, pay later or slice it. No credit card numbers, no passwords, no worries.', 'woocommerce'),
    before: Object(external_this_wp_element_["createElement"])("img", {
      src: client_settings["e" /* WC_ASSET_URL */] + 'images/klarna-black.png',
      alt: ""
    }),
    visible: ['SE', 'FI', 'NO', 'NL'].includes(countryCode) && !hasCbdIndustry,
    plugins: ['klarna-checkout-for-woocommerce'],
    container: Object(external_this_wp_element_["createElement"])(klarna, {
      plugin: 'checkout'
    }),
    // @todo This should check actual Klarna connection information.
    isConfigured: activePlugins.includes('klarna-checkout-for-woocommerce'),
    isEnabled: options.woocommerce_kco_settings && options.woocommerce_kco_settings.enabled === 'yes',
    optionName: 'woocommerce_kco_settings'
  }, {
    key: 'klarna_payments',
    title: Object(external_this_wp_i18n_["__"])('Klarna Payments', 'woocommerce'),
    content: Object(external_this_wp_i18n_["__"])('Choose the payment that you want, pay now, pay later or slice it. No credit card numbers, no passwords, no worries.', 'woocommerce'),
    before: Object(external_this_wp_element_["createElement"])("img", {
      src: client_settings["e" /* WC_ASSET_URL */] + 'images/klarna-black.png',
      alt: ""
    }),
    visible: ['DK', 'DE', 'AT'].includes(countryCode) && !hasCbdIndustry,
    plugins: ['klarna-payments-for-woocommerce'],
    container: Object(external_this_wp_element_["createElement"])(klarna, {
      plugin: 'payments'
    }),
    // @todo This should check actual Klarna connection information.
    isConfigured: activePlugins.includes('klarna-payments-for-woocommerce'),
    isEnabled: options.woocommerce_klarna_payments_settings && options.woocommerce_klarna_payments_settings.enabled === 'yes',
    optionName: 'woocommerce_klarna_payments_settings'
  }, {
    key: 'square',
    title: Object(external_this_wp_i18n_["__"])('Square', 'woocommerce'),
    content: Object(external_this_wp_element_["createElement"])(external_this_wp_element_["Fragment"], null, Object(external_this_wp_i18n_["__"])('Securely accept credit and debit cards with one low rate, no surprise fees (custom rates available). ' + 'Sell online and in store and track sales and inventory in one place.', 'woocommerce'), hasCbdIndustry && Object(external_this_wp_element_["createElement"])("span", {
      className: "text-style-strong"
    }, Object(external_this_wp_i18n_["__"])(' Selling CBD products is only supported by Square.', 'woocommerce'))),
    before: Object(external_this_wp_element_["createElement"])("img", {
      src: client_settings["e" /* WC_ASSET_URL */] + 'images/square-black.png',
      alt: ""
    }),
    visible: hasCbdIndustry && ['US'].includes(countryCode) || ['brick-mortar', 'brick-mortar-other'].includes(profileItems.selling_venues) && ['US', 'CA', 'JP', 'GB', 'AU'].includes(countryCode),
    plugins: ['woocommerce-square'],
    container: Object(external_this_wp_element_["createElement"])(square, null),
    isConfigured: options.wc_square_refresh_tokens && options.wc_square_refresh_tokens.length,
    isEnabled: options.woocommerce_square_credit_card_settings && options.woocommerce_square_credit_card_settings.enabled === 'yes',
    optionName: 'woocommerce_square_credit_card_settings',
    hasCbdIndustry: hasCbdIndustry
  }, {
    key: 'payfast',
    title: Object(external_this_wp_i18n_["__"])('PayFast', 'woocommerce'),
    content: Object(external_this_wp_element_["createElement"])(external_this_wp_element_["Fragment"], null, Object(external_this_wp_i18n_["__"])('The PayFast extension for WooCommerce enables you to accept payments by Credit Card and EFT via one of South Africa’s most popular payment gateways. No setup fees or monthly subscription costs.', 'woocommerce'), Object(external_this_wp_element_["createElement"])("p", null, Object(external_this_wp_i18n_["__"])('Selecting this extension will configure your store to use South African rands as the selected currency.', 'woocommerce'))),
    before: Object(external_this_wp_element_["createElement"])("img", {
      src: client_settings["e" /* WC_ASSET_URL */] + 'images/payfast.png',
      alt: "PayFast logo"
    }),
    visible: ['ZA'].includes(countryCode) && !hasCbdIndustry,
    plugins: ['woocommerce-payfast-gateway'],
    container: Object(external_this_wp_element_["createElement"])(payfast, null),
    isConfigured: options.woocommerce_payfast_settings && options.woocommerce_payfast_settings.merchant_id && options.woocommerce_payfast_settings.merchant_key && options.woocommerce_payfast_settings.pass_phrase,
    isEnabled: options.woocommerce_payfast_settings && options.woocommerce_payfast_settings.enabled === 'yes',
    optionName: 'woocommerce_payfast_settings'
  }, {
    key: 'cod',
    title: Object(external_this_wp_i18n_["__"])('Cash on delivery', 'woocommerce'),
    content: Object(external_this_wp_i18n_["__"])('Take payments in cash upon delivery.', 'woocommerce'),
    before: Object(external_this_wp_element_["createElement"])(cod, null),
    visible: !hasCbdIndustry,
    isEnabled: options.woocommerce_cod_settings && options.woocommerce_cod_settings.enabled === 'yes',
    optionName: 'woocommerce_cod_settings'
  }, {
    key: 'bacs',
    title: Object(external_this_wp_i18n_["__"])('Direct bank transfer', 'woocommerce'),
    content: Object(external_this_wp_i18n_["__"])('Take payments via bank transfer.', 'woocommerce'),
    before: Object(external_this_wp_element_["createElement"])(images_bacs, null),
    visible: !hasCbdIndustry,
    container: Object(external_this_wp_element_["createElement"])(bacs, null),
    isConfigured: options.woocommerce_bacs_accounts && options.woocommerce_bacs_accounts.length,
    isEnabled: options.woocommerce_bacs_settings && options.woocommerce_bacs_settings.enabled === 'yes',
    optionName: 'woocommerce_bacs_settings'
  });
  return Object(external_lodash_["filter"])(methods, function (method) {
    return method.visible;
  });
}
// CONCATENATED MODULE: ./client/dashboard/task-list/tasks/payments/index.js









function payments_ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function payments_objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { payments_ownKeys(Object(source), true).forEach(function (key) { defineProperty_default()(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { payments_ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function payments_createSuper(Derived) { var hasNativeReflectConstruct = payments_isNativeReflectConstruct(); return function () { var Super = getPrototypeOf_default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = getPrototypeOf_default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return possibleConstructorReturn_default()(this, result); }; }

function payments_isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/**
 * External dependencies
 */






/**
 * WooCommerce dependencies
 */




/**
 * Internal dependencies
 */







var payments_Payments = /*#__PURE__*/function (_Component) {
  inherits_default()(Payments, _Component);

  var _super = payments_createSuper(Payments);

  function Payments(props) {
    var _this;

    classCallCheck_default()(this, Payments);

    _this = _super.apply(this, arguments);
    var methods = props.methods;
    var enabledMethods = {};
    methods.forEach(function (method) {
      return enabledMethods[method.key] = method.isEnabled;
    });
    _this.state = {
      enabledMethods: enabledMethods
    };
    _this.recommendedMethod = 'stripe';
    methods.forEach(function (method) {
      if (method.key === 'wcpay' && method.visible) {
        _this.recommendedMethod = 'wcpay';
      }
    });
    _this.completeTask = _this.completeTask.bind(assertThisInitialized_default()(_this));
    _this.markConfigured = _this.markConfigured.bind(assertThisInitialized_default()(_this));
    _this.skipTask = _this.skipTask.bind(assertThisInitialized_default()(_this));
    return _this;
  }

  createClass_default()(Payments, [{
    key: "componentDidUpdate",
    value: function componentDidUpdate(prevProps) {
      var _this$props = this.props,
          createNotice = _this$props.createNotice,
          errors = _this$props.errors,
          methods = _this$props.methods,
          requesting = _this$props.requesting;
      methods.forEach(function (method) {
        var key = method.key,
            title = method.title;

        if (prevProps.requesting[key] && !requesting[key] && errors[key]) {
          createNotice('error', Object(external_this_wp_i18n_["sprintf"])(Object(external_this_wp_i18n_["__"])('There was a problem updating settings for %s', 'woocommerce'), title));
        }
      });
    }
  }, {
    key: "completeTask",
    value: function completeTask() {
      var _this$props2 = this.props,
          createNotice = _this$props2.createNotice,
          methods = _this$props2.methods,
          updateOptions = _this$props2.updateOptions;
      updateOptions({
        woocommerce_task_list_payments: {
          completed: 1,
          timestamp: Math.floor(Date.now() / 1000)
        }
      });
      Object(tracks["b" /* recordEvent */])('tasklist_payment_done', {
        configured: methods.filter(function (method) {
          return method.isConfigured;
        }).map(function (method) {
          return method.key;
        })
      });
      createNotice('success', Object(external_this_wp_i18n_["__"])('💰 Ka-ching! Your store can now accept payments 💳', 'woocommerce'));
      Object(external_this_wc_navigation_["getHistory"])().push(Object(external_this_wc_navigation_["getNewPath"])({}, '/', {}));
    }
  }, {
    key: "skipTask",
    value: function skipTask() {
      var _this$props3 = this.props,
          methods = _this$props3.methods,
          updateOptions = _this$props3.updateOptions;
      updateOptions({
        woocommerce_task_list_payments: {
          skipped: 1,
          timestamp: Math.floor(Date.now() / 1000)
        }
      });
      Object(tracks["b" /* recordEvent */])('tasklist_payment_skip_task', {
        options: methods.map(function (method) {
          return method.key;
        })
      });
      Object(external_this_wc_navigation_["getHistory"])().push(Object(external_this_wc_navigation_["getNewPath"])({}, '/', {}));
    }
  }, {
    key: "markConfigured",
    value: function markConfigured(method) {
      var enabledMethods = this.state.enabledMethods;
      this.setState({
        enabledMethods: payments_objectSpread({}, enabledMethods, defineProperty_default()({}, method, true))
      });
      Object(external_this_wc_navigation_["getHistory"])().push(Object(external_this_wc_navigation_["getNewPath"])({
        task: 'payments'
      }, '/', {}));
      Object(tracks["b" /* recordEvent */])('tasklist_payment_connect_method', {
        payment_method: method
      });
    }
  }, {
    key: "getCurrentMethod",
    value: function getCurrentMethod() {
      var _this$props4 = this.props,
          methods = _this$props4.methods,
          query = _this$props4.query;

      if (!query.method) {
        return;
      }

      return methods.find(function (method) {
        return method.key === query.method;
      });
    }
  }, {
    key: "getInstallStep",
    value: function getInstallStep() {
      var currentMethod = this.getCurrentMethod();

      if (!currentMethod.plugins || !currentMethod.plugins.length) {
        return;
      }

      var activePlugins = this.props.activePlugins;
      var pluginsToInstall = currentMethod.plugins.filter(function (method) {
        return !activePlugins.includes(method);
      });
      var pluginNamesString = currentMethod.plugins.map(function (pluginSlug) {
        return onboarding_constants["a" /* pluginNames */][pluginSlug];
      }).join(' ' + Object(external_this_wp_i18n_["__"])('and', 'woocommerce') + ' ');
      return {
        key: 'install',
        label: Object(external_this_wp_i18n_["sprintf"])(Object(external_this_wp_i18n_["__"])('Install %s', 'woocommerce'), pluginNamesString),
        content: Object(external_this_wp_element_["createElement"])(external_this_wc_components_["Plugins"], {
          onComplete: function onComplete() {
            Object(tracks["b" /* recordEvent */])('tasklist_payment_install_method', {
              plugins: currentMethod.plugins
            });
          },
          autoInstall: true,
          pluginSlugs: currentMethod.plugins
        }),
        isComplete: !pluginsToInstall.length
      };
    }
  }, {
    key: "toggleMethod",
    value: function toggleMethod(key) {
      var _this$props5 = this.props,
          methods = _this$props5.methods,
          options = _this$props5.options,
          updateOptions = _this$props5.updateOptions;
      var enabledMethods = this.state.enabledMethods;
      var method = methods.find(function (option) {
        return option.key === key;
      });
      enabledMethods[key] = !enabledMethods[key];
      this.setState({
        enabledMethods: enabledMethods
      });
      Object(tracks["b" /* recordEvent */])('tasklist_payment_toggle', {
        enabled: !method.isEnabled,
        payment_method: key
      });
      updateOptions(defineProperty_default()({}, method.optionName, payments_objectSpread({}, options[method.optionName], {
        enabled: method.isEnabled ? 'no' : 'yes'
      })));
    }
  }, {
    key: "render",
    value: function render() {
      var _this2 = this;

      var currentMethod = this.getCurrentMethod();
      var _this$props6 = this.props,
          methods = _this$props6.methods,
          query = _this$props6.query;
      var enabledMethods = this.state.enabledMethods;
      var configuredMethods = methods.filter(function (method) {
        return method.isConfigured;
      }).length;

      if (currentMethod) {
        return Object(external_this_wp_element_["createElement"])(external_this_wc_components_["Card"], {
          className: "woocommerce-task-payment-method is-narrow"
        }, Object(external_this_wp_element_["cloneElement"])(currentMethod.container, {
          query: query,
          installStep: this.getInstallStep(),
          markConfigured: this.markConfigured,
          hasCbdIndustry: currentMethod.hasCbdIndustry
        }));
      }

      return Object(external_this_wp_element_["createElement"])("div", {
        className: "woocommerce-task-payments"
      }, methods.map(function (method) {
        var before = method.before,
            container = method.container,
            content = method.content,
            isConfigured = method.isConfigured,
            key = method.key,
            title = method.title,
            visible = method.visible;

        if (!visible) {
          return null;
        }

        var classes = classnames_default()('woocommerce-task-payment', 'is-narrow', !isConfigured && 'woocommerce-task-payment-not-configured', 'woocommerce-task-payment-' + key);
        var isRecommended = key === _this2.recommendedMethod && !isConfigured;
        var showRecommendedRibbon = isRecommended && _this2.recommendedMethod !== 'wcpay';
        var showRecommendedPill = isRecommended && _this2.recommendedMethod === 'wcpay';
        return Object(external_this_wp_element_["createElement"])(external_this_wc_components_["Card"], {
          key: key,
          className: classes
        }, Object(external_this_wp_element_["createElement"])("div", {
          className: "woocommerce-task-payment__before"
        }, showRecommendedRibbon && Object(external_this_wp_element_["createElement"])("div", {
          className: "woocommerce-task-payment__recommended-ribbon"
        }, Object(external_this_wp_element_["createElement"])("span", null, Object(external_this_wp_i18n_["__"])('Recommended', 'woocommerce'))), before), Object(external_this_wp_element_["createElement"])("div", {
          className: "woocommerce-task-payment__text"
        }, Object(external_this_wp_element_["createElement"])(external_this_wc_components_["H"], {
          className: "woocommerce-task-payment__title"
        }, title, showRecommendedPill && Object(external_this_wp_element_["createElement"])("span", {
          className: "woocommerce-task-payment__recommended-pill"
        }, Object(external_this_wp_i18n_["__"])('Recommended', 'woocommerce'))), Object(external_this_wp_element_["createElement"])("div", {
          className: "woocommerce-task-payment__content"
        }, content)), Object(external_this_wp_element_["createElement"])("div", {
          className: "woocommerce-task-payment__after"
        }, container && !isConfigured ? Object(external_this_wp_element_["createElement"])(build_module_button["a" /* default */], {
          isPrimary: key === _this2.recommendedMethod,
          isDefault: key !== _this2.recommendedMethod,
          onClick: function onClick() {
            Object(tracks["b" /* recordEvent */])('tasklist_payment_setup', {
              options: methods.map(function (option) {
                return option.key;
              }),
              selected: key
            });
            Object(external_this_wc_navigation_["updateQueryString"])({
              method: key
            });
          }
        }, Object(external_this_wp_i18n_["__"])('Set up', 'woocommerce')) : Object(external_this_wp_element_["createElement"])(form_toggle["a" /* default */], {
          checked: enabledMethods[key],
          onChange: function onChange() {
            return _this2.toggleMethod(key);
          },
          onClick: function onClick(e) {
            return e.stopPropagation();
          }
        })));
      }), Object(external_this_wp_element_["createElement"])("div", {
        className: "woocommerce-task-payments__actions"
      }, configuredMethods.length === 0 ? Object(external_this_wp_element_["createElement"])(build_module_button["a" /* default */], {
        isLink: true,
        onClick: this.skipTask
      }, Object(external_this_wp_i18n_["__"])('My store doesn’t take payments', 'woocommerce')) : Object(external_this_wp_element_["createElement"])(build_module_button["a" /* default */], {
        isPrimary: true,
        onClick: this.completeTask
      }, Object(external_this_wp_i18n_["__"])('Done', 'woocommerce'))));
    }
  }]);

  return Payments;
}(external_this_wp_element_["Component"]);

/* harmony default export */ var payments = (Object(compose["a" /* default */])(Object(with_select["a" /* default */])(function (select) {
  var _select = select('wc-api'),
      getProfileItems = _select.getProfileItems,
      getOptions = _select.getOptions,
      getUpdateOptionsError = _select.getUpdateOptionsError,
      isUpdateOptionsRequesting = _select.isUpdateOptionsRequesting;

  var _select2 = select(external_this_wc_data_["PLUGINS_STORE_NAME"]),
      getActivePlugins = _select2.getActivePlugins,
      isJetpackConnected = _select2.isJetpackConnected;

  var activePlugins = getActivePlugins();
  var profileItems = getProfileItems();
  var options = getOptions(['woocommerce_default_country', 'woocommerce_woocommerce_payments_settings', 'woocommerce_stripe_settings', 'woocommerce_ppec_paypal_settings', 'woocommerce_payfast_settings', 'woocommerce_square_credit_card_settings', 'woocommerce_klarna_payments_settings', 'woocommerce_kco_settings', 'wc_square_refresh_tokens', 'woocommerce_cod_settings', 'woocommerce_bacs_settings', 'woocommerce_bacs_accounts']);
  var countryCode = Object(utils["a" /* getCountryCode */])(options.woocommerce_default_country);
  var methods = getPaymentMethods({
    activePlugins: activePlugins,
    countryCode: countryCode,
    isJetpackConnected: isJetpackConnected(),
    options: options,
    profileItems: profileItems
  });
  var errors = {};
  var requesting = {};
  methods.forEach(function (method) {
    errors[method.key] = Boolean(getUpdateOptionsError([method.optionName]));
    requesting[method.key] = Boolean(isUpdateOptionsRequesting([method.optionName]));
  });
  return {
    countryCode: countryCode,
    errors: errors,
    profileItems: profileItems,
    activePlugins: activePlugins,
    options: options,
    methods: methods,
    requesting: requesting
  };
}), Object(external_this_wp_data_["withDispatch"])(function (dispatch) {
  var _dispatch = dispatch('core/notices'),
      createNotice = _dispatch.createNotice;

  var _dispatch2 = dispatch('wc-api'),
      updateOptions = _dispatch2.updateOptions;

  return {
    createNotice: createNotice,
    updateOptions: updateOptions
  };
}))(payments_Payments));
// CONCATENATED MODULE: ./client/dashboard/task-list/tasks.js


/**
 * External dependencies
 */



/**
 * WooCommerce dependencies
 */



/**
 * Internal dependencies
 */








function getAllTasks(_ref) {
  var profileItems = _ref.profileItems,
      options = _ref.options,
      query = _ref.query,
      toggleCartModal = _ref.toggleCartModal;

  var _getSetting = Object(client_settings["g" /* getSetting */])('onboarding', {
    hasPhysicalProducts: false,
    hasProducts: false,
    isAppearanceComplete: false,
    isTaxComplete: false,
    shippingZonesCount: 0
  }),
      hasPhysicalProducts = _getSetting.hasPhysicalProducts,
      hasProducts = _getSetting.hasProducts,
      isAppearanceComplete = _getSetting.isAppearanceComplete,
      isTaxComplete = _getSetting.isTaxComplete,
      shippingZonesCount = _getSetting.shippingZonesCount;

  var productIds = Object(utils["d" /* getProductIdsForCart */])(profileItems, true);
  var remainingProductIds = Object(utils["d" /* getProductIdsForCart */])(profileItems);
  var paymentsCompleted = Object(external_lodash_["get"])(options, ['woocommerce_task_list_payments', 'completed'], false);
  var paymentsSkipped = Object(external_lodash_["get"])(options, ['woocommerce_task_list_payments', 'skipped'], false);
  var tasks = [{
    key: 'purchase',
    title: Object(external_this_wp_i18n_["__"])('Purchase & install extensions', 'woocommerce'),
    content: Object(external_this_wp_i18n_["__"])('Purchase, install, and manage your extensions directly from your dashboard', 'wooocommerce-admin'),
    icon: 'extension',
    container: null,
    onClick: function onClick() {
      return remainingProductIds.length ? toggleCartModal() : null;
    },
    visible: productIds.length,
    completed: !remainingProductIds.length
  }, {
    key: 'connect',
    title: Object(external_this_wp_i18n_["__"])('Connect your store to WooCommerce.com', 'woocommerce'),
    content: Object(external_this_wp_i18n_["__"])('Install and manage your extensions directly from your Dashboard', 'wooocommerce-admin'),
    icon: 'extension',
    container: Object(external_this_wp_element_["createElement"])(tasks_connect, {
      query: query
    }),
    visible: profileItems.items_purchased && !profileItems.wccom_connected,
    completed: profileItems.wccom_connected
  }, {
    key: 'products',
    title: Object(external_this_wp_i18n_["__"])('Add your first product', 'woocommerce'),
    content: Object(external_this_wp_i18n_["__"])('Add products manually, import from a sheet or migrate from another platform', 'wooocommerce-admin'),
    icon: 'add_box',
    container: Object(external_this_wp_element_["createElement"])(products_Products, null),
    completed: hasProducts,
    visible: true
  }, {
    key: 'appearance',
    title: Object(external_this_wp_i18n_["__"])('Personalize your store', 'woocommerce'),
    content: Object(external_this_wp_i18n_["__"])('Create a custom homepage and upload your logo', 'wooocommerce-admin'),
    icon: 'palette',
    container: Object(external_this_wp_element_["createElement"])(appearance, null),
    completed: isAppearanceComplete,
    visible: true
  }, {
    key: 'shipping',
    title: Object(external_this_wp_i18n_["__"])('Set up shipping', 'woocommerce'),
    content: Object(external_this_wp_i18n_["__"])('Configure some basic shipping rates to get started', 'wooocommerce-admin'),
    icon: 'local_shipping',
    container: Object(external_this_wp_element_["createElement"])(shipping, null),
    completed: shippingZonesCount > 0,
    visible: profileItems.product_types && profileItems.product_types.includes('physical') || hasPhysicalProducts
  }, {
    key: 'tax',
    title: Object(external_this_wp_i18n_["__"])('Set up tax', 'woocommerce'),
    content: Object(external_this_wp_i18n_["__"])('Choose how to configure tax rates - manually or automatically', 'wooocommerce-admin'),
    icon: 'account_balance',
    container: Object(external_this_wp_element_["createElement"])(tax, null),
    completed: isTaxComplete,
    visible: true
  }, {
    key: 'payments',
    title: Object(external_this_wp_i18n_["__"])('Set up payments', 'woocommerce'),
    content: Object(external_this_wp_i18n_["__"])('Select which payment providers you’d like to use and configure them', 'wooocommerce-admin'),
    icon: 'payment',
    container: Object(external_this_wp_element_["createElement"])(payments, null),
    completed: paymentsCompleted || paymentsSkipped,
    onClick: function onClick() {
      if (paymentsCompleted || paymentsSkipped) {
        window.location = Object(client_settings["f" /* getAdminLink */])('admin.php?page=wc-settings&tab=checkout');
        return;
      }

      Object(external_this_wc_navigation_["updateQueryString"])({
        task: 'payments'
      });
    },
    visible: true
  }];
  return Object(external_this_wp_hooks_["applyFilters"])('woocommerce_admin_onboarding_task_list', tasks, query);
}
// CONCATENATED MODULE: ./client/dashboard/task-list/index.js








function task_list_ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function task_list_objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { task_list_ownKeys(Object(source), true).forEach(function (key) { defineProperty_default()(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { task_list_ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function task_list_createSuper(Derived) { var hasNativeReflectConstruct = task_list_isNativeReflectConstruct(); return function () { var Super = getPrototypeOf_default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = getPrototypeOf_default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return possibleConstructorReturn_default()(this, result); }; }

function task_list_isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/**
 * External dependencies
 */







/**
 * WooCommerce dependencies
 */




/**
 * Internal dependencies
 */







var task_list_TaskDashboard = /*#__PURE__*/function (_Component) {
  inherits_default()(TaskDashboard, _Component);

  var _super = task_list_createSuper(TaskDashboard);

  function TaskDashboard(props) {
    var _this;

    classCallCheck_default()(this, TaskDashboard);

    _this = _super.call(this, props);

    _this.onSkipStoreSetup = function () {
      var completedTaskKeys = _this.getTasks().filter(function (x) {
        return x.completed;
      }).map(function (x) {
        return x.key;
      });

      Object(tracks["b" /* recordEvent */])('tasklist_skip', {
        completed_tasks_count: completedTaskKeys.length,
        completed_tasks: completedTaskKeys,
        reason: 'skip'
      });

      _this.props.updateOptions({
        woocommerce_task_list_hidden: 'yes'
      });
    };

    _this.state = {
      isCartModalOpen: false,
      isWelcomeModalOpen: !props.modalDismissed
    };
    return _this;
  }

  createClass_default()(TaskDashboard, [{
    key: "componentDidMount",
    value: function componentDidMount() {
      var _this$props = this.props,
          incompleteTasks = _this$props.incompleteTasks,
          updateOptions = _this$props.updateOptions;
      document.body.classList.add('woocommerce-onboarding');
      document.body.classList.add('woocommerce-task-dashboard__body');
      this.recordTaskView();
      this.recordTaskListView();

      if (!incompleteTasks.length) {
        updateOptions({
          woocommerce_task_list_complete: true
        });
      }

      this.possiblyTrackCompletedTasks();
    }
  }, {
    key: "componentDidUpdate",
    value: function componentDidUpdate(prevProps) {
      var _this$props2 = this.props,
          completedTaskKeys = _this$props2.completedTaskKeys,
          incompleteTasks = _this$props2.incompleteTasks,
          query = _this$props2.query,
          updateOptions = _this$props2.updateOptions;
      var prevCompletedTaskKeys = prevProps.completedTaskKeys,
          prevIncompleteTasks = prevProps.incompleteTasks,
          prevQuery = prevProps.query;
      var prevTask = prevQuery.task;
      var task = query.task;

      if (prevTask !== task) {
        window.document.documentElement.scrollTop = 0;
        this.recordTaskView();
      }

      if (!incompleteTasks.length && prevIncompleteTasks.length) {
        updateOptions({
          woocommerce_task_list_complete: true
        });
      }

      if (!Object(external_lodash_["isEqual"])(prevCompletedTaskKeys, completedTaskKeys)) {
        this.possiblyTrackCompletedTasks();
      }
    }
  }, {
    key: "possiblyTrackCompletedTasks",
    value: function possiblyTrackCompletedTasks() {
      var _this$props3 = this.props,
          completedTaskKeys = _this$props3.completedTaskKeys,
          trackedCompletedTasks = _this$props3.trackedCompletedTasks,
          updateOptions = _this$props3.updateOptions;

      if (!Object(external_lodash_["isEqual"])(trackedCompletedTasks, completedTaskKeys)) {
        updateOptions({
          woocommerce_task_list_tracked_completed_tasks: completedTaskKeys
        });
      }
    }
  }, {
    key: "componentWillUnmount",
    value: function componentWillUnmount() {
      document.body.classList.remove('woocommerce-onboarding');
      document.body.classList.remove('woocommerce-task-dashboard__body');
    }
  }, {
    key: "getTasks",
    value: function getTasks() {
      var _this$props4 = this.props,
          profileItems = _this$props4.profileItems,
          query = _this$props4.query,
          taskListPayments = _this$props4.taskListPayments;
      return getAllTasks({
        profileItems: profileItems,
        options: taskListPayments,
        query: query,
        toggleCartModal: this.toggleCartModal.bind(this)
      }).filter(function (task) {
        return task.visible;
      });
    }
  }, {
    key: "getPluginsInformation",
    value: function getPluginsInformation() {
      var _this$props5 = this.props,
          isJetpackConnected = _this$props5.isJetpackConnected,
          activePlugins = _this$props5.activePlugins,
          installedPlugins = _this$props5.installedPlugins;
      return {
        wcs_installed: installedPlugins.includes('woocommerce-services'),
        wcs_active: activePlugins.includes('woocommerce-services'),
        jetpack_installed: installedPlugins.includes('jetpack'),
        jetpack_active: activePlugins.includes('jetpack'),
        jetpack_connected: isJetpackConnected
      };
    }
  }, {
    key: "recordTaskView",
    value: function recordTaskView() {
      var task = this.props.query.task; // eslint-disable-next-line @wordpress/no-unused-vars-before-return

      var pluginsInformation = this.getPluginsInformation();

      if (!task) {
        return;
      }

      Object(tracks["b" /* recordEvent */])('task_view', task_list_objectSpread({
        task_name: task
      }, pluginsInformation));
    }
  }, {
    key: "recordTaskListView",
    value: function recordTaskListView() {
      if (this.getCurrentTask()) {
        return;
      }

      var profileItems = this.props.profileItems;
      var tasks = this.getTasks();
      Object(tracks["b" /* recordEvent */])('tasklist_view', {
        number_tasks: tasks.length,
        store_connected: profileItems.wccom_connected
      });
    }
  }, {
    key: "keepTaskCard",
    value: function keepTaskCard() {
      Object(tracks["b" /* recordEvent */])('tasklist_completed', {
        action: 'keep_card'
      });
      this.props.updateOptions({
        woocommerce_task_list_prompt_shown: true
      });
    }
  }, {
    key: "hideTaskCard",
    value: function hideTaskCard(action) {
      Object(tracks["b" /* recordEvent */])('tasklist_completed', {
        action: action
      });
      this.props.updateOptions({
        woocommerce_task_list_hidden: 'yes',
        woocommerce_task_list_prompt_shown: true
      });
    }
  }, {
    key: "getCurrentTask",
    value: function getCurrentTask() {
      var task = this.props.query.task;
      var currentTask = this.getTasks().find(function (s) {
        return s.key === task;
      });

      if (!currentTask) {
        return null;
      }

      return currentTask;
    }
  }, {
    key: "renderPrompt",
    value: function renderPrompt() {
      var _this2 = this;

      if (this.props.promptShown) {
        return null;
      }

      return Object(external_this_wp_element_["createElement"])(snackbar["a" /* default */], {
        className: "woocommerce-task-card__prompt"
      }, Object(external_this_wp_element_["createElement"])("div", {
        className: "woocommerce-task-card__prompt-pointer"
      }), Object(external_this_wp_element_["createElement"])("div", {
        className: "woocommerce-task-card__prompt-content"
      }, Object(external_this_wp_element_["createElement"])("span", null, Object(external_this_wp_i18n_["__"])('Is this card useful?', 'woocommerce')), Object(external_this_wp_element_["createElement"])("div", {
        className: "woocommerce-task-card__prompt-actions"
      }, Object(external_this_wp_element_["createElement"])(build_module_button["a" /* default */], {
        isLink: true,
        onClick: function onClick() {
          return _this2.hideTaskCard('hide_card');
        }
      }, Object(external_this_wp_i18n_["__"])('No, hide it', 'woocommerce')), Object(external_this_wp_element_["createElement"])(build_module_button["a" /* default */], {
        isLink: true,
        onClick: function onClick() {
          return _this2.keepTaskCard();
        }
      }, Object(external_this_wp_i18n_["__"])('Yes, keep it', 'woocommerce')))));
    }
  }, {
    key: "renderMenu",
    value: function renderMenu() {
      var _this3 = this;

      return Object(external_this_wp_element_["createElement"])(external_this_wc_components_["EllipsisMenu"], {
        label: Object(external_this_wp_i18n_["__"])('Task List Options', 'woocommerce'),
        renderContent: function renderContent() {
          return Object(external_this_wp_element_["createElement"])("div", {
            className: "woocommerce-task-card__section-controls"
          }, Object(external_this_wp_element_["createElement"])(external_this_wc_components_["MenuItem"], {
            isClickable: true,
            onInvoke: function onInvoke() {
              return _this3.hideTaskCard('remove_card');
            }
          }, Object(external_this_wp_element_["createElement"])(icon["a" /* default */], {
            icon: 'trash',
            label: Object(external_this_wp_i18n_["__"])('Remove block')
          }), Object(external_this_wp_i18n_["__"])('Remove this card', 'woocommerce')));
        }
      });
    }
  }, {
    key: "toggleCartModal",
    value: function toggleCartModal() {
      var isCartModalOpen = this.state.isCartModalOpen;

      if (!isCartModalOpen) {
        Object(tracks["b" /* recordEvent */])('tasklist_purchase_extensions');
      }

      this.setState({
        isCartModalOpen: !isCartModalOpen
      });
    }
  }, {
    key: "closeWelcomeModal",
    value: function closeWelcomeModal() {
      // Prevent firing this event before the modal is seen.
      if (document.body.classList.contains('woocommerce-admin-is-loading')) {
        return;
      }

      this.setState({
        isWelcomeModalOpen: false
      });
      this.props.updateOptions({
        woocommerce_task_list_welcome_modal_dismissed: true
      });
    }
  }, {
    key: "renderWelcomeModal",
    value: function renderWelcomeModal() {
      var _this4 = this;

      return Object(external_this_wp_element_["createElement"])(modal["a" /* default */], {
        title: Object(external_this_wp_element_["createElement"])(external_this_wp_element_["Fragment"], null, Object(external_this_wp_element_["createElement"])("span", {
          role: "img",
          "aria-hidden": "true",
          focusable: "false",
          className: "woocommerce-task-dashboard__welcome-modal-icon"
        }, "\uD83D\uDE80"), Object(external_this_wp_i18n_["__"])("Woo hoo - you're almost there!", 'woocommerce')),
        onRequestClose: function onRequestClose() {
          return _this4.closeWelcomeModal();
        },
        className: "woocommerce-task-dashboard__welcome-modal"
      }, Object(external_this_wp_element_["createElement"])("div", {
        className: "woocommerce-task-dashboard__welcome-modal-wrapper"
      }, Object(external_this_wp_element_["createElement"])("div", {
        className: "woocommerce-task-dashboard__welcome-modal-message"
      }, Object(external_this_wp_element_["createElement"])("p", null, Object(external_this_wp_i18n_["__"])('Based on the information you provided we’ve prepared some final set up tasks for you to perform.', 'woocommerce')), Object(external_this_wp_element_["createElement"])("p", null, Object(external_this_wp_i18n_["__"])('Once complete your store will be ready for launch - exciting!', 'woocommerce'))), Object(external_this_wp_element_["createElement"])(build_module_button["a" /* default */], {
        isPrimary: true,
        isDefault: true,
        onClick: function onClick() {
          return _this4.closeWelcomeModal();
        }
      }, Object(external_this_wp_i18n_["__"])('Continue', 'woocommerce'))));
    }
  }, {
    key: "renderSkipActions",
    value: function renderSkipActions() {
      return Object(external_this_wp_element_["createElement"])("div", {
        className: "skip-actions"
      }, Object(external_this_wp_element_["createElement"])(build_module_button["a" /* default */], {
        isLink: true,
        className: "is-secondary",
        onClick: this.onSkipStoreSetup
      }, Object(external_this_wp_i18n_["__"])('Skip store setup', 'woocommerce')));
    }
  }, {
    key: "render",
    value: function render() {
      var _this5 = this;

      var _this$props6 = this.props,
          inline = _this$props6.inline,
          query = _this$props6.query;
      var _this$state = this.state,
          isCartModalOpen = _this$state.isCartModalOpen,
          isWelcomeModalOpen = _this$state.isWelcomeModalOpen;
      var currentTask = this.getCurrentTask();
      var listTasks = this.getTasks().map(function (task) {
        task.className = classnames_default()(task.completed ? 'is-complete' : null, task.className);
        task.before = task.completed ? Object(external_this_wp_element_["createElement"])("i", {
          className: "material-icons-outlined"
        }, "check_circle") : Object(external_this_wp_element_["createElement"])("i", {
          className: "material-icons-outlined"
        }, task.icon);
        task.after = Object(external_this_wp_element_["createElement"])("i", {
          className: "material-icons-outlined"
        }, "chevron_right");

        if (!task.onClick) {
          task.onClick = function () {
            return Object(external_this_wc_navigation_["updateQueryString"])({
              task: task.key
            });
          };
        }

        return task;
      });
      return Object(external_this_wp_element_["createElement"])(external_this_wp_element_["Fragment"], null, Object(external_this_wp_element_["createElement"])("div", {
        className: "woocommerce-task-dashboard__container"
      }, currentTask ? Object(external_this_wp_element_["cloneElement"])(currentTask.container, {
        query: query
      }) : Object(external_this_wp_element_["createElement"])(external_this_wp_element_["Fragment"], null, Object(external_this_wp_element_["createElement"])(external_this_wc_components_["Card"], {
        className: "woocommerce-task-card",
        title: Object(external_this_wp_i18n_["__"])('Set up your store and start selling', 'woocommerce'),
        description: Object(external_this_wp_i18n_["__"])('Below you’ll find a list of the most important steps to get your store up and running.', 'woocommerce'),
        menu: inline && this.renderMenu()
      }, Object(external_this_wp_element_["createElement"])(external_this_wc_components_["List"], {
        items: listTasks
      })), inline && this.renderPrompt(), isWelcomeModalOpen && this.renderWelcomeModal(), this.renderSkipActions())), isCartModalOpen && Object(external_this_wp_element_["createElement"])(cart_modal, {
        onClose: function onClose() {
          return _this5.toggleCartModal();
        },
        onClickPurchaseLater: function onClickPurchaseLater() {
          return _this5.toggleCartModal();
        }
      }));
    }
  }]);

  return TaskDashboard;
}(external_this_wp_element_["Component"]);

/* harmony default export */ var task_list = __webpack_exports__["default"] = (Object(compose["a" /* default */])(Object(with_select["a" /* default */])(function (select, props) {
  var _select = select('wc-api'),
      getProfileItems = _select.getProfileItems,
      getOptions = _select.getOptions;

  var _select2 = select(external_this_wc_data_["PLUGINS_STORE_NAME"]),
      getActivePlugins = _select2.getActivePlugins,
      getInstalledPlugins = _select2.getInstalledPlugins,
      isJetpackConnected = _select2.isJetpackConnected;

  var profileItems = getProfileItems();
  var options = getOptions(['woocommerce_task_list_prompt_shown', 'woocommerce_task_list_welcome_modal_dismissed', 'woocommerce_task_list_hidden', 'woocommerce_task_list_tracked_completed_tasks']);
  var promptShown = Object(external_lodash_["get"])(options, ['woocommerce_task_list_prompt_shown'], false);
  var modalDismissed = Object(external_lodash_["get"])(options, ['woocommerce_task_list_welcome_modal_dismissed'], false);
  var taskListPayments = getOptions(['woocommerce_task_list_payments']);
  var trackedCompletedTasks = Object(external_lodash_["get"])(options, ['woocommerce_task_list_tracked_completed_tasks'], []);
  var tasks = getAllTasks({
    profileItems: profileItems,
    options: getOptions(['woocommerce_task_list_payments']),
    query: props.query
  });
  var completedTaskKeys = tasks.filter(function (task) {
    return task.completed;
  }).map(function (task) {
    return task.key;
  });
  var incompleteTasks = tasks.filter(function (task) {
    return task.visible && !task.completed;
  });
  var activePlugins = getActivePlugins();
  var installedPlugins = getInstalledPlugins();
  return {
    modalDismissed: modalDismissed,
    profileItems: profileItems,
    promptShown: promptShown,
    taskListPayments: taskListPayments,
    isJetpackConnected: isJetpackConnected(),
    incompleteTasks: incompleteTasks,
    trackedCompletedTasks: trackedCompletedTasks,
    completedTaskKeys: completedTaskKeys,
    activePlugins: activePlugins,
    installedPlugins: installedPlugins
  };
}), Object(external_this_wp_data_["withDispatch"])(function (dispatch) {
  var _dispatch = dispatch('wc-api'),
      updateOptions = _dispatch.updateOptions;

  return {
    updateOptions: updateOptions
  };
}))(task_list_TaskDashboard));

/***/ })

}]);
