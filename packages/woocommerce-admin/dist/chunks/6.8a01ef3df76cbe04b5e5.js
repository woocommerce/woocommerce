(window["webpackJsonp"] = window["webpackJsonp"] || []).push([[6],{

/***/ 758:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* unused harmony export ALLOWED_TAGS */
/* unused harmony export ALLOWED_ATTR */
/* harmony import */ var dompurify__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(766);
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

/***/ 760:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";

// EXPORTS
__webpack_require__.d(__webpack_exports__, "a", function() { return /* binding */ activity_card_ActivityCard; });
__webpack_require__.d(__webpack_exports__, "b", function() { return /* reexport */ placeholder; });

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/classCallCheck.js
var classCallCheck = __webpack_require__(38);
var classCallCheck_default = /*#__PURE__*/__webpack_require__.n(classCallCheck);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/createClass.js
var createClass = __webpack_require__(37);
var createClass_default = /*#__PURE__*/__webpack_require__.n(createClass);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/inherits.js
var inherits = __webpack_require__(39);
var inherits_default = /*#__PURE__*/__webpack_require__.n(inherits);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/possibleConstructorReturn.js
var possibleConstructorReturn = __webpack_require__(42);
var possibleConstructorReturn_default = /*#__PURE__*/__webpack_require__.n(possibleConstructorReturn);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/getPrototypeOf.js
var getPrototypeOf = __webpack_require__(26);
var getPrototypeOf_default = /*#__PURE__*/__webpack_require__.n(getPrototypeOf);

// EXTERNAL MODULE: external {"this":["wp","element"]}
var external_this_wp_element_ = __webpack_require__(0);

// EXTERNAL MODULE: ./node_modules/classnames/index.js
var classnames = __webpack_require__(8);
var classnames_default = /*#__PURE__*/__webpack_require__.n(classnames);

// EXTERNAL MODULE: ./node_modules/gridicons/dist/index.js
var dist = __webpack_require__(90);
var dist_default = /*#__PURE__*/__webpack_require__.n(dist);

// EXTERNAL MODULE: external "moment"
var external_moment_ = __webpack_require__(16);
var external_moment_default = /*#__PURE__*/__webpack_require__.n(external_moment_);

// EXTERNAL MODULE: ./node_modules/prop-types/index.js
var prop_types = __webpack_require__(1);
var prop_types_default = /*#__PURE__*/__webpack_require__.n(prop_types);

// EXTERNAL MODULE: ./client/header/activity-panel/activity-card/style.scss
var style = __webpack_require__(762);

// EXTERNAL MODULE: external {"this":["wc","components"]}
var external_this_wc_components_ = __webpack_require__(53);

// EXTERNAL MODULE: external "lodash"
var external_lodash_ = __webpack_require__(2);

// CONCATENATED MODULE: ./client/header/activity-panel/activity-card/placeholder.js







function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = getPrototypeOf_default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = getPrototypeOf_default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return possibleConstructorReturn_default()(this, result); }; }

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







function activity_card_createSuper(Derived) { var hasNativeReflectConstruct = activity_card_isNativeReflectConstruct(); return function _createSuperInternal() { var Super = getPrototypeOf_default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = getPrototypeOf_default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return possibleConstructorReturn_default()(this, result); }; }

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
      }), icon && Object(external_this_wp_element_["createElement"])("span", {
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

/***/ 761:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(38);
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(37);
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(39);
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(42);
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(26);
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(8);
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(classnames__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(1);
/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(prop_types__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var _style_scss__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(763);
/* harmony import */ var _style_scss__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(_style_scss__WEBPACK_IMPORTED_MODULE_8__);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(53);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_components__WEBPACK_IMPORTED_MODULE_9__);







function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_4___default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_4___default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_3___default()(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/**
 * External dependencies
 */



/**
 * Internal dependencies
 */




var ActivityHeader = /*#__PURE__*/function (_Component) {
  _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_2___default()(ActivityHeader, _Component);

  var _super = _createSuper(ActivityHeader);

  function ActivityHeader() {
    _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0___default()(this, ActivityHeader);

    return _super.apply(this, arguments);
  }

  _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1___default()(ActivityHeader, [{
    key: "render",
    value: function render() {
      var _this$props = this.props,
          className = _this$props.className,
          menu = _this$props.menu,
          subtitle = _this$props.subtitle,
          title = _this$props.title,
          unreadMessages = _this$props.unreadMessages;
      var cardClassName = classnames__WEBPACK_IMPORTED_MODULE_6___default()({
        'woocommerce-layout__inbox-panel-header': subtitle,
        'woocommerce-layout__activity-panel-header': !subtitle
      }, className);
      var countUnread = unreadMessages ? unreadMessages : 0;
      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])("div", {
        className: cardClassName
      }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_9__["H"], {
        className: "woocommerce-layout__activity-panel-header-title"
      }, title, countUnread > 0 && Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])("span", null, unreadMessages)), subtitle && Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])("div", {
        className: "woocommerce-layout__activity-panel-header-subtitle"
      }, subtitle), menu && Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])("div", {
        className: "woocommerce-layout__activity-panel-header-menu"
      }, menu));
    }
  }]);

  return ActivityHeader;
}(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["Component"]);

ActivityHeader.propTypes = {
  className: prop_types__WEBPACK_IMPORTED_MODULE_7___default.a.string,
  unreadMessages: prop_types__WEBPACK_IMPORTED_MODULE_7___default.a.number,
  title: prop_types__WEBPACK_IMPORTED_MODULE_7___default.a.string.isRequired,
  subtitle: prop_types__WEBPACK_IMPORTED_MODULE_7___default.a.string,
  menu: prop_types__WEBPACK_IMPORTED_MODULE_7___default.a.shape({
    type: prop_types__WEBPACK_IMPORTED_MODULE_7___default.a.oneOf([_woocommerce_components__WEBPACK_IMPORTED_MODULE_9__["EllipsisMenu"]])
  })
};
/* harmony default export */ __webpack_exports__["a"] = (ActivityHeader);

/***/ }),

/***/ 762:
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),

