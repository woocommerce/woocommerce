(window["webpackJsonp"] = window["webpackJsonp"] || []).push([["activity-panels-orders"],{

/***/ "./client/header/activity-panel/activity-outbound-link/index.js":
/*!**********************************************************************!*\
  !*** ./client/header/activity-panel/activity-outbound-link/index.js ***!
  \**********************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/extends */ "./node_modules/@babel/runtime/helpers/extends.js");
/* harmony import */ var _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _babel_runtime_helpers_objectWithoutProperties__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/objectWithoutProperties */ "./node_modules/@babel/runtime/helpers/objectWithoutProperties.js");
/* harmony import */ var _babel_runtime_helpers_objectWithoutProperties__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_objectWithoutProperties__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! prop-types */ "./node_modules/prop-types/index.js");
/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(prop_types__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! classnames */ "./node_modules/classnames/index.js");
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(classnames__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var gridicons__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! gridicons */ "./node_modules/gridicons/dist/index.js");
/* harmony import */ var gridicons__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(gridicons__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _style_scss__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./style.scss */ "./client/header/activity-panel/activity-outbound-link/style.scss");
/* harmony import */ var _style_scss__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(_style_scss__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @woocommerce/components */ "@woocommerce/components");
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_components__WEBPACK_IMPORTED_MODULE_7__);




/**
 * External dependencies
 */



/**
 * Internal dependencies
 */




var ActivityOutboundLink = function ActivityOutboundLink(props) {
  var href = props.href,
      type = props.type,
      className = props.className,
      children = props.children,
      restOfProps = _babel_runtime_helpers_objectWithoutProperties__WEBPACK_IMPORTED_MODULE_1___default()(props, ["href", "type", "className", "children"]);

  var classes = classnames__WEBPACK_IMPORTED_MODULE_4___default()('woocommerce-layout__activity-panel-outbound-link', className);
  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_7__["Link"], _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0___default()({
    href: href,
    type: type,
    className: classes
  }, restOfProps), children, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__["createElement"])(gridicons__WEBPACK_IMPORTED_MODULE_5___default.a, {
    icon: "arrow-right"
  }));
};

ActivityOutboundLink.propTypes = {
  href: prop_types__WEBPACK_IMPORTED_MODULE_3___default.a.string.isRequired,
  type: prop_types__WEBPACK_IMPORTED_MODULE_3___default.a.oneOf(['wp-admin', 'wc-admin', 'external']).isRequired,
  className: prop_types__WEBPACK_IMPORTED_MODULE_3___default.a.string
};
ActivityOutboundLink.defaultProps = {
  type: 'wp-admin'
};
/* harmony default export */ __webpack_exports__["default"] = (ActivityOutboundLink);

/***/ }),

