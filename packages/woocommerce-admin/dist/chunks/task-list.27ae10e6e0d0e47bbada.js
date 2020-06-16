(window["webpackJsonp"] = window["webpackJsonp"] || []).push([["task-list"],{

/***/ "./client/dashboard/components/cart-modal.js":
/*!***************************************************!*\
  !*** ./client/dashboard/components/cart-modal.js ***!
  \***************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/classCallCheck */ "./node_modules/@babel/runtime/helpers/classCallCheck.js");
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/createClass */ "./node_modules/@babel/runtime/helpers/createClass.js");
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @babel/runtime/helpers/inherits */ "./node_modules/@babel/runtime/helpers/inherits.js");
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @babel/runtime/helpers/possibleConstructorReturn */ "./node_modules/@babel/runtime/helpers/possibleConstructorReturn.js");
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @babel/runtime/helpers/getPrototypeOf */ "./node_modules/@babel/runtime/helpers/getPrototypeOf.js");
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @wordpress/compose */ "./node_modules/@wordpress/compose/build-module/index.js");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! @wordpress/components */ "./node_modules/@wordpress/components/build-module/index.js");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! lodash */ "lodash");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_9__);
/* harmony import */ var _wordpress_html_entities__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! @wordpress/html-entities */ "@wordpress/html-entities");
/* harmony import */ var _wordpress_html_entities__WEBPACK_IMPORTED_MODULE_10___default = /*#__PURE__*/__webpack_require__.n(_wordpress_html_entities__WEBPACK_IMPORTED_MODULE_10__);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_11___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_11__);
/* harmony import */ var _woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! @woocommerce/wc-admin-settings */ "./client/settings/index.js");
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! @woocommerce/components */ "@woocommerce/components");
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_13___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_components__WEBPACK_IMPORTED_MODULE_13__);
/* harmony import */ var _woocommerce_data__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! @woocommerce/data */ "@woocommerce/data");
/* harmony import */ var _woocommerce_data__WEBPACK_IMPORTED_MODULE_14___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_data__WEBPACK_IMPORTED_MODULE_14__);
/* harmony import */ var dashboard_utils__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(/*! dashboard/utils */ "./client/dashboard/utils.js");
/* harmony import */ var lib_sanitize_html__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__(/*! lib/sanitize-html */ "./client/lib/sanitize-html/index.js");
/* harmony import */ var lib_tracks__WEBPACK_IMPORTED_MODULE_17__ = __webpack_require__(/*! lib/tracks */ "./client/lib/tracks.js");
/* harmony import */ var lib_in_app_purchase__WEBPACK_IMPORTED_MODULE_18__ = __webpack_require__(/*! lib/in-app-purchase */ "./client/lib/in-app-purchase.js");







function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_4___default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_4___default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_3___default()(this, result); }; }

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






var CartModal = /*#__PURE__*/function (_Component) {
  _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_2___default()(CartModal, _Component);

  var _super = _createSuper(CartModal);

  function CartModal(props) {
    var _this;

    _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0___default()(this, CartModal);

    _this = _super.call(this, props);
    _this.state = {
      purchaseNowButtonBusy: false,
      purchaseLaterButtonBusy: false
    };
    return _this;
  }

  _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1___default()(CartModal, [{
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

      Object(lib_tracks__WEBPACK_IMPORTED_MODULE_17__["recordEvent"])('tasklist_modal_proceed_checkout', {
        product_ids: productIds,
        purchase_install: true
      });
      var url = Object(lib_in_app_purchase__WEBPACK_IMPORTED_MODULE_18__["getInAppPurchaseUrl"])('https://woocommerce.com/cart', {
        'wccom-replace-with': productIds.join(',')
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
      Object(lib_tracks__WEBPACK_IMPORTED_MODULE_17__["recordEvent"])('tasklist_modal_proceed_checkout', {
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
      Object(lib_tracks__WEBPACK_IMPORTED_MODULE_17__["recordEvent"])('tasklist_modal_proceed_checkout', {
        product_ids: productIds,
        purchase_install: false
      });
      onClose();
    }
  }, {
    key: "renderProducts",
    value: function renderProducts() {
      var productIds = this.props.productIds;

      var _getSetting = Object(_woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_12__["getSetting"])('onboarding', {}),
          _getSetting$productTy = _getSetting.productTypes,
          productTypes = _getSetting$productTy === void 0 ? {} : _getSetting$productTy,
          _getSetting$themes = _getSetting.themes,
          themes = _getSetting$themes === void 0 ? [] : _getSetting$themes;

      var listItems = [];
      productIds.forEach(function (productId) {
        var productInfo = Object(lodash__WEBPACK_IMPORTED_MODULE_9__["find"])(productTypes, function (productType) {
          return productType.product === productId;
        });

        if (productInfo) {
          listItems.push({
            title: productInfo.label,
            content: productInfo.description
          });
        }

        var themeInfo = Object(lodash__WEBPACK_IMPORTED_MODULE_9__["find"])(themes, function (theme) {
          return theme.id === productId;
        });

        if (themeInfo) {
          listItems.push({
            title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__["sprintf"])(Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__["__"])('%s — %s per year', 'woocommerce'), themeInfo.title, Object(_wordpress_html_entities__WEBPACK_IMPORTED_MODULE_10__["decodeEntities"])(themeInfo.price)),
            content: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])("span", {
              dangerouslySetInnerHTML: Object(lib_sanitize_html__WEBPACK_IMPORTED_MODULE_16__["default"])(themeInfo.excerpt)
            })
          });
        }
      });
      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_13__["List"], {
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
      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_8__["Modal"], {
        title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__["__"])('Would you like to purchase and install the following features now?', 'woocommerce'),
        onRequestClose: function onRequestClose() {
          return _this2.onClose();
        },
        className: "woocommerce-cart-modal"
      }, this.renderProducts(), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])("p", {
        className: "woocommerce-cart-modal__help-text"
      }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__["__"])("You won't have access to this functionality until the extensions have been purchased and installed.", 'woocommerce')), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])("div", {
        className: "woocommerce-cart-modal__actions"
      }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_8__["Button"], {
        isLink: true,
        isBusy: purchaseLaterButtonBusy,
        onClick: function onClick() {
          return _this2.onClickPurchaseLater();
        }
      }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__["__"])("I'll do it later", 'woocommerce')), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_8__["Button"], {
        isPrimary: true,
        isBusy: purchaseNowButtonBusy,
        onClick: function onClick() {
          return _this2.onClickPurchaseNow();
        }
      }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__["__"])('Purchase & install now', 'woocommerce'))));
    }
  }]);

  return CartModal;
}(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["Component"]);

/* harmony default export */ __webpack_exports__["default"] = (Object(_wordpress_compose__WEBPACK_IMPORTED_MODULE_7__["compose"])(Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_11__["withSelect"])(function (select) {
  var _select = select(_woocommerce_data__WEBPACK_IMPORTED_MODULE_14__["PLUGINS_STORE_NAME"]),
      getInstalledPlugins = _select.getInstalledPlugins;

  var _select2 = select(_woocommerce_data__WEBPACK_IMPORTED_MODULE_14__["ONBOARDING_STORE_NAME"]),
      getProfileItems = _select2.getProfileItems;

  var profileItems = getProfileItems();
  var installedPlugins = getInstalledPlugins();
  var productIds = Object(dashboard_utils__WEBPACK_IMPORTED_MODULE_15__["getProductIdsForCart"])(profileItems, false, installedPlugins);
  return {
    profileItems: profileItems,
    productIds: productIds
  };
}))(CartModal));

/***/ }),

/***/ "./client/dashboard/components/settings/general/store-address.js":
/*!***********************************************************************!*\
  !*** ./client/dashboard/components/settings/general/store-address.js ***!
  \***********************************************************************/
/*! exports provided: validateStoreAddress, getCountryStateOptions, useGetCountryStateAutofill, StoreAddress */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "validateStoreAddress", function() { return validateStoreAddress; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "getCountryStateOptions", function() { return getCountryStateOptions; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "useGetCountryStateAutofill", function() { return useGetCountryStateAutofill; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "StoreAddress", function() { return StoreAddress; });
/* harmony import */ var _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/extends */ "./node_modules/@babel/runtime/helpers/extends.js");
/* harmony import */ var _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _babel_runtime_helpers_slicedToArray__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/slicedToArray */ "./node_modules/@babel/runtime/helpers/slicedToArray.js");
/* harmony import */ var _babel_runtime_helpers_slicedToArray__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_slicedToArray__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _babel_runtime_helpers_toConsumableArray__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @babel/runtime/helpers/toConsumableArray */ "./node_modules/@babel/runtime/helpers/toConsumableArray.js");
/* harmony import */ var _babel_runtime_helpers_toConsumableArray__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_toConsumableArray__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _wordpress_html_entities__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @wordpress/html-entities */ "@wordpress/html-entities");
/* harmony import */ var _wordpress_html_entities__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_wordpress_html_entities__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! lodash */ "lodash");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var _woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! @woocommerce/wc-admin-settings */ "./client/settings/index.js");
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! @woocommerce/components */ "@woocommerce/components");
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_components__WEBPACK_IMPORTED_MODULE_9__);





/**
 * External dependencies
 */






/**
 * Internal dependencies
 */



var _getSetting = Object(_woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_8__["getSetting"])('dataEndpoints', {
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
        label: Object(_wordpress_html_entities__WEBPACK_IMPORTED_MODULE_5__["decodeEntities"])(country.name) + ' — ' + Object(_wordpress_html_entities__WEBPACK_IMPORTED_MODULE_5__["decodeEntities"])(state.name)
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

/***/ "./client/lib/in-app-purchase.js":
/*!***************************************!*\
  !*** ./client/lib/in-app-purchase.js ***!
  \***************************************/
/*! exports provided: getInAppPurchaseUrl */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "getInAppPurchaseUrl", function() { return getInAppPurchaseUrl; });
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/defineProperty */ "./node_modules/@babel/runtime/helpers/defineProperty.js");
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_url__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/url */ "@wordpress/url");
/* harmony import */ var _wordpress_url__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_url__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @woocommerce/wc-admin-settings */ "./client/settings/index.js");


function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0___default()(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

/**
 * External dependencies
 */

/**
 * WooCommerce dependencies
 */


/**
 * Returns an in-app-purchase URL.
 *
 * @param {string} url
 * @param {Object} queryArgs
 * @return {string} url with in-app-purchase query parameters
 */

var getInAppPurchaseUrl = function getInAppPurchaseUrl(url) {
  var queryArgs = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};
  var _window$location = window.location,
      pathname = _window$location.pathname,
      search = _window$location.search;
  var connectNonce = Object(_woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_2__["getSetting"])('connectNonce', '');
  queryArgs = _objectSpread({
    'wccom-site': Object(_woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_2__["getSetting"])('siteUrl'),
    // If the site is installed in a directory the directory must be included in the back param path.
    'wccom-back': pathname + search,
    'wccom-woo-version': Object(_woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_2__["getSetting"])('wcVersion'),
    'wccom-connect-nonce': connectNonce
  }, queryArgs);
  return Object(_wordpress_url__WEBPACK_IMPORTED_MODULE_1__["addQueryArgs"])(url, queryArgs);
};

/***/ }),

/***/ "./client/lib/sanitize-html/index.js":
/*!*******************************************!*\
  !*** ./client/lib/sanitize-html/index.js ***!
  \*******************************************/
/*! exports provided: ALLOWED_TAGS, ALLOWED_ATTR, default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "ALLOWED_TAGS", function() { return ALLOWED_TAGS; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "ALLOWED_ATTR", function() { return ALLOWED_ATTR; });
/* harmony import */ var dompurify__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! dompurify */ "./node_modules/dompurify/dist/purify.js");
/* harmony import */ var dompurify__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(dompurify__WEBPACK_IMPORTED_MODULE_0__);
/**
 * External dependencies
 */

var ALLOWED_TAGS = ['a', 'b', 'em', 'i', 'strong', 'p'];
var ALLOWED_ATTR = ['target', 'href', 'rel', 'name', 'download'];
/* harmony default export */ __webpack_exports__["default"] = (function (html) {
  return {
    __html: Object(dompurify__WEBPACK_IMPORTED_MODULE_0__["sanitize"])(html, {
      ALLOWED_TAGS: ALLOWED_TAGS,
      ALLOWED_ATTR: ALLOWED_ATTR
    })
  };
});

/***/ }),

/***/ "./client/task-list/index.js":
/*!***********************************!*\
  !*** ./client/task-list/index.js ***!
  \***********************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/defineProperty */ "./node_modules/@babel/runtime/helpers/defineProperty.js");
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/classCallCheck */ "./node_modules/@babel/runtime/helpers/classCallCheck.js");
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @babel/runtime/helpers/createClass */ "./node_modules/@babel/runtime/helpers/createClass.js");
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @babel/runtime/helpers/inherits */ "./node_modules/@babel/runtime/helpers/inherits.js");
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @babel/runtime/helpers/possibleConstructorReturn */ "./node_modules/@babel/runtime/helpers/possibleConstructorReturn.js");
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @babel/runtime/helpers/getPrototypeOf */ "./node_modules/@babel/runtime/helpers/getPrototypeOf.js");
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! lodash */ "lodash");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_8__);
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! @wordpress/compose */ "./node_modules/@wordpress/compose/build-module/index.js");
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! classnames */ "./node_modules/classnames/index.js");
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_10___default = /*#__PURE__*/__webpack_require__.n(classnames__WEBPACK_IMPORTED_MODULE_10__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! @wordpress/components */ "./node_modules/@wordpress/components/build-module/index.js");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_12___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_12__);
/* harmony import */ var _wordpress_icons__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! @wordpress/icons */ "./node_modules/@wordpress/icons/build-module/index.js");
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! @woocommerce/components */ "@woocommerce/components");
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_14___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_components__WEBPACK_IMPORTED_MODULE_14__);
/* harmony import */ var _woocommerce_navigation__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(/*! @woocommerce/navigation */ "@woocommerce/navigation");
/* harmony import */ var _woocommerce_navigation__WEBPACK_IMPORTED_MODULE_15___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_15__);
/* harmony import */ var _woocommerce_data__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__(/*! @woocommerce/data */ "@woocommerce/data");
/* harmony import */ var _woocommerce_data__WEBPACK_IMPORTED_MODULE_16___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_data__WEBPACK_IMPORTED_MODULE_16__);
/* harmony import */ var _style_scss__WEBPACK_IMPORTED_MODULE_17__ = __webpack_require__(/*! ./style.scss */ "./client/task-list/style.scss");
/* harmony import */ var _style_scss__WEBPACK_IMPORTED_MODULE_17___default = /*#__PURE__*/__webpack_require__.n(_style_scss__WEBPACK_IMPORTED_MODULE_17__);
/* harmony import */ var dashboard_components_cart_modal__WEBPACK_IMPORTED_MODULE_18__ = __webpack_require__(/*! dashboard/components/cart-modal */ "./client/dashboard/components/cart-modal.js");
/* harmony import */ var _tasks__WEBPACK_IMPORTED_MODULE_19__ = __webpack_require__(/*! ./tasks */ "./client/task-list/tasks.js");
/* harmony import */ var lib_tracks__WEBPACK_IMPORTED_MODULE_20__ = __webpack_require__(/*! lib/tracks */ "./client/lib/tracks.js");
/* harmony import */ var wc_api_with_select__WEBPACK_IMPORTED_MODULE_21__ = __webpack_require__(/*! wc-api/with-select */ "./client/wc-api/with-select.js");








function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0___default()(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_5___default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_5___default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_4___default()(this, result); }; }

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







var TaskDashboard = /*#__PURE__*/function (_Component) {
  _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_3___default()(TaskDashboard, _Component);

  var _super = _createSuper(TaskDashboard);

  function TaskDashboard(props) {
    var _this;

    _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_1___default()(this, TaskDashboard);

    _this = _super.call(this, props);
    _this.state = {
      isCartModalOpen: false,
      isWelcomeModalOpen: !props.modalDismissed
    };
    return _this;
  }

  _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_2___default()(TaskDashboard, [{
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
          woocommerce_task_list_complete: 'yes'
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
          woocommerce_task_list_complete: 'yes'
        });
      }

      if (!Object(lodash__WEBPACK_IMPORTED_MODULE_8__["isEqual"])(prevCompletedTaskKeys, completedTaskKeys)) {
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

      if (!Object(lodash__WEBPACK_IMPORTED_MODULE_8__["isEqual"])(trackedCompletedTasks, completedTaskKeys)) {
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
          taskListPayments = _this$props4.taskListPayments,
          installedPlugins = _this$props4.installedPlugins;
      return Object(_tasks__WEBPACK_IMPORTED_MODULE_19__["getAllTasks"])({
        profileItems: profileItems,
        taskListPayments: taskListPayments,
        query: query,
        toggleCartModal: this.toggleCartModal.bind(this),
        installedPlugins: installedPlugins
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

      Object(lib_tracks__WEBPACK_IMPORTED_MODULE_20__["recordEvent"])('task_view', _objectSpread({
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
      Object(lib_tracks__WEBPACK_IMPORTED_MODULE_20__["recordEvent"])('tasklist_view', {
        number_tasks: tasks.length,
        store_connected: profileItems.wccom_connected
      });
    }
  }, {
    key: "keepTaskCard",
    value: function keepTaskCard() {
      Object(lib_tracks__WEBPACK_IMPORTED_MODULE_20__["recordEvent"])('tasklist_completed', {
        action: 'keep_card'
      });
      this.props.updateOptions({
        woocommerce_task_list_prompt_shown: true
      });
    }
  }, {
    key: "hideTaskCard",
    value: function hideTaskCard(action) {
      Object(lib_tracks__WEBPACK_IMPORTED_MODULE_20__["recordEvent"])('tasklist_completed', {
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
    key: "renderMenu",
    value: function renderMenu() {
      var _this2 = this;

      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("div", {
        className: "woocommerce-card__menu woocommerce-card__header-item"
      }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_14__["EllipsisMenu"], {
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__["__"])('Task List Options', 'woocommerce'),
        renderContent: function renderContent() {
          return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("div", {
            className: "woocommerce-task-card__section-controls"
          }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_11__["Button"], {
            onClick: function onClick() {
              return _this2.hideTaskCard('remove_card');
            }
          }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__["__"])('Hide this', 'woocommerce')));
        }
      }));
    }
  }, {
    key: "toggleCartModal",
    value: function toggleCartModal() {
      var isCartModalOpen = this.state.isCartModalOpen;

      if (!isCartModalOpen) {
        Object(lib_tracks__WEBPACK_IMPORTED_MODULE_20__["recordEvent"])('tasklist_purchase_extensions');
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
      var _this3 = this;

      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_11__["Modal"], {
        title: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["Fragment"], null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("span", {
          role: "img",
          "aria-hidden": "true",
          focusable: "false",
          className: "woocommerce-task-dashboard__welcome-modal-icon"
        }, "\uD83D\uDE80"), Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__["__"])("Woo hoo - you're almost there!", 'woocommerce')),
        onRequestClose: function onRequestClose() {
          return _this3.closeWelcomeModal();
        },
        className: "woocommerce-task-dashboard__welcome-modal"
      }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("div", {
        className: "woocommerce-task-dashboard__welcome-modal-wrapper"
      }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("div", {
        className: "woocommerce-task-dashboard__welcome-modal-message"
      }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("p", null, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__["__"])('Based on the information you provided we’ve prepared some final set up tasks for you to perform.', 'woocommerce')), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("p", null, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__["__"])('Once complete your store will be ready for launch - exciting!', 'woocommerce'))), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_11__["Button"], {
        isPrimary: true,
        onClick: function onClick() {
          return _this3.closeWelcomeModal();
        }
      }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__["__"])('Continue', 'woocommerce'))));
    }
  }, {
    key: "render",
    value: function render() {
      var _this4 = this;

      var query = this.props.query;
      var _this$state = this.state,
          isCartModalOpen = _this$state.isCartModalOpen,
          isWelcomeModalOpen = _this$state.isWelcomeModalOpen;
      var currentTask = this.getCurrentTask();
      var listTasks = this.getTasks().map(function (task) {
        task.className = classnames__WEBPACK_IMPORTED_MODULE_10___default()(task.completed ? 'is-complete' : null, task.className);
        task.before = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("div", {
          className: "woocommerce-task__icon"
        }, task.completed && Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])(_wordpress_icons__WEBPACK_IMPORTED_MODULE_13__["Icon"], {
          icon: _wordpress_icons__WEBPACK_IMPORTED_MODULE_13__["check"]
        }));

        if (!task.completed) {
          task.after = task.time ? Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("span", {
            className: "woocommerce-task-estimated-time"
          }, task.time) : Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])(_wordpress_icons__WEBPACK_IMPORTED_MODULE_13__["Icon"], {
            icon: _wordpress_icons__WEBPACK_IMPORTED_MODULE_13__["chevronRight"]
          });
        }

        if (!task.onClick) {
          task.onClick = function () {
            return Object(_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_15__["updateQueryString"])({
              task: task.key
            });
          };
        }

        return task;
      });
      var numCompleteTasks = listTasks.filter(function (task) {
        return task.completed;
      }).length;
      var progressBarClass = classnames__WEBPACK_IMPORTED_MODULE_10___default()('woocommerce-task-card__progress-bar', {
        completed: listTasks.length === numCompleteTasks
      });
      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["Fragment"], null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("div", {
        className: "woocommerce-task-dashboard__container"
      }, currentTask ? Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["cloneElement"])(currentTask.container, {
        query: query
      }) : Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["Fragment"], null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_11__["Card"], {
        size: "large",
        className: "woocommerce-task-card"
      }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("progress", {
        className: progressBarClass,
        max: listTasks.length,
        value: numCompleteTasks
      }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_11__["CardHeader"], null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_14__["H"], null, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__["__"])('Finish setup', 'woocommerce')), this.renderMenu()), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_11__["CardBody"], null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_14__["List"], {
        items: listTasks
      }))), isWelcomeModalOpen && this.renderWelcomeModal())), isCartModalOpen && Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])(dashboard_components_cart_modal__WEBPACK_IMPORTED_MODULE_18__["default"], {
        onClose: function onClose() {
          return _this4.toggleCartModal();
        },
        onClickPurchaseLater: function onClickPurchaseLater() {
          return _this4.toggleCartModal();
        }
      }));
    }
  }]);

  return TaskDashboard;
}(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["Component"]);

/* harmony default export */ __webpack_exports__["default"] = (Object(_wordpress_compose__WEBPACK_IMPORTED_MODULE_9__["compose"])(Object(wc_api_with_select__WEBPACK_IMPORTED_MODULE_21__["default"])(function (select, props) {
  var _select = select(_woocommerce_data__WEBPACK_IMPORTED_MODULE_16__["ONBOARDING_STORE_NAME"]),
      getProfileItems = _select.getProfileItems;

  var _select2 = select(_woocommerce_data__WEBPACK_IMPORTED_MODULE_16__["OPTIONS_STORE_NAME"]),
      getOption = _select2.getOption;

  var _select3 = select(_woocommerce_data__WEBPACK_IMPORTED_MODULE_16__["PLUGINS_STORE_NAME"]),
      getActivePlugins = _select3.getActivePlugins,
      getInstalledPlugins = _select3.getInstalledPlugins,
      isJetpackConnected = _select3.isJetpackConnected;

  var profileItems = getProfileItems();
  var modalDismissed = getOption('woocommerce_task_list_welcome_modal_dismissed') || false;
  var taskListPayments = getOption('woocommerce_task_list_payments');
  var trackedCompletedTasks = getOption('woocommerce_task_list_tracked_completed_tasks') || [];
  var payments = getOption('woocommerce_task_list_payments');
  var installedPlugins = getInstalledPlugins();
  var tasks = Object(_tasks__WEBPACK_IMPORTED_MODULE_19__["getAllTasks"])({
    profileItems: profileItems,
    options: payments,
    query: props.query,
    installedPlugins: installedPlugins
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
  return {
    modalDismissed: modalDismissed,
    profileItems: profileItems,
    taskListPayments: taskListPayments,
    isJetpackConnected: isJetpackConnected(),
    incompleteTasks: incompleteTasks,
    trackedCompletedTasks: trackedCompletedTasks,
    completedTaskKeys: completedTaskKeys,
    activePlugins: activePlugins,
    installedPlugins: installedPlugins
  };
}), Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_12__["withDispatch"])(function (dispatch) {
  var _dispatch = dispatch(_woocommerce_data__WEBPACK_IMPORTED_MODULE_16__["OPTIONS_STORE_NAME"]),
      updateOptions = _dispatch.updateOptions;

  return {
    updateOptions: updateOptions
  };
}))(TaskDashboard));

/***/ }),

