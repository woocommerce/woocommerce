(window["webpackJsonp"] = window["webpackJsonp"] || []).push([["activity-panels-stock"],{

/***/ "./client/header/activity-panel/panels/stock/card.js":
/*!***********************************************************!*\
  !*** ./client/header/activity-panel/panels/stock/card.js ***!
  \***********************************************************/
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
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! classnames */ "./node_modules/classnames/index.js");
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(classnames__WEBPACK_IMPORTED_MODULE_9__);
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! @wordpress/compose */ "./node_modules/@wordpress/compose/build-module/index.js");
/* harmony import */ var _wordpress_keycodes__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! @wordpress/keycodes */ "./node_modules/@wordpress/keycodes/build-module/index.js");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! lodash */ "lodash");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_12___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_12__);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_13___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_13__);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! @woocommerce/components */ "@woocommerce/components");
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_14___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_components__WEBPACK_IMPORTED_MODULE_14__);
/* harmony import */ var _woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(/*! @woocommerce/wc-admin-settings */ "./client/settings/index.js");
/* harmony import */ var _activity_card__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__(/*! ../../activity-card */ "./client/header/activity-panel/activity-card/index.js");








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



var ProductStockCard = /*#__PURE__*/function (_Component) {
  _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_3___default()(ProductStockCard, _Component);

  var _super = _createSuper(ProductStockCard);

  function ProductStockCard(props) {
    var _this;

    _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0___default()(this, ProductStockCard);

    _this = _super.call(this, props);
    _this.state = {
      quantity: props.product.stock_quantity,
      editing: false,
      edited: false
    };
    _this.beginEdit = _this.beginEdit.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_2___default()(_this));
    _this.cancelEdit = _this.cancelEdit.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_2___default()(_this));
    _this.onQuantityChange = _this.onQuantityChange.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_2___default()(_this));
    _this.handleKeyDown = _this.handleKeyDown.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_2___default()(_this));
    _this.onSubmit = _this.onSubmit.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_2___default()(_this));
    return _this;
  }

  _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1___default()(ProductStockCard, [{
    key: "beginEdit",
    value: function beginEdit() {
      var _this2 = this;

      var product = this.props.product;
      this.setState({
        editing: true,
        quantity: product.stock_quantity
      }, function () {
        if (_this2.quantityInput) {
          _this2.quantityInput.focus();
        }
      });
    }
  }, {
    key: "cancelEdit",
    value: function cancelEdit() {
      var product = this.props.product;
      this.setState({
        editing: false,
        quantity: product.stock_quantity
      });
    }
  }, {
    key: "handleKeyDown",
    value: function handleKeyDown(event) {
      if (event.keyCode === _wordpress_keycodes__WEBPACK_IMPORTED_MODULE_11__["ESCAPE"]) {
        this.cancelEdit();
      }
    }
  }, {
    key: "onQuantityChange",
    value: function onQuantityChange(event) {
      this.setState({
        quantity: event.target.value
      });
    }
  }, {
    key: "onSubmit",
    value: function onSubmit() {
      var _this$props = this.props,
          product = _this$props.product,
          updateProductStock = _this$props.updateProductStock;
      var quantity = this.state.quantity;
      this.setState({
        editing: false,
        edited: true
      });
      updateProductStock(product, quantity);
    }
  }, {
    key: "getActions",
    value: function getActions() {
      var editing = this.state.editing;

      if (editing) {
        return [Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_8__["Button"], {
          key: "save",
          type: "submit",
          isPrimary: true
        }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__["__"])('Save', 'woocommerce')), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_8__["Button"], {
          key: "cancel",
          type: "reset"
        }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__["__"])('Cancel', 'woocommerce'))];
      }

      return [Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_8__["Button"], {
        key: "update",
        isSecondary: true,
        onClick: this.beginEdit
      }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__["__"])('Update stock', 'woocommerce'))];
    }
  }, {
    key: "getBody",
    value: function getBody() {
      var _this3 = this;

      var product = this.props.product;
      var _this$state = this.state,
          editing = _this$state.editing,
          quantity = _this$state.quantity;

      if (editing) {
        return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["Fragment"], null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_8__["BaseControl"], {
          className: "woocommerce-stock-activity-card__edit-quantity"
        }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("input", {
          className: "components-text-control__input",
          type: "number",
          value: quantity,
          onKeyDown: this.handleKeyDown,
          onChange: this.onQuantityChange,
          ref: function ref(input) {
            _this3.quantityInput = input;
          }
        })), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("span", null, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__["__"])('in stock', 'woocommerce')));
      }

      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("span", {
        className: "woocommerce-stock-activity-card__stock-quantity"
      }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__["sprintf"])(Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__["__"])('%d in stock', 'woocommerce'), product.stock_quantity));
    }
  }, {
    key: "render",
    value: function render() {
      var product = this.props.product;
      var _this$state2 = this.state,
          edited = _this$state2.edited,
          editing = _this$state2.editing;
      var notifyLowStockAmount = Object(_woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_15__["getSetting"])('notifyLowStockAmount', 0);
      var lowStockAmount = Number.isFinite(product.low_stock_amount) ? product.low_stock_amount : notifyLowStockAmount;
      var isLowStock = product.stock_quantity <= lowStockAmount; // Hide cards that are not in low stock and have not been edited.
      // This allows clearing cards which are no longer in low stock after
      // closing & opening the panel without having to make another request.

      if (!isLowStock && !edited) {
        return null;
      }

      var title = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_14__["Link"], {
        href: 'post.php?action=edit&post=' + (product.parent_id || product.id),
        type: "wp-admin"
      }, product.name);
      var subtitle = null;

      if (product.type === 'variation') {
        subtitle = Object.values(product.attributes).map(function (attr) {
          return attr.option;
        }).join(', ');
      }

      var productImage = Object(lodash__WEBPACK_IMPORTED_MODULE_12__["get"])(product, ['images', 0]) || Object(lodash__WEBPACK_IMPORTED_MODULE_12__["get"])(product, ['image']);
      var productImageClasses = classnames__WEBPACK_IMPORTED_MODULE_9___default()('woocommerce-stock-activity-card__image-overlay__product', {
        'is-placeholder': !productImage || !productImage.src
      });
      var icon = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("div", {
        className: "woocommerce-stock-activity-card__image-overlay"
      }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("div", {
        className: productImageClasses
      }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_14__["ProductImage"], {
        product: product
      })));
      var activityCardClasses = classnames__WEBPACK_IMPORTED_MODULE_9___default()('woocommerce-stock-activity-card', {
        'is-dimmed': !editing && !isLowStock
      });
      var activityCard = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])(_activity_card__WEBPACK_IMPORTED_MODULE_16__["ActivityCard"], {
        className: activityCardClasses,
        title: title,
        subtitle: subtitle,
        icon: icon,
        actions: this.getActions()
      }, this.getBody());

      if (editing) {
        return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["createElement"])("form", {
          onReset: this.cancelEdit,
          onSubmit: this.onSubmit
        }, activityCard);
      }

      return activityCard;
    }
  }]);

  return ProductStockCard;
}(_wordpress_element__WEBPACK_IMPORTED_MODULE_6__["Component"]);

