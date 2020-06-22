(window["webpackJsonp"] = window["webpackJsonp"] || []).push([[7],{

/***/ 748:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";

// EXPORTS
__webpack_require__.d(__webpack_exports__, "a", function() { return /* binding */ activity_card_ActivityCard; });
__webpack_require__.d(__webpack_exports__, "b", function() { return /* reexport */ placeholder; });

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

// EXTERNAL MODULE: ./node_modules/classnames/index.js
var classnames = __webpack_require__(10);
var classnames_default = /*#__PURE__*/__webpack_require__.n(classnames);

// EXTERNAL MODULE: ./node_modules/gridicons/dist/index.js
var dist = __webpack_require__(66);
var dist_default = /*#__PURE__*/__webpack_require__.n(dist);

// EXTERNAL MODULE: external "moment"
var external_moment_ = __webpack_require__(12);
var external_moment_default = /*#__PURE__*/__webpack_require__.n(external_moment_);

// EXTERNAL MODULE: ./node_modules/prop-types/index.js
var prop_types = __webpack_require__(1);
var prop_types_default = /*#__PURE__*/__webpack_require__.n(prop_types);

// EXTERNAL MODULE: ./client/header/activity-panel/activity-card/style.scss
var style = __webpack_require__(756);

// EXTERNAL MODULE: external {"this":["wc","components"]}
var external_this_wc_components_ = __webpack_require__(63);

// EXTERNAL MODULE: external "lodash"
var external_lodash_ = __webpack_require__(2);

// CONCATENATED MODULE: ./client/header/activity-panel/activity-card/placeholder.js







function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function () { var Super = getPrototypeOf_default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = getPrototypeOf_default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return possibleConstructorReturn_default()(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/**
 * External dependencies
 */





var placeholder_ActivityCardPlaceholder = /*#__PURE__*/function (_Component) {
  inherits_default()(ActivityCardPlaceholder, _Component);

  var _super = _createSuper(ActivityCardPlaceholder);

  function ActivityCardPlaceholder() {
    classCallCheck_default()(this, ActivityCardPlaceholder);

    return _super.apply(this, arguments);
  }

  createClass_default()(ActivityCardPlaceholder, [{
    key: "render",
    value: function render() {
      var _this$props = this.props,
          className = _this$props.className,
          hasAction = _this$props.hasAction,
          hasDate = _this$props.hasDate,
          hasSubtitle = _this$props.hasSubtitle,
          lines = _this$props.lines;
      var cardClassName = classnames_default()('woocommerce-activity-card is-loading', className);
      return Object(external_this_wp_element_["createElement"])("div", {
        className: cardClassName,
        "aria-hidden": true
      }, Object(external_this_wp_element_["createElement"])("span", {
        className: "woocommerce-activity-card__icon"
      }, Object(external_this_wp_element_["createElement"])("span", {
        className: "is-placeholder"
      })), Object(external_this_wp_element_["createElement"])("div", {
        className: "woocommerce-activity-card__header"
      }, Object(external_this_wp_element_["createElement"])("div", {
        className: "woocommerce-activity-card__title is-placeholder"
      }), hasSubtitle && Object(external_this_wp_element_["createElement"])("div", {
        className: "woocommerce-activity-card__subtitle is-placeholder"
      }), hasDate && Object(external_this_wp_element_["createElement"])("div", {
        className: "woocommerce-activity-card__date"
      }, Object(external_this_wp_element_["createElement"])("span", {
        className: "is-placeholder"
      }))), Object(external_this_wp_element_["createElement"])("div", {
        className: "woocommerce-activity-card__body"
      }, Object(external_lodash_["range"])(lines).map(function (i) {
        return Object(external_this_wp_element_["createElement"])("span", {
          className: "is-placeholder",
          key: i
        });
      })), hasAction && Object(external_this_wp_element_["createElement"])("div", {
        className: "woocommerce-activity-card__actions"
      }, Object(external_this_wp_element_["createElement"])("span", {
        className: "is-placeholder"
      })));
    }
  }]);

  return ActivityCardPlaceholder;
}(external_this_wp_element_["Component"]);

placeholder_ActivityCardPlaceholder.propTypes = {
  className: prop_types_default.a.string,
  hasAction: prop_types_default.a.bool,
  hasDate: prop_types_default.a.bool,
  hasSubtitle: prop_types_default.a.bool,
  lines: prop_types_default.a.number
};
placeholder_ActivityCardPlaceholder.defaultProps = {
  hasAction: false,
  hasDate: false,
  hasSubtitle: false,
  lines: 1
};
/* harmony default export */ var placeholder = (placeholder_ActivityCardPlaceholder);
// CONCATENATED MODULE: ./client/header/activity-panel/activity-card/index.js







function activity_card_createSuper(Derived) { var hasNativeReflectConstruct = activity_card_isNativeReflectConstruct(); return function () { var Super = getPrototypeOf_default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = getPrototypeOf_default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return possibleConstructorReturn_default()(this, result); }; }

function activity_card_isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/**
 * External dependencies
 */





/**
 * Internal dependencies
 */




var activity_card_ActivityCard = /*#__PURE__*/function (_Component) {
  inherits_default()(ActivityCard, _Component);

  var _super = activity_card_createSuper(ActivityCard);

  function ActivityCard() {
    classCallCheck_default()(this, ActivityCard);

    return _super.apply(this, arguments);
  }

  createClass_default()(ActivityCard, [{
    key: "render",
    value: function render() {
      var _this$props = this.props,
          actions = _this$props.actions,
          className = _this$props.className,
          children = _this$props.children,
          date = _this$props.date,
          icon = _this$props.icon,
          subtitle = _this$props.subtitle,
          title = _this$props.title,
          unread = _this$props.unread;
      var cardClassName = classnames_default()('woocommerce-activity-card', className);
      var actionsList = Array.isArray(actions) ? actions : [actions];
      return Object(external_this_wp_element_["createElement"])("section", {
        className: cardClassName
      }, unread && Object(external_this_wp_element_["createElement"])("span", {
        className: "woocommerce-activity-card__unread"
      }), Object(external_this_wp_element_["createElement"])("span", {
        className: "woocommerce-activity-card__icon",
        "aria-hidden": true
      }, icon), Object(external_this_wp_element_["createElement"])("header", {
        className: "woocommerce-activity-card__header"
      }, Object(external_this_wp_element_["createElement"])(external_this_wc_components_["H"], {
        className: "woocommerce-activity-card__title"
      }, title), subtitle && Object(external_this_wp_element_["createElement"])("div", {
        className: "woocommerce-activity-card__subtitle"
      }, subtitle), date && Object(external_this_wp_element_["createElement"])("span", {
        className: "woocommerce-activity-card__date"
      }, external_moment_default.a.utc(date).fromNow())), Object(external_this_wp_element_["createElement"])(external_this_wc_components_["Section"], {
        className: "woocommerce-activity-card__body"
      }, children), actions && Object(external_this_wp_element_["createElement"])("footer", {
        className: "woocommerce-activity-card__actions"
      }, actionsList.map(function (item, i) {
        return Object(external_this_wp_element_["cloneElement"])(item, {
          key: i
        });
      })));
    }
  }]);

  return ActivityCard;
}(external_this_wp_element_["Component"]);

activity_card_ActivityCard.propTypes = {
  actions: prop_types_default.a.oneOfType([prop_types_default.a.arrayOf(prop_types_default.a.element), prop_types_default.a.element]),
  className: prop_types_default.a.string,
  children: prop_types_default.a.node.isRequired,
  date: prop_types_default.a.string,
  icon: prop_types_default.a.node,
  subtitle: prop_types_default.a.node,
  title: prop_types_default.a.oneOfType([prop_types_default.a.string, prop_types_default.a.node]).isRequired,
  unread: prop_types_default.a.bool
};
activity_card_ActivityCard.defaultProps = {
  icon: Object(external_this_wp_element_["createElement"])(dist_default.a, {
    icon: "notice-outline",
    size: 48
  }),
  unread: false
};



/***/ }),

/***/ 754:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(41);
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(40);
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(44);
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(29);
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(42);
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(10);
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(classnames__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(1);
/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(prop_types__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var _style_scss__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(757);
/* harmony import */ var _style_scss__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(_style_scss__WEBPACK_IMPORTED_MODULE_8__);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(63);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_components__WEBPACK_IMPORTED_MODULE_9__);







function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function () { var Super = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_3___default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_3___default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_2___default()(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/**
 * External dependencies
 */



/**
 * Internal dependencies
 */




var ActivityHeader = /*#__PURE__*/function (_Component) {
  _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_4___default()(ActivityHeader, _Component);

  var _super = _createSuper(ActivityHeader);

  function ActivityHeader() {
    _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0___default()(this, ActivityHeader);

    return _super.apply(this, arguments);
  }

  _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1___default()(ActivityHeader, [{
    key: "render",
    value: function render() {
      var _this$props = this.props,
          title = _this$props.title,
          className = _this$props.className,
          menu = _this$props.menu;
      var cardClassName = classnames__WEBPACK_IMPORTED_MODULE_6___default()('woocommerce-layout__activity-panel-header', className);
      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])("div", {
        className: cardClassName
      }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_9__["H"], {
        className: "woocommerce-layout__activity-panel-header-title"
      }, title), menu && Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])("div", {
        className: "woocommerce-layout__activity-panel-header-menu"
      }, menu));
    }
  }]);

  return ActivityHeader;
}(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["Component"]);

ActivityHeader.propTypes = {
  className: prop_types__WEBPACK_IMPORTED_MODULE_7___default.a.string,
  title: prop_types__WEBPACK_IMPORTED_MODULE_7___default.a.string.isRequired,
  menu: prop_types__WEBPACK_IMPORTED_MODULE_7___default.a.shape({
    type: prop_types__WEBPACK_IMPORTED_MODULE_7___default.a.oneOf([_woocommerce_components__WEBPACK_IMPORTED_MODULE_9__["EllipsisMenu"]])
  })
};
/* harmony default export */ __webpack_exports__["a"] = (ActivityHeader);

/***/ }),

