(window["webpackJsonp"] = window["webpackJsonp"] || []).push([[23],{

/***/ 112:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "a", function() { return useInstanceId; });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
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
  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["useMemo"])(function () {
    return createId(object);
  }, [object]);
}
//# sourceMappingURL=index.js.map

/***/ }),

/***/ 128:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/esm/defineProperty.js
var defineProperty = __webpack_require__(6);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/esm/objectWithoutProperties.js
var objectWithoutProperties = __webpack_require__(14);

// EXTERNAL MODULE: ./node_modules/classnames/index.js
var classnames = __webpack_require__(8);
var classnames_default = /*#__PURE__*/__webpack_require__.n(classnames);

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



function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { Object(defineProperty["a" /* default */])(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

/**
 * External dependencies
 */

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
      className = _ref.className,
      props = Object(objectWithoutProperties["a" /* default */])(_ref, ["as", "className"]);

  return renderAsRenderProps(_objectSpread({
    as: as,
    className: classnames_default()('components-visually-hidden', className)
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
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(8);
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(classnames__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _visually_hidden__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(128);


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

/***/ 422:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var _babel_runtime_helpers_esm_defineProperty__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(6);
/* harmony import */ var _babel_runtime_helpers_esm_objectWithoutProperties__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(14);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__);



function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { Object(_babel_runtime_helpers_esm_defineProperty__WEBPACK_IMPORTED_MODULE_0__[/* default */ "a"])(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

/**
 * WordPress dependencies
 */
 // Disable reason: JSDoc linter doesn't seem to parse the union (`&`) correctly.

/* eslint-disable jsdoc/valid-types */

/** @typedef {{icon: JSX.Element, size?: number} & import('react').ComponentPropsWithoutRef<'SVG'>} IconProps */

/* eslint-enable jsdoc/valid-types */

/**
 * Return an SVG icon.
 *
 * @param {IconProps} props icon is the SVG component to render
 *                          size is a number specifiying the icon size in pixels
 *                          Other props will be passed to wrapped SVG component
 *
 * @return {JSX.Element}  Icon component
 */

function Icon(_ref) {
  var icon = _ref.icon,
      _ref$size = _ref.size,
      size = _ref$size === void 0 ? 24 : _ref$size,
      props = Object(_babel_runtime_helpers_esm_objectWithoutProperties__WEBPACK_IMPORTED_MODULE_1__[/* default */ "a"])(_ref, ["icon", "size"]);

  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__["cloneElement"])(icon, _objectSpread({
    width: size,
    height: size
  }, props));
}

/* harmony default export */ __webpack_exports__["a"] = (Icon);
//# sourceMappingURL=index.js.map

/***/ }),

/***/ 721:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var _babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(12);
/* harmony import */ var _babel_runtime_helpers_esm_classCallCheck__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(9);
/* harmony import */ var _babel_runtime_helpers_esm_createClass__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(7);
/* harmony import */ var _babel_runtime_helpers_esm_assertThisInitialized__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(5);
/* harmony import */ var _babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(11);
/* harmony import */ var _babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(4);
/* harmony import */ var _babel_runtime_helpers_esm_inherits__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(10);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(8);
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(classnames__WEBPACK_IMPORTED_MODULE_8__);
/* harmony import */ var _popover__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(82);









function _createSuper(Derived) { return function () { var Super = Object(_babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_5__[/* default */ "a"])(Derived), result; if (_isNativeReflectConstruct()) { var NewTarget = Object(_babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_5__[/* default */ "a"])(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return Object(_babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_4__[/* default */ "a"])(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */


/**
 * Internal dependencies
 */



var Dropdown = /*#__PURE__*/function (_Component) {
  Object(_babel_runtime_helpers_esm_inherits__WEBPACK_IMPORTED_MODULE_6__[/* default */ "a"])(Dropdown, _Component);

  var _super = _createSuper(Dropdown);

  function Dropdown() {
    var _this;

    Object(_babel_runtime_helpers_esm_classCallCheck__WEBPACK_IMPORTED_MODULE_1__[/* default */ "a"])(this, Dropdown);

    _this = _super.apply(this, arguments);
    _this.toggle = _this.toggle.bind(Object(_babel_runtime_helpers_esm_assertThisInitialized__WEBPACK_IMPORTED_MODULE_3__[/* default */ "a"])(_this));
    _this.close = _this.close.bind(Object(_babel_runtime_helpers_esm_assertThisInitialized__WEBPACK_IMPORTED_MODULE_3__[/* default */ "a"])(_this));
    _this.closeIfFocusOutside = _this.closeIfFocusOutside.bind(Object(_babel_runtime_helpers_esm_assertThisInitialized__WEBPACK_IMPORTED_MODULE_3__[/* default */ "a"])(_this));
    _this.containerRef = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createRef"])();
    _this.state = {
      isOpen: false
    };
    return _this;
  }

  Object(_babel_runtime_helpers_esm_createClass__WEBPACK_IMPORTED_MODULE_2__[/* default */ "a"])(Dropdown, [{
    key: "componentWillUnmount",
    value: function componentWillUnmount() {
      var isOpen = this.state.isOpen;
      var onToggle = this.props.onToggle;

      if (isOpen && onToggle) {
        onToggle(false);
      }
    }
  }, {
    key: "componentDidUpdate",
    value: function componentDidUpdate(prevProps, prevState) {
      var isOpen = this.state.isOpen;
      var onToggle = this.props.onToggle;

      if (prevState.isOpen !== isOpen && onToggle) {
        onToggle(isOpen);
      }
    }
  }, {
    key: "toggle",
    value: function toggle() {
      this.setState(function (state) {
        return {
          isOpen: !state.isOpen
        };
      });
    }
    /**
     * Closes the dropdown if a focus leaves the dropdown wrapper. This is
     * intentionally distinct from `onClose` since focus loss from the popover
     * is expected to occur when using the Dropdown's toggle button, in which
     * case the correct behavior is to keep the dropdown closed. The same applies
     * in case when focus is moved to the modal dialog.
     */

  }, {
    key: "closeIfFocusOutside",
    value: function closeIfFocusOutside() {
      if (!this.containerRef.current.contains(document.activeElement) && !document.activeElement.closest('[role="dialog"]')) {
        this.close();
      }
    }
  }, {
    key: "close",
    value: function close() {
      if (this.props.onClose) {
        this.props.onClose();
      }

      this.setState({
        isOpen: false
      });
    }
  }, {
    key: "render",
    value: function render() {
      var isOpen = this.state.isOpen;
      var _this$props = this.props,
          renderContent = _this$props.renderContent,
          renderToggle = _this$props.renderToggle,
          _this$props$position = _this$props.position,
          position = _this$props$position === void 0 ? 'bottom right' : _this$props$position,
          className = _this$props.className,
          contentClassName = _this$props.contentClassName,
          expandOnMobile = _this$props.expandOnMobile,
          headerTitle = _this$props.headerTitle,
          focusOnMount = _this$props.focusOnMount,
          popoverProps = _this$props.popoverProps;
      var args = {
        isOpen: isOpen,
        onToggle: this.toggle,
        onClose: this.close
      };
      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])("div", {
        className: classnames__WEBPACK_IMPORTED_MODULE_8___default()('components-dropdown', className),
        ref: this.containerRef
      }, renderToggle(args), isOpen && Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])(_popover__WEBPACK_IMPORTED_MODULE_9__[/* default */ "a"], Object(_babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_0__[/* default */ "a"])({
        position: position,
        onClose: this.close,
        onFocusOutside: this.closeIfFocusOutside,
        expandOnMobile: expandOnMobile,
        headerTitle: headerTitle,
        focusOnMount: focusOnMount
      }, popoverProps, {
        className: classnames__WEBPACK_IMPORTED_MODULE_8___default()('components-dropdown__content', popoverProps ? popoverProps.className : undefined, contentClassName)
      }), renderContent(args)));
    }
  }]);

  return Dropdown;
}(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["Component"]);

/* harmony default export */ __webpack_exports__["a"] = (Dropdown);
//# sourceMappingURL=index.js.map

/***/ }),

/***/ 724:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "a", function() { return TextControl; });
/* harmony import */ var _babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(12);
/* harmony import */ var _babel_runtime_helpers_esm_objectWithoutProperties__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(14);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(112);
/* harmony import */ var _base_control__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(171);




/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */


function TextControl(_ref) {
  var label = _ref.label,
      hideLabelFromVision = _ref.hideLabelFromVision,
      value = _ref.value,
      help = _ref.help,
      className = _ref.className,
      onChange = _ref.onChange,
      _ref$type = _ref.type,
      type = _ref$type === void 0 ? 'text' : _ref$type,
      props = Object(_babel_runtime_helpers_esm_objectWithoutProperties__WEBPACK_IMPORTED_MODULE_1__[/* default */ "a"])(_ref, ["label", "hideLabelFromVision", "value", "help", "className", "onChange", "type"]);

  var instanceId = Object(_wordpress_compose__WEBPACK_IMPORTED_MODULE_3__[/* default */ "a"])(TextControl);
  var id = "inspector-text-control-".concat(instanceId);

  var onChangeValue = function onChangeValue(event) {
    return onChange(event.target.value);
  };

  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__["createElement"])(_base_control__WEBPACK_IMPORTED_MODULE_4__[/* default */ "a"], {
    label: label,
    hideLabelFromVision: hideLabelFromVision,
    id: id,
    help: help,
    className: className
  }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__["createElement"])("input", Object(_babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_0__[/* default */ "a"])({
    className: "components-text-control__input",
    type: type,
    id: id,
    value: value,
    onChange: onChangeValue,
    "aria-describedby": !!help ? id + '__help' : undefined
  }, props)));
}
//# sourceMappingURL=index.js.map

