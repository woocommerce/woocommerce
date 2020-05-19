(window["webpackJsonp"] = window["webpackJsonp"] || []).push([[40],{

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

/***/ 717:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var _babel_runtime_helpers_esm_defineProperty__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(13);
/* harmony import */ var _babel_runtime_helpers_esm_classCallCheck__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(7);
/* harmony import */ var _babel_runtime_helpers_esm_createClass__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(6);
/* harmony import */ var _babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(8);
/* harmony import */ var _babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(4);
/* harmony import */ var _babel_runtime_helpers_esm_assertThisInitialized__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(5);
/* harmony import */ var _babel_runtime_helpers_esm_inherits__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(9);
/* harmony import */ var _babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(11);
/* harmony import */ var _babel_runtime_helpers_esm_objectWithoutProperties__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(16);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__);
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(10);
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_10___default = /*#__PURE__*/__webpack_require__.n(classnames__WEBPACK_IMPORTED_MODULE_10__);
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(2);
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_11___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_11__);
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(173);
/* harmony import */ var _navigable_container__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(424);
/* harmony import */ var _button__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(88);











/**
 * External dependencies
 */


/**
 * WordPress dependencies
 */



/**
 * Internal dependencies
 */




var TabButton = function TabButton(_ref) {
  var tabId = _ref.tabId,
      onClick = _ref.onClick,
      children = _ref.children,
      selected = _ref.selected,
      rest = Object(_babel_runtime_helpers_esm_objectWithoutProperties__WEBPACK_IMPORTED_MODULE_8__[/* default */ "a"])(_ref, ["tabId", "onClick", "children", "selected"]);

  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["createElement"])(_button__WEBPACK_IMPORTED_MODULE_14__[/* default */ "a"], Object(_babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_7__[/* default */ "a"])({
    role: "tab",
    tabIndex: selected ? null : -1,
    "aria-selected": selected,
    id: tabId,
    onClick: onClick
  }, rest), children);
};

var TabPanel =
/*#__PURE__*/
function (_Component) {
  Object(_babel_runtime_helpers_esm_inherits__WEBPACK_IMPORTED_MODULE_6__[/* default */ "a"])(TabPanel, _Component);

  function TabPanel() {
    var _this;

    Object(_babel_runtime_helpers_esm_classCallCheck__WEBPACK_IMPORTED_MODULE_1__[/* default */ "a"])(this, TabPanel);

    _this = Object(_babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_3__[/* default */ "a"])(this, Object(_babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_4__[/* default */ "a"])(TabPanel).apply(this, arguments));
    var _this$props = _this.props,
        tabs = _this$props.tabs,
        initialTabName = _this$props.initialTabName;
    _this.handleClick = _this.handleClick.bind(Object(_babel_runtime_helpers_esm_assertThisInitialized__WEBPACK_IMPORTED_MODULE_5__[/* default */ "a"])(_this));
    _this.onNavigate = _this.onNavigate.bind(Object(_babel_runtime_helpers_esm_assertThisInitialized__WEBPACK_IMPORTED_MODULE_5__[/* default */ "a"])(_this));
    _this.state = {
      selected: initialTabName || (tabs.length > 0 ? tabs[0].name : null)
    };
    return _this;
  }

  Object(_babel_runtime_helpers_esm_createClass__WEBPACK_IMPORTED_MODULE_2__[/* default */ "a"])(TabPanel, [{
    key: "handleClick",
    value: function handleClick(tabKey) {
      var _this$props$onSelect = this.props.onSelect,
          onSelect = _this$props$onSelect === void 0 ? lodash__WEBPACK_IMPORTED_MODULE_11__["noop"] : _this$props$onSelect;
      this.setState({
        selected: tabKey
      });
      onSelect(tabKey);
    }
  }, {
    key: "onNavigate",
    value: function onNavigate(childIndex, child) {
      child.click();
    }
  }, {
    key: "render",
    value: function render() {
      var _this2 = this;

      var selected = this.state.selected;
      var _this$props2 = this.props,
          _this$props2$activeCl = _this$props2.activeClass,
          activeClass = _this$props2$activeCl === void 0 ? 'is-active' : _this$props2$activeCl,
          className = _this$props2.className,
          instanceId = _this$props2.instanceId,
          _this$props2$orientat = _this$props2.orientation,
          orientation = _this$props2$orientat === void 0 ? 'horizontal' : _this$props2$orientat,
          tabs = _this$props2.tabs;
      var selectedTab = Object(lodash__WEBPACK_IMPORTED_MODULE_11__["find"])(tabs, {
        name: selected
      });
      var selectedId = instanceId + '-' + selectedTab.name;
      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["createElement"])("div", {
        className: className
      }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["createElement"])(_navigable_container__WEBPACK_IMPORTED_MODULE_13__[/* default */ "a"], {
        role: "tablist",
        orientation: orientation,
        onNavigate: this.onNavigate,
        className: "components-tab-panel__tabs"
      }, tabs.map(function (tab) {
        return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["createElement"])(TabButton, {
          className: classnames__WEBPACK_IMPORTED_MODULE_10___default()(tab.className, Object(_babel_runtime_helpers_esm_defineProperty__WEBPACK_IMPORTED_MODULE_0__[/* default */ "a"])({}, activeClass, tab.name === selected)),
          tabId: instanceId + '-' + tab.name,
          "aria-controls": instanceId + '-' + tab.name + '-view',
          selected: tab.name === selected,
          key: tab.name,
          onClick: Object(lodash__WEBPACK_IMPORTED_MODULE_11__["partial"])(_this2.handleClick, tab.name)
        }, tab.title);
      })), selectedTab && Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["createElement"])("div", {
        "aria-labelledby": selectedId,
        role: "tabpanel",
        id: selectedId + '-view',
        className: "components-tab-panel__tab-content",
        tabIndex: "0"
      }, this.props.children(selectedTab)));
    }
  }]);

  return TabPanel;
}(_wordpress_element__WEBPACK_IMPORTED_MODULE_9__["Component"]);

/* harmony default export */ __webpack_exports__["a"] = (Object(_wordpress_compose__WEBPACK_IMPORTED_MODULE_12__[/* default */ "a"])(TabPanel));
//# sourceMappingURL=index.js.map

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

/***/ 761:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var _babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(11);
/* harmony import */ var _babel_runtime_helpers_esm_objectWithoutProperties__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(16);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(173);
/* harmony import */ var _base_control__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(171);
/* harmony import */ var _dashicon__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(80);




/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */




function CheckboxControl(_ref) {
  var label = _ref.label,
      className = _ref.className,
      heading = _ref.heading,
      checked = _ref.checked,
      help = _ref.help,
      instanceId = _ref.instanceId,
      onChange = _ref.onChange,
      props = Object(_babel_runtime_helpers_esm_objectWithoutProperties__WEBPACK_IMPORTED_MODULE_1__[/* default */ "a"])(_ref, ["label", "className", "heading", "checked", "help", "instanceId", "onChange"]);

  var id = "inspector-checkbox-control-".concat(instanceId);

  var onChangeValue = function onChangeValue(event) {
    return onChange(event.target.checked);
  };

  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__["createElement"])(_base_control__WEBPACK_IMPORTED_MODULE_4__[/* default */ "a"], {
    label: heading,
    id: id,
    help: help,
    className: className
  }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__["createElement"])("span", {
    className: "components-checkbox-control__input-container"
  }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__["createElement"])("input", Object(_babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_0__[/* default */ "a"])({
    id: id,
    className: "components-checkbox-control__input",
    type: "checkbox",
    value: "1",
    onChange: onChangeValue,
    checked: checked,
    "aria-describedby": !!help ? id + '__help' : undefined
  }, props)), checked ? Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__["createElement"])(_dashicon__WEBPACK_IMPORTED_MODULE_5__[/* default */ "a"], {
    icon: "yes",
    className: "components-checkbox-control__checked",
    role: "presentation"
  }) : null), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__["createElement"])("label", {
    className: "components-checkbox-control__label",
    htmlFor: id
  }, label));
}

/* harmony default export */ __webpack_exports__["a"] = (Object(_wordpress_compose__WEBPACK_IMPORTED_MODULE_3__[/* default */ "a"])(CheckboxControl));
//# sourceMappingURL=index.js.map

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

/***/ 890:
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),

/***/ 891:
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),

/***/ 898:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
// ESM COMPAT FLAG
__webpack_require__.r(__webpack_exports__);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/defineProperty.js
var defineProperty = __webpack_require__(15);
var defineProperty_default = /*#__PURE__*/__webpack_require__.n(defineProperty);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/asyncToGenerator.js
var asyncToGenerator = __webpack_require__(46);
var asyncToGenerator_default = /*#__PURE__*/__webpack_require__.n(asyncToGenerator);

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

// EXTERNAL MODULE: external {"this":["wp","i18n"]}
var external_this_wp_i18n_ = __webpack_require__(3);

// EXTERNAL MODULE: external {"this":["wp","element"]}
var external_this_wp_element_ = __webpack_require__(0);

// EXTERNAL MODULE: ./node_modules/@wordpress/compose/build-module/higher-order/compose.js
var compose = __webpack_require__(256);

// EXTERNAL MODULE: external "lodash"
var external_lodash_ = __webpack_require__(2);

// EXTERNAL MODULE: external {"this":["wp","data"]}
var external_this_wp_data_ = __webpack_require__(19);

// EXTERNAL MODULE: external {"this":["wc","navigation"]}
var external_this_wc_navigation_ = __webpack_require__(22);

// EXTERNAL MODULE: external {"this":["wc","data"]}
var external_this_wc_data_ = __webpack_require__(51);

// EXTERNAL MODULE: ./node_modules/@wordpress/components/build-module/button/index.js
var build_module_button = __webpack_require__(88);

// EXTERNAL MODULE: external {"this":["wc","components"]}
var external_this_wc_components_ = __webpack_require__(63);

// EXTERNAL MODULE: ./client/settings/index.js
var client_settings = __webpack_require__(26);

// EXTERNAL MODULE: ./client/dashboard/components/connect/index.js
var connect = __webpack_require__(759);

// CONCATENATED MODULE: ./client/profile-wizard/steps/benefits/logo.js







