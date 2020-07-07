(window["webpackJsonp"] = window["webpackJsonp"] || []).push([[18],{

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

/***/ 265:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "a", function() { return Spinner; });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);

function Spinner() {
  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("span", {
    className: "components-spinner"
  });
}
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

/***/ 720:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "a", function() { return SelectControl; });
/* harmony import */ var _babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(12);
/* harmony import */ var _babel_runtime_helpers_esm_toConsumableArray__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(15);
/* harmony import */ var _babel_runtime_helpers_esm_objectWithoutProperties__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(14);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(2);
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(112);
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
      label = _ref.label,
      _ref$multiple = _ref.multiple,
      multiple = _ref$multiple === void 0 ? false : _ref$multiple,
      onChange = _ref.onChange,
      _ref$options = _ref.options,
      options = _ref$options === void 0 ? [] : _ref$options,
      className = _ref.className,
      hideLabelFromVision = _ref.hideLabelFromVision,
      props = Object(_babel_runtime_helpers_esm_objectWithoutProperties__WEBPACK_IMPORTED_MODULE_2__[/* default */ "a"])(_ref, ["help", "label", "multiple", "onChange", "options", "className", "hideLabelFromVision"]);

  var instanceId = Object(_wordpress_compose__WEBPACK_IMPORTED_MODULE_5__[/* default */ "a"])(SelectControl);
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
//# sourceMappingURL=index.js.map

/***/ }),

/***/ 729:
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
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(2);
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_8__);
/* harmony import */ var _wordpress_a11y__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(127);
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(54);









function _createSuper(Derived) { return function () { var Super = Object(_babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_5__[/* default */ "a"])(Derived), result; if (_isNativeReflectConstruct()) { var NewTarget = Object(_babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_5__[/* default */ "a"])(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return Object(_babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_4__[/* default */ "a"])(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */




/**
 * A Higher Order Component used to be provide a unique instance ID by
 * component.
 *
 * @param {WPComponent} WrappedComponent  The wrapped component.
 *
 * @return {WPComponent} The component to be rendered.
 */

/* harmony default export */ __webpack_exports__["a"] = (Object(_wordpress_compose__WEBPACK_IMPORTED_MODULE_10__[/* default */ "a"])(function (WrappedComponent) {
  return /*#__PURE__*/function (_Component) {
    Object(_babel_runtime_helpers_esm_inherits__WEBPACK_IMPORTED_MODULE_6__[/* default */ "a"])(_class, _Component);

    var _super = _createSuper(_class);

    function _class() {
      var _this;

      Object(_babel_runtime_helpers_esm_classCallCheck__WEBPACK_IMPORTED_MODULE_1__[/* default */ "a"])(this, _class);

      _this = _super.apply(this, arguments);
      _this.debouncedSpeak = Object(lodash__WEBPACK_IMPORTED_MODULE_8__["debounce"])(_this.speak.bind(Object(_babel_runtime_helpers_esm_assertThisInitialized__WEBPACK_IMPORTED_MODULE_3__[/* default */ "a"])(_this)), 500);
      return _this;
    }

    Object(_babel_runtime_helpers_esm_createClass__WEBPACK_IMPORTED_MODULE_2__[/* default */ "a"])(_class, [{
      key: "speak",
      value: function speak(message) {
        var type = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : 'polite';

        Object(_wordpress_a11y__WEBPACK_IMPORTED_MODULE_9__[/* speak */ "a"])(message, type);
      }
    }, {
      key: "componentWillUnmount",
      value: function componentWillUnmount() {
        this.debouncedSpeak.cancel();
      }
    }, {
      key: "render",
      value: function render() {
        return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])(WrappedComponent, Object(_babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_0__[/* default */ "a"])({}, this.props, {
          speak: this.speak,
          debouncedSpeak: this.debouncedSpeak
        }));
      }
    }]);

    return _class;
  }(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["Component"]);
}, 'withSpokenMessages'));
//# sourceMappingURL=index.js.map

/***/ }),

/***/ 768:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(32);


/**
 * WordPress dependencies
 */

var check = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__[/* SVG */ "b"], {
  xmlns: "http://www.w3.org/2000/svg",
  viewBox: "0 0 24 24"
}, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(_wordpress_primitives__WEBPACK_IMPORTED_MODULE_1__[/* Path */ "a"], {
  d: "M9 18.6L3.5 13l1-1L9 16.4l9.5-9.9 1 1z"
}));
/* harmony default export */ __webpack_exports__["a"] = (check);
//# sourceMappingURL=check.js.map

/***/ }),

/***/ 771:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "a", function() { return CheckboxControl; });
/* harmony import */ var _babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(12);
/* harmony import */ var _babel_runtime_helpers_esm_objectWithoutProperties__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(14);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(0);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(112);
/* harmony import */ var _wordpress_icons__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(422);
/* harmony import */ var _wordpress_icons__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(768);
/* harmony import */ var _base_control__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(171);




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
      onChange = _ref.onChange,
      props = Object(_babel_runtime_helpers_esm_objectWithoutProperties__WEBPACK_IMPORTED_MODULE_1__[/* default */ "a"])(_ref, ["label", "className", "heading", "checked", "help", "onChange"]);

  var instanceId = Object(_wordpress_compose__WEBPACK_IMPORTED_MODULE_3__[/* default */ "a"])(CheckboxControl);
  var id = "inspector-checkbox-control-".concat(instanceId);

  var onChangeValue = function onChangeValue(event) {
    return onChange(event.target.checked);
  };

  return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__["createElement"])(_base_control__WEBPACK_IMPORTED_MODULE_6__[/* default */ "a"], {
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
  }, props)), checked ? Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__["createElement"])(_wordpress_icons__WEBPACK_IMPORTED_MODULE_4__[/* default */ "a"], {
    icon: _wordpress_icons__WEBPACK_IMPORTED_MODULE_5__[/* default */ "a"],
    className: "components-checkbox-control__checked",
    role: "presentation"
  }) : null), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__["createElement"])("label", {
    className: "components-checkbox-control__label",
    htmlFor: id
  }, label));
}
//# sourceMappingURL=index.js.map

/***/ }),

/***/ 794:
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),

/***/ 795:
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),

/***/ 796:
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),