/***/ }),

/***/ 757:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(17);
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(38);
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(37);
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(62);
/* harmony import */ var _babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(39);
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(42);
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(26);
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(1);
/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(prop_types__WEBPACK_IMPORTED_MODULE_8__);
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(2);
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_9__);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(18);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_10___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_10__);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(53);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_11___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_components__WEBPACK_IMPORTED_MODULE_11__);
/* harmony import */ var _woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(22);
/* harmony import */ var _woocommerce_data__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(43);
/* harmony import */ var _woocommerce_data__WEBPACK_IMPORTED_MODULE_13___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_data__WEBPACK_IMPORTED_MODULE_13__);
/* harmony import */ var lib_tracks__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(63);
/* harmony import */ var lib_date__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(108);
/* harmony import */ var lib_currency_context__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__(200);









function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0___default()(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

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





var ReportFilters = /*#__PURE__*/function (_Component) {
  _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_4___default()(ReportFilters, _Component);

  var _super = _createSuper(ReportFilters);

  function ReportFilters() {
    var _this;

    _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_1___default()(this, ReportFilters);

    _this = _super.call(this);
    _this.trackDateSelect = _this.trackDateSelect.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_3___default()(_this));
    _this.trackFilterSelect = _this.trackFilterSelect.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_3___default()(_this));
    _this.trackAdvancedFilterAction = _this.trackAdvancedFilterAction.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_3___default()(_this));
    return _this;
  }

  _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_2___default()(ReportFilters, [{
    key: "trackDateSelect",
    value: function trackDateSelect(data) {
      var report = this.props.report;
      Object(lib_tracks__WEBPACK_IMPORTED_MODULE_14__[/* recordEvent */ "b"])('datepicker_update', _objectSpread({
        report: report
      }, Object(lodash__WEBPACK_IMPORTED_MODULE_9__["omitBy"])(data, lodash__WEBPACK_IMPORTED_MODULE_9__["isUndefined"])));
    }
  }, {
    key: "trackFilterSelect",
    value: function trackFilterSelect(data) {
      var report = this.props.report;
      Object(lib_tracks__WEBPACK_IMPORTED_MODULE_14__[/* recordEvent */ "b"])('analytics_filter', {
        report: report,
        filter: data.filter || 'all'
      });
    }
  }, {
    key: "trackAdvancedFilterAction",
    value: function trackAdvancedFilterAction(action, data) {
      var report = this.props.report;

      switch (action) {
        case 'add':
          Object(lib_tracks__WEBPACK_IMPORTED_MODULE_14__[/* recordEvent */ "b"])('analytics_filters_add', {
            report: report,
            filter: data.key
          });
          break;

        case 'remove':
          Object(lib_tracks__WEBPACK_IMPORTED_MODULE_14__[/* recordEvent */ "b"])('analytics_filters_remove', {
            report: report,
            filter: data.key
          });
          break;

        case 'filter':
          var snakeCaseData = Object.keys(data).reduce(function (result, property) {
            result[Object(lodash__WEBPACK_IMPORTED_MODULE_9__["snakeCase"])(property)] = data[property];
            return result;
          }, {});
          Object(lib_tracks__WEBPACK_IMPORTED_MODULE_14__[/* recordEvent */ "b"])('analytics_filters_filter', {
            report: report,
            snakeCaseData: snakeCaseData
          });
          break;

        case 'clear_all':
          Object(lib_tracks__WEBPACK_IMPORTED_MODULE_14__[/* recordEvent */ "b"])('analytics_filters_clear_all', {
            report: report
          });
          break;

        case 'match':
          Object(lib_tracks__WEBPACK_IMPORTED_MODULE_14__[/* recordEvent */ "b"])('analytics_filters_all_any', {
            report: report,
            value: data.match
          });
          break;
      }
    }
  }, {
    key: "render",
    value: function render() {
      var _this$props = this.props,
          advancedFilters = _this$props.advancedFilters,
          filters = _this$props.filters,
          path = _this$props.path,
          query = _this$props.query,
          showDatePicker = _this$props.showDatePicker,
          defaultDateRange = _this$props.defaultDateRange;

      var _getDateParamsFromQue = Object(lib_date__WEBPACK_IMPORTED_MODULE_15__[/* getDateParamsFromQuery */ "h"])(query, defaultDateRange),
          period = _getDateParamsFromQue.period,
          compare = _getDateParamsFromQue.compare,
          before = _getDateParamsFromQue.before,
          after = _getDateParamsFromQue.after;

      var _getCurrentDates = Object(lib_date__WEBPACK_IMPORTED_MODULE_15__[/* getCurrentDates */ "f"])(query, defaultDateRange),
          primaryDate = _getCurrentDates.primary,
          secondaryDate = _getCurrentDates.secondary;

      var dateQuery = {
        period: period,
        compare: compare,
        before: before,
        after: after,
        primaryDate: primaryDate,
        secondaryDate: secondaryDate
      };
      var Currency = this.context;
      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_11__["ReportFilters"], {
        query: query,
        siteLocale: _woocommerce_wc_admin_settings__WEBPACK_IMPORTED_MODULE_12__[/* LOCALE */ "c"].siteLocale,
        currency: Currency.getCurrency(),
        path: path,
        filters: filters,
        advancedFilters: advancedFilters,
        showDatePicker: showDatePicker,
        onDateSelect: this.trackDateSelect,
        onFilterSelect: this.trackFilterSelect,
        onAdvancedFilterAction: this.trackAdvancedFilterAction,
        dateQuery: dateQuery,
        isoDateFormat: lib_date__WEBPACK_IMPORTED_MODULE_15__[/* isoDateFormat */ "k"]
      });
    }
  }]);

  return ReportFilters;
}(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["Component"]);