/***/ 763:
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),

/***/ 776:
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),

/***/ 792:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
// ESM COMPAT FLAG
__webpack_require__.r(__webpack_exports__);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/objectWithoutProperties.js
var objectWithoutProperties = __webpack_require__(129);
var objectWithoutProperties_default = /*#__PURE__*/__webpack_require__.n(objectWithoutProperties);

// EXTERNAL MODULE: external {"this":["wp","element"]}
var external_this_wp_element_ = __webpack_require__(0);

// EXTERNAL MODULE: external {"this":["wp","i18n"]}
var external_this_wp_i18n_ = __webpack_require__(3);

// EXTERNAL MODULE: ./node_modules/@wordpress/compose/build-module/higher-order/compose.js
var compose = __webpack_require__(169);

// EXTERNAL MODULE: external {"this":["wc","data"]}
var external_this_wc_data_ = __webpack_require__(43);

// EXTERNAL MODULE: ./client/header/activity-panel/activity-card/index.js + 1 modules
var activity_card = __webpack_require__(760);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/classCallCheck.js
var classCallCheck = __webpack_require__(38);
var classCallCheck_default = /*#__PURE__*/__webpack_require__.n(classCallCheck);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/createClass.js
var createClass = __webpack_require__(37);
var createClass_default = /*#__PURE__*/__webpack_require__.n(createClass);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/inherits.js
var inherits = __webpack_require__(39);
var inherits_default = /*#__PURE__*/__webpack_require__.n(inherits);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/possibleConstructorReturn.js
var possibleConstructorReturn = __webpack_require__(42);
var possibleConstructorReturn_default = /*#__PURE__*/__webpack_require__.n(possibleConstructorReturn);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/getPrototypeOf.js
var getPrototypeOf = __webpack_require__(26);
var getPrototypeOf_default = /*#__PURE__*/__webpack_require__.n(getPrototypeOf);

// EXTERNAL MODULE: ./node_modules/prop-types/index.js
var prop_types = __webpack_require__(1);
var prop_types_default = /*#__PURE__*/__webpack_require__.n(prop_types);

// CONCATENATED MODULE: ./client/header/activity-panel/panels/inbox/placeholder.js