/***/ 930:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
// ESM COMPAT FLAG
__webpack_require__.r(__webpack_exports__);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/extends.js
var helpers_extends = __webpack_require__(80);
var extends_default = /*#__PURE__*/__webpack_require__.n(helpers_extends);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/toConsumableArray.js
var toConsumableArray = __webpack_require__(41);
var toConsumableArray_default = /*#__PURE__*/__webpack_require__.n(toConsumableArray);

// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/defineProperty.js
var defineProperty = __webpack_require__(17);
var defineProperty_default = /*#__PURE__*/__webpack_require__.n(defineProperty);

// EXTERNAL MODULE: external {"this":["wp","element"]}
var external_this_wp_element_ = __webpack_require__(0);

// EXTERNAL MODULE: external {"this":["wp","i18n"]}
var external_this_wp_i18n_ = __webpack_require__(3);

// EXTERNAL MODULE: ./node_modules/@wordpress/components/build-module/button/index.js
var build_module_button = __webpack_require__(67);

// EXTERNAL MODULE: ./node_modules/@wordpress/compose/build-module/higher-order/compose.js
var compose = __webpack_require__(169);

// EXTERNAL MODULE: external {"this":["wp","data"]}
var external_this_wp_data_ = __webpack_require__(18);

// EXTERNAL MODULE: external {"this":["wc","components"]}
var external_this_wc_components_ = __webpack_require__(53);

// EXTERNAL MODULE: external {"this":["wc","data"]}
var external_this_wc_data_ = __webpack_require__(43);

// EXTERNAL MODULE: ./client/analytics/settings/index.scss
var settings = __webpack_require__(794);

// EXTERNAL MODULE: ./client/analytics/settings/config.js + 1 modules
var config = __webpack_require__(270);

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

// EXTERNAL MODULE: ./node_modules/@wordpress/components/build-module/checkbox-control/index.js
var checkbox_control = __webpack_require__(771);

// EXTERNAL MODULE: ./node_modules/prop-types/index.js
var prop_types = __webpack_require__(1);
var prop_types_default = /*#__PURE__*/__webpack_require__.n(prop_types);

// EXTERNAL MODULE: external "lodash"
var external_lodash_ = __webpack_require__(2);

// EXTERNAL MODULE: ./client/analytics/settings/setting.scss
var settings_setting = __webpack_require__(795);

// CONCATENATED MODULE: ./client/analytics/settings/setting.js








