(window["__wcAdmin_webpackJsonp"] = window["__wcAdmin_webpackJsonp"] || []).push([["task-list"],{

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

/***/ "./client/dashboard/components/connect/index.js":
/*!******************************************************!*\
  !*** ./client/dashboard/components/connect/index.js ***!
  \******************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/regenerator */ "./node_modules/@babel/runtime/regenerator/index.js");
/* harmony import */ var _babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0__);
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
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! @wordpress/components */ "./node_modules/@wordpress/components/build-module/index.js");
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! @wordpress/compose */ "./node_modules/@wordpress/compose/build-module/index.js");
/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! prop-types */ "./node_modules/prop-types/index.js");
/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_12___default = /*#__PURE__*/__webpack_require__.n(prop_types__WEBPACK_IMPORTED_MODULE_12__);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_13___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_13__);
/* harmony import */ var _woocommerce_data__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! @woocommerce/data */ "@woocommerce/data");
/* harmony import */ var _woocommerce_data__WEBPACK_IMPORTED_MODULE_14___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_data__WEBPACK_IMPORTED_MODULE_14__);










function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_7___default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_7___default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_6___default()(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/**
 * External dependencies
 */






/**
 * WooCommerce dependencies
 */



var Connect = /*#__PURE__*/function (_Component) {
  _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_5___default()(Connect, _Component);

  var _super = _createSuper(Connect);

  function Connect(props) {
    var _this;

    _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_2___default()(this, Connect);

    _this = _super.call(this, props);
    _this.state = {
      isConnecting: false
    };
    _this.connectJetpack = _this.connectJetpack.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_4___default()(_this));
    props.setIsPending(true);
    return _this;
  }

  _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_3___default()(Connect, [{
    key: "componentDidUpdate",
    value: function componentDidUpdate(prevProps) {
      var _this$props = this.props,
          createNotice = _this$props.createNotice,
          error = _this$props.error,
          isRequesting = _this$props.isRequesting,
          onError = _this$props.onError,
          setIsPending = _this$props.setIsPending;

      if (prevProps.isRequesting && !isRequesting) {
        setIsPending(false);
      }

      if (error && error !== prevProps.error) {
        if (onError) {
          onError();
        }

        createNotice('error', error);
      }
    }
  }, {
    key: "connectJetpack",
    value: function () {
      var _connectJetpack = _babel_runtime_helpers_asyncToGenerator__WEBPACK_IMPORTED_MODULE_1___default()( /*#__PURE__*/_babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0___default.a.mark(function _callee() {
        var _this$props2, jetpackConnectUrl, onConnect;

        return _babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0___default.a.wrap(function _callee$(_context) {
          while (1) {
            switch (_context.prev = _context.next) {
              case 0:
                _this$props2 = this.props, jetpackConnectUrl = _this$props2.jetpackConnectUrl, onConnect = _this$props2.onConnect;
                this.setState({
                  isConnecting: true
                }, function () {
                  if (onConnect) {
                    onConnect();
                  }

                  window.location = jetpackConnectUrl;
                });

              case 2:
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
      var _this$props3 = this.props,
          hasErrors = _this$props3.hasErrors,
          isRequesting = _this$props3.isRequesting,
          onSkip = _this$props3.onSkip,
          skipText = _this$props3.skipText;
      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_8__["createElement"])(_wordpress_element__WEBPACK_IMPORTED_MODULE_8__["Fragment"], null, hasErrors ? Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_8__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_10__["Button"], {
        isPrimary: true,
        onClick: function onClick() {
          return window.location.reload();
        }
      }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_9__["__"])('Retry', 'woocommerce')) : Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_8__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_10__["Button"], {
        disabled: isRequesting,
        isBusy: this.state.isConnecting,
        isPrimary: true,
        onClick: this.connectJetpack
      }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_9__["__"])('Connect', 'woocommerce')), onSkip && Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_8__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_10__["Button"], {
        onClick: onSkip
      }, skipText || Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_9__["__"])('No thanks', 'woocommerce')));
    }
  }]);

  return Connect;
}(_wordpress_element__WEBPACK_IMPORTED_MODULE_8__["Component"]);