function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = getPrototypeOf_default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = getPrototypeOf_default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return possibleConstructorReturn_default()(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/**
 * External dependencies
 */



var placeholder_InboxNotePlaceholder = /*#__PURE__*/function (_Component) {
  inherits_default()(InboxNotePlaceholder, _Component);

  var _super = _createSuper(InboxNotePlaceholder);

  function InboxNotePlaceholder() {
    classCallCheck_default()(this, InboxNotePlaceholder);

    return _super.apply(this, arguments);
  }

  createClass_default()(InboxNotePlaceholder, [{
    key: "render",
    value: function render() {
      var className = this.props.className;
      return Object(external_this_wp_element_["createElement"])("div", {
        className: "woocommerce-inbox-message is-placeholder ".concat(className),
        "aria-hidden": true
      }, Object(external_this_wp_element_["createElement"])("div", {
        className: "woocommerce-inbox-message__image"
      }, Object(external_this_wp_element_["createElement"])("div", {
        className: "banner-block"
      })), Object(external_this_wp_element_["createElement"])("div", {
        className: "woocommerce-inbox-message__wrapper"
      }, Object(external_this_wp_element_["createElement"])("div", {
        className: "woocommerce-inbox-message__content"
      }, Object(external_this_wp_element_["createElement"])("div", {
        className: "woocommerce-inbox-message__date"
      }, Object(external_this_wp_element_["createElement"])("div", {
        className: "sixth-line"
      })), Object(external_this_wp_element_["createElement"])("div", {
        className: "woocommerce-inbox-message__title"
      }, Object(external_this_wp_element_["createElement"])("div", {
        className: "line"
      }), Object(external_this_wp_element_["createElement"])("div", {
        className: "line"
      })), Object(external_this_wp_element_["createElement"])("div", {
        className: "woocommerce-inbox-message__text"
      }, Object(external_this_wp_element_["createElement"])("div", {
        className: "line"
      }), Object(external_this_wp_element_["createElement"])("div", {
        className: "third-line"
      }))), Object(external_this_wp_element_["createElement"])("div", {
        className: "woocommerce-inbox-message__actions"
      }, Object(external_this_wp_element_["createElement"])("div", {
        className: "fifth-line"
      }), Object(external_this_wp_element_["createElement"])("div", {
        className: "fifth-line"
      }))));
    }
  }]);

  return InboxNotePlaceholder;
}(external_this_wp_element_["Component"]);

placeholder_InboxNotePlaceholder.propTypes = {
  className: prop_types_default.a.string
};
/* harmony default export */ var placeholder = (placeholder_InboxNotePlaceholder);
// EXTERNAL MODULE: ./client/header/activity-panel/activity-header/index.js
var activity_header = __webpack_require__(761);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/assertThisInitialized.js
var assertThisInitialized = __webpack_require__(62);
var assertThisInitialized_default = /*#__PURE__*/__webpack_require__.n(assertThisInitialized);

// EXTERNAL MODULE: ./node_modules/@wordpress/components/build-module/dropdown/index.js
var dropdown = __webpack_require__(721);

// EXTERNAL MODULE: ./node_modules/@wordpress/components/build-module/button/index.js
var build_module_button = __webpack_require__(67);

// EXTERNAL MODULE: ./node_modules/@wordpress/components/build-module/modal/index.js + 3 modules
var modal = __webpack_require__(722);

// EXTERNAL MODULE: ./node_modules/react-visibility-sensor/dist/visibility-sensor.js
var visibility_sensor = __webpack_require__(775);
var visibility_sensor_default = /*#__PURE__*/__webpack_require__.n(visibility_sensor);

// EXTERNAL MODULE: external "moment"
var external_moment_ = __webpack_require__(16);
var external_moment_default = /*#__PURE__*/__webpack_require__.n(external_moment_);

// EXTERNAL MODULE: external {"this":["wp","data"]}
var external_this_wp_data_ = __webpack_require__(18);

// EXTERNAL MODULE: ./client/settings/index.js
var settings = __webpack_require__(22);

// CONCATENATED MODULE: ./client/header/activity-panel/panels/inbox/action.js








function action_createSuper(Derived) { var hasNativeReflectConstruct = action_isNativeReflectConstruct(); return function _createSuperInternal() { var Super = getPrototypeOf_default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = getPrototypeOf_default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return possibleConstructorReturn_default()(this, result); }; }

function action_isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/**
 * External dependencies
 */





/**
 * WooCommerce dependencies
 */


/**
 * Internal dependencies
 */

var action_InboxNoteAction = /*#__PURE__*/function (_Component) {
  inherits_default()(InboxNoteAction, _Component);

  var _super = action_createSuper(InboxNoteAction);

  function InboxNoteAction(props) {
    var _this;

    classCallCheck_default()(this, InboxNoteAction);

    _this = _super.call(this, props);
    _this.state = {
      inAction: false
    };
    _this.handleActionClick = _this.handleActionClick.bind(assertThisInitialized_default()(_this));
    return _this;
  }

  createClass_default()(InboxNoteAction, [{
    key: "handleActionClick",
    value: function handleActionClick(event) {
      var _this$props = this.props,
          action = _this$props.action,
          actionCallback = _this$props.actionCallback,
          noteId = _this$props.noteId,
          triggerNoteAction = _this$props.triggerNoteAction,
          removeAllNotes = _this$props.removeAllNotes,
          removeNote = _this$props.removeNote;
      var href = event.target.href || '';
      var inAction = true;

      if (href.length && !href.startsWith(settings["a" /* ADMIN_URL */])) {
        event.preventDefault();
        inAction = false; // link buttons shouldn't be "busy".

        window.open(href, '_blank');
      }

      if (!action) {
        if (noteId) {
          removeNote(noteId);
        } else {
          removeAllNotes();
        }

        actionCallback(true);
      } else {
        this.setState({
          inAction: inAction
        }, function () {
          return triggerNoteAction(noteId, action.id);
        });
      }
    }
  }, {
    key: "render",
    value: function render() {
      var _this$props2 = this.props,
          action = _this$props2.action,
          dismiss = _this$props2.dismiss,
          label = _this$props2.label;
      var isPrimary = dismiss || action.primary;
      return Object(external_this_wp_element_["createElement"])(build_module_button["a" /* default */], {
        isPrimary: isPrimary,
        isSecondary: !isPrimary,
        isBusy: this.state.inAction,
        disabled: this.state.inAction,
        href: action ? action.url : undefined,
        onClick: this.handleActionClick
      }, dismiss ? label : action.label);
    }
  }]);

  return InboxNoteAction;
}(external_this_wp_element_["Component"]);

action_InboxNoteAction.propTypes = {
  noteId: prop_types_default.a.number,
  label: prop_types_default.a.string,
  dismiss: prop_types_default.a.bool,
  actionCallback: prop_types_default.a.func,
  action: prop_types_default.a.shape({
    id: prop_types_default.a.number.isRequired,
    url: prop_types_default.a.string,
    label: prop_types_default.a.string.isRequired,
    primary: prop_types_default.a.bool.isRequired
  })
};
/* harmony default export */ var inbox_action = (Object(compose["a" /* default */])(Object(external_this_wp_data_["withDispatch"])(function (dispatch) {
  var _dispatch = dispatch('wc-api'),
      removeAllNotes = _dispatch.removeAllNotes,
      removeNote = _dispatch.removeNote,
      triggerNoteAction = _dispatch.triggerNoteAction;

  return {
    removeAllNotes: removeAllNotes,
    removeNote: removeNote,
    triggerNoteAction: triggerNoteAction
  };
}))(action_InboxNoteAction));
// EXTERNAL MODULE: ./client/lib/sanitize-html/index.js
var sanitize_html = __webpack_require__(758);

// EXTERNAL MODULE: ./node_modules/classnames/index.js
var classnames = __webpack_require__(8);
var classnames_default = /*#__PURE__*/__webpack_require__.n(classnames);

// EXTERNAL MODULE: ./client/lib/tracks.js
var tracks = __webpack_require__(63);

// EXTERNAL MODULE: ./client/header/activity-panel/panels/inbox/style.scss
var style = __webpack_require__(776);

// EXTERNAL MODULE: external {"this":["wc","components"]}
var external_this_wc_components_ = __webpack_require__(53);

// EXTERNAL MODULE: ./client/utils/index.js
var utils = __webpack_require__(268);

// CONCATENATED MODULE: ./client/header/activity-panel/panels/inbox/card.js








function card_createSuper(Derived) { var hasNativeReflectConstruct = card_isNativeReflectConstruct(); return function _createSuperInternal() { var Super = getPrototypeOf_default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = getPrototypeOf_default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return possibleConstructorReturn_default()(this, result); }; }

function card_isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/**
 * External dependencies
 */






/**
 * Internal dependencies
 */









var card_InboxNoteCard = /*#__PURE__*/function (_Component) {
  inherits_default()(InboxNoteCard, _Component);

  var _super = card_createSuper(InboxNoteCard);

  function InboxNoteCard(props) {
    var _this;

    classCallCheck_default()(this, InboxNoteCard);

    _this = _super.call(this, props);
    _this.onVisible = _this.onVisible.bind(assertThisInitialized_default()(_this));
    _this.hasBeenSeen = false;
    _this.state = {
      isDismissModalOpen: false,
      dismissType: null
    };
    _this.openDismissModal = _this.openDismissModal.bind(assertThisInitialized_default()(_this));
    _this.closeDismissModal = _this.closeDismissModal.bind(assertThisInitialized_default()(_this));
    _this.bodyNotificationRef = Object(external_this_wp_element_["createRef"])();
    _this.screen = _this.getScreenName();
    return _this;
  }

  createClass_default()(InboxNoteCard, [{
    key: "componentDidMount",
    value: function componentDidMount() {
      var _this2 = this;

      if (this.bodyNotificationRef.current) {
        this.bodyNotificationRef.current.addEventListener('click', function (event) {
          return _this2.handleBodyClick(event, _this2.props);
        });
      }
    }
  }, {
    key: "componentWillUnmount",
    value: function componentWillUnmount() {
      var _this3 = this;

      if (this.bodyNotificationRef.current) {
        this.bodyNotificationRef.current.removeEventListener('click', function (event) {
          return _this3.handleBodyClick(event, _this3.props);
        });
      }
    }
  }, {
    key: "handleBodyClick",
    value: function handleBodyClick(event, props) {
      var innerLink = event.target.href;

      if (innerLink) {
        var note = props.note;
        Object(tracks["b" /* recordEvent */])('wcadmin_inbox_action_click', {
          note_name: note.name,
          note_title: note.title,
          note_content_inner_link: innerLink,
          screen: this.screen
        });
      }
    }
  }, {
    key: "getScreenName",
    value: function getScreenName() {
      var screenName = '';

      var _getUrlParams = Object(utils["a" /* getUrlParams */])(window.location.search),
          page = _getUrlParams.page,
          path = _getUrlParams.path,
          postType = _getUrlParams.post_type;

      if (page) {
        var currentPage = page === 'wc-admin' ? 'home_screen' : page;
        screenName = path ? path.replace(/\//g, '_').substring(1) : currentPage;
      } else if (postType) {
        screenName = postType;
      }

      return screenName;
    } // Trigger a view Tracks event when the note is seen.

  }, {
    key: "onVisible",
    value: function onVisible(isVisible) {
      if (isVisible && !this.hasBeenSeen) {
        var note = this.props.note;
        Object(tracks["b" /* recordEvent */])('inbox_note_view', {
          note_content: note.content,
          note_name: note.name,
          note_title: note.title,
          note_type: note.type,
          screen: this.screen
        });
        this.hasBeenSeen = true;
      }
    }
  }, {
    key: "openDismissModal",
    value: function openDismissModal(type, onToggle) {
      this.setState({
        isDismissModalOpen: true,
        dismissType: type
      });
      onToggle();
    }
  }, {
    key: "closeDismissModal",
    value: function closeDismissModal(noteNameDismissConfirmation) {
      var dismissType = this.state.dismissType;
      var note = this.props.note;
      var noteNameDismissAll = dismissType === 'all' ? true : false;
      Object(tracks["b" /* recordEvent */])('inbox_action_dismiss', {
        note_name: note.name,
        note_title: note.title,
        note_name_dismiss_all: noteNameDismissAll,
        note_name_dismiss_confirmation: noteNameDismissConfirmation || false,
        screen: this.screen
      });
      this.setState({
        isDismissModalOpen: false
      });
    }
  }, {
    key: "handleBlur",
    value: function handleBlur(event, onClose) {
      var dropdownClasses = ['woocommerce-admin-dismiss-notification', 'components-popover__content']; // This line is for IE compatibility.

      var relatedTarget = event.relatedTarget ? event.relatedTarget : document.activeElement;
      var isClickOutsideDropdown = relatedTarget ? dropdownClasses.some(function (className) {
        return relatedTarget.className.includes(className);
      }) : false;

      if (isClickOutsideDropdown) {
        event.preventDefault();
      } else {
        onClose();
      }
    }
  }, {
    key: "renderDismissButton",
    value: function renderDismissButton() {
      var _this4 = this;

      return Object(external_this_wp_element_["createElement"])(dropdown["a" /* default */], {
        contentClassName: "woocommerce-admin-dismiss-dropdown",
        position: "bottom right",
        renderToggle: function renderToggle(_ref) {
          var onClose = _ref.onClose,
              onToggle = _ref.onToggle;
          return Object(external_this_wp_element_["createElement"])(build_module_button["a" /* default */], {
            isTertiary: true,
            onClick: onToggle,
            onBlur: function onBlur(event) {
              return _this4.handleBlur(event, onClose);
            }
          }, Object(external_this_wp_i18n_["__"])('Dismiss', 'woocommerce'));
        },
        focusOnMount: false,
        popoverProps: {
          noArrow: true
        },
        renderContent: function renderContent(_ref2) {
          var onToggle = _ref2.onToggle;
          return Object(external_this_wp_element_["createElement"])("ul", null, Object(external_this_wp_element_["createElement"])("li", null, Object(external_this_wp_element_["createElement"])(build_module_button["a" /* default */], {
            className: "woocommerce-admin-dismiss-notification",
            onClick: function onClick() {
              return _this4.openDismissModal('this', onToggle);
            }
          }, Object(external_this_wp_i18n_["__"])('Dismiss this message', 'woocommerce'))), Object(external_this_wp_element_["createElement"])("li", null, Object(external_this_wp_element_["createElement"])(build_module_button["a" /* default */], {
            className: "woocommerce-admin-dismiss-notification",
            onClick: function onClick() {
              return _this4.openDismissModal('all', onToggle);
            }
          }, Object(external_this_wp_i18n_["__"])('Dismiss all messages', 'woocommerce'))));
        }
      });
    }
  }, {
    key: "getDismissConfirmationButton",
    value: function getDismissConfirmationButton() {
      var note = this.props.note;
      var dismissType = this.state.dismissType;
      return Object(external_this_wp_element_["createElement"])(inbox_action, {
        key: note.id,
        noteId: dismissType === 'all' ? null : note.id,
        label: Object(external_this_wp_i18n_["__"])("Yes, I'm sure", 'woocommerce'),
        actionCallback: this.closeDismissModal,
        dismiss: true
      });
    }
  }, {
    key: "renderDismissConfirmationModal",
    value: function renderDismissConfirmationModal() {
      var _this5 = this;

      return Object(external_this_wp_element_["createElement"])(modal["a" /* default */], {
        title: Object(external_this_wp_element_["createElement"])(external_this_wp_element_["Fragment"], null, Object(external_this_wp_i18n_["__"])('Are you sure?', 'woocommerce')),
        onRequestClose: function onRequestClose() {
          return _this5.closeDismissModal();
        },
        className: "woocommerce-inbox-dismiss-confirmation_modal"
      }, Object(external_this_wp_element_["createElement"])("div", {
        className: "woocommerce-inbox-dismiss-confirmation_wrapper"
      }, Object(external_this_wp_element_["createElement"])("p", null, Object(external_this_wp_i18n_["__"])('Dismissed messages cannot be viewed again', 'woocommerce')), Object(external_this_wp_element_["createElement"])("div", {
        className: "woocommerce-inbox-dismiss-confirmation_buttons"
      }, Object(external_this_wp_element_["createElement"])(build_module_button["a" /* default */], {
        isSecondary: true,
        onClick: function onClick() {
          return _this5.closeDismissModal();
        }
      }, Object(external_this_wp_i18n_["__"])('Cancel', 'woocommerce')), this.getDismissConfirmationButton())));
    }
  }, {
    key: "renderActions",
    value: function renderActions(note) {
      var noteActions = note.actions,
          noteId = note.id;

      if (!noteActions) {
        return;
      }

      return Object(external_this_wp_element_["createElement"])(external_this_wp_element_["Fragment"], null, noteActions.map(function (action, index) {
        return Object(external_this_wp_element_["createElement"])(inbox_action, {
          key: index,
          noteId: noteId,
          action: action
        });
      }));
    }
  }, {
    key: "render",
    value: function render() {
      var _this$props = this.props,
          lastRead = _this$props.lastRead,
          note = _this$props.note;
      var isDismissModalOpen = this.state.isDismissModalOpen;
      var content = note.content,
          dateCreated = note.date_created,
          dateCreatedGmt = note.date_created_gmt,
          image = note.image,
          isDeleted = note.is_deleted,
          layout = note.layout,
          title = note.title;

      if (isDeleted) {
        return null;
      }

      var unread = !lastRead || !dateCreatedGmt || new Date(dateCreatedGmt + 'Z').getTime() > lastRead;
      var date = dateCreated;
      var hasImage = layout !== 'plain' && layout !== '';
      var cardClassName = classnames_default()('woocommerce-inbox-message', layout, {
        'message-is-unread': unread
      });
      return Object(external_this_wp_element_["createElement"])(visibility_sensor_default.a, {
        onChange: this.onVisible
      }, Object(external_this_wp_element_["createElement"])("section", {
        className: cardClassName
      }, hasImage && Object(external_this_wp_element_["createElement"])("div", {
        className: "woocommerce-inbox-message__image"
      }, Object(external_this_wp_element_["createElement"])("img", {
        src: image,
        alt: ""
      })), Object(external_this_wp_element_["createElement"])("div", {
        className: "woocommerce-inbox-message__wrapper"
      }, Object(external_this_wp_element_["createElement"])("div", {
        className: "woocommerce-inbox-message__content"
      }, date && Object(external_this_wp_element_["createElement"])("span", {
        className: "woocommerce-inbox-message__date"
      }, external_moment_default.a.utc(date).fromNow()), Object(external_this_wp_element_["createElement"])(external_this_wc_components_["H"], {
        className: "woocommerce-inbox-message__title"
      }, title), Object(external_this_wp_element_["createElement"])(external_this_wc_components_["Section"], {
        className: "woocommerce-inbox-message__text"
      }, Object(external_this_wp_element_["createElement"])("span", {
        dangerouslySetInnerHTML: Object(sanitize_html["a" /* default */])(content),
        ref: this.bodyNotificationRef
      }))), Object(external_this_wp_element_["createElement"])("div", {
        className: "woocommerce-inbox-message__actions"
      }, this.renderActions(note), this.renderDismissButton())), isDismissModalOpen && this.renderDismissConfirmationModal()));
    }
  }]);

  return InboxNoteCard;
}(external_this_wp_element_["Component"]);

card_InboxNoteCard.propTypes = {
  note: prop_types_default.a.shape({
    id: prop_types_default.a.number,
    status: prop_types_default.a.string,
    title: prop_types_default.a.string,
    content: prop_types_default.a.string,
    date_created: prop_types_default.a.string,
    date_created_gmt: prop_types_default.a.string,
    actions: prop_types_default.a.arrayOf(prop_types_default.a.shape({
      id: prop_types_default.a.number.isRequired,
      url: prop_types_default.a.string,
      label: prop_types_default.a.string.isRequired,
      primary: prop_types_default.a.bool.isRequired
    })),
    layout: prop_types_default.a.string,
    image: prop_types_default.a.string,
    is_deleted: prop_types_default.a.bool
  }),
  lastRead: prop_types_default.a.number
};
/* harmony default export */ var card = (card_InboxNoteCard);
// EXTERNAL MODULE: ./client/wc-api/constants.js
var constants = __webpack_require__(33);

// EXTERNAL MODULE: ./client/wc-api/with-select.js
var with_select = __webpack_require__(101);

// EXTERNAL MODULE: external "lodash"
var external_lodash_ = __webpack_require__(2);

// CONCATENATED MODULE: ./client/header/activity-panel/panels/inbox/utils.js
/**
 * External dependencies
 */

/**
 * Get the count of the unread notes.
 *
 * @param {Array} notes - List of notes, contains read and unread notes.
 * @param {number} lastRead - The timestamp that the user read a note.
 * @return {number} - Unread notes count.
 */

function getUnreadNotesCount(notes, lastRead) {
  var unreadNotes = Object(external_lodash_["filter"])(notes, function (note) {
    var isDeleted = note.is_deleted,
        dateCreatedGmt = note.date_created_gmt;

    if (!isDeleted) {
      return !lastRead || !dateCreatedGmt || new Date(dateCreatedGmt + 'Z').getTime() > lastRead;
    }
  });
  return unreadNotes.length;
}
/**
 * Verifies if there are any valid notes in the list.
 *
 * @param {Array} notes - List of notes, contains read and unread notes.
 * @return {boolean} - Whether there are valid notes or not.
 */

function hasValidNotes(notes) {
  var validNotes = Object(external_lodash_["filter"])(notes, function (note) {
    var isDeleted = note.is_deleted;
    return !isDeleted;
  });
  return validNotes.length > 0;
}
// CONCATENATED MODULE: ./client/header/activity-panel/panels/inbox/index.js



/**
 * External dependencies
 */



/**
 * WooCommerce dependencies
 */


/**
 * Internal dependencies
 */










var inbox_renderEmptyCard = function renderEmptyCard() {
  return Object(external_this_wp_element_["createElement"])(activity_card["a" /* ActivityCard */], {
    className: "woocommerce-empty-activity-card",
    title: Object(external_this_wp_i18n_["__"])('Your inbox is empty', 'woocommerce'),
    icon: false
  }, Object(external_this_wp_i18n_["__"])('As things begin to happen in your store your inbox will start to fill up. ' + "You'll see things like achievements, new feature announcements, extension recommendations and more!", 'woocommerce'));
};

var inbox_renderNotes = function renderNotes(_ref) {
  var hasNotes = _ref.hasNotes,
      isDismissUndoRequesting = _ref.isDismissUndoRequesting,
      isDismissAllUndoRequesting = _ref.isDismissAllUndoRequesting,
      lastRead = _ref.lastRead,
      notes = _ref.notes;

  if (isDismissAllUndoRequesting) {
    return;
  }

  if (!hasNotes) {
    return inbox_renderEmptyCard();
  }

  var notesArray = Object.keys(notes).map(function (key) {
    return notes[key];
  });
  return notesArray.map(function (note) {
    if (isDismissUndoRequesting === note.id) {
      return Object(external_this_wp_element_["createElement"])(placeholder, {
        className: "banner message-is-unread",
        key: note.id
      });
    }

    return Object(external_this_wp_element_["createElement"])(card, {
      key: note.id,
      note: note,
      lastRead: lastRead
    });
  });
};

var inbox_InboxPanel = function InboxPanel(props) {
  var isError = props.isError,
      isPanelEmpty = props.isPanelEmpty,
      isRequesting = props.isRequesting,
      isUndoRequesting = props.isUndoRequesting,
      isDismissAllUndoRequesting = props.isDismissAllUndoRequesting,
      isDismissUndoRequesting = props.isDismissUndoRequesting,
      notes = props.notes;

  var _useUserPreferences = Object(external_this_wc_data_["useUserPreferences"])(),
      updateUserPreferences = _useUserPreferences.updateUserPreferences,
      userPrefs = objectWithoutProperties_default()(_useUserPreferences, ["updateUserPreferences"]);

  var lastRead = userPrefs.activity_panel_inbox_last_read;
  Object(external_this_wp_element_["useEffect"])(function () {
    var mountTime = Date.now();
    return function () {
      var userDataFields = {
        activity_panel_inbox_last_read: mountTime
      };
      updateUserPreferences(userDataFields);
    };
  }, []);

  if (isError) {
    var title = Object(external_this_wp_i18n_["__"])('There was an error getting your inbox. Please try again.', 'woocommerce');

    var actionLabel = Object(external_this_wp_i18n_["__"])('Reload', 'woocommerce');

    var actionCallback = function actionCallback() {
      // @todo Add tracking for how often an error is displayed, and the reload action is clicked.
      window.location.reload();
    };

    return Object(external_this_wp_element_["createElement"])(external_this_wp_element_["Fragment"], null, Object(external_this_wp_element_["createElement"])(external_this_wc_components_["EmptyContent"], {
      title: title,
      actionLabel: actionLabel,
      actionURL: null,
      actionCallback: actionCallback
    }));
  }

  var hasNotes = hasValidNotes(notes);
  var isActivityHeaderVisible = hasNotes || isRequesting || isUndoRequesting;

  if (isPanelEmpty) {
    isPanelEmpty(!hasNotes && !isActivityHeaderVisible);
  }

  return Object(external_this_wp_element_["createElement"])(external_this_wp_element_["Fragment"], null, isActivityHeaderVisible && Object(external_this_wp_element_["createElement"])(activity_header["a" /* default */], {
    title: Object(external_this_wp_i18n_["__"])('Inbox', 'woocommerce'),
    subtitle: Object(external_this_wp_i18n_["__"])('Insights and growth tips for your business', 'woocommerce'),
    unreadMessages: getUnreadNotesCount(notes, lastRead)
  }), Object(external_this_wp_element_["createElement"])("div", {
    className: "woocommerce-homepage-notes-wrapper"
  }, (isRequesting || isDismissAllUndoRequesting) && Object(external_this_wp_element_["createElement"])(external_this_wc_components_["Section"], null, Object(external_this_wp_element_["createElement"])(placeholder, {
    className: "banner message-is-unread"
  })), Object(external_this_wp_element_["createElement"])(external_this_wc_components_["Section"], null, !isRequesting && !isDismissAllUndoRequesting && inbox_renderNotes({
    hasNotes: hasNotes,
    isDismissUndoRequesting: isDismissUndoRequesting,
    isDismissAllUndoRequesting: isDismissAllUndoRequesting,
    lastRead: lastRead,
    notes: notes
  }))));
};

/* harmony default export */ var inbox = __webpack_exports__["default"] = (Object(compose["a" /* default */])(Object(with_select["a" /* default */])(function (select) {
  var _select = select('wc-api'),
      getNotes = _select.getNotes,
      getNotesError = _select.getNotesError,
      isGetNotesRequesting = _select.isGetNotesRequesting,
      getUndoDismissRequesting = _select.getUndoDismissRequesting;

  var inboxQuery = {
    page: 1,
    per_page: constants["d" /* QUERY_DEFAULTS */].pageSize,
    type: constants["d" /* QUERY_DEFAULTS */].noteTypes,
    orderby: 'date',
    order: 'desc',
    status: 'unactioned',
    _fields: ['id', 'name', 'title', 'content', 'type', 'status', 'actions', 'date_created', 'date_created_gmt', 'layout', 'image', 'is_deleted']
  };
  var notes = getNotes(inboxQuery);
  var isError = Boolean(getNotesError(inboxQuery));
  var isRequesting = isGetNotesRequesting(inboxQuery);

  var _getUndoDismissReques = getUndoDismissRequesting(),
      isUndoRequesting = _getUndoDismissReques.isUndoRequesting,
      isDismissUndoRequesting = _getUndoDismissReques.isDismissUndoRequesting,
      isDismissAllUndoRequesting = _getUndoDismissReques.isDismissAllUndoRequesting;

  return {
    notes: notes,
    isError: isError,
    isRequesting: isRequesting,
    isUndoRequesting: isUndoRequesting,
    isDismissUndoRequesting: isDismissUndoRequesting,
    isDismissAllUndoRequesting: isDismissAllUndoRequesting
  };
}))(inbox_InboxPanel));

/***/ }),