function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = getPrototypeOf_default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = getPrototypeOf_default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return possibleConstructorReturn_default()(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/**
 * External dependencies
 */






/**
 * Internal dependencies
 */



var setting_Setting = /*#__PURE__*/function (_Component) {
  inherits_default()(Setting, _Component);

  var _super = _createSuper(Setting);

  function Setting(props) {
    var _this;

    classCallCheck_default()(this, Setting);

    _this = _super.call(this, props);

    _this.renderInput = function () {
      var _this$props = _this.props,
          handleChange = _this$props.handleChange,
          name = _this$props.name,
          inputText = _this$props.inputText,
          inputType = _this$props.inputType,
          options = _this$props.options,
          value = _this$props.value,
          component = _this$props.component;
      var disabled = _this.state.disabled;

      switch (inputType) {
        case 'checkboxGroup':
          return options.map(function (optionGroup) {
            return optionGroup.options.length > 0 && Object(external_this_wp_element_["createElement"])("div", {
              className: "woocommerce-setting__options-group",
              key: optionGroup.key,
              "aria-labelledby": name + '-label'
            }, optionGroup.label && Object(external_this_wp_element_["createElement"])("span", {
              className: "woocommerce-setting__options-group-label"
            }, optionGroup.label), _this.renderCheckboxOptions(optionGroup.options));
          });

        case 'checkbox':
          return _this.renderCheckboxOptions(options);

        case 'button':
          return Object(external_this_wp_element_["createElement"])(build_module_button["a" /* default */], {
            isSecondary: true,
            onClick: _this.handleInputCallback,
            disabled: disabled
          }, inputText);

        case 'component':
          var SettingComponent = component;
          return Object(external_this_wp_element_["createElement"])(SettingComponent, extends_default()({
            value: value,
            onChange: handleChange
          }, _this.props));

        case 'text':
        default:
          var id = Object(external_lodash_["uniqueId"])(name);
          return Object(external_this_wp_element_["createElement"])("input", {
            id: id,
            type: "text",
            name: name,
            onChange: handleChange,
            value: value,
            placeholder: inputText,
            disabled: disabled
          });
      }
    };

    _this.handleInputCallback = function () {
      var _this$props2 = _this.props,
          createNotice = _this$props2.createNotice,
          callback = _this$props2.callback;

      if (typeof callback !== 'function') {
        return;
      }

      return new Promise(function (resolve, reject) {
        _this.setState({
          disabled: true
        });

        callback(resolve, reject, createNotice);
      }).then(function () {
        _this.setState({
          disabled: false
        });
      }).catch(function () {
        _this.setState({
          disabled: false
        });
      });
    };

    _this.state = {
      disabled: false
    };
    return _this;
  }

  createClass_default()(Setting, [{
    key: "renderCheckboxOptions",
    value: function renderCheckboxOptions(options) {
      var _this$props3 = this.props,
          handleChange = _this$props3.handleChange,
          name = _this$props3.name,
          value = _this$props3.value;
      var disabled = this.state.disabled;
      return options.map(function (option) {
        return Object(external_this_wp_element_["createElement"])(checkbox_control["a" /* default */], {
          key: name + '-' + option.value,
          label: option.label,
          name: name,
          checked: value && value.includes(option.value),
          onChange: function onChange(checked) {
            return handleChange({
              target: {
                checked: checked,
                name: name,
                type: 'checkbox',
                value: option.value
              }
            });
          },
          disabled: disabled
        });
      });
    }
  }, {
    key: "render",
    value: function render() {
      var _this$props4 = this.props,
          helpText = _this$props4.helpText,
          label = _this$props4.label,
          name = _this$props4.name;
      return Object(external_this_wp_element_["createElement"])("div", {
        className: "woocommerce-setting"
      }, Object(external_this_wp_element_["createElement"])("div", {
        className: "woocommerce-setting__label",
        id: name + '-label'
      }, label), Object(external_this_wp_element_["createElement"])("div", {
        className: "woocommerce-setting__input"
      }, this.renderInput(), helpText && Object(external_this_wp_element_["createElement"])("span", {
        className: "woocommerce-setting__help"
      }, helpText)));
    }
  }]);

  return Setting;
}(external_this_wp_element_["Component"]);

setting_Setting.propTypes = {
  /**
   * A callback that is fired after actionable items, such as buttons.
   */
  callback: prop_types_default.a.func,

  /**
   * Function assigned to the onChange of all inputs.
   */
  handleChange: prop_types_default.a.func.isRequired,

  /**
   * Optional help text displayed underneath the setting.
   */
  helpText: prop_types_default.a.oneOfType([prop_types_default.a.string, prop_types_default.a.array]),

  /**
   * Text used as placeholder or button text in the input area.
   */
  inputText: prop_types_default.a.string,

  /**
   * Type of input to use; defaults to a text input.
   */
  inputType: prop_types_default.a.oneOf(['button', 'checkbox', 'checkboxGroup', 'text', 'component']),

  /**
   * Label used for describing the setting.
   */
  label: prop_types_default.a.string.isRequired,

  /**
   * Setting slug applied to input names.
   */
  name: prop_types_default.a.string.isRequired,

  /**
   * Array of options used for when the `inputType` allows multiple selections.
   */
  options: prop_types_default.a.arrayOf(prop_types_default.a.shape({
    /**
     * Input value for this option.
     */
    value: prop_types_default.a.string,

    /**
     * Label for this option or above a group for a group `inputType`.
     */
    label: prop_types_default.a.string,

    /**
     * Description used for screen readers.
     */
    description: prop_types_default.a.string,

    /**
     * Key used for a group `inputType`.
     */
    key: prop_types_default.a.string,

    /**
     * Nested options for a group `inputType`.
     */
    options: prop_types_default.a.array
  })),

  /**
   * The string value used for the input or array of items if the input allows multiselection.
   */
  value: prop_types_default.a.oneOfType([prop_types_default.a.string, prop_types_default.a.array])
};
/* harmony default export */ var analytics_settings_setting = (Object(compose["a" /* default */])(Object(external_this_wp_data_["withDispatch"])(function (dispatch) {
  var _dispatch = dispatch('core/notices'),
      createNotice = _dispatch.createNotice;

  return {
    createNotice: createNotice
  };
}))(setting_Setting));
// EXTERNAL MODULE: ./node_modules/@babel/runtime/helpers/assertThisInitialized.js
var assertThisInitialized = __webpack_require__(62);
var assertThisInitialized_default = /*#__PURE__*/__webpack_require__.n(assertThisInitialized);

// EXTERNAL MODULE: external {"this":["wp","url"]}
var external_this_wp_url_ = __webpack_require__(27);

// EXTERNAL MODULE: external {"this":["wp","apiFetch"]}
var external_this_wp_apiFetch_ = __webpack_require__(21);
var external_this_wp_apiFetch_default = /*#__PURE__*/__webpack_require__.n(external_this_wp_apiFetch_);

// EXTERNAL MODULE: external "moment"
var external_moment_ = __webpack_require__(16);
var external_moment_default = /*#__PURE__*/__webpack_require__.n(external_moment_);

// EXTERNAL MODULE: ./node_modules/@wordpress/components/build-module/higher-order/with-spoken-messages/index.js
var with_spoken_messages = __webpack_require__(729);

// CONCATENATED MODULE: ./client/analytics/settings/historical-data/utils.js
/**
 * External dependencies
 */


var utils_formatParams = function formatParams(dateFormat, period, skipChecked) {
  var params = {};

  if (skipChecked) {
    params.skip_existing = true;
  }

  if (period.label !== 'all') {
    if (period.label === 'custom') {
      var daysDifference = external_moment_default()().diff(external_moment_default()(period.date, dateFormat), 'days', true);
      params.days = Math.floor(daysDifference);
    } else {
      params.days = parseInt(period.label, 10);
    }
  }

  return params;
};
var utils_getStatus = function getStatus(_ref) {
  var customersProgress = _ref.customersProgress,
      customersTotal = _ref.customersTotal,
      inProgress = _ref.inProgress,
      ordersProgress = _ref.ordersProgress,
      ordersTotal = _ref.ordersTotal;

  if (inProgress) {
    if (Object(external_lodash_["isNil"])(customersProgress) || Object(external_lodash_["isNil"])(ordersProgress) || Object(external_lodash_["isNil"])(customersTotal) || Object(external_lodash_["isNil"])(ordersTotal)) {
      return 'initializing';
    }

    if (customersProgress < customersTotal) {
      return 'customers';
    }

    if (ordersProgress < ordersTotal) {
      return 'orders';
    }

    return 'finalizing';
  }

  if (customersTotal > 0 || ordersTotal > 0) {
    if (customersProgress === customersTotal && ordersProgress === ordersTotal) {
      return 'finished';
    }

    return 'ready';
  }

  return 'nothing';
};
// EXTERNAL MODULE: ./node_modules/@fresh-data/framework/es/index.js + 8 modules
var es = __webpack_require__(170);

// EXTERNAL MODULE: ./client/wc-api/constants.js
var constants = __webpack_require__(33);

// CONCATENATED MODULE: ./client/analytics/settings/historical-data/actions.js


/**
 * External dependencies
 */




function HistoricalDataActions(_ref) {
  var importDate = _ref.importDate,
      onDeletePreviousData = _ref.onDeletePreviousData,
      onReimportData = _ref.onReimportData,
      onStartImport = _ref.onStartImport,
      onStopImport = _ref.onStopImport,
      status = _ref.status;

  var getActions = function getActions() {
    var importDisabled = status !== 'ready'; // An import is currently in progress

    if (['initializing', 'customers', 'orders', 'finalizing'].includes(status)) {
      return Object(external_this_wp_element_["createElement"])(external_this_wp_element_["Fragment"], null, Object(external_this_wp_element_["createElement"])(build_module_button["a" /* default */], {
        className: "woocommerce-settings-historical-data__action-button",
        isPrimary: true,
        onClick: onStopImport
      }, Object(external_this_wp_i18n_["__"])('Stop Import', 'woocommerce')), Object(external_this_wp_element_["createElement"])("div", {
        className: "woocommerce-setting__help woocommerce-settings-historical-data__action-help"
      }, Object(external_this_wp_i18n_["__"])('Imported data will not be lost if the import is stopped.', 'woocommerce'), Object(external_this_wp_element_["createElement"])("br", null), Object(external_this_wp_i18n_["__"])('Navigating away from this page will not affect the import.', 'woocommerce')));
    }

    if (['ready', 'nothing'].includes(status)) {
      if (importDate) {
        return Object(external_this_wp_element_["createElement"])(external_this_wp_element_["Fragment"], null, Object(external_this_wp_element_["createElement"])(build_module_button["a" /* default */], {
          isPrimary: true,
          onClick: onStartImport,
          disabled: importDisabled
        }, Object(external_this_wp_i18n_["__"])('Start', 'woocommerce')), Object(external_this_wp_element_["createElement"])(build_module_button["a" /* default */], {
          isSecondary: true,
          onClick: onDeletePreviousData
        }, Object(external_this_wp_i18n_["__"])('Delete Previously Imported Data', 'woocommerce')));
      }

      return Object(external_this_wp_element_["createElement"])(external_this_wp_element_["Fragment"], null, Object(external_this_wp_element_["createElement"])(build_module_button["a" /* default */], {
        isPrimary: true,
        onClick: onStartImport,
        disabled: importDisabled
      }, Object(external_this_wp_i18n_["__"])('Start', 'woocommerce')));
    } // Has imported all possible data


    return Object(external_this_wp_element_["createElement"])(external_this_wp_element_["Fragment"], null, Object(external_this_wp_element_["createElement"])(build_module_button["a" /* default */], {
      isSecondary: true,
      onClick: onReimportData
    }, Object(external_this_wp_i18n_["__"])('Re-import Data', 'woocommerce')), Object(external_this_wp_element_["createElement"])(build_module_button["a" /* default */], {
      isSecondary: true,
      onClick: onDeletePreviousData
    }, Object(external_this_wp_i18n_["__"])('Delete Previously Imported Data', 'woocommerce')));
  };

  return Object(external_this_wp_element_["createElement"])("div", {
    className: "woocommerce-settings__actions woocommerce-settings-historical-data__actions"
  }, getActions());
}

/* harmony default export */ var actions = (HistoricalDataActions);
// EXTERNAL MODULE: ./node_modules/@wordpress/components/build-module/select-control/index.js
var select_control = __webpack_require__(720);

// EXTERNAL MODULE: ./client/lib/date.js
var lib_date = __webpack_require__(108);

// CONCATENATED MODULE: ./client/analytics/settings/historical-data/period-selector.js


/**
 * External dependencies
 */



/**
 * WooCommerce dependencies
 */




function HistoricalDataPeriodSelector(_ref) {
  var dateFormat = _ref.dateFormat,
      disabled = _ref.disabled,
      onDateChange = _ref.onDateChange,
      onPeriodChange = _ref.onPeriodChange,
      value = _ref.value;

  var onSelectChange = function onSelectChange(val) {
    onPeriodChange(val);
  };

  var onDatePickerChange = function onDatePickerChange(val) {
    if (val.date && val.date.isValid) {
      onDateChange(val.date.format(dateFormat));
    } else {
      onDateChange(val.text);
    }
  };

  var getDatePickerError = function getDatePickerError(momentDate) {
    if (!momentDate.isValid() || value.date.length !== dateFormat.length) {
      return lib_date["b" /* dateValidationMessages */].invalid;
    }

    if (momentDate.isAfter(new Date(), 'day')) {
      return lib_date["b" /* dateValidationMessages */].future;
    }

    return null;
  };

  var getDatePicker = function getDatePicker() {
    var momentDate = external_moment_default()(value.date, dateFormat);
    return Object(external_this_wp_element_["createElement"])("div", {
      className: "woocommerce-settings-historical-data__column"
    }, Object(external_this_wp_element_["createElement"])("div", {
      className: "woocommerce-settings-historical-data__column-label"
    }, Object(external_this_wp_i18n_["__"])('Beginning on', 'woocommerce')), Object(external_this_wp_element_["createElement"])(external_this_wc_components_["DatePicker"], {
      date: momentDate.isValid() ? momentDate.toDate() : null,
      dateFormat: dateFormat,
      disabled: disabled,
      error: getDatePickerError(momentDate),
      isInvalidDate: function isInvalidDate(date) {
        return external_moment_default()(date).isAfter(new Date(), 'day');
      },
      onUpdate: onDatePickerChange,
      text: value.date
    }));
  };

  return Object(external_this_wp_element_["createElement"])("div", {
    className: "woocommerce-settings-historical-data__columns"
  }, Object(external_this_wp_element_["createElement"])("div", {
    className: "woocommerce-settings-historical-data__column"
  }, Object(external_this_wp_element_["createElement"])(select_control["a" /* default */], {
    label: Object(external_this_wp_i18n_["__"])('Import Historical Data', 'woocommerce'),
    value: value.label,
    disabled: disabled,
    onChange: onSelectChange,
    options: [{
      label: 'All',
      value: 'all'
    }, {
      label: 'Last 365 days',
      value: '365'
    }, {
      label: 'Last 90 days',
      value: '90'
    }, {
      label: 'Last 30 days',
      value: '30'
    }, {
      label: 'Last 7 days',
      value: '7'
    }, {
      label: 'Last 24 hours',
      value: '1'
    }, {
      label: 'Custom',
      value: 'custom'
    }]
  })), value.label === 'custom' && getDatePicker());
}

/* harmony default export */ var period_selector = (HistoricalDataPeriodSelector);
// CONCATENATED MODULE: ./client/analytics/settings/historical-data/progress.js


/**
 * External dependencies
 */



function HistoricalDataProgress(_ref) {
  var label = _ref.label,
      progress = _ref.progress,
      total = _ref.total;
  var labelText = Object(external_this_wp_i18n_["sprintf"])(Object(external_this_wp_i18n_["__"])('Imported %(label)s', 'woocommerce'), {
    label: label
  });
  var labelCounters = !Object(external_lodash_["isNil"])(total) ? Object(external_this_wp_i18n_["sprintf"])(Object(external_this_wp_i18n_["__"])('%(progress)s of %(total)s', 'woocommerce'), {
    progress: progress || 0,
    total: total
  }) : null;
  return Object(external_this_wp_element_["createElement"])("div", {
    className: "woocommerce-settings-historical-data__progress"
  }, Object(external_this_wp_element_["createElement"])("span", {
    className: "woocommerce-settings-historical-data__progress-label"
  }, labelText), labelCounters && Object(external_this_wp_element_["createElement"])("span", {
    className: "woocommerce-settings-historical-data__progress-label"
  }, labelCounters), Object(external_this_wp_element_["createElement"])("progress", {
    className: "woocommerce-settings-historical-data__progress-bar",
    max: total,
    value: progress || 0
  }));
}

/* harmony default export */ var historical_data_progress = (HistoricalDataProgress);
// EXTERNAL MODULE: external {"this":["wp","hooks"]}
var external_this_wp_hooks_ = __webpack_require__(48);

// EXTERNAL MODULE: ./node_modules/@wordpress/components/build-module/spinner/index.js
var spinner = __webpack_require__(265);

// CONCATENATED MODULE: ./client/analytics/settings/historical-data/status.js


/**
 * External dependencies
 */




/**
 * WooCommerce dependencies
 */


var HISTORICAL_DATA_STATUS_FILTER = 'woocommerce_admin_import_status';

function HistoricalDataStatus(_ref) {
  var importDate = _ref.importDate,
      status = _ref.status;
  var statusLabels = Object(external_this_wp_hooks_["applyFilters"])(HISTORICAL_DATA_STATUS_FILTER, {
    nothing: Object(external_this_wp_i18n_["__"])('Nothing To Import', 'woocommerce'),
    ready: Object(external_this_wp_i18n_["__"])('Ready To Import', 'woocommerce'),
    initializing: [Object(external_this_wp_i18n_["__"])('Initializing', 'woocommerce'), Object(external_this_wp_element_["createElement"])(spinner["a" /* default */], {
      key: "spinner"
    })],
    customers: [Object(external_this_wp_i18n_["__"])('Importing Customers', 'woocommerce'), Object(external_this_wp_element_["createElement"])(spinner["a" /* default */], {
      key: "spinner"
    })],
    orders: [Object(external_this_wp_i18n_["__"])('Importing Orders', 'woocommerce'), Object(external_this_wp_element_["createElement"])(spinner["a" /* default */], {
      key: "spinner"
    })],
    finalizing: [Object(external_this_wp_i18n_["__"])('Finalizing', 'woocommerce'), Object(external_this_wp_element_["createElement"])(spinner["a" /* default */], {
      key: "spinner"
    })],
    finished: importDate === -1 ? Object(external_this_wp_i18n_["__"])('All historical data imported', 'woocommerce') : Object(external_this_wp_i18n_["sprintf"])(Object(external_this_wp_i18n_["__"])('Historical data from %s onward imported', 'woocommerce'), // @todo The date formatting should be localized ( 'll' ), but this is currently broken in Gutenberg.
    // See https://github.com/WordPress/gutenberg/issues/12626 for details.
    external_moment_default()(importDate).format('YYYY-MM-DD'))
  });
  return Object(external_this_wp_element_["createElement"])("span", {
    className: "woocommerce-settings-historical-data__status"
  }, Object(external_this_wp_i18n_["__"])('Status:', 'woocommerce') + ' ', statusLabels[status]);
}

/* harmony default export */ var historical_data_status = (Object(external_this_wc_components_["useFilters"])(HISTORICAL_DATA_STATUS_FILTER)(HistoricalDataStatus));
// CONCATENATED MODULE: ./client/analytics/settings/historical-data/skip-checkbox.js


/**
 * External dependencies
 */



function HistoricalDataSkipCheckbox(_ref) {
  var checked = _ref.checked,
      disabled = _ref.disabled,
      onChange = _ref.onChange;
  return Object(external_this_wp_element_["createElement"])(checkbox_control["a" /* default */], {
    className: "woocommerce-settings-historical-data__skip-checkbox",
    checked: checked,
    disabled: disabled,
    label: Object(external_this_wp_i18n_["__"])('Skip previously imported customers and orders', 'woocommerce'),
    onChange: onChange
  });
}

/* harmony default export */ var skip_checkbox = (HistoricalDataSkipCheckbox);
// EXTERNAL MODULE: ./client/wc-api/with-select.js
var with_select = __webpack_require__(101);

// EXTERNAL MODULE: ./client/analytics/settings/historical-data/style.scss
var style = __webpack_require__(796);

// CONCATENATED MODULE: ./client/analytics/settings/historical-data/layout.js







function layout_createSuper(Derived) { var hasNativeReflectConstruct = layout_isNativeReflectConstruct(); return function _createSuperInternal() { var Super = getPrototypeOf_default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = getPrototypeOf_default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return possibleConstructorReturn_default()(this, result); }; }

function layout_isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/**
 * External dependencies
 */




/**
 * WooCommerce dependencies
 */


/**
 * Internal dependencies
 */











var layout_HistoricalDataLayout = /*#__PURE__*/function (_Component) {
  inherits_default()(HistoricalDataLayout, _Component);

  var _super = layout_createSuper(HistoricalDataLayout);

  function HistoricalDataLayout() {
    classCallCheck_default()(this, HistoricalDataLayout);

    return _super.apply(this, arguments);
  }

  createClass_default()(HistoricalDataLayout, [{
    key: "render",
    value: function render() {
      var _this$props = this.props,
          customersProgress = _this$props.customersProgress,
          customersTotal = _this$props.customersTotal,
          dateFormat = _this$props.dateFormat,
          importDate = _this$props.importDate,
          inProgress = _this$props.inProgress,
          onPeriodChange = _this$props.onPeriodChange,
          onDateChange = _this$props.onDateChange,
          onSkipChange = _this$props.onSkipChange,
          onDeletePreviousData = _this$props.onDeletePreviousData,
          onReimportData = _this$props.onReimportData,
          onStartImport = _this$props.onStartImport,
          onStopImport = _this$props.onStopImport,
          ordersProgress = _this$props.ordersProgress,
          ordersTotal = _this$props.ordersTotal,
          period = _this$props.period,
          skipChecked = _this$props.skipChecked;
      var status = utils_getStatus({
        customersProgress: customersProgress,
        customersTotal: customersTotal,
        inProgress: inProgress,
        ordersProgress: ordersProgress,
        ordersTotal: ordersTotal
      });
      return Object(external_this_wp_element_["createElement"])(external_this_wp_element_["Fragment"], null, Object(external_this_wp_element_["createElement"])(external_this_wc_components_["SectionHeader"], {
        title: Object(external_this_wp_i18n_["__"])('Import Historical Data', 'woocommerce')
      }), Object(external_this_wp_element_["createElement"])("div", {
        className: "woocommerce-settings__wrapper"
      }, Object(external_this_wp_element_["createElement"])("div", {
        className: "woocommerce-setting"
      }, Object(external_this_wp_element_["createElement"])("div", {
        className: "woocommerce-setting__input"
      }, Object(external_this_wp_element_["createElement"])("span", {
        className: "woocommerce-setting__help"
      }, Object(external_this_wp_i18n_["__"])('This tool populates historical analytics data by processing customers ' + 'and orders created prior to activating WooCommerce Admin.', 'woocommerce')), status !== 'finished' && Object(external_this_wp_element_["createElement"])(external_this_wp_element_["Fragment"], null, Object(external_this_wp_element_["createElement"])(period_selector, {
        dateFormat: dateFormat,
        disabled: inProgress,
        onPeriodChange: onPeriodChange,
        onDateChange: onDateChange,
        value: period
      }), Object(external_this_wp_element_["createElement"])(skip_checkbox, {
        disabled: inProgress,
        checked: skipChecked,
        onChange: onSkipChange
      }), Object(external_this_wp_element_["createElement"])(historical_data_progress, {
        label: Object(external_this_wp_i18n_["__"])('Registered Customers', 'woocommerce'),
        progress: customersProgress,
        total: customersTotal
      }), Object(external_this_wp_element_["createElement"])(historical_data_progress, {
        label: Object(external_this_wp_i18n_["__"])('Orders and Refunds', 'woocommerce'),
        progress: ordersProgress,
        total: ordersTotal
      })), Object(external_this_wp_element_["createElement"])(historical_data_status, {
        importDate: importDate,
        status: status
      })))), Object(external_this_wp_element_["createElement"])(actions, {
        importDate: importDate,
        onDeletePreviousData: onDeletePreviousData,
        onReimportData: onReimportData,
        onStartImport: onStartImport,
        onStopImport: onStopImport,
        status: status
      }));
    }
  }]);

  return HistoricalDataLayout;
}(external_this_wp_element_["Component"]);

/* harmony default export */ var layout = (Object(with_select["a" /* default */])(function (select, props) {
  var _select = select('wc-api'),
      getImportStatus = _select.getImportStatus,
      isGetImportStatusRequesting = _select.isGetImportStatusRequesting,
      getImportTotals = _select.getImportTotals;

  var activeImport = props.activeImport,
      dateFormat = props.dateFormat,
      lastImportStartTimestamp = props.lastImportStartTimestamp,
      lastImportStopTimestamp = props.lastImportStopTimestamp,
      onImportStarted = props.onImportStarted,
      onImportFinished = props.onImportFinished,
      period = props.period,
      skipChecked = props.skipChecked;
  var inProgress = typeof lastImportStartTimestamp !== 'undefined' && typeof lastImportStopTimestamp === 'undefined' || lastImportStartTimestamp > lastImportStopTimestamp;
  var params = utils_formatParams(dateFormat, period, skipChecked); // Use timestamp to invalidate previous totals when the import finished/stopped

  var _getImportTotals = getImportTotals(params, lastImportStopTimestamp),
      customers = _getImportTotals.customers,
      orders = _getImportTotals.orders;

  var requirement = inProgress ? {
    freshness: 3 * es["c" /* SECOND */],
    timeout: 3 * es["c" /* SECOND */]
  } : constants["a" /* DEFAULT_REQUIREMENT */]; // Use timestamp to invalidate previous status when a new import starts

  var _getImportStatus = getImportStatus(lastImportStartTimestamp, requirement),
      customersStatus = _getImportStatus.customers,
      importDate = _getImportStatus.imported_from,
      isImporting = _getImportStatus.is_importing,
      ordersStatus = _getImportStatus.orders;

  var _ref = customersStatus || {},
      customersProgress = _ref.imported,
      customersTotal = _ref.total;

  var _ref2 = ordersStatus || {},
      ordersProgress = _ref2.imported,
      ordersTotal = _ref2.total;

  var isStatusLoading = isGetImportStatusRequesting(lastImportStartTimestamp);
  var hasImportStarted = Boolean(!lastImportStartTimestamp && !isStatusLoading && !inProgress && isImporting === true);

  if (hasImportStarted) {
    onImportStarted();
  }

  var hasImportFinished = Boolean(!isStatusLoading && inProgress && isImporting === false && (customersProgress === customersTotal && customersTotal > 0 || ordersProgress === ordersTotal && ordersTotal > 0));

  if (hasImportFinished) {
    onImportFinished();
  }

  if (!activeImport) {
    return {
      customersTotal: customers,
      importDate: importDate,
      ordersTotal: orders
    };
  }

  return {
    customersProgress: customersProgress,
    customersTotal: Object(external_lodash_["isNil"])(customersTotal) ? customers : customersTotal,
    importDate: importDate,
    inProgress: inProgress,
    ordersProgress: ordersProgress,
    ordersTotal: Object(external_lodash_["isNil"])(ordersTotal) ? orders : ordersTotal
  };
})(layout_HistoricalDataLayout));
// EXTERNAL MODULE: ./client/lib/tracks.js
var tracks = __webpack_require__(63);

// CONCATENATED MODULE: ./client/analytics/settings/historical-data/index.js









function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { defineProperty_default()(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function historical_data_createSuper(Derived) { var hasNativeReflectConstruct = historical_data_isNativeReflectConstruct(); return function _createSuperInternal() { var Super = getPrototypeOf_default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = getPrototypeOf_default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return possibleConstructorReturn_default()(this, result); }; }

function historical_data_isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/**
 * External dependencies
 */








/**
 * Internal dependencies
 */







var historical_data_HistoricalData = /*#__PURE__*/function (_Component) {
  inherits_default()(HistoricalData, _Component);

  var _super = historical_data_createSuper(HistoricalData);

  function HistoricalData() {
    var _this;

    classCallCheck_default()(this, HistoricalData);

    _this = _super.apply(this, arguments);
    _this.dateFormat = Object(external_this_wp_i18n_["__"])('MM/DD/YYYY', 'woocommerce');
    _this.state = {
      // Whether there is an active import (which might have been stopped)
      // that matches the period and skipChecked settings
      activeImport: null,
      lastImportStartTimestamp: 0,
      lastImportStopTimestamp: 0,
      period: {
        date: external_moment_default()().format(_this.dateFormat),
        label: 'all'
      },
      skipChecked: true
    };
    _this.makeQuery = _this.makeQuery.bind(assertThisInitialized_default()(_this));
    _this.onImportFinished = _this.onImportFinished.bind(assertThisInitialized_default()(_this));
    _this.onImportStarted = _this.onImportStarted.bind(assertThisInitialized_default()(_this));
    _this.onDeletePreviousData = _this.onDeletePreviousData.bind(assertThisInitialized_default()(_this));
    _this.onReimportData = _this.onReimportData.bind(assertThisInitialized_default()(_this));
    _this.onStartImport = _this.onStartImport.bind(assertThisInitialized_default()(_this));
    _this.onStopImport = _this.onStopImport.bind(assertThisInitialized_default()(_this));
    _this.onDateChange = _this.onDateChange.bind(assertThisInitialized_default()(_this));
    _this.onPeriodChange = _this.onPeriodChange.bind(assertThisInitialized_default()(_this));
    _this.onSkipChange = _this.onSkipChange.bind(assertThisInitialized_default()(_this));
    return _this;
  }

  createClass_default()(HistoricalData, [{
    key: "makeQuery",
    value: function makeQuery(path, errorMessage) {
      var _this2 = this;

      var createNotice = this.props.createNotice;
      external_this_wp_apiFetch_default()({
        path: path,
        method: 'POST'
      }).then(function (response) {
        if (response.status === 'success') {
          createNotice('success', response.message);
        } else {
          createNotice('error', errorMessage);

          _this2.setState({
            activeImport: false,
            lastImportStopTimestamp: Date.now()
          });
        }
      }).catch(function (error) {
        if (error && error.message) {
          createNotice('error', error.message);

          _this2.setState({
            activeImport: false,
            lastImportStopTimestamp: Date.now()
          });
        }
      });
    }
  }, {
    key: "onImportFinished",
    value: function onImportFinished() {
      var debouncedSpeak = this.props.debouncedSpeak;
      debouncedSpeak('Import complete');
      this.setState({
        lastImportStopTimestamp: Date.now()
      });
    }
  }, {
    key: "onImportStarted",
    value: function onImportStarted() {
      var _this$props = this.props,
          notes = _this$props.notes,
          updateNote = _this$props.updateNote;
      var historicalDataNote = notes.find(function (note) {
        return note.name === 'wc-admin-historical-data';
      });

      if (historicalDataNote) {
        updateNote(historicalDataNote.id, {
          status: 'actioned'
        });
      }

      this.setState({
        activeImport: true,
        lastImportStartTimestamp: Date.now()
      });
    }
  }, {
    key: "onDeletePreviousData",
    value: function onDeletePreviousData() {
      var path = '/wc-analytics/reports/import/delete';

      var errorMessage = Object(external_this_wp_i18n_["__"])('There was a problem deleting your previous data.', 'woocommerce');

      this.makeQuery(path, errorMessage);
      this.setState({
        activeImport: false
      });
      Object(tracks["b" /* recordEvent */])('analytics_import_delete_previous');
    }
  }, {
    key: "onReimportData",
    value: function onReimportData() {
      this.setState({
        activeImport: false
      });
    }
  }, {
    key: "onStartImport",
    value: function onStartImport() {
      var _this$state = this.state,
          period = _this$state.period,
          skipChecked = _this$state.skipChecked;
      var path = Object(external_this_wp_url_["addQueryArgs"])('/wc-analytics/reports/import', utils_formatParams(this.dateFormat, period, skipChecked));

      var errorMessage = Object(external_this_wp_i18n_["__"])('There was a problem rebuilding your report data.', 'woocommerce');

      this.makeQuery(path, errorMessage);
      this.onImportStarted();
    }
  }, {
    key: "onStopImport",
    value: function onStopImport() {
      this.setState({
        lastImportStopTimestamp: Date.now()
      });
      var path = '/wc-analytics/reports/import/cancel';

      var errorMessage = Object(external_this_wp_i18n_["__"])('There was a problem stopping your current import.', 'woocommerce');

      this.makeQuery(path, errorMessage);
    }
  }, {
    key: "onPeriodChange",
    value: function onPeriodChange(val) {
      this.setState({
        activeImport: false,
        period: _objectSpread(_objectSpread({}, this.state.period), {}, {
          label: val
        })
      });
    }
  }, {
    key: "onDateChange",
    value: function onDateChange(val) {
      this.setState({
        activeImport: false,
        period: {
          date: val,
          label: 'custom'
        }
      });
    }
  }, {
    key: "onSkipChange",
    value: function onSkipChange(val) {
      this.setState({
        activeImport: false,
        skipChecked: val
      });
    }
  }, {
    key: "render",
    value: function render() {
      var _this$state2 = this.state,
          activeImport = _this$state2.activeImport,
          lastImportStartTimestamp = _this$state2.lastImportStartTimestamp,
          lastImportStopTimestamp = _this$state2.lastImportStopTimestamp,
          period = _this$state2.period,
          skipChecked = _this$state2.skipChecked;
      return Object(external_this_wp_element_["createElement"])(layout, {
        activeImport: activeImport,
        dateFormat: this.dateFormat,
        onImportFinished: this.onImportFinished,
        onImportStarted: this.onImportStarted,
        lastImportStartTimestamp: lastImportStartTimestamp,
        lastImportStopTimestamp: lastImportStopTimestamp,
        onPeriodChange: this.onPeriodChange,
        onDateChange: this.onDateChange,
        onSkipChange: this.onSkipChange,
        onDeletePreviousData: this.onDeletePreviousData,
        onReimportData: this.onReimportData,
        onStartImport: this.onStartImport,
        onStopImport: this.onStopImport,
        period: period,
        skipChecked: skipChecked
      });
    }
  }]);

  return HistoricalData;
}(external_this_wp_element_["Component"]);

/* harmony default export */ var historical_data = (Object(compose["a" /* default */])([Object(with_select["a" /* default */])(function (select) {
  var _select = select('wc-api'),
      getNotes = _select.getNotes;

  var notesQuery = {
    page: 1,
    per_page: constants["d" /* QUERY_DEFAULTS */].pageSize,
    type: 'update',
    status: 'unactioned'
  };
  var notes = getNotes(notesQuery);
  return {
    notes: notes
  };
}), Object(external_this_wp_data_["withDispatch"])(function (dispatch) {
  var _dispatch = dispatch('wc-api'),
      updateNote = _dispatch.updateNote;

  return {
    updateNote: updateNote
  };
}), with_spoken_messages["a" /* default */]])(historical_data_HistoricalData));
// CONCATENATED MODULE: ./client/analytics/settings/index.js





function settings_ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function settings_objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { settings_ownKeys(Object(source), true).forEach(function (key) { defineProperty_default()(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { settings_ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

/**
 * External dependencies
 */





/**
 * WooCommerce dependencies
 */



/**
 * Internal dependencies
 */






var SETTINGS_FILTER = 'woocommerce_admin_analytics_settings';

var settings_Settings = function Settings(_ref) {
  var createNotice = _ref.createNotice,
      query = _ref.query;

  var _useSettings = Object(external_this_wc_data_["useSettings"])('wc_admin', ['wcAdminSettings']),
      settingsError = _useSettings.settingsError,
      isRequesting = _useSettings.isRequesting,
      isDirty = _useSettings.isDirty,
      persistSettings = _useSettings.persistSettings,
      updateAndPersistSettings = _useSettings.updateAndPersistSettings,
      updateSettings = _useSettings.updateSettings,
      wcAdminSettings = _useSettings.wcAdminSettings;

  var hasSaved = Object(external_this_wp_element_["useRef"])(false);
  Object(external_this_wp_element_["useEffect"])(function () {
    function warnIfUnsavedChanges(event) {
      if (isDirty) {
        event.returnValue = Object(external_this_wp_i18n_["__"])('You have unsaved changes. If you proceed, they will be lost.', 'woocommerce');
        return event.returnValue;
      }
    }

    window.addEventListener('beforeunload', warnIfUnsavedChanges);
    return function () {
      return window.removeEventListener('beforeunload', warnIfUnsavedChanges);
    };
  }, [isDirty]);
  Object(external_this_wp_element_["useEffect"])(function () {
    if (isRequesting) {
      hasSaved.current = true;
      return;
    }

    if (!isRequesting && hasSaved.current) {
      if (!settingsError) {
        createNotice('success', Object(external_this_wp_i18n_["__"])('Your settings have been successfully saved.', 'woocommerce'));
      } else {
        createNotice('error', Object(external_this_wp_i18n_["__"])('There was an error saving your settings.  Please try again.', 'woocommerce'));
      }

      hasSaved.current = false;
    }
  }, [isRequesting, settingsError, createNotice]);

  var resetDefaults = function resetDefaults() {
    if ( // eslint-disable-next-line no-alert
    window.confirm(Object(external_this_wp_i18n_["__"])('Are you sure you want to reset all settings to default values?', 'woocommerce'))) {
      var resetSettings = Object.keys(config["b" /* config */]).reduce(function (result, setting) {
        result[setting] = config["b" /* config */][setting].defaultValue;
        return result;
      }, {});
      updateAndPersistSettings('wcAdminSettings', resetSettings);
      Object(tracks["b" /* recordEvent */])('analytics_settings_reset_defaults');
    }
  };

  var saveChanges = function saveChanges() {
    persistSettings();
    Object(tracks["b" /* recordEvent */])('analytics_settings_save', wcAdminSettings); // On save, reset persisted query properties of Nav Menu links to default

    query.period = undefined;
    query.compare = undefined;
    query.before = undefined;
    query.after = undefined;
    query.interval = undefined;
    query.type = undefined;
    window.wpNavMenuUrlUpdate(query);
  };

  var handleInputChange = function handleInputChange(e) {
    var _e$target = e.target,
        checked = _e$target.checked,
        name = _e$target.name,
        type = _e$target.type,
        value = _e$target.value;

    var nextSettings = settings_objectSpread({}, wcAdminSettings);

    if (type === 'checkbox') {
      if (checked) {
        nextSettings[name] = [].concat(toConsumableArray_default()(nextSettings[name]), [value]);
      } else {
        nextSettings[name] = nextSettings[name].filter(function (v) {
          return v !== value;
        });
      }
    } else {
      nextSettings[name] = value;
    }

    updateSettings('wcAdminSettings', nextSettings);
  };

  return Object(external_this_wp_element_["createElement"])(external_this_wp_element_["Fragment"], null, Object(external_this_wp_element_["createElement"])(external_this_wc_components_["SectionHeader"], {
    title: Object(external_this_wp_i18n_["__"])('Analytics Settings', 'woocommerce')
  }), Object(external_this_wp_element_["createElement"])("div", {
    className: "woocommerce-settings__wrapper"
  }, Object.keys(config["b" /* config */]).map(function (setting) {
    return Object(external_this_wp_element_["createElement"])(analytics_settings_setting, extends_default()({
      handleChange: handleInputChange,
      value: wcAdminSettings[setting],
      key: setting,
      name: setting
    }, config["b" /* config */][setting]));
  }), Object(external_this_wp_element_["createElement"])("div", {
    className: "woocommerce-settings__actions"
  }, Object(external_this_wp_element_["createElement"])(build_module_button["a" /* default */], {
    isSecondary: true,
    onClick: resetDefaults
  }, Object(external_this_wp_i18n_["__"])('Reset Defaults', 'woocommerce')), Object(external_this_wp_element_["createElement"])(build_module_button["a" /* default */], {
    isPrimary: true,
    isBusy: isRequesting,
    onClick: saveChanges
  }, Object(external_this_wp_i18n_["__"])('Save Settings', 'woocommerce')))), query.import === 'true' ? Object(external_this_wp_element_["createElement"])(external_this_wc_components_["ScrollTo"], {
    offset: "-56"
  }, Object(external_this_wp_element_["createElement"])(historical_data, {
    createNotice: createNotice
  })) : Object(external_this_wp_element_["createElement"])(historical_data, {
    createNotice: createNotice
  }));
};

/* harmony default export */ var analytics_settings = __webpack_exports__["default"] = (Object(compose["a" /* default */])(Object(external_this_wp_data_["withDispatch"])(function (dispatch) {
  var _dispatch = dispatch('core/notices'),
      createNotice = _dispatch.createNotice;

  return {
    createNotice: createNotice
  };
}))(Object(external_this_wc_components_["useFilters"])(SETTINGS_FILTER)(settings_Settings)));

/***/ })

}]);
