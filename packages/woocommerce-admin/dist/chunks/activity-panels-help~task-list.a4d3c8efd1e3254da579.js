(window["__wcAdmin_webpackJsonp"] = window["__wcAdmin_webpackJsonp"] || []).push([["activity-panels-help~task-list"],{

/***/ "./client/lib/notices/index.js":
/*!*************************************!*\
  !*** ./client/lib/notices/index.js ***!
  \*************************************/
/*! exports provided: createNoticesFromResponse */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "createNoticesFromResponse", function() { return createNoticesFromResponse; });
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_0__);
/**
 * External dependencies
 */

function createNoticesFromResponse(response) {
  var _dispatch = Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_0__["dispatch"])('core/notices'),
      createNotice = _dispatch.createNotice;

  if (response.error_data && response.errors && Object.keys(response.errors).length) {
    // Loop over multi-error responses.
    Object.keys(response.errors).forEach(function (errorKey) {
      createNotice('error', response.errors[errorKey].join(' '));
    });
  } else if (response.message) {
    // Handle generic messages.
    createNotice(response.code ? 'error' : 'success', response.message);
  }
}

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
/* harmony import */ var _babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/regenerator */ "./node_modules/@babel/runtime/regenerator/index.js");
/* harmony import */ var _babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_1__);
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
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! @babel/runtime/helpers/defineProperty */ "./node_modules/@babel/runtime/helpers/defineProperty.js");
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_9__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_10___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_11___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! @wordpress/components */ "./node_modules/@wordpress/components/build-module/index.js");
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! @wordpress/compose */ "./node_modules/@wordpress/compose/build-module/index.js");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_14___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_14__);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(/*! @woocommerce/components */ "@woocommerce/components");
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_15___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_components__WEBPACK_IMPORTED_MODULE_15__);
/* harmony import */ var _woocommerce_data__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__(/*! @woocommerce/data */ "@woocommerce/data");
/* harmony import */ var _woocommerce_data__WEBPACK_IMPORTED_MODULE_16___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_data__WEBPACK_IMPORTED_MODULE_16__);












function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_8___default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_8___default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_7___default()(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/**
 * External dependencies
 */





/**
 * WooCommerce dependencies
 */




var PayFast = /*#__PURE__*/function (_Component) {
  _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_6___default()(PayFast, _Component);

  var _super = _createSuper(PayFast);

  function PayFast() {
    var _this;

    _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_3___default()(this, PayFast);

    for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
      args[_key] = arguments[_key];
    }

    _this = _super.call.apply(_super, [this].concat(args));

    _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_9___default()(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_5___default()(_this), "getInitialConfigValues", function () {
      return {
        account_name: '',
        account_number: '',
        bank_name: '',
        sort_code: '',
        iban: '',
        bic: ''
      };
    });

    _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_9___default()(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_5___default()(_this), "validate", function (values) {
      var errors = {};

      if (!values.account_number && !values.iban) {
        errors.account_number = errors.iban = Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__["__"])('Please enter an account number or IBAN', 'woocommerce');
      }

      return errors;
    });

    _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_9___default()(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_5___default()(_this), "updateSettings", /*#__PURE__*/function () {
      var _ref = _babel_runtime_helpers_asyncToGenerator__WEBPACK_IMPORTED_MODULE_2___default()( /*#__PURE__*/_babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_1___default.a.mark(function _callee(values) {
        var _this$props, updateOptions, createNotice, markConfigured, update;

        return _babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_1___default.a.wrap(function _callee$(_context) {
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
                  createNotice('success', Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__["__"])('Direct bank transfer details added successfully', 'woocommerce'));
                } else {
                  createNotice('error', Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__["__"])('There was a problem saving your payment setings', 'woocommerce'));
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
    }());

    return _this;
  }

  _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_4___default()(PayFast, [{
    key: "render",
    value: function render() {
      var isOptionsRequesting = this.props.isOptionsRequesting;
      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_15__["Form"], {
        initialValues: this.getInitialConfigValues(),
        onSubmitCallback: this.updateSettings,
        validate: this.validate
      }, function (_ref2) {
        var getInputProps = _ref2.getInputProps,
            handleSubmit = _ref2.handleSubmit;
        return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__["createElement"])(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__["Fragment"], null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_15__["H"], null, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__["__"])('Add your bank details', 'woocommerce')), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__["createElement"])("p", null, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__["__"])('These details are required to receive payments via bank transfer', 'woocommerce')), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__["createElement"])("div", {
          className: "woocommerce-task-payment-method__fields"
        }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_15__["TextControl"], _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0___default()({
          label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__["__"])('Account name', 'woocommerce'),
          required: true
        }, getInputProps('account_name'))), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_15__["TextControl"], _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0___default()({
          label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__["__"])('Account number', 'woocommerce'),
          required: true
        }, getInputProps('account_number'))), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_15__["TextControl"], _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0___default()({
          label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__["__"])('Bank name', 'woocommerce'),
          required: true
        }, getInputProps('bank_name'))), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_15__["TextControl"], _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0___default()({
          label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__["__"])('Sort code', 'woocommerce'),
          required: true
        }, getInputProps('sort_code'))), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_15__["TextControl"], _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0___default()({
          label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__["__"])('IBAN', 'woocommerce'),
          required: true
        }, getInputProps('iban'))), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_15__["TextControl"], _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0___default()({
          label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__["__"])('BIC / Swift', 'woocommerce'),
          required: true
        }, getInputProps('bic')))), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_12__["Button"], {
          isPrimary: true,
          isBusy: isOptionsRequesting,
          onClick: handleSubmit
        }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__["__"])('Save', 'woocommerce')));
      });
    }
  }]);

  return PayFast;
}(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__["Component"]);

/* harmony default export */ __webpack_exports__["default"] = (Object(_wordpress_compose__WEBPACK_IMPORTED_MODULE_13__["compose"])(Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_14__["withSelect"])(function (select) {
  var _select = select(_woocommerce_data__WEBPACK_IMPORTED_MODULE_16__["OPTIONS_STORE_NAME"]),
      isOptionsUpdating = _select.isOptionsUpdating;

  var isOptionsRequesting = isOptionsUpdating();
  return {
    isOptionsRequesting: isOptionsRequesting
  };
}), Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_14__["withDispatch"])(function (dispatch) {
  var _dispatch = dispatch('core/notices'),
      createNotice = _dispatch.createNotice;

  var _dispatch2 = dispatch(_woocommerce_data__WEBPACK_IMPORTED_MODULE_16__["OPTIONS_STORE_NAME"]),
      updateOptions = _dispatch2.updateOptions;

  return {
    createNotice: createNotice,
    updateOptions: updateOptions
  };
}))(PayFast));