/***/ "./client/task-list/tasks.js":
/*!***********************************!*\
  !*** ./client/task-list/tasks.js ***!
  \***********************************/
/*! exports provided: getAllTasks */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "getAllTasks", function() { return getAllTasks; });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_hooks__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/hooks */ "@wordpress/hooks");
/* harmony import */ var _wordpress_hooks__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_hooks__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @woocommerce/wc-admin-settings */ "./client/settings/index.js");
/* harmony import */ var _woocommerce_navigation__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @woocommerce/navigation */ "@woocommerce/navigation");
/* harmony import */ var _woocommerce_navigation__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _tasks_appearance__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./tasks/appearance */ "./client/task-list/tasks/appearance.js");
/* harmony import */ var _tasks_connect__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./tasks/connect */ "./client/task-list/tasks/connect.js");
/* harmony import */ var dashboard_utils__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! dashboard/utils */ "./client/dashboard/utils.js");
/* harmony import */ var _tasks_products__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ./tasks/products */ "./client/task-list/tasks/products.js");
/* harmony import */ var _tasks_shipping__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ./tasks/shipping */ "./client/task-list/tasks/shipping/index.js");
/* harmony import */ var _tasks_tax__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! ./tasks/tax */ "./client/task-list/tasks/tax.js");
/* harmony import */ var _tasks_payments__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! ./tasks/payments */ "./client/task-list/tasks/payments/index.js");


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
      taskListPayments = _ref.taskListPayments,
      query = _ref.query,
      toggleCartModal = _ref.toggleCartModal,
      installedPlugins = _ref.installedPlugins;

  var _getSetting = Object(_woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_3__["getSetting"])('onboarding', {
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

  var productIds = Object(dashboard_utils__WEBPACK_IMPORTED_MODULE_7__["getProductIdsForCart"])(profileItems, true, installedPlugins);
  var remainingProductIds = Object(dashboard_utils__WEBPACK_IMPORTED_MODULE_7__["getProductIdsForCart"])(profileItems, false, installedPlugins);
  var paymentsCompleted = Boolean(taskListPayments && taskListPayments.completed);
  var paymentsSkipped = Boolean(taskListPayments && taskListPayments.skipped);
  var tasks = [{
    key: 'store_details',
    title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Store details', 'woocommerce'),
    container: null,
    onClick: function onClick() {
      window.location = Object(_woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_3__["getAdminLink"])('admin.php?page=wc-admin&reset_profiler=1');
    },
    completed: profileItems.completed,
    visible: true,
    time: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('4 minutes', 'woocommerce')
  }, {
    key: 'purchase',
    title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Purchase & install extensions', 'woocommerce'),
    container: null,
    onClick: function onClick() {
      return remainingProductIds.length ? toggleCartModal() : null;
    },
    visible: productIds.length,
    completed: productIds.length && !remainingProductIds.length,
    time: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('2 minutes', 'woocommerce')
  }, {
    key: 'connect',
    title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Connect your store to WooCommerce.com', 'woocommerce'),
    container: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_tasks_connect__WEBPACK_IMPORTED_MODULE_6__["default"], {
      query: query
    }),
    visible: profileItems.items_purchased && !profileItems.wccom_connected,
    completed: profileItems.wccom_connected,
    time: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('1 minute', 'woocommerce')
  }, {
    key: 'products',
    title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Add my products', 'woocommerce'),
    container: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_tasks_products__WEBPACK_IMPORTED_MODULE_8__["default"], null),
    completed: hasProducts,
    visible: true,
    time: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('1 minute per product', 'woocommerce')
  }, {
    key: 'appearance',
    title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Personalize my store', 'woocommerce'),
    container: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_tasks_appearance__WEBPACK_IMPORTED_MODULE_5__["default"], null),
    completed: isAppearanceComplete,
    visible: true,
    time: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('2 minutes', 'woocommerce')
  }, {
    key: 'shipping',
    title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Set up shipping', 'woocommerce'),
    container: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_tasks_shipping__WEBPACK_IMPORTED_MODULE_9__["default"], null),
    completed: shippingZonesCount > 0,
    visible: profileItems.product_types && profileItems.product_types.includes('physical') || hasPhysicalProducts,
    time: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('1 minute', 'woocommerce')
  }, {
    key: 'tax',
    title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Set up tax', 'woocommerce'),
    container: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_tasks_tax__WEBPACK_IMPORTED_MODULE_10__["default"], null),
    completed: isTaxComplete,
    visible: true,
    time: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('1 minute', 'woocommerce')
  }, {
    key: 'payments',
    title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Set up payments', 'woocommerce'),
    container: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_tasks_payments__WEBPACK_IMPORTED_MODULE_11__["default"], null),
    completed: paymentsCompleted || paymentsSkipped,
    onClick: function onClick() {
      if (paymentsCompleted || paymentsSkipped) {
        window.location = Object(_woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_3__["getAdminLink"])('admin.php?page=wc-settings&tab=checkout');
        return;
      }

      Object(_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_4__["updateQueryString"])({
        task: 'payments'
      });
    },
    visible: true,
    time: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('2 minutes', 'woocommerce')
  }];
  return Object(_wordpress_hooks__WEBPACK_IMPORTED_MODULE_2__["applyFilters"])('woocommerce_admin_onboarding_task_list', tasks, query);
}

/***/ }),

/***/ "./client/task-list/tasks/appearance.js":
/*!**********************************************!*\
  !*** ./client/task-list/tasks/appearance.js ***!
  \**********************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _babel_runtime_helpers_asyncToGenerator__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/asyncToGenerator */ "./node_modules/@babel/runtime/helpers/asyncToGenerator.js");
/* harmony import */ var _babel_runtime_helpers_asyncToGenerator__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_asyncToGenerator__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/defineProperty */ "./node_modules/@babel/runtime/helpers/defineProperty.js");
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @babel/runtime/helpers/classCallCheck */ "./node_modules/@babel/runtime/helpers/classCallCheck.js");
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @babel/runtime/helpers/createClass */ "./node_modules/@babel/runtime/helpers/createClass.js");
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @babel/runtime/helpers/assertThisInitialized */ "./node_modules/@babel/runtime/helpers/assertThisInitialized.js");
/* harmony import */ var _babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @babel/runtime/helpers/inherits */ "./node_modules/@babel/runtime/helpers/inherits.js");
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @babel/runtime/helpers/possibleConstructorReturn */ "./node_modules/@babel/runtime/helpers/possibleConstructorReturn.js");
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @babel/runtime/helpers/getPrototypeOf */ "./node_modules/@babel/runtime/helpers/getPrototypeOf.js");
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_8__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_9__);
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! @wordpress/api-fetch */ "@wordpress/api-fetch");
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_10___default = /*#__PURE__*/__webpack_require__.n(_wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_10__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! @wordpress/components */ "./node_modules/@wordpress/components/build-module/index.js");
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! @wordpress/compose */ "./node_modules/@wordpress/compose/build-module/index.js");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! lodash */ "lodash");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_13___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_13__);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_14___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_14__);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(/*! @woocommerce/components */ "@woocommerce/components");
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_15___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_components__WEBPACK_IMPORTED_MODULE_15__);
/* harmony import */ var _woocommerce_navigation__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__(/*! @woocommerce/navigation */ "@woocommerce/navigation");
/* harmony import */ var _woocommerce_navigation__WEBPACK_IMPORTED_MODULE_16___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_16__);
/* harmony import */ var _woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_17__ = __webpack_require__(/*! @woocommerce/wc-admin-settings */ "./client/settings/index.js");
/* harmony import */ var _woocommerce_data__WEBPACK_IMPORTED_MODULE_18__ = __webpack_require__(/*! @woocommerce/data */ "@woocommerce/data");
/* harmony import */ var _woocommerce_data__WEBPACK_IMPORTED_MODULE_18___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_data__WEBPACK_IMPORTED_MODULE_18__);
/* harmony import */ var lib_tracks__WEBPACK_IMPORTED_MODULE_19__ = __webpack_require__(/*! lib/tracks */ "./client/lib/tracks.js");
/* harmony import */ var wc_api_constants__WEBPACK_IMPORTED_MODULE_20__ = __webpack_require__(/*! wc-api/constants */ "./client/wc-api/constants.js");










function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_1___default()(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_7___default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_7___default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_6___default()(this, result); }; }

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




var Appearance = /*#__PURE__*/function (_Component) {
  _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_5___default()(Appearance, _Component);

  var _super = _createSuper(Appearance);

  function Appearance(props) {
    var _this;

    _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_2___default()(this, Appearance);

    _this = _super.call(this, props);

    var _getSetting = Object(_woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_17__["getSetting"])('onboarding', {}),
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
      isUpdatingLogo: false,
      isUpdatingNotice: false,
      storeNoticeText: props.demoStoreNotice || ''
    };
    _this.completeStep = _this.completeStep.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_4___default()(_this));
    _this.createHomepage = _this.createHomepage.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_4___default()(_this));
    _this.importProducts = _this.importProducts.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_4___default()(_this));
    _this.updateLogo = _this.updateLogo.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_4___default()(_this));
    _this.updateNotice = _this.updateNotice.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_4___default()(_this));
    return _this;
  }

  _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_3___default()(Appearance, [{
    key: "componentDidMount",
    value: function componentDidMount() {
      var _getSetting2 = Object(_woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_17__["getSetting"])('onboarding', {}),
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
    value: function componentDidUpdate(prevProps) {
      var _this2 = this;

      var _this$state = this.state,
          isPending = _this$state.isPending,
          logo = _this$state.logo;
      var demoStoreNotice = this.props.demoStoreNotice;

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

      if (demoStoreNotice && prevProps.demoStoreNotice !== demoStoreNotice) {
        /* eslint-disable react/no-did-update-set-state */
        this.setState({
          storeNoticeText: demoStoreNotice
        });
        /* eslint-enable react/no-did-update-set-state */
      }
    }
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
        Object(_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_16__["getHistory"])().push(Object(_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_16__["getNewPath"])({}, '/', {}));
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
      Object(lib_tracks__WEBPACK_IMPORTED_MODULE_19__["recordEvent"])('tasklist_appearance_import_demo', {});
      _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_10___default()({
        path: "".concat(wc_api_constants__WEBPACK_IMPORTED_MODULE_20__["WC_ADMIN_NAMESPACE"], "/onboarding/tasks/import_sample_products"),
        method: 'POST'
      }).then(function (result) {
        if (result.failed && result.failed.length) {
          createNotice('error', Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_9__["__"])('There was an error importing some of the sample products.', 'woocommerce'));
        } else {
          createNotice('success', Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_9__["__"])('All sample products have been imported.', 'woocommerce'));
          Object(_woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_17__["setSetting"])('onboarding', _objectSpread(_objectSpread({}, Object(_woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_17__["getSetting"])('onboarding', {})), {}, {
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
      Object(lib_tracks__WEBPACK_IMPORTED_MODULE_19__["recordEvent"])('tasklist_appearance_create_homepage', {
        create_homepage: true
      });
      _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_10___default()({
        path: '/wc-admin/onboarding/tasks/create_homepage',
        method: 'POST'
      }).then(function (response) {
        createNotice(response.status, response.message, {
          actions: response.edit_post_link ? [{
            label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_9__["__"])('Customize', 'woocommerce'),
            onClick: function onClick() {
              Object(lib_tracks__WEBPACK_IMPORTED_MODULE_19__["queueRecordEvent"])('tasklist_appearance_customize_homepage', {});
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
    value: function () {
      var _updateLogo = _babel_runtime_helpers_asyncToGenerator__WEBPACK_IMPORTED_MODULE_0___default()( /*#__PURE__*/regeneratorRuntime.mark(function _callee() {
        var _this$props, updateOptions, createNotice, logo, _getSetting3, stylesheet, themeMods, updatedThemeMods, update;

        return regeneratorRuntime.wrap(function _callee$(_context) {
          while (1) {
            switch (_context.prev = _context.next) {
              case 0:
                _this$props = this.props, updateOptions = _this$props.updateOptions, createNotice = _this$props.createNotice;
                logo = this.state.logo;
                _getSetting3 = Object(_woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_17__["getSetting"])('onboarding', {}), stylesheet = _getSetting3.stylesheet, themeMods = _getSetting3.themeMods;
                updatedThemeMods = _objectSpread(_objectSpread({}, themeMods), {}, {
                  custom_logo: logo ? logo.id : null
                });
                Object(lib_tracks__WEBPACK_IMPORTED_MODULE_19__["recordEvent"])('tasklist_appearance_upload_logo');
                Object(_woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_17__["setSetting"])('onboarding', _objectSpread(_objectSpread({}, Object(_woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_17__["getSetting"])('onboarding', {})), {}, {
                  themeMods: updatedThemeMods
                }));
                this.setState({
                  isUpdatingLogo: true
                });
                _context.next = 9;
                return updateOptions(_babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_1___default()({}, "theme_mods_".concat(stylesheet), updatedThemeMods));

              case 9:
                update = _context.sent;

                if (update.success) {
                  this.setState({
                    isUpdatingLogo: false
                  });
                  createNotice('success', Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_9__["__"])('Store logo updated sucessfully.', 'woocommerce'));
                  this.completeStep();
                } else {
                  createNotice('error', update.message);
                }

              case 11:
              case "end":
                return _context.stop();
            }
          }
        }, _callee, this);
      }));

      function updateLogo() {
        return _updateLogo.apply(this, arguments);
      }

      return updateLogo;
    }()
  }, {
    key: "updateNotice",
    value: function () {
      var _updateNotice = _babel_runtime_helpers_asyncToGenerator__WEBPACK_IMPORTED_MODULE_0___default()( /*#__PURE__*/regeneratorRuntime.mark(function _callee2() {
        var _this$props2, updateOptions, createNotice, storeNoticeText, update;

        return regeneratorRuntime.wrap(function _callee2$(_context2) {
          while (1) {
            switch (_context2.prev = _context2.next) {
              case 0:
                _this$props2 = this.props, updateOptions = _this$props2.updateOptions, createNotice = _this$props2.createNotice;
                storeNoticeText = this.state.storeNoticeText;
                Object(lib_tracks__WEBPACK_IMPORTED_MODULE_19__["recordEvent"])('tasklist_appearance_set_store_notice', {
                  added_text: Boolean(storeNoticeText.length)
                });
                Object(_woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_17__["setSetting"])('onboarding', _objectSpread(_objectSpread({}, Object(_woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_17__["getSetting"])('onboarding', {})), {}, {
                  isAppearanceComplete: true
                }));
                this.setState({
                  isUpdatingNotice: true
                });
                _context2.next = 7;
                return updateOptions({
                  woocommerce_task_list_appearance_complete: true,
                  woocommerce_demo_store: storeNoticeText.length ? 'yes' : 'no',
                  woocommerce_demo_store_notice: storeNoticeText
                });

              case 7:
                update = _context2.sent;

                if (update.success) {
                  this.setState({
                    isUpdatingNotice: false
                  });
                  createNotice('success', Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_9__["__"])("🎨 Your store is looking great! Don't forget to continue personalizing it.", 'woocommerce'));
                  this.completeStep();
                } else {
                  createNotice('error', update.message);
                }

              case 9:
              case "end":
                return _context2.stop();
            }
          }
        }, _callee2, this);
      }));

      function updateNotice() {
        return _updateNotice.apply(this, arguments);
      }

      return updateNotice;
    }()
  }, {
    key: "getSteps",
    value: function getSteps() {
      var _this5 = this;

      var _this$state2 = this.state,
          isDirty = _this$state2.isDirty,
          isPending = _this$state2.isPending,
          logo = _this$state2.logo,
          storeNoticeText = _this$state2.storeNoticeText,
          isUpdatingLogo = _this$state2.isUpdatingLogo;
      var steps = [{
        key: 'import',
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_9__["__"])('Import sample products', 'woocommerce'),
        description: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_9__["__"])('We’ll add some products that will make it easier to see what your store looks like', 'woocommerce'),
        content: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_8__["createElement"])(_wordpress_element__WEBPACK_IMPORTED_MODULE_8__["Fragment"], null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_8__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_11__["Button"], {
          onClick: this.importProducts,
          isBusy: isPending,
          isPrimary: true
        }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_9__["__"])('Import products', 'woocommerce')), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_8__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_11__["Button"], {
          onClick: function onClick() {
            return _this5.completeStep();
          }
        }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_9__["__"])('Skip', 'woocommerce'))),
        visible: this.stepVisibility.import
      }, {
        key: 'homepage',
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_9__["__"])('Create a custom homepage', 'woocommerce'),
        description: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_9__["__"])('Create a new homepage and customize it to suit your needs', 'woocommerce'),
        content: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_8__["createElement"])(_wordpress_element__WEBPACK_IMPORTED_MODULE_8__["Fragment"], null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_8__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_11__["Button"], {
          isPrimary: true,
          isBusy: isPending,
          onClick: this.createHomepage
        }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_9__["__"])('Create homepage', 'woocommerce')), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_8__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_11__["Button"], {
          onClick: function onClick() {
            Object(lib_tracks__WEBPACK_IMPORTED_MODULE_19__["recordEvent"])('tasklist_appearance_create_homepage', {
              create_homepage: false
            });

            _this5.completeStep();
          }
        }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_9__["__"])('Skip', 'woocommerce'))),
        visible: this.stepVisibility.homepage
      }, {
        key: 'logo',
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_9__["__"])('Upload a logo', 'woocommerce'),
        description: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_9__["__"])('Ensure your store is on-brand by adding your logo', 'woocommerce'),
        content: isPending ? null : Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_8__["createElement"])(_wordpress_element__WEBPACK_IMPORTED_MODULE_8__["Fragment"], null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_8__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_15__["ImageUpload"], {
          image: logo,
          onChange: function onChange(image) {
            return _this5.setState({
              isDirty: true,
              logo: image
            });
          }
        }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_8__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_11__["Button"], {
          disabled: !logo && !isDirty,
          onClick: this.updateLogo,
          isBusy: isUpdatingLogo,
          isPrimary: true
        }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_9__["__"])('Proceed', 'woocommerce')), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_8__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_11__["Button"], {
          isTertiary: true,
          onClick: function onClick() {
            return _this5.completeStep();
          }
        }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_9__["__"])('Skip', 'woocommerce'))),
        visible: true
      }, {
        key: 'notice',
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_9__["__"])('Set a store notice', 'woocommerce'),
        description: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_9__["__"])('Optionally display a prominent notice across all pages of your store', 'woocommerce'),
        content: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_8__["createElement"])(_wordpress_element__WEBPACK_IMPORTED_MODULE_8__["Fragment"], null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_8__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_15__["TextControl"], {
          label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_9__["__"])('Store notice text', 'woocommerce'),
          placeholder: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_9__["__"])('Store notice text', 'woocommerce'),
          value: storeNoticeText,
          onChange: function onChange(value) {
            return _this5.setState({
              storeNoticeText: value
            });
          }
        }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_8__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_11__["Button"], {
          onClick: this.updateNotice,
          isPrimary: true
        }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_9__["__"])('Complete task', 'woocommerce'))),
        visible: true
      }];
      return Object(lodash__WEBPACK_IMPORTED_MODULE_13__["filter"])(steps, function (step) {
        return step.visible;
      });
    }
  }, {
    key: "render",
    value: function render() {
      var _this$state3 = this.state,
          isPending = _this$state3.isPending,
          stepIndex = _this$state3.stepIndex,
          isUpdatingLogo = _this$state3.isUpdatingLogo,
          isUpdatingNotice = _this$state3.isUpdatingNotice;
      var currentStep = this.getSteps()[stepIndex].key;
      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_8__["createElement"])("div", {
        className: "woocommerce-task-appearance"
      }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_8__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_15__["Card"], {
        className: "is-narrow"
      }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_8__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_15__["Stepper"], {
        isPending: isUpdatingNotice || isUpdatingLogo || isPending,
        isVertical: true,
        currentStep: currentStep,
        steps: this.getSteps()
      })));
    }
  }]);

  return Appearance;
}(_wordpress_element__WEBPACK_IMPORTED_MODULE_8__["Component"]);

/* harmony default export */ __webpack_exports__["default"] = (Object(_wordpress_compose__WEBPACK_IMPORTED_MODULE_12__["compose"])(Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_14__["withSelect"])(function (select) {
  var _select = select(_woocommerce_data__WEBPACK_IMPORTED_MODULE_18__["OPTIONS_STORE_NAME"]),
      getOption = _select.getOption;

  return {
    demoStoreNotice: getOption('woocommerce_demo_store_notice')
  };
}), Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_14__["withDispatch"])(function (dispatch) {
  var _dispatch = dispatch('core/notices'),
      createNotice = _dispatch.createNotice;

  var _dispatch2 = dispatch(_woocommerce_data__WEBPACK_IMPORTED_MODULE_18__["OPTIONS_STORE_NAME"]),
      updateOptions = _dispatch2.updateOptions;

  return {
    createNotice: createNotice,
    updateOptions: updateOptions
  };
}))(Appearance));

/***/ }),

/***/ "./client/task-list/tasks/connect.js":
/*!*******************************************!*\
  !*** ./client/task-list/tasks/connect.js ***!
  \*******************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _babel_runtime_helpers_asyncToGenerator__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/asyncToGenerator */ "./node_modules/@babel/runtime/helpers/asyncToGenerator.js");
/* harmony import */ var _babel_runtime_helpers_asyncToGenerator__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_asyncToGenerator__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/defineProperty */ "./node_modules/@babel/runtime/helpers/defineProperty.js");
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @babel/runtime/helpers/classCallCheck */ "./node_modules/@babel/runtime/helpers/classCallCheck.js");
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @babel/runtime/helpers/createClass */ "./node_modules/@babel/runtime/helpers/createClass.js");
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @babel/runtime/helpers/inherits */ "./node_modules/@babel/runtime/helpers/inherits.js");
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @babel/runtime/helpers/possibleConstructorReturn */ "./node_modules/@babel/runtime/helpers/possibleConstructorReturn.js");
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @babel/runtime/helpers/getPrototypeOf */ "./node_modules/@babel/runtime/helpers/getPrototypeOf.js");
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_8__);
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! @wordpress/compose */ "./node_modules/@wordpress/compose/build-module/index.js");
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! @wordpress/api-fetch */ "@wordpress/api-fetch");
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_10___default = /*#__PURE__*/__webpack_require__.n(_wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_10__);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_11___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_11__);
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! lodash */ "lodash");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_12___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_12__);
/* harmony import */ var _woocommerce_navigation__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! @woocommerce/navigation */ "@woocommerce/navigation");
/* harmony import */ var _woocommerce_navigation__WEBPACK_IMPORTED_MODULE_13___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_13__);
/* harmony import */ var _woocommerce_data__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! @woocommerce/data */ "@woocommerce/data");
/* harmony import */ var _woocommerce_data__WEBPACK_IMPORTED_MODULE_14___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_data__WEBPACK_IMPORTED_MODULE_14__);
/* harmony import */ var wc_api_constants__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(/*! wc-api/constants */ "./client/wc-api/constants.js");
/* harmony import */ var wc_api_with_select__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__(/*! wc-api/with-select */ "./client/wc-api/with-select.js");