/***/ 756:
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),

/***/ 757:
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),

/***/ 889:
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),

/***/ 911:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
// ESM COMPAT FLAG
__webpack_require__.r(__webpack_exports__);

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

// EXTERNAL MODULE: ./node_modules/@wordpress/components/build-module/button/index.js
var build_module_button = __webpack_require__(88);

// EXTERNAL MODULE: ./node_modules/@wordpress/compose/build-module/higher-order/compose.js
var compose = __webpack_require__(256);

// EXTERNAL MODULE: ./node_modules/gridicons/dist/index.js
var dist = __webpack_require__(66);
var dist_default = /*#__PURE__*/__webpack_require__.n(dist);

// EXTERNAL MODULE: ./node_modules/prop-types/index.js
var prop_types = __webpack_require__(1);
var prop_types_default = /*#__PURE__*/__webpack_require__.n(prop_types);

// EXTERNAL MODULE: ./node_modules/interpolate-components/lib/index.js
var lib = __webpack_require__(35);
var lib_default = /*#__PURE__*/__webpack_require__.n(lib);

// EXTERNAL MODULE: external "lodash"
var external_lodash_ = __webpack_require__(2);

// EXTERNAL MODULE: external {"this":["wc","components"]}
var external_this_wc_components_ = __webpack_require__(63);