function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function () { var Super = getPrototypeOf_default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = getPrototypeOf_default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return possibleConstructorReturn_default()(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/**
 * External dependencies
 */



var logo_Logo = /*#__PURE__*/function (_Component) {
  inherits_default()(Logo, _Component);

  var _super = _createSuper(Logo);

  function Logo() {
    classCallCheck_default()(this, Logo);

    return _super.apply(this, arguments);
  }

  createClass_default()(Logo, [{
    key: "render",
    value: function render() {
      return Object(external_this_wp_element_["createElement"])("svg", {
        width: "161",
        height: "46",
        viewBox: "0 0 161 46",
        fill: "none",
        xmlns: "http://www.w3.org/2000/svg",
        "aria-label": Object(external_this_wp_i18n_["__"])('WooCommerce + Jetpack', 'woocommerce'),
        className: "woocommerce-profile-wizard__benefits-logo"
      }, Object(external_this_wp_element_["createElement"])("path", {
        d: "M139.071 45.4503C150.906 45.4503 160.5 35.7679 160.5 23.824C160.5 11.8802 150.906 2.19775 139.071 2.19775C127.236 2.19775 117.642 11.8802 117.642 23.824C117.642 35.7679 127.236 45.4503 139.071 45.4503Z",
        fill: "#008710"
      }), Object(external_this_wp_element_["createElement"])("path", {
        d: "M140.134 20.1919V41.1578L150.849 20.1919H140.134Z",
        fill: "#F6F7F7"
      }), Object(external_this_wp_element_["createElement"])("path", {
        d: "M137.967 27.4144V6.48975L127.293 27.4144H137.967Z",
        fill: "#F6F7F7"
      }), Object(external_this_wp_element_["createElement"])("path", {
        d: "M95.7021 24.668H100.542V21.6445H95.7021V16.8633H92.7138V21.6445H87.8857V24.668H92.7138V29.4609H95.7021V24.668Z",
        fill: "#2C3338"
      }), Object(external_this_wp_element_["createElement"])("g", {
        clipPath: "url(#clip0)"
      }, Object(external_this_wp_element_["createElement"])("path", {
        fillRule: "evenodd",
        clipRule: "evenodd",
        d: "M7.52123 2.25732H66.5046C70.2374 2.25732 73.2591 5.27907 73.2591 9.01182V31.5268C73.2591 35.2596 70.2374 38.2813 66.5046 38.2813H45.3524L48.2556 45.3913L35.4872 38.2813H7.55086C3.81811 38.2813 0.796359 35.2596 0.796359 31.5268V9.01182C0.766734 5.3087 3.78848 2.25732 7.52123 2.25732Z",
        fill: "#7F54B3"
      }), Object(external_this_wp_element_["createElement"])("path", {
        d: "M4.41042 8.38982C4.82517 7.82695 5.4473 7.5307 6.2768 7.47145C7.78767 7.35295 8.6468 8.06395 8.85417 9.60445C9.77255 15.7961 10.7798 21.0397 11.8463 25.3353L18.3342 12.9817C18.9267 11.8559 19.6673 11.2634 20.5561 11.2042C21.8596 11.1153 22.6594 11.9448 22.9853 13.6927C23.7259 17.6328 24.6739 20.9805 25.7997 23.8245C26.5699 16.2997 27.8734 10.8783 29.7102 7.5307C30.1546 6.7012 30.8063 6.28645 31.6654 6.2272C32.3468 6.16795 32.9689 6.37533 33.5318 6.8197C34.0947 7.26408 34.3909 7.82695 34.4502 8.50832C34.4798 9.04157 34.3909 9.48595 34.1539 9.93033C32.9986 12.0633 32.0506 15.648 31.2803 20.625C30.5397 25.4538 30.2731 29.2162 30.4508 31.9121C30.5101 32.6527 30.3916 33.3045 30.0953 33.8673C29.7398 34.5191 29.2066 34.8746 28.5252 34.9338C27.7549 34.9931 26.955 34.6376 26.1848 33.8377C23.4297 31.0233 21.2374 26.8166 19.6377 21.2175C17.7121 25.0095 16.29 27.8535 15.3717 29.7495C13.6238 33.0971 12.1426 34.8153 10.8983 34.9042C10.0984 34.9635 9.41705 34.2821 8.82455 32.8601C7.31368 28.9792 5.6843 21.4841 3.93643 10.3747C3.84755 9.60445 3.99567 8.92307 4.41042 8.38982Z",
        fill: "#F6F7F7"
      }), Object(external_this_wp_element_["createElement"])("path", {
        d: "M68.1043 13.041C67.0378 11.1746 65.4677 10.0489 63.3643 9.60451C62.8015 9.48601 62.2682 9.42676 61.7646 9.42676C58.9206 9.42676 56.6098 10.908 54.8027 13.8705C53.2622 16.3886 52.4919 19.1734 52.4919 22.2248C52.4919 24.5059 52.9659 26.4611 53.9139 28.0905C54.9804 29.9569 56.5506 31.0826 58.6539 31.527C59.2168 31.6455 59.7501 31.7048 60.2537 31.7048C63.1273 31.7048 65.4381 30.2235 67.2156 27.261C68.7561 24.7133 69.5263 21.9285 69.5263 18.8771C69.5263 16.5664 69.0523 14.6408 68.1043 13.041ZM64.3716 21.2471C63.9568 23.2024 63.2162 24.654 62.1201 25.6316C61.2609 26.4019 60.4611 26.7278 59.7204 26.5796C59.0094 26.4315 58.4169 25.8094 57.9726 24.654C57.6171 23.7356 57.4393 22.8173 57.4393 21.9581C57.4393 21.2175 57.4986 20.4769 57.6467 19.7955C57.9133 18.5809 58.417 17.3959 59.2168 16.2701C60.1945 14.8185 61.2313 14.226 62.2978 14.4334C63.0088 14.5815 63.6013 15.2036 64.0457 16.359C64.4012 17.2774 64.5789 18.1958 64.5789 19.0549C64.5789 19.8251 64.4901 20.5658 64.3716 21.2471Z",
        fill: "#F6F7F7"
      }), Object(external_this_wp_element_["createElement"])("path", {
        d: "M49.5294 13.041C48.4629 11.1746 46.8631 10.0489 44.7894 9.60451C44.2265 9.48601 43.6932 9.42676 43.1896 9.42676C40.3456 9.42676 38.0349 10.908 36.2277 13.8705C34.6872 16.3886 33.917 19.1734 33.917 22.2248C33.917 24.5059 34.391 26.4611 35.339 28.0905C36.4055 29.9569 37.9756 31.0826 40.079 31.527C40.6419 31.6455 41.1751 31.7048 41.6787 31.7048C44.5524 31.7048 46.8631 30.2235 48.6406 27.261C50.1811 24.7133 50.9514 21.9285 50.9514 18.8771C50.9514 16.5664 50.4774 14.6408 49.5294 13.041ZM45.7966 21.2471C45.3819 23.2024 44.6412 24.654 43.5451 25.6316C42.686 26.4019 41.8861 26.7278 41.1455 26.5796C40.4345 26.4315 39.842 25.8094 39.3976 24.654C39.0421 23.7356 38.8644 22.8173 38.8644 21.9581C38.8644 21.2175 38.9236 20.4769 39.0717 19.7955C39.3384 18.5809 39.842 17.3959 40.6419 16.2701C41.6195 14.8185 42.6564 14.226 43.7229 14.4334C44.4339 14.5815 45.0264 15.2036 45.4707 16.359C45.8262 17.2774 46.004 18.1958 46.004 19.0549C46.004 19.8251 45.9447 20.5658 45.7966 21.2471Z",
        fill: "#F6F7F7"
      })), Object(external_this_wp_element_["createElement"])("defs", null, Object(external_this_wp_element_["createElement"])("clipPath", {
        id: "clip0"
      }, Object(external_this_wp_element_["createElement"])("rect", {
        x: "0.5",
        y: "2.19775",
        width: "72.8775",
        height: "43.2525",
        fill: "white"
      }))));
    }
  }]);

  return Logo;
}(external_this_wp_element_["Component"]);

/* harmony default export */ var logo = (logo_Logo);
// CONCATENATED MODULE: ./client/profile-wizard/steps/benefits/images/management.js

/* harmony default export */ var management = (function () {
  return Object(external_this_wp_element_["createElement"])("svg", {
    width: "295",
    height: "160",
    viewBox: "0 0 295 160",
    fill: "none",
    xmlns: "http://www.w3.org/2000/svg"
  }, Object(external_this_wp_element_["createElement"])("g", {
    clipPath: "url(#management-svg)"
  }, Object(external_this_wp_element_["createElement"])("path", {
    d: "M0 6C0 2.6863 2.68629 0 6 0H289C292.314 0 295 2.68629 295 6V160H0V6Z",
    fill: "#F7EDF7"
  }), Object(external_this_wp_element_["createElement"])("path", {
    opacity: "0.05",
    d: "M268 125.429L188.122 25.6443C184.495 28.9602 180.026 31.2158 175.204 32.165C160.166 35.1256 145.48 24.8598 142.402 9.23575C140.226 -1.80872 144.438 -12.5683 152.403 -18.9762L125.167 -53L-30 79.5708L112.833 258L268 125.429Z",
    fill: "#7F54B3"
  }), Object(external_this_wp_element_["createElement"])("path", {
    opacity: "0.05",
    d: "M169 27C181.703 27 192 16.9264 192 4.5C192 -7.9264 181.703 -18 169 -18C156.297 -18 146 -7.9264 146 4.5C146 16.9264 156.297 27 169 27Z",
    fill: "#7F54B3"
  }), Object(external_this_wp_element_["createElement"])("path", {
    d: "M84.0826 16.6412H147.092C150.132 16.6411 153.082 17.6756 155.457 19.5743C157.832 21.4731 159.489 24.1232 160.158 27.0891L191.995 168.296C192.343 169.836 192.349 171.434 192.013 172.977C191.678 174.52 191.009 175.97 190.054 177.227C189.098 178.484 187.879 179.517 186.482 180.253C185.086 180.989 183.545 181.411 181.968 181.489L117.194 184.68C113.725 184.851 110.313 183.754 107.594 181.594C104.875 179.434 103.035 176.359 102.417 172.942L84.3566 73.107L83.013 73.4908L79.5574 54.5684L80.9405 54.2226L75.8993 26.3551C75.6834 25.1615 75.7334 23.9349 76.0459 22.7629C76.3584 21.5908 76.9257 20.5021 77.7072 19.5744C78.4887 18.6467 79.4653 17.9029 80.5673 17.3958C81.6693 16.8888 82.8696 16.6311 84.0826 16.6412Z",
    fill: "white"
  }), Object(external_this_wp_element_["createElement"])("path", {
    d: "M138.797 19.6853H146.152C148.264 19.6853 150.313 20.4076 151.958 21.7324C153.603 23.0572 154.745 24.9047 155.195 26.9683L185.755 167.023C185.931 167.83 185.931 168.665 185.754 169.471C185.578 170.277 185.23 171.036 184.733 171.695C184.237 172.355 183.604 172.899 182.878 173.292C182.152 173.684 181.35 173.915 180.526 173.969L115.962 178.2C114.14 178.32 112.339 177.764 110.901 176.64C109.463 175.516 108.489 173.901 108.165 172.105L82.0889 27.3445C81.9201 26.4075 81.959 25.4449 82.2029 24.5247C82.4468 23.6044 82.8897 22.7489 83.5003 22.0185C84.111 21.288 84.8745 20.7005 85.737 20.2974C86.5995 19.8942 87.54 19.6853 88.4921 19.6853H95.4695C96.1448 19.6853 96.8013 19.9079 97.3373 20.3187C97.8734 20.7294 98.259 21.3054 98.4346 21.9575C98.5895 22.5331 98.93 23.0416 99.4032 23.4043C99.8764 23.7669 100.456 23.9634 101.052 23.9634H134.176C135.344 23.9634 136.468 23.5228 137.325 22.7296C138.181 21.9365 138.707 20.8492 138.797 19.6853L138.797 19.6853Z",
    fill: "#F6F7F7"
  }), Object(external_this_wp_element_["createElement"])("path", {
    d: "M159.366 55.8843H92.5569V56.2134H159.366V55.8843Z",
    fill: "#2F2E41"
  }), Object(external_this_wp_element_["createElement"])("path", {
    d: "M161.999 66.5796H94.532V66.9087H161.999V66.5796Z",
    fill: "#2F2E41"
  }), Object(external_this_wp_element_["createElement"])("path", {
    d: "M96.7759 55.3634L98.294 53.3396L98.0308 53.1421L96.7189 54.8913L91.6633 50.7551L91.4551 51.0102L96.7759 55.3634Z",
    fill: "#2F2E41"
  }), Object(external_this_wp_element_["createElement"])("path", {
    d: "M105.498 103.081L107.016 101.057L106.752 100.86L105.44 102.609L100.385 98.4729L100.177 98.7277L105.498 103.081Z",
    fill: "#2F2E41"
  }), Object(external_this_wp_element_["createElement"])("path", {
    d: "M169.4 103.109L99.4648 104.59L99.4718 104.919L169.407 103.438L169.4 103.109Z",
    fill: "#2F2E41"
  }), Object(external_this_wp_element_["createElement"])("path", {
    d: "M171.539 112.816L100.287 114.462L100.295 114.791L171.547 113.146L171.539 112.816Z",
    fill: "#2F2E41"
  }), Object(external_this_wp_element_["createElement"])("path", {
    opacity: "0.1",
    d: "M127.854 140.871C127.854 145.017 129.501 148.993 132.433 151.924C135.364 154.856 139.341 156.503 143.487 156.503C147.633 156.503 151.609 154.856 154.54 151.924C157.472 148.993 159.119 145.017 159.119 140.871C159.119 140.675 159.116 140.481 159.108 140.287C158.955 136.195 157.202 132.326 154.226 129.512C151.25 126.699 147.289 125.166 143.194 125.242C139.099 125.319 135.198 126.999 132.33 129.922C129.461 132.844 127.854 136.776 127.854 140.871H127.854Z",
    fill: "black"
  }), Object(external_this_wp_element_["createElement"])("path", {
    opacity: "0.1",
    d: "M129.335 146.96C129.335 147.155 129.338 147.349 129.346 147.544C130.863 150.756 133.428 153.355 136.619 154.915C139.81 156.475 143.438 156.901 146.904 156.125C150.37 155.348 153.468 153.414 155.688 150.642C157.908 147.869 159.118 144.423 159.119 140.871C159.119 140.676 159.116 140.481 159.107 140.287C157.591 137.075 155.026 134.476 151.835 132.916C148.643 131.356 145.016 130.93 141.55 131.706C138.084 132.483 134.986 134.417 132.765 137.189C130.545 139.962 129.335 143.408 129.335 146.96H129.335Z",
    fill: "black"
  }), Object(external_this_wp_element_["createElement"])("path", {
    d: "M224.461 59.1491C229.783 59.1491 234.098 54.8348 234.098 49.513C234.098 44.1912 229.783 39.877 224.461 39.877C219.139 39.877 214.824 44.1912 214.824 49.513C214.824 54.8348 219.139 59.1491 224.461 59.1491Z",
    fill: "#2F2E41"
  }), Object(external_this_wp_element_["createElement"])("path", {
    d: "M236.166 86.9957C236.166 86.9957 235.643 98.2553 235.119 99.0408C234.595 99.8264 229.881 111.086 229.881 111.086L227.525 103.754C227.525 103.754 231.191 97.7316 230.667 94.3275C230.143 90.9235 230.473 86.7346 230.473 86.7346L236.166 86.9957Z",
    fill: "#9F616A"
  }), Object(external_this_wp_element_["createElement"])("path", {
    d: "M221.24 59.7637C225.29 59.7637 228.572 56.4811 228.572 52.4319C228.572 48.3827 225.29 45.1001 221.24 45.1001C217.191 45.1001 213.908 48.3827 213.908 52.4319C213.908 56.4811 217.191 59.7637 221.24 59.7637Z",
    fill: "#9F616A"
  }), Object(external_this_wp_element_["createElement"])("path", {
    d: "M227.263 53.217C227.263 53.217 227.001 63.691 228.834 65.2621C230.667 66.8332 218.359 65.524 218.359 65.524C218.359 65.524 220.978 58.1922 218.883 56.6211C216.788 55.05 227.263 53.217 227.263 53.217Z",
    fill: "#9F616A"
  }), Object(external_this_wp_element_["createElement"])("path", {
    d: "M217.574 65.0008C217.574 65.0008 219.145 62.1205 221.24 62.3823C223.335 62.6442 230.144 63.1679 230.406 63.6916C230.667 64.2153 231.453 66.0482 231.977 66.5719C232.501 67.0956 236.429 67.8812 236.69 71.0234C236.952 74.1656 227.263 93.2806 227.263 93.2806C227.263 93.2806 228.573 97.732 228.311 98.7794C228.049 99.8268 229.62 102.969 229.358 104.278C229.096 105.588 233.286 113.443 231.191 123.655V140.152C231.191 140.152 238.262 168.693 236.167 170.788C234.072 172.883 227.001 171.574 225.692 170.788C224.383 170.003 218.884 130.463 218.884 130.463L216.265 117.109L216.003 146.436C216.003 146.436 216.527 171.05 214.694 171.836C212.861 172.621 205.79 172.883 205.266 171.312C204.858 170.087 202.857 137.484 201.996 123.193C201.697 118.26 202.085 113.309 203.149 108.483C204.238 103.54 205.821 97.7324 207.623 95.3754C211.028 90.9239 213.122 76.7841 213.122 76.7841L208.671 68.6667C208.671 68.6667 211.289 66.0482 212.861 66.0482C214.432 66.0482 217.574 65.0008 217.574 65.0008Z",
    fill: "#444053"
  }), Object(external_this_wp_element_["createElement"])("path", {
    d: "M235.119 69.1902L236.5 70.1301C236.5 70.1301 237.476 86.996 236.69 88.0434C235.904 89.0908 230.443 88.5796 230.031 87.6569C229.62 86.7341 235.119 69.1902 235.119 69.1902Z",
    fill: "#444053"
  }), Object(external_this_wp_element_["createElement"])("path", {
    d: "M211.767 84.5249C212.533 89.8422 213.205 94.5756 209.286 98.8578C205.347 103.192 200.904 107.039 196.053 110.319C195.572 111.593 188.515 120.943 188.156 117.213C187.797 113.484 190.235 110.56 193.7 107.406C197.165 104.252 202.012 97.4081 204.747 93.6531C207.482 89.898 206.356 87.5565 206.261 84.42C206.166 81.2835 210.691 84.5628 211.767 84.5249Z",
    fill: "#9F616A"
  }), Object(external_this_wp_element_["createElement"])("path", {
    d: "M212.337 67.8806L208.671 68.6662C208.671 68.6662 208.671 74.4269 207.623 76.7835C206.576 79.1402 204.743 84.639 205.528 84.9008C206.314 85.1627 213.646 88.8286 214.17 86.7338C214.694 84.639 216.003 67.8806 212.337 67.8806Z",
    fill: "#444053"
  }), Object(external_this_wp_element_["createElement"])("path", {
    d: "M234.901 46.4615C240.223 46.4615 244.537 42.1473 244.537 36.8255C244.537 31.5037 240.223 27.1895 234.901 27.1895C229.579 27.1895 225.264 31.5037 225.264 36.8255C225.264 42.1473 229.579 46.4615 234.901 46.4615Z",
    fill: "#2F2E41"
  }), Object(external_this_wp_element_["createElement"])("path", {
    d: "M243.213 44.8046C241.966 45.706 240.521 46.2957 238.999 46.5242C237.477 46.7527 235.922 46.6134 234.465 46.118C233.008 45.6226 231.691 44.7855 230.624 43.6767C229.557 42.5679 228.771 41.2197 228.331 39.7449C228.498 41.0666 228.936 42.3395 229.619 43.4832C230.302 44.6269 231.215 45.6166 232.3 46.3898C233.384 47.163 234.618 47.7029 235.922 47.9754C237.226 48.2479 238.572 48.2471 239.876 47.973C241.18 47.699 242.412 47.1576 243.496 46.3831C244.58 45.6087 245.492 44.6179 246.173 43.4734C246.855 42.3288 247.292 41.0554 247.456 39.7335C247.621 38.4116 247.51 37.07 247.13 35.7932C247.346 37.5085 247.096 39.2503 246.407 40.8358C245.718 42.4214 244.614 43.7922 243.213 44.8046Z",
    fill: "#2F2E41"
  }), Object(external_this_wp_element_["createElement"])("path", {
    d: "M227.031 58.1854C230.934 58.1854 234.098 55.0216 234.098 51.1189C234.098 47.2162 230.934 44.0525 227.031 44.0525C223.128 44.0525 219.964 47.2162 219.964 51.1189C219.964 55.0216 223.128 58.1854 227.031 58.1854Z",
    fill: "#2F2E41"
  }), Object(external_this_wp_element_["createElement"])("path", {
    d: "M221.088 53.3669C224.548 53.3669 227.352 51.1739 227.352 48.4686C227.352 45.7634 224.548 43.5703 221.088 43.5703C217.629 43.5703 214.824 45.7634 214.824 48.4686C214.824 51.1739 217.629 53.3669 221.088 53.3669Z",
    fill: "#2F2E41"
  }), Object(external_this_wp_element_["createElement"])("path", {
    d: "M219.417 55.5916C220.074 55.5916 220.606 54.65 220.606 53.4884C220.606 52.3269 220.074 51.3853 219.417 51.3853C218.761 51.3853 218.229 52.3269 218.229 53.4884C218.229 54.65 218.761 55.5916 219.417 55.5916Z",
    fill: "#A0616A"
  })), Object(external_this_wp_element_["createElement"])("defs", null, Object(external_this_wp_element_["createElement"])("clipPath", {
    id: "management-svg"
  }, Object(external_this_wp_element_["createElement"])("path", {
    d: "M0 6C0 2.6863 2.68629 0 6 0H289C292.314 0 295 2.68629 295 6V160H0V6Z",
    fill: "white"
  }))));
});
// CONCATENATED MODULE: ./client/profile-wizard/steps/benefits/images/sales_tax.js

/* harmony default export */ var sales_tax = (function () {
  return Object(external_this_wp_element_["createElement"])("svg", {
    width: "295",
    height: "160",
    viewBox: "0 0 295 160",
    fill: "none",
    xmlns: "http://www.w3.org/2000/svg"
  }, Object(external_this_wp_element_["createElement"])("g", {
    clipPath: "url(#sales-tax-svg)"
  }, Object(external_this_wp_element_["createElement"])("path", {
    d: "M0 6C0 2.6863 2.68629 0 6 0H289C292.314 0 295 2.68629 295 6V160H0V6Z",
    fill: "#F7EDF7"
  }), Object(external_this_wp_element_["createElement"])("path", {
    opacity: "0.05",
    d: "M164.773 36.3068C148.909 35.7553 133.8 30.6347 119.37 25.0313C104.94 19.4279 90.6516 13.2214 75.1674 10.2506C65.2082 8.34111 53.82 8.07148 45.7947 13.4102C38.0735 18.5577 35.5782 27.4188 34.2349 35.6499C33.2275 41.8416 32.6343 48.3594 35.3992 54.1564C37.3185 58.1813 40.7281 61.564 43.0862 65.4172C51.2879 78.8302 45.4908 95.3807 36.6052 108.468C32.4382 114.613 27.5995 120.478 24.3811 127.013C21.1627 133.548 19.6748 141.046 22.4888 147.719C25.2807 154.337 31.9308 159.298 39.1348 162.791C53.7636 169.9 71.0004 171.917 87.8181 173.066C125.032 175.613 162.447 174.51 199.759 173.407C213.569 172.998 227.438 172.584 241.025 170.449C248.568 169.265 256.357 167.382 261.833 162.85C268.785 157.082 270.508 147.317 265.851 140.083C258.037 127.952 236.437 124.94 230.968 111.921C227.96 104.754 231.049 96.7729 235.417 90.1253C244.79 75.8691 260.5 63.3607 261.328 47.0651C261.897 35.873 254.345 24.6636 242.668 19.3666C230.426 13.8171 213.454 14.5157 204.429 23.7028C195.119 33.1521 178.775 36.7971 164.773 36.3068Z",
    fill: "#7F54B3"
  }), Object(external_this_wp_element_["createElement"])("path", {
    d: "M98.0173 58.1228C98.0173 58.1228 104.812 49.2691 108.518 49.9554C108.518 49.9554 104.4 57.8483 98.0173 59.0248V58.1228Z",
    fill: "#7F54B3"
  }), Object(external_this_wp_element_["createElement"])("path", {
    d: "M101.445 47.2146C101.445 47.2146 107.45 40.4248 110.693 40.4419C110.693 40.4419 105.952 49.5113 101.197 49.428L101.445 47.2146Z",
    fill: "#7F54B3"
  }), Object(external_this_wp_element_["createElement"])("path", {
    d: "M103.474 39.9447L109.273 25.8037C109.273 25.8037 109.565 38.5867 102.736 42.5674L103.474 39.9447Z",
    fill: "#7F54B3"
  }), Object(external_this_wp_element_["createElement"])("path", {
    d: "M101.922 48.1023C101.922 48.1023 101.9 39.0329 99.4566 36.9077C99.4566 36.9077 97.0225 46.8424 100.645 49.9285L101.922 48.1023Z",
    fill: "#7F54B3"
  }), Object(external_this_wp_element_["createElement"])("path", {
    d: "M198.534 25.4629H97.6055C96.2789 25.4629 95.2034 26.5384 95.2034 27.8651V180.276C95.2034 181.602 96.2789 182.678 97.6055 182.678H198.534C199.86 182.678 200.936 181.602 200.936 180.276V27.8651C200.936 26.5384 199.86 25.4629 198.534 25.4629Z",
    fill: "white"
  }), Object(external_this_wp_element_["createElement"])("path", {
    opacity: "0.1",
    d: "M185.408 38.9763H112.041C108.086 38.9763 104.881 42.1819 104.881 46.1363V62.7235C104.881 66.6778 108.086 69.8835 112.041 69.8835H185.408C189.362 69.8835 192.567 66.6778 192.567 62.7235V46.1363C192.567 42.1819 189.362 38.9763 185.408 38.9763Z",
    fill: "black"
  }), Object(external_this_wp_element_["createElement"])("path", {
    d: "M189.303 39.5791H107.945C106.564 39.5791 105.445 40.6985 105.445 42.0793V66.9712C105.445 68.3521 106.564 69.4715 107.945 69.4715H189.303C190.683 69.4715 191.803 68.3521 191.803 66.9712V42.0793C191.803 40.6985 190.683 39.5791 189.303 39.5791Z",
    fill: "#E6E8EC"
  }), Object(external_this_wp_element_["createElement"])("path", {
    opacity: "0.1",
    d: "M122.728 82.7573H110.58C109.119 82.7573 107.935 83.9415 107.935 85.4022V99.4867C107.935 100.947 109.119 102.132 110.58 102.132H122.728C124.189 102.132 125.373 100.947 125.373 99.4867V85.4022C125.373 83.9415 124.189 82.7573 122.728 82.7573Z",
    fill: "black"
  }), Object(external_this_wp_element_["createElement"])("path", {
    opacity: "0.1",
    d: "M144.041 82.7573H131.893C130.432 82.7573 129.248 83.9415 129.248 85.4022V99.4867C129.248 100.947 130.432 102.132 131.893 102.132H144.041C145.502 102.132 146.686 100.947 146.686 99.4867V85.4022C146.686 83.9415 145.502 82.7573 144.041 82.7573Z",
    fill: "black"
  }), Object(external_this_wp_element_["createElement"])("path", {
    opacity: "0.1",
    d: "M165.354 82.7573H153.206C151.745 82.7573 150.561 83.9415 150.561 85.4022V99.4867C150.561 100.947 151.745 102.132 153.206 102.132H165.354C166.815 102.132 167.999 100.947 167.999 99.4867V85.4022C167.999 83.9415 166.815 82.7573 165.354 82.7573Z",
    fill: "black"
  }), Object(external_this_wp_element_["createElement"])("path", {
    opacity: "0.1",
    d: "M182.243 83.1694H178.944C175.04 83.1694 171.875 86.3344 171.875 90.2387V95.4745C171.875 99.3787 175.04 102.544 178.944 102.544H182.243C186.147 102.544 189.312 99.3787 189.312 95.4745V90.2387C189.312 86.3344 186.147 83.1694 182.243 83.1694Z",
    fill: "black"
  }), Object(external_this_wp_element_["createElement"])("path", {
    d: "M186.667 82.7573H174.519C173.059 82.7573 171.875 83.9415 171.875 85.4022V99.4867C171.875 100.947 173.059 102.132 174.519 102.132H186.667C188.128 102.132 189.312 100.947 189.312 99.4867V85.4022C189.312 83.9415 188.128 82.7573 186.667 82.7573Z",
    fill: "#646970"
  }), Object(external_this_wp_element_["createElement"])("path", {
    opacity: "0.1",
    d: "M122.728 104.07H110.58C109.119 104.07 107.935 105.254 107.935 106.715V120.8C107.935 122.26 109.119 123.445 110.58 123.445H122.728C124.189 123.445 125.373 122.26 125.373 120.8V106.715C125.373 105.254 124.189 104.07 122.728 104.07Z",
    fill: "black"
  }), Object(external_this_wp_element_["createElement"])("path", {
    opacity: "0.1",
    d: "M144.041 104.07H131.893C130.432 104.07 129.248 105.254 129.248 106.715V120.8C129.248 122.26 130.432 123.445 131.893 123.445H144.041C145.502 123.445 146.686 122.26 146.686 120.8V106.715C146.686 105.254 145.502 104.07 144.041 104.07Z",
    fill: "black"
  }), Object(external_this_wp_element_["createElement"])("path", {
    opacity: "0.1",
    d: "M165.354 104.07H153.206C151.745 104.07 150.561 105.254 150.561 106.715V120.8C150.561 122.26 151.745 123.445 153.206 123.445H165.354C166.815 123.445 167.999 122.26 167.999 120.8V106.715C167.999 105.254 166.815 104.07 165.354 104.07Z",
    fill: "black"
  }), Object(external_this_wp_element_["createElement"])("path", {
    opacity: "0.1",
    d: "M122.728 127.597H110.58C109.119 127.597 107.935 128.781 107.935 130.242V144.327C107.935 145.787 109.119 146.971 110.58 146.971H122.728C124.189 146.971 125.373 145.787 125.373 144.327V130.242C125.373 128.781 124.189 127.597 122.728 127.597Z",
    fill: "black"
  }), Object(external_this_wp_element_["createElement"])("path", {
    opacity: "0.1",
    d: "M144.041 127.597H131.893C130.432 127.597 129.248 128.781 129.248 130.242V144.327C129.248 145.787 130.432 146.971 131.893 146.971H144.041C145.502 146.971 146.686 145.787 146.686 144.327V130.242C146.686 128.781 145.502 127.597 144.041 127.597Z",
    fill: "black"
  }), Object(external_this_wp_element_["createElement"])("path", {
    opacity: "0.1",
    d: "M165.354 127.597H153.206C151.745 127.597 150.561 128.781 150.561 130.242V144.327C150.561 145.787 151.745 146.971 153.206 146.971H165.354C166.815 146.971 167.999 145.787 167.999 144.327V130.242C167.999 128.781 166.815 127.597 165.354 127.597Z",
    fill: "black"
  }), Object(external_this_wp_element_["createElement"])("path", {
    opacity: "0.1",
    d: "M165.354 151.123H153.206C151.745 151.123 150.561 152.307 150.561 153.768V167.853C150.561 169.313 151.745 170.498 153.206 170.498H165.354C166.815 170.498 167.999 169.313 167.999 167.853V153.768C167.999 152.307 166.815 151.123 165.354 151.123Z",
    fill: "black"
  }), Object(external_this_wp_element_["createElement"])("path", {
    opacity: "0.1",
    d: "M186.542 104.07H174.644C173.115 104.07 171.875 105.31 171.875 106.84V120.675C171.875 122.205 173.115 123.445 174.644 123.445H186.542C188.072 123.445 189.312 122.205 189.312 120.675V106.84C189.312 105.31 188.072 104.07 186.542 104.07Z",
    fill: "black"
  }), Object(external_this_wp_element_["createElement"])("path", {
    opacity: "0.1",
    d: "M144.225 151.123H110.396C109.037 151.123 107.935 152.225 107.935 153.584V168.037C107.935 169.396 109.037 170.498 110.396 170.498H144.225C145.584 170.498 146.686 169.396 146.686 168.037V153.584C146.686 152.225 145.584 151.123 144.225 151.123Z",
    fill: "black"
  }), Object(external_this_wp_element_["createElement"])("path", {
    opacity: "0.1",
    d: "M186.452 127.597H173.071C171.492 127.597 170.213 128.877 170.213 130.455V167.642C170.213 169.221 171.492 170.5 173.071 170.5H186.452C188.031 170.5 189.31 169.221 189.31 167.642V130.455C189.31 128.877 188.031 127.597 186.452 127.597Z",
    fill: "black"
  }), Object(external_this_wp_element_["createElement"])("path", {
    d: "M187.925 64.251H180.292V65.077H187.925V64.251Z",
    fill: "#646970"
  }), Object(external_this_wp_element_["createElement"])("path", {
    d: "M176.75 64.251H169.117V65.077H176.75V64.251Z",
    fill: "#646970"
  }), Object(external_this_wp_element_["createElement"])("path", {
    d: "M165.572 64.251H157.939V65.077H165.572V64.251Z",
    fill: "#646970"
  }), Object(external_this_wp_element_["createElement"])("path", {
    d: "M154.398 64.251H146.765V65.077H154.398V64.251Z",
    fill: "#646970"
  }), Object(external_this_wp_element_["createElement"])("path", {
    d: "M236.67 122.236C237.456 119.589 236.67 116.941 236.67 116.941V114.444C236.67 114.444 237.569 109.561 233.554 106.428L230.892 104.08C230.892 104.08 229.399 102.798 228.965 102.717C228.885 102.7 228.803 102.687 228.72 102.68L228.642 102.634C228.413 102.242 228.218 101.832 228.058 101.408C228.231 101.43 228.406 101.413 228.57 101.357C228.735 101.301 228.884 101.208 229.007 101.085C229.103 100.974 229.179 100.847 229.23 100.71C229.385 100.364 229.412 99.9448 229.541 99.5771C229.655 99.2789 229.821 99.0034 230.032 98.7633C230.005 98.8074 229.975 98.8491 229.951 98.8956C230.328 98.425 230.821 98.023 231.176 97.5279C231.247 97.4297 231.312 97.3273 231.37 97.2215C231.355 97.2435 231.336 97.2631 231.319 97.2852C231.809 96.4984 231.958 95.5449 232.101 94.6257C232.169 94.3128 232.174 93.9894 232.115 93.6746C232.046 93.4657 231.958 93.2637 231.85 93.0716C231.272 91.8779 231.282 90.3753 230.358 89.4218C229.662 88.7085 228.608 88.4903 227.615 88.3678C226.622 88.2452 225.578 88.1741 224.725 87.6545C224.487 87.4739 224.223 87.3294 223.943 87.2255C223.802 87.1763 223.648 87.1778 223.508 87.2297C223.368 87.2816 223.251 87.3806 223.176 87.5098C223.158 87.5863 223.123 87.6577 223.073 87.7182C223.028 87.7434 222.979 87.7581 222.928 87.761C222.877 87.764 222.826 87.7552 222.779 87.7353L221.457 87.3824L221.472 87.4486H221.438L221.487 87.6569C221.513 87.6523 221.539 87.657 221.562 87.6699C221.585 87.6829 221.603 87.7034 221.612 87.728L221.399 88.0565C220.99 88.049 220.586 87.9659 220.207 87.8113C220.205 87.8325 220.205 87.8539 220.207 87.8751H220.188C220.163 88.2476 220.587 88.4903 220.717 88.8408C220.769 88.9942 220.769 89.1605 220.717 89.3139C220.664 89.4562 220.58 89.585 220.472 89.6914C220.004 90.1547 219.403 90.064 218.864 90.3557C218.817 90.3756 218.776 90.4054 218.742 90.4431C218.708 90.4808 218.683 90.5254 218.668 90.5738C218.647 90.61 218.64 90.6532 218.651 90.6939C218.672 90.7502 218.711 90.7983 218.761 90.8312C218.918 90.9581 219.09 91.0651 219.273 91.1499C219.219 91.1989 219.168 91.2479 219.119 91.2994C218.435 91.9949 217.933 92.8492 217.659 93.7859C217.386 94.7225 217.348 95.7124 217.551 96.667C217.753 97.6216 218.188 98.5113 218.818 99.2565C219.448 100.002 220.253 100.579 221.161 100.938C221.18 101.04 221.195 101.146 221.21 101.256C221.21 101.268 221.21 101.276 221.21 101.288L221.225 101.43L219.445 102.188C219.177 102.186 218.914 102.107 218.689 101.962C218.463 101.817 218.283 101.61 218.17 101.366C217.756 100.511 216.293 101.366 216.293 101.366C216.293 101.366 211.829 101.033 210.165 101.857C210.165 101.857 207.5 100.739 207.125 100.663C206.75 100.587 206.189 100.29 205.927 100.366C205.664 100.442 205.326 99.8761 205.137 99.9178C204.949 99.9595 204.875 99.5452 204.275 99.3957C203.674 99.2462 203.448 99.4349 202.774 98.7633C202.1 98.0917 196.619 95.4444 196.619 95.4444L194.158 93.4197L191.533 91.3313L191.487 91.417C191.469 91.3558 191.452 91.292 191.435 91.2234C191.174 90.183 190.846 89.1604 190.455 88.1619C190.286 87.7574 190.21 87.2476 189.982 87.5172C189.842 87.679 189.604 87.3235 189.246 87.0833C189.17 87.0309 189.084 86.9961 188.993 86.9813C188.902 86.9666 188.809 86.9724 188.72 86.9982C188.631 87.024 188.55 87.0693 188.481 87.1307C188.412 87.192 188.358 87.268 188.322 87.353C186.278 90.5591 185.545 90.7846 186.729 91.9637C187.785 93.0103 188.486 93.9246 189.347 94.2139L189.45 94.2457C189.431 94.2506 189.412 94.2506 189.393 94.2457L194.384 100.212C194.384 100.212 197.985 103.345 200.463 104.35C202.941 105.355 202.75 105.656 202.75 105.656C202.75 105.656 210.255 109.458 211.417 109.384C212.579 109.311 212.356 117.177 211.868 118.454C211.381 119.731 210.594 128.893 210.594 128.893C210.594 128.893 209.841 130.832 210.63 132.173C210.63 132.173 210.805 134.742 210.648 135.36C209.964 136.414 208.988 138.203 208.829 140.044C208.829 140.142 208.809 140.233 208.799 140.328C208.749 140.386 208.69 140.435 208.623 140.473L208.79 140.424C208.719 141.191 208.554 141.947 208.299 142.674C207.903 143.695 207.693 144.78 207.679 145.875V151.797L207.189 155.474C207.189 155.474 206.588 158.256 207.189 158.906C207.442 159.23 207.56 159.639 207.517 160.048C207.492 160.523 207.627 160.992 207.9 161.382C208.004 161.495 208.081 161.632 208.123 161.78C208.166 161.929 208.173 162.085 208.145 162.237C207.946 162.882 208.439 169.743 208.439 169.743C208.439 169.743 208.294 171.632 208.096 171.733C207.897 171.833 208.439 173.919 208.439 173.919C208.439 173.919 206.412 175.611 206.929 176.505L207.446 177.3L207.309 177.434L207.226 177.52C207.069 177.43 206.887 177.396 206.709 177.422C206.071 178.074 205.39 178.681 204.669 179.239C203.755 179.898 201.267 180.854 201.267 180.854L199.306 181.013H199.269C199.269 181.013 198.33 180.925 198.857 182.379C199.384 183.832 204.096 183.386 204.096 183.386L206.846 182.952C206.846 182.952 211.748 182.977 213.153 182.665C213.739 182.535 213.888 182.222 213.839 181.905L213.824 181.891C213.757 181.589 213.619 181.308 213.422 181.07C213.412 181.054 213.401 181.039 213.388 181.025L213.476 180.02C215.557 178.822 212.888 176.464 212.888 176.464C212.888 176.464 213.802 174.476 214.271 173.831C214.739 173.187 214.739 163.688 214.739 163.688C214.739 163.688 214.984 160.256 214.339 159.462C214.328 159.204 214.387 158.947 214.511 158.719C214.634 158.492 214.817 158.302 215.04 158.171C215.503 157.854 216.903 153.499 218.001 149.883L218.692 154.143C218.692 154.143 218.543 156.183 218.992 156.778C219.076 156.891 219.136 157.019 219.168 157.156C219.2 157.292 219.203 157.434 219.178 157.572C219.071 158.144 219.106 158.733 219.278 159.288C219.331 159.526 219.459 159.741 219.644 159.901C220.043 160.1 220.068 160.808 220.217 160.958C220.367 161.107 221.293 160.747 221.342 161.293C221.37 161.773 221.37 162.255 221.342 162.735L221.644 171.583C221.644 171.583 221.153 172.319 221.043 174.28C221.001 175.116 220.845 175.591 220.683 175.863C220.593 176.009 220.552 176.18 220.565 176.351C220.578 176.522 220.645 176.684 220.756 176.814L220.842 176.915L220.472 177.199C220.323 177.315 220.219 177.478 220.177 177.662C220.135 177.845 220.158 178.038 220.242 178.207C220.122 178.31 220.037 178.448 219.996 178.601C219.955 178.754 219.962 178.915 220.014 179.065C220.029 179.114 220.05 179.161 220.077 179.204C219.796 179.248 219.535 179.377 219.33 179.574C218.96 180.082 218.514 180.53 218.009 180.903C217.274 181.466 216.572 182.068 215.903 182.707C215.588 183.136 215.165 183.474 214.677 183.687C214.558 183.736 214.434 183.773 214.307 183.798C213.327 183.999 213.633 185.023 214.432 185.193C215.231 185.362 217.884 186.173 219.947 185.7C219.947 185.7 220.56 185.945 221.46 185.489C222.359 185.033 224.367 184.474 224.367 184.474C224.367 184.474 225.492 184.474 225.644 184.028C225.684 183.886 225.705 183.739 225.708 183.592L225.74 183.56L225.71 183.533C225.713 183.199 225.688 182.865 225.634 182.535C225.605 182.364 225.573 182.212 225.544 182.089C225.448 181.717 225.269 181.234 225.193 181.03C225.206 181.014 225.217 180.997 225.227 180.979C225.656 180.244 224.943 178.498 224.943 178.498C225.177 178.127 225.462 177.789 225.789 177.496C226.188 177.197 227.24 169.441 227.24 169.441C227.24 169.441 227.188 167.453 227.73 166.559C228.272 165.664 226.98 160.247 226.98 160.247C226.98 160.247 226.428 159.004 226.629 158.555C226.83 158.107 226.428 156.467 226.428 156.467V154.506C226.427 154.272 226.388 154.041 226.313 153.82C226.159 153.359 225.931 152.474 226.178 151.839C226.529 150.944 227.328 145.427 227.328 145.427C227.328 145.427 226.703 142.993 227.62 141.054C227.691 140.899 227.773 140.749 227.865 140.605C228.544 139.539 228.34 137.644 228.005 136.117C227.956 135.872 227.899 135.651 227.843 135.438C227.821 135.345 227.796 135.257 227.772 135.168L227.88 135.124C227.88 135.124 227.88 134.98 227.914 134.732C228.284 134.659 228.635 134.51 228.945 134.296C229.256 134.081 229.519 133.805 229.718 133.485C229.718 133.485 232.007 130.911 234.032 129.83C236.057 128.749 236.209 127.182 236.209 127.182C235.91 126.66 236.746 124.535 236.746 124.535L236.67 122.236ZM230.066 125.668L227.899 128.18V128.165C227.335 127.381 228.424 125.628 228.424 125.628L230.225 121.608C230.211 122.123 230.147 122.589 229.98 122.792C229.49 123.464 230.882 124.584 230.882 124.584C230.14 124.587 230.066 125.668 230.066 125.668Z",
    fill: "url(#paint0_linear)"
  }), Object(external_this_wp_element_["createElement"])("path", {
    d: "M191.915 92.7263L190.732 95.023C190.357 94.991 189.987 94.9136 189.631 94.7926C189.066 94.624 188.544 94.3376 188.098 93.9522C187.652 93.5669 187.293 93.0914 187.045 92.5571C185.989 90.2236 187.736 88.7161 188.55 88.27C189.364 87.8238 190.121 87.3165 190.732 88.7798C191.115 89.7726 191.435 90.7886 191.69 91.8218C191.832 92.3586 191.915 92.7263 191.915 92.7263Z",
    fill: "#CC818C"
  }), Object(external_this_wp_element_["createElement"])("path", {
    d: "M229.515 104.674L227.625 107.488C227.625 107.488 218.695 111.748 220.401 106.752C221.151 104.546 221.239 102.936 221.104 101.82C221.104 101.808 221.104 101.801 221.104 101.789C220.928 100.394 220.403 99.7884 220.403 99.7884C220.403 99.7884 229.108 95.3321 227.811 98.3789C227.546 99.0122 227.441 99.7007 227.505 100.384C227.611 101.368 227.931 102.318 228.441 103.166C228.747 103.703 229.107 104.208 229.515 104.674Z",
    fill: "#CC818C"
  }), Object(external_this_wp_element_["createElement"])("path", {
    d: "M226.796 160.416C226.796 160.416 228.08 165.808 227.531 166.688C226.982 167.568 227.041 169.554 227.041 169.554C227.041 169.554 226.004 177.26 225.61 177.554C225.286 177.841 225.003 178.171 224.769 178.535C224.769 178.535 225.475 180.28 225.051 181.001C224.985 181.111 224.887 181.196 224.769 181.246C224.107 181.525 221.661 180.285 220.268 179.53C220.156 179.469 220.061 179.381 219.99 179.275C219.918 179.169 219.874 179.047 219.86 178.92C219.846 178.793 219.863 178.665 219.909 178.545C219.956 178.426 220.03 178.32 220.126 178.236C220.043 178.069 220.019 177.878 220.06 177.696C220.101 177.513 220.204 177.351 220.352 177.236L220.719 176.951L220.634 176.851C220.524 176.721 220.457 176.56 220.444 176.39C220.431 176.22 220.471 176.05 220.56 175.905C220.719 175.638 220.874 175.169 220.916 174.334C221.016 172.407 221.509 171.667 221.509 171.667L221.212 162.872C221.24 162.395 221.24 161.917 221.212 161.44C221.163 160.899 220.249 161.256 220.102 161.107C219.955 160.957 219.928 160.256 219.533 160.058C219.351 159.898 219.226 159.684 219.175 159.447C219.001 158.893 218.966 158.303 219.075 157.732C219.1 157.595 219.097 157.454 219.065 157.318C219.034 157.182 218.974 157.054 218.891 156.942C218.447 156.349 218.594 154.327 218.594 154.327L217.913 150.091C216.827 153.685 215.447 158.014 214.989 158.327C214.769 158.459 214.588 158.648 214.467 158.873C214.345 159.099 214.286 159.353 214.297 159.609C214.94 160.401 214.692 163.811 214.692 163.811C214.692 163.811 214.692 173.243 214.229 173.887C213.765 174.532 212.868 176.515 212.868 176.515C212.868 176.515 215.516 178.871 213.432 180.06H213.41C211.285 181.246 206.89 177.937 206.89 177.937L207.348 177.478L207.483 177.344L206.963 176.569C206.453 175.679 208.454 174 208.454 174C208.454 174 207.919 171.924 208.115 171.826C208.311 171.728 208.454 169.85 208.454 169.85C208.454 169.85 207.963 163.034 208.164 162.391C208.191 162.24 208.183 162.085 208.141 161.938C208.098 161.79 208.022 161.654 207.919 161.541C207.649 161.154 207.517 160.688 207.542 160.217C207.585 159.811 207.468 159.404 207.216 159.082C206.623 158.44 207.216 155.675 207.216 155.675L207.706 152.018V146.211C207.713 145.09 207.923 143.98 208.326 142.934C208.626 142.086 208.807 141.201 208.865 140.304C209.027 138.431 210.035 136.612 210.711 135.575C211.069 135.026 211.336 134.698 211.336 134.698L227.142 134.08C227.142 134.08 227.387 134.784 227.651 135.762C227.705 135.975 227.759 136.2 227.811 136.436C228.142 137.955 228.34 139.838 227.671 140.897C227.58 141.04 227.498 141.189 227.426 141.343C226.521 143.27 227.139 145.689 227.139 145.689C227.139 145.689 226.35 151.17 226.004 152.062C225.759 152.692 225.985 153.572 226.139 154.023C226.211 154.244 226.248 154.475 226.249 154.707V156.66C226.249 156.66 226.646 158.291 226.448 158.737C226.249 159.183 226.796 160.416 226.796 160.416Z",
    fill: "#444053"
  }), Object(external_this_wp_element_["createElement"])("path", {
    opacity: "0.1",
    d: "M227.524 141.054C227.433 141.196 227.351 141.344 227.279 141.497L226.389 141.448C226.475 140.99 225.87 136.693 225.87 136.693L227.661 136.593C227.992 138.11 228.193 139.992 227.524 141.054Z",
    fill: "black"
  }), Object(external_this_wp_element_["createElement"])("path", {
    d: "M227.671 140.904C227.58 141.048 227.499 141.197 227.426 141.35L226.536 141.299C226.622 140.843 226.017 136.543 226.017 136.543L227.808 136.443C228.142 137.963 228.34 139.845 227.671 140.904Z",
    fill: "#444053"
  }), Object(external_this_wp_element_["createElement"])("path", {
    opacity: "0.05",
    d: "M227.671 140.904C227.58 141.048 227.499 141.197 227.426 141.35L226.536 141.299C226.622 140.843 226.017 136.543 226.017 136.543L227.808 136.443C228.142 137.963 228.34 139.845 227.671 140.904Z",
    fill: "black"
  }), Object(external_this_wp_element_["createElement"])("path", {
    opacity: "0.1",
    d: "M220.869 160.774C220.869 160.774 221.894 159.303 223.649 159.303C225.404 159.303 226.154 159.104 226.154 159.104C226.154 159.104 225.031 159.771 224.254 159.857C223.477 159.943 220.869 160.774 220.869 160.774Z",
    fill: "black"
  }), Object(external_this_wp_element_["createElement"])("path", {
    opacity: "0.1",
    d: "M208.662 140.728C208.736 140.74 209.983 139.431 210.069 138.914C210.137 138.587 210.332 138.299 210.611 138.115C210.611 138.115 211.415 138.436 210.699 139.421C209.983 140.407 208.662 140.728 208.662 140.728Z",
    fill: "black"
  }), Object(external_this_wp_element_["createElement"])("path", {
    opacity: "0.1",
    d: "M227.799 98.3738C227.515 99.0454 227.265 99.1092 227.309 99.7489C226.978 100.099 226.597 100.399 226.179 100.639C225.296 101.163 224.335 102.19 223.237 102.19C222.501 102.191 221.772 102.053 221.088 101.783C220.911 100.389 220.386 99.7833 220.386 99.7833C220.386 99.7833 229.093 95.327 227.799 98.3738Z",
    fill: "black"
  }), Object(external_this_wp_element_["createElement"])("path", {
    d: "M229.132 96.0035C229.132 97.167 228.787 98.3044 228.141 99.2718C227.494 100.239 226.575 100.993 225.5 101.439C224.425 101.884 223.243 102 222.101 101.773C220.96 101.546 219.912 100.986 219.089 100.163C218.267 99.3406 217.706 98.2923 217.479 97.1512C217.252 96.01 217.369 94.8272 217.814 93.7522C218.259 92.6772 219.013 91.7585 219.981 91.112C220.948 90.4656 222.086 90.1206 223.249 90.1206C224.809 90.1206 226.306 90.7404 227.409 91.8437C228.512 92.9469 229.132 94.4432 229.132 96.0035Z",
    fill: "#CC818C"
  }), Object(external_this_wp_element_["createElement"])("path", {
    opacity: "0.1",
    d: "M229.515 104.673L227.625 107.487C227.625 107.487 218.695 111.747 220.401 106.752C221.151 104.546 221.239 102.935 221.104 101.82H221.119C221.225 102.19 221.285 102.571 221.298 102.955C221.325 103.725 221.345 104.698 223.259 105.019C226.446 105.553 228.147 103.563 228.446 103.173C228.751 103.708 229.109 104.21 229.515 104.673Z",
    fill: "black"
  }), Object(external_this_wp_element_["createElement"])("path", {
    opacity: "0.1",
    d: "M191.915 92.7262L190.731 95.023C190.357 94.9909 189.987 94.9135 189.631 94.7926L189.528 94.67C190.018 94.8563 191.638 91.78 191.638 91.78L191.69 91.8217C191.832 92.3585 191.915 92.7262 191.915 92.7262Z",
    fill: "black"
  }), Object(external_this_wp_element_["createElement"])("path", {
    opacity: "0.1",
    d: "M227.652 135.769C223.838 137.426 210.645 136.015 210.645 136.015C210.685 135.874 210.707 135.729 210.711 135.583C211.069 135.034 211.337 134.706 211.337 134.706L227.142 134.088C227.142 134.088 227.399 134.791 227.652 135.769Z",
    fill: "black"
  }), Object(external_this_wp_element_["createElement"])("path", {
    d: "M221.109 101.958L219.349 102.71C219.084 102.708 218.825 102.63 218.602 102.485C218.379 102.341 218.202 102.135 218.092 101.894C217.683 101.043 216.239 101.894 216.239 101.894C216.239 101.894 211.827 101.56 210.199 102.384C210.199 102.384 207.569 101.271 207.199 101.198C206.829 101.124 206.272 100.828 206.013 100.901C205.753 100.975 205.422 100.411 205.235 100.457C205.049 100.504 204.976 100.087 204.382 99.9377C203.789 99.7882 203.569 99.9745 202.912 99.3078C202.255 98.641 196.835 96.0109 196.835 96.0109L194.384 94.0107L191.791 91.937C191.791 91.937 190.161 95.0108 189.678 94.8245L194.605 100.754C194.605 100.754 198.161 103.865 200.608 104.865C203.054 105.865 202.868 106.161 202.868 106.161C202.868 106.161 210.278 109.941 211.425 109.868C212.572 109.794 212.356 117.601 211.866 118.861C211.376 120.121 210.609 129.234 210.609 129.234C210.609 129.234 209.873 131.161 210.645 132.495C210.645 132.495 210.829 135.237 210.645 135.718C210.645 135.718 223.919 137.137 227.686 135.458C227.686 135.458 228.242 129.308 227.686 128.531C227.13 127.754 228.206 126.021 228.206 126.021L230.576 120.724L236.356 114.907C236.356 114.907 237.243 110.054 233.28 106.943L230.649 104.607C230.649 104.607 229.179 103.335 228.747 103.254C228.316 103.173 228.475 103.254 228.475 103.254C228.475 103.254 226.759 105.757 223.24 105.164C221.333 104.843 221.313 103.869 221.279 103.102C221.269 102.715 221.212 102.331 221.109 101.958Z",
    fill: "#67647E"
  }), Object(external_this_wp_element_["createElement"])("path", {
    opacity: "0.1",
    d: "M228.353 121.901C228.353 121.901 220.2 122.234 218.572 124.795C218.572 124.795 220.685 123.793 222.573 124.09C224.46 124.386 228.353 121.901 228.353 121.901Z",
    fill: "black"
  }), Object(external_this_wp_element_["createElement"])("path", {
    opacity: "0.1",
    d: "M219.349 130.827C219.349 130.827 223.203 131.087 224.019 130.271C224.835 129.455 224.838 128.974 224.838 128.974L219.349 130.827Z",
    fill: "black"
  }), Object(external_this_wp_element_["createElement"])("path", {
    opacity: "0.1",
    d: "M229.132 114.044C229.132 114.044 225.87 117.898 229.836 119.564L229.132 114.044Z",
    fill: "black"
  }), Object(external_this_wp_element_["createElement"])("path", {
    opacity: "0.1",
    d: "M209.829 106.6C209.829 106.6 211.015 109.019 211.608 109.179L209.829 106.6Z",
    fill: "black"
  }), Object(external_this_wp_element_["createElement"])("path", {
    opacity: "0.1",
    d: "M205.828 105.561C205.828 105.561 208.792 108.23 209.348 108.487C209.348 108.487 206.272 105.561 205.828 105.561Z",
    fill: "black"
  }), Object(external_this_wp_element_["createElement"])("path", {
    opacity: "0.1",
    d: "M217.646 103.006C217.646 103.006 214.792 107.267 216.645 109.87C216.645 109.86 215.609 106.784 217.646 103.006Z",
    fill: "black"
  }), Object(external_this_wp_element_["createElement"])("path", {
    d: "M224.838 135.274L223.965 136.096L223.842 136.208L222.832 137.164C222.761 137.218 222.69 137.272 222.617 137.321C218.506 140.263 217.682 135.792 217.682 135.792C217.682 135.792 215.869 136.978 215.609 136.015C215.349 135.051 219.832 133.681 220.511 133.755C220.571 133.761 220.631 133.761 220.69 133.755C221.014 133.701 221.326 133.59 221.612 133.429L221.81 133.323C222.039 133.195 222.26 133.055 222.475 132.904L224.838 135.274Z",
    fill: "#CC818C"
  }), Object(external_this_wp_element_["createElement"])("path", {
    opacity: "0.1",
    d: "M229.27 96.0032C229.272 97.0235 229.009 98.0269 228.506 98.9148C228.003 99.8026 227.278 100.544 226.402 101.067C226.32 100.93 226.247 100.786 226.186 100.638C225.975 100.148 225.845 99.6187 225.632 99.126C225.399 98.5882 225.066 98.0992 224.652 97.6847C224.581 97.6026 224.491 97.5401 224.389 97.5033C224.294 97.4902 224.197 97.4967 224.104 97.5224C224.011 97.5481 223.925 97.5925 223.85 97.6528C223.6 97.8219 223.322 97.944 223.029 98.0132C222.882 98.0468 222.729 98.042 222.585 97.999C222.44 97.9561 222.31 97.8765 222.205 97.768C221.975 97.4935 222.007 97.0915 222.073 96.741C222.217 95.9738 222.458 95.2188 222.46 94.4393C222.463 93.6598 222.164 92.8215 221.48 92.4342C220.882 92.0935 220.139 92.1891 219.475 91.9979C219.372 91.971 219.271 91.9366 219.173 91.895C219.99 91.0568 221.038 90.4812 222.183 90.2418C223.329 90.0024 224.52 90.1102 225.604 90.5513C226.688 90.9923 227.616 91.7467 228.269 92.7179C228.922 93.689 229.27 94.8329 229.27 96.0032Z",
    fill: "black"
  }), Object(external_this_wp_element_["createElement"])("path", {
    d: "M218.771 90.954C218.665 91.0104 218.548 91.1134 218.58 91.231C218.6 91.2871 218.638 91.3352 218.687 91.3683C218.962 91.5898 219.279 91.7525 219.619 91.8463C220.283 92.0277 221.028 91.9419 221.626 92.2826C222.303 92.6674 222.607 93.5082 222.607 94.2877C222.607 95.0671 222.362 95.8221 222.222 96.5869C222.156 96.9399 222.122 97.3419 222.352 97.6164C222.457 97.7242 222.588 97.8034 222.732 97.8463C222.876 97.8892 223.029 97.8945 223.176 97.8615C223.469 97.7937 223.747 97.6724 223.997 97.5036C224.072 97.4429 224.159 97.3981 224.252 97.372C224.345 97.3459 224.443 97.3389 224.538 97.3517C224.639 97.39 224.729 97.4523 224.801 97.533C225.216 97.9482 225.548 98.4381 225.781 98.9768C225.997 99.467 226.124 99.9941 226.335 100.489C226.519 100.994 226.863 101.425 227.316 101.715C227.786 101.975 228.438 101.96 228.801 101.558C229.164 101.156 229.134 100.604 229.306 100.122C229.6 99.2808 230.421 98.7537 230.936 98.0282C231.512 97.2119 231.671 96.1873 231.828 95.1995C231.898 94.8885 231.903 94.5664 231.843 94.2533C231.776 94.0461 231.689 93.8458 231.583 93.6552C231.01 92.4689 231.022 90.9761 230.112 90.0299C229.426 89.3191 228.384 89.1034 227.404 88.9808C226.423 88.8583 225.394 88.7896 224.553 88.2724C224.317 88.0942 224.057 87.9506 223.781 87.8459C223.641 87.7967 223.488 87.7982 223.349 87.8502C223.211 87.9021 223.094 88.0013 223.021 88.1303C223.004 88.2057 222.969 88.2761 222.921 88.3362C222.877 88.3615 222.827 88.3763 222.777 88.3792C222.726 88.3822 222.676 88.3733 222.629 88.3533L221.325 88.0028L221.474 88.6597C221 88.6796 220.528 88.5959 220.089 88.4146C220.065 88.7872 220.484 89.0274 220.614 89.3755C220.661 89.5338 220.663 89.702 220.619 89.8613C220.576 90.0206 220.489 90.1647 220.369 90.2775C219.896 90.753 219.305 90.6623 218.771 90.954Z",
    fill: "#2F2E41"
  }), Object(external_this_wp_element_["createElement"])("path", {
    opacity: "0.1",
    d: "M224.838 135.274L223.965 136.095L221.798 133.321C222.027 133.192 222.248 133.052 222.463 132.901L224.838 135.274Z",
    fill: "black"
  }), Object(external_this_wp_element_["createElement"])("path", {
    d: "M234.662 112.54L236.378 114.91V117.393C236.378 117.393 237.155 120.023 236.378 122.653L236.461 124.95C236.461 124.95 235.635 127.06 235.932 127.58C235.932 127.58 235.785 129.137 233.784 130.21C231.784 131.284 229.514 133.828 229.514 133.828C229.514 133.828 228.661 135.458 226.365 135.088L224.178 136.015L221.661 132.791L225.068 130.938C225.068 130.938 226.254 129.347 226.958 129.421L229.848 126.048C229.848 126.048 229.921 124.974 230.662 124.974C230.662 124.974 229.291 123.864 229.772 123.197C230.252 122.531 229.848 119.565 229.848 119.565L234.662 112.54Z",
    fill: "#67647E"
  }), Object(external_this_wp_element_["createElement"])("path", {
    opacity: "0.1",
    d: "M231.476 123.344C231.476 123.344 235.366 123.27 235.846 124.271C236.326 125.271 232.65 122.788 231.476 123.344Z",
    fill: "black"
  }), Object(external_this_wp_element_["createElement"])("path", {
    opacity: "0.1",
    d: "M223.86 136.014C223.86 136.079 223.855 136.144 223.843 136.208C223.798 136.506 223.651 136.78 223.428 136.983C223.205 137.185 222.918 137.305 222.617 137.321H222.548C222.201 137.321 221.868 137.183 221.623 136.937C221.377 136.692 221.239 136.359 221.239 136.012C221.24 135.867 221.265 135.724 221.313 135.588C220.695 135.098 220.661 134.195 220.678 133.749C221.002 133.695 221.314 133.585 221.6 133.423C221.761 133.886 221.977 134.328 222.244 134.74C222.344 134.716 222.446 134.704 222.548 134.703C222.721 134.703 222.891 134.737 223.05 134.803C223.209 134.869 223.354 134.965 223.476 135.087C223.597 135.209 223.694 135.353 223.76 135.512C223.826 135.672 223.86 135.842 223.86 136.014Z",
    fill: "black"
  }), Object(external_this_wp_element_["createElement"])("path", {
    d: "M221.717 133.129C221.717 133.129 220.84 133.328 220.84 133.487C220.84 133.646 220.641 135.093 221.604 135.693C222.568 136.294 222.47 134.865 222.47 134.865C222.47 134.865 221.641 133.595 221.717 133.129Z",
    fill: "#575988"
  }), Object(external_this_wp_element_["createElement"])("path", {
    d: "M222.695 137.323C223.418 137.323 224.004 136.737 224.004 136.015C224.004 135.292 223.418 134.706 222.695 134.706C221.972 134.706 221.386 135.292 221.386 136.015C221.386 136.737 221.972 137.323 222.695 137.323Z",
    fill: "#E4AAB4"
  }), Object(external_this_wp_element_["createElement"])("path", {
    d: "M222.695 137.137C223.315 137.137 223.818 136.635 223.818 136.015C223.818 135.394 223.315 134.892 222.695 134.892C222.075 134.892 221.573 135.394 221.573 136.015C221.573 136.635 222.075 137.137 222.695 137.137Z",
    fill: "#DCE6F2"
  }), Object(external_this_wp_element_["createElement"])("path", {
    opacity: "0.1",
    d: "M217.597 136.522C217.597 136.522 220.364 138.816 223.918 137.36V138.473C223.918 138.473 221.548 140.103 219.401 139.115C217.254 138.127 217.597 136.522 217.597 136.522Z",
    fill: "black"
  }), Object(external_this_wp_element_["createElement"])("path", {
    opacity: "0.1",
    d: "M217.597 136.225C217.597 136.225 220.364 138.522 223.918 137.063V138.176C223.918 138.176 221.548 139.806 219.401 138.818C217.254 137.831 217.597 136.225 217.597 136.225Z",
    fill: "black"
  }), Object(external_this_wp_element_["createElement"])("path", {
    d: "M217.597 136.225C217.597 136.225 220.364 138.522 223.918 137.063V138.176C223.918 138.176 221.548 139.806 219.401 138.818C217.254 137.831 217.597 136.225 217.597 136.225Z",
    fill: "#444053"
  }), Object(external_this_wp_element_["createElement"])("path", {
    opacity: "0.05",
    d: "M217.67 136.225C217.67 136.225 220.438 138.522 223.994 137.063V138.176C223.994 138.176 221.624 139.806 219.474 138.818C217.325 137.831 217.67 136.225 217.67 136.225Z",
    fill: "black"
  }), Object(external_this_wp_element_["createElement"])("path", {
    opacity: "0.05",
    d: "M215.298 143.179L217.925 150.108C217.925 150.108 218.295 145.65 217.513 144.316C217.291 143.935 216.965 143.625 216.572 143.424C216.179 143.222 215.737 143.137 215.298 143.179Z",
    fill: "black"
  }), Object(external_this_wp_element_["createElement"])("g", {
    opacity: "0.1"
  }, Object(external_this_wp_element_["createElement"])("path", {
    opacity: "0.1",
    d: "M221.754 88.2718L221.732 88.1713L221.311 88.0586L221.357 88.2645C221.489 88.2743 221.622 88.2767 221.754 88.2718Z",
    fill: "black"
  }), Object(external_this_wp_element_["createElement"])("path", {
    opacity: "0.1",
    d: "M220.595 89.9436L220.639 89.9043C220.76 89.7917 220.847 89.6476 220.89 89.4882C220.933 89.3288 220.931 89.1606 220.884 89.0023C220.833 88.8794 220.762 88.7658 220.673 88.6665C220.465 88.6255 220.262 88.5656 220.065 88.4875C220.041 88.8577 220.457 89.0979 220.587 89.446C220.648 89.6059 220.65 89.7819 220.595 89.9436Z",
    fill: "black"
  }), Object(external_this_wp_element_["createElement"])("path", {
    opacity: "0.1",
    d: "M227.603 101.334C227.146 101.046 226.797 100.616 226.61 100.109C226.402 99.6186 226.272 99.0892 226.058 98.5989C225.826 98.0602 225.493 97.5703 225.078 97.1552C225.007 97.0736 224.917 97.0112 224.816 96.9738C224.72 96.9612 224.624 96.968 224.531 96.9937C224.438 97.0194 224.352 97.0634 224.276 97.1233C224.027 97.2924 223.749 97.4145 223.455 97.4836C223.309 97.5168 223.156 97.5117 223.011 97.4688C222.867 97.4259 222.736 97.3466 222.632 97.2385C222.401 96.964 222.433 96.5595 222.499 96.209C222.644 95.4442 222.884 94.6868 222.887 93.9098C222.889 93.1328 222.59 92.2896 221.906 91.9047C221.308 91.564 220.565 91.6596 219.901 91.4684C219.56 91.3747 219.243 91.212 218.967 90.9904C218.95 90.9762 218.933 90.9606 218.918 90.9438C218.862 90.9652 218.808 90.9906 218.756 91.0198C218.649 91.0762 218.533 91.1792 218.563 91.2968C218.585 91.3526 218.623 91.4004 218.673 91.4341C218.947 91.6561 219.264 91.8188 219.605 91.9121C220.269 92.0935 221.012 92.0077 221.61 92.3484C222.286 92.7332 222.59 93.574 222.59 94.3535C222.59 95.1329 222.345 95.8879 222.203 96.6527C222.137 97.0057 222.105 97.4077 222.335 97.6822C222.44 97.7902 222.571 97.8695 222.715 97.9125C222.859 97.9554 223.012 97.9605 223.159 97.9273C223.452 97.8595 223.73 97.7381 223.98 97.5694C224.055 97.5088 224.141 97.464 224.234 97.4379C224.327 97.4117 224.424 97.4048 224.519 97.4175C224.62 97.4552 224.71 97.5175 224.781 97.5988C225.195 98.0148 225.528 98.5045 225.762 99.0426C225.978 99.5328 226.105 100.06 226.316 100.555C226.5 101.06 226.844 101.491 227.296 101.781C227.765 102.04 228.419 102.026 228.767 101.624C228.863 101.515 228.938 101.388 228.988 101.251C228.79 101.398 228.554 101.485 228.308 101.5C228.062 101.514 227.817 101.457 227.603 101.334Z",
    fill: "black"
  }), Object(external_this_wp_element_["createElement"])("path", {
    opacity: "0.1",
    d: "M229.721 99.4493C230.091 98.9811 230.579 98.5815 230.927 98.0913C230.995 97.9933 231.059 97.8903 231.118 97.7874C230.669 98.3585 230.054 98.812 229.721 99.4493Z",
    fill: "black"
  }))), Object(external_this_wp_element_["createElement"])("defs", null, Object(external_this_wp_element_["createElement"])("linearGradient", {
    id: "paint0_linear",
    x1: "211.591",
    y1: "185.849",
    x2: "211.591",
    y2: "86.973",
    gradientUnits: "userSpaceOnUse"
  }, Object(external_this_wp_element_["createElement"])("stop", {
    stopColor: "#808080",
    stopOpacity: "0.25"
  }), Object(external_this_wp_element_["createElement"])("stop", {
    offset: "0.54",
    stopColor: "#808080",
    stopOpacity: "0.12"
  }), Object(external_this_wp_element_["createElement"])("stop", {
    offset: "1",
    stopColor: "#808080",
    stopOpacity: "0.1"
  })), Object(external_this_wp_element_["createElement"])("clipPath", {
    id: "sales-tax-svg"
  }, Object(external_this_wp_element_["createElement"])("path", {
    d: "M0 6C0 2.6863 2.68629 0 6 0H289C292.314 0 295 2.68629 295 6V160H0V6Z",
    fill: "white"
  }))));
});
// CONCATENATED MODULE: ./client/profile-wizard/steps/benefits/images/shipping_labels.js

/* harmony default export */ var shipping_labels = (function () {
  return Object(external_this_wp_element_["createElement"])("svg", {
    width: "295",
    height: "160",
    viewBox: "0 0 295 160",
    fill: "none",
    xmlns: "http://www.w3.org/2000/svg"
  }, Object(external_this_wp_element_["createElement"])("g", {
    clipPath: "url(#shipping-labels-svg)"
  }, Object(external_this_wp_element_["createElement"])("path", {
    d: "M0 6C0 2.6863 2.68629 0 6 0H289C292.314 0 295 2.68629 295 6V160H0V6Z",
    fill: "#F7EDF7"
  }), Object(external_this_wp_element_["createElement"])("path", {
    opacity: "0.05",
    d: "M297.839 152.033C290.282 167.747 274.937 178.255 259.035 185.184C237.627 194.516 214.051 198.218 190.8 196.652C186.725 196.379 182.663 195.942 178.613 195.342C164.546 193.274 150.833 189.248 137.864 183.381C134.121 181.682 130.454 179.827 126.862 177.816C120.864 174.465 115.117 170.676 109.666 166.48C108.295 165.425 106.943 164.343 105.61 163.235C104.686 162.466 103.77 161.681 102.84 160.918C100.878 159.31 98.8582 157.782 96.6056 156.638C95.9259 156.292 95.2292 155.982 94.518 155.709C87.3847 152.974 79.1593 154.006 71.7331 156.139C66.0845 157.762 60.5781 159.983 54.9352 161.569C52.4252 162.296 49.8677 162.844 47.2815 163.209C41.5074 163.946 35.6437 163.426 30.0854 161.686L29.6105 161.537C28.8141 161.285 28.0243 161.009 27.2412 160.709L26.7663 160.525C26.0078 160.231 25.2655 159.915 24.5392 159.576L24.0643 159.358C23.3058 159.003 22.5635 158.627 21.8373 158.232C21.5756 158.094 21.3168 157.945 21.0608 157.805C16.9289 155.478 13.1731 152.531 9.92289 149.063C9.82903 148.969 9.74086 148.871 9.63847 148.776C9.18624 148.283 8.74255 147.782 8.31307 147.268C8.1538 147.079 7.99452 146.89 7.83809 146.695C7.32803 146.068 6.83598 145.427 6.36195 144.771C6.29085 144.677 6.22259 144.579 6.15433 144.485C4.21218 141.776 2.60205 138.841 1.359 135.743C1.31918 135.648 1.28221 135.551 1.24808 135.456C0.868851 134.472 0.522806 133.471 0.209944 132.455C0.0819547 132.03 -0.0403466 131.595 -0.148426 131.17C-0.185401 131.044 -0.219532 130.915 -0.250818 130.789C-1.82082 124.559 -2 118.082 -2 111.643C-2 110.892 -2 110.142 -2 109.393C-1.95449 98.9768 -1.79522 88.3833 -0.270727 78.1109C-0.270727 78.0392 -0.250817 77.9675 -0.239441 77.8987C0.270798 74.4405 0.964835 71.0124 1.83967 67.6291C2.75527 64.0792 3.92542 60.601 5.34088 57.222C8.84778 48.9077 13.9474 41.3417 19.6102 34.3291C33.8739 16.6827 52.5802 1.86901 74.4749 -3.67574C98.0675 -9.64768 124.288 -3.92517 143.319 11.3473C149.121 16.0033 154.334 21.5165 160.85 25.0859C166.539 28.1937 173.197 29.481 179.289 27.0354C184.324 25.0142 187.6 24.0108 193.254 24.3175C204.96 24.985 216.5 27.4215 227.487 31.5452C227.931 31.7086 228.375 31.8778 228.815 32.0469C257.459 43.0934 282.082 65.0403 294.881 93.2428C295.22 93.9882 295.549 94.7394 295.868 95.4963C303.499 113.464 306.257 134.522 297.839 152.033Z",
    fill: "#7F54B3"
  }), Object(external_this_wp_element_["createElement"])("path", {
    d: "M238.595 87.2081H167V163.188H238.595V87.2081Z",
    fill: "white"
  }), Object(external_this_wp_element_["createElement"])("path", {
    d: "M170.312 125.071V90.0034H174.389L194.39 90.6387L193.498 95.848V125.071H170.312Z",
    fill: "#DCDCDE"
  }), Object(external_this_wp_element_["createElement"])("path", {
    opacity: "0.5",
    d: "M190.44 93.561H172.86V94.5775H190.44V93.561Z",
    fill: "#7F54B3"
  }), Object(external_this_wp_element_["createElement"])("path", {
    opacity: "0.5",
    d: "M190.44 98.1351H172.86V99.1516H190.44V98.1351Z",
    fill: "#7F54B3"
  }), Object(external_this_wp_element_["createElement"])("path", {
    opacity: "0.5",
    d: "M190.44 102.709H180.504V103.726H190.44V102.709Z",
    fill: "#7F54B3"
  }), Object(external_this_wp_element_["createElement"])("path", {
    opacity: "0.5",
    d: "M177.701 101.184H172.86V105.25H177.701V101.184Z",
    fill: "#7F54B3"
  }), Object(external_this_wp_element_["createElement"])("path", {
    opacity: "0.5",
    d: "M190.441 117.702H179.994V121.768H190.441V117.702Z",
    fill: "#7F54B3"
  }), Object(external_this_wp_element_["createElement"])("path", {
    opacity: "0.5",
    d: "M190.44 107.283H172.86V108.3H190.44V107.283Z",
    fill: "#7F54B3"
  }), Object(external_this_wp_element_["createElement"])("path", {
    opacity: "0.5",
    d: "M190.44 111.857H172.86V112.874H190.44V111.857Z",
    fill: "#7F54B3"
  }), Object(external_this_wp_element_["createElement"])("path", {
    d: "M209.04 140.572H196.555V162.934H209.04V140.572Z",
    fill: "#DCDCDE"
  }), Object(external_this_wp_element_["createElement"])("path", {
    d: "M112.731 122.276H61.5195V163.442H112.731V122.276Z",
    fill: "white"
  }), Object(external_this_wp_element_["createElement"])("path", {
    d: "M88.7813 151.499H80.1187V163.188H88.7813V151.499Z",
    fill: "#DCDCDE"
  }), Object(external_this_wp_element_["createElement"])("path", {
    d: "M185.577 98.8864C183.646 102.254 170.048 96.6409 170.048 96.6409L144.067 86.2055L137.786 81.5686L133.785 78.6144L142.672 76.1334L142.921 76.2524L149.113 79.2016L172.864 93.3487C172.864 93.3487 190.613 90.1043 185.577 98.8864Z",
    fill: "#A0616A"
  }), Object(external_this_wp_element_["createElement"])("path", {
    d: "M120.088 59.773C120.088 59.773 116.745 61.1143 116.358 64.8721C115.971 68.63 115.939 89.8314 115.939 89.8314C115.939 89.8314 124.674 84.8159 129.371 84.7133C134.068 84.6107 144.979 76.708 144.979 76.708C144.979 76.708 124.421 58.1896 120.088 59.773Z",
    fill: "#67647E"
  }), Object(external_this_wp_element_["createElement"])("path", {
    opacity: "0.1",
    d: "M120.088 59.773C120.088 59.773 116.745 61.1143 116.358 64.8721C115.971 68.63 115.939 89.8314 115.939 89.8314C115.939 89.8314 124.674 84.8159 129.371 84.7133C134.068 84.6107 144.979 76.708 144.979 76.708C144.979 76.708 124.421 58.1896 120.088 59.773Z",
    fill: "black"
  }), Object(external_this_wp_element_["createElement"])("path", {
    d: "M152.86 129.137L145.726 129.645C145.726 129.645 145.507 142.995 144.579 146.417L156.809 156.073L154.261 147.942L152.86 129.137Z",
    fill: "#A0616A"
  }), Object(external_this_wp_element_["createElement"])("path", {
    d: "M157.501 156.35L157.501 156.35L157.501 156.35C156.994 156.171 157.064 156.073 157.064 156.073V155.565L144.599 145.588C144.478 146.035 144.344 146.329 144.197 146.417C143.74 146.69 143.086 147.585 142.435 148.621C141.678 149.834 141.193 151.196 141.013 152.614C140.833 154.032 140.964 155.472 141.394 156.835V156.835L140.885 165.475H142.923L143.433 157.598H145.98C145.98 157.598 150.312 163.188 151.586 165.221C152.86 167.254 158.465 167.762 164.07 163.951C168.807 160.73 160.263 157.328 157.501 156.35Z",
    fill: "#444053"
  }), Object(external_this_wp_element_["createElement"])("path", {
    d: "M90.1827 99.0244C90.1827 99.0244 77.6983 114.017 89.6732 123.165C94.9424 127.191 99.5703 134.808 103.101 142.076C105.871 147.808 109.869 152.863 114.813 156.884C119.757 160.906 125.526 163.795 131.713 165.348C131.713 165.348 174.262 115.796 159.739 101.82C145.216 87.8434 105.779 99.1902 105.779 99.1902L90.1827 99.0244Z",
    fill: "#444053"
  }), Object(external_this_wp_element_["createElement"])("path", {
    d: "M125.529 86.3823C125.585 89.729 124.943 93.4035 122.031 94.1963C116.425 95.721 115.916 94.9587 115.916 94.9587C115.916 94.9587 114.897 96.7375 115.152 98.7704C115.406 100.803 112.731 99.1516 112.731 99.1516C112.731 99.1516 85.5965 102.582 86.6157 98.7704C87.6348 94.9587 97.3166 81.7447 97.3166 81.7447C97.3166 81.7447 113.113 59.6368 116.425 60.3992C116.538 60.4246 116.652 60.4551 116.772 60.4881C120.145 61.469 125.87 65.7965 126.362 67.5144C126.872 69.2931 124.833 80.4742 124.833 80.4742C125.248 82.4178 125.481 84.3957 125.529 86.3823Z",
    fill: "#67647E"
  }), Object(external_this_wp_element_["createElement"])("path", {
    d: "M165.599 117.575C162.287 119.608 160.249 105.123 160.249 105.123L136.299 90.6388L130.852 85.0508L127.381 81.4907L136.554 80.4742L136.78 80.6318L142.414 84.5401L163.561 102.328C163.561 102.328 168.911 115.542 165.599 117.575Z",
    fill: "#A0616A"
  }), Object(external_this_wp_element_["createElement"])("path", {
    opacity: "0.1",
    d: "M124.833 80.4741C125.248 82.4178 125.481 84.3957 125.529 86.3823C123.748 86.705 122.14 86.8219 121.012 86.5729C116.425 85.5564 106.744 88.3517 106.744 88.3517C106.744 88.3517 111.839 67.7684 113.113 64.2108C113.417 63.3632 113.901 62.5914 114.533 61.9487C115.164 61.3059 115.928 60.8076 116.772 60.488C120.145 61.4689 125.87 65.7965 126.362 67.5143C126.872 69.2931 124.833 80.4741 124.833 80.4741Z",
    fill: "black"
  }), Object(external_this_wp_element_["createElement"])("path", {
    d: "M124.717 57.2728C119.214 54.6655 116.866 48.0884 119.472 42.5824C122.077 37.0763 128.651 34.7264 134.154 37.3336C139.657 39.9409 142.006 46.518 139.4 52.024C136.794 57.5301 130.221 59.88 124.717 57.2728Z",
    fill: "#2F2E41"
  }), Object(external_this_wp_element_["createElement"])("path", {
    opacity: "0.1",
    d: "M138.082 82.5072C138.082 82.5072 134.724 83.8743 130.852 85.0508L127.381 81.4907L136.554 80.4742L136.78 80.6318C137.601 81.7982 138.082 82.5072 138.082 82.5072Z",
    fill: "black"
  }), Object(external_this_wp_element_["createElement"])("path", {
    d: "M132.131 54.7977L128.077 71.9742L118.3 62.9339C118.3 62.9339 123.308 56.0031 123.308 54.195C123.308 52.3869 132.131 54.7977 132.131 54.7977Z",
    fill: "#A0616A"
  }), Object(external_this_wp_element_["createElement"])("path", {
    d: "M118.719 58.8744C118.719 58.8744 115.152 59.3827 113.878 62.9403C112.604 66.4979 107.508 87.0811 107.508 87.0811C107.508 87.0811 117.19 84.2859 121.776 85.3023C126.362 86.3188 138.847 81.2365 138.847 81.2365C138.847 81.2365 123.305 58.3662 118.719 58.8744Z",
    fill: "#67647E"
  }), Object(external_this_wp_element_["createElement"])("path", {
    d: "M127.459 59.6811C123.207 57.6664 121.392 52.5841 123.406 48.3295C125.419 44.0748 130.499 42.2589 134.751 44.2736C139.003 46.2883 140.818 51.3706 138.805 55.6253C136.791 59.8799 131.712 61.6958 127.459 59.6811Z",
    fill: "#9F616A"
  }), Object(external_this_wp_element_["createElement"])("path", {
    d: "M122.429 55.079C118.426 53.1828 116.718 48.3995 118.613 44.3951C120.508 40.3907 125.289 38.6816 129.291 40.5778C133.294 42.474 135.002 47.2573 133.107 51.2617C131.211 55.2661 126.431 56.9752 122.429 55.079Z",
    fill: "#2F2E41"
  }), Object(external_this_wp_element_["createElement"])("path", {
    d: "M117.254 40.5669C113.557 38.8153 111.979 34.3967 113.729 30.6977C115.48 26.9987 119.896 25.4199 123.593 27.1715C127.29 28.9231 128.868 33.3417 127.117 37.0407C125.367 40.7397 120.951 42.3185 117.254 40.5669Z",
    fill: "#2F2E41"
  }), Object(external_this_wp_element_["createElement"])("path", {
    d: "M112.267 36.8522C112.843 37.8846 113.659 38.7679 114.645 39.4279C115.632 40.088 116.762 40.5057 117.939 40.6462C119.116 40.7866 120.308 40.6457 121.413 40.2351C122.518 39.8246 123.506 39.1564 124.292 38.2865C123.746 39.1413 123.025 39.8724 122.177 40.4339C121.328 40.9954 120.369 41.375 119.361 41.5487C118.352 41.7224 117.317 41.6865 116.318 41.4432C115.32 41.1999 114.382 40.7546 113.562 40.1353C112.742 39.5159 112.059 38.7361 111.555 37.845C111.052 36.9538 110.739 35.9708 110.636 34.9579C110.534 33.945 110.644 32.9243 110.959 31.9601C111.275 30.996 111.79 30.1093 112.471 29.3562C111.761 30.4654 111.368 31.7467 111.332 33.0656C111.297 34.3845 111.619 35.6924 112.267 36.8522Z",
    fill: "#2F2E41"
  }), Object(external_this_wp_element_["createElement"])("path", {
    d: "M130.914 53.5515C127.412 51.8923 125.63 48.3155 126.933 45.5625C128.236 42.8095 132.131 41.9227 135.633 43.5819C139.135 45.241 140.917 48.8178 139.614 51.5708C138.311 54.3239 134.416 55.2106 130.914 53.5515Z",
    fill: "#2F2E41"
  }), Object(external_this_wp_element_["createElement"])("path", {
    d: "M131.416 56.008C130.665 55.6525 130.537 54.3498 131.129 53.0984C131.721 51.8471 132.81 51.1208 133.56 51.4764C134.311 51.8319 134.439 53.1346 133.847 54.3859C133.254 55.6373 132.166 56.3635 131.416 56.008Z",
    fill: "#A0616A"
  })), Object(external_this_wp_element_["createElement"])("defs", null, Object(external_this_wp_element_["createElement"])("clipPath", {
    id: "shipping-labels-svg"
  }, Object(external_this_wp_element_["createElement"])("path", {
    d: "M0 6C0 2.6863 2.68629 0 6 0H289C292.314 0 295 2.68629 295 6V160H0V6Z",
    fill: "white"
  }))));
});
// CONCATENATED MODULE: ./client/profile-wizard/steps/benefits/images/speed.js

/* harmony default export */ var speed = (function () {
  return Object(external_this_wp_element_["createElement"])("svg", {
    width: "295",
    height: "160",
    viewBox: "0 0 295 160",
    fill: "none",
    xmlns: "http://www.w3.org/2000/svg"
  }, Object(external_this_wp_element_["createElement"])("g", {
    clipPath: "url(#speed-svg)"
  }, Object(external_this_wp_element_["createElement"])("path", {
    d: "M0 6C0 2.6863 2.68629 0 6 0H289C292.314 0 295 2.68629 295 6V160H0V6Z",
    fill: "#F7EDF7"
  }), Object(external_this_wp_element_["createElement"])("path", {
    opacity: "0.05",
    d: "M287.559 129.935C280.58 144.446 266.411 154.149 251.726 160.548C231.957 169.166 210.186 172.584 188.715 171.138C184.952 170.886 181.2 170.482 177.46 169.928C164.471 168.018 151.807 164.301 139.831 158.883C136.374 157.314 132.988 155.601 129.672 153.744C124.133 150.649 118.825 147.151 113.792 143.276C112.526 142.302 111.278 141.303 110.047 140.279C109.193 139.569 108.347 138.844 107.489 138.14C105.676 136.654 103.812 135.243 101.731 134.187C101.104 133.868 100.46 133.582 99.8036 133.329C93.2164 130.803 85.6207 131.757 78.763 133.726C73.5468 135.225 68.462 137.277 63.2511 138.741C60.9333 139.412 58.5715 139.918 56.1833 140.255C50.8512 140.935 45.4365 140.456 40.3037 138.849L39.8651 138.712C39.1297 138.479 38.4004 138.224 37.6772 137.946L37.2386 137.777C36.5382 137.505 35.8527 137.213 35.1821 136.901L34.7435 136.699C34.0431 136.371 33.3576 136.024 32.687 135.659C32.4453 135.532 32.2063 135.394 31.9699 135.264C28.1544 133.116 24.6861 130.394 21.6847 127.192C21.598 127.105 21.5166 127.015 21.422 126.927C21.0044 126.472 20.5947 126.009 20.1981 125.535C20.051 125.36 19.904 125.185 19.7595 125.005C19.2885 124.426 18.8341 123.834 18.3964 123.229C18.3307 123.142 18.2677 123.052 18.2046 122.964C16.4112 120.463 14.9243 117.753 13.7764 114.892C13.7396 114.805 13.7055 114.715 13.674 114.627C13.3238 113.718 13.0042 112.794 12.7153 111.855C12.5971 111.463 12.4842 111.061 12.3844 110.669C12.3502 110.553 12.3187 110.434 12.2898 110.317C10.84 104.564 10.6746 98.5833 10.6746 92.637C10.6746 91.9433 10.6746 91.2506 10.6746 90.5587C10.7166 80.9403 10.8637 71.1578 12.2714 61.6718C12.2714 61.6056 12.2898 61.5394 12.3003 61.4758C12.7715 58.2824 13.4124 55.1167 14.2203 51.9925C15.0658 48.7143 16.1464 45.5024 17.4535 42.382C20.6919 34.7042 25.4011 27.7175 30.6304 21.2417C43.8021 4.94627 61.0764 -8.7334 81.2949 -13.8537C103.081 -19.3684 127.295 -14.084 144.868 0.0192566C150.226 4.31881 155.041 9.40996 161.058 12.7061C166.311 15.576 172.459 16.7647 178.085 14.5064C182.734 12.6399 185.76 11.7133 190.981 11.9966C201.791 12.613 212.447 14.8629 222.593 18.6709C223.003 18.8218 223.413 18.9781 223.82 19.1343C250.271 29.3351 273.008 49.6018 284.827 75.6453C285.141 76.3336 285.445 77.0273 285.739 77.7262C292.786 94.3181 295.333 113.764 287.559 129.935Z",
    fill: "#7F54B3"
  }), Object(external_this_wp_element_["createElement"])("path", {
    d: "M245.704 26.7351H50.4275C48.9672 26.7351 47.7834 27.9277 47.7834 29.3988V136.883C47.7834 138.354 48.9672 139.546 50.4275 139.546H245.704C247.165 139.546 248.349 138.354 248.349 136.883V29.3988C248.349 27.9277 247.165 26.7351 245.704 26.7351Z",
    fill: "white"
  }), Object(external_this_wp_element_["createElement"])("path", {
    d: "M245.704 26.7351H50.4275C48.9672 26.7351 47.7834 27.9277 47.7834 29.3988V136.883C47.7834 138.354 48.9672 139.546 50.4275 139.546H245.704C247.165 139.546 248.349 138.354 248.349 136.883V29.3988C248.349 27.9277 247.165 26.7351 245.704 26.7351Z",
    fill: "white"
  }), Object(external_this_wp_element_["createElement"])("path", {
    opacity: "0.04",
    d: "M245.704 26.7351H50.4275C48.9672 26.7351 47.7834 27.9277 47.7834 29.3988V136.883C47.7834 138.354 48.9672 139.546 50.4275 139.546H245.704C247.165 139.546 248.349 138.354 248.349 136.883V29.3988C248.349 27.9277 247.165 26.7351 245.704 26.7351Z",
    fill: "white"
  }), Object(external_this_wp_element_["createElement"])("path", {
    d: "M247.33 25.4404H48.8017C48.5316 25.4404 48.2727 25.5485 48.0817 25.7409C47.8907 25.9333 47.7834 26.1942 47.7834 26.4662V30.8019H248.34V26.4662C248.34 26.1957 248.234 25.936 248.045 25.7439C247.856 25.5517 247.599 25.4427 247.33 25.4404Z",
    fill: "#5A5773"
  }), Object(external_this_wp_element_["createElement"])("path", {
    d: "M51.7886 29.5076C52.5486 29.5076 53.1646 28.8869 53.1646 28.1213C53.1646 27.3557 52.5486 26.7351 51.7886 26.7351C51.0287 26.7351 50.4126 27.3557 50.4126 28.1213C50.4126 28.8869 51.0287 29.5076 51.7886 29.5076Z",
    fill: "white"
  }), Object(external_this_wp_element_["createElement"])("path", {
    d: "M55.4935 29.5073C56.2534 29.5073 56.8695 28.8867 56.8695 28.1211C56.8695 27.3555 56.2534 26.7349 55.4935 26.7349C54.7335 26.7349 54.1174 27.3555 54.1174 28.1211C54.1174 28.8867 54.7335 29.5073 55.4935 29.5073Z",
    fill: "white"
  }), Object(external_this_wp_element_["createElement"])("path", {
    d: "M59.198 29.5073C59.958 29.5073 60.5741 28.8867 60.5741 28.1211C60.5741 27.3555 59.958 26.7349 59.198 26.7349C58.4381 26.7349 57.822 27.3555 57.822 28.1211C57.822 28.8867 58.4381 29.5073 59.198 29.5073Z",
    fill: "white"
  }), Object(external_this_wp_element_["createElement"])("path", {
    opacity: "0.2",
    d: "M118.126 40.8855H64.3022V45.4878H118.126V40.8855Z",
    fill: "#646970"
  }), Object(external_this_wp_element_["createElement"])("path", {
    opacity: "0.1",
    d: "M231.83 59.3391H64.3022V130.135H231.83V59.3391Z",
    fill: "#5A5773"
  }), Object(external_this_wp_element_["createElement"])("path", {
    opacity: "0.1",
    d: "M162.391 63.9563H159.523V58.3431C159.523 52.0091 154.51 46.7436 148.223 46.6476C146.706 46.6316 145.202 46.9179 143.796 47.4902C142.391 48.0625 141.111 48.9094 140.032 49.9821C138.953 51.0548 138.095 52.3321 137.507 53.7404C136.92 55.1486 136.616 56.66 136.611 58.1874V63.9563H133.749C132.484 63.9602 131.272 64.4682 130.378 65.3692C129.483 66.2702 128.979 67.4912 128.975 68.7654V91.8451C128.979 93.1194 129.483 94.3403 130.378 95.2413C131.272 96.1424 132.484 96.6503 133.749 96.6542H162.391C163.656 96.6503 164.868 96.1423 165.762 95.2412C166.656 94.3401 167.16 93.1192 167.163 91.8451V68.7654C167.16 67.4914 166.656 66.2704 165.762 65.3693C164.868 64.4682 163.656 63.9602 162.391 63.9563ZM149.742 78.9596V87.2257C149.745 87.6634 149.583 88.0858 149.288 88.4074C148.993 88.7289 148.588 88.9256 148.155 88.9575C147.929 88.9681 147.703 88.9325 147.49 88.8526C147.278 88.7728 147.084 88.6504 146.921 88.4929C146.757 88.3354 146.626 88.1461 146.537 87.9363C146.448 87.7266 146.402 87.5008 146.402 87.2727V78.9553C145.627 78.5748 145.004 77.9411 144.633 77.1577C144.262 76.3742 144.166 75.4873 144.359 74.6415C144.553 73.7957 145.026 73.0411 145.7 72.5007C146.374 71.9603 147.211 71.6662 148.072 71.6662C148.934 71.6662 149.77 71.9603 150.444 72.5007C151.119 73.0411 151.591 73.7957 151.785 74.6415C151.979 75.4873 151.882 76.3742 151.511 77.1577C151.141 77.9411 150.517 78.5748 149.742 78.9553V78.9596ZM156.187 63.9563H139.952V58.1874C139.952 56.0194 140.806 53.9402 142.328 52.4072C143.85 50.8742 145.914 50.013 148.066 50.013C150.218 50.013 152.282 50.8742 153.804 52.4072C155.325 53.9402 156.18 56.0194 156.18 58.1874L156.187 63.9563Z",
    fill: "black"
  }), Object(external_this_wp_element_["createElement"])("path", {
    d: "M162.391 63.5298H159.523V57.9166C159.523 51.5826 154.51 46.3171 148.223 46.2211C146.706 46.2051 145.202 46.4914 143.796 47.0637C142.391 47.636 141.111 48.4829 140.032 49.5556C138.953 50.6283 138.095 51.9056 137.507 53.3138C136.92 54.7221 136.616 56.2335 136.611 57.7609V63.5298H133.749C132.484 63.5337 131.272 64.0416 130.378 64.9427C129.483 65.8437 128.979 67.0647 128.975 68.3389V91.4186C128.979 92.6928 129.483 93.9137 130.378 94.8148C131.272 95.7158 132.484 96.2238 133.749 96.2277H162.391C163.656 96.2238 164.868 95.7158 165.762 94.8147C166.656 93.9136 167.16 92.6926 167.163 91.4186V68.3389C167.16 67.0649 166.656 65.8439 165.762 64.9428C164.868 64.0417 163.656 63.5337 162.391 63.5298ZM149.742 78.533V86.7992C149.745 87.2369 149.583 87.6593 149.288 87.9808C148.993 88.3024 148.588 88.499 148.155 88.5309C147.929 88.5416 147.703 88.5059 147.49 88.4261C147.278 88.3462 147.084 88.2239 146.921 88.0664C146.757 87.9089 146.626 87.7195 146.537 87.5098C146.448 87.3001 146.402 87.0743 146.402 86.8461V78.5288C145.627 78.1483 145.004 77.5146 144.633 76.7312C144.262 75.9477 144.166 75.0607 144.359 74.2149C144.553 73.3692 145.026 72.6145 145.7 72.0742C146.374 71.5338 147.211 71.2396 148.072 71.2396C148.934 71.2396 149.77 71.5338 150.444 72.0742C151.119 72.6145 151.591 73.3692 151.785 74.2149C151.979 75.0607 151.882 75.9477 151.511 76.7312C151.141 77.5146 150.517 78.1483 149.742 78.5288V78.533ZM156.187 63.5298H139.952V57.7609C139.952 55.5929 140.806 53.5137 142.328 51.9807C143.85 50.4477 145.914 49.5864 148.066 49.5864C150.218 49.5864 152.282 50.4477 153.804 51.9807C155.325 53.5137 156.18 55.5929 156.18 57.7609L156.187 63.5298Z",
    fill: "#444053"
  }), Object(external_this_wp_element_["createElement"])("path", {
    opacity: "0.1",
    d: "M231.83 52.5251H214.873V56.558H231.83V52.5251Z",
    fill: "#5A5773"
  }), Object(external_this_wp_element_["createElement"])("path", {
    d: "M237.601 174.735C237.489 174.17 237.298 173.623 237.034 173.112L234.493 169.291H234.508C234.508 169.291 234.108 167.371 234.508 166.429C234.908 165.486 233.081 163.168 233.081 163.168C233.081 163.168 232.859 162.586 233.439 161.869C234.019 161.152 233.081 159.054 233.081 159.054C233.081 159.054 232.637 154.671 232.592 154.002C232.548 153.332 232.19 149.621 232.19 149.621L231.343 141.754L229.836 120.903L229.809 120.512C229.995 120.421 230.094 120.363 230.094 120.363L229.794 108.834L230.054 108.597C232.328 106.524 237.572 101.726 237.519 101.621C237.466 101.517 236.884 93.6089 236.816 91.6298C236.758 90.2233 236.452 88.8384 235.914 87.5393C235.783 87.277 235.404 85.5474 235.247 84.8159C235.205 84.6155 235.177 84.4896 235.177 84.4896C235.177 84.4896 233.64 81.538 233.774 80.1966C233.907 78.8552 234.174 77.8507 230.897 77.3815C230.897 77.3815 228.145 76.9677 226.506 75.8396C225.537 75.1638 224.478 74.6296 223.36 74.2529C223.36 74.2529 222.442 72.5467 221.802 73.1247C221.532 72.5159 221.329 71.8786 221.199 71.2245L221.18 71.1051C222.394 70.2526 223.267 68.9933 223.644 67.5521C223.665 67.471 223.682 67.3878 223.701 67.3068C223.765 67.3285 223.831 67.3407 223.898 67.3431C224.455 67.3431 224.798 66.7182 225.319 66.5198C225.53 66.4367 225.791 66.4239 225.954 66.2554C226.007 66.1904 226.047 66.1152 226.071 66.0342C226.095 65.9532 226.103 65.8681 226.093 65.7841C226.099 65.7259 226.099 65.6674 226.093 65.6092C226.094 65.5886 226.094 65.568 226.093 65.5474V65.5687C225.952 64.7924 225.355 64.1035 225.42 63.3145C225.484 62.5723 226.131 61.9197 226.079 61.1967C226.078 61.1396 226.073 61.0825 226.064 61.0261V61.0517C225.985 60.7694 225.84 60.5105 225.64 60.2967C225.412 59.984 225.113 59.7309 224.768 59.5588C224.474 59.4287 224.133 59.3989 223.858 59.2432C223.049 58.7996 223.051 57.6437 222.571 56.8546C222.365 56.5252 222.073 56.2595 221.726 56.0868C221.38 55.914 220.993 55.841 220.608 55.8757C220.007 55.9355 219.463 56.2575 218.868 56.362C218.055 56.507 217.228 56.2404 216.457 55.9355C215.686 55.6305 214.905 55.2957 214.078 55.2957C213.25 55.2957 212.354 55.7222 212.105 56.5091C211.971 56.9357 212.037 57.3942 211.935 57.8271C211.753 58.5906 211.088 59.1366 210.4 59.5162C209.977 59.7529 209.473 60.0238 209.342 60.4716C209.342 60.4588 209.342 60.4482 209.342 60.4375C209.332 60.4732 209.325 60.5096 209.321 60.5463C209.316 60.5938 209.316 60.6416 209.321 60.6892C209.321 60.9963 209.482 61.2884 209.59 61.5806C209.791 62.1365 209.786 62.7468 209.577 63.2995C209.42 63.7047 209.153 64.0609 209.001 64.464C208.929 64.6517 208.882 64.8482 208.861 65.0483V65.0142C208.856 65.1051 208.856 65.1963 208.861 65.2872C208.859 65.5986 208.931 65.906 209.073 66.1829C209.17 66.3741 209.304 66.5437 209.468 66.6818C209.631 66.8199 209.82 66.9236 210.024 66.9869C210.447 67.085 210.712 66.667 211.158 66.7736C211.238 66.793 211.317 66.818 211.393 66.8483C211.408 66.9442 211.423 67.0402 211.44 67.1341C211.71 68.5694 212.469 69.8645 213.586 70.7958C213.586 70.847 213.603 70.8982 213.61 70.9515C213.745 71.8018 213.804 72.6626 213.785 73.5235C213.785 73.5235 213.119 72.5403 212.204 74.4384C212.204 74.4384 209.818 75.8246 208.995 75.9825C208.171 76.1403 205.248 77.1661 205.248 77.1661C204.807 77.4873 204.276 77.6598 203.732 77.6587C202.82 77.6587 202.062 79.6698 202.172 80.7639C202.282 81.8579 202.106 82.6641 201.257 82.7089C200.408 82.7537 200.872 84.4875 200.878 84.5174L200.8 84.8906C200.635 85.6541 200.275 87.292 200.148 87.5479C199.61 88.847 199.304 90.2318 199.244 91.6383C199.178 93.6174 198.609 101.495 198.543 101.63C198.499 101.717 202.091 105.025 204.617 107.337L204.983 107.67L203.821 118.606C203.855 119.027 203.979 119.436 204.185 119.804C204.391 120.173 204.674 120.491 205.015 120.738C205.015 120.866 205.015 120.997 205.002 121.131C204.951 122.41 204.85 123.959 204.646 124.364C204.291 125.081 204.38 128.924 204.38 128.924C204.38 128.924 204.022 136.746 203.577 137.597C203.133 138.448 203.042 140.233 203.175 140.583C203.309 140.933 202.728 150.327 202.728 150.327L202.284 154.53C202.284 154.53 202.417 158.553 202.015 158.642C201.613 158.732 201.391 161.504 201.391 161.504C201.391 161.504 199.966 163.471 200.588 164.185C201.211 164.899 200.497 165.215 200.432 165.392C200.366 165.569 199.073 166.245 200.008 168.745C200.017 168.769 200.027 168.794 200.038 168.817C199.161 170.043 196.384 174 196.553 174.601C196.678 174.984 196.943 175.304 197.294 175.497C197.948 175.891 199.07 176.075 200.855 175.497C203.819 174.535 203.607 173.842 203.607 173.842C203.622 173.518 203.76 173.212 203.993 172.987C204.225 172.763 204.534 172.636 204.856 172.635C206.082 172.545 206.996 171.406 206.996 171.406L206.852 170.867L206.655 170.12C206.679 169.907 206.829 169.534 207.502 169.327C208.529 169.013 208.26 167.047 208.26 167.047C208.26 167.047 208.171 164.991 208.616 164.722C209.06 164.454 208.885 161.056 208.885 161.056C208.885 161.056 209.376 158.597 210.087 156.943C210.798 155.288 211.649 145.456 211.649 145.456C211.649 145.456 212.227 140.628 212.763 139.241C213.298 137.855 213.79 133.967 213.79 133.967C213.79 133.967 217.09 127.529 217.132 126.098C217.132 126.098 217.556 124.756 217.979 126.232C218.402 127.708 219.592 131.24 219.592 131.24L220.75 135.575L222.133 142.057L223.026 147.512C223.026 147.512 222.89 149.568 223.238 150.284C223.585 151.001 224.084 152.652 223.818 152.965C223.551 153.279 224.351 154.754 224.576 155.066C224.8 155.377 224.576 161.995 224.576 161.995C224.576 161.995 225.6 167.627 227.071 168.606C227.174 168.674 227.283 168.731 227.397 168.777C227.376 169.387 227.355 170.203 227.383 170.63C227.379 170.725 227.395 170.82 227.429 170.909C227.563 171.088 229.078 172.296 229.392 172.296C229.705 172.296 230.816 173.078 230.863 173.458C230.909 173.837 234.121 175.445 234.576 175.492C235.031 175.539 237.237 175.938 237.54 175.313C237.615 175.13 237.636 174.93 237.601 174.735ZM229.711 94.1805L229.94 94.3788C229.94 94.3788 230.175 96.2235 230.342 96.7759C230.509 97.3283 230.308 98.4031 230.308 98.4031C230.324 98.8043 230.403 99.2003 230.543 99.5761C230.742 99.9792 230.141 100.856 230.141 100.856H229.605L229.438 93.9501L229.711 94.1805ZM213.785 73.5619V73.5214C213.781 73.5342 213.779 73.5491 213.779 73.5619H213.785ZM205.561 100.146C205.479 99.9669 205.466 99.7634 205.527 99.5761C205.575 99.4748 205.612 99.3691 205.639 99.2605C205.705 98.9803 205.746 98.6948 205.762 98.4074C205.762 98.4038 205.762 98.4003 205.762 98.3967C205.743 98.2944 205.572 97.3048 205.73 96.7802C205.889 96.2555 206.13 94.3831 206.13 94.3831L206.283 94.253L206.636 93.9437C206.636 93.9437 206.636 93.8286 206.662 93.6388C206.736 95.2596 206.795 97.051 206.7 97.1406C206.522 97.3176 205.874 99.06 206.031 99.1282C206.082 99.1517 206.012 99.8491 205.893 100.787C205.76 100.586 205.649 100.371 205.561 100.146Z",
    fill: "url(#paint0_linear)"
  }), Object(external_this_wp_element_["createElement"])("path", {
    d: "M222.623 75.2659V77.9189L217.221 80.6551C217.221 80.6551 211.857 77.3857 213.18 76.0848C214.026 75.2616 213.863 73.001 213.635 71.4463C213.501 70.542 213.347 69.8787 213.347 69.8787C213.347 69.8787 222.983 67.1063 221.527 69.2112C221.04 69.915 221.002 70.8256 221.165 71.7278C221.495 73.5171 222.623 75.2659 222.623 75.2659Z",
    fill: "#CC818C"
  }), Object(external_this_wp_element_["createElement"])("path", {
    d: "M207.824 101.21L207.127 109.851C207.127 109.851 206.083 108.898 204.733 107.657C202.228 105.356 198.672 102.065 198.714 101.977C198.78 101.843 199.349 94.0034 199.411 92.0328C199.469 90.6341 199.771 89.2566 200.304 87.9637C200.431 87.7078 200.789 86.0785 200.939 85.3171C200.99 85.0804 201.022 84.9268 201.022 84.9268L202.19 84.4534C202.19 84.4534 207.059 89.9258 207.059 90.026C207.059 90.1262 206.727 94.3297 206.727 94.3297L206.382 94.6326L206.232 94.7626C206.232 94.7626 205.999 96.5989 205.834 97.1491C205.668 97.6993 205.846 98.6548 205.865 98.7571C205.85 99.0445 205.809 99.3299 205.745 99.6102C205.717 99.718 205.68 99.8229 205.632 99.9237C205.573 100.11 205.585 100.313 205.666 100.491C205.76 100.738 205.883 100.974 206.03 101.193L207.824 101.21Z",
    fill: "#444053"
  }), Object(external_this_wp_element_["createElement"])("path", {
    d: "M237.332 101.977C237.383 102.082 232.19 106.857 229.94 108.919L228.919 109.851L228.229 101.209H230.018C230.018 101.209 230.615 100.341 230.416 99.9405C230.277 99.5666 230.199 99.1727 230.185 98.7739C230.185 98.7739 230.382 97.7076 230.217 97.1552C230.052 96.6028 229.819 94.7688 229.819 94.7688L229.593 94.5726L229.324 94.3358C229.324 94.3358 228.991 90.1324 228.991 90.0321C228.991 89.9319 233.86 84.4595 233.86 84.4595L235.02 84.9265C235.02 84.9265 235.046 85.0524 235.088 85.2528C235.245 85.9801 235.62 87.7011 235.749 87.9634C236.282 89.2563 236.584 90.6338 236.642 92.0326C236.697 94.0031 237.264 101.843 237.332 101.977Z",
    fill: "#444053"
  }), Object(external_this_wp_element_["createElement"])("path", {
    d: "M234.349 166.476C233.951 167.41 234.349 169.323 234.349 169.323C234.349 169.323 228.422 169.634 226.974 168.655C225.526 167.676 224.501 162.069 224.501 162.069C224.501 162.069 224.713 155.486 224.501 155.175C224.29 154.863 223.485 153.396 223.75 153.085C224.014 152.773 223.538 151.127 223.176 150.414C222.814 149.702 222.964 147.657 222.964 147.657L222.08 142.227L220.712 135.778L219.55 131.461C219.55 131.461 218.358 127.947 217.954 126.48C217.549 125.012 217.122 126.345 217.122 126.345C217.079 127.77 213.811 134.176 213.811 134.176C213.811 134.176 213.324 138.047 212.795 139.425C212.265 140.803 211.689 145.61 211.689 145.61C211.689 145.61 210.843 155.396 210.144 157.043C209.445 158.689 208.965 161.135 208.965 161.135C208.965 161.135 209.143 164.518 208.7 164.784C208.258 165.051 208.33 167.098 208.33 167.098C208.33 167.098 208.594 169.054 207.578 169.367C206.562 169.681 206.732 170.346 206.732 170.346C206.732 170.346 201.079 171.28 200.152 168.787C199.225 166.294 200.506 165.631 200.575 165.452C200.645 165.273 201.35 164.963 200.732 164.249C200.114 163.535 201.526 161.581 201.526 161.581C201.526 161.581 201.748 158.824 202.144 158.734C202.54 158.644 202.411 154.641 202.411 154.641L202.851 150.459C202.851 150.459 203.427 141.116 203.294 140.76C203.16 140.404 203.249 138.627 203.692 137.774C204.134 136.921 204.485 129.141 204.485 129.141C204.485 129.141 204.397 125.317 204.75 124.605C204.962 124.202 205.051 122.651 205.101 121.387C205.14 120.421 205.148 119.621 205.148 119.621L210.093 117.753L216.444 115.708L221.787 118.288L229.605 119.8L229.703 121.159L231.195 141.912L232.042 149.741C232.042 149.741 232.395 153.432 232.44 154.1C232.484 154.767 232.925 159.129 232.925 159.129C232.925 159.129 233.852 161.219 233.278 161.931C232.705 162.643 232.925 163.21 232.925 163.21C232.925 163.21 234.747 165.541 234.349 166.476Z",
    fill: "#444053"
  }), Object(external_this_wp_element_["createElement"])("path", {
    opacity: "0.1",
    d: "M221.531 69.2109C221.044 69.9147 221.006 70.8254 221.169 71.7275C220.057 72.5181 218.717 72.9186 217.356 72.8674C215.996 72.8161 214.69 72.316 213.639 71.4438C213.506 70.5396 213.351 69.8763 213.351 69.8763C213.351 69.8763 222.987 67.1081 221.531 69.2109Z",
    fill: "black"
  }), Object(external_this_wp_element_["createElement"])("path", {
    d: "M223.783 66.4897C223.783 67.7256 223.42 68.9337 222.738 69.9613C222.056 70.9889 221.088 71.7898 219.954 72.2628C218.821 72.7357 217.574 72.8595 216.371 72.6184C215.167 72.3773 214.062 71.7821 213.195 70.9082C212.327 70.0343 211.736 68.9209 211.497 67.7088C211.258 66.4967 211.381 65.2403 211.85 64.0985C212.32 62.9567 213.115 61.9807 214.135 61.2941C215.155 60.6075 216.354 60.241 217.581 60.241C218.396 60.2371 219.205 60.396 219.959 60.7086C220.713 61.0213 221.399 61.4814 221.975 62.0624C222.552 62.6435 223.009 63.3339 223.319 64.0938C223.63 64.8537 223.787 65.668 223.783 66.4897Z",
    fill: "#CC818C"
  }), Object(external_this_wp_element_["createElement"])("path", {
    opacity: "0.1",
    d: "M225.075 76.8738L223.043 113.332L212.003 110.996L209.816 96.3812L211.982 76.7607L213.612 76.9719L216.487 77.3408L219.334 77.5178L222.421 77.1723L225.075 76.8738Z",
    fill: "black"
  }), Object(external_this_wp_element_["createElement"])("path", {
    d: "M225.075 77.1404L223.043 113.598L212.003 111.263L209.816 96.6479L211.982 77.0295L213.612 77.2385L216.487 77.6075L219.334 77.7845L222.421 77.439L225.075 77.1404Z",
    fill: "white"
  }), Object(external_this_wp_element_["createElement"])("path", {
    d: "M216 78.1191L219.975 78.2535L218.858 79.8295C218.858 79.8295 218.936 83.0136 219.467 83.9264C219.999 84.8391 219.842 98.9403 219.842 98.9403L218.784 100.964L216.974 98.695L216.997 80.0769L216 78.1191Z",
    fill: "#7F54B3"
  }), Object(external_this_wp_element_["createElement"])("path", {
    opacity: "0.1",
    d: "M216 78.1191L219.975 78.2535L218.858 79.8295C218.858 79.8295 218.936 83.0136 219.467 83.9264C219.999 84.8391 219.842 98.9403 219.842 98.9403L218.784 100.964L216.974 98.695L216.997 80.0769L216 78.1191Z",
    fill: "black"
  }), Object(external_this_wp_element_["createElement"])("path", {
    opacity: "0.1",
    d: "M222.225 73.9817L218.517 78.1191L220.636 80.3434L224.148 76.8949L222.225 73.9817Z",
    fill: "black"
  }), Object(external_this_wp_element_["createElement"])("path", {
    d: "M222.225 73.7151L218.517 77.8524L220.636 80.0768L224.148 76.6283L222.225 73.7151Z",
    fill: "white"
  }), Object(external_this_wp_element_["createElement"])("path", {
    opacity: "0.1",
    d: "M216.95 77.9187L213.194 74.1375L212.841 76.1166L214.564 80.4544L216.95 77.9187Z",
    fill: "black"
  }), Object(external_this_wp_element_["createElement"])("path", {
    d: "M217.082 77.652L213.328 73.8708L212.975 75.85L214.698 80.1878L217.082 77.652Z",
    fill: "white"
  }), Object(external_this_wp_element_["createElement"])("path", {
    opacity: "0.1",
    d: "M229.722 121.165C227.919 122.03 225.988 122.592 224.006 122.83C220.363 123.297 217.979 118.627 217.979 118.627L216.588 120.45C216.146 122.387 209.412 122.875 206.674 122.141C206.106 122 205.572 121.746 205.104 121.393C205.142 120.427 205.15 119.627 205.15 119.627L210.095 117.759L216.446 115.714L221.789 118.294L229.607 119.806L229.722 121.165Z",
    fill: "black"
  }), Object(external_this_wp_element_["createElement"])("path", {
    opacity: "0.1",
    d: "M225.075 77.1426L223.045 113.598L212.003 111.263L209.812 96.65L211.975 77.0295L213.605 77.2428C213.564 77.9575 213.484 78.6694 213.366 79.3755C213.102 80.6231 214.736 89.4757 214.736 89.4757C214.736 89.4757 217.841 99.4033 219.239 97.9723C220.636 96.5413 222.626 88.9852 222.626 88.9852C223.974 86.1829 222.649 78.3219 222.55 78.1641C222.48 77.9316 222.438 77.6919 222.423 77.4497L225.075 77.1426Z",
    fill: "black"
  }), Object(external_this_wp_element_["createElement"])("path", {
    opacity: "0.1",
    d: "M229.845 105.353L229.938 108.925L228.917 109.857L228.229 101.209H229.753L229.845 105.353Z",
    fill: "black"
  }), Object(external_this_wp_element_["createElement"])("path", {
    opacity: "0.1",
    d: "M205.868 98.7637C205.852 99.051 205.812 99.3364 205.747 99.6167C205.747 99.5378 205.747 99.493 205.73 99.4866C205.666 99.4695 205.743 99.1433 205.868 98.7637Z",
    fill: "black"
  }), Object(external_this_wp_element_["createElement"])("path", {
    opacity: "0.1",
    d: "M207.824 101.21L207.127 109.851C207.127 109.851 206.084 108.898 204.733 107.657L204.894 106.125C204.894 106.125 205.464 102.363 205.671 100.508C205.765 100.755 205.887 100.991 206.035 101.21H207.824Z",
    fill: "black"
  }), Object(external_this_wp_element_["createElement"])("path", {
    d: "M213.813 74.0032C213.813 74.0032 213.152 73.0243 212.246 74.9139C212.246 74.9139 209.884 76.2937 209.071 76.4494C208.258 76.6051 205.366 77.6288 205.366 77.6288C204.929 77.9468 204.404 78.1177 203.865 78.1171C202.959 78.1171 202.208 80.1197 202.318 81.2095C202.428 82.2993 202.252 83.1012 201.414 83.1459C200.575 83.1907 201.037 84.9459 201.037 84.9459L206.624 91.3972C206.624 91.3972 206.977 97.3367 206.801 97.5158C206.626 97.6949 205.984 99.4352 206.139 99.4949C206.293 99.5546 205.292 106.123 205.292 106.123L203.946 118.87C203.946 118.87 203.946 121.003 206.685 121.739C209.424 122.474 216.163 121.988 216.597 120.052L217.987 118.226C217.987 118.226 220.371 122.899 224.014 122.432C227.658 121.965 229.978 120.63 229.978 120.63L229.58 105.353L229.315 94.3424L235.004 84.9267C235.004 84.9267 233.479 81.99 233.613 80.6614C233.746 79.3327 234.009 78.3261 230.763 77.8591C230.763 77.8591 228.026 77.4475 226.413 76.3236C225.454 75.653 224.407 75.1217 223.301 74.7454C223.301 74.7454 222.391 73.0393 221.758 73.6236C221.758 73.6236 222.393 76.0357 222.34 76.3961C222.287 76.7565 222.439 78.3858 222.539 78.5543C222.638 78.7228 223.964 86.5731 222.615 89.3754C222.615 89.3754 220.636 96.9315 219.228 98.3625C217.82 99.7935 214.725 89.866 214.725 89.866C214.725 89.866 213.091 81.0112 213.355 79.7657C213.62 78.5202 213.813 74.0032 213.813 74.0032Z",
    fill: "#444053"
  }), Object(external_this_wp_element_["createElement"])("path", {
    d: "M218.538 103.167C219.074 103.167 219.509 102.729 219.509 102.188C219.509 101.648 219.074 101.209 218.538 101.209C218.001 101.209 217.566 101.648 217.566 102.188C217.566 102.729 218.001 103.167 218.538 103.167Z",
    fill: "#50575E"
  }), Object(external_this_wp_element_["createElement"])("path", {
    d: "M218.538 107.782C219.074 107.782 219.509 107.344 219.509 106.804C219.509 106.263 219.074 105.825 218.538 105.825C218.001 105.825 217.566 106.263 217.566 106.804C217.566 107.344 218.001 107.782 218.538 107.782Z",
    fill: "#50575E"
  }), Object(external_this_wp_element_["createElement"])("path", {
    opacity: "0.1",
    d: "M223.783 66.4897C223.785 67.0229 223.718 67.554 223.584 68.07C223.454 68.01 223.345 67.9113 223.271 67.787C223.198 67.6628 223.164 67.519 223.174 67.3748C223.201 67.121 223.369 66.8885 223.333 66.6368C223.28 66.2487 222.814 66.0909 222.568 65.7838C222.266 65.4127 222.331 64.8731 222.357 64.3933C222.382 63.9134 222.261 63.3269 221.815 63.1627C221.681 63.1137 221.535 63.1094 221.406 63.054C221.055 62.9047 220.947 62.4653 220.712 62.1625C220.242 61.5526 219.308 61.5654 218.595 61.8554C218.284 62.0054 217.966 62.1392 217.642 62.2563C217.48 62.314 217.307 62.3372 217.136 62.3243C216.964 62.3115 216.797 62.2629 216.645 62.1817C216.505 62.0921 216.393 61.9684 216.249 61.8789C215.771 61.5888 215.144 61.8789 214.729 62.2521C214.314 62.6253 213.982 63.1265 213.459 63.3547C213.224 63.4613 212.934 63.5253 212.824 63.7556C212.769 63.881 212.746 64.0179 212.756 64.1544C212.756 64.984 212.854 65.8264 212.682 66.639C212.572 67.1615 212.208 67.7544 211.681 67.6904C211.615 67.6806 211.551 67.6642 211.488 67.6413C211.329 66.7872 211.349 65.9091 211.547 65.0631C211.744 64.2171 212.114 63.4219 212.634 62.7282C213.153 62.0346 213.811 61.4577 214.564 61.0344C215.317 60.6112 216.15 60.3509 217.009 60.2702C217.868 60.1895 218.734 60.2901 219.552 60.5657C220.37 60.8412 221.122 61.2857 221.76 61.8706C222.398 62.4555 222.908 63.168 223.257 63.9626C223.606 64.7572 223.787 65.6164 223.788 66.4854L223.783 66.4897Z",
    fill: "black"
  }), Object(external_this_wp_element_["createElement"])("path", {
    d: "M211.211 67.2875C211.37 67.3237 211.52 67.4069 211.683 67.4282C212.21 67.4922 212.574 66.8993 212.684 66.3768C212.856 65.5643 212.767 64.7219 212.761 63.8923C212.749 63.7559 212.771 63.6187 212.826 63.4935C212.947 63.2632 213.237 63.1992 213.461 63.0925C213.974 62.8622 214.308 62.3717 214.731 61.99C215.155 61.6082 215.771 61.3267 216.249 61.6167C216.391 61.702 216.505 61.83 216.645 61.9196C216.798 61.9996 216.965 62.0475 217.137 62.0603C217.309 62.0732 217.481 62.0507 217.644 61.9942C217.972 61.8961 218.279 61.7234 218.595 61.5933C219.302 61.3032 220.238 61.2926 220.712 61.8982C220.947 62.2032 221.055 62.6404 221.406 62.7897C221.535 62.8452 221.681 62.8516 221.815 62.9006C222.261 63.0691 222.376 63.6513 222.357 64.1311C222.338 64.611 222.274 65.1506 222.568 65.5216C222.814 65.8245 223.28 65.9823 223.333 66.3747C223.366 66.6264 223.201 66.861 223.174 67.1126C223.168 67.2061 223.18 67.3 223.21 67.3887C223.24 67.4775 223.287 67.5594 223.349 67.6296C223.41 67.6998 223.485 67.757 223.569 67.7979C223.653 67.8387 223.744 67.8624 223.836 67.8676C224.387 67.8676 224.728 67.247 225.242 67.0486C225.454 66.9655 225.71 66.9527 225.877 66.7842C225.939 66.7032 225.983 66.6096 226.006 66.5098C226.029 66.41 226.029 66.3064 226.008 66.2062C225.905 65.3894 225.255 64.6792 225.325 63.8603C225.39 63.0755 226.112 62.3909 225.96 61.6189C225.882 61.3342 225.737 61.0729 225.536 60.8575C225.309 60.5477 225.014 60.2957 224.673 60.1217C224.378 59.9938 224.038 59.9639 223.769 59.8082C222.968 59.3668 222.971 58.2151 222.499 57.4303C222.296 57.106 222.009 56.8441 221.668 56.6733C221.328 56.5025 220.947 56.4294 220.568 56.4621C219.973 56.5218 219.433 56.8396 218.845 56.9462C218.038 57.0912 217.221 56.8247 216.457 56.5197C215.692 56.2147 214.92 55.8799 214.101 55.8799C213.281 55.8799 212.394 56.3064 212.144 57.087C212.013 57.5135 212.079 57.9699 211.977 58.3986C211.799 59.1599 211.13 59.7016 210.457 60.0791C209.994 60.3393 209.437 60.6379 209.382 61.1689C209.346 61.5058 209.53 61.8087 209.646 62.135C209.844 62.687 209.84 63.2922 209.634 63.8411C209.479 64.2442 209.221 64.5982 209.064 64.9991C208.953 65.2713 208.901 65.564 208.91 65.8582C208.92 66.1523 208.991 66.441 209.119 66.7053C209.218 66.8979 209.355 67.0683 209.521 67.2058C209.687 67.3433 209.88 67.4451 210.087 67.505C210.504 67.6052 210.766 67.1894 211.211 67.2875Z",
    fill: "#2F2E41"
  }), Object(external_this_wp_element_["createElement"])("g", {
    opacity: "0.1"
  }, Object(external_this_wp_element_["createElement"])("path", {
    opacity: "0.1",
    d: "M212.685 65.9121C212.574 66.4346 212.208 67.0275 211.681 66.9635C211.518 66.9422 211.37 66.859 211.209 66.8227C210.767 66.7246 210.504 67.1426 210.087 67.036C209.88 66.9762 209.687 66.8744 209.52 66.7369C209.354 66.5993 209.217 66.429 209.117 66.2362C209.007 66.0192 208.94 65.7822 208.921 65.5389C208.878 65.9165 208.947 66.2983 209.117 66.6372C209.217 66.8299 209.354 67.0003 209.52 67.1378C209.687 67.2753 209.88 67.3771 210.087 67.4369C210.51 67.535 210.767 67.117 211.209 67.2237C211.364 67.278 211.522 67.3243 211.681 67.3623C212.208 67.4284 212.574 66.8355 212.685 66.313C212.771 65.8514 212.805 65.3813 212.786 64.9119C212.784 65.2478 212.75 65.5827 212.685 65.9121Z",
    fill: "black"
  }), Object(external_this_wp_element_["createElement"])("path", {
    opacity: "0.1",
    d: "M209.784 62.7001C209.811 62.3506 209.764 61.9994 209.645 61.67C209.549 61.451 209.468 61.2259 209.401 60.9961C209.392 61.0319 209.385 61.0682 209.38 61.1049C209.344 61.4397 209.53 61.7447 209.645 62.071C209.719 62.2735 209.766 62.4851 209.784 62.7001Z",
    fill: "black"
  }), Object(external_this_wp_element_["createElement"])("path", {
    opacity: "0.1",
    d: "M225.958 61.5762C225.858 62.1819 225.371 62.7513 225.323 63.3889C225.311 63.4981 225.311 63.6082 225.323 63.7174C225.429 62.9731 226.083 62.3141 225.958 61.5762Z",
    fill: "black"
  }), Object(external_this_wp_element_["createElement"])("path", {
    opacity: "0.1",
    d: "M225.994 66.0762C225.976 66.1653 225.934 66.2479 225.873 66.315C225.717 66.4835 225.45 66.4963 225.238 66.5795C224.724 66.7778 224.392 67.4048 223.833 67.3984C223.69 67.3903 223.552 67.3386 223.439 67.2503C223.326 67.162 223.241 67.0411 223.198 66.9036C223.185 66.9499 223.175 66.9969 223.168 67.0444C223.162 67.1378 223.174 67.2314 223.204 67.3199C223.235 67.4084 223.282 67.49 223.343 67.56C223.405 67.63 223.48 67.687 223.563 67.7277C223.647 67.7684 223.738 67.7921 223.831 67.7972C224.383 67.7972 224.722 67.1766 225.236 66.9804C225.448 66.8972 225.706 66.8844 225.871 66.716C225.933 66.635 225.977 66.5413 226 66.4415C226.023 66.3418 226.024 66.2382 226.003 66.138C226 66.1124 225.996 66.0954 225.994 66.0762Z",
    fill: "black"
  }), Object(external_this_wp_element_["createElement"])("path", {
    opacity: "0.1",
    d: "M223.339 65.8993C223.287 65.5111 222.821 65.3533 222.575 65.0462C222.425 64.8445 222.343 64.5991 222.342 64.3467C222.325 64.7434 222.332 65.1486 222.575 65.4471C222.819 65.7457 223.189 65.8737 223.308 66.1808C223.338 66.0902 223.348 65.9943 223.339 65.8993Z",
    fill: "black"
  }), Object(external_this_wp_element_["createElement"])("path", {
    opacity: "0.1",
    d: "M222.355 63.6662C222.372 63.1863 222.257 62.5998 221.811 62.4356C221.679 62.3866 221.533 62.3802 221.404 62.3269C221.053 62.1776 220.943 61.7383 220.71 61.4354C220.238 60.8276 219.304 60.8383 218.593 61.1283C218.275 61.2584 217.973 61.4311 217.642 61.5293C217.48 61.5869 217.308 61.6101 217.136 61.5972C216.964 61.5844 216.797 61.5358 216.645 61.4546C216.506 61.365 216.391 61.2413 216.249 61.1518C215.769 60.8617 215.142 61.1518 214.729 61.525C214.317 61.8982 213.982 62.3972 213.459 62.6276C213.222 62.7342 212.934 62.7982 212.811 63.0285C212.758 63.1541 212.735 63.291 212.746 63.4273C212.746 63.5105 212.746 63.5937 212.746 63.6768C212.749 63.5909 212.77 63.5066 212.807 63.4295C212.93 63.1991 213.218 63.1351 213.455 63.0285C213.967 62.7982 214.302 62.3077 214.725 61.9259C215.149 61.5442 215.765 61.2627 216.245 61.5527C216.387 61.638 216.501 61.766 216.641 61.8534C216.793 61.9351 216.96 61.984 217.132 61.9968C217.303 62.0097 217.476 61.9862 217.638 61.9281C217.962 61.812 218.279 61.6789 218.589 61.5293C219.298 61.2392 220.231 61.2264 220.706 61.8342C220.938 62.1371 221.049 62.5764 221.4 62.7257C221.529 62.7811 221.675 62.7875 221.806 62.8366C222.168 62.9731 222.312 63.3825 222.344 63.7877L222.355 63.6662Z",
    fill: "black"
  })), Object(external_this_wp_element_["createElement"])("g", {
    opacity: "0.1"
  }, Object(external_this_wp_element_["createElement"])("path", {
    opacity: "0.1",
    d: "M211.928 65.8994C211.94 65.5454 212.066 65.205 212.287 64.9292C212.508 64.6535 212.811 64.4573 213.152 64.3703C214.096 64.1378 215.086 64.183 216.006 64.5004C218.015 65.17 215.752 67.5799 215.752 67.5799C215.752 67.5799 214.246 69.0728 212.843 68.0299C212.428 67.7143 212.138 67.2609 212.024 66.7503C211.955 66.4721 211.923 66.186 211.928 65.8994Z",
    fill: "black"
  }), Object(external_this_wp_element_["createElement"])("path", {
    opacity: "0.1",
    d: "M222.972 65.8991C222.96 65.5448 222.834 65.2042 222.613 64.9284C222.392 64.6526 222.088 64.4566 221.747 64.37C220.803 64.1379 219.814 64.1831 218.895 64.5001C216.886 65.1697 219.149 67.5796 219.149 67.5796C219.149 67.5796 220.652 69.0725 222.058 68.0296C222.472 67.714 222.763 67.2606 222.877 66.75C222.946 66.4718 222.978 66.1857 222.972 65.8991Z",
    fill: "black"
  }), Object(external_this_wp_element_["createElement"])("path", {
    opacity: "0.1",
    d: "M215.627 64.3039V64.4041H219.295V64.2847L215.627 64.3039Z",
    fill: "black"
  }), Object(external_this_wp_element_["createElement"])("path", {
    opacity: "0.1",
    d: "M216.797 65.3724H216.914V65.0333C216.914 65.0333 217.286 64.7412 217.905 65.0333V65.3362H218.116V65.1378H218.013C218.013 65.1378 218.055 64.7262 217.365 64.7923C217.365 64.7923 216.81 64.739 216.823 65.093H216.696L216.797 65.3724Z",
    fill: "black"
  })), Object(external_this_wp_element_["createElement"])("path", {
    d: "M211.928 65.7608C211.941 65.4068 212.066 65.0664 212.287 64.7906C212.508 64.5149 212.811 64.3187 213.152 64.2317C214.096 64.0008 215.086 64.0459 216.006 64.3618C218.015 65.0336 215.752 67.4413 215.752 67.4413C215.752 67.4413 214.246 68.9342 212.843 67.8913C212.428 67.5765 212.137 67.1228 212.024 66.6117C211.955 66.3336 211.923 66.0474 211.928 65.7608Z",
    fill: "#2F2E41"
  }), Object(external_this_wp_element_["createElement"])("path", {
    d: "M222.973 65.7608C222.961 65.4066 222.835 65.0659 222.613 64.7902C222.392 64.5144 222.088 64.3184 221.747 64.2317C220.804 64.0008 219.815 64.0459 218.896 64.3618C216.887 65.0336 219.15 67.4414 219.15 67.4414C219.15 67.4414 220.653 68.9342 222.058 67.8914C222.474 67.5765 222.764 67.1229 222.878 66.6118C222.947 66.3336 222.979 66.0475 222.973 65.7608Z",
    fill: "#2F2E41"
  }), Object(external_this_wp_element_["createElement"])("path", {
    d: "M215.627 64.1696V64.2719H219.295V64.1504L215.627 64.1696Z",
    fill: "#2F2E41"
  }), Object(external_this_wp_element_["createElement"])("path", {
    d: "M216.797 65.2382H216.914V64.8991C216.914 64.8991 217.286 64.6091 217.905 64.8991V65.2041H218.116V65.0036H218.013C218.013 65.0036 218.055 64.5942 217.365 64.6582C217.365 64.6582 216.81 64.607 216.823 64.9589H216.696L216.797 65.2382Z",
    fill: "#2F2E41"
  })), Object(external_this_wp_element_["createElement"])("defs", null, Object(external_this_wp_element_["createElement"])("linearGradient", {
    id: "paint0_linear",
    x1: "38982",
    y1: "103102",
    x2: "38982",
    y2: "34934.7",
    gradientUnits: "userSpaceOnUse"
  }, Object(external_this_wp_element_["createElement"])("stop", {
    stopColor: "#808080",
    stopOpacity: "0.25"
  }), Object(external_this_wp_element_["createElement"])("stop", {
    offset: "0.54",
    stopColor: "#808080",
    stopOpacity: "0.12"
  }), Object(external_this_wp_element_["createElement"])("stop", {
    offset: "1",
    stopColor: "#808080",
    stopOpacity: "0.1"
  })), Object(external_this_wp_element_["createElement"])("clipPath", {
    id: "speed-svg"
  }, Object(external_this_wp_element_["createElement"])("path", {
    d: "M0 6C0 2.6863 2.68629 0 6 0H289C292.314 0 295 2.68629 295 6V160H0V6Z",
    fill: "white"
  }))));
});
// EXTERNAL MODULE: ./client/wc-api/with-select.js
var with_select = __webpack_require__(101);

// EXTERNAL MODULE: ./client/lib/tracks.js
var tracks = __webpack_require__(79);

// EXTERNAL MODULE: ./client/wc-api/onboarding/constants.js
var constants = __webpack_require__(760);

// CONCATENATED MODULE: ./client/profile-wizard/steps/benefits/index.js









function benefits_createSuper(Derived) { var hasNativeReflectConstruct = benefits_isNativeReflectConstruct(); return function () { var Super = getPrototypeOf_default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = getPrototypeOf_default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return possibleConstructorReturn_default()(this, result); }; }

function benefits_isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/**
 * External dependencies
 */






/**
 * WooCommerce dependencies
 */




/**
 * Internal dependencies
 */











var benefits_Benefits = /*#__PURE__*/function (_Component) {
  inherits_default()(Benefits, _Component);

  var _super = benefits_createSuper(Benefits);

  function Benefits(props) {
    var _this;

    classCallCheck_default()(this, Benefits);

    _this = _super.call(this, props);
    _this.state = {
      isConnecting: false,
      isInstalling: false,
      isActioned: false
    };
    _this.isJetpackActive = props.activePlugins.includes('jetpack');
    _this.isWcsActive = props.activePlugins.includes('woocommerce-services');
    _this.pluginsToInstall = [];

    if (!_this.isJetpackActive) {
      _this.pluginsToInstall.push('jetpack');
    }

    if (!_this.isWcsActive) {
      _this.pluginsToInstall.push('woocommerce-services');
    }

    Object(tracks["b" /* recordEvent */])('storeprofiler_plugins_to_install', {
      plugins: _this.pluginsToInstall
    });
    _this.startPluginInstall = _this.startPluginInstall.bind(assertThisInitialized_default()(_this));
    _this.skipPluginInstall = _this.skipPluginInstall.bind(assertThisInitialized_default()(_this));
    return _this;
  }

  createClass_default()(Benefits, [{
    key: "componentDidUpdate",
    value: function componentDidUpdate(prevProps, prevState) {
      var goToNextStep = this.props.goToNextStep;
      var isActioned = this.state.isActioned; // No longer pending or updating profile items, go to next step.

      if (isActioned && !this.isPending() && (prevProps.isRequesting || prevState.isConnecting || prevState.isInstalling)) {
        goToNextStep();
      }
    }
  }, {
    key: "isPending",
    value: function isPending() {
      var _this$state = this.state,
          isActioned = _this$state.isActioned,
          isConnecting = _this$state.isConnecting,
          isInstalling = _this$state.isInstalling;
      var isRequesting = this.props.isRequesting;
      return isActioned && (isConnecting || isInstalling || isRequesting);
    }
  }, {
    key: "skipPluginInstall",
    value: function () {
      var _skipPluginInstall = asyncToGenerator_default()( /*#__PURE__*/regeneratorRuntime.mark(function _callee() {
        var _this$props, createNotice, isProfileItemsError, updateProfileItems, plugins;

        return regeneratorRuntime.wrap(function _callee$(_context) {
          while (1) {
            switch (_context.prev = _context.next) {
              case 0:
                _this$props = this.props, createNotice = _this$props.createNotice, isProfileItemsError = _this$props.isProfileItemsError, updateProfileItems = _this$props.updateProfileItems;
                plugins = this.isJetpackActive ? 'skipped-wcs' : 'skipped';
                _context.next = 4;
                return updateProfileItems({
                  plugins: plugins
                });

              case 4:
                this.setState({
                  isActioned: true
                });

                if (isProfileItemsError) {
                  createNotice('error', Object(external_this_wp_i18n_["__"])('There was a problem updating your preferences.', 'woocommerce'));
                } else {
                  Object(tracks["b" /* recordEvent */])('storeprofiler_install_plugins', {
                    install: false,
                    plugins: plugins
                  });
                }

              case 6:
              case "end":
                return _context.stop();
            }
          }
        }, _callee, this);
      }));

      function skipPluginInstall() {
        return _skipPluginInstall.apply(this, arguments);
      }

      return skipPluginInstall;
    }()
  }, {
    key: "startPluginInstall",
    value: function () {
      var _startPluginInstall = asyncToGenerator_default()( /*#__PURE__*/regeneratorRuntime.mark(function _callee2() {
        var _this$props2, updateProfileItems, updateOptions, plugins;

        return regeneratorRuntime.wrap(function _callee2$(_context2) {
          while (1) {
            switch (_context2.prev = _context2.next) {
              case 0:
                _this$props2 = this.props, updateProfileItems = _this$props2.updateProfileItems, updateOptions = _this$props2.updateOptions;
                this.setState({
                  isActioned: true,
                  isInstalling: true
                });
                _context2.next = 4;
                return updateOptions({
                  woocommerce_setup_jetpack_opted_in: true
                });

              case 4:
                plugins = this.isJetpackActive ? 'installed-wcs' : 'installed';
                Object(tracks["b" /* recordEvent */])('storeprofiler_install_plugins', {
                  install: true,
                  plugins: plugins
                });
                updateProfileItems({
                  plugins: plugins
                });

              case 7:
              case "end":
                return _context2.stop();
            }
          }
        }, _callee2, this);
      }));

      function startPluginInstall() {
        return _startPluginInstall.apply(this, arguments);
      }

      return startPluginInstall;
    }()
  }, {
    key: "renderBenefit",
    value: function renderBenefit(benefit) {
      var description = benefit.description,
          icon = benefit.icon,
          title = benefit.title;
      return Object(external_this_wp_element_["createElement"])("div", {
        className: "woocommerce-profile-wizard__benefit-card",
        key: title
      }, icon, Object(external_this_wp_element_["createElement"])("div", {
        className: "woocommerce-profile-wizard__benefit-card-content"
      }, Object(external_this_wp_element_["createElement"])(external_this_wc_components_["H"], {
        className: "woocommerce-profile-wizard__benefit-card-title"
      }, title), Object(external_this_wp_element_["createElement"])("p", null, description)));
    }
  }, {
    key: "getBenefits",
    value: function getBenefits() {
      return [{
        title: Object(external_this_wp_i18n_["__"])('Store management on the go', 'woocommerce'),
        icon: Object(external_this_wp_element_["createElement"])(management, null),
        description: Object(external_this_wp_i18n_["__"])('Your store in your pocket. Manage orders, receive sales notifications, and more. Only with a Jetpack connection.', 'woocommerce'),
        visible: !this.isJetpackActive
      }, {
        title: Object(external_this_wp_i18n_["__"])('Automated sales taxes', 'woocommerce'),
        icon: Object(external_this_wp_element_["createElement"])(sales_tax, null),
        description: Object(external_this_wp_i18n_["__"])('Ensure that the correct rate of tax is charged on all of your orders automatically, and print shipping labels at home.', 'woocommerce'),
        visible: !this.isWcsActive || !this.isJetpackActive
      }, {
        title: Object(external_this_wp_i18n_["__"])('Improved speed & security', 'woocommerce'),
        icon: Object(external_this_wp_element_["createElement"])(speed, null),
        description: Object(external_this_wp_i18n_["__"])('Automatically block brute force attacks and speed up your store using our powerful, global server network to cache images.', 'woocommerce'),
        visible: !this.isJetpackActive
      }, {
        title: Object(external_this_wp_i18n_["__"])('Print shipping labels at home', 'woocommerce'),
        icon: Object(external_this_wp_element_["createElement"])(shipping_labels, null),
        description: Object(external_this_wp_i18n_["__"])('Save time at the post office by printing shipping labels for your orders at home.', 'woocommerce'),
        visible: this.isJetpackActive && !this.isWcsActive
      }];
    }
  }, {
    key: "renderBenefits",
    value: function renderBenefits() {
      var _this2 = this;

      return Object(external_this_wp_element_["createElement"])("div", {
        className: "woocommerce-profile-wizard__benefits"
      }, Object(external_lodash_["filter"])(this.getBenefits(), function (benefit) {
        return benefit.visible;
      }).map(function (benefit) {
        return _this2.renderBenefit(benefit);
      }));
    }
  }, {
    key: "render",
    value: function render() {
      var _this3 = this;

      var _this$state2 = this.state,
          isConnecting = _this$state2.isConnecting,
          isInstalling = _this$state2.isInstalling;
      var _this$props3 = this.props,
          isJetpackConnected = _this$props3.isJetpackConnected,
          isRequesting = _this$props3.isRequesting;
      var pluginNamesString = this.pluginsToInstall.map(function (pluginSlug) {
        return constants["a" /* pluginNames */][pluginSlug];
      }).join(' ' + Object(external_this_wp_i18n_["__"])('and', 'woocommerce') + ' ');
      return Object(external_this_wp_element_["createElement"])(external_this_wc_components_["Card"], {
        className: "woocommerce-profile-wizard__benefits-card"
      }, Object(external_this_wp_element_["createElement"])(logo, null), Object(external_this_wp_element_["createElement"])(external_this_wc_components_["H"], {
        className: "woocommerce-profile-wizard__header-title"
      }, Object(external_this_wp_i18n_["sprintf"])(Object(external_this_wp_i18n_["__"])('Enhance your store with %s', 'woocommerce'), pluginNamesString)), this.renderBenefits(), Object(external_this_wp_element_["createElement"])("div", {
        className: "woocommerce-profile-wizard__card-actions"
      }, Object(external_this_wp_element_["createElement"])(build_module_button["a" /* default */], {
        isPrimary: true,
        isBusy: this.isPending() && (isInstalling || isConnecting),
        disabled: this.isPending(),
        onClick: this.startPluginInstall,
        className: "woocommerce-profile-wizard__continue"
      }, Object(external_this_wp_i18n_["__"])('Yes please!', 'woocommerce')), Object(external_this_wp_element_["createElement"])(build_module_button["a" /* default */], {
        isDefault: true,
        isBusy: this.isPending() && !isInstalling && !isConnecting,
        disabled: this.isPending(),
        className: "woocommerce-profile-wizard__skip",
        onClick: this.skipPluginInstall
      }, Object(external_this_wp_i18n_["__"])('No thanks', 'woocommerce')), isInstalling && Object(external_this_wp_element_["createElement"])(external_this_wc_components_["Plugins"], {
        autoInstall: true,
        onComplete: function onComplete() {
          return _this3.setState({
            isInstalling: false,
            isConnecting: !isJetpackConnected
          });
        },
        onError: function onError() {
          return _this3.setState({
            isInstalling: false
          });
        },
        pluginSlugs: this.pluginsToInstall
      }), isConnecting && !isJetpackConnected && !isRequesting && Object(external_this_wp_element_["createElement"])(connect["a" /* default */], {
        autoConnect: true,
        onConnect: function onConnect() {
          Object(tracks["b" /* recordEvent */])('storeprofiler_jetpack_connect_redirect');
        },
        onError: function onError() {
          return _this3.setState({
            isConnecting: false
          });
        },
        redirectUrl: Object(client_settings["f" /* getAdminLink */])('admin.php?page=wc-admin&reset_profiler=0')
      })), Object(external_this_wp_element_["createElement"])("p", {
        className: "woocommerce-profile-wizard__benefits-install-notice"
      }, Object(external_this_wp_i18n_["sprintf"])(Object(external_this_wp_i18n_["__"])('%s %s will be installed & activated for free.', 'woocommerce'), pluginNamesString, Object(external_this_wp_i18n_["_n"])('plugin', 'plugins', this.pluginsToInstall.length, 'woocommerce'))));
    }
  }]);

  return Benefits;
}(external_this_wp_element_["Component"]);

/* harmony default export */ var benefits = (Object(compose["a" /* default */])(Object(with_select["a" /* default */])(function (select) {
  var _select = select('wc-api'),
      getProfileItemsError = _select.getProfileItemsError,
      getProfileItems = _select.getProfileItems,
      isGetProfileItemsRequesting = _select.isGetProfileItemsRequesting;

  var _select2 = select(external_this_wc_data_["PLUGINS_STORE_NAME"]),
      getActivePlugins = _select2.getActivePlugins,
      isJetpackConnected = _select2.isJetpackConnected;

  var isProfileItemsError = Boolean(getProfileItemsError());
  var activePlugins = getActivePlugins();
  var profileItems = getProfileItems();
  return {
    activePlugins: activePlugins,
    isProfileItemsError: isProfileItemsError,
    profileItems: profileItems,
    isJetpackConnected: isJetpackConnected(),
    isRequesting: isGetProfileItemsRequesting()
  };
}), Object(external_this_wp_data_["withDispatch"])(function (dispatch) {
  var _dispatch = dispatch('wc-api'),
      updateProfileItems = _dispatch.updateProfileItems,
      updateOptions = _dispatch.updateOptions;

  var _dispatch2 = dispatch('core/notices'),
      createNotice = _dispatch2.createNotice;

  return {
    createNotice: createNotice,
    updateProfileItems: updateProfileItems,
    updateOptions: updateOptions
  };
}))(benefits_Benefits));
// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/extends.js
var helpers_extends = __webpack_require__(105);
var extends_default = /*#__PURE__*/__webpack_require__.n(helpers_extends);

// EXTERNAL MODULE: ./node_modules/@wordpress/components/build-module/form-toggle/index.js
var form_toggle = __webpack_require__(718);

// EXTERNAL MODULE: external {"this":["wc","number"]}
var external_this_wc_number_ = __webpack_require__(204);

// EXTERNAL MODULE: ./client/dashboard/utils.js
var utils = __webpack_require__(742);

// EXTERNAL MODULE: ./client/lib/currency-context.js
var currency_context = __webpack_require__(203);

// CONCATENATED MODULE: ./client/profile-wizard/steps/business-details.js










function business_details_createSuper(Derived) { var hasNativeReflectConstruct = business_details_isNativeReflectConstruct(); return function () { var Super = getPrototypeOf_default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = getPrototypeOf_default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return possibleConstructorReturn_default()(this, result); }; }

function business_details_isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/**
 * External dependencies
 */






/**
 * WooCommerce dependencies
 */




/**
 * Internal dependencies
 */







var wcAdminAssetUrl = Object(client_settings["g" /* getSetting */])('wcAdminAssetUrl', '');

var business_details_BusinessDetails = /*#__PURE__*/function (_Component) {
  inherits_default()(BusinessDetails, _Component);

  var _super = business_details_createSuper(BusinessDetails);

  function BusinessDetails(props) {
    var _this;

    classCallCheck_default()(this, BusinessDetails);

    _this = _super.call(this);
    var profileItems = Object(external_lodash_["get"])(props, 'profileItems', {});
    var businessExtensions = Object(external_lodash_["get"])(profileItems, 'business_extensions', false);
    _this.initialValues = {
      other_platform: profileItems.other_platform || '',
      other_platform_name: profileItems.other_platform_name || '',
      product_count: profileItems.product_count || '',
      selling_venues: profileItems.selling_venues || '',
      revenue: profileItems.revenue || '',
      'facebook-for-woocommerce': businessExtensions ? businessExtensions.includes('facebook-for-woocommerce') : true,
      'mailchimp-for-woocommerce': businessExtensions ? businessExtensions.includes('mailchimp-for-woocommerce') : true,
      'kliken-marketing-for-google': businessExtensions ? businessExtensions.includes('kliken-marketing-for-google') : true
    };
    _this.state = {
      installExtensions: false,
      isInstallingExtensions: false,
      extensionInstallError: false
    };
    _this.extensions = ['facebook-for-woocommerce', 'mailchimp-for-woocommerce', 'kliken-marketing-for-google'];
    _this.onContinue = _this.onContinue.bind(assertThisInitialized_default()(_this));
    _this.validate = _this.validate.bind(assertThisInitialized_default()(_this));
    _this.getNumberRangeString = _this.getNumberRangeString.bind(assertThisInitialized_default()(_this));
    _this.numberFormat = _this.numberFormat.bind(assertThisInitialized_default()(_this));
    return _this;
  }

  createClass_default()(BusinessDetails, [{
    key: "onContinue",
    value: function () {
      var _onContinue = asyncToGenerator_default()( /*#__PURE__*/regeneratorRuntime.mark(function _callee(values) {
        var _this$props, createNotice, goToNextStep, isError, updateProfileItems, otherPlatform, otherPlatformName, productCount, revenue, sellingVenues, businessExtensions, getCurrency, _updates, updates;

        return regeneratorRuntime.wrap(function _callee$(_context) {
          while (1) {
            switch (_context.prev = _context.next) {
              case 0:
                _this$props = this.props, createNotice = _this$props.createNotice, goToNextStep = _this$props.goToNextStep, isError = _this$props.isError, updateProfileItems = _this$props.updateProfileItems;
                otherPlatform = values.other_platform, otherPlatformName = values.other_platform_name, productCount = values.product_count, revenue = values.revenue, sellingVenues = values.selling_venues;
                businessExtensions = this.getBusinessExtensions(values);
                getCurrency = this.context.getCurrency;
                Object(tracks["b" /* recordEvent */])('storeprofiler_store_business_details_continue', {
                  product_number: productCount,
                  already_selling: sellingVenues,
                  currency: getCurrency().code,
                  revenue: revenue,
                  used_platform: otherPlatform,
                  used_platform_name: otherPlatformName,
                  install_facebook: values['facebook-for-woocommerce'],
                  install_mailchimp: values['mailchimp-for-woocommerce'],
                  install_google_ads: values['kliken-marketing-for-google']
                });
                _updates = {
                  other_platform: otherPlatform,
                  other_platform_name: otherPlatform === 'other' ? otherPlatformName : '',
                  product_count: productCount,
                  revenue: revenue,
                  selling_venues: sellingVenues,
                  business_extensions: businessExtensions
                }; // Remove possible empty values like `revenue` and `other_platform`.

                updates = {};
                Object.keys(_updates).forEach(function (key) {
                  if (_updates[key] !== '') {
                    updates[key] = _updates[key];
                  }
                });
                _context.next = 10;
                return updateProfileItems(updates);

              case 10:
                if (isError) {
                  _context.next = 17;
                  break;
                }

                if (!(businessExtensions.length === 0)) {
                  _context.next = 14;
                  break;
                }

                goToNextStep();
                return _context.abrupt("return");

              case 14:
                this.setState({
                  installExtensions: true,
                  isInstallingExtensions: true
                });
                _context.next = 18;
                break;

              case 17:
                createNotice('error', Object(external_this_wp_i18n_["__"])('There was a problem updating your business details.', 'woocommerce'));

              case 18:
              case "end":
                return _context.stop();
            }
          }
        }, _callee, this);
      }));

      function onContinue(_x2) {
        return _onContinue.apply(this, arguments);
      }

      return onContinue;
    }()
  }, {
    key: "validate",
    value: function validate(values) {
      var _this2 = this;

      var errors = {};
      Object.keys(values).forEach(function (name) {
        if (name === 'other_platform') {
          if (!values.other_platform.length && ['other', 'brick-mortar-other'].includes(values.selling_venues)) {
            errors.other_platform = Object(external_this_wp_i18n_["__"])('This field is required', 'woocommerce');
          }
        } else if (name === 'other_platform_name') {
          if (!values.other_platform_name && values.other_platform === 'other' && ['other', 'brick-mortar-other'].includes(values.selling_venues)) {
            errors.other_platform_name = Object(external_this_wp_i18n_["__"])('This field is required', 'woocommerce');
          }
        } else if (name === 'revenue') {
          if (!values.revenue.length && ['other', 'brick-mortar', 'brick-mortar-other', 'other-woocommerce'].includes(values.selling_venues)) {
            errors.revenue = Object(external_this_wp_i18n_["__"])('This field is required', 'woocommerce');
          }
        } else if (!_this2.extensions.includes(name) && !values[name].length) {
          errors[name] = Object(external_this_wp_i18n_["__"])('This field is required', 'woocommerce');
        }
      });
      return errors;
    }
  }, {
    key: "getBusinessExtensions",
    value: function getBusinessExtensions(values) {
      var _this3 = this;

      return Object(external_lodash_["keys"])(Object(external_lodash_["pickBy"])(values)).filter(function (name) {
        return _this3.extensions.includes(name);
      });
    }
  }, {
    key: "convertCurrency",
    value: function convertCurrency(value) {
      var region = Object(utils["b" /* getCurrencyRegion */])(this.props.settings.woocommerce_default_country);

      if (region === 'US') {
        return value;
      } // These are rough exchange rates from USD.  Precision is not paramount.
      // The keys here should match the keys in `getCurrencyData`.


      var exchangeRates = {
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
      var exchangeRate = exchangeRates[region] || exchangeRates.US;
      var digits = exchangeRate.toString().split('.')[0].length;
      var multiplier = Math.pow(10, 2 + digits);
      return Math.round(value * exchangeRate / multiplier) * multiplier;
    }
  }, {
    key: "numberFormat",
    value: function numberFormat(value) {
      var getCurrency = this.context.getCurrency;
      return Object(external_this_wc_number_["formatValue"])(getCurrency(), 'number', value);
    }
  }, {
    key: "getNumberRangeString",
    value: function getNumberRangeString(min) {
      var max = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : false;
      var format = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : this.numberFormat;

      if (!max) {
        return Object(external_this_wp_i18n_["sprintf"])(Object(external_this_wp_i18n_["_x"])('%s+', 'store product count or revenue', 'woocommerce'), format(min));
      }

      return Object(external_this_wp_i18n_["sprintf"])(Object(external_this_wp_i18n_["_x"])('%1$s - %2$s', 'store product count or revenue range', 'woocommerce'), format(min), format(max));
    }
  }, {
    key: "renderBusinessExtensionHelpText",
    value: function renderBusinessExtensionHelpText(values) {
      var isInstallingExtensions = this.state.isInstallingExtensions;
      var extensions = this.getBusinessExtensions(values);

      if (extensions.length === 0) {
        return null;
      }

      var extensionsList = extensions.map(function (extension) {
        return constants["a" /* pluginNames */][extension];
      }).join(', ');

      if (isInstallingExtensions) {
        return Object(external_this_wp_element_["createElement"])("p", null, Object(external_this_wp_i18n_["sprintf"])(Object(external_this_wp_i18n_["_n"])('Installing the following plugin: %s', 'Installing the following plugins: %s', extensions.length, 'woocommerce'), extensionsList));
      }

      return Object(external_this_wp_element_["createElement"])("p", null, Object(external_this_wp_i18n_["sprintf"])(Object(external_this_wp_i18n_["_n"])('The following plugin will be installed for free: %s', 'The following plugins will be installed for free: %s', extensions.length, 'woocommerce'), extensionsList));
    }
  }, {
    key: "renderBusinessExtensions",
    value: function renderBusinessExtensions(values, getInputProps) {
      var _this4 = this;

      var _this$state = this.state,
          installExtensions = _this$state.installExtensions,
          extensionInstallError = _this$state.extensionInstallError;
      var goToNextStep = this.props.goToNextStep;
      var extensionsToInstall = this.getBusinessExtensions(values);
      var extensionBenefits = [{
        slug: 'facebook-for-woocommerce',
        title: Object(external_this_wp_i18n_["__"])('Market on Facebook', 'woocommerce'),
        icon: 'onboarding/fb-woocommerce.png',
        description: Object(external_this_wp_i18n_["__"])('Grow your business by targeting the right people and driving sales with Facebook.', 'woocommerce')
      }, {
        slug: 'mailchimp-for-woocommerce',
        title: Object(external_this_wp_i18n_["__"])('Contact customers with Mailchimp', 'woocommerce'),
        icon: 'onboarding/mailchimp.png',
        description: Object(external_this_wp_i18n_["__"])('Send targeted campaigns, recover abandoned carts and much more with Mailchimp.', 'woocommerce')
      }, {
        slug: 'kliken-marketing-for-google',
        title: Object(external_this_wp_i18n_["__"])('Drive sales with Google Ads', 'woocommerce'),
        icon: 'onboarding/g-shopping.png',
        description: Object(external_this_wp_i18n_["__"])('Get in front of new customers on Google and secure $150 in ads credit with Kliken’s integration.', 'woocommerce')
      }];
      return Object(external_this_wp_element_["createElement"])(external_this_wp_element_["Fragment"], null, extensionBenefits.map(function (benefit) {
        return Object(external_this_wp_element_["createElement"])("div", {
          className: "woocommerce-profile-wizard__benefit",
          key: benefit.title
        }, Object(external_this_wp_element_["createElement"])("div", {
          className: "woocommerce-profile-wizard__business-extension"
        }, Object(external_this_wp_element_["createElement"])("img", {
          src: wcAdminAssetUrl + benefit.icon,
          alt: ""
        })), Object(external_this_wp_element_["createElement"])("div", {
          className: "woocommerce-profile-wizard__benefit-content"
        }, Object(external_this_wp_element_["createElement"])(external_this_wc_components_["H"], {
          className: "woocommerce-profile-wizard__benefit-title"
        }, benefit.title), Object(external_this_wp_element_["createElement"])("p", null, benefit.description)), Object(external_this_wp_element_["createElement"])("div", {
          className: "woocommerce-profile-wizard__benefit-toggle"
        }, Object(external_this_wp_element_["createElement"])(form_toggle["a" /* default */], extends_default()({
          checked: values[benefit.slug]
        }, getInputProps(benefit.slug)))));
      }), installExtensions && Object(external_this_wp_element_["createElement"])("div", {
        className: "woocommerce-profile-wizard__card-actions"
      }, Object(external_this_wp_element_["createElement"])(external_this_wc_components_["Plugins"], {
        onComplete: function onComplete() {
          goToNextStep();
        },
        onSkip: function onSkip() {
          goToNextStep();
        },
        onError: function onError() {
          _this4.setState({
            extensionInstallError: true,
            isInstallingExtensions: false
          });
        },
        autoInstall: !extensionInstallError,
        pluginSlugs: extensionsToInstall
      })));
    }
  }, {
    key: "render",
    value: function render() {
      var _this5 = this;

      var _this$state2 = this.state,
          isInstallingExtensions = _this$state2.isInstallingExtensions,
          extensionInstallError = _this$state2.extensionInstallError;
      var formatCurrency = this.context.formatCurrency;
      var productCountOptions = [{
        key: '0',
        label: Object(external_this_wp_i18n_["__"])("I don't have any products yet.", 'woocommerce')
      }, {
        key: '1-10',
        label: this.getNumberRangeString(1, 10)
      }, {
        key: '11-100',
        label: this.getNumberRangeString(11, 100)
      }, {
        key: '101-1000',
        label: this.getNumberRangeString(101, 1000)
      }, {
        key: '1000+',
        label: this.getNumberRangeString(1000)
      }];
      var revenueOptions = [{
        key: 'none',
        label: Object(external_this_wp_i18n_["sprintf"])(
        /* translators: %s: $0 revenue amount */
        Object(external_this_wp_i18n_["__"])("%s (I'm just getting started)", 'woocommerce'), formatCurrency(0))
      }, {
        key: 'up-to-2500',
        label: Object(external_this_wp_i18n_["sprintf"])(
        /* translators: %s: A given revenue amount, e.g., $2500 */
        Object(external_this_wp_i18n_["__"])('Up to %s', 'woocommerce'), formatCurrency(this.convertCurrency(2500)))
      }, {
        key: '2500-10000',
        label: this.getNumberRangeString(this.convertCurrency(2500), this.convertCurrency(10000), formatCurrency)
      }, {
        key: '10000-50000',
        label: this.getNumberRangeString(this.convertCurrency(10000), this.convertCurrency(50000), formatCurrency)
      }, {
        key: '50000-250000',
        label: this.getNumberRangeString(this.convertCurrency(50000), this.convertCurrency(250000), formatCurrency)
      }, {
        key: 'more-than-250000',
        label: Object(external_this_wp_i18n_["sprintf"])(
        /* translators: %s: A given revenue amount, e.g., $250000 */
        Object(external_this_wp_i18n_["__"])('More than %s', 'woocommerce'), formatCurrency(this.convertCurrency(250000)))
      }];
      var sellingVenueOptions = [{
        key: 'no',
        label: Object(external_this_wp_i18n_["__"])('No', 'woocommerce')
      }, {
        key: 'other',
        label: Object(external_this_wp_i18n_["__"])('Yes, on another platform', 'woocommerce')
      }, {
        key: 'other-woocommerce',
        label: Object(external_this_wp_i18n_["__"])('Yes, I own a different store powered by WooCommerce', 'woocommerce')
      }, {
        key: 'brick-mortar',
        label: Object(external_this_wp_i18n_["__"])('Yes, in person at physical stores and/or events', 'woocommerce')
      }, {
        key: 'brick-mortar-other',
        label: Object(external_this_wp_i18n_["__"])('Yes, on another platform and in person at physical stores and/or events', 'woocommerce')
      }];
      var otherPlatformOptions = [{
        key: 'shopify',
        label: Object(external_this_wp_i18n_["__"])('Shopify', 'woocommerce')
      }, {
        key: 'bigcommerce',
        label: Object(external_this_wp_i18n_["__"])('BigCommerce', 'woocommerce')
      }, {
        key: 'magento',
        label: Object(external_this_wp_i18n_["__"])('Magento', 'woocommerce')
      }, {
        key: 'wix',
        label: Object(external_this_wp_i18n_["__"])('Wix', 'woocommerce')
      }, {
        key: 'amazon',
        label: Object(external_this_wp_i18n_["__"])('Amazon', 'woocommerce')
      }, {
        key: 'ebay',
        label: Object(external_this_wp_i18n_["__"])('eBay', 'woocommerce')
      }, {
        key: 'etsy',
        label: Object(external_this_wp_i18n_["__"])('Etsy', 'woocommerce')
      }, {
        key: 'squarespace',
        label: Object(external_this_wp_i18n_["__"])('Squarespace', 'woocommerce')
      }, {
        key: 'other',
        label: Object(external_this_wp_i18n_["__"])('Other', 'woocommerce')
      }];
      return Object(external_this_wp_element_["createElement"])(external_this_wc_components_["Form"], {
        initialValues: this.initialValues,
        onSubmitCallback: this.onContinue,
        validate: this.validate
      }, function (_ref) {
        var getInputProps = _ref.getInputProps,
            handleSubmit = _ref.handleSubmit,
            values = _ref.values,
            isValidForm = _ref.isValidForm;
        // Show extensions when the currently selling elsewhere checkbox has been answered.
        var showExtensions = values.selling_venues !== '';
        return Object(external_this_wp_element_["createElement"])(external_this_wp_element_["Fragment"], null, Object(external_this_wp_element_["createElement"])(external_this_wc_components_["H"], {
          className: "woocommerce-profile-wizard__header-title"
        }, Object(external_this_wp_i18n_["__"])('Tell us about your business', 'woocommerce')), Object(external_this_wp_element_["createElement"])("p", null, Object(external_this_wp_i18n_["__"])("We'd love to know if you are just getting started or you already have a business in place.", 'woocommerce')), Object(external_this_wp_element_["createElement"])(external_this_wc_components_["Card"], null, Object(external_this_wp_element_["createElement"])(external_this_wp_element_["Fragment"], null, Object(external_this_wp_element_["createElement"])(external_this_wc_components_["SelectControl"], extends_default()({
          label: Object(external_this_wp_i18n_["__"])('How many products do you plan to display?', 'woocommerce'),
          options: productCountOptions,
          required: true
        }, getInputProps('product_count'))), Object(external_this_wp_element_["createElement"])(external_this_wc_components_["SelectControl"], extends_default()({
          label: Object(external_this_wp_i18n_["__"])('Currently selling elsewhere?', 'woocommerce'),
          options: sellingVenueOptions,
          required: true
        }, getInputProps('selling_venues'))), ['other', 'brick-mortar', 'brick-mortar-other', 'other-woocommerce'].includes(values.selling_venues) && Object(external_this_wp_element_["createElement"])(external_this_wc_components_["SelectControl"], extends_default()({
          label: Object(external_this_wp_i18n_["__"])("What's your current annual revenue?", 'woocommerce'),
          options: revenueOptions,
          required: true
        }, getInputProps('revenue'))), ['other', 'brick-mortar-other'].includes(values.selling_venues) && Object(external_this_wp_element_["createElement"])(external_this_wp_element_["Fragment"], null, Object(external_this_wp_element_["createElement"])("div", {
          className: "business-competitors"
        }, Object(external_this_wp_element_["createElement"])(external_this_wc_components_["SelectControl"], extends_default()({
          label: Object(external_this_wp_i18n_["__"])('Which platform is the store using?', 'woocommerce'),
          options: otherPlatformOptions,
          required: true
        }, getInputProps('other_platform'))), values.other_platform === 'other' && Object(external_this_wp_element_["createElement"])(external_this_wc_components_["TextControl"], extends_default()({
          label: Object(external_this_wp_i18n_["__"])('What is the platform name?', 'woocommerce'),
          required: true
        }, getInputProps('other_platform_name'))))), showExtensions && _this5.renderBusinessExtensions(values, getInputProps), !extensionInstallError && Object(external_this_wp_element_["createElement"])(build_module_button["a" /* default */], {
          isPrimary: true,
          className: "woocommerce-profile-wizard__continue",
          onClick: handleSubmit,
          disabled: !isValidForm,
          isBusy: isInstallingExtensions
        }, Object(external_this_wp_i18n_["__"])('Continue', 'woocommerce')))), showExtensions && _this5.renderBusinessExtensionHelpText(values));
      });
    }
  }]);

  return BusinessDetails;
}(external_this_wp_element_["Component"]);

business_details_BusinessDetails.contextType = currency_context["a" /* CurrencyContext */];
/* harmony default export */ var business_details = (Object(compose["a" /* default */])(Object(with_select["a" /* default */])(function (select) {
  var _select = select('wc-api'),
      getProfileItems = _select.getProfileItems,
      getProfileItemsError = _select.getProfileItemsError;

  return {
    isError: Boolean(getProfileItemsError()),
    profileItems: getProfileItems()
  };
}), Object(external_this_wp_data_["withSelect"])(function (select) {
  var _select2 = select(external_this_wc_data_["SETTINGS_STORE_NAME"]),
      getSettings = _select2.getSettings,
      getSettingsError = _select2.getSettingsError,
      isGetSettingsRequesting = _select2.isGetSettingsRequesting;

  var _getSettings = getSettings('general'),
      _getSettings$general = _getSettings.general,
      settings = _getSettings$general === void 0 ? {} : _getSettings$general;

  var isSettingsError = Boolean(getSettingsError('general'));
  var isSettingsRequesting = isGetSettingsRequesting('general');
  return {
    isSettingsError: isSettingsError,
    isSettingsRequesting: isSettingsRequesting,
    settings: settings
  };
}), Object(external_this_wp_data_["withDispatch"])(function (dispatch) {
  var _dispatch = dispatch('wc-api'),
      updateProfileItems = _dispatch.updateProfileItems;

  var _dispatch2 = dispatch('core/notices'),
      createNotice = _dispatch2.createNotice;

  return {
    createNotice: createNotice,
    updateProfileItems: updateProfileItems
  };
}))(business_details_BusinessDetails));
// EXTERNAL MODULE: ./node_modules/@wordpress/components/build-module/checkbox-control/index.js
var checkbox_control = __webpack_require__(761);

// CONCATENATED MODULE: ./client/profile-wizard/steps/industry.js









function industry_createSuper(Derived) { var hasNativeReflectConstruct = industry_isNativeReflectConstruct(); return function () { var Super = getPrototypeOf_default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = getPrototypeOf_default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return possibleConstructorReturn_default()(this, result); }; }

function industry_isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/**
 * External dependencies
 */






/**
 * WooCommerce Dependencies
 */



/**
 * Internal dependencies
 */





var onboarding = Object(client_settings["g" /* getSetting */])('onboarding', {});

var industry_Industry = /*#__PURE__*/function (_Component) {
  inherits_default()(Industry, _Component);

  var _super = industry_createSuper(Industry);

  function Industry(props) {
    var _this;

    classCallCheck_default()(this, Industry);

    var profileItems = Object(external_lodash_["get"])(props, 'profileItems', {});
    var selected = profileItems.industry || [];
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

    var locationSettings = props.locationSettings;
    var region = Object(utils["b" /* getCurrencyRegion */])(locationSettings.woocommerce_default_country);

    if (region !== 'US') {
      var cbdSlug = 'cbd-other-hemp-derived-products';
      selected = selected.filter(function (industry) {
        return cbdSlug !== industry && cbdSlug !== industry.slug;
      });
    }
    /**
     * End block to be removed after refactor.
     */


    _this = _super.call(this);
    _this.state = {
      error: null,
      selected: selected,
      textInputListContent: {}
    };
    _this.onContinue = _this.onContinue.bind(assertThisInitialized_default()(_this));
    _this.onIndustryChange = _this.onIndustryChange.bind(assertThisInitialized_default()(_this));
    _this.onDetailChange = _this.onDetailChange.bind(assertThisInitialized_default()(_this));
    return _this;
  }

  createClass_default()(Industry, [{
    key: "onContinue",
    value: function () {
      var _onContinue = asyncToGenerator_default()( /*#__PURE__*/regeneratorRuntime.mark(function _callee() {
        var _this$props, createNotice, goToNextStep, isError, updateProfileItems, selectedIndustriesList, industriesWithDetail;

        return regeneratorRuntime.wrap(function _callee$(_context) {
          while (1) {
            switch (_context.prev = _context.next) {
              case 0:
                _context.next = 2;
                return this.validateField();

              case 2:
                if (!this.state.error) {
                  _context.next = 4;
                  break;
                }

                return _context.abrupt("return");

              case 4:
                _this$props = this.props, createNotice = _this$props.createNotice, goToNextStep = _this$props.goToNextStep, isError = _this$props.isError, updateProfileItems = _this$props.updateProfileItems;
                selectedIndustriesList = this.state.selected.map(function (industry) {
                  return industry.slug;
                }); // Here the selected industries are converted to a string that is a comma separated list

                industriesWithDetail = this.state.selected.map(function (industry) {
                  return industry.detail;
                }).filter(function (n) {
                  return n;
                }).join(',');
                Object(tracks["b" /* recordEvent */])('storeprofiler_store_industry_continue', {
                  store_industry: selectedIndustriesList,
                  industries_with_detail: industriesWithDetail
                });
                _context.next = 10;
                return updateProfileItems({
                  industry: this.state.selected
                });

              case 10:
                if (!isError) {
                  goToNextStep();
                } else {
                  createNotice('error', Object(external_this_wp_i18n_["__"])('There was a problem updating your industries.', 'woocommerce'));
                }

              case 11:
              case "end":
                return _context.stop();
            }
          }
        }, _callee, this);
      }));

      function onContinue() {
        return _onContinue.apply(this, arguments);
      }

      return onContinue;
    }()
  }, {
    key: "validateField",
    value: function () {
      var _validateField = asyncToGenerator_default()( /*#__PURE__*/regeneratorRuntime.mark(function _callee2() {
        var error;
        return regeneratorRuntime.wrap(function _callee2$(_context2) {
          while (1) {
            switch (_context2.prev = _context2.next) {
              case 0:
                error = this.state.selected.length ? null : Object(external_this_wp_i18n_["__"])('Please select at least one industry', 'woocommerce');
                this.setState({
                  error: error
                });

              case 2:
              case "end":
                return _context2.stop();
            }
          }
        }, _callee2, this);
      }));

      function validateField() {
        return _validateField.apply(this, arguments);
      }

      return validateField;
    }()
  }, {
    key: "onIndustryChange",
    value: function onIndustryChange(slug) {
      var _this2 = this;

      this.setState(function (state) {
        var newSelected = state.selected;
        var selectedIndustry = Object(external_lodash_["find"])(newSelected, {
          slug: slug
        });

        if (selectedIndustry) {
          var newTextInputListContent = state.textInputListContent;
          newTextInputListContent[slug] = selectedIndustry.detail;
          return {
            selected: Object(external_lodash_["filter"])(state.selected, function (value) {
              return value.slug !== slug;
            }) || [],
            textInputListContent: newTextInputListContent
          };
        }

        newSelected.push({
          slug: slug,
          detail: state.textInputListContent[slug]
        });
        return {
          selected: newSelected
        };
      }, function () {
        return _this2.validateField();
      });
    }
  }, {
    key: "onDetailChange",
    value: function onDetailChange(value, slug) {
      this.setState(function (state) {
        var newSelected = state.selected;
        var newTextInputListContent = state.textInputListContent;
        var industryIndex = Object(external_lodash_["findIndex"])(newSelected, {
          slug: slug
        });
        newSelected[industryIndex].detail = value;
        newTextInputListContent[slug] = value;
        return {
          selected: newSelected,
          textInputListContent: newTextInputListContent
        };
      });
    }
  }, {
    key: "render",
    value: function render() {
      var _this3 = this;

      var industries = onboarding.industries;
      var _this$state = this.state,
          error = _this$state.error,
          selected = _this$state.selected,
          textInputListContent = _this$state.textInputListContent;
      var locationSettings = this.props.locationSettings;
      var region = Object(utils["b" /* getCurrencyRegion */])(locationSettings.woocommerce_default_country);
      var industryKeys = Object.keys(industries);
      var filteredIndustryKeys = region === 'US' ? industryKeys : industryKeys.filter(function (slug) {
        return slug !== 'cbd-other-hemp-derived-products';
      });
      return Object(external_this_wp_element_["createElement"])(external_this_wp_element_["Fragment"], null, Object(external_this_wp_element_["createElement"])(external_this_wc_components_["H"], {
        className: "woocommerce-profile-wizard__header-title"
      }, Object(external_this_wp_i18n_["__"])('In which industry does the store operate?', 'woocommerce')), Object(external_this_wp_element_["createElement"])("p", {
        className: "woocommerce-profile-wizard__intro-paragraph"
      }, Object(external_this_wp_i18n_["__"])('Choose any that apply')), Object(external_this_wp_element_["createElement"])(external_this_wc_components_["Card"], null, Object(external_this_wp_element_["createElement"])("div", {
        className: "woocommerce-profile-wizard__checkbox-group"
      }, filteredIndustryKeys.map(function (slug) {
        var selectedIndustry = Object(external_lodash_["find"])(selected, {
          slug: slug
        });
        return Object(external_this_wp_element_["createElement"])("div", {
          key: "div-".concat(slug)
        }, Object(external_this_wp_element_["createElement"])(checkbox_control["a" /* default */], {
          key: "checkbox-control-".concat(slug),
          label: industries[slug].label,
          onChange: function onChange() {
            return _this3.onIndustryChange(slug);
          },
          checked: selectedIndustry || false,
          className: "woocommerce-profile-wizard__checkbox"
        }), industries[slug].use_description && selectedIndustry && Object(external_this_wp_element_["createElement"])(external_this_wc_components_["TextControl"], {
          key: "text-control-".concat(selectedIndustry.slug),
          label: industries[selectedIndustry.slug].description_label,
          value: selectedIndustry.detail || textInputListContent[slug] || '',
          onChange: function onChange(value) {
            return _this3.onDetailChange(value, selectedIndustry.slug);
          },
          className: "woocommerce-profile-wizard__text"
        }));
      }), error && Object(external_this_wp_element_["createElement"])("span", {
        className: "woocommerce-profile-wizard__error"
      }, error)), Object(external_this_wp_element_["createElement"])(build_module_button["a" /* default */], {
        isPrimary: true,
        onClick: this.onContinue,
        disabled: !selected.length
      }, Object(external_this_wp_i18n_["__"])('Continue', 'woocommerce'))));
    }
  }]);

  return Industry;
}(external_this_wp_element_["Component"]);

/* harmony default export */ var industry = (Object(compose["a" /* default */])(Object(with_select["a" /* default */])(function (select) {
  var _select = select('wc-api'),
      getProfileItems = _select.getProfileItems,
      getProfileItemsError = _select.getProfileItemsError;

  var _select2 = select(external_this_wc_data_["SETTINGS_STORE_NAME"]),
      getSettings = _select2.getSettings;

  var _getSettings = getSettings('general'),
      _getSettings$general = _getSettings.general,
      locationSettings = _getSettings$general === void 0 ? {} : _getSettings$general;

  return {
    isError: Boolean(getProfileItemsError()),
    profileItems: getProfileItems(),
    locationSettings: locationSettings
  };
}), Object(external_this_wp_data_["withDispatch"])(function (dispatch) {
  var _dispatch = dispatch('wc-api'),
      updateProfileItems = _dispatch.updateProfileItems;

  var _dispatch2 = dispatch('core/notices'),
      createNotice = _dispatch2.createNotice;

  return {
    createNotice: createNotice,
    updateProfileItems: updateProfileItems
  };
}))(industry_Industry));
// EXTERNAL MODULE: ./node_modules/interpolate-components/lib/index.js
var lib = __webpack_require__(35);
var lib_default = /*#__PURE__*/__webpack_require__.n(lib);

// CONCATENATED MODULE: ./client/profile-wizard/steps/product-types.js









function product_types_createSuper(Derived) { var hasNativeReflectConstruct = product_types_isNativeReflectConstruct(); return function () { var Super = getPrototypeOf_default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = getPrototypeOf_default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return possibleConstructorReturn_default()(this, result); }; }

function product_types_isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/**
 * External dependencies
 */







/**
 * Internal dependencies
 */






var product_types_ProductTypes = /*#__PURE__*/function (_Component) {
  inherits_default()(ProductTypes, _Component);

  var _super = product_types_createSuper(ProductTypes);

  function ProductTypes(props) {
    var _this;

    classCallCheck_default()(this, ProductTypes);

    _this = _super.call(this);
    var profileItems = Object(external_lodash_["get"])(props, 'profileItems', {});
    _this.state = {
      error: null,
      selected: profileItems.product_types || []
    };
    _this.onContinue = _this.onContinue.bind(assertThisInitialized_default()(_this));
    _this.onChange = _this.onChange.bind(assertThisInitialized_default()(_this));
    return _this;
  }

  createClass_default()(ProductTypes, [{
    key: "validateField",
    value: function () {
      var _validateField = asyncToGenerator_default()( /*#__PURE__*/regeneratorRuntime.mark(function _callee() {
        var error;
        return regeneratorRuntime.wrap(function _callee$(_context) {
          while (1) {
            switch (_context.prev = _context.next) {
              case 0:
                error = this.state.selected.length ? null : Object(external_this_wp_i18n_["__"])('Please select at least one product type', 'woocommerce');
                this.setState({
                  error: error
                });

              case 2:
              case "end":
                return _context.stop();
            }
          }
        }, _callee, this);
      }));

      function validateField() {
        return _validateField.apply(this, arguments);
      }

      return validateField;
    }()
  }, {
    key: "onContinue",
    value: function () {
      var _onContinue = asyncToGenerator_default()( /*#__PURE__*/regeneratorRuntime.mark(function _callee2() {
        var _this$props, createNotice, goToNextStep, isError, updateProfileItems;

        return regeneratorRuntime.wrap(function _callee2$(_context2) {
          while (1) {
            switch (_context2.prev = _context2.next) {
              case 0:
                _context2.next = 2;
                return this.validateField();

              case 2:
                if (!this.state.error) {
                  _context2.next = 4;
                  break;
                }

                return _context2.abrupt("return");

              case 4:
                _this$props = this.props, createNotice = _this$props.createNotice, goToNextStep = _this$props.goToNextStep, isError = _this$props.isError, updateProfileItems = _this$props.updateProfileItems;
                Object(tracks["b" /* recordEvent */])('storeprofiler_store_product_type_continue', {
                  product_type: this.state.selected
                });
                _context2.next = 8;
                return updateProfileItems({
                  product_types: this.state.selected
                });

              case 8:
                if (!isError) {
                  goToNextStep();
                } else {
                  createNotice('error', Object(external_this_wp_i18n_["__"])('There was a problem updating your product types.', 'woocommerce'));
                }

              case 9:
              case "end":
                return _context2.stop();
            }
          }
        }, _callee2, this);
      }));

      function onContinue() {
        return _onContinue.apply(this, arguments);
      }

      return onContinue;
    }()
  }, {
    key: "onChange",
    value: function onChange(slug) {
      var _this2 = this;

      this.setState(function (state) {
        if (Object(external_lodash_["includes"])(state.selected, slug)) {
          return {
            selected: Object(external_lodash_["filter"])(state.selected, function (value) {
              return value !== slug;
            }) || []
          };
        }

        var newSelected = state.selected;
        newSelected.push(slug);
        return {
          selected: newSelected
        };
      }, function () {
        return _this2.validateField();
      });
    }
  }, {
    key: "onLearnMore",
    value: function onLearnMore(slug) {
      Object(tracks["b" /* recordEvent */])('storeprofiler_store_product_type_learn_more', {
        product_type: slug
      });
    }
  }, {
    key: "render",
    value: function render() {
      var _this3 = this;

      var _getSetting = Object(client_settings["g" /* getSetting */])('onboarding', {}),
          _getSetting$productTy = _getSetting.productTypes,
          productTypes = _getSetting$productTy === void 0 ? {} : _getSetting$productTy;

      var _this$state = this.state,
          error = _this$state.error,
          selected = _this$state.selected;
      return Object(external_this_wp_element_["createElement"])(external_this_wp_element_["Fragment"], null, Object(external_this_wp_element_["createElement"])(external_this_wc_components_["H"], {
        className: "woocommerce-profile-wizard__header-title"
      }, Object(external_this_wp_i18n_["__"])('What type of products will be listed?', 'woocommerce')), Object(external_this_wp_element_["createElement"])("p", null, Object(external_this_wp_i18n_["__"])('Choose any that apply')), Object(external_this_wp_element_["createElement"])(external_this_wc_components_["Card"], null, Object(external_this_wp_element_["createElement"])("div", {
        className: "woocommerce-profile-wizard__checkbox-group"
      }, Object.keys(productTypes).map(function (slug) {
        var helpText = productTypes[slug].description && lib_default()({
          mixedString: productTypes[slug].description + (productTypes[slug].more_url ? ' {{moreLink/}}' : ''),
          components: {
            moreLink: productTypes[slug].more_url ? Object(external_this_wp_element_["createElement"])(external_this_wc_components_["Link"], {
              href: productTypes[slug].more_url,
              target: "_blank",
              type: "external",
              onClick: function onClick() {
                return _this3.onLearnMore(slug);
              }
            }, Object(external_this_wp_i18n_["__"])('Learn more', 'woocommerce')) : ''
          }
        });
        return Object(external_this_wp_element_["createElement"])(checkbox_control["a" /* default */], {
          key: slug,
          label: productTypes[slug].label,
          help: helpText,
          onChange: function onChange() {
            return _this3.onChange(slug);
          },
          checked: selected.includes(slug),
          className: "woocommerce-profile-wizard__checkbox"
        });
      }), error && Object(external_this_wp_element_["createElement"])("span", {
        className: "woocommerce-profile-wizard__error"
      }, error)), Object(external_this_wp_element_["createElement"])(build_module_button["a" /* default */], {
        isPrimary: true,
        className: "woocommerce-profile-wizard__continue",
        onClick: this.onContinue,
        disabled: !selected.length
      }, Object(external_this_wp_i18n_["__"])('Continue', 'woocommerce'))));
    }
  }]);

  return ProductTypes;
}(external_this_wp_element_["Component"]);

/* harmony default export */ var product_types = (Object(compose["a" /* default */])(Object(with_select["a" /* default */])(function (select) {
  var _select = select('wc-api'),
      getProfileItems = _select.getProfileItems,
      getProfileItemsError = _select.getProfileItemsError;

  return {
    isError: Boolean(getProfileItemsError()),
    profileItems: getProfileItems()
  };
}), Object(external_this_wp_data_["withDispatch"])(function (dispatch) {
  var _dispatch = dispatch('wc-api'),
      updateProfileItems = _dispatch.updateProfileItems;

  var _dispatch2 = dispatch('core/notices'),
      createNotice = _dispatch2.createNotice;

  return {
    createNotice: createNotice,
    updateProfileItems: updateProfileItems
  };
}))(product_types_ProductTypes));
// CONCATENATED MODULE: ./client/profile-wizard/header.js







function header_createSuper(Derived) { var hasNativeReflectConstruct = header_isNativeReflectConstruct(); return function () { var Super = getPrototypeOf_default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = getPrototypeOf_default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return possibleConstructorReturn_default()(this, result); }; }

function header_isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/**
 * External dependencies
 */


/**
 * WooCommerce dependencies
 */


/**
 * Internal dependencies
 */



var header_ProfileWizardHeader = /*#__PURE__*/function (_Component) {
  inherits_default()(ProfileWizardHeader, _Component);

  var _super = header_createSuper(ProfileWizardHeader);

  function ProfileWizardHeader() {
    classCallCheck_default()(this, ProfileWizardHeader);

    return _super.apply(this, arguments);
  }

  createClass_default()(ProfileWizardHeader, [{
    key: "renderStepper",
    value: function renderStepper() {
      var _this$props = this.props,
          currentStep = _this$props.currentStep,
          steps = _this$props.steps;
      var visibleSteps = Object(external_lodash_["filter"])(steps, function (step) {
        return !!step.label;
      });
      var currentStepIndex = visibleSteps.findIndex(function (step) {
        return step.key === currentStep;
      });
      visibleSteps.map(function (step, index) {
        var previousStep = visibleSteps[index - 1];

        if (index < currentStepIndex) {
          step.isComplete = true;
        }

        if (!previousStep || previousStep.isComplete) {
          step.onClick = function (key) {
            return Object(external_this_wc_navigation_["updateQueryString"])({
              step: key
            });
          };
        }

        return step;
      });
      return Object(external_this_wp_element_["createElement"])(external_this_wc_components_["Stepper"], {
        steps: visibleSteps,
        currentStep: currentStep
      });
    }
  }, {
    key: "render",
    value: function render() {
      var _this = this;

      var currentStep = this.props.steps.find(function (s) {
        return s.key === _this.props.currentStep;
      });

      if (!currentStep || !currentStep.label) {
        return null;
      }

      return Object(external_this_wp_element_["createElement"])("div", {
        className: "woocommerce-profile-wizard__header"
      }, this.renderStepper());
    }
  }]);

  return ProfileWizardHeader;
}(external_this_wp_element_["Component"]);


// EXTERNAL MODULE: ./client/wc-api/constants.js
var wc_api_constants = __webpack_require__(24);

// EXTERNAL MODULE: external {"this":["wp","apiFetch"]}
var external_this_wp_apiFetch_ = __webpack_require__(20);
var external_this_wp_apiFetch_default = /*#__PURE__*/__webpack_require__.n(external_this_wp_apiFetch_);

// EXTERNAL MODULE: external {"this":["wc","currency"]}
var external_this_wc_currency_ = __webpack_require__(137);

// EXTERNAL MODULE: ./client/dashboard/components/settings/general/store-address.js
var store_address = __webpack_require__(769);

// EXTERNAL MODULE: ./node_modules/@wordpress/components/build-module/modal/index.js + 3 modules
var modal = __webpack_require__(721);

// CONCATENATED MODULE: ./client/profile-wizard/steps/usage-modal.js









function usage_modal_createSuper(Derived) { var hasNativeReflectConstruct = usage_modal_isNativeReflectConstruct(); return function () { var Super = getPrototypeOf_default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = getPrototypeOf_default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return possibleConstructorReturn_default()(this, result); }; }

function usage_modal_isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/**
 * External dependencies
 */







/**
 * Internal dependencies
 */




var usage_modal_UsageModal = /*#__PURE__*/function (_Component) {
  inherits_default()(UsageModal, _Component);

  var _super = usage_modal_createSuper(UsageModal);

  function UsageModal(props) {
    var _this;

    classCallCheck_default()(this, UsageModal);

    _this = _super.call(this, props);
    _this.state = {
      allowTracking: props.allowTracking,
      isLoadingScripts: false
    };
    _this.onTrackingChange = _this.onTrackingChange.bind(assertThisInitialized_default()(_this));
    return _this;
  }

  createClass_default()(UsageModal, [{
    key: "onTrackingChange",
    value: function onTrackingChange() {
      this.setState({
        allowTracking: !this.state.allowTracking
      });
    }
  }, {
    key: "componentDidUpdate",
    value: function () {
      var _componentDidUpdate = asyncToGenerator_default()( /*#__PURE__*/regeneratorRuntime.mark(function _callee(prevProps, prevState) {
        var _this$props, hasErrors, isRequesting, onClose, onContinue, createNotice, isLoadingScripts, isRequestSuccessful, isRequestError;

        return regeneratorRuntime.wrap(function _callee$(_context) {
          while (1) {
            switch (_context.prev = _context.next) {
              case 0:
                _this$props = this.props, hasErrors = _this$props.hasErrors, isRequesting = _this$props.isRequesting, onClose = _this$props.onClose, onContinue = _this$props.onContinue, createNotice = _this$props.createNotice;
                isLoadingScripts = this.state.isLoadingScripts;
                isRequestSuccessful = !isRequesting && !isLoadingScripts && (prevProps.isRequesting || prevState.isLoadingScripts) && !hasErrors;
                isRequestError = !isRequesting && prevProps.isRequesting && hasErrors;

                if (isRequestSuccessful) {
                  onClose();
                  onContinue();
                }

                if (isRequestError) {
                  createNotice('error', Object(external_this_wp_i18n_["__"])('There was a problem updating your preferences.', 'woocommerce'));
                  onClose();
                }

              case 6:
              case "end":
                return _context.stop();
            }
          }
        }, _callee, this);
      }));

      function componentDidUpdate(_x, _x2) {
        return _componentDidUpdate.apply(this, arguments);
      }

      return componentDidUpdate;
    }()
  }, {
    key: "updateTracking",
    value: function updateTracking() {
      var _this2 = this;

      var allowTracking = this.state.allowTracking;
      var updateOptions = this.props.updateOptions;

      if (allowTracking && typeof window.wcTracks.enable === 'function') {
        this.setState({
          isLoadingScripts: true
        });
        window.wcTracks.enable(function () {
          _this2.setState({
            isLoadingScripts: false
          });
        });
      } else if (!allowTracking) {
        window.wcTracks.isEnabled = false;
      }

      var trackingValue = allowTracking ? 'yes' : 'no';
      updateOptions({
        woocommerce_allow_tracking: trackingValue
      });
    }
  }, {
    key: "render",
    value: function render() {
      var _this3 = this;

      var allowTracking = this.state.allowTracking;
      var isRequesting = this.props.isRequesting;
      var trackingMessage = lib_default()({
        mixedString: Object(external_this_wp_i18n_["__"])('Get improved features and faster fixes by sharing non-sensitive data via {{link}}usage tracking{{/link}} ' + 'that shows us how WooCommerce is used. No personal data is tracked or stored.', 'woocommerce'),
        components: {
          link: Object(external_this_wp_element_["createElement"])(external_this_wc_components_["Link"], {
            href: "https://woocommerce.com/usage-tracking",
            target: "_blank",
            type: "external"
          })
        }
      });
      return Object(external_this_wp_element_["createElement"])(modal["a" /* default */], {
        title: Object(external_this_wp_i18n_["__"])('Build a better WooCommerce', 'woocommerce'),
        onRequestClose: function onRequestClose() {
          return _this3.props.onClose();
        },
        className: "woocommerce-profile-wizard__usage-modal"
      }, Object(external_this_wp_element_["createElement"])("div", {
        className: "woocommerce-profile-wizard__usage-wrapper"
      }, Object(external_this_wp_element_["createElement"])("div", {
        className: "woocommerce-profile-wizard__usage-modal-message"
      }, trackingMessage), Object(external_this_wp_element_["createElement"])("div", {
        className: "woocommerce-profile-wizard__tracking"
      }, Object(external_this_wp_element_["createElement"])(checkbox_control["a" /* default */], {
        className: "woocommerce-profile-wizard__tracking-checkbox",
        checked: allowTracking,
        label: Object(external_this_wp_i18n_["__"])('Yes, count me in!', 'woocommerce'),
        onChange: this.onTrackingChange
      }), Object(external_this_wp_element_["createElement"])(form_toggle["a" /* default */], {
        "aria-hidden": "true",
        checked: allowTracking,
        onChange: this.onTrackingChange,
        onClick: function onClick(e) {
          return e.stopPropagation();
        },
        tabIndex: "-1"
      })), Object(external_this_wp_element_["createElement"])(build_module_button["a" /* default */], {
        isPrimary: true,
        isDefault: true,
        isBusy: isRequesting,
        onClick: function onClick() {
          return _this3.updateTracking();
        }
      }, Object(external_this_wp_i18n_["__"])('Continue', 'woocommerce'))));
    }
  }]);

  return UsageModal;
}(external_this_wp_element_["Component"]);

/* harmony default export */ var usage_modal = (Object(compose["a" /* default */])(Object(with_select["a" /* default */])(function (select) {
  var _select = select('wc-api'),
      getOptions = _select.getOptions,
      getOptionsError = _select.getOptionsError,
      isUpdateOptionsRequesting = _select.isUpdateOptionsRequesting;

  var options = getOptions(['woocommerce_allow_tracking']);
  var allowTracking = Object(external_lodash_["get"])(options, ['woocommerce_allow_tracking'], false) === 'yes';
  var isRequesting = Boolean(isUpdateOptionsRequesting(['woocommerce_allow_tracking']));
  var hasErrors = Boolean(getOptionsError(['woocommerce_allow_tracking']));
  return {
    allowTracking: allowTracking,
    isRequesting: isRequesting,
    hasErrors: hasErrors
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
}))(usage_modal_UsageModal));
// CONCATENATED MODULE: ./client/profile-wizard/steps/store-details.js










function store_details_createSuper(Derived) { var hasNativeReflectConstruct = store_details_isNativeReflectConstruct(); return function () { var Super = getPrototypeOf_default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = getPrototypeOf_default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return possibleConstructorReturn_default()(this, result); }; }

function store_details_isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/**
 * External dependencies
 */







/**
 * WooCommerce dependencies
 */




/**
 * Internal dependencies
 */







var store_details_StoreDetails = /*#__PURE__*/function (_Component) {
  inherits_default()(StoreDetails, _Component);

  var _super = store_details_createSuper(StoreDetails);

  function StoreDetails(props) {
    var _this;

    classCallCheck_default()(this, StoreDetails);

    _this = _super.apply(this, arguments);
    var profileItems = props.profileItems,
        settings = props.settings;
    _this.state = {
      showUsageModal: false
    }; // Check if a store address is set so that we don't default
    // to WooCommerce's default country of the UK.

    var countryState = settings.woocommerce_store_address && settings.woocommerce_default_country || '';
    _this.initialValues = {
      addressLine1: settings.woocommerce_store_address || '',
      addressLine2: settings.woocommerce_store_address_2 || '',
      city: settings.woocommerce_store_city || '',
      countryState: countryState,
      postCode: settings.woocommerce_store_postcode || '',
      isClient: profileItems.setup_client || false
    };
    _this.onContinue = _this.onContinue.bind(assertThisInitialized_default()(_this));
    _this.onSubmit = _this.onSubmit.bind(assertThisInitialized_default()(_this));
    return _this;
  }

  createClass_default()(StoreDetails, [{
    key: "componentWillUnmount",
    value: function componentWillUnmount() {
      external_this_wp_apiFetch_default()({
        path: '/wc-admin/onboarding/tasks/create_store_pages',
        method: 'POST'
      });
    }
  }, {
    key: "deriveCurrencySettings",
    value: function deriveCurrencySettings(countryState) {
      if (!countryState) {
        return null;
      }

      var region = Object(utils["b" /* getCurrencyRegion */])(countryState);
      var currencyData = Object(external_this_wc_currency_["getCurrencyData"])();
      return currencyData[region] || currencyData.US;
    }
  }, {
    key: "onSubmit",
    value: function onSubmit() {
      this.setState({
        showUsageModal: true
      });
    }
  }, {
    key: "onContinue",
    value: function () {
      var _onContinue = asyncToGenerator_default()( /*#__PURE__*/regeneratorRuntime.mark(function _callee(values) {
        var _this$props, createNotice, goToNextStep, isSettingsError, updateProfileItems, isProfileItemsError, updateAndPersistSettingsForGroup, profileItems, currencySettings, Currency, profileItemsToUpdate, region, cbdSlug, trimmedIndustries;

        return regeneratorRuntime.wrap(function _callee$(_context) {
          while (1) {
            switch (_context.prev = _context.next) {
              case 0:
                _this$props = this.props, createNotice = _this$props.createNotice, goToNextStep = _this$props.goToNextStep, isSettingsError = _this$props.isSettingsError, updateProfileItems = _this$props.updateProfileItems, isProfileItemsError = _this$props.isProfileItemsError, updateAndPersistSettingsForGroup = _this$props.updateAndPersistSettingsForGroup, profileItems = _this$props.profileItems;
                currencySettings = this.deriveCurrencySettings(values.countryState);
                Currency = this.context;
                Currency.setCurrency(currencySettings);
                Object(tracks["b" /* recordEvent */])('storeprofiler_store_details_continue', {
                  store_country: Object(utils["a" /* getCountryCode */])(values.countryState),
                  derived_currency: currencySettings.code,
                  setup_client: values.isClient
                });
                _context.next = 7;
                return updateAndPersistSettingsForGroup('general', {
                  general: {
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

              case 7:
                profileItemsToUpdate = {
                  setup_client: values.isClient
                };
                region = Object(utils["b" /* getCurrencyRegion */])(values.countryState);
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
                  cbdSlug = 'cbd-other-hemp-derived-products';
                  trimmedIndustries = profileItems.industry.filter(function (industry) {
                    return cbdSlug !== industry && cbdSlug !== industry.slug;
                  });
                  profileItemsToUpdate.industry = trimmedIndustries;
                }

                _context.next = 12;
                return updateProfileItems(profileItemsToUpdate);

              case 12:
                if (!isSettingsError && !isProfileItemsError) {
                  goToNextStep();
                } else {
                  createNotice('error', Object(external_this_wp_i18n_["__"])('There was a problem saving your store details.', 'woocommerce'));
                }

              case 13:
              case "end":
                return _context.stop();
            }
          }
        }, _callee, this);
      }));

      function onContinue(_x) {
        return _onContinue.apply(this, arguments);
      }

      return onContinue;
    }()
  }, {
    key: "render",
    value: function render() {
      var _this2 = this;

      var showUsageModal = this.state.showUsageModal;
      return Object(external_this_wp_element_["createElement"])(external_this_wp_element_["Fragment"], null, Object(external_this_wp_element_["createElement"])(external_this_wc_components_["H"], {
        className: "woocommerce-profile-wizard__header-title"
      }, Object(external_this_wp_i18n_["__"])('Where is your store based?', 'woocommerce')), Object(external_this_wp_element_["createElement"])(external_this_wc_components_["H"], {
        className: "woocommerce-profile-wizard__header-subtitle"
      }, Object(external_this_wp_i18n_["__"])('This will help us configure your store and get you started quickly', 'woocommerce')), Object(external_this_wp_element_["createElement"])(external_this_wc_components_["Card"], null, Object(external_this_wp_element_["createElement"])(external_this_wc_components_["Form"], {
        initialValues: this.initialValues,
        onSubmitCallback: this.onSubmit,
        validate: store_address["b" /* validateStoreAddress */]
      }, function (_ref) {
        var getInputProps = _ref.getInputProps,
            handleSubmit = _ref.handleSubmit,
            values = _ref.values,
            isValidForm = _ref.isValidForm,
            setValue = _ref.setValue;
        return Object(external_this_wp_element_["createElement"])(external_this_wp_element_["Fragment"], null, showUsageModal && Object(external_this_wp_element_["createElement"])(usage_modal, {
          onContinue: function onContinue() {
            return _this2.onContinue(values);
          },
          onClose: function onClose() {
            return _this2.setState({
              showUsageModal: false
            });
          }
        }), Object(external_this_wp_element_["createElement"])(store_address["a" /* StoreAddress */], {
          getInputProps: getInputProps,
          setValue: setValue
        }), Object(external_this_wp_element_["createElement"])("div", {
          className: "woocommerce-profile-wizard__client"
        }, Object(external_this_wp_element_["createElement"])(checkbox_control["a" /* default */], extends_default()({
          label: Object(external_this_wp_i18n_["__"])("I'm setting up a store for a client", 'woocommerce')
        }, getInputProps('isClient')))), Object(external_this_wp_element_["createElement"])(build_module_button["a" /* default */], {
          isPrimary: true,
          onClick: handleSubmit,
          disabled: !isValidForm
        }, Object(external_this_wp_i18n_["__"])('Continue', 'woocommerce')));
      })));
    }
  }]);

  return StoreDetails;
}(external_this_wp_element_["Component"]);

store_details_StoreDetails.contextType = currency_context["a" /* CurrencyContext */];
/* harmony default export */ var store_details = (Object(compose["a" /* default */])(Object(with_select["a" /* default */])(function (select) {
  var _select = select('wc-api'),
      getProfileItemsError = _select.getProfileItemsError,
      getProfileItems = _select.getProfileItems;

  var profileItems = getProfileItems();
  var isProfileItemsError = Boolean(getProfileItemsError());
  return {
    isProfileItemsError: isProfileItemsError,
    profileItems: profileItems
  };
}), Object(external_this_wp_data_["withSelect"])(function (select) {
  var _select2 = select(external_this_wc_data_["SETTINGS_STORE_NAME"]),
      getSettings = _select2.getSettings,
      getSettingsError = _select2.getSettingsError,
      isGetSettingsRequesting = _select2.isGetSettingsRequesting;

  var _getSettings = getSettings('general'),
      _getSettings$general = _getSettings.general,
      settings = _getSettings$general === void 0 ? {} : _getSettings$general;

  var isSettingsError = Boolean(getSettingsError('general'));
  var isSettingsRequesting = isGetSettingsRequesting('general');
  return {
    isSettingsError: isSettingsError,
    isSettingsRequesting: isSettingsRequesting,
    settings: settings
  };
}), Object(external_this_wp_data_["withDispatch"])(function (dispatch) {
  var _dispatch = dispatch('core/notices'),
      createNotice = _dispatch.createNotice;

  var _dispatch2 = dispatch('wc-api'),
      updateProfileItems = _dispatch2.updateProfileItems;

  var _dispatch3 = dispatch(external_this_wc_data_["SETTINGS_STORE_NAME"]),
      updateAndPersistSettingsForGroup = _dispatch3.updateAndPersistSettingsForGroup;

  return {
    createNotice: createNotice,
    updateProfileItems: updateProfileItems,
    updateAndPersistSettingsForGroup: updateAndPersistSettingsForGroup
  };
}))(store_details_StoreDetails));
// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/toConsumableArray.js
var toConsumableArray = __webpack_require__(32);
var toConsumableArray_default = /*#__PURE__*/__webpack_require__.n(toConsumableArray);

// EXTERNAL MODULE: external {"this":["wp","htmlEntities"]}
var external_this_wp_htmlEntities_ = __webpack_require__(69);

// EXTERNAL MODULE: ./node_modules/gridicons/dist/index.js
var dist = __webpack_require__(66);
var dist_default = /*#__PURE__*/__webpack_require__.n(dist);

// EXTERNAL MODULE: ./node_modules/@wordpress/components/build-module/tooltip/index.js
var tooltip = __webpack_require__(110);

// EXTERNAL MODULE: ./node_modules/@wordpress/components/build-module/tab-panel/index.js
var tab_panel = __webpack_require__(717);

// EXTERNAL MODULE: ./client/profile-wizard/steps/theme/style.scss
var style = __webpack_require__(890);

// EXTERNAL MODULE: ./node_modules/classnames/index.js
var classnames = __webpack_require__(10);
var classnames_default = /*#__PURE__*/__webpack_require__.n(classnames);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/esm/toConsumableArray.js + 3 modules
var esm_toConsumableArray = __webpack_require__(17);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/esm/classCallCheck.js
var esm_classCallCheck = __webpack_require__(7);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/esm/createClass.js
var esm_createClass = __webpack_require__(6);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/esm/possibleConstructorReturn.js
var esm_possibleConstructorReturn = __webpack_require__(8);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/esm/getPrototypeOf.js
var esm_getPrototypeOf = __webpack_require__(4);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/esm/assertThisInitialized.js
var esm_assertThisInitialized = __webpack_require__(5);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/esm/inherits.js + 1 modules
var esm_inherits = __webpack_require__(9);

// EXTERNAL MODULE: ./node_modules/@wordpress/is-shallow-equal/index.js
var is_shallow_equal = __webpack_require__(77);
var is_shallow_equal_default = /*#__PURE__*/__webpack_require__.n(is_shallow_equal);

// CONCATENATED MODULE: ./node_modules/@wordpress/components/build-module/drop-zone/provider.js









/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */




var _createContext = Object(external_this_wp_element_["createContext"])({
  addDropZone: function addDropZone() {},
  removeDropZone: function removeDropZone() {}
}),
    Provider = _createContext.Provider,
    Consumer = _createContext.Consumer;

var provider_getDragEventType = function getDragEventType(_ref) {
  var dataTransfer = _ref.dataTransfer;

  if (dataTransfer) {
    // Use lodash `includes` here as in the Edge browser `types` is implemented
    // as a DomStringList, whereas in other browsers it's an array. `includes`
    // happily works with both types.
    if (Object(external_lodash_["includes"])(dataTransfer.types, 'Files')) {
      return 'file';
    }

    if (Object(external_lodash_["includes"])(dataTransfer.types, 'text/html')) {
      return 'html';
    }
  }

  return 'default';
};

var isTypeSupportedByDropZone = function isTypeSupportedByDropZone(type, dropZone) {
  return type === 'file' && dropZone.onFilesDrop || type === 'html' && dropZone.onHTMLDrop || type === 'default' && dropZone.onDrop;
};

var isWithinElementBounds = function isWithinElementBounds(element, x, y) {
  var rect = element.getBoundingClientRect(); /// make sure the rect is a valid rect

  if (rect.bottom === rect.top || rect.left === rect.right) {
    return false;
  }

  return x >= rect.left && x <= rect.right && y >= rect.top && y <= rect.bottom;
};

var provider_DropZoneProvider =
/*#__PURE__*/
function (_Component) {
  Object(esm_inherits["a" /* default */])(DropZoneProvider, _Component);

  function DropZoneProvider() {
    var _this;

    Object(esm_classCallCheck["a" /* default */])(this, DropZoneProvider);

    _this = Object(esm_possibleConstructorReturn["a" /* default */])(this, Object(esm_getPrototypeOf["a" /* default */])(DropZoneProvider).apply(this, arguments)); // Event listeners

    _this.onDragOver = _this.onDragOver.bind(Object(esm_assertThisInitialized["a" /* default */])(_this));
    _this.onDrop = _this.onDrop.bind(Object(esm_assertThisInitialized["a" /* default */])(_this)); // Context methods so this component can receive data from consumers

    _this.addDropZone = _this.addDropZone.bind(Object(esm_assertThisInitialized["a" /* default */])(_this));
    _this.removeDropZone = _this.removeDropZone.bind(Object(esm_assertThisInitialized["a" /* default */])(_this)); // Utility methods

    _this.resetDragState = _this.resetDragState.bind(Object(esm_assertThisInitialized["a" /* default */])(_this));
    _this.toggleDraggingOverDocument = Object(external_lodash_["throttle"])(_this.toggleDraggingOverDocument.bind(Object(esm_assertThisInitialized["a" /* default */])(_this)), 200);
    _this.dropZones = [];
    _this.dropZoneCallbacks = {
      addDropZone: _this.addDropZone,
      removeDropZone: _this.removeDropZone
    };
    _this.state = {
      hoveredDropZone: -1,
      isDraggingOverDocument: false,
      position: null
    };
    return _this;
  }

  Object(esm_createClass["a" /* default */])(DropZoneProvider, [{
    key: "componentDidMount",
    value: function componentDidMount() {
      window.addEventListener('dragover', this.onDragOver);
      window.addEventListener('mouseup', this.resetDragState);
    }
  }, {
    key: "componentWillUnmount",
    value: function componentWillUnmount() {
      window.removeEventListener('dragover', this.onDragOver);
      window.removeEventListener('mouseup', this.resetDragState);
    }
  }, {
    key: "addDropZone",
    value: function addDropZone(dropZone) {
      this.dropZones.push(dropZone);
    }
  }, {
    key: "removeDropZone",
    value: function removeDropZone(dropZone) {
      this.dropZones = Object(external_lodash_["filter"])(this.dropZones, function (dz) {
        return dz !== dropZone;
      });
    }
  }, {
    key: "resetDragState",
    value: function resetDragState() {
      // Avoid throttled drag over handler calls
      this.toggleDraggingOverDocument.cancel();
      var _this$state = this.state,
          isDraggingOverDocument = _this$state.isDraggingOverDocument,
          hoveredDropZone = _this$state.hoveredDropZone;

      if (!isDraggingOverDocument && hoveredDropZone === -1) {
        return;
      }

      this.setState({
        hoveredDropZone: -1,
        isDraggingOverDocument: false,
        position: null
      });
      this.dropZones.forEach(function (dropZone) {
        return dropZone.setState({
          isDraggingOverDocument: false,
          isDraggingOverElement: false,
          position: null,
          type: null
        });
      });
    }
  }, {
    key: "toggleDraggingOverDocument",
    value: function toggleDraggingOverDocument(event, dragEventType) {
      var _this2 = this;

      // In some contexts, it may be necessary to capture and redirect the
      // drag event (e.g. atop an `iframe`). To accommodate this, you can
      // create an instance of CustomEvent with the original event specified
      // as the `detail` property.
      //
      // See: https://developer.mozilla.org/en-US/docs/Web/Guide/Events/Creating_and_triggering_events
      var detail = window.CustomEvent && event instanceof window.CustomEvent ? event.detail : event; // Index of hovered dropzone.

      var hoveredDropZones = Object(external_lodash_["filter"])(this.dropZones, function (dropZone) {
        return isTypeSupportedByDropZone(dragEventType, dropZone) && isWithinElementBounds(dropZone.element, detail.clientX, detail.clientY);
      }); // Find the leaf dropzone not containing another dropzone

      var hoveredDropZone = Object(external_lodash_["find"])(hoveredDropZones, function (zone) {
        return !Object(external_lodash_["some"])(hoveredDropZones, function (subZone) {
          return subZone !== zone && zone.element.parentElement.contains(subZone.element);
        });
      });
      var hoveredDropZoneIndex = this.dropZones.indexOf(hoveredDropZone);
      var position = null;

      if (hoveredDropZone) {
        var rect = hoveredDropZone.element.getBoundingClientRect();
        position = {
          x: detail.clientX - rect.left < rect.right - detail.clientX ? 'left' : 'right',
          y: detail.clientY - rect.top < rect.bottom - detail.clientY ? 'top' : 'bottom'
        };
      } // Optimisation: Only update the changed dropzones


      var toUpdate = [];

      if (!this.state.isDraggingOverDocument) {
        toUpdate = this.dropZones;
      } else if (hoveredDropZoneIndex !== this.state.hoveredDropZone) {
        if (this.state.hoveredDropZone !== -1) {
          toUpdate.push(this.dropZones[this.state.hoveredDropZone]);
        }

        if (hoveredDropZone) {
          toUpdate.push(hoveredDropZone);
        }
      } else if (hoveredDropZone && hoveredDropZoneIndex === this.state.hoveredDropZone && !Object(external_lodash_["isEqual"])(position, this.state.position)) {
        toUpdate.push(hoveredDropZone);
      } // Notifying the dropzones


      toUpdate.forEach(function (dropZone) {
        var index = _this2.dropZones.indexOf(dropZone);

        var isDraggingOverDropZone = index === hoveredDropZoneIndex;
        dropZone.setState({
          isDraggingOverDocument: isTypeSupportedByDropZone(dragEventType, dropZone),
          isDraggingOverElement: isDraggingOverDropZone,
          position: isDraggingOverDropZone ? position : null,
          type: isDraggingOverDropZone ? dragEventType : null
        });
      });
      var newState = {
        isDraggingOverDocument: true,
        hoveredDropZone: hoveredDropZoneIndex,
        position: position
      };

      if (!is_shallow_equal_default()(newState, this.state)) {
        this.setState(newState);
      }
    }
  }, {
    key: "onDragOver",
    value: function onDragOver(event) {
      this.toggleDraggingOverDocument(event, provider_getDragEventType(event));
      event.preventDefault();
    }
  }, {
    key: "onDrop",
    value: function onDrop(event) {
      // This seemingly useless line has been shown to resolve a Safari issue
      // where files dragged directly from the dock are not recognized
      event.dataTransfer && event.dataTransfer.files.length; // eslint-disable-line no-unused-expressions

      var _this$state2 = this.state,
          position = _this$state2.position,
          hoveredDropZone = _this$state2.hoveredDropZone;
      var dragEventType = provider_getDragEventType(event);
      var dropZone = this.dropZones[hoveredDropZone];
      this.resetDragState();

      if (dropZone) {
        switch (dragEventType) {
          case 'file':
            dropZone.onFilesDrop(Object(esm_toConsumableArray["a" /* default */])(event.dataTransfer.files), position);
            break;

          case 'html':
            dropZone.onHTMLDrop(event.dataTransfer.getData('text/html'), position);
            break;

          case 'default':
            dropZone.onDrop(event, position);
        }
      }

      event.stopPropagation();
      event.preventDefault();
    }
  }, {
    key: "render",
    value: function render() {
      return Object(external_this_wp_element_["createElement"])("div", {
        onDrop: this.onDrop,
        className: "components-drop-zone__provider"
      }, Object(external_this_wp_element_["createElement"])(Provider, {
        value: this.dropZoneCallbacks
      }, this.props.children));
    }
  }]);

  return DropZoneProvider;
}(external_this_wp_element_["Component"]);

/* harmony default export */ var provider = (provider_DropZoneProvider);

//# sourceMappingURL=provider.js.map
// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/esm/extends.js
var esm_extends = __webpack_require__(11);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/esm/objectWithoutProperties.js
var objectWithoutProperties = __webpack_require__(16);

// EXTERNAL MODULE: ./node_modules/@wordpress/components/build-module/icon-button/index.js
var icon_button = __webpack_require__(85);

// CONCATENATED MODULE: ./node_modules/@wordpress/components/build-module/form-file-upload/index.js










/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */



var form_file_upload_FormFileUpload =
/*#__PURE__*/
function (_Component) {
  Object(esm_inherits["a" /* default */])(FormFileUpload, _Component);

  function FormFileUpload() {
    var _this;

    Object(esm_classCallCheck["a" /* default */])(this, FormFileUpload);

    _this = Object(esm_possibleConstructorReturn["a" /* default */])(this, Object(esm_getPrototypeOf["a" /* default */])(FormFileUpload).apply(this, arguments));
    _this.openFileDialog = _this.openFileDialog.bind(Object(esm_assertThisInitialized["a" /* default */])(_this));
    _this.bindInput = _this.bindInput.bind(Object(esm_assertThisInitialized["a" /* default */])(_this));
    return _this;
  }

  Object(esm_createClass["a" /* default */])(FormFileUpload, [{
    key: "openFileDialog",
    value: function openFileDialog() {
      this.input.click();
    }
  }, {
    key: "bindInput",
    value: function bindInput(ref) {
      this.input = ref;
    }
  }, {
    key: "render",
    value: function render() {
      var _this$props = this.props,
          accept = _this$props.accept,
          children = _this$props.children,
          _this$props$icon = _this$props.icon,
          icon = _this$props$icon === void 0 ? 'upload' : _this$props$icon,
          _this$props$multiple = _this$props.multiple,
          multiple = _this$props$multiple === void 0 ? false : _this$props$multiple,
          onChange = _this$props.onChange,
          render = _this$props.render,
          props = Object(objectWithoutProperties["a" /* default */])(_this$props, ["accept", "children", "icon", "multiple", "onChange", "render"]);

      var ui = render ? render({
        openFileDialog: this.openFileDialog
      }) : Object(external_this_wp_element_["createElement"])(icon_button["a" /* default */], Object(esm_extends["a" /* default */])({
        icon: icon,
        onClick: this.openFileDialog
      }, props), children);
      return Object(external_this_wp_element_["createElement"])("div", {
        className: "components-form-file-upload"
      }, ui, Object(external_this_wp_element_["createElement"])("input", {
        type: "file",
        ref: this.bindInput,
        multiple: multiple,
        style: {
          display: 'none'
        },
        accept: accept,
        onChange: onChange
      }));
    }
  }]);

  return FormFileUpload;
}(external_this_wp_element_["Component"]);

/* harmony default export */ var form_file_upload = (form_file_upload_FormFileUpload);
//# sourceMappingURL=index.js.map
// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/esm/defineProperty.js
var esm_defineProperty = __webpack_require__(13);

// EXTERNAL MODULE: ./node_modules/@wordpress/components/build-module/dashicon/index.js
var dashicon = __webpack_require__(80);

// CONCATENATED MODULE: ./node_modules/@wordpress/components/build-module/drop-zone/index.js










/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */



/**
 * Internal dependencies
 */




var drop_zone_DropZone = function DropZone(props) {
  return Object(external_this_wp_element_["createElement"])(Consumer, null, function (_ref) {
    var addDropZone = _ref.addDropZone,
        removeDropZone = _ref.removeDropZone;
    return Object(external_this_wp_element_["createElement"])(drop_zone_DropZoneComponent, Object(esm_extends["a" /* default */])({
      addDropZone: addDropZone,
      removeDropZone: removeDropZone
    }, props));
  });
};

var drop_zone_DropZoneComponent =
/*#__PURE__*/
function (_Component) {
  Object(esm_inherits["a" /* default */])(DropZoneComponent, _Component);

  function DropZoneComponent() {
    var _this;

    Object(esm_classCallCheck["a" /* default */])(this, DropZoneComponent);

    _this = Object(esm_possibleConstructorReturn["a" /* default */])(this, Object(esm_getPrototypeOf["a" /* default */])(DropZoneComponent).apply(this, arguments));
    _this.dropZoneElement = Object(external_this_wp_element_["createRef"])();
    _this.dropZone = {
      element: null,
      onDrop: _this.props.onDrop,
      onFilesDrop: _this.props.onFilesDrop,
      onHTMLDrop: _this.props.onHTMLDrop,
      setState: _this.setState.bind(Object(esm_assertThisInitialized["a" /* default */])(_this))
    };
    _this.state = {
      isDraggingOverDocument: false,
      isDraggingOverElement: false,
      position: null,
      type: null
    };
    return _this;
  }

  Object(esm_createClass["a" /* default */])(DropZoneComponent, [{
    key: "componentDidMount",
    value: function componentDidMount() {
      // Set element after the component has a node assigned in the DOM
      this.dropZone.element = this.dropZoneElement.current;
      this.props.addDropZone(this.dropZone);
    }
  }, {
    key: "componentWillUnmount",
    value: function componentWillUnmount() {
      this.props.removeDropZone(this.dropZone);
    }
  }, {
    key: "render",
    value: function render() {
      var _this$props = this.props,
          className = _this$props.className,
          label = _this$props.label,
          onFilesDrop = _this$props.onFilesDrop,
          onHTMLDrop = _this$props.onHTMLDrop,
          onDrop = _this$props.onDrop;
      var _this$state = this.state,
          isDraggingOverDocument = _this$state.isDraggingOverDocument,
          isDraggingOverElement = _this$state.isDraggingOverElement,
          position = _this$state.position,
          type = _this$state.type;
      var classes = classnames_default()('components-drop-zone', className, Object(esm_defineProperty["a" /* default */])({
        'is-active': (isDraggingOverDocument || isDraggingOverElement) && (type === 'file' && onFilesDrop || type === 'html' && onHTMLDrop || type === 'default' && onDrop),
        'is-dragging-over-document': isDraggingOverDocument,
        'is-dragging-over-element': isDraggingOverElement,
        'is-close-to-top': position && position.y === 'top',
        'is-close-to-bottom': position && position.y === 'bottom',
        'is-close-to-left': position && position.x === 'left',
        'is-close-to-right': position && position.x === 'right'
      }, "is-dragging-".concat(type), !!type));
      var children;

      if (isDraggingOverElement) {
        children = Object(external_this_wp_element_["createElement"])("div", {
          className: "components-drop-zone__content"
        }, Object(external_this_wp_element_["createElement"])(dashicon["a" /* default */], {
          icon: "upload",
          size: "40",
          className: "components-drop-zone__content-icon"
        }), Object(external_this_wp_element_["createElement"])("span", {
          className: "components-drop-zone__content-text"
        }, label ? label : Object(external_this_wp_i18n_["__"])('Drop files to upload')));
      }

      return Object(external_this_wp_element_["createElement"])("div", {
        ref: this.dropZoneElement,
        className: classes
      }, children);
    }
  }]);

  return DropZoneComponent;
}(external_this_wp_element_["Component"]);

/* harmony default export */ var drop_zone = (drop_zone_DropZone);
//# sourceMappingURL=index.js.map
// EXTERNAL MODULE: ./node_modules/prop-types/index.js
var prop_types = __webpack_require__(1);
var prop_types_default = /*#__PURE__*/__webpack_require__.n(prop_types);

// CONCATENATED MODULE: ./client/profile-wizard/steps/theme/uploader.js








function uploader_createSuper(Derived) { var hasNativeReflectConstruct = uploader_isNativeReflectConstruct(); return function () { var Super = getPrototypeOf_default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = getPrototypeOf_default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return possibleConstructorReturn_default()(this, result); }; }

function uploader_isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/**
 * External dependencies
 */










/**
 * WooCommerce dependencies
 */



var uploader_ThemeUploader = /*#__PURE__*/function (_Component) {
  inherits_default()(ThemeUploader, _Component);

  var _super = uploader_createSuper(ThemeUploader);

  function ThemeUploader() {
    var _this;

    classCallCheck_default()(this, ThemeUploader);

    _this = _super.call(this);
    _this.state = {
      isUploading: false
    };
    _this.handleFilesUpload = _this.handleFilesUpload.bind(assertThisInitialized_default()(_this));
    _this.handleFilesDrop = _this.handleFilesDrop.bind(assertThisInitialized_default()(_this));
    return _this;
  }

  createClass_default()(ThemeUploader, [{
    key: "handleFilesDrop",
    value: function handleFilesDrop(files) {
      var file = files[0];
      this.uploadTheme(file);
    }
  }, {
    key: "handleFilesUpload",
    value: function handleFilesUpload(e) {
      var file = e.target.files[0];
      this.uploadTheme(file);
    }
  }, {
    key: "uploadTheme",
    value: function uploadTheme(file) {
      var _this2 = this;

      var _this$props = this.props,
          createNotice = _this$props.createNotice,
          onUploadComplete = _this$props.onUploadComplete;
      this.setState({
        isUploading: true
      });
      var body = new window.FormData();
      body.append('pluginzip', file);
      return external_this_wp_apiFetch_default()({
        path: '/wc-admin/themes',
        method: 'POST',
        body: body
      }).then(function (response) {
        onUploadComplete(response);

        _this2.setState({
          isUploading: false
        });

        createNotice(response.status, response.message);
      }).catch(function (error) {
        _this2.setState({
          isUploading: false
        });

        if (error && error.message) {
          createNotice('error', error.message);
        }
      });
    }
  }, {
    key: "render",
    value: function render() {
      var className = this.props.className;
      var isUploading = this.state.isUploading;
      var classes = classnames_default()('woocommerce-theme-uploader', className, {
        'is-uploading': isUploading
      });
      return Object(external_this_wp_element_["createElement"])(external_this_wc_components_["Card"], {
        className: classes
      }, Object(external_this_wp_element_["createElement"])(provider, null, !isUploading ? Object(external_this_wp_element_["createElement"])(external_this_wp_element_["Fragment"], null, Object(external_this_wp_element_["createElement"])(form_file_upload, {
        accept: ".zip",
        onChange: this.handleFilesUpload
      }, Object(external_this_wp_element_["createElement"])(dist_default.a, {
        icon: "cloud-upload"
      }), Object(external_this_wp_element_["createElement"])(external_this_wc_components_["H"], {
        className: "woocommerce-theme-uploader__title"
      }, Object(external_this_wp_i18n_["__"])('Upload a theme', 'woocommerce')), Object(external_this_wp_element_["createElement"])("p", null, Object(external_this_wp_i18n_["__"])('Drop a theme zip file here to upload', 'woocommerce'))), Object(external_this_wp_element_["createElement"])(drop_zone, {
        label: Object(external_this_wp_i18n_["__"])('Drop your theme zip file here', 'woocommerce'),
        onFilesDrop: this.handleFilesDrop
      })) : Object(external_this_wp_element_["createElement"])(external_this_wp_element_["Fragment"], null, Object(external_this_wp_element_["createElement"])(external_this_wc_components_["Spinner"], null), Object(external_this_wp_element_["createElement"])(external_this_wc_components_["H"], {
        className: "woocommerce-theme-uploader__title"
      }, Object(external_this_wp_i18n_["__"])('Uploading theme', 'woocommerce')), Object(external_this_wp_element_["createElement"])("p", null, Object(external_this_wp_i18n_["__"])('Your theme is being uploaded', 'woocommerce')))));
    }
  }]);

  return ThemeUploader;
}(external_this_wp_element_["Component"]);

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
/* harmony default export */ var uploader = (Object(compose["a" /* default */])(Object(external_this_wp_data_["withDispatch"])(function (dispatch) {
  var _dispatch = dispatch('core/notices'),
      createNotice = _dispatch.createNotice;

  return {
    createNotice: createNotice
  };
}))(uploader_ThemeUploader));
// CONCATENATED MODULE: ./client/profile-wizard/steps/theme/preview.js








function preview_createSuper(Derived) { var hasNativeReflectConstruct = preview_isNativeReflectConstruct(); return function () { var Super = getPrototypeOf_default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = getPrototypeOf_default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return possibleConstructorReturn_default()(this, result); }; }

function preview_isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/**
 * External dependencies
 */





/**
 * WooCommerce dependencies
 */


/**
 * Internal dependencies
 */


var devices = [{
  key: 'mobile',
  icon: 'phone_android'
}, {
  key: 'tablet',
  icon: 'tablet_android'
}, {
  key: 'desktop',
  icon: 'desktop_windows'
}];

var preview_ThemePreview = /*#__PURE__*/function (_Component) {
  inherits_default()(ThemePreview, _Component);

  var _super = preview_createSuper(ThemePreview);

  function ThemePreview() {
    var _this;

    classCallCheck_default()(this, ThemePreview);

    _this = _super.apply(this, arguments);
    _this.state = {
      device: 'desktop'
    };
    _this.handleDeviceClick = _this.handleDeviceClick.bind(assertThisInitialized_default()(_this));
    return _this;
  }

  createClass_default()(ThemePreview, [{
    key: "handleDeviceClick",
    value: function handleDeviceClick(device) {
      var theme = this.props.theme;
      Object(tracks["b" /* recordEvent */])('storeprofiler_store_theme_demo_device', {
        device: device,
        theme: theme.slug
      });
      this.setState({
        device: device
      });
    }
  }, {
    key: "render",
    value: function render() {
      var _this2 = this;

      var _this$props = this.props,
          isBusy = _this$props.isBusy,
          onChoose = _this$props.onChoose,
          onClose = _this$props.onClose,
          theme = _this$props.theme;
      var demoUrl = theme.demo_url,
          slug = theme.slug,
          title = theme.title;
      var currentDevice = this.state.device;
      return Object(external_this_wp_element_["createElement"])("div", {
        className: "woocommerce-theme-preview"
      }, Object(external_this_wp_element_["createElement"])("div", {
        className: "woocommerce-theme-preview__toolbar"
      }, Object(external_this_wp_element_["createElement"])(build_module_button["a" /* default */], {
        className: "woocommerce-theme-preview__close",
        onClick: onClose
      }, Object(external_this_wp_element_["createElement"])("i", {
        className: "material-icons-outlined"
      }, "close")), Object(external_this_wp_element_["createElement"])("div", {
        className: "woocommerce-theme-preview__theme-name"
      }, lib_default()({
        /* translators: Describing who a previewed theme is developed by. E.g., Storefront developed by WooCommerce */
        mixedString: Object(external_this_wp_i18n_["sprintf"])(Object(external_this_wp_i18n_["__"])('{{strong}}%s{{/strong}} developed by WooCommerce', 'woocommerce'), title),
        components: {
          strong: Object(external_this_wp_element_["createElement"])("strong", null)
        }
      })), Object(external_this_wp_element_["createElement"])("div", {
        className: "woocommerce-theme-preview__devices"
      }, devices.map(function (device) {
        return Object(external_this_wp_element_["createElement"])(build_module_button["a" /* default */], {
          key: device.key,
          className: classnames_default()('woocommerce-theme-preview__device', {
            'is-selected': device.key === currentDevice
          }),
          onClick: function onClick() {
            return _this2.handleDeviceClick(device.key);
          }
        }, Object(external_this_wp_element_["createElement"])("i", {
          className: "material-icons-outlined"
        }, device.icon));
      })), Object(external_this_wp_element_["createElement"])(build_module_button["a" /* default */], {
        isPrimary: true,
        onClick: function onClick() {
          return onChoose(slug, 'preview');
        },
        isBusy: isBusy
      }, Object(external_this_wp_i18n_["__"])('Choose', 'woocommerce'))), Object(external_this_wp_element_["createElement"])(external_this_wc_components_["WebPreview"], {
        src: demoUrl,
        title: title,
        className: "is-".concat(currentDevice)
      }));
    }
  }]);

  return ThemePreview;
}(external_this_wp_element_["Component"]);

/* harmony default export */ var preview = (preview_ThemePreview);
// CONCATENATED MODULE: ./client/profile-wizard/steps/theme/index.js










function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { defineProperty_default()(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function theme_createSuper(Derived) { var hasNativeReflectConstruct = theme_isNativeReflectConstruct(); return function () { var Super = getPrototypeOf_default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = getPrototypeOf_default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return possibleConstructorReturn_default()(this, result); }; }

function theme_isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/**
 * External dependencies
 */








/**
 * WooCommerce dependencies
 */



/**
 * Internal dependencies
 */








var theme_Theme = /*#__PURE__*/function (_Component) {
  inherits_default()(Theme, _Component);

  var _super = theme_createSuper(Theme);

  function Theme() {
    var _this;

    classCallCheck_default()(this, Theme);

    _this = _super.apply(this, arguments);
    _this.state = {
      activeTab: 'all',
      chosen: null,
      demo: null,
      uploadedThemes: []
    };
    _this.handleUploadComplete = _this.handleUploadComplete.bind(assertThisInitialized_default()(_this));
    _this.onChoose = _this.onChoose.bind(assertThisInitialized_default()(_this));
    _this.onClosePreview = _this.onClosePreview.bind(assertThisInitialized_default()(_this));
    _this.onSelectTab = _this.onSelectTab.bind(assertThisInitialized_default()(_this));
    _this.openDemo = _this.openDemo.bind(assertThisInitialized_default()(_this));
    _this.skipStep = _this.skipStep.bind(assertThisInitialized_default()(_this));
    return _this;
  }

  createClass_default()(Theme, [{
    key: "componentDidUpdate",
    value: function componentDidUpdate(prevProps) {
      var _this$props = this.props,
          isError = _this$props.isError,
          isGetProfileItemsRequesting = _this$props.isGetProfileItemsRequesting,
          createNotice = _this$props.createNotice;
      var chosen = this.state.chosen;
      var isRequestSuccessful = !isGetProfileItemsRequesting && prevProps.isGetProfileItemsRequesting && !isError && chosen;
      var isRequestError = !isGetProfileItemsRequesting && prevProps.isRequesting && isError;

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

        createNotice('error', Object(external_this_wp_i18n_["__"])('There was a problem selecting your store theme.', 'woocommerce'));
      }
    }
  }, {
    key: "onChoose",
    value: function onChoose(theme) {
      var location = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : '';
      var updateProfileItems = this.props.updateProfileItems;
      var isInstalled = theme.is_installed,
          price = theme.price,
          slug = theme.slug;

      var _getSetting = Object(client_settings["g" /* getSetting */])('onboarding', {}),
          _getSetting$activeThe = _getSetting.activeTheme,
          activeTheme = _getSetting$activeThe === void 0 ? '' : _getSetting$activeThe;

      this.setState({
        chosen: slug
      });
      Object(tracks["b" /* recordEvent */])('storeprofiler_store_theme_choose', {
        theme: slug,
        location: location
      });

      if (slug !== activeTheme && Object(utils["c" /* getPriceValue */])(price) <= 0) {
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
  }, {
    key: "installTheme",
    value: function installTheme(slug) {
      var _this2 = this;

      var createNotice = this.props.createNotice;
      external_this_wp_apiFetch_default()({
        path: '/wc-admin/onboarding/themes/install?theme=' + slug,
        method: 'POST'
      }).then(function (response) {
        createNotice('success', Object(external_this_wp_i18n_["sprintf"])(Object(external_this_wp_i18n_["__"])('%s was installed on your site.', 'woocommerce'), response.name));

        _this2.activateTheme(slug);
      }).catch(function (response) {
        _this2.setState({
          chosen: null
        });

        createNotice('error', response.message);
      });
    }
  }, {
    key: "activateTheme",
    value: function activateTheme(slug) {
      var _this3 = this;

      var _this$props2 = this.props,
          createNotice = _this$props2.createNotice,
          updateProfileItems = _this$props2.updateProfileItems;
      external_this_wp_apiFetch_default()({
        path: '/wc-admin/onboarding/themes/activate?theme=' + slug,
        method: 'POST'
      }).then(function (response) {
        createNotice('success', Object(external_this_wp_i18n_["sprintf"])(Object(external_this_wp_i18n_["__"])('%s was activated on your site.', 'woocommerce'), response.name));
        Object(client_settings["h" /* setSetting */])('onboarding', _objectSpread({}, Object(client_settings["g" /* getSetting */])('onboarding', {}), {
          activeTheme: response.slug
        }));
        updateProfileItems({
          theme: slug
        });
      }).catch(function (response) {
        _this3.setState({
          chosen: null
        });

        createNotice('error', response.message);
      });
    }
  }, {
    key: "onClosePreview",
    value: function onClosePreview() {
      var demo = this.state.demo;
      Object(tracks["b" /* recordEvent */])('storeprofiler_store_theme_demo_close', {
        theme: demo.slug
      });
      document.body.classList.remove('woocommerce-theme-preview-active');
      this.setState({
        demo: null
      });
    }
  }, {
    key: "openDemo",
    value: function openDemo(theme) {
      Object(tracks["b" /* recordEvent */])('storeprofiler_store_theme_live_demo', {
        theme: theme.slug
      });
      document.body.classList.add('woocommerce-theme-preview-active');
      this.setState({
        demo: theme
      });
    }
  }, {
    key: "skipStep",
    value: function skipStep() {
      var _getSetting2 = Object(client_settings["g" /* getSetting */])('onboarding', {}),
          _getSetting2$activeTh = _getSetting2.activeTheme,
          activeTheme = _getSetting2$activeTh === void 0 ? '' : _getSetting2$activeTh;

      Object(tracks["b" /* recordEvent */])('storeprofiler_store_theme_skip_step', {
        activeTheme: activeTheme
      });
      this.props.goToNextStep();
    }
  }, {
    key: "renderTheme",
    value: function renderTheme(theme) {
      var _this4 = this;

      var demoUrl = theme.demo_url,
          hasSupport = theme.has_woocommerce_support,
          image = theme.image,
          slug = theme.slug,
          title = theme.title;
      var chosen = this.state.chosen;

      var _getSetting3 = Object(client_settings["g" /* getSetting */])('onboarding', {}),
          _getSetting3$activeTh = _getSetting3.activeTheme,
          activeTheme = _getSetting3$activeTh === void 0 ? '' : _getSetting3$activeTh;

      return Object(external_this_wp_element_["createElement"])(external_this_wc_components_["Card"], {
        className: "woocommerce-profile-wizard__theme",
        key: theme.slug
      }, image && Object(external_this_wp_element_["createElement"])("div", {
        className: "woocommerce-profile-wizard__theme-image",
        style: {
          backgroundImage: "url(".concat(image, ")")
        },
        role: "img",
        "aria-label": title
      }), Object(external_this_wp_element_["createElement"])("div", {
        className: "woocommerce-profile-wizard__theme-details"
      }, Object(external_this_wp_element_["createElement"])(external_this_wc_components_["H"], {
        className: "woocommerce-profile-wizard__theme-name"
      }, title, !hasSupport && Object(external_this_wp_element_["createElement"])(tooltip["a" /* default */], {
        text: Object(external_this_wp_i18n_["__"])('This theme does not support WooCommerce.', 'woocommerce')
      }, Object(external_this_wp_element_["createElement"])("span", null, Object(external_this_wp_element_["createElement"])(dist_default.a, {
        icon: "info",
        role: "img",
        "aria-hidden": "true",
        focusable: "false"
      })))), Object(external_this_wp_element_["createElement"])("p", {
        className: "woocommerce-profile-wizard__theme-status"
      }, this.getThemeStatus(theme)), Object(external_this_wp_element_["createElement"])("div", {
        className: "woocommerce-profile-wizard__theme-actions"
      }, slug === activeTheme ? Object(external_this_wp_element_["createElement"])(build_module_button["a" /* default */], {
        isPrimary: true,
        onClick: function onClick() {
          return _this4.onChoose(theme, 'card');
        },
        isBusy: chosen === slug
      }, Object(external_this_wp_i18n_["__"])('Continue with my active theme', 'woocommerce')) : Object(external_this_wp_element_["createElement"])(build_module_button["a" /* default */], {
        isDefault: true,
        onClick: function onClick() {
          return _this4.onChoose(theme, 'card');
        },
        isBusy: chosen === slug
      }, Object(external_this_wp_i18n_["__"])('Choose', 'woocommerce')), demoUrl && Object(external_this_wp_element_["createElement"])(build_module_button["a" /* default */], {
        isTertiary: true,
        onClick: function onClick() {
          return _this4.openDemo(theme);
        }
      }, Object(external_this_wp_i18n_["__"])('Live demo', 'woocommerce')))));
    }
  }, {
    key: "getThemeStatus",
    value: function getThemeStatus(theme) {
      var isInstalled = theme.is_installed,
          price = theme.price,
          slug = theme.slug;

      var _getSetting4 = Object(client_settings["g" /* getSetting */])('onboarding', {}),
          _getSetting4$activeTh = _getSetting4.activeTheme,
          activeTheme = _getSetting4$activeTh === void 0 ? '' : _getSetting4$activeTh;

      if (activeTheme === slug) {
        return Object(external_this_wp_i18n_["__"])('Currently active theme', 'woocommerce');
      }

      if (isInstalled) {
        return Object(external_this_wp_i18n_["__"])('Installed', 'woocommerce');
      } else if (Object(utils["c" /* getPriceValue */])(price) <= 0) {
        return Object(external_this_wp_i18n_["__"])('Free', 'woocommerce');
      }

      return Object(external_this_wp_i18n_["sprintf"])(Object(external_this_wp_i18n_["__"])('%s per year', 'woocommerce'), Object(external_this_wp_htmlEntities_["decodeEntities"])(price));
    }
  }, {
    key: "doesActiveThemeSupportWooCommerce",
    value: function doesActiveThemeSupportWooCommerce() {
      var _getSetting5 = Object(client_settings["g" /* getSetting */])('onboarding', {}),
          _getSetting5$activeTh = _getSetting5.activeTheme,
          activeTheme = _getSetting5$activeTh === void 0 ? '' : _getSetting5$activeTh;

      var allThemes = this.getThemes();
      var currentTheme = allThemes.find(function (theme) {
        return theme.slug === activeTheme;
      });
      return currentTheme && currentTheme.has_woocommerce_support;
    }
  }, {
    key: "onSelectTab",
    value: function onSelectTab(tab) {
      Object(tracks["b" /* recordEvent */])('storeprofiler_store_theme_navigate', {
        navigation: tab
      });
      this.setState({
        activeTab: tab
      });
    }
  }, {
    key: "getPriceValue",
    value: function getPriceValue(string) {
      return Number(Object(external_this_wp_htmlEntities_["decodeEntities"])(string).replace(/[^0-9.-]+/g, ''));
    }
  }, {
    key: "getThemes",
    value: function getThemes() {
      var activeTab = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : 'all';
      var uploadedThemes = this.state.uploadedThemes;

      var _getSetting6 = Object(client_settings["g" /* getSetting */])('onboarding', {}),
          _getSetting6$activeTh = _getSetting6.activeTheme,
          activeTheme = _getSetting6$activeTh === void 0 ? '' : _getSetting6$activeTh,
          _getSetting6$themes = _getSetting6.themes,
          themes = _getSetting6$themes === void 0 ? [] : _getSetting6$themes;

      var allThemes = [].concat(toConsumableArray_default()(themes.filter(function (theme) {
        return theme && (theme.has_woocommerce_support || theme.slug === activeTheme);
      })), toConsumableArray_default()(uploadedThemes));

      switch (activeTab) {
        case 'paid':
          return allThemes.filter(function (theme) {
            return Object(utils["c" /* getPriceValue */])(theme.price) > 0;
          });

        case 'free':
          return allThemes.filter(function (theme) {
            return Object(utils["c" /* getPriceValue */])(theme.price) <= 0;
          });

        case 'all':
        default:
          return allThemes;
      }
    }
  }, {
    key: "handleUploadComplete",
    value: function handleUploadComplete(upload) {
      if (upload.status === 'success' && upload.theme_data) {
        this.setState({
          uploadedThemes: [].concat(toConsumableArray_default()(this.state.uploadedThemes), [upload.theme_data])
        });
        Object(tracks["b" /* recordEvent */])('storeprofiler_store_theme_upload', {
          theme: upload.theme_data.slug
        });
      }
    }
  }, {
    key: "render",
    value: function render() {
      var _this5 = this;

      var _this$state = this.state,
          activeTab = _this$state.activeTab,
          chosen = _this$state.chosen,
          demo = _this$state.demo;
      var themes = this.getThemes(activeTab);
      var activeThemeSupportsWooCommerce = this.doesActiveThemeSupportWooCommerce();
      return Object(external_this_wp_element_["createElement"])(external_this_wp_element_["Fragment"], null, Object(external_this_wp_element_["createElement"])(external_this_wc_components_["H"], {
        className: "woocommerce-profile-wizard__header-title"
      }, Object(external_this_wp_i18n_["__"])('Choose a theme', 'woocommerce')), Object(external_this_wp_element_["createElement"])(external_this_wc_components_["H"], {
        className: "woocommerce-profile-wizard__header-subtitle"
      }, Object(external_this_wp_i18n_["__"])("Choose how your store appears to customers. And don't worry, you can always switch themes and edit them later.", 'woocommerce')), Object(external_this_wp_element_["createElement"])(tab_panel["a" /* default */], {
        className: "woocommerce-profile-wizard__themes-tab-panel",
        activeClass: "is-active",
        onSelect: this.onSelectTab,
        tabs: [{
          name: 'all',
          title: Object(external_this_wp_i18n_["__"])('All themes', 'woocommerce')
        }, {
          name: 'paid',
          title: Object(external_this_wp_i18n_["__"])('Paid themes', 'woocommerce')
        }, {
          name: 'free',
          title: Object(external_this_wp_i18n_["__"])('Free themes', 'woocommerce')
        }]
      }, function () {
        return Object(external_this_wp_element_["createElement"])("div", {
          className: "woocommerce-profile-wizard__themes"
        }, themes && themes.map(function (theme) {
          return _this5.renderTheme(theme);
        }), Object(external_this_wp_element_["createElement"])(uploader, {
          onUploadComplete: _this5.handleUploadComplete
        }));
      }), demo && Object(external_this_wp_element_["createElement"])(preview, {
        theme: demo,
        onChoose: this.onChoose,
        onClose: this.onClosePreview,
        isBusy: chosen === demo.slug
      }), activeThemeSupportsWooCommerce && Object(external_this_wp_element_["createElement"])("p", null, Object(external_this_wp_element_["createElement"])(build_module_button["a" /* default */], {
        isLink: true,
        className: "woocommerce-profile-wizard__skip",
        onClick: function onClick() {
          return _this5.skipStep();
        }
      }, Object(external_this_wp_i18n_["__"])('Skip this step', 'woocommerce'))));
    }
  }]);

  return Theme;
}(external_this_wp_element_["Component"]);

/* harmony default export */ var steps_theme = (Object(compose["a" /* default */])(Object(with_select["a" /* default */])(function (select) {
  var _select = select('wc-api'),
      getProfileItems = _select.getProfileItems,
      getProfileItemsError = _select.getProfileItemsError,
      isGetProfileItemsRequesting = _select.isGetProfileItemsRequesting;

  return {
    isError: Boolean(getProfileItemsError()),
    isGetProfileItemsRequesting: isGetProfileItemsRequesting(),
    profileItems: getProfileItems()
  };
}), Object(external_this_wp_data_["withDispatch"])(function (dispatch) {
  var _dispatch = dispatch('wc-api'),
      updateProfileItems = _dispatch.updateProfileItems;

  var _dispatch2 = dispatch('core/notices'),
      createNotice = _dispatch2.createNotice;

  return {
    createNotice: createNotice,
    updateProfileItems: updateProfileItems
  };
}))(theme_Theme));
// EXTERNAL MODULE: ./client/profile-wizard/style.scss
var profile_wizard_style = __webpack_require__(891);

// CONCATENATED MODULE: ./client/profile-wizard/index.js









function profile_wizard_ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function profile_wizard_objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { profile_wizard_ownKeys(Object(source), true).forEach(function (key) { defineProperty_default()(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { profile_wizard_ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function profile_wizard_createSuper(Derived) { var hasNativeReflectConstruct = profile_wizard_isNativeReflectConstruct(); return function () { var Super = getPrototypeOf_default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = getPrototypeOf_default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return possibleConstructorReturn_default()(this, result); }; }

function profile_wizard_isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/**
 * External dependencies
 */





/**
 * WooCommerce dependencies
 */



/**
 * Internal dependencies
 */













var profile_wizard_ProfileWizard = /*#__PURE__*/function (_Component) {
  inherits_default()(ProfileWizard, _Component);

  var _super = profile_wizard_createSuper(ProfileWizard);

  function ProfileWizard(props) {
    var _this;

    classCallCheck_default()(this, ProfileWizard);

    _this = _super.call(this, props);
    _this.state = {
      cartRedirectUrl: null
    };
    _this.activePlugins = props.activePlugins;
    _this.goToNextStep = _this.goToNextStep.bind(assertThisInitialized_default()(_this));
    return _this;
  }

  createClass_default()(ProfileWizard, [{
    key: "componentDidUpdate",
    value: function componentDidUpdate(prevProps) {
      var prevStep = prevProps.query.step;
      var step = this.props.query.step;
      var _this$props = this.props,
          isError = _this$props.isError,
          isGetProfileItemsRequesting = _this$props.isGetProfileItemsRequesting,
          createNotice = _this$props.createNotice;
      var isRequestError = !isGetProfileItemsRequesting && prevProps.isRequesting && isError;

      if (isRequestError) {
        createNotice('error', Object(external_this_wp_i18n_["__"])('There was a problem finishing the profile wizard.', 'woocommerce'));
      }

      if (prevStep !== step) {
        window.document.documentElement.scrollTop = 0;
        Object(tracks["b" /* recordEvent */])('storeprofiler_step_view', {
          step: this.getCurrentStep().key
        });
      }
    }
  }, {
    key: "componentDidMount",
    value: function componentDidMount() {
      var _this$props2 = this.props,
          profileItems = _this$props2.profileItems,
          updateProfileItems = _this$props2.updateProfileItems;
      document.body.classList.remove('woocommerce-admin-is-loading');
      document.documentElement.classList.remove('wp-toolbar');
      document.body.classList.add('woocommerce-onboarding');
      document.body.classList.add('woocommerce-profile-wizard__body');
      document.body.classList.add('woocommerce-admin-full-screen');
      Object(tracks["b" /* recordEvent */])('storeprofiler_step_view', {
        step: this.getCurrentStep().key
      }); // Track plugins if already installed.

      if (this.activePlugins.includes('woocommerce-services') && this.activePlugins.includes('jetpack') && profileItems.plugins !== 'already-installed') {
        Object(tracks["b" /* recordEvent */])('wcadmin_storeprofiler_already_installed_plugins', {});
        updateProfileItems({
          plugins: 'already-installed'
        });
      }
    }
  }, {
    key: "componentWillUnmount",
    value: function componentWillUnmount() {
      var cartRedirectUrl = this.state.cartRedirectUrl;

      if (cartRedirectUrl) {
        document.body.classList.add('woocommerce-admin-is-loading');
        window.location = cartRedirectUrl;
      }

      document.documentElement.classList.add('wp-toolbar');
      document.body.classList.remove('woocommerce-onboarding');
      document.body.classList.remove('woocommerce-profile-wizard__body');
      document.body.classList.remove('woocommerce-admin-full-screen');
    }
  }, {
    key: "getSteps",
    value: function getSteps() {
      var profileItems = this.props.profileItems;
      var steps = [];
      steps.push({
        key: 'store-details',
        container: store_details,
        label: Object(external_this_wp_i18n_["__"])('Store Details', 'woocommerce'),
        isComplete: profileItems.hasOwnProperty('setup_client') && profileItems.setup_client !== null
      });
      steps.push({
        key: 'industry',
        container: industry,
        label: Object(external_this_wp_i18n_["__"])('Industry', 'woocommerce'),
        isComplete: profileItems.hasOwnProperty('industry') && profileItems.industry !== null
      });
      steps.push({
        key: 'product-types',
        container: product_types,
        label: Object(external_this_wp_i18n_["__"])('Product Types', 'woocommerce'),
        isComplete: profileItems.hasOwnProperty('product_types') && profileItems.product_types !== null
      });
      steps.push({
        key: 'business-details',
        container: business_details,
        label: Object(external_this_wp_i18n_["__"])('Business Details', 'woocommerce'),
        isComplete: profileItems.hasOwnProperty('product_count') && profileItems.product_count !== null
      });
      steps.push({
        key: 'theme',
        container: steps_theme,
        label: Object(external_this_wp_i18n_["__"])('Theme', 'woocommerce'),
        isComplete: profileItems.hasOwnProperty('theme') && profileItems.theme !== null
      });

      if (!this.activePlugins.includes('woocommerce-services') || !this.activePlugins.includes('jetpack')) {
        steps.push({
          key: 'benefits',
          container: benefits
        });
      }

      return steps;
    }
  }, {
    key: "getCurrentStep",
    value: function getCurrentStep() {
      var step = this.props.query.step;
      var currentStep = this.getSteps().find(function (s) {
        return s.key === step;
      });

      if (!currentStep) {
        return this.getSteps()[0];
      }

      return currentStep;
    }
  }, {
    key: "goToNextStep",
    value: function () {
      var _goToNextStep = asyncToGenerator_default()( /*#__PURE__*/regeneratorRuntime.mark(function _callee() {
        var currentStep, currentStepIndex, nextStep;
        return regeneratorRuntime.wrap(function _callee$(_context) {
          while (1) {
            switch (_context.prev = _context.next) {
              case 0:
                currentStep = this.getCurrentStep();
                currentStepIndex = this.getSteps().findIndex(function (s) {
                  return s.key === currentStep.key;
                });
                Object(tracks["b" /* recordEvent */])('storeprofiler_step_complete', {
                  step: currentStep.key
                });
                nextStep = this.getSteps()[currentStepIndex + 1];

                if (!(typeof nextStep === 'undefined')) {
                  _context.next = 7;
                  break;
                }

                this.completeProfiler();
                return _context.abrupt("return");

              case 7:
                return _context.abrupt("return", Object(external_this_wc_navigation_["updateQueryString"])({
                  step: nextStep.key
                }));

              case 8:
              case "end":
                return _context.stop();
            }
          }
        }, _callee, this);
      }));

      function goToNextStep() {
        return _goToNextStep.apply(this, arguments);
      }

      return goToNextStep;
    }()
  }, {
    key: "completeProfiler",
    value: function completeProfiler() {
      var _this$props3 = this.props,
          notes = _this$props3.notes,
          updateNote = _this$props3.updateNote,
          updateProfileItems = _this$props3.updateProfileItems;
      updateProfileItems({
        completed: true
      });
      Object(tracks["b" /* recordEvent */])('storeprofiler_complete');
      var profilerNote = notes.find(function (note) {
        return note.name === 'wc-admin-onboarding-profiler-reminder';
      });

      if (profilerNote) {
        updateNote(profilerNote.id, {
          status: 'actioned'
        });
      }
    }
  }, {
    key: "render",
    value: function render() {
      var query = this.props.query;
      var step = this.getCurrentStep();
      var container = Object(external_this_wp_element_["createElement"])(step.container, {
        query: query,
        step: step,
        goToNextStep: this.goToNextStep
      });
      var steps = this.getSteps().map(function (_step) {
        return Object(external_lodash_["pick"])(_step, ['key', 'label', 'isComplete']);
      });
      return Object(external_this_wp_element_["createElement"])(external_this_wp_element_["Fragment"], null, Object(external_this_wp_element_["createElement"])(header_ProfileWizardHeader, {
        currentStep: step.key,
        steps: steps
      }), Object(external_this_wp_element_["createElement"])("div", {
        className: "woocommerce-profile-wizard__container"
      }, container));
    }
  }]);

  return ProfileWizard;
}(external_this_wp_element_["Component"]);

var hydrateSettings = window.wcSettings.preloadSettings && window.wcSettings.preloadSettings.general;
/* harmony default export */ var profile_wizard = __webpack_exports__["default"] = (Object(compose["a" /* default */])(Object(with_select["a" /* default */])(function (select) {
  var _select = select('wc-api'),
      getNotes = _select.getNotes,
      getProfileItems = _select.getProfileItems,
      getProfileItemsError = _select.getProfileItemsError;

  var _select2 = select(external_this_wc_data_["PLUGINS_STORE_NAME"]),
      getActivePlugins = _select2.getActivePlugins;

  var notesQuery = {
    page: 1,
    per_page: wc_api_constants["d" /* QUERY_DEFAULTS */].pageSize,
    type: 'update',
    status: 'unactioned'
  };
  var notes = getNotes(notesQuery);
  var activePlugins = getActivePlugins();
  return {
    isError: Boolean(getProfileItemsError()),
    notes: notes,
    profileItems: getProfileItems(),
    activePlugins: activePlugins
  };
}), Object(external_this_wp_data_["withDispatch"])(function (dispatch) {
  var _dispatch = dispatch('wc-api'),
      updateNote = _dispatch.updateNote,
      updateProfileItems = _dispatch.updateProfileItems;

  var _dispatch2 = dispatch('core/notices'),
      createNotice = _dispatch2.createNotice;

  return {
    createNotice: createNotice,
    updateNote: updateNote,
    updateProfileItems: updateProfileItems
  };
}), hydrateSettings ? Object(external_this_wc_data_["withSettingsHydration"])('general', {
  general: window.wcSettings.preloadSettings.general
}) : external_lodash_["identity"], window.wcSettings.plugins ? Object(external_this_wc_data_["withPluginsHydration"])(profile_wizard_objectSpread({}, window.wcSettings.plugins, {
  jetpackStatus: window.wcSettings.dataEndpoints.jetpackStatus
})) : external_lodash_["identity"])(profile_wizard_ProfileWizard));

/***/ })

}]);