/***/ }),

/***/ "./client/task-list/tasks/payments/eway.js":
/*!*************************************************!*\
  !*** ./client/task-list/tasks/payments/eway.js ***!
  \*************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/extends */ "./node_modules/@babel/runtime/helpers/extends.js");
/* harmony import */ var _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/regenerator */ "./node_modules/@babel/runtime/regenerator/index.js");
/* harmony import */ var _babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_1__);
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
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! @babel/runtime/helpers/defineProperty */ "./node_modules/@babel/runtime/helpers/defineProperty.js");
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_9__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_10___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_11___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! @wordpress/components */ "./node_modules/@wordpress/components/build-module/index.js");
/* harmony import */ var interpolate_components__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! interpolate-components */ "./node_modules/interpolate-components/lib/index.js");
/* harmony import */ var interpolate_components__WEBPACK_IMPORTED_MODULE_13___default = /*#__PURE__*/__webpack_require__.n(interpolate_components__WEBPACK_IMPORTED_MODULE_13__);
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! @wordpress/compose */ "./node_modules/@wordpress/compose/build-module/index.js");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_15___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_15__);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__(/*! @woocommerce/components */ "@woocommerce/components");
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_16___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_components__WEBPACK_IMPORTED_MODULE_16__);
/* harmony import */ var _woocommerce_data__WEBPACK_IMPORTED_MODULE_17__ = __webpack_require__(/*! @woocommerce/data */ "@woocommerce/data");
/* harmony import */ var _woocommerce_data__WEBPACK_IMPORTED_MODULE_17___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_data__WEBPACK_IMPORTED_MODULE_17__);












function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_8___default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_8___default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_7___default()(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/**
 * External dependencies
 */






/**
 * WooCommerce dependencies
 */




var EWay = /*#__PURE__*/function (_Component) {
  _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_6___default()(EWay, _Component);

  var _super = _createSuper(EWay);

  function EWay() {
    var _this;

    _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_3___default()(this, EWay);

    for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
      args[_key] = arguments[_key];
    }

    _this = _super.call.apply(_super, [this].concat(args));

    _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_9___default()(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_5___default()(_this), "getInitialConfigValues", function () {
      return {
        customer_api: '',
        customer_password: ''
      };
    });

    _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_9___default()(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_5___default()(_this), "validate", function (values) {
      var errors = {};

      if (!values.customer_api) {
        errors.customer_api = Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__["__"])('Please enter your customer API key ', 'woocommerce');
      }

      if (!values.customer_password) {
        errors.customer_password = Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__["__"])('Please enter your customer password', 'woocommerce');
      }

      return errors;
    });

    _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_9___default()(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_5___default()(_this), "updateSettings", /*#__PURE__*/function () {
      var _ref = _babel_runtime_helpers_asyncToGenerator__WEBPACK_IMPORTED_MODULE_2___default()( /*#__PURE__*/_babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_1___default.a.mark(function _callee(values) {
        var _this$props, updateOptions, createNotice, markConfigured, update;

        return _babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_1___default.a.wrap(function _callee$(_context) {
          while (1) {
            switch (_context.prev = _context.next) {
              case 0:
                _this$props = _this.props, updateOptions = _this$props.updateOptions, createNotice = _this$props.createNotice, markConfigured = _this$props.markConfigured;
                _context.next = 3;
                return updateOptions({
                  woocommerce_eway_settings: {
                    customer_api: values.customer_api,
                    customer_password: values.customer_password,
                    enabled: 'yes'
                  }
                });

              case 3:
                update = _context.sent;

                if (update.success) {
                  markConfigured('eway');
                  createNotice('success', Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__["__"])('eWAY connected successfully', 'woocommerce'));
                } else {
                  createNotice('error', Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__["__"])('There was a problem saving your payment setings', 'woocommerce'));
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
    }());

    return _this;
  }

  _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_4___default()(EWay, [{
    key: "renderConnectStep",
    value: function renderConnectStep() {
      var isOptionsRequesting = this.props.isOptionsRequesting;
      var helpText = interpolate_components__WEBPACK_IMPORTED_MODULE_13___default()({
        mixedString: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__["__"])('Your API details can be obtained from your {{link}}eWAY account{{/link}}', 'woocommerce'),
        components: {
          link: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_16__["Link"], {
            href: "https://www.eway.com.au/",
            target: "_blank",
            type: "external"
          })
        }
      });
      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_16__["Form"], {
        initialValues: this.getInitialConfigValues(),
        onSubmitCallback: this.updateSettings,
        validate: this.validate
      }, function (_ref2) {
        var getInputProps = _ref2.getInputProps,
            handleSubmit = _ref2.handleSubmit;
        return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__["createElement"])(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__["Fragment"], null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_16__["TextControl"], _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0___default()({
          label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__["__"])('Customer API Key', 'woocommerce'),
          required: true
        }, getInputProps('customer_api'))), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_16__["TextControl"], _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0___default()({
          label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__["__"])('Customer Password', 'woocommerce'),
          required: true
        }, getInputProps('customer_password'))), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_12__["Button"], {
          isPrimary: true,
          isBusy: isOptionsRequesting,
          onClick: handleSubmit
        }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__["__"])('Proceed', 'woocommerce')), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__["createElement"])("p", null, helpText));
      });
    }
  }, {
    key: "render",
    value: function render() {
      var _this$props2 = this.props,
          installStep = _this$props2.installStep,
          isOptionsRequesting = _this$props2.isOptionsRequesting;
      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_16__["Stepper"], {
        isVertical: true,
        isPending: !installStep.isComplete || isOptionsRequesting,
        currentStep: installStep.isComplete ? 'connect' : 'install',
        steps: [installStep, {
          key: 'connect',
          label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__["__"])('Connect your eWAY account', 'woocommerce'),
          content: this.renderConnectStep()
        }]
      });
    }
  }]);

  return EWay;
}(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__["Component"]);