ReportFilters.contextType = lib_currency_context__WEBPACK_IMPORTED_MODULE_16__[/* CurrencyContext */ "a"];
/* harmony default export */ __webpack_exports__["a"] = (Object(_wordpress_data__WEBPACK_IMPORTED_MODULE_10__["withSelect"])(function (select) {
  var _select$getSetting = select(_woocommerce_data__WEBPACK_IMPORTED_MODULE_13__["SETTINGS_STORE_NAME"]).getSetting('wc_admin', 'wcAdminSettings'),
      defaultDateRange = _select$getSetting.woocommerce_default_date_range;

  return {
    defaultDateRange: defaultDateRange
  };
})(ReportFilters));
ReportFilters.propTypes = {
  /**
   * Config option passed through to `AdvancedFilters`
   */
  advancedFilters: prop_types__WEBPACK_IMPORTED_MODULE_8___default.a.object,

  /**
   * Config option passed through to `FilterPicker` - if not used, `FilterPicker` is not displayed.
   */
  filters: prop_types__WEBPACK_IMPORTED_MODULE_8___default.a.array,

  /**
   * The `path` parameter supplied by React-Router
   */
  path: prop_types__WEBPACK_IMPORTED_MODULE_8___default.a.string.isRequired,

  /**
   * The query string represented in object form
   */
  query: prop_types__WEBPACK_IMPORTED_MODULE_8___default.a.object,

  /**
   * Whether the date picker must be shown..
   */
  showDatePicker: prop_types__WEBPACK_IMPORTED_MODULE_8___default.a.bool,

  /**
   * The report where filter are placed.
   */
  report: prop_types__WEBPACK_IMPORTED_MODULE_8___default.a.string.isRequired
};