/* harmony default export */ __webpack_exports__["default"] = (Object(_wordpress_compose__WEBPACK_IMPORTED_MODULE_10__["compose"])(Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_13__["withDispatch"])(function (dispatch) {
  var _dispatch = dispatch('wc-api'),
      updateProductStock = _dispatch.updateProductStock;

  return {
    updateProductStock: updateProductStock
  };
}))(ProductStockCard));

/***/ }),

/***/ "./client/header/activity-panel/panels/stock/index.js":
/*!************************************************************!*\
  !*** ./client/header/activity-panel/panels/stock/index.js ***!
  \************************************************************/
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
/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! prop-types */ "./node_modules/prop-types/index.js");
/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(prop_types__WEBPACK_IMPORTED_MODULE_8__);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! @woocommerce/components */ "@woocommerce/components");
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_components__WEBPACK_IMPORTED_MODULE_9__);
/* harmony import */ var _activity_card__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! ../../activity-card */ "./client/header/activity-panel/activity-card/index.js");
/* harmony import */ var _activity_header__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! ../../activity-header */ "./client/header/activity-panel/activity-header/index.js");
/* harmony import */ var gridicons__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! gridicons */ "./node_modules/gridicons/dist/index.js");
/* harmony import */ var gridicons__WEBPACK_IMPORTED_MODULE_12___default = /*#__PURE__*/__webpack_require__.n(gridicons__WEBPACK_IMPORTED_MODULE_12__);
/* harmony import */ var _card__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! ./card */ "./client/header/activity-panel/panels/stock/card.js");
/* harmony import */ var wc_api_constants__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! wc-api/constants */ "./client/wc-api/constants.js");
/* harmony import */ var wc_api_with_select__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(/*! wc-api/with-select */ "./client/wc-api/with-select.js");







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