/***/ 925:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(38);
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(37);
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(39);
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(42);
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(26);
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(3);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(8);
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(classnames__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(67);
/* harmony import */ var gridicons__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(90);
/* harmony import */ var gridicons__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(gridicons__WEBPACK_IMPORTED_MODULE_9__);
/* harmony import */ var interpolate_components__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(36);
/* harmony import */ var interpolate_components__WEBPACK_IMPORTED_MODULE_10___default = /*#__PURE__*/__webpack_require__.n(interpolate_components__WEBPACK_IMPORTED_MODULE_10__);
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(2);
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_11___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_11__);
/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(1);
/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_12___default = /*#__PURE__*/__webpack_require__.n(prop_types__WEBPACK_IMPORTED_MODULE_12__);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(53);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_13___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_components__WEBPACK_IMPORTED_MODULE_13__);
/* harmony import */ var _woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(22);
/* harmony import */ var _activity_card__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(760);
/* harmony import */ var _activity_header__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__(761);
/* harmony import */ var wc_api_constants__WEBPACK_IMPORTED_MODULE_17__ = __webpack_require__(33);
/* harmony import */ var lib_sanitize_html__WEBPACK_IMPORTED_MODULE_18__ = __webpack_require__(758);
/* harmony import */ var wc_api_with_select__WEBPACK_IMPORTED_MODULE_19__ = __webpack_require__(101);
/* harmony import */ var lib_tracks__WEBPACK_IMPORTED_MODULE_20__ = __webpack_require__(63);







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