/* harmony default export */ __webpack_exports__["default"] = (Object(_wordpress_compose__WEBPACK_IMPORTED_MODULE_14__["compose"])(Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_15__["withSelect"])(function (select) {
  var _select = select(_woocommerce_data__WEBPACK_IMPORTED_MODULE_17__["OPTIONS_STORE_NAME"]),
      isOptionsUpdating = _select.isOptionsUpdating;

  var isOptionsRequesting = isOptionsUpdating();
  return {
    isOptionsRequesting: isOptionsRequesting
  };
}), Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_15__["withDispatch"])(function (dispatch) {
  var _dispatch = dispatch('core/notices'),
      createNotice = _dispatch.createNotice;

  var _dispatch2 = dispatch(_woocommerce_data__WEBPACK_IMPORTED_MODULE_17__["OPTIONS_STORE_NAME"]),
      updateOptions = _dispatch2.updateOptions;

  return {
    createNotice: createNotice,
    updateOptions: updateOptions
  };
}))(EWay));

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
/*! exports provided: installActivateAndConnectWcpay, getPaymentMethods */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "installActivateAndConnectWcpay", function() { return installActivateAndConnectWcpay; });
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
/* harmony import */ var _eway__WEBPACK_IMPORTED_MODULE_19__ = __webpack_require__(/*! ./eway */ "./client/task-list/tasks/payments/eway.js");


/**
 * External dependencies
 */





/**
 * WooCommerce dependencies
 */




/**
 * Internal dependencies
 */













function installActivateAndConnectWcpay(resolve, reject, createNotice, installAndActivatePlugins) {
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
}
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
        return installActivateAndConnectWcpay(resolve, reject, createNotice, installAndActivatePlugins);
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
    isConfigured: options.woocommerce_ppec_paypal_settings && (options.woocommerce_ppec_paypal_settings.reroute_requests && options.woocommerce_ppec_paypal_settings.email || options.woocommerce_ppec_paypal_settings.api_username && options.woocommerce_ppec_paypal_settings.api_password),
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
    key: 'eway',
    title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('eWAY', 'woocommerce'),
    content: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["Fragment"], null, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__["__"])('The eWAY extension for WooCommerce allows you to take credit card payments directly on your store without redirecting your customers to a third party site to make payment.', 'woocommerce')),
    before: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("img", {
      src: _woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_5__["WC_ASSET_URL"] + 'images/eway-logo.jpg',
      alt: "eWAY logo"
    }),
    visible: ['AU', 'NZ'].includes(countryCode) && !hasCbdIndustry,
    plugins: ['woocommerce-gateway-eway'],
    container: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_eway__WEBPACK_IMPORTED_MODULE_19__["default"], null),
    isConfigured: options.woocommerce_eway_settings && options.woocommerce_eway_settings.customer_api && options.woocommerce_eway_settings.customer_password,
    isEnabled: options.woocommerce_eway_settings && options.woocommerce_eway_settings.enabled === 'yes',
    optionName: 'woocommerce_eway_settings'
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
/* harmony import */ var _babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/regenerator */ "./node_modules/@babel/runtime/regenerator/index.js");
/* harmony import */ var _babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_1__);
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
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! @babel/runtime/helpers/defineProperty */ "./node_modules/@babel/runtime/helpers/defineProperty.js");
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_9__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_10___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_11___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! @wordpress/components */ "./node_modules/@wordpress/components/build-module/index.js");
/* harmony import */ var interpolate_components__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! interpolate-components */ "./node_modules/interpolate-components/lib/index.js");
/* harmony import */ var interpolate_components__WEBPACK_IMPORTED_MODULE_13___default = /*#__PURE__*/__webpack_require__.n(interpolate_components__WEBPACK_IMPORTED_MODULE_13__);
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! @wordpress/compose */ "./node_modules/@wordpress/compose/build-module/index.js");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_15___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_15__);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__(/*! @woocommerce/components */ "@woocommerce/components");
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_16___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_components__WEBPACK_IMPORTED_MODULE_16__);
/* harmony import */ var _woocommerce_data__WEBPACK_IMPORTED_MODULE_17__ = __webpack_require__(/*! @woocommerce/data */ "@woocommerce/data");
/* harmony import */ var _woocommerce_data__WEBPACK_IMPORTED_MODULE_17___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_data__WEBPACK_IMPORTED_MODULE_17__);












function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_8___default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_8___default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_7___default()(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/**
 * External dependencies
 */






/**
 * WooCommerce dependencies
 */




var PayFast = /*#__PURE__*/function (_Component) {
  _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_6___default()(PayFast, _Component);

  var _super = _createSuper(PayFast);

  function PayFast() {
    var _this;

    _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_3___default()(this, PayFast);

    for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
      args[_key] = arguments[_key];
    }

    _this = _super.call.apply(_super, [this].concat(args));

    _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_9___default()(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_5___default()(_this), "getInitialConfigValues", function () {
      return {
        merchant_id: '',
        merchant_key: '',
        pass_phrase: ''
      };
    });

    _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_9___default()(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_5___default()(_this), "validate", function (values) {
      var errors = {};

      if (!values.merchant_id) {
        errors.merchant_id = Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__["__"])('Please enter your merchant ID', 'woocommerce');
      }

      if (!values.merchant_key) {
        errors.merchant_key = Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__["__"])('Please enter your merchant key', 'woocommerce');
      }

      if (!values.pass_phrase) {
        errors.pass_phrase = Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__["__"])('Please enter your passphrase', 'woocommerce');
      }

      return errors;
    });

    _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_9___default()(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_5___default()(_this), "updateSettings", /*#__PURE__*/function () {
      var _ref = _babel_runtime_helpers_asyncToGenerator__WEBPACK_IMPORTED_MODULE_2___default()( /*#__PURE__*/_babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_1___default.a.mark(function _callee(values) {
        var _this$props, updateOptions, createNotice, markConfigured, update;

        return _babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_1___default.a.wrap(function _callee$(_context) {
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
                  createNotice('success', Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__["__"])('PayFast connected successfully', 'woocommerce'));
                } else {
                  createNotice('error', Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__["__"])('There was a problem saving your payment setings', 'woocommerce'));
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
    }());

    return _this;
  }

  _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_4___default()(PayFast, [{
    key: "renderConnectStep",
    value: function renderConnectStep() {
      var isOptionsRequesting = this.props.isOptionsRequesting;
      var helpText = interpolate_components__WEBPACK_IMPORTED_MODULE_13___default()({
        mixedString: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__["__"])('Your API details can be obtained from your {{link}}PayFast account{{/link}}', 'woocommerce'),
        components: {
          link: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_16__["Link"], {
            href: "https://www.payfast.co.za/",
            target: "_blank",
            type: "external"
          })
        }
      });
      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_16__["Form"], {
        initialValues: this.getInitialConfigValues(),
        onSubmitCallback: this.updateSettings,
        validate: this.validate
      }, function (_ref2) {
        var getInputProps = _ref2.getInputProps,
            handleSubmit = _ref2.handleSubmit;
        return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__["createElement"])(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__["Fragment"], null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_16__["TextControl"], _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0___default()({
          label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__["__"])('Merchant ID', 'woocommerce'),
          required: true
        }, getInputProps('merchant_id'))), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_16__["TextControl"], _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0___default()({
          label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__["__"])('Merchant Key', 'woocommerce'),
          required: true
        }, getInputProps('merchant_key'))), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_16__["TextControl"], _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0___default()({
          label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__["__"])('Passphrase', 'woocommerce'),
          required: true
        }, getInputProps('pass_phrase'))), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_12__["Button"], {
          isPrimary: true,
          isBusy: isOptionsRequesting,
          onClick: handleSubmit
        }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__["__"])('Proceed', 'woocommerce')), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__["createElement"])("p", null, helpText));
      });
    }
  }, {
    key: "render",
    value: function render() {
      var _this$props2 = this.props,
          installStep = _this$props2.installStep,
          isOptionsRequesting = _this$props2.isOptionsRequesting;
      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_16__["Stepper"], {
        isVertical: true,
        isPending: !installStep.isComplete || isOptionsRequesting,
        currentStep: installStep.isComplete ? 'connect' : 'install',
        steps: [installStep, {
          key: 'connect',
          label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__["__"])('Connect your PayFast account', 'woocommerce'),
          content: this.renderConnectStep()
        }]
      });
    }
  }]);

  return PayFast;
}(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__["Component"]);