var StockPanel = /*#__PURE__*/function (_Component) {
  _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_2___default()(StockPanel, _Component);

  var _super = _createSuper(StockPanel);

  function StockPanel() {
    _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0___default()(this, StockPanel);

    return _super.apply(this, arguments);
  }

  _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1___default()(StockPanel, [{
    key: "renderEmptyCard",
    value: function renderEmptyCard() {
      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(_activity_card__WEBPACK_IMPORTED_MODULE_10__["ActivityCard"], {
        className: "woocommerce-empty-activity-card",
        title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__["__"])('Your stock is in good shape.', 'woocommerce'),
        icon: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(gridicons__WEBPACK_IMPORTED_MODULE_12___default.a, {
          icon: "checkmark",
          size: 48
        })
      }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__["__"])('You currently have no products running low on stock.', 'woocommerce'));
    }
  }, {
    key: "renderProducts",
    value: function renderProducts() {
      var products = this.props.products;

      if (products.length === 0) {
        return this.renderEmptyCard();
      }

      return products.map(function (product) {
        return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(_card__WEBPACK_IMPORTED_MODULE_13__["default"], {
          key: product.id,
          product: product
        });
      });
    }
  }, {
    key: "render",
    value: function render() {
      var _this$props = this.props,
          isError = _this$props.isError,
          isRequesting = _this$props.isRequesting,
          products = _this$props.products;

      if (isError) {
        var _title = Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__["__"])('There was an error getting your low stock products. Please try again.', 'woocommerce');

        var actionLabel = Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__["__"])('Reload', 'woocommerce');

        var actionCallback = function actionCallback() {
          // @todo Add tracking for how often an error is displayed, and the reload action is clicked.
          window.location.reload();
        };

        return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["Fragment"], null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_9__["EmptyContent"], {
          title: _title,
          actionLabel: actionLabel,
          actionURL: null,
          actionCallback: actionCallback
        }));
      }

      var title = isRequesting || products.length > 0 ? Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__["__"])('Stock', 'woocommerce') : Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__["__"])('No products with low stock', 'woocommerce');
      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["Fragment"], null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(_activity_header__WEBPACK_IMPORTED_MODULE_11__["default"], {
        title: title
      }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_9__["Section"], null, isRequesting ? Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(_activity_card__WEBPACK_IMPORTED_MODULE_10__["ActivityCardPlaceholder"], {
        className: "woocommerce-stock-activity-card",
        hasAction: true,
        lines: 1
      }) : this.renderProducts()));
    }
  }]);

  return StockPanel;
}(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["Component"]);

