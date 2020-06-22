(window["webpackJsonp"] = window["webpackJsonp"] || []).push([[41],{

/***/ 119:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/esm/objectSpread.js
var objectSpread = __webpack_require__(27);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/esm/objectWithoutProperties.js
var objectWithoutProperties = __webpack_require__(16);

// EXTERNAL MODULE: external {"this":["wp","element"]}
var external_this_wp_element_ = __webpack_require__(0);

// CONCATENATED MODULE: ./node_modules/@wordpress/components/build-module/visually-hidden/utils.js



/**
 * Utility Functions
 */

/**
 * renderAsRenderProps is used to wrap a component and convert
 * the passed property "as" either a string or component, to the
 * rendered tag if a string, or component.
 *
 * See VisuallyHidden hidden for example.
 *
 * @param {string|WPComponent} as A tag or component to render.
 * @return {WPComponent} The rendered component.
 */
function renderAsRenderProps(_ref) {
  var _ref$as = _ref.as,
      Component = _ref$as === void 0 ? 'div' : _ref$as,
      props = Object(objectWithoutProperties["a" /* default */])(_ref, ["as"]);

  if (typeof props.children === 'function') {
    return props.children(props);
  }

  return Object(external_this_wp_element_["createElement"])(Component, props);
}


//# sourceMappingURL=utils.js.map
// CONCATENATED MODULE: ./node_modules/@wordpress/components/build-module/visually-hidden/index.js



/**
 * Internal dependencies
 */

/**
 * VisuallyHidden component to render text out non-visually
 * for use in devices such as a screen reader.
 */

function VisuallyHidden(_ref) {
  var _ref$as = _ref.as,
      as = _ref$as === void 0 ? 'div' : _ref$as,
      props = Object(objectWithoutProperties["a" /* default */])(_ref, ["as"]);

  return renderAsRenderProps(Object(objectSpread["a" /* default */])({
    as: as,
    className: 'components-visually-hidden'
  }, props));
}

/* harmony default export */ var visually_hidden = __webpack_exports__["a"] = (VisuallyHidden);
//# sourceMappingURL=index.js.map

/***/ }),

/***/ 171:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(10);
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(classnames__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _visually_hidden__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(119);


/**
 * External dependencies
 */

/**
 * Internal dependencies
 */



function BaseControl(_ref) {
  var id = _ref.id,
      label = _ref.label,
      hideLabelFromVision = _ref.hideLabelFromVision,
      help = _ref.help,
      className = _ref.className,
      children = _ref.children;
  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("div", {
    className: classnames__WEBPACK_IMPORTED_MODULE_1___default()('components-base-control', className)
  }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("div", {
    className: "components-base-control__field"
  }, label && id && (hideLabelFromVision ? Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_visually_hidden__WEBPACK_IMPORTED_MODULE_2__[/* default */ "a"], {
    as: "label",
    htmlFor: id
  }, label) : Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("label", {
    className: "components-base-control__label",
    htmlFor: id
  }, label)), label && !id && (hideLabelFromVision ? Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_visually_hidden__WEBPACK_IMPORTED_MODULE_2__[/* default */ "a"], {
    as: "label"
  }, label) : Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(BaseControl.VisualLabel, null, label)), children), !!help && Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("p", {
    id: id + '__help',
    className: "components-base-control__help"
  }, help));
}

BaseControl.VisualLabel = function (_ref2) {
  var className = _ref2.className,
      children = _ref2.children;
  className = classnames__WEBPACK_IMPORTED_MODULE_1___default()('components-base-control__label', className);
  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("span", {
    className: className
  }, children);
};

/* harmony default export */ __webpack_exports__["a"] = (BaseControl);
//# sourceMappingURL=index.js.map

/***/ }),

/***/ 173:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/esm/extends.js
var esm_extends = __webpack_require__(11);

// EXTERNAL MODULE: external {"this":["wp","element"]}
var external_this_wp_element_ = __webpack_require__(0);

// EXTERNAL MODULE: ./node_modules/@wordpress/compose/build-module/utils/create-higher-order-component/index.js
var create_higher_order_component = __webpack_require__(53);

// CONCATENATED MODULE: ./node_modules/@wordpress/compose/build-module/hooks/use-instance-id/index.js
/**
 * WordPress dependencies
 */

var instanceMap = new WeakMap();
/**
 * Creates a new id for a given object.
 *
 * @param {Object} object Object reference to create an id for.
 */

function createId(object) {
  var instances = instanceMap.get(object) || 0;
  instanceMap.set(object, instances + 1);
  return instances;
}
/**
 * Provides a unique instance ID.
 *
 * @param {Object} object Object reference to create an id for.
 */