function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_1___default()(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_6___default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_6___default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_5___default()(this, result); }; }

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




var Connect = /*#__PURE__*/function (_Component) {
  _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_4___default()(Connect, _Component);

  var _super = _createSuper(Connect);

  function Connect() {
    _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_2___default()(this, Connect);

    return _super.apply(this, arguments);
  }

  _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_3___default()(Connect, [{
    key: "componentDidMount",
    value: function componentDidMount() {
      document.body.classList.add('woocommerce-admin-is-loading');
      var query = this.props.query;

      if (query.deny === '1') {
        this.errorMessage(Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__["__"])('You must click approve to install your extensions and connect to WooCommerce.com.', 'woocommerce'));
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
      var baseQuery = Object(lodash__WEBPACK_IMPORTED_MODULE_12__["omit"])(_objectSpread(_objectSpread({}, query), {}, {
        page: 'wc-admin'
      }), ['task', 'wccom-connected', 'request_token', 'deny']);
      return Object(_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_13__["getNewPath"])({}, '/', baseQuery);
    }
  }, {
    key: "errorMessage",
    value: function errorMessage() {
      var message = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__["__"])('There was an error connecting to WooCommerce.com. Please try again.', 'woocommerce');
      document.body.classList.remove('woocommerce-admin-is-loading');
      Object(_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_13__["getHistory"])().push(this.baseQuery());
      this.props.createNotice('error', message);
    }
  }, {
    key: "request",
    value: function () {
      var _request = _babel_runtime_helpers_asyncToGenerator__WEBPACK_IMPORTED_MODULE_0___default()( /*#__PURE__*/regeneratorRuntime.mark(function _callee() {
        var connectResponse;
        return regeneratorRuntime.wrap(function _callee$(_context) {
          while (1) {
            switch (_context.prev = _context.next) {
              case 0:
                _context.prev = 0;
                _context.next = 3;
                return _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_10___default()({
                  path: "".concat(wc_api_constants__WEBPACK_IMPORTED_MODULE_15__["WC_ADMIN_NAMESPACE"], "/plugins/request-wccom-connect"),
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
      var _finish = _babel_runtime_helpers_asyncToGenerator__WEBPACK_IMPORTED_MODULE_0___default()( /*#__PURE__*/regeneratorRuntime.mark(function _callee2() {
        var query, connectResponse;
        return regeneratorRuntime.wrap(function _callee2$(_context2) {
          while (1) {
            switch (_context2.prev = _context2.next) {
              case 0:
                query = this.props.query;
                _context2.prev = 1;
                _context2.next = 4;
                return _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_10___default()({
                  path: "".concat(wc_api_constants__WEBPACK_IMPORTED_MODULE_15__["WC_ADMIN_NAMESPACE"], "/plugins/finish-wccom-connect"),
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
                  this.props.createNotice('success', Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__["__"])('Store connected to WooCommerce.com and extensions are being installed.', 'woocommerce')); // @todo Show a notice for when extensions are correctly installed.

                  document.body.classList.remove('woocommerce-admin-is-loading');
                  Object(_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_13__["getHistory"])().push(this.baseQuery());
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
}(_wordpress_element__WEBPACK_IMPORTED_MODULE_8__["Component"]);

/* harmony default export */ __webpack_exports__["default"] = (Object(_wordpress_compose__WEBPACK_IMPORTED_MODULE_9__["compose"])(Object(wc_api_with_select__WEBPACK_IMPORTED_MODULE_16__["default"])(function (select) {
  var _select = select(_woocommerce_data__WEBPACK_IMPORTED_MODULE_14__["ONBOARDING_STORE_NAME"]),
      getOnboardingError = _select.getOnboardingError;

  var isProfileItemsError = Boolean(getOnboardingError('updateProfileItems'));
  return {
    isProfileItemsError: isProfileItemsError
  };
}), Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_11__["withDispatch"])(function (dispatch) {
  var _dispatch = dispatch('core/notices'),
      createNotice = _dispatch.createNotice;

  var _dispatch2 = dispatch(_woocommerce_data__WEBPACK_IMPORTED_MODULE_14__["ONBOARDING_STORE_NAME"]),
      updateProfileItems = _dispatch2.updateProfileItems;

  return {
    createNotice: createNotice,
    updateProfileItems: updateProfileItems
  };
}))(Connect));

/***/ }),

/***/ "./client/task-list/tasks/payments/bacs.js":
/*!*************************************************!*\
  !*** ./client/task-list/tasks/payments/bacs.js ***!
  \*************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/extends */ "./node_modules/@babel/runtime/helpers/extends.js");
/* harmony import */ var _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _babel_runtime_helpers_asyncToGenerator__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/asyncToGenerator */ "./node_modules/@babel/runtime/helpers/asyncToGenerator.js");
/* harmony import */ var _babel_runtime_helpers_asyncToGenerator__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_asyncToGenerator__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @babel/runtime/helpers/classCallCheck */ "./node_modules/@babel/runtime/helpers/classCallCheck.js");
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @babel/runtime/helpers/createClass */ "./node_modules/@babel/runtime/helpers/createClass.js");
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @babel/runtime/helpers/inherits */ "./node_modules/@babel/runtime/helpers/inherits.js");
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @babel/runtime/helpers/possibleConstructorReturn */ "./node_modules/@babel/runtime/helpers/possibleConstructorReturn.js");
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @babel/runtime/helpers/getPrototypeOf */ "./node_modules/@babel/runtime/helpers/getPrototypeOf.js");
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_8__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! @wordpress/components */ "./node_modules/@wordpress/components/build-module/index.js");
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! @wordpress/compose */ "./node_modules/@wordpress/compose/build-module/index.js");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_11___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_11__);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! @woocommerce/components */ "@woocommerce/components");
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_12___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_components__WEBPACK_IMPORTED_MODULE_12__);
/* harmony import */ var _woocommerce_data__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! @woocommerce/data */ "@woocommerce/data");
/* harmony import */ var _woocommerce_data__WEBPACK_IMPORTED_MODULE_13___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_data__WEBPACK_IMPORTED_MODULE_13__);









function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_6___default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_6___default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_5___default()(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/**
 * External dependencies
 */





/**
 * WooCommerce dependencies
 */




var PayFast = /*#__PURE__*/function (_Component) {
  _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_4___default()(PayFast, _Component);

  var _super = _createSuper(PayFast);

  function PayFast() {
    var _temp, _this;

    _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_2___default()(this, PayFast);

    for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
      args[_key] = arguments[_key];
    }

    return _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_5___default()(_this, (_temp = _this = _super.call.apply(_super, [this].concat(args)), _this.getInitialConfigValues = function () {
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
        errors.account_number = errors.iban = Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_8__["__"])('Please enter an account number or IBAN', 'woocommerce');
      }

      return errors;
    }, _this.updateSettings = /*#__PURE__*/function () {
      var _ref = _babel_runtime_helpers_asyncToGenerator__WEBPACK_IMPORTED_MODULE_1___default()( /*#__PURE__*/regeneratorRuntime.mark(function _callee(values) {
        var _this$props, updateOptions, createNotice, markConfigured, update;

        return regeneratorRuntime.wrap(function _callee$(_context) {
          while (1) {
            switch (_context.prev = _context.next) {
              case 0:
                _this$props = _this.props, updateOptions = _this$props.updateOptions, createNotice = _this$props.createNotice, markConfigured = _this$props.markConfigured;
                _context.next = 3;
                return updateOptions({
                  woocommerce_bacs_settings: {
                    enabled: 'yes'
                  },
                  woocommerce_bacs_accounts: [values]
                });

              case 3:
                update = _context.sent;

                if (update.success) {
                  markConfigured('bacs');
                  createNotice('success', Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_8__["__"])('Direct bank transfer details added successfully', 'woocommerce'));
                } else {
                  createNotice('error', Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_8__["__"])('There was a problem saving your payment setings', 'woocommerce'));
                }

              case 5:
              case "end":
                return _context.stop();
            }
          }
        }, _callee);
      }));

      return function (_x) {
        return _ref.apply(this, arguments);
      };
    }(), _temp));
  }

  _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_3___default()(PayFast, [{
    key: "render",
    value: function render() {
      var isOptionsRequesting = this.props.isOptionsRequesting;
      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_12__["Form"], {
        initialValues: this.getInitialConfigValues(),
        onSubmitCallback: this.updateSettings,
        validate: this.validate
      }, function (_ref2) {
        var getInputProps = _ref2.getInputProps,
            handleSubmit = _ref2.handleSubmit;
        return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["Fragment"], null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_12__["H"], null, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_8__["__"])('Add your bank details', 'woocommerce')), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])("p", null, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_8__["__"])('These details are required to receive payments via bank transfer', 'woocommerce')), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])("div", {
          className: "woocommerce-task-payment-method__fields"
        }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_12__["TextControl"], _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0___default()({
          label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_8__["__"])('Account name', 'woocommerce'),
          required: true
        }, getInputProps('account_name'))), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_12__["TextControl"], _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0___default()({
          label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_8__["__"])('Account number', 'woocommerce'),
          required: true
        }, getInputProps('account_number'))), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_12__["TextControl"], _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0___default()({
          label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_8__["__"])('Bank name', 'woocommerce'),
          required: true
        }, getInputProps('bank_name'))), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_12__["TextControl"], _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0___default()({
          label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_8__["__"])('Sort code', 'woocommerce'),
          required: true
        }, getInputProps('sort_code'))), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_12__["TextControl"], _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0___default()({
          label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_8__["__"])('IBAN', 'woocommerce'),
          required: true
        }, getInputProps('iban'))), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_12__["TextControl"], _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0___default()({
          label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_8__["__"])('BIC / Swift', 'woocommerce'),
          required: true
        }, getInputProps('bic')))), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_9__["Button"], {
          isPrimary: true,
          isBusy: isOptionsRequesting,
          onClick: handleSubmit
        }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_8__["__"])('Save', 'woocommerce')));
      });
    }
  }]);

  return PayFast;
}(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["Component"]);

/* harmony default export */ __webpack_exports__["default"] = (Object(_wordpress_compose__WEBPACK_IMPORTED_MODULE_10__["compose"])(Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_11__["withSelect"])(function (select) {
  var _select = select(_woocommerce_data__WEBPACK_IMPORTED_MODULE_13__["OPTIONS_STORE_NAME"]),
      isOptionsUpdating = _select.isOptionsUpdating;

  var isOptionsRequesting = isOptionsUpdating();
  return {
    isOptionsRequesting: isOptionsRequesting
  };
}), Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_11__["withDispatch"])(function (dispatch) {
  var _dispatch = dispatch('core/notices'),
      createNotice = _dispatch.createNotice;

  var _dispatch2 = dispatch(_woocommerce_data__WEBPACK_IMPORTED_MODULE_13__["OPTIONS_STORE_NAME"]),
      updateOptions = _dispatch2.updateOptions;

  return {
    createNotice: createNotice,
    updateOptions: updateOptions
  };
}))(PayFast));

/***/ }),

/***/ "./client/task-list/tasks/payments/images/bacs.js":
/*!********************************************************!*\
  !*** ./client/task-list/tasks/payments/images/bacs.js ***!
  \********************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);

/* harmony default export */ __webpack_exports__["default"] = (function () {
  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("svg", {
    width: "96",
    height: "32",
    viewBox: "0 0 96 32",
    fill: "none",
    xmlns: "http://www.w3.org/2000/svg"
  }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("rect", {
    width: "32",
    height: "32",
    rx: "16",
    fill: "#8E9196"
  }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("mask", {
    id: "bacs0",
    "mask-type": "alpha",
    maskUnits: "userSpaceOnUse",
    x: "8",
    y: "8",
    width: "16",
    height: "16"
  }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("path", {
    fillRule: "evenodd",
    clipRule: "evenodd",
    d: "M8.875 12.25L16 8.5L23.125 12.25V13.75H8.875V12.25ZM16 10.195L19.9075 12.25H12.0925L16 10.195ZM10.75 15.25H12.25V20.5H10.75V15.25ZM15.25 20.5V15.25H16.75V20.5H15.25ZM23.125 23.5V22H8.875V23.5H23.125ZM19.75 15.25H21.25V20.5H19.75V15.25Z",
    fill: "white"
  })), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("g", {
    mask: "url(#bacs0)"
  }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("rect", {
    x: "7",
    y: "7",
    width: "18",
    height: "18",
    fill: "white"
  })), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("mask", {
    id: "bacs1",
    "mask-type": "alpha",
    maskUnits: "userSpaceOnUse",
    x: "39",
    y: "10",
    width: "18",
    height: "12"
  }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("path", {
    d: "M39 17L53.17 17L49.59 20.59L51 22L57 16L51 10L49.59 11.41L53.17 15L39 15L39 17Z",
    fill: "white"
  })), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("g", {
    mask: "url(#bacs1)"
  }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("rect", {
    x: "60",
    y: "28",
    width: "24",
    height: "24",
    transform: "rotate(-180 60 28)",
    fill: "#8E9196"
  })), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("rect", {
    x: "64",
    width: "32",
    height: "32",
    rx: "16",
    fill: "#8E9196"
  }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("mask", {
    id: "bacs2",
    "mask-type": "alpha",
    maskUnits: "userSpaceOnUse",
    x: "72",
    y: "8",
    width: "16",
    height: "16"
  }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("path", {
    fillRule: "evenodd",
    clipRule: "evenodd",
    d: "M72.875 12.25L80 8.5L87.125 12.25V13.75H72.875V12.25ZM80 10.195L83.9075 12.25H76.0925L80 10.195ZM74.75 15.25H76.25V20.5H74.75V15.25ZM79.25 20.5V15.25H80.75V20.5H79.25ZM87.125 23.5V22H72.875V23.5H87.125ZM83.75 15.25H85.25V20.5H83.75V15.25Z",
    fill: "white"
  })), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("g", {
    mask: "url(#bacs2)"
  }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("rect", {
    x: "71",
    y: "7",
    width: "18",
    height: "18",
    fill: "white"
  })));
});

/***/ }),

/***/ "./client/task-list/tasks/payments/images/cod.js":
/*!*******************************************************!*\
  !*** ./client/task-list/tasks/payments/images/cod.js ***!
  \*******************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);

/* harmony default export */ __webpack_exports__["default"] = (function () {
  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("svg", {
    width: "96",
    height: "32",
    viewBox: "0 0 96 32",
    fill: "none",
    xmlns: "http://www.w3.org/2000/svg"
  }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("rect", {
    width: "32",
    height: "32",
    rx: "16",
    fill: "#8E9196"
  }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("mask", {
    id: "cod-mask-0",
    "mask-type": "alpha",
    maskUnits: "userSpaceOnUse",
    x: "7",
    y: "10",
    width: "18",
    height: "12"
  }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("path", {
    fillRule: "evenodd",
    clipRule: "evenodd",
    d: "M22 13H19.75V10H9.25C8.425 10 7.75 10.675 7.75 11.5V19.75H9.25C9.25 20.995 10.255 22 11.5 22C12.745 22 13.75 20.995 13.75 19.75H18.25C18.25 20.995 19.255 22 20.5 22C21.745 22 22.75 20.995 22.75 19.75H24.25V16L22 13ZM21.625 14.125L23.095 16H19.75V14.125H21.625ZM10.75 19.75C10.75 20.1625 11.0875 20.5 11.5 20.5C11.9125 20.5 12.25 20.1625 12.25 19.75C12.25 19.3375 11.9125 19 11.5 19C11.0875 19 10.75 19.3375 10.75 19.75ZM13.165 18.25C12.7525 17.7925 12.1675 17.5 11.5 17.5C10.8325 17.5 10.2475 17.7925 9.835 18.25H9.25V11.5H18.25V18.25H13.165ZM19.75 19.75C19.75 20.1625 20.0875 20.5 20.5 20.5C20.9125 20.5 21.25 20.1625 21.25 19.75C21.25 19.3375 20.9125 19 20.5 19C20.0875 19 19.75 19.3375 19.75 19.75Z",
    fill: "white"
  })), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("g", {
    mask: "url(#cod-mask-0)"
  }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("rect", {
    x: "7",
    y: "7",
    width: "18",
    height: "18",
    fill: "white"
  })), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("mask", {
    id: "cod-mask-1",
    "mask-type": "alpha",
    maskUnits: "userSpaceOnUse",
    x: "39",
    y: "10",
    width: "18",
    height: "12"
  }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("path", {
    d: "M39 17L53.17 17L49.59 20.59L51 22L57 16L51 10L49.59 11.41L53.17 15L39 15L39 17Z",
    fill: "white"
  })), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("g", {
    mask: "url(#cod-mask-1)"
  }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("rect", {
    x: "60",
    y: "28",
    width: "24",
    height: "24",
    transform: "rotate(-180 60 28)",
    fill: "#8E9196"
  })), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("rect", {
    x: "64",
    width: "32",
    height: "32",
    rx: "16",
    fill: "#8E9196"
  }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("mask", {
    id: "cod-mask-2",
    "mask-type": "alpha",
    maskUnits: "userSpaceOnUse",
    x: "76",
    y: "9",
    width: "8",
    height: "14"
  }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("path", {
    d: "M80.2926 15.175C78.5901 14.7325 78.0426 14.275 78.0426 13.5625C78.0426 12.745 78.8001 12.175 80.0676 12.175C81.4026 12.175 81.8976 12.8125 81.9426 13.75H83.6001C83.5476 12.46 82.7601 11.275 81.1926 10.8925V9.25H78.9426V10.87C77.4876 11.185 76.3176 12.13 76.3176 13.5775C76.3176 15.31 77.7501 16.1725 79.8426 16.675C81.7176 17.125 82.0926 17.785 82.0926 18.4825C82.0926 19 81.7251 19.825 80.0676 19.825C78.5226 19.825 77.9151 19.135 77.8326 18.25H76.1826C76.2726 19.8925 77.5026 20.815 78.9426 21.1225V22.75H81.1926V21.1375C82.6551 20.86 83.8176 20.0125 83.8176 18.475C83.8176 16.345 81.9951 15.6175 80.2926 15.175Z",
    fill: "white"
  })), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("g", {
    mask: "url(#cod-mask-2)"
  }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("rect", {
    x: "71",
    y: "7",
    width: "18",
    height: "18",
    fill: "white"
  })));
});

/***/ }),

/***/ "./client/task-list/tasks/payments/images/wcpay.js":
/*!*********************************************************!*\
  !*** ./client/task-list/tasks/payments/images/wcpay.js ***!
  \*********************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);

/* harmony default export */ __webpack_exports__["default"] = (function () {
  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("svg", {
    width: "100",
    height: "64",
    viewBox: "-10 0 120 64",
    fill: "none",
    xmlns: "http://www.w3.org/2000/svg"
  }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("path", {
    fillRule: "evenodd",
    clipRule: "evenodd",
    d: "M9.78073 0.5H91.1787C96.3299 0.5 100.5 4.77335 100.5 10.0522V41.8929C100.5 47.1717 96.3299 51.4451 91.1787 51.4451H61.9883L65.9948 61.5L48.3742 51.4451H9.82161C4.67036 51.4451 0.500298 47.1717 0.500298 41.8929V10.0522C0.459415 4.81524 4.62947 0.5 9.78073 0.5Z",
    fill: "#7F54B3"
  }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("path", {
    d: "M5.48791 9.1725C6.06028 8.37648 6.91882 7.95752 8.06354 7.87373C10.1486 7.70615 11.3342 8.71165 11.6204 10.8902C12.8877 19.6464 14.2778 27.0619 15.7495 33.1368L24.7029 15.6663C25.5206 14.0743 26.5426 13.2364 27.7691 13.1526C29.568 13.0269 30.6718 14.2 31.1215 16.6718C32.1436 22.2439 33.4519 26.9781 35.0054 31.0001C36.0684 20.3586 37.8672 12.6917 40.402 7.95753C41.0152 6.78445 41.9146 6.19791 43.1002 6.11412C44.0405 6.03033 44.8991 6.3236 45.6759 6.95203C46.4526 7.58047 46.8615 8.37648 46.9432 9.34008C46.9841 10.0942 46.8615 10.7226 46.5344 11.3511C44.94 14.3676 43.6317 19.4369 42.5688 26.4754C41.5467 33.3044 41.1787 38.6251 41.424 42.4376C41.5058 43.485 41.3423 44.4067 40.9334 45.2027C40.4428 46.1244 39.707 46.6272 38.7666 46.711C37.7037 46.7948 36.5998 46.292 35.5369 45.1608C31.7348 41.1807 28.7094 35.2316 26.5018 27.3133C23.8444 32.6759 21.882 36.6979 20.6146 39.3792C18.2025 44.1134 16.1584 46.5434 14.4413 46.6691C13.3374 46.7529 12.3971 45.7893 11.5795 43.7783C9.49445 38.2899 7.24589 27.6904 4.83379 11.9795C4.71114 10.8902 4.91555 9.92662 5.48791 9.1725Z",
    fill: "white"
  }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("path", {
    d: "M93.3864 15.7499C91.9146 13.1105 89.7478 11.5185 86.8451 10.89C86.0683 10.7225 85.3324 10.6387 84.6374 10.6387C80.7127 10.6387 77.5238 12.7335 75.0299 16.923C72.904 20.4841 71.8411 24.4223 71.8411 28.7376C71.8411 31.9635 72.4952 34.7286 73.8034 37.0329C75.2752 39.6723 77.442 41.2644 80.3447 41.8928C81.1215 42.0604 81.8574 42.1442 82.5524 42.1442C86.518 42.1442 89.7069 40.0494 92.1599 35.8598C94.2858 32.2568 95.3488 28.3186 95.3488 24.0034C95.3488 20.7355 94.6946 18.0123 93.3864 15.7499ZM88.2351 27.355C87.6628 30.1201 86.6407 32.173 85.128 33.5556C83.9424 34.6449 82.8386 35.1057 81.8165 34.8962C80.8353 34.6868 80.0177 33.8069 79.4044 32.173C78.9138 30.8742 78.6685 29.5755 78.6685 28.3605C78.6685 27.3131 78.7503 26.2657 78.9547 25.3021C79.3226 23.5844 80.0177 21.9086 81.1215 20.3166C82.4706 18.2637 83.9015 17.4258 85.3733 17.719C86.3545 17.9285 87.1722 18.8083 87.7854 20.4422C88.276 21.741 88.5213 23.0398 88.5213 24.2547C88.5213 25.344 88.3987 26.3914 88.2351 27.355Z",
    fill: "white"
  }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("path", {
    d: "M67.7528 15.7499C66.281 13.1105 64.0734 11.5185 61.2116 10.89C60.4348 10.7225 59.6989 10.6387 59.0039 10.6387C55.0791 10.6387 51.8903 12.7335 49.3964 16.923C47.2705 20.4841 46.2075 24.4223 46.2075 28.7376C46.2075 31.9635 46.8616 34.7286 48.1699 37.0329C49.6417 39.6723 51.8085 41.2644 54.7112 41.8928C55.488 42.0604 56.2238 42.1442 56.9189 42.1442C60.8845 42.1442 64.0734 40.0494 66.5263 35.8598C68.6523 32.2568 69.7152 28.3186 69.7152 24.0034C69.7152 20.7355 69.0611 18.0123 67.7528 15.7499ZM62.6016 27.355C62.0292 30.1201 61.0071 32.173 59.4945 33.5556C58.3089 34.6449 57.205 35.1057 56.183 34.8962C55.2018 34.6868 54.3841 33.8069 53.7709 32.173C53.2803 30.8742 53.035 29.5755 53.035 28.3605C53.035 27.3131 53.1167 26.2657 53.3212 25.3021C53.6891 23.5844 54.3841 21.9086 55.4879 20.3166C56.8371 18.2637 58.268 17.4258 59.7398 17.719C60.721 17.9285 61.5386 18.8083 62.1519 20.4422C62.6425 21.741 62.8878 23.0398 62.8878 24.2547C62.8878 25.344 62.806 26.3914 62.6016 27.355Z",
    fill: "white"
  }));
});

/***/ }),

/***/ "./client/task-list/tasks/payments/index.js":
/*!**************************************************!*\
  !*** ./client/task-list/tasks/payments/index.js ***!
  \**************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/defineProperty */ "./node_modules/@babel/runtime/helpers/defineProperty.js");
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _babel_runtime_helpers_asyncToGenerator__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/asyncToGenerator */ "./node_modules/@babel/runtime/helpers/asyncToGenerator.js");
/* harmony import */ var _babel_runtime_helpers_asyncToGenerator__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_asyncToGenerator__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @babel/runtime/helpers/classCallCheck */ "./node_modules/@babel/runtime/helpers/classCallCheck.js");
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @babel/runtime/helpers/createClass */ "./node_modules/@babel/runtime/helpers/createClass.js");
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @babel/runtime/helpers/assertThisInitialized */ "./node_modules/@babel/runtime/helpers/assertThisInitialized.js");
/* harmony import */ var _babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @babel/runtime/helpers/inherits */ "./node_modules/@babel/runtime/helpers/inherits.js");
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @babel/runtime/helpers/possibleConstructorReturn */ "./node_modules/@babel/runtime/helpers/possibleConstructorReturn.js");
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @babel/runtime/helpers/getPrototypeOf */ "./node_modules/@babel/runtime/helpers/getPrototypeOf.js");
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_8__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_9__);
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! classnames */ "./node_modules/classnames/index.js");
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_10___default = /*#__PURE__*/__webpack_require__.n(classnames__WEBPACK_IMPORTED_MODULE_10__);
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! @wordpress/compose */ "./node_modules/@wordpress/compose/build-module/index.js");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! @wordpress/components */ "./node_modules/@wordpress/components/build-module/index.js");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_13___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_13__);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! @woocommerce/components */ "@woocommerce/components");
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_14___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_components__WEBPACK_IMPORTED_MODULE_14__);
/* harmony import */ var _woocommerce_navigation__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(/*! @woocommerce/navigation */ "@woocommerce/navigation");
/* harmony import */ var _woocommerce_navigation__WEBPACK_IMPORTED_MODULE_15___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_15__);
/* harmony import */ var _woocommerce_data__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__(/*! @woocommerce/data */ "@woocommerce/data");
/* harmony import */ var _woocommerce_data__WEBPACK_IMPORTED_MODULE_16___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_data__WEBPACK_IMPORTED_MODULE_16__);
/* harmony import */ var lib_tracks__WEBPACK_IMPORTED_MODULE_17__ = __webpack_require__(/*! lib/tracks */ "./client/lib/tracks.js");
/* harmony import */ var dashboard_utils__WEBPACK_IMPORTED_MODULE_18__ = __webpack_require__(/*! dashboard/utils */ "./client/dashboard/utils.js");
/* harmony import */ var wc_api_with_select__WEBPACK_IMPORTED_MODULE_19__ = __webpack_require__(/*! wc-api/with-select */ "./client/wc-api/with-select.js");
/* harmony import */ var _methods__WEBPACK_IMPORTED_MODULE_20__ = __webpack_require__(/*! ./methods */ "./client/task-list/tasks/payments/methods.js");