StockPanel.propTypes = {
  products: prop_types__WEBPACK_IMPORTED_MODULE_8___default.a.array.isRequired,
  isError: prop_types__WEBPACK_IMPORTED_MODULE_8___default.a.bool,
  isRequesting: prop_types__WEBPACK_IMPORTED_MODULE_8___default.a.bool
};
StockPanel.defaultProps = {
  products: [],
  isError: false,
  isRequesting: false
};
/* harmony default export */ __webpack_exports__["default"] = (Object(_wordpress_compose__WEBPACK_IMPORTED_MODULE_7__["compose"])(Object(wc_api_with_select__WEBPACK_IMPORTED_MODULE_15__["default"])(function (select) {
  var _select = select('wc-api'),
      getItems = _select.getItems,
      getItemsError = _select.getItemsError,
      isGetItemsRequesting = _select.isGetItemsRequesting;

  var productsQuery = {
    page: 1,
    per_page: wc_api_constants__WEBPACK_IMPORTED_MODULE_14__["QUERY_DEFAULTS"].pageSize,
    low_in_stock: true,
    status: 'publish'
  };
  var products = Array.from(getItems('products', productsQuery).values());
  var isError = Boolean(getItemsError('products', productsQuery));
  var isRequesting = isGetItemsRequesting('products', productsQuery);
  return {
    products: products,
    isError: isError,
    isRequesting: isRequesting
  };
}))(StockPanel));

/***/ }),

/***/ "./node_modules/@wordpress/keycodes/build-module/index.js":
/*!****************************************************************!*\
  !*** ./node_modules/@wordpress/keycodes/build-module/index.js ***!
  \****************************************************************/