var ReviewsPanel = /*#__PURE__*/function (_Component) {
  _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_2___default()(ReviewsPanel, _Component);

  var _super = _createSuper(ReviewsPanel);

  function ReviewsPanel() {
    var _this;

    _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0___default()(this, ReviewsPanel);

    _this = _super.call(this);
    _this.mountTime = new Date().getTime();
    return _this;
  }

  _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1___default()(ReviewsPanel, [{
    key: "renderReview",
    value: function renderReview(review, props) {
      var lastRead = props.lastRead;
      var product = review && review._embedded && review._embedded.up && review._embedded.up[0] || null;

      if (Object(lodash__WEBPACK_IMPORTED_MODULE_11__["isNull"])(product)) {
        return null;
      }

      var title = interpolate_components__WEBPACK_IMPORTED_MODULE_10___default()({
        mixedString: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__["sprintf"])(Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__["__"])('{{productLink}}%s{{/productLink}} reviewed by {{authorLink}}%s{{/authorLink}}', 'woocommerce'), product.name, review.reviewer),
        components: {
          productLink: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_13__["Link"], {
            href: product.permalink,
            type: "external"
          }),
          authorLink: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_13__["Link"], {
            href: 'mailto:' + review.reviewer_email,
            type: "external"
          })
        }
      });
      var subtitle = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["Fragment"], null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_13__["ReviewRating"], {
        review: review
      }), review.verified && Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])("span", {
        className: "woocommerce-review-activity-card__verified"
      }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(gridicons__WEBPACK_IMPORTED_MODULE_9___default.a, {
        icon: "checkmark",
        size: 18
      }), Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__["__"])('Verified customer', 'woocommerce')));
      var productImage = Object(lodash__WEBPACK_IMPORTED_MODULE_11__["get"])(product, ['images', 0]) || Object(lodash__WEBPACK_IMPORTED_MODULE_11__["get"])(product, ['image']);
      var productImageClasses = classnames__WEBPACK_IMPORTED_MODULE_7___default()('woocommerce-review-activity-card__image-overlay__product', {
        'is-placeholder': !productImage || !productImage.src
      });
      var icon = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])("div", {
        className: "woocommerce-review-activity-card__image-overlay"
      }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_13__["Gravatar"], {
        user: review.reviewer_email,
        size: 24
      }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])("div", {
        className: productImageClasses
      }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_13__["ProductImage"], {
        product: product
      })));
      var manageReviewEvent = {
        date: review.date_created_gmt,
        status: review.status
      };
      var cardActions = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_8__[/* default */ "a"], {
        isSecondary: true,
        onClick: function onClick() {
          return Object(lib_tracks__WEBPACK_IMPORTED_MODULE_20__[/* recordEvent */ "b"])('review_manage_click', manageReviewEvent);
        },
        href: Object(_woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_14__[/* getAdminLink */ "f"])('comment.php?action=editcomment&c=' + review.id)
      }, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__["__"])('Manage', 'woocommerce'));
      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(_activity_card__WEBPACK_IMPORTED_MODULE_15__[/* ActivityCard */ "a"], {
        className: "woocommerce-review-activity-card",
        key: review.id,
        title: title,
        subtitle: subtitle,
        date: review.date_created_gmt,
        icon: icon,
        actions: cardActions,
        unread: review.status === 'hold' || !lastRead || !review.date_created_gmt || new Date(review.date_created_gmt + 'Z').getTime() > lastRead
      }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])("span", {
        dangerouslySetInnerHTML: Object(lib_sanitize_html__WEBPACK_IMPORTED_MODULE_18__[/* default */ "a"])(review.review)
      }));
    }
  }, {
    key: "renderEmptyMessage",
    value: function renderEmptyMessage() {
      var lastApprovedReviewTime = this.props.lastApprovedReviewTime;

      var title = Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__["__"])('You have no reviews to moderate', 'woocommerce');

      var buttonUrl = '';
      var buttonTarget = '';
      var buttonText = '';
      var content = '';

      if (lastApprovedReviewTime) {
        var now = new Date();
        var DAY = 24 * 60 * 60 * 1000;

        if ((now.getTime() - lastApprovedReviewTime) / DAY > 30) {
          buttonUrl = 'https://woocommerce.com/posts/reviews-woocommerce-best-practices/';
          buttonTarget = '_blank';
          buttonText = Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__["__"])('Learn more', 'woocommerce');
          content = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["Fragment"], null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])("p", null, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__["__"])("We noticed that it's been a while since your products had any reviews.", 'woocommerce')), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])("p", null, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__["__"])('Take some time to learn about best practices for collecting and using your reviews.', 'woocommerce')));
        } else {
          buttonUrl = Object(_woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_14__[/* getAdminLink */ "f"])('edit-comments.php?comment_type=review');
          buttonText = Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__["__"])('View all Reviews', 'woocommerce');
          content = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])("p", null, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__["__"])(
          /* eslint-disable max-len */
          "Awesome, you've moderated all of your product reviews. How about responding to some of those negative reviews?", 'woocommerce'
          /* eslint-enable */
          ));
        }
      } else {
        buttonUrl = 'https://woocommerce.com/posts/reviews-woocommerce-best-practices/';
        buttonTarget = '_blank';
        buttonText = Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__["__"])('Learn more', 'woocommerce');
        content = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["Fragment"], null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])("p", null, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__["__"])("Your customers haven't started reviewing your products.", 'woocommerce')), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])("p", null, Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__["__"])('Take some time to learn about best practices for collecting and using your reviews.', 'woocommerce')));
      }

      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(_activity_card__WEBPACK_IMPORTED_MODULE_15__[/* ActivityCard */ "a"], {
        className: "woocommerce-empty-activity-card",
        title: title,
        icon: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(gridicons__WEBPACK_IMPORTED_MODULE_9___default.a, {
          icon: "time",
          size: 48
        }),
        actions: Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(_wordpress_components__WEBPACK_IMPORTED_MODULE_8__[/* default */ "a"], {
          href: buttonUrl,
          target: buttonTarget,
          isSecondary: true
        }, buttonText)
      }, content);
    }
  }, {
    key: "render",
    value: function render() {
      var _this2 = this;

      var _this$props = this.props,
          isError = _this$props.isError,
          isRequesting = _this$props.isRequesting,
          reviews = _this$props.reviews;

      if (isError) {
        var _title = Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__["__"])('There was an error getting your reviews. Please try again.', 'woocommerce');

        var actionLabel = Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__["__"])('Reload', 'woocommerce');

        var actionCallback = function actionCallback() {
          window.location.reload();
        };

        return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["Fragment"], null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_13__["EmptyContent"], {
          title: _title,
          actionLabel: actionLabel,
          actionURL: null,
          actionCallback: actionCallback
        }));
      }

      var title = isRequesting || reviews.length ? Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__["__"])('Reviews', 'woocommerce') : Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__["__"])('No reviews to moderate', 'woocommerce');
      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["Fragment"], null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(_activity_header__WEBPACK_IMPORTED_MODULE_16__[/* default */ "a"], {
        title: title
      }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_13__["Section"], null, isRequesting ? Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(_activity_card__WEBPACK_IMPORTED_MODULE_15__[/* ActivityCardPlaceholder */ "b"], {
        className: "woocommerce-review-activity-card",
        hasAction: true,
        hasDate: true,
        lines: 2
      }) : Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["Fragment"], null, reviews.length ? reviews.map(function (review) {
        return _this2.renderReview(review, _this2.props);
      }) : this.renderEmptyMessage())));
    }
  }]);

  return ReviewsPanel;
}(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["Component"]);