function useInstanceId(object) {
  return Object(external_this_wp_element_["useMemo"])(function () {
    return createId(object);
  }, [object]);
}
//# sourceMappingURL=index.js.map
// CONCATENATED MODULE: ./node_modules/@wordpress/compose/build-module/higher-order/with-instance-id/index.js



/**
 * Internal dependencies
 */


/**
 * A Higher Order Component used to be provide a unique instance ID by
 * component.
 *
 * @param {WPComponent} WrappedComponent The wrapped component.
 *
 * @return {WPComponent} Component with an instanceId prop.
 */

/* harmony default export */ var with_instance_id = __webpack_exports__["a"] = (Object(create_higher_order_component["a" /* default */])(function (WrappedComponent) {
  return function (props) {
    var instanceId = useInstanceId(WrappedComponent);
    return Object(external_this_wp_element_["createElement"])(WrappedComponent, Object(esm_extends["a" /* default */])({}, props, {
      instanceId: instanceId
    }));
  };
}, 'withInstanceId'));
//# sourceMappingURL=index.js.map

/***/ }),

/***/ 709:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var _babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(11);
/* harmony import */ var _babel_runtime_helpers_esm_toConsumableArray__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(17);
/* harmony import */ var _babel_runtime_helpers_esm_objectWithoutProperties__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(16);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(2);
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(173);
/* harmony import */ var _base_control__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(171);





/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */


/**
 * Internal dependencies
 */



function SelectControl(_ref) {
  var help = _ref.help,
      instanceId = _ref.instanceId,
      label = _ref.label,
      _ref$multiple = _ref.multiple,
      multiple = _ref$multiple === void 0 ? false : _ref$multiple,
      onChange = _ref.onChange,
      _ref$options = _ref.options,
      options = _ref$options === void 0 ? [] : _ref$options,
      className = _ref.className,
      hideLabelFromVision = _ref.hideLabelFromVision,
      props = Object(_babel_runtime_helpers_esm_objectWithoutProperties__WEBPACK_IMPORTED_MODULE_2__[/* default */ "a"])(_ref, ["help", "instanceId", "label", "multiple", "onChange", "options", "className", "hideLabelFromVision"]);

  var id = "inspector-select-control-".concat(instanceId);

  var onChangeValue = function onChangeValue(event) {
    if (multiple) {
      var selectedOptions = Object(_babel_runtime_helpers_esm_toConsumableArray__WEBPACK_IMPORTED_MODULE_1__[/* default */ "a"])(event.target.options).filter(function (_ref2) {
        var selected = _ref2.selected;
        return selected;
      });

      var newValues = selectedOptions.map(function (_ref3) {
        var value = _ref3.value;
        return value;
      });
      onChange(newValues);
      return;
    }

    onChange(event.target.value);
  }; // Disable reason: A select with an onchange throws a warning

  /* eslint-disable jsx-a11y/no-onchange */


  return !Object(lodash__WEBPACK_IMPORTED_MODULE_4__["isEmpty"])(options) && Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_3__["createElement"])(_base_control__WEBPACK_IMPORTED_MODULE_6__[/* default */ "a"], {
    label: label,
    hideLabelFromVision: hideLabelFromVision,
    id: id,
    help: help,
    className: className
  }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_3__["createElement"])("select", Object(_babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_0__[/* default */ "a"])({
    id: id,
    className: "components-select-control__input",
    onChange: onChangeValue,
    "aria-describedby": !!help ? "".concat(id, "__help") : undefined,
    multiple: multiple
  }, props), options.map(function (option, index) {
    return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_3__["createElement"])("option", {
      key: "".concat(option.label, "-").concat(option.value, "-").concat(index),
      value: option.value,
      disabled: option.disabled
    }, option.label);
  })));
  /* eslint-enable jsx-a11y/no-onchange */
}

/* harmony default export */ __webpack_exports__["a"] = (Object(_wordpress_compose__WEBPACK_IMPORTED_MODULE_5__[/* default */ "a"])(SelectControl));
//# sourceMappingURL=index.js.map

/***/ }),

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

/***/ 779:
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),

/***/ 912:
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

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/assertThisInitialized.js
var assertThisInitialized = __webpack_require__(59);
var assertThisInitialized_default = /*#__PURE__*/__webpack_require__.n(assertThisInitialized);

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

// EXTERNAL MODULE: ./node_modules/@wordpress/components/build-module/select-control/index.js
var select_control = __webpack_require__(709);