/*! exports provided: BACKSPACE, TAB, ENTER, ESCAPE, SPACE, LEFT, UP, RIGHT, DOWN, DELETE, F10, ALT, CTRL, COMMAND, SHIFT, ZERO, modifiers, rawShortcut, displayShortcutList, displayShortcut, shortcutAriaLabel, isKeyboardEvent */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "BACKSPACE", function() { return BACKSPACE; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "TAB", function() { return TAB; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "ENTER", function() { return ENTER; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "ESCAPE", function() { return ESCAPE; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "SPACE", function() { return SPACE; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "LEFT", function() { return LEFT; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "UP", function() { return UP; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "RIGHT", function() { return RIGHT; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "DOWN", function() { return DOWN; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "DELETE", function() { return DELETE; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "F10", function() { return F10; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "ALT", function() { return ALT; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "CTRL", function() { return CTRL; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "COMMAND", function() { return COMMAND; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "SHIFT", function() { return SHIFT; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "ZERO", function() { return ZERO; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "modifiers", function() { return modifiers; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "rawShortcut", function() { return rawShortcut; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "displayShortcutList", function() { return displayShortcutList; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "displayShortcut", function() { return displayShortcut; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "shortcutAriaLabel", function() { return shortcutAriaLabel; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "isKeyboardEvent", function() { return isKeyboardEvent; });
/* harmony import */ var _babel_runtime_helpers_esm_defineProperty__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/esm/defineProperty */ "./node_modules/@babel/runtime/helpers/esm/defineProperty.js");
/* harmony import */ var _babel_runtime_helpers_esm_toConsumableArray__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/esm/toConsumableArray */ "./node_modules/@babel/runtime/helpers/esm/toConsumableArray.js");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! lodash */ "lodash");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _platform__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./platform */ "./node_modules/@wordpress/keycodes/build-module/platform.js");



/**
 * Note: The order of the modifier keys in many of the [foo]Shortcut()
 * functions in this file are intentional and should not be changed. They're
 * designed to fit with the standard menu keyboard shortcuts shown in the
 * user's platform.
 *
 * For example, on MacOS menu shortcuts will place Shift before Command, but
 * on Windows Control will usually come first. So don't provide your own
 * shortcut combos directly to keyboardShortcut().
 */

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */


/**
 * Internal dependencies
 */


/**
 * @typedef {'primary'|'primaryShift'|'primaryAlt'|'secondary'|'access'|'ctrl'|'alt'|'ctrlShift'|'shift'|'shiftAlt'} WPKeycodeModifier
 */

/**
 * An object of handler functions for each of the possible modifier
 * combinations. A handler will return a value for a given key.
 *
 * @typedef {Record<WPKeycodeModifier, (key:string)=>any>} WPKeycodeHandlerByModifier
 */

/**
 * Keycode for BACKSPACE key.
 */

var BACKSPACE = 8;
/**
 * Keycode for TAB key.
 */

var TAB = 9;
/**
 * Keycode for ENTER key.
 */

var ENTER = 13;
/**
 * Keycode for ESCAPE key.
 */

var ESCAPE = 27;
/**
 * Keycode for SPACE key.
 */

var SPACE = 32;
/**
 * Keycode for LEFT key.
 */

var LEFT = 37;
/**
 * Keycode for UP key.
 */

var UP = 38;
/**
 * Keycode for RIGHT key.
 */

var RIGHT = 39;
/**
 * Keycode for DOWN key.
 */

var DOWN = 40;
/**
 * Keycode for DELETE key.
 */

var DELETE = 46;
/**
 * Keycode for F10 key.
 */

var F10 = 121;
/**
 * Keycode for ALT key.
 */

var ALT = 'alt';
/**
 * Keycode for CTRL key.
 */

var CTRL = 'ctrl';
/**
 * Keycode for COMMAND/META key.
 */

var COMMAND = 'meta';
/**
 * Keycode for SHIFT key.
 */

var SHIFT = 'shift';
/**
 * Keycode for ZERO key.
 */

var ZERO = 48;
/**
 * Object that contains functions that return the available modifier
 * depending on platform.
 *
 * - `primary`: takes a isApple function as a parameter.
 * - `primaryShift`: takes a isApple function as a parameter.
 * - `primaryAlt`: takes a isApple function as a parameter.
 * - `secondary`: takes a isApple function as a parameter.
 * - `access`: takes a isApple function as a parameter.
 * - `ctrl`
 * - `alt`
 * - `ctrlShift`
 * - `shift`
 * - `shiftAlt`
 */

var modifiers = {
  primary: function primary(_isApple) {
    return _isApple() ? [COMMAND] : [CTRL];
  },
  primaryShift: function primaryShift(_isApple) {
    return _isApple() ? [SHIFT, COMMAND] : [CTRL, SHIFT];
  },
  primaryAlt: function primaryAlt(_isApple) {
    return _isApple() ? [ALT, COMMAND] : [CTRL, ALT];
  },
  secondary: function secondary(_isApple) {
    return _isApple() ? [SHIFT, ALT, COMMAND] : [CTRL, SHIFT, ALT];
  },
  access: function access(_isApple) {
    return _isApple() ? [CTRL, ALT] : [SHIFT, ALT];
  },
  ctrl: function ctrl() {
    return [CTRL];
  },
  alt: function alt() {
    return [ALT];
  },
  ctrlShift: function ctrlShift() {
    return [CTRL, SHIFT];
  },
  shift: function shift() {
    return [SHIFT];
  },
  shiftAlt: function shiftAlt() {
    return [SHIFT, ALT];
  }
};
/**
 * An object that contains functions to get raw shortcuts.
 * E.g. rawShortcut.primary( 'm' ) will return 'meta+m' on Mac.
 * These are intended for user with the KeyboardShortcuts component or TinyMCE.
 *
 * @type {WPKeycodeHandlerByModifier} Keyed map of functions to raw shortcuts.
 */

var rawShortcut = Object(lodash__WEBPACK_IMPORTED_MODULE_2__["mapValues"])(modifiers, function (modifier) {
  return function (character) {
    var _isApple = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : _platform__WEBPACK_IMPORTED_MODULE_4__["isAppleOS"];

    return [].concat(Object(_babel_runtime_helpers_esm_toConsumableArray__WEBPACK_IMPORTED_MODULE_1__["default"])(modifier(_isApple)), [character.toLowerCase()]).join('+');
  };
});
/**
 * Return an array of the parts of a keyboard shortcut chord for display
 * E.g displayShortcutList.primary( 'm' ) will return [ '⌘', 'M' ] on Mac.
 *
 * @type {WPKeycodeHandlerByModifier} Keyed map of functions to shortcut
 *                                    sequences.
 */

var displayShortcutList = Object(lodash__WEBPACK_IMPORTED_MODULE_2__["mapValues"])(modifiers, function (modifier) {
  return function (character) {
    var _replacementKeyMap;

    var _isApple = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : _platform__WEBPACK_IMPORTED_MODULE_4__["isAppleOS"];

    var isApple = _isApple();

    var replacementKeyMap = (_replacementKeyMap = {}, Object(_babel_runtime_helpers_esm_defineProperty__WEBPACK_IMPORTED_MODULE_0__["default"])(_replacementKeyMap, ALT, isApple ? '⌥' : 'Alt'), Object(_babel_runtime_helpers_esm_defineProperty__WEBPACK_IMPORTED_MODULE_0__["default"])(_replacementKeyMap, CTRL, isApple ? '^' : 'Ctrl'), Object(_babel_runtime_helpers_esm_defineProperty__WEBPACK_IMPORTED_MODULE_0__["default"])(_replacementKeyMap, COMMAND, '⌘'), Object(_babel_runtime_helpers_esm_defineProperty__WEBPACK_IMPORTED_MODULE_0__["default"])(_replacementKeyMap, SHIFT, isApple ? '⇧' : 'Shift'), _replacementKeyMap);
    var modifierKeys = modifier(_isApple).reduce(function (accumulator, key) {
      var replacementKey = Object(lodash__WEBPACK_IMPORTED_MODULE_2__["get"])(replacementKeyMap, key, key); // If on the Mac, adhere to platform convention and don't show plus between keys.

      if (isApple) {
        return [].concat(Object(_babel_runtime_helpers_esm_toConsumableArray__WEBPACK_IMPORTED_MODULE_1__["default"])(accumulator), [replacementKey]);
      }

      return [].concat(Object(_babel_runtime_helpers_esm_toConsumableArray__WEBPACK_IMPORTED_MODULE_1__["default"])(accumulator), [replacementKey, '+']);
    }, []);
    var capitalizedCharacter = Object(lodash__WEBPACK_IMPORTED_MODULE_2__["capitalize"])(character);
    return [].concat(Object(_babel_runtime_helpers_esm_toConsumableArray__WEBPACK_IMPORTED_MODULE_1__["default"])(modifierKeys), [capitalizedCharacter]);
  };
});
/**
 * An object that contains functions to display shortcuts.
 * E.g. displayShortcut.primary( 'm' ) will return '⌘M' on Mac.
 *
 * @type {WPKeycodeHandlerByModifier} Keyed map of functions to display
 *                                    shortcuts.
 */

var displayShortcut = Object(lodash__WEBPACK_IMPORTED_MODULE_2__["mapValues"])(displayShortcutList, function (shortcutList) {
  return function (character) {
    var _isApple = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : _platform__WEBPACK_IMPORTED_MODULE_4__["isAppleOS"];

    return shortcutList(character, _isApple).join('');
  };
});
/**
 * An object that contains functions to return an aria label for a keyboard shortcut.
 * E.g. shortcutAriaLabel.primary( '.' ) will return 'Command + Period' on Mac.
 *
 * @type {WPKeycodeHandlerByModifier} Keyed map of functions to shortcut ARIA
 *                                    labels.
 */

var shortcutAriaLabel = Object(lodash__WEBPACK_IMPORTED_MODULE_2__["mapValues"])(modifiers, function (modifier) {
  return function (character) {
    var _replacementKeyMap2;

    var _isApple = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : _platform__WEBPACK_IMPORTED_MODULE_4__["isAppleOS"];

    var isApple = _isApple();

    var replacementKeyMap = (_replacementKeyMap2 = {}, Object(_babel_runtime_helpers_esm_defineProperty__WEBPACK_IMPORTED_MODULE_0__["default"])(_replacementKeyMap2, SHIFT, 'Shift'), Object(_babel_runtime_helpers_esm_defineProperty__WEBPACK_IMPORTED_MODULE_0__["default"])(_replacementKeyMap2, COMMAND, isApple ? 'Command' : 'Control'), Object(_babel_runtime_helpers_esm_defineProperty__WEBPACK_IMPORTED_MODULE_0__["default"])(_replacementKeyMap2, CTRL, 'Control'), Object(_babel_runtime_helpers_esm_defineProperty__WEBPACK_IMPORTED_MODULE_0__["default"])(_replacementKeyMap2, ALT, isApple ? 'Option' : 'Alt'), Object(_babel_runtime_helpers_esm_defineProperty__WEBPACK_IMPORTED_MODULE_0__["default"])(_replacementKeyMap2, ',', Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__["__"])('Comma')), Object(_babel_runtime_helpers_esm_defineProperty__WEBPACK_IMPORTED_MODULE_0__["default"])(_replacementKeyMap2, '.', Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__["__"])('Period')), Object(_babel_runtime_helpers_esm_defineProperty__WEBPACK_IMPORTED_MODULE_0__["default"])(_replacementKeyMap2, '`', Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__["__"])('Backtick')), _replacementKeyMap2);
    return [].concat(Object(_babel_runtime_helpers_esm_toConsumableArray__WEBPACK_IMPORTED_MODULE_1__["default"])(modifier(_isApple)), [character]).map(function (key) {
      return Object(lodash__WEBPACK_IMPORTED_MODULE_2__["capitalize"])(Object(lodash__WEBPACK_IMPORTED_MODULE_2__["get"])(replacementKeyMap, key, key));
    }).join(isApple ? ' ' : ' + ');
  };
});
/**
 * An object that contains functions to check if a keyboard event matches a
 * predefined shortcut combination.
 * E.g. isKeyboardEvent.primary( event, 'm' ) will return true if the event
 * signals pressing ⌘M.
 *
 * @type {WPKeycodeHandlerByModifier} Keyed map of functions to match events.
 */

var isKeyboardEvent = Object(lodash__WEBPACK_IMPORTED_MODULE_2__["mapValues"])(modifiers, function (getModifiers) {
  return function (event, character) {
    var _isApple = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : _platform__WEBPACK_IMPORTED_MODULE_4__["isAppleOS"];

    var mods = getModifiers(_isApple);

    if (!mods.every(function (key) {
      return event["".concat(key, "Key")];
    })) {
      return false;
    }

    if (!character) {
      return Object(lodash__WEBPACK_IMPORTED_MODULE_2__["includes"])(mods, event.key.toLowerCase());
    }

    return event.key === character;
  };
});
//# sourceMappingURL=index.js.map

/***/ }),

/***/ "./node_modules/@wordpress/keycodes/build-module/platform.js":
/*!*******************************************************************!*\
  !*** ./node_modules/@wordpress/keycodes/build-module/platform.js ***!
  \*******************************************************************/
/*! exports provided: isAppleOS */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "isAppleOS", function() { return isAppleOS; });
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! lodash */ "lodash");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_0__);
/**
 * External dependencies
 */

/**
 * Return true if platform is MacOS.
 *
 * @param {Object} _window   window object by default; used for DI testing.
 *
 * @return {boolean}         True if MacOS; false otherwise.
 */

function isAppleOS() {
  var _window = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : window;

  var platform = _window.navigator.platform;
  return platform.indexOf('Mac') !== -1 || Object(lodash__WEBPACK_IMPORTED_MODULE_0__["includes"])(['iPad', 'iPhone'], platform);
}
//# sourceMappingURL=platform.js.map

/***/ })

}]);
//# sourceMappingURL=activity-panels-stock.4cc41dd9fe16a0c01776.min.js.map