/***/ }),

/***/ 910:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
// ESM COMPAT FLAG
__webpack_require__.r(__webpack_exports__);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/objectWithoutProperties.js
var objectWithoutProperties = __webpack_require__(129);
var objectWithoutProperties_default = /*#__PURE__*/__webpack_require__.n(objectWithoutProperties);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/defineProperty.js
var defineProperty = __webpack_require__(17);
var defineProperty_default = /*#__PURE__*/__webpack_require__.n(defineProperty);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/toConsumableArray.js
var toConsumableArray = __webpack_require__(41);
var toConsumableArray_default = /*#__PURE__*/__webpack_require__.n(toConsumableArray);

// EXTERNAL MODULE: external {"this":["wp","element"]}
var external_this_wp_element_ = __webpack_require__(0);

// EXTERNAL MODULE: external {"this":["wp","i18n"]}
var external_this_wp_i18n_ = __webpack_require__(3);

// EXTERNAL MODULE: ./node_modules/@wordpress/compose/build-module/higher-order/compose.js
var compose = __webpack_require__(169);

// EXTERNAL MODULE: external "lodash"
var external_lodash_ = __webpack_require__(2);

// EXTERNAL MODULE: ./node_modules/@wordpress/components/build-module/dropdown/index.js
var dropdown = __webpack_require__(721);

// EXTERNAL MODULE: ./node_modules/@wordpress/components/build-module/button/index.js
var build_module_button = __webpack_require__(67);

// EXTERNAL MODULE: ./node_modules/@wordpress/components/build-module/icon/index.js
var icon = __webpack_require__(96);

// EXTERNAL MODULE: external {"this":["wp","hooks"]}
var external_this_wp_hooks_ = __webpack_require__(48);

// EXTERNAL MODULE: ./node_modules/@wordpress/icons/build-module/icon/index.js
var build_module_icon = __webpack_require__(422);

// EXTERNAL MODULE: ./node_modules/@wordpress/primitives/build-module/svg/index.js
var svg = __webpack_require__(32);

// CONCATENATED MODULE: ./node_modules/@wordpress/icons/build-module/library/plus-circle-filled.js


/**
 * WordPress dependencies
 */

var plusCircleFilled = Object(external_this_wp_element_["createElement"])(svg["b" /* SVG */], {
  xmlns: "http://www.w3.org/2000/svg",
  viewBox: "0 0 24 24"
}, Object(external_this_wp_element_["createElement"])(svg["a" /* Path */], {
  d: "M2 12C2 6.44444 6.44444 2 12 2C17.5556 2 22 6.44444 22 12C22 17.5556 17.5556 22 12 22C6.44444 22 2 17.5556 2 12ZM13 11V7H11V11H7V13H11V17H13V13H17V11H13Z"
}));
/* harmony default export */ var plus_circle_filled = (plusCircleFilled);
//# sourceMappingURL=plus-circle-filled.js.map
// EXTERNAL MODULE: external {"this":["wc","components"]}
var external_this_wc_components_ = __webpack_require__(53);

// EXTERNAL MODULE: external {"this":["wc","data"]}
var external_this_wc_data_ = __webpack_require__(43);

// EXTERNAL MODULE: ./client/dashboard/style.scss
var style = __webpack_require__(784);

// CONCATENATED MODULE: ./client/dashboard/default-sections.js


/**
 * External dependencies
 */



/**
 * Internal dependencies
 */


var LazyDashboardCharts = Object(external_this_wp_element_["lazy"])(function () {
  return Promise.all(/* import() | dashboard-charts */[__webpack_require__.e(2), __webpack_require__.e(1), __webpack_require__.e(25)]).then(__webpack_require__.bind(null, 933));
});
var LazyLeaderboards = Object(external_this_wp_element_["lazy"])(function () {
  return Promise.all(/* import() | leaderboards */[__webpack_require__.e(3), __webpack_require__.e(31)]).then(__webpack_require__.bind(null, 938));
});
var LazyStorePerformance = Object(external_this_wp_element_["lazy"])(function () {
  return __webpack_require__.e(/* import() | store-performance */ 43).then(__webpack_require__.bind(null, 928));
});

var default_sections_DashboardCharts = function DashboardCharts(props) {
  return Object(external_this_wp_element_["createElement"])(external_this_wp_element_["Suspense"], {
    fallback: Object(external_this_wp_element_["createElement"])(external_this_wc_components_["Spinner"], null)
  }, Object(external_this_wp_element_["createElement"])(LazyDashboardCharts, props));
};