Connect.propTypes = {
  /**
   * Method to create a displayed notice.
   */
  createNotice: prop_types__WEBPACK_IMPORTED_MODULE_12___default.a.func.isRequired,

  /**
   * Human readable error message.
   */
  error: prop_types__WEBPACK_IMPORTED_MODULE_12___default.a.string,

  /**
   * Bool to determine if the "Retry" button should be displayed.
   */
  hasErrors: prop_types__WEBPACK_IMPORTED_MODULE_12___default.a.bool,

  /**
   * Bool to check if the connection URL is still being requested.
   */
  isRequesting: prop_types__WEBPACK_IMPORTED_MODULE_12___default.a.bool,

  /**
   * Generated Jetpack connection URL.
   */
  jetpackConnectUrl: prop_types__WEBPACK_IMPORTED_MODULE_12___default.a.string,

  /**
   * Called before the redirect to Jetpack.
   */
  onConnect: prop_types__WEBPACK_IMPORTED_MODULE_12___default.a.func,

  /**
   * Called when the plugin has an error retrieving the jetpackConnectUrl.
   */
  onError: prop_types__WEBPACK_IMPORTED_MODULE_12___default.a.func,

  /**
   * Called when the plugin connection is skipped.
   */
  onSkip: prop_types__WEBPACK_IMPORTED_MODULE_12___default.a.func,

  /**
   * Redirect URL to encode as a URL param for the connection path.
   */
  redirectUrl: prop_types__WEBPACK_IMPORTED_MODULE_12___default.a.string,

  /**
   * Text used for the skip connection button.
   */
  skipText: prop_types__WEBPACK_IMPORTED_MODULE_12___default.a.string,

  /**
   * Control the `isPending` logic of the parent containing the Stepper.
   */
  setIsPending: prop_types__WEBPACK_IMPORTED_MODULE_12___default.a.func
};
Connect.defaultProps = {
  setIsPending: function setIsPending() {}
};
/* harmony default export */ __webpack_exports__["default"] = (Object(_wordpress_compose__WEBPACK_IMPORTED_MODULE_11__["compose"])(Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_13__["withSelect"])(function (select, props) {
  var _select = select(_woocommerce_data__WEBPACK_IMPORTED_MODULE_14__["PLUGINS_STORE_NAME"]),
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
}), Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_13__["withDispatch"])(function (dispatch) {
  var _dispatch = dispatch('core/notices'),
      createNotice = _dispatch.createNotice;

  return {
    createNotice: createNotice
  };
}))(Connect));

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
/* harmony import */ var _babel_runtime_helpers_toConsumableArray__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/toConsumableArray */ "./node_modules/@babel/runtime/helpers/toConsumableArray.js");
/* harmony import */ var _babel_runtime_helpers_toConsumableArray__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_toConsumableArray__WEBPACK_IMPORTED_MODULE_0__);
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
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! @wordpress/compose */ "./node_modules/@wordpress/compose/build-module/index.js");
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! classnames */ "./node_modules/classnames/index.js");
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(classnames__WEBPACK_IMPORTED_MODULE_9__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! @wordpress/components */ "./node_modules/@wordpress/components/build-module/index.js");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_11___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_11__);
/* harmony import */ var _wordpress_icons__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! @wordpress/icons */ "./node_modules/@wordpress/icons/build-module/index.js");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! lodash */ "lodash");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_13___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_13__);
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
/* harmony import */ var dashboard_utils__WEBPACK_IMPORTED_MODULE_20__ = __webpack_require__(/*! dashboard/utils */ "./client/dashboard/utils.js");
/* harmony import */ var lib_tracks__WEBPACK_IMPORTED_MODULE_21__ = __webpack_require__(/*! lib/tracks */ "./client/lib/tracks.js");








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
      isCartModalOpen: false
    };
    return _this;
  }

  _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_2___default()(TaskDashboard, [{
    key: "componentDidMount",
    value: function componentDidMount() {
      document.body.classList.add('woocommerce-onboarding');
      document.body.classList.add('woocommerce-task-dashboard__body');
      this.recordTaskView();
      this.recordTaskListView();
      this.possiblyCompleteTaskList();
      this.possiblyTrackCompletedTasks();
    }
  }, {
    key: "componentDidUpdate",
    value: function componentDidUpdate(prevProps) {
      var query = this.props.query;
      var prevQuery = prevProps.query;
      var prevTask = prevQuery.task;
      var task = query.task;

      if (prevTask !== task) {
        window.document.documentElement.scrollTop = 0;
        this.recordTaskView();
      }

      this.possiblyCompleteTaskList();
      this.possiblyTrackCompletedTasks();
    }
  }, {
    key: "possiblyCompleteTaskList",
    value: function possiblyCompleteTaskList() {
      var _this$props = this.props,
          isTaskListComplete = _this$props.isTaskListComplete,
          updateOptions = _this$props.updateOptions;

      if (!this.getIncompleteTasks().length && !isTaskListComplete) {
        updateOptions({
          woocommerce_task_list_complete: 'yes'
        });
      }
    }
  }, {
    key: "getCompletedTaskKeys",
    value: function getCompletedTaskKeys() {
      return this.getVisibleTasks().filter(function (task) {
        return task.completed;
      }).map(function (task) {
        return task.key;
      });
    }
  }, {
    key: "getIncompleteTasks",
    value: function getIncompleteTasks() {
      return this.getAllTasks().filter(function (task) {
        return task.visible && !task.completed;
      });
    }
  }, {
    key: "possiblyTrackCompletedTasks",
    value: function possiblyTrackCompletedTasks() {
      var _this$props2 = this.props,
          trackedCompletedTasks = _this$props2.trackedCompletedTasks,
          updateOptions = _this$props2.updateOptions;
      var completedTaskKeys = this.getCompletedTaskKeys();

      if (Object(lodash__WEBPACK_IMPORTED_MODULE_13__["xor"])(trackedCompletedTasks, completedTaskKeys).length !== 0) {
        updateOptions({
          woocommerce_task_list_tracked_completed_tasks: completedTaskKeys
        });
      }
    }
  }, {
    key: "dismissTask",
    value: function dismissTask(key) {
      var _this2 = this;

      var _this$props3 = this.props,
          createNotice = _this$props3.createNotice,
          dismissedTasks = _this$props3.dismissedTasks,
          updateOptions = _this$props3.updateOptions;
      createNotice('success', Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__["__"])('Task dismissed'), {
        actions: [{
          label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__["__"])('Undo', 'woocommerce'),
          onClick: function onClick() {
            return _this2.undoDismissTask(key);
          }
        }]
      });
      Object(lib_tracks__WEBPACK_IMPORTED_MODULE_21__["recordEvent"])('tasklist_dismiss_task', {
        task_name: key
      });
      updateOptions({
        woocommerce_task_list_dismissed_tasks: [].concat(_babel_runtime_helpers_toConsumableArray__WEBPACK_IMPORTED_MODULE_0___default()(dismissedTasks), [key])
      });
    }
  }, {
    key: "undoDismissTask",
    value: function undoDismissTask(key) {
      var _this$props4 = this.props,
          dismissedTasks = _this$props4.dismissedTasks,
          updateOptions = _this$props4.updateOptions;
      var updatedDismissedTasks = dismissedTasks.filter(function (task) {
        return task !== key;
      });
      updateOptions({
        woocommerce_task_list_dismissed_tasks: updatedDismissedTasks
      });
    }
  }, {
    key: "componentWillUnmount",
    value: function componentWillUnmount() {
      document.body.classList.remove('woocommerce-onboarding');
      document.body.classList.remove('woocommerce-task-dashboard__body');
    }
  }, {
    key: "getAllTasks",
    value: function getAllTasks() {
      var _this$props5 = this.props,
          countryCode = _this$props5.countryCode,
          profileItems = _this$props5.profileItems,
          query = _this$props5.query,
          taskListPayments = _this$props5.taskListPayments,
          activePlugins = _this$props5.activePlugins,
          installedPlugins = _this$props5.installedPlugins,
          installAndActivatePlugins = _this$props5.installAndActivatePlugins,
          createNotice = _this$props5.createNotice,
          isJetpackConnected = _this$props5.isJetpackConnected;
      return Object(_tasks__WEBPACK_IMPORTED_MODULE_19__["getAllTasks"])({
        countryCode: countryCode,
        profileItems: profileItems,
        taskListPayments: taskListPayments,
        query: query,
        toggleCartModal: this.toggleCartModal.bind(this),
        activePlugins: activePlugins,
        installedPlugins: installedPlugins,
        installAndActivatePlugins: installAndActivatePlugins,
        createNotice: createNotice,
        isJetpackConnected: isJetpackConnected
      });
    }
  }, {
    key: "getVisibleTasks",
    value: function getVisibleTasks() {
      var dismissedTasks = this.props.dismissedTasks;
      return this.getAllTasks().filter(function (task) {
        return task.visible && !dismissedTasks.includes(task.key);
      });
    }
  }, {
    key: "recordTaskView",
    value: function recordTaskView() {
      var _this$props6 = this.props,
          isJetpackConnected = _this$props6.isJetpackConnected,
          activePlugins = _this$props6.activePlugins,
          installedPlugins = _this$props6.installedPlugins,
          query = _this$props6.query;
      var taskName = query.task;

      if (!taskName) {
        return;
      }

      Object(_tasks__WEBPACK_IMPORTED_MODULE_19__["recordTaskViewEvent"])(taskName, isJetpackConnected, activePlugins, installedPlugins);
    }
  }, {
    key: "recordTaskListView",
    value: function recordTaskListView() {
      if (this.getCurrentTask()) {
        return;
      }

      var profileItems = this.props.profileItems;
      var tasks = this.getVisibleTasks();
      Object(lib_tracks__WEBPACK_IMPORTED_MODULE_21__["recordEvent"])('tasklist_view', {
        number_tasks: tasks.length,
        store_connected: profileItems.wccom_connected
      });
    }
  }, {
    key: "keepTaskCard",
    value: function keepTaskCard() {
      Object(lib_tracks__WEBPACK_IMPORTED_MODULE_21__["recordEvent"])('tasklist_completed', {
        action: 'keep_card'
      });
      this.props.updateOptions({
        woocommerce_task_list_prompt_shown: true
      });
    }
  }, {
    key: "hideTaskCard",
    value: function hideTaskCard(action) {
      Object(lib_tracks__WEBPACK_IMPORTED_MODULE_21__["recordEvent"])('tasklist_completed', {
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
      var query = this.props.query;
      var task = query.task;
      var currentTask = this.getAllTasks().find(function (s) {
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
      var _this3 = this;

      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("div", {
        className: "woocommerce-card__menu woocommerce-card__header-item"
      }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_14__["EllipsisMenu"], {
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__["__"])('Task List Options', 'woocommerce'),
        renderContent: function renderContent() {
          return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("div", {
            className: "woocommerce-task-card__section-controls"
          }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_10__["Button"], {
            onClick: function onClick() {
              return _this3.hideTaskCard('remove_card');
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
        Object(lib_tracks__WEBPACK_IMPORTED_MODULE_21__["recordEvent"])('tasklist_purchase_extensions');
      }

      this.setState({
        isCartModalOpen: !isCartModalOpen
      });
    }
  }, {
    key: "render",
    value: function render() {
      var _this4 = this;

      var query = this.props.query;
      var isCartModalOpen = this.state.isCartModalOpen;
      var currentTask = this.getCurrentTask();
      var listTasks = this.getVisibleTasks().map(function (task) {
        task.className = classnames__WEBPACK_IMPORTED_MODULE_9___default()(task.completed ? 'is-complete' : null, task.className);
        task.before = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("div", {
          className: "woocommerce-task__icon"
        }, task.completed && Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])(_wordpress_icons__WEBPACK_IMPORTED_MODULE_12__["Icon"], {
          icon: _wordpress_icons__WEBPACK_IMPORTED_MODULE_12__["check"]
        }));
        task.title = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_10__["__experimentalText"], {
          as: "div",
          variant: task.completed ? 'body.small' : 'button'
        }, task.title, task.time && !task.completed && Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("span", {
          className: "woocommerce-task__estimated-time"
        }, task.time));

        if (!task.completed) {
          task.after = task.isDismissable ? Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_10__["Button"], {
            isTertiary: true,
            onClick: function onClick(event) {
              event.stopPropagation();

              _this4.dismissTask(task.key);
            }
          }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__["__"])('Dismiss', 'woocommerce')) : Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])(_wordpress_icons__WEBPACK_IMPORTED_MODULE_12__["Icon"], {
            icon: _wordpress_icons__WEBPACK_IMPORTED_MODULE_12__["chevronRight"]
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
      var progressBarClass = classnames__WEBPACK_IMPORTED_MODULE_9___default()('woocommerce-task-card__progress-bar', {
        completed: listTasks.length === this.getCompletedTaskKeys().length
      });
      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["Fragment"], null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("div", {
        className: "woocommerce-task-dashboard__container"
      }, currentTask ? Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["cloneElement"])(currentTask.container, {
        query: query
      }) : Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["Fragment"], null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_10__["Card"], {
        size: "large",
        className: "woocommerce-task-card woocommerce-dashboard-card"
      }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("progress", {
        className: progressBarClass,
        max: listTasks.length,
        value: this.getCompletedTaskKeys().length
      }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_10__["CardHeader"], {
        size: "medium"
      }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_10__["__experimentalText"], {
        variant: "title.small"
      }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__["__"])('Finish setup', 'woocommerce')), this.renderMenu()), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_10__["CardBody"], null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_14__["List"], {
        items: listTasks
      }))))), isCartModalOpen && Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])(dashboard_components_cart_modal__WEBPACK_IMPORTED_MODULE_18__["default"], {
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

/* harmony default export */ __webpack_exports__["default"] = (Object(_wordpress_compose__WEBPACK_IMPORTED_MODULE_8__["compose"])(Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_11__["withSelect"])(function (select) {
  var _select = select(_woocommerce_data__WEBPACK_IMPORTED_MODULE_16__["ONBOARDING_STORE_NAME"]),
      getProfileItems = _select.getProfileItems;

  var _select2 = select(_woocommerce_data__WEBPACK_IMPORTED_MODULE_16__["OPTIONS_STORE_NAME"]),
      getOption = _select2.getOption;

  var _select3 = select(_woocommerce_data__WEBPACK_IMPORTED_MODULE_16__["SETTINGS_STORE_NAME"]),
      getSettings = _select3.getSettings;

  var _select4 = select(_woocommerce_data__WEBPACK_IMPORTED_MODULE_16__["PLUGINS_STORE_NAME"]),
      getActivePlugins = _select4.getActivePlugins,
      getInstalledPlugins = _select4.getInstalledPlugins,
      isJetpackConnected = _select4.isJetpackConnected;

  var profileItems = getProfileItems();
  var isTaskListComplete = getOption('woocommerce_task_list_complete') || false;
  var taskListPayments = getOption('woocommerce_task_list_payments');
  var trackedCompletedTasks = getOption('woocommerce_task_list_tracked_completed_tasks') || [];
  var payments = getOption('woocommerce_task_list_payments');
  var dismissedTasks = getOption('woocommerce_task_list_dismissed_tasks') || [];

  var _getSettings = getSettings('general'),
      _getSettings$general = _getSettings.general,
      generalSettings = _getSettings$general === void 0 ? {} : _getSettings$general;

  var countryCode = Object(dashboard_utils__WEBPACK_IMPORTED_MODULE_20__["getCountryCode"])(generalSettings.woocommerce_default_country);
  var activePlugins = getActivePlugins();
  var installedPlugins = getInstalledPlugins();
  return {
    activePlugins: activePlugins,
    countryCode: countryCode,
    dismissedTasks: dismissedTasks,
    isJetpackConnected: isJetpackConnected(),
    installedPlugins: installedPlugins,
    isTaskListComplete: isTaskListComplete,
    payments: payments,
    profileItems: profileItems,
    taskListPayments: taskListPayments,
    trackedCompletedTasks: trackedCompletedTasks
  };
}), Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_11__["withDispatch"])(function (dispatch) {
  var _dispatch = dispatch('core/notices'),
      createNotice = _dispatch.createNotice;

  var _dispatch2 = dispatch(_woocommerce_data__WEBPACK_IMPORTED_MODULE_16__["OPTIONS_STORE_NAME"]),
      updateOptions = _dispatch2.updateOptions;

  var _dispatch3 = dispatch(_woocommerce_data__WEBPACK_IMPORTED_MODULE_16__["PLUGINS_STORE_NAME"]),
      installAndActivatePlugins = _dispatch3.installAndActivatePlugins;

  return {
    createNotice: createNotice,
    installAndActivatePlugins: installAndActivatePlugins,
    updateOptions: updateOptions
  };
}))(TaskDashboard));

/***/ }),

/***/ "./client/task-list/tasks.js":
/*!***********************************!*\
  !*** ./client/task-list/tasks.js ***!
  \***********************************/
/*! exports provided: recordTaskViewEvent, getAllTasks */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "recordTaskViewEvent", function() { return recordTaskViewEvent; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "getAllTasks", function() { return getAllTasks; });
/* harmony import */ var _babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/regenerator */ "./node_modules/@babel/runtime/regenerator/index.js");
/* harmony import */ var _babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _babel_runtime_helpers_asyncToGenerator__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/asyncToGenerator */ "./node_modules/@babel/runtime/helpers/asyncToGenerator.js");
/* harmony import */ var _babel_runtime_helpers_asyncToGenerator__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_asyncToGenerator__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _wordpress_hooks__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @wordpress/hooks */ "@wordpress/hooks");
/* harmony import */ var _wordpress_hooks__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_wordpress_hooks__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @woocommerce/wc-admin-settings */ "./client/settings/index.js");
/* harmony import */ var _woocommerce_navigation__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @woocommerce/navigation */ "@woocommerce/navigation");
/* harmony import */ var _woocommerce_navigation__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var _tasks_appearance__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ./tasks/appearance */ "./client/task-list/tasks/appearance.js");
/* harmony import */ var dashboard_utils__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! dashboard/utils */ "./client/dashboard/utils.js");
/* harmony import */ var _tasks_products__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ./tasks/products */ "./client/task-list/tasks/products.js");
/* harmony import */ var _tasks_shipping__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! ./tasks/shipping */ "./client/task-list/tasks/shipping/index.js");
/* harmony import */ var _tasks_tax__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! ./tasks/tax */ "./client/task-list/tasks/tax.js");
/* harmony import */ var _tasks_payments__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! ./tasks/payments */ "./client/task-list/tasks/payments/index.js");
/* harmony import */ var _tasks_payments_methods__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! ./tasks/payments/methods */ "./client/task-list/tasks/payments/methods.js");
/* harmony import */ var lib_tracks__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! lib/tracks */ "./client/lib/tracks.js");




/**
 * External dependencies
 */


/**
 * WooCommerce dependencies
 */




/**
 * Internal dependencies
 */