function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0___default()(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_7___default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_7___default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_6___default()(this, result); }; }

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






var Payments = /*#__PURE__*/function (_Component) {
  _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_5___default()(Payments, _Component);

  var _super = _createSuper(Payments);

  function Payments(props) {
    var _this;

    _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_2___default()(this, Payments);

    _this = _super.apply(this, arguments);
    var methods = props.methods;
    var enabledMethods = {};
    methods.forEach(function (method) {
      return enabledMethods[method.key] = method.isEnabled;
    });
    _this.state = {
      busyMethod: null,
      enabledMethods: enabledMethods,
      recommendedMethod: _this.getRecommendedMethod()
    };
    _this.completeTask = _this.completeTask.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_4___default()(_this));
    _this.markConfigured = _this.markConfigured.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_4___default()(_this));
    _this.skipTask = _this.skipTask.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_4___default()(_this));
    return _this;
  }

  _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_3___default()(Payments, [{
    key: "componentDidUpdate",
    value: function componentDidUpdate() {
      var recommendedMethod = this.state.recommendedMethod;
      var method = this.getRecommendedMethod();

      if (recommendedMethod !== method) {
        this.setState({
          recommendedMethod: method
        });
      }
    }
  }, {
    key: "getRecommendedMethod",
    value: function getRecommendedMethod() {
      var methods = this.props.methods;
      return methods.find(function (m) {
        return m.key === 'wcpay' && m.visible;
      }) ? 'wcpay' : 'stripe';
    }
  }, {
    key: "completeTask",
    value: function () {
      var _completeTask = _babel_runtime_helpers_asyncToGenerator__WEBPACK_IMPORTED_MODULE_1___default()( /*#__PURE__*/regeneratorRuntime.mark(function _callee() {
        var _this$props, createNotice, methods, updateOptions, update;

        return regeneratorRuntime.wrap(function _callee$(_context) {
          while (1) {
            switch (_context.prev = _context.next) {
              case 0:
                _this$props = this.props, createNotice = _this$props.createNotice, methods = _this$props.methods, updateOptions = _this$props.updateOptions;
                _context.next = 3;
                return updateOptions({
                  woocommerce_task_list_payments: {
                    completed: 1,
                    timestamp: Math.floor(Date.now() / 1000)
                  }
                });

              case 3:
                update = _context.sent;
                Object(lib_tracks__WEBPACK_IMPORTED_MODULE_17__["recordEvent"])('tasklist_payment_done', {
                  configured: methods.filter(function (method) {
                    return method.isConfigured;
                  }).map(function (method) {
                    return method.key;
                  })
                });

                if (update.success) {
                  createNotice('success', Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_9__["__"])('💰 Ka-ching! Your store can now accept payments 💳', 'woocommerce'));
                  Object(_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_15__["getHistory"])().push(Object(_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_15__["getNewPath"])({}, '/', {}));
                } else {
                  createNotice('error', Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_9__["__"])('There was a problem updating settings', 'woocommerce'));
                }

              case 6:
              case "end":
                return _context.stop();
            }
          }
        }, _callee, this);
      }));

      function completeTask() {
        return _completeTask.apply(this, arguments);
      }

      return completeTask;
    }()
  }, {
    key: "skipTask",
    value: function skipTask() {
      var _this$props2 = this.props,
          methods = _this$props2.methods,
          updateOptions = _this$props2.updateOptions;
      updateOptions({
        woocommerce_task_list_payments: {
          skipped: 1,
          timestamp: Math.floor(Date.now() / 1000)
        }
      });
      Object(lib_tracks__WEBPACK_IMPORTED_MODULE_17__["recordEvent"])('tasklist_payment_skip_task', {
        options: methods.map(function (method) {
          return method.key;
        })
      });
      Object(_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_15__["getHistory"])().push(Object(_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_15__["getNewPath"])({}, '/', {}));
    }
  }, {
    key: "markConfigured",
    value: function markConfigured(method) {
      var enabledMethods = this.state.enabledMethods;
      this.setState({
        enabledMethods: _objectSpread(_objectSpread({}, enabledMethods), {}, _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0___default()({}, method, true))
      });
      Object(_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_15__["getHistory"])().push(Object(_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_15__["getNewPath"])({
        task: 'payments'
      }, '/', {}));
      Object(lib_tracks__WEBPACK_IMPORTED_MODULE_17__["recordEvent"])('tasklist_payment_connect_method', {
        payment_method: method
      });
    }
  }, {
    key: "getCurrentMethod",
    value: function getCurrentMethod() {
      var _this$props3 = this.props,
          methods = _this$props3.methods,
          query = _this$props3.query;

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
        return _woocommerce_data__WEBPACK_IMPORTED_MODULE_16__["pluginNames"][pluginSlug];
      }).join(' ' + Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_9__["__"])('and', 'woocommerce') + ' ');
      return {
        key: 'install',
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_9__["sprintf"])(Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_9__["__"])('Install %s', 'woocommerce'), pluginNamesString),
        content: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_8__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_14__["Plugins"], {
          onComplete: function onComplete() {
            Object(lib_tracks__WEBPACK_IMPORTED_MODULE_17__["recordEvent"])('tasklist_payment_install_method', {
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
      var _this$props4 = this.props,
          methods = _this$props4.methods,
          options = _this$props4.options,
          updateOptions = _this$props4.updateOptions;
      var enabledMethods = this.state.enabledMethods;
      var method = methods.find(function (option) {
        return option.key === key;
      });
      enabledMethods[key] = !enabledMethods[key];
      this.setState({
        enabledMethods: enabledMethods
      });
      Object(lib_tracks__WEBPACK_IMPORTED_MODULE_17__["recordEvent"])('tasklist_payment_toggle', {
        enabled: !method.isEnabled,
        payment_method: key
      });
      updateOptions(_babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0___default()({}, method.optionName, _objectSpread(_objectSpread({}, options[method.optionName]), {}, {
        enabled: method.isEnabled ? 'no' : 'yes'
      })));
    }
  }, {
    key: "handleClick",
    value: function () {
      var _handleClick = _babel_runtime_helpers_asyncToGenerator__WEBPACK_IMPORTED_MODULE_1___default()( /*#__PURE__*/regeneratorRuntime.mark(function _callee2(method) {
        var _this2 = this;

        var methods, key, onClick;
        return regeneratorRuntime.wrap(function _callee2$(_context2) {
          while (1) {
            switch (_context2.prev = _context2.next) {
              case 0:
                methods = this.props.methods;
                key = method.key, onClick = method.onClick;
                Object(lib_tracks__WEBPACK_IMPORTED_MODULE_17__["recordEvent"])('tasklist_payment_setup', {
                  options: methods.map(function (option) {
                    return option.key;
                  }),
                  selected: key
                });

                if (!onClick) {
                  _context2.next = 8;
                  break;
                }

                this.setState({
                  busyMethod: key
                });
                _context2.next = 7;
                return new Promise(onClick).then(function () {
                  _this2.setState({
                    busyMethod: null
                  });
                }).catch(function () {
                  _this2.setState({
                    busyMethod: null
                  });
                });

              case 7:
                return _context2.abrupt("return");

              case 8:
                Object(_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_15__["updateQueryString"])({
                  method: key
                });

              case 9:
              case "end":
                return _context2.stop();
            }
          }
        }, _callee2, this);
      }));

      function handleClick(_x) {
        return _handleClick.apply(this, arguments);
      }

      return handleClick;
    }()
  }, {
    key: "render",
    value: function render() {
      var _this3 = this;

      var currentMethod = this.getCurrentMethod();
      var _this$state = this.state,
          busyMethod = _this$state.busyMethod,
          enabledMethods = _this$state.enabledMethods,
          recommendedMethod = _this$state.recommendedMethod;
      var _this$props5 = this.props,
          methods = _this$props5.methods,
          query = _this$props5.query,
          requesting = _this$props5.requesting;
      var hasEnabledMethods = Object.keys(enabledMethods).filter(function (method) {
        return enabledMethods[method];
      }).length;

      if (currentMethod) {
        return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_8__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_14__["Card"], {
          className: "woocommerce-task-payment-method is-narrow"
        }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_8__["cloneElement"])(currentMethod.container, {
          query: query,
          installStep: this.getInstallStep(),
          markConfigured: this.markConfigured,
          hasCbdIndustry: currentMethod.hasCbdIndustry
        }));
      }

      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_8__["createElement"])("div", {
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

        var classes = classnames__WEBPACK_IMPORTED_MODULE_10___default()('woocommerce-task-payment', 'is-narrow', !isConfigured && 'woocommerce-task-payment-not-configured', 'woocommerce-task-payment-' + key);
        var isRecommended = key === recommendedMethod && !isConfigured;
        var showRecommendedRibbon = isRecommended && key !== 'wcpay';
        var showRecommendedPill = isRecommended && key === 'wcpay';
        return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_8__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_14__["Card"], {
          key: key,
          className: classes
        }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_8__["createElement"])("div", {
          className: "woocommerce-task-payment__before"
        }, showRecommendedRibbon && Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_8__["createElement"])("div", {
          className: "woocommerce-task-payment__recommended-ribbon"
        }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_8__["createElement"])("span", null, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_9__["__"])('Recommended', 'woocommerce'))), before), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_8__["createElement"])("div", {
          className: "woocommerce-task-payment__text"
        }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_8__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_14__["H"], {
          className: "woocommerce-task-payment__title"
        }, title, showRecommendedPill && Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_8__["createElement"])("span", {
          className: "woocommerce-task-payment__recommended-pill"
        }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_9__["__"])('Recommended', 'woocommerce'))), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_8__["createElement"])("div", {
          className: "woocommerce-task-payment__content"
        }, content)), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_8__["createElement"])("div", {
          className: "woocommerce-task-payment__after"
        }, container && !isConfigured ? Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_8__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_12__["Button"], {
          isPrimary: key === recommendedMethod,
          isSecondary: key !== recommendedMethod,
          isBusy: busyMethod === key,
          disabled: busyMethod,
          onClick: function onClick() {
            return _this3.handleClick(method);
          }
        }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_9__["__"])('Set up', 'woocommerce')) : Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_8__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_12__["FormToggle"], {
          checked: enabledMethods[key],
          onChange: function onChange() {
            return _this3.toggleMethod(key);
          },
          onClick: function onClick(e) {
            return e.stopPropagation();
          }
        })));
      }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_8__["createElement"])("div", {
        className: "woocommerce-task-payments__actions"
      }, !hasEnabledMethods ? Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_8__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_12__["Button"], {
        isLink: true,
        onClick: this.skipTask
      }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_9__["__"])('My store doesn’t take payments', 'woocommerce')) : Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_8__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_12__["Button"], {
        isPrimary: true,
        isBusy: requesting,
        onClick: this.completeTask
      }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_9__["__"])('Done', 'woocommerce'))));
    }
  }]);

  return Payments;
}(_wordpress_element__WEBPACK_IMPORTED_MODULE_8__["Component"]);

/* harmony default export */ __webpack_exports__["default"] = (Object(_wordpress_compose__WEBPACK_IMPORTED_MODULE_11__["compose"])(Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_13__["withDispatch"])(function (dispatch) {
  var _dispatch = dispatch('core/notices'),
      createNotice = _dispatch.createNotice;

  var _dispatch2 = dispatch(_woocommerce_data__WEBPACK_IMPORTED_MODULE_16__["PLUGINS_STORE_NAME"]),
      installAndActivatePlugins = _dispatch2.installAndActivatePlugins;

  var _dispatch3 = dispatch(_woocommerce_data__WEBPACK_IMPORTED_MODULE_16__["OPTIONS_STORE_NAME"]),
      updateOptions = _dispatch3.updateOptions;

  return {
    createNotice: createNotice,
    installAndActivatePlugins: installAndActivatePlugins,
    updateOptions: updateOptions
  };
}), Object(wc_api_with_select__WEBPACK_IMPORTED_MODULE_19__["default"])(function (select, props) {
  var createNotice = props.createNotice,
      installAndActivatePlugins = props.installAndActivatePlugins;

  var _select = select(_woocommerce_data__WEBPACK_IMPORTED_MODULE_16__["ONBOARDING_STORE_NAME"]),
      getProfileItems = _select.getProfileItems;

  var _select2 = select(_woocommerce_data__WEBPACK_IMPORTED_MODULE_16__["OPTIONS_STORE_NAME"]),
      getOption = _select2.getOption,
      isOptionsUpdating = _select2.isOptionsUpdating;

  var _select3 = select(_woocommerce_data__WEBPACK_IMPORTED_MODULE_16__["PLUGINS_STORE_NAME"]),
      getActivePlugins = _select3.getActivePlugins,
      isJetpackConnected = _select3.isJetpackConnected;

  var activePlugins = getActivePlugins();
  var profileItems = getProfileItems();
  var optionNames = ['woocommerce_default_country', 'woocommerce_woocommerce_payments_settings', 'woocommerce_stripe_settings', 'woocommerce_ppec_paypal_settings', 'woocommerce_payfast_settings', 'woocommerce_square_credit_card_settings', 'woocommerce_klarna_payments_settings', 'woocommerce_kco_settings', 'wc_square_refresh_tokens', 'woocommerce_cod_settings', 'woocommerce_bacs_settings', 'woocommerce_bacs_accounts'];
  var options = optionNames.reduce(function (result, name) {
    result[name] = getOption(name);
    return result;
  }, {});
  var countryCode = Object(dashboard_utils__WEBPACK_IMPORTED_MODULE_18__["getCountryCode"])(options.woocommerce_default_country);
  var methods = Object(_methods__WEBPACK_IMPORTED_MODULE_20__["getPaymentMethods"])({
    activePlugins: activePlugins,
    countryCode: countryCode,
    createNotice: createNotice,
    installAndActivatePlugins: installAndActivatePlugins,
    isJetpackConnected: isJetpackConnected(),
    options: options,
    profileItems: profileItems
  });
  var requesting = isOptionsUpdating();
  return {
    countryCode: countryCode,
    profileItems: profileItems,
    activePlugins: activePlugins,
    options: options,
    methods: methods,
    requesting: requesting
  };
}))(Payments));

/***/ }),

/***/ "./client/task-list/tasks/payments/klarna.js":
/*!***************************************************!*\
  !*** ./client/task-list/tasks/payments/klarna.js ***!
  \***************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/classCallCheck */ "./node_modules/@babel/runtime/helpers/classCallCheck.js");
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/createClass */ "./node_modules/@babel/runtime/helpers/createClass.js");
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @babel/runtime/helpers/assertThisInitialized */ "./node_modules/@babel/runtime/helpers/assertThisInitialized.js");
/* harmony import */ var _babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @babel/runtime/helpers/inherits */ "./node_modules/@babel/runtime/helpers/inherits.js");
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @babel/runtime/helpers/possibleConstructorReturn */ "./node_modules/@babel/runtime/helpers/possibleConstructorReturn.js");
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @babel/runtime/helpers/getPrototypeOf */ "./node_modules/@babel/runtime/helpers/getPrototypeOf.js");
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! @wordpress/components */ "./node_modules/@wordpress/components/build-module/index.js");
/* harmony import */ var interpolate_components__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! interpolate-components */ "./node_modules/interpolate-components/lib/index.js");
/* harmony import */ var interpolate_components__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(interpolate_components__WEBPACK_IMPORTED_MODULE_9__);
/* harmony import */ var _woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! @woocommerce/wc-admin-settings */ "./client/settings/index.js");
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! @woocommerce/components */ "@woocommerce/components");
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_11___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_components__WEBPACK_IMPORTED_MODULE_11__);








function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_5___default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_5___default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_4___default()(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/**
 * External dependencies
 */




/**
 * WooCommerce dependencies
 */




var Klarna = /*#__PURE__*/function (_Component) {
  _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_3___default()(Klarna, _Component);

  var _super = _createSuper(Klarna);

  function Klarna(props) {
    var _this;

    _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0___default()(this, Klarna);

    _this = _super.call(this, props);
    _this.continue = _this.continue.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_2___default()(_this));
    return _this;
  }

  _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1___default()(Klarna, [{
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
      var link = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_11__["Link"], {
        href: _woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_10__["ADMIN_URL"] + 'admin.php?page=wc-settings&tab=checkout&section=' + section,
        target: "_blank",
        type: "external"
      });
      var helpLink = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_11__["Link"], {
        href: 'https://docs.woocommerce.com/document/' + slug + '/#section-3',
        target: "_blank",
        type: "external"
      });
      var configureText = interpolate_components__WEBPACK_IMPORTED_MODULE_9___default()({
        mixedString: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__["__"])('Klarna can be configured under your {{link}}store settings{{/link}}. Figure out {{helpLink}}what you need{{/helpLink}}.', 'woocommerce'),
        components: {
          link: link,
          helpLink: helpLink
        }
      });
      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["Fragment"], null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("p", null, configureText), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_8__["Button"], {
        isPrimary: true,
        onClick: this.continue
      }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__["__"])('Continue', 'woocommerce')));
    }
  }, {
    key: "render",
    value: function render() {
      var installStep = this.props.installStep;
      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_11__["Stepper"], {
        isVertical: true,
        isPending: !installStep.isComplete,
        currentStep: installStep.isComplete ? 'connect' : 'install',
        steps: [installStep, {
          key: 'connect',
          label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__["__"])('Connect your Klarna account', 'woocommerce'),
          content: this.renderConnectStep()
        }]
      });
    }
  }]);

  return Klarna;
}(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["Component"]);

/* harmony default export */ __webpack_exports__["default"] = (Klarna);

/***/ }),

/***/ "./client/task-list/tasks/payments/methods.js":
/*!****************************************************!*\
  !*** ./client/task-list/tasks/payments/methods.js ***!
  \****************************************************/