var default_sections_Leaderboards = function Leaderboards(props) {
  return Object(external_this_wp_element_["createElement"])(external_this_wp_element_["Suspense"], {
    fallback: Object(external_this_wp_element_["createElement"])(external_this_wc_components_["Spinner"], null)
  }, Object(external_this_wp_element_["createElement"])(LazyLeaderboards, props));
};

var default_sections_StorePerformance = function StorePerformance(props) {
  return Object(external_this_wp_element_["createElement"])(external_this_wp_element_["Suspense"], {
    fallback: Object(external_this_wp_element_["createElement"])(external_this_wc_components_["Spinner"], null)
  }, Object(external_this_wp_element_["createElement"])(LazyStorePerformance, props));
};

var DEFAULT_SECTIONS_FILTER = 'woocommerce_dashboard_default_sections';
/* harmony default export */ var default_sections = (Object(external_this_wp_hooks_["applyFilters"])(DEFAULT_SECTIONS_FILTER, [{
  key: 'store-performance',
  component: default_sections_StorePerformance,
  title: Object(external_this_wp_i18n_["__"])('Performance', 'woocommerce'),
  isVisible: true,
  icon: 'arrow-right-alt',
  hiddenBlocks: ['coupons/amount', 'coupons/orders_count', 'downloads/download_count', 'taxes/order_tax', 'taxes/total_tax', 'taxes/shipping_tax', 'revenue/shipping', 'orders/avg_order_value', 'revenue/refunds', 'revenue/gross_sales']
}, {
  key: 'charts',
  component: default_sections_DashboardCharts,
  title: Object(external_this_wp_i18n_["__"])('Charts', 'woocommerce'),
  isVisible: true,
  icon: 'chart-bar',
  hiddenBlocks: ['orders_avg_order_value', 'avg_items_per_order', 'products_items_sold', 'revenue_total_sales', 'revenue_refunds', 'coupons_amount', 'coupons_orders_count', 'revenue_shipping', 'taxes_total_tax', 'taxes_order_tax', 'taxes_shipping_tax', 'downloads_download_count']
}, {
  key: 'leaderboards',
  component: default_sections_Leaderboards,
  title: Object(external_this_wp_i18n_["__"])('Leaderboards', 'woocommerce'),
  isVisible: true,
  icon: 'editor-ol',
  hiddenBlocks: ['coupons', 'customers']
}]));
// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/extends.js
var helpers_extends = __webpack_require__(80);
var extends_default = /*#__PURE__*/__webpack_require__.n(helpers_extends);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/classCallCheck.js
var classCallCheck = __webpack_require__(38);
var classCallCheck_default = /*#__PURE__*/__webpack_require__.n(classCallCheck);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/createClass.js
var createClass = __webpack_require__(37);
var createClass_default = /*#__PURE__*/__webpack_require__.n(createClass);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/assertThisInitialized.js
var assertThisInitialized = __webpack_require__(62);
var assertThisInitialized_default = /*#__PURE__*/__webpack_require__.n(assertThisInitialized);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/inherits.js
var inherits = __webpack_require__(39);
var inherits_default = /*#__PURE__*/__webpack_require__.n(inherits);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/possibleConstructorReturn.js
var possibleConstructorReturn = __webpack_require__(42);
var possibleConstructorReturn_default = /*#__PURE__*/__webpack_require__.n(possibleConstructorReturn);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/getPrototypeOf.js
var getPrototypeOf = __webpack_require__(26);
var getPrototypeOf_default = /*#__PURE__*/__webpack_require__.n(getPrototypeOf);

// EXTERNAL MODULE: ./node_modules/@wordpress/components/build-module/text-control/index.js
var text_control = __webpack_require__(724);

// CONCATENATED MODULE: ./client/dashboard/section-controls.js