/* harmony default export */ __webpack_exports__["default"] = (Object(_wordpress_compose__WEBPACK_IMPORTED_MODULE_14__["compose"])(Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_15__["withSelect"])(function (select) {
  var _select = select(_woocommerce_data__WEBPACK_IMPORTED_MODULE_17__["OPTIONS_STORE_NAME"]),
      isOptionsUpdating = _select.isOptionsUpdating;

  var isOptionsRequesting = isOptionsUpdating();
  return {
    isOptionsRequesting: isOptionsRequesting
  };
}), Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_15__["withDispatch"])(function (dispatch) {
  var _dispatch = dispatch('core/notices'),
      createNotice = _dispatch.createNotice;

  var _dispatch2 = dispatch(_woocommerce_data__WEBPACK_IMPORTED_MODULE_17__["OPTIONS_STORE_NAME"]),
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
/*! exports provided: PayPal, default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "PayPal", function() { return PayPal; });
/* harmony import */ var _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/extends */ "./node_modules/@babel/runtime/helpers/extends.js");
/* harmony import */ var _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/defineProperty */ "./node_modules/@babel/runtime/helpers/defineProperty.js");
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @babel/runtime/regenerator */ "./node_modules/@babel/runtime/regenerator/index.js");
/* harmony import */ var _babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _babel_runtime_helpers_asyncToGenerator__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @babel/runtime/helpers/asyncToGenerator */ "./node_modules/@babel/runtime/helpers/asyncToGenerator.js");
/* harmony import */ var _babel_runtime_helpers_asyncToGenerator__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_asyncToGenerator__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @babel/runtime/helpers/classCallCheck */ "./node_modules/@babel/runtime/helpers/classCallCheck.js");
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @babel/runtime/helpers/createClass */ "./node_modules/@babel/runtime/helpers/createClass.js");
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @babel/runtime/helpers/assertThisInitialized */ "./node_modules/@babel/runtime/helpers/assertThisInitialized.js");
/* harmony import */ var _babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @babel/runtime/helpers/inherits */ "./node_modules/@babel/runtime/helpers/inherits.js");
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! @babel/runtime/helpers/possibleConstructorReturn */ "./node_modules/@babel/runtime/helpers/possibleConstructorReturn.js");
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_8__);
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! @babel/runtime/helpers/getPrototypeOf */ "./node_modules/@babel/runtime/helpers/getPrototypeOf.js");
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_9__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_10___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_11___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__);
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! @wordpress/api-fetch */ "@wordpress/api-fetch");
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_12___default = /*#__PURE__*/__webpack_require__.n(_wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_12__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! @wordpress/components */ "./node_modules/@wordpress/components/build-module/index.js");
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! @wordpress/compose */ "./node_modules/@wordpress/compose/build-module/index.js");
/* harmony import */ var interpolate_components__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(/*! interpolate-components */ "./node_modules/interpolate-components/lib/index.js");
/* harmony import */ var interpolate_components__WEBPACK_IMPORTED_MODULE_15___default = /*#__PURE__*/__webpack_require__.n(interpolate_components__WEBPACK_IMPORTED_MODULE_15__);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_16___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_16__);
/* harmony import */ var _wordpress_url__WEBPACK_IMPORTED_MODULE_17__ = __webpack_require__(/*! @wordpress/url */ "@wordpress/url");
/* harmony import */ var _wordpress_url__WEBPACK_IMPORTED_MODULE_17___default = /*#__PURE__*/__webpack_require__.n(_wordpress_url__WEBPACK_IMPORTED_MODULE_17__);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_18__ = __webpack_require__(/*! @woocommerce/components */ "@woocommerce/components");
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_18___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_components__WEBPACK_IMPORTED_MODULE_18__);
/* harmony import */ var _woocommerce_navigation__WEBPACK_IMPORTED_MODULE_19__ = __webpack_require__(/*! @woocommerce/navigation */ "@woocommerce/navigation");
/* harmony import */ var _woocommerce_navigation__WEBPACK_IMPORTED_MODULE_19___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_19__);
/* harmony import */ var wc_api_constants__WEBPACK_IMPORTED_MODULE_20__ = __webpack_require__(/*! wc-api/constants */ "./client/wc-api/constants.js");
/* harmony import */ var _woocommerce_data__WEBPACK_IMPORTED_MODULE_21__ = __webpack_require__(/*! @woocommerce/data */ "@woocommerce/data");
/* harmony import */ var _woocommerce_data__WEBPACK_IMPORTED_MODULE_21___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_data__WEBPACK_IMPORTED_MODULE_21__);