function recordTaskViewEvent(taskName, isJetpackConnected, activePlugins, installedPlugins) {
  Object(lib_tracks__WEBPACK_IMPORTED_MODULE_14__["recordEvent"])('task_view', {
    task_name: taskName,
    wcs_installed: installedPlugins.includes('woocommerce-services'),
    wcs_active: activePlugins.includes('woocommerce-services'),
    jetpack_installed: installedPlugins.includes('jetpack'),
    jetpack_active: activePlugins.includes('jetpack'),
    jetpack_connected: isJetpackConnected
  });
}
function getAllTasks(_ref) {
  var countryCode = _ref.countryCode,
      profileItems = _ref.profileItems,
      taskListPayments = _ref.taskListPayments,
      query = _ref.query,
      toggleCartModal = _ref.toggleCartModal,
      activePlugins = _ref.activePlugins,
      installedPlugins = _ref.installedPlugins,
      installAndActivatePlugins = _ref.installAndActivatePlugins,
      createNotice = _ref.createNotice,
      isJetpackConnected = _ref.isJetpackConnected;

  var _getSetting = Object(_woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_5__["getSetting"])('onboarding', {
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

  var groupedProducts = Object(dashboard_utils__WEBPACK_IMPORTED_MODULE_8__["getCategorizedOnboardingProducts"])(profileItems, installedPlugins);
  var products = groupedProducts.products,
      remainingProducts = groupedProducts.remainingProducts,
      uniqueItemsList = groupedProducts.uniqueItemsList;
  var paymentsCompleted = Boolean(taskListPayments && taskListPayments.completed);
  var paymentsSkipped = Boolean(taskListPayments && taskListPayments.skipped);
  var woocommercePaymentsInstalled = installedPlugins.indexOf('woocommerce-payments') !== -1;
  var profilerCompleted = profileItems.completed,
      productTypes = profileItems.product_types;

  var purchaseAndInstallText = Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__["__"])('Purchase & install extensions');

  if (uniqueItemsList.length === 1) {
    var _uniqueItemsList$ = uniqueItemsList[0],
        itemName = _uniqueItemsList$.name,
        itemType = _uniqueItemsList$.type;
    var purchaseAndInstallFormat = itemType === 'theme' ? Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__["__"])('Purchase & install %s theme', 'woocommerce') : Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__["__"])('Purchase & install %s extension', 'woocommerce');
    purchaseAndInstallText = Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__["sprintf"])(purchaseAndInstallFormat, itemName);
  }

  var tasks = [{
    key: 'store_details',
    title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__["__"])('Store details', 'woocommerce'),
    container: null,
    onClick: function onClick() {
      Object(lib_tracks__WEBPACK_IMPORTED_MODULE_14__["recordEvent"])('tasklist_click', {
        task_name: 'store_details'
      });
      Object(_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_6__["getHistory"])().push(Object(_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_6__["getNewPath"])({}, "/profiler", {}));
    },
    completed: profilerCompleted,
    visible: true,
    time: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__["__"])('4 minutes', 'woocommerce')
  }, {
    key: 'purchase',
    title: purchaseAndInstallText,
    container: null,
    onClick: function onClick() {
      Object(lib_tracks__WEBPACK_IMPORTED_MODULE_14__["recordEvent"])('tasklist_click', {
        task_name: 'purchase'
      });
      return remainingProducts.length ? toggleCartModal() : null;
    },
    visible: products.length,
    completed: products.length && !remainingProducts.length,
    time: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__["__"])('2 minutes', 'woocommerce'),
    isDismissable: true
  }, {
    key: 'products',
    title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__["__"])('Add my products', 'woocommerce'),
    container: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__["createElement"])(_tasks_products__WEBPACK_IMPORTED_MODULE_9__["default"], null),
    onClick: function onClick() {
      Object(lib_tracks__WEBPACK_IMPORTED_MODULE_14__["recordEvent"])('tasklist_click', {
        task_name: 'products'
      });
      Object(_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_6__["updateQueryString"])({
        task: 'products'
      });
    },
    completed: hasProducts,
    visible: true,
    time: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__["__"])('1 minute per product', 'woocommerce')
  }, {
    key: 'woocommerce-payments',
    title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__["__"])('Set up WooCommerce Payments', 'woocommerce'),
    container: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__["createElement"])(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__["Fragment"], null),
    completed: paymentsCompleted || paymentsSkipped,
    onClick: function () {
      var _onClick = _babel_runtime_helpers_asyncToGenerator__WEBPACK_IMPORTED_MODULE_1___default()( /*#__PURE__*/_babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0___default.a.mark(function _callee() {
        return _babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0___default.a.wrap(function _callee$(_context) {
          while (1) {
            switch (_context.prev = _context.next) {
              case 0:
                _context.next = 2;
                return new Promise(function (resolve, reject) {
                  // This task doesn't have a view, so the recordEvent call
                  // in TaskDashboard.recordTaskView() is never called. So
                  // record it here.
                  recordTaskViewEvent('wcpay', isJetpackConnected, activePlugins, installedPlugins);
                  Object(lib_tracks__WEBPACK_IMPORTED_MODULE_14__["recordEvent"])('tasklist_click', {
                    task_name: 'woocommerce-payments'
                  });
                  return Object(_tasks_payments_methods__WEBPACK_IMPORTED_MODULE_13__["installActivateAndConnectWcpay"])(resolve, reject, createNotice, installAndActivatePlugins);
                });

              case 2:
              case "end":
                return _context.stop();
            }
          }
        }, _callee);
      }));

      function onClick() {
        return _onClick.apply(this, arguments);
      }

      return onClick;
    }(),
    visible: window.wcAdminFeatures.wcpay && woocommercePaymentsInstalled && countryCode === 'US',
    time: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__["__"])('2 minutes', 'woocommerce')
  }, {
    key: 'payments',
    title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__["__"])('Set up payments', 'woocommerce'),
    container: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__["createElement"])(_tasks_payments__WEBPACK_IMPORTED_MODULE_12__["default"], null),
    completed: paymentsCompleted || paymentsSkipped,
    onClick: function onClick() {
      Object(lib_tracks__WEBPACK_IMPORTED_MODULE_14__["recordEvent"])('tasklist_click', {
        task_name: 'payments'
      });
      Object(_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_6__["updateQueryString"])({
        task: 'payments'
      });
    },
    visible: !woocommercePaymentsInstalled,
    time: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__["__"])('2 minutes', 'woocommerce')
  }, {
    key: 'tax',
    title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__["__"])('Set up tax', 'woocommerce'),
    container: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__["createElement"])(_tasks_tax__WEBPACK_IMPORTED_MODULE_11__["default"], null),
    onClick: function onClick() {
      Object(lib_tracks__WEBPACK_IMPORTED_MODULE_14__["recordEvent"])('tasklist_click', {
        task_name: 'tax'
      });
      Object(_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_6__["updateQueryString"])({
        task: 'tax'
      });
    },
    completed: isTaxComplete,
    visible: true,
    time: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__["__"])('1 minute', 'woocommerce')
  }, {
    key: 'shipping',
    title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__["__"])('Set up shipping', 'woocommerce'),
    container: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__["createElement"])(_tasks_shipping__WEBPACK_IMPORTED_MODULE_10__["default"], null),
    onClick: function onClick() {
      Object(lib_tracks__WEBPACK_IMPORTED_MODULE_14__["recordEvent"])('tasklist_click', {
        task_name: 'shipping'
      });
      Object(_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_6__["updateQueryString"])({
        task: 'shipping'
      });
    },
    completed: shippingZonesCount > 0,
    visible: productTypes && productTypes.includes('physical') || hasPhysicalProducts,
    time: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__["__"])('1 minute', 'woocommerce')
  }, {
    key: 'appearance',
    title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__["__"])('Personalize my store', 'woocommerce'),
    container: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__["createElement"])(_tasks_appearance__WEBPACK_IMPORTED_MODULE_7__["default"], null),
    onClick: function onClick() {
      Object(lib_tracks__WEBPACK_IMPORTED_MODULE_14__["recordEvent"])('tasklist_click', {
        task_name: 'appearance'
      });
      Object(_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_6__["updateQueryString"])({
        task: 'appearance'
      });
    },
    completed: isAppearanceComplete,
    visible: true,
    time: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__["__"])('2 minutes', 'woocommerce')
  }];
  return Object(_wordpress_hooks__WEBPACK_IMPORTED_MODULE_4__["applyFilters"])('woocommerce_admin_onboarding_task_list', tasks, query);
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
/* harmony import */ var _babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/regenerator */ "./node_modules/@babel/runtime/regenerator/index.js");
/* harmony import */ var _babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _babel_runtime_helpers_asyncToGenerator__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/asyncToGenerator */ "./node_modules/@babel/runtime/helpers/asyncToGenerator.js");
/* harmony import */ var _babel_runtime_helpers_asyncToGenerator__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_asyncToGenerator__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @babel/runtime/helpers/defineProperty */ "./node_modules/@babel/runtime/helpers/defineProperty.js");
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_2__);
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
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! lodash */ "lodash");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_14___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_14__);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_15___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_15__);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__(/*! @woocommerce/components */ "@woocommerce/components");
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_16___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_components__WEBPACK_IMPORTED_MODULE_16__);
/* harmony import */ var _woocommerce_navigation__WEBPACK_IMPORTED_MODULE_17__ = __webpack_require__(/*! @woocommerce/navigation */ "@woocommerce/navigation");
/* harmony import */ var _woocommerce_navigation__WEBPACK_IMPORTED_MODULE_17___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_17__);
/* harmony import */ var _woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_18__ = __webpack_require__(/*! @woocommerce/wc-admin-settings */ "./client/settings/index.js");
/* harmony import */ var _woocommerce_data__WEBPACK_IMPORTED_MODULE_19__ = __webpack_require__(/*! @woocommerce/data */ "@woocommerce/data");
/* harmony import */ var _woocommerce_data__WEBPACK_IMPORTED_MODULE_19___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_data__WEBPACK_IMPORTED_MODULE_19__);
/* harmony import */ var lib_tracks__WEBPACK_IMPORTED_MODULE_20__ = __webpack_require__(/*! lib/tracks */ "./client/lib/tracks.js");
/* harmony import */ var wc_api_constants__WEBPACK_IMPORTED_MODULE_21__ = __webpack_require__(/*! wc-api/constants */ "./client/wc-api/constants.js");