/*! exports provided: getPaymentMethods */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "getPaymentMethods", function() { return getPaymentMethods; });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/api-fetch */ "@wordpress/api-fetch");
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! lodash */ "lodash");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var interpolate_components__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! interpolate-components */ "./node_modules/interpolate-components/lib/index.js");
/* harmony import */ var interpolate_components__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(interpolate_components__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @woocommerce/wc-admin-settings */ "./client/settings/index.js");
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @woocommerce/components */ "@woocommerce/components");
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_components__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var wc_api_constants__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! wc-api/constants */ "./client/wc-api/constants.js");
/* harmony import */ var _bacs__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ./bacs */ "./client/task-list/tasks/payments/bacs.js");
/* harmony import */ var _images_bacs__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ./images/bacs */ "./client/task-list/tasks/payments/images/bacs.js");
/* harmony import */ var _images_cod__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! ./images/cod */ "./client/task-list/tasks/payments/images/cod.js");
/* harmony import */ var lib_notices__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! lib/notices */ "./client/lib/notices/index.js");
/* harmony import */ var _stripe__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! ./stripe */ "./client/task-list/tasks/payments/stripe.js");
/* harmony import */ var _square__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! ./square */ "./client/task-list/tasks/payments/square.js");
/* harmony import */ var _wcpay__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! ./wcpay */ "./client/task-list/tasks/payments/wcpay.js");
/* harmony import */ var _images_wcpay__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(/*! ./images/wcpay */ "./client/task-list/tasks/payments/images/wcpay.js");
/* harmony import */ var _paypal__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__(/*! ./paypal */ "./client/task-list/tasks/payments/paypal.js");
/* harmony import */ var _klarna__WEBPACK_IMPORTED_MODULE_17__ = __webpack_require__(/*! ./klarna */ "./client/task-list/tasks/payments/klarna.js");
/* harmony import */ var _payfast__WEBPACK_IMPORTED_MODULE_18__ = __webpack_require__(/*! ./payfast */ "./client/task-list/tasks/payments/payfast.js");


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
      createNotice = _ref.createNotice,
      installAndActivatePlugins = _ref.installAndActivatePlugins,
      options = _ref.options,
      profileItems = _ref.profileItems;
  var settings = Object(_woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_5__["getSetting"])('onboarding', {
    stripeSupportedCountries: [],
    wcPayIsConnected: false
  });
  var stripeSupportedCountries = settings.stripeSupportedCountries,
      wcPayIsConnected = settings.wcPayIsConnected;
  var hasCbdIndustry = Object(lodash__WEBPACK_IMPORTED_MODULE_3__["some"])(profileItems.industry, {
    slug: 'cbd-other-hemp-derived-products'
  }) || false;
  var methods = [];

  if (window.wcAdminFeatures.wcpay) {
    var tosLink = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_6__["Link"], {
      href: 'https://wordpress.com/tos/',
      target: "_blank",
      type: "external"
    });
    var tosPrompt = interpolate_components__WEBPACK_IMPORTED_MODULE_4___default()({
      mixedString: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('By clicking "Set up," you agree to the {{link}}Terms of Service{{/link}}', 'woocommerce'),
      components: {
        link: tosLink
      }
    });
    var wcPayDocLink = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_6__["Link"], {
      href: 'https://docs.woocommerce.com/document/payments/testing/dev-mode/',
      target: "_blank",
      type: "external"
    });
    var wcPayDocPrompt = interpolate_components__WEBPACK_IMPORTED_MODULE_4___default()({
      mixedString: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Setting up a store for a client? {{link}}Start here{{/link}}', 'woocommerce'),
      components: {
        link: wcPayDocLink
      }
    });
    var wcPaySettingsLink = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_6__["Link"], {
      href: Object(_woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_5__["getAdminLink"])('admin.php?page=wc-settings&tab=checkout&section=woocommerce_payments'),
      type: "wp-admin"
    }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Settings', 'woocommerce'));
    methods.push({
      key: 'wcpay',
      title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('WooCommerce Payments', 'woocommerce'),
      content: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["Fragment"], null, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Accept credit card payments the easy way! No setup fees. No ' + 'monthly fees. Just 2.9% + $0.30 per transaction ' + 'on U.S. issued cards. ', 'woocommerce'), wcPayIsConnected && wcPaySettingsLink, !wcPayIsConnected && Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("p", null, tosPrompt), profileItems.setup_client && Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("p", null, wcPayDocPrompt)),
      before: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_images_wcpay__WEBPACK_IMPORTED_MODULE_15__["default"], null),
      onClick: function onClick(resolve, reject) {
        var errorMessage = Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('There was an error connecting to WooCommerce Payments. Please try again or connect later in store settings.', 'woocommerce');

        var connect = function connect() {
          _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default()({
            path: wc_api_constants__WEBPACK_IMPORTED_MODULE_7__["WC_ADMIN_NAMESPACE"] + '/plugins/connect-wcpay',
            method: 'POST'
          }).then(function (response) {
            window.location = response.connectUrl;
          }).catch(function () {
            createNotice('error', errorMessage);
            reject();
          });
        };

        installAndActivatePlugins(['woocommerce-payments']).then(function () {
          return connect();
        }).catch(function (error) {
          Object(lib_notices__WEBPACK_IMPORTED_MODULE_11__["createNoticesFromResponse"])(error);
          reject();
        });
      },
      visible: ['US', 'PR'].includes(countryCode) && !hasCbdIndustry,
      plugins: ['woocommerce-payments'],
      container: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_wcpay__WEBPACK_IMPORTED_MODULE_14__["default"], null),
      isConfigured: wcPayIsConnected,
      isEnabled: options.woocommerce_woocommerce_payments_settings && options.woocommerce_woocommerce_payments_settings.enabled === 'yes',
      optionName: 'woocommerce_woocommerce_payments_settings'
    });
  }

  methods.push({
    key: 'stripe',
    title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Credit cards - powered by Stripe', 'woocommerce'),
    content: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["Fragment"], null, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Accept debit and credit cards in 135+ currencies, methods such as Alipay, ' + 'and one-touch checkout with Apple Pay.', 'woocommerce')),
    before: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("img", {
      src: _woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_5__["WC_ASSET_URL"] + 'images/stripe.png',
      alt: ""
    }),
    visible: stripeSupportedCountries.includes(countryCode) && !hasCbdIndustry,
    plugins: ['woocommerce-gateway-stripe'],
    container: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_stripe__WEBPACK_IMPORTED_MODULE_12__["default"], null),
    isConfigured: options.woocommerce_stripe_settings && options.woocommerce_stripe_settings.publishable_key && options.woocommerce_stripe_settings.secret_key,
    isEnabled: options.woocommerce_stripe_settings && options.woocommerce_stripe_settings.enabled === 'yes',
    optionName: 'woocommerce_stripe_settings'
  }, {
    key: 'paypal',
    title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('PayPal Checkout', 'woocommerce'),
    content: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["Fragment"], null, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])("Safe and secure payments using credit cards or your customer's PayPal account.", 'woocommerce')),
    before: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("img", {
      src: _woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_5__["WC_ASSET_URL"] + 'images/paypal.png',
      alt: ""
    }),
    visible: !hasCbdIndustry,
    plugins: ['woocommerce-gateway-paypal-express-checkout'],
    container: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_paypal__WEBPACK_IMPORTED_MODULE_16__["default"], null),
    isConfigured: options.woocommerce_ppec_paypal_settings && options.woocommerce_ppec_paypal_settings.api_username && options.woocommerce_ppec_paypal_settings.api_password,
    isEnabled: options.woocommerce_ppec_paypal_settings && options.woocommerce_ppec_paypal_settings.enabled === 'yes',
    optionName: 'woocommerce_ppec_paypal_settings'
  }, {
    key: 'klarna_checkout',
    title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Klarna Checkout', 'woocommerce'),
    content: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Choose the payment that you want, pay now, pay later or slice it. No credit card numbers, no passwords, no worries.', 'woocommerce'),
    before: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("img", {
      src: _woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_5__["WC_ASSET_URL"] + 'images/klarna-black.png',
      alt: ""
    }),
    visible: ['SE', 'FI', 'NO', 'NL'].includes(countryCode) && !hasCbdIndustry,
    plugins: ['klarna-checkout-for-woocommerce'],
    container: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_klarna__WEBPACK_IMPORTED_MODULE_17__["default"], {
      plugin: 'checkout'
    }),
    // @todo This should check actual Klarna connection information.
    isConfigured: activePlugins.includes('klarna-checkout-for-woocommerce'),
    isEnabled: options.woocommerce_kco_settings && options.woocommerce_kco_settings.enabled === 'yes',
    optionName: 'woocommerce_kco_settings'
  }, {
    key: 'klarna_payments',
    title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Klarna Payments', 'woocommerce'),
    content: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Choose the payment that you want, pay now, pay later or slice it. No credit card numbers, no passwords, no worries.', 'woocommerce'),
    before: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("img", {
      src: _woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_5__["WC_ASSET_URL"] + 'images/klarna-black.png',
      alt: ""
    }),
    visible: ['DK', 'DE', 'AT'].includes(countryCode) && !hasCbdIndustry,
    plugins: ['klarna-payments-for-woocommerce'],
    container: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_klarna__WEBPACK_IMPORTED_MODULE_17__["default"], {
      plugin: 'payments'
    }),
    // @todo This should check actual Klarna connection information.
    isConfigured: activePlugins.includes('klarna-payments-for-woocommerce'),
    isEnabled: options.woocommerce_klarna_payments_settings && options.woocommerce_klarna_payments_settings.enabled === 'yes',
    optionName: 'woocommerce_klarna_payments_settings'
  }, {
    key: 'square',
    title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Square', 'woocommerce'),
    content: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["Fragment"], null, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Securely accept credit and debit cards with one low rate, no surprise fees (custom rates available). ' + 'Sell online and in store and track sales and inventory in one place.', 'woocommerce'), hasCbdIndustry && Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("span", {
      className: "text-style-strong"
    }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])(' Selling CBD products is only supported by Square.', 'woocommerce'))),
    before: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("img", {
      src: _woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_5__["WC_ASSET_URL"] + 'images/square-black.png',
      alt: ""
    }),
    visible: hasCbdIndustry && ['US'].includes(countryCode) || ['brick-mortar', 'brick-mortar-other'].includes(profileItems.selling_venues) && ['US', 'CA', 'JP', 'GB', 'AU'].includes(countryCode),
    plugins: ['woocommerce-square'],
    container: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_square__WEBPACK_IMPORTED_MODULE_13__["default"], null),
    isConfigured: options.wc_square_refresh_tokens && options.wc_square_refresh_tokens.length,
    isEnabled: options.woocommerce_square_credit_card_settings && options.woocommerce_square_credit_card_settings.enabled === 'yes',
    optionName: 'woocommerce_square_credit_card_settings',
    hasCbdIndustry: hasCbdIndustry
  }, {
    key: 'payfast',
    title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('PayFast', 'woocommerce'),
    content: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["Fragment"], null, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('The PayFast extension for WooCommerce enables you to accept payments by Credit Card and EFT via one of South Africa’s most popular payment gateways. No setup fees or monthly subscription costs.', 'woocommerce'), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("p", null, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Selecting this extension will configure your store to use South African rands as the selected currency.', 'woocommerce'))),
    before: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("img", {
      src: _woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_5__["WC_ASSET_URL"] + 'images/payfast.png',
      alt: "PayFast logo"
    }),
    visible: ['ZA'].includes(countryCode) && !hasCbdIndustry,
    plugins: ['woocommerce-payfast-gateway'],
    container: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_payfast__WEBPACK_IMPORTED_MODULE_18__["default"], null),
    isConfigured: options.woocommerce_payfast_settings && options.woocommerce_payfast_settings.merchant_id && options.woocommerce_payfast_settings.merchant_key && options.woocommerce_payfast_settings.pass_phrase,
    isEnabled: options.woocommerce_payfast_settings && options.woocommerce_payfast_settings.enabled === 'yes',
    optionName: 'woocommerce_payfast_settings'
  }, {
    key: 'cod',
    title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Cash on delivery', 'woocommerce'),
    content: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Take payments in cash upon delivery.', 'woocommerce'),
    before: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_images_cod__WEBPACK_IMPORTED_MODULE_10__["default"], null),
    visible: !hasCbdIndustry,
    isEnabled: options.woocommerce_cod_settings && options.woocommerce_cod_settings.enabled === 'yes',
    optionName: 'woocommerce_cod_settings'
  }, {
    key: 'bacs',
    title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Direct bank transfer', 'woocommerce'),
    content: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('Take payments via bank transfer.', 'woocommerce'),
    before: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_images_bacs__WEBPACK_IMPORTED_MODULE_9__["default"], null),
    visible: !hasCbdIndustry,
    container: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_bacs__WEBPACK_IMPORTED_MODULE_8__["default"], null),
    isConfigured: options.woocommerce_bacs_accounts && options.woocommerce_bacs_accounts.length,
    isEnabled: options.woocommerce_bacs_settings && options.woocommerce_bacs_settings.enabled === 'yes',
    optionName: 'woocommerce_bacs_settings'
  });
  return Object(lodash__WEBPACK_IMPORTED_MODULE_3__["filter"])(methods, function (method) {
    return method.visible;
  });
}

/***/ }),

/***/ "./client/task-list/tasks/payments/payfast.js":
/*!****************************************************!*\
  !*** ./client/task-list/tasks/payments/payfast.js ***!
  \****************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/extends */ "./node_modules/@babel/runtime/helpers/extends.js");
/* harmony import */ var _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _babel_runtime_helpers_asyncToGenerator__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/asyncToGenerator */ "./node_modules/@babel/runtime/helpers/asyncToGenerator.js");
/* harmony import */ var _babel_runtime_helpers_asyncToGenerator__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_asyncToGenerator__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @babel/runtime/helpers/classCallCheck */ "./node_modules/@babel/runtime/helpers/classCallCheck.js");
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @babel/runtime/helpers/createClass */ "./node_modules/@babel/runtime/helpers/createClass.js");
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @babel/runtime/helpers/inherits */ "./node_modules/@babel/runtime/helpers/inherits.js");
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @babel/runtime/helpers/possibleConstructorReturn */ "./node_modules/@babel/runtime/helpers/possibleConstructorReturn.js");
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @babel/runtime/helpers/getPrototypeOf */ "./node_modules/@babel/runtime/helpers/getPrototypeOf.js");
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_8__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! @wordpress/components */ "./node_modules/@wordpress/components/build-module/index.js");
/* harmony import */ var interpolate_components__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! interpolate-components */ "./node_modules/interpolate-components/lib/index.js");
/* harmony import */ var interpolate_components__WEBPACK_IMPORTED_MODULE_10___default = /*#__PURE__*/__webpack_require__.n(interpolate_components__WEBPACK_IMPORTED_MODULE_10__);
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! @wordpress/compose */ "./node_modules/@wordpress/compose/build-module/index.js");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_12___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_12__);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! @woocommerce/components */ "@woocommerce/components");
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_13___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_components__WEBPACK_IMPORTED_MODULE_13__);
/* harmony import */ var _woocommerce_data__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! @woocommerce/data */ "@woocommerce/data");
/* harmony import */ var _woocommerce_data__WEBPACK_IMPORTED_MODULE_14___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_data__WEBPACK_IMPORTED_MODULE_14__);









function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_6___default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_6___default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_5___default()(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/**
 * External dependencies
 */






/**
 * WooCommerce dependencies
 */




var PayFast = /*#__PURE__*/function (_Component) {
  _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_4___default()(PayFast, _Component);

  var _super = _createSuper(PayFast);

  function PayFast() {
    var _temp, _this;

    _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_2___default()(this, PayFast);

    for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
      args[_key] = arguments[_key];
    }

    return _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_5___default()(_this, (_temp = _this = _super.call.apply(_super, [this].concat(args)), _this.getInitialConfigValues = function () {
      return {
        merchant_id: '',
        merchant_key: '',
        pass_phrase: ''
      };
    }, _this.validate = function (values) {
      var errors = {};

      if (!values.merchant_id) {
        errors.merchant_id = Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_8__["__"])('Please enter your merchant ID', 'woocommerce');
      }

      if (!values.merchant_key) {
        errors.merchant_key = Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_8__["__"])('Please enter your merchant key', 'woocommerce');
      }

      if (!values.pass_phrase) {
        errors.pass_phrase = Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_8__["__"])('Please enter your passphrase', 'woocommerce');
      }

      return errors;
    }, _this.updateSettings = /*#__PURE__*/function () {
      var _ref = _babel_runtime_helpers_asyncToGenerator__WEBPACK_IMPORTED_MODULE_1___default()( /*#__PURE__*/regeneratorRuntime.mark(function _callee(values) {
        var _this$props, updateOptions, createNotice, markConfigured, update;

        return regeneratorRuntime.wrap(function _callee$(_context) {
          while (1) {
            switch (_context.prev = _context.next) {
              case 0:
                _this$props = _this.props, updateOptions = _this$props.updateOptions, createNotice = _this$props.createNotice, markConfigured = _this$props.markConfigured; // Because the PayFast extension only works with the South African Rand
                // currency, force the store to use it while setting the PayFast settings

                _context.next = 3;
                return updateOptions({
                  woocommerce_currency: 'ZAR',
                  woocommerce_payfast_settings: {
                    merchant_id: values.merchant_id,
                    merchant_key: values.merchant_key,
                    pass_phrase: values.pass_phrase,
                    enabled: 'yes'
                  }
                });

              case 3:
                update = _context.sent;

                if (update.success) {
                  markConfigured('payfast');
                  createNotice('success', Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_8__["__"])('PayFast connected successfully', 'woocommerce'));
                } else {
                  createNotice('error', Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_8__["__"])('There was a problem saving your payment setings', 'woocommerce'));
                }

              case 5:
              case "end":
                return _context.stop();
            }
          }
        }, _callee);
      }));

      return function (_x) {
        return _ref.apply(this, arguments);
      };
    }(), _temp));
  }

  _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_3___default()(PayFast, [{
    key: "renderConnectStep",
    value: function renderConnectStep() {
      var isOptionsRequesting = this.props.isOptionsRequesting;
      var helpText = interpolate_components__WEBPACK_IMPORTED_MODULE_10___default()({
        mixedString: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_8__["__"])('Your API details can be obtained from your {{link}}PayFast account{{/link}}', 'woocommerce'),
        components: {
          link: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_13__["Link"], {
            href: "https://www.payfast.co.za/",
            target: "_blank",
            type: "external"
          })
        }
      });
      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_13__["Form"], {
        initialValues: this.getInitialConfigValues(),
        onSubmitCallback: this.updateSettings,
        validate: this.validate
      }, function (_ref2) {
        var getInputProps = _ref2.getInputProps,
            handleSubmit = _ref2.handleSubmit;
        return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["Fragment"], null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_13__["TextControl"], _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0___default()({
          label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_8__["__"])('Merchant ID', 'woocommerce'),
          required: true
        }, getInputProps('merchant_id'))), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_13__["TextControl"], _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0___default()({
          label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_8__["__"])('Merchant Key', 'woocommerce'),
          required: true
        }, getInputProps('merchant_key'))), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_13__["TextControl"], _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0___default()({
          label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_8__["__"])('Passphrase', 'woocommerce'),
          required: true
        }, getInputProps('pass_phrase'))), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_9__["Button"], {
          isPrimary: true,
          isBusy: isOptionsRequesting,
          onClick: handleSubmit
        }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_8__["__"])('Proceed', 'woocommerce')), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])("p", null, helpText));
      });
    }
  }, {
    key: "render",
    value: function render() {
      var _this$props2 = this.props,
          installStep = _this$props2.installStep,
          isOptionsRequesting = _this$props2.isOptionsRequesting;
      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_13__["Stepper"], {
        isVertical: true,
        isPending: !installStep.isComplete || isOptionsRequesting,
        currentStep: installStep.isComplete ? 'connect' : 'install',
        steps: [installStep, {
          key: 'connect',
          label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_8__["__"])('Connect your PayFast account', 'woocommerce'),
          content: this.renderConnectStep()
        }]
      });
    }
  }]);

  return PayFast;
}(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["Component"]);

/* harmony default export */ __webpack_exports__["default"] = (Object(_wordpress_compose__WEBPACK_IMPORTED_MODULE_11__["compose"])(Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_12__["withSelect"])(function (select) {
  var _select = select(_woocommerce_data__WEBPACK_IMPORTED_MODULE_14__["OPTIONS_STORE_NAME"]),
      isOptionsUpdating = _select.isOptionsUpdating;

  var isOptionsRequesting = isOptionsUpdating();
  return {
    isOptionsRequesting: isOptionsRequesting
  };
}), Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_12__["withDispatch"])(function (dispatch) {
  var _dispatch = dispatch('core/notices'),
      createNotice = _dispatch.createNotice;

  var _dispatch2 = dispatch(_woocommerce_data__WEBPACK_IMPORTED_MODULE_14__["OPTIONS_STORE_NAME"]),
      updateOptions = _dispatch2.updateOptions;

  return {
    createNotice: createNotice,
    updateOptions: updateOptions
  };
}))(PayFast));

/***/ }),

/***/ "./client/task-list/tasks/payments/paypal.js":
/*!***************************************************!*\
  !*** ./client/task-list/tasks/payments/paypal.js ***!
  \***************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/extends */ "./node_modules/@babel/runtime/helpers/extends.js");
/* harmony import */ var _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/defineProperty */ "./node_modules/@babel/runtime/helpers/defineProperty.js");
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _babel_runtime_helpers_asyncToGenerator__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @babel/runtime/helpers/asyncToGenerator */ "./node_modules/@babel/runtime/helpers/asyncToGenerator.js");
/* harmony import */ var _babel_runtime_helpers_asyncToGenerator__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_asyncToGenerator__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @babel/runtime/helpers/classCallCheck */ "./node_modules/@babel/runtime/helpers/classCallCheck.js");
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @babel/runtime/helpers/createClass */ "./node_modules/@babel/runtime/helpers/createClass.js");
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @babel/runtime/helpers/assertThisInitialized */ "./node_modules/@babel/runtime/helpers/assertThisInitialized.js");
/* harmony import */ var _babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @babel/runtime/helpers/inherits */ "./node_modules/@babel/runtime/helpers/inherits.js");
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @babel/runtime/helpers/possibleConstructorReturn */ "./node_modules/@babel/runtime/helpers/possibleConstructorReturn.js");
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! @babel/runtime/helpers/getPrototypeOf */ "./node_modules/@babel/runtime/helpers/getPrototypeOf.js");
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_8__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_10___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_10__);
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! @wordpress/api-fetch */ "@wordpress/api-fetch");
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_11___default = /*#__PURE__*/__webpack_require__.n(_wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_11__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! @wordpress/components */ "./node_modules/@wordpress/components/build-module/index.js");
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! @wordpress/compose */ "./node_modules/@wordpress/compose/build-module/index.js");
/* harmony import */ var interpolate_components__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! interpolate-components */ "./node_modules/interpolate-components/lib/index.js");
/* harmony import */ var interpolate_components__WEBPACK_IMPORTED_MODULE_14___default = /*#__PURE__*/__webpack_require__.n(interpolate_components__WEBPACK_IMPORTED_MODULE_14__);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_15___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_15__);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__(/*! @woocommerce/components */ "@woocommerce/components");
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_16___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_components__WEBPACK_IMPORTED_MODULE_16__);
/* harmony import */ var _woocommerce_navigation__WEBPACK_IMPORTED_MODULE_17__ = __webpack_require__(/*! @woocommerce/navigation */ "@woocommerce/navigation");
/* harmony import */ var _woocommerce_navigation__WEBPACK_IMPORTED_MODULE_17___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_17__);
/* harmony import */ var wc_api_constants__WEBPACK_IMPORTED_MODULE_18__ = __webpack_require__(/*! wc-api/constants */ "./client/wc-api/constants.js");
/* harmony import */ var _woocommerce_data__WEBPACK_IMPORTED_MODULE_19__ = __webpack_require__(/*! @woocommerce/data */ "@woocommerce/data");
/* harmony import */ var _woocommerce_data__WEBPACK_IMPORTED_MODULE_19___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_data__WEBPACK_IMPORTED_MODULE_19__);