function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_1___default()(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_9___default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_9___default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_8___default()(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/**
 * External dependencies
 */








/**
 * WooCommerce dependencies
 */





var PayPal = /*#__PURE__*/function (_Component) {
  _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_7___default()(PayPal, _Component);

  var _super = _createSuper(PayPal);

  function PayPal(props) {
    var _this;

    _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_4___default()(this, PayPal);

    _this = _super.call(this, props);
    _this.state = {
      autoConnectFailed: false,
      connectURL: '',
      isPending: false
    };
    _this.updateSettings = _this.updateSettings.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_6___default()(_this));
    _this.validate = _this.validate.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_6___default()(_this));
    return _this;
  }

  _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_5___default()(PayPal, [{
    key: "componentDidMount",
    value: function componentDidMount() {
      var _this$props = this.props,
          createNotice = _this$props.createNotice,
          markConfigured = _this$props.markConfigured;
      var query = Object(_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_19__["getQuery"])(); // Handle redirect back from PayPal

      if (query['paypal-connect']) {
        if (query['paypal-connect'] === '1') {
          createNotice('success', Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__["__"])('PayPal connected successfully.', 'woocommerce'));
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
    key: "isWooCommerceServicesConnected",
    value: function isWooCommerceServicesConnected() {
      var _this$props2 = this.props,
          activePlugins = _this$props2.activePlugins,
          isJetpackConnected = _this$props2.isJetpackConnected,
          wcsTosAccepted = _this$props2.wcsTosAccepted;
      return isJetpackConnected && wcsTosAccepted && activePlugins.includes('woocommerce-services');
    }
  }, {
    key: "fetchOAuthConnectURL",
    value: function () {
      var _fetchOAuthConnectURL = _babel_runtime_helpers_asyncToGenerator__WEBPACK_IMPORTED_MODULE_3___default()( /*#__PURE__*/_babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_2___default.a.mark(function _callee() {
        var activePlugins, result;
        return _babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_2___default.a.wrap(function _callee$(_context) {
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
                return _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_12___default()({
                  path: wc_api_constants__WEBPACK_IMPORTED_MODULE_20__["WC_ADMIN_NAMESPACE"] + '/plugins/connect-paypal',
                  method: 'POST'
                });

              case 7:
                result = _context.sent;

                if (!(!result || !result.connectUrl)) {
                  _context.next = 11;
                  break;
                }

                this.setState({
                  autoConnectFailed: true,
                  isPending: false
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
    key: "updateSettings",
    value: function () {
      var _updateSettings = _babel_runtime_helpers_asyncToGenerator__WEBPACK_IMPORTED_MODULE_3___default()( /*#__PURE__*/_babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_2___default.a.mark(function _callee2(values) {
        var _this$props3, createNotice, options, updateOptions, markConfigured, optionValues, update;

        return _babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_2___default.a.wrap(function _callee2$(_context2) {
          while (1) {
            switch (_context2.prev = _context2.next) {
              case 0:
                _this$props3 = this.props, createNotice = _this$props3.createNotice, options = _this$props3.options, updateOptions = _this$props3.updateOptions, markConfigured = _this$props3.markConfigured;
                optionValues = _objectSpread(_objectSpread({}, options.woocommerce_ppec_paypal_settings), {}, {
                  enabled: 'yes'
                });

                if (values.create_account) {
                  // Tell WCS to proxy payment requests.
                  // See: https://github.com/Automattic/woocommerce-services/blob/29dfe0ba6fd3075afe08f917a6ff33c321502d9c/classes/class-wc-connect-paypal-ec.php#L53.
                  optionValues.reroute_requests = 'yes';
                  optionValues.email = values.account_email;
                } else {
                  optionValues.api_username = values.api_username;
                  optionValues.api_password = values.api_password;
                }

                _context2.next = 5;
                return updateOptions({
                  woocommerce_ppec_paypal_settings: optionValues
                });

              case 5:
                update = _context2.sent;

                if (update.success) {
                  createNotice('success', Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__["__"])('PayPal connected successfully.', 'woocommerce'));
                  markConfigured('paypal');
                } else {
                  createNotice('error', Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__["__"])('There was a problem saving your payment settings.', 'woocommerce'));
                }

              case 7:
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
        api_password: '',
        create_account: this.isWooCommerceServicesConnected(),
        account_email: ''
      };
    }
  }, {
    key: "validate",
    value: function validate(values) {
      var errors = {};

      if (!values.create_account && !values.api_username) {
        errors.api_username = Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__["__"])('Please enter your API username', 'woocommerce');
      }

      if (!values.create_account && !values.api_password) {
        errors.api_password = Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__["__"])('Please enter your API password', 'woocommerce');
      }

      if (this.isWooCommerceServicesConnected() && values.create_account && !Object(_wordpress_url__WEBPACK_IMPORTED_MODULE_17__["isEmail"])(values.account_email)) {
        errors.account_email = Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__["__"])('Please enter a valid email address', 'woocommerce');
      }

      return errors;
    }
  }, {
    key: "renderAutomaticConfig",
    value: function renderAutomaticConfig() {
      var isOptionsUpdating = this.props.isOptionsUpdating;
      var _this$state = this.state,
          autoConnectFailed = _this$state.autoConnectFailed,
          connectURL = _this$state.connectURL,
          isPending = _this$state.isPending;
      var canAutoCreate = this.isWooCommerceServicesConnected();
      var initialValues = this.getInitialConfigValues();
      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_18__["Form"], {
        initialValues: initialValues,
        onSubmitCallback: this.updateSettings,
        validate: this.validate
      }, function (_ref) {
        var getInputProps = _ref.getInputProps,
            handleSubmit = _ref.handleSubmit,
            values = _ref.values;
        return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__["createElement"])(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__["Fragment"], null, canAutoCreate && Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__["createElement"])("div", {
          className: "woocommerce-task-payments__paypal-auto-create-account"
        }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_13__["CheckboxControl"], _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0___default()({
          label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__["__"])('Create a PayPal account for me', 'woocommerce')
        }, getInputProps('create_account'))), values.create_account && Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_18__["TextControl"], _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0___default()({
          label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__["__"])('Email address', 'woocommerce'),
          type: "email"
        }, getInputProps('account_email')))), !isPending && (autoConnectFailed || !connectURL) && (!canAutoCreate || !values.create_account) && Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__["createElement"])(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__["Fragment"], null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_18__["TextControl"], _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0___default()({
          label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__["__"])('API Username', 'woocommerce'),
          required: true
        }, getInputProps('api_username'))), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_18__["TextControl"], _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0___default()({
          label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__["__"])('API Password', 'woocommerce'),
          required: true
        }, getInputProps('api_password'))), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_13__["Button"], {
          onClick: handleSubmit,
          isPrimary: true,
          isBusy: isOptionsUpdating
        }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__["__"])('Proceed', 'woocommerce')), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__["createElement"])("p", null, interpolate_components__WEBPACK_IMPORTED_MODULE_15___default()({
          mixedString: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__["__"])('Your API details can be obtained from your {{link}}PayPal account{{/link}}', 'woocommerce'),
          components: {
            link: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_18__["Link"], {
              href: "https://docs.woocommerce.com/document/paypal-express-checkout/#section-8",
              target: "_blank",
              type: "external"
            })
          }
        }))), canAutoCreate && values.create_account && Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_13__["Button"], {
          onClick: handleSubmit,
          isPrimary: true,
          isBusy: isOptionsUpdating
        }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__["__"])('Create account', 'woocommerce')), !autoConnectFailed && connectURL && (!canAutoCreate || !values.create_account) && Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__["createElement"])(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__["Fragment"], null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_13__["Button"], {
          isPrimary: true,
          href: connectURL
        }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__["__"])('Connect', 'woocommerce')), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__["createElement"])("p", null, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__["__"])('You will be redirected to the Paypal website to create the connection.', 'woocommerce'))));
      });
    }
  }, {
    key: "getConnectStep",
    value: function getConnectStep() {
      return {
        key: 'connect',
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__["__"])('Connect your PayPal account', 'woocommerce'),
        description: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__["__"])('A Paypal account is required to process payments. Connect your store to your PayPal account.', 'woocommerce'),
        content: this.renderAutomaticConfig()
      };
    }
  }, {
    key: "render",
    value: function render() {
      var installStep = this.props.installStep;
      var isPending = this.state.isPending;
      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_18__["Stepper"], {
        isVertical: true,
        isPending: !installStep.isComplete || isPending,
        currentStep: installStep.isComplete ? 'connect' : 'install',
        steps: [installStep, this.getConnectStep()]
      });
    }
  }]);

  return PayPal;
}(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__["Component"]);
PayPal.defaultProps = {
  manualConfig: false // WCS is not required for the PayPal OAuth flow, so we can default to smooth connection.

};
/* harmony default export */ __webpack_exports__["default"] = (Object(_wordpress_compose__WEBPACK_IMPORTED_MODULE_14__["compose"])(Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_16__["withSelect"])(function (select) {
  var _select = select(_woocommerce_data__WEBPACK_IMPORTED_MODULE_21__["OPTIONS_STORE_NAME"]),
      getOption = _select.getOption,
      isOptionsUpdating = _select.isOptionsUpdating;

  var _select2 = select(_woocommerce_data__WEBPACK_IMPORTED_MODULE_21__["PLUGINS_STORE_NAME"]),
      getActivePlugins = _select2.getActivePlugins,
      isJetpackConnected = _select2.isJetpackConnected;

  var paypalOptions = getOption('woocommerce_ppec_paypal_settings');
  var wcsOptions = getOption('wc_connect_options');
  var activePlugins = getActivePlugins();
  return {
    activePlugins: activePlugins,
    isJetpackConnected: isJetpackConnected(),
    isOptionsUpdating: isOptionsUpdating(),
    options: paypalOptions,
    wcsTosAccepted: wcsOptions && wcsOptions.tos_accepted
  };
}), Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_16__["withDispatch"])(function (dispatch) {
  var _dispatch = dispatch('core/notices'),
      createNotice = _dispatch.createNotice;

  var _dispatch2 = dispatch(_woocommerce_data__WEBPACK_IMPORTED_MODULE_21__["OPTIONS_STORE_NAME"]),
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
/* harmony import */ var _babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/regenerator */ "./node_modules/@babel/runtime/regenerator/index.js");
/* harmony import */ var _babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0__);
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
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_13___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_13__);
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! @wordpress/compose */ "./node_modules/@wordpress/compose/build-module/index.js");
/* harmony import */ var _woocommerce_navigation__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(/*! @woocommerce/navigation */ "@woocommerce/navigation");
/* harmony import */ var _woocommerce_navigation__WEBPACK_IMPORTED_MODULE_15___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_15__);
/* harmony import */ var wc_api_constants__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__(/*! wc-api/constants */ "./client/wc-api/constants.js");
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_17__ = __webpack_require__(/*! @woocommerce/components */ "@woocommerce/components");
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_17___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_components__WEBPACK_IMPORTED_MODULE_17__);
/* harmony import */ var _woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_18__ = __webpack_require__(/*! @woocommerce/wc-admin-settings */ "./client/settings/index.js");
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