function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_2___default()(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

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




var Appearance = /*#__PURE__*/function (_Component) {
  _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_6___default()(Appearance, _Component);

  var _super = _createSuper(Appearance);

  function Appearance(props) {
    var _this;

    _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_3___default()(this, Appearance);

    _this = _super.call(this, props);

    var _getSetting = Object(_woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_18__["getSetting"])('onboarding', {}),
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
    _this.completeStep = _this.completeStep.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_5___default()(_this));
    _this.createHomepage = _this.createHomepage.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_5___default()(_this));
    _this.importProducts = _this.importProducts.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_5___default()(_this));
    _this.updateLogo = _this.updateLogo.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_5___default()(_this));
    _this.updateNotice = _this.updateNotice.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_5___default()(_this));
    return _this;
  }

  _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_4___default()(Appearance, [{
    key: "componentDidMount",
    value: function componentDidMount() {
      var _getSetting2 = Object(_woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_18__["getSetting"])('onboarding', {}),
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
        Object(_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_17__["getHistory"])().push(Object(_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_17__["getNewPath"])({}, '/', {}));
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
      Object(lib_tracks__WEBPACK_IMPORTED_MODULE_20__["recordEvent"])('tasklist_appearance_import_demo', {});
      _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_11___default()({
        path: "".concat(wc_api_constants__WEBPACK_IMPORTED_MODULE_21__["WC_ADMIN_NAMESPACE"], "/onboarding/tasks/import_sample_products"),
        method: 'POST'
      }).then(function (result) {
        if (result.failed && result.failed.length) {
          createNotice('error', Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_10__["__"])('There was an error importing some of the sample products.', 'woocommerce'));
        } else {
          createNotice('success', Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_10__["__"])('All sample products have been imported.', 'woocommerce'));
          Object(_woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_18__["setSetting"])('onboarding', _objectSpread(_objectSpread({}, Object(_woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_18__["getSetting"])('onboarding', {})), {}, {
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
      Object(lib_tracks__WEBPACK_IMPORTED_MODULE_20__["recordEvent"])('tasklist_appearance_create_homepage', {
        create_homepage: true
      });
      _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_11___default()({
        path: '/wc-admin/onboarding/tasks/create_homepage',
        method: 'POST'
      }).then(function (response) {
        createNotice(response.status, response.message, {
          actions: response.edit_post_link ? [{
            label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_10__["__"])('Customize', 'woocommerce'),
            onClick: function onClick() {
              Object(lib_tracks__WEBPACK_IMPORTED_MODULE_20__["queueRecordEvent"])('tasklist_appearance_customize_homepage', {});
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
      var _updateLogo = _babel_runtime_helpers_asyncToGenerator__WEBPACK_IMPORTED_MODULE_1___default()( /*#__PURE__*/_babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0___default.a.mark(function _callee() {
        var _this$props, updateOptions, createNotice, logo, _getSetting3, stylesheet, themeMods, updatedThemeMods, update;

        return _babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0___default.a.wrap(function _callee$(_context) {
          while (1) {
            switch (_context.prev = _context.next) {
              case 0:
                _this$props = this.props, updateOptions = _this$props.updateOptions, createNotice = _this$props.createNotice;
                logo = this.state.logo;
                _getSetting3 = Object(_woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_18__["getSetting"])('onboarding', {}), stylesheet = _getSetting3.stylesheet, themeMods = _getSetting3.themeMods;
                updatedThemeMods = _objectSpread(_objectSpread({}, themeMods), {}, {
                  custom_logo: logo ? logo.id : null
                });
                Object(lib_tracks__WEBPACK_IMPORTED_MODULE_20__["recordEvent"])('tasklist_appearance_upload_logo');
                Object(_woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_18__["setSetting"])('onboarding', _objectSpread(_objectSpread({}, Object(_woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_18__["getSetting"])('onboarding', {})), {}, {
                  themeMods: updatedThemeMods
                }));
                this.setState({
                  isUpdatingLogo: true
                });
                _context.next = 9;
                return updateOptions(_babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_2___default()({}, "theme_mods_".concat(stylesheet), updatedThemeMods));

              case 9:
                update = _context.sent;

                if (update.success) {
                  this.setState({
                    isUpdatingLogo: false
                  });
                  createNotice('success', Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_10__["__"])('Store logo updated sucessfully.', 'woocommerce'));
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
      var _updateNotice = _babel_runtime_helpers_asyncToGenerator__WEBPACK_IMPORTED_MODULE_1___default()( /*#__PURE__*/_babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0___default.a.mark(function _callee2() {
        var _this$props2, updateOptions, createNotice, storeNoticeText, update;

        return _babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0___default.a.wrap(function _callee2$(_context2) {
          while (1) {
            switch (_context2.prev = _context2.next) {
              case 0:
                _this$props2 = this.props, updateOptions = _this$props2.updateOptions, createNotice = _this$props2.createNotice;
                storeNoticeText = this.state.storeNoticeText;
                Object(lib_tracks__WEBPACK_IMPORTED_MODULE_20__["recordEvent"])('tasklist_appearance_set_store_notice', {
                  added_text: Boolean(storeNoticeText.length)
                });
                Object(_woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_18__["setSetting"])('onboarding', _objectSpread(_objectSpread({}, Object(_woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_18__["getSetting"])('onboarding', {})), {}, {
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
                  createNotice('success', Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_10__["__"])("🎨 Your store is looking great! Don't forget to continue personalizing it.", 'woocommerce'));
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
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_10__["__"])('Import sample products', 'woocommerce'),
        description: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_10__["__"])('We’ll add some products that will make it easier to see what your store looks like', 'woocommerce'),
        content: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["createElement"])(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["Fragment"], null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_12__["Button"], {
          onClick: this.importProducts,
          isBusy: isPending,
          isPrimary: true
        }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_10__["__"])('Import products', 'woocommerce')), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_12__["Button"], {
          onClick: function onClick() {
            return _this5.completeStep();
          }
        }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_10__["__"])('Skip', 'woocommerce'))),
        visible: this.stepVisibility.import
      }, {
        key: 'homepage',
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_10__["__"])('Create a custom homepage', 'woocommerce'),
        description: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_10__["__"])('Create a new homepage and customize it to suit your needs', 'woocommerce'),
        content: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["createElement"])(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["Fragment"], null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_12__["Button"], {
          isPrimary: true,
          isBusy: isPending,
          onClick: this.createHomepage
        }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_10__["__"])('Create homepage', 'woocommerce')), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_12__["Button"], {
          onClick: function onClick() {
            Object(lib_tracks__WEBPACK_IMPORTED_MODULE_20__["recordEvent"])('tasklist_appearance_create_homepage', {
              create_homepage: false
            });

            _this5.completeStep();
          }
        }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_10__["__"])('Skip', 'woocommerce'))),
        visible: this.stepVisibility.homepage
      }, {
        key: 'logo',
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_10__["__"])('Upload a logo', 'woocommerce'),
        description: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_10__["__"])('Ensure your store is on-brand by adding your logo', 'woocommerce'),
        content: isPending ? null : Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["createElement"])(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["Fragment"], null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_16__["ImageUpload"], {
          image: logo,
          onChange: function onChange(image) {
            return _this5.setState({
              isDirty: true,
              logo: image
            });
          }
        }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_12__["Button"], {
          disabled: !logo && !isDirty,
          onClick: this.updateLogo,
          isBusy: isUpdatingLogo,
          isPrimary: true
        }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_10__["__"])('Proceed', 'woocommerce')), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_12__["Button"], {
          isTertiary: true,
          onClick: function onClick() {
            return _this5.completeStep();
          }
        }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_10__["__"])('Skip', 'woocommerce'))),
        visible: true
      }, {
        key: 'notice',
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_10__["__"])('Set a store notice', 'woocommerce'),
        description: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_10__["__"])('Optionally display a prominent notice across all pages of your store', 'woocommerce'),
        content: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["createElement"])(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["Fragment"], null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_16__["TextControl"], {
          label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_10__["__"])('Store notice text', 'woocommerce'),
          placeholder: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_10__["__"])('Store notice text', 'woocommerce'),
          value: storeNoticeText,
          onChange: function onChange(value) {
            return _this5.setState({
              storeNoticeText: value
            });
          }
        }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_12__["Button"], {
          onClick: this.updateNotice,
          isPrimary: true
        }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_10__["__"])('Complete task', 'woocommerce'))),
        visible: true
      }];
      return Object(lodash__WEBPACK_IMPORTED_MODULE_14__["filter"])(steps, function (step) {
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
      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["createElement"])("div", {
        className: "woocommerce-task-appearance"
      }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_16__["Card"], {
        className: "is-narrow"
      }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_16__["Stepper"], {
        isPending: isUpdatingNotice || isUpdatingLogo || isPending,
        isVertical: true,
        currentStep: currentStep,
        steps: this.getSteps()
      })));
    }
  }]);

  return Appearance;
}(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["Component"]);

/* harmony default export */ __webpack_exports__["default"] = (Object(_wordpress_compose__WEBPACK_IMPORTED_MODULE_13__["compose"])(Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_15__["withSelect"])(function (select) {
  var _select = select(_woocommerce_data__WEBPACK_IMPORTED_MODULE_19__["OPTIONS_STORE_NAME"]),
      getOption = _select.getOption;

  return {
    demoStoreNotice: getOption('woocommerce_demo_store_notice')
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
}))(Appearance));

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
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_10___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_10__);
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! classnames */ "./node_modules/classnames/index.js");
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_11___default = /*#__PURE__*/__webpack_require__.n(classnames__WEBPACK_IMPORTED_MODULE_11__);
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! @wordpress/compose */ "./node_modules/@wordpress/compose/build-module/index.js");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! @wordpress/components */ "./node_modules/@wordpress/components/build-module/index.js");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_14___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_14__);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(/*! @woocommerce/components */ "@woocommerce/components");
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_15___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_components__WEBPACK_IMPORTED_MODULE_15__);
/* harmony import */ var _woocommerce_navigation__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__(/*! @woocommerce/navigation */ "@woocommerce/navigation");
/* harmony import */ var _woocommerce_navigation__WEBPACK_IMPORTED_MODULE_16___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_16__);
/* harmony import */ var _woocommerce_data__WEBPACK_IMPORTED_MODULE_17__ = __webpack_require__(/*! @woocommerce/data */ "@woocommerce/data");
/* harmony import */ var _woocommerce_data__WEBPACK_IMPORTED_MODULE_17___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_data__WEBPACK_IMPORTED_MODULE_17__);
/* harmony import */ var lib_tracks__WEBPACK_IMPORTED_MODULE_18__ = __webpack_require__(/*! lib/tracks */ "./client/lib/tracks.js");
/* harmony import */ var dashboard_utils__WEBPACK_IMPORTED_MODULE_19__ = __webpack_require__(/*! dashboard/utils */ "./client/dashboard/utils.js");
/* harmony import */ var wc_api_with_select__WEBPACK_IMPORTED_MODULE_20__ = __webpack_require__(/*! wc-api/with-select */ "./client/wc-api/with-select.js");
/* harmony import */ var _methods__WEBPACK_IMPORTED_MODULE_21__ = __webpack_require__(/*! ./methods */ "./client/task-list/tasks/payments/methods.js");