function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_1___default()(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_8___default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_8___default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_7___default()(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/**
 * External dependencies
 */







/**
 * WooCommerce dependencies
 */






var PayPal = /*#__PURE__*/function (_Component) {
  _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_6___default()(PayPal, _Component);

  var _super = _createSuper(PayPal);

  function PayPal(props) {
    var _this;

    _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_3___default()(this, PayPal);

    _this = _super.call(this, props);
    _this.state = {
      autoConnectFailed: false,
      connectURL: '',
      isPending: false
    };
    _this.updateSettings = _this.updateSettings.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_5___default()(_this));
    return _this;
  }

  _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_4___default()(PayPal, [{
    key: "componentDidMount",
    value: function componentDidMount() {
      var _this$props = this.props,
          createNotice = _this$props.createNotice,
          markConfigured = _this$props.markConfigured;
      var query = Object(_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_17__["getQuery"])(); // Handle redirect back from PayPal

      if (query['paypal-connect']) {
        if (query['paypal-connect'] === '1') {
          createNotice('success', Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_10__["__"])('PayPal connected successfully.', 'woocommerce'));
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
      var _fetchOAuthConnectURL = _babel_runtime_helpers_asyncToGenerator__WEBPACK_IMPORTED_MODULE_2___default()( /*#__PURE__*/regeneratorRuntime.mark(function _callee() {
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
                return _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_11___default()({
                  path: wc_api_constants__WEBPACK_IMPORTED_MODULE_18__["WC_ADMIN_NAMESPACE"] + '/plugins/connect-paypal',
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
      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_12__["Button"], {
        isPrimary: true,
        href: connectURL
      }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_10__["__"])('Connect', 'woocommerce'));
    }
  }, {
    key: "updateSettings",
    value: function () {
      var _updateSettings = _babel_runtime_helpers_asyncToGenerator__WEBPACK_IMPORTED_MODULE_2___default()( /*#__PURE__*/regeneratorRuntime.mark(function _callee2(values) {
        var _this$props2, createNotice, options, updateOptions, markConfigured, update;

        return regeneratorRuntime.wrap(function _callee2$(_context2) {
          while (1) {
            switch (_context2.prev = _context2.next) {
              case 0:
                _this$props2 = this.props, createNotice = _this$props2.createNotice, options = _this$props2.options, updateOptions = _this$props2.updateOptions, markConfigured = _this$props2.markConfigured;
                _context2.next = 3;
                return updateOptions({
                  woocommerce_ppec_paypal_settings: _objectSpread(_objectSpread({}, options.woocommerce_ppec_paypal_settings), {}, {
                    api_username: values.api_username,
                    api_password: values.api_password,
                    enabled: 'yes'
                  })
                });

              case 3:
                update = _context2.sent;

                if (update.success) {
                  createNotice('success', Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_10__["__"])('PayPal connected successfully.', 'woocommerce'));
                  markConfigured('paypal');
                } else {
                  createNotice('error', Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_10__["__"])('There was a problem saving your payment settings.', 'woocommerce'));
                }

              case 5:
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
        errors.api_username = Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_10__["__"])('Please enter your API username', 'woocommerce');
      }

      if (!values.api_password) {
        errors.api_password = Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_10__["__"])('Please enter your API password', 'woocommerce');
      }

      return errors;
    }
  }, {
    key: "renderManualConfig",
    value: function renderManualConfig() {
      var isOptionsUpdating = this.props.isOptionsUpdating;
      var link = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_16__["Link"], {
        href: "https://docs.woocommerce.com/document/paypal-express-checkout/#section-8",
        target: "_blank",
        type: "external"
      });
      var help = interpolate_components__WEBPACK_IMPORTED_MODULE_14___default()({
        mixedString: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_10__["__"])('Your API details can be obtained from your {{link}}PayPal account{{/link}}', 'woocommerce'),
        components: {
          link: link
        }
      });
      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_16__["Form"], {
        initialValues: this.getInitialConfigValues(),
        onSubmitCallback: this.updateSettings,
        validate: this.validate
      }, function (_ref) {
        var getInputProps = _ref.getInputProps,
            handleSubmit = _ref.handleSubmit;
        return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["createElement"])(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["Fragment"], null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_16__["TextControl"], _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0___default()({
          label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_10__["__"])('API Username', 'woocommerce'),
          required: true
        }, getInputProps('api_username'))), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_16__["TextControl"], _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0___default()({
          label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_10__["__"])('API Password', 'woocommerce'),
          required: true
        }, getInputProps('api_password'))), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_12__["Button"], {
          onClick: handleSubmit,
          isPrimary: true,
          isBusy: isOptionsUpdating
        }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_10__["__"])('Proceed', 'woocommerce')), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["createElement"])("p", null, help));
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
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_10__["__"])('Connect your PayPal account', 'woocommerce')
      };

      if (isPending) {
        return connectStep;
      }

      if (!autoConnectFailed && connectURL) {
        return _objectSpread(_objectSpread({}, connectStep), {}, {
          description: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_10__["__"])('A Paypal account is required to process payments. You will be redirected to the Paypal website to create the connection.', 'woocommerce'),
          content: this.renderConnectButton()
        });
      }

      return _objectSpread(_objectSpread({}, connectStep), {}, {
        description: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_10__["__"])('Connect your store to your PayPal account. Don’t have a PayPal account? Create one.', 'woocommerce'),
        content: this.renderManualConfig()
      });
    }
  }, {
    key: "render",
    value: function render() {
      var installStep = this.props.installStep;
      var isPending = this.state.isPending;
      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_16__["Stepper"], {
        isVertical: true,
        isPending: !installStep.isComplete || isPending,
        currentStep: installStep.isComplete ? 'connect' : 'install',
        steps: [installStep, this.getConnectStep()]
      });
    }
  }]);

  return PayPal;
}(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["Component"]);

PayPal.defaultProps = {
  manualConfig: false // WCS is not required for the PayPal OAuth flow, so we can default to smooth connection.

};
/* harmony default export */ __webpack_exports__["default"] = (Object(_wordpress_compose__WEBPACK_IMPORTED_MODULE_13__["compose"])(Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_15__["withSelect"])(function (select) {
  var _select = select(_woocommerce_data__WEBPACK_IMPORTED_MODULE_19__["OPTIONS_STORE_NAME"]),
      getOption = _select.getOption,
      isOptionsUpdating = _select.isOptionsUpdating;

  var _select2 = select(_woocommerce_data__WEBPACK_IMPORTED_MODULE_19__["PLUGINS_STORE_NAME"]),
      getActivePlugins = _select2.getActivePlugins;

  var options = getOption('woocommerce_ppec_paypal_settings');
  var activePlugins = getActivePlugins();
  return {
    activePlugins: activePlugins,
    options: options,
    isOptionsUpdating: isOptionsUpdating()
  };
}), Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_15__["withDispatch"])(function (dispatch) {
  var _dispatch = dispatch('core/notices'),
      createNotice = _dispatch.createNotice;

  var _dispatch2 = dispatch(_woocommerce_data__WEBPACK_IMPORTED_MODULE_19__["OPTIONS_STORE_NAME"]),
      updateOptions = _dispatch2.updateOptions;

  return {
    createNotice: createNotice,
    updateOptions: updateOptions
  };
}))(PayPal));

/***/ }),

/***/ "./client/task-list/tasks/payments/square.js":
/*!***************************************************!*\
  !*** ./client/task-list/tasks/payments/square.js ***!
  \***************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/defineProperty */ "./node_modules/@babel/runtime/helpers/defineProperty.js");
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _babel_runtime_helpers_asyncToGenerator__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/asyncToGenerator */ "./node_modules/@babel/runtime/helpers/asyncToGenerator.js");
/* harmony import */ var _babel_runtime_helpers_asyncToGenerator__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_asyncToGenerator__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @babel/runtime/helpers/classCallCheck */ "./node_modules/@babel/runtime/helpers/classCallCheck.js");
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @babel/runtime/helpers/createClass */ "./node_modules/@babel/runtime/helpers/createClass.js");
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @babel/runtime/helpers/assertThisInitialized */ "./node_modules/@babel/runtime/helpers/assertThisInitialized.js");
/* harmony import */ var _babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @babel/runtime/helpers/inherits */ "./node_modules/@babel/runtime/helpers/inherits.js");
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @babel/runtime/helpers/possibleConstructorReturn */ "./node_modules/@babel/runtime/helpers/possibleConstructorReturn.js");
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @babel/runtime/helpers/getPrototypeOf */ "./node_modules/@babel/runtime/helpers/getPrototypeOf.js");
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_8__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_9__);
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! @wordpress/api-fetch */ "@wordpress/api-fetch");
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_10___default = /*#__PURE__*/__webpack_require__.n(_wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_10__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! @wordpress/components */ "./node_modules/@wordpress/components/build-module/index.js");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_12___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_12__);
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! @wordpress/compose */ "./node_modules/@wordpress/compose/build-module/index.js");
/* harmony import */ var _woocommerce_navigation__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! @woocommerce/navigation */ "@woocommerce/navigation");
/* harmony import */ var _woocommerce_navigation__WEBPACK_IMPORTED_MODULE_14___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_14__);
/* harmony import */ var wc_api_constants__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(/*! wc-api/constants */ "./client/wc-api/constants.js");
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__(/*! @woocommerce/components */ "@woocommerce/components");
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_16___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_components__WEBPACK_IMPORTED_MODULE_16__);
/* harmony import */ var _woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_17__ = __webpack_require__(/*! @woocommerce/wc-admin-settings */ "./client/settings/index.js");
/* harmony import */ var _woocommerce_data__WEBPACK_IMPORTED_MODULE_18__ = __webpack_require__(/*! @woocommerce/data */ "@woocommerce/data");
/* harmony import */ var _woocommerce_data__WEBPACK_IMPORTED_MODULE_18___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_data__WEBPACK_IMPORTED_MODULE_18__);










function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0___default()(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_7___default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_7___default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_6___default()(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/**
 * External dependencies
 */






/**
 * WooCommerce dependencies
 */







var Square = /*#__PURE__*/function (_Component) {
  _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_5___default()(Square, _Component);

  var _super = _createSuper(Square);

  function Square(props) {
    var _this;

    _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_2___default()(this, Square);

    _this = _super.call(this, props);
    _this.state = {
      isPending: false
    };
    _this.connect = _this.connect.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_4___default()(_this));
    return _this;
  }

  _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_3___default()(Square, [{
    key: "componentDidMount",
    value: function componentDidMount() {
      var _this$props = this.props,
          createNotice = _this$props.createNotice,
          markConfigured = _this$props.markConfigured;
      var query = Object(_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_14__["getQuery"])(); // Handle redirect back from Square

      if (query['square-connect']) {
        if (query['square-connect'] === '1') {
          createNotice('success', Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_9__["__"])('Square connected successfully.', 'woocommerce'));
          markConfigured('square');
        }
      }
    }
  }, {
    key: "connect",
    value: function () {
      var _connect = _babel_runtime_helpers_asyncToGenerator__WEBPACK_IMPORTED_MODULE_1___default()( /*#__PURE__*/regeneratorRuntime.mark(function _callee() {
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
                  woocommerce_square_credit_card_settings: _objectSpread(_objectSpread({}, options.woocommerce_square_credit_card_settings), {}, {
                    enabled: 'yes'
                  })
                });
                errorMessage = Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_9__["__"])('There was an error connecting to Square. Please try again or skip to connect later in store settings.', 'woocommerce');
                _context.prev = 4;
                newWindow = null;

                if (hasCbdIndustry) {
                  // It's necessary to declare the new tab before the async call,
                  // otherwise, it won't be possible to open it.
                  newWindow = window.open('/', '_blank');
                }

                _context.next = 9;
                return _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_10___default()({
                  path: wc_api_constants__WEBPACK_IMPORTED_MODULE_15__["WC_ADMIN_NAMESPACE"] + '/plugins/connect-square',
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
        window.location = Object(_woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_17__["getAdminLink"])('admin.php?page=wc-admin');
      } else {
        window.location = connectUrl;
      }
    }
  }, {
    key: "render",
    value: function render() {
      var installStep = this.props.installStep;
      var isPending = this.state.isPending;
      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_8__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_16__["Stepper"], {
        isVertical: true,
        isPending: !installStep.isComplete || isPending,
        currentStep: installStep.isComplete ? 'connect' : 'install',
        steps: [installStep, {
          key: 'connect',
          label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_9__["__"])('Connect your Square account', 'woocommerce'),
          description: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_9__["__"])('A Square account is required to process payments. You will be redirected to the Square website to create the connection.', 'woocommerce'),
          content: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_8__["createElement"])(_wordpress_element__WEBPACK_IMPORTED_MODULE_8__["Fragment"], null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_8__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_11__["Button"], {
            isPrimary: true,
            isBusy: isPending,
            onClick: this.connect
          }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_9__["__"])('Connect', 'woocommerce')))
        }]
      });
    }
  }]);

  return Square;
}(_wordpress_element__WEBPACK_IMPORTED_MODULE_8__["Component"]);

/* harmony default export */ __webpack_exports__["default"] = (Object(_wordpress_compose__WEBPACK_IMPORTED_MODULE_13__["compose"])(Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_12__["withSelect"])(function (select) {
  var _select = select(_woocommerce_data__WEBPACK_IMPORTED_MODULE_18__["OPTIONS_STORE_NAME"]),
      getOption = _select.getOption,
      isResolving = _select.isResolving;

  var options = getOption('woocommerce_square_credit_card_settings');
  var optionsIsRequesting = isResolving('getOption', ['woocommerce_square_credit_card_settings']);
  return {
    options: options,
    optionsIsRequesting: optionsIsRequesting
  };
}), Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_12__["withDispatch"])(function (dispatch) {
  var _dispatch = dispatch('core/notices'),
      createNotice = _dispatch.createNotice;

  var _dispatch2 = dispatch(_woocommerce_data__WEBPACK_IMPORTED_MODULE_18__["OPTIONS_STORE_NAME"]),
      updateOptions = _dispatch2.updateOptions;

  return {
    createNotice: createNotice,
    updateOptions: updateOptions
  };
}))(Square));

/***/ }),

/***/ "./client/task-list/tasks/payments/stripe.js":
/*!***************************************************!*\
  !*** ./client/task-list/tasks/payments/stripe.js ***!
  \***************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/extends */ "./node_modules/@babel/runtime/helpers/extends.js");
/* harmony import */ var _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/defineProperty */ "./node_modules/@babel/runtime/helpers/defineProperty.js");
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _babel_runtime_helpers_asyncToGenerator__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @babel/runtime/helpers/asyncToGenerator */ "./node_modules/@babel/runtime/helpers/asyncToGenerator.js");
/* harmony import */ var _babel_runtime_helpers_asyncToGenerator__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_asyncToGenerator__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @babel/runtime/helpers/classCallCheck */ "./node_modules/@babel/runtime/helpers/classCallCheck.js");
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @babel/runtime/helpers/createClass */ "./node_modules/@babel/runtime/helpers/createClass.js");
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @babel/runtime/helpers/assertThisInitialized */ "./node_modules/@babel/runtime/helpers/assertThisInitialized.js");
/* harmony import */ var _babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @babel/runtime/helpers/inherits */ "./node_modules/@babel/runtime/helpers/inherits.js");
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @babel/runtime/helpers/possibleConstructorReturn */ "./node_modules/@babel/runtime/helpers/possibleConstructorReturn.js");
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! @babel/runtime/helpers/getPrototypeOf */ "./node_modules/@babel/runtime/helpers/getPrototypeOf.js");
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_8__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_10___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_10__);
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! @wordpress/compose */ "./node_modules/@wordpress/compose/build-module/index.js");
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! @wordpress/api-fetch */ "@wordpress/api-fetch");
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_12___default = /*#__PURE__*/__webpack_require__.n(_wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_12__);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_13___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_13__);
/* harmony import */ var interpolate_components__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! interpolate-components */ "./node_modules/interpolate-components/lib/index.js");
/* harmony import */ var interpolate_components__WEBPACK_IMPORTED_MODULE_14___default = /*#__PURE__*/__webpack_require__.n(interpolate_components__WEBPACK_IMPORTED_MODULE_14__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(/*! @wordpress/components */ "./node_modules/@wordpress/components/build-module/index.js");
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__(/*! @woocommerce/components */ "@woocommerce/components");
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_16___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_components__WEBPACK_IMPORTED_MODULE_16__);
/* harmony import */ var _woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_17__ = __webpack_require__(/*! @woocommerce/wc-admin-settings */ "./client/settings/index.js");
/* harmony import */ var _woocommerce_navigation__WEBPACK_IMPORTED_MODULE_18__ = __webpack_require__(/*! @woocommerce/navigation */ "@woocommerce/navigation");
/* harmony import */ var _woocommerce_navigation__WEBPACK_IMPORTED_MODULE_18___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_18__);
/* harmony import */ var wc_api_constants__WEBPACK_IMPORTED_MODULE_19__ = __webpack_require__(/*! wc-api/constants */ "./client/wc-api/constants.js");
/* harmony import */ var _woocommerce_data__WEBPACK_IMPORTED_MODULE_20__ = __webpack_require__(/*! @woocommerce/data */ "@woocommerce/data");
/* harmony import */ var _woocommerce_data__WEBPACK_IMPORTED_MODULE_20___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_data__WEBPACK_IMPORTED_MODULE_20__);











function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_1___default()(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_8___default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_8___default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_7___default()(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/**
 * External dependencies
 */







/**
 * WooCommerce dependencies
 */







var Stripe = /*#__PURE__*/function (_Component) {
  _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_6___default()(Stripe, _Component);

  var _super = _createSuper(Stripe);

  function Stripe(props) {
    var _this;

    _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_3___default()(this, Stripe);

    _this = _super.call(this, props);
    _this.state = {
      oAuthConnectFailed: false,
      connectURL: null,
      isPending: false
    };
    _this.updateSettings = _this.updateSettings.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_5___default()(_this));
    return _this;
  }

  _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_4___default()(Stripe, [{
    key: "componentDidMount",
    value: function componentDidMount() {
      var stripeSettings = this.props.stripeSettings;
      var query = Object(_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_18__["getQuery"])(); // Handle redirect back from Stripe.

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
      var activePlugins = this.props.activePlugins;

      if (!prevProps.activePlugins.includes('woocommerce-gateway-stripe') && activePlugins.includes('woocommerce-gateway-stripe')) {
        this.fetchOAuthConnectURL();
      }
    }
  }, {
    key: "requiresManualConfig",
    value: function requiresManualConfig() {
      var _this$props = this.props,
          activePlugins = _this$props.activePlugins,
          isJetpackConnected = _this$props.isJetpackConnected;
      var oAuthConnectFailed = this.state.oAuthConnectFailed;
      return !isJetpackConnected || !activePlugins.includes('woocommerce-services') || oAuthConnectFailed;
    }
  }, {
    key: "completeMethod",
    value: function completeMethod() {
      var _this$props2 = this.props,
          createNotice = _this$props2.createNotice,
          markConfigured = _this$props2.markConfigured;
      this.setState({
        isPending: false
      });
      createNotice('success', Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_10__["__"])('Stripe connected successfully.', 'woocommerce'));
      markConfigured('stripe');
    }
  }, {
    key: "fetchOAuthConnectURL",
    value: function () {
      var _fetchOAuthConnectURL = _babel_runtime_helpers_asyncToGenerator__WEBPACK_IMPORTED_MODULE_2___default()( /*#__PURE__*/regeneratorRuntime.mark(function _callee() {
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
                return _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_12___default()({
                  path: wc_api_constants__WEBPACK_IMPORTED_MODULE_19__["WCS_NAMESPACE"] + '/connect/stripe/oauth/init',
                  method: 'POST',
                  data: {
                    returnUrl: Object(_woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_17__["getAdminLink"])('admin.php?page=wc-admin&task=payments&method=stripe&stripe-connect=1')
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
      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_15__["Button"], {
        isPrimary: true,
        href: connectURL
      }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_10__["__"])('Connect', 'woocommerce'));
    }
  }, {
    key: "updateSettings",
    value: function () {
      var _updateSettings = _babel_runtime_helpers_asyncToGenerator__WEBPACK_IMPORTED_MODULE_2___default()( /*#__PURE__*/regeneratorRuntime.mark(function _callee2(values) {
        var _this$props3, updateOptions, stripeSettings, createNotice, update;

        return regeneratorRuntime.wrap(function _callee2$(_context2) {
          while (1) {
            switch (_context2.prev = _context2.next) {
              case 0:
                _this$props3 = this.props, updateOptions = _this$props3.updateOptions, stripeSettings = _this$props3.stripeSettings, createNotice = _this$props3.createNotice;
                _context2.next = 3;
                return updateOptions({
                  woocommerce_stripe_settings: _objectSpread(_objectSpread({}, stripeSettings), {}, {
                    publishable_key: values.publishable_key,
                    secret_key: values.secret_key,
                    enabled: 'yes'
                  })
                });

              case 3:
                update = _context2.sent;

                if (update.success) {
                  this.completeMethod();
                } else {
                  createNotice('error', Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_10__["__"])('There was a problem saving your payment setings', 'woocommerce'));
                }

              case 5:
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
        publishable_key: '',
        secret_key: ''
      };
    }
  }, {
    key: "validateManualConfig",
    value: function validateManualConfig(values) {
      var errors = {};

      if (values.publishable_key.match(/^pk_live_/) === null) {
        errors.publishable_key = Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_10__["__"])('Please enter a valid publishable key. Valid keys start with "pk_live".', 'woocommerce');
      }

      if (values.secret_key.match(/^[rs]k_live_/) === null) {
        errors.secret_key = Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_10__["__"])('Please enter a valid secret key. Valid keys start with "sk_live" or "rk_live".', 'woocommerce');
      }

      return errors;
    }
  }, {
    key: "renderManualConfig",
    value: function renderManualConfig() {
      var isOptionsUpdating = this.props.isOptionsUpdating;
      var stripeHelp = interpolate_components__WEBPACK_IMPORTED_MODULE_14___default()({
        mixedString: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_10__["__"])('Your API details can be obtained from your {{docsLink}}Stripe account{{/docsLink}}.  Don’t have a Stripe account? {{registerLink}}Create one.{{/registerLink}}', 'woocommerce'),
        components: {
          docsLink: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_16__["Link"], {
            href: "https://stripe.com/docs/keys",
            target: "_blank",
            type: "external"
          }),
          registerLink: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_16__["Link"], {
            href: "https://dashboard.stripe.com/register",
            target: "_blank",
            type: "external"
          })
        }
      });
      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_16__["Form"], {
        initialValues: this.getInitialConfigValues(),
        onSubmitCallback: this.updateSettings,
        validate: this.validateManualConfig
      }, function (_ref) {
        var getInputProps = _ref.getInputProps,
            handleSubmit = _ref.handleSubmit;
        return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["createElement"])(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["Fragment"], null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_16__["TextControl"], _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0___default()({
          label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_10__["__"])('Live Publishable Key', 'woocommerce'),
          required: true
        }, getInputProps('publishable_key'))), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_16__["TextControl"], _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0___default()({
          label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_10__["__"])('Live Secret Key', 'woocommerce'),
          required: true
        }, getInputProps('secret_key'))), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_15__["Button"], {
          isPrimary: true,
          isBusy: isOptionsUpdating,
          onClick: handleSubmit
        }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_10__["__"])('Proceed', 'woocommerce')), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["createElement"])("p", null, stripeHelp));
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
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_10__["__"])('Connect your Stripe account', 'woocommerce')
      };

      if (isPending) {
        return connectStep;
      }

      if (!oAuthConnectFailed && connectURL) {
        return _objectSpread(_objectSpread({}, connectStep), {}, {
          description: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_10__["__"])('A Stripe account is required to process payments.', 'woocommerce'),
          content: this.renderConnectButton()
        });
      }

      return _objectSpread(_objectSpread({}, connectStep), {}, {
        description: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_10__["__"])('Connect your store to your Stripe account. Don’t have a Stripe account? Create one.', 'woocommerce'),
        content: this.renderManualConfig()
      });
    }
  }, {
    key: "render",
    value: function render() {
      var _this$props4 = this.props,
          installStep = _this$props4.installStep,
          isOptionsUpdating = _this$props4.isOptionsUpdating;
      var isPending = this.state.isPending;
      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_16__["Stepper"], {
        isVertical: true,
        isPending: !installStep.isComplete || isOptionsUpdating || isPending,
        currentStep: installStep.isComplete ? 'connect' : 'install',
        steps: [installStep, this.getConnectStep()]
      });
    }
  }]);

  return Stripe;
}(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["Component"]);

/* harmony default export */ __webpack_exports__["default"] = (Object(_wordpress_compose__WEBPACK_IMPORTED_MODULE_11__["compose"])(Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_13__["withSelect"])(function (select) {
  var _select = select(_woocommerce_data__WEBPACK_IMPORTED_MODULE_20__["OPTIONS_STORE_NAME"]),
      getOption = _select.getOption,
      isOptionsUpdating = _select.isOptionsUpdating;

  var _select2 = select(_woocommerce_data__WEBPACK_IMPORTED_MODULE_20__["PLUGINS_STORE_NAME"]),
      getActivePlugins = _select2.getActivePlugins,
      isJetpackConnected = _select2.isJetpackConnected;

  return {
    activePlugins: getActivePlugins(),
    isJetpackConnected: isJetpackConnected(),
    isOptionsUpdating: isOptionsUpdating(),
    stripeSettings: getOption('woocommerce_stripe_settings') || []
  };
}), Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_13__["withDispatch"])(function (dispatch) {
  var _dispatch = dispatch('core/notices'),
      createNotice = _dispatch.createNotice;

  var _dispatch2 = dispatch(_woocommerce_data__WEBPACK_IMPORTED_MODULE_20__["OPTIONS_STORE_NAME"]),
      updateOptions = _dispatch2.updateOptions;

  return {
    createNotice: createNotice,
    updateOptions: updateOptions
  };
}))(Stripe));