// EXTERNAL MODULE: external {"this":["wc","navigation"]}
var external_this_wc_navigation_ = __webpack_require__(22);

// EXTERNAL MODULE: ./client/settings/index.js
var settings = __webpack_require__(26);

// EXTERNAL MODULE: external {"this":["wc","data"]}
var external_this_wc_data_ = __webpack_require__(51);

// EXTERNAL MODULE: ./client/header/activity-panel/activity-card/index.js + 1 modules
var activity_card = __webpack_require__(748);

// EXTERNAL MODULE: ./client/header/activity-panel/activity-header/index.js
var activity_header = __webpack_require__(754);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/extends.js
var helpers_extends = __webpack_require__(105);
var extends_default = /*#__PURE__*/__webpack_require__.n(helpers_extends);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/objectWithoutProperties.js
var objectWithoutProperties = __webpack_require__(121);
var objectWithoutProperties_default = /*#__PURE__*/__webpack_require__.n(objectWithoutProperties);

// EXTERNAL MODULE: ./node_modules/classnames/index.js
var classnames = __webpack_require__(10);
var classnames_default = /*#__PURE__*/__webpack_require__.n(classnames);

// EXTERNAL MODULE: ./client/header/activity-panel/activity-outbound-link/style.scss
var style = __webpack_require__(889);