function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0___default()(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

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






var Payments = /*#__PURE__*/function (_Component) {
  _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_6___default()(Payments, _Component);

  var _super = _createSuper(Payments);

  function Payments(props) {
    var _this;

    _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_3___default()(this, Payments);

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
    _this.completeTask = _this.completeTask.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_5___default()(_this));
    _this.markConfigured = _this.markConfigured.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_5___default()(_this));
    _this.skipTask = _this.skipTask.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_5___default()(_this));
    return _this;
  }

  _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_4___default()(Payments, [{
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
      var _completeTask = _babel_runtime_helpers_asyncToGenerator__WEBPACK_IMPORTED_MODULE_2___default()( /*#__PURE__*/_babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_1___default.a.mark(function _callee() {
        var _this$props, createNotice, methods, updateOptions, update;

        return _babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_1___default.a.wrap(function _callee$(_context) {
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
                Object(lib_tracks__WEBPACK_IMPORTED_MODULE_18__["recordEvent"])('tasklist_payment_done', {
                  configured: methods.filter(function (method) {
                    return method.isConfigured;
                  }).map(function (method) {
                    return method.key;
                  })
                });

                if (update.success) {
                  createNotice('success', Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_10__["__"])('💰 Ka-ching! Your store can now accept payments 💳', 'woocommerce'));
                  Object(_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_16__["getHistory"])().push(Object(_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_16__["getNewPath"])({}, '/', {}));
                } else {
                  createNotice('error', Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_10__["__"])('There was a problem updating settings', 'woocommerce'));
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
      Object(lib_tracks__WEBPACK_IMPORTED_MODULE_18__["recordEvent"])('tasklist_payment_skip_task', {
        options: methods.map(function (method) {
          return method.key;
        })
      });
      Object(_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_16__["getHistory"])().push(Object(_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_16__["getNewPath"])({}, '/', {}));
    }
  }, {
    key: "markConfigured",
    value: function markConfigured(method) {
      var enabledMethods = this.state.enabledMethods;
      this.setState({
        enabledMethods: _objectSpread(_objectSpread({}, enabledMethods), {}, _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0___default()({}, method, true))
      });
      Object(_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_16__["getHistory"])().push(Object(_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_16__["getNewPath"])({
        task: 'payments'
      }, '/', {}));
      Object(lib_tracks__WEBPACK_IMPORTED_MODULE_18__["recordEvent"])('tasklist_payment_connect_method', {
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
        return _woocommerce_data__WEBPACK_IMPORTED_MODULE_17__["pluginNames"][pluginSlug];
      }).join(' ' + Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_10__["__"])('and', 'woocommerce') + ' ');
      return {
        key: 'install',
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_10__["sprintf"])(Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_10__["__"])('Install %s', 'woocommerce'), pluginNamesString),
        content: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_15__["Plugins"], {
          onComplete: function onComplete() {
            Object(lib_tracks__WEBPACK_IMPORTED_MODULE_18__["recordEvent"])('tasklist_payment_install_method', {
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
      Object(lib_tracks__WEBPACK_IMPORTED_MODULE_18__["recordEvent"])('tasklist_payment_toggle', {
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
      var _handleClick = _babel_runtime_helpers_asyncToGenerator__WEBPACK_IMPORTED_MODULE_2___default()( /*#__PURE__*/_babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_1___default.a.mark(function _callee2(method) {
        var _this2 = this;

        var methods, key, onClick;
        return _babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_1___default.a.wrap(function _callee2$(_context2) {
          while (1) {
            switch (_context2.prev = _context2.next) {
              case 0:
                methods = this.props.methods;
                key = method.key, onClick = method.onClick;
                Object(lib_tracks__WEBPACK_IMPORTED_MODULE_18__["recordEvent"])('tasklist_payment_setup', {
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
                Object(_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_16__["updateQueryString"])({
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
        return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_15__["Card"], {
          className: "woocommerce-task-payment-method is-narrow"
        }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["cloneElement"])(currentMethod.container, {
          query: query,
          installStep: this.getInstallStep(),
          markConfigured: this.markConfigured,
          hasCbdIndustry: currentMethod.hasCbdIndustry
        }));
      }

      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["createElement"])("div", {
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

        var classes = classnames__WEBPACK_IMPORTED_MODULE_11___default()('woocommerce-task-payment', 'is-narrow', !isConfigured && 'woocommerce-task-payment-not-configured', 'woocommerce-task-payment-' + key);
        var isRecommended = key === recommendedMethod && !isConfigured;
        var showRecommendedRibbon = isRecommended && key !== 'wcpay';
        var showRecommendedPill = isRecommended && key === 'wcpay';
        return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_15__["Card"], {
          key: key,
          className: classes
        }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["createElement"])("div", {
          className: "woocommerce-task-payment__before"
        }, showRecommendedRibbon && Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["createElement"])("div", {
          className: "woocommerce-task-payment__recommended-ribbon"
        }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["createElement"])("span", null, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_10__["__"])('Recommended', 'woocommerce'))), before), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["createElement"])("div", {
          className: "woocommerce-task-payment__text"
        }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_15__["H"], {
          className: "woocommerce-task-payment__title"
        }, title, showRecommendedPill && Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["createElement"])("span", {
          className: "woocommerce-task-payment__recommended-pill"
        }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_10__["__"])('Recommended', 'woocommerce'))), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["createElement"])("div", {
          className: "woocommerce-task-payment__content"
        }, content)), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["createElement"])("div", {
          className: "woocommerce-task-payment__after"
        }, container && !isConfigured ? Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_13__["Button"], {
          isPrimary: key === recommendedMethod,
          isSecondary: key !== recommendedMethod,
          isBusy: busyMethod === key,
          disabled: busyMethod,
          onClick: function onClick() {
            return _this3.handleClick(method);
          }
        }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_10__["__"])('Set up', 'woocommerce')) : Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_13__["FormToggle"], {
          checked: enabledMethods[key],
          onChange: function onChange() {
            return _this3.toggleMethod(key);
          },
          onClick: function onClick(e) {
            return e.stopPropagation();
          }
        })));
      }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["createElement"])("div", {
        className: "woocommerce-task-payments__actions"
      }, !hasEnabledMethods ? Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_13__["Button"], {
        isLink: true,
        onClick: this.skipTask
      }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_10__["__"])('My store doesn’t take payments', 'woocommerce')) : Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_13__["Button"], {
        isPrimary: true,
        isBusy: requesting,
        onClick: this.completeTask
      }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_10__["__"])('Done', 'woocommerce'))));
    }
  }]);

  return Payments;
}(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["Component"]);

/* harmony default export */ __webpack_exports__["default"] = (Object(_wordpress_compose__WEBPACK_IMPORTED_MODULE_12__["compose"])(Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_14__["withDispatch"])(function (dispatch) {
  var _dispatch = dispatch('core/notices'),
      createNotice = _dispatch.createNotice;

  var _dispatch2 = dispatch(_woocommerce_data__WEBPACK_IMPORTED_MODULE_17__["PLUGINS_STORE_NAME"]),
      installAndActivatePlugins = _dispatch2.installAndActivatePlugins;

  var _dispatch3 = dispatch(_woocommerce_data__WEBPACK_IMPORTED_MODULE_17__["OPTIONS_STORE_NAME"]),
      updateOptions = _dispatch3.updateOptions;

  return {
    createNotice: createNotice,
    installAndActivatePlugins: installAndActivatePlugins,
    updateOptions: updateOptions
  };
}), Object(wc_api_with_select__WEBPACK_IMPORTED_MODULE_20__["default"])(function (select, props) {
  var createNotice = props.createNotice,
      installAndActivatePlugins = props.installAndActivatePlugins;

  var _select = select(_woocommerce_data__WEBPACK_IMPORTED_MODULE_17__["ONBOARDING_STORE_NAME"]),
      getProfileItems = _select.getProfileItems;

  var _select2 = select(_woocommerce_data__WEBPACK_IMPORTED_MODULE_17__["OPTIONS_STORE_NAME"]),
      getOption = _select2.getOption,
      isOptionsUpdating = _select2.isOptionsUpdating;

  var _select3 = select(_woocommerce_data__WEBPACK_IMPORTED_MODULE_17__["PLUGINS_STORE_NAME"]),
      getActivePlugins = _select3.getActivePlugins,
      isJetpackConnected = _select3.isJetpackConnected;

  var _select4 = select(_woocommerce_data__WEBPACK_IMPORTED_MODULE_17__["SETTINGS_STORE_NAME"]),
      getSettings = _select4.getSettings;

  var _getSettings = getSettings('general'),
      _getSettings$general = _getSettings.general,
      generalSettings = _getSettings$general === void 0 ? {} : _getSettings$general;

  var activePlugins = getActivePlugins();
  var profileItems = getProfileItems();
  var optionNames = ['woocommerce_woocommerce_payments_settings', 'woocommerce_stripe_settings', 'woocommerce_ppec_paypal_settings', 'woocommerce_payfast_settings', 'woocommerce_square_credit_card_settings', 'woocommerce_klarna_payments_settings', 'woocommerce_kco_settings', 'wc_square_refresh_tokens', 'woocommerce_cod_settings', 'woocommerce_bacs_settings', 'woocommerce_bacs_accounts', 'woocommerce_eway_settings'];
  var options = optionNames.reduce(function (result, name) {
    result[name] = getOption(name);
    return result;
  }, {});
  var countryCode = Object(dashboard_utils__WEBPACK_IMPORTED_MODULE_19__["getCountryCode"])(generalSettings.woocommerce_default_country);
  var methods = Object(_methods__WEBPACK_IMPORTED_MODULE_21__["getPaymentMethods"])({
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
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_10___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_10__);
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! @wordpress/api-fetch */ "@wordpress/api-fetch");
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_11___default = /*#__PURE__*/__webpack_require__.n(_wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_11__);
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! @wordpress/compose */ "./node_modules/@wordpress/compose/build-module/index.js");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! lodash */ "lodash");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_13___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_13__);
/* harmony import */ var interpolate_components__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! interpolate-components */ "./node_modules/interpolate-components/lib/index.js");
/* harmony import */ var interpolate_components__WEBPACK_IMPORTED_MODULE_14___default = /*#__PURE__*/__webpack_require__.n(interpolate_components__WEBPACK_IMPORTED_MODULE_14__);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_15___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_15__);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__(/*! @woocommerce/components */ "@woocommerce/components");
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_16___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_components__WEBPACK_IMPORTED_MODULE_16__);
/* harmony import */ var _woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_17__ = __webpack_require__(/*! @woocommerce/wc-admin-settings */ "./client/settings/index.js");
/* harmony import */ var _woocommerce_navigation__WEBPACK_IMPORTED_MODULE_18__ = __webpack_require__(/*! @woocommerce/navigation */ "@woocommerce/navigation");
/* harmony import */ var _woocommerce_navigation__WEBPACK_IMPORTED_MODULE_18___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_18__);
/* harmony import */ var _woocommerce_data__WEBPACK_IMPORTED_MODULE_19__ = __webpack_require__(/*! @woocommerce/data */ "@woocommerce/data");
/* harmony import */ var _woocommerce_data__WEBPACK_IMPORTED_MODULE_19___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_data__WEBPACK_IMPORTED_MODULE_19__);
/* harmony import */ var dashboard_components_connect__WEBPACK_IMPORTED_MODULE_20__ = __webpack_require__(/*! dashboard/components/connect */ "./client/dashboard/components/connect/index.js");
/* harmony import */ var dashboard_utils__WEBPACK_IMPORTED_MODULE_21__ = __webpack_require__(/*! dashboard/utils */ "./client/dashboard/utils.js");
/* harmony import */ var _steps_location__WEBPACK_IMPORTED_MODULE_22__ = __webpack_require__(/*! ../steps/location */ "./client/task-list/tasks/steps/location.js");
/* harmony import */ var _rates__WEBPACK_IMPORTED_MODULE_23__ = __webpack_require__(/*! ./rates */ "./client/task-list/tasks/shipping/rates.js");
/* harmony import */ var lib_tracks__WEBPACK_IMPORTED_MODULE_24__ = __webpack_require__(/*! lib/tracks */ "./client/lib/tracks.js");











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