/***/ }),

/***/ "./client/task-list/tasks/payments/wcpay.js":
/*!**************************************************!*\
  !*** ./client/task-list/tasks/payments/wcpay.js ***!
  \**************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/classCallCheck */ "./node_modules/@babel/runtime/helpers/classCallCheck.js");
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/createClass */ "./node_modules/@babel/runtime/helpers/createClass.js");
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @babel/runtime/helpers/inherits */ "./node_modules/@babel/runtime/helpers/inherits.js");
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @babel/runtime/helpers/possibleConstructorReturn */ "./node_modules/@babel/runtime/helpers/possibleConstructorReturn.js");
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @babel/runtime/helpers/getPrototypeOf */ "./node_modules/@babel/runtime/helpers/getPrototypeOf.js");
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var _woocommerce_navigation__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! @woocommerce/navigation */ "@woocommerce/navigation");
/* harmony import */ var _woocommerce_navigation__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_8__);






function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_4___default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_4___default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_3___default()(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/**
 * External dependencies
 */



/**
 * WooCommerce dependencies
 */



var WCPay = /*#__PURE__*/function (_Component) {
  _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_2___default()(WCPay, _Component);

  var _super = _createSuper(WCPay);

  function WCPay() {
    _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0___default()(this, WCPay);

    return _super.apply(this, arguments);
  }

  _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1___default()(WCPay, [{
    key: "componentDidMount",
    value: function componentDidMount() {
      var _this$props = this.props,
          createNotice = _this$props.createNotice,
          markConfigured = _this$props.markConfigured;
      var query = Object(_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_8__["getQuery"])(); // Handle redirect back from WCPay on-boarding

      if (query['wcpay-connection-success']) {
        createNotice('success', Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_5__["__"])('WooCommerce Payments connected successfully.', 'woocommerce'));
        markConfigured('wcpay');
      }
    }
  }, {
    key: "render",
    value: function render() {
      return null;
    }
  }]);

  return WCPay;
}(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["Component"]);

/* harmony default export */ __webpack_exports__["default"] = (Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_7__["withDispatch"])(function (dispatch) {
  var _dispatch = dispatch('core/notices'),
      createNotice = _dispatch.createNotice;

  return {
    createNotice: createNotice
  };
})(WCPay));

/***/ }),

/***/ "./client/task-list/tasks/products.js":
/*!********************************************!*\
  !*** ./client/task-list/tasks/products.js ***!
  \********************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return Products; });
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/classCallCheck */ "./node_modules/@babel/runtime/helpers/classCallCheck.js");
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/createClass */ "./node_modules/@babel/runtime/helpers/createClass.js");
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @babel/runtime/helpers/inherits */ "./node_modules/@babel/runtime/helpers/inherits.js");
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @babel/runtime/helpers/possibleConstructorReturn */ "./node_modules/@babel/runtime/helpers/possibleConstructorReturn.js");
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @babel/runtime/helpers/getPrototypeOf */ "./node_modules/@babel/runtime/helpers/getPrototypeOf.js");
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @woocommerce/components */ "@woocommerce/components");
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_components__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var _woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! @woocommerce/wc-admin-settings */ "./client/settings/index.js");
/* harmony import */ var lib_tracks__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! lib/tracks */ "./client/lib/tracks.js");







function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_4___default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_4___default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_3___default()(this, result); }; }

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


var subTasks = [{
  title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__["__"])('Add manually (recommended)', 'woocommerce'),
  content: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__["__"])('For small stores we recommend adding products manually', 'woocommerce'),
  before: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])("i", {
    className: "material-icons-outlined"
  }, "add_box"),
  after: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])("i", {
    className: "material-icons-outlined"
  }, "chevron_right"),
  onClick: function onClick() {
    return Object(lib_tracks__WEBPACK_IMPORTED_MODULE_9__["recordEvent"])('tasklist_add_product', {
      method: 'manually'
    });
  },
  href: Object(_woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_8__["getAdminLink"])('post-new.php?post_type=product&wc_onboarding_active_task=products&tutorial=true')
}, {
  title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__["__"])('Import', 'woocommerce'),
  content: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__["__"])('For larger stores we recommend importing all products at once via CSV file', 'woocommerce'),
  before: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])("i", {
    className: "material-icons-outlined"
  }, "import_export"),
  after: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])("i", {
    className: "material-icons-outlined"
  }, "chevron_right"),
  onClick: function onClick() {
    return Object(lib_tracks__WEBPACK_IMPORTED_MODULE_9__["recordEvent"])('tasklist_add_product', {
      method: 'import'
    });
  },
  href: Object(_woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_8__["getAdminLink"])('edit.php?post_type=product&page=product_importer&wc_onboarding_active_task=product-import')
}, {
  title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__["__"])('Migrate', 'woocommerce'),
  content: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__["__"])('For stores currently selling elsewhere we suggest using a product migration service', 'woocommerce'),
  before: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])("i", {
    className: "material-icons-outlined"
  }, "cloud_download"),
  after: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])("i", {
    className: "material-icons-outlined"
  }, "chevron_right"),
  onClick: function onClick() {
    return Object(lib_tracks__WEBPACK_IMPORTED_MODULE_9__["recordEvent"])('tasklist_add_product', {
      method: 'migrate'
    });
  },
  // @todo This should be replaced with the in-app purchase iframe when ready.
  href: 'https://woocommerce.com/products/cart2cart/',
  target: '_blank'
}];

var Products = /*#__PURE__*/function (_Component) {
  _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_2___default()(Products, _Component);

  var _super = _createSuper(Products);

  function Products() {
    _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0___default()(this, Products);

    return _super.apply(this, arguments);
  }

  _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1___default()(Products, [{
    key: "render",
    value: function render() {
      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["Fragment"], null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_7__["Card"], {
        className: "woocommerce-task-card"
      }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_7__["List"], {
        items: subTasks
      })));
    }
  }]);

  return Products;
}(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["Component"]);



/***/ }),

/***/ "./client/task-list/tasks/shipping/index.js":
/*!**************************************************!*\
  !*** ./client/task-list/tasks/shipping/index.js ***!
  \**************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/extends */ "./node_modules/@babel/runtime/helpers/extends.js");
/* harmony import */ var _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _babel_runtime_helpers_asyncToGenerator__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/asyncToGenerator */ "./node_modules/@babel/runtime/helpers/asyncToGenerator.js");
/* harmony import */ var _babel_runtime_helpers_asyncToGenerator__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_asyncToGenerator__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @babel/runtime/helpers/classCallCheck */ "./node_modules/@babel/runtime/helpers/classCallCheck.js");
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @babel/runtime/helpers/createClass */ "./node_modules/@babel/runtime/helpers/createClass.js");
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @babel/runtime/helpers/assertThisInitialized */ "./node_modules/@babel/runtime/helpers/assertThisInitialized.js");
/* harmony import */ var _babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @babel/runtime/helpers/inherits */ "./node_modules/@babel/runtime/helpers/inherits.js");
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @babel/runtime/helpers/possibleConstructorReturn */ "./node_modules/@babel/runtime/helpers/possibleConstructorReturn.js");
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @babel/runtime/helpers/getPrototypeOf */ "./node_modules/@babel/runtime/helpers/getPrototypeOf.js");
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_8__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_9__);
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! @wordpress/api-fetch */ "@wordpress/api-fetch");
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_10___default = /*#__PURE__*/__webpack_require__.n(_wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_10__);
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! @wordpress/compose */ "./node_modules/@wordpress/compose/build-module/index.js");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! lodash */ "lodash");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_12___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_12__);
/* harmony import */ var interpolate_components__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! interpolate-components */ "./node_modules/interpolate-components/lib/index.js");
/* harmony import */ var interpolate_components__WEBPACK_IMPORTED_MODULE_13___default = /*#__PURE__*/__webpack_require__.n(interpolate_components__WEBPACK_IMPORTED_MODULE_13__);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_14___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_14__);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(/*! @woocommerce/components */ "@woocommerce/components");
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_15___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_components__WEBPACK_IMPORTED_MODULE_15__);
/* harmony import */ var _woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__(/*! @woocommerce/wc-admin-settings */ "./client/settings/index.js");
/* harmony import */ var _woocommerce_navigation__WEBPACK_IMPORTED_MODULE_17__ = __webpack_require__(/*! @woocommerce/navigation */ "@woocommerce/navigation");
/* harmony import */ var _woocommerce_navigation__WEBPACK_IMPORTED_MODULE_17___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_17__);
/* harmony import */ var _woocommerce_data__WEBPACK_IMPORTED_MODULE_18__ = __webpack_require__(/*! @woocommerce/data */ "@woocommerce/data");
/* harmony import */ var _woocommerce_data__WEBPACK_IMPORTED_MODULE_18___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_data__WEBPACK_IMPORTED_MODULE_18__);
/* harmony import */ var dashboard_components_connect__WEBPACK_IMPORTED_MODULE_19__ = __webpack_require__(/*! dashboard/components/connect */ "./client/dashboard/components/connect/index.js");
/* harmony import */ var dashboard_utils__WEBPACK_IMPORTED_MODULE_20__ = __webpack_require__(/*! dashboard/utils */ "./client/dashboard/utils.js");
/* harmony import */ var _steps_location__WEBPACK_IMPORTED_MODULE_21__ = __webpack_require__(/*! ../steps/location */ "./client/task-list/tasks/steps/location.js");
/* harmony import */ var _rates__WEBPACK_IMPORTED_MODULE_22__ = __webpack_require__(/*! ./rates */ "./client/task-list/tasks/shipping/rates.js");
/* harmony import */ var lib_tracks__WEBPACK_IMPORTED_MODULE_23__ = __webpack_require__(/*! lib/tracks */ "./client/lib/tracks.js");










function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_7___default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_7___default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_6___default()(this, result); }; }

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







var Shipping = /*#__PURE__*/function (_Component) {
  _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_5___default()(Shipping, _Component);

  var _super = _createSuper(Shipping);

  function Shipping(props) {
    var _this;

    _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_2___default()(this, Shipping);

    _this = _super.call(this, props);
    _this.initialState = {
      isPending: false,
      step: 'store_location',
      shippingZones: []
    }; // Cache active plugins to prevent removal mid-step.

    _this.activePlugins = props.activePlugins;
    _this.state = _this.initialState;
    _this.completeStep = _this.completeStep.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_4___default()(_this));
    return _this;
  }

  _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_3___default()(Shipping, [{
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
      var _fetchShippingZones = _babel_runtime_helpers_asyncToGenerator__WEBPACK_IMPORTED_MODULE_1___default()( /*#__PURE__*/regeneratorRuntime.mark(function _callee2() {
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
                return _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_10___default()({
                  path: '/wc/v3/shipping/zones'
                });

              case 5:
                zones = _context2.sent;
                hasCountryZone = false;
                _context2.next = 9;
                return Promise.all(zones.map( /*#__PURE__*/function () {
                  var _ref = _babel_runtime_helpers_asyncToGenerator__WEBPACK_IMPORTED_MODULE_1___default()( /*#__PURE__*/regeneratorRuntime.mark(function _callee(zone) {
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
                            return _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_10___default()({
                              path: "/wc/v3/shipping/zones/".concat(zone.id, "/methods")
                            });

                          case 3:
                            zone.methods = _context.sent;
                            zone.name = Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_9__["__"])('Rest of the world', 'woocommerce');
                            zone.toggleable = true;
                            shippingZones.push(zone);
                            return _context.abrupt("return");

                          case 8:
                            _context.next = 10;
                            return _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_10___default()({
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
                            return _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_10___default()({
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
                return _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_10___default()({
                  method: 'POST',
                  path: '/wc/v3/shipping/zones',
                  data: {
                    name: countryName
                  }
                });

              case 12:
                zone = _context2.sent;
                _context2.next = 15;
                return _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_10___default()({
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
      var _this$props2 = this.props,
          countryCode = _this$props2.countryCode,
          settings = _this$props2.settings;
      var storeAddress = settings.woocommerce_store_address,
          defaultCountry = settings.woocommerce_default_country,
          storePostCode = settings.woocommerce_store_postcode;
      var step = this.state.step;

      if (step === 'rates' && (prevProps.countryCode !== countryCode || prevState.step !== 'rates')) {
        this.fetchShippingZones();
      }

      var isCompleteAddress = Boolean(storeAddress && defaultCountry && storePostCode);

      if (step === 'store_location' && isCompleteAddress) {
        this.completeStep();
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
        createNotice('success', Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_9__["__"])("📦 Shipping is done! Don't worry, you can always change it later.", 'woocommerce'));
        Object(_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_17__["getHistory"])().push(Object(_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_17__["getNewPath"])({}, '/', {}));
      }
    }
  }, {
    key: "getPluginsToActivate",
    value: function getPluginsToActivate() {
      var countryCode = this.props.countryCode;
      var plugins = [];

      if (['GB', 'CA', 'AU'].includes(countryCode)) {
        plugins.push('woocommerce-shipstation-integration');
      } else if (countryCode === 'US') {
        plugins.push('woocommerce-services');
        plugins.push('jetpack');
      }

      return Object(lodash__WEBPACK_IMPORTED_MODULE_12__["difference"])(plugins, this.activePlugins);
    }
  }, {
    key: "getSteps",
    value: function getSteps() {
      var _this2 = this;

      var _this$props3 = this.props,
          countryCode = _this$props3.countryCode,
          isJetpackConnected = _this$props3.isJetpackConnected;
      var pluginsToActivate = this.getPluginsToActivate();
      var requiresJetpackConnection = !isJetpackConnected && countryCode === 'US';
      var steps = [{
        key: 'store_location',
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_9__["__"])('Set store location', 'woocommerce'),
        description: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_9__["__"])('The address from which your business operates', 'woocommerce'),
        content: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_8__["createElement"])(_steps_location__WEBPACK_IMPORTED_MODULE_21__["default"], _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0___default()({}, this.props, {
          onComplete: function onComplete(values) {
            var country = Object(dashboard_utils__WEBPACK_IMPORTED_MODULE_20__["getCountryCode"])(values.countryState);
            Object(lib_tracks__WEBPACK_IMPORTED_MODULE_23__["recordEvent"])('tasklist_shipping_set_location', {
              country: country
            });

            _this2.completeStep();
          }
        })),
        visible: true
      }, {
        key: 'rates',
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_9__["__"])('Set shipping costs', 'woocommerce'),
        description: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_9__["__"])('Define how much customers pay to ship to different destinations', 'woocommerce'),
        content: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_8__["createElement"])(_rates__WEBPACK_IMPORTED_MODULE_22__["default"], _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0___default()({
          buttonText: pluginsToActivate.length || requiresJetpackConnection ? Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_9__["__"])('Proceed', 'woocommerce') : Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_9__["__"])('Complete task', 'woocommerce'),
          shippingZones: this.state.shippingZones,
          onComplete: this.completeStep
        }, this.props)),
        visible: true
      }, {
        key: 'label_printing',
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_9__["__"])('Enable shipping label printing', 'woocommerce'),
        description: pluginsToActivate.includes('woocommerce-shipstation-integration') ? interpolate_components__WEBPACK_IMPORTED_MODULE_13___default()({
          mixedString: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_9__["__"])('We recommend using ShipStation to save time at the post office by printing your shipping ' + 'labels at home. Try ShipStation free for 30 days. {{link}}Learn more{{/link}}.', 'woocommerce'),
          components: {
            link: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_8__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_15__["Link"], {
              href: "https://woocommerce.com/products/shipstation-integration",
              target: "_blank",
              type: "external"
            })
          }
        }) : Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_9__["__"])('With WooCommerce Services and Jetpack you can save time at the ' + 'Post Office by printing your shipping labels at home', 'woocommerce'),
        content: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_8__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_15__["Plugins"], _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0___default()({
          onComplete: function onComplete() {
            Object(lib_tracks__WEBPACK_IMPORTED_MODULE_23__["recordEvent"])('tasklist_shipping_label_printing', {
              install: true,
              pluginsToActivate: pluginsToActivate
            });

            _this2.completeStep();
          },
          onSkip: function onSkip() {
            Object(lib_tracks__WEBPACK_IMPORTED_MODULE_23__["recordEvent"])('tasklist_shipping_label_printing', {
              install: false,
              pluginsToActivate: pluginsToActivate
            });
            Object(_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_17__["getHistory"])().push(Object(_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_17__["getNewPath"])({}, '/', {}));
          },
          pluginSlugs: pluginsToActivate
        }, this.props)),
        visible: pluginsToActivate.length
      }, {
        key: 'connect',
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_9__["__"])('Connect your store', 'woocommerce'),
        description: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_9__["__"])('Connect your store to WordPress.com to enable label printing', 'woocommerce'),
        content: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_8__["createElement"])(dashboard_components_connect__WEBPACK_IMPORTED_MODULE_19__["default"], _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0___default()({
          redirectUrl: Object(_woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_16__["getAdminLink"])('admin.php?page=wc-admin'),
          completeStep: this.completeStep
        }, this.props, {
          onConnect: function onConnect() {
            Object(lib_tracks__WEBPACK_IMPORTED_MODULE_23__["recordEvent"])('tasklist_shipping_connect_store');
          }
        })),
        visible: requiresJetpackConnection
      }];
      return Object(lodash__WEBPACK_IMPORTED_MODULE_12__["filter"])(steps, function (step) {
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
      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_8__["createElement"])("div", {
        className: "woocommerce-task-shipping"
      }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_8__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_15__["Card"], {
        className: "is-narrow"
      }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_8__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_15__["Stepper"], {
        isPending: isPending || isSettingsRequesting,
        isVertical: true,
        currentStep: step,
        steps: this.getSteps()
      })));
    }
  }]);

  return Shipping;
}(_wordpress_element__WEBPACK_IMPORTED_MODULE_8__["Component"]);

/* harmony default export */ __webpack_exports__["default"] = (Object(_wordpress_compose__WEBPACK_IMPORTED_MODULE_11__["compose"])(Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_14__["withSelect"])(function (select) {
  var _select = select(_woocommerce_data__WEBPACK_IMPORTED_MODULE_18__["SETTINGS_STORE_NAME"]),
      getSettings = _select.getSettings,
      getSettingsError = _select.getSettingsError,
      isGetSettingsRequesting = _select.isGetSettingsRequesting;

  var _select2 = select(_woocommerce_data__WEBPACK_IMPORTED_MODULE_18__["PLUGINS_STORE_NAME"]),
      getActivePlugins = _select2.getActivePlugins,
      isJetpackConnected = _select2.isJetpackConnected;

  var _getSettings = getSettings('general'),
      _getSettings$general = _getSettings.general,
      settings = _getSettings$general === void 0 ? {} : _getSettings$general;

  var isSettingsError = Boolean(getSettingsError('general'));
  var isSettingsRequesting = isGetSettingsRequesting('general');
  var countryCode = Object(dashboard_utils__WEBPACK_IMPORTED_MODULE_20__["getCountryCode"])(settings.woocommerce_default_country);

  var _getSetting = Object(_woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_16__["getSetting"])('dataEndpoints', {}),
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
}), Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_14__["withDispatch"])(function (dispatch) {
  var _dispatch = dispatch('core/notices'),
      createNotice = _dispatch.createNotice;

  var _dispatch2 = dispatch(_woocommerce_data__WEBPACK_IMPORTED_MODULE_18__["SETTINGS_STORE_NAME"]),
      updateAndPersistSettingsForGroup = _dispatch2.updateAndPersistSettingsForGroup;

  return {
    createNotice: createNotice,
    updateAndPersistSettingsForGroup: updateAndPersistSettingsForGroup
  };
}))(Shipping));

/***/ }),

/***/ "./client/task-list/tasks/shipping/rates.js":
/*!**************************************************!*\
  !*** ./client/task-list/tasks/shipping/rates.js ***!
  \**************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/extends */ "./node_modules/@babel/runtime/helpers/extends.js");
/* harmony import */ var _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/defineProperty */ "./node_modules/@babel/runtime/helpers/defineProperty.js");
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _babel_runtime_helpers_asyncToGenerator__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @babel/runtime/helpers/asyncToGenerator */ "./node_modules/@babel/runtime/helpers/asyncToGenerator.js");
/* harmony import */ var _babel_runtime_helpers_asyncToGenerator__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_asyncToGenerator__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @babel/runtime/helpers/classCallCheck */ "./node_modules/@babel/runtime/helpers/classCallCheck.js");
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @babel/runtime/helpers/createClass */ "./node_modules/@babel/runtime/helpers/createClass.js");
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @babel/runtime/helpers/assertThisInitialized */ "./node_modules/@babel/runtime/helpers/assertThisInitialized.js");
/* harmony import */ var _babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @babel/runtime/helpers/inherits */ "./node_modules/@babel/runtime/helpers/inherits.js");
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @babel/runtime/helpers/possibleConstructorReturn */ "./node_modules/@babel/runtime/helpers/possibleConstructorReturn.js");
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! @babel/runtime/helpers/getPrototypeOf */ "./node_modules/@babel/runtime/helpers/getPrototypeOf.js");
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_8__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_10___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_10__);
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! @wordpress/api-fetch */ "@wordpress/api-fetch");
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_11___default = /*#__PURE__*/__webpack_require__.n(_wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_11__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! @wordpress/components */ "./node_modules/@wordpress/components/build-module/index.js");
/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! prop-types */ "./node_modules/prop-types/index.js");
/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_13___default = /*#__PURE__*/__webpack_require__.n(prop_types__WEBPACK_IMPORTED_MODULE_13__);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! @woocommerce/components */ "@woocommerce/components");
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_14___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_components__WEBPACK_IMPORTED_MODULE_14__);
/* harmony import */ var _woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(/*! @woocommerce/wc-admin-settings */ "./client/settings/index.js");
/* harmony import */ var lib_tracks__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__(/*! lib/tracks */ "./client/lib/tracks.js");
/* harmony import */ var lib_currency_context__WEBPACK_IMPORTED_MODULE_17__ = __webpack_require__(/*! lib/currency-context */ "./client/lib/currency-context.js");











function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_1___default()(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_8___default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_8___default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_7___default()(this, result); }; }

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