var Square = /*#__PURE__*/function (_Component) {
  _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_6___default()(Square, _Component);

  var _super = _createSuper(Square);

  function Square(props) {
    var _this;

    _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_3___default()(this, Square);

    _this = _super.call(this, props);
    _this.state = {
      isPending: false
    };
    _this.connect = _this.connect.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_5___default()(_this));
    return _this;
  }

  _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_4___default()(Square, [{
    key: "componentDidMount",
    value: function componentDidMount() {
      var _this$props = this.props,
          createNotice = _this$props.createNotice,
          markConfigured = _this$props.markConfigured;
      var query = Object(_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_15__["getQuery"])(); // Handle redirect back from Square

      if (query['square-connect']) {
        if (query['square-connect'] === '1') {
          createNotice('success', Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_10__["__"])('Square connected successfully.', 'woocommerce'));
          markConfigured('square');
        }
      }
    }
  }, {
    key: "connect",
    value: function () {
      var _connect = _babel_runtime_helpers_asyncToGenerator__WEBPACK_IMPORTED_MODULE_2___default()( /*#__PURE__*/_babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0___default.a.mark(function _callee() {
        var _this$props2, createNotice, hasCbdIndustry, options, updateOptions, errorMessage, newWindow, result;

        return _babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0___default.a.wrap(function _callee$(_context) {
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
                errorMessage = Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_10__["__"])('There was an error connecting to Square. Please try again or skip to connect later in store settings.', 'woocommerce');
                _context.prev = 4;
                newWindow = null;

                if (hasCbdIndustry) {
                  // It's necessary to declare the new tab before the async call,
                  // otherwise, it won't be possible to open it.
                  newWindow = window.open('/', '_blank');
                }

                _context.next = 9;
                return _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_11___default()({
                  path: wc_api_constants__WEBPACK_IMPORTED_MODULE_16__["WC_ADMIN_NAMESPACE"] + '/plugins/connect-square',
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
        window.location = Object(_woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_18__["getAdminLink"])('admin.php?page=wc-admin');
      } else {
        window.location = connectUrl;
      }
    }
  }, {
    key: "render",
    value: function render() {
      var installStep = this.props.installStep;
      var isPending = this.state.isPending;
      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_17__["Stepper"], {
        isVertical: true,
        isPending: !installStep.isComplete || isPending,
        currentStep: installStep.isComplete ? 'connect' : 'install',
        steps: [installStep, {
          key: 'connect',
          label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_10__["__"])('Connect your Square account', 'woocommerce'),
          description: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_10__["__"])('A Square account is required to process payments. You will be redirected to the Square website to create the connection.', 'woocommerce'),
          content: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["createElement"])(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["Fragment"], null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_12__["Button"], {
            isPrimary: true,
            isBusy: isPending,
            onClick: this.connect
          }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_10__["__"])('Connect', 'woocommerce')))
        }]
      });
    }
  }]);

  return Square;
}(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["Component"]);