var Shipping = /*#__PURE__*/function (_Component) {
  _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_6___default()(Shipping, _Component);

  var _super = _createSuper(Shipping);

  function Shipping(props) {
    var _this;

    _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_3___default()(this, Shipping);

    _this = _super.call(this, props);
    _this.initialState = {
      isPending: false,
      step: 'store_location',
      shippingZones: []
    }; // Cache active plugins to prevent removal mid-step.

    _this.activePlugins = props.activePlugins;
    _this.state = _this.initialState;
    _this.completeStep = _this.completeStep.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_5___default()(_this));
    return _this;
  }

  _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_4___default()(Shipping, [{
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
      var _fetchShippingZones = _babel_runtime_helpers_asyncToGenerator__WEBPACK_IMPORTED_MODULE_2___default()( /*#__PURE__*/_babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_1___default.a.mark(function _callee2() {
        var _this$props, countryCode, countryName, shippingZones, zones, hasCountryZone, zone;

        return _babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_1___default.a.wrap(function _callee2$(_context2) {
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
                return _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_11___default()({
                  path: '/wc/v3/shipping/zones'
                });

              case 5:
                zones = _context2.sent;
                hasCountryZone = false;
                _context2.next = 9;
                return Promise.all(zones.map( /*#__PURE__*/function () {
                  var _ref = _babel_runtime_helpers_asyncToGenerator__WEBPACK_IMPORTED_MODULE_2___default()( /*#__PURE__*/_babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_1___default.a.mark(function _callee(zone) {
                    var countryLocation;
                    return _babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_1___default.a.wrap(function _callee$(_context) {
                      while (1) {
                        switch (_context.prev = _context.next) {
                          case 0:
                            if (!(zone.id === 0)) {
                              _context.next = 8;
                              break;
                            }

                            _context.next = 3;
                            return _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_11___default()({
                              path: "/wc/v3/shipping/zones/".concat(zone.id, "/methods")
                            });

                          case 3:
                            zone.methods = _context.sent;
                            zone.name = Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_10__["__"])('Rest of the world', 'woocommerce');
                            zone.toggleable = true;
                            shippingZones.push(zone);
                            return _context.abrupt("return");

                          case 8:
                            _context.next = 10;
                            return _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_11___default()({
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
                            return _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_11___default()({
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
                return _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_11___default()({
                  method: 'POST',
                  path: '/wc/v3/shipping/zones',
                  data: {
                    name: countryName
                  }
                });

              case 12:
                zone = _context2.sent;
                _context2.next = 15;
                return _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_11___default()({
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
        createNotice('success', Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_10__["__"])("📦 Shipping is done! Don't worry, you can always change it later.", 'woocommerce'));
        Object(_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_18__["getHistory"])().push(Object(_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_18__["getNewPath"])({}, '/', {}));
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

      return Object(lodash__WEBPACK_IMPORTED_MODULE_13__["difference"])(plugins, this.activePlugins);
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
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_10__["__"])('Set store location', 'woocommerce'),
        description: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_10__["__"])('The address from which your business operates', 'woocommerce'),
        content: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["createElement"])(_steps_location__WEBPACK_IMPORTED_MODULE_22__["default"], _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0___default()({}, this.props, {
          onComplete: function onComplete(values) {
            var country = Object(dashboard_utils__WEBPACK_IMPORTED_MODULE_21__["getCountryCode"])(values.countryState);
            Object(lib_tracks__WEBPACK_IMPORTED_MODULE_24__["recordEvent"])('tasklist_shipping_set_location', {
              country: country
            });

            _this2.completeStep();
          }
        })),
        visible: true
      }, {
        key: 'rates',
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_10__["__"])('Set shipping costs', 'woocommerce'),
        description: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_10__["__"])('Define how much customers pay to ship to different destinations', 'woocommerce'),
        content: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["createElement"])(_rates__WEBPACK_IMPORTED_MODULE_23__["default"], _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0___default()({
          buttonText: pluginsToActivate.length || requiresJetpackConnection ? Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_10__["__"])('Proceed', 'woocommerce') : Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_10__["__"])('Complete task', 'woocommerce'),
          shippingZones: this.state.shippingZones,
          onComplete: this.completeStep
        }, this.props)),
        visible: true
      }, {
        key: 'label_printing',
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_10__["__"])('Enable shipping label printing', 'woocommerce'),
        description: pluginsToActivate.includes('woocommerce-shipstation-integration') ? interpolate_components__WEBPACK_IMPORTED_MODULE_14___default()({
          mixedString: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_10__["__"])('We recommend using ShipStation to save time at the post office by printing your shipping ' + 'labels at home. Try ShipStation free for 30 days. {{link}}Learn more{{/link}}.', 'woocommerce'),
          components: {
            link: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_16__["Link"], {
              href: "https://woocommerce.com/products/shipstation-integration",
              target: "_blank",
              type: "external"
            })
          }
        }) : Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_10__["__"])('With WooCommerce Services and Jetpack you can save time at the ' + 'Post Office by printing your shipping labels at home', 'woocommerce'),
        content: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_16__["Plugins"], _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0___default()({
          onComplete: function onComplete() {
            Object(lib_tracks__WEBPACK_IMPORTED_MODULE_24__["recordEvent"])('tasklist_shipping_label_printing', {
              install: true,
              pluginsToActivate: pluginsToActivate
            });

            _this2.completeStep();
          },
          onSkip: function onSkip() {
            Object(lib_tracks__WEBPACK_IMPORTED_MODULE_24__["recordEvent"])('tasklist_shipping_label_printing', {
              install: false,
              pluginsToActivate: pluginsToActivate
            });
            Object(_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_18__["getHistory"])().push(Object(_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_18__["getNewPath"])({}, '/', {}));
          },
          pluginSlugs: pluginsToActivate
        }, this.props)),
        visible: pluginsToActivate.length
      }, {
        key: 'connect',
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_10__["__"])('Connect your store', 'woocommerce'),
        description: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_10__["__"])('Connect your store to WordPress.com to enable label printing', 'woocommerce'),
        content: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["createElement"])(dashboard_components_connect__WEBPACK_IMPORTED_MODULE_20__["default"], _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0___default()({
          redirectUrl: Object(_woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_17__["getAdminLink"])('admin.php?page=wc-admin'),
          completeStep: this.completeStep
        }, this.props, {
          onConnect: function onConnect() {
            Object(lib_tracks__WEBPACK_IMPORTED_MODULE_24__["recordEvent"])('tasklist_shipping_connect_store');
          }
        })),
        visible: requiresJetpackConnection
      }];
      return Object(lodash__WEBPACK_IMPORTED_MODULE_13__["filter"])(steps, function (step) {
        return step.visible;
      });
    }
  }, {
    key: "render",
    value: function render() {
      var _this$state = this.state,
          isPending = _this$state.isPending,
          step = _this$state.step;
      var isUpdateSettingsRequesting = this.props.isUpdateSettingsRequesting;
      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["createElement"])("div", {
        className: "woocommerce-task-shipping"
      }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_16__["Card"], {
        className: "is-narrow"
      }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_16__["Stepper"], {
        isPending: isPending || isUpdateSettingsRequesting,
        isVertical: true,
        currentStep: step,
        steps: this.getSteps()
      })));
    }
  }]);

  return Shipping;
}(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["Component"]);

/* harmony default export */ __webpack_exports__["default"] = (Object(_wordpress_compose__WEBPACK_IMPORTED_MODULE_12__["compose"])(Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_15__["withSelect"])(function (select) {
  var _select = select(_woocommerce_data__WEBPACK_IMPORTED_MODULE_19__["SETTINGS_STORE_NAME"]),
      getSettings = _select.getSettings,
      isUpdateSettingsRequesting = _select.isUpdateSettingsRequesting;

  var _select2 = select(_woocommerce_data__WEBPACK_IMPORTED_MODULE_19__["PLUGINS_STORE_NAME"]),
      getActivePlugins = _select2.getActivePlugins,
      isJetpackConnected = _select2.isJetpackConnected;

  var _getSettings = getSettings('general'),
      _getSettings$general = _getSettings.general,
      settings = _getSettings$general === void 0 ? {} : _getSettings$general;

  var countryCode = Object(dashboard_utils__WEBPACK_IMPORTED_MODULE_21__["getCountryCode"])(settings.woocommerce_default_country);

  var _getSetting = Object(_woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_17__["getSetting"])('dataEndpoints', {}),
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
    isUpdateSettingsRequesting: isUpdateSettingsRequesting('general'),
    settings: settings,
    activePlugins: activePlugins,
    isJetpackConnected: isJetpackConnected()
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
/* harmony import */ var _babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/regenerator */ "./node_modules/@babel/runtime/regenerator/index.js");
/* harmony import */ var _babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @babel/runtime/helpers/defineProperty */ "./node_modules/@babel/runtime/helpers/defineProperty.js");
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_2__);
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
/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! prop-types */ "./node_modules/prop-types/index.js");
/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_14___default = /*#__PURE__*/__webpack_require__.n(prop_types__WEBPACK_IMPORTED_MODULE_14__);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(/*! @woocommerce/components */ "@woocommerce/components");
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_15___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_components__WEBPACK_IMPORTED_MODULE_15__);
/* harmony import */ var _woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__(/*! @woocommerce/wc-admin-settings */ "./client/settings/index.js");
/* harmony import */ var lib_tracks__WEBPACK_IMPORTED_MODULE_17__ = __webpack_require__(/*! lib/tracks */ "./client/lib/tracks.js");
/* harmony import */ var lib_currency_context__WEBPACK_IMPORTED_MODULE_18__ = __webpack_require__(/*! lib/currency-context */ "./client/lib/currency-context.js");












function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_2___default()(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_9___default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_9___default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_8___default()(this, result); }; }

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
  _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_7___default()(ShippingRates, _Component);

  var _super = _createSuper(ShippingRates);

  function ShippingRates() {
    var _this;

    _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_4___default()(this, ShippingRates);

    _this = _super.apply(this, arguments);
    _this.updateShippingZones = _this.updateShippingZones.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_6___default()(_this));
    return _this;
  }

  _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_5___default()(ShippingRates, [{
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
        _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_12___default()({
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
      var _updateShippingZones = _babel_runtime_helpers_asyncToGenerator__WEBPACK_IMPORTED_MODULE_3___default()( /*#__PURE__*/_babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_1___default.a.mark(function _callee(values) {
        var _this2 = this;

        var _this$props, createNotice, shippingZones, restOfTheWorld, shippingCost;

        return _babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_1___default.a.wrap(function _callee$(_context) {
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

                  _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_12___default()({
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
                Object(lib_tracks__WEBPACK_IMPORTED_MODULE_17__["recordEvent"])('tasklist_shipping_set_costs', {
                  shipping_cost: shippingCost,
                  rest_world: restOfTheWorld
                }); // @todo This is a workaround to force the task to mark as complete.
                // This should probably be updated to use wc-api so we can fetch shipping methods.

                Object(_woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_16__["setSetting"])('onboarding', _objectSpread(_objectSpread({}, Object(_woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_16__["getSetting"])('onboarding', {})), {}, {
                  shippingZonesCount: 1
                }));
                createNotice('success', Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__["__"])('Your shipping rates have been updated.', 'woocommerce'));
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
      var _this$context$getCurr = this.context.getCurrencyConfig(),
          symbolPosition = _this$context$getCurr.symbolPosition,
          symbol = _this$context$getCurr.symbol;

      if (symbolPosition.indexOf('right') === 0) {
        return null;
      }

      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__["createElement"])("span", {
        className: "woocommerce-shipping-rate__control-prefix"
      }, symbol);
    }
  }, {
    key: "renderInputSuffix",
    value: function renderInputSuffix(rate) {
      var _this$context$getCurr2 = this.context.getCurrencyConfig(),
          symbolPosition = _this$context$getCurr2.symbolPosition,
          symbol = _this$context$getCurr2.symbol;

      if (symbolPosition.indexOf('right') === 0) {
        return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__["createElement"])("span", {
          className: "woocommerce-shipping-rate__control-suffix"
        }, symbol);
      }

      return parseFloat(rate) === parseFloat(0) ? Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__["createElement"])("span", {
        className: "woocommerce-shipping-rate__control-suffix"
      }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__["__"])('Free shipping', 'woocommerce')) : null;
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
          errors[rate] = Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__["__"])('Shipping rates can not be negative numbers.', 'woocommerce');
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

      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_15__["Form"], {
        initialValues: this.getInitialValues(),
        onSubmitCallback: this.updateShippingZones,
        validate: this.validate
      }, function (_ref) {
        var getInputProps = _ref.getInputProps,
            handleSubmit = _ref.handleSubmit,
            setTouched = _ref.setTouched,
            setValue = _ref.setValue,
            values = _ref.values;
        return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__["createElement"])(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__["Fragment"], null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__["createElement"])("div", {
          className: "woocommerce-shipping-rates"
        }, shippingZones.map(function (zone) {
          return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__["createElement"])("div", {
            className: "woocommerce-shipping-rate",
            key: zone.id
          }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__["createElement"])("div", {
            className: "woocommerce-shipping-rate__icon"
          }, zone.locations ? zone.locations.map(function (location) {
            return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_15__["Flag"], {
              size: 24,
              code: location.code,
              key: location.code
            });
          }) : // Icon used for zones without locations or "Rest of the world".
          Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__["createElement"])("i", {
            className: "material-icons-outlined"
          }, "public")), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__["createElement"])("div", {
            className: "woocommerce-shipping-rate__main"
          }, zone.toggleable ? Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__["createElement"])("label", {
            htmlFor: "woocommerce-shipping-rate__toggle-".concat(zone.id),
            className: "woocommerce-shipping-rate__name"
          }, zone.name, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_13__["FormToggle"], _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0___default()({
            id: "woocommerce-shipping-rate__toggle-".concat(zone.id)
          }, getInputProps("".concat(zone.id, "_enabled"))))) : Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__["createElement"])("div", {
            className: "woocommerce-shipping-rate__name"
          }, zone.name), (!zone.toggleable || values["".concat(zone.id, "_enabled")]) && Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_15__["TextControlWithAffixes"], _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0___default()({
            label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__["__"])('Shipping cost', 'woocommerce'),
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
        })), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_13__["Button"], {
          isPrimary: true,
          onClick: handleSubmit
        }, buttonText || Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__["__"])('Update', 'woocommerce')));
      });
    }
  }]);

  return ShippingRates;
}(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__["Component"]);