/***/ "./client/header/activity-panel/activity-outbound-link/style.scss":
/*!************************************************************************!*\
  !*** ./client/header/activity-panel/activity-outbound-link/style.scss ***!
  \************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),

/***/ "./client/header/activity-panel/panels/orders.js":
/*!*******************************************************!*\
  !*** ./client/header/activity-panel/panels/orders.js ***!
  \*******************************************************/
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
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @wordpress/components */ "./node_modules/@wordpress/components/build-module/index.js");
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! @wordpress/compose */ "./node_modules/@wordpress/compose/build-module/index.js");
/* harmony import */ var gridicons__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! gridicons */ "./node_modules/gridicons/dist/index.js");
/* harmony import */ var gridicons__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(gridicons__WEBPACK_IMPORTED_MODULE_9__);
/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! prop-types */ "./node_modules/prop-types/index.js");
/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_10___default = /*#__PURE__*/__webpack_require__.n(prop_types__WEBPACK_IMPORTED_MODULE_10__);
/* harmony import */ var interpolate_components__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! interpolate-components */ "./node_modules/interpolate-components/lib/index.js");
/* harmony import */ var interpolate_components__WEBPACK_IMPORTED_MODULE_11___default = /*#__PURE__*/__webpack_require__.n(interpolate_components__WEBPACK_IMPORTED_MODULE_11__);
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! lodash */ "lodash");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_12___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_12__);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! @woocommerce/components */ "@woocommerce/components");
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_13___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_components__WEBPACK_IMPORTED_MODULE_13__);
/* harmony import */ var _woocommerce_navigation__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! @woocommerce/navigation */ "@woocommerce/navigation");
/* harmony import */ var _woocommerce_navigation__WEBPACK_IMPORTED_MODULE_14___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_14__);
/* harmony import */ var _woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(/*! @woocommerce/wc-admin-settings */ "./client/settings/index.js");
/* harmony import */ var _woocommerce_data__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__(/*! @woocommerce/data */ "@woocommerce/data");
/* harmony import */ var _woocommerce_data__WEBPACK_IMPORTED_MODULE_16___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_data__WEBPACK_IMPORTED_MODULE_16__);
/* harmony import */ var _activity_card__WEBPACK_IMPORTED_MODULE_17__ = __webpack_require__(/*! ../activity-card */ "./client/header/activity-panel/activity-card/index.js");
/* harmony import */ var _activity_header__WEBPACK_IMPORTED_MODULE_18__ = __webpack_require__(/*! ../activity-header */ "./client/header/activity-panel/activity-header/index.js");
/* harmony import */ var _activity_outbound_link__WEBPACK_IMPORTED_MODULE_19__ = __webpack_require__(/*! ../activity-outbound-link */ "./client/header/activity-panel/activity-outbound-link/index.js");
/* harmony import */ var wc_api_constants__WEBPACK_IMPORTED_MODULE_20__ = __webpack_require__(/*! wc-api/constants */ "./client/wc-api/constants.js");
/* harmony import */ var analytics_settings_config__WEBPACK_IMPORTED_MODULE_21__ = __webpack_require__(/*! analytics/settings/config */ "./client/analytics/settings/config.js");
/* harmony import */ var wc_api_with_select__WEBPACK_IMPORTED_MODULE_22__ = __webpack_require__(/*! wc-api/with-select */ "./client/wc-api/with-select.js");
/* harmony import */ var lib_currency_context__WEBPACK_IMPORTED_MODULE_23__ = __webpack_require__(/*! lib/currency-context */ "./client/lib/currency-context.js");







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