/* harmony default export */ __webpack_exports__["default"] = (Object(_wordpress_compose__WEBPACK_IMPORTED_MODULE_14__["compose"])(Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_13__["withSelect"])(function (select) {
  var _select = select(_woocommerce_data__WEBPACK_IMPORTED_MODULE_19__["OPTIONS_STORE_NAME"]),
      getOption = _select.getOption,
      isResolving = _select.isResolving;

  var options = getOption('woocommerce_square_credit_card_settings');
  var optionsIsRequesting = isResolving('getOption', ['woocommerce_square_credit_card_settings']);
  return {
    options: options,
    optionsIsRequesting: optionsIsRequesting
  };
}), Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_13__["withDispatch"])(function (dispatch) {
  var _dispatch = dispatch('core/notices'),
      createNotice = _dispatch.createNotice;

  var _dispatch2 = dispatch(_woocommerce_data__WEBPACK_IMPORTED_MODULE_19__["OPTIONS_STORE_NAME"]),
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
/* harmony import */ var _babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @babel/runtime/regenerator */ "./node_modules/@babel/runtime/regenerator/index.js");
/* harmony import */ var _babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _babel_runtime_helpers_asyncToGenerator__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @babel/runtime/helpers/asyncToGenerator */ "./node_modules/@babel/runtime/helpers/asyncToGenerator.js");
/* harmony import */ var _babel_runtime_helpers_asyncToGenerator__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_asyncToGenerator__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @babel/runtime/helpers/classCallCheck */ "./node_modules/@babel/runtime/helpers/classCallCheck.js");
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @babel/runtime/helpers/createClass */ "./node_modules/@babel/runtime/helpers/createClass.js");
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @babel/runtime/helpers/assertThisInitialized */ "./node_modules/@babel/runtime/helpers/assertThisInitialized.js");
/* harmony import */ var _babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @babel/runtime/helpers/inherits */ "./node_modules/@babel/runtime/helpers/inherits.js");
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! @babel/runtime/helpers/possibleConstructorReturn */ "./node_modules/@babel/runtime/helpers/possibleConstructorReturn.js");
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_8__);
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! @babel/runtime/helpers/getPrototypeOf */ "./node_modules/@babel/runtime/helpers/getPrototypeOf.js");
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_9__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_10___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_11___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__);
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! @wordpress/compose */ "./node_modules/@wordpress/compose/build-module/index.js");
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! @wordpress/api-fetch */ "@wordpress/api-fetch");
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_13___default = /*#__PURE__*/__webpack_require__.n(_wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_13__);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_14___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_14__);
/* harmony import */ var interpolate_components__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(/*! interpolate-components */ "./node_modules/interpolate-components/lib/index.js");
/* harmony import */ var interpolate_components__WEBPACK_IMPORTED_MODULE_15___default = /*#__PURE__*/__webpack_require__.n(interpolate_components__WEBPACK_IMPORTED_MODULE_15__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__(/*! @wordpress/components */ "./node_modules/@wordpress/components/build-module/index.js");
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_17__ = __webpack_require__(/*! @woocommerce/components */ "@woocommerce/components");
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_17___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_components__WEBPACK_IMPORTED_MODULE_17__);
/* harmony import */ var _woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_18__ = __webpack_require__(/*! @woocommerce/wc-admin-settings */ "./client/settings/index.js");
/* harmony import */ var _woocommerce_navigation__WEBPACK_IMPORTED_MODULE_19__ = __webpack_require__(/*! @woocommerce/navigation */ "@woocommerce/navigation");
/* harmony import */ var _woocommerce_navigation__WEBPACK_IMPORTED_MODULE_19___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_19__);
/* harmony import */ var wc_api_constants__WEBPACK_IMPORTED_MODULE_20__ = __webpack_require__(/*! wc-api/constants */ "./client/wc-api/constants.js");
/* harmony import */ var _woocommerce_data__WEBPACK_IMPORTED_MODULE_21__ = __webpack_require__(/*! @woocommerce/data */ "@woocommerce/data");
/* harmony import */ var _woocommerce_data__WEBPACK_IMPORTED_MODULE_21___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_data__WEBPACK_IMPORTED_MODULE_21__);