// EXTERNAL MODULE: ./node_modules/@wordpress/components/build-module/dashicon/index.js
var dashicon = __webpack_require__(80);

// EXTERNAL MODULE: ./node_modules/@wordpress/components/build-module/icon-button/index.js
var icon_button = __webpack_require__(85);

// EXTERNAL MODULE: ./node_modules/classnames/index.js
var classnames = __webpack_require__(10);
var classnames_default = /*#__PURE__*/__webpack_require__.n(classnames);

// EXTERNAL MODULE: ./node_modules/interpolate-components/lib/index.js
var lib = __webpack_require__(35);
var lib_default = /*#__PURE__*/__webpack_require__.n(lib);

// EXTERNAL MODULE: ./node_modules/@wordpress/compose/build-module/higher-order/compose.js
var compose = __webpack_require__(256);

// EXTERNAL MODULE: external {"this":["wp","data"]}
var external_this_wp_data_ = __webpack_require__(19);

// EXTERNAL MODULE: external "moment"
var external_moment_ = __webpack_require__(12);
var external_moment_default = /*#__PURE__*/__webpack_require__.n(external_moment_);

// EXTERNAL MODULE: external {"this":["wc","components"]}
var external_this_wc_components_ = __webpack_require__(63);

// EXTERNAL MODULE: ./client/settings/index.js
var settings = __webpack_require__(26);

// EXTERNAL MODULE: ./client/wc-api/with-select.js
var with_select = __webpack_require__(101);

// EXTERNAL MODULE: ./client/wc-api/constants.js
var constants = __webpack_require__(24);

// EXTERNAL MODULE: ./client/lib/sanitize-html/index.js
var sanitize_html = __webpack_require__(747);

// EXTERNAL MODULE: ./node_modules/prop-types/index.js
var prop_types = __webpack_require__(1);
var prop_types_default = /*#__PURE__*/__webpack_require__.n(prop_types);

// CONCATENATED MODULE: ./client/layout/store-alerts/placeholder.js