var OrdersPanel = /*#__PURE__*/function (_Component) {
  _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_2___default()(OrdersPanel, _Component);

  var _super = _createSuper(OrdersPanel);

  function OrdersPanel() {
    _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0___default()(this, OrdersPanel);

    return _super.apply(this, arguments);
  }

  _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1___default()(OrdersPanel, [{
    key: "renderEmptyCard",
    value: function renderEmptyCard() {
      var hasNonActionableOrders = this.props.hasNonActionableOrders;

      if (hasNonActionableOrders) {
        return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(_activity_card__WEBPACK_IMPORTED_MODULE_17__["ActivityCard"], {
          className: "woocommerce-empty-activity-card",
          title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__["__"])('You have no orders to fulfill', 'woocommerce'),
          icon: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(gridicons__WEBPACK_IMPORTED_MODULE_9___default.a, {
            icon: "checkmark",
            size: 48
          })
        }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__["__"])("Good job, you've fulfilled all of your new orders!", 'woocommerce'));
      }

      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(_activity_card__WEBPACK_IMPORTED_MODULE_17__["ActivityCard"], {
        className: "woocommerce-empty-activity-card",
        title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__["__"])('You have no orders to fulfill', 'woocommerce'),
        icon: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(gridicons__WEBPACK_IMPORTED_MODULE_9___default.a, {
          icon: "time",
          size: 48
        }),
        actions: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_7__["Button"], {
          href: "https://docs.woocommerce.com/document/managing-orders/",
          isSecondary: true,
          target: "_blank"
        }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__["__"])('Learn more', 'woocommerce'))
      }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__["__"])("You're still waiting for your customers to make their first orders. " + 'While you wait why not learn how to manage orders?', 'woocommerce'));
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
        return Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__["sprintf"])(Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__["__"])(
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

        var customerUrl = customer.customer_id ? Object(_woocommerce_navigation__WEBPACK_IMPORTED_MODULE_14__["getNewPath"])({}, '/analytics/customers', {
          filter: 'single_customer',
          customers: customer.customer_id
        }) : null;
        return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["Fragment"], null, interpolate_components__WEBPACK_IMPORTED_MODULE_11___default()({
          mixedString: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__["sprintf"])(Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__["__"])('Order {{orderLink}}#%(orderNumber)s{{/orderLink}} %(customerString)s {{destinationFlag/}}', 'woocommerce'), {
            orderNumber: orderNumber,
            customerString: getCustomerString(order)
          }),
          components: {
            orderLink: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_13__["Link"], {
              href: Object(_woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_15__["getAdminLink"])('post.php?action=edit&post=' + orderId),
              type: "wp-admin"
            }),
            destinationFlag: customer.country ? Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_13__["Flag"], {
              code: customer.country,
              round: false
            }) : null,
            customerLink: customerUrl ? Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_13__["Link"], {
              href: customerUrl,
              type: "wc-admin"
            }) : Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])("span", null)
          }
        }));
      };

      var cards = [];
      orders.forEach(function (order) {
        var extendedInfo = order.extended_info || {};
        var productsCount = extendedInfo && extendedInfo.products ? extendedInfo.products.length : 0;
        var total = order.total_sales;
        cards.push(Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(_activity_card__WEBPACK_IMPORTED_MODULE_17__["ActivityCard"], {
          key: order.order_id,
          className: "woocommerce-order-activity-card",
          title: orderCardTitle(order),
          date: order.date_created_gmt,
          subtitle: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])("div", null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])("span", null, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__["sprintf"])(Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__["_n"])('%d product', '%d products', productsCount, 'woocommerce'), productsCount)), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])("span", null, Currency.formatCurrency(total))),
          actions: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_7__["Button"], {
            isSecondary: true,
            href: Object(_woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_15__["getAdminLink"])('post.php?action=edit&post=' + order.order_id)
          }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__["__"])('Begin fulfillment'))
        }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_13__["OrderStatus"], {
          order: order,
          orderStatusMap: Object(_woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_15__["getSetting"])('orderStatuses', {})
        })));
      });
      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["Fragment"], null, cards, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(_activity_outbound_link__WEBPACK_IMPORTED_MODULE_19__["default"], {
        href: 'edit.php?post_type=shop_order'
      }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__["__"])('Manage all orders', 'woocommerce')));
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
          return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_13__["EmptyContent"], {
            title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__["__"])("You currently don't have any actionable statuses. " + 'To display orders here, select orders that require further review in settings.', 'woocommerce'),
            actionLabel: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__["__"])('Settings', 'woocommerce'),
            actionURL: Object(_woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_15__["getAdminLink"])('admin.php?page=wc-admin&path=/analytics/settings')
          });
        }

        var _title = Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__["__"])('There was an error getting your orders. Please try again.', 'woocommerce');

        var actionLabel = Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__["__"])('Reload', 'woocommerce');

        var actionCallback = function actionCallback() {
          // @todo Add tracking for how often an error is displayed, and the reload action is clicked.
          window.location.reload();
        };

        return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["Fragment"], null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_13__["EmptyContent"], {
          title: _title,
          actionLabel: actionLabel,
          actionURL: null,
          actionCallback: actionCallback
        }));
      }

      var title = isRequesting || orders.length ? Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__["__"])('Orders', 'woocommerce') : Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__["__"])('No orders to fulfill', 'woocommerce');
      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["Fragment"], null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(_activity_header__WEBPACK_IMPORTED_MODULE_18__["default"], {
        title: title
      }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_13__["Section"], null, isRequesting ? Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(_activity_card__WEBPACK_IMPORTED_MODULE_17__["ActivityCardPlaceholder"], {
        className: "woocommerce-order-activity-card",
        hasAction: true,
        hasDate: true,
        lines: 2
      }) : this.renderOrders()));
    }
  }]);

  return OrdersPanel;
}(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["Component"]);