// CONCATENATED MODULE: ./client/header/activity-panel/activity-outbound-link/index.js




/**
 * External dependencies
 */



/**
 * Internal dependencies
 */




var activity_outbound_link_ActivityOutboundLink = function ActivityOutboundLink(props) {
  var href = props.href,
      type = props.type,
      className = props.className,
      children = props.children,
      restOfProps = objectWithoutProperties_default()(props, ["href", "type", "className", "children"]);

  var classes = classnames_default()('woocommerce-layout__activity-panel-outbound-link', className);
  return Object(external_this_wp_element_["createElement"])(external_this_wc_components_["Link"], extends_default()({
    href: href,
    type: type,
    className: classes
  }, restOfProps), children, Object(external_this_wp_element_["createElement"])(dist_default.a, {
    icon: "arrow-right"
  }));
};

activity_outbound_link_ActivityOutboundLink.propTypes = {
  href: prop_types_default.a.string.isRequired,
  type: prop_types_default.a.oneOf(['wp-admin', 'wc-admin', 'external']).isRequired,
  className: prop_types_default.a.string
};
activity_outbound_link_ActivityOutboundLink.defaultProps = {
  type: 'wp-admin'
};
/* harmony default export */ var activity_outbound_link = (activity_outbound_link_ActivityOutboundLink);
// EXTERNAL MODULE: ./client/wc-api/constants.js
var constants = __webpack_require__(24);

// EXTERNAL MODULE: ./client/analytics/settings/config.js + 1 modules
var config = __webpack_require__(263);

// EXTERNAL MODULE: ./client/wc-api/with-select.js
var with_select = __webpack_require__(101);

// EXTERNAL MODULE: ./client/lib/currency-context.js
var currency_context = __webpack_require__(203);

// CONCATENATED MODULE: ./client/header/activity-panel/panels/orders.js







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