function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function () { var Super = getPrototypeOf_default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = getPrototypeOf_default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return possibleConstructorReturn_default()(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/**
 * External dependencies
 */



var placeholder_StoreAlertsPlaceholder = /*#__PURE__*/function (_Component) {
  inherits_default()(StoreAlertsPlaceholder, _Component);

  var _super = _createSuper(StoreAlertsPlaceholder);

  function StoreAlertsPlaceholder() {
    classCallCheck_default()(this, StoreAlertsPlaceholder);

    return _super.apply(this, arguments);
  }

  createClass_default()(StoreAlertsPlaceholder, [{
    key: "render",
    value: function render() {
      var hasMultipleAlerts = this.props.hasMultipleAlerts;
      return Object(external_this_wp_element_["createElement"])("div", {
        className: "woocommerce-card woocommerce-store-alerts is-loading",
        "aria-hidden": true
      }, Object(external_this_wp_element_["createElement"])("div", {
        className: "woocommerce-card__header"
      }, Object(external_this_wp_element_["createElement"])("div", {
        className: "woocommerce-card__title woocommerce-card__header-item"
      }, Object(external_this_wp_element_["createElement"])("span", {
        className: "is-placeholder"
      })), hasMultipleAlerts && Object(external_this_wp_element_["createElement"])("div", {
        className: "woocommerce-card__action woocommerce-card__header-item"
      }, Object(external_this_wp_element_["createElement"])("span", {
        className: "is-placeholder"
      }))), Object(external_this_wp_element_["createElement"])("div", {
        className: "woocommerce-card__body"
      }, Object(external_this_wp_element_["createElement"])("div", {
        className: "woocommerce-store-alerts__message"
      }, Object(external_this_wp_element_["createElement"])("span", {
        className: "is-placeholder"
      }), Object(external_this_wp_element_["createElement"])("span", {
        className: "is-placeholder"
      })), Object(external_this_wp_element_["createElement"])("div", {
        className: "woocommerce-store-alerts__actions"
      }, Object(external_this_wp_element_["createElement"])("span", {
        className: "is-placeholder"
      }))));
    }
  }]);

  return StoreAlertsPlaceholder;
}(external_this_wp_element_["Component"]);

/* harmony default export */ var placeholder = (placeholder_StoreAlertsPlaceholder);
placeholder_StoreAlertsPlaceholder.propTypes = {
  /**
   * Whether multiple alerts exists.
   */
  hasMultipleAlerts: prop_types_default.a.bool
};
placeholder_StoreAlertsPlaceholder.defaultProps = {
  hasMultipleAlerts: false
};
// EXTERNAL MODULE: ./client/lib/tracks.js
var tracks = __webpack_require__(79);

// EXTERNAL MODULE: ./client/layout/store-alerts/style.scss
var style = __webpack_require__(779);

// CONCATENATED MODULE: ./client/layout/store-alerts/index.js








function store_alerts_createSuper(Derived) { var hasNativeReflectConstruct = store_alerts_isNativeReflectConstruct(); return function () { var Super = getPrototypeOf_default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = getPrototypeOf_default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return possibleConstructorReturn_default()(this, result); }; }

function store_alerts_isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/**
 * External dependencies
 */








/**
 * WooCommerce dependencies
 */



/**
 * Internal dependencies
 */








var store_alerts_StoreAlerts = /*#__PURE__*/function (_Component) {
  inherits_default()(StoreAlerts, _Component);

  var _super = store_alerts_createSuper(StoreAlerts);

  function StoreAlerts(props) {
    var _this;

    classCallCheck_default()(this, StoreAlerts);

    _this = _super.call(this, props);
    var alerts = _this.props.alerts;
    _this.state = {
      currentIndex: alerts ? 0 : null
    };
    _this.previousAlert = _this.previousAlert.bind(assertThisInitialized_default()(_this));
    _this.nextAlert = _this.nextAlert.bind(assertThisInitialized_default()(_this));
    return _this;
  }

  createClass_default()(StoreAlerts, [{
    key: "previousAlert",
    value: function previousAlert(event) {
      event.stopPropagation();
      var currentIndex = this.state.currentIndex;

      if (currentIndex > 0) {
        this.setState({
          currentIndex: currentIndex - 1
        });
      }
    }
  }, {
    key: "nextAlert",
    value: function nextAlert(event) {
      event.stopPropagation();
      var alerts = this.props.alerts;
      var currentIndex = this.state.currentIndex;

      if (currentIndex < alerts.length - 1) {
        this.setState({
          currentIndex: currentIndex + 1
        });
      }
    }
  }, {
    key: "renderActions",
    value: function renderActions(alert) {
      var _this$props = this.props,
          triggerNoteAction = _this$props.triggerNoteAction,
          updateNote = _this$props.updateNote;
      var actions = alert.actions.map(function (action) {
        return Object(external_this_wp_element_["createElement"])(build_module_button["a" /* default */], {
          key: action.name,
          isDefault: true,
          isPrimary: action.primary,
          href: action.url || undefined,
          onClick: function onClick() {
            return triggerNoteAction(alert.id, action.id);
          }
        }, action.label);
      }); // TODO: should "next X" be the start, or exactly 1X from the current date?

      var snoozeOptions = [{
        value: external_moment_default()().add(4, 'hours').unix().toString(),
        label: Object(external_this_wp_i18n_["__"])('Later Today', 'woocommerce')
      }, {
        value: external_moment_default()().add(1, 'day').hour(9).minute(0).second(0).millisecond(0).unix().toString(),
        label: Object(external_this_wp_i18n_["__"])('Tomorrow', 'woocommerce')
      }, {
        value: external_moment_default()().add(1, 'week').hour(9).minute(0).second(0).millisecond(0).unix().toString(),
        label: Object(external_this_wp_i18n_["__"])('Next Week', 'woocommerce')
      }, {
        value: external_moment_default()().add(1, 'month').hour(9).minute(0).second(0).millisecond(0).unix().toString(),
        label: Object(external_this_wp_i18n_["__"])('Next Month', 'woocommerce')
      }];

      var setReminderDate = function setReminderDate(snoozeOption) {
        updateNote(alert.id, {
          status: 'snoozed',
          date_reminder: snoozeOption.value
        });
        var eventProps = {
          alert_name: alert.name,
          alert_title: alert.title,
          snooze_duration: snoozeOption.value,
          snooze_label: snoozeOption.label
        };
        Object(tracks["b" /* recordEvent */])('store_alert_snooze', eventProps);
      };

      var snooze = alert.is_snoozable && Object(external_this_wp_element_["createElement"])(select_control["a" /* default */], {
        className: "woocommerce-store-alerts__snooze",
        options: [{
          label: Object(external_this_wp_i18n_["__"])('Remind Me Later', 'woocommerce'),
          value: '0'
        }].concat(snoozeOptions),
        onChange: function onChange(value) {
          if (value === '0') {
            return;
          }

          var reminderOption = snoozeOptions.find(function (option) {
            return option.value === value;
          });
          var reminderDate = {
            value: value,
            label: reminderOption && reminderOption.label
          };
          setReminderDate(reminderDate);
        }
      });

      if (actions || snooze) {
        return Object(external_this_wp_element_["createElement"])("div", {
          className: "woocommerce-store-alerts__actions"
        }, actions, snooze);
      }
    }
  }, {
    key: "render",
    value: function render() {
      var alerts = this.props.alerts || [];
      var preloadAlertCount = Object(settings["g" /* getSetting */])('alertCount', 0, function (count) {
        return parseInt(count, 10);
      });

      if (preloadAlertCount > 0 && this.props.isLoading) {
        return Object(external_this_wp_element_["createElement"])(placeholder, {
          hasMultipleAlerts: preloadAlertCount > 1
        });
      } else if (alerts.length === 0) {
        return null;
      }

      var currentIndex = this.state.currentIndex;
      var numberOfAlerts = alerts.length;
      var alert = alerts[currentIndex];
      var type = alert.type;
      var className = classnames_default()('woocommerce-store-alerts', 'woocommerce-analytics__card', {
        'is-alert-error': type === 'error',
        'is-alert-update': type === 'update'
      });
      return Object(external_this_wp_element_["createElement"])(external_this_wc_components_["Card"], {
        title: [alert.icon && Object(external_this_wp_element_["createElement"])(dashicon["a" /* default */], {
          key: "icon",
          icon: alert.icon
        }), Object(external_this_wp_element_["createElement"])(external_this_wp_element_["Fragment"], {
          key: "title"
        }, alert.title)],
        className: className,
        action: numberOfAlerts > 1 && Object(external_this_wp_element_["createElement"])("div", {
          className: "woocommerce-store-alerts__pagination"
        }, Object(external_this_wp_element_["createElement"])(icon_button["a" /* default */], {
          icon: "arrow-left-alt2",
          onClick: this.previousAlert,
          disabled: currentIndex === 0,
          label: Object(external_this_wp_i18n_["__"])('Previous Alert', 'woocommerce')
        }), Object(external_this_wp_element_["createElement"])("span", {
          className: "woocommerce-store-alerts__pagination-label",
          role: "status",
          "aria-live": "polite"
        }, lib_default()({
          mixedString: Object(external_this_wp_i18n_["__"])('{{current /}} of {{total /}}', 'woocommerce'),
          components: {
            current: Object(external_this_wp_element_["createElement"])(external_this_wp_element_["Fragment"], null, currentIndex + 1),
            total: Object(external_this_wp_element_["createElement"])(external_this_wp_element_["Fragment"], null, numberOfAlerts)
          }
        })), Object(external_this_wp_element_["createElement"])(icon_button["a" /* default */], {
          icon: "arrow-right-alt2",
          onClick: this.nextAlert,
          disabled: numberOfAlerts - 1 === currentIndex,
          label: Object(external_this_wp_i18n_["__"])('Next Alert', 'woocommerce')
        }))
      }, Object(external_this_wp_element_["createElement"])("div", {
        className: "woocommerce-store-alerts__message",
        dangerouslySetInnerHTML: Object(sanitize_html["a" /* default */])(alert.content)
      }), this.renderActions(alert));
    }
  }]);

  return StoreAlerts;
}(external_this_wp_element_["Component"]);

/* harmony default export */ var store_alerts = __webpack_exports__["default"] = (Object(compose["a" /* default */])(Object(with_select["a" /* default */])(function (select) {
  var _select = select('wc-api'),
      getNotes = _select.getNotes,
      isGetNotesRequesting = _select.isGetNotesRequesting;

  var alertsQuery = {
    page: 1,
    per_page: constants["d" /* QUERY_DEFAULTS */].pageSize,
    type: 'error,update',
    status: 'unactioned'
  }; // Filter out notes that may have been marked actioned or not delayed after the initial request

  var filterNotes = function filterNotes(note) {
    return note.status === 'unactioned';
  };

  var alerts = getNotes(alertsQuery).filter(filterNotes);
  var isLoading = isGetNotesRequesting(alertsQuery);
  return {
    alerts: alerts,
    isLoading: isLoading
  };
}), Object(external_this_wp_data_["withDispatch"])(function (dispatch) {
  var _dispatch = dispatch('wc-api'),
      triggerNoteAction = _dispatch.triggerNoteAction,
      updateNote = _dispatch.updateNote;

  return {
    triggerNoteAction: triggerNoteAction,
    updateNote: updateNote
  };
}))(store_alerts_StoreAlerts));

/***/ })

}]);