OrdersPanel.propTypes = {
  orders: prop_types__WEBPACK_IMPORTED_MODULE_10___default.a.array.isRequired,
  isError: prop_types__WEBPACK_IMPORTED_MODULE_10___default.a.bool,
  isRequesting: prop_types__WEBPACK_IMPORTED_MODULE_10___default.a.bool
};
OrdersPanel.defaultProps = {
  orders: [],
  isError: false,
  isRequesting: false
};
OrdersPanel.contextType = lib_currency_context__WEBPACK_IMPORTED_MODULE_23__["CurrencyContext"];
/* harmony default export */ __webpack_exports__["default"] = (Object(_wordpress_compose__WEBPACK_IMPORTED_MODULE_8__["compose"])(Object(wc_api_with_select__WEBPACK_IMPORTED_MODULE_22__["default"])(function (select, props) {
  var hasActionableOrders = props.hasActionableOrders;

  var _select = select('wc-api'),
      getItems = _select.getItems,
      getItemsTotalCount = _select.getItemsTotalCount,
      getItemsError = _select.getItemsError,
      isGetItemsRequesting = _select.isGetItemsRequesting,
      getReportItems = _select.getReportItems,
      getReportItemsError = _select.getReportItemsError,
      isReportItemsRequesting = _select.isReportItemsRequesting;

  var _select2 = select(_woocommerce_data__WEBPACK_IMPORTED_MODULE_16__["SETTINGS_STORE_NAME"]),
      getMutableSetting = _select2.getSetting;

  var _getMutableSetting = getMutableSetting('wc_admin', 'wcAdminSettings', {}),
      _getMutableSetting$wo = _getMutableSetting.woocommerce_actionable_order_statuses,
      orderStatuses = _getMutableSetting$wo === void 0 ? analytics_settings_config__WEBPACK_IMPORTED_MODULE_21__["DEFAULT_ACTIONABLE_STATUSES"] : _getMutableSetting$wo;

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
      per_page: wc_api_constants__WEBPACK_IMPORTED_MODULE_20__["QUERY_DEFAULTS"].pageSize,
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
      per_page: wc_api_constants__WEBPACK_IMPORTED_MODULE_20__["QUERY_DEFAULTS"].pageSize,
      extended_info: true,
      order_includes: Object(lodash__WEBPACK_IMPORTED_MODULE_12__["map"])(actionableOrders, 'id'),
      _fields: ['order_id', 'order_number', 'status', 'data_created_gmt', 'total_sales', 'extended_info.customer', 'extended_info.products']
    };
    var reportOrders = getReportItems('orders', ordersQuery).data;

    var _isError = Boolean(getReportItemsError('orders', ordersQuery));

    var _isRequesting = isReportItemsRequesting('orders', ordersQuery);

    var orders = [];

    if (reportOrders && reportOrders.length) {
      // Merge the core endpoint data with our reporting table.
      var actionableOrdersById = Object(lodash__WEBPACK_IMPORTED_MODULE_12__["keyBy"])(actionableOrders, 'id');
      orders = reportOrders.map(function (order) {
        return Object(lodash__WEBPACK_IMPORTED_MODULE_12__["merge"])({}, order, actionableOrdersById[order.order_id] || {});
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
}))(OrdersPanel));

/***/ })

}]);
//# sourceMappingURL=activity-panels-orders.eea7e317988205894dcf.min.js.map