var orders_OrdersPanel = /*#__PURE__*/function (_Component) {
  inherits_default()(OrdersPanel, _Component);

  var _super = _createSuper(OrdersPanel);

  function OrdersPanel() {
    classCallCheck_default()(this, OrdersPanel);

    return _super.apply(this, arguments);
  }

  createClass_default()(OrdersPanel, [{
    key: "renderEmptyCard",
    value: function renderEmptyCard() {
      var hasNonActionableOrders = this.props.hasNonActionableOrders;

      if (hasNonActionableOrders) {
        return Object(external_this_wp_element_["createElement"])(activity_card["a" /* ActivityCard */], {
          className: "woocommerce-empty-activity-card",
          title: Object(external_this_wp_i18n_["__"])('You have no orders to fulfill', 'woocommerce'),
          icon: Object(external_this_wp_element_["createElement"])(dist_default.a, {
            icon: "checkmark",
            size: 48
          })
        }, Object(external_this_wp_i18n_["__"])("Good job, you've fulfilled all of your new orders!", 'woocommerce'));
      }

      return Object(external_this_wp_element_["createElement"])(activity_card["a" /* ActivityCard */], {
        className: "woocommerce-empty-activity-card",
        title: Object(external_this_wp_i18n_["__"])('You have no orders to fulfill', 'woocommerce'),
        icon: Object(external_this_wp_element_["createElement"])(dist_default.a, {
          icon: "time",
          size: 48
        }),
        actions: Object(external_this_wp_element_["createElement"])(build_module_button["a" /* default */], {
          href: "https://docs.woocommerce.com/document/managing-orders/",
          isDefault: true,
          target: "_blank"
        }, Object(external_this_wp_i18n_["__"])('Learn more', 'woocommerce'))
      }, Object(external_this_wp_i18n_["__"])("You're still waiting for your customers to make their first orders. " + 'While you wait why not learn how to manage orders?', 'woocommerce'));
    }
  }, {
    key: "renderOrders",
    value: function renderOrders() {
      var orders = this.props.orders;
      var Currency = this.context;

      if (orders.length === 0) {
        return this.renderEmptyCard();
      }

      var getCustomerString = function getCustomerString(order) {
        var extendedInfo = order.extended_info || {};

        var _ref = extendedInfo.customer || {},
            firstName = _ref.first_name,
            lastName = _ref.last_name;

        if (!firstName && !lastName) {
          return '';
        }

        var name = [firstName, lastName].join(' ');
        return Object(external_this_wp_i18n_["sprintf"])(Object(external_this_wp_i18n_["__"])(
        /* translators: describes who placed an order, e.g. Order #123 placed by John Doe */
        'placed by {{customerLink}}%(customerName)s{{/customerLink}}', 'woocommerce'), {
          customerName: name
        });
      };

      var orderCardTitle = function orderCardTitle(order) {
        var extendedInfo = order.extended_info,
            orderId = order.order_id,
            orderNumber = order.order_number;

        var _ref2 = extendedInfo || {},
            customer = _ref2.customer;

        var customerUrl = customer.customer_id ? Object(external_this_wc_navigation_["getNewPath"])({}, '/analytics/customers', {
          filter: 'single_customer',
          customers: customer.customer_id
        }) : null;
        return Object(external_this_wp_element_["createElement"])(external_this_wp_element_["Fragment"], null, lib_default()({
          mixedString: Object(external_this_wp_i18n_["sprintf"])(Object(external_this_wp_i18n_["__"])('Order {{orderLink}}#%(orderNumber)s{{/orderLink}} %(customerString)s {{destinationFlag/}}', 'woocommerce'), {
            orderNumber: orderNumber,
            customerString: getCustomerString(order)
          }),
          components: {
            orderLink: Object(external_this_wp_element_["createElement"])(external_this_wc_components_["Link"], {
              href: Object(settings["f" /* getAdminLink */])('post.php?action=edit&post=' + orderId),
              type: "wp-admin"
            }),
            destinationFlag: customer.country ? Object(external_this_wp_element_["createElement"])(external_this_wc_components_["Flag"], {
              code: customer.country,
              round: false
            }) : null,
            customerLink: customerUrl ? Object(external_this_wp_element_["createElement"])(external_this_wc_components_["Link"], {
              href: customerUrl,
              type: "wc-admin"
            }) : Object(external_this_wp_element_["createElement"])("span", null)
          }
        }));
      };

      var cards = [];
      orders.forEach(function (order) {
        var extendedInfo = order.extended_info || {};
        var productsCount = extendedInfo && extendedInfo.products ? extendedInfo.products.length : 0;
        var total = order.total_sales;
        cards.push(Object(external_this_wp_element_["createElement"])(activity_card["a" /* ActivityCard */], {
          key: order.order_id,
          className: "woocommerce-order-activity-card",
          title: orderCardTitle(order),
          date: order.date_created_gmt,
          subtitle: Object(external_this_wp_element_["createElement"])("div", null, Object(external_this_wp_element_["createElement"])("span", null, Object(external_this_wp_i18n_["sprintf"])(Object(external_this_wp_i18n_["_n"])('%d product', '%d products', productsCount, 'woocommerce'), productsCount)), Object(external_this_wp_element_["createElement"])("span", null, Currency.formatCurrency(total))),
          actions: Object(external_this_wp_element_["createElement"])(build_module_button["a" /* default */], {
            isDefault: true,
            href: Object(settings["f" /* getAdminLink */])('post.php?action=edit&post=' + order.order_id)
          }, Object(external_this_wp_i18n_["__"])('Begin fulfillment'))
        }, Object(external_this_wp_element_["createElement"])(external_this_wc_components_["OrderStatus"], {
          order: order,
          orderStatusMap: Object(settings["g" /* getSetting */])('orderStatuses', {})
        })));
      });
      return Object(external_this_wp_element_["createElement"])(external_this_wp_element_["Fragment"], null, cards, Object(external_this_wp_element_["createElement"])(activity_outbound_link, {
        href: 'edit.php?post_type=shop_order'
      }, Object(external_this_wp_i18n_["__"])('Manage all orders', 'woocommerce')));
    }
  }, {
    key: "render",
    value: function render() {
      var _this$props = this.props,
          orders = _this$props.orders,
          isRequesting = _this$props.isRequesting,
          isError = _this$props.isError,
          orderStatuses = _this$props.orderStatuses;

      if (isError) {
        if (!orderStatuses.length) {
          return Object(external_this_wp_element_["createElement"])(external_this_wc_components_["EmptyContent"], {
            title: Object(external_this_wp_i18n_["__"])("You currently don't have any actionable statuses. " + 'To display orders here, select orders that require further review in settings.', 'woocommerce'),
            actionLabel: Object(external_this_wp_i18n_["__"])('Settings', 'woocommerce'),
            actionURL: Object(settings["f" /* getAdminLink */])('admin.php?page=wc-admin&path=/analytics/settings')
          });
        }

        var _title = Object(external_this_wp_i18n_["__"])('There was an error getting your orders. Please try again.', 'woocommerce');

        var actionLabel = Object(external_this_wp_i18n_["__"])('Reload', 'woocommerce');

        var actionCallback = function actionCallback() {
          // @todo Add tracking for how often an error is displayed, and the reload action is clicked.
          window.location.reload();
        };

        return Object(external_this_wp_element_["createElement"])(external_this_wp_element_["Fragment"], null, Object(external_this_wp_element_["createElement"])(external_this_wc_components_["EmptyContent"], {
          title: _title,
          actionLabel: actionLabel,
          actionURL: null,
          actionCallback: actionCallback
        }));
      }

      var title = isRequesting || orders.length ? Object(external_this_wp_i18n_["__"])('Orders', 'woocommerce') : Object(external_this_wp_i18n_["__"])('No orders to fulfill', 'woocommerce');
      return Object(external_this_wp_element_["createElement"])(external_this_wp_element_["Fragment"], null, Object(external_this_wp_element_["createElement"])(activity_header["a" /* default */], {
        title: title
      }), Object(external_this_wp_element_["createElement"])(external_this_wc_components_["Section"], null, isRequesting ? Object(external_this_wp_element_["createElement"])(activity_card["b" /* ActivityCardPlaceholder */], {
        className: "woocommerce-order-activity-card",
        hasAction: true,
        hasDate: true,
        lines: 2
      }) : this.renderOrders()));
    }
  }]);

  return OrdersPanel;
}(external_this_wp_element_["Component"]);