ShippingRates.propTypes = {
  /**
   * Text displayed on the primary button.
   */
  buttonText: prop_types__WEBPACK_IMPORTED_MODULE_14___default.a.string,

  /**
   * Function used to mark the step complete.
   */
  onComplete: prop_types__WEBPACK_IMPORTED_MODULE_14___default.a.func.isRequired,

  /**
   * Function to create a transient notice in the store.
   */
  createNotice: prop_types__WEBPACK_IMPORTED_MODULE_14___default.a.func.isRequired,

  /**
   * Array of shipping zones returned from the WC REST API with added
   * `methods` and `locations` properties appended from separate API calls.
   */
  shippingZones: prop_types__WEBPACK_IMPORTED_MODULE_14___default.a.array
};
ShippingRates.defaultProps = {
  shippingZones: []
};
ShippingRates.contextType = lib_currency_context__WEBPACK_IMPORTED_MODULE_18__["CurrencyContext"];
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
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! @wordpress/components */ "./node_modules/@wordpress/components/build-module/index.js");
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! @woocommerce/components */ "@woocommerce/components");
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_12___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_components__WEBPACK_IMPORTED_MODULE_12__);
/* harmony import */ var dashboard_components_settings_general_store_address__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! dashboard/components/settings/general/store-address */ "./client/dashboard/components/settings/general/store-address.js");











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



var StoreLocation = /*#__PURE__*/function (_Component) {
  _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_6___default()(StoreLocation, _Component);

  var _super = _createSuper(StoreLocation);

  function StoreLocation() {
    var _this;

    _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_3___default()(this, StoreLocation);

    _this = _super.apply(this, arguments);
    _this.onSubmit = _this.onSubmit.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_5___default()(_this));
    return _this;
  }

  _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_4___default()(StoreLocation, [{
    key: "onSubmit",
    value: function () {
      var _onSubmit = _babel_runtime_helpers_asyncToGenerator__WEBPACK_IMPORTED_MODULE_2___default()( /*#__PURE__*/_babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0___default.a.mark(function _callee(values) {
        var _this$props, onComplete, createNotice, isSettingsError, updateAndPersistSettingsForGroup, settings;

        return _babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0___default.a.wrap(function _callee$(_context) {
          while (1) {
            switch (_context.prev = _context.next) {
              case 0:
                _this$props = this.props, onComplete = _this$props.onComplete, createNotice = _this$props.createNotice, isSettingsError = _this$props.isSettingsError, updateAndPersistSettingsForGroup = _this$props.updateAndPersistSettingsForGroup, settings = _this$props.settings;
                _context.next = 3;
                return updateAndPersistSettingsForGroup('general', {
                  general: _objectSpread(_objectSpread({}, settings), {}, {
                    woocommerce_store_address: values.addressLine1,
                    woocommerce_store_address_2: values.addressLine2,
                    woocommerce_default_country: values.countryState,
                    woocommerce_store_city: values.city,
                    woocommerce_store_postcode: values.postCode
                  })
                });

              case 3:
                if (!isSettingsError) {
                  onComplete(values);
                } else {
                  createNotice('error', Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_10__["__"])('There was a problem saving your store location.', 'woocommerce'));
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

      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_12__["Form"], {
        initialValues: this.getInitialValues(),
        onSubmitCallback: this.onSubmit,
        validate: dashboard_components_settings_general_store_address__WEBPACK_IMPORTED_MODULE_13__["validateStoreAddress"]
      }, function (_ref) {
        var getInputProps = _ref.getInputProps,
            handleSubmit = _ref.handleSubmit,
            setValue = _ref.setValue;
        return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["createElement"])(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["Fragment"], null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["createElement"])(dashboard_components_settings_general_store_address__WEBPACK_IMPORTED_MODULE_13__["StoreAddress"], {
          getInputProps: getInputProps,
          setValue: setValue
        }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_11__["Button"], {
          isPrimary: true,
          onClick: handleSubmit
        }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_10__["__"])('Continue', 'woocommerce')));
      });
    }
  }]);

  return StoreLocation;
}(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["Component"]);



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
/* harmony import */ var _babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/regenerator */ "./node_modules/@babel/runtime/regenerator/index.js");
/* harmony import */ var _babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @babel/runtime/helpers/defineProperty */ "./node_modules/@babel/runtime/helpers/defineProperty.js");
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_2__);
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
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! @wordpress/components */ "./node_modules/@wordpress/components/build-module/index.js");
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! @wordpress/compose */ "./node_modules/@wordpress/compose/build-module/index.js");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! lodash */ "lodash");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_14___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_14__);
/* harmony import */ var interpolate_components__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(/*! interpolate-components */ "./node_modules/interpolate-components/lib/index.js");
/* harmony import */ var interpolate_components__WEBPACK_IMPORTED_MODULE_15___default = /*#__PURE__*/__webpack_require__.n(interpolate_components__WEBPACK_IMPORTED_MODULE_15__);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_16___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_16__);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_17__ = __webpack_require__(/*! @woocommerce/components */ "@woocommerce/components");
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_17___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_components__WEBPACK_IMPORTED_MODULE_17__);
/* harmony import */ var _woocommerce_navigation__WEBPACK_IMPORTED_MODULE_18__ = __webpack_require__(/*! @woocommerce/navigation */ "@woocommerce/navigation");
/* harmony import */ var _woocommerce_navigation__WEBPACK_IMPORTED_MODULE_18___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_18__);
/* harmony import */ var _woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_19__ = __webpack_require__(/*! @woocommerce/wc-admin-settings */ "./client/settings/index.js");
/* harmony import */ var _woocommerce_data__WEBPACK_IMPORTED_MODULE_20__ = __webpack_require__(/*! @woocommerce/data */ "@woocommerce/data");
/* harmony import */ var _woocommerce_data__WEBPACK_IMPORTED_MODULE_20___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_data__WEBPACK_IMPORTED_MODULE_20__);
/* harmony import */ var dashboard_components_connect__WEBPACK_IMPORTED_MODULE_21__ = __webpack_require__(/*! dashboard/components/connect */ "./client/dashboard/components/connect/index.js");
/* harmony import */ var lib_notices__WEBPACK_IMPORTED_MODULE_22__ = __webpack_require__(/*! lib/notices */ "./client/lib/notices/index.js");
/* harmony import */ var dashboard_utils__WEBPACK_IMPORTED_MODULE_23__ = __webpack_require__(/*! dashboard/utils */ "./client/dashboard/utils.js");
/* harmony import */ var _steps_location__WEBPACK_IMPORTED_MODULE_24__ = __webpack_require__(/*! ./steps/location */ "./client/task-list/tasks/steps/location.js");
/* harmony import */ var lib_tracks__WEBPACK_IMPORTED_MODULE_25__ = __webpack_require__(/*! lib/tracks */ "./client/lib/tracks.js");