function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = getPrototypeOf_default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = getPrototypeOf_default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return possibleConstructorReturn_default()(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/**
 * External dependencies
 */



/**
 * Internal dependencies
 */



var section_controls_SectionControls = /*#__PURE__*/function (_Component) {
  inherits_default()(SectionControls, _Component);

  var _super = _createSuper(SectionControls);

  function SectionControls(props) {
    var _this;

    classCallCheck_default()(this, SectionControls);

    _this = _super.call(this, props);
    _this.onMoveUp = _this.onMoveUp.bind(assertThisInitialized_default()(_this));
    _this.onMoveDown = _this.onMoveDown.bind(assertThisInitialized_default()(_this));
    return _this;
  }

  createClass_default()(SectionControls, [{
    key: "onMoveUp",
    value: function onMoveUp() {
      var _this$props = this.props,
          onMove = _this$props.onMove,
          onToggle = _this$props.onToggle;
      onMove(-1); // Close the dropdown

      onToggle();
    }
  }, {
    key: "onMoveDown",
    value: function onMoveDown() {
      var _this$props2 = this.props,
          onMove = _this$props2.onMove,
          onToggle = _this$props2.onToggle;
      onMove(1); // Close the dropdown

      onToggle();
    }
  }, {
    key: "render",
    value: function render() {
      var _this$props3 = this.props,
          onRemove = _this$props3.onRemove,
          isFirst = _this$props3.isFirst,
          isLast = _this$props3.isLast,
          onTitleBlur = _this$props3.onTitleBlur,
          onTitleChange = _this$props3.onTitleChange,
          titleInput = _this$props3.titleInput;
      return Object(external_this_wp_element_["createElement"])(external_this_wp_element_["Fragment"], null, Object(external_this_wp_element_["createElement"])("div", {
        className: "woocommerce-ellipsis-menu__item"
      }, Object(external_this_wp_element_["createElement"])(text_control["a" /* default */], {
        label: Object(external_this_wp_i18n_["__"])('Section Title', 'woocommerce'),
        onBlur: onTitleBlur,
        onChange: onTitleChange,
        required: true,
        value: titleInput
      })), Object(external_this_wp_element_["createElement"])("div", {
        className: "woocommerce-dashboard-section-controls"
      }, !isFirst && Object(external_this_wp_element_["createElement"])(external_this_wc_components_["MenuItem"], {
        isClickable: true,
        onInvoke: this.onMoveUp
      }, Object(external_this_wp_element_["createElement"])(icon["a" /* default */], {
        icon: 'arrow-up-alt2',
        label: Object(external_this_wp_i18n_["__"])('Move up')
      }), Object(external_this_wp_i18n_["__"])('Move up', 'woocommerce')), !isLast && Object(external_this_wp_element_["createElement"])(external_this_wc_components_["MenuItem"], {
        isClickable: true,
        onInvoke: this.onMoveDown
      }, Object(external_this_wp_element_["createElement"])(icon["a" /* default */], {
        icon: 'arrow-down-alt2',
        label: Object(external_this_wp_i18n_["__"])('Move Down')
      }), Object(external_this_wp_i18n_["__"])('Move Down', 'woocommerce')), Object(external_this_wp_element_["createElement"])(external_this_wc_components_["MenuItem"], {
        isClickable: true,
        onInvoke: onRemove
      }, Object(external_this_wp_element_["createElement"])(icon["a" /* default */], {
        icon: 'trash',
        label: Object(external_this_wp_i18n_["__"])('Remove block')
      }), Object(external_this_wp_i18n_["__"])('Remove section', 'woocommerce'))));
    }
  }]);

  return SectionControls;
}(external_this_wp_element_["Component"]);

/* harmony default export */ var section_controls = (section_controls_SectionControls);
// CONCATENATED MODULE: ./client/dashboard/section.js










function section_createSuper(Derived) { var hasNativeReflectConstruct = section_isNativeReflectConstruct(); return function _createSuperInternal() { var Super = getPrototypeOf_default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = getPrototypeOf_default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return possibleConstructorReturn_default()(this, result); }; }

function section_isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/**
 * External dependencies
 */


/**
 * Internal dependencies
 */



var section_Section = /*#__PURE__*/function (_Component) {
  inherits_default()(Section, _Component);

  var _super = section_createSuper(Section);

  function Section(props) {
    var _this;

    classCallCheck_default()(this, Section);

    _this = _super.call(this, props);
    var title = props.title;
    _this.state = {
      titleInput: title
    };
    _this.onToggleHiddenBlock = _this.onToggleHiddenBlock.bind(assertThisInitialized_default()(_this));
    _this.onTitleChange = _this.onTitleChange.bind(assertThisInitialized_default()(_this));
    _this.onTitleBlur = _this.onTitleBlur.bind(assertThisInitialized_default()(_this));
    return _this;
  }

  createClass_default()(Section, [{
    key: "onTitleChange",
    value: function onTitleChange(updatedTitle) {
      this.setState({
        titleInput: updatedTitle
      });
    }
  }, {
    key: "onTitleBlur",
    value: function onTitleBlur() {
      var _this$props = this.props,
          onTitleUpdate = _this$props.onTitleUpdate,
          title = _this$props.title;
      var titleInput = this.state.titleInput;

      if (titleInput === '') {
        this.setState({
          titleInput: title
        });
      } else if (onTitleUpdate) {
        onTitleUpdate(titleInput);
      }
    }
  }, {
    key: "onToggleHiddenBlock",
    value: function onToggleHiddenBlock(key) {
      var _this2 = this;

      return function () {
        var hiddenBlocks = Object(external_lodash_["xor"])(_this2.props.hiddenBlocks, [key]);

        _this2.props.onChangeHiddenBlocks(hiddenBlocks);
      };
    }
  }, {
    key: "render",
    value: function render() {
      var _this$props2 = this.props,
          SectionComponent = _this$props2.component,
          props = objectWithoutProperties_default()(_this$props2, ["component"]);

      var titleInput = this.state.titleInput;
      return Object(external_this_wp_element_["createElement"])("div", {
        className: "woocommerce-dashboard-section"
      }, Object(external_this_wp_element_["createElement"])(SectionComponent, extends_default()({
        onTitleChange: this.onTitleChange,
        onTitleBlur: this.onTitleBlur,
        onToggleHiddenBlock: this.onToggleHiddenBlock,
        titleInput: titleInput,
        controls: section_controls
      }, props)));
    }
  }]);

  return Section;
}(external_this_wp_element_["Component"]);


// EXTERNAL MODULE: ./client/wc-api/with-select.js
var with_select = __webpack_require__(101);

// EXTERNAL MODULE: ./client/lib/tracks.js
var tracks = __webpack_require__(63);

// EXTERNAL MODULE: ./client/dashboard/utils.js
var utils = __webpack_require__(753);

// EXTERNAL MODULE: ./client/lib/date.js
var date = __webpack_require__(108);

// EXTERNAL MODULE: ./client/analytics/components/report-filters/index.js
var report_filters = __webpack_require__(757);

// CONCATENATED MODULE: ./client/dashboard/customizable.js





function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { defineProperty_default()(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

/**
 * External dependencies
 */







/**
 * WooCommerce dependencies
 */



/**
 * Internal dependencies
 */









var TaskList = Object(external_this_wp_element_["lazy"])(function () {
  return Promise.all(/* import() | task-list */[__webpack_require__.e(3), __webpack_require__.e(5), __webpack_require__.e(44)]).then(__webpack_require__.bind(null, 929));
});
var DASHBOARD_FILTERS_FILTER = 'woocommerce_admin_dashboard_filters';
var filters = Object(external_this_wp_hooks_["applyFilters"])(DASHBOARD_FILTERS_FILTER, []);

var customizable_mergeSectionsWithDefaults = function mergeSectionsWithDefaults(prefSections) {
  if (!prefSections || prefSections.length === 0) {
    return default_sections;
  }

  var defaultKeys = default_sections.map(function (section) {
    return section.key;
  });
  var prefKeys = prefSections.map(function (section) {
    return section.key;
  });
  var keys = new Set([].concat(toConsumableArray_default()(prefKeys), toConsumableArray_default()(defaultKeys)));
  var sections = [];
  keys.forEach(function (key) {
    var defaultSection = default_sections.find(function (section) {
      return section.key === key;
    });

    if (!defaultSection) {
      return;
    }

    var prefSection = prefSections.find(function (section) {
      return section.key === key;
    });
    sections.push(_objectSpread(_objectSpread({}, defaultSection), prefSection));
  });
  return sections;
};

var customizable_CustomizableDashboard = function CustomizableDashboard(_ref) {
  var defaultDateRange = _ref.defaultDateRange,
      homepageEnabled = _ref.homepageEnabled,
      path = _ref.path,
      query = _ref.query,
      taskListComplete = _ref.taskListComplete,
      taskListHidden = _ref.taskListHidden;

  var _useUserPreferences = Object(external_this_wc_data_["useUserPreferences"])(),
      updateUserPreferences = _useUserPreferences.updateUserPreferences,
      userPrefs = objectWithoutProperties_default()(_useUserPreferences, ["updateUserPreferences"]);

  var sections = customizable_mergeSectionsWithDefaults(userPrefs.dashboard_sections);
  var isTaskListEnabled = !homepageEnabled && Object(utils["e" /* isOnboardingEnabled */])() && !taskListHidden;
  var isDashboardShown = !isTaskListEnabled || !query.task && taskListComplete;

  var updateSections = function updateSections(newSections) {
    updateUserPreferences({
      dashboard_sections: newSections
    });
  };

  var updateSection = function updateSection(updatedKey, newSettings) {
    var newSections = sections.map(function (section) {
      if (section.key === updatedKey) {
        return _objectSpread(_objectSpread({}, section), newSettings);
      }

      return section;
    });
    updateSections(newSections);
  };

  var onChangeHiddenBlocks = function onChangeHiddenBlocks(updatedKey) {
    return function (updatedHiddenBlocks) {
      updateSection(updatedKey, {
        hiddenBlocks: updatedHiddenBlocks
      });
    };
  };

  var onSectionTitleUpdate = function onSectionTitleUpdate(updatedKey) {
    return function (updatedTitle) {
      Object(tracks["b" /* recordEvent */])('dash_section_rename', {
        key: updatedKey
      });
      updateSection(updatedKey, {
        title: updatedTitle
      });
    };
  };

  var toggleVisibility = function toggleVisibility(key, onToggle) {
    return function () {
      if (onToggle) {
        // Close the dropdown before setting state so an action is not performed on an unmounted component.
        onToggle();
      } // When toggling visibility, place section at the end of the array.


      var index = sections.findIndex(function (s) {
        return key === s.key;
      });
      var toggledSection = sections.splice(index, 1).shift();
      toggledSection.isVisible = !toggledSection.isVisible;
      sections.push(toggledSection);

      if (toggledSection.isVisible) {
        Object(tracks["b" /* recordEvent */])('dash_section_add', {
          key: toggledSection.key
        });
      } else {
        Object(tracks["b" /* recordEvent */])('dash_section_remove', {
          key: toggledSection.key
        });
      }

      updateSections(sections);
    };
  };

  var onMove = function onMove(index, change) {
    var movedSection = sections.splice(index, 1).shift();
    var newIndex = index + change; // Figure out the index of the skipped section.

    var nextJumpedSectionIndex = change < 0 ? newIndex : newIndex - 1;

    if (sections[nextJumpedSectionIndex].isVisible || // Is the skipped section visible?
    index === 0 || // Will this be the first element?
    index === sections.length - 1 // Will this be the last element?
    ) {
        // Yes, lets insert.
        sections.splice(newIndex, 0, movedSection);
        updateSections(sections);
        var eventProps = {
          key: movedSection.key,
          direction: change > 0 ? 'down' : 'up'
        };
        Object(tracks["b" /* recordEvent */])('dash_section_order_change', eventProps);
      } else {
      // No, lets try the next one.
      onMove(index, change + change);
    }
  };

  var renderAddMore = function renderAddMore() {
    var hiddenSections = sections.filter(function (section) {
      return section.isVisible === false;
    });

    if (hiddenSections.length === 0) {
      return null;
    }

    return Object(external_this_wp_element_["createElement"])(dropdown["a" /* default */], {
      position: "top center",
      className: "woocommerce-dashboard-section__add-more",
      renderToggle: function renderToggle(_ref2) {
        var onToggle = _ref2.onToggle,
            isOpen = _ref2.isOpen;
        return Object(external_this_wp_element_["createElement"])(build_module_button["a" /* default */], {
          onClick: onToggle,
          title: Object(external_this_wp_i18n_["__"])('Add more sections', 'woocommerce'),
          "aria-expanded": isOpen
        }, Object(external_this_wp_element_["createElement"])(build_module_icon["a" /* default */], {
          icon: plus_circle_filled
        }));
      },
      renderContent: function renderContent(_ref3) {
        var onToggle = _ref3.onToggle;
        return Object(external_this_wp_element_["createElement"])(external_this_wp_element_["Fragment"], null, Object(external_this_wp_element_["createElement"])(external_this_wc_components_["H"], null, Object(external_this_wp_i18n_["__"])('Dashboard Sections', 'woocommerce')), Object(external_this_wp_element_["createElement"])("div", {
          className: "woocommerce-dashboard-section__add-more-choices"
        }, hiddenSections.map(function (section) {
          return Object(external_this_wp_element_["createElement"])(build_module_button["a" /* default */], {
            key: section.key,
            onClick: toggleVisibility(section.key, onToggle),
            className: "woocommerce-dashboard-section__add-more-btn",
            title: Object(external_this_wp_i18n_["sprintf"])(Object(external_this_wp_i18n_["__"])('Add %s section', 'woocommerce'), section.title)
          }, Object(external_this_wp_element_["createElement"])(icon["a" /* default */], {
            icon: section.icon,
            size: 30
          }), Object(external_this_wp_element_["createElement"])("span", {
            className: "woocommerce-dashboard-section__add-more-btn-title"
          }, section.title));
        })));
      }
    });
  };

  var renderDashboardReports = function renderDashboardReports() {
    var _getDateParamsFromQue = Object(date["h" /* getDateParamsFromQuery */])(query, defaultDateRange),
        period = _getDateParamsFromQue.period,
        compare = _getDateParamsFromQue.compare,
        before = _getDateParamsFromQue.before,
        after = _getDateParamsFromQue.after;

    var _getCurrentDates = Object(date["f" /* getCurrentDates */])(query, defaultDateRange),
        primaryDate = _getCurrentDates.primary,
        secondaryDate = _getCurrentDates.secondary;

    var dateQuery = {
      period: period,
      compare: compare,
      before: before,
      after: after,
      primaryDate: primaryDate,
      secondaryDate: secondaryDate
    };
    var visibleSectionKeys = sections.filter(function (section) {
      return section.isVisible;
    }).map(function (section) {
      return section.key;
    });
    return Object(external_this_wp_element_["createElement"])(external_this_wp_element_["Fragment"], null, Object(external_this_wp_element_["createElement"])(report_filters["a" /* default */], {
      report: "dashboard",
      query: query,
      path: path,
      dateQuery: dateQuery,
      isoDateFormat: date["k" /* isoDateFormat */],
      filters: filters
    }), sections.map(function (section, index) {
      if (section.isVisible) {
        return Object(external_this_wp_element_["createElement"])(section_Section, {
          component: section.component,
          hiddenBlocks: section.hiddenBlocks,
          key: section.key,
          onChangeHiddenBlocks: onChangeHiddenBlocks(section.key),
          onTitleUpdate: onSectionTitleUpdate(section.key),
          path: path,
          query: query,
          title: section.title,
          onMove: Object(external_lodash_["partial"])(onMove, index),
          onRemove: toggleVisibility(section.key),
          isFirst: section.key === visibleSectionKeys[0],
          isLast: section.key === visibleSectionKeys[visibleSectionKeys.length - 1]
        });
      }

      return null;
    }), renderAddMore());
  };

  return Object(external_this_wp_element_["createElement"])(external_this_wp_element_["Fragment"], null, isTaskListEnabled && Object(external_this_wp_element_["createElement"])(external_this_wp_element_["Suspense"], {
    fallback: Object(external_this_wp_element_["createElement"])(external_this_wc_components_["Spinner"], null)
  }, Object(external_this_wp_element_["createElement"])(TaskList, {
    query: query,
    inline: isDashboardShown
  })), isDashboardShown && renderDashboardReports());
};

/* harmony default export */ var customizable = __webpack_exports__["default"] = (Object(compose["a" /* default */])(Object(with_select["a" /* default */])(function (select) {
  var _select = select(external_this_wc_data_["OPTIONS_STORE_NAME"]),
      getOption = _select.getOption;

  var _select$getSetting = select(external_this_wc_data_["SETTINGS_STORE_NAME"]).getSetting('wc_admin', 'wcAdminSettings'),
      defaultDateRange = _select$getSetting.woocommerce_default_date_range;

  var withSelectData = {
    defaultDateRange: defaultDateRange
  };

  if (Object(utils["e" /* isOnboardingEnabled */])()) {
    withSelectData.homepageEnabled = window.wcAdminFeatures.homescreen && getOption('woocommerce_homescreen_enabled') === 'yes';
    withSelectData.taskListHidden = getOption('woocommerce_task_list_hidden') === 'yes';
    withSelectData.taskListComplete = getOption('woocommerce_task_list_complete') === 'yes';
  }

  return withSelectData;
}))(customizable_CustomizableDashboard));

/***/ })

}]);