ReviewsPanel.propTypes = {
  reviews: prop_types__WEBPACK_IMPORTED_MODULE_12___default.a.array.isRequired,
  isError: prop_types__WEBPACK_IMPORTED_MODULE_12___default.a.bool,
  isRequesting: prop_types__WEBPACK_IMPORTED_MODULE_12___default.a.bool
};
ReviewsPanel.defaultProps = {
  reviews: [],
  isError: false,
  isRequesting: false
};
/* harmony default export */ __webpack_exports__["default"] = (Object(wc_api_with_select__WEBPACK_IMPORTED_MODULE_19__[/* default */ "a"])(function (select, props) {
  var hasUnapprovedReviews = props.hasUnapprovedReviews;

  var _select = select('wc-api'),
      getReviews = _select.getReviews,
      getReviewsError = _select.getReviewsError,
      isGetReviewsRequesting = _select.isGetReviewsRequesting;

  var reviews = [];
  var isError = false;
  var isRequesting = false;
  var lastApprovedReviewTime = null;

  if (hasUnapprovedReviews) {
    var reviewsQuery = {
      page: 1,
      per_page: wc_api_constants__WEBPACK_IMPORTED_MODULE_17__[/* QUERY_DEFAULTS */ "d"].pageSize,
      status: 'hold',
      _embed: 1
    };
    reviews = getReviews(reviewsQuery);
    isError = Boolean(getReviewsError(reviewsQuery));
    isRequesting = isGetReviewsRequesting(reviewsQuery);
  } else {
    var approvedReviewsQuery = {
      page: 1,
      per_page: 1,
      status: 'approved',
      _embed: 1
    };
    var approvedReviews = getReviews(approvedReviewsQuery);

    if (approvedReviews.length) {
      var lastApprovedReview = approvedReviews[0];

      if (lastApprovedReview.date_created_gmt) {
        var creationDate = new Date(lastApprovedReview.date_created_gmt);
        lastApprovedReviewTime = creationDate.getTime();
      }
    }

    isError = Boolean(getReviewsError(approvedReviewsQuery));
    isRequesting = isGetReviewsRequesting(approvedReviewsQuery);
  }

  return {
    reviews: reviews,
    isError: isError,
    isRequesting: isRequesting,
    lastApprovedReviewTime: lastApprovedReviewTime
  };
})(ReviewsPanel));

/***/ })

}]);