var ShippingRates = /*#__PURE__*/function (_Component) {
  _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_6___default()(ShippingRates, _Component);

  var _super = _createSuper(ShippingRates);

  function ShippingRates() {
    var _this;

    _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_3___default()(this, ShippingRates);

    _this = _super.apply(this, arguments);
    _this.updateShippingZones = _this.updateShippingZones.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_5___default()(_this));
    return _this;
  }

  _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_4___default()(ShippingRates, [{
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
        _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_11___default()({
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
      var _updateShippingZones = _babel_runtime_helpers_asyncToGenerator__WEBPACK_IMPORTED_MODULE_2___default()( /*#__PURE__*/regeneratorRuntime.mark(function _callee(values) {
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

                  _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_11___default()({
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
                Object(lib_tracks__WEBPACK_IMPORTED_MODULE_16__["recordEvent"])('tasklist_shipping_set_costs', {
                  shipping_cost: shippingCost,
                  rest_world: restOfTheWorld
                }); // @todo This is a workaround to force the task to mark as complete.
                // This should probably be updated to use wc-api so we can fetch shipping methods.

                Object(_woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_15__["setSetting"])('onboarding', _objectSpread(_objectSpread({}, Object(_woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_15__["getSetting"])('onboarding', {})), {}, {
                  shippingZonesCount: 1
                }));
                createNotice('success', Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_10__["__"])('Your shipping rates have been updated.', 'woocommerce'));
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

      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["createElement"])("span", {
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
        return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["createElement"])("span", {
          className: "woocommerce-shipping-rate__control-suffix"
        }, symbol);
      }

      return parseFloat(rate) === parseFloat(0) ? Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["createElement"])("span", {
        className: "woocommerce-shipping-rate__control-suffix"
      }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_10__["__"])('Free shipping', 'woocommerce')) : null;
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
          errors[rate] = Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_10__["__"])('Shipping rates can not be negative numbers.', 'woocommerce');
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

      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_14__["Form"], {
        initialValues: this.getInitialValues(),
        onSubmitCallback: this.updateShippingZones,
        validate: this.validate
      }, function (_ref) {
        var getInputProps = _ref.getInputProps,
            handleSubmit = _ref.handleSubmit,
            setTouched = _ref.setTouched,
            setValue = _ref.setValue,
            values = _ref.values;
        return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["createElement"])(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["Fragment"], null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["createElement"])("div", {
          className: "woocommerce-shipping-rates"
        }, shippingZones.map(function (zone) {
          return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["createElement"])("div", {
            className: "woocommerce-shipping-rate",
            key: zone.id
          }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["createElement"])("div", {
            className: "woocommerce-shipping-rate__icon"
          }, zone.locations ? zone.locations.map(function (location) {
            return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_14__["Flag"], {
              size: 24,
              code: location.code,
              key: location.code
            });
          }) : // Icon used for zones without locations or "Rest of the world".
          Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["createElement"])("i", {
            className: "material-icons-outlined"
          }, "public")), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["createElement"])("div", {
            className: "woocommerce-shipping-rate__main"
          }, zone.toggleable ? Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["createElement"])("label", {
            htmlFor: "woocommerce-shipping-rate__toggle-".concat(zone.id),
            className: "woocommerce-shipping-rate__name"
          }, zone.name, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_12__["FormToggle"], _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0___default()({
            id: "woocommerce-shipping-rate__toggle-".concat(zone.id)
          }, getInputProps("".concat(zone.id, "_enabled"))))) : Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["createElement"])("div", {
            className: "woocommerce-shipping-rate__name"
          }, zone.name), (!zone.toggleable || values["".concat(zone.id, "_enabled")]) && Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_14__["TextControlWithAffixes"], _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0___default()({
            label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_10__["__"])('Shipping cost', 'woocommerce'),
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
        })), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_12__["Button"], {
          isPrimary: true,
          onClick: handleSubmit
        }, buttonText || Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_10__["__"])('Update', 'woocommerce')));
      });
    }
  }]);

  return ShippingRates;
}(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["Component"]);

ShippingRates.propTypes = {
  /**
   * Text displayed on the primary button.
   */
  buttonText: prop_types__WEBPACK_IMPORTED_MODULE_13___default.a.string,

  /**
   * Function used to mark the step complete.
   */
  onComplete: prop_types__WEBPACK_IMPORTED_MODULE_13___default.a.func.isRequired,

  /**
   * Function to create a transient notice in the store.
   */
  createNotice: prop_types__WEBPACK_IMPORTED_MODULE_13___default.a.func.isRequired,

  /**
   * Array of shipping zones returned from the WC REST API with added
   * `methods` and `locations` properties appended from separate API calls.
   */
  shippingZones: prop_types__WEBPACK_IMPORTED_MODULE_13___default.a.array
};
ShippingRates.defaultProps = {
  shippingZones: []
};
ShippingRates.contextType = lib_currency_context__WEBPACK_IMPORTED_MODULE_17__["CurrencyContext"];
/* harmony default export */ __webpack_exports__["default"] = (ShippingRates);

/***/ }),

/***/ "./client/task-list/tasks/steps/location.js":
/*!**************************************************!*\
  !*** ./client/task-list/tasks/steps/location.js ***!
  \**************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return StoreLocation; });
/* harmony import */ var _babel_runtime_helpers_asyncToGenerator__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/asyncToGenerator */ "./node_modules/@babel/runtime/helpers/asyncToGenerator.js");
/* harmony import */ var _babel_runtime_helpers_asyncToGenerator__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_asyncToGenerator__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/classCallCheck */ "./node_modules/@babel/runtime/helpers/classCallCheck.js");
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @babel/runtime/helpers/createClass */ "./node_modules/@babel/runtime/helpers/createClass.js");
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @babel/runtime/helpers/assertThisInitialized */ "./node_modules/@babel/runtime/helpers/assertThisInitialized.js");
/* harmony import */ var _babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @babel/runtime/helpers/inherits */ "./node_modules/@babel/runtime/helpers/inherits.js");
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @babel/runtime/helpers/possibleConstructorReturn */ "./node_modules/@babel/runtime/helpers/possibleConstructorReturn.js");
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @babel/runtime/helpers/getPrototypeOf */ "./node_modules/@babel/runtime/helpers/getPrototypeOf.js");
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_8__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! @wordpress/components */ "./node_modules/@wordpress/components/build-module/index.js");
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! @woocommerce/components */ "@woocommerce/components");
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_10___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_components__WEBPACK_IMPORTED_MODULE_10__);
/* harmony import */ var dashboard_components_settings_general_store_address__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! dashboard/components/settings/general/store-address */ "./client/dashboard/components/settings/general/store-address.js");









function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_6___default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_6___default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_5___default()(this, result); }; }

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



var StoreLocation = /*#__PURE__*/function (_Component) {
  _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_4___default()(StoreLocation, _Component);

  var _super = _createSuper(StoreLocation);

  function StoreLocation() {
    var _this;

    _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_1___default()(this, StoreLocation);

    _this = _super.apply(this, arguments);
    _this.onSubmit = _this.onSubmit.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_3___default()(_this));
    return _this;
  }

  _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_2___default()(StoreLocation, [{
    key: "onSubmit",
    value: function () {
      var _onSubmit = _babel_runtime_helpers_asyncToGenerator__WEBPACK_IMPORTED_MODULE_0___default()( /*#__PURE__*/regeneratorRuntime.mark(function _callee(values) {
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
                  createNotice('error', Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_8__["__"])('There was a problem saving your store location.', 'woocommerce'));
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

      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_10__["Form"], {
        initialValues: this.getInitialValues(),
        onSubmitCallback: this.onSubmit,
        validate: dashboard_components_settings_general_store_address__WEBPACK_IMPORTED_MODULE_11__["validateStoreAddress"]
      }, function (_ref) {
        var getInputProps = _ref.getInputProps,
            handleSubmit = _ref.handleSubmit,
            setValue = _ref.setValue;
        return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["Fragment"], null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])(dashboard_components_settings_general_store_address__WEBPACK_IMPORTED_MODULE_11__["StoreAddress"], {
          getInputProps: getInputProps,
          setValue: setValue
        }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_9__["Button"], {
          isPrimary: true,
          onClick: handleSubmit
        }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_8__["__"])('Continue', 'woocommerce')));
      });
    }
  }]);

  return StoreLocation;
}(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["Component"]);



/***/ }),

/***/ "./client/task-list/tasks/tax.js":
/*!***************************************!*\
  !*** ./client/task-list/tasks/tax.js ***!
  \***************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/extends */ "./node_modules/@babel/runtime/helpers/extends.js");
/* harmony import */ var _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/defineProperty */ "./node_modules/@babel/runtime/helpers/defineProperty.js");
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _babel_runtime_helpers_asyncToGenerator__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @babel/runtime/helpers/asyncToGenerator */ "./node_modules/@babel/runtime/helpers/asyncToGenerator.js");
/* harmony import */ var _babel_runtime_helpers_asyncToGenerator__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_asyncToGenerator__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @babel/runtime/helpers/classCallCheck */ "./node_modules/@babel/runtime/helpers/classCallCheck.js");
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @babel/runtime/helpers/createClass */ "./node_modules/@babel/runtime/helpers/createClass.js");
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @babel/runtime/helpers/assertThisInitialized */ "./node_modules/@babel/runtime/helpers/assertThisInitialized.js");
/* harmony import */ var _babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @babel/runtime/helpers/inherits */ "./node_modules/@babel/runtime/helpers/inherits.js");
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @babel/runtime/helpers/possibleConstructorReturn */ "./node_modules/@babel/runtime/helpers/possibleConstructorReturn.js");
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! @babel/runtime/helpers/getPrototypeOf */ "./node_modules/@babel/runtime/helpers/getPrototypeOf.js");
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_8__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_10___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_10__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! @wordpress/components */ "./node_modules/@wordpress/components/build-module/index.js");
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! @wordpress/compose */ "./node_modules/@wordpress/compose/build-module/index.js");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! lodash */ "lodash");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_13___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_13__);
/* harmony import */ var interpolate_components__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! interpolate-components */ "./node_modules/interpolate-components/lib/index.js");
/* harmony import */ var interpolate_components__WEBPACK_IMPORTED_MODULE_14___default = /*#__PURE__*/__webpack_require__.n(interpolate_components__WEBPACK_IMPORTED_MODULE_14__);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_15___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_15__);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__(/*! @woocommerce/components */ "@woocommerce/components");
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_16___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_components__WEBPACK_IMPORTED_MODULE_16__);
/* harmony import */ var _woocommerce_navigation__WEBPACK_IMPORTED_MODULE_17__ = __webpack_require__(/*! @woocommerce/navigation */ "@woocommerce/navigation");
/* harmony import */ var _woocommerce_navigation__WEBPACK_IMPORTED_MODULE_17___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_17__);
/* harmony import */ var _woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_18__ = __webpack_require__(/*! @woocommerce/wc-admin-settings */ "./client/settings/index.js");
/* harmony import */ var _woocommerce_data__WEBPACK_IMPORTED_MODULE_19__ = __webpack_require__(/*! @woocommerce/data */ "@woocommerce/data");
/* harmony import */ var _woocommerce_data__WEBPACK_IMPORTED_MODULE_19___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_data__WEBPACK_IMPORTED_MODULE_19__);
/* harmony import */ var dashboard_components_connect__WEBPACK_IMPORTED_MODULE_20__ = __webpack_require__(/*! dashboard/components/connect */ "./client/dashboard/components/connect/index.js");
/* harmony import */ var dashboard_utils__WEBPACK_IMPORTED_MODULE_21__ = __webpack_require__(/*! dashboard/utils */ "./client/dashboard/utils.js");
/* harmony import */ var _steps_location__WEBPACK_IMPORTED_MODULE_22__ = __webpack_require__(/*! ./steps/location */ "./client/task-list/tasks/steps/location.js");
/* harmony import */ var lib_tracks__WEBPACK_IMPORTED_MODULE_23__ = __webpack_require__(/*! lib/tracks */ "./client/lib/tracks.js");











function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_1___default()(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_8___default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_8___default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_7___default()(this, result); }; }

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






var Tax = /*#__PURE__*/function (_Component) {
  _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_6___default()(Tax, _Component);

  var _super = _createSuper(Tax);

  function Tax(props) {
    var _this;

    _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_3___default()(this, Tax);

    _this = _super.call(this, props);
    _this.initialState = {
      isPending: false,
      stepIndex: 0,
      automatedTaxEnabled: true,
      // Cache the value of pluginsToActivate so that we can show/hide tasks based on it, but not have them update mid task.
      pluginsToActivate: props.pluginsToActivate
    };
    _this.state = _this.initialState;
    _this.completeStep = _this.completeStep.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_5___default()(_this));
    _this.configureTaxRates = _this.configureTaxRates.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_5___default()(_this));
    _this.updateAutomatedTax = _this.updateAutomatedTax.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_5___default()(_this));
    _this.setIsPending = _this.setIsPending.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_5___default()(_this));
    _this.shouldShowSuccessScreen = _this.shouldShowSuccessScreen.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_5___default()(_this));
    return _this;
  }

  _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_4___default()(Tax, [{
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
      var calcTaxes = generalSettings.woocommerce_calc_taxes,
          storeAddress = generalSettings.woocommerce_store_address,
          defaultCountry = generalSettings.woocommerce_default_country,
          storePostCode = generalSettings.woocommerce_store_postcode;
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

      var isCompleteAddress = Boolean(storeAddress && defaultCountry && storePostCode);

      if (currentStepKey === 'store_location' && isCompleteAddress) {
        this.completeStep();
      }

      var prevCalcTaxes = prevProps.generalSettings.woocommerce_calc_taxes;

      if (prevCalcTaxes === 'no' && calcTaxes === 'yes') {
        window.location = Object(_woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_18__["getAdminLink"])('admin.php?page=wc-settings&tab=tax&section=standard');
      }
    }
  }, {
    key: "isTaxJarSupported",
    value: function isTaxJarSupported() {
      var _this$props3 = this.props,
          countryCode = _this$props3.countryCode,
          tosAccepted = _this$props3.tosAccepted;

      var _getSetting = Object(_woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_18__["getSetting"])('onboarding', {}),
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
        Object(_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_17__["getHistory"])().push(Object(_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_17__["getNewPath"])({}, '/', {}));
      }
    }
  }, {
    key: "configureTaxRates",
    value: function () {
      var _configureTaxRates = _babel_runtime_helpers_asyncToGenerator__WEBPACK_IMPORTED_MODULE_2___default()( /*#__PURE__*/regeneratorRuntime.mark(function _callee() {
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
                window.location = Object(_woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_18__["getAdminLink"])('admin.php?page=wc-settings&tab=tax&section=standard&wc_onboarding_active_task=tax');

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
      var _updateAutomatedTax = _babel_runtime_helpers_asyncToGenerator__WEBPACK_IMPORTED_MODULE_2___default()( /*#__PURE__*/regeneratorRuntime.mark(function _callee2() {
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
                  Object(_woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_18__["setSetting"])('onboarding', _objectSpread(_objectSpread({}, Object(_woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_18__["getSetting"])('onboarding', {})), {}, {
                    isTaxComplete: true
                  }));
                  createNotice('success', Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_10__["__"])("You're awesome! One less item on your to-do list ✅", 'woocommerce'));

                  if (automatedTaxEnabled) {
                    Object(_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_17__["getHistory"])().push(Object(_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_17__["getNewPath"])({}, '/', {}));
                  } else {
                    this.configureTaxRates();
                  }
                } else {
                  createNotice('error', Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_10__["__"])('There was a problem updating your tax settings.', 'woocommerce'));
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
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_10__["__"])('Set store location', 'woocommerce'),
        description: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_10__["__"])('The address from which your business operates', 'woocommerce'),
        content: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["createElement"])(_steps_location__WEBPACK_IMPORTED_MODULE_22__["default"], _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0___default()({}, this.props, {
          onComplete: function onComplete(values) {
            var country = Object(dashboard_utils__WEBPACK_IMPORTED_MODULE_21__["getCountryCode"])(values.countryState);
            Object(lib_tracks__WEBPACK_IMPORTED_MODULE_23__["recordEvent"])('tasklist_tax_set_location', {
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
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_10__["__"])('Install Jetpack and WooCommerce Services', 'woocommerce'),
        description: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_10__["__"])('Jetpack and WooCommerce Services allow you to automate sales tax calculations', 'woocommerce'),
        content: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_16__["Plugins"], {
          onComplete: function onComplete() {
            Object(lib_tracks__WEBPACK_IMPORTED_MODULE_23__["recordEvent"])('tasklist_tax_install_extensions', {
              install_extensions: true
            });

            _this2.completeStep();
          },
          onSkip: function onSkip() {
            Object(lib_tracks__WEBPACK_IMPORTED_MODULE_23__["queueRecordEvent"])('tasklist_tax_install_extensions', {
              install_extensions: false
            });
            window.location.href = Object(_woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_18__["getAdminLink"])('admin.php?page=wc-settings&tab=tax&section=standard');
          },
          skipText: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_10__["__"])('Set up tax rates manually', 'woocommerce')
        }),
        visible: pluginsToActivate.length && this.isTaxJarSupported()
      }, {
        key: 'connect',
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_10__["__"])('Connect your store', 'woocommerce'),
        description: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_10__["__"])('Connect your store to WordPress.com to enable automated sales tax calculations', 'woocommerce'),
        content: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["createElement"])(dashboard_components_connect__WEBPACK_IMPORTED_MODULE_20__["default"], _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0___default()({}, this.props, {
          setIsPending: this.setIsPending,
          onConnect: function onConnect() {
            Object(lib_tracks__WEBPACK_IMPORTED_MODULE_23__["recordEvent"])('tasklist_tax_connect_store', {
              connect: true
            });
          },
          onSkip: function onSkip() {
            Object(lib_tracks__WEBPACK_IMPORTED_MODULE_23__["queueRecordEvent"])('tasklist_tax_connect_store', {
              connect: false
            });
            window.location.href = Object(_woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_18__["getAdminLink"])('admin.php?page=wc-settings&tab=tax&section=standard');
          },
          skipText: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_10__["__"])('Set up tax rates manually', 'woocommerce')
        })),
        visible: !isJetpackConnected && this.isTaxJarSupported()
      }, {
        key: 'manual_configuration',
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_10__["__"])('Configure tax rates', 'woocommerce'),
        description: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_10__["__"])('Head over to the tax rate settings screen to configure your tax rates', 'woocommerce'),
        content: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["createElement"])(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["Fragment"], null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_11__["Button"], {
          isPrimary: true,
          isBusy: isPending,
          onClick: function onClick() {
            Object(lib_tracks__WEBPACK_IMPORTED_MODULE_23__["recordEvent"])('tasklist_tax_config_rates');

            _this2.configureTaxRates();
          }
        }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_10__["__"])('Configure', 'woocommerce')), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["createElement"])("p", null, generalSettings.woocommerce_calc_taxes !== 'yes' && interpolate_components__WEBPACK_IMPORTED_MODULE_14___default()({
          mixedString: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_10__["__"])(
          /*eslint-disable max-len*/
          'By clicking "Configure" you\'re enabling tax rates and calculations. More info {{link}}here{{/link}}.',
          /*eslint-enable max-len*/
          'woocommerce'),
          components: {
            link: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_16__["Link"], {
              href: "https://docs.woocommerce.com/document/setting-up-taxes-in-woocommerce/#section-1",
              target: "_blank",
              type: "external"
            })
          }
        }))),
        visible: !this.isTaxJarSupported()
      }];
      return Object(lodash__WEBPACK_IMPORTED_MODULE_13__["filter"])(steps, function (step) {
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
      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["createElement"])("div", {
        className: "woocommerce-task-tax"
      }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_16__["Card"], {
        className: "is-narrow"
      }, step ? Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_16__["Stepper"], {
        isPending: isPending || isGeneralSettingsRequesting || isTaxSettingsRequesting,
        isVertical: true,
        currentStep: step.key,
        steps: this.getSteps()
      }) : Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["createElement"])("div", {
        className: "woocommerce-task-tax__success"
      }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["createElement"])("span", {
        className: "woocommerce-task-tax__success-icon",
        role: "img",
        "aria-labelledby": "woocommerce-task-tax__success-message"
      }, "\uD83C\uDF8A"), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_16__["H"], {
        id: "woocommerce-task-tax__success-message"
      }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_10__["__"])('Good news!', 'woocommerce')), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["createElement"])("p", null, interpolate_components__WEBPACK_IMPORTED_MODULE_14___default()({
        mixedString: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_10__["__"])('{{strong}}Jetpack{{/strong}} and {{strong}}WooCommerce Services{{/strong}} ' + 'can automate your sales tax calculations for you.', 'woocommerce'),
        components: {
          strong: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["createElement"])("strong", null)
        }
      })), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_11__["Button"], {
        isPrimary: true,
        isBusy: Object.keys(taxSettings).length && isTaxSettingsRequesting,
        onClick: function onClick() {
          Object(lib_tracks__WEBPACK_IMPORTED_MODULE_23__["recordEvent"])('tasklist_tax_setup_automated_proceed', {
            setup_automatically: true
          });

          _this3.setState({
            automatedTaxEnabled: true
          }, _this3.updateAutomatedTax);
        }
      }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_10__["__"])('Yes please', 'woocommerce')), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_11__["Button"], {
        onClick: function onClick() {
          Object(lib_tracks__WEBPACK_IMPORTED_MODULE_23__["recordEvent"])('tasklist_tax_setup_automated_proceed', {
            setup_automatically: false
          });

          _this3.setState({
            automatedTaxEnabled: false
          }, _this3.updateAutomatedTax);
        }
      }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_10__["__"])("No thanks, I'll configure taxes manually", 'woocommerce')))));
    }
  }]);

  return Tax;
}(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["Component"]);

/* harmony default export */ __webpack_exports__["default"] = (Object(_wordpress_compose__WEBPACK_IMPORTED_MODULE_12__["compose"])(Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_15__["withSelect"])(function (select) {
  var _select = select(_woocommerce_data__WEBPACK_IMPORTED_MODULE_19__["SETTINGS_STORE_NAME"]),
      getSettings = _select.getSettings,
      getSettingsError = _select.getSettingsError,
      isGetSettingsRequesting = _select.isGetSettingsRequesting;

  var _select2 = select(_woocommerce_data__WEBPACK_IMPORTED_MODULE_19__["OPTIONS_STORE_NAME"]),
      getOption = _select2.getOption;

  var _select3 = select(_woocommerce_data__WEBPACK_IMPORTED_MODULE_19__["PLUGINS_STORE_NAME"]),
      getActivePlugins = _select3.getActivePlugins,
      isJetpackConnected = _select3.isJetpackConnected;

  var _getSettings = getSettings('general'),
      _getSettings$general = _getSettings.general,
      generalSettings = _getSettings$general === void 0 ? {} : _getSettings$general;

  var isGeneralSettingsError = Boolean(getSettingsError('general'));
  var isGeneralSettingsRequesting = isGetSettingsRequesting('general');
  var countryCode = Object(dashboard_utils__WEBPACK_IMPORTED_MODULE_21__["getCountryCode"])(generalSettings.woocommerce_default_country);

  var _getSettings2 = getSettings('tax'),
      _getSettings2$tax = _getSettings2.tax,
      taxSettings = _getSettings2$tax === void 0 ? {} : _getSettings2$tax;

  var isTaxSettingsError = Boolean(getSettingsError('tax'));
  var isTaxSettingsRequesting = isGetSettingsRequesting('tax');
  var activePlugins = getActivePlugins();
  var pluginsToActivate = Object(lodash__WEBPACK_IMPORTED_MODULE_13__["difference"])(['jetpack', 'woocommerce-services'], activePlugins);
  var connectOptions = getOption('wc_connect_options') || {};
  var tosAccepted = connectOptions.tos_accepted || getOption('woocommerce_setup_jetpack_opted_in');
  return {
    isGeneralSettingsError: isGeneralSettingsError,
    isGeneralSettingsRequesting: isGeneralSettingsRequesting,
    generalSettings: generalSettings,
    countryCode: countryCode,
    taxSettings: taxSettings,
    isTaxSettingsError: isTaxSettingsError,
    isTaxSettingsRequesting: isTaxSettingsRequesting,
    isJetpackConnected: isJetpackConnected(),
    pluginsToActivate: pluginsToActivate,
    tosAccepted: tosAccepted
  };
}), Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_15__["withDispatch"])(function (dispatch) {
  var _dispatch = dispatch('core/notices'),
      createNotice = _dispatch.createNotice;

  var _dispatch2 = dispatch(_woocommerce_data__WEBPACK_IMPORTED_MODULE_19__["SETTINGS_STORE_NAME"]),
      updateAndPersistSettingsForGroup = _dispatch2.updateAndPersistSettingsForGroup;

  return {
    createNotice: createNotice,
    updateAndPersistSettingsForGroup: updateAndPersistSettingsForGroup
  };
}))(Tax));

/***/ })

}]);
//# sourceMappingURL=task-list.27ae10e6e0d0e47bbada.min.js.map