function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_1___default()(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_9___default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_9___default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_8___default()(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/**
 * External dependencies
 */







/**
 * WooCommerce dependencies
 */







var Stripe = /*#__PURE__*/function (_Component) {
  _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_7___default()(Stripe, _Component);

  var _super = _createSuper(Stripe);

  function Stripe(props) {
    var _this;

    _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_4___default()(this, Stripe);

    _this = _super.call(this, props);
    _this.state = {
      oAuthConnectFailed: false,
      connectURL: null,
      isPending: false
    };
    _this.updateSettings = _this.updateSettings.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_6___default()(_this));
    return _this;
  }

  _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_5___default()(Stripe, [{
    key: "componentDidMount",
    value: function componentDidMount() {
      var stripeSettings = this.props.stripeSettings;
      var query = Object(_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_19__["getQuery"])(); // Handle redirect back from Stripe.

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
      createNotice('success', Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__["__"])('Stripe connected successfully.', 'woocommerce'));
      markConfigured('stripe');
    }
  }, {
    key: "fetchOAuthConnectURL",
    value: function () {
      var _fetchOAuthConnectURL = _babel_runtime_helpers_asyncToGenerator__WEBPACK_IMPORTED_MODULE_3___default()( /*#__PURE__*/_babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_2___default.a.mark(function _callee() {
        var activePlugins, result;
        return _babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_2___default.a.wrap(function _callee$(_context) {
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
                return _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_13___default()({
                  path: wc_api_constants__WEBPACK_IMPORTED_MODULE_20__["WCS_NAMESPACE"] + '/connect/stripe/oauth/init',
                  method: 'POST',
                  data: {
                    returnUrl: Object(_woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_18__["getAdminLink"])('admin.php?page=wc-admin&task=payments&method=stripe&stripe-connect=1')
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
      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_16__["Button"], {
        isPrimary: true,
        href: connectURL
      }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__["__"])('Connect', 'woocommerce'));
    }
  }, {
    key: "updateSettings",
    value: function () {
      var _updateSettings = _babel_runtime_helpers_asyncToGenerator__WEBPACK_IMPORTED_MODULE_3___default()( /*#__PURE__*/_babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_2___default.a.mark(function _callee2(values) {
        var _this$props3, updateOptions, stripeSettings, createNotice, update;

        return _babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_2___default.a.wrap(function _callee2$(_context2) {
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
                  createNotice('error', Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__["__"])('There was a problem saving your payment setings', 'woocommerce'));
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
        errors.publishable_key = Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__["__"])('Please enter a valid publishable key. Valid keys start with "pk_live".', 'woocommerce');
      }

      if (values.secret_key.match(/^[rs]k_live_/) === null) {
        errors.secret_key = Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__["__"])('Please enter a valid secret key. Valid keys start with "sk_live" or "rk_live".', 'woocommerce');
      }

      return errors;
    }
  }, {
    key: "renderManualConfig",
    value: function renderManualConfig() {
      var isOptionsUpdating = this.props.isOptionsUpdating;
      var stripeHelp = interpolate_components__WEBPACK_IMPORTED_MODULE_15___default()({
        mixedString: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__["__"])('Your API details can be obtained from your {{docsLink}}Stripe account{{/docsLink}}. Don’t have a Stripe account? {{registerLink}}Create one.{{/registerLink}}', 'woocommerce'),
        components: {
          docsLink: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_17__["Link"], {
            href: "https://stripe.com/docs/keys",
            target: "_blank",
            type: "external"
          }),
          registerLink: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_17__["Link"], {
            href: "https://dashboard.stripe.com/register",
            target: "_blank",
            type: "external"
          })
        }
      });
      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_17__["Form"], {
        initialValues: this.getInitialConfigValues(),
        onSubmitCallback: this.updateSettings,
        validate: this.validateManualConfig
      }, function (_ref) {
        var getInputProps = _ref.getInputProps,
            handleSubmit = _ref.handleSubmit;
        return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__["createElement"])(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__["Fragment"], null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_17__["TextControl"], _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0___default()({
          label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__["__"])('Live Publishable Key', 'woocommerce'),
          required: true
        }, getInputProps('publishable_key'))), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_17__["TextControl"], _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0___default()({
          label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__["__"])('Live Secret Key', 'woocommerce'),
          required: true
        }, getInputProps('secret_key'))), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_16__["Button"], {
          isPrimary: true,
          isBusy: isOptionsUpdating,
          onClick: handleSubmit
        }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__["__"])('Proceed', 'woocommerce')), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__["createElement"])("p", null, stripeHelp));
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
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__["__"])('Connect your Stripe account', 'woocommerce')
      };

      if (isPending) {
        return connectStep;
      }

      if (!oAuthConnectFailed && connectURL) {
        return _objectSpread(_objectSpread({}, connectStep), {}, {
          description: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__["__"])('A Stripe account is required to process payments.', 'woocommerce'),
          content: this.renderConnectButton()
        });
      }

      return _objectSpread(_objectSpread({}, connectStep), {}, {
        description: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__["__"])('Connect your store to your Stripe account. Don’t have a Stripe account? Create one.', 'woocommerce'),
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
      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_17__["Stepper"], {
        isVertical: true,
        isPending: !installStep.isComplete || isOptionsUpdating || isPending,
        currentStep: installStep.isComplete ? 'connect' : 'install',
        steps: [installStep, this.getConnectStep()]
      });
    }
  }]);

  return Stripe;
}(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__["Component"]);

/* harmony default export */ __webpack_exports__["default"] = (Object(_wordpress_compose__WEBPACK_IMPORTED_MODULE_12__["compose"])(Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_14__["withSelect"])(function (select) {
  var _select = select(_woocommerce_data__WEBPACK_IMPORTED_MODULE_21__["OPTIONS_STORE_NAME"]),
      getOption = _select.getOption,
      isOptionsUpdating = _select.isOptionsUpdating;

  var _select2 = select(_woocommerce_data__WEBPACK_IMPORTED_MODULE_21__["PLUGINS_STORE_NAME"]),
      getActivePlugins = _select2.getActivePlugins,
      isJetpackConnected = _select2.isJetpackConnected;

  return {
    activePlugins: getActivePlugins(),
    isJetpackConnected: isJetpackConnected(),
    isOptionsUpdating: isOptionsUpdating(),
    stripeSettings: getOption('woocommerce_stripe_settings') || []
  };
}), Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_14__["withDispatch"])(function (dispatch) {
  var _dispatch = dispatch('core/notices'),
      createNotice = _dispatch.createNotice;

  var _dispatch2 = dispatch(_woocommerce_data__WEBPACK_IMPORTED_MODULE_21__["OPTIONS_STORE_NAME"]),
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

/***/ })

}]);
//# sourceMappingURL=activity-panels-help~task-list.a4d3c8efd1e3254da579.min.js.map