orders_OrdersPanel.propTypes = {
  orders: prop_types_default.a.array.isRequired,
  isError: prop_types_default.a.bool,
  isRequesting: prop_types_default.a.bool
};
orders_OrdersPanel.defaultProps = {
  orders: [],
  isError: false,
  isRequesting: false
};
orders_OrdersPanel.contextType = currency_context["a" /* CurrencyContext */];
/* harmony default export */ var panels_orders = __webpack_exports__["default"] = (Object(compose["a" /* default */])(Object(with_select["a" /* default */])(function (select, props) {
  var hasActionableOrders = props.hasActionableOrders;

  var _select = select('wc-api'),
      getItems = _select.getItems,
      getItemsTotalCount = _select.getItemsTotalCount,
      getItemsError = _select.getItemsError,
      isGetItemsRequesting = _select.isGetItemsRequesting,
      getReportItems = _select.getReportItems,
      getReportItemsError = _select.getReportItemsError,
      isReportItemsRequesting = _select.isReportItemsRequesting;

  var _select2 = select(external_this_wc_data_["SETTINGS_STORE_NAME"]),
      getMutableSetting = _select2.getSetting;

  var _getMutableSetting = getMutableSetting('wc_admin', 'wcAdminSettings', {}),
      _getMutableSetting$wo = _getMutableSetting.woocommerce_actionable_order_statuses,
      orderStatuses = _getMutableSetting$wo === void 0 ? config["a" /* DEFAULT_ACTIONABLE_STATUSES */] : _getMutableSetting$wo;

  if (!orderStatuses.length) {
    return {
      orders: [],
      isError: true,
      isRequesting: false,
      orderStatuses: orderStatuses
    };
  }

  if (hasActionableOrders) {
    // Query the core Orders endpoint for the most up-to-date statuses.
    var actionableOrdersQuery = {
      page: 1,
      per_page: constants["d" /* QUERY_DEFAULTS */].pageSize,
      status: orderStatuses,
      _fields: ['id', 'date_created_gmt', 'status']
    };
    var actionableOrders = Array.from(getItems('orders', actionableOrdersQuery).values());
    var isRequestingActionable = isGetItemsRequesting('orders', actionableOrdersQuery);

    if (isRequestingActionable) {
      return {
        isError: Boolean(getItemsError('orders', actionableOrdersQuery)),
        isRequesting: isRequestingActionable,
        orderStatuses: orderStatuses
      };
    } // Retrieve the Order stats data from our reporting table.


    var ordersQuery = {
      page: 1,
      per_page: constants["d" /* QUERY_DEFAULTS */].pageSize,
      extended_info: true,
      order_includes: Object(external_lodash_["map"])(actionableOrders, 'id'),
      _fields: ['order_id', 'order_number', 'status', 'data_created_gmt', 'total_sales', 'extended_info.customer', 'extended_info.products']
    };
    var reportOrders = getReportItems('orders', ordersQuery).data;

    var _isError = Boolean(getReportItemsError('orders', ordersQuery));

    var _isRequesting = isReportItemsRequesting('orders', ordersQuery);

    var orders = [];

    if (reportOrders && reportOrders.length) {
      // Merge the core endpoint data with our reporting table.
      var actionableOrdersById = Object(external_lodash_["keyBy"])(actionableOrders, 'id');
      orders = reportOrders.map(function (order) {
        return Object(external_lodash_["merge"])({}, order, actionableOrdersById[order.order_id] || {});
      });
    }

    return {
      orders: orders,
      isError: _isError,
      isRequesting: _isRequesting,
      orderStatuses: orderStatuses
    };
  } // Get a count of all orders for messaging purposes.
  // @todo Add a property to settings api for this?


  var allOrdersQuery = {
    page: 1,
    per_page: 1,
    _fields: ['id']
  };
  getItems('orders', allOrdersQuery);
  var totalNonActionableOrders = getItemsTotalCount('orders', allOrdersQuery);
  var isError = Boolean(getItemsError('orders', allOrdersQuery));
  var isRequesting = isGetItemsRequesting('orders', allOrdersQuery);
  return {
    hasNonActionableOrders: totalNonActionableOrders > 0,
    isError: isError,
    isRequesting: isRequesting,
    orderStatuses: orderStatuses
  };
}))(orders_OrdersPanel));

/***/ })

}]);