function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_2___default()(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_9___default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_9___default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_8___default()(this, result); }; }

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
  _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_7___default()(Tax, _Component);

  var _super = _createSuper(Tax);

  function Tax(props) {
    var _this;

    _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_4___default()(this, Tax);

    _this = _super.call(this, props);
    var hasCompleteAddress = props.hasCompleteAddress,
        pluginsToActivate = props.pluginsToActivate;
    _this.initialState = {
      isPending: false,
      stepIndex: hasCompleteAddress ? 1 : 0,
      // Cache the value of pluginsToActivate so that we can
      // show/hide tasks based on it, but not have them update mid task.
      cachedPluginsToActivate: pluginsToActivate
    };
    _this.state = _this.initialState;
    _this.completeStep = _this.completeStep.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_6___default()(_this));
    return _this;
  }

  _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_5___default()(Tax, [{
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
      var _this$props = this.props,
          isJetpackConnected = _this$props.isJetpackConnected,
          hasCompleteAddress = _this$props.hasCompleteAddress,
          pluginsToActivate = _this$props.pluginsToActivate;
      return hasCompleteAddress && !pluginsToActivate.length && isJetpackConnected && this.isTaxJarSupported();
    }
  }, {
    key: "isTaxJarSupported",
    value: function isTaxJarSupported() {
      var countryCode = this.props.countryCode;

      var _getSetting = Object(_woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_19__["getSetting"])('onboarding', {}),
          _getSetting$automated = _getSetting.automatedTaxSupportedCountries,
          automatedTaxSupportedCountries = _getSetting$automated === void 0 ? [] : _getSetting$automated,
          taxJarActivated = _getSetting.taxJarActivated;

      return !taxJarActivated && // WCS integration doesn't work with the official TaxJar plugin.
      automatedTaxSupportedCountries.includes(countryCode);
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
      }
    }
  }, {
    key: "manuallyConfigureTaxRates",
    value: function () {
      var _manuallyConfigureTaxRates = _babel_runtime_helpers_asyncToGenerator__WEBPACK_IMPORTED_MODULE_3___default()( /*#__PURE__*/_babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_1___default.a.mark(function _callee() {
        var _this2 = this;

        var _this$props2, generalSettings, updateAndPersistSettingsForGroup;

        return _babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_1___default.a.wrap(function _callee$(_context) {
          while (1) {
            switch (_context.prev = _context.next) {
              case 0:
                _this$props2 = this.props, generalSettings = _this$props2.generalSettings, updateAndPersistSettingsForGroup = _this$props2.updateAndPersistSettingsForGroup;

                if (generalSettings.woocommerce_calc_taxes !== 'yes') {
                  this.setState({
                    isPending: true
                  });
                  updateAndPersistSettingsForGroup('general', {
                    general: _objectSpread(_objectSpread({}, generalSettings), {}, {
                      woocommerce_calc_taxes: 'yes'
                    })
                  }).then(function () {
                    return _this2.redirectToTaxSettings();
                  }).catch(function (error) {
                    return Object(lib_notices__WEBPACK_IMPORTED_MODULE_22__["createNoticesFromResponse"])(error);
                  });
                } else {
                  this.redirectToTaxSettings();
                }

              case 2:
              case "end":
                return _context.stop();
            }
          }
        }, _callee, this);
      }));

      function manuallyConfigureTaxRates() {
        return _manuallyConfigureTaxRates.apply(this, arguments);
      }

      return manuallyConfigureTaxRates;
    }()
  }, {
    key: "updateAutomatedTax",
    value: function updateAutomatedTax(isEnabling) {
      var _this3 = this;

      var _this$props3 = this.props,
          createNotice = _this$props3.createNotice,
          updateAndPersistSettingsForGroup = _this$props3.updateAndPersistSettingsForGroup,
          generalSettings = _this$props3.generalSettings,
          taxSettings = _this$props3.taxSettings;
      Promise.all([updateAndPersistSettingsForGroup('tax', {
        tax: _objectSpread(_objectSpread({}, taxSettings), {}, {
          wc_connect_taxes_enabled: isEnabling ? 'yes' : 'no'
        })
      }), updateAndPersistSettingsForGroup('general', {
        general: _objectSpread(_objectSpread({}, generalSettings), {}, {
          woocommerce_calc_taxes: 'yes'
        })
      })]).then(function () {
        // @todo This is a workaround to force the task to mark as complete.
        // This should probably be updated to use wc-api so we can fetch tax rates.
        Object(_woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_19__["setSetting"])('onboarding', _objectSpread(_objectSpread({}, Object(_woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_19__["getSetting"])('onboarding', {})), {}, {
          isTaxComplete: true
        }));

        if (isEnabling) {
          createNotice('success', Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__["__"])("You're awesome! One less item on your to-do list ✅", 'woocommerce'));
          Object(_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_18__["getHistory"])().push(Object(_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_18__["getNewPath"])({}, '/', {}));
        } else {
          _this3.redirectToTaxSettings();
        }
      }).catch(function () {
        createNotice('error', Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__["__"])('There was a problem updating your tax settings.', 'woocommerce'));
      });
    }
  }, {
    key: "redirectToTaxSettings",
    value: function redirectToTaxSettings() {
      window.location = Object(_woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_19__["getAdminLink"])('admin.php?page=wc-settings&tab=tax&section=standard&wc_onboarding_active_task=tax');
    }
  }, {
    key: "getSteps",
    value: function getSteps() {
      var _this4 = this;

      var _this$props4 = this.props,
          generalSettings = _this$props4.generalSettings,
          isJetpackConnected = _this$props4.isJetpackConnected,
          isPending = _this$props4.isPending,
          tosAccepted = _this$props4.tosAccepted,
          updateOptions = _this$props4.updateOptions;
      var cachedPluginsToActivate = this.state.cachedPluginsToActivate;
      var steps = [{
        key: 'store_location',
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__["__"])('Set store location', 'woocommerce'),
        description: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__["__"])('The address from which your business operates', 'woocommerce'),
        content: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__["createElement"])(_steps_location__WEBPACK_IMPORTED_MODULE_24__["default"], _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0___default()({}, this.props, {
          onComplete: function onComplete(values) {
            var country = Object(dashboard_utils__WEBPACK_IMPORTED_MODULE_23__["getCountryCode"])(values.countryState);
            Object(lib_tracks__WEBPACK_IMPORTED_MODULE_25__["recordEvent"])('tasklist_tax_set_location', {
              country: country
            });

            _this4.completeStep();
          },
          isSettingsRequesting: false,
          settings: generalSettings
        })),
        visible: true
      }, {
        key: 'plugins',
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__["__"])('Install Jetpack and WooCommerce Services', 'woocommerce'),
        description: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__["__"])('Jetpack and WooCommerce Services allow you to automate sales tax calculations', 'woocommerce'),
        content: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__["createElement"])(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__["Fragment"], null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_17__["Plugins"], {
          onComplete: function onComplete() {
            Object(lib_tracks__WEBPACK_IMPORTED_MODULE_25__["recordEvent"])('tasklist_tax_install_extensions', {
              install_extensions: true
            });
            updateOptions({
              woocommerce_setup_jetpack_opted_in: true
            });

            _this4.completeStep();
          },
          onSkip: function onSkip() {
            Object(lib_tracks__WEBPACK_IMPORTED_MODULE_25__["queueRecordEvent"])('tasklist_tax_install_extensions', {
              install_extensions: false
            });

            _this4.manuallyConfigureTaxRates();
          },
          skipText: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__["__"])('Set up tax rates manually', 'woocommerce')
        }), !tosAccepted && Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_12__["__experimentalText"], {
          variant: "caption",
          className: "woocommerce-task__caption"
        }, interpolate_components__WEBPACK_IMPORTED_MODULE_15___default()({
          mixedString: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__["__"])('By installing Jetpack and WooCommerce Services you agree to the {{link}}Terms of Service{{/link}}.', 'woocommerce'),
          components: {
            link: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_17__["Link"], {
              href: 'https://wordpress.com/tos/',
              target: "_blank",
              type: "external"
            })
          }
        }))),
        visible: (cachedPluginsToActivate.length || !tosAccepted) && this.isTaxJarSupported()
      }, {
        key: 'connect',
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__["__"])('Connect your store', 'woocommerce'),
        description: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__["__"])('Connect your store to WordPress.com to enable automated sales tax calculations', 'woocommerce'),
        content: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__["createElement"])(dashboard_components_connect__WEBPACK_IMPORTED_MODULE_21__["default"], _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0___default()({}, this.props, {
          onConnect: function onConnect() {
            Object(lib_tracks__WEBPACK_IMPORTED_MODULE_25__["recordEvent"])('tasklist_tax_connect_store', {
              connect: true
            });
          },
          onSkip: function onSkip() {
            Object(lib_tracks__WEBPACK_IMPORTED_MODULE_25__["queueRecordEvent"])('tasklist_tax_connect_store', {
              connect: false
            });

            _this4.manuallyConfigureTaxRates();
          },
          skipText: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__["__"])('Set up tax rates manually', 'woocommerce')
        })),
        visible: !isJetpackConnected && this.isTaxJarSupported()
      }, {
        key: 'manual_configuration',
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__["__"])('Configure tax rates', 'woocommerce'),
        description: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__["__"])('Head over to the tax rate settings screen to configure your tax rates', 'woocommerce'),
        content: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__["createElement"])(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__["Fragment"], null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_12__["Button"], {
          disabled: isPending,
          isPrimary: true,
          isBusy: isPending,
          onClick: function onClick() {
            Object(lib_tracks__WEBPACK_IMPORTED_MODULE_25__["recordEvent"])('tasklist_tax_config_rates');

            _this4.manuallyConfigureTaxRates();
          }
        }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__["__"])('Configure', 'woocommerce')), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__["createElement"])("p", null, generalSettings.woocommerce_calc_taxes !== 'yes' && interpolate_components__WEBPACK_IMPORTED_MODULE_15___default()({
          mixedString: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__["__"])(
          /*eslint-disable max-len*/
          'By clicking "Configure" you\'re enabling tax rates and calculations. More info {{link}}here{{/link}}.',
          /*eslint-enable max-len*/
          'woocommerce'),
          components: {
            link: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_17__["Link"], {
              href: "https://docs.woocommerce.com/document/setting-up-taxes-in-woocommerce/#section-1",
              target: "_blank",
              type: "external"
            })
          }
        }))),
        visible: !this.isTaxJarSupported()
      }];
      return Object(lodash__WEBPACK_IMPORTED_MODULE_14__["filter"])(steps, function (step) {
        return step.visible;
      });
    }
  }, {
    key: "render",
    value: function render() {
      var _this5 = this;

      var stepIndex = this.state.stepIndex;
      var _this$props5 = this.props,
          isPending = _this$props5.isPending,
          isResolving = _this$props5.isResolving;
      var step = this.getSteps()[stepIndex];
      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__["createElement"])("div", {
        className: "woocommerce-task-tax"
      }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_17__["Card"], {
        className: "is-narrow"
      }, this.shouldShowSuccessScreen() ? Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__["createElement"])("div", {
        className: "woocommerce-task-tax__success"
      }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__["createElement"])("span", {
        className: "woocommerce-task-tax__success-icon",
        role: "img",
        "aria-labelledby": "woocommerce-task-tax__success-message"
      }, "\uD83C\uDF8A"), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_17__["H"], {
        id: "woocommerce-task-tax__success-message"
      }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__["__"])('Good news!', 'woocommerce')), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__["createElement"])("p", null, interpolate_components__WEBPACK_IMPORTED_MODULE_15___default()({
        mixedString: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__["__"])('{{strong}}Jetpack{{/strong}} and {{strong}}WooCommerce Services{{/strong}} ' + 'can automate your sales tax calculations for you.', 'woocommerce'),
        components: {
          strong: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__["createElement"])("strong", null)
        }
      })), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_12__["Button"], {
        disabled: isPending,
        isPrimary: true,
        isBusy: isPending,
        onClick: function onClick() {
          Object(lib_tracks__WEBPACK_IMPORTED_MODULE_25__["recordEvent"])('tasklist_tax_setup_automated_proceed', {
            setup_automatically: true
          });

          _this5.updateAutomatedTax(true);
        }
      }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__["__"])('Yes please', 'woocommerce')), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_12__["Button"], {
        disabled: isPending,
        isBusy: isPending,
        onClick: function onClick() {
          Object(lib_tracks__WEBPACK_IMPORTED_MODULE_25__["recordEvent"])('tasklist_tax_setup_automated_proceed', {
            setup_automatically: false
          });

          _this5.updateAutomatedTax(false);
        }
      }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_11__["__"])("No thanks, I'll configure taxes manually", 'woocommerce'))) : Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_17__["Stepper"], {
        isPending: isPending || isResolving,
        isVertical: true,
        currentStep: step.key,
        steps: this.getSteps()
      })));
    }
  }]);

  return Tax;
}(_wordpress_element__WEBPACK_IMPORTED_MODULE_10__["Component"]);

/* harmony default export */ __webpack_exports__["default"] = (Object(_wordpress_compose__WEBPACK_IMPORTED_MODULE_13__["compose"])(Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_16__["withSelect"])(function (select) {
  var _select = select(_woocommerce_data__WEBPACK_IMPORTED_MODULE_20__["SETTINGS_STORE_NAME"]),
      getSettings = _select.getSettings,
      isUpdateSettingsRequesting = _select.isUpdateSettingsRequesting;

  var _select2 = select(_woocommerce_data__WEBPACK_IMPORTED_MODULE_20__["OPTIONS_STORE_NAME"]),
      getOption = _select2.getOption;

  var _select3 = select(_woocommerce_data__WEBPACK_IMPORTED_MODULE_20__["PLUGINS_STORE_NAME"]),
      getActivePlugins = _select3.getActivePlugins,
      isJetpackConnected = _select3.isJetpackConnected,
      isPluginsRequesting = _select3.isPluginsRequesting;

  var _getSettings = getSettings('general'),
      _getSettings$general = _getSettings.general,
      generalSettings = _getSettings$general === void 0 ? {} : _getSettings$general;

  var countryCode = Object(dashboard_utils__WEBPACK_IMPORTED_MODULE_23__["getCountryCode"])(generalSettings.woocommerce_default_country);
  var storeAddress = generalSettings.woocommerce_store_address,
      defaultCountry = generalSettings.woocommerce_default_country,
      storePostCode = generalSettings.woocommerce_store_postcode;
  var hasCompleteAddress = Boolean(storeAddress && defaultCountry && storePostCode);

  var _getSettings2 = getSettings('tax'),
      _getSettings2$tax = _getSettings2.tax,
      taxSettings = _getSettings2$tax === void 0 ? {} : _getSettings2$tax;

  var activePlugins = getActivePlugins();
  var pluginsToActivate = Object(lodash__WEBPACK_IMPORTED_MODULE_14__["difference"])(['jetpack', 'woocommerce-services'], activePlugins);
  var connectOptions = getOption('wc_connect_options') || {};
  var tosAccepted = connectOptions.tos_accepted || getOption('woocommerce_setup_jetpack_opted_in');
  var isPending = isUpdateSettingsRequesting('tax') || isUpdateSettingsRequesting('general');
  var isResolving = isPluginsRequesting('getJetpackConnectUrl');
  return {
    countryCode: countryCode,
    generalSettings: generalSettings,
    hasCompleteAddress: hasCompleteAddress,
    isJetpackConnected: isJetpackConnected(),
    isPending: isPending,
    isResolving: isResolving,
    pluginsToActivate: pluginsToActivate,
    taxSettings: taxSettings,
    tosAccepted: tosAccepted
  };
}), Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_16__["withDispatch"])(function (dispatch) {
  var _dispatch = dispatch('core/notices'),
      createNotice = _dispatch.createNotice;

  var _dispatch2 = dispatch(_woocommerce_data__WEBPACK_IMPORTED_MODULE_20__["OPTIONS_STORE_NAME"]),
      updateOptions = _dispatch2.updateOptions;

  var _dispatch3 = dispatch(_woocommerce_data__WEBPACK_IMPORTED_MODULE_20__["SETTINGS_STORE_NAME"]),
      updateAndPersistSettingsForGroup = _dispatch3.updateAndPersistSettingsForGroup;

  return {
    createNotice: createNotice,
    updateAndPersistSettingsForGroup: updateAndPersistSettingsForGroup,
    updateOptions: updateOptions
  };
}))(Tax));

/***/ })

}]);
//# sourceMappingURL=task-list.1e0e9839d6e9bce3818d.min.js.map
